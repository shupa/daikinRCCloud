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

	class daikinRCCloud_generator
	{
		private static function getFile($_type, $_model) {
			$url = __DIR__ .  "/../data/".$_type."_".$_model.".json";

			$readJSONFile = file_get_contents($url);
			return json_decode($readJSONFile, TRUE);
		}

		public static function updateCMD($_eqLogic) {
			/*** Recuperation de l'id de l'équipement ***/
			$deviceID = $_eqLogic->getConfiguration('deviceID', false);
			if ($deviceID == FALSE) return False;
			/*** Recuperation des data de l'équipement ***/
			$data = daikinRCCloud_deamon::getDevicesByID($deviceID);

			if (!isset($data['managementPoints'])) return FALSE;
			if (!is_array($data['managementPoints'])) return FALSE;

			foreach ($data['managementPoints'] as $MPName => $managementPoint) {
				if (!is_array($managementPoint)) continue;
				foreach ($managementPoint as $DTName => $dataPoint) {
					if (!is_array($dataPoint)) continue;
					if (isset($dataPoint['settable'])) {
						self::createCMD($_eqLogic, $dataPoint, $MPName, $DTName);
					}
				}
			}
		}

		private static function createCMD($_eqLogic, $data, $_managementPoint, $_dataPoint, $_dataPointPath = NULL) {
			$deviceType = $_eqLogic->getConfiguration('deviceType', false);
			$devicesModel = $_eqLogic->getConfiguration('devicesModel', false);

			$def = self::getFile($deviceType, $devicesModel);

			if (is_null($_dataPointPath)) {
				if (isset($def[$_managementPoint][$_dataPoint])) {
					$cmdData = $def[$_managementPoint][$_dataPoint];
					foreach ($cmdData as $cmd) {
						$daikinRCCloudCmd = $_eqLogic->getCmd(null, $cmd['logicalId']);
						if (!is_object($daikinRCCloudCmd)) {
							$daikinRCCloudCmd = new daikinRCCloudCmd();
							$daikinRCCloudCmd->setName($cmd['name']);
							$daikinRCCloudCmd->setType($cmd['type']);
							$daikinRCCloudCmd->setSubType($cmd['subType']);
							$daikinRCCloudCmd->setIsVisible($cmd['visible']);
							$daikinRCCloudCmd->setEqLogic_id($_eqLogic->getId());
							$daikinRCCloudCmd->setLogicalId($cmd['logicalId']);

							$daikinRCCloudCmd->setConfiguration("managementPoint",$_managementPoint);
							$daikinRCCloudCmd->setConfiguration("dataPoint",$_dataPoint);
							if (!is_null($_dataPointPath)) $daikinRCCloudCmd->setConfiguration("dataPointPath",$_dataPointPath);

							if (isset($cmd['info'])) {
								$value = $_eqLogic->getCmd('info', $cmd['info']);
								if (is_object($value)) {
									$daikinRCCloudCmd->setValue($value->getID());
								}
							}
						}
						if (isset($data['minValue'])) $daikinRCCloudCmd->setConfiguration("minValue",$data['minValue']);
						if (isset($data['maxValue'])) $daikinRCCloudCmd->setConfiguration("maxValue",$data['maxValue']);

						$daikinRCCloudCmd->save();

						$daikinRCCloudCmd->event($data['value']);
					}
				}
			}
		}
	}