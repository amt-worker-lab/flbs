<?php

set_time_limit(500);


if (isset($_GET['u']) && !isset($_GET['v'])) { // init

	file_put_contents('./queue', $_GET['u']."\n", FILE_APPEND);
	
}
else if (isset($_GET['u']) && isset($_GET['v'])) { // read done
	
	$file = str_replace('/', '', $_GET['u']);
	
	$file = './s/'.$file;
	
	if (file_exists($file))
	{
		echo file_get_contents($file);
	}
	
}
else if (isset($_POST['u2']) && isset($_POST['v2'])) { // save done
	
	$file = str_replace('/', '', $_POST['u2']);
	
	$file = './s/' . $file;
	
	file_put_contents($file, $_POST['v2']);
	
	chmod($file, 0777);
	
	//file_put_contents($file, $_GET['v2']."\n", FILE_APPEND);
	
}
else if (isset($_GET['u2']) && !isset($_GET['v2'])) { // next in queue

	$file = './queue';
	
	$f = file_get_contents($file);
	
	preg_match('/^([^\n]+)\n/', $f, $m);
	
	if (!empty($m[1])) {
		
		$f = preg_replace('/'.preg_quote($m[1]).'\n/', '', $f);
		//$f = str_replace($m[1], '', $f);
		
		file_put_contents($file, $f);
		
		echo trim($m[1]);
		
	}
	
}
exit;
























?>
