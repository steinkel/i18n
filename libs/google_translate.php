<?php
/**
 * Copyright 2010, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2010, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Google Translate Library
 *
 * @package i18n
 * @subpackage i18n.libs
 * @see http://code.google.com/apis/ajaxlanguage/documentation/reference.html#_intro_fonje
 */
class GoogleTranslate extends Object {

/**
 * Whether or not the user ip must be passed in the API calls
 * 
 * Note: Here is what is detailed on Google website
 * 	Requests that include it are less likely to be mistaken for abuse.
 * 	In choosing to utilize this parameter, please be sure that you're in compliance
 * 	with any local laws, including any laws relating to disclosure of personal information being sent.
 * 
 * @var boolean
 */
	public $useUserIp = true;
	
/**
 * Application API Key
 * Can be configured using the Configure::write('GoogleTranslate.ApiKey', $value)
 * 
 * @var string
 */
	public $key = null;

/**
 * HttpSocket instance
 * 
 * @var HttpSocket
 */
	public $Http = null;
	
/**
 * API Endpoint url
 * 
 * @var string
 */
	private $__apiEndpoint = 'http://ajax.googleapis.com/ajax/services/language/';

/**
 * API version used
 * 
 * @var string
 */
	private $__version = '1.0';
	
/**
 * Constructor
 * Initialize the default attributes
 * 
 * @return void
 */
	public function _construct() {
		$key = Configure::read('GoogleTranslate.ApiKey');
		if (!empty($key)) {
			$this->key = $key;
		}
	}

/**
 * Translate a text from a source language to destination language
 * 
 * @throws RuntimeException When an error is returned by Google
 * @param string $text Text to translate
 * @param string $source Language code for the source (2 or 3 letters)
 * @param mixed $dest Language code to translate the text in (2 or 3 letters)
 * @param boolean $isHtml Whether or not the text is an Html text, optional [default: false]
 * @return array Translated data
 */
	public function translate($text, $source, $dest, $isHtml = false) {
		$source = $this->_twoLettersCode($source);
		$dest = $this->_twoLettersCode($dest);
		
		$url = $this->__apiEndpoint . 'translate';
		$query = array(
			'q' => $text,
			'langpair' => $source . '|' . $dest,
			'format' => $isHtml ? 'html' : 'text');
		$result = $this->_doCall($url, $query);
		
		return isset($result['translatedText']) ? urldecode($result['translatedText']) : false;
	}

/**
 * Wrapper for calling the remote API
 * 
 * @throws RuntimeException When an error is returned by Google
 * @return HttpSocket instance
 */
	protected function _doCall($uri, $query) {
		$result = false;
		if (!is_a($this->Http, 'HttpSocket')) {
			App::import('Core', 'HttpSocket');
			$this->Http = new HttpSocket();
		}

		$query['v'] = $this->__version;

		if ($this->useUserIp) {
			App::import('Component', 'RequestHandler');
			$RequestHandler = new RequestHandlerComponent();
			$query['userip'] = $RequestHandler->getClientIP(); 
		}

		if (!is_null($this->key)) {
			$query['key'] = $this->key;
		}

		$response = $this->Http->get($uri, $query);
		if ($this->Http->response['status']['code'] == 200) {
			$response = json_decode($response, true);
			if ($response['responseStatus'] != 200) {
				throw new RuntimeException($response['responseDetails']);
			}
			$result = $response['responseData'];
		}

		return $result;
	}

/**
 * Returns a 2 letters language code compatible with Google Translate
 * 
 * @param string $langCode Language code to convert
 * @return Two letters language code
 */
	protected function _twoLettersCode($langCode) {
		static $L10n = null;
		if (strlen($langCode) == 3) {
			if (is_null($L10n)) {
				App::import('Core', 'L10n');
				$L10n = new L10n();
			}
			$langCode = $L10n->map($langCode);
		}
		return $langCode;
	}
}