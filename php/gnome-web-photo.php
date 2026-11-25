<?php

//set_time_limit(300); //5 min.
//set_time_limit(800); //10 min.

//echo str_replace('"', '\"', 'xy"z');exit;


if (isset($argv[1])) {
	$_GET['u'] = $argv[1];
}

if (isset($_GET['u'])) {
	
	$url = str_replace('/"/', '\"', $_GET['u']);
	
	$f = '/var/www/html/mturk/.scripts/s/' . preg_replace('/\//', '', $url);
	
	if (!file_exists($f)) {
		
		$opts = '--mode=photo --timeout=12 --force --width=1024 --disable-plugins';
		//$opts = '--mode=photo --timeout=12 --force --width=1024 --disable-plugins --file="'.$f.'"';
		//$opts = '--mode=photo --timeout=12 --force --width=1024 --print-background --disable-plugins --file="'.$f.'"';
		
		//echo $opts;
		
		exec('gnome-web-thumbnail '.$opts.' "'.$url.'" "'.$f.'" &>/dev/null');
		//exec('gnome-web-thumbnail '.$opts.' "'.$url.'" &>/dev/null');
		
	}
	
}

?>
