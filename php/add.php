<?php


error_reporting(E_ALL|E_STRICT);


$cln = $_POST;
foreach ($cln as $k=>$v) {
	$cln[$k] = htmlspecialchars($v);
}
foreach ($_POST as $k=>$v) {
	$_POST[$k] = trim($v);
}

?>
<html>
<head>
<style type="text/css">
textarea {
	width:60%;
	height:80px;
	
}
input {
	/*width:100%;*/
}
</style>
</head>

<body>
<!--form-->
<form method="post" action="?<?php echo rand(0,2000000000); ?>">
	<p>
	Domain (i.e. cbpremier.com):<br>
	<input type="text" name="domain" style="width:60%;" value="<?php echo (isset($cln['domain'])) ? $cln['domain']: ''; ?>">
	</p>
	<p>
	List of URLs by new line:<br>
	Expansion is {{_____+1+20963+1+_____}} (start, end, increment), i.e. {{_____+start+end+increment_____}} (i=start; i<=end; i+=increment)<br>
	Ex: {{_____+1+10+1+_____}} 1 to 10 by 1. {{_____+0+20+5+_____}} 0 to 20 by 5.<br>
	<textarea name="urls" style="width:90%"><?php echo (isset($cln['urls'])) ? $cln['urls']: ''; ?></textarea>
	</p>
	<p>
	Regular expression:<br>
	<input type="text" name="regex" value="<?php
		echo (isset($cln['regex'])) ? $cln['regex']: '';
	?>" style="width:60%;">
	</p>
	<p>
	Regular expression capture #:<br>
	DB agent_name:<input type="text" name="regex_agent_name" value="<?php 
		echo (isset($cln['regex_agent_name'])) ? $cln['regex_agent_name'] : '2'; 
	?>" size=6><br>
	DB image_url:<input type="text" name="regex_image_url" value="<?php
		echo (isset($cln['regex_image_url'])) ? $cln['regex_image_url'] : '1';
	?>" size=6><br>
	DB brokerage_name:<input type="text" name="regex_brokerage_name" value="<?php
		echo (isset($cln['regex_brokerage_name'])) ? $cln['regex_brokerage_name'] : '';
	?>" size=6><br>
	DB brokerage_website1:<input type="text" name="regex_brokerage_website" value="<?php
		echo (isset($cln['regex_brokerage_website'])) ? $cln['regex_brokerage_website'] : '';
	?>" size=6><br>
	DB brokerage_website2:<input type="text" name="regex_brokerage_website2" value="<?php
		echo (isset($cln['regex_brokerage_website2'])) ? $cln['regex_brokerage_website2'] : '';
	?>" size=6><br>
	DB brokerage_city:<input type="text" name="regex_brokerage_city" value="<?php
		echo (isset($cln['regex_brokerage_city'])) ? $cln['regex_brokerage_city'] : '';
	?>" size=6><br>
	DB brokerage_state:<input type="text" name="regex_brokerage_state" value="<?php
		echo (isset($cln['regex_brokerage_state'])) ? $cln['regex_brokerage_state'] : '';
	?>" size=6>
	</p>
	<p>
	Images that are considered to be "no image" (separate by new line):<br>
	<textarea name="image_no_image" style=""><?php
		echo (isset($cln['image_no_image'])) ? $cln['image_no_image']: '';
	?></textarea>
	</p>
	<p>
	Regular expression functions to be called on the names:<br>
	(Separate by new line. Each line is a "JSON array". Ex: ["/^/", "http://replacment/start"] ["/^(.+?),(.+?)$/", "$2 $1"])<br>
	<textarea name="regex_name_functions" style=""><?php
		echo (isset($cln['regex_name_functions'])) ? $cln['regex_name_functions']: '';
	?></textarea>
	</p>
	<p>
	Regular expression functions to be called on the images:<br>
	(Separate by new line. Each line is a "JSON array". Ex: ["/^/", "http://replacment/start"])<br>
	<textarea name="regex_image_functions" style=""><?php
		echo (isset($cln['regex_image_functions'])) ? $cln['regex_image_functions']: '';
	?></textarea>
	</p>
	<p>
	Block of text to execute upon:<br>
	<textarea name="content" style=""><?php echo (isset($cln['content'])) ? $cln['content']: ''; ?></textarea>
	</p>
	<p>
	Test: <input type="checkbox" name="is_test" value="1" style="width:none;"<?php echo (isset($cln['is_test'])) ? 'checked' : ''; ?>>
	<br>
	Debug: <input type="checkbox" name="is_debug" value="1" style="width:none;"<?php echo (isset($cln['is_debug'])) ? 'checked' : ''; ?>>
	</p>
	<input type="submit" value="Submit" style="width:100%;height:40px;">
</form>

<br>
PHP:
<br>
preg_replace($1, $2, $image/$name);
<br>
Examples of regular expression functions for images.<br>
<br>
NOTE: JSON.parse('["/^start/", "http://replacment/start"]');
<br>
["/130/", "150"] => replace 130 with 150<br>
["/^(\/sitebuilder/)", "http://www.site.com$1"] => prepend to start of string (with capture)<br>
["/^/", "http://"] => add to the start of string<br>
["/$/", "Make4x3=true"] => add to the end of string<br>


<p>
If image capture returns 12234 (i.e. an image number), call replacement on the image<br>
to create "http://iamges2.e-net.com/sadf/agent/12234.jpg":<br>
["/^(\d+)$/", "http://iamges2.e-net.com/sadf/agent/$1.jpg"]
</p>

<hr>
<hr>

<?php

//functions
function replace_num_entity($ord)
{
	$ord = $ord[1];
	if (preg_match('/^x([0-9a-f]+)$/i', $ord, $match))
	{
		$ord = hexdec($match[1]);
	}
	else
	{
		$ord = intval($ord);
	}
	
	$no_bytes = 0;
	$byte = array();

	if ($ord < 128)
	{
		return chr($ord);
	}
	elseif ($ord < 2048)
	{
		$no_bytes = 2;
	}
	elseif ($ord < 65536)
	{
		$no_bytes = 3;
	}
	elseif ($ord < 1114112)
	{
		$no_bytes = 4;
	}
	else
	{
		return;
	}

	switch($no_bytes)
	{
		case 2:
		{
			$prefix = array(31, 192);
			break;
		}
		case 3:
		{
			$prefix = array(15, 224);
			break;
		}
		case 4:
		{
			$prefix = array(7, 240);
		}
	}

	for ($i = 0; $i < $no_bytes; $i++)
	{
		$byte[$no_bytes - $i - 1] = (($ord & (63 * pow(2, 6 * $i))) / pow(2, 6 * $i)) & 63 | 128;
	}

	$byte[0] = ($byte[0] & $prefix[0]) | $prefix[1];

	$ret = '';
	for ($i = 0; $i < $no_bytes; $i++)
	{
		$ret .= chr($byte[$i]);
	}

	return $ret;
}
/*
== blank then no image on site
var o = '';
function esc(s) {
	s = s.replace(/([\n\r\\'"])/g, '\\$1');
	return s;
}



*/
/*
PHP CODE
	@param $_POST['domain']
		table row website_url
	@param $_POST['urls']
		list of urls separated by new line
	@param $_POST['regex']
		regex to run against the returned url
	@param $_POST['regex_params']
		list of parameters for the regex matches. Ex [agent_name,image_url]
	@param $_POST['regex_agent_name']
		# of the capture for agent name
	@param $_POST['regex_image_url']
		# of the capture for image url
	@param $_POST['regex_name_functions']
		List of regular expression functions to run on name captures. Separated
		by new line. Parsed as JSON object.
	@param $_POST['regex_image_functions']
		List of regular expression functions to run on image captures. Separated
		by new line. Parsed as JSON object.
*/

if (isset($_POST['content'])) {
	//
}
//if (isset($_POST['domain']) && $_POST['domain'] != '') {
if (isset($_POST['domain'])) {
	
	set_time_limit(0);
	
	//$debug = false;
	$debug = true;
	if ($debug) {
		$debug = $_POST;
		$debug['content'] = ''; // clear
		ob_start();
		print_r($debug);
		$debug = ob_get_clean();
		file_put_contents(
			'/var/www/html/mturk/KristinHowe_agent_photos/log',
			$debug."\n________=====================================_________\n",
			FILE_APPEND
		);
	}
	//print_r($_POST);
	
	mysql_connect('localhost', 'root', '_redacted_') or die('Could not connect');
	mysql_select_db('amt_kristin_howe') or die('Could not select');
	
	// URLs
	// expand URLs if desired, then turn them into
	// an array
	
	// expansion
	// {{_____+1+20963+_____}}
	// NEW
	// {{_____+1+20963+1+_____}} // start, end, increment
	$urls = array();
	$urls_tmp = explode("\n", $_POST['urls']);
	foreach ($urls_tmp as $k => $v) {
		preg_match('/^(.+?)\{\{_____\+(\d+)\+(\d+)\+(\d+)\+_____\}\}(.*?)$/', $v, $m);
		if (!empty($m[0])) {
			for ($i = $m[2]; $i <= $m[3]; $i+=$m[4]) {
				$urls[] = $m[1].$i.$m[5];
			}
		}
		else { // standard
			preg_match('/^(.+?)\{\{_____\+a\+z\+_____\}\}(.*?)$/', $v, $m);
			if (!empty($m[0])) {
				$a = ['a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z'];
				foreach ($a as $k2 => $v2) {
					$urls[] = $m[1].$v2.$m[2];
				}
			} else
				$urls[] = $v;
		}
	}
	
	/*preg_match('/^(.+?)\{\{_____\+(\d+)\+(\d+)\+_____\}\}(.*?)$/', $_POST['urls'], $m);
	if (!empty($m[0])) {
		$urls = [];
		for ($i = $m[2]; $i <= $m[3]; $i++) {
			$urls[] = $m[1].$i.$m[4];
		}
		//echo count($urls);exit;
	} else {		
		// explode URLs
		$urls = explode("\n", $_POST['urls']);
	}*/
	
	// From DB
	//  @var_array $agents_in_db
	//
	/*	id	int(10) UN PK AI
		website_url	varchar(255) 
		agent_name	varchar(255) 
		image_url	varchar(255) 
		brokerage_name	varchar(255) 
		brokerage_website	varchar(255) 
		brokerage_city	varchar(100) 
		brokerage_state	varchar(45) 
	 */
	 
	$agents_in_db = array();
	$agents_in_db__bkgWebsite__agtName = array();
	//$agents_in_db__bkgWebsite__agtName__imgUrl = array();
	//$agents_in_db__name_image_url = array();
	
	// Realtors from the specified website
	if ($_POST['domain'] != '') {
		// get all from database for url
		// is in array agent-name/image-url true/false
		$rslt = mysql_query('SELECT * FROM images WHERE website_url="'.mysql_real_escape_string($_POST['domain']).'"') or die('select all');
		while ($row = mysql_fetch_array($rslt)) {
			$agents_in_db[] = strtolower($row['agent_name'].$row['image_url']);
		}
		// All realtors in db by "brokerage_website, agent_name"
		$rslt = mysql_query('SELECT brokerage_website,agent_name FROM images') or die('select all');//echo 'a';
		while ($row = mysql_fetch_array($rslt)) {
			$agents_in_db__bkgWebsite__agtName[] = strtolower($row['brokerage_website'].$row['agent_name']); // web url + name
		}
	}
	else if ($_POST['domain'] == '') {
		// All realtors in db by "brokerage_website, agent_name, image_url"
		$rslt = mysql_query('SELECT brokerage_website,agent_name FROM images WHERE brokerage_website IS NOT NULL') or die('select all');
		while ($row = mysql_fetch_array($rslt)) {
			$agents_in_db__bkgWebsite__agtName[] = strtolower($row['brokerage_website'].$row['agent_name']); // web url + name
		}
	}
	//echo 'a';
	//else {
		//$rslt = mysql_query('SELECT * FROM images') or die('select all');
		//while ($row = mysql_fetch_array($rslt)) {
		//	$agents_in_db__website__agtname__imgUrl[] = $row['website_url'].$row['agent_name'].$row['image_url']; // web url + name
			/*array(
				'agent_name' => ,
				'image_url' => ,
				'' => ,
				'' => ,$row['agent_name'].$row['image_url'];
			);*/
		//}
	//}

	//$agent
	$regex_image_num = $_POST['regex_image_url'];
	$regex_agent_num = $_POST['regex_agent_name'];
	$regex_brokerage_name_num		= $_POST['regex_brokerage_name'];
	$regex_brokerage_website_num	= $_POST['regex_brokerage_website'];
	$regex_brokerage_website2_num	= $_POST['regex_brokerage_website2'];
	$regex_brokerage_city_num		= $_POST['regex_brokerage_city'];
	$regex_brokerage_state_num		= $_POST['regex_brokerage_state'];

	//success/fail
	$complete_success = array();
	$complete_failed  = array();

	//
	$regex_name_functions = array();
	if (isset($_POST['regex_name_functions'])) {
		if (trim($_POST['regex_name_functions']) != '') {
			$x = explode("\n", $_POST['regex_name_functions']);
			foreach ($x as $k=>$rif) {
				$rif = trim($rif);
				if ($rif == '') {
					echo 'is blank - name';
					exit;
				} else {
					$rif = json_decode($rif);
					if (!$rif || is_null($rif)) {
						echo 'is null';
					}
					array_push($regex_name_functions, $rif);
				}
			}
		}
	}
	//
	// ["/^/","http://www.coldwellbankeraction.com/images/agents/"]
	$regex_image_functions = array();
	if (isset($_POST['regex_image_functions'])) {
		if (trim($_POST['regex_image_functions']) != '') {
			$x = explode("\n", $_POST['regex_image_functions']);
			foreach ($x as $k=>$rif) {
				$rif = trim($rif);
				if ($rif == '') {
					echo 'is blank - image';
					exit;
				} else {
					$rif = json_decode($rif);
					if (!$rif || is_null($rif)) {
						echo 'is null';
						exit;
					}
					array_push($regex_image_functions, $rif);
				}
			}
		}
	}
	//
	$no_image_array = array();
	if (isset($_POST['image_no_image'])) {
		$_POST['image_no_image'] = trim($_POST['image_no_image']);
		if ($_POST['image_no_image'] != '') {
			$x = explode("\n", $_POST['image_no_image']);
			foreach ($x as $k=>$rif) {
				$rif = trim($rif);
				if ($rif == '') {
					echo 'is blank - no images';
					exit;
				} else {
					array_push($no_image_array, $rif);
				}
			}
		}
	}
	
	// total scraped
	$total_people = 0;
	// global homes.org "UPDATE" completed array
	$completed__homes_org = array();
	// homes.org multi
	$homes_org_arr = array();
	
	/**
	 * 
	 * Function scraper()
	 * 
	 */
	function scraper($rs, $url, $debug_output = false) {
		
		global $total_people;
		global $complete_success;
		global $complete_failed;
		global $regex_agent_num;
		global $regex_image_num;
		global $regex_name_functions;
		global $regex_image_functions;
		global $agents_in_db;
		global $no_image_array;
		
		global $regex_brokerage_name_num;
		global $regex_brokerage_website_num;
		global $regex_brokerage_website2_num;
		global $regex_brokerage_city_num;
		global $regex_brokerage_state_num;
		//global $agents_in_db__bkgWebsite__agtName__imgUrl;
		global $completed__homes_org;
		global $homes_org_arr;
		
		global $agents_in_db__bkgWebsite__agtName;
		
		/*echo $regex_brokerage_name_num.'<br>';
		echo $regex_brokerage_website_num.'<br>';
		echo $regex_brokerage_website2_num.'<br>';
		echo $regex_brokerage_city_num.'<br>';
		echo $regex_brokerage_state_num.'<br>';*/
		
		
		// new lines
		$rs = preg_replace('/[\r\n]/', '', $rs); // w/o this ... does not work -> /<img id="grdAgents_imgPhoto_\d*" src="([^"]+)"(?:.+?)<br>/
		
		// execute the preg
		preg_match_all($_POST['regex'], $rs, $m);
		
		if (empty($m[1])) {
			echo 'no matches at page '.$url.'<br>';
			return;
		}
		
		// here?
		if (!empty($m[1])) {
			//print_r($m);
			
			$c = 0;
			echo 'found '.count($m[1]).' matches at '.$url.'<br>';
			foreach($m[1] as $k2 => $v) {
				
				if ($regex_agent_num == '')
					$agent_name = '';
				else
					$agent_name = $m[$regex_agent_num][$c];
				
				if ($regex_image_num == '')
					$image_url = '';
				else
					$image_url = $m[$regex_image_num][$c];
				
				if ($regex_brokerage_name_num == '')
					$brokerage_name = '';
				else
					$brokerage_name = $m[$regex_brokerage_name_num][$c];
				
				if ($regex_brokerage_website_num == '')
					$brokerage_website = '';
				else
					$brokerage_website = $m[$regex_brokerage_website_num][$c];
				
				if ($regex_brokerage_website2_num == '')
					$brokerage_website2 = '';
				else
					$brokerage_website2 = $m[$regex_brokerage_website2_num][$c];
				
				if ($regex_brokerage_city_num == '')
					$brokerage_city = '';
				else
					$brokerage_city = $m[$regex_brokerage_city_num][$c];
				
				if ($regex_brokerage_state_num == '')
					$brokerage_state = '';
				else
					$brokerage_state = $m[$regex_brokerage_state_num][$c];
				
				if ($_POST['domain'] == '') {
					$listing_url = 'http://listings.homes.org/listing/printable/a/a/a/' . $m[1][$c];
				}
					
				// increment $c now!
				$c++;
				
				// perform operations on agent_name, image_url
				// perform operations on agent_name
				foreach ($regex_name_functions as $k3 => $rf) {
					$agent_name = preg_replace($rf[0], $rf[1], $agent_name);
				}
				// is no_image_array set?
				if (in_array($image_url, $no_image_array)) {
					$image_url = '';
					echo '|';
				}
				else {
					// perform operations on $image_url
					foreach ($regex_image_functions as $k3 => $rf) {
						$image_url = preg_replace($rf[0], $rf[1], $image_url);
					}
				}
				
				if ($_POST['domain'] != '') {
					// clean html entities
					$agent_name = html_entity_decode($agent_name);
					$image_url = html_entity_decode($image_url);
					
					// auto trim
					$agent_name = trim($agent_name);
					$image_url = trim($image_url);
					
					// other entity removal!
					$agent_name = preg_replace_callback('/&#([0-9a-fx]+);/mi', 'replace_num_entity', $agent_name);
					$image_url = preg_replace_callback('/&#([0-9a-fx]+);/mi', 'replace_num_entity', $image_url);
					
					//~ $brokerage_name = html_entity_decode($brokerage_name);
					//~ $brokerage_website = html_entity_decode($brokerage_website);
					//~ $brokerage_website2 = html_entity_decode($brokerage_website2);
					//~ $brokerage_city = html_entity_decode($brokerage_city);
					//~ $brokerage_state = html_entity_decode($brokerage_state);
					//~ $brokerage_name = trim($brokerage_name);
					//~ $brokerage_website = trim($brokerage_website);
					//~ $brokerage_website2 = trim($brokerage_website2);
					//~ $brokerage_city = trim($brokerage_city);
					//~ $brokerage_state = trim($brokerage_state);	
				}
				// 
				else if ($_POST['domain'] == '') {
					$listing_url = 'http://listings.homes.org/listing/printable/a/a/a/' . $m[1][$c];
				}
					//~ // cleaned (coming from AMT script.js)
					//~ // specific to homes.org scrape, cleaning
					//~ $agent_name = trim($agent_name, '"');
					//~ $brokerage_name = trim($brokerage_name, '"');
					//~ $brokerage_website = trim($brokerage_website, '"');
					//~ $brokerage_website2 = trim($brokerage_website2, '"');
					//~ $brokerage_city = trim($brokerage_city, '"');
					//~ $brokerage_state = trim($brokerage_state, '"');
					//~ //
					//~ $brokerage_name = preg_replace('/\\\/', '', $brokerage_name);
					//~ $brokerage_website = preg_replace('/\\\/', '', $brokerage_website);
					//~ $brokerage_website2 = preg_replace('/\\\/', '', $brokerage_website2);
					//~ $brokerage_city = preg_replace('/\\\/', '', $brokerage_city);
					//~ $brokerage_state = preg_replace('/\\\/', '', $brokerage_state);
					//~ //
					//~ $brokerage_website = preg_replace('/\/$/', '', $brokerage_website);
					//~ $brokerage_website = preg_replace('/^https?:\/\/(www\.|)/i', '', $brokerage_website);
					//~ //
					//~ $brokerage_website2 = preg_replace('/\/$/', '', $brokerage_website2);
					//~ $brokerage_website2 = preg_replace('/^https?:\/\/(www\.|)/i', '', $brokerage_website2);
					//~ // domain is lower case!
					//~ //b = __js_clean_html_str(b).trim().toLowerCase();
					//~ //b = b.replace('http://www.', '');
					//~ //b = b.replace(/\/$/, '');
					//~ $brokerage_website = strtolower($brokerage_website);
					//~ $brokerage_website2 = strtolower($brokerage_website2);
					//~ // nulls
					//~ $brokerage_name = preg_replace('/^null$/', '', $brokerage_name);
					//~ $brokerage_website = preg_replace('/^null$/', '', $brokerage_website);
					//~ $brokerage_website2 = preg_replace('/^null$/', '', $brokerage_website2);
					//~ $brokerage_city = preg_replace('/^null$/', '', $brokerage_city);
					//~ $brokerage_state = preg_replace('/^null$/', '', $brokerage_state);
				//~ $brokerage_name = preg_replace_callback('/&#([0-9a-fx]+);/mi', 'replace_num_entity', $brokerage_name);
				//~ $brokerage_website = preg_replace_callback('/&#([0-9a-fx]+);/mi', 'replace_num_entity', $brokerage_website);
				//~ $brokerage_website2 = preg_replace_callback('/&#([0-9a-fx]+);/mi', 'replace_num_entity', $brokerage_website2);
				//~ $brokerage_city = preg_replace_callback('/&#([0-9a-fx]+);/mi', 'replace_num_entity', $brokerage_city);
				//~ $brokerage_state = preg_replace_callback('/&#([0-9a-fx]+);/mi', 'replace_num_entity', $brokerage_state);
				
				
				// debug :: is_test
				if (isset($_POST['is_test'])) {
					if ($_POST['domain'] != '') {
						if ($_POST['is_debug']) {//is_debug
							echo '<p>'.
								'agent_name:'.htmlspecialchars($agent_name).'<br>'.
								'image_url:'.htmlspecialchars($image_url).
								'</p>';
							/*if ($_POST['domain'] == '') {
								echo "
									<p>
									bn $brokerage_name<br>
									bw $brokerage_website<br>
									bw $brokerage_website2<br>
									bc $brokerage_city<br>
									bs $brokerage_state
									</p>
								";
							}*/
						}
					}
					else {
						
						//
						// homes.org
						//
					
						// ~~
						// "TEST" + "DEBUG"
						// @param $_POST['is_debug']
						// @param $_POST['is_test']
						// ~~
						//
						// THIS WILL JUST DOWNLOAD THE PAGE!
						if ($_POST['is_debug']) {
							
							$homes_org_arr[] = $listing_url;
							
							continue;
						}
						
						// ~~
						// "TEST" ONLY
						// @param $_POST['is_test']
						// ~~
						$fn = './cache/'.str_replace('/','',basename($listing_url).md5($listing_url));
						
						// does file exist?
						if (!file_exists($fn) || filesize($fn) < 100) {
							echo 'blank<br>';
							echo $fn;
							//exit;
						} else {
							$fp = file_get_contents($fn);
							$agent_name = '';
							$bn = '';
							$bw = '';
							$bw2 = '';
							
							// SKIP ALL BUT @ SYMBOL. 2ND RUN!
							//~ if (strpos($fp, '@') === false) {
								//~ echo 'x';
								//~ continue;
							//~ }
							
							preg_match('/<span class="bold">Agent:<\/span>[\r\n\t\s]*<br \/>([^<]+)/', $fp, $agent_preg_match);
							if (!empty($agent_preg_match[1])) {
								$agent_name = $agent_preg_match[1];
								$agent_name = preg_replace_callback('/&#([0-9a-fx]+);/mi', 'replace_num_entity', $agent_name);
								$agent_name = trim(html_entity_decode($agent_name));
							}
							//~ preg_match('/<span class="bold">Provided By:</span>[\r\n\t\s]*<br \/>([^<]+)/', $fp, $m2);
							//~ if (!empty($m2[1])) {
								//~ $bn = $m2[1];
								//~ $bn = preg_replace_callback('/&#([0-9a-fx]+);/mi', 'replace_num_entity', $bn);
								//~ $bn = trim(html_entity_decode($bn));
							//~ }
							preg_match('/"BROKER_WEBSITE_CLICKED" href="([^"]+)/', $fp, $m2);
							if (!empty($m2[1])) {
								$bw = $m2[1];
								$bw = preg_replace_callback('/&#([0-9a-fx]+);/mi', 'replace_num_entity', $bw);
								$bw = trim(html_entity_decode(strtolower($bw)));
								$bw = preg_replace('/\/$/', '', $bw);
								$bw = preg_replace('/^https?:\/\/(www\.|)/', '', $bw); // i.e. script.js
							}
							//~ preg_match('/@([^ <\r\n]+)/', $fp, $m2);
							//~ if (!empty($m2[1])) {
								//~ $bw2 = $m2[1];
								//~ $bw2 = preg_replace_callback('/&#([0-9a-fx]+);/mi', 'replace_num_entity', $bw2);
								//~ $bw2 = trim(html_entity_decode(strtolower($bw2)));
								//~ $bw2 = preg_replace('/\/$/', '', $bw2);
								//~ $bw2 = preg_replace('/^https?:\/\/(www\.|)/', '', $bw2); // i.e. script.js
							//~ }
							// 
							// add @ !
							//@realestateiii.com </span>
							//~ if ($bw2 != '') {
								//~ $rslt = mysql_query('
									//~ UPDATE images 
									//~ SET
										//~ brokerage_website2="'.mysql_real_escape_string($bw2).'"
									//~ WHERE
										//~ agent_name="'.mysql_real_escape_string($agent_name).'" &&
										//~ brokerage_website="'.mysql_real_escape_string($bw).'" &&
										//~ brokerage_website2 IS NULL
									//~ LIMIT 1
								//~ ') or die('mysql :: '.mysql_error());
							//~ }
							//~ echo '.';
							//~ // CONTINUE
							//~ continue;
							
							
							$id = strtolower($bw . $agent_name . '');
							if (in_array($id, $agents_in_db__bkgWebsite__agtName)) {
								//
								echo '_';
							}
							else {
								// KEEPS website_url empty!
								if  (
									$agent_name != '' || // at least one of these is filled
									//$bn != '' ||
									$bw != ''
									)
								{
									// check if already in (in from other scripts(s) that may be running)
									// If it has been added (from another script, it must be) then
									// update the entire ids array -> $agents_in_db__bkgWebsite__agtName
									$rslt = mysql_query('
										SELECT * FROM images WHERE
											agent_name="'.mysql_real_escape_string($agent_name).'" &&
											brokerage_website="'.mysql_real_escape_string($bw).'"
										LIMIT 1
									') or die('mysql :: '.mysql_error());
									if (mysql_num_rows($rslt) > 0) {
										//
										// UPDATE THE ids ARRAY!
										//
										$agents_in_db__bkgWebsite__agtName = array();
										$rslt = mysql_query('SELECT brokerage_website,agent_name FROM images WHERE brokerage_website IS NOT NULL') or die('select all');
										while ($row = mysql_fetch_array($rslt)) {
											$agents_in_db__bkgWebsite__agtName[] = strtolower($row['brokerage_website'].$row['agent_name']); // web url + name
										}
										echo 'R';
									} else {
										mysql_query('
											INSERT INTO images
												SET
													agent_name="'.mysql_real_escape_string($agent_name).'",
													brokerage_website="'.mysql_real_escape_string($bw).'"
										') or die('mysql :: '.mysql_error()); //brokerage_name="'.mysql_real_escape_string($bn).'",
										//
										echo '.';
										// add to aidbai
										$agents_in_db__bkgWebsite__agtName[] = $id;
									}
								}
								else
									echo 'B';
							}
							
							/*// TRIM THE FILE
							// experimental
							//<span class="bold">Agent:</span>
							if (!empty($agent_preg_match[1])) {
								$pos = strpos($fp, '<span class="bold">Agent:</span>');
								if ($pos !== 0) { // already trimmed is 0
									file_put_contents($fn, substr($fp, $pos)); // TRIM
								}
							}*/
						}
					}
					
					//~ 
					//~ CONTINUE
					//~ 
					continue;
				}
				
				
				// Already in db?
				if ($_POST['domain'] != '') {
					
					// IS SPECIFIC TO THE DOMAIN!!!
					// Name/Website/Image combo already in DB?
					// Website URL is only set when an image URL
					// has been entered/
					
					//echo $agent_name . $image_url . '<br>'; // DEBUG
					
					$id = strtolower($agent_name . $image_url);
					if (in_array($id, $agents_in_db)) { //
						echo 'in';
						continue;
					}
					
					// push here
					$agents_in_db[] = $id;
					
					// increment a match here
					$total_people++;
					
					/*mysql_query('
						INSERT INTO images
						SET
							website_url="'.mysql_real_escape_string($_POST['domain']).'",
							agent_name="'.mysql_real_escape_string($agent_name).'",
							image_url="'.mysql_real_escape_string($image_url).'"
					') or die('mysql :: '.mysql_error());
					//
					echo '.';*/
					
					// insert or update
					// @param $agents_in_db__bkgWebsite__agtName
					if (in_array(strtolower($_POST['domain'] . $agent_name), $agents_in_db__bkgWebsite__agtName)) { // is in brokerage_website / homes.org scrape
						// set the website_url and image_url
						// where brokerage_website && agent_name are already set
						mysql_query('
							UPDATE images
							SET
								website_url="'.mysql_real_escape_string($_POST['domain']).'",
								image_url="'.mysql_real_escape_string($image_url).'"
							WHERE
								brokerage_website="'.mysql_real_escape_string($_POST['domain']).'" &&
								agent_name="'.mysql_real_escape_string($agent_name).'"
						') or die('mysql :: '.mysql_error());
						
						echo '_';
					}
					else {
						mysql_query('
							INSERT INTO images
							SET
								website_url="'.mysql_real_escape_string($_POST['domain']).'",
								agent_name="'.mysql_real_escape_string($agent_name).'",
								image_url="'.mysql_real_escape_string($image_url).'"
						') or die('mysql :: '.mysql_error());
						//
						echo '.';
						// add to aidbai
						$agents_in_db__bkgWebsite__agtName[] = strtolower($_POST['domain'] . $agent_name);
					}
				}
				else {
					// Domain is not set!
					// 
					// homes.org
					// http://listings.homes.org/listing/printable/a/a/a/clawca-13719905
					// @param $listing_url
					
					//$rs = getpage($listing_url);
					
					
					/*// so this is coming from homes.org
					// (and image_url is therefore blank)
					// update versus insert
					// @param $agents_in_db__bkgWebsite__agtName__imgUrl
					// update *** (update an already scraped table row)
					$id = strtolower($brokerage_website . $agent_name . $image_url);
					if (in_array($id, $agents_in_db__bkgWebsite__agtName__imgUrl)) {
						if (!in_array($id, $completed__homes_org)) { // avoid mutli-update
							mysql_query('
								UPDATE images
								SET
									brokerage_name="'.mysql_real_escape_string($brokerage_name).'",
									brokerage_website="'.mysql_real_escape_string($brokerage_website).'",
									brokerage_website2="'.mysql_real_escape_string($brokerage_website2).'",
									brokerage_city="'.mysql_real_escape_string($brokerage_city).'",
									brokerage_state="'.mysql_real_escape_string($brokerage_state).'"
								WHERE
									website_url="'.mysql_real_escape_string($brokerage_website).'" &&
									agent_name="'.mysql_real_escape_string($agent_name).'" &&
									brokerage_name="" &&
									brokerage_website="" &&
									brokerage_website2="" &&
									brokerage_city="" &&
									brokerage_state=""
							') or die('mysql :: '.mysql_error());
							echo '_';
							$completed__homes_org[] = $id;
						} else {
							echo 'x';
						}
					}
					// insert
					else {
						// KEEPS website_url empty!
						mysql_query('
						INSERT INTO images
							SET
								agent_name="'.mysql_real_escape_string($agent_name).'",
								brokerage_name="'.mysql_real_escape_string($brokerage_name).'",
								brokerage_website="'.mysql_real_escape_string($brokerage_website).'",
								brokerage_website2="'.mysql_real_escape_string($brokerage_website2).'",
								brokerage_city="'.mysql_real_escape_string($brokerage_city).'",
								brokerage_state="'.mysql_real_escape_string($brokerage_state).'"
						') or die('mysql :: '.mysql_error());
						//
						echo '.';
						// add to aidbai
						$agents_in_db__bkgWebsite__agtName__imgUrl[] = $id;
					}*/
				}
			}
			array_push($complete_success, $url);
		}
	}
	
	
	
	if (!isset($_POST['content']) || $_POST['content'] == '') {
		// foreach
		$c = 0;
		foreach ($urls as $k => $url) {
			
			$fn = './cache/'.str_replace('/','',basename($url).md5($url));
			
			if (file_exists($fn)) {
				$rs = file_get_contents($fn);
				echo ' <b>(cached)</b> ';
			}
			else {
				
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, trim($url));
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_TIMEOUT, 30);
				//curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.8.0.9) Gecko/20061206 Firefox/1.5.0.9');
				curl_setopt($ch, CURLOPT_REFERER, 'http://www.a.com/');
				curl_setopt($ch, CURLOPT_COOKIEJAR, '/var/www/html/mturk/KristinHowe/cookie.txt');
				curl_setopt($ch, CURLOPT_COOKIEJAR, '/var/www/html/mturk/KristinHowe/cookie.txt');
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
				$rs = curl_exec($ch);
				
				if (curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200) {
					echo 'http_code ('.$url.')';
					echo curl_getinfo($ch, CURLINFO_HTTP_CODE) . '<br>';
					array_push($complete_failed, $url);
					// break if test
					if (isset($_POST['is_test'])) {
						print_r(curl_getinfo($ch)) . '<br>';
						curl_close($ch);
						break;
					}
					curl_close($ch);
					continue;
				}
				
				// write
				file_put_contents($fn, $rs);
				@chmod($fn, 0777);
				
				curl_close($ch);
			}
			
			//curl_setopt($ch, CURLINFO_HEADER_OUT, true);
			//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			//'var_dump(curl_getinfo($ch,CURLINFO_HEADER_OUT));
			//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			//curl_setopt($ch, CURLOPT_VERBOSE, true);
			//curl_setopt($curlhandle, CURLOPT_VERBOSE, true);
			//$verbose = fopen('php://temp', 'rw+');
			//curl_setopt($curlhandle, CURLOPT_STDERR, $verbose);
			
			//echo $url;
			//rewind($verbose);
			//$verboseLog = stream_get_contents($verbose);
			//echo "Verbose information:\n<pre>", htmlspecialchars($verboseLog), "</pre>\n";
			//echo 'yyy';
			//echo curl_getinfo($ch, CURLINFO_HTTP_CODE) . ' ';
			//print_r(curl_getinfo($ch));
			//echo htmlspecialchars($rs);exit;
			
			// go to function
			scraper($rs, $url);
			
			//if ($c == 0)
			//	scraper($rs, $url, true);  //debug_output
			//else
			//	scraper($rs, $url, false); //debug_output
			
			// break if test
			if (isset($_POST['is_test'])) {
				//break;
			}
			
			//
			$c++;
			
			//
			if ($_POST['domain'] == '')
				echo '<br>';
			else if ($c % 20 == 0)
				echo '<br>';
				
			// HOMES.ORG
			// MULTI
			if ($_POST['domain'] == '' && count($homes_org_arr) > 10) {
				require_once('/var/www/html/curl_mult_request.php');
				$requester = new MultipleRequester();
				
				$f_arr = array();
				foreach ($homes_org_arr as $hkey => $hu) {
					$fn = './cache/'.str_replace('/','',basename($hu).md5($hu));
					
					//echo $fn;exit;
					
					/*
					 * DEBUG
					 */
					//~ if (!file_exists($fn) || filesize($fn) < 100) { // when complete remove all files where filesize is 0 bytes (or however many 1/2/3 bytes...)
						//~ echo '<br>';
						//~ echo $fn;
						//~ echo filesize($fn);
						//~ exit;
					//~ }
						
					if (!file_exists($fn) || filesize($fn) < 100) { // when complete remove all files where filesize is 0 bytes (or however many 1/2/3 bytes...)
						$requester->addGetRequest(
							$hkey,
							$hu,
							[CURLOPT_PROXY => '127.0.0.1:8123']
						);
						$f_arr[$hkey] = $hu;
						file_put_contents($fn, '');
						/*if (!file_put_contents($fn, '')) { // place empty string
							 echo 'put 1 '.$fn;
							 exit;
						}*/
						@chmod($fn, 0777);
						echo '0';
					}
				}
				
				/*
				 * DEBUG
				 */
				//$homes_org_arr = array();//reset
				//continue;
				
				while($requester->moreRemaining()) {
					$requester->execute(20); # Num to execute at a time
					echo '.';
				}
				foreach ($f_arr as $hkey => $hu) {
					if ($requester->getStatusCode($hkey) == 200) {
						$fn = './cache/'.str_replace('/','',basename($hu).md5($hu));
						//if (!file_exists($fn)) {
						file_put_contents($fn, $requester->getContent($hkey)) or die('put 2');
						array_push($complete_success, $hu);
						//}
					}
					else
						array_push($complete_failed, $hu);
				}
				
				//reset
				$homes_org_arr = array();
			}
			//echo count($homes_org_arr);
		}
	}
	if (isset($_POST['content']) && $_POST['content'] != '') {
		scraper($_POST['content'], 'STATIC');
	}
	
	




	echo 'Total people: '.$total_people.'<br>';
	echo 'Successful URLs:<br>'.implode('<br>', $complete_success);
	echo '<br>';
	echo '<br>';
	echo '<br>';
	echo 'Failed URLs:<br>'.implode('<br>', $complete_failed);
}

function getpage($url) {
	
	global $completed_failed;
	global $homes_org_arr;
	
	$fn = './cache/'.str_replace('/','',basename($url).md5($url));
			
	if (file_exists($fn)) {
		$rs = file_get_contents($fn);
		echo ' <b>(c)</b> ';
	}
	else {
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, trim($url));
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 20);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.8.0.9) Gecko/20061206 Firefox/1.5.0.9');
		//curl_setopt($ch, CURLOPT_REFERER, 'http://www.google.com/munchies');
		curl_setopt($ch, CURLOPT_COOKIEJAR, '/var/www/html/mturk/KristinHowe/cookie.txt');
		curl_setopt($ch, CURLOPT_COOKIEFILE, '/var/www/html/mturk/KristinHowe/cookie.txt');
		$rs = curl_exec($ch);
		
		if (curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200) {
			echo 'http_code ('.$url.')';
			echo curl_getinfo($ch, CURLINFO_HTTP_CODE) . '<br>';
			array_push($complete_failed, $url);
			curl_close($ch);
			
			// break if test
			if (isset($_POST['is_test'])) {
				//break;
			}
			//continue;
			
			$rs = '';
		}
		else {
			// write
			file_put_contents($fn, $rs);
			@chmod($fn, 0777);
			curl_close($ch);
		}
	}
	
	return $rs;
}



?>



</body></html>















































