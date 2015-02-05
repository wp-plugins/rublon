(function($) {
	$(document).on('heartbeat-send', function(e, data) {
		data['rublon_heartbeat'] = 'logout_listener';
	});
	$(document).on( 'heartbeat-tick', function(e, data) {
		if ( !data['wp-auth-check'] ) {
			window.onbeforeunload = null;
			location.reload();
		}
	});
}(jQuery));