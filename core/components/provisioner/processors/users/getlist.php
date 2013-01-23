<?php
/**
 * Resource Get users processor
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

$limit = 20;
$start = 0;
$username = 'none';

if (@$_REQUEST['limit'] != '') {
	
	$limit = $_REQUEST['limit'];
}

if (@$_REQUEST['start'] != '') {
	
	$start = $_REQUEST['start'];
}

if (@$_REQUEST['username'] != '') {
	
	$username = $_REQUEST['username'];
}

$errorstring = "";

/* Pass the parameters to the Provisioner class method */
$result = $pv->getUsers($start, $limit, $username, $errorstring, $nodes);

/* Check the result for error */
if ($result !== true) {
	return $modx->error->failure($modx->lexicon('getusersfailed')." - ".$errorstring);
}

return $nodes;

