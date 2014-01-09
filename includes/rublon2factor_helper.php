<?php
/**
 * Additional helper functions for Rublon2Factor module
 *
 * @package   rublon2factor\includes
 * @author     Rublon Developers http://www.rublon.com
 * @copyright  Rublon Developers http://www.rublon.com
 * @license    http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */

/**
 * RublonHelper class
 *
 * It provides helper functionalities for Rublon2Factor module.
 *
 */

class RublonHelper {


	const RUBLON_API_DOMAIN = 'https://code.rublon.com';

	const RUBLON_SETTINGS_KEY = 'rublon2factor_settings';
	const RUBLON_REGISTRATION_SETTINGS_KEY = 'rublon2factor_registration_settings';
	const RUBLON_ADDITIONAL_SETTINGS_KEY = 'rublon2factor_additional_settings';
	const RUBLON_META_PROFILE_ID = 'rublon_profile_id';
	const RUBLON_ACTION_PREFIX = 'rublon_';
	const RUBLON_AUTH_TIME = 5;
	const RUBLON_PAGE = 'admin.php?page=rublon';


	/**
	 * An instance of the RublonService2Factor class
	 * 
	 * @var RublonService2Factor
	 */
	static private $service;


	/**
	 * Plugin cookies
	 *
	 * @var array
	 */
	static public $cookies;


	/**
	 * Nonce used in the validation of the plugin registration process
	 * 
	 * @var string
	 */
	static private $nonce;


	/**
	 * Load i18n files and check for possible plugin update
	 * 
	 */
	static public function init() {

		// Initialize localization
		if (function_exists('load_plugin_textdomain')) {
			load_plugin_textdomain('rublon2factor', false, RUBLON2FACTOR_BASE_PATH . '/includes/languages/');
		}

		// check for a possible update
		self::_updateChecker();

		// prevent XML-RPC access if it was disabled in plugin settings 
		self::_checkXMLRPCStatus();

	}


	/**
	 * Transfer plugin messages from cookie to a private field
	 * 
	 */
	static public function cookieTransfer() {

		$cookies = array();
		$messages = RublonCookies::getMessagesFromCookie();
		if (!empty($messages))
			$cookies['messages'] = $messages;
		self::$cookies = $cookies;

	}


	/**
	 * Transfer plugin messages back to the cookie
	 * 
	 */
	static public function cookieTransferBack() {

		if (!empty(self::$cookies['messages'])) {
			RublonCookies::storeAllMessagesInCookie(self::$cookies['messages']);
			unset(self::$cookies['messages']);
		}

	}


	/**
	 * Check for any Rublon actions in the URI
	 * 
	 */
	static public function checkForActions() {

		$rublonAction = self::uriGet('rublon');
		if (isset($rublonAction)) {
			switch (strtolower($rublonAction)) {
				case 'register':
					$rublonRegAction = self::uriGet('action');
					if (isset($rublonRegAction)) {
						self::consumerRegistrationAction($rublonRegAction);
					}
					break;
				case 'callback':
					$accessToken = self::uriGet('token');
					$responseState = self::uriGet('state');
					if (isset($accessToken) && isset($responseState)) {
						self::handleCallback();
					}
					break;
				case 'init-registration':
					$nonce = self::uriGet('rublon_nonce');
					$nonceCookie = RublonCookies::getNonceFromCookie();
					if (!empty($nonce) && wp_verify_nonce($nonce, 'rublon=init-registration') && $nonce == $nonceCookie) {
						self::consumerRegistrationAction('initialize');
					} else {
						self::setMessage('NONCE_VERIFICATION_FAILED', 'error', 'CR');
						wp_redirect(admin_url(self::RUBLON_PAGE));
					}
					break;
			}
			exit;
		} else {
			$settings = self::getSettings();
			if (self::isPluginRegistered()) {
				$consumer = new RublonConsumer($settings['rublon_system_token'], $settings['rublon_secret_key']);
				$consumer->setDomain(self::getAPIDomain());
				$consumer->setLang(self::getBlogLanguage());
				$consumer->setTechnology(self::getBlogTechnology());
				self::$service = new RublonService2Factor($consumer);
			} 
		}

	}


	/**
	 * Handle the Rublon callback
	 * 
	 */
	static public function handleCallback()	{

		$callback = new Rublon2FactorCallback();
		$callback->run();

	}


	/**
	 * Perform Rublon2Factor authentication
	 * 
	 */
	static public function authenticateWithRublon($user) {

		if (!empty(self::$service)) {
			$rublonProfileId = self::getUserProfileId($user);
			$authParams = new RublonAuthParams(self::$service);
			$authParams->setConsumerParam('wp_user', self::getUserId($user));
			$here = '[[CUSTOM]]' . urlencode($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
			$authParams->setConsumerParam('customURIParam', $here);
			self::$service->initAuthorization($rublonProfileId, $authParams);
		}

	}


	/**
	 * Add Rublon secure account button to the page code
	 * 
	 * @param string $page Optional parameter setting the return page
	 */
	static public function addSecureAccountButton($page = 'profile') {

		if (!empty(self::$service)) {
			$currentUser = wp_get_current_user();
			$button = self::$service->createButtonEnable(__('Protect your account', 'rublon2factor'));
			$button->getAuthParams()->setConsumerParam('customURIParam', $page);
			$button->getAuthParams()->setConsumerParam('wp_user', self::getUserId($currentUser));
			echo $button;
		}

	}


	/**
	 * Add button for disabling Rublon protection button to the page code.
	 * 
	 * @param string $page Optional parameter setting the return page
	 */
	static public function addDisableAccountSecurityButton($page = 'profile') {

		if (!empty(self::$service)) {
			$currentUser = wp_get_current_user();
			$rublonProfileId = self::getUserProfileId($currentUser);
			$label = __('Disable account protection', 'rublon2factor');
			$button = self::$service->createButtonDisable($label, $rublonProfileId);
			$button->getAuthParams()->setConsumerParam('customURIParam', $page);
			$button->getAuthParams()->setConsumerParam('wp_user', self::getUserId($currentUser));
			echo $button;
		}

	}


	/**
	 * Return the plugin settings
	 * 
	 * @param string $group Settings group
	 */
	static public function getSettings($group = '') {

		switch ($group) {
			case 'additional':
				$key = self::RUBLON_ADDITIONAL_SETTINGS_KEY;
				break;
			default:
				$key = self::RUBLON_SETTINGS_KEY;
		}
		$settings = get_option($key);
		if (!$settings)
			$settings = array();
		return $settings;

	}


	/**
	 * Save the plugin settings
	 * 
	 * @param array $settings Settings to be saved
	 * @param string $group Settings group 
	 */
	static public function saveSettings($settings, $group = '') {

		switch ($group) {
			case 'additional':
				$key = self::RUBLON_ADDITIONAL_SETTINGS_KEY;
				break;
			default:
				$key = self::RUBLON_SETTINGS_KEY;
		}
		update_option($key, $settings);

	}


	/**
	 * Checks if the plugin has ever been activated
	 * 
	 * @return boolean
	 */
	static public function wasPluginEverActivated() {

		$settings = self::getSettings();
		return !empty($settings['plugin-activated']);

	}


	/**
	 * Registers the fact of the plugin's activation
	 * 
	 */
	static public function registerPluginActivation() {

		$settings = self::getSettings();
		$settings['plugin-activated'] = true;
		self::saveSettings($settings);

	}


	/**
	 * Retrieve message codes from helper and prepare them for viewing
	 * 
	 * @return array|null
	 */
	static public function getMessages() {

		if (!empty(self::$cookies['messages'])) {
			$messages = self::$cookies['messages'];
			unset(self::$cookies['messages']);
			return self::_explainMessages($messages);
		}
		return null;

	}


	/**
	 * Store a message in the plugin cookies
	 *
	 * @param string $code Message code
	 * @param string $type Message type
	 * @param string $origin Message origin
	 */
	static public function setMessage($code, $type, $origin) {
	
		$msg = $type . '__' . $origin . '__' . $code;
		RublonCookies::storeMessageInCookie($msg);

	}


	/**
	 * Transform message codes into messages themselves
	 * 
	 * @param array $messages Message "headers" retrieved from plugin cookie
	 * @return array
	 */
	static private function _explainMessages($messages) {

		$result = array();
		foreach ($messages as $message) {
			$msg = explode('__', $message);
			$msgType = $msg[0];
			$msgOrigin = $msg[1];
			$msgCode = $msg[2];
			if ($msgType == 'error') {
				switch ($msgOrigin) {
					case 'RC':
						$errorMessage = __('There was a problem during the authentication process.', 'rublon2factor');
						break;
					case 'CR':
						$errorMessage = __('Rublon activation failed. Please try again. Should the error occur again, contact us at <a href="mailto:support@rublon.com">support@rublon.com</a>.', 'rublon2factor');
						break;
				}
				$errorCode = $msgOrigin . '_' . $msgCode;
				switch ($errorCode) {
					case 'RC_ALREADY_PROTECTED':
						$errorMessage = __('You cannot protect an account already protected by Rublon.', 'rublon2factor');
						break;
					case 'RC_CANNOT_PROTECT_ACCOUNT':
						$errorMessage = __('Unable to protect your account with Rublon.', 'rublon2factor');
						break;
					case 'RC_CANNOT_DISABLE_ACCOUNT_PROTECTION':
						$errorMessage = __('Unable to disable Rublon protection.', 'rublon2factor');
						break;
					case 'CR_PLUGIN_OUTDATED':
						$errorMessage = sprintf(__('The version of Rublon for Wordpress that you are trying to activate is outdated. Please go to the <a href="%s">Plugins</a> page and update it to the newest version or', 'rublon2factor'), admin_url('plugins.php'))
						. '<a href="' . esc_attr(wp_nonce_url( self_admin_url('update.php?action=upgrade-plugin&plugin=') . plugin_basename(RUBLON2FACTOR_PLUGIN_PATH), 'upgrade-plugin_' . plugin_basename(RUBLON2FACTOR_PLUGIN_PATH))) . '">'
								. ' <strong>' . __('update now', 'rublon2factor') . '</strong></a>.';
						break;
					case 'CR_PLUGIN_REGISTERED_NO_PROTECTION':
						$errorMessage = sprintf(__('Thank you! Now all of your users can protect their accounts with Rublon. However, there has been a problem with protecting your account. Go to <a href="%s">Rublon page</a> in order to protect your account.', 'rublon2factor'), admin_url(self::RUBLON_PAGE));
						break;
					case 'CR_SYSTEM_TOKEN_INVALID_RESPONSE_TIMESTAMP':
					case 'CR_INVALID_RESPONSE_TIMESTAMP':
					case 'RC_CODE_TIMESTAMP_ERROR':
						$errorMessage = __('Your server\'s time seems out of sync. Please check that it is properly synchronized - Rublon won\'t be able to verify your website\'s security otherwise.', 'rublon2factor');
						break;
				}
				$result[] = array('message' => $errorMessage, 'type' => $msgType);
				$result[] = array('message' => __('Rublon error code: ', 'rublon2factor') . '<strong>' . $errorCode . '</strong>', 'type' => $msgType);
			} elseif ($msgType == 'updated') {
				$updatedMessage = '';
				$updatedCode = $msgOrigin . '_' . $msgCode;
				switch ($updatedCode) {
					case 'RC_ACCOUNT_PROTECTED':
						$updatedMessage = __('Your account is now protected by Rublon.', 'rublon2factor');
						break;
					case 'RC_PROTECTION_DISABLED':
						$updatedMessage = __('Rublon protection has been disabled. You are now protected by a password only, which may result in unauthorized access to your account. We strongly encourage you to protect your account with Rublon.', 'rublon2factor');
						break;
					case 'CR_PLUGIN_REGISTERED':
						$updatedMessage = __('Thank you! Now all of your users can protect their accounts with Rublon.', 'rublon2factor');
						break;
				}
				$result[] = array('message' => $updatedMessage, 'type' => $msgType);
			}
		}
		return $result;

	}


	/**
	 * Check if plugin is registered
	 *
	 * @return boolean
	 */
	static public function isPluginRegistered() {

		$settings = self::getSettings();
		return (!empty($settings) && !empty($settings['rublon_system_token']) && !empty($settings['rublon_secret_key']));

	}


	/**
	 * Retrieves plugin's version from the settings
	 *
	 * @return string
	 */
	static private function _getSavedPluginVersion() {

		$settings = self::getSettings();
		return (!empty($settings) && !empty($settings['rublon_plugin_version'])) ? $settings['rublon_plugin_version'] : '';

	}


	/**
	 * Retrieve plugin's version from the plugin's file
	 * 
	 * @return string
	 */
	static public function getCurrentPluginVersion() {

		if (!function_exists('get_plugin_data'))
			require_once(ABSPATH . 'wp-admin/includes/plugin.php');
		$pluginData = get_plugin_data(RUBLON2FACTOR_PLUGIN_PATH);
		return (!empty($pluginData) && !empty($pluginData['Version'])) ? $pluginData['Version'] : '';
		

	}


	/**
	 * Update the rublon_plugin_version field in the plugin's options
	 * 
	 * @param string $version Plugin's current version
	 */
	static private function _setPluginVersion($version) {

		$settings = self::getSettings();
		$settings['rublon_plugin_version'] = $version;
		self::saveSettings($settings);

	}


	/**
	 * Updates rublon_profile_id for a given user, to turn on second authentication factor.
	 *
	 * @param WP_User $user WordPress user object
	 * @param int $rublonProfileId User's Rublon profile ID
	 * @return int|boolean
	 */
	static public function connectRublon2Factor($user, $rublonProfileId) {

		return add_user_meta(self::getUserId($user), self::RUBLON_META_PROFILE_ID, $rublonProfileId, true);

	}


	/**
	 * Updates rublon_profile_id for a given user, to turn off second authentication factor.
	 *
	 * @param int $user
	 * @return boolean
	 */
	static public function disconnectRublon2Factor($user) {

		$hasProfileId = get_user_meta(self::getUserId($user), self::RUBLON_META_PROFILE_ID, true);
		if ($hasProfileId)
			return delete_user_meta(self::getUserId($user), self::RUBLON_META_PROFILE_ID);
		else
			return false;

	}


	/**
	 * Check if the current user's account is protected by Rublon
	 *
	 * @return boolean
	 */
	static public function isCurrentUserSecured() {

		$currentUser = wp_get_current_user();
		return self::isUserSecured($currentUser);

	}


	/**
	 * Check if the given user is protected by Rublon
	 *
	 * @param WP_User $user
	 * @return boolean
	 */
	static public function isUserSecured($user) {

		$rublonProfileId = get_user_meta(self::getUserId($user), self::RUBLON_META_PROFILE_ID, true);
		return self::isPluginRegistered() && !empty($rublonProfileId);

	}


	/**
	 * Check if a user has been authenticated by Rublon
	 * 
	 * @param WP_User $user
	 */
	static public function isUserAuthenticated($user) {

		return RublonCookies::isAuthCookieSet($user);

	}


	/**
	 * Retrieve a user's Rublon profile ID from user meta
	 * 
	 * @param unknown $user
	 */
	static public function getUserProfileId($user) {

		if (!empty($user))
			return get_user_meta(self::getUserId($user), self::RUBLON_META_PROFILE_ID, true);

	}


	/**
	 * Perform a consumer registration action
	 * 
	 * @param string $action
	 */
	static public function consumerRegistrationAction($action) {

		$consumerRegistration = new RublonConsumerRegistration();
		$consumerRegistration->action($action);

	}

	/**
	 * Prepare url pieces needed for the plugin history request
	 * 
	 * @return array
	 */
	static public function getConsumerRegistrationData() {

		$consumerRegistration = new RublonConsumerRegistration();
		return array(
				'url' => $consumerRegistration->getDomain(),
				'action' => $consumerRegistration->getActionUrl()
		);

	}


	/**
	 * Send a request with plugin's history to Rublon servers
	 * 
	 * @param array $data Plugin's history data
	 */
	static public function pluginHistoryRequest($data) {

		$settings = self::getSettings();
		$consumer = new RublonConsumer($settings['rublon_system_token'], $settings['rublon_secret_key']);
		$service = new RublonService2Factor($consumer);
		$request = new RublonRequest($service);
		$data['systemToken'] = $settings['rublon_system_token'];
		$data['lib-version'] = $consumer->getVersion();
		$consumerRegistrationData = self::getConsumerRegistrationData();
		$url = $consumerRegistrationData['url'] . $consumerRegistrationData['action'] . '/add_history';
		$response = $request->setRequestParams($url, $data)->getRawResponse();

		try {
			$response = json_decode($response, true);
		} catch (Exception $e) {
			$response = null;
		}

		if (!empty($response['status']) && $response['status'] == 'OK' && !empty($response['historyAdded']))
			return true;

		return false;

	}


	/**
	 * Send an error report via cURL
	 * 
	 * @param string $msg Error info
	 */
	static function notify($msg) {

		$msg['bloginfo'] = get_bloginfo();
		$pluginMeta = self::preparePluginMeta();
		if (!empty($pluginMeta['meta']))
			$msg['plugin-info'] = $pluginMeta['meta'];
		$msg['phpinfo'] = self::_info();				
		
		$ch = curl_init(RUBLON2FACTOR_NOTIFY_URL);
		$headers = array(
			"Content-Type: application/json; charset=utf-8",
			"Accept: application/json, text/javascript, */*; q=0.01"
		);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		
		curl_setopt($ch, CURLOPT_POST, true);							
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($msg));
					
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Rublon for WordPress');

		// Execute request
		curl_exec($ch);		
		if (curl_error($ch)) {
			throw new RublonException("Notifier: " . curl_error($ch), RublonException::CODE_CURL_ERROR);
		}		
		curl_close($ch);
	}


	/**
	 * Prepare server's PHP info for error reporting
	 * 
	 * @return array
	 */
	static private function _info() {
		ob_start();
		phpinfo();
		$phpinfo = array('phpinfo' => array());
		if(preg_match_all('#(?:<h2>(?:<a name=".*?">)?(.*?)(?:</a>)?</h2>)|(?:<tr(?: class=".*?")?><t[hd](?: class=".*?")?>(.*?)\s*</t[hd]>(?:<t[hd](?: class=".*?")?>(.*?)\s*</t[hd]>(?:<t[hd](?: class=".*?")?>(.*?)\s*</t[hd]>)?)?</tr>)#s', ob_get_clean(), $matches, PREG_SET_ORDER))
		    foreach($matches as $match)
		        if(strlen($match[1]))
		            $phpinfo[$match[1]] = array();
		        elseif(isset($match[3]))
		            $phpinfo[end(array_keys($phpinfo))][$match[2]] = isset($match[4]) ? array($match[3], $match[4]) : $match[3];
		        else
		            $phpinfo[end(array_keys($phpinfo))][] = $match[2];
	    return $phpinfo;		            
	}


	/**
	 * Remove any scheme modifications from older versions and migrate data to user meta
	 * 
	 */
	static private function _dbMigrate() {

		global $wpdb;

		$user_fields = $wpdb->get_col('SHOW COLUMNS FROM ' . $wpdb->users);
		if (in_array('rublon_profile_id', $user_fields)) {
			$all_users = get_users();
			foreach ($all_users as $user) {
				if (!empty($user->rublon_profile_id)) {
					add_user_meta(self::getUserId($user), self::RUBLON_META_PROFILE_ID, $user->rublon_profile_id, true);
				}
			}
			$db_error = $wpdb->query('ALTER TABLE ' . $wpdb->users . ' DROP COLUMN `rublon_profile_id`') === false;
			if ($db_error) {
				deactivate_plugins(plugin_basename(RUBLON2FACTOR_PLUGIN_PATH), true);
				_e('Plugin requires database modification but you do not have permission to do it.', 'rublon2factor');
				exit;
			}
		}

	}


	/**
	 * Perform any necessary actions on plugin update
	 * 
	 * @param string $from Version the plugin's being updated from
	 * @param string $to Plugin's Version the plugin's being updated to
	 */
	static private function _performUpdate($from, $to) {

		// migrate old database entries into user meta
		self::_dbMigrate();

		// make sure that Rublon is run before other plugins
		self::meFirst();

		// send update info to Rublon
		if (self::isPluginRegistered()) {
			$pluginMeta = self::preparePluginMeta();
			$pluginMeta['action'] = 'update';
			$pluginMeta['meta']['previous-version'] = $from;
			self::pluginHistoryRequest($pluginMeta);
			if (!self::wasPluginEverActivated())
				self::registerPluginActivation();
		}

		// remove any deprecated cookies
		RublonCookies::cookieCleanup(array('return_url'));

		$user = wp_get_current_user();
		if (self::isPluginRegistered() && is_user_logged_in() && is_admin() && self::isUserSecured($user) && !self::isUserAuthenticated($user)) {
 			RublonCookies::setAuthCookie($user);
		}

		// disable XML-RPC by default
		$additionalSettings = self::getSettings('additional');
		if (!isset($additionalSettings['disable-xmlrpc'])) {
			$additionalSettings['disable-xmlrpc'] = 'on';
			self::saveSettings($additionalSettings, 'additional');
		}

		

	}


	/**
	 * Check if the plugin has been updated and if so, act accordingly
	 * 
	 */
	static private function _updateChecker() {

		$savedPluginVersion = self::_getSavedPluginVersion();
		$currentPluginVersion = self::getCurrentPluginVersion();
		if (version_compare($savedPluginVersion, $currentPluginVersion, 'l')) {
			self::_performUpdate($savedPluginVersion, $currentPluginVersion);
			self::_setPluginVersion($currentPluginVersion);
		}

	}


	/**
	 * Prepare plugin meta data to be reported
	 * 
	 * @return array
	 */
	static public function preparePluginMeta() {

		// prepare meta for plugin history request
		$all_users = get_users();
		$roles = array();
		foreach ($all_users as $user) {
			if (!empty($user->roles))
				foreach ($user->roles as $role) {
				if (!isset($roles[$role]))
					$roles[$role] = 0;
				$roles[$role]++;
			}
		}
		$pluginMeta = array(
				'wordpress-version' => get_bloginfo('version'),
				'plugin-version' => self::getCurrentPluginVersion(),
		);
		foreach ($roles as $role => $count) {
			$pluginMeta['registered-' . $role . 's'] = $count;
		}
		$metaHeader = array(
				'meta' => $pluginMeta,
		);
		return $metaHeader;
		

	}


	/**
	 * Returns WordPress User Id
	 * 
	 * Translate uppercased key "ID" which exist in old WordPress versions (3.0-3.2).
	 * 
	 * @param WP_User $user User object
	 * @return int 
	 */
	static public function getUserId($user) {

		return isset($user->ID) ? $user->ID : $user->id;

	}


	/**
	 * Returns the blog language code
	 *
	 * @return string
	 */
	static public function getBlogLanguage() {
	
		$language = get_bloginfo('language');
		$language = strtolower(substr($language, 0, 2));
		return $language;
	
	}


	/**
	 * Returns the blog's technology
	 *
	 * @return string
	 */
	static public function getBlogTechnology() {

		return 'wordpress3';

	}


	/**
	 * Return the Rublon API domain
	 * 
	 * @return string
	 */
	static public function getAPIDomain() {

		return self::RUBLON_API_DOMAIN;

	}


	/**
	 * This function SHOULD NOT BE USED. It exists for l18n purposes only.
	 * 
	 */
	static private function _additionalTranslations() {

		$translation = __('Rublon provides stronger security for online accounts through invisible two-factor authentication. It protects your accounts from sign-ins from unknown devices, even if your passwords get stolen.', 'rublon2factor');

	}


	/**
	 * Retrieve a GET-passed parameter
	 * 
	 * @param string $key
	 * @return mixed|null
	 */
	static public function uriGet($key) {

		return ((isset($_GET[$key])) ? $_GET[$key] : null);

	}


	/**
	 * Retrieve a POST-passed parameter
	 * 
	 * @param string $key
	 * @return mixed|null
	 */
	static public function formGet($key) {

		return ((isset($_POST[$key])) ? $_POST[$key] : null);

	}


	/**
	 * Retrieve return page in the Admin Panel received via GET
	 * 
	 */
	static public function getReturnPage() {

		$page = admin_url();
		$custom = self::uriGet('custom');
		if (!empty($custom))
			switch ($custom) {
				case 'rublon':
					$page = admin_url(self::RUBLON_PAGE);
					break;
				case 'profile':
					$page = admin_url('profile.php');
					break;
				default:
					$page = urldecode(str_replace('[[CUSTOM]]', '', $custom));
		}
		return $page;

	}


	/**
	 * Re-orders the active plugin list so that Rublon is always run first
	 * 
	 */
	static public function meFirst() {

		$plugin_list = get_option('active_plugins');
		$me = plugin_basename(RUBLON2FACTOR_PLUGIN_PATH);
		$my_plugin_position = array_search($me, $plugin_list);
		if ($my_plugin_position) {
			array_splice($plugin_list, $my_plugin_position, 1);
			array_unshift($plugin_list, $me);
			update_option('active_plugins', $plugin_list);
		}

	}


	/**
	 * Sets the XML-RPC API access status
	 * 
	 * Checks if XML-RPC API has been disabled in the plugin settings
	 * and if yes, prevents any access to it.
	 * 
	 */
	static private function _checkXMLRPCStatus() {

		$settings = self::getSettings('additional');
		if(!empty($settings['disable-xmlrpc']) && $settings['disable-xmlrpc'] == 'on') {
			add_filter('xmlrpc_enabled', '__return_false');
		}

	}


	/**
	 * Create and store a new nonce for further use once headers are sent
	 * 
	 */
	static public function newNonce() {

		$nonce = wp_create_nonce('rublon=init-registration');
		self::$nonce = $nonce;
		RublonCookies::storeNonceInCookie($nonce);

	}


	/**
	 * Retrieve nonce and clear it
	 * 
	 * @return string
	 */
	static public function getNonce() {

		return self::$nonce;

	}


	/**
	 * Checks if a given URL points to an Administrator Panel page
	 * 
	 * The method assumes that if a given URL points to an Admin
	 * Panel page, it contains the Admin Panel URL, so it must be
	 * a full URL path.
	 * 
	 * @param string $url
	 * @return boolean
	 */
	static public function isAdminURL($url) {

		$admin_url = admin_url();
		if (substr($url, -1) == '/')
			$admin_url = trailingslashit($admin_url);
		$url_no_scheme = preg_replace('/http(s)?:\/\//', '', $url);
		$admin_url_no_scheme = preg_replace('/http(s)?:\/\//', '', $admin_url);
		return (strpos($url_no_scheme, $admin_url_no_scheme) !== false);
		

	}


	/**
	 * Extends a given URL to its full form 
	 * 
	 * @param string $url
	 * @return string
	 */
	static public function normalizeURL($url) {

		if (!preg_match('/http(s)?:\/\//', $url))
			$url = 'http://' . $url;
		if (self::isAdminURL($url))
			if (defined('FORCE_SSL_ADMIN'))
				if (FORCE_SSL_ADMIN)
					$url = preg_replace('/http:\/\//', 'https://', $url);
		return $url;

	}

}