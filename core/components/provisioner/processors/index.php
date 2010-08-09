<?php
/**
 * Common processor
 *
 * @category  Provisioning
 * @author    S. Hamblett <steve.hamblett@linux.com>
 * @copyright 2009 S. Hamblett
 * @license   GPLv3 http://www.gnu.org/licenses/gpl.html
 * @link      none
 *
 * @package provisioner
 * @subpackage processors
 */
require_once dirname(dirname(__FILE__)).'/model/provisioner/provisioner.class.php';

/* Load our main class */
$pv = new Provisioner($modx);

/* initialize into a faux connector context to let PV know we dont want
 * to do mgr-specific actions, just processor ones
 */
return $pv->initialize('connector');

