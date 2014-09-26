<?php
/**
 * Code to be run while on login page
 *
 * @package   rublon2factor\includes
 * @author     Rublon Developers http://www.rublon.com
 * @copyright  Rublon Developers http://www.rublon.com
 * @license    http://opensource.org/licenses/gpl-license.php GNU Public License, version 2 
 */


/**
 * Display Rublon messages on the login screen
 * 
 * @param array $message WP message displayed on the login screen
 * @return void
 */
function rublon2factor_login_message($message) {

	$messages = RublonHelper::getMessages();
	if (!empty($message)) {
		$result = $message;
	} else {
		$result = '';
	}
	if ($messages) {
		$wpVersion = get_bloginfo('version');
		if (version_compare($wpVersion, '3.8', 'ge')) {
			$result .= RublonHelper::transformMessagesToVersion($messages);
		} else {
			$result .= RublonHelper::transformMessagesToVersion($messages, '3.7');
		}
	}
	return $result;

}

add_filter('login_message', 'rublon2factor_login_message');

/**
 * Transfers any plugin messages back to the cookie on redirection
 * 
 * @param string $location
 * @param int $status
 * @return string
 */
function rublon2factor_wp_redirect($location, $status = 302) {

	RublonHelper::cookieTransferBack();
	return $location;

}

add_filter('wp_redirect', 'rublon2factor_wp_redirect');


/**
 * Save the page the user needs to be redirected to after a successful authentication
 *
 * @param string $redirect_to
 * @return string
 */
function rublon2factor_login_redirect($redirect_to) {

	if (!empty($redirect_to))
		RublonCookies::storeReturnURL($redirect_to);
	return $redirect_to;

}

add_filter( 'login_redirect', 'rublon2factor_login_redirect', 10, 3);


/**
 * Makes sure the plugin is always run before other plugins
 * 
 * @return void
 */
function rublon2factor_plugin_activated_mefirst() {

	RublonHelper::meFirst();
	if (!RublonHelper::isPluginRegistered()) {
		RublonHelper::enqueueRegistration(true);
	}

}

add_action('activated_plugin', 'rublon2factor_plugin_activated_mefirst');

function rublon2factor_authenticate($user, $username, $password) {

	$user = wp_authenticate_username_password($user, $username, $password);

	if (is_wp_error($user)) {
		return $user;
	} else {
		if (RublonHelper::isPluginRegistered()) {
			wp_clear_auth_cookie();
			$protectionType = array(
				RublonHelper::roleProtectionType($user),
				RublonHelper::userProtectionType($user)
			);
			$authURL = RublonHelper::authenticateWithRublon($user, $protectionType);
			if (empty($authURL)) {
				if (in_array(RublonHelper::PROTECTION_TYPE_MOBILE, $protectionType)) {
					RublonHelper::setMessage('ROLE_BLOCKED', 'error', 'LM');
					$returnPage = RublonHelper::getReturnPage();
					wp_redirect($returnPage);
					exit;
				} else {
					RublonCookies::setLoggedInCookie(RublonHelper::getUserId($user));
					return $user;
				}
			} else {
				wp_redirect($authURL);
				exit;
			}
		} else {
			return $user;
		}
	}
}

remove_filter('authenticate', 'wp_authenticate_username_password', 20);
add_filter('authenticate', 'rublon2factor_authenticate', 10, 3);


/**
 * Main initialization hook
 * 
 * Check if we should start the 2FA authentication or just retrieve the
 * plugin cookies and display current page.
 * 
 * @return void
 */
function rublon2factor_init() {

	RublonHelper::cookieTransfer();

}

add_action('init', 'rublon2factor_init');

/**
 * Store WP authentication cookie params in the settings for future use
 * 
 * Since the plugin needs to duplicate the WP cookie settings, we need to
 * store them for future use, as on this stage we do not authenticate
 * the user with the second factor yet - this will happen in a moment.
 * 
 * @param string $auth_cookie Set cookie
 * @param int $expire Whether the cookie is a session or permanent one
 * @param int $expiration Expiration date (timestamp)
 * @param id $user_id Logged in user's WP ID
 * @param string $scheme Whether the cookie is secure
 * @return void
 */
function rublon2factor_store_auth_cookie_params($auth_cookie, $expire, $expiration, $user_id = null, $scheme) {

	if ($user_id) {
		$user = get_user_by('id', $user_id);
		$secure = ($scheme == 'secure_auth');
		if ($user) {
			$secure_logged_in_cookie = apply_filters('secure_logged_in_cookie', false, $user_id, $secure);
			$cookieParams = array(
					'secure' => $secure,
					'remember' => ($expire > 0),
					'logged_in_secure' => $secure_logged_in_cookie,
			);
			$settings = RublonHelper::getSettings();
			$settings['wp_cookie_params'] = $cookieParams;
			$settings['wp_cookie_expiration'] = $expire;
			RublonHelper::saveSettings($settings);
		}
	}

}

add_action('set_auth_cookie', 'rublon2factor_store_auth_cookie_params', 10, 5);


/**
 * Check Rublon protection for displayed users.
 *
 * The function is executed during any query to the users
 * table. It retrieves the user list that is to be
 * displayed on the admin's user list page and checks the
 * status of the Rublon protection for each user, then
 * stores the result in the prerender data for further use.
 *
 * @param WP_User_Query $query Current "users" query
 */
function rublon2factor_pre_user_query(&$query) {

	global $pagenow;

	// Check if plugin is active and we're on the user list page
	if (RublonHelper::isPluginRegistered() && $pagenow == 'users.php') {
		$query_vars = $query->query_vars;
		// Check whether the query was manually executed by the plugin
		// in order to avoid an infinite loop.
		if (empty($query_vars['exclude']) || !in_array('rublon_user_query', $query_vars['exclude'])) {
			// This is not the plugin's query - prepare one.
			array_push($query_vars['exclude'], 'rublon_user_query');
			$users_query = new WP_User_Query($query_vars);
			$users = $users_query->get_results();
			if (!empty($users)) {
				$userList = array();
				$check = null;
				// Prepare the CheckProtection request via Rublon API.
				require_once dirname(__FILE__) . '/libs/RublonImplemented/RublonAPICheckProtection.php';
				foreach ($users as $user) {
					if (empty($check)) {
						$check = new RublonAPICheckProtection(
							RublonHelper::getRublon(),
							RublonHelper::getUserId($user),
							RublonHelper::getUserEmail($user)
						);
					} else {
						$check->appendUser(RublonHelper::getUserId($user), RublonHelper::getUserEmail($user));
					}
				}
				try {
					$check->perform();
					$rublon_users = array();
					foreach ($users as $user) {
						if ($check->isProtectionEnabled(RublonHelper::getUserId($user))) {
							$rublon_users[RublonHelper::getUserId($user)] = true;
						}
					}
					// Store the request's result in the prerender data.
					RublonHelper::setPrerenderData(
						RublonHelper::PRERENDER_USERS,
						$rublon_users
					);
				} catch (RublonException $e) {
					// TODO: Issue Notifier
				}
			}
		}
	}

}

add_action('pre_user_query', 'rublon2factor_pre_user_query', 10, 1);

/**
 * Clear Rublon auth cookie on logout
 * 
 * @return void
 */
function rublon2factor_wp_logout() {

	RublonCookies::clearAuthCookie();

}

add_action('wp_logout', 'rublon2factor_wp_logout');


/**
 * Perform any post-login operations
 *
 * Checks if the user has been protected by an earlier
 * version of the Rublon plugin
 *
 * @param string $user_login
 * @param WP_User $user
 */
function rublon2factor_wp_login($user_login, $user) {

	if (RublonHelper::isUserSecured($user) && !RublonHelper::isUserAuthenticated($user)) {
		$msg_meta = get_user_meta(RublonHelper::getUserId($user), RublonHelper::RUBLON_META_AUTH_CHANGED_MSG, true);
		if ($msg_meta === '') {
			$msg_meta = -1;
		} else {
			$msg_meta = (int)$msg_meta;
		}
		if ($msg_meta > 8) {
			delete_user_meta(RublonHelper::getUserId($user), RublonHelper::RUBLON_META_AUTH_CHANGED_MSG);
			RublonHelper::disconnectRublon2Factor($user);
		} else {
			$msg_meta++;
			if ($msg_meta % 3 == 0) {
				RublonHelper::setMessage('AUTHENTICATION_TYPE_CHANGED', 'updated', 'POSTL');
			}
			update_user_meta(RublonHelper::getUserId($user), RublonHelper::RUBLON_META_AUTH_CHANGED_MSG, $msg_meta);
		}
	}

}

add_action('wp_login', 'rublon2factor_wp_login', 10, 2);


/**
 * Render hidden elements to be used later in the iframe layer
 */
function rublon2factor_show_user_profile() {

	if (RublonHelper::isPluginRegistered()) {
		echo '<script>
			document.addEventListener(\'DOMContentLoaded\', function() {
				RublonWP.setUpFormSubmitListener();
			}, false);
		</script>';
		$current_user = wp_get_current_user();
		echo new RublonConsumerScript(
			RublonHelper::getRublon(),
			RublonHelper::getUserId($current_user),
			RublonHelper::getUserEmail($current_user)
		);
		if ($current_user && $current_user instanceof WP_User) {
			wp_nonce_field(
				RublonHelper::TRANSIENT_PROFILE_PREFIX . RublonHelper::getUserId($current_user),
				RublonHelper::NONCE_PROFILE_UPDATE_NAME
			);
			RublonHelper::printEmail2FAProfileSection($current_user);
		}
	}

}

add_action('show_user_profile', 'rublon2factor_show_user_profile');

function rublon2factor_user_profile_update_errors(&$errors, $update, &$user) {

	global $pagenow;

	$current_user = wp_get_current_user();
	$current_user_id = RublonHelper::getUserId($current_user);
	$updated_user_id = (!empty($user->ID)) ? $user->ID : $user->Id;

	if ($pagenow == 'profile.php'
			&& $current_user_id == $updated_user_id
			&& empty($errors->errors)
			&& $update) {
		if (!empty($_POST)) {
			$post = $_POST;
			RublonHelper::checkPostDataProfileUpdate($post);
		}
	}
	
}

add_action('user_profile_update_errors', 'rublon2factor_user_profile_update_errors', 10, 3);