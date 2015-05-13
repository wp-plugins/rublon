<?php

class RublonConfirmStrategy_SettingsUpdate extends RublonConfirmStrategyForm {
	
	protected $action = 'SettingsUpdate';
	protected $label = 'Update Settings';
	protected $confirmMessage = 'Do you want to update settings?';
	
	protected $pageNowInit = 'options-general.php';
	protected $pageNowAction = 'options.php';
	
	protected $formSelector = 'form[action="options.php"]';
	
	
	static protected $pageNowInitPages = array('options-general.php', 'options-writing.php', 'options-reading.php', 'options-discussion.php',
		'options-media.php', 'options-permalinks.php', 'options.php', 'admin.php?page=rublon_confirmations');
// 	static protected $optionPages = array('general', 'writing', 'reading', 'discussion', 'media', 'permalinks');
	static protected $avoidPages = array('rublon2factor_confirmations_settings_group', 'rublon2factor_additional_settings_group');
	
	
	
	function isThePage() {
		global $pagenow;
		return (is_admin() AND !empty($pagenow) AND in_array($pagenow, self::$pageNowInitPages));
	}
	
	
	function checkForAction() {
		if (!empty($_POST['option_page']) AND !in_array($_POST['option_page'], self::$avoidPages)) {
			parent::checkForAction();
		}
	}
	
	
}
