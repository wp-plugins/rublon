<?php

if (!defined('DIRECTORY_SEPARATOR')) {
	define('DIRECTORY_SEPARATOR', '/');
}

require_once 'core/RublonException.php';
require_once 'core/RublonSignatureWrapper.php';
require_once 'core/RublonAuthParams.php';
require_once 'core/RublonService.php';

require_once 'HTML/RublonButton.php';
require_once 'HTML/RublonConsumerScript.php';
require_once 'HTML/RublonHTMLHelper.php';


/**
 * Rublon Consumer class.
 *
 * The main class provides common methods for all Rublon services.
 * In order for the class to work properly, it must be initiated with two parameters: System Token and the Secret Key.
 * Both of parameters can be obtained from developer dashboard at developers.rublon.com. 
 *
 * @author Rublon Developers
 * @version 2013-08-01
 */
class RublonConsumer {

	/**
	 * Latest version release date of the RublonConsumer class.
	 *
	 * @var string
	 */
	const VERSION_DATE = '2013-08-01';

	/**
	 * Enable log array storage.
	 * 
	 * If TRUE then the storage will collect all informations about authentication process. 
	 * @see RublonConsumer::getLog() to obtain a log array.	  
	 *
	 * @var boolean
	 */
	const LOG_ENABLED = true;

	/**
	 * System token
	 *
	 * @var string
	 */
	protected $systemToken = null;

	/**
	 * Secret key
	 *
	 * @var string
	 */
	protected $secretKey = null;

	/**
	 * Rublon API domain.
	 * 
	 * URL used to make requests to Rublon API.
	 *
	 * @var string
	 */
	protected $domain = 'https://code.rublon.com';
	
	/**
	 * Language code
	 * 
	 * 2-letter language code compliant with <a href="https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes">ISO 639-1</a>.
	 * 
	 * @see https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes
	 * @var string
	 */
	protected $lang = 'en';
	
	/**
	 * Log store
	 * 
	 * The array stores informations about Rublon authentication process.
	 *
	 * @var array
	 */
	protected $log = array();


	// --------------------------------------------------------------------------------------------------------------------------------
	// Public methods
	// --------------------------------------------------------------------------------------------------------------------------------



	/**
	 * Initialize RublonConsumer with given System Token and Secret Key.
	 *
	 * @param string $systemToken
	 * @param string $secretKey
	*/
	public function __construct($systemToken, $secretKey) {
		
		$this->log(__METHOD__);
		
		$this->systemToken = $systemToken;
		$this->secretKey = $secretKey;
		
	}
	
	
	
	
	// --------------------------------------------------------------------------------------------------------------------------------
	// Getters and setters
	// --------------------------------------------------------------------------------------------------------------------------------


	/**
	 * Get Rublon API domain.
	 *
	 * @return string
	 */
	public function getDomain() {
		return $this->domain;
	}

	/**
	 * Set Rublon API domain.
	 *
	 * @param string $domain
	 * @return RublonConsumer
	 */
	public function setDomain($domain) {
		$this->log(__METHOD__ .' -- '. $domain);
		$this->domain = $domain;
		return $this;
	}

	/**
	 * Get secret key
	 *
	 * @return string
	 */
	public function getSecretKey() {
		return $this->secretKey;
	}


	/**
	 * Get system token
	 *
	 * @return string
	 */
	public function getSystemToken() {
		return $this->systemToken;
	}
	
	/**
	 * Log message
	 * 
	 * @param mixed $msg
	 * @return RublonConsumer
	 */
	public function log($msg) {
		if (self::LOG_ENABLED) {
			$this->log[] = $msg;
		}
		return $this;
	}

	/**
	 * Get logs array
	 * 
	 * @return array
	 */
	public function getLog() {
		return $this->log;
	}
	
	/**
	 * Get version date
	 * 
	 * @return string
	 */
	public function getVersion() {
		return self::VERSION_DATE;
	}
	
	
	/**
	 * Set language code
	 * 
	 * 2-letter language code compliant with <a href="https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes">ISO 639-1</a>.
	 * 
	 * @see https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes
	 * @param string $lang
	 * @return RublonConsumer
	 */
	public function setLang($lang) {
		$this->lang = $lang;
		return $this;
	}
	
	
	/**
	 * Get language code
	 * 
	 * 2-letter language code compliant with <a href="https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes">ISO 639-1</a>.
	 * 
	 * @see https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes
	 * @return string
	 */
	public function getLang() {
		return $this->lang;
	}


}

