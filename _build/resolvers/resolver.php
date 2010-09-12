<?php
  /**
 * Provisioner Resolver
 *
 * @package provisioner
 * @author S. Hamblett steve.hamblett@linux.com
 */

$success = false;
$modx =& $object->xpdo;

switch($options[xPDOTransport::PACKAGE_ACTION]) {


    case xPDOTransport::ACTION_INSTALL:

         /* Set the correct permissions for the file imports and tmp folders */

        $importPath = $modx->getOption('assets_path') . 'components/provisioner/imports/';
        $modx->log(xPDO::LOG_LEVEL_INFO,"Setting file permissions for imports");
        $result = chmod($importPath, 0777);
        if ( !$result ) {
            $modx->log(xPDO::LOG_LEVEL_INFO,"Failed to set permissions on import directory");
            $success = false;
            break;
        }
			
        $tmpPath = $modx->getOption('assets_path') . 'components/provisioner/tmp/';
        $modx->log(xPDO::LOG_LEVEL_INFO,"Setting file permissions for tmp");
        $result = chmod($tmpPath, 0777);
        if ( !$result ) {
            $modx->log(xPDO::LOG_LEVEL_INFO,"Failed to set permissions on tmp directory");
            $success = false;
            break;
        }

        $success = true;
        break;

        case xPDOTransport::ACTION_UPGRADE:
            
            $success = true;
            break;
            
        case xPDOTransport::ACTION_UNINSTALL:

            $success = true;
            break;

}
return $success;

