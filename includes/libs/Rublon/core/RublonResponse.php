<?php

/**
 * REST response class.
 * 
 * This is a general REST class used for handling responses
 * from the Rublon servers via the RublonRequest class. It does
 * not differentiate the type of the request, which should be
 * implemented in any necessary descendant class.
 *
 * @see RublonRequest
 * @author Rublon Developers
 */
class RublonResponse {


	/**
	 * Response main data
	 *
	 * @property array $data
	 */
	public $data = null;


	/**
	 * Raw response data
	 *
	 * @property string $rawResponse
	 */
	public $rawResponse = null;
	
	
	/**
	 * Response object
	 * 
	 * @property RublonResponse
	 */
	public $response = null;


	/**
	 * Rublon Request class instance.
	 * 
	 * @property RublonRequest $request
	 */
	protected $request = null;


	/**
	 * Import and process the response string.
	 *
	 * @param RublonRequest $request An instance of a RublonRequest class
	 * @param string|array $rawResponse Raw response string
	 * @throws RublonException
	 */
	public function __construct(RublonRequest $request, $rawResponse) {

		$this->request = $request;
		
		$this->request->getService()->getConsumer()->log(__METHOD__);
		$this->request->getService()->getConsumer()->log(print_r($rawResponse, true));

		$this->rawResponse = $rawResponse;
		
		try {
			$this->data = RublonSignatureWrapper::parseMessage($rawResponse, $this->getConsumer()->getSecretKey());
		} catch (RublonException $e) {
			throw $e;
		}
		
	}


	/**
	 * Get all session data.
	 * 
	 * Returns the authentication process' session data array for further
	 * processing, e.g. in the RublonHTMLHelper class returnToPage method.
	 *
	 * @return array
	 */
	public function getSessionData() {
		$this->request->getService()->getConsumer()->log(__METHOD__);
		if (isset($this->data['sessionData'])) {
			return $this->data['sessionData'];
		} else return false;
	}


	/**
	 * Get consumer params from session data.
	 * 
	 * Returns the consumer params array if it has been
	 * set before the authentication process, null otherwise.
	 *
	 * @see RublonAuthParams
	 * @return array
	 */
	public function getConsumerParams() {
		$this->request->getService()->getConsumer()->log(__METHOD__);
		$sessionData = $this->getSessionData();
		if (is_array($sessionData) AND isset($sessionData['consumerParams'])) {
			return $sessionData['consumerParams'];
		} else return null;
	}


	/**
	 * Get new single-use access token from request.
	 *
	 * Returns a new single-use access token for another
	 * Rublon request.
	 *
	 * @return string|FALSE
	 */
	public function getAccessToken() {
		$this->request->getService()->getConsumer()->log(__METHOD__);
		$sessionData = $this->getSessionData();
		if (isset($sessionData['accessToken'])) {
			return $sessionData['accessToken'];
		} else return false;
	}


	
	
	/**
	 * Get request instance.
	 * 
	 * RublonRequest class property getter.
	 * 
	 * @return RublonRequest
	 */
	public function getRequest() {
		return $this->request;
	}
	
	/**
	 * Get service instance.
	 * 
	 * Returns the object's RublonRequest instance's
	 * RublonService object.
	 * 
	 * @return RublonService
	 */
	public function getService() {
		return $this->getRequest()->getService();
	}
	
	/**
	 * Get consumer instance.
	 * 
	 * Returns the object's RublonRequest instance's
	 * RublonService object's RublonConsumer class instance.
	 * 
	 * @return RublonConsumer
	 */
	public function getConsumer() {
		return $this->getService()->getConsumer();
	}
	
	

}