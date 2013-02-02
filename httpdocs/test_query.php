<?php
/** 
 * @file
 * Sample file to access the scga class methods. This file calls our custom 
 * fbQuery() method to run fql.query SQL against Facebook tables. Custom queries 
 * can be used or pull a query string like $scga->queryApplication()
 * 
 * @see https://developers.facebook.com/docs/reference/fql/ 
 */ 
header('Content-type: text/plain');

chdir(dirname(__FILE__)); 
require_once 'includes/scgaQuery.php';

$scga = new scgaQuery(); 
$id = 288606834496713; 



$q = $scga->queryApplication($id);
$result = $scga->fbQuery($id, $q); 
//$scga->saveResults($id, $result); 
print_r(array($q, $result));



$q = $scga->queryInsights($id);
foreach($q as $j) {
	$result = $scga->fbQuery($id, $j); 
	//$scga->saveResults($id, $result); 
	print_r(array($j, $result));
}



/* 
$q = 'SELECT metric, value, period, end_time FROM insights WHERE object_id="'. $id .'" 
			AND metric="page_impressions" AND period=period("week") AND end_time=end_time_date("2011-10-30")';
$q = 'SELECT metric, value, period, end_time FROM insights WHERE object_id="'. $id .'" 
			AND metric="page_views" AND period=period("day") AND end_time=end_time_date("2011-10-30")';
$q = 'SELECT metric, value, period, end_time FROM insights WHERE object_id="'. $id .'"
			AND metric="application_tab_views" AND period=period("day") AND end_time=end_time_date("2011-10-30")';
$q = 'SELECT metric, value, period, end_time FROM insights WHERE object_id="'. $id .'" 
			AND metric="application_canvas_views" AND period=period("day") AND end_time=end_time_date("2011-11-01")';
$result = $scga->runQuery($id, $q); 
*/


print_r($scga);





