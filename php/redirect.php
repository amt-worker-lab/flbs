<?php
#print_r($_GET);
#print_r($_SERVER);
// $_SERVER['REQUEST_URI']
//~ echo 'xx';exit;
if (isset($_GET['google_news'])) {
	preg_match('/url=([^&]+)/', $_SERVER['REQUEST_URI'], $url); // /news/url?{...}
	//print_r($url);
	//echo $url[1];exit;
	chdir('./mturk/');
	if ($url && $url[1]) {
		$_REQUEST['AMT__output_html_from_URL'] = urldecode( $url[1] );
		$_REQUEST['AMT__output_html_from_URL__options'] = '{"_redacted__use_proxy":true, "do_not_save_to_file":true}'; //
	} else { // ^news.google.com$
		$_REQUEST['AMT__output_html_from_URL'] = urldecode( 'http://news.google.com' );
		$_REQUEST['AMT__output_html_from_URL__options'] = '{"do_not_save_to_file":true}'; //
		//~ $_REQUEST['AMT__output_html_from_URL__options'] = '{"_redacted__use_proxy":true, "do_not_save_to_file":true}'; //
	}
	include './util.php';
	exit;
	
}
?>
