<?php
/**
 * Base controller file
 *
 * @category  Provisioning
 * @author    S. Hamblett <steve.hamblett@linux.com>
 * @copyright 2009 S. Hamblett
 * @license   GPLv3 http://www.gnu.org/licenses/gpl.html
 * @link      none
 *
 * @package provisioner
 * @subpackage controllers
 */
require_once dirname(dirname(__FILE__)).'/model/provisioner/provisioner.class.php';

/* Load our main class */
$pv = new Provisioner($modx);
$pv->initialize('mgr');
$assetsUrl = $modx->getOption('provisioner.assets_url',null,$modx->getOption('assets_url').'components/provisioner/');
/* Register common JS to HEAD tag */
$modx->regClientStartupScript($assetsUrl .'js/provisioner.js');
/* Administration */
$modx->regClientStartupScript($assetsUrl . 'js/administration.js');
/* Resources */
$modx->regClientStartupScript($assetsUrl . 'js/resources/pv.tree.resource.js');
$modx->regClientStartupScript($assetsUrl . 'js/resources/resources.js');
/* Elements */
$modx->regClientStartupScript($assetsUrl . 'js/elements/pv.tree.element.js');
$modx->regClientStartupScript($assetsUrl . 'js/elements/elements.js');
/* Files */
$modx->regClientStartupScript($assetsUrl . 'js/files/pv.tree.file.js');
$modx->regClientStartupScript($assetsUrl . 'js/files/files.js');
/* Packages */
$modx->regClientStartupScript($modx->getOption('manager_url') . 'assets/modext/workspace/combos.js');
$modx->regClientStartupScript($assetsUrl . 'js/packages/pv.package.grid.js');
$modx->regClientStartupScript($assetsUrl . 'js/packages/packages.js');
/* Users */ 
$modx->regClientStartupScript($assetsUrl . 'js/users/pv.user.grid.js');
$modx->regClientStartupScript($assetsUrl . 'js/users/users.js');
return $modx->smarty->fetch('pvindex.tpl');
