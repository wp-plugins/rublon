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
function rublon2factor_plugins_loaded() {


	// Initialize Rublon2Factor plug-in helper
	RublonHelper::init();

	$attemptRegistration = RublonHelper::shouldPluginAttemptRegistration();
	if ($attemptRegistration && current_user_can('manage_options')) {
		RublonHelper::enqueueRegistration(false);
		RublonHelper::newNonce();
		RublonHelper::registerPlugin();
	}

	// check for Rublon-type actions
	RublonHelper::checkForActions();

}