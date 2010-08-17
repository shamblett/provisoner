<?php
/**
 * Provisioner evolution elements
 *
 * @category  Provisioning
 * @author    S. Hamblett <steve.hamblett@linux.com>
 * @copyright 2010 S. Hamblett
 * @license   GPLv3 http://www.gnu.org/licenses/gpl.html
 * @link      none
 *
 * @package provisioner
 */

/* Protection */
if(REVO_GATEWAY_OPEN != "true") die("Revo Gateway API error - Invalid access");

$nodes = array();
/* 0: type,  1: element/category  2: elID  3: catID */
$categoryId = isset($g[3]) ? $g[3] : ($g[1] == 'category' ? $g[2] : 0);
$elementIdentifier = $g[0];
$elementType = ucfirst($elementIdentifier);
$elementClassKey = $ar_typemap[$elementIdentifier];


/* Get all elements in the category */
$db = mysql_connect($database_server, $database_user, $database_password);
if (!$db) die("Revo Gateway API error - No server :- $database_server, $databse_user, $databse_password");

$dbase = str_replace('`', '', $dbase);
$db_selected = mysql_select_db($dbase, $db);
if (!$db_selected) die ("Revo Gateway API error - No database :- $dbase");

$sql = "SELECT * FROM " . $table_prefix . $ar_tablemap[$elementIdentifier] .
       " WHERE `category` = " . $categoryId;

$result = mysql_query($sql, $db);

while ($element = mysql_fetch_assoc($result)) {

    $name = $elementIdentifier == 'template' ? $element['templatename'] : $element['name'];
    $name = utf8_encode($name);
    $elementIdentifier = utf8_encode($elementIdentifier);
    $class = 'icon-'.$elementIdentifier;
    
    $nodes[] = array(
        'text' => $name . ' (' . $element['id'] . ')',
        'id' => 'n_'.$elementIdentifier.'_element_'.$element['id'].'_'.$element['category'],
        'pk' => $element['id'],
        'category' => $categoryId,
        'leaf' => 1,
        'name' => $name,
        'cls' => $class,
        'page' => '',
        'type' => $elementIdentifier,
        'elementType' => $elementType,
        'classKey' => $elementClassKey,
    );
}

mysql_close($db);
echo json_encode($nodes);
