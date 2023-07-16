<?php
/**
 * @author Dmitriy Lukin <lukin.d87@gmail.com>
 */

namespace XrTools\Locale;

/**
 * Localized Strings (messages)
 */
class Messages
{
	
	protected $mes = [];
	
	protected $langs = [];

	protected $default_lang = 'en';
	
	protected $lang;
	
	protected $default_mes_folder = '';

	/**
	 * Runtime cache for loaded messages
	 * @var array
	 */
	protected $keys_loaded = [];
	
	protected $debug = false;

	protected $debug_messages = [];

	function __construct($sys = []){

		// default language
		if(!empty($sys['default_lang'])){
			$this->default_lang = (string) $sys['default_lang'];
		}

		// allowed languages
		$this->langs = !empty($sys['lang_enabled']) ? explode(',', $sys['lang_enabled']) : [ $this->default_lang ];
		

		if(isset($sys['debug'])){
			$this->debug = !empty($sys['debug']);
		}

		// Папка для чтения сообщений
		if(!empty($sys['default_mes_folder'])){
			$this->default_mes_folder = (string) $sys['default_mes_folder'];
		}

		// Настройка языка
		$this->set_lang($sys['lang'] ?? $this->default_lang);

		// Наполнение массива сообщений по умолчанию
		if(!empty($sys['type'])){
			$this->load((string) $sys['type']);
		}
	}

	protected function debug($mes, $method){
		$this->debug_messages[] = $method . ': ' . $mes;
	}

	public function debug_flush(){
		$this->debug_messages = [];
	}

	protected function check_type($type){
		if(!$type){
			throw new \Exception('Type is not set');
		}
	}

	public function load(string $type, $sys = []){

		$debug = !empty($sys['debug']) || $this->debug;
		
		$this->check_type($type);

		if(in_array($type, $this->keys_loaded)){
			return true;
		}
		
		// генерим путь
		$path = $this->default_mes_folder . $type;

		try {
			// грузим из файла
			$this->load_from_dir($path, array('debug'=>$debug));

			// запоминаем ключ
			$this->keys_loaded[] = $type;

			// возвращаем подтверждение
			return true;

		} catch (\Exception $e) {
			// пишем в дебаг
			$this->debug($e, __METHOD__);

			// возвращаем отказ
			return false;
		}
	}
	
	// ф-ция подгружает дополнительный языковой файл и накладывает на имеющееся
	public function load_another($type, bool $override = false){
		
		if(in_array($type, $this->keys_loaded)){
			return true;
		}

		// временно сохраняем имеющееся
		$existing_messages = $this->mes;
		
		// подгружаем новый тип
		if(!$this->load($type)){
			return false;
		}
		
		// смешиваем поля (новый переписывает старый)
		if($override){
			$this->mes = array_merge($existing_messages, $this->mes);
		}
		// смешиваем поля (добавляем только новые, старые оставляем как есть)
		else {
			$this->mes = array_merge($this->mes, $existing_messages);
		}

		return true;
	}

	// загрузка сообщений из файла
	public function load_from_dir(string $path, $sys = []){
		// неверный путь
		if(!$path){
			throw new \Exception('Path is not set');
		}
		
		// собираем полный путь
		$path_file = $path . '/' . $this->lang.'.ini';
		
		if(!is_file($path_file)){
			throw new \Exception('File does not exist: '.$path_file);
		}
		
		// грузим сообщения
		$messages = parse_ini_file($path_file);
		
		// если не загрузилось
		if($messages === false){
			throw new \Exception('Parsing ini file failed: '.$path_file);
		}
		
		$this->mes = $messages;
	}
	
	// настройка языка
	public function set_lang(string $lang){
		// допустимые языки
		if(!$this->is_allowed_lang($lang)){
			throw new \Exception('Language is not supported!');
		}
		
		// настраиваем язык
		$this->lang = $lang;
	}

	public function is_allowed_lang(string $lang){
		return in_array($lang, $this->langs);
	}

	public function get_default_lang(){
		return $this->default_lang;
	}

	public function get_lang(){
		return $this->lang;
	}

	/**
	 * @param string $index
	 * @return bool
	 */
	public function issetMes(string $index): bool
	{
		return isset($this->mes[$index]);
	}

	// загрузка сообщения
	public function get(string $index){
		// если сообщение не задано или не настроено
		if(!isset($this->mes[$index])){
			return $index;
		}
		
		// грузим сообщение
		$message = $this->mes[$index];
		
		// если включен парсинг (replacements)
		$args = func_get_args();
		
		if(isset($args[1])){
			// удаляем первый аргумент
			array_shift($args);
			
			// накапливаем массив для замены текстов
			$patterns = array_fill(0, count($args), '/\[([^\]]*)\]/');
			
			// производим замену
			$message = preg_replace($patterns, $args, $message, 1);
		}
		
		return $message;
	}

	/**
	 * Returns all messages
	 * @return array All messages
	 */
	public function getAllMes(): array
	{
		return $this->mes;
	}
}