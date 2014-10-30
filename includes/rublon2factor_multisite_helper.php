<?php
/**
 * Additional helper functions for Rublon for WordPress MU (multisite features)
 *
 * @package   rublon2factor\includes
 * @author     Rublon Developers http://www.rublon.com
 * @copyright  Rublon Developers http://www.rublon.com
 * @license    http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */

/**
 * RublonHelperMultisite class
 *
 * It provides helper functionalities for Rublon2Factor for multisite installation.
 *
 */
class RublonMultisiteHelper extends RublonHelper {		


	/**
	 * Initialize the helper.
	 * 
	 * Initializes the helper and sets up any necessary actions and filters.
	 */
	static public function init() {

		if (self::isMultisite()) {

			// Removes any native WordPress hooks that collide with the Rublon Multisite plugin
			add_action('rublon_admin_init', 'RublonMultisiteHelper::removeHooks', 10, 1);

			// Replaces main site settings with network ones
			add_filter('rublon_get_settings', 'RublonMultisiteHelper::retrieveSettings', 10, 1);

			// Register subproject if this is a multisite network
			// and current site is not registered in Rublon
			add_action('rublon_pre_authenticate', 'RublonMultisiteHelper::checkSubprojectRegistration', 10, 1);
			add_action('rublon_site_registration', 'RublonMultisiteHelper::checkSubprojectRegistration', 10, 1);

			// Save network settings on saving main site settings
			add_action('rublon_save_settings', 'RublonMultisiteHelper::settingsSaved', 10, 2);

		}

	}


	/**
	 * Check if we're in a MU environment.
	 *
	 * @return boolean
	 */
	static public function isMultisite() {

		return (function_exists('is_multisite') && is_multisite());

	}
	
	/**
	 * Check if we're in the Network Admin Panel.
	 * 
	 * @return boolean
	 */
	static public function isNetworkAdmin() {
	
		return is_multisite() && is_network_admin();

	} 
	
	
	/**
	 * Return plugin's network settings.
	 *
	 * @param string $group Settings group
	 * @return array
	 */
	static public function getNetworkSettings($group = '') {
	
		switch ($group) {
			case 'additional':
				$key = self::RUBLON_ADDITIONAL_SETTINGS_KEY;
				break;
			default:
				$key = self::RUBLON_SETTINGS_KEY;
		}
				
		$settings = get_site_option($key);		
		if (!$settings) {
			$settings = array();
		}

		return $settings;
	
	}


	/**
	 * Save plugin's network settings
	 *
	 * @param array $settings Settings to save
	 * @param string $group Settings group
	 */
	static public function saveNetworkSettings($settings, $group = '') {
	
		switch ($group) {
			case 'additional':
				$key = self::RUBLON_ADDITIONAL_SETTINGS_KEY;
				break;
			default:
				$key = self::RUBLON_SETTINGS_KEY;
		}
		update_site_option($key, $settings);

	}


	/**
	 * Check if the network is registered in Rublon.
	 * 
	 * Chceck if the network has been registered in Rublon
	 * and its System Token and Secret Key have been saved.
	 * 
	 * @return boolean
	 */
	static public function isNetworkRegistered() {

		$settings = self::getNetworkSettings();							
		return (!empty($settings) && !empty($settings['rublon_system_token']) && !empty($settings['rublon_secret_key']));

	}


	/**
	 * Perform single site registration.
	 * 
	 * Register one of network's sites in Rublon and save
	 * System Token and Secret Key into the site's individual
	 * options table. 
	 * 
	 * @param WP_User $user WordPress user object
	 * @return boolean True if registration was successful
	 */
	static public function performSiteRegistration() {		
		
		try {
			require_once dirname(__FILE__) . '/libs/RublonImplemented/RublonSubprojectRegistrationWordPress.php';

			// Create a RublonSubprojectRegistration instance
			// and register the site
			$site_registration = new RublonSubprojectRegistrationWordPress();
			$response = $site_registration->register();

			// Save the received settings upon success
			$settings = RublonHelper::getSettings();
			$settings['rublon_system_token'] = $response['system_token'];
			$settings['rublon_secret_key'] = $response['secret_key'];
			RublonHelper::saveSettings($settings);
		} catch (Exception $e) {
			// Cannot register the site
			return false;
		}		
		return !empty($response);

	}	 


	/**
	 * Retrieve list of the network sites.
	 * 
	 * Added for backward compatibility with older WP versions.
	 * 
	 * @param int $main_site_id
	 * @return array
	 */
	static public function getSiteList($main_site_id) {

		$sites = array();
		if (self::isMultisite()) {
			if (function_exists('wp_get_sites')) {
				$sites = wp_get_sites(array(
					'network_id' => $main_site_id,
				));
			} else {
				$sites = get_blog_list(0, 'all');
			}
		}
		return $sites;

	}


	/**
	 * Remove unnecessary hooks.
	 * 
	 * Remove any hooks that are unnecessary when using Rublon for WP MU.
	 */
	static public function removeHooks() {

		if (has_action('personal_options_update', 'send_confirmation_on_profile_email')) {
			remove_action('personal_options_update', 'send_confirmation_on_profile_email');
		}

	}


	/**
	 * Check if a single site registration is needed.
	 *
	 * If the multisite network has been registered
	 * in Rublon, perform a single site registration.
	 *
	 * @param WP_User|null $user (optional) WordPress user
	 * @return void
	 */
	static public function checkSubprojectRegistration($user = null) {

		if (self::isNetworkRegistered()
			&& !is_main_site()
			&& !RublonHelper::isSiteRegistered()) {
			if (!empty($user) && $user instanceof WP_User) {
				$current_user = $user;
				$user_can_register_subproject = true;
			} else {
				$current_user = wp_get_current_user();
				$user_can_register_subproject = is_user_logged_in();
			}
			if ($current_user instanceof WP_User && $user_can_register_subproject) {
				self::performSiteRegistration();
			}
		}		

	}


	/**
	 * Save settings in network settings if we're in the main site
	 * 
	 * @param array $settings
	 * @param string $group
	 */
	static public function settingsSaved($settings, $group = '') {

		if (is_main_site()) {
			self::saveNetworkSettings($settings, $group);
		}

	}


	/**
	 * Retrieve network settings if it's registered in Rublon
	 * 
	 * If the network has been registered, return its settings.
	 * Otherwise pass through the given settings array.
	 * 
	 * @param  array $settings
	 * @return mixed
	 */
	static public function retrieveSettings($settings) {

		if (self::isNetworkRegistered()) {
			$settings = self::getNetworkSettings();
		}
		return $settings;

	}


	static public function uninstallMultisite() {

		$current_blog_id = get_current_blog_id();
		$sites = RublonMultisiteHelper::getSiteList($current_blog_id);
		foreach ($sites as $site) {
			switch_to_blog(intval($site['blog_id']));
			delete_option(RublonHelper::RUBLON_SETTINGS_KEY);
			delete_option(RublonHelper::RUBLON_ADDITIONAL_SETTINGS_KEY);
			delete_option(RublonHelper::RUBLON_REGISTRATION_SETTINGS_KEY);
			$all_user_ids = get_users('fields=id');
			foreach ($all_user_ids as $user_id) {
				delete_user_meta($user_id, RublonHelper::RUBLON_META_PROFILE_ID);
				delete_user_meta($user_id, RublonHelper::RUBLON_META_USER_PROTTYPE);
				delete_user_meta($user_id, RublonHelper::RUBLON_META_AUTH_CHANGED_MSG);
			}
			restore_current_blog();
		}
		
		// Clear network settings
		delete_site_option(RublonHelper::RUBLON_SETTINGS_KEY);
		delete_site_option(RublonHelper::RUBLON_ADDITIONAL_SETTINGS_KEY);
		delete_site_option(RublonHelper::RUBLON_REGISTRATION_SETTINGS_KEY);		

	}


}

// Initialize Rublon multisite plugin helper
add_action('rublon_plugin_pre_init', 'RublonMultisiteHelper::init', 10, 1);
