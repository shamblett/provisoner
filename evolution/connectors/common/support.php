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

