<?php

class RublonConfirmStrategy_DeleteAdmin extends RublonConfirmStrategyForm {
	
	protected $action = 'DeleteAdmin';
	protected $label = 'Delete Administrator\'s account';
	protected $confirmMessage = 'Do you want to delete Administrator\'s account for user: %s?';
	
	protected $pageNowInit = 'users.php';
	protected $pageNowAction = 'users.php';
	
	protected $formSelector = '#updateusers';
	
	protected $user;
	
	
	function isThePage() {
		return (parent::isThePage() AND !empty($_GET['action']) AND $_GET['action'] == 'delete');
	}
	
	
	function isTheAction() {
		return (parent::isThePage() AND !empty($_GET['action']) AND $_GET['action'] == 'delete');
	}
	
	
	function getFallbackUrl() {
		return admin_url('users.php');
	}
	
	
	function checkChanges() {
		if (!empty($_GET['user']) AND $user = get_userdata($_GET['user'])) {
			$this->setUser($user);
			return (in_array('administrator', $user->roles) ? 1 : 0);
		} else {
			return 0;
		}
	}
	
	
	function getConfirmMessage() {
		return sprintf(parent::getConfirmMessage(), $this->getUserLogin());
	}
	
	
	function getUserLogin() {
		return $this->user->user_login;
	}
	

	function setUser($user) {
		$this->user = $user;
		return $this;
	}
	
	
}
