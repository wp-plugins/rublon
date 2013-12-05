<?php

/**
 * Signature wrapper for input and output data
 *
 * Body of the message is signed by the HMAC-SHA256 hash of the string formed of
 * concatenation of the consumer's secret key and the body string.
 * Body and its signature are wrapped into a JSON structure.
 *
 * To verify the input message it's necessary to compute the HMAC-SHA256 hash
 * of the consumer's secret key concatenated with the message body string
 * and compare with the signature of the message.
 * 
 * @author Rublon Developers
 */
class RublonSignatureWrapper {
	

	/**
	 * Rublon message life time
	 * 
	 * @var int
	 */
	const MESSAGE_LIFETIME = 300;
	
	/**
	 * Secret key for verifying signature
	 *
	 * @var string
	 */
	protected $secretKey = null;

	/**
	 * Body of data
	 *
	 * @var array
	 */
	protected $body = null;

	/**
	 * Raw data string
	 *
	 * @var string
	 */
	protected $rawData = null;




	/**
	 * Get object's string - JSON with signed data
	 *
	 * @return string
	 */
	public function __toString() {
		return json_encode($this->getWrapper());
	}



	/**
	 * Set raw input
	 *
	 * @param string $input
	 * @return RublonSignatureWrapper
	 */
	public function setInput($input) {
		$this->rawData = $input;
		@ $data = json_decode($input, true);
		@ $data = json_decode($data['data'], true);
		@ $this->body = json_decode($data['body'], true);
		return $this;
	}



	/**
	 * Set secret key
	 *
	 * @param string $secretKey
	 * @return RublonSignatureWrapper
	 */
	public function setSecretKey($secretKey) {
		$this->secretKey = $secretKey;
		return $this;
	}


	/**
	 * Set body of data
	 *
	 * @param array $body
	 * @return RublonSignatureWrapper
	 */
	public function setBody($body) {
		$this->body = $body;
		return $this;
	}


	/**
	 * Get body data
	 *
	 * @return array
	 */
	public function getBody() {
		return $this->body;
	}

	/**
	 * Get wrapper with data and signature generated from body
	 *
	 * @return array
	 */
	public function getWrapper() {
		return self::wrap($this->secretKey, $this->body);
	}





	// ------------------------------------------------------------------------------------------------------------------------------
	// Static methods
	// ------------------------------------------------------------------------------------------------------------------------------


	/**
	 * Verify data by signature and secret key
	 *
	 * @param mixed $data Data to sign
	 * @param string $secretKey Secret key used to create the signature
	 * @param string $sign Computed signature
	 * @return bool
	 */
	public static function verifyData($data, $secretKey, $sign) {
		$dataSign = self::signData($data, $secretKey);
		return ($dataSign == $sign);
	}




	/**
	 * Sign data by secret key
	 *
	 * @param string $data Data to sign
	 * @param string $secretKey Secret key to create the signature
	 * @return string
	 */
	public static function signData($data, $secretKey) {
		return hash_hmac('SHA256', $data, $secretKey);
	}



	/**
	 * Wrap string message into wrapper with signature
	 *
	 * @param string $secretKey Secret key used to create a signature
	 * @param string|array $body Body of the message
	 * @param array $outerParams Extra outer params for output wrapper array (default null)
	 * @return array Wrapper with signature and data fields (data is JSON with head and body fields)
	 */
	public static function wrap($secretKey, $body, $outerParams = null) {

		if (!is_string($body)) $body = json_encode($body);

		if (!empty($outerParams) AND is_array($outerParams)) {
			$data = $outerParams;
		} else {
			$data = array();
		}

		$data['head'] = array(
			'size' => strlen($body),
			'time' => time(),
		);
		$data['body'] = $body;

		$data = json_encode($data);

		return array(
			'data' => $data,
			'sign' => self::signData($data, $secretKey),
		);

	}

	

	/**
	 * Parse signed message
	 *
	 * @throws Exception
	 * @param mixed $response
	 * @param string $secretKey
	 * @return mixed
	 */
	static function parseMessage($response, $secretKey) {
		$response = json_decode($response, true);
		if (!empty($response)) {
			if (!empty($response['data']) AND !empty($response['sign'])) {
				if (RublonSignatureWrapper::verifyData($response['data'], $secretKey, $response['sign'])) {
					$data = json_decode($response['data'], true);
					if (!empty($data) AND is_array($data)) {
						if (isset($data['head']) AND is_array($data['head']) AND !empty($data['head'])) {
							$head = $data['head'];
							if (isset($head['time']) AND abs(time() - $head['time']) <= self::MESSAGE_LIFETIME) {
								if (isset($data['body']) AND is_string($data['body'])) {
									
									$body = json_decode($data['body'], true);
									if (is_array($body) AND !empty($body)) {
										return $body;
									} else {
										return $data['body'];
									}
									
								} else {
									throw new RublonException('Invalid response data (no body)');
								}
							} else {
								throw new RublonException('Invalid message time', RublonException::CODE_TIMESTAMP_ERROR);
							}
						} else {
							throw new RublonException('Invalid response data (invalid header)');
						}
					} else {
						throw new RublonException('Invalid response');
					}
				} else {
					throw new RublonException('Invalid signature');
				}
			}
			else if (!empty($response['status'])) {
				if ($response['status'] == 'ERROR') {
					throw new RublonException(isset($response['msg']) ? $response['status'] : 'Error response');
				} else {
					return $response;
				}
			} else {
				throw new RublonException('Invalid response');
			}
		} else {
			throw new RublonException('Empty response');
		}
	}
	
	





}
