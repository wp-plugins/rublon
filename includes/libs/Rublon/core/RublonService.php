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
 */
abstract class RublonService {

	/**
	 * Service name
	 *
	 * @var string
	 */
	protected $serviceName = '';
	
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
	 * Get service name
	 * 
	 * @return string
	 */
	public function getServiceName() {
		return $this->serviceName;
	}
	
	
	
	/**
	 * Create instance of the RublonAuthParams by given configuration
	 * 
	 * @param string $actionFlag Action flag
	 * @param array $params Existing instance of the RublonAuthParams to configure or consumer parameters array (optional)
	 * @return RublonAuthParams
	 */
	protected function _initAuthParams($actionFlag, $params = null) {
		if (is_object($params) AND $params instanceof RublonAuthParams) {
			$authParams = $params;
		} else {
			$authParams = new RublonAuthParams($this, $actionFlag);
			if (is_array($params) AND !empty($params)) {
				$authParams->setConsumerParams($params);
			}
		}
		
		$authParams->setActionFlag($actionFlag);
		
		return $authParams;
		
	}
	
}