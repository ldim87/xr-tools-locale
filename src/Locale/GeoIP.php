<?php
/**
 * 
 */

namespace XrTools\Locale;

use \GeoIp2\Database\Reader;
use \GeoIp2\Exception\AddressNotFoundException;
use \MaxMind\Db\InvalidDatabaseException;

/**
 * 
 */
class GeoIP {
	
	// Default: $_SERVER['REMOTE_ADDR']
	protected $default_ip_address;

	protected $readers = [];

	protected $readers_paths = [];

	protected $ip_info_precision = 'city';

	protected $ip_info_cache = [];
	
	function __construct(array $opt = []){
		
		// paths to mmdb files, e.g. ['city' => '/path/to/file.mmdb']
		if(isset($opt['readers_paths']) && is_array($opt['readers_paths'])){
			$this->readers_paths = $opt['readers_paths'];
		}

		// city / country
		if(isset($opt['ip_info_precision']) && isset($this->readers_paths[$opt['ip_info_precision']])){
			$this->ip_info_precision = $opt['ip_info_precision'];
		}

		// set default ip
		$this->default_ip_address = $opt['default_ip_address'] ?? $_SERVER['REMOTE_ADDR'];
	}

	protected function isValidResult($record){

		$precision_city_ok = $this->ip_info_precision != 'city' || ( isset($record->city) && isset($record->mostSpecificSubdivision) );

		return isset($record->location) && isset($record->country) && $precision_city_ok;
	}

	protected function getReader(string $type){
		
		$type = $type ?? $this->default_reader;

		if(!isset($this->readers[$type])){

			if(!isset($this->readers_paths[$type])){
				throw new \Exception("Path to current reader ({$type}) is not set!");
			}

			$this->readers[$type] = new Reader($this->readers_paths[$type]);
		}

		return $this->readers[$type];
	}

	protected function getCachedResult($ip){
		return $this->ip_info_cache[$this->ip_info_precision][$ip] ?? null;
	}

	protected function setCachedResult($ip, array $result){
		$this->ip_info_cache[$this->ip_info_precision][$ip] = $result;
	}

	function validateIp(string $ip){
		return filter_var($ip, FILTER_VALIDATE_IP);
	}

	function getIpInfo(string $ip = null){

		$ip = $ip ?? $this->default_ip_address;

		if(!$this->validateIp($ip)){
			return ['status' => false, 'error' => 'Invalid IP address!'];
		}

		// return cached result if exists
		if ($result = $this->getCachedResult($ip)){
			return $result;
		}

		$result = ['status' => false, 'error' => ''];

		try {

			// default search
			$record = $this->ip_info_precision == 'city' ? $this->getReader('city')->city($ip) : $this->getReader('country')->country($ip);

			if($this->isValidResult($record)){
				
				$result['status'] = true;

				$result['record'] = $record;

				// helper keys
				$result['country_code'] = $record->country->isoCode;
				$result['region'] = $record->mostSpecificSubdivision->isoCode;
				$result['timezone'] = $record->location->timeZone;

				if($this->ip_info_precision == 'city'){
					$result['city'] = $record->city->name;
				}
			}
			else {
				$result = ['status' => false, 'error' => 'Invalid result!'];
			}
		}
		catch (InvalidDatabaseException $e) {
			$result = ['status' => false, 'error' => 'Invalid database error! ' . $e->getMessage()];
		}
		catch (AddressNotFoundException $e) {
			$result = ['status' => false, 'error' => 'Address not found! ' . $e->getMessage()];
		}
		catch (\Exception $e) {
			$result = ['status' => false, 'error' => $e->getMessage()];
		}

		// cache result
		$this->setCachedResult($ip, $result);

		return $result;
	}
}
