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

$elements = array();
$elementType = ucfirst($g[1]);
$type = $scriptProperties['type'];

/* Get all the elements in this type */
$db = connectToDb();

$sql = "SELECT * FROM " . $table_prefix . $ar_tablemap[$type];

$result = mysql_query($sql, $db);

while ($element = mysql_fetch_assoc($result)) {

    $elements[] = $element;
}
unset($element);
$catsSeen = array();

/* Loop through categories with elements in this type */
foreach ( $elements as $element ) {

    if ( $element['category'] != 0 ) {

        if ( !in_array( $element['category'], $catsSeen) ) {

            $catsSeen[] = $element['category'];
            $sql = "SELECT * FROM " . $table_prefix ."categories WHERE `id` = " . $element['category'];
            $result = mysql_query($sql, $db);
            while ($category = mysql_fetch_assoc($result)) {
				
                $class = 'icon-category folder';
                $nodes[] = array(
                    'text' => $category['category'] . ' (' . $category['id'] . ')',
                    'id' => 'n_'.$g[1].'_category_'.($category['id'] != null ? $category['id'] : 0),
                    'pk' => $category['id'],
                    'category' => $category['id'],
                    'data' => $category,
                    'leaf' => false,
                    'cls' => $class,
                    'page' => '',
                    'classKey' => 'modCategory',
                    'elementType' => $elementType,
                    'type' => $g[1],
                );
            }
        }

   } else {

            /* handle templatename case */
            $name = $type == 'template' ? $element['templatename'] : $element['name'];
            $name = $name;
            $class = 'icon-'.$g[1];
            $nodes[] = array(
                'text' => strip_tags($name) . ' (' . $element['id'] . ')',
                'id' => 'n_'.$g[1].'_element_'.$element['id'].'_0',
                'pk' => $element['id'],
                'category' => 0,
                'leaf' => true,
                'name' => $name,
                'cls' => $class,
                'page' => '',
                'type' => $g[1],
                'elementType' => $elementType,
                'classKey' => $elementClassKey,
                'qtip' => $element['description']
            );
   }

}

mysql_close($db);
echo toJSON($nodes);
