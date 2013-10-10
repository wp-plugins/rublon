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
function rublon2factor_init() {

	// Initialize Rublon2Factor plug-in helper
	Rublon2FactorHelper::init();

	// check for Rublon-type actions
	Rublon2FactorHelper::checkForActions();

}