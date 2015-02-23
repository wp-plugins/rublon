<?php

require_once dirname (__FILE__) . '/../Rublon/Rublon2FactorGUI.php';

class Rublon2FactorGUIWordPress extends Rublon2FactorGUI {
	
	public static $instance;
	
	public static function getInstance() {

		if (empty(self::$instance)) {
			$additional_settings = RublonHelper::getSettings('additional');
			$current_user = wp_get_current_user();
			self::$instance = new self(
				RublonHelper::getRublon(),
				RublonHelper::getUserId($current_user),
				RublonHelper::getUserEmail($current_user),
				$logout_listener = false // Now we are using the WP Heartbeat
			);

			// Embed consumer script
			if (RublonHelper::isSiteRegistered()) {
				add_action('admin_footer', array(self::$instance, 'renderConsumerScript'));
			}
		}

		return self::$instance;
	}


	public function getConsumerScript() {
		// Don't show consumer script, it will be embeded in the footer action using self::renderConsumerScript() method.
		return '';
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
	public function getTDMWidget() {

		$result = '';

		if (RublonHelper::isSiteRegistered()) {

			$current_user = wp_get_current_user();
			$protection_type = RublonHelper::YES;
			RublonHelper::isUserProtected($current_user, $protection_type);
			switch ($protection_type) {
				case RublonHelper::PROTECTION_TYPE_MOBILE:
					$result .= '<p>' . sprintf(__('Your account is protected by <a href="%s" target="_blank">Rublon</a>.', 'rublon'), RublonHelper::rubloncomUrl()) . '</p>';
					break;
				case RublonHelper::PROTECTION_TYPE_EMAIL:
					$result .= '<p>' . sprintf(__('Your account is protected by <a href="%s" target="_blank">Rublon</a>.', 'rublon'), RublonHelper::rubloncomUrl())
						. ' ' . sprintf(__('Get the <a href="%s/get" target="_blank">Rublon mobile app</a> for more security.', 'rublon'), RublonHelper::rubloncomUrl()) . '</p>';
					break;
				case RublonHelper::PROTECTION_TYPE_NONE:
					$lang = RublonHelper::getBlogLanguage();
					$result .= '<p>' . sprintf(
						'<span style="color: red; font-weight: bold;">' . __('Warning!', 'rublon') . '</span>'
							. ' ' . __('Your account is not protected. Go to <a href="%s">your profile page</a> to enable account protection.', 'rublon'),
						admin_url(RublonHelper::WP_PROFILE_PAGE . RublonHelper::WP_PROFILE_EMAIL2FA_SECTION)
					) . '</p>';
					break;
			}
	
			$result .= $this->getDeviceWidget();
	
		}

		return $result;

	}


	/**
	 * Create Trusted Devices Widget container for WP Dashboard
	 *
	 * @return string
	 */
	public function getACMWidget() {
		return $this->getShareAccessWidget();
	}

	
	
	public function renderConsumerScript() {
		
		wp_enqueue_script('jquery');
		
		// Consumer script
		echo parent::getConsumerScript();

	}


	public function userBox() {

		return '';

	}


}
