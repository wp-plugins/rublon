<?php


/**
 * REST request class
 * 
 * @author Rublon Developers
 * @version 2013-07-05
 */
class RublonRequest {

	/**
	 * Rublon service instance
	 * 
	 * @var RublonService
	 */
	protected $service = null;
	
	/**
	 * Request URL
	 *
	 * @var string
	 */
	protected $url = null;

	/**
	 * Request POST params
	 *
	 * @var array
	 */
	protected $params = array();
	
	/**
	 * Response
	 * 
	 * @var mixed
	 */
	protected $response = null;


	/**
	 * Constructor - set access parameters
	 *
	 * @param RublonService $service
	 */
	public function __construct(RublonService $service) {
		$this->service = $service;
		$this->getConsumer()->log(__METHOD__);
	}
	
	/**
	 * Set parameters of the request
	 * 
	 * @param string $url
	 * @param array $params
	 * @return RublonRequest
	 */
	public function setRequestParams($url, $params = array()) {
		$this->url = $url;
		if (!is_array($params)) $params = array();
		$this->params = $params;
		return $this;
	}
	

	/**
	 * Perform request and get response string
	 *
	 * @throws RublonException
	 * @return string
	 */
	public function getResponse() {
		$this->getConsumer()->log(__METHOD__);
		if (!empty($this->response)) {
			return $this->response;
		} else {
			try {
				return $this->_request($this->url, $this->params);
			} catch (RublonException $e) {
				throw new RublonException(
					'Connection problem.',
					RublonException::CODE_CONNECTION_ERROR,
					$e
				);
			}
		}
	}
	
	
	/**
	 * Perform HTTP request
	 *
	 * @param string $url
	 * @param array $params POST params
	 * @return string Response
	 * @throws RublonException
	 */
	protected function _request($url, $params = array()) {
		$this->getConsumer()->log(__METHOD__ .' -- '. $url);

		if (!function_exists('curl_init')) {
			throw new RublonException(
				'Fatal error: cURL functions are not available.',
				RublonException::CODE_CURL_NOT_AVAILABLE
			);
		}

		$ch = curl_init($url);
		$headers = array(
			"Content-Type: application/json; charset=utf-8",
			"Accept: application/json, text/javascript, */*; q=0.01"
		);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		if (!empty($params)) {
			curl_setopt($ch, CURLOPT_POST, true);
			$wrapper = new RublonSignatureWrapper;
			$wrapper->setSecretKey($this->getConsumer()->getSecretKey());
			$wrapper->setBody($params);
			$post = (string)$wrapper;
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
			$this->getConsumer()->log($post);
		}
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER , false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST , 0);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Rublon Consumer ('. $this->getConsumer()->getVersion() .')');
		
		$response = curl_exec($ch);
		if (curl_error($ch)) {
			throw new RublonException(curl_error($ch), RublonException::CODE_CURL_ERROR);
		}
		curl_close($ch);
		return $response;
	}
	
	
	/**
	 * Get the service instance
	 * 
	 * @return RublonService
	 */
	public function getService() {
		return $this->service;
	}
	
	/**
	 * Get consumer instance
	 * 
	 * @return RublonConsumer
	 */
	public function getConsumer() {
		return $this->getService()->getConsumer();
	}

}