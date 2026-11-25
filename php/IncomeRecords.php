<?php

date_default_timezone_set('America/New_York');

$DEBUG = true;
if($DEBUG) {
	error_reporting(E_ALL);
	ini_set('display_errors', '1');

	//$_GET['GetLatest'] = 1;
	//$_GET['ComputerId'] = 1;
	//$_GET['DailyUpdate'] = 1;
}

// DB
$mysql_host = "localhost"; // localhost
$mysql_database = "IncomeRecords";
$mysql_user = "root";
$mysql_password = "_redacted_";
// Connect
$mysqli = new mysqli("localhost", "root", $mysql_password, $mysql_database );
// Test connection
if (mysqli_connect_errno()) {
    printf("Connect failed: %s\n", mysqli_connect_error());
    exit();
}
// Set UTF-8
// JUNE 27, 2015
// THIS LOOKS TO BE NECESSARY. IN CERTAIN CASES SUCH AS "’" BAD DATA
// E.G. "???" IS WRITTEN TO DB.
$mysqli->query("Set names 'utf8'"); 




function htmlToMturkEntity($str) {
	// This is broken for now.
	// For some reason it is reading in, at a number of points,
	// the HTML representation of certain elements, such as apostrophes.
	// To be sure, at the start it is an apostrophe on the AMT
	// site (not like before, when it was actually &#39;).
	// So, somewhere along the line it is being corrupted and converted
	// into htmlspecialchars.
	
	
	// OLD ONES
	//~ $str = str_replace('"',		'&quot;', $str);
	//~ $str = str_replace('&#0',	'&#', $str);
	//~ $str = str_replace("\\u2019",	'’', $str); //
	//~ $str = str_replace("'",		'&#39;', $str);
	
	// Convert back to "normal"
	$str = str_replace("&#39;",	"'", $str);
	
	 
	return $str;
}


if(isset($argv) && $argv[1] == 'DailyClear') {file_put_contents('/home/_redacted_/tmp_cron_dbg', 'x');
	$dayBegin = DateTime::createFromFormat('U',strtotime('now'));
	$dayBegin->setTimestamp($dayBegin->getTimestamp() - 60*60*3);
	$dayBegin->setTimezone(new DateTimeZone('America/New_York'));
	$dayBeginYear = $dayBegin->format('Y')*1;//.'<br>';
	$dayBeginMonth = $dayBegin->format('m')*1;
	$dayBeginDay = $dayBegin->format('d');
	$GLOBALS['dayBeginDay'] = $dayBeginDay;
	$GLOBALS['dayBeginMonth'] = $dayBeginMonth;
	$GLOBALS['dayBeginYear'] = $dayBeginYear;
	$GLOBALS['dayBegin'] = mktime(3,0,0,$dayBeginMonth,$dayBeginDay,$dayBeginYear);//?????????????????????//
	
	//Update Hits_Daily so that it doesn't include anything from before today
	$deleteDayQuery = "DELETE FROM HITS_DAILY WHERE ".
					  "TimeAccepted <".$GLOBALS['dayBegin']." AND ".
					  "TimeFinished != 0";
	$mysqli->query($deleteDayQuery) or die(mysqli_error($mysqli).' error in query was: '.$deleteDayQuery);
	//~ file_put_contents('/home/_redacted_/tmp_cron_dbg', $deleteDayQuery);
	
	// This will delete all non-finished older than three days.
	$deleteDayQuery = "DELETE FROM HITS_DAILY WHERE ".
					  "TimeAccepted <".($GLOBALS['dayBegin'] - (86400*3))." AND ".
					  "TimeFinished = 0";
	$mysqli->query($deleteDayQuery) or die(mysqli_error($mysqli).' error in query was: '.$deleteDayQuery);
	//~ file_put_contents('/home/_redacted_/tmp_cron_dbg', $deleteDayQuery);
	exit;
}
if(isset($_GET['FullUpdate']) || isset($_GET['DailyUpdate']) || isset($argv)) {exit;
	$runTimeStart = strtotime('now');
	
	//Get the time of 12:00 a.m. pacific time
	//That's three hours before now
	echo 'Running Update';
	echo strtotime('now')."<br>";
	
	$dayBegin = DateTime::createFromFormat('U',strtotime('now'));
	$dayBegin->setTimestamp($dayBegin->getTimestamp() - 60*60*3);
	$dayBegin->setTimezone(new DateTimeZone('America/New_York'));
	echo $dayBegin->format('Y-m-d').'<br>';
	$dayBeginYear = $dayBegin->format('Y')*1;//.'<br>';
	$dayBeginMonth = $dayBegin->format('m')*1;
	$dayBeginDay = $dayBegin->format('d');
	$GLOBALS['dayBeginDay'] = $dayBeginDay;
	$GLOBALS['dayBeginMonth'] = $dayBeginMonth;
	$GLOBALS['dayBeginYear'] = $dayBeginYear;
	
	$GLOBALS['dayBegin'] = mktime(3,0,0,$dayBeginMonth,$dayBeginDay,$dayBeginYear);//?????????????????????//
	echo "GLOBALS['dayBegin'] ".$GLOBALS['dayBegin'].'<br>';
	
	$dayOfWeek = date('l', mktime(0,0,0,$dayBeginMonth,$dayBeginDay,$dayBeginYear));		
	$GLOBALS['dayOfWeek'] = $dayOfWeek;
	$GLOBALS['yesterdayBegin'] = $GLOBALS['dayBegin'] - 24*60*60;




	$GLOBALS['isBeginningOfWeek'] = false;
	$GLOBALS['isBeginningOfMonth'] = false;
	$GLOBALS['isBeginningOfYear'] = false;

	if($GLOBALS['dayOfWeek'] == "Monday") {
		$GLOBALS['isBeginningOfWeek'] = true;
	}
	
	if($GLOBALS['dayBeginDay'] == 1) {
		$GLOBALS['isBeginningOfMonth'] = true;
	}
		
	if($GLOBALS['dayBeginMonth'] == 1 && $GLOBALS['dayBeginDay'] == 1) {
		$GLOBALS['isBeginningOfYear'] = true;
	}

	

	//Get the beginning of this week
	$offsetTime = 24*60*60;
	switch($dayOfWeek) {
		case 'Monday': $offsetTime *= 0;
		break;
		case 'Tuesday': $offsetTime *= 1;
		break;
		case 'Wednesday': $offsetTime *= 2;
		break;
		case 'Thursday': $offsetTime *= 3;
		break;
		case 'Friday': $offsetTime *= 4;
		break;
		case 'Saturday': $offsetTime *= 5;
		break;
		case 'Sunday': $offsetTime *= 6;
		break;
	}

	$GLOBALS['weekBegin'] = $GLOBALS['dayBegin'] - $offsetTime;	
	$GLOBALS['monthBegin'] = mktime(3,0,0,$dayBeginMonth,1,$dayBeginYear);
	echo '<br>'.$GLOBALS['monthBegin'].'<br>';
	$GLOBALS['yearBegin'] = mktime(3,0,0,1,1,$dayBeginYear);
	echo $GLOBALS['yearBegin'];

	
	
	//Update Hits_Daily so that it doesn't include anything from before today
	$deleteDayQuery = "DELETE FROM HITS_DAILY WHERE ".
					  "TimeAccepted <".$GLOBALS['dayBegin']." AND ".
					  "TimeFinished != 0";
					  //AND id != 03450329647456?
	$mysqli->query($deleteDayQuery) or die(mysqli_error($mysqli).' error in query was: '.$deleteDayQuery);
	

	

	
	
	$iStats = new IncomeStats();
	$updateOnlyFromYesterday = false;

	$cachedStats = new CachedStats();


	/*if(isset($_GET['DailyUpdate']) || isset($argv)) {
		$updateOnlyFromYesterday = true;
		$cachedStats->readStats();
		$iStats->allAccountStats = $cachedStats->allAccountStats;
		$iStats->allUserStats = $cachedStats->allUserStats;
	}*/

	$iStats->updateUnique_Jobs($updateOnlyFromYesterday);
	$iStats->generateCachedStats($updateOnlyFromYesterday);
	$iStats->generateCachedHistoryStats($updateOnlyFromYesterday);
	$cachedStats->writeStats($iStats);
	var_dump($iStats);

	$runTimeEnd = strtotime('now');
	$runTime = ($runTimeEnd - $runTimeStart);
	echo "<br>Total Runtime: $runTime seconds";
}


if(isset($_GET['resolvedHit'])) {
	echo 'Arrived Successfully with Resolved HIT';
	$resolvedHIT = json_decode($_GET['resolvedHit']);
	echo 'JSON of recieved resolved HIT'.json_encode($resolvedHIT);
	
	if($resolvedHIT->isFailed)
		$resolvedHIT->isFailed = 1;
	else
		$resolvedHIT->isFailed = 0;
	
	if($resolvedHIT->returned)
		$resolvedHIT->returned = 1;
	else
		$resolvedHIT->returned = 0;
	
	/**
	 * Make sure the HIT hasn't already been resolved
	 * The if statement will also succeed if, for some reason,
	 * the mysql statement is malformed.
	 * **/
	 $isResolvedQuery = $mysqli->query('SELECT * FROM HITS WHERE '. 
											'AccountName = "'.mysqli_real_escape_string($mysqli, $resolvedHIT->accountName).'" AND '.
											'TimeFinished > 0 AND '.
											'HitId = "'.mysqli_real_escape_string($mysqli, $resolvedHIT->hitId).'" ORDER BY Id DESC LIMIT 0, 1') or die(mysqli_error($mysqli));
	$isResolved = mysqli_num_rows($isResolvedQuery);
	echo '<br>is resolved: ';
	var_dump($isResolved);
	$timeFinished = strtotime('now');
	
	 
	if($isResolved) {
		echo 'Error hitId of '.$resolvedHIT->hitId.' was already resolved in the database OR the hitId/username/accountName/computerId is wrong';
		return;
	}
	
	$updateResolvedQuery = 	'UPDATE HITS SET '.
				'Returned = '.mysqli_real_escape_string($mysqli, $resolvedHIT->returned).', '.
				'UserName="'.mysqli_real_escape_string($mysqli, $resolvedHIT->userName).'", '.
				'TimeFinished='.mysqli_real_escape_string($mysqli, $timeFinished).', '.
				'IsFailed='.mysqli_real_escape_string($mysqli, $resolvedHIT->isFailed).', '.
				'TypeOfFail="'.mysqli_real_escape_string($mysqli, $resolvedHIT->typeOfFail).'" '.
				'WHERE '.
				'HitId="'.mysqli_real_escape_string($mysqli, $resolvedHIT->hitId).'" AND '.
				'AccountName="'.mysqli_real_escape_string($mysqli, $resolvedHIT->accountName).'"';
	var_dump($updateResolvedQuery);
	
	$mysqli->query($updateResolvedQuery) or die(mysqli_error($mysqli));

	$updateResolvedQuery = 	'UPDATE HITS_DAILY SET '.
			'Returned = '.mysqli_real_escape_string($mysqli, $resolvedHIT->returned).', '.
			'UserName="'.mysqli_real_escape_string($mysqli, $resolvedHIT->userName).'", '.
			'TimeFinished='.mysqli_real_escape_string($mysqli, $timeFinished).', '.
			'IsFailed='.mysqli_real_escape_string($mysqli, $resolvedHIT->isFailed).', '.
			'TypeOfFail="'.mysqli_real_escape_string($mysqli, $resolvedHIT->typeOfFail).'" '.
			'WHERE '.
			'HitId="'.mysqli_real_escape_string($mysqli, $resolvedHIT->hitId).'" AND '.
			'AccountName="'.mysqli_real_escape_string($mysqli, $resolvedHIT->accountName).'"';
	$mysqli->query($updateResolvedQuery) or die(mysqli_error($mysqli));
}

//echo var_dump($_GET);
if(isset($_GET['HIT'])) {
	echo 'Arrived Successefully with a HIT\n<br>';
	$HIT = json_decode($_GET['HIT']);
	
	// Use htmlspecialchars to encode
	// the HTML.
	$HIT->requesterName = ($HIT->requesterName);
	$HIT->jobName = htmlToMturkEntity($HIT->jobName);
	
	//~ echo str_replace('2019', 'XX', $HIT->jobName);
	//~ echo str_replace('\u', 'BB', $HIT->jobName);
	//~ echo str_replace('\\u', 'AA', $HIT->jobName);
	
	
	echo 'JSON of recieved HIT'.json_encode($HIT).'<br>';	
	echo '$HIT requester name '.$HIT->requesterName.'<br>';	
	
	/**
	 * Make sure that this AccountName+HitId isn't already placed in the Database
	 * 
	 */	 
 	 $alreadyAddedQuery = $mysqli->query(
		'SELECT * FROM HITS WHERE '. 
		'AccountName = "'.mysqli_real_escape_string($mysqli, $HIT->accountName).'" AND '.
		'HitId = "'.mysqli_real_escape_string($mysqli, $HIT->hitId).'" ORDER BY Id DESC LIMIT 0, 1'
	) or die(mysqli_error($mysqli));
	if(mysqli_num_rows($alreadyAddedQuery) != 0)
	{
		echo "PHP ran, hitId+AccountName has already been added";
		return;
	}
	 
	 
	 $timeAccepted = strtotime('now');
	 
	 /*
	  * Check to see if this is a new job (or new version of an old job), if so add it to IncomeRecords.Unique_Jobs
	  * */
	 $UniqueJobQuery = 'SELECT * FROM Unique_Jobs WHERE '.
							'JobName = "'.mysqli_real_escape_string($mysqli, $HIT->jobName).'" AND '.
							'RequesterName = "'.mysqli_real_escape_string($mysqli, $HIT->requesterName).'" AND '.
							'Reward = '.mysqli_real_escape_string($mysqli, $HIT->reward).' '.
							'LIMIT 0,1';
	$rslt = $mysqli->query($UniqueJobQuery);
	if ($rslt && mysqli_num_rows($rslt) && mysqli_num_rows($rslt) > 0) {
		$matchFound = mysqli_num_rows($rslt);
		var_dump($matchFound);
		echo 'Number found = '.$matchFound.'<br>';
		echo 'Job already added to Unique_Jobs<br>';
		$uniqueJob = mysqli_fetch_array($rslt);
		$uniqueJobHash = $uniqueJob['UniqueJobHash'];
		echo 'uniqueJobHash: '.$uniqueJobHash;
	} else {
		$uniqueJobHash = uniqid();
		echo 'Found new unique job<br>';
		$UniqueJobInsert = 'INSERT INTO Unique_Jobs '.
							'SET '.
							'RequesterName = "'.mysqli_real_escape_string($mysqli, $HIT->requesterName).'", '.
							'JobName = "'.mysqli_real_escape_string($mysqli, $HIT->jobName).'", '.
							'Reward = '.mysqli_real_escape_string($mysqli, $HIT->reward).', '.
							'UniqueJobHash = "'.mysqli_real_escape_string($mysqli, $uniqueJobHash).'", '.
							'TimeFirstAccepted = '.mysqli_real_escape_string($mysqli, $timeAccepted);
		echo 'UJI:'.$UniqueJobInsert.'<br>';
		$mysqli->query($UniqueJobInsert) or die('AddingUniqueJob'.mysqli_error($mysqli));
		
	}
	 
	//'30 minutes';
	//Calculate Expiration Time
	$expReg = '/([\d]+) ([\w]+)/';
	$matched = preg_match($expReg, $HIT->duration, $expTime);
	if(!$matched) {
		echo 'Error Duration Scrape in PHP IncomeRecords - Send Alert';
		return;
	}
	$expTime[1] = intval($expTime[1]);
	switch($expTime[2]) {
		case 'minutes': 
			$expTime[1] = $expTime[1]  * 60; echo 'ran here';
		break; 
		case 'hours':
			$expTime[1]	*=  60 * 60;
		break; 
		case 'days':
			$expTime[1]	*=  60 * 60 * 24;
		break; 
		case 'weeks':
			$expTime[1]	*=  60 * 60 * 24 * 7;
		break; 
	}
	$expTime[1] += $timeAccepted;
	
	// 
	$q = '
		INSERT INTO HITS
		SET
			RequesterName="'.mysqli_real_escape_string($mysqli, $HIT->requesterName).'",
			Reward="'.mysqli_real_escape_string($mysqli, $HIT->reward).'",
			RequesterId="'.mysqli_real_escape_string($mysqli, $HIT->requesterId).'",
			HitId="'.mysqli_real_escape_string($mysqli, $HIT->hitId).'",
			JobName="'.mysqli_real_escape_string($mysqli, $HIT->jobName).'",
			GroupId="'.mysqli_real_escape_string($mysqli, $HIT->groupId).'",
			TimeAccepted='.mysqli_real_escape_string($mysqli, $timeAccepted).',
			TimeFinished='.mysqli_real_escape_string($mysqli, 0).',			
			ExpirationTime='.mysqli_real_escape_string($mysqli, $expTime[1]).',			
			UserName="'.mysqli_real_escape_string($mysqli, $HIT->userName).'",			
			AccountName="'.mysqli_real_escape_string($mysqli, $HIT->accountName).'",			
			ComputerId='.mysqli_real_escape_string($mysqli, $HIT->computerId).',
			UniqueJobHash="'.mysqli_real_escape_string($mysqli, $uniqueJobHash).'"
	';
	echo 'HITS insert:'.$q.'<br>';
	//Add it to HITS
	$rslt = $mysqli->query($q) or die(mysqli_error($mysqli));

	//Add it to HITS daily
	$rslt = $mysqli->query('
		INSERT INTO HITS_DAILY
		SET
			RequesterName="'.mysqli_real_escape_string($mysqli, $HIT->requesterName).'",
			Reward="'.mysqli_real_escape_string($mysqli, $HIT->reward).'",
			RequesterId="'.mysqli_real_escape_string($mysqli, $HIT->requesterId).'",
			HitId="'.mysqli_real_escape_string($mysqli, $HIT->hitId).'",
			JobName="'.mysqli_real_escape_string($mysqli, $HIT->jobName).'",
			GroupId="'.mysqli_real_escape_string($mysqli, $HIT->groupId).'",
			TimeAccepted='.mysqli_real_escape_string($mysqli, $timeAccepted).',
			TimeFinished='.mysqli_real_escape_string($mysqli, 0).',			
			ExpirationTime='.mysqli_real_escape_string($mysqli, $expTime[1]).',			
			UserName="'.mysqli_real_escape_string($mysqli, $HIT->userName).'",			
			AccountName="'.mysqli_real_escape_string($mysqli, $HIT->accountName).'",			
			ComputerId='.mysqli_real_escape_string($mysqli, $HIT->computerId).',
			UniqueJobHash="'.mysqli_real_escape_string($mysqli, $uniqueJobHash).'"
	') or die(mysqli_error($mysqli));

	echo "PHP ran and sent";
}
else if(isset($_GET['GetLatest'])) {	
	//echo 'User: ' + $_GET['UserName'].
	
	$dayBegin = DateTime::createFromFormat('U',strtotime('now'));
	$dayBegin->setTimestamp($dayBegin->getTimestamp() - 60*60*3);
	$dayBegin->setTimezone(new DateTimeZone('America/New_York'));
	//echo $dayBegin->format('Y-m-d').'<br>';
	$dayBeginYear = $dayBegin->format('Y')*1;//.'<br>';
	$dayBeginMonth = $dayBegin->format('m')*1;
	$dayBeginDay = $dayBegin->format('d');

	$GLOBALS['dayBegin'] = mktime(3,0,0,$dayBeginMonth,$dayBeginDay,$dayBeginYear);


	//Update Hits Daily so that it doesn't include anything from before today
	$deleteDayQuery = "DELETE FROM HITS_DAILY WHERE ".
					  "TimeAccepted <".$GLOBALS['dayBegin']." AND ".
					  "TimeFinished > 0";
					  //AND id != 03450329647456?
	$mysqli->query($deleteDayQuery) or die(mysqli_error($mysqli).' error in query was: '.$deleteDayQuery);
	
	$dayOfWeek = date('l', mktime(3,0,0,$dayBeginMonth,$dayBeginDay,$dayBeginYear));	
	$offsetTime = 24*60*60;
	switch($dayOfWeek) {
		case 'Monday': $offsetTime *= 0;
		break;
		case 'Tuesday': $offsetTime *= 1;
		break;
		case 'Wednesday': $offsetTime *= 2;
		break;
		case 'Thursday': $offsetTime *= 3;
		break;
		case 'Friday': $offsetTime *= 4;
		break;
		case 'Saturday': $offsetTime *= 5;
		break;
		case 'Sunday': $offsetTime *= 6;
		break;
	}
	$GLOBALS['weekBegin'] = $GLOBALS['dayBegin'] - $offsetTime;	
	
	$iStats = new IncomeStats();
	$iStats->generateStats();
	
	//echo 'got here';
	echo json_encode($iStats);
	
}



class IncomeStats {
	public $allAccountStats;
	public $allUserStats;
	public $currentJobStats;
	public $combinedStats;
	//public $updateOnlyFromYesterday;
	public static $cachedStats;
	
	public function __construct() {
			$GLOBALS['allAccountNames'] = array('_redacted_','_redacted_','_redacted_','_redacted_','_redacted_');
			$GLOBALS['allUserNames'] = array('_redacted_','_redacted_','_redacted_');
	}
	
	public function updateUnique_Jobs($updateOnlyFromYesterday) {
		 /* For each job in HITS:
		  * Check to see if this is a new job
		  * 	if so add it to IncomeRecords.Unique_Jobs
		  *		otherwise, update the sum(Reward)
		  * */
		global $mysqli; 
		  $timeOfInterest = null;
		  if($updateOnlyFromYesterday)
			$timeOfInterest = $GLOBALS['yesterdayBegin'];
		  else
			$timeOfInterest = 1;
		  
		  $getAllUniqueJobsQuery = 'SELECT RequesterName, JobName, Reward, SUM(Reward), MIN(TimeAccepted) '.
									   'FROM HITS WHERE '.
									   'TimeAccepted > '.$timeOfInterest.' AND '.
									   'TimeAccepted < '.$GLOBALS['dayBegin'].' AND '.
									   'TimeFinished > 0 '.
									   'GROUP BY RequesterName, JobName, Reward';
		echo $getAllUniqueJobsQuery;
		$sqlPointer = $mysqli->query($getAllUniqueJobsQuery) or die(mysqli_error($mysqli).' query was: '.$getAllUniqueJobsQuery);
		echo 'GOT HERE';
	
	
		while($HIT = mysqli_fetch_array($sqlPointer)){
			echo '1';
			
			$countAllReturnedQuery = 'SELECT COUNT(*) FROM HITS WHERE '.
									 'JobName = "'.mysqli_real_escape_string($mysqli, $HIT['JobName']).'" AND '.
									 'RequesterName = "'.mysqli_real_escape_string($mysqli, $HIT['RequesterName']).'" AND '.
									 'Reward = '.mysqli_real_escape_string($mysqli, $HIT['Reward']).' AND '.
									 'TimeFinished > 0 AND '.
									 'Returned = 1';
			$countAllSubmittedQuery = 'SELECT COUNT(*) FROM HITS WHERE '.
									 'JobName = "'.mysqli_real_escape_string($mysqli, $HIT['JobName']).'" AND '.
									 'RequesterName = "'.mysqli_real_escape_string($mysqli, $HIT['RequesterName']).'" AND '.
									 'Reward = '.mysqli_real_escape_string($mysqli, $HIT['Reward']).' AND '.
									 'TimeFinished > 0 AND '.
									 'Returned = 0';
			$allNumReturns = $mysqli->query($countAllReturnedQuery) or die (mysqli_error($mysqli)."query was: $countAllReturnedQuery");
			$allNumReturns = mysqli_fetch_array($allNumReturns)['COUNT(*)'];
			$allNumSubmitted = $mysqli->query($countAllSubmittedQuery) or die (mysqli_error($mysqli)."query was: $countAllSubmittedQuery");
			$allNumSubmitted = mysqli_fetch_array($allNumSubmitted)['COUNT(*)'];
			echo '<br>allNumReturns: '.$allNumReturns;
			echo '<br>allNumSubmitted: '.$allNumSubmitted.'<br>';
			
			
			$UniqueJobQuery = 'SELECT * FROM Unique_Jobs WHERE '.
								'JobName = "'.mysqli_real_escape_string($mysqli, $HIT['JobName']).'" AND '.
								'RequesterName = "'.mysqli_real_escape_string($mysqli, $HIT['RequesterName']).'" AND '.
								'Reward = '.mysqli_real_escape_string($mysqli, $HIT['Reward']).' '.
								'LIMIT 0,1';
			$rslt = $mysqli->query($UniqueJobQuery) or die(mysqli_error($mysqli)." query was: $UniqueJobQuery");
			$matchFound = mysqli_num_rows($rslt);
			
			
			
			if($matchFound == 0) {
				echo '2';
				 $uniqueJobHash = uniqid();
				 $UniqueJobInsert = 'INSERT INTO Unique_Jobs '.
								'SET '.
								'RequesterName = "'.mysqli_real_escape_string($mysqli, $HIT['RequesterName']).'", '.
								'JobName = "'.mysqli_real_escape_string($mysqli, $HIT['JobName']).'", '.
								'Reward = '.mysqli_real_escape_string($mysqli, $HIT['Reward']).', '.
								'UniqueJobHash = "'.mysqli_real_escape_string($mysqli, $uniqueJobHash).'", '.
								'TimeFirstAccepted = '.mysqli_real_escape_string($mysqli, $HIT['MIN(TimeAccepted)']).', '.
								'AllTotalIncome = '.mysqli_real_escape_string($mysqli, $HIT['SUM(Reward)']).', '.
								'AllNumReturns = '.mysqli_real_escape_string($mysqli, $allNumReturns).', '.
								'AllNumSubmitted = '.mysqli_real_escape_string($mysqli, $allNumSubmitted);
				$mysqli->query($UniqueJobInsert) or die('AddingUniqueJob'.mysqli_error($mysqli));
			}
			else {
				if($updateOnlyFromYesterday) {
					$uniqueJobUpdateQuery = 'UPDATE Unique_Jobs SET '.
											'AllTotalIncome = AllTotalIncome '.mysqli_real_escape_string($mysqli, $HIT['SUM(Reward)']).', '.
											'AllNumReturns = AllNumReturns '.mysqli_real_escape_string($mysqli, $allNumReturns).', '.
											'AllNumSubmitted = AllNumSubmitted '.mysqli_real_escape_string($mysqli, $allNumSubmitted).' '.
											'WHERE '.
											'JobName="'.mysqli_real_escape_string($mysqli, $HIT['JobName']).'" AND '.
											'RequesterName="'.mysqli_real_escape_string($mysqli, $HIT['RequesterName']).'" AND '.
											'Reward="'.mysqli_real_escape_string($mysqli, $HIT['Reward']).'"';
					$mysqli->query($uniqueJobUpdateQuery) or die(mysqli_error($mysqli)." query was: $uniqueJobUpdateQuery"); 
				}
				else 
				{
					$uniqueJobUpdateQuery = 'UPDATE Unique_Jobs SET '.
											'AllTotalIncome = '.mysqli_real_escape_string($mysqli, $HIT['SUM(Reward)']).', '.
											'AllNumReturns = '.mysqli_real_escape_string($mysqli, $allNumReturns).', '.
											'AllNumSubmitted = '.mysqli_real_escape_string($mysqli, $allNumSubmitted).' '.
											'WHERE '.
											'JobName="'.mysqli_real_escape_string($mysqli, $HIT['JobName']).'" AND '.
											'RequesterName="'.mysqli_real_escape_string($mysqli, $HIT['RequesterName']).'" AND '.
											'Reward="'.mysqli_real_escape_string($mysqli, $HIT['Reward']).'"';
					$mysqli->query($uniqueJobUpdateQuery) or die(mysqli_error($mysqli)." query was: $uniqueJobUpdateQuery"); 
				}
			}
		}
	}
	
	public function generateCachedStats($updateOnlyFromYesterday) {
		foreach($GLOBALS['allAccountNames'] as $simplifiedUserName){
			//Initialize
			if($updateOnlyFromYesterday) {
				$this->allAccountStats[$simplifiedUserName] = new AccountStats($simplifiedUserName, $this->allAccountStats[$simplifiedUserName]);
			}
			else
				$this->allAccountStats[$simplifiedUserName] = new AccountStats($simplifiedUserName, false);
			
			//generate stats
			$this->allAccountStats[$simplifiedUserName]->generateCachedStats($updateOnlyFromYesterday);
		}
		
		foreach($GLOBALS['allUserNames'] as $userName){
			$this->allUserStats[$userName] = new UserStats($userName);
			$this->allUserStats[$userName]->generateCachedStats($updateOnlyFromYesterday);
		}
	}
	
	public function generateCachedHistoryStats($updateOnlyFromYesterday) {
		foreach($GLOBALS['allAccountNames'] as $simplifiedUserName){
			$this->allAccountStats[$simplifiedUserName]->generateCachedHistoryStats($updateOnlyFromYesterday);
		}
		
		foreach($GLOBALS['allUserNames'] as $userName){
			$this->allUserStats[$userName]->generateCachedHistoryStats($updateOnlyFromYesterday);
		}
		
		$this->equalizeTimeSeriesWithTrailingZeros();
	}
	
	
	
	public function generateStats() {						
			$cachedTextFile = "CachedIncomeStats.txt";
			$handle = fopen($cachedTextFile, "r");
			IncomeStats::$cachedStats = fread($handle, filesize($cachedTextFile));
			IncomeStats::$cachedStats = json_decode(IncomeStats::$cachedStats);
			
			foreach($GLOBALS['allAccountNames'] as $accountName){
				$this->allAccountStats[$accountName] = new AccountStats($accountName, false);
				$this->allAccountStats[$accountName]->generateStats();
			}
			
			foreach($GLOBALS['allUserNames'] as $userName){
				$this->allUserStats[$userName] = new UserStats($userName);
				$this->allUserStats[$userName]->generateStats();
			}
			
			$this->currentJobStats = new CurrentJobStats();
			$this->currentJobStats->generateStats();
			$this->combinedStats = new CombinedStats();
			$this->combinedStats->generateStats($this);
	}
	
	//Adds 0's to the end of any history array that is shorter than another until they are all of equal length
	public function equalizeTimeSeriesWithTrailingZeros() {
	
	}
	
	//addVars = an array containing the variables you want to add up. These include Reward, Returned, Submitted
	//sqlQuery = the query you want to combine with the vars you want to add up
	//if there is only one thing in addVars, it returns an integer, otherwise it returns an associative array of sumResult['Reward','Returned','Submitted']
	public static function accumulateStats($addVars,$sqlQuery) {
		global $mysqli;
		$addReward = false;
		$addReturned = false;
		$addSubmitted = false;
		$sumResult = array('Reward','Returned','Submitted');
		$sumResult['Reward'] = 0;
		$sumResult['Returned'] = 0;
		$sumResult['Submitted'] = 0;
		
		
		
		if(!strpos($sqlQuery, 'TimeFinished')) {
			//echo 'Adding a TimeFinished to Query: '.$sqlQuery.'<br>';
			$sqlQuery = $sqlQuery.' AND TimeFinished > 0';
		}
		
		if(in_array('Returned', $addVars)) {
			$addReturned = true;
			$sqlReturnedQuery = $sqlQuery.' AND Returned = 1';
			$sqlReturnedPointer = $mysqli->query($sqlReturnedQuery) or die(mysqli_error($mysqli).' sqlQuery was: '.$sqlReturnedQuery);
			$sumResult['Returned'] = mysqli_num_rows($sqlReturnedPointer);			
		}
		if(in_array('Submitted', $addVars)) {
			$addSubmitted = true;
			$sqlSubmittedQuery = $sqlQuery.' AND Returned = 0';
			$sqlSubmittedPointer = $mysqli->query($sqlSubmittedQuery) or die(mysqli_error($mysqli).' sqlQuery was: '.$sqlSubmittedQuery);
			$sumResult['Submitted'] = mysqli_num_rows($sqlSubmittedPointer);
		}
		if(in_array('Reward', $addVars)) {
			//echo $sqlQuery.'<br';
			$sqlQuery = $sqlQuery.' AND Returned = 0';
			$addReward = true;
			
			//echo $sqlQuery.'<br>';
			$sqlRewardPointer = $mysqli->query($sqlQuery) or die(mysqli_error($mysqli).' sqlQuery was: '.$sqlQuery);
			//echo 'got here';
			
			//The lazy way, adds each reward, better is to multiply the count by the reward amount for each unique job
			while($hit = mysqli_fetch_array($sqlRewardPointer)) {
				if(!$hit['TimeFinished']) {
					continue;
				}
				if(!$hit['Returned']) {
					$sumResult['Reward'] += $hit['Reward'];
				}
			}
		}
		
		if(count($addVars) > 1) {
			return $sumResult;
		}
		else
		{
			//var_dump($addVars);
			//echo $addReward;
			if($addReward) {
				echo $sumResult['Reward'].'<br>';
				return $sumResult['Reward'];
			}
			if($addReturned)
				return $sumResult['Returned'];
			if($addSubmitted)
				return $sumResult['Submitted'];

			//Still here?
			throw new Exception('malformed addVars parameter');
			
		}				
	}
}

class AccountStats {
	public $accountName;
	public $simplifiedUserName;
	public $incomeToday;
	public $numTodaySubmitted;
	public $numTodayReturns;


	//In the cache, is everything except today. Then we add today's results to the cached variable
	public $incomeThisWeek;
	public $incomeThisMonth;
	public $incomeThisYear;

	public $hitsRemaining;
	
	//In the cache, is everything except today. Then we add today's results to the cached variable
	public $incomeDaily; 
	public $incomeWeekly;
	public $incomeMonthly;	

	//Only initialized if we're doing an update of only yesterday's stats
	public $yesterdayIncome;



	public function __construct($simplifiedUserName, $cachedStats) {
			
			if($cachedStats) {
				$this->incomeDaily = split(',',$cachedStats->incomeDaily);
				$this->incomeweekly = split(',',$cachedStats->incomeWeekly);
				$this->incomeMonthly = split(',',$cachedStats->incomeMonthly);
				$this->incomeThisWeek = $cachedStats->incomeThisWeek;
				$this->incomeThisMonth = $cachedStats->incomeThisMonth;
				$this->incomeThisYear = $cachedStats->incomeThisYear;
				
				$sqlQuery = 'SELECT * FROM HITS WHERE '.
						 'AccountName = "'.$this->accountName.'" AND '.
						 'TimeFinished < '.$GLOBALS['dayBegin'].' AND '.
						 'TimeFinished > '.$GLOBALS['yesterdayBegin'];
				$sum = IncomeStats::accumulateStats(array('Reward'),$sqlQuery);
				$this->yesterdayIncome = $sum;
				
			}
			else {
				$this->incomeDaily = array();
				$this->incomeWeekly = array();
				$this->incomeMonthly = array();				
			}

			$this->simplifiedUserName = $simplifiedUserName;
			switch($this->simplifiedUserName) {
				case '_redacted_': $this->accountName = '_redacted_';
				break;
				case '_redacted_': $this->accountName = '_redacted_';
				break;
				case '_redacted_': $this->accountName = '_redacted_';
				break;
				case '_redacted_': $this->accountName = '_redacted_';
				break;
				case '_redacted_': $this->accountName = '_redacted_';
				break;
				default: $this->accountName = 'UNKNOWN';
			}
	}
	
	public function generateStats() {
		$sqlQuery = 'SELECT * FROM HITS_DAILY WHERE '.
									 'AccountName = "'.$this->accountName.'" AND '.
									 'TimeFinished > '.$GLOBALS['dayBegin'];
		$sums = IncomeStats::accumulateStats(array('Reward','Returned','Submitted'),$sqlQuery);
		$this->numTodayReturns = $sums['Returned'];
		$this->numTodaySubmitted = $sums['Submitted'];
		$this->incomeToday = round($sums['Reward'],2);
		
		$this->hitsRemaining = 3800 - ($this->numTodayReturns+$this->numTodaySubmitted);
	}
	
	public function generateCachedStats($updateOnlyFromYesterday) {
		$timeOfInterest = null;
		if($updateOnlyFromYesterday) {			
			if($GLOBALS['isBeginningOfWeek']) {
				$this->incomeThisWeek = 0;
			}
			else {
				$this->incomeThisWeek += $this->yesterdayIncome;
			}

			if($GLOBALS['isBeginningOfMonth']) {
				$this->incomeThisMonth = 0;
			}
			else {
				$this->incomeThisMonth += $this->yesterdayIncome;
			}
			
			if($GLOBALS['isBeginningOfYear']) {
				$this->incomeThisYear = 0;
			}
			else {
				$this->incomeThisYear += $this->yesterdayIncome;
			}
		}
		else {
			$sqlQuery = 'SELECT * FROM HITS WHERE '.
						 'AccountName = "'.$this->accountName.'" AND '.
						 'TimeFinished < '.$GLOBALS['dayBegin'].' AND '.
						 'TimeFinished > '.$GLOBALS['weekBegin'];
			$sum = IncomeStats::accumulateStats(array('Reward'),$sqlQuery);
			$this->incomeThisWeek = $sum;
			
			
			$sqlQuery2 = 'SELECT * FROM HITS WHERE '.
						 'AccountName = "'.$this->accountName.'" AND '.
						 'TimeFinished < '.$GLOBALS['dayBegin'].' AND '.
						 'TimeFinished > '.$GLOBALS['monthBegin'];
			$sum = IncomeStats::accumulateStats(array('Reward'),$sqlQuery2);
			$this->incomeThisMonth = $sum;
			echo 'TOTALING: '.$this->incomeThisMonth.'<br>';

			$sqlQuery = 'SELECT * FROM HITS WHERE '.
						 'AccountName = "'.$this->accountName.'" AND '.
						 'TimeFinished < '.$GLOBALS['dayBegin'].' AND '.
						 'TimeFinished > '.$GLOBALS['yearBegin'];
			$sum = IncomeStats::accumulateStats(array('Reward'),$sqlQuery);
			$this->incomeThisYear = $sum;
		}
	}
	
	public function generateCachedHistoryStats($updateOnlyFromYesterday) {
		global $mysqli;
			if($updateOnlyFromYesterday) {
				$this->incomeDaily[0] = $this->yesterdayIncome;
				array_push($this->incomeDaily, 0);
				
				if($GLOBALS['isBeginningOfWeek']) {
					$this->incomeWeekly;
					$this->incomeWeekly[1] += $this->yesterdayIncome;
				}
				else {
					$this->incomeWeekly[0] += $this->yesterdayIncome;
				}

				if($GLOBALS['isBeginningOfMonth']) {
					$this->incomeThisMonth = 0;
				}
				else {
					$this->incomeThisMonth += $this->yesterdayIncome;
				}
				
				if($GLOBALS['isBeginningOfYear']) {
					$this->incomeThisYear = 0;
				}
				else {
					$this->incomeThisYear += $this->yesterdayIncome;
				}
				
				$this->incomeDaily = implode(',',$this->incomeDaily);
				$this->incomeWeekly = implode(',',$this->incomeWeekly);
				$this->incomeMonthly = implode(',',$this->incomeMonthly);
			}
			else {
			
			$dayLength = 24*60*60;
			$timezoneAdjust = 60*60*3;
			$unixWeekDayAdjust = 3*$dayLength;//Unix began on a Thursday, but we want to group weekly by Sundays
			$weekLength = $dayLength * 7;
			
			
			$sqlQuery = 'SELECT SUM(Reward), FLOOR((TimeFinished-'.$timezoneAdjust.')/'.$dayLength.') FROM HITS WHERE '.
						 'AccountName = "'.$this->accountName.'" AND '.
						 'TimeFinished < '.$GLOBALS['dayBegin'].' AND '.
						 'Returned = 0 '.
						 'GROUP BY FLOOR((TimeFinished -'.$timezoneAdjust.')/'.$dayLength.') '.
						 'ORDER BY FLOOR((TimeFinished-'.$timezoneAdjust.')/'.$dayLength.') DESC';
			//echo $sqlQuery;	
			array_push($this->incomeDaily,0);//0 b/c we aren't counting today. This slot will be update with today's income later.

			$sqlPointer = $mysqli->query($sqlQuery) or die(mysqli_error($mysqli).' query was: '.$sqlQuery); 
			$lastDayTime = floor(($GLOBALS['dayBegin']-1-$timezoneAdjust)/$dayLength);
			while($rewardsOfDay = mysqli_fetch_array($sqlPointer)){
				//Check that we haven't skipped a day(s), and if we have then simply place a zero in its place(s)
				if($lastDayTime - $rewardsOfDay["FLOOR((TimeFinished-10800)/86400)"] > 1){
					//echo 'here';
					for($i = $lastDayTime; $i - $rewardsOfDay["FLOOR((TimeFinished-10800)/86400)"] > 1; $i--) {
						array_push($this->incomeDaily,0);
					}
				} 
				
				array_push($this->incomeDaily, $rewardsOfDay['SUM(Reward)']);
				//$day = $rewardsOfDay["FLOOR((TimeFinished-10800)/86400)"]*24*60*60 + 60*60*4;// $timezoneAdjust;
				//$day = date('G,j,n,y',$day);//hour, day, month, year
				//echo $day.'<br>';
				//echo $rewardsOfDay['SUM(Reward)'].'<br>';
				$lastDayTime = $rewardsOfDay["FLOOR((TimeFinished-10800)/86400)"];
			}
			$this->incomeDaily = implode(',',$this->incomeDaily);
			
			//INCOME WEEKLY
			$sqlQuery = 'SELECT SUM(Reward), FLOOR((TimeFinished-'.$timezoneAdjust.'+'.$unixWeekDayAdjust.')/'.$weekLength.') FROM HITS WHERE '.
						 'AccountName = "'.$this->accountName.'" AND '.
						 'TimeFinished > '.$GLOBALS['weekBegin'].' AND '.
						 'TimeFinished < '.$GLOBALS['dayBegin'].' AND '.
						 'Returned = 0 '.
						 'GROUP BY FLOOR((TimeFinished-'.$timezoneAdjust.'+'.$unixWeekDayAdjust.')/'.$weekLength.') '.
						 'ORDER BY FLOOR((TimeFinished-'.$timezoneAdjust.'+'.$unixWeekDayAdjust.')/'.$weekLength.') DESC';
			$currentWeek = $mysqli->query($sqlQuery);
			if(mysqli_num_rows($currentWeek)) {
				$currentWeek = mysqli_fetch_array($currentWeek);
				array_push($this->incomeWeekly,$currentWeek['SUM(Reward)']);
			}else{
				array_push($this->incomeWeekly,0);
			}
			
			$sqlQuery = 'SELECT SUM(Reward), FLOOR((TimeFinished-'.$timezoneAdjust.'+'.$unixWeekDayAdjust.')/'.$weekLength.') FROM HITS WHERE '.
						 'AccountName = "'.$this->accountName.'" AND '.
						 'TimeFinished < '.$GLOBALS['weekBegin'].' AND '.
						 'Returned = 0 '.
						 'GROUP BY FLOOR((TimeFinished-'.$timezoneAdjust.'+'.$unixWeekDayAdjust.')/'.$weekLength.') '.
						 'ORDER BY FLOOR((TimeFinished-'.$timezoneAdjust.'+'.$unixWeekDayAdjust.')/'.$weekLength.') DESC';
			//echo $sqlQuery.'<br>';	
			$sqlPointer = $mysqli->query($sqlQuery) or die(mysqli_error($mysqli).' query was: '.$sqlQuery); 
			$lastWeekTime = floor(($GLOBALS['weekBegin']-1-$timezoneAdjust + $unixWeekDayAdjust)/$weekLength);
			//echo $lastWeekTime.'<br>';
			//return;
			while($rewardsOfWeek = mysqli_fetch_array($sqlPointer)){
				//Check that we haven't skipped a week(s), and if we have then simply place a zero in its place(s)
				if($lastWeekTime - $rewardsOfWeek["FLOOR((TimeFinished-10800+259200)/604800)"] > 1){
					for($i = $lastWeekTime; $i - $rewardsOfWeek["FLOOR((TimeFinished-10800+259200)/604800)"] > 1; $i--) {
						array_push($this->incomeWeekly,0);
					}
				} 
				
				array_push($this->incomeWeekly, $rewardsOfWeek['SUM(Reward)']);
				$lastDayTime = $rewardsOfWeek["FLOOR((TimeFinished-10800+259200)/604800)"];
			}
			$this->incomeWeekly = implode(',',$this->incomeWeekly);
			
			//INCOME MONTHLY
			//ASSUMPTION: NO TWO HITS ARE SEPARATED BY AN ENTIRE YEAR WITHOUT ANY HITS INBETWEEN
			//		- Allows an easy test for how far apart months are
			$sqlQuery = 'SELECT SUM(Reward), YEAR(FROM_UNIXTIME(TimeFinished-'.$timezoneAdjust.')), MONTH(FROM_UNIXTIME(TimeFinished-'.$timezoneAdjust.')) '.
						'FROM HITS WHERE '.
						'AccountName = "'.$this->accountName.'" AND '.
						'TimeFinished > '.$GLOBALS['monthBegin'].' AND '.
						'TimeFinished < '.$GLOBALS['dayBegin'].' AND '.
						'Returned = 0 '.
						'GROUP BY YEAR(FROM_UNIXTIME(TimeFinished-'.$timezoneAdjust.')), MONTH(FROM_UNIXTIME(TimeFinished-'.$timezoneAdjust.')) '.
						'ORDER BY YEAR(FROM_UNIXTIME(TimeFinished-'.$timezoneAdjust.')), MONTH(FROM_UNIXTIME(TimeFinished-'.$timezoneAdjust.')) DESC';
			$currentMonth = $mysqli->query($sqlQuery);
			if(mysqli_num_rows($currentMonth)) {
				$currentMonth = mysqli_fetch_array($currentMonth);
				array_push($this->incomeMonthly,$currentMonth['SUM(Reward)']);
			}
			else{
				array_push($this->incomeMonthly,0);				
			}

			$sqlQuery = 'SELECT SUM(Reward), YEAR(FROM_UNIXTIME(TimeFinished-'.$timezoneAdjust.')), MONTH(FROM_UNIXTIME(TimeFinished-'.$timezoneAdjust.')) '.
						'FROM HITS WHERE '.
						'AccountName = "'.$this->accountName.'" AND '.
						'TimeFinished < '.$GLOBALS['monthBegin'].' AND '.
						'Returned = 0 '.
						'GROUP BY YEAR(FROM_UNIXTIME(TimeFinished-'.$timezoneAdjust.')), MONTH(FROM_UNIXTIME(TimeFinished-'.$timezoneAdjust.')) '.
						'ORDER BY YEAR(FROM_UNIXTIME(TimeFinished-'.$timezoneAdjust.')), MONTH(FROM_UNIXTIME(TimeFinished-'.$timezoneAdjust.')) DESC';
			echo $sqlQuery.'<br>';	
			$sqlPointer = $mysqli->query($sqlQuery) or die(mysqli_error($mysqli).' query was: '.$sqlQuery); 
			echo 'HERE CHERRIES'.'<br>';
			$lastMonthTime = date('n',$GLOBALS['monthBegin']-1-$timezoneAdjust)*1;
			echo $lastMonthTime.'<br>';
			//$lastYearTime = date('y',$GLOBALS['yearBegin']+$timezoneAdjust);//We want 2014, not 2013 Dec 31st at 9 p.m.
			//echo $lastYearTime.'<br>';
			//$lastMonthTime = $lastYearTime*1+($lastMonthTime*1-1)/12; //Done so that each month is equally apart from the other mathematically
			//$lastMonthTime = round($lastMonthTime,3);
			//echo $lastMonthTime.'<br>';
			while($rewardsOfMonth = mysqli_fetch_array($sqlPointer)){
				//Check that we haven't skipped a month(s), and if we have then simply place a zero in its place(s)
				$monthTime = $rewardsOfMonth['MONTH(FROM_UNIXTIME(TimeFinished-10800))'];
				$lastMonthDiff;
				if($monthTime > $lastMonthTime) {
					$lastMonthDiff = $lastMonthTime + 12 - $monthTime;
				}
				else {
					$lastMonthDiff = $lastMonthTime - $monthTime;
				}
				
				if($lastMonthDiff > 1){
					for($i = $lastMonthDiff; $i > 1; $i--) {
						array_push($this->incomeMonthly,0);
					}
				} 
				
				array_push($this->incomeMonthly, $rewardsOfMonth['SUM(Reward)']);
				//echo $rewardsOfWeek['SUM(Reward)'].'<br>';
				$lastMonthTime = $monthTime;
			}
			$this->incomeMonthly = implode(',',$this->incomeMonthly);
		}
	}
}

class UserStats {
	public $userName;
	public $simplifiedUserName;
	public $incomeToday;
	public $incomeThisWeek;
	public $incomeThisMonth;
	public $incomeThisYear;
	
	public $thisWeekStartTime;
	
	
	//In the cache, is everything except today. Then we add today's results to the cached variable
	public $incomeDaily; 
	public $incomeWeekly;
	public $incomeMonthly;	
	

	public function __construct($userName) {
			$this->incomeDaily = array();
			$this->incomeWeekly = array();
			$this->incomeMonthly = array();
			$this->userName = $userName;
			$this->simplifiedUserName = $userName;					
	}
	
	public function generateCachedStats() {
		$sqlQuery = 'SELECT * FROM HITS WHERE '.
					 'UserName = "'.$this->userName.'" AND '.
					 'TimeFinished < '.$GLOBALS['dayBegin'].' AND '.
					 'TimeFinished > '.$GLOBALS['weekBegin'];

		$sum = IncomeStats::accumulateStats(array('Reward'),$sqlQuery);
		$this->incomeThisWeek = $sum;
		echo "IncomeThisWeek: ".$this->incomeThisWeek;
		
		$sqlQuery = 'SELECT * FROM HITS WHERE '.
					 'UserName = "'.$this->userName.'" AND '.
					 'TimeFinished < '.$GLOBALS['dayBegin'].' AND '.
					 'TimeFinished > '.$GLOBALS['monthBegin'];
		$sum = IncomeStats::accumulateStats(array('Reward'),$sqlQuery);
		$this->incomeThisMonth = $sum;


		$sqlQuery = 'SELECT * FROM HITS WHERE '.
							 'UserName = "'.$this->userName.'" AND '.
		  					 'TimeFinished < '.$GLOBALS['dayBegin'].' AND '.
							 'TimeFinished > '.$GLOBALS['yearBegin'];		
							 
		$sum = IncomeStats::accumulateStats(array('Reward'),$sqlQuery);
		$this->incomeThisYear = $sum;
	}
	
	public function generateStats() {
		$sqlQuery = 'SELECT * FROM HITS_DAILY WHERE '.
									 'UserName = "'.$this->userName.'" AND '.
									 'TimeFinished > '.$GLOBALS['dayBegin'];
		$sums = IncomeStats::accumulateStats(array('Reward','Returned','Submitted'),$sqlQuery);
		$this->numTodayReturns = $sums['Returned'];
		$this->numTodaySubmitted = $sums['Submitted'];
		//echo $GLOBALS['dayBegin'];
		$this->incomeToday = $sums['Reward'];
		
		$this->incomeThisWeek = $this->incomeToday;
		$this->incomeThisMonth = $this->incomeToday;
		$this->incomeThisYear = $this->incomeToday;
		
		switch($this->simplifiedUserName) {
			case '_redacted_':
				$this->incomeThisWeek += IncomeStats::$cachedStats->allUserStats->_redacted_->incomeThisWeek;
				$this->incomeThisMonth += IncomeStats::$cachedStats->allUserStats->_redacted_->incomeThisMonth;
				$this->incomeThisYear += IncomeStats::$cachedStats->allUserStats->_redacted_->incomeThisYear;
			break;
			case '_redacted_': 
				$this->incomeThisWeek += IncomeStats::$cachedStats->allUserStats->_redacted_->incomeThisWeek;
				$this->incomeThisMonth += IncomeStats::$cachedStats->allUserStats->_redacted_->incomeThisMonth;
				$this->incomeThisYear += IncomeStats::$cachedStats->allUserStats->_redacted_->incomeThisYear;
			break;
			case '_redacted_': 
				$this->incomeThisWeek += IncomeStats::$cachedStats->allUserStats->_redacted_->incomeThisWeek;
				$this->incomeThisMonth += IncomeStats::$cachedStats->allUserStats->_redacted_->incomeThisMonth;
				$this->incomeThisYear += IncomeStats::$cachedStats->allUserStats->_redacted_->incomeThisYear;
			break;
		}
		
		//Round everything
		$this->incomeToday = round($this->incomeToday,2);
		$this->incomeThisWeek = round($this->incomeThisWeek,2);
		$this->incomeThisMonth = round($this->incomeThisMonth,2);
		$this->incomeThisYear = round($this->incomeThisYear,2);
	}
	
	
	public function generateCachedHistoryStats() {
		global $mysqli;
			$dayLength = 24*60*60;
			$timezoneAdjust = 60*60*3;
			$unixWeekDayAdjust = 3*$dayLength;//Unix began on a Thursday, but we want to group weekly by Sundays
			$weekLength = $dayLength * 7;
			
			
			$sqlQuery = 'SELECT SUM(Reward), FLOOR((TimeFinished-'.$timezoneAdjust.')/'.$dayLength.') FROM HITS WHERE '.
						 'UserName = "'.$this->userName.'" AND '.
						 'TimeFinished < '.$GLOBALS['dayBegin'].' AND '.
						 'Returned = 0 '.
						 'GROUP BY FLOOR((TimeFinished -'.$timezoneAdjust.')/'.$dayLength.') '.
						 'ORDER BY FLOOR((TimeFinished-'.$timezoneAdjust.')/'.$dayLength.') DESC';
			//echo $sqlQuery;	
			array_push($this->incomeDaily,0);//0 b/c we aren't counting today. This slot will be update with today's income later.
			$sqlPointer = $mysqli->query($sqlQuery) or die(mysqli_error($mysqli).' query was: ' + $sqlQuery); 
			$lastDayTime = floor(($GLOBALS['dayBegin']-1-$timezoneAdjust)/$dayLength);
			while($rewardsOfDay = mysqli_fetch_array($sqlPointer)){
				//Check that we haven't skipped a day(s), and if we have then simply place a zero in its place(s)
				if($lastDayTime - $rewardsOfDay["FLOOR((TimeFinished-10800)/86400)"] > 1){
					//echo 'here';
					for($i = $lastDayTime; $i - $rewardsOfDay["FLOOR((TimeFinished-10800)/86400)"] > 1; $i--) {
						array_push($this->incomeDaily,0);
					}
				} 
				
				array_push($this->incomeDaily, $rewardsOfDay['SUM(Reward)']);
				//$day = $rewardsOfDay["FLOOR((TimeFinished-10800)/86400)"]*24*60*60 + 60*60*4;// $timezoneAdjust;
				//$day = date('G,j,n,y',$day);//hour, day, month, year
				//echo $day.'<br>';
				//echo $rewardsOfDay['SUM(Reward)'].'<br>';
				$lastDayTime = $rewardsOfDay["FLOOR((TimeFinished-10800)/86400)"];
			}
			$this->incomeDaily = implode(',',$this->incomeDaily);
			
			//INCOME WEEKLY
			$sqlQuery = 'SELECT SUM(Reward), FLOOR((TimeFinished-'.$timezoneAdjust.'+'.$unixWeekDayAdjust.')/'.$weekLength.') FROM HITS WHERE '.
						 'UserName = "'.$this->userName.'" AND '.
						 'TimeFinished > '.$GLOBALS['weekBegin'].' AND '.
						 'TimeFinished < '.$GLOBALS['dayBegin'].' AND '.
						 'Returned = 0 '.
						 'GROUP BY FLOOR((TimeFinished-'.$timezoneAdjust.'+'.$unixWeekDayAdjust.')/'.$weekLength.') '.
						 'ORDER BY FLOOR((TimeFinished-'.$timezoneAdjust.'+'.$unixWeekDayAdjust.')/'.$weekLength.') DESC';
			$currentWeek = $mysqli->query($sqlQuery);
			if(mysqli_num_rows($currentWeek)) {
				$currentWeek = mysqli_fetch_array($currentWeek);
				array_push($this->incomeWeekly,$currentWeek['SUM(Reward)']);
			}
			
			$sqlQuery = 'SELECT SUM(Reward), FLOOR((TimeFinished-'.$timezoneAdjust.'+'.$unixWeekDayAdjust.')/'.$weekLength.') FROM HITS WHERE '.
						 'UserName = "'.$this->userName.'" AND '.
						 'TimeFinished < '.$GLOBALS['weekBegin'].' AND '.
						 'Returned = 0 '.
						 'GROUP BY FLOOR((TimeFinished-'.$timezoneAdjust.'+'.$unixWeekDayAdjust.')/'.$weekLength.') '.
						 'ORDER BY FLOOR((TimeFinished-'.$timezoneAdjust.'+'.$unixWeekDayAdjust.')/'.$weekLength.') DESC';
			//echo $sqlQuery.'<br>';	
			$sqlPointer = $mysqli->query($sqlQuery) or die(mysqli_error($mysqli).' query was: ' + $sqlQuery); 
			$lastWeekTime = floor(($GLOBALS['weekBegin']-1-$timezoneAdjust + $unixWeekDayAdjust)/$weekLength);
			//echo $lastWeekTime.'<br>';
			//return;
			while($rewardsOfWeek = mysqli_fetch_array($sqlPointer)){
				//Check that we haven't skipped a week(s), and if we have then simply place a zero in its place(s)
				if($lastWeekTime - $rewardsOfWeek["FLOOR((TimeFinished-10800+259200)/604800)"] > 1){
					for($i = $lastWeekTime; $i - $rewardsOfWeek["FLOOR((TimeFinished-10800+259200)/604800)"] > 1; $i--) {
						array_push($this->incomeWeekly,0);
					}
				} 
				
				array_push($this->incomeWeekly, $rewardsOfWeek['SUM(Reward)']);
				$lastDayTime = $rewardsOfWeek["FLOOR((TimeFinished-10800+259200)/604800)"];
			}
			$this->incomeWeekly = implode(',',$this->incomeWeekly);
			
			//INCOME MONTHLY
			//ASSUMPTION: NO TWO HITS ARE SEPARATED BY AN ENTIRE YEAR WITHOUT ANY HITS INBETWEEN
			//		- Allows an easy test for how far apart months are
			$sqlQuery = 'SELECT SUM(Reward), YEAR(FROM_UNIXTIME(TimeFinished-'.$timezoneAdjust.')), MONTH(FROM_UNIXTIME(TimeFinished-'.$timezoneAdjust.')) '.
						'FROM HITS WHERE '.
						'UserName = "'.$this->userName.'" AND '.
						'TimeFinished > '.$GLOBALS['monthBegin'].' AND '.
						'TimeFinished < '.$GLOBALS['dayBegin'].' AND '.
						'Returned = 0 '.
						'GROUP BY YEAR(FROM_UNIXTIME(TimeFinished-'.$timezoneAdjust.')), MONTH(FROM_UNIXTIME(TimeFinished-'.$timezoneAdjust.')) '.
						'ORDER BY YEAR(FROM_UNIXTIME(TimeFinished-'.$timezoneAdjust.')), MONTH(FROM_UNIXTIME(TimeFinished-'.$timezoneAdjust.')) DESC';
			$currentMonth = $mysqli->query($sqlQuery);
			if(mysqli_num_rows($currentMonth)) {
				$currentMonth = mysqli_fetch_array($currentMonth);
				array_push($this->incomeMonthly,$currentMonth['SUM(Reward)']);
			}
			else {
				array_push($this->incomeMonthly,0);
			}

			$sqlQuery = 'SELECT SUM(Reward), YEAR(FROM_UNIXTIME(TimeFinished-'.$timezoneAdjust.')), MONTH(FROM_UNIXTIME(TimeFinished-'.$timezoneAdjust.')) '.
						'FROM HITS WHERE '.
						'UserName = "'.$this->userName.'" AND '.
						'TimeFinished < '.$GLOBALS['monthBegin'].' AND '.
						'Returned = 0 '.
						'GROUP BY YEAR(FROM_UNIXTIME(TimeFinished-'.$timezoneAdjust.')), MONTH(FROM_UNIXTIME(TimeFinished-'.$timezoneAdjust.')) '.
						'ORDER BY YEAR(FROM_UNIXTIME(TimeFinished-'.$timezoneAdjust.')), MONTH(FROM_UNIXTIME(TimeFinished-'.$timezoneAdjust.')) DESC';
			echo $sqlQuery.'<br>';	
			$sqlPointer = $mysqli->query($sqlQuery) or die(mysqli_error($mysqli).' query was: ' + $sqlQuery); 
			echo 'HERE CHERRIES'.'<br>';
			$lastMonthTime = date('n',$GLOBALS['monthBegin']-1-$timezoneAdjust)*1;
			echo $lastMonthTime.'<br>';
			//$lastYearTime = date('y',$GLOBALS['yearBegin']+$timezoneAdjust);//We want 2014, not 2013 Dec 31st at 9 p.m.
			//echo $lastYearTime.'<br>';
			//$lastMonthTime = $lastYearTime*1+($lastMonthTime*1-1)/12; //Done so that each month is equally apart from the other mathematically
			//$lastMonthTime = round($lastMonthTime,3);
			//echo $lastMonthTime.'<br>';
			while($rewardsOfMonth = mysqli_fetch_array($sqlPointer)){
				//Check that we haven't skipped a month(s), and if we have then simply place a zero in its place(s)
				$monthTime = $rewardsOfMonth['MONTH(FROM_UNIXTIME(TimeFinished-10800))'];
				$lastMonthDiff;
				if($monthTime > $lastMonthTime) {
					$lastMonthDiff = $lastMonthTime + 12 - $monthTime;
				}
				else {
					$lastMonthDiff = $lastMonthTime - $monthTime;
				}
				
				if($lastMonthDiff > 1){
					for($i = $lastMonthDiff; $i > 1; $i--) {
						array_push($this->incomeMonthly,0);
					}
				} 
				
				array_push($this->incomeMonthly, $rewardsOfMonth['SUM(Reward)']);
				//echo $rewardsOfWeek['SUM(Reward)'].'<br>';
				$lastMonthTime = $monthTime;
			}
			$this->incomeMonthly = implode(',',$this->incomeMonthly);
	}
	
}



class CurrentJobStats {
	public $latestHit;	
	
	public $simplifiedUserName;
	
	public $tenMinutesAgo;
	public $thirtyMinutesAgo;
	
	
	public $num10MinReturns;
	public $num30MinReturns;
	public $numTodayReturns;

	public $num10MinSubmitted;
	public $num30MinSubmitted;
	public $numTodaySubmitted;
	
	public $hourly10Min;
	public $hourly30Min;
	
	public $percentSub10Min;
	public $percentSub30Min;
	public $percentSubToday;
	
	public $income10Min;
	public $income30Min;
	public $incomeToday;


	public $percent10MinPool;
	public $percent30MinPool;
	public $percentStartTimePool;
	
	//The Prefix 'ALL' means that it includes HITs from all computerId's
	public $allIncomeToday;
	public $allTodaySubmitted;
	public $allTodayReturns;
	public $percentSubAllToday;
	
	public $allNumSubmitted;
	public $allNumReturns;
	public $allTotalIncome;
	public $allPercentSubTotal;
	
	public $pool;
	
	public $hasDoneAHitToday;
	
	
	public function __construct() {
		global $mysqli;
		$this->tenMinutesAgo = strtotime('now') - (10*60);
		$this->thirtyMinutesAgo = strtotime('now') - (30*60);
		//echo 'USERNAME '.$_GET['UserName'].'<br>';
		$getLatestQuery = 'SELECT * FROM HITS_DAILY WHERE UserName = "'.$_GET['UserName'].'" AND '.
													'TimeFinished != 0 '.
													'ORDER BY TimeAccepted DESC LIMIT 0, 1';
		//echo $_GET['ComputerId'];
													
		$latest = $mysqli->query($getLatestQuery);
		//echo $getLatestQuery;
		//var_dump(mysqli_fetch_array($latest));
		if(mysqli_num_rows($latest) == 0)
		{
			$this->hasDoneAHitToday = false;
			//$this->initializeToZero();
			$getLatestQuery = 'SELECT * FROM HITS WHERE UserName = "'.$_GET['UserName'].'" AND '.
														'TimeFinished != 0 '.
														'ORDER BY TimeAccepted DESC LIMIT 0, 1';
			$latest = $mysqli->query($getLatestQuery);
		}
		else
			$this->hasDoneAHitToday = true;		

		
		$this->latestHit = mysqli_fetch_array($latest);
				
		switch($this->latestHit['AccountName']) {
				case '_redacted_': $this->simplifiedUserName = '_redacted_';
				break;
				case '_redacted_': $this->simplifiedUserName = '_redacted_';
				break;
				case '_redacted_': $this->simplifiedUserName = '_redacted_';
				break;
				case '_redacted_': $this->simplifiedUserName = '_redacted_';
				break;
				default: $this->simplifiedUserName = 'UNKNOWN';
			}
		$this->pool = new Pool($this->hasDoneAHitToday);
	}
	
	private function initializeToZero() {
			$this->num10MinReturns = 0;
			$this->num30MinReturns = 0;
			$this->numTodayReturns = 0;
			
			$this->num10MinSubmitted = 0;
			$this->num30MinSubmitted = 0;
			$this->numTodaySubmitted = 0;
			
			$this->hourly10Min = 0;
			$this->hourly30Min = 0;
			
			$this->percentSub10Min = 0;
			$this->percentSub30Min = 0;
			$this->percentSubToday = 0;
			
			$this->income10Min = 0;
			$this->income30Min = 0;
			$this->incomeToday = 0;
			
			
			$this->percent10MinPool = 0;
			$this->percent30MinPool = 0;
			$this->percentStartTimePool = 0;
			
			$this->allIncomeToday = 0;
			$this->allTodaySubmitted = 0;
			$this->allTodayReturns = 0;
			$this->percentSubAllToday = 0;
			$this->percentSubAllToday = 0;
			
			
			$this->allNumSubmitted = 0;
			$this->allNumReturns = 0;
			$this->allTotalIncome = 0;
			$this->allTotalIncome = 0;
			$this->allPercentSubTotal = 0;
	}
	
	public function generateStats() {
		global $mysqli;
		//'SELECT Count('
		
		//get the last 10 minutes
		$sqlQuery = 'SELECT * FROM HITS_DAILY WHERE '.
									 'UserName = "'.$_GET['UserName'].'" AND '.
									 'RequesterName = "'.$this->latestHit['RequesterName'].'" AND '.
									 'JobName = "'.$this->latestHit['JobName'].'" AND '.
									 'TimeFinished > '.$this->tenMinutesAgo;
		$sums = IncomeStats::accumulateStats(array('Reward','Returned','Submitted'),$sqlQuery);
		$this->num10MinReturns = $sums['Returned'];
		$this->num10MinSubmitted = $sums['Submitted'];
		$this->income10Min = $sums['Reward'];
		$this->hourly10Min = round(($this->income10Min*6),2);
		if($this->num10MinReturns != 0 && $this->num10MinSubmitted != 0)
			$this->percentSub10Min = round(($this->num10MinSubmitted/($this->num10MinSubmitted+$this->num10MinReturns))*100,0);
		else if($this->num10MinSubmitted > 0)
			$this->percentSub10Min = 100;
		else if($this->num10MinReturns > 0)
			$this->percentSub10Min = 0;
		else
			$this->percentSub10Min = 'NA';
				
		//get the last 30 minutes
		$sqlQuery = 'SELECT * FROM HITS_DAILY WHERE '.
									 'UserName = "'.$_GET['UserName'].'" AND '.
									 'RequesterName = "'.$this->latestHit['RequesterName'].'" AND '.
									 'JobName = "'.$this->latestHit['JobName'].'" AND '.
									 'TimeFinished > '.$this->thirtyMinutesAgo;
		$sums = IncomeStats::accumulateStats(['Reward','Returned','Submitted'],$sqlQuery);
		$this->num30MinReturns = $sums['Returned'];
		$this->num30MinSubmitted = $sums['Submitted'];
		$this->income30Min = $sums['Reward'];
		$this->hourly30Min = round(($this->income30Min*2),2);
		if($this->num30MinReturns != 0 && $this->num30MinSubmitted != 0)
			$this->percentSub30Min = round(($this->num30MinSubmitted/($this->num30MinSubmitted+$this->num30MinReturns))*100,0);
		else if($this->num30MinSubmitted > 0)
			$this->percentSub30Min = 100;
		else if($this->num30MinReturns > 0)
			$this->percentSub30Min = 0;
		else
			$this->percentSub30Min = 'NA';

		
		
		//get all of today
		$sqlQuery = 'SELECT * FROM HITS_DAILY WHERE '.
									 'UserName = "'.$_GET['UserName'].'" AND '.
									 'RequesterName = "'.$this->latestHit['RequesterName'].'" AND '.
									 'JobName = "'.$this->latestHit['JobName'].'" AND '.
									 'TimeFinished > '.$GLOBALS['dayBegin'];
		$sums = IncomeStats::accumulateStats(['Reward','Returned','Submitted'],$sqlQuery);
		$this->numTodayReturns = $sums['Returned'];
		$this->numTodaySubmitted = $sums['Submitted'];
		$this->incomeToday = round($sums['Reward'],2);
		if($this->numTodayReturns != 0 && $this->numTodaySubmitted != 0)
			$this->percentSubToday = round(($this->numTodaySubmitted/($this->numTodaySubmitted+$this->numTodayReturns))*100,0);
		else if($this->numTodaySubmitted > 0)
			$this->percentSubToday = 100;
		else if($this->numTodayReturns > 0)
			$this->percentSubToday = 0;
		else
			$this->percentSubToday = 'NA';
		
		
		
		//get total income/returns/submits of today for everyone, not just this computer, corresponding to this job
		$sqlQuery = 'SELECT * FROM HITS_DAILY WHERE '.
									 'RequesterName = "'.$this->latestHit['RequesterName'].'" AND '.
									 'JobName = "'.$this->latestHit['JobName'].'" AND '.
									 'TimeFinished > '.$GLOBALS['dayBegin'];
		$sums = IncomeStats::accumulateStats(['Reward','Returned','Submitted'],$sqlQuery);
		$this->allIncomeToday = round($sums['Reward'],2);		
		$this->allTodaySubmitted = $sums['Submitted'];		
		$this->allTodayReturns = $sums['Returned'];
		
		if($this->allTodayReturns != 0 && $this->allTodaySubmitted != 0)
			$this->percentSubAllToday = round(($this->allTodaySubmitted/($this->allTodaySubmitted+$this->allTodayReturns))*100,0);
		else if($this->allTodaySubmitted > 0)
			$this->percentSubAllToday = 100;
		else if($this->allTodayReturns > 0)
			$this->percentSubAllToday = 0;
		else
			$this->percentSubAllToday = 'NA';
		
		
		$sqlQuery = 'SELECT * FROM HITS WHERE '.
									 'RequesterName = "'.$this->latestHit['RequesterName'].'" AND '.
									 'TimeFinished != 0 AND '.
									 'JobName = "'.$this->latestHit['JobName'].'"';
		$sums = IncomeStats::accumulateStats(['Reward','Submitted','Returned'],$sqlQuery);
		
		$allSumsQuery = 'SELECT AllTotalIncome, AllNumReturns, AllNumSubmitted FROM Unique_Jobs WHERE '.
							'JobName = "'.mysqli_real_escape_string($mysqli, $this->latestHit['JobName']).'" AND '.
							'RequesterName = "'.mysqli_real_escape_string($mysqli, $this->latestHit['RequesterName']).'" AND '.
							'Reward = '.mysqli_real_escape_string($mysqli, $this->latestHit['Reward']).' '.
							'LIMIT 0,1';
		$sqlPointer = $mysqli->query($allSumsQuery) or die (mysqli_error($mysqli)." query was: $allSumsQuery");

		if(mysqli_num_rows($sqlPointer) != 0) {
			$allSums = mysqli_fetch_array($sqlPointer);		
			$this->allTotalIncome = $allSums['AllTotalIncome'];
			$this->allNumReturns = $allSums['AllNumReturns'];
			$this->allNumSubmitted = $allSums['AllNumSubmitted'];
		}else {
			$this->allTotalIncome = 0;
			$this->allNumReturns = 0;
			$this->allNumSubmitted = 0;		
		}

		$this->allTotalIncome += $this->allIncomeToday;
		$this->allNumReturns += $this->allTodayReturns;
		$this->allNumSubmitted += $this->allTodaySubmitted;
		
		if($this->allNumReturns != 0 && $this->allNumSubmitted != 0)
			$this->allPercentSubTotal = round(($this->allNumSubmitted/($this->allNumSubmitted+$this->allNumReturns))*100,0);
		else if($this->allNumSubmitted > 0)
			$this->allPercentSubTotal = 100;
		else if($this->allNumReturns > 0)
			$this->allPercentSubTotal = 0;
		else
			$this->allPercentSubTotal = 'NA';
			
		$this->generatePoolStats();
	}
	
	private function generatePoolStats() {
		global $mysqli;
		mysqli_select_db($mysqli, 'AMT_Batch') or die('err'.mysqli_error($mysqli));
		
		$reward = $this->latestHit['Reward'];
		// Remove trailing zero, e.g. 0.10 is 0.1.
		// Updated March 2018.
		$reward = preg_replace('/0+$/', '', $reward);
		//var_dump($reward);
		//echo 'reward: '.$reward.'<br>';
		
		//~ var_dump($this->latestHit);
		
		//~ $this->latestHit['JobName'] = str_replace('&', 'funny_bone', $this->latestHit['JobName']); // funny_boneamp;amp; 
		//~ echo $this->latestHit['JobName'];
		//~ $jn_space = mysqli_real_escape_string(preg_replace('/[\r\n\t\s]+/', ' ', $this->latestHit['JobName']));
		
		//Get the id from jobs_history_index which becomes id_jobs_history_index in history_weekly
		$this->latestHit['RequesterName'] = htmlToMturkEntity($this->latestHit['RequesterName']);
		$this->latestHit['JobName'] = htmlToMturkEntity($this->latestHit['JobName']);
		$query = 'SELECT * FROM jobs_history_index WHERE '.
				 'title="'.mysqli_real_escape_string($mysqli, $this->latestHit['JobName']).'" AND '.
				 'requester_name="'.mysqli_real_escape_string($mysqli, $this->latestHit['RequesterName']).'" AND '.
				 'reward="'.mysqli_real_escape_string($mysqli, $reward).'" AND '.
				 'id_duplicate_jobs = 0 Order BY id Desc';
		$sqlPointer = $mysqli->query($query) or die(mysqli_error($mysqli).' query was: '.$query);
		if(mysqli_num_rows($sqlPointer) == 0) {
			echo 'Error generating pool stats.
			Maybe it is an error with the jobname (check htmlToMturkEntity)
			JobName:'.$this->latestHit['JobName'] .'
			RequesterName:'.$this->latestHit['RequesterName'] . '
			Error: cannot find currentJob in jobs_history_index, has enough time passed for there to be a scrape? Query is: 
			' . $query;
		}
		//var_dump($id_jobs_history_index);
		
		$candHistoryHts = array();
		while($historyHit = mysqli_fetch_array($sqlPointer)) {
			$id_jobs_history_index = $historyHit['id'];
			//echo '$candidate id_jobs_history_index: '.$id_jobs_history_index.'<br>';
			array_push($candHistoryHts, $historyHit['id']);
		}

		$query = 'SELECT * FROM history_weekly WHERE '.
				 'id_jobs_history_index in ('.mysqli_real_escape_string($mysqli, implode(',',$candHistoryHts)).')'.
				 ' ORDER BY date_run_time DESC LIMIT 0,1';
		$sqlPointer = $mysqli->query($query) or die ($query . ' : ' . mysqli_error($mysqli));
		$latestHistoryHit = mysqli_fetch_array($sqlPointer);
		$id_jobs_history_index = $latestHistoryHit['id_jobs_history_index'];
		
		//echo 'latest id_jobs_history_index: '.$id_jobs_history_index.'<br>';
		
		$query = 'SELECT * FROM history_weekly WHERE '.
				 'id_jobs_history_index='.mysqli_real_escape_string($mysqli, $id_jobs_history_index).' ORDER BY date_run_time DESC';
			
		$sqlPointer2 = $mysqli->query($query);
		
		
		$i = 1; 
		$lastLoopHit = null;
		$lastUpdateIsHighestCount = false;
		$gotETAStats = null;
		
		//echo 'Now: '.strtotime('now').'<br>';


		if($sqlPointer2) {
			while($hit = mysqli_fetch_array($sqlPointer2)) {
				//var_dump($hit);
				//echo '<br>';
				//echo '$hit[date_run_time]: '.$hit['date_run_time']."<br>";
				if($i ==1) { //&& ($hit['date_run_time'] > strtotime('now') - 55*60) //Has the last update been in the last 55 minutes?
					$this->pool->endTime = $hit['date_run_time'];
					$this->pool->endCount = $hit['HITs_available'];
					$this->pool->currentCount = $this->pool->endCount;
				}
				
				if($i != 1 && !$gotETAStats) {
					if(!$this->pool->etaStartCount) {
						$this->pool->etaStartCount = $hit['HITs_available'];
						$this->pool->etaStartTime = $hit['date_run_time'];
					}
					else if($this->pool->etaStartCount <= $hit['HITs_available']) {
						$this->pool->etaStartCount = $hit['HITs_available'];
						$this->pool->etaStartTime = $hit['date_run_time'];
					}
				}
				
				if($i != 1 && 
						(($lastLoopHit['date_run_time'] - $hit['date_run_time']) > (25*60) || //Was there a 25 minute break or more?
						($this->pool->endTime - $hit['date_run_time']) > (65*60)) && !$gotETAStats)//Are we an hour and 5 minutes beyond when the job was last recorded?
						 
				{
					$gotETAStats = true;
				}
				
				if($hit['HITs_available'] > $this->pool->apexCount) {
					$this->pool->apexCount = $hit['HITs_available'];
				}
				
				if($i != 1 && ($lastLoopHit['date_run_time'] - $hit['date_run_time']) > (25*60)) {
					$this->pool->firstRunTime = $hit['date_run_time'];
					break;
				}
				
				$i++;
				$lastLoopHit = $hit;
			}
			
			if(!$this->pool->firstRunTime) {
				$this->pool->firstRunTime = $lastLoopHit['date_run_time'];
			}
			
			$this->pool->timeSinceLastUpdate = (strtotime('now') - $this->pool->endTime)/60;
			
			if($this->pool->endCount >= $this->pool->etaStartCount) {
				$lastUpdateIsHighestCount = true;
			}
			else {
				$this->pool->etaTimeDiff = $this->pool->endTime - $this->pool->etaStartTime;
				$this->pool->etaTimeDiffInHours = $this->pool->etaTimeDiff/60/60;
				$this->pool->etaTimeDiffInMins = ($this->pool->etaTimeDiffInHours-floor($this->pool->etaTimeDiffInHours))*60;
				$this->pool->etaTimeDiffInSecs = ($this->pool->etaTimeDiffInMins-floor($this->pool->etaTimeDiffInMins))*60;
				
				$this->pool->etaTimeDiffInHours = floor($this->pool->etaTimeDiffInHours);
				$this->pool->etaTimeDiffInMins = floor($this->pool->etaTimeDiffInMins);
				$this->pool->etaTimeDiffInSecs = floor($this->pool->etaTimeDiffInSecs);
				
				
				$this->pool->etaCountDiff = $this->pool->etaStartCount - $this->pool->endCount;
				$this->pool->dropRate = ($this->pool->etaCountDiff/$this->pool->etaTimeDiff);
				$this->pool->dropRatePerHour = ($this->pool->etaCountDiff/$this->pool->etaTimeDiff)*60*60;
				$this->pool->activeETA = $this->pool->endCount/$this->pool->dropRatePerHour;
			}
			
			//echo "endTime: ".$this->pool->endTime."<br>";
			//echo "endCount: ".$this->pool->endCount."<br>";
			//echo "etaStartTime: ".$this->pool->etaStartTime."<br>";
			//echo "etaStartCount: ".$this->pool->etaStartCount."<br>";
			//echo "etaTimeDiff: ".$this->pool->etaTimeDiff."<br>";
			//echo "etaTimeDiffInHours: ".$this->pool->etaTimeDiffInHours."<br>";
			//echo "etaCountDiff: ".$this->pool->etaCountDiff."<br>";
			//echo "dropRate: ".$this->pool->dropRate."<br>";
			//echo "dropRatePerHour: ".$this->pool->dropRatePerHour."<br>";
			//echo "activeETA: ".$this->pool->activeETA."<br>";
			//echo "apexCount: ".$this->pool->apexCount."<br>";
			
			if($this->pool->endTime && $this->pool->etaStartTime && !$lastUpdateIsHighestCount) {
				mysqli_select_db($mysqli, 'IncomeRecords') or die('err'.mysqli_error($mysqli));	
				//Get how much we've done in the same amount of time
				$query = 'SELECT * FROM HITS WHERE '.
						 'RequesterName = "'.$this->latestHit['RequesterName'].'" AND '.
						 'JobName = "'.$this->latestHit['JobName'].'" AND '.
						 'Returned = 0 AND '.
						 'TimeFinished < '.mysqli_real_escape_string($mysqli, $this->pool->endTime).' AND '.
						 'TimeFinished > '.mysqli_real_escape_string($mysqli, $this->pool->etaStartTime) or die(mysqli_error($mysqli));
				$sqlPointer = $mysqli->query($query);
				$this->pool->ourETACount = mysqli_num_rows($sqlPointer);
				
				//Controversial
				if($this->pool->ourETACount > $this->pool->etaCountDiff) {
					$this->pool->ourETACount = 0;
				}
				
				
				$this->pool->passiveDropRatePerHour = ($this->pool->etaCountDiff-$this->pool->ourETACount)/$this->pool->etaTimeDiff*60*60;
				if($this->pool->passiveDropRatePerHour == 0) {
					$this->pool->passiveETA = $this->pool->activeETA;
				}
				else
					$this->pool->passiveETA = $this->pool->endCount/$this->pool->passiveDropRatePerHour;
				
				//echo "ourETACount: ".$this->pool->ourETACount."<br>";
				//echo "passiveETA: ".$this->pool->passiveETA."<br>";
				
				//Now get how much of the pool we've acquired
				$query = 'SELECT * FROM HITS WHERE '.
						 'RequesterName = "'.$this->latestHit['RequesterName'].'" AND '.
						 'JobName = "'.$this->latestHit['JobName'].'" AND '.
						 'Returned = 0 AND '.
						 'TimeFinished < '.mysqli_real_escape_string($mysqli, $this->pool->endTime).' AND '.
						 'TimeFinished > '.mysqli_real_escape_string($mysqli, $this->pool->firstRunTime);
				//echo $query;
				$sqlPointer = $mysqli->query($query) or die(mysqli_error($mysqli));
				$this->pool->ourTotalCount = mysqli_num_rows($sqlPointer);
				$this->pool->percentPool = $this->pool->ourTotalCount/$this->pool->apexCount*100;
				$this->pool->totalPoolMoney = $this->latestHit['Reward'] * $this->pool->apexCount;
				$this->pool->leftOverPoolMoney = $this->latestHit['Reward'] * $this->pool->currentCount;
				
				$this->pool->allMturkDoneSoFar = $this->pool->apexCount-$this->pool->currentCount;
				
				$this->pool->percentSoFarPool = $this->pool->ourTotalCount/$this->pool->allMturkDoneSoFar*100;
				
				//echo "percentPool: ".$this->pool->percentPool."<br>";
				
			}
			else if($lastUpdateIsHighestCount) {
				//Either the pool is still growing, or it has stagnated
				if($this->pool->apexCount - $this->pool->endCount  > 25) {
					$this->pool->stagnantPool = false;
					$this->pool->growingPool = true;
				}
				else {
					$this->pool->stagnantPool = true;
					$this->pool->growingPool = false;
				}
				
				//echo "stagnantPool:".$this->pool->stagnantPool."<br>";
				//echo "growingPool: ".$this->pool->growingPool."<br>";
			}
			else {
				//Not enough data to create a pool
			}
			
		}
		else
		{
			$this->pool->errorPool = true;
		}
		
		//Round whatever variables need rounding
		$this->pool->timeSinceLastUpdate = round($this->pool->timeSinceLastUpdate,1);
		$this->pool->etaTimeDiffInHours = round($this->pool->etaTimeDiffInHours,2);
		$this->pool->activeETA = round($this->pool->activeETA,2);
		$this->pool->passiveETA = round($this->pool->passiveETA,2);
		$this->pool->percentPool = round($this->pool->percentPool,2);
		$this->pool->percentSoFarPool = round($this->pool->percentSoFarPool,2);
		$this->pool->totalPoolMoney = round($this->pool->totalPoolMoney,2);
		$this->pool->leftOverPoolMoney = round($this->pool->leftOverPoolMoney,2);
		$this->pool->passiveDropRatePerHour = round($this->pool->passiveDropRatePerHour);
		$this->pool->dropRatePerHour = round($this->pool->dropRatePerHour);
		
		//Return to the default database
		mysqli_select_db($mysqli, 'IncomeRecords') or die('err'.mysqli_error($mysqli));
	}
	
}

class CombinedStats {
	
	public $allIncomeToday;
	public $allIncomeThisWeek;
	public $allIncomeThisMonth;
	public $allIncomeThisYear;
	
	public function __construct() {
		$this->allIncomeToday = 0;
		$this->allIncomeThisWeek = 0;
		$this->allIncomeThisMonth = 0;
		$this->allIncomeThisYear = 0;
	}
	
	public function generateStats($iStats) {
		foreach($GLOBALS['allUserNames'] as $userName){
				$this->allIncomeToday += $iStats->allUserStats[$userName]->incomeToday;
				$this->allIncomeThisWeek += $iStats->allUserStats[$userName]->incomeThisWeek;
				$this->allIncomeThisMonth += $iStats->allUserStats[$userName]->incomeThisMonth;
				$this->allIncomeThisYear += $iStats->allUserStats[$userName]->incomeThisYear;
		}
	}	
}

class Pool {
	public $endTime;
	public $endCount;
	public $etaStartTime;
	public $etaStartCount;
	public $firstRunTime;
	public $etaTimeDiff;
	public $etaCountDiff;
	public $dropRate;
	public $passiveETA;
	public $apexCount;
	public $currentCount;
	public $dropRatePerHour;
	public $passiveDropRatePerHour;
	public $activeETA;
	public $stagnantPool;
	public $growingPool;
	public $etaTimeDiffInHours;
	public $timeSinceLastUpdate;
	public $percentPool;
	public $errorPool;
	public $percentSoFarPool;
	public $totalPoolMoney;
	public $leftOverPoolMoney;
	public $etaTimeDiffInMins;
	public $etaTimeDiffInSecs;	
	public $allMturkDoneSoFar;
	
	public function __construct($hasDoneAHitToday) {
		$this->endTime = !$hasDoneAHitToday ? 0 : null;
		$this->endCount = !$hasDoneAHitToday ? 0 :  null;
		$this->etaStartTime = !$hasDoneAHitToday ? 0 : null;
		$this->etaStartCount = !$hasDoneAHitToday ? 0 : null;
		$this->etaTimeDiff = !$hasDoneAHitToday ? 0 : null;
		$this->etaCountDiff = !$hasDoneAHitToday ? 0 : null;
		$this->dropRate = !$hasDoneAHitToday ? 0 : null;
		$this->passiveETA = !$hasDoneAHitToday ? 0 : null;
		$this->apexCount = !$hasDoneAHitToday ? 0 : null;
		$this->dropRatePerHour = !$hasDoneAHitToday ? 0 : null;
		$this->activeETA = !$hasDoneAHitToday ? 0 : null;
		$this->stagnantPool = !$hasDoneAHitToday ? 0 : null;
		$this->growingPool = !$hasDoneAHitToday ? 0 : null;
		$this->firstRunTime = !$hasDoneAHitToday ? 0 : null;
		$this->etaTimeDiffInHours = !$hasDoneAHitToday ? 0 : null;
		$this->timeSinceLastUpdate = !$hasDoneAHitToday ? 0 : null;
		$this->percentPool = !$hasDoneAHitToday ? 0 : null;
		$this->errorPool = !$hasDoneAHitToday ? 0 : null;
		$this->currentCount = !$hasDoneAHitToday ? 0 : null;
		$this->percentSoFarPool = !$hasDoneAHitToday ? 0 : null;
		$this->totalPoolMoney = !$hasDoneAHitToday ? 0 : null;
		$this->leftOverPoolMoney = !$hasDoneAHitToday ? 0 : null;
		$this->etaTimeDiffInMins = !$hasDoneAHitToday ? 0 :  null;
		$this->etaTimeDiffInSecs = !$hasDoneAHitToday ? 0 :  null;
		$this->allMturkDoneSoFar = !$hasDoneAHitToday ? 0 : null;
		$this->passiveDropRatePerHour = !$hasDoneAHitToday ? 0 : null;
		
	}
}

//Writes income record stats into CachedIncomeStats.txt
class CachedStats {
	public $allAccountStats;
	public $allUserStats;
	
	
	public function __construct() {

	}
	
	public function writeStats($iStats) {
		$this->allAccountStats = $iStats->allAccountStats;
		$this->allUserStats = $iStats->allUserStats;
		
		
		$filename = "CachedIncomeStats.txt";
		$handle = fopen($filename, "w");
		echo json_encode($iStats->allAccountStats);
		fwrite($handle, json_encode($this));
		//fwrite($handle, json_encode($iStats->allAccountStats));
		fclose($handle);		
	}
	
	public function readStats() {
		$filename = "CachedIncomeStats.txt";
		$handle = fopen($filename, "r");
		$cachedStats = fread($handle);
		fclose($handle);
		$cachedStats = json_decode($cachedStats);
		$this->allAccountStats = (array) $cachedStats->allAccountStats;
		$this->allUserStats = (array) $cachedStats->allUserStats;
	}
}



//{"AllIncomeStats":{"AccountStats":{"Week":{"_redacted_":123123,"_redacted_":123123,"_redacted_":123123,"_redacted_":123123},"Month":{"_redacted_":123123,"_redacted_":123123,"_redacted_":123123,"_redacted_":123123},"Year":{"_redacted_":123123,"_redacted_":123123,"_redacted_":123123,"_redacted_":123123}},"UserStats":{"Week":{"_redacted_":123123,"_redacted_":123123,"_redacted_":123123},"Month":{"_redacted_":123123,"_redacted_":123123,"_redacted_":123123},"Year":{"_redacted_":123123,"_redacted_":123123,"_redacted_":123123}}},"LastUpdate":123123}





//~ function connect() {
	//~ mysql_connect('127.0.0.1', 'root', '_redacted_') or die('err'.mysqli_error($mysqli));
	//mysql_connect('localhost', 'root', '_redacted_') or die('err'.mysqli_error($mysqli));
	//~ mysqli_select_db($mysqli, 'IncomeRecords') or die('err'.mysqli_error($mysqli));
//~ }

?>
