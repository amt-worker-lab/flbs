<?php
# error reporting
error_reporting(E_ALL ^ E_NOTICE); ini_set('display_errors', 1); ini_set('display_startup_errors', 1);

//echo "\nArrived in inputOutputRecords.php\n";

//error_reporting(E_ALL ^ E_NOTICE); ini_set('display_errors', 1); ini_set('display_startup_errors', 1);

//connect to database
// Make a global mysql variable.
$mysqli = null;
connect();

//--------------------------------------------------------------------//
// DEBUG
//------//
//~ $_POST['match'] = '{"recordName":"kronopin-findurl,2018","matchString":"Teas Tea|Tea & Coffee Rtd|Tea Rtd","outputs":[{"inputIdentifier":"Id(web_url)","value":"https://teastea.com"}],"count":1,"lastTimeOfSubmit":1519510559124}';
//--------------------------------------------------------------------//

class findObject {
	public $recordName;
	public $matchString;
	public $timeCutOff;
	public $returnAllMatches;
	
	function __construct($rec, $match, $time, $retAll)
	{
		$this->recordName = $rec;
		//echo "\nRecord Name: ".$this->recordName;
		$this->matchString = $match;
		//echo "\nMatch String: ".$this->matchString;
		$this->timeCutOff = $time;
		
		$this->returnAllMatches = $retAll;
	}
	
	public function getMatch()
	{
		global $mysqli;
		if($this->returnAllMatches) {
			return $this->getAllMatches();
		}
		else {
			//echo "isInsideDatabase (getter version)";
			$rslt = $mysqli->query('SELECT * FROM InputOutputMatches
								WHERE matchString="'.mysqli_real_escape_string($mysqli, $this->matchString).'" AND
									  recordName="'.mysqli_real_escape_string($mysqli, $this->recordName).'"');
								//Todo: implement timeCutOff
								
			if(mysqli_num_rows($rslt) == 0)
			{
				echo "No Match";
				return false;
			}
			else
			{
				$tempCount = 0;
				$mostCommon;
				while ($row = mysqli_fetch_array($rslt)) {
					//echo "Iterating Through Found Stuff!";
					if($row["count"] > $tempCount)
					{
						$tempCount = $row["count"];
						$mostCommon = $row;
					}					
				}
				
				$match = new InputOutputMatch(
					$mostCommon["recordName"],
					$mostCommon["matchString"],
					$mostCommon["outputs"],
					$mostCommon["lastTimeOfSubmit"],
					$mostCommon["count"]
				);
							//Todo: Implement ratio
							
				return json_encode($match);
			}
		}
	}
	
	public function getAllMatches()
	{
		global $mysqli;
		//echo "isInsideDatabase (getter version) get all";
		$rslt = $mysqli->query('SELECT * FROM InputOutputMatches
							WHERE matchString="'.mysqli_real_escape_string($mysqli,$this->matchString).'" AND
								  recordName="'.mysqli_real_escape_string($mysqli,$this->recordName).'"');
							//Todo: implement timeCutOff
							
		if(mysqli_num_rows($rslt) == 0)
		{
			echo "No Match";
			return false;
		}
		else
		{
			$tempCount = 0;
			$mostCommon;
			$total = 0;
			$allMatches = array();
			while ($row = mysqli_fetch_array($rslt)) {
				$match = new InputOutputMatch(
				$row["recordName"],
				$row["matchString"],
				$row["outputs"],
				$row["lastTimeOfSubmit"],
				$row["count"]
				);
				array_push($allMatches, $match);				
			}			
			return json_encode($allMatches);
		}
	}
}

class InputOutputMatch {
	public $recordName;
	public $matchString;
	public $outputs;
	public $lastTimeOfSubmit;	
	public $count;
	public $isMostCommon;
	
	public function __construct($rName, $mString, $oPuts, $time, $count)
	{
		$this->recordName = $rName;
		//echo "\nConstruct, Match->recordName: ".$this->recordName;
		$this->matchString = $mString;
		//echo "\nConstruct, Match->matchString: ".$this->matchString;
		$this->outputs = $oPuts;
		//echo "\nConstruct, Match->outputs: ".$this->outputs;
		$this->lastTimeOfSubmit = $time;
		//echo "\nConstruct, Match->lastTimeOfSubmit: ".$this->lastTimeOfSubmit;
		if(!$count)
			$this->count = 1;
		else
			$this->count = $count;		
	}
	
	public function addToDatabase()
	{
		global $mysqli;
		if($rslt = $this->alreadyInsideDatabase())
		{
			$this->updateMatch($rslt);
		}
		else
		{
			$query = 'INSERT INTO InputOutputMatches
						SET
						recordName="'.mysqli_real_escape_string($mysqli,$this->recordName).'",
						matchString="'.mysqli_real_escape_string($mysqli,$this->matchString).'",
						outputs="'.mysqli_real_escape_string($mysqli,$this->outputs).'",
						lastTimeOfSubmit="'.mysqli_real_escape_string($mysqli,$this->lastTimeOfSubmit).'",
						count="'.mysqli_real_escape_string($mysqli,$this->count).'"';
			echo $query;
			$rslt = $mysqli->query($query) or die("\naddToDatabaseError: ".$mysqli->error());
		}	
	}
	
	//returns a mysql query
	private function alreadyInsideDatabase()
	{
		global $mysqli;
		$query = 'SELECT * FROM InputOutputMatches
			WHERE recordName="'.mysqli_real_escape_string($mysqli,$this->recordName).'" AND
				matchString="'.mysqli_real_escape_string($mysqli,$this->matchString).'" AND
				outputs="'.mysqli_real_escape_string($mysqli,$this->outputs).'"';
		echo $query;
		$rslt = $mysqli->query($query);
		//echo "\nnum rows: ".mysql_num_rows($rslt);
		if(mysqli_num_rows($rslt) == 0)
		{
			return false;
		}
		else
		{
			return $rslt;
		}
	}
	
	private function updateMatch($rslt)
	{
		global $mysqli;
		$mysqli->query('
			UPDATE InputOutputMatches
			SET
			count=count+1,
			lastTimeOfSubmit='.mysqli_real_escape_string($mysqli,$this->lastTimeOfSubmit).'
			WHERE
			recordName="'.mysqli_real_escape_string($mysqli,$this->recordName).'" AND
			matchString="'.mysqli_real_escape_string($mysqli,$this->matchString).'" AND
			outputs="'.mysqli_real_escape_string($mysqli,$this->outputs).'"') or die("\nincrementCount Error: ".$mysqli->error());
	}
}

if(isset($_POST) && isset($_POST['findObject']))
{
	$find = json_decode($_POST['findObject']);
  $returnAllMatches = isset($find->returnAllMatches) ? $find->returnAllMatches : false;
	$find = new FindObject($find->recordName, $find->matchString, $find->timeCutOff, $returnAllMatches);
	$match = $find->getMatch();
	echo $match;
}

if(isset($_POST) && isset($_POST['match']))
{
	$match = json_decode($_POST['match']);
	$match = new InputOutputMatch($match->recordName,
		$match->matchString,
		json_encode($match->outputs),
		$match->lastTimeOfSubmit,
		$match->count
	);
	$match->addToDatabase();
}

//echo "test";


function connect() {
	//~ mysql_connect('127.0.0.1', 'root', '_redacted_') or die('err'.mysql_error());
	//~ mysql_select_db('AMT_Records') or die('err'.mysql_error());
	// Feb2018:
	// 		Update to utilize mysqli.
	global $mysqli;
	$mysqli = new mysqli('localhost',
		'root',
		'_redacted_',
		'AMT_Records');
	// Test connection
	if (mysqli_connect_errno()) {
		printf("Connect failed: %s\n", mysqli_connect_error());
		exit();
	}
	//~ return $m;
}

?>

