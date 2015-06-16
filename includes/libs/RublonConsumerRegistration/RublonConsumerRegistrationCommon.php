<?php

abstract class RublonConsumerRegistrationCommon {
	
	/**
	 * Library version date.
	 */
	const VERSION = '2014-06-10';
	
	/**
	 * Default registration API domain.
	 */
	const DEFAULT_API_DOMAIN = 'https://developers.rublon.com';

	/**
	 * URL path for API methods.
	 */
	const URL_PATH_ACTION = '/consumers_registration';
	
	/**
	 * Field name for the system token.
	 */
	const FIELD_SYSTEM_TOKEN = 'systemToken';

	/**
	 * Field name for the secret key.
	 */
	const FIELD_SECRET_KEY = 'secretKey';

	
	/**
	 * Field name for the library version.
	 */
	const FIELD_LIB_VERSION = 'libVersion';
	
	/**
	 * Field name for the project name.
	 */
	const FIELD_PROJECT_NAME = 'projectName';
	
	/**
	 * Field name for the additional project data.
	 */
	const FIELD_PROJECT_DATA = 'projectData';
	
	/**
	 * Field name for the project technology.
	 */
	const FIELD_PROJECT_TECHNOLOGY = 'projectTechnology';
	
	/**
	 * Field name for the project URL.
	 */
	const FIELD_PROJECT_URL = 'projectUrl';
	
	/**
	 * Field name for the project's callback URL.
	 */
	const FIELD_PROJECT_CALLBACK_URL = 'callbackUrl';
	
	/**
	 * Length of the secret key.
	 */
	const SECRET_KEY_LENGTH = 100;
	
	/**
	 * Field name for the user's ID.
	 */
	const FIELD_USER_ID = 'userId';
	
	/**
	 * Field name for the user's email address.
	 */
	const FIELD_USER_EMAIL_HASH = 'userEmailHash';
	
	

	// Common technology flags
	const TECHNOLOGY_PHP_SDK = 'rublon-php-sdk';
	const TECHNOLOGY_OTHER = 'other';
	
	
	protected $APIClient = null;
	
	
	
	public function __construct() {}
	

	/**
	 * Get registration API domain.
	 *
	 * @return string
	 */
	protected function getAPIDomain() {
		return self::DEFAULT_API_DOMAIN;
	}
	

	/**
	 * Perform a HTTP request.
	 *
	 * @param string $url
	 * @param array $params
	 * @return array
	 */
	protected function request($url, array $params = array()) {
		$client = $this->getAPIClient();
		try {
			return $client
				->setRequestUrl($url)
				->setRequestParams($params)
				->perform()
				->getResponse();
		} catch (Exception $e) {
			throw $e;
		}
	}
	
	
	/**
	 * Get API client instance.
	 *
	 * @return RublonAPIClient
	 */
	protected function getAPIClient() {
		if (empty($this->APIClient)) {
			$this->APIClient = new RublonAPIClient($this->getRublon());
		}
		return $this->APIClient;
	}
	
	
	/**
	 * Get project's additional data.
	 *
	 * The data returned will be used upon consumer's registration
	 * and are required. If any additional data is needed,
	 * this method may be overwritten.
	 *
	 * @return string
	 */
	protected function getProjectData() {
		return array(
			self::FIELD_PROJECT_NAME			=> $this->getProjectName(),
			self::FIELD_PROJECT_TECHNOLOGY		=> $this->getProjectTechnology(),
			self::FIELD_USER_ID					=> $this->getUserId(),
			self::FIELD_USER_EMAIL_HASH			=> self::hash($this->getUserEmail()),
			self::FIELD_LIB_VERSION				=> self::VERSION,
			self::FIELD_PROJECT_CALLBACK_URL	=> $this->getCallbackUrl(),
			self::FIELD_PROJECT_URL 			=> $this->getProjectUrl(),
		);
	}
	
	
	/**
	 * Hash value.
	 *
	 * @param string $val
	 * @return string
	 */
	protected static function hash($val) {
		return hash(RublonAPIClient::HASH_ALG, strtolower($val));
	}
	

	/**
	 * Save system token to the local storage.
	 *
	 * Save given system token into local storage.
	 * Note that parameter must be available for different browsers
	 * and IPs so cannot be stored in browser sesssion,
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
	 * Save secret key to the local storage.
	 *
	 * Save given secret key into local storage.
	 * Note that parameter must be available for different browsers
	 * and IPs so cannot be stored in browser sesssion,
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
	 * Get temporary key from local storage.
	 *
	 * Returns local-stored temporary key or NULL if empty.
	 * Temporary key is used to sign communication with API
	 * instead of secret key which is not given.
	 * Note that parameter must be available for different browsers
	 * and IPs so cannot be stored in browser sesssion,
	 * but in database or configuration file.
	 *
	 * @return string
	*/
	abstract protected function getTempKey();
	

	/**
	 * Get project's public webroot URL address.
	 *
	 * Returns the main project URL address needful
	 * for registration consumer in API.
	 *
	 * @return string
	 * @abstract
	 */
	abstract protected function getProjectUrl();
	
	/**
	 * Get the callback URL of this Rublon module.
	 *
	 * Returns public URL address of the Rublon consumer's callback script.
	 * API server calls the callback URL after valid authentication.
	 * The callback URL is needful for registration consumer in API.
	 *
	 * @return string
	 * @abstract
	*/
	abstract protected function getCallbackUrl();
	
	
	/**
	 * Get name of the project.
	 *
	 * Returns name of the project that will be set in Rublon Developers Dashboard.
	 *
	 * @return string
	*/
	abstract protected function getProjectName();
	
	
	
	/**
	 * Get project's technology.
	 *
	 * Returns technology, module or library name to set in project.
	 *
	 * @return string
	*/
	abstract protected function getProjectTechnology();


	/**
	 * Get Rublon2Factor class instance.
	 *
	 * @return Rublon2Factor
	 */
	abstract protected function getRublon();
	
	/**
	 * Get current user's ID.
	 *
	 * @return string
	 */
	abstract protected function getUserId();
	
	
	/**
	 * Get current user's email.
	 *
	 * @return string
	*/
	abstract protected function getUserEmail();
	
	
}