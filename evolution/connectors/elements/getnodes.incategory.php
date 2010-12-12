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
$db = connectToDb();

$sql = "SELECT * FROM " . $table_prefix . $ar_tablemap[$elementIdentifier] .
       " WHERE `category` = " . $categoryId;

$result = mysql_query($sql, $db);

while ($element = mysql_fetch_assoc($result)) {

    $name = $elementIdentifier == 'template' ? $element['templatename'] : $element['name'];
    $name = $name;
    $elementIdentifier = $elementIdentifier;
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
echo toJSON($nodes);
