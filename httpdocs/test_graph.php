<?php
/** 
 * @file
 * Sample file to access the scga class methods. This file calls our custom 
 * fbGraph() method which creates a Facebook object and calls the Graph API path. 
 * 
 */ 
header('Content-type: text/plain');

chdir(dirname(__FILE__)); 
require_once 'includes/scgaBatch.php';

$scga = new scgaBatch(); 
$id = 101063233083; //178191330369 //222749621090228; 


$path = $id .'/insights/page_like_adds/'; 
$result = $scga->fbGraph($id, $path, array('since'=>'Jan 15, 2012', 'until'=>'Mar 1, 2012', 'period'=>'day'));
print_r(array($path, $result));

$path = $scga->queryInfo($id);
print_r(array($path, $scga->fbGraph($id, $path)));

$path = $scga->queryFeed($id);
print_r(array($path, $scga->fbGraph($id, $path)));

$path = $scga->queryInsights($id);
print_r(array($path, $scga->fbGraph($id, $path)));



/* 
$path = $id . '/insights/page_fan_adds_unique'; 
$results[] = $result = $scga->fbGraph($id, $path);
$scga->saveResults($id, $result); 

$path = $id . '/insights/page_like_adds/'; 
$results[] = $result = $scga->fbGraph($id, $path, array('since'=>$dateSince, 'until'=>$dateUntil, 'period'=>'day'));
$scga->saveResults($id, $result); 

$path = $id . '/insights'; 
$results[] = $result = $scga->fbGraph($id, $path);
$scga->saveResults($id, $result); 

$path = $id . '/insights'; 
$results[] = $result = $scga->fbGraph($id, $path, array('since'=>$dateSince, 'until'=>$dateUntil));
$scga->saveResults($id, $result); 

*/


print_r($results);
print_r($scga);

