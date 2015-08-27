<?php
/*
Plugin Name: Rublon Account Security
Text Domain: rublon
Plugin URI: http://wordpress.org/plugins/rublon/
Description: Rublon instantly protects all accounts with effortless, email-based two-factor authentication. Works out-of-the-box, no configuration or training needed. Install the Rublon mobile app on your phone for more security and control. No more tokens or one-time passwords. Just click a link (email) or scan a Rublon Code (phone) to confirm your identity. Set up trusted devices that let you log in without having to confirm your identity.
Version: 3.1.1
Author: Rublon
Author URI: https://rublon.com
License: http://opensource.org/licenses/gpl-license.php GNU Public License, version 2 
*/


/*
 * Define some constants for future usage
*/
define('RUBLON2FACTOR_PLUGIN_URL', plugins_url() . '/' . basename(dirname(__FILE__)));
define('RUBLON2FACTOR_BASE_PATH', dirname(plugin_basename(__FILE__)));
define('RUBLON2FACTOR_PLUGIN_PATH', __FILE__);

/**
 * Ensure proper version migration
**/

function rublon2factor_add_settings_link($links, $file) {

	static $rublon2factor_plugin = null;

	if (is_null($rublon2factor_plugin)) {
		$rublon2factor_plugin = plugin_basename(__FILE__);
	}

	if ($file == $rublon2factor_plugin) {
		$settings_link = '<a href="admin.php?page=rublon">' . __('Settings', 'rublon') . '</a>';
		array_unshift($links, $settings_link);
	}
	return $links;

}

add_filter('plugin_action_links', 'rublon2factor_add_settings_link', 10, 2);


// For compatibility with version 3.5.x
if (!function_exists('wp_get_session_token')) {
    function wp_get_session_token() {
        $cookie = wp_parse_auth_cookie( '', 'logged_in' );        
        return ! empty( $cookie['token'] ) ? $cookie['token'] : '';
    }
}

require_once dirname(__FILE__) . '/includes/libs/RublonImplemented/Rublon2FactorWordPress.php';
require_once dirname(__FILE__) . '/includes/libs/RublonImplemented/Rublon2FactorCallbackWordPress.php';
require_once dirname(__FILE__) . '/includes/libs/RublonImplemented/Rublon2FactorGUIWordPress.php';

/*
 * Include plug-in files
*/
require_once dirname(__FILE__) . '/includes/classes/RublonFlashMessage.php';
require_once dirname(__FILE__) . '/includes/classes/features/RublonFeature.php';
require_once dirname(__FILE__) . '/includes/classes/RublonRolesProtection.php';
require_once dirname(__FILE__) . '/includes/classes/confirmations/RublonConfirmations.php';
require_once dirname(__FILE__) . '/includes/rublon2factor_helper.php';
require_once dirname(__FILE__) . '/includes/rublon2factor_multisite_helper.php';
require_once dirname(__FILE__) . '/includes/classes/class-rublon-transients.php';
require_once dirname(__FILE__) . '/includes/rublon2factor_cookies.php';
require_once dirname(__FILE__) . '/includes/rublon2factor_requests.php';
require_once dirname(__FILE__) . '/includes/rublon2factor_initialization.php';
require_once dirname(__FILE__) . '/includes/rublon2factor_admin.php';
require_once dirname(__FILE__) . '/includes/rublon2factor_hooks.php';

/*
 * Include Rublon PHP libraries
*/
require_once dirname(__FILE__) . '/includes/libs/RublonConsumerRegistration/RublonConsumerRegistrationTemplate.php';
require_once dirname(__FILE__) . '/includes/libs/RublonImplemented/RublonConsumerRegistrationWordPress.php';

// Initialize rublon2factor plug-in
add_action('plugins_loaded', 'rublon2factor_plugins_loaded', 9);


