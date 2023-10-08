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
 
$hostname = $_SERVER["argv"][1];
$snmp_community = $_SERVER["argv"][2];
 
if(isset($_SERVER["argv"][3]))
$snmp_version = $_SERVER["argv"][3];
else    
$snmp_version = "2" ;
 
function snmp_walk($hostname, $snmp_community, $oid, $snmp_version, $cacti_version) {
        
        if ($cacti_version) {
                $value = cacti_snmp_walk($hostname, $snmp_community, $oid, $snmp_version, "", "", "", "", "", "", 161, 1000, 1);
        } else {
               $value = cacti_snmp_walk($hostname, $snmp_community, $oid, $snmp_version, "", "", 161, 1000);
        }
        //print_r($value);
        return $value;
}
 

$bsnAPIfPhyTxPowerLevel = ".1.3.6.1.4.1.14179.2.2.2.1.6";
$powerlevel = reindex(snmp_walk($hostname, $snmp_community, $bsnAPIfPhyTxPowerLevel, $snmp_version, $config["cacti_version"]));

 
$sorted = array_count_values($powerlevel);
 
if (is_array($sorted) && count($sorted) > 0) {
  $indexmax = 8;
  for ($i=1;$i<=$indexmax;$i++) {
    if(!isset($sorted[$i]))
      $sorted[$i] = 0;
  }
	$newfinalarray = array("8:"=>$sorted[8], "7:"=>$sorted[7], "6:"=>$sorted[6], "5:"=>$sorted[5], "4:"=>$sorted[4], "3:"=>$sorted[3], "2:"=>$sorted[2], "1:"=>$sorted[1]);

 print " 8:" . $sorted[8] . " 7:" . $sorted[7] . " 6:" . $sorted[6] . " 5:" . $sorted[5] . " 4:" . $sorted[4] . " 3:" . $sorted[3] . " 2:" . $sorted[2] . " 1:" . $sorted[1] . "\n";

} else {
	
print "8:0 7:0 6:0 5:0 4:0 3:0 2:0 1:0\n";
}
 
function reindex($arr) {
   $return_arr = array();

   for ($i=0;($i<sizeof($arr));$i++) {
      $return_arr[$i] = $arr[$i]["value"];
   }
 
   return $return_arr;
}
?>
