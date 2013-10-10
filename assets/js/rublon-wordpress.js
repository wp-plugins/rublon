var RublonWP = {

	eventHandles: {},

};

RublonWP.checkTrustedDevices = function() {

	if (window.RublonConfigure && !window.RublonConfigure.trustedDevices) {
			var elements = document.querySelectorAll('.rublon-app-info-box');
		for (var i = 0; i < elements.length; i++)
			elements[i].style.display = 'block';
	}

};

RublonWP.addListener = function(eventType, eventCallback) {

	var that = this;
	if (document.addEventListener) {
		if (window.CustomEvent) {
			// modern browsers
			document.addEventListener(eventType, function() {

				eventCallback();

			}, false);
		} else {
			// mobile webkit
			this.eventHandles[eventType] = setInterval(function() {

				if (typeof window.RublonConfigure != 'undefined' && typeof window.RublonConfigure.trustedDevices != 'undefined' && !window.RublonConfigure.trustedDevices) {
					clearInterval(that.eventHandles[eventType]);
					delete that.eventHandles[eventType];
					eventCallback();
				}

			}, 100); 
		}
	} else {
		// IE<9
		document.documentElement[eventType] = 0;
		document.documentElement.attachEvent('onpropertychange', function(event) {

			if (event.propertyName == eventType && event.srcElement[eventType] > 0) {

				eventCallback();

			}

		});
	}

};

RublonWP.handleAppInfoBox = function() {

	if (window.RublonConfigure) {
		this.checkTrustedDevices();
	} else {
		this.addListener('RublonJSSDKInit', this.checkTrustedDevices);
	}

};

RublonWP.showSeal = function() {

	var rublonSeal = document.getElementById('rublon-seal');
	rublonSeal.parentNode.removeChild(rublonSeal);
	var pSubmit = document.querySelector('#loginform p.submit');
	if (pSubmit) {
		pSubmit.appendChild(rublonSeal);
		rublonSeal.style.display = 'block';
	}

};