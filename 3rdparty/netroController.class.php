<?php
namespace NetroPublicAPI;

/* This file is part of the implementation of the NPA (Netro Public API) PHP wrapper 
 * 
 * The NPA (Netro Public API) allows to control our Netro devices through a public API, which also provides infinite flexibility in integrating with other services
 * 
 */

/* * ***************************Includes********************************* */
require 'vendor/autoload.php';
require_once dirname(__FILE__) . '/netroFunction.class.php';

// besoin d'une portée global pour une variable très locale dans le principe pour être utilisée dans les callbacks
// de filtre et tri de tableau pour la gestion des schedules
$zoneIndex;

class netroSensor {
    const DEBUG_MODE = false;

    private $_key;
    private $_sensor_data;
    public $time;
    public $local_date;
    public $local_time;
    public $moisture;
    public $sunlight;
    public $celsius;
    public $fahrenheit;
    public $battery_level;
    public $name;
    public $status;
    public $version;
    public $sw_version;
    public $active_flag;
    public $last_active_time;
    private $_meta = [];
    private $_device = [];

    function __construct($key) {
        $this->_key = $key;
    } 

    public function getKey() {
        return $this->_key;
    }
    public function loadInfo() {
        $info = NetroFunction::getInfo($this->_key);
        $this->_meta = $info["meta"];
        $this->_device = $info["data"]["sensor"];

        // mise à jour des propriétés du sensor
        $this->name = $this->_device["name"];
        $this->status = $this->_device["status"];
        $this->version = $this->_device["version"];
        $this->sw_version = $this->_device["sw_version"];

        $this->last_active_time = $this->_device["last_active"];
        $this->active_flag = ($this->status == NetroFunction::NETRO_STATUS_ONLINE) ? true : false;

        if (self::DEBUG_MODE) {
            // var_dump($this);
        }
    } 

    public function loadSensorData () {
        $this->_sensor_data = netroFunction::getSensorData($this->_key, date('Y-m-d'), date('Y-m-d'))["data"]["sensor_data"];

        // mise à jour des propriétés du controleur
        $this->time = $this->_sensor_data[0]["time"];
        $this->local_date = $this->_sensor_data[0]["local_date"];
        $this->local_time = $this->_sensor_data[0]["local_time"];
        $this->moisture = $this->_sensor_data[0]["moisture"];
        $this->sunlight = $this->_sensor_data[0]["sunlight"];
        $this->celsius = $this->_sensor_data[0]["celsius"];
        $this->fahrenheit = $this->_sensor_data[0]["fahrenheit"];
        $this->battery_level = $this->_sensor_data[0]["battery_level"];

        if (self::DEBUG_MODE) {
            //var_dump($this);
        }
    }
}

class netroZone {
    const DEBUG_MODE = true;

    private $_key;
    public $id;
    public $name;
    public $smart;
    public $moistures = []; // l'ensembles des moistures obtenu au moment du loadMoustures depuis l'instance netroController
    public $past_schedules = [];
    public $coming_schedules = [];

    function __construct($key, $id, $name, $smart) {
        $this->_key = $key;
        $this->id = $id;
        $this->name = $name;
        $this->smart = $smart;        
    } 

    public function startWatering($duration, $delay = 0, $startTime = '') {
        NetroFunction::water($this->_key, $duration, array("$this->id"), $delay, $startTime);

    }
    public function stopWatering() {
        NetroFunction::stopWater($this->_key);
    }

    public function getMoisture() {
        if (empty($this->moistures) ||
            (!empty($this->moistures) &&
                ($this->moistures[array_key_first($this->moistures)]["date"] < (new \DateTime('-1 day'))->format ('Y-m-d')))) {
            $moistures = NetroFunction::getMoistures($this->_key, array("$this->id"), (new \DateTime('-1 day'))
                ->format ('Y-m-d'), date('Y-m-d'))["data"]["moistures"];
            if (self::DEBUG_MODE) echo "netroZone::getMoisture -> rechargement requis";
        }
        return $this->moistures[array_key_first($this->moistures)];
    }

    public function getLastRun() {
        if (!empty($this->past_schedules)) {
            return  $this->past_schedules[array_key_first($this->past_schedules)];
        }
        else return false;
    }

    public function getNextRun() {
        if (!empty($this->coming_schedules)) {
            return $this->coming_schedules[array_key_first($this->coming_schedules)];
        }
        else return false;
    }

    public function isCurrentlyWatering () {
        if (!empty($this->past_schedules)) {
            return $this->getLastRun()["status"] == NetroFunction::NETRO_SCHEDULE_EXECUTING;
        }
        else return false;
    }
}

class netroController {
    const DEBUG_MODE = false;

    private $_key;

    private $_meta = [];
    private $_device = [];
    private $_moistures = [];    
    private $_schedules = [];    

    // netro meta data
    public $token_limit;
    public $token_remaining;
    public $token_time;

    public $name;
    public $status;
    public $version;
    public $sw_version;
    public $watering_flag;
    public $active_flag;
    public $last_active_time;
    public $zone_number;
    public $active_zones = []; // tableau d'instances de NetroZone

    function __construct($key) {
        $this->_key = $key;
    } 

    public function getKey() {
        return $this->_key;
    }

    public function getDevice() {
        return $this->_device;
    }

    public function getMeta() {
        return $this->_meta;
    }

    public function getMoistures() {
        return $this->_moistures;
    }

    public function getSchedules() {
        return $this->_schedules;
    }

    public function setKey($key) {
        $this->_key=$key;
    }

    public function enable() {
        NetroFunction::setStatus($this->_key, NetroFunction::NETRO_STATUS_ENABLE);
    }

    public function disable() {
        NetroFunction::setStatus($this->_key, NetroFunction::NETRO_STATUS_DISABLE);
    }

    public function startWatering($duration, $zoneIds = null, $delay = 0, $startTime = '') {
        NetroFunction::water($this->_key, $duration, $zoneIds, $delay, $startTime);
    }

    public function stopWatering() {
        NetroFunction::stopWater($this->_key);
    }

    public function noWater($days = null) {
        NetroFunction::noWater($this->_key, $days);
    }

    public function setMoisture($moisture, $zoneIds) {
        NetroFunction::setMoisture($this->_key, $moisture, $zoneIds);
    }

    public function loadMoistures($zoneIds = array(), $startDate = '', $endDate = '') {
        $this->_moistures = netroFunction::getMoistures($this->_key, $zoneIds, $startDate, $endDate)["data"]["moistures"];
        
        global $zoneIndex;

        foreach ($this->active_zones as $zoneId => $zone) {
            if (in_array($zoneId, $zoneIds) || empty($zoneIds)) {
                $zoneIndex = $zoneId;
                $zone->moistures = array_filter($this->_moistures, function($schedItem) {
                    global $zoneIndex;
                    return $schedItem["zone"] == $zoneIndex;
                });
            }
        }

        if (self::DEBUG_MODE) {
            echo "loadMoistures::active_zones \n";
            var_dump($this->active_zones);
        }
    }

    public function loadSchedules ($startDate = '', $endDate = '') {
        $this->_schedules = NetroFunction::getSchedules($this->_key, null, $startDate, $endDate)["data"]["schedules"];

        // sans paramètre pur la requete on récupère les schedules sur le dernier mois et sur le prochain mois
        // le but est de retrouver pour chaque active zone le statut EXECUTED/EXECUTING le plus récent (ça vient de se passer) et le statut VALID
        // dont la date est la plus petite (c'est à dire prochaine). Il faut donc filtrer sur la zone et sur le statut et trier sur la date.
        // il est possible qu'il n'y ait pas de tuples de chaque statut (dernière exécution a plus d'une mois, prochaine exécution après un mois)
        // si ça pose un proiblème on peut tout à fait extraire plusieurs mois glissant par exemple +3 mois et -3 mois par rapport à aujourd'hui
        if (self::DEBUG_MODE) {
            // var_dump($this->_schedules["data"]["schedules"]);
        }

        global $zoneIndex;

        foreach ($this->active_zones as $zoneId => $zone) {
            $zoneIndex = $zoneId; // remonter cette valeur en portée globale pour une utilisation dans les fonctions callback
            
            // initialisation du past schedule de chacune des zones actives
            {
                // filtrage du tableau sur le status EXECUTED/EXECUTING et la zone
                $past_schedules = array_filter($this->_schedules, function($schedItem) {
                    global $zoneIndex;
                    return ($schedItem["status"] == NetroFunction::NETRO_SCHEDULE_EXECUTED || $schedItem["status"] == NetroFunction::NETRO_SCHEDULE_EXECUTING) && $schedItem["zone"] == $zoneIndex;
                });

                // tri du tableau obtenu par le filtre par date décroissante pour avoir l'exécution la plus récente en premier
                usort($past_schedules, function($schedItem1, $schedItem2) {
                    if ($schedItem1["start_time"] == $schedItem2["start_time"]) {
                        return 0;
                    }
                    return $schedItem1["start_time"] < $schedItem2["start_time"] ? 1 : -1;
                });

                $this->active_zones[$zoneId]->past_schedules = $past_schedules;
            }

            // initialisation du coming schedule de chacune des zones actives
            {
                // filtrage du tableau sur le status VALID et la zone, en ne conservant que les date/heure à venir (et non celles qui sont déjà passées)
                $coming_schedules = array_filter($this->_schedules, function($schedItem) {
                    global $zoneIndex;
                    return $schedItem["status"] == NetroFunction::NETRO_SCHEDULE_VALID && $schedItem["zone"] == $zoneIndex && $schedItem["start_time"] > gmdate("Y-m-d\\TH:i:s");
                });

                // tri du tableau obtenu par le filtre par date croissante pour avoir la prochaine date la plus récente en premier
                usort($coming_schedules, function($schedItem1, $schedItem2) {
                    if ($schedItem1["start_time"] == $schedItem2["start_time"]) {
                        return 0;
                    }
                    return $schedItem1["start_time"] < $schedItem2["start_time"] ? -1 : 1;
                });

                $this->active_zones[$zoneId]->coming_schedules = $coming_schedules;
            }
        }

        if (self::DEBUG_MODE) {
            // var_dump($this->active_zones);
        }
    }


    public function loadInfo () {
        $info = NetroFunction::getInfo($this->_key);
        $this->_meta = $info["meta"];
        $this->_device = $info["data"]["device"];

        // mise à jour des métadonnées
        $this->token_time = $this->_meta["time"];
        $this->token_limit = $this->_meta["token_limit"];
        $this->token_remaining = $this->_meta["token_remaining"];

        // mise à jour des propriétés du controleur
        $this->name = $this->_device["name"];
        $this->status = $this->_device["status"];
        $this->version = $this->_device["version"];
        $this->sw_version = $this->_device["sw_version"];

        $this->last_active_time = $this->_device["last_active"];
        $this->zone_number = $this->_device["zone_num"];
        $this->watering_flag = ($this->status == NetroFunction::NETRO_STATUS_WATERING) ? true : false;
        $this->active_flag = ($this->status == NetroFunction::NETRO_STATUS_ONLINE) ? true : false;

        foreach ($this->_device["zones"] as $clef => $zone) {
            if ($zone["enabled"]) {
                if (!array_key_exists($zone["ith"], $this->active_zones)) {
                    $this->active_zones[$zone["ith"]] = new netroZone ($this->_key, $zone["ith"], $zone["name"], $zone["smart"]);
                }
            }
        }

        if (self::DEBUG_MODE) {
            // var_dump($this);
        }
    } 
}
