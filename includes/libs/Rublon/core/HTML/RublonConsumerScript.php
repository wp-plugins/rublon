<?php

/**
 * Class for generating script tag that embeds consumer's JavaScript library.
 * 
 * The so-called "consumer script" is an individualized JavaScript library
 * that allows the website to use Rublon JavaScript elements - usually
 * the Rublon buttons. The library searches Rublon button HTML containers
 * in the website's DOM tree and fills them with proper buttons.
 * 
 * @see RublonButton
 */
class RublonConsumerScript {

	/**
	 * Template for script tag.
	 */
	const TEMPLATE_SCRIPT = '<script type="text/javascript" src="%s?t=%s"></script>';
	
	/**
	 * Consumer script URL.
	 */
	const URL_CONSUMER_SCRIPT = '/native/consumer_script_2factor';
		
	/**
	 * Rublon instance.
	 *
	 * @var Rublon
	 */
	protected $rublon = null;
	
	/**
	 * Current user's ID.
	 * 
	 * @var string
	 */
	protected $userId;

	/**
	 * Current user's email address.
	 *
	 * @var string
	 */
	protected $userEmail;
	
	
	/**
	 * Initialize object with Rublon instance.
	 *
	 * A Rublon class instance is required for
	 * the object to work.
	 * 
	 * @param Rublon2Factor $rublon An instance of the Rublon class
	 * @param string $userId
	 * @param string $userEmail
	 */
	public function __construct(Rublon2Factor $rublon, $userId, $userEmail) {
		$rublon->log(__METHOD__);
		$this->rublon = $rublon;
		$this->userId = $userId;
		$this->userEmail = $userEmail;
	}
	
	/**
	 * Generate a HTML code of this object.
	 * 
	 * Returns a HTML script tag that will load the consumer
	 * script from the Rublon servers.
	 * 
	 * @return string
	 */
	public function __toString() {
		$this->getRublon()->log(__METHOD__);
		return sprintf(self::TEMPLATE_SCRIPT,
			$this->getConsumerScriptURL(),
			md5(microtime())
		);
	}
	
	
	/**
	 * Get consumer's script URL.
	 * 
	 * Returns the URL address of the consumer script on
	 * the Rublon servers.
	 *
	 * @return string
	 */
	protected function getConsumerScriptURL() {
		$this->getRublon()->log(__METHOD__);
		return $this->getRublon()->getAPIDomain()
			. self::URL_CONSUMER_SCRIPT . '/'
			. urlencode(base64_encode($this->getParamsWrapper())) . '/'
			. rand(1, 99999);
	}
	
	
	
	/**
	 * Get script input parameters.
	 * 
	 * @return array
	 */
	protected function getParams() {
		$params = array(
			RublonAuthParams::FIELD_SYSTEM_TOKEN	=> $this->getRublon()->getSystemToken(),
			RublonAuthParams::FIELD_VERSION 		=> str_replace('-', '', $this->getRublon()->getVersionDate()),
			RublonAuthParams::FIELD_SERVICE 		=> $this->getRublon()->getServiceName(),
			RublonAuthParams::FIELD_USER_ID 		=> $this->userId,
			RublonAuthParams::FIELD_USER_EMAIL_HASH	=> hash(RublonAuthParams::HASH_ALG, strtolower($this->userEmail)),
		);
		
		if ($lang = $this->getRublon()->getLang()) {
			$params[RublonAuthParams::FIELD_LANG] = $lang;
		}
		
		return $params;
		
	}
	
	
	/**
	 * Get signed script input parameters.
	 * 
	 * @return string
	 */
	protected function getParamsWrapper() {
		if ($this->getRublon()->isConfigured()) {
			$wrapper = new RublonSignatureWrapper;
			$wrapper->setSecretKey($this->getRublon()->getSecretKey());
			$wrapper->setBody($this->getParams());
			return (string)$wrapper;
		} else {
			return json_encode($this->getParams());
		}
	}
	

	/**
	 * Get Rublon instance.
	 *
	 * @return Rublon
	 */
	public function getRublon() {
		return $this->rublon;
	}
	
	
}
