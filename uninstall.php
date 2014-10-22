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
	exit;
}

if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

require_once dirname(__FILE__) . '/includes/rublon2factor_helper.php';
require_once dirname(__FILE__) . '/includes/rublon2factor_multisite_helper.php';

/**
 * Perform database and options clean-up before deleting plug-in
 */
function rublon2factor_plugin_uninstall() {

	// Clear settings for all sites
	if (RublonMultisiteHelper::isMultisite()) {
		RublonMultisiteHelper::uninstallMultisite();
	} else {
		delete_option(RublonHelper::RUBLON_SETTINGS_KEY);
		delete_option(RublonHelper::RUBLON_ADDITIONAL_SETTINGS_KEY);
		delete_option(RublonHelper::RUBLON_REGISTRATION_SETTINGS_KEY);
		
		$all_user_ids = get_users('fields=id');
		foreach ($all_user_ids as $user_id) {
			delete_user_meta($user_id, RublonHelper::RUBLON_META_PROFILE_ID);
			delete_user_meta($user_id, RublonHelper::RUBLON_META_PROTECTION_TYPE);
			delete_user_meta($user_id, RublonHelper::RUBLON_META_AUTH_CHANGED_MSG);
		}		
	}

}

// Call the clean-up function
rublon2factor_plugin_uninstall();