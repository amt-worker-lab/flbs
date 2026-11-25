<?php

set_time_limit(800);

if (
	isset( $_GET['u']) &&
	!isset($_GET['v'])
) {
	
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
	
	$dir = './s/';
	$file = basename($_GET['u']);
	
	// download pdf
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_VERBOSE, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
	curl_setopt($ch, CURLOPT_REFERER, "https://sites.google.com/site/_redacted_/");
	curl_setopt($ch, CURLOPT_URL, $_GET['u']);
	//curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie2.txt');
	//curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie2.txt');
	$r = curl_exec($ch);
	$v = curl_getinfo($ch);

	if  (
			$v['http_code'] == 200
		)
	{
		
		chdir('/var/www/html/mturk/Mturk_Krista/s/');
		
		file_put_contents($file, $r);
		
		chmod($file, 0777);
		
		exec('pdfimages '.$file.' '.$file); // filename, image root
		
		if (file_exists($file . '-000.ppm')) {
			
			chmod($file.'-000.ppm', 0777);
			
			exec('convert -resize 2200x '.$file.'-000.ppm '.$file.'.tif'); // 
			
			exec('convert -crop 1130x380+530+0 '.$file.'.tif '.$file.'-title.tif'); // 1660 - 530
			
			exec('convert -crop 760x250+0+950  '.$file.'.tif '.$file.'-1.tif'); //
			exec('convert -crop 760x250+0+1670 '.$file.'.tif '.$file.'-2.tif'); //
			exec('convert -crop 760x250+0+2380 '.$file.'.tif '.$file.'-3.tif'); //
			
			exec('convert -crop 660x250+760+950  '.$file.'.tif '.$file.'-4.tif'); //
			exec('convert -crop 660x250+760+1670 '.$file.'.tif '.$file.'-5.tif'); //
			exec('convert -crop 660x250+760+2380    '.$file.'.tif '.$file.'-6.tif'); //
			
			exec('convert -crop x250+1420+950  '.$file.'.tif '.$file.'-7.tif'); //
			exec('convert -crop x250+1420+1670 '.$file.'.tif '.$file.'-8.tif'); //
			exec('convert -crop x250+1420+2380    '.$file.'.tif '.$file.'-9.tif'); //
			
			/*exec('convert -resize 2200x '.$file.'-000.ppm '.$file.'.tif'); //760
			
			exec('convert -crop x400+0+0 '.$file.'.tif '.$file.'-title.tif'); //
			
			exec('convert -crop 760x250+0+990  '.$file.'.tif '.$file.'-1.tif'); //
			exec('convert -crop 760x250+0+1700 '.$file.'.tif '.$file.'-2.tif'); //
			exec('convert -crop 760x250+0+2410 '.$file.'.tif '.$file.'-3.tif'); //
			
			exec('convert -crop 660x250+760+990  '.$file.'.tif '.$file.'-4.tif'); //
			exec('convert -crop 660x250+760+1700 '.$file.'.tif '.$file.'-5.tif'); //
			exec('convert -crop 660x250+760+2410    '.$file.'.tif '.$file.'-6.tif'); //
			
			exec('convert -crop x250+1420+990  '.$file.'.tif '.$file.'-7.tif'); //
			exec('convert -crop x250+1420+1700 '.$file.'.tif '.$file.'-8.tif'); //
			exec('convert -crop x250+1420+2410    '.$file.'.tif '.$file.'-9.tif'); //*/
			
			exec('convert '.$file.'.tif '.$file.'.jpg');
			
			chmod($file.'.jpg', 0777);
			
			if (file_exists($file . '.tif')) {
				chmod($file.'.tif', 0777);
				exec('tesseract -psm 3 '.$file.'-title.tif	'.$file.'-title	-l eng nobatch goodchars-mturk_krista'); //filename.txt
				for ($i = 1; $i <= 9; $i++) {
					exec('tesseract -psm 3 '.$file.'-'.$i.'.tif		'.$file.'-'.$i.' -l eng nobatch goodchars-mturk_krista');
				}
				
				if (file_exists($file . '-title.txt')) {
					chmod($file.'-title.txt', 0777);
					for ($i = 1; $i <= 9; $i++) {
						chmod($file.'-'.$i.'.txt', 0777);
					}
				}
			}
		}
		
	}
	exit;
}
else if (
	isset($_GET['u']) &&
	isset($_GET['v'])
		)
{
	
	$file = './s/'.basename($_GET['u']);
	if  (
			file_exists($file . '-title.txt') &&
			file_exists($file . '-1.txt') &&
			file_exists($file . '-2.txt') &&
			file_exists($file . '-3.txt') &&
			file_exists($file . '-4.txt') &&
			file_exists($file . '-5.txt') &&
			file_exists($file . '-6.txt') &&
			file_exists($file . '-7.txt') &&
			file_exists($file . '-8.txt') &&
			file_exists($file . '-9.txt')
		)
	{
		
		//sleep(3); //wait in case file is being written
		
		echo file_get_contents($file . '-title.txt');
		echo '|';
		for ($i = 1; $i <= 9; $i++) {
			echo file_get_contents($file . '-'.$i.'.txt');
			if ($i != 9)
				echo '|';
		}
	}
	exit;
}



// Jun. 17
if (
	isset( $_GET['u2']) &&
	!isset($_GET['v2'])
   )
{
	
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
	
	$dir = './s/';
	$file = basename($_GET['u2']);
	
	// download pdf
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_VERBOSE, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
	curl_setopt($ch, CURLOPT_REFERER, "https://sites.google.com/site/_redacted_/");
	curl_setopt($ch, CURLOPT_URL, $_GET['u2']);
	//curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie2.txt');
	//curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie2.txt');
	$r = curl_exec($ch);
	$v = curl_getinfo($ch);

	if  (
			$v['http_code'] == 200
		)
	{
		
		chdir('/var/www/html/mturk/Mturk_Krista/s/');
		
		file_put_contents($file, $r);
		
		chmod($file, 0777);
		
		$file = str_replace('"','\"',$file);
		
		exec('pdftotext -raw ./"'.$file.'"'); // filename, image root
		
	}
	exit;
}
else if
   (
	isset($_GET['u2']) &&
	isset($_GET['v2'])
   )
{
	
	$file = '/var/www/html/mturk/Mturk_Krista/s/'. str_replace('.pdf','',basename($_GET['u2'])) . '.txt';
	
	//echo $file;
	
	if  (
			file_exists($file)
		)
	{
		
		//sleep(3); //wait in case file is being written
		
		echo file_get_contents($file);
	}
	exit;
}




















?>
