var RublonWP = {

	profileForm: null,
	submitHandle: null,
	roles: null,
	lang: null,

	findProfileForm: function() {
		var forms = document.getElementsByTagName('form');
		if (forms) {
			var foundForms = [];
			for (var i = 0; i < forms.length; i++) {
				// There should be only one profile form, but let's be safe.
				if (forms[i].id && forms[i].id == 'your-profile') {
					foundForms.push(forms[i]);
				}
			}
			if (foundForms.length) {
				// Form(s) found, return the first one.
				return foundForms.pop();
			} else {
				return null;
			}
		}
	},

	setUpFormSubmitListener: function() {

		var profileForm = this.findProfileForm();
		if (profileForm) {
			RublonWPTools.addClass(profileForm, 'rublon-confirmation-form');
			this.profileForm = profileForm;
/*			var _that = this;
			this.submitHandle = function(event) {
				_that.prepareRublonIframe();
			}
			profileForm.addEventListener('submit', this.submitHandle, false);*/
		}

	},

	removeFormSubmitListener: function() {

		if (this.profileForm) {
			RublonWPTools.removeClass(this.profileForm, 'rublon-confirmation-form');
//			this.profileForm.setAttribute('target', '');
/*			if (this.submitHandle) {
				this.profileForm.removeEventListener('submit', this.submitHandle, false);
			}*/
		}

	},

	prepareRublonIframe: function() {

		if (this.profileForm) {

			// Prepare the overlay and iframe
			var overlay = this.setUpOverlay();
			overlay.style.display = 'block';

			var iframeObject = this.setUpIframe(overlay);
			iframeObject.container.style.display = 'block';
			iframeObject.iframe.contentDocument.body.className = 'rublon-busy-body';

			var rublonBusyStylesTemplate = document.getElementById('rublon-busy-styles');
			var rublonBusyStyles = rublonBusyStylesTemplate.cloneNode(true);
			iframeObject.iframe.contentDocument.body.appendChild(rublonBusyStyles);

			var rublonBusyWrapperTemplate = document.getElementById('rublon-busy-profile');
			var rublonBusyWrapper = rublonBusyWrapperTemplate.cloneNode(true);
			iframeObject.iframe.contentDocument.body.appendChild(rublonBusyWrapper);
			rublonBusyWrapper.style.display = 'block';

			var rublonBusyClose = document.getElementById('rublon-busy-iframe-close');
			if (rublonBusyClose) {
				rublonBusyClose.style.display = 'block';
			}

			// Prepare the iframe for changing style
			// of a possible WordPress error page.
			iframeObject.iframe.addEventListener('load', function() {
				try {
					if (iframeObject.iframe.contentDocument) {
						var errorPage = iframeObject.iframe.contentDocument.getElementById('error-page');
						if (errorPage && errorPage.style) {
							errorPage.style.marginLeft = errorPage.style.marginRight = '20px';
							errorPage.style.borderRadius = '6px';
						}
					}
				} catch (e) {
					// Nothing, just in case there's a CDP-violation.
				}
			});

			// Change the form's target to the iframe
			this.profileForm.setAttribute('target', 'rublon-busy-iframe');

		}

	},

	setUpOverlay: function() {

		var overlay = document.createElement('div');
		overlay.id = 'rublon-busy-overlay';
		document.body.appendChild(overlay);
		return overlay;

	},

	setUpIframe: function() {

		// iframe
		var iframe = document.createElement('iframe');
		iframe.id = 'rublon-busy-iframe';
		iframe.name = 'rublon-busy-iframe';
		iframe.setAttribute('scrolling', 'no');

		// Container
		var iframeContainer = document.createElement('div');
		iframeContainer.appendChild(iframe);
		iframeContainer.id = 'rublon-busy-iframe-container';
		document.body.appendChild(iframeContainer);

		// Close button
		var iframeClose = document.createElement('div');
		var iframeCloseText = this.lang.closeButton;
		iframeClose.appendChild(document.createTextNode(iframeCloseText));
		iframeClose.id = 'rublon-busy-iframe-close';
		iframeClose.className = 'rublon-busy-iframe-close';
		var _that = this;
		iframeClose.addEventListener('click', function() {
			_that.resetProcess();
		}, false);
		iframeContainer.appendChild(iframeClose);

		return {
			iframe: iframe,
			container: iframeContainer
		};

	},

	resetProcess: function() {

		var iframe = document.getElementById('rublon-busy-iframe');
		RublonWPTools.remove(iframe);
		var iframeContainer = document.getElementById('rublon-busy-iframe-container');
		RublonWPTools.remove(iframeContainer);
		var overlay = document.getElementById('rublon-busy-overlay');
		RublonWPTools.remove(overlay);
		this.removeFormSubmitListener();
		this.setUpFormSubmitListener();
		

	},

	submitForm: function() {

		if (this.profileForm) {
			var submitButtons = this.profileForm.getElementsByClassName('rublon-profile-submit-button');
			if (submitButtons && submitButtons.length) {
				submitButtons[0].click();
			} else {
				var button = document.createElement('button');
				button.setAttribute('name', 'RublonSubmitFormButton');
				button.setAttribute('type', 'submit');
				button.className = 'rublon-profile-submit-button';
				button.style.display = 'none';
				this.profileForm.appendChild(button);
				var _that = this;
				var waitForIt = setInterval(function() {
					var submitButtons = _that.profileForm.getElementsByClassName('rublon-profile-submit-button');
					if (submitButtons && submitButtons.length) {
						clearInterval(waitForIt);
						submitButtons[0].click();
					}
				}, 100);
			}
		}

	},

	reloadPage: function() {

		window.location.reload();

	},

	addPUToken: function(tokenValue) {

		if (this.profileForm) {
			var tokenInputs = this.profileForm.getElementsByClassName('rublon-profile-update-token');
			if (tokenInputs) {
				for (var i = 0; i < tokenInputs.length; i++) {
					tokenInputs[i].parentNode.removeChild(tokenInputs[i]);
				}
			}
			var tokenInput = document.createElement('input');
			tokenInput.setAttribute('type', 'hidden');
			tokenInput.setAttribute('name', 'rublon_profile_update_token');
			tokenInput.setAttribute('id', 'rublon_profile_update_token');
			tokenInput.className = 'rublon-profile-update-token';
			tokenInput.setAttribute('value', tokenValue);
			this.profileForm.appendChild(tokenInput);
		}

	},

	setUpXMLRPCChangeListener: function() {

		var xmlRpcSelect = document.getElementById('rublon-xmlrpc-dropdown');
		if (xmlRpcSelect) {
			xmlRpcSelect.addEventListener('change', function(event) {
				if (event.target.value) {
					var locked = document.querySelector('label.rublon-label-xmlrpc .rublon-locked-container.rublon-xmlrpc-locked');
					var unlocked = document.querySelector('label.rublon-label-xmlrpc .rublon-unlocked-container.rublon-xmlrpc-unlocked');
					switch (event.target.value) {
						case 'off': {
							if (locked) {
								RublonWPTools.hide(locked);
							}
							if (unlocked) {
								RublonWPTools.show(unlocked);
							}
							break;
						}
						case 'on': {
							if (locked) {
								RublonWPTools.show(locked);
							}
							if (unlocked) {
								RublonWPTools.hide(unlocked);
							}
							break;
						}
					}
				}
			}, false);
		}

	},

	setUpRoleProtectionTypeChangeListener: function() {

		if (this.roles) {
			var roles = this.roles;
			for (var i = 0; i < roles.length; i++) {
				var roleSelect = document.getElementById('rublon-role-' + roles[i] + '-dropdown');
				if (roleSelect) {
					roleSelect.addEventListener('change', function(event) {
						if (event.target.value) {
							console.log(event.target.id);
							var role = event.target.id.replace(/rublon-role-([a-z\-]+)-dropdown/, '$1');
							var locked1 = document.querySelector('label.rublon-label-' + role + ' .rublon-locked-container.rublon-' + role + '-locked');
							var locked2 = document.querySelector('label.rublon-label-' + role + ' .rublon-locked-container.rublon-' + role + '-locked2');
							var unlocked = document.querySelector('label.rublon-label-' + role + ' .rublon-unlocked-container.rublon-' + role + '-unlocked');
							switch (event.target.value) {
								case 'mobile': {
									if (locked1) {
										RublonWPTools.show(locked1);
									}
									if (locked2) {
										RublonWPTools.show(locked2);
									}
									if (unlocked) {
										RublonWPTools.hide(unlocked);
									}
									break;
								}
								case 'email': {
									if (locked1) {
										RublonWPTools.show(locked1);
									}
									if (locked2) {
										RublonWPTools.hide(locked2);
									}
									if (unlocked) {
										RublonWPTools.hide(unlocked);
									}
									break;
								}
								case 'none': {
									if (locked1) {
										RublonWPTools.hide(locked1);
									}
									if (locked2) {
										RublonWPTools.hide(locked2);
									}
									if (unlocked) {
										RublonWPTools.show(unlocked);
									}
									break;
								}
							}
						}
					}, false);
				}
			}
		}
	},

	setUpUserProtectionTypeChangeListener: function() {

		var userProtectionTypeSelect = document.getElementById('rublon-userprotectiontype-dropdown');
		if (userProtectionTypeSelect) {
			userProtectionTypeSelect.addEventListener('change', function(event) {
				if (event.target.value) {
					var locked = document.querySelector('label.rublon-label-userprotectiontype .rublon-locked-container.rublon-userprotectiontype-locked');
					var unlocked = document.querySelector('label.rublon-label-userprotectiontype .rublon-unlocked-container.rublon-userprotectiontype-unlocked');
					switch (event.target.value) {
						case 'none': {
							if (locked) {
								RublonWPTools.hide(locked);
							}
							if (unlocked) {
								RublonWPTools.show(unlocked);
							}
							break;
						}
						case 'email': {
							if (locked) {
								RublonWPTools.show(locked);
							}
							if (unlocked) {
								RublonWPTools.hide(unlocked);
							}
							break;
						}
					}
				}
			}, false);
		}

	}

};

var RublonWPTools = {

	addClass: function(element, classToAdd) {

		if (element && typeof element.className != 'undefined') {
			if (classToAdd != '') {
				classToAdd = classToAdd.toLowerCase();
				var classes = element.className.split(' ');
				var newClasses = [];
				var classFound = false;
				for (var i = 0; i < classes.length; i++) {
					var currentClass = classes[i].toLowerCase();
					if (currentClass != '') {
						newClasses.push(currentClass);
					}
					if (classToAdd === currentClass) {
						classFound = true;
					}
				}
				if (!classFound) {
					newClasses.push(classToAdd);
					element.className = newClasses.join(' ');
				}
			}
		}

	},

	removeClass: function(element, classToRemove) {

		if (element && element.className && classToRemove != '') {
			classToRemove = classToRemove.toLowerCase();
			var classes = element.className.split(' ');
			var newClasses = [];
			for (var i = 0; i < classes.length; i++) {
				var currentClass = classes[i].toLowerCase();
				if (currentClass != '' && currentClass !== classToRemove) {
					newClasses.push(currentClass);
				}
			}
			element.className = newClasses.join(' ');
		}

	},

	show: function(element) {

		if (element) {
			this.removeClass(element, 'hidden');
			this.addClass(element, 'visible');
		}

	},

	hide: function(element) {

		if (element) {
			this.removeClass(element, 'visible');
			this.addClass(element, 'hidden');
		}

	},

	remove: function(element) {

		if (element && element.parentNode) {
			element.parentNode.removeChild(element);
		}

	},


};