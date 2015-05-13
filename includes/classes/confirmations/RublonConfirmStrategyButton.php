<?php

abstract class RublonConfirmStrategyButton extends RublonConfirmStrategy {

	protected $buttonSelector = '';
	const BUTTON_CLASS = 'rublon-confirmation-button';
	

	function appendScript($selector = null) {
		if (empty($selector)) $selector = $this->buttonSelector;
		if ($this->isThePage() AND $this->isConfirmationRequired()) {
			$gui = Rublon2FactorGUIWordPress::getInstance();
			echo self::getScript($selector, self::BUTTON_CLASS);
		}
	}
	
	
	static function getScript($selector, $buttonClass) {
		return '<script type="text/javascript">//<![CDATA[
				document.addEventListener(\'DOMContentLoaded\', function() {
					var initRublonConfirmation = function() {
						jQuery('. json_encode($selector) .')
							.filter(":not(.'. $buttonClass .')")
							.addClass("'. $buttonClass .'")
							.each(function() {
								if (RublonSDK) {
									RublonSDK.initConfirmationButton(this);
								}
							});
					}
 					initRublonConfirmation();
					// Repeat initialization since the buttons can be added dynamically:
					setInterval(initRublonConfirmation, 1000);
				}, false);
			//]]></script>';
	}
	
	
	function checkForAction() {
		if ($this->isTheAction()) {
			RublonConfirmations::handleConfirmation($this->getAction(), $this->getInitialContext());
		}
	}
	
	
	function getInitialContext() {
		return $_GET;
	}
	
	
	function restoreContext($data) {
		$_GET = $data;
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
	
	
	function getFallbackUrl() {
		return admin_url($this->pageNowInit);
	}
	
	
}
