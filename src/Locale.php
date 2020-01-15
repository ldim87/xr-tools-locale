<?php
/**
 * @author  Dmitriy Lukin <lukin.d87@gmail.com>
 */


namespace XrTools;

/**
 * :TODO:REFACTOR: Localization utilities (time, language, geo)
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

	
}
