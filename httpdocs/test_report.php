<?php
/** 
 * @file
 * Quick view of batch report. 
 * 
 */ 
header('Content-type: text/plain');
chdir(dirname(__FILE__)); 
require_once 'includes/scgaBatch.php';

$scga = new scgaBatch();
print $scga->batchReport();
print "\n\n\nDEBUG:\n". print_r($scga, true);


