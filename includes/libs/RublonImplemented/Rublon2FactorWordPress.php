<?php

require_once dirname (__FILE__) . '/../Rublon/Rublon2Factor.php';

class Rublon2FactorWordPress extends Rublon2Factor {


	public function canUserActivate() {

		return (!RublonHelper::isPluginRegistered() && current_user_can('manage_options'));

	}


	public function getLang() {

		return RublonHelper::getBlogLanguage();

	}


	public function getAPIDomain() {

		return RublonHelper::getAPIDomain();

	}


}
