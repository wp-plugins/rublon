<?php

require_once 'RublonConsumerRegistrationCommon.php';

/**
 * Template method pattern abstract class to perform quick registration of the Rublon module.
 * 
 * Class provides main methods for communication with module registration API.
 *
 */
abstract class RublonConsumerRegistrationTemplate extends RublonConsumerRegistrationCommon {
	
	
	/**
	 * Action to initialize process.
	 */
	const ACTION_INITIALIZE = 'initialize';
	
	/**
	 * Action to update system token.
	 */
	const ACTION_UPDATE_SYSTEM_TOKEN = 'update_system_token';
	
	/**
	 * Action to pull the secret key from API.
	 */
	const ACTION_PULL_SECRET_KEY = 'pull_secret_key';
	
	/**
	 * Action final success.
	 */
	const ACTION_FINAL_SUCCESS = 'final_success';

	/**
	 * Action final error.
	 */
	const ACTION_FINAL_ERROR = 'final_error';
	
	/**
	 * Process lifetime in seconds.
	 */
	const PROCESS_LIFETIME = 3600; // seconds
	
	/**
	 * GET parameter with error code.
	 */
	const PARAM_ERROR = 'error';
	
	/**
	 * GET parameter with error message.
	 */
	const PARAM_ERROR_MSG = 'error_msg';
	
	/**
	 * Field name for the registration's communication URL.
	 */
	const FIELD_COMMUNICATION_URL = 'communicationUrl';
	
	/**
	 * Field name for the temporary secret key.
	 */
	const FIELD_TEMP_KEY = 'tempKey';
	
	/**
	 * Rublon Developers error flag.
	 */
	const DEVELOPERS_ERROR = 'developers_error';
	
	/**
	 * URL path for API methods.
	 */
	const URL_PATH_ACTION = '/consumers_registration';
	
	/**
	 * Template HTML for the POST form.
	 */
	const TEMPLATE_FORM_POST = '<form action="%s" method="post" id="RublonConsumerRegistration">
			%s
			<script>document.getElementById("RublonConsumerRegistration").submit();</script>
			<noscript><input type="submit" value="Register" /></noscript>
		</form>';
	
	/**
	 * Template HTML for the form's hidden input.
	 */
	const TEMPLATE_FORM_INPUT = '<input type="hidden" name="%s" value="%s" />';
	

	// Common technology flags
	const TECHNOLOGY_PHP_SDK = 'rublon-php-sdk';
	const TECHNOLOGY_OTHER = 'other';
	
	
	
	
	
	// --------------------------------------------------------------------------------------------------
	// The only public method


	/**
	 * Action to perform when the communication URL has been invoked.
	 *
	 * @param string $action Action name
	 * @final
	 */
	final public function action($action) {
		switch ($action) {
			case self::ACTION_INITIALIZE:
				$this->initialize();
				break;
			case self::ACTION_UPDATE_SYSTEM_TOKEN: // Save the system token given for the module
				$this->updateSystemToken();
				break;
			case self::ACTION_FINAL_SUCCESS: // Final success
				$this->finalSuccess();
				break;
			case self::ACTION_FINAL_ERROR: // Final error
				$this->finalError(self::DEVELOPERS_ERROR);
				break;
		}
	}
	
	
	
	
	
	// --------------------------------------------------------------------------------------------------
	// Major protected methods to override
	
	
	
	/**
	 * Final method when process was successful.
	 *
	 * To return to page and show an message please override this method in subclass.
	 * Don't forget to call the parent method first - it will clean some garbage.
	 *
	 * @return void
	 * @override
	 */
	protected function finalSuccess() {
		if ($this->canUserActivate()) {
			$this->saveInitialParameters(NULL, NULL);
		}
	}
	
	
	/**
	 * Final method when process was failed.
	 *
	 * To return to page and show an message please override this method in subclass.
	 * Don't forget to call the parent method first - it will clean some garbage.
	 *
	 * @param string $msg
	 * @return void
	 * @override
	 */
	protected function finalError($msg = NULL) {
		if ($this->canUserActivate()) {
// 			$this->saveSystemToken(NULL);
// 			$this->saveSecretKey(NULL);
// 			$this->saveInitialParameters(NULL, NULL);
		}
	}
	
	
	
	// --------------------------------------------------------------------------------------------------
	// Main actions final protected methods
	
	
	
	/**
	 * Initialize the module's registration process by generating temporary key.
	 * 
	 * @return bool
	 * @final
	 */
	final protected function initialize() {
		if ($this->canUserActivate()) {
			$tempKey = RublonSignatureWrapper::generateRandomString(self::SECRET_KEY_LENGTH);
			$this->saveInitialParameters($tempKey, time());
			$this->stdOut($this->getRegistrationForm());
			
		} else {
			throw new UserUnauthorized_RublonConsumerException;
		}
	}
	
	
	
	/**
	 * Update system token.
	 * 
	 * Save received by POST system token to local storage
	 * and call the secret key pulling method.
	 * To protect this method, the user have to be authorized
	 * in local system and this must be POST request.
	 * 
	 * @return void
	 * @final
	 */
	final protected function updateSystemToken() {
		if ($this->validateGeneral()) {
			if ($this->canUserActivate()) {
				try {
					$systemToken = $this->parseSystemToken($this->inputGET(RublonAuthParams::FIELD_SYSTEM_TOKEN));
					if (empty($systemToken)) {
						throw new MissingField_RublonClientException($this->getAPIClient(), self::FIELD_SYSTEM_TOKEN);
					}
				} catch (RublonException $e) {
					throw $e;
				}
				$this->saveSystemToken($systemToken);
				$this->pullSecretKey($systemToken);
			} else {
				throw new UserUnauthorized_RublonConsumerException();
			}
		} else {
			throw new InvalidProcess_RublonConsumerException();
		}
	}
	
	
	/**
	 * Pull the secret key from API server.
	 * 
	 * Perform REST request to get generated secret key and save it in local storage.
	 * Communication is signed by the temporary key.
	 * 
	 * @param string $systemToken
	 * @return void
	 * @final
	 */
	final protected function pullSecretKey($systemToken) {
		if ($this->validateGeneral()) {
			$response = $this->pullSecretKeyRequest($systemToken);
			if (!empty($response[RublonAPIClient::FIELD_RESULT][self::FIELD_SECRET_KEY])) {
				$this->saveSecretKey($response[RublonAPIClient::FIELD_RESULT][self::FIELD_SECRET_KEY]);
				$this->finalSuccess();
			} else {
				throw new MissingField_RublonClientException($this->getAPIClient(), self::FIELD_SECRET_KEY);
			}
		} else {
			throw new InvalidProcess_RublonConsumerException();
		}
	}


	// --------------------------------------------------------------------------------------------------
	// Minor protected methods - can be overriden
	
	
	
	/**
	 * Send string to the standard output.
	 * 
	 * If your system have an ususual way to echo strings, override this method in a subclass.
	 * 
	 * @param string $str
	 * @return void
	 */
	protected function stdOut($str) {
		echo $str;
	}
	
	
	/**
	 * Get GET parameter.
	 * 
	 * If your system have an ususual way to get GET parameters, override this method in a subclass.
	 * 
	 * @param string $name
	 * @return mixed
	 */
	protected function inputGET($name) {
		return (isset($_GET[$name]) ? $_GET[$name] : NULL);
	}
	

	/**
	 * Get the registration form.
	 *
	 * @return string
	 */
	protected function getRegistrationForm() {
		
		$action = $this->getAPIDomain() . self::URL_PATH_ACTION . '/' . self::ACTION_INITIALIZE;
		$action = htmlspecialchars($action);
		
		$content = $this->getInputHidden(self::FIELD_PROJECT_URL, $this->getProjectUrl())
			. $this->getInputHidden(self::FIELD_PROJECT_CALLBACK_URL, $this->getCallbackUrl())
			. $this->getInputHidden(self::FIELD_PROJECT_DATA, json_encode($this->getProjectData()))
			. $this->getInputHidden(self::FIELD_COMMUNICATION_URL, $this->getCommunicationUrl())
			. $this->getInputHidden(self::FIELD_TEMP_KEY, $this->getTempKey());
		
		return sprintf(self::TEMPLATE_FORM_POST, $action, $content);
		
	}		
	
	
	/**
	 * Get hidden input field for POST form.
	 *
	 * @param string $name
	 * @param string $value
	 * @return string
	 */
	protected function getInputHidden($name, $value) {
		return sprintf(self::TEMPLATE_FORM_INPUT, htmlspecialchars($name), htmlspecialchars($value));
	}
	
	

	// --------------------------------------------------------------------------------------------------
	// Core private methods

	
	/**
	 * Perform a HTTP request to pull the secret key.
	 * 
	 * @param string $systemToken
	 * @return array
	 */
	protected function pullSecretKeyRequest($systemToken) {
		
		// Prepare the request parameters
		$url = $this->getAPIDomain() . self::URL_PATH_ACTION . '/' . self::ACTION_PULL_SECRET_KEY;
		$params = array(
			self::FIELD_SYSTEM_TOKEN				=> $systemToken,
			self::FIELD_USER_ID						=> $this->getUserId(),
			self::FIELD_USER_EMAIL_HASH				=> self::hash($this->getUserEmail()),
			self::FIELD_LIB_VERSION					=> self::VERSION,
		);
		
		try /* to connect with Rublon API and pull secret key */ {
			$response = $this->request($url, $params);
		} catch (RublonException $e) {
			throw $e;
		}
		
		return $response;
		
	}
	
	
	/**
	 * Validate general parameters of the registration process.
	 *
	 * Check temporary key and start time.
	 *
	 * @return boolean
	 */
	protected function validateGeneral() {
		$tempKey = $this->getTempKey();
		$time = $this->getStartTime();
		return (
			!empty($tempKey)
			AND !empty($time)
			AND preg_match('/[a-z0-9]{' . self::SECRET_KEY_LENGTH . '}/i', $tempKey)
			AND is_integer($time)
			AND abs(time() - $time) <= self::PROCESS_LIFETIME
		);
	}
	
	
	/**	 
	 * Get System Token from base64 parameter.
	 * 
	 * @param string $data Base64 decoded data
	 * @return bool If the sign is valid return true the false
	 * @throws RublonException
	 */
	protected function parseSystemToken($data) {
		$body = RublonSignatureWrapper::parseMessage(base64_decode(urldecode($data)), $this->getTempKey());								
		return $body[self::FIELD_SYSTEM_TOKEN];
	}
	
	
	/**
	 * Check if current user is authorized to perform registration of the Rublon module.
	 *
	 * Check whether user authenticated in current session
	 * can perform administrative operations
	 * such as registering the Rublon module.
	 */
	protected function canUserActivate() {
		return $this->getRublon()->canUserActivate();
	}
	
	
	
	// --------------------------------------------------------------------------------------------------
	// Abstract methods necessary to implement

	
	/**
	 * Save initial parameters to the local storage.
	 * 
	 * Save given temporary key and process start time into
	 * local storage.
	 * Note that parameters must be available for different browsers
	 * and IPs so cannot be stored in browser sesssion,
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
	 * Get process start time from local storage.
	 *
	 * Return local-stored start time of the process or NULL if empty.
	 * Start time is used to validate lifetime of the process.
	 * Note that parameter must be available for different browsers
	 * and IPs so cannot be stored in browser sesssion,
	 * but in database or configuration file.
	 * 
	 * @return int
	 * @abstract
	 */
	abstract protected function getStartTime();
	
	
	/**
	 * Get the communication URL of this Rublon module.
	 * 
	 * Returns public URL address of the communication script.
	 * API server calls the communication URL to communicate
	 * with local system by REST or browser redirections.
	 * The communication URL is supplied to the API during initialization.
	 * 
	 * @return string
	 * @abstract
	 */
	abstract protected function getCommunicationUrl();
	

}


class RublonConsumerException extends RublonException {}
class InvalidProcess_RublonConsumerException extends RublonConsumerException {}
class UserUnauthorized_RublonConsumerException extends RublonConsumerException {}
