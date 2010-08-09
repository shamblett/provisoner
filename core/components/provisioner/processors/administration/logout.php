<?php
/**
 * Logout Administration processor
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

/* Check the parameters are present */
if ($_REQUEST['account'] == '') {
	return $modx->error->failure($modx->lexicon('noaccount_error'));
}

$account = $_REQUEST['account'];

$errorstring = "";

$siteIsEvo = false;
if ( $_REQUEST['site'] == 'evolution') {
        $siteIsEvo = true;
}

/* Pass the parameters to the Provisioner class method */
$result = $pv->logout($errorstring, $siteIsEvo, $account);

/* Check the result for error */
if ($result !== true) {
	return $modx->error->failure($modx->lexicon('logoutfailed')." - ".$errorstring);
}

return $modx->error->success($modx->lexicon('logoutsuccess'));
