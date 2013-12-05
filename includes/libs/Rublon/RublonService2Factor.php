<?php

require_once 'core/RublonService.php';
require_once 'core/REST/Credentials/RublonRequestCredentials.php';
require_once 'core/REST/Credentials/RublonResponseCredentials.php';

require_once 'HTML/RublonButton.php';


/**
 * Class provides methods used by `Rublon Two Factor` service process.
 *
 * @author Rublon Developers
 */
class RublonService2Factor extends RublonService {
	
	/**
	 * Service name
	 * 
	 * @var string
	 */
	protected $serviceName = '2factor';
	
	
	/**
	 * Init authorization for user with given Rublon profile ID.
	 * 
	 * Method performs an HTTP redirection for the Rublon authorization process.
	 * 
	 * @param int $profileId Required Rublon user's profile ID
	 * @param RublonAuthParams|array $authParams Instance of the RublonAuthParams or consumer parameters array (optional)
	 * @return void
	 */
	public function initAuthorization($profileId, $authParams = null) {
		$this->consumer->log(__METHOD__);
		header('Location: '. $this->getAuthWebURL($profileId, $authParams));
		exit;
	}
	
	
	
	/**
	 * Get web-based login authentication URL address
	 * 
	 * @param int $profileId Required Rublon user's profile ID
	 * @param RublonAuthParams|array $params Instance of the RublonAuthParams or consumer parameters array (optional)
	 * @return string
	 */
	public function getAuthWebURL($profileId, $params = null) {
		return $this
			->_initAuthParamsLogin($profileId, $params)
			->getUrl();
	}
	
	
	
	/**
	 * Authenticate user and get user's credentials using one-time use access token and expected user's profile ID.
	 *
	 * One-time use access token is a session identifier which will be deleted after first usage.
	 * This method can be called only once in authentication process.
	 *
	 * @param string $accessToken One-time use access token
	 * @return RublonResponseCredentials
	 * @throws Exception
	 */
	public function getCredentials($accessToken) {
		if (isset($this->cacheCredentials[$accessToken])) {
			$this->consumer->log('return cached credentials');
			return $this->cacheCredentials[$accessToken];
		} else {
			$request = new RublonRequestCredentials($this, $accessToken);
			$response = $request->getResponse();
			$this->cacheCredentials[$accessToken] = $response;
			return $response;
		}
	}
	
	
	
	/**
	 * Create instance of button to enable 2FA for user's account ("Protect your account" button).
	 * 
	 * If you have to change any authentication parameters for the ready-made button get the RublonAuthParams reference by using:
	 * 
	 * <code>$authParams = $button->getAuthParams()</code>
	 * 
	 * Then utilize the object's method, for example:
	 * 
	 * <code>$authParams->setConsumerParam('name', 'value');</code>
	 * 
	 * @param array|RublonAuthParams $params (optional) Instance of the RublonAuthParams or consumer parameters for the auth transaction
	 * @return RublonButton
	 */
	public function createButtonEnable($params = null) {
		
		// Extract arguments by type
		$args = func_get_args();
		foreach ($args as $arg) {
			if (is_array($arg) || (is_object($arg) AND $arg instanceof RublonAuthParams)) {
				$params = $arg;
			}
		}
		
		return new RublonButton($this, $this->_initAuthParamsEnable($params));
	}
	
	
	/**
	 * Create instance of button to disable 2FA for user's account ("Disable protection" button).
	 * 
	 * If you have to change any authentication parameters for the ready-made button get the RublonAuthParams reference by using:
	 * 
	 * <code>$authParams = $button->getAuthParams()</code>
	 * 
	 * Then utilize the object's method, for example:
	 * 
	 * <code>$authParams->setConsumerParam('name', 'value');</code>
	 * 
	 * @param int $requireProfileId Require to authenticate by user with given profile ID
	 * @param array|RublonAuthParams $params (optional) Instance of the RublonAuthParams or consumer parameters for the auth transaction
	 * @return RublonButton
	 */
	public function createButtonDisable($requireProfileId, $params = null) {
		
		// Extract arguments by type
		$args = func_get_args();
		foreach ($args as $arg) {
			if (ctype_digit($arg)) {
				$requireProfileId = $arg;
			}
			else if (is_array($arg) || (is_object($arg) AND $arg instanceof RublonAuthParams)) {
				$params = $arg;
			}
		}
		
		if (empty($requireProfileId)) {
			trigger_error('Missing argument $requireProfileId in '. __METHOD__, E_USER_WARNING);
			return null;
		}
		
		if (empty($params) OR !is_array($params)) {
			$params = array();
		}
		
		return new RublonButton($this, $this->_initAuthParamsDisable($requireProfileId, $params));
		
	}

	

	/**
	 * Create instance of the RublonAuthParams configured for login
	 *
	 * @param int $profileId Required Rublon user's profile ID
	 * @param RublonAuthParams|array $params Instance of the RublonAuthParams or consumer parameters array (optional)
	 * @return RublonAuthParams
	 */
	protected function _initAuthParamsLogin($profileId, $params = null) {
		$authParams = $this->_initAuthParams(RublonAuthParams::ACTION_FLAG_LOGIN, $params);
		$authParams->setConsumerParam(RublonAuthParams::FIELD_REQUIRE_PROFILE_ID, $profileId);
		return $authParams;
	}
	
	

	/**
	 * Create instance of the RublonAuthParams configured for enabling Rublon protection
	 *
	 * @param RublonAuthParams|array $params Instance of the RublonAuthParams or consumer parameters array (optional)
	 * @return RublonAuthParams
	 */
	protected function _initAuthParamsEnable($params = null) {
		return $this->_initAuthParams(RublonAuthParams::ACTION_FLAG_LINK_ACCOUNTS, $params);
	}
	
	
	

	/**
	 * Create instance of the RublonAuthParams configured for disabling Rublon protection
	 *
	 * @param int $profileId Required Rublon user's profile ID
	 * @param RublonAuthParams|array $params Instance of the RublonAuthParams or consumer parameters array (optional)
	 * @return RublonAuthParams
	 */
	protected function _initAuthParamsDisable($profileId, $params = null) {
		$authParams = $this->_initAuthParams(RublonAuthParams::ACTION_FLAG_UNLINK_ACCOUNTS, $params);
		$authParams->setConsumerParam(RublonAuthParams::FIELD_REQUIRE_PROFILE_ID, $profileId);
		return $authParams;
	}
	
	
	
	
	
}