<?php

// Analyze user agents claiming to be Safari

if (!defined('WP_BB_CWD'))
	die('');

require_once(WP_BB_CWD . "/bad-behavior-accept.php");

wp_bb_check_accept();

?>
