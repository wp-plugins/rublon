<?php

if (!class_exists('Rublon_Pointers')) {

	class Rublon_Pointers {

		const API_REGISTRATION_DISMISSED = 'api_registration_dismissed';
		const ANONYMOUS_STATS_ALLOWED = 'anonymous_stats_allowed';

		const AJAX_API_REGISTRATION_ACTION = 'rublon_apireg_dismissed';
		const AJAX_ANONYMOUS_STATS_ACTION = 'rublon_stats_agreement';

		public static $instance;

		private $_apireg_form = '';

		private function __construct() {

			if (current_user_can('manage_options')) {
				if (!RublonHelper::isSiteRegistered() || RublonHelper::isTrackingAllowed() === null) {
					wp_enqueue_style('wp-pointer');
					wp_enqueue_script('jquery-ui');
					wp_enqueue_script('wp-pointer');
					wp_enqueue_script('utils');
					if (RublonHelper::isTrackingAllowed() === null) {
						add_action('admin_print_footer_scripts', array($this, 'anonymousStats'));
					} elseif (!RublonHelper::isSiteRegistered()) {
						try {
							$consumer_registration = new RublonConsumerRegistrationWordPress();
							$this->_apireg_form = $consumer_registration->retrieveRegistrationForm();
							add_action('admin_print_footer_scripts', array($this, 'apiRegistration'));
						} catch (Exception $e) {
							RublonHelper::handleRegistrationException($e, $no_redirect = true);
							
						}
					}
				}
			}

		}

		public function getInstance() {

			if (!(self::$instance instanceof self)) {
				self::$instance = new self();
			}
			return self::$instance;

		}

		public function anonymousStats() {

			$current_user = wp_get_current_user();
			$selector = '#wpadminbar';

			$content = '<h3>' . __('Help improve Rublon Account Security', 'rublon') . '</h3>';
			$content .= '<p>' . __('You\'ve just installed Rublon Account Security. Please helps us improve it by allowing us to gather anonymous usage stats so we know which configurations, plugins and themes to test with.', 'rublon') . '</p>';

			$options = array(
					'content' => $content,
					'position' => array('edge' => 'top', 'align' => 'center'),
					'pointerClass' => 'wp-pointer rublon-annmstats-pointer',
					'showFunction' => 'showAnnmStatsPointer',
			);

			$dismiss_nonce = wp_create_nonce(self::ANONYMOUS_STATS_ALLOWED);
			$buttons = array(
					'button1' => array(
							'text' => __('Do not allow tracking', 'rublon'),
							'function' => 'RublonWP.pointers.annmStats.answer("' . RublonHelper::NO . '", ' . json_encode($dismiss_nonce) . ')',
							'id' => 'rublon-annmstats-button-cancel',
					),
					'button2' => array(
							'text' => __('Allow tracking', 'rublon'),
							'function' => 'RublonWP.pointers.annmStats.answer("' . RublonHelper::YES . '", ' . json_encode($dismiss_nonce) . ')',
							'id' => 'rublon-annmstats-button-activate',
					),
					'appearance' => array(
						'connection' => 'before',
					),
			);

			$event_binding = '
				if (rublon_pointer_options.position && rublon_pointer_options.position.defer_loading) {
					$(window).bind("load.wp-pointers", function() {
						showAnnmStatsPointer();
						RublonWP.pointers.disableClicks();
					});
				} else {
					$(document).ready(function() {
						showAnnmStatsPointer();
						RublonWP.pointers.disableClicks();
					});
				}
			';
			
			$constants_binding = '
				RublonWP.pointers.annmStats.DISMISSED_ACTION = ' . json_encode(self::AJAX_ANONYMOUS_STATS_ACTION) . ';
			';
			
			$bindings = array($event_binding, $constants_binding);
			
			$this->_printPointer($selector, $options, $buttons, $bindings);			

		}

		public function apiRegistration() {

			$current_user = wp_get_current_user();
			$email = RublonHelper::getUserEmail($current_user);
			$selector = '#wpadminbar';

			$content = '<h3>' . __('Rublon API registration', 'rublon') . '</h3>';
			$content .= '<div class="rublon-apireg-half-column rublon-apireg-description">';

			$content .= '<p>' . __('Rublon Account Security works by talking to the Rublon API. This requires an API key, which needs to be generated specifically for your website.', 'rublon') . '</p>';
			$content .= '<p>' . sprintf(
				__('Due to security reasons, this requires a registration with your email address: <strong>%s</strong>. In order to register with a different email address, change it in your <a href="%s">profile settings</a>.', 'rublon'),
				$email,
				admin_url('profile.php')
			) . '</p>';
			$content .= '<fieldset class="rublon-apireg-fieldset"><label for="rublon-apireg-terms-agreed">'
				. '<input type="checkbox" id="rublon-apireg-terms-agreed" name="apiregTermsAgreed" value="1" />'
				. sprintf(
					__('I agree to the <a href="%s" target="_blank">Rublon API Terms of Service</a>', 'rublon'),
					'https://developers.rublon.com/54/Terms-of-Service'
				) . '</fieldset>';
			$content .= '<fieldset class="rublon-apireg-fieldset"><label for="rublon-apireg-newsletter-signup">'
				. '<input type="checkbox" id="rublon-apireg-newsletter-signup" name="apiregNewsletterSignup" value="1" />'
				. __('Subscribe to our newsletter', 'rublon')
				. '</fieldset>';
			$content .= '</div>';
			$content .= '<div class="rublon-apireg-half-column rublon-apireg-image"><img src="' . RUBLON2FACTOR_PLUGIN_URL . '/assets/images/rublon_visual.gif" class="rublon-apireg-visual rublon-image" /></div>';
			$content .= $this->_apireg_form;

			$options = array(
				'content' => $content,
				'position' => array('edge' => 'top', 'align' => 'center'),
				'pointerClass' => 'wp-pointer rublon-apireg-pointer',
				'pointerClassSelector' => '.wp-pointer.rublon-apireg-pointer',
				'showFunction' => 'showApiRegPointer',
			);

			$dismiss_nonce = wp_create_nonce(self::API_REGISTRATION_DISMISSED);
			$buttons = array(
				'button1' => array(
					'text' => __('Cancel', 'rublon'),
					'function' => 'RublonWP.pointers.apiReg.answer("no", ' . json_encode($dismiss_nonce) . ')',
					'id' => 'rublon-apireg-button-cancel',
					'close_on_click' => true,
				),
				'button2' => array(
					'text' => __('Activate', 'rublon'),
					'function' => 'RublonWP.pointers.apiReg.answer("yes", ' . json_encode($dismiss_nonce) . ')',
					'additional_class' => 'disabled',
					'id' => 'rublon-apireg-button-activate',
				),
				'placement' => array(
					'additional_class' => 'rublon-apireg-buttons',
					'append_to' => '.rublon-apireg-description',
				),
				'appearance' => array(
					'connection' => 'before',
				),
			);

			$other_settings = RublonHelper::getSettings('other');
			if (!empty($other_settings[self::API_REGISTRATION_DISMISSED])) {
				$event_binding = '
			 		$(document).ready(function() {
						RublonWP.pointers.apiReg.show = showApiRegPointer;
						RublonWP.pointers.apiReg.prepareHidden();
					});
				';
			} else {
				$event_binding = '
					if (rublon_pointer_options.position && rublon_pointer_options.position.defer_loading) {
						$(window).bind("load.wp-pointers", function() {
							RublonWP.pointers.apiReg.show = showApiRegPointer;
							showApiRegPointer();
							RublonWP.pointers.apiReg.addBehaviour();
							RublonWP.pointers.disableClicks();
						});
					} else {
						$(document).ready(function() {
							RublonWP.pointers.apiReg.show = showApiRegPointer;
							showApiRegPointer();
							RublonWP.pointers.apiReg.addBehaviour();
							RublonWP.pointers.disableClicks();
						});
					}
				';
			}

			$constants_binding = '
				RublonWP.pointers.apiReg.DISMISSED_ACTION = ' . json_encode(self::AJAX_API_REGISTRATION_ACTION) . ';
				RublonWP.pointers.apiReg.BUTTON_ACTIVATE_SELECTOR = "#" + ' . json_encode($buttons['button2']['id']) . ';
			';
		
			$bindings = array($event_binding, $constants_binding);
		
			$this->_printPointer($selector, $options, $buttons, $bindings);
		
		}

		private function _printPointer($selector, $options, $buttons, $bindings = array()) {
			?>
			<script type="text/javascript">
				//<![CDATA[
				(function($) {
					var rublon_pointer_options = <?php echo json_encode($options); ?>;
					rublon_pointer_options = $.extend(rublon_pointer_options, {
						buttons: function(event, t) {
							var button = $('<a id="<?php echo $buttons['button1']['id'] ?>" class="button-secondary"><?php echo htmlspecialchars($buttons['button1']['text']); ?></a>');
							<?php if ( !empty( $buttons['button1']['close_on_click'] ) ): ?>
							button.bind('click.pointer', function() {
								t.element.pointer('close');
							});
							<?php endif; ?>
							return button;
						},
						close: function() {	}
					});
					var <?php echo $options['showFunction']; ?> = function() {
						$('<?php echo $selector; ?>').pointer(rublon_pointer_options).pointer('open');
						<?php if (!empty($buttons['placement'])): ?>
						var buttonsContainer = $('<?php echo $options['pointerClassSelector']; ?>' + ' .wp-pointer-buttons').detach();
						<?php 	if (!empty($buttons['placement']['additional_class'])): ?>
							$(buttonsContainer).addClass('<?php echo $buttons['placement']['additional_class']; ?>');
						<?php 	endif; ?>
						<?php 	if (!empty($buttons['placement']['append_to'])): ?>
							$('<?php echo $buttons['placement']['append_to']; ?>').append(buttonsContainer);
						<?php 	endif; ?>
						<?php endif; ?>
	
						<?php
							$connecting_function = 'after';
							if (!empty($buttons['appearance']['connection'])) {
								$connecting_function = $buttons['appearance']['connection'];
							}
						?>
						<?php if ($buttons['button2']['text']): ?>
							$('#<?php echo $buttons['button1']['id']; ?>').<?php echo $connecting_function; ?>('<a id="<?php echo $buttons['button2']['id']; ?>" class="button-primary<?php if (!empty($buttons['button2']['additional_class'])) { echo ' ' . $buttons['button2']['additional_class']; } ?>"><?php echo htmlspecialchars($buttons['button2']['text']); ?></a>');
							$('#<?php echo $buttons['button2']['id']; ?>').click(function() {
								<?php echo $buttons['button2']['function']; ?>;
							});
						<?php endif; ?>
						$('#<?php echo $buttons['button1']['id']; ?>').click(function() {
							<?php echo $buttons['button1']['function']; ?>;
						});
					};
					<?php
						foreach ($bindings as $binding) {
							echo $binding;
						}
					?>
				})(jQuery);
				//]]>
			</script>
			<?php
		}

	}

}