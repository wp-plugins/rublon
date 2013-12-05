var RublonWP = {

	showSeal: function() {
	
		var rublonSeal = document.getElementById('rublon-seal');
		rublonSeal.parentNode.removeChild(rublonSeal);
		var loginForm = document.querySelector('form#loginform');
		if (loginForm) {
			loginForm.appendChild(rublonSeal);
			rublonSeal.style.display = 'block';
		}
	
	}

};