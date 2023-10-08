<?php

/* do NOT run this script through a web browser */
if (!isset($_SERVER["argv"][0]) || isset($_SERVER['REQUEST_METHOD'])  || isset($_SERVER['REMOTE_ADDR'])) {
   die("<br><strong>This script is only meant to run at the command line.</strong>");
}


$no_http_headers = true;

/* display No errors */
error_reporting(E_ERROR);

include_once(dirname(__FILE__) . '/../include/global.php');


if (!isset($called_by_script_server)) {
	print call_user_func("script_thold_alerts_count");
}

function script_thold_alerts_count() {
	global $config;
	$step = 60;
	$t = time() - $step;
	$tc = db_fetch_cell("SELECT count(id) FROM plugin_thold_log WHERE time > $t AND status = 4 ", FALSE);
	$rs = db_fetch_cell("SELECT count(id) FROM plugin_thold_log WHERE time > $t AND status = 5 ", FALSE);
	$re = db_fetch_cell("SELECT count(id) FROM plugin_thold_log WHERE time > $t AND status = 2 ", FALSE);
	return "thresholds:$tc realert:$re restored:$rs";
}


