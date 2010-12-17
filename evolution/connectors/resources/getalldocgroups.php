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
$db = connectToDb();

/* Get the keywords */
logImportEvent("Getting all docgroups", $db);
$sql = "SELECT a.id, name, private_memgroup, private_webgroup, document FROM " . $table_prefix . "documentgroup_names as a";
$sql .= " LEFT JOIN " . $table_prefix . "document_groups on a.id = document_group";
$result = mysql_query($sql, $db);
if (!$result) die("Revo Gateway API error - Invalid Docgroup query");
while ($docgroup = mysql_fetch_assoc($result)) {

    $docgroupArray[] = $docgroup;
}

/* Assemble the output array, list of mapped resources indexed by docgroup name */
foreach ( $docgroupArray as $docgroup ) {

    $outputMapArray[$docgroup['name']][] = $docgroup['document'];
}

$outputArray[] = $docgroupArray;
$outputArray[] = $outputMapArray;

$response = errorSuccess('',$outputArray);
logImportEvent("Got all docgroups", $db);
mysql_close($db);
echo toJSON($response);
