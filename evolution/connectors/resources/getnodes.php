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
 */

/* Protection */
if(REVO_GATEWAY_OPEN != "true") die("Revo Gateway API error - Invalid access");

 /*  Get the default properties */
$sortBy = 'ASC';
$stringLiterals = $scriptProperties['stringLiterals'];
$noMenu = false;

$defaultRootId = 0;
if (empty($scriptProperties['id'])) {
    $context= 'root';
    $node= $defaultRootId;
} else {
    $parts= explode('_', $scriptProperties['id']);
    $context= isset($parts[0]) ? $parts[0] : 'root';
    $node = !empty($parts[1]) ? intval($parts[1]) : 0;
}

$items = array();

/* Check for a root context request */
if ( $scriptProperties['id'] == 'root' && $node == 'root') {

    /* Yes, send the evolution dummy context back. */
    $items[] = array(
                'text' => 'Evolution',
                'id' => 'evolution_0',
                'pk' => 'evolution',
                'ctx' => 'evolution',
                'leaf' => false,
                'cls' => 'icon-context',
                'qtip' => '',
                'type' => 'modContext',
                'page' => ''
            );

    echo json_encode($items);
    return;
}

/* OK, must be a resource request */

/* Get the resources from the database */
$db = connectToDb();

$sql = "SELECT id, pagetitle, longtitle, alias, description, parent, published,
          deleted, isfolder, menuindex, hidemenu FROM " . $table_prefix . "site_content "
          . "WHERE `parent` = " . $node;

$result = mysql_query($sql, $db);

while ($item = mysql_fetch_assoc($result)) {

    $class = 'icon-resource';
    if ( $item['isfolder'] == 0 ) {
        $class .= ' icon-folder';
        $hasChildren = true;
    } else {
        $class .= ' x-tree-node-leaf';
        $hasChildren = false;
    }
    if ( $item['published'] == 1 ) {
        $class .= ' ';
    } else {
        $class .= ' unpublished';
    }
    if ( $item['deleted'] == 1 ) {
        $class .= ' deleted';
    } else {
        $class .= ' ';
    }
    if ( $item['hidemenu'] == 1 ) {
        $class .= ' hidemenu';
    } else {
        $class .= ' ';
    }
    $qtip = '';
    if ($item['longtitle'] != '') {
            $qtip = '<b>'.$item['longtitle'].'</b><br />';
    }
    if ($item['description'] != '') {
            $qtip = '<i>'.$item['description'].'</i>';
    }
    $itemArray = array(
            'text' => $item['pagetitle'].' ('.$item['id'] .')',
            'id' => 'evolution'. '_' .$item['id'],
            'pk' => $item['id'],
            'cls' => $class,
            'type' => 'modResource',
            'classKey' => 'modDocument',
            'key' => $item['id'],
            'ctx' => 'evolution',
            'qtip' => $qtip,
            'preview_url' => '',
            'page' => '',
            'allowDrop' => true,
        );
    if ($hasChildren) {
            $itemArray['hasChildren'] = true;
            $itemArray['children'] = array();
            $itemArray['expanded'] = true;
        }

    $items[] = $itemArray;
    unset($qtip,$class,$itemArray,$hasChildren);

}

    mysql_close($db);
    echo toJSON($items);

