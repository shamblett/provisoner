<?php
/**
 * File Import processor
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
$file = 'none';
$folder = false;
$realpath = 'none';
$prependpath = '';


if (@$_REQUEST['file'] != '') {
	
	$file = $_REQUEST['file'];
}

if (@$_REQUEST['folder'] != '') {
	
	if ($_REQUEST['folder'] == 'true' ) { 
	
		$folder = true;
		
	}
}

if (@$_REQUEST['realpath'] != '') {
	
	$realpath = $_REQUEST['realpath'];
		
}

if (@$_REQUEST['prependpath'] != '') {
	
	$prependpath = $_REQUEST['prependpath'];
		
}

$errorstring = "";

/* Pass the parameters to the Provisioner class method */
$result = $pv->importFiles($file, $folder, $realpath, $prependpath, $errorstring);

/* Check the result for error */
if ($result !== true) {
	return $modx->error->failure($modx->lexicon('importfilesfailed')." - ".$errorstring);
}

return $modx->error->success($modx->lexicon('importsuccess'));;

