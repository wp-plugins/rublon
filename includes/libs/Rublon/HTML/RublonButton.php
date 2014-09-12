<?php

/**
 * Rublon button class.
 * 
 * This class can be utilized to prepare a HTML container
 * for the Rublon buttons. The containers embedded in the website
 * will be filled with proper Rublon buttons once the consumer script
 * is executed.
 * 
 * @see RublonConsumerScript
 * @author Rublon Developers
 */
class RublonButton {
	
	
	/**
	 * Rublon Service instance.
	 * 
	 * An istance of the RublonService class or its descendant. Necessary
	 * for the class to work.
	 *
	 * @property RublonService $service
	 */
	protected $service = null;
	

	/**
	 * Authentication process parameters.
	 *
	 * An instance of the RublonAuthParams class which, if set, will be
	 * used in the button container creation. 
	 *
	 * @property RublonAuthParams $authParams
	 */
	protected $authParams = null;
	
	
	/**
	 * Label of the button.
	 * 
	 * Label displayed on the button and as its "title" attribute.
	 * 
	 * @property string $label 
	 */
	protected $label = null;
	
	/**
	 * Size of the button.
	 * 
	 * One of the predefined button size constants.
	 * 
	 * @var string $size
	 */
	protected $size = null;
	
	/**
	 * Color of the button.
	 * 
	 * One of the predefined button color constants.
	 * 
	 * @var string $color
	 */
	protected $color = null;
	
	
	/**
	 * Tooltip flag.
	 * 
	 * One of the predefined button tooltip flag constants.
	 *
	 * @var string $tooltipFlag
	 */
	protected $tooltipFlag = null;
	
	
	/**
	 * HTML attributes of the button's container.
	 * 
	 * Any additional HTML attributes that will be added to the
	 * button upon its creation, e.g. class, style, data-attributes.
	 * 
	 * @var array $attributes
	 */
	protected $attributes = array();
	
	
	/**
	 * HTML content of the button
	 * 
	 * @var string
	 */
	protected $content = '<a href="https://rublon.com/">Rublon</a>';
	
	
	// Available sizes
	const SIZE_MINI = 'mini';
	const SIZE_SMALL = 'small';
	const SIZE_MEDIUM = 'medium';
	const SIZE_LARGE = 'large';
	
	// Available colors
	const COLOR_DARK = 'dark';
	const COLOR_LIGHT = 'light';
	
	// Available tooltip flags
	const TOOLTIP_FLAG_LINK_ACCOUNTS = 'link_accounts';
	const TOOLTIP_FLAG_UNLINK_ACCOUNTS = 'unlink_accounts';
	const TOOLTIP_FLAG_CONFIRM_ACTION = 'confirm_action';
	const TOOLTIP_FLAG_LOGIN = 'login';
	
	
	/**
	 * Initialize object with RublonService instance.
	 *
	 * A RublonService class instance is required for
	 * the object to work.
	 *
	 * @param RublonService $service An instance of the RublonService class
	 * @param RublonAuthParams $authParams (optional) Authentication parameters object
	 */
	public function __construct(RublonService $service, RublonAuthParams $authParams = null) {
		
		$service->getConsumer()->log(__METHOD__);
		$this->service = $service;
		$this->setSize(self::SIZE_MEDIUM);
		$this->setColor(self::COLOR_DARK);
		
		if (is_object($authParams) AND $authParams instanceof RublonAuthParams) {
			$this->authParams = $authParams;
		} else {
			$this->authParams = new RublonAuthParams($service);
		}
		
	}
	
	
	/**
	 * Convert object into string
	 * 
	 * Returns HTML container of the button that can be
	 * embedded in the website.
	 * 
	 * @return string
	 */
	public function __toString() {
		$this->getConsumer()->log(__METHOD__);
		
		$attributes = $this->attributes;
		
		$buttonClass = 'rublon-button';
		if (isset($attributes['class'])) {
			$attributes['class'] = $buttonClass .' '. $attributes['class'];
		} else {
			$attributes['class'] = $buttonClass;
		}
		
		$attributes['class'] .= ' rublon-button-size-'. $this->getSize();
		$attributes['class'] .= ' rublon-button-color-'. $this->getColor();
		
		// Include consumer parameters
		if ($consumerParamsWrapper = $this->getConsumerParamsWrapper()) {
			$attributes['data-rublonconsumerparams'] = json_encode($consumerParamsWrapper);
		}
		
		if ($title = $this->getLabel()) {
			$attributes['title'] = $title;
		}
		
		$result = '<div';
		foreach ($attributes as $name => $val) {
			$result .= ' '. $name .'="'. htmlspecialchars($val) .'"';
		}
		$result .= '>'. $this->getContent() .'</div>';
		
		return $result;
		
	}
	
	
	/**
	 * Get HTML content of the button
	 * 
	 * @return string
	 */
	public function getContent() {
		return $this->content;
	}
	
	
	/**
	 * Set HTML content of the button
	 * 
	 * @param string $content
	 * @return RublonButton
	 */
	public function setContent($content) {
		$this->content = $content;
		return $this;
	}
	
	
	/**
	 * Get consumer parameters wrapper array.
	 * 
	 * Returns the Signature Wrapper-signed consumer params and/or
	 * the outer params if they're set in the RublonAuthParams
	 * object (if it exists) as an array, null otherwise. The tooltip flag
	 * is added as a consumer param upon invocation of this method.
	 * 
	 * @return array|NULL
	 */
	public function getConsumerParamsWrapper() {
		
		// Include the button tooltip
		if ($tooltipFlag = $this->getTooltipFlag()) {
			$this->authParams->setConsumerParam('tooltipFlag', $tooltipFlag);
		}
		
		return $this->authParams->getConsumerParamsWrapper();
	}
	
	
	/**
	 * Set label of the button.
	 * 
	 * Button label property setter.
	 * 
	 * @param string $label Text to be set as the button's label.
	 * @return RublonButton
	 */
	public function setLabel($label) {
		$this->label = $label;
		return $this;
	}
	
	/**
	 * Get label of the button.
	 * 
	 * Button label property getter.
	 * 
	 * @return string
	 */
	public function getLabel() {
		return $this->label;
	}
	
	
	/**
	 * Set size of the button.
	 * 
	 * Button size property setter.
	 * Get available size from RublonButton::SIZE_... constant.
	 * 
	 * @param string $size One of the button size constants.
	 * @return RublonButton
	 */
	public function setSize($size) {
		$this->size = $size;
		return $this;
	}
	
	/**
	 * Get size of the button.
	 * 
	 * Button size property getter. 
	 * 
	 * @return string
	 */
	public function getSize() {
		return $this->size;
	}
	
	
	/**
	 * Set color of the button.
	 *
	 * Button color property setter.
	 * Get available color from RublonButton::COLOR_... constant.
	 *
	 * @param string $color One of the button color constants.
	 * @return RublonButton
	 */
	public function setColor($color) {
		$this->color = $color;
		return $this;
	}
	
	
	/**
	 * Get color of the button.
	 * 
	 * Button color property getter.
	 * 
	 * @return string
	 */
	public function getColor() {
		return $this->color;
	}
	
	
	
	/**
	 * Set tooltip flag of the button.
	 *
	 * Button tooltip flag setter.
	 * Get available flags from RublonButton::TOOLTIP_FLAG_... constant.
	 *
	 * @param string $tooltipFlag One of the button tooltip flag constants.
	 * @return RublonButton
	 */
	public function setTooltipFlag($tooltipFlag) {
		$this->tooltipFlag = $tooltipFlag;
		return $this;
	}
	
	
	/**
	 * Get tooltip flag of the button.
	 * 
	 * Button tooltip flag getter.
	 *
	 * @return string
	 */
	public function getTooltipFlag() {
		return $this->tooltipFlag;
	}
	
	
	/**
	 * Get authentication parameters object.
	 * 
	 * Returns the buttons RublonAuthParams object.
	 *
	 * @return RublonAuthParams
	 */
	public function getAuthParams() {
		return $this->authParams;
	}
	
	
	/**
	 * Set HTML attribute of the button's container.
	 * 
	 * Add a single HTML attribute to the button's container.
	 * 
	 * @param string $name Attribute's name
	 * @param string $value Attribute's value
	 * @return RublonButton
	 */
	public function setAttribute($name, $value) {
		$this->attributes[$name] = $value;
		return $this;
	}
	
	
	/**
	 * Get HTML attribute of the button's container.
	 * 
	 * Returns the button's container single HTML attribute.
	 * Null if the attribute doesn't exist.
	 * 
	 * @param string $name Attribute's name
	 * @return string|NULL
	 */
	public function getAttribute($name) {
		if (isset($this->attributes[$name])) {
			return $this->attributes[$name];
		} else {
			return null;
		}
	}
	

	/**
	 * Get service instance.
	 * 
	 * Returns the object's instance of the RublonService class.
	 *
	 * @return RublonService
	 */
	public function getService() {
		return $this->service;
	}
	
	/**
	 * Get Rublon Consumer instance.
	 * 
	 * Returns the RublonConsumer class instance used in the creation
	 * of this class' RublonService class instance.
	 *
	 * @return RublonConsumer
	 */
	public function getConsumer() {
		return $this->getService()->getConsumer();
	}
	
	
}