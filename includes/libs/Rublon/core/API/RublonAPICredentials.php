<?php

require_once 'RublonAPIClient.php';

/**
 * API request: Credentials.
 *
 */
class RublonAPICredentials extends RublonAPIClient {
	
	/**
	 * Invalid access token error.
	 */
	const ERROR_ACCESS_TOKEN = 'Invalid access token.';
	
	/**
	 * Field name with the additional confirmation result.
	 */
	const FIELD_CONFIRM_RESULT = 'answer';
	
	/**
	 * User pressed the "Yes" button on the additional confirmation of the transaction.
	 */
	const CONFIRM_RESULT_YES = 'true';
	
	/**
	 * User pressed the "No" button on the additional confirmation of the transaction.
	 */
	const CONFIRM_RESULT_NO = 'false';
	
	/**
	 * URL path of the request.
	 *
	 * @var string
	 */
	protected $urlPath = '/api/v3/credentials';

	
	/**
	 * Constructor.
	 * 
	 * @param RublonConsumer $rublon
	 * @param string $accessToken
	 */
	public function __construct(RublonConsumer $rublon, $accessToken) {
		
		parent::__construct($rublon);
		
		if (!$rublon->isConfigured()) {
			trigger_error(RublonConsumer::TEMPLATE_CONFIG_ERROR, E_USER_ERROR);
		}
		if (!preg_match('/[a-z0-9]{100}/i', $accessToken)) {
			throw new RublonException(self::ERROR_ACCESS_TOKEN, RublonException::CODE_INVALID_ACCESS_TOKEN);
		}
		
		// Set request URL and parameters
		$url = $rublon->getAPIDomain() . $this->urlPath;
		$params = array(
			self::FIELD_SYSTEM_TOKEN => $rublon->getSystemToken(),
			self::FIELD_ACCESS_TOKEN => $accessToken,
		);
		$this->setRequestURL($url)->setRequestParams($params);

	}
	
	/**
	 * Get local user's ID.
	 *
	 * @return string
	 */
	public function getUserId() {
		if (isset($this->response[self::FIELD_RESULT][self::FIELD_USER_ID])) {
			return $this->response[self::FIELD_RESULT][self::FIELD_USER_ID];
		}
	}
	
	
	/**
	 * Get the additional confirmation of the transaction result.
	 * 
	 * Method returns a result only if the Rublon2Factor::confirm()
	 * method was used to initialize the auth transaction.
	 *
	 * @return string
	 * @see Rublon2Factor::confirm()
	 * @see RublonAPICredentials::CONFIRM_RESULT_YES
	 * @see RublonAPICredentials::CONFIRM_RESULT_NO
	 */
	public function getConfirmResult() {
		if (isset($this->response[self::FIELD_RESULT][self::FIELD_CONFIRM_RESULT])) {
			return $this->response[self::FIELD_RESULT][self::FIELD_CONFIRM_RESULT];
		}
	}


}
