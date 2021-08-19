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

	try {
		require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
		include_file('core', 'authentification', 'php');

		if (!isConnect('admin')) {
			throw new Exception(__('401 - Accès non autorisé', __FILE__));
		}

		ajax::init();

		if (init('action') == 'regenToken') {
			log::add('daikinRCCloud', 'info', 'Regen token en cours');
			$sensor_path = realpath(dirname(__FILE__) . '/../../resources');
			if (!file_exists($sensor_path . '/tokenset.json')) {
				log::add('daikinRCCloud_token', 'info', 'Récupération d\'un token de connexion');

				$cmd = 'node ' . $sensor_path . '/tokensaver.js "' . config::byKey('login', 'daikinRCCloud') . '" "' . config::byKey('password', 'daikinRCCloud').'"';
				log::add('daikinRCCloud_token', 'debug', 'Lancement de la recuperation du token : ' . $cmd);
				$result = exec($cmd . ' >> ' . log::getPathToLog('daikinRCCloud_token') . ' 2>&1 &');
				if (strpos(strtolower($result), 'error') !== FALSE || strpos(strtolower($result), 'traceback') !== FALSE) {
					log::add('daikinRCCloud', 'error', $result);
					return FALSE;
				}
			}
			log::add('daikinRCCloud', 'info', 'Fin de la regen du token');
			ajax::success();
		}


		throw new Exception(__('Aucune méthode correspondante à : ', __FILE__) . init('action'));
		/*     * *********Catch exeption*************** */
	} catch (Exception $e) {
		ajax::error(displayException($e), $e->getCode());
	}

