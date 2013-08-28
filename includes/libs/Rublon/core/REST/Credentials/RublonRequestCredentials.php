<?php

/**
 * REST request: Credentials
 *
 * @author Rublon Developers
 * @version 2013-08-01
 */
class RublonRequestCredentials extends RublonRequest {
	
	/**
	 * URL path of the request
	 *
	 * @var string
	 */
	protected $urlPath = '/native/rest/Credentials';
	
	/**
	 * Response object
	 * 
	 * @var RublonResponseCredentials
	 */
	protected $response = null;

	
	/**
	 * Constructor
	 * 
	 * @param RublonService $service
	 * @param string $accessToken
	 */
	public function __construct(RublonService $service, $accessToken) {
		
		parent::__construct($service);
		
		// Set request URL and parameters
		$url = $this->getConsumer()->getDomain() . $this->urlPath;
		$params = array(
			'systemToken' => $service->getConsumer()->getSystemToken(),
			'accessToken' => $accessToken,
		);
		$this->setRequestParams($url, $params);

	}


	/**
	 * Perform request and get response object
	 *
	 * @throws RublonException
	 * @return RublonResponseCredentials
	 */
	public function getResponse() {
		if (empty($this->response)) {
			$rawResponse = parent::getRawResponse();
			$this->response = new RublonResponseCredentials($this, $rawResponse);
		}
		return $this->response;
	}

}
