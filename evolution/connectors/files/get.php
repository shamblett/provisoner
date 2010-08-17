<?php
/**
 * Provisoner files evolution component
 *
 * @category  Provisioning
 * @author    S. Hamblett <steve.hamblett@linux.com>
 * @copyright 2010 S. Hamblett
 * @license   GPLv3 http://www.gnu.org/licenses/gpl.html
 * @link      none
 *
 * @package provisioner
 *
 */

/* Protection */
if(REVO_GATEWAY_OPEN != "true") die("Revo Gateway API error - Invalid access");

include "common/support.php";

/* format filename */
$file= rawurldecode($scriptProperties['file']);

if (!file_exists($file)) {
    $response = errorFailure(" : No file found : ",
                        array('name' => $file));
     echo json_encode($response);
     return;
}

$filename = ltrim(strrchr($file,'/'),'/');

$fbuffer = @file_get_contents($file);
$time_format = '%b %d, %Y %H:%I:%S %p';

$fa = array(
    'name' => utf8_encode($filename),
    'size' => filesize($file),
    'last_accessed' => strftime($time_format,fileatime($file)),
    'last_modified' => strftime($time_format,filemtime($file)),
    'content' => $fbuffer,
);

$response = errorSuccess('', $fa);
echo json_encode($response);
