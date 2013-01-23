<?php
/**
 * Resource Get List processor
 *
 * @category  Provisioning
 * @author    S. Hamblett <steve.hamblett@linux.com>
 * @copyright 2009 S. Hamblett
 * @license   GPLv3 http://www.gnu.org/licenses/gpl.html
 * @link      none
 *
 * @package   provisioner
 * @subpackage processors
 **/
require_once dirname(dirname(__FILE__)).'/index.php';

/* Check the incoming parameters */

$id = "";
$type = "";

if (@$_REQUEST['id'] != '') {
	
	$id = $_REQUEST['id'];
}

if (@$_REQUEST['node'] != '') {
	
	$id = $_REQUEST['node'];
}

if (@$_REQUEST['type'] != '') {
	
	$type = $_REQUEST['type'];
}


$errorstring = "";

/* Pass the parameters to the Provisioner class method */
$result = $pv->getResources($id, $type, $errorstring, $nodes);

/* Check the result for error */
if ($result !== true) {
	return $modx->error->failure($modx->lexicon('getresourcesfailed')." - ".$errorstring);
}

return $nodes;

