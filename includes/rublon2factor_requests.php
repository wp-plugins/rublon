<?php
/**
 * Class for performing various requests to Rublon servers 
 *
 * @package   rublon2factor\includes
 * @author     Rublon Developers http://www.rublon.com
 * @copyright  Rublon Developers http://www.rublon.com
 * @license    http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */

class RublonRequests {


	/**
	 * Rublon2Factor instance
	 * 
	 * @var Rublon2Factor
	 */
	protected $rublon;


	/**
	 * Constructor
	 */
	public function __construct() {

		$this->rublon = RublonHelper::getRublon();

	}


	/**
	 * Check mobile app status of a single WP user
	 * 
	 * @param WP_User $user
	 * @return string RublonHelper constant
	 */
	public function checkMobileStatus(WP_User $user) {

		$user_id = RublonHelper::getUserId($user);
		$user_email = RublonHelper::getUserEmail($user);
		require_once dirname(__FILE__) . '/libs/RublonImplemented/RublonAPICheckProtection.php';
		$check = new RublonAPICheckProtection(
			$this->rublon,
			$user_id,
			$user_email
		);
		try {
			$check->perform();
		} catch (RublonException $e) {
			$check = null;
		}
		if (!empty($check) && $check->isProtectionEnabled($user_id)) {
			$mobile_user_status = RublonHelper::YES;
		} else {
			$mobile_user_status = RublonHelper::NO;
		}
		return $mobile_user_status;

	}


}