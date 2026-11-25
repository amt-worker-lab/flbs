<?php

ini_set('error_reporting', E_ERROR);
ini_set('display_errors', 'On');
ini_set('display_startup_errors', 'On');

// connect
$mysqli = connect();

// global requester names array
$requester_names = array();

// Set a global time variable for database inserts.
$db_insert_time = time();

function doRStats() {
	$_redacted_Latest = 0;
	$_redacted_Latest = 0;
	$_redacted_Latest = 0;
	
	$_redacted_FileName = '';
	$_redacted_FileName = '';
	$_redacted_ = '';
	
	echo 'test';
	

	$directory = '/var/www/html/mturk/.scripts/AMT_Alerts/logs';
	
	$dirIt = new DirectoryIterator($directory);
	
	foreach($dirIt as $fileInfo)
	{
			if($fileInfo->isFile()) {
			$fileName = $fileInfo->getFileName();
			$latestTime = $fileInfo->getMTime();
			
			if(strpos($fileName, '_redacted_') > -1)
			{
				if($latestTime > $_redacted_)
				{
					$_redacted_ = $latestTime;
					$_redacted_ = $fileName;
				}
			}
			else if(strpos($fileName,'_redacted_') > -1)
			{
				if($latestTime > $emsLatest)
				{
					$emsLatest = $latestTime;
					$_redacted_ = $fileName;
				}				
			}
			else if(strpos($fileName,'_redacted_') > -1)
			{
				if($latestTime > $_redacted_Latest)
				{
					$_redacted_Latest = $latestTime;
					$_redacted_FileName = $fileName;
				}				
			}
		}
	}
	
	echo 'latest _redacted_: '.$_redacted_FileName.'\n';
	echo 'latest _redacted_: '.$_redacted_.'\n';
	echo 'latest _redacted_: '.$_redacted_.'\n';
		
	
	$v = exec('"/usr/bin/Rscript" "/var/www/html/mturk/.scripts/AMT_Alerts/logs/incomeMachine.r" "logs/'.$_redacted_.'" "logs/'.$_redacted_FileName.'" "logs/'.$_redacted_.'" 2>&1', $output);
	
	$u = exec('mv /var/www/html/mturk/.scripts/AMT_Alerts/Income\ Stats\ v3.pdf /home/_redacted_/Desktop/Income\ Stats\ v4.pdf 2>&1', $output2);
    
	echo var_dump($v);
	print_r($output);
	print_r($output2);
}


function writeCSVtoNewFile($csvData, $saveDir, $name) {
	$fileName = $name.','.date(DATE_RFC2822).".txt";
	$myFile = "".$saveDir."/".$fileName;
	$fh = fopen($myFile, 'w') or die("can't open file");
	fwrite($fh, $csvData);
	fclose($fh);
	return $myFile;
}


class uniqueHits
{
		
	public static $uniqueHits				= array();
	public static $uniqueHitsNotInDB		= array();
	
	public static $uniqueRequesters		= array();
	public static $uniqueRequesterNms	= array();
	public static $uniqueTasks			= array();
	public static $uniqueDays			= array();
	public static $uniqueRewards		= array();
	public static $uniqueHitIds			= array();
	public static $uniqueStatus			= array();
	
	public static $uniqueRequestersNotInDB		= array();
	public static $uniqueRequesterNmsNotInDB	= array();
	public static $uniqueTasksNotInDB			= array();
	public static $uniqueDaysNotInDB			= array();
	public static $uniqueRewardsNotInDB			= array();
	public static $uniqueHitIdsNotInDB			= array();
	public static $uniqueStatusNotInDB			= array();
	
	public static $db_pending_tasks = array(); // status = pending
	
	public static $post_object;
	
	public static $save_filename;
	public static $delimeter;
	public static $account_name;
	public static $file_date;
	
	public static $script_start;
	
	//~ public static $uniqueAccounts	= array(); // NOT THIS
	
	/**
	 {
		name: name,
		csvData: curr_chunk,
		chunk_num: num,
		chunk_num_total: num_total,
		file_date: file_date
	 }
	 */
	function init_instance($post_object) {
		
		/**
		 * "If get_as_float is set to TRUE, then microtime() returns a float,
		 * which represents the current time in seconds since the Unix epoch accurate to the nearest microsecond."
		 */
		uniqueHits::$script_start = microtime(true);
		
		// ...
		uniqueHits::$post_object = $post_object;
		
		$fileName = uniqueHits::$post_object->name.','.uniqueHits::$post_object->file_date.'.csv';
		$file = '/var/www/html/mturk/.scripts/AMT_Alerts/logs/'.$fileName;
		
		uniqueHits::$save_filename = $file;
		uniqueHits::$delimeter = ',';
		uniqueHits::$account_name = uniqueHits::$post_object->name;
		uniqueHits::$file_date = uniqueHits::$post_object->file_date;
		
	}
	
	//~ function __construct($save_filename='', $delimiter=',', $account_name, $file_date) {
		//~ $this->post_object = $post_object;
	//~ }
	
	//~ function __construct($save_filename='', $delimiter=',', $account_name, $file_date) {
		//~ $this->save_filename = $save_filename;
		//~ $this->delimeter = $delimeter;
		//~ $this->account_name = $account_name;
		//~ $this->file_date = $file_date;
	//~ }
	
	//checks if the Hit Id is unique against all of the hits
	public static function alreadyAdded($requesterId, $name)
	{
		if(uniqueHits::$uniqueHits[$requesterId.$name])
		{
				//echo uniqueHits::$uniqueHits[$requesterId]->name;
				return true;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * not_in_db_requesters
	 * 
	 * Adds a new Requester!
	 */
	function not_in_db_requesters()
	{
		global $mysqli;
		$tmp = array();
		foreach(uniqueHits::$uniqueRequesters as $req_id => $v)
		{
			array_push($tmp, '"' . mysqli_real_escape_string($mysqli, $req_id) . '"');
		}
		$rslt = $mysqli->query('
			SELECT requesterUserId FROM
				requesters
			WHERE
				requesterUserId IN ('.implode(',', $tmp).')
		');
		// Iterate
		uniqueHits::$uniqueRequestersNotInDB = uniqueHits::$uniqueRequesters;
		while ($row = mysqli_fetch_array($rslt)) {
			// Has key?
			if (array_key_exists($row['requesterUserId'], uniqueHits::$uniqueRequestersNotInDB)) {
				// Remove key
				unset(
					uniqueHits::$uniqueRequestersNotInDB[ $row['requesterUserId'] ]
				);
			}
		}
		// Add new
		$totalNew = 0;
		foreach(uniqueHits::$uniqueRequestersNotInDB as $req_id => $task)
		{
			echo "Found New Requester: ".$req_id."\n";
			
			$rslt = $mysqli->query('
				INSERT INTO
					requesters
				SET
					requesterUserId="'.mysqli_real_escape_string($mysqli, $req_id).'",
					wakeupAlertDefault=0,
					emailAlertDefault=0,
					moneyAmountThreshold=0,
					minTasksThreshold=0
			') or die($mysqli->error());
			$totalNew++;
		}
		echo "Number of New Requesters: ".$totalNew."\n";
	}
	
	/**
	 * not_in_db_requesters
	 * 
	 * Adds a new Requester!
	 */
	function not_in_db_requester_names()
	{
		global $mysqli;
		// Select all
		$rslt = $mysqli->query('
			SELECT
				requesterUserId, requesterName
			FROM
				requester_names
		');
		// Iterate
		uniqueHits::$uniqueRequesterNmsNotInDB = uniqueHits::$uniqueRequesterNms;
		while ($row = mysqli_fetch_array($rslt)) {
			// Has key?
			if (array_key_exists($row['requesterUserId'], uniqueHits::$uniqueRequesterNmsNotInDB)) {
				// Remove key
				unset(
					uniqueHits::$uniqueRequesterNmsNotInDB[ $row['requesterUserId'] ]
				);
			}
		}
		// Add new
		$totalNew = 0;
		foreach(uniqueHits::$uniqueRequesterNmsNotInDB as $req_id => $req_name)
		{
			echo "Found New Requester Name: ".$req_name." (".$req_id.")\n";
			$query = '
				INSERT INTO
					requester_names
				SET
					requesterUserId	="'.mysqli_real_escape_string($mysqli, $req_id).'",
					requesterName	="'.mysqli_real_escape_string($mysqli, $req_name).'"
			';
			//~ echo $query;exit;
			$rslt = $mysqli->query($query) or die($mysqli->error());
			$totalNew++;
		}
		echo "Number of New Requester Names: ".$totalNew."\n";
	}
	
	/**
	 * not_in_db_tasks
	 * 
	 * Adds a new task!
	 * RequesterUserId is a FK.
	 * 
	 * NOTE: JULY 3, 2015: titleRequesterHash COLUMN HAS BEEN CHANGED TO COLLATION `UTF8_BIN`! This is to allow case sensitive varchar, e.g. TaskA versus taskA.
	 */
	function not_in_db_tasks()
	{
		global $mysqli;
		$tmp = array();
		foreach(uniqueHits::$uniqueHits as $title_req => $v)
		{
			array_push($tmp, '"' . mysqli_real_escape_string($mysqli, $title_req) . '"');
		}
		$query = '
			SELECT titleRequesterHash FROM
				tasks
			WHERE
				titleRequesterHash IN ('.implode(',', $tmp).')
		';
		$rslt = $mysqli->query($query);
		// Iterate
		uniqueHits::$uniqueHitsNotInDB = uniqueHits::$uniqueHits;
		while ($row = mysqli_fetch_array($rslt)) {
			// Has key?
			if (array_key_exists($row['titleRequesterHash'], uniqueHits::$uniqueHitsNotInDB)) {
				// Remove key
				unset(
					uniqueHits::$uniqueHitsNotInDB[ $row['titleRequesterHash'] ]
				);
			}
		}
		// Add new
		$totalNew = 0;
		foreach(uniqueHits::$uniqueHitsNotInDB as $req_title => $task)
		{
			echo "Found New Task: ".$task->name."\n";
			
			$rslt = $mysqli->query('
				INSERT INTO tasks
				SET
					taskTitle			="'.mysqli_real_escape_string($mysqli, $task->name).'",
					wakeupAlert			="'.'0'.'",
					emailAlert			="'.'0'.'",
					moneyAmountThreshold="'.'0'.'",
					minTasksThreshold	="'.'0'.'",
					requesterUserId		="'.mysqli_real_escape_string($mysqli, $task->requesterId).'",
					titleRequesterHash	="'.mysqli_real_escape_string($mysqli, $req_title).'",
					dateAdded			="'.uniqueHits::$file_date.'"
			') or die($mysqli->error());
			$totalNew++;
		}
		echo "Number of New Tasks: ".$totalNew."\n";
	}
	/**
	 * not_in_db_status
	 */
	function not_in_db_status()
	{
		global $mysqli;
		$tmp = array();
		foreach(uniqueHits::$uniqueStatus as $st => $v)
		{
			array_push($tmp, '"' . mysqli_real_escape_string($mysqli, $st) . '"');
		}
		$rslt = $mysqli->query('
			SELECT status FROM
				ta_status
			WHERE
				status IN ('.implode(',', $tmp).')
		');
		// Iterate
		uniqueHits::$uniqueStatusNotInDB = uniqueHits::$uniqueStatus;
		while ($row = mysqli_fetch_array($rslt)) {
			// Has key?
			if (array_key_exists($row['status'], uniqueHits::$uniqueStatusNotInDB)) {
				// Remove key
				unset(
					uniqueHits::$uniqueStatusNotInDB[ $row['status'] ]
				);
			}
		}
		// Add new
		$totalNew = 0;
		foreach(uniqueHits::$uniqueStatusNotInDB as $status=>$v)
		{
			echo "Found New Status: ".$status."\n";
			$rslt = $mysqli->query('
				INSERT INTO ta_status
				SET
					status="'.mysqli_real_escape_string($mysqli, $status).'"
			') or die($mysqli->error());
			$totalNew++;
		}
		echo "Number of New Statuses: ".$totalNew."\n";
	}
	/**
	 * not_in_db_days
	 */
	function not_in_db_days()
	{
		global $mysqli;
		$tmp = array();
		foreach(uniqueHits::$uniqueDays as $day => $v)
		{
			array_push($tmp, '"' . mysqli_real_escape_string($mysqli, $day) . '"');
		}
		$rslt = $mysqli->query('
			SELECT date_string FROM
				ta_dates
			WHERE
				date_string IN ('.implode(',', $tmp).')
		');
		// Iterate
		uniqueHits::$uniqueDaysNotInDB = uniqueHits::$uniqueDays;
		while ($row = mysqli_fetch_array($rslt)) {
			// Has key?
			if (array_key_exists($row['date_string'], uniqueHits::$uniqueDaysNotInDB)) {
				// Remove key
				unset(
					uniqueHits::$uniqueDaysNotInDB[ $row['date_string'] ]
				);
			}
		}
		// Add new
		$totalNew = 0;
		foreach(uniqueHits::$uniqueDaysNotInDB as $day=>$v)
		{
			echo "Found New Day: ".$day."\n";
			$d = explode('-', $day); // 0=year, 1=month, 2=day
			$rslt = $mysqli->query('
				INSERT INTO ta_dates
				SET
					date_string="'	.mysqli_real_escape_string($mysqli, $day).'",
					day="'			.mysqli_real_escape_string($mysqli, $d[2]).'",
					month="'		.mysqli_real_escape_string($mysqli, $d[1]).'",
					year="'			.mysqli_real_escape_string($mysqli, $d[0]).'"
			') or die($mysqli->error());
			$totalNew++;
		}
		echo "Number of New Days: ".$totalNew."\n";
	}
	/**
	 * not_in_db_rewards
	 */
	function not_in_db_rewards()
	{
		global $mysqli;
		$tmp = array();
		foreach(uniqueHits::$uniqueRewards as $rwd => $v)
		{
			array_push($tmp, '"' . mysqli_real_escape_string($mysqli, $rwd) . '"');
		}
		$rslt = $mysqli->query('
			SELECT reward FROM
				ta_rewards
			WHERE
				reward IN ('.implode(',', $tmp).')
		');
		// Iterate
		uniqueHits::$uniqueRewardsNotInDB = uniqueHits::$uniqueRewards;
		while ($row = mysqli_fetch_array($rslt)) {
			// Has key?
			if (array_key_exists($row['reward'], uniqueHits::$uniqueRewardsNotInDB)) {
				// Remove key
				unset(
					uniqueHits::$uniqueRewardsNotInDB[ $row['reward'] ]
				);
			}
		}
		// Add new
		$totalNew = 0;
		foreach(uniqueHits::$uniqueRewardsNotInDB as $rwd => $v)
		{
			if (trim($rwd) == '') continue; // Apparently one of these comes in recently...?
			echo "Found New Reward: ".$rwd."\n";
			$rslt = $mysqli->query('
				INSERT INTO ta_rewards
				SET
					reward="'.mysqli_real_escape_string($mysqli, $rwd).'"
			') or die($mysqli->error());
			$totalNew++;
		}
		echo "Number of New Rewards: ".$totalNew."\n";
	}
	/**
	 * not_in_db_hit_ids
	 */
	function not_in_db_hit_ids()
	{
		global $mysqli;
		$tmp = array();
		foreach(uniqueHits::$uniqueHitIds as $hid => $v)
		{
			array_push($tmp, '"' . mysqli_real_escape_string($mysqli, $hid) . '"');
		}
		// Do only 
		//~ echo count('Number of hit IDs not in:'.$tmp);exit;
		$rslt = $mysqli->query('
			SELECT hit_id FROM
				hit_ids
			WHERE
				hit_id IN ('.implode(',', $tmp).')
		');
		// Iterate
		uniqueHits::$uniqueHitIdsNotInDB = uniqueHits::$uniqueHitIds; // All HitIds from current run.
		uniqueHits::$uniqueHitIds = null; // UNSET THIS
		while ($row = mysqli_fetch_array($rslt)) {
			// Remove key
			unset(
				uniqueHits::$uniqueHitIdsNotInDB[ $row['hit_id'] ]
			);
		}
		
		// Add new
		// Make an array of INSERTS and then perform
		// all of the requests at once! (But split this into
		// approx. 40,000 inserts at once.)
		
		$totalNew = 0;
		$inserts = array();
		foreach(uniqueHits::$uniqueHitIdsNotInDB as $hit_id=>$v)
		{
			//~ xx
			//~ echo "Found New HIT IDs: ".$hit_id."\n";
			
			$inserts[] = '
				("'.mysqli_real_escape_string($mysqli, $hit_id).'")
			';
			
			if (count($inserts) > 40000) {
				uniqueHits::write_multi_hits_to_db($inserts, 'hit_ids');
				// Reset
				$inserts = array();
			}
			
			$totalNew++;
		}
		if (count($inserts) > 0)
			uniqueHits::write_multi_hits_to_db($inserts, 'hit_ids');
		
		echo "Number of New HIT IDs: ".$totalNew."\n";
	}
	function getPending()
	{
		global $mysqli;
		$rslt = $mysqli->query('
			SELECT amt_hit_id, fk_amt_status FROM
				tasks_accomplished
			WHERE
				fk_amt_status != "Paid" &&
				fk_amt_status != "Rejected" &&
				fk_amt_account = "'.mysqli_real_escape_string($mysqli, uniqueHits::$account_name).'"
		') or die($mysqli->error());
		while ($row = mysqli_fetch_array($rslt)) {
			uniqueHits::$db_pending_tasks[ $row['amt_hit_id'] ] = $row['fk_amt_status'];
		}
	}
	/**
	 * function getUniqueHits
	 * 
		uniqueHits::$save_filename = $file;
		uniqueHits::$delimeter = ',';
		uniqueHits::$account_name = uniqueHits::$post_object->name;
		uniqueHits::$file_date = uniqueHits::$post_object->file_date;
	 */
	//~ function getUniqueHits($filename='', $delimiter=',', $accountName, $db_insert_time)
	
	function getUniqueHits()
	{
		global $mysqli;
		if(!file_exists(uniqueHits::$save_filename) || !is_readable(uniqueHits::$save_filename)){
			return FALSE;
		}
		$header = NULL;
		$data = array();
		
		// ...
		if (($handle = fopen(uniqueHits::$save_filename, 'r')) !== FALSE)
		{
			while(($row = fgetcsv($handle, 1000, uniqueHits::$delimeter,'"')) !== FALSE)
			{
				if(!$header) { // SKIP THE HEADER :)
					$header = $row;
					continue;
				}
				
				// With each line, create the associative array
				$data = array_combine($header, $row);
				$hit = new Hit(
								$data['hitId'],
								$data['date'],
								$data['requesterName'],
								$data['requesterId'],
								$data['title'],
								$data['reward'],
								$data['status'],
								$data['feedback']
				);
				if(!uniqueHits::alreadyAdded($hit->requesterId, $hit->name) 
											 && $hit && trim($hit->requesterId) != '' 
											 && trim($hit->name))
				{
					uniqueHits::$uniqueHits[$hit->requesterId.$hit->name] = $hit;
				}
				
				// Unique requesters ($hit)
				// Unique days
				// Unique rewards
				// Unique hit ids
				// Unique statuses
				
				uniqueHits::$uniqueRequesters[ $data['requesterId'] ]	= $hit; // Ex
				uniqueHits::$uniqueRequesterNms[ $data['requesterId'] ]	= $data['requesterName']; // Ex
				uniqueHits::$uniqueDays[ $data['date'] ]				= true; // Ex 2014-09-27
				uniqueHits::$uniqueRewards[ $data['reward'] ]			= true; // Ex 0.06
				uniqueHits::$uniqueHitIds[ $data['hitId'] ]				= true; // Ex 30U1YOGZGAWI8IY7H435XJCVRPYSD3
				uniqueHits::$uniqueStatus[ $data['status'] ]			= true; // Ex Paid
			}
			fclose($handle);
		}
		
		// Things that are not in DB
		// These will add rows to DB for each
		// when not in DB.
		uniqueHits::not_in_db_requesters();
		uniqueHits::not_in_db_requester_names();
		uniqueHits::not_in_db_tasks();
		uniqueHits::not_in_db_hit_ids();
		uniqueHits::not_in_db_days();
		uniqueHits::not_in_db_rewards();
		uniqueHits::not_in_db_status();
		
		/**
		 * getPending
		 * 
		 * Retrieve all of the non- Paid, non- Rejected tasks
		 * from the DB. In these cases it will be necessary to check whether
		 * they have been resolved.
		 * 
		 * @ uniqueHits::$db_pending_tasks
		 */
		uniqueHits::getPending(); // THIS CREATES THE VARIABLE "uniqueHits::$db_pending_tasks", associative array
		
		/**
		 * Add tasks_accomplished here !
		 * 
		 * Max Inserts! Only 10k inserts max. per insert.
		 * 
		 tasks_accomplished
			id	int(11) UN PK AI
			fk_requesterUserId	varchar(45) 
			fk_titleRequesterHash	varchar(255) 
			amt_hit_id	varchar(30) 
			amt_feedback	text 
			date_inserted	int(11) 
			fk_amt_status	varchar(40) 
			fk_amt_date	varchar(10) 
			fk_amt_reward	decimal(4,2) UN 
			fk_amt_account	varchar(60) 
		 */
		
		// Add new
		$num_pending_updates = 0;
		$num_new_HITs = 0;
		$header = NULL;
		$data = array();
		$insert_values = array(); // array of arrays
		if (($handle = fopen(uniqueHits::$save_filename, 'r')) !== FALSE)
		{
			while(($row = fgetcsv($handle, 1000, uniqueHits::$delimeter,'"')) !== FALSE)
			{
				if(!$header) { // SKIP THE HEADER :)
					$header = $row;
					continue;
				}
				
				// With each line, create the associative array
				$data = array_combine($header, $row);
				$hit = new Hit(
								$data['hitId'],
								$data['date'],
								$data['requesterName'],
								$data['requesterId'],
								$data['title'],
								$data['reward'],
								$data['status'],
								$data['feedback']
				);
				
				$req_title = $data['requesterId'].$data['title'];
				
				// Is in array $uniqueHitIdsNotInDB?
				// Max Inserts = 40,000
				if (array_key_exists($data['hitId'], uniqueHits::$uniqueHitIdsNotInDB)) {
					
					$insert_values[] = '(
						"'.mysqli_real_escape_string($mysqli, $data['requesterId']).'",
						"'.mysqli_real_escape_string($mysqli, $req_title).'",
						"'.mysqli_real_escape_string($mysqli, $data['hitId']).'",
						"'.mysqli_real_escape_string($mysqli, $data['feedback']).'",
						"'.mysqli_real_escape_string($mysqli, uniqueHits::$file_date).'",
						"'.mysqli_real_escape_string($mysqli, $data['status']).'",
						"'.mysqli_real_escape_string($mysqli, $data['date']).'",
						"'.mysqli_real_escape_string($mysqli, $data['reward']).'",
						"'.mysqli_real_escape_string($mysqli, uniqueHits::$account_name).'"
					)';
					
					// Execute multiple inserts all at once.
					// All at once causes max memory size fatal error.
					
					if (count($insert_values) > 40000) {
						uniqueHits::write_multi_hits_to_db($insert_values, 'tasks_accomplished');
						// Reset
						$insert_values = array();
					}
					
					// Increment
					$num_new_HITs++;
					
				}
				
				// Is row pending in DB?
				// And are statuses different?
				// Resolved statuses are "Paid" and "Rejected".
				// DO NOT UPDATE `date_inserted`, `amt_hit_id`
				// HIT ID is not unique so this must be HIT ID + Account Name!
				
				else if
					(
					array_key_exists($data['hitId'], uniqueHits::$db_pending_tasks) &&
					uniqueHits::$db_pending_tasks[ $data['hitId'] ] != $data['status']
					)
				{
					$query = '
						UPDATE
							tasks_accomplished
						SET
							fk_requesterUserId		= "'.mysqli_real_escape_string($mysqli, $data['requesterId']).'",
							fk_titleRequesterHash	= "'.mysqli_real_escape_string($mysqli, $req_title).'",
							amt_feedback			= "'.mysqli_real_escape_string($mysqli, $data['feedback']).'",
							fk_amt_status			= "'.mysqli_real_escape_string($mysqli, $data['status']).'",
							fk_amt_date				= "'.mysqli_real_escape_string($mysqli, $data['date']).'",
							fk_amt_reward			= "'.mysqli_real_escape_string($mysqli, $data['reward']).'"
						WHERE
							amt_hit_id				= "'.mysqli_real_escape_string($mysqli, $data['hitId']).'" &&
							fk_amt_account			= "'.mysqli_real_escape_string($mysqli, uniqueHits::$account_name).'"
					';
					$rslt = $mysqli->query($query) or die($mysqli->error());
					$num_pending_updates++;
				}
			}
		}
		
		// Execute multiple inserts (again)
		//
		
		if (count($insert_values) > 0) {
			uniqueHits::write_multi_hits_to_db($insert_values, 'tasks_accomplished');
		}
		
		//--------------------------------------------------------------
		// Update all pending, Change to Paid, Where time > 45 days.
		//
		// This is because some tasks were never scraped as being paid.
		// But the assumption is that they were paid.
		// Uses column date_inserted.
		// Note that 45 days = 86400 seconds/day * 45.
		//--------------------------------------------------------------
		$query = '
			UPDATE
				tasks_accomplished
			SET
				fk_amt_status = "Paid"
			WHERE
				date_inserted < '.(time() - (86400*45)).'
		';
		$rslt = $mysqli->query($query) or die($mysqli->error());
		
		// Debug
		//
		
		echo 'Found '.count(uniqueHits::$uniqueDays).' unique days.'."\n";
		echo 'Found '.count(uniqueHits::$uniqueRewards).' unique rewards.'."\n";
		echo 'Found '.count(uniqueHits::$uniqueHitIds).' unique HIT IDs.'."\n";
		echo 'Found '.count(uniqueHits::$uniqueStatus).' unique statuses.'."\n";
		echo 'Found '.count(uniqueHits::$db_pending_tasks).' pending HITs for this account.'."\n";
		echo 'Found '.$num_pending_updates.' HITs with a changed (resolved) status.'."\n";
		echo 'Number of new HIT IDs added to DB: '.$num_new_HITs."\n";
		
		$time = round(microtime(true) - uniqueHits::$script_start, 3); // precision=2
		
		echo 'Completed in '.($time).' seconds.'."\n";
	}
	
	/**
	 * write_multi_hits_to_db
	 * 
	 * @ array_insert_values
	 * @ last_run
	 * 
	 * @returns		No return value.
	 */
	function write_multi_hits_to_db($array_insert_values, $update_type)
	{
		global $mysqli;
		switch($update_type) {
			case 'tasks_accomplished':
				$query = '
					INSERT INTO
						tasks_accomplished
						(
							fk_requesterUserId,
							fk_titleRequesterHash,
							amt_hit_id,
							amt_feedback,
							date_inserted,
							fk_amt_status,
							fk_amt_date,
							fk_amt_reward,
							fk_amt_account
						)
					VALUES
						'.implode(',', $array_insert_values).'
				';
				$rslt = $mysqli->query($query) or die($mysqli->error());
				// Debug
				echo 'Wrote '.count($array_insert_values).' to tasks_accomplished.'."\n";
				break;
			case 'hit_ids':
				$query = '
					INSERT INTO
						hit_ids
						(
							hit_id
						)
					VALUES
						'.implode(',', $array_insert_values).'
				';
				$rslt = $mysqli->query($query) or die($mysqli->error());
				// Debug
				echo 'Wrote '.count($array_insert_values).' to hit_ids.'."\n";
				break;
		}
	}
	
	/**
	 * check_account_name
	 * 
	 * @name - Account name.
	 * 
	 * Check if user account exists. If not then create the account.
	 * 
	 */
	function check_account_name($name)
	{
		global $mysqli;
		$rslt = $mysqli->query('
			SELECT * FROM
				ta_accounts
			WHERE
				account_name = "'.mysqli_real_escape_string($mysqli, $name).'"
		') or die($mysqli->error());
		if (mysqli_num_rows($rslt) == 0) {
			echo 'Found new user '.$name."\n";
			$rslt = $mysqli->query('
				INSERT INTO
					ta_accounts
				SET
					account_name = "'.mysqli_real_escape_string($mysqli, $name).'"
			') or die($mysqli->error());
		} else {
			echo 'Found existing user '.$name."\n";
		}
	}
	
	/**
	 * export_csv_save
	 * 
	 * @obj - Post object.
	 * 
	 * Save the parts of the file. If this is not the final part
	 * then exit. Else if this is the final part then write a combined
	 * file and continue script.
	 * 
	 */
	function export_csv_save($obj) {
		$fileName = $obj->name.','.$obj->file_date.','.$obj->chunk_num.'-'.$obj->chunk_num_total.'.csv';
		$file = '/var/www/html/mturk/.scripts/AMT_Alerts/logs/'.$fileName;
		file_put_contents(
			$file,
			$obj->csvData
		);
		chmod($file, 0777);
		
		// Debug
		echo 'Received ' . $obj->chunk_num.'-'.$obj->chunk_num_total."\n";
		
		// Continue?
		if ($obj->chunk_num != $obj->chunk_num_total) {
			exit;
		}
		
		// Here?
		// Get all of the chunks.
		$csvData = '';
		for ($i = 1; $i <= $obj->chunk_num_total; $i++) {
			$fileName = $obj->name.','.$obj->file_date.','.$i.'-'.$obj->chunk_num_total.'.csv';
			$file = 'logs/'.$fileName;
			$csvData .= file_get_contents($file);
		}
		
		// Write to a combined file.
		// uniqueHits::$save_filename
		$success = file_put_contents(
			uniqueHits::$save_filename,
			$csvData
		);
		if (!$success) {
			echo 'err';
			exit;
		}
		
		// Delete the parts files
		for ($i = 1; $i <= $obj->chunk_num_total; $i++) {
			$fileName = $obj->name.','.$obj->file_date.','.$i.'-'.$obj->chunk_num_total.'.csv';
			$file = 'logs/'.$fileName;
			unlink($file);
		}
		
		// Debug
		echo "Done writing to file\n";
		
	}
}




class Hit{
	public $hitId;
	public $date;
	public $requesterName;
	public $requesterId;
	public $name;
	public $reward;
	public $status;
	public $feedback;
	
	function __construct($hitId, $date, $reqName, $reqId,
						 $name, $reward, $status, $feedback)
	{
		$this->hitId = $hitId;
		$this->date = $date;
		$this->requesterName = $reqName;
		$this->requesterId = $reqId;
		$this->name = $name;
		$this->reward = $reward;
		$this->status = $status;
		$this->feedback = $feedback;
		//$groupId; //Not Yet Implemented
		//echo $this->hitId;
	}
}

function addNewRequesterFromHitDB($hit)
{
	global $mysqli;
	$rslt = $mysqli->query('
		INSERT INTO requesters
		SET
			requesterName="'.mysqli_real_escape_string($mysqli, $hit->requesterName).'",
			requesterUserId="'.mysqli_real_escape_string($mysqli, $hit->requesterId).'",
			wakeupAlertDefault=0,
			emailAlertDefault=0,
			moneyAmountThreshold=0,
			minTasksThreshold=0
	') or die($mysqli->error());
	return $rslt;
}

/**
 * POST export_csv
 * 
 * Typical line of CSV:
"3ZZAYRN1I6YYNYZO7BVT40SC3V3OTZ","2014-08-16","Big Bird","AIDYBIWXEXQWO","Identify the information about where a case was published",0.03,"Pending Approval", ""
 */
/**
 * ALL OF THE HITS
 * 
 * @hits_accomplished
 * @ta_accounts
 * @ta_rewards
 * @ta_dates
 * @users
 * 
 * JS object:
	 export_csv = 
	 {
		name: name,
		csvData: curr_chunk,
		chunk_num: num,
		chunk_num_total: num_total,
		file_date: file_date
	 }
 */
if (isset($_POST['export_csv'])) {
	$obj = json_decode($_POST['export_csv']);
	
	// Initiate the class.
	// This sets a few vars.
	//
	uniqueHits::init_instance($obj);
	
	// Saves file & decides whether to continue.
	// The function may exit the program.
	// Finally the filename will be saved
	// as a class var: uniqueHits::$save_filename.
	uniqueHits::export_csv_save($obj); // re-use $obj
	
	//
	if (property_exists($obj, 'doRStats'))
	{
		doRStats();
	}
	
	// User account in database?
	//
	uniqueHits::check_account_name($obj->name);
	
	// All the rest.
	//
	uniqueHits::getUniqueHits();
	
	// Exit 
	// Do not process below, for now at least.
	exit;
	
	//~ $allSubmits	= uniqueHits::getUniqueHits(uniqueHits::$save_filename, ",", $obj->name, $obj->file_date); // ... $accountName, $db_insert_time
	
	//Get a hasharray of all unique hits
	/**
	 * allSubmits->
	 * 		@requesterName
	 * 		@requesterId
	 * 		@hitId
	 * 		@date
	 * 		@name
	 * 		@reward
	 * 
	 * allDays[] - associative, key = day
	 * 		Ex 2014-09-27
	 * 
	 * allRewards[] - associative, key = reward
	 * 		Ex 0.06
	 * 
	 */
	//Get only the unique requesters
	$allReqs = array();
	$sqlAllReqsQuery = "SELECT * FROM AMT_Alerts.requesters where requesterUserId in (";
	foreach($allSubmits as $key=>$value)
	{
		if(!in_array($value->requesterId,$allReqs))
		{
			array_push($allReqs,$value->requesterId);
			$sqlAllReqsQuery.= "'".$value->requesterId."',";
		}		
	}
	$sqlAllReqsQuery = trim($sqlAllReqsQuery,",");
	$sqlAllReqsQuery.=")";
	
	$rslt = $mysqli->query($sqlAllReqsQuery);
	
	echo "Number of Requesters already in DB: ".mysqli_num_rows($rslt)."\n";
	
	$allInDatabase = array();
	while($row = mysqli_fetch_array($rslt))
	{
		// Push to associative array
		$allInDatabase[ $row['requesterUserId'] ] = true;
	}
	
	$totalNew = 0;
	foreach($allSubmits as $key=>$value)
	{
		
		/**
		 * Insert Requester into database.
		 * TODO: change requesters to object instead of array.
		 */
		if (!array_key_exists($value->requesterId, $allInDatabase))
		{
			echo "Found new Requester: ".$value->requesterId."\n";
			$rslt2 = $mysqli->query('
						INSERT INTO requesters
						SET
							requesterName="'.mysqli_real_escape_string($mysqli, $value->requesterName).'",
							requesterUserId="'.mysqli_real_escape_string($mysqli, $value->requesterId).'",
							wakeupAlertDefault=0,
							emailAlertDefault=0,
							moneyAmountThreshold=0,
							minTasksThreshold=0
					') or die($mysqli->error());
			$totalNew++;
			// Add
			$allInDatabase[ $value->requesterId ] = true;
		}
	}
	// Old
	//~ if(!in_array($value->requesterId, $allInDatabase))
	//~ array_push($allInDatabase,$value->requesterId);
	
	echo "Total new requesters: ".$totalNew."\n";
	
	$trh_arr = array();
	foreach($allSubmits as $key=>$value)
	{
		array_push($trh_arr, '"' . mysqli_real_escape_string($mysqli, $value->requesterId . $value->name) . '"');
	}
	$sqlAllHitsQuery = '
		SELECT * FROM AMT_Alerts.tasks where titleRequesterHash in ('.implode(',', $trh_arr).')
	';
	//~ $sqlAllHitsQuery.="'".mysqli_real_escape_string($mysqli, $value->requesterId . $value->name)."',";
	//~ $sqlAllHitsQuery = trim($sqlAllHitsQuery,",");
	//~ $sqlAllHitsQuery.=")";
	//~ echo $sqlAllHitsQuery;
	
	$rslt = $mysqli->query($sqlAllHitsQuery) or die($mysqli->error());
	
	echo "Number of Tasks (Requester ID + Task Title) already in DB: ".mysqli_num_rows($rslt)."\n";
	
	
	
	$allHitsInDb = array();
	while($row = mysqli_fetch_array($rslt))
	{
		$allHitsInDb[ $row['titleRequesterHash'] ] = true;
	}
		
	$totalNew = 0;
	//~ $date = time(); // $obj->file_date
	foreach($allSubmits as $trhash=>$value)
	{
		if(!array_key_exists($trhash, $allHitsInDb))
		{
			echo "Found New Task: ".$trhash."\n";
			$rslt3 = $mysqli->query('
								INSERT INTO tasks
								SET
									taskTitle="'.mysqli_real_escape_string($mysqli, $value->name).'",
									wakeupAlert="'.'0'.'",
									emailAlert="'.'0'.'",
									moneyAmountThreshold="'.'0'.'",
									minTasksThreshold="'.'0'.'",
									requesterUserId="'.mysqli_real_escape_string($mysqli, $value->requesterId).'",
									titleRequesterHash="'.mysqli_real_escape_string($mysqli, $trhash).'",
									dateAdded="'.$obj->file_date.'"
							') or die($mysqli->error());
			$allHitsInDb[ $trhash ] = true;
			$totalNew++;
		}				
	}
	echo "Number of New Tasks: ".$totalNew."\n";
	
	//~ array_push($allHitsInDb, $row['titleRequesterHash']);
	//~ if(!in_array($key, $allHitsInDb))
	//~ array_push($allHitsInDb, $key);
	
	
	/**
	 * Check all non- "Paid", non- "Rejected"
	 * 
	 */
	
	
	
	// select *,taskId from tasks where requesterUserId in (a,b,c,...)
	// 
	
	
	// insert new requesters to db
	// insert new tasks to db
	
	exit;
}

/*
if (isset($_POST['csvData'])) {

	$csvData = $_POST['csvData'];
	
	//~ echo strlen($csvData);exit;
	
	// Log all our work
	$myFile = writeCSVtoNewFile($csvData,"logs", $_POST['name']);
	
	// $_POST['doRStats']
	if(isset($_POST['doRStats']))
	{
		doRStats();
	}
	
	echo "done writing to file\n";
	

	//echo 'Current script owner: ' . get_current_user()."\n";
	
	
	//Get a hasharray of all unique hits
	$allSubmits = uniqueHits::getUniqueHits($myFile,",");
	
	//Get only the unique requesters
	$allReqs = array();
	$sqlAllReqsQuery = "SELECT * FROM AMT_Alerts.requesters where requesterUserId in (";
	
	foreach($allSubmits as $key=>$value)
	{
		if(!in_array($value->requesterId,$allReqs))
		{
			array_push($allReqs,$value->requesterId);
			$sqlAllReqsQuery.= "'".$value->requesterId."',";
		}		
	}
	
	$sqlAllReqsQuery = trim($sqlAllReqsQuery,",");
	$sqlAllReqsQuery.=")";
	//echo $sqlAllReqsQuery;
	
	
	$rslt = $mysqli->query($sqlAllReqsQuery);
	
	echo "Number of already inside: ".mysqli_num_rows($rslt)."\n";
	
	
	$allInDatabase = array();
	while($row = mysqli_fetch_array($rslt))
	{
		// Push to associative array
		$allInDatabase[ $row['requesterUserId'] ] = true;
		
		//array_push($allInDatabase, $row['requesterUserId']);
	}
	
	
	echo "Total new requesters: ".$totalNew."\n";
	
	
	$sqlAllHitsQuery = "SELECT * FROM AMT_Alerts.tasks where titleRequesterHash in (";
	foreach($allSubmits as $key=>$value)
	{
		$sqlAllHitsQuery.="'".mysqli_real_escape_string($mysqli, $key)."',";
	}
	
	$sqlAllHitsQuery = trim($sqlAllHitsQuery,",");
	$sqlAllHitsQuery.=")";

	echo $sqlAllHitsQuery;
	
	$rslt = $mysqli->query($sqlAllHitsQuery) or die($mysqli->error());
	
	echo "Number of HITs already inside: ".mysqli_num_rows($rslt)."\n";
	
	
	
	$allHitsInDb = array();
	while($row = mysqli_fetch_array($rslt))
	{
		array_push($allHitsInDb, $row['titleRequesterHash']);
	}
		
	$totalNew = 0;
	$date = time();
	foreach($allSubmits as $key=>$value)
	{
		if(!in_array($key, $allHitsInDb))
		{
			echo "Found New Hit: ".$key."\n";
			$rslt3 = $mysqli->query('
								INSERT INTO tasks
								SET
									taskTitle="'.mysqli_real_escape_string($mysqli, $value->name).'",
									wakeupAlert="'.'0'.'",
									emailAlert="'.'0'.'",
									moneyAmountThreshold="'.'0'.'",
									minTasksThreshold="'.'0'.'",
									requesterUserId="'.mysqli_real_escape_string($mysqli, $value->requesterId).'",
									titleRequesterHash="'.mysqli_real_escape_string($mysqli, $key).'",
									dateAdded="'.$date.'"
							') or die($mysqli->error());
			array_push($allHitsInDb, $key);
			$totalNew++;
		}				
	}
	echo "Number of New Hits: ".$totalNew."\n";
	
	
	
	
	
	// select *,taskId from tasks where requesterUserId in (a,b,c,...)
	// 
	
	
	// insert new requesters to db
	// insert new tasks to db
	
	exit;
}*/

/**
 * Displayer
 * 
 * NOTE: The default page is always Month >= current month, Year = current Year. So if
 * there are no GET vars defined, then set GET vars to this.
 * 
 * Get: "page"
 * Ex: http://localhost/mturk/.scripts/AMT_Alerts/alerter.php?page=recent_requesters
 */
class Displayer
{
	
	// recent_tasks
	public $recent_tasks__table_head; //
	public $recent_tasks__table_body; //
	public $recent_tasks__table_footer; //
	
	// recent_tasks_daily
	public $recent_tasks_daily__table_head; //
	public $recent_tasks_daily__table_body; //
	public $recent_tasks_daily__table_footer; //
	
	private $pages;
	private $page;
	private $links;
	
	private $filters;
	
	//~ public $host = 'http://192.168.1.86/mturk/.scripts/AMT_Alerts/'; //
	public $host = 'http://localhost/mturk/.scripts/AMT_Alerts/'; //
	
	function __construct($page) {
		global $mysqli;
		$this->page = $page; // str
		
		$this->pages = array(
			'Recent Requesters'			=> ['recent_requesters','Tasks grouped by Requester'],
			'Recent Requesters Daily'	=> ['recent_requesters_daily','Tasks grouped by Day, then by Requester'],
			'Recent Tasks'				=> ['recent_tasks','Tasks grouped by Task Name'],
			'Recent Tasks Daily'		=> ['recent_tasks_daily','Tasks grouped by Day, then by Task Name'],
			'Features, Notes, Bugs'		=> ['tracker','TODO: DB for...']
		);
		
		// Stats
		$this->stats = array( // This is a stats array
			'total_rewards'			=> 0,
			'unique_days_worked'	=> 0,
			'first_day_of_period'	=> null,
			'last_day_of_period'	=> null,
			'total_days_in_period'	=> null,
			'total_rewards_over_30'	=> 0
		);
		
		// Shortcut
		//~ $this->short_url = 'http://192.168.1.86/tasks/';
		$this->short_url = 'http://localhost/tasks/';
		
		// Links
		// This is an array
		$this->links = []; // An array of HTML links
		// Old
		//~ $this->links = 'Links: ';
		// Set month-=1. If month==1 then set year-=1, month=12
		$fy = date('Y')*1;
		$fm = date('n')*1;
		if ($fm == 1) {
			$fy -= 1;
			$fm = 12;
		} else {
			$fm -= 1;
		}
		foreach ($this->pages as $title => $page_array) { //link
			$link = $page_array[0];
			$info = $page_array[1];
			$link_body = '';
			if ($link == $this->page) {
				$link_body .= '<b style="color:black;">['.$title.']</b>';
				$link_rows = '<tr><td>'.trim($link_body).'</td><td><b style="color:black;">'.$info.'</b></td></tr>';
			} else if ($link == 'tracker') {
				$link_body .= '<a href="'.$this->short_url.$link.'/" >['.$title.']</a>';
				$link_rows = '<tr><td>'.trim($link_body).'</td><td>'.$info.'</td></tr>';
			} else {
				$link_body .= '<a href="'.$this->short_url.$link.'/Year>='.$fy.'/Month>='.$fm.'/" >['.$title.']</a>';
				$link_rows = '<tr><td>'.trim($link_body).'</td><td>'.$info.'</td></tr>';
			}
			// Push
			array_push($this->links, $link_rows);
		}
		$this->links = '<table>'.implode("\n", $this->links).'</table>';
		
		/**
		 * Filter GET variables!
		 * @ $this->filters string
		 * @ $_GET['page_filters']
		 * 
		 * e.g. "Features.and.Bugs.-.Status=open/requesterName=100/"
		 * XXXXe.g. WHERE requesterName=Chios && year > 2013
		 * e.g. WHERE ta_dates.year>2013 && ta_dates.month>9
		 * 
		 * TODO: clean/verify/validate GET
		 */
		$this->filters = '';
		$f = $_GET['page_filters'];
		$f = explode('/', $f);
		$this->page_filters = array( // page_filters
			'date_year_start'		=>null, // Start
			'date_month_start'		=>null, // ""
			'date_day_start'		=>null, // ""
			'date_year_end'			=>null, // End
			'date_month_end'		=>null, // ""
			'date_day_end'			=>null  // ""
		);
		if (count($f) > 0) {
			$farray = array();
			foreach ($f as $key => $filter) {
				if (trim($filter) == '') continue; // Skip blank if exists
				$value = '';
				// page_filters
				// In JS is "/([^\/!<>=]+)([!<>=]+)([^\/]+)/g;"
				// 1=filter type
				// 2=filter operator
				// 3=filter value
				preg_match('/^([^!<>=]+)([!<>=]+)([^$]+)$/', $filter, $match);
				if ($match) { //print_r($match);
					switch($match[1]) {
						case 'Year':
							$this->page_filters['date_year_start'] = $match[3];
							break;
						case 'Month':
							$this->page_filters['date_month_start'] = $match[3];
							break;
						case 'Day':
							$this->page_filters['date_day_start'] = $match[3];
							break;
						case 'End-Year':
							$this->page_filters['date_year_end'] = $match[3];
							break;
						case 'End-Month':
							$this->page_filters['date_month_end'] = $match[3];
							break;
						case 'End-Day':
							$this->page_filters['date_day_end'] = $match[3];
							break;
						case 'tasks_accomplished.fk_requesterUserId':
							$match[3] = '"'.mysqli_real_escape_string($mysqli, $match[3]).'"'; // Adds quotes
							$filter = $match[1].$match[2].$match[3];
							break;
						case '':
							break;
					}
					//~ $value = $match[3];
				} else {
					echo 'testing April 20, 2016: this return false... wow...';
				}
				
				// Replace Year, Month, Day
				// For example, turns Year[=...] into ta_dates.year[=...]
				$filter = preg_replace('/^Year/',		'ta_dates.year',	$filter);
				$filter = preg_replace('/^Month/',		'ta_dates.month',	$filter);
				$filter = preg_replace('/^Day/',		'ta_dates.day',		$filter);
				
				// End Y/M/D
				$filter = preg_replace('/^End-Year/',	'ta_dates.year',	$filter);
				$filter = preg_replace('/^End-Month/',	'ta_dates.month',	$filter);
				$filter = preg_replace('/^End-Day/',	'ta_dates.day',		$filter);
				
				// Add quotes
				//~ $filter = preg_replace('/=(.+?)$/',	'="$1"',	mysqli_real_escape_string($mysqli, $filter));
				// mysqli_real_escape_string
				//~ $filter = mysqli_real_escape_string($mysqli, $filter);
				
				// Array push
				array_push($farray, $filter);
			}
			if (count($farray) > 0) {
				$this->filters = 'WHERE ' . implode(' && ', $farray);
				// Has Start-Year/Month/Day ?
				if (!$this->page_filters['date_year_start']) {
					$this->page_filters['date_year_start'] = date('Y'); //1900
				}
				if (!$this->page_filters['date_month_start']) {
					$this->page_filters['date_month_start'] = 1;
				}
				if (!$this->page_filters['date_day_start']) {
					$this->page_filters['date_day_start'] = 1;
				}
				// Has End-Year/Month/Day ?
				if (!$this->page_filters['date_year_end']) {
					$this->page_filters['date_year_end'] = date('Y'); //1900
				}
				if (!$this->page_filters['date_month_end']) {
					$this->page_filters['date_month_end'] = date('n'); //1-12
				}
				if (!$this->page_filters['date_day_end']) {
					$this->page_filters['date_day_end'] = date('j'); // 1-31
				} //print_r($this->page_filters);
				
				$dstart = $this->page_filters['date_year_start'] . '-' . $this->page_filters['date_month_start'] . '-' . $this->page_filters['date_day_start'];
				$dend = $this->page_filters['date_year_end'] . '-' . $this->page_filters['date_month_end'] . '-' . $this->page_filters['date_day_end'];
				echo $dstart;
				echo $dend;
				// Add to stats array!
				$date1 = new DateTime(
					$dstart
				); // past, e.g. "2010-07-06"
				$date2 = new DateTime(
					$dend
				); // present, i.e. date('Y-M-d'), e.g. "2010-07-09"
				$diff = $date2->diff($date1)->format("%a");
				print_r($date2->diff($date1));
				print_r($date2);
				echo $diff;
				$this->stats['total_days_in_period'] = $diff + 1;
				// $this->stats['total_days_in_period']
				//~ $now = time(); //
				//~ $date = strtotime($this->page_filters['date_year_start'] . '-' . $this->page_filters['date_month_start'] . '-' . $this->page_filters['date_day_start']);
				//~ $diff = $now - $date;
				//~ $this->stats['total_days_in_period'] = floor($diff/(60*60*24));
				//~ $now = time();
				//~ print_r($this->page_filters);
			}
			echo '<br>Parameters are:';
			print_r($this->filters);
			echo '<br>';
		}
	}
	
	function head() {
		
		// Table CSS from http://johnsardine.com/freebies/dl-html-css/simple-little-tab/
		
		echo '<html>';
		echo '<head>';
		echo '
		<script type="text/javascript" src="'.$this->host.'js/alerter.js"></script><!-- Contains filter js -->
		<script type="text/javascript" src="'.$this->host.'js/common.js"></script>
		<script type="text/javascript" src="'.$this->host.'js/css.js"></script>
		<script type="text/javascript" src="'.$this->host.'js/standardista-table-sorting.js"></script>
		<link rel="stylesheet" type="text/css" href="'.$this->host.'alerter.css">
		<style type="text/css">
			/* FILTERS */
			#filter_div {
				
			}
		</style>
		';
		echo '</head>';
	}
	function body() {
		echo '<body>';
	}
	function show_filters() {
		$filter_fields = array(
			'Requester ID',
			'Requester Name',
			'Task Title',
			'Number of Unique Tasks',
			'Accomplished-Submitted',
			'Accomplished-Accepted',
			'Accomplished-Rejected',
			'Accomplished-Pending',
			'Earnings',
			'Notes-Requester',
			'Notes-Task',
			'Date-Last Seen',			# date picker
			'Date-First Seen',			# date picker
			'Date-Age',
			'Date-Days Seen',
			'AMT Account',
			'Category',
			'Tag',
			'Alerts-Requester',
			'Alerts-Task'
		);
		echo '
		<div id="filter_div">
			<!-- THIS SELECT+BUTTON IS JUST A PROTOTYPE -->
			<select id="">
				<option value="">-- Select Filter (proto.)--</option>
				<option value="">Requester ID</option>
				<option value="">Requester Name</option>
				<option value="">Task Title</option>
				<option value="">Accomplished-Submitted</option>
				<option value="">Accomplished-Accepted</option>
				<option value="">Accomplished-Rejected</option>
				<option value="">Accomplished-Pending</option>
				<option value="">Earnings</option>
				<option value="">Notes-Requester</option>
				<option value="">Notes-Task</option>
				<option value="">Date-Last Seen</option>
				<option value="">Date-First Seen</option>
				<option value="">Date-Age</option>
				<option value="">Date-Days Seen</option>
				<option value="">AMT Account</option>
				<option value="">Category</option>
				<option value="">Tag</option>
				<option value="">Alerts-Requester</option>
				<option value="">Alerts-Task</option>
			</select>
			<input type="submit" value="Submit" />
			
			<br />
			<input type="button" id="filter_add_row_btn" value="Add Row" />
			<input type="submit" id="filter_submit_btn" value="Submit Filters" />
			<br />
		</div>
		';
	}
	
	/**
	 * tracker_overview
	 * 
	 * 
	 */
	function tracker_overview() {
		
		// Init vars
		$this->tracker_overview__init();
		
		echo '
			Features, Notes, Bugs Overview
			
			<br>
		';
		
		// Filters
		echo $this->show_filters();
		
		// Links
		echo $this->links;
		
		// Stats table
		//echo $this->stats_table;
		
		// Head
		echo $this->table_head;
		
		// Body
		echo $this->table_body;
		
		// Footer
		echo $this->table_footer;
		
	}
	/**
	 * recent_requesters
	 * ** Latest tasks, by last day accepted
	 * Ordered by month?... Steps through months.
	 */
	function recent_requesters() {
		// Init vars
		$this->recent_requesters__init();
		echo '
			Requesters Overview
			
			<br>
		';
		// Filters
		echo $this->show_filters();
		// Links
		echo $this->links;
		// Stats table
		echo $this->stats_table;
		// Head
		echo $this->table_head;
		// Body
		echo $this->table_body;
		// Footer
		echo $this->table_footer;
	}
	/**
	 * recent_requesters_daily
	 * ** Latest tasks, by last day accepted
	 * Ordered by month?... Steps through months.
	 */
	function recent_requesters_daily() {
		
		// Init vars
		$this->recent_requesters__init(true);
		echo '
			Requesters Overview Daily
			
			<br>
		';
		// Filters
		echo $this->show_filters();
		// Links
		echo $this->links;
		// Stats table
		echo $this->stats_table;
		// Head
		echo $this->table_head;
		// Body
		echo $this->table_body;
		// Footer
		echo $this->table_footer;
	}
	/**
	 * recent_tasks
	 * 
	 * ** Latest tasks, by last day accepted
	 * 
	 * Ordered by month?... Steps through months.
	 * 
	 */
	function recent_tasks() {
		
		// Init vars
		$this->recent_tasks__init(false);
		
		echo '
			Tasks Overview
			
			<br>
		';
		
		// Filters
		echo $this->show_filters();
		
		// Links
		echo $this->links;
		
		// Stats table
		echo $this->stats_table;
		
		// Head
		echo $this->table_head;
		
		// Body
		echo $this->table_body;
		
		// Footer
		echo $this->table_footer;
		
	}
	/**
	 * recent_tasks_daily
	 * 
	 */
	function recent_tasks_daily() {
		
		// Init vars
		$this->recent_tasks__init(true);
		
		//~ $this->recent_tasks_daily__init('daily');
		
		echo '
			Tasks Overview Daily
			
			<br>
			TODO<br>
			COUNT(features_and_bugs.id) as count_requester_bugs_features,<br>
			COUNT(features_and_bugs__tasks.id) as count_task_bugs_features
		';
		
		// Filters
		echo $this->show_filters();
		
		// Links
		echo $this->links;
		
		// Stats table
		echo $this->stats_table;
		
		// Head
		echo $this->table_head;
		
		// Body
		echo $this->table_body;
		
		// Footer
		echo $this->table_footer;
		
		// Javascript
		echo '<script type="text/javascript">
			
			// MAKE A GLOBAL OBJECT FOR MOUSEOVER/MOUSEOUT
			// CLASSNAMES.
			//var tasks_daily_obj = {};
			
			/**
			 * THIS ONLY RUNS ON recent_tasks_daily.
			 *DNW!
			 *
			 * NOTE: NOV.2 2014
			 * MAKE THIS DOCUMENT.BODY.ADDEVENTLISTENER
			 * PROBLEM: Adding mouseover to every row causes SLOW down!
			 * SOLUTION: Add mouseover only to .body. Then check the event.target to find
			 * which element is currently .mouseover!
			 */
			
			/*setTimeout(add_hover_tasks, 4000);
			function add_hover_tasks() {
				
				if (document.location.href.indexOf("recent_tasks_daily") == -1) {
					return;
				}
				
				var t = document.getElementsByTagName("tr");
				for (var i in t) {
					if (i == "length") break;
					if (t[i].className.indexOf("tasks_daily_") == -1) continue;
					
					var cls = t[i].className;
					//~ //console.log(cls);
					
					if (tasks_daily_obj.hasOwnProperty(cls)) {
						//
					} else {
						tasks_daily_obj[cls] = [];
					}
					tasks_daily_obj[cls].push(t[i]);
					
					// MOUSEOVER/MOUSEOUT
					t[i].onmouseover = function() {
						var cls = this.className;
						var els = tasks_daily_obj[cls];
						//~ console.log(els);
						
						for (var i in els) {
							t[i].style.fontWeight = "bold";
						}
					}
					t[i].onmouseout = function() {
						var cls = this.className;
						var els = tasks_daily_obj[cls];
						//~ console.log(els);
						
						for (var i in els) {
							t[i].style.fontWeight = "";
						}
					}
				}
				//~ console.log(tasks_daily_obj);
			}*/
			
			/*window.onload = function() {
				var t = document.getElementsByTagName("tr");
				for (var i in t) {
					if (i == "length") break;
					if (t[i].className.indexOf("tasks_daily_") == -1) continue;
					
					// MOUSEOVER/MOUSEOUT
					//~ t[i].onmouseover = function() {
						//~ var cls = this.className;
						//~ var els = document.getElementsByClassName(cls);
						//~ 
						//~ for (var i in els) {
							//~ if (i == "length") break;
							//~ t[i].style.fontWeight = "bold";
						//~ }
					//~ }
					//~ t[i].onmouseout = function() {
						//~ 
						//~ for (var i in els) {
							//~ t[i].style.fontWeight = "";
						//~ }
					//~ }
					
					//~ var cls = t[i].className;
					//console.log(cls);
					//~ 
					//~ if (tasks_daily_obj.hasOwnProperty(cls)) {
						//~ //
					//~ } else {
						//~ tasks_daily_obj[cls] = [];
					//~ }
					//~ tasks_daily_obj[cls].push(t[i]);
					//~ 
					//~ // MOUSEOVER/MOUSEOUT
					//~ t[i].onmouseover = function() {
						//~ var cls = this.className;
						//~ var els = tasks_daily_obj[cls];
						//~ console.log(els);
						//~ 
						//~ for (var i in els) {
							//~ t[i].style.fontWeight = "bold";
						//~ }
					//~ }
					//~ t[i].onmouseout = function() {
						//~ var cls = this.className;
						//~ var els = tasks_daily_obj[cls];
						//~ console.log(els);
						//~ 
						//~ for (var i in els) {
							//~ t[i].style.fontWeight = "";
						//~ }
					//~ }
				
				}
			}*/
		</script>';
	}
	function footer() {
		echo '</body></html>';
	}
	
	/**
	 * TABLES
	 * 
	 * $this->table_head
	 * $this->table_body
	 * $this->table_footer
	 * 
	 * 
	 * 
	 * NOTE:ENSURE THAT all "second-level" thead columns
	 * are no break.
	 * > tr > .nobreak
	 */
	
	/**
	 * tracker_overview__init
	 */
	function tracker_overview__init() {
		global $mysqli;
		// class="nobreak"
		$this->table_head = '
			<table class="sortable">
			<thead>
				<tr>
					<th>
						Description
					</th>
					<th align="left">
						Type<br>
						F=Feat.<br>
						B=Bug<br>
						N=Note
					</th>
					<th align="left">
						Status<br>
						O=Open<br>
						C=Closed<br>
						X=Resolved<br>
						RO=Re-opened<br>
						H=On Hold
					</th>
					<th align="left">
						Priority<br>
						H=High<br>
						N=Normal<br>
						L=Low
					</th>
					<th>
						Requester<br>
						(Optional)
					</th>
					<th>
						Task Title<br>
						(Optional)
					</th>
					<th>
						Date<br>
						Created
					</th>
					<th>
						Date<br>
						Last<br>
						Modified
					</th>
					<th>
						Created By<br>
						(User)
					</th>
					<th>
						Active<br>
						Users
					</th>
				</tr>
				<tr>
					<th>
						■
					</th>
					<th>
						■
					</th>
					<th>
						■
					</th>
					<th>
						■
					</th>
					<th>
						■
					</th>
					<th>
						■
					</th>
					<th>
						■
					</th>
					<th>
						■
					</th>
					<th>
						■
					</th>
					<th>
						■
					</th>
				</tr>
			</thead>
			<tbody>
		';
		
		
		$this->table_body = '';
		
		$this->table_footer = '</tbody></table>';
		
		// Query
		/**
		 * TODO: ADD ACTIVE/PARTICIPATING USERS TO TABLE features_and_bugs
		Table: features_and_bugs
		Columns:
			id	int(11) UN PK AI
			description	text 
			fk_created_by_user	varchar(45) 
			date_added	int(11) 
			date_last_modified	int(11) 
			fk_requesterUserId	varchar(45) 
			fk_titleRequesterHash	varchar(255) 
			status	varchar(45) 
			title	varchar(45) 
			type	varchar(45) 
			priority	varchar(45)
		
		 *
		INSERT INTO `AMT_Alerts`.`users` SET user_name='_redacted_' -_redacted_ -_redacted_;
		 */
		
		$query = '
			SELECT
				id
			FROM
				features_and_bugs
		';
		
		$rslt = $mysqli->query($query) or die($mysqli->error());
		
		while ($fb = mysqli_fetch_array($rslt)) {
			$this->table_body .= '
				<tr>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
				</tr>
			';
		}
	}
	
	/**
	 * recent_requesters__init
	 */
	function recent_requesters__init($is_daily) {
		global $mysqli;
		$groupBy;
		$table_row_html;
		if (!$is_daily) {
			$group_by = 'tasks_accomplished.fk_requesterUserId';
			$table_row_html = '<tr>';
		} else {
			// TODO: ALSO ADD IN THE TOTAL FOR THE DAY.
			//		So col1/2='', col3,4,5,6,7 = totals, col8...=''
			$group_by = 'tasks_accomplished.fk_amt_date, tasks_accomplished.fk_requesterUserId';
			$table_row_html = '<tr class="tasks_daily_%s">';
		}
		// Stats table
		// Percent total days=integer,signed decimal number
		$this->stats_table = '<table class="sortable">
			<thead>
				<tr>
					<th colspan=2>Stats</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td>Total rewards during period (includes pending=yes/ includes rejected=no):</td><td>%s</td>
				</tr>
				<tr>
					<td>Number of days in requested period:</td><td>'.$this->stats['total_days_in_period'].'</td>
				</tr>
				<tr>
					<td>Number of days with &gt;0 tasks accomplished:</td><td>%s</td>
				</tr>
				<tr>
					<td>Percent of days with &gt;0 tasks accomplished:</td><td>%s</td>
				</tr>
				<tr>
					<td>Number of days with &gt;$30:</td><td>%s</td>
				</tr>
				<tr>
					<td>Percent of days with &gt;$30:</td><td>%s</td>
				</tr>
			</tbody>
			</table>
		';
		$this->table_head = '
			<table class="sortable">
			<thead>
				<tr>
					<th>
						Requester
					</th>
					<th>
						# Tasks
					</th>
					<th colspan=4 align=left>
						Accomplished Stats<br>
						S=<br>
						A=<br>
						R=<br>
						P=
					</th>
					<th align=left>
						Earnings<br>
						S x Price
					</th>
					<th colspan=2>
						# Notes
					</th>
					<th colspan=4>
						Date Range
					</th>
					<th>
						Accounts
					</th>
					<th colspan=2>
						# Features & Bugs
					</th>
					<th>
						Task<br>Categories
					</th>
					<th>
						Task<br>Tags
					</th>
					
					<th colspan=3>
						Task<br>Alerts
					</th>
					<th colspan=3>
						Req.<br>Alerts
					</th>
					
					<th>
					</th>
				</tr>
				<tr class="nobreak">
					<th>
						■
					</th>
					<th>
						■
					</th>
					
					<th>
						S
					</th>
					<th>
						A
					</th>
					<th>
						R
					</th>
					<th>
						P
					</th>
					
					<th>
						■
					</th>
					
					<th>
						#R
					</th>
					<th>
						#T
					</th>
					
					<th>
						Last
					</th>
					<th>
						First
					</th>
					<th>
						Age
					</th>
					<th>
						#Days
					</th>
					
					<th>
						■
					</th>
					
					<th>
						#F
					</th>
					<th>
						#B
					</th>
					
					<th>
						■
					</th>
					<th>
						■
					</th>
					
					<th>
						Wk.
					</th>
					<th>
						Em.
					</th>
					<th>
						Av.?
					</th>
					<th>
						Wk.
					</th>
					<th>
						Em.
					</th>
					<th>
						Av.?
					</th>
					
					<th>
					</th>
				</tr>
			</thead>
			<tbody>
		';
		
		/**
		 * Rows
		 * With ORDER BY and OFFSET
		 * And FILTERERED ?
		 * NOTE:
		 *  Use GROUP_CONCAT + DISTINCT to get all of the requester names associated with requesterUserId.
		 *  Use a separator '___REQUESTER_NAMES_SEP___'.
		 * 		GROUP_CONCAT(DISTINCT requester_names.requesterName separator \'___REQUESTER_NAMES_SEP___\') as rn
		 * 
		 * 
		 * NOTE:
		 * Group By = "fk_titleRequesterHash" OR "tasks_accomplished.fk_amt_date, fk_titleRequesterHash"
		 * 
		 * NOTE:
		 * 
		 * 
		 */
		
		$query = '
			SELECT
				tasks_accomplished.id
				fk_requesterUserId,
				tasks_accomplished.fk_titleRequesterHash,
				amt_hit_id,
				amt_feedback,
				date_inserted,
				fk_amt_status,
				fk_amt_date,
				fk_amt_reward,
				fk_amt_account,
				
				ta_dates.day as d,
				ta_dates.month as m,
				ta_dates.year as y,
				ta_dates.date_string as date_string,
				
				MAX(unix_timestamp(ta_dates.date_string))	as date_last_completed,
				MIN(unix_timestamp(ta_dates.date_string))	as date_first_completed,
				COUNT( DISTINCT ta_dates.date_string )		as date_count_num_days,
				
				tasks.id as tasks_id,
				tasks.taskTitle as title,
				tasks.titleRequesterHash as trh,
				
				tasks.wakeupAlert as tasks_wakeupAlert,
				tasks.emailAlert as tasks_emailAlert,
				tasks.alarm_active as tasks_alarm_active,
				
				requesters.wakeupAlertDefault as requesters_wakeupAlertDefault,
				requesters.emailAlertDefault as requesters_emailAlertDefault,
				requesters.alarm_active as requesters_alarm_active,
				requesters.requesterUserId as requesters_requesterUserId,

				GROUP_CONCAT(DISTINCT requester_names.requesterName separator ",") as rns,
				GROUP_CONCAT(DISTINCT ta_accounts.account_name separator ",") as account_names,
				
				SUM(ta_rewards.reward) as reward,
				SUM(ta_status.status = "Pending") * ta_rewards.reward	as reward_pending,
				SUM(ta_status.status = "Rejected") * ta_rewards.reward	as reward_rejected,
				SUM(ta_status.status = "Accepted") * ta_rewards.reward	as reward_accepted,
				SUM(ta_status.status = "Paid") * ta_rewards.reward		as reward_paid,
				
				COUNT(*) as totals_submitted,
				SUM(ta_status.status = "Paid") as totals_accepted,
				SUM(ta_status.status = "Rejected") as totals_rejected,
				SUM(ta_status.status != "Rejected" && ta_status.status != "Paid") as totals_pending,
				
				COUNT(DISTINCT tasks.taskTitle) as number_of_unique_tasks,
				
				COUNT(features_and_bugs.id) as count_requester_bugs_features,
				COUNT(features_and_bugs__tasks.id) as count_task_bugs_features

			FROM
				`tasks_accomplished` tasks_accomplished

			LEFT JOIN `requesters` requesters ON tasks_accomplished.fk_requesterUserId = requesters.requesterUserId
			LEFT JOIN `requester_names` requester_names ON requesters.requesterUserId = requester_names.requesterUserId
			LEFT JOIN `tasks` tasks ON tasks_accomplished.fk_titleRequesterHash = tasks.titleRequesterHash
			LEFT JOIN `hit_ids` hit_ids ON tasks_accomplished.amt_hit_id = hit_ids.hit_id
			LEFT JOIN `ta_status` ta_status ON tasks_accomplished.fk_amt_status = ta_status.status
			LEFT JOIN `ta_dates` ta_dates ON tasks_accomplished.fk_amt_date = ta_dates.date_string
			LEFT JOIN `ta_rewards` ta_rewards ON tasks_accomplished.fk_amt_reward = ta_rewards.reward
			LEFT JOIN `ta_accounts` ta_accounts ON tasks_accomplished.fk_amt_account = ta_accounts.account_name

			LEFT JOIN `features_and_bugs` features_and_bugs			ON tasks_accomplished.fk_requesterUserId = features_and_bugs.fk_requesterUserId
			LEFT JOIN `features_and_bugs` features_and_bugs__tasks	ON tasks_accomplished.fk_titleRequesterHash = features_and_bugs.fk_titleRequesterHash
			
			'.$this->filters.'
			
			GROUP BY
				'.$group_by.'
			ORDER BY
				date_last_completed DESC
		';
		
		// Debug
		echo '<!--QUERY:'.$query.'-->';
		
		// Perform the query
		$rslt = $mysqli->query($query) or die($mysqli->error());
		
		// $last_day
		$last_day = false;
		$show_day_row = false;// echo $this->stats['total_rewards_over_30'];
		
		// $stat_day_total
		$stat_day_rwd_total = 0;
		$day_rwd_total = 0;
		
		// Loop through the result set
		while ($tsk = mysqli_fetch_array($rslt)) {
			
			// htmlspecialchars
			foreach($tsk as $k=>$v) {
				$tsk[$k] = htmlspecialchars($v);
			}
			
			// First/Last
			// Only show the year when it is not *this* year
			$date = array('l'=>null, 'f'=>null, 'a'=>null);
			if (date('y') != date('y', $tsk['date_last_completed'])) {
				$date['l'] = date('j M, y', $tsk['date_last_completed']);
			} else {
				$date['l'] = date('j M', $tsk['date_last_completed']);
			}
			if (date('y') != date('y', $tsk['date_first_completed'])) {
				$date['f'] = date('j M, y', $tsk['date_first_completed']);
			} else {
				$date['f'] = date('j M', $tsk['date_first_completed']);
			}
			// Age
			$date['a'] = $this->dateDiff(floor($tsk['date_first_completed']), 2, floor($tsk['date_last_completed']));
			
			// Account names
			$acct = $this->accounts_names($tsk['account_names']);
			
			// Increment stat day total
			// $stat_day_total
			$tsk_rwd_total = floatval($tsk['reward_pending']) + floatval($tsk['reward_accepted']) + floatval($tsk['reward_paid']);
			//~ echo 'x';
			//~ print_r($tsk);
			//~ echo floatval($tsk['reward_pending']) + '<br>' + floatval($tsk['reward_accepted']) + ' ' + floatval($tsk['reward_paid']) + '-' + $tsk_rwd_total + '<br>';
			//~ $stat_day_rwd_total += $tsk_rwd_total;
			
			// Is it a new day?
			// $last_day
			// On first day ($date['l'] != $last_day(false)).
			if ($date['l'] != $last_day) {
				$last_day = $date['l'];
				$last_day_fmt = date('D', $tsk['date_last_completed']) . ', ' . $date['l'];
				$show_day_row = true;
				
				// Increment the unique days stat!
				$this->stats['unique_days_worked']++;
				
			} else {
				$show_day_row = false;
			}
			
			// Total rewards stats
			// Let's try do not add if it is rejected (rewards_less_rejected)
			$this->stats['total_rewards'] += $tsk_rwd_total;
			
			// Show day row (bool)
			if ($show_day_row) {
				$this->table_body .= '
					<tr class="show-day-row">
						<td colspan=25 style="text-align:center;">
							'.$last_day_fmt.'
						</td>
					</tr>
				';
			}
			
			// If is_daily then do not show the number of 
			// days that task has been completed. This shows
			// as "1" in this case for all tasks. Although
			// as a TODO this could be made to show num days.
			if ($is_daily) {
				$tsk['date_count_num_days'] = '-';
			}
			
			// Table body
			$this->table_body .= '
				'.sprintf($table_row_html, $tsk['tasks_id']).'
					<td>
						<a href="#'.$tsk['requesters_requesterUserId'].'">
							'.$tsk['rns'].'
						</a>
					</td>
					<td>
						'.$tsk['number_of_unique_tasks'].'
					</td>
					
					<td>
						'.$tsk['totals_submitted'].'
					</td>
					<td>
						'.$tsk['totals_accepted'].'
					</td>
					<td>
						'.$tsk['totals_rejected'].'
					</td>
					<td>
						'.$tsk['totals_pending'].'
					</td>
					
					<td>
						$'.$tsk['reward'].'
					</td>
					
					<td>
						# R
					</td>
					<td>
						# T
					</td>
					
					<td class="nobreak">
						'.$date['l'].'
					</td>
					<td class="nobreak">
						'.$date['f'].'
					</td>
					<td class="nobreak">
						'.$date['a'].'
					</td>
					<td class="nobreak">
						'.$tsk['date_count_num_days'].'
					</td>
					
					<td>
						'.$acct.'
					</td>
					
					<td>
						# F
					</td>
					<td>
						# B
					</td>
					
					<td>
						&nbsp;
					</td>
					<td>
						&nbsp;
					</td>
					
					<td>
						'.(($tsk['tasks_wakeupAlert'] == '1') ? '✔' : '-').'
						
					</td>
					<td>
						'.(($tsk['tasks_emailAlert'] == '1') ? '✔' : '-').'
					</td>
					<td>
						'.(($tsk['tasks_alarm_active'] == '1') ? '☺' : '-').'
					</td>
					
					<td>
						'.(($tsk['requesters_wakeupAlertDefault'] == '1') ? '✔' : '-').'
						
					</td>
					<td>
						'.(($tsk['requesters_emailAlertDefault'] == '1') ? '✔' : '-').'
					</td>
					<td>
						'.(($tsk['requesters_alarm_active'] == '1') ? '☺' : '-').'
					</td>
					
					<td>
					</td>
					
				</tr>
			';
		}
		
		$this->table_footer = '</tbody></table>'; //echo ' '.$this->stats['total_rewards_over_30'];
		
		// Stats table
		$fmtrwd = $this->stats['total_rewards'];
		$fmtrwd = preg_replace('/(\.\d)$/', '${1}0', $fmtrwd); // .1 to .10
		$fmtrwd = preg_replace('/^(\d+)$/', '$1.00', $fmtrwd); // 1 to 1.00
		$this->stats_table = sprintf(
			$this->stats_table,
			'$' . $fmtrwd,
			$this->stats['unique_days_worked'],
			floor($this->stats['unique_days_worked'] / $this->stats['total_days_in_period']*100), // TOTAL STATS IS total_days_in_period-1!
			$this->stats['total_rewards_over_30'],
			floor($this->stats['total_rewards_over_30'] / $this->stats['total_days_in_period']*100) // TOTAL STATS IS total_days_in_period-1!
		);
	}
	/**
	 * accounts_names
	 * 
	 * @param $str, string, long version of names
	 * 
	 * @returns $acct, string, short version of names
	 */
	function accounts_names($str) {
		$str = preg_replace_callback('/(_redacted_|_redacted_|_redacted_|_redacted_)/', function($matches) { return substr($matches[1], 0, 1); }, $str);
		// NEW=Always display account names in age order (B,E,_redacted_,_redacted_,G)
		// OLD=Always display account names in alph. order
		$acct = array();
		if (strpos($str, 'B') !== false)
			$acct[] = 'B';
		if (strpos($str, 'E') !== false)
			$acct[] = 'E';
		if (strpos($str, '_redacted_') !== false)
			$acct[] = 'Sv';
		if (strpos($str, 'S') !== false)
			$acct[] = 'S';
		if (strpos($str, 'G') !== false)
			$acct[] = 'G';
		$acct = implode(',', $acct);
		return $acct;
	}
	function recent_tasks__init($is_daily) {
		global $mysqli;
		$groupBy;
		$table_row_html;
		if (!$is_daily) {
			$group_by = 'tasks_accomplished.fk_titleRequesterHash';
			$table_row_html = '<tr>';
		} else {
			// TODO: ALSO ADD IN THE TOTAL FOR THE DAY.
			//		So col1/2='', col3,4,5,6,7 = totals, col8...=''
			$group_by = 'tasks_accomplished.fk_amt_date, tasks_accomplished.fk_titleRequesterHash';
			$table_row_html = '<tr class="tasks_daily_%s">';
		}
		// Stats table
		// Percent total days=integer,signed decimal number
		$this->stats_table = '<table class="sortable">
			<thead>
				<tr>
					<th colspan=2>Stats</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td>Total rewards during period (includes pending=yes/ includes rejected=no):</td><td>%s</td>
				</tr>
				<tr>
					<td>Number of days in requested period:</td><td>'.$this->stats['total_days_in_period'].'</td>
				</tr>
				<tr>
					<td>Number of days with &gt;0 tasks accomplished:</td><td>%s</td>
				</tr>
				<tr>
					<td>Percent of days with &gt;0 tasks accomplished:</td><td>%s</td>
				</tr>
				<tr>
					<td>Number of days with &gt;$30:</td><td>%s</td>
				</tr>
				<tr>
					<td>Percent of days with &gt;$30:</td><td>%s</td>
				</tr>
			</tbody>
			</table>
		';
		$this->table_head = '
			<table class="sortable">
			<thead>
				<tr>
					<th>
						Requester
					</th>
					<th>
						Task
					</th>
					<th colspan=4 align=left>
						Accomplished Stats<br>
						S=<br>
						A=<br>
						R=<br>
						P=<br>
					</th>
					<th align=left>
						Earnings<br>
						S x Price
					</th>
					<th colspan=2>
						# Notes
					</th>
					<th colspan=4>
						Date Range
					</th>
					<th>
						Accounts
					</th>
					<th colspan=2>
						# Features & Bugs
					</th>
					<th>
						Task<br>Categories
					</th>
					<th>
						Task<br>Tags
					</th>
					
					<th colspan=3>
						Task<br>Alerts
					</th>
					<th colspan=3>
						Req.<br>Alerts
					</th>
					
					<th>
					</th>
				</tr>
				<tr class="nobreak">
					<th>
						■
					</th>
					<th>
						■
					</th>
					
					<th>
						S
					</th>
					<th>
						A
					</th>
					<th>
						R
					</th>
					<th>
						P
					</th>
					
					<th>
						■
					</th>
					
					<th>
						#R
					</th>
					<th>
						#T
					</th>
					
					<th>
						Last
					</th>
					<th>
						First
					</th>
					<th>
						Age
					</th>
					<th>
						#Days
					</th>
					
					<th>
						■
					</th>
					
					<th>
						#F
					</th>
					<th>
						#B
					</th>
					
					<th>
						■
					</th>
					<th>
						■
					</th>
					
					<th>
						Wk.
					</th>
					<th>
						Em.
					</th>
					<th>
						Av.?
					</th>
					<th>
						Wk.
					</th>
					<th>
						Em.
					</th>
					<th>
						Av.?
					</th>
					
					<th>
					</th>
				</tr>
			</thead>
			<tbody>
		';
		
		$this->table_body = '';
		/**
		 * Rows
		 * With ORDER BY and OFFSET
		 * And FILTERERED ?
		 *
		 * NOTE:
		 *  Use GROUP_CONCAT + DISTINCT to get all of the requester names associated with requesterUserId.
		 *  Use a separator '___REQUESTER_NAMES_SEP___'.
		 * 		GROUP_CONCAT(DISTINCT requester_names.requesterName separator \'___REQUESTER_NAMES_SEP___\') as rn
		 * 
		 * NOTE:
		 * TASKS
			wakeupAlert	tinyint(4) UN 
			emailAlert	tinyint(4) UN 
			taskTitle	varchar(255) 
			minTasksThreshold	int(11) UN 
			moneyAmountThreshold	int(11) UN 
			requesterUserId	varchar(45) 
			dateAdded	int(11) UN 
			titleRequesterHash	varchar(255) 
			alarm_active	tinyint(4) UN 
			
		* REQUESTERS 
			requesterUserId	varchar(45) 
			wakeupAlertDefault	tinyint(4) UN 
			emailAlertDefault	tinyint(4) UN 
			moneyAmountThreshold	int(11) UN 
			minTasksThreshold	int(11) UN 
			dateAdded	int(11) UN 
			alarm_active	tinyint(4) UN 
		 
		 * NOTE:
		 * Group By = "fk_titleRequesterHash" OR "tasks_accomplished.fk_amt_date, fk_titleRequesterHash"
		 * 
		 * NOTE:
		 * 
		 * 
		 */
		
		$query = '
			SELECT
				tasks_accomplished.id
				fk_requesterUserId,
				tasks_accomplished.fk_titleRequesterHash,
				amt_hit_id,
				amt_feedback,
				date_inserted,
				fk_amt_status,
				fk_amt_date,
				fk_amt_reward,
				fk_amt_account,
				
				ta_dates.day as d,
				ta_dates.month as m,
				ta_dates.year as y,
				ta_dates.date_string as date_string,
				
				MAX(unix_timestamp(ta_dates.date_string))	as date_last_completed,
				MIN(unix_timestamp(ta_dates.date_string))	as date_first_completed,
				COUNT( DISTINCT ta_dates.date_string )		as date_count_num_days,
				
				tasks.id as tasks_id,
				tasks.taskTitle as title,
				tasks.titleRequesterHash as trh,
				
				tasks.wakeupAlert as tasks_wakeupAlert,
				tasks.emailAlert as tasks_emailAlert,
				tasks.alarm_active as tasks_alarm_active,
				
				requesters.wakeupAlertDefault as requesters_wakeupAlertDefault,
				requesters.emailAlertDefault as requesters_emailAlertDefault,
				requesters.alarm_active as requesters_alarm_active,
				requesters.requesterUserId as requesters_requesterUserId,

				GROUP_CONCAT(DISTINCT requester_names.requesterName separator ",") as rns,
				GROUP_CONCAT(DISTINCT ta_accounts.account_name separator ",") as account_names,
				
				SUM(ta_rewards.reward) as reward,
				SUM(ta_status.status = "Pending") * ta_rewards.reward	as reward_pending,
				SUM(ta_status.status = "Rejected") * ta_rewards.reward	as reward_rejected,
				SUM(ta_status.status = "Accepted") * ta_rewards.reward	as reward_accepted,
				SUM(ta_status.status = "Paid") * ta_rewards.reward		as reward_paid,
				
				COUNT(*) as totals_submitted,
				SUM(ta_status.status = "Paid") as totals_accepted,
				SUM(ta_status.status = "Rejected") as totals_rejected,
				SUM(ta_status.status != "Rejected" && ta_status.status != "Paid") as totals_pending,
				
				COUNT(features_and_bugs.id) as count_requester_bugs_features,
				COUNT(features_and_bugs__tasks.id) as count_task_bugs_features
			FROM
				`tasks_accomplished` tasks_accomplished

			LEFT JOIN `requesters` requesters ON tasks_accomplished.fk_requesterUserId = requesters.requesterUserId
			LEFT JOIN `requester_names` requester_names ON requesters.requesterUserId = requester_names.requesterUserId
			LEFT JOIN `tasks` tasks ON tasks_accomplished.fk_titleRequesterHash = tasks.titleRequesterHash
			LEFT JOIN `hit_ids` hit_ids ON tasks_accomplished.amt_hit_id = hit_ids.hit_id
			LEFT JOIN `ta_status` ta_status ON tasks_accomplished.fk_amt_status = ta_status.status
			LEFT JOIN `ta_dates` ta_dates ON tasks_accomplished.fk_amt_date = ta_dates.date_string
			LEFT JOIN `ta_rewards` ta_rewards ON tasks_accomplished.fk_amt_reward = ta_rewards.reward
			LEFT JOIN `ta_accounts` ta_accounts ON tasks_accomplished.fk_amt_account = ta_accounts.account_name
			
			LEFT JOIN `features_and_bugs` features_and_bugs			ON tasks_accomplished.fk_requesterUserId = features_and_bugs.fk_requesterUserId
			LEFT JOIN `features_and_bugs` features_and_bugs__tasks	ON tasks_accomplished.fk_titleRequesterHash = features_and_bugs.fk_titleRequesterHash
			
			'.$this->filters.'
			
			GROUP BY
				'.$group_by.'
			ORDER BY
				date_last_completed DESC
		';//WHERE MAX(unix_timestamp(ta_dates.date_string)) > UNIX_TIMESTAMP() - (30*86400)
		//WHERE ta_dates.year>2013 && ta_dates.month>9
		
		// Debug
		echo '<!--QUERY:'.$query.'-->';
		
		// Perform the query
		$rslt = $mysqli->query($query) or die($mysqli->error());
		
		// $last_day
		$last_day = false;
		$show_day_row = false;
		
		while ($tsk = mysqli_fetch_array($rslt)) {
			
			// htmlspecialchars
			foreach($tsk as $k=>$v) {
				$tsk[$k] = htmlspecialchars($v);
			}
			
			// first/last
			// Only show the year when it is not *this* year
			$date = array('l'=>null, 'f'=>null, 'a'=>null);
			if (date('y') != date('y', $tsk['date_last_completed'])) {
				$date['l'] = date('j M, y', $tsk['date_last_completed']);
			} else {
				$date['l'] = date('j M', $tsk['date_last_completed']);
			}
			if (date('y') != date('y', $tsk['date_first_completed'])) {
				$date['f'] = date('j M, y', $tsk['date_first_completed']);
			} else {
				$date['f'] = date('j M', $tsk['date_first_completed']);
			}
			// age
			$date['a'] = $this->dateDiff(floor($tsk['date_first_completed']), 2, floor($tsk['date_last_completed']));
			
			// Account names
			$acct = $this->accounts_names($tsk['account_names']);
			//~ $tsk['account_names'] = preg_replace_callback('/(_redacted_|_redacted_|_redacted_|_redacted_)/', function($matches) { return substr($matches[1], 0, 1); }, $tsk['account_names']);
			//~ // NEW=Always display account names in age order (B,E,_redacted_,_redacted_,G)
			//~ // OLD=Always display account names in alph. order
			//~ $acct = array();
			//~ if (strpos($tsk['account_names'], 'B') !== false)
				//~ $acct[] = 'B';
			//~ if (strpos($tsk['account_names'], 'E') !== false)
				//~ $acct[] = 'E';
			//~ if (strpos($tsk['account_names'], '_redacted_') !== false)
				//~ $acct[] = 'Sv';
			//~ if (strpos($tsk['account_names'], 'S') !== false)
				//~ $acct[] = 'S';
			//~ if (strpos($tsk['account_names'], 'G') !== false)
				//~ $acct[] = 'G';
			//~ $acct = implode(',', $acct);
			
			// Total rewards stats
			// Let's try do not add if it is rejected (rewards_less_rejected)
			//~ $tsk_rwd_total = $tsk['reward_pending'] + $tsk['reward_accepted'] + $tsk['reward_paid'];
			//~ echo floatval($tsk['reward_pending']) .'+'. floatval($tsk['reward_accepted']) .'+'. floatval($tsk['reward_paid']) .'='. $tsk_rwd_total;
			
			// Is it a new day?
			// $last_day
			if ($date['l'] != $last_day) {
				$last_day = $date['l'];
				$last_day_fmt = date('D', $tsk['date_last_completed']) . ', ' . $date['l'];
				$show_day_row = true;
				
				// Increment the unique days stat!
				$this->stats['unique_days_worked']++;
			} else {
				$show_day_row = false;
			}
			
			// Total rewards stats
			// Let's try do not add if it is rejected (rewards_less_rejected)
			$this->stats['total_rewards'] += $tsk['reward'];
			//~ $this->stats['total_rewards'] += $tsk_rwd_total;
			
			// Stats, Days over $30
			//~ if ($this->stats['total_rewards'] >= 30) {
				//~ $this->stats['total_rewards_over_30']++;
			//~ }
			
			//~ // account name
			//~ $tsk['account_names'] = preg_replace_callback('/(_redacted_|_redacted_|_redacted_|_redacted_)/', function($matches) { return substr($matches[1], 0, 1); }, $tsk['account_names']);
			//~ // Always display account names in alph. order
			//~ $acct = array();
			//~ if (strpos($tsk['account_names'], 'B') !== false)
				//~ $acct[] = 'B';
			//~ if (strpos($tsk['account_names'], 'E') !== false)
				//~ $acct[] = 'E';
			//~ if (strpos($tsk['account_names'], 'G') !== false)
				//~ $acct[] = 'G';
			//~ if (strpos($tsk['account_names'], 'S') !== false)
				//~ $acct[] = 'S';
			//~ $acct = implode(',', $acct);
			//~ 
			//~ if ($date['l'] != $last_day) {
				//~ $last_day = $date['l'];
				//~ $last_day_fmt = date('D', $tsk['date_last_completed']) . ', ' . $date['l'];
				//~ $show_day_row = true;
			//~ } else {
				//~ $show_day_row = false;
			//~ }
			
			if ($show_day_row) {
				$this->table_body .= '
					<tr class="show-day-row">
						<td colspan=25 style="text-align:center;">
							'.$last_day_fmt.'
						</td>
					</tr>
				';
			}
			
			// If is_daily then do not show the number of 
			// days that task has been completed. This shows
			// as "1" in this case for all tasks. Although
			// as a TODO this could be made to show num days.
			if ($is_daily) {
				$tsk['date_count_num_days'] = '-';
			}
			
			// table_body
			$this->table_body .= '
				'.sprintf($table_row_html, $tsk['tasks_id']).'
					<td>
						<a href="#'.$tsk['requesters_requesterUserId'].'">
							'.$tsk['rns'].'
						</a>
					</td>
					<td>
						'.$tsk['title'].'
					</td>
					
					<td>
						'.$tsk['totals_submitted'].'
					</td>
					<td>
						'.$tsk['totals_accepted'].'
					</td>
					<td>
						'.$tsk['totals_rejected'].'
					</td>
					<td>
						'.$tsk['totals_pending'].'
					</td>
					
					<td>
						$'.$tsk['reward'].'
					</td>
					
					<td>
						# R
					</td>
					<td>
						# T
					</td>
					
					<td class="nobreak">
						'.$date['l'].'
					</td>
					<td class="nobreak">
						'.$date['f'].'
					</td>
					<td class="nobreak">
						'.$date['a'].'
					</td>
					<td class="nobreak">
						'.$tsk['date_count_num_days'].'
					</td>
					
					<td>
						'.$acct.'
					</td>
					
					<td>
						# F
					</td>
					<td>
						# B
					</td>
					
					<td>
						&nbsp;
					</td>
					<td>
						&nbsp;
					</td>
					
					<td>
						'.(($tsk['tasks_wakeupAlert'] == '1') ? '✔' : '-').'
						
					</td>
					<td>
						'.(($tsk['tasks_emailAlert'] == '1') ? '✔' : '-').'
					</td>
					<td>
						'.(($tsk['tasks_alarm_active'] == '1') ? '☺' : '-').'
					</td>
					
					<td>
						'.(($tsk['requesters_wakeupAlertDefault'] == '1') ? '✔' : '-').'
						
					</td>
					<td>
						'.(($tsk['requesters_emailAlertDefault'] == '1') ? '✔' : '-').'
					</td>
					<td>
						'.(($tsk['requesters_alarm_active'] == '1') ? '☺' : '-').'
					</td>
					
					<td>
					</td>
					
				</tr>
			';
		}
		
		$this->table_footer = '</tbody></table>';
		
		// Stats table
		$fmtrwd = $this->stats['total_rewards'];
		$fmtrwd = preg_replace('/(\.\d)$/', '${1}0', $fmtrwd); // .1 to .10
		$fmtrwd = preg_replace('/^(\d+)$/', '$1.00', $fmtrwd); // 1 to 1.00
		$this->stats_table = sprintf(
			$this->stats_table,
			'$' . $fmtrwd,
			$this->stats['unique_days_worked'],
			floor($this->stats['unique_days_worked'] / $this->stats['total_days_in_period']*100), // TOTAL STATS IS total_days_in_period-1!
			$this->stats['total_rewards_over_30'],
			floor($this->stats['total_rewards_over_30'] / $this->stats['total_days_in_period']*100) //
		);
	}
	#SUM(date_last_completed - date_first_completed) as date_age,
	//,				y desc, m desc, d desc
	// fk_titleRequesterHash
	//~ WHERE
			//~ ta_dates.year = 2014 && ta_dates.month >= 7 && ta_dates.day > 0
	
	
	
	/**
	 * FUNCS
	 */
	
	/**
	 * function dateDiff2
	 * 
	 * Human readable Year/Month/Day time offsets.
	 * 
	 * @param int $old_date
	 * @param int $accuracy
	 * @param int $now
	 */
	//http://www.techchattr.com/calculating-php-time-difference-time-ago-datetime-timestamp
	// author: Feras
	private function dateDiff($old_date, $accuracy = 2, $now = Null)
	{
		// you can specify more periods starting from smaller to larger!
		$periods = array("second" => 1, "minute" => 60, "h" => 3600, "d" => 86400, "w" => 604800, "m" => 2630880, "y" => 31570560, "decade" => 315705600);
		$periods = array_reverse($periods);
		$result_array = array(
			'decade'=>'',
			'y'=>'',
			'm'=>'',
			'w'=>'',
			'd'=>'',
			'hour'=>'',
			'minute'=>'',
			'second'=>''
		);
		
		if ($now === Null) {
			$now = time();
		}
		 
		if (!is_int($accuracy) || $accuracy < 1 || $accuracy > count($periods)) {
			throw new InvalidArgumentException("Date Accuracy should be an integer between 1 and 6");
		}
		
		$difference = $now - $old_date;
		if ($difference < 0) {
			throw new InvalidArgumentException('The previous date cannot be greater than todays date');
		}
		
		// LOOP THROUGH ONCE TO SET result_array
		foreach($periods as $period=>$v){
			//round the number of times a large period fits in the time difference
			$tmp = floor($difference/$v);
			$result_array[$period] = $tmp;
		}
		$result = array();
		$i = 0;
		foreach($periods as $period=>$v){
			//round the number of times a large period fits in the time difference
			$tmp = floor($difference/$v);
			if ($period == 'decade' || $period == 'h' || $period == 'minute' || $period == 'second')
				continue;
			if ($period == 'y' && $tmp == '0') // 0y
				continue;
			if ($period == 'm' && $tmp == '0' && $result_array['y'] == 0)  // 0y 0m
				continue;
			if ($period == 'w' && $tmp == '0' && $result_array['m'] == 0 && $result_array['y'] == 0)  // 0w
				continue;
			$result[] = $tmp . $period;
			
			//substract the reminder of the division if the difference can be divided by the current period in the loop
			$difference = $difference%$v;
			
			//~ $i++;
			//~ //break the loop if desired accuracy is reached
			//~ if($i == $accuracy){
				//~ break;
			//~ }
			
			// THIS WOULD BE PLURALIZED
			//~ if($tmp > 1){
				//~ $result .= $tmp . $k . 's';
				//~ $result .= $tmp . $k . 's';
				//~ $i++;
			//~ }elseif($tmp == 1){
				//~ $result .= $tmp . $k;
				//~ $i++;
			//~ }
		}
		$result = implode('/', $result);
		$result = ($result == '0d') ? '-' : $result;
		
		return $result;
		//~ $periods = array("second" => 1, "minute" => 60, "hour" => 3600, "day" => 86400, "week" => 604800, "month" => 2630880, "year" => 31570560, "decade" => 315705600);
	}
}
// END CLASS DISPLAYER

/**
 * page
 * $_GET['page']
 * 
 * @ recent_requesters
 * @ 
 * @ recent_tasks
 * @ recent_tasks_daily
 * 
 */
if (isset($_GET['page'])) {
	//~ echo 'abc';
	//~ print_r($_GET);
	/**
	 * recent_requesters
	 * $_GET['recent_requesters']
	 * 
	 * 
	 */
	if ($_GET['page'] == 'recent_requesters')
	{
		$a = new Displayer($_GET['page']);
		$a->head();
		$a->body();
		$a->recent_requesters();
		$a->footer();
		
		exit;
	}
	
	/**
	 * recent_tasks_overview
	 * $_GET['recent_tasks']
	 * 
	 * 
	 */
	if ($_GET['page'] == 'recent_requesters_daily')
	{
		$a = new Displayer($_GET['page']);
		$a->head();
		$a->body();
		$a->recent_requesters_daily();
		$a->footer();
		
		exit;
	}
	
	/**
	 * recent_tasks_overview
	 * $_GET['recent_tasks']
	 * 
	 * 
	 */
	if ($_GET['page'] == 'recent_tasks')
	{
		echo 'aaa';
		$a = new Displayer($_GET['page']);
		$a->head();
		$a->body();
		$a->recent_tasks();
		$a->footer();
		
		exit;
	}
	
	/**
	 * recent_tasks_daily
	 * $_GET['recent_tasks_daily']
	 * 
	 * 
	 */
	if ($_GET['page'] == 'recent_tasks_daily')
	{
		$a = new Displayer($_GET['page']);
		$a->head();
		$a->body();
		$a->recent_tasks_daily();
		$a->footer();
		
		exit;
	}
	
	/**
	 * tracker
	 * $_GET['tracker']
	 * 
	 * 
	 */
	if ($_GET['page'] == 'tracker')
	{
		$a = new Displayer($_GET['page']);
		$a->head();
		$a->body();
		$a->tracker_overview();
		$a->footer();
		
		exit;
	}
}


// else if not HITDB
?>
<html>
<head>
<style type="text/css">
/*general*/
hr,h4 {
	margin:0;
	padding:0;
}
/*span notice*/
.notice {
	border:4px solid blue;
	background-color:LightGreen;
	color:DarkMagenta;
	zoom:2;
}
/*center form*/
.center {
}
.center input {
	display:block;
}
.center div {
}
/*.new-task {
	height:60px;
	overflow:auto;
}
.new-task:hover {
	height:auto;
}
*/
</style>
</head>
Todo:<br>
Date Added,<br>
XX -add DB row which "user" (worker ID) added the task?<br>

HITDB Todo:<br>
 -Save CSV file from HITDB "Update"<br>
   -Save in file './logs/[worker ID].time().log'<br>
 -Scrape "CSV" for requesters/tasks in DB (& add new)<br>
 


<?php


// trim all post requests by default
foreach ($_POST as $key=>$value) {
	$_POST[$key] = trim($value);
}

// LEFT PANEL

// add new requester before showing
if (isset($_POST['add_requester'])) { // POST requesterName,requesterUserId,add_requester
	echo add_requester();
	echo '<hr />';
}
// update task before showing
if (isset($_POST['update_task'])) {
	echo update_task();
	echo '<hr />';
}
// add task before showing
if (isset($_POST['add_task'])) {
	echo add_task();
	echo '<hr />';
}
// update requester before showing
if (isset($_POST['update_requester'])) {
	echo update_requester();
	echo '<hr />';
}

// Always get_all_requester_names
get_all_requester_names();

// show_all_requesters() is always on 
// by default
echo show_all_requesters();
echo '<hr />';

// show_add_new_requester() is always on
// by default
echo show_add_new_requester();
echo '<hr />';
// end of the left panel //

alerter_editor_check_post();

echo '</body>
</html>';

// This entire block is checked for only when post var. requesterUserId is set.
// show_requester_editor, show_all_requester_tasks,
// show_add_new_task, on by default
// when requesterUserId is set
function alerter_editor_check_post() {
	if (isset($_POST['requesterUserId'])) {
		
		// MIDDLE PANEL
		echo show_requester_editor();
		echo '<hr />';
		echo show_all_requester_tasks();
		echo '<hr />';
		echo show_add_new_task(); // this form is partially hidden. To save space it is maybe 50px-100px until activated.
		echo '<hr />';
		
		
		// show_requester_editor is on by default
		// when requesterUserId is set
		if (isset($_POST['taskId'])) {
			echo show_task_editor();
			echo '<hr />';
		}
	}
}
function get_all_requester_names() {
	global $requester_names;
	global $mysqli;
	$rslt = $mysqli->query('
		SELECT
			requesterUserId, requesterName
		FROM
			requester_names
		ORDER BY
			requesterName ASC
	') or die($mysqli->error());
	while ($row = mysqli_fetch_array($rslt)) {
		// Exists
		$exists = array_key_exists($row['requesterUserId'], $requester_names);
		if ($exists) {
			array_push($requester_names[$row['requesterUserId']], $row['requesterName']);
		}
		// New
		else {
			$requester_names[$row['requesterUserId']] = array( $row['requesterName'] );
		}
	}
}
function show_all_requesters() {
	global $requester_names;
	$form = '<h4 style="margin-top:20px;">Select a requester to edit (by requester name)</h4>';
	$rid = (isset($_POST['requesterUserId'])) ? $_POST['requesterUserId'] : '';
	$form .= '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">';
	$select = '<select name="requesterUserId" onchange="javascript:if (this.value.trim()!=\'\') this.parentNode.submit();">';
	$select.= '<option value="">-- Requester Names --</option>';
	
	// $requester_names
	// These are sorted alphabetical. Grouped by ruid?
	foreach($requester_names as $ruid => $array_req_names) {
		// Requester Names
		$rn = implode(',', $array_req_names);
		// Is a current requester already selected
		// in the $_POST? Then set selected here.
		if ($rid == $ruid)
			$sel = ' selected';
		else
			$sel = '';
		$select .= '<option'.$sel.' value="'.htmlspecialchars($ruid).'">'.$rn.'</option>';
	}
	$select .= '</select>';
	$form .= $select;
	$form .= ' <input type="submit" />';
	$form .= '</form>';
	
	// Select By Requester IDs
	$form .= '<h4>Select a requester to edit (by requester id)</h4>';
	$rid = (isset($_POST['requesterUserId'])) ? $_POST['requesterUserId'] : '';
	$form .= '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">';
	$select = '<select name="requesterUserId" onchange="javascript:if (this.value.trim()!=\'\') this.parentNode.submit();">';
	$select.= '<option value="">-- Requester IDs --</option>';
	// ...
	foreach($requester_names as $ruid => $array_req_names) {
		// Requester Names
		$rn = implode(',', $array_req_names);
		// Is a current requester already selected
		// in the $_POST? Then set selected here.
		if ($rid == $ruid)
			$sel = ' selected';
		else
			$sel = '';
		$select .= '<option'.$sel.' value="'.htmlspecialchars($ruid).'">'.$ruid.'</option>';
	}
	$select .= '</select>';
	$form .= $select;
	$form .= ' <input type="submit" />';
	$form .= '</form>';
	
	// Enter req. id.
	$form .= '<h4>Enter a requester id to edit</h4>';
	$rid = (isset($_POST['requesterUserId'])) ? $_POST['requesterUserId'] : '';
	$form .= '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">';
	$form .= '<input name="requesterUserId" value="'.htmlspecialchars($rid).'">';
	$form .= ' <input type="submit" />';
	$form .= '</form>';
	
	return $form;
}
function show_add_new_requester() {
	$form = '<h4>Add requester</h4>';
	$form.= '<form action="'.$_SERVER['PHP_SELF'].'" method="POST" id="addRequesterForm">';
	$form.= '<div style="float:left;width:80px;">Name:</div>';
	$form.= '<input type="text" name="requesterName" style="display:block;" />';
	$form.= '<div style="float:left;width:80px;">ID:</div>';
	$form.= '<input type="text" name="requesterUserId" style="display:block;" id="addRequesterFormUID" />';
	$form.= '<input type="submit" name="add_requester" />';
	$form.= '</form>';
	$form.= '
<script type="text/javascript">
	// Block requester id if it has improper formatting.
	// Useful to ensure, e.g., Name and ID are not accidently
	// put into form in reverse.
	var tf = document.getElementById("addRequesterForm"); // The form.
	tf.onsubmit = function(e) {
		var uid = document.getElementById("addRequesterFormUID"); // UID
		uid = uid.value.trim();
		if (!/^[A-Z0-9]{10,20}$/.exec(uid)) { // 10-20 characters
			alert("Invalid requester id!");
			e.stopPropagation();
			e.preventDefault();
			return false;
		}
	}
		
</script>';
	return $form;
}
function add_requester() {
	global $mysqli;
	$return_str = '';
	
	$rslt = $mysqli->query('
		SELECT * FROM
			requesters
		WHERE
			requesterUserId="'.mysqli_real_escape_string($mysqli, $_POST['requesterUserId']).'"
	');
	if (mysqli_num_rows($rslt) > 0) { // exists!
		//~ return '<span class="notice">User ID is in database!</span>';
		$return_str .= '<span class="notice">User ID already in requesters table!</span>';
	}
	else {
		// Does not exist!
		$rslt = $mysqli->query('
			INSERT INTO requesters
			SET
				requesterUserId="'.mysqli_real_escape_string($mysqli, $_POST['requesterUserId']).'",
				wakeupAlertDefault=0,
				emailAlertDefault=0,
				moneyAmountThreshold=0,
				minTasksThreshold=0
		') or die($mysqli->error());
		
		$return_str .= '<span class="notice">User ID added to requesters table!</span>';
	}
	$rslt = $mysqli->query('
		SELECT * FROM
			requester_names
		WHERE
			requesterUserId	="'.mysqli_real_escape_string($mysqli, $_POST['requesterUserId']).'" &&
			requesterName	="'.mysqli_real_escape_string($mysqli, $_POST['requesterName']).'"
	');
	if (mysqli_num_rows($rslt) > 0) { // exists!
		//~ return '<span class="notice">User ID is in database!</span>';
		$return_str .= '<span class="notice">User name/ID already in names table!</span>';
	}
	else {
		$rslt = $mysqli->query('
			INSERT INTO requester_names
			SET
				requesterName	="'.mysqli_real_escape_string($mysqli, $_POST['requesterName']).'",
				requesterUserId	="'.mysqli_real_escape_string($mysqli, $_POST['requesterUserId']).'"
		') or die($mysqli->error());
		
		$return_str .= '<span class="notice">User name/ID added to names table!</span>';
	}
	// Reset requester names
	get_all_requester_names();
	return $return_str;
	
	
	//~ return '<span class="notice">User added to database!</span>';
}
function update_requester() {
	global $mysqli;
	$rslt = $mysqli->query('
		SELECT * FROM
			requesters
		WHERE
			id="'.mysqli_real_escape_string($mysqli, $_POST['id']).'"
	');
	if (mysqli_num_rows($rslt) == 0) { // none!
		return 'No requester found!';
	}
	// Still here?
	// requesterName		="'.mysqli_real_escape_string($mysqli, $_POST['requesterName']).'",
	$rslt = $mysqli->query('
		UPDATE
			requesters
		SET
			wakeupAlertDefault	="'.mysqli_real_escape_string($mysqli, $_POST['wakeupAlertDefault']).'",
			emailAlertDefault	="'.mysqli_real_escape_string($mysqli, $_POST['emailAlertDefault']).'",
			moneyAmountThreshold="'.mysqli_real_escape_string($mysqli, $_POST['moneyAmountThreshold']).'",
			minTasksThreshold	="'.mysqli_real_escape_string($mysqli, $_POST['minTasksThreshold']).'",
			requesterUserId		="'.mysqli_real_escape_string($mysqli, $_POST['requesterUserId']).'"
		WHERE
			id="'.mysqli_real_escape_string($mysqli, $_POST['id']).'"
	') or die($mysqli->error());
	return '<span class="notice">Requester saved!</span>';
}
function show_requester_editor() {
	global $requester_names;
	global $mysqli;
	$form = '<h4>Edit requester details</h4>';
	$rslt = $mysqli->query('
		SELECT * FROM requesters
		WHERE requesterUserId="'.mysqli_real_escape_string($mysqli, $_POST['requesterUserId']).'"
	');
	if (mysqli_num_rows($rslt) == 0) { // none!
		return $form . 'No requester found!';
	}
	// Requester exists!
	$row = mysqli_fetch_array($rslt);
	// Name
	$rn = implode(',', $requester_names[ $row['requesterUserId'] ]);
	// Form
	$form.= '<form action="'.$_SERVER['PHP_SELF'].'" method="POST" class="center">';
	//$form.= '<h4>Edit requester details</h4>';
	$form.= '<div style="float:left;width:200px;">Name:</div>';
	$form.= '<input type="text" value="'.htmlspecialchars($rn).'" style="" disabled />';
	$form.= '<div style="float:left;width:200px;">ID:</div>';
	$form.= '<input type="text" name="requesterUserId"			value="'.htmlspecialchars($row['requesterUserId']).'" style="" />';
	$form.= '<div style="float:left;width:200px;">Wake up alert default:</div>';
	$form.= '<input type="text" name="wakeupAlertDefault"	value="'.htmlspecialchars($row['wakeupAlertDefault']).'" style="" />';
	$form.= '<div style="float:left;width:200px;">Email alert default:</div>';
	$form.= '<input type="text" name="emailAlertDefault"	value="'.htmlspecialchars($row['emailAlertDefault']).'" style="" />';
	
	$form.= '<div style="float:left;width:200px;height:60px;">Minimum money threshold<br />(Integer, Dollars, Default=0)</div>';
	$form.= '<div style="display:block;height:60px;"><input type="text" name="moneyAmountThreshold"	value="'.htmlspecialchars($row['moneyAmountThreshold']).'" style="" /></div>';
	
	$form.= '<div style="float:left;width:200px;height:60px;">Minimum tasks available threshold<br />(Integer, Default=0)</div>';
	$form.= '<div style="display:block;height:60px;"><input type="text" name="minTasksThreshold"	value="'.htmlspecialchars($row['minTasksThreshold']).'" style="" /></div>';
	
	$form.= '<input type="hidden" name="id" value="'.htmlspecialchars($row['id']).'" />';
	$form.= '<input type="submit" name="update_requester" />';
	$form.= '</form>';
	return $form;
}
function show_add_new_task() { // class active/inactive (50px versus ~400px)
	$form = '<h4>Add new task</h4>';
	$form.= '<form action="'.$_SERVER['PHP_SELF'].'" method="POST" class="center new-task">';
	$form.= '<div style="float:left;width:200px;">Requester ID:</div>';
	$form.= '<input type="text" '.
			'value="'.htmlspecialchars($_POST['requesterUserId']).'" disabled style="background-color:none;" />';
	$form.= '<div style="float:left;width:200px;">Task title:</div>';
	$form.= '<input type="text" name="taskTitle"			value=""	style="" />';
	$form.= '<div style="float:left;width:200px;">Wake up alert:</div>';
	$form.= '<input type="text" name="wakeupAlert"			value="0"	style="" />';
	$form.= '<div style="float:left;width:200px;">Email alert:</div>';
	$form.= '<input type="text" name="emailAlert"			value="0"	style="" />';
	
	$form.= '<div style="float:left;width:200px;height:60px;">Minimum money threshold<br />(Integer, Dollars, Default=0)</div>';
	$form.= '<div style="display:block;height:60px;"><input type="text" name="moneyAmountThreshold"	value="0"	style="" /></div>';
	
	$form.= '<div style="float:left;width:200px;height:60px;">Minimum tasks threshold<br />(Integer, Default=0)</div>';
	$form.= '<div style="display:block;height:60px;"><input type="text" name="minTasksThreshold"	value="0"	style="" /></div>';
	
	$form.= '<input type="hidden" name="requesterUserId" value="'.htmlspecialchars($_POST['requesterUserId']).'" />';
	$form.= '<input type="submit" name="add_task" value="Add Task" />';
	$form.= '</form>';
	return $form;
}
function add_task() {
	global $mysqli;
	// Add task
	$query = '
		INSERT INTO
			tasks
		SET
			taskTitle="'.			mysqli_real_escape_string($mysqli, $_POST['taskTitle']).'",
			titleRequesterHash="'.	mysqli_real_escape_string($mysqli, $_POST['requesterUserId']).mysqli_real_escape_string($mysqli, $_POST['taskTitle']).'",
			wakeupAlert="'.			mysqli_real_escape_string($mysqli, $_POST['wakeupAlert']).'",
			emailAlert="'.			mysqli_real_escape_string($mysqli, $_POST['emailAlert']).'",
			moneyAmountThreshold="'.mysqli_real_escape_string($mysqli, $_POST['moneyAmountThreshold']).'",
			minTasksThreshold="'.	mysqli_real_escape_string($mysqli, $_POST['minTasksThreshold']).'",
			requesterUserId="'.		mysqli_real_escape_string($mysqli, $_POST['requesterUserId']).'",
			dateAdded="'.			time().'"
	';
	// Debug
	//~ print_r($query);
	$rslt = $mysqli->query($query) or die($mysqli->error());
	return '<span class="notice">Task added to database!</span>';
}
function update_task() {
	global $mysqli;
	$rslt = $mysqli->query('
		SELECT * FROM
			tasks
		WHERE
			id="'.mysqli_real_escape_string($mysqli, $_POST['taskId']).'"
	');
	if (mysqli_num_rows($rslt) == 0) { // none!
		return 'No task found!';
	}
	// still here?
	$rslt = $mysqli->query('
		UPDATE
			tasks
		SET
			taskTitle="'.			mysqli_real_escape_string($mysqli, $_POST['taskTitle']).'",
			wakeupAlert="'.			mysqli_real_escape_string($mysqli, $_POST['wakeupAlert']).'",
			emailAlert="'.			mysqli_real_escape_string($mysqli, $_POST['emailAlert']).'",
			moneyAmountThreshold="'.mysqli_real_escape_string($mysqli, $_POST['moneyAmountThreshold']).'",
			minTasksThreshold="'.	mysqli_real_escape_string($mysqli, $_POST['minTasksThreshold']).'",
			requesterUserId="'.		mysqli_real_escape_string($mysqli, $_POST['requesterUserId']).'"
		WHERE
			id="'.mysqli_real_escape_string($mysqli, $_POST['taskId']).'"
	') or die($mysqli->error());
	return '<span class="notice">Task saved!</span>';
}
function show_task_editor() {
	global $mysqli;
	$form = '<h4>Edit task</h4>';
	$rslt = $mysqli->query('
		SELECT * FROM tasks
		WHERE id="'.mysqli_real_escape_string($mysqli, $_POST['taskId']).'"
	');
	if (mysqli_num_rows($rslt) == 0) { // none!
		return $form.'No task found!';
	}
	// task exists!
	$row = mysqli_fetch_array($rslt);
	$form.= '<form action="'.$_SERVER['PHP_SELF'].'" method="POST" class="center">';
	//$form.= '<h4>Edit task</h4>';
	$form.= '<div style="float:left;width:200px;">DB Task ID:</div>';
	$form.= '<input type="text" '.
			'value="'.htmlspecialchars($row['id']).'"				disabled style="background-color:none;" />';
	$form.= '<div style="float:left;width:200px;">DB Requester ID:</div>';
	$form.= '<input type="text" '.
			'value="'.htmlspecialchars($row['requesterUserId']).'"	disabled style="background-color:none;" />';
	$form.= '<div style="float:left;width:200px;">Task title:</div>';
	$form.= '<input type="text" name="taskTitle"			value="'.htmlspecialchars($row['taskTitle']).'"				style="" />';
	$form.= '<div style="float:left;width:200px;">Wake up alert:</div>';
	$form.= '<input type="text" name="wakeupAlert"			value="'.htmlspecialchars($row['wakeupAlert']).'"			style="" />';
	$form.= '<div style="float:left;width:200px;">Email alert:</div>';
	$form.= '<input type="text" name="emailAlert"			value="'.htmlspecialchars($row['emailAlert']).'"			style="" />';
	
	$form.= '<div style="float:left;width:200px;height:60px;">Minimum money threshold<br />(Integer, Dollars, Default=0)</div>';
	$form.= '<div style="display:block;height:60px;"><input type="text" name="moneyAmountThreshold"	value="'.htmlspecialchars($row['moneyAmountThreshold']).'"	style="" /></div>';
	
	$form.= '<div style="float:left;width:200px;height:60px;">Minimum tasks threshold<br />(Integer, Default=0)</div>';
	$form.= '<div style="display:block;height:60px;"><input type="text" name="minTasksThreshold"	value="'.htmlspecialchars($row['minTasksThreshold']).'"	style="" /></div>';
	
	$form.= '<input type="hidden" name="taskId" value="'.htmlspecialchars($row['id']).'" />';
	$form.= '<input type="hidden" name="requesterUserId" value="'.htmlspecialchars($_POST['requesterUserId']).'" />';
	$form.= '<input type="submit" name="update_task" />';
	$form.= '</form>';
	return $form;
}
function show_all_requester_tasks() {
	global $mysqli;
	$form = '<h4>Select a task to edit</h4>';
	$rslt = $mysqli->query('
		SELECT * FROM
			tasks
		WHERE
			requesterUserId="'.mysqli_real_escape_string($mysqli, $_POST['requesterUserId']).'"
	');
	if (mysqli_num_rows($rslt) == 0) { // none!
		return $form.'No requester tasks found!';
	}
	// select
	$form.= '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">';
	//$form.= '<h4>Select a task to edit</h4>';
	$select = '<select name="taskId" onchange="javascript:if (this.value.trim()!=\'\') this.parentNode.submit();">';
	$select.= '<option value=""> -- Tasks -- </option>';
	$tid = (isset($_POST['taskId'])) ? $_POST['taskId'] : '';
	while ($row = mysqli_fetch_array($rslt)) {
		// Is a current requester already selected
		// in the $_POST? Then set selected here.
		if ($tid == $row['id'])
			$selected = ' selected';
		else
			$selected = '';
		$select .= '<option'.$selected.' value="'.htmlspecialchars($row['id']).'">'.$row['taskTitle'].'</option>';
	}
	$select .= '</select>';
	$form .= $select;
	$form.= '<input type="hidden" name="requesterUserId" value="'.htmlspecialchars($_POST['requesterUserId']).'" />';
	$form .= '</form>';
	return $form;
}
/**
 * Function connect
 * Returns connection resource.
 */
function connect() {
	$mysql_password = "_redacted_";
	// Connect
	$mysqli = new mysqli("localhost", "root", $mysql_password, "AMT_Alerts");
	// Test connection
	if (mysqli_connect_errno()) {
		printf("Connect failed: %s\n", mysqli_connect_error());
		exit();
	}
	// Set UTF-8
	$mysqli->query("Set names 'utf8'"); 
	// Return
	return $mysqli;
}

//~ function add_requester() {
//~ }
/*
* EMAIL new jobs
* Wakeup new jobs
foreach new job {
	if (rid is in [rid1, rid2, rid3]) { (in_array(haystack, needle, optional return key?))
		
	}
}
Alert-Object
PieSize -- 
[
	{
		"requesterNames"		: ["xxxxxxx"],
		"requesterUserId"			: "xxxxxxx",
		"wakeupAlertDefault"		: boolean,
								//True: Opens a wake-up alarm for all jobs, except for jobs with alert=false
								//False: Does not open a wakeup alarm for any job, except for jobs with alert= true
		"emailAlertDefault"		: boolean,
								//True: Sends an email for all jobs, except for jobs with email=false:
								//False: Does not send an email for any job, except for jobs with email=true;
		"moneyAmountThesh"	: 0, // dollars
		"minHitsThresh"		: 0,
		"jobs"					: [
			{
				"wakeupAlert"			: boolean,
				"emailAlert"			: boolean,
				"hitTitle"			:"xxxxxxx",
				"minimumHitsThreshold"		:"xxxxxxx",
				"moneyAmountThesh"		:"xxxxxxx",
			}
		]
	}
]
*/

	

	


?>
