RublonWP.pointers = {

	MENU_SELECTOR: 'a.toplevel_page_rublon',
	SETTINGS_SELECTOR: 'tr#rublon-account-security a[href$="page=rublon"]',

	clickHandler: null,

	apiReg: {

		parent: null,

		SELECTOR: 'div.wp-pointer.rublon-apireg-pointer',
		TERMS_AGREED_CHECKBOX_SELECTOR: 'input[type="checkbox"]#rublon-apireg-terms-agreed',
		REGISTRATION_FORM_SELECTOR: 'form#rublon-consumer-registration',
		NEWSLETTER_SIGNUP_CHECKBOX_SELECTOR: 'input[type="checkbox"]#rublon-apireg-newsletter-signup',
		BUTTON_ACTIVATE_SELECTOR: '',
		DISMISSED_ACTION: '',

		show: null,

		prepareHidden: function() {

			var rublonMenuButton = document.querySelector(this.parent.MENU_SELECTOR);
			var rublonSettingsButton = document.querySelector(this.parent.SETTINGS_SELECTOR);
			if ((rublonMenuButton || rublonSettingsButton) && this.show !== null) {
				var apiReg = this;
				this.parent.clickHandler = function(event) {
					event.preventDefault();
					var apiRegPointer = document.querySelector(apiReg.SELECTOR);
					if (!apiRegPointer || !RublonWPTools.visible(apiRegPointer)) {
						apiReg.show();
						apiReg.addBehaviour();
					}
				};
				if (rublonMenuButton) {
					rublonMenuButton.addEventListener('click', this.parent.clickHandler, false);
				}
				if (rublonSettingsButton) {
					rublonSettingsButton.addEventListener('click', this.parent.clickHandler, false);
				}

			}				

		},

		addBehaviour: function() {

			var checkBox = document.querySelector(this.TERMS_AGREED_CHECKBOX_SELECTOR);
			var nextButton = document.querySelector(this.BUTTON_ACTIVATE_SELECTOR);
			if (checkBox && nextButton) {
				checkBox.addEventListener('click', function(event) {
					var el = event.target;
					if (typeof el.checked != 'undefined' && el.checked) {
						RublonWPTools.removeClass(nextButton, 'disabled');
					} else {
						RublonWPTools.addClass(nextButton, 'disabled');
					}
				}, false);
				RublonWP.updateRetinaImages();
			}

		},

		answer: function(answer, dismissNonce) {

			var apiRegConfirmButton = document.querySelector(this.BUTTON_ACTIVATE_SELECTOR);
			if (answer == 'yes' && apiRegConfirmButton && !RublonWPTools.hasClass(apiRegConfirmButton, 'disabled')) {
				this.removeButtons();
				var newsletterSignupCheckbox = document.querySelector(this.NEWSLETTER_SIGNUP_CHECKBOX_SELECTOR);
				var signEmUp = (newsletterSignupCheckbox && newsletterSignupCheckbox.checked);
				var apiRegDismiss = {
					action            : this.DISMISSED_ACTION,
					newsletter_signup : signEmUp, 
					nonce             : dismissNonce
				};
				var apiReg = this;
				jQuery.post(ajaxurl, apiRegDismiss, function() {
					apiRegForm = document.querySelector(apiReg.REGISTRATION_FORM_SELECTOR);
					if (apiRegForm) {
						apiRegForm.submit();
					}
				});
			} else if (answer == 'no') {
				this.removeButtons();
				var apiRegDismiss = {
					action : this.DISMISSED_ACTION,
					nonce  : dismissNonce
				};
				var apiReg = this;
				jQuery.post(ajaxurl, apiRegDismiss, function() {
					if (apiReg.parent.clickHandler != null) {
						var rublonMenuButton = document.querySelector(apiReg.parent.MENU_SELECTOR);
						var rublonSettingsButton = document.querySelector(apiReg.parent.SETTINGS_SELECTOR);
						if (rublonMenuButton || rublonSettingsButton) {
							if (rublonMenuButton) {
								rublonMenuButton.removeEventListener('click', apiReg.parent.clickHandler, false);									
							}
							if (rublonSettingsButton) {
								rublonSettingsButton.removeEventListener('click', apiReg.parent.clickHandler, false);									
							}
							apiReg.parent.clickHandler = null;
						}
					}
					apiReg.prepareHidden();
				});
			}

		},

		removeButtons: function() {

			jQuery('.rublon-apireg-pointer #rublon-apireg-button-cancel').remove();
			jQuery('.rublon-apireg-pointer #rublon-apireg-button-activate').remove();
			jQuery('.rublon-apireg-pointer .rublon-apireg-fieldset').attr('disabled', 'disabled');
			jQuery('.rublon-apireg-pointer .wp-pointer-buttons').append('<div class="rublon-busy-spinner"></div>');

		}

	},

	annmStats: {

		parent: null,

		DISMISSED_ACTION: '',

		answer: function(answer, dismissNonce) {

			this.removeButtons();
			var annmStatsDismiss = {
				action : this.DISMISSED_ACTION,
				answer : answer,
				nonce  : dismissNonce
			};
			jQuery.post(ajaxurl, annmStatsDismiss, function() {
				window.location.reload();
			});

		},

		removeButtons: function() {

			jQuery('.rublon-annmstats-pointer #rublon-annmstats-button-cancel').remove();
			jQuery('.rublon-annmstats-pointer #rublon-annmstats-button-activate').remove();
			jQuery('.rublon-annmstats-pointer .wp-pointer-buttons').append('<div class="rublon-busy-spinner"></div>');

		}

	},

	disableClicks: function() {

		var rublonMenuButton = document.querySelector(this.MENU_SELECTOR);
		var rublonSettingsButton = document.querySelector(this.SETTINGS_SELECTOR);
		if (rublonMenuButton || rublonSettingsButton) {
			this.clickHandler = function(event) {
				event.preventDefault();
			};
			if (rublonMenuButton) {
				rublonMenuButton.addEventListener('click', this.clickHandler, false);
			}
			if (rublonSettingsButton) {
				rublonSettingsButton.addEventListener('click', this.clickHandler, false);
			}
		}

	},

	init: function() {

		this.apiReg.parent = this;
		this.annmStats.parent = this;

	}

};

RublonWP.pointers.init();