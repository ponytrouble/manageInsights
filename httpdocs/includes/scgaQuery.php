<?php
/** 
 * @file
 * Wrapper methods to define or run queries on the Facebook API.
 * If using the Graph API we use an access_token for that account. Impersonation 
 * isn't allowed for fql.query so we need an applicationId, secret and new 
 * Facebook object for those. 
 * 
 * Results come back in pretty wild arrays (formatting is not consistent) so 
 * we loop results and flatten arrays. Each result is stored as a key, value, 
 * date. 
 * 
 */ 
require_once 'scgaAccounts.php';
require_once 'scgaQuery.inc.php';

class scgaQuery extends scgaAccounts
{
	public $unixTimes = array(86400 => 'day', 604800 => 'week', 2592000 => 'month', 0 => 'lifetime');
	
	function __construct() 
	{
		parent::__construct();
	}

	/* 
	 * Query based on docs at 
	 * https://developers.facebook.com/docs/reference/fql/application/
	 */
	public function queryApplication($applicationId = null)
	{ 
		$query = sprintf('SELECT app_id, api_key, canvas_name, display_name, company_name, developers, restriction_info,
			daily_active_users, weekly_active_users, monthly_active_users
			FROM application WHERE app_id="%d"', $applicationId);
		return $query; 
	}
	
	/* 
	 * Query based on docs at 
	 * https://developers.facebook.com/docs/reference/fql/page/ 
	 */
	public function queryPage($applicationId = null)
	{ 
		$account = $this->getAccount($applicationId);
		if($account['category'] == 'Application') {
			$query = null; // need to add this
		}
		else { 
			$query = '/'. $applicationId;
		}
		return $query; 
	}

	public function queryInfo($applicationId) 
	{
		$account = $this->getAccount($id);
		if($account['category'] == 'Application') {
			return $this->queryApplication($applicationId); 
		}
		else { 
			return $this->queryPage($applicationId); 
		}
	}
	
	/* 
	 * Query based on docs at 
	 * https://developers.facebook.com/docs/reference/api/page/#feed
	 */
	public function queryFeed($applicationId) 
	{
		$account = $this->getAccount($applicationId);
		if($account['category'] == 'Application') {
			$query = null; // need to add this
		}
		else { 
			$query = '/'. $applicationId .'/feed';
		}
		return $query; 
	}
	
	/* 
	 * Query based on docs at 
	 * https://developers.facebook.com/docs/reference/fql/insights/ 
	 */
	public function queryInsights($applicationId = null, $startTime = null, $endTime = null)
	{ 
		$account = $this->getAccount($applicationId);
		if($account['category'] == 'Application') {
			$endTime = empty($endTime) ? strtotime('-2 day') : $endTime;  // queries must end midnight prior 
			foreach($GLOBALS['insightsColumns'] as $insight => $insightOptions) { 
				$period = in_array('lifetime', $insightOptions) ? 'lifetime' : in_array('month', $insightOptions) ? 'month' : in_array('week', $insightOptions) ? 'week' : in_array('day', $insightOptions) ? 'day' : $insightOptions[0];
				$query[] = sprintf('SELECT metric, value, period, end_time FROM insights WHERE object_id="%d" 
					AND metric="%s" AND period=period("%s") AND end_time=end_time_date("%s")', $applicationId, $insight, $period, date('Y-m-d', $endTime));
			} 
		}
		else { 
			$query[] = '/'. $applicationId .'/insights';
			$query[] = '/'. $applicationId .'/insights?period=lifetime&';
			if($startTime && $endTime) { 
				$query = array(); 
				$query[] = '/'. $applicationId .'/insights?since='. $startTime .'&until='. $endTime;  
				$query[] = '/'. $applicationId .'/insights?period=lifetime&since='. $startTime .'&until='. $endTime; // force facebook to give aggregate metrics too 
				/* special metrics wanted: */
				$query[] = '/'. $applicationId .'/insights/page_fan_adds?since='. $startTime .'&until='. $endTime; 
				$query[] = '/'. $applicationId .'/insights/page_fans?since='. $startTime .'&until='. $endTime; 
				$query[] = '/'. $applicationId .'/insights/page_impressions?since='. $startTime .'&until='. $endTime; 
				$query[] = '/'. $applicationId .'/insights/page_impressions_unique?since='. $startTime .'&until='. $endTime; 
				$query[] = '/'. $applicationId .'/insights/page_impressions_viral_unique?since='. $startTime .'&until='. $endTime; 
				$query[] = '/'. $applicationId .'/insights/page_stories?since='. $startTime .'&until='. $endTime; 
				$query[] = '/'. $applicationId .'/insights/page_active_users?since='. $startTime .'&until='. $endTime; 
			}
		}
		return $query; 
	}

	
	
	/* 
	 * Search results table for status posts and return insights query 
	 */ 
	public function queryPostInsights($applicationId)
	{
		$account = $this->getAccount($applicationId);
		$resultsTable = $this->silos->getAccountSilo($accountId); 
		if($account['category'] == 'Application') {
			$query = null; 
		}
		else { 
			$query = sprintf('SELECT * FROM `%s` WHERE graph_type="status" AND application="%d" AND (DAYOFYEAR(NOW())-DAYOFYEAR(date_updated)) < 2 GROUP BY graph_id', $resultsTable, $applicationId);
			$result = mysql_query($query);
			$query = array(); 
			while($data = mysql_fetch_assoc($result)) {
				$query[] = '/'. $data['graph_id'] .'/insights';
			}
		}
		return $query ? $query : ''; 
	}

	/* 
	 * Parse query or graph result for db writing 
	 */ 
	public function saveResults($applicationId = null, $result = array()) 
	{
		$graphId = isset($result['id']) ? $result['id'] : null; 
		$graphType = isset($result['type']) ? $result['type'] : null; 
		// insights and feeds are under a data key 
		if(isset($result['data'])) {
			$this->saveResults($applicationId, $result['data']);
		}
		// graph insights are under a values array  
		elseif(isset($result['values'])) { 
			foreach($result['values'] as $k => $v) {
				$this->saveResult($applicationId, $graphId, $graphType, $result['name'], $v['value'], $result['period'], $v['end_time']); 
			}
		}
		// fql.query results have a metric and value
		elseif(isset($result['metric'])) {
			$this->saveResult($applicationId, $graphId, $graphType, $result['metric'], $result['value'], $result['period'], $result['end_time']); 
		}
		// otherwise save key values 
		elseif(is_array($result)) { 
			foreach($result as $key => $val) {
				if(is_numeric($key) && is_array($val)) {
					$this->saveResults($applicationId, $val);
				}
				else {
					$this->saveResult($applicationId, $graphId, $graphType, $key, $val); 
				}
			}
		}
	}

	/* 
	 * Write query results to database 
	 */ 
	public function saveResult($accountId = null, $graphId = null, $graphType = null, $field = null, $value = null, $period = null, $endTime = null)
	{
		$field = addslashes($field);
		$value = (is_array($value) || is_object($value)) ? addslashes(json_encode($value)) : addslashes($value);
		$period = (array_key_exists($period, $this->unixTimes)) ? $this->unixTimes[$period] : $period; // make these 'day', 'week', 'month'... 
		$endTime = $endTime ? date('Y-m-d H:i:s', strtotime($endTime)) : $endTime; // make these MySql format 
		$resultsTable = $this->silos->getAccountSilo($accountId); 
		
		$query = sprintf('INSERT INTO `%s` (`application`, `graph_id`, `graph_type`, `field`, `value`, `period`, `end_time`) 
			VALUES ("%d", "%s", "%s", "%s", "%s", "%s", "%s")',
			$resultsTable, 
			$accountId, $graphId, $graphType, $field, $value, $period, $endTime);
		mysql_query($query); 
	}

	/* 
	 * Create Facebook object for app and run query
	 */ 
	public function fbQuery($accountId = null, $query = null, $params = array()) 
	{
		$account = $this->getAccount($accountId); 
		$facebook = $this->getFacebook($account['application'], $account['secret']);
		try{
					if(!$account['secret']) { 
						throw new Exception('SCGA needs an account secret for fql');
					}
					else { 
						$params = array_merge(array(
								'method' => 'fql.query',
								'query' => $query, //'callback' => null, 'format' => '', 'access_token' => $account['token'],
								), $params);
						$result = $facebook->api($params); 
					}
		}
		catch(Exception $o){
				error_log($o->getMessage() .' '. json_encode(array($query, $params)));
				$result = $o; 
		}
		return $result; 
	}

	/* 
	 * Run an impersonated query on the FB graph API 
	 */ 
	public function fbGraph($accountId = null, $path = null, $params = array()) 
	{
		$account = $this->getAccount($accountId); 
		$facebook = $this->getFacebook();
		try{
					$params = array_merge(array(
							'method' => 'GET',
							'access_token' => $account['access_token'],
							), $params);
					$result = $this->facebook->api($path, $params); 
		}
		catch(Exception $o){
				error_log($o->getMessage() .' '. json_encode(array($path, $params)));
				$result = $o; 
		}
		return $result; 
	}


}











