<?php

require_once 'RublonConsumerRegistrationTemplate.php';

class RublonConsumerRegistration extends RublonConsumerRegistrationTemplate {

	/**
	 * Final method when registration process was successful
	 *
	 * Clean some variables.
	 * Update Rublon using 'systemToken' and 'secretKey' from
	 * successfully registered project and to Rublon setting
	 * page
	 *
	 * @return void
	 */
	protected function finalSuccess() {

		parent::finalSuccess();
		
		$adminProfileId = $this->getAdminProfileId();
		$this->updateRublonSettings();

		$currentUser = wp_get_current_user();
		if (!empty($adminProfileId) && !RublonHelper::isUserSecured($currentUser))
			$success = RublonHelper::connectRublon2Factor($currentUser, $adminProfileId);

		if ($success) {
			$updateMessage = 'PLUGIN_REGISTERED';
			RublonHelper::setMessage($updateMessage, 'updated', 'CR');
		} else {
			$errorCode = 'PLUGIN_REGISTERED_NO_PROTECTION';
			RublonHelper::setMessage($errorCode, 'error', 'CR');
		}
		RublonCookies::setAuthCookie();

		$pluginMeta = RublonHelper::preparePluginMeta();
		$pluginMeta['action'] = 'activation';
		RublonHelper::pluginHistoryRequest($pluginMeta);
		
		$this->_redirect(admin_url(RublonHelper::RUBLON_PAGE));
	}
	
	/**
	 * Final method when registration process was failed
	 *
	 * Clean some variables.
	 * Set an error message, an redirect to Rublon setting
	 * page
	 *
	 * @param string $msg
	 * @return void
	 */
	protected function finalError($msg = NULL) {

		parent::finalError($msg);

		if (!$msg)
			$msg = $this->_get('error');

		$notifierMessage = 'Consumer registration error.<br /><br />';
		$errorCode = 'API_ERROR';
		if (!empty($msg)) {
			if (stripos($msg, 'ERROR_CODE:') !== false) {
				$errorCode = str_replace('ERROR_CODE: ', '', $msg);
				$notifierMessage .= __('Rublon error code: ', 'rublon2factor') . '<strong>' . $errorCode . '</strong>';
			} else {
				$notifierMessage .= 'Rublon error message: [' . $msg . ']';
			}
		}
		RublonHelper::setMessage($errorCode, 'error', 'CR');
		
		// send issue notify
		echo $this->_notify($notifierMessage);

		$this->_redirect(admin_url(RublonHelper::RUBLON_PAGE));

	}


	/**
	 * Check whether user authenticated in current session can 
	 * perform administrative operations such as registering 
	 * the Rublon module.
	 *
	 * @return bool
	 */
	protected function isUserAuthorized() {
		return current_user_can('manage_options');
	}

	/**
	 * Returns local-stored system token or NULL if empty.
	 * 
	 * @return string/null
	 */
	protected function getSystemToken() {
		$config = $this->getConfig();		
		return (isset($config['systemToken']) ? $config['systemToken'] : NULL);
	}
	
	/**
	 * Save system token to the local storage
	 * 
	 * Returns true/false on success/failure.
	 *
	 * @param string $systemToken
	 * @return bool
	 */
	protected function saveSystemToken($systemToken) {
		$config = $this->getConfig();
		$config['systemToken'] = $systemToken;
		return $this->_saveConfig($config);
	}


	/**
	 * Save profileId of the admin who activated the plugin
	 * 
	 * @param int $profileId
	 */
	protected function handleProfileId($profileId) {

		$config = $this->getConfig();
		$config['adminProfileId'] = $profileId;
		return $this->_saveConfig($config);

	}


	/**
	 * Return profileId of the admin who activated the plugin, if it was received
	 * 
	 * @return string
	 */
	protected function getAdminProfileId() {

		$config = $this->getConfig();
		return (isset($config['adminProfileId'])) ? $config['adminProfileId'] : NULL;

	}


	/**
	 * Return local-stored secret key or NULL if empty.
	 * 
	 * @return string/null
	 */
	protected function getSecretKey() {
		$config = $this->getConfig();
		return (isset($config['secretKey']) ? $config['secretKey'] : NULL);
	}
	
	/**
	 * Save secret key to the local storage
	 *
	 * Returns true/false on success/failure.
	 *
	 * @param string $secretKey
	 * @return bool
	*/
	protected function saveSecretKey($secretKey) {
		$config = $this->getConfig();
		$config['secretKey'] = $secretKey;
		return $this->_saveConfig($config);
	}
	
	/**
	 * Returns local-stored temporary key or NULL if empty.
	 * Temporary key is used to sign communication with API instead of secret key which is not given.
	 * 
	 * @return string
	 */
	protected function getTempKey() {
		$config = $this->getConfig();
		return (isset($config['tempKey']) ? $config['tempKey'] : NULL);
	}

	/**
	 * Save temporary key to the local storage
	 *
	 * Returns true/false on success/failure.
	 *
	 * @param string $tempKey
	 * @return bool
	*/
	protected function saveTempKey($tempKey) {
		$config = $this->getConfig();
		$config['tempKey'] = $tempKey;
		return $this->_saveConfig($config['rublon']);
	}
	
	/**
	 * Save given temporary key and process start time into local storage.
	 * 
	 * Returns true/false on success/failure.
	 * 
	 * @param string $tempKey
	 * @param int $startTime
	 * @return bool
	 */	
	protected function saveInitialParameters($tempKey, $startTime) {
		$config = $this->getConfig();
		$config['tempKey'] = $tempKey;
		$config['startTime'] = $startTime;
		return $this->_saveConfig($config);
	}
	
	/**
	 * Return local-stored start time of the process or NULL if empty.
	 * Start time is used to validate lifetime of the process.
	 * 
	 * @return int/null
	 */
	protected function getStartTime() {
		$config = $this->getConfig();
		return (isset($config['startTime']) ? $config['startTime'] : NULL);
	}

	/**
	 * Get the communication URL of this Rublon module
	 * 
	 * Returns public URL address of the communication script.
	 * API server calls the communication URL to communicate with local system by REST or browser redirections.
	 * The communication URL is supplied to the API during initialization.
	 * 
	 * @return string
	 */
	protected function getCommunicationUrl() {

		return $this->_getRublonActionUrl('register');

	}
	
	/**
	 * Get project's public webroot URL address
	 * 
	 * Returns the main project URL address needful for registration consumer in API.
	 * 
	 * @return string
	 */
	protected function getProjectUrl() {

		return trailingslashit(site_url());

	}
	
	/**
	 * Get the callback URL of this Rublon module
	 * 
	 * Returns public URL address of the Rublon consumer's callback script.
	 * API server calls the callback URL after valid authentication.
	 * The callback URL is needful for registration consumer in API.
	 * 
	 * @return string
	 */
	protected function getCallbackUrl() {

		return $this->_getRublonActionUrl('callback');

	}


	/**
	 * Prepare the URL for executing Rublon actions
	 * 
	 * @param string $action Action to be passed in the URL via GET
	 */
	private function _getRublonActionUrl($action) {

		return site_url('?rublon=' . $action);		

	}
	
	/**
	 * Save a given data in a local-stored configuration.
	 * 
	 * @param array $data
	 * @return bool
	 */
	protected function _saveConfig($data) {
		$settings = get_option(RublonHelper::RUBLON_REGISTRATION_SETTINGS_KEY);
		$settings = $data;
		update_option(RublonHelper::RUBLON_REGISTRATION_SETTINGS_KEY, $settings);
		return (isset($settings));
	}

	/**
	 * Sets the API domain for testing
	 * 
	 * @param string $domain API domain
	 */
	public function setDomain($domain) {
		$this->apiDomain = $domain;
	}

	/**
	 * Returns the API domain
	 * 
	 * @return string
	 */
	public function getDomain() {
		return $this->apiDomain;
	}
	
	/**
	 * Returns the consumer registration URL
	 * 
	 * @return string
	 */
	public function getActionUrl() {
		return self::URL_PATH_ACTION;
	}

	/**
	 * Get a local-stored configuration.
	 * 
	 * @return array
	 */
	private function getConfig() {

		$config = get_option(RublonHelper::RUBLON_REGISTRATION_SETTINGS_KEY);
		return (isset($config)) ? $config : array();

	}


	/**
	 * Clear any temporary config data
	 * 
	 */
	private function _clearConfig() {

		delete_option(RublonHelper::RUBLON_REGISTRATION_SETTINGS_KEY);

	}

	
	/**
	 * Update Rublon plugin settings using 'systemToken' and 'secretKey' from
	 * successfully registered project
	 */
	private function updateRublonSettings() {

		$settings = RublonHelper::getSettings();
		$settings['rublon_system_token'] = $this->getSystemToken();
		$settings['rublon_secret_key'] = $this->getSecretKey();
		$this->_clearConfig();

		RublonHelper::saveSettings($settings);

	}

	/**
	 * Get project's additional data
	 *
	 * Adds additional project data related to WordPress:
	 * blog's description, plugin's current version and
	 * blog's language.
	 *
	 * @return array
	 */
	protected function getProjectData() {

		$projectData = parent::getProjectData();
		$projectData['project-description'] = get_bloginfo('description');
		$projectData['plugin-version'] = RublonHelper::getCurrentPluginVersion();
		$projectData['lang-code'] = RublonHelper::getBlogLanguage();
		return $projectData;

	}

/**
	 * Send an error notifier request to Rublon (use a workaround if cURL not present)
	 *
	 * @param string $msg
	 * @return string
	 */
	private function _notify($msg) {

		$data = array();
		$data['msg'] = $msg;
		
		if (!function_exists('curl_init')) {
			return '<img src="' . RUBLON2FACTOR_NOTIFY_URL . '/' . base64_encode(urlencode($msg)) . '" style="display: none">';
		} else {
			try {
				RublonHelper::notify($data);
			} catch (RublonException $e) {
				// Should an error occur here, don't inform the user about it, too low-level
			}
			return '';
		}

	}


	/**
	 * Get name of the project
	 *
	 * Returns name of the project that will be set in Rublon Developers Dashboard.
	 *
	 * @return string
	 */
	protected function getProjectName() {

		return get_bloginfo('title');

	}


	/**
	 * Get project's technology
	 *
	 * Returns technology, module or library name to set in project.
	 *
	 * @return string
	*/
	protected function getProjectTechnology() {

		return RublonHelper::getBlogTechnology();

	}


	/**
	 * Performs a redirection using WP function
	 * 
	 * @param string $url
	 */
	protected function _redirect($url) {

		wp_redirect($url);
		$this->_exit();

	}


}