<?php
/**
 * User Import processor
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
$id = -1;
$type = 'mananger';

if ($_REQUEST['id'] != '') {
	
	$id = $_REQUEST['id'];
}

if ($_REQUEST['type'] != '') {

	$type = $_REQUEST['type'];
}

$errorstring = "";

/* Pass the parameters to the Provisioner class method */
$result = $pv->importUsers($id, $type, $errorstring);

/* Check the result for error */
if ($result !== true) {
	return $modx->error->failure($modx->lexicon('importusersfailed')." - ".$errorstring);
}

return $modx->error->success($modx->lexicon('importsuccess'));;

