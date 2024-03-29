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
require_once dirname(__FILE__) . '/../../3rdparty/netroController.class.php';

define('__ROOT_NETRO_ARROSAGE__', dirname(dirname(dirname(__FILE__))));
define('__PLUGIN_NAME_NETRO_ARROSAGE__', 'netroarrosage');

use NetroPublicAPI\netroController;
use NetroPublicAPI\netroSensor;
use NetroPublicAPI\netroZone;
use NetroPublicAPI\netroFunction;

class netroarrosage extends eqLogic {
  /*     * *************************Attributs****************************** */

  /*
  * Permet de définir les possibilités de personnalisation du widget (en cas d'utilisation de la fonction 'toHtml' par exemple)
  * Tableau multidimensionnel - exemple: array('custom' => true, 'custom::layout' => false)
  public static $_widgetPossibility = array();
  */

  /*
  * Permet de crypter/décrypter automatiquement des champs de configuration du plugin
  * Exemple : "param1" & "param2" seront cryptés mais pas "param3"
  public static $_encryptConfigKey = array('param1', 'param2');
  */

  /*     * ***********************Methode static*************************** */

  public static function synchronize() {
    $config = array("ctrl_serial_n" => config::byKey('ctrl_serial_n', __PLUGIN_NAME_NETRO_ARROSAGE__),
                    "sensor_serial_n" => config::byKey('sensor_serial_n', __PLUGIN_NAME_NETRO_ARROSAGE__),
                    "default_parent_object" => config::byKey('default_parent_object', __PLUGIN_NAME_NETRO_ARROSAGE__),
                    "netroBaseURL" => config::byKey('netroBaseURL', __PLUGIN_NAME_NETRO_ARROSAGE__));

    log::add(__PLUGIN_NAME_NETRO_ARROSAGE__, 'debug', 'synchronize:: config : ' . var_export($config, true));

    // initialisation de l'API Netro avec l'URL en vigueur
    if (!empty($config["netroBaseURL"]))
      NetroPublicAPI\init($config["netroBaseURL"]);

    if (!empty($config["ctrl_serial_n"])) {  // création ou mise à jour des controleurs
      $controller_serials = explode(" ", $config["ctrl_serial_n"]); // les numéros de série sont séparés par des espaces
      $sensor_index = 0;
      log::add(__PLUGIN_NAME_NETRO_ARROSAGE__, 'debug', 'synchronize:: controller_serials : ' . var_export($controller_serials, true));

      foreach ($controller_serials as $controller_serial) {

        // recherche d'un équipement possédant le numéro de série du controleur
        $eqLogicController = eqLogic::byLogicalId($controller_serial, __PLUGIN_NAME_NETRO_ARROSAGE__);

        // si pas trouvé il faut créer un équipement vide
        if (!is_object($eqLogicController)) {
          log::add(__PLUGIN_NAME_NETRO_ARROSAGE__, 'debug', 'synchronize:: ' . __('nouveau contrôleur', __FILE__));
          $eqLogicController = new netroarrosage();
        }
        else{
          log::add(__PLUGIN_NAME_NETRO_ARROSAGE__, 'debug', 'synchronize:: ' . __('contrôleur existant', __FILE__));          
        }

        // chargement des données du controlleur depuis Netro
        $nc = new netroController($controller_serial);
        $nc->loadInfo();
        $nc->loadMoistures();
        $nc->loadSchedules(self::getSchedulesStartDate(), self::getSchedulesEndDate());

        log::add(__PLUGIN_NAME_NETRO_ARROSAGE__, 'debug', 'synchronize:: ' . __('contrôleur netro', __FILE__) . ' : ['
          . $nc->name . ', '
          . $nc->status . ', '
          . $nc->zone_number . ', '
          . $nc->last_active_time . ', '
          . count($nc->active_zones)
          . ']');        

        // mise à jour de l'équipement et de ses commandes avec les données de Netro 
        $eqLogicController->updateEqLogicController($nc, $config["default_parent_object"]);

        $eqLogicController->createCmd();

        { // création ou mise à jour des zones
          foreach ($nc->active_zones as $zoneId => $zone) {
            // recherche d'une zone possédant le numéro "série du controleur_zoneId"
            $eqLogicZone = eqLogic::byLogicalId($controller_serial . '_' . $zoneId, __PLUGIN_NAME_NETRO_ARROSAGE__);

            // si pas trouvé il faut créer un équipement de type zone vide
            if (!is_object($eqLogicZone)) {
              log::add(__PLUGIN_NAME_NETRO_ARROSAGE__, 'debug', 'synchronize:: ' . __('nouvelle zone', __FILE__));
              $eqLogicZone = new netroarrosage();
            }
            else{
              log::add(__PLUGIN_NAME_NETRO_ARROSAGE__, 'debug', 'synchronize:: ' . __('zone existante', __FILE__));          
            }

            // chargement des données de la zone depuis Netro
            log::add(__PLUGIN_NAME_NETRO_ARROSAGE__, 'debug', 'synchronize:: ' . __('zone netro', __FILE__) . ' : ['
              . $zone->id . ', '
              . $zone->name . ', '
              . $zone->smart . ', '
              . ']');

            // mise à jour de l'équipement et de ses commandes avec les données de Netro 
            $eqLogicZone->updateEqLogicZone($nc, $zone, $config["default_parent_object"]);

            $eqLogicZone->createCmd();
          }
        }

        // mise à jour des commandes info
        $eqLogicController->refreshController($nc);
      }
    }
    
    if (!empty($config["sensor_serial_n"])) {  // création ou mise à jour des capteurs
      $sensor_serials = explode(" ", $config["sensor_serial_n"]); // les numéros de série sont séparés par des espaces
      $sensor_index = 0;
      log::add(__PLUGIN_NAME_NETRO_ARROSAGE__, 'debug', 'synchronize:: sensor_serials : ' . var_export($sensor_serials, true));

      foreach ($sensor_serials as $sensor_serial) {
        // recherche d'un équipement possédant le numéro de série du capteur
        $eqLogicSensor = eqLogic::byLogicalId($sensor_serial, __PLUGIN_NAME_NETRO_ARROSAGE__);

        // si pas trouvé il faut créer un équipement vide
        if (!is_object($eqLogicSensor)) {
          log::add(__PLUGIN_NAME_NETRO_ARROSAGE__, 'debug', 'synchronize:: ' . __('nouveau capteur', __FILE__));
          $eqLogicSensor = new netroarrosage();
        }
        else {
          log::add(__PLUGIN_NAME_NETRO_ARROSAGE__, 'debug', 'synchronize:: ' . __('capteur existant', __FILE__));          
        }

        // chargement des données du capteur depuis Netro
        $ns = new netroSensor($sensor_serial);
        $ns->loadInfo();
        $ns->loadSensorData();
        log::add(__PLUGIN_NAME_NETRO_ARROSAGE__, 'debug', 'synchronize:: ' . __('capteur netro', __FILE__) . ' : ['
          . $ns->name . ', '
          . $ns->status . ', '
          . $ns->last_active_time . ', '
          . $ns->time . ', '
          . $ns->battery_level
          . ']');

        // mise à jour de l'équipement et de ses commandes avec les données de Netro, le suffixe à ajouter au nom du capteur est fourni (aucun suffixe si un seul capteur)
        $eqLogicSensor->updateEqLogicSensor($ns, $config["default_parent_object"], count($sensor_serials) > 1 ? ' ' . ++$sensor_index : '');

        $eqLogicSensor->createCmd();

        // mise à jour des commandes info
        $eqLogicSensor->refreshSensor($ns);
      }
    }
    else {
      log::add(__PLUGIN_NAME_NETRO_ARROSAGE__, 'warning', 'synchronize:: ' . __("aucun numéro de série n'est fourni pour le(s) capteur(s) de sol", __FILE__));
    }
  }

  public function getIconFile() {
    $type = $this->getConfiguration('type');
    if ($type == 'NetroSensor')
      $filename = __ROOT_NETRO_ARROSAGE__.'/core/config/devices/'.$type.'/'.'Whisperer.png';
    elseif ($type == 'NetroController') {
      // on part du principe que c'est un Pixie si la configuration fournit le niveau de batterie
      // et dans le cas contraire que c'est un Sprite (pas de distingo à ce stade entre le Spark et le Sprite)
      if (empty($this->getConfiguration('battery_level')))
        $filename = __ROOT_NETRO_ARROSAGE__.'/core/config/devices/'.$type.'/'.'Sprite.png';
      else
        $filename = __ROOT_NETRO_ARROSAGE__.'/core/config/devices/'.$type.'/'.'Pixie.png';
    }
    elseif ($type == 'NetroZone')
      $filename = __ROOT_NETRO_ARROSAGE__.'/core/config/devices/'.$type.'/'.'SolenoidValve.png';
    else
      $filename = __ROOT_NETRO_ARROSAGE__.'/core/config/devices/default.png';

    preg_match ("/\/core\/config\/devices\/.*/", $filename, $matches); //extrait le nom du fichier à partir de /core

    return (file_exists($filename) === true ? ('plugins/' . __PLUGIN_NAME_NETRO_ARROSAGE__ . $matches[0])
                                            : ('plugins/' . __PLUGIN_NAME_NETRO_ARROSAGE__ . '/core/config/devices/default.png'));
  }

  private function loadConfigFile() {
    $type = $this->getConfiguration('type');
    $filename = __ROOT_NETRO_ARROSAGE__.'/core/config/devices/'.$type.'/'.$type.'.json';
    if ( file_exists($filename) === false ) {
        throw new Exception(__("Impossible de trouver le fichier de configuration pour l'équipement de type", __FILE__) . ' ' . $this->getConfiguration('type'));
    }
    $content = file_get_contents($filename);
    if (!is_json($content)) {
        throw new Exception(__('Le fichier de configuration', __FILE__) . ' \'' . $filename . '\' ' . __('est corrompu', __FILE__));
    }

    $data = json_decode($content, true);
    if (!is_array($data) || !isset($data['configuration']) || !isset($data['commands'])) {
        throw new Exception(__('Le fichier de configuration', __FILE__) . '  \'' . $filename . '\' ' . __('est invalide', __FILE__));
    }

    return $data;
  }

  private function updateEqLogicController($netroController, $parentObjectId = '') {
    $this->setLogicalId($netroController->getKey());
    if (empty($this->getName())) // no reason to set name if set already
      $this->setName(self::getAvailableName($netroController->name, $parentObjectId, 'NC'));
    $this->setEqType_name(__PLUGIN_NAME_NETRO_ARROSAGE__);
    $this->setIsEnable(1);
    $this->setObject_id($parentObjectId);

    $this->setConfiguration('type', 'NetroController');

    $this->setConfiguration('battery_level', $netroController->battery_level);
    $this->setConfiguration('name', $netroController->name);
    $this->setConfiguration('version', $netroController->version);
    $this->setConfiguration('sw_version', $netroController->sw_version);
    $this->setConfiguration('nb_zones', $netroController->zone_number);
    $this->setConfiguration('token_limit', $netroController->token_limit);
    $this->setConfiguration('token_remaining', $netroController->token_remaining);
    $this->setConfiguration('token_time', $netroController->token_time);

    $config = $this->loadConfigFile();

    foreach ($config['configuration'] as $key => $value) {
        $this->setConfiguration($key, $value);
    }

    $this->save();

    log::add(__PLUGIN_NAME_NETRO_ARROSAGE__, 'info', __("mise à jour de l'équipement contrôleur", __FILE__) . ' "' . $this->name . '" ' . __('effectuée', __FILE__));
  }

  private function updateEqLogicZone($netroController, $netroZone, $parentObjectId = '') {
    $this->setLogicalId($netroController->getKey() . '_' . $netroZone->id);
    if (empty($this->getName())) // no reason to set name if set already
      $this->setName(self::getAvailableName(trim($netroZone->name) != '' ? $netroZone->name : $netroController->name . ' ' . $netroZone->id, $parentObjectId, 'NZ'));
    $this->setEqType_name(__PLUGIN_NAME_NETRO_ARROSAGE__);
    $this->setIsEnable(1);
    $this->setObject_id($parentObjectId);        

    $this->setConfiguration('type', 'NetroZone');

    $this->setConfiguration('version', $netroController->version);
    $this->setConfiguration('sw_version', $netroController->sw_version);
    $this->setConfiguration('id', $netroZone->id);
    $this->setConfiguration('name', $netroZone->name);
    $this->setConfiguration('smart', $netroZone->smart);
    $this->setConfiguration('controller', $netroController->name);

    $config = $this->loadConfigFile();

    foreach ($config['configuration'] as $key => $value) {
        $this->setConfiguration($key, $value);
    }

    $this->save();

    log::add(__PLUGIN_NAME_NETRO_ARROSAGE__, 'info', __("mise à jour de l'équipement zone", __FILE__) . ' "' . $this->name . '" ' . __('effectuée', __FILE__));
  }

  private function updateEqLogicSensor($netroSensor, $parentObjectId = '', $suffix = '') {
    $this->setLogicalId($netroSensor->getKey());
    if (empty($this->getName())) // no reason to set name if set already
      $this->setName(self::getAvailableName($netroSensor->name, $parentObjectId, 'NS'));
    $this->setEqType_name(__PLUGIN_NAME_NETRO_ARROSAGE__);
    $this->setIsEnable(1);
    $this->setObject_id($parentObjectId);

    $this->setConfiguration('type', 'NetroSensor');

    $this->setConfiguration('battery_level', $netroSensor->battery_level);
    $this->setConfiguration('name', $netroSensor->name);
    $this->setConfiguration('version', $netroSensor->version);
    $this->setConfiguration('sw_version', $netroSensor->sw_version);
    $this->setConfiguration('token_limit', $netroSensor->token_limit);
    $this->setConfiguration('token_remaining', $netroSensor->token_remaining);
    $this->setConfiguration('token_time', $netroSensor->token_time);

    $config = $this->loadConfigFile();

    foreach ($config['configuration'] as $key => $value) {
        $this->setConfiguration($key, $value);
    }

    $this->save();

    log::add(__PLUGIN_NAME_NETRO_ARROSAGE__, 'info', __("mise à jour de l'équipement capteur", __FILE__) . ' "' . $this->name . '" ' . __('effectuée', __FILE__));
  }

  private function createCmd() {
    $config = $this->loadConfigFile();

    $dashboard = array();
    $i = 0;

    foreach ($config['commands'] as $command) {
      // on ne créera pas de commande donnant le niveau de batterie si l'équipement (seulement les contrôleurs) ne possède pas cette information
      if ($command['logicalId'] == 'battery_level' && empty($this->getConfiguration('battery_level'))
                                                    && $this->getConfiguration('type') == 'NetroController') {
        log::add(__PLUGIN_NAME_NETRO_ARROSAGE__, 'debug', 'createCmd:: ' . __('commande non pertinente pour ce contrôleur :', __FILE__) . ' ' . $this->getName() . '.' . $command['logicalId']);                                                      
        $cmd = $this->getCmd(null, $command['logicalId']);
        if (is_object($cmd))
          $cmd->remove(); // on détruit cette commande si elle existe d'une précédente version
        continue;
      }
      // on ne recrée pas la commande si elle existe déjà
      $cmd = $this->getCmd(null, $command['logicalId']);
      if (!is_object($cmd)) {
        $cmd = new netroarrosageCmd();
      }
      $cmd->setOrder($i++);
      $cmd->setEqLogic_id($this->getId());
      utils::a2o($cmd, $command);
      $cmd->setName(__($cmd->getName(), __ROOT_NETRO_ARROSAGE__.'/core/config/devices/'
        . $this->getConfiguration('type') . '/' . $this->getConfiguration('type') . '.json'));
      $cmd->save();
      log::add(__PLUGIN_NAME_NETRO_ARROSAGE__, 'debug', 'createCmd:: ' . __('commande créée :', __FILE__) . ' ' . $this->getName() . '.' . $cmd->getName());

      if ( $command['isDashboard'] == true ) {
          $dashboard[] = $command['logicalId'];
      }
    }

    $this->setConfiguration('dashboard', $dashboard);
    $this->save();

    log::add(__PLUGIN_NAME_NETRO_ARROSAGE__, 'info', __("création ou mise à jour des commandes de l'équipement", __FILE__) . ' "' . $this->name . '" ' . __('effectuée', __FILE__));
  }

  public static function templateWidget() {
    $return = array('info' => array('binary' => array()));
    $return['info']['binary']['arrosoir'] = array(
      'template' => 'tmplimg',
      'test' => array(),
      'display' => array(
        '#icon#' => '<i class=\'icon nature-watering1\'></i>'
      ),
      'replace' => array(
        '#_time_widget_#' => '0',
        '#_img_light_on_#' => '<img src=\'plugins/' . __PLUGIN_NAME_NETRO_ARROSAGE__ . '/core/img/arrosage-on-tr.png\'>',
        '#_img_dark_on_#' => '<img src=\'plugins/' . __PLUGIN_NAME_NETRO_ARROSAGE__ . '/core/img/arrosage-on-tr-white.png\'>',
        '#_img_light_off_#' => '<img src=\'plugins/' . __PLUGIN_NAME_NETRO_ARROSAGE__ . '/core/img/arrosage-off-tr.png\'>',
        '#_img_dark_off_#' => '<img src=\'plugins/' . __PLUGIN_NAME_NETRO_ARROSAGE__ . '/core/img/arrosage-off-tr-white.png\'>',
        '#_desktop_width_#' => '',
        '#_mobile_width_#' => ''
      )
    );
    return $return;
  }

  public function refresh($schedulesFlag = true, $moisturesFlag = false) {
    log::add(__PLUGIN_NAME_NETRO_ARROSAGE__, 'debug', 'refresh::' . var_export($this, true));

    if ($this->getConfiguration('type') == 'NetroController') {
      $this->refreshController(null, $schedulesFlag, $moisturesFlag);
    }
    if ($this->getConfiguration('type') == 'NetroSensor') {
      $this->refreshSensor();
    }
    if ($this->getConfiguration('type') == 'NetroZone') {
      // c'est tout le controleur qu'il faut rafraichir
      $eqLogicController = eqLogic::byLogicalId(substr($this->getLogicalId(), 0, strpos($this->getLogicalId(), '_')), __PLUGIN_NAME_NETRO_ARROSAGE__);
      $eqLogicController->refreshController();
    }
  }

  public static function refreshDevices($controllerFlag = true, $schedulesFlag = true, $moisturesFlag = true, $sensorFlag = true) {
    // récupère tous les équipements et demande un refresh sur chacun d'eux, on peut remarquer que les objets zones ne sont
    // pas considérés dans la boucle dans la mesure ou ils sont pris en compte par le refresh sur l'objet "controleur"
    $netroEqLogics = self::byType(__PLUGIN_NAME_NETRO_ARROSAGE__);
    foreach (self::byType(__PLUGIN_NAME_NETRO_ARROSAGE__) as $netroEqLogic) {
      if ($controllerFlag && $netroEqLogic->getConfiguration('type') == 'NetroController') {
        $netroEqLogic->refreshController(null, $schedulesFlag, $moisturesFlag);
      }
      if ($sensorFlag && $netroEqLogic->getConfiguration('type') == 'NetroSensor') {
        $netroEqLogic->refreshSensor();
      }
    }
  }

  /*
  * Cette méthode à vocation a être déclenchée de manière asynchrone par une cron "one shot"
  * Voir plus bas la méthode executeAsync
  */
  public static function asynchronousRefresh ($_options) {
    log::add(__PLUGIN_NAME_NETRO_ARROSAGE__, 'debug', 'asynchronousRefresh:: options : ' . var_export($_options, true));

    if (isset($_options["type"])) {
      if ($_options['type'] == 'NetroController' || $_options['type'] == 'NetroZone') {
        self::refreshDevices(true, true, false, false); // rafraîchit le contrôleur et ses zones seulement (les "moistures" et les capteurs sont exclus)
      }
      if ($_options['type'] == 'NetroSensor') {
        self::refreshDevices(false, false, false, true); // rafraîchit les capteurs seulement (les contrôleurs et leurs informations sous jacentes sont exclus)
      }
    }
    else { // si le type n'est pas fourni on rafraîchit tout sans réfléchir
        self::refreshDevices(true, true, false, true); // rafraîchit le contrôleur, ses zones et tous les capteurs (les "moistures" sont exclus)      
    }
  }

  public function refreshController($controller = null, $schedulesFlag = true, $moisturesFlag = true) {
    // chargement des infos du controleur
    if (!is_object($controller)) { // l'objet netro n'est pas fourni il faut donc le construire
      $controller = new netroController($this->getLogicalId());
      $controller->loadInfo();
      if ($schedulesFlag)
        $controller->loadSchedules(self::getSchedulesStartDate(), self::getSchedulesEndDate());
      if ($moisturesFlag)
        $controller->loadMoistures();
    }

    // mise à jour de l'équipement controleur
    $this->checkAndUpdateCmd('name', $controller->name);
    $this->checkAndUpdateCmd('status', $controller->status);
    $this->checkAndUpdateCmd('is_watering', $controller->watering_flag);
    $this->checkAndUpdateCmd('is_enabled', $controller->active_flag);
    $this->checkAndUpdateCmd('token_remaining', $controller->token_remaining);
    $this->checkAndUpdateCmd('last_active_time', $controller->last_active_time);
    $this->checkAndUpdateCmd('active_zone_number', count($controller->active_zones));
    if (!empty($this->getConfiguration('battery_level')))
      $this->checkAndUpdateCmd('battery_level', $controller->battery_level);

    // mise à jour de l'équipement zone associé à chaque zone active du controleur
    foreach ($controller->active_zones as $zoneId => $zone) {
      $eqLogicZone = self::byLogicalId($controller->getKey() . '_' . $zoneId, __PLUGIN_NAME_NETRO_ARROSAGE__);

      $eqLogicZone->checkAndUpdateCmd('name', $zone->name);

      if ($schedulesFlag) { // les données suivantes ne peuvent être mise à jour que si les schedules ont été chargés
        $eqLogicZone->checkAndUpdateCmd('is_watering', $zone->isCurrentlyWatering());

        $lastRun = $zone->getLastRun(); // retourne false s'il n'y a pas d'historique
        $eqLogicZone->checkAndUpdateCmd('last_watering_status', is_array($lastRun) ? $lastRun['status'] : '');
        $eqLogicZone->checkAndUpdateCmd('last_date', is_array($lastRun) ? $lastRun['local_date'] : '');
        $eqLogicZone->checkAndUpdateCmd('last_start_time', is_array($lastRun) ? $lastRun['local_start_time'] : '');
        $eqLogicZone->checkAndUpdateCmd('last_end_time', is_array($lastRun) ? $lastRun['local_end_time'] : '');
        $eqLogicZone->checkAndUpdateCmd('event_source', is_array($lastRun) ? $lastRun['source'] : '');

        $nextRun = $zone->getNextRun(); // retourne false s'il n'y a pas d'historique        
        $eqLogicZone->checkAndUpdateCmd('next_watering_status', is_array($nextRun) ? $nextRun['status'] : '');
        $eqLogicZone->checkAndUpdateCmd('next_date', is_array($nextRun) ? $nextRun['local_date'] : '');
        $eqLogicZone->checkAndUpdateCmd('next_start_time', is_array($nextRun) ? $nextRun['local_start_time'] : '');
        $eqLogicZone->checkAndUpdateCmd('next_end_time', is_array($nextRun) ? $nextRun['local_end_time'] : '');
        $eqLogicZone->checkAndUpdateCmd('next_event_source', is_array($nextRun) ? $nextRun['source'] : '');

        $eqLogicZone->checkAndUpdateCmd('so_what', self::soWhat($lastRun, $nextRun));
      }
    }

    // mise à jour de la configuration
    $this->setConfiguration('token_limit', $controller->token_limit);
    $this->setConfiguration('token_remaining', $controller->token_remaining);
    $this->setConfiguration('token_time', $controller->token_time);
    $this->setConfiguration('battery_level', $controller->battery_level);
    $this->save();

    log::add(__PLUGIN_NAME_NETRO_ARROSAGE__, 'info', __('les informations du contrôleur', __FILE__) . ' "' . $this->name . '" ' . __('ont été mises à jour', __FILE__));
  }

  public function refreshSensor($sensor = null) {
    // chargement des propriétés et configuration du controleur
    if (!is_object($sensor)) { // l'objet netro n'est pas fourni il faut donc le construire
      $sensor = new netroSensor($this->getLogicalId());
      $sensor->loadSensorData();
    }

    // mise à jour de l'équipement sensor
    $this->checkAndUpdateCmd('moisture', $sensor->moisture);
    $this->checkAndUpdateCmd('temperature', $sensor->celsius);
    $this->checkAndUpdateCmd('sunlight', $sensor->sunlight);
    $this->checkAndUpdateCmd('battery_level', $sensor->battery_level);
    $this->checkAndUpdateCmd('time', $sensor->time);
    $this->checkAndUpdateCmd('local_time', $sensor->local_time);
    $this->checkAndUpdateCmd('local_date', $sensor->local_date);
    $this->checkAndUpdateCmd('token_remaining', $sensor->token_remaining);
    $this->checkAndUpdateCmd('last_active_time', $sensor->last_active_time);

    // mise à jour de la configuration
    $this->setConfiguration('token_limit', $sensor->token_limit);
    $this->setConfiguration('token_remaining', $sensor->token_remaining);
    $this->setConfiguration('token_time', $sensor->token_time);
    $this->setConfiguration('battery_level', $sensor->battery_level);
    $this->save();

    log::add(__PLUGIN_NAME_NETRO_ARROSAGE__, 'info', __('les informations du capteur', __FILE__) . ' "' . $this->name . '" ' . __('ont été mises à jour', __FILE__));
  }

  public static function soWhat ($lastRun, $nextRun) {
    $soWhatText = '';
    $arrosageEnCoursText = __("arrosage en cours depuis %02d:%02d, fin prévu à %02d:%02d pour une durée de %01.0f mn environ", __FILE__);
    $arrosageTermineText = __("arrosée %8$01.0f mn le %02d/%02d/%02d à %02d:%02d", __FILE__);
    $arrosageSuivantText = __(", prochain arrosage le %02d/%02d/%02d à %02d:%02d (%8$01.0f mn)", __FILE__); 

    // a t-on des informations sur le dernier arrosage, sinon on ne fait rien
    if (is_array($lastRun)) {
      $dtStartLastW = DateTime::createFromFormat(netroFunction::NETRO_DATETIME_FORMAT, $lastRun['local_date'] . ' ' . $lastRun['local_start_time']);
      $dtEndLastW = DateTime::createFromFormat(netroFunction::NETRO_DATETIME_FORMAT, $lastRun['local_date'] . ' ' . $lastRun['local_end_time']);      

      if ($lastRun['status'] == netroFunction::NETRO_SCHEDULE_EXECUTING) {
        $soWhatText = sprintf($arrosageEnCoursText,
                              getDate($dtStartLastW->getTimestamp())['hours'],
                              getDate($dtStartLastW->getTimestamp())['minutes'],
                              getDate($dtEndLastW->getTimestamp())['hours'],
                              getDate($dtEndLastW->getTimestamp())['minutes'],
                              ($dtEndLastW->getTimestamp() - $dtStartLastW->getTimestamp()) / 60);
      }

      if ($lastRun['status'] == netroFunction::NETRO_SCHEDULE_EXECUTED) {
        // le prochain arrosage est-il planifié
        if (is_array($nextRun)) {
          $dtStartNextW = DateTime::createFromFormat(netroFunction::NETRO_DATETIME_FORMAT, $nextRun['local_date'] . ' ' . $nextRun['local_start_time']);
          $dtEndNextW = DateTime::createFromFormat(netroFunction::NETRO_DATETIME_FORMAT, $nextRun['local_date'] . ' ' . $nextRun['local_end_time']);      
        }

        $soWhatText = sprintf($arrosageTermineText,
                              getDate($dtStartLastW->getTimestamp())['mday'],
                              getDate($dtStartLastW->getTimestamp())['mon'],
                              getDate($dtStartLastW->getTimestamp())['year'],
                              getDate($dtStartLastW->getTimestamp())['hours'],
                              getDate($dtStartLastW->getTimestamp())['minutes'],
                              getDate($dtEndLastW->getTimestamp())['hours'],
                              getDate($dtEndLastW->getTimestamp())['minutes'],
                              ($dtEndLastW->getTimestamp() - $dtStartLastW->getTimestamp()) / 60) .
                    (is_array($nextRun) && $nextRun['status'] != '' ?
                      sprintf($arrosageSuivantText,
                              getDate($dtStartNextW->getTimestamp())['mday'],
                              getDate($dtStartNextW->getTimestamp())['mon'],
                              getDate($dtStartNextW->getTimestamp())['year'],
                              getDate($dtStartNextW->getTimestamp())['hours'],
                              getDate($dtStartNextW->getTimestamp())['minutes'],
                              getDate($dtEndNextW->getTimestamp())['hours'],
                              getDate($dtEndNextW->getTimestamp())['minutes'],
                              ($dtEndNextW->getTimestamp() - $dtStartNextW->getTimestamp()) / 60)
                      : '');
      }
    }

    return $soWhatText;
  }

  private static function getSchedulesStartDate () {
    $startDate = '';

    $schedules_month_before = config::byKey('schedules_month_before', __PLUGIN_NAME_NETRO_ARROSAGE__);
    if (!empty($schedules_month_before) && is_numeric($schedules_month_before)) {
      $todayAndBefore = new DateTime();
      $todayAndBefore->sub(new DateInterval('P' . $schedules_month_before . 'M'));
      $startDate = $todayAndBefore->format(netroFunction::NETRO_DATE_FORMAT);
    }

    return $startDate;
  }
  
  private static function getSchedulesEndDate () {
    $endDate = '';

    $schedules_month_after = config::byKey('schedules_month_after', __PLUGIN_NAME_NETRO_ARROSAGE__);
    if (!empty($schedules_month_after) && is_numeric($schedules_month_after)) {
      $todayAndAfter = new DateTime();
      $todayAndAfter->add(new DateInterval('P' . $schedules_month_after . 'M'));
      $endDate = $todayAndAfter->format(netroFunction::NETRO_DATE_FORMAT);
    }

    return $endDate;
  }

  public static function getFactorsFromString ($factors) {
    // un exemple de la chaine de caractères attendues : "23:00,05:30,6;11:00,17:23,3"
    // cad un point virgule pour séparer les slots au premier niveau et une virgule pour séparer "from","to" et "sdf" au deuxième niveau
    $slots = explode(";", $factors);
    foreach($slots as &$slot) {
      $slotV = explode(",", $slot);
      $slotK = array("from", "to", "sdf");
      $slot = array_combine($slotK,$slotV); // combinaison clé, valeur
    }
    return $slots;
  }

  private static function getAvailableName($wishedName, $objectid, $qualifier='Netro') {
    // looking for all the equipments belonging to the object (including the disabled one)
    $existing_eqs = eqLogic::byObjectId($objectid, false);
  
    // building the list of names related to the object starting with the wished name
    $existing_names = array();
    foreach ($existing_eqs as $existing_eq) {
      $name = $existing_eq->getName();
      if (startsWith($name, $wishedName))
          $existing_names[] = $name;
    }
    
    // if the wished name is available, bingo !
    if (!in_array($wishedName, $existing_names))
      return $wishedName;
    // building another name since the wished one is not available
    for ($i = 0; $i < 250; $i++) {
      // proposed name is : <wished-name>-<qualifier>[-<incremented-index>]
      $newName = $wishedName . ' ' . $qualifier . ($i==0 ? '' : $i);
      if (!in_array($newName, $existing_names)) {
        $warning_string = __("Impossible de créer l'équipement '%s' dont le nom est déjà utilisé, le nom de substitution proposé est '%s'", __FILE__);
        log::add(__PLUGIN_NAME_NETRO_ARROSAGE__, 'warning', sprintf($warning_string, $wishedName, $newName));
        return $newName;
      }
    }
    $exception_string = __("Impossible de générer un nom unique (trop de nomn générés) pour remplacer '%s' qui est déjà utilisé", __FILE__);
    throw new Exception(sprintf($exception_string, $wishedName));
  }
  
  public static function getSlowdownFactor () {
    // on ne s'intéresse qu'au cas où l'utilisateur a renseigné le facteur de ralentissement dans la configuration
    if (config::byKey('slowdown_factor', __PLUGIN_NAME_NETRO_ARROSAGE__) != '') {
      $slots = self::getFactorsFromString(config::byKey('slowdown_factor', __PLUGIN_NAME_NETRO_ARROSAGE__));
      log::add(__PLUGIN_NAME_NETRO_ARROSAGE__, 'debug', 'getSlowdownFactor:: ' . __('tableau des facteurs de ralentissement', __FILE__) . ' : ' . var_export($slots, true));
    }
    else {
      return 1; // pas de ralentissement
    }

    if (is_array($slots)) {
      // convertir les heures du tableau en décimal
      foreach ($slots as &$slot) {
        $slot['from'] = explode(":", $slot['from'])[0] + explode(":", $slot['from'])[1] / 60;
        $slot['to'] = explode(":", $slot['to'])[0] + explode(":", $slot['to'])[1] / 60;
        if ($slot['from'] > $slot['to']) {
          $slot['from'] = $slot['from'] - 24;
        }
      }
      unset($slot); // casse la référence sur le dernier element

      // test l'heure courante en décimal dans sa forme positive et négative au regard des slots proposés
      $heureCouranteDecimalPositive = getdate()["hours"] + getDate()["minutes"] / 60;
      $heureCouranteDecimalNegative = $heureCouranteDecimalPositive - 24;
      foreach ($slots as $slot) {
        if (($heureCouranteDecimalPositive >= $slot['from'] && $heureCouranteDecimalPositive <= $slot['to']) ||
            ($heureCouranteDecimalNegative >= $slot['from'] && $heureCouranteDecimalNegative <= $slot['to'])) {
          log::add(__PLUGIN_NAME_NETRO_ARROSAGE__, 'debug', 'getSlowdownFactor:: ' . __('le facteur de ralentissement', __FILE__) . ' ' . $slot['sdf'] . ' ' . __('va être appliqué', __FILE__));
          return $slot['sdf'];
        }
      }
    }
    else {
      if (config::byKey('slowdown_factor', __PLUGIN_NAME_NETRO_ARROSAGE__) != '') {
        log::add(__PLUGIN_NAME_NETRO_ARROSAGE__, 'warning', 'getSlowdownFactor:: ' . __('le tableau des facteurs de ralentissement est incorrect, aucun ralentissement ne sera donc appliqué', __FILE__));
      }
      return 1; // pas de ralentissement
    }

    return 1;
  }

  public static function controllerSmartRefresh() {
    $tick  = config::byKey('controllerTick', __PLUGIN_NAME_NETRO_ARROSAGE__);
    $sdf = self::getSlowdownFactor();

    if (($tick % $sdf) == 0) { // c'est le moment de rafraichir
      self::refreshDevices(true, true, false, false); // rafraîchit le contrôleur et ses zones seulement (les "moistures" et les capteurs sont exclus)
      $tick = 1;
    }
    else { // faudra attendre une prochaine fois pour rafraichir (facteur de ralentissement actif à l'heure du traitement)
      log::add(__PLUGIN_NAME_NETRO_ARROSAGE__, 'debug', 'controllerSmartRefresh:: ' . __('aucune mise à jour due au facteur de ralentissement', __FILE__) . ' [' . $tick . ', ' . $sdf . ']');
      $tick++;
    }
    
    config::save('controllerTick', $tick, __PLUGIN_NAME_NETRO_ARROSAGE__);
  }

  public static function sensorSmartRefresh() {
    $tick  = config::byKey('sensorTick', __PLUGIN_NAME_NETRO_ARROSAGE__);
    $sdf = self::getSlowdownFactor();

    if (($tick % $sdf) == 0) { // c'est le moment de rafraichir
      self::refreshDevices(false, false, false, true); // rafraîchit les capteurs seulement (les contrôleurs et leurs informations sous jacentes sont exclus)
      $tick = 1;
    }
    else { // faudra attendre une prochaine fois pour rafraichir (facteur de ralentissement actif à l'heure du traitement)
      log::add(__PLUGIN_NAME_NETRO_ARROSAGE__, 'debug', 'sensorSmartRefresh:: ' . __('aucune mise à jour due au facteur de ralentissement', __FILE__) . ' [' . $tick . ', ' . $sdf . ']');
      $tick++;
    }
    
    config::save('sensorTick', $tick, __PLUGIN_NAME_NETRO_ARROSAGE__);
  }


  /*
  * Fonction exécutée automatiquement toutes les minutes par Jeedom
  */
  public static function cron() {
    // si le cron 5 est actif, je désactive ce cron pour éviter deux refresh simultanés
    if (config::byKey('functionality::cron5::enable', __PLUGIN_NAME_NETRO_ARROSAGE__, 1) == 1)
    {
      config::save('functionality::cron::enable', 0, __PLUGIN_NAME_NETRO_ARROSAGE__);
    }
    else {
      self::controllerSmartRefresh();
    }
  }

  /*
  * Fonction exécutée automatiquement toutes les 5 minutes par Jeedom
  */
  public static function cron5() {
    self::controllerSmartRefresh();
  }

  /*
  * Fonction exécutée automatiquement toutes les 10 minutes par Jeedom
  */
  public static function cron10() {
    self::sensorSmartRefresh();
  }

  /*
  * Fonction exécutée automatiquement toutes les 15 minutes par Jeedom
  public static function cron15() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les 30 minutes par Jeedom
  public static function cron30() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les heures par Jeedom
  public static function cronHourly() {}
  */

  /*
  * Fonction exécutée automatiquement tous les jours par Jeedom
  public static function cronDaily() {}
  */

  /*     * *********************Méthodes d'instance************************* */

  // Fonction exécutée automatiquement avant la création de l'équipement
  public function preInsert() {
  }

  // Fonction exécutée automatiquement après la création de l'équipement
  public function postInsert() {
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
  }

  // Fonction exécutée automatiquement avant la suppression de l'équipement
  public function preRemove() {
  }

  // Fonction exécutée automatiquement après la suppression de l'équipement
  public function postRemove() {
  }

  /*
  * Permet de crypter/décrypter automatiquement des champs de configuration des équipements
  * Exemple avec le champ "Mot de passe" (password)
  public function decrypt() {
    $this->setConfiguration('password', utils::decrypt($this->getConfiguration('password')));
  }
  public function encrypt() {
    $this->setConfiguration('password', utils::encrypt($this->getConfiguration('password')));
  }
  */

  /*
  * Permet de modifier l'affichage du widget (également utilisable par les commandes)
  public function toHtml($_version = 'dashboard') {}
  */

  /*
  * Permet de déclencher une action avant modification d'une variable de configuration du plugin
  * Exemple avec la variable "param3"
  public static function preConfig_param3( $value ) {
    // do some checks or modify on $value
    return $value;
  }
  */

  /*
  * Permet de déclencher une action après modification d'une variable de configuration du plugin
  * Exemple avec la variable "param3"
  public static function postConfig_param3($value) {
    // no return value
  }
  */

  /*
  * Permet de déclencher une méthode de manière asynchrone en produisant un cron "one shot"
  */
  public static function executeAsync(string $_method, $_option = null, $_date = 'now') {
    if (!method_exists(__CLASS__, $_method)) {
      throw new InvalidArgumentException(__('Method provided for executeAsync does not exist', __FILE__) . ': '. $_method);
    }

    $cron = new cron();
    $cron->setClass(__CLASS__);
    $cron->setFunction($_method);
    if (isset($_option)) {
      $cron->setOption($_option);
    }
    $cron->setOnce(1);
    $scheduleTime = strtotime($_date);
    $cron->setSchedule(cron::convertDateToCron($scheduleTime));
    $cron->save();
    if ($scheduleTime <= strtotime('now')) {
      $cron->run();
      log::add(__PLUGIN_NAME_NETRO_ARROSAGE__, 'debug', __('tâche', __FILE__) . ' ' . $_method . ' ' . __('exécuté maintenant', __FILE__));
    } else {
      log::add(__PLUGIN_NAME_NETRO_ARROSAGE__, 'debug', __('tâche', __FILE__) . ' ' . $_method . ' ' . __('programmé à', __FILE__) . ' ' . $_date);
    }
  }
}


class netroarrosageCmd extends cmd {
  /*     * *************************Attributs****************************** */

  /*
  public static $_widgetPossibility = array();
  */

  /*     * ***********************Methode static*************************** */


  /*     * *********************Methode d'instance************************* */

  /*
  * Permet d'empêcher la suppression des commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
  public function dontRemoveCmd() {
    return true;
  }
  */

  /*
  * Implémenter un refresh asynchrone par cron au lieu d'une attente bloquante pendant l'exécution de la commande
  * qui nécessite ce refresh après son application
  */
  const ASYNCHRONOUS_REFRESH = false;

  // Exécution d'une commande
  public function execute($_options = array()) {
    log::add(__PLUGIN_NAME_NETRO_ARROSAGE__, 'debug', 'execute:: options:' . var_export($_options, true));
    log::add(__PLUGIN_NAME_NETRO_ARROSAGE__, 'debug', 'execute:: this:' . var_export($this, true));
    log::add(__PLUGIN_NAME_NETRO_ARROSAGE__, 'info', __("exécution de l'action", __FILE__) . ' (' . $this->getEqLogic()->getName() . '[' . $this->getEqLogic()->getConfiguration('type') . '] /' . $this->getName() . ' #' . $this->getId() . ')');

    $noNeedToRefresh = false;

    // voyons les commandes de l'équipement "controller"
    if ($this->getEqLogic()->getConfiguration('type') == 'NetroController') {
      $nc = new netroController($this->getEqLogic()->getLogicalId());
      switch ($this->getLogicalId()) {
        case 'refresh':
        $this->getEqLogic()->refreshController();
        $noNeedToRefresh = true;
        break;
        case 'enable':
        $nc->enable();
        break;
        case 'disable':
        $nc->disable();
        break;
        case 'start_watering':
        $duration = array_key_exists("slider", $_options) ? (is_numeric($_options["slider"]) ? $_options["slider"] : 30) : 30; // 30 mn par défaut
        $nc->startWatering($duration);
        break;
        case 'stop_watering':
        $nc->stopWatering();
        break;
        case 'no_water':
        $numberOfDays = array_key_exists("slider", $_options) ? (is_numeric($_options["slider"]) ? $_options["slider"] : 2) : 2; // 2 jours par défaut
        $nc->noWater($numberOfDays);
        break;
      }
    }

    // voyons les commandes de l'équipement zone
    if ($this->getEqLogic()->getConfiguration('type') == 'NetroZone') {
      $nz = new netroZone(substr($this->getEqLogic()->getLogicalId(), 0, strpos($this->getEqLogic()->getLogicalId(), '_')),
                          $this->getEqLogic()->getConfiguration('id'),
                          $this->getEqLogic()->getConfiguration('name'),
                          $this->getEqLogic()->getConfiguration('smart'));
      switch ($this->getLogicalId()) {
        case 'start_watering':
        $duration = array_key_exists("slider", $_options) ? (is_numeric($_options["slider"]) ? $_options["slider"] : 30) : 30; // 30 mn par défaut
        $nz->startWatering($duration);
        break;
        case 'stop_watering':
        $nz->stopWatering();
        break;
      }
    }

    // voyons finalement les commandes de l'équipement sensor
    if ($this->getEqLogic()->getConfiguration('type') == 'NetroSensor') {
      switch ($this->getLogicalId()) {
        case 'refresh':
        $this->getEqLogic()->refreshSensor();
        $noNeedToRefresh = true;
        break;
      }
    }

    // refraichissement de l'équipement concerné sauf si ça n'est pas nécessaire (avec un délai avant le refresh si l'équipement le demande
    if (!$noNeedToRefresh) {
      if($this->getEqLogic()->getConfiguration('delayBeforeRefreshInfo') != '') {
        log::add(__PLUGIN_NAME_NETRO_ARROSAGE__, 'debug', 'execute:: ' . __('on attend', __FILE__) . ' '
          . $this->getEqLogic()->getConfiguration('delayBeforeRefreshInfo') . ' s ' . __("avant de mettre à jour l'équipement...", __FILE__));
        if (self::ASYNCHRONOUS_REFRESH) {
          netroarrosage::executeAsync('asynchronousRefresh',
            array('type' => $this->getEqLogic()->getConfiguration('type')),
            '+' . $this->getEqLogic()->getConfiguration('delayBeforeRefreshInfo') . ' seconds'
            );
          log::add(__PLUGIN_NAME_NETRO_ARROSAGE__, 'debug', 'execute:: ' . __('un refresh asynchrone dans', __FILE__) . ' ' .
            '+' . $this->getEqLogic()->getConfiguration('delayBeforeRefreshInfo') . ' ' . __('secondes', __FILE__) . ' ' . __('a été programmé', __FILE__));

        }
        else {
          usleep($this->getEqLogic()->getConfiguration('delayBeforeRefreshInfo') * 1000000);
          $this->getEqLogic()->refresh();
        }
      }
      else { // simple rafraichissement sans délai si pas nécessaire
        $this->getEqLogic()->refresh();
      }
    }
  }
}
