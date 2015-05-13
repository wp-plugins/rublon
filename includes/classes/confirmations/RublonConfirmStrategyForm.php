<?php

abstract class RublonConfirmStrategyForm extends RublonConfirmStrategy {

	protected $formSelector = '';
	const FORM_CLASS = 'rublon-confirmation-form';
	

	function appendScript($selector = null) {
		if (empty($selector)) $selector = $this->formSelector;
		if ($this->isThePage() AND $this->isConfirmationRequired()) {
			$gui = Rublon2FactorGUIWordPress::getInstance();
			echo self::getScript($selector, self::FORM_CLASS);
		}
	}
	
	
	static function getScript($selector, $formClass) {
		return '<script type="text/javascript">//<![CDATA[
				document.addEventListener(\'DOMContentLoaded\', function() {
					var initRublonConfirmation = function() {
						jQuery('. json_encode($selector) .')
							.filter(":not(.'. $formClass .')")
							.addClass("'. $formClass .'")
							.each(function() {
								if (RublonSDK) {
									RublonSDK.initConfirmationForm(this);							         
								}
							});
					}
 					initRublonConfirmation();							    
					// Repeat initialization since the buttons can be added dynamically:
					// setInterval(initRublonConfirmation, 1000);
				}, false);
			//]]></script>';
	}
	
	
	function checkForAction() {
		if ($this->isTheAction() AND !empty($_POST)) {
			RublonConfirmations::handleConfirmation($this->getAction(), $this->getInitialContext());
		}
	}
	
	
	function getInitialContext() {
		return $_POST;
	}
	
	
	function restoreContext($data) {
		$_POST = $data;
	}
	
	
	function pluginsLoaded() {
		parent::pluginsLoaded();
		if ($this->isTheAction()) {
			if ($data = RublonConfirmations::popStoredData($this->getAction())) {
				$this->restoreContext($data['context']);
				RublonConfirmations::$dataRestored = true;
			}
		}
	}
	
	
}
