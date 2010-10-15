<?php
/**
 * Provisoner all plugin event evolution component
 *
 * @category  Provisioning
 * @author    S. Hamblett <steve.hamblett@linux.com>
 * @copyright 2010 S. Hamblett
 * @license   GPLv3 http://www.gnu.org/licenses/gpl.html
 * @link      none
 *
 * @package provisioner
 *
 *
 */

$eventNameArray = array();
$eventMapArray = array();
$outputArray = array();

/* Protection */
if(REVO_GATEWAY_OPEN != "true") die("Revo Gateway API error - Invalid access");

/* Get the TV access data  from the database */
$db = mysql_connect($database_server, $database_user, $database_password);
if (!$db) die("Revo Gateway API error - No server :- $database_server, $databse_user, $databse_password");

$dbase = str_replace('`', '', $dbase);
$db_selected = mysql_select_db($dbase, $db);
if (!$db_selected) die ("Revo Gateway API error - No database :- $dbase");

/* Get the plugin event data names */
$sql = "SELECT id, name FROM " . $table_prefix . "system_eventnames";
$result = mysql_query($sql, $db);
if (!$result) die("Revo Gateway API error - Invalid TV access query");
while ($eventname = mysql_fetch_assoc($result)) {
 
    $eventNameArray[] = $eventname;
}

/* Get the plugin event map data*/
$sql = "SELECT pluginid, evtid, priority FROM " . $table_prefix . "site_plugin_events, " . $table_prefix . "site_plugins ";
$sql .= " WHERE id = pluginid";
$result = mysql_query($sql, $db);
if (!$result) die("Revo Gateway API error - Invalid plugin event query");
while ($eventmap = mysql_fetch_assoc($result)) {

    $eventMapArray[] = $eventmap;
}



/* Assemble the output array */
$outputArray[0] = $eventNameArray;
$outputArray[1] = $eventMapArray;

$response = errorSuccess('',$outputArray);
mysql_close($db);
echo toJSON($response);
