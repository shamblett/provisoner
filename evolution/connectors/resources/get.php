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

/* Get the resource from the database */
$db = connectToDb();

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

/* Set the class key for correct creation in Revolution */
if ( $resource['type'] == 'reference') {
    
     $resource['class_key'] = 'modWebLink';
   
} else {
    
    $resource['class_key'] = 'modDocument';
}

$response = errorSuccess('',$resource);
mysql_close($db);
echo toJSON($response);
