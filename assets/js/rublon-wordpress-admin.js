var RublonWP = {

	profileForm: null,
	submitHandle: null,
	roles: null,
	roleProtectionLevels: null,
	lang: null,
	performedTasks: [],

	findForm: function(formId) {
		var forms = document.getElementsByTagName('form');
		if (forms) {
			var foundForms = [];
			for (var i = 0; i < forms.length; i++) {
				// There should be only one profile form, but let's be safe.
				if (forms[i].id && forms[i].id == formId) {
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

	setUpFormSubmitListener: function(formId, className) {

		var form = this.findForm(formId);
		if (form) {
			RublonWPTools.addClass(form, className);
		}

	},

	reloadPage: function() {

		window.location.reload();

	},

	goToPage: function(url) {

		window.location.href = url;

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

		var userProtectionTypeCheckbox = document.getElementById('rublon-userprotectiontype-checkbox');
		if (userProtectionTypeCheckbox) {
			userProtectionTypeCheckbox.addEventListener('change', function(event) {
				if (typeof event.target.checked != 'undefined') {
					var locked = document.querySelector('label.rublon-label-userprotectiontype .rublon-locked-container.rublon-userprotectiontype-locked');
					var unlocked = document.querySelector('label.rublon-label-userprotectiontype .rublon-unlocked-container.rublon-userprotectiontype-unlocked');
					if (!event.target.checked) {
						if (locked) {
							RublonWPTools.hide(locked);
						}
						if (unlocked) {
							RublonWPTools.show(unlocked);
						}
					} else {
						if (locked) {
							RublonWPTools.show(locked);
						}
						if (unlocked) {
							RublonWPTools.hide(unlocked);
						}
					}
				}
			}, false);
		}

	},

	setUpNewUserRoleChangeListener: function() {

		if (this.performedTasks.indexOf('setUpNewUserRoleChangeListener') == -1) {
			var descriptions = document.querySelectorAll('div.rublon-secured-role-description');
			var labels = document.querySelectorAll('label.rublon-label-newuserrole');
			if (descriptions.length > 1) {
				for (var i = 1; i < descriptions.length; i++) {
					descriptions[i].parentNode.removeChild(descriptions[i]);
				}
			}
			if (labels.length > 1) {
				for (var i = 1; i < labels.length; i++) {
					labels[i].parentNode.removeChild(labels[i]);
				}
			}
			var newUserRoleSelect = document.querySelector('form#createuser select#role');
			var addUserRoleSelect = document.querySelector('form#adduser select#adduser-role');
			var roleSelects = [newUserRoleSelect, addUserRoleSelect];
			if (this.roleProtectionLevels) {
				var roleProtectionLevels = this.roleProtectionLevels;
				for (var i in roleSelects) {
					if (roleSelects[i] !== null) {
						var rublonSecuredRoleDescription = document.querySelector('div.rublon-secured-role-description');
						var rublonSecuredRoleLabel = document.querySelector('label.rublon-label-newuserrole');
						var newDescription = rublonSecuredRoleDescription.cloneNode(true);
						var newLabel = rublonSecuredRoleLabel.cloneNode(true);
						roleSelects[i].dataset.rublonSelectId = (parseInt(i) + 1);
						RublonWPTools.addClass(newDescription, 'rublon-secured-role-description-' + (parseInt(i) + 1));
						RublonWPTools.addClass(newLabel, 'rublon-label-newuserrole-' + (parseInt(i) + 1));
						roleSelects[i].parentNode.appendChild(newLabel);
						roleSelects[i].parentNode.appendChild(newDescription);
						if (roleSelects[i].selectedIndex > -1) {
							var selectedOption = roleSelects[i].selectedIndex;
							if (roleSelects[i].options && roleSelects[i].options[selectedOption] && roleSelects[i].options[selectedOption].value) {
								var selectedOptionValue = roleSelects[i].options[selectedOption].value;
								if (roleProtectionLevels.protectionLevels[selectedOptionValue]) {
									RublonWPTools.show(newDescription);
									RublonWPTools.show(newLabel);
								}
							}
						}
						roleSelects[i].addEventListener('change', function(event) {
							if (event.target) {
								target = event.target;
								if (target.selectedIndex > -1) {
									var selectedOption = target.selectedIndex;
									if (target.options && target.options[selectedOption] && target.options[selectedOption].value) {
										var selectedOptionValue = target.options[selectedOption].value;
										if (typeof target.dataset.rublonSelectId !== undefined) {
											var selectId = target.dataset.rublonSelectId;
											var changedDescription = document.querySelector('.rublon-secured-role-description-' + selectId);
											var changedLabel = document.querySelector('.rublon-label-newuserrole-' + selectId);
											if (roleProtectionLevels.protectionLevels[selectedOptionValue]) {
												RublonWPTools.show(changedDescription);
												RublonWPTools.show(changedLabel);
											} else {
												RublonWPTools.hide(changedDescription);
												RublonWPTools.hide(changedLabel);
											}
										}
									}
								}
							}
						}, false);					
					}
				}			
			}
			this.performedTasks.push('setUpNewUserRoleChangeListener');
		}

	},

	setUpRegistrationAgreementListener: function() {

		this.registrationAgreement = false;
		var checkBox = document.querySelector('input[type="checkbox"]#regTermsAgreed');
		var nextButton = document.querySelector('a#regNext');
		var regForm = document.querySelector('form#RublonConsumerRegistration');
		var regButtonContainer = document.querySelector('div.rublon-reg-button-container');
		var spinner = document.querySelector('div.rublon-busy-spinner');
		if (checkBox && nextButton && regForm && regButtonContainer && spinner) {
			checkBox.addEventListener('click', function(event) {
				var el = event.target;
				if (typeof el.checked != 'undefined' && el.checked) {
					RublonWPTools.removeClass(nextButton, 'inactive');
				} else {
					RublonWPTools.addClass(nextButton, 'inactive');
				}
			}, false);
			nextButton.addEventListener('click', function(event) {
				event.preventDefault();
				var el = event.target;
				if (el && !RublonWPTools.hasClass(el, 'inactive')) {
					RublonWPTools.hide(regButtonContainer);
					RublonWPTools.show(spinner);
					checkBox.setAttribute('disabled', 'disabled');
					regForm.submit();
				}
			}, false);
		}

	},

	updateRetinaImages: function() {

		if (window.devicePixelRatio && window.devicePixelRatio >= 2) {
			var rublonImages = document.getElementsByClassName('rublon-image');
			for (var i = 0; i < rublonImages.length; i++) {
				var rublonImageSrc = rublonImages[i].getAttribute('src');
				rublonImageSrc = rublonImageSrc.replace(/(\.[a-z]{3})$/, '@2x$1');
				rublonImages[i].setAttribute('src', rublonImageSrc);
			}
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

	hasClass: function(element, className) {

		if (element && element.className) {
			var regex = new RegExp(className);
			return element.className.match(regex);
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

document.addEventListener('DOMContentLoaded', function() {

	RublonWP.updateRetinaImages();

}, false);