<?php

class RublonConfirmStrategy_UserProfileUpdate extends RublonConfirmStrategyForm {
	
	protected $action = 'UserProfileUpdate';
	protected $label = 'Change user\'s or own profile data';
	
	protected $formSelector = '#your-profile';
	
	protected $pageNowInit = 'user-edit.php';
	protected $pageNowAction = 'user-edit.php';
	
	protected static $pageNow = array('profile.php', 'user-edit.php');
	
	protected $confirmMessage = 'Do you want to update profile data for user: %s?';
	protected $confirmMessageOwn = 'Do you want to update your user profile?';
	
// 	protected static $confirmMessage = array(
// 		1 => 'Do you want to change email for user: %s?',
// 		2 => 'Do you want to change password for user: %s?',
// 		3 => 'Do you want to change email and password for user: %s?',
// 	);
	
	protected $user;
	
	
	function checkChanges() {
		
		return 1;
		
// 		$old = $this->getOldValue();
// 		$new = $this->getNewValue();
// 		$context = $this->getContext();
// 		$result = 0;
// 		if ($old['email'] != $new['email']) {
// 			$result += 1;
// 		}
// 		if (!empty($context['pass1'])) {
// 			$result += 2;
// 		}
// 		if ($old['rublon_user_protection_type'] == RublonHelper::PROTECTION_TYPE_EMAIL AND $new['rublon_user_protection_type'] != RublonHelper::PROTECTION_TYPE_EMAIL) {
// 			$result += 4;
// 		}
// 		return $result;
	}
	
	
	function isConfirmationRequired() {
		return (parent::isConfirmationRequired());
	}
	
	
	function getConfirmMessage() {
		if ($this->isOwnProfile()) {
			return $this->confirmMessageOwn;
		} else {
			return sprintf($this->confirmMessage, $this->getUserLogin());
		}
// 		$changes = $this->checkChanges();
// 		if ($changes > 1 AND RublonConfirmations::isConfirmationRequired(RublonConfirmations::ACTION_USER_PASSWORD_UPDATE)) {
// 			$template = self::$confirmMessage[$changes];
// 		} else {
// 			$template = self::$confirmMessage[1];
// 		}
// 		$msg = sprintf($template, $this->getUserLogin());
// 		return $msg;
	}
	
	
	
	function isOwnProfile() {
		return ($this->user->ID == get_current_user_id());
	}
	
	
	function getUserLogin() {
		return $this->user->user_login;
	}
	
	
	function setUser($user) {
		$this->user = $user;
		return $this;
	}


	function checkForAction() {
		global $pagenow;
		if ($this->isTheAction() AND !empty($_POST) AND ($pagenow == 'profile.php' OR !empty($_POST['user_id']))) {
			$userId = (empty($_POST['user_id']) ? get_current_user_id() : $_POST['user_id']);
			if ($user = get_userdata($userId)) {
				$this->setUser($user);
				$old = array(
					'email' => $user->user_email,
					RublonHelper::FIELD_USER_PROTECTION_TYPE => RublonHelper::userProtectionType($user),
				);
				$new = array(
					'email' => $_POST['email'],
					RublonHelper::FIELD_USER_PROTECTION_TYPE => (empty($_POST[RublonHelper::FIELD_USER_PROTECTION_TYPE]) ?
																null : $_POST[RublonHelper::FIELD_USER_PROTECTION_TYPE])
				);
				RublonConfirmations::handleConfirmation($this->getAction(), $_POST, $old, $new);
			}
		}
	}
	
	
	function isThePage() {
		global $pagenow;
		return (is_admin() AND !empty($pagenow) AND in_array($pagenow, self::$pageNow));
	}
	

	function isTheAction() {
		global $pagenow;
		return (is_admin() AND !empty($pagenow) AND in_array($pagenow, self::$pageNow));
	}
	
	
	function pluginsLoaded() {
		parent::pluginsLoaded();
		
		if ($this->isTheAction() AND (RublonConfirmations::$dataRestored) OR !$this->isConfirmationRequired()) {
			
			// Update user protection type
			
			$current_user = wp_get_current_user();
			if (!empty($_POST[RublonHelper::FIELD_USER_PROTECTION_TYPE])
					AND $_POST[RublonHelper::FIELD_USER_PROTECTION_TYPE] != RublonHelper::userProtectionType($current_user)) {
				RublonHelper::setUserProtectionType(
					$current_user,
					$_POST[RublonHelper::FIELD_USER_PROTECTION_TYPE]
				);
			}
			if (!empty($_POST['email']) && $_POST['email'] !== RublonHelper::getUserEmail($current_user)) {
				RublonHelper::clearMobileUserStatus($current_user);
			}
			
		}
		
	}
	
	
}
