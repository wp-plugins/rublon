<?php
/**
 * Additional helper functions for Rublon for WordPress plugin
 *
 * @package   rublon2factor\includes
 * @author     Rublon Developers http://www.rublon.com
 * @copyright  Rublon Developers http://www.rublon.com
 * @license    http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */

/**
 * RublonHelper class
 *
 * It provides helper functionalities for Rublon2Factor module.
 *
 */

class RublonHelper {


	const RUBLON_API_DOMAIN = 'https://code.rublon.com';
	const RUBLON_REGISTRATION_DOMAIN = 'https://developers.rublon.com';

	const RUBLON_SETTINGS_KEY = 'rublon2factor_settings';
	const RUBLON_REGISTRATION_SETTINGS_KEY = 'rublon2factor_registration_settings';
	const RUBLON_ADDITIONAL_SETTINGS_KEY = 'rublon2factor_additional_settings';

	const RUBLON_META_PROFILE_ID = 'rublon_profile_id';
	const RUBLON_META_AUTH_CHANGED_MSG = 'rublon_auth_changed_msg';
	const RUBLON_META_PROTECTION_TYPE = 'rublon_user_protection_type';
	
	const RUBLON_NOTIFY_URL_PATH = '/issue_notifier/wp_notify';
	const RUBLON_ACTION_PREFIX = 'rublon_';

	const PRERENDER_KEY_MOBILE_USERS = 'prer_mobile_users';

	const TRANSIENT_PROFILE_TOKEN_PREFIX = 'rublon_put_';
	const TRANSIENT_PROFILE_FORM_PREFIX = 'rublon_puform_';
	const TRANSIENT_ADDSETT_TOKEN_PREFIX = 'rublon_asut_';
	const TRANSIENT_ADDSETT_FORM_PREFIX = 'rublon_asuform_';
	const TRANSIENT_MOBILE_USER = 'rublon_mobuser_';
	const TRANSIENT_DEBUG = 'rublon_debug';
	
	const PROFILE_UPDATE_TOKEN_NAME = 'rublon_profile_update_token';
	const ADDSETT_UPDATE_TOKEN_NAME = 'rublon_additional_settings_update_token';
	
	const SETTING_CAN_SHOW_ACM = 'can_show_acm';
	
	const YES = 'yes';
	const NO = 'no';

	const UPDATE_TOKEN_LIFETIME = 5;
	const UPDATE_FORM_LIFETIME = 15;
	const MOBILE_USER_INFO_LIFETIME = 15;

	const FLAG_PROFILE_UPDATE = 'wp_profile_update';
	const FLAG_ADDSETT_UPDATE = 'wp_addsett_update';
	
	const FIELD_USER_PROTECTION_TYPE = 'rublon_user_protection_type';

	const PROTECTION_TYPE_NONE = 'none';
	const PROTECTION_TYPE_EMAIL = 'email';
	const PROTECTION_TYPE_MOBILE = 'mobile';

	const PROTECTION_TYPE_SETT_PREFIX = 'prottype-for-';

	const WP_PROFILE_PAGE = 'profile.php';
	const WP_OPTIONS_PAGE = 'options.php';
	const WP_RUBLON_PAGE = 'admin.php?page=rublon';
	const WP_PROFILE_EMAIL2FA_SECTION = '#rublon-email2fa';

	const PHP_VERSION_REQUIRED = '5.2.17';
	
	/**
	 * An instance of the Rublon2FactorWordPress class
	 * 
	 * @var Rublon2FactorWordPress
	 */
	static private $rublon;


	/**
	 * Plugin cookies
	 *
	 * @var array
	 */
	static public $cookies;


	/**
	 * Nonce used in the validation of the plugin registration process
	 * 
	 * @var string
	 */
	static private $nonce;


	/**
	 * Additional data, pre-render
	 *
	 * Additional data populated before rendering time. Can be used in views.
	 *
	 * @var array
	 */
	static private $pre_render_data;


	/**
	 * Load i18n files and check for possible plugin update
	 * 
	 */
	static public function init() {

		do_action('rublon_plugin_pre_init');
		
		// Initialize localization
		if (function_exists('load_plugin_textdomain')) {
			load_plugin_textdomain('rublon', false, RUBLON2FACTOR_BASE_PATH . '/includes/languages/');
		}

		// check for a possible update
		self::_updateChecker();

		// Set default additional settings if not present
		self::_checkAdditionalSettings();

		// prevent XML-RPC access if it was disabled in plugin settings 
		self::_checkXMLRPCStatus();

	}


	/**
	 * Check for any Rublon actions in the URI
	 *
	 */
	static public function checkForActions() {
	
		global $pagenow;
	
		$rublonAction = self::uriGet('rublon');
		if (isset($rublonAction)) {
			switch (strtolower($rublonAction)) {
				case 'register':
					$rublonRegAction = self::uriGet('action');
					if (isset($rublonRegAction)) {
						self::consumerRegistrationAction($rublonRegAction);
					}
					break;
				case 'callback':
					$accessToken = self::uriGet('token');
					$responseState = self::uriGet('state');
					if (isset($accessToken) && isset($responseState)) {
						self::handleCallback();
					}
					break;
				case 'confirm':
					$accessToken = self::uriGet('token');
					$responseState = self::uriGet('state');
					if (isset($accessToken) && isset($responseState)) {
						self::handleConfirmation();
					}
					break;
				case 'init-registration':
					$nonce = self::uriGet('rublon_nonce');
					$nonceCookie = RublonCookies::getNonceFromCookie();
					if (!empty($nonce) && wp_verify_nonce($nonce, 'rublon=init-registration') && $nonce == $nonceCookie) {
						self::_initializeConsumerRegistration();
	
					} else {
						self::setMessage('NONCE_VERIFICATION_FAILED', 'error', 'CR');
						wp_redirect(admin_url(self::WP_RUBLON_PAGE));
					}
					break;
			}
			exit;
		} else {
			// Check for transient-stored profile update form
			self::_checkForStoredPUForm();
			self::_checkForStoredASUForm();
		}
	
	}


	static public function getRublon($refresh = true) {

		if (empty(self::$rublon) || $refresh) {
			if (self::isSiteRegistered()) {
				$settings = self::getSettings();
				self::$rublon = new Rublon2FactorWordPress($settings['rublon_system_token'], $settings['rublon_secret_key']);
			} else {
				self::$rublon = new Rublon2FactorWordPress('', '');
			}
		}
		return self::$rublon;

	}


	static public function checkIfUserPermitted() {

		if (self::isSiteRegistered()) {
			$current_user = wp_get_current_user();
			if (!empty($current_user)) {
				$current_user_id = self::getUserId($current_user);
				if ($current_user_id && is_user_logged_in()) {
					if (!self::isUserAuthenticated($current_user)) {
						$protection_type = array(
							self::roleProtectionType($current_user),
							self::userProtectionType($current_user)
						);
						$authURL = self::authenticateWithRublon($current_user, $protection_type);
						if (empty($authURL)) {
							if (in_array(self::PROTECTION_TYPE_MOBILE, $protection_type)) {
								wp_logout();
								self::setMessage('ROLE_BLOCKED', 'error', 'LM');
								$return_page = RublonHelper::getReturnPage();
								wp_redirect($return_page);
								exit;
							}
						} else {
							wp_logout();
							wp_redirect($authURL);
							exit;
						}
					}
				}
			}
		}

	}


	/**
	 * Transfer plugin messages from cookie to a private field
	 * 
	 */
	static public function cookieTransfer() {

		$cookies = array();
		$messages = RublonCookies::getMessagesFromCookie();
		if (!empty($messages))
			$cookies['messages'] = $messages;
		self::$cookies = $cookies;

	}


	/**
	 * Transfer plugin messages back to the cookie
	 * 
	 */
	static public function cookieTransferBack() {

		if (!empty(self::$cookies['messages'])) {
			RublonCookies::storeAllMessagesInCookie(self::$cookies['messages']);
			unset(self::$cookies['messages']);
		}

	}


	static private function _checkForStoredPUForm() {

		global $pagenow;

		if ($pagenow == self::WP_PROFILE_PAGE) {
			$current_user = wp_get_current_user();
			if ($current_user instanceof WP_User) {
				$current_user_id = self::getUserId($current_user);
				$post = self::_retrieveForm(
					$current_user_id,
					self::TRANSIENT_PROFILE_FORM_PREFIX
				);
				$PUToken = self::_retrieveUpdateToken(
					$current_user_id,
					self::TRANSIENT_PROFILE_TOKEN_PREFIX
				);
				if (!empty($post) && !empty($PUToken)) {
					if (!empty($post[self::PROFILE_UPDATE_TOKEN_NAME])) {
						$_POST = $post;					
					}
					self::_clearForm(
						$current_user_id,
						self::TRANSIENT_PROFILE_FORM_PREFIX
					);
				}
			}
		}

	}


	static private function _checkForStoredASUForm() {

		global $pagenow;
		
		if ($pagenow == self::WP_OPTIONS_PAGE) {
			$current_user = wp_get_current_user();
			if ($current_user instanceof WP_User) {
				$current_user_id = self::getUserId($current_user);
				$post = self::_retrieveForm(
					$current_user_id,
					self::TRANSIENT_ADDSETT_FORM_PREFIX
				);
				$ASUToken = self::_retrieveUpdateToken(
					$current_user_id,
					self::TRANSIENT_ADDSETT_TOKEN_PREFIX
				);
				if (!empty($post) && !empty($ASUToken)) {
					if (!empty($post[self::ADDSETT_UPDATE_TOKEN_NAME])) {
						$_POST = $post;
					}
					self::_clearForm(
						$current_user_id,
						self::TRANSIENT_ADDSETT_FORM_PREFIX
					);
				}
			}
		}		

	}


	/**
	 * Handle profile update POST form 
	 * 
	 * Check if the profile update involves any Rublon-
	 * protected fields, if so, confirm the change with
	 * transaction confirmation. If not, check if this is
	 * a Rublon-confirmed change - the Rublon profile update
	 * token should be present in DB and the POST data then.
	 * 
	 * @param array $post POST form data
	 */
	static public function checkPostDataProfileUpdate($post) {

		$current_user = wp_get_current_user();
		if ($current_user instanceof WP_User) {
			$current_user_id = self::getUserId($current_user);
			// Is the profile update token saved in the DB?
			$rublonPUToken = self::_retrieveUpdateToken(
				$current_user_id,
				self::TRANSIENT_PROFILE_TOKEN_PREFIX
			);
			self::_clearUpdateToken(
				$current_user_id,
				self::TRANSIENT_PROFILE_TOKEN_PREFIX
			);
			if ($rublonPUToken !== false) {
				// Found a profile update token. Check if it's also present
				// in the POST data, abort otherwise.
				if (empty($post[self::PROFILE_UPDATE_TOKEN_NAME])
					|| $rublonPUToken !== $post[self::PROFILE_UPDATE_TOKEN_NAME]) {
					self::_abortConfirmation(
						self::profileUrl(),
						'MALFORMED_FORM_DATA'
					);
				} else {
					unset($_POST[self::PROFILE_UPDATE_TOKEN_NAME]);
					if (!empty($post[self::FIELD_USER_PROTECTION_TYPE])) {
						self::_setUserProtectionType(
							$current_user, 
							$post[self::FIELD_USER_PROTECTION_TYPE]
						);
					}
					if (!empty($post['email']) && $post['email'] !== self::getUserEmail($current_user)) {
						self::clearMobileUserStatus($current_user);
					}
				}
			} else {
				// See if a Rublon-confirmed change is in the works.
				$change = self::_pUChangeRequiresConfirmation($post);
				if ($change > 0) {
					self::_storeForm(
						$current_user_id,
						$post,
						self::TRANSIENT_PROFILE_FORM_PREFIX,
						self::UPDATE_FORM_LIFETIME
					);
					// Confirm transaction with Rublon
					self::_confirmPUWithRublon($post, $change);
				} else {
					// Let the update go forth.
					$rublonPUToken = ($token != null) ? $token : self::_generateToken();
					$post[self::PROFILE_UPDATE_TOKEN_NAME] = $rublonPUToken;
					self::_storeForm(
						$current_user_id,
						$post,
						self::TRANSIENT_PROFILE_FORM_PREFIX,
						self::UPDATE_FORM_LIFETIME
					);
					self::_storeUpdateToken(
						$current_user_id,
						$rublonPUToken,
						self::TRANSIENT_PROFILE_TOKEN_PREFIX,
						self::UPDATE_TOKEN_LIFETIME
					);
					self::_reloadParentFrame(
						__('Your profile is being updated.', 'rublon'),
						true
					);
				}
			}
		}

	}

	
	static public function checkPostDataAddSettUpdate($post, $new_value, $old_value) {

		$current_user = wp_get_current_user();
		if ($current_user instanceof WP_User) {
			$current_user_id = self::getUserId($current_user);
			// Is the addSett update token saved in the DB?
			$rublonASUToken = self::_retrieveUpdateToken(
				$current_user_id,
				self::TRANSIENT_ADDSETT_TOKEN_PREFIX
			);
			self::_clearUpdateToken(
				$current_user_id,
				self::TRANSIENT_ADDSETT_TOKEN_PREFIX
			);
			if ($rublonASUToken !== false) {
				// Found an addSett update token. Check if it's also present
				// in the POST data, abort otherwise.
				if (empty($post[self::ADDSETT_UPDATE_TOKEN_NAME])
				|| $rublonASUToken !== $post[self::ADDSETT_UPDATE_TOKEN_NAME]) {
					self::_abortConfirmation(
						self::rublonUrl(),
						'MALFORMED_FORM_DATA'
					);
				} else {
					unset($_POST[self::ADDSETT_UPDATE_TOKEN_NAME]);
					return $new_value;
				}
			} else {
				// See if a Rublon-confirmed change is in the works.
				$change = self::_aSUChangeRequiresConfirmation($new_value, $old_value);
				if ($change > 0) {
					self::_storeForm(
						$current_user_id,
						$post,
						self::TRANSIENT_ADDSETT_FORM_PREFIX,
						self::UPDATE_FORM_LIFETIME
					);
					// Confirm transaction with Rublon
					self::_confirmASUWithRublon($post, $change);
				} else {
					// Let the update go forth.
					$rublonASUToken = ($token != null) ? $token : self::_generateToken();
					$post[self::ADDSETT_UPDATE_TOKEN_NAME] = $rublonASUToken;
					self::_storeForm(
						$current_user_id,
						$post,
						self::TRANSIENT_ADDSETT_FORM_PREFIX,
						self::UPDATE_FORM_LIFETIME
					);
					self::_storeUpdateToken(
						$current_user_id,
						$rublonASUToken,
						self::TRANSIENT_ADDSETT_TOKEN_PREFIX,
						self::UPDATE_TOKEN_LIFETIME
					);
					self::_redirectParentFrame(
						self::optionsUrl(),
						__('Rublon settings are being updated.', 'rublon'),
						true
					);
				}
			}
		}

	}

	
	static private function _confirmPUWithRublon($post, $change) {
	
		$current_user = wp_get_current_user();
		$user_id = self::getUserId($current_user);
		$user_email = self::getUserEmail($current_user);
	
		$rublon = self::getRublon();
	
		$authParams = array();
		$roleProtectionType = self::roleProtectionType($current_user);
		$userProtectionType = self::userProtectionType($current_user);
		if ($roleProtectionType == self::PROTECTION_TYPE_EMAIL || $userProtectionType == self::PROTECTION_TYPE_EMAIL) {
			$authParams[RublonAuthParams::FIELD_CAN_USE_EMAIL2FA] = true;
		}
		$authParams[self::FLAG_PROFILE_UPDATE] = true;
		$authParams['customURIParam'] = self::FLAG_PROFILE_UPDATE;
	
		$msg = __('Do you confirm changing your %s?', 'rublon');
		$msg2 = '';
		if ($change > 3) {
			$change -= 4;
			$msg2 = __('protection type', 'rublon');
		}
		if ($change > 1) {
			$change -= 2;
			if (!empty($msg2)) {
				if ($change > 0) {
					$msg2 .= __(', your ', 'rublon');
				} else {
					$msg2 .= __(' and your ', 'rublon');
				}
			}
			$msg2 .= sprintf(__('email address to: %s', 'rublon'), $post['email']);
		}
		if ($change > 0) {
			$change -= 1;
			if (!empty($msg2)) {
				$msg2 .= __(', as well as your ', 'rublon');
			}
			$msg2 .= __('password', 'rublon');
		}
		$msg = sprintf($msg, $msg2);
	
		try {
			$authUrl = $rublon->confirm(
				self::getActionURL('confirm'),
				$user_id,
				$user_email,
				$msg,
				$authParams
			);
			if (!empty($authUrl)) {
				wp_redirect($authUrl);
				exit;
			} else {
				if ($roleProtectionType == self::PROTECTION_TYPE_MOBILE) {
					self::_abortConfirmation(
						self::profileUrl(),
						'MOBILE_APP_REQUIRED'
					);
				} else {
					$rublonPUToken = self::_generateToken();
					$post[self::PROFILE_UPDATE_TOKEN_NAME] = $rublonPUToken;
					self::_storeForm(
						$user_id,
						$post,
						self::TRANSIENT_PROFILE_FORM_PREFIX,
						self::UPDATE_FORM_LIFETIME
					);
					self::_storeUpdateToken(
						$user_id,
						$rublonPUToken,
						self::TRANSIENT_PROFILE_TOKEN_PREFIX,
						self::UPDATE_TOKEN_LIFETIME
					);
					self::_reloadParentFrame(
						__('Your profile is being updated.', 'rublon'),
						true
					);
				}
			}
		} catch (RublonException $e) {
			self::_handleCallbackException($e);
			self::_abortConfirmation(self::profileUrl());
		}
	}


	static private function _confirmASUWithRublon($post, $change) {

		$current_user = wp_get_current_user();
		$user_id = self::getUserId($current_user);
		$user_email = self::getUserEmail($current_user);

		$rublon = self::getRublon();
		
		$authParams = array();
		$roleProtectionType = self::roleProtectionType($current_user);
		$userProtectionType = self::userProtectionType($current_user);
		if ($roleProtectionType == self::PROTECTION_TYPE_EMAIL || $userProtectionType == self::PROTECTION_TYPE_EMAIL) {
			$authParams[RublonAuthParams::FIELD_CAN_USE_EMAIL2FA] = true;
		}
		$authParams[self::FLAG_ADDSETT_UPDATE] = true;
		$authParams['customURIParam'] = self::FLAG_ADDSETT_UPDATE;

		$msg = __('Do you confirm changing default protection levels?', 'rublon');

		try {
			$authUrl = $rublon->confirm(
				self::getActionURL('confirm'),
				$user_id,
				$user_email,
				$msg,
				$authParams
			);
			if (!empty($authUrl)) {
				wp_redirect($authUrl);
				exit;
			} else {
				if ($roleProtectionType == self::PROTECTION_TYPE_MOBILE) {
					self::_abortConfirmation(
						self::rublonUrl(),
						'MOBILE_APP_REQUIRED'
					);
				} else {
					$rublonASUToken = self::_generateToken();
					$post[self::ADDSETT_UPDATE_TOKEN_NAME] = $rublonASUToken;
					self::_storeForm(
						$user_id,
						$post,
						self::TRANSIENT_ADDSETT_FORM_PREFIX,
						self::UPDATE_FORM_LIFETIME
					);
					self::_storeUpdateToken(
						$user_id,
						$rublonASUToken,
						self::TRANSIENT_ADDSETT_TOKEN_PREFIX,
						self::UPDATE_TOKEN_LIFETIME
					);
					self::_redirectParentFrame(
						self::optionsUrl(),
						__('Rublon settings are being updated.', 'rublon'),
						true
					);
				}
			}
		} catch (RublonException $e) {
			self::_handleCallbackException($e);
			self::_abortConfirmation(self::rublonUrl());
		}		

	}


	static private function _storeForm($user_id, $form, $transient_prefix, $transient_lifetime) {

		set_transient(
			$transient_prefix . $user_id,
			$form,
			$transient_lifetime * MINUTE_IN_SECONDS
		);

	}


	static private function _retrieveForm($user_id, $transient_prefix) {

		return get_transient($transient_prefix . $user_id);

	}


	static private function _clearForm($user_id, $transient_prefix) {
	
		delete_transient($transient_prefix . $user_id);
	
	}


	static private function _storeUpdateToken($user_id, $token, $transient_prefix, $transient_lifetime) {

		set_transient(
			$transient_prefix . $user_id,
			$token,
			$transient_lifetime * MINUTE_IN_SECONDS
		);

	}


	static private function _retrieveUpdateToken($user_id, $transient_prefix) {

		return get_transient($transient_prefix . $user_id);

	}


	static private function _clearUpdateToken($user_id, $transient_prefix) {

		delete_transient($transient_prefix . $user_id);

	}


	static public function setMobileUserStatus(WP_User $user, $mobile_status = self::YES) {

		$user_id = self::getUserId($user);
		set_transient(
			self::TRANSIENT_MOBILE_USER . $user_id,
			$mobile_status,
			self::MOBILE_USER_INFO_LIFETIME * MINUTE_IN_SECONDS
		);

	}


	static public function getMobileUserStatus(WP_User $user, $refresh = false) {

		$user_id = self::getUserId($user);
		$mobile_user_status = get_transient(self::TRANSIENT_MOBILE_USER . $user_id);
		if ($refresh && $mobile_user_status === false) {
			$rublon_req = new RublonRequests();
			$mobile_user_status = $rublon_req->checkMobileStatus($user);
			self::setMobileUserStatus($user, $mobile_user_status);
		}
		return $mobile_user_status;

	}


	static public function clearMobileUserStatus(WP_User $user) {

		$user_id = self::getUserId($user);
		delete_transient(self::TRANSIENT_MOBILE_USER . $user_id);

	}


	/**
	 * Display the "Busy" page
	 * 
	 * @param array $content An array of text and script to be displayed
	 */
	static private function _displayBusyPageWithContent($content) {

		$pageTemplate = self::pageTemplate();
		$busyPageContentTemplate = self::busyPageContentTemplate();
		$styles = self::busyPageStyles(true);
		$pageBody = sprintf($busyPageContentTemplate,
			'',
			$content['text'],
			$content['script']
		);
		$resultingPage = sprintf($pageTemplate,
			__('Profile update', 'rublon'),
			$styles,
			$pageBody
		);
		echo $resultingPage;
		exit;

	}


	/**
	 * Check for Rublon protected fields
	 * 
	 * Some of the profile fields are protected by Rublon-based
	 * confirmation. These include user email and password.
	 * 
	 * @param array $post
	 * @return boolean
	 */
	static private function _pUChangeRequiresConfirmation($post = array()) {

		$change = 0;
		if (!empty($post['pass1'])) {
			$change += 1;
		}
		$current_user = wp_get_current_user();
		if (!empty($post['email']) && $post['email'] !== self::getUserEmail($current_user)) {
			$change += 2;
		}
		$userProtectionType = self::userProtectionType($current_user);
		if (!empty($post[self::FIELD_USER_PROTECTION_TYPE])
			&& $post[self::FIELD_USER_PROTECTION_TYPE] == self::PROTECTION_TYPE_NONE
			&& $userProtectionType == self::PROTECTION_TYPE_EMAIL) {
			$change += 4;
		}
		return $change;

	}

	static private function _aSUChangeRequiresConfirmation($new_value, $old_value) {

		$levels = array(
			self::PROTECTION_TYPE_NONE => 0,
			self::PROTECTION_TYPE_EMAIL => 1,
			self::PROTECTION_TYPE_MOBILE => 2,
		);
		$change = 0;
		foreach ($new_value as $key => $value) {
			if (strpos($key, self::PROTECTION_TYPE_SETT_PREFIX) !== false && isset($old_value[$key])) {
				if ($levels[$value] < $levels[$old_value[$key]]) {
					$change++;
				}
			}
		}
		return $change;

	}


	/**
	 * Handle transaction confirmation
	 *
	 */
	static public function handleConfirmation()	{
	
		try {
			$callback = new Rublon2FactorCallback(self::getRublon());
			$callback->call(
				'RublonHelper::confirmationSuccess',
				'RublonHelper::confirmationFailure'
			);
		} catch (RublonException $e) {
			self::_handleConfirmationException($e);
			$failureUrl = self::_determineConfirmationReturnUrl();
			self::_abortConfirmation($failureUrl);
		}
	
	}
	
	
	static public function confirmationSuccess($user_id, $callback) {
	
		$fallbackUrl = self::_determineConfirmationReturnUrl();
		try {
			$user = get_user_by('id', $user_id);
			if ($user) {
				$usingEmail2FA = $callback->getConsumerParam(RublonAPIClient::FIELD_USING_EMAIL2FA);
				if (!$usingEmail2FA) {
					self::setMobileUserStatus($user);
				}
				if (RublonAPICredentials::CONFIRM_RESULT_YES == $callback->getCredentials()->getConfirmResult()) {
					$consumerParams = $callback->getCredentials()->getResponse();
					if (!empty($consumerParams['result'])) {
						if (!empty($consumerParams['result'][self::FLAG_PROFILE_UPDATE])) {
							$process_type = self::FLAG_PROFILE_UPDATE;
							$transient_form_prefix = self::TRANSIENT_PROFILE_FORM_PREFIX;
							$transient_token_prefix = self::TRANSIENT_PROFILE_TOKEN_PREFIX;
							$update_token_name = self::PROFILE_UPDATE_TOKEN_NAME;
						} elseif (!empty($consumerParams['result'][self::FLAG_ADDSETT_UPDATE])) {
							$process_type = self::FLAG_ADDSETT_UPDATE;
							$transient_form_prefix = self::TRANSIENT_ADDSETT_FORM_PREFIX;
							$transient_token_prefix = self::TRANSIENT_ADDSETT_TOKEN_PREFIX;
							$update_token_name = self::ADDSETT_UPDATE_TOKEN_NAME;
						} else {
							self::_abortConfirmation(
								$fallbackUrl,
								'MALFORMED_AUTHENTICATION_DATA'
							);
						}
						$rublonUpdateToken = self::_generateToken();
						$post = self::_retrieveForm(
							$user_id,
							$transient_form_prefix
						);
						$post[$update_token_name] = $rublonUpdateToken;
						self::_storeForm(
							$user_id,
							$post,
							$transient_form_prefix,
							self::UPDATE_FORM_LIFETIME
						);
						self::_storeUpdateToken(
							$user_id,
							$rublonUpdateToken,
							$transient_token_prefix,
							self::UPDATE_TOKEN_LIFETIME
						);
						switch ($process_type) {
							case self::FLAG_PROFILE_UPDATE:
								self::_reloadParentFrame(
									__('Your profile is being updated.', 'rublon'),
									true
								);
								break;
							case self::FLAG_ADDSETT_UPDATE:
								self::_redirectParentFrame(
									self::optionsUrl(),
									__('Rublon settings are being updated.', 'rublon'),
									true
								);
								break;
						}
						
					} else {
						self::_abortConfirmation(
							$fallbackUrl,
							'ERRONEOUS_AUTHENTICATION_DATA'
						);
					}
				} else {
					self::_cancelConfirmation($fallbackUrl);
				}
			} else {
				self::_abortConfirmation(
					$fallbackUrl,
					'USER_NOT_FOUND'
				);
			}
		} catch (RublonException $e) {
			self::_handleConfirmationException($e);
			self::_abortConfirmation($fallbackUrl);
		}
	
	}


	static public function confirmationFailure($callback) {

		$failureUrl = self::_determineConfirmationReturnUrl();
		self::_abortConfirmation(
			$failureUrl,
			'RUBLON_OPERATION_CANCELLED'
		);

	}


	static private function _abortConfirmation($url, $errorCode = null, $frame = true) {

		if ($errorCode !== null) {
			self::setMessage($errorCode, 'error', 'TC');
		}

		if (!$frame) {
			wp_safe_redirect($url);
			exit;
		} else {
			self::_redirectParentFrame(
				$url,
				__('Operation aborted.', 'rublon'),
				true
			);
		}

	}


	static private function _cancelConfirmation($url) {

		self::_redirectParentFrame(
			$url,
			__('Operation cancelled.', 'rublon'),
			true
		);

	}


	static private function _handleConfirmationException($e) {
	
		self::_handleCallbackException($e, 'TC');
	
	}


	static private function _handleRegistrationException($e) {

		self::_handleCallbackException($e, 'CR');

	}


	/**
	 * Handle the Rublon callback
	 * 
	 */
	static public function handleCallback()	{

		try {
			$callback = new Rublon2FactorCallback(self::getRublon());
			$callback->call(
				'RublonHelper::callbackSuccess',
				'RublonHelper::callbackFailure'
			);
		} catch (RublonException $e) {
			self::_handleCallbackException($e);
			self::_returnToPage();
		}

	}


	/**
	 * Handle possible RublonExceptions
	 * 
	 * @param RublonException $e
	 */
	static private function _handleCallbackException($e, $prefix = 'RC') {

		$errorCode = $e->getCode();
		switch($errorCode) {
			case Rublon2FactorCallback::ERROR_MISSING_ACCESS_TOKEN:
				$errorCode = 'MISSING_ACCESS_TOKEN';
				break;
			case Rublon2FactorCallback::ERROR_REST_CREDENTIALS:
				$errorCode = 'REST_CREDENTIALS_FAILURE';
				$previous = $e->getPrevious();
				if (!empty($previous)) {
					if ($previous->getCode() == RublonException::CODE_TIMESTAMP_ERROR) {
						$errorCode = 'CODE_TIMESTAMP_ERROR';
					} else {
						$additionalErrorMessage = __('Error details: ', 'rublon') . $previous->getMessage();
					}
				}
				break;
			case Rublon2FactorCallback::ERROR_USER_NOT_AUTHORIZED:
				$errorCode = 'USER_NOT_AUTHENTICATED';
				break;
			case Rublon2FactorCallback::ERROR_DIFFERENT_USER:
				$errorCode = 'DIFFERENT_USER';
				break;
			case Rublon2FactorCallback::ERROR_API_ERROR:
				$errorCode = 'API_ERROR';
				break;
			default:
				$errorCode = 'API_ERROR';
		}
		
		self::setMessage($errorCode, 'error', $prefix);
		
		// prepare message for issue notifier
		$notifierMessage = 'RublonCallback error.<br /><br />' . __('Rublon error code: ', 'rublon') . '<strong>' . $prefix . '_' . $errorCode . '</strong>';
		if (!empty($additionalErrorMessage))
			$notifierMessage .= '<br />' . $additionalErrorMessage;
		
		// send issue notify
		try {
			self::notify(array('msg' => $notifierMessage));
		} catch (Exception $e) {
			// Do nothing.
		}		

	}


	/**
	 * Handle a successful authentication with Rublon
	 * 
	 * @param string $user_id
	 * @param Rublon2FactorCallback $callback
	 */
	static public function callbackSuccess($user_id, $callback) {

		$user = get_user_by('id', $user_id);
		if ($user) {
			$usingEmail2FA = $callback->getConsumerParam(RublonAPIClient::FIELD_USING_EMAIL2FA);
			if (!$usingEmail2FA) {
				self::setMobileUserStatus($user);
			}
			$ACMStatus = $callback->getConsumerParam(RublonAuthParams::FIELD_ACCESS_CONTROL_MANAGER_ALLOWED);
			if ($ACMStatus === true) {
				self::setACMPermission(self::YES);
			} else {
				self::setACMPermission(self::NO);
			}
			$remember = $callback->getConsumerParam('remember');
			wp_logout();
			RublonCookies::setLoggedInCookie($user, $remember);
			RublonCookies::setAuthCookie($user, $remember);
			do_action('wp_login', $user->user_login, $user);
		}
		self::_returnToPage();

	}


	/**
	 * Handle a failed or cancelled authentication
	 * 
	 * @param unknown $callback
	 */
	static public function callbackFailure($callback) {

		self::setMessage('CALLBACK_ERROR', 'error', 'RC');
		self::_returnToPage();

	}


	/**
	 * Perform Rublon2Factor authentication
	 * 
	 */
	static public function authenticateWithRublon($user, $protection_type, $remember = false) {

		$rublon = self::getRublon();
		$here = RublonCookies::getReturnURL();
		$authParams = array(
			'remember' => $remember,
		);
		if (in_array(self::PROTECTION_TYPE_EMAIL, $protection_type)) {
			$authParams[RublonAuthParams::FIELD_CAN_USE_EMAIL2FA] = true;
		}
		if (!empty($here)) {
			$authParams['customURIParam'] = '[[CUSTOM]]' . $here;
		}
		try {
			$authUrl = $rublon->auth(
				self::getActionURL('callback'),
				self::getUserId($user),
				self::getUserEmail($user),
				$authParams
			);
			return $authUrl;
		} catch (RublonException $e) {
			return '';
		}

	}


	/**
	 * Return the plugin settings
	 * 
	 * @param string $group Settings group
	 */
	static public function getSettings($group = '') {

		switch ($group) {
			case 'additional':
				$key = self::RUBLON_ADDITIONAL_SETTINGS_KEY;
				break;
			default:
				$key = self::RUBLON_SETTINGS_KEY;
		}
		$settings = get_option($key);
		if (!$settings) {
			$settings = array();
		}
		return $settings;

	}


	/**
	 * Save the plugin settings
	 * 
	 * @param array $settings Settings to be saved
	 * @param string $group Settings group 
	 */
	static public function saveSettings($settings, $group = '') {

		switch ($group) {
			case 'additional':
				$key = self::RUBLON_ADDITIONAL_SETTINGS_KEY;
				break;
			default:
				$key = self::RUBLON_SETTINGS_KEY;
		}
		update_option($key, $settings);

		do_action('rublon_save_settings', $settings, $group);

	}


	static public function shouldPluginAttemptRegistration() {

		$settings = self::getSettings();
		return !empty($settings['attempt-registration']);

	}
	
	
	static public function enqueueRegistration($deny = false) {

		$settings = self::getSettings();
		$settings['attempt-registration'] = $deny;
		self::saveSettings($settings);

	}
	
	static public function registerSite() {

		$current_user = wp_get_current_user();
		$rublonGUI = new Rublon2FactorGUIWordPress(
			self::getRublon(),
			self::getUserId($current_user),
			self::getUserEmail($current_user)
		);
		wp_redirect($rublonGUI->getActivationURL());
		exit;		

	}


	/**
	 * Retrieve message codes from helper and prepare them for viewing
	 * 
	 * @return array|null
	 */
	static public function getMessages() {

		if (!empty(self::$cookies['messages'])) {
			$messages = self::$cookies['messages'];
			unset(self::$cookies['messages']);
			return self::_explainMessages($messages);
		}
		return null;

	}


	/**
	 * Store a message in the plugin cookies
	 *
	 * @param string $code Message code
	 * @param string $type Message type
	 * @param string $origin Message origin
	 */
	static public function setMessage($code, $type, $origin) {
	
		$msg = $type . '__' . $origin . '__' . $code;
		RublonCookies::storeMessageInCookie($msg);

	}


	/**
	 * Transform message codes into messages themselves
	 * 
	 * @param array $messages Message "headers" retrieved from plugin cookie
	 * @return array
	 */
	static private function _explainMessages($messages) {

		$result = array();
		foreach ($messages as $message) {
			$msg = explode('__', $message);
			$msgType = $msg[0];
			$msgOrigin = $msg[1];
			$msgCode = $msg[2];
			if ($msgType == 'error') {
				$no_code = false;
				switch ($msgOrigin) {
					case 'RC':
					case 'TC':
						$errorMessage = __('There was a problem during the authentication process.', 'rublon');
						break;
					case 'CR':
						$errorMessage = __('Rublon activation failed. Please try again. Should the error occur again, contact us at <a href="mailto:support@rublon.com">support@rublon.com</a>.', 'rublon');
						break;
				}
				$errorCode = $msgOrigin . '_' . $msgCode;
				switch ($errorCode) {
					case 'RC_ALREADY_PROTECTED':
						$errorMessage = __('You cannot protect an account already protected by Rublon.', 'rublon');
						break;
					case 'RC_CANNOT_PROTECT_ACCOUNT':
						$errorMessage = __('Unable to protect your account with Rublon.', 'rublon');
						break;
					case 'RC_CANNOT_DISABLE_ACCOUNT_PROTECTION':
						$errorMessage = __('Unable to disable Rublon protection.', 'rublon');
						break;
					case 'CR_PLUGIN_OUTDATED':
						$errorMessage = sprintf(__('The version of Rublon for Wordpress that you are trying to activate is outdated. Please go to the <a href="%s">Plugins</a> page and update it to the newest version or', 'rublon'), admin_url('plugins.php'))
						. '<a href="' . esc_attr(wp_nonce_url( self_admin_url('update.php?action=upgrade-plugin&plugin=') . plugin_basename(RUBLON2FACTOR_PLUGIN_PATH), 'upgrade-plugin_' . plugin_basename(RUBLON2FACTOR_PLUGIN_PATH))) . '">'
								. ' <strong>' . __('update now', 'rublon') . '</strong></a>.';
						break;
					case 'CR_PLUGIN_REGISTERED_NO_PROTECTION':
						$errorMessage = sprintf(__('Thank you! Now all of your users can protect their accounts with Rublon. However, there has been a problem with protecting your account. Go to <a href="%s">Rublon page</a> in order to protect your account.', 'rublon'), admin_url(self::WP_RUBLON_PAGE));
						break;
					case 'LM_ROLE_BLOCKED':
						$no_code = true;
						$errorMessage = __('The authentication process has been halted.', 'rublon') . ' ' . __('This site\'s administrator requires that you protect your account with the Rublon mobile app.', 'rublon')
						. '<br /><br />' . __('Rublon protects against intruders who found out your password.', 'rublon')
						. '<br /><br />' . sprintf(__('Learn more at <a href="%s" target="_blank">www.rublon.com</a>.', 'rublon'), self::rubloncomUrl());
						$errorMessage = str_replace('target="_blank"', 'target="_blank" class="rublon-link"', $errorMessage);
						break;
					case 'CR_SYSTEM_TOKEN_INVALID_RESPONSE_TIMESTAMP':
					case 'CR_INVALID_RESPONSE_TIMESTAMP':
					case 'RC_CODE_TIMESTAMP_ERROR':
					case 'TC_CODE_TIMESTAMP_ERROR':
						$errorMessage = __('Your server\'s time seems out of sync. Please check that it is properly synchronized - Rublon won\'t be able to verify your website\'s security otherwise.', 'rublon');
						break;
					case 'TC_MOBILE_APP_REQUIRED':
						$no_code = true;
						$errorMessage = __('The authentication process has been halted.', 'rublon') . ' ' . __('This site\'s administrator requires you to confirm this operation using the Rublon mobile app.', 'rublon')
						. ' ' . __('Learn more at <a href="https://rublon.com" target="_blank">www.rublon.com</a>.', 'rublon');
						break;
				}
				$result[] = array('message' => $errorMessage, 'type' => $msgType);
				if (!$no_code) {
					$result[] = array('message' => __('Rublon error code: ', 'rublon') . '<strong>' . $errorCode . '</strong>', 'type' => $msgType);
				}
			} elseif ($msgType == 'updated') {
				$updatedMessage = '';
				$updatedCode = $msgOrigin . '_' . $msgCode;
				switch ($updatedCode) {
					case 'RC_ACCOUNT_PROTECTED':
						$updatedMessage = __('Your account is now protected by Rublon.', 'rublon');
						break;
					case 'RC_PROTECTION_DISABLED':
						$updatedMessage = __('Rublon protection has been disabled. You are now protected by a password only, which may result in unauthorized access to your account. We strongly encourage you to protect your account with Rublon.', 'rublon');
						break;
					case 'CR_PLUGIN_REGISTERED':
						$updatedMessage = __('Thank you! Now all of your users can protect their accounts with Rublon.', 'rublon');
						break;
					case 'POSTL_AUTHENTICATION_TYPE_CHANGED':
 						$updatedMessage = __('Since Rublon plugin version 2.0, the authentication process has been changed significantly. The accounts are now protected using the email address. We have detected that your WordPress account\'s email address differs from the one you used to create your account in the Rublon mobile app. Please change your WordPress account\'s email address accordingly or add your WordPress account\'s email address in the "Email addresses" section of the Rublon mobile app.', 'rublon');
						break;
				}
				$result[] = array('message' => $updatedMessage, 'type' => $msgType);
			}
		}
		return $result;

	}


	/**
	 * Check if plugin is registered
	 *
	 * @return boolean
	 */
	static public function isSiteRegistered() {

		$settings = self::getSettings();
		return (!empty($settings) && !empty($settings['rublon_system_token']) && !empty($settings['rublon_secret_key']));

	}


	/**
	 * Retrieves plugin's version from the settings
	 *
	 * @return string
	 */
	static private function _getSavedPluginVersion() {

		$settings = apply_filters('rublon_get_settings', self::getSettings());
		return (!empty($settings) && !empty($settings['rublon_plugin_version'])) ? $settings['rublon_plugin_version'] : '';

	}


	/**
	 * Retrieve plugin's version from the plugin's file
	 * 
	 * @return string
	 */
	static public function getCurrentPluginVersion() {

		if (!function_exists('get_plugin_data')) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$pluginData = get_plugin_data(RUBLON2FACTOR_PLUGIN_PATH);
		return (!empty($pluginData) && !empty($pluginData['Version'])) ? $pluginData['Version'] : '';
		

	}


	/**
	 * Update the rublon_plugin_version field in the plugin's options
	 * 
	 * @param string $version Plugin's current version
	 */
	static private function _setPluginVersion($version) {

		$settings = self::getSettings();
		$settings['rublon_plugin_version'] = $version;
		self::saveSettings($settings);

	}


	/**
	 * Updates rublon_profile_id for a given user, to turn off second authentication factor.
	 *
	 * @param int $user
	 * @return boolean
	 */
	static public function disconnectRublon2Factor($user) {

		$hasProfileId = get_user_meta(self::getUserId($user), self::RUBLON_META_PROFILE_ID, true);
		if ($hasProfileId) {
			return delete_user_meta(self::getUserId($user), self::RUBLON_META_PROFILE_ID);
		} else {
			return false;
		}

	}


	/**
	 * Check if the given user is protected by Rublon
	 *
	 * @param WP_User $user
	 * @return boolean
	 */
	static public function isUserSecured($user) {

		$rublonProfileId = get_user_meta(self::getUserId($user), self::RUBLON_META_PROFILE_ID, true);
		return self::isSiteRegistered() && !empty($rublonProfileId);

	}


	/**
	 * Check if a user has been authenticated by Rublon
	 * 
	 * @param WP_User $user
	 */
	static public function isUserAuthenticated($user, $plugin_version = '2.0.2') {

		return RublonCookies::isAuthCookieSet($user, $plugin_version);

	}


	/**
	 * Perform a consumer registration action
	 * 
	 * @param string $action
	 */
	static public function consumerRegistrationAction($action) {

		try {
			$consumerRegistration = new RublonConsumerRegistrationWordPress();
			$consumerRegistration->action($action);
		} catch (RublonException $e) {
			self::_handleRegistrationException($e);
			wp_safe_redirect(admin_url());
			exit;
		}

	}


	/**
	 * Initialize consumer registration (with a "busy" indicator) 
	 */
	static private function _initializeConsumerRegistration() {

		try {
			$consumerRegistration = new RublonConsumerRegistrationWordPress();
			$consumerRegistration->initForWordPress();		
		} catch (RublonException $e) {
			self::_handleRegistrationException($e);
			wp_safe_redirect(admin_url());
			exit;
		}
	}


	/**
	 * Prepare url pieces needed for the plugin history request
	 * 
	 * @return array
	 */
	static public function getConsumerRegistrationData() {

		$consumerRegistration = new RublonConsumerRegistrationWordPress();
		return array(
			'url' => $consumerRegistration->getAPIDomain(),
			'action' => $consumerRegistration->getConsumerActionURL()
		);

	}


	/**
	 * Send a request with plugin's history to Rublon servers
	 * 
	 * @param array $data Plugin's history data
	 */
	static public function pluginHistoryRequest($data) {

		require_once dirname(__FILE__) . '/libs/RublonImplemented/RublonAPIPluginHistory.php';

		$settings = self::getSettings();
		$rublon = new Rublon2FactorWordPress($settings['rublon_system_token'], $settings['rublon_secret_key']);
		$data['systemToken'] = $settings['rublon_system_token'];
		$request = new RublonAPIPluginHistory($rublon, $data);

		try {
			$response = $request->perform();
		} catch (Exception $e) {
			$response = null;
		}

		if (!empty($response) && $response->historyHasBeenAdded()) {
			return true;
		}
		return false;

	}


	/**
	 * Send an error report via cURL
	 * 
	 * @param string $msg Error info
	 */
	static function notify($msg) {

		$msg['bloginfo'] = get_bloginfo();
		$pluginMeta = self::preparePluginMeta();
		if (!empty($pluginMeta['meta']))
			$msg['plugin-info'] = $pluginMeta['meta'];
		$msg['phpinfo'] = self::_info();				
		
		$ch = curl_init(self::RUBLON_API_DOMAIN . self::RUBLON_NOTIFY_URL_PATH);
		$headers = array(
			"Content-Type: application/json; charset=utf-8",
			"Accept: application/json, text/javascript, */*; q=0.01"
		);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		
		curl_setopt($ch, CURLOPT_POST, true);							
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($msg));
					
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Rublon for WordPress');

		// Execute request
		curl_exec($ch);		
		if (curl_error($ch)) {
			throw new RublonException("Notifier: " . curl_error($ch), RublonException::CODE_CURL_ERROR);
		}		
		curl_close($ch);
	}


	/**
	 * Prepare server's PHP info for error reporting
	 * 
	 * @return array
	 */
	static private function _info() {
		ob_start();
		phpinfo();
		$phpinfo = array('phpinfo' => array());
		if(preg_match_all('#(?:<h2>(?:<a name=".*?">)?(.*?)(?:</a>)?</h2>)|(?:<tr(?: class=".*?")?><t[hd](?: class=".*?")?>(.*?)\s*</t[hd]>(?:<t[hd](?: class=".*?")?>(.*?)\s*</t[hd]>(?:<t[hd](?: class=".*?")?>(.*?)\s*</t[hd]>)?)?</tr>)#s', ob_get_clean(), $matches, PREG_SET_ORDER))
		    foreach($matches as $match)
		        if(strlen($match[1]))
		            $phpinfo[$match[1]] = array();
		        elseif(isset($match[3]))
		            $phpinfo[end(array_keys($phpinfo))][$match[2]] = isset($match[4]) ? array($match[3], $match[4]) : $match[3];
		        else
		            $phpinfo[end(array_keys($phpinfo))][] = $match[2];
	    return $phpinfo;		            
	}


	/**
	 * Perform any necessary actions on plugin update
	 * 
	 * @param string $from Version the plugin's being updated from
	 * @param string $to Plugin's Version the plugin's being updated to
	 */
	static private function _performUpdate($from, $to) {

		// migrate old database entries into user meta
		self::_dbMigrate();
		
		// make sure that Rublon is run before other plugins
		self::meFirst();
		
		// send update info to Rublon
		if (self::isSiteRegistered()) {
			$pluginMeta = self::preparePluginMeta();
			$pluginMeta['action'] = 'update';
			$pluginMeta['meta']['previous-version'] = $from;
			self::pluginHistoryRequest($pluginMeta);
		}
		
		// remove any deprecated cookies
		RublonCookies::cookieCleanup(array('return_url'));
		
		$user = wp_get_current_user();
		if (self::isSiteRegistered()
			&& is_user_logged_in()
			&& is_admin()
			&& self::isUserSecured($user)
			&& !self::isUserAuthenticated($user, $from)) {
			RublonCookies::setAuthCookie($user);
		}
		
		// Update auth cookie (new cookie since 2.0.2)
		if (self::isSiteRegistered()
			&& is_user_logged_in()
			&& is_admin()
			&& self::isUserAuthenticated($user, $from)
			&& !self::isUserAuthenticated($user)) {
			RublonCookies::setAuthCookie($user);
		}

		// Check for the presence of additional settings
		// and set them if they're missing
		self::_checkAdditionalSettings();

	}


	/**
	 * Check if the plugin has been updated and if so, act accordingly
	 * 
	 */
	static private function _updateChecker() {

		$savedPluginVersion = self::_getSavedPluginVersion();
		$currentPluginVersion = self::getCurrentPluginVersion();
		if (version_compare($savedPluginVersion, $currentPluginVersion, 'l')) {
			self::_performUpdate($savedPluginVersion, $currentPluginVersion);
			self::_setPluginVersion($currentPluginVersion);
		}

	}


	static private function _checkAdditionalSettings() {

		// disable XML-RPC by default
		$additional_settings = self::getSettings('additional');
		if (!isset($additional_settings['disable-xmlrpc'])) {
			$additional_settings['disable-xmlrpc'] = 'on';
			self::saveSettings($additional_settings, 'additional');
		}
		
		$adminRole = self::prepareRoleID('administrator');
		// Enable Email2FA for all roles by default
		if (!isset($additional_settings[$adminRole])) {
			global $wp_roles;
			if (!isset($wp_roles)) {
				$wp_roles = new WP_Roles();
			}
			$roles = $wp_roles->get_names();
			foreach ($roles as $role) {
				$role_id = self::prepareRoleID($role);
				$additional_settings[$role_id] = self::PROTECTION_TYPE_EMAIL;
			}
			self::saveSettings($additional_settings, 'additional');
		}

	}


	/**
	 * Remove any scheme modifications from older versions and migrate data to user meta
	 *
	 */
	static private function _dbMigrate() {
	
		global $wpdb;
	
		$user_fields = $wpdb->get_col('SHOW COLUMNS FROM ' . $wpdb->users);
		if (in_array('rublon_profile_id', $user_fields)) {
			$all_users = get_users();
			foreach ($all_users as $user) {
				if (!empty($user->rublon_profile_id)) {
					add_user_meta(self::getUserId($user), self::RUBLON_META_PROFILE_ID, $user->rublon_profile_id, true);
				}
			}
			$db_error = $wpdb->query('ALTER TABLE ' . $wpdb->users . ' DROP COLUMN `rublon_profile_id`') === false;
			if ($db_error) {
				deactivate_plugins(plugin_basename(RUBLON2FACTOR_PLUGIN_PATH), true);
				_e('Plugin requires database modification but you do not have permission to do it.', 'rublon');
				exit;
			}
		}
	
	}


	/**
	 * Prepare plugin meta data to be reported
	 * 
	 * @return array
	 */
	static public function preparePluginMeta() {

		// prepare meta for plugin history request
		$all_users = get_users();
		$roles = array();
		foreach ($all_users as $user) {
			if (!empty($user->roles))
				foreach ($user->roles as $role) {
				if (!isset($roles[$role]))
					$roles[$role] = 0;
				$roles[$role]++;
			}
		}
		$pluginMeta = array(
				'wordpress-version' => get_bloginfo('version'),
				'plugin-version' => self::getCurrentPluginVersion(),
		);
		foreach ($roles as $role => $count) {
			$pluginMeta['registered-' . $role . 's'] = $count;
		}
		$metaHeader = array(
				'meta' => $pluginMeta,
		);
		return $metaHeader;
		

	}


	/**
	 * Returns WordPress User Id
	 * 
	 * Translate uppercased key "ID" which exist in old WordPress versions (3.0-3.2).
	 * Null if the given object is not a WordPress user.
	 * 
	 * @param WP_User $user User object
	 * @return int 
	 */
	static public function getUserId($user) {

		if ($user instanceof WP_User) {
			return isset($user->ID) ? $user->ID : $user->id;
		} else {
			return 0;
		}

	}


	/**
	 * Returns WordPress User's email address
	 *
	 * Empty string if the given object is not a WordPress user.
	 *
	 * @param WP_User $user User object
	 * @return string
	 */
	static public function getUserEmail($user) {

		if ($user instanceof WP_User) {
			return $user->user_email;
		} else {
			return '';
		}

	}


	/**
	 * Returns the blog language code
	 *
	 * @return string
	 */
	static public function getBlogLanguage() {
	
		$language = get_bloginfo('language');
		$language = strtolower(substr($language, 0, 2));
		if (!in_array($language, array('en', 'pl', 'de'))) {
			$language = 'en';
		}
		return $language;
	
	}


	/**
	 * Returns the blog's technology
	 *
	 * @return string
	 */
	static public function getBlogTechnology() {

		return 'wordpress3';

	}


	/**
	 * Return the Rublon API domain
	 * 
	 * @return string
	 */
	static public function getAPIDomain() {

		return self::RUBLON_API_DOMAIN;

	}


	/**
	 * This function SHOULD NOT BE USED. It exists for l18n purposes only.
	 * 
	 */
	static private function _additionalTranslations() {

		$translation = __('Rublon provides stronger security for online accounts through invisible two-factor authentication. It protects your accounts from sign-ins from unknown devices, even if your passwords get stolen.', 'rublon');

	}


	/**
	 * Retrieve a GET-passed parameter
	 * 
	 * @param string $key
	 * @return mixed|null
	 */
	static public function uriGet($key) {

		return ((isset($_GET[$key])) ? $_GET[$key] : null);

	}


	/**
	 * Retrieve a POST-passed parameter
	 * 
	 * @param string $key
	 * @return mixed|null
	 */
	static public function formGet($key) {

		return ((isset($_POST[$key])) ? $_POST[$key] : null);

	}


	/**
	 * Retrieve return page in the Admin Panel received via GET
	 * 
	 */
	static public function getReturnPage() {

		$page = admin_url();
		$custom = self::uriGet('custom');
		if (!empty($custom)) {
			switch ($custom) {
				case 'rublon':
					$page = admin_url(self::WP_RUBLON_PAGE);
					break;
				case self::FLAG_PROFILE_UPDATE:
					$page = self::profileUrl();
					break;
				default:
					$page = urldecode(str_replace('[[CUSTOM]]', '', $custom));
			}
		}
		return $page;

	}


	/**
	 * Re-orders the active plugin list so that Rublon is always run first
	 * 
	 */
	static public function meFirst() {

		$plugin_list = get_option('active_plugins');
		$me = plugin_basename(RUBLON2FACTOR_PLUGIN_PATH);
		$my_plugin_position = array_search($me, $plugin_list);
		if ($my_plugin_position) {
			array_splice($plugin_list, $my_plugin_position, 1);
			array_unshift($plugin_list, $me);
			update_option('active_plugins', $plugin_list);
		}

	}


	/**
	 * Sets the XML-RPC API access status
	 * 
	 * Checks if XML-RPC API has been disabled in the plugin settings
	 * and if yes, prevents any access to it.
	 * 
	 */
	static private function _checkXMLRPCStatus() {

		$settings = self::getSettings('additional');
		if(!empty($settings['disable-xmlrpc']) && $settings['disable-xmlrpc'] == 'on') {
			add_filter('xmlrpc_enabled', '__return_false');
		}

	}


	/**
	 * Check for a registration attempt
	 * 
	 * Checks if a plugin registration attempt has been
	 * queued and perform it if it has.
	 * 
	 * @return void
	 */
	static public function checkRegistration() {

		$attemptRegistration = self::shouldPluginAttemptRegistration();
		if ($attemptRegistration && current_user_can('manage_options')) {
			self::enqueueRegistration(false);
			self::newNonce();
			self::registerSite();
		}

		do_action('rublon_site_registration');

	}


	/**
	 * Create and store a new nonce for further use once headers are sent
	 * 
	 */
	static public function newNonce() {

		$nonce = wp_create_nonce('rublon=init-registration');
		self::$nonce = $nonce;
		RublonCookies::storeNonceInCookie($nonce);

	}


	/**
	 * Retrieve nonce and clear it
	 * 
	 * @return string
	 */
	static public function getNonce() {

		return self::$nonce;

	}


	/**
	 * Getter for pre-render data
	 *
	 * @param string $key Data key
	 * @param boolean $clear Clear data upon retrieval
	 * @return array
	 */
	static public function getPrerenderData($key, $clear = false) {
		$data = array();
		if (!empty(self::$pre_render_data[$key])) {
			$data = self::$pre_render_data[$key];
			if (!empty($clear)) {
				unset(self::$pre_render_data[$key]);
			}
		}
		return $data;
	}


	/**
	 * Setter for pre-render data
	 *
	 * @param string $key
	 * @param mixed $data
	 */
	static public function setPrerenderData($key, $data) {
		if (!empty($data)) {
			if (!is_array($data)) {
				$data = array($data);
			}
			self::$pre_render_data[$key] = $data;
		}
	}


	/**
	 * Checks if a given URL points to an Administrator Panel page
	 * 
	 * The method assumes that if a given URL points to an Admin
	 * Panel page, it contains the Admin Panel URL, so it must be
	 * a full URL path.
	 * 
	 * @param string $url
	 * @return boolean
	 */
	static public function isAdminURL($url) {

		$admin_url = admin_url();
		if (substr($url, -1) == '/')
			$admin_url = trailingslashit($admin_url);
		$url_no_scheme = preg_replace('/http(s)?:\/\//', '', $url);
		$admin_url_no_scheme = preg_replace('/http(s)?:\/\//', '', $admin_url);
		return (strpos($url_no_scheme, $admin_url_no_scheme) !== false);
		

	}


	/**
	 * Extends a given URL to its full form 
	 * 
	 * @param string $url
	 * @return string
	 */
	static public function normalizeURL($url) {

		if (!preg_match('/http(s)?:\/\//', $url))
			$url = 'http://' . $url;
		if (self::isAdminURL($url))
			if (defined('FORCE_SSL_ADMIN'))
				if (FORCE_SSL_ADMIN)
					$url = preg_replace('/http:\/\//', 'https://', $url);
		return $url;

	}


	/**
	 * Create a Rublon action URL based on the site URL
	 * 
	 * @param string $action
	 */
	static public function getActionURL($action) {

		return site_url('?rublon=' . $action);

	}


	/**
	 * Prepare a role ID.
	 *
	 * The role ID is derived from the role's name and will be used
	 * in its setting name in the additional settings.
	 *
	 * @param string $role_name Role name
	 * @return string
	 */
	static public function prepareRoleID($role_name) {
	
		return self::PROTECTION_TYPE_SETT_PREFIX . strtolower(preg_replace('/[\W]/', '-', before_last_bar($role_name)));
	
	}


	/**
	 * Check if any of the user's roles requires 2FA 
	 * 
	 * @param WP_User $user
	 * @return boolean
	 */
	static public function roleProtectionType($user) {

		$settings = self::getSettings('additional');
		$roleProtectionType = self::PROTECTION_TYPE_NONE;
		foreach ($user->roles as $role) {
			$role_id = self::prepareRoleID($role);
			if (!empty($settings[$role_id]) && $settings[$role_id] == self::PROTECTION_TYPE_EMAIL && $roleProtectionType == self::PROTECTION_TYPE_NONE) {
				$roleProtectionType = self::PROTECTION_TYPE_EMAIL;
			} elseif (!empty($settings[$role_id]) && $settings[$role_id] == self::PROTECTION_TYPE_MOBILE) {
				$roleProtectionType = self::PROTECTION_TYPE_MOBILE;
			}
		}
		return $roleProtectionType;

	}


	static public function userProtectionType($user) {

		$userProtectionType = get_user_meta(self::getUserId($user), self::RUBLON_META_PROTECTION_TYPE, true);
		if ($userProtectionType) {
			return $userProtectionType;
		} else {
			return self::PROTECTION_TYPE_NONE;
		}

	}


	static private function _setUserProtectionType($user, $type) {

		update_user_meta(
			self::getUserId($user),
			self::RUBLON_META_PROTECTION_TYPE,
			$type
		);

	}


	static public function isUserProtected($user, &$protection_type = self::NO) {

		$role_protection_type = self::roleProtectionType($user);
		$user_protection_type = self::userProtectionType($user);
		$mobile_user_status = self::getMobileUserStatus($user, true);
		if ($protection_type == self::YES) {
			if ($mobile_user_status == self::YES
				|| $role_protection_type == self::PROTECTION_TYPE_MOBILE) {
				$protection_type = self::PROTECTION_TYPE_MOBILE;
			} elseif ($role_protection_type == self::PROTECTION_TYPE_EMAIL
				|| $user_protection_type == self::PROTECTION_TYPE_EMAIL) {
				$protection_type = self::PROTECTION_TYPE_EMAIL;
			} else {
				$protection_type = self::PROTECTION_TYPE_NONE;
			}
		}
		return (
			$role_protection_type !== self::PROTECTION_TYPE_NONE
			|| $user_protection_type !== self::PROTECTION_TYPE_NONE
			|| $mobile_user_status == self::YES
		);

	}


	/**
	 * Redirection after authentication
	 */
	static private function _returnToPage($returnUrl = null) {

		if (!$returnUrl) {
			$returnUrl = self::getReturnPage();			
		}
		$returnUrl = (!empty($returnUrl)) ? $returnUrl : admin_url();
		$returnUrl = self::normalizeURL($returnUrl);
		wp_safe_redirect($returnUrl);
		exit;

	}


	static public function pageTemplate() {

		$template = '<!doctype html>
<html>
<head>
	<meta charset="utf-8" />
	<title>%s</title>
	%s
	<script type="text/javascript" src="' . RUBLON2FACTOR_PLUGIN_URL . '/assets/js/rublon-wordpress-admin.js?ver=' . self::getCurrentPluginVersion() . '"></script>
</head>
<body class="rublon-busy-body">
%s
</body>
</html>';
		return $template;

	}


	static public function busyPageContentTemplate() {

		$template =	'
	<div class="rublon-busy-wrapper"%s>
		<div class="rublon-busy-container">
			<div class="rublon-busy-text">%s</div>
			<div class="rublon-busy-spinner"></div>
		</div>
	</div>
	%s';
		return $template;

	}


	static public function busyPageStyles($withMarkup = false) {

		$template = ($withMarkup) ? '<style type="text/css" id="rublon-busy-styles">' : '';
		$template .= '
		.rublon-busy-body {
			background-color: #EEEEEE;
			margin: 0;
			padding: 0;
		}
		.rublon-busy-wrapper {
			width: 100%%;
			height: 100%%;
		}
		.rublon-busy-container {
			width: auto;
			padding-bottom: 10px;
			text-align: center;
			background-color: #FFFFFF;
			margin: 50px 20px 0 20px;
			border-radius: 6px;
			-webkit-box-shadow: 0 1px 3px rgba(0, 0, 0, 0.13);
			-moz-box-shadow: 0 1px 3px rgba(0, 0, 0, 0.13);
			-o-box-shadow: 0 1px 3px rgba(0, 0, 0, 0.13);
			box-shadow: 0 1px 3px rgba(0, 0, 0, 0.13);
		}
		.rublon-busy-text {
			text-align: center;
			padding: 10px 0;
			font-family: "Open Sans", sans-serif;
			color: #777;
			font-size: 14px;
			line-height: 20px;
		}
		.rublon-busy-spinner {
			height: 18px;
			width: 18px;
			margin: 2px auto;
			-webkit-animation: rotation .9s infinite linear;
			-moz-animation: rotation .9s infinite linear;
			-o-animation: rotation .9s infinite linear;
			animation: rotation .9s infinite linear;
			border-left: 3px solid rgba(52, 52, 52, .6);
			border-right: 3px solid rgba(52, 52, 52, .15);
			border-bottom: 3px solid rgba(52, 52, 52, .15);
			border-top: 3px solid rgba(52, 52, 52, .6);
			border-radius: 100px;
		}
		@-webkit-keyframes rotation {
			from {-webkit-transform: rotate(0deg);}
			to {-webkit-transform: rotate(359deg);}
		}
		@-moz-keyframes rotation {
			from {-moz-transform: rotate(0deg);}
			to {-moz-transform: rotate(359deg);}
		}
		@-o-keyframes rotation {
			from {-o-transform: rotate(0deg);}
			to {-o-transform: rotate(359deg);}
		}
		@keyframes rotation {
			from {transform: rotate(0deg);}
			to {transform: rotate(359deg);}
		}';
		$template .= ($withMarkup) ? '</style>' : '';
		return $template;

	}


	static private function _reloadParentFrame($text, $withMarkup = false) {

		$script = ($withMarkup) ? '<script type="text/javascript">//<![CDATA[' : '';
		$script .= '
			if (window && window.parent && window.parent.RublonWP) {
				var RublonWP = window.parent.RublonWP;
				RublonWP.reloadPage();
			}
		';
		$script .= ($withMarkup) ? '//]]></script>' : '';
		$text .= '<br />' . __('This will only take a moment.', 'rublon');
		$content = array(
			'text' => $text,
			'script' => $script
		);
		self::_displayBusyPageWithContent($content);

	}


	static public function _redirectParentFrame($url, $text, $withMarkup = false) {

		$script = ($withMarkup) ? '<script type="text/javascript">//<![CDATA[' : '';
		$script .= '
			if (window && window.parent && window.parent.RublonWP) {
				var RublonWP = window.parent.RublonWP;
				RublonWP.goToPage(' . json_encode($url) . ');
			}
		';
		$script .= ($withMarkup) ? '//]]></script>' : '';
		$text .= '<br />' . __('This will only take a moment.', 'rublon');
		$content = array(
			'text' => $text,
			'script' => $script
		);
		self::_displayBusyPageWithContent($content);

	}


	static public function transformMessagesToVersion($messages, $version = '3.8') {

		$messageList = '';
		$errorList = ''; 
		foreach ($messages as $msg) {
			switch ($version) {
				case '3.8':
					if ($msg['type'] == 'updated') {
						$messageList .= '<p class="message">' . $msg['message'] . '</p>';
					} elseif ($msg['type'] == 'error') {
						if (strlen($errorList) === 0) {
							$errorList = '<div id="login_error"><p>';
						} else {
							$errorList .= '<p style="margin-top: 1em">';
						}
						$errorList .= $msg['message'] . '</p>';
					}
					break;
				default:
					$messageList .= '<div class="' . $msg['type'] . ' fade" style="margin: 0 0 16px 8px; padding: 12px;">' . $msg['message'] . '</div>';
			}
		}
		if (!empty($errorList)) {
			$errorList .= '</div>';
		}
		return $errorList . $messageList;

	}


	static private function _generateToken($length = 32) {

		$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
		$result = '';
		for ($i = 0; $i < $length; $i++) {
			$rand = mt_rand(0, 61);
			$result .= $chars[$rand];
		}
		return $result;

	}


	static public function printProfileSectionAdditions($user) {

		$role_protection_type = self::roleProtectionType($user);
		$user_protection_type = self::userProtectionType($user);
		if ($role_protection_type == self::PROTECTION_TYPE_NONE) {
			$emailSelected = '';
			$noneSelected = '';
			if ($user_protection_type == self::PROTECTION_TYPE_EMAIL) {
				$emailChecked = ' checked';
				$lockedVisible = 'visible';
				$unlockedVisible = 'hidden';
			} else {
				$emailChecked = '';
				$lockedVisible = 'hidden';
				$unlockedVisible = 'visible';
			}
?>
<a id="rublon-email2fa"></a>
<h3>
<?php _e('Account Protection', 'rublon'); ?>
</h3>
<table class="form-table">
<tr>
	<th>
		<?php _e('Protection via Email', 'rublon'); ?>
	</th>
	<td>
		<label class="rublon-label rublon-label-userprotectiontype" for="rublon-userprotectiontype-checkbox">
			<input type="hidden" name="<?php echo self::FIELD_USER_PROTECTION_TYPE; ?>" value="none" />
			<input type="checkbox" name="<?php echo self::FIELD_USER_PROTECTION_TYPE; ?>" id="rublon-userprotectiontype-checkbox" value="email"<?php echo $emailChecked; ?> />
			<div class="rublon-lock-container rublon-unlocked-container rublon-userprotectiontype rublon-userprotectiontype-unlocked <?php echo $unlockedVisible; ?>"><img class="rublon-lock rublon-unlocked" src="<?php echo RUBLON2FACTOR_PLUGIN_URL; ?>/assets/images/unlocked.png" /></div>
			<div class="rublon-lock-container rublon-locked-container rublon-userprotectiontype rublon-userprotectiontype-locked <?php echo $lockedVisible; ?>"><img class="rublon-lock rublon-locked" src="<?php echo RUBLON2FACTOR_PLUGIN_URL; ?>/assets/images/locked.png" /></div>
			<?php _e('Enable account protection via email', 'rublon'); ?> 
		</label>
		<br />
		<div class="rublon-description"><span class="description"><?php
			echo __('You will need to confirm every sign in from a new device via email. You will also need to confirm changes to important settings like your password or email address.', 'rublon')
				. '<br /><strong>' . __('Notice:', 'rublon') . '</strong> ' . __('Administrators can always change your settings without your consent.', 'rublon'); ?></span>
		</div>
	</td>	
</tr>
<?php
	$mobile_user_status = self::getMobileUserStatus($user, true);
	if ($mobile_user_status !== self::YES):
?>
<tr>
	<th>
		<?php _e('Protection via Phone', 'rublon'); ?>
	</th>
	<td>
	<div class="rublon-description"><?php
		printf(
			__('This will be automatically enabled once you install the <a href="%s/get" target="_blank">Rublon mobile app</a>. Your phone will be required for identity or action confirmation. This will replace protection via email.', 'rublon'),
			self::rubloncomUrl()
		);
	?></div>
	</td>	
</tr>
<?php endif; ?>
</table>
<script>//<![CDATA[
	if (RublonWP) {
		RublonWP.setUpUserProtectionTypeChangeListener();
	}
//]]></script>
<?php

		}
	}


	static public function printRublonWPLang($withScriptTag = true) {

		$script = '';
		if ($withScriptTag) {
			$script .= '<script>//<![CDATA[';
		}
		$script .= '
 			if (RublonWP) {
 				RublonWP.lang = {
 					"closeButton": "' . __('Close', 'rublon') . '" 
 				};
 			}
 		';
		if ($withScriptTag) {
			$script .= '//]]></script>';
		}
		echo $script;

	}


	static public function profileUrl() {

		return admin_url(self::WP_PROFILE_PAGE);

	}


	static public function optionsUrl() {

		return admin_url(self::WP_OPTIONS_PAGE);

	}


	static public function rublonUrl() {

		return admin_url(self::WP_RUBLON_PAGE);

	}


	static public function rubloncomUrl() {
	
		$lang = self::getBlogLanguage();
		return 'https://rublon.com' . (($lang != 'en') ? ('/' . $lang) : '');
	
	}


	static private function _determineConfirmationReturnUrl() {

		$custom = self::uriGet('custom');
		switch ($custom) {
			case self::FLAG_PROFILE_UPDATE:
				return self::profileUrl();
				break;
			case self::FLAG_ADDSETT_UPDATE:
				return self::rublonUrl();
				break;
			default:
				return self::rublonUrl();
		}

	}


	static public function createDashboardDeviceWidget() {

		$current_user = wp_get_current_user();
		$rublonGUI = new Rublon2FactorGUIWordPress(
			self::getRublon(),
			self::getUserId($current_user),
			self::getUserEmail($current_user)
		);
		echo $rublonGUI->getDashboardDeviceWidget();

	}


	static public function createDashboardACMWidget() {

		$current_user = wp_get_current_user();
		$rublonGUI = new Rublon2FactorGUIWordPress(
			self::getRublon(),
			self::getUserId($current_user),
			self::getUserEmail($current_user)
		);
		echo $rublonGUI->getDashboardACMWidget();

	}


	static public function canShowACM() {

		$additional_settings = self::getSettings('additional');
		return (isset($additional_settings[self::SETTING_CAN_SHOW_ACM]) && $additional_settings[self::SETTING_CAN_SHOW_ACM] == self::YES);

	}


	static public function setACMPermission($status) {

		$additional_settings = self::getSettings('additional');
		$additional_settings[self::SETTING_CAN_SHOW_ACM] = $status;
		self::saveSettings($additional_settings, 'additional');

	}


	/**
	 * Store debug info in a transient option
	 *
	 * @param string $data Text data to store
	 */
	static public function debug($data) {

		$debug = get_transient(self::TRANSIENT_DEBUG);
		if (empty($debug)) {
			$debug = array();
		}
		$debug[] = $data;
		set_transient(
			self::TRANSIENT_DEBUG,
			$debug,
			30 * MINUTE_IN_SECONDS
		);

	}

}