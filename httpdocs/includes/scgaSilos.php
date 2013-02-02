<?php
/** 
 * @file
 * Methods to put query results into seperate tables. 
 */

class scgaSilo 
{

	public $defaultSilo = 'scga_results';
	function __construct() 
	{
	}

	public function addAccountSilo($accountId, $siloId) 
	{
		$query = sprintf('INSERT scga_account_silos (application, silo_id) VALUES("%d", "%d") ON DUPLICATE KEY UPDATE application="%d", silo_id="%d"', $accountId, $siloId, $accountId, $siloId);
		$this->log[] = $query; 
		return mysql_query($query);
	}

	public function removeAccountSilo($accountId) 
	{
		$query = sprintf('DELETE FROM scga_account_silos WHERE application="%d"', $accountId);
		$this->log[] = $query;
		return mysql_query($query);
	}
	
	public function getSilos() 
	{
		$result = mysql_query('SELECT * FROM scga_silos');
		while($data = mysql_fetch_assoc($result)) {
			$this->silos[] = $data;
		}
		return $this->silos; 
	}
	
	public function getAccountSilo($applicationId) 
	{
		$siloId = $this->getAccountSiloId($applicationId);
		$query = sprintf('SELECT * FROM scga_silos WHERE id="%d"', $siloId);
		$data = mysql_fetch_assoc(mysql_query($query));
		if(!empty($data['silo_machine_name'])) {
			return $this->defaultSilo .'_silo_'. $data['silo_machine_name'];
		}
		return $this->defaultSilo;
	}
	
	public function getAccountSiloId($applicationId) 
	{
		$query = sprintf('SELECT * FROM scga_account_silos WHERE application="%d"', $applicationId);
		$data = mysql_fetch_assoc(mysql_query($query));
		if(!empty($data['silo_id'])) {
			return $data['silo_id'];
		}
	}
	
	public function addSilo($siloName) 
	{ 
		$machineName = ereg_replace('[^A-Za-z0-9]', '', $siloName);
		$result = mysql_query(sprintf('INSERT INTO scga_silos (silo_name, silo_machine_name) VALUES ("%s", "%s")', addslashes($siloName), $machineName));
		$query = sprintf('SHOW TABLES LIKE "scga_results_silo_%s"', $machineName); 
		$data = mysql_fetch_assoc(mysql_query($query));
		if(empty($data)) {
			mysql_query(sprintf('CREATE TABLE `scga_results_silo_%s` LIKE `%s`', $machineName, $this->defaultSilo));
		}
	}
	
	public function deleteSilo($siloId)
	{
		$query = sprintf('DELETE FROM scga_account_silos WHERE silo_id="%d"', $siloId);
		mysql_query($query);
		$query = sprintf('UPDATE scga_silos SET is_deleted="1" WHERE id="%d" LIMIT 1', $siloId);
		return mysql_query($query);
	}

}


