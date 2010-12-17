<?php
/**
 * Provisioner evolution get all elements
 *
 * @category  Provisioning
 * @author    S. Hamblett <steve.hamblett@linux.com>
 * @copyright 2010 S. Hamblett
 * @license   GPLv3 http://www.gnu.org/licenses/gpl.html
 * @link      none
 *
 * @package provisioner
 */

$elementArray = array();

/* Protection */
if(REVO_GATEWAY_OPEN != "true") die("Revo Gateway API error - Invalid access");

$type = $scriptProperties['type'];

$db = connectToDb();

/* Get the requested element */
switch ( $type ) {
    
    case 'snippet' :
        
        logImportEvent("Getting all snippets", $db);
        $sql = "SELECT * FROM " . $table_prefix . "site_snippets";
        $result = mysql_query($sql, $db);
        
        while ( $element = mysql_fetch_assoc($result)) {

            $elementArray[] = $element;
        }

        $response = errorSuccess('',$elementArray);
        logImportEvent("Got all snippets", $db);
        mysql_close($db);
        echo toJSON($response);
        break;

    case 'chunk' :

        logImportEvent("Getting all chunks", $db);
        $sql = "SELECT * FROM " . $table_prefix . "site_htmlsnippets";
        $result = mysql_query($sql, $db);
  
        while ( $element = mysql_fetch_assoc($result) ) {

            $elementArray[] = $element;
        }

        $response = errorSuccess('',$elementArray);
        logImportEvent("Got all chunks", $db);
        mysql_close($db);
        echo toJSON($response);
        break;

    case 'template' :

        logImportEvent("Getting all templates", $db);
        $sql = "SELECT * FROM " . $table_prefix . "site_templates";
        $result = mysql_query($sql, $db);
        
        while ( $element = mysql_fetch_assoc($result) ) {

            $elementArray[] = $element;
        }

        $response = errorSuccess('',$elementArray);
        logImportEvent("Got all templates", $db);
        mysql_close($db);
        echo toJSON($response);
        break;

    case 'plugin' :

        logImportEvent("Getting all plugins", $db);
        $sql = "SELECT * FROM " . $table_prefix . "site_plugins";
        $result = mysql_query($sql, $db);
        
        while ( $element = mysql_fetch_assoc($result) ) {

            $elementArray[] = $element;
        }

        $response = errorSuccess('',$elementArray);
        logImportEvent("Got all plugins", $db);
        mysql_close($db);
        echo toJSON($response);
        break;

   case 'tv' :

       logImportEvent("Getting all TVs", $db);
       $sql = "SELECT * FROM " . $table_prefix . "site_tmplvars";
        $result = mysql_query($sql, $db);

        while ( $element = mysql_fetch_assoc($result) ) {

            $elementArray[] = $element;
        }

        $response = errorSuccess('',$elementArray);
        logImportEvent("Got all TVs", $db);
        mysql_close($db);
        echo toJSON($response);
        break;

  case 'category' :

        logImportEvent("Getting all categories", $db);
        $sql = "SELECT * FROM " . $table_prefix . "categories";
        $result = mysql_query($sql, $db);

        while ( $category = mysql_fetch_assoc($result) ) {
            
            $category['parent'] = 0;
            $elementArray[] = $category;
        }

        $response = errorSuccess('',$elementArray);
        logImportEvent("Got all categories", $db);
        mysql_close($db);
        echo toJSON($response);
        break;

  default :

        $response = errorFailure("No such element type",
                        array('type' => $type));
        mysql_close($db);
        echo toJSON($response);
        
}
