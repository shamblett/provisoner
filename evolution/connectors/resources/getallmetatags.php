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
$db = mysql_connect($database_server, $database_user, $database_password);
if (!$db) die("Revo Gateway API error - No server :- $database_server, $databse_user, $databse_password");

$dbase = str_replace('`', '', $dbase);
$db_selected = mysql_select_db($dbase, $db);
if (!$db_selected) die ("Revo Gateway API error - No database :- $dbase");

$sql = "SELECT * FROM " . $table_prefix . "site_metatags ";

$result = mysql_query($sql, $db);
if (!$result) die("Revo Gateway API error - Invalid Resource query");

while ($metatag = mysql_fetch_assoc($result)) {

    /* UTF8 encode character fields */
    $metatag['name'] = utf8_encode($metatag['name']);
    $metatag['tag'] = utf8_encode($metatag['tag']);
    $metatag['tagValue'] = utf8_encode($metatag['tagVlaue']);
    $metatagArray[] = $metatag;

}

$response = errorSuccess('',$metatagArray);
mysql_close($db);
echo toJSON($response);
