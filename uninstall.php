<?php
/**
 * Code to be run during plug-in uninstall process
 *
 * @package   rublon2factor
 * @author     Rublon Developers http://www.rublon.com
 * @copyright  Rublon Developers http://www.rublon.com
 * @license    http://opensource.org/licenses/gpl-license.php GNU Public License, version 2  
 */

if (!current_user_can('activate_plugins')) {
	exit();
}

if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit();
}

require_once dirname(__FILE__) . '/includes/rublon2factor_helper.php';
require_once dirname(__FILE__) . '/includes/rublon2factor_multisite_helper.php';

/**
 * Perform database and options clean-up before deleting plug-in
 */
function rublon2factor_plugin_uninstall() {

	global $wpdb;

	// Clear settings for all sites
	if (RublonMultisiteHelper::isMultisite()) {
		RublonMultisiteHelper::uninstallMultisite();
	} else {
		delete_option(RublonHelper::RUBLON_SETTINGS_KEY);
		delete_option(RublonHelper::RUBLON_ADDITIONAL_SETTINGS_KEY);
		delete_option(RublonHelper::RUBLON_OTHER_SETTINGS_KEY);
		delete_option(RublonHelper::RUBLON_REGISTRATION_SETTINGS_KEY);
		
		// Bulk delete user meta
		$wpdb->query($wpdb->prepare("DELETE FROM $wpdb->usermeta WHERE meta_key IN (%s, %s, %s)",
			RublonHelper::RUBLON_META_PROFILE_ID,
			RublonHelper::RUBLON_META_USER_PROTTYPE,
			RublonHelper::RUBLON_META_AUTH_CHANGED_MSG
		));

		$wpdb->query($wpdb->prepare("DELETE FROM $wpdb->usermeta WHERE meta_key LIKE %s",
			RublonHelper::RUBLON_META_DEVICE_ID . '%'
		));

// 		$all_user_ids = get_users(array(
// 			'fields' => 'id',
// 		));
// 		foreach ($all_user_ids as $user_id) {
// 			delete_user_meta($user_id, RublonHelper::RUBLON_META_PROFILE_ID);
// 			delete_user_meta($user_id, RublonHelper::RUBLON_META_USER_PROTTYPE);
// 			delete_user_meta($user_id, RublonHelper::RUBLON_META_AUTH_CHANGED_MSG);
// 		}		

	}

}

// Call the clean-up function
rublon2factor_plugin_uninstall();