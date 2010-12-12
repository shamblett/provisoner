<?php
/**
 * Provisioner evolution elements
 *
 * @category  Provisioning
 * @author    S. Hamblett <steve.hamblett@linux.com>
 * @copyright 2010 S. Hamblett
 * @license   GPLv3 http://www.gnu.org/licenses/gpl.html
 * @link      none
 *
 * @package provisioner
 */

/* Protection */
if(REVO_GATEWAY_OPEN != "true") die("Revo Gateway API error - Invalid access");

$type = $scriptProperties['type'];
$id = $scriptProperties['id'];

$db = connectToDb();

/* Get the requested element */
switch ( $type ) {
    
    case 'snippet' :
        
        $sql = "SELECT * FROM " . $table_prefix . "site_snippets" .
               " WHERE `id` = " . $id;
        $result = mysql_query($sql, $db);
        if (mysql_num_rows($result) == 0) {
            
            $response = errorFailure("No snippet found",
                        array('id' => $id));
               
        } else {
        
            $element = mysql_fetch_assoc($result);
            $response = errorSuccess('',$element);
        }

        mysql_close($db);
        echo toJSON($response);
        break;

    case 'chunk' :

        $sql = "SELECT * FROM " . $table_prefix . "site_htmlsnippets" .
               " WHERE `id` = " . $id;
        $result = mysql_query($sql, $db);
        if (mysql_num_rows($result) == 0) {

            $response = errorFailure("No chunk found",
                        array('id' => $id));

        } else {

            $element = mysql_fetch_assoc($result);
            $response = errorSuccess('',$element);
        }

        mysql_close($db);
        echo toJSON($response);
        break;

    case 'template' :

        $sql = "SELECT * FROM " . $table_prefix . "site_templates" .
               " WHERE `id` = " . $id;
        $result = mysql_query($sql, $db);
        if (mysql_num_rows($result) == 0) {

            $response = errorFailure("No template found",
                        array('id' => $id));

        } else {

            $element = mysql_fetch_assoc($result);
            $response = errorSuccess('',$element);
        }

        mysql_close($db);
        echo toJSON($response);
        break;

    case 'plugin' :

        $sql = "SELECT * FROM " . $table_prefix . "site_plugins" .
               " WHERE `id` = " . $id;
        $result = mysql_query($sql, $db);
        if (mysql_num_rows($result) == 0) {

            $response = errorFailure("No plugin found",
                        array('id' => $id));

        } else {

            $element = mysql_fetch_assoc($result);
            $response = errorSuccess('',$element);
        }

        mysql_close($db);
        echo toJSON($response);
        break;

   case 'tv' :

        $sql = "SELECT * FROM " . $table_prefix . "site_tmplvars" .
               " WHERE `id` = " . $id;
        $result = mysql_query($sql, $db);
        if (mysql_num_rows($result) == 0) {

            $response = errorFailure("No TV found",
                        array('id' => $id));

        } else {

            $element = mysql_fetch_assoc($result);
            $response = errorSuccess('',$element);
        }

        mysql_close($db);
        echo toJSON($response);
        break;

  case 'category' :

        $sql = "SELECT * FROM " . $table_prefix . "categories" .
               " WHERE `id` = " . $id;
        $result = mysql_query($sql, $db);
        if (mysql_num_rows($result) == 0) {

            $response = errorFailure("No category found",
                        array('id' => $id));

        } else {

            $category = mysql_fetch_assoc($result);
            $category['parent'] = 0;
            $response = errorSuccess('',$category);
        }

        mysql_close($db);
        echo toJSON($response);
        break;

  default :

        $response = errorFailure("No such element type",
                        array('type' => $type));
        mysql_close($db);
        echo toJSON($response);
        
}
