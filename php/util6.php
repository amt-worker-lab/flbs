<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if (isset($_GET['kristin_howe_photos__name']) && isset($_GET['kristin_howe_photos__site'])) {
  
  

	mysql_connect('localhost', 'root', '_redacted_') or die('Could not connect');
	mysql_select_db('amt_kristin_howe') or die('Could not select');
	
	// @ var $out_array = array();
	// 	int		out_array['count_website_url']
	// 	array	out_array['results'][]
	//			[{result1},{result2},...{resultN}]
	$out_array = array();
	$out_array['results'] = array();
	
	// @ var count_website_url
	// does specified domain exist?
	// 
	// create all iterations of domain?
	$rslt = mysql_query('
		SELECT COUNT(id) AS count FROM images
		WHERE
			website_url="'.mysql_real_escape_string($_GET['kristin_howe_photos__site']).'"
	') or die(mysql_error());
	// will be 0 if none, or X if any
	$row = mysql_fetch_array($rslt);
	// 
	$out_array['count_website_url'] = $row['count'];
	
	// @ var results
	// all results (limit 10!)
	$rslt = mysql_query('
		SELECT agent_name, image_url FROM amt_kristin_howe.images
		WHERE
			MATCH(agent_name) AGAINST (\''.mysql_real_escape_string($_GET['kristin_howe_photos__name']).'\') &&
			website_url="'.mysql_real_escape_string($_GET['kristin_howe_photos__site']).'"
		LIMIT 10
	') or die(mysql_error());
	/*$rslt = mysql_query('
		SELECT agent_name,image_url FROM images
		WHERE
			agent_name LIKE \'%'.mysql_real_escape_string($_GET['kristin_howe_photos__name']).'%\' &&
			website_url="'.mysql_real_escape_string($_GET['kristin_howe_photos__site']).'"
		ORDER BY
			agent_name ASC
		LIMIT 20
	') or die(mysql_error());*/
	
	while ($row = mysql_fetch_array($rslt)) {
		$out_array['results'][] = array(
			'agent_name' => $row['agent_name'],
			'image_url' => $row['image_url']
		);
	}
	echo json_encode($out_array);
	
	exit;
}
if (isset($_GET['kristin_howe_photos__complete_name']) && isset($_GET['kristin_howe_photos__complete_site']) && isset($_GET['kristin_howe_photos__complete_image_url'])) {
	mysql_connect('localhost', 'root', '_redacted_') or die('Could not connect');
	mysql_select_db('amt_kristin_howe') or die('Could not select');
	
	//print_r($_GET);
	/*
	 * '?kristin_howe_photos__complete_name='+
		'&kristin_howe_photos__complete_site='+
		'&kristin_howe_photos__complete_image_url='+*/
	// exists?
	$rslt = mysql_query('
		SELECT id FROM amt_kristin_howe.images
		WHERE
			agent_name="'.mysql_real_escape_string($_GET['kristin_howe_photos__complete_name']).'" &&
			website_url="'.mysql_real_escape_string($_GET['kristin_howe_photos__complete_site']).'"
	') or die(mysql_error());
	// update
	if (mysql_num_rows($rslt) > 0) {
		$row = mysql_fetch_array($rslt);
		$rslt = mysql_query('
			UPDATE amt_kristin_howe.images
			SET
				agent_name="'.mysql_real_escape_string($_GET['kristin_howe_photos__complete_name']).'",
				image_url="'.mysql_real_escape_string($_GET['kristin_howe_photos__complete_image_url']).'"
			WHERE
				id="'.mysql_real_escape_string($row['id']).'"
		') or die(mysql_error());
	}
	// new
	else {
		$rslt = mysql_query('
			INSERT INTO amt_kristin_howe.images
			SET
				agent_name="'.mysql_real_escape_string($_GET['kristin_howe_photos__complete_name']).'",
				image_url="'.mysql_real_escape_string($_GET['kristin_howe_photos__complete_image_url']).'",
				website_url="'.mysql_real_escape_string($_GET['kristin_howe_photos__complete_site']).'"
		') or die(mysql_error());
	}
}

























?>
