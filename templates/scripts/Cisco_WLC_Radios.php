<?php

/* do NOT run this script through a web browser */
if (!isset($_SERVER["argv"][0]) || isset($_SERVER['REQUEST_METHOD'])  || isset($_SERVER['REMOTE_ADDR'])) {
	die("<br><strong>This script is only meant to run at the command line.</strong>");
}

$no_http_headers = true;

/* display No errors */
error_reporting(0);

if (isset($config)) {
	include_once(dirname(__FILE__) . "/../lib/snmp.php");
}

if (!isset($called_by_script_server)) {
	include_once(dirname(__FILE__) . "/../include/global.php");
	include_once(dirname(__FILE__) . "/../lib/snmp.php");

	array_shift($_SERVER["argv"]);

	print call_user_func_array("ss_host_radios", $_SERVER["argv"]);
}

function ss_host_radios($hostname, $host_id, $snmp_auth, $cmd, $arg1 = "", $arg2 = "") {
	$snmp = explode(":", $snmp_auth);
	$snmp_version 	= $snmp[0];
	$snmp_port    	= $snmp[1];
	$snmp_timeout 	= $snmp[2];
	$ping_retries 	= $snmp[3];
	$max_oids		= $snmp[4];

	$snmp_auth_username   	= "";
	$snmp_auth_password   	= "";
	$snmp_auth_protocol  	= "";
	$snmp_priv_passphrase 	= "";
	$snmp_priv_protocol   	= "";
	$snmp_context         	= "";
	$snmp_community 		= "";

	if ($snmp_version == 3) {
		$snmp_auth_username   = $snmp[6];
		$snmp_auth_password   = $snmp[7];
		$snmp_auth_protocol   = $snmp[8];
		$snmp_priv_passphrase = $snmp[9];
		$snmp_priv_protocol   = $snmp[10];
		$snmp_context         = $snmp[11];
	}else{
		$snmp_community = $snmp[5];
	}

	/* 12-aug-2011 Licio tolto dagli OIDs MIBs il .0 finale */
	
	$oids = array(
		"index" 				=> ".1.3.6.1.4.1.14179.2.2.13.1.1",
		"parentname" 				=> ".1.3.6.1.4.1.14179.2.2.1.1.3",
		"parentmac" 				=> ".1.3.6.1.4.1.14179.2.2.1.1.1",
		"parentethmac" 				=> ".1.3.6.1.4.1.14179.2.2.1.1.33",
		"parentmodel" 				=> ".1.3.6.1.4.1.14179.2.2.1.1.16",
		"parentserial" 				=> ".1.3.6.1.4.1.14179.2.2.1.1.17",
		"ifslotid" 				=> ".1.3.6.1.4.1.14179.2.2.2.1.1",
		"ifphychannelnumber" 			=> ".1.3.6.1.4.1.14179.2.2.2.1.4",
		"ifdot11transmittedfragmentcount" 	=> ".1.3.6.1.4.1.14179.2.2.6.1.1",
		"ifdot11mcasttransmittedframecount" 	=> ".1.3.6.1.4.1.14179.2.2.6.1.2",
		"ifdot11retrycount" 			=> ".1.3.6.1.4.1.14179.2.2.6.1.3",
		"ifdot11multipleretrycount" 		=> ".1.3.6.1.4.1.14179.2.2.6.1.4",
		"ifdot11frameduplicatecount" 		=> ".1.3.6.1.4.1.14179.2.2.6.1.5",
		"ifdot11rtssuccesscount" 		=> ".1.3.6.1.4.1.14179.2.2.6.1.6",
		"ifdot11rtsfailurecount" 		=> ".1.3.6.1.4.1.14179.2.2.6.1.7",
		"ifdot11ackfailurecount" 		=> ".1.3.6.1.4.1.14179.2.2.6.1.8",
		"ifdot11receivedfragmentcount" 		=> ".1.3.6.1.4.1.14179.2.2.6.1.9",
		"ifdot11mcastreceivedframecount" 	=> ".1.3.6.1.4.1.14179.2.2.6.1.10",
		"ifdot11fcserrorcount" 			=> ".1.3.6.1.4.1.14179.2.2.6.1.11",
		"ifdot11transmittedframecount" 		=> ".1.3.6.1.4.1.14179.2.2.6.1.12",
		"ifdot11wepundecryptablecount" 		=> ".1.3.6.1.4.1.14179.2.2.6.1.13",
		"ifdot11failedcount" 			=> ".1.3.6.1.4.1.14179.2.2.6.1.33",
		"rxutilization" 			=> ".1.3.6.1.4.1.14179.2.2.13.1.1",
		"txutilization" 			=> ".1.3.6.1.4.1.14179.2.2.13.1.2",
		"utilization" 				=> ".1.3.6.1.4.1.14179.2.2.13.1.3",
		"clients" 				=> ".1.3.6.1.4.1.14179.2.2.13.1.4",
		"users" 				=> ".1.3.6.1.4.1.14179.2.2.2.1.15",
		"poorsnrclients"			=> ".1.3.6.1.4.1.14179.2.2.13.1.24",
		"noise-.1"				=> ".1.3.6.1.4.1.14179.2.2.15.1.21",
		"noise-.2"				=> ".1.3.6.1.4.1.14179.2.2.15.1.21",
		"noise-.3"				=> ".1.3.6.1.4.1.14179.2.2.15.1.21",
		"noise-.4"				=> ".1.3.6.1.4.1.14179.2.2.15.1.21",
		"noise-.5"				=> ".1.3.6.1.4.1.14179.2.2.15.1.21",
		"noise-.6"				=> ".1.3.6.1.4.1.14179.2.2.15.1.21",
		"noise-.7"				=> ".1.3.6.1.4.1.14179.2.2.15.1.21",
		"noise-.8"				=> ".1.3.6.1.4.1.14179.2.2.15.1.21",
		"noise-.9"				=> ".1.3.6.1.4.1.14179.2.2.15.1.21",
		"noise-.10"				=> ".1.3.6.1.4.1.14179.2.2.15.1.21",
		"noise-.11"				=> ".1.3.6.1.4.1.14179.2.2.15.1.21",
		"noise-.12"				=> ".1.3.6.1.4.1.14179.2.2.15.1.21",
		"noise-.13"				=> ".1.3.6.1.4.1.14179.2.2.15.1.21",
		"noise-.36"				=> ".1.3.6.1.4.1.14179.2.2.15.1.21",
		"noise-.40"				=> ".1.3.6.1.4.1.14179.2.2.15.1.21",
		"noise-.44"				=> ".1.3.6.1.4.1.14179.2.2.15.1.21",
		"noise-.48"				=> ".1.3.6.1.4.1.14179.2.2.15.1.21",
		"noise-.52"				=> ".1.3.6.1.4.1.14179.2.2.15.1.21",
		"noise-.56"				=> ".1.3.6.1.4.1.14179.2.2.15.1.21",
		"noise-.60"				=> ".1.3.6.1.4.1.14179.2.2.15.1.21",
		"noise-.64"				=> ".1.3.6.1.4.1.14179.2.2.15.1.21",
		"noise-.100"				=> ".1.3.6.1.4.1.14179.2.2.15.1.21",
		"noise-.104"				=> ".1.3.6.1.4.1.14179.2.2.15.1.21",
		"noise-.108"				=> ".1.3.6.1.4.1.14179.2.2.15.1.21",
		"noise-.112"				=> ".1.3.6.1.4.1.14179.2.2.15.1.21",
		"noise-.116"				=> ".1.3.6.1.4.1.14179.2.2.15.1.21",
		"noise-.132"				=> ".1.3.6.1.4.1.14179.2.2.15.1.21",
		"noise-.136"				=> ".1.3.6.1.4.1.14179.2.2.15.1.21",
		"noise-.140"				=> ".1.3.6.1.4.1.14179.2.2.15.1.21",
		"interference-.1"			=> ".1.3.6.1.4.1.14179.2.2.14.1.2",
		"interference-.2"			=> ".1.3.6.1.4.1.14179.2.2.14.1.2",
		"interference-.3"			=> ".1.3.6.1.4.1.14179.2.2.14.1.2",
		"interference-.4"			=> ".1.3.6.1.4.1.14179.2.2.14.1.2",
		"interference-.5"			=> ".1.3.6.1.4.1.14179.2.2.14.1.2",
		"interference-.6"			=> ".1.3.6.1.4.1.14179.2.2.14.1.2",
		"interference-.7"			=> ".1.3.6.1.4.1.14179.2.2.14.1.2",
		"interference-.8"			=> ".1.3.6.1.4.1.14179.2.2.14.1.2",
		"interference-.9"			=> ".1.3.6.1.4.1.14179.2.2.14.1.2",
		"interference-.10"			=> ".1.3.6.1.4.1.14179.2.2.14.1.2",
		"interference-.11"			=> ".1.3.6.1.4.1.14179.2.2.14.1.2",
		"interference-.12"			=> ".1.3.6.1.4.1.14179.2.2.14.1.2",
		"interference-.13"			=> ".1.3.6.1.4.1.14179.2.2.14.1.2",
		"interference-.36"			=> ".1.3.6.1.4.1.14179.2.2.14.1.2",
		"interference-.40"			=> ".1.3.6.1.4.1.14179.2.2.14.1.2",
		"interference-.44"			=> ".1.3.6.1.4.1.14179.2.2.14.1.2",
		"interference-.48"			=> ".1.3.6.1.4.1.14179.2.2.14.1.2",
		"interference-.52"			=> ".1.3.6.1.4.1.14179.2.2.14.1.2",
		"interference-.56"			=> ".1.3.6.1.4.1.14179.2.2.14.1.2",
		"interference-.60"			=> ".1.3.6.1.4.1.14179.2.2.14.1.2",
		"interference-.64"			=> ".1.3.6.1.4.1.14179.2.2.14.1.2",
		"interference-.100"			=> ".1.3.6.1.4.1.14179.2.2.14.1.2",
		"interference-.104"			=> ".1.3.6.1.4.1.14179.2.2.14.1.2",
		"interference-.108"			=> ".1.3.6.1.4.1.14179.2.2.14.1.2",
		"interference-.112"			=> ".1.3.6.1.4.1.14179.2.2.14.1.2",
		"interference-.116"			=> ".1.3.6.1.4.1.14179.2.2.14.1.2",
		"interference-.132"			=> ".1.3.6.1.4.1.14179.2.2.14.1.2",
		"interference-.136"			=> ".1.3.6.1.4.1.14179.2.2.14.1.2",
		"interference-.140"			=> ".1.3.6.1.4.1.14179.2.2.14.1.2",
		"snr-.1"				=> ".1.3.6.1.4.1.14179.2.2.19.1.23",
		"snr-.2"				=> ".1.3.6.1.4.1.14179.2.2.19.1.23",
		"snr-.3"				=> ".1.3.6.1.4.1.14179.2.2.19.1.23",
		"snr-.4"				=> ".1.3.6.1.4.1.14179.2.2.19.1.23",
		"snr-.5"				=> ".1.3.6.1.4.1.14179.2.2.19.1.23",
		"snr-.6"				=> ".1.3.6.1.4.1.14179.2.2.19.1.23",
		"snr-.7"				=> ".1.3.6.1.4.1.14179.2.2.19.1.23",
		"snr-.8"				=> ".1.3.6.1.4.1.14179.2.2.19.1.23",
		"snr-.9"				=> ".1.3.6.1.4.1.14179.2.2.19.1.23",
		"snr-.10"				=> ".1.3.6.1.4.1.14179.2.2.19.1.23",
		);

	if ($cmd == "index") {
		$return_arr = ss_host_radios_reindex(cacti_snmp_walk($hostname, $snmp_community, $oids["index"], $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids, SNMP_POLLER));

		for ($i=0;($i<sizeof($return_arr));$i++) {
			print $return_arr[$i] . "\n";
		}
	}elseif ($cmd == "query") {
		$arg = $arg1;

		$arr_index = ss_host_radios_reindex(cacti_snmp_walk($hostname, $snmp_community, $oids["index"], $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids, SNMP_POLLER));

		if ($arg == "index") {
			$arr = ss_host_radios_reindex(cacti_snmp_walk($hostname, $snmp_community, $oids[$arg], $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids, SNMP_POLLER));

		} elseif (($arg == "parentname") || ($arg == "parentmac") || ($arg == "parentethmac") || ($arg == "parentmodel") || ($arg == "parentserial")) {
			for ($i=0;($i<sizeof($arr_index));$i++) {
			 /* 12-aug-2011 Licio tolto il "." .  comentata parte della riga originale*/
			 /* $arr[$i] = cacti_snmp_get($hostname, $snmp_community, $oids[$arg] . "." . preg_replace */	
				$arr[$i] = cacti_snmp_get($hostname, $snmp_community, $oids[$arg] . preg_replace("/(.[0-9]{1,3})$/", "", $arr_index[$i]), $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol,$snmp_priv_passphrase,$snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, SNMP_POLLER);
			}
			
		} else {
			$arr = ss_host_radios_values(cacti_snmp_walk($hostname, $snmp_community, $oids[$arg], $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids, SNMP_POLLER));
		}

		for ($i=0;($i<sizeof($arr_index));$i++) {
			print $arr_index[$i] . "!" . $arr[$i] . "\n";
		}
	}elseif ($cmd == "get") {
		$arg = $arg1;
		$index = $arg2;
		list($pre, $post) = split('[-]', $arg);
		 /* 12-aug-2011 Licio tolto il .  comentata parte della riga originale*/
		 /* return cacti_snmp_get($hostname, $snmp_community, $oids[$arg] . ".$index" */	
			return cacti_snmp_get($hostname, $snmp_community, $oids[$arg] . "$index" . "$post", $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol,$snmp_priv_passphrase,$snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, SNMP_POLLER);
	}
}

function ss_host_radios_reindex($arr) {
	$return_arr = array();
	
	/* 12-aug-2011 Licio tolto dall'OID MIB il 0. finale */

	for ($i=0;($i<sizeof($arr));$i++) {
		$return_arr[$i] = preg_replace("/(1.3.6.1.4.1.14179.2.2.13.1.1.)/", "", $arr[$i]["oid"]);
	}
	return $return_arr;
}

function ss_host_radios_values($arr) {
	$return_arr = array();

	for ($i=0;($i<sizeof($arr));$i++) {
		$return_arr[$i] = $arr[$i]["value"];
	}
	return $return_arr;
}

?>
