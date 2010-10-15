<?php
/**
 * Provisoner all TV data evolution component
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

$accessArray = array();
$templateArray = array();
$contentArray = array();
$outputArray = array();

/* Protection */
if(REVO_GATEWAY_OPEN != "true") die("Revo Gateway API error - Invalid access");

/* Get the TV access data  from the database */
$db = mysql_connect($database_server, $database_user, $database_password);
if (!$db) die("Revo Gateway API error - No server :- $database_server, $databse_user, $databse_password");

$dbase = str_replace('`', '', $dbase);
$db_selected = mysql_select_db($dbase, $db);
if (!$db_selected) die ("Revo Gateway API error - No database :- $dbase");

/* Get the access data */
$sql = "SELECT * FROM " . $table_prefix . "site_tmplvar_access";
$result = mysql_query($sql, $db);
if (!$result) die("Revo Gateway API error - Invalid TV access query");
while ($tvaccess = mysql_fetch_assoc($result)) {

    
    $accessArray[] = $tvaccess;
}

/* Get the template data */
$sql = "SELECT * FROM " . $table_prefix . "site_tmplvar_templates";
$result = mysql_query($sql, $db);
if (!$result) die("Revo Gateway API error - Invalid TV template query");
while ($tvtemplate = mysql_fetch_assoc($result)) {


    $templateArray[] = $tvtemplate;
}

/* Get the content value data */
$sql = "SELECT * FROM " . $table_prefix . "site_tmplvar_contentvalues";
$result = mysql_query($sql, $db);
if (!$result) die("Revo Gateway API error - Invalid TV content value query");
while ($tvcontent = mysql_fetch_assoc($result)) {

    /* UTF8 encode */
    $tvcontent['value'] = utf8_encode($tvcontent['value']);
    $contentArray[] = $tvcontent;
}

/* Assemble the output array */
$outputArray[0] = $accessArray;
$outputArray[1] = $templateArray;
$outputArray[2] = $contentArray;

$response = errorSuccess('',$outputArray);
mysql_close($db);
echo toJSON($response);
