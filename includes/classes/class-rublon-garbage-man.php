<?php
/**
 * Collects unnecessary garbage
 *
 * @class		Rublon_Garbage_Man
 * @package		rublon2factor/includes/classes
 * @category	Class
 * @author		Rublon Developers http://www.rublon.com
 * @copyright	Rublon Developers http://www.rublon.com
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2 
 */

class Rublon_Garbage_Man {

	public function collectTrash() {

		$this->removeOldTransients();

	}

	private function removeOldTransients() {

		global $wpdb;
		
		// Clean old transients
		$clean_sql = "DELETE
						a, b
					FROM
						{$wpdb->options} a, {$wpdb->options} b
					WHERE
						a.option_name LIKE '\\_transient_rublon\\_%'
						AND a.option_name NOT LIKE '\\_transient\\_timeout\\_rublon\\_%'
						AND b.option_name = CONCAT(
							'_transient_timeout_rublon_',
							SUBSTRING(
								a.option_name,
								CHAR_LENGTH('_transient_rublon_') + 1
							)
						)
						AND b.option_value < UNIX_TIMESTAMP()";

		$wpdb->query($clean_sql);

	}

}
