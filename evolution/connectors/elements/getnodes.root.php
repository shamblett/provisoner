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

$elementType = ucfirst($g[0]);
$nodes = array();

/* Templates */


$nodes[] = array(
        'text' => 'Templates',
        'id' => 'n_type_template',
        'leaf' => false,
        'cls' => 'icon-template',
        'page' => '',
        'classKey' => 'root',
        'type' => 'template',
        'draggable' => false,
);

/* TVs */

$nodes[] = array(
        'text' => 'Template Variables',
        'id' => 'n_type_tv',
        'leaf' => false,
        'cls' => 'icon-tv',
        'page' => '',
        'classKey' => 'root',
        'type' => 'tv',
        'draggable' => false,
);

/* Chunks */


$nodes[] = array(
        'text' => 'Chunks',
        'id' => 'n_type_chunk',
        'leaf' => false,
        'cls' => 'icon-chunk',
        'page' => '',
        'classKey' => 'root',
        'type' => 'chunk',
        'draggable' => false,
);


/* Snippets */


$nodes[] = array(
        'text' => 'Snippets',
        'id' => 'n_type_snippet',
        'leaf' => false,
        'cls' => 'icon-snippet',
        'page' => '',
        'classKey' => 'root',
        'type' => 'snippet',
        'draggable' => false,
);


/* Plugins */




$nodes[] = array(
        'text' => 'Plugins',
        'id' => 'n_type_plugin',
        'leaf' => false,
        'cls' => 'icon-plugin',
        'page' => '',
        'classKey' => 'root',
        'type' => 'plugin',
        'draggable' => false,
);


/* Categories */



$nodes[] = array(
        'text' => 'Categories',
        'id' => 'n_category',
        'leaf' => 0,
        'cls' => 'icon-category',
        'page' => '',
        'classKey' => 'root',
        'type' => 'category',
        'draggable' => false,
);

echo json_encode($nodes);
