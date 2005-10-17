<?php

// Analyze user-agent conformance to HTTP protocol.

if (!defined('WP_BB_CWD'))
	die('');

// Is it claiming to be HTTP/1.1?  Then it shouldn't do HTTP/1.0 things
if (!strcmp($wp_bb_server_protocol, "HTTP/1.1")) {
	if (!strstr($wp_bb_http_headers_mixed['Pragma'], 'no-cache') && strstr($wp_bb_http_headers_mixed['Cache-Control'], 'no-cache')) {
			wp_bb_spammer("Header 'Pragma' without 'Cache-Control' prohibited for HTTP/1.1 requests");
		}
	}
}

?>
