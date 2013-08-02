<?php

/**
 * REST request: Credentials
 *
 * @author Rublon Developers
 * @version 2013-07-05
 */
class RublonRequestCredentials extends RublonRequest {
	
	/**
	 * URL of the request
	 *
	 * @var string
	 */
	protected $urlPath = '/native/rest/Credentials';

	
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
		if (!empty($this->response)) {
			return $this->response;
		} else {
			$response = parent::getResponse();
			$this->response = new RublonResponseCredentials($this, $response);
			return $this->response;
		}
	}

}
