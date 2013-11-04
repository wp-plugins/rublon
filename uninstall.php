<?php
/**
 * Code to be run during plug-in uninstall process
 *
 * @package   rublon2factor
 * @author     Rublon Developers http://www.rublon.com
 * @copyright  Rublon Developers http://www.rublon.com
 * @license    http://opensource.org/licenses/gpl-license.php GNU Public License, version 2  
 */

if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit;

require_once(dirname (__FILE__) . '/includes/rublon2factor_helper.php');

/**
 * Checks if this Rublon module configuration(api settings, linking accounts data etc.)
 * should be removed during uninstall.
 *
 * @return boolean
 */
function rublon2factor_should_config_be_removed() {

	if (!Rublon2FactorHelper::isPluginRegistered()) {
		return true;
	}

	$uninstall_rublon_config = isset( $settings['uninstall_rublon_config'] ) ? (bool) $settings['uninstall_rublon_config'] : false;

	return $uninstall_rublon_config;

}

/**
 * Perform database and options clean-up before deleting plug-in
 */
function rublon2factor_plugin_uninstall() {

	delete_option(Rublon2FactorHelper::RUBLON_SETTINGS_KEY);
	delete_option(Rublon2FactorHelper::RUBLON_REGISTRATION_SETTINGS_KEY);

	$all_user_ids = get_users('fields=id');
	foreach ($all_user_ids as $user_id) {
		delete_user_meta($user_id, Rublon2FactorHelper::RUBLON_META_PROFILE_ID);
	}

}

// Call the clean-up function
rublon2factor_plugin_uninstall();