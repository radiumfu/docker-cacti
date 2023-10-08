<?php
$no_http_headers = true;

/* display No errors */
error_reporting(E_ERROR);

include_once(dirname(__FILE__) . "/../global/config.php");
include_once(dirname(__FILE__) . "/../lib/snmp.php");

if (!isset($called_by_script_server)) {
	include_once(dirname(__FILE__) . "/../include/global.php");
	array_shift($_SERVER["argv"]);
	print call_user_func_array("win_services", $_SERVER["argv"]);
}

function win_services($hostname, $snmp_community, $snmp_version, $host_id, $cmd, $arg1 = "", $arg2 = "", $snmp_port = 161, $snmp_timeout = 500) {
	$oids = array(
		'index'	=> '.1.3.6.1.4.1.77.1.2.3.1.1',
		'servstate'	=> '.1.3.6.1.4.1.77.1.2.3.1.2',
		'status'	=> '.1.3.6.1.4.1.77.1.2.3.1.2',
		);
	if ((func_num_args() <= "9") && (func_num_args() >= "5")) {
		if ($cmd == "index") {
			/* this is where it is pulling the index */
			$return_arr = cacti_snmp_walk($hostname, $snmp_community, $oids["index"], $snmp_version, "", "", $snmp_port, $snmp_timeout, read_config_option("snmp_retries"), SNMP_POLLER);

			for ($i=0; $i < sizeof($return_arr); $i++) {
				if (substr($return_arr[$i]['oid'],0,2) != "1.")
					print substr($return_arr[$i]['oid'],36) . "\n";
				else
					print substr($return_arr[$i]['oid'],strlen($oids['index'])) . "\n";
			}
		}elseif ($cmd == "query") {
			$arg = $arg1;
			$arr_index2 = array();
			$arr_index = cacti_snmp_walk($hostname, $snmp_community, $oids["index"], $snmp_version, "", "", $snmp_port, $snmp_timeout, read_config_option("snmp_retries"), SNMP_POLLER);
			for ($i = 0; $i < sizeof($arr_index); $i++) {
				if (substr($arr_index[$i]['oid'],0,2) != "1.")
					$arr_index2[$i] =  substr($arr_index[$i]['oid'], 36);
				else
					$arr_index2[$i] =  substr($arr_index[$i]['oid'], strlen($oids['index']));
			}

			$arr = win_services_reindex(cacti_snmp_walk($hostname, $snmp_community, $oids[$arg], $snmp_version, "", "", $snmp_port, $snmp_timeout, read_config_option("snmp_retries"), SNMP_POLLER));
			for ($i = 0; $i < sizeof($arr_index2); $i++) {
				if ($arg == 'status') {
					switch ($arr[$i]) {
						case 4:
							$arr[$i] = 'Started';
							break;
						default:
							$arr[$i] = 'Stopped';
							break;

					}
				}
				print $arr_index2[$i] . " !" . $arr[$i] . "\n";
			}
		}elseif ($cmd == "get") {
			$arg = $arg1;
			$index = trim($arg2);
			if ($arg == "servstate") {
				$x = cacti_snmp_get($hostname, $snmp_community, $oids[$arg] . '.' . $index, $snmp_version, "", "", $snmp_port, $snmp_timeout, read_config_option("snmp_retries"), SNMP_POLLER);
				if (trim($x) == '') $x = 0;
				if ($x < 4) $x = 0;
				if ($x == 4) $x = 1;
				return $x;
			}
		}
	} else {
		return "ERROR: Invalid Parameters\n";
	}
}

function win_services_reindex($arr) {
	$return_arr = array();

	for ($i=0;($i<sizeof($arr));$i++) {
		$return_arr[$i] = $arr[$i]["value"];
	}

	return $return_arr;
}

?>