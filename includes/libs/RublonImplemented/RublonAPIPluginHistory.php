<?php

require_once dirname(__FILE__) . '/../Rublon/core/API/RublonAPIClient.php';

/**
 * API request: Plugin history.
 *
 */
class RublonAPIPluginHistory extends RublonAPIClient {


	/**
	 * History added response field. 
	 */
	const FIELD_HISTORY_ADDED = 'historyAdded';


	/**
	 * URL path of the request.
	 *
	 * @var string
	 */
	protected $urlPath = '/add_history';


	/**
	 * Constructor.
	 * 
	 * @param Rublon $rublon
	 * @param string $url
	 * @param array $params
	 */
	public function __construct(RublonConsumer $rublon, $params) {
		
		parent::__construct($rublon);
		
		if (!$rublon->isConfigured()) {
			trigger_error(RublonConsumer::TEMPLATE_CONFIG_ERROR, E_USER_ERROR);
		}

		$consumerRegistrationData = RublonHelper::getConsumerRegistrationData();
		$url = $consumerRegistrationData['url'] . $consumerRegistrationData['action'] . $this->urlPath;

		// Set request URL and parameters
		$this->setRequestURL($url)->setRequestParams($params);

	}

	/**
	 * Get secret key.
	 *
	 * @return string
	 */
	public function historyHasBeenAdded() {
		if (isset($this->response[self::FIELD_RESULT][self::FIELD_HISTORY_ADDED])) {
			return $this->response[self::FIELD_RESULT][self::FIELD_HISTORY_ADDED];
		}
	}

}
