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


	protected function getShareAccessWidget() {
		return '';
	}


}
