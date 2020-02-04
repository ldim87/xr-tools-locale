<?php
/**
 * @author  Dmitriy Lukin <lukin.d87@gmail.com>
 */


namespace XrTools;

/**
 * Localization utilities (time, language, geo)
 */
class Locale {

	private $config;

	function __construct(\XrTools\Config $config){
		$this->config = $config;
	}

	// messaging service
	private $mes; function mes(){
		return $this->mes ?: $this->mes = new \XrTools\Locale\Messages(
			$this->config->get('mes') ?? []
		);
	}

	// geoIp service
	private $geoip; function geoip(){
		return $this->geoip ?: $this->geoip = new \XrTools\Locale\GeoIP(
			$this->config->get('geoip') ?? []
		);
	}

	/**
	 * [getTimezoneOffset description]
	 * @param  [type] $remote_tz can be obtained via this->get_user_timezone()
	 * @param  [type] $origin_tz 
	 * @return [type]            
	 */
	function getTimezoneOffset($remote_tz, $origin_tz = null) {

	    if($origin_tz === null) {
	        if(!is_string($origin_tz = date_default_timezone_get())) {
	        	// A UTC timestamp was returned -- bail out!
	            return 0;
	        }
	    }

	    $origin_dtz = new \DateTimeZone($origin_tz);
	    $remote_dtz = new \DateTimeZone($remote_tz);
	    
	    $origin_dt = new \DateTime("now", $origin_dtz);
	    $remote_dt = new \DateTime("now", $remote_dtz);
	    
	    $offset = $origin_dtz->getOffset($origin_dt) - $remote_dtz->getOffset($remote_dt);
	    
	    return $offset;
	}


	/**
	 * [getBrowserLocale description]
	 * @return [type] [description]
	 */
	function getBrowserLocale(){
		// read browser
		return isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? 
			\Locale::acceptFromHttp($_SERVER['HTTP_ACCEPT_LANGUAGE']) : 
			'';
	}

	/**
	 * [getTimezone description]
	 * @param  string $country_code [description]
	 * @param  string $region_code  [description]
	 * @return [type]               [description]
	 */
	function getTimezone(string $country_code, string $region_code){
		// get via geoip service
		$user_tz = $this->geoip()->geoTimezone($country_code, $region_code);

		// or default via browser
		return $user_tz ?: date_default_timezone_get();
	}
}
