<?php
/** 
 * @file
 * Jobs to batch for FB each night. Jobs query the FB Graph API. 
 * They run nightly or, run once, or get deleted. 
 */

class scgaJobs  
{
	function __construct() 
	{
	}

	public function getJob($jobId) 
	{
		$query = sprintf('SELECT * FROM scga_jobs WHERE id="%d" AND is_deleted IS NULL LIMIT 1', $jobId); 
		return mysql_fetch_assoc(mysql_query($query)); 
	}
	
	public function addJob($params = array())
	{
		$query = sprintf('INSERT INTO scga_jobs (application, start_date, end_date) VALUES ("%d", "%s", "%s")', 
			$params['application'], $params['start_date'], $params['end_date']);
		return mysql_query($query);
	}
	
	public function deleteJob($jobId)
	{
		$query = sprintf('UPDATE scga_jobs SET is_deleted="1" WHERE id="%d" LIMIT 1', $jobId);
		return mysql_query($query);
	}
	
	public function markBatched($jobId)
	{
		$query = sprintf('UPDATE scga_jobs SET is_batched="1" WHERE id="%d" LIMIT 1', $jobId);
		return mysql_query($query);
	}

	public function getJobs($deletedJobs = false) 
	{
		$query = 'SELECT j.*, a.name FROM scga_jobs j JOIN scga_accounts a ON j.application=a.application WHERE j.is_deleted IS NULL GROUP BY j.id ORDER BY j.id'; 
		if($deletedJobs) { 
			$query = 'SELECT j.*, a.name FROM scga_jobs j JOIN scga_accounts a ON j.application=a.application GROUP BY j.id ORDER BY j.id'; 
		}
		$result = mysql_query($query);
		while($data = mysql_fetch_assoc($result)) {
			$this->jobs[] = $data;
		}
		return (array)$this->jobs; 
	}
	public function getNewJobs() 
	{
		$query = 'SELECT j.*, a.name FROM scga_jobs j JOIN scga_accounts a ON j.application=a.application WHERE j.is_deleted IS NULL AND (j.is_batched IS NULL OR j.is_batched=0) GROUP BY j.id ORDER BY j.id'; 
		$result = mysql_query($query);
		while($data = mysql_fetch_assoc($result)) {
			$jobs[] = $data;
		}
		return $jobs; 
	}
	
	
}
