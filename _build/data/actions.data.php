<?php
/**
 * Actions build script
 *
 * @category  Provisioning
 * @package   Provisioner
 * @author    S. Hamblett <steve.hamblett@linux.com>
 * @copyright 2009 S. Hamblett
 * @license   GPLv3 http://www.gnu.org/licenses/gpl.html
 * @link      none
 **/

/* Actions */
$action= $modx->newObject('modAction');
$action->fromArray(array(
    'id' => 1,
    'namespace' => 'provisioner',
    'parent' => '0',
    'controller' => 'index',
    'haslayout' => '1',
    'lang_topics' => 'provisioner:default,file',
    'assets' => '',
), '', true, true);

/* load menu into action */
$menu= $modx->newObject('modMenu');
$menu->fromArray(array(
    'text' => 'provisioner',
    'parent' => 'components',
    'text' => 'provisioner',
    'description' => 'provisioner.desc',
    'icon' => 'images/icons/plugin.gif',
    'menuindex' => '0',
    'params' => '',
    'handler' => '',
), '', true, true);
$menu->addOne($action);

return $menu;
