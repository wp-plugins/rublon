<?php

class RublonConfirmStrategy_SavePluginFile extends RublonConfirmStrategyForm {
	
	protected $action = 'SavePluginFile';
	protected $label = 'Save plugin\'s file when using the Plugin Editor';
	protected $confirmMessage = 'Do you want to save plugin\'s edited file?';
	
	protected $formSelector = '#template';
	
	protected $pageNowInit = 'plugin-editor.php';
	protected $pageNowAction = 'plugin-editor.php';
	
	
}
