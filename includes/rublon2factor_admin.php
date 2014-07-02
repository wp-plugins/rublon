<?php
/**
 * Code to be run while on administrator panel pages
 *
 * @package   rublon2factor\includes
 * @author     Rublon Developers http://www.rublon.com
 * @copyright  Rublon Developers http://www.rublon.com
 * @license    http://opensource.org/licenses/gpl-license.php GNU Public License, version 2 
 */

/**
 * Add plugin css
 **/
function rublon2factor_admin_css() {

	$currentPluginVersion = RublonHelper::getCurrentPluginVersion();
	
	// check if the site is running WordPress 3.8+, which brought
	// some visual style changes
	$wp_version = get_bloginfo('version');
	$addCompatStyles = false;
	if (version_compare($wp_version, '3.8', 'ge'))
		$addCompatStyles = true;
	
	if (!wp_style_is ('rublon2factor_admin_css', 'registered')) {
		wp_register_style ('rublon2factor_admin_css', RUBLON2FACTOR_PLUGIN_URL . '/assets/css/rublon2factor_admin.css', false, $currentPluginVersion);
		if ($addCompatStyles)
			wp_register_style ('rublon2factor_admin_wp_3.8_plus_css', RUBLON2FACTOR_PLUGIN_URL . '/assets/css/rublon2factor_admin_wp_3.8_plus.css', false, $currentPluginVersion);
	}

	if (did_action ('wp_print_styles'))	{
		wp_print_styles ('rublon2factor_admin_css');
		if ($addCompatStyles)
			wp_print_styles ('rublon2factor_admin_wp_3.8_plus_css');
	}
	else {
		wp_enqueue_style ('rublon2factor_admin_css');
		if ($addCompatStyles)
			wp_enqueue_style ('rublon2factor_admin_wp_3.8_plus_css');
	}

}

/**
 * Create Rublon page in the menu
 */
function rublon2factor_add_settings_page() {

	$title = __('Rublon', 'rublon');
	$settings_page = add_utility_page($title, $title, 'read', 'rublon' , 'rublon2factor_create_settings_page', 'div');
	add_action('admin_print_styles', 'rublon2factor_admin_css');

}

add_action('admin_menu', 'rublon2factor_add_settings_page');

/**
 * Add plugin JS to the administrator pages 
 * 
 */
function rublon2factor_admin_scripts() {

	$currentPluginVersion = RublonHelper::getCurrentPluginVersion();
	wp_enqueue_script('rublon2factor_admin_js', RUBLON2FACTOR_PLUGIN_URL . '/assets/js/rublon-wordpress.js', false, $currentPluginVersion);

}

add_action('admin_enqueue_scripts', 'rublon2factor_admin_scripts');

/**
 * Register plugin settings and redirect to plugin page if this is the first activation
 * 
 */
function rublon2factor_register_settings() {

	// register additional settings
	register_setting('rublon2factor_additional_settings_group', RublonHelper::RUBLON_ADDITIONAL_SETTINGS_KEY);
	add_settings_section('rublon2factor-additional-settings', __('External applications', 'rublon'), null, 'rublon');
	add_settings_field('rublon2factor_disable_xmlrpc', __('XML-RPC', 'rublon'), 'rublon2factor_render_disable_xmlrpc', 'rublon', 'rublon2factor-additional-settings');

	// redirect to plugin page upon first activation
	if (RublonHelper::isPluginRegistered() && !RublonHelper::wasPluginEverActivated())
		RublonHelper::registerPluginActivation();
	if (!RublonHelper::wasPluginEverActivated()) {
		RublonHelper::registerPluginActivation();
		wp_redirect(admin_url(RublonHelper::RUBLON_PAGE));
		exit;
	}

 	if (!RublonHelper::isPluginRegistered()) {
		RublonHelper::newNonce();
 	} 

}

add_action('admin_init', 'rublon2factor_register_settings');

/**
 * Callback for rendering the "Disable XML-RPC" setting on the plugin's settings page
 * 
 */
function rublon2factor_render_disable_xmlrpc() {

	$settings = RublonHelper::getSettings('additional');
	$checked = '';
	if (!empty($settings['disable-xmlrpc']) && $settings['disable-xmlrpc'] == 'on')
		$checked = ' checked';
	if (!empty($checked)) {
		echo '<p class="rublon-xmlrpc-desc">' . __('In order to assure a high level of security, by default Rublon disallows external applications to manage your website by disabling XML-RPC.', 'rublon');
		echo '<br />' . __('Enabling XML-RPC will allow external applications to bypass Rublon security. We strongly recommend that you leave it disabled.', 'rublon') . '</p>';
	} else {
		echo '<p class="rublon-xmlrpc-desc">' . __('In order to assure a high level of security, by default Rublon disallows external applications to manage your website by disabling XML-RPC.', 'rublon');
		echo '<br />' . '<span class="rublon-bold rublon-red">' . __('XML-RPC is currently enabled, which allows external applications to bypass Rublon security. We strongly recommend that you disable it.', 'rublon') . '</span>' . '</p>';
	}
	echo '<input type="hidden" name="' . RublonHelper::RUBLON_ADDITIONAL_SETTINGS_KEY . '[disable-xmlrpc]" value="off" /> ';
	echo '<input type="checkbox" name="' . RublonHelper::RUBLON_ADDITIONAL_SETTINGS_KEY . '[disable-xmlrpc]" value="on"' . $checked . ' /> ';
	_e('Disable XML-RPC', 'rublon');

}

/**
 * Display the Rublon page
 * 
 */
function rublon2factor_create_settings_page() {
	?>
<div class="wrap">
	<div id="rublon2factor_page" class="rublon2factor_settings">
		<h2 class="rublon-header">
			<?php _e('Rublon', 'rublon'); ?>
		</h2>

		<?php

			// necessary, otherwise "updated" messages won't be visible
			settings_errors();

			if ((!RublonHelper::isPluginRegistered() && current_user_can('manage_options')) || RublonHelper::isPluginRegistered()) {
				echo new RublonGUI;
			} else {
				$admin_email = get_option('admin_email');
				$admin_url = admin_url(RublonHelper::RUBLON_PAGE);
				echo '<div class="updated rublon-activation-mere-user">'
					. __('Rublon will be available to you once your administrator protects his account.', 'rublon')
					. ' <strong>' . __('Contact your administrator', 'rublon') . ':' . ' <a href="mailto:' . $admin_email . '?subject=' . __('Protect your account with Rublon', 'rublon') . '&body=' . sprintf(__('Hey, could you please protect your account with Rublon on %s? I want to protect my account, but I won\'t be able to do this until an administrator will.', 'rublon'), $admin_url) . '">' . $admin_email . '</a></strong>'
					. '</div>';
				echo '<div class="rublon-button-header">'
					. __('Since your account is protected by a password only, it can be accessed from any device in the world. Rublon protects your account from sign ins from unknown devices, even if your password gets stolen.', 'rublon')
					. ' ' . __('Learn more at <a href="http://rublon.com" target="_blank">www.rublon.com</a>.', 'rublon') . '</div>';
				echo '<div class="rublon-clear"></div>';
			}

			if (current_user_can('manage_options')): // START_BLOCK: Is user authorized to manage plugins? ?>

				<form method="post" action="options.php" id="rublon-plugin-additional-settings">
				<?php

					settings_fields('rublon2factor_additional_settings_group');
					do_settings_sections('rublon');
					submit_button();

				?>
				</form>
			<?php

			endif; // END_BLOCK: Is user authorized to manage plugins? ?>
	</div>
</div>
<?php 
}

/**
 * Display plugin warnings and the registration ribbon
 */
function rublon2factor_no_settings_warning() {

	global $pagenow;

	$screen = get_current_screen();

	if ($pagenow == 'plugins.php'
		|| (!empty($screen->base) && $screen->base == 'toplevel_page_rublon')) {
	
		if (!version_compare(phpversion(), RUBLON2FACTOR_REQUIRE_PHPVERSION, 'ge')) {
			echo "<div class='error'><p><strong>" . __('Warning! The PHP version of your server is too old to run Rublon. Please upgrade your server\'s PHP version.', 'rublon') . '</strong></p><p>' . __('Required PHP version:', 'rublon') . ' <strong>'.RUBLON2FACTOR_REQUIRE_PHPVERSION.' ' . __('(or above)', 'rublon') . '</strong></p><p>' . __('Your PHP version:', 'rublon') . ' <strong>' . phpversion() . '</strong></p></div>';
		}

		if (!function_exists('curl_init')) {
			echo "<div class='error'><p><strong>" . __('Warning! The cURL library has not been found on this server.', 'rublon') . '</strong> ' . __('It is a crucial component of the Rublon plugin and its absence will prevent it from working properly. Please have the cURL library installed or consult your server administrator about it.', 'rublon') . '</p></div>';
		}
	
		if (!RublonHelper::isPluginRegistered() && $pagenow == 'plugins.php'
			&& !empty($screen->base) && $screen->base == 'plugins') {
			$rublonGUI = new RublonGUI;
			echo $rublonGUI->registrationRibbon();
		}

	}

}

add_action('admin_notices', 'rublon2factor_no_settings_warning');


/**
 * Display a checkbox for disabling account security for users other than the logged in admin
 * 
 * @param WP_User $user Object containing the user whose profile is being displayed
 */
function rublon2factor_add_users_2factor_disabler($user) {

	if (!empty($user) && RublonHelper::isPluginRegistered()) {

		if (RublonHelper::isUserSecured($user)) {
			?><h3><?php _e('Security', 'rublon') ?></h3>
				<table class="form-table">
					<tr>
						<th><?php _e('Rublon', 'rublon'); ?></th>
						<td>
							<label for="rublon2factor_disable_users_security">
								<input name="rublon2factor_disable_users_security" type="checkbox" id="rublon2factor_disable_users_security" value="false" />
								<?php _e('Disable Rublon protection for this account', 'rublon') ?>
							</label>
						</td>
					</tr>
				</table><?php
		}

	}

}

add_action('edit_user_profile', 'rublon2factor_add_users_2factor_disabler');

/**
 * Disable the currently updated user's Rublon protection 
 *  
 * @param int $user_id User id
 */
function rublon2factor_disable_users_2factor($user_id) {

	if (!empty($user_id) && !empty($_POST['rublon2factor_disable_users_security'])) {
		$rublonProfileId = get_user_meta($user_id, RublonHelper::RUBLON_META_PROFILE_ID, true);
		$wp_user = get_user_by('id', $user_id);
		if ($wp_user && !empty($rublonProfileId))
			RublonHelper::disconnectRublon2Factor($wp_user, $rublonProfileId);
	}

}

add_action('edit_user_profile_update', 'rublon2factor_disable_users_2factor');

/**
 * Display Rublon button box in the user's profile section
*/
function rublon2factor_secure_account_buttons() {
	
	if(RublonHelper::isPluginRegistered()): // START_BLOCK: Is the plugin registered?
		?>
<h3>
<?php
	_e('Rublon', 'rublon'); 
?>
</h3>
<table class="form-table">
<tr>
	<th>
	</th>
	<td>
<?php

	echo new RublonGUI;

?>
	</td>	
</tr>
</table>
<?php
	endif; // END_BLOCK: Is the plugin registered?
}

add_action( 'show_user_profile', 'rublon2factor_secure_account_buttons');

/**
 *
 * Displays messages in admin pages
 *
 */
function rublon2factor_add_update_message() {

	$messages = RublonHelper::getMessages();
	if ($messages) {
		foreach ($messages as $message)
			echo "<div class='". $message['type'] ." fade'><p>" . $message['message'] . "</p></div>";
	}

}

add_action('admin_notices', 'rublon2factor_add_update_message');

/**
 * Add a Rublon column to the admin's user list
 * 
 * @param array $columns Existing user list columns
 * @return array
 */
function rublon2factor_add_userlist_columns($columns) {

	$new_columns = array();
	foreach ($columns as $k => $v) {
		$new_columns[$k] = $v;
		if ($k == 'username') {
			$new_columns['rublon2factor_status'] = __('Rublon', 'rublon');
		}
	}
	return $new_columns;

}

add_filter('manage_users_columns', 'rublon2factor_add_userlist_columns');

/**
 * Handle the additional Rublon columns for a given user
 * 
 * @param mixed $value Current column value
 * @param string $column_name Column name
 * @param int $user_id User's ID 
 * @return string
 */
function rublon2factor_manage_rublon_columns($value, $column_name, $user_id) {

	if ($column_name == 'rublon2factor_status') {
		$wp_user = get_user_by('id', $user_id);
		
		if (!empty($wp_user)) {
						
			$wp_user_id = RublonHelper::getUserId($wp_user);
			
			$rublonProfileId = get_user_meta($wp_user_id, RublonHelper::RUBLON_META_PROFILE_ID, true);
			
			$lang = RublonHelper::getBlogLanguage();
			
			if (!empty($rublonProfileId))
				$value = '<a href="http://rublon.com' . ($lang != 'en' ? '/' . $lang . '/' : '') . '" target="_blank"><img style="margin-top: 1px" src="' . RUBLON2FACTOR_PLUGIN_URL . '/assets/images/R_32x32.png' . '" title="' . __('Account protected by Rublon', 'rublon') . '" /></a>';
		}
	}
	return $value;

}

add_filter('manage_users_custom_column', 'rublon2factor_manage_rublon_columns', 10, 3);

/**
 * Add the Rublon icon and menu to the toolbar
 * 
 */
function rublon2factor_modify_admin_toolbar() {

	global $wp_admin_bar;
	
	if (RublonHelper::isCurrentUserSecured()) {

		// add a Rublon icon to the my-account node
		$my_account = $wp_admin_bar->get_node('my-account');
		$my_account->title = $my_account->title . '<img id="rublon-toolbar-logo" src="' . RUBLON2FACTOR_PLUGIN_URL . '/assets/images/R_16x16.png' . '" />';
 		$wp_admin_bar->remove_node('my-account');
 		$wp_admin_bar->add_node($my_account);

 		// remove all my-account subnodes except user-info
 		$nodes = $wp_admin_bar->get_nodes();
 		$removed_nodes = array();
 		foreach ($nodes as $node) {
 			if (!empty($node->title) && $node->parent == 'user-actions' && $node->id != 'user-info') {
 				array_push($removed_nodes, $node);
 				$wp_admin_bar->remove_node($node->id);
 			}
 		}
 		
 		// add Rublon node
 		$wp_admin_bar->add_node(array(
 				'id' => 'rublon',
 				'title' => __('Protected by Rublon', 'rublon'),
 				'href' => admin_url(RublonHelper::RUBLON_PAGE),
 				'parent' => 'user-actions',
 		
 		));
 		
		// restore all removed nodes
 		foreach ($removed_nodes as $node) {
 			$wp_admin_bar->add_node($node);
 		}

 	}
	

}

add_action( 'wp_before_admin_bar_render', 'rublon2factor_modify_admin_toolbar', 999);

/**
 * Include the Rublon css and JS to the frontend and login page
 * 
 */
function rublon2factor_add_frontend_files() {

	$currentPluginVersion = RublonHelper::getCurrentPluginVersion();
	wp_enqueue_style('rublon2factor_frontend', RUBLON2FACTOR_PLUGIN_URL . '/assets/css/rublon2factor_frontend.css', false, $currentPluginVersion);
	if (is_rtl()) {
		wp_enqueue_style('rublon2factor_rtl', RUBLON2FACTOR_PLUGIN_URL . '/assets/css/rtl.css', false, $currentPluginVersion);
	}
	wp_enqueue_script('rublon2factor_admin_js', RUBLON2FACTOR_PLUGIN_URL . '/assets/js/rublon-wordpress.js', false, $currentPluginVersion);

}

add_action('wp_enqueue_scripts', 'rublon2factor_add_frontend_files');
add_action('login_enqueue_scripts', 'rublon2factor_add_frontend_files');

/**
 * Add the Rublon Seal to the login page
 * 
 */
function rublon2factor_modify_login_form() {

	$rublonSealUrl = 'https://rublon.com/img/rublon_seal_79x30.png';
	$lang = RublonHelper::getBlogLanguage();
	echo '<div style="display: none;" id="rublon-seal"><div class="rublon-seal-link"><a href="http://rublon.com' . (($lang != 'en') ? ('/' . $lang . '/') : '') . '" target="_blank" title="' . __('Rublon Two-Factor Authentication', 'rublon') . '">'
		. '<img class="rublon-seal-image" src="' . $rublonSealUrl .  '" alt="' . __('Rublon Two-Factor Authentication', 'rublon') . '" /></a></div></div>';
	echo '<script>//<![CDATA[
		if (RublonWP)
			RublonWP.showSeal();
	//]]></script>';

}

add_action('login_footer', 'rublon2factor_modify_login_form');