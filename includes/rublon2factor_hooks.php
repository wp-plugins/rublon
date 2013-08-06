<?php
/**
 * Code to be run while on login page
 *
 * @package   rublon2factor\includes
 * @author     Adips Sp. z o.o.
 * @copyright  Adips Sp. z o.o.
 * @license    http://opensource.org/licenses/gpl-license.php GNU Public License, version 2 
 */


/**
 * Add the authentication by Rublon2Factor.
 *
 * @return WP_User/WP_Error
 */
remove_filter('authenticate', 'wp_authenticate_username_password', 20);
add_filter('authenticate', 'rublon2factor_authenticate', 10, 3);
function rublon2factor_authenticate($user, $username, $password)
{
	$systemUser = wp_authenticate_username_password($user, $username, $password);
	if (is_wp_error($systemUser) || !Rublon2FactorHelper::isUserSecured($systemUser)) {
		return $systemUser;
	}

	Rublon2FactorHelper::authenticateWithRublon($systemUser);
}