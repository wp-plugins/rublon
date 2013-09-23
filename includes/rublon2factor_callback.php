<?php
/**
 * Code to be run as a callback from Rublon server
 *
 * @package   rublon2factor\includes
 * @author     Rublon Developers http://www.rublon.com
 * @copyright  Rublon Developers http://www.rublon.com
 * @license    http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */

/**
 * Rublon2FactorHelper class
 *
 * It provides functionalities for handles actions made
 * after the Rublon Code has been scanned.
 * Based on data from Rublon server it will try to confirm user login
 * or secure account of currently logged in WP user with Rublon.
 */

class Rublon2FactorCallback {

	const RUBLON_DOMAIN = 'https://code.rublon.com';

	private $consumer = null;
	private $service = null;
	private $htmlHelper = null;
	private $consumerScript = null;
	private $accessToken = null;

	/**
	 * Constructor
	 * 
	 * @param array $settings
	 */
	public function __construct($settings){
		try {
			$this->consumer = new RublonConsumer($settings['rublon_system_token'], $settings['rublon_secret_key']);
			$this->consumer->setDomain(self::RUBLON_DOMAIN);
			$language = get_bloginfo('language');
			$language = strtolower(substr($language, 0, 2));
			if (!in_array($language, array('pl')))
				$language = 'en';
			$this->consumer->setLang($language);
			$this->init();
		} catch (RublonException $error) {
			$this->handleError($error);
		}
	}
	
	/**
	 * Initialize callback.
	 */
	public function init()
	{
		$this->service = new RublonService2Factor($this->consumer);
		$this->htmlHelper = new RublonHTMLHelper($this->service);
		$this->consumerScript = new RublonConsumerScript($this->service);
	}

	/**
	 * Return credentials read form service for a given access token.
	 * 
	 * @return RublonResponseCredentials
	 */
	public function getCredentials()
	{
		return $this->service->getCredentials($this->accessToken);
	}

	/**
	 * Method which runs the callback actions after the successful
	 * Rublon Code scanning.
	 */
	public function run($state, $token, $windowType) {

		switch ($state) {
			case 'ok':
				$this->accessToken = $token;
				try {
					$this->handleCallback();
				} catch (RublonException $error) {
					$this->handleError($error);
				}
				break;
			case 'error':
				$error = new RublonException('', RublonException::CODE_INVALID_RESPONSE);
				$this->handleError($error);
				break;
			default:
				$this->returnToPage(null, admin_url('profile.php'));				
				break;
		}		
		
		
		
	}

	/**
	 * Handle the callback for an "OK" state
	 * 
	 */
	public function handleCallback()
	{
		$consumerParams = $this->getCredentials()->getConsumerParams();
		$action = (!empty($consumerParams) && !empty($consumerParams['action'])) ? $consumerParams['action'] : null;
		$this->handleAction($action);
	}

	/**
	 * Handle specified action received from Rublon server.
	 *
	 * @param string $action
	 * @return void
	 */
	function handleAction($action) {
		switch ($action) {
			case RublonAuthParams::ACTION_FLAG_LOGIN :
				$this->authenticateLogin();
				break;
			case RublonAuthParams::ACTION_FLAG_LINK_ACCOUNTS :
				$this->secureAccount();
				break;
			case RublonAuthParams::ACTION_FLAG_UNLINK_ACCOUNTS :
				$this->disableAccountSecurity();
				break;
			default :
				$this->returnToPage(null, admin_url('profile.php'));
				exit ;
		}
	}

	/**
	 * Clear the second factor in user authentication
	 * 
	 */
	function authenticateLogin() {
		$credentials = $this->getCredentials();
		$rublonProfileId = $credentials->getProfileId();
		$systemUser = Rublon2FactorHelper::getUserToAuthenticate();
		$returnUrl = Rublon2FactorHelper::getReturnPageUrl();
		$returnUrl = (!empty($returnUrl)) ? $returnUrl : admin_url();

		if ($systemUser && $rublonProfileId == get_user_meta(Rublon2FactorHelper::getUserId($systemUser), Rublon2FactorHelper::RUBLON_META_PROFILE_ID, true)) {
			wp_clear_auth_cookie();
			wp_set_auth_cookie(Rublon2FactorHelper::getUserId($systemUser), true);
			do_action('wp_login', $systemUser->user_login, $systemUser);
		} else {
			$errorCode = 'AUTHENTICATE_ERROR';
			Rublon2FactorHelper::setMessage(__('There was a problem during the authentication process.', 'rublon2factor'), 'error');
			Rublon2FactorHelper::setMessage(__('Rublon error code: ', 'rublon2factor') . '<strong>' . $errorCode . '</strong>', 'error');
		}

		$sessionData = $credentials->getSessionData();
		$this->returnToPage($sessionData, $returnUrl);

	}

	/**
	 * Links currently authenticated (already logged in) WP user with
	 * Rublon account.
	 *
	 */
	function secureAccount() {
		$credentials =  $this->getCredentials();
		$rublonProfileId = $credentials->getProfileId();
		$consumerParams = $this->getCredentials()->getConsumerParams();
		$currentUser = wp_get_current_user();
		
		if (!Rublon2FactorHelper::isUserSecured($currentUser)) {
			if (!empty($consumerParams['security_token'])) {
				if (!Rublon2FactorHelper::validateSecurityToken($consumerParams['security_token'])) {
					Rublon2FactorHelper::setMessage(__('Warning: this may be a hijacking attempt! The security of this website might be compromised.', 'rublon2factor'), 'error');
					$errorCode = 'MISSING_SECURITY_TOKEN';
					Rublon2FactorHelper::setMessage(__('Rublon error code: ', 'rublon2factor') . '<strong>' . $errorCode . '</strong>', 'error');
					$sessionData = $credentials->getSessionData();
					$this->returnToPage($sessionData, admin_url('profile.php'));
				}
			}
			$success = Rublon2FactorHelper::connectRublon2Factor($currentUser, $rublonProfileId);
			if ($success) {
				Rublon2FactorHelper::setMessage(__('Your account has been protected by Rublon.', 'rublon2factor'), 'updated');
				Rublon2FactorHelper::clearSecurityTokens();
			} else {
				Rublon2FactorHelper::setMessage(__('Unable to protect your account with Rublon.', 'rublon2factor'), 'error');
				$errorCode = 'CANNOT_PROTECT_ACCOUNT';
				Rublon2FactorHelper::setMessage(__('Rublon error code: ', 'rublon2factor') . '<strong>' . $errorCode . '</strong>', 'error');
			}
		} else {
			$errorCode = 'ALREADY_PROTECTED';
			Rublon2FactorHelper::setMessage(__('You cannot protect an account already protected by Rublon.', 'rublon2factor'), 'error');
			Rublon2FactorHelper::setMessage(__('Rublon error code: ', 'rublon2factor') . '<strong>' . $errorCode . '</strong>', 'error');
		}

		$sessionData = $credentials->getSessionData();
		$this->returnToPage($sessionData, admin_url('profile.php'));
	}
	
	/**
	 * Remove Rublon second factor authentication from current
	 * user account.
	 */
	function disableAccountSecurity() {
		$credentials =  $this->getCredentials();
		$rublonProfileId = $credentials->getProfileId();
		$currentUser = wp_get_current_user();
		
		if (Rublon2FactorHelper::isUserSecured($currentUser)) {
			$success = Rublon2FactorHelper::disconnectRublon2Factor($currentUser, $rublonProfileId);
			if ($success) {
				Rublon2FactorHelper::setMessage(__('Rublon protection has been disabled.', 'rublon2factor'), 'updated');
			} else {
				Rublon2FactorHelper::setMessage(__('Unable to disable Rublon protection.', 'rublon2factor'), 'error');
				$errorCode = 'CANNOT_DISABLE_ACCOUNT_SECURITY';
				Rublon2FactorHelper::setMessage(__('Rublon error code: ', 'rublon2factor') . '<strong>' . $errorCode . '</strong>', 'error');
			}
		} else {
			$errorCode = 'USER_NOT_PROTECTED';
			Rublon2FactorHelper::setMessage(__('You cannot disable Rublon protection on a non-protected account.', 'rublon2factor'), 'error');
			Rublon2FactorHelper::setMessage(__('Rublon error code: ', 'rublon2factor') . '<strong>' . $errorCode . '</strong>', 'error');
		}

		$sessionData = $credentials->getSessionData();
		$this->returnToPage($sessionData, admin_url('profile.php'));
	}
	

	/**
	 * Perform a safe redirection to a given URL or retrieve it from Rublon session data
	 * 
	 * @param array $sessionData Rublon session data
	 * @param string $url URL address to redirect to
	 */
	private function returnToPage($sessionData = null, $url = null) {
		$returnPageUrl = (isset($url)) ? $url : Rublon2FactorHelper::getReturnPageUrl();
		if (!empty($sessionData)) {
			echo $this->htmlHelper->returnToPage($sessionData, $returnPageUrl);
		} else {
			if (empty($returnPageUrl)) {
				$returnPageUrl = '/';
			}
			header('Location: '. $returnPageUrl);
			exit;
		}
	}

	/**
	 * Send an error notifier request to Rublon (use a workaround if cURL not present)
	 * 
	 * @param string $msg
	 * @return string
	 */
	private function notify($msg) {
		$data = array();		
		$data['msg'] = $msg;		
		
		if (!function_exists('curl_init')) {
			return '<img src="'.$url.'/'.base64_encode(urlencode($msg)).'" style="display: none">';
		} else {			
			try {				 					
				Rublon2FactorHelper::notify($data);
			} catch (RublonException $e) {
				Rublon2FactorHelper::setMessage($e->getMessage(), 'error');
			}			
			return '';
		}
		
	}
	
	/**
	 * Handle error
	 *
	 * @param mixed $error
	 * @return void
	*/
	public function handleError($error){
		global $rublon_update_message_is_error;
		
		$errorMessage = '';
	
		switch($error->getCode())
		{
			case RublonException::CODE_CURL_NOT_AVAILABLE:
				$errorMessage = __('cURL functions are not available. Please install the appropriate library.','rublon2factor');
				break;
	
			case RublonException::CODE_CONNECTION_ERROR:
				$errorMessage = __('Rublon server connection problem.<br />', 'rublon2factor');
	
				$error_origin = $error->getPrevious();
				if(isset($error_origin))
					$errorMessage .= __('Error details: ', 'rublon2factor') . $error_origin->getMessage();
				break;
	
			case RublonException::CODE_INVALID_RESPONSE:
				$errorMessage = __('Rublon server error. Please try again in a moment.','rublon2factor');
				break;
	
			case RublonException::CODE_RESPONSE_ERROR:
				$errorMessage = __('Rublon server error.<br />','rublon2factor');
				$errorMessage .= __('Error details: ', 'rublon2factor');
				
				switch($error->getMessage())
				{
					case '(ERROR) Invalid signature':
						$errorMessage .= __('Configuration error. Invalid secret key.', 'rublon2factor');
						break;
					default:
						$errorMessage .= $error->getMessage();
						break;
				}				
				break;
			
			default:
				switch($error->getMessage()){
					case 'Invalid response.':
						$errorMessage = __('Rublon server error. Please try again in a moment.','rublon2factor');
						break;
					case 'Invalid GET parameters.':
						$errorMessage = __('Rublon server internal error. Received parameters are invalid. Please try again in a moment.', 'rublon2factor');
						break;
					default:
						$errorMessage = $error->getMessage();
				}
		}
		
		// send issue notify
		$errorMessage .= $this->notify($errorMessage);
		
		Rublon2FactorHelper::setMessage($errorMessage, 'error');
		$this->returnToPage(null, admin_url('profile.php'));
	}

	/**
	 * Add the Rublon JS Library to the page code.
	 */
	public function addScript() {
		echo $this->consumerScript;
	}
	
	/**
	 * Add Rublon secure account button to the page code.
	 */
	public function addSecureAccountButton() {
		$button = $this->service->createButtonEnable(__('Protect your account', 'rublon2factor'));
		$securityToken = Rublon2FactorHelper::newSecurityToken();
		$button->getAuthParams()->setConsumerParam('security_token', $securityToken);
		echo $button;
	}
	
	/**
	 * Add button for disabling Rublon protection to the page code.
	 */
	public function addDisableAccountSecurityButton() {
		$label = __('Disable account protection', 'rublon2factor');
		$currentUser = wp_get_current_user();		
		$button = $this->service->createButtonDisable($label, get_user_meta(Rublon2FactorHelper::getUserId($currentUser), Rublon2FactorHelper::RUBLON_META_PROFILE_ID, true));
		echo $button;
	}
	
	/**
	 * Perform authorization by Rublon2Factor.
	 */
	public function authenticateWithRublon($user) {

		Rublon2FactorHelper::setUserToAuthenticate($user);

		$authParams = new RublonAuthParams($this->service);
		$authParams->setConsumerParam('action', RublonAuthParams::ACTION_FLAG_LOGIN);

		$this->service->initAuthorization(get_user_meta(Rublon2FactorHelper::getUserId($user), Rublon2FactorHelper::RUBLON_META_PROFILE_ID, true), $authParams);
	}


}