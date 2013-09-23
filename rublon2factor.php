<?php
/*
Plugin Name: Rublon
Text Domain: rublon2factor
Plugin URI: http://wordpress.org/plugins/rublon/
Description: Rublon protects your accounts from sign ins from unknown devices, even if your password gets stolen. It's a totally seamless way of securing your online accounts and the easiest two-factor authentication solution in the world.
Version: 1.1.9
Author: Rublon
Author URI: https://rublon.com
License:http://opensource.org/licenses/gpl-license.php GNU Public License, version 2 
*/

/*
 * Define some constants for future usage
*/
define ('RUBLON2FACTOR_PLUGIN_URL', plugins_url () . '/' . basename (dirname (__FILE__)));
define ('RUBLON2FACTOR_BASE_PATH', dirname (plugin_basename (__FILE__)));
define ('RUBLON2FACTOR_PLUGIN_PATH', __FILE__);
define ('RUBLON2FACTOR_NOTIFY_URL', 'https://code.rublon.com/issue_notifier/wp_notify');
define ('RUBLON2FACTOR_REQUIRE_PHPVERSION', '5.2.17');

/**
 * Ensure proper version migration
**/

function rublon2factor_add_settings_link ($links, $file) {

	static $rublon2factor_plugin = null;

	if (is_null ($rublon2factor_plugin))
	{
		$rublon2factor_plugin = plugin_basename (__FILE__);
	}

	if ($file == $rublon2factor_plugin)
	{
		$settings_link = '<a href="plugins.php?page=rublon">' . __('Settings', 'rublon2factor') . '</a>';
		array_unshift ($links, $settings_link);
	}
	return $links;

}

add_filter ('plugin_action_links', 'rublon2factor_add_settings_link', 10, 2);

/*
 * Include plug-in files
*/
require_once(dirname (__FILE__) . '/includes/rublon2factor_helper.php');
require_once(dirname (__FILE__) . '/includes/rublon2factor_callback.php');
require_once(dirname (__FILE__) . '/includes/rublon2factor_initialization.php');
require_once(dirname (__FILE__) . '/includes/rublon2factor_admin.php');
require_once(dirname (__FILE__) . '/includes/rublon2factor_hooks.php');

/*
 * Include Rublon PHP libraries
*/
require_once(dirname (__FILE__) . '/includes/libs/Rublon/RublonConsumer.php');
require_once(dirname (__FILE__) . '/includes/libs/Rublon/RublonService2Factor.php');
require_once(dirname (__FILE__) . '/includes/libs/Rublon/HTML/RublonConsumerScript.php');
require_once(dirname (__FILE__) . '/includes/libs/Rublon/HTML/RublonButton.php');
require_once(dirname (__FILE__) . '/includes/libs/Rublon/HTML/RublonHTMLHelper.php');
require_once(dirname (__FILE__) . '/includes/libs/RublonConsumerRegistration/RublonConsumerRegistrationTemplate.php');
require_once(dirname (__FILE__) . '/includes/libs/RublonConsumerRegistration/RublonConsumerRegistration.php');


// Initialize rublon2factor plug-in
add_action ('init', 'rublon2factor_init', 9);