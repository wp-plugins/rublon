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
	const RUBLON_SESSION_KEY_MESSAGE = 'rublon2factor_message';
	const RUBLON_SESSION_KEY_MESSAGE_TYPE = 'rublon2factor_message_type';
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
	}

	static public function handleCallback($state, $token, $window_type)
	{
		if (!isset($state) || $state != 'ok' || !isset($token)) {
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
	 */
	static public function addSecureAccountButton() {
		if (self::isActive(self::getSettings())) {
			self::getCallback()->addSecureAccountButton();
		}
	}
	
	/**
	 * Add Rublon insecure account button to the page code.
	 */
	static public function addInsecureAccountButton() {
		if (self::isActive(self::getSettings())) {
			self::getCallback()->addInsecureAccountButton();
		}
	}
	
	/**
	 * Remove Rublon second factor protection from current user
	 * account.
	 */
	static public function insecureAccount()
	{
		if (self::isActive(self::getSettings())) {
			self::getCallback()->insecureAccount();
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
	 * @return string
	 */
	static public function getMessage() {
		$key = self::RUBLON_SESSION_KEY_MESSAGE;
		$value = self::getSessionData($key);
		self::clearSessionData($key);
		return $value;
	}

	/**
	 * Store the message.
	 *
	 * @param string $message
	 */
	static public function setMessage($message) {
		self::setSessionData(self::RUBLON_SESSION_KEY_MESSAGE, $message);
	}
	
	/**
	 * Return the message type.
	 *
	 * @return string
	 */
	static public function getMessageType() {
		$key = self::RUBLON_SESSION_KEY_MESSAGE_TYPE;
		$value = self::getSessionData($key);
		self::clearSessionData($key);
		return $value;
	}

	/**
	 * Store the message type.
	 *
	 * @param string $messageType
	 */
	static public function setMessageType($messageType) {
		self::setSessionData(self::RUBLON_SESSION_KEY_MESSAGE_TYPE, $messageType);
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
	 * Updates rublon_profile_id for a given user, to turn on 
	 * second authentication factor.
	 *
	 * @param int $user
	 * @param int $rublonProfileId
	 * @return int|false Number of updated users or false on error
	 */
	static public function connectRublon2Factor($user, $rublonProfileId) {
		global $wpdb;

		$sql = "UPDATE $wpdb->users SET rublon_profile_id = %d WHERE ID = %d";
		return $wpdb -> query($wpdb -> prepare($sql, $rublonProfileId, $user->id));
	}
	
	/**
	 * Updates rublon_profile_id for a given user, to turn off 
	 * second authentication factor.
	 *
	 * @param int $user
	 * @param int $rublonProfileId
	 * @return int|false Number of updated users or false on error
	 */
	static public function unconnectRublon2Factor($user, $rublonProfileId) {
		global $wpdb;

		$sql = "UPDATE $wpdb->users SET rublon_profile_id = %d WHERE ID = %d AND rublon_profile_id = %d";
		return $wpdb -> query($wpdb -> prepare($sql, null, $user->id, $rublonProfileId));
	}

	/**
	 * Specify and save url of the return page.
	 */
	static public function saveReturnPageUrl() {
		self::setReturnPageUrl(self::getCurrentPageUrl());
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
		$rublonProfileId = $user -> rublon_profile_id;
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
		if (isset($_SESSION[$key]))
			unset($_SESSION[$key]);
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
	
	static public function consumerRegistrationAction($action)
	{
		self::$registration->action($action);
	}
}
