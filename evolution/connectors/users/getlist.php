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

/* setup default properties */
$isLimit = !empty($scriptProperties['limit']);
$start = $scriptProperties['start'];
$limit = $scriptProperties['limit'];
$username = $scriptProperties['username'];
$sort = 'username';
$dir = 'ASC';
$manager_users = array();
$web_users = array();

/* Get all the users */
$db = mysql_connect($database_server, $database_user, $database_password);
if (!$db) die("Revo Gateway API error - No server :- $database_server, $databse_user, $databse_password");

$dbase = str_replace('`', '', $dbase);
$db_selected = mysql_select_db($dbase, $db);
if (!$db_selected) die ("Revo Gateway API error - No database :- $dbase");

if ( $username != '' ) {

    $likeName = '%' . $username . '%';
    $sql = "SELECT * FROM " . $table_prefix . "manager_users" .
       " WHERE `username` LIKE " . "'". $likeName . "'";
    $result = mysql_query($sql, $db);
    while ($manageruser = mysql_fetch_assoc($result)) {
        $manager_users[] = $manageruser;
    }

    $sql = "SELECT * FROM " . $table_prefix . "web_users" .
       " WHERE `username` LIKE " . "'". $likeName . "'";
    $result = mysql_query($sql, $db);
    while ($webuser = mysql_fetch_assoc($result)) {
        $web_users[] = $webuser;
    }

} else {

    $sql = "SELECT * FROM " . $table_prefix . "manager_users";
    $result = mysql_query($sql, $db);
    while ($manageruser = mysql_fetch_assoc($result)) {
        $manager_users[] = $manageruser;
    }

    $sql = "SELECT * FROM " . $table_prefix . "web_users";
    $result = mysql_query($sql, $db);
    while ($webuser = mysql_fetch_assoc($result)) {
        $web_users[] = $webuser;
    }

}

/* Get the user details */
foreach ( $manager_users as $manager_user ) {
    
    $sql =  "SELECT fullname, email, blocked FROM " . $table_prefix . "user_attributes" .
            " WHERE `id` = " . $manager_user['id'];

    $result = mysql_query($sql, $db);
    while ($manageruser = mysql_fetch_assoc($result)) {

        $manageruser['username'] = $manager_user['username'];
        $manageruser['id'] = $manager_user['id'];
        if ( $manageruser['blocked'] == 1 ) { 
			$manageruser['true'];
		} else {
			$manageruser['blocked'] = false;
		}
        $users[] = $manageruser;
    }

}

foreach ( $web_users as $web_user ) {

    $sql =  "SELECT fullname, email, blocked FROM " . $table_prefix . "web_user_attributes" .
            " WHERE `id` = " . $web_user['id'];

    $result = mysql_query($sql, $db);
    while ($webuser = mysql_fetch_assoc($result)) {

        $webuser['username'] = $web_user['username'];
        $webuser['id'] = $web_user['id'] . '_w';
        if ( $webuser['blocked'] == 1 ) { 
			$webuser['true'];
		} else {
			$webuser['blocked'] = false;
		}
        $users[] = $webuser;
    }

}

mysql_close($db);
$count = count($users);
$response = '({"total":"'.$count.'","results":'.json_encode($users).'})';
echo $response;
