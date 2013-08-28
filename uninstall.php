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
function rublon2factor_should_config_be_removed()
{
	$settings = Rublon2FactorHelper::getSettings();	
	if (!Rublon2FactorHelper::isActive($settings)) {
		return true;
	}
	
	$uninstall_rublon_config = isset( $settings['uninstall_rublon_config'] ) ? (bool) $settings['uninstall_rublon_config'] : false;
	
	return $uninstall_rublon_config;
}

/**
 * Perform database and options clean-up before deleting plug-in
 */
function rublon2factor_plugin_uninstall()
{
	global $wpdb;
	$user_fields = $wpdb->get_col("SHOW COLUMNS FROM $wpdb->users");
	
	//if ( rublon2factor_should_config_be_removed() ) {
		delete_option('rublon2factor_settings');
		
		if (in_array('rublon_profile_id', $user_fields))
		{
			$wpdb->query("ALTER TABLE $wpdb->users DROP rublon_profile_id");
		}		
	//}
}

// Call the clean-up function
rublon2factor_plugin_uninstall();