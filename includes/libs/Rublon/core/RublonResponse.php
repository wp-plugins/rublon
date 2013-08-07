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
 * @version 2013-07-05
 */
class RublonResponse {


	/**
	 * Response status.
	 *
	 * @property string $status
	 */
	public $status = null;


	/**
	 * Response main data.
	 *
	 * @property array $data
	 */
	public $data = null;


	/**
	 * Raw response data.
	 *
	 * @property string $rawResponse
	 */
	public $rawResponse = null;


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
	 * @param string|array $response Request response
	 * @throws RublonException
	 */
	public function __construct(RublonRequest $request, $response) {

		$this->request = $request;
		
		$this->request->getService()->getConsumer()->log(__METHOD__);
		$this->request->getService()->getConsumer()->log(print_r($response, true));

		$this->rawResponse = $response;

		// Response may be array already
		if (is_string($response)) {
			$response = @json_decode($response, true);
		}

		if (is_array($response) AND isset($response['status'])) { // valid array
			if ($response['status'] == 'OK') { // valid status
				if (isset($response['data'])) { // data present
					if ($this->verifyResponse($response)) { // Valid signature

						// Set status field
						$this->status = $response['status'];

						// Set data field
						@ $data = json_decode($response['data'], true);
						@ $this->data = json_decode($data['body'], true);

						$this->request->getService()->getConsumer()->log(__METHOD__ .' -- success');

					} else {
						throw new RublonException(
							'Invalid response signature.',
							RublonException::CODE_INVALID_RESPONSE
						);
					}
						
				} else {
					throw new RublonException(
						'Empty response data.',
						RublonException::CODE_INVALID_RESPONSE
					);
				}

			} else {
				$msg = '('. $response['status'] .') '. (isset($response['message']) ? $response['message'] : '');
				throw new RublonException($msg, RublonException::CODE_RESPONSE_ERROR);
			}
		} else {
			throw new RublonException(
				'Invalid response.',
				RublonException::CODE_INVALID_RESPONSE
			);
		}
	}


	/**
	 * Handler for getting non-existing fields: look up in response data for given field and return value if found.
	 *
	 * @param string $name Field name.
	 * @return mixed
	 */
	public function __get($name) {
		if (isset($this->data[$name])) {
			return $this->data[$name];
		}
		else if (isset($this->$name)) {
			return $this->$name;
		} else {
			return null;
		}
	}


	/**
	 * Handler for setting non-existing fields: look up in response data for given field and set value if found
	 *
	 * @param string $name Field name
	 * @param mixed $val Value to be set.
	 */
	public function __set($name, $val) {
		if (isset($this->data[$name])) {
			$this->data[$name] = $val;
		} else $this->$name = $val;
	}


	/**
	 * Handler for checking non-existing fields: look up in response data for given field and return isset() value
	 *
	 * @param string $name Field name
	 * @return boolean
	 */
	public function __isset($name) {
		return (isset($this->data[$name]) OR isset($this->$name));
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
	 * Verify the response.
	 *
	 * Checks if the received response is properly
	 * signed by the Signature Wrapper witht the website's
	 * secret key.
	 * 
	 * @see RublonSignatureWrapper
	 * @param array $response Rublon authentication response array
	 * @return boolean
	 */
	public function verifyResponse($response) {
		if (isset($response['data']) AND isset($response['sign'])) {
			$wrapper = new RublonSignatureWrapper;
			$wrapper->setSecretKey($this->getConsumer()->getSecretKey());
			$wrapper->setInput($this->rawResponse);
			return $wrapper->verify();
		}
		return false;
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