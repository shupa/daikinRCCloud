<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once __DIR__ . '/../../../../core/php/core.inc.php';

class daikinRCCloud_deamon
{
    public static function getDevices()
    {
        log::add('daikinRCCloud', 'debug', 'Recuperation de tout les equipment');

		return self::request("devices");
    }

    public static function getDevicesByID($_deviceID) {
        log::add('daikinRCCloud', 'debug', 'Recuperation de l\'equipment par un ID');
		return self::request("devices", $_deviceID);
    }

    public static function executeAction($_deviceID, $datas) {
		log::add('daikinRCCloud', 'debug', 'Envoi de l\'action au deamon');

		$params = "?";

		foreach ($datas as $key => $data) {
			$data = urlencode($data);
			$params.=$key."=".$data."&";
		}
		$params = substr_replace($params ,"", -1);

		return self::request("setdata", $_deviceID, $params);
	}

    public static function executeActions($_deviceID, $datas) {
        log::add('daikinRCCloud', 'debug', 'Envoi de l\'action au deamon');

        $params = "?";

        foreach ($datas as $key => $data) {
            $data = urlencode($data);
            $params.=$key."=".$data."&";
        }
        $params = substr_replace($params ,"", -1);

        return self::request("setdatas", $_deviceID, $params);
    }

    private static function request($_endPoint, $_devicesID = null, $_params = null)
    {
        $deamon = daikinRCCloud::deamon_info();
        if ($deamon['state'] == 'ok') {
            log::add('daikinRCCloud', 'debug', 'Nouvelle demande au Deamon | EndPoint : ' . $_endPoint . '  | Device : ' . $_devicesID);
            /*** Creation de l'url pour la demand du demon ***/
            $url = "http://" . config::byKey('internalAddr') . ":8890/".$_endPoint;
			if ($_devicesID != null) $url .= "/".$_devicesID;
			if ($_params != NULL)  $url.= $_params;
			log::add('daikinRCCloud', 'debug', 'Url de la demande : ' . $url);
			/*** Creation de la request HTTP ***/
            $request_http = new com_http($url);
            $request_http->setAllowEmptyReponse(true);//Autorise les r??ponses vides
            $request_http->setNoSslCheck(true);
            $request_http->setNoReportError(true);
            /*** Execution de la request ***/
            $result = $request_http->exec(6, 3);//Time out ?? 3s 3 essais
            /*** Verification de la request ***/
            if (!$result) {
                log::add('daikinRCCloud', 'debug', 'Pas de Data');
                return "No Data";
            }

            /*** Format de la response ***/
            log::add('daikinRCCloud', 'debug', 'Result de la demand : ' . $result);
            return json_decode($result, true);
        }

        return "Deamon NOk";
    }
}