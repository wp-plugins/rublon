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
		$this->updateRublonSettings();
		
		Rublon2FactorHelper::setMessage(__('Rublon has been activated. Now go to your <a href="profile.php">profile</a> in order to secure your account with Rublon.', 'rublon2factor'));
		Rublon2FactorHelper::setMessageType('updated');
		
		$this->_redirect('wp-admin/options-general.php?page=rublon');
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

		Rublon2FactorHelper::setMessage(__('Rublon activation failed. In order to get "System Token" and "Secret Key" add your website manually in the Developer Dashboard at <a href="https://developers.rublon.com/" target="_blank">developers.rublon.com</a> or contact us at <a href="mailto:support@rublon.com">support@rublon.com</a>.', 'rublon2factor'));
		Rublon2FactorHelper::setMessageType('error');

		$this->_redirect('wp-admin/options-general.php?page=rublon');
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
		return trailingslashit(site_url());
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
		return trailingslashit(site_url());
	}
	
	/**
	 * Save a given data in a local-stored configuration.
	 * 
	 * @param array $data
	 * @return bool
	 */
	protected function _saveConfig($data) {
		$settings = get_option(Rublon2FactorHelper::RUBLON_REGISTRATION_SETTINGS_KEY);
		$settings = $data;
		update_option(Rublon2FactorHelper::RUBLON_REGISTRATION_SETTINGS_KEY, $settings);
		return (isset($settings));
	}

	/**
	 * Set API domain for testing
	 */
	public function setDomain($domain) {
		$this->apiDomain = $domain;
	}

	/**
	 * Get a local-stored configuration.
	 * 
	 * @return array
	 */
	private function getConfig()
	{
		$config = get_option(Rublon2FactorHelper::RUBLON_REGISTRATION_SETTINGS_KEY);
		return (isset($config)) ? $config : array();
	}
	
	/**
	 * Update Rublon plugin settings using 'systemToken' and 'secretKey' from
	 * successfully registered project
	 */
	private function updateRublonSettings()
	{
		$settings = get_option(Rublon2FactorHelper::RUBLON_SETTINGS_KEY);
		$settings['rublon_system_token'] = $this->getSystemToken();
		$settings['rublon_secret_key'] = $this->getSecretKey();	
		update_option(Rublon2FactorHelper::RUBLON_SETTINGS_KEY, $settings);
	}
}