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
	const RUBLON_SESSION_KEY_SECURITY_TOKEN = 'rublon2factor_security_token';
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
	 * Store the anti-CSRF security token
	 * 
	 * @param string $token Token to store
	 */
	static public function setSecurityToken($token) {

		self::setSessionData(self::RUBLON_SESSION_KEY_SECURITY_TOKEN, $token);

	}


	/**
	 * Retrieve the anti-CSRF security token
	 * 
	 * @return string
	 */
	static public function getSecurityToken() {

		$key = self::RUBLON_SESSION_KEY_SECURITY_TOKEN;
		$value = self::getSessionData($key);
		self::clearSessionData($key);
		return $value;

	}

	/**
	 * Generate random string
	 *
	 * @param int $len (optional)
	 * @return string
	 */
	static public function generateRandomString($len = 100) {

		$chars = '1234567890qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM';
		$max = strlen($chars) - 1;
		$result = '';
		for ($i=0; $i<$len; $i++) {
			$result .= $chars[mt_rand(0, $max)];
		}
		return $result;

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
		if (isset($_SESSION[$key]) || (array_key_exists($key, $_SESSION) && is_null($_SESSION[$key])))
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
}
