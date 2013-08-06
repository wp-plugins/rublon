<?php

/**
 * Signature wrapper for input and output data
 *
 * Body of the message is signed by the MD5 hash of the string formed of
 * concatenation of the consumer's secret key and the body string.
 * Body and its signature are wrapped into a JSON structure.
 *
 * To verify the input message it's necessary to compute the MD5 hash
 * of the consumer's secret key concatenated with the message body string
 * and compare with the signature of the message.
 * 
 * @author Rublon Developers
 * @version 2013-07-05
 */
class RublonSignatureWrapper {

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
	 * Verify signature of raw input data
	 *
	 * @return boolean
	 */
	public function verify() {
		@ $data = json_decode($this->rawData, true);
		if ($data AND isset($data['data']) AND isset($data['sign'])) {
			return self::verifyData($data['data'], $this->secretKey, $data['sign']);
		}
		return false;
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
	 * @param mixed $data
	 * @param string $secretKey
	 * @param string $sign
	 * @return bool
	 */
	public static function verifyData($data, $secretKey, $sign) {
		$dataSign = self::signData($data, $secretKey);
		return ($dataSign == $sign);
	}




	/**
	 * Sign data by secret key
	 *
	 * @param mixed $data
	 * @param string $secretKey
	 * @return string
	 */
	public static function signData($data, $secretKey) {

		if (!is_string($data)) {
			if (is_array($data) OR is_object($data)) {
				$data = json_encode($data);
			}
		}

		$data = $secretKey . $data;

		return md5($data);

	}



	/**
	 * Wrap string message into wrapper with signature
	 *
	 * @param string $secretKey
	 * @param mixed $body If not string - use json_encode to stringify
	 * @param array $outerParams Extra outer params for output wrapper array (default null)
	 * @return array Wrapper with signature and data fields (data is json with head and body fields)
	 */
	public static function wrap($secretKey, $body, $outerParams = null) {

		$signatureMethod = 'string-md5';

		if (!is_string($body)) $body = json_encode($body);

		if (!empty($outerParams) AND is_array($outerParams)) {
			$data = $outerParams;
		} else {
			$data = array();
		}

		$data['head'] = array(
			'size' => strlen($body),
			'time' => time(),
			'signMethod' => $signatureMethod,
		);
		$data['body'] = $body;

		$data = json_encode($data);

		return array(
			'data' => $data,
			'sign' => self::signData($data, $secretKey, $signatureMethod),
		);

	}






}
