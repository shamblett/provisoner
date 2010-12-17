<?php
/**
 * Provisoner all resources evolution component
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

$resourceArray = array();

/* Protection */
if(REVO_GATEWAY_OPEN != "true") die("Revo Gateway API error - Invalid access");

/* Get the resources from the database */
$db = connectToDb();

logImportEvent("Getting all resources", $db);

$sql = "SELECT * FROM " . $table_prefix . "site_content ";

$result = mysql_query($sql, $db);
if (!$result) die("Revo Gateway API error - Invalid Resource query");

while ($resource = mysql_fetch_assoc($result)) {

    /* Set the class key for correct creation in Revolution */
    if ( $resource['type'] == 'reference') {

        $resource['class_key'] = 'modWebLink';

    } else {

        $resource['class_key'] = 'modDocument';
    }
    $resourceArray[] = $resource;

}

$response = errorSuccess('',$resourceArray);
logImportEvent("Got all resources", $db);
mysql_close($db);
echo toJSON($response);
