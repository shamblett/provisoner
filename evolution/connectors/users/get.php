<?php
/**
 * Provisioner evolution users component
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

$db = connectToDb();

/*  Get the user */
if (  $scriptProperties['type'] == 'manager' ) {

    $sql = "SELECT * FROM "  . $table_prefix . "user_attributes " .
           "WHERE `internalKey` = " . "'" . $scriptProperties['id'] . "'" ;

     $result = mysql_query($sql, $db);
     if ( mysql_num_rows($result) != 1 ) {

         $response = errorFailure("No manager user found",
                        array('id' => $id));
        mysql_close($db);
        echo json_encode($response);
        return;

     } else {

         $user = mysql_fetch_assoc($result);
         $sql = "SELECT * FROM "  . $table_prefix . "manager_users " .
           "WHERE `id` = " . "'" . $scriptProperties['id'] . "'";
         $result = mysql_query($sql, $db);
         $userMain = mysql_fetch_assoc($result);
         $user['username'] = $userMain['username'];
         $user['password'] = $userMain['password'];
         $response = errorSuccess('',$user);

     }

 } else {

     $sql = "SELECT * FROM "  . $table_prefix . "web_user_attributes " .
           "WHERE `internalKey` = " . "'" . $scriptProperties['id'] . "'";

     $result = mysql_query($sql, $db);
     if ( mysql_num_rows($result) != 1 ) {

         $response = errorFailure("No web user found",
                        array('id' => $id));
        mysql_close($db);
        echo json_encode($response);
        return;

     } else {

         $user = mysql_fetch_assoc($result);
         $sql = "SELECT * FROM "  . $table_prefix . "web_users " .
           "WHERE `id` = " . "'" . $scriptProperties['id'] . "'";
         $result = mysql_query($sql, $db);
         $userMain = mysql_fetch_assoc($result);
         $user['username'] = $userMain['username'];
         $user['password'] = $userMain['password'];
         $response = errorSuccess('',$user);

     }

}

/* Formatting */
$user['dob'] = !empty($user['dob']) ? strftime('%m/%d/%Y',$user['dob']) : '';
$user['blockeduntil'] = !empty($user['blockeduntil']) ? strftime('%m/%d/%Y %I:%M %p',$user['blockeduntil']) : '';
$user['blockedafter'] = !empty($user['blockedafter']) ? strftime('%m/%d/%Y %I:%M %p',$user['blockedafter']) : '';
$user['lastlogin'] = !empty($user['lastlogin']) ? strftime('%m/%d/%Y',$user['lastlogin']) : '';

mysql_close($db);
echo toJSON($response);


