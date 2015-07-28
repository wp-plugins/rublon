<?php

require_once dirname(__FILE__) . '/../RublonConsumerRegistration/RublonConsumerRegistrationTemplate.php';

class RublonConsumerRegistrationWordPress extends RublonConsumerRegistrationTemplate {

	const TEMPLATE_FORM_POST = '<form action="%s" method="POST" id="rublon-consumer-registration">
			%s
		</form>';

	protected function finalSuccess() {

		parent::finalSuccess();
		
		$this->updateRublonSettings();

		$updateMessage = 'PLUGIN_REGISTERED';
		RublonHelper::setMessage($updateMessage, 'updated', 'CR');
		RublonCookies::setAuthCookie();

		$pluginMeta = RublonHelper::preparePluginMeta();
		$pluginMeta['action'] = 'activation';
		RublonHelper::pluginHistoryRequest($pluginMeta);

		$other_settings = RublonHelper::getSettings('other');
		if (!empty($other_settings['newsletter_signup'])) {
			foreach ($other_settings['newsletter_signup'] as $email) {
				$rublon_req = new RublonRequests();
				$rublon_req->subscribeToNewsletter($email);
			}
			unset($other_settings['newsletter_signup']);
			RublonHelper::saveSettings($other_settings, 'other');
		}
		
		// Save project owner information
		RublonHelper::saveProjectOwner();
		
		$this->_redirect(admin_url(RublonHelper::WP_RUBLON_PAGE));

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
	
		RublonHelper::saveSettings($settings, 'main');

		do_action('rublon_save_settings', $settings, 'main');

	}


	/**
	 * Clear any temporary config data
	 * 
	 */
	private function _clearConfig() {

		delete_option(RublonHelper::RUBLON_REGISTRATION_SETTINGS_KEY);

	}


	protected function finalError($msg = NULL) {

		parent::finalError($msg);
		
		if (!$msg || $msg == self::DEVELOPERS_ERROR) {
			$msg = RublonHelper::uriGet('error_msg');
		}
		
		$notifierMessage = 'Consumer registration error.<br /><br />';
		$msg_data = explode(':', $msg);
		$errorCode = (!empty($msg_data[1]) ? $msg_data[1] : '');
		$errorMessage = (!empty($msg_data[0]) ? $msg_data[0] : '');
		
		if (!empty($msg)) {
			if (stripos($msg, 'ERROR_CODE:') !== false) {
				$errorCode = str_replace('ERROR_CODE: ', '', $msg);
				$notifierMessage .= __('Rublon error code: ', 'rublon') . '<strong>' . $errorCode . '</strong>';
			} else {
				$notifierMessage .= 'Rublon error message: [' . $msg . ']';
			}
		}
		RublonHelper::setMessage($errorCode, 'error', 'CR', false, strtolower($errorCode));
		
		// send issue notify
		echo $this->_notify($notifierMessage);
		
		$this->_redirect(admin_url(RublonHelper::WP_RUBLON_PAGE));

	}


	protected function getSystemToken() {

		$config = $this->getConfig();		
		return (isset($config['systemToken']) ? $config['systemToken'] : NULL);

	}


	protected function saveSystemToken($systemToken) {

		$config = $this->getConfig();
		$config['systemToken'] = $systemToken;
		return $this->_saveConfig($config);

	}


	protected function getSecretKey() {

		$config = $this->getConfig();
		return (isset($config['secretKey']) ? $config['secretKey'] : NULL);

	}


	protected function saveSecretKey($secretKey) {

		$config = $this->getConfig();
		$config['secretKey'] = $secretKey;
		return $this->_saveConfig($config);

	}


	protected function getTempKey() {

		$config = $this->getConfig();
		return (isset($config['tempKey']) ? $config['tempKey'] : NULL);

	}


	protected function saveInitialParameters($tempKey, $startTime) {

		$config = $this->getConfig();
		$config['tempKey'] = $tempKey;
		$config['startTime'] = $startTime;
		return $this->_saveConfig($config);

	}


	protected function getStartTime() {

		$config = $this->getConfig();
		return (isset($config['startTime']) ? $config['startTime'] : NULL);

	}


	protected function getCommunicationUrl() {

		return RublonHelper::getActionURL('register');

	}


	protected function getProjectUrl() {

		return trailingslashit(site_url());

	}
	
	protected function getCallbackUrl() {

		return RublonHelper::getLoginURL('callback');

	}


	public function getAPIDomain() {

		return RublonHelper::RUBLON_REGISTRATION_DOMAIN;

	}


	protected function getProjectName() {

		return get_bloginfo('title');

	}


	protected function getProjectTechnology() {

		return RublonHelper::getBlogTechnology();

	}


	protected function getUserId() {

		$current_user = wp_get_current_user();
		return RublonHelper::getUserId($current_user);

	}


	protected function getUserEmail() {

		$current_user = wp_get_current_user();
		return RublonHelper::getUserEmail($current_user); 

	}


	protected function getRublon() {

		if (empty($this->rublon)) {
			$this->rublon = new Rublon2FactorWordPress(null, $this->getTempKey());
		}
		return $this->rublon;

	}


	/**
	 * Performs a redirection using a WP function
	 *
	 * @param string $url
	 */
	protected function _redirect($url) {
	
		wp_redirect($url);
		$this->_exit();
	
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
		$data['request_uri'] = $_SERVER['REQUEST_URI'];
	
		try {
			RublonHelper::notify($data, array('message-type' => RublonHelper::RUBLON_NOTIFY_TYPE_ERROR));
		} catch (Exception $e) {
			// Do nothing.
		}
		return '';
	
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
	 * Save a given data in a local-stored configuration.
	 *
	 * @param array $data
	 * @return bool
	 */
	protected function _saveConfig($data) {

		$settings = $data;
		update_option(RublonHelper::RUBLON_REGISTRATION_SETTINGS_KEY, $settings);
		return (isset($settings));

	}


	/**
	 * Returns the consumer registration URL
	 *
	 * @return string
	 */
	public function getConsumerActionURL() {

		return self::URL_PATH_ACTION;

	}


	protected function getProjectData() {

		$projectData = parent::getProjectData();
		$projectData['project-description'] = get_bloginfo('description');
		$projectData['plugin-version'] = RublonHelper::getCurrentPluginVersion();
		$projectData['lang'] = RublonHelper::getBlogLanguage();
		$current_user = wp_get_current_user();
		$email = RublonHelper::getUserEmail($current_user);
		$projectData['project-owner-email'] = $email;
		$projectData['project-owner-email-hash'] = self::hash($email);
		return $projectData;

	}


	public function retrieveRegistrationForm() {

		$temp_key = RublonSignatureWrapper::generateRandomString(self::SECRET_KEY_LENGTH);
		$this->saveInitialParameters($temp_key, time());
		$reg_form = $this->getRegistrationForm();
		return $reg_form;

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


}
