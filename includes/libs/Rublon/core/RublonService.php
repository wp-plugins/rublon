<?php

require_once 'RublonRequest.php';
require_once 'RublonResponse.php';

/**
 * Abstract class for Rublon services.
 * 
 * Class defines common interface for given Rublon service instance.
 * 
 * @abstract
 * @author Rublon Developers
 * @version 2013-08-01
 */
abstract class RublonService {

	/**
	 * Service name
	 *
	 * @var string
	 */
	protected $service = '';
	
	/**
	 * Rublon Consumer instance.
	 *
	 * @var RublonConsumer
	 */
	protected $consumer = null;
	
	/**
	 * Cached credentials
	 * 
	 * @property array
	 */
	protected $cacheCredentials = array();
	
	
	/**
	 * Init service with RublonConsumer instance.
	 *
	 * @param RublonConsumer $consumer
	 */
	public function __construct(RublonConsumer $consumer) {
		$consumer->log(__METHOD__);
		$this->consumer = $consumer;
	}
	
	
	/**
	 * Get consumer instance
	 * 
	 * @return RublonConsumer
	 */
	public function getConsumer() {
		return $this->consumer;
	}
	
	

	/**
	 * Create a HTML button instance specific for this service
	 * 
	 * Returns instance of the RublonButton class that provides the HTML button.
	 * 
	 * @param string $label Button's label
	 * @param string $actionFlag Action flag for the authentication parameters, see const RublonAuthParams::ACTION_FLAG_...
	 * @param string $tooltipFlag Tooltip flag of the button, see const RublonButton::TOOLTIP_FLAG_...
	 * @param array $consumerParams Consumer parameters
	 * @return RublonButton instance that provides the HTML button.
	 */
	protected function _createButton($label, $actionFlag, $tooltipFlag, $consumerParams = array()) {
		
		$authParams = new RublonAuthParams($this);
		if (!empty($consumerParams)) {
			$authParams->setConsumerParams($consumerParams);
		}
		$authParams->setConsumerParam('actionFlag', $actionFlag);
		$authParams->setConsumerParam('action', $actionFlag);
		if ($this->service) {
			$authParams->setConsumerParam('service', $this->service);
		}
		
		$button = new RublonButton($this, $authParams);
		$button->setTooltipFlag($tooltipFlag);
		$button->setLabel($label);
		
		return $button;
		
	}
	
}