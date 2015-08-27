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
	const RUBLON_EMAIL_SALES = 'sales@rublon.com';
	
	const RUBLON_SETTINGS_KEY = 'rublon2factor_settings';
	const RUBLON_REGISTRATION_SETTINGS_KEY = 'rublon2factor_registration_settings';
	const RUBLON_ADDITIONAL_SETTINGS_KEY = 'rublon2factor_additional_settings';
	const RUBLON_CONFIRMATIONS_SETTINGS_KEY = 'rublon2factor_confirmations_settings';
	const RUBLON_OTHER_SETTINGS_KEY = 'rublon2factor_other_settings';
	const RUBLON_FIRSTINSTALL_SETTINGS_KEY = 'rublon2factor_firstinstall_settings';
	const RUBLON_TRANSIENTS_SETTINGS_KEY = 'rublon2factor_transient_settings';

	const RUBLON_SETTINGS_RL_ACTIVE_LISTENER = 'rl-active-listener';
	const RUBLON_SETTINGS_ACCESS_CONTROL = 'access-control';
	const RUBLON_SETTINGS_ADAM = 'enable-adam';

	const RUBLON_META_PROFILE_ID = 'rublon_profile_id';
	const RUBLON_META_AUTH_CHANGED_MSG = 'rublon_auth_changed_msg';
	const RUBLON_META_USER_PROTTYPE = 'rublon_user_protection_type';
	const RUBLON_META_DEVICE_ID = 'rublon_device_id';
	
	const RUBLON_NOTIFY_URL_PATH = '/issue_notifier/wp_notify';
	const RUBLON_NOTIFY_TYPE_ERROR = 'error';
	const RUBLON_NOTIFY_TYPE_STATS = 'statistics';

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
	const SETTING_PROJECT_OWNER_EMAIL = 'project_owner_email';
	
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
	const PROTECTION_TYPE_MOBILE_EVERYTIME = 'mobileEverytime';

	const PROTTYPE_SETT_PREFIX = 'prottype-for-';

	const WP_PROFILE_PAGE = 'profile.php';
	const WP_OPTIONS_PAGE = 'options.php';
	const WP_RUBLON_PAGE = 'admin.php?page=rublon';
	const WP_RUBLON_CONFIRMATIONS_PAGE = 'admin.php?page=rublon_confirmations';
	const WP_PROFILE_EMAIL2FA_SECTION = '#rublon-email2fa';
	
	const LOGOUT_LISTENER_HEARTBEAT_INTERVAL = 5;

	const PAGE_ANY = 'any';
	const PAGE_LOGIN = 'login';
	const PAGE_WP_LOADED = 'wp_loaded';

	const PHP_VERSION_REQUIRED = '5.2.4';
	const INSTALL_OBSTACLE_PHP_VERSION_TOO_LOW = 'php_version_too_low';
	const INSTALL_OBSTACLE_CURL_NOT_AVAILABLE = 'curl_not_available';
	const INSTALL_OBSTACLE_HASH_NOT_AVAILABLE = 'hash_not_available';
	
	const CACHE_PURGE_ACTION = 'cache-purge';
	const CACHE_PURGE_NONCE = 'rublon-cache-purge';
	const CACHE_PURGE_CAPABILITY = 'manage_options';	
	
	
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
	 * Messages stored without cookie involvement
	 * 
	 * @var array
	 */
	static public $messages = array();


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
	static public function plugins_loaded() {

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
		
		add_action('init', array(__CLASS__, 'init'));
		
	}
	
	
	static public function init() {
		
		self::initLogoutListener();

		// Custom admin actions
		if (is_admin() AND !empty($_GET['page']) AND $_GET['page'] == 'rublon') {
			if (!empty($_GET['action'])) switch ($_GET['action']) {
				case self::CACHE_PURGE_ACTION:
					if (current_user_can(self::CACHE_PURGE_CAPABILITY)
							AND !empty($_GET['nonce']) AND wp_verify_nonce($_GET['nonce'], self::CACHE_PURGE_NONCE)) {
						delete_transient('rublon_features');
	}
					wp_redirect(admin_url('admin.php?page=rublon'));
					break;
			}
		}

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
				case 'newsletter_subscribe':
					$post = $_POST;
					$wp_nonce = self::uriGet( '_wpnonce' );
					if ( !empty( $wp_nonce ) && wp_verify_nonce( $wp_nonce, 'newsletter_subscribe' ) && !empty( $post['email'] ) ) {
						$rublon_req = new RublonRequests();
						$result = $rublon_req->subscribeToNewsletter( $post['email'] );
						if ( $result === true ) {
							self::setMessage( RublonRequests::SUCCESS_NL_SUBSCRIBED_SUCCESSFULLY, 'updated', 'NL' );
						} else {
							self::setMessage( $result, 'error', 'NL' );
						}
					} else {
						self::setMessage( RublonRequests::ERROR_INVALID_NONCE, 'error', 'NL' );
					}
					wp_safe_redirect( admin_url( self::WP_RUBLON_PAGE ) );
					break;
			}
			exit();
		} else {
			// Check for transient-stored profile update form
			self::_checkForStoredPUForm();
			self::_checkForStoredASUForm();
			
			self::_saveAdamSaidFirstSentence();						
		}
	
	}
    
	/**
	 * Store in the cookie information about Adam seid first sentence
	 */
	static private function _saveAdamSaidFirstSentence() {
	    $has_adam_said_first_sentence = RublonCookies::getAdamsCookie();
	    if (empty($has_adam_said_first_sentence)) {
	        RublonCookies::storeAdamsCookie();
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
				'confirm',
				'deactivate',
				'newsletter_subscribe',
			),
			self::PAGE_LOGIN => array('callback'),
			self::PAGE_WP_LOADED => array('register'),
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
		
		// Skip checking permission since the edition is personal
		if (self::isPersonalEdition()) {			    	    
		    return;
		}
		
		if (self::isSiteRegistered()) {
			$current_user = wp_get_current_user();
			if (!empty($current_user)) {
				$current_user_id = self::getUserId($current_user);
				if ($current_user_id && is_user_logged_in()) {
					if (!self::isUserAuthenticated($current_user)) {
						$authURL = self::authenticateWithRublon($current_user);
						if (empty($authURL)) {
							if (RublonRolesProtection::isGrater(RublonHelper::getUserProtectionType(), RublonHelper::PROTECTION_TYPE_MOBILE, true)) {
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
	 * @deprecated
	 * @TODO remove function
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
						self::setUserProtectionType(
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
				if ($change > 0 AND RublonFeature::checkFeature(RublonAPIGetAvailableFeatures::FEATURE_OPERATION_CONFIRMATION)) {
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

	/**
	 * 
	 * @param unknown $post
	 * @param unknown $new_value
	 * @param unknown $old_value
	 * @return unknown
	 */
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
				$forceMobileApp = false;
				$change = self::_aSUChangeRequiresConfirmation($new_value, $old_value, $forceMobileApp);
				if ($change > 0 AND RublonFeature::checkFeature(RublonAPIGetAvailableFeatures::FEATURE_OPERATION_CONFIRMATION)) {
					self::_storeForm(
						$current_user_id,
						$post,
						self::TRANSIENT_ADDSETT_FORM_PREFIX,
						self::UPDATE_FORM_LIFETIME
					);
					// Confirm transaction with Rublon
					self::_confirmASUWithRublon($post, $change, $forceMobileApp);
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

	
	/**
	 * User profile change confirmation.
	 * 
	 * @param unknown $post
	 * @param unknown $change
	 */
	static private function _confirmPUWithRublon($post, $change) {
	
		$current_user = wp_get_current_user();
		$user_id = self::getUserId($current_user);
		$user_email = self::getUserEmail($current_user);
	
		$rublon = self::getRublon();
	
		$authParams = array();
		$roleProtectionType = self::roleProtectionType($current_user);
		$userProtectionType = self::userProtectionType($current_user);
		$protection_type = array($roleProtectionType, $userProtectionType);
		if (in_array(self::PROTECTION_TYPE_EMAIL, $protection_type)) {
			$authParams[RublonAuthParams::FIELD_CAN_USE_EMAIL2FA] = true;
		}
		if (RublonHelper::getUserProtectionType($current_user) == RublonHelper::PROTECTION_TYPE_MOBILE_EVERYTIME AND RublonFeature::checkFeature(RublonAPIGetAvailableFeatures::FEATURE_IGNORE_TRUSTED_DEVICE)) {
			$authParams[RublonAuthParams::FIELD_IGNORE_TRUSTED_DEVICE] = true;
		}
		if ($timeBuffer = RublonFeature::getBufferedConfirmationTime()) {
			$authParams[RublonAuthParams::FIELD_CONFIRM_TIME_BUFFER] = $timeBuffer * 60;
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
		} catch (ForbiddenMethod_RublonAPIException $e) {
			self::_abortConfirmation(self::rublonUrl(), 'FORBIDDEN_METHOD');
		} catch (RublonException $e) {
			self::_handleCallbackException($e);
			self::_abortConfirmation(self::profileUrl());
		}
	}


	static private function _confirmASUWithRublon($post, $change, $forceMobileApp = false) {
		$current_user = wp_get_current_user();
		$user_id = self::getUserId($current_user);
		$user_email = self::getUserEmail($current_user);

		$rublon = self::getRublon();
		
		$authParams = array();
		
		if ($forceMobileApp) {
			$roleProtectionType = $userProtectionType = self::PROTECTION_TYPE_MOBILE;
			$userProtectionType = null;
		} else {
		$roleProtectionType = self::roleProtectionType($current_user);
		$userProtectionType = self::userProtectionType($current_user);
		}
		$protection_type = array($roleProtectionType, $userProtectionType);
		if (in_array(self::PROTECTION_TYPE_EMAIL, $protection_type)) {
			$authParams[RublonAuthParams::FIELD_CAN_USE_EMAIL2FA] = true;
		}
		if (RublonHelper::getUserProtectionType($current_user) == RublonHelper::PROTECTION_TYPE_MOBILE_EVERYTIME AND RublonFeature::checkFeature(RublonAPIGetAvailableFeatures::FEATURE_IGNORE_TRUSTED_DEVICE)) {
			$authParams[RublonAuthParams::FIELD_IGNORE_TRUSTED_DEVICE] = true;
		}
		if ($timeBuffer = RublonFeature::getBufferedConfirmationTime()) {
			$authParams[RublonAuthParams::FIELD_CONFIRM_TIME_BUFFER] = $timeBuffer * 60;
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
		} catch (ForbiddenMethod_RublonAPIException $e) {
			self::_abortConfirmation(self::rublonUrl(), 'FORBIDDEN_METHOD');
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
		
		if ($refresh && ($mobile_user_status === false || $mobile_user_status == 'no')) {
			$rublon_req = new RublonRequests();
			$mobile_user_status = $rublon_req->checkMobileStatus($user);
			self::setMobileUserStatus($user, $mobile_user_status);
		}		
		
		return (($mobile_user_status === false || $mobile_user_status == self::NO)?false:true);

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
	 * @deprecated
	 * @TODO remove function
	 * @param array $post
	 * @return boolean
	 */
	static private function _pUChangeRequiresConfirmation($post = array()) {

		$change = 0;
		if (!empty($post['pass1']) AND RublonConfirmations::isConfirmationRequired(RublonConfirmations::ACTION_OWN_PASSWORD_UPDATE)) {
			$change += 1;
		}
		$current_user = wp_get_current_user();
		if (!empty($post['email']) && $post['email'] !== self::getUserEmail($current_user)
				AND RublonConfirmations::isConfirmationRequired(RublonConfirmations::ACTION_OWN_EMAIL_UPDATE)) {
			$change += 2;
		}
		$userProtectionType = self::userProtectionType($current_user);
		if (!empty($post[self::FIELD_USER_PROTECTION_TYPE])
			&& $post[self::FIELD_USER_PROTECTION_TYPE] == self::PROTECTION_TYPE_NONE
				&& $userProtectionType == self::PROTECTION_TYPE_EMAIL
				&& RublonConfirmations::isConfirmationRequired(RublonConfirmations::ACTION_OWN_EMAIL2FA_DISABLE)) {
			$change += 4;
		}
		return $change;

	}

	/**
	 * 
	 * @param unknown $new_value
	 * @param unknown $old_value
	 * @param unknown $forceMobileApp
	 * @return number
	 */
	static private function _aSUChangeRequiresConfirmation($new_value, $old_value, &$forceMobileApp) {

		$change = 0;
		
		if (!RublonConfirmations::isConfirmationRequired(RublonConfirmations::ACTION_REDUCE_ROLE_PROTECTION_LEVEL)) {
			return $change;
		}
		
		$levels = array_flip(RublonRolesProtection::getProtectionTypes());
		$myRolesIds = RublonHelper::getMyRolesIds();
		foreach ($new_value as $key => $value) {
			$isMyRole = in_array($key, $myRolesIds);
			if (strpos($key, self::PROTTYPE_SETT_PREFIX) !== false && isset($old_value[$key])) {
				if ($levels[$value] < $levels[$old_value[$key]]) { // reducing the security level
					$change++;
				}
				else if ($levels[$value] > $levels[$old_value[$key]] AND $isMyRole) {
					// Requiring mobile app from one of my role
					$change++;
					if ($levels[$value] >= $levels[self::PROTECTION_TYPE_MOBILE]) {
						// Forcing mobile app to protect from blocking my account:
						$forceMobileApp = true;
					}
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
				
				// Try new confirmation method:
				RublonConfirmations::callbackSuccess($callback);
				
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
							// If failed:
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

		// Try new confirmation class:
		RublonConfirmations::callbackFailure($callback);
		
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


	static public function handleRegistrationException($e, $no_redirect = false) {
        	    
		$exception_code = $e->getCode();
		$exception_message = $e->getMessage();
		$exception_class = get_class($e);
		$exception_string = (string)$e;        
		$error_code = strtoupper($exception_class);
		
        if (empty($exception_code)) $exception_code = $error_code;
        
		self::setMessage($exception_code, 'error', 'CR', $no_redirect, $exception_message);

		// prepare message for issue notifier
		$error_report = array(
			'message' => 'RublonConsumerRegistration exception.',
			'exception-code' => $exception_code,
			'exception-class' => $exception_class,
			'exception-message' => '[urldecode]' . urlencode($exception_message) . '[/urldecode]',
			'exception-string' => '[urldecode]' . urlencode($exception_string) . '[/urldecode]',
		);

		// send issue notify
		try {
			self::notify($error_report, array('message-type' => 'error'));
		} catch (Exception $e) {
			// Do nothing.
		}

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
	static public function _handleCallbackException($e, $prefix = 'RC') {

		$errorCode = $e->getCode();	
		if ($errorCode == 0) {
            $errorCode = strtoupper(get_class($e));
        }
		$errno = $errorCode;	
		$errorMessage = $e->getMessage();
		if ($e instanceof RublonCallbackException) {
    		switch($errorCode) {
    				case RublonCallbackException::ERROR_MISSING_ACCESS_TOKEN:
    				$errorCode = 'MISSING_ACCESS_TOKEN';
    				break;
    				case RublonCallbackException::ERROR_REST_CREDENTIALS:
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
    				case RublonCallbackException::ERROR_USER_NOT_AUTHORIZED:
    				$errorCode = 'USER_NOT_AUTHENTICATED';
    				break;
    				case RublonCallbackException::ERROR_DIFFERENT_USER:
    				$errorCode = 'DIFFERENT_USER';
    				break;
    				case RublonCallbackException::ERROR_API_ERROR:
    				$errorCode = 'API_ERROR';
    				break;
    			default:
    				$errorCode = 'API_ERROR';
    		}
		} else {
			$errorCode = 'API_ERROR';
		}		
		
		self::setMessage($errno, 'error', $prefix, false, $errorMessage);
		
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
			self::notify(array('msg' => $notifierMessage), array('message-type' => self::RUBLON_NOTIFY_TYPE_ERROR));
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
				
				// Save info about project owner
				$projectOwner = $callback->getConsumerParam(RublonAPICredentials::FIELD_PROJECT_OWNER);				
				if ($projectOwner === -1) { // Personal edition disabled - clear cashed features
				    RublonFeature::deleteFeaturesFromCache();
				} elseif ($projectOwner) {
    				self::saveProjectOwner($user);    				
				}				
				
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
	 * Default value (NULL) added for $token because of compatybility with WordPress version 3.5.x
	 *
	 * @see wp_generate_auth_cookie()
	 * @param string $cookie
	 * @param int $user_id
	 * @param int $expiration
	 * @param string $scheme
	 * @param string $token
	 * @return string
	 */
	static public function associateSessionWithDevice($cookie, $user_id, $expiration, $scheme, $token = NULL) {
	    
	    // Compatybility with WordPress version 3.5.x
	    if (empty($token)) {
	        $token = md5(time());	        
	    }
	    
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
	static public function authenticateWithRublon($user, $remember = false) {

		$protection_type = array(
			RublonHelper::roleProtectionType($user),
			RublonHelper::userProtectionType($user)
		);

		$rublon = self::getRublon();
		$here = RublonCookies::getReturnURL();
		$authParams = array(
			'remember' => $remember,
		);
		if (in_array(self::PROTECTION_TYPE_EMAIL, $protection_type) && !in_array(self::PROTECTION_TYPE_MOBILE, $protection_type)
				&& !in_array(self::PROTECTION_TYPE_MOBILE_EVERYTIME, $protection_type)) {
			$authParams[RublonAuthParams::FIELD_CAN_USE_EMAIL2FA] = true;
		}
		if (RublonHelper::getUserProtectionType($user) == RublonHelper::PROTECTION_TYPE_MOBILE_EVERYTIME
				AND RublonFeature::checkFeature(RublonAPIGetAvailableFeatures::FEATURE_IGNORE_TRUSTED_DEVICE)) {
			$authParams[RublonAuthParams::FIELD_IGNORE_TRUSTED_DEVICE] = true;
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
		    
		    $errorCode = $e->getCode()?$e->getCode():strtoupper(get_class($e));
		    $errorMessage = $e->getMessage();	

		    if ($e instanceof PersonalEditionLimited_RublonApiException) {
		        // Clear cashed features
		        RublonFeature::deleteFeaturesFromCache();
		    }
		    
			$error_data = array(
				'msg' => 'Authentication error',
				'errorCode' => $errorCode,
				'errorMessage' => $errorMessage,
			);
			$previous_exception = $e->getPrevious();
			if ($previous_exception != null) {
				$error_data['previousCode'] = $previous_exception->getCode();
				$error_data['previousMessage'] = $previous_exception->getMessage();
			}
			try {			    
			    self::setMessage($errorCode, 'error', 'RC', false, $errorMessage);
				self::notify($error_data, array('message-type' => self::RUBLON_NOTIFY_TYPE_ERROR)); 				
			} catch (Exception $e) {
				// Do nothing.				
			}
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
		if ( !empty( $login_token_id ) ) {
			return Rublon_Transients::getTransient( 'lt_' . $login_token_id );
		}

	}


	static public function setLoginToken($user) {

		$login_token_id = self::_generateLoginTokenId();
		$login_token = array(
			'user_id' => self::getUserId( $user ),
			'token_id' => $login_token_id,
		);
		Rublon_Transients::setTransient(
			'lt_' . $login_token_id,
			$login_token,
			self::LOGIN_TOKEN_LIFETIME * MINUTE_IN_SECONDS
		);
		RublonCookies::storeLoginTokenIdInCookie( $login_token_id );

	}


	static private function _clearLoginToken( $login_token_id ) {

		Rublon_Transients::deleteTransient( 'lt_' . $login_token_id );

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
			case 'confirmations':
				$key = self::RUBLON_CONFIRMATIONS_SETTINGS_KEY;
				break;
			case 'other':
				$key = self::RUBLON_OTHER_SETTINGS_KEY;
				break;
			case 'firstinstall':
				$key = self::RUBLON_FIRSTINSTALL_SETTINGS_KEY;
				break;
			case 'transient':
				$key = self::RUBLON_TRANSIENTS_SETTINGS_KEY;
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
			case 'transient':
				$key = self::RUBLON_TRANSIENTS_SETTINGS_KEY;
				break;
			default:
				$key = self::RUBLON_SETTINGS_KEY;
		}
		update_option($key, $settings);

		do_action('rublon_save_settings', $settings, $group);

	}


	static public function canPluginAttemptRegistration(&$obstacles = null) {

		if (is_array($obstacles)) {
			$obstacles[self::INSTALL_OBSTACLE_PHP_VERSION_TOO_LOW] = version_compare( phpversion(), self::PHP_VERSION_REQUIRED, 'l' );
			$obstacles[self::INSTALL_OBSTACLE_CURL_NOT_AVAILABLE] = ! function_exists( 'curl_init' );
			$obstacles[self::INSTALL_OBSTACLE_HASH_NOT_AVAILABLE] = ! function_exists( 'hash' );
		}
		return !self::isSiteRegistered()
			&& ( version_compare( phpversion(), self::PHP_VERSION_REQUIRED, 'ge' ) )
			&& function_exists( 'curl_init' )
			&& function_exists( 'hash' );

	}


	static public function isTrackingAllowed() {

		require_once dirname(__FILE__) . '/classes/class-rublon-pointers.php';		
		$other_settings = self::getSettings('other');
		if (!empty($other_settings[Rublon_Pointers::ANONYMOUS_STATS_ALLOWED])) {
			return $other_settings[Rublon_Pointers::ANONYMOUS_STATS_ALLOWED] == self::YES;
		} else {
			return null;
		}

	}


	/**
	 * Retrieve message codes from helper and prepare them for viewing
	 * 
	 * @return array|null
	 */
	static public function getMessages() {

		$messages = array();
		if (!empty(self::$cookies['messages'])) {
			$messages = self::$cookies['messages'];
			unset(self::$cookies['messages']);
		}
		if (!empty(self::$messages)) {
			$messages = array_merge($messages, self::$messages);
			self::$messages = array();
		}
		return self::_explainMessages($messages);

	}


	/**
	 * Store a message in the plugin cookies
	 *
	 * @param string $code Message code
	 * @param string $type Message type
	 * @param string $origin Message origin
	 */
	static public function setMessage($code, $type, $origin, $now = false, $message = '') {
	
		$msg = $type . '__' . $origin . '__' . $code . '__' . $message;
		if ($now) {
			self::storeMessageInInstance($msg);			
		} else {
			RublonCookies::storeMessageInCookie($msg);
		}

	}


	static public function storeMessageInInstance($msg) {

		array_push(self::$messages, $msg);

	}


	/**
	 * Transform message codes into messages themselves
	 * 
	 * @param array $messages Message "headers" retrieved from plugin cookie
	 * @return array
	 */
	static private function _explainMessages($messages) {
        
		$result = array();
		$errorMessage = '';
		foreach ($messages as $message) {
			$msg = explode('__', $message);
			$msgType = $msg[0];
			$msgOrigin = !empty($msg[1])?$msg[1]:'';
			$msgCode = !empty($msg[2])?$msg[2]:'';;
			$msgContent = !empty($msg[3])?$msg[3]:'';
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
					case 'NL':
						$no_code = true;
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
						    __('If you already have the Rublon mobile app installed on your phone, please add the above email address as an additional email address after pressing the "@" symbol inside the app.', 'rublon'),
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
					case 'CR_6':    
					case 'RC_CODE_TIMESTAMP_ERROR':
					case 'TC_CODE_TIMESTAMP_ERROR':
						$errorMessage = __('Your server\'s time seems out of sync. Please check that it is properly synchronized - Rublon won\'t be able to verify your website\'s security otherwise.', 'rublon');
						break;
					case 'TC_MOBILE_APP_REQUIRED':
						$errorMessage = __('The authentication process has been halted.', 'rublon') . ' ' . __('This site\'s administrator requires you to confirm this operation using the Rublon mobile app.', 'rublon')
						. ' ' . sprintf(__('Learn more at <a href="%s" target="_blank">wordpress.rublon.com</a>.', 'rublon'), RublonHelper::wordpressRublonComURL());
						$no_code = true;
						break;
					case 'RC_FIRST_FACTOR_NOT_CLEARED':
						$errorMessage = __('<strong>ERROR:</strong> Unauthorized access.', 'rublon');
						$no_code = true;
						break;

					// --- Newsletter subscription exceptions --- //
						
					case 'NL_' . RublonRequests::ERROR_RUBLON_NOT_CONFIGURED:
						$errorMessage = __('Rublon Account Security has not been properly registered in the Rublon API.', 'rublon')
							. ' ' . __('Register the plugin in the Rublon API and try this action again.', 'rublon');
						break;
					case 'NL_' . RublonRequests::ERROR_INVALID_NONCE:
						$errorMessage = __('Internal WordPress error while subscribing to the newsletter.', 'rublon')
							. ' ' . __('Please try again in a minute.', 'rublon');
						break;
					case 'NL_' . RublonRequests::ERROR_NL_RUBLON_API:
						$errorMessage = __('Rublon API error while subscribing to the newsletter.', 'rublon')
							. ' ' . __('Please try again in a minute.', 'rublon')
							. ' ' . __('Should the error occur again, contact us at <a href="mailto:support@rublon.com">support@rublon.com</a>.', 'rublon');
							break;
					case 'NL_' . RublonRequests::ERROR_NL_API:
						$errorMessage = __('API error while subscribing to the newsletter.', 'rublon')
							. ' ' . __('Please try again in a minute.', 'rublon')
							. ' ' . __('Should the error occur again, contact us at <a href="mailto:support@rublon.com">support@rublon.com</a>.', 'rublon');
						break;
					case 'NL_' . RublonRequests::ERROR_ALREADY_SUBSCRIBED:
						$errorMessage = __('You are already subscribed to this newsletter.', 'rublon');
						break;
						
				    // --- API registration exceptions --- //
				    
					case 'CR_2':
					    $errorMessage = __('There is something wrong with Rublon API response. Probably the response was incomplete.', 'rublon');
					    break;
					case 'CR_8':
					    // Empty Json parse failed. Displaying general registration error message.					        
					    break;
				    case 'CR_9':
				        // Incorrect Json parse failed. Displaying general registration error message.
				        break;
				    case 'CR_10':
				        // Missing field "data" in JSON string
				        break;    
				    case 'CR_11':
				        // Missing field "sign" in JSON string
				        break;
				    case 'CR_12':
				        // Empty JSON response
				        break;
				    case 'CR_13':
				        // Secret Key is missing
				        break;
				    case 'CR_14':
				        // Invalid signatur
				        break;
				    case 'CR_15':
				        
				        break;
				    case 'CR_16':
				        // Empty JSON field "data"
				        break;     
				    case 'CR_17':
				        // Invalid JSON field "head"
				        break;    
			        case 'CR_18':
			            // Missing field "body" in JSON string
			            break;
				        
				    // Handle errors from Rublon API    
			        case 'CR_INVALID_INITIAL_PARAMETERS':
			            // Invalid initial parameters, missing email or email hash.
			            $msgContent = __('Initial plugin registration parematers are invalid', 'rublon');
			            $errorCode = 'CR_API_1';
			            break;
		            case 'CR_USER_NOT_FOUND':
		                // Cannot find users email 
		                $msgContent = __('Registration process failed', 'rublon');
		                $errorCode = 'CR_API_2';
		                break;
	                case 'CR_EMAIL_VALIDATION_FAILED':
	                    // Invalid email address or cannot register account email	                    
	                    $errorMessage = sprintf(__('Invalid email address %s. Please enter a valid email address on <a href="%s">your profile page</a>.', 'rublon'), '<strong>' . self::getUserEmail(wp_get_current_user()) . '</strong>', self::WP_PROFILE_PAGE);
	                    $msgContent = __('Registration process failed', 'rublon');
	                    $errorCode = 'CR_API_3';
	                    break;
                    case 'CR_CANNOT_CREATE_SYSTEM_TOKEN':
                        // Cannot obtain system token from ASV
                        $msgContent = __('Registration process failed', 'rublon');
                        $errorCode = 'CR_API_4';
                        break;
                    case 'CR_CANNOT_ADD_CONSUMER':
                        // Cannot add new consumer to database
                        $msgContent = __('Registration process failed', 'rublon');
                        $errorCode = 'CR_API_5';
                        break;           
                    case 'CR_CONSUMER_REGISTRATION_LIMIT_REACHED':
                        // Consumer registartion limit reached
                        $errorMessage = __('Registration limit reached. Please contact us at <a href="mailto:support@rublon.com">support@rublon.com</a>.', 'rublon');
                        $msgContent = __('Registration process failed', 'rublon');
                        $errorCode = 'CR_API_6';
                        break;
                    case 'CR_OTHER':
                        // Consumer registartion general error
                        $msgContent = __('Registration process failed', 'rublon');
                        $errorCode = 'CR_API_7';
                        break;
                    case strtoupper('cr_UnsupportedRequestMethod_RublonAPIException'):
                        // Invalid request method
                        //$msgContent = __('Registration process failed', 'rublon');
                        $errorCode = 'CR_API_8';
                        break;
                    case strtoupper('cr_MissingHeader_RublonAPIException'):
                        // Missing X-Rublon headers
                        // $msgContent = __('Registration process failed', 'rublon');
                        $errorCode = 'CR_API_9';
                        break;
                    case strtoupper('cr_UnsupportedVersion_RublonAPIException'):
                        // Unsupported sdk version
                        // $msgContent = __('Registration process failed', 'rublon');
                        $errorCode = 'CR_API_10';
                        break;
                    case strtoupper('cr_EmptyInput_RublonAPIException'):
                        // Unsupported sdk version
                        // $msgContent = __('Registration process failed', 'rublon');
                        $errorCode = 'CR_API_11';
                        break;
                    case strtoupper('cr_InvalidJSON_RublonAPIException'):
                        // Unsupported sdk version
                        // $msgContent = __('Registration process failed', 'rublon');
                        $errorCode = 'CR_API_12';
                        break;
                    case strtoupper('cr_MissingField_RublonAPIException'):
                        // Unsupported sdk version
                        // $msgContent = __('Registration process failed', 'rublon');
                        $errorCode = 'CR_API_13';
                        break;
                    case strtoupper('cr_ConsumerNotFound_RublonAPIException'):
                        // Consumer not found
                        // $msgContent = __('Registration process failed', 'rublon');
                        $errorCode = 'CR_API_14';
                        break;
                    case strtoupper('cr_InvalidSignature_RublonAPIException'):
                        // Invalid signature
                        // $msgContent = __('Registration process failed', 'rublon');
                        $errorCode = 'CR_API_15';
                        break;                            
                    case strtoupper('cr_InvalidSignature_RublonAPIException'):
                        // Invalid signature
                        // $msgContent = __('Registration process failed', 'rublon');
                        $errorCode = 'CR_API_16';
                        break;
                    case strtoupper('cr_InvalidSignature_RublonAPIException'):
                        // Invalid signature
                        // $msgContent = __('Registration process failed', 'rublon');
                        $errorCode = 'CR_API_17';
                        break;
                    case strtoupper('cr_InvalidSignature_RublonAPIException'):
                        // Invalid signature
                        // $msgContent = __('Registration process failed', 'rublon');
                        $errorCode = 'CR_API_18';
                        break;
                                                
			        // -- Authentication exceptions
			            
				    case 'RC_1':
				        $errorMessage = __('The cURL module is not available on this server. Please ask your server administrator to activate it.', 'rublon');
				        break;
			        case 'RC_4':
			            $errorMessage = __('There is a cURL error occured. Please see the message below.', 'rublon');
			            break;
		            case strtoupper('rc_InvalidResponse_RublonClientException'):		                
		                $errorCode = 'RC_200';
		                break;
	                case strtoupper('rc_MissingField_RublonClientException'):
	                    $errorCode = 'RC_201';
	                    break;
                    case strtoupper('rc_InvalidJSON_RublonClientException'):
                        $errorCode = 'RC_202';
                        break;
                    case strtoupper('rc_EmptyResponse_RublonClientException'):
                        $errorCode = 'RC_203';
                        break;
                    case strtoupper('rc_InvalidResponseHTTPStatusCode_RublonClientException'):
                        $errorCode = 'RC_204';
                        break;       
                    case strtoupper('rc_EmptyResponseField_RublonClientException'):
                        $errorCode = 'RC_205';
                        break;
                    case strtoupper('rc_EmptyErrorResponseField_RublonClientException'):
                        $errorCode = 'RC_206';
                        break;
                    case strtoupper('rc_ErrorResponse_RublonClientException'):
                        $errorCode = 'RC_207';
                        break;
                        
                    case strtoupper('rc_MissingHeader_RublonAPIException'):
                        $errorCode = 'RC_API_208';
                        break;
                    case strtoupper('rc_UnsupportedRequestMethod_RublonAPIException'):
                        $errorCode = 'RC_API_209';
                        break;
                    case strtoupper('rc_EmptyInput_RublonAPIException'):
                        $errorCode = 'RC_API_210';
                        break;
                    case strtoupper('rc_InvalidJSON_RublonAPIException'):
                        $errorCode = 'RC_API_211';
                        break;
                    case strtoupper('rc_InvalidSignature_RublonAPIException'):
                        $errorCode = 'RC_API_212';
                        break;
                    case strtoupper('rc_ConsumerNotFound_RublonAPIException'):
                        $errorCode = 'RC_API_213';
                        break;
                    case strtoupper('rc_UnsupportedVersion_RublonAPIException'):
                        $errorCode = 'RC_API_214';
                        break;
                    case strtoupper('rc_UserNotFound_RublonAPIException'):
                        $errorCode = 'RC_API_215';
                        break;
                    case strtoupper('rc_AccessTokenExpired_RublonAPIException'):
                        $errorCode = 'RC_API_216';
                        break;
                    case strtoupper('rc_UnknownAccessToken_RublonAPIException'):
                        $errorCode = 'RC_API_217';
                        break;
                    case strtoupper('rc_SessionRestore_RublonAPIException'):
                        $errorCode = 'RC_API_218';
                        break;
                    case strtoupper('rc_UnauthorizedUser_RublonAPIException'):
                        $errorCode = 'RC_API_219';
                        break;
                    case strtoupper('rc_ForbiddenMethod_RublonAPIException'):
                        $errorCode = 'RC_API_220';
                        break;
                    case strtoupper('rc_PersonalEditionLimited_RublonAPIException'):
                        $errorCode = 'RC_API_221';
                        break;
                    default:
                        $errorCode = 'E_0';    
                        
                        
	                    
				}
				$result[] = array('message' => $errorMessage, 'type' => $msgType);
				if ($no_code == false) {			    
					$result[] = array('message' => __('Rublon error code: ', 'rublon') . '<strong>' . (!empty($msgContent)?$msgContent . ' ['.$errorCode.']':$errorCode) . '</strong>', 'type' => $msgType);
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
						$updatedMessage = __('Thank you! Your account is now protected by Rublon.', 'rublon');
						break;
					case 'POSTL_AUTHENTICATION_TYPE_CHANGED':
 						$updatedMessage = __('Since Rublon plugin version 2.0, the authentication process has been changed significantly. The accounts are now protected using the email address. We have detected that your WordPress account\'s email address differs from the one you used to create your account in the Rublon mobile app. Please change your WordPress account\'s email address accordingly or add your WordPress account\'s email address in the "Email addresses" section of the Rublon mobile app.', 'rublon');
						break;
					case 'NL_' . RublonRequests::SUCCESS_NL_SUBSCRIBED_SUCCESSFULLY:
						$updatedMessage = __('You have successfully subscribed to our newsletter! Check your email inbox for more details.', 'rublon');
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
			self::handleRegistrationException($e);
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
	static public function notify($error_info, $options) {

		if (self::isTrackingAllowed()) {

			if (!empty($options['message-type'])) {
				$error_info['message-type'] = $options['message-type'];
			}
			$error_info['wp-site-title'] = get_bloginfo();
			$plugin_meta = self::preparePluginMeta();
			if (!empty($plugin_meta['meta'])) {
				$error_info['wp-site-info'] = $plugin_meta['meta'];
			}
			$error_info['wp-site-config'] = self::_getWPConfig();
			$error_info['php-info'] = self::_phpinfo();
			
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

	}


	/**
	 * Prepare server's PHP info for error reporting
	 * 
	 * @return array
	 */
	static private function _phpinfo() {

		$php_info = array(
			'php-extensions' => get_loaded_extensions(),
			'operating-system' => php_uname(),
			'php-version' => phpversion(),
			'stream-wrappers' => stream_get_wrappers(),
			'php-config-options' => ini_get_all(),
		);
	    return $php_info;

	}


	static private function _getWPConfig() {

		$wp_configuration = array(
			'plugins' => array(),
			'active-plugins' => array(),
			'themes' => array(),
			'active-theme' => array(), 
		);

		$all_plugins = get_plugins();
		$active_plugins = get_option('active_plugins');
		foreach ($all_plugins as $plugin_file => $plugin_data) {
			array_push($wp_configuration['plugins'], array(
				'plugin-name' => $plugin_data['Name'],
				'plugin-uri' => $plugin_data['PluginURI'],
				'plugin-version' => $plugin_data['Version'],
				'plugin-file' => $plugin_file,
			));
			if (in_array($plugin_file, $active_plugins)) {
				array_push($wp_configuration['active-plugins'], array(
					'active-plugin-name' => $plugin_data['Name'],
					'active-plugin-file' => $plugin_file,
				));
			}
		}

		$all_themes = wp_get_themes();
		$current_theme = wp_get_theme();
		foreach ($all_themes as $theme) {
			if ($theme instanceof WP_Theme) {
				array_push($wp_configuration['themes'], array(
					'theme-name' => $theme->Name,
					'theme-uri' => $theme->ThemeURI,
					'theme-version' => $theme->Version,
					'theme-dir' => $theme->get_stylesheet(),
					'theme-parent-dir' => $theme->get_template(),
				));
				if ($current_theme instanceof WP_Theme && $theme->get_stylesheet() == $current_theme->get_stylesheet()) {
					array_push($wp_configuration['active-theme'], array(
						'theme-name' => $theme->Name,
						'theme-dir' => $theme->get_stylesheet(),
					));
				}
			}
		}

		return $wp_configuration;

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
		
		// Enable Adam on login page by default
		if (!isset($additional_settings['enable-adam'])) {		    
		    $additional_settings['enable-adam'] = 'on';
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
			'site-url' => site_url(),
			'wordpress-version' => get_bloginfo('version'),
			'wordpress-language' => get_bloginfo('language'),
			'plugin-version' => self::getCurrentPluginVersion(),
		);

		$current_user = wp_get_current_user();
		if ($current_user instanceof WP_User) {
			$plugin_meta['admin-email'] = RublonHelper::getUserEmail($current_user);
		}

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
	static public function getUserId($user = null) {
		if (empty($user)) $user = wp_get_current_user();
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
	static public function getUserEmail($user = null) {
		if (empty($user)) $user = wp_get_current_user();
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

		do_action('rublon_site_registration');

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
	 * Get a user's roles maximum protection type.
	 * 
	 * @param WP_User $user
	 * @return string
	 */
	static public function roleProtectionType($user) {
		return RublonRolesProtection::getUserRolesMaxProtectionType($user);
	}



	/**
	 * Get a user's protection type (only user's, excluding roles protection).
	 * 
	 * @param WP_User $user
	 * @return string
	 */
	static public function userProtectionType($user = null) {
		if (is_null($user)) $user = wp_get_current_user();
		self::getMobileUserStatus($user);
		$user_protection_type = get_user_meta(self::getUserId($user), self::RUBLON_META_USER_PROTTYPE, true);
		if ($user_protection_type) {
			return RublonRolesProtection::getMinimumProtectionType($user_protection_type);
		} else {
			return self::PROTECTION_TYPE_NONE;
		}
	}
	

	/**
	 * Get a user's protection type (including roles protection).
	 * 
	 * @param WP_User $user
	 * @return string
	 */
	static public function getUserProtectionType($user = null) {
		if (is_null($user)) $user = wp_get_current_user();
		if (empty($user) OR !($user instanceof WP_User)) return array();
		else return RublonRolesProtection::getMaximumProtectionType(array(
			self::userProtectionType($user),
			self::roleProtectionType($user),
		));
	}


	/**
	 * Set a user's individual protection type
	 * 
	 * @param WP_User $user
	 * @param string $type
	 */
	static public function setUserProtectionType($user, $type) {

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
	static public function isUserProtected($user = null, &$protection_type = self::NO) {

		if (empty($user)) $user = wp_get_current_user();
		if (!($user instanceof WP_User)) return false;
		$role_protection_type = self::roleProtectionType($user);
		$user_protection_type = self::userProtectionType($user);
		$mobile_user_status = self::getMobileUserStatus($user, true);
		if ($protection_type == self::YES) {
			if ($mobile_user_status == self::YES OR $role_protection_type == self::PROTECTION_TYPE_MOBILE) {
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
			margin-bottom: 8px;
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
	static function _reloadParentFrame($text, $withMarkup = false) {

		$script = ($withMarkup) ? '<script type="text/javascript">//<![CDATA[' : '';
		$script .= '
			if (window && window.parent && window.parent.RublonWP) {
				var RublonWP = window.parent.RublonWP;
				setTimeout(function() {
				RublonWP.reloadPage();
				}, 100);
			} else location.reload();
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
				setTimeout(function() {
				RublonWP.goToPage(' . json_encode($url) . ');
				}, 100);
			} else {
				location.href = ' . json_encode($url) . ';
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
	static function _generateToken($length = 32) {

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
<?php _e('Rublon - Account Protection', 'rublon'); ?>
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
		<label class="rublon-label rublon-label-userprotectiontype"
			for="rublon-userprotectiontype-checkbox"> <input type="hidden"
				name="<?php echo self::FIELD_USER_PROTECTION_TYPE; ?>" value="none" />
				<input type="checkbox"
				name="<?php echo self::FIELD_USER_PROTECTION_TYPE; ?>"
				id="rublon-userprotectiontype-checkbox" value="email"
				<?php echo $emailChecked; ?> />
				<div
					class="rublon-lock-container rublon-unlocked-container rublon-userprotectiontype rublon-userprotectiontype-unlocked <?php echo $unlockedVisible; ?>">
					<img class="rublon-lock rublon-unlocked"
						src="<?php echo RUBLON2FACTOR_PLUGIN_URL; ?>/assets/images/unlocked.png" />
				</div>
				<div
					class="rublon-lock-container rublon-locked-container rublon-userprotectiontype rublon-userprotectiontype-locked <?php echo $lockedVisible; ?>">
					<img class="rublon-lock rublon-locked"
						src="<?php echo RUBLON2FACTOR_PLUGIN_URL; ?>/assets/images/locked.png" />
				</div>
			<?php _e('Enable account protection via email', 'rublon'); ?> 
		</label> <br />
			<div class="rublon-description">
				<span class="description"><?php
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

	static public function printSocialSection() {
?>
<h3><?php _e('Keep in touch', 'rublon'); ?></h3>
<table class="form-table">
	<tr>
		<th>
		<?php _e('Social media', 'rublon'); ?>
	</th>
		<td>
		<?php _e('Join our community', 'rublon'); ?>:
		<a href="https://www.facebook.com/RublonApp" target="_blank">Facebook</a>
			| <a href="https://twitter.com/rublon" target="_blank">Twitter</a> |
			<a href="http://instagram.com/rublon" target="_blank">Instagram</a> |
			<a href="https://www.linkedin.com/company/2772205" target="_blank">LinkedIn</a>
		</td>
	</tr>
	<tr>
		<th>
		<?php _e('Newsletter', 'rublon'); ?>
	</th>
<?php
	$current_user = wp_get_current_user();
	$user_email = ($current_user instanceof WP_User) ? self::getUserEmail($current_user) : '';
?>
	<td id="rublon-newsletter-form-container">
			<form method="POST"
				action="<?php echo wp_nonce_url( self::getActionURL( 'newsletter_subscribe' ), 'newsletter_subscribe' ) ; ?>"
				id="rublon-newsletter-form">
				<input type="text" name="email" id="rublon-newsletter-email"
					value="<?php echo htmlspecialchars($user_email); ?>"
					class="regular-text" />
				<div class="rublon-busy-spinner-anchor hidden"></div>
				<input type="submit" name="subscribe"
					id="rublon-newsletter-subscribe" class="button button-primary"
					value="<?php _e('Subscribe', 'rublon'); ?>" />
				</div>
			</form> <br />
		<?php _e('Stay up to date on new product releases, events, WordPress news and special promotions.', 'rublon'); ?>
	</td>
	</tr>
</table>
<script type="text/javascript">//<![CDATA[
	if (RublonWP) {
		RublonWP.setUpSubscribeListener();
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
	
		$url = str_replace( 'developers', '', self::RUBLON_REGISTRATION_DOMAIN );
		$url = str_replace( '-', '', $url ); 
		$url = str_replace( '/.', '/', $url );
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
		return (RublonFeature::checkFeature(RublonAPIGetAvailableFeatures::FEATURE_IDENTITY_PROVIDING) AND RublonHelper::isAccessControlWidgetEnabled());
// 		$other_settings = self::getSettings('other');
// 		return (isset($other_settings[self::SETTING_CAN_SHOW_ACM]) && $other_settings[self::SETTING_CAN_SHOW_ACM] == self::YES);

	}


	/**
	 * Set the site's permission to use ACM (Account Sharing Manager)
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
	 * Prepare an array of current site's user roles converted into ids.
	 * 
	 * @return array
	 */
	static public function getMyRolesIds() {
		$result = array();
		if (is_user_logged_in() AND $userId = get_current_user_id() AND $user = get_userdata($userId)) {
			foreach ($user->roles as $role) {
				$result[] = self::prepareRoleId($role);
			}
		}
		return $result;
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
			
		    // Remote logout available since WordPress version 3.6.0
		    if (version_compare(get_bloginfo('version'), '3.6', 'ge')) {		    
    			if (RublonHelper::isLogoutListenerEnabled()) { // Rublon-push listener
    				// Get GUI instance to embed consumer script:
    				Rublon2FactorGUIWordPress::getInstance();
    			} else {
    				// Increase the Heartbeat pulse delay:
    				add_filter( 'heartbeat_settings', array(__CLASS__, 'heartbeatSettings') );
    			}
    				
    			// Embed JavaScript for logout listener
    			if (is_admin()) {
    				remove_action( 'admin_enqueue_scripts', 'wp_auth_check_load' );
    				add_action('admin_enqueue_scripts', array(__CLASS__, 'initLogoutListenerScripts'));
    			} else {
    				add_action('wp_enqueue_scripts', array(__CLASS__, 'initLogoutListenerScripts'));
    			}
		    }
			
		}

	}
	
	/**
	 * Change the Heartbeat pulse delay.
	 * 
	 * @param array $settings
	 * @return array
	 */
	static public function heartbeatSettings($settings) {
		$settings['interval'] = self::LOGOUT_LISTENER_HEARTBEAT_INTERVAL;
		return $settings;
	}
	
	
	/**
	 * Embed JavaScript needed to make logout listener work.
	 */
	static public function initLogoutListenerScripts() {
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'heartbeat' );
		wp_enqueue_script('rublon2factor_logout_listener', RUBLON2FACTOR_PLUGIN_URL . '/assets/js/rublon-logout-listener.js',
			array('jquery', 'heartbeat'), RublonHelper::getCurrentPluginVersion());
	}

	
	/**
	 * Check if logout listener has been enabled in the Rublon plugin settings.
	 * 
	 * @return boolean
	 */
	static public function isLogoutListenerEnabled() {
		$additional_settings = RublonHelper::getSettings('additional');
		return (!empty($additional_settings[RublonHelper::RUBLON_SETTINGS_RL_ACTIVE_LISTENER])
					&& $additional_settings[RublonHelper::RUBLON_SETTINGS_RL_ACTIVE_LISTENER] == 'on');
	}
	

	/**
	 * Check if Account Sharing widget has been enabled in the Rublon plugin settings.
	 *
	 * @return boolean
	 */
	static public function isAccessControlWidgetEnabled() {
		$additional_settings = RublonHelper::getSettings('additional');
		return (!empty($additional_settings[RublonHelper::RUBLON_SETTINGS_ACCESS_CONTROL])
			AND $additional_settings[RublonHelper::RUBLON_SETTINGS_ACCESS_CONTROL] == 'on');
	}
	
	
	/**
	 * Filter the heartbeat response and send the logout trigger if needed.
	 * 
	 * @param array $response
	 * @param array $data
	 * @return array
	 */
	static public function heartbeatLogoutListener($response, $data) {
		if (!is_user_logged_in() AND RublonHelper::isLogoutListenerEnabled()) {
			if (isset($data['rublon_heartbeat']) AND $data['rublon_heartbeat'] == 'logout_listener') {
				$response['rublon-logout-trigger'] = true;
			}
		}
		return $response;
	}


	static public function memUsage($var) {
		$start_memory = memory_get_usage();
		$tmp = unserialize(serialize($var));
		return memory_get_usage() - $start_memory;

	}
	
	/**
	 * Returns Adams sentence to be said on login screen
	 * 
	 * @return String Adams sentence
	 */
	static public function adam_says() {
	    $sentences = array(
	    __('Hello, Thanks for using Rublon Account Security. My name is Adam and I wish to share with you interesting facts about WordPress and security.', 'rublon'),	    
	    __('It might interest you to know that over 600,000 account logins are compromised daily on Facebook alone. The good news is that we now have Rublon Account Security.', 'rublon'),	    
	    __('WordPress was first released on May 27 in 2003 by Matt Mullenweg and Mike Little as free software, licensed under GPLv2. That is quite some time now.', 'rublon'),	    
	    __('WordPress powers over 23% of the World Wide Web. This translates to over 60 Million websites. Wow!', 'rublon'),	    
	    __('Did you know that such known companies as CNN, Best Buy, Forbes, Mashable, TechCrunch and The New Yorker use WordPress to power their websites? It must be a solid platform, indeed!', 'rublon'),	    
	    __('Rublon Account Security is just one of the over 30,000 free WordPress plugins. Have you already taken a look at them?', 'rublon'),	    
	    __('How are you? WordPress is available in over 50 languages. Quite impressive, huh? I didn\'t even know that so many exist!', 'rublon'),	    
        __('Brute force attacks against WordPress have been very common. On some days, WordPress is hit by over 200,000 login attempts! Rublon Account Security protects you from them.', 'rublon'),        	    
        __('When did you last change your password? You don\'t have to do it monthly, but it\'s wise to change your password occasionally.', 'rublon'),        	    
        __('Your password doesn\'t have to be complicated, as long as your account is protected by Rublon Account Security. Even if someone guesses it, they probably won\'t have access to your email account or phone.', 'rublon'),        	    
        __('Are you using the Rublon mobile app? It enables you to scan a Rublon Code using your phone whenever you sign in from an unknown device. This means enhanced security for all!', 'rublon'),        	    
        __('The Rublon mobile app allows you to view and remove your trusted devices directly from the app. This is great for people on the go — like me!', 'rublon'),        	    
        __('Did you know that it is possible to log out remotely by simply removing a trusted device using any other of your devices? Last time I forgot to log out from my office computer, Rublon saved my butt!', 'rublon'),        	    
        __('I like how Rublon Account Security works with Bitcoin exchanges. Before any money transfer can be made, I need to confirm it using my phone.', 'rublon'),        	    
        __('I\'m going to reveal to you a little secret. Now that my accounts are protected by Rublon Account Security, I\'m using very simple passwords! I can finally remember them now.', 'rublon'),        	    
        __('Are you an administrator of this website? Did you know that you can request certain groups to state their identity each time they log in, regardless of whether they are using a trusted device or not? This only works with the Business Edition though.', 'rublon'),        	    
        __('Did you know that 1 in 10 social network users acknowledged falling victim to a scam or fake link on social network platforms? Watch out!', 'rublon'),        	    
        __('The US Navy sees over 100,000 cyber attacks per hour. It is a creepy world out there!', 'rublon'),        	    
        __('Small and medium sized businesses have lately become popular targets because they don\'t invest much in cyber security. The good thing is that you now have Rublon Account Security!', 'rublon'),	    
	    __('Want to give someone temporary access to your account? Give him your login credentials and add his email address to your Account Sharing Manager! This only works with Rublon Account Security Business Edition.', 'rublon')	        
	    );
	    
	    $sentence = '';	    
	    $has_adam_said_first_sentence = RublonCookies::getAdamsCookie();	    
	    if (empty($has_adam_said_first_sentence)) { // Show first sentence only once
	        $sentence = $sentences[0];	        
	    } else {
    	    // seed generator
    	    srand ((double) microtime() * 10000000);
    	    $sentence = $sentences[rand(1, 19)];
	    } 
	    
	    return $sentence; 
	}       

	static public function isAdamEnabled() {
	    $additional_settings = RublonHelper::getSettings('additional');
	    return (!empty($additional_settings[RublonHelper::RUBLON_SETTINGS_ADAM])
	        AND $additional_settings[RublonHelper::RUBLON_SETTINGS_ADAM] == 'on');
	}

	/**
	 * Check wheter Rublon is working as personal edition.
	 * @return boolean
	 */
    static public function isPersonalEdition() {        
        return !RublonFeature::isBusinessEdition();
    }
    
    static public function isProjectOwner($userId = 0) {        
        $settings = self::getSettings();
        
        if (!empty($userId)) {
            $user = get_user_by('id', $userId);
        } else {
            $user = wp_get_current_user();
        }
        
        return is_object($user) && !empty($settings[self::SETTING_PROJECT_OWNER_EMAIL]) && ($settings[self::SETTING_PROJECT_OWNER_EMAIL] == $user->user_email); 
    }
    
    static public function canShowTDMWidget() {
        return RublonFeature::isBusinessEdition() || self::isProjectOwner();
    }
    
    static public function saveProjectOwner($user = null) {
        if (empty($user)) {
          $user = wp_get_current_user();
        }        
        if ($user && $user instanceof WP_User && !empty($user->user_email)) {            
            $settings = self::getSettings();
            $settings[self::SETTING_PROJECT_OWNER_EMAIL] = $user->user_email;
            self::saveSettings($settings);            
        }
    }     
}