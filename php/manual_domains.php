<?php

/*Table: manual_domains
Columns:
id	int(10) UN PK AI
domain	varchar(255) 
type	tinyint(4) 
agent_directory_url	varchar(255) 
directory_images_full_size	tinyint(1)*/



mysql_connect('localhost', 'root', '_redacted_') or die('Could not connect');
mysql_select_db('amt_kristin_howe') or die('Could not select');

// initial (Core mturk.com)
if (isset($_GET['read_complete']) && isset($_GET['site']) && isset($_GET['name'])) {
	$rslt = mysql_query('
		SELECT image_url FROM amt_kristin_howe.images
		WHERE
			agent_name="'.mysql_real_escape_string($_GET['name']).'" &&
			website_url="'.mysql_real_escape_string($_GET['site']).'"
	') or die(mysql_error());
	// update
	if (mysql_num_rows($rslt) > 0) {
		$row = mysql_fetch_array($rslt);
		echo json_encode($row);
	}
}
if (isset($_GET['initial_iframe']) && isset($_GET['site'])) {
	
	$rslt = mysql_query('
		SELECT type,agent_directory_url FROM manual_domains
		WHERE
			domain="'.mysql_real_escape_string($_GET['site']).'"
	');
	
	if (mysql_num_rows($rslt) == 0) {
		echo '{"type":"","agent_directory_url":""}';
	}
	else {
		$row = mysql_fetch_array($rslt);
		echo json_encode($row);
	}
	exit;
}
// load
if (isset($_GET['load_site']) && isset($_GET['site'])) {
	
	$rslt = mysql_query('
		SELECT type,agent_directory_url,directory_images_full_size FROM manual_domains
		WHERE
			domain="'.mysql_real_escape_string($_GET['site']).'"
	');
	
	$out_array = array();
	if (mysql_num_rows($rslt) == 0) {
		echo '[]';
	}
	else {
		$row = mysql_fetch_array($rslt);
		$out_array['type'] = $row['type'];
		$out_array['agent_directory_url'] = $row['agent_directory_url'];
		$out_array['directory_images_full_size'] = $row['directory_images_full_size'];
		echo json_encode($out_array);
	}
	exit;
}
// write/save
if (isset($_GET['save_site']) && isset($_GET['data'])) {
	
	//echo $_GET['data'];exit;
	$v = json_decode($_GET['data'], true); // true->decode as array
	//$v['manual__directory_images_full_size'] = ($v['manual__directory_images_full_size'] == 'on') ? '1' : '0';
	//print_r($v); exit;
	
	$rslt = mysql_query('
		SELECT * FROM manual_domains
		WHERE
			domain="'.mysql_real_escape_string($v['manual__domain']).'"
	');
	
	if (mysql_num_rows($rslt) == 0) {
		$rslt = mysql_query('
			INSERT INTO manual_domains
			SET
				domain="'.mysql_real_escape_string($v['manual__domain']).'",
				type="'.mysql_real_escape_string($v['manual__type']).'",
				agent_directory_url="'.mysql_real_escape_string($v['manual__agent_directory_url']).'",
				directory_images_full_size="'.mysql_real_escape_string($v['manual__directory_images_full_size']).'"
		') or die (mysql_error());
	}
	else {
		$rslt = mysql_query('
			UPDATE manual_domains
			SET
				type="'.mysql_real_escape_string($v['manual__type']).'",
				agent_directory_url="'.mysql_real_escape_string($v['manual__agent_directory_url']).'",
				directory_images_full_size="'.mysql_real_escape_string($v['manual__directory_images_full_size']).'"
			WHERE
				domain="'.mysql_real_escape_string($v['manual__domain']).'"
		') or die (mysql_error());
	}
	// echos blank string or error
	exit;
}


























?>
