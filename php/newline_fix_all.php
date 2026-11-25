<?php


$x = trim(file_get_contents('/var/www/html/mturk/newline_fix_all'));
$x = explode("\n", $x);

// Out file1
$fp_cr = fopen('Corrected Results.csv', 'w');
$fp = fopen('All Results.csv', 'w');
$cheader = [
	'Data Corrected',
	'Data Updated',
	'PIN',
	'0 - Result Type',
	'Sold 1 - Year',
	'Sold 1 - Sale Type',
	'Sold 1 - Sale Status',
	'Sold 1 - Document #',
	'Sold 1 - Date',
	'Sold 2 - Year',
	'Sold 2 - Sale Type',
	'Sold 2 - Sale Status',
	'Sold 2 - Document #',
	'Sold 2 - Date',
	'Sold 3 - Year',
	'Sold 3 - Sale Type',
	'Sold 3 - Sale Status',
	'Sold 3 - Document #',
	'Sold 3 - Date',
	'Sold 4 - Year',
	'Sold 4 - Sale Type',
	'Sold 4 - Sale Status',
	'Sold 4 - Document #',
	'Sold 4 - Date',
	'Sold 5 - Year',
	'Sold 5 - Sale Type',
	'Sold 5 - Sale Status',
	'Sold 5 - Document #',
	'Sold 5 - Date',
	'Sold - Additional Years',
	'Delinquent 1 - Year',
	'Delinquent 1 - Status',
	'Delinquent 1 - Forfeit Date',
	'Delinquent 1 - 1st INST Balance',
	'Delinquent 1 - 2nd INST Balance',
	'Delinquent 2 - Year',
	'Delinquent 2 - Status',
	'Delinquent 2 - Forfeit Date',
	'Delinquent 2 - 1st INST Balance',
	'Delinquent 2 - 2nd INST Balance',
	'Delinquent 3 - Year',
	'Delinquent 3 - Status',
	'Delinquent 3 - Forfeit Date',
	'Delinquent 3 - 1st INST Balance',
	'Delinquent 3 - 2nd INST Balance',
	'Delinquent 4 - Year',
	'Delinquent 4 - Status',
	'Delinquent 4 - Forfeit Date',
	'Delinquent 4 - 1st INST Balance',
	'Delinquent 4 - 2nd INST Balance',
	'Delinquent 5 - Year',
	'Delinquent 5 - Status',
	'Delinquent 5 - Forfeit Date',
	'Delinquent 5 - 1st INST Balance',
	'Delinquent 5 - 2nd INST Balance',
	'Delinquent 6 - 1st INST Total',
	'Delinquent 6 - 1st INST Total',
	'MoreDelinquentTaxCheckbox'
];
fputcsv($fp, $cheader);
fputcsv($fp_cr, $cheader);

$count_updates = 0;
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
		
	// Filename
	$value = str_replace('./', '', $value);
	echo 'Start:'.$value;
	
	//~ foreach ($list as $fields) {
		//~ fputcsv($fp, $fields);
	//~ }


	// File content
	$fc = file_get_contents('/var/www/html/mturk/.cached/'.$value);
	
	// Get pin
	preg_match('/<h4>([\d\-]+)<\/h4>/', $fc, $m);
	if (!isset($m) || !isset($m[0]) || !isset($m[1])) {
		echo 'Bad file: PIN ???';
		exit;
	}
	$pin = $m[1];
	echo ' pin:'.$pin.' ';
	
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
	
	//$fn = '/var/www/html/mturk/.scripts/newline_fix/'.$pin;
	//$r1 = file_get_contents($fn);
	
	// Difference between First run and Current run.
	$pf = pg_type($fc);
	$pc = pg_type($r);
	$changed_between_runs = false;
	if ($pf != $pc) {
		//echo 'Does not match: first:'.$pf.' current:'.$pc;
		////////////////////////
		// Exit to debug here
		// when first == SD and current != SD
		// Result: Only bad ones to start with are the 1,582 NoSD to SD.
		//
		////////////////////////
		echo '[nosd] to [sd]';
		$changed_between_runs = true;
		if ($pf != 'NoSD' || $pc != 'SD') {
			// This never occurs.
			echo '??';
			exit;
		}
	} else {
		echo 'match';
	}
	// Check data
	if ($pc == 'SD') { 
		$dc = get_data($pin, $r, $changed_between_runs);
		if ($pf == 'SD') {
			$df = get_data($pin, $fc);
		} else {
			// Write corrected row
			fputcsv($fp_cr, $dc);
		}
		
		// Compare new to old.
		// And the second question: Does the current data match the first
		// data!?
		
		// Do they match???
		if ($pf != 'NoSD' && $df != $dc) {
			$dc['Data Updated'] = '1'; // Update the column to reflect latest changes.
			echo '@: df != dc (first SD result does not match second SD result)';
			//~ echo ''
			//~ var_dump($df);
			//~ var_dump($dc);
			//~ exit;
			$count_updates++;
		} else {
			echo '@: df == dc ';
		}
		
		// Write the row
		fputcsv($fp, $dc);
		//~ exit;
		
		
		// If row was previously NoSD, add to separate CORRECTIONS.csv file.
		//
		// This file contains the specifically corrected NoSD to SD
		// results.
		
	} else if ($pc == 'NoSD') {
		// Write row
		// PIN, <error message: nosd or invalid>
		fputcsv($fp, ['','',$pin, 'No Sale/Delinquency']);
	} else if ($pc == 'invalid') {
		fputcsv($fp, ['','',$pin, 'New PIN']);
	} else {
		// No cases
		echo '???';
		exit;
	}
	
	//~ if (pg_type($r) == ) {
		//~ if (strpos($fc, 'Data as of') === false) {
			//~ echo '-->new is [F]';
		//~ } else echo '[**]'; // EXPECTED RESULT
	//~ }
	//~ else if (strpos($r, 'This is an invalid PIN') !== false) {
		//~ if (strpos($fc, 'This is an invalid PIN') === false) {
			//~ echo '-->new is [I]';
		//~ } else echo '[**]'; // EXPECTED RESULT
	//~ }
	//~ else if (strpos($r, 'No Sold or Delinquent Taxes for this PIN') !== false) {
		//~ if (strpos($fc, 'No Sold or Delinquent Taxes for this PIN') === false) {
			//~ echo '-->new is [No SD]';
		//~ } else echo '[**]'; // EXPECTED RESULT
	//~ } else {
		//~ echo '[??]'; // EXPECTED RESULT
		//~ exit;
	//~ }
	echo "\n";
	
	// When not reading from cache, sleep momentarily between downloads.
	if (!$cached)
		usleep(200000); // 0.2 seconds
		// wait for 2 seconds
		//usleep(2000000);
		//sleep(1);
}
echo 'num_updates:'.$count_updates.' ';
fclose($fp);
fclose($fp_cr);

/**
 * Parse the page, then extract fields, then writes to csv.
 * 
 * Note that, for comparison's sake, echo [2,1,"a"] == [2,1,"a"];
 * results in "1".
 */
function get_data($pin, $page, $changed_between_runs = false) {
	$doc = new DOMDocument();
	@$doc->loadHTML($page);
	$row = array(
		($changed_between_runs ? '1' : ''), // blank infers no correction
		'Data Updated'=>'', // Default = ''
		$pin,
		'', // Blank string, b/c no error message
		'Sold 1 - Year'=>'',
		'Sold 1 - Sale Type'=>'',
		'Sold 1 - Sale Status'=>'',
		'Sold 1 - Document #'=>'',
		'Sold 1 - Date'=>'',
		'Sold 2 - Year'=>'',
		'Sold 2 - Sale Type'=>'',
		'Sold 2 - Sale Status'=>'',
		'Sold 2 - Document #'=>'',
		'Sold 2 - Date'=>'',
		'Sold 3 - Year'=>'',
		'Sold 3 - Sale Type'=>'',
		'Sold 3 - Sale Status'=>'',
		'Sold 3 - Document #'=>'',
		'Sold 3 - Date'=>'',
		'Sold 4 - Year'=>'',
		'Sold 4 - Sale Type'=>'',
		'Sold 4 - Sale Status'=>'',
		'Sold 4 - Document #'=>'',
		'Sold 4 - Date'=>'',
		'Sold 5 - Year'=>'',
		'Sold 5 - Sale Type'=>'',
		'Sold 5 - Sale Status'=>'',
		'Sold 5 - Document #'=>'',
		'Sold 5 - Date'=>'',
		'Sold - Additional Years'=>'0', // By default, it is 0, unless overridden.
		'Delinquent 1 - Year'=>'',
		'Delinquent 1 - Status'=>'',
		'Delinquent 1 - Forfeit Date'=>'',
		'Delinquent 1 - 1st INST Balance'=>'',
		'Delinquent 1 - 2nd INST Balance'=>'',
		'Delinquent 2 - Year'=>'',
		'Delinquent 2 - Status'=>'',
		'Delinquent 2 - Forfeit Date'=>'',
		'Delinquent 2 - 1st INST Balance'=>'',
		'Delinquent 2 - 2nd INST Balance'=>'',
		'Delinquent 3 - Year'=>'',
		'Delinquent 3 - Status'=>'',
		'Delinquent 3 - Forfeit Date'=>'',
		'Delinquent 3 - 1st INST Balance'=>'',
		'Delinquent 3 - 2nd INST Balance'=>'',
		'Delinquent 4 - Year'=>'',
		'Delinquent 4 - Status'=>'',
		'Delinquent 4 - Forfeit Date'=>'',
		'Delinquent 4 - 1st INST Balance'=>'',
		'Delinquent 4 - 2nd INST Balance'=>'',
		'Delinquent 5 - Year'=>'',
		'Delinquent 5 - Status'=>'',
		'Delinquent 5 - Forfeit Date'=>'',
		'Delinquent 5 - 1st INST Balance'=>'',
		'Delinquent 5 - 2nd INST Balance'=>'',
		'Delinquent 6 - 1st INST Total'=>'',
		'Delinquent 6 - 2nd INST Total'=>'',
		'MoreDelinquentTaxCheckbox'=>'0' // By default, it is 0, unless overridden.
	);
	
	// The number of tables is either 1 or 2.
	// 
	//echo 'num tables:'.$doc->getElementsByTagName('table')->length;
	
	// Here is the layout.
	/*
	'PIN',
	'0 - Result Type',
	'Sold 1 - Year',
	'Sold 1 - Sale Type',
	'Sold 1 - Sale Status',
	'Sold 1 - Document #',
	'Sold 1 - Date',
	'Sold 2 - Year',
	'Sold 2 - Sale Type',
	'Sold 2 - Sale Status',
	'Sold 2 - Document #',
	'Sold 2 - Date',
	'Sold 3 - Year',
	'Sold 3 - Sale Type',
	'Sold 3 - Sale Status',
	'Sold 3 - Document #',
	'Sold 3 - Date',
	'Sold 4 - Year',
	'Sold 4 - Sale Type',
	'Sold 4 - Sale Status',
	'Sold 4 - Document #',
	'Sold 4 - Date',
	'Sold 5 - Year',
	'Sold 5 - Sale Type',
	'Sold 5 - Sale Status',
	'Sold 5 - Document #',
	'Sold 5 - Date',
	'Sold - Additional Years',
	'Delinquent 1 - Year',
	'Delinquent 1 - Status',
	'Delinquent 1 - Forfeit Date',
	'Delinquent 1 - 1st INST Balance',
	'Delinquent 1 - 2nd INST Balance',
	'Delinquent 2 - Year',
	'Delinquent 2 - Status',
	'Delinquent 2 - Forfeit Date',
	'Delinquent 2 - 1st INST Balance',
	'Delinquent 2 - 2nd INST Balance',
	'Delinquent 3 - Year',
	'Delinquent 3 - Status',
	'Delinquent 3 - Forfeit Date',
	'Delinquent 3 - 1st INST Balance',
	'Delinquent 3 - 2nd INST Balance',
	'Delinquent 4 - Year',
	'Delinquent 4 - Status',
	'Delinquent 4 - Forfeit Date',
	'Delinquent 4 - 1st INST Balance',
	'Delinquent 4 - 2nd INST Balance',
	'Delinquent 5 - Year',
	'Delinquent 5 - Status',
	'Delinquent 5 - Forfeit Date',
	'Delinquent 5 - 1st INST Balance',
	'Delinquent 5 - 2nd INST Balance',
	'Delinquent 6 - 1st INST Total',
	'Delinquent 6 - 2nd INST Total',
	'MoreDelinquentTaxCheckbox'*/
	$tbs = $doc->getElementsByTagName('table');
	$c = array('s'=>1,'d'=>1);
    foreach ($tbs as $tb) 
    { 
        //$innerHTML .= $element->ownerDocument->saveHTML($child);
        $tbih = DOMinnerHTML($tb);
        // Type.
        // The table parents specify either
        // "Sold Taxes" or "Delinquent Taxes"
        if (strpos($tbih, 'Tax Sale') !== false) {
			$type = 'sold';
		} else if (strpos($tbih, '>Tax Year<') !== false) {
			$type = 'delq';
		} else {
			// no type?
			echo '@get_data: no type???';
			echo $tbih;
			exit;
		}
        $trs = $tb->getElementsByTagName('tr');
        foreach($trs as $tr) {
			if (strpos(DOMinnerHTML($tr), '<th>') !== false) {
				continue; // A th
			}
			// If here, fill in.
			$tds = $tr->getElementsByTagName('td');
			// Returns assoc. array
			$fi = fill_in($type, $tds);
			// Add to $row.
			if ($type == 'sold') {
				if ($c['s'] <= 5) {
					$row['Sold '.$c['s'].' - Year'] = $fi['year'];
					$row['Sold '.$c['s'].' - Sale Type'] = $fi['type'];
					$row['Sold '.$c['s'].' - Sale Status'] = $fi['status'];
					$row['Sold '.$c['s'].' - Document #'] = $fi['doc#'];
					$row['Sold '.$c['s'].' - Date'] = $fi['date'];
					$c['s']++;
				} else {
					// Assume checkbox value = 1 when checked.
					// Max 5 entries.
					$row['Sold - Additional Years'] = '1';
				}
			} else if ($type == 'delq') {//print_r($fi);exit;
				if ($fi['ftype'] == 'footer') { // fill type is "footer"
					$row['Delinquent 6 - 1st INST Total'] = $fi['first'];
					$row['Delinquent 6 - 2nd INST Total'] = $fi['second'];
				} else if ($c['d'] <= 5) {
					$row['Delinquent '.$c['d'].' - Year'] = $fi['year'];
					$row['Delinquent '.$c['d'].' - Status'] = $fi['status'];
					$row['Delinquent '.$c['d'].' - Forfeit Date'] = $fi['date'];
					$row['Delinquent '.$c['d'].' - 1st INST Balance'] = $fi['first'];
					$row['Delinquent '.$c['d'].' - 2nd INST Balance'] = $fi['second'];
					$c['d']++;
				} else {
					// Assume checkbox value = 1 when checked.
					// Max 5 entries.
					$row['MoreDelinquentTaxCheckbox'] = '1';
				}
			}
		}
    }
    
    ////
    // Return the row here
    ////
    return $row;
    
	//~ foreach ($tb as $key=>$value) {
	//~ }
}
/**
 * 
 * 
 * Return an array of entries.
 */
function fill_in($type, $tds) {
	// if: Total Tax Balance Due*
	$return = array();
	if ($type == 'sold') {
		// td0:
		// td1: ignore
		// td2: status
		$rslt = preg_match('/^(\d+)( Annual Sale| Scavenger Sale| Special Assessment| )$/', DOMinnerHTML($tds[0]), $m);
		if (!$rslt) {
			// inspect
			echo '@fill_in: bad sold type / year ??';
			exit;
		}
		$return['year'] = $m[1];
		$return['type'] = trim($m[2]);
		$return['status'] = trim(DOMinnerHTML($tds[2]));
		$return['doc#'] = trim(DOMinnerHTML($tds[3]));
		$return['date'] = trim(DOMinnerHTML($tds[4]));
		return $return;
	} else if ($type == 'delq') {
		$rslt = preg_match('/^(\d+)$/', DOMinnerHTML($tds[0]));
		if ($rslt) { // regular fill type
			$return['ftype'] = '';
			$return['year'] = DOMinnerHTML($tds[0]);
			$return['status'] = trim(DOMinnerHTML($tds[1]));
			$return['date'] = trim(DOMinnerHTML($tds[2]));
			$return['first'] = trim(DOMinnerHTML($tds[3]));
			$return['second'] = trim(DOMinnerHTML($tds[4]));
			
		} else if (strpos(DOMinnerHTML($tds[0]), 'Total Tax Balance Due*') !== false) {
			$return['ftype'] = 'footer';
			$return['first'] = DOMinnerHTML($tds[2]);
			$return['second'] = trim(DOMinnerHTML($tds[3]));
		} else {
			// Other??
			// inspect
			echo '@fill_in: other delq tds??';
			exit;
		}
		return $return;
	}
}
function DOMinnerHTML(DOMNode $element) 
{ 
    $innerHTML = ""; 
    $children  = $element->childNodes;

    foreach ($children as $child) 
    { 
        $innerHTML .= $element->ownerDocument->saveHTML($child);
    }

    return $innerHTML; 
} 
/**
 * Return the type of page: No SD, SD, or invalid.
 **/ 
function pg_type($page) {
	if (strpos($page, 'Data as of') !== false) {
		return 'SD';
	} else if (strpos($page, 'This is an invalid PIN') !== false) {
		return 'invalid';
	} else if (strpos($page, 'No Sold or Delinquent Taxes for this PIN') !== false) {
		return 'NoSD';
	} else {
		// Bad page?
		echo '@pg_type: Bad page???';
		exit;
	}
}

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
