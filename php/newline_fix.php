<?php


$x = trim(file_get_contents('/var/www/html/mturk/newline_no_sd'));
$x = explode("\n", $x);
$count = 0;
$count2 = 0;

foreach ($x as $key=>$value) {
	//curl 'https://taxdelinquent.cookcountyclerk.com/' -H 'Cookie: __RequestVerificationToken=eUGGAzy2i0s4A5WUEOoJeNbGGvYpznZvQFFYMsTqiarhdPxY-lwSt0wuw4xvmCMfDtRR9htpqkK6PxfzuEm2rRY6Gws-UzMTwAUYvER504U1; AWSELB=A9EBA5270C2EAE5F7E065F1AC8C0149982D3A5666DFA0DF2F708F8B85E85FADFCC352017385562BA93D3B8A9A16D952602C0787FE0AFFBD9A37E8796B394C62F98705AE290' -H 'Origin: https://taxdelinquent.cookcountyclerk.com' -H 'Accept-Encoding: gzip, deflate, br' -H 'Accept-Language: en-US,en;q=0.9' -H 'Upgrade-Insecure-Requests: 1' -H 'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.186 Safari/537.36' -H 'Content-Type: application/x-www-form-urlencoded' -H 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8' -H 'Cache-Control: max-age=0' -H 'Referer: https://taxdelinquent.cookcountyclerk.com/' -H 'Connection: keep-alive' --data '__RequestVerificationToken=M9o_A5rQ2iU2TvuXAIpWajkw12vgtl-y8S7nDEj_1z-DzPruC78NoqElYXy6Y1CjZMV62KheboxjRV_Ry4Z2u-_VtxosZnLC6tSYkiCaRO01&Pin=26-06-112-016-0000' --compressed
	
	$h = array(
		'Origin: https://taxdelinquent.cookcountyclerk.com',
		//~ 'Accept-Encoding: gzip, deflate, br',
		'Accept-Language: en-US,en;q=0.9',
		'Upgrade-Insecure-Requests: 1',
		'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.186 Safari/537.36',
		'Content-Type: application/x-www-form-urlencoded',
		'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
		'Cache-Control: max-age=0',
		'Referer: https://taxdelinquent.cookcountyclerk.com/',
		//'Connection: keep-alive'
	);
		
	// Get file content
	$value = str_replace('./', '', $value);
	
	echo 'Start:'.$value;
	$fc = file_get_contents('/var/www/html/mturk/.cached/'.$value);
	// Get pin
	preg_match('/<h4>([\d\-]+)<\/h4>/', $fc, $m);
	if (!isset($m) || !isset($m[0]) || !isset($m[1])) {
		echo 'Bad file: PIN ???';
		continue;
		exit;
	}
	$pin = $m[1];
	
	// Try download
	$fn = '/var/www/html/mturk/.scripts/newline_fix/'.$pin;
	$cached = false; // Set marker.
	if (!file_exists($fn)) {
		$ch = curl_init('https://taxdelinquent.cookcountyclerk.com/');
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, 'Pin='.urlencode($pin));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $h);
		$r = curl_exec($ch);
		$x = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if ($x != 200) {
			echo 'Bad status code:'.$x;
			exit;
		}
		// Save
		file_put_contents($fn, $r);
	} else {
		//~ echo '[read from cache]';
		$cached = true;
		$r = file_get_contents($fn);
	}
	
	if (strpos($r, 'Data as of') !== false) {
		//~ echo '  Unexpected result: Found PIN with data ???';
		echo '[F]';
		$count++;
	}
	else if (strpos($r, 'This is an invalid PIN') !== false) {
		echo '[I]';
		$count2++;
		//~ echo '  Unexpected result: Contains "INVALID PIN" message ???';
	}
	else if (strpos($r, 'No Sold or Delinquent Taxes for this PIN') === false) {
		echo 'Unexpected result: Does not contain SD message ???';
		exit;
	} else {
		echo '[*No SD*]'; // EXPECTED RESULT
	}
	echo ".\n";
	
	// When not reading from cache, sleep momentarily between downloads.
	if (!$cached)
		usleep(200000); // 0.2 seconds
		// wait for 2 seconds
		//usleep(2000000);
		//sleep(1);
}
echo 'num Found:'.$count;
echo 'num Invalid:'.$count2;

/**
 * function curl_get
 * 
 * 
 * @options, Options object.
 * @->request_to_file, Save to cached file when status==200.
 * @->request_from_file, Read from file if exists.
 * @->folder, Includes trailing forward-slash.
 */
function curl_get($options) {
	// Return var
	$return = new stdClass;
	
	if  (
		property_exists($options, 'request_to_file') ||
		property_exists($options, 'request_from_file')
		)
	{
		$file = $options->folder . $options->filename; 
		if (property_exists($options, 'request_from_file') && file_exists($file)) {
			$return->isCached = true;
			$return->html = file_get_contents($file);
			$return->status = 200;
			return $return;
		}
	}
	
	$ch = curl_init($options->url);
	// Add headers, array.
	if (property_exists($options, 'headers'))
	{
		$h = $options->headers;
		$new_h = array();
		foreach ($h as $k => $v) {
			array_push($new_h, $v);
		}
		curl_setopt($ch, CURLOPT_HTTPHEADER, $new_h);
	}
	//
	curl_setopt($ch, CURLOPT_VERBOSE, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	//~ curl_setopt($ch, CURLOPT_REFERER, 'http://example.com'); // testing..
	//~ curl_setopt($ch, CURLOPT_AUTOREFERER, true);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
	//~ curl_setopt($ch, CURLOPT_COOKIEFILE, '/var/www/html/cookie.txt');
	//~ curl_setopt($ch, CURLOPT_COOKIEJAR, '/var/www/html/cookie.txt');
	curl_setopt($ch, CURLOPT_TIMEOUT, 140);
	curl_setopt($ch, CURLOPT_ENCODING, 'identity');//
	if (property_exists($options, 'post')) {
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $options->post_str);
	} else
		curl_setopt($ch, CURLOPT_POST, false);
	// Exec
	$r = curl_exec($ch);
	$x = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	
	// Save to file
	if  (
		property_exists($options, 'request_to_file') && $x == 200
		)
	{
		file_put_contents($file, $r);
	}
	
	// Return
	$return->html = $r;
	$return->status = $x;
	return $return;
}

/*******************************************************
 * AMT__database_select2
 * AMT__database_write2
 * New versions of AMT__database_select and AMT__database_write.
 * 
 * 
 * 
 * 
 */
//~ /if (isset($_REQUEST['AMT__database_select2']) ||
	//~ isset($_REQUEST['AMT__database_write2'])) {
	//~ $mysqli = @connect__(); // Connect to new DB
	//~ /**
	 //~ * AMT__database_sw2
	 //~ */
	//~ class AMT__database_sw2 {
		//~ function __construct() {
		
		
?>
