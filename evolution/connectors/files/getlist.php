<?php
/**
 * Provisoner files getlist evolution component
 *
 * @category  Provisioning
 * @author    S. Hamblett <steve.hamblett@linux.com>
 * @copyright 2010 S. Hamblett
 * @license   GPLv3 http://www.gnu.org/licenses/gpl.html
 * @link      none
 *
 * @package provisioner
 *
 * Get nodes for the file tree, taken from the revolution processor, adapted for
 * use in an evolution site. The JSON interface to the remote revolution site stays the
 * same.
 */

/* Protection */
if(REVO_GATEWAY_OPEN != "true") die("Revo Gateway API error - Invalid access");

function postfixSlash($path) {
    $len = strlen($path);
    if (substr($path,$len-1,$len) != '/') {
        $path .= '/';
    }
    return $path;
}

/* setup default properties */

$dir = !isset($scriptProperties['id']) || $scriptProperties['id'] == 'root' ? '' : str_replace('n_','',$scriptProperties['id']);

$directories = array();
$files = array();
$ls = array();


/* Get base path and sanitize file names */
$db = mysql_connect($database_server, $database_user, $database_password);
if (!$db) die("Revo Gateway API error - No server :- $database_server, $databse_user, $databse_password");

$dbase = str_replace('`', '', $dbase);
$db_selected = mysql_select_db($dbase, $db);
if (!$db_selected) die ("Revo Gateway API error - No database :- $dbase");

$sql = "SELECT setting_value FROM " . $table_prefix . "system_settings "
          . "WHERE `setting_name` = 'rb_base_dir'";

$result = mysql_query($sql, $db);
$item = mysql_fetch_assoc($result);
$root = $item['setting_value'];

$dir = postfixSlash($dir);
if ( $root != '') {
    $fullpath = $root.$dir;
    $relativeRootPath = postfixSlash($root);
} else {
    $fullpath = $dir;
    $relativeRootPath = '';
}

/* Iterate through directories */
$odir = dir($fullpath);
while(false !== ($name = $odir->read())) {

    if(in_array($name,array('.','..','.svn','_notes'))) continue;

    $fullname = $fullpath.'/'.$name;
    if(!is_readable($fullname)) continue;

    $fileName = $name;
    $filePathName = $fullpath;

    /* handle dirs */
    if(is_dir($fullname)) {

        $cls = 'folder';
        $directories[$fileName] = array(
            'id' => $dir.$fileName,
            'text' => $fileName,
            'cls' => $cls,
            'type' => 'dir',
            'leaf' => false,
            'perms' => '',
        );
    }

    /* get files in current dir */
    if(is_file($fullname)) {

        $ext = pathinfo($filePathName,PATHINFO_EXTENSION);
        $cls = 'icon-file icon-'.$ext;
        $files[$fileName] = array(
            'id' => $dir.$fileName,
            'text' => $fileName,
            'cls' => $cls,
            'type' => 'file',
            'leaf' => true,
            'perms' => '',
            'path' => $relativeRootPath.$fileName,
            'file' => rawurlencode($filePathName),
        );
    }
}

/* now sort files/directories */
ksort($directories);
foreach ($directories as $dir) {
    $ls[] = $dir;
}
ksort($files);
foreach ($files as $file) {
    $ls[] = $file;
}


mysql_close($db);
echo json_encode($ls);
