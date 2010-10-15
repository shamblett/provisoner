<?php
/**
 * Provisoner all docgroups evolution component
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

$docgroupArray = array();
$outputMapArray = array();
$outputArray = array();

/* Protection */
if(REVO_GATEWAY_OPEN != "true") die("Revo Gateway API error - Invalid access");

/* Get the docgroups from the database */
$db = mysql_connect($database_server, $database_user, $database_password);
if (!$db) die("Revo Gateway API error - No server :- $database_server, $databse_user, $databse_password");

$dbase = str_replace('`', '', $dbase);
$db_selected = mysql_select_db($dbase, $db);
if (!$db_selected) die ("Revo Gateway API error - No database :- $dbase");

/* Get the keywords */
$sql = "SELECT a.id, name, private_memgroup, private_webgroup, document FROM " . $table_prefix . "documentgroup_names as a";
$sql .= " LEFT JOIN " . $table_prefix . "document_groups on a.id = document_group";
$result = mysql_query($sql, $db);
if (!$result) die("Revo Gateway API error - Invalid Docgroup query");
while ($docgroup = mysql_fetch_assoc($result)) {

    /* UTF8 encode character fields */
    $docgroup['name'] = utf8_encode($docgroup['name']);
    $docgroupArray[] = $docgroup;
}

/* Assemble the output array, list of mapped resources indexed by docgroup name */
foreach ( $docgroupArray as $docgroup ) {

    $outputMapArray[$docgroup['name']][] = $docgroup['document'];
}

$outputArray[] = $docgroupArray;
$outputArray[] = $outputMapArray;

$response = errorSuccess('',$outputArray);
mysql_close($db);
echo toJSON($response);
