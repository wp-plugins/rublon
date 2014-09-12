<?php
/**
 * Code to be run while on login page
 *
 * @package   rublon2factor\includes
 * @author     Rublon Developers http://www.rublon.com
 * @copyright  Rublon Developers http://www.rublon.com
 * @license    http://opensource.org/licenses/gpl-license.php GNU Public License, version 2 
 */


/**
 * Display Rublon messages on the login screen
 * 
 * @param array $message WP message displayed on the login screen
 * @return void
 */
function rublon2factor_login_message($message) {

	$messages = RublonHelper::getMessages();
	if (!empty($message))
		$result = $message;
	else
		$result = '';
	if ($messages) {
		foreach ($messages as $msg)
			$result .= '<div class="' . $msg['type'] . ' fade" style="margin: 0 0 16px 8px; padding: 12px;">' . $msg['message'] . '</div>';
	}
	return $result;

}

add_filter('login_message', 'rublon2factor_login_message');

/**
 * Transfers any plugin messages back to the cookie on redirection
 * 
 * @param string $location
 * @param int $status
 * @return string
 */
function rublon2factor_wp_redirect($location, $status = 302) {

	RublonHelper::cookieTransferBack();
	return $location;

}

add_filter('wp_redirect', 'rublon2factor_wp_redirect');

/**
 * Makes sure the plugin is always run before other plugins
 * 
 * @return void
 */
function rublon2factor_plugin_activated_mefirst() {

	RublonHelper::meFirst();

}

add_action('activated_plugin', 'rublon2factor_plugin_activated_mefirst');

/**
 * Main initialization hook
 * 
 * Check if we should start the 2FA authentication or just retrieve the
 * plugin cookies and display current page.
 * 
 * @return void
 */
function rublon2factor_init() {

	if (is_user_logged_in()) {
		$user = wp_get_current_user();
		if ($user) {
			if (RublonHelper::isUserSecured($user) && !RublonHelper::isUserAuthenticated($user)) {
				wp_clear_auth_cookie();
				RublonHelper::authenticateWithRublon($user);
			}
		}
	}
	RublonHelper::cookieTransfer();

}

add_action('init', 'rublon2factor_init');

/**
 * Store WP authentication cookie params in the settings for future use
 * 
 * Since the plugin needs to duplicate the WP cookie settings, we need to
 * store them for future use, as on this stage we do not authenticate
 * the user with the second factor yet - this will happen in a moment.
 * 
 * @param string $auth_cookie Set cookie
 * @param int $expire Whether the cookie is a session or permanent one
 * @param int $expiration Expiration date (timestamp)
 * @param id $user_id Logged in user's WP ID
 * @param string $scheme Whether the cookie is secure
 * @return void
 */
function rublon2factor_store_auth_cookie_params($auth_cookie, $expire, $expiration, $user_id = null, $scheme) {

	if ($user_id) {
		$user = get_user_by('id', $user_id);
		$secure = ($scheme == 'secure_auth');
		if ($user) {
			$secure_logged_in_cookie = apply_filters('secure_logged_in_cookie', false, $user_id, $secure);
			$cookieParams = array(
					'secure' => $secure,
					'remember' => ($expire > 0),
					'logged_in_secure' => $secure_logged_in_cookie,
			);
			$settings = RublonHelper::getSettings();
			$settings['wp_cookie_params'] = $cookieParams;
			$settings['wp_cookie_expiration'] = $expire;
			RublonHelper::saveSettings($settings);
		}
	}

}

add_action('set_auth_cookie', 'rublon2factor_store_auth_cookie_params', 10, 5);

/**
 * Clear Rublon auth cookie on logout
 * 
 * @return void
 */
function rublon2factor_wp_logout() {

	RublonCookies::clearAuthCookie();

}

add_action('wp_logout', 'rublon2factor_wp_logout');

/**
 * Register the fact that the plugin has been activated
 * 
 */
function rublon2factor_plugin_activation() {

	if (RublonHelper::isPluginRegistered() && !RublonHelper::wasPluginEverActivated())
		RublonHelper::registerPluginActivation();

}

register_activation_hook(RUBLON2FACTOR_PLUGIN_PATH, 'rublon2factor_plugin_activation');