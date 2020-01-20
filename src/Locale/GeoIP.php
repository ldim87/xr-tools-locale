<?php
/**
 * 
 */

namespace XrTools\Locale;

/**
 * 
 */
class GeoIP {
	
	// extend geoip.custom_directory
	private $geoip_custom_dirs = [];

	// try before _SERVER['REMOTE_ADDR']
	private $default_ip_address;
	
	function __construct(array $opt = []){
		// additional geoip dirs to scan
		if(isset($opt['geoip_custom_dirs'])){
			$this->setCustomDirs($opt['geoip_custom_dirs']);
		}

		// set default ip
		if(isset($opt['default_ip_address'])){
			$this->default_ip_address = $opt['default_ip_address'];
		}
	}

	private function isValidResult($result){
		return is_array($result) && isset($result['country_code']) && $result['country_code'] != '--';
	}

	function setCustomDirs(array $dirs){
		$this->geoip_custom_dirs = $dirs;
	}

	function validateIp(string $ip){
		return filter_var($ip, FILTER_VALIDATE_IP);
	}

	function getIpInfo(string $ip = null){

		$ip = $ip ?? $this->default_ip_address ?? $_SERVER['REMOTE_ADDR'];

		if(!$this->validateIp($ip)){
			return ['status' => false, 'error' => 1];
		}

		// default search
		$geo_info = geoip_record_by_name($ip);

		if($this->isValidResult($geo_info)){
			$geo_info['found_in'] = 'geoip.custom_directory';
			$geo_info['status'] = true;

			return $geo_info;
		}
		
		// if not found, let's search custom directories
		foreach ($this->geoip_custom_dirs as $custom_dir) {
			// switch directory
			geoip_setup_custom_directory($custom_dir);

			// search
			$geo_info = geoip_record_by_name($ip);

			// check
			if($this->isValidResult($geo_info)){

				$geo_info['found_in'] = $custom_dir;
				$geo_info['status'] = true;

				return $geo_info;
			}
		}

		// nothing found
		return ['status' => false, 'error' => 2];
	}

	function geoTimezone(string $country_code, string $region_code){
		return geoip_time_zone_by_country_and_region(
			$country_code, $region_code
		);
	}
}
