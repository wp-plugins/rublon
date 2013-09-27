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
 * Rublon2FactorHelper class
 *
 * It provides helper functionalities for Rublon2Facror module.
 *
 */

class Rublon2FactorHelper {

	const RUBLON_SESSION_NAME = 'rublon2factor_session';
	const RUBLON_SETTINGS_KEY = 'rublon2factor_settings';
	const RUBLON_REGISTRATION_SETTINGS_KEY = 'rublon2factor_registration_settings';
	const RUBLON_SESSION_KEY_USER = 'rublon2factor_user';
	const RUBLON_SESSION_KEY_RETURN_PAGE = 'rublon2factor_return_page';
	const RUBLON_SESSION_KEY_MESSAGES = 'rublon2factor_messages';
	const RUBLON_SESSION_KEY_SECURITY_TOKENS = 'rublon2factor_security_tokens';
	const RUBLON_META_PROFILE_ID = 'rublon_profile_id';
	static private $callback = null;
	static private $registration = null;

	/**
	 * Initialize the consumer.
	 */
	static public function init() {

		//Initialize private session for storing data.
		session_name(self::RUBLON_SESSION_NAME);
		session_start();

		// Initialize localization
		if (function_exists ('load_plugin_textdomain'))
		{
			load_plugin_textdomain ('rublon2factor', false, RUBLON2FACTOR_BASE_PATH . '/includes/languages/');
		}

		self::$registration = new RublonConsumerRegistration();

		$settings = self::getSettings();
		if (self::isActive($settings)) {
			self::$callback = new Rublon2FactorCallback($settings);
		}

		self::updateChecker();

	}


	/**
	 * Handle the Rublon callback
	 * 
	 * @param string|NULL $state Callback state
	 * @param string $token Access token
	 * @param string $window_type Window type (window, popup)
	 */
	static public function handleCallback($state, $token, $window_type)	{

		if (!isset($state) || !isset($token)) {
			return;
		}
		self::getCallback()->run($state, $token, $window_type);

	}

	/**
	 * Return the initialized callback.
	 *
	 * @return Rublon2FactorCallback
	 */
	static public function getCallback() {
		return self::$callback;
	}
	
	/**
	 * Add the Rublon JS Library to the page code.
	 */
	static public function addScript() {
		if (self::isActive(self::getSettings())) {
			self::getCallback()->addScript();
		}
	}
	
	/**
	 * Perform authorization by Rublon2Factor.
	 */
	static public function authenticateWithRublon($user) {
		if (self::isActive(self::getSettings())) {
			self::getCallback()->authenticateWithRublon($user);
		}
	}
	
	/**
	 * Add Rublon secure account button to the page code.
	 * 
	 * @param string $page Optional parameter setting the return page
	 */
	static public function addSecureAccountButton($page = 'profile') {
		if (self::isActive(self::getSettings())) {
			self::getCallback()->addSecureAccountButton($page);
		}
	}
	
	/**
	 * Add button for disabling Rublon protection button to the page code.
	 * 
	 * @param string $page Optional parameter setting the return page
	 */
	static public function addDisableAccountSecurityButton($page = 'profile') {
		if (self::isActive(self::getSettings())) {
			self::getCallback()->addDisableAccountSecurityButton($page);
		}
	}
	
	/**
	 * Remove Rublon second factor protection from current user
	 * account.
	 */
	static public function disableAccountSecurity()
	{
		if (self::isActive(self::getSettings())) {
			self::getCallback()->disableAccountSecurity();
		}
	}
	
	/**
	 * Return actual settings of Rublon plug-in. 
	 */
	static public function getSettings()
	{
		return get_option(self::RUBLON_SETTINGS_KEY);
	}


	/**
	 * Save the Rublon plugin settings
	 */
	static public function saveSettings($settings) {

		update_option(self::RUBLON_SETTINGS_KEY, $settings);

	}


	/**
	 * Return the page url for redirection.
	 *
	 * @return string
	 */
	static public function getReturnPageUrl() {
		$key = self::RUBLON_SESSION_KEY_RETURN_PAGE;
		$value = self::getSessionData($key);
		self::clearSessionData($key);
		return $value;
	}
	
	/**
	 * Store the page url for redirection.
	 *
	 * @param string $url
	 */
	static public function setReturnPageUrl($url) {
		self::setSessionData(self::RUBLON_SESSION_KEY_RETURN_PAGE, $url);
	}


	/**
	 * Generate random security token
	 *
	 * @return string
	 */
	static private function generateSecurityToken() {
	
		return sha1(microtime() . serialize($_SERVER) . mt_rand(1, 999999999) . uniqid('', true));
	
	}


	/**
	 * Adds a new anti-CSRF security token to the session
	 *
	 * @param string $newToken
	 */
	static public function newSecurityToken() {
	
		$securityTokens = self::getSessionData(self::RUBLON_SESSION_KEY_SECURITY_TOKENS);
		if (empty($securityTokens))
			$securityTokens = array();
		$newToken = self::generateSecurityToken();
		$securityTokens[] = $newToken;
		self::setSessionData(self::RUBLON_SESSION_KEY_SECURITY_TOKENS, $securityTokens);
		return $newToken;
	
	}


	/**
	 * Validate the security token against CSRF attacks
	 *
	 * @param string $receivedToken Token received in the consumer params
	 */
	static public function validateSecurityToken($receivedToken) {
	
		$securityTokens = self::getSessionData(self::RUBLON_SESSION_KEY_SECURITY_TOKENS);
		if (is_array($securityTokens) && in_array($receivedToken, $securityTokens)) {
			$key = array_search($receivedToken, $securityTokens);
			array_splice($securityTokens, $key, 1);
			if (!empty($securityTokens))
				self::setSessionData(self::RUBLON_SESSION_KEY_SECURITY_TOKENS, $securityTokens);
			else
				self::clearSessionData(self::RUBLON_SESSION_KEY_SECURITY_TOKENS);
			return true;
		} else {
			return false;
		}
	
	
	}


	/**
	 * Return the page url for redirection.
	 *
	 * @return WP_User
	 */
	static public function getUserToAuthenticate() {
		$key = self::RUBLON_SESSION_KEY_USER;
		$value = self::getSessionData($key);
		self::clearSessionData($key);
		return $value;
	}

	/**
	 * Store the user to be authenticated.
	 *
	 * @param WP_User $user
	 */
	static public function setUserToAuthenticate($user) {
		self::setSessionData(self::RUBLON_SESSION_KEY_USER, $user);
	}

	/**
	 * Return the message.
	 *
	 * @return array
	 */
	static public function getMessages() {

		$key = self::RUBLON_SESSION_KEY_MESSAGES;
		$messages = self::getSessionData($key);
		self::clearSessionData($key);
		return $messages;

	}

	/**
	 * Store the message.
	 *
	 * @param string $message
	 * @param string $type
	 */
	static public function setMessage($message, $type) {
	
		$key = self::RUBLON_SESSION_KEY_MESSAGES;
		$messages = self::getSessionData($key);
		if (empty($messages))
			$messages = array();
		$messages[] = array(
			'message' => $message,
			'message_type' => $type,
		);
		self::setSessionData(self::RUBLON_SESSION_KEY_MESSAGES, $messages);

	}

	/**
	 * Checks if Rublon2factor module is active.
	 *
	 * @return boolean
	 */
	static public function isActive($settings) {
		return !empty($settings) && !empty($settings['rublon_system_token']) && !empty($settings['rublon_secret_key']);
	}

	
	/**
	 * Retrieves plugin's version from the settings
	 *
	 * @return string
	 */
	static public function getSavedPluginVersion() {

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
	static public function setPluginVersion($version) {

		$settings = self::getSettings();
		if (empty($settings))
			$settings = array();
		$settings['rublon_plugin_version'] = $version;
		self::saveSettings($settings);

	}


	/**
	 * Updates rublon_profile_id for a given user, to turn on second authentication factor.
	 *
	 * @param int $user
	 * @param int $rublonProfileId
	 * @return int|false Number of updated users or false on error
	 */
	static public function connectRublon2Factor($user, $rublonProfileId) {

		return add_user_meta(self::getUserId($user), self::RUBLON_META_PROFILE_ID, $rublonProfileId, true);

	}
	
	/**
	 * Updates rublon_profile_id for a given user, to turn off second authentication factor.
	 *
	 * @param int $user
	 * @param int $rublonProfileId
	 * @return int|false Number of updated users or false on error
	 */
	static public function disconnectRublon2Factor($user, $rublonProfileId) {

		$hasProfileId = get_user_meta(self::getUserId($user), self::RUBLON_META_PROFILE_ID, true);
		if ($hasProfileId && $hasProfileId == $rublonProfileId)
			return delete_user_meta(self::getUserId($user), self::RUBLON_META_PROFILE_ID);
		else
			return false;

	}

	/**
	 * Specify and save url of the return page.
	 */
	static public function saveReturnPageUrl($url) {
		self::setReturnPageUrl($url);
	}

	/**
	 * Checks if current users account is secured by Rublon2Factor.
	 *
	 * @return boolean
	 */
	static public function isCurrentUserSecured() {
		$current_user = wp_get_current_user();
		return self::isUserSecured($current_user);
	}

	/**
	 * Checks if given user is secured by Rublon2Factor.
	 *
	 * @param WP_User $user
	 * @return boolean
	 */
	static public function isUserSecured($user) {
		$rublonProfileId = get_user_meta(self::getUserId($user), self::RUBLON_META_PROFILE_ID, true);
		return self::isActive(self::getSettings()) && !empty($rublonProfileId);
	}

	/**
	 * Return a session data for a given key.
	 *
	 * @param string $key
	 * @return mixed
	 */
	static private function getSessionData($key) {
		if (isset($_SESSION[$key])) {
			$data = $_SESSION[$key];
			return $data;
		}
		return false;
	}

	/**
	 * Clear a session data for a given key.
	 *
	 * @param string $key
	 */
	static private function clearSessionData($key) {
		if (isset($_SESSION[$key]) || (array_key_exists($key, $_SESSION) && is_null($_SESSION[$key])))
			unset($_SESSION[$key]);
	}

	static public function clearSecurityTokens() {

		self::clearSessionData(self::RUBLON_SESSION_KEY_SECURITY_TOKENS);		

	}


	/**
	 * Store a session data for a given key.
	 *
	 * @param string $key Key under which the data will be stored
	 * @param mixed $value Data to store
	 */
	static private function setSessionData($key, $value) {
		$_SESSION[$key] = $value;
	}

	/**
	 * Returns the current page url
	 *
	 * @return string
	 */
	static public function getCurrentPageUrl() {
		$request_uri = ((!isset($_SERVER['REQUEST_URI'])) ? $_SERVER['PHP_SELF'] : $_SERVER['REQUEST_URI']);
		$request_port = ((!empty($_SERVER['SERVER_PORT']) AND $_SERVER['SERVER_PORT'] <> '80') ? (":" . $_SERVER['SERVER_PORT']) : '');
		$request_protocol = (self::isHttps() ? 'https' : 'http') . "://";
			
		return $request_protocol . $_SERVER['SERVER_NAME'] . $request_port . $request_uri;
	}

	/**
	 * Checks if the current connection is being made over https
	 *
	 * @return boolean
	 */
	static private function isHttps() {
		if (!empty($_SERVER['SERVER_PORT'])) {
			if (trim($_SERVER['SERVER_PORT']) == '443') {
				return true;
			}
		}

		if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
			if (strtolower(trim($_SERVER['HTTP_X_FORWARDED_PROTO'])) == 'https') {
				return true;
			}
		}

		if (!empty($_SERVER['HTTPS'])) {
			if (strtolower(trim($_SERVER['HTTPS'])) == 'on' OR trim($_SERVER['HTTPS']) == '1') {
				return true;
			}
		}

		return false;
	}
	
	/**
	 * Perform a consumer registration action
	 * 
	 * @param string $action
	 */
	static public function consumerRegistrationAction($action)
	{
		self::$registration->action($action);
	}
	
	/**
	 * Validate System Token and Secret Key on Rublon servers 
	 * 
	 * @param array $settings
	 * @return boolean
	 */
	static public function verifyConsumerSettings($settings) {

		$consumer = new RublonConsumer($settings['rublon_system_token'], $settings['rublon_secret_key']);
		$service = new RublonService2Factor($consumer);
		$request = new RublonRequest($service);
		$url = self::$registration->getDomain() . self::$registration->getActionUrl() . '/verify_consumer_settings' ;
		$params = array('systemToken' => $settings['rublon_system_token']);
		$response = $request->setRequestParams($url, $params)->getRawResponse();

		try {
			$response = json_decode($response, true);
		} catch (Exception $e) {
			$response = null;
		}

		if (!empty($response['status']) && $response['status'] == 'OK' && !empty($response['paramsValidity']))
			return true;

		return false;

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
		$url = self::$registration->getDomain() . self::$registration->getActionUrl() . '/add_history';
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
		$msg['phpinfo'] = self::info();				
		
		$ch = curl_init(RUBLON2FACTOR_NOTIFY_URL);
		$headers = array(
			"Content-Type: application/json; charset=utf-8",
			"Accept: application/json, text/javascript, */*; q=0.01"
		);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		
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
	static function info() {
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
	static private function dbMigrate() {

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
	static private function performUpdate($from, $to) {

		// migrate old database entries into user meta
		self::dbMigrate();

		// send update info to Rublon
		$pluginMeta = self::preparePluginMeta();
		$pluginMeta['action'] = 'update';
		$pluginMeta['meta']['previous-version'] = $from;
		self::pluginHistoryRequest($pluginMeta);

	}

	/**
	 * Check if the plugin has been updated and if so, act accordingly
	 * 
	 */
	static private function updateChecker() {

		$savedPluginVersion = self::getSavedPluginVersion();
		$currentPluginVersion = self::getCurrentPluginVersion();
		if (version_compare($savedPluginVersion, $currentPluginVersion, 'l')) {
			self::performUpdate($savedPluginVersion, $currentPluginVersion);
			self::setPluginVersion($currentPluginVersion);
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
				'plugin-version' => Rublon2FactorHelper::getCurrentPluginVersion(),
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
	 * Returns WordPress User Id.
	 * 
	 * Translate uppercased key "ID" which exist in old WordPress versions (3.0-3.2).
	 * 
	 * @param WP_User $user User object 
	 */
	static public function getUserId($user) {

		return isset($user->ID) ? $user->ID : $user->id;

	}


	/**
	 * Returns the blog language code
	 *
	 * $returns string
	 */
	static public function getBlogLanguage() {
	
		$language = get_bloginfo('language');
		$language = strtolower(substr($language, 0, 2));
		return $language;
	
	}


	/**
	 * Prepare HTML code for displaying the plugin activation ribblon on the "Plugins" page
	 * 
	 */
	static public function activationRibbon() {

		$ribbonStart = '<div class="updated" style="padding: 0; margin: 0; border: none; background: none;">';
		$ribbonStart .= '<div class="rublon-activate-ribbon">';
		$ribbonStart .= '<form method="post" action="options.php" id="rublon-plugin-admin-activation">';
		echo $ribbonStart;
		settings_fields('rublon2factor_settings_group');
		$ribbonEnd = '<div class="rublon-activate-description-wrapper">' . self::constructRublonButton(__('Protect your account', 'rublon2factor'), 'document.getElementById(\'rublon-plugin-admin-activation\').submit();return false;') . '</div>';
		$ribbonEnd .= '<input type="hidden" name="' . RublonConsumerRegistration::ACTION_INITIALIZE . '" value="' . __('Protect your account', 'rublon2factor') . '" />';
		$lang = self::getBlogLanguage(); 
		$ribbonEnd .= '<div class="rublon-activate-description-wrapper"><div class="rublon-activate-description">' . __('Rublon mobile app required', 'rublon2factor') . '.' . sprintf('<strong><a href="http://rublon.com%s/get" target="_blank"><span style=color:#5bba36> ',  (($lang != 'en') ? ('/' . $lang) : '')) . __('Free Download', 'rublon2factor') . ' &raquo;</span></a></strong></div></div>';
		$ribbonEnd .= '<div class="rublon-activate-image"><a href="http://rublon.com'. (($lang != 'en') ? '/' . $lang . '/' : '') . '" target="_blank"><img src="' . RUBLON2FACTOR_PLUGIN_URL . '/assets/images/rublon-ribbon-text.png" /></a></div>';
		$ribbonEnd .= '<div class="rublon-clear"></div>';
		$ribbonEnd .= '</form></div></div>';
		echo $ribbonEnd;

	}


	/**
	 * Create a Rublon button with a dynamic text
	 */
	static public function constructRublonButton($text, $onClick) {

		$button = '<a href="http://rublon.com" onclick="' . $onClick . '" style="width:auto;height:30px;background: url(' . Rublon2FactorCallback::RUBLON_DOMAIN
			. '/public/img/buttons/rublon-btn-bg-dark-medium.png) left top repeat-x;font-weight:bold;font-size:13px;font-family:&quot;Helvetica&quot;'
			. ' &quot;Nimbus Sans&quot; &quot;Arial&quot; &quot;sans-serif&quot;;color:#ffffff;text-decoration:none;display:inline-block;position:relative;padding:0 8px;'
			. 'margin:0 8px;"><span style="display:block;width:33px;height:30px;background:url(' . Rublon2FactorCallback::RUBLON_DOMAIN
			. '/public/img/buttons/rublon-btn-bg-begin-dark-medium.png) left top no-repeat;position:absolute;left:-8px;top:0;padding:0;margin:0;"></span><span'
			. ' style="display:block;width:22px;height:30px;background:url(' . Rublon2FactorCallback::RUBLON_DOMAIN . '/public/img/buttons/rublon-btn-bg-end-dark-medium.png)'
			. ' left top no-repeat;position:absolute;right:-8px;top:0;padding:0;margin:0;"></span><span style="display:block;margin:0;padding:5px 22px 0 33px;font-weight:bold;'
			. 'line-height:17px;height:17px;font-size:13px;font-family: Helvetica, &quot;Nimbus Sans&quot;, Arial, sans-serif;color:#ffffff;white-space:nowrap;'
			. '">'
			. $text . '</span></a>';
		return $button;

	}


	/**
	 * Displays the app info box (to be displayed under the Rublon buttons when a Trusted Device is not present)
	 * 
	 * @return string
	 */
	static public function appInfoBox($hidden = true) {

		$infoBox = '<div class="rublon-app-info-box"' . ((!$hidden) ? ' style="display: block;"' : '') . '>';
		$infoBox .= '<p class="rublon-app-info-text"><strong>' . __('Rublon mobile app required:', 'rublon2factor') . '</strong></p>';
		$infoBox .= '<div class="rublon-app-info-icons"></div>';
		$lang = self::getBlogLanguage();
		$infoBox .= '<p class="rublon-app-info-link"><strong>' . sprintf('<a href="http://rublon.com%s/get" target="_blank">', (($lang != 'en') ? ('/' . $lang) : '')) . __('Free Download', 'rublon2factor') . '</a></strong></p>';
		$infoBox .= '</div>';
		if ($hidden) {
			$infoBox .= '<script>//<![CDATA[
				var checkTrustedDevices = function() {
					if (window.RublonConfigure && !window.RublonConfigure.trustedDevices) {
							var elements = document.querySelectorAll(\'.rublon-app-info-box\');
						for (var i = 0; i < elements.length; i++)
							elements[i].style.display = \'block\';
					}
				};
				if (window.RublonConfigure) {
					checkTrustedDevices();
				} else {
					if (document.addEventListener) {
						document.addEventListener(\'RublonJSSDKInit\', function() {
							checkTrustedDevices();
						}, false);
					} else {
						document.documentElement.RublonJSSDKInit = 0;
						document.documentElement.attachEvent(\'onpropertychange\', function(event) {
							if (event.propertyName == \'RublonJSSDKInit\' && event.srcElement.RublonJSSDKInit > 0) {
								checkTrustedDevices();
							}
						});
					}
				}
			//]]></script>';
		}
		return $infoBox;		

	}


	/**
	 * This function SHOULD NOT BE USED. It exists for l18n purposes only.
	 * 
	 */
	static private function additionalTranslations() {

		$translation = __('Rublon provides stronger security for online accounts through invisible two-factor authentication. It protects your accounts from sign-ins from unknown devices, even if your passwords get stolen.', 'rublon2factor');

	}
	

}