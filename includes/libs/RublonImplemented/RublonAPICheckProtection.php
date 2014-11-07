<?php

require_once dirname(__FILE__) . '/../Rublon/core/API/RublonAPIClient.php';

/**
 * API request: Check Rublon protection.
 *
 */
class RublonAPICheckProtection extends RublonAPIClient {


	/**
	 * Name of the field with users data.
	 */
	const FIELD_USERS = 'users';

	/**
	 * Email address also taken into account when checking protection status
	 */
	const FIELD_INCLUDING_EMAIL = 'includingEmail';
	
	/**
	 * Dummy email for ping request to check
	 * the Rublon server availability when user's email
	 * is not accessible.
	 */
	const EMAIL_PING = 'ping@rublon.com';
	
	/**
	 * URL path of the request.
	 *
	 * @var string
	 */
	protected $urlPath = '/api/v3/checkProtection';

	/**
	 * Constructor.
	 *
	 * @param Rublon $rublon
	 */
	public function __construct(RublonConsumer $rublon, $userId, $userEmail) {
	
		parent::__construct($rublon);
		
		if (!$rublon->isConfigured()) {
			trigger_error(RublonConsumer::TEMPLATE_CONFIG_ERROR, E_USER_ERROR);
		}
		
		if (empty($userEmail)) {
			$userEmail = self::EMAIL_PING;
		}

		// Set request URL and parameters
		$url = $rublon->getAPIDomain() . $this->urlPath;
		$params = array(
			RublonAuthParams::FIELD_SYSTEM_TOKEN => $rublon->getSystemToken(),
			self::FIELD_USERS => array(array(
				RublonAuthParams::FIELD_USER_ID => $userId,
				RublonAuthParams::FIELD_USER_EMAIL_HASH => hash(self::HASH_ALG, strtolower($userEmail)),
			)),
			self::FIELD_INCLUDING_EMAIL => true,
		);
		
		$this->setRequestURL($url)->setRequestParams($params);
	
	}
	
	/**
	 * Append user to check.
	 * 
	 * @param string $userId
	 * @param string $userEmail
	 * @return RublonAPICheckProtection
	 */
	public function appendUser($userId, $userEmail) {
		if (empty($userEmail)) {
			$userEmail = self::EMAIL_PING;
		}
		$this->params[self::FIELD_USERS][] = array(
			RublonAuthParams::FIELD_USER_ID => $userId,
			RublonAuthParams::FIELD_USER_EMAIL_HASH => hash(self::HASH_ALG, strtolower($userEmail)),
		);
		return $this;
	}
	

	/**
	 * Check protection status.
	 *
	 * @return boolean
	 */
	public function isProtectionEnabled($userId) {
		return (
			!empty($this->response[self::FIELD_RESULT])
			&& !empty($this->response[self::FIELD_RESULT][$userId])
		);
	}


}
