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

	class daikinRCCloud_data
	{

		public static function retrieveDevices() {
			$devices = daikinRCCloud_deamon::getDevices();

			foreach ($devices as $device) {

				if (!isset($device['_id'])) continue;

				$id = $device['_id'];
				$type = $device['type'];
				$deviceModel = $device['deviceModel'];
				$name = $device['managementPoints'][0]['serialNumber']['value'];


				$eqLogic = eqLogic::byLogicalId($id, 'daikinRCCloud');
				if (!is_object($eqLogic)) {
					$eqLogic = new daikinRCCloud();
					$eqLogic->setName($name);
					$eqLogic->setLogicalId($id);
					$eqLogic->setEqType_name("daikinRCCloud");
					$eqLogic->setConfiguration('deviceID', $id);
					$eqLogic->setConfiguration('deviceType', $type);
					$eqLogic->setConfiguration('devicesModel', $deviceModel);
					$eqLogic->setIsEnable(1);
					$eqLogic->setIsVisible(0);
					$eqLogic->save();
				}
			}
		}

	}