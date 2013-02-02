<?php
/** 
 * @file
 * Test file that creates batch (stored queries) for all current jobs,
 * The runs the batch storing results.
 */ 

header('Content-type: text/plain');
chdir(dirname(__FILE__)); 
require_once 'includes/scgaBatch.php';

$scga = new scgaBatch();
$scga->batchQueueJobs();
$scga->batchRunAll();


print $scga->batchReport() ."\n\n";
print_r($scga);



