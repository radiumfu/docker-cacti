<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2023 The Cacti Group                                 |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
 | GNU General Public License for more details.                            |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDTool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | This code is designed, written, and maintained by the Cacti Group. See  |
 | about.php and/or the AUTHORS file for specific developer information.   |
 +-------------------------------------------------------------------------+
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/

include(__DIR__ . '/../../include/cli_check.php');

$sli = read_config_option('syslog_last_incoming');
$slt = read_config_option('syslog_last_total');

$line = syslog_db_fetch_row("SHOW TABLE STATUS LIKE 'syslog_incoming'");
$i_rows = $line['Auto_increment'];

$line = syslog_db_fetch_row("SHOW TABLE STATUS LIKE 'syslog'");
$total_rows = $line['Auto_increment'];

if ($sli == "") {
	$sql = "REPLACE INTO settings VALUES ('syslog_last_incoming','$i_rows')";
} else {
	$sql = "UPDATE settings SET value='$i_rows' WHERE name='syslog_last_incoming'";
}
db_execute($sql);

if ($slt == "") {
	$sql = "REPLACE INTO settings VALUES ('syslog_last_total','$total_rows')";
} else {
	$sql = "UPDATE settings SET value='$total_rows' WHERE name='syslog_last_total'";
}
db_execute($sql);

if ($sli == '') $sli = 0;
if ($slt == '') $slt = 0;

print 'total:' . ($total_rows-$slt) . ' incoming:' . ($i_rows-$sli);
