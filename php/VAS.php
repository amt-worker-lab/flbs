<?php

set_time_limit(800);

if (
	isset($_GET['u']) ||
	isset($argv[1])
) {
	
	if (isset($_GET['u'])) { // exit browser
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
	} else {
		$_GET['u'] = $argv[1];
	}
	
	include '/var/www/html/proxychains.php';
	
	
	$file = '/var/www/html/mturk/VAS/s/' . preg_replace('/\//i', '', $_GET['u']);
	
	$proxies = array(
		//'/var/www/html/proxies/_reliable_list-4-11.txt',
		//'/var/www/html/proxies/_reliable_list-4-10.txt'
		//'/var/www/html/proxies/'
	);
	
	// run url, outfile, array(proxy files)
	run($_GET['u'], $file, $proxies);
}
else if (isset($_GET['v']))
{
	$file = '/var/www/html/mturk/VAS/s/' . preg_replace('/\//i', '', $_GET['v']);
	
	if (file_exists($file)) {
		$x = file_get_contents($file);
		if ($x != '') {
			sleep(1); // ensure fully written
			$x = file_get_contents($file);
			echo $x;
		}
	}
}

exit;

?>
