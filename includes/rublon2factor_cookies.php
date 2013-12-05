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
	const COOKIE_NONCE = 'nonce';
	const COOKIE_AUTHENTICATED = 'auth';


	/**
	 * Retrieve messages from the message cookie
	 * 
	 * @return array|null
	 */
	static public function getMessagesFromCookie() {

		$cookieName = self::COOKIE_PREFIX . self::COOKIE_MESSAGES;
		$messages = self::_getCookieData($cookieName);
		self::_clearCookieData($cookieName);
		if (!empty($messages))
			$messages = explode(':', $messages);
		return $messages;
	
	}


	/**
	 * Store a message in the message cookie
	 * 
	 * @param string $msg
	 */
	static public function storeMessageInCookie($msg) {

		$cookieName = self::COOKIE_PREFIX . self::COOKIE_MESSAGES;
		$messages = self::_getCookieData($cookieName);
		if (empty($messages))
			$messages = array();
		else
			$messages = explode(':', $messages);
		array_push($messages, $msg);
		$messages = implode(':', $messages);
		self::_setCookieData($cookieName, $messages);

	}


	/**
	 * Store a nonce in cookie
	 * 
	 * The nonce is used in the plugin registration process.
	 * 
	 * @param string $nonce
	 */
	static public function storeNonceInCookie($nonce) {

		$cookieName = self::COOKIE_PREFIX . self::COOKIE_NONCE;
		self::_clearCookieData($cookieName);
		self::_setCookieData($cookieName, $nonce);

	}


	static public function getNonceFromCookie() {

		$cookieName = self::COOKIE_PREFIX . self::COOKIE_NONCE;
		$nonce = self::_getCookieData($cookieName);
		self::_clearCookieData($cookieName);
		return $nonce;

	}


	/**
	 * Retrieve auth cookie params (if they're set)
	 *
	 */
	static private function _getAuthCookieParams() {
	
		$cookieParams = array(
				'secure' => '',
				'remember' => false,
		);
		$settings = RublonHelper::getSettings();
		if (!empty($settings['wp_cookie_params'])) {
			$cookieParams = $settings['wp_cookie_params'];
		}
		return $cookieParams;
	
	}


	/**
	 * Retrieve cookie expiration time
	 * 
	 * @return int
	 */
	static private function _getAuthCookieExpiration() {

		$expiration = 0;
		$settings = RublonHelper::getSettings();
		if (!empty($settings['wp_cookie_expiration'])) {
			$expiration = $settings['wp_cookie_expiration'];
		}
		return $expiration;

	}


	/**
	 * Set the WP auth cookie using stored cookie params
	 * 
	 * @param int $user_id
	 */
	static public function setLoggedInCookie($user_id) {

		$cookieParams = self::_getAuthCookieParams();
		wp_set_auth_cookie($user_id, $cookieParams['remember'], $cookieParams['secure']);

	}


	/**
	 * Set the user's Rublon authentication cookie
	 *
	 * @param WP_User $user User whose authentication should be checked
	 * @return string
	 */
	static public function setAuthCookie($user = null) {

		$cookieName = self::COOKIE_PREFIX . self::COOKIE_AUTHENTICATED;
		$cookieParams = self::_getAuthCookieParams();
		$cookieParams['expire'] = self::_getAuthCookieExpiration();
		if (!$user)
			$user = wp_get_current_user();
		$cookieData = self::_prepareAuthCookieData($user);
		self::_setCookieData($cookieName, $cookieData, $cookieParams);
		$_COOKIE[$cookieName] = $cookieData;
		return $cookieData;

	}


	/**
	 * Clear the user's Rublon authentication cookie
	 *
	 */
	static public function clearAuthCookie() {

		$cookieName = self::COOKIE_PREFIX . self::COOKIE_AUTHENTICATED;
		self::_clearCookieData($cookieName);

	}


	/**
	 * Prepares the Rublon auth cookie data
	 *
	 * @param WP_User $user User whose data will be hashed into the cookie
	 * @return string
	 */
	static private function _prepareAuthCookieData($user) {

		$userId = RublonHelper::getUserId($user);
		$userProfileId = RublonHelper::getUserProfileId($user);
		$userLogin = $user->user_login;
		$userData = $userLogin . $userId . $userProfileId;
		$settings = RublonHelper::getSettings();
		$cookieData = hash_hmac('SHA256', $userData, $settings['rublon_secret_key']);
		return $cookieData;

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
			if (isset($cookieData))
				self::_clearCookieData($name);
		}

	}


	/**
	 * Check if a user's Rublon auth cookie has been set
	 * 
	 * @param WP_User $user
	 * @return boolean
	 */
	static public function isAuthCookieSet($user) {
	
		$authCookie = self::_getCookieData(self::COOKIE_PREFIX . self::COOKIE_AUTHENTICATED);
		$cookieData = self::_prepareAuthCookieData($user);
		if (!empty($authCookie) && $authCookie == $cookieData) {
			return true;
		} else {
			self::clearAuthCookie();
			return false;
		}
	
	}


	/**
	 * Get cookie params from WordPress settings
	 *
	 */
	static private function _getCookieParams() {

		// set domains and paths in case they're not defined
		$cookieDomain = false;
		if (defined('COOKIE_DOMAIN'))
			$cookieDomain = COOKIE_DOMAIN;
		$cookiePath = preg_replace('|https?://[^/]+|i', '', get_option('home') . '/');
		if (defined('COOKIEPATH'))
			$cookiePath = COOKIEPATH;
		$cookieSitePath = preg_replace('|https?://[^/]+|i', '', get_option('siteurl') . '/');
		if (defined('SITECOOKIEPATH'))
			$cookieSitePath = SITECOOKIEPATH;
	
		// other cookie params
		$cookieExpires = time() + 14 * 24 * 60 * 60;
		$cookieSecure = is_ssl();
	
		return array(
				'cookie_domain' => $cookieDomain,
				'cookie_path' => $cookiePath,
				'cookie_site_path' => $cookieSitePath,
				'cookie_expires' => $cookieExpires,
				'cookie_secure' => $cookieSecure
		);

	}


	/**
	 * Set Rublon cookie data
	 *
	 * @param string $name Cookie name
	 * @param string $data Cookie data
	 */
	static private function _setCookieData($name, $data, $params = null) {

		$cp = self::_getCookieParams();
		if ($params) {
			if (isset($params['expire']))
				$cp['cookie_expires'] = $params['expire'];
			if (isset($params['secure']))
				$cp['cookie_secure'] = $params['secure'];
		}
	
		// set cookie
		setcookie($name, $data, $cp['cookie_expires'], $cp['cookie_path'], $cp['cookie_domain'], $cp['cookie_secure'], true);
		if ($cp['cookie_path'] != $cp['cookie_site_path'])
			setcookie($name, $data, $cp['cookie_expires'], $cp['cookie_site_path'], $cp['cookie_domain'], $cp['cookie_secure'], true);

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
			$cp = self::_getCookieParams();
			setcookie($name, '', time() - 3600, $cp['cookie_path'], $cp['cookie_domain'], $cp['cookie_secure'], true);
			if ($cp['cookie_path'] != $cp['cookie_site_path'])
				setcookie($name, '', time() - 3600, $cp['cookie_site_path'], $cp['cookie_domain'], $cp['cookie_secure'], true);
			unset($_COOKIE[$name]);
		}

	}


}