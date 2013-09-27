<?php

/**
 * Template method pattern abstract class to perform quick registration of the Rublon module
 * 
 * Class provides main methods for communication with module registration API.
 *
 */
abstract class RublonConsumerRegistrationTemplate {
	
	/**
	 * Action to initialize process
	 * 
	 * @var string
	 */
	const ACTION_INITIALIZE = 'initialize';
	
	/**
	 * Action to pull the secret key from API
	 * 
	 * @var string
	 */
	const ACTION_PULL_SECRET_KEY = 'pull_secret_key';
	
	/**
	 * Process lifetime in seconds
	 * 
	 * @var int
	 */
	const PROCESS_LIFETIME = 300; // seconds
	
	
	/**
	 * API domain
	 * 
	 * @var string
	 */
	protected $apiDomain = 'https://developers.rublon.com';
	
	
	/**
	 * URL path for API methods
	 * 
	 * @var string
	 */
	protected $actionUrl = '/consumers_registration';
	
	
	
	
	
	// --------------------------------------------------------------------------------------------------------------------------------------------------------
	// The only public method


	/**
	 * Action to perform when the communication URL has been invoked
	 *
	 * @param string $action Action name
	 * @final
	 */
	final public function action($action) {
		switch ($action) {
			case 'initialize':
				$this->initialize();
				break;
			case 'update_system_token': // Save the system token given for the module
				$this->updateSystemToken();
				break;
			case 'final_success': // Final success
				$this->finalSuccess();
				break;
			case 'final_error': // Final error
				$this->finalError();
				break;
		}
	}
	
	
	
	
	
	// --------------------------------------------------------------------------------------------------------------------------------------------------------
	// Major protected methods to override
	
	
	
	/**
	 * Final method when process was successful
	 *
	 * Clean some variables.
	 * To return to page and show an message please override this method in subclass.
	 * Don't forget to call the parent method!
	 *
	 * @return void
	 * @override
	 */
	protected function finalSuccess() {
		if ($this->isUserAuthorized()) {
			$this->saveInitialParameters(NULL, NULL);
		}
	}
	
	
	/**
	 * Final method when process was failed
	 *
	 * Clean some variables.
	 * To return to page and show an message please override this method in subclass.
	 * Don't forget to call the parent method!
	 *
	 * @param string $msg
	 * @return void
	 * @override
	 */
	protected function finalError($msg = NULL) {
		if ($this->isUserAuthorized()) {
			$this->saveSystemToken(NULL);
			$this->saveSecretKey(NULL);
			$this->saveInitialParameters(NULL, NULL);
		}
	}
	
	
	
	
	
	
	
	// --------------------------------------------------------------------------------------------------------------------------------------------------------
	// Main actions final protected methods
	
	
	
	/**
	 * Initialize the module's registration process by generating temporary key
	 * 
	 * @return bool
	 * @final
	 */
	final protected function initialize() {
		if ($this->isUserAuthorized()) {
			if ($this->_post('initialize')) {
				if ($this->saveInitialParameters($this->_generateRandomString(), time())) {
					$this->_echo($this->getRegistrationForm());
				} else {
					$this->finalError('Failed to save initial parameters');
				}
			} else {
				$this->finalError('Method must be invoked by POST request');
			}
		} else {
			$this->finalError('Unauthorized');
		}
	}
	
	
	
	/**
	 * Update system token
	 * 
	 * Save received by POST system token to local storage and call the secret key pulling method.
	 * To protect this method, the user have to be authorized in local system and this must be POST request.
	 * 
	 * @return void
	 * @final
	 */
	final protected function updateSystemToken() {
		if ($this->_validateGeneral()) {
			if ($this->isUserAuthorized()) {
				if ($systemToken = $this->_getSystemTokenFromBase64($this->_get('systemToken'))) {
					if ($this->saveSystemToken($systemToken)) {
						$this->pullSecretKey($systemToken);
					} else {
						$this->finalError('Failed to save system token');
					}
				} else {
					$this->finalError('No system token received');
				}
			} else {
				$this->finalError('Unauthorized');
			}
		} else {
			$this->finalError('Invalid process session');
		}
	}
	
	
	/**
	 * Pull the secret key from API server
	 * 
	 * Perform REST request to get generated secret key and save it in local storage.
	 * Communication is signed by the temporary key.
	 * 
	 * @param string $systemToken
	 * @return void
	 * @final
	 */
	final protected function pullSecretKey($systemToken) {
		if ($this->_validateGeneral()) {
			
			$consumer = new RublonConsumer(null, $this->getTempKey());
			$service = new RublonService2Factor($consumer);
			$request = new RublonRequest($service);
			$url = $this->apiDomain . $this->actionUrl .'/'. self::ACTION_PULL_SECRET_KEY;
			$params = array('systemToken' => $systemToken);
			try {
			$response = $request->setRequestParams($url, $params)->getRawResponse();
			} catch (RublonException $e) {
				$this->finalError('Failed to perform a Rublon request. ' . $e->getMessage());
			}
			try {
				$response = $this->_parseMessage($response, $this->getTempKey());
			} catch (Exception $e) {
				$this->finalError('Invalid response');
			}
				
			if (!empty($response['secretKey'])) {
	
				if ($this->saveSecretKey($response['secretKey'])) {
					if (!empty($response['profileId']))
						$this->handleProfileId($response['profileId']);
					$this->finalSuccess();
				} else {
					$this->finalError('Failed to save the secret key');
				}
	
			} else {
				$this->finalError('No secret key received');
			}
				
		} else {
			$this->finalError('Invalid process session');
		}
	}


	// --------------------------------------------------------------------------------------------------------------------------------------------------------
	// Minor protected methods - can be overriden
	
	
	
	/**
	 * Send string to the standard output
	 * 
	 * If your system have an ususual way to echo strings, override this method in a subclass.
	 * 
	 * @param string $str
	 * @return void
	 */
	protected function _echo($str) {
		echo $str;
	}
	
	

	/**
	 * Redirect to the given URL
	 *
	 * If your system have an ususual way to perform HTTP redirects, override this method in a subclass.
	 *
	 * @param string $url
	 * @return void
	 */
	protected function _redirect($url) {
		$this->_header('Location: '. $url);
		$this->_exit();
	}
	

	/**
	 * Send HTTP header
	 *
	 * If your system have an ususual way to send HTTP headers, override this method in a subclass.
	 *
	 * @param string $str
	 * @return void
	 */
	protected function _header($str) {
		header($str);
	}
	
	/**
	 * Interrupt the script execution
	 * 
	 * If you have to trigger some actions before exit, override this method in a subclass.
	 * 
	 * @return void
	 */
	protected function _exit() {
		exit;
	}
	
	
	/**
	 * Get POST parameter
	 * 
	 * If your system have an ususual way to get POST parameters, override this method in a subclass.
	 * 
	 * @param string $name
	 * @return mixed
	 */
	protected function _post($name) {
		return (isset($_POST[$name]) ? $_POST[$name] : NULL);
	}
	
	/**
	 * Get GET parameter
	 * 
	 * If your system have an ususual way to get GET parameters, override this method in a subclass.
	 * 
	 * @param string $name
	 * @return mixed
	 */
	protected function _get($name) {
		return (isset($_GET[$name]) ? $_GET[$name] : NULL);
	}
	
	

	/**
	 * Get the registration form
	 *
	 * @return string
	 */
	protected function getRegistrationForm() {
		$action = $this->apiDomain . $this->actionUrl .'/'. self::ACTION_INITIALIZE;
		$result = '<form action="'. htmlspecialchars($action) .'" method="post" id="RublonConsumerRegistration">
			'. $this->_getHidden('projectUrl', $this->getProjectUrl()) .'
			'. $this->_getHidden('communicationUrl', $this->getCommunicationUrl()) .'
			'. $this->_getHidden('callbackUrl', $this->getCallbackUrl()) .'
			'. $this->_getHidden('tempKey', $this->getTempKey()) .'
			'. $this->_getHidden('projectData', $this->getProjectData()) .'
		<script>document.getElementById("RublonConsumerRegistration").submit();</script>
		<noscript><input type="submit" value="Register" /></noscript>
		</form>';
		return $result;
	}			
	
	
	/**
	 * Get hidden input field for POST form
	 *
	 * @param string $name
	 * @param string $value
	 * @return string
	 */
	protected function _getHidden($name, $value) {
		return '<input type="hidden" name="'. htmlspecialchars($name) .'" value="'. htmlspecialchars($value) .'" />';
	}


	/**
	 * Get project's additional data
	 *
	 * The data returned will be used upon consumer's registration
	 * and are required. If any additional data is needed,
	 * this method may be overwritten.
	 *
	 * @return string
	 */
	protected function getProjectData() {
	
		return json_encode(array(
				'project-name' => 'New Rublon PHP Project',
				'project-technology' => 'rublon-php-sdk'
		), true);
	
	}


	// --------------------------------------------------------------------------------------------------------------------------------------------------------
	// Core private methods


	/**
	 * Send a REST response to the standard output
	 *
	 * If secret key given wrap response into RublonSignatureWrapper.
	 *
	 * @param mixed $response
	 * @param string $secretKey (optional)
	 * @return void
	 */
	private function _RESTResponse($response, $secretKey = NULL) {
		$this->_header('content-type: application/json; charset=UTF-8');
		if ($secretKey) {
			$response = RublonSignatureWrapper::wrap($secretKey, $response);
		}
		$response = (is_string($response) ? $response : json_encode($response));
		$this->_echo($response);
		$this->_exit();
	}
	
	
	/**
	 * Create an error response array
	 *
	 * @param string $msg
	 * @return array
	 */
	private function _RESTError($msg) {
		return array('status' => 'ERROR', 'msg' => $msg);
	}
	
	
	
	/**
	 * Parse signed message
	 *
	 * @throws Exception
	 * @param mixed $response
	 * @param string $secretKey
	 * @return mixed
	 */
	private function _parseMessage($response, $secretKey) {
		$response = json_decode($response, true);
		if (!empty($response)) {
			if (!empty($response['data']) AND !empty($response['sign'])) {
				if (RublonSignatureWrapper::verifyData($response['data'], $secretKey, $response['sign'])) {
					$data = json_decode($response['data'], true);
					if (!empty($data) AND isset($data['body'])) {
						$body = json_decode($data['body'], true);
						if (is_array($body) AND !empty($body)) {
							return $body;
						} else {
							return $data['body'];
						}
					} else {
						throw new Exception('Invalid response data');
					}
				} else {
					throw new Exception('Invalid signature');
				}
			}
			else if (!empty($response['status'])) {
				if ($response['status'] == 'ERROR') {
					throw new Exception(isset($response['msg']) ? $response['status'] : 'Error response');
				} else {
					return $response;
				}
			} else {
				throw new Exception('Invalid response');
			}
		} else {
			throw new Exception('Empty response');
		}
	}
	
	
	
	/**
	 * Generate random string
	 *
	 * @param int $len (optional)
	 * @return string
	 */
	private function _generateRandomString($len = 100) {
		$chars = '1234567890qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM';
		$max = strlen($chars) - 1;
		$result = '';
		for ($i=0; $i<$len; $i++) {
			$result .= $chars[mt_rand(0, $max)];
		}
		return $result;
	}
	
	
	/**
	 * Validate general parameters of the registration process
	 *
	 * Check temporary key and start time.
	 *
	 * @return boolean
	 */
	private function _validateGeneral() {
		$tempKey = $this->getTempKey();
		$time = $this->getStartTime();
		return (!empty($tempKey) AND !empty($time) AND preg_match('/[a-z0-9]{100}/i', $tempKey) AND is_integer($time) AND $time > time() - self::PROCESS_LIFETIME);
	}
	
	/**	 
	 * Get System Token from base64 parameter
	 * @param string $data Base64 decoded data
	 * @return bool If the sign is valid return true the false
	 */
	private function _getSystemTokenFromBase64($data) {				
		$obj = $this->_parseMessage(base64_decode(urldecode($data)), $this->getTempKey());								
		return $obj['systemToken'];
	}
	
	
	
	
	
	
	
	// --------------------------------------------------------------------------------------------------------------------------------------------------------
	// Abstract methods necessary to implement
	


	/**
	 * Check if current user is authorized to perform registration of the Rublon module
	 *
	 * Check whether user authenticated in current session can perform administrative operations
	 * such as registering the Rublon module.
	 *
	 * @return bool
	 * @abstract
	 */
	abstract protected function isUserAuthorized();
	
	
	/**
	 * Get current system token from local storage
	 * 
	 * Returns local-stored system token or NULL if empty.
	 * Note that parameter must be available for different browsers and IPs so cannot be stored in browser sesssion,
	 * but in database or configuration file.
	 * 
	 * @return string
	 * @abstract
	 */
	abstract protected function getSystemToken();
	
	

	/**
	 * Save system token to the local storage
	 *
	 * Save given system token into local storage.
	 * Note that parameter must be available for different browsers and IPs so cannot be stored in browser sesssion,
	 * but in database or configuration file.
	 *
	 * Returns true/false on success/failure.
	 *
	 * @param string $systemToken
	 * @return bool
	 * @abstract
	 */
	abstract protected function saveSystemToken($systemToken);
	

	/**
	 * Get current secret key from local storage
	 * 
	 * Return local-stored secret key or NULL if empty.
	 * Note that parameter must be available for different browsers and IPs so cannot be stored in browser sesssion,
	 * but in database or configuration file.
	 * 
	 * @return string
	 * @abstract
	 */
	abstract protected function getSecretKey();
	
	/**
	 * Save secret key to the local storage
	 *
	 * Save given secret key into local storage.
	 * Note that parameter must be available for different browsers and IPs so cannot be stored in browser sesssion,
	 * but in database or configuration file.
	 *
	 * Returns true/false on success/failure.
	 *
	 * @param string $systemToken
	 * @return bool
	 * @abstract
	*/
	abstract protected function saveSecretKey($secretKey);
	
	/**
	 * Handle profileId of the user registering the consumer
	 * 
	 * If the user's profileId was received along with the secretKey, handle it accordingly
	 * (e.g. secure the registering user's account if necessary).
	 *  
	 * @param int $profileId
	 * @abstract
	 */
	abstract protected function handleProfileId($profileId);

	/**
	 * Get temporary key from local storage
	 * 
	 * Returns local-stored temporary key or NULL if empty.
	 * Temporary key is used to sign communication with API instead of secret key which is not given.
	 * Note that parameter must be available for different browsers and IPs so cannot be stored in browser sesssion,
	 * but in database or configuration file.
	 * 
	 * @return string
	 */
	abstract protected function getTempKey();
	
	/**
	 * Save temporary key to the local storage
	 *
	 * Save given temporary key into local storage.
	 * Temporary key is used to sign communication with API instead of secret key which is not given.
	 * Note that parameter must be available for different browsers and IPs so cannot be stored in browser sesssion,
	 * but in database or configuration file.
	 *
	 * Returns true/false on success/failure.
	 *
	 * @param string $systemToken
	 * @return bool
	 * @abstract
	*/
	abstract protected function saveTempKey($tempKey);
	
	
	/**
	 * Save initial parameters to the local storage
	 * 
	 * Save given temporary key and process start time into local storage.
	 * Note that parameters must be available for different browsers and IPs so cannot be stored in browser sesssion,
	 * but in database or configuration file.
	 * 
	 * Returns true/false on success/failure.
	 * 
	 * @param string $tempKey
	 * @param int $startTime
	 * @return bool
	 * @abstract
	 */
	abstract protected function saveInitialParameters($tempKey, $startTime);

	
	/**
	 * Get process start time from local storage
	 *
	 * Return local-stored start time of the process or NULL if empty.
	 * Start time is used to validate lifetime of the process.
	 * Note that parameter must be available for different browsers and IPs so cannot be stored in browser sesssion,
	 * but in database or configuration file.
	 * 
	 * @return int
	 * @abstract
	 */
	abstract protected function getStartTime();
	
	
	/**
	 * Get the communication URL of this Rublon module
	 * 
	 * Returns public URL address of the communication script.
	 * API server calls the communication URL to communicate with local system by REST or browser redirections.
	 * The communication URL is supplied to the API during initialization.
	 * 
	 * @return string
	 * @abstract
	 */
	abstract protected function getCommunicationUrl();
	
	
	/**
	 * Get project's public webroot URL address
	 * 
	 * Returns the main project URL address needful for registration consumer in API.
	 * 
	 * @return string
	 * @abstract
	 */
	abstract protected function getProjectUrl();
	
	/**
	 * Get the callback URL of this Rublon module
	 * 
	 * Returns public URL address of the Rublon consumer's callback script.
	 * API server calls the callback URL after valid authentication.
	 * The callback URL is needful for registration consumer in API.
	 * 
	 * @return string
	 * @abstract
	 */
	abstract protected function getCallbackUrl();


}


