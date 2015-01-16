<?php

require_once 'RublonConsumerRegistrationCommon.php';

abstract class RublonSubprojectRegistration extends RublonConsumerRegistrationCommon {
	
	/**
	 * The register subproject action.
	 */
	const ACTION_REGISTER_SUBPROJECT = 'register_subproject';
	
	/**
	 * Field for the subproject data.
	 */
	const FIELD_SUBPROJECT = 'subproject';
	
	
	
	public function __construct() {
		parent::__construct();
	}
	
	/**
	 * Register the subproject.
	 * 
	 * @throws RublonException
	 */
	public function register() {

		// Prepare the request parameters
		$url = $this->getAPIDomain() . self::URL_PATH_ACTION . '/' . self::ACTION_REGISTER_SUBPROJECT;
		$params = array(
			RublonAuthParams::FIELD_SYSTEM_TOKEN	=> $this->getRublon()->getSystemToken(),
			self::FIELD_LIB_VERSION					=> self::VERSION,
			self::FIELD_SUBPROJECT					=> $this->getProjectData(),
		);

		try /* to connect with Rublon API and send new project's data */ {
			$response = $this->request($url, $params);
			if (isset($response[RublonAPIClient::FIELD_RESULT])) {
				return $response[RublonAPIClient::FIELD_RESULT];
			} else {
				throw new RublonException('Missing registration result.');
			}
		} catch (RublonException $e) {
			throw $e;
		}

	}


	/**
	 * Get temporary key from local storage.
	 *
	 * Returns local-stored temporary key or NULL if empty.
	 * Temporary key is used to sign communication with API
	 * instead of secret key which is not given.
	 * Note that parameter must be available for different browsers
	 * and IPs so cannot be stored in browser sesssion,
	 * but in database or configuration file.
	 *
	 * @return string
	 */
	protected function getTempKey() {
		return $this->getRublon()->getSecretKey();
	}


}
