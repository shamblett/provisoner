<?php
/**
 * Provisoner all metatags evolution component
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

$metatagArray = array();

/* Protection */
if(REVO_GATEWAY_OPEN != "true") die("Revo Gateway API error - Invalid access");

/* Get the resources from the database */
$db = connectToDb();

$sql = "SELECT * FROM " . $table_prefix . "site_metatags ";

$result = mysql_query($sql, $db);
if (!$result) die("Revo Gateway API error - Invalid Resource query");

while ($metatag = mysql_fetch_assoc($result)) {

    $metatagArray[] = $metatag;

}

$response = errorSuccess('',$metatagArray);
mysql_close($db);
echo toJSON($response);
