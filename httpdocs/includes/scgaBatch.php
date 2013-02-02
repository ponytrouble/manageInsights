<?php
/** 
 * @file
 * Class file that gets called each night to record and run queries. 
 * We use the jobs table to for applications to query, then record 
 * all the same queries for each job. 
 * 
 * If a job has a start or end date, honor those dates by going back 
 * for information and storing more queries. 
 * 
 */ 
require_once 'scgaQuery.php';
require_once 'scgaJobs.php';

class scgaBatch extends scgaQuery
{
	public $countBatched;
	public $countStarting;
	public $countRemaining; 
	public $countRunMaxium = 400;
	
	function __construct() 
	{
		parent::__construct();
		$this->countBatched();
		$this->countRemaining();
		$this->countStarting = $this->countRemaining;
		$this->batchReportJs();
	}

	/* 
	 * Queries count. Metrics are only available per midnight so use day as a marker 
	 */ 
	public function countBatched() {
		$result = mysql_query('SELECT count(*) as count FROM scga_batch WHERE DATE(date_updated)=DATE(NOW())');
		$data = mysql_fetch_assoc($result);
		$this->countBatched = $data['count'];
	}

	/* 
	 * Queries count. All the queries that didn't get run 
	 */ 
	public function countRemaining() 
	{
		$result = mysql_query('SELECT count(*) as count FROM scga_batch WHERE query_result IS NULL');
		$data = mysql_fetch_assoc($result);
		$this->countRemaining = $data['count'];
		return $data['count'];
	}

	/* 
	 * Store a query to run, incase some fail or a time-out 
	 */
	public function batchItemAdd($accountId = null, $queryItem = null) 
	{
		if(is_array($queryItem)) {
			foreach($queryItem as $q) {
				$this->batchItemAdd($accountId, $q);
			}
			return; 
		}
		if(empty($queryItem)) {
			return;
		}
		$queryType = stripos(' '. $queryItem, 'select') ? 'fql.query' : null; 
		$query = sprintf('INSERT INTO scga_batch (application, query, query_type, date_added) VALUES ("%d", "%s", "%s", NOW())', $accountId, addslashes($queryItem), $queryType);
		$result = mysql_query($query);
		$this->countRemaining();
		$this->batchReportJs();
		return $result; 
	}
	
	/* 
	 * Store a query to run, incase some fail or a time-out 
	 */
	public function batchItemUpdate($batchId = null, $result = null, $message = null) 
	{
		$result = (is_array($result) || is_object($result)) ? json_encode($result) : $result; 
		$query = sprintf('UPDATE scga_batch SET query_result="%s", message="%s" WHERE id="%d"', addslashes($result), addslashes($message), $batchId);
		$result = mysql_query($query);
		$this->countRemaining();
		$this->batchReportJs();
		return $result; 
	}

	/* 
	 * Iterate jobs, store queries and mark jobs batched 
	 */ 
	public function batchQueueJobs($jobs = array()) 
	{
		$jobs = empty($jobs) ? $this->jobs->getJobs() : $jobs;
		foreach($jobs as $job) 
		{
			$accountId = $job['application']; 
			$nowDate = new DateTime(); // store time for consistency 
			$endDate = new DateTime($job['end_date']);
			$startDate = new DateTime($job['start_date']); 
			if($startDate && $startDate->format('U') > $nowDate->format('U')) { // it hasn't come yet, skip this one  
				continue; 
			}
			if($endDate && $endDate->format('U') < $nowDate->format('U')) { // end_date has passed, don't run this next time 
				$this->jobs->deleteJob($job['id']); 
			}
			if(!$job['is_batched'] && ($startDate || $endDate)) { // history has not run  
				$this->queryInsightsRange($accountId, $startDate, $endDate); 
			}
			$this->batchQueueAccount($accountId);  
			$this->jobs->markBatched($job['id']); 
		}
		$this->countBatched();
		$this->countRemaining();
		$this->batchReportJs();
	}
	
	public function batchQueueNewJobs() 
	{
		$jobs = $this->jobs->getNewJobs(); 
		$this->batchQueueJobs($jobs);
	}
	
	/* 
	 * Store queries for an account
	 */ 
	public function batchQueueAccount($accountId) 
	{
		$this->batchItemAdd($accountId, $this->queryInfo($accountId));
		$this->batchItemAdd($accountId, $this->queryInsights($accountId));
		$this->batchItemAdd($accountId, $this->queryFeed($accountId));
		$this->batchItemAdd($accountId, $this->queryPostInsights($accountId));
	}

	/* 
	 * Store queries for range of time, as in /12345/insights/ for a 30day span 
	 */ 
	public function queryInsightsRange($accountId, $startDate = null, $endDate = null) 
	{
		$account = $this->getAccount($accountId);
		while($startDate->format('U') < $endDate->format('U')) 
		{
			if($account['category'] == 'Application') {
				$end = $startDate->format('U'); 
				$startDate->modify('+1 day'); // fql.query can only pull a day at a time 
			} 
			else { 
				$start = $startDate->format('U');
				$startDate->modify('+30 day'); // graph paths can pull up to 90 days at a time 
				$end = min($startDate->format('U'), $endDate->format('U'));
			}
			$query = $this->queryInsights($accountId, $start, $end);
			$this->batchItemAdd($accountId, $query);
		}
	}
		 
	/* 
	 * Run queries that haven't been run, store the results and update the batch item 
	 */ 
	public function batchRunAll()
	{
		$result = mysql_query(sprintf('SELECT * FROM scga_batch WHERE query_result IS NULL ORDER BY date_updated DESC LIMIT %d', $this->countRunMaxium));
		while($data = mysql_fetch_assoc($result)) {
			$_result = $_message = null; 
			if(stripos(' '. $data['query_type'], 'fql') || stripos(' '. $data['query'], 'select ')) { 
				if(empty($account['secret'])) { 
					$_result = 'ERROR'; 
					$_message = 'Skipping. No account secret stored.'; 
				}
				else {
					$_result = $this->fbQuery($data['application'], $data['query']);
				}
			}
			else {
				$_result = $this->fbGraph($data['application'], $data['query']);
			}
			if(is_object($_result) && $_result instanceof Exception) { 
				$_message = 'ERROR'. (method_exists($_result, 'getMessage') ? ' '. $_result->getMessage() : '');
				$_result = 'ERROR';
			}
			if(!empty($_result) && $_result != 'ERROR') {
				$this->saveResults($data['application'], $_result); 
			}
			$this->batchItemUpdate($data['id'], $_result, $_message);
			$f = dirname(__FILE__) .'/temp/'. trim(preg_replace('/[^a-zA-Z0-9]/', '-', $data['query']), '-') .'.txt';
			file_put_contents($f .'.json', json_encode($_result)); 
			usleep(50000);
		}
	}
	
	public function batchReport()
	{
		$appData = array();
		$reportFile = 'reports/batch_report.'. date('Y-m-d') .'.'. microtime(true); 
		
		// get new messages for queries today 
		$result = mysql_query('SELECT a.application, a.name, a.category, a.user_email AS owner_email, b.query, b.message FROM scga_batch b JOIN scga_accounts a USING (application) WHERE b.message IS NOT NULL AND b.message!="" AND DATE(b.date_updated)=DATE(NOW()) GROUP BY b.application, b.message');
		while($d = mysql_fetch_assoc($result)) 
		{
			foreach($d as $k => $v) {
				$s .= ucwords(str_replace('_', ' ', $k)) .": $v. ";
			}
			$messages .= "\n\n\t***Message: ". $s; $s = '';
		}

		$o[] = mysql_fetch_assoc(mysql_query('SELECT count(*) as stored_results FROM scga_results'));
		$o[] = mysql_fetch_assoc(mysql_query('SELECT count(*) as batched_queries FROM scga_batch'));
		$o[] = mysql_fetch_assoc(mysql_query('SELECT count(*) as batched_remaining FROM scga_batch WHERE query_result IS NULL'));
		$o[] = mysql_fetch_assoc(mysql_query('SELECT count(*) as batch_added_today FROM scga_batch WHERE DATE(date_added)=DATE(NOW())'));
		$o[] = mysql_fetch_assoc(mysql_query('SELECT count(*) as batch_run_today FROM scga_batch WHERE query_result IS NOT NULL AND DATE(date_added)=DATE(NOW())'));
		$o[] = mysql_fetch_assoc(mysql_query('SELECT count(*) as queries_today FROM scga_batch WHERE query IS NOT NULL AND query!="" AND DATE(date_added)=DATE(NOW())'));

		$s = '';
		$s .= "\n\t\tBATCH REPORT. ". date('r') ."\n\t\tThis report is online at http://springcg.com/analytics/facebook/". $reportFile; 
		$s .= "\n\nBATCH DETAIL: "; 
		foreach($o as $key => $val) { 
			foreach($val as $k => $v) {
				$s .= ucwords(str_replace('_', ' ', $k)) .": $v. ";
			}
		}
		$s .= "\n\nMESSAGES TODAY: ". $messages;
		$s .= "\n\nDATA DUMP: ". print_r($this, true); 
		file_put_contents($reportFile, $s, LOCK_EX);
		return $s; 
	}
	
	public function batchReportJs() 
	{
		$js = array('countBatched' => $this->countBatched, 'countStarting' => $this->countStarting, 'countRemaining' => $this->countRemaining, 'countRunMaxium' => $this->countRunMaxium);
		file_put_contents(dirname(__FILE__) .'/js/progress.js', json_encode($js)); 
	}

}
