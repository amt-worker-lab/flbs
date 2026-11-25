<?php 

error_reporting(E_ALL);

set_time_limit(180);

if (!isset($_POST['fFjbHoxpPdkEbnspVdoSgisN']) || !isset($_POST['t']))
	exit;

$mysql_host = "localhost";
$mysql_database = "_redacted__AMT";
$mysql_user = "_redacted__user";
$mysql_password = "_REDACTED_";

mysql_connect($mysql_host,$mysql_user,$mysql_password) or die(mysql_error());
mysql_select_db($mysql_database) or die(err(mysql_error()));

/***
 * UTF-8
**/
mysql_query("Set names 'utf8'"); 

mysql_query('UPDATE last_update SET HITs_available = 0, jobs = 0 WHERE id = 1');
mysql_query('DELETE FROM jobs WHERE id != -1');

$x = $_POST['fFjbHoxpPdkEbnspVdoSgisN'];
$x = json_decode(urldecode($x));
/*id as i,
title as t,
time_posted as p,
time_last_update as u,
requester_id as r,
requester_name as n,
HITs_available as h,
on_page_number as o,
groupId as g,
reward as w,
time_allotted as a,
description as d,
qualifications as q*/
foreach ($x as $k => $v) {
	mysql_query('
		INSERT INTO jobs
			(
				id,
				title,
				requester_id,
				requester_name,
				HITs_available,
				time_posted,
				time_last_update,
				job_exists,
				id_duplicate_jobs,
				on_page_number,
				qualifications,
				groupId,
				reward,
				time_allotted,
				description
			)
		VALUES
			(
				"'.mysql_real_escape_string($v->i).'",
				"'.mysql_real_escape_string($v->t).'",
				"'.mysql_real_escape_string($v->r).'",
				"'.mysql_real_escape_string($v->n).'",
				"'.mysql_real_escape_string($v->h).'",
				"'.mysql_real_escape_string($v->p).'",
				"'.mysql_real_escape_string($v->u).'",
				1,
				0,
				"'.mysql_real_escape_string($v->o).'",
				"'.mysql_real_escape_string($v->q).'",
				"'.mysql_real_escape_string($v->g).'",
				"'.mysql_real_escape_string($v->w).'",
				"'.mysql_real_escape_string($v->a).'",
				"'.mysql_real_escape_string($v->d).'"
			)
	');
}

$t = explode(',', urldecode($_POST['t']));
mysql_query('
	UPDATE last_update
	SET
		HITs_available = '.mysql_real_escape_string($t[0]).',
		jobs = '.mysql_real_escape_string($t[1]).'
	WHERE	
		id = 1
');




















?>
