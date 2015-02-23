<?php

require_once dirname(__FILE__) . '/../Rublon/core/API/RublonAPIClient.php';

/**
 * API request: Check Rublon protection.
 *
 */
class RublonAPINewsletterSignup extends RublonAPIClient {


	/**
	 * Name of the field with the action to perform
	 */
	const FIELD_ACTION = 'newsletterAction';

	/**
	 * User email field
	 */
	const FIELD_USER_EMAIL = 'userEmail';

	/**
	 * Subscribe action
	 */
	const ACTION_SUBSCRIBE = 'subscribe';

	/**
	 * URL path of the request.
	 *
	 * @var string
	 */
	protected $urlPath = '/api/v3/newsletter';

	/**
	 * Constructor.
	 *
	 * @param Rublon $rublon
	 */
	public function __construct(RublonConsumer $rublon, $userEmail) {
	
		parent::__construct($rublon);
		
		if (!$rublon->isConfigured()) {
			trigger_error(RublonConsumer::TEMPLATE_CONFIG_ERROR, E_USER_ERROR);
		}
		
		// Set request URL and parameters
		$url = $rublon->getAPIDomain() . $this->urlPath;
		$params = array(
			RublonAuthParams::FIELD_SYSTEM_TOKEN => $rublon->getSystemToken(),
			self::FIELD_ACTION => self::ACTION_SUBSCRIBE,
			self::FIELD_USER_EMAIL => $userEmail,
		);
		
		$this->setRequestURL($url)->setRequestParams($params);
	
	}

	public function subscribedSuccessfully() {

		return !empty( $this->response[self::FIELD_STATUS] ) && $this->response[self::FIELD_STATUS] == 'OK';

	}

}
