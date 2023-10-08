<?php

/* do NOT run this script through a web browser */
if (!isset($_SERVER["argv"][0]) || isset($_SERVER['REQUEST_METHOD'])  || isset($_SERVER['REMOTE_ADDR'])) {
	die("<br><strong>This script is only meant to run at the command line.</strong>");
}

$no_http_headers = true;

/* display ALL errors */
error_reporting(0);

if (!isset($called_by_script_server)) {
	include_once(dirname(__FILE__) . "/../include/global.php");
	array_shift($_SERVER["argv"]);
	if (isset($_SERVER['argv'][0]) && $_SERVER['argv'][0] == 'website_stats') {
		array_shift($_SERVER["argv"]);
	}
	print call_user_func("website_stats", $_SERVER["argv"]);
}

function website_stats($data) {
	$url = $data[0];
	$c = new cURL();
	$results = $c->get($url);

	$text = "http_code:".$results['options']['http_code'];
	$text .= " redirect_count:".$results['options']['redirect_count'];
	$text .= " total_time:".$results['options']['total_time'];
	$text .= " namelookup_time:".$results['options']['namelookup_time'];
	$text .= " connect_time:".$results['options']['connect_time'];
	$text .= " size_download:".$results['options']['size_download'];
	$text .= " speed_download:".$results['options']['speed_download'];
	$text .= " redirect_time:".$results['options']['redirect_time'];
	return $text;	
}




class cURL {
	var $user_agent;
	var $results;
	var $host;
	var $url;

	function cURL() {
		global $config;
		$this->user_agent = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';
		$this->compression = 'gzip';
		$this->results = array('result' => 0, 'time' => time(), 'error' => '');
	}

	function get($url) {
		$process = curl_init($url);
		curl_setopt($process, CURLOPT_HEADER, 1);
		curl_setopt($process, CURLOPT_USERAGENT, $this->user_agent);
		curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($process, CURLOPT_MAXREDIRS, 4);
		curl_setopt($process, CURLOPT_TIMEOUT, 10);
		curl_setopt($process, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($process, CURLOPT_SSL_VERIFYHOST, FALSE);

		curl_exec($process);
		$this->results['options'] = curl_getinfo($process);
		curl_close($process);

		return $this->results;
	}
}