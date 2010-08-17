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

/* Grab all the root categories */
$db = mysql_connect($database_server, $database_user, $database_password);
if (!$db) die("Revo Gateway API error - No server :- $database_server, $databse_user, $databse_password");

$dbase = str_replace('`', '', $dbase);
$db_selected = mysql_select_db($dbase, $db);
if (!$db_selected) die ("Revo Gateway API error - No database :- $dbase");

$sql = "SELECT * FROM " . $table_prefix . 'categories';

$result = mysql_query($sql, $db);

while ($category = mysql_fetch_assoc($result)) {

    $class = 'icon-category folder';
    $nodes[] = array(
        'text' => utf8_encode($category['category']) . ' (' . $category['id'] . ')',
        'id' => 'n_category_'.$category['id'],
        'pk' => $category['id'],
        'data' => $category,
        'category' => $category['id'],
        'leaf' => true,
        'cls' => $class,
        'page' => '',
        'classKey' => 'modCategory',
        'type' => 'category',
    );
}

mysql_close($db);
echo json_encode($nodes);
