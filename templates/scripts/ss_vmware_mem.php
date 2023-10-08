<?php

/* do NOT run this script through a web browser */
if (!isset($_SERVER["argv"][0]) || isset($_SERVER['REQUEST_METHOD'])  || isset($_SERVER['REMOTE_ADDR'])) {
	die("<br><strong>This script is only meant to run at the command line.</strong>");
}

$no_http_headers = true;

/* display No errors */
#error_reporting(0);

if (isset($config)) {
	include_once(dirname(__FILE__) . "/../lib/snmp.php");
}

if (!isset($called_by_script_server)) {
	include_once(dirname(__FILE__) . "/../include/global.php");
	include_once(dirname(__FILE__) . "/../lib/snmp.php");

	array_shift($_SERVER["argv"]);

	print call_user_func_array("ss_vmware_esx_mem", $_SERVER["argv"]);
}

function ss_vmware_esx_mem($hostname, $host_id, $snmp_auth, $cmd, $arg1 = "", $arg2 = "") {
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

	$oids = array(
	        "usage" => ".1.3.6.1.4.1.6876.3.2.4.1.4",
	        "vmid" => ".1.3.6.1.4.1.6876.2.1.1.7",
	        "displayname" => ".1.3.6.1.4.1.6876.2.1.1.2",
	        "index" => ".1.3.6.1.4.1.6876.2.1.1.1",
	        "vmstate" => ".1.3.6.1.4.1.6876.2.1.1.6",
	        "gueststate" => ".1.3.6.1.4.1.6876.2.1.1.8",
	        "total" => ".1.3.6.1.4.1.6876.3.2.4.1.3"
		);

	if ($cmd == "index") {
		$return_arr = ss_vmware_esx_mem_reindex(cacti_snmp_walk($hostname, $snmp_community, $oids["index"], $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids, SNMP_POLLER));

		for ($i=0;($i<sizeof($return_arr));$i++) {
			print $return_arr[$i] . "\n";
		}
	}elseif ($cmd == "query") {
		$arg = $arg1;

		$arr_index = ss_vmware_esx_mem_reindex(cacti_snmp_walk($hostname, $snmp_community, $oids["index"], $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids, SNMP_POLLER));
		
		if ($arg == "index") {
			for ($i=0;($i<sizeof($arr_index));$i++) {
                        	print $arr_index[$i] . "!" . $arr_index[$i] . "\n";
			}
		}elseif ($arg == "usage" || $arg == "total") {
			$arr_vmid = ss_vmware_esx_mem_reindex(cacti_snmp_walk($hostname, $snmp_community, $oids["vmid"], $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids, SNMP_POLLER));
			$arr = ss_vmware_esx_mem_vmid_reindex(cacti_snmp_walk($hostname, $snmp_community, $oids[$arg], $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids, SNMP_POLLER));

	                for ($i=0;($i<sizeof($arr_index));$i++) {
        	                if ($arr_vmid[$i] == -1) {
                	                print $arr_index[$i] . "!U\n";
                        	}elseif (isset($arr[$arr_vmid[$i]])) {
                                	print $arr_index[$i] . "!" . $arr[$arr_vmid[$i]] . "\n";
	                        }
	                }

		}else {
			$arr = ss_vmware_esx_mem_reindex(cacti_snmp_walk($hostname, $snmp_community, $oids[$arg], $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids, SNMP_POLLER));

			for ($i=0;($i<sizeof($arr_index));$i++) {
				print $arr_index[$i] . "!" . $arr[$i] . "\n";
			}
		}

	}elseif ($cmd == "get") {
		$arg = $arg1;
		$index = $arg2;

		if ($arg != "usage" && $arg != "total") {
			return cacti_snmp_get($hostname, $snmp_community, $oids[$arg] . ".$index", $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol,$snmp_priv_passphrase,$snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, SNMP_POLLER);

		}else {
			/* get VM ID from the snmp cache since it is faster */
			$vmid = eregi_replace("[^0-9]", "", db_fetch_cell("select field_value from host_snmp_cache where host_id=$host_id and field_name='memvmID' and snmp_index=$index"));
			if (!$vmid || $vmid == -1) {
				return "U";
			}
			return cacti_snmp_get($hostname, $snmp_community, $oids[$arg] . ".$vmid", $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol,$snmp_priv_passphrase,$snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, SNMP_POLLER);

		}
	}
}

function ss_vmware_esx_mem_reindex($arr) {
	$return_arr = array();
	for ($i=0;($i<sizeof($arr));$i++) {
		$return_arr[$i] = $arr[$i]["value"];
	}
	return $return_arr;
}

function ss_vmware_esx_mem_vmid_reindex($arr) {
        $return_arr = array();
        for ($i=0;($i<sizeof($arr));$i++) {
                if (ereg("\.([0-9]+)$", $arr[$i]["oid"], $regs)) {
                        $return_arr[$regs[1]] = $arr[$i]["value"];
                }
        }
        return $return_arr;
}

?>
