<?php
/**
 * Settings data script
 *
 * @category  Provisioning
 * @package   Provisioner
 * @author    S. Hamblett <steve.hamblett@linux.com>
 * @copyright 2009 S. Hamblett
 * @license   GPLv3 http://www.gnu.org/licenses/gpl.html
 * @link      none
 **/

/* Context */

$context = $modx->newObject('modContext');
$context->fromArray(array(
    			'key' => 'provisioner',
    			'description' => 'The provisioner component context'
				), '', true);

/* Category */

$category = $modx->newObject('modCategory');
$category->fromArray(array(
    			'category' => 'Provisioner'
				));
				
/* System Settings */

$datasetting = $modx->newObject('modSystemSetting');
$datasetting->fromArray(array(
				'key' => 'status',
				'value' => 2,
				'xtype' => 'textfield',
				'namespace' => 'provisioner',
				'area' => 'Provisioner'
				), '', true, true);
$settings[] = $datasetting;
unset($datasetting);

$datasetting = $modx->newObject('modSystemSetting');
$datasetting->fromArray(array(
				'key' => 'cookiefile',
				'value' => "no file",
				'xtype' => 'textfield',
				'namespace' => 'provisioner',
				'area' => 'Provisioner'
				), '', true, true);
$settings[] = $datasetting;
unset($datasetting);

$datasetting = $modx->newObject('modSystemSetting');
$datasetting->fromArray(array(
				'key' => 'url',
				'value' => "no url",
				'xtype' => 'textfield',
				'namespace' => 'provisioner',
				'area' => 'Provisioner'
				), '', true, true);
$settings[] = $datasetting;	
unset($datasetting);

$datasetting = $modx->newObject('modSystemSetting');
$datasetting->fromArray(array(
				'key' => 'sitetype',
				'value' => "revolution",
				'xtype' => 'textfield',
				'namespace' => 'provisioner',
				'area' => 'Provisioner'
				), '', true, true);
$settings[] = $datasetting;	
unset($datasetting);

$datasetting = $modx->newObject('modSystemSetting');
$datasetting->fromArray(array(
				'key' => 'account',
				'value' => "",
				'xtype' => 'textfield',
				'namespace' => 'provisioner',
				'area' => 'Provisioner'
				), '', true, true);
$settings[] = $datasetting;	
unset($datasetting);

$datasetting = $modx->newObject('modSystemSetting');
$datasetting->fromArray(array(
				'key' => 'siteid',
				'value' => "",
				'xtype' => 'textfield',
				'namespace' => 'provisioner',
				'area' => 'Provisioner'
				), '', true, true);
$settings[] = $datasetting;	
unset($datasetting);

$datasetting = $modx->newObject('modSystemSetting');
$datasetting->fromArray(array(
				'key' => 'user',
				'value' => "",
				'xtype' => 'textfield',
				'namespace' => 'provisioner',
				'area' => 'Provisioner'
				), '', true, true);
$settings[] = $datasetting;
unset($datasetting);

/* User group */

$usergroup = $modx->newObject('modUserGroup');
$usergroup->fromArray(array(
				'name' => 'Provisioner',
				'parent' => 0
				), '', true);
		

				
