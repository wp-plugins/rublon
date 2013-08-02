<?php


/**
 * Helper for creating HTML and JS output.
 * 
 * This class provides methods for specific JS/HTML output
 * regarding (but not limited to) the Rublon QR Code window.
 *
 * @author Rublon Developers
 * @version 2013-07-05
 */
class RublonHTMLHelper {
	

	/**
	 * Rublon Service instance.
	 *
	 * An istance of the RublonService class or its descendant. Necessary
	 * for the class to work.
	 *
	 * @property RublonService $service
	 */
	protected $service = null;
	
	
	/**
	 * Initialize the helper with RublonService instance.
	 *
	 * A RublonService class instance is required for
	 * the object to work.
	 *
	 * @param RublonService $service An instance of the RublonService class
	 */
	public function __construct(RublonService $service) {
		$service->getConsumer()->log(__METHOD__);
		$this->service = $service;
	}

	/**
	 * Return to specified page depending on sessionData['windowType'] field.
	 *
	 * If windowType = popup:
	 *    return JS script that will redirect the opener window to a given URL
	 *    and close the popup window
	 * else:
	 *    perform an HTTP redirection to a given URL in the current browser tab.
	 *
	 * @param array|string $sessionData Session data array or window type name (string)
	 * @param string $redirectUrl (optional) URL address to redirect to
	 * @return string|void
	 */
	public function returnToPage($sessionData, $redirectUrl = null) {
		$this->getConsumer()->log(__METHOD__);
		
		if (isset($sessionData) AND
				((is_array($sessionData) AND isset($sessionData['windowType']) AND $sessionData['windowType'] == 'popup')
					OR $sessionData == 'popup')
			) {
			return $this->closeWindow($redirectUrl);
		} else {
			if (empty($redirectUrl)) $redirectUrl = '/';
			$this->getConsumer()->log($redirectUrl);
			header('Location: '. $redirectUrl);
			exit;
		}
	}



	/**
	 * Get window closing JS code.
	 * 
	 * Returns JavaScript code that closes the popup window and
	 * (optionally) redirects the window's opener to a given URL address.
	 *
	 * @param string $openerRedirectUrl (optional) URL to redirect the window's opener to
	 * @return string
	 */
	public function closeWindow($openerRedirectUrl = null) {
		$this->getConsumer()->log(__METHOD__ .' -- '. $openerRedirectUrl);
		$result = '<script type="text/javascript">';
		if ($openerRedirectUrl) {
			$result .= 'var _url = '. json_encode($openerRedirectUrl) .';
			var _location = null;
			try {
				if (window.opener && window.opener.location && window.opener.location.href) {
					_location = window.opener.location;
				} else {
					_location = window.location;
				}
			} catch (e) {
				_location = window.location;
			}
			if (_location.href == _url) {
				_location.reload(true);
			} else {
				_location.href = _url;
			}
			';
		}
		$result .= 'self.close();
		</script>';
		return $result;
	}

	/**
	 * Get service instance.
	 * 
	 * Returns the object's instance of the RublonService class.
	 *
	 * @return RublonService
	 */
	public function getService() {
		return $this->service;
	}
	
	/**
	 * Get Rublon Consumer instance.
	 *
	 * Returns the RublonConsumer class instance used in the creation
	 * of this class' RublonService class instance.
	 *
	 * @return RublonConsumer
	 */
	public function getConsumer() {
		return $this->getService()->getConsumer();
	}


}



