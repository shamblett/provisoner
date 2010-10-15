<?php
/**
 * Provisoner all keywords evolution component
 *
 * @category  Provisioning
 * @author    S. Hamblett <steve.hamblett@linux.com>
 * @copyright 2010 S. Hamblett
 * @license   GPLv3 http://www.gnu.org/licenses/gpl.html
 * @link      none
 *
 * @package provisioner
 *
 *
 */

$keywordArray = array();
$outputArray = array();

/* Protection */
if(REVO_GATEWAY_OPEN != "true") die("Revo Gateway API error - Invalid access");

/* Get the keywords from the database */
$db = mysql_connect($database_server, $database_user, $database_password);
if (!$db) die("Revo Gateway API error - No server :- $database_server, $databse_user, $databse_password");

$dbase = str_replace('`', '', $dbase);
$db_selected = mysql_select_db($dbase, $db);
if (!$db_selected) die ("Revo Gateway API error - No database :- $dbase");

/* Get the keywords */
$sql = "SELECT keyword, content_id FROM " . $table_prefix . "site_keywords";
$sql .= " LEFT JOIN " . $table_prefix . "keyword_xref on id = keyword_id";
$result = mysql_query($sql, $db);
if (!$result) die("Revo Gateway API error - Invalid Keyword query");
while ($keyword = mysql_fetch_assoc($result)) {

    /* UTF8 encode character fields */
    $keyword['keyword'] = utf8_encode($keyword['keyword']);
    $keywordArray[] = $keyword;
}

/* Assemble the output array, list of mapped resources indexed by keyword */
foreach ( $keywordArray as $keyword ) {

    $outputArray[$keyword['keyword']][] = $keyword['content_id'];
}

$response = errorSuccess('',$outputArray);
mysql_close($db);
echo toJSON($response);
