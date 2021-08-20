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

        $result = self::request("devices");

        log::add('daikinRCCloud', 'error', 'data : '.$result);

        return $result;
    }

    public static function getDevicesByID($_deviceID) {
        log::add('daikinRCCloud', 'debug', 'Recuperation de l\'equipment');

        $result = self::request("devices", $_deviceID);

        log::add('daikinRCCloud', 'error', 'data : '.$result);

        return $result;
    }

    private static function request($_endPoint, $_devicesID = null, $_params = null)
    {
        $deamon = daikinRCCloud::deamon_info();
        if ($deamon['state'] == 'ok') {
            log::add('daikinRCCloud', 'debug', 'Nouvelle demande au Deamon | EndPoint : ' . $_endPoint . ' | Params : ' . $_params . ' | Device : ' . $_devicesID);
            /*** Creation de l'url pour la demand du demon ***/
            $url = "http://" . config::byKey('internalAddr') . ":8890/".$_endPoint;
            log::add('daikinRCCloud', 'debug', 'Url de la demande : ' . $url);
            if ($_devicesID != null) $url .= "/".$_devicesID;
            log::add('daikinRCCloud', 'error', "Coucou 1");
            /*** Creation de la request HTTP ***/
            $request_http = new com_http($url);
            $request_http->setAllowEmptyReponse(true);//Autorise les réponses vides
            $request_http->setNoSslCheck(true);
            $request_http->setNoReportError(true);
            /*** Execution de la request ***/
            log::add('daikinRCCloud', 'error', "Coucou 2");
            $result = $request_http->exec(6, 3);//Time out à 3s 3 essais

            log::add('daikinRCCloud', 'error', "Coucou 3");
            log::add('daikinRCCloud', 'error', $result);

            /*** Verification de la request ***/
            if (!$result) return "No Data";
            /*** Format de la response ***/
            $result = substr($result, 1, -1);
            log::add('daikinRCCloud', 'debug', 'Result de la demand : ' . $result);
            return json_decode($result, true);
        }

        return "deamon NOk";
    }
}