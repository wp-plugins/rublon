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
 * @version 2013-07-05
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
	 * Init native service with RublonConsumer instance.
	 *
	 * @param RublonConsumer $consumer
	 */
	public function __construct(RublonConsumer $consumer) {
		$consumer->log(__METHOD__);
		$this->consumer = $consumer;
	}
	
	
	/**
	 * Get consumer instance.
	 * 
	 * @return RublonConsumer
	 */
	public function getConsumer() {
		return $this->consumer;
	}
	
	

	/**
	 * Create button instance
	 * 
	 * @param string $label Button's label
	 * @param string $actionFlag Action flag for the authentication parameters
	 * @param string $tooltipFlag Tooltip flag of the button
	 * @param array $consumerParams Other consumer parameters
	 * @return RublonButton
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