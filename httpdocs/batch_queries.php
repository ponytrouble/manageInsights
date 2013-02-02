<?php
/** 
 * @file
 * This file is called nightly several times. It creates the batch of queries to run, 
 * if there are none for the day. Then, executes each query and stores the results. The 
 * bath is based on a count for a day because metrics only change daily. Ie queries 
 * are only needed each midnight. 
 * 
 */ 
header('Content-type: text/plain');
$startTime = microtime(true);
$startDate = date('r');
$message = "\n\t\tNothing to do.";
$email = false; 

chdir(dirname(__FILE__)); 
require_once 'includes/scgaBatch.php';

$scga = new scgaBatch();

if(!$scga->countBatched) {
	$scga->batchQueueJobs();
	$message = "\n\t\tCreated batch. ". (int)$scga->countBatched ." queries batched, ". (int)$scga->countRemaining ." remaining.\n\n\n";
	$email = true; 
} 
elseif($scga->countRemaining) {
	$scga->batchRunAll(); 
	$message = $scga->batchReport();
	$email = true; 
}



$message = 'START TIME: '. $startDate .' EXEC TIME: '. (microtime(true) - $startTime) .'sec MEM USE: '. round(memory_get_usage() / 1048576, 2) .'Mb MEM PEAK: '.  round(memory_get_peak_usage() / 1048576, 2) .'Mb'. "\n\n". $message;
if($email) {
	mail('nick.meyer@springcreekgroup.com, abaca@springcreekgroup.com, smelancon@springcreekgroup.com, Yesenia.Garcia@springcreekgroup.com', '[scgtrout] php '. __FILE__, $message);
	print $message ."\n\n";
	print_r($scga);
}



// print $message . print_r($scga, true);
