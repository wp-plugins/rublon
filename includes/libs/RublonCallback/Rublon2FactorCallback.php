<?php

require_once(dirname(__FILE__) . '/../Rublon/Rublon2FactorCallbackTemplate.php');

class Rublon2FactorCallback extends Rublon2FactorCallbackTemplate {


	/**
	 * User currently being authenticated
	 *  
	 */
	private $_user;

	/**
	 * An instance of the RublonHTMLHelper class
	 * 
	 */
	private $_htmlHelper;


	/**
	 * Create a new RublonHTMLHelper instance
	 * 
	 */
	public function __construct() {

		parent::__construct();
		$this->_htmlHelper = new RublonHTMLHelper($this->service);
	}


	/**
	 * Get state from GET parameters or NULL if not present
	 *
	 * @return string|NULL
	 */
	protected function getState() {

		return Rublon2FactorHelper::uriGet('state');

	}


	/**
	 * Get access token from GET parameters or NULL if not present
	 *
	 * @return string|NULL
	*/
	protected function getAccessToken() {

		return Rublon2FactorHelper::uriGet('token');

	}


	/**
	 * Handle an error and return back to the previous page
	 *
	 * @param int $errorCode
	 * @param mixed $details (optional)
	*/
	protected function finalError($errorCode, $details = null) {

		switch($errorCode) {
			case self::ERROR_MISSING_ACCESS_TOKEN:
				$errorCode = 'MISSING_ACCESS_TOKEN';
				break;
			case self::ERROR_REST_CREDENTIALS:
				$errorCode = 'REST_CREDENTIALS_FAILURE';
				if (isset($details))
					$additionalErrorMessage = __('Error details: ', 'rublon2factor') . $details->getMessage();
				break;
			case self::ERROR_UNKNOWN_ACTION_FLAG:
				$errorCode = 'UNKNOWN_ACTION_FLAG';
				break;
			case self::ERROR_MISSING_ACTION_FLAG:
				$errorCode = 'MISSING_ACTION_FLAG';
				break;
			case self::ERROR_USER_NOT_AUTHORIZED:
				$errorCode = 'USER_NOT_AUTHENTICATED';
				break;
			case self::ERROR_DIFFERENT_USER:
				$errorCode = 'DIFFERENT_USER';
				break;
			case self::ERROR_API_ERROR:
				$errorCode = 'API_ERROR';
				break;
			default:
				$errorCode = 'API_ERROR';
		}

		Rublon2FactorHelper::setMessage($errorCode, 'error', 'RC');

		// prepare message for issue notifier
		$notifierMessage = 'RublonCallback error.<br /><br />' . __('Rublon error code: ', 'rublon2factor') . '<strong>' . $errorCode . '</strong>';
		if (!empty($additionalErrorMessage))
			$notifierMessage .= '<br />' . $additionalErrorMessage;
	
		// send issue notify
		echo $this->_notify($notifierMessage);

		$returnPage = Rublon2FactorHelper::getReturnPage();
		$this->_returnToPage($returnPage);

	}
	

	/**
	 * Restore session with passed session ID
	 *
	 * @param string $sessionId
	*/
	protected function sessionStart($sessionId) {

		// Not needed - WordPress doesn't use sessions

	}


	/**
	 * Check whether user exists in session and has been authorized by first factor
	 *
	 * @return boolean
	*/
	protected function isUserAuthorizedByFirstFactor() {

		$consumerParams = $this->response->getConsumerParams();
		if (!empty($consumerParams['wp_user'])) {
			if (isset($consumerParams[self::FIELD_ACTION_FLAG])) {
				if ($consumerParams[self::FIELD_ACTION_FLAG] == RublonAuthParams::ACTION_FLAG_LOGIN) {
					if (!empty($consumerParams['wp_auth_time']))
						$timeOK = (time() - $consumerParams['wp_auth_time'] <= Rublon2FactorHelper::RUBLON_AUTH_TIME * 60);
					else
						$timeOK = false;
				} else {
					$timeOK = true;
				}
				if ($timeOK) {
					$systemUser = get_user_by('id', $consumerParams['wp_user']);
					if (!empty($systemUser)) {
						$this->_user = $systemUser;
						return true;
					}
				}
			} else {
				$this->finalError(self::ERROR_UNKNOWN_ACTION_FLAG);
			}
		}
		return false;

	}


	/**
	 * Get Rublon profile ID of the user in current session
	 *
	 * @return int
	*/
	protected function getRublonProfileId() {

		if ($this->_user) {
			return Rublon2FactorHelper::getUserProfileId($this->_user);
		}
		return '';

	}


	/**
	 * Set Rublon profile ID of the user in current session
	 *
	 * @param int $rublonProfileId New profile ID
	*/
	protected function setRublonProfileId($rublonProfileId) {

		if ($this->_user) {
			if (!empty($rublonProfileId)) {
				if (Rublon2FactorHelper::isUserSecured($this->_user)) {
					$errorCode = 'ALREADY_PROTECTED';
					Rublon2FactorHelper::setMessage($errorCode, 'error', 'RC');
				} else {
					Rublon2FactorHelper::connectRublon2Factor($this->_user, $rublonProfileId);
				}
				Rublon2FactorCookies::setAuthCookie();
			} else {
				Rublon2FactorHelper::disconnectRublon2Factor($this->_user);
				Rublon2FactorCookies::clearAuthCookie();
			}
		}

	}


	/**
	 * Set second-factor authorization status of the user in current session to SUCCESS
	 *
	 */
	protected function authorizeUser() {

		if ($this->_user) {
			wp_clear_auth_cookie();
			Rublon2FactorCookies::setLoggedInCookie(Rublon2FactorHelper::getUserId($this->_user));
			Rublon2FactorCookies::setAuthCookie($this->_user);
			do_action('wp_login', $this->_user->user_login, $this->_user);
		}

	}


	/**
	 * Cancel authentication process and return back to the previous page
	 *
	 * Note that it may be login authentication where user is not signed-in
	 * or enabling/disabling Rublon when user is signed-in.
	 *
	 */
	protected function cancel() {

		$page = Rublon2FactorHelper::getReturnPage();
		$this->_returnToPage($page);

	}


	/**
	 * Handle success
	 *
	 */
	protected function finalSuccess() {

		$consumerParams = $this->response->getConsumerParams();
		$flag = $consumerParams[self::FIELD_ACTION_FLAG];
		switch ($flag) {
			case RublonAuthParams::ACTION_FLAG_LOGIN:
				$returnUrl = Rublon2FactorHelper::getReturnPage();
				$returnUrl = (!empty($returnUrl)) ? $returnUrl : admin_url();
				$sessionData = $this->response->getSessionData();
				$this->_returnToPage($returnUrl, $sessionData);
				break;
			case RublonAuthParams::ACTION_FLAG_LINK_ACCOUNTS:
				$currentUser = $this->_user;
				if (!Rublon2FactorHelper::isUserSecured($currentUser)) {
					$errorCode = 'CANNOT_PROTECT_ACCOUNT';
					Rublon2FactorHelper::setMessage($errorCode, 'error', 'RC');
				} else {
					$updateMessage = 'ACCOUNT_PROTECTED';
					Rublon2FactorHelper::setMessage($updateMessage, 'updated', 'RC');
				}
				$sessionData = $this->response->getSessionData();
				$page = Rublon2FactorHelper::getReturnPage();
				$this->_returnToPage($page, $sessionData);
				break;
			case RublonAuthParams::ACTION_FLAG_UNLINK_ACCOUNTS:
				$currentUser = $this->_user;
				if (!Rublon2FactorHelper::isUserSecured($currentUser)) {
					$updateMessage = 'PROTECTION_DISABLED';
					Rublon2FactorHelper::setMessage($updateMessage, 'updated', 'RC');
				} else {
					$errorCode = 'CANNOT_DISABLE_ACCOUNT_PROTECTION';
					Rublon2FactorHelper::setMessage($errorCode, 'error', 'RC');
				}
				$sessionData = $this->response->getSessionData();
				$page = Rublon2FactorHelper::getReturnPage();
				$this->_returnToPage($page, $sessionData);
				break;
		}

	}


	/**
	 * Retrieve consumer's systemToken
	 *
	 * @return string
	*/
	protected function getSystemToken() {

		$settings = Rublon2FactorHelper::getSettings();
		return (!empty($settings['rublon_system_token'])) ? $settings['rublon_system_token'] : '';

	}


	/**
	 * Retrieve consumer's secretKey;
	 *
	 * @return string
	*/
	protected function getSecretKey() {

		$settings = Rublon2FactorHelper::getSettings();
		return (!empty($settings['rublon_secret_key'])) ? $settings['rublon_secret_key'] : '';

	}


	/**
	 * Return the (optional) Rublon API domain
	 *
	 * Default domain can be replaced by testing configuration.
	 *
	 * @return string
	 */
	protected function getAPIDomain() {

		return Rublon2FactorHelper::getAPIDomain();

	}


	/**
	 * Get current language
	 *
	 * @return string
	 */
	protected function getLang() {

		return Rublon2FactorHelper::getBlogLanguage();

	}


	/**
	 * Send an error notifier request to Rublon (use a workaround if cURL not present)
	 *
	 * @param string $msg
	 * @return string
	 */
	private function _notify($msg) {

		$data = array();
		$data['msg'] = $msg;
		
		if (!function_exists('curl_init')) {
			return '<img src="' . RUBLON2FACTOR_NOTIFY_URL . '/' . base64_encode(urlencode($msg)) . '" style="display: none">';
		} else {
			try {
				Rublon2FactorHelper::notify($data);
			} catch (RublonException $e) {
				// Should an error occur here, don't inform the user about it, too low-level
			}
			return '';
		}

	}


	/**
	 * Perform a safe redirection to a given URL or retrieve it from Rublon session data
	 *
	 * @param array $sessionData Rublon session data
	 * @param string $url URL address to redirect to
	 */
	private function _returnToPage($url, $sessionData = null) {

		if (!empty($sessionData)) {
			echo $this->_htmlHelper->returnToPage($sessionData, $url);
		} else {
			wp_safe_redirect($url);
			exit;
		}

	}


}