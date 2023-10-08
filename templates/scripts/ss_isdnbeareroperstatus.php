<?php

/* do NOT run this script through a web browser */
if (!isset($_SERVER["argv"][0]) || isset($_SERVER['REQUEST_METHOD'])  || isset($_SERVER['REMOTE_ADDR'])) {
   die("<br><strong>This script is only meant to run at the command line.</strong>");
}

$no_http_headers = true;

/* display No errors */
error_reporting(0);

include_once(dirname(__FILE__) . "/../lib/snmp.php");

if (!isset($called_by_script_server)) {
	include_once(dirname(__FILE__) . "/../include/global.php");

	array_shift($_SERVER["argv"]);

	print call_user_func_array("ss_isdnbeareroperstatus", $_SERVER["argv"]);
}

function ss_isdnbeareroperstatus($hostname, $host_id, $snmp_auth, $cmd, $arg1 = "", $arg2 = "") {
	snmp_set_quick_print(0);
	$snmp = explode(":", $snmp_auth);
	$snmp_version = $snmp[0];
	$snmp_port    = $snmp[1];
	$snmp_timeout = $snmp[2];

	$snmp_auth_username   = "";
	$snmp_auth_password   = "";
	$snmp_auth_protocol   = "";
	$snmp_priv_passphrase = "";
	$snmp_priv_protocol   = "";
	$snmp_context         = "";
	$snmp_community = "";

	if ($snmp_version == 3) {
		$snmp_auth_username   = $snmp[4];
		$snmp_auth_password   = $snmp[5];
		$snmp_auth_protocol   = $snmp[6];
		$snmp_priv_passphrase = $snmp[7];
		$snmp_priv_protocol   = $snmp[8];
		$snmp_context         = $snmp[9];
	}else{
		$snmp_community = $snmp[3];
	}

	$oid_isdnBearerOperStatus = "1.3.6.1.2.1.10.20.1.2.1.1.2";

	if (($cmd == "index")) {
		$arr_index = ss_isdnbeareroperstatus_get_indexes($hostname, $snmp_community, $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout);

		foreach (array_keys($arr_index) as $item) {
			print $item."\n";
		}
	} elseif ($cmd == "query") {
		$arg = $arg1;
		
		$arr_index = ss_isdnbeareroperstatus_get_indexes($hostname, $snmp_community, $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout);

		foreach (array_keys($arr_index) as $item) {
			print $item."!".$arr_index[$item][$arg]."\n";
		}
	} elseif ($cmd == "get") {
		$arg = $arg1;
		list($index,$chan_count) = split("#",$arg2);

		$arr = cacti_snmp_walk($hostname, $snmp_community, $oid_isdnBearerOperStatus, $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, read_config_option("snmp_retries"), SNMP_POLLER);

		$r_idle = 0;
		$r_connecting = 0;
		$r_connected = 0;
		$r_active = 0;

		$return_arr = array();
		for ($i=0;($i<sizeof($arr));$i++) {
#			print $arr[$i]["oid"]."\n";
			$return_arr[substr($arr[$i]["oid"],strlen($oid_isdnBearerOperStatus)+1)] = $arr[$i]["value"];
		}

		for ($i=$index;$i<($index+$chan_count);$i++) {
			if (($return_arr[$i] == 1) || ($return_arr[$i] == 'idle(1)')) {
				$r_idle++;
			} elseif (($return_arr[$i] == 2) || ($return_arr[$i] == 'connecting(2)')) {
				$r_connecting++;
			} elseif (($return_arr[$i] == 3) || ($return_arr[$i] == 'connected(3)')) {
				$r_connected++;
			} elseif (($return_arr[$i] == 4) || ($return_arr[$i] == 'active(4)')) {
				$r_active++;
			}
		}

		switch($arg) {
			case "idle":
				return $r_idle;
				break;
			case "connecting":
				return $r_connecting;
				break;
			case "connected":
				return $r_connected;
				break;
			case "active":
				return $r_active;
				break;
			case "breakdown":
				return $r_idle."!".$r_connecting."!".$r_connected."!".$r_active;
				break;
			default:
				return $r_connecting+$r_connected+$r_active;
				break;
		}
	}
}

function ss_isdnbeareroperstatus_get_indexes($hostname, $snmp_community, $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout) {
	$oid_ifDescr = "1.3.6.1.2.1.2.2.1.2";
	$arr = cacti_snmp_walk($hostname, $snmp_community, $oid_ifDescr, $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, read_config_option("snmp_retries"), SNMP_POLLER);
	$return_arr = array();

	$chan_count = 0;
	$parent_int = "!@@!";
	$id = 0;
	for ($i=0;($i<sizeof($arr));$i++) {
		$int = $arr[$i]["value"];
		if (strlen($int) > 17) {
			if (substr($int,0,strlen($parent_int)) == $parent_int) {
				$chan_count++;
			} else {
				if (substr($int,-17) == ":0-Bearer Channel") {
					if ($chan_count > 0) { // not first PRI
						$return_arr[$id."#".$chan_count] = array();
						$return_arr[$id."#".$chan_count]["ifDescr"] = $parent_int;
						$return_arr[$id."#".$chan_count]["channels"] = $chan_count;
					}
					$id = substr($arr[$i]["oid"],strlen($oid_ifDescr)+1);
					$parent_int = substr($int,0,-17);
					$chan_count = 1;
				}
			}
		}
	}

	if ($chan_count > 0) { // last PRI
		$return_arr[$id."#".$chan_count] = array();
		$return_arr[$id."#".$chan_count]["ifDescr"] = $parent_int;
		$return_arr[$id."#".$chan_count]["channels"] = $chan_count;
	}

	return $return_arr;
}

?>
