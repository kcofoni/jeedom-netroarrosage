<?php
namespace NetroPublicAPI;
use \GuzzleHttp as GuzzleHttp;

/* This file is a PHP implementation of the Netro Public API
 *
 * The NPA (Netro Public API) allows to control our Netro devices through a public API, which also provides infinite flexibility in integrating with other services
 *
 */


/* * ***************************Includes********************************* */
require 'vendor/autoload.php';


class netroException extends \Exception
{
  public function __construct($netroFunctionResult) {
    parent::__construct($netroFunctionResult["errors"][0]["message"], $netroFunctionResult["errors"][0]["code"]);
  }

  public function __toString() {
    return __CLASS__ . "line " . __LINE__  . " -- error code #{$this->code} -> {$this->message}\n";
  }
}

class netroFunction {
    private static $_netroBaseURL = 'https://api.netrohome.com/npa/v1/';
    const NETRO_GET_SCHEDULES = 'schedules.json';
    const NETRO_GET_INFO = 'info.json';
    const NETRO_GET_MOISTURES = 'moistures.json';
    const NETRO_GET_SENSORDATA = 'sensor_data.json';
    const NETRO_POST_REPORTWEATHER = 'report_weather.json';
    const NETRO_POST_MOISTURE = 'set_moisture.json';
    const NETRO_POST_WATER = 'water.json';
    const NETRO_POST_STOPWATER = 'stop_water.json';
    const NETRO_POST_NOWATER = 'no_water.json';
    const NETRO_POST_STATUS = 'set_status.json';
    const NETRO_GET_EVENTS = 'events.json';

    const NETRO_STATUS_ENABLE = 1;
    const NETRO_STATUS_DISABLE = 0;
    const NETRO_STATUS_STANDBY = "STANDBY";
    const NETRO_STATUS_SETUP = "SETUP";
    const NETRO_STATUS_ONLINE = "ONLINE";
    const NETRO_STATUS_WATERING = "WATERING";
    const NETRO_STATUS_OFFLINE = "OFFLINE";
    const NETRO_STATUS_SLEEPING = "SLEEPING";
    const NETRO_STATUS_POWEROFF = "POWEROFF";
    const NETRO_SCHEDULE_EXECUTED = "EXECUTED";
    const NETRO_SCHEDULE_EXECUTING = "EXECUTING";
    const NETRO_SCHEDULE_VALID = "VALID";
    const NETRO_DATETIME_FORMAT = "Y-m-d H:i:s";
    const NETRO_DATE_FORMAT = "Y-m-d";
    const NETRO_ERROR = "ERROR";
    const NETRO_OK = "OK";    
    const NETRO_EVENT_DEVICEOFFLINE = 1;
    const NETRO_EVENT_DEVICEONLINE = 2;
    const NETRO_EVENT_SCHEDULESTART = 3;
    const NETRO_EVENT_SCHEDULEEND = 4;

    const DEBUG_MODE = false;

    public static function setNetroBaseURL ($netroBaseURL) {
        self::$_netroBaseURL = $netroBaseURL;
    }
    public static function getSchedules ($key, $zoneIds = null, $startDate = '', $endDate = '') {
        $client = new GuzzleHttp\Client(['base_uri' => self::$_netroBaseURL]);
        $params['key'] = $key;
        if ($zoneIds !== null) {
            $params['zones'] = '[' . implode(",", $zoneIds) . ']';
        }
        if ($startDate !== '') {
            $params['start_date'] = $startDate;
        }
        if ($endDate !== '') {
            $params['end_date'] = $endDate;
        }

        if (self::DEBUG_MODE) {
            // var_dump($params);
        }

        $response = $client->request('GET', self::NETRO_GET_SCHEDULES, [
            'query' => $params, 'http_errors' => false
        ])->getBody()->getContents();

        $result = json_decode($response, true);
        if ($result["status"] == self::NETRO_ERROR) {
            throw new netroException($result);
        }

        return json_decode($response, true);
    }
    
    public static function getInfo ($key) {
        $client = new GuzzleHttp\Client(['base_uri' => self::$_netroBaseURL]);
        $response = $client->request('GET', self::NETRO_GET_INFO, [
            'query' => ['key' => $key], 'http_errors' => false
        ])->getBody()->getContents();

        if (self::DEBUG_MODE) {
            // var_dump($response);
        }

        $result = json_decode($response, true);
        if ($result["status"] == self::NETRO_ERROR) {
            throw new netroException($result);
        }

        return json_decode($response, true);
    }

    public static function getMoistures($key, $zoneIds = null, $startDate = '', $endDate = '') {
        $client = new GuzzleHttp\Client(['base_uri' => self::$_netroBaseURL]);
        $params['key'] = $key;
        if ($zoneIds !== null) {
            $params['zones'] = '[' . implode(",", $zoneIds) . ']';
        }
        if ($startDate !== '') {
            $params['start_date'] = $startDate;
        }
        if ($endDate !== '') {
            $params['end_date'] = $endDate;
        }

        if (self::DEBUG_MODE) {
            // var_dump($params);
        }
        $response = $client->request('GET', self::NETRO_GET_MOISTURES, [
            'query' => $params, 'http_errors' => false
        ])->getBody()->getContents();

        $result = json_decode($response, true);
        if ($result["status"] == self::NETRO_ERROR) {
            throw new netroException($result);
        }

        return json_decode($response, true);
    }

    public static function reportWeather($key, $date, $condition, $rain, $rain_prob, $temp, $t_min, $t_max, $t_dew, $wind_speed, $humidity, $pressure) {
        $client = new GuzzleHttp\Client(['base_uri' => self::$_netroBaseURL]);
        $params['key'] = $key;
        $params['date'] = $date;
        if ($condition !== '') {
            $params['condition'] = $condition;
        }
        if ($rain !== '') {
            $params['rain'] = $rain;
        }

        if ($rain_prob !== '') {
            $params['rain_prob'] = $rain_prob;
        }

        if ($temp !== '') {
            $params['temp'] = $temp;
        }

        if ($t_min !== '') {
            $params['t_min'] = $t_min;
        }

        if ($t_max !== '') {
            $params['t_max'] = $t_max;
        }

        if ($t_dew !== '') {
            $params['t_dew'] = $t_dew;
        }

        if ($wind_speed !== '') {
            $params['wind_speed'] = $wind_speed;
        }

        if ($humidity !== '') {
            $params['humidity'] = $humidity;
        }

        if ($pressure !== '') {
            $params['pressure'] = $pressure;
        }
        $response = $client->request('POST', self::NETRO_POST_REPORTWEATHER,
            ['form_params' => $params, 'http_errors' => false])->getBody()->getContents();

        $result = json_decode($response, true);
        if ($result["status"] == self::NETRO_ERROR) {
            throw new netroException($result);
        }

        return json_decode($response, true);        
    }

    public static function setMoisture($key, $moisture, $zoneIds) {
        $client = new GuzzleHttp\Client(['base_uri' => self::$_netroBaseURL]);
        $params['key'] = $key;
        $params['moisture'] = $moisture;                
        if ($zoneIds !== null) {
            $params['zones'] = '[' . implode(",", $zoneIds) . ']';
        }
        $response = $client->request('POST', self::NETRO_POST_MOISTURE,
            ['form_params' => $params, 'http_errors' => false])->getBody()->getContents();

        $result = json_decode($response, true);
        if ($result["status"] == self::NETRO_ERROR) {
            throw new netroException($result);
        }

        return json_decode($response, true);                
    }

    public static function water($key, $duration, $zoneIds = null, $delay = 0, $startTime = '') {
        $client = new GuzzleHttp\Client(['base_uri' => self::$_netroBaseURL]);
        $params['key'] = $key;
        $params['duration'] = round($duration);
        if ($zoneIds !== null) {
            $params['zones'] = '[' . implode(",", $zoneIds) . ']';
        }
        if ($delay > 0) {
            $params['delay'] = $delay;
        }
        if ($startTime !== '') {
            // convert local time to UTC
            $params['start_time'] = gmdate('Y.m.d H:i', strtotime($startTime));
        }

        $response = $client->request('POST', self::NETRO_POST_WATER, [
            'form_params' => $params,
            'debug' => false,
            'http_errors' => false // true : pas d'exception en cas d'erreur
        ])->getBody()->getContents();

        $result = json_decode($response, true);
        if ($result["status"] == self::NETRO_ERROR) {
            throw new netroException($result);
        }

        return json_decode($response, true);                
    }

    public static function stopWater($key) {
        $client = new GuzzleHttp\Client(['base_uri' => self::$_netroBaseURL]);
        $response = $client->request('POST', self::NETRO_POST_STOPWATER,
            ['form_params' => ['key' => $key], 'http_errors' => false
        ])->getBody()->getContents();

        $result = json_decode($response, true);
        if ($result["status"] == self::NETRO_ERROR) {
            throw new netroException($result);
        }

        return json_decode($response, true);                        
    }

    public static function noWater($key, $days = null) {
        $client = new GuzzleHttp\Client(['base_uri' => self::$_netroBaseURL]);
        $params['key'] = $key;
        if (!is_null($days)) {
            $params['days'] = round($days);            
        }
        $response = $client->request('POST', self::NETRO_POST_NOWATER,
            ['form_params' => $params, 'http_errors' => false
        ])->getBody()->getContents();
        
        $result = json_decode($response, true);
        if ($result["status"] == self::NETRO_ERROR) {
            throw new netroException($result);
        }

        return json_decode($response, true);
    }

    public static function setStatus($key, $status) {
        $client = new GuzzleHttp\Client(['base_uri' => self::$_netroBaseURL]);
        $response = $client->request('POST', self::NETRO_POST_STATUS, [
            'form_params' => [
                'key' => $key,
                'status' => $status
            ],
            'http_errors' => false
        ])->getBody()->getContents();

        $result = json_decode($response, true);
        if ($result["status"] == self::NETRO_ERROR) {
            throw new netroException($result);
        }

        return json_decode($response, true);        
    }

    public static function getSensorData ($key, $startDate ='', $endDate = '') {
        $client = new GuzzleHttp\Client(['base_uri' => self::$_netroBaseURL]);
        $params['key'] = $key;        
        if ($startDate !== '') {
            $params['start_date'] = $startDate;
        }
        if ($endDate !== '') {
            $params['end_date'] = $endDate;
        }


        $response = $client->request('GET', self::NETRO_GET_SENSORDATA, [
            'query' => $params,
            'http_errors' => false
        ])->getBody()->getContents();

        $result = json_decode($response, true);
        if ($result["status"] == self::NETRO_ERROR) {
            throw new netroException($result);
        }

        return json_decode($response, true);
    }

    public static function getEvents ($key, $typeOfEvent = 0, $startDate = '', $endDate = '') {
        $client = new GuzzleHttp\Client(['base_uri' => self::$_netroBaseURL]);
        $params['key'] = $key;
        if ($typeOfEvent > 0) {
            $params['event'] = $typeOfEvent;
        }        
        if ($startDate !== '') {
            $params['start_date'] = $startDate;
        }
        if ($endDate !== '') {
            $params['end_date'] = $endDate;
        }

        $response = $client->request('GET', self::NETRO_GET_EVENTS, [
            'query' => $params,
            'http_errors' => false
        ])->getBody()->getContents();

        $result = json_decode($response, true);
        if ($result["status"] == self::NETRO_ERROR) {
            throw new netroException($result);
        }

        return json_decode($response, true);    	
    }
}