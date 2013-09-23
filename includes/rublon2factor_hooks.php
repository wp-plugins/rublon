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
 * Add the authentication by Rublon2Factor.
 *
 * @return WP_User/WP_Error
 */
function rublon2factor_authenticate($user, $username, $password) {

	$systemUser = wp_authenticate_username_password($user, $username, $password);
	if (is_wp_error($systemUser) || !Rublon2FactorHelper::isUserSecured($systemUser)) {
		return $systemUser;
	}

	Rublon2FactorHelper::authenticateWithRublon($systemUser);
}

remove_filter('authenticate', 'wp_authenticate_username_password', 20);
add_filter('authenticate', 'rublon2factor_authenticate', 10, 3);

/**
 * Save the page the user needs to be redirected to after a successful authentication
 * 
 * @param string $redirect_to
 * @return string
 */
function rublon2factor_login_redirect($redirect_to) {

	if (!empty($redirect_to))
		Rublon2FactorHelper::saveReturnPageUrl($redirect_to);
	return $redirect_to;

}

add_filter( 'login_redirect', 'rublon2factor_login_redirect', 10, 3);

/**
 * Display the Rublon messages on the login screen
 * 
 * @param array $message
 */
function rublon2factor_login_message($message) {

	$messages = Rublon2FactorHelper::getMessages();
	if ($messages) {
		foreach ($messages as $message)
			echo '<div class="' . $message['message_type'] . ' fade" style="margin: 0 0 16px 8px; padding: 12px;">' . $message['message'] . '</div>';
	}

}

add_filter('login_message', 'rublon2factor_login_message');