<?php

require_once dirname(__FILE__) .'/RublonConfirmStrategy.php';

add_action('plugins_loaded', array('RublonConfirmations', 'pluginsLoaded'));
add_action('admin_notices', array('RublonConfirmations', 'adminNotices'));


class RublonConfirmations {
	
	const DATA_TRANSIENT_PREFIX = 'rco_';
	const DATA_TRANSIENT_CACHE_TIME = 1800;
	
	const KEY_TRANSIENT_PREFIX = 'rcok_';
	const KEY_TRANSIENT_CACHE_TIME = 600;
	
	const PARAM_ACTION = 'confirmActionName';
	const PARAM_ACTION_URL = 'actionUrl';
	const PARAM_FALLBACK_URL = 'fallbackUrl';
	const PARAM_DATA_KEY = 'actionDataKey';
	const CUSTOM_URI_PARAM_PREFIX = 'RublonConfirmations_';
	
	const ACTION_RUBLON_CONFIRM_SETTINGS_UPDATE = 'RublonConfirmations';
	const ACTION_REDUCE_ROLE_PROTECTION_LEVEL = 'ReduceRoleProtectionLevel';
	const ACTION_SETTINGS_UPDATE = 'SettingsUpdate';
	const ACTION_USER_PROFILE_UPDATE = 'UserProfileUpdate';
	const ACTION_DELETE_TRASH_POST = 'DeleteTrashPost';
	const ACTION_SAVE_PLUGIN_FILE = 'SavePluginFile';
	const ACTION_DELETE_ADMIN = 'DeleteAdmin';
	const ACTION_THEME_CHANGE = 'ThemeChange';
	
	protected static $strategies = array(
		self::ACTION_RUBLON_CONFIRM_SETTINGS_UPDATE,
		self::ACTION_REDUCE_ROLE_PROTECTION_LEVEL,
		self::ACTION_SETTINGS_UPDATE,
		self::ACTION_USER_PROFILE_UPDATE,
		self::ACTION_DELETE_TRASH_POST,
		self::ACTION_SAVE_PLUGIN_FILE,
		self::ACTION_DELETE_ADMIN,
		self::ACTION_THEME_CHANGE,
	);
	
	static $dataRestored = false;
	
	
	static function getUIActions() {
		$actions = array();
		foreach (self::$strategies as $action) {
			if ($strategy = RublonConfirmStrategy::getStrategyInstance($action) AND $strategy->hasAdminUI()) {
				$actions[$action] = $strategy->getLabel();
			}
		}
		return $actions;
	}
	
	
	static function getSettings() {
		$default = array(self::ACTION_REDUCE_ROLE_PROTECTION_LEVEL, self::ACTION_USER_PROFILE_UPDATE);
		return (array)get_option(RublonHelper::RUBLON_CONFIRMATIONS_SETTINGS_KEY, $default);
	}
	

	
	static function handleConfirmation($action, $context, $newValue = null, $oldValue = null) {
		
		if (self::$dataRestored) return $newValue;
		
		$strategy = RublonConfirmStrategy::getStrategyInstance($action);
		$strategy->setContext($context)->setOldValue($oldValue)->setNewValue($newValue);
		
		if ($strategy->isConfirmationRequired()) {
			$url = $_SERVER['REQUEST_URI'];
			$dataKey = self::storeData($strategy);
			if ($strategy->isConfirmationNeeded()) {
				if ($dataKey) {
					self::confirm($strategy, $dataKey);
				} else {
					self::redirectParentFrame($url, $action, __('Please wait.', 'rublon'), $dataKey);
				}
			} else {
				self::redirectParentFrame($url, $action, __('Please wait.', 'rublon'), $dataKey);
			}
		}
	}
	
	
	
	static function storeData(RublonConfirmStrategy $strategy) {
		$dataKey = self::generateDataKey($strategy->getAction());
		$result = set_transient($dataKey, array(
			'context' => $strategy->getContext(),
			'new' => $strategy->getNewValue(),
			'old' => $strategy->getOldValue(),
			'url' => $_SERVER['REQUEST_URI'],
		), self::DATA_TRANSIENT_CACHE_TIME);
		if ($result) return $dataKey;
	}
	
	
	static function reloadParentFrame($action, $msg, $dataKey = null) {
		$userId = get_current_user_id();
		if ($dataKey) {
			$result = set_transient(self::getDataKeyTransientName($action, $userId), $dataKey, self::KEY_TRANSIENT_CACHE_TIME);
		}
		RublonHelper::_reloadParentFrame($msg, true);
	}
	
	
	static function redirectParentFrame($url, $action, $msg, $dataKey = null) {
		$userId = get_current_user_id();
		if ($dataKey) {
			$result = set_transient(self::getDataKeyTransientName($action, $userId), $dataKey, self::KEY_TRANSIENT_CACHE_TIME);
		}
		RublonHelper::_redirectParentFrame($url, $msg, true);
	}
	
	
	static function getDataKeyTransientName($action, $userId) {
		return self::KEY_TRANSIENT_PREFIX ."_{$action}_{$userId}";
	}
	
	
	static function popStoredData($action, $key = null) {
		$userId = get_current_user_id();
		if (empty($key)) {
			$keyTransient = self::getDataKeyTransientName($action, $userId);
			$key = get_transient($keyTransient);
			delete_transient($keyTransient);
		}
		if ($key AND $data = get_transient($key)) {
			delete_transient($key);
			return $data;
		}
	}
	
	
	
	static function confirm(RublonConfirmStrategy $strategy, $dataKey) {
		try {
			$rublon = RublonHelper::getRublon();
			$authUrl = $rublon->confirm(
				RublonHelper::getActionURL('confirm'),
				RublonHelper::getUserId(),
				RublonHelper::getUserEmail(),
				$strategy->getConfirmMessage(),
				self::getConfirmParams($strategy, $dataKey)
			);
		} catch (ForbiddenMethod_RublonAPIException $e) {
			RublonConfirmations::abortConfirmation('FORBIDDEN_METHOD');
		} catch (RublonException $e) {
// 			echo $e->getClient()->getRawRequest();exit;
			RublonHelper::_handleCallbackException($e);
			RublonConfirmations::abortConfirmation('API_ERROR');
		}		
		
		if (!empty($authUrl)) {
			wp_redirect($authUrl);
			exit;
		} else { // Why empty?
			if ($roleProtectionType == RublonHelper::PROTECTION_TYPE_MOBILE) {
				// Mobile App is required:
				RublonConfirmations::abortConfirmation('MOBILE_APP_REQUIRED');
			} else {
				// Rublon is not working at this moment or user is not protected:
				self::redirectParentFrame(
					$_SERVER['REQUEST_URI'],
					$strategy->getAction(),
					__('Please wait.', 'rublon'),
					$dataKey
				);
			}
		}
		
	}
	
	

	static public function abortConfirmation($errorMessage) {
		RublonFlashMessage::push($errorMessage, RublonFlashMessage::ERROR);
		RublonHelper::_reloadParentFrame(
			__('Operation aborted.', 'rublon'),
			true
		);
	}
	
	
	static function getConfirmParams(RublonConfirmStrategy $strategy, $dataKey) {
		
		$params = array();
		$context = $strategy->getContext();
		
		$protectionType = RublonHelper::getUserProtectionType();
		switch ($protectionType) {
			case RublonHelper::PROTECTION_TYPE_MOBILE_EVERYTIME:
				$params[RublonAuthParams::FIELD_IGNORE_TRUSTED_DEVICE] = true;
				$params[RublonAuthParams::FIELD_CAN_USE_EMAIL2FA] = false;
				break;
			case RublonHelper::PROTECTION_TYPE_MOBILE:
				$params[RublonAuthParams::FIELD_CAN_USE_EMAIL2FA] = false;
				break;
			case RublonHelper::PROTECTION_TYPE_EMAIL:
				$params[RublonAuthParams::FIELD_CAN_USE_EMAIL2FA] = true;
				break;
			default:
				$params[RublonAuthParams::FIELD_CAN_USE_EMAIL2FA] = false;
		}
		
		if ($timeBuffer = RublonFeature::getBufferedConfirmationTime()) {
			$params[RublonAuthParams::FIELD_CONFIRM_TIME_BUFFER] = $timeBuffer * 60;
		}
		
		$params[self::PARAM_ACTION] = $strategy->getAction();
		$params[self::PARAM_DATA_KEY] = $dataKey;
		$params[self::PARAM_ACTION_URL] = $_SERVER['REQUEST_URI'];
		$params[self::PARAM_FALLBACK_URL] = self::getFallbackUrl($strategy);
		if (empty($params[self::PARAM_FALLBACK_URL])) {
			$params[self::PARAM_FALLBACK_URL] = $params[self::PARAM_ACTION_URL];
		}
		$params[RublonAuthParams::FIELD_CUSTOM_URI_PARAM] = self::CUSTOM_URI_PARAM_PREFIX . $params[self::PARAM_FALLBACK_URL];
		 		
		return $params;
		
	}
	
	
	static function getFallbackUrl(RublonConfirmStrategy $strategy) {
		if ($url = $strategy->getFallbackUrl()) {
			return $url;
		} else {
			$context = $strategy->getContext();
			if (!empty($context['rublonFallbackUrl'])) {
				return $context['rublonFallbackUrl'];
			}
			else if (!empty($context['_wp_http_referer'])) {
				return $context['_wp_http_referer'];
			}
		}
	}
	
	
	static function generateDataKey($action, $userId = null) {
		if (empty($userId)) $userId = get_current_user_id();
		return self::DATA_TRANSIENT_PREFIX . md5(microtime() . $action . $userId);
	}
	
	
	static function callbackSuccess(Rublon2FactorCallback $callback) {
		if ($action = $callback->getConsumerParam(self::PARAM_ACTION)) {
			if ($strategy = RublonConfirmStrategy::getStrategyInstance($action)) {
				$strategy->callbackSuccess($callback);
			}
		}
	}
	

	static function callbackFailure(Rublon2FactorCallback $callback) {
		if (isset($_GET['custom'])) {
			$custom = $_GET['custom'];
			if (strpos($custom, self::CUSTOM_URI_PARAM_PREFIX) === 0) {
				$custom = explode(self::CUSTOM_URI_PARAM_PREFIX, $custom);
				wp_safe_redirect(end($custom));
				exit;
			}
		}
	}
	
	
	static function isConfirmationRequired($action) {
		if ($strategy = RublonConfirmStrategy::getStrategyInstance($action)) {
			return $strategy->isConfirmationRequired();
		}
	}
	
	
	
	static function isUserProtected() {
		$protection = RublonHelper::getUserProtectionType();
		return (!empty($protection) AND $protection != RublonHelper::PROTECTION_TYPE_NONE);
	}
	
	
	static function pluginsLoaded() {
		if (RublonHelper::isSiteRegistered()) {
			foreach (self::$strategies as $action) {
				if ($strategy = RublonConfirmStrategy::getStrategyInstance($action)) {
					$strategy->pluginsLoaded();
				}
			}
		}
	}
	
	
	static function adminNotices() {
		$msgs = RublonFlashMessage::pop();
		foreach ($msgs as $type => $messages) {
			foreach ($messages as $msg) {
				printf('<div class="%s"><p>%s</p></div>', $type, $msg);
			}
		}
	}
	
		
}
