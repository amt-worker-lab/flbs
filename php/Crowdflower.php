<?php

set_time_limit(600); //10 min.
$start = time();

/*
 * Ryanair
 * 
 * 
 */
if (isset($_GET['ryanair'])) {
	
	if (isset($_GET['reload_write'])) {
		if ($_GET['reload_write'] == 1 && file_get_contents('ryanair') == 2) //already waiting for input
			exit;
		file_put_contents('./ryanair', $_GET['reload_write']);
	} else if (isset($_GET['reload_check_status'])) {
		echo file_get_contents('./ryanair');
	}
	/*if (isset($_GET['u'])) {
		if ($_GET['u'] == 'reload_write') {
			file_put_contents('./ryanair', '1');
		}
		else if ($_GET['u'] == 'reload2') {
			file_put_contents('./ryanair', '2');
		}
		else if ($_GET['u'] == 'reload_is_complete') {
			file_put_contents('./ryanair', '');
		}
		else if ($_GET['u'] == 'reload_check_status') {
			echo file_get_contents('./ryanair');
			//if (file_get_contents('./ryanair') == '') {
			//	echo '1';
			//}
		}
	}*/
	
	exit;
}





if ( isset($_GET['u']) && !isset($_GET['id']) ) {
	
	require_once('../../_redacted_curl.php');
	
	$file = './s/' . preg_replace('/\//i','',$_GET['u']);
	
	if ($_GET['u'] == 'undefined')
		exit;
	
	if (file_exists($file)) {
		if (file_get_contents($file) != '') {
			//file_put_contents('./mturk-amicuslabs2-log','Exists: '.$file."\n", FILE_APPEND);
			exit;
		} else {
			//file_put_contents('./mturk-amicuslabs2-log','Empty: '.$file."\n", FILE_APPEND);
		}
	} else {
		file_put_contents($file, '');
		chmod($file, 0777);
		//file_put_contents('./mturk-amicuslabs2-log','New file: '.$file."\n", FILE_APPEND);
	}
	
	//
	//
	//
	header("Content-Length: 0");
	header('Connection: close');

	$iscon = false;
	$w = 'http://web.archive.org/liveweb/';
	while ( !$iscon ) {
			
		$_redacted_ = new _redacted_();	
		$_redacted_->launch($w . $_GET['u'], '', 12, true); // url, vector, timeout, cookie //.'&oe=utf-8'
		//$_redacted_->launch($_GET['u'].'&hl=en', '', 14, true); // url, vector, timeout, cookie //.'&oe=utf-8'
		#$_redacted_->launch($_GET['u'].'&hl=en', '', 30, true); // url, vector, timeout, cookie //.'&oe=utf-8'
		//$_redacted_->launch($_GET['u'], '', 30, true); 
		
		$data = $_redacted_->get_redacted_Data();
		$d=$data['return'];
		
		$o='';
		ob_start();
		print_r($data['info']);
		$o=ob_get_contents();
		ob_end_clean();
		file_put_contents('xx-succ2.html', $_GET['u'] . "\n" . $o . "\n" .$d);
		chmod('xx-succ.html', 0777);
		
		$w = '';
		
		if (stripos($d, "_redacted_") !== false) {
			file_put_contents('../../turon',"1");
			//file_put_contents('cookie.txt','');
			if (time() - $start > 540) { // 9 min
				unlink($file);
				exit;
			}
			sleep(2);
		} else if (stripos($d, "_redacted_") !== false
		||	stripos($d, "_redacted_") !== false)
		{
			file_put_contents('../../turon',"1");
			//file_put_contents('cookie.txt','');
			if (time() - $start > 540) { // 
				unlink($file);
				exit;
			}
			sleep(2);
		} else if (strpos($d, "<!doctype html>") !== false) {
			file_put_contents('../../turon',"0");
			$iscon=true;
		} else {
			file_put_contents('../../turon',"1");
			//file_put_contents('cookie.txt','');
			if (time() - $start > 540) { // 
				unlink($file);
				exit;
			}
			sleep(2);
		}
	}
	
	$d = preg_replace('/<\/html>[\r\n\t\s]+<!--[^>]+>[\d\D]+$/','</html>',$d);
	//$d = preg_replace('/<!-- Start of LiveWebDisclaimer\.jsp output -->(?:.+?)<!-- End of LiveWebDisclaimer\.jsp output -->/',"",$d);
	$d = preg_replace('/["=]{1}\/web\/\d{12,20}[\w_]{0,4}\//','',$d);
			
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
			//$y=file_get_contents('./mturk-amicuslabs2-log');
			//if (strpos($y, $file) === false)
			//	file_put_contents('./mturk-amicuslabs2-log','Exists !!: '.$file."\n", FILE_APPEND);
		}
	}
	exit;
}
?>
