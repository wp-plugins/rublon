<?php

/**
 * Implementation of the Rublon 2-Factor GUI template
 *
 */
class RublonGUI extends Rublon2FactorGUITemplate {


	/**
	 * Get session ID or NULL if not set
	 *
	 * @return string|NULL
	 */
	protected function getSessionId() {

		// Not needed - WordPress doesn't use sessions 
		return null;

	}


	/**
	 * Get Rublon profile ID of the user in current session or 0 if not set
	 *
	 * @return int
	*/
	protected function getRublonProfileId() {

		$user = wp_get_current_user();
		if ($user)
			return RublonHelper::getUserProfileId($user);
		else
			return 0;

	}


	/**
	 * Retrieve consumer's systemToken or NULL if not set
	 *
	 * @return string|NULL
	*/
	protected function getSystemToken() {

		$settings = RublonHelper::getSettings();
		return (!empty($settings['rublon_system_token'])) ? $settings['rublon_system_token'] : null;

	}


	/**
	 * Retrieve consumer's secretKey or NULL if not set
	 *
	 * @return string|NULL
	*/
	protected function getSecretKey() {

		$settings = RublonHelper::getSettings();
		return (!empty($settings['rublon_secret_key'])) ? $settings['rublon_secret_key'] : null;

	}


	/**
	 * Check whether plugin's activation is provided and user can activate the plugin
	 *
	 * @return boolean
	*/
	protected function canActivate() {

		return current_user_can('manage_options');

	}


	/**
	 * Get URL of the activation process
	 *
	 * Return NULL if the integration does not implement activation.
	 *
	 * @return string|NULL
	 */
	protected function getActivationURL() {

		return site_url('?rublon=init-registration&rublon_nonce='  . RublonHelper::getNonce());

	}


	/**
	 * Returns consumer parameters for auth transaction
	 *
	 * @return array
	 */
	protected function getConsumerParams() {

		global $pagenow;

 		if ($pagenow == 'profile.php')
 			$page = 'profile';
 		else
 			$page = 'rublon';

		return array(
			'customURIParam' => $page,
			'wp_user' => RublonHelper::getUserId(wp_get_current_user())
		);

	}


	/**
	 * Return the Rublon API domain
	 *
	 * @return string
	 */
	protected function getAPIDomain() {

		return RublonHelper::getAPIDomain();

	}


	/**
	 * Get current language
	 *
	 * @return string
	 */
	protected function getLang() {

		return RublonHelper::getBlogLanguage();

	}


	/**
	 * Return the activation button without the user box
	 * 
	 * @return string
	 */
	public function getRegistrationButton() {

		$result = (string)new RublonConsumerScript($this->service);
		$button = $this->createActivationButton();
		$result .= '<div class=data-can-activate="' . $this->canActivate() . '">';
		$result .= (string)$button;
		$result .= '</div>';
		return $result;

	}


	/**
	 * Return the plugin registration ribbon
	 * 
	 * @return string
	 */
	public function registrationRibbon() {

		$lang = RublonHelper::getBlogLanguage();
		$ribbon = '<div class="updated" style="padding: 0; margin: 0; border: none; background: none;">'
			. '<div class="rublon-activate-ribbon">'
			. '<div class="rublon-activate-description-wrapper">' . $this->getRegistrationButton() . '</div>'
			. '<div class="rublon-activate-description-wrapper"><div class="rublon-activate-description">' . __('Rublon mobile app required', 'rublon2factor')
			. '.' . sprintf('<strong><a href="http://rublon.com%s/get" target="_blank"><span style=color:#5bba36> ',  (($lang != 'en') ? ('/' . $lang) : ''))
			. __('Free Download', 'rublon2factor') . ' &raquo;</span></a></strong></div></div>'
			. '<div class="rublon-activate-image"><a href="http://rublon.com'. (($lang != 'en') ? '/' . $lang . '/' : '') . '" target="_blank"><img src="'
			. RUBLON2FACTOR_PLUGIN_URL . '/assets/images/rublon-ribbon-text.png" /></a></div>'
			. '<div class="rublon-clear"></div>'
			. '</div></div>';
		return $ribbon;

	}


}