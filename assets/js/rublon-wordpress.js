var RublonWP = {

	showBadge: function() {
	
		var rublonBadge = document.getElementById('rublon-badge');
		rublonBadge.parentNode.removeChild(rublonBadge);
		var loginForm = document.querySelector('form#loginform');
		if (loginForm) {
			loginForm.appendChild(rublonBadge);
			var loginButton = loginForm.querySelector('p.submit');
			if (loginButton) {
				loginButton.parentNode.removeChild(loginButton);
				rublonBadge.appendChild(loginButton);
				var loginButtonStyles = window.getComputedStyle(loginButton);
				if (loginButtonStyles && loginButtonStyles.paddingTop) {
					var loginButtonPaddingTop = parseInt(loginButtonStyles.paddingTop);
					loginButtonPaddingTop = !isNaN(loginButtonPaddingTop) ? loginButtonPaddingTop : 0;
					loginButton.style.paddingTop = (loginButtonPaddingTop + 1) + 'px';
				}
			}
			this.themeMyLoginCompat(loginForm);
			rublonBadge.style.display = 'block';
		}
		this.updateRetinaImages();
	
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

	},

	themeMyLoginCompat: function(loginForm) {

		var formParent = loginForm.parentNode;
		if (formParent && formParent.getAttribute('id') == 'theme-my-login') {
			var rublonSeal = document.getElementById('rublon-badge');
			rublonSeal.style.marginBottom = '34px';
			var loginButton = rublonSeal.querySelector('input[type="submit"]');
			var loginButtonP = rublonSeal.querySelector('p.submit');
			if (loginButton) {
				loginButton.style.marginTop = '0';
			}
			if (loginButtonP) {
				loginButtonP.style.paddingTop = '0';
			}
		}

	}

};