<?php
/**
 * Code to build widget
 *
 * @package   rublon-login\includes
 * @author     Adips Sp. z o.o.
 * @copyright  Adips Sp. z o.o.
 * @license    http://opensource.org/licenses/gpl-license.php GNU Public License, version 2 
 */

/**
 * Rublon for WordPress widget
 *
 * @package   rublon-login\includes
 */
class rublon2factor_widget extends WP_Widget
{
	/**
	 * Constructor
	 */
	public function __construct ()
	{
		// Localization
		if (function_exists ('load_plugin_textdomain'))
		{
			load_plugin_textdomain ('rublon-login', false, RUBLON_LOGIN_BASE_PATH . '/languages/');
		}
	
		parent::WP_Widget ('rublon_login', __('Rublon for WordPress', 'rublon-login'), array (
				'description' => __('Rublon is the most secure convenient login system. It allows your visitors to log in via Rublon Codes.', 'rublon-login')
		));
	}


	/**
	 *  Display the Rublon for WordPress widget
	 */
	public function widget ($args, $instance)
	{
		$settings = get_option ('rublon_login_settings');

		if(!rublon_login_is_rublon_inactive($settings))
		{
			if (!is_user_logged_in())
			{
				$redirect_after_enter = rublon_login_get_current_page_url();
				$button = rublon_login_get_enter_button($redirect_after_enter);
			}
			else if (!rublon_login_is_current_user_rublon_user())
			{
				$extra_options = array('class' => 'rublon-button-size-small');
				$button = rublon_login_get_link_account_button($extra_options);
			}
			
			if ($button)
			{
				// Any before Widget code
				echo $args ['before_widget'];
				
				// Rublon for WordPress content
				echo $button;
				
				//Any after Widget code
				echo $args ['after_widget'];
			}
			
			
		}
	}
}
add_action ('widgets_init', create_function ('', 'return register_widget( "rublon2factor_widget" );')); 