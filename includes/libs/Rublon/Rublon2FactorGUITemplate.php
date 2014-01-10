<?php

require_once 'RublonConsumer.php';
require_once 'RublonService2Factor.php';

/**
 * Template Method to create Rublon GUI elements
 *
 * You have to create a subclass which extends this abstract class
 * and implement the abstract methods. Then you can use GUI elements
 * by creating an class instance and calling its public methods.
 *
 */
abstract class Rublon2FactorGUITemplate {
	
	/**
	 * CSS class for Rublon activation button.
	 * 
	 * @var string
	 */
	const BUTTON_ACTIVATION_CLASS = 'rublon-button-activation rublon-button-label-enable';
	

	/**
	 * Instance of the Rublon service (2-factor)
	 *
	 * @var RublonService2Factor
	 */
	protected $service;
	
	
	
	/**
	 * Create and initialize consumer and service instances
	 */
	public function __construct() {
		$this->service = $this->getServiceInstance();
	}
	

	/**
	 * Construct Rublon PHP SDK library and return 2FA service instance.
	 *
	 * @return RublonService2Factor
	 */
	protected function getServiceInstance() {
		$consumer = new RublonConsumer($this->getSystemToken(), $this->getSecretKey());
		$consumer->setLang($this->getLang());
		$consumer->setDomain($this->getAPIDomain());
		$consumer->setTechnology($this->getTechnology());
		return new RublonService2Factor($consumer);
	}
	
	

	/**
	 * Create user box with button to enable/disable Rublon
	 * 
	 * @return string
	 */
	public function userBox() {
	
		// Embed the JavaScript SDK
		$result = (string)new RublonConsumerScript($this->service);
		$content = '';
		
		if ($this->isConfigured()) {
			$content = $this->createProtectionButton();
		} else {
			if ($this->canActivate()) {
				$content = $this->createActivationButton();
			}
		}
		
		$result .= $this->getUserBoxContainer($content);
		
		return $result;
	
	}
	
	
	/**
	 * Cast object into string
	 * 
	 * @return string
	 */
	public function __toString() {
		try {
			return $this->userBox();
		} catch (Exception $e) {
			return $e->getMessage();
		}
	}
	
	
	/**
	 * Create button to enable/disable Rublon protection for current user
	 * 
	 * @return RublonButton
	 */
	protected function createProtectionButton() {
		
		if ($this->isUserProtected()) { // Rublon is enabled
			// Create button to disable protection
			$button = $this->service->createButtonDisable(
				$this->getRublonProfileId(),
				$this->getConsumerParams()
			);
		} else { // Rublon is disabled
			// Create button to enable protection
			$button = $this->service->createButtonEnable($this->getConsumerParams());
		}
		
		// Set sessionId consumer parameter on the button
		if ($sessionId = $this->getSessionId()) {
			$button->getAuthParams()->setConsumerParam('sessionId', $sessionId);
		}
		
		return $button;
		
	}
	
	
	/**
	 * Returns consumer parameters for auth transaction
	 * 
	 * @return array
	 */
	protected function getConsumerParams() {
		return array();
	}
	
	
	
	/**
	 * Returns Rublon Button for plugin's activation
	 * 
	 * @return RublonButton
	 */
	protected function createActivationButton() {
		$button = new RublonButton($this->service);
		$button->setAttribute('class', self::BUTTON_ACTIVATION_CLASS);
		$button->setContent('<a href="'. htmlspecialchars($this->getActivationURL())
			.'" class="rublon-button-custom-link"></a>');
		return $button;
	}
	
	
	
	/**
	 * Get container of the user box
	 * 
	 * @param string $content HTML content
	 * @return string
	 */
	protected function getUserBoxContainer($content) {
		return '<div '.
			'class="rublon-box" '.
			'data-configured="' . ($this->isConfigured() ? '1' : '0') .'" '.
			'data-can-activate="' . ($this->canActivate() ? '1' : '0') .'" '.
			'data-account-protected="'. ($this->isUserProtected() ? '1' : '0') .'">'.
			$content .
			'</div>';
	}
	
	
	
	/**
	 * Check whether Rublon plugin is configured (the system tokend and secret key are defined)
	 * 
	 * @return boolean
	 */
	protected function isConfigured() {
		$systemToken = $this->getSystemToken();
		$secretKey = $this->getSecretKey();
		return (!empty($systemToken) AND !empty($secretKey));
	}
	
	
	/**
	 * Check whether current user is protected by Rublon
	 * 
	 * @return boolean
	 */
	protected function isUserProtected() {
		$profileId = $this->getRublonProfileId();
		return (!empty($profileId));
	}
	

	/**
	 * Return the (optional) Rublon API domain.
	 * Default domain can be replaced by testing configuration.
	 *
	 * @return string
	 */
	protected function getAPIDomain() {
		return RublonConsumer::DEFAULT_API_DOMAIN;
	}
	
	
	/**
	 * Get technology code name.
	 */
	protected function getTechnology() {
		return RublonConsumer::DEFAULT_TECHNOLOGY;
	}
	
	
	
	
	// -------------------------------------------------------------------------------------------------------
	// Methods to implement
	


	/**
	 * Get current language code.
	 * 
	 * 2-letter language code compliant with <a href="https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes">ISO 639-1</a>.
	 *
	 * @return string
	 */
	abstract protected function getLang();
	
	
	/**
	 * Get session ID or NULL if not set
	 *
	 * @return string|NULL
	*/
	abstract protected function getSessionId();
	
	
	/**
	 * Get Rublon profile ID of the user in current session or 0 if not set
	 *
	 * @return int
	*/
	abstract protected function getRublonProfileId();
	
	
	/**
	 * Retrieve consumer's systemToken or NULL if not set
	 *
	 * @return string|NULL
	*/
	abstract protected function getSystemToken();
	
	
	/**
	 * Retrieve consumer's secretKey or NULL if not set
	 *
	 * @return string|NULL
	*/
	abstract protected function getSecretKey();

	
	
	/**
	 * Check whether plugin's activation is provided and user can activate the plugin
	 * 
	 * @return boolean
	 */
	abstract protected function canActivate();
	
	
	/**
	 * Get URL of the activation process
	 * 
	 * Return NULL if the integration does not implement activation.
	 * 
	 * @return string|NULL
	 */
	abstract protected function getActivationURL();
	
	
}