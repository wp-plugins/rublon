var RublonWP = {

	showSeal: function() {
	
		var rublonSeal = document.getElementById('rublon-seal');
		rublonSeal.parentNode.removeChild(rublonSeal);
		var loginForm = document.querySelector('form#loginform');
		if (loginForm) {
			loginForm.appendChild(rublonSeal);
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