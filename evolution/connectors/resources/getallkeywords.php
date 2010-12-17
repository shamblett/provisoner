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
$db = connectToDb();

/* Get the keywords */
logImportEvent("Getting all keywords", $db);
$sql = "SELECT keyword, content_id FROM " . $table_prefix . "site_keywords";
$sql .= " LEFT JOIN " . $table_prefix . "keyword_xref on id = keyword_id";
$result = mysql_query($sql, $db);
if (!$result) die("Revo Gateway API error - Invalid Keyword query");
while ($keyword = mysql_fetch_assoc($result)) {

    $keywordArray[] = $keyword;
}

/* Assemble the output array, list of mapped resources indexed by keyword */
foreach ( $keywordArray as $keyword ) {

    $outputArray[$keyword['keyword']][] = $keyword['content_id'];
}

$response = errorSuccess('',$outputArray);
logImportEvent("Got all keywords", $db);
mysql_close($db);
echo toJSON($response);
