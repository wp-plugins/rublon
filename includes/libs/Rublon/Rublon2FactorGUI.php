<?php

require_once('Rublon2Factor.php');
require_once('core/HTML/RublonButton.php');
require_once('core/HTML/RublonConsumerScript.php');

/**
 * Class to create Rublon GUI elements.
 * 
 * To display the Rublon GUI you can just echo the class instance.
 *
 */
class Rublon2FactorGUI {
	
	/**
	 * CSS class for Rublon activation button.
	 */
	const BUTTON_ACTIVATION_CLASS = 'rublon-button-activation rublon-button-label-enable';
	
	/**
	 * CSS class for Rublon activation button.
	 */
	const BUTTON_PROTECTION_CLASS = "rublon-button-protection";
	
	/**
	 * Template for activation button's link.
	 */
	const TEMPLATE_CUSTOM_BUTTON_LINK = '<a href="%s" class="rublon-button-custom-link"></a>';
	
	/**
	 * Template for user box container.
	 */
	const TEMPLATE_BOX_CONTAINER = '<div class="rublon-box" data-configured="%d" data-can-activate="%d">%s</div>';
	
	// Device Widget CSS attributes.
	const WIDGET_CSS_FONT_COLOR = 'font-color';
	const WIDGET_CSS_FONT_SIZE = 'font-size';
	const WIDGET_CSS_FONT_FAMILY = 'font-family';
	const WIDGET_CSS_BACKGROUND_COLOR = 'background-color';
	

	/**
	 * Instance of the Rublon 2-factor service.
	 *
	 * @var Rublon2Factor
	 */
	protected $rublon;
	
	/**
	 * Current user's ID.
	 * 
	 * @var string
	 */
	protected $userId;

	/**
	 * Current user's email address.
	 *
	 * @var string
	 */
	protected $userEmail;
	
	/**
	 * Constructor.
	 * 
	 * @param Rublon2Factor $rublon Rublon instance.
	 * @param string $userId Current user's ID.
	 * @param string $userEmail Current user's email.
	 */
	public function __construct(Rublon2Factor $rublon, $userId, $userEmail) {
		$this->rublon = $rublon;
		$this->userId = $userId;
		$this->userEmail = $userEmail;
	}
	
	
	/**
	 * Create user box.
	 * 
	 * @return string
	 */
	public function userBox() {
		return $this->getConsumerScript()
				. $this->getUserBoxContainer(
					$this->getUserBoxContainerContent()
				);
	}
	
	
	/**
	 * Returns HTML code to embed consumer script.
	 * 
	 * @return string
	 */
	protected function getConsumerScript() {
		return (string)new RublonConsumerScript($this->getRublon(), $this->userId, $this->userEmail);
	}
	
	
	/**
	 * Get Rublon instance.
	 * 
	 * @return Rublon2Factor
	 */
	protected function getRublon() {
		return $this->rublon;
	}
	
	
	/**
	 * Cast object into string.
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
	 * Returns Rublon Button for plugin's activation.
	 * 
	 * @return RublonButton
	 */
	protected function createActivationButton($activationURL) {
		$button = new RublonButton($this->getRublon());
		$button->setAttribute('class', self::BUTTON_ACTIVATION_CLASS);
		$button->setContent(sprintf(self::TEMPLATE_CUSTOM_BUTTON_LINK,
			htmlspecialchars($this->getActivationURL($activationURL))));
		return $button;
	}
	
	
	/**
	 * Get container of the user box.
	 * 
	 * @param string $content HTML content
	 * @return string
	 */
	protected function getUserBoxContainer($content) {
		return sprintf(self::TEMPLATE_BOX_CONTAINER,
			(int)$this->getRublon()->isConfigured(),
			(int)$this->canUserActivate(),
			(string)$content
		)
			. $this->getDeviceWidget()
			. $this->getShareAccessWidget();
	}

	

	/**
	 * Get activation URL address, if the automatic module activation is
	 * implemented.
	 * 
	 * @return string
	 */
	public function getActivationURL() {
		return null;
	}
	
	
	/**
	 * Get iframe to load the Device Widget.
	 * 
	 * @return string
	 */
	protected function getDeviceWidget() {
		$attr = array_merge($this->getDeviceWidgetAttributes(), $this->getWidgetCSSAttribsData());
		return '<iframe '. self::createAttributesString($attr) .'></iframe>';
	}
	
	
	/**
	 * Device Widget HTML iframe attributes.
	 * 
	 * @return array
	 */
	protected function getDeviceWidgetAttributes() {
		return array(
			'id' => 'RublonDeviceWidget',
		);
	}
	
	/**
	 * Creates HTML attributes string.
	 * 
	 * @param array $attr
	 * @return string
	 */
	protected static function createAttributesString($attr) {
		$result = '';
		foreach ($attr as $name => $value) {
			$result .= ' ' . htmlspecialchars($name) .'="'. htmlspecialchars($value) .'"';
		}
		return $result;
	}
	
	
	/**
	 * Creates HTML attributes array for a widget CSS attributes.
	 * 
	 * @return array
	 */
	private function getWidgetCSSAttribsData() {
		$result = array();
		$attribs = $this->getWidgetCSSAttribs();
		foreach ($attribs as $name => $value) {
			$result['data-' . $name] = $value;
		}
		return $result;
	}
	
	
	/**
	 * Returns CSS attributes for a widget.
	 * 
	 * @return array
	 */
	protected function getWidgetCSSAttribs() {
		return array();
	}
	
	

	/**
	 * Check whether current user can perform the automatic module's activation,
	 * if implemented.
	 * 
	 * @return boolean
	 */
	protected function canUserActivate() {
		return $this->getRublon()->canUserActivate();
	}
	
	
	
	/**
	 * Get content for user box.
	 * 
	 * @return string
	 */
	protected function getUserBoxContainerContent() {
		if (!$this->getRublon()->isConfigured() AND $this->canUserActivate()) {
			if ($activationURL = $this->getActivationURL()) {
				return $this->createActivationButton($activationURL);
			}
		}
	}
	
	/**
	 * Get iframe to load the Share Access Widget.
	 *
	 * @return string
	 */
	protected function getShareAccessWidget() {
		$attr = array_merge($this->getShareAccessWidgetAttributes(), $this->getWidgetCSSAttribsData());
		return '<iframe '. self::createAttributesString($attr) .'></iframe>';
	}
	


	/**
	 * Device Widget HTML iframe attributes.
	 *
	 * @return array
	 */
	protected function getShareAccessWidgetAttributes() {
		return array(
			'id' => 'RublonShareAccessWidget',
		);
	}

	
}
