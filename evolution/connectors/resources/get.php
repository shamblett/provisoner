<?php
/**
 * Provisoner resources evolution component
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

/* Protection */
if(REVO_GATEWAY_OPEN != "true") die("Revo Gateway API error - Invalid access");

include "common/support.php";

/* Get the resource from the database */
$db = mysql_connect($database_server, $database_user, $database_password);
if (!$db) die("Revo Gateway API error - No server :- $database_server, $databse_user, $databse_password");

$dbase = str_replace('`', '', $dbase);
$db_selected = mysql_select_db($dbase, $db);
if (!$db_selected) die ("Revo Gateway API error - No database :- $dbase");

$sql = "SELECT * FROM " . $table_prefix . "site_content "
          . "WHERE `id` = " . $scriptProperties['id'];

$result = mysql_query($sql, $db);
if (!$result) die("Revo Gateway API error - Invalid Resource query");

if ( mysql_num_rows($result) == 1 ) {
    
    $resource = mysql_fetch_assoc($result);
    
} else {
    
    $response = errorFailure("No resource found",
                        array('id' => $scriptProperties['id']));
    mysql_close($db);
    echo json_encode($response);
    return;
}

if (!empty($resource['pub_date']) && $resource['pub_date'] != '0000-00-00 00:00:00') {
    $resource['pub_date'] = strftime('%Y-%m-%d %H:%M:%S',strtotime($resource['pub_date']));
} else $resource['pub_date'] = '';
if (!empty($resource['unpub_date']) && $resource['unpub_date'] != '0000-00-00 00:00:00') {
    $resource['unpub_date'] = strftime('%Y-%m-%d %H:%M:%S',strtotime($resource['unpub_date']));
} else $resource['unpub_date'] = '';
if (!empty($resource) && $resource['publishedon'] != '0000-00-00 00:00:00') {
    $resource['publishedon'] = strftime('%Y-%m-%d %H:%M:%S',strtotime($resource['publishedon']));
} else $resource['publishedon'] = '';

$resource['class_key'] = 'modDocument';
$response = errorSuccess('',$resource);
mysql_close($db);
echo json_encode($response);
