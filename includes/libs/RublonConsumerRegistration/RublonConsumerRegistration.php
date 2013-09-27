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

		$currentUser = wp_get_current_user();
		$adminProfileId = $this->getAdminProfileId();
		if (!empty($adminProfileId) && !Rublon2FactorHelper::isUserSecured($currentUser))
			$success = Rublon2FactorHelper::connectRublon2Factor($currentUser, $adminProfileId);

		if ($success) {
			Rublon2FactorHelper::setMessage(__('Thank you! Now all of your users can protect their accounts with Rublon.', 'rublon2factor'), 'updated');
		} else {
			Rublon2FactorHelper::setMessage(sprintf(__('Thank you! Now all of your users can protect their accounts with Rublon. However, there has been a problem with protecting your account. Go to <a href="%s">Rublon page</a> in order to protect your account.', 'rublon2factor'), admin_url('admin.php?page=rublon')), 'error');
		}

		$pluginMeta = Rublon2FactorHelper::preparePluginMeta();
		$pluginMeta['action'] = 'activation';
		Rublon2FactorHelper::pluginHistoryRequest($pluginMeta);
		
		$this->_redirect('wp-admin/admin.php?page=rublon');
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

		$errorMessage = __('Rublon activation failed. Please try again. Should the error occur again, contact us at <a href="mailto:support@rublon.com">support@rublon.com</a>.', 'rublon2factor');
		$notifierMessage = $errorMessage;
		if (!empty($msg)) {
			if (stripos($msg, 'ERROR_CODE:') !== false) {
				Rublon2FactorHelper::setMessage($errorMessage, 'error');
				$errorCode = str_replace('ERROR_CODE: ', '', $msg);
				$errorCodeMessage = $this->handleErrorCode($errorCode);
				if ($errorCodeMessage) {
					Rublon2FactorHelper::setMessage($errorCodeMessage, 'error');
					$notifierMessage .= '<br />' . $errorCodeMessage;
				} else {
					Rublon2FactorHelper::setMessage(__('Rublon error code: ', 'rublon2factor') . '<strong>' . $errorCode . '</strong>', 'error');
					$notifierMessage .= '<br />' . $errorCode;
				}
			} else {
				$notifierMessage = $errorMessage . '<br />Rublon error message: [' . $msg . ']';
				Rublon2FactorHelper::setMessage($notifierMessage, 'error');
			}
		} else {
			Rublon2FactorHelper::setMessage($errorMessage, 'error');
		}
		
		// send issue notify
		Rublon2FactorHelper::notify(array('msg' => $notifierMessage));
		
		$this->_redirect('wp-admin/admin.php?page=rublon');
	}


	protected function handleErrorCode($errorCode = '') {

		switch ($errorCode) {
			case 'PLUGIN_OUTDATED':
				return sprintf(__('The version of Rublon for Wordpress that you are trying to activate is outdated. Please go to the <a href="%s">Plugins</a> page and update it to the newest version or', 'rublon2factor'), admin_url('plugins.php'))
					. '<a href="' . esc_attr(wp_nonce_url( self_admin_url('update.php?action=upgrade-plugin&plugin=') . plugin_basename(RUBLON2FACTOR_PLUGIN_PATH), 'upgrade-plugin_' . plugin_basename(RUBLON2FACTOR_PLUGIN_PATH))) . '">'
					. ' <strong>' . __('update now', 'rublon2factor') . '</strong></a>.'; 
				break;
			default:
				return null; 
		}

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
		return $this->actionUrl;
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
	private function updateRublonSettings() {

		$settings = Rublon2FactorHelper::getSettings();
		$settings['rublon_system_token'] = $this->getSystemToken();
		$settings['rublon_secret_key'] = $this->getSecretKey();

		Rublon2FactorHelper::saveSettings($settings);

	}

	/**
	 * Get project's additional data
	 *
	 * Return the blog name as project name,
	 * "wordpress3" as project technology and plugin's
	 * version.
	 *
	 * @return string
	 */
	protected function getProjectData() {

		return json_encode(array(
				'project-name' => get_bloginfo('title'),
				'project-technology' => 'wordpress3',
				'plugin-version' => Rublon2FactorHelper::getCurrentPluginVersion(),
		));

	}

}