<?php
/** 
 * @file
 * Class file that manages our accounts and jobs. 
 *
 * Accounts are used for access information with a applicationId and 
 * maybe application secret. We request "offline access" and 
 * "manage pages" permission to get those for each user's accounts. 
 * 
 * Jobs are stored with a applicationId, startDate and endDate. The batch script 
 * uses these jobs to create and run all the special queries wanted. 
 * 
 */ 
require_once '../../library/facebook/facebook.php';
require_once 'scgaJobs.php';
require_once 'scgaSilos.php';


class scgaAccounts 
{
	public $user;
	public $accounts; 
	public $jobs; 
	public $errors; 
	public $log; 

	function __construct() 
	{
		$this->jobs = new scgaJobs();
		$this->silos = new scgaSilo();
		$this->getDb();
		$this->getFacebook();
	}
	
	/* 
	 * Connect to host and database 
	 */
	public function getDb() 
	{
		mysql_connect('localhost', 'springcreek', 'spring!qazxcvb');
		mysql_select_db('springcreek'); 
	}

	/* 
	 * Get a facebook object for queries 
	 */
	public function getFacebook($appId = null, $secret = null) 
	{
		$params = array('appId'=>'1111111111', 'secret'=>'22222222222222222222222222', 'cookie'=>true,);
		if($appId && $secret) {
			$params = array('appId'=>$appId, 'secret'=>$secret, 'cookie'=>true,);
		}
		$facebook = new Facebook($params);
		$facebook->getAccessToken();
		$facebook->getSignedRequest(); 
		if(empty($this->facebook)) {
			$this->facebook = $facebook; 
		}
		return $facebook; 
	}

	/* 
	 * Check permissions to download. App should be in "sanbox mode" so only admins view
	 */ 
	protected function userAccess()
	{
		$access = false; 
		if($_SERVER['REMOTE_ADDR'] == '67.139.99.226') {
			$access = true; 
		}
		if($_SERVER['SCRIPT_NAME'] == '/analytics/facebook/scrape_accounts.php') {
			$access = true; 
		}
		if(!$access) {
			if($this->getUser() && $this->user['id']) { 
				$result = $this->facebook->api(array(
					'method' => 'fql.query',
					'query'  => 'SELECT developer_id FROM developer WHERE application_id="'. $this->facebook->getAppId() .'" AND developer_id="'. $this->user['id'] .'"',
				));
				$access = !empty($result[0]['developer_id']) ? true : false;
			}
		}
		return $access;
	}
	
	/* 
	 * Get user information from Facebook
	 */ 
	public function getUser() 
	{
		$this->user = $this->facebook->getUser(); // 0 or 1234567
		if(!$this->user) {
			$a = $this->facebook->getLoginUrl() .'&scope=email,sms,offline_access,read_insights,manage_pages,publish_stream';
			$this->errors[] = 'Missing user. Redirecting for authoriation: '. $a .'<script>top.location.href="'. $a .'"</script>';
		}
		try {
					$this->user = $this->facebook->api('me');
					$this->user['accounts'] = $this->facebook->api('me/accounts');
		}
		catch (FacebookApiException $e) {
				error_log(json_encode($o));
				$this->errors[] = $e->getMessage();
		}
		return $this->user;
	}
	
	/*
	 * Add Facebook user to the database 
	 */ 
	public function addUser()
	{
		if(empty($this->user['accounts'])) { 
		   $this->getUser(); 
		}
		$query = sprintf('INSERT INTO scga_accounts (user, name) VALUES ("%d", "%s")', $this->user['id'], $this->user['name']); 
		return mysql_query($query);
	}
	
	/* 
	 * Get saved accounts with access tokens 
	 */ 
	public function getAccounts() 
	{
		$result = mysql_query('SELECT * FROM scga_accounts WHERE is_deleted IS NULL GROUP BY application ORDER BY name ASC');
		while($data = mysql_fetch_assoc($result)) {
			$this->accounts[] = $data;
		}
		return empty($this->accounts) ? array() : $this->accounts;
	}
	
	public function getAccount($accountId)
	{
		$query = sprintf('SELECT * FROM scga_accounts WHERE application="%d" AND is_deleted IS NULL ORDER BY date_updated DESC LIMIT 1', $accountId); 
		$data = mysql_fetch_assoc(mysql_query($query)); 
		return $data; 
	}
	
	/* 
	 * Add user Facebook applications to database with access tokens 
	 */ 
	public function addAccounts($accounts = array())
	{
		foreach($accounts as $account) {
			$this->addAccount($account); 
		}
		return $this->getAccounts(); 
	}
	
	/* 
	 * Add user application for api calls 
	 */ 
	public function addAccount($account = array())
	{
		$this->deleteAccount($account['id'], true);
		$query = sprintf('INSERT INTO scga_accounts 
			(user, user_email, application, name, category, secret, access_token, date_added) VALUES 
			("%d", "%s", "%d", "%s", "%s", "%s", "%s", NOW())', 
			$this->user['id'], $this->user['email'], $account['id'], $account['name'], $account['category'], $account['secret'], $account['access_token']); 
		$this->log[] = $query; 
		return mysql_query($query);
	}
	
	/* 
	 * Remove account so other tokens are used
	 */ 
	public function deleteAccount($accountId = null, $hardDelete = false) 
	{
		$query = sprintf('UPDATE scga_accounts SET is_deleted="1" WHERE application="%d"', $accountId);
		if($hardDelete) {
			$query = sprintf('DELETE FROM scga_accounts WHERE application="%d"', $accountId);
		}
		return mysql_query($query);
	}
	
	/* 
	 * Remove user accounts, incase they're dropped from an app
	 */ 
	public function deleteUserAccounts($userId) 
	{
		$query = sprintf('UPDATE scga_accounts SET is_deleted="1" WHERE user="%d" AND (user IS NOT NULL AND user!="")', $userId);
		return mysql_query($query);
	}
	
	/* 
	 * Add application secret for fql.query calls 
	 */ 
	public function addAccountSecret($accountId = null, $secret = null)
	{
		$query = sprintf('UPDATE scga_accounts SET secret="%s" WHERE application="%d"', addslashes(trim($secret)), $accountId);
		return mysql_query($query);
	}
}










