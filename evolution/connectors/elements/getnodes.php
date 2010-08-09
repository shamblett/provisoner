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

/* process ID prefixes */
$scriptProperties['id'] = !isset($scriptProperties['id']) ? 0 : (substr($scriptProperties['id'],0,2) == 'n_' ? substr($scriptProperties['id'],2) : $scriptProperties['id']);
$grab = $scriptProperties['id'];

/* setup maps */
$ar_typemap = array(
    'template' => 'modTemplate',
    'tv' => 'modTemplateVar',
    'chunk' => 'modChunk',
    'snippet' => 'modSnippet',
    'plugin' => 'modPlugin',
    'category' => 'modCategory',
);

$ar_tablemap = array(
    'template' => 'site_templates',
    'tv' => 'site_tmplvars',
    'chunk' => 'site_htmlsnippets',
    'snippet' => 'site_snippets',
    'plugin' => 'site_plugins',
    'category' => 'categories',
);

/* split the array */
$g = explode('_',$grab);

/* load correct mode */
$nodes = array();
switch ($g[0]) {
    case 'type': /* if in the element, but not in a category */
        $nodes = include dirname(__FILE__).'/getnodes.type.php';
        break;
    case 'root': /* if clicking one of the root nodes */
        $nodes = include dirname(__FILE__).'/getnodes.root.php';
        break;
    case 'category': /* if browsing categories */
       $nodes = include dirname(__FILE__).'/getnodes.category.php';
        break;
    default: /* if clicking a node in a category */
        $nodes = include dirname(__FILE__).'/getnodes.incategory.php';
        break;
}
