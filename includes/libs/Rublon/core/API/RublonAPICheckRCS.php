<?php

require_once 'RublonAPIClient.php';

/**
 * Request to the Rublon Cache Server to check if user is a Rublon user.
 *
 */
class RublonAPICheckRCS extends RublonAPIClient {
	
	/**
	 * Primary domain to check RCS.
	 */
	const RCS_PRIMARY_DOMAIN = 'https://lo-rcs.rublon.com';
	
	/**
	 * URL path to check RCS.
	 */
	const RCS_URL_CHECK = '/check/%s';
	
	const RESULT_NOT_FOUND = 0;
	const RESULT_FOUND = 1;
	const RESULT_ERROR = 2;
	
	
	/**
	 * List of the all single RCS domains.
	 * 
	 * @var array
	 */
	protected $secondaryRCSDomains = array('https://lo-rcs.rublon.com', 'https://lo-rcs2.rublon.com');
	
	/**
	 * User's email address hash.
	 * 
	 * @var string
	 */
	protected $userEmailHash;
	
	/**
	 * System token signed by secret key using HMAC-SHA256.
	 * 
	 * @var string
	 */
	protected $systemTokenHash = null;
	
	/**
	 * RCS response result.
	 * 
	 * @var boolean
	 */
	protected $result = 2;
	
	
	/**
	 * Constructor.
	 *
	 * @param RublonConsumer $rublon
	 */
	public function __construct(RublonConsumer $rublon, $userEmail) {
	
		parent::__construct($rublon);
		
		$this->userEmailHash = hash(self::HASH_ALG, $userEmail);
		
		if ($rublon->isConfigured()) {
			$this->systemTokenHash = hash_hmac(self::HASH_ALG,
				$this->getRublon()->getSystemToken(),
				$this->getRublon()->getSecretKey()
			);
		}
		
	}
	
	/**
	 * Perform the request.
	 * 
	 * @return RublonAPICheckRCS
	 */
	public function perform() {
		
		$domains = $this->getDomains();
		foreach ($domains as $domain) {
			$this->performForDomain($domain);
			if ($this->result === self::RESULT_FOUND OR $this->result === self::RESULT_NOT_FOUND) {
				return $this;
			}
		}
		
		// No response from any domain
		$this->result = self::RESULT_ERROR;
		return $this;
		
	}
	
	
	/**
	 * Get method's result code.
	 * 
	 * @return int
	 */
	public function getResult() {
		return $this->result;
	}
	

	/**
	 * Perform RCS check request for given domain.
	 * 
	 * @param string $domain
	 */
	protected function performForDomain($domain) {
		$url = $domain . sprintf($this->getRCSPath(), $this->userEmailHash);
		if (!empty($this->systemTokenHash)) {
			$url .= '/' . $this->systemTokenHash;
		}
		$this->setRequestURL($url);
		try {
			$response = (string)$this->performRequest()->getRawResponseBody();
			if ($response == (string)self::RESULT_FOUND) {
				$this->result = self::RESULT_FOUND;
			}
			else if ($response == (string)self::RESULT_NOT_FOUND) {
				$this->result = self::RESULT_NOT_FOUND;
			} else {
				$this->result = self::RESULT_ERROR;
			}
		} catch (RublonException $e) {
			$this->getRublon()->log($e);
			$this->result = self::RESULT_ERROR;
		}
	}
	
	

	/**
	 * Check if user has been not found on the RCS.
	 * 
	 * @return boolean
	 */
	public function isUserNotFound() {
		return ($this->getResult() == self::RESULT_NOT_FOUND);
	}
	
	
	/**
	 * Get all RCS domains list.
	 *
	 * @return array
	 */
	protected function getDomains() {
		$domains = $this->secondaryRCSDomains;
		array_unshift($domains, $this->getPrimaryDomain());
		return $domains;
	}
	
	
	
	/**
	 * Get primary RCS domain.
	 *
	 * @return string
	 */
	protected function getPrimaryDomain() {
		return self::RCS_PRIMARY_DOMAIN;
	}
	
	
	/**
	 * Get URL path for RCS check method.
	 * 
	 * @return string
	 */
	protected function getRCSPath() {
		return self::RCS_URL_CHECK;
	}
	

}
