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
 * Adds Settings CSS
 **/
function rublon2factor_admin_css ()
{
	
	if (!wp_style_is ('rublon2factor_admin_css', 'registered')) {
		$currentPluginVersion = Rublon2FactorHelper::getCurrentPluginVersion();
		wp_register_style ('rublon2factor_admin_css', RUBLON2FACTOR_PLUGIN_URL . '/assets/css/rublon2factor_admin.css', false, $currentPluginVersion);
	}

	if (did_action ('wp_print_styles'))	{
		wp_print_styles ('rublon2factor_admin_css');
	}
	else {
		wp_enqueue_style ('rublon2factor_admin_css');
	}
}

/**
 * Creates settings submenu page
 */
function rublon2factor_add_settings_page()
{
	$title = __('Rublon', 'rublon2factor');
	$settings_page = add_menu_page($title, $title, 'read', 'rublon' , 'rublon2factor_create_settings_page', 'div', 81);
	add_action ('admin_print_styles', 'rublon2factor_admin_css');
}
add_action('admin_menu', 'rublon2factor_add_settings_page');

/**
 * Registers each plugin option
*/
function rublon2factor_register_settings()
{
	register_setting('rublon2factor_settings_group', Rublon2FactorHelper::RUBLON_SETTINGS_KEY, 'rublon2factor_validate_settings');
	register_setting('rublon2factor_settings_group', Rublon2FactorHelper::RUBLON_REGISTRATION_SETTINGS_KEY);
}
add_action('admin_init', 'rublon2factor_register_settings');

/**
 * Validates Rublon2Factor settings
*/
function rublon2factor_validate_settings($settings)
{
	$action = RublonConsumerRegistration::ACTION_INITIALIZE;
	if (isset($_POST[$action])) {
		Rublon2FactorHelper::consumerRegistrationAction($action);
		exit;
	}
	
	// Prevent second call for the same settings
	static $found_error = false;

	$settings['uninstall_rublon_config'] = (bool) $settings['uninstall_rublon_config'];
	$previous_settings = Rublon2FactorHelper::getSettings();
	
	if (!$found_error AND (empty($settings['rublon_system_token']) OR empty($settings['rublon_secret_key'])))
	{
		add_settings_error(Rublon2FactorHelper::RUBLON_SETTINGS_KEY, 'empty_rublon2factor_settings', __('In order to activate Rublon please provide "System Token" and "Secret Key".', 'rublon2factor'));
		$found_error = true;
		return $previous_settings;
	} else {
		if (!Rublon2FactorHelper::verifyConsumerSettings($settings)) {
			add_settings_error(Rublon2FactorHelper::RUBLON_SETTINGS_KEY, 'invalid_rublon2factor_settings', __('Invalid "System Token" or "Secret Key". Please contact us at <a href="mailto:support@rublon.com">support@rublon.com</a>.', 'rublon2factor'));
			return $previous_settings;
		}
	}

	return $settings;
}

/**
 * Displays settings page
 */
function rublon2factor_create_settings_page() {
	?>
<div class="wrap">
	<div id="rublon2factor_page" class="rublon2factor_settings">
		<h2 class="rublon-header">
			<?php _e('Rublon', 'rublon2factor'); ?>
		</h2>

			<?php	
				$settings = Rublon2FactorHelper::getSettings();
			?>

			<?php if (!Rublon2FactorHelper::isActive($settings)): // START_BLOCK: Is Rublon active? ?>

			<?php
				$current_user = wp_get_current_user();
				if (user_can($current_user, 'manage_options')): // START_BLOCK: Is user authorized to manage plugins?
			?>

			<form method="post" action="options.php" id="rublon-plugin-admin-activation">
			<?php settings_fields('rublon2factor_settings_group');
			
				echo '<div class="updated rublon-activation-mere-user">'
					. __('Before any of your users will be able to use Rublon, you or another administrator needs to protect his account first.', 'rublon2factor')
					. '</div>';
				echo '<div class="rublon-button-header">'
					. __('Since your account is protected by a password only, it can be accessed from any device in the world. Rublon protects your account from sign ins from unknown devices, even if your password gets stolen.', 'rublon2factor')
					. ' ' . __('Learn more at <a href="http://rublon.com" target="_blank">www.rublon.com</a>.', 'rublon2factor') . '</div>';
 				echo '<div class="rublon-button-content">';
    			echo Rublon2FactorHelper::constructRublonButton(__('Protect your account', 'rublon2factor'), 'document.getElementById(\'rublon-plugin-admin-activation\').submit();return false;');
    			echo Rublon2FactorHelper::appInfoBox(false);
				echo '</div><div class="rublon-clear"></div>';
			
			?>

			<input style="display: none !important;" type="hidden" name="<?php echo RublonConsumerRegistration::ACTION_INITIALIZE ?>" value="<?php _e('Activate Rublon and protect your account', 'rublon2factor') ?>" />

			</form>
			<?php else: // ELSE_BLOCK: Is user authorized to manage plugins?

				$admin_email = get_option('admin_email');
				$admin_url = admin_url('admin.php?page=rublon');
				echo '<div class="updated rublon-activation-mere-user">'
					. __('Rublon will be available to you once your administrator protects his account.', 'rublon2factor')
					. ' <strong>' . __('Contact your administrator', 'rublon2factor') . ':' . ' <a href="mailto:' . $admin_email . '?subject=' . __('Protect your account with Rublon', 'rublon2factor') . '&body=' . sprintf(__('Hey, could you please protect your account with Rublon on %s? I want to protect my account, but I won\'t be able to do this until an administrator will.', 'rublon2factor'), $admin_url) . '">' . $admin_email . '</a></strong>'
					. '</div>';
				echo '<div class="rublon-button-header">'
					. __('Since your account is protected by a password only, it can be accessed from any device in the world. Rublon protects your account from sign ins from unknown devices, even if your password gets stolen.', 'rublon2factor')
					. ' ' . __('Learn more at <a href="http://rublon.com" target="_blank">www.rublon.com</a>.', 'rublon2factor') . '</div>';
				echo '<div class="rublon-clear"></div>';
			
			?>

			<?php endif; // END_BLOCK: Is user authorized to manage plugins? ?>

			<?php else: // ELSE_BLOCK: Is Rublon active? ?>
			
			<?php if (Rublon2FactorHelper::isCurrentUserSecured()) {

				echo '<div class="rublon-button-header">'
					. __('<strong>Your account is protected by Rublon</strong>. It can be accessed from your Trusted Devices only.', 'rublon2factor')
					. ' ' . __('Learn more at <a href="http://rublon.com" target="_blank">www.rublon.com</a>.', 'rublon2factor') . '</div>';
				echo '<div class="rublon-button-content">';
				Rublon2FactorHelper::addDisableAccountSecurityButton('rublon');
				echo '</div><div class="rublon-clear"></div>';

			} else {

				echo '<div class="rublon-button-header">'
					. __('Since your account is protected by a password only, it can be accessed from any device in the world. Rublon protects your account from sign ins from unknown devices, even if your password gets stolen.', 'rublon2factor')
					. ' ' . __('Learn more at <a href="http://rublon.com" target="_blank">www.rublon.com</a>.', 'rublon2factor') . '</div>';
 				echo '<div class="rublon-button-content">';
    			Rublon2FactorHelper::addSecureAccountButton('rublon');
    			echo Rublon2FactorHelper::appInfoBox();
				echo '</div><div class="rublon-clear"></div>';
			
			} ?>

			<?php endif; // END_BLOCK: Is Rublon active? ?>
	</div>
</div>
<?php 
}

/**
 * Displays a warning message if Rublon2Factor settings are not set.
 */
function rublon2factor_no_settings_warning()
{
	global $pagenow;

	$screen = get_current_screen();

	if ($pagenow == 'plugins.php' && !version_compare(phpversion(), RUBLON2FACTOR_REQUIRE_PHPVERSION, 'ge')) {
		echo "<div class='error'><p><strong>" . __('Warning! The PHP version of your server is too old to run Rublon. Please upgrade your server\'s PHP version.', 'rublon2factor') . '</strong></p><p>' . __('Required PHP version:', 'rublon2factor') . ' <strong>'.RUBLON2FACTOR_REQUIRE_PHPVERSION.' ' . __('(or above)', 'rublon2factor') . '</strong></p><p>' . __('Your PHP version:', 'rublon2factor') . ' <strong>' . phpversion() . '</strong></p></div>';
	}

	if ($pagenow == 'plugins.php' && !function_exists('curl_init')) {
		echo "<div class='error'><p><strong>" . __('Warning! The cURL library has not been found on this server.', 'rublon2factor') . '</strong> ' . __('It is a crucial component of the Rublon plugin and its absence will prevent it from working properly. Please have the cURL library installed or consult your server administrator about it.', 'rublon2factor') . '</p></div>';
	}
	
	if ( $pagenow == 'plugins.php' AND !Rublon2FactorHelper::isActive(Rublon2FactorHelper::getSettings()) && $screen->base == 'plugins') {
		Rublon2FactorHelper::activationRibbon();
	}
}
add_action('admin_notices', 'rublon2factor_no_settings_warning');

/**
 * Includes the Rublon JS Library on User Profile page
 */
function rublon2factor_add_script_on_profile_page()
{
	global $pagenow;

	if($pagenow == 'profile.php' || ($pagenow == 'admin.php' && get_current_screen()->base == 'toplevel_page_rublon'))
	{
		Rublon2FactorHelper::addScript();
	}
}
add_action('admin_head', 'rublon2factor_add_script_on_profile_page');

/**
 * Displays the checkbox for disabling account security for users other than the logged in admin
 * 
 * @param WP_User $user Object containing the user whose profile is being displayed
 */
function rublon2factor_add_users_2factor_disabler($user) {

	if (!empty($user) && Rublon2FactorHelper::isActive(Rublon2FactorHelper::getSettings())) {

		if (Rublon2FactorHelper::isUserSecured($user)) {
			?><h3><?php _e('Security', 'rublon2factor') ?></h3>
				<table class="form-table">
					<tr>
						<th><?php _e('Rublon', 'rublon2factor'); ?></th>
						<td>
							<label for="rublon2factor_disable_users_security">
								<input name="rublon2factor_disable_users_security" type="checkbox" id="rublon2factor_disable_users_security" value="false" />
								<?php _e('Disable Rublon protection for this account', 'rublon2factor') ?>
							</label>
						</td>
					</tr>
				</table><?php
		}

	}

}

add_action('edit_user_profile', 'rublon2factor_add_users_2factor_disabler');

/**
 * Disable the currently updated user's Rublon 2factor 
 *  
 * @param int $user_id User id
 */
function rublon2factor_disable_users_2factor($user_id) {

	if (!empty($user_id) && !empty($_POST['rublon2factor_disable_users_security'])) {
		$rublonProfileId = get_user_meta($user_id, Rublon2FactorHelper::RUBLON_META_PROFILE_ID, true);
		$wp_user = get_user_by('id', $user_id);
		if ($wp_user && !empty($rublonProfileId))
			Rublon2FactorHelper::disconnectRublon2Factor($wp_user, $rublonProfileId);
	}

}

add_action('edit_user_profile_update', 'rublon2factor_disable_users_2factor');

/**
 * Displays Rublon secure account button
*/
function rublon2factor_secure_account_buttons()
{
	
	if(Rublon2FactorHelper::isActive(Rublon2FactorHelper::getSettings()))
	{
		?>
<h3>
<?php
	_e('Rublon', 'rublon2factor'); 
?>
</h3>
<table class="form-table">
<tr>
	<th>
	</th>
	<td>
<?php 
if ( !Rublon2FactorHelper::isCurrentUserSecured() ) { 	

	echo '<div class="rublon-button-header">'
		. __('Since your account is protected by a password only, it can be accessed from any device in the world. Rublon protects your account from sign ins from unknown devices, even if your password gets stolen.', 'rublon2factor')
		. ' ' . __('Learn more at <a href="http://rublon.com" target="_blank">www.rublon.com</a>.', 'rublon2factor') . '</div>';
 	echo '<div class="rublon-button-content">';
    Rublon2FactorHelper::addSecureAccountButton();
    echo Rublon2FactorHelper::appInfoBox();
	echo '</div><div class="rublon-clear"></div>';
 	
} else {	
	
	echo '<div class="rublon-button-header">'
		. __('<strong>Your account is protected by Rublon</strong>. It can be accessed from your Trusted Devices only.', 'rublon2factor')
		. ' ' . __('Learn more at <a href="http://rublon.com" target="_blank">www.rublon.com</a>.', 'rublon2factor') . '</div>';
	echo '<div class="rublon-button-content">';
	Rublon2FactorHelper::addDisableAccountSecurityButton();
	echo '</div><div class="rublon-clear"></div>';

}
?>
	</td>	
</tr>
</table>
<?php
	}
}
add_action( 'show_user_profile', 'rublon2factor_secure_account_buttons');

/**
 *
 * Displays messages in user profile
 *
 */
function rublon2factor_add_update_message() {
	$messages = Rublon2FactorHelper::getMessages();
	
	if ($messages) {
		foreach ($messages as $message)
			echo "<div id='rublon-warning' class='". $message['message_type'] ." fade'><p>" . $message['message'] . "</p></div>";
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
			$new_columns['rublon2factor_status'] = __('Rublon', 'rublon2factor');
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
						
			$wp_user_id = Rublon2FactorHelper::getUserId($wp_user);
			
			$rublonProfileId = get_user_meta($wp_user_id, Rublon2FactorHelper::RUBLON_META_PROFILE_ID, true);
			
			$lang = Rublon2FactorHelper::getBlogLanguage();
			
			if (!empty($rublonProfileId))
				$value = '<a href="http://rublon.com' . ($lang != 'en' ? '/' . $lang . '/' : '') . '" target="_blank"><img style="margin-top: 1px" src="' . RUBLON2FACTOR_PLUGIN_URL . '/assets/images/R_32x32.png' . '" title="' . __('Account protected by Rublon', 'rublon2factor') . '" /></a>';
		}
	}
	return $value;

}

add_filter('manage_users_custom_column', 'rublon2factor_manage_rublon_columns', 10, 3);

function rublon2factor_modify_admin_toolbar() {

	global $wp_admin_bar;
	
	if (Rublon2FactorHelper::isCurrentUserSecured()) {

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
 				'title' => __('Protected by Rublon', 'rublon2factor'),
 				'href' => admin_url('admin.php?page=rublon'),
 				'parent' => 'user-actions',
 		
 		));
 		
		// restore all removed nodes
 		foreach ($removed_nodes as $node) {
 			$wp_admin_bar->add_node($node);
 		}

 	}
	

}

add_action( 'wp_before_admin_bar_render', 'rublon2factor_modify_admin_toolbar', 999);

function rublon2factor_add_frontend_styles() {

	$currentPluginVersion = Rublon2FactorHelper::getCurrentPluginVersion();
	wp_enqueue_style('rublon2factor_frontend', RUBLON2FACTOR_PLUGIN_URL . '/assets/css/rublon2factor_frontend.css', false, $currentPluginVersion);

}

add_action('wp_enqueue_scripts', 'rublon2factor_add_frontend_styles');
add_action('login_enqueue_scripts', 'rublon2factor_add_frontend_styles');

function rublon2factor_modify_login_form() {

	$rublonSealUrl = 'http://rublon.com/img/rublon_seal_79x30.png';
	$lang = Rublon2FactorHelper::getBlogLanguage();
	echo '<div style="display: none;" id="rublon-seal"><div class="rublon-seal-link"><a href="http://rublon.com' . (($lang != 'en') ? ('/' . $lang . '/') : '') . '" target="_blank" title="' . __('Rublon Two-Factor Authentication', 'rublon2factor') . '">'
		. '<img src="' . $rublonSealUrl .  '" alt="' . __('Rublon Two-Factor Authentication', 'rublon2factor') . '" /></a></div></div>';
	echo '<script>//<![CDATA[
		var rublonSeal = document.getElementById(\'rublon-seal\');
		rublonSeal.parentNode.removeChild(rublonSeal);
		document.querySelector(\'#loginform p.submit\').appendChild(rublonSeal);
		rublonSeal.style.display = \'block\';
	//]]></script>';

}

add_action('login_footer', 'rublon2factor_modify_login_form');