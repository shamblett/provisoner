<?php
/**
 * Administration processor
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
require_once dirname(__FILE__).'/index.php';

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

$account = $_REQUEST['account'];
$password = $_REQUEST['password'];
$url = $_REQUEST['url'];

/* Pass the parameters to the Provisioner class method */
$result = $pv->login($account, $password, $url);

/* Check the result for error */
if ($result !== true) {
	return $modx->error->failure($modx->lexicon($result[1]));
}

return $modx->error->success($modx->lexicon('loginsuccess'));
