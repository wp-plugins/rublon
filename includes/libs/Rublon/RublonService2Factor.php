<?php

require_once 'core/RublonService.php';
require_once 'core/REST/Credentials/RublonRequestCredentials.php';
require_once 'core/REST/Credentials/RublonResponseCredentials.php';

require_once 'HTML/RublonButton.php';


/**
 * Class provides methods used by `Rublon Two Factor` service process.
 *
 * @author Rublon Developers
 * @version 2013-07-05
 */
class RublonService2Factor extends RublonService {
	
	/**
	 * Service name
	 * 
	 * @var string
	 */
	protected $service = '2factor';
	
	
	/**
	 * Init authorization for user with given Rublon profile ID.
	 * 
	 * Method performs an HTTP redirection for the Rublon authorization process.
	 * 
	 * @param int $profileId Rublon user's profile ID
	 * @param RublonAuthParams $authParams
	 * @return void
	 */
	public function initAuthorization($profileId, RublonAuthParams $authParams = null) {
		
		$this->consumer->log(__METHOD__);
		
		if (empty($authParams)) {
			$authParams = new RublonAuthParams($this);
		}
		
		$authParams->setConsumerParam('requireProfileId', $profileId);
		$authParams->setConsumerParam('service', '2factor');
		$url = $authParams->getUrl();
		
		header('Location: '. $url);
		exit;
		
	}
	
	
	
	/**
	 * Authenticate user using one-time use access token and expected user's profile ID.
	 *
	 * One-time use access token is a session identifier which will be deleted after first usage.
	 * This method can be called only once in authentication process.
	 *
	 * @param string $accessToken One-time use access token
	 * @return RublonResponseCredentials
	 * @throws Exception
	 */
	public function getCredentials($accessToken) {
		if (isset($this->cacheCredentials[$accessToken])) {
			$this->consumer->log('return cached credentials');
			return $this->cacheCredentials[$accessToken];
		} else {
			$request = new RublonRequestCredentials($this, $accessToken);
			$response = $request->getResponse();
			$this->cacheCredentials[$accessToken] = $response;
			return $response;
		}
	}
	
	
	
	/**
	 * Create instance of button to enable 2FA for user's account (Secure account button).
	 * 
	 * If you have to change any authentication parameters for the ready-made button get the RublonAuthParams reference by using:
	 * 
	 * <code>$authParams = $button->getAuthParams()</code>
	 * 
	 * Then utilize the object's method, for example:
	 * 
	 * <code>$authParams->setActionFlag('other');</code>
	 * 
	 * @param string $label Label of the button
	 * @return RublonButton
	 */
	public function createButtonEnable($label) {
		return $this->_createButton(
			$label,
			RublonAuthParams::ACTION_FLAG_LINK_ACCOUNTS,
			RublonButton::TOOLTIP_FLAG_LINK_ACCOUNTS
		);
	}
	
	
	/**
	 * Create instance of button to disable 2FA for user's account (Disable account security button).
	 * 
	 * If you have to change any authentication parameters for the ready-made button get the RublonAuthParams reference by using:
	 * 
	 * <code>$authParams = $button->getAuthParams()</code>
	 * 
	 * Then utilize the object's method, for example:
	 * 
	 * <code>$authParams->setActionFlag('other');</code>
	 * 
	 * @param string $label Label of the button
	 * @param int $requireProfileId Require to authenticate by user with given profile ID
	 * @return RublonButton
	 */
	public function createButtonDisable($label, $requireProfileId) {
		return $this->_createButton(
			$label,
			RublonAuthParams::ACTION_FLAG_UNLINK_ACCOUNTS,
			RublonButton::TOOLTIP_FLAG_UNLINK_ACCOUNTS,
			array('requireProfileId' => $requireProfileId)
		);
	}
	
	
	
	
}