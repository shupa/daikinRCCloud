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

			/*** Verification que l'on a bien toute les data ***/
			if (!isset($data['managementPoints'])) return FALSE;
			if (!is_array($data['managementPoints'])) return FALSE;

			/*** Analyse de tout les Management Points de l'api ***/
			foreach ($data['managementPoints'] as $MPName => $managementPoint) {
				if (!is_array($managementPoint)) continue;
				/*** Analyse de tout les Datapoint de chaque Management Points ***/
                foreach ($managementPoint as $DTName => $dataPoint) {
					if (!is_array($dataPoint)) continue;
					/*** Verification de si il y a un DataPointPatch ***/
					if (isset($dataPoint['settable'])) {
					    /*** Creation de la commande avec les information ***/
						self::createCMD($_eqLogic, $dataPoint, $MPName, $DTName);
					} else {
					    /*** Verification de tout les Data Point Patch de ce Data Point ***/
						foreach ($dataPoint as $DPHName => $dataPointPath) {
							if (isset($dataPointPath['settable'])) {
                                /*** Creation de la commande avec les information récupére ***/
								self::createCMD($_eqLogic, $dataPointPath, $MPName, $DTName, $DPHName);
							}
						}
					}
				}
			}
		}

		private static function createCMD($_eqLogic, $data, $_managementPoint, $_dataPoint, $_dataPointPath = NULL)
		{
			$deviceType = $_eqLogic->getConfiguration('deviceType', FALSE);
			$devicesModel = $_eqLogic->getConfiguration('devicesModel', FALSE);

			$def = self::getFile($deviceType, $devicesModel);
			$cmdData = NULL;
			$isCommunData = 0;
			$dataPatchEdit = "";


			if (!is_null($_dataPointPath)) {
			    $pointPatchData = self::getSpecDataPatch($_dataPointPath);
			    $isCommunData = $pointPatchData['commun'];
                $dataPatchEdit = $pointPatchData['dataPatchEdit'];
			    $_dataPointPath = $pointPatchData['dataPatch'];
				if (isset($def[$_managementPoint][$_dataPoint][$_dataPointPath])) $cmdData = $def[$_managementPoint][$_dataPoint][$_dataPointPath];
			} else {
				if (isset($def[$_managementPoint][$_dataPoint])) $cmdData = $def[$_managementPoint][$_dataPoint];
			}

			if (is_null($cmdData)) return;

			foreach ($cmdData as $cmd) {
				$daikinRCCloudCmd = $_eqLogic->getCmd(NULL, $cmd['logicalId']);
				if (!is_object($daikinRCCloudCmd)) {
					$daikinRCCloudCmd = new daikinRCCloudCmd();
					$daikinRCCloudCmd->setName($cmd['name']);
					$daikinRCCloudCmd->setType($cmd['type']);
					$daikinRCCloudCmd->setSubType($cmd['subType']);
					$daikinRCCloudCmd->setIsVisible($cmd['visible']);

					$daikinRCCloudCmd->setEqLogic_id($_eqLogic->getId());
					$daikinRCCloudCmd->setLogicalId($cmd['logicalId']);

					$daikinRCCloudCmd->setConfiguration("managementPoint", $_managementPoint);
					$daikinRCCloudCmd->setConfiguration("dataPoint", $_dataPoint);
					if (isset($cmd['historized'])) $daikinRCCloudCmd->setIsHistorized($cmd['historized']);
					if (isset($cmd['widget'])) $daikinRCCloudCmd->setTemplate('dashboard', $cmd['widget']);
					if (isset($cmd['unite'])) $daikinRCCloudCmd->setUnite($cmd['unite']);
					if (isset($cmd['value'])) $daikinRCCloudCmd->setConfiguration("value", $cmd['value']);
					if (!is_null($_dataPointPath)) $daikinRCCloudCmd->setConfiguration("dataPointPath", $_dataPointPath);

					if (isset($cmd['info'])) {
						$value = $_eqLogic->getCmd('info', $cmd['info']);
						if (is_object($value)) {
							$daikinRCCloudCmd->setValue($value->getID());
						}
					}

					if (isset($cmd['possibleValue'])) {
						$values = "";
						foreach ($cmd['possibleValue'] as $key => $value) {
							$values .= $key . "|" . $value . ";";
						}
						$values = substr_replace($values, "", -1);
						$daikinRCCloudCmd->setConfiguration('listValue', $values);
					}
				}
				if (isset($data['minValue'])) $daikinRCCloudCmd->setConfiguration("minValue", $data['minValue']);
				if (isset($data['maxValue'])) $daikinRCCloudCmd->setConfiguration("maxValue", $data['maxValue']);

				if ($isCommunData) {
                    $daikinRCCloudCmd->setConfiguration("dataPointPathMutable", $dataPatchEdit);
                    $daikinRCCloudCmd->setConfiguration("isCommunData", $isCommunData);
                    $daikinRCCloudCmd->setConfiguration("mode", array("heating", "cooling", "auto", "dry"));
                }

				$daikinRCCloudCmd->save();

				$daikinRCCloudCmd->event($data['value']);
			}
		}

		private static function getSpecDataPatch($dataPatch) {
		    $result = array("dataPatch"=>"", "commun"=>0, "dataPatchEdit"=>"");
		    switch ($dataPatch) {
                case "/operationModes/cooling/fanSpeed/currentMode":
                case "/operationModes/heating/fanSpeed/currentMode":
                case "/operationModes/auto/fanSpeed/currentMode":
                case "/operationModes/dry/fanSpeed/currentMode":
                    $result["dataPatch"] = "/operationModes/cooling/fanSpeed/currentMode";
                    $result["dataPatchEdit"] = "/operationModes/#mode#/fanSpeed/currentMode";
                    $result["commun"] = 1;
                    break;
                case "/operationModes/cooling/fanSpeed/modes/fixed":
                case "/operationModes/heating/fanSpeed/modes/fixed":
                case "/operationModes/auto/fanSpeed/modes/fixed":
                case "/operationModes/dry/fanSpeed/modes/fixed":
                    $result["dataPatch"] = "/operationModes/cooling/fanSpeed/modes/fixed";
                $result["dataPatchEdit"] = "/operationModes/#mode#/fanSpeed/modes/fixed";
                $result["commun"] = 1;
                    break;
                case "/operationModes/cooling/fanDirection/horizontal/currentMode":
                case "/operationModes/heating/fanDirection/horizontal/currentMode":
                case "/operationModes/auto/fanDirection/horizontal/currentMode":
                case "/operationModes/dry/fanDirection/horizontal/currentMode":
                     $result["dataPatch"] = "/operationModes/cooling/fanDirection/horizontal/currentMode";
                $result["dataPatchEdit"] = "/operationModes/#mode#/fanDirection/horizontal/currentMode";
                $result["commun"] = 1;
                    break;
                case "/operationModes/cooling/fanDirection/vertical/currentMode":
                case "/operationModes/heating/fanDirection/vertical/currentMode":
                case "/operationModes/auto/fanDirection/vertical/currentMode":
                case "/operationModes/dry/fanDirection/vertical/currentMode":
                    $result["dataPatch"] = "/operationModes/cooling/fanDirection/vertical/currentMode";
                $result["dataPatchEdit"] = "/operationModes/#mode#/fanDirection/vertical/currentMode";
                $result["commun"] = 1;
                    break;
                default:
                    $result["dataPatch"] = $dataPatch;
            }
            return $result;
        }


		/** Va permettre d'avoir des json pré générer par le plugin **/
		public static function generateJson($_eqLogicID) {
			$_eqLogic = eqLogic::byLogicalId($_eqLogicID, "daikinRCCloud");

			if (!is_object($_eqLogic)) return FALSE;
			$result = array();

			$deviceID = $_eqLogic->getConfiguration('deviceID', false);
			if ($deviceID == FALSE) return False;
			$data = daikinRCCloud_deamon::getDevicesByID($deviceID);

			if (!isset($data['managementPoints'])) return array("error"=>"On a pas trouver de Point de management");
			if (!is_array($data['managementPoints'])) return array("error"=>"On a pas trouver de Point de management");

			foreach ($data['managementPoints'] as $managementPointName => $managementPoint) {
				if (!is_array($managementPoint)) continue;
				foreach ($managementPoint as $dataPointName => $dataPoint) {
					if (!is_array($dataPoint)) continue;
					if (isset($dataPoint['settable'])) {

						$result[$managementPointName][$dataPointName][] = array(
							"name" => $dataPointName,
							"logicalId"=> $managementPointName.$dataPointName,
							"type" => "",
							"subType"=>"string",
							"visible"=> 0, "historized"=>0
						);

					} else {
						foreach ($dataPoint as $dataPointPathName => $dataPointPath) {
							if (isset($dataPointPath['settable'])) {
								$result[$managementPointName][$dataPointName][$dataPointPathName][] = array(
									"name" => $dataPointName,
									"logicalId"=> $managementPointName.$dataPointName,
									"type" => "",
									"subType"=>"string",
									"visible"=> 0, "historized"=>0
								);
							}
						}
					}
				}
			}

			return $result;
		}

	}