<?php

/* do NOT run this script through a web browser */
if (!isset($_SERVER["argv"][0]) || isset($_SERVER['REQUEST_METHOD'])  || isset($_SERVER['REMOTE_ADDR'])) {
   die("<br><strong>This script is only meant to run at the command line.</strong>");
}

$no_http_headers = true;

if (file_exists(dirname(__FILE__) . "/../include/global.php")) {
	include(dirname(__FILE__) . "/../include/global.php");
}
include(dirname(__FILE__) . "/../include/config.php");
include(dirname(__FILE__) . "/../lib/snmp.php");

$oids = array(
	"index" => ".1.3.6.1.4.1.14179.1.2.13.1.1",
	"agentInterfaceVlanId" => ".1.3.6.1.4.1.14179.1.2.13.1.2",
	"agentInterfaceIPAddress" => ".1.3.6.1.4.1.14179.1.2.13.1.5"
	);

$hostname = $_SERVER["argv"][1];
$snmp_community = $_SERVER["argv"][2];
$snmp_version = $_SERVER["argv"][3];
$cmd = $_SERVER["argv"][4];

function snmp_walk($hostname, $snmp_community, $oid, $snmp_version, $cacti_version) {
	if ($cacti_version) {
		$value = cacti_snmp_walk($hostname, $snmp_community, $oid, $snmp_version, "", "", "", "", "", "", 161, 1000, 1);
	} else {
		$value = cacti_snmp_walk($hostname, $snmp_community, $oid, $snmp_version, "", "", 161, 1000);
	}
	return $value;
}

if ($cmd == "index") {
	$return_arr = reindex(snmp_walk($hostname, $snmp_community, $oids["index"], $snmp_version, $config["cacti_version"]));

	for ($i=0;($i<sizeof($return_arr));$i++) {
		print $return_arr[$i] . "\n";
	}
}elseif ($cmd == "query") {
	$arg = $_SERVER["argv"][5];


	$arr_index = reindex(snmp_walk($hostname, $snmp_community, $oids["index"], $snmp_version, $config["cacti_version"]));
	$arr = reindex(snmp_walk($hostname, $snmp_community, $oids[$arg], $snmp_version));

	for ($i=0;($i<sizeof($arr_index));$i++) {
		print $arr_index[$i] . "!" . $arr[$i] . "\n";
	}


}elseif ($cmd == "get") {
	$arg = $_SERVER["argv"][5];
	$index = $_SERVER["argv"][6];

	if (($arg == "intassoc")){
 
		$arr1[0]="\"".$index."\"";



		$bsnMobileStationInterface = ".1.3.6.1.4.1.14179.2.1.4.1.27";
		$stations = snmp_walk($hostname, $snmp_community, $bsnMobileStationInterface, $snmp_version, $config["cacti_version"]);
		$assoc=count($stations);
	}
	print $assoc;
}

function reindex($arr) {
	$return_arr = array();
	
	for ($i=0;($i<sizeof($arr));$i++) {
		$return_arr[$i] = $arr[$i]["value"];
	}

	return $return_arr;
}

?>
