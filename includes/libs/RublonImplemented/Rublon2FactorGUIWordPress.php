<?php

require_once dirname (__FILE__) . '/../Rublon/Rublon2FactorGUI.php';

class Rublon2FactorGUIWordPress extends Rublon2FactorGUI {


	/**
	 * CSS class for Rublon activation button.
	 */
	const BUTTON_ACTIVATION_CLASS = 'rublon-button-activation rublon-button-label-enable-protection';


	public function getActivationURL() {

		return site_url('?rublon=init-registration&rublon_nonce='  . RublonHelper::getNonce());

	}


	/**
	 * Return the activation button without the user box
	 *
	 * @return string
	 */
	public function getRegistrationButton() {
	
		$result = (string)$this->getConsumerScript();
		$button = (string)$this->createActivationButton(null)->setLabel(__('Enable protection', 'rublon'));
		$result .= '<div class=data-can-activate="' . $this->canUserActivate() . '">';
		$result .= $button;
		$result .= '</div>';
		return $result;
	
	}


	/**
	 * Returns Rublon Button for plugin's registration.
	 * 
	 * Since the registration is now handled automatically,
	 * the button is not necessary.
	 *
	 * @return RublonButton
	 */
	protected function createActivationButton($activationURL) {
		return '';
	}


	/**
	 * Create Trusted Devices Widget container for WP Dashboard 
	 * 
	 * @return string
	 */
	public function getDashboardDeviceWidget($withConsumerScript = true) {

		$result = '';

		$current_user = wp_get_current_user();
		$protection_type = RublonHelper::YES;
		RublonHelper::isUserProtected($current_user, $protection_type);
		switch ($protection_type) {
			case RublonHelper::PROTECTION_TYPE_MOBILE:
				$result .= sprintf(__('Your account is protected by <a href="%s" target="_blank">Rublon</a>.', 'rublon'), RublonHelper::rubloncomUrl());
				break;
			case RublonHelper::PROTECTION_TYPE_EMAIL:
				$result .= sprintf(__('Your account is protected by <a href="%s" target="_blank">Rublon</a>.', 'rublon'), RublonHelper::rubloncomUrl())
					. ' ' . sprintf(__('Get the <a href="%s/get" target="_blank">Rublon mobile app</a> for more security.', 'rublon'), RublonHelper::rubloncomUrl());
				break;
			case RublonHelper::PROTECTION_TYPE_NONE:
				$lang = RublonHelper::getBlogLanguage();
				$result .= sprintf(
					'<span style="color: red; font-weight: bold;">' . __('Warning!', 'rublon') . '</span>'
						. ' ' . __('Your account is not protected. Go to <a href="%s">your profile page</a> to enable account protection.', 'rublon'),
					admin_url(RublonHelper::WP_PROFILE_PAGE . RublonHelper::WP_PROFILE_EMAIL2FA_SECTION)
				);
				break;
		}

		$result .= $this->getDeviceWidget();
		if ($withConsumerScript) {
			$result .= $this->getConsumerScript();
		}

		return $result;

	}


	/**
	 * Create Trusted Devices Widget container for WP Dashboard
	 *
	 * @return string
	 */
	public function getDashboardACMWidget($withConsumerScript = false) {
	
		$result = '';

		$result .= $this->getShareAccessWidget();
		if ($withConsumerScript) {
			$result .= $this->getConsumerScript();
		}
	
		return $result;
	
	}


	public function addConsumerScript() {

		echo $this->getConsumerScript();

	}


	public function userBox() {

		return '';

	}


}
