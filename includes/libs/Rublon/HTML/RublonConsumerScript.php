<?php

/**
 * Class for generating script tag that embeds consumer's JavaScript library.
 * 
 * The so-called "consumer script" is an individualized JavaScript library
 * that allows the website to use Rublon JavaScript elements - usually
 * the Rublon buttons. The library searches Rublon button HTML containers
 * in the website's DOM tree and fills them with proper buttons.
 * 
 * @see RublonButton
 * @author Rublon Developers
 * @version 2013-08-01
 */
class RublonConsumerScript {
	
	/**
	 * Consumer script URL (self explanatory).
	 *
	 * @property string $URL_CONSUMER_SCRIPT
	 */
	protected $URL_CONSUMER_SCRIPT = '/native/consumer_script';
	
	
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
	 * Initialize object with RublonService instance.
	 *
	 * A RublonService class instance is required for
	 * the object to work.
	 * 
	 * @param RublonService $service An instance of the RublonService class
	 */
	public function __construct(RublonService $service) {
		$service->getConsumer()->log(__METHOD__);
		$this->service = $service;
	}
	
	/**
	 * Generate a HTML code of this object.
	 * 
	 * Returns a HTML script tag that will load the consumer
	 * script from the Rublon servers.
	 * 
	 * @return string
	 */
	public function __toString() {
		$this->getConsumer()->log(__METHOD__);
		$url = $this->getConsumerScriptURL();
		return '<script type="text/javascript" src="'. $url .'?t='. md5(microtime()) .'"></script>';
	}
	
	
	/**
	 * Get consumer's script URL.
	 * 
	 * Returns the URL address of the consumer script on
	 * the Rublon servers.
	 *
	 * @return string
	 */
	protected function getConsumerScriptURL() {
		$this->getConsumer()->log(__METHOD__);
	
		$params = array(
			'lang' => $this->getConsumer()->getLang(),
			'systemToken' => $this->getConsumer()->getSystemToken(),
			'version' => str_replace('-', '', $this->getConsumer()->getVersion()),
		);
		
		$params = urlencode(base64_encode(json_encode($params)));
		
		return $this->getConsumer()->getDomain()
			. $this->URL_CONSUMER_SCRIPT .'/'
			. $params .'/'
			. rand(1, 99999);
	
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