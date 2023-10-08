<?php

#
# ss_poweralert_ups_status.php
# version 0.8
# November 11, 2010
#
# Copyright (C) 2006-2009, Eric A. Hall
# http://www.eric-a-hall.com/
#
# This software is licensed under the same terms as Cacti itself
#

#
# load the Cacti configuration settings if they aren't already present
#
if (isset($config) == FALSE) {

	if (file_exists(dirname(__FILE__) . "/../include/config.php")) {
		include_once(dirname(__FILE__) . "/../include/config.php");
	}

	if (file_exists(dirname(__FILE__) . "/../include/global.php")) {
		include_once(dirname(__FILE__) . "/../include/global.php");
	}

	if (isset($config) == FALSE) {
		echo ("FATAL: Unable to load Cacti configuration files \n");
		return;
	}
}

#
# load the Cacti SNMP libraries if they aren't already present
#
if (defined('SNMP_METHOD_PHP') == FALSE) {

	if (file_exists(dirname(__FILE__) . "/../lib/snmp.php")) {
		include_once(dirname(__FILE__) . "/../lib/snmp.php");
	}

	if (defined('SNMP_METHOD_PHP') == FALSE) {
		echo ("FATAL: Unable to load SNMP libraries \n");
		return;
	}
}

#
# call the main function manually if executed outside the Cacti script server
#
if (isset($called_by_script_server) == FALSE) {

	array_shift($_SERVER["argv"]);
	print call_user_func_array("ss_poweralert_ups_status", $_SERVER["argv"]);
}

#
# main function
#
function ss_poweralert_ups_status($protocol_bundle="",
	$cacti_request="", $data_request="", $data_request_key="") {

	#
	# 1st function argument contains the protocol-specific bundle
	#
	# use '====' matching for strpos in case colon is 1st character
	#
	if ((trim($protocol_bundle) == "") || (strpos($protocol_bundle, ":") === FALSE)) {

		echo ("FATAL: No SNMP parameter bundle provided\n");
		ss_poweralert_ups_status_syntax();
		return;
	}

	$protocol_array = explode(":", $protocol_bundle);

	if (count($protocol_array) < 11) {

		echo ("FATAL: Not enough elements in SNMP parameter bundle\n");
		ss_poweralert_ups_status_syntax();
		return;
	}

	if (count($protocol_array) > 11) {

		echo ("FATAL: Too many elements in SNMP parameter bundle\n");
		ss_poweralert_ups_status_syntax();
		return;
	}

	#
	# 1st bundle element is $snmp_hostname
	#
	$snmp_hostname = trim($protocol_array[0]);

	if ($snmp_hostname == "") {

		echo ("FATAL: Hostname not specified in SNMP parameter bundle\n");
		ss_poweralert_ups_status_syntax();
		return;
	}

	#
	# 2nd bundle element is $snmp_version
	#
	$snmp_version = trim($protocol_array[1]);

	if ($snmp_version == "") {

		echo ("FATAL: SNMP version not specified in SNMP parameter bundle\n");
		ss_poweralert_ups_status_syntax();
		return;
	}

	if (($snmp_version != 1) and ($snmp_version != 2) and ($snmp_version != 3)) {

		echo ("FATAL: \"$snmp_version\" is not a valid SNMP version\n");
		ss_poweralert_ups_status_syntax();
		return;
	}

	#
	# 3rd bundle element is $snmp_community
	#
	$snmp_community = trim($protocol_array[2]);

	if (($snmp_version != 3) and ($snmp_community == "")) {

		echo ("FATAL: SNMP v$snmp_version community not specified in SNMP parameter bundle\n");
		ss_poweralert_ups_status_syntax();
		return;
	}

	#
	# 4th bundle element is $snmp_v3_username
	#
	$snmp_v3_username = trim($protocol_array[3]);

	#
	# 5th bundle element is $snmp_v3_password
	#
	$snmp_v3_password = trim($protocol_array[4]);

	#
	# 6th bundle element is $snmp_v3_authproto
	#
	$snmp_v3_authproto = trim($protocol_array[5]);

	#
	# 7th bundle element is $snmp_v3_privpass
	#
	$snmp_v3_privpass = trim($protocol_array[6]);

	#
	# 8th bundle element is $snmp_v3_privproto
	#
	$snmp_v3_privproto = trim($protocol_array[7]);

	#
	# 9th bundle element is $snmp_v3_context
	#
	$snmp_v3_context = trim($protocol_array[8]);

	#
	# 10th bundle element is $snmp_port
	#
	$snmp_port = trim($protocol_array[9]);

	if ($snmp_port == "") {

		#
		# if the value was omitted use the default port number
		#
		$snmp_port = 3664;
	}

	if (is_numeric($snmp_port) == FALSE) {

		echo ("FATAL: Non-numeric SNMP port \"$snmp_port\" specified in SNMP parameter bundle\n");
		ss_poweralert_ups_status_syntax();
		return;
	}

	#
	# 11th bundle element is $snmp_timeout
	#
	$snmp_timeout = trim($protocol_array[10]);

	if ($snmp_timeout == "") {

		#
		# if the value was omitted use the global default timeout
		#
		$snmp_timeout = read_config_option("snmp_timeout");
	}

	if (is_numeric($snmp_timeout) == FALSE) {

		echo ("FATAL: Non-numeric SNMP timeout \"$snmp_timeout\" specified in SNMP parameter bundle\n");
		ss_poweralert_ups_status_syntax();
		return;
	}

	#
	# these aren't parameters, but go ahead and out $snmp_retries and $snmp_maxoids
	# from the global settings
	#
	$snmp_retries = read_config_option("snmp_retries");
	$snmp_maxoids = read_config_option("max_get_size");

	#
	# 2nd function argument is $cacti_request
	#
	$cacti_request = strtolower(trim($cacti_request));

	if ($cacti_request == "") {

		echo ("FATAL: No Cacti request provided\n");
		ss_poweralert_ups_status_syntax();
		return;
	}

	if (($cacti_request != "index") &&
		($cacti_request != "query") &&
		($cacti_request != "get")) {

		echo ("FATAL: \"$cacti_request\" is not a valid Cacti request\n");
		ss_poweralert_ups_status_syntax();
		return;
	}

	#
	# remaining function arguments are $data_request and $data_request_key
	#
	if (($cacti_request == "query") || ($cacti_request == "get")) {

		$data_request = strtolower(trim($data_request));

		if ($data_request == "") {

			echo ("FATAL: No data requested for Cacti \"$cacti_request\" request\n");
			ss_poweralert_ups_status_syntax();
			return;
		}

		if (($data_request != "upsdevice") &&
			($data_request != "upsname") &&
			($data_request != "outputload") &&
			($data_request != "inputvoltage") &&
			($data_request != "inputfrequency") &&
			($data_request != "batteryvoltage") &&
			($data_request != "batterytemperature") &&
			($data_request != "batterycharge")) {

			echo ("FATAL: \"$data_request\" is not a valid data request\n");
			ss_poweralert_ups_status_syntax();
			return;
		}

		#
		# get the index variable
		#
		if ($cacti_request == "get") {

			$data_request_key = strtolower(trim($data_request_key));

			if ($data_request_key == "") {

				echo ("FATAL: No index value provided for \"$data_request\" data request\n");
				ss_poweralert_ups_status_syntax();
				return;
			}
		}

		#
		# clear out spurious command-line parameters on query requests
		#
		else {
			$data_request_key = "";
		}
	}

	#
	# build a nested array of data elements for future use
	#
	$oid_array = array ("upsCount" => ".1.3.6.1.4.1.850.10.2.1.0",
		"attrLabels" => ".1.3.6.1.4.1.850.10.2.3.1.1.4",
		"attrValues" => ".1.3.6.1.4.1.850.10.2.3.1.1.6");

	#
	# build the snmp_arguments array for future use
	#
	# note that the array structure varies according to the version of Cacti in use
	#
	if (isset($GLOBALS['config']['cacti_version']) == FALSE) {

		echo ("FATAL: Unable to determine Cacti version\n");
		return;
	}

	elseif (substr($GLOBALS['config']['cacti_version'],0,5) == "0.8.6") {

		$snmp_arguments = array(
			$snmp_hostname,
			$snmp_community,
			"",
			$snmp_version,
			$snmp_v3_username,
			$snmp_v3_password,
			$snmp_port,
			$snmp_timeout);

		#
		# Cacti 0.8.6 SNMP timeout used milliseconds, while PHP uses Net-SNMP foormat, which
		# is typically microseconds. Normalize by multiplying the timeout value by 1000.
		#
		$snmp_timeout = ($snmp_timeout * 1000);
	}

	elseif (substr($GLOBALS['config']['cacti_version'],0,5) >= "0.8.7") {

		$snmp_arguments = array(
			$snmp_hostname,
			$snmp_community,
			"",
			$snmp_version,
			$snmp_v3_username,
			$snmp_v3_password,
			$snmp_v3_authproto,
			$snmp_v3_privpass,
			$snmp_v3_privproto,
			$snmp_v3_context,
			$snmp_port,
			$snmp_timeout,
			$snmp_retries,
			$snmp_maxoids);
	}

	else {
		echo ("FATAL: \"" . $GLOBALS['config']['cacti_version'] .
			"\" is not a supported Cacti version\n");
		return;
	}

	#
	# if they want data for just one ups, use the input data to seed the array
	#
	if ($cacti_request == "get") {

		#
		# set snmp_arguments to the first entry in the attribute label table and query
		#
		$snmp_arguments[2] = $oid_array['attrLabels'] . "." . $data_request_key . ".1";
		$snmp_test = trim(call_user_func_array("cacti_snmp_get", $snmp_arguments));

		#
		# the snmp response usually contains a "Location" label string but it could be anything
		#
		if ((isset($snmp_test) == FALSE) ||
			(substr($snmp_test, 0, 16) == "No Such Instance")) {

			echo ("FATAL: Unable to locate UPS device in SNMP\n");
			return;
		}

		#
		# response looks okay, so assume the requested index value is valid
		#
		$ups_array[0]['index'] = $data_request_key;
	}

	#
	# if they want data for all ups devices, use the PowerAlert device count to seed the array
	#
	else {
		#
		# set the snmp_arguments array to the ups count OID
		#
		$snmp_arguments[2] = $oid_array['upsCount'];

		#
		# capture the number of ups devices
		#
		$scratch = trim(call_user_func_array("cacti_snmp_get", $snmp_arguments));

		if ((isset($scratch) == FALSE) ||
			(substr($scratch, 0, 16) == "No Such Instance") ||
			(is_numeric($scratch) == FALSE) ||
			($scratch == "")) {

			echo ("FATAL: No UPS devices were returned from SNMP\n");
			return;
		}

		#
		# create the array entries
		#
		$ups_count = 0;

		while ($ups_count < $scratch) {

			$ups_array[$ups_count]['index'] = ($ups_count + 1);

			#
			# increment the device counter
			#
			$ups_count++;
		}
	}

	#
	# verify that the ups_array exists and has data
	#
	if ((isset($ups_array) == FALSE) ||
		(count($ups_array) == 0)) {

		echo ("FATAL: No matching UPS devices were returned from SNMP\n");
		return;
	}

	#
	# requests for data other than index values require additional processing
	#
	if ((($cacti_request == "query") || ($cacti_request == "get")) &&
		($data_request != "upsdevice")) {

		#
		# PowerAlert does not use fixed OIDs to store common values, instead we have to
		# search device-specific tables for matching strings to find the OID for a value
		# and then query for the value in another table
		#
		$ups_count = 0;

		foreach ($ups_array as $ups) {

			#
			# walk the "labels" tree for each UPS device
			#
			$snmp_arguments[2] = $oid_array['attrLabels'] . "." . $ups['index'];
			$attr_array = call_user_func_array("cacti_snmp_walk", $snmp_arguments);

			#
			# verify that the response contains expected data structures
			#
			if ((isset($attr_array) == FALSE) ||
				(count($attr_array) == 0) ||
				(array_key_exists('oid', $attr_array[0]) == FALSE) || 
				(array_key_exists('value', $attr_array[0]) == FALSE) ||
				(substr($attr_array[0]['value'],0,16) == "No Such Instance")) {

				echo ("FATAL: Unable to locate UPS attribute data in SNMP\n");
				return;
			}

			#
			# find the label that matches the requested data
			#
			foreach ($attr_array as $label) {

				if ((($data_request == "upsname") &&
					(trim($label['value']) == "Device Name")) ||

					(($data_request == "outputload") &&
					(trim($label['value']) == "Output Load")) ||

					(($data_request == "inputvoltage") &&
					(trim($label['value']) == "Input Voltage")) ||

					(($data_request == "inputfrequency") &&
					((trim($label['value']) == "Input Frequency") ||
					(trim($label['value']) == "Frequency"))) ||

					(($data_request == "batteryvoltage") &&
					(trim($label['value']) == "Battery Voltage")) ||

					(($data_request == "batterytemperature") &&
					(trim($label['value']) == "Battery Temperature (C)")) ||

					(($data_request == "batterycharge") &&
					(trim($label['value']) == "Battery Charge Remaining"))) {

					#
					# kick out of the loop if a match for the requested data is found
					#
					break;
				}

				#
				# no match yet, so clear $label in case one is never found
				#
				unset ($label);
			}

			#
			# all attribute labels were read and a matching data_request was not found
			#
			# the loop is only executed once per query per device so this is bad
			#
			if (isset($label) == FALSE) {

				echo ("FATAL: Unable to locate requested data in SNMP\n");
				return;
			}

			#
			# use regex to locate the relative OID
			#
			# exit if no match found
			#
			if (preg_match('/(\d+)$/', $label['oid'], $scratch) == 0) {

				echo ("FATAL: Unable to locate the requested data in SNMP\n");
				return;
			}

			#
			# match was found so use relative OID for $snmp_arguments and query
			#
			$snmp_arguments[2] = ($oid_array['attrValues'] . "." . $ups['index'] . "." . $scratch[1]);
			$scratch = trim(call_user_func_array("cacti_snmp_get", $snmp_arguments));

			#
			# verify the results of the snmp query
			#
			if ((isset($scratch) == FALSE) ||
				(substr($scratch, 0, 16) == "No Such Instance")) {

				echo ("FATAL: Unable to locate the requested data in SNMP\n");
				return;
			}

			#
			# fill in the appropriate variable with the requested data
			#
			switch ($data_request) {

				case "upsname":

					#
					# if the name is unknown, use the device index name
					#
					if (trim($scratch) == "") {

						$scratch = "Unlabelled UPS " . $ups_count;
					}

					#
					# if the name is long and has dashes, trim it down
					#
					while ((strlen($scratch) > 18) &&
						(strrpos($scratch, "-") > 12)) {

						$scratch[1] = (substr($scratch,0,
							(strrpos($scratch, "-"))));
					}

					#
					# if the name is long and has spaces, trim it down
					#
					while ((strlen($scratch) > 18) &&
						(strrpos($scratch, " ") > 12)) {

						$scratch[1] = (substr($scratch,0,
							(strrpos($scratch, " "))));
					}

					#
					# if the name is still long, chop it manually
					#
					if (strlen($scratch) > 18) {

						$scratch = (substr($scratch,0,17));
					}

					$ups_array[$ups_count]['name'] = $scratch;

					break;

				case "outputload":

					if ($scratch == "") {

						$scratch = "0";
					}

					$ups_array[$ups_count]['outputload'] = $scratch;
					break;

				case "inputvoltage":

					if ($scratch == "") {

						$scratch = "0";
					}

					#
					# some UPS input voltages are 1/10th of actual
					#
					if (($scratch > 0) && ($scratch < 30)) {

						$scratch = ($scratch * 10);
					}

					$ups_array[$ups_count]['inputvoltage'] = $scratch;
					break;

				case "inputfrequency":

					if ($scratch == "") {

						$scratch = "0";
					}

					$ups_array[$ups_count]['inputfrequency'] = $scratch;
					break;

				case "batteryvoltage":

					if ($scratch == "") {

						$scratch = "0";
					}

					#
					# some UPS battery voltages are 10x actual
					#
					if ($scratch >= 100) {

						$scratch = ($scratch / 10);
					}

					$ups_array[$ups_count]['batteryvoltage'] = $scratch;
					break;

				case "batterytemperature":

					if ($scratch == "") {

						$scratch = "0";
					}

					$ups_array[$ups_count]['batterytemperature'] = $scratch;
					break;

				case "batterycharge":

					if ($scratch == "") {

						$scratch = "0";
					}

					$ups_array[$ups_count]['batterycharge'] = $scratch;
					break;
			}

			$ups_count++;
		}
	}

	#
	# generate output
	#
	$ups_count = 0;

	foreach ($ups_array as $ups) {

		#
		# return output data according to $cacti_request
		#
		switch ($cacti_request) {

		#
		# if they want an index, dump a list of numbers
		#
		case "index":

			echo ($ups['index'] . "\n");
			break;

		#
		# if they want query data, give them the type they want
		#
		case "query":

			switch ($data_request) {

				case "upsdevice":

					echo ($ups['index'] . ":" . $ups['index'] . "\n");
					break;

				case "upsname":

					echo ($ups['index'] . ":" . $ups['name'] . "\n");
					break;

				case "outputload":

					echo ($ups['index'] . ":" . $ups['outputload'] . "\n");
					break;

				case "batterycharge":

					echo ($ups['index'] . ":" . $ups['batterycharge'] . "\n");
					break;

				case "inputvoltage":

					echo ($ups['index'] . ":" . $ups['inputvoltage'] . "\n");
					break;

				case "inputfrequency":

					echo ($ups['index'] . ":" .$ups['inputfrequency'] . "\n");
					break;

				case "batteryvoltage":

					echo ($ups['index'] . ":" .$ups['batteryvoltage'] . "\n");
					break;

				case "batterytemperature":

					echo ($ups['index'] . ":" .$ups['batterytemperature'] . "\n");
					break;
			}

			break;

		#
		# if they want explicit data, give them the type they want
		#
		case "get":

			switch ($data_request) {

				case "upsdevice":

					echo ($ups['index'] . "\n");
					break;

				case "upsname":

					echo ($ups['name'] . "\n");
					break;

				case "outputload":

					if (isset($GLOBALS['called_by_script_server']) == TRUE) {

						return($ups['outputload']);
					}

					else {
						echo ($ups['outputload'] . "\n");
					}

					break;

				case "batterycharge":

					if (isset($GLOBALS['called_by_script_server']) == TRUE) {

						return($ups['batterycharge']);
					}

					else {
						echo ($ups['batterycharge'] . "\n");
					}

					break;

				case "inputvoltage":

					if (isset($GLOBALS['called_by_script_server']) == TRUE) {

						return($ups['inputvoltage']);
					}

					else {
						echo ($ups['inputvoltage'] . "\n");
					}

					break;

				case "inputfrequency":

					if (isset($GLOBALS['called_by_script_server']) == TRUE) {

						return($ups['inputfrequency']);
					}

					else {
						echo ($ups['inputfrequency'] . "\n");
					}

					break;

				case "batteryvoltage":

					if (isset($GLOBALS['called_by_script_server']) == TRUE) {

						return($ups['batteryvoltage']);
					}

					else {
						echo ($ups['batteryvoltage'] . "\n");
					}

				case "batterytemperature":
	
					if (isset($GLOBALS['called_by_script_server']) == TRUE) {

						return($ups['batterytemperature']);
					}

					else {
						echo ($ups['batterytemperature'] . "\n");
					}

					break;
			}

			break;
		}

		$ups_count++;
	}
}

#
# find all array entries with a "value" key whose value is "Device Name"
#
function ss_poweralert_ups_status_deviceName($scratch) {

	if ($scratch['value'] == "Device Name") {

		return(true);
	}

	else {
		return(false);
	}
}

#
# display the syntax
#
function ss_poweralert_ups_status_syntax() {

	echo ("Syntax: ss_poweralert_ups_status.php <hostname>:<snmp_version>:[<snmp_community>]:\ \n" .
	"      [<snmp3_username>]:[<snmp3_password>]:[<snmp3_auth_protocol>]:[<snmp3_priv_password>]:\ \n" .
	"      [<snmp3_priv_protocol>]:[<snmp3_context>]:[<snmp_port>}:[<snmp_timeout>] \ \n" .
	"      (index|query <fieldname>|get <fieldname> <ups_device>)\n");

}

?>
