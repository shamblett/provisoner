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
        
        $sql = "SELECT * FROM " . $table_prefix . "site_snippets";
        $result = mysql_query($sql, $db);
        
        while ( $element = mysql_fetch_assoc($result)) {

            $elementArray[] = $element;
        }

        $response = errorSuccess('',$elementArray);
        mysql_close($db);
        echo toJSON($response);
        break;

    case 'chunk' :

        $sql = "SELECT * FROM " . $table_prefix . "site_htmlsnippets";
        $result = mysql_query($sql, $db);
  
        while ( $element = mysql_fetch_assoc($result) ) {

            $elementArray[] = $element;
        }

        $response = errorSuccess('',$elementArray);
        mysql_close($db);
        echo toJSON($response);
        break;

    case 'template' :

        $sql = "SELECT * FROM " . $table_prefix . "site_templates";
        $result = mysql_query($sql, $db);
        
        while ( $element = mysql_fetch_assoc($result) ) {

            $elementArray[] = $element;
        }

        $response = errorSuccess('',$elementArray);
        mysql_close($db);
        echo toJSON($response);
        break;

    case 'plugin' :

        $sql = "SELECT * FROM " . $table_prefix . "site_plugins";
        $result = mysql_query($sql, $db);
        
        while ( $element = mysql_fetch_assoc($result) ) {

            $elementArray[] = $element;
        }

        $response = errorSuccess('',$elementArray);
        mysql_close($db);
        echo toJSON($response);
        break;

   case 'tv' :

        $sql = "SELECT * FROM " . $table_prefix . "site_tmplvars";
        $result = mysql_query($sql, $db);

        while ( $element = mysql_fetch_assoc($result) ) {

            $elementArray[] = $element;
        }

        $response = errorSuccess('',$elementArray);
        mysql_close($db);
        echo toJSON($response);
        break;

  case 'category' :

        $sql = "SELECT * FROM " . $table_prefix . "categories";
        $result = mysql_query($sql, $db);

        while ( $category = mysql_fetch_assoc($result) ) {
            
            $category['parent'] = 0;
            $elementArray[] = $category;
        }

        $response = errorSuccess('',$elementArray);
        mysql_close($db);
        echo toJSON($response);
        break;

  default :

        $response = errorFailure("No such element type",
                        array('type' => $type));
        mysql_close($db);
        echo toJSON($response);
        
}
