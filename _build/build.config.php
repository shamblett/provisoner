<?php

/**
 * Build constants
 *
 * @category  Provisioning
 * @package   Provisioner
 * @author    S. Hamblett <shamblett@cwazy.co.uk>
 * @copyright 2009 S. Hamblett
 * @license   GPLv3 http://www.gnu.org/licenses/gpl.html 
 * @link      none
 **/
 

/* Define the MODX path constants necessary for connecting to your core */
define('MODX_BASE_PATH', dirname(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))))) . '/2.0/');
define('MODX_CORE_PATH', MODX_BASE_PATH . 'core/');
define('MODX_MANAGER_PATH', MODX_BASE_PATH . 'manager/');
define('MODX_CONNECTORS_PATH', MODX_BASE_PATH . 'connectors/');
define('MODX_ASSETS_PATH', MODX_BASE_PATH . 'assets/');

define('MODX_BASE_URL','/revolution/');
define('MODX_CORE_URL', MODX_BASE_URL . 'core/');
define('MODX_MANAGER_URL', MODX_BASE_URL . 'manager/');
define('MODX_CONNECTORS_URL', MODX_BASE_URL . 'connectors/');
define('MODX_ASSETS_URL', MODX_BASE_URL . 'assets/');
