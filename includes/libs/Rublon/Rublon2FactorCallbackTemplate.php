<?php

require_once 'RublonConsumer.php';
require_once 'RublonService2Factor.php';

/**
 * Template Method to handle Rublon callback script
 * 
 * You have to create a subclass which extends this abstract class
 * and implement the abstract methods. Then you can write the callback script
 * by creating an class instance and calling the run() method.
 *
 */
abstract class Rublon2FactorCallbackTemplate {
	
	
	// Errors
	const ERROR_MISSING_ACCESS_TOKEN = 1;
	const ERROR_REST_CREDENTIALS = 2;
	const ERROR_UNKNOWN_ACTION_FLAG = 3;
	const ERROR_MISSING_ACTION_FLAG = 4;
	const ERROR_USER_NOT_AUTHORIZED = 5;
	const ERROR_DIFFERENT_USER = 6;
	const ERROR_API_ERROR = 7;
	
	/**
	 * Field name of the action flag in consumer params
	 */
	const FIELD_ACTION_FLAG = 'actionFlag';
	
	
	/**
	 * Instance of the Rublon service (2-factor)
	 * 
	 * @var RublonService2Factor
	 */
	protected $service;
	
	
	/**
	 * Rublon API response instance
	 * 
	 * @var RublonResponse
	 */
	protected $response;
	


	// -------------------------------------------------------------------------------------------------------
	// Method that can be overridden
	
	
	/**
	 * Create and initialize consumer and service instances
	 */
	public function __construct() {
		$consumer = new RublonConsumer($this->getSystemToken(), $this->getSecretKey());
		$consumer->setLang($this->getLang());
		$consumer->setDomain($this->getAPIDomain());
		$this->service = new RublonService2Factor($consumer);
	}


	/**
	 * Return the (optional) Rublon API domain
	 * 
	 * Default domain can be replaced by testing configuration.
	 *
	 * @return string
	 */
	protected function getAPIDomain() {
		return 'https://code.rublon.com';
	}
	
	
	/**
	 * Get current language
	 * 
	 * @return string
	 */
	protected function getLang() {
		return 'en';
	}


	
	
	
	
	
	// -------------------------------------------------------------------------------------------------------
	// Methods to implement
	
	
	

	/**
	 * Get state from GET parameters or NULL if not present
	 *
	 * @return string|NULL
	 */
	abstract protected function getState();
	
	/**
	 * Get access token from GET parameters or NULL if not present
	 * 
	 * @return string|NULL
	 */
	abstract protected function getAccessToken();
	
	
	/**
	 * Handle an error and return back to the previous page
	 * 
	 * @param int $errorCode
	 * @param mixed $details (optional)
	 * @return void
	 */
	abstract protected function finalError($errorCode, $details = null);
	
	
	/**
	 * Restore session with passed session ID
	 * 
	 * @param string $sessionId
	 * @return void
	 */
	abstract protected function sessionStart($sessionId);
	
	/**
	 * Check whether user exists in session and has been authorized by first factor
	 * 
	 * @return boolean
	 */
	abstract protected function isUserAuthorizedByFirstFactor();
	
	
	/**
	 * Get Rublon profile ID of the user in current session
	 * 
	 * @return int
	 */
	abstract protected function getRublonProfileId();
	
	
	/**
	 * Set Rublon profile ID of the user in current session
	 * 
	 * @param int $rublonProfileId New profile ID
	 * @return void
	 */
	abstract protected function setRublonProfileId($rublonProfileId);
	
	
	/**
	 * Set second-factor authorization status of the user in current session to SUCCESS
	 * 
	 * @return void
	 */
	abstract protected function authorizeUser();
	
	
	/**
	 * Cancel authentication process and return back to the previous page
	 * 
	 * Note that it may be login authentication where user is not signed-in
	 * or enabling/disabling Rublon when user is signed-in.
	 * 
	 * @return void
	 */
	abstract protected function cancel();


	/**
	 * Handle success
	 * 
	 * @return void
	 */
	abstract protected function finalSuccess();


	/**
	 * Retrieve consumer's systemToken
	 * 
	 * @return string
	 */
	abstract protected function getSystemToken();


	/**
	 * Retrieve consumer's secretKey
	 * 
	 * @return string
	 */
	abstract protected function getSecretKey();
	
	
	
	
	
	
	
	// -------------------------------------------------------------------------------------------------------
	// Final methods - do not modify
	
	

	/**
	 * Run callback script
	 *
	 * @param RublonService2Factor $service
	 * @return void
	 */
	final public function run() {
	
		$state = strtolower($this->getState());
		switch ($state) {
				
			case 'ok':
				$this->handleStateOK();
				break;
	
			case 'error':
				$this->finalError(self::ERROR_API_ERROR);
				break;
	
			default:
				$this->cancel();
					
		}
	
	}
	
	
	/**
	 * Handle state "OK" - run authentication
	 *
	 * @return void
	 */
	final protected function handleStateOK() {
		if ($accessToken = $this->getAccessToken()) {
				
			try {
				$this->response = $this->service->getCredentials($accessToken);
			} catch (RublonException $e) {
				$this->finalError(self::ERROR_REST_CREDENTIALS, $e);
			}
				
			$consumerParams = $this->response->getConsumerParams();
			$sessionData = $this->response->getSessionData();
			
			// Start or restore session
			if (!empty($consumerParams['sessionId'])) {
				$sessionId = $consumerParams['sessionId'];
			} else {
				$sessionId = NULL;
			}
			$this->sessionStart($sessionId);
			
			// Check auth status
			if ($this->isUserAuthorizedByFirstFactor()) {
				if (isset($consumerParams[self::FIELD_ACTION_FLAG])) {
						
					switch ($consumerParams[self::FIELD_ACTION_FLAG]) {
						case RublonAuthParams::ACTION_FLAG_LOGIN:
							$this->login();
							break;
						case RublonAuthParams::ACTION_FLAG_LINK_ACCOUNTS:
							$this->enable();
							break;
						case RublonAuthParams::ACTION_FLAG_UNLINK_ACCOUNTS:
							$this->disable();
							break;
						default:
							$this->finalError(self::ERROR_UNKNOWN_ACTION_FLAG);
					}
						
					// If all is good - return back
					$this->finalSuccess();
						
				} else {
					$this->finalError(self::ERROR_MISSING_ACTION_FLAG);
				}
			} else {
				$this->finalError(self::ERROR_USER_NOT_AUTHORIZED);
			}
				
		} else {
			$this->finalError(self::ERROR_MISSING_ACCESS_TOKEN);
		}
	
	}
	
	
	/**
	 * Handle action "LOGIN"
	 * 
	 * @return void
	 */
	final protected function login() {
		if ($this->response->checkProfileId($this->getRublonProfileId())) {
			$this->authorizeUser();
		} else {
			$this->finalError(self::ERROR_DIFFERENT_USER);
		}
	}
	
	
	/**
	 * Handle action "UNLINK_ACCOUNTS" (disable Rublon)
	 * 
	 * @return void
	 */
	final protected function disable() {
		if ($this->response->checkProfileId($this->getRublonProfileId())) {
			$this->setRublonProfileId(NULL);
		} else {
			$this->finalError(self::ERROR_DIFFERENT_USER);
		}
	}
	
	
	/**
	 * Handle action "LINK_ACCOUNTS" (enable Rublon)
	 * 
	 * @return void
	 */
	final protected function enable() {
		$this->setRublonProfileId($this->response->getProfileId());
	}
	
	



}