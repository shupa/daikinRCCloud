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

class daikinRCCloud extends eqLogic {
    /*     * *************************Attributs****************************** */
    
  /*
   * Permet de définir les possibilités de personnalisation du widget (en cas d'utilisation de la fonction 'toHtml' par exemple)
   * Tableau multidimensionnel - exemple: array('custom' => true, 'custom::layout' => false)
	public static $_widgetPossibility = array();
   */
    
    /*     * ***********************Methode static*************************** */

    /*
     * Fonction exécutée automatiquement toutes les minutes par Jeedom*/
      public static function cron() {
		  $eqLogics = eqLogic::byType('daikinRCCloud');
		  foreach ($eqLogics as $eqLogic) {
			  daikinRCCloud_data::updateCMDInfo($eqLogic);
		  }
      }


    /*
     * Fonction exécutée automatiquement toutes les 5 minutes par Jeedom
      public static function cron5() {

      }
*/

    /*
     * Fonction exécutée automatiquement toutes les 10 minutes par Jeedom
      public static function cron10() {
      }
     */
    
    /*
     * Fonction exécutée automatiquement toutes les 15 minutes par Jeedom
      public static function cron15() {
      }
     */
    
    /*
     * Fonction exécutée automatiquement toutes les 30 minutes par Jeedom
      public static function cron30() {
      }
     */
    
    /*
     * Fonction exécutée automatiquement toutes les heures par Jeedom
      public static function cronHourly() {
      }
     */

	/*
	 * Fonction exécutée automatiquement tous les jours par Jeedom */
	  public static function cronDaily() {
	  	daikinRCCloud_data::retrieveDevices();
	  }



	/*     * *********************Méthodes d'instance************************* */

	public static function dependancy_info()
	{
		$return = array();
		$return['log'] = 'daikinRCCloud_dep';
		$resources = realpath(dirname(__FILE__) . '/../../resources/');
		$packageJson = json_decode(file_get_contents($resources . '/package.json'), TRUE);
		$state = 'ok';
		foreach ($packageJson["dependencies"] as $dep => $ver) {
			if (!file_exists($resources . '/node_modules/' . $dep . '/package.json')) {
				$state = 'nok';
			}
		}
		$return['progress_file'] = jeedom::getTmpFolder('daikinRCCloud') . '/dependance';
		$return['state'] = $state;
		return $return;
	}

	public static function dependancy_install($verbose = "false")
	{
		if (file_exists(jeedom::getTmpFolder('daikinRCCloud') . '/dependance')) {
			return FALSE;
		}
		log::remove('daikinRCCloud_dep');
		$_debug = 0;
		if (log::getLogLevel('daikinRCCloud') == 100 || $verbose === "true" || $verbose === TRUE) $_debug = 1;
		log::add('daikinRCCloud', 'info', 'Installation des dépendances : ');
		$resource_path = realpath(dirname(__FILE__) . '/../../resources');
		return array('script' => $resource_path . '/install.sh ' . $resource_path . ' daikinRCCloud ' . $_debug, 'log' => log::getPathToLog('daikinRCCloud_dep'));
	}

	public static function deamon_info()
	{
		$return = array();
		$return['log'] = 'daikinRCCloud_node';
		$return['state'] = 'nok';

		// Regarder si daikinRCCloud.js est lancé
		$pid = trim(shell_exec('ps ax | grep "resources/daikinRCCloud.js" | grep -v "grep" | wc -l'));
		if ($pid != '' && $pid != '0') $return['state'] = 'ok';

		$sensor_path = realpath(dirname(__FILE__) . '/../../resources');
		if (!file_exists($sensor_path . '/tokenset.json')) {
			$return['launchable'] = 'nok';
			$return['launchable_message'] = "Not find token File";
		} else {
			$return['launchable'] = 'ok';
		}

		return $return;
	}

	public static function deamon_start($_debug = FALSE)
	{
		self::deamon_stop();
		$deamon_info = self::deamon_info();
		if ($deamon_info['launchable'] != 'ok') throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
		log::add('daikinRCCloud', 'info', 'Lancement du deamon');

		$sensor_path = realpath(dirname(__FILE__) . '/../../resources');
		$cmd = 'nice -n 19 node ' . $sensor_path . '/daikinRCCloud.js "'.network::getNetworkAccess('internal').'" "'.jeedom::getApiKey('daikinRCCloud').'"';
		log::add('daikinRCCloud', 'debug', 'Lancement démon daikinRCCloud : ' . $cmd);
		$result = exec('NODE_ENV=production nohup ' . $cmd . ' >> ' . log::getPathToLog('daikinRCCloud_node') . ' 2>&1 &');
		if (strpos(strtolower($result), 'error') !== FALSE || strpos(strtolower($result), 'traceback') !== FALSE) {
			log::add('daikinRCCloud', 'error', $result);
			return FALSE;
		}
		$i = 0;
		while ($i < 30) {
			$deamon_info = self::deamon_info();
			if ($deamon_info['state'] == 'ok') break;
			sleep(1);
			$i++;
		}
		if ($i >= 30) {
			log::add('daikinRCCloud', 'error', 'Impossible de lancer le démon daikinRCCloud, vérifiez le port', 'unableStartDeamon');
			return FALSE;
		}
		message::removeAll('daikinRCCloud', 'unableStartDeamon');
		log::add('daikinRCCloud', 'info', 'Démon daikinRCCloud lancé');
		return TRUE;
	}

	public static function deamon_stop()
	{
		log::add('daikinRCCloud', 'info', 'Arrêt du service daikinRCCloud');
		@file_get_contents("http://" . config::byKey('internalAddr') . ":8890/stop");
		sleep(3);
		if (shell_exec('ps aux | grep "resources/daikinRCCloud.js" | grep -v "grep" | wc -l') == '1') {
			exec('sudo kill $(ps aux | grep "resources/daikinRCCloud.js" | grep -v "grep" | awk \'{print $2}\') &>/dev/null');
			$deamon_info = self::deamon_info();
			if ($deamon_info['state'] == 'ok') {
				sleep(1);
				exec('sudo kill -9 $(ps aux | grep "resources/daikinRCCloud.js" | grep -v "grep" | awk \'{print $2}\') &>/dev/null');
			}
			$deamon_info = self::deamon_info();
			if ($deamon_info['state'] == 'ok') {
				sleep(1);
				exec('sudo kill -9 $(ps aux | grep "resources/daikinRCCloud.js" | grep -v "grep" | awk \'{print $2}\') &>/dev/null');
			}
		}
	}

	// Fonction exécutée automatiquement avant la création de l'équipement
	public function preInsert()
	{

	}

	// Fonction exécutée automatiquement après la création de l'équipement
	public function postInsert()
	{

	}

	// Fonction exécutée automatiquement avant la mise à jour de l'équipement
    public function preUpdate() {
        
    }

 // Fonction exécutée automatiquement après la mise à jour de l'équipement 
    public function postUpdate() {
        
    }

 // Fonction exécutée automatiquement avant la sauvegarde (création ou mise à jour) de l'équipement 
    public function preSave() {
        
    }

 // Fonction exécutée automatiquement après la sauvegarde (création ou mise à jour) de l'équipement 
    public function postSave() {
        daikinRCCloud_generator::updateCMD($this);
    }

 // Fonction exécutée automatiquement avant la suppression de l'équipement 
    public function preRemove() {
        
    }

 // Fonction exécutée automatiquement après la suppression de l'équipement 
    public function postRemove() {
        
    }


    /*
     * Non obligatoire : permet de modifier l'affichage du widget (également utilisable par les commandes)
      public function toHtml($_version = 'dashboard') {

      }
     */

    /*
     * Non obligatoire : permet de déclencher une action après modification de variable de configuration
    public static function postConfig_<Variable>() {
    }
     */

    /*
     * Non obligatoire : permet de déclencher une action avant modification de variable de configuration
    public static function preConfig_<Variable>() {
    }
     */

    /*     * **********************Getteur Setteur*************************** */
}

class daikinRCCloudCmd extends cmd {
    /*     * *************************Attributs****************************** */
    
    /*
      public static $_widgetPossibility = array();
    */
    
    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    /*
     * Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
      public function dontRemoveCmd() {
      return true;
      }
     */

  // Exécution d'une commande  
     public function execute($_options = array()) {
     	if ($this->getType() == "action") {
			$managementPoint = $this->getConfiguration("managementPoint",NULL);
			$dataPointPath = $this->getConfiguration("dataPointPath",NULL);
			$dataPoint = $this->getConfiguration("dataPoint",NULL);
			$dataValue = $this->getConfiguration("value",NULL);
			$dataPointPathMutable = $this->getConfiguration("dataPointPathMutable",NULL);
			$isCommunData = $this->getConfiguration("isCommunData",0);
			$modes = $this->getConfiguration("mode",false);

			if (isset($_options['select'])) $dataValue = $_options['select'];
			if (isset($_options['slider'])) $dataValue = $_options['slider'];

			$eqLogics = $this->getEqLogic();
			$deviceID = $eqLogics->getConfiguration('deviceID', false);

			if(substr($managementPoint, 0, 3) === "mp_"){
				$managementPoint = str_replace("mp_", '', $managementPoint);
				$managementPoint = intval($managementPoint);
			}

			$params = array(
				"managementPoint" =>$managementPoint,
				"dataPoint" =>$dataPoint,
				"dataValue" =>$dataValue,
			);
			if (!is_null($dataPointPath)) $params['dataPointPath'] = $dataPointPath;

			if ($isCommunData == 1) {
                $data = array();
                foreach ($modes as $mode) {
                    $tempsParams = $params;
                    $tempsParams['dataPointPath'] = str_replace("#mode#",$mode,$dataPointPathMutable);
                    $data[] = json_encode($tempsParams);
                }
                daikinRCCloud_deamon::executeActions($deviceID, $data);
            } else {
                if (!is_null($dataPointPath)) $params['dataPointPath'] = $dataPointPath;
                daikinRCCloud_deamon::executeAction($deviceID, $params);
            }

            sleep(1);

			daikinRCCloud_data::updateCMDInfo($eqLogics);
		}
     }

    /*     * **********************Getteur Setteur*************************** */
}


