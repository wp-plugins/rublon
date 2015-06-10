<?php

class RublonConfirmStrategy_DeleteTrashPost extends RublonConfirmStrategyButton {
	
	protected $action = 'DeleteTrashPost';
	protected $label = 'Delete post from trash';
	protected $confirmMessage = 'Do you want to delete post(s) from trash?';
	
	protected $formSelector = '#posts-filter';
	protected $buttonSelector = '#posts-filter .delete .submitdelete';
	
	protected $pageNowInit = 'edit.php';
	protected $pageNowAction = 'edit.php';
	
	
	function isThePage() {
		return (parent::isThePage() AND !empty($_GET['post_status']) AND $_GET['post_status'] == 'trash');
	}
	
	
	function isTheAction() {
		global $pagenow;
		$pages = array($this->pageNowAction, 'post.php');
		$actions = array('delete', 'delete_all');
		return (is_admin() AND !empty($pagenow) AND in_array($pagenow, $pages) AND in_array(self::getPostFilterAction(), $actions));
	}
	
	
	static function getPostFilterAction() {
		if (!empty($_GET['delete_all'])) return 'delete_all';
		else if (!empty($_GET['action']) AND $_GET['action'] != '-1') return $_GET['action'];
		else if (!empty($_GET['action2']) AND $_GET['action2'] != '-1') return $_GET['action2'];
	}
	
	
	function appendScript($selector = NULL) {
		parent::appendScript();
		echo self::getFilterScript();
	}
	
	
	static function getFilterScript() {
		return '<script type="text/javascript">//<![CDATA[
				document.addEventListener(\'DOMContentLoaded\', function() {
					jQuery("#delete_all, #delete_all2").click(function(e) {
						if (RublonSDK) {
							RublonSDK.initConfirmationForm(jQuery("#posts-filter")[0]);
						}
					});
					jQuery("#doaction, #doaction2").click(function(e) {
						var action = jQuery("#" + (this.id == "doaction" ? "bulk-action-selector-top" : "bulk-action-selector-bottom")).val();
						if (RublonSDK && action == "delete") {
							RublonSDK.initConfirmationForm(jQuery("#posts-filter")[0]);
						}
					});
				}, false);
			//]]></script>';
	}
	
	function getFallbackUrl() {
		$context = $this->getContext();
		if (!empty($context['post'])) {
			if (is_array($context['post'])) $postId = reset($context['post']);
			else $postId = $context['post'];
			if ($post = get_post($postId)) {
				return admin_url('edit.php?post_status=trash&post_type='. urlencode($post->post_type));
			}
		}
	}
	
	
}
