<?php

//set_time_limit(300); //5 min.
//set_time_limit(800); //10 min.

//echo str_replace('"', '\"', 'xy"z');exit;


if (isset($argv[1])) {
	//$_GET['u'] = $argv[1];
	//echo $_GET['u'];
	
	$continue=true;
	while ($continue) {
		/*delete
		$dir = '/var/www/html/mturk/.scripts/gnome-web-thumbnail/';
		$iterator = new DirectoryIterator($dir . '.queue/');
		foreach ($iterator as $fileinfo) {
			if  (
					$fileinfo->isFile() &&
				)
			{
				if (time() - $fileinfo->getMTime() > 900) { //7.5
					$f = $fileinfo->getFilename();
					rename('/var/www/html/mturk-trevor-faden/done/'.$f, './mturk-trevor-faden/remove/'.$f);
					chmod('./mturk-trevor-faden/remove/'.$f, 0777);
				}
				//echo $fileinfo->getFilename()."\n";
				//$filename = $fileinfo->getFilename();
				//break;
			}
		}*/

		$dir = '/var/www/html/mturk/.scripts/gnome-web-thumbnail/';
		$filename = '';
		$iterator = new DirectoryIterator($dir . '.queue/');
		$files = array();
		foreach ($iterator as $fileinfo) {
			if  (
					$fileinfo->isFile()
				)
			{
				$filename = $fileinfo->getFilename();
				$mtime = $fileinfo->getMTime();
				$files[$mtime] = $filename;
			}
		}
		
		if (count($files) === 0) {
			echo '.';
			sleep(1);
			continue;
		} else
			ksort($files); // sort by mtime // sorts ascending?
		
		foreach ($files as $key => $value) {
			$filename = $value;
			break;
		}
		$l_url = $dir . '.queue/' . $filename;
		$url = file_get_contents($l_url);
		unlink($l_url);
		
		$filename	= str_replace('/"/', '\"', $filename);
		$url		= str_replace('/"/', '\"', $url);
		
		$l_url = $dir . 's/' . $filename . '.jpg';
		echo $filename."\n";
		
		//$opts = '--mode=photo --timeout=10 --width=1024 --disable-plugins';
		$opts = '--mode=photo --timeout=10 --force --width=900 --disable-plugins';

		
		$cmd = '/usr/bin/gnome-web-thumbnail '.$opts.' "'.$url.'" "'.$l_url.'" &';
		//$cmd = 'gnome-web-thumbnail '.$opts.' "'.$url.'" "'.$l_url.'" &>2 /dev/null';
		//$cmd = 'gnome-web-thumbnail '.$opts.' '.$url.' '.$f;
		//$cmd = 'gnome-web-thumbnail '.$opts.' "'.$url.'" "'.$f.'" && chmod 777 "'.$f.'"';
		//echo $cmd;
		
		echo shell_exec($cmd);
		
	}
	exit;
}

else if (isset($_GET['u'])) {
	
	$u = str_replace('/"/', '\"', $_GET['u']);
	
	$f1 = '/var/www/html/mturk/.scripts/gnome-web-thumbnail/s/' . preg_replace('/\//', '', $u) . '.jpg';
	$f2 = '/var/www/html/mturk/.scripts/gnome-web-thumbnail/.queue/' . preg_replace('/\//', '', $u);
	
	if (!file_exists($f1) && !file_exists($f2)) {
		file_put_contents($f2, $_GET['u']);
	}
		
		/*//
		// kills
		//
		header("Connection: close");
		ob_start();
		phpinfo();
		$size=ob_get_length();
		header("Content-Length: $size");
		ob_end_flush();
		flush();
	
		$opts = '--mode=photo --timeout=10 --force --width=900 --disable-plugins';
		
		$cmd = '/usr/bin/gnome-web-thumbnail '.$opts.' "'.$url.'" "'.$f.'"';
		echo $cmd;
		echo shell_exec($cmd);
		//exec('sudo -u _redacted_ gnome-web-thumbnail '.$opts.' "'.$url.'" "'.$f.'" && chmod 777 "'.$f.'"', $opts);*/
	
}
else if (isset($_GET['v'])) {
	$f = '/var/www/html/mturk/.scripts/gnome-web-thumbnail/s/' . preg_replace('/\//', '', $_GET['v']);
	$f.= '.jpg';
	//echo $f;
	if (file_exists($f)) {
		echo '1';
	}
}

?>
