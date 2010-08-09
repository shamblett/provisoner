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

include "common/support.php";

$type = $scriptProperties['type'];
$id = $scriptProperties['id'];

$db = mysql_connect($database_server, $database_user, $database_password);
if (!$db) die("Revo Gateway API error - No server :- $database_server, $databse_user, $databse_password");

$dbase = str_replace('`', '', $dbase);
$db_selected = mysql_select_db($dbase, $db);
if (!$db_selected) die ("Revo Gateway API error - No database :- $dbase");

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
        echo json_encode($response);
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
        echo json_encode($response);
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
        echo json_encode($response);
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
        echo json_encode($response);
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
        echo json_encode($response);
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
        echo json_encode($response);
        break;

  default :

        $response = errorFailure("No such element type",
                        array('type' => $type));
        mysql_close($db);
        echo json_encode($response);
        
}
