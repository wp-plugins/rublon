<?php

require_once dirname(__FILE__) . '/../RublonConsumerRegistration/RublonConsumerRegistrationTemplate.php';

class RublonConsumerRegistrationWordPress extends RublonConsumerRegistrationTemplate {


	protected function finalSuccess() {

		parent::finalSuccess();
		
		$this->updateRublonSettings();

		$updateMessage = 'PLUGIN_REGISTERED';
		RublonHelper::setMessage($updateMessage, 'updated', 'CR');
		RublonCookies::setAuthCookie();

		$pluginMeta = RublonHelper::preparePluginMeta();
		$pluginMeta['action'] = 'activation';
		RublonHelper::pluginHistoryRequest($pluginMeta);
		
		$this->_redirect(admin_url(RublonHelper::RUBLON_PAGE));

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
		$errorCode = 'API_ERROR';
		if (!empty($msg)) {
			if (stripos($msg, 'ERROR_CODE:') !== false) {
				$errorCode = str_replace('ERROR_CODE: ', '', $msg);
				$notifierMessage .= __('Rublon error code: ', 'rublon') . '<strong>' . $errorCode . '</strong>';
			} else {
				$notifierMessage .= 'Rublon error message: [' . $msg . ']';
			}
		}
		RublonHelper::setMessage($errorCode, 'error', 'CR');
		
		// send issue notify
		echo $this->_notify($notifierMessage);
		
		$this->_redirect(admin_url(RublonHelper::RUBLON_PAGE));

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

		return RublonHelper::getActionURL('callback');

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
	
		if (!function_exists('curl_init')) {
			return '<img src="' . RublonHelper::RUBLON_API_DOMAIN . RublonHelper::RUBLON_NOTIFY_URL_PATH . '/' . base64_encode(urlencode($msg)) . '" style="display: none">';
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

		$settings = get_option(RublonHelper::RUBLON_REGISTRATION_SETTINGS_KEY);
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
		$projectData['lang-code'] = RublonHelper::getBlogLanguage();
		$current_user = wp_get_current_user();
		$email = RublonHelper::getUserEmail($current_user);
		$projectData['project-owner-email'] = $email;
		$projectData['project-owner-email-hash'] = self::hash($email);
		return $projectData;

	}


	/**
	 * Initialize consumer registration
	 * 
	 * WordPress needs its own initialize method
	 * to print out a "busy" indicator along with the
	 * registration form.
	 * 
	 * @throws UserUnauthorized_RublonConsumerException
	 */
	public function initForWordPress() {

		if ($this->canUserActivate()) {
			$tempKey = RublonSignatureWrapper::generateRandomString(self::SECRET_KEY_LENGTH);
			$this->saveInitialParameters($tempKey, time());
			$regForm = $this->getRegistrationForm();
			$pageTemplate = RublonHelper::pageTemplate();
			$busyPageContent = RublonHelper::busyPageContentTemplate();
			$pageContent = sprintf($busyPageContent,
				'',
				__('Rublon is being configured.', 'rublon') . '<br />' . __('This will only take a moment.', 'rublon'),
				$regForm
			);
			$styles = RublonHelper::busyPageStyles(true);
			$page = sprintf($pageTemplate,
				__('Rublon Configuration', 'rublon'),
				$styles,
				$pageContent
			);
			echo $page;
			exit;
		} else {
			throw new UserUnauthorized_RublonConsumerException;
		}

	}


}
