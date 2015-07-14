<?php
/**
 * Helper functions for plugin's cookie handling
 *
 * @package   rublon2factor\includes
 * @author     Rublon Developers http://www.rublon.com
 * @copyright  Rublon Developers http://www.rublon.com
 * @license    http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */

/**
 * Contains methods used for cookie handling
 * 
 */
class RublonCookies {


	const COOKIE_PREFIX = 'Rublon-WP_';
	const COOKIE_MESSAGES = 'messages';
	const COOKIE_AUTHENTICATED = 'auth';
	const COOKIE_LOGIN_TOKEN_ID = 'login';
	const COOKIE_RETURNURL = 'return_url';
	const COOKIE_ADAM = 'adam_said';


	/**
	 * Retrieve messages from the message cookie
	 * 
	 * @return array|null
	 */
	static public function getMessagesFromCookie() {

		$cookieName = self::COOKIE_PREFIX . self::COOKIE_MESSAGES;
		$messages = self::_getCookieData($cookieName);		
		
		self::_clearCookieData($cookieName);
		if (!empty($messages)) {
			$messages = json_decode(stripslashes($messages), true);
		}		
		
		return $messages;
	
	}


	/**
	 * Store all given messages in the cookie
	 * 
	 * @param array $messages
	 */
	static public function storeAllMessagesInCookie($messages) {

		if (!empty($messages)) {
			foreach ($messages as $msg) {
				self::storeMessageInCookie($msg);
			}
		}

	}


	/**
	 * Store a message in the message cookie
	 * 
	 * @param string $msg
	 */
	static public function storeMessageInCookie($msg) {

		$cookieName = self::COOKIE_PREFIX . self::COOKIE_MESSAGES;
		$messages = self::_getCookieData($cookieName);
		if (empty($messages)) {
			$messages = array();
		} else {
			$messages = json_decode(stripslashes($messages), true);
		}
		array_push($messages, $msg);
		$messages = json_encode($messages);
		
		self::_setCookieData($cookieName, $messages);

	}


	/**
	 * Retrieve the return URL from the cookie
	 * 
	 * @return string
	 */
	static public function getReturnURL() {

		$cookieName = self::COOKIE_PREFIX . self::COOKIE_RETURNURL;
		$returnURL = self::_getCookieData($cookieName);
		self::_clearCookieData($cookieName);
		return !empty($returnURL) ? $returnURL : '';

	}

	
	/**
	 * Store the return URL in a cookie
	 * 
	 * The user's browser will be redirected to this
	 * URL after the authentication.
	 * 
	 * @param string $url
	 */
	static public function storeReturnURL($url) {

		if (!headers_sent()) {
			$cookieName = self::COOKIE_PREFIX . self::COOKIE_RETURNURL;
			self::_clearCookieData($cookieName);
			self::_setCookieData($cookieName, $url);
		}
	}


	static public function storeLoginTokenIdInCookie($login_token_id) {

		$cookie_name = self::COOKIE_PREFIX . self::COOKIE_LOGIN_TOKEN_ID;
		self::_clearCookieData($cookie_name);
		self::_setCookieData($cookie_name, $login_token_id);		

	}


	static public function getLoginTokenIdFromCookie() {
	
		$cookie_name = self::COOKIE_PREFIX . self::COOKIE_LOGIN_TOKEN_ID;
		$login_token_id = self::_getCookieData($cookie_name);
		self::_clearCookieData($cookie_name);
		return $login_token_id;
	
	}


	/**
	 * Set the WP auth cookie using stored cookie params
	 * 
	 * @param WP_User $user
	 * @param boolean $remember
	 */
	static public function setLoggedInCookie($user, $remember) {

		$user_id = RublonHelper::getUserId($user);
		wp_set_auth_cookie($user_id, $remember, is_ssl());

	}


	/**
	 * Set the user's Rublon authentication cookie
	 *
	 * @param WP_User $user User whose authentication should be checked
	 * @return string
	 */
	static public function setAuthCookie($user = null, $remember = null) {

		if (!$user) {
			$user = wp_get_current_user();
		}
		$user_id = RublonHelper::getUserId($user);
		$cookie_name = self::COOKIE_PREFIX . self::COOKIE_AUTHENTICATED;
		$cookie_params = self::_getAuthCookieParams();
		if ($remember === null) {
			$remember = self::_isUserRemembered($user);
		}
		$expiration_params = self::_getAuthCookieExpiration($user_id, $remember);
		if (isset($cookie_params['logged_in_secure']) && empty($cookie_params['logged_in_secure'])) {
			$cookie_params['secure'] = false;
		}
		if (!isset($cookie_params['secure'])) {
			$cookie_params['secure'] = false;
		}
		$cookie_data = self::_prepareAuthCookieData($user, $expiration_params['expiration']);
		self::_setCookieData($cookie_name, $cookie_data, $expiration_params['expire'], $cookie_params['secure']);
		$_COOKIE[$cookie_name] = $cookie_data;
		return $cookie_data;

	}


	static private function _isUserRemembered($user) {

		$logged_in_cookie = wp_parse_auth_cookie('', 'logged_in');
		$default_cookie_life = apply_filters('auth_cookie_expiration', (2 * DAY_IN_SECONDS), RublonHelper::getUserId($user), false);
		$remember = (($logged_in_cookie['expiration'] - time()) > $default_cookie_life);
		return $remember;

	}


	/**
	 * Clear the user's Rublon authentication cookie
	 *
	 */
	static public function clearAuthCookie() {

		$cookie_name = self::COOKIE_PREFIX . self::COOKIE_AUTHENTICATED;
		self::_clearCookieData($cookie_name);

	}


	/**
	 * Remove depreacted cookies
	 * 
	 * @param array $names Names of cookies to be removed
	 */
	static public function cookieCleanup($names = array()) {

		foreach ($names as $name) {
			$name = self::COOKIE_PREFIX . $name;
			$cookieData = self::_getCookieData($name);
			if (isset($cookieData)) {
				self::_clearCookieData($name);
			}
		}

	}


	/**
	 * Check if a user's Rublon auth cookie has been set
	 * 
	 * @param WP_User $user
	 * @param string $plugin_version
	 * @return boolean
	 */
	static public function isAuthCookieSet($user, $plugin_version) {

		$auth_cookie = self::_getCookieData(self::COOKIE_PREFIX . self::COOKIE_AUTHENTICATED);
		if (!empty($auth_cookie)) {
			$user_id = RublonHelper::getUserId($user);
			$cookie_elements = explode('|', $auth_cookie);
			if (isset($cookie_elements[1])) {
				$expiration = $cookie_elements[1];
			} else {
				$expiration = 0;
			}
			$cookie_data = self::_prepareAuthCookieData($user, $expiration, $plugin_version);
			if ($auth_cookie == $cookie_data) {
				return true;
			} else {
				self::clearAuthCookie();
			}
		}
		return false;
	
	}


	/**
	 * Set Rublon cookie data
	 *
	 * @param string $name Cookie name
	 * @param string $data Cookie data
	 */
	static private function _setCookieData($name, $data, $expire = null, $secure = null) {

		$cp = self::_getCookieParams();
		if ($expire !== null) {
			$cp['cookie_expires'] = $expire;
		}
		if ($secure !== null) {
			$cp['cookie_secure'] = $secure;
		}
	
		// set cookie
		setcookie($name, $data, $cp['cookie_expires'], $cp['cookie_path'], $cp['cookie_domain'], $cp['cookie_secure'], true);
		if ($cp['cookie_path'] != $cp['cookie_site_path']) {
			setcookie($name, $data, $cp['cookie_expires'], $cp['cookie_site_path'], $cp['cookie_domain'], $cp['cookie_secure'], true);
		}

	}


	/**
	 * Retrieve Rublon cookie data
	 *
	 * @param string $name Cookie name
	 * @return array|null
	 */
	static private function _getCookieData($name) {

		if (isset($_COOKIE[$name])) {
			$cookieData = $_COOKIE[$name];
			return $cookieData;
		}

	}


	/**
	 * Clear Rublon cookie data
	 *
	 * @param string $name Cookie name
	 */
	static private function _clearCookieData($name) {

		if (isset($_COOKIE[$name])) {
			if (!headers_sent()) {
				$cp = self::_getCookieParams();
				setcookie($name, '', time() - 3600, $cp['cookie_path'], $cp['cookie_domain'], $cp['cookie_secure'], true);
				if ($cp['cookie_path'] != $cp['cookie_site_path']) {
					setcookie($name, '', time() - 3600, $cp['cookie_site_path'], $cp['cookie_domain'], $cp['cookie_secure'], true);
				}
			}
			unset($_COOKIE[$name]);
		}

	}


	/**
	 * Prepares the Rublon auth cookie data
	 *
	 * @param WP_User $user User whose data will be hashed into the cookie
	 * @param int $expiration
	 * @param string $plugin_version
	 * @return string
	 */
	static private function _prepareAuthCookieData($user, $expiration, $plugin_version = '2.0.2') {

		$user_id = RublonHelper::getUserId($user);
		$settings = RublonHelper::getSettings();
		
		if (version_compare($plugin_version, '2.0.2', 'lt')) {
			$user_login = $user->user_login;
			$user_data = $user_login . $user_id;
			$cookie_data = hash_hmac('SHA256', $user_data, $settings['rublon_secret_key']);
		} else {
			$pass_frag = substr($settings['rublon_secret_key'], 8, 4);			
			$key = wp_hash($user->user_login . '|' . $pass_frag . '|' . $expiration, 'auth');
			$hash = hash_hmac('SHA256', $user->user_login . '|' . $expiration, $key);
			$cookie_data = $user->user_login . '|' . $expiration . '|' . $hash;
		}
		return $cookie_data;

	}


	/**
	 * Retrieve auth cookie params (if they're set)
	 *
	 * @return array
	 */
	static private function _getAuthCookieParams() {
	
		$cookie_params = array();
		$settings = RublonHelper::getSettings();
		if (!empty($settings['wp_cookie_params'])) {
			$cookie_params = $settings['wp_cookie_params'];
		}
		return $cookie_params;
	
	}


	/**
	 * Retrieve cookie expiration time
	 *
	 * @return array
	 */
	static private function _getAuthCookieExpiration($user_id, $remember) {

		$expiration_params = array();
		if ($remember) {
			$expiration_params['expiration'] = time() + apply_filters('auth_cookie_expiration', 14 * DAY_IN_SECONDS, $user_id, $remember);
			$expiration_params['expire'] = $expiration_params['expiration'] + (12 * HOUR_IN_SECONDS);
		} else {
			$expiration_params['expiration'] = time() + apply_filters('auth_cookie_expiration', 2 * DAY_IN_SECONDS, $user_id, $remember);
			$expiration_params['expire'] = 0;
		}
		return $expiration_params;
	
	}


	/**
	 * Get cookie params from WordPress settings
	 *
	 * @return array
	 */
	static private function _getCookieParams() {
	
		// set domains and paths in case they're not defined
		$cookie_domain = false;
		if (defined('COOKIE_DOMAIN')) {
			$cookie_domain = COOKIE_DOMAIN;
		}
		$cookie_path = preg_replace('|https?://[^/]+|i', '', get_option('home') . '/');
		if (defined('COOKIEPATH')) {
			$cookie_path = COOKIEPATH;
		}
		$cookie_site_path = preg_replace('|https?://[^/]+|i', '', get_option('siteurl') . '/');
		if (defined('SITECOOKIEPATH')) {
			$cookie_site_path = SITECOOKIEPATH;
		}
	
		// other cookie params
		$cookie_expires = time() + 2 * DAY_IN_SECONDS;
		$cookie_secure = is_ssl();
	
		return array(
			'cookie_domain' => $cookie_domain,
			'cookie_path' => $cookie_path,
			'cookie_site_path' => $cookie_site_path,
			'cookie_expires' => $cookie_expires,
			'cookie_secure' => $cookie_secure,
		);
	
	}
	
	/**
	 * Retrieve information if Adam has already said first sentence
	 *
	 * @return array|null
	 */
	static public function getAdamsCookie() {
	
	    $cookieName = self::COOKIE_PREFIX . self::COOKIE_ADAM;	    	    	    
	    return self::_getCookieData($cookieName);
	
	}
	
	/**
	 * Save information if Adam has already said first sentence
	 *
	 * @return array|null
	 */
	static public function storeAdamsCookie() {
	
	    $cookieName = self::COOKIE_PREFIX . self::COOKIE_ADAM;
	    return self::_setCookieData($cookieName, 1);
	
	}


}