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

/**
 * Only run the file under these circumstances:
 */
if  (
	!isset($_GET['last_update']) &&
	!isset($_GET['jobs']) &&
	!isset($_GET['hits_available']) &&
	!isset($_GET['show_all']) &&
	!isset($_GET['requester-overview']) && // NEW Apr. 16, 2015
	!isset($_GET['debug']) &&
	!isset($_POST['graph'])
	)
{
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
//~ $mysql_database = "[redacted]_AMT";
//~ $mysql_user = "[redacted]_user";
//~ $mysql_password = "[redacted]";

// CHANGED THIS 5 SEP 2014
// After turning on selinux from
// permissive to enforcing.
// "mysql_host" changed from "localhost" to "127.0.0.1".
//*
set_time_limit(110); // 1:50
//~ $mysql_host = "127.0.0.1"; // localhost
$mysql_host = "localhost"; // localhost
$mysql_database = "AMT_Batch";
$mysql_user = "root";
$mysql_password = "[redacted]";
//*/


// Connect
//~ mysql_connect($mysql_host,$mysql_user,$mysql_password) or die(mysql_error());
$mysqli = new mysqli("localhost", "root", $mysql_password, "AMT_Batch");
// Test connection
if (mysqli_connect_errno()) {
    printf("Connect failed: %s\n", mysqli_connect_error());
    exit();
}
// Select db
//~ $mysqli->select_db($mysql_database);
//~ mysqli_select_db($mysql_database) or die(err(mysql_error()));
/***
 * UTF-8
**/
//~ mysqli_query("SET names 'utf8'");
$mysqli->query("SET names 'utf8'");
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
	if (mysqli_num_rows($res) > 0) {
		$rows = array();
		while($row = mysqli_fetch_assoc($res)) {
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
	if (mysqli_num_rows($res) > 0) {
		$rows = array();
		while($row = mysqli_fetch_assoc($res)) {
			$rows[] = $row;
		}
		echo json_encode($rows);
	}
	if (mysqli_num_rows($res) == 0) {
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
	if (mysqli_num_rows($res) > 0) {
		$rows = array();
		while($row = mysqli_fetch_assoc($res)) {
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
 * 
 * Note 1: ROUND( SUM(task_votes.value) / COUNT(DISTINCT task_votes.id), 1 ) ==> Round to one decimal place
 * 
 * NOTE 2: Table .jobs is all tasks on first 20 pages (~200).
 * 
 * table .votes:
		id	int(10) UN PK AI
		value	smallint(6) 
		fk_user_name	varchar(10) 
		fk_id_jobs_history_index	int(11) UN 
		fk_requester_id	varchar(45) 
 */
else if ( // show all jobs findhits?match=false
	isset($_GET['show_all'])
) {
	
	//parent.requester_id IS A FK
	// Unnecessary of course:
	//		GROUP_CONCAT(task_votes.fk_id_jobs_history_index SEPARATOR ",") as task_votes_idjh,
	//		GROUP_CONCAT(requester_votes.fk_requester_id SEPARATOR ",..,..,") as requester_votes_ids,
	//		GROUP_CONCAT(task_comments.fk_id_jobs_history_index SEPARATOR ",") as task_comments_idjh
	$res = $mysqli->query('
		SELECT
			
			jobs.id as id,
			jobs.time_posted as time_posted,
			jobs.HITs_available as HITs_available,
			jobs.on_page_number as on_page_number,
			
			parent.title as title,
			parent.requester_id as requester_id,
			parent.requester_name as requester_name,
			parent.groupId as groupId,
			parent.id as history_id,
			parent.reward as reward,
			
			GROUP_CONCAT(task_votes.vote) as task_votes_values,
			GROUP_CONCAT(task_votes.fk_user_name SEPARATOR ",") as task_votes_users,
			ROUND( SUM(task_votes.vote) / COUNT(DISTINCT task_votes.id), 1 ) as task_votes_average,
			
			GROUP_CONCAT(requester_votes.vote) as requester_votes_values,
			GROUP_CONCAT(requester_votes.fk_user_name SEPARATOR ",") as requester_votes_users,
			ROUND( SUM(requester_votes.vote) / COUNT(DISTINCT requester_votes.id), 1 ) as requester_votes_average,
			
			task_comments.comment as comment,
			task_comments.last_modified as comment_last_modified
			
		FROM `jobs` jobs
		
		LEFT JOIN `jobs_history_index` parent ON jobs.id_jobs_history_index = parent.id
		LEFT JOIN `votes` task_votes ON parent.id = task_votes.fk_id_jobs_history_index
		LEFT JOIN `votes` requester_votes ON parent.requester_id = task_votes.fk_id_jobs_history_index
		LEFT JOIN `comments` task_comments ON parent.id = task_comments.fk_id_jobs_history_index
		
		WHERE
			jobs.time_posted != 0
		GROUP BY
			jobs.id
		ORDER BY
			jobs.time_posted DESC
	');//parent.hash_id
	//GROUP_CONCAT(task_comments.comment) as task_comments,
	//GROUP_CONCAT(task_comments.fk_user_name SEPARATOR ",") as task_comments_users
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
	$out[9] = array();
	$out[10] = array(); // title
	$out[11] = array(); // title
	$out[12] = array(); // req.
	$out[13] = array(); // req.
	$out[14] = array(); // req.
	$out[15] = array(); // req.
	$out[16] = array(); // req.
	$out[17] = array(); // req.
	$out['rows'] = array();
	
	while ($row = mysqli_fetch_array($res))
	{
		if  (
				($_GET['show_all'] == '') ||
				($_GET['show_all'] != '' && intval($_GET['show_all']) < $row['time_posted'])
			)
		{
			$row['comment'] = (!$row['comment']) ? '' : $row['comment'];
			$row['comment_last_modified'] = (!$row['comment_last_modified']) ? '' : $row['comment_last_modified'];
			
			array_push($out[0],$row['id']);
			array_push($out[1],$row['on_page_number']);
			array_push($out[2],$row['title']);
			array_push($out[3],$row['requester_name']);
			array_push($out[4],$row['HITs_available']);
			array_push($out[5],$row['time_posted']);
			array_push($out[6],$row['requester_id']);
			array_push($out[7],$row['groupId']);
			array_push($out[8],$row['history_id']);
			array_push($out[9],$row['reward']);
			array_push($out[10],$row['comment']);
			array_push($out[11],$row['comment_last_modified']);
			// Full row
			$out[ 'rows' ][ $row['id'] ] = array(
				'0'=>$row['id'],
				'1'=>$row['on_page_number'],
				'2'=>$row['title'],
				'3'=>$row['requester_name'],
				'4'=>$row['HITs_available'],
				'5'=>$row['time_posted'],
				'6'=>$row['requester_id'],
				'7'=>$row['groupId'],
				'8'=>$row['history_id'],
				'9'=>$row['reward'],
				'10'=>$row['comment'],
				'11'=>$row['comment_last_modified']
			);
			//~ array_push($out[10],$row['task_votes_values']);
			//~ array_push($out[11],$row['task_votes_users']);
			//~ array_push($out[12],$row['task_votes_idjh']);
			//~ array_push($out[13],$row['requester_votes_values']);
			//~ array_push($out[14],$row['requester_votes_users']);
			//~ array_push($out[15],$row['requester_votes_average']);
			//~ array_push($out[15],$row['task_comments']);
			//~ array_push($out[15],$row['task_comments_users']);
		}
		// Limit output to what is new. Give only HITs, updated/new comments, etc.
		else if ($_GET['show_all'] != '' && intval($_GET['show_all']) >= $row['time_posted'])
		{
			$row['comment'] = (!$row['comment']) ? '' : $row['comment'];
			$row['comment_last_modified'] = (!$row['comment_last_modified']) ? '' : $row['comment_last_modified'];
			
			array_push($out[0],$row['id']);
			array_push($out[1],'');
			array_push($out[2],'');
			array_push($out[3],'');
			array_push($out[4],$row['HITs_available']);
			array_push($out[5],'');
			array_push($out[6],'');
			array_push($out[7],'');
			array_push($out[8],'');
			array_push($out[9],'');
			// Only send back comments when they are newer than the JS timestamp.
			if  (
				$_GET['last_comment_retrieved'] != '' &&
				$row['comment'] != '' &&
				$row['comment_last_modified'] != '' &&
				intval($_GET['last_comment_retrieved']) < $row['comment_last_modified']
				)
			{
				array_push($out[10],$row['comment']);
				array_push($out[11],$row['comment_last_modified']);
			}
			// No new comment.
			else {
				array_push($out[10],'');
				array_push($out[11],'');
			}
		}
	}
	echo json_encode($out);
	exit;
}
/**
 * requester-overview
 * 
 * @param: $_GET['requester-overview']
 * @param: $_GET['requester-overview']->requesterIDs, Array
 * @returns: Associative array: DB rows of jobs. Grouped by requester ID.
 * 
 * Description: Sort by `id` descending.
 * 		- Group concat has 1024 character limit, so overwrite max.
 * 		- Group by id is required. Otherwise the query is returned as a single row.
 * 
 * 
 * AMT_Batch.jobs_history_index:
 *  id	int(11) UN PK AI
	title	text 
	requester_id	varchar(45) 
	requester_name	varchar(255) 
	id_duplicate_jobs	int(11) UN 
	qualifications	text 
	groupId	varchar(45) 
	reward	varchar(45) 
	time_allotted	varchar(45) 
	description	text 
	hash_id	varchar(255) 
 */
else if
	(
	isset($_GET['requester-overview'])
	)
{
	$rq_obj = json_decode($_GET['requester-overview']);
	if (!property_exists($rq_obj, 'requesterIDs')) {
		exit;
	}
	//~ mysql_query("SET SESSION group_concat_max_len = 100000000000;"); // OVERWRITE
	$mysqli->query("SET SESSION group_concat_max_len = 100000000000;"); // OVERWRITE
	$rids = [];
	foreach ($rq_obj->requesterIDs as $key => $rid) {
		$rid = preg_replace('/[^a-z0-9]/i', '', $rid);
		array_push($rids, mysqli_real_escape_string($mysqli, $rid));
	}
	$query = '
		SELECT
			jhi.id as id,
			title,
			requester_id,
			requester_name,
			qualifications,
			groupId,
			reward,
			time_allotted,
			description,
			id_duplicate_jobs,
			hash_id,
			#GROUP_CONCAT(DISTINCT B.HITs_available) AS g
			GROUP_CONCAT(DISTINCT DATE_FORMAT(FROM_UNIXTIME(history.date_run_time),			\'%Y-%m-%d\')) as g
			#GROUP_CONCAT(DISTINCT DATE_FORMAT(FROM_UNIXTIME(history_weekly.date_run_time),	\'%Y-%m-%d\')) as g2
		FROM
			AMT_Batch.jobs_history_index as jhi
		LEFT JOIN AMT_Batch.history as history ON history.id_jobs_history_index = jhi.id
		#LEFT JOIN AMT_Batch.history_weekly as history_weekly ON history_weekly.id_jobs_history_index = jhi.id
		WHERE
			requester_id IN ("'. implode('","', $rids) .'")
		GROUP BY id
		ORDER BY id DESC
	'; //echo $query;
	$rslt = $mysqli->query($query) or die(mysqli_error());
	
	// Return an associative array.
	// Grouped by requester ID.
	$return_obj = array();
	while ($row = mysqli_fetch_array($rslt)) {
		if (strpos($row['title'], '<span') !== false) continue; // QUICK HACK. Fix if necessary.
		if (strpos($row['title'], 'span>') !== false) continue; // QUICK HACK. Fix if necessary. END SPAN
		if (strpos($row['requester_name'], '<span') !== false) continue; // QUICK HACK. Fix if necessary.
		if (strpos($row['requester_name'], 'span>') !== false) continue; // QUICK HACK. Fix if necessary. END SPAN
		if  (
			!isset($return_obj[ $row['requester_id'] ])
			)
			$return_obj[ $row[ 'requester_id' ] ] = array();
		// For each distinct day, add on the max hits (high water mark).
		$query2 = 'SELECT
			MAX(H.HITs_available) AS max_hits,
			DATE_FORMAT( FROM_UNIXTIME(H.date_run_time), \'%Y-%m-%d\' ) AS d
		FROM AMT_Batch.history AS H
		WHERE id_jobs_history_index = '.$row['id'].'
		GROUP BY DATE_FORMAT( FROM_UNIXTIME(H.date_run_time), \'%Y-%m-%d\' );';
		//~ echo $query2; exit;
		//~ WHERE id_jobs_history_index in (SELECT id FROM jobs_history_index
			//~ WHERE requester_id in ("'. implode('","', $rids) .'")
		//~ )
		$rslt2 = $mysqli->query($query2) or die(mysqli_error());
		$high_water_marks = []; // Assoc. array.
		while ($row2 = mysqli_fetch_array($rslt2)) {
			$high_water_marks[$row2['d']] = $row2['max_hits'];
		}
		//~ echo $query2; var_dump($high_water_marks);exit;
		array_push(
			$return_obj[ $row['requester_id'] ],
			array(
				'id'				=> $row['id'],
				'title'				=> $row['title'],
				'requester_id'		=> $row['requester_id'],
				'requester_name'	=> $row['requester_name'],
				'qualifications'	=> $row['qualifications'],
				'groupId'			=> $row['groupId'],
				'reward'			=> $row['reward'],
				'time_allotted'		=> $row['time_allotted'],
				'description'		=> $row['description'],
				'id_duplicate_jobs'	=> $row['id_duplicate_jobs'],
				'hash_id'			=> $row['hash_id'],
				'distinct_dates'	=> $row['g'],
				'high_water_marks'	=> $high_water_marks
				#'distinct_dates2'	=> $row['g2']
			)
		);
	}
	
	echo json_encode($return_obj);
}
/**
 * graph
 * 
 * @param: $_POST['graph']
 * @param: $_POST['graph']->type, String
 * @param: $_POST['graph']->history_ids, Array
 * 
 */
else if
	(
	isset($_POST['graph'])
	)
{
	/**
	 * $graph_obj
	 * 
	 * This contains:
	 * @type
	 *  1)"24HOUR"
	 * 		Get 10-20 minute updates for all in a list. Retrieve 24 hours of this.
	 *  	This is based on an initial list of ~200 hash IDs.
	 *  2)"FULL"
	 *		Get full history for this job.
	 *  3)"ZOOM-WINDOW"
	 *		Get "zoomed in" details for this job (Zoomed in is always one week? OR... Max time? One week?)
	 * 
	 * ...
	 * 
	 * NOTES:
	 * 		- When making averages, the final number must be rounded.
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
	$graph_obj = json_decode($_POST['graph']);
	//~ print_r($graph_obj);
	
	// DAILY-20MIN-AVG
	// 1200s
	//
	if ($graph_obj->type == 'DAILY-20MIN-AVG') { //avg
		
		// history_ids
		// These are from the jobs_history_index table.
		$in_str = preg_replace('/[^0-9,]/', '', implode(',', $graph_obj->history_ids)); // Replace [^\d,] with ""
		$minus_one_day = time() - 86400 * 7;
		
		// query
		// BY id DESC == Most recent first.
		// BY id ASC  == Oldest entry first.
		$query = '
			SELECT
				id_jobs_history_index AS i,
				date_run_time AS d,
				ROUND(AVG(HITs_available)) AS h
			FROM
				AMT_Batch.history_weekly
			WHERE
				id_jobs_history_index IN ('.$in_str.') &&
				date_run_time > '.$minus_one_day.'
			GROUP BY
				id_jobs_history_index,
				FLOOR(date_run_time / 1200)
			ORDER BY
				id
			ASC
		';
		$rslt = $mysqli->query($query) or die(mysqli_error());//mysql_error());
		
		// Make into an object
		// where key == id_jobs_history_index.
		//
		//
		$obj = array();
		while ($row = mysqli_fetch_array($rslt)) {
			if  (
				!isset($obj[$row['i']])
				)
				$obj[$row['i']] = array();
			array_push(
				$obj[$row['i']],
				array(
					'd' => $row['d'],
					'h' => $row['h']
				)
			);
		}
		echo json_encode($obj);
	}
	// DAILY-30MIN-AVG
	// 1800s
	//
	if ($graph_obj->type == 'DAILY-30MIN-AVG') { //avg
		$in_str = preg_replace('/[^0-9,]/', '', implode(',', $graph_obj->history_ids)); // Replace [^\d,] with ""
		$minus_one_day = time() - 86400 * 7;
		
		// query
		// BY id DESC == Most recent first.
		// BY id ASC  == Oldest entry first.
		$query = '
			SELECT
				id_jobs_history_index AS i,
				date_run_time AS d,
				ROUND(AVG(HITs_available)) AS h
			FROM
				AMT_Batch.history_weekly
			WHERE
				id_jobs_history_index IN ('.$in_str.') &&
				date_run_time > '.$minus_one_day.'
			GROUP BY
				id_jobs_history_index,
				FLOOR(date_run_time / 1800)
			ORDER BY
				id
			ASC
		';
		
		//~ echo $query;
		
		$rslt = $mysqli->query($query) or die(mysqli_error());
		
		// Make into an object
		// where key == id_jobs_history_index.
		//
		//
		$obj = array();
		while ($row = mysqli_fetch_array($rslt)) {
			if  (
				!isset($obj[$row['i']])
				)
				$obj[$row['i']] = array();
			array_push(
				$obj[$row['i']],
				array(
					'd' => $row['d'],
					'h' => $row['h']
				)
			);
		}
		echo json_encode($obj);
	}
	if ($graph_obj->type == 'DAILY-6HOUR-AVG') { //avg
		$in_str = preg_replace('/[^0-9,]/', '', implode(',', $graph_obj->history_ids)); // Replace [^\d,] with ""
		$minus_one_day = time() - 86400 * 7;
		
		// query
		// BY id DESC == Most recent first.
		// BY id ASC  == Oldest entry first.
		$query = '
			SELECT
				id_jobs_history_index AS i,
				date_run_time AS d,
				ROUND(AVG(HITs_available)) AS h
			FROM
				AMT_Batch.history_weekly
			WHERE
				id_jobs_history_index IN ('.$in_str.') &&
				date_run_time > '.$minus_one_day.'
			GROUP BY
				id_jobs_history_index,
				FLOOR(date_run_time / (3600 * 6))
			ORDER BY
				id
			ASC
		';
		$rslt = $mysqli->query($query) or die(mysqli_error());
		
		// Make into an object
		// where key == id_jobs_history_index.
		//
		//
		$obj = array();
		while ($row = mysqli_fetch_array($rslt)) {
			if  (
				!isset($obj[$row['i']])
				)
				$obj[$row['i']] = array();
			array_push(
				$obj[$row['i']],
				array(
					'd' => $row['d'],
					'h' => $row['h']
				)
			);
		}
		echo json_encode($obj);
	}
	if ($graph_obj->type == '24HOUR-ALL') {
		$in_str = preg_replace('/[^0-9,]/', '', implode(',', $graph_obj->history_ids)); // Replace [^\d,] with ""
		$minus_one_day = time() - 86400;
		
		// query
		// BY id DESC == Most recent first.
		// BY id ASC  == Oldest entry first.
		$query = '
			SELECT
				id_jobs_history_index AS i,
				date_run_time AS d,
				HITs_available AS h
			FROM
				AMT_Batch.history_weekly
			WHERE
				id_jobs_history_index IN ('.$in_str.') &&
				date_run_time > '.$minus_one_day.'
			ORDER BY
				id
			ASC
		';
		$rslt = $mysqli->query($query) or die(mysqli_error());
		
		// Make into an object
		// where key == id_jobs_history_index.
		//
		//
		$obj = array();
		while ($row = mysqli_fetch_array($rslt)) {
			if  (
				!isset($obj[$row['i']])
				)
				$obj[$row['i']] = array();
			array_push(
				$obj[$row['i']],
				array(
					'd' => $row['d'],
					'h' => $row['h']
				)
			);
		}
		echo json_encode($obj);
	}
	if ($graph_obj->type == '2HOUR-ALL') {
		$in_str = preg_replace('/[^0-9,]/', '', implode(',', $graph_obj->history_ids)); // Replace [^\d,] with ""
		$minus_one_day = time() - 7200;
		
		// query
		// BY id DESC == Most recent first.
		// BY id ASC  == Oldest entry first.
		$query = '
			SELECT
				id_jobs_history_index AS i,
				date_run_time AS d,
				HITs_available AS h
			FROM
				AMT_Batch.history_weekly
			WHERE
				id_jobs_history_index IN ('.$in_str.') &&
				date_run_time > '.$minus_one_day.'
			ORDER BY
				id
			ASC
		';
		$rslt = $mysqli->query($query) or die(mysqli_error());
		
		// Make into an object
		// where key == id_jobs_history_index.
		//
		//
		$obj = array();
		while ($row = mysqli_fetch_array($rslt)) {
			if  (
				!isset($obj[$row['i']])
				)
				$obj[$row['i']] = array();
			array_push(
				$obj[$row['i']],
				array(
					'd' => $row['d'],
					'h' => $row['h']
				)
			);
		}
		echo json_encode($obj);
	}
	if ($graph_obj->type == '30MINUTE-ALL') {
		$in_str = preg_replace('/[^0-9,]/', '', implode(',', $graph_obj->history_ids)); // Replace [^\d,] with ""
		$minus_one_day = time() - 1800;
		
		// query
		// BY id DESC == Most recent first.
		// BY id ASC  == Oldest entry first.
		$query = '
			SELECT
				id_jobs_history_index AS i,
				date_run_time AS d,
				HITs_available AS h
			FROM
				AMT_Batch.history_weekly
			WHERE
				id_jobs_history_index IN ('.$in_str.') &&
				date_run_time > '.$minus_one_day.'
			ORDER BY
				id
			ASC
		';
		$rslt = $mysqli->query($query) or die(mysqli_error());
		
		// Make into an object
		// where key == id_jobs_history_index.
		//
		//
		$obj = array();
		while ($row = mysqli_fetch_array($rslt)) {
			if  (
				!isset($obj[$row['i']])
				)
				$obj[$row['i']] = array();
			array_push(
				$obj[$row['i']],
				array(
					'd' => $row['d'],
					'h' => $row['h']
				)
			);
		}
		echo json_encode($obj);
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
	$res = $mysqli->query('
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
	
	while ($row = mysqli_fetch_array($res))
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

/**
 * function mysql_try
 * This is a mysql-try function to retry when failed.
 * 
 * This is pretty much used when this program (g.php) is being
 * hosted on a really poor host (such as free php hosts online).
 * On these hosts, a mysql connection will occasionally fail.
 * Thus, the need for this function :)
 * 
 * Note: The err() function (below) also is mostly for the same case.
 */
function mysql_try($query) {
	global $mysqli;
	$result = false;
	$count = 0;
	while (!$result && $count < 4) {
		if ($rslt = $mysqli->query($query)) {
			return $rslt;
		}
		$count++;
		sleep(1);
	}
	if (!$result) {
		printf("Connect failed: %s\n", mysqli_connect_error());
		exit();
		//~ die(err(mysql_error()).':count:'.$count);
	}
}
function err($mysql) {
	global $debug_pos,$count_pages_complete, $mysqli;
	$a = date('i');
    if ($a == '00') $a = "\r\n".$a;
	$e = '<br>'.
		'Debug pos:'.$debug_pos.
		'<br>'.
		'Mysql:'.$mysql.
		'<br>'.
		'Count pages complete:'.$count_pages_complete.
		'<br>'.
		$a;
	file_put_contents(
		'./debug2.log',
		$e,
		FILE_APPEND
	);
}



?>
