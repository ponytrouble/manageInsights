<?php

require_once '../../../scg_common/scg.php';

class scgaFangraphics extends scgaJobs
{
	public $queries;
	public $log; 
	
	function __construct() 
	{
		parent::__construct(); 
	}

	public function exportFangraphics($format = null, $table = null) 
	{
		ini_set('memory_limit', '1024M'); // memory for unserialize
		if($table == 'cha_poll_questions') {
			$sql = 'SELECT * FROM springc6_htc.cha_poll_questions ORDER BY id ASC';
			$result = mysql_query($sql);
		}
		else {
			$sql = 'SELECT * FROM springc6_htc.cha_poll WHERE question_id > 68 ORDER BY id ASC';
			$result = mysql_query($sql);
		}
		$dataArray = array(); 
		while($data = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$user = empty($data['user_data']) ? array() : unserialize(base64_decode(trim($data['user_data']))); 
			unset($data['user_data']);
			// error with offset or utf-8 encoding? double check file 
			if(!is_array($user)) {
				error_log('No user unseralize for '. $data['user_id'] .' |Searching file: '. $file);
				$file = '../../fbapps/htc/fangraphics/fb_user/'. $data['user_id'] .'.bs.txt'; 
				if(is_file($file) && ($file = file_get_contents($file))) {
					$user = unserialize(base64_decode($file));
				}
			}
			// remove some big arrays 
			foreach(array('scg_friends', 'scg_likes', 'scg_groups') as $k) {
				if(isset($user[$k]) && isset($user[$k]['data'])) { 
					$data[$k] = count($user[$k]['data']); 
					unset($user[$k]);
				}
			}
			/* $showOnly = array('id', 'name', 'first_name', 'last_name', 'link', 'username', 'birthday', 'hometown_id', 'hometown_name', 'location_id', 'location_name', 'gender', 'timezone', 'locale', 'verified', 'updated_time', 'scg_friends', 'scg_likes', 'scg_groups', 'middle_name', 'about', 'quotes');
			foreach((array)$user as $k => $v) {
				if(!in_array($k, $showOnly)) { 
					unset($user[$k]); 
				 }
			} */ 
			$data['_'] = $user;
			$dataArray[] = $data;
		}
		$fileName = 'fangraphic-results.'. $table .'.'. date('YmdHis') .'.'. $format;
		if($format == 'csv') {
			$result = self::makeCsv($dataArray);
			echo self::downloadCsv($result, $fileName);
			exit;
		}
		$result = self::makeXml($dataArray);
		echo self::downloadXml($result, $fileName);
		exit;
	}
}