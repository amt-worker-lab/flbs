<?php 
set_time_limit(200);
error_reporting(E_ALL ^ E_NOTICE); ini_set('display_errors', 1); ini_set('display_startup_errors', 1);

if (isset($_GET['u'])) {
	$f = str_replace('/', '', $_GET['u']);
	$f = '../.deleteme/'.$f;
	if (file_exists($f) && !isset($_GET['no_cache'])) { // exists // NOTE: CHANGED "&" TO "&&"
		echo file_get_contents($f); 
		exit;
	}
	
	require_once('./URL_Resolver/URLResolver.php');
	$resolver = new URLResolver();
	$resolver->setRequestTimeout(120);
	print $resolver->resolveURL($_GET['u'])->getURL();
	
	if (strlen($resolver->resolveURL($_GET['u'])->getURL()) > 10)
		file_put_contents($f, $resolver->resolveURL($_GET['u'])->getURL());
}
if (isset($_GET['v'])) {
	$ch = curl_init($_GET['v']);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_VERBOSE, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
	curl_setopt($ch, CURLOPT_POST, false);
	curl_setopt($ch, CURLOPT_COOKIEFILE, '/var/www/html/cookie.txt');
	curl_setopt($ch, CURLOPT_COOKIEJAR, '/var/www/html/cookie.txt');
	$r = curl_exec($ch);
	$x = curl_getinfo($ch);
	print_r($x);
	curl_close($ch);
	
	echo $r;
	if(curl_errno($ch))
	{
		echo 'error:' . curl_error($ch);
	}
	exit;
}
























?>
