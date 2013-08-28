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
	const RUBLON_INSECURE_ACCOUNT_ACTION = 'insecure_action';

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
				$this->returnToPage();				
				break;
		}		
		
		
		
	}

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
				$this->insecureAccount();
				break;
			default :
				$this->returnToPage();
				exit ;
		}
	}

	function authenticateLogin() {
		$credentials = $this->getCredentials();
		$rublonProfileId = $credentials->getProfileId();
		$systemUser = Rublon2FactorHelper::getUserToAuthenticate();
		
		if ($systemUser && $rublonProfileId == $systemUser->rublon_profile_id){
			wp_clear_auth_cookie();
			wp_set_auth_cookie($systemUser->id, true);
			do_action('wp_login', $systemUser->user_login, $systemUser);
		}
		
		$sessionData = $credentials->getSessionData();
		$this->returnToPage($sessionData, admin_url());
	}

	/**
	 * Links currently authorized (already logged in) WP user with
	 * Rublon account.
	 *
	 */
	function secureAccount() {
		$credentials =  $this->getCredentials();
		$rublonProfileId = $credentials->getProfileId();
		$currentUser = wp_get_current_user();
		
		if (!Rublon2FactorHelper::isUserSecured($currentUser)) {
			$success = Rublon2FactorHelper::connectRublon2Factor($currentUser, $rublonProfileId);
			if ($success) {
				Rublon2FactorHelper::setMessageType('updated');
				Rublon2FactorHelper::setMessage(__('Your account has been secured by Rublon.', 'rublon2factor'));
			} else {
				Rublon2FactorHelper::setMessageType('error');
				Rublon2FactorHelper::setMessage(__('Unable to secure your account by Rublon.', 'rublon2factor'));
			}
		}

		$sessionData = $credentials->getSessionData();
		$this->returnToPage($sessionData);
	}
	
	/**
	 * Remove Rublon second factor authorization from current
	 * user account.
	 */
	function insecureAccount() {
		$credentials =  $this->getCredentials();
		$rublonProfileId = $credentials->getProfileId();
		$currentUser = wp_get_current_user();

		if (Rublon2FactorHelper::isUserSecured($currentUser)) {
			$success = Rublon2FactorHelper::unconnectRublon2Factor($currentUser, $rublonProfileId);
			if ($success) {
				Rublon2FactorHelper::setMessageType('updated');
				Rublon2FactorHelper::setMessage(__('Rublon security has been disabled.', 'rublon2factor'));
			} else {
				Rublon2FactorHelper::setMessageType('error');
				Rublon2FactorHelper::setMessage(__('Unable to disable Rublon security.', 'rublon2factor'));
			}
		}

		$sessionData = $credentials->getSessionData();
		$this->returnToPage($sessionData);
	}
	

	private function returnToPage($sessionData = null, $url = null) {
		$returnPageUrl = (isset($url)) ? $url : Rublon2FactorHelper::getReturnPageUrl();
		if (!empty($sessionData)) {
			echo $this->htmlHelper->returnToPage($sessionData, $returnPageUrl);
		} else {
			if (empty($returnPageUrl)) {
				$returnPageUrl = '/';
			}
			header('Location: '. $returnPageUrl);
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
				$errorMessage = __('cURL functions are not available, please install appropriate library.','rublon2factor');
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
		Rublon2FactorHelper::setMessageType('error');
		Rublon2FactorHelper::setMessage($errorMessage);
		$this->returnToPage();
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
		Rublon2FactorHelper::saveReturnPageUrl();
		$button = $this->service->createButtonEnable(__('Secure your account with Rublon', 'rublon2factor'));
		echo $button;
	}
	
	/**
	 * Add Rublon insecure account button to the page code.
	 */
	public function addInsecureAccountButton() {
		Rublon2FactorHelper::saveReturnPageUrl();
		$label = __('Disable Rublon security', 'rublon2factor');
		$currentUser = wp_get_current_user();		
		$button = $this->service->createButtonDisable($label, $currentUser->rublon_profile_id);
		echo $button;
	}
	
	/**
	 * Perform authorization by Rublon2Factor.
	 */
	public function authenticateWithRublon($user) {
		Rublon2FactorHelper::saveReturnPageUrl();
		Rublon2FactorHelper::setUserToAuthenticate($user);

		$authParams = new RublonAuthParams($this->service);
		$authParams->setConsumerParam('action', RublonAuthParams::ACTION_FLAG_LOGIN);

		$this->service->initAuthorization($user->rublon_profile_id, $authParams);
	}
}
