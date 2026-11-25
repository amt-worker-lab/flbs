<?php

//XXTEST
if (isset($_GET['Seth_Hays_geocoding'])) {
	$ch = curl_init($file); // post upload
	curl_setopt($ch, CURLOPT_VERBOSE, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
	curl_setopt($ch, CURLOPT_POST, false);
	curl_setopt($ch, CURLOPT_COOKIEFILE, '/var/www/html/cookie.txt');
	curl_setopt($ch, CURLOPT_COOKIEJAR, '/var/www/html/cookie.txt');
	curl_setopt($ch, CURLOPT_TIMEOUT, 100);
	$r = curl_exec($ch);
	$x = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	if ($x != 200) {
		file_put_contents($f.'.log', $file.' '.$x."\n", FILE_APPEND);
	}
}
?>
