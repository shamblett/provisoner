<?php
/**
 * Evolution site import processor
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

$errorstring = "";
$parent = false;
$importArray = array();
$abort = true;
$context='provisioner';
$timeout = 0;

/* Check for abort */
if ( !isset($_POST['pv-import-abort'])) {
    if ( $_POST['pv-import-abort'] == 0 ) $abort = false;
}

/* Assemble the import parameters */
if ( isset($_POST['pv-import-context'])) $context = $_POST['pv-import-context'];

/* Always have the base set */
$importArray['templates'] = true;
$importArray['resources'] = true;
$importArray['tvs'] = true;

/* But not the extra's */
$importArray['snippets'] = false;
$importArray['chunks'] = false;
$importArray['plugins'] = false;

if ( isset($_POST['pv-import-snippets'])) $importArray['snippets'] = true;
if ( isset($_POST['pv-import-chunks'])) $importArray['chunks'] = true;
if ( isset($_POST['pv-import-plugins'])) $importArray['plugins'] = true;
if ( isset($_POST['pv-import-parent-cat'])) $parent = true;

/* Timeout, default to 2 mins */
if ( isset($_POST['pv-ct-import-timeout'])) {

    $timeout = $_POST['pv-ct-import-timeout'];

} else {

    $timeout = 120;
}

/* Pass the parameters to the Provisioner class method */
$result = $pv->importEvoSite($importArray, $context, $parent, $abort, $timeout, $errorstring);

/* Check the result for error */
if ($result !== true) {
	return $modx->error->failure($modx->lexicon('evoimportfailed')." - ".$errorstring);
}

return $modx->error->success($modx->lexicon('evoimportsuccess') . $errorstring);
