<?php

if (!defined('WP_BB_CWD'))
	die('');

define(WP_BB_VERSION, "1.2.1");

require_once(WP_BB_CWD . "/bad-behavior-functions.php");

// Write to the log file.
function wp_bb_log($response, $denied_reason) {
	global $wp_bb_logging, $wp_bb_verbose_logging;

	if (($wp_bb_verbose_logging) || ($wp_bb_logging && ($response == 403 || $response == 412))) {
		require_once(WP_BB_CWD . "/bad-behavior-database.php");
		wp_bb_db_log($response, $denied_reason);
	}
}

// This function is called when there is absolutely no hope for redemption for
// the offending spammer.
function wp_bb_spammer($denied_reason) {
	wp_bb_log(403, $denied_reason);
	require_once(WP_BB_CWD . "/bad-behavior-banned.php");
	wp_bb_banned($denied_reason);
}

// Load up PHP4 specific stuff if needed
if (version_compare(phpversion(), "5.0.0") < 0) {
	require_once(WP_BB_CWD . "/bad-behavior-php4.php");
}

// Set up some initial variables.
$wp_bb_approved = 2;
$wp_bb_db_failure = FALSE;
$wp_bb_remote_addr = $_SERVER['REMOTE_ADDR'];
$wp_bb_request_method = $_SERVER['REQUEST_METHOD'];
$wp_bb_http_host = $_SERVER['HTTP_HOST'];
$wp_bb_request_uri = $_SERVER['REQUEST_URI'];
$wp_bb_server_protocol = $_SERVER['SERVER_PROTOCOL'];
if (array_key_exists('HTTP_REFERER', $_SERVER))
	$wp_bb_http_referer = $_SERVER['HTTP_REFERER'];
else
	$wp_bb_http_referer = '';
$wp_bb_http_user_agent = $_SERVER['HTTP_USER_AGENT'];
$wp_bb_server_signature = $_SERVER['SERVER_SIGNATURE'];

// Load up database stuff only if requested
if ($wp_bb_verbose_logging || $wp_bb_logging) {
	require_once(WP_BB_CWD . "/bad-behavior-database.php");
}

// Reconstruct the entire HTTP headers as received.
$wp_bb_headers = "$wp_bb_request_method $wp_bb_request_uri $wp_bb_server_protocol\n";
$wp_bb_http_headers = getheaders();
foreach ($wp_bb_http_headers as $wp_bb_header => $wp_bb_value) {
	$wp_bb_headers .= "$wp_bb_header: $wp_bb_value\n";
}

// Reconstruct the HTTP entity, if present.
$wp_bb_request_entity = "";
if (!strcasecmp($wp_bb_request_method, "POST")) {
	foreach ($_POST as $wp_bb_header => $wp_bb_value) {
		$wp_bb_request_entity .= "$wp_bb_header: $wp_bb_value\n";
	}
}

// Postprocess the headers to mixed-case
// FIXME: get the world to stop using PHP as CGI
$wp_bb_http_headers_mixed = array();
foreach ($wp_bb_http_headers as $h=>$v)
	$wp_bb_http_headers_mixed[uc_all($h)]=$v;
	
// First check the whitelist
require_once(WP_BB_CWD . "/bad-behavior-whitelist.php");
if (!wp_bb_check_whitelist()):

	// First see if it's a spammer we know about
	if ($wp_bb_logging && wp_bb_db_search()) {
		$denied_reason = "I know you and I don't like you, dirty spammer.";
		wp_bb_log(412, $denied_reason);
		require_once(WP_BB_CWD . "/bad-behavior-banned.php");
		wp_bb_banned($denied_reason);
	}

	// Easy stuff: Ban known bad user-agents
	require_once(WP_BB_CWD . "/bad-behavior-user-agent.php");
	
	// Now analyze requests coming from "MSIE"
	if (stripos($wp_bb_http_user_agent, "MSIE") !== FALSE) {
		if (stripos($wp_bb_http_user_agent, "Opera") === FALSE) {
			require_once(WP_BB_CWD . "/bad-behavior-msie.php");
		} else {
			require_once(WP_BB_CWD . "/bad-behavior-opera.php");
		}
	}
	elseif (stripos($wp_bb_http_user_agent, "msnbot") !== FALSE) {
		require_once(WP_BB_CWD . "/bad-behavior-msnbot.php");
	}
	elseif (stripos($wp_bb_http_user_agent, "Googlebot") !== FALSE ||
		stripos($wp_bb_http_user_agent, "Mediapartners-Google") !== FALSE) {
		require_once(WP_BB_CWD . "/bad-behavior-google.php");
	}
	// Now analyze requests coming from "Konqueror"
	elseif (stripos($wp_bb_http_user_agent, "Konqueror") !== FALSE) {
		require_once(WP_BB_CWD . "/bad-behavior-konqueror.php");
	}
	elseif (stripos($wp_bb_http_user_agent, "Opera") !== FALSE) {
		require_once(WP_BB_CWD . "/bad-behavior-opera.php");
	}
	elseif (stripos($wp_bb_http_user_agent, "Safari") !== FALSE) {
		require_once(WP_BB_CWD . "/bad-behavior-safari.php");
	}
	elseif (stripos($wp_bb_http_user_agent, "Lynx") !== FALSE) {
		require_once(WP_BB_CWD . "/bad-behavior-lynx.php");
	}
	elseif (stripos($wp_bb_http_user_agent, "MovableType") !== FALSE) {
		require_once(WP_BB_CWD . "/bad-behavior-movabletype.php");
	}
	elseif (stripos($wp_bb_http_user_agent, "Mozilla") !== FALSE &&
		stripos($wp_bb_http_user_agent, "Mozilla") == 0) {
		require_once(WP_BB_CWD . "/bad-behavior-mozilla.php");
	}
	
	// Analyze the Referer, if present
	if (array_key_exists('Referer', $wp_bb_http_headers_mixed)) {
		require_once(WP_BB_CWD . "/bad-behavior-referer.php");
	}
	
	// Now analyze all other requests
	require_once(WP_BB_CWD . "/bad-behavior-http-headers.php");
	
endif; // whitelist

// If we get this far, the client is probably OK
wp_bb_log(200, '');

?>
