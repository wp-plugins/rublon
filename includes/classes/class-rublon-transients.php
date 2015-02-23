<?php
/**
 * Stores transient values manually
 *
 * @class		Rublon_Transients
 * @package		rublon2factor/includes/classes
 * @category	Class
 * @author		Rublon Developers http://www.rublon.com
 * @copyright	Rublon Developers http://www.rublon.com
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2 
 */

class Rublon_Transients {

	const DATA_KEY = 'data';
	const EXPIRES_KEY = 'expires';

	static public function setTransient( $key, $value, $expiration = 0 ) {

		$expiration = (int) $expiration;

		if ( empty( $expiration ) ) {
			$expiration = 15 * MINUTE_IN_SECONDS;
		}

		$transient_settings = RublonHelper::getSettings( 'transient' );
		$transient_settings[$key] = array(
			self::DATA_KEY => $value,
			self::EXPIRES_KEY => time() + $expiration
		); 
		RublonHelper::saveSettings( $transient_settings, 'transient' );

	}

	static public function getTransient( $key ) {

		$transient_settings = RublonHelper::getSettings( 'transient' );
		if ( !empty( $transient_settings[$key] ) ) {
			if ( (int) $transient_settings[$key][self::EXPIRES_KEY] < time() ) {
				self::deleteTransient( $key );
				return false;
			} else {
				return $transient_settings[$key][self::DATA_KEY];
			}
		} else {
			return false;
		}

	}

	static public function deleteTransient( $key ) {

		$transient_settings = RublonHelper::getSettings( 'transient' );
		if ( !empty( $transient_settings[$key] ) ) {
			unset( $transient_settings[$key] );
			RublonHelper::saveSettings( $transient_settings , 'transient' );
		}

	}

}
