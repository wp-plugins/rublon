<?php

class RublonRolesProtection {
	
	
	/**
	 * Get maximum protection type determined by the user's roles.
	 * 
	 * @param WP_User $user
	 * @return string
	 */
	static public function getUserRolesMaxProtectionType($user) {
		$types = array();
		if (!empty($user->roles) AND is_array($user->roles)) foreach ($user->roles as $role) {
			$types[] = RublonRolesProtection::getRoleProtectionType($role);
		}
		return RublonRolesProtection::getMaximumProtectionType($types);
	}
	
	
	/**
	 * Get minimum protection type for given role.
	 * If the protection type saved in settings is not available
	 * for this consumer, then get the next available lower protection type.
	 * 
	 * @param string $role
	 * @return string
	 */
	static function getRoleProtectionType($role) {
		$settings = RublonHelper::getSettings('additional');
		$role_id = RublonHelper::prepareRoleId($role);
		if (isset($settings[$role_id])) {
			return self::getMinimumProtectionType($settings[$role_id]);
		} else {
			return RublonHelper::PROTECTION_TYPE_NONE;
		}
	}
	
	
	/**
	 * Get minimum available protection type for given protection type.
	 * If the current protection type from settings is not available
	 * for this consumer, then get the next available lower protection type.
	 * If it is available, returns $current protection type.
	 * 
	 * @param string $current
	 * @return string
	 */
	static function getMinimumProtectionType($current) {
		$types = array_reverse(RublonRolesProtection::getProtectionTypes());
		$found = false;
		foreach ($types as $type) {
			if ($type == $current OR $found) {
				if (self::isProtectionTypeAvailable($type)) {
					return $type;
				} else {
					$found = true;
				}
			}
		}
		return RublonHelper::PROTECTION_TYPE_NONE;
	}
	
	
	/**
	 * Choose maximum protection type from given array.
	 * 
	 * @param array $types
	 * @return string
	 */
	static function getMaximumProtectionType(array $types) {
		$availableProtectionTypes = array_flip(RublonRolesProtection::getAvailableProtectionTypes());
		$result = RublonHelper::PROTECTION_TYPE_NONE;
		$maxTypeWeight = 0;
		foreach ($types as $type) {
			if (isset($availableProtectionTypes[$type]) AND $availableProtectionTypes[$type] > $maxTypeWeight) {
				$maxTypeWeight = $availableProtectionTypes[$type];
				$result = $type;
			}
		}
		return $result;
	}
	
	
	/**
	 * Returns protection types ordered by importance
	 * from less important to most important type.
	 * 
	 * @return array
	 */
	static function getProtectionTypes() {
		return array(
			RublonHelper::PROTECTION_TYPE_NONE,
			RublonHelper::PROTECTION_TYPE_EMAIL,
			RublonHelper::PROTECTION_TYPE_MOBILE,
			RublonHelper::PROTECTION_TYPE_MOBILE_EVERYTIME,
		);
	}
	
	
	/**
	 * Returns protection types levels map.
	 * 
	 * @return array
	 */
	static function getProtectionTypesLevels() {
		return array_flip(self::getProtectionTypes());
	}
	
	
	/**
	 * Return only available protection types.
	 * 
	 * @see RublonRolesProtection::getProtectionTypes()
	 * @return array
	 */
	static function getAvailableProtectionTypes() {
		$types = RublonRolesProtection::getProtectionTypes();
		foreach ($types as &$type) {
			if (!self::isProtectionTypeAvailable($type)) {
				$type = null;
			}
		}
		return array_filter($types);
	}
	
	
	/**
	 * Check if given protection type is available for this consumer.
	 * 
	 * @param string $type
	 * @return boolean
	 */
	static function isProtectionTypeAvailable($type) {
		$forceMobileApp = RublonFeature::checkFeature(RublonAPIGetAvailableFeatures::FEATURE_FORCE_MOBILE_APP);
		switch ($type) {
			case RublonHelper::PROTECTION_TYPE_MOBILE:
				return $forceMobileApp;
			case RublonHelper::PROTECTION_TYPE_MOBILE_EVERYTIME:
				return ($forceMobileApp AND RublonFeature::checkFeature(RublonAPIGetAvailableFeatures::FEATURE_IGNORE_TRUSTED_DEVICE));
			default:
				return true;
		}
	}
	
	
	/**
	 * Check if the priority of type is grater than other type (or equal).
	 * 
	 * @param string $type
	 * @param string $thanType
	 * @param string $orEqual (optional) Default false.
	 * @return boolean
	 */
	static function isGrater($type, $thanType, $orEqual = false) {
		$levels = self::getProtectionTypesLevels();
		if (isset($levels[$type]) AND isset($levels[$thanType])) {
			if ($orEqual) return ($levels[$type] >= $levels[$thanType]);
			else return ($levels[$type] > $levels[$thanType]);
		} else {
			return false;
		}
	}
	
	
}
