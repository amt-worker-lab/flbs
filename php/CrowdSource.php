<?php

set_time_limit(600); //10 min.
$start = time();

if ( isset($_GET['u']) && !isset($_GET['id']) ) {
	
	require_once('../../_redacted_curl.php');
	
	$file = './s/' . preg_replace('/\//i','',$_GET['u']);
	
	if ($_GET['u'] == 'undefined')
		exit;
	
	if (file_exists($file)) {
		if (file_get_contents($file) != '') {
			exit;
		} else {
		}
	} else {
		file_put_contents($file, '');
		chmod($file, 0777);
	}
	
	//
	// kills
	//
	header("Connection: close");
	ob_start();
	phpinfo();
	$size=ob_get_length();
	header("Content-Length: $size");
	ob_end_flush();
	flush();
	//echo '2';exit;
	
	//include '/var/www/html/proxychains.php';
	//run($url_to_retrieve, $file_to_save, $proxy_files_array = null, $catch_include = null) { // 
	//run($_GET['u'], $file, null, null);
	//exit;
	
	$iscon = false;	
	while (!$iscon) {
			
		$$_redacted_ = new _redacted_();	
		$$_redacted_->launch($_GET['u'], '', 30, null); // url, vec$_redacted_, timeout, cookie //.'&oe=utf-8'
		
		$data = $$_redacted_->get_redacted_Data();
		$d = $data['return'];
		
		//print_r($data['info']);
		
		$o='';
		ob_start();
		print_r($data['info']);
		$o=ob_get_contents();
		ob_end_clean();
		file_put_contents('./xx-succ2.html', $_GET['u'] . "\n" . $o . "\n" .$d);
		chmod('./xx-succ2.html', 0777);
		
		if (stripos($d, "_redacted_") !== false) {
			file_put_contents('../../turon',"1");
			if (time() - $start > 540) { // 9 min
				unlink($file);
				exit;
			}
			sleep(4);
		}
		else if
			(
				stripos($d, "_redacted_") !== false ||
				stripos($d, "_redacted_") !== false ||
				stripos($d, "_redacted_") !== false ||
				stripos($d, "_redacted_") !== false
			)
		{
			file_put_contents('../../turon',"1");
			if (time() - $start > 540) { // 
				unlink($file);
				exit;
			}
			sleep(2);
		} else if (stripos($d, "<!doctype html>") !== false) {
			file_put_contents('../../turon',"0");
			$iscon=true;
		} else {
			file_put_contents('../../turon',"1");
			if (time() - $start > 540) { // 
				unlink($file);
				exit;
			}
			sleep(2);
		}
		
		// continue?
		if (file_get_contents($file) != '') {
			exit;
		}
	}
	file_put_contents($file, $d);
	
	chmod($file, 0777);
    exit;
}
else if (isset($_GET['u']) && isset($_GET['id']))
{
	$id = $_GET['id'];
	$file = './s/' . preg_replace('/\//i','',$_GET['u']);
	
	if (file_exists($file)) {
		$x=file_get_contents($file);
		if ($x != '') {
			echo $_GET['id'] . $x;
		}
	}
	exit;
}
?>
