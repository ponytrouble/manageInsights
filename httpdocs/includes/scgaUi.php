<?php
/** 
 * @file
 * Class file with helpers for UI. 
 * 
 * Requires user is requesting from office IP address or has a 
 * Facebook Developer account for this Facebook application. 
 * 
 * 
 */ 
require_once 'scgaAccounts.php';
require_once 'scgaBatch.php';


class scgaUi extends scgaAccounts
{
	public $user;
	public $messages = array();
	public $errors = array();
	public $url = 'http://50.57.106.106/analytics/facebook';
	
	function __construct() 
	{
		parent::__construct();
		session_start();
		ob_start();
		$this->batch = new scgaBatch();
		// redirecting to box because of load balancer problem
		if($_SERVER['HTTP_HOST'] == 'springcg.com') { 
			header('Location: '. $this->url);
			exit();
		}
		if(!$this->userAccess()) {
			$m = 'Valid request or developer account needed.' . (empty($this->errors) ? '' : ' '. implode("\n", $this->errors));
			exit($m);
		}
		if(!empty($_REQUEST['action'])) {
			$this->handleActions();
			header('Location: '. $this->url);
			exit();
		}
		if(!empty($_SESSION['scga']['messages'])) {
			$this->messages = array_merge($this->messages, $_SESSION['scga']['messages']);
			unset($_SESSION['scga']['messages']);
		}

		
		$this->getUser();
		$this->getAccounts(true);
		$this->jobs->getJobs(true);
		$this->silos->getSilos();
		$this->sortJobs(); 
		$this->getReports();
	}
	
	public function handleActions()
	{
		switch($_REQUEST['action']) 
		{
			case 'add-accounts': 
				$this->getUser();
				$save = array();
				foreach($this->user['accounts']['data'] as $userAccount) {
					if(in_array($userAccount['id'], $_REQUEST['account-id'])) {
						$save[] = $userAccount;
					}
				}
				$this->deleteUserAccounts($this->user['id']);
				$this->addAccounts($save);
				$_SESSION['scga']['messages'][] = 'Account(s) added';
				$_SESSION['scga']['messages'][] = $this->log; 
			break;
			case 'add-account-secret': 
				$this->addAccountSecret($_REQUEST['account-id'], $_REQUEST['account-secret']);
				$_SESSION['scga']['messages'][] = 'Account secret added';
				break; 
			case 'delete-account': 
				foreach($_REQUEST['account-id'] as $id) {
					$this->deleteAccount($id);
				}
				$_SESSION['scga']['messages'][] = 'Account(s) deleted'; 
				break; 
			case 'add-job': 
				$job = array('application' => $_REQUEST['account-id'], 'start_date' => $_REQUEST['start-date'], 'end_date' => $_REQUEST['end-date']);
				$this->jobs->addJob($job);
				$_SESSION['scga']['messages'][] = 'New Job added';
			break;
			case 'delete-job':
				$this->jobs->deleteJob($_REQUEST['job-id']);
				$_SESSION['scga']['messages'][] = 'Deleted job';
				break; 
			case 'add-silo':
				$this->silos->addSilo($_REQUEST['silo-name']);
				$_SESSION['scga']['messages'][] = 'Added silo';
			break; 
			case 'delete-silo':
				$this->silos->deleteSilo($_REQUEST['silo-id']);
				$_SESSION['scga']['messages'][] = 'Deleted silo';
			break; 
			case 'add-account-silo':
				foreach($_REQUEST['account-id'] as $id) {
					$this->silos->addAccountSilo($id, $_REQUEST['silo-id']);
				}
				$_SESSION['scga']['messages'][] = 'Added account(s) to silo';
			break; 
			case 'remove-account-silo':
				foreach($_REQUEST['account-id'] as $id) {
					$this->silos->removeAccountSilo($id);
				}
				$_SESSION['scga']['messages'][] = 'Account(s) removed from silo';
			break; 
			case 'batch-new-jobs':
				$this->batch->batchQueueNewJobs();
				$_SESSION['scga']['messages'][] = 'Batched new jobs. '. $this->batch->countBatched .' queries batched and '. $this->batch->countRemaining .' remaining'; 
			break;  
			case 'batch-all-jobs':
				$this->batch->batchQueueJobs();
				$_SESSION['scga']['messages'][] = 'Batched all jobs. '. $this->batch->countBatched .' queries batched and '. $this->batch->countRemaining .' remaining'; 
			break; 
			case 'batch-run-all': 
				$this->batch->batchRunAll(); 
				$message = $this->batch->batchReport();
				mail('nick.meyer@springcreekgroup.com, abaca@springcreekgroup.com, smelancon@springcreekgroup.com', '[scgtrout] php '. __FILE__, $message);
				$_SESSION['scga']['messages'][] = 'Batch Run. '.  $message;
			break; 
			case 'reports-generate': 
				$this->batch->batchReport();
				$_SESSION['scga']['messages'][] = 'Report created.'; 
			break; 
		}
	}
	
	public function sortJobs() {
		$this->oneTimeJobs = array(); 
		$this->recurringJobs = array(); 
		foreach((array)$this->jobs->jobs as $job) { 
			$job['start_date'] = $job['start_date'] ? date('Y-m-d', strtotime($job['start_date'])) : '-'; 
			$job['end_date'] = $job['end_date'] ? date('Y-m-d', strtotime($job['end_date'])) : '-'; 
			$job['is_deleted'] = $job['is_deleted'] ? '1' : '-';
			$job['is_batched'] = $job['is_batched'] ? '1' : '-';
			if($job['start_date'] != '-' || $job['end_date'] != '-') { 
				$this->jobs->oneTimeJobs[ $job['id'] ] = $job;
			} 
			else { 
				$this->jobs->recurringJobs[ $job['id'] ] = $job;
			}
		}
	}
	
	public function getReports() {
		$this->reports = array(); 
		if ($handle = opendir('./reports')) {
			while (false !== ($i = readdir($handle))) {
				if(strpos($i, 'report')) {
					$reports[] = $i;  
				}
			}
			sort($reports);
			$this->reports = array_reverse($reports);
			closedir($handle);
		}
	}
}










