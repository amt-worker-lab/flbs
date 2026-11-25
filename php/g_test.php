<?php
/*/
 *
 * Copyright 2013 David Shumway
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * Contact: _redacted_.com
 * 
/*/


if (
	!isset($_GET['last_update']) &&
	!isset($_GET['jobs']) &&
	!isset($_GET['hits_available']) &&
	!isset($_GET['show_all']) &&
	!isset($_GET['debug'])
) {
	exit;
}

error_reporting(E_ALL);

$debug_pos = 0;

/*$mysql_host = "mysql11.000webhost.com"; // 000webhost
$mysql_database = "a3450205_turk";
$mysql_user = "a3450205_user";
$mysql_password = "_REDACTED_";*/
/*$mysql_host = "localhost"; // host1free vps
$mysql_database = "a3450205_turk";
$mysql_user = "a3450205_user00";
$mysql_password = "_REDACTED_";*/
/*
$mysql_host = "localhost"; // host1free
$mysql_database = "a20_turk";
$mysql_user = "a20_turk";
$mysql_password = "_REDACTED_";*/
/*
$mysql_host = "localhost"; // noads.biz
$mysql_database = "383752";
$mysql_user = "383752";
$mysql_password = "_REDACTED_";
//*/

//~ $mysql_host = "localhost";
//~ $mysql_database = "_redacted__AMT";
//~ $mysql_user = "_redacted__user";
//~ $mysql_password = "_REDACTED_";

//*
set_time_limit(110); // 1:50
$mysql_host = "localhost"; // localhost
$mysql_database = "AMT_Batch";
$mysql_user = "root";
$mysql_password = "_REDACTED_";
//*/
//echo 'a';exit;

mysql_connect($mysql_host,$mysql_user,$mysql_password) or die(mysql_error());
mysql_select_db($mysql_database) or die(err(mysql_error()));
/***
 * UTF-8
**/
mysql_query("Set names 'utf8'");

$debug_pos = 1;

header('Access-Control-Allow-Origin: *');

/**
 * last_update
 * 
 * 
 */
if (
	isset($_GET['last_update'])
) {
	$query = '
		SELECT
			jobs as j,
			HITs_available as h
		FROM `last_update`
		WHERE
			id = 1
		LIMIT 1';
	$res = mysql_try($query);
	if (mysql_num_rows($res) > 0) {
		$rows = array();
		while($row = mysql_fetch_assoc($res)) {
			$rows[] = $row;
		}
		echo json_encode($rows);
	} else {
		echo '[]';
	}
}
/**
 * jobs
 * 
 * Aug5 2014
 * Add hash_id as d
 */
else if ( // Get new jobs
	isset($_GET['jobs'])
) {
	
	$_GET['jobs'] = preg_replace('/\D/', '', $_GET['jobs']);
	$_GET['jobs'] = (strlen($_GET['jobs']) > 0) ? $_GET['jobs'] : '0';
	
	// id_jobs_history_index references parent
	// EITHER ONE ... parent.id as i, OR jobs.id as i ???
	// ex:
	//~ select parent.name, child.name
	//~ from your_table child
	//~ left join your_table parent on child.parent = parent.id
	//~ where child.id = 3
	
	$query = '
		SELECT
			jobs.id as i,
			jobs.time_posted as p,
			jobs.time_last_update as u,
			jobs.HITs_available as h,
			jobs.on_page_number as o,
			
			parent.title as t,
			parent.requester_id as r,
			parent.requester_name as n,
			parent.hash_id as hi
		FROM `jobs` jobs
		LEFT JOIN `jobs_history_index` parent ON jobs.id_jobs_history_index = parent.id
		WHERE
			jobs.time_posted > '.$_GET['jobs'].'
		ORDER BY
			jobs.time_posted
		ASC
	';
	$res = mysql_try($query);
	if (mysql_num_rows($res) > 0) {
		$rows = array();
		while($row = mysql_fetch_assoc($res)) {
			$rows[] = $row;
		}
		echo json_encode($rows);
	}
	if (mysql_num_rows($res) == 0) {
		echo '[]';
		exit;
	}
}
/**
 * hits_available
 * 
 * 
 */
else if ( // Update all hits available
	isset($_GET['hits_available'])
) {
	$query = '
		SELECT 
			id as i,
			HITs_available as h
		FROM `jobs`
	';
	$res = mysql_try($query);
	if (mysql_num_rows($res) > 0) {
		$rows = array();
		while($row = mysql_fetch_assoc($res)) {
			$rows[] = $row;
		}
		echo json_encode($rows);
	} else {
		echo '[]';
	}
}
/**
 * show_all
 * 
 * Aug5 2014
 * Add hash_id
 */
else if ( // show all jobs findhits?match=false
	isset($_GET['show_all'])
) {
	
	
	$res = mysql_query('
		SELECT
			jobs.id as id,
			jobs.time_posted as time_posted,
			jobs.HITs_available as HITs_available,
			jobs.on_page_number as on_page_number,
			
			parent.title as title,
			parent.requester_id as requester_id,
			parent.requester_name as requester_name,
			parent.groupId as groupId
		FROM `jobs` jobs
		LEFT JOIN `jobs_history_index` parent ON jobs.id_jobs_history_index = parent.id
		WHERE
			jobs.time_posted != 0
		ORDER BY
			jobs.time_posted DESC
	');//parent.hash_id
	$out	= array();
	$out[0] = array();
	$out[1] = array();
	$out[2] = array();
	$out[3] = array();
	$out[4] = array();
	$out[5] = array();
	$out[6] = array();
	$out[7] = array();
	$out[8] = array();
	
	while ($row = mysql_fetch_array($res))
	{
		array_push($out[0],$row['id']);
		array_push($out[1],$row['on_page_number']);
		array_push($out[2],$row['title']);
		array_push($out[3],$row['requester_name']);
		array_push($out[4],$row['HITs_available']);
		array_push($out[5],$row['time_posted']);
		array_push($out[6],$row['requester_id']);
		array_push($out[7],$row['groupId']);
		//~ array_push($out[8],$row['hash_id']);
	}
	echo json_encode($out);
	exit;
}
/**
 * graph
 * 
 * 
 */
else if
	( //
	isset($_POST['graph'])
	)
{
	
	/**
	 * $graph_obj
	 * 
	 * This contains:
	 * @type
	 *  "24HOUR"
	 * 	1 =
	 * 		Get 10-20 minute updates for all in a list. Retrieve 24 hours of this.
	 *  	This is based on an initial list of ~200 hash IDs.
	 *  "FULL"
	 *  2 = Get full history for this job.
	 *  "ZOOM-WINDOW"
	 *  3 = Get "zoomed in" details for this job (Zoomed in is always one week? OR... Max time? One week?)
	 * 
	 * @...
	 * 
	 * @ids
	 *  ->array
	 * 
	 * MD5 HASH FOR JOBS
	 * $md5 = md5(
			$v['title'].
			$v['requester_id'].
			$v['requester_name'].
			$v['id_duplicate_jobs'].
			$v['qualifications'].
			$v['groupId'].
			$v['reward'].
			$v['time_allotted'].
			$v['description']
		);
		
		Table: jobs_history_index
		Columns:
		id	int(11) UN PK AI
		title	text 
		requester_id	varchar(60) 
		requester_name	varchar(255) 
		id_duplicate_jobs	int(11) UN 
		qualifications	text 
		groupId	varchar(45) 
		reward	varchar(45) 
		time_allotted	varchar(45) 
		description	text 
		hash_id	varchar(255) 
	 */
	$graph_obj = json_decode($_GET['graph']);
	
	if ($graph_obj->type == '24HOUR') {
		
		// hash_ids
		// These are from the hash_id field on .jobs.
		//xxxxxThese are the ids from the .jobs table.
		$graph_obj->hash_ids = json_decode($graph_obj->hash_ids);
		//~ $ids = json_decode($graph_obj->ids
		
		// query
		$query = 'SELECT * FROM AMT_Batch.jobs WHERE';
		
		//SELECT * FROM AMT_Batch.jobs_history_index order by id desc limit 40;
		
		
	}
	
	exit;
}
/**
 * debug
 * 
 * 
 */
else if
	( // show all jobs findhits?match=false
	isset($_GET['debug'])
	)
{
	$res = mysql_query('
		SELECT
			id,on_page_number,title,requester_name,HITs_available,time_posted,requester_id,groupId
		FROM
			`jobs`
		WHERE
			time_posted != 0
		ORDER BY
			time_posted DESC
	');
	
	$ignore_requesters = [
		'CrowdFlower',
		'CrowdSource',
		'Classify AdImages',
		'ClariTrans',
		'RelevanceQuest',
		'AMTSpeech',
		'Amazon Requester Inc. (Product Ads)',
		'Amazon Requester Inc.',
		'Chris Callison-Burch',
		'User453',
		'ProductRnR',
		'WSOVC COM',
		'rohzit0d',
		'Cardcow',
		'Redwood',
		'Craft Listings',
		'Stanford Language and Cognition Lab',
		'Brandeis NLP',
		'Streema',
		'Fluffy Cloud',
		'Intelius Data Systems',
		'Johnny Wong',
		'Oscar Smith',
		'Sara Rosenthal',
		'Comdata IT Fuel Tax',
		'Venue Quality',
		'Product Listings',
		'Amazon Requester Inc - browse classification',
		'Classify This',
		'Tagasauris',
		'chinese.mturk',
		'Quality Website Reviews',
		'Phillip Atiba Goff',
		'Catalog Choice',
		'WMT Admin',
		'Sebastian Darr',
		'Coder of Web Apps',
		'Smartsheet.com Clients',
		'A-Team',
		'MASC Data',
		'Ad Tagger',
		'Dmitry Kachaev',
		'Nic Lowe',
		'Nascent Visions Corp',
		'RI Services',
		'techlist',
		'Benjamin C Lok',
		'Readabl',
		'NetMSi',
		'Todd Dickerson',
		'Jimmy Dragon',
		'Jon Brelig',
		'Taste of the World',
		'SET Master Account',
		'Ignite Media Solutions',
		'The Public Group',
		'Turk Experiment',
		'Crowdsurf Support',
		'Social Media Research',
		'11223344',
		'A Global Media Analytics',
		'411Richmond',
		'Visual Genome',
		'p9r'
	];
	
	while ($row = mysql_fetch_array($res))
	{
		if (in_array($row['requester_name'], $ignore_requesters))
			continue;
		echo $row['title'].',';
		echo $row['requester_name'].',';
		echo $row['HITs_available'].',';
		echo date('g:i', $row['time_posted']);
		echo '<br>';
	}
}
exit;




function mysql_try($query) {
	$result = false;
	$count = 0;
	while (!$result && $count < 4) {
		if ($rslt = mysql_query($query)) {
			return $rslt;
		}
		$count++;
		sleep(1);
	}
	if (!$result) {
		die(err(mysql_error()).':count:'.$count);
	}
}
function err($mysql) {
	global $debug_pos,$count_pages_complete;
	
	$a = date('i');
    if ($a == '00') $a = "\r\n".$a;
    	
	$e =	'<br>'.
		'Debug pos:'.$debug_pos.
		'<br>'.
		'Mysql:'.$mysql.
		'<br>'.
		'Count pages complete:'.$count_pages_complete.
		'<br>'.
		$a;
	//echo $e;

	file_put_contents(
		//'/home/a3450205/public_html/debug2.log',
		'./debug2.log',
		$e,
		FILE_APPEND
	);
}



?>
