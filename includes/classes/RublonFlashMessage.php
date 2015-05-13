<?php

class RublonFlashMessage {
	
	const TRANSIENT_PREFIX = 'rublon_msg_';
	
	const SUCCESS = 'success';
	const ERROR = 'error';
	
	static $messages = array();
	
	
	static function init() {
		$sid = wp_get_session_token();
		self::$messages = get_transient(self::getTransientKey());
		if (!array(self::$messages)) self::$messages = array();
	}
	
	
	static function push($msg, $type = self::SUCCESS) {
		self::$messages[$type][] = $msg;
		self::save();
	}
	
	
	static function pop() {
		$result = self::$messages;
		self::$messages = array();
		delete_transient(self::getTransientKey());
		if (!is_array($result)) $result = array();
		return $result;
	}
	
	
	static function getTransientKey() {
		$sid = wp_get_session_token();
		return self::TRANSIENT_PREFIX . $sid;
	}
	
	
	static function save() {
		if (!empty(self::$messages)) {
			set_transient(self::getTransientKey(), self::$messages);
		}
	}
	
}

add_action('plugins_loaded', array('RublonFlashMessage', 'init'));