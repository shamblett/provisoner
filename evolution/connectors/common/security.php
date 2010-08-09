<?php
/**
 * Provisoner security evolution component
 *
 * @category  Provisioning
 * @author    S. Hamblett <steve.hamblett@linux.com>
 * @copyright 2010 S. Hamblett
 * @license   GPLv3 http://www.gnu.org/licenses/gpl.html
 * @link      none
 *
 * @package provisioner
 *
 * Check the request is from a logged in manager user.
 */

/* Protection */
if(IN_REVO_GATEWAY_CONNECTOR != "true") die("Revo Gateway API error - Invalid access");

include_once "../../../../manager/includes/config.inc.php";

startCMSSession();

if(!isset($_SESSION['mgrValidated'])) die("Not permitted to use the gateway");

define("REVO_GATEWAY_OPEN", "true");
