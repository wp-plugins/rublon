<?php

if (!class_exists('Rublon_Pointers')) {

	class Rublon_Pointers {

		const API_REGISTRATION_DISMISSED = 'api_registration_dismissed';

		public static $instance;		

		private function __construct() {

			if (current_user_can('manage_options') && !RublonHelper::isSiteRegistered()) {
				wp_enqueue_style('wp-pointer');
				wp_enqueue_script('jquery-ui');
				wp_enqueue_script('wp-pointer');
				wp_enqueue_script('utils');
				add_action('admin_print_footer_scripts', array($this, 'apiRegistration'));
			}

		}

		public function getInstance() {

			if (!(self::$instance instanceof self)) {
				self::$instance = new self();
			}
			return self::$instance;

		}

		public function apiRegistration() {

			$reg_nonce = wp_create_nonce('rublon=init-registration');
			$dismiss_nonce = wp_create_nonce(self::API_REGISTRATION_DISMISSED);
			$current_user = wp_get_current_user();
			$email = RublonHelper::getUserEmail($current_user);
			$selector = 'a.toplevel_page_rublon';

			$content = '<h3>' . __('Rublon API registration', 'rublon') . '</h3>';
			$content .= '<div class="rublon-apireg-half-column rublon-apireg-description">';
			$content .= '<p>' . __('Rublon Account Protection works by talking to the Rublon API. This requires an API key, which needs to be generated specifically for your website.', 'rublon') . '</p>';
			$content .= '<p>' . sprintf(
				__('Due to security reasons, this requires a registration with your email address: <strong>%s</strong>. In order to register with a different email address, change it in your <a href="%s">profile settings</a>.', 'rublon'),
				$email,
				admin_url('profile.php')
			) . '</p>';
			$content .= '<fieldset class="rublon-apireg-fieldset"><label for="rublon-apireg-terms-agreed">'
				. '<input type="checkbox" id="rublon-apireg-terms-agreed" name="apiregTermsAgreed" value="1" />'
				. sprintf(
					__('I agree to the <a href="%s" target="_blank">Rublon API Terms of Service</a>.', 'rublon'),
					'https://developers.rublon.com/54/Terms-of-Service'
				) . '</fieldset>';
			$content .= '</div>';
			$content .= '<div class="rublon-apireg-half-column rublon-apireg-image"><img src="' . RUBLON2FACTOR_PLUGIN_URL . '/assets/images/rublon_visual.gif" class="rublon-apireg-visual rublon-image" /></div>';

			$options = array(
				'content' => $content,
				'position' => array('edge' => is_rtl() ? 'right' : 'left', 'align' => 'center'),
				'pointerClass' => 'wp-pointer rublon-apireg-pointer',
			);
			
			$buttons = array(
				'button1' => array(
					'text' => __('Cancel', 'rublon'),
					'function' => 'RublonWP.apiRegistrationAnswer("no", "' . $dismiss_nonce . '");',
					'id' => 'rublon-apireg-button-cancel',
				),
				'button2' => array(
					'text' => __('Activate', 'rublon'),
					'function' => 'RublonWP.apiRegistrationAnswer("yes", "' . $dismiss_nonce . '", "' . $reg_nonce . '");',
					'id' => 'rublon-apireg-button-activate',
				),
			);

			$this->_printPointers($selector, $options, $buttons);

		}

		private function _printPointers($selector, $options, $buttons) {

		?>
		<script type="text/javascript">
			//<![CDATA[
			(function($) {
				var rublon_pointer_options = <?php echo json_encode($options); ?>;
				rublon_pointer_options = $.extend(rublon_pointer_options, {
					buttons: function(event, t) {
						var button = $('<a <?php if (!empty($buttons['button1']['id'])) { echo 'id="' . $buttons['button1']['id'] . '" '; } ?>style="margin:0 5px;" class="button-secondary">' + '<?php echo $buttons['button1']['text']; ?>' + '</a>');
						button.bind('click.pointer', function() {
							t.element.pointer('close');
						});
						return button;
					},
					close: function() {	}
				});
				var showPointer = function () {
					$('<?php echo $selector; ?>').pointer(rublon_pointer_options).pointer('open');
					var buttonsContainer = $('.wp-pointer-buttons').detach();
					$(buttonsContainer).addClass('rublon-apireg-buttons');
					$('.rublon-apireg-description').append(buttonsContainer);
					<?php if ($buttons['button2']['text']): ?>
						$('#rublon-apireg-button-cancel').before('<a <?php if (!empty($buttons['button2']['id'])) { echo 'id="' . $buttons['button2']['id'] . '" '; } ?>class="button-primary disabled">' + '<?php echo $buttons['button2']['text']; ?>' + '</a>');
						$('#rublon-apireg-button-activate').click(function() {
						<?php echo $buttons['button2']['function']; ?>;
						});
					<?php endif; ?>
					$('#rublon-apireg-button-cancel').click(function() {
						<?php echo $buttons['button1']['function']; ?>;
					});
				};
				<?php
				$rublonGUI = Rublon2FactorGUIWordPress::getInstance();
				$apiRegURL = $rublonGUI->getActivationURL();
				$other_settings = RublonHelper::getSettings('other');
				if (!empty($other_settings[self::API_REGISTRATION_DISMISSED])):
				?>
 				$(document).ready(function() {
					RublonWP.pointers.apireg = showPointer;
					RublonWP.prepareApiRegPointer();
				});
				<?php else: ?>
				if (rublon_pointer_options.position && rublon_pointer_options.position.defer_loading) {
					$(window).bind('load.wp-pointers', function() {
						RublonWP.pointers.apireg = showPointer;
						showPointer();
						RublonWP.setUpRegistrationAgreementListener();
						RublonWP.setRublonMenuEmptyClick();
					});
				} else {
					$(document).ready(function() {
						RublonWP.pointers.apireg = showPointer;
						showPointer();
						RublonWP.setUpRegistrationAgreementListener();
						RublonWP.setRublonMenuEmptyClick();
					});
				}
				<?php endif; ?>
				RublonWP.apiRegURL = <?php echo json_encode($apiRegURL); ?>;
			})(jQuery);
			//]]>
		</script>
		<?php
		}

	}

}