var RublonWP = {

	showSeal: function() {
	
		var rublonSeal = document.getElementById('rublon-seal');
		rublonSeal.parentNode.removeChild(rublonSeal);
		var loginForm = document.querySelector('form#loginform');
		if (loginForm) {
			loginForm.appendChild(rublonSeal);
			var loginButton = loginForm.querySelector('p.submit');
			if (loginButton) {
				loginButton.parentNode.removeChild(loginButton);
				rublonSeal.appendChild(loginButton);
				var loginButtonStyles = window.getComputedStyle(loginButton);
				if (loginButtonStyles && loginButtonStyles.paddingTop) {
					var loginButtonPaddingTop = parseInt(loginButtonStyles.paddingTop);
					loginButtonPaddingTop = !isNaN(loginButtonPaddingTop) ? loginButtonPaddingTop : 0;
					loginButton.style.paddingTop = (loginButtonPaddingTop + 1) + 'px';
				}
			}
			rublonSeal.style.display = 'block';
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

	}

};