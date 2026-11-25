<?php
error_reporting(E_ALL ^ E_NOTICE); ini_set('display_errors', 1); ini_set('display_startup_errors', 1);
//
// 
//
class output_html_from_URL {
	
	function get_URL($url) {
		$f = './.cached/'.md5($url);
		if (file_exists($f)) {
			return trim(file_get_contents($f));
			//~ if (trim(file_get_contents($f)) != '')
				//~ exit;
		}
		
		// here?
		$ch = curl_init($url); // post upload
		
		if (isset($_GET['AMT__output_html_from_URL__headers'])) {
			//curl_setopt($ch, CURLOPT_HEADER, true);
			$h = json_decode($_GET['AMT__output_html_from_URL__headers']);
			$new_h = array();
			foreach ($h as $k => $v) {
				array_push($new_h, $v);
			}
			curl_setopt($ch, CURLOPT_HTTPHEADER, $new_h);
		}
		curl_setopt($ch, CURLOPT_VERBOSE, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
		curl_setopt($ch, CURLOPT_POST, false);
		//~ curl_setopt($ch, CURLOPT_COOKIEFILE, '/var/www/html/cookie.txt');
		//~ curl_setopt($ch, CURLOPT_COOKIEJAR, '/var/www/html/cookie.txt');
		curl_setopt($ch, CURLOPT_TIMEOUT, 100);
		curl_setopt($ch, CURLOPT_ENCODING, 'identity');//gzip encoding requires this to be ...
		
		$r = curl_exec($ch);
		$x = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if ($x != 200) {
			return false;
		} else {
			//~ $r = preg_replace_callback( // // YAHOO !!
				//~ '/yschttl spt" href="[^*]+[*]+([^"]+)"/',
				//~ function($matches) {
					//~ return 'yschttl spt" href="'.urldecode($matches[1]).'"';
				//~ },
				//~ $r
			//~ );
			file_put_contents($f, $r);
			return $r;
		}
	}
}

/**
 * $_GET['AMT_Hartti_Suomela']
 * @class AMT_Hartti_Suomela
 * 
 */
/**
 *  Table: fees
	Columns:
	id	int(11) PK AI
	code	varchar(100) 
	description	text 
	* description_athena_healthcare_11_12
	* description_athena_healthcare_11_12__coverage
 */
class AMT_Hartti_Suomela {
	public function __construct() {
		
		set_time_limit(0);
		
		@connect_();
		mysql_select_db('AMT_Requester_Hartti_Suomela');
		
		$query = mysql_query('
			SELECT id, code FROM AMT_Requester_Hartti_Suomela.fees
				WHERE
					description_athena_healthcare_11_12 = ""
		') or die(mysql_error());
		
		//WHERE	description_athena_healthcare_11_12!=""
		
		//~ echo mysqle_num_rows($query);return;
		if (mysql_num_rows($query) == 0) return;
		while ($row = mysql_fetch_array($query)) {
			//~ print_r($row);return;
			
			echo ' ';
			echo $row['code'];
			
			$u = 'http://private.athenahealthcare.com/medicarepricing/Report.asp?HCPCS='.$row['code'].'&CurrentYear=2012&PriorYear=2011&txtState=';
			$c = new output_html_from_URL;
			$c = $c->get_URL($u);
			
			//~ echo $c;
			//~ exit;
			
			if (!$c) {
				// try again?
				echo '!200';
				continue;
			}
			
			// Here?
			// This will be displayed if there is a server error (the site is down)
			// or if the ID (99150, 99151, ... etc.) does not exist.
			if (strpos($c, 'The page cannot be displayed because an internal server error has occurred.')) {
				echo 'server';
				continue;
			}
			
			// preg_match
			$matches = preg_match('/<td width="10%">\d+<\/td>[\r\n\t\s]+<td width="45%">([^<]*)<\/td>[\r\n\t\s]+<td width="45%">([^<]*)<\/td>/', $c, $match);
			if ($matches == 1) {
				mysql_query('
					UPDATE AMT_Requester_Hartti_Suomela.fees
					SET
						description_athena_healthcare_11_12				="'.mysql_real_escape_string($match[1]).'",
						description_athena_healthcare_11_12__coverage	="'.mysql_real_escape_string($match[2]).'"
					WHERE
						id="'.mysql_real_escape_string($row['id']).'"
				') or die(mysql_error());
			}
			//~ return;
		}
		
		//~ for ($i = 99150; $i >= 22000; $i--)
		//~ {
			//~ echo $i. ' ';
			//~ 
			//~ $code = str_pad($i, 5 - strlen($i), '0', STR_PAD_LEFT);
			//~ 
			//~ 
		//~ }
	} 
}
if (isset($argv) && $argv[1] == 'AMT_Hartti_Suomela') { // new
	
	$a = new AMT_Hartti_Suomela;
	
}

if (isset($_GET['output_html_from_URL'])) { // new
	
	exit;
}

function connect_() {
	mysql_connect('localhost', 'root', '_redacted_') or die('err'.mysql_error());
}

?>
