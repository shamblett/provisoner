<?php
/**
 * Login Administration processor
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

if ($_REQUEST['password'] == '') {
	return $modx->error->failure($modx->lexicon('nopassword_error'));
}

if ($_REQUEST['url'] == '') {
	return $modx->error->failure($modx->lexicon('nourl_error'));
}

$siteIsEvo = false;
if ( $_REQUEST['site'] == 'evolution') {
        $siteIsEvo = true;
}

$siteId = '';
if ( !$siteIsEvo ) {
    if ($_REQUEST['siteid'] == '') {
	return $modx->error->failure($modx->lexicon('nositeid_error'));
    } else {
        $siteId = $_REQUEST['siteid'];
    }
}

$account = $_REQUEST['account'];
$password = $_REQUEST['password'];
$url = $_REQUEST['url'];
$errorstring = "";

/* Pass the parameters to the Provisioner class method */
$result = $pv->login($account, $password, $url, $siteIsEvo, $siteId, $errorstring);

/* Check the result for error */
if ($result !== true) {
	return $modx->error->failure($modx->lexicon('loginfailed')." - ".$errorstring);
}

return $modx->error->success($modx->lexicon('loginsuccess'));
