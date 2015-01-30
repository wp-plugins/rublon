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
	const RUBLON_OTHER_SETTINGS_KEY = 'rublon2factor_other_settings';
	const RUBLON_FIRSTINSTALL_SETTINGS_KEY = 'rublon2factor_firstinstall_settigs';

	const RUBLON_SETTINGS_RL_ACTIVE_LISTENER = 'rl-active-listener';

	const RUBLON_META_PROFILE_ID = 'rublon_profile_id';
	const RUBLON_META_AUTH_CHANGED_MSG = 'rublon_auth_changed_msg';
	const RUBLON_META_USER_PROTTYPE = 'rublon_user_protection_type';
	const RUBLON_META_DEVICE_ID = 'rublon_device_id';
	
	const RUBLON_NOTIFY_URL_PATH = '/issue_notifier/wp_notify';
	const RUBLON_ACTION_PREFIX = 'rublon_';

	const PRERENDER_KEY_MOBILE_USERS = 'prer_mobile_users';

	const TRANSIENT_PROFILE_TOKEN_PREFIX = 'rublon_put_';
	const TRANSIENT_PROFILE_FORM_PREFIX = 'rublon_puform_';
	const TRANSIENT_ADDSETT_TOKEN_PREFIX = 'rublon_asut_';
	const TRANSIENT_ADDSETT_FORM_PREFIX = 'rublon_asuform_';
	const TRANSIENT_LOGIN_TOKEN_PREFIX = 'rublon_lt_';
	const TRANSIENT_MOBILE_USER = 'rublon_mobuser_';
	const TRANSIENT_DEBUG = 'rublon_debug';
	const TRANSIENT_FLAG_UPDATE_AUTH_COOKIE = 'rublon_upd_authck_';
	const TRANSIENT_REMOVE_FLAG = '<<REMOVE_FLAG_PLEASE>>';
	
	const PROFILE_UPDATE_TOKEN_NAME = 'rublon_profile_update_token';
	const ADDSETT_UPDATE_TOKEN_NAME = 'rublon_additional_settings_update_token';
	
	const SETTING_CAN_SHOW_ACM = 'can_show_acm';
	const SETTING_FORCED_REGISTRATION = 'forced_registration';
	
	const YES = 'yes';
	const NO = 'no';

	const UPDATE_TOKEN_LIFETIME = 5;
	const UPDATE_FORM_LIFETIME = 15;
	const MOBILE_USER_INFO_LIFETIME = 15;
	const LOGIN_TOKEN_LIFETIME = 16;
	const FLAG_LIFETIME = 5;

	const FLAG_PROFILE_UPDATE = 'wp_profile_update';
	const FLAG_ADDSETT_UPDATE = 'wp_addsett_update';
	
	const FIELD_USER_PROTECTION_TYPE = 'rublon_user_protection_type';

	const PROTECTION_TYPE_NONE = 'none';
	const PROTECTION_TYPE_EMAIL = 'email';
	const PROTECTION_TYPE_MOBILE = 'mobile';

	const PROTTYPE_SETT_PREFIX = 'prottype-for-';

	const WP_PROFILE_PAGE = 'profile.php';
	const WP_OPTIONS_PAGE = 'options.php';
	const WP_RUBLON_PAGE = 'admin.php?page=rublon';
	const WP_PROFILE_EMAIL2FA_SECTION = '#rublon-email2fa';

	const PAGE_ANY = 'any';
	const PAGE_LOGIN = 'login';
	const PAGE_WP_LOADED = 'wp_loaded';

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
	 * Device ID given in callback.
	 * 
	 * @var int
	 */
	static private $deviceId;


	/**
	 * Load i18n files and check for possible plugin update
	 * 
	 */
	static public function init() {

		require_once dirname(__FILE__) . '/classes/class-rublon-garbage-man.php';

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

		$garbage_man = new Rublon_Garbage_Man();
		$garbage_man->collectTrash();
		
		self::initLogoutListener();
		
	}


	/**
	 * Check for any Rublon actions in the URI
	 *
	 */
	static public function checkForActions($page = self::PAGE_ANY) {
	
		$rublonAction = self::uriGet('rublon');
		if (isset($rublonAction) && self::_isActionPermitted($rublonAction, $page)) {
			switch (strtolower($rublonAction)) {
				case 'deactivate':
					$go_to = self::uriGet('rublon_goto');
					if ($go_to == 'profile') {
						$page = 'profile.php';
					} elseif ($go_to == 'plugins') {
						$page = 'plugins.php';
					} else {
						$page = '';
					}
					deactivate_plugins(plugin_basename(RUBLON2FACTOR_PLUGIN_PATH));
					wp_safe_redirect(admin_url($page));
					break;
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
			exit();
		} else {
			// Check for transient-stored profile update form
			self::_checkForStoredPUForm();
			self::_checkForStoredASUForm();
		}
	
	}


	/**
	 * Check if Rublon action permitted on current page
	 * 
	 * @param string $action
	 * @param string $page
	 * @return boolean
	 */
	static private function _isActionPermitted($action, $page) {

		$page_actions = array(
			self::PAGE_ANY => array(
				'register',
				'confirm',
				'deactivate',
			),
			self::PAGE_LOGIN => array('callback'),
			self::PAGE_WP_LOADED => array('init-registration'),
		);
		return (isset($page_actions[$page]) && in_array($action, $page_actions[$page]));

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


		if (self::isAjaxRequest()) {
			return;
		}
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
								$user_email = self::getUserEmail($current_user);
								$obfuscated_email = self::obfuscateEmail($user_email);
								self::setMessage('ROLE_BLOCKED|' . base64_encode($obfuscated_email), 'error', 'LM');
								$return_page = RublonHelper::getReturnPage();
								wp_safe_redirect(wp_login_url($return_page));
								exit();
							}
						} else {
							wp_logout();
							self::setLoginToken($current_user);
							wp_redirect($authURL);
							exit();
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
					$rublonPUToken = self::_generateToken();
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

					// Remove the unnecessary ASUToken field from the post form
					unset($_POST[self::ADDSETT_UPDATE_TOKEN_NAME]);
					
					// Return the updated additional settings array
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
					$rublonASUToken = self::_generateToken();
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
	
		if ($change > 3) {
			$change -= 4;
			$msg = __('Do you confirm changing your protection type?', 'rublon');
		}
		if ($change > 1) {
			$change -= 2;
			if (!empty($msg)) {
				if ($change > 0) {
					$change -= 1;
					$msg = sprintf(__('Do you confirm changing your protection type, your email address to: %s, as well as your password?', 'rublon'), $post['email']);
				} else {
					$msg = sprintf(__('Do you confirm changing your protection type and your email address to: %s?', 'rublon'), $post['email']);
				}
			} else {
				if ($change > 0) {
					$change -= 1;
					$msg = sprintf(__('Do you confirm changing your email address to: %s, as well as your password?', 'rublon'), $post['email']);
				} else {
					$msg = sprintf(__('Do you confirm changing your email address to: %s?', 'rublon'), $post['email']);					
				}
			}
		}
		if ($change > 0) {
			$change -= 1;
			if (!empty($msg)) {
				$msg = sprintf(__('Do you confirm changing your protection type, as well as your password?', 'rublon'), $post['email']);
			} else {
				$msg = __('Do you confirm changing your password?', 'rublon');
			}
		}

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
				exit();
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

		$msg = __('Do you confirm changing minimum protection levels?', 'rublon');

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
				exit();
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


	static public function flag($user = null, $flag = null, $new_value = self::TRANSIENT_REMOVE_FLAG) {

		if ($user instanceof WP_User && !empty($flag)) {
			if ($new_value !== null && $new_value !== self::TRANSIENT_REMOVE_FLAG) {
				set_transient(
					$flag . self::getUserId($user),
					$new_value,
					self::FLAG_LIFETIME * MINUTE_IN_SECONDS
				);				
			} else {
				$stored_value = get_transient($flag . self::getUserId($user));
				if ($stored_value !== false && $new_value === self::TRANSIENT_REMOVE_FLAG) {
					delete_transient($flag . self::getUserId($user));
				}
				return $stored_value;
			}
		}

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


	static public function setMobileUserStatus($user, $mobile_status = self::YES) {

		$user_id = self::getUserId($user);
		set_transient(
			self::TRANSIENT_MOBILE_USER . $user_id,
			$mobile_status,
			self::MOBILE_USER_INFO_LIFETIME * MINUTE_IN_SECONDS
		);

	}


	static public function getMobileUserStatus($user, $refresh = false) {

		$user_id = self::getUserId($user);
		$mobile_user_status = get_transient(self::TRANSIENT_MOBILE_USER . $user_id);
		if ($refresh && $mobile_user_status === false) {
			$rublon_req = new RublonRequests();
			$mobile_user_status = $rublon_req->checkMobileStatus($user);
			self::setMobileUserStatus($user, $mobile_user_status);
		}
		return $mobile_user_status;

	}


	static public function clearMobileUserStatus($user) {

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
			self::spinnerTemplate(),
			$content['script']
		);
		$resultingPage = sprintf($pageTemplate,
			__('Profile update', 'rublon'),
			$styles,
			$pageBody
		);
		echo $resultingPage;
		exit();

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
			if (strpos($key, self::PROTTYPE_SETT_PREFIX) !== false && isset($old_value[$key])) {
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
			$callback = new Rublon2FactorCallbackWordPress(self::getRublon());
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
								if (!empty($post['pass1'])) {
									self::flag(
										$user,
										self::TRANSIENT_FLAG_UPDATE_AUTH_COOKIE,
										self::YES
									);
								}
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
			exit();
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
			$callback = new Rublon2FactorCallbackWordPress(self::getRublon());
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
		$errorMessage = $e->getMessage();
		switch($errorCode) {
			case Rublon2FactorCallbackWordPress::ERROR_MISSING_ACCESS_TOKEN:
				$errorCode = 'MISSING_ACCESS_TOKEN';
				break;
			case Rublon2FactorCallbackWordPress::ERROR_REST_CREDENTIALS:
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
			case Rublon2FactorCallbackWordPress::ERROR_USER_NOT_AUTHORIZED:
				$errorCode = 'USER_NOT_AUTHENTICATED';
				break;
			case Rublon2FactorCallbackWordPress::ERROR_DIFFERENT_USER:
				$errorCode = 'DIFFERENT_USER';
				break;
			case Rublon2FactorCallbackWordPress::ERROR_API_ERROR:
				$errorCode = 'API_ERROR';
				break;
			default:
				$errorCode = 'API_ERROR';
		}
		
		self::setMessage($errorCode, 'error', $prefix);
		
		// prepare message for issue notifier
		$notifierMessage = 'RublonCallback error. ' . 'Error code: ' . '<strong>' . $prefix . '_' . $errorCode . '</strong>.';
		if (!empty($errorMessage)) {
			$notifierMessage .= ' Error message: [urldecode]' . urlencode($errorMessage) . '[/urldecode].';
		}
		if (!empty($additionalErrorMessage)) {
			$notifierMessage .= ' Additional error message: [urldecode]' . urlencode($additionalErrorMessage) . '[/urldecode].';
		}

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
	 * @param Rublon2FactorCallbackWordPress $callback
	 */
	static public function callbackSuccess($user_id, Rublon2FactorCallbackWordPress $callback) {

		$user = get_user_by('id', $user_id);
		if ($user && $user instanceof WP_User) {
			$login_token = null;
			$first_factor_cleared = self::_isFirstFactorCleared($user, $login_token);
			if ($first_factor_cleared) {
				self::_clearLoginToken($login_token['token_id']);
				$usingEmail2FA = $callback->getConsumerParam(RublonAPIClient::FIELD_USING_EMAIL2FA);
				if (!$usingEmail2FA) {
					self::setMobileUserStatus($user);
				}
				$acm_status = $callback->getConsumerParam(RublonAPIClient::FIELD_ACCESS_CONTROL_MANAGER_ALLOWED);
				if ($acm_status === true) {
					self::setACMPermission(self::YES);
				} else {
					self::setACMPermission(self::NO);
				}
				$deviceId = $callback->getConsumerParam(RublonAPICredentials::FIELD_DEVICE_ID);
				$remember = $callback->getConsumerParam('remember');
				wp_logout();
				self::$deviceId = $deviceId;
				add_filter('auth_cookie', array(__CLASS__, 'associateSessionWithDevice'), 10, 5);
				RublonCookies::setLoggedInCookie($user, $remember);
				RublonCookies::setAuthCookie($user, $remember);
				do_action('wp_login', $user->user_login, $user);
			} else {
				self::setMessage('FIRST_FACTOR_NOT_CLEARED', 'error', 'RC');
				wp_logout();
			}
		} else {
			wp_logout();
		}
		self::_returnToPage();

	}
	
	/**
	 * On creating auth cookie add a user meta to associate device ID with user's session.
	 * The 'auth_cookie' filter.
	 *
	 * @see wp_generate_auth_cookie()
	 * @param string $cookie
	 * @param int $user_id
	 * @param int $expiration
	 * @param string $scheme
	 * @param string $token
	 * @return string
	 */
	static public function associateSessionWithDevice($cookie, $user_id, $expiration, $scheme, $token) {
		if (!empty(self::$deviceId) AND !empty($token)) {
			add_user_meta($user_id, RublonHelper::RUBLON_META_DEVICE_ID .'_'. self::$deviceId, hash( 'sha256', $token ), $unque = false);
		}
		return $cookie;
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
		if (in_array(self::PROTECTION_TYPE_EMAIL, $protection_type)
			&& !in_array(self::PROTECTION_TYPE_MOBILE, $protection_type)) {
			$authParams[RublonAuthParams::FIELD_CAN_USE_EMAIL2FA] = true;
		}
		if (!empty($here)) {
			$authParams['customURIParam'] = '[[CUSTOM]]' . $here;
		}
		try {
			$authUrl = $rublon->auth(
				self::getLoginURL('callback'),
				self::getUserId($user),
				self::getUserEmail($user),
				$authParams
			);
			return $authUrl;
		} catch (RublonException $e) {
			$error_data = array(
				'msg' => 'Authentication error',
				'errorCode' => $e->getCode(),
				'errorMessage' => $e->getMessage(),
			);
			$previous_exception = $e->getPrevious();
			if ($previous_exception != null) {
				$error_data['previousCode'] = $previous_exception->getCode();
				$error_data['previousMessage'] = $previous_exception->getMessage();
			}
			self::notify($error_data);
			return '';
		}

	}


	static private function _isFirstFactorCleared($user, &$login_token_data) {

		$first_factor_cleared = false;
		if ($user instanceof WP_User) {
			$login_token = self::_getLoginToken();
			if (!empty($login_token)) {
				$first_factor_cleared = (is_numeric($login_token['user_id']) && $login_token['user_id'] == self::getUserId($user));
				if ($first_factor_cleared) {
					$login_token_data = $login_token;
				}
			}
		}
		return $first_factor_cleared;

	}


	static private function _getLoginToken() {

		$login_token_id = RublonCookies::getLoginTokenIdFromCookie();
		if (!empty($login_token_id)) {
			return get_transient(self::TRANSIENT_LOGIN_TOKEN_PREFIX . $login_token_id);
		}

	}


	static public function setLoginToken($user) {

		$login_token_id = self::_generateLoginTokenId();
		$login_token = array(
			'user_id' => self::getUserId($user),
			'token_id' => $login_token_id,
		);
		set_transient(
			self::TRANSIENT_LOGIN_TOKEN_PREFIX . $login_token_id,
			$login_token,
			self::LOGIN_TOKEN_LIFETIME * MINUTE_IN_SECONDS
		);
		RublonCookies::storeLoginTokenIdInCookie($login_token_id);

	}


	static private function _clearLoginToken($login_token_id) {

		delete_transient(self::TRANSIENT_LOGIN_TOKEN_PREFIX . $login_token_id);

	}


	static private function _generateLoginTokenId() {

		$login_token_id = false;
		while (!$login_token_id) {
			$new_token_id = self::_generateToken();
			$check_token = get_transient(self::TRANSIENT_LOGIN_TOKEN_PREFIX . $new_token_id);
			if (!$check_token) {
				$login_token_id = $new_token_id;
			}
		}
		return $login_token_id;

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
			case 'other':
				$key = self::RUBLON_OTHER_SETTINGS_KEY;
				break;
			case 'firstinstall':
				$key = self::RUBLON_FIRSTINSTALL_SETTINGS_KEY;
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
			case 'other':
				$key = self::RUBLON_OTHER_SETTINGS_KEY;
				break;
			case 'firstinstall':
				$key = self::RUBLON_FIRSTINSTALL_SETTINGS_KEY;
				break;
			default:
				$key = self::RUBLON_SETTINGS_KEY;
		}
		update_option($key, $settings);

		do_action('rublon_save_settings', $settings, $group);

	}


	static public function shouldPluginAttemptRegistration() {

		$settings = self::getSettings();
		$firstinstall_settings = self::getSettings('firstinstall');
		$forced_registration = false;
		if (!empty($firstinstall_settings[self::SETTING_FORCED_REGISTRATION]) && is_admin() && is_user_logged_in()) {
			$forced_registration = ($firstinstall_settings[self::SETTING_FORCED_REGISTRATION] == self::YES);
			$firstinstall_settings[self::SETTING_FORCED_REGISTRATION] = self::NO;
			self::saveSettings($firstinstall_settings, 'firstinstall');
		}
		return !self::isSiteRegistered()
			&& (!empty($settings['attempt-registration']) || $forced_registration)
			&& (version_compare(phpversion(), self::PHP_VERSION_REQUIRED, 'ge'))
			&& function_exists('curl_init');

	}
	
	
	static public function enqueueRegistration($deny = false) {

		$settings = self::getSettings();
		$settings['attempt-registration'] = $deny;
		self::saveSettings($settings);

	}
	
	static public function registerSite() {

		$rublonGUI = Rublon2FactorGUIWordPress::getInstance();
		wp_redirect($rublonGUI->getActivationURL());
		exit();

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
				if (($delimiter = strpos($msgCode, '|')) !== false) {
					$additional_data = substr($msgCode, $delimiter + 1);
					$msgCode = substr($msgCode, 0, $delimiter);
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
						$obfs_email = base64_decode($additional_data);
						$no_code = true;
						$errorMessage = sprintf(
							__('Due to security purposes, your administrator requires you to install the <a href="%s" target="_blank">Rublon mobile app</a>. Enter your WordPress account\'s email address during setup: %s.', 'rublon'),
							self::rubloncomUrl(true, '/get'),
							'<strong>' . $obfs_email . '</strong>')
						. '<br /><br />'
						. sprintf(
							__('If you already have the Rublon mobile app, please go to <a href="%s" target="_blank">www.rublon.com</a> and log in to your User Panel in order to add the above email address as an additional one.', 'rublon'),
							self::rubloncomUrl())
						. '<br /><br />'
						. '<div class="rublon-app-button"><a href="' . self::appStoreUrl('android')  . '" target="_blank"><img src="https://rublon.com/img/play_store_small.png" /></a></div>'
						. '<div class="rublon-app-button"><a href="' . self::appStoreUrl('ios')  . '" target="_blank"><img src="https://rublon.com/img/app_store_small.png" /></a></div>'
						. '<div class="rublon-clear"></div>'
						. '<div class="rublon-app-button rublon-width-full"><a href="' . self::appStoreUrl('windows phone')  . '" target="_blank"><img src="https://rublon.com/img/wphone_store_small.png" /></a></div>'
						. '<div class="rublon-clear"></div>';
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
						. ' ' . sprintf(__('Learn more at <a href="%s" target="_blank">wordpress.rublon.com</a>.', 'rublon'), RublonHelper::wordpressRublonComURL());
						break;
					case 'RC_FIRST_FACTOR_NOT_CLEARED':
						$no_code = true;
						$errorMessage = __('<strong>ERROR:</strong> Unauthorized access.', 'rublon');
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
						$updatedMessage = __('Thank you! All accounts are now protected by Rublon.', 'rublon');
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
			exit();
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
			exit();
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
	 * @param array $error_info Error info
	 */
	static function notify($error_info) {

		$error_info['blog-info'] = get_bloginfo();
		$plugin_meta = self::preparePluginMeta();
		if (!empty($plugin_meta['meta'])) {
			$error_info['plugin-info'] = $plugin_meta['meta'];
		}
		$error_info['php-info'] = self::_info();				
		
		$ch = curl_init(self::RUBLON_API_DOMAIN . self::RUBLON_NOTIFY_URL_PATH);
		$headers = array(
			"Content-Type: application/json; charset=utf-8",
			"Accept: application/json, text/javascript, */*; q=0.01"
		);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		
		curl_setopt($ch, CURLOPT_POST, true);							
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($error_info));
					
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Rublon for WordPress');

		// Execute request
		curl_exec($ch);		
		if (curl_error($ch)) {
			// Nothing for now
		}		
		curl_close($ch);

	}


	/**
	 * Prepare server's PHP info for error reporting
	 * 
	 * @return array
	 */
	static private function _info() {

		$php_info = array(
			'php-extensions' => get_loaded_extensions(),
			'operating-system' => php_uname(),
			'php-version' => phpversion(),
			'stream-wrappers' => stream_get_wrappers(),
			'php-config-options' => ini_get_all(),
		);
	    return $php_info;

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
		
		$admin_role = self::prepareRoleId('administrator');
		// Enable Email2FA for all roles by default
		if (!isset($additional_settings[$admin_role])) {
			$roles = self::getUserRoles();
			foreach ($roles as $role) {
				$role_id = self::prepareRoleId($role);
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
			$all_users = $wpdb->get_results("SELECT ID, rublon_profile_id FROM $wpdb->users", ARRAY_N);
			foreach ($all_users as $user) {
				if (!empty($user[1])) {
					add_user_meta($user[0], self::RUBLON_META_PROFILE_ID, $user[1], true);
				}
			}
			$db_error = $wpdb->query('ALTER TABLE ' . $wpdb->users . ' DROP COLUMN `rublon_profile_id`') === false;
			if ($db_error) {
				deactivate_plugins(plugin_basename(RUBLON2FACTOR_PLUGIN_PATH), true);
				_e('Plugin requires database modification but you do not have permission to do it.', 'rublon');
				exit();
			}
		}
	
	}


	/**
	 * Prepare plugin meta data to be reported
	 * 
	 * @return array
	 */
	static public function preparePluginMeta() {

		global $wpdb;
		
		$roles = self::getUserRoles();
		$role_count = array();

		foreach ($roles as $role_name => $role_translation) {
			$role_count[$role_name] = 0;
		}

		foreach ($role_count as $role_name => $value) {
			$count = intval($wpdb->get_var("SELECT COUNT(*) FROM $wpdb->usermeta WHERE meta_key = 'wp_capabilities' AND meta_value LIKE '%:\"$role_name\"%';"));
			$role_count[$role_name] = $count;
			
		}

		$plugin_meta = array(
			'wordpress-version' => get_bloginfo('version'),
			'plugin-version' => self::getCurrentPluginVersion(),
		);
		foreach ($role_count as $role_name => $count) {
			$plugin_meta['registered-' . $role_name . 's'] = $count;
		}

		$meta_header = array(
				'meta' => $plugin_meta,
		);
		return $meta_header;

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
		if (substr($url, -1) == '/') {
			$admin_url = trailingslashit($admin_url);
		}
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

		if (!preg_match('/http(s)?:\/\//', $url)) {
			$url = 'http://' . $url;
		}
		if (self::isAdminURL($url)) {
			if (defined('FORCE_SSL_ADMIN')) {
				if (FORCE_SSL_ADMIN) {
					$url = str_replace('http://', 'https://', $url);
				}
			}
		}
		if (is_ssl()) {
			$url = str_replace('http://', 'https://', $url);
		}
		return $url;

	}


	/**
	 * Create a Rublon action URL based on the site URL
	 * 
	 * @param string $action
	 */
	static public function getActionURL($action) {

		return add_query_arg('rublon', $action, trailingslashit(site_url()));

	}


	static public function getLoginURL($action) {

		return add_query_arg('rublon', $action, wp_login_url());

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
	static public function prepareRoleId($role_name) {
	
		return self::PROTTYPE_SETT_PREFIX . strtolower(preg_replace('/[\W]/', '-', before_last_bar($role_name)));
	
	}


	/**
	 * Check if any of the user's roles requires 2FA 
	 * 
	 * @param WP_User $user
	 * @return boolean
	 */
	static public function roleProtectionType($user) {

		$settings = self::getSettings('additional');
		$role_protection_type = self::PROTECTION_TYPE_NONE;
		foreach ($user->roles as $role) {
			$role_id = self::prepareRoleId($role);
			if (!empty($settings[$role_id]) && $settings[$role_id] == self::PROTECTION_TYPE_EMAIL && $role_protection_type == self::PROTECTION_TYPE_NONE) {
				$role_protection_type = self::PROTECTION_TYPE_EMAIL;
			} elseif (!empty($settings[$role_id]) && $settings[$role_id] == self::PROTECTION_TYPE_MOBILE) {
				$role_protection_type = self::PROTECTION_TYPE_MOBILE;
			}
		}
		return $role_protection_type;

	}


	/**
	 * Get a user's protection type
	 * 
	 * @param WP_User $user
	 * @return string
	 */
	static public function userProtectionType($user) {

		$user_protection_type = get_user_meta(self::getUserId($user), self::RUBLON_META_USER_PROTTYPE, true);
		if ($user_protection_type) {
			return $user_protection_type;
		} else {
			return self::PROTECTION_TYPE_NONE;
		}

	}


	/**
	 * Set a user's individual protection type
	 * 
	 * @param WP_User $user
	 * @param string $type
	 */
	static private function _setUserProtectionType($user, $type) {

		update_user_meta(
			self::getUserId($user),
			self::RUBLON_META_USER_PROTTYPE,
			$type
		);

	}


	/**
	 * Check if a user is protected by Rublon in any way
	 * 
	 * The method will return the user's highest protection level,
	 * so if the user is protected individually by email
	 * and the user's role requires the mobile app, the protection
	 * level will always be mobile.
	 * 
	 * @param WP_User $user
	 * @param string $protection_type If set to "yes", the protection level will be returned in this variable
	 * @return boolean
	 */
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
	 * Redirect the browser after authentication
	 * 
	 * @param string $return_url
	 */
	static private function _returnToPage($return_url = null) {

		if (!$return_url) {
			$return_url = self::getReturnPage();			
		}
		
		// Apply return URL filter
		$return_url = apply_filters('rublon_return_url', $return_url);
		
		if (!empty($return_url)) {
			if (is_ssl()) {
				$return_url = str_replace('http://', 'https://', $return_url);
			}
		}
		$return_url = (!empty($return_url) && strpos($return_url, site_url()) !== false) ? $return_url : admin_url();
		$return_url = self::normalizeURL($return_url);

		wp_safe_redirect($return_url);
		exit();

	}


	/**
	 * Create a "Rublon busy" page template
	 * 
	 * @return string
	 */
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


	/**
	 * Create a "Rublon busy" page content template
	 * 
	 * @return string
	 */
	static public function busyPageContentTemplate() {

		$template =	'
	<div class="rublon-busy-wrapper"%s>
		<div class="rublon-busy-container">
			<div class="rublon-busy-text">%s</div>
			%s
		</div>
	</div>
	%s';
		return $template;

	}

	static public function spinnerTemplate($additionalClass = '') {

		return '<div class="rublon-busy-spinner' . $additionalClass . '"></div>';		

	}


	/**
	 * Create a "Rublon busy" stylesheet template
	 * 
	 * @param string $withMarkup Include <style></style> tags
	 * @return string
	 */
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
			padding: 10px;
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
		.rublon-reg-spinner {
			margin-bottom: 16px;
		}
		.rublon-reg-button {
			padding: 7px;
			border-radius: 2px;
			background-color: #EEEEEE;
			border: none;
			color: #777;
			-webkit-box-shadow: 0 1px 1px rgba(0, 0, 0, 0.5);
			box-shadow: 0 1px 1px rgba(0, 0, 0, 0.5);
			-moz-box-shadow: 0 1px 1px rgba(0, 0, 0, 0.5);
			-o-box-shadow: 0 1px 1px rgba(0, 0, 0, 0.5);
			font-family: \'Open Sans\', sans-serif;
			font-size: 1em;
			text-decoration: none;
			cursor: pointer;
		}
		.rublon-reg-button-container {
			padding-bottom: 10px;
		}		
		.rublon-reg-smallprint {
			font-family: \'Open Sans\', sans-serif;
			font-size: 0.8em;
			color: #777;
			margin-top: 10px;
		}
		.rublon-reg-button.inactive {
			color: #CCCCCC;
			cursor: default;
		}
		.rublon-first-button {
			margin-right: 10px;
		}
		.rublon-main-button {
			font-weight: bold;
		}
		.rublon-reg-fieldset {
			border: none;
			padding: 5px;
			padding-top: 0;
			margin: 0 0 15px 0;
		}
		.rublon-reg-fieldset label {
			font-family: \'Open Sans\', sans-serif;
			color: #777;
			font-size: 14px;
			cursor: pointer;
		}
		.rublon-reg-checkbox {
			vertical-align: middle;
			margin: -2px 5px 0 0;
			cursor: pointer;
		}
		form#RublonConsumerRegistration {
			margin: 0 0 0.5em 0;
		}
		.hidden {
			display: none;
		}
		.visible {
			display: block;
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


	/**
	 * Reload parent frame with a text message displayed
	 * 
	 * @param string $text
	 * @param true $withMarkup Include <script></script> tags
	 */
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


	/**
	 * Redirect the parent frame to a URL with a text message displayed
	 * @param string $url
	 * @param string $text
	 * @param boolean $withMarkup  Include <script></script> tags
	 */
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


	/**
	 * Transform old messages to the new WP 3.8+ style
	 * 
	 * @param array $messages
	 * @param string $version
	 * @return string
	 */
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


	/**
	 * Generater a random token string
	 * 
	 * @param int $length
	 * @return string
	 */
	static private function _generateToken($length = 32) {

		$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
		$result = '';
		for ($i = 0; $i < $length; $i++) {
			$rand = mt_rand(0, 61);
			$result .= $chars[$rand];
		}
		return $result;

	}


	/**
	 * Print any additional Rublon sections on user's profile page
	 * 
	 * @param WP_User $user
	 */
	static public function printProfileSectionAdditions($user) {

		$role_protection_type = self::roleProtectionType($user);
		$user_protection_type = self::userProtectionType($user);
		$mobile_user_status = self::getMobileUserStatus($user, true);

?>
<a id="rublon-email2fa"></a>
<h3 class="rublon-header">
<?php _e('Rublon Account Protection ', 'rublon'); ?>
</h3>
<table class="form-table">
<tr>
	<th>
		<?php _e('Protection via Email', 'rublon'); ?>
	</th>
	<td>
<?php
		if ($role_protection_type == self::PROTECTION_TYPE_MOBILE || $mobile_user_status == self::YES): 
?>
		<div class="rublon-description">
			<?php
				echo __('Protection via Email is not necessary for you.', 'rublon')
					. ' ' . __('You are already protected via Mobile App.', 'rublon');
			?>
		</div>
<?php
		elseif ($role_protection_type == self::PROTECTION_TYPE_EMAIL):
?>
		<div class="rublon-description">
			<?php _e('You are protected via Email.', 'rublon'); ?>
		</div>
<?php
		else:
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
		endif;
?>
<tr>
	<th>
		<?php _e('Protection via Mobile App', 'rublon'); ?>
	</th>
	<td>
		<div class="rublon-description">
<?php
		if ($role_protection_type == self::PROTECTION_TYPE_MOBILE || $mobile_user_status == self::YES) {
			_e('You are already protected via Mobile App.', 'rublon');
		} else {
			printf(
				__('This will be automatically enabled once you install the <a href="%s/get" target="_blank">Rublon mobile app</a>. Your phone will be required for identity or action confirmation. This feature will replace protection via email.', 'rublon'),
				self::rubloncomUrl()
			);
		}
?>
		</div>
	</td>	
</tr>
<?php //endif; ?>
</table>
<script>//<![CDATA[
		if (RublonWP) {
			RublonWP.setUpUserProtectionTypeChangeListener();
		}
//]]></script>
<?php

	}


	/**
	 * Print a JS that sets localized messages for Rublon JS scripts
	 * 
	 * @param string $withScriptTag Include <script></script> tags
	 */
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


	/**
	 * Create a URL for WP user profile page
	 * 
	 * @return string
	 */
	static public function profileUrl() {

		return admin_url(self::WP_PROFILE_PAGE);

	}


	/**
	 * Create a URL for WP options page
	 * 
	 * @return string
	 */
	static public function optionsUrl() {

		return admin_url(self::WP_OPTIONS_PAGE);

	}


	/**
	 * Create a URL for Rublon settings page
	 * 
	 * @return string
	 */
	static public function rublonUrl() {

		return admin_url(self::WP_RUBLON_PAGE);

	}


	/**
	 * Create a URL for rublon.com
	 * 
	 * @param boolean $withLang (optional) Include site language
	 * @param string $path (optional) Additional path on rublon.com
	 * @return string
	 */
	static public function rubloncomUrl($withLang = true, $path = null) {
	
		$url = 'https://rublon.com';
		if ($withLang) {
			$lang = self::getBlogLanguage();
			$url .= ($lang != 'en') ? ('/' . $lang) : '';
		}
		if ($path) {
			$url .= $path;
		}
		return $url;

	}


	/**
	 * Create a URL for wordpress.rublon.com
	 *
	 * @param string $path (optional) Additional path on wordpress.rublon.com
	 * @return string
	 */
	static public function wordpressRublonComURL($path = null) {
	
		$url = 'http://wordpress.rublon.com';
		if ($path) {
			$url .= $path;
		}
		return $url;
	
	}


	/**
	 * Return Rublon mobile app store URL
	 * 
	 * @param string $type
	 * @return string
	 */
	static public function appStoreUrl($type) {

		$lang = self::getBlogLanguage();
		switch ($type) {
			case 'android':
				$url = 'http://play.google.com/store/apps/details?id=com.rublon.android';
				break;
			case 'ios':
				$url = 'http://itunes.apple.com/%s/app/rublon/id501336019';
				$region = $lang;
				if ($region == 'en') {
					$region = 'us';
				}
				$url = sprintf($url, $lang);
				break;
			case 'windows phone':
				$region = $lang;
				if ($region == 'en') {
					$region = 'us';
				} 
				$url = 'http://www.windowsphone.com/%s-%s/store/app/rublon/809d960f-a3e8-412d-bc63-6cf7f2167d42';
				$url = sprintf($url, $lang, $region);
				break;
		}
		return $url;

	}


	/**
	 * Determine where the browser should be redirected after operation confirmation
	 */
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


	/**
	 * Create a widget on the WP Dashboard to manage Trusted Devices
	 *
	 */
	static public function createDashboardDeviceWidget() {

		$rublonGUI = Rublon2FactorGUIWordPress::getInstance();
		echo $rublonGUI->getTDMWidget();

	}


	/**
	 * Check whether the site can use ACM
	 * 
	 * @return boolean
	 */
	static public function canShowACM() {

		$other_settings = self::getSettings('other');
		return (isset($other_settings[self::SETTING_CAN_SHOW_ACM]) && $other_settings[self::SETTING_CAN_SHOW_ACM] == self::YES);

	}


	/**
	 * Set the site's permission to use ACM (Access Control Manager)
	 * 
	 * @param string $status Permission status
	 */
	static public function setACMPermission($status) {

		$other_settings = self::getSettings('other');
		$other_settings[self::SETTING_CAN_SHOW_ACM] = $status;
		self::saveSettings($other_settings, 'other');

	}


	static public function isAjaxRequest() {

		return defined('DOING_AJAX') && DOING_AJAX;

	}


	/**
	 * Prepare an array of current site's user roles
	 * 
	 * return array
	 */
	static public function getUserRoles() {

		global $wp_roles;
		if (!isset($wp_roles)) {
			$wp_roles = new WP_Roles();
		}
		return $wp_roles->get_names();


	}


	/**
	 * Change the email address to firstLetter***lastLetter@example.com format
	 *
	 * @param string $email
	 * @return string
	 */
	static public function obfuscateEmail($email) {

		return preg_replace('/(.)(?:[^@]+)(.@.+)/', '$1***$2', $email);

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


	static public function initLogoutListener() {

		if (is_user_logged_in()) {
			// Create GUI instance to automatically add the logout listener
			$gui = Rublon2FactorGUIWordPress::getInstance();
	
			// Create AJAX endpoint to check if user is logged-in
			$callback = array(__CLASS__, 'ajaxLogout');
			add_action('wp_ajax_rublon_logout', $callback);
			add_action('wp_ajax_nopriv_rublon_logout', $callback);
		}

	}


	static public function ajaxLogout() {
		wp_logout();
		exit();
	}


	static public function memUsage($var) {
		$start_memory = memory_get_usage();
		$tmp = unserialize(serialize($var));
		return memory_get_usage() - $start_memory;

	}


}