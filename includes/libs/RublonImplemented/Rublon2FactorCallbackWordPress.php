<?php

class Rublon2FactorCallbackWordPress extends Rublon2FactorCallback {
	
	
	protected function handleLogout($userId, $deviceId) {

		global $wpdb;
		$sql = $wpdb->prepare(
			"SELECT user_id, meta_value FROM $wpdb->usermeta WHERE meta_key = %s", RublonHelper::RUBLON_META_DEVICE_ID .'_'. $deviceId);
		$results = $wpdb->get_results($sql, ARRAY_A);
		foreach ($results as $record) {
			$tokens = get_user_meta($record['user_id'], 'session_tokens', $single = true);
			if (!empty($tokens) AND is_array($tokens)) {
// 				$manager = WP_Session_Tokens::get_instance( $record['user_id'] );
// 				$manager->destroy($record['meta_value']);
				if (isset($tokens[$record['meta_value']])) {
					unset($tokens[$record['meta_value']]);
					update_user_meta($record['user_id'], 'session_tokens', $tokens);
				}
			}
		}
		$wpdb->query($wpdb->prepare(
			"DELETE FROM $wpdb->usermeta WHERE meta_key = %s", RublonHelper::RUBLON_META_DEVICE_ID .'_'. $deviceId));

	}
	
}
