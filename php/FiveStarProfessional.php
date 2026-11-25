<?php

set_time_limit(500);

error_reporting(E_ALL ^ E_NOTICE); ini_set('display_errors', 1); ini_set('display_startup_errors', 1);


if (isset($_GET['complete'])) { // read done //==b
	
	$file = str_replace('/', '', urldecode($_GET['complete']));
	
	$file = './s/'.$file;
	
	if (file_exists($file))
	{
		echo file_get_contents($file);
	}
	
}
else if (isset($_GET['write']) && isset($_GET['id'])) { // save license type
	
	// This overwrites any existing file!
	
	$file = str_replace('/', '', $_GET['id']);
	
	$file = './s/' . $file;
	
	file_put_contents($file, $_GET['write']);
	
	chmod($file, 0777);
}
else if (isset($_POST['write']) && isset($_POST['id'])) { // save done
	
	// Save 1
	//  If file == '', 
	// file_get_contents == 'SEC', 'FINRA', or 'DUAL'
	// save 1
	//  file_put_contents(sec or broker)
	// save 2
	//  file_put_contents(broker)
	// save 3
	//  multiple results exist.
	
	$file = str_replace('/', '', $_POST['id']);
	
	$file = './s/' . $file;
	
	
	// SAVE A SEARCH WHERE MULTIPLE IS RETURNED. FILE DOES NOT EXIST. $_GET['write'/'id'] above has yet to be called.
	// For example, "Bob Smith". File becomes "./Bob Smith". On a subsequent run the ID will
	// be changed to a number, if correct user is found, and a different file is written to.
	// For example, if "Bob Smith" is ID 123456789, "./Bob Smith" is left for future use,
	// while the current file process becomes "./123456789".
	if (!file_exists($file)) {
		file_put_contents($file, $_POST['write']); // this will save .id, .multiple, if multiple results
		chmod($file, 0777);
		exit;
	}
	
	$f = file_get_contents($file);
	
	if ($f != '') {
		// multiple
		//if ($f != 'SEC' && $f != 'FINRA' && $f != 'DUAL') { // first write to file after initial write
		//	$obj = json_decode($_POST['write']);
		//}
		//else 
		if ($f == 'SEC' || $f == 'FINRA' || $f == 'DUAL') { // first write to file after initial write
			
			$obj = json_decode($_POST['write']);
			
			$obj->lic_type = $f;
			
			// Set both sites as being not active. This will be updated on subsequent
			// runs.
			if (!isset($obj->not_active_SEC)) {
				$obj->not_active_SEC = true;
			} else if (!isset($obj->not_active_FINRA)) {
				$obj->not_active_FINRA = true;
			}
		}
		else {  // second write to file (if necessary)
				// overwrite if second exists (finra)
				// all but name
			$obj = json_decode($f);
			$obj_post = json_decode($_POST['write']);
			
			foreach ($obj_post as $k => $v) { //
				if ($k == 'name') { // name
					if ($obj->name == '')
						$obj->name = $obj_post->name;
					continue;
				}
				//if ($k == 'hasResult') continue; //skip here
				
				if  (
						($k == 'comp' || $k == 'compType') &&
						$obj->comp != '' // company exists!
					)
				{
					continue; ////
				}
				$obj->$k = $v; // all else
			}
		}
		// Set .multiple to blank. .multiple is for scraping the results of a search where
		// more than one broker is returned.
		// THIS SEEMS UNNECESSARY TO BE REMOVE .multiple. Many instance of .mupltiple
		//	// could occur, in which case after the first instance all others would only ever get to see "".
		// 		
		/*$obj->multiple = '';*/ 
		file_put_contents($file, json_encode($obj));
	} else {
		file_put_contents($file, $_POST['write']); // (...this ought to never happen)
	}
	chmod($file, 0777);
				//$lic_type = $obj->lic_type;
				//if (isset($obj->not_active_SEC) && !isset($obj_post->not_active_SEC)) { // allow overwrite if requested
				//	
				//}
				/*if (isset($obj_post->not_active_SEC)) // allow overwrite if requested
					$not_active_SEC = $obj_post->not_active_SEC;
				else if (isset($obj->not_active_SEC))
					$not_active_SEC = $obj->not_active_SEC;
				else
					$not_active_SEC = false;*/
				//$obj = json_decode($_POST['write']);
				//$obj->lic_type = $lic_type;
				//$obj->not_active_SEC = $not_active_SEC;
}
else if (isset($_GET['appendQueue'])) { // init //==b
	
	// is it already complete?
	// XXis it in queue already?
	$file = str_replace('/', '', urldecode($_GET['appendQueue'])); // "FNAME LNAME" from script not "FNAME+LNAME"
	$file = './s/'.$file;
	if (file_exists($file))
	{
		/*$x = file_get_contents($file);
		if (str_len($x) < 6) { // FINRA,SEC,DUAL,""
			// re-add
			$q = file_get_contents('./queue');
			if (strpos($q, $_GET['appendQueue']) !== false) {
				exit;
			} else {
				// continue!
			}
		} else
			exit;*/
		exit; // exit!
	}// else {
	
	$f = file_get_contents('./queue');
	
	// if is in file in different location, add to top
	// else add to bottom
	
	if (strpos($f, $_GET['appendQueue']."\n") !== false) {
		$f = str_replace($_GET['appendQueue']."\n", '', $f);
		$f = $_GET['appendQueue']."\n".$f; // add at first
	}
	else {
		$f.= $_GET['appendQueue']."\n"; // add at last
	}
	
	file_put_contents('./queue', $f, LOCK_EX);
	
	// first
	
	// last
	//
	
	
	//file_put_contents('./queue', $_GET['appendQueue']."\n", FILE_APPEND|LOCK_EX);
	//}
	
}
else if (isset($_GET['readQueue'])) { // next in queue

	$file = './queue';
	
	$f = trim(file_get_contents($file));

	if ($f != '') {
		
		$f = explode("\n", $f);
		
		if (count($f) > 0) {
			$x = array_shift($f); // first element
			$f = implode("\n", $f) . "\n"; // add \n for elements still in queue file
			
			file_put_contents($file, $f, LOCK_EX);
			
			echo trim($x);
		}
	}

	/* http://stackoverflow.com/questions/4314503/any-faster-simpler-alternative-to-php-preg-match
	 * "I ran a quick benchmark comparing regex to hash tables in this scenario. To run it with regex 1000 times, it took 17 seconds. To run it with a hash table 1000 times, it took 0.4 seconds. It should be an O(n+m) process."
	 * 
	preg_match('/^([^\n]+)/', $f, $m);
	
	if (!empty($m[1])) {
		
		//$f = preg_replace('/'.preg_quote($m[1]).'(\n|$)/', '', $f);
		$f = str_replace($m[1], '', $f);
		
		file_put_contents($file, $f."\n", LOCK_EX); // add \n for elements still in queue file
		
		echo trim($m[1]);
		
	}
	*/
	
}
else if (isset($_GET['ct__last'])) { //ct
	
	$f = file_get_contents('Real_Estate_Salespersons.csv');
	
	$g = preg_quote($_GET['ct__last']);
	$h = (isset($_GET['ct__first'])) ? preg_quote($_GET['ct__first']) : '[^,]+';
	
	$s = '/'.PHP_EOL.$h.','.$g.',[^'.PHP_EOL.']+/';
	//$s = '/'.PHP_EOL.'[^,]+,[^,]+,[^,]+,[^,]+,[^,]+,[^,]+,[^\.]+\.0?'.$g.'[^'.PHP_EOL.']+/';
	
	preg_match($s, $f, $m);
	
	if (!empty($m)) {
		echo trim($m[0]);
	}
}
else if (isset($_GET['ct__first'])) { //ct
	
	$f = file_get_contents('Real_Estate_Salespersons.csv');
	
	$h = preg_quote($_GET['ct__first']);
	//$g = (isset($_GET['ct__last'])) ? preg_quote($_GET['ct__last']) : '[^,]+';
	
	$s = '/'.PHP_EOL.$h.',[^'.PHP_EOL.']+/';
	
	//$s = '/'.PHP_EOL.'[^,]+,[^,]+,[^,]+,[^,]+,[^,]+,[^,]+,[^\.]+\.0?'.$g.'[^'.PHP_EOL.']+/';
	
	preg_match($s, $f, $m);
	
	if (!empty($m)) {
		echo trim($m[0]);
	}
}
else if (isset($_GET['ct__num'])) { //ct
	
	$f = file_get_contents('Real_Estate_Salespersons.csv');
	
	$g = preg_quote($_GET['ct__num']);
	
	$s = '/'.PHP_EOL.'[^,]+,[^,]+,[^,]+,[^,]+,[^,]+,[^,]+,[^\.]+\.0?'.$g.'[^'.PHP_EOL.']+/';
	
	
	preg_match($s, $f, $m);
	
	if (!empty($m)) {
		echo trim($m[0]);
	}
	
	
	/**
	 * 
	 * 
	 * 
	 *
	 
	//fields
	var x = document.getElementsByClassName('collapsePanel');var y;for (var i in x) { y = x[i].getElementsByTagName('input'); for (var j in y) { y[j].checked = true; }; };
	//csv
	var x = document.getElementsByTagName('input'); for (var i in x) { if (x[i].value == 'Comma') x[i].checked = true; };
	var x = document.getElementsByTagName('input'); for (var i in x) { if (x[i].type == 'submit') x[i].type = 'button'; };
	window.onbeforeunload = function() { return false; };
	//
	var x = document.getElementsByTagName('input'); var c = 0;
	var xint=setInterval(function() {
		if (!x[c]) { clearInterval(xint); };
		if (x[c].value == 'Download') { x[c].click(); }; c++;
	}, 10);
	 
	 * 
	 */
}

exit;
























?>
