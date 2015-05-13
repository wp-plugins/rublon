<?php

class RublonConfirmStrategy_ThemeChange extends RublonConfirmStrategyButton {
	
	protected $action = 'ThemeChange';
	protected $label = 'Change theme';
	protected $confirmMessage = 'Do you want to change the theme?';
	
	protected $pageNowInit = 'themes.php';
	protected $pageNowAction = 'themes.php';
	
	protected $buttonSelector = '.theme-actions .activate, .theme-actions .delete-theme';
	
	
	function isTheAction() {
		$actions = array('activate', 'delete');
		return (parent::isTheAction() AND !empty($_GET['action']) AND in_array($_GET['action'], $actions));
	}
	
}
