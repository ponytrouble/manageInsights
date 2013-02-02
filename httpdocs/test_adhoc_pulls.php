<?php
/** 
 * @file
 * Shortcut file to put queries in the batch table, so they're run later. 
 * These pull insights for a few key metrics and history. 
 * 
 * @see https://developers.facebook.com/docs/reference/fql/ 
 */ 

header('Content-type: text/plain');

chdir(dirname(__FILE__)); 
require_once 'includes/scgaAccounts.php';

$scga = new scgaAccounts(); 
$scga->getAccounts();
$sinceDate = date('U', strtotime('7/27/2012')); // date twice to get midnight Unix time
$untilDate = date('U', strtotime('8/1/2012')); 


foreach($scga->accounts as $account) 
{
	$n = strtolower($account['name']);
	foreach(array('Micro', 'American', 'IHOP', ) as $v) 
	{ 
		if(stripos($n, strtolower($v)) !== false && $account['type'] != 'Application') { 
			print "\n\n###". $account['application'] .' ##'. $account['name'] ."\n". quickQueries($account['application'], $sinceDate, $untilDate); 
		}
	}
}




function historyQueries($appId) 
{
	$q = "insert scga_batch (application, query) values
		('$appId', '/$appId/insights'),
		('$appId', '/$appId/insights?period=lifetime'),

		('$appId', '/$appId/insights?since=1322697600&until=1325376000'),
		('$appId', '/$appId/insights?since=1325376000&until=1328054400'),
		('$appId', '/$appId/insights?since=1328054400&until=1330560000'),
		('$appId', '/$appId/insights?since=1330560000&until=1333238400'),
		('$appId', '/$appId/insights?since=1333238400&until=1335830400'),
		('$appId', '/$appId/insights?since=1335830400&until=1338508800'),
		"; 
	foreach(array('page_stories', 'page_active_users', 'page_fans', 'page_impressions') as $v) {
		$q .= "
		('$appId', '/$appId/insights/$v?since=1322697600&until=1325376000'),
		('$appId', '/$appId/insights/$v?since=1325376000&until=1328054400'),
		('$appId', '/$appId/insights/$v?since=1328054400&until=1330560000'),
		('$appId', '/$appId/insights/$v?since=1330560000&until=1333238400'),
		('$appId', '/$appId/insights/$v?since=1333238400&until=1335830400'),
		('$appId', '/$appId/insights/$v?since=1335830400&until=1338508800'),"; 
	}
	return rtrim($q, ',') .'; ';
}

function quickQueries($appId, $sinceDate, $untilDate) 
{	
	$q = "##Since ". date('r', $sinceDate) ." ##Until". date('r', $untilDate) ."\n";
	$q .= "insert scga_batch (application, query) values
	('$appId', '/$appId/insights'),
	('$appId', '/$appId/insights?period=lifetime'),
	('$appId', '/$appId/insights?since=". $sinceDate ."&until=". $untilDate ."'),
	"; 
	foreach(array('page_stories', 'page_active_users', 'page_fans', 'page_impressions', 'page_fan_adds', 'page_impressions_unique', 'page_impressions_viral_unique') as $v) {
		$q .= "
		('$appId', '/$appId/insights/$v?since=". $sinceDate ."&until=". $untilDate ."'),"; 
	}
	return rtrim($q, ',') .', ';
}













