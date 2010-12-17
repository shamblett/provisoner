<?php
/**
 * Provisoner support evolution component
 *
 * @category  Provisioning
 * @author    S. Hamblett <steve.hamblett@linux.com>
 * @copyright 2010 S. Hamblett
 * @license   GPLv3 http://www.gnu.org/licenses/gpl.html
 * @link      none
 *
 * @package provisioner
 *
 * /

/* General support functions for the Revolution gateway processor */

include_once 'encode.php';

function errorFailure($message = '', $object = null) {

    return processResponse($message, false, $object);
}

function errorSuccess($message = '', $object = null) {

    return processResponse($message, true, $object);
}

function processResponse($message = '', $status = false, $object = null) {

    $total = 0;

    if ( !$status ) $total = 1;
    return array (
            'success' => $status,
            'message' => $message,
            'total' => $total,
            'object' => $object,
        );
}

function outputArray($output) {
	
	$count = count($output);
	$response = '({"total":"'.$count.'","results":'.toJSON($output).'})';
	return $response;
}

function connectToDb() {

    global $database_server;
    global $database_user;
    global $database_password;
    global $dbase;
    global $database_connection_charset;
    
    /* Server */
    $db = mysql_connect($database_server, $database_user, $database_password);
    if (!$db) die("Revo Gateway API error - No server :- $database_server, $databse_user, $databse_password");

    /* Database */
    $dbase = str_replace('`', '', $dbase);
    $db_selected = mysql_select_db($dbase, $db);
    if (!$db_selected) die ("Revo Gateway API error - No database :- $dbase");

    /* Set the character collation */
    mysql_set_charset($database_connection_charset,$db);

    return $db;


}

function logImportEvent($msg, &$db) {

        global $table_prefix;
        
        $evtid = 0;
        $type = 1;
        $source = 'RevoGateway';
        $LoginUserID = 0;
        $sql= "INSERT INTO " . $table_prefix . "event_log" . " (eventid,type,createdon,source,description,user) " .
	          "VALUES($evtid,$type," . time() . ",'$source','$msg','" . $LoginUserID . "')";
        mysql_query($sql, $db);
    }

