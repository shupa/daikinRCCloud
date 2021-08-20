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
	require_once __DIR__  . '/../../../../core/php/core.inc.php';
	require_once dirname(__FILE__) . '/../../core/php/daikinRCCloud.inc.php';


	/*** Verification de la clée api du plugin ***/
	if (!jeedom::apiAccess(init('apikey'), 'daikinRCCloud')) {
		echo __('Vous n\'êtes pas autorisé à effectuer cette action', __FILE__);
		log::add('daikinRCCloud', 'debug',  'Clé API Invalide');
		die();
	}

	/*** Reponse en cas de commande de test ***/
	if (init('test') != '') {
		echo 'OK';
		die();
	}

	/*** Recuperation des donné recu ***/
	$recoversArray = file_get_contents("php://input");
	$action=$_GET["action"];
	log::add('daikinRCCloud', 'debug',  'Réception données sur jeeDaikinRCCloud ['.$action.']');

	log::add('daikinRCCloud', 'debug',  "chaineRecuperee: ".$recoversArray);

	$start=strpos($recoversArray, "{");
	$finish=strrpos($recoversArray, "}");
	$lengh=1+intval($finish)-intval($start);
	$recoversArray=substr($recoversArray, $start, $lengh);

	$result = json_decode($recoversArray, true);

	/*** Verification du format des donné ***/
	if (!is_array($result)) {
		log::add('daikinRCCloud_api', 'error', 'Format API Invalide');
		die();
	}

	/*** Execution de l'action demander ***/
	switch ($action) {
		case 'log':
			log::add('daikinRCCloud_api', $result['level'], $result['text']);
			break;
		default:
			log::add('daikinRCCloud', 'debug', "Erreur, api inconnu : ".$action);
			die();
	}