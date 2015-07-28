<?php

/**
 * Proxy class for caching the available Rublon features.
 * 
 * If local cache is unavailable, then requesting the Rublon server
 * to get list of active features for this consumer.
 * 
 * This class is used to determine if some Rublon payed features
 * will work for this consumer. If some feature is not enabled
 * on the Rublon server, it won't work even if Wordpress performs
 * an API call. This is used only to avoid displaying disabled GUI elements.
 */
abstract class RublonFeature {
	
	/**
	 * Feature key name to override in subclass.
	 * 
	 * @var string
	 */
	const FEATURE = '';
	
	/**
	 * Number of seconds to keep the cache.
	 */
	const CACHE_EXPIRATION_SEC = 86400; // 24h
	
	const BUFFERED_CONFIRMATION_OPTION_KEY = 'buffered-confirmation-time';
	
	
	/**
	 * Check if given features is availabe for this consumer.
	 * 
	 * @param strng $name
	 * @return boolean
	 */
	static function checkFeature($name) {
		if ($features = self::getFeatures()) {
			return (!empty($features) AND is_array($features) AND !empty($features[$name]));
		} else {
			return false;
		}
	}
	
	
	/**
	 * Get list of all features with information which one is available for the consumer.
	 * 
	 * @param boolean $cached Use cached features (default: true)
	 * @return mixed|Ambigous <NULL, array>
	 */
	static function getFeatures($cached = true) {
		if (!RublonHelper::isSiteRegistered()) {
			return null;
		}
		else if ($cached AND $features = self::getFeaturesFromCache()) {
			return $features;
		} else {
			if ($features = self::getFeaturesFromServer()) {
				self::saveFeaturesInCache($features);
				return $features;
			}
		}
	}
	
	
	/**
	 * Get features list from cache.
	 * 
	 * @return array|NULL
	 */
	static function getFeaturesFromCache() {
		return get_transient(self::getTransientName());
	}
	
	
	/**
	 * Save features got from server in local cache.
	 * 
	 * @param array $features
	 * @return bool
	 */
	static function saveFeaturesInCache($features) {
		return set_transient(self::getTransientName(), $features, self::CACHE_EXPIRATION_SEC);
	}
	
	
	/**
	 * Get the features list from Rublon server.
	 * 
	 * @return NULL|array
	 */
	static function getFeaturesFromServer() {
		if (!RublonHelper::isSiteRegistered()) {
			return null;
		}
		try {
			$client = new RublonAPIGetAvailableFeatures(RublonHelper::getRublon());
			$client->perform();
			return $client->getFeatures();
		} catch (RublonException $e) {
			return null;
		}
	}
	
	
	/**
	 * Get the transient name.
	 * 
	 * @return string
	 */
	protected static function getTransientName() {
		return 'rublon_features';
	}
	
	
	/**
	 * Check if this is a Business Edition.
	 * 
	 * @return boolean
	 */
	static function isBusinessEdition() {
		return RublonFeature::checkFeature(RublonAPIGetAvailableFeatures::FEATURE_FLAG_BUSINESS_EDITION);
	}
	
	
	/**
	 * Get the buffered confirmation buffer time (minutes).
	 * 
	 * If this feature is disabled, returns 0.
	 * 
	 * @return number
	 */
	static function getBufferedConfirmationTime() {
		if (self::checkFeature(RublonAPIGetAvailableFeatures::FEATURE_BUFFERED_CONFIRMATION)) {
			return self::getBufferedConfirmationOptionValue();
		} else {
			return 0;
		}
	}
	
	
	/**
	 * Get the buffered confirmation buffer time option value (minutes).
	 * 
	 * No matter if this feature is enabled.
	 * 
	 * @return number
	 */
	static function getBufferedConfirmationOptionValue() {
		$options = get_option(RublonHelper::RUBLON_ADDITIONAL_SETTINGS_KEY);
		if (!empty($options[self::BUFFERED_CONFIRMATION_OPTION_KEY])) {
			return $options[self::BUFFERED_CONFIRMATION_OPTION_KEY];
		} else {
			return 0;
		}
	}
	
	/**
	 * Removes features list from cache.
	 *
	 * @return bool
	 */
	static function deleteFeaturesFromCache() {
	    return delete_transient(self::getTransientName());
	}
	
}
