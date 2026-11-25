<?php

set_time_limit(600); //10 min.
$start = time();
error_reporting(E_ALL ^ E_NOTICE); ini_set('display_errors', 1); ini_set('display_startup_errors', 1);
//$home = '192.168.1.69';
//~ $home = '192.168.1.86';
$home = '192.168.1.117';

// From the command line......
//
//
if (isset($argv[1])) {
	$_GET['u'] = $argv[1];
	if (isset($argv[2]))
		$_GET['catch_include'] = $argv[2];
	preg_match('/(q=[^&]+)/', $_GET['u'], $x);
	$_GET['short_filename'] = $x[1];
	
	$_GET['__ARGV__'] = 1;
}


// URL HAS short_filename!
// This will make it so that even when "u" is a different URL,
// that DOES resolve to the same end point,
// the proxy will only initiate a request for one "short_filename".
// I.e. "yahoo.com/biz/&randomXXXXX=1111111" and "yahoo.com/biz/&randomXXXXX=222222222"
// both resolving to "yahoo.com/biz/". Load this with "short_filename=yahoo.com/biz/"
// to enable this feature.

// Added shared cache Apr. 18 !
//
//
if (isset($_GET['u'])) {
	
	require_once('/var/www/html/_redacted_curl.php');
	
	if (isset($_GET['short_filename'])) {
		$x = $_GET['short_filename'];
	} else
		$x = $_GET['u'];
	
	$md5 = md5($x);
	$file = '/var/www/html/mturk/.cached/' . preg_replace('/\//i', '', $md5);
	$file2= 'http://'.$home.'/mturk/.cached/' . preg_replace('/\//i', '', $md5);
	print_r($md5);
	// ...
	if ($_GET['u'] == 'undefined' || trim($_GET['u']) == '')
		exit;
	print_r('x');
	// File exists?
	if  (
		file_exists($file) ||
		server_fe($file2)
		)
	{
		if  (
			file_get_contents($file)  != '' ||
			file_get_contents($file2) != ''
			)
		{
			if (!isset($_GET['get_page_debug']))
				exit;
		} else { // CONTINUES !!
		}
	} else {
		file_put_contents($file, '');
		chmod($file, 0777);
	}
  print_r('x');
	
	// Kill browser session
	//
	//
	if (!isset($_GET['get_page_debug'])) {
		header("Connection: close");
		ob_start();
		phpinfo();
		$size=ob_get_length();
		header("Content-Length: $size");
		ob_end_flush();
		flush();
	}
	
	// use_cookie_file
	// Default=no, but if set then always True.
	if (!isset($_GET['use_cookie_file'])) {
		$_GET['use_cookie_file'] = null;
	} else {
		$_GET['use_cookie_file'] = true;
	}
	//
	if (!isset($_GET['catch_success'])) {
		$_GET['catch_success'] = '<!doctype html';
		//$_GET['catch_success'] = '';
	}
	// Single item to check
	if (!isset($_GET['catch_include'])) {
		$_GET['catch_include'] = '';
	}
	// Multiple items to check
	// success_array, include_array: appear synonymous
	if (isset($_GET['catch_success_array'])) {
		$_GET['catch_success_array'] = json_decode($_GET['catch_success_array']);
	} else $_GET['catch_success_array'] = [];
	if (isset($_GET['catch_include_array'])) {
		$catch_include_array = json_decode($_GET['catch_include_array']);
	} else {
		$catch_include_array = [];
	}
	
	// ex: array ("Content-Type: text/xml; charset=\"utf-8\"","Accept: text/xml")
	$get_header_array = null;
	if (isset($_GET['get_header_array'])) {
		$get_header_array = json_decode($_GET['get_header_array']);
	}
	
	// ex: object {'key': value}
	// Is an object. However, this needs to be changed to
	// an ASSOCIATIVE array.
	$get_post_array = null;
	if (isset($_GET['get_post_array'])) {
		$tmp = json_decode($_GET['get_post_array']);
		foreach ($tmp as $key => $value) {
			$get_post_array[$key] = $value;
		}
	}
	
	//print_r($catch_include_array);
	
	$iscon = false;
	while (!$iscon) {
		
		// EXIT IF COMPLETE
		// EITHER HERE OR ELSEWHERE
		if  (
			!file_exists($file) || // File removed by other process
			file_get_contents($file)  != '' ||	// File written to by other process
			(
				server_fe($file2) && trim(file_get_contents($file2)) != '' // File exists on server and is non-blank
			)
			)
		{
      print_r('debug:exit--');
			exit;
		}
		
		/**
		 * launch($url, $vector = null, $timeout = null, $cookie = null, $post_array = null, $header_array = null)
		 */
		$_redacted_ = new _redacted_();
		// url, vector, timeout, cookie, post array, header array //.'&oe=utf-8'
		$_redacted_->launch($_GET['u'], '', 24, $_GET['use_cookie_file'],
			$get_post_array, $get_header_array);
		
		$data = $_redacted_->get_redacted_Data();
		$d = $data['return'];
		
		// DEBUG
		$debug_message = '';
		ob_start();
		print_r($data);
		print_r($get_post_array);
		print_r($get_header_array);
		$debug_message = ob_get_contents();
		ob_end_clean();
		file_put_contents('./xx-succ2.html', $_GET['u'] . "\n" . $debug_message . "\n" .$d);
		@chmod('./xx-succ2.html', 0777);
		// END DEBUG
		
		// catch_success_array_bool
		$catch_success_array_bool = false;
		if  (isset($catch_success_array)) {
			foreach ($catch_success_array as $key => $csa) {
				if (stripos($d, $csa) !== false) {
					$catch_success_array_bool = true;
					break;
				}
			}
		}
		
		// catch_include_array_bool
		$catch_include_array_bool = false;
		if  (isset($catch_include_array)) {
			foreach ($catch_include_array as $key => $cia) {
				if (stripos($d, $cia) !== false) {
					$catch_include_array_bool = true;
					break;
				}
			}
		}
		if (isset($_GET['get_page_debug'])) {
			//echo 'd:'.$d;
			echo 'catch_include_bool' . $catch_include_array_bool;
			echo 'exit';
			//~ exit;
		}
		
		
		// If unsuccessful, then try again on a new proxy.
		if
			(
				$catch_include_array_bool ||
				
				stripos($d, "_redacted_") !== false ||
				stripos($d, "_redacted_") !== false ||
				stripos($d, "_redacted_") !== false ||
				stripos($d, "_redacted_") !== false ||
				stripos($d, "_redacted_") !== false ||
				stripos($d, $_GET['catch_include']) === false // ||
				
				
			)
		{
			echo 'err';
			file_put_contents('/var/www/html/turon',"1");
			//file_put_contents('/var/www/html/cookie.txt',"");
			if (time() - $start > 300) { // 
				unlink($file);
				exit;
			}
			sleep(1);//exit;
		} else if (
			stripos($d, $_GET['catch_success']) !== false ||
			$catch_success_array_bool
		) {
			echo 'succ';
			file_put_contents('/var/www/html/turon',"0");
			$iscon = true;
		} else {
			echo 'err2';
			file_put_contents('/var/www/html/turon',"1");
			//file_put_contents('/var/www/html/cookie.txt',"");
			if (time() - $start > 300) { // 
				// Remove the file if it exists.
				@unlink($file);
				exit;
			}
			// Update "bad" results file
			file_put_contents('./failed-attempt.log', $_GET['u'] . "\n" . $debug_message . "\n" .$d);
			@chmod('./xx-succ2.html', 0777);
			// Wait
			sleep(1);
		}
	}
	
	file_put_contents($file, $d);
	
	@chmod($file, 0777);
	
    exit;
}
else if (isset($_GET['v']))
{
	$md5 = md5($_GET['v']);
	
	$file = '/var/www/html/mturk/.cached/' . preg_replace('/\//i', '', $md5); // apr18
	$file2= 'http://'.$home.'/mturk/.cached/' . preg_replace('/\//i', '', $md5); // apr18
	
	// Try local file
	if (file_exists($file) && trim(file_get_contents($file)) != '' ) {
		$x = file_get_contents($file);
		if ($x != '') {
			echo $x;
			exit;
		}
	}
	// Try get_headers
	if (server_fe($file2)) {
		// File exists
		$x = file_get_contents($file2);
		if (trim($x) != '') {
			echo $x;
			exit;
		}
	}
	
	exit;
}
// file exists server
function server_fe($file) {
	$file_headers = @get_headers($file);
// 	print_r($file);
//	print_r(get_headers($file)); //#file_headers);
	if ($file_headers[0] != 'HTTP/1.1 404 Not Found') {
		// File exists
		return true;
	} else {
		// File does not exist
		return false;
	}
}
?>
