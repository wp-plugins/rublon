<?php
/**
 * Code to be run on Rublon2Factor plug-in initialization
 *
 * @package   rublon2factor\includes
 * @author     Rublon Developers http://www.rublon.com
 * @copyright  Rublon Developers http://www.rublon.com
 * @license    http://opensource.org/licenses/gpl-license.php GNU Public License, version 2 
 */

/**
 * Initialize
 */
function rublon2factor_init ()
{
	// Initialize Rublon2Factor plug-in helper
	Rublon2FactorHelper::init();
	
	if (isset($_GET['action'])) {
		Rublon2FactorHelper::consumerRegistrationAction($_GET['action']);
	}
	
	$state = $_GET['state'];
	$token = $_GET['token'];
	$window_type = $_GET['windowType'];
	
	// Handle potential callback from Rublon server
	Rublon2FactorHelper::handleCallback($state, $token, $window_type);
}