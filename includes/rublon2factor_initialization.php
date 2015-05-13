<?php
/**
 * Code to be run after the plugin is loaded
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

	// Initialize Rublon plugin helper
	RublonHelper::plugins_loaded();

	// Check if plugin registration should be attempted
	// in this run
	RublonHelper::checkRegistration();
	
	// check for Rublon-type actions
	RublonHelper::checkForActions();

}