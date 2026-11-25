<?php

set_time_limit(300); //5 min.
$start = time();

if (isset($_GET['u'])) {
	
	//
	// http://web.archive.org/liveweb/http://www.yelp.com/biz/taqueria-mi-pueblo-iii-novato
	//
	// "/web/20130305053333im_/ "/web/20130305053333(*)/
	// or <a href=/web/20130305055204/http:
	//
	// <!-- Start of LiveWebDisclaimer.jsp output --> ((--))
	// <!-- End of LiveWebDisclaimer.jsp output -->
	//
	// @ end file:
	/*</html>




<!--
     FILE ARCHIVED ON 5:33:33 Mac 5, 2013 AND RETRIEVED FROM THE
     INTERNET ARCHIVE ON 5:33:33 Mac 5, 2013.
     JAVASCRIPT APPENDED BY WAYBACK MACHINE, COPYRIGHT INTERNET ARCHIVE.

     ALL OTHER CONTENT MAY ALSO BE PROTECTED BY COPYRIGHT (17 U.S.C.
     SECTION 108(a)(3)).
-->*/
	//
	
	require_once('../../_redacted_curl.php');
	
	$file = './s/' . preg_replace('/\//i','',$_GET['u']);
	if ($_GET['u'] == 'undefined')
		exit;
	
	if (file_exists($file)) {
		$f = trim(file_get_contents($file));
		if ($f != '') { //done
			//file_put_contents('./mturk-amicuslabs2-log','Exists: '.$file."\n", FILE_APPEND);
			echo $f;
			exit;
		} else { // not done
			//file_put_contents('./mturk-amicuslabs2-log','Empty: '.$file."\n", FILE_APPEND);
			exit;
		}
	} else {
		file_put_contents($file, '');
		chmod($file, 0777);
		//file_put_contents('./mturk-amicuslabs2-log','New file: '.$file."\n", FILE_APPEND);
	}
	
	//
	// Browser close conn.
	//
	header("Content-Length: 0");
	header('Connection: close');
	
	$iscon=false;	
	while (!$iscon) {
			
		$_redacted_ = new _redacted_();	
		$_redacted_->launch('http://web.archive.org/liveweb/'.$_GET['u'], '', 20, false); // url, vector, timeout, cookie //.'&oe=utf-8'
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
		file_put_contents('xx-succ.html', $_GET['u'] . "\n" . $o . "\n" .$d);
		//chmod('xx-succ.html', 0777);
		
		if (
			stripos($d, "_redacted_") !== false ||
			trim($d) == '' ||
			stripos($d, '<!DOCTYPE HTML>') !== 0
		)
		{
			file_put_contents('../../turon',"1");
			//file_put_contents('cookie.txt','');
			if (time() - $start > 240) { // 4 min
			//	unlink($file);
				exit;
			}
			sleep(4);
		} else {
			file_put_contents('../../turon',"0");
			$iscon=true;
		}
	}
	
	$d = preg_replace('/<\/html>[\r\n\t\s]+<!--[^>]+>[\d\D]+$/','</html>',$d);
	//$d = preg_replace('/<!-- Start of LiveWebDisclaimer\.jsp output -->(?:.+?)<!-- End of LiveWebDisclaimer\.jsp output -->/',"",$d);
	$d = preg_replace('/\/web\/\d{12,20}[\w_]{0,4}\//','',$d);
	
	file_put_contents($file, $d);
	
	chmod($file, 0777);
    exit;
}
/*else if (isset($_GET['u']) && isset($_GET['id']))
{
	$id = $_GET['id'];
	$file = 'mturk-amicuslabs2/' . preg_replace('/\//i','',$_GET['u']);
	
	if (file_exists($file)) {
		$x=file_get_contents($file);
		if ($x != '') {
			echo $_GET['id'] . $x;
			$y=file_get_contents('./mturk-amicuslabs2-log');
			if (strpos($y, $file) === false)
				file_put_contents('./mturk-amicuslabs2-log','Exists !!: '.$file."\n", FILE_APPEND);
		}
	}
	exit;
}*/
?>
