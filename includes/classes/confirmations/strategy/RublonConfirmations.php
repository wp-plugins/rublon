<?php

class RublonConfirmStrategy_RublonConfirmations extends RublonConfirmStrategyForm {
	
	const OPTION_PAGE = 'rublon2factor_confirmations_settings_group';
	
	protected $adminUI = false;
	
	protected $action = 'RublonConfirmations';
	protected $label = 'Update Rublon confirmations settings';
	protected $confirmMessage = 'Do you want to update Rublon confirmations settings?';
	
	protected $pageNowInit = 'admin.php?page=rublon_confirmations';
	protected $pageNowAction = 'options.php';
	
	protected $formSelector = 'form[action="options.php"]';
	
	
	
	function checkForAction() {
		if (!empty($_POST['option_page']) AND $_POST['option_page'] == self::OPTION_PAGE) {
			parent::checkForAction();
		}
	}
	
}
