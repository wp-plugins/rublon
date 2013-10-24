<?php

/**
 * Parameters wrapper of the Rublon authentication process.
 * 
 * This class is used to prepare the parameters for the authentication
 * process. This includes both the parameters used for the authentication
 * itself as well as any additional parameters that would be used by the
 * integrated website in the callback. An object of this class can also
 * be used to embed the authentication parameters in a Rublon button. 
 * 
 * @see RublonButton
 * @author Rublon Developers
 * @version 2013-08-01
 */
class RublonAuthParams {
	

	/**
	 * Rublon service instance.
	 * 
	 * An istance of the RublonService class. Necessary for
	 * the class to work.
	 *
	 * @property RublonService $service
	 */
	protected $service = null;
	
	/**
	 * Consumer parameters store.
	 * 
	 * These optional parameters can be set by the integrated website.
	 * They will be signed with the Signature Wrapper (RublonSignatureWrapper class)
	 * using the website's secret key and can be retrieved in the callback via
	 * the getConsumerParams() method of the RublonResponse class. 
	 * 
	 * @property array $consumerParams
	 */
	protected $consumerParams = array();
	
	/**
	 * Outer parameters (not documented).
	 * 
	 * @property array $outerParams
	 */
	protected $outerParams = array();
	
	/**
	 * URL of the origin window.
	 * 
	 * The detault value is taken from the REQUEST_URI environment variable.
	 * 
	 * Note: the $originUrl parameter is needed by JavaScript postMessage method
	 * in Rublon Code popup window. It is not signed by the Signature Wrapper
	 * and should NOT be utilized to perform any HTTP redirects because of phishing possibility.
	 * 
	 * @property string $originUrl
	 */
	protected $originUrl = 'https://rublon.com/';
	
	/**
	 * Action flag.
	 * 
	 * The action flag determines both the text description displayed in the
	 * QR code window and sometimes may impose a certain behavior on the
	 * authentication process.
	 *
	 * @property string $actionFlag
	 */
	protected $actionFlag = null;
	
	
	/**
	 * Link accounts action
	 */
	const ACTION_FLAG_LINK_ACCOUNTS = 'link_accounts';
	
	/**
	 * Unlink accounts action
	 */
	const ACTION_FLAG_UNLINK_ACCOUNTS = 'unlink_accounts';
	
	/**
	 * Identity confirmation action
	 */
	const ACTION_FLAG_CONFIRM_ACTION = 'confirm_action';
	
	/**
	 * Login action
	 */
	const ACTION_FLAG_LOGIN = 'login';
	

	

	/**
	 * Initialize object with RublonService instance.
	 * 
	 * A RublonService class instance is required for
	 * the object to work.
	 *
	 * @param RublonService $service An instance of the RublonService class
	 */
	public function __construct(RublonService $service) {
		$service->getConsumer()->log(__METHOD__);
		$this->service = $service;
		
		if (isset($_SERVER['REQUEST_URI'])) {
			$this->originUrl = 'http://'. $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		}
		
	}
	
	
	/**
	 * Get URL of the authentication request to perform simple HTTP redirection.
	 * 
	 * Returns a URL address that will start the Rublon
	 * authentication process if redirected to.
	 * 
	 * @return string URL address
	 */
	public function getUrl() {
		$this->getConsumer()->log(__METHOD__);
		return $this->getConsumer()->getDomain() .
			'/code/native/' .
			urlencode($this->getUrlParamsString());
	}
	
	
	
	/**
	 * Get parameters string to apply in the authentication URL address
	 * 
	 * Returns the authentication parameters as a base64-encoded JSON string
	 * that will be passed with the URL address to the Rublon code window.
	 * 
	 * @return string
	 */
	protected function getUrlParamsString() {
		return base64_encode(json_encode($this->getUrlParams()));
	}
	
	
	/**
	 * Get ready-made authentication parameters object to apply in the authentication URL address
	 * 
	 * Returns the authentication process parameters as an object
	 * (including the Signature Wrapper-signed consumer params)
	 * that will be passed with the URL address to the Rublon code window. 
	 * 
	 * @return array
	 */
	public function getUrlParams() {
		
		$consumerParams = $this->getConsumerParams();
		$outerParams = $this->getOuterParams();
		
		$params = array();
		
		if (!empty($consumerParams) OR !empty($outerParams)) {
			$wrapper = RublonSignatureWrapper::wrap(
				$this->getConsumer()->getSecretKey(),
				$consumerParams,
				$outerParams
			);
			$params['consumerParams'] = $wrapper;
		}
		
		return $params;
		
	}
	
	/**
	 * Get the consumer parameters wrapper to apply in the Rublon button.
	 * 
	 * Returns the Signature Wrapper-signed consumer params
	 * to apply in the HTML wrapper of the Rublon button.
	 *
	 * @return array|NULL
	 */
	public function getConsumerParamsWrapper() {
		$consumerParams = $this->getConsumerParams();
		$outerParams = $this->getOuterParams();
		
		if (!empty($consumerParams) OR !empty($outerParams)) {
			return RublonSignatureWrapper::wrap(
				$this->getConsumer()->getSecretKey(),
				$consumerParams,
				$outerParams
			);
		} else {
			return null;
		}
	}
	
	
	/**
	 * Get the consumer parameters string to apply in the Rublon button.
	 * 
	 * Returns the Signature Wrapped-signed consumer params
	 * as a JSON string.
	 * 
	 * @return string|NULL
	 */
	public function getConsumerParamsWrapperString() {
		return json_encode($this->getConsumerParamsWrapper());
	}
	
	
	
	
	
	/**
	 * Set consumer parameters.
	 *
	 * Sets the consumer parameters using the given array.
	 *
	 * @param array $consumerParams An array of consumer parameters
	 * @return RublonAuthParams
	 */
	public function setConsumerParams($consumerParams) {
		$this->consumerParams = $consumerParams;
		return $this;
	}
	
	/**
	 * Set single consumer parameter.
	 * 
	 * Allows to add a single consumer param to the consumer
	 * params array.
	 * 
	 * @param string $name Param key in the array.
	 * @param mixed $value Param value.
	 * @return RublonAuthParams
	 */
	public function setConsumerParam($name, $value) {
		$this->consumerParams[$name] = $value;
		return $this;
	}
	

	/**
	 * Get consumer parameters.
	 * 
	 * Returns the consumer params as an array with the
	 * addition of actionFlag if it's set.
	 *
	 * @return array
	 */
	public function getConsumerParams() {
		$consumerParams = $this->consumerParams;
		if ($actionFlag = $this->getActionFlag()) {
			$consumerParams['actionFlag'] = $actionFlag;
		}
		$consumerParams['lang'] = $this->getConsumer()->getLang();
		$consumerParams['systemToken'] = $this->getConsumer()->getSystemToken();
		$consumerParams['originUrl'] = $this->originUrl;
		$consumerParams['version'] = $this->getConsumer()->getVersion();
		return $consumerParams;
	}
	
	
	/**
	 * Get single consumer parameter.
	 * 
	 * Returns a single consumer param from the consumer params
	 * array or null if the requested param doesn't exist.
	 * 
	 * @param string $name Param key in the array.
	 * @return mixed|NULL
	 */
	public function getConsumerParam($name) {
		$consumerParams = $this->getConsumerParams();
		if (isset($consumerParams[$name])) {
			return $consumerParams[$name];
		} else {
			return NULL;
		}
	}
	

	/**
	 * Set outer parameters (not documented).
	 *
	 * @param array $params Param array to be set.
	 * @return RublonAuthParams
	 */
	public function setOuterParams($params) {
		$this->outerParams = $params;
		return $this;
	}
	
	/**
	 * Get outer parameters (not documented).
	 *
	 * @return array
	 */
	public function getOuterParams() {
		return $this->outerParams;
	}
	
	
	/**
	 * Set the URL of the origin window.
	 * 
	 * The default value is taken from the REQUEST_URI environment variable. 
	 * 
	 * Note: the $originUrl parameter is needed by JavaScript postMessage method
	 * in Rublon Code popup window. It is not signed by the Signature Wrapper
	 * and should NOT be utilize to perform any HTTP redirects because of phishing possibility.
	 * 
	 * @param string $originUrl The URL to be set as originUrl. 
	 * @return RublonAuthParams
	 */
	public function setOriginUrl($originUrl) {
		$this->originUrl = $originUrl;
		return $this;
	}
	
	
	/**
	 * Get the URL of the origin window.
	 * 
	 * Returns the value of the originURL property.
	 * 
	 * @return string
	 */
	public function getOriginUrl() {
		return $this->originUrl;
	}
	
	

	/**
	 * Set action flag of the process
	 *
	 * Get available flags from RublonAuthParams::ACTION_FLAG_... constant.
	 *
	 * @param string $actionFlag One of the actionFlag constants.
	 * @return RublonAuthParams
	 */
	public function setActionFlag($actionFlag) {
		$this->actionFlag = $actionFlag;
		return $this;
	}
	
	
	/**
	 * Get action flag of the process.
	 * 
	 * Returns the value of the actionFlag property.
	 *
	 * @return string
	 */
	public function getActionFlag() {
		return $this->actionFlag;
	}
	
	
	/**
	 * Get service instance.
	 *
	 * Returns the object's instance of the RublonService class.
	 *
	 * @return RublonService
	 */
	public function getService() {
		return $this->service;
	}

	/**
	 * Get Rublon Consumer instance.
	 * 
	 * Returns the RublonConsumer class instance used in the creation
	 * of this class' RublonService class instance.
	 *
	 * @return RublonConsumer
	 */
	public function getConsumer() {
		return $this->getService()->getConsumer();
	}
	
}