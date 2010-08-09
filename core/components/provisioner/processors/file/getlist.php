<?php
/**
 * File Get List processor
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

if ($_REQUEST['node'] != '') {
	
	$filenode = $_REQUEST['node'];
}

$errorstring = "";

/* Pass the parameters to the Provisioner class method */
$result = $pv->getFiles($filenode, $errorstring, $nodes);

/* Check the result for error */
if ($result !== true) {
	return $modx->error->failure($modx->lexicon('getfilesfailed')." - ".$errorstring);
}

return $nodes;

