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
	if (!wp_style_is ('rublon2factor_admin_css', 'registered'))
	{
		wp_register_style ('rublon2factor_admin_css', RUBLON2FACTOR_PLUGIN_URL . "/assets/css/rublon2factor_admin.css");
	}

	if (did_action ('wp_print_styles'))
	{
		wp_print_styles ('rublon2factor_admin_css');
	}
	else
	{
		wp_enqueue_style ('rublon2factor_admin_css');
	}
}

/**
 * Creates settings submenu page
 */
function rublon2factor_add_settings_page()
{
	$title = __('Rublon', 'rublon2factor');
	$settings_page = add_options_page($title, $title, 'manage_options', /*'rublon2factor_settings'*/ 'rublon' , 'rublon2factor_create_settings_page');
	add_action ('admin_print_styles-' . $settings_page, 'rublon2factor_admin_css');
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
	$previous_settings = get_option(Rublon2FactorHelper::RUBLON_SETTINGS_KEY);
	
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
		<h2>
			<?php _e('Rublon Settings', 'rublon2factor'); ?>
		</h2>
		<form method="post" action="options.php">
			<?php	
			settings_fields ('rublon2factor_settings_group');
			$settings = Rublon2FactorHelper::getSettings();
			?>

			<p>
				<?php _e('Rublon protects your account from sign ins from unknown devices, even if your password gets stolen. It\'s a totally seamless way of securing your online accounts and the easiest two-factor authentication solution in the world.', 'rublon2factor'); ?>					
			</p>
			<p>					
				<?php _e('Find out more: <a href="https://rublon.com" target="_blank">www.rublon.com</a>', 'rublon2factor'); ?>					
			</p>
			
			<?php if (!Rublon2FactorHelper::isActive($settings)): ?>
			<table class="form-table rublon2factor_table">								

				<tr class="row_head">
					<th colspan="2"><strong><?php _e('Rublon Activation', 'rublon2factor'); ?></strong></th>
				</tr>

				<tr class="row_even">
					<td style="width: 50%">
						<?php _e('In order to be able to secure your WordPress account with Rublon, you need to activate Rublon first. Click the button below:', 'rublon2factor'); ?>
						<br /><br />
						<input class="button button-primary button-hero" type="submit" name="<?php echo RublonConsumerRegistration::ACTION_INITIALIZE ?>" value="<?php _e('Activate Rublon', 'rublon2factor') ?>" />
						<input type="hidden" name="projectName" value="<?= get_bloginfo('title') ?>" />
						<input type="hidden" name="projectTechnology" value="wordpress3" />
						<br /><br />
					</td>
				</tr>			
			</table>
			<?php else: ?>
			
			<p class="rublon_active">
				<span><?php _e('Rublon is active.', 'rublon2factor') ?></span>
			</p>
							
			<table class="form-table rublon2factor_table">	
				<tr class="row_head">
					<th colspan="2"><strong><?php _e('API Settings', 'rublon2factor'); ?></strong></th>
				</tr>
				
				<tr class="row_even">
					<td><label><?php _e('System Token', 'rublon2factor'); ?>:</label>
					</td>
					<td><input class="api_params" type="text"
						name="rublon2factor_settings[rublon_system_token]"
						value="<?php echo (isset ($settings['rublon_system_token']) ? htmlspecialchars ($settings['rublon_system_token']) : ''); ?>" />
					</td>
				</tr>
				<tr class="row_odd">
					<td><label><?php _e ('Secret Key', 'rublon2factor'); ?>:</label>
					</td>
					<td><input class="api_params" type="text"
						name="rublon2factor_settings[rublon_secret_key]"
						value="<?php echo (isset ($settings['rublon_secret_key']) ? htmlspecialchars ($settings['rublon_secret_key']) : ''); ?>" />
					</td>
				</tr>
				
				<tr class="row_even">
					<td colspan="2">
						<input type="submit" class="button-primary" value="<?php _e('Save', 'rublon2factor') ?>" />
					</td>
				</tr>				
								
			</table>
			<?php endif; ?>
			<?php if (false):?>
			<table class="form-table rublon2factor_table">
				<tr class="row_head">
					<th><strong><?php _e('Additional settings', 'rublon2factor'); ?></strong></th>
				</tr>
	  			<tr class="row_odd">
					<td><label><?php _e('During the uninstallation of this plugin', 'rublon2factor'); ?>:</label>
					</td>
					<td>
						<label for="rublon2factor_settings[uninstall_rublon_config]yes">
							<input id="rublon2factor_settings[uninstall_rublon_config]yes" type="radio" <?php checked(true, (bool) $settings['uninstall_rublon_config']) ?> value="1" name="rublon2factor_settings[uninstall_rublon_config]">
							<span><?php _e('delete all Rublon data', 'rublon2factor'); ?></span>
						</label>
						<br>
						<label for="rublon2factor_settings[uninstall_rublon_config]no">
							<input id="rublon2factor_settings[uninstall_rublon_config]no" type="radio" <?php checked(false, (bool) $settings['uninstall_rublon_config']) ?> value="0" name="rublon2factor_settings[uninstall_rublon_config]">
							<span><?php _e('keep the configuration and information about accounts already secured with Rublon', 'rublon2factor'); ?></span>
						</label>
					</td>
				</tr>
			</table>
			<?php endif;?>						

		</form>
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

	if ($pagenow == 'plugins.php' && !version_compare(phpversion(), '5.3.2', 'ge')) {
		echo "<div class='error'><p><strong>" . __('Warning! The PHP version of your server is insufficient to run Rublon. Please upgrade your server\'s PHP version.', 'rublon2factor') . '</strong></p><p>' . __('Required version:', 'rublon2factor') . ' <strong>5.3.2</strong></p><p>' . __('Your version:', 'rublon2factor') . ' <strong>' . phpversion() . '</strong></p></div>';
	}

	if ($pagenow == 'plugins.php' && !function_exists('curl_init')) {
		echo "<div class='error'><p><strong>" . __('Warning! The cURL library has not been found on this server.', 'rublon2factor') . '</strong> ' . __('It is a crucial component of the Rublon plugin and its absence will prevent it from working properly. Please have the cURL library installed or consult your server administrator about it.', 'rublon2factor') . '</p></div>';
	}
	
	if ( $pagenow == 'plugins.php' AND !Rublon2FactorHelper::isActive(Rublon2FactorHelper::getSettings())) 	{
		echo "<div class='updated'><p><strong>" . __('Rublon is almost ready.', 'rublon2factor') . "</strong> " . sprintf(__('You must <a href="%1$s">configure</a> it before it can be used.', 'rublon2factor'), "options-general.php?page=rublon") . "</p></div>";
	}
}
add_action('admin_notices', 'rublon2factor_no_settings_warning');

/**
 * Includes the Rublon JS Library on User Profile page
 */
function rublon2factor_add_script_on_profile_page()
{
	global $pagenow;

	if($pagenow == 'profile.php')
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
								<?php _e('Disable Rublon security for this account', 'rublon2factor') ?>
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
		$wp_user = get_user_by('id', $user_id);
		if (!empty($wp_user)) {
			$rublonProfileId = $wp_user->get('rublon_profile_id');
			if (!empty($rublonProfileId))
				Rublon2FactorHelper::unconnectRublon2Factor($wp_user, $rublonProfileId);
		}
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
_e('Security', 'rublon2factor'); 
?>
</h3>
<table class="form-table">
<tr>
	<th>
	<?php
		_e('Rublon', 'rublon2factor');
	?>
	</th>
	<td>
<?php 
if ( !Rublon2FactorHelper::isCurrentUserSecured() ) { 	
 	
 	echo '<div style="margin:0em;
            padding:2em;
            border:solid 1px #cccccc;
            background:none;
            font:normal 12px Helvetica, Arial;
            width: 40%;
            ">
		    <h2 style="font:bold 16px Helvetica, Arial;">' . __('Rublon Two-Factor Authentication', 'rublon2factor') . '</h2>     
		    <p>' .
		     __('Since your account is protected by a password only, it can be accessed from any device in the world. Secure your account with Rublon in order to restrict access from unknown devices:', 'rublon2factor')
		    . '</p><p>';		 
		    Rublon2FactorHelper::addSecureAccountButton();		             
	   echo '</p><p>' .
		      __('Rublon is a tokenless two-factor authentication mechanism. Learn more at <a href="https://rublon.com/" target="_blank">www.rublon.com</a>.', 'rublon2factor')
		    . '</p>
		 
		</div>';
 	
} else {	
	
	echo '<div style="margin:0em;
            padding:2em;
            border:solid 1px #cccccc;
            background:#B2E1A0;
            font:normal 12px Helvetica,Arial;
            width: 40%;
            ">
		   <h2 style="font:bold 16px Helvetica, Arial;">' . __('Rublon Two-Factor Authentication', 'rublon2factor') . '</h2>
		 
		   <p>
		    <strong>' .
		     __('Your account is secured by Rublon. ', 'rublon2factor')
		    . '</strong>' .
		     __('It can be accessed from your Trusted Devices only.', 'rublon2factor')
		   . '</p>		 
		   <p>';
			Rublon2FactorHelper::addInsecureAccountButton();		 
	 echo '</p>
		   <p>' .
		    __('Rublon is a tokenless two-factor authentication mechanism. Learn more at <a href="https://rublon.com/" target="_blank">www.rublon.com</a>.', 'rublon2factor')
		   . '</p>
		</div>';
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

function rublon2factor_add_userlist_columns($columns) {

	$new_columns = array();
	foreach ($columns as $k => $v) {
		$new_columns[$k] = $v;
		if ($k == 'username') {
			$new_columns['rublon2factor_status'] = __('Rublon security', 'rublon2factor');
		}
	}
	return $new_columns;

}

add_filter('manage_users_columns', 'rublon2factor_add_userlist_columns');

function rublon2factor_manage_rublon_columns($value, $column_name, $user_id) {

	if ($column_name == 'rublon2factor_status') {
		$wp_user = get_user_by('id', $user_id);
		if (!empty($wp_user)) {
			$rublonProfileId = $wp_user->get('rublon_profile_id');
			
			$language = get_bloginfo('language');
			$language = strtolower(substr($language, 0, 2));			
			
			if (!empty($rublonProfileId))
				$value = '<a href="https://www.rublon.com' . ($language=='pl'?'/pl/':'') . '" target="_blank"><img style="margin-top: 1px" src="' . RUBLON2FACTOR_PLUGIN_URL . '/assets/images/R_32x32.png' . '" title="' . __('Account secured by Rublon', 'rublon2factor') . '" /></a>';
		}
	}
	return $value;

}

add_filter('manage_users_custom_column', 'rublon2factor_manage_rublon_columns', 10, 3);