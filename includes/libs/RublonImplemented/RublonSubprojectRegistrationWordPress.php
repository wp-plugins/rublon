<?php

require_once dirname(__FILE__) . '/../RublonConsumerRegistration/RublonSubprojectRegistration.php';

class RublonSubprojectRegistrationWordPress extends RublonSubprojectRegistration {


	const FIELD_PROJECT_DESCRIPTION = 'project_description';
	const FIELD_PROJECT_LANG = 'lang';

	/**
	 * Rublon2Factor instance
	 * 
	 * @var Rublon2Factor
	 */
	protected $rublon;


	/**
	 * Constructor
	 * 
	 * @throws RublonException
	 */
	public function __construct() {

		$rublon_config = apply_filters('rublon_get_settings', RublonHelper::getSettings()); 
		if (!empty($rublon_config['rublon_system_token']) AND !empty($rublon_config['rublon_secret_key'])) {
			parent::__construct();
			$this->rublon = new Rublon2FactorWordPress(
				$rublon_config['rublon_system_token'],
				$rublon_config['rublon_secret_key']
			);
		} else {
			throw new RublonException('Missing main site system token and secret key.');
		}

	}


	/**
	 * Get registration API domain.
	 *
	 * @return string
	 */
	public function getAPIDomain() {
	
		return RublonHelper::RUBLON_REGISTRATION_DOMAIN;
	
	}


	/**
	 * Save system token to the local storage.
	 *
	 * Not needed in subproject registration.
	 *
	 * @param string $systemToken
	 * @return bool
	 */
	protected function saveSystemToken($systemToken) {

		return true;

	}


	/**
	 * Save secret key to the local storage.
	 *
	 * Not needed in subproject registration.
	 * 
	 * @param string $systemToken
	 * @return bool
	 */
	protected function saveSecretKey($secretKey) {

		return true;		

	}


	/**
	 * Get the project's public webroot URL address.
	 *
	 * Returns the main project URL address needful
	 * for registration consumer in API.
	 *
	 * @return string
	 */
	protected function getProjectUrl() {

		return trailingslashit(site_url());		

	}
	

	/**
	 * Get the callback URL of this Rublon module.
	 *
	 * Returns public URL address of the Rublon consumer's callback script.
	 * API server calls the callback URL after valid authentication.
	 * The callback URL is needful for registration consumer in API.
	 *
	 * @return string
	 */
	protected function getCallbackUrl() {

		return RublonHelper::getLoginURL('callback');		

	}


	/**
	 * Get name of the project.
	 *
	 * Returns name of the project that will be set in Rublon Developers Dashboard.
	 *
	 * @return string
	 */
	protected function getProjectName() {

		return get_bloginfo('title');

	}


	/**
	 * Get project's technology.
	 *
	 * Returns technology, module or library name to set in project.
	 *
	 * @return string
	 */
	protected function getProjectTechnology() {

		return RublonHelper::getBlogTechnology();

	}


	/**
	 * Get Rublon2Factor class instance.
	 *
	 * @return Rublon2Factor
	 */
	protected function getRublon() {

		return $this->rublon;

	}


	/**
	 * Get current user's ID.
	 *
	 * @return string
	 */
	protected function getUserId() {

		$current_user = wp_get_current_user();
		return RublonHelper::getUserId($current_user);		

	}


	/**
	 * Get current user's email.
	 *
	 * @return string
	 */
	protected function getUserEmail() {

		$current_user = wp_get_current_user();
		return RublonHelper::getUserEmail($current_user);		

	}


	/**
	 * Get project's additional data.
	 *
	 * The data returned will be used upon consumer's registration
	 * and are required. If any additional data is needed,
	 * this method may be overwritten.
	 *
	 * @return string
	 */
	protected function getProjectData() {

		$project_data = parent::getProjectData();
		$project_data[self::FIELD_PROJECT_DESCRIPTION] = get_bloginfo('description');
		$project_data[self::FIELD_PROJECT_LANG] = RublonHelper::getBlogLanguage();
		return $project_data;

	}


}