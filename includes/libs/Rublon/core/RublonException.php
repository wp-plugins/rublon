<?php

/**
 * Exception class
 * 
 * @author Rublon Developers
 * @version 2013-07-05
 */
class RublonException extends Exception {

	const CODE_CURL_NOT_AVAILABLE = 1;
	const CODE_INVALID_RESPONSE = 2;
	const CODE_RESPONSE_ERROR = 3;
	const CODE_CURL_ERROR = 4;
	const CODE_CONNECTION_ERROR = 5;


	/**
	 * For backward compatibility: manually store previous exception
	 *
	 * @var Exception
	 */
	protected $previous = null;


	/**
	 * Constructor
	 *
	 * @param string $msg (optional)
	 * @param int $code (optional)
	 * @param Exception $prev (optional)
	 */
	public function __construct($msg = "", $code = 0, Exception $prev = null) {

		// For backward compatibility check if getPrevious() method exists
		if (method_exists($this, 'getPrevious')) {
			parent::__construct($msg, $code, $prev);
		} else {
			parent::__construct($msg, $code);
			$this->previous = $prev;
		}
	}

	/**
	 * Handler for non-existing methods
	 * 
	 * @param string $method
	 * @param array $args
	 * @return Exception
	 */
	public function __call($method, $args = array()) {
		// For backward compatibility handle non-existing method getPrevious()
		if ($method == 'getPrevious') {
			return $this->previous;
		}
	}

}

