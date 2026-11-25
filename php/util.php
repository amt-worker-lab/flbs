<?php
/**
 * For command-line utility scripts search for argv. 
 */
//error_reporting(E_ALL);
error_reporting(E_ALL ^ E_NOTICE); ini_set('display_errors', 1); ini_set('display_startup_errors', 1);
//set_time_limit(300); //5 min.
//set_time_limit(800); //10 min.

//
if (isset($_POST['AMT__mail']) && isset($_POST['AMT__mail__mailer_object'])) { // as in iframe
	require("/usr/share/php/PHPMailer/class.phpmailer.php");
	//print_r($_POST['AMT__mail__mailer_object']);
	
	$mail = new PHPMailer();
	$mailer_obj = json_decode($_POST["AMT__mail__mailer_object"]);	
	$mail->IsSMTP();	// telling the class to use SMTP
	$mail->SMTPAuth   = true;	// SMTP authentication
	$mail->Host       = "smtp.gmail.com";	// SMTP server
	$mail->Host       = "74.125.142.109";	// SMTP server // THIS IS RETRIEVED FROM "nslookup smtp.gmail.com"
	//~ $mail->Host       = "smtp.gmail.com:465";	// SMTP server
	//~ $mail->Host       = "localhost";	// SMTP server
	$mail->SMTPDebug  = 1; 
	//~ $mail->Host       = "ssl://smtp.gmail.com";	// SMTP server
	$mail->Port       = 465;	// SMTP Port
	//~ $mail->Port       = 587;	// SMTP Port
	//~ $mail->Port       = 25;	// SMTP Port
	//~ $mail->Port       = 80;	// SMTP Port
	$mail->SMTPSecure = "ssl"; //$mail->SMTPSecure = 'ssl';
	//~ $mail->SMTPSecure = 'tls'; //$mail->SMTPSecure = 'ssl';
	$mail->Username   = "_REDACTED_@gmail.com";		// SMTP account username
	//~ $mail->Password   = "_REDACTED_";	// SMTP account password
	$mail->Password   = "_REDACTED_";	// SMTP account password
	$mail->WordWrap = 50;
	//~ $mail->IsHTML(true);
	$mail->AddAttachment(
		$mailer_obj->filename//,
		//$mailer_obj['new_filename']
	);
	$mail->SetFrom("_REDACTED_@gmail.com", "_REDACTED_");		// FROM
	$mail->AddReplyTo("_REDACTED_@gmail.com", "_REDACTED_");	// Reply TO
	$mail->AddAddress("photos@crowdpac.com", "photos@crowdpac.com");	// recipient email
	$mail->AddAddress("_REDACTED_@gmail.com", "_REDACTED_@gmail.com");	// recipient email
	//$mail->AddAddress('_REDACTED_@gmail.com', '_REDACTED_@gmail.com');	// recipient email
	$mail->Subject    = $mailer_obj->subject; // email subject
	$mail->Body       = $mailer_obj->body . '

--
_redacted_
Amazon Worker ID: _redacted_';

	if(!$mail->Send()) {
		echo 'Message was not sent.';
		echo 'Mailer error: ' . $mail->ErrorInfo;
	} else {
		echo 'Message has been sent.';
	}
}

/**
 * php_run_task
 * 
 * @param options
 * @param options._client
 * @param options.opts
 * @param options.opts.{any params to pass to php}
 * @param options.callback
 * 
 * @param options._status_filename (private) 
 * @param options._check_complete (private) 
 * 
 */
if (isset($_POST['php_run_task'])) {
	$opts = json_decode($_POST['php_run_task']);
	// Close the browser session...
	//...
	$r = new PhpRunTask();
	$r->run($opts);
	exit;
}
class PhpRunTask {
	private $opts;
	function run($opts) {
		if ($opts->_client == 'jb') {
			// Process image analysis.
			$this->opts = $opts;
			$this->jb_init();
		}
	}
	function jb_init() {
		// Return object.
		$this->returnObj = [
			// Non-assoc. array.
			'att-cols'=> [],
			'vz-cols'=> [],
			
			// Assoc. array (a dict for fast lookup!)
			'att-cols-dict'=> [],
			'vz-cols-dict'=> [],
		];
		if ($this->opts->opts->type == 'att')
			$this->jb_('att');
		else if ($this->opts->opts->type == 'vz')
			$this->jb_('vz');
	}
	function jb_($type) {
		// If the text file already exists, then exit.
		$folder = '/var/www/html/mturk/.cached/';
		$this->opts->_status_filename = preg_replace(
			'/[^\w\d_\-]/',
			'',
			$this->opts->_status_filename
		);
		if (file_exists($folder.$this->opts->_status_filename)) {
			echo 'File exists - exit';
			exit;
		}
		
		// Download tiles (includes caching).
		// opts:
		//		index: index,
		//		XY: line.pixelFloor  // {x: ..., y: ...}
		$xy = $this->opts->opts->XY;
		$tiles = [ // 9 in total.
			['x'=>$xy->x-1, 'y'=>$xy->y-1], // start in top left, going left-to-right
			['x'=>$xy->x,   'y'=>$xy->y-1],
			['x'=>$xy->x+1, 'y'=>$xy->y-1],
			['x'=>$xy->x-1, 'y'=>$xy->y  ],
			['x'=>$xy->x,   'y'=>$xy->y  ],
			['x'=>$xy->x+1, 'y'=>$xy->y  ],
			['x'=>$xy->x-1, 'y'=>$xy->y+1],
			['x'=>$xy->x,   'y'=>$xy->y+1],
			['x'=>$xy->x+1, 'y'=>$xy->y+1],
		];
		$file_str = '';
		foreach ($tiles as $key=>$t) {
			$s = new stdClass;
			$s->url = 'http://png2.tilesgridv2.rootmetrics.com/'.
				'tilesgrid/api/v2/tile/12/'.$t['x'].'/'.$t['y'].
				'/png/sig/'.($type == 'att' ? '1' : '4');
			$s->folder = '/var/www/html/mturk/.deleteme/';
			$s->request_to_file = true;
			$s->request_from_file = true;
			$s->filename = 'jb_img_'.$t['x'].'_'.$t['y'].'_'.$type;
			$x = curl_get($s);
			if ($x->status != 200) {
				// Make a blank image.
				// Watch out! If there are no hexes on a given tile
				// then RM will return a 404!
				// Ex. as of now is:
				//	png1.tilesgridv2.rootmetrics.com/tilesgrid/api/v2/tile/12/657/1592/png/sig/1
				// Use "PNG" to make the file a png but avoid
				// it in the filename. Meh.
				$se = 
				'cd /var/www/html/mturk/.deleteme/ && '.
				'convert -size "256x256" xc:white PNG:'.$s->filename;
				$output = shell_exec($se);
				//~ echo "\n".$x->status;
				//~ echo "\n".$se;
				//~ echo "\n".$output.'-';
			}
			$file_str .= $s->filename . ' ';
		}
		// Now have imagemagick stitch images together.
		// convert -tile 2x2
		$output = shell_exec(
			'cd /var/www/html/mturk/.deleteme/ && '.
			'montage -mode concatenate -tile x3 -geometry 256x256 '.
			$file_str .' montage-'.$type.'.png');
		
		// Don't delete the tile files.
		// This way, if something goes wrong, at least the image
		// turns out to be 9x9 (the correct dimensions).
		// ...
		
		//convert in.png out.txt
		$output = shell_exec( // 14mb
			'cd /var/www/html/mturk/.deleteme/ && '.
			'convert -colorspace gray -white-threshold 86% montage-'.$type.'.png colors-'.$type.'.txt');
		
		// Show test image
		// For testing purposes
		// Show output plus points.
		if (rand(1,14) == 2 || 0==1) {
			$output = shell_exec(
				'cd /var/www/html/mturk/.deleteme/ && '.
				'convert -colorspace gray -white-threshold 86% montage-'.$type.'.png colors-'.$type.'-testresize.png');
			$drawStr = '';
			$file = 'colors-'.$type.'-testresize.png';
			foreach ($this->opts->opts->pointsToConsider as $k=>$v) {
				////////////////////////////////////////////////////
				// Debug to show point coords on the image.
				/*$s = explode(',', $v);
				if ($s[0] == '324' && $s[1] == '332') {
					$drawStr .= ' -draw "point '.$v.'" -pointsize 8 -draw "text '.$v.' \''.$v.'\'"';
				}
				else {
					$drawStr .= ' -draw "point '.$v.'"';
				}*/
				////////////////////////////////////////////////////
				$drawStr .= ' -draw "point '.$v.'"';
				if (strlen($drawStr) > 3000) {
					$output = shell_exec(
						'cd /var/www/html/mturk/.deleteme/ && '.
						//~ 'mogrify -fill black '.$drawStr.' colors-'.$type.'-testresize.png'
						'convert '.$file.' -fill black '.$drawStr.' '.$file
					); //
					$drawStr = '';
				}
			}
			$output = shell_exec(
				'cd /var/www/html/mturk/.deleteme/ && '.
				'convert '.$file.' -fill black '.$drawStr.' '.$file); //
		}
		
		// A dict for quick lookup.
		$pc_dict = [];
		foreach ($this->opts->opts->pointsToConsider as $k=>$v) {
			$pc_dict[$v] = true;
		}
		
		// Get the file and remove some of the cruft.
				//num gray:589825 BEFORE
				//num gray:186228
				//num gray:589825 BEFORE
				//num gray:186897
		$x = file_get_contents('/var/www/html/mturk/.deleteme/colors-'.$type.'.txt');
				//echo 'num gray:'.substr_count($x, 'gray')."<br>";
		$x = preg_replace('/\n\d+,\d+: \(65535,65535,65535\)  #FFFFFFFFFFFF  gray\(255\)/', '', $x);
				//echo 'num gray:'.substr_count($x, 'gray')."<br>";
		$x = str_replace(// Remove first line
			'# ImageMagick pixel enumeration: 768,768,65535,gray',
			'',
			$x
		);
		$x = explode("\n", trim($x));
		foreach ($x as $k=>$v) {
			$key = explode(':', $v); // [0] holds "x,y".
			$xycoord = $key[0];
			// Skip Non-Centers!
			if (!array_key_exists($xycoord, $pc_dict))
				continue;
			//~ $this->returnObj[$type.'-cols'][ $xycoord ] = 1;
			// Add to return array.
			array_push(
				$this->returnObj[$type.'-cols'],
				$xycoord
			);
			// Add to return dict.
			$this->returnObj[$type.'-cols-dict'][ $xycoord ] = 1;
		}
		
		// Finally, write to file.
		$this->jb_write();
	}
	function jb_write() {
		// Save to the status file and be done.
		$folder = '/var/www/html/mturk/.cached/';
		$this->opts->_status_filename = preg_replace(
			'/[^\w\d_\-]/',
			'',
			$this->opts->_status_filename
		);
		file_put_contents(
			$folder.$this->opts->_status_filename,
			json_encode($this->returnObj)
		);
	}
	////////////////////////////////////////////////////////////
	// This is some testing for reducing the file size.
	////////////////////////////////////////////////////////////
	
	// Now scale the image by 20 to reduce the amount of
	// pixels in total.
	// Default file size is about 32mb for 768x768 image.
	
	// Now have imagemagick scrape pixel-color-info.
	//~ $output = shell_exec(
		//~ 'cd /var/www/html/mturk/.deleteme/ && '.
		//~ 'convert -depth 4 -colors 2 montage-'.$type.'.png colors-'.$type.'.txt');
	//~ $output = shell_exec(
		//~ 'cd /var/www/html/mturk/.deleteme/ && '.
		//~ 'convert -colorspace gray  montage-'.$type.'.png colors-'.$type.'.txt');
	//~ $output = shell_exec(
		//~ 'cd /var/www/html/mturk/.deleteme/ && '.
		//~ 'convert -colorspace gray -resize 64x64  montage-'.$type.'.png colors-'.$type.'.txt');
	//~ $output = shell_exec(
		//~ 'cd /var/www/html/mturk/.deleteme/ && '.
		//~ 'convert -colorspace gray -resize 64x64  montage-'.$type.'.png colors-'.$type.'-testresize.png');
	//		Removing white: 4097 down to 3185 (0.777398096 remain)
	//~ $output = shell_exec(
		//~ 'cd /var/www/html/mturk/.deleteme/ && '.
		//~ 'convert -colorspace gray -resize 256x256  montage-'.$type.'.png colors-'.$type.'.txt');
	//~ $output = shell_exec(
		//~ 'cd /var/www/html/mturk/.deleteme/ && '.
		//~ 'convert -colorspace gray -resize 256x256  montage-'.$type.'.png colors-'.$type.'-testresize.png');
	//		Removing white: 65537 down to 36465 (0.556403253 remain)
	//~ $output = shell_exec( // 14mb
		//~ 'cd /var/www/html/mturk/.deleteme/ && '.
		//~ 'convert -colorspace gray -resize 512x512  montage-'.$type.'.png colors-'.$type.'.txt');
	//~ $output = shell_exec(
		//~ 'cd /var/www/html/mturk/.deleteme/ && '.
		//~ 'convert -colorspace gray -resize 512x512  montage-'.$type.'.png colors-'.$type.'-testresize.png');
	//		262145 to 120415 (0.459345019 remain)
	//~ $output = shell_exec( // 14mb
		//~ 'cd /var/www/html/mturk/.deleteme/ && '.
		//~ 'convert -colorspace gray -resize 1024x1024 -white-threshold 86% montage-'.$type.'.png colors-'.$type.'.txt');
	//~ $output = shell_exec(
		//~ 'cd /var/www/html/mturk/.deleteme/ && '.
		//~ 'convert -colorspace gray -resize 1024x1024 -white-threshold 86%  montage-'.$type.'.png colors-'.$type.'-testresize.png');
	//		262145 to 120415 (0.459345019 remain)
	// 1048576 to 327k
	
	//~ $output = shell_exec( // 14mb
		//~ 'cd /var/www/html/mturk/.deleteme/ && '.
		//~ 'convert -colorspace gray -resize 64x64 -white-threshold 86% montage-'.$type.'.png colors-'.$type.'.txt');
	//~ $output = shell_exec(
		//~ 'cd /var/www/html/mturk/.deleteme/ && '.
		//~ 'convert -colorspace gray -resize 64x64 -white-threshold 86%  montage-'.$type.'.png colors-'.$type.'-testresize.png');
}


/**
 * AMT_carol_chen_alexa
 * 
 * Use AWS api to get alexa.com ranking.
 * 
 * @param	url		string		domain or url
 */
if (isset($_GET['AMT_carol_chen_alexa'])) {
	$opts = json_decode($_GET['AMT_carol_chen_alexa']);
	
	/**
	 * Makes a request to ATS for the top 10 sites in a country
	 */
	class TopSites {

		protected static $ServiceHost       = 'awis.amazonaws.com';
		protected static $SigVersion        = '2';
		protected static $HashAlgorithm     = 'HmacSHA256';
		protected static $accessKeyId       = '_redacted_';   
		protected static $secretAccessKey   = '_redacted_';
		protected static $debug  			= false;
		//~ protected static $url				= '';

		public function TopSites() {
			//~ self::accessKeyId = '_redacted_';
			//~ $this->secretAccessKey = '_redacted_';
		}

		/**
		* Get top sites from ATS
		*/ 
		public function getTopSites($url, $debug) {
			$this->url = $url;
			self::$debug = $debug;
			$queryParams = $this->buildQueryParams();
			$sig = $this->generateSignature($queryParams);
			$url = 'http://' . self::$ServiceHost . '/?' . $queryParams . 
			'&Signature=' . $sig;
			//~ echo $url;
			$ret = self::makeRequest($url);
			self::parseResponse($ret);
		}

		/**
		* Builds an ISO 8601 timestamp for request
		*/
		protected static function getTimestamp() {
			// ROUND
			// About 12 minute max
			// So round down to 10 min?
			//~ $minute = gmdate('i', time());
			//~ $minute = ($minute %% 10 != 0) ? floor($minute / 10) * 10: $minute; // 9 = 0.9 = 0 * 10 = 0 // 4 = 0.4 = 0
			// THIS IS WITHOUT CACHE. EACH REQUEST HAS UNIQUE TIMESTAMP.
			return gmdate("Y-m-d\TH:i:00.\\0\\0\\0\\Z", time());
		}

		/**
		* Builds the url for the request to ATS
		* The url will be urlencoded as per RFC 3986 and the uri params
		* will be in alphabetical order
		*/
		protected function buildQueryParams() {
			$params = array(
				'Action'            => 'UrlInfo',
				'ResponseGroup'     => 'Rank,RankByCountry',
				'AWSAccessKeyId'    => self::$accessKeyId,
				'Timestamp'         => self::getTimestamp(),
				'SignatureVersion'  => self::$SigVersion,
				'Url'		        => $this->url,
				'SignatureMethod'   => self::$HashAlgorithm
			);
			ksort($params);
			$keyvalue = array();
			foreach($params as $k => $v) {
				$keyvalue[] = $k . '=' . rawurlencode($v);
			}
			//~ echo implode('&',$keyvalue);
			return implode('&',$keyvalue);
		}

		/**
		* Makes an http request
		*
		* @param $url      URL to make request to
		* @return String   Result of request
		*/
		protected static function makeRequest($url) {
			//~ print_r("Making request to: \n$url\n");
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_TIMEOUT, 20);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$result = curl_exec($ch);
			curl_close($ch);
			return $result;
		}

		/**
		* Parses the XML response from ATS and echoes the DataUrl element 
		* for each returned site
		*
		* @param String $response    xml response from ATS
		*/
		protected static function parseResponse($response) {
			//~ print_r(xml2obj($response));return;
			$objXML = new xml2Array();
			$a = $objXML->parse($response);
			if (self::$debug) {
				print_r($a); // DEBUG
			}
			
			// Cont
			// Conainer for
			// 1=URL searched
			// 2=Global Rank
			// 3=Rank by country
			$cont = $a[0]['children'][0]['children'][1]['children'][0]['children'][0]['children'];
			
			// Domain
			$domain = false;
			if ($cont[0]['name'] != 'AWS:DATAURL')
				exit;
			$domain = $cont[0]['tagData'];
			
			// Global
			$global = false;
			if ($cont[1]['name'] != 'AWS:RANK')
				exit;
			if (array_key_exists('tagData', $cont[1]))
				$global = $cont[1]['tagData'];
			
			// Rank by country
			// Find US (if there)
			$usrank = false; // Default=false
			if ($cont[2]['name'] != 'AWS:RANKBYCOUNTRY')
				exit;
			if (array_key_exists('children', $cont[2])) {
				$ct = $cont[2]['children'];
				foreach($ct as $key => $country) {
					if ($country['attrs']['CODE'] != 'US')
						continue;
					if ($country['children'][0]['name'] != 'AWS:RANK')
						exit;
					// Here
					$usrank = $country['children'][0]['tagData'];
					break;
				}
			}
			
			echo '{
				"domain":'.json_encode($domain).',
				"global":'.json_encode($global).',
				"usrank":'.json_encode($usrank).'
			}';
			exit;
		}

		/**
		* Generates a signature per RFC 2104
		*
		* @param String $queryParams query parameters to use in creating signature
		* @return String             signature
		*/
		protected function generateSignature($queryParams) {
			$sign = "GET\n" . strtolower(self::$ServiceHost) . "\n/\n". $queryParams;
			//~ echo "String to sign: \n" . $sign . "\n\n";
			$sig = base64_encode(hash_hmac('sha256', $sign, self::$secretAccessKey, true));
			return rawurlencode($sig);
		}
	}
	
	$ranking = new TopSites();
	$ranking->getTopSites($opts->url, $opts->debug);
}
/**
 * xml2Array
 * Helper function xml2Array
 * This is from an example on PHP manual.
 */
class xml2Array {
    
    var $arrOutput = array();
    var $resParser;
    var $strXmlData;
    
    function parse($strInputXML) {
		$this->resParser = xml_parser_create ();
		xml_set_object($this->resParser,$this);
		xml_set_element_handler($this->resParser, "tagOpen", "tagClosed");
		xml_set_character_data_handler($this->resParser, "tagData");
		$this->strXmlData = xml_parse($this->resParser,$strInputXML );
		if (!$this->strXmlData) {
			die(sprintf("XML error: %s at line %d",
			xml_error_string(xml_get_error_code($this->resParser)),
			xml_get_current_line_number($this->resParser)));
		}			
		xml_parser_free($this->resParser);
		return $this->arrOutput;
    }
    function tagOpen($parser, $name, $attrs) {
       $tag = array("name"=>$name, "attrs"=>$attrs); 
       array_push($this->arrOutput, $tag);
    }
    
    function tagData($parser, $tagData) {
       if(trim($tagData)) {
            if(isset($this->arrOutput[count($this->arrOutput)-1]['tagData'])) {
                $this->arrOutput[count($this->arrOutput)-1]['tagData'] .= $tagData;
            } 
            else {
                $this->arrOutput[count($this->arrOutput)-1]['tagData'] = $tagData;
            }
       }
    }
    
    function tagClosed($parser, $name) {
       $this->arrOutput[count($this->arrOutput)-2]['children'][] = $this->arrOutput[count($this->arrOutput)-1];
       array_pop($this->arrOutput);
    }
}
// io__command_line_request
// AMT__io__readWord
// -r would recurse
// // wget -E -H -k -K -p /home/_redacted_/Downloads/test2 -A jpeg,jpg,bmp,gif,png "http://www.dcmeadows.com/" -e robots=off
if (isset($_POST['AMT__io__command_line_request'])) {
	$opts = json_decode($_POST['AMT__io__command_line_request']);
	global__kill_browser_session();
	//debug
	//~ print_r($opts);
	
	switch ($opts->command) {
		case 'readWord':
			$fn = '/var/www/html/mturk/.cached/' . $opts->filename;
			exec('catdoc '. escapeshellcmd($fn) . ' > ' . escapeshellcmd($fn) . '_txt');
			file_put_contents($fn . '_complete', '');
			chmod($fn . '_complete', 0777);
			break;
		case 'pdftotext':
			$fn = '/var/www/html/mturk/.cached/' . $opts->filename;
			exec('pdftotext '. escapeshellcmd($fn));
			file_put_contents($fn . '_complete', '');
			chmod($fn . '_complete', 0777);
			break;
		case 'checkDupImage':
			$fn1 = '/var/www/html/mturk/.cached/' . md5($opts->image1);
			$fn2 = '/var/www/html/mturk/.cached/' . md5($opts->image2);
			$fn_result = '/var/www/html/mturk/.cached/' . md5($opts->image1 . $opts->image2) . 'result'; // Combining result
			if (file_exists($fn_result)) {
				//~ echo file_get_contents($fn_result);
				exit;
			}
			if (!file_exists($fn1)) {
				$fo = file_get_contents($opts->image1);
				file_put_contents($fn1, $fo);
			}
			if (!file_exists($fn2)) {
				$fo = file_get_contents($opts->image2);
				file_put_contents($fn2, $fo);
			}
			exec(
				'[ "$( compare -metric rmse ' .$fn1. ' ' .$fn2. ' null: 2>&1 )" = "0 (0)" ] && \
				echo "same" > ' .$fn_result. ' || echo "not same"  > ' .$fn_result
			);
			//~ echo file_get_contents($fn_result);
			break;
	}
	exit;
}
if (isset($_POST['AMT__io__command_line_request__complete'])) {
	$opts = json_decode($_POST['AMT__io__command_line_request__complete']);
	
	switch ($opts->command) {
		case 'readWord':
			$fn  = '/var/www/html/mturk/.cached/' . $opts->filename . '_complete';
			$ftx = '/var/www/html/mturk/.cached/' . $opts->filename . '_txt';
			if (file_exists($fn)) {
				echo file_get_contents($ftx);
			}
			break;
		case 'pdftotext':
			$fn  = '/var/www/html/mturk/.cached/' . $opts->filename . '_complete';
			$ftx = '/var/www/html/mturk/.cached/' . str_replace('.pdf', '', $opts->filename) . '.txt'; // pdftotext removes ".pdf" and saves as ".txt".
			if (file_exists($fn)) {
				echo file_get_contents($ftx);
			}
			break;
		case 'checkDupImage':
			$fn_result = '/var/www/html/mturk/.cached/' . md5($opts->image1 . $opts->image2) . 'result'; // Combining result
			if (file_exists($fn_result)) {
				echo file_get_contents($fn_result);
			}
			break;
	}
	exit;
}
function global__kill_browser_session() {
	header("Connection: close");
	ob_start();
	phpinfo();
	$size = ob_get_length();
	header("Content-Length: $size");
	ob_end_flush();
	flush();
}
function parseWord($userDoc) {
    $fileHandle = fopen($userDoc, "r");
    $line = @fread($fileHandle, filesize($userDoc));   
    $lines = explode(chr(0x0D),$line);
    $outtext = "";
    foreach($lines as $thisline)
      {
        $pos = strpos($thisline, chr(0x00));
        if (($pos !== FALSE)||(strlen($thisline)==0))
          {
          } else {
            $outtext .= $thisline." ";
          }
      }
     $outtext = preg_replace("/[^a-zA-Z0-9\s\,\.\-\n\r\t@\/\_\(\)]/","",$outtext);
    return $outtext;
} 

/**
 * AMT__utility_window_placeholder
 * Utility Window Placeholder
 * 
 */
if (isset($_GET['AMT__utility_window_placeholder'])) {
	echo 'Utility Window Placeholder';
	exit;
}

// io__wget_download_images
// -r would recurse
// // wget -E -H -k -K -p /home/_redacted_/Downloads/test2 -A jpeg,jpg,bmp,gif,png "http://www.dcmeadows.com/" -e robots=off
if (isset($_GET['AMT__io__wget_download_images'])) {
	// kills browser
	header("Connection: close");
	ob_start();
	phpinfo();
	$size = ob_get_length();
	header("Content-Length: $size");
	ob_end_flush();
	flush();
	
	// fs clean
	//~ $_GET['AMT__io__wget_download_images_filename'] = str_replace('/', '', $_GET['AMT__io__wget_download_images_filename']);
	$_GET['AMT__io__wget_download_images_filename'] = escapeshellcmd($_GET['AMT__io__wget_download_images_filename']);
	
	// fn
	$f				= '/var/www/html/mturk/.deleteme/AMT__io__wget_download_images/'.$_GET['AMT__io__wget_download_images_filename'];
	$f_isComplete	= '/var/www/html/mturk/.deleteme/AMT__io__wget_download_images-'.$_GET['AMT__io__wget_download_images_filename'];
	//~ $f = '/tmp/AMT__io__wget_download_images/'.$_GET['AMT__io__wget_download_images_filename'];
	//~ $f = './.deleteme/AMT__io__wget_download_images/'.$_GET['AMT__io__wget_download_images_filename'];
	
	// exists?
	if (file_exists($f)) {
		exit;
	}
	
	// here?
	mkdir($f, 0777);
	chdir($f);	
	$cmd = 'wget -E -H -k -K -T 60 -p '.$f.' -A jpeg,jpg,bmp,gif,png "'.$_GET['AMT__io__wget_download_images_website'].'" -e robots=off';
	// exec
	// -T seconds
	exec($cmd);
	file_put_contents($f_isComplete, '');
	chmod($f_isComplete, 0777);
	exit;
}
//...
if (isset($_GET['AMT__io__wget_download_images__complete'])) {
	// fs clean
	//~ $_GET['AMT__io__wget_download_images_filename'] = str_replace('/', '', $_GET['AMT__io__wget_download_images_filename']);
	$_GET['AMT__io__wget_download_images_filename'] = escapeshellcmd($_GET['AMT__io__wget_download_images_filename']);
	
	// fn
	$f = '/var/www/html/mturk/.deleteme/AMT__io__wget_download_images/'.$_GET['AMT__io__wget_download_images_filename'];
	$f_isComplete	= '/var/www/html/mturk/.deleteme/AMT__io__wget_download_images-'.$_GET['AMT__io__wget_download_images_filename'];
	
	// exists?
	if (file_exists($f_isComplete)) {
		exit;
	}
	
	//XXXX THIS IS BROKEN :(
	$Directory = new RecursiveDirectoryIterator($f);
	$Iterator = new RecursiveIteratorIterator($Directory);
	$Regex = new RegexIterator($Iterator, '/^.+?\.(jpeg|jpg|bmp|gif|png)$/i', RecursiveRegexIterator::GET_MATCH);
	$files = array();
	foreach ($Regex as $k => $v) {
		array_push(
			$files,
			str_replace($f, '', $v[0])
		);
	}
	echo json_encode($files);
	exit;

	// exists?
	// file iteration
	if (file_exists($f)) {
		$files = array();
		foreach (new DirectoryIterator($f) as $fileInfo) {
			if($fileInfo->isDot()) continue;
			//~ echo $fileInfo->getPath() . $fileInfo->getFilename();
			array_push($files, $fileInfo->getPath() . $fileInfo->getFilename());
		}
		echo json_encode($files);
	}
	exit;
}

// AMT__io_file_exists
// 
//
if (isset($_GET['AMT__io_file_exists'])) { //
	$j = json_decode($_GET['AMT__io_file_exists']);
	if (!property_exists($j, 'folder') || !property_exists($j, 'filename')) {
		echo '0';
		exit;
	}
	$f = $j->folder . '/' . $j->filename;
	if (file_exists($f)) {
		echo '1';
	} else {
		echo '0';
	}
	exit;
}

// xhr_with_disk_cache__read_from_disk
// 
// @params: file_folder,filename
//
if (isset($_GET['xhr_with_disk_cache__read_from_disk'])) {
	$acceptable_folders = ['.cached'=>true, '.miscellaneous'=>true, '.deleteme'=>true];
	$options = json_decode($_GET['xhr_with_disk_cache__read_from_disk']);
	
	// Folder in array acceptable folders?
	if (!array_key_exists($options->file_folder, $acceptable_folders)) {
		echo '___FILE_DOES_NOT_EXIST___';
		exit;
	}
	// Qualified filename
	$strlen = strlen($options->filename);
	if (preg_match('/[^a-z0-9\.\-]/i', $options->filename) || $strlen < 1 || $strlen > 240) {
		// non alphanumeric OR length
		echo '___FILE_DOES_NOT_EXIST___';
		exit;
	}
	
	// ...
	$f = $options->file_folder . '/' . $options->filename;
	if (file_exists($f)) {
		echo file_get_contents($f);
	} else {
		echo '___FILE_DOES_NOT_EXIST___';
	}
	exit;
}
// xhr_with_disk_cache__write_to_disk
// 
// @params: file_folder,filename
//
if (isset($_REQUEST['xhr_with_disk_cache__write_to_disk'])) {
	//~ isset($_GET['xhr_with_disk_cache__write_to_disk']) // NOT THIS {
	$acceptable_folders = ['.cached'=>true, '.miscellaneous'=>true, '.deleteme'=>true];
	$options = json_decode($_REQUEST['xhr_with_disk_cache__write_to_disk']);
	
	// Folder in array acceptable folders?
	if (!array_key_exists($options->file_folder, $acceptable_folders)) {
		echo '___FAILED_WRITE_TO_DISK___';
		exit;
	}
	// Qualified filename
	$strlen = strlen($options->filename);
	if (preg_match('/[^a-z0-9]/i', $options->filename) || $strlen < 1 || $strlen > 140) { // non alphanumeric OR length
		echo '___FAILED_WRITE_TO_DISK___';
		exit;
	}
	
	// ...
	$f = $options->file_folder . '/' . $options->filename;
	if (file_put_contents($f, $options->tmp_response_text)) {
		//~ echo $f;
		//~ echo '->SUCCESS';
		echo '';
	} else {
		echo '___FAILED_WRITE_TO_DISK___';
	}
	exit;
}

/*******************************************************
 * AMT__database_select2
 * AMT__database_write2
 * New versions of AMT__database_select and AMT__database_write.
 * 
 */
if (isset($_REQUEST['AMT__database_select2']) ||
	isset($_REQUEST['AMT__database_write2'])) {
	$mysqli = @connect__(); // Connect to new DB
	/**
	 * AMT__database_sw2
	 */
	class AMT__database_sw2 {
		function __construct() {
			global $_REQUEST, $mysqli;
			$this->mysqli = $mysqli;
			if (isset($_REQUEST['AMT__database_select2'])) {
				$this->obj = json_decode($_REQUEST['AMT__database_select2']);
				$this->output = [ // associative array
					'input'=>$this->obj,
					'results'=>[] // To be filled.
				];
				$this->select();
			} else if (isset($_REQUEST['AMT__database_write2'])) {
				$this->obj = json_decode($_REQUEST['AMT__database_write2']);
				$this->write();
			}
		}
		function select() {
			// obj:
			/**
			.database	String. e.g. "AMT_Batch". (0-9A-Za-z_).
			.table		String. e.g. "history". (0-9A-Za-z_).
			.columns	Array. e.g. ['id', ''] or ['*'].
			.filters	Array. e.g. [{
									field: "XYZ", (0-9A-Za-z_)
									value: "to-encode-w/mysql-real-escape"
									operator: "" (<, >, =, LIKE, !=)
									connector: "" (&&,||,"")}]
			**/
			//~ var_dump($this->obj);
			//~ var_dump($this->obj->columns);
			$cols = implode(',', $this->obj->columns);
			$r = 'SELECT '.$cols.' FROM
				`'.preg_replace('/[^A-Za-z0-9_]/', '', $this->obj->database).'`.`'
				  .preg_replace('/[^A-Za-z0-9_]/', '', $this->obj->table).'` ';
			if ($this->obj->filters && count($this->obj->filters) > 0) {
				$r .= ' WHERE ';
				foreach ($this->obj->filters as $key => $value) {
					$value->value = ($value->value == 'NULL') ?
						'NULL' : 
						' "'.mysqli_real_escape_string($this->mysqli, $value->value).'" ';
					$r .=
						preg_replace('/[^A-Za-z0-9_]/', '', $value->field) .' '.
						preg_replace('/[^<>=LIKE!IS]/', '', $value->operator) .' '.
						$value->value.' '.
						preg_replace('/[^&\|]/', '', $value->connector).' ';
				}
			}
			if ($this->obj->limit) {
				$r .= ' LIMIT '.preg_replace('/[^0-9]/', '', $this->obj->limit);
			}
			/////////////////////
			// DEBUG
			//
			//echo $r;
			/////////////////////
			$r = $this->mysqli->query($r) or die(mysqli_error($this->mysqli));
			while ($row = mysqli_fetch_array($r)) {
				array_push($this->output['results'], $row);
			}
			// Return the response.
			echo json_encode($this->output);
		}
		function write() {
			$r = '';
			$db = '`'
				.preg_replace('/[^A-Za-z0-9_]/', '', $this->obj->database).'`.`'
				.preg_replace('/[^A-Za-z0-9_]/', '', $this->obj->table).'`';
			if ($this->obj->type == 'UPDATE') {
				$r = 'UPDATE '.$db;
			} else if ($this->obj->type == 'INSERT') {
				foreach ($this->obj->columns as $key=>$value) {
					$this->obj->columns[$key] =
						preg_replace('/[^A-Za-z0-9_]/', '', $value);
				}
				$r = 'INSERT INTO '.$db.' ('.implode(',', $this->obj->columns).')';
			} else {
				echo 'Error';
				var_dump($this->obj);
				exit;
			}
			if ($this->obj->type == 'UPDATE') {
				$r .= ' SET ';
				$sets = [];
				foreach ($this->obj->values as $key => $value) {
					array_push($sets, 
						preg_replace('/[^A-Za-z0-9_]/', '', $value->field) .
						preg_replace('/[^=]/', '', $value->operator) .
						'"'.mysqli_real_escape_string($this->mysqli, $value->value).'"');
				}
				$r .= implode(',', $sets);
				if ($this->obj->filters && count($this->obj->filters)) {
					$r .= ' WHERE ';
					foreach ($this->obj->filters as $key => $value) {
						$value->value = ($value->value == 'NULL') ?
							'NULL' : 
							' "'.mysqli_real_escape_string($this->mysqli, $value->value).'" ';
						$r .=
							preg_replace('/[^A-Za-z0-9_]/', '', $value->field) .' '.
							preg_replace('/[^<>=LIKE! IS]/', '', $value->operator) .' '.
							$value->value.' '.
							preg_replace('/[^&\|]/', '', $value->connector).' ';
					}
				}
				//echo $r;
			} else if ($this->obj->type == 'INSERT') {
				foreach ($this->obj->values as $key=>$value) {
					$this->obj->values[$key] =
						mysqli_real_escape_string($this->mysqli, $value);
				}
				$r .= ' VALUES("'.implode('","',$this->obj->values).'") ';
			}
			//echo $r;
			$r = $this->mysqli->query($r) or die(mysqli_error($this->mysqli));
		}
	}
	new AMT__database_sw2();exit;
}
/*******************************************************
 * AMT__database_select
 * AMT__database_select__query
 * 
 * Extended description:
 * 
 * 
 *******************************************************/
////////////////////////////////////////////////////////////////////////
if  (
		(
			isset($_GET['AMT__database_select']) &&
			isset($_GET['AMT__database_select__query'])
		)
	||
		(
			isset($_POST['AMT__database_write']) &&
			isset($_POST['AMT__database_write__query'])
		)
  ) {
////////////////////////////////////////////////////////////////////////
	@connect_(); // Connect to DB
	
	
	// WeFi
	// AMT_Requester__Wefi_Geocode_CModeStats
	//
	//
	if ($_REQUEST['AMT__database_select__query'] == 'AMT_Requester__Wefi_Geocode_CModeStats') {
		class wefi_cmodestats {
			function __construct($enc_strings) {
				$this->es = json_decode($enc_strings); // cModeParam is an array
				$this->output = []; // associative array
				$this->g();
			}
			function finish() {
				echo json_encode($this->output);
				exit;
			}
			function g() { // select queries....
				if (count($this->es->cModeParam) == 0) {
					$this->finish();
					return;
				}
				$next = array_shift($this->es->cModeParam);
				$d = mysql_real_escape_string($next);
				if ($d == 'address_line_2') {
					$sl = ' AND address_line_2 IS NOT NULL AND address_line_2 != "" ';
				} else {
					$sl = ' AND (	store_name		LIKE "%'.$d.'%" ||
									address			LIKE "%'.$d.'%" ||
									address_line_2	LIKE "%'.$d.'%" ||
									address_line_3	LIKE "%'.$d.'%"
					)';
				}
				$r = '
					SELECT
						count(*) as c
					FROM
						FLBS__Wefi_Geocode.`stores'.$this->es->batch_id.'`
					WHERE
						(FLBS_data IS NULL || FLBS_data = "")
						'.$sl.'
				';
				//echo $r;
				$r = mysql_query($r) or die(mysql_error());
				if (mysql_num_rows($r) == 0) {
					$this->output[ $next ] = 0;
				} else {
					$row = mysql_fetch_array($r); //
					$this->output[ $next ] = $row['c'];
				}
				$this->g();
				return;
			}
		}
		new wefi_cmodestats($_REQUEST['AMT__database_select__encodeStrings']);exit;
	}
	// WeFi
	// Review: Write delete
	//
	//
	if ($_REQUEST['AMT__database_write__query'] == 'AMT_Requester__Wefi_Geocode_delete') {
		class wefi_review {
			function __construct($enc_strings) {
				$this->table = ''; // Init loop to find task
				$this->es = json_decode($enc_strings); // {user:, view_status:}
				if (property_exists($this->es, 'batch_id')) { // ADD BATCH ID HERE.......... //
					$this->table = 'stores' .  substr(preg_replace('/[^\d]/', '', $this->es->batch_id), 0, 6); // Remove non-digits, Limit to 6 digits.
				}
				$this->delete_task();
			}
			function delete_task() {
				$r = mysql_query('
					UPDATE 
						FLBS__Wefi_Geocode.`'.$this->table.'`
					SET
						FLBS_data = NULL,
						FLBS_locked_user_timestamp = NULL,
						FLBS_date_complete = NULL,
						FLBS_locked_user_id = NULL
					WHERE
						id = '.mysql_real_escape_string($this->es->id).'
					LIMIT 1 # Only one.........
				') or die(mysql_error());
				exit;
			}
		}
		new wefi_review($_REQUEST['AMT__database_write__encodeStrings']); exit;
	}
	// WeFi
	// Review: Read Cache
	// ****NOTE****
	// ****....Feb.17.2016: Set character otherwise it errors!
	//
	if ($_GET['AMT__database_select__query'] == 'AMT_Requester__Wefi_Geocode_review') {
		class wefi_review {
			function __construct($enc_strings) {
				$this->table = ''; // Init loop to find task
				$this->es = json_decode($enc_strings); // {user:, view_status:}
				mysql_query("SET CHARACTER SET utf8 ") or die (mysql_error('x'));
				if (property_exists($this->es, 'batch_id')) { // ADD BATCH ID HERE.......... //
					$this->table = 'stores' .  substr(preg_replace('/[^\d]/', '', $this->es->batch_id), 0, 6); // Remove non-digits, Limit to 6 digits.
				}
				$this->get_tasks();
				//if 
				//~ $this->remove_stale();
				//~ $this->count_user_tasks(); // Init loop to find task
				//~ $this->get_task(); // Init loop to find task
			}
			function get_tasks() {
				$vs = '';
				if ($this->es->which == 'a') { // Any / All
					//
				} else if ($this->es->which == 'special') {
					$vs = ' && dba="Taco Bell" && multibrand like "%K%"';
					//~ $vs = ' && dba="KFC"';
					//~ $vs = '
					//~ && FLBS_data NOT LIKE "%boundaries%"
					//~ && FLBS_data NOT LIKE "%indicating%"
					//~ && FLBS_data NOT LIKE "%construction%"
					//~ && FLBS_data NOT LIKE "%trees%"
					//~ && (
						//~ store_name LIKE "%basha%" ||
						//~ store_name LIKE "%ralph%" ||
						//~ store_name LIKE "%albertson%" ||
						//~ store_name LIKE "%haggen%" ||
						//~ store_name LIKE "%city market%" ||
						//~ store_name LIKE "%dillon%" ||
						//~ store_name LIKE "%ingles%" ||
						//~ store_name LIKE "%macys%" ||
						//~ store_name LIKE "%macy\'s%" ||
						//~ store_name LIKE "%hy-vee%" ||
						//~ store_name LIKE "%hyvee%" ||
						//~ store_name LIKE "%fry\'s%" ||
						//~ store_name LIKE "%frys%" ||
						//~ store_name LIKE "%teeter%" ||
						//~ store_name LIKE "%soopers%" ||
						//~ store_name LIKE "%kroger%" ||
						//~ store_name LIKE "%vons%" ||
						//~ store_name LIKE "%von\'s%" ||
						//~ store_name LIKE "%safeway%" ||
						//~ store_name LIKE "%fred meyer%" ||
						//~ store_name LIKE "%qfc%" ||
						//~ store_name LIKE "%quality food center%" ||
						//~ store_name LIKE "%randall%" ||
						//~ store_name LIKE "%thumb%" ||
						//~ store_name LIKE "%publix%"
						//~ )';
				} else { // if ($this->es->which == 'm') 
					$vs = ' && FLBS_locked_user_id = "'.mysql_real_escape_string($this->es->which).'"';
				}
				$this->es->num_reviews = intval($this->es->num_reviews);
				if ($this->es->num_reviews > 1000) {
					$this->es->num_reviews = 1000; //?
				}
				$order_dir = ($this->es->review_direction == 'D') ? 'DESC' : 'ASC';
				$this->es->num_reviews = abs($this->es->num_reviews); // Set this to always positive
				$date_complete = ($order_dir == 'DESC') ? ' && FLBS_date_complete > '.mysql_real_escape_string($this->es->since) : ''; // For instant updates
				$multibr = ($this->es->tb_multibrand == '1') ? ' && multibrand!="" && multibrand is not null' : ''; //
				$r = '
					SELECT
						*
					FROM
						FLBS__Wefi_Geocode.`'.$this->table.'`
					WHERE
						FLBS_data IS NOT NULL &&
						FLBS_data != "" &&
						FLBS_date_complete IS NOT NULL &&
						FLBS_date_complete != ""
						'.$date_complete.'
						'.$multibr.'
						'.$vs.'
					ORDER BY FLBS_date_complete '.$order_dir.', id ASC # Orders by latest, Then by database id ascending.
					LIMIT '.mysql_real_escape_string($this->es->num_reviews).'
				';
				//~ echo $r;
				$r = mysql_query($r) or die(mysql_error());
				if (mysql_num_rows($r) == 0) {
					//~ echo 'bleh';
					exit;
				}
				$out_arr = array();
				while ($row = mysql_fetch_array($r)) {
					array_push($out_arr,
						[
							$row['dba'],							//0
							$row['store_name'],						//1
							$row['address'],						//2
							$row['address_line_2'],					//3
							$row['city'],							//4
							$row['state'],							//5
							$row['country'],						//6
							$row['country'],						//7
							$row['id'],								//8
							$row['FLBS_cached_lat_lng'],			//9
							$row['FLBS_cached_placard_name'],		//10
							$row['FLBS_cached_placard_address'],	//11
							
							$row['latitude'],						//12
							$row['longitude'],						//13
							
							$row['FLBS_data'],						//14
							$row['FLBS_locked_user_id'],			//15
							$row['FLBS_date_complete'],				//16
						]
					);
				}
				echo json_encode($out_arr);
				exit;
			}
		}
		new wefi_review($_GET['AMT__database_select__encodeStrings']); exit;
	}
	// WeFi
	// AMT_Requester__Wefi_Geocode_select
	// Note: "Override" -> nearest.....
	//
	if ($_GET['AMT__database_select__query'] == 'AMT_Requester__Wefi_Geocode_select') {
		class wefi_select {
			private $vars;
			private $debug_get_task_iterations = 0;
			function __construct($enc_strings) {
				set_time_limit(20);// Set timeout...
				$this->table = ''; // Init loop to find task
				$this->vars = new StdClass; //
				$this->es = json_decode($enc_strings); // {user:, view_status:}
				if (property_exists($this->es, 'batch_id')) { // ADD BATCH ID HERE.......... //
					$this->table = 'stores' .  substr(preg_replace('/[^\d]/', '', $this->es->batch_id), 0, 6); // Remove non-digits, Limit to 6 digits.
				}
				// Set utf8! This is, in particular, because json_encode
				// fails SILENTLY! with e.g.
				// json_last_error() == JSON_ERROR_UTF8;
				mysql_set_charset("utf8");
				$this->remove_stale();
				$this->count_user_tasks(); // Init loop to find task
				// Reviewing?
				if ($this->es->review_id != '')
					$this->get_task_by_id(); // Init loop to find task
				else
					$this->get_task(); // Init loop to find task
			}
			function count_user_tasks() {
				$usr =  ' FLBS_locked_user_id="'.mysql_real_escape_string($this->es->user).'"';
				$r = mysql_query('
					SELECT
						SUM(UNIX_TIMESTAMP() - '.$this->table.'.FLBS_date_complete <= 300 &&'.$usr.') as stores300,   #5m
						SUM(UNIX_TIMESTAMP() - '.$this->table.'.FLBS_date_complete <= 600 &&'.$usr.') as stores600,   #10m
						SUM(UNIX_TIMESTAMP() - '.$this->table.'.FLBS_date_complete <= 1800 &&'.$usr.') as stores1800, #30m
						SUM(UNIX_TIMESTAMP() - '.$this->table.'.FLBS_date_complete <= 3600 &&'.$usr.') as stores3600, #60m
						SUM(UNIX_TIMESTAMP() - '.$this->table.'.FLBS_date_complete <= 3600*2 &&'.$usr.') as stores2,  #
						SUM(UNIX_TIMESTAMP() - '.$this->table.'.FLBS_date_complete <= 3600*4 &&'.$usr.') as stores4,  #
						SUM(UNIX_TIMESTAMP() - '.$this->table.'.FLBS_date_complete <= 3600*8 &&'.$usr.') as stores8,  #
						SUM(UNIX_TIMESTAMP() - '.$this->table.'.FLBS_date_complete <= 3600*12 &&'.$usr.') as stores12,#
						SUM('.$usr.') as stores_user, #User
						COUNT(*) as stores_count #All
					FROM FLBS__Wefi_Geocode.`'.$this->table.'`
					WHERE
						FLBS_data != "" &&
						FLBS_data IS NOT NULL &&
						FLBS_date_complete != "" &&
						FLBS_date_complete IS NOT NULL
				') or die(mysql_error());
				//  WHERE FLBS_data!="" && FLBS_date_complete IS NOT NULL && FLBS_locked_user_id="'.mysql_real_escape_string($this->es->user).'"
				if (mysql_num_rows($r) == 0) {
					echo 'No user ' . $this->es->user;
					exit;
				}
				$row = mysql_fetch_array($r);
				$this->sc = array(
					'stores300'=>	$row['stores300'],
					'stores600'=>	$row['stores600'],
					'stores1800'=>	$row['stores1800'],
					'stores3600'=>	$row['stores3600'], // 1hr
					'stores2'=>		$row['stores2'],    // 2hr
					'stores4'=>		$row['stores4'],    // 4hr
					'stores8'=>		$row['stores8'],    // 8hr
					'stores12'=>	$row['stores12'],   // 12hr
					'stores_user'=> $row['stores_user'],
					'stores_count'=>$row['stores_count'],
				);
			}
			function remove_stale() {
				// Delete all locks where timestamp is older than 1 hour. (Old time is 10 minutes.)
				$r = mysql_query('
					UPDATE 
						FLBS__Wefi_Geocode.`'.$this->table.'`
					SET
						FLBS_locked_user_timestamp = NULL
					WHERE
						FLBS_locked_user_timestamp IS NOT NULL &&
						FLBS_locked_user_timestamp < UNIX_TIMESTAMP() - 3600
				') or die(mysql_error());
			}
			function get_task_by_id() {
				$this->es->review_id = preg_replace('/[^\d]/', '', $this->es->review_id); // Remove digits
				$this->es->review_id = substr($this->es->review_id, 0, 9); // Trim to at most "100,000,000" (9 chars.)
				$r = mysql_query('
					SELECT
						*
					FROM
						FLBS__Wefi_Geocode.'.$this->table.' 
					WHERE
						id = "'.mysql_real_escape_string($this->es->review_id).'"
					LIMIT 1
				') or die(mysql_error());
				if (mysql_num_rows($r) == 0) {
					echo '';
					exit;
				}
				$row = mysql_fetch_array($r);
				$this->lock_task($row['id']); // Probably unnecessary but lock this task...
				//~ $this->send_row($row);
				$this->get_nearest_stores($row); // TEMP OVERRIDE.........
			}
			function order_stores_by_city() { //
				// Initial order by cities density...
				$this->vars->cities_data = '[]'; // Default is empty
				$this->vars->cities_array = array(); // Default is empty
				if ($this->es->db_order_by_nearest == 'city') { // Note: FLBS_cached_lat_lng must not be empty!
					$query = '
						SELECT
							COUNT(*) AS num, city, state, FLBS_locked_user_timestamp
						FROM FLBS__Wefi_Geocode.'.$this->table.'
						WHERE 
							(FLBS_locked_user_timestamp IS NULL || FLBS_locked_user_timestamp = 0) AND
							(FLBS_data IS NULL || FLBS_data = "") AND
							(FLBS_date_complete IS NULL || FLBS_date_complete = "") AND
							FLBS_cached_lat_lng IS NOT NULL
							'.$this->vars->vs.'
							'.$this->vars->db_dba_equals.'
						GROUP BY city, state
						ORDER BY num DESC
					';
					//echo '...$this->order_stores_by_city:' . $query . '<br>'; // Debug
					$q = mysql_query($query) or die(mysql_error());
					$cts = array();
					while ($row2 = mysql_fetch_array($q)) {
						array_push($cts, [$row2['city'], $row2['state']]);
						//print_r($row2);
					}
					if (count($cts) > 0) {
						$this->vars->cities_data = json_encode($cts); // This variable is encoded to be returned to JavaScript... but so far this is unnecessary.
						$this->vars->cities_array = $cts;
					}
					//print_r($this->vars);
				}
			}
			function get_nearest_stores($row) { // TODO: Limit distance to e.g. 10-20 miles?
				$row['get_nearest_stores'] = array(); // Default is an empty array....
				// Which lat lng pairs to go by?
				// Three choices thus far.
				// 1) Site scrape
				// 2) Wefi coords
				// 3) FLBS cached placard
				$ll = '';
				$ll2 = '';
				$ll3 = '';
				$type = 0;
				if ($row['latitude'] != NULL && $row['longitude'] != NULL) {
					$ll = $row['latitude'] . ',' . $row['longitude'];
					$ll2 = 'latitude,longitude';
					$ll3 = 'latitude IS NOT NULL && longitude IS NOT NULL &&';
					$type = 0;
				} else if ($row['FLBS_site_scrape_lat'] != NULL && $row['FLBS_site_scrape_lon'] != NULL) {
					$ll = $row['FLBS_site_scrape_lat'] . ',' . $row['FLBS_site_scrape_lon'];
					$ll2 = 'FLBS_site_scrape_lat,FLBS_site_scrape_lon';
					$ll3 = 'FLBS_site_scrape_lat IS NOT NULL && FLBS_site_scrape_lon IS NOT NULL &&';
					$type = 1;
				} else if ($row['FLBS_cached_lat_lng'] != NULL && $row['FLBS_cached_lat_lng'] != '0,0') { // Not working for now...
					//~ $ll = $row['FLBS_cached_lat_lng'];
					//~ $ll2 = 'FLBS_cached_lat_lng';
					//~ $ll2 = "SUBSTRING_INDEX(SUBSTRING_INDEX(FLBS_cached_lat_lng, ' ', 1), ' ', -1),SUBSTRING_INDEX(SUBSTRING_INDEX(FLBS_cached_lat_lng, ' ', 2), ' ', -1)";
					//~ $ll3 = 'FLBS_cached_lat_lng IS NOT NULL && ';
					//~ $type = 2;
				}
				//~ echo $ll . '<br>';
				//~ echo $type . '<br>';
				//~ echo '<br>';
				if ($ll == '') {//echo 'x';
					$this->send_row($row);
					return;
				}
				// Do search...
				$query = '
					SELECT
						*,
						ST_Distance(
							POINT('. mysql_real_escape_string($ll) .'),
							POINT('.$ll2.'))
						AS distance
					FROM
						FLBS__Wefi_Geocode.'.$this->table.' 
					WHERE
						id != "'.mysql_real_escape_string($row['id']).'" &&
						'.$ll3.'
						(
						 dba = "'.mysql_real_escape_string($row['dba']).'" ||
						 FLBS_spreadsheet_id = "'.mysql_real_escape_string($row['FLBS_spreadsheet_id']).'"
						)
					ORDER BY distance ASC
					LIMIT 200
				';
				// Debug //
				//echo $query;
				$q = mysql_query($query) or die(mysql_error());
				$gns = array();
				while ($row2 = mysql_fetch_array($q)) { // Sends back non-site scrape
					$is_done = ($row2['FLBS_data'] != '' && strtolower($row2['FLBS_data']) != 'null' && $row2['FLBS_data'] != null) ? '1' : '0';
					if ($type == 0) {
						$add =	$row2['latitude'] . ',' .
								$row2['longitude'] . ',';
					} else if ($type == 1) {
						$add =	$row2['FLBS_site_scrape_lat'] . ',' .
								$row2['FLBS_site_scrape_lon'] . ',';
					} else if ($type == 2) {
						$add =	$row2['FLBS_cached_lat_lng'] . ',';
					}
					$add .=	$is_done;
					array_push($gns, $add);
				}
				$gns = implode('|', $gns);
				$row['get_nearest_stores'] = $gns;
				$this->send_row($row);
				
				//~ if ($row['FLBS_site_scrape_lat'] == 'null' || 
					//~ $row['FLBS_site_scrape_lat'] == 'NULL' ||
					//~ $row['FLBS_site_scrape_lat'] == null ||
					//~ $row['FLBS_site_scrape_lat'] == '') {
					//~ if ($row['latitude'] != NULL && $row['longitude'] != NULL) {
						
						//~ return;
					//~ } else if () { //FLBS_cached_lat_lng
						
					//~ } else {
						
					//~ }
				//~ }
				//~ $query = '
					//~ SELECT
						//~ *,
						//~ ST_Distance(
							//~ POINT('.$row['FLBS_site_scrape_lat'].','.$row['FLBS_site_scrape_lon'].'),
							//~ POINT(FLBS_site_scrape_lat, FLBS_site_scrape_lon))
						//~ AS distance
					//~ FROM
						//~ FLBS__Wefi_Geocode.'.$this->table.' 
					//~ WHERE
						//~ id != "'.mysql_real_escape_string($row['id']).'" &&
						//~ FLBS_site_scrape_lat IS NOT NULL &&
						//~ FLBS_site_scrape_lon IS NOT NULL &&
						//~ (dba = "'.mysql_real_escape_string($row['dba']).'" ||
						 //~ FLBS_spreadsheet_id = "'.mysql_real_escape_string($row['FLBS_spreadsheet_id']).'"
						//~ )
					//~ ORDER BY distance ASC
					//~ LIMIT 100
				//~ ';
				//~ // Debug //
				//~ //echo $query;
				//~ $q = mysql_query($query) or die(mysql_error()); // old=20,then old=40,now=100
				//~ $gns = array();
				//~ while ($row2 = mysql_fetch_array($q)) {
					//~ $is_done = ($row2['FLBS_data'] != '' && strtolower($row2['FLBS_data']) != 'null' && $row2['FLBS_data'] != null) ? '1' : '0';
					//~ $add =	$row2['FLBS_site_scrape_lat'] . ',' .
							//~ $row2['FLBS_site_scrape_lon'] . ',' .
							//~ $is_done;
					//~ array_push($gns, $add);
				//~ }
				//~ $gns = implode('|', $gns);
				//~ $row['get_nearest_stores'] = $gns;
				//~ $this->send_row($row);
			}
			function send_row($row) {
				$out_arr = array();
				array_push($out_arr,
					[
						$row['dba'],							//0
						$row['store_name'],						//1
						$row['address'],						//2
						$row['address_line_2'],					//3 +multibrand
						$row['city'],							//4
						$row['state'],							//5
						$row['country'],						//6
						$row['country'],						//7
						$row['id'],								//8
						$row['FLBS_cached_lat_lng'],			//9
						$row['FLBS_cached_placard_name'],		//10
						$row['FLBS_cached_placard_address'],	//11
						
						$row['latitude'],						//12
						$row['longitude'],						//13
						
						$row['FLBS_ground_floor'],				//14
						$row['FLBS_data'],						//15
						
						$row['atm'],							//16
						$row['address_line_3'] .($row['multibrand'] != '' ? '**'. $row['multibrand'] : ''),					//17
						$row['store_number'],					//18
						$row['FLBS_site_scrape_lat'],			//19
						$row['FLBS_site_scrape_lon'],			//20
						$row['get_nearest_stores'],				//21
						$row['multibrand'],						//22
						
						$row['FLBS_by_placemark'],				//23
						$row['FLBS_imprecise'],					//24
						
					]
				);
				array_push($out_arr, $this->sc); // Push stores count data.....
				echo json_encode($out_arr);
				exit;
			}
			function get_task() {
				// Debug
				//echo '..starting $this->get_task()..';
				//if ($this->debug_get_task_iterations > 1) {
				//	echo '..exit (dgtk='.$this->debug_get_task_iterations.')..';
				//	exit;
				//}
				$this->debug_get_task_iterations++; // Debug
				// Clean......
				$this->es->db_store_is_like = trim($this->es->db_store_is_like);
				// View status. Is the job incomplete?
				// If so show (A=Any viewed-but-undone along with any undone, N=Only undone, M=Only viewed-but-undone of this user).
				$this->vars->vs = '';
				$this->vars->db_dba_equals = '';
				if ($this->es->view_status == 'a') { // Any/All
					//
				} else if ($this->es->view_status == 'n') { // None
					$this->vars->vs = ' AND (FLBS_store_viewed_by_users IS NULL || FLBS_store_viewed_by_users="") ';
				} else if ($this->es->view_status == 'm') { // Mine
					$this->vars->vs = ' AND (FLBS_store_viewed_by_users LIKE "%'.mysql_real_escape_string($this->es->user).'%") ';
				}
				// db_select_city
				// db_select_state
				$sct = '';
				$sst = '';
				if ($this->es->db_last_city && $this->es->db_last_city != '' && $this->es->db_order_by_nearest == 'city') {
					$sct = ' AND city="'.mysql_real_escape_string($this->es->db_last_city).'"';
				}
				if ($this->es->db_last_state && $this->es->db_last_state != '' && $this->es->db_order_by_nearest == 'city') {
					$sst = ' AND state="'.mysql_real_escape_string($this->es->db_last_state).'"';
				}
				// DB State Is (0/[state])
				// db_state_is
				// NOTE: ...... case does not matter ......
				$db_obn = '';
				if ($this->es->db_state_is != '0') {
					$db_obn = mysql_real_escape_string($this->es->db_state_is);
					$db_obn =	' AND state="' . $db_obn . '"';
				}
				// db_order_by_nearest
				$ob1 = '';
				$ob2 = '';
				$ob3 = '';
				if ($this->es->db_order_by_nearest == '0') {
					// Nothing
				} else { // AREA ***OR*** CITY
					$a1 = mysql_real_escape_string($this->es->db_nearest_lat);
					$a2 = mysql_real_escape_string($this->es->db_nearest_lng);
					$ob1 =	', ST_Distance(POINT('.$a1.','.$a2.'),'.
							' POINT(latitude, longitude)) AS distance';
					$ob2 = ' AND FLBS_cached_lat_lng IS NOT NULL';
					$ob3 = ' ORDER BY distance ASC';
				}
				// db_store_is_like
				$sl = '';
				if ($this->es->db_store_is_like == '' || $this->es->db_store_is_like == '0') {
					// Nothing
				} else {
					$d = mysql_real_escape_string($this->es->db_store_is_like);
					if ($this->es->db_store_is_like == 'address_line_2') {
						$sl = ' AND address_line_2 IS NOT NULL AND address_line_2 != "" ';
					//~ } else if ($this->es->db_store_is_like == 'city') {
						//~ // Do nothing...
					} else {
						$sl = ' AND (	store_name		LIKE "%'.$d.'%" ||
										address			LIKE "%'.$d.'%" ||
										address_line_2	LIKE "%'.$d.'%" ||
										address_line_3	LIKE "%'.$d.'%"
						)';
					}
				}
				// db_dba_equals
				$sl2 = '';
				if ($this->es->db_dba_equals == '' || $this->es->db_dba_equals == '0') {
					// Nothing
				} else {
					$d = mysql_real_escape_string($this->es->db_dba_equals);
					$sl2 = ' 
						AND (
						 dba = "'.$d.'" ||
						 FLBS_spreadsheet_id = "'.$d.'"
						)
					';
					$this->vars->db_dba_equals = $sl2;
				}
				// Get the data for *order by city*
				if ($this->es->db_order_by_nearest == 'city') {
					$this->order_stores_by_city();
				}
				// Query...
				$query = '
					SELECT
						* '.$ob1.'
					FROM
						FLBS__Wefi_Geocode.'.$this->table.' 
					WHERE
						(FLBS_locked_user_timestamp IS NULL || FLBS_locked_user_timestamp = 0) AND
						(FLBS_data IS NULL || FLBS_data = "") AND
						(FLBS_date_complete IS NULL || FLBS_date_complete = "")
						'.$this->vars->vs.'
						'.$sl.'
						'.$sl2.'
						'.$ob2.'
						'.$sct.'
						'.$sst.'
						'.$db_obn.'
					'.$ob3.'
					LIMIT 1
				';
				//echo $query."<br>"; // DEBUG
				$r = mysql_query($query) or die(mysql_error());// DEBUG //echo 'success';
				if (mysql_num_rows($r) == 0) {
					//echo '..0 rows..';// Debug
					// If going by city then.... Try another city.
					if ($this->es->db_order_by_nearest == 'city' && $this->debug_get_task_iterations < 20) { // Only 20 tries!
						//echo '..order by nearest..'; // Debug
						$this->es->db_last_city = $this->vars->cities_array[0][0];
						$this->es->db_last_state = $this->vars->cities_array[0][1];
						$this->get_task();
					} else {
						echo '{"error": "No Stores Found!"}';
					}
					return;
				}
				// TRY LOCKING THIS ROW.
				// IF NO ROW IS CHANGED THEN THE LOCK
				// WAS SET BY SOMEONE ELSE!
				$row = mysql_fetch_array($r);
				if ($this->lock_task($row['id']) == 0) {
					//echo '..Tried but cannot lock task..';exit; // Debug
					$this->get_task();
				} else {
					// Safe!
					// Here...
					//~ $this->send_row($row);
					//~ echo "\n".'Get nearest stores :: ';
					$this->get_nearest_stores($row); // TEMP OVERRIDE.........
				}
			}
			// Returns INT mysql_affected_rows()
			function lock_task($id) {
				// IFNULL(concat(FLBS_store_viewed_by_users, "_redacted_"), "_redacted_")
				$u = '"'.mysql_real_escape_string($this->es->user).'"';
				$r = '
					UPDATE 
						FLBS__Wefi_Geocode.`'.$this->table.'`
					SET
						FLBS_locked_user_timestamp = UNIX_TIMESTAMP(),
						FLBS_locked_user_id = "'.mysql_real_escape_string($this->es->user).'",
						FLBS_store_viewed_by_users = IFNULL(CONCAT(FLBS_store_viewed_by_users, '.$u.'), '.$u.') #Add this user to viewers
					WHERE
						id = "'.mysql_real_escape_string($id).'"
				';
				$r = mysql_query($r) or die(mysql_error()); // $id assumed "safe"
				return mysql_affected_rows();
			}
		}
		new wefi_select($_GET['AMT__database_select__encodeStrings']);
	}
	// WeFi
	// AMT_Requester__Wefi_Geocode_write
	//
	//
	if($_POST['AMT__database_write__query'] == 'AMT_Requester__Wefi_Geocode_write') {
		$query_strings = json_decode($_REQUEST['AMT__database_write__encodeStrings']);
		$table = 'stores' .  substr(preg_replace('/[^\d]/', '', $query_strings->batch_id), 0, 6); // Remove non-digits, Limit to 6 digits.
		$gf =  ($query_strings->ground_floor == 'Y') ? 'Y' : '';
		$px1 = ($query_strings->placemark == 'Y') ? 'Y' : '';
		$px2 = ($query_strings->imprecise == 'Y') ? 'Y' : '';
		$atm = (property_exists($query_strings, 'atm')) ? ',atm="' . mysql_real_escape_string($query_strings->atm) . '"' : '';
		//~ $px1 = (property_exists($query_strings, 'placemark')) ? ',FLBS_by_placemark="' . mysql_real_escape_string($query_strings->placemark) . '"' : '';
		//~ $px2 = (property_exists($query_strings, 'imprecise')) ? ',FLBS_imprecise="' . mysql_real_escape_string($query_strings->imprecise) . '"' : '';
		//ground_floor
		$str = '
			UPDATE
				FLBS__Wefi_Geocode.`'.$table.'`
			SET
				FLBS_data = "'.mysql_real_escape_string($query_strings->data).'",
				FLBS_date_complete = UNIX_TIMESTAMP(),
				FLBS_ground_floor = "'.$gf.'",
				FLBS_by_placemark = "'.$px1.'",
				FLBS_imprecise = "'.$px2.'"
				'.$atm.'
			WHERE
				id = '.mysql_real_escape_string($query_strings->id).'
		';
		$r = mysql_query($str) or die(mysql_error());
		exit;
	}
	// WeFi
	// Read Cache
	//
	//
	if ($_GET['AMT__database_select__query'] == 'AMT_Requester__WeFi_select_cache') {

		class wefi_select_cache {
			function __construct() {
				mysql_query("SET CHARACTER SET utf8 ") or die (mysql_error('x'));
				$this->table = 'stores14'; //stores,stores2
				$this->get_task(); // Init loop to find task
			}
			function get_task() {
				// Select all where IN HIT_ids_to_correct
				//  + assignment_id = "Fix_Wal_Mart"
				//  + locked_timestamp = NULL or 0
				//  + is_complete is 0
				$r = '
					SELECT 
						*
					FROM
						FLBS__Wefi_Geocode.'.$this->table.' 
					WHERE
						(FLBS_locked_user_timestamp is NULL  || FLBS_locked_user_timestamp = "") AND
						FLBS_cached_lat_lng is NULL AND
						(FLBS_cached_placard_name is NULL    || FLBS_cached_placard_name = "") AND
						(FLBS_cached_placard_address is NULL || FLBS_cached_placard_address = "")
					LIMIT 1
				';
				$r = mysql_query($r) or die(mysql_error());
				if (mysql_num_rows($r) == 0) {
					echo '';
					exit;
				}
				// TRY LOCKING THIS ROW.
				// IF NO ROW IS CHANGED THEN THE LOCK
				// WAS SET BY SOMEONE ELSE!
				$row = mysql_fetch_array($r);
				if ($this->lock_task($row['id']) == 0) {
					$this->get_task();
				} else {
					//~ print_r($row);
					// Safe!
					// here?
					$out_arr = array();
					array_push(
						$out_arr,
						[
							$row['dba'],
							//~ $row['store_name'],
							$row['address'],
							//~ $row['address_line_2'],
							$row['city'],
							$row['state'],
							$row['country'],
							//~ $row['country_code'],
							$row['id']
						]
					);
					echo json_encode($out_arr);
					exit;
				}
			}
			// Returns INT mysql_affected_rows()
			function lock_task($id) {
				$r = mysql_query('
					UPDATE 
						FLBS__Wefi_Geocode.'.$this->table.' 
					SET
						FLBS_locked_user_timestamp = UNIX_TIMESTAMP()
					WHERE
						id = "'.mysql_real_escape_string($id).'"
				') or die(mysql_error()); // $id assumed "safe"
				return mysql_affected_rows();
			}
		}
		new wefi_select_cache();
	}
	//
	//
	// WeFi
	// AMT_Requester__WeFi_write_cache
	if($_POST['AMT__database_write__query'] == 'AMT_Requester__WeFi_write_cache') {
		$query_strings = json_decode($_REQUEST['AMT__database_write__encodeStrings']);
		$table = 'stores14'; // stores,stores2
		$str = '
			UPDATE
				FLBS__Wefi_Geocode.`'.$table.'`
			SET
				FLBS_locked_user_timestamp = NULL,
				FLBS_cached_lat_lng = "'.mysql_real_escape_string($query_strings->lat.','.$query_strings->lng).'",
				FLBS_cached_placard_name = "'.mysql_real_escape_string($query_strings->placard_name).'",
				FLBS_cached_placard_address = "'.mysql_real_escape_string($query_strings->placard_addr).'"
			WHERE
				id = '.mysql_real_escape_string($query_strings->id).'
		';
		$r = mysql_query($str) or die(mysql_error());
		exit;
	}
	
	// AMT_Requester_Aidin.facilities
	// 
	//
	//
	if ($_GET['AMT__database_select__query'] == 'AMT_Requester_Aidin') { //
		$str = json_decode($_GET['AMT__database_select__encodeStrings'])[0]; // array
		$out = new stdClass;
		$out->facility = array();
		$out->address = array();
		$out->phone = array();
		// facility
		$q = '
			SELECT
				*,
				MATCH(facility) AGAINST (\''.mysql_real_escape_string($str->facility).'\') as relevance
			FROM
				AMT_Requester_Aidin.facilities
			WHERE
				MATCH(facility) AGAINST (\''.mysql_real_escape_string($str->facility).'\')
			ORDER BY relevance DESC
			LIMIT 10
				';
		$r = mysql_query($q);
		$out_arr = array();
		if (mysql_num_rows($r) == 0) {
			//
		} else {
			while ($row = mysql_fetch_array($r)) {
				array_push($out_arr, $row);
			}
		}
		$out->facility = $out_arr;
		// address
		$q = '
			SELECT
				*,
				MATCH(address1) AGAINST (\''.mysql_real_escape_string($str->address).'\') as relevance
			FROM
				AMT_Requester_Aidin.facilities
			WHERE
				MATCH(address1) AGAINST (\''.mysql_real_escape_string($str->address).'\')
			ORDER BY relevance DESC
			LIMIT 10
				';
		$r = mysql_query($q);
		$out_arr = array();
		if (mysql_num_rows($r) == 0) {
			//
		} else {
			while ($row = mysql_fetch_array($r)) {
				array_push($out_arr, $row);
			}
		}
		$out->address = $out_arr;
		// phone
		$q = '
			SELECT
				*
			FROM
				AMT_Requester_Aidin.facilities
			WHERE
				phone = "'.mysql_real_escape_string($str->phone).'"
			LIMIT 10
				';
		$r = mysql_query($q);
		$out_arr = array();
		if (mysql_num_rows($r) == 0) {
			//
		} else {
			while ($row = mysql_fetch_array($r)) {
				array_push($out_arr, $row);
			}
		}
		$out->phone = $out_arr;
		// Done
		echo json_encode($out);
		exit;
	}
	// AMT_Requester_Rory_Bernard.tracks
	//
	if ($_GET['AMT__database_select__query'] == 'AMT_Requester_Rory_Bernard') { //
		$str = json_decode($_GET['AMT__database_select__encodeStrings'])[0]; // array
		$out = new stdClass;
		$out->artist = array();
		$out->title = array();
		$out->a_t = array();
		$out->composer = array();
		$q = '
			SELECT
				*,
				MATCH(CP_TrackTitle) AGAINST (\''.mysql_real_escape_string($str->title).'\') as relevance,
				MATCH(CP_PerformedBy) AGAINST (\''.mysql_real_escape_string($str->artist).'\') as artist_relevance
			FROM
				AMT_Requester_Rory_Bernard.tracks
			WHERE
				MATCH(CP_TrackTitle) AGAINST (\''.mysql_real_escape_string($str->title).'\') AND
				MATCH(CP_PerformedBy) AGAINST (\''.mysql_real_escape_string($str->artist).'\')
			ORDER BY artist_relevance + relevance DESC
				';
		$r = mysql_query($q);
		$out_arr = array();
		if (mysql_num_rows($r) == 0) {
			//
		} else {
			while ($row = mysql_fetch_array($r)) {
				array_push($out_arr, [ $row['CP_ID'], $row['CP_TrackTitle'], $row['CP_PerformedBy'], $row['CP_Composer'] ]);
			}
		}
		$out->a_t = $out_arr;
		// Track
		$q = '
			SELECT
				*,
				MATCH(CP_TrackTitle) AGAINST (\''.mysql_real_escape_string($str->title).'\') as relevance
			FROM
				AMT_Requester_Rory_Bernard.tracks
			WHERE
				MATCH(CP_TrackTitle) AGAINST (\''.mysql_real_escape_string($str->title).'\')
			ORDER BY relevance DESC
				';
		$r = mysql_query($q);
		$out_arr = array();
		if (mysql_num_rows($r) == 0) {
			//
		} else {
			while ($row = mysql_fetch_array($r)) {
				array_push($out_arr, [ $row['CP_ID'], $row['CP_TrackTitle'], $row['CP_PerformedBy'], $row['CP_Composer'] ]);
			}
		}
		$out->title = $out_arr;
		// Artist
		$q = '
			SELECT
				*,
				MATCH(CP_PerformedBy) AGAINST (\''.mysql_real_escape_string($str->artist).'\') as artist_relevance
			FROM
				AMT_Requester_Rory_Bernard.tracks
			WHERE
				MATCH(CP_PerformedBy) AGAINST (\''.mysql_real_escape_string($str->artist).'\')
			ORDER BY artist_relevance DESC
				';
		$r = mysql_query($q);
		$out_arr = array();
		if (mysql_num_rows($r) == 0) {
			//
		} else {
			while ($row = mysql_fetch_array($r)) {
				array_push($out_arr, [ $row['CP_ID'], $row['CP_TrackTitle'], $row['CP_PerformedBy'], $row['CP_Composer'] ]);
			}
		}
		$out->artist = $out_arr;
		// Composer
		$q = '
			SELECT
				*,
				MATCH(CP_Composer) AGAINST (\''.mysql_real_escape_string($str->artist).'\') as composer_relevance
			FROM
				AMT_Requester_Rory_Bernard.tracks
			WHERE
				MATCH(CP_Composer) AGAINST (\''.mysql_real_escape_string($str->artist).'\')
			ORDER BY composer_relevance DESC
				';
		$r = mysql_query($q) or die(mysql_error());
		$out_arr = array();
		if (mysql_num_rows($r) == 0) {
			//
		} else {
			while ($row = mysql_fetch_array($r)) {
				array_push($out_arr, [ $row['CP_ID'], $row['CP_TrackTitle'], $row['CP_PerformedBy'], $row['CP_Composer'] ]);
			}
		}
		$out->composer = $out_arr;
		// Done
		echo json_encode($out);
		exit;
	}
	// Tucker Severson
	// AMT__Tucker_Severson
	//
	//
	if ($_GET['AMT__database_select__query'] == 'AMT__Tucker_Severson') { // AMT__John_Curtis_Mall_Search2.stores;
		$str = json_decode($_GET['AMT__database_select__encodeStrings'])[0]; // array
		$q = 'SELECT * FROM
				AMT_Requester.fips
			WHERE
				MATCH(name) AGAINST (\''.mysql_real_escape_string($str->city).'\')
					AND
				state_abbreviation = \''.mysql_real_escape_string($str->state).'\'';//echo $q;
		$r = mysql_query($q);
		if (mysql_num_rows($r) == 0) { // ...
			echo '';
			exit;
		}
		// here?
		$out_arr = array();
		while ($row = mysql_fetch_array($r)) {
			array_push($out_arr,
				[ $row['state_abbreviation'],
				$row['state_fips'], 
				$row['county_fips'], 
				$row['entity_fips'], 
				$row['name'], 
				$row['type'], 
				$row['pop'], 
				$row['entity_fips_col2']
				]
			);
		}
		echo json_encode($out_arr);
		exit;
	}
	// John Curtis
	// AMT__John_Curtis_Mall_Search2
	//
	//
	if ($_GET['AMT__database_select__query'] == 'AMT__John_Curtis_Mall_Search2') { // AMT__John_Curtis_Mall_Search2.stores;
		$str = json_decode($_GET['AMT__database_select__encodeStrings'])[0]; // array
		$s_m = '';
		if (property_exists($str, 'mall')) {
			$s_m = ' AND MATCH(mall) AGAINST (\''.mysql_real_escape_string($str->mall).'\')';
		}
		$q = 'SELECT * FROM
				AMT__John_Curtis_Mall_Search2.stores
			WHERE
				MATCH(store) AGAINST (\''.mysql_real_escape_string($str->store).'\')
				'. $s_m;
		$r = mysql_query($q);
		if (mysql_num_rows($r) == 0) {
			// Try exact store plus close mall
			$q = 'SELECT * FROM
					AMT__John_Curtis_Mall_Search2.stores
				WHERE
					store=\''.mysql_real_escape_string($str->store).'\'
					 AND
					mall LIKE \'%'.mysql_real_escape_string($str->mall).'%\'
					';
			$r = mysql_query($q);
			if (mysql_num_rows($r) == 0) {
				// Try close store plus close mall
				$q = 'SELECT * FROM
						AMT__John_Curtis_Mall_Search2.stores
					WHERE
						store LIKE \'%'.mysql_real_escape_string($str->store).'%\'
						 AND
						mall LIKE \'%'.mysql_real_escape_string($str->mall).'%\'
						';//echo $q;
				$r = mysql_query($q);
				if (mysql_num_rows($r) == 0) {
					echo '';
					exit;
				}
			}
		}
		// here?
		$out_arr = array();
		while ($row = mysql_fetch_array($r)) {
			array_push($out_arr, [ $row['store'], $row['mall'], $row['magic_number'] ]);
		}
		echo json_encode($out_arr);
		exit;
	}
	// John Curtis
	// AMT__John_Curtis_Mall_Search
	//
	//
	if ($_GET['AMT__database_select__query'] == 'AMT__John_Curtis_Mall_Search') { // AMT__John_Curtis_Mall_Search.stores;
		$str = json_decode($_GET['AMT__database_select__encodeStrings'])[0]; // array
		$s_m = '';
		if (property_exists($str, 'mall')) {
			$s_m = ' AND MATCH(mall) AGAINST (\''.mysql_real_escape_string($str->mall).'\')';
		}
		$q = 'SELECT * FROM
				AMT__John_Curtis_Mall_Search.stores
			WHERE
				MATCH(store) AGAINST (\''.mysql_real_escape_string($str->store).'\')
				'. $s_m;//echo $q;
		$r = mysql_query($q);
		if (mysql_num_rows($r) == 0) {
			// Try exact store plus close mall
			$q = 'SELECT * FROM
					AMT__John_Curtis_Mall_Search.stores
				WHERE
					store=\''.mysql_real_escape_string($str->store).'\'
					 AND
					mall LIKE \'%'.mysql_real_escape_string($str->mall).'%\'
					';//echo $q;
			$r = mysql_query($q);
			if (mysql_num_rows($r) == 0) {
				// Try close store plus close mall
				$q = 'SELECT * FROM
						AMT__John_Curtis_Mall_Search.stores
					WHERE
						store LIKE \'%'.mysql_real_escape_string($str->store).'%\'
						 AND
						mall LIKE \'%'.mysql_real_escape_string($str->mall).'%\'
						';//echo $q;
				$r = mysql_query($q);
				if (mysql_num_rows($r) == 0) {
					echo '';
					exit;
				}
			}
		}
		// here?
		$out_arr = array();
		while ($row = mysql_fetch_array($r)) {
			array_push($out_arr, [ $row['store'], $row['mall'], $row['magic_number'] ]);
		}
		echo json_encode($out_arr);
		exit;
	}
	// AMT_Requester__WeFi_write_WalMart
	// 
	//
	if ($_REQUEST['AMT__database_write__query'] == 'AMT_Requester__WeFi_write_WalMart') {
		$query_strings = json_decode($_REQUEST['AMT__database_write__encodeStrings']);
		//~ $query_strings->id
		//~ $query_strings->task_formatted
		$str = '
			UPDATE
				AMT_batch_correct.HIT_ids_to_correct
			SET
				is_complete = 1,
				correct_value = "'.mysql_real_escape_string($query_strings->correct_value).'"
			WHERE
				id = '.mysql_real_escape_string($query_strings->id).'
		';
		$r = mysql_query($str) or die(mysql_error());
		exit;
	}
	// AMT_Requester__WeFi_select_WalMart
	// 
	//
	if ($_GET['AMT__database_select__query'] == 'AMT_Requester__WeFi_select_WalMart') {
		class wefi_correct_walmart {
			function __construct() {
				$this->remove_stale();
				$this->get_task(); // Init loop to find task
			}
			function remove_stale() {
				// Delete all older than 10 MINUTES that are not complete.
				// time_created
				$r = mysql_query('
					UPDATE 
						AMT_batch_correct.HIT_ids_to_correct
					SET
						locked_timestamp = 0
					WHERE
						locked_timestamp IS NOT NULL &&
						locked_timestamp < UNIX_TIMESTAMP() - 600
				') or die(mysql_error());
			}
			function get_task() {
				// Select all where IN HIT_ids_to_correct
				//  + assignment_id = "Fix_Wal_Mart"
				//  + locked_timestamp = NULL or 0
				//  + is_complete is 0
				$r = mysql_query('
					SELECT 
						*
					FROM
						AMT_batch_correct.HIT_ids_to_correct
					WHERE
						assignment_id = "Fix_Wal_Mart"
							AND
						(
							locked_timestamp IS NULL ||
							locked_timestamp = 0
						)
							AND
						is_complete = 0
					LIMIT 1
				') or die(mysql_error());
				if (mysql_num_rows($r) == 0) {
					echo '';
					exit;
				}
				// TRY LOCKING THIS ROW.
				// IF NO ROW IS CHANGED THEN THE LOCK
				// WAS SET BY SOMEONE ELSE!
				$row = mysql_fetch_array($r);
				if ($this->lock_task($row['id']) == 0) {
					$this->get_task();
				} else {
					// Safe!
					$out_arr = array([
						$row['id'],
						$row['correct_value']
					]);
					echo json_encode($out_arr);
					exit;
				}
			}
			// Returns INT mysql_affected_rows()
			function lock_task($id) {
				$r = mysql_query('
					UPDATE 
						AMT_batch_correct.HIT_ids_to_correct
					SET
						locked_timestamp = UNIX_TIMESTAMP()
					WHERE
						id = "'.$id.'"
				') or die(mysql_error()); // $id assumed "safe"
				return mysql_affected_rows();
			}
		}
		new wefi_correct_walmart();
	}
	// AMT_Requester__WeFi_write
	// 
	//
	if ($_REQUEST['AMT__database_write__query'] == 'AMT_Requester__WeFi_write') {
		$query_strings = json_decode($_REQUEST['AMT__database_write__encodeStrings']);
		//~ $query_strings->id
		//~ $query_strings->task_formatted
		$str = '
			UPDATE
				AMT_batch_correct.AMT_record_to_correct
			SET
				completed = 1,
				task_formatted = "'.mysql_real_escape_string($query_strings->task_formatted).'"
			WHERE
				id = '.mysql_real_escape_string($query_strings->id).'
		';
		$r = mysql_query($str) or die(mysql_error());
		exit;
	}
	// AMT_Requester__WeFi_select
	// 
	//
	if ($_GET['AMT__database_select__query'] == 'AMT_Requester__WeFi_select') {
		// Delete all older than 10 MINUTES that are not complete.
		// time_created
		$r = mysql_query('
			DELETE FROM
				AMT_batch_correct.AMT_record_to_correct
			WHERE
				completed = 0 AND
				time_created < UNIX_TIMESTAMP() - 600
		') or die(mysql_error());
		// Select all where NOT IN AMT_record_to_correct (already corrected)
		$r = mysql_query('
			SELECT 
				*
			FROM
				AMT_Records.InputOutputMatches
			WHERE
				recordName = "WeFi - Quality Control"
					AND
				id > 1510000
					AND
				matchString NOT IN
				(
					SELECT
						matchString
					FROM
						AMT_batch_correct.AMT_record_to_correct
					WHERE
						recordName = "WeFi - Quality Control"
				)
			LIMIT 1
		') or die(mysql_error()); //matchString NOT LIKE \'%Rite Aid%\'
		if (mysql_num_rows($r) == 0) {
			echo '';
			exit;
		}
		// Here...
		// Add to AMT_batch_correct.AMT_record_to_correct, Default completed=0.
		$row = mysql_fetch_array($r);
		$r2 = mysql_query('
			INSERT INTO AMT_batch_correct.AMT_record_to_correct
			SET
				recordName=		"'.mysql_real_escape_string($row['recordName']).'",
				matchString=	"'.mysql_real_escape_string($row['matchString']).'",
				outputs=		"'.mysql_real_escape_string($row['outputs']).'",
				completed=		0,
				task_formatted=	"",
				time_created=	'.time().'
		');
		$out_arr = array([
			$row['recordName'],
			$row['matchString'],
			$row['outputs'],
			mysql_insert_id()
			//$row['id']
		]);
		echo json_encode($out_arr);
		exit;
	}
	// AMT_Requester__Chios_System_Review_Submitted
	// 
	//
	if ($_GET['AMT__database_select__query'] == 'AMT_Requester__Chios_System_Review_Submitted') {
		$r = mysql_query(
			'SELECT
				tasks_accomplished.id,
				amt_hit_id,
				iomatch.id,
				iomatch.matchString AS matchString,
				iomatch.outputs AS outputs,
				iomatch.lastTimeOfSubmit AS lastTimeOfSubmit
			FROM
				AMT_Alerts.tasks_accomplished
			LEFT JOIN
				AMT_Records.InputOutputMatches iomatch ON
					iomatch.matchString LIKE CONCAT("%",tasks_accomplished.amt_hit_id,"%") &&
					iomatch.recordName="Chios Polygon - Quality Control"
			WHERE
				fk_amt_status="Rejected" &&
				fk_requesterUserId="AU06FWQZTTRD1"
			ORDER BY
				tasks_accomplished.id
			DESC'
		);
		if (mysql_num_rows($r) == 0) {
			echo '';
			exit;
		}
		// Here?
		$out_arr = array();
		while ($row = mysql_fetch_array($r)) {
			$arr = [
				$row['amt_hit_id'],
				$row['matchString'],
				$row['outputs'],
				$row['lastTimeOfSubmit']
			];
			array_push($out_arr, $arr);
		}
		echo json_encode($out_arr);
		exit;
	}
	
	//
	// AMT_Flicksio_IMDB_GetDetails
	//
	if ($_GET['AMT__database_select__query'] == 'AMT_Flicksio_IMDB_GetDetails') {
		$query_strings = json_decode($_GET['AMT__database_select__encodeStrings'])->qStrs; // array
		//~ print_r($query_strings);
		
		//var isTopShow
		$rslt = array(
			'search_t'				=> array(),
			'search_t_s'			=> array(),
			'search_s'				=> array(),
			'search_title_no_opts'	=> array(),
			'search_itunes'			=> array(),
			'original_query_object' => $query_strings
		);
		
		// select db
		mysql_select_db('AMT_Requester_FlicksIO');
		mysql_query("Set names 'utf8'"); 
		
		// $tmp_season
		$tmp_season = ($query_strings->season == null) ? '' : 'and child.season_nr = "'.mysql_real_escape_string($query_strings->season).'"'; // tmp_season
		
		// Help functions
		class run_amt {
			
			// Result to array
			public function add_results($rslt) {
				//~ print_r($rslt);
				$tmp = array();
				if (mysql_num_rows($rslt) == 0) {
					return $tmp; // Blank
				}
				while ($row = mysql_fetch_array($rslt)) {
					$tmp_cast = explode('-_-', $row['cast']); // blank array or filled array
					foreach($tmp_cast as $key=>$cast_mbr) { // Convert names from "Last, First" to "First Last", if person has F/L.
						$tmp_mbr = explode(',', $cast_mbr);
						if (count($tmp_mbr) == 2) { // 2 names
							$cast_mbr = trim($tmp_mbr[1]) .' '. $tmp_mbr[0];
						}
						$tmp_cast[$key] = $cast_mbr;
					}
					$rslt_array = array(
						'id'			=>$row['id'],
						'title'			=>$row['title'],
						'year'			=>$row['year'],
						'season_nr'		=>$row['season_nr'],
						'episode_nr'	=>$row['episode_nr'],
						'keywords'		=>$row['kw'],
						'kind'			=>$row['kind'],
						'parent_id'		=>$row['parent_id'],
						'parent_title'	=>$row['parent_title'],
						'cast'			=>$tmp_cast,
						'score'			=>$row['child_relevancy']
					);
					array_push($tmp, $rslt_array);
				}
				return $tmp;
			}
			
		}
		$run_amt = new run_amt;
		
		
		// search_itunes
		// Order by episode_nr ascending
		if ($query_strings->itunes_search) {
			$tmp_season = ($query_strings->season == null) ? '' : ' and child.season_nr = "'.mysql_real_escape_string($query_strings->season).'"'; // tmp_season
			$s = '
				SELECT 
					child.id as id,
					child.title as title,
					child.production_year as year,
					child.season_nr as season_nr,
					child.episode_nr as episode_nr,
					GROUP_CONCAT(DISTINCT(keyword.keyword)) as kw, #comma separated
					###GROUP_CONCAT(DISTINCT(name.name) separator \'-_-\') as cast,
					kind_type.kind as kind,
					
					parent.title as parent_id,
					parent.title as parent_title
				FROM 
				(
					title child
				)

				LEFT JOIN title as parent on child.episode_of_id = parent.id
				LEFT JOIN 
				(
					movie_keyword, keyword
				) on movie_keyword.movie_id = child.id and movie_keyword.keyword_id = keyword.id
				LEFT JOIN kind_type as kind_type on kind_type.id = child.kind_id
				
				WHERE
					parent.title = "'.mysql_real_escape_string($query_strings->series).'" AND
					child.season_nr = "'.mysql_real_escape_string($query_strings->season).'"
				
				GROUP BY child.id
				ORDER BY child.episode_nr ASC
				LIMIT 100
			'; // AND
					//child.episode_nr = "'.mysql_real_escape_string($query_strings->episode_nr).'"
			$r = mysql_query($s) or die(mysql_error());
			$rslt['search_itunes'] = $run_amt->add_results($r);
			echo json_encode($rslt);
			exit;
		}
		// search_t_s
		// Title + Season# + Series name
		// Title + Season# + kind=episode + Series name ( and kind_type.kind = "episode")
		$tmp_season = ($query_strings->season == null) ? '' : ' and child.season_nr = "'.mysql_real_escape_string($query_strings->season).'"'; // tmp_season
		$s = '
			SELECT 
				MATCH (child.title) AGAINST (\''.mysql_real_escape_string($query_strings->title).'\') as child_relevancy,
				MATCH (parent.title) AGAINST (\''.mysql_real_escape_string($query_strings->series).'\') as parent_relevancy,
				child.id as id,
				child.title as title,
				child.production_year as year,
				child.season_nr as season_nr,
				child.episode_nr as episode_nr,
				GROUP_CONCAT(DISTINCT(keyword.keyword)) as kw, #comma separated
				GROUP_CONCAT(DISTINCT(name.name) separator \'-_-\') as cast,
				kind_type.kind as kind,
				
				parent.title as parent_id,
				parent.title as parent_title
			FROM 
			(
				title child
			)

			LEFT JOIN title as parent on child.episode_of_id = parent.id
			LEFT JOIN 
			(
				movie_keyword, keyword
			) on movie_keyword.movie_id = child.id and movie_keyword.keyword_id = keyword.id
			LEFT JOIN kind_type as kind_type on kind_type.id = child.kind_id
			LEFT JOIN 
			(
				cast_info, name
			) on cast_info.movie_id = child.id and cast_info.person_id = name.id and cast_info.person_id = -1 # -1 IGNORE PEOPLE!
			
			WHERE
				(
					MATCH (child.title) AGAINST (\''.mysql_real_escape_string($query_strings->title).'\') OR
					child.title = \''.mysql_real_escape_string($query_strings->title).'\'
				)
				and 
				(
					MATCH (parent.title) AGAINST (\''.mysql_real_escape_string($query_strings->series).'\') OR
					parent.title = \''.mysql_real_escape_string($query_strings->series).'\'
				)
				'.$tmp_season.'
			
			GROUP BY child.id
			ORDER BY
				child_relevancy DESC,
				parent_relevancy DESC
			LIMIT 8
		';
		$r = mysql_query($s) or die(mysql_error());//echo $s;exit;
		$rslt['search_t_s'] = $run_amt->add_results($r);
		if ($query_strings->one_query) {
			echo json_encode($rslt);
			exit;
		}
		// search_t
		// Title + Season# + kind=episode
		$r = mysql_query(
			'SELECT 
				MATCH (child.title) AGAINST (\''.mysql_real_escape_string($query_strings->title).'\') as child_relevancy,
				child.id as id,
				child.title as title,
				child.production_year as year,
				child.season_nr as season_nr,
				child.episode_nr as episode_nr,
				GROUP_CONCAT(DISTINCT(keyword.keyword)) as kw, #comma separated
				GROUP_CONCAT(DISTINCT(name.name) separator \'-_-\') as cast,
				kind_type.kind as kind,
				
				parent.title as parent_id,
				parent.title as parent_title
			FROM 
			(
				title child
			)

			LEFT JOIN title as parent on child.episode_of_id = parent.id
			LEFT JOIN 
			(
				movie_keyword, keyword
			) on movie_keyword.movie_id = child.id and movie_keyword.keyword_id = keyword.id
			LEFT JOIN kind_type as kind_type on kind_type.id = child.kind_id
			LEFT JOIN 
			(
				cast_info, name
			) on cast_info.movie_id = child.id and cast_info.person_id = name.id
			
			WHERE
				MATCH (child.title) AGAINST (\''.mysql_real_escape_string($query_strings->title).'\') and
				kind_type.kind = "episode" '.$tmp_season.'

			GROUP BY child.id
			ORDER BY
				child_relevancy DESC
			LIMIT 8
			'
		) or die(mysql_error());
		$rslt['search_t'] = $run_amt->add_results($r);
		// search_s
		// child.episode_nr IS NULL and
		// child.season_nr  IS NULL
		$r = mysql_query(
			'SELECT 
				MATCH (child.title) AGAINST (\''.mysql_real_escape_string($query_strings->series).'\') as child_relevancy,
				child.id as id,
				child.title as title,
				child.production_year as year,
				child.season_nr as season_nr,
				child.episode_nr as episode_nr,
				GROUP_CONCAT(DISTINCT(keyword.keyword)) as kw, #comma separated
				GROUP_CONCAT(DISTINCT(name.name) separator \'-_-\') as cast,
				kind_type.kind as kind,
				
				parent.title as parent_id,
				parent.title as parent_title
			FROM 
			(
				title child
			)

			LEFT JOIN title as parent on child.episode_of_id = parent.id
			LEFT JOIN 
			(
				movie_keyword, keyword
			) on movie_keyword.movie_id = child.id and movie_keyword.keyword_id = keyword.id
			LEFT JOIN kind_type as kind_type on kind_type.id = child.kind_id
			LEFT JOIN 
			(
				cast_info, name
			) on cast_info.movie_id = child.id and cast_info.person_id = name.id
			
			WHERE
				MATCH (child.title) AGAINST (\''.mysql_real_escape_string($query_strings->series).'\') and
				child.episode_nr IS NULL and
				child.season_nr  IS NULL
				
			GROUP BY child.id
			ORDER BY
				child_relevancy DESC
			LIMIT 8
			'
		) or die(mysql_error());
		$rslt['search_s'] = $run_amt->add_results($r);
		// search_title_no_opts
		$r = mysql_query(
			'SELECT 
				MATCH (child.title) AGAINST (\''.mysql_real_escape_string($query_strings->title).'\') as child_relevancy,
				child.id as id,
				child.title as title,
				child.production_year as year,
				child.season_nr as season_nr,
				child.episode_nr as episode_nr,
				GROUP_CONCAT(DISTINCT(keyword.keyword)) as kw, #comma separated
				GROUP_CONCAT(DISTINCT(name.name) separator \'-_-\') as cast,
				kind_type.kind as kind,
				
				parent.title as parent_id,
				parent.title as parent_title
			FROM 
			(
				title child
			)

			LEFT JOIN title as parent on child.episode_of_id = parent.id
			LEFT JOIN 
			(
				movie_keyword, keyword
			) on movie_keyword.movie_id = child.id and movie_keyword.keyword_id = keyword.id
			LEFT JOIN kind_type as kind_type on kind_type.id = child.kind_id
			LEFT JOIN 
			(
				cast_info, name
			) on cast_info.movie_id = child.id and cast_info.person_id = name.id
			
			WHERE
				MATCH (child.title) AGAINST (\''.mysql_real_escape_string($query_strings->title).'\')
			
			GROUP BY child.id
			ORDER BY
				child_relevancy DESC
			LIMIT 8
			'
		) or die(mysql_error());
		$rslt['search_title_no_opts'] = $run_amt->add_results($r);
		
		//~ echo '1';
		//~ print_r($rslt);
		//~ // The final result!
		//~ echo json_encode(array());
		echo json_encode($rslt);
		exit;
	}
	//
	// AMT__Ryan_Bubley_company_search
	//
	if ($_GET['AMT__database_select__query'] == 'AMT__Ryan_Bubley_company_search') {
		$str = json_decode($_GET['AMT__database_select__encodeStrings'])[0]; // array
		//echo $str;
		$r = mysql_query(
			'SELECT * FROM AMT_Requester__RyanBubley.companies WHERE MATCH(name) AGAINST (\''.mysql_real_escape_string($str).'\')'
		);
		if (mysql_num_rows($r) == 0) {
			echo '';
			exit;
		}
		// here?
		$out_arr = array();
		while ($row = mysql_fetch_array($r)) {
			array_push($out_arr, [ $row['name'], $row['id_number'] ]);
		}
		echo json_encode($out_arr);
		exit;
	}
	
	//
	// AMT_Requester_Morgan_Intrator
	//
	/*
	Table: schools
	Columns:
	id	int(10) UN PK AI
	code	varchar(20) 
	name	varchar(200) 
	 */
	//~ print_r($_GET);
	if ($_GET['AMT__database_select__query'] == 'AMT__Morgan_Intrator_school_codes') {
		$str = json_decode($_GET['AMT__database_select__encodeStrings'])[0]; // array
		//~ echo $str;
		$r = mysql_query(
			'SELECT * FROM AMT_Requester_Morgan_Intrator.schools WHERE name LIKE "%'.mysql_real_escape_string($str).'%"'
		) or die(mysql_error());
		if (mysql_num_rows($r) == 0) {
			echo '';
			exit;
		}
		// Here?
		$out_arr = array();
		$num = 0;
		while ($row = mysql_fetch_array($r)) { // && $num < 20
			//~ print_r($row);
			array_push($out_arr, [ $row['code'], $row['name'] ]);
			if ($num > 20) break;
			$num++;
		}
		echo json_encode($out_arr);
		exit;
	}
	//
	// Hartti_Suomela_fees_search
	//
	/*
	Table: books
	Columns:
	id	int(11) PK AI
	title	varchar(256) 
	authors	varchar(256) 
	cisbn	varchar(20) 
	cid	int(11) 
	company	varchar(256) */
	//~ print_r($_GET);
	if ($_GET['AMT__database_select__query'] == 'AMT__Hartti_Suomela_fees_search') {
		$str = json_decode($_GET['AMT__database_select__encodeStrings'])[0]; // array
		//~ echo $str;
		$r = mysql_query(
			'SELECT * FROM AMT_Requester_Hartti_Suomela.fees WHERE code=\''.mysql_real_escape_string($str).'\''
		) or die(mysql_error());
		//~ echo mysql_num_rows($r);
		if (mysql_num_rows($r) == 0) {
			echo '';
			exit;
		}
		// here?
		$out_arr = array();
		$num = 0;
		while ($row = mysql_fetch_array($r)) { // && $num < 20
			//~ print_r($row);
			array_push($out_arr, [ $row['code'], $row['description'] ]);
			if ($num > 20) break;
			$num++;
		}
		echo json_encode($out_arr);
		exit;
	}
	
	//
	// Geo__US_Cities
	//
	// SELECT * FROM `Geo__US_Cities`.`city` WHERE MATCH(name) AGAINST ('"Ithaca"') and admin1_code='NY' and (feature_class='P' || feature_class='A');
	// http://www.geonames.org/export/codes.html
	// feature_class =
	//	P => P city, village,... (Misses "Town"/"Village" ???)
	//	A => A country, state, region,... (Gets Town of Ithaca, Village of Ithaca)
	//
	// Differences between INDEX, PRIMARY, UNIQUE, FULLTEXT
	// http://stackoverflow.com/questions/707874/differences-between-index-primary-unique-fulltext-in-mysql
	/*
	Table: city
	Columns:
		id	int(10) UN PK 
		name	varchar(200) 
		asciiname	varchar(200) 
		alternatenames	varchar(8000) 
		latitude	decimal(10,8) 
		longitude	decimal(11,8) 
		feature_class	char(1) 
		feature_code	varchar(10) 
		country_code	varchar(2) 
		cc2	varchar(60) 
		admin1_code	varchar(20) 
		admin2_code	varchar(80) 
		admin3_code	varchar(20) 
		admin4_code	varchar(20) 
		population	bigint(20) UN 
		elevation	int(10) UN 
		dem	varchar(45) 
		timezone	varchar(40) 
		modification_date	varchar(14)  */
	if ($_GET['AMT__database_select__query'] == 'Geo__US_Cities') {
		$cities = json_decode($_GET['AMT__database_select__encodeStrings']); // array of arrays // [ [city,state abbr], [city,state abbr], ... ]
		
		$out_obj = new stdClass;
		
		foreach ($cities as $key => $array_city) {
			$city  = $array_city[0];
			$state = $array_city[1];
			$hash = $city . '|' . $state;
			$out_obj->$hash = array(); // Array of matches ($match objects)
			
			// Exact str match is ("'city'") or ('"city"')
			// Inexact is ("city") or ('city')
			$r = mysql_query('
				SELECT
					name,
					latitude,
					longitude,
						MATCH (name)
						AGAINST
							("'.mysql_real_escape_string($city).'")
						AS score,
					population
				FROM
					`Geo__US_Cities`.`city`
				WHERE 
					MATCH (name)
				AGAINST
					("'.mysql_real_escape_string($city).'") and
					admin1_code=\''.mysql_real_escape_string($state).'\' and
					(feature_class=\'P\' || feature_class=\'A\')
				ORDER BY
					score DESC,
					population DESC
				LIMIT 10
				
			') or die(mysql_error());
			
			if (mysql_num_rows($r) == 0) {
				continue;
			}
			
			// Here?
			while ($row = mysql_fetch_array($r)) {
				//~ print_r($row);
				
				$match = new stdClass;
				$match->name = $row['name'];
				$match->lat = $row['latitude'];
				$match->lon = $row['longitude'];
				//~ $match->SCR = $row['score']; //debug
				//~ $match->POP = $row['population']; //debug
				
				array_push($out_obj->$hash, $match);
			}
		}
		
		echo json_encode($out_obj);
		exit;
	}
	
	//
	// AMT__Sol_Rosenberg_book_search
	//
	/*
	Table: books
	Columns:
	id	int(11) PK AI
	title	varchar(256) 
	authors	varchar(256) 
	cisbn	varchar(20) 
	cid	int(11) 
	company	varchar(256) */
	//~ print_r($_GET);
	if ($_GET['AMT__database_select__query'] == 'AMT__Sol_Rosenberg_book_search') {
		$str = json_decode($_GET['AMT__database_select__encodeStrings'])[0]; // array
		//~ echo $str;
		$r = mysql_query(
			'SELECT * FROM AMT_Requester_Sol_Rosenberg.books WHERE MATCH(title) AGAINST (\''.mysql_real_escape_string($str).'\')'
		) or die(mysql_error());
		//~ echo mysql_num_rows($r);
		if (mysql_num_rows($r) == 0) {
			echo '';
			exit;
		}
		// here?
		$out_arr = array();
		$num = 0;
		while ($row = mysql_fetch_array($r)) { // && $num < 20
			//~ print_r($row);
			array_push($out_arr, [ $row['title'], $row['authors'], $row['cisbn'], $row['cid'], $row['company'] ]);
			if ($num > 20) break;
			$num++;
		}
		echo json_encode($out_arr);
		exit;
	}
	
	function AMT_Requester__Superfish_clean($s) {
		$md5s_str = str_replace('/[^\w\d,]/g', '', $s); // Just in case. This is mysql_clean...
		$md5s_str = explode(',', $md5s_str);
		foreach ($md5s_str as $k=>$v) {
			$md5s_str[$k] = '"'.$v.'"'; // enclose in quotation marks
		}
		$md5s_str = implode(',', $md5s_str);
		return $md5s_str;
	}
	//
	// AMT_Requester__Superfish_write
	//
	if ($_POST['AMT__database_write__query'] == 'AMT_Requester__Superfish_write') {
		$arr = json_decode($_POST['AMT__database_write__encodeStrings']); // array
		$if  = AMT_Requester__Superfish_clean($arr[0]); // flag
		$inf = AMT_Requester__Superfish_clean($arr[1]); // not flag
		
		// flag
		$qry = 'UPDATE AMT_Requester__Superfish.flag_images
					SET
						num_choices=num_choices+1,
						choice=1
					WHERE
						img_src_md5 IN ('.$if.')';
		$r = mysql_query(
			$qry
		) or die(mysql_error());
		// not flag
		$qry = 'UPDATE AMT_Requester__Superfish.flag_images
					SET
						num_choices=num_choices+1,
						choice=0
					WHERE
						img_src_md5 IN ('.$inf.')';
		$r = mysql_query(
			$qry
		) or die(mysql_error());
		exit;
	}
	//
	// AMT_Requester__Superfish_select
	//
	if ($_GET['AMT__database_select__query'] == 'AMT_Requester__Superfish_select') { //
		$str = json_decode($_GET['AMT__database_select__encodeStrings']); // array [all md5=str, category]
		$md5s_str = $str[0];
		$category = $str[1];
		//~ echo $md5s_str;
		$md5s_str = str_replace('/[^\w\d,]/g', '', $md5s_str); // Just in case. This is mysql_clean...
		$md5s_str = explode(',', $md5s_str);
		foreach ($md5s_str as $k=>$v) {
			$md5s_str[$k] = '"'.$v.'"'; // enclose in quotation marks
		}
		$md5s_str = implode(',', $md5s_str);
		
		// Build associative array
		$md5s = explode(',' , $str[0]);
		$md5_asc = array(); // assoc.
		foreach ($md5s as $k => $v) {
			$md5_asc[$v] = true;
		}
		
		// SELECT
		$r = mysql_query(
			'SELECT * FROM AMT_Requester__Superfish.flag_images
				WHERE
					category="'.mysql_real_escape_string($category).'" &&
					img_src_md5 IN ('.$md5s_str.')'
		) or die(mysql_error());
		
		//
		$out_arr = array();
		if (mysql_num_rows($r) > 0) {
			while ($row = mysql_fetch_array($r)) {
				array_push($out_arr, [ $row['img_src_md5'], $row['choice'], $row['num_choices'] ]);
				if ($md5_asc[$row['img_src_md5']])
					unset($md5_asc[$row['img_src_md5']]); // UNSET TO REMOVE KEY
				
				//~ if (!$md5_asc[$row['img_src_md5']])
					//~ array_push($not_found_arr, $row['img_src_md5']);
			}
		}
		
		// echo
		echo json_encode($out_arr);
		
		// FOR ALL OF THE IMAGES NOT IN THE DATABASE
		// INSERT A NEW ROW INTO THE DB!
		// This is by KEY!
		// Default choice = 0
		foreach ($md5_asc as $md5 => $v) { 
			$qry = 'INSERT INTO AMT_Requester__Superfish.flag_images
						SET
							img_src_md5="'.mysql_real_escape_string($md5).'",
							category="'.mysql_real_escape_string($str[1]).'",
							choice=0,
							num_choices=0';
			$r = mysql_query(
				$qry
			) or die(mysql_error());
		}
			
		
		exit;
		/*
		Table: flag_images
		Columns:
		id	int(11) PK AI
		img_src_md5	varchar(80) 
		category	varchar(100) 
		choice	tinyint(4) 
		num_choices	int(11)*/
	}
	
	//
	// AMT_Requester__DHG_images_write
	//
	/**
	 * Table: images
		Columns:
		id	int(10) UN PK AI
		unique_id	varchar(30) 
		is_watermarked	varchar(45) 
		is_404	bit(1) 
		num_human_decisions	int(11)
		*/
	class AMT_Requester__DHG_images
	{
		
		// Array
		public function ids_db_str($ids) {
			$ids_db = array();
			foreach ($ids as $k=>$v) {
				$ids_db[$k] = '"'.mysql_real_escape_string($v).'"'; // enclose in quotation marks
			}
			$ids_db = implode(',', $ids_db);
			return $ids_db;
		}
		
		// Array (assoc.)
		public function images_associative($images) {
			$images_asc = array(); // assoc.
			foreach ($images as $idx => $image) {
				$images_asc[ $image->id ] = $image;
			}
			return $images_asc;
		}
		
		public function select_in_unique($ids_db) {
			// SELECT
			$r = mysql_query('
				SELECT
					unique_id,
					is_watermarked,
					is_404,
					num_human_decisions
				FROM
					AMT_Requester__DHG_images.images
				WHERE
					unique_id IN ('.$ids_db.')
			') or die(mysql_error());
			return $r;
		}
	}
	if ($_POST['AMT__database_write__query'] == 'AMT_Requester__DHG_images_write') {
		
		$str = json_decode($_POST['AMT__database_write__encodeStrings']); // object
		
		$dg = new AMT_Requester__DHG_images;
		
		$ids_watermarked		= $str->ids_watermarked;		// array strings
		$ids_not_watermarked	= $str->ids_not_watermarked;	// array strings
		
		$ids_w_db  = $dg->ids_db_str($ids_watermarked);		// string mysql-escaped, e.g.> "albu_288051587_00","albu_288052237_00","albu_288051778_00", ...
		$ids_nw_db = $dg->ids_db_str($ids_not_watermarked);	// string mysql-escaped
		
		//~ echo $ids_w_db . '||';
		//~ echo $ids_nw_db;
		//~ exit;
		
		// is wm
		if ($ids_w_db != '') {
			$qry = '
				UPDATE
					AMT_Requester__DHG_images.images
				SET
					num_human_decisions=num_human_decisions+1,
					is_watermarked=1
				WHERE
					unique_id IN ('.$ids_w_db.')';
			$r = mysql_query(
				$qry
			) or die(mysql_error());
		}
		
		// not wm
		if ($ids_nw_db != '') {
			$qry = '
				UPDATE
					AMT_Requester__DHG_images.images
				SET
					num_human_decisions=num_human_decisions+1,
					is_watermarked=0
				WHERE
					unique_id IN ('.$ids_nw_db.')';
			$r = mysql_query(
				$qry
			) or die(mysql_error());
		}
		exit;
	}
	//
	// AMT_Requester__DHG_images_select
	//
	if ($_GET['AMT__database_select__query'] == 'AMT_Requester__DHG_images_select') { //
		
		$str = json_decode($_GET['AMT__database_select__encodeStrings']);
		//~ print_r($str);exit;
		
		$dg = new AMT_Requester__DHG_images;
		
		//~ encodeStrings: {
			//~ images: o.images,
			//~ ids: o.images_ids,
			//~ urls: o.images_urls
		//~ }
		
		// object [ ids, urls ]
		$ids = $str->ids; // array
		$urls = $str->urls; // array
		$images = $str->images; // array of objects
		
		$ids_db = $dg->ids_db_str($ids); // array of mysql-escaped
		
		$images_asc = $dg->images_associative($images); // associative array
		
		$rslt = $dg->select_in_unique($ids_db);
		
		//
		$out_arr = array();
		if (mysql_num_rows($rslt) > 0) {
			while ($row = mysql_fetch_array($rslt)) {
				array_push($out_arr,
					
						$row //['img_src_md5'], $row['choice'], $row['num_choices']
					
				);
				if ($images_asc[$row['unique_id']])
					unset($images_asc[$row['unique_id']]); // UNSET TO REMOVE KEY
				
				//~ if (!$md5_asc[$row['img_src_md5']])
					//~ array_push($not_found_arr, $row['img_src_md5']);
			}
		}
		
		// For all new images:
		// Download the image.
		// Check for !=200 and/or 'not found'.
		// Write initial value to DB.
		// NOTE1: The images at this point ARE unique. This
		// is good. =) It is because they are currently in an
		// object which uses the unique image ID for its
		// key values.
		
		// Function curl_get, returns (->html, ->status)
		
		// FOR ALL OF THE IMAGES NOT IN THE DATABASE
		// INSERT A NEW ROW INTO THE DB!
		
		foreach ($images_asc as $unique_id => $image) { 
			
			
			$u = new stdClass;
			$u->url = $image->url;
			$rslt = curl_get($u);
			
			$is_404 = ($rslt->status != 200 || trim($rslt->html) == 'not found') ? 1 : 0;
			
			$qry = 'INSERT INTO
						AMT_Requester__DHG_images.images
					SET
						unique_id="'.mysql_real_escape_string($unique_id).'",
						is_watermarked=0,
						is_404='.$is_404.',
						num_human_decisions=0';
			$r = mysql_query(
				$qry
			) or die(mysql_error());
		}
		
		// Echo result from first query.
		echo json_encode($out_arr);
		
		exit;
	}
	//
	// AMT_Requester__correct_tasks
	// OLD NAME IS AMT_Requester__Stephanie_Hass (AMT_Requester__Stephanie_Hass_write) (AMT_Requester__Stephanie_Hass_select)
	//
	/**
	 * Table:
		id	int(10) UN PK AI
		hit_id	varchar(60) 
		locked_timestamp	int(10) UN 
		correct_value	text 
		assignment_id	varchar(60) 
		*/
	class AMT_Requester__correct_tasks
	{
		// 
		public function write($hit_id, $value) {
			$r = mysql_query('
				UPDATE
					AMT_Alerts.HIT_ids_to_correct
				SET
					correct_value="'.mysql_real_escape_string($value).'"
				WHERE
					hit_id="'.mysql_real_escape_string($hit_id).'"
				LIMIT 1
			') or die(mysql_error());
		}
		
		// Returns HIT id OR false
		public function next_blank_row() {
			// Remove old entries
			// @10 Minutes.
			$r = mysql_query('
				UPDATE
					AMT_Alerts.HIT_ids_to_correct
				SET
					locked_timestamp=NULL
				WHERE
					locked_timestamp<UNIX_TIMESTAMP()-60
			') or die(mysql_error());
			
			// SELECT
			$r = mysql_query('
				SELECT
					hit_id
				FROM
					AMT_Alerts.HIT_ids_to_correct
				WHERE
					locked_timestamp IS NULL &&
					correct_value IS NULL
				LIMIT 1
			') or die(mysql_error()); // xxx assignment_id="3SKEMFQBZ4FQJE4CBNF8VZ8QR9O8KN" &&
			if (mysql_num_rows($r) == 0) return false;
			
			// Lock this one
			$i = mysql_fetch_array($r);
			$hit_id = $i['hit_id'];
			$r = mysql_query('
				UPDATE
					AMT_Alerts.HIT_ids_to_correct
				SET
					locked_timestamp=UNIX_TIMESTAMP()
				WHERE
					hit_id="'.mysql_real_escape_string($hit_id).'"
				LIMIT 1
			') or die(mysql_error());
			
			// Return hit id
			return $hit_id;
		}
	}
	// AMT_Requester__correct_tasks
	// 
	//
	if ($_POST['AMT__database_write__query'] == 'AMT_Requester__correct_tasks_write') {
		
		$str = json_decode($_POST['AMT__database_write__encodeStrings']); // object
		//~ print_r($str);return;
		$sh = new AMT_Requester__correct_tasks;
		
		$sh->write($str->hit_id, $str->correct_value);
		
		exit;
	}
	// AMT_Requester__correct_tasks
	// 
	//
	if ($_GET['AMT__database_select__query'] == 'AMT_Requester__correct_tasks') { // SELECT
		$sh = new AMT_Requester__correct_tasks;
		$hit_id = $sh->next_blank_row();
		if ($hit_id)
			echo json_encode(array('hit_id'=>$hit_id));
		exit;
	}
	
	//
	// AMT__Colby_Vorland_food_search
	//
	if ($_GET['AMT__database_select__query'] == 'AMT__Colby_Vorland_food_search') {
		$str = json_decode($_GET['AMT__database_select__encodeStrings'])[0]; // array
		//echo $str;
		$r = mysql_query(
			'SELECT * FROM AMT_Requester__ColbyVorland.foods WHERE MATCH(name) AGAINST (\''.mysql_real_escape_string($str).'\')'
		);
		if (mysql_num_rows($r) == 0) {
			echo '';
			exit;
		}
		// here?
		$out_arr = array();
		while ($row = mysql_fetch_array($r)) {
			array_push($out_arr, [ $row['name'], $row['id_number'] ]);
		}
		echo json_encode($out_arr);
		exit;
	}
	
	//
	// AMT_Requester__Scott_Neufeld
	//
	if ($_GET['AMT__database_select__query'] == 'AMT_Requester__Scott_Neufeld') {
		$arr = json_decode($_GET['AMT__database_select__encodeStrings']); // array
		//~ print_r($arr);exit;
		$qry = 'SELECT * FROM AMT_Requester__Scott_Neufeld.places WHERE MATCH(FEATURE_NAME) AGAINST ("'.mysql_real_escape_string($arr[0]).'")';
		if ($arr[1] != '')
			$qry .= ' && STATE_ALPHA="'.mysql_real_escape_string($arr[1]).'"';
			
		//~ for ($i = 0; $i = count($arr) - 1; $i++) {
			//~ switch($i) {
				//~ case 0:
					//~ $qry = 'SELECT * FROM AMT_Requester__Scott_Neufeld.places WHERE MATCH(FEATURE_NAME) AGAINST (\''.mysql_real_escape_string($arr[$i]).'\')';
					//~ break;
				//~ case 1:
					//~ if ($arr[$i] != '')
						//~ $qry .= ' && STATE_ALPHA="'.mysql_real_escape_string($arr[$i]).'"';
					//~ break;
				//~ case 2:
					//~ break;
				//~ case 3:
					//~ break;
			//~ }
			//~ exit;
		//~ }
		//~ echo $qry;exit;
		
		$r = mysql_query(
			$qry
		);
		if (mysql_num_rows($r) == 0) {
			echo '';
			exit;
		}
		// here?
		$out_arr = array();
		while ($row = mysql_fetch_array($r)) {
			array_push($out_arr,
				[
					$row['FEATURE_ID'], $row['FEATURE_NAME'], $row['STATE_ALPHA'], $row['COUNTY_NAME'], $row['Population'], $row['PRIMARY_LATITUDE'], $row['PRIMARY_LONGITUDE']
				]
			);
		}
		echo json_encode($out_arr);
		exit;
	}
	
	
	//
	// Work__Procore_LinkedIn
	//
	/*people.
		Contact_ID
		Account_Name
		First_Name
		Last_Name
		Title
		Mailing_City
		Mailing_StateProvince
		Mailing_Country
		Account_Owner
		Crawler_Linkedin_Profile_URL
		FLBS_LI_CompanyID
		FLBS_Mailing_ZipCode
		FLBS_num_choices
		id
		*/
	
	// Next row for resolution
	// Note:This is where 199, 299, 399 will go.
	//
	if ($_GET['AMT__database_select__query'] == 'Work__Procore_LinkedIn_next_row_to_resolve') {
		$arr = json_decode($_GET['AMT__database_select__encodeStrings']); // array
		$px = $arr[0];
		//$px = $arr[1];
		/* id NOT IN(
					SELECT people_id FROM FLBS__Procore_LinkedIn.profile_choices WHERE person_choosing='.mysql_real_escape_string($px).'
				) &&
				*/
		$qry = 'SELECT * FROM FLBS__Procore_LinkedIn.people WHERE FLBS_num_choices >= 2 &&
					Crawler_Linkedin_Profile_URL = ""
				LIMIT 1';
		$r = mysql_query(
			$qry
		);
		if (mysql_num_rows($r) == 0) {
			echo '';
			exit;
		}
		// here?
		$out_arr = mysql_fetch_array($r);
		echo json_encode($out_arr);
		exit;
	}
	
	// Next row in db
	// FLBS_locked_user_id == ""
	// Use an UPDATE query here.
	// Note: Only ONE user is allowed to access a specific .people row at any one time.
	// This is initiated when FLBS_locked_user_id="userX". This ends when...?
	// Note: FLBS_locked_user_id IS NULL
	//
	if ($_GET['AMT__database_select__query'] == 'Work__Procore_LinkedIn_next_row_in_db') {
		$arr = json_decode($_GET['AMT__database_select__encodeStrings']); // array
		$px = $arr[0];
		
		// This is for 10 minutes ago, remove any locks.
		$timeMinus10 = time() - 600; // 10 minutes
		
		// UPDATE people (remove old)
		$qry = 'UPDATE FLBS__Procore_LinkedIn.people
				SET
					FLBS_locked_user_id = NULL
				WHERE
					FLBS_locked_user_id = '.mysql_real_escape_string($px).' OR
					FLBS_locked_user_id_timestamp < '.mysql_real_escape_string($timeMinus10);
		$r = mysql_query(
			$qry
		);
		
		// UPDATE people (new person)
		$qry = 'UPDATE FLBS__Procore_LinkedIn.people
				SET
					FLBS_locked_user_id = '.mysql_real_escape_string($px).',
					FLBS_locked_user_id_timestamp = '.mysql_real_escape_string(time()).'
				WHERE
					id NOT IN(
						SELECT people_id FROM FLBS__Procore_LinkedIn.profile_choices WHERE person_choosing='.mysql_real_escape_string($px).'
					) &&
					FLBS_num_choices < 2 &&
					FLBS_locked_user_id IS NULL
				LIMIT 1';  //echo $qry;exit;
		$r = mysql_query(
			$qry
		);
		if (mysql_affected_rows() == 0) { // NO CHANGE MADE!
			echo '';
			exit;
		}
		
		// Here?
		// SELECT the person's row
		$qry = 'SELECT * FROM FLBS__Procore_LinkedIn.people WHERE FLBS_locked_user_id = '.mysql_real_escape_string($px);
		$r = mysql_query(
			$qry
		);
		$out_arr = mysql_fetch_array($r);
		echo json_encode($out_arr);
		exit;
	}
	
	/**
	 * Resolve conflict
	 * 
	 */
	if ($_POST['AMT__database_write__query'] == 'Work__Procore_LinkedIn_resolve_conflict') {
		$arr = json_decode($_POST['AMT__database_write__encodeStrings']); // array
		$people_id = $arr[0];
		$linkedin_url = $arr[1];
		$qry = 'UPDATE FLBS__Procore_LinkedIn.people SET Crawler_Linkedin_Profile_URL="'.mysql_real_escape_string($linkedin_url).'" WHERE id="'.mysql_real_escape_string($people_id).'"';
		$r = mysql_query(
			$qry
		) or die(mysql_error());
		exit;
	}
	
	
	/**
	 * Add choice
	 *  Insert new choice, .profile_choices
	 *  Update .people
	 *   OR
	 *  Update choice, .profile_choices
	 *  a,b, or c
	 *   a-Set final choice
	 *   b-Return opposing choice
	 *   c-Goto next row
	 */
	/**
	 * Function Work__Procore_LinkedIn__set_final_choice
	 * Add choice
	 * @POST
	 * [people_id, person_choosing, linkedin_url, linkedin_id, secondary_linkedin_urls, json__search_variables_upon_selection]
	 */
	function Work__Procore_LinkedIn__set_final_choice($people_id, $linkedin_url) {
		$qry = 'UPDATE FLBS__Procore_LinkedIn.people
				SET
					Crawler_Linkedin_Profile_URL="'.mysql_real_escape_string($linkedin_url).'"
				WHERE
					id="'.mysql_real_escape_string($people_id).'"';
		$r = mysql_query(
			$qry
		) or die(mysql_error());
	}
	/**
	 * Function Work__Procore_LinkedIn__do_results_match
	 * 
	 */
	function Work__Procore_LinkedIn__do_results_match($people_id, $person_choosing) {
		
		$qry = 'SELECT * FROM FLBS__Procore_LinkedIn.profile_choices
				WHERE
					people_id="'.mysql_real_escape_string($people_id).'" &&
					person_choosing != 9999';
		$r = mysql_query(
			$qry
		) or die(mysql_error());
		
		$last = '';
		$match = true;
		$other_person_json = array();
		while ($row = mysql_fetch_array($r)) {
			if ($last != '' && $last != $row['linkedin_id']) {
				$match = false;
			}
			
			$last = $row['linkedin_id'];
			
			if ($row['person_choosing'] != $person_choosing) {
				$other_person_rslt['id'] = $row['id'];
				$other_person_rslt['people_id'] = $row['people_id'];
				$other_person_rslt['person_choosing'] = $row['person_choosing'];
				$other_person_rslt['linkedin_url'] = $row['linkedin_url'];
				$other_person_rslt['linkedin_id'] = $row['linkedin_id'];
				$other_person_rslt['secondary_linkedin_urls'] = $row['secondary_linkedin_urls'];
				$other_person_rslt['json__search_variables_upon_selection'] = $row['json__search_variables_upon_selection'];
				
				//~ $other_person_json = $row['json__search_variables_upon_selection'];
				
				//~ $other_person_json = $row;
			}
		}
		if ($match) {
			return ['match'=>1]; //, $last, 'json'=>$other_person_json
		}
		else
		{
			return ['match'=>0, 'other_person_rslt'=>json_encode($other_person_rslt)];
		}
	}
	// IF
	if ($_POST['AMT__database_write__query'] == 'Work__Procore_LinkedIn_add_choice') {
		$arr = json_decode($_POST['AMT__database_write__encodeStrings']); // array
		$people_id		= $arr[0];
		$person_choosing= $arr[1];
		$linkedin_url	= $arr[2];
		
		// Insert into profile_choices !!
		// However, if userX already made a decision on this,
		// then just update it!
		// Note: If updating profile_choices then do NOT increment
		// people.FLBS_num_choices!
		$qry = 'SELECT id FROM FLBS__Procore_LinkedIn.profile_choices
				WHERE
					people_id="'.mysql_real_escape_string($arr[0]).'" &&
					person_choosing="'.mysql_real_escape_string($arr[1]).'"';
		$r = mysql_query(
			$qry
		);
		//~ echo mysql_num_rows($r);
		if (mysql_num_rows($r) < 1)
		{	
			// INSERT NEW CHOICE!
			$qry = 'INSERT INTO FLBS__Procore_LinkedIn.profile_choices SET
						people_id								="'.mysql_real_escape_string($arr[0]).'",
						person_choosing							="'.mysql_real_escape_string($arr[1]).'",
						linkedin_url							="'.mysql_real_escape_string($arr[2]).'",
						linkedin_id								="'.mysql_real_escape_string($arr[3]).'",
						secondary_linkedin_urls					="'.mysql_real_escape_string($arr[4]).'",
						json__search_variables_upon_selection	="'.mysql_real_escape_string($arr[5]).'"
				';
			$r = mysql_query(
				$qry
			) or die(mysql_error());
			
			// UPDATE THE people TABLE !!
			// Increment. This always occurs UNLESS the user is 9999
			// in which case it is cache. Don't increment.
			if ($person_choosing != '9999') {
				$qry = 'UPDATE FLBS__Procore_LinkedIn.people SET FLBS_num_choices = FLBS_num_choices+1 WHERE id="'.mysql_real_escape_string($people_id).'"';
				$r = mysql_query(
					$qry
				) or die(mysql_error());
			}
		}
		else // UPDATE THE CHOICE!
		{
			$row = mysql_fetch_array($r);
			$qry = 'UPDATE FLBS__Procore_LinkedIn.profile_choices SET
						people_id								="'.mysql_real_escape_string($arr[0]).'",
						person_choosing							="'.mysql_real_escape_string($arr[1]).'",
						linkedin_url							="'.mysql_real_escape_string($arr[2]).'",
						linkedin_id								="'.mysql_real_escape_string($arr[3]).'",
						secondary_linkedin_urls					="'.mysql_real_escape_string($arr[4]).'",
						json__search_variables_upon_selection	="'.mysql_real_escape_string($arr[5]).'"
					WHERE
						id='.$row['id'];
			$r = mysql_query(
				$qry
			) or die(mysql_error());
		}
		
		// Response after selection.
		// Send back blank array [] if there is a match.
		// Else send back the full row of the other person's choice.
		//  @Work__Procore_LinkedIn__do_results_match
		//   Returns full row
		$qry = 'SELECT * FROM FLBS__Procore_LinkedIn.people WHERE id="'.mysql_real_escape_string($people_id).'" && FLBS_num_choices >= 2';
		$r = mysql_query(
			$qry
		) or die(mysql_error());
		if (mysql_num_rows($r) > 0) { // Indicates completed
			
			// CHECK IF THE RESULTS MATCH
			$x = Work__Procore_LinkedIn__do_results_match($people_id, $person_choosing); //?
			
			if ($x['match'] == '1')
			{
				echo '[]';
				Work__Procore_LinkedIn__set_final_choice($people_id, $linkedin_url); // update Crawler_Linkedin_Profile_URL !
			}
			else 
			{	
				// Send back the full choice row of the other person. 
				// The row is JSON encoded.
				echo $x['other_person_rslt'];
			}
		}
		else
		{
			echo '[]';
		}
		exit;
	}
	
	// States
	//
	//
	if ($_GET['AMT__database_select__query'] == 'Work__Procore_LinkedIn_States') {
		$arr = json_decode($_GET['AMT__database_select__encodeStrings']); // array
		$id = $arr[0];
		$type = $arr[1];
		
		if ($type == 0)		// read
		{
			$qry = 'SELECT Mailing_City,Mailing_StateProvince,Mailing_Country FROM FLBS__Procore_LinkedIn.people WHERE id="'.mysql_real_escape_string($id).'" && Mailing_Country=""';
			$r = mysql_query(
				$qry
			);
			if (mysql_num_rows($r) == 0) {
				echo '';
				exit;
			}
			// here?
			$out_arr = array();
			while ($row = mysql_fetch_array($r)) {
				$out_arr = array($id, $row['Mailing_City'], $row['Mailing_StateProvince'], $row['Mailing_Country']);
			}
			echo json_encode($out_arr);
			exit;
		}
		else 				// write!
		{
			$qry = 'UPDATE FLBS__Procore_LinkedIn.people SET Mailing_Country="United States" WHERE id="'.mysql_real_escape_string($id).'" && Mailing_Country=""';
			$r = mysql_query(
				$qry
			) or die(mysql_error());
			exit;
		}
	}
	// Zips
	//
	//
	if ($_GET['AMT__database_select__query'] == 'Work__Procore_LinkedIn_Zipcode') {
		$arr = json_decode($_GET['AMT__database_select__encodeStrings']); // array
		$id = $arr[0];
		$type = $arr[1];
		
		if ($type == 0)		// read
		{
			$qry = 'SELECT Mailing_City,Mailing_StateProvince,Mailing_Country FROM FLBS__Procore_LinkedIn.people
						WHERE
							id="'.mysql_real_escape_string($id).'" &&
							Mailing_City != "" &&
							Mailing_StateProvince != "" &&
							FLBS_Mailing_ZipCode = ""';
			$r = mysql_query(
				$qry
			);
			if (mysql_num_rows($r) == 0) {
				echo '';
				exit;
			}
			// here?
			$out_arr = array();
			while ($row = mysql_fetch_array($r)) {
				$out_arr = array($id, $row['Mailing_City'], $row['Mailing_StateProvince'], $row['Mailing_Country']);
			}
			echo json_encode($out_arr);
			exit;
		}
		else 				// write!
		{
			$zip = $arr[2];
			$qry = 'UPDATE FLBS__Procore_LinkedIn.people SET FLBS_Mailing_ZipCode="'.mysql_real_escape_string($zip).'" WHERE id="'.mysql_real_escape_string($id).'"';
			$r = mysql_query(
				$qry
			) or die(mysql_error());
			exit;
		}
	}
}
//
// server side convert
//
if (isset($_GET['AMT__im_convert__folder']) &&
	isset($_GET['AMT__im_convert__old_file']) &&
	isset($_GET['AMT__im_convert__new_file'])) { // write to file!
	if  (
		$_GET['AMT__im_convert__folder'] != '.miscellaneous' &&
		$_GET['AMT__im_convert__folder'] != '.cached'
		)
		return;
	
	$f = '/var/www/html/mturk/'.$_GET['AMT__im_convert__folder'].'/'.str_replace('/', '', $_GET['AMT__im_convert__old_file']);
	$f2= '/var/www/html/mturk/'.$_GET['AMT__im_convert__folder'].'/'.str_replace('/', '', $_GET['AMT__im_convert__new_file']);
	
	$output = shell_exec('convert "'.$f.'" "'.$f2.'"');
	echo $output;
	
	chmod($f2, 0777);
	exit;
}
// Server side write to file!
// 
// Notes:
// 		Remove certain characters from filename.
if (isset($_POST['AMT__serverSide_write__value']) &&
	isset($_POST['AMT__serverSide_write__folder']) &&
	isset($_POST['AMT__serverSide_write__file'])) {
	if  (
		$_POST['AMT__serverSide_write__folder'] != '.miscellaneous' &&
		$_POST['AMT__serverSide_write__folder'] != '.cached'
		)
		return;
	if (isset($_POST['AMT__serverSide_write__opts'])) {
		$opts = json_decode($_POST['AMT__serverSide_write__opts']);
	} else {
		$opts = new StdClass;
	}
	if (!property_exists($opts, 'append')) {
		$opts->append = false;
	}
	
	$f = '/var/www/html/mturk/'.$_POST['AMT__serverSide_write__folder'].'/'.
		preg_replace('/[^a-z0-9_\-\.]/i', '', $_POST['AMT__serverSide_write__file']);
	$flags = LOCK_EX;
	if ($opts->append) {
		$flags = LOCK_EX | FILE_APPEND;
	}
	$x = file_put_contents(
		$f,
		$_POST['AMT__serverSide_write__value'],
		$flags);
	// APPARENTLY THIS ONLY WORKS **RELIABLY** WITH LOCK_EX!!!!!!
	// Otherwise this was only occasionally working, and mostly failing!!!
	if (!$x) {
		echo 'failed:' . $x;
	} else {
		chmod($f, 0777);
		echo 'success';
	}
	exit;
}
// Server side download file!
//
//
if (isset($_GET['AMT__download_file__url']) &&
	isset($_GET['AMT__download_file__folder']) &&
	isset($_GET['AMT__download_file__filename'])) { // 
	if  (
		$_GET['AMT__download_file__folder'] != '.miscellaneous' &&
		$_GET['AMT__download_file__folder'] != '.cached'
		)
		return;
	
	$f = '/var/www/html/mturk/'.$_GET['AMT__download_file__folder'].'/'.str_replace('/', '', $_GET['AMT__download_file__filename']);
	
	$ch = curl_init($_GET['AMT__download_file__url']);
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
		echo $x;
	} else {
		// OLD // THIS WAS FOR YAHOO SEARCHES.
		//~ // This is for YAHOO!
		//~ //
		//~ //
		//~ //
		//~ $r = preg_replace_callback(
			//~ '/yschttl spt" href="[^*]+[*]+([^"]+)"/',
			//~ function($matches) {
				//~ return 'yschttl spt" href="'.urldecode($matches[1]).'"';
			//~ },
			//~ $r
		//~ );
		
		//...
		file_put_contents($f, $r);
		chmod($f, 0777);
		//~ echo $r;
		echo $r;
	}
	exit;
	
	// Old! As of June 18 2014.
	//
	//
	//
	//~ $url	= file_get_contents($_GET['AMT__download_file__url']);
	//~ $x		= file_put_contents($f, $url);
	//~ if (!$x) {
		//~ echo 'x' . file_put_contents($f, $url);
	//~ } else {
		//~ chmod($f, 0777);
	//~ }
	//~ exit;
}
//
// 
//
if (isset($_GET['AMT__dominantColor_colorThief'])) { // as in iframe
	include '.scripts/ColorThiefPHP.php';
	$dominantColor = ColorThiefPHP::getColor($_GET['AMT__dominantColor_colorThief']);
	
	//ColorThiefPHP::getColor($sourceImage[, $quality=10]) 
	//returns array(r: num, g: num, b: num)
	
	echo json_encode($dominantColor);
	exit;
}
//
// 
//
if (isset($_GET['AMT__displayGetStrAsHTML'])) { // as in iframe
	echo $_GET['AMT__displayGetStrAsHTML'];
}
//
// 
//
if (isset($_POST['AMT__displayPostStrAsHTML'])) { // as in iframe
	//echo $_POST['AMT__displayPostStrAsHTML'];
	$fn = '.cached/' . md5($_POST['AMT__displayPostStrAsHTML']);
	file_put_contents($fn, $_POST['AMT__displayPostStrAsHTML']);
	echo 'http://192.168.1.2/mturk/' . $fn;
}
/**
 * AMT__proxy_resolve_URL
 */
if (isset($_REQUEST['AMT__proxy_resolve_URL'])) { // new
	$opts = json_decode($_REQUEST['AMT__proxy_resolve_URL']);
	$f = '/var/www/html/mturk/.cached/'.md5($opts->URL);
	if (file_exists($f)) { // Cached results.....
		echo file_get_contents($f); 
		exit;
	}
	if ($opts->check_complete) { // Checking for complete. Exit here.....
		exit;
	}
	
	// Kill browser session....
	//global__kill_browser_session();
	
	// Resolve....
	require_once('/var/www/html/mturk/.scripts/URL_Resolver/URLResolver.php');
	$resolver = new URLResolver();
	$resolver->setRequestTimeout(120);
	echo $resolver->set_redacted_Proxy();
	echo $resolver->resolveURL($opts->URL)->getURL();
	if (strlen($resolver->resolveURL($opts->URL)->getURL()) > 6) {
		file_put_contents($f, $resolver->resolveURL($opts->URL)->getURL());
		chmod($f, 0777);
	}
}
/**
 * AMT__output_html_from_URL
 * 
 * @ short_filename:				string	...
 * @ strings_do_not_save_to_file:	array	Do not save to file if these exist.
 * 
 * NEW: APRIL 29, 2015. CHANGE TO $_REQUEST.
 * 
 * 
 * 
 * 
 */
if (isset($_REQUEST['AMT__output_html_from_URL'])) {
	
	// Opts
	// $_REQUEST['AMT__output_html_from_URL__options']
	$opts = new stdClass; // Set $opts to a blank object.
	//AMT__output_html_from_URL__options
	$opts->short_filename = false;
	$opts->replace_absolute_url = false;
	$opts->replace_scripts = false;
	$opts->htmlspecialchars = false;
	$opts->post_object = false;
	$opts->post_object_as_str = false;
	$opts->headers_array = false;

	if (isset($_REQUEST['AMT__output_html_from_URL__options'])) {
		$o_ = json_decode($_REQUEST['AMT__output_html_from_URL__options']);
		if (property_exists($o_, 'short_filename')) {$opts->short_filename = $o_->short_filename;}
		if (property_exists($o_, 'replace_absolute_url')) {$opts->replace_absolute_url = $o_->replace_absolute_url;}
		if (property_exists($o_, 'replace_scripts')) {$opts->replace_scripts = $o_->replace_scripts;}
		if (property_exists($o_, 'htmlspecialchars')) {$opts->htmlspecialchars = $o_->htmlspecialchars;}
		if (property_exists($o_, 'htmlspecialchars')) {$opts->htmlspecialchars = $o_->htmlspecialchars;}
    if (property_exists($o_, 'url')) {$opts->url = $o_->url;}
    if (property_exists($o_, 'post_object_as_str')) {$opts->post_object_as_str = $o_->post_object_as_str;}
    if (property_exists($o_, 'post_object')) {$opts->post_object = $o_->post_object;}
    if (property_exists($o_, 'headers_array')) {$opts->headers_array = $o_->headers_array;}
	} else {
	}
	
	// Kill browser XHR request?
	//
	//
	if (property_exists($opts, 'browser_exit') && $opts->browser_exit)
		global__kill_browser_session();
	
	// $_REQUEST['AMT__output_html_from_URL']
	if (property_exists($opts, 'url')) {
		$_REQUEST['AMT__output_html_from_URL'] = $opts->url;
	}
	
	// Filename
	if (!isset($_REQUEST['AMT__output_html_from_URL__short_filename']) && !$opts->short_filename) {
		$f = './.cached/'.md5($_REQUEST['AMT__output_html_from_URL']);
	}
	else if (isset($_REQUEST['AMT__output_html_from_URL__short_filename'])) {
		$f = './.cached/'.preg_replace('/[\/]/i', '', $_REQUEST['AMT__output_html_from_URL__short_filename']);
		// OLD
		//~ $f = './.cached/'.preg_replace('/[^a-z0-9\.]/i', '', $_REQUEST['AMT__output_html_from_URL__short_filename']);
	}
	else if (property_exists($opts, 'short_filename') && $opts->short_filename) {
		$f = './.cached/'.preg_replace('/[\/]/i', '', $opts->short_filename);
		// OLD
		//~ $f = './.cached/'.preg_replace('/[^a-z0-9\.]/i', '', $opts->short_filename);
	}
	else {
		$f = './.cached/'.md5($_REQUEST['AMT__output_html_from_URL']);
	}
	
	// If file exists.
	// And !do_not_save_to_file.
	// This will exit after echoing file content.
	if (file_exists($f) && !property_exists($opts, 'do_not_save_to_file')) {
		$fp = trim(file_get_contents($f));
		if ($opts) {
			// replace_absolute_url
			if ($opts->replace_absolute_url) {
				$u = parse_url($_REQUEST['AMT__output_html_from_URL']);
				$u = $u['scheme'] . '://' . $u['host'];
				// href or src
				// spaces or '' = spaces or ''
				// 0 or 1 of ' or "
				$fp = str_replace("\n", '', $fp);
				$fp = preg_replace('/(href|src)( *= *[\'"]?)\//i', '$1$2'.$u.'/', $fp);
			}
			// $opts->replace_scripts
			if ($opts->replace_scripts) {
				$fp = str_replace("\n", '', $fp);
				$fp = preg_replace('/< *s *c *r *i *p *t *>.*?< *\/ *s *c *r *i *p *t *>/i', '', $fp);
				$fp = preg_replace('/<script[^>]*>.*?<\/script>/i', '', $fp);
			}
			// $opts->htmlspecialchars
			if ($opts->htmlspecialchars) {
				$fp = htmlspecialchars($fp, ENT_COMPAT|ENT_IGNORE, "UTF-8");
			}
		}
		//~ if (!$opts->htmlspecialchars) {
		//~ } else {
			//~ $fp = htmlspecialchars($fp, ENT_COMPAT|ENT_IGNORE, "UTF-8");
		//~ }
		
		// Echo file.
		// Unless silent_output.
		if (!property_exists($opts, 'silent_output'))
			echo $fp;
		else {
			// No output
		}
		
		// Are there any callback routines to perform?
		// If so, set $opts->response and pass the variable $opts.
		if (property_exists($opts, 'callback_routine')) {
			$opts->response = $r;
			//~ file_put_contents('test', $opts->callback_routine->name);
			if ($opts->callback_routine->name == 'trevor_faden_cu')
				new trevor_faden_cu($opts);
			if ($opts->callback_routine->name == 'giphy_speed_gifs')
				new giphy_speed_gifs($opts);
		}
		
		// If not blank then exit.
		if (trim($fp) != '') {
			// Debug
			//
			//
			//~ echo ' - cached!';
			// Exit
			exit;
		}
	}
	
	// Command line
	if (property_exists($opts, 'curl_cmdline')) { // New method!
		file_put_contents($f, '');
		chmod($f, 0777);
		shell_exec("curl ".$opts->curl_cmdline." -o " . $f);
		//~ file_put_contents($f, "curl ".$opts->curl_cmdline." -o " . $f);
		chmod($f, 0777);
		if (file_exists($f)) {
			echo trim(file_get_contents($f));
		}
		exit; // End!
	} else if (isset($_REQUEST['AMT__output_html_from_URL__use_cmdline'])) { // ____OLD METHOD____
		
		$fn = '/var/www/html/mturk/.cached/'.md5($_REQUEST['AMT__output_html_from_URL']);
		
		file_put_contents($fn, '');
		//~ echo "curl ".escapeshellarg($_REQUEST['AMT__output_html_from_URL'])." -H 'X-Requested-With: XMLHttpRequest' -o " . $fn;
		shell_exec("curl ".escapeshellarg($_REQUEST['AMT__output_html_from_URL'])." -H 'X-Requested-With: XMLHttpRequest' -o " . $fn);
		if (file_exists($fn)) {
			echo trim(file_get_contents($fn));
		}
		exit; // End!
	}
	
	// Curl init
	$ch = curl_init($_REQUEST['AMT__output_html_from_URL']); // post upload
	
	// Headers
	if (isset($_REQUEST['AMT__output_html_from_URL__headers'])) {
		$h = json_decode($_REQUEST['AMT__output_html_from_URL__headers']);
		
		$new_h = array();
		foreach ($h as $k => $v) {
			array_push($new_h, $v);
		}
		curl_setopt($ch, CURLOPT_HTTPHEADER, $new_h);
		//curl_setopt($ch, CURLOPT_HEADER, true);
	}
	
	// Other opts.
	curl_setopt($ch, CURLOPT_POST, false); // Default. Overwrite this below, if necessary.
	if ($opts) {
		
		// Post as object.
		// $opts->post_object
		// Assumption here is that the values have already been url encoded.
		if ($opts->post_object) {
			curl_setopt($ch, CURLOPT_POST, true);
			$array_post = array();
			foreach ($opts->post_object as $k => $v) {
				$array_post[$k] = $v;
			}
			curl_setopt($ch, CURLOPT_POSTFIELDS, $array_post);
		}
		
		// Post as string.
		// Assumption here is that the values have already been url encoded.
		if ($opts->post_object_as_str) {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $opts->post_object_as_str);
		}
		
		// $opts->headers_array
		if ($opts->headers_array) {
			$array_headers = array();
			foreach ($opts->headers_array as $k => $v) {
				array_push($array_headers, $v);
			}
			curl_setopt($ch, CURLOPT_HTTPHEADER, $array_headers);
      print_r($array_headers);
		}
	}
	
	// More curl_setopt
	curl_setopt($ch, CURLOPT_VERBOSE, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
	curl_setopt($ch, CURLOPT_TIMEOUT, 200);
	curl_setopt($ch, CURLOPT_ENCODING, 'gzip');//gzip encoding requires this to be ...
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // necessary for downloading the website https://alpinestock.com, Feb 22 2018, req="Wilfried Haferland"
	// Proxy
	if (property_exists($opts, '_redacted__use_proxy'))
		curl_setopt($ch, CURLOPT_PROXY, '127.0.0.1:8123');
	if (property_exists($opts, 'no_cookies') && $opts->no_cookies) {
		// Nothing
	} else if (property_exists($opts, 'cookie_filename') && $opts->cookie_filename) {
		$cf = '/var/www/html/mturk/.cached/' . preg_replace('/[\/]/', '', $opts->cookie_filename);
		if (!file_exists($cf)) { // Create file and chmod 777, if does not exist.
			file_put_contents($cf, '');
			chmod($cf, 0777);
		}
		curl_setopt($ch, CURLOPT_COOKIEFILE, $cf);
		curl_setopt($ch, CURLOPT_COOKIEJAR,  $cf);
		//~ echo $cf;
	} else {
		curl_setopt($ch, CURLOPT_COOKIEFILE, '/var/www/html/cookie.txt');
		curl_setopt($ch, CURLOPT_COOKIEJAR,  '/var/www/html/cookie.txt');
	}
	
  curl_setopt($ch, CURLOPT_VERBOSE, true);
  $verbose = fopen('php://temp', 'w+');
  curl_setopt($ch, CURLOPT_STDERR, $verbose);

	// Exec
	$r = curl_exec($ch);
  
  // Get info
	$x = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	
	/////////////////////////////////
	// Debug
  //~ rewind($verbose);
  //~ $verboseLog = stream_get_contents($verbose);
  //~ echo "Verbose information:\n", $verboseLog, "\n";
  //~ print_r($_REQUEST);
	//~ print_r(curl_getinfo($ch));
	/////////////////////////////////
	
	// Final URL
	if (property_exists($opts, 'CURLINFO_EFFECTIVE_URL') && $opts->CURLINFO_EFFECTIVE_URL) {
		echo curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
		exit;
	}
	
	// If not 200 then exit.
	if ($x != 200) {
		echo $x;
    print_r($opts);
		exit;
	}
	
	// Replace locations?
	// This will replace hrefs that are "/". It will add
	// domain name to this.
	if (property_exists($opts, 'replace_absolute_url') && $opts->replace_absolute_url) {
		$u = parse_url($_REQUEST['AMT__output_html_from_URL']);
		$u = $u['scheme'] . '://' . $u['host'];
		// href or src
		// spaces or '' = spaces or ''
		// 0 or 1 of ' or "
		$r = preg_replace('/(href|src)( *= *[\'"]?)\//i', '$1$2'.$u.'/', $r);
	}
	
	// Check for erroneous strings before file_put_contents.
	$bool_strings_do_not_save = false; // Default=false
	if (property_exists($opts, 'strings_do_not_save_to_file') && count($opts->strings_do_not_save_to_file) > 0) {
		foreach ($opts->strings_do_not_save_to_file as $k => $string) {
			if (strpos($r, $string) !== false) {
				$bool_strings_do_not_save = true;
				break;
			}
		}
	}
	
	// Option to not save to file.
	$bool_do_not_save_to_file = false; // Default=false
	if (property_exists($opts, 'do_not_save_to_file')) {
		$bool_do_not_save_to_file = true;
	}
	
	// Do not save to disk when using _redacted_ proxy.
	// By default.
	if (property_exists($opts, '_redacted__use_proxy')) {
		$bool_strings_do_not_save = true;
	}
	
	// File put contents.
	if (!$bool_strings_do_not_save && !$bool_do_not_save_to_file) {
		file_put_contents($f, $r);
		chmod($f, 0777); // chmod
	}
	
	// # get the content type
	//~ echo curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
	//~ exit;
	
	// Get info
	if (property_exists($opts, 'display_curl_info')) {
		$bool_strings_do_not_save = true;
		$gi = json_encode(curl_getinfo($ch)); // Assoc. array.
		$r = $gi.'||<<<<<<<END CURL GET INFO>>>>>>>||'.$r; // Add before output.
	}
	
	// Echo content.
	// Unless silent_output.
	if (!property_exists($opts, 'silent_output'))
		echo $r;
	else {
		// No output
	}
	
	// Are there any callback routines to perform?
	// If so, set $opts->response and pass the variable $opts.
	if (property_exists($opts, 'callback_routine')) {
		$opts->response = $r;
		if ($opts->callback_routine->name == 'trevor_faden_cu')
			new trevor_faden_cu($opts);
		if ($opts->callback_routine->name == 'giphy_speed_gifs')
			new giphy_speed_gifs($opts);
	}
	
	// Exit.
	exit;
}
/**
 * Callback routines for AMT__output_html_from_URL
 * 
 * -giphy_speed_gifs
 * -trevor_faden_cu
 */
// class giphy_speed_gifs
class giphy_speed_gifs {
	private $opts;
	private $filename;
	private $filename2x;
	private $filename3x;
	private $status_file;
	function __construct($opts) {
		// $f = './.cached/'.preg_replace('/[\/]/i', '', $opts->short_filename);
		// e.g. ffmpeg -i giphy.gif -filter:v "setpts=0.33*PTS" test4.gif
		$this->opts = $opts; // Set opts.
		$this->filename = '/var/www/html/mturk/.cached/' . $opts->short_filename;
		$this->filename = str_replace('"','',$this->filename); // No "
		$this->status_file = $this->filename . '.status';
		$this->create('0.33');
		$this->create('0.5');
		// Write to status file
		file_put_contents($this->status_file, 'x');
		chmod($this->status_file, 0777);
	}
	function create($speed) {
		$of = ($speed == '0.33') ? $this->filename . '3x.gif' : $this->filename . '2x.gif';
		$cmd = '/usr/bin/ffmpeg -i "'.$this->filename.'" -filter:v "setpts='.$speed.'*PTS" "'.$of.'"';
		//~ echo $cmd;
		shell_exec($cmd);
	}
}
class trevor_faden_cu {
	private $opts;
	private $filename;
	private $status_file;
	function __construct($opts) {
		
		$this->opts = $opts; // Set opts.
		$this->filename = '/var/www/html/mturk/.cached/' . $opts->short_filename;
		$this->status_file = str_replace('.pdf', '', $this->filename) . $opts->js_unique_id . '.status'; // Add "js_unique_id" to the status filename.
		
		// Create status file
		if (file_exists($this->status_file)) {
			unlink($this->status_file); // Get rid of old
		}
		file_put_contents($this->status_file, '');
		chmod($this->status_file, 0777); // chmod
		
		// Correct filetype?
		$filetype = (new finfo(FILEINFO_MIME))->file($this->filename);
		if (stripos($filetype, 'pdf') === false) { // Case insensitive search.
			file_put_contents($this->status_file, 'WRONG FILE TYPE:'. $filetype);
			// Remove the downloaded file!
			// This is probably that the CAPTCHA is wrong.
			unlink($this->filename);
			exit;
		}
		
		// Double-check that this is not the HTML page when a CAPTCHA has been entered incorrectly?
		// If file size is less than... PDFs are typically 900KB. HTMLs are typically 40KB.
		// ...
		//~ file_get_contents($this->filename);
		
		
		// Try upload
		//
		//
		$this->upload();
	}
	function upload() {
		$id = $this->opts->callback_routine->id;
		
		// Init
		$ch		= curl_init();
		$url	= 'http://www.creditunions.com/mturk/upload.aspx?cn=' . $id;
		
		// Get page 1 for the form vars __EVENTVALIDATION, __VIEWSTATE.
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_VERBOSE, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (compatible;)");
		curl_setopt($ch, CURLOPT_URL, $url);
		//
		curl_setopt($ch, CURLOPT_ENCODING,"");
		curl_setopt($ch, CURLOPT_TIMEOUT, 800);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 800);
		curl_setopt($ch, CURLOPT_FAILONERROR, true);
		$r = curl_exec($ch);
	
		// Scrape response.
		$r = str_replace("\n", "", $r);
		preg_match('/id="__VIEWSTATEGENERATOR" value="([^"]+)" \/>(?:.+?)"__EVENTVALIDATION" value="([^"]*)" \/>(?:.+?)"__VIEWSTATE" value="([^"]*)" \/>/i', $r, $m);
		if (empty($m[1])) {
			file_put_contents($this->status_file, 'Error at scrape creditunions.com');
			exit;
		}
		
		// Page 2
		// On success will be strpos "Success!" === 0.
		/**
		 * From: http://stackoverflow.com/questions/574242/when-posting-an-associative-array-with-curl-do-i-need-to-urlencode-the-contents
		 * You don't have to urlencode first. However, it is important to realize that passing an array will make cURL send it as multipart/form-data, which explains why it is does not need to get urlencoded (by neither you nor cURL), and you need to use an array if you want to upload files. If you http_build_query() first (and send it as a string) it will be treated as application/x-www-form-urlencoded.
		 */
		chdir('/var/www/html/mturk/.cached/');
		$post = array(
			//~ "form1"					=> "",
			"__VIEWSTATEGENERATOR"	=> $m[1],
			"__EVENTVALIDATION"		=> $m[2],
			"__VIEWSTATE"			=> $m[3],
			//~ "flUpload"				=> $cfile,
			//~ "flUpload"				=>"@" . $this->filename,
			//~ "flUpload"				=> curl_file_create($this->filename, 'application/pdf', 'flUpload'),
			//~ "flUpload"				=> curl_file_create(basename($this->filename), 'application/pdf', 'flUpload'),
			"flUpload"				=> curl_file_create(basename($this->filename)), // BASENAME NOT FULL IS REQUIRED
			// E.g.
			// "flUpload" => curl_file_create('/var/www/html/mturk/.cached/CallReport5300_June-2015_COMMUNITY.pdf');
			
			"btnUpload"				=> "Upload"
		);//file_put_contents($this->status_file, $this->filename);exit;
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_VERBOSE, 0);
		curl_setopt($ch, CURLOPT_ENCODING,"gzip");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (compatible;)");
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60); // 60s
		curl_setopt($ch, CURLOPT_TIMEOUT, 800);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 800);
		//~ curl_setopt($ch, CURLOPT_FAILONERROR, true);
		$r = curl_exec($ch);
		$v = curl_getinfo($ch);
		
		//~ foreach ($v as $k=>$value) {//DEBUG
			//~ $post[$k] = $k.':'.$value.' ';
		//~ }
		//~ file_put_contents($this->status_file, implode(',', $post));exit;
		
		// Check completion
		if ( $v['http_code'] != '200' && strpos($r, 'Success!') !== 0) { // Err
			file_put_contents($this->status_file, 'Error uploading the PDF: Code:'.$v['http_code']);
			exit;
		} else if (strpos($r, 'Success!') !== false) { // Success
			file_put_contents($this->status_file, 'okay'); // "okay" == submit
		} else {
			file_put_contents($this->status_file, 'No "Success!" after upload!' . $this->filename.',' .$url.','.$r, FILE_APPEND); // "okay" == submit
		}
	}
}

/**
 * AMT__doSubmit__serverSide_read
 * 
 * @param: AMT__doSubmit__serverSide
 * @param: AMT__doSubmit__serverSide_read
 * echoes "time" (ms) + ./AMT__doSubmit__serverSide file contents
 * 
 * Start by sending the current time (on this server)
 * float(1283846202.89), where 1283846202 is seconds since epoch,
 * but apparently this may also be e.g. 777.9384748, etc., i.e. multiple numbers after decimal
 */
if (isset($_GET['AMT__doSubmit__serverSide_read'])) { // new
	$f = './.miscellaneous/AMT__doSubmit__serverSide';
	if (isset($_GET['AMT__doSubmit__serverSide_read_opts'])) {
		$opts = json_decode($_GET['AMT__doSubmit__serverSide_read_opts']);
		if ($opts->queue && $opts->queue != 'serverSide')
			$f = './.miscellaneous/AMT__doSubmit__serverSide_' . str_replace(' ', '', $opts->queue);
	}
	$time_ms = microtime(true) * 1000;
	echo 'time:'.$time_ms;
	// echo file
	if (!file_exists($f)) {
		file_put_contents($f, '');
	} else {
		echo trim(
			str_replace(
				']{', ',{', file_get_contents($f)
			)
		);
	}
	exit;
}
/**
 * AMT__doSubmit__serverSide_write
 */
// 
if (isset($_POST['AMT__doSubmit__serverSide_write'])) {
	$f = './.miscellaneous/AMT__doSubmit__serverSide';
	if (isset($_POST['AMT__doSubmit__serverSide_write_opts'])) {
		$opts = json_decode($_POST['AMT__doSubmit__serverSide_write_opts']);
		if ($opts->queue && $opts->queue != 'serverSide')
			$f = './.miscellaneous/AMT__doSubmit__serverSide_' . str_replace(' ', '', $opts->queue);
	}
	$_POST['AMT__doSubmit__serverSide_write'] =
		str_replace(
			']{', '},{', $_POST['AMT__doSubmit__serverSide_write']
		);
	if (!file_exists($f)) {
		file_put_contents($f, $_POST['AMT__doSubmit__serverSide_write']);
		chmod($f, 0777);
	} else {
		file_put_contents($f, $_POST['AMT__doSubmit__serverSide_write']);
	}
	//[{"id":"_EybPl","time_to_submit":1393819719528}]{"id":"_1hTp4","time_to_submit":1393819720227},{"id":"_xe8oP","time_to_submit":1393819722427},{"id":"_XKSAP","time_to_submit":1393819724627},{"id":"_NiQFP","time_to_submit":1393819726827}] 
	exit;
}
//
// read cache **without** MD5
//
if (isset($_GET['mt__echo_cached'])) { // new
	$f = substr(str_replace('/','',basename($_GET['mt__echo_cached'])), 0, 200);
	
	// folder!
	$folder = './.cached/';
	
	// mt__echo_cached__folder! (i.e. Yogesh_K_Riyat_lv)
	if (isset($_GET['mt__echo_cached'])) {
		$folder = './.'.str_replace('.','',$_GET['mt__echo_cached__folder']).'/'; // remove dots!
	}
	
	$f = $folder.$f;
	if (file_exists($f)) {
		echo trim(file_get_contents($f));
	}
	exit;
}
//
// aka crowdsource (MD5!)
//
if (isset($_GET['mt__read_cache'])) { // new
	$fmd5 = md5($_GET['mt__read_cache']).str_replace('/','',basename($_GET['mt__read_cache']));
	$f = './.cached/'.$fmd5;
	$f = '/var/www/html/mturk/.cached/'.$fmd5;
	if (file_exists($f)) {
		echo trim(file_get_contents($f));
	} else {
		//~ echo $f." ";
		//~ echo file_exists($f);
		//~ echo 'x';
	}
	exit;
}
if (isset($_GET['mt__write_cache']) && isset($_GET['mt__write_cache_value'])) { // new get
	$fmd5 = md5($_GET['mt__write_cache']).str_replace('/','',basename($_GET['mt__write_cache']));
	$f = '/var/www/html/mturk/.cached/'.$fmd5;
	if (!file_put_contents($f, trim($_GET['mt__write_cache_value']))) {
		echo '!write, get';
	} else {
	}
	exit;
}
if (isset($_GET['mt__append_cache']) && isset($_GET['mt__append_cache_value'])) { // append get
	$fmd5 = md5($_GET['mt__append_cache']).str_replace('/','',basename($_GET['mt__append_cache']));
	$f = './.cached/'.$fmd5;
	if (!file_put_contents($f, "\n" . trim($_GET['mt__append_cache_value']), FILE_APPEND)) {
		echo '!write, get';
	}
	exit;
}
if (isset($_POST['mt__write_cache']) && isset($_POST['mt__write_cache_value'])) { // new post
	$fmd5 = md5($_POST['mt__write_cache']).str_replace('/','',basename($_POST['mt__write_cache']));
	$f = './.cached/'.$fmd5;
	if (!file_put_contents($f, trim($_POST['mt__write_cache_value']))) {
		echo '!write, post';
	}
	exit;
}

// AMT__pdfimages
// Set size to 300px. This is a bit big for "three thumbnails tall" on
// a 1080px resolution monitor. But then these may be shrunk with
// HTML and expanded to 300px higher resolution.
if (isset($_GET['AMT__pdfimages'])) {
	$j = json_decode($_GET['AMT__pdfimages']);
	$fn = $j->filename;
	$f = '/var/www/html/mturk/.cached/'.$fn;
	$f2= '/var/www/html/mturk/.cached/'.$fn.'.png';
	$f3= '/var/www/html/mturk/.cached/'.$fn.'.complete';
	$f4= '/var/www/html/mturk/.cached/'.$fn.'full.png';
	$s = new stdClass;
	$s->url = $j->url;
	$fp = curl_get($s)->html;
	// TODO: KILL BROWSER SESSION HERE.........
	// ........................................
	// Is it cached?
	if (file_exists($f3)) {
		exit;
	}
	// Save original pdf and chmod 777.
	file_put_contents($f, $fp);
	chmod($f, 0777);
	// Convert to images.
	$output = shell_exec(
		'convert -thumbnail x300 -background white -alpha remove "'.$f.'" "'.$f2.'"');
	// Convert to full size images.
	// Density=300 seems to work well for quality, as well as file size.
	$output = shell_exec(
		'convert -density 200 "'.$f.'" "'.$f4.'"');
	
	// If there are no files, add message to .complete.
	if (!file_exists($f.'-0.png')) {
		file_put_contents($f3, 'Error: No files converted!');
		exit;
	}
	
	// Put the number of images extracted into a log file.
	// Assume no more than 99 pages in a pdf.
	for ($i=0; $i<99; $i++) {
		if (!file_exists($f.'-'.$i.'.png')) {
			file_put_contents($f3, $i-1); // Set to next to last $i.
			break;
		}
	}
}

// AMT__pdfocr
// Set size to 300px. This is a bit big for "three thumbnails tall" on
// a 1080px resolution monitor. But then these may be shrunk with
// HTML and expanded to 300px higher resolution.
if (isset($_GET['AMT__pdfocr'])) {
	$j = json_decode($_GET['AMT__pdfocr']);
	$fn = $j->filename;
	$f = '/var/www/html/mturk/.cached/'.$fn;
	$f2= '/var/www/html/mturk/.cached/'.$fn.'.png';
	$f3= '/var/www/html/mturk/.cached/'.$fn.'.complete';
	$f4= '/var/www/html/mturk/.cached/'.$fn.'.tif';
	$f5= '/var/www/html/mturk/.cached/'.$fn.'full.png';
	// Debug
		//~ echo $f.'<br>';
		//~ echo $f2.'<br>';
		//~ echo $f3.'<br>';
		//~ echo $f4.'<br>';
		//~ /var/www/html/mturk/.cached/8EE6A2D10EA8E630357F279FB7BA79B4
		//~ /var/www/html/mturk/.cached/8EE6A2D10EA8E630357F279FB7BA79B4.png
		//~ /var/www/html/mturk/.cached/8EE6A2D10EA8E630357F279FB7BA79B4.complete
		//~ /var/www/html/mturk/.cached/8EE6A2D10EA8E630357F279FB7BA79B4.tif
	$s = new stdClass;
	$s->url = $j->url;
	$fp = curl_get($s)->html;
	
	// TODO: KILL BROWSER SESSION HERE
	// .........
	// ........................................
	
	// Is it cached?
	if (file_exists($f3)) {
		exit;
	}
	// Save original pdf and chmod 777.
	file_put_contents($f, $fp);
	chmod($f, 0777);
	// Convert to thumbnails, useful for jobs which need thumbnails.
	$output = shell_exec(
		'convert -thumbnail x300 -background white -alpha remove "'.$f.'" "'.$f2.'"');
	// Convert to full size images for OCR.
	// Density=300 seems to work well for quality, as well as file size.
	$output = shell_exec(
		'convert -trim -quality 100 -density 300x300 -sharpen 0x1.0 "'.$f.'" "'.$f4.'"'); //-density 200 or -density 300x300
	// Convert to full size PNG for HTML viewing.
	$output = shell_exec(
		'convert -trim -quality 100 -density 300x300 -sharpen 0x1.0 "'.$f.'" "'.$f5.'"');
	
	// -verbose       \
   //~ -density 150   \
   //~ -trim          \
    //~ test.pdf      \
   //~ -quality 100   \
   //~ -flatten       \
   //~ -sharpen 0x1.0 \
    
	// If there are no files, add message to .complete.
	// In the case that there is only one page to the pdf,
	// there will only be one png.
	// And it's name will be ".png", NOT "-0.png"!!!
	if (!file_exists($f.'-0.png') && !file_exists($f2)) {
		file_put_contents($f3, '{"error": "No files converted(1)."}');
		exit;
	}
	// Check the same for .tif, as well.
	if (!file_exists($f.'-0.tif') && !file_exists($f4)) {
		file_put_contents($f3, '{"error": "No files converted(2)."}');
		exit;
	}
	
	// Put the number of images extracted into a log file.
	// The log file is: "____.complete".
	// Assume no more than 99 pages in a pdf.
	$output = Array('page'=>Array());
	for ($i=0; $i<99; $i++) {
		$ftmp = $f.'-'.$i.'.tif';
		if ($i == 0 && !file_exists($ftmp)) {
			// Only on the first run, filename may be this version!
			$ftmp = $f4;
		}
		if (file_exists($ftmp)) {
			// tesseract
			$o = shell_exec(
				'tesseract "'.$ftmp.'" "'.$f.'-tmptext" -psm '.$j->psm.
				' -c tessedit_create_hocr=1 -c tessedit_pageseg_mode=6'. // force hocr to match psm!!
				'  nobatch ' . $j->goodchars_file . ' hocr'
			); //-l eng // add hocr for html output
			chmod($f.'-tmptext.txt', 0777);
			$txt = file_get_contents($f.'-tmptext.hocr');
			//~ $txt = file_get_contents($f.'-tmptext.txt'); // this is text mode (when using no hocr)
			array_push($output['page'], $txt);
		} else {
			// No more images left! All done.
			break;
		}
	}
	file_put_contents($f3, json_encode($output));
	exit;
}

//
// AMT__andrew_ryan_guidestar__PDF_2_OCR
//
if (isset($_GET['AMT__andrew_ryan_guidestar__PDF_2_OCR'])) {	
	$fn = md5($_GET['AMT__andrew_ryan_guidestar__PDF_2_OCR']);
	$f = '/var/www/html/mturk/.cached/'.$fn;
	$f2= '/var/www/html/mturk/.cached/'.$fn;
	$s = new stdClass;
	$s->url = $_GET['AMT__andrew_ryan_guidestar__PDF_2_OCR'];
	$fp = curl_get($s)->html;
	
	file_put_contents($f, $fp);
	chmod($f, 0777);
	
	//pdfimages -f 1 -l 1 2013-362906866-09f93776-9.pdf meeeeh
	$output = shell_exec('pdfimages -f 1 -l 1 "'.$f.'" "'.$f.'"');
	
	// orig = 2800x4259
	// x off=0, y off=1200, 
	// w = 2700, h = 1600
	$output = shell_exec('convert -crop 2700x1600+100+1200 "'.$f.'-000.pbm" "'.$f.'.tif"');
	chmod($f.'.tif', 0777);
	
	// (Save a copy as png)
	$output = shell_exec('convert "'.$f.'-000.pbm" "'.$f.'.png"');
	chmod($f.'.png', 0777);
	
	// tesseract
	$output = shell_exec('tesseract "'.$f.'.tif" "'.$f.'-text" -psm 6');
	chmod($f.'-text.txt', 0777);
	
	// filename
	echo $fn . ' ';
	echo file_get_contents($f.'-text.txt');
	
	
	
	//~ $x = file_put_contents($f, $_GET['AMT__convert_file__value']);
	//~ if (!$x) {
		//~ echo 'x' . file_put_contents($f, $_GET['AMT__convert_file__value']);
	//~ } else {
		//~ chmod($f, 0777);
	//~ }
	
	exit;
}

/**
 * AMT__image_2_OCR
 * 
 * textcleaner is copied to /usr/local/bin for this script.
 * http://www.fmwconcepts.com/imagemagick/textcleaner/index.php
 **/
if (isset($_GET['AMT__image_2_OCR'])) {
	set_time_limit(300);
	$opts = json_decode($_GET['AMT__image_2_OCR']);
	$basenm = pathinfo($opts->url);
	$basenm = $basenm['basename'];
	
	// Filename. This has the "resize" & "crop" vars embedded in it!
	$fn = md5($opts->url . $opts->resize . $opts->crop);
	$f = '/var/www/html/mturk/.cached/'.$fn.$basenm; // basenm
	$txt_file = $f.'-text.txt';
	$process_complete = $f.'-pc';
	$thumbnail = $fn.$basenm.'-thm.png'; // Only if necessary
	$t = $fn.$basenm.'-text.txt';
	// $ocrf_img;
	$ocr_thm = $fn.$basenm.'.tif'; // Default ocr filename image.
	if ($opts->custom) {
		$ocr_thm = $fn.$basenm.'-ocr.png';
	}
	
	// $opts->CHECK_COMPLETE
	if (property_exists($opts, 'CHECK_COMPLETE')) {
		if (file_exists($process_complete)) {
			echo '__OCR_COMPLETE__';
			echo '__response_details__'.json_encode(["text_file"=>$t, "thumb"=>$thumbnail, "ocr_thumb"=>$ocr_thm]); // Details on response
			echo '__end_details__';
			echo file_get_contents($txt_file);
		} else {
			//
		}
		exit;
	}
	
	// EXISTS?
	if (file_exists($process_complete)) {
		echo '__OCR_COMPLETE__';
		echo '__response_details__'.json_encode(["text_file"=>$t, "thumb"=>$thumbnail, "ocr_thumb"=>$ocr_thm]); // Details on response
		echo '__end_details__';
		echo file_get_contents($txt_file);
		exit;
	} else {
		// FORCE BROWSER CLOSE
		//
		//~ header("Connection: close");
		//~ ob_start();
		//~ phpinfo();
		//~ $size = ob_get_length();
		//~ header("Content-Length: $size");
		//~ ob_end_flush();
		//~ flush();
		
		//~ if (!isset($_GET['testingTEST'])) {
			//~ header("Connection: close");
			//~ ob_start();
			
			//~ print_r($opts);
			//~ phpinfo();
			//~ $size = ob_get_length();
			//~ header("Content-Length: $size");
			//~ ob_end_flush();
			//~ flush();
		//~ }
	}
	
	// New one.
	$s = new stdClass;
	$s->url = $opts->url;//print_r($opts);
	$content = curl_get($s);
	
	// $ocrf_img;
	$ocrf_img = $f.'.tif'; // Default ocr filename image.
	
	// THIS BREAKS THE SCRIPT ON REMOTE!!! (PRINT_R)
	// THIS IS PROBABLY BECAUSE $content->html IS LARGER THAN
	// MEMORY LIMIT.
	//~ print_r($content);
	
	// If not 200
	if ($content->status != 200) {
	//	print_r($content->status);echo 'xx';
		file_put_contents($txt_file, '');
		chmod($txt_file, 0777);
		exit;
	}
	
	// Save to file
	file_put_contents($f, $content->html);
	chmod($f, 0777);
	// Resize
	if ($opts->resize && preg_match('/^\d{1,3}%$/', $opts->resize) === 1) {
		//
	} else {
		$opts->resize = false;
	}
	// Resize
	$resize = '';
	if ($opts && $opts->resize) {
		$resize = '-resize '.$opts->resize . ' ';
	} else $resize = '';
	
	// Crop
	// e.g. crop: '-2+0',
	if ($opts->crop && preg_match('/^[\-+]\d{1,10}[\-+]\d{1,10}$/', $opts->crop)) {
		$opts->crop = ' -crop '.$opts->crop;
	} else {
		$opts->crop = '';
	}
	
	// Custom convert
	if ($opts->custom) {
		// convert -density 300 -trim 1.pdf 6a.png && convert 6a.png -crop 60%x40%+20%+5% 6ax.png &&
		// ./textcleaner -g -e stretch -f 30 -o 30 -t 40 -u -s 1 -T -p 30 6ax.png 6ay.png
		$ocrf_img = $f.'-ocr.png';
		$thm = '/var/www/html/mturk/.cached/' . $thumbnail;
		// 70%x40%+15%+5% to 90%x30%+5%+20%
		$output = shell_exec('convert -density 300 -trim "'.$f.'" "'.$thm.'" && chmod 777 "'.$thm.'" && convert "'.$thm.'" -crop 96%x30%+2%+20% "'.$f.'"-crop.png');
		$output = shell_exec('textcleaner -g -e stretch -f 30 -o 30 -t 40 -u -s 1 -T -p 30 "'.$f.'"-crop.png "'.$ocrf_img.'"');
	} else {
		// Back to standard...
		// tif + ...
		$output = shell_exec('convert "'.$f.'"'.$opts->crop.' '.$resize.'"'.$ocrf_img.'"');
		if (file_exists($f.'.tif')) {
			chmod($f.'.tif', 0777);
		} else {
			echo 'fe:' . $f.'.tif'; // Error?
			exit;
		}
	}
	// 
	
	// Nobatch?
	// e.g. goodchars, digits, etc
	// Default=goodchars
	if (!$opts->nobatch) $opts->nobatch = ' nobatch goodchars'; // DEFAULT
	else if ($opts->nobatch == 'none') $opts->nobatch = '';
	else $opts->nobatch = ' nobatch '.$opts->nobatch;
	
	// Tesseract
	// Linux `timeout`.
	// "Start COMMAND, and kill it if still running after NUMBER
	// seconds. SUFFIX may be "s" for seconds (the default), "m" for minutes, "h" for hours or "d" for days."
	shell_exec('timeout 50 tesseract "'.$ocrf_img.'" "'.$f.'-text" -l eng -psm 6' . $opts->nobatch); // nobatch goodchars
	chmod($txt_file, 0777);
	file_put_contents($process_complete, '');
		//~ exec('tesseract "'.$f.'.tif" "'.$f.'-text" -l eng -psm 6 nobatch goodchars');
		//~ $output = shell_exec('tesseract "'.$f.'.tif" "'.$f.'-text" -l eng -psm 6 nobatch goodchars');
	exit;
}

//
// read cache **without** MD5
//
//~ if (isset($_GET['Richard_Hunt_google_yelp_scrape'])) { // queue
	//~ $f = './.miscellaneous/Richard_Hunt_queue';
	//~ if (file_exists($f)) {
		//~ $r = file_get_contents($f);
		//~ $r = explode("\n", $r);
		//~ echo array_shift($r);
		//~ $r = implode("\n", $r);
		//~ file_put_contents($f, $r);
	//~ }
	//~ exit;
//~ }
if (isset($_GET['Richard_Hunt_google_yelp_scrape'])) { // save
	$f = './.miscellaneous/Richard_Hunt_complete';
	
	foreach ($_GET as $key => $v) {
		$_GET[$key] = str_replace('"', '\\"', $v);
	}
	$string =
		'"'.$_GET['Richard_Hunt_google_yelp_scrape'].'",'.
		'"'.$_GET['Richard_Hunt_google_yelp_scrape__val_google_url'].'",'.
		'"'.$_GET['Richard_Hunt_google_yelp_scrape__val_rating'].'",'.
		'"'.$_GET['Richard_Hunt_google_yelp_scrape__val_reviews'].'"'."\r\n";
	file_put_contents($f, $string, FILE_APPEND);
	
	// save halves
	if (strpos($_GET['Richard_Hunt_google_yelp_scrape__val_rating'], '.5') !== false) {
		$f = './.miscellaneous/Richard_Hunt_complete_JustHalves';
		$string2 =
			'"'.$_GET['Richard_Hunt_google_yelp_scrape'].'",'.
			'"'.$_GET['Richard_Hunt_google_yelp_scrape__val_google_url'].'",'.
			'"'.$_GET['Richard_Hunt_google_yelp_scrape__val_rating'].'",'.
			'"'.$_GET['Richard_Hunt_google_yelp_scrape__val_reviews'].'"'."\r\n";
		file_put_contents($f, $string2, FILE_APPEND);
	}
	//echo $string;
	//exit;
	
	$f = './.miscellaneous/Richard_Hunt_queue';
	if (file_exists($f)) {
		$r = file_get_contents($f);
		$r = explode("\n", $r);
		echo array_shift($r);
		$r = implode("\n", $r);
		file_put_contents($f, $r);
	}
	
	//~ if (file_exists($f)) {
		//~ echo trim(file_get_contents($f));
	//~ }
	exit;
}
//
// Kurtis Meloche
// Kurtis_Meloche_cpaontario_com
// 
if (isset($_GET['Kurtis_Meloche_cpaontario_com'])) {
	$f = './.deleteme/Kurtis_Meloche_cpaontario_com'.$_GET['Kurtis_Meloche_cpaontario_com'];
	echo file_get_contents($f);
	exit;
}
if (isset($_POST['Kurtis_Meloche_cpaontario_com']) && isset($_POST['Kurtis_Meloche_cpaontario_com__num'])) {
	$f = './.deleteme/Kurtis_Meloche_cpaontario_com'.$_POST['Kurtis_Meloche_cpaontario_com__num'];
	file_put_contents($f, $_POST['Kurtis_Meloche_cpaontario_com']);
	//~ if (!file_exists($f)) {
	//~ }
	exit;
}
//
// BCPM
// BCPM_price_quotes__uship_com
if (isset($_GET['BCPM_price_quotes__uship_com'])) {
	$f = './.deleteme/bpcm'.str_replace('/','',$_GET['BCPM_price_quotes__uship_com']);
	//echo $f;exit;
	if (file_exists($f)) {
		//echo trim(file_get_contents($f));
		//exit;
	}
	
	$ch = curl_init($_GET['BCPM_price_quotes__uship_com']); // init
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
	//~ curl_setopt($ch, CURLOPT_COOKIEFILE, '/var/www/html/cookie.txt');
	//~ curl_setopt($ch, CURLOPT_COOKIEJAR, '/var/www/html/cookie.txt');
	curl_setopt($ch, CURLOPT_PROXY, '127.0.0.1:_redacted_');
	curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
	curl_setopt($ch, CURLOPT_TIMEOUT, 100);
	$r = curl_exec($ch);
	$x = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	if ($x != 200) {
		echo $x;
		print_r(curl_getinfo($ch));
		exit;
	} else {
		// scrape for moved id
		// Object moved to <a href="/quotes/view.aspx?qrid=52f2890f72cfec1bbc93ca27&amp;view=0">
		//~ echo htmlspecialchars($r);
		//~ print_r(curl_getinfo($ch));
		
		preg_match('/Object moved to <a href="\/quotes\/view\.aspx\?qrid=([^&]+)/', $r, $m);
		if (!empty($m[1])) {
			$id = $m[1];
		} else {
			$lurl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL); // last effective
			preg_match('/qrid=([^&]+)/', $lurl, $m);
			if (!empty($m[1])) {
				$id = $m[1];
			} else
				exit;
		}
	}
	//~ echo $id;exit;
	//~ exit;
	
	// here?
	//http://www.uship.com/quotes/view.aspx?z1=11111&z2=22222&country1ID=1&country2ID=1&a_year=2012&a_make=30&a_model=14804&a_running=true&c=79&c2=0
	
	//curl ''  --data '{"quoteRequestId":"52f265ae72cfec20b0edb5e4","shipperUserId":"0","queryString":"qrid=52f265ae72cfec20b0edb5e4&view=0"}' --compressed
	//curl 'https://www.uship.com/ajaxpro/id3Solutions.UShip.Web.QuotePopper,id3Solutions.UShip.ashx' -H 'X-AjaxPro-Method: GetInstantQuotes' -H 'Origin: https://www.uship.com' -H 'Accept-Encoding: gzip,deflate,sdch' -H 'X-uShip-LangCulture: en-US' -H 'Accept-Language: en-US,en;q=0.8' -H 'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.46 Safari/537.36' -H 'Content-Type: application/x-www-form-urlencoded; charset=UTF-8' -H 'Accept: */*' -H 'X-Requested-With: XMLHttpRequest' -H 'Connection: keep-alive' -H 'DNT: 1' --data '{"quoteRequestId":"52f265cb72cfec20b0edb6a6","shipperUserId":"0","queryString":"qrid=52f265cb72cfec20b0edb6a6&view=0"}' --compressed
	$array_hds = array(
		"X-AjaxPro-Method: GetInstantQuotes",
		"Origin: https://www.uship.com",
		"Accept-Encoding: gzip,deflate,sdch",
		"X-uShip-LangCulture: en-US",
		"Accept-Language: en-US,en;q=0.8",
		"User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.46 Safari/537.36",
		"Content-Type: application/x-www-form-urlencoded; charset=UTF-8",
		"Accept: */*",
		"X-Requested-With: XMLHttpRequest",
		"Connection: keep-alive",
		"DNT: 1"
	);
	//http://192.168.1.6/mturk/util.php?BCPM_price_quotes__uship_com=http%3A%2F%2Fwww.uship.com%2Fquotes%2Fview.aspx%3Fz1%3D02201%26z2%3D98401%26country1ID%3D1%26country2ID%3D1%26a_year%3D2012%26a_make%3D30%26a_model%3D14804%26a_running%3Dtrue%26c%3D79%26c2%3D0%26currency%3Ddollar%26localcountryid%3D1%26pageculture%3Dus-US%26langculture%3Dus-US
	$array_pst = array(
		//'' => '{"quoteRequestId":"'.$_GET['BCPM_price_quotes__uship_com'].'","shipperUserId":"0","queryString":"qrid='.$_GET['BCPM_price_quotes__uship_com'].'&view=0"}'
		"quoteRequestId"=>$id,
		"shipperUserId"=>"0",
		"queryString"=>'qrid='.$id.'&view=0' //&country=US&currency=dollar&currency=$&localcountryid=1&pageculture=us-US&langculture=us-US'
	);
	$array_pst = json_encode($array_pst);//echo $array_pst;
	$ch = curl_init('https://www.uship.com/ajaxpro/id3Solutions.UShip.Web.QuotePopper,id3Solutions.UShip.ashx'); // post upload
	curl_setopt($ch, CURLOPT_HTTPHEADER, $array_hds);
	curl_setopt($ch, CURLOPT_VERBOSE, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $array_pst);
	//~ curl_setopt($ch, CURLOPT_COOKIEFILE, '/var/www/html/cookie.txt');
	//~ curl_setopt($ch, CURLOPT_COOKIEJAR, '/var/www/html/cookie.txt');
	curl_setopt($ch, CURLOPT_PROXY, '127.0.0.1:_redacted_');
	curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
	curl_setopt($ch, CURLOPT_TIMEOUT, 100);
	curl_setopt($ch, CURLOPT_ENCODING, 'identity');//gzip encoding requires this to be ...
	$r = curl_exec($ch);
	$x = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	if ($x != 200) {
		echo $x;
	} else {
		//~ $r = preg_replace_callback(
			//~ '/yschttl spt" href="[^*]+[*]+([^"]+)"/',
			//~ function($matches) {
				//~ return 'yschttl spt" href="'.urldecode($matches[1]).'"';
			//~ },
			//~ $r
		//~ );
		file_put_contents($f, $r);
		echo $r;
	}
	exit;
}
/**
 * (SOME) ARGV HERE
 * 
 * @ WeFi_DD # Dunkin Donuts
 * @ WeFi_Casino # Try places api, first result. Then do a lat long.
 * @ WeFi_PH
 * @ WeFi_TB
 * @ WeFi_SB
 * @ WeFi_RA
 * @ WeFi_Quality_Control
 * @ AMT_Facebook__First_and_Last_Names
 * @ Tucker_Severson
 * @ ca_tally
 * @ Geography_of_Transport (Spring 2017)
 * @ JB_JeffParish
 * 
 */
if (!isset($argv)) { // broken post php-7
	$argc = ['xxx', 'xxx'];
	$argv = ['xxx', 'xxx'];
}
/**
* Geography_of_Transport
**/
if ($argv[1] == 'JB_JeffParish') {
	class JB_JeffParish {
		function __construct() {
			global $argv;
			$this->init();
		}
		function init() {
			$this->data = [
				'1000 Tensas Dr'=>'306100 0147154',
				'1004 Tensas Dr'=>'306099 0147154',
				'1005 Tensas Dr'=>'305921 0147154',
				'1008 Tensas Dr'=>'306098 0147154',
				'1009 Inca Dr'=>'305586 0147154',
				'1009 Tensas Dr'=>'305922 0147154',
				'1012 Inca Dr'=>'305744 0147154',
				'1012 Tensas Dr'=>'306090 0147154',
				'1013 Inca Dr'=>'305587 0147154',
				'1013 Tensas Dr'=>'305923 0147154',
				'1016 Inca Dr'=>'305745 0147154',
				'1016 Tensas Dr'=>'306089 0147154',
				'1017 Inca Dr'=>'305588 0147154',
				'1017 Tensas Dr'=>'305924 0147154',
				'1020 Inca Dr'=>'305746 0147154',
				'1020 Tensas Dr'=>'306088 0147154',
				'1021 Inca Dr'=>'305589 0147154',
				'1021 Tensas Dr'=>'305925 0147154',
				'1024 Inca Dr'=>'305747 0147154',
				'1024 Tensas Dr'=>'306097 0147154',
				'1025 Inca Dr'=>'305590 0147154',
				'1025 Tensas Dr'=>'305926 0147154',
				'1028 Inca Dr'=>'305748 0147154',
				'1028 Tensas Dr'=>'306096 0147154',
				'1029 Inca Dr'=>'305591 0147154',
				'1029 Tensas Dr'=>'305927 0147154',
				'1032 Inca Dr'=>'305749 0147154',
				'1032 Tensas Dr'=>'306095 0147154',
				'1033 Tensas Dr'=>'305928 0147154',
				'1036 Tensas Dr'=>'306094 0147154',
				'1040 Tensas Dr'=>'306093 0147154',
				'1041 Tensas Dr'=>'400105 0147154',
				'1044 Tensas Dr'=>'306092 0147154',
				'1050 Manhattan Blvd'=>'210390 0147154',
				'1100 Tensas Dr'=>'306091 0147154',
				'1101 Tensas Dr'=>'305929 0147154',
				'1104 Tensas Dr'=>'306087 0147154',
				'1105 Tensas Dr'=>'305930 0147154',
				'1108 Tensas Dr'=>'306086 0147154',
				'1109 Tensas Dr'=>'305931 0147154',
				'1112 Tensas Dr'=>'306085 0147154',
				'1113 Tensas Dr'=>'305932 0147154',
				'1116 Tensas Dr'=>'306084 0147154',
				'1117 Tensas Dr'=>'305933 0147154',
				'1120 Tensas Dr'=>'306083 0147154',
				'1121 Tensas Dr'=>'400444 0147154',
				'1124 Tensas Dr'=>'306082 0147154',
				'1125 Tensas Dr'=>'305935 0147154',
				'1128 Tensas Dr'=>'306081 0147154',
				'1129 Tensas Dr'=>'305936 0147154',
				'1132 Tensas Dr'=>'306080 0147154',
				'1133 Tensas Dr'=>'305937 0147154',
				'1136 Tensas Dr'=>'306079 0147154',
				'1137 Tensas Dr'=>'305938 0147154',
				'1140 Tensas Dr'=>'306078 0147154',
				'1141 Tensas Dr'=>'305939 0147154',
				'1144 Tensas Dr'=>'306077 0147154',
				'1145 Tensas Dr'=>'305940 0147154',
				'1148 Tensas Dr'=>'305941 0147154',
				'1612 Apache Dr'=>'305943 0147154',
				'1616 Apache Dr'=>'305920 0147154',
				'1616 Ute Dr'=>'305666 0147154',
				'1617 Apache Dr'=>'305670 0147154',
				'1620 Ute Dr'=>'306197 0147154',
				'1621 Apache Dr'=>'305662 0147154',
				'1624 Ute Dr'=>'306198 0147154',
				'1625 Apache Dr'=>'305563 0147154',
				'1628 Ute Dr'=>'306199 0147154',
				'1629 Apache Dr'=>'305664 0147154'];
				
			$this->counter_subpages = 0; // counter to counter sub-pages.
			
			foreach ($this->data as $key=>$value) {
				$value = str_replace(' ', '', $value);
				echo 'Looking up #'.$value;
				
				$this->counter = 0; // Set a counter for num pages requested.
				$this->pin = $value; // Set a value for pin.
				
				// Write single instance of pin. (Not used.)
				// https://secure.php.net/manual/en/function.fputcsv.php
				//~ $fp = fopen('/var/www/html/mturk/.deleteme/jb-jp.csv', 'a');
				//~ fputcsv($fp, [ $value ]);
				//~ fclose($fp);
				
				$u = new stdClass;
				$u->request_to_file = true;
				$u->request_from_file = true;
				$u->filename = 'jb-jeffparish-2018-1-'.$value;
				$u->folder = '/var/www/html/mturk/.deleteme/';
				$u->tmp_cookie = true;
				$u->post = true;
				//~ $u->post_str = '__EVENTTARGET=&__EVENTARGUMENT=&__VIEWSTATE=&__VIEWSTATEGENERATOR=&__EVENTVALIDATION=&btnSubmit.x=35&btnSubmit.y=13&txtAcctNum='.$value;
				$u->post_str = '__EVENTTARGET=&__EVENTARGUMENT=&__VIEWSTATE=%2FwEPDwUKLTg3NTkxNjE4MQ9kFgICAQ9kFgICAw9kFgICDQ8PFgIeB1Zpc2libGVoZGQYAQUeX19Db250cm9sc1JlcXVpcmVQb3N0QmFja0tleV9fFgEFCWJ0blN1Ym1pdKTFjRbDMoRH11WW8QBEhtKEwcFgvPR7Zh9Mrc0WMUU%2B&__VIEWSTATEGENERATOR=6F86BD96&__EVENTVALIDATION=%2FwEdAAOEotD6JC57L%2B%2F36W7xKR7lPOaW1pQztoQA36D1w%2F%2BbXbsGy5j3K3wTk9sm1qsJx45OQGHX960SC6fV5wNLB%2FgpvyRl6BP99VxAqEqXjT6bwQ%3D%3D&btnSubmit.x=35&btnSubmit.y=13&txtAcctNum='.$value;
				$u->url = 'http://waterbill.jeffparish.net/loginscreen.aspx?ReturnUrl=%2fmeterreads.aspx';
				$rslt = curl_get($u);
				$this->counter_subpages++; // Increment sub-page count.
				// Check exists.
				if (file_exists($u->folder . $u->filename)) {
					$this->l(file_get_contents($u->folder . $u->filename));
				} else {
					$rslt = curl_get($u);
					if ($rslt->status != 200) {
						echo 'not 200!';
						exit;
					} else {
						$this->l($rslt->html);
					}
				}
				// Add a newline to separate entries.
				file_put_contents('/var/www/html/mturk/.deleteme/jb-jp.csv', "\n", FILE_APPEND);
			}
			
			// Echo some stats
			echo 'Num sub-pages downloaded: '.$this->counter_subpages;
		}
		function l($html) {
			// Wrapper for data
			$rgx = '/<td align="center" valign="middle" style="white-space:nowrap;">[\r\n\t\s]+<span id="dgmeterreads_trdate2_\d+">(.+?)<\/span>[\r\n\t\s]+<\/td><td align="center" valign="middle" style="white-space:nowrap;">[\r\n\t\s]+<span id="dgmeterreads_Label1_\d+">(.+?)<\/span>[\r\n\t\s]+<\/td><td align="center" valign="middle" style="white-space:nowrap;">[\r\n\t\s]+<span id="dgmeterreads_Label1_\d+">(.+?)<\/span>[\r\n\t\s]+<\/td><td align="center" valign="middle" style="white-space:nowrap;">[\r\n\t\s]+<span id="dgmeterreads_Label1_\d+">(.+?)<\/span>[\r\n\t\s]+<\/td>/';
			preg_match_all($rgx, $html, $m);
			// https://secure.php.net/manual/en/function.fputcsv.php
			$fp = fopen('/var/www/html/mturk/.deleteme/jb-jp.csv', 'a');
			if ($m) {
				$counter = 0;
				foreach($m[1] as $key=>$value) {
					$fields = [$this->pin]; // Starts with acct #.
					array_push($fields, $value);
					array_push($fields, $m[2][$counter]);
					array_push($fields, $m[3][$counter]);
					array_push($fields, $m[4][$counter]);
					fputcsv($fp, $fields);
					$counter++;
				}
				fclose($fp);
				$this->counter_subpages++; // Increment sub-page count.
			} else {
				echo 'no matches found';
				exit;
			}

			// Does "next exist"?
			// If so, get next and add to current.
			$rgx = '/<a href="javascript:__doPostBack\(&#39;dgmeterreads\$ctl(\d+)\$ctl(\d+)&#39;,&#39;&#39;\)" style="color:Black;">> Next <\/a>/';
			preg_match($rgx, $html, $m);
			if ($m) {
				$this->dl_next_page($m[1], $m[2], $html);
			}
		}
		function dl_next_page($p1, $p2, $html) {
			echo 'next page:'.$p1.','.$p2;
			
			// Scrape form data.
			$v1 = '/<input type="hidden" name="__VIEWSTATE" id="__VIEWSTATE" value="(.+?)"/';
			$v2 = '/<input type="hidden" name="__VIEWSTATEGENERATOR" id="__VIEWSTATEGENERATOR" value="(.+?)"/';
			$v3 = '/<input type="hidden" name="__EVENTVALIDATION" id="__EVENTVALIDATION" value="(.+?)"/';
			preg_match($v1, $html, $v1);
			preg_match($v2, $html, $v2);
			preg_match($v3, $html, $v3);
			if (!$v1 || !$v2 || !$v3) {
				echo 'missing form data!';
				exit;
			}
			
			// next page
			$u = new stdClass;
			$u->request_to_file = true;
			$u->request_from_file = true;
			$u->filename = 'jb-jeffparish-2018-2-'.$this->pin.'_'.$this->counter;
			$u->folder = '/var/www/html/mturk/.deleteme/';
			$u->tmp_cookie = true;
			$u->post = true;
			$u->post_str = '__EVENTARGUMENT=&__VIEWSTATE='.urlencode($v1[1]).'&__VIEWSTATEGENERATOR='.urlencode($v2[1]).'&__EVENTVALIDATION='.urlencode($v3[1]).'&btnSubmit.x=35&btnSubmit.y=13&__EVENTTARGET=dgmeterreads$ctl'.$p1.'$ctl'.$p2;
			$u->url = 'http://waterbill.jeffparish.net/meterreads.aspx';
			
			// Increment the page num counter.
			$this->counter++;
			
			// Continue.
			if (file_exists($u->folder . $u->filename)) {
				$this->l(file_get_contents($u->folder . $u->filename));
			} else {
				$rslt = curl_get($u);
				if ($rslt->status != 200) {
					echo 'not 200!';
					exit;
				} else {
					$this->l($rslt->html);
				}
			}
		}
	}
	new JB_JeffParish();
	exit;
}
/**
* Geography_of_Transport
**/
if ($argv[1] == 'Geography_of_Transport') {
	class Geography_of_Transport {
		function __construct() {
			global $argv;
			$this->init();
		}
		function cleanPage($src) {
			$src = preg_replace('/<header>(.+?)<\/header>/s', '<section"><strong><bold>$1</bold></strong></section>', $src);
			$src = preg_replace('/[ \t]+/s', ' ', $src);
			$src = preg_replace('/<p /s', '<div ', $src);
			$src = preg_replace('/<p>/s', '<div>', $src);
			$src = preg_replace('/<\/p>/s', '<\/div>', $src);
			return $src;
		}
		function init() {
			$indx = 'http://people.hofstra.edu/geotrans/eng/content.html';				// Index
			$menu = 'http://people.hofstra.edu/geotrans/eng/ch%uen/ch%umenu.html';		// Chapters, %u=...Unsigned decimal number (>=0)
			$conc = 'http://people.hofstra.edu/geotrans/eng/conclusion.html';			// Conclusion
			$appx = 'http://people.hofstra.edu/geotrans/eng/methods/tg_methods.html';	// Appendix
			$css  = '<style type="text/css">
					header {margin: 8px 0}
					section {text-indent: 50px;}
					div {display:block;}
					img {max-width:90%;height:auto;}
				</style>';
			// Empty the output file. (Reset.)
			file_put_contents('/var/www/html/transport-geography.html', '<html><head>'.$css.'</head><body>');
			// Index
			$u = new stdClass;
			$u->request_to_file = true;
			$u->request_from_file = true;
			$u->filename = 'indx';
			$u->folder = '/var/www/html/mturk/.deleteme/';
			$u->url = $indx;
			$rslt = curl_get($u);
			if ($rslt->status != 200) {
				echo 'status error! '.$rslt->status;
				exit;
			}
			preg_match('/<article>(.+?)<\/article>/si', $rslt->html, $n);
			if (!$n) {
				echo 'BAD ARTICLE! Exiting.'."\n";
				exit;
			}
			$n[1] = cleanPage($n[1]);
			file_put_contents('/var/www/html/transport-geography.html', $n[1] . "<br><hr><br>", FILE_APPEND);
			// Loop through main pages.
			for ($i=1; $i<9; $i++) {
				$u = new stdClass;
				$u->request_to_file = true;
				$u->request_from_file = true;
				$u->filename = 'chap'.$i;
				$u->folder = '/var/www/html/mturk/.deleteme/';
				$u->url = sprintf($menu, $i, $i);
				$rslt = curl_get($u);
				if ($rslt->status != 200) {
					echo 'status error! '.$rslt->status;
					exit;
				}
				// Parse
				$doc = new DOMDocument();
				@$doc->loadHTML($rslt->html);
				// Get main content
				$m = $doc->getElementById('mainContent');
				// For the "Reader" version, remove all web links?
				// Nah. Unnecessary...
				// ...
				///
				// For the reader version...
				// Save as straight HTML.
				// 
				///
				preg_match('/<article>(.+?)<\/article>/si', $rslt->html, $n);
				if (!$n) {
					echo 'BAD ARTICLE! Exiting.'."\n";
					exit;
				}
				$n[1] = cleanPage($n[1]);
				// Write to file div mainContent.
				// Add line break between pages.
				file_put_contents('/var/www/html/transport-geography.html', $n[1] . "<br><hr><br>", FILE_APPEND);
				// Loop through concept and application links.
				// Individual sections of chapter.
				// This is 1) concepts, 2) applications.
				// Get links.
				// Only retrieve links that are: ^conc, ^appl.
				// Full href is http://people.hofstra.edu/geotrans/eng/ch1en/<link, e.g. "appl1en/ch1a1en.html">
				$links = $m->getElementsByTagName('a');
				$counter = 0; // Counter for filenames.
				// Tertiary page links (will be lowest level traversed)
				$tertpages = [];
				// Loop through secondary page links
				foreach ($links as $link) {
					echo $link->getAttribute('href')."\n";
					$l = $link->getAttribute('href');
					echo $l."\n";
					if (strpos($l, 'conc') === 0 ||
						strpos($l, 'appl') === 0
						) {
						echo 'retrieve:'.$l."\n";
						$new = sprintf('http://people.hofstra.edu/geotrans/eng/ch%uen/'.$l, $i);
						echo $new."\n";
						$u = new stdClass;
						$u->request_to_file = true;
						$u->request_from_file = true;
						$u->filename = 'chap'.$i.'section'.$counter;
						$u->folder = '/var/www/html/mturk/.deleteme/';
						$u->url = $new;
						$rslt2 = curl_get($u);
						if ($rslt2->status != 200) {
							echo 'status error! '.$rslt2->status;
							exit;
						}
						$doc2 = new DOMDocument();
						@$doc2->loadHTML($rslt2->html);
						// Get main content
						$m2 = $doc2->getElementById('mainContent');
						// Get tertiary links
						$links2 = $m2->getElementsByTagName('a');
						foreach ($links2 as $link2) {
							$l2 = $link2->getAttribute('href');
							echo 'Found tert. page ' . $l2 . "\n";
							// First of all, do not get pages not on same level (contains "/")
							// Contains "/"?
							if (strpos($l2, '/') !== false) {
								continue;
							}
							// Base
							$base = preg_replace('/\/[^\/]+$/s', '/', $u->url);
							// Make full URL
							$l2 = $base . $l2;
							// Now, they must be unique.
							if (!in_array($l2, $tertpages)) {
								array_push($tertpages, $l2);
							}
						}
						///
						// For the reader version...
						// Save as straight HTML.
						///
						preg_match('/<article>(.+?)<\/article>/si', $rslt2->html, $n);
						if (!$n) {
							echo 'BAD ARTICLE! Exiting.'."\n";
							exit;
						}
						$n[1] = cleanPage($n[1]);
						// Save
						file_put_contents('/var/www/html/transport-geography.html', $n[1] . "<br><hr><br>", FILE_APPEND);
						// Increment counter.
						$counter++;
					}
				}
				// Now that all of the secondary pages are scraped,
				// let's go get the tertiary pages. The tertiary
				// pages will be thrown in at the end of the document.
				$counter = 0;
				foreach ($tertpages as $link) {
					echo 'retrieve:'.$link."\n";
					$u = new stdClass;
					$u->request_to_file = true;
					$u->request_from_file = true;
					$u->filename = 'tertpage'.$counter;
					$u->folder = '/var/www/html/mturk/.deleteme/';
					$u->url = $link;
					$rslt = curl_get($u);
					if ($rslt->status != 200) {
						echo 'status error! '.$rslt->status;
						exit;
					}
					//~ // Remove \n
					//~ $rslt->html = str_replace("\n",'',$rslt->html);
					///
					// For the reader version...
					// Save as straight HTML.
					///
					preg_match('/<article>(.+?)<\/article>/si', $rslt->html, $n); // s for newline
					if (!$n) {
						echo 'BAD ARTICLE! Exiting.'."\n";
						exit;
					}
					// Download all images to /var/www/html/transport-geography-images/
					// Replace <img> with correct location.
					preg_match('/<img(?:.+?)src="img\/([^"]+)"/si', $rslt->html, $n2);
					if ($n2) {
						// Remove trailing filename, e.g. "/.../bb.html" becomes "/.../".
						$base = preg_replace('/\/[^\/]+$/', '/', $u->url);
						// Make remove URL
						$rem = $base . 'img/' . $n2[1];
						// Local file
						$f = '/var/www/html/transport-geography-images/' . $n2[1];
						// Save image but only if it does not yet exist.
						// Or if the file is blank.
						if (!file_exists($f) || trim(file_get_contents($f)) == '') {
							// Save image.
							file_put_contents(
								$f,
								file_get_contents( $rem )
							);
						}
						// Update image location in source.
						$n[1] = preg_replace('/<img(?:.+?)src="img/s', '<img src="transport-geography-images', $n[1]);
					}
					$n[1] = cleanPage($n[1]);
					// Now save to file.
					file_put_contents('/var/www/html/transport-geography.html', $n[1] . "<br><hr><br>", FILE_APPEND);
					// Increment counter.
					$counter++;
				}
			}
			// Conclusion
			$u = new stdClass;
			$u->request_to_file = true;
			$u->request_from_file = true;
			$u->filename = 'conc';
			$u->folder = '/var/www/html/mturk/.deleteme/';
			$u->url = $conc;
			$rslt = curl_get($u);
			if ($rslt->status != 200) {
				echo 'status error! '.$rslt->status;
				exit;
			}
			preg_match('/<article>(.+?)<\/article>/i', str_replace("\n",'',$rslt->html), $n);
			if (!$n) {
				echo 'BAD ARTICLE! Exiting.'."\n";
				exit;
			}
			$n[1] = cleanPage($n[1]);
			// Now save to file.
			file_put_contents('/var/www/html/transport-geography.html', $n[1] . "<br><hr><br>", FILE_APPEND);
			// Appendix
			$u = new stdClass;
			$u->request_to_file = true;
			$u->request_from_file = true;
			$u->filename = 'appx';
			$u->folder = '/var/www/html/mturk/.deleteme/';
			$u->url = $appx;
			$rslt = curl_get($u);
			if ($rslt->status != 200) {
				echo 'status error! '.$rslt->status;
				exit;
			}
			preg_match('/<article>(.+?)<\/article>/i', str_replace("\n",'',$rslt->html), $n);
			if (!$n) {
				echo 'BAD ARTICLE! Exiting.'."\n";
				exit;
			}
			file_put_contents('/var/www/html/transport-geography.html', $n[1] . "<br><hr><br>", FILE_APPEND);
			// Parse
			$doc = new DOMDocument();
			@$doc->loadHTML($rslt->html);
			// Get main content
			$m = $doc->getElementById('mainContent');
			$links = $m->getElementsByTagName('a');
			$counter = 0; // Counter for filenames.
			foreach ($links as $link) {
				$l = $link->getAttribute('href');
				if (strpos($l, 'ch') === 0
					) {
					echo 'retrieve:'.$l."\n";
					$new = 'http://people.hofstra.edu/geotrans/eng/methods/'.$l;
					echo $new."\n";
					$u = new stdClass;
					$u->request_to_file = true;
					$u->request_from_file = true;
					$u->filename = 'methods'.$counter;
					$u->folder = '/var/www/html/mturk/.deleteme/';
					$u->url = $new;
					$rslt2 = curl_get($u);
					if ($rslt2->status != 200) {
						echo 'status error! '.$rslt2->status;
						exit;
					}
					///
					// For the reader version...
					// Save as straight HTML.
					///
					preg_match('/<article>(.+?)<\/article>/i', str_replace("\n",'',$rslt2->html), $n);
					if (!$n) {
						echo 'BAD ARTICLE! Exiting.'."\n";
						exit;
					}
					$n[1] = cleanPage($n[1]);
					// Now save to file.
					file_put_contents('/var/www/html/transport-geography.html', $n[1] . "<br><hr><br>", FILE_APPEND);
					// Increment counter.
					$counter++;
				}
			}
			// End page
			file_put_contents('/var/www/html/transport-geography.html', '</body></html>', FILE_APPEND);
		}
	}
	new Geography_of_Transport();
	exit;
}
/**
 * ca_tally_csv
 * See http://vote.sos.ca.gov/returns/maps/president/party/democratic/
 * Make a "csv" file
 * 
 * TODO: For each county update, find the nearast unprocessed timestamp
 * 		 for that county. So when e.g.:
 * 		  Alameda=June 13 2016 6:30 PM
 * 		  unprocessed=6/13/2016 5:20 p.m.
 * 		 Then make another column that notes that unprocessed timestamp.
 */
if ($argv[1] == 'ca_tally_csv') {
	class ca_tally_csv {
		private $mysqli;
		private $stores_arr;
		function __construct() {
			global $argv;
			$this->mysqli = connect__();
			$this->mysqli->autocommit(FALSE);
			$this->dl(); // Totals
			$this->ub(); // Make a sheet for unprocessed ballots
		}
		function closest_upbc() { // County: Closest unprocessed ballots count
			
		}
		function ub() {
			$csv = ',Vote By Mail,Provisional,Other,Total,Last Update PDT'."\n";
			$csvc= 'County,Vote By Mail,Provisional,Other,Total,Last Update PDT'."\n";
			$q = 'SELECT * FROM FLBS_gen_tasks.ca_unprocessed_counties
				ORDER BY county ASC, timestamp DESC
				';
			$query = $this->mysqli->query($q) or die(mysqli_error($this->mysqli));
			$csv1 = '';
			$csv2 = '';
			while ($row = mysqli_fetch_array($query)) {
				// Remove [.,] characters.
				$row['timestamp_str'] = preg_replace('/[,\.]/', '', $row['timestamp_str']);
				$str =
					'"'.$row['county'].'",'.
					'"'.number_format($row['vbm']).'",'.
					'"'.number_format($row['prov']).'",'.
					'"'.number_format($row['oth']).'",'.
					'"'.number_format($row['total']).'",'.
					'"'.$row['timestamp_str'] . '"' .
					"\n"
					;
				if ($row['county'] == 'Statewide') {
					$csv1 .= $str; 
				} else {
					$csv2 .= $str;
				}
			}
			file_put_contents('/var/www/html/dev/ca_tally_provisionals.csv', $csv . $csv1);
			file_put_contents('/var/www/html/dev/ca_tally_provisionals_county.csv', $csvc . $csv2);
		}
		function dl() {
			global $argv;
			$q = 'SELECT ca_tally.id as id,
					county,
					hi,
					ro,
					he,
					ke,
					be,
					mi,
					wi,
					run_id,
					bc,
					te,
					ji,
					jo,
					do,
					third_party_total,
					p.timestamp as timestamp
				FROM FLBS_gen_tasks.ca_tally
				LEFT JOIN FLBS_gen_tasks.ca_runs AS p ON p.id = run_id
				ORDER BY county ASC, p.id DESC
			';
			// Get the JSON of Ballots Cast from CSS.
			// This is object with counties, eg (int) $bccss->Alameda->voterTurnout
			//~ $bccss = file_get_contents('http://api.sos.ca.gov/api/status?format=json&file=16PP');
			//~ $bccss = json_decode($bccss);
			$u = new stdClass;
			$u->request_to_file = true;
			$u->request_from_file = true;
			$u->filename = 'ca-2016-tmpjson-'.time();
			$u->folder = '/var/www/html/mturk/.deleteme/';
			$u->url = 'http://api.sos.ca.gov/api/status?format=json&file=16PP';
			if (file_exists($u->folder . $u->filename)) {
				$this->l('-');
			}
			$rslt = curl_get($u);
			if ($rslt->status != 200) {
				$this->l( 'error status='.$rslt->status );
				exit;
			}
			$bccss = json_decode($rslt->html);
			// Note: Per C. Rucinski, remove "," and "." from timestamp.
			// This is to allow Google Sheets to parse dates, apparently.
			// Add: Total votes since June 7. Total #change since June 7.
			// June15, 21:45, changing from "6172623" to "6200000" estimated
			// Democratic ballots.
			// Total ballots ratio (D/R): 3,467,011+1,174,829+176,620+144,125+54,145+11,101=5027831
			// 							  3,467,011/5027831=0.689563949.
			// Note: Current BC From CSS @
			// 		 http://vote.sos.ca.gov/returns/status/
			$query = $this->mysqli->query($q) or die(mysqli_error($this->mysqli));
			$csv1 = $csv2 = 'County,HC%,BS%,HC,BS,Total DEM Votes,Estimated Reporting**,Total-Running-Offset%,HC%-Running-Offset,BS%-Running-Offset,Precinct Last Updated PDT,Total Dem/Rep Since 6/7,Nearest U.P.B.C.,Total DEM Since 6/7,HC Since 6/7,BS Since 6/7,Total Rep Votes,Tot 3rd Party Votes,Total Dem/Rep Votes,Total Dem/Rep/3rd Votes,Latest BC CSS,CSS BC Missing,CSS BC Discrpcy,R. De La Fuente%,H. Hewes%,K. Judd%,M. Steinberg%,W. Wilson%,R. De La Fuente,H. Hewes,K. Judd,M. Steinberg,W. Wilson,Gap Direction (S/B),Flipped County'."\n";
			if (mysqli_num_rows($query) != 0) {
				// Set the "baseline" percentage for running % offset.
				// This is the June 7 tally.
				$baselines = array();
				while ($x = mysqli_fetch_array($query)) {
					if ($x['timestamp'] != NULL) continue; // Skip all but initial (Jun 7)!
					$tot = $x['hi'] + $x['be'] + $x['ro'] + $x['he'] + $x['ke'] + $x['mi'] + $x['wi'];
					$tot_rep = $x['bc'] + $x['te'] + $x['ji'] + $x['jo'] + $x['do'];
					// This array only contains the details for June 7.
					// All other rows are skipped.
					// See above: timestamp != "NULL"...
					$baselines[ $x['county'] ] = array(
						'hi'=>$x['hi'] / $tot,
						'be'=>$x['be'] / $tot,
						'june7-total' => $tot,
						'june7-hi-total' => $x['hi'],
						'june7-be-total' => $x['be'],
						'june7-total-republican' => $tot_rep,
						'june7-total-dem-rep' => $tot+$tot_rep,
						'flipped_county_id' => false, // Currently programmed as one-time event
						'latest_county_id' => false,  // Database id of most recent result.
						'latest_winning' => '', // Blank now, set to H or B on first run below, see next while loop
						'css_ballots' => false, // False now, set to true on latest result
					);
				}
				// Reset MySQL pointer.
				mysqli_data_seek($query, 0);
				// Make a flipped column!
				// Blank "-" or "F" flipped.
				while ($x = mysqli_fetch_array($query)) {
					if ($x['timestamp'] == NULL) continue; // Skip Jun 7... unnecessary to do this but whatev...
					$tot = $x['hi'] + $x['be'] + $x['ro'] + $x['he'] + $x['ke'] + $x['mi'] + $x['wi'];
					$tot_rep = $x['bc'] + $x['te'] + $x['ji'] + $x['jo'] + $x['do'];
					// This is overwritten each run until it comes
					// to the point where HC is ahead of BS. The ID
					// run is set to the FIRST row where BS is ahead of
					// HC after trailing.
					if ($baselines[ $x['county'] ]['latest_winning'] == '') {
						$baselines[ $x['county'] ]['latest_winning'] = ($x['hi'] <= $x['be']) ? 'B' : 'H'; // Ties go to B
					}
					// Only set this on the first occurrence!
					// If multiple flips in the same county then only
					// show the flip on the latest. Also, it is only
					// flipped if it started H June 7 and it not H
					// in the latest result.
					// Beware: Every county is updated on *every* run.
					// This means that for the majority of runs a
					// county will *not* differ in vote count!
					if ($baselines[ $x['county'] ]['june7-hi-total'] > $baselines[ $x['county'] ]['june7-be-total'] &&
						$x['hi'] < $x['be'] &&
						$baselines[ $x['county'] ]['latest_winning'] == 'B' && // Sanders up in latest
						$baselines[ $x['county'] ]['flipped_county_id'] == false  // 
						) {
						$unique_vote_count = $x['hi'] .'/'. $x['be'];
						$baselines[ $x['county'] ]['flipped_county_id'] = $unique_vote_count;
					}
				}
				// Reset MySQL pointer.
				mysqli_data_seek($query, 0);
				// Column p.timestamp is a string.
				$county = '';
				$tmp1 = '';
				$tmp2 = '';
				$currv = ''; // This is to check when to add or not add row
				$lastv = ''; // Same...
				$last_ts = '';
				// Results loop...
				while ($x = mysqli_fetch_array($query)) {
					$fmt = array(
						'tot_rep'=>null,
						'tot_dem_rep'=>null,
						'tot_dr_since_jun7'=>null,
						'tot_3rd'=>null,
						'tot_dem_rep_3rd'=>null,
					);
					$tot = $x['hi'] + $x['be'] + $x['ro'] + $x['he'] + $x['ke'] + $x['mi'] + $x['wi'];
					$tot_rep = $x['bc'] + $x['te'] + $x['ji'] + $x['jo'] + $x['do'];
					$tot_dem_rep = $tot + $tot_rep;
					$tot_dr_since_jun7 = $tot_dem_rep - $baselines[ $x['county'] ]['june7-total-dem-rep'];
					$tot_3rd = $x['third_party_total'];
					$tot_dem_rep_3rd = $tot_dem_rep + $tot_3rd;
					// 
					if ($tot_rep == 0) {//echo '--';
						$tot_dr_since_jun7 = '-'; // No scrape of Rep votes at this point. Do not tally bc it is OFF
						$tot_rep = '-';
						$tot_dem_rep = '-';
						$fmt['tot_rep'] = $tot_rep;
						$fmt['tot_dem_rep'] = $tot_dem_rep;
						$fmt['tot_dr_since_jun7'] = $tot_dr_since_jun7;
					} else {
						$fmt['tot_rep'] = number_format($tot_rep);
						$fmt['tot_dem_rep'] = number_format($tot_dem_rep);
						$fmt['tot_dr_since_jun7'] = number_format($tot_dr_since_jun7);
					}
					if ($x['third_party_total'] == NULL) {
						$fmt['tot_3rd'] = '-';
						$fmt['tot_dem_rep_3rd'] = '-';
					} else {
						$fmt['tot_3rd'] = number_format( $x['third_party_total'] );
						$fmt['tot_dem_rep_3rd'] = number_format( $tot_dem_rep + $x['third_party_total'] );
					}
					$currv = $x['hi'] .'/'. $x['be'];
					if ($x['timestamp'] == NULL) {
						$x['timestamp'] = 'June 7';
						// Get a timestamp of June 7 at Unix time, PDT (based on gmt)
						$upt = strtotime('June 7, 2016, 11:55 p.m. PDT');
					} else {
						// Get a timestamp of update time at Unix time, PDT (based on gmt)
						$upt = strtotime($x['timestamp'] . ' PDT');
					}
					// Find the nearest estimated unprocessed ballots
					// count.
					// Find nearest regardless of time.
					// If this is the "Most Recent" county result
					// (or the only county result) then get the
					// "Most Recent" county estimated upbc!
					if (!$baselines[ $x['county'] ]['latest_county_id']) {
						// Find "Most Recent"
						$q2 = 'SELECT * FROM FLBS_gen_tasks.ca_unprocessed_counties
							WHERE
								county="'.mysqli_real_escape_string($this->mysqli, $x['county']).'"
							ORDER BY timestamp DESC
							LIMIT 1
							';
					} else {
						// Find "Nearest"
						$q2 = 'SELECT * FROM FLBS_gen_tasks.ca_unprocessed_counties
							WHERE
								county="'.mysqli_real_escape_string($this->mysqli, $x['county']).'"
							ORDER BY ABS(timestamp - '.$upt.') ASC
							LIMIT 1
							';
					}
					$query2 = $this->mysqli->query($q2) or die(mysqli_error($this->mysqli));
					if ($query2->num_rows == 0) {
						$approx_upbc = '-';
					} else {
						$z = mysqli_fetch_array($query2);
						$approx_upbc = number_format($z['total']);
					}
					// Timestamp format...
					$x['timestamp'] = preg_replace('/[\.,]/', '', $x['timestamp']);
					// Total reporting% (using ~6200000)
					$statewide_reporting = ($x['county'] == 'Statewide') ?
						'"' . (round($tot / 6200000, 4))*100 . '%",' :
						'-,'
						;
					// Statewide debug...
					// 2503064+7498+6794+6591+2044428+9962+10930 = 4589267
					if ($x['county'] == 'Statewide') {
						//
						//~ echo 
					}
					// CSS Ballots Cast, Missing, and Discrpancy
					if (!$baselines[ $x['county'] ]['css_ballots']) {
						$bccss_tmp = '"' . number_format($bccss->$x['county']->voterTurnout) . '",';
						if ($fmt['tot_dem_rep_3rd'] != '-' && $bccss->$x['county']->voterTurnout != 0) {
							$n = $bccss->$x['county']->voterTurnout - $tot_dem_rep_3rd;
							$bccss_tmp .= '"' . number_format($n) . '",';
							$bccss_tmp .= round($n / $bccss->$x['county']->voterTurnout, 4) * 100 . '%,';
						} else {
							$bccss_tmp .= '-,-,';
						}
					} else {
						$bccss_tmp = '-,-,-,';
					}
					// June 7
					// Total swing here=0.
					// No need to set css_ballots=true !
					// new = Total Rep Votes,Tot 3rd Party Votes,Total Dem/Rep Votes,Total Dem/Rep/3rd Votes,
					if ($x['timestamp'] == 'June 7') {
						if ($currv != $lastv) { // If new vote...
							if ($x['county'] == 'Statewide')
								$csv1 .= $tmp1;
							else
								$csv2 .= $tmp1;
							//~ $csv .= $tmp1;
						}//echo $bccss_tmp . ' ==2 "' . $tot_rep ."\"\n";
						// Add to CSV
						$tmpcsv = $x['county'] . ',' .
							'"' . number_format($x['hi']) . '",' .
							'"' . number_format($x['be']) . '",' .
							round($x['hi'] / $tot, 4) * 100 . '%,' .
							round($x['be'] / $tot, 4) * 100 . '%,' .
							'"' . number_format($tot) . '",' .
							$statewide_reporting . 
							'-,-,-,'.
							'"' . $x['timestamp']  . '",' .
							'-,' .
							'"' . $approx_upbc . '",' .
							'"' . number_format($tot - $baselines[ $x['county'] ]['june7-total']) . '",' .
							'"' . number_format($x['hi'] - $baselines[ $x['county'] ]['june7-hi-total']) . '",' .
							'"' . number_format($x['be'] - $baselines[ $x['county'] ]['june7-be-total']) . '",' .
							'"' . $fmt['tot_rep'] . '",' .
							'"' . $fmt['tot_3rd'] . '",' .
							'"' . $fmt['tot_dem_rep'] . '",' .
							'"' . $fmt['tot_dem_rep_3rd'] . '",' .
							//Current BC From CSS,CSS BC Discrpcy,
							$bccss_tmp . 
							round($x['ro'] / $tot, 4) * 100 . '%,' .
							round($x['he'] / $tot, 4) * 100 . '%,' .
							round($x['ke'] / $tot, 4) * 100 . '%,' .
							round($x['mi'] / $tot, 4) * 100 . '%,' .
							round($x['wi'] / $tot, 4) * 100 . '%,' .
							'"' . number_format($x['ro']) . '",' .
							'"' . number_format($x['he']) . '",' .
							'"' . number_format($x['ke']) . '",' .
							'"' . number_format($x['mi']) . '",' .
							'"' . number_format($x['wi']) . '",' .
							'-,-' . // Swing direction and Flipped here is always blank
							"\n"
							;
						if ($x['county'] == 'Statewide')
							$csv1 .= $tmpcsv;
						else
							$csv2 .= $tmpcsv;
						continue;
					}
					if ($county != $x['county']) { // New county
						$county = $x['county'];
						$lastv = $currv; // Reset lastv
						$last_ts = $x['timestamp']; // Reset last_ts
					}
					// If new vote...
					// Or if it is Statewide and timestamps differ.
					// Basically, always run when Statewide as long as
					// timestamps differ.
					if ($currv != $lastv || 
						(	$x['timestamp'] != $last_ts && 
							$x['county'] == 'Statewide')
						) {
						$lastv = $currv;
						$last_ts = $x['timestamp']; // Reset last_ts
						
						// Add the temp "previous row" to CSV
						if ($x['county'] == 'Statewide')
							$csv1 .= $tmp1;
						else
							$csv2 .= $tmp1;
						// Set the latest county to TRUE.
						// This is for estimated unprocessed ballots count.
						// Must be set to FALSE on FIRST county result.
						// Then on all subsequent county results it
						// will be set to TRUE.
						$baselines[ $x['county'] ]['latest_county_id'] = true;
						// Set county CSS Ballots Cast.
						// For all remaining results in this county
						// the CSS Ballots Cast will no longer appear!
						$baselines[ $x['county'] ]['css_ballots'] = true;
						// Edge case... When this is Statewide,
						// set $bccss_tmp to none.
						if ($x['county'] == 'Statewide')
							$bccss_tmp = '-,-,-,';
					}
					// Swings
					$swings = [
						'h' => abs((($x['hi'] / $tot) - $baselines[ $x['county'] ]['hi'])), // e.g. 
						'b' => abs((($x['hi'] / $tot) - $baselines[ $x['county'] ]['hi'])),
					];
					$tot_swing = $swings['h'] + $swings['b'];
					$tot_swing = round($tot_swing, 4) * 100;
					// Who gains the most ground? Or rather, which
					// direction is it going?
					// Consider exactly 0 to also be a swing toward Bernie.
					// Which direction is higher? Swing to Bernie or
					// swing to Clinton?
					$is_ber_swing = ((($x['hi'] / $tot) - $baselines[ $x['county'] ]['hi']) > 0) ? 'C' : 'S';
					// Make a flipped column!
					// Blank "-" or "F" flipped.
					// Is this row where a flip occurs!?
					$col_flipped = '-';
					if ($baselines[ $x['county'] ]['flipped_county_id'] == $currv) {
						$col_flipped = 'F';
					}
					// Format output row...
					$tmp1 = $x['county'] . ',' .
						'"' . number_format($x['hi']) . '",' .
						'"' . number_format($x['be']) . '",' .
						round($x['hi'] / $tot, 4) * 100 . '%,' .
						round($x['be'] / $tot, 4) * 100 . '%,' .
						'"' . number_format($tot) . '",' .
						$statewide_reporting . 
						$tot_swing . '%,' .
						round(($x['hi'] / $tot) - $baselines[ $x['county'] ]['hi'], 4)*100 . '%,' . //hc off
						round(($x['be'] / $tot) - $baselines[ $x['county'] ]['be'], 4)*100 . '%,' . //bs off
						'"' . $x['timestamp']  . '",' .
						'"' . $fmt['tot_dr_since_jun7'] . '",' .
						'"' . $approx_upbc . '",' .
						'"' . number_format($tot - $baselines[ $x['county'] ]['june7-total']) . '",' .
						'"' . number_format($x['hi'] - $baselines[ $x['county'] ]['june7-hi-total']) . '",' .
						'"' . number_format($x['be'] - $baselines[ $x['county'] ]['june7-be-total']) . '",' .
						'"' . $fmt['tot_rep'] . '",' .
						'"' . $fmt['tot_3rd'] . '",' .
						'"' . $fmt['tot_dem_rep'] . '",' .
						'"' . $fmt['tot_dem_rep_3rd'] . '",' .
						//Current BC From CSS,CSS BC Discrpcy,
						$bccss_tmp . 
						round($x['ro'] / $tot, 4) * 100 . '%,' .
						round($x['he'] / $tot, 4) * 100 . '%,' .
						round($x['ke'] / $tot, 4) * 100 . '%,' .
						round($x['mi'] / $tot, 4) * 100 . '%,' .
						round($x['wi'] / $tot, 4) * 100 . '%,' .
						'"' . number_format($x['ro']) . '",' .
						'"' . number_format($x['he']) . '",' .
						'"' . number_format($x['ke']) . '",' .
						'"' . number_format($x['mi']) . '",' .
						'"' . number_format($x['wi']) . '",'  .
						'"' . $is_ber_swing . '",'  .
						'"' . $col_flipped . '"'  .
						"\n" 
						;
				}
			}
			file_put_contents('/var/www/html/dev/ca_tally_csv_statewide.csv', $csv1);
			file_put_contents('/var/www/html/dev/ca_tally_csv_counties.csv',  $csv2);
		}
	}
	new ca_tally_csv();
	exit;
}
/**
 * ca_tally
 * See http://vote.sos.ca.gov/returns/maps/president/party/democratic/
 * 
 */
if ($argv[1] == 'ca_tally') {
	class ca_tally {
		private $mysqli;
		private $stores_arr;
		function __construct() {
			global $argv;
			$this->mysqli = connect__();
			$this->mysqli->autocommit(FALSE);
			
			// Build snapshots of the given time stamp.
			// For each county in the json file, determine if its
			// result has changed since the last update.
			$this->dl();
		}
		function l($s) {
			$s .= "\n";
			file_put_contents( '/var/www/html/dev/ca_tally.log', $s, FILE_APPEND );
			echo $s;
		}
		/**
		 * Returns an array
		 * $cands_rb[ county ]['total'] = ...
		 * Total = ai + green + lib + p&f
		 */
		function third_parties() {
			$cands = array();
			// set error level
			// http://stackoverflow.com/questions/1685277/warning-domdocumentloadhtml-htmlparseentityref-expecting-in-entity
			$internalErrors = libxml_use_internal_errors(true);
			//////////
			// ai
			//////////
			$u = new stdClass;
			$u->request_to_file = true;
			$u->request_from_file = true;
			$u->filename = 'ca-2016-ai-'.time();
			$u->folder = '/var/www/html/mturk/.miscellaneous/';
			$u->url = 'http://vote.sos.ca.gov/returns/maps/president/party/american-independent/';
			if (file_exists($u->folder . $u->filename)) {
				$this->l('-');
			}
			$rslt2 = curl_get($u);
			if ($rslt2->status != 200) {
				$this->l( 'error status='.$rslt2->status );
				exit;
			}
			// Preg match all, counties
			preg_match_all('/document\.getElementById\(\'mapTableDisplay\'\)\.innerHTML = \'(.+?)\'/', $rslt2->html, $m);
			if (count($m[1]) != 59) {
				$this->l( 'Bad state count:' . count($m[1]) );
				exit;
			}
			// Loop counties
			foreach ($m[1] as $k=>$v) {
				// Parse each as HTML
				$d = new DOMDocument();
				$d->loadHTML($v);
				$div = $d->getElementsByTagName('div');
				$tr = $d->getElementsByTagName('tr');
				// Instantiate the county if necessary.
				if (!array_key_exists($div[0]->nodeValue, $cands))
					$cands[ $div[0]->nodeValue ] = 0;
				foreach ($tr as $b) {
					$td = $b->getElementsByTagName('td'); // Second td
					$votes = $td[2]->nodeValue;
					//$cand = strtolower(substr($td[1]->nodeValue, 0, 2)); // First 2 chars
					//if ($cand == 'be') $cand = 'bc'; // Ben Carson cannot be "be" because that is Sanders...
					$cands[ $div[0]->nodeValue ] += (int) trim(str_replace(',', '', $votes));
				}
			}
			//////////
			// green
			//////////
			$u = new stdClass;
			$u->request_to_file = true;
			$u->request_from_file = true;
			$u->filename = 'ca-2016-green-'.time();
			$u->folder = '/var/www/html/mturk/.miscellaneous/';
			$u->url = 'http://vote.sos.ca.gov/returns/maps/president/party/green/';
			if (file_exists($u->folder . $u->filename)) {
				$this->l('-');
			}
			$rslt2 = curl_get($u);
			if ($rslt2->status != 200) {
				$this->l( 'error status='.$rslt2->status );
				exit;
			}
			// Preg match all, counties
			preg_match_all('/document\.getElementById\(\'mapTableDisplay\'\)\.innerHTML = \'(.+?)\'/', $rslt2->html, $m);
			if (count($m[1]) != 59) {
				$this->l( 'Bad state count:' . count($m[1]) );
				exit;
			}
			// Loop counties
			foreach ($m[1] as $k=>$v) {
				// Parse each as HTML
				$d = new DOMDocument();
				$d->loadHTML($v);
				$div = $d->getElementsByTagName('div');
				$tr = $d->getElementsByTagName('tr');
				// Instantiate the county if necessary.
				if (!array_key_exists($div[0]->nodeValue, $cands))
					$cands[ $div[0]->nodeValue ] = 0;
				foreach ($tr as $b) {
					$td = $b->getElementsByTagName('td'); // Second td
					$votes = $td[2]->nodeValue;
					//$cand = strtolower(substr($td[1]->nodeValue, 0, 2)); // First 2 chars
					//if ($cand == 'be') $cand = 'bc'; // Ben Carson cannot be "be" because that is Sanders...
					$cands[ $div[0]->nodeValue ] += (int) trim(str_replace(',', '', $votes));
				}
			}
			//////////
			// libertarian
			//////////
			$u = new stdClass;
			$u->request_to_file = true;
			$u->request_from_file = true;
			$u->filename = 'ca-2016-libertarian-'.time();
			$u->folder = '/var/www/html/mturk/.miscellaneous/';
			$u->url = 'http://vote.sos.ca.gov/returns/maps/president/party/libertarian/';
			if (file_exists($u->folder . $u->filename)) {
				$this->l('-');
			}
			$rslt2 = curl_get($u);
			if ($rslt2->status != 200) {
				$this->l( 'error status='.$rslt2->status );
				exit;
			}
			// Preg match all, counties
			preg_match_all('/document\.getElementById\(\'mapTableDisplay\'\)\.innerHTML = \'(.+?)\'/', $rslt2->html, $m);
			if (count($m[1]) != 59) {
				$this->l( 'Bad state count:' . count($m[1]) );
				exit;
			}
			// Loop counties
			foreach ($m[1] as $k=>$v) {
				// Parse each as HTML
				$d = new DOMDocument();
				$d->loadHTML($v);
				$div = $d->getElementsByTagName('div');
				$tr = $d->getElementsByTagName('tr');
				// Instantiate the county if necessary.
				if (!array_key_exists($div[0]->nodeValue, $cands))
					$cands[ $div[0]->nodeValue ] = 0;
				foreach ($tr as $b) {
					$td = $b->getElementsByTagName('td'); // Second td
					$votes = $td[2]->nodeValue;
					//$cand = strtolower(substr($td[1]->nodeValue, 0, 2)); // First 2 chars
					//if ($cand == 'be') $cand = 'bc'; // Ben Carson cannot be "be" because that is Sanders...
					$cands[ $div[0]->nodeValue ] += (int) trim(str_replace(',', '', $votes));
				}
			}
			//////////
			// peace-and-freedom
			//////////
			$u = new stdClass;
			$u->request_to_file = true;
			$u->request_from_file = true;
			$u->filename = 'ca-2016-peace-and-freedom-'.time();
			$u->folder = '/var/www/html/mturk/.miscellaneous/';
			$u->url = 'http://vote.sos.ca.gov/returns/maps/president/party/peace-and-freedom/';
			if (file_exists($u->folder . $u->filename)) {
				$this->l('-');
			}
			$rslt2 = curl_get($u);
			if ($rslt2->status != 200) {
				$this->l( 'error status='.$rslt2->status );
				exit;
			}
			// Preg match all, counties
			preg_match_all('/document\.getElementById\(\'mapTableDisplay\'\)\.innerHTML = \'(.+?)\'/', $rslt2->html, $m);
			if (count($m[1]) != 59) {
				$this->l( 'Bad state count:' . count($m[1]) );
				exit;
			}
			// Loop counties
			foreach ($m[1] as $k=>$v) {
				// Parse each as HTML
				$d = new DOMDocument();
				$d->loadHTML($v);
				$div = $d->getElementsByTagName('div');
				$tr = $d->getElementsByTagName('tr');
				// Instantiate the county if necessary.
				if (!array_key_exists($div[0]->nodeValue, $cands))
					$cands[ $div[0]->nodeValue ] = 0;
				foreach ($tr as $b) {
					$td = $b->getElementsByTagName('td'); // Second td
					$votes = $td[2]->nodeValue;
					//$cand = strtolower(substr($td[1]->nodeValue, 0, 2)); // First 2 chars
					//if ($cand == 'be') $cand = 'bc'; // Ben Carson cannot be "be" because that is Sanders...
					$cands[ $div[0]->nodeValue ] += (int) trim(str_replace(',', '', $votes));
				}
			}
			////////
			// Now return array....
			////////
			// Restore error level
			libxml_use_internal_errors($internalErrors);
			return $cands;
		}
		function dl() {
			global $argv;
			// Get run id from database
			$r_run_id = 0;
			$r_timestamp = '';
			$q = 'SELECT * FROM FLBS_gen_tasks.ca_runs ORDER BY id desc';
			$query = $this->mysqli->query($q) or die(mysqli_error($this->mysqli));
			$x = mysqli_fetch_array($query);
			$r_timestamp = $x['timestamp'];
			$r_run_id = $x['id'];
			// Download latest
			// Dem latest
			$u = new stdClass;
			$u->request_to_file = true;
			$u->request_from_file = true;
			$u->filename = 'ca-2016-'.time();
			$u->folder = '/var/www/html/mturk/.miscellaneous/';
			$u->url = 'http://vote.sos.ca.gov/returns/maps/president/party/democratic/';
			if (file_exists($u->folder . $u->filename)) {
				$this->l('-');
			}
			$rslt = curl_get($u);
			if ($rslt->status != 200) {
				$this->l( 'error status='.$rslt->status );
				exit;
			}
			// Get timestamp (exact string match)
			preg_match(
				'/<span class="mapsPrecincts">[\r\n\s\t]+100\.0% \( 22,356 of 22,356 \) precincts partially reporting as of ([^\n]+)\n<\/span>/',
				$rslt->html,
				$m
			);
			if (!$m) {
				$this->l( 'Bad time:' . $m );
				exit;
			}
			//////
			// Has this been updated?
			//////
			if (trim($m[1]) == $r_timestamp) {
				$this->l( 'Run is already downloaded! Exiting. [ Run=' . $r_timestamp . ' ]' );
				// Remove file!
				$x = $u->folder . $u->filename;
				if (file_exists($x)) {
					unlink($x);
				}
				exit;
			}
			// Insert new run into database
			$q = '
				INSERT INTO FLBS_gen_tasks.ca_runs
				SET
					timestamp = "'.mysqli_real_escape_string($this->mysqli, trim($m[1])).'"
				';
			$query = $this->mysqli->query($q) or die(mysqli_error($this->mysqli));
			$r_run_id = $this->mysqli->insert_id;
			if (!$r_run_id) {
				$this->l( '!$r_run_id' );
				exit;
			}
			///////////////////////////
			// Get 3rd parties
			// ai, green, lib, p&f
			///////////////////////////
			$cands_3rd = $this->third_parties();
			//~ print_r($cands_3rd);exit;
			///////////////////////////
			// NOW GET THE REPUB.....
			// Repub (added Jun15)
			///////////////////////////
				$u = new stdClass;
				$u->request_to_file = true;
				$u->request_from_file = true;
				$u->filename = 'ca-2016-republican-'.time();
				$u->folder = '/var/www/html/mturk/.miscellaneous/';
				$u->url = 'http://vote.sos.ca.gov/returns/maps/president/party/republican/';
				if (file_exists($u->folder . $u->filename)) {
					$this->l('-');
				}
				$rslt2 = curl_get($u);
				if ($rslt2->status != 200) {
					$this->l( 'error status='.$rslt2->status );
					exit;
				}
			// Preg match all, counties (REPUB)
			preg_match_all('/document\.getElementById\(\'mapTableDisplay\'\)\.innerHTML = \'(.+?)\'/', $rslt2->html, $m);
			if (count($m[1]) != 59) {
				$this->l( 'Bad state count:' . count($m[1]) );
				exit;
			}
			// Add Repub to a global...
			$cands_rb = array();
			// Loop counties
			foreach ($m[1] as $k=>$v) {
				// Parse each as HTML
				$d = new DOMDocument();
				$d->loadHTML($v);
				$div = $d->getElementsByTagName('div');
				$tr = $d->getElementsByTagName('tr');
				$cands_rb[ $div[0]->nodeValue ] = array(
					'bc'=>'', // Ben carson, rather than "be" for "Ben"
					'te'=>'',
					'ji'=>'',
					'jo'=>'',
					'do'=>''
				);
				foreach ($tr as $b) {
					$td = $b->getElementsByTagName('td'); // Second td
					$votes = $td[2]->nodeValue;
					$cand = strtolower(substr($td[1]->nodeValue, 0, 2)); // First 2 chars
					if ($cand == 'be') $cand = 'bc'; // Ben Carson cannot be "be" because that is Sanders...
					$cands_rb[ $div[0]->nodeValue ][ $cand ] = trim(str_replace(',', '', $votes));
				}
			}
			// Preg match all, counties (DEM)
			preg_match_all('/document\.getElementById\(\'mapTableDisplay\'\)\.innerHTML = \'(.+?)\'/', $rslt->html, $m);
			if (count($m[1]) != 59) {
				$this->l( 'Bad state count:' . count($m[1]) );
				exit;
			}
			// Loop counties
			foreach ($m[1] as $k=>$v) {
				// Parse each as HTML
				$d = new DOMDocument();
				$d->loadHTML($v);
				$div = $d->getElementsByTagName('div');
				$tr = $d->getElementsByTagName('tr');
				$cands = [
					'county'=>$div[0]->nodeValue,
					'hi'=>'',
					'ro'=>'',
					'he'=>'',
					'ke'=>'',
					'be'=>'',
					'mi'=>'',
					'wi'=>'',
				];
				// Repub
				$rcands = $cands_rb[ $div[0]->nodeValue ];
				foreach ($tr as $b) {
					$td = $b->getElementsByTagName('td'); // Second td
					$votes = $td[2]->nodeValue;
					$cand = strtolower(substr($td[1]->nodeValue, 0, 2)); // First 2 chars
					$cands[ $cand ] = trim(str_replace(',', '', $votes));
						// No......
						// $votes = $b->getElementsByClassName
				}
				// Insert 
				$q = '
					INSERT INTO FLBS_gen_tasks.ca_tally
					SET
						county = "'.mysqli_real_escape_string($this->mysqli, $cands['county']).'",
						hi = "'.mysqli_real_escape_string($this->mysqli, $cands['hi']).'",
						ro = "'.mysqli_real_escape_string($this->mysqli, $cands['ro']).'",
						he = "'.mysqli_real_escape_string($this->mysqli, $cands['he']).'",
						ke = "'.mysqli_real_escape_string($this->mysqli, $cands['ke']).'",
						be = "'.mysqli_real_escape_string($this->mysqli, $cands['be']).'",
						mi = "'.mysqli_real_escape_string($this->mysqli, $cands['mi']).'",
						wi = "'.mysqli_real_escape_string($this->mysqli, $cands['wi']).'",
						bc = "'.mysqli_real_escape_string($this->mysqli, $rcands['bc']).'",
						te = "'.mysqli_real_escape_string($this->mysqli, $rcands['te']).'",
						ji = "'.mysqli_real_escape_string($this->mysqli, $rcands['ji']).'",
						jo = "'.mysqli_real_escape_string($this->mysqli, $rcands['jo']).'",
						do = "'.mysqli_real_escape_string($this->mysqli, $rcands['do']).'",
						third_party_total = "'.mysqli_real_escape_string($this->mysqli, $cands_3rd[ $div[0]->nodeValue ]).'",
						run_id = "'.mysqli_real_escape_string($this->mysqli, $r_run_id).'"
					';
					$query = $this->mysqli->query($q) or die(mysqli_error($this->mysqli));
					// The following was to update
					// the database for REP and Total3rdParty
					// in all latest cases.
							//~ // Update all totals for latest.......
							//~ // This is a fix for previous........
							//~ // Update in cases where DEM vote PRIOR to Jun15 is
							//~ // still the SAME. Update by setting this PRIOR to Jun15
							//~ // for all REP and 3rd county candidates.
							//~ // Set REP candidates....
							//~ $q = '
								//~ UPDATE FLBS_gen_tasks.ca_tally
								//~ SET
									//~ bc = "'.mysqli_real_escape_string($this->mysqli, $rcands['bc']).'",
									//~ te = "'.mysqli_real_escape_string($this->mysqli, $rcands['te']).'",
									//~ ji = "'.mysqli_real_escape_string($this->mysqli, $rcands['ji']).'",
									//~ jo = "'.mysqli_real_escape_string($this->mysqli, $rcands['jo']).'",
									//~ do = "'.mysqli_real_escape_string($this->mysqli, $rcands['do']).'",
									//~ third_party_total = "'.mysqli_real_escape_string($this->mysqli, $cands_3rd[ $div[0]->nodeValue ]).'"
								//~ WHERE
									//~ county = "'.mysqli_real_escape_string($this->mysqli, $cands['county']).'" &&
									//~ hi = "'.mysqli_real_escape_string($this->mysqli, $cands['hi']).'" &&
									//~ ro = "'.mysqli_real_escape_string($this->mysqli, $cands['ro']).'" &&
									//~ he = "'.mysqli_real_escape_string($this->mysqli, $cands['he']).'" &&
									//~ ke = "'.mysqli_real_escape_string($this->mysqli, $cands['ke']).'" &&
									//~ be = "'.mysqli_real_escape_string($this->mysqli, $cands['be']).'" &&
									//~ mi = "'.mysqli_real_escape_string($this->mysqli, $cands['mi']).'" &&
									//~ wi = "'.mysqli_real_escape_string($this->mysqli, $cands['wi']).'" &&
									//~ bc IS NULL &&
									//~ te IS NULL &&
									//~ ji IS NULL &&
									//~ jo IS NULL &&
									//~ do IS NULL &&
									//~ third_party_total IS NULL
								//~ ';
							//~ $query = $this->mysqli->query($q) or die(mysqli_error($this->mysqli));
							//~ // Update in cases where DEM and REP is set to LATEST
							//~ // but third_party_total is NULL.
							//~ // Set 3rd party candidates....
							//~ $q = '
								//~ UPDATE FLBS_gen_tasks.ca_tally
								//~ SET
									//~ third_party_total = "'.mysqli_real_escape_string($this->mysqli, $cands_3rd[ $div[0]->nodeValue ]).'"
								//~ WHERE
									//~ county = "'.mysqli_real_escape_string($this->mysqli, $cands['county']).'" &&
									//~ hi = "'.mysqli_real_escape_string($this->mysqli, $cands['hi']).'" &&
									//~ ro = "'.mysqli_real_escape_string($this->mysqli, $cands['ro']).'" &&
									//~ he = "'.mysqli_real_escape_string($this->mysqli, $cands['he']).'" &&
									//~ ke = "'.mysqli_real_escape_string($this->mysqli, $cands['ke']).'" &&
									//~ be = "'.mysqli_real_escape_string($this->mysqli, $cands['be']).'" &&
									//~ mi = "'.mysqli_real_escape_string($this->mysqli, $cands['mi']).'" &&
									//~ wi = "'.mysqli_real_escape_string($this->mysqli, $cands['wi']).'" &&
									//~ bc = "'.mysqli_real_escape_string($this->mysqli, $rcands['bc']).'" &&
									//~ te = "'.mysqli_real_escape_string($this->mysqli, $rcands['te']).'" &&
									//~ ji = "'.mysqli_real_escape_string($this->mysqli, $rcands['ji']).'" &&
									//~ jo = "'.mysqli_real_escape_string($this->mysqli, $rcands['jo']).'" &&
									//~ do = "'.mysqli_real_escape_string($this->mysqli, $rcands['do']).'" &&
									//~ third_party_total IS NULL
								//~ ';
							//~ $query = $this->mysqli->query($q) or die(mysqli_error($this->mysqli));
			}
			// Commit to db
			if (!$this->mysqli->commit()) {
				$this->l( 'Transaction commit failed' );
				exit;
			}
			// Send an update email for status!
			// Send the last "state" as the body,
			// as the last state is actually a statewide (county="Statewide")
			// count rather than a state.
			mail("_redacted_.com", 'CA Tally Update', json_encode($cands));
			// Let's try downloading the updated "unprocessed ballots report"
			// just out of curiosity...
			$curr_unproc = '/var/www/html/mturk/.miscellaneous/unprocessed-ballots-report-'.time().'.pdf';
			file_put_contents(
				$curr_unproc,
				file_get_contents(
					'http://elections.cdn.sos.ca.gov/statewide-elections/2016-primary/unprocessed-ballots-report.pdf'
				)
			);
			// Do pdf to html
			// Returns this:
			/*Alameda<br/>
22,279<br/>
42,000<br/>
0<br/>
<b>64,279</b><br/>
6/13/2016 5:20 p.m.<br/>*/
			shell_exec('/usr/bin/pdftohtml -noframes ' . $curr_unproc);
			// Now scrape html doc
			$a = file_get_contents($curr_unproc);
			// Try a preg match all
			//~ preg_match_all(
				//~ '/\n([a-zA-Z ]+)<br\/>\n([\d,]+)<br\/>\n([\d,]+)<br\/>\n([\d,]+)<br\/>\n<b>([\d,]+)<\/b><br\/>\n(6/\d+/2016 \d+:\d+ [ap]\.m\.)<br\/>/',
				//~ $a, $b
			//~ );
			//~ // Select all runs that match this timestamp
			//~ // 
		}
	}
	new ca_tally();
	exit;
}
// Add initial run of unprocessed ballots
/**
 * ca_tally
 * See http://vote.sos.ca.gov/returns/maps/president/party/democratic/
 * 
 */
if ($argv[1] == 'ca_tally_unprocessed_dl') {
	class ca_tally_unprocessed_dl {
		private $mysqli;
		private $stores_arr;
		function __construct() {
			global $argv;
			$this->mysqli = connect__();
			$this->mysqli->autocommit(FALSE);
			$this->dl();
		}
		function l($s) {
			$s .= "\n";
			file_put_contents( '/var/www/html/dev/ca_tally_up.log', $s, FILE_APPEND );
			// mail an error for this
			mail('_redacted_.com', 'ca_tally_unprocessed_dl error', $s);
			echo $s;
		}
		function dl() {
			// Start by downloading a running total. This is just
			// to cache this... kind of side note...
			$u = new stdClass;
			$u->request_to_file = true;
			$u->request_from_file = true;
			$u->filename = 'status'.time().'.json';
			$u->folder = '/var/www/html/mturk/.miscellaneous/ca-2016-json/';
			$u->url = 'http://api.sos.ca.gov/api/status?format=json&file=16PP';
			if (file_exists($u->folder . $u->filename)) {
				$this->l('-');
			}
			$rslt = curl_get($u);
			if ($rslt->status != 200) {
				$this->l( 'error status='.$rslt->status );
				exit;
			}
			// Download the PDF.
			$u = new stdClass;
			$u->request_to_file = true;
			$u->request_from_file = true;
			$u->filename = 'ca_tally_unprocessed_dl'.time();
			$u->folder = '/var/www/html/mturk/.miscellaneous/ca-2016/unprocessed/';
			$u->url = 'http://elections.cdn.sos.ca.gov/statewide-elections/2016-primary/unprocessed-ballots-report.pdf';
			if (file_exists($u->folder . $u->filename)) {
				$this->l('-');
			}
			$rslt = curl_get($u);
			if ($rslt->status != 200) {
				$this->l( 'error status='.$rslt->status );
				exit;
			}
			// Do pdf to html
			// Returns e.g. this:
			/*Alameda<br/>
22,279<br/>
42,000<br/>
0<br/>
<b>64,279</b><br/>
6/13/2016 5:20 p.m.<br/>*/
//=====ca_unprocessed_runs
//~ id	int(11) AI PK
//~ timestamp	datetime
//~ timestamp_str	varchar(45)
//~====ca_unprocessed_counties
//~ id	int(11) AI PK
//~ vbm	int(11)
//~ prov	int(11)
//~ oth	int(11)
//~ total	int(11)
//~ ca_unprocessed_runs_id	int(11)
//~ county	varchar(45)
			$statewide_vc = array( // Statewide vote count.
				'vbm'			=>0,
				'prov'			=>0,
				'oth'			=>0,
				'total'			=>0,
				'timestamp'		=>0,
				'timestamp_str'	=>0,
			);
			$f = '/var/www/html/mturk/.miscellaneous/ca-2016/unprocessed/' . $u->filename;
			$hfile = $f.'.html';
			if (!file_exists($hfile)) {
				shell_exec('/usr/bin/pdftohtml -noframes ' . $f);
			}
			// Scrape html doc
			$a = file_get_contents($hfile);
			// Get the update time
			// Variations on the update time.
			// E.g. can be "4:51p.m." or "4:51 p.m." etc.
			//<i>Updated:&#160;06/15/2016 4:51 p.m.</i><br/>
			//<i>Updated:&#160;06/15/2016 4:51p.m.</i><br/>
			//<i>Updated: 06/17/2016 5:30 p.m.</i>
			//<i>As&#160;of&#160;6/21/2016 4:55 p.m.</i><br/>
			//<i>As&#160;of&#160;7/1/2016 11:53 a.m.</i><br/>
			preg_match('/<i>Updated:(&#160;| *)(0*[678]\/\d+\/2016 \d+:\d+ *[ap]\.m\.)<\/i><br\/>/', $a, $b);
			if (!$b) {
				preg_match('/<i[^>]*>(As&#160;of&#160;|As of |Updated: )(0*[678]\/\d+\/2016 \d+:\d+ *[ap]\.m\.)<\/i>/', $a, $b);
				if (!$b) {
					$this->l( 'X[no update time!]:'."\n".
							$hfile."\n".
							$f."\n" );
					exit;
				}
			}
			$statewide_vc['timestamp_str'] = $b[2];
			$upt = strtotime($b[2] . ' PDT'); // This is based on GMT! So "4:55 pm" becomes "23:55:00 GMT". That is, 11:55 pm GMT, +7 hours.
			$statewide_vc['timestamp'] = $upt;
			if ($b[2] == '') {
				$this->l( 'X[time==""!]:'."\n".
						$hfile."\n".
						$f."\n" );
				exit;
			}
			// Does this update exist in the DB? Some of these
			// files are just duplicates! If duplicate and already
			// in DB then go ahead and continue.
			$q = 'SELECT * FROM FLBS_gen_tasks.ca_unprocessed_runs
				WHERE
					timestamp = "'.mysqli_real_escape_string($this->mysqli, $upt).'"';
			$query = $this->mysqli->query($q) or die(mysqli_error($this->mysqli));
			// Add a new run!
			if ($query->num_rows == 0) {
				$q = 'INSERT INTO FLBS_gen_tasks.ca_unprocessed_runs
					SET
						timestamp = "'.mysqli_real_escape_string($this->mysqli, $upt).'",
						timestamp_str = "'.mysqli_real_escape_string($this->mysqli, $b[2]).'"
					';
				$query = $this->mysqli->query($q) or die(mysqli_error($this->mysqli) . "\n" . $q);
				$id = $this->mysqli->insert_id;
				// Mail an alert. Looks like a new result...
				mail('_redacted_.com', 'ca_tally_unprocessed_dl new result...', $b[2]);
			} else {
				////////////////////////////
				// Stop here!
				// There is no need to continue if it is already
				// in the db.
				echo 'Timestamp exists! Exiting.'."\n";
				echo $b[2]."\n";
				exit;
			}
			// Replace all &#160; in file.
			$a = str_replace('&#160;', ' ', $a);
			// Try a preg match all
			// variations...
			// 6/27/2016 9*:20 a.m.
			//~ Alameda<br/>
//~ 0<br/>
//~ 0<br/>
//~ 0<br/>
//~ <b>0</b><br/>
 //~ 6/21/2016 10:54 a.m.<
			preg_match_all(
				'/\n([a-zA-Z ]+)<br\/>\n([\d, ]+)<br\/>\n([\d, ]+)<br\/>\n([\d, ]+)<br\/>\n<b>([\d, ]+)<\/b>(<br\/>\n|placehold|) *([0678])\/(\d+)\/2016 *(\d+[:;\*]+\d+ *[ap]\.*m\.*)/',//
				$a, $b
			);
			if (count($b[0]) == 0) { // Try type 2 (later download of Google cached)
				preg_match_all(
					'/<div[^>]+><nobr[^>]+>([a-zA-Z ]+)<\/nobr><\/div>\n<div[^>]+><nobr[^>]+>([\d,]+)<\/nobr><\/div>\n<div[^>]+><nobr[^>]+>([\d,]+)<\/nobr><\/div>[\r\n\t\s]+<div[^>]+><nobr[^>]+>([\d,]+)<\/nobr><\/div>\n<\/span><\/font>\n<font[^>]+><span[^>]+>\n<div[^>]+><nobr[^>]+><b[^>]+>([\d,]+)<\/b><\/nobr><\/div>\n<\/span><\/font>\n<font[^>]+><span[^>]+>\n<div[^>]+><nobr[^>]+>(placehold|)([0678])\/(\d+)\/2016 (\d+[:;\*]+\d+ [ap]\.m\.)<\/nobr><\/div>/',#
					$a, $b
				);
				if (count($b[0]) == 0) { // Try type 3 (later download of Google cached)
					preg_match_all(
						'/<div[^>]+><nobr[^>]+>([a-zA-Z ]+)<\/nobr><\/div>\n<div[^>]+><nobr[^>]+>([\d,]+)<\/nobr><\/div>\n<div[^>]+><nobr[^>]+>([\d,]+)<\/nobr><\/div>[\r\n\t\s]+<div[^>]+><nobr[^>]+>([\d,]+)<\/nobr><\/div>\n<div[^>]+><nobr[^>]+><b[^>]+>([\d,]+)<\/b><\/nobr><\/div>\n<div[^>]+><nobr[^>]+>(placehold|)([0678])\/(\d+)\/2016 (\d+[:;\*]+\d+ [ap]\.m\.)<\/nobr><\/div>/',#
						$a, $b
					);
					// 
					if (count($b[0]) == 0) { //
						$this->l( 'count($b[0])!'."\n".
								count($b[0])."\n" );
						exit;
					}
				}
			}
			// 58 counties in CA. Skip this check very FIRST
			// unprocessed ballots pdf. This is because first
			// was before all counties had submitted their first
			// numbers.
			if (count($b[0]) != 58 && $u->filename != 'unprocessed 6-9 5pm.html') {
				// Remove all of the CORRECT html! Haha!
				for ($i=0; $i<count($b[0]); $i++) {
					$a = str_replace($b[0][$i],'',$a);
				}
				print_r($a);
				$this->l( 'count($b[0])!'."\n".
					count($b[0])."\n".
					$a.
					"n"
				);
				exit;
			} else {
				//
			}
			// Now go through each row and update the corresponding
			// values in the database.
			// 6=month
			// 7=day
			// 8=time
			for ($i=0; $i<count($b[0]); $i++) {
				// Vars...
				$county = $b[1][$i];
				$b[9][$i] = str_replace(';', ':', $b[9][$i]); // In one place the time had a semi-colon! e.g. "8;04 a.m."!
				$b[9][$i] = str_replace('*', '', $b[9][$i]);  // In one place the time had a * ! e.g. "9*:04 a.m."
				$ts_str = $b[7][$i] . '/'.$b[8][$i].'/2016 '.$b[9][$i];
				$upt = strtotime($ts_str . ' PDT');
				// Add to statewide vote counts
				$statewide_vc['vbm'] +=   (int) str_replace(',','',$b[2][$i]);
				$statewide_vc['prov'] +=  (int) str_replace(',','',$b[3][$i]);
				$statewide_vc['oth'] +=   (int) str_replace(',','',$b[4][$i]);
				$statewide_vc['total'] += (int) str_replace(',','',$b[5][$i]);
				// If there is no run_id then update June 7?
				// Update county for this run
				$q = 'SELECT * FROM FLBS_gen_tasks.ca_unprocessed_counties
					WHERE 
						county = "'.mysqli_real_escape_string($this->mysqli, $county).'" &&
						timestamp = "'.mysqli_real_escape_string($this->mysqli, $upt).'" &&
						timestamp_str = "'.mysqli_real_escape_string($this->mysqli, $ts_str).'"
					';
				$query = $this->mysqli->query($q) or die(mysqli_error($this->mysqli));
				// This timestamp already in db! Continue.
				if ($query->num_rows != 0) {
					continue;
				}
				// Still here. Add to db.
				$q = 'INSERT INTO FLBS_gen_tasks.ca_unprocessed_counties
					SET
						vbm = "'.mysqli_real_escape_string($this->mysqli, str_replace(',','',$b[2][$i])).'",
						prov = "'.mysqli_real_escape_string($this->mysqli, str_replace(',','',$b[3][$i])).'",
						oth = "'.mysqli_real_escape_string($this->mysqli, str_replace(',','',$b[4][$i])).'",
						total = "'.mysqli_real_escape_string($this->mysqli, str_replace(',','',$b[5][$i])).'",
						county = "'.mysqli_real_escape_string($this->mysqli, $county).'",
						timestamp = "'.mysqli_real_escape_string($this->mysqli, $upt).'",
						timestamp_str = "'.mysqli_real_escape_string($this->mysqli, $ts_str).'"
					';
				$query = $this->mysqli->query($q) or die(mysqli_error($this->mysqli));
			}
			// Insert a statewide result...
			$q = 'INSERT INTO FLBS_gen_tasks.ca_unprocessed_counties
				SET
					vbm = "'.mysqli_real_escape_string($this->mysqli, str_replace(',','',$statewide_vc['vbm'])).'",
					prov = "'.mysqli_real_escape_string($this->mysqli, str_replace(',','',$statewide_vc['prov'])).'",
					oth = "'.mysqli_real_escape_string($this->mysqli, str_replace(',','',$statewide_vc['oth'])).'",
					total = "'.mysqli_real_escape_string($this->mysqli, str_replace(',','',$statewide_vc['total'])).'",
					county = "Statewide",
					timestamp = "'.mysqli_real_escape_string($this->mysqli, $statewide_vc['timestamp']).'",
					timestamp_str = "'.mysqli_real_escape_string($this->mysqli, $statewide_vc['timestamp_str']).'"
				';
			$query = $this->mysqli->query($q) or die(mysqli_error($this->mysqli));

			// Commit to db
			if (!$this->mysqli->commit()) {
				echo 'Transaction commit failed';
				exit;
			}
		}
	}
	new ca_tally_unprocessed_dl();
	exit;
}
/**
 * ca_tally
 * See http://vote.sos.ca.gov/returns/maps/president/party/democratic/
 * 
 */
if ($argv[1] == 'ca_tally_fix2') {
	class ca_tally_fix2 {
		private $mysqli;
		private $stores_arr;
		function __construct() {
			global $argv;
			$this->mysqli = connect__();
			$this->mysqli->autocommit(FALSE);
			$this->dl();
		}
		function dl() {
			$arr = [
				'unprocessed-ballots-report-1466014083.pdf','unprocessed-ballots-report-1466100723.pdf','unprocessed-ballots-report-1466219283.pdf','unprocessed-ballots-report-1466619122.pdf','unprocessed-ballots-report-1466022483.pdf','unprocessed-ballots-report-1466117763.pdf','unprocessed-ballots-report-1466230083.pdf','unprocessed-ballots-report-1466640738.pdf','unprocessed-ballots-report-1466024882.pdf','unprocessed-ballots-report-1466120402.pdf','unprocessed-ballots-report-1466284322.pdf','unprocessed-ballots-report-1466651523.pdf','unprocessed-ballots-report-1466028962.pdf','unprocessed-ballots-report-1466122323.pdf','unprocessed-ballots-report-1466438162.pdf','unprocessed-ballots-report-1466705524.pdf','unprocessed-ballots-report-1466031362.pdf','unprocessed-ballots-report-1466133123.pdf','unprocessed-ballots-report-1466446323.pdf','unprocessed-ballots-report-1466716323.pdf','unprocessed-ballots-report-1466034242.pdf','unprocessed-ballots-report-1466176322.pdf','unprocessed-ballots-report-1466456163.pdf','unprocessed-ballots-report-1466727122.pdf','unprocessed-ballots-report-1466035682.pdf','unprocessed-ballots-report-1466186883.pdf','unprocessed-ballots-report-1466467923.pdf','unprocessed-ballots-report-1466737923.pdf','unprocessed-ballots-report-1466046483.pdf','unprocessed-ballots-report-1466197683.pdf','unprocessed-ballots-report-1466478723.pdf','unprocessed-ballots-report-1466792163.pdf','unprocessed-ballots-report-1466050315.pdf','unprocessed-ballots-report-1466202962.pdf','unprocessed-ballots-report-1466532482.pdf','unprocessed-ballots-report-1466802963.pdf','unprocessed-ballots-report-1466050495.pdf','unprocessed-ballots-report-1466206323.pdf','unprocessed-ballots-report-1466550003.pdf','unprocessed-ballots-report-1466813763.pdf','unprocessed-ballots-report-1466057522.pdf','unprocessed-ballots-report-1466207763.pdf','unprocessed-ballots-report-1466554083.pdf','unprocessed-ballots-report-1466824563.pdf','unprocessed-ballots-report-1466095443.pdf','unprocessed-ballots-report-1466208242.pdf','unprocessed-ballots-report-1466564883.pdf','unprocessed 6-13 5pm (modified).html','unprocessed 6-9 5pm.html',
			];
			// Do pdf to html
			// Returns e.g. this:
			/*Alameda<br/>
22,279<br/>
42,000<br/>
0<br/>
<b>64,279</b><br/>
6/13/2016 5:20 p.m.<br/>*/
//=====ca_unprocessed_runs
//~ id	int(11) AI PK
//~ timestamp	datetime
//~ timestamp_str	varchar(45)
//~====ca_unprocessed_counties
//~ id	int(11) AI PK
//~ vbm	int(11)
//~ prov	int(11)
//~ oth	int(11)
//~ total	int(11)
//~ ca_unprocessed_runs_id	int(11)
//~ county	varchar(45)
			foreach ($arr as $key=>$value) {
				$statewide_vc = array( // Statewide vote count.
					'vbm'=>0,
					'prov'=>0,
					'oth'=>0,
					'total'=>0,
					'timestamp'=>0,
					'timestamp_str'=>0,
				);
				$f = '/var/www/html/mturk/.miscellaneous/' . $value;
				$hfile = str_replace('.pdf', '.html', $f);
				if (!file_exists($hfile)) {
					shell_exec('/usr/bin/pdftohtml -noframes ' . $f);
				}
				// Now scrape html doc
				$a = file_get_contents($hfile);
				// Get the update time
				preg_match('/<i>Updated:(&#160;| *)(0*6\/\d+\/2016 \d+:\d+ *[ap]\.m\.)<\/i><br\/>/', $a, $b);
				// Variations on the update time.
				// E.g. can be "4:51p.m." or "4:51 p.m." etc.
				//<i>Updated:&#160;06/15/2016 4:51 p.m.</i><br/>
				//<i>Updated:&#160;06/15/2016 4:51p.m.</i><br/>
				//<i>Updated: 06/17/2016 5:30 p.m.</i>
				//<i>As&#160;of&#160;6/21/2016 4:55 p.m.</i><br/>
				if (!$b) {
					preg_match('/<i[^>]*>(As&#160;of&#160;|As of |Updated: )(0*6\/\d+\/2016 \d+:\d+ *[ap]\.m\.)<\/i>/', $a, $b);
					if (!$b) {
						echo 'X[no update time!]:'."\n";
						echo $hfile."\n";
						echo $f."\n";
						exit;
					}
				}
				$statewide_vc['timestamp_str'] = $b[2];
				$upt = strtotime($b[2] . ' PDT'); // This is based on GMT! So "4:55 pm" becomes "23:55:00 GMT". That is, 11:55 pm GMT, +7 hours.
				$statewide_vc['timestamp'] = $upt;
				if ($b[2] == '') {
					echo 'X[time==""!]:'."\n";
					echo $hfile."\n";
					echo $f."\n";
					exit;
				}
				// Does this update exist in the DB? Some of these
				// files are just duplicates! If duplicate and already
				// in DB then go ahead and continue.
				$q = 'SELECT * FROM FLBS_gen_tasks.ca_unprocessed_runs WHERE timestamp = "'.mysqli_real_escape_string($this->mysqli, $upt).'"';
				$query = $this->mysqli->query($q) or die(mysqli_error($this->mysqli));
				// Add a new run!
				if ($query->num_rows == 0) {
					$q = 'INSERT INTO FLBS_gen_tasks.ca_unprocessed_runs
						SET
							timestamp = "'.mysqli_real_escape_string($this->mysqli, $upt).'",
							timestamp_str = "'.mysqli_real_escape_string($this->mysqli, $b[2]).'"
						';
					$query = $this->mysqli->query($q) or die(mysqli_error($this->mysqli) . "\n" . $q);
					$id = $this->mysqli->insert_id;
				} else {
					////////////////////////////
					// Stop here!
					// There is no need to continue if it is already
					// in the db.
					continue;
					////////////////////////////
					// Provides $x['id']
					$x = mysqli_fetch_array($query);
					$id = $x['id'];
					
				}
				// Replace all &#160; in file.
				$a = str_replace('&#160;', ' ', $a);
				// Try a preg match all
				preg_match_all(
					'/\n([a-zA-Z ]+)<br\/>\n([\d, ]+)<br\/>\n([\d, ]+)<br\/>\n([\d, ]+)<br\/>\n<b>([\d, ]+)<\/b>(<br\/>\n|placehold|)([067])\/(\d+)\/2016 *(\d+[:;]\d+ *[ap]\.*m\.*)/',//
					$a, $b
				);
				if (count($b[0]) == 0) { // Try type 2 (later download of Google cached)
					preg_match_all(
						'/<div[^>]+><nobr[^>]+>([a-zA-Z ]+)<\/nobr><\/div>\n<div[^>]+><nobr[^>]+>([\d,]+)<\/nobr><\/div>\n<div[^>]+><nobr[^>]+>([\d,]+)<\/nobr><\/div>[\r\n\t\s]+<div[^>]+><nobr[^>]+>([\d,]+)<\/nobr><\/div>\n<\/span><\/font>\n<font[^>]+><span[^>]+>\n<div[^>]+><nobr[^>]+><b[^>]+>([\d,]+)<\/b><\/nobr><\/div>\n<\/span><\/font>\n<font[^>]+><span[^>]+>\n<div[^>]+><nobr[^>]+>(placehold|)([067])\/(\d+)\/2016 (\d+:\d+ [ap]\.m\.)<\/nobr><\/div>/',#
						$a, $b
					);
					if (count($b[0]) == 0) { // Try type 3 (later download of Google cached)
						preg_match_all(
							'/<div[^>]+><nobr[^>]+>([a-zA-Z ]+)<\/nobr><\/div>\n<div[^>]+><nobr[^>]+>([\d,]+)<\/nobr><\/div>\n<div[^>]+><nobr[^>]+>([\d,]+)<\/nobr><\/div>[\r\n\t\s]+<div[^>]+><nobr[^>]+>([\d,]+)<\/nobr><\/div>\n<div[^>]+><nobr[^>]+><b[^>]+>([\d,]+)<\/b><\/nobr><\/div>\n<div[^>]+><nobr[^>]+>(placehold|)([067])\/(\d+)\/2016 (\d+:\d+ [ap]\.m\.)<\/nobr><\/div>/',#
							$a, $b
						);
						// 
						if (count($b[0]) == 0) { //
							echo 'count($b[0])!'."\n";
							echo count($b[0])."\n";
							print_r($a);
							exit;
						}
					}
				}
				// 58 counties in CA. Skip this check very FIRST
				// unprocessed ballots pdf. This is because first
				// was before all counties had submitted their first
				// numbers.
				if (count($b[0]) != 58 && $value != 'unprocessed 6-9 5pm.html') {
					echo 'count($b[0])!'."\n";
					echo count($b[0])."\n";
					// Remove all of the CORRECT html! Haha!
					for ($i=0; $i<count($b[0]); $i++) {
						$a = str_replace($b[0][$i],'',$a);
					}
					print_r($a);
					exit;
				} else {
					//
				}
				// Now go through each row and update the corresponding
				// values in the database.
				// 6=month
				// 7=day
				// 8=time
				for ($i=0; $i<count($b[0]); $i++) {
					// Vars...
					$county = $b[1][$i];
					//~ // Fix mismatching statewide string...
					//~ if ($county == 'STATEWIDE') $county = 'Statewide';
					$b[8][$i] = str_replace(';', ':', $b[8][$i]); // In one place the time had a semi-colon! e.g. "8;04 a.m."!
					$ts_str = $b[7][$i] . '/'.$b[8][$i].'/2016 '.$b[9][$i];
					$upt = strtotime($ts_str . ' PDT');
					// Add to statewide vote counts
					$statewide_vc['vbm'] +=   (int) str_replace(',','',$b[2][$i]);
					$statewide_vc['prov'] +=  (int) str_replace(',','',$b[3][$i]);
					$statewide_vc['oth'] +=   (int) str_replace(',','',$b[4][$i]);
					$statewide_vc['total'] += (int) str_replace(',','',$b[5][$i]);
					
					//~ // Select all runs that match this timestamp.
					//~ // If there is not an exact match...?
					//~ // Either select closest or select first older than.
					//~ $q = 'SELECT * FROM FLBS_gen_tasks.ca_runs WHERE timestamp = "June '.$b[6][$i].', 2016, '.$b[7][$i].'"';echo $q;exit;
					//~ $query = $this->mysqli->query($q) or die(mysqli_error($this->mysqli));
					//~ $x = mysqli_fetch_array($query);
					//~ // $x['id'];
					//~ $x_run_id = $x['id'];
					// If there is no run_id then update June 7?
					// Update county for this run
					$q = 'SELECT * FROM FLBS_gen_tasks.ca_unprocessed_counties
						WHERE 
							county = "'.mysqli_real_escape_string($this->mysqli, $county).'" &&
							timestamp = "'.mysqli_real_escape_string($this->mysqli, $upt).'" &&
							timestamp_str = "'.mysqli_real_escape_string($this->mysqli, $ts_str).'"
						';
					$query = $this->mysqli->query($q) or die(mysqli_error($this->mysqli));
					// This timestamp already in db! Continue.
					if ($query->num_rows != 0) {
						continue;
					}
					// Still here. Add to db.
					$q = 'INSERT INTO FLBS_gen_tasks.ca_unprocessed_counties
						SET
							vbm = "'.mysqli_real_escape_string($this->mysqli, str_replace(',','',$b[2][$i])).'",
							prov = "'.mysqli_real_escape_string($this->mysqli, str_replace(',','',$b[3][$i])).'",
							oth = "'.mysqli_real_escape_string($this->mysqli, str_replace(',','',$b[4][$i])).'",
							total = "'.mysqli_real_escape_string($this->mysqli, str_replace(',','',$b[5][$i])).'",
							county = "'.mysqli_real_escape_string($this->mysqli, $county).'",
							timestamp = "'.mysqli_real_escape_string($this->mysqli, $upt).'",
							timestamp_str = "'.mysqli_real_escape_string($this->mysqli, $ts_str).'"
						';
					// No longer this:
					// 		ca_unprocessed_runs_id = "'.mysqli_real_escape_string($this->mysqli, $id).'",
					$query = $this->mysqli->query($q) or die(mysqli_error($this->mysqli));
				}
				// Insert a statewide result...
				$q = 'INSERT INTO FLBS_gen_tasks.ca_unprocessed_counties
					SET
						vbm = "'.mysqli_real_escape_string($this->mysqli, str_replace(',','',$statewide_vc['vbm'])).'",
						prov = "'.mysqli_real_escape_string($this->mysqli, str_replace(',','',$statewide_vc['prov'])).'",
						oth = "'.mysqli_real_escape_string($this->mysqli, str_replace(',','',$statewide_vc['oth'])).'",
						total = "'.mysqli_real_escape_string($this->mysqli, str_replace(',','',$statewide_vc['total'])).'",
						county = "Statewide",
						timestamp = "'.mysqli_real_escape_string($this->mysqli, $statewide_vc['timestamp']).'",
						timestamp_str = "'.mysqli_real_escape_string($this->mysqli, $statewide_vc['timestamp_str']).'"
					';
				$query = $this->mysqli->query($q) or die(mysqli_error($this->mysqli));
			}
			// Commit to db
			if (!$this->mysqli->commit()) {
				echo 'Transaction commit failed';
				exit;
			}
		}
	}
	new ca_tally_fix2();
	exit;
}
// Fix the republican # where J. Kasich is messed up...
// ...
/**
 * ca_tally
 * See http://vote.sos.ca.gov/returns/maps/president/party/democratic/
 * 
 */
if ($argv[1] == 'ca_tally_fix') {
	class ca_tally_fix {
		private $mysqli;
		private $stores_arr;
		function __construct() {
			global $argv;
			$this->mysqli = connect__();
			$this->mysqli->autocommit(FALSE);
			
			// Build snapshots of the given time stamp.
			// For each county in the json file, determine if its
			// result has changed since the last update.
			$this->dl();
		}
		function dl() {
			$arr = [
				"/var/www/html/mturk/.miscellaneous/ca-2016-republican-1466041681",
				"/var/www/html/mturk/.miscellaneous/ca-2016-republican-1466050314",
				"/var/www/html/mturk/.miscellaneous/ca-2016-republican-1466050441",
				"/var/www/html/mturk/.miscellaneous/ca-2016-republican-1466050494",
				"/var/www/html/mturk/.miscellaneous/ca-2016-republican-1466057522",
				"/var/www/html/mturk/.miscellaneous/ca-2016-republican-1466095442",
				"/var/www/html/mturk/.miscellaneous/ca-2016-republican-1466100722",
				"/var/www/html/mturk/.miscellaneous/ca-2016-republican-1466117762",
				"/var/www/html/mturk/.miscellaneous/ca-2016-republican-1466120402",
				"/var/www/html/mturk/.miscellaneous/ca-2016-republican-1466122322",
				"/var/www/html/mturk/.miscellaneous/ca-2016-republican-1466133122",
				"/var/www/html/mturk/.miscellaneous/ca-2016-republican-1466176322",
				"/var/www/html/mturk/.miscellaneous/ca-2016-republican-1466186882",
			];
			foreach ($arr as $k=>$v) {
				$html = file_get_contents($v);
				// Preg match all, counties (REPUB)
				preg_match_all('/document\.getElementById\(\'mapTableDisplay\'\)\.innerHTML = \'(.+?)\'/', $html, $m);
				if (count($m[1]) != 59) {
					echo 'Bad state count:' . count($m[1]);
					exit;
				}
				// Add Repub to a global...
				$cands_rb = array();
				// Loop counties
				foreach ($m[1] as $k2=>$v2) {
					// Parse each as HTML
					$d = new DOMDocument();
					$d->loadHTML($v2);
					$div = $d->getElementsByTagName('div');
					$tr = $d->getElementsByTagName('tr');
					$county = $div[0]->nodeValue;
					$cands = array(
						'bc'=>'', // Ben carson, rather than "be" for "Ben"
						'te'=>'',
						'ji'=>'',
						'jo'=>'',
						'do'=>''
					);
					foreach ($tr as $b) {
						$td = $b->getElementsByTagName('td'); // Second td
						$votes = $td[2]->nodeValue;
						$cand = strtolower(substr($td[1]->nodeValue, 0, 2)); // First 2 chars
						if ($cand == 'be') $cand = 'bc'; // Ben Carson cannot be "be" because that is Sanders...
						$cands[ $cand ] = trim(str_replace(',', '', $votes));
					}
					// Update database! County-by-county.
					// Use the D.Trump column to match the data
					// to original.
					$q = '
						UPDATE FLBS_gen_tasks.ca_tally
						SET
							jo = "'.mysqli_real_escape_string($this->mysqli, $cands['jo']).'"
						WHERE
							county = "'.mysqli_real_escape_string($this->mysqli, $county).'" &&
							do = "'.mysqli_real_escape_string($this->mysqli, $cands['do']).'"
						';
					$query = $this->mysqli->query($q) or die(mysqli_error($this->mysqli));
				}
				// Commit to db
				if (!$this->mysqli->commit()) {
					echo 'Transaction commit failed';
					exit;
				}
			}
		}
	}
	new ca_tally_fix();
	exit;
}
/**
 * Tucker_Severson
 * 
 * 
 */
if ($argv[1] == 'Tucker_Severson') {
	class Tucker_Severson {
		private $mysqli;
		private $stores_arr;
		function __construct() {
			global $argv;
			$this->mysqli = connect__();
			$this->mysqli->autocommit(FALSE);
			$this->stores_arr =  array_map('str_getcsv', file('/home/_redacted_/share/SUB-EST2015_ALL.csv'));
			$this->stores_arr2 = array_map('str_getcsv', file('/home/_redacted_/share/fips_codes_website.csv'));
			//SUMLEV,STATE,COUNTY,PLACE,COUSUB,CONCIT,PRIMGEO_FLAG,FUNCSTAT,NAME,STNAME,CENSUS2010POP,ESTIMATESBASE2010,POPESTIMATE2010,POPESTIMATE2011,POPESTIMATE2012,POPESTIMATE2013,POPESTIMATE2014,POPESTIMATE2015

			//echo count($this->stores_arr);exit; // 7794
			//~ print_r($this->stores_arr[0]);exit; // 7794
			$this->dl();
		}
		function dl() {
			global $argv;
			foreach($this->stores_arr as $k => $v) { // 10 = pop
				if ($v[0] == 'SUMLEV') continue;
				echo $v[0]."|";
				$q = '
					UPDATE AMT_Requester.fips
					SET
						pop = "'.mysqli_real_escape_string($this->mysqli, $v[10]).'"
					WHERE
						state_fips = "'.mysqli_real_escape_string($this->mysqli, $v[1]).'" &&
						county_fips= "'.mysqli_real_escape_string($this->mysqli, $v[2]).'" &&
						entity_fips= "'.mysqli_real_escape_string($this->mysqli, $v[3]).'"
					';
				$query = $this->mysqli->query($q) or die(mysqli_error($this->mysqli));
			}
			// Commit to db
			if (!$this->mysqli->commit()) {
				print('Transaction commit failed');
				exit;
			}
		}
	}
	new Tucker_Severson();
	exit;
}
/**
 * WeFi_DD
 * 
 * 
 */
if ($argv[1] == 'WeFi_DD') {
	class WeFi_DD {
		private $mysqli;
		private $stores_arr;
		function __construct() {
			global $argv;
			$this->mysqli = connect__();
			$this->mysqli->autocommit(FALSE);
			$this->stores_arr = array_map('str_getcsv', file('/var/www/html/mturk/.deleteme/dunkindonuts-im.csv'));
			//echo count($this->stores_arr);exit; // 7794
			//~ print_r($this->stores_arr[0]);exit; // 7794
			$this->dl();
		}
		function dl() {
			global $argv;
			
			foreach($this->stores_arr as $k => $v) {
				$q = '
					UPDATE FLBS__Wefi_Geocode.stores10
					SET
						FLBS_site_scrape_lat_lon=1,
						FLBS_site_scrape_lat="'.mysqli_real_escape_string($this->mysqli, $v[1]).'",
						FLBS_site_scrape_lon="'.mysqli_real_escape_string($this->mysqli, $v[2]).'"
					WHERE dba="Dunkin Donuts" and store_number="'.mysqli_real_escape_string($this->mysqli, $v[0]).'"
					';
				$query = $this->mysqli->query($q) or die(mysqli_error($this->mysqli));
			}
			// Commit to db
			if (!$this->mysqli->commit()) {
				print('Transaction commit failed');
				exit;
			}
		}
	}
	new WeFi_DD();
	exit;
}
/**
 * WeFi_PA
 * 
 * 
 */
if ($argv[1] == 'WeFi_PA') {
	class WeFi_PA {
		private $mysqli;
		private $stores_arr;
		function __construct() {
			global $argv;
			$this->mysqli = connect__();
			$this->mysqli->autocommit(FALSE);
			$this->dl();
		}
		function recurse_get($row) {
			if ($this->stores_arr[ $row['store_number'] ] === true) {
				echo '.';
				return; // Skips
			} else {
				echo '>'; // Getting it.....
			}
			// Download
			$u = new stdClass;
			$u->request_to_file = true;
			$u->request_from_file = true;
			$u->filename = 'pa-jan24-2016-'.$row['id'];
			$u->folder = '/var/www/html/mturk/.deleteme/';
			$u->url = 'https://www.panerabread.com/pbdyn/panerabread/searchcafe?address='.
				urlencode(str_replace('\'', '', $row['address'])).','. // Remove apostrophe as this apparently breaks their search!
				urlencode(str_replace('\'', '', $row['city'])).','.
				urlencode(str_replace('\'', '', $row['state'])).''.
				'&limit=100';
			if (file_exists($u->folder . $u->filename)) {
				echo '-';
			}
			$rslt = curl_get($u);
			if ($rslt->status != 200) {
				echo 'error status='.$rslt->status;
				print_r($u);
				print_r($rslt);
				exit;
			}
			if (trim($rslt->html) == '') {
				echo '(Failed:Empty file!)';
				exit;
			}
			$r = json_decode($rslt->html);
			if (!$r || !$r->features || count($r->features) == 0) {
				echo '(Failed:Features!)';
				return; // Skip here..........
				//~ print_r($r);
				//~ exit;
			}
			$r = $r->features; // Array
			$a = 0;
			foreach ($r as $k => $v) {
				if (!$this->stores_arr[ $v->cafeID ]) {
					$this->stores_arr[ $v->cafeID ] = true;
					$q = '
						UPDATE FLBS__Wefi_Geocode.stores7
						SET
							FLBS_site_scrape_lat_lon=1,
							FLBS_site_scrape_lat="'.mysqli_real_escape_string($this->mysqli, $v->lat).'",
							FLBS_site_scrape_lon="'.mysqli_real_escape_string($this->mysqli, $v->lng).'"
						WHERE dba="Panera Bread" and store_number="'.mysqli_real_escape_string($this->mysqli, $v->cafeID).'"
						';
					$query = $this->mysqli->query($q) or die(mysqli_error($this->mysqli));
					$a++;
					//~ echo "(W:".$v->cafeID.",".$v->lat.",".$v->lng.")";
				}
			}
			//~ echo '(w='.$a.')'."\n";exit;
		}
		function dl() {
			global $argv;
			if (isset($argv[2]) && $argv[2] == 'd') {
				$q = '
				SELECT * FROM FLBS__Wefi_Geocode.stores7 where dba="Panera Bread" order by id desc';
			} else {
				$q = '
				SELECT * FROM FLBS__Wefi_Geocode.stores7 where dba="Panera Bread" order by id asc';
			}
			$query = $this->mysqli->query($q) or die(mysqli_error());
			if (mysqli_num_rows($query) == 0) {
				echo 'No more rows!';
				exit;
			}
			# Make an associative array
			$this->stores_arr = array();
			while ($row = $query->fetch_assoc()) {
				$this->stores_arr[ $row['store_number'] ] = false; # Will set true when complete
			}
			# New
			$a = 0;
			$query = $this->mysqli->query($q) or die(mysqli_error());
			while ($row = $query->fetch_assoc()) {
				echo "\n" . $a . " of " . mysqli_num_rows($query); $a++;
				$this->recurse_get($row);
			}
			//~ exit;
			// Commit to db
			if (!$this->mysqli->commit()) {
				print('Transaction commit failed');
				exit;
			}
		}
	}
	new WeFi_PA();
	exit;
}
/**
 * WeFi_RA
 * 
 * 
 */
if ($argv[1] == 'WeFi_RA') {
	class WeFi_RA {
		private $mysqli;
		private $stores_arr;
		function __construct() {//echo 'x';
			global $argv;
			$this->mysqli = connect__();
			$this->mysqli->autocommit(FALSE);
			$this->dl();
		}
		function recurse_get($row) {
			if ($this->stores_arr[ $row['store_number'] ] === true) {
				echo '.';
				return; // Skips
			} else {
				echo '>'; // Getting it.....
			}
			// Download
			$u = new stdClass;
			$u->request_to_file = true;
			$u->request_from_file = true;
			$u->filename = 'ra-jan23-2016-'.$row['id'];
			$u->folder = '/var/www/html/mturk/.deleteme/';
			$u->url = 'https://m.riteaid.com/SDRiteRewards/mapstore.aspx?st='.urlencode($row['store_number']);
			if (file_exists($u->folder . $u->filename)) {
				echo '-';
			}
			$rslt = curl_get($u);
			if ($rslt->status != 200) {
				echo 'error status='.$rslt->status;
				exit;
			}
			preg_match('/<input type="hidden" name="txtLat" id="txtLat" value="([^"]+)" \/>/', $rslt->html, $lat);
			preg_match('/<input type="hidden" name="txtLong" id="txtLong" value="([^"]+)" \/>/', $rslt->html, $lng);
			if (!$lat || !$lng) { // || $r->response->longitude == null
				//~ // Not second try?
				//~ if (!$second_try) {
					//~ echo 'second'."\n";
					//~ $this->recurse_get($row, true);
					//~ return;
				//~ }
				echo 'Failed:';
				exit;
			} else {
				echo 'Success:'.$row['store_number'].','.$lat[1].','.$lng[1];
				//~ print_r($lat);return;
			}
			$q = '
				UPDATE FLBS__Wefi_Geocode.stores7
				SET
					FLBS_site_scrape_lat_lon=1,
					FLBS_site_scrape_lat="'.mysqli_real_escape_string($this->mysqli, $lat[1]).'",
					FLBS_site_scrape_lon="'.mysqli_real_escape_string($this->mysqli, $lng[1]).'"
				WHERE id="'.mysqli_real_escape_string($this->mysqli, $row['id']).'"
				LIMIT 1';
			$query = $this->mysqli->query($q) or die(mysqli_error($this->mysqli));
		}
		function dl() {
			global $argv;
			if (isset($argv[2]) && $argv[2] == 'd') {
				$q = '
				SELECT * FROM FLBS__Wefi_Geocode.stores7 where dba="Rite Aid" order by id desc';
			} else {
				$q = '
				SELECT * FROM FLBS__Wefi_Geocode.stores7 where dba="Rite Aid" order by id asc';
			}
			$query = $this->mysqli->query($q) or die(mysqli_error());
			if (mysqli_num_rows($query) == 0) {
				echo 'No more rows!';
				exit;
			}
			# Make an associative array
			$this->stores_arr = array();
			while ($row = $query->fetch_assoc()) {
				$this->stores_arr[ $row['store_number'] ] = false; # Will set true when complete
			}
			# New
			$a = 0;
			$query = $this->mysqli->query($q) or die(mysqli_error());
			while ($row = $query->fetch_assoc()) {
				echo "\n" . $a . " of " . mysqli_num_rows($query); $a++;
				$this->recurse_get($row);
			}
			//~ exit;
			// Commit to db
			if (!$this->mysqli->commit()) {
				print('Transaction commit failed');
				exit;
			}
		}
	}
	new WeFi_RA();
	exit;
}
/**
 * WeFi_FG
 * Five guys
 * 
 */
if ($argv[1] == 'WeFi_FG') {
	class WeFi_FG {
		private $mysqli;
		private $stores_arr;
		function __construct() {//echo 'x';
			global $argv;
			$this->json = '[{"lat":32.60721969604492,"lng":-85.48176574707031,.......}]';
			$this->json = json_decode($this->json);
			$this->mysqli = connect__();
			$this->mysqli->autocommit(FALSE);
			$this->dl();
		}
		function dl() {
			foreach ($this->json as $k => $v) {
				$q = '
					UPDATE FLBS__Wefi_Geocode.stores7
					SET
						FLBS_site_scrape_lat_lon=1,
						FLBS_site_scrape_lat="'.mysqli_real_escape_string($this->mysqli, $v->lat).'",
						FLBS_site_scrape_lon="'.mysqli_real_escape_string($this->mysqli, $v->lng).'"
					WHERE dba="Five Guys Burgers and Fries" &&
					address="'.mysqli_real_escape_string($this->mysqli, $v->address).'" &&
					city="'.mysqli_real_escape_string($this->mysqli, $v->city).'" &&
					state="'.mysqli_real_escape_string($this->mysqli, $v->state).'"
					';
				$query = $this->mysqli->query($q) or die(mysqli_error($this->mysqli));
				$x = mysqli_affected_rows();
				if ($x != 1) {
					echo 'AR:'."$x\n";
				}
			}
			if (!$this->mysqli->commit()) {
				print('Transaction commit failed');
				exit;
			}
		}
	}
	new WeFi_FG();
	exit;
}
/**
 * WeFi_UB
 * 
 * 
 */
if ($argv[1] == 'WeFi_UB') {
	class WeFi_UB {
		private $mysqli;
		private $stores_arr;
		function __construct() {
			$this->mysqli = connect__();
			$this->mysqli->autocommit(FALSE);
			$this->recurse_get();
			$this->dl();
		}
		function recurse_get() {
			// Download
			$u = new stdClass;
			$u->request_to_file = true;
			$u->request_from_file = true;
			$u->filename = 'ub-jan24-2016-';
			$u->folder = '/var/www/html/mturk/.deleteme/';
			$u->url = 'http://careers.ulta.com/Sublayouts/Sites/Ulta/ui/location.xml';
			echo $u->url;
			$rslt = curl_get($u);
			if ($rslt->status != 200) {
				echo 'error status='.$rslt->status;
				exit;
			}
			$r = xml_to_object($rslt->html);
			if (!$r) {
				echo 'Failed1:';
				exit;
			}
			//~ print_r($r);exit;
			$v = $r->children[4]->children; // Array
			$a = 0;
			foreach ($v as $key => $value) {
				//~ print_r($value);
				$c = $value->children;
				$key = '';
				$lat = '0'; // Default = 0
				$lng = '0';
				$street = '';
				$state = '';
				$write = false;
				foreach ($c as $key2 => $value2) {
					if ($value2->name == 'location_name') {
						$key = trim($value2->content);
						if ($this->stores_arr[ $key ] == false) {
							$this->stores_arr[ $key ] = true;
							$write = true;
						}
						continue;
					}
					if ($value2->name == 'address') {
						$street = trim($value2->content);
						continue;
					}
					if ($value2->name == 'state') {
						$state = trim($value2->content);
						continue;
					}
					if ($value2->name == 'geocode') {
						$z = $value2->children;
						$lat = trim($z[0]->content);
						$lng = trim($z[1]->content);
					}
				}
				//~ echo "(W:$key,$lat,$lng)";
				// Write
				if ($write) {
					$q = '
						UPDATE FLBS__Wefi_Geocode.stores7
						SET
							FLBS_site_scrape_lat_lon=1,
							FLBS_site_scrape_lat="'.mysqli_real_escape_string($this->mysqli, $lat).'",
							FLBS_site_scrape_lon="'.mysqli_real_escape_string($this->mysqli, $lng).'"
						WHERE	store_name="'.mysqli_real_escape_string($this->mysqli, $key).'" ||
								(address="'.mysqli_real_escape_string($this->mysqli, $street).'" && state="'.mysqli_real_escape_string($this->mysqli, $state).'")
						AND dba = "Ulta Beauty"
						LIMIT 1';
					$query = $this->mysqli->query($q) or die(mysqli_error($this->mysqli));
					$a++;
				}
			}
			echo 'Wrote '.$a."\n";//exit;
			//~ exit;
			//~ print_r(xml_to_object($rslt->html));
			//~ $p = xml_parser_create();
			//~ xml_parse_into_struct($p, $rslt->html, $vals, $index);
			//~ print_r($vals);
			//~ print_r($index);exit;
			//~ xml_parser_free($p);
		}
		function dl() {
			// Commit to db
			if (!$this->mysqli->commit()) {
				print('Transaction commit failed');
				exit;
			} else {
				echo 'MySQLi commit successful!';
			}
		}
	}
	new WeFi_UB();
	exit;
}
/**
 * WeFi_CF
 * 
 * 
 */
if ($argv[1] == 'WeFi_CF') {
	class WeFi_CF {
		private $mysqli;
		private $stores_arr;
		function __construct() {//echo 'x';
			$this->mysqli = connect__();
			$this->mysqli->autocommit(FALSE);
			$this->dl();
		}
		function recurse_get($row) {
			if ($this->stores_arr[ $row['store_number'] ] === true) {
				echo '.';
				return; // Skips
			} else {
				echo '>'; // Getting it.....
			}
			// Download
			$u = new stdClass;
			$u->request_to_file = true;
			$u->request_from_file = true;
			$u->filename = 'cfa2-jan24-2016-'.$row['id'];
			$u->folder = '/var/www/html/mturk/.deleteme/';
			$u->url = 'https://hosted.where2getit.com/chickfila/newdesign/ajax?&xml_request=<request><appkey>_redacted_<%2Fappkey><formdata+id%3D"locatorsearch"><dataview>store_default<%2Fdataview><limit>1700<%2Flimit><geolocs><geoloc><addressline><%2Faddressline><latitude>'.
				urlencode($row['latitude']).
				'<%2Flatitude><longitude>'.
				urlencode($row['longitude']).'<%2Flongitude><country><%2Fcountry><%2Fgeoloc><%2Fgeolocs><searchradius>5000<%2Fsearchradius><where><offersonlineordering><eq><%2Feq><%2Foffersonlineordering><hasdrivethru><eq><%2Feq><%2Fhasdrivethru><offerswireless><eq><%2Feq><%2Fofferswireless><playground><in><%2Fin><%2Fplayground><clientkey><notin>_redacted_<%2Fnotin><%2Fclientkey><status><distinctfrom>future<%2Fdistinctfrom><%2Fstatus><%2Fwhere><%2Fformdata><%2Frequest>';
			echo $u->url;
			$rslt = curl_get($u);
			if ($rslt->status != 200) {
				echo 'error status='.$rslt->status;
				exit;
			}
			$r = xml_to_object($rslt->html);
			if (!$r) {
				echo 'Failed1:';
				exit;
			}
			$v = $r->children[0]->children;
			$a = 0;
			foreach ($v as $key => $value) {
				//~ print_r($value);
				$c = $value->children;
				$key = '';
				$lat = '0'; // Default = 0
				$lng = '0';
				$write = false;
				foreach ($c as $key2 => $value2) {
					if ($value2->name == 'clientkey') {
						$key = preg_replace('/^0+/', '', $value2->content); // Remove all leading zeroes
						if ($this->stores_arr[ $key ] == false) {
							$this->stores_arr[ $key ] = true;
							$write = true;
						}
						continue;
					}
					if ($value2->name == 'latitude') {
						$lat = $value2->content;
						continue;
					}
					if ($value2->name == 'longitude') {
						$lng = $value2->content;
						continue;
					}
				}
				// Write
				if ($write) {
					$q = '
						UPDATE FLBS__Wefi_Geocode.stores7
						SET
							FLBS_site_scrape_lat_lon=1,
							FLBS_site_scrape_lat="'.mysqli_real_escape_string($this->mysqli, $lat).'",
							FLBS_site_scrape_lon="'.mysqli_real_escape_string($this->mysqli, $lng).'"
						WHERE store_number="'.mysqli_real_escape_string($this->mysqli, $key).'"
						AND dba = "Chick-Fil-A"
						LIMIT 1';
					$query = $this->mysqli->query($q) or die(mysqli_error($this->mysqli));
					$a++;
				}
			}
			echo 'Wrote '.$a."\n";//exit;
			//~ exit;
			//~ print_r(xml_to_object($rslt->html));
			//~ $p = xml_parser_create();
			//~ xml_parse_into_struct($p, $rslt->html, $vals, $index);
			//~ print_r($vals);
			//~ print_r($index);exit;
			//~ xml_parser_free($p);
		}
		function dl() {
			$q = '
				SELECT * FROM FLBS__Wefi_Geocode.stores7 where dba="Chick-Fil-A"';
				//WHERE FLBS_site_scrape_lat_lon IS NULL && dba="Pizza Hut"';
			$query = $this->mysqli->query($q) or die(mysqli_error());
			if (mysqli_num_rows($query) == 0) {
				echo 'No more rows!';
				exit;
			}
			# Make an associative array
			$this->stores_arr = array();
			while ($row = $query->fetch_assoc()) {
				$this->stores_arr[ $row['store_number'] ] = false; # Will set true when complete
			}
			# New
			$a = 0;
			$query = $this->mysqli->query($q) or die(mysqli_error());
			while ($row = $query->fetch_assoc()) {
				echo $a."\n"; $a++;
				$this->recurse_get($row);
			}//exit;
			// Commit to db
			if (!$this->mysqli->commit()) {
				print('Transaction commit failed');
				exit;
			}
		}
	}
	new WeFi_CF();
	exit;
}
/**
 * WeFi_DD2
 * 
 * 
 */
if ($argv[1] == 'WeFi_DD2') {
	class WeFi_DD2 {
		private $mysqli;
		private $stores_arr;
		function __construct() {
			$this->mysqli = connect__();
			$this->mysqli->autocommit(FALSE);
			$this->dl();
		}
		function recurse_get($row) {
			if ($this->stores_arr[ $row['store_number'] ] === true) {
				echo '.';
				return; // Skips
			} else {
				echo '>'; // Getting it.....
			}
			// Download
			$tmp_addr = $row['address'].','.$row['city'].','.$row['state'].',United States';
			$u = new stdClass;
			$u->request_to_file = true;
			$u->request_from_file = true;
			$u->filename = 'dd-mar13-2016-'.$row['id'];
			$u->folder = '/var/www/html/mturk/.deleteme/';
			$u->url = 'https://www.mapquestapi.com/search/v2/radius?callback='.
				'&key=_redacted_&origin='.urlencode($tmp_addr).
				'&units=m&maxMatches=4000&ambiguities=ignore&radius=3000&hostedData=mqap.33454_DunkinDonuts&_=145789822196999';
			//echo $u->url;
			$rslt = curl_get($u);
			if ($rslt->status != 200) {
				echo 'error status='.$rslt->status;
				exit;
			}
			$r = json_decode($rslt->html);
			if (!$r || !$r->searchResults || count($r->searchResults) == 0) {
				//~ // Not second try?
				//~ if (!$second_try) {
					//~ echo 'second'."\n";
					//~ $this->recurse_get($row, true);
					//~ return;
				//~ }
				echo 'Failed1:';
				exit;
			} else {
				echo 'Success1:';
			}
			foreach ($r->searchResults as $k => $v) {
				$rid = $v->fields->recordid;
				$ll = $v->fields;
				if ($this->stores_arr[ $rid ] == false) {
					$this->stores_arr[ $rid ] = true; // Set
					echo $this->stores_arr[ $rid ];
					// Write
					$q = '
						UPDATE FLBS__Wefi_Geocode.stores10
						SET
							FLBS_site_scrape_lat_lon=1,
							FLBS_site_scrape_lat="'.mysqli_real_escape_string($this->mysqli, $ll->lat).'",
							FLBS_site_scrape_lon="'.mysqli_real_escape_string($this->mysqli, $ll->lng).'"
						WHERE store_number="'.mysqli_real_escape_string($this->mysqli, $rid).'"
						LIMIT 1';
					$query = $this->mysqli->query($q) or die(mysqli_error($this->mysqli));
					echo 'W('.$rid.')';//exit;
				} else if (array_key_exists($rid, $this->stores_arr)) { // Has store number but is already done!
					echo '-';
				} else {
					echo 'No store #!!:';
					print_r($v);
					exit;
				}
			}//exit;
		}
		function dl() {
			$q = '
				SELECT * FROM FLBS__Wefi_Geocode.stores10';
			$query = $this->mysqli->query($q) or die(mysqli_error());
			if (mysqli_num_rows($query) == 0) {
				echo 'No more rows!';
				exit;
			}
			# Make an associative array
			$this->stores_arr = array();
			while ($row = $query->fetch_assoc()) {
				if ($row['FLBS_site_scrape_lat_lon'] == '1') {
					$this->stores_arr[ $row['store_number'] ] = true;
				} else {
					$this->stores_arr[ $row['store_number'] ] = false; # Will set true when complete
				}
			}
			# New
			$a = 0;
			$query = $this->mysqli->query($q) or die(mysqli_error());
			while ($row = $query->fetch_assoc()) {
				echo $a."\n"; $a++;
				$this->recurse_get($row);
			}//exit;
			// Commit to db
			if (!$this->mysqli->commit()) {
				print('Transaction commit failed');
				exit;
			}
		}
	}
	new WeFi_DD2();
	exit;
}
/**
 * WeFi_MD
 * 
 * 
 */
if ($argv[1] == 'WeFi_MD') {
	class WeFi_MD {
		private $mysqli;
		private $stores_arr;
		function __construct() {//echo 'x';
			$this->mysqli = connect__();
			$this->mysqli->autocommit(FALSE);
			$this->dl();
		}
		function recurse_get($row) {
			if ($this->stores_arr[ $row['store_number'] ] === true) {
				echo '.';
				return; // Skips
			} else {
				echo '>'; // Getting it.....
			}
			// Download
			$u = new stdClass;
			$u->request_to_file = true;
			$u->request_from_file = true;
			$u->filename = 'mcd-jan5-2016-'.$row['id'];
			$u->folder = '/var/www/html/mturk/.deleteme/';
			$u->url = 'http://rl.mcdonalds.com/googleapps/GoogleSearchUSAction.do'.
					'?method=searchLocation'.
					'&searchTxtLatlng=('.urlencode($row['latitude']).'%2C%20'.urlencode($row['longitude']).')'.
					'&actionType=searchRestaurant&language=en&country=us';
			$rslt = curl_get($u);
			if ($rslt->status != 200) {
				echo 'error status='.$rslt->status;
				exit;
			}
			$r = json_decode($rslt->html);
			if (!$r || !$r->results || count($r->results) == 0) { // || $r->response->longitude == null
				//~ // Not second try?
				//~ if (!$second_try) {
					//~ echo 'second'."\n";
					//~ $this->recurse_get($row, true);
					//~ return;
				//~ }
				echo 'Failed1:';
				exit;
			} else {
				echo 'Success1:';
			}
			foreach ($r->results as $k => $v) {
				//~ echo '$v:('.$v->natlStrNumber.')';
				//~ $nsn = $v->natlStrNumber
				if ($this->stores_arr[ $v->natlStrNumber ] == false) {
					$this->stores_arr[ $v->natlStrNumber ] = true; // Set
					echo $this->stores_arr[ $v->natlStrNumber ];//exit;
					// Write
					$q = '
						UPDATE FLBS__Wefi_Geocode.stores6
						SET
							FLBS_site_scrape_lat_lon=1,
							FLBS_site_scrape_lat="'.mysqli_real_escape_string($this->mysqli, $v->latitude).'",
							FLBS_site_scrape_lon="'.mysqli_real_escape_string($this->mysqli, $v->longitude).'"
						WHERE store_number="'.mysqli_real_escape_string($this->mysqli, $v->natlStrNumber).'"
						LIMIT 1';
					$query = $this->mysqli->query($q) or die(mysqli_error($this->mysqli));
					echo 'W('.$v->natlStrNumber.')';//exit;
				} else if (array_key_exists($v->natlStrNumber, $this->stores_arr)) { // Has store number but is already done!
					echo '-';
				} else {
					echo 'No store #!!:';
					print_r($v);
					exit;
				}
			}//exit;
		}
		function dl() {
			$q = '
				SELECT * FROM FLBS__Wefi_Geocode.stores6 where dba="McDonald\'s"';
				//WHERE FLBS_site_scrape_lat_lon IS NULL && dba="Pizza Hut"';
			$query = $this->mysqli->query($q) or die(mysqli_error());
			if (mysqli_num_rows($query) == 0) {
				echo 'No more rows!';
				exit;
			}
			# Make an associative array
			$this->stores_arr = array();
			while ($row = $query->fetch_assoc()) {
				$this->stores_arr[ $row['store_number'] ] = false; # Will set true when complete
			}
			# New
			$a = 0;
			$query = $this->mysqli->query($q) or die(mysqli_error());
			while ($row = $query->fetch_assoc()) {
				echo $a."\n"; $a++;
				$this->recurse_get($row);
			}//exit;
			// Commit to db
			if (!$this->mysqli->commit()) {
				print('Transaction commit failed');
				exit;
			}
		}
	}
	new WeFi_MD();
	exit;
}
/**
 * WeFi_MV
 * music venue
 * 
 */
if ($argv[1] == 'WeFi_MV') {
	class WeFi_MV {
		private $mysqli;
		function __construct() {
			$this->mysqli = connect__();
			$this->mysqli->autocommit(FALSE);
			$this->dl();
		}
		function recurse_get($row) {
			// To write.... Defaults...
			$w = array(
				'adr'=>'',
				'city'=>'',
				'state'=>'',
				'lat'=>'0', # Default is 0,0
				'lng'=>'0'
			);
			echo $row['dba'] . "\n";
			// Places api, text search (broad)
			$r =	'https://maps.googleapis.com/maps/api/place/textsearch/json?'.
					'query='.urlencode($row['dba'] . ',' . $row['city']).
					//~ 'query='.urlencode(str_replace('+', ' ', $row['dba'])).
					'&key=_redacted_';
					//~ '&type='.urlencode(trim(str_replace(' ','_',$row['address_line_3']))); // Replace spaces with "_" (i.e. amusement_park)
			echo $r;
			// ...
			$u = new stdClass;
			$u->request_to_file = true;
			$u->request_from_file = true;
			$u->filename = 'WeFi_MV-feb-19-2016-gp--'.$row['id'];
			$u->folder = '/var/www/html/mturk/.deleteme/';
			$u->url = $r;
			$rslt = curl_get($u);
			if ($rslt->status != 200) {
				echo 'error status='.$rslt->status;
				exit;
			}
			$r = json_decode($rslt->html);
			if (!$r || !$r->results || count($r->results) == 0) { // || $r->response->longitude == null
				echo 'Failed2:';
			} else {
				echo 'Success2:';
				$r = $r->results[0];
				$x = explode(',', $r->formatted_address); // Removes ", city"
				$w['lat'] = $r->geometry->location->lat;
				$w['lng'] = $r->geometry->location->lng;
				$w['adr'] = $x[0];
				$w['city'] = trim($x[1]);
				$w['state'] = explode(' ',trim($x[2]))[0];
			}
			//~ var_dump($w);exit;
			// Write
			$q = '
				UPDATE FLBS__Wefi_Geocode.stores8
				SET
					address="'.mysqli_real_escape_string($this->mysqli, $w['adr']).'",
					state="'.mysqli_real_escape_string($this->mysqli, $w['state']).'",
					latitude="'.mysqli_real_escape_string($this->mysqli, $w['lat']).'",
					longitude="'.mysqli_real_escape_string($this->mysqli, $w['lng']).'"
				WHERE id="'.mysqli_real_escape_string($this->mysqli, $row['id']).'"
				LIMIT 1'; // Note: No city......
			$query = $this->mysqli->query($q) or die(mysqli_error($this->mysqli));
			echo 'Written...'."\n";//
		}
		function dl() {
			$this->mysqli->set_charset("utf8");
			$q = '
				SELECT * FROM FLBS__Wefi_Geocode.stores8 where FLBS_spreadsheet_id="music venues.csv"';
				//WHERE FLBS_site_scrape_lat_lon IS NULL && dba="Pizza Hut"';
			$query = $this->mysqli->query($q) or die(mysqli_error());
			if (mysqli_num_rows($query) == 0) {
				echo 'No more rows!';
				exit;
			}
			$a = 0;
			while ($row = $query->fetch_assoc()) {
				echo $a."\n"; $a++;
				$this->recurse_get($row);
			}
			// Commit to db
			if (!$this->mysqli->commit()) {
				print('Transaction commit failed');
				exit;
			}
		}
	}
	new WeFi_MV();
	exit;
}
/**
 * WeFi_AMZ
 * Amusement / Musuems / Zoos
 * 
 */
if ($argv[1] == 'WeFi_AMZ') {
	class WeFi_AMZ {
		private $mysqli;
		function __construct() {
			$this->mysqli = connect__();
			$this->mysqli->autocommit(FALSE);
			$this->dl();
		}
		function recurse_get($row) {
			// To write.... Defaults...
			$w = array(
				'adr'=>'',
				'city'=>'',
				'state'=>'',
				'lat'=>'0', # Default is 0,0
				'lng'=>'0'
			);
			echo $row['dba'] . "\n";
			echo $row['dba'] . "\n";
			// Places api, text search (broad)
			$r =	'https://maps.googleapis.com/maps/api/place/textsearch/json?'.
					'query='.urlencode(str_replace('+', ' ', $row['dba'])).
					'&key=_redacted_'.
					'&type='.urlencode(trim(str_replace(' ','_',$row['address_line_3']))); // Replace spaces with "_" (i.e. amusement_park)
			echo $r;
			// ...
			$u = new stdClass;
			$u->request_to_file = true;
			$u->request_from_file = true;
			$u->filename = 'WeFi_AMZ-feb-15-2016-gp--'.$row['id'];
			$u->folder = '/var/www/html/mturk/.deleteme/';
			$u->url = $r;
			$rslt = curl_get($u);
			if ($rslt->status != 200) {
				echo 'error status='.$rslt->status;
				exit;
			}
			$r = json_decode($rslt->html);
			if (!$r || !$r->results || count($r->results) == 0) { // || $r->response->longitude == null
				echo 'Failed2:';
			} else {
				echo 'Success2:';
				$r = $r->results[0];
				$x = explode(',', $r->formatted_address); // Removes ", city"
				$w['lat'] = $r->geometry->location->lat;
				$w['lng'] = $r->geometry->location->lng;
				$w['adr'] = $x[0];
				$w['city'] = trim($x[1]);
				$w['state'] = explode(' ',trim($x[2]))[0];
			}
			//~ var_dump($w);exit;
			// Write
			$q = '
				UPDATE FLBS__Wefi_Geocode.stores8
				SET
					address="'.mysqli_real_escape_string($this->mysqli, $w['adr']).'",
					city="'.mysqli_real_escape_string($this->mysqli, $w['city']).'",
					state="'.mysqli_real_escape_string($this->mysqli, $w['state']).'",
					latitude="'.mysqli_real_escape_string($this->mysqli, $w['lat']).'",
					longitude="'.mysqli_real_escape_string($this->mysqli, $w['lng']).'"
				WHERE id="'.mysqli_real_escape_string($this->mysqli, $row['id']).'"
				LIMIT 1';
			$query = $this->mysqli->query($q) or die(mysqli_error($this->mysqli));
			echo 'Written...'."\n";//exit;
		}
		function dl() {
			$this->mysqli->set_charset("utf8");
			$q = '
				SELECT * FROM FLBS__Wefi_Geocode.stores8 where FLBS_spreadsheet_id="AMZ.csv"';
				//WHERE FLBS_site_scrape_lat_lon IS NULL && dba="Pizza Hut"';
			$query = $this->mysqli->query($q) or die(mysqli_error());
			if (mysqli_num_rows($query) == 0) {
				echo 'No more rows!';
				exit;
			}
			$a = 0;
			while ($row = $query->fetch_assoc()) {
				echo $a."\n"; $a++;
				$this->recurse_get($row);
			}
			// Commit to db
			if (!$this->mysqli->commit()) {
				print('Transaction commit failed');
				exit;
			}
		}
	}
	new WeFi_AMZ();
	exit;
}
/**
 * WeFi_Casino
 * 
 * 
 */
if ($argv[1] == 'WeFi_Casino') {
	class WeFi_Casino {
		private $mysqli;
		function __construct() {
			$this->mysqli = connect__();
			$this->mysqli->autocommit(FALSE);
			$this->dl();
		}
		function recurse_get($row) {
			// To write....
			$w = array(
				'adr'=>'',
				'lat'=>'0', # Default is 0,0
				'lng'=>'0'
			);
			// Download
			$u = new stdClass;
			$u->request_to_file = true;
			$u->request_from_file = true;
			$u->filename = 'casinos-jan5-2016-cs-'.$row['id'];
			$u->folder = '/var/www/html/mturk/.deleteme/';
			$u->url = 'http://maps.googleapis.com/maps/api/geocode/json?address='.urlencode($row['city'] .','. $row['state'] .','. $row['country']).'&sensor=false';
			$rslt = curl_get($u);
			if ($rslt->status != 200) {
				echo 'error status='.$rslt->status;
				exit;
			}
			$r = json_decode($rslt->html);
			if (!$r || !$r->results || count($r->results) == 0) { // || $r->response->longitude == null
				//~ // Not second try?
				//~ if (!$second_try) {
					//~ echo 'second'."\n";
					//~ $this->recurse_get($row, true);
					//~ return;
				//~ }
				echo 'Failed1:';
				exit;
			} else {
				echo 'Success1:';
			}
			$a = $r->results[0]->geometry->location;
			// Update temporarily...
			$w['lat'] = $a->lat;
			$w['lng'] = $a->lng;
			// And now places api.
			$r = 'https://maps.googleapis.com/maps/api/place/nearbysearch/json'.
				'?location='.urlencode($a->lat).','.urlencode($a->lng).
				'&keyword=' . urlencode($row['dba']).
				#'&rankby='.
				'&radius=50000'.
				'&sensor=false'.
				'&key=_redacted_';
			$u = new stdClass;
			$u->request_to_file = true;
			$u->request_from_file = true;
			$u->filename = 'casinos-jan5-2016-gp-'.$row['id'];
			$u->folder = '/var/www/html/mturk/.deleteme/';
			$u->url = $r;
			$rslt = curl_get($u);
			if ($rslt->status != 200) {
				echo 'error status='.$rslt->status;
				exit;
			}
			$r = json_decode($rslt->html);//print_r($r);exit;
			if (!$r || !$r->results || count($r->results) == 0) { // || $r->response->longitude == null
				echo 'Failed2:';
			} else {
				echo 'Success2:';
				$r = $r->results[0];
				$adr = explode(',', $r->vicinity); // Removes ", city"
				$adr = $adr[0];
				$w['lat'] = $r->geometry->location->lat;
				$w['lng'] = $r->geometry->location->lng;
				$w['adr'] = $adr;
			}
			// Write
			$q = '
				UPDATE FLBS__Wefi_Geocode.stores6
				SET
					address="'.mysqli_real_escape_string($this->mysqli, $w['adr']).'",
					latitude="'.mysqli_real_escape_string($this->mysqli, $w['lat']).'",
					longitude="'.mysqli_real_escape_string($this->mysqli, $w['lng']).'"
				WHERE id="'.mysqli_real_escape_string($this->mysqli, $row['id']).'"
				LIMIT 1';
			$query = $this->mysqli->query($q) or die(mysqli_error($this->mysqli));
			echo 'Written...'."\n";//exit;
		}
		function dl() {
			$q = '
				SELECT * FROM FLBS__Wefi_Geocode.stores6 where dba!="McDonald\'s"';
				//WHERE FLBS_site_scrape_lat_lon IS NULL && dba="Pizza Hut"';
			$query = $this->mysqli->query($q) or die(mysqli_error());
			if (mysqli_num_rows($query) == 0) {
				echo 'No more rows!';
				exit;
			}
			$a = 0;
			while ($row = $query->fetch_assoc()) {
				echo $a."\n"; $a++;
				$this->recurse_get($row);
			}
			// Commit to db
			if (!$this->mysqli->commit()) {
				print('Transaction commit failed');
				exit;
			}
		}
	}
	new WeFi_Casino();
	exit;
}
/**
 * WeFi_PH
 * 
 * 
 */
//curl 'https://order.pizzahut.com/site/ajax/stores/getStoreAjax' -H 'Cookie: failover_checked=1; PHPpoolSSL=!Sh7gMX678BL8awDWWPR8rI/3M2NcITxO7PyTiO0PCQmp/Sl8d3ksrO4V7ecU8pLw2dYbdcMXBUp7+fg=; TS011d6839=01166f2bd0b0f41fac1874c128e0b0b8560e685fc3b57955a64c7c8e671b08f33845060c59d50712bb1c55a36fcd142261e29c329a; www-origin=yeti-NY-PLATTSBURGH-523; www-origin-reset=1; QOSESSID=d4pm4untpl2ijqojpletiqfa03; exp_last_visit=1451516887; exp_user_state=%7B%22status%22%3A%22unrecognized%22%2C%22occasion%22%3A%22C%22%2C%22StoreNumber%22%3Anull%2C%22cookie_loaded%22%3Atrue%2C%22current_search_occasion%22%3A%22C%22%2C%22action%22%3A%22%22%2C%22cookie_create_date%22%3A%2263919%22%7D; exp_last_activity=1451721646; exp_tracker=a%3A5%3A%7Bi%3A0%3Bs%3A29%3A%22site%2Fajax%2Fstores%2FgetStoreAjax%22%3Bi%3A1%3Bs%3A24%3A%22site_embed%2Fmap_it%2F022570%22%3Bi%3A2%3Bs%3A27%3A%22locations%2Futah%2FOgden%2F022570%22%3Bi%3A3%3Bs%3A16%3A%22locations%2F022570%22%3Bi%3A4%3Bs%3A29%3A%22site%2Fajax%2Fstores%2FgetStoreAjax%22%3B%7D; TS01bded03=01166f2bd04b8442a8514d20de28a478dbe63fbcfa1a0c02e8ee49f185f4458180c0549f801e0b477060c5222dd5827d347bf10824249b0e86e450ba2411b51a4d98e7f7e793b1392972bbd4a84e56810975695d32; TS0118ed69=01166f2bd00a564d7484e098b8f46c27d77e2b1e0a5dc099a4159c4d5c3cf55e069bbd0fd65630b595e409aa2067298aeee49aab4653e253f1816d9800d2b9a0b4aff53f08c7fb01c44fe3fb2f28baa9b3bef357fb30808d8172c309d06a04fd718aa70e62dbab10eeff0fc0a9d18637086d0ede88' -H 'Origin: https://order.pizzahut.com' -H 'Accept-Encoding: gzip, deflate' -H 'Accept-Language: en-US,en;q=0.8,nl;q=0.6,es;q=0.4,fr;q=0.2' -H 'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.106 Safari/537.36' -H 'Content-Type: application/x-www-form-urlencoded' -H 'Accept: */*' -H 'Referer: https://order.pizzahut.com/locations/utah/Ogden/022570' -H 'X-Requested-With: XMLHttpRequest' -H 'Connection: keep-alive' --data 'storeNum=022570' --compressed
if ($argv[1] == 'WeFi_PH') {
	class WeFi_PH {
		private $mysqli;
		function __construct() {
			$this->mysqli = connect__();
			$this->mysqli->autocommit(FALSE);
			$this->dl();
		}
		function recurse_get($row) { // Needs to be (6 digits) either 022570 or 002570 or 225700 or 761012
			// Download
			$u = new stdClass;
			$u->request_to_file = true;
			$u->request_from_file = true;
			$r = str_pad($row['store_number'], 6, "0", STR_PAD_LEFT);
			//~ $row['store_number'] = '022570';
			$u->filename = 'pizza-hut-stores-jan2-2016-'.preg_replace('/[^\d]/', '', $r);
			$u->folder = '/var/www/html/mturk/.deleteme/';
			$u->url = 'https://order.pizzahut.com/site/ajax/stores/getStoreAjax';
			$u->post = true;
			$u->post_str = 'storeNum='.urlencode($r); // Add "0" for second
			$u->headers = [
				'Cookie: failover_checked=1; PHPpoolSSL=!Sh7gMX678BL8awDWWPR8rI/3M2NcITxO7PyTiO0PCQmp/Sl8d3ksrO4V7ecU8pLw2dYbdcMXBUp7+fg=; TS011d6839=01166f2bd0b0f41fac1874c128e0b0b8560e685fc3b57955a64c7c8e671b08f33845060c59d50712bb1c55a36fcd142261e29c329a; www-origin=yeti-NY-PLATTSBURGH-523; www-origin-reset=1; QOSESSID=d4pm4untpl2ijqojpletiqfa03; exp_last_visit=1451516887; exp_user_state=%7B%22status%22%3A%22unrecognized%22%2C%22occasion%22%3A%22C%22%2C%22StoreNumber%22%3Anull%2C%22cookie_loaded%22%3Atrue%2C%22current_search_occasion%22%3A%22C%22%2C%22action%22%3A%22%22%2C%22cookie_create_date%22%3A%2263919%22%7D; exp_last_activity=1451721646; exp_tracker=a%3A5%3A%7Bi%3A0%3Bs%3A29%3A%22site%2Fajax%2Fstores%2FgetStoreAjax%22%3Bi%3A1%3Bs%3A24%3A%22site_embed%2Fmap_it%2F022570%22%3Bi%3A2%3Bs%3A27%3A%22locations%2Futah%2FOgden%2F022570%22%3Bi%3A3%3Bs%3A16%3A%22locations%2F022570%22%3Bi%3A4%3Bs%3A29%3A%22site%2Fajax%2Fstores%2FgetStoreAjax%22%3B%7D; TS01bded03=01166f2bd04b8442a8514d20de28a478dbe63fbcfa1a0c02e8ee49f185f4458180c0549f801e0b477060c5222dd5827d347bf10824249b0e86e450ba2411b51a4d98e7f7e793b1392972bbd4a84e56810975695d32; TS0118ed69=01166f2bd00a564d7484e098b8f46c27d77e2b1e0a5dc099a4159c4d5c3cf55e069bbd0fd65630b595e409aa2067298aeee49aab4653e253f1816d9800d2b9a0b4aff53f08c7fb01c44fe3fb2f28baa9b3bef357fb30808d8172c309d06a04fd718aa70e62dbab10eeff0fc0a9d18637086d0ede88',
				'Origin: https://order.pizzahut.com',
				'Accept-Encoding: gzip, deflate',
				'Accept-Language: en-US,en;q=0.8,nl;q=0.6,es;q=0.4,fr;q=0.2',
				'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.106 Safari/537.36',
				'Content-Type: application/x-www-form-urlencoded',
				'Accept: */*',
				'Referer: https://order.pizzahut.com/',
				'X-Requested-With: XMLHttpRequest',
				'Connection: keep-alive'
			];
			$rslt = curl_get($u);
			if ($rslt->status != 200) {
				echo 'error status='.$rslt->status;
				exit;
			}
			$r = json_decode($rslt->html);
			if (!$r || !$r->success || $r->success != 1 || !$r->response || !$r->response->storeNum) { // || $r->response->longitude == null
				//~ // Not second try?
				//~ if (!$second_try) {
					//~ echo 'second'."\n";
					//~ $this->recurse_get($row, true);
					//~ return;
				//~ }
				echo 'Failed:';
			} else {
				echo 'Success:';
			}
			$q = '
				UPDATE FLBS__Wefi_Geocode.stores5
				SET
					FLBS_site_scrape_lat_lon=1,
					FLBS_site_scrape_lat="'.mysqli_real_escape_string($this->mysqli, $r->response->latitude).'",
					FLBS_site_scrape_lon="'.mysqli_real_escape_string($this->mysqli, $r->response->longitude).'"
				WHERE FLBS_site_scrape_lat_lon IS NULL && dba="Pizza Hut" && id="'.mysqli_real_escape_string($this->mysqli, $row['id']).'"
				LIMIT 1';
			$query = $this->mysqli->query($q) or die(mysqli_error());
			echo $r->response->storeNum."\n";//exit;
		}
		function dl() {
			$q = '
				SELECT * FROM FLBS__Wefi_Geocode.stores5
				WHERE FLBS_site_scrape_lat_lon IS NULL && dba="Pizza Hut"';
				//LIMIT 1';
			$query = $this->mysqli->query($q) or die(mysqli_error());
			if (mysqli_num_rows($query) == 0) {
				echo 'No more rows!';
				exit;
			}
			while ($row = $query->fetch_assoc()) {
				$this->recurse_get($row, false);
			}
			// Commit to db
			if (!$this->mysqli->commit()) {
				print('Transaction commit failed');
				exit;
			}
		}
	}
	new WeFi_PH();
	exit;
}
/**
 * WeFi_TB
 * {
  "data": [
    {
      "distance": 0.0,
      "storeId": "5953",
      "entityId": "5953",
      "storeNumber": "009587",
 */
if ($argv[1] == 'WeFi_TB') {
	class WeFi_TB {
		private $mysqli;
		function __construct() {
			$this->mysqli = connect__();
			$this->mysqli->autocommit(FALSE);
			//Get data files
			// Calculate distances
			//if (file_exists('/var/www/html/mturk/.miscellaneous/starbucks-stores-dec18-2015')) {
			//	$this->stores_obj = json_decode(file_get_contents('/var/www/html/mturk/.miscellaneous/starbucks-stores-dec18-2015'));
			//	$this->calc_distances();
			//}
			// Scrape website
			//$this->check();exit;
			// Write to db
			//~ $this->write();
			$this->dl();
		}
		function dl() {
			$q = '
				SELECT * FROM FLBS__Wefi_Geocode.stores5
				WHERE FLBS_site_scrape_lat_lon IS NULL && dba="Taco Bell"
				LIMIT 1';
			$query = $this->mysqli->query($q) or die(mysqli_error());
			if (mysqli_num_rows($query) == 0) {
				echo 'No more rows!';
				exit;
			}
			$row = $query->fetch_assoc();
			// Update row
			$q = '
				UPDATE FLBS__Wefi_Geocode.stores5
				SET
					FLBS_site_scrape_lat_lon=1
				WHERE FLBS_site_scrape_lat_lon IS NULL && dba="Taco Bell" && id="'.mysqli_real_escape_string($this->mysqli, $row['id']).'"
				LIMIT 1';
			$query = $this->mysqli->query($q) or die(mysqli_error());
			if (!$this->mysqli->commit()) {
				print('Transaction commit failed');
				exit;
			}
			echo 'Row is:'.$row['id']."\n";
			// Download
			$u = new stdClass;
			$u->request_to_file = true;
			$u->request_from_file = true;
			$u->filename = 'taco-bell-stores-dec28-2015-'.md5($row['latitude'].$row['longitude']);
			$u->folder = '/var/www/html/mturk/.miscellaneous/';
			$u->url = 'https://prd-tac-api01.cfrprd.com/location/v1/stores?longitude='.urlencode($row['longitude']).
				'&latitude='.urlencode($row['latitude']).
				'&distance=100';
			$u->headers = [
				'Origin: https://www.tacobell.com',
				'Accept-Encoding: gzip, deflate, sdch',
				'Accept-Language: en-US,en;q=0.8,nl;q=0.6,es;q=0.4,fr;q=0.2',
				'Source: Web',
				'Authorization: bearer 2962bdbe-503c-11e5-8504-005056b335f2',
				'Content-type: application/json',
				'Accept: */*',
				'Device-Identifier: a49e30c4-b982-4f46-671d-52455404485a',
				'CF-App-Version: v1',
				'X-Requested-With: XMLHttpRequest',
				'Connection: keep-alive',
				'Referer: https://www.tacobell.com/locations',
				'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.106 Safari/537.36',
				'DeviceType: Desktop'
			];
			$rslt = curl_get($u);
			if ($rslt->status != 200) {
				echo 'error status='.$rslt->status;
				exit;
			}
			$r = json_decode($rslt->html);
			if (!$r->data || count($r->data) == 0) {
				echo '??';
				exit;
			}
			foreach ($r->data as $k => $v) {
				if (!property_exists($v, 'storeNumber')) continue;
				if (!property_exists($v, 'coordinates')) {
					$v->coordinates = new stdClass;
					$v->latitude=NULL;
					$v->longitude=NULL;
				}
				$sn = $v->storeNumber . ''; // Ensure a string
				//~ echo $sn.' ';
				if (!preg_match('/[a-zA-Z]/', $sn)) {
					$sn = intval($sn); // Convert to integer, removes leading zeroes
					//~ echo $sn.' ';
				}
				//~ echo $sn.' ';
				if (!$sn) continue;
				$q = '
					UPDATE FLBS__Wefi_Geocode.stores5
					SET
						FLBS_site_scrape_lat_lon=1,
						FLBS_site_scrape_lat="'.mysqli_real_escape_string($this->mysqli, $v->coordinates->latitude).'",
						FLBS_site_scrape_lon="'.mysqli_real_escape_string($this->mysqli, $v->coordinates->longitude).'"
					WHERE FLBS_site_scrape_lat_lon IS NULL && dba="Taco Bell" && store_number="'.mysqli_real_escape_string($this->mysqli, $sn).'"
					LIMIT 1';
					//~ SET
						//~ `FLBS_site_scrape_lat`="'.$mysqli->escape_string($item->latitude).'",
						//~ `FLBS_site_scrape_lon`="'.$mysqli->escape_string($item->longitude).'"
					//~ WHERE
						//~ `store_number` = "'.$mysqli->escape_string($key).'"
						 //~ [coordinates] => stdClass Object
                        //~ (
                            //~ [latitude] => 33.846873
                            //~ [longitude] => -118.117508
                        //~ )


				//~ ';
				$query = $this->mysqli->query($q) or die(mysqli_error());
			}//exit;
			if (!$this->mysqli->commit()) {
				print('Transaction commit failed');
				exit;
			} else echo 'x';
			//~ exit;
			// Do again!
			$this->dl();
		}
	}
	new WeFi_TB();
	exit;
}
/**
 * WeFi_SB
 * Starbucks scrape.
 * Total=23279.
 * Increment is 0,50,100,...,
 * Function distance => distance($lat1, $lon1, $lat2, $lon2, $unit)
 * Add to db?: FLBS_site_scrape_lat_lon, FLBS_site_scrape_closest_stores
 * Note: 40 update queries per second. There are approximately 23,000 update queries.
 *       So total time ought to be roughly 575 seconds, or 10 minutes.
 * https://openapi.starbucks.com/location/v1/stores?apikey=_redacted_&format=json&limit=50&offset=0&ignore=HoursNext7Days,today,extendedHours,regularHours,features,timeZoneInfo,ownershipTypeCode
 */
if ($argv[1] == 'WeFi_SB') {
	class WeFi_SB {
		private $max = 23279;
		private $offset = 0;
		private $stores_obj = [];
		private $cache_distance = [];
		function __construct() {
			// Calculate distances
			//if (file_exists('/var/www/html/mturk/.miscellaneous/starbucks-stores-dec18-2015')) {
			//	$this->stores_obj = json_decode(file_get_contents('/var/www/html/mturk/.miscellaneous/starbucks-stores-dec18-2015'));
			//	$this->calc_distances();
			//}
			// Scrape website
			//$this->check();exit;
			// Write to db
			$this->write();
		}
		function write() {
			if (file_exists('/var/www/html/mturk/.miscellaneous/starbucks-stores-dec18-2015-with-distances')) {
				$mysqli = connect__();//'FLBS__Wefi_Geocode'
				if (mysqli_connect_errno() || !$mysqli) {
					printf("Connect failed: %s\n", mysqli_connect_error());
					exit;
				}
				$mysqli->autocommit(FALSE);
				//~ mysqli_autocommit($mysqli, FALSE);
				$this->stores_obj = json_decode(file_get_contents('/var/www/html/mturk/.miscellaneous/starbucks-stores-dec18-2015-with-distances'));
				$start = microtime(true);
				$x = 1;
				//~ $big_query = [];
				foreach ($this->stores_obj as $key => $item) {
					//~ if ($x < 4000) {
						//~ $x++;
						//~ continue;
					//~ }
					$c = [];
					if (count($item->closest) > 0) {
						$count = 0;
						foreach ($item->closest as $k => $v) { // Generate lat lon pairs for closest...
							$a = $this->stores_obj->$v; // Other store
							array_push($c, $a->latitude . ',' . $a->longitude);
							$count++;
							//if ($count > 20) break;
						}
					}
					//~ array_push(
						//~ $big_query,
						//~ 'UPDATE FLBS__Wefi_Geocode.stores4
							//~ SET
								//~ FLBS_site_scrape_lat_lon="'.mysql_real_escape_string($item->latitude.','.$item->longitude).'",
								//~ `FLBS_site_scrape_closest_stores`="'.mysql_real_escape_string(implode('|', $c)).'"
							//~ WHERE
								//~ `store_number` = "'.mysql_real_escape_string($key).'"'
					//~ );
					
					$q = '
						UPDATE FLBS__Wefi_Geocode.stores4
						SET
							`FLBS_site_scrape_lat`="'.$mysqli->escape_string($item->latitude).'",
							`FLBS_site_scrape_lon`="'.$mysqli->escape_string($item->longitude).'"
						WHERE
							`store_number` = "'.$mysqli->escape_string($key).'"
					';
					$query = $mysqli->query($q) or die(mysqli_error());
					if ($x % 100 == 0) {
						if (!$mysqli->commit()) {
							print('Transaction commit failed');
							exit;
						}
						$end = microtime(true);
						echo 'The last 100 queries took ' . ($end - $start) . ' milliseconds. (x='.$x.')'."\n";
						$start = microtime(true); // Reset this
						//~ exit;
					}
					$x++;
				}
				exit;
			}
		}
		function check() {
			if ($this->offset >= $this->max) {
				echo 'End: $this->offset='.$this->offset."\n";
				print(count($this->stores_obj)."\n");
				file_put_contents(
					'/var/www/html/mturk/.miscellaneous/starbucks-stores-dec18-2015',
					json_encode($this->stores_obj)
				);
				//~ $this->calc_distances();
				exit;
			}
			$u = new stdClass;
			$u->request_to_file = true;
			$u->request_from_file = true;
			$u->filename = 'starbucks-stores-dec18-2015-'.$this->offset;
			$u->folder = '/var/www/html/mturk/.miscellaneous/';
			$u->url = 'https://openapi.starbucks.com/location/v1/stores?apikey=_redacted_'.
				'&format=json&limit=50&offset='.$this->offset.
				'&ignore=HoursNext7Days,today,extendedHours,regularHours,features,timeZoneInfo,ownershipTypeCode';
			$rslt = curl_get($u);
			if ($rslt->status != 200) {
				echo 'error status='.$rslt->status;
				exit;
			}
			$r = json_decode($rslt->html);
			if (!$r->items || count($r->items) == 0) {
				echo '??';
				exit;
			}
			foreach ($r->items as $key => $item) {
				$this->stores_obj[ $item->storeNumber ] = [
					'name'				=>$item->name,
					'latitude'			=>$item->coordinates->latitude,
					'longitude'			=>$item->coordinates->longitude,
					'streetAddressLine1'=>$item->address->streetAddressLine1,
					'countryCode'		=>$item->address->countryCode
				];
			}
			print(count($this->stores_obj)."\n");
			$this->offset += 50;
			$this->check();
		}
		function calc_distances() {
			$c = 0;
			foreach ($this->stores_obj as $key => $item) {
				$this->stores_obj->$key->closest = $this->find_nearby($key);
				if ($c % 10 == 0) {
					echo $c."\n";
				}
				$c++;
			}
			file_put_contents(
				'/var/www/html/mturk/.miscellaneous/starbucks-stores-dec18-2015-with-distances',
				json_encode($this->stores_obj)
			);
		}
		function find_nearby($store_id) {
			$closest = [];
			$s1 = $this->stores_obj->$store_id;
			$obj_closest = [];
			foreach ($this->stores_obj as $key => $item) {
				if ($key == $store_id) continue; // Skip self
				if ($s1->countryCode != $item->countryCode) continue; // Skip other countries
				$hash_names = ($store_id > $key) ? $store_id.' '.$key : $key.' '.$store_id;
				if ($self->cache_distance[ $hash_names ]) {
					$d = $self->cache_distance[ $hash_names ];
				} else {
					$d = distance($s1->latitude, $s1->longitude, $item->latitude, $item->longitude, 'M');
					$self->cache_distance[ $hash_names ] = $d; // Memoize this!
				}
				if ($d >= 5) continue; // None further than 5 miles
				if ($obj_closest[$d]) {
					array_push($obj_closest[$d], $key); // Add to old
				} else {
					$obj_closest[$d] = [ $key ]; // New array
				}
			}
			ksort($obj_closest); // Sort by keys. This is DESC.
			//~ print_r($obj_closest[0]);exit;
			
			foreach ($obj_closest as $miles => $item) {
				if ($miles > 4 || count($closest) >= 20) {
					return $closest;
				}
				foreach ($item as $k => $id) {
					array_push($closest, $id);
					if (count($closest) >= 20 && $miles > 1)
						break;
				}
				//~ echo 'miles:'.$miles."\n";
				//~ echo 'num:'.count($item). "\n";
			}
		}
	}
	new WeFi_SB();
	exit;
}
/**
 * WeFi_Quality_Control (aka WeFi_QC)
 * 
 * @param DATE YYYY-MM-DD, e.g. 2014-09-23
 * 
 * @TODO Make default select last 1 day.
 * @TODO "HIT Id", "Store to Geocode", "Polygon Geocode Data" ??
 * 
 * e.g. php -f util.php -- "WeFi_QC" "2015-07-24""
 */
if ($argv[1] == 'WeFi_QC') {
	class wefi_correct {
		function __construct() {
			global $argv;
			$this->check();
			connect_();
			$fp = fopen('WeFi: All completed geocoding for the day of '.$argv[2].'.csv', 'w');
			$q = @mysql_query('
				SELECT
					amt_hit_id
				FROM
					AMT_Alerts.tasks_accomplished
				WHERE
					fk_amt_date="'.mysql_real_escape_string($argv[2]).'" &&
					fk_requesterUserId="A2BW27V01DOI0Q"
			') or die(mysql_error());
			if (mysql_num_rows($q) == 0) {
				echo 'No tasks found. Is HITs DB updated?';
				exit;
			}
			while ($row = mysql_fetch_array($q)) {
				//array_push($hitIds, $row['amt_hit_id']);
				$q2 = @mysql_query('
					SELECT
						matchString, outputs
					FROM
						AMT_Records.InputOutputMatches
					WHERE 
						matchString LIKE \'%'.mysql_real_escape_string($row['amt_hit_id']).'%\' &&
						recordName = "WeFi - Quality Control"
					LIMIT 1
				');
				if (mysql_num_rows($q2) == 0) {
					echo 'HIT ID found in tasks_accomplished but NOT in InputOutputMatches!';
					exit;
				}
				$row2 = mysql_fetch_array($q2);
				$ms = explode('||||', $row2['matchString']);
				$tmp_store = '';
				if ($ms[0] != $ms[1]) { // Has separated store name?
					$tmp_store = trim($ms[0]) .' '. $ms[1];
				} else {
					$tmp_store = $ms[0];
				}
				// Get geo
				$tmp_op = json_decode($row2['outputs']);
				// Fields...
				$fields = array(
					$row['amt_hit_id'],
					$tmp_store,
					$tmp_op[0]->value
				);
				// Write
				fputcsv($fp, $fields);
			}
			fclose($fp);
		}
		function check() {
			global $argv;
			if (!isset($argv[2])) {
				echo 'No date!';
				exit;
			}
		}
	}
	new wefi_correct();
	exit;
}
//
// 
//AMT_Facebook__First_and_Last_Names
if ($argv[1] == 'UniversityStudy_names__utilinsertdb' && 0 == 1) { // init
	connect_();

	$fn = './.miscellaneous/University Study-facebook-firstnames-withcount.txt';
	$ln = './.miscellaneous/University Study-facebook-lastnames-withcount.txt';
	//$names = json_decode($_GET['UniversityStudy__names']); // array of arrays[[f,l],[f,l],...[n]]
	//$fn = trim(file_get_contents($fn));
	$fn = file_get_contents($ln);
	
	$while_num = 999;
	$offset = 0;
	$iterations = 0;
	$fn = "\n".$fn;
	while ($while_num > 20) {
		
		//~ preg_match('/[\r\n]+(\d+) ([^\r\n]+)/', $fn, $matches);
		//~ $fn = trim(substr($fn,4)); //
		//~ print_r($matches);
		//$fn = trim(substr($fn,4)); //
		
		preg_match('/[\r\n ]+\d+[^\r\n]+/', $fn, $matches, PREG_OFFSET_CAPTURE, $offset);
		
		//$fn = trim(substr($fn, $matches[0][1] + 6)); //
		//echo substr($fn, $matches[0][1] + 6, 40);
		//echo "\n";
		//echo $matches[0][1] + 4;
		//print_r($matches);
		//$offset = intval($matches[0][1]) + 2;
		//print_r($matches[0]);
		
		$arr = explode(' ', trim($matches[0][0]));
		$num = array_shift($arr);
		$name = trim(implode(' ', $arr));
		$offset = $matches[0][1] + 4;
		$num = intval($num);
		
		//echo "\noffset:$offset\n"; 
		//print_r($num."\n");
		//print_r($name);
		
		mysql_query('
			INSERT INTO AMT_Facebook__First_and_Last_Names.last_names
			SET
				name="'.mysql_real_escape_string($name).'",
				count='.$num.'
		');// or die(mysql_error());
		
		$while_num = $num;
		$iterations++;
		if ($iterations % 400 == 0)
		{
			echo '.';
			echo $iterations."($offset)";
			//exit;
		}
		//echo "\n";
	}
	echo $while_num;
	
	close_();
	exit;
}
if (isset($_GET['UniversityStudy_names'])) { //
	@connect_();
	
	$db = mysql_real_escape_string($_GET['UniversityStudy_names__db']);
	$name = mysql_real_escape_string($_GET['UniversityStudy_names']);
	$rslt = mysql_query('
		SELECT * FROM
			AMT_Facebook__First_and_Last_Names.'.$db.'
		WHERE
			name="'.$name.'" && count >= 400
		LIMIT 1
	') or die(mysql_error());
	
	if (mysql_num_rows($rslt) > 0) { // always greater than 0
		$row = mysql_fetch_array($rslt);
		echo $row['count'];
	} else
		echo '0';
		
	close_();
	exit;
}
//
// Sharon Sung
//
if (isset($_GET['SharonSung_brokercheck__complete'])) { // read done //==b
	
	$file = str_replace('/', '', urldecode($_GET['SharonSung_brokercheck__complete']));
	
	$file = './.deleteme/SharonSung_brokercheck__'.$file;
	
	if (file_exists($file))
	{
		echo file_get_contents($file);
	}
	
}
else if (isset($_GET['SharonSung_brokercheck__write']) && isset($_GET['SharonSung_brokercheck__id'])) { // save license type
	
	// This overwrites any existing file!
	
	$file = str_replace('/', '', $_GET['SharonSung_brokercheck__id']);
	
	$file = './.deleteme/SharonSung_brokercheck__' . $file;
	
	file_put_contents($file, $_GET['SharonSung_brokercheck__write']);
	chmod($file, 0777);
}
else if (isset($_POST['SharonSung_brokercheck__write']) && isset($_POST['SharonSung_brokercheck__id'])) { // save done
	
	$file = str_replace('/', '', $_POST['SharonSung_brokercheck__id']);
	
	$file = './.deleteme/SharonSung_brokercheck__' . $file;
	
	if (!file_exists($file)) {
		// this will save .id, .multiple, if multiple results
		// else this will save '' blank if initiating?
		file_put_contents($file, $_POST['SharonSung_brokercheck__write']); 
		chmod($file, 0777);
		exit;
	}
	// get file!
	$f = file_get_contents($file);
	
	if ($f != '') { // overwrite
		
	} else { // is blank
		file_put_contents($file, $_POST['SharonSung_brokercheck__write']); //
	}
	chmod($file, 0777);
	//~ if ($f == 'SEC' || $f == 'FINRA' || $f == 'DUAL') { // first write to file after initial write
		//~ 
		//~ $obj = json_decode($_POST['write']);
		//~ 
		//~ $obj->lic_type = $f;
		//~ 
		//~ // Set both sites as being not active. This will be updated on subsequent
		//~ // runs.
		//~ if (!isset($obj->not_active_SEC)) {
			//~ $obj->not_active_SEC = true;
		//~ } else if (!isset($obj->not_active_FINRA)) {
			//~ $obj->not_active_FINRA = true;
		//~ }
	//~ }
	//~ else {  // second write to file (if necessary)
			//~ // overwrite if second exists (finra)
			//~ // all but name
		//~ $obj = json_decode($f);
		//~ $obj_post = json_decode($_POST['write']);
		//~ 
		//~ foreach ($obj_post as $k => $v) { //
			//~ if ($k == 'name') { // name
				//~ if ($obj->name == '')
					//~ $obj->name = $obj_post->name;
				//~ continue;
			//~ }
			//~ //if ($k == 'hasResult') continue; //skip here
			//~ 
			//~ if  (
					//~ ($k == 'comp' || $k == 'compType') &&
					//~ $obj->comp != '' // company exists!
				//~ )
			//~ {
				//~ continue; ////
			//~ }
			//~ $obj->$k = $v; // all else
		//~ }
	//~ }
	// Set .multiple to blank. .multiple is for scraping the results of a search where
	// more than one broker is returned.
	// THIS SEEMS UNNECESSARY TO BE REMOVE .multiple. Many instance of .mupltiple
	//	// could occur, in which case after the first instance all others would only ever get to see "".
	// 		
	/*$obj->multiple = '';*/ 
	//~ file_put_contents($file, json_encode($obj));
}
else if (isset($_GET['SharonSung_brokercheck__appendQueue'])) { // init //==b
	
	// is it already complete and/or in progress?
	$file = str_replace('/', '', urldecode($_GET['SharonSung_brokercheck__appendQueue'])); // "FNAME LNAME" from script not "FNAME+LNAME"
	$file = './.deleteme/SharonSung_brokercheck__'.$file;
	if (file_exists($file))
	{
		exit;
	}
	
	$queue_file = './.deleteme/SharonSung_brokercheck__queue';
	if (!file_exists($queue_file)) {
		file_put_contents($queue_file, "\n");
		chmod($queue_file, 0777);
	}
	$f = file_get_contents($queue_file);
	
	// if is in file in different location, add to top
	// else add to bottom
	
	if (strpos($f, $_GET['SharonSung_brokercheck__appendQueue']."\n") !== false) {
		$f = str_replace($_GET['SharonSung_brokercheck__appendQueue']."\n", '', $f);
		$f = $_GET['SharonSung_brokercheck__appendQueue']."\n".$f; // add at first
	}
	else {
		$f.= $_GET['SharonSung_brokercheck__appendQueue']."\n"; // add at last
	}
	
	file_put_contents($queue_file, $f, LOCK_EX);
}
else if (isset($_GET['SharonSung_brokercheck__readQueue'])) { // next in queue

	$file = './.deleteme/SharonSung_brokercheck__queue';
	if (!file_exists($file)) {
		file_put_contents($file, "\n");
		chmod($file, 0777);
	}
	$f = trim(file_get_contents($file));

	if ($f != '') {
		
		$f = explode("\n", $f);
		
		if (count($f) > 0) {
			$x = array_shift($f); // first element
			$f = implode("\n", $f) . "\n"; // add \n for elements still in queue file
			
			file_put_contents($file, $f, LOCK_EX);
			
			echo trim($x);
		}
	}
}



/**
 * AMT__autoComplete
 * @param AMT__autoComplete
 * @param AMT__autoComplete_recordName
 */
if (isset($_GET['AMT__autoComplete']) && isset($_GET['AMT__autoComplete_recordName'])) { //
	
	@connect_();
	//$str = '[{"inputIdentifier":"Name(q1)[0]","value":false},{"inputIdentifier":"Name(q1)[1]","value":true},{"inputIdentifier":"Name(instructions)[0]","value":"';
	$str = '"value":"';
	$str.= $_GET['AMT__autoComplete'];
	$rslt = mysql_query(
		'
		SELECT COUNT(outputs) as tmp_count, outputs FROM AMT_Records.InputOutputMatches
			WHERE recordName="'.mysql_real_escape_string($_GET['AMT__autoComplete_recordName']).'" &&
			outputs LIKE \'%'.mysql_real_escape_string($str).'%\'
			GROUP BY outputs
			ORDER BY tmp_count DESC
			LIMIT 8
		'
	);
	$out = array();
	while ($row = mysql_fetch_array($rslt)) {
		array_push($out, $row['outputs']);
	}
	echo json_encode($out);
	
	exit;
}
//
// Katelin_Williams_math_ocr__dbCheck
// most popular
if (isset($_GET['Katelin_Williams_math_ocr__dbCheck'])) { // new
	
	// modified?
	$f = './.miscellaneous/Katelin_Williams 2';
	$new = false;
	if (!file_exists($f)) {
		file_put_contents($f, '');
		chmod(0777, $f);
		$new = true;
	}
	
	if (filemtime($f) < time() || $new) { // time - 60
		@connect_();
		$rslt = mysql_query(
			'
			SELECT COUNT(outputs) AS count_o, outputs FROM AMT_Records.InputOutputMatches
				WHERE recordName="Katelin Williams 2"
				GROUP BY outputs
				ORDER BY count_o DESC
				LIMIT 40
			'
		);
		$out = array();
		while ($row = mysql_fetch_array($rslt)) {
			array_push($out, $row['outputs']);
		}
		$fp = json_encode($out);
		file_put_contents($f, $fp);
	} else {
		$fp = file_get_contents($f);
	}
	
	echo $fp;
	
	exit;
}
//
// Ce Zhang
//
if (isset($_GET['Ce_Zhang_id']) && isset($_GET['Ce_Zhang_src'])) { // new
	
	//
	// kills
	//
	
	header("Connection: close");
	ob_start();
	phpinfo();
	$size = ob_get_length();
	header("Content-Length: $size");
	ob_end_flush();
	flush();
	
	$ch = curl_init($_GET['Ce_Zhang_id']);
	curl_setopt($ch, CURLOPT_VERBOSE, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
	curl_setopt($ch, CURLOPT_POST, false);
	curl_setopt($ch, CURLOPT_COOKIEFILE, '/var/www/html/cookie.txt');
	curl_setopt($ch, CURLOPT_COOKIEJAR, '/var/www/html/cookie.txt');
	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	$r = curl_exec($ch);
	$x = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	if ($x != 200) {
		exit;
	}
	//file_put_contents
	
	chdir('./.deleteme/');
	$f = preg_replace('/\?[^$]*/', '', basename($_GET['Ce_Zhang_id']));
	$f = md5($_GET['Ce_Zhang_id']) . $f;
	file_put_contents($f, $r);
	//file_put_contents($f, file_get_contents($_GET['Ce_Zhang_id']));exit; // WHAT!? getaddress...getnetworkaddr or something error...
	
	shell_exec('convert "'.$f.'" -level 20%,80%,2.0 -depth 8 -density 300 -resize 200% "'.$f.'.tif"'); // "can't handle bpp > 32" ??
	shell_exec('tesseract "'.$f.'.tif" "'.$f.'.tif" -l eng -psm 3 nobatch goodchars'); //3 = "Default"
	exit;
}
if (isset($_GET['Ce_Zhang_id'])) { // done
	$f = preg_replace('/\?[^$]*/', '', basename($_GET['Ce_Zhang_id']));
	$f = md5($_GET['Ce_Zhang_id']) . $f;
	$f = './.deleteme/'.$f.'.tif.txt';
	if (file_exists($f)) {
		echo file_get_contents($f);
	}
	exit;
}
//
// 
//
if (isset($_GET['Center_for_Investigative_Reporting__write'])) { //write
	$file = './.miscellaneous/Center_for_Investigative_Reporting__autofill';
	if (!file_exists($file)) {
		file_put_contents($file, '');
		chmod($file, 0777);
	}
	$x = file_get_contents($file);
	$s = $_GET['Center_for_Investigative_Reporting__write'];
			
	$x = preg_replace('/([\^\n])'.preg_quote($s, '/').'\n+/','$1',$x);
	
	$x .= $s . "\n";
	file_put_contents($file, $x);
	exit;
} else if (isset($_GET['Center_for_Investigative_Reporting__read'])) { //read
	$file = './.miscellaneous/Center_for_Investigative_Reporting__autofill';
	if (file_exists($file)) {
		$x = file_get_contents($file);
		$s = $_GET['Center_for_Investigative_Reporting__read'];
		preg_match_all('/[\^\n]('.preg_quote($s, '/').'[^\n$]*)/i', $x, $m);
		//preg_match_all('/[\^\n]('.preg_quote($s, '/').')\n+/i', $x, $m);
		if (!empty($m[1])) {
			$str = '';
			foreach ($m[1] as $key=>$v) {
				$str .= $v."\n";
			}
			echo trim($str);
		}
	}
	exit;
}
//
// Tyler Wry
//
if (isset($_GET['tyler_wry_universities_school']) && isset($_GET['tyler_wry_universities_directory'])) { //write
	$file = './.miscellaneous/Tyler_Wry_Universities';
	if (!file_exists($file)) {
		file_put_contents($file, '');
		chmod($file, 0777);
	}
	$x = file_get_contents($file);
	$s = $_GET['tyler_wry_universities_school']. ' ';
	$s_new = $_GET['tyler_wry_universities_school']. ' ' .$_GET['tyler_wry_universities_directory'];
	//echo preg_quote($s);
	//if (stripos($x, $s) !== false) {
		$x = preg_replace('/([\^\n])'.preg_quote($s, '/').'[^\n]+\n+/i','$1',$x);
		//echo '/([\^\n])'.preg_quote($s, '/').'[^\n]+\n+/i';
	//}
	$x .= $s_new . "\n";
	file_put_contents($file, $x);
	exit;
} else if (isset($_GET['tyler_wry_universities_school'])) { //read
	$file = './.miscellaneous/Tyler_Wry_Universities';
	if (file_exists($file)) {
		$x = file_get_contents($file);
		$s = $_GET['tyler_wry_universities_school'];
		preg_match('/'.preg_quote($s, '/').' ([^\n]*)\n+/i', $x, $m);
		if (!empty($m[1])) {
			echo $m[1];
		}
	}
	exit;
}
//
// James_M_Ratcliffe_readQueue
//
if (isset($_GET['James_M_Ratcliffe_readcomplete'])) { // read
	$file = './.cached/James_M_Ratcliffe_'.str_replace('/', '', $_GET['James_M_Ratcliffe_readcomplete']);
	//echo $file;
	if (file_exists($file)) {
		echo file_get_contents($file);
	}
	exit;
}
if (isset($_GET['James_M_Ratcliffe_writecomplete']) && isset($_GET['James_M_Ratcliffe_writecomplete_val'])) { // write
	$file = './.cached/James_M_Ratcliffe_'.str_replace('/', '', $_GET['James_M_Ratcliffe_writecomplete']);
	file_put_contents($file, $_GET['James_M_Ratcliffe_writecomplete_val']);
	@chmod($file, 0777);
	exit;
}
if (isset($_GET['James_M_Ratcliffe_readqueue'])) { // read
	$file = './.miscellaneous/James_M_Ratcliffe_queue';
	if (!file_exists($file)) {
		file_put_contents($file, '');
		@chmod($file, 0777);
		$f = '';
	} else {
		$f = file_get_contents($file);
		$f = trim($f);
	}
	
	if ($f != '') {
		$f = explode("\n", $f);
		if (count($f) > 0) {
			$x = array_shift($f); // first element
			$f = implode("\n", $f) . "\n"; // add \n for elements still in queue file
			file_put_contents($file, $f, LOCK_EX);
			echo trim($x);
		}
	}
	exit;
}
if (isset($_GET['James_M_Ratcliffe_addqueue'])) { // read
	$file = './.miscellaneous/James_M_Ratcliffe_queue';
	if (!file_exists($file)) {
		file_put_contents($file, '');
		@chmod($file, 0777);
		$f = '';
	} else {
		$f = file_get_contents($file);
		//$f = trim($f);
	}
	
	if (strpos($f, $_GET['James_M_Ratcliffe_addqueue'] . "\n") === false) {
		$f .= $_GET['James_M_Ratcliffe_addqueue'] . "\n";
		file_put_contents($file, $f, LOCK_EX);
	}
	exit;
}

//
// Dan_Barowy
//
if (isset($_POST['Dan_Barowy_id']) && isset($_POST['Dan_Barowy_src'])) { // new
	
	//
	// kills
	//
	
	header("Connection: close");
	ob_start();
	phpinfo();
	$size = ob_get_length();
	header("Content-Length: $size");
	ob_end_flush();
	flush();
	
	chdir('./.deleteme/');
	$f = preg_replace('/\?[^$]*/', '', basename($_POST['Dan_Barowy_id']));
	file_put_contents($f, file_get_contents($_POST['Dan_Barowy_id']));
	shell_exec('convert '.$f.' -resize 600% -level 20%,80%,2.0 '.$f.'.tif');
	shell_exec('tesseract '.$f.'.tif '.$f.'.tif -l eng -psm 7 nobatch digits2'); //hocr
	//$out = ``;
	exit;
}
if (isset($_GET['Dan_Barowy_id'])) { // done
	$f = preg_replace('/\?[^$]*/', '', basename($_GET['Dan_Barowy_id']));
	$f = './.deleteme/'.$f.'.tif.txt';
	if (file_exists($f)) {
		echo file_get_contents($f);
	}
	exit;
}

//
// Raymond_Chandler
//
if (isset($_POST['Raymond_Chandler']) && isset($_POST['Raymond_Chandler_id'])) { // new
	set_time_limit(0);
	
	//
	// kills
	//
	header("Connection: close");
	ob_start();
	phpinfo();
	$size = ob_get_length();
	header("Content-Length: $size");
	ob_end_flush();
	flush();
	
	$f = './.deleteme/'.$_POST['Raymond_Chandler_name'].'_'.$_POST['Raymond_Chandler_id']; // (id) is # \d{1,}
	
	$short = $_POST['Raymond_Chandler_name'].'_'.$_POST['Raymond_Chandler_id'];
	
	if (!file_exists($f)) {
		mkdir($f); // 777
	}
	
	// files are downloadable URLs
	$files = json_decode($_POST['Raymond_Chandler']);
	file_put_contents($f.'-all-files.log', implode(',',$files));
	
	$zip = new ZipArchive();
	$z = $zip->open($f . '.zip', ZIPARCHIVE::OVERWRITE);
	if ($z !== TRUE) {
		file_put_contents(
			$f.'.log', 'cannot open file'."\n".$z."\n",
			FILE_APPEND
		);
		exit;
	}
	
	// iterate and download
	foreach ($files as $k => $file) {
		
		$new = $f . '/' .basename($file);
		
		if (file_exists($new)) {
			$zip->addFile($new);
			continue;
		}
		
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
		} else {
			//if (file_exists($new)) {
			//	file_put_contents($f.'.log', $file.' EXISTS'."\n", FILE_APPEND);
			//}
			file_put_contents( // filename: from page is 'File Name.pdf', from URL is 'File_Name.pdf', after download is same as URL
				$new,
				$r
			);
			$zip->addFile($new);
		}
	}
	
	ob_start();
	//$zip->addDir($f);
	$zip->close();
	$x = ob_get_contents();
	//rename($short.'.zip', $f.'.zip');
	file_put_contents($f.'complete', $x);
	
	/*// // // // //
	class Zipper extends ZipArchive {
		public function addDir($path) {
			$this->addEmptyDir($path);
			$nodes = glob($path . '/*');
			foreach ($nodes as $node) {
				if (is_dir($node)) {
					$this->addDir($node);
				} else if (is_file($node)) {
					$this->addFile($node);
				}
			}
		}
	} // class Zipper
	// save folder to a zip titled by name + id
	$zip = new ZipArchive; //
	//$zip = new Zipper; //
	if ($zip->open($f . '.zip')) { //str_replace(' ', '_', $f)
		ob_start();
		$zip->addDir($f);
		$zip->close();
		$x = ob_get_contents();
		//echo 'ok';
		file_put_contents($f.'complete', $x);
	} else {
		//echo 'failed';
		file_put_contents($f.'complete', 'failed to open '.$f.'.zip');
	}*/
	/*// save folder to a zip titled by name + id
	$zip = new ZipArchive; //
	if ($zip->open('./.deleteme/' . $f . '.zip') === TRUE) {
		$zip->addFile($f);
		$zip->close();
		echo 'ok';
	} else {
		echo 'failed';
	}*/

	exit;
}
if (isset($_GET['Raymond_Chandler_complete'])) { // show complete
	
	$f = './.deleteme/'.$_GET['Raymond_Chandler_name'].'_'.$_GET['Raymond_Chandler_id'];
	
	if (file_exists($f.'complete')) { // good is if blank
		echo file_get_contents($f.'complete');
		if (file_exists($f.'.log'))
			echo file_get_contents($f.'.log');
	} else {
		echo 'wait';
	}
	
	exit;
}

//
// Joshua_Kitlas
//
if (isset($_GET['Joshua_Kitlas'])) {
	$f = file_get_contents('./.miscellaneous/crossfit');
	preg_match(
		'/<b><a href="[^"]*"[^>]*>'.preg_quote($_GET['Joshua_Kitlas']).'<\/a>[^\r\n]*/i',
		$f,
		$m
	);//'/<b><a href="[^"]+" target="_blank">[^<]+<\/a></b><br \/>[^<]+<br \/>[^<]+<br \/>[^\r\n]+/',
	
	$r = array();
	if (!empty($m[0])) {
		$r['result'] = $m[0];
	} else {
		preg_match_all(
			'/\n[^\r\n]*'.preg_quote($_GET['Joshua_Kitlas']).'[^\r\n]*/i',
			$f,
			$m
		);
		if (!empty($m[0])) {
			$r['results'] = array();
			foreach ($m[0] as $k => $v) {
				array_push($r['results'], $v);
			}
		} else {
			echo 'X';
			exit;
		}
	}
	echo json_encode($r);
	exit;
}
if (isset($_GET['crossfit_scrape']) || $argv[1] == 'crossfit') {
	set_time_limit(0);
	
	// exists in file?
	$f = file_get_contents('./.miscellaneous/crossfit');
			
	for ($i = 0; $i <= 8879; $i++) {
	//for ($i = 8879; $i >= 0; $i--) {
	//for ($i = 4440; $i >= 0; $i--) {
	//for ($i = 4441; $i >= 0; $i++) {
		//echo '<br>';
		
		if (strpos($f, "\n$i ") !== false) {
			continue; //SKIPS!
		}
		
		echo $i.'.';
		$ch = curl_init('http://map.crossfit.com/affinfo.php?a='.$i.'&t=0'); // post upload
		curl_setopt($ch, CURLOPT_VERBOSE, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
		curl_setopt($ch, CURLOPT_POST, false);
		curl_setopt($ch, CURLOPT_COOKIEFILE, '/var/www/html/cookie.txt');
		curl_setopt($ch, CURLOPT_COOKIEJAR, '/var/www/html/cookie.txt');
		curl_setopt($ch, CURLOPT_TIMEOUT, 2);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
		$r = curl_exec($ch);
		$x = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if ($x != 200) {
			echo 'Not 200:';
			echo $r."\n";
			echo $x."\n";
		} else {
			$o = $i . ' ' . str_replace("\n", '', $r);
			
			file_put_contents(
				'./.miscellaneous/crossfit',
				"\n" . $o,
				FILE_APPEND|LOCKEX
			);
		}
	}
	exit;
}

//
// Ashley Fraser
//
if (isset($_GET['ashley_fraser_email__load_skips'])) {
	//
	$f = './.miscellaneous/ashley_fraser_email__load_skips';
	if (!file_exists($f)) {
		file_put_contents($f, $_GET['ashley_fraser_email__load_skips']."\n");
		chmod($f, 0777);
	} else {
		if ($_GET['ashley_fraser_email__load_skips'] != '') {
			file_put_contents($f, $_GET['ashley_fraser_email__load_skips']."\n", FILE_APPEND);
		} else {
			echo file_get_contents($f);
		}
	}
	exit;
}
/**
 * fs_load_skips_file
 */
if (isset($_REQUEST['fs_load_skips_file'])) {
	// {key:, [add:STRING, remove:{domain_full:STRING,domain_short:STRING}]}
	$opts = json_decode($_REQUEST['fs_load_skips_file']);
	// Filename
	$cln = preg_replace('/[^\d\w_]/', '', $opts->key);
	if (strlen($cln) < 1) {
		echo 'fs_load_skips_file: bad filename!';
		exit; // Quit.
	}
	$f = './.miscellaneous/'.$cln;
	//~ $f = './.miscellaneous/'.md5($opts->key);
	
	// Create file if necessary.
	// Fork if specified.
	if (!file_exists($f)) {
		if (isset($opts->fork) && $opts->fork !== false) {
			$cln = preg_replace('/[^\d\w_]/','',$opts->fork);
			if (strlen($cln) < 1) {
				echo 'fs_load_skips_file: bad filename (fork)!';
				exit; // Quit.
			}
			$fork = './.miscellaneous/'.$cln;
			if (!file_exists($fork)) {
				echo 'fs_load_skips_file: fork does not exist!';
				exit;
			}
			copy($fork, $f);
		} else {
			file_put_contents($f, '');
		}
		chmod($f, 0777);
	}
	// Load (Default)
	if (!isset($opts->remove) && !isset($opts->add)) {
		echo file_get_contents($f);
		exit;
	}
	// Add
	if (isset($opts->add)) {
		file_put_contents($f, $opts->add."\n", FILE_APPEND);
		echo file_get_contents($f); // echo...
		exit;
	}
	// Remove
	if (isset($opts->remove)) {
		$domain1 = trim($opts->remove->domain_full);
		$domain2 = trim($opts->remove->domain_short);
		$fp = file_get_contents($f);
		if ($domain1 && $domain1 != '') {
			$fp = str_replace("\n" . $domain1, '', $fp);
			if (strpos($fp, $domain1) !== false) {echo 'removed1';
				// Start or newline.
				// Not newline+, followed by preg_quote(short_domain).
				// Ended with newline or $.
				// Replace with newline or $
				$fp = preg_replace('/(^|\n)'.preg_quote($domain1, '/').'(\n|$)/i', '$2', $fp);
			}
		}
		if ($domain2 && $domain2 != '') {
			$fp = str_replace("\n" . $domain2, '', $fp);
			if (strpos($fp, $domain2) !== false) {echo 'removed2';echo $domain2;
				// Start or newline.
				// Not newline+, followed by preg_quote(short_domain).
				// Ended with newline or $.
				// Replace with newline or $
				$fp = preg_replace('/(^|\n)[^\n]+?'.preg_quote($domain2, '/').'([\n$])/i', '$2', $fp);
				
				// -
				// OLD VERSION, BEFORE FEB2018
				//$fp = preg_replace('/(^|\n)'.preg_quote($domain2, '/').'(\n|$)/i', '$2', $fp);
				// -
			}
		}
		file_put_contents($f, $fp);
		exit;
	}
}
//
// David Austin
//
if (isset($_GET['austin_load_skips'])) {
	//
	$f = './.miscellaneous/austin_load_skips';
	// ADD
	if (!isset($_GET['load_skips_removeMe'])) {
		if (!file_exists($f)) {
			file_put_contents($f, $_GET['austin_load_skips']."\n");
		} else {
			if ($_GET['austin_load_skips'] != '') {
				file_put_contents($f, $_GET['austin_load_skips']."\n", FILE_APPEND);
			} else {
				echo file_get_contents($f);
			}
		}
		exit;
	}
	// REMOVE
	// domain1, 2
	if (file_exists($f)) {
		$domain1 = $_GET['load_skips_domain_full'];
		$domain2 = $_GET['load_skips_domain_short'];
		$fp = file_get_contents($f);
		$fp = str_replace("\n" . $domain1, '', $fp);
		$fp = str_replace("\n" . $domain2, '', $fp);
		
		if (strpos($fp, $domain2) !== false) {
			// Start or newline.
			// Not newline+, followed by preg_quote(short_domain).
			// Ended with newline or $.
			// Replace with newline or $
			$fp = preg_replace('/(^|\n)[^\n]+?'.preg_quote($domain2, '/').'([\n$])/i', '$2', $fp);
			
			//$fp = preg_replace('/\n(https?:\/\/.+?|.+?).+?)\.'.preg_quote($domain2).'([\n$])/i', '$2', $fp);
		}
		file_put_contents($f, $fp);
	}
	exit;
}

//
//
//
if (isset($_GET['thomas_murphy_hid'])) {
	//
	$f = './.deleteme/thomas_murphy_hid';
	if (!file_exists($f)) {
		file_put_contents($f, $_GET['thomas_murphy_hid']."\n");
	} else {
		$x = file_get_contents($f);
		if (strpos($x, $_GET['thomas_murphy_hid']) !== false) {
			echo '1'; // exists!
		} else {
			if (isset($_GET['w'])) {
				file_put_contents($f, $_GET['thomas_murphy_hid']."\n", FILE_APPEND);
			}
		}
	}
	exit;
}
//
//
//
if (isset($_GET['thomas_murphy_crop'])) {
	//
	$f = str_replace('/', '', $_GET['thomas_murphy_crop']);
	$f = str_replace('"', '', $f);
	$f.= $_GET['thomas_murphy_crop_id'];
	
	// add .jpg
	$f.= '.jpg';
	
	$ftmp = './.deleteme/TMurphy-tmp-'.$f;
	
	$f = './.deleteme/TMurphy-'.$f.'-done';
	
	if (file_exists($ftmp) && !file_exists($f)) {
		// not ready
		//exit;
	} else if (file_exists($ftmp) && file_exists($f)) {
		// ready
		echo file_get_contents($f);
		exit;
	}
	
	// initialize
	if (!file_exists($ftmp)) { // new
		file_put_contents($ftmp, '');
		file_put_contents($ftmp, file_get_contents($_GET['thomas_murphy_crop']));
	} else {
		//xxsleep 2 and retry
	}
	if (file_get_contents($ftmp) == '') {
		//file_put_contents($ftmp, file_get_contents($_GET['thomas_murphy_crop']));
		exit;
	}
	
	// -trim
	if (!isset($_GET['thomas_murphy_crop_dx']))
		shell_exec('convert "'.$ftmp.'" -bordercolor White -border 40 -fuzz 40% -trim +repage -resize 150x150\! "'.$ftmp.'"');
	// or -crop
	else {//-crop 40x30+10+10  
		//$_GET['thomas_murphy_crop_dx'] = preg_replace('/[^]/', '', $_GET['thomas_murphy_crop_dx']);
		$i = $_GET['thomas_murphy_crop_dx'];
		if (preg_match('/^\d+x\d+\+\d+\+\d+$/', $i) === false)
			exit;
		shell_exec('convert "'.$ftmp.'" -crop '.$i.' +repage -resize 150x150\! "'.$ftmp.'"');
	}
	//shell_exec('convert "'.$ftmp.'" -bordercolor White -resize 500x500 -border 40 -fuzz 40% -trim +repage -resize 150x150 -crop "'.$ftmp.'"');
	
	// b64
	$x = file_get_contents($ftmp);
	$x = base64_encode($x);

	$post['image'] = $x;
	$post['type'] = 'base64';
	$post['title'] = 'IG';
	//$post['album'] = 'x36Nud0uWFiUU6E';
	
	$headers = array("Authorization: Client-ID 4745a0fd7bcfe8e");
	
	// upload
	$ch = curl_init('https://api.imgur.com/3/image');  // post upload
	curl_setopt($ch, CURLOPT_VERBOSE, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	curl_setopt($ch, CURLOPT_COOKIEFILE, '/var/www/html/cookie.txt');
	curl_setopt($ch, CURLOPT_COOKIEJAR, '/var/www/html/cookie.txt');
	curl_setopt($ch, CURLOPT_TIMEOUT, 6);
	$r = curl_exec($ch);
	$x = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	//curl_close($ch);ECHO $r.'a';
	//echo $r;
	if ($x != 200)
		echo $x;
	else
		echo $r;
	
	exit;
}

// HealthDetail
// Sep. 23, 2013
// 'healthDetail='+encodeURIComponent(state)+'&healthDetail_data='+rs,
if (isset($_POST['healthDetail']) && isset($_POST['healthDetail_data'])) {
	//
	$f = './.deleteme/'.$_POST['healthDetail'];
	file_put_contents($f, $_POST['healthDetail_data']);
	exit;
}
if (isset($_GET['healthDetail'])) {
	//
	$f = './.deleteme/'.$_GET['healthDetail'];
	if (file_exists($f))
		echo file_get_contents($f);
	exit;
}
// Jason Yu
// Facebook
if (isset($_POST['jason_yu'])) {
	// get from queue
	$f_= './.miscellaneous/jason_yu-queue';
	$f = file_get_contents($f_);
	$f = explode("\n", $f);
	$x = $f[0];
	unset($f[0]);
	file_put_contents($f_, trim(implode("\n", $f))); // saves queue
	
	//if (file_put_contents('./.deleteme/jason_yu-'.str_replace('/','',$_POST['jason_yu_url']), $_POST['jason_yu']))
	
	if (file_put_contents('./.deleteme/jason_yu-'.str_replace('/','',$x), $_POST['jason_yu']))
		echo '1';
	else
		echo '2';
	exit;
}
if (isset($_GET['jason_yu_getqueue'])) { //read queue
	$f_= './.miscellaneous/jason_yu-queue';
	$f = file_get_contents($f_);
	$f = explode("\n", $f);
	$x = $f[0];
	//unset($f[0]);
	//file_put_contents($f_, trim(implode("\n", $f)));
	echo $x; //x
	exit;
}
if (isset($_GET['jason_yu_writequeue'])) { //queue
	$f_= './.miscellaneous/jason_yu-queue';
	file_put_contents($f_, $_GET['jason_yu_writequeue']."\n", FILE_APPEND);
	exit;
}
if (isset($_GET['jason_yu_getfinal'])) { //queue
	$f_= './.deleteme/jason_yu-'.str_replace('/','',$_GET['jason_yu_getfinal']);
	if (file_exists($f_))
		echo file_get_contents($f_);
	exit;
}

// Matthew Taskrabbit
if (isset($_POST['matthew_taskrb'])) {
	if (file_put_contents('./.deleteme/matthewtr-'.$_POST['matthew_taskrb'], $_POST['matthew_taskrb_write']))
		echo '1';
	else
		echo '2';
	exit;
}
if (isset($_GET['matthew_taskrb_getqueue'])) { //queue
	$f_= './.miscellaneous/matthewtr-queue';
	$f = file_get_contents($f_);
	$f = explode("\n", $f);
	$x = $f[0];
	unset($f[0]);
	file_put_contents($f_, trim(implode("\n", $f)));
	echo $x; //xx|xx
	exit;
}
if (isset($_GET['matthew_taskrb_writequeue'])) { //queue
	$f_= './.miscellaneous/matthewtr-queue';
	file_put_contents($f_, $_GET['matthew_taskrb_writequeue'].'|'.$_GET['matthew_taskrb_writequeue_id']."\n", FILE_APPEND);
	exit;
}
if (isset($_GET['matthew_taskrb_getfinal'])) { //queue
	$f_= './.deleteme/matthewtr-'.$_GET['matthew_taskrb_getfinal'];
	if (file_exists($f_))
		echo file_get_contents($f_);
	exit;
}

if (isset($_POST['jason_droege'])) {
	file_put_contents('./.miscellaneous/droege', $_POST['jason_droege']."\n", FILE_APPEND);
	exit;
}

if (isset($_POST['alireza_mansoori'])) {
	//echo 'y';
	//print_r($_POST);
	//echo 
	if (!file_put_contents('./.miscellaneous/alireza', $_POST['alireza_mansoori']."\n", FILE_APPEND)) {
		echo 'x'.file_put_contents('./.miscellaneous/alireza', $_POST['alireza_mansoori']."\n", FILE_APPEND);
	}
	
	exit;
}

//
// Aaron Zwas
//
if (isset($_POST['aaron_zwas_hotelsDaemon_save'])) {
	$f = './.deleteme/aaron_zwas_'.str_replace('/', '', $_POST['aaron_zwas_hotelsDaemon_Id']);
	if (file_put_contents($f, $_POST['aaron_zwas_hotelsDaemon_save'], LOCK_EX)) {
		echo '1';
	} else
		echo 'x';
	exit;
}
if (isset($_GET['aaron_zwas_hotelsDaemon_getQueue'])) {
	$f = './.miscellaneous/aaron_zwas_hotelsDaemon_queue';
	if (file_exists($f)) {
		$fp = file_get_contents($f);
		if (trim($fp) != '') {
			
			preg_match('/^[^\n]+\n/', $fp, $m);

			if (!empty($m[0])) {
				
				$z = $m[0];
				$fp = preg_replace('/^[^\n]+\n/', '', $fp);
				
				file_put_contents($f, $fp, LOCK_EX);
				
				echo trim($z); // city,state
			}
		}
	}
	exit;
}
if (isset($_GET['aaron_zwas_hotelsDaemon_writeToQueue'])) {
	$f = './.miscellaneous/aaron_zwas_hotelsDaemon_queue';
	$fp= './.deleteme/aaron_zwas_'.str_replace('/', '', $_GET['aaron_zwas_hotelsDaemon_writeToQueue']);
	
	if (file_exists($fp)) {
		// do nothing
	}
	else if (!file_put_contents($f, $_GET['aaron_zwas_hotelsDaemon_writeToQueue']."\n", LOCK_EX)) {
		echo 'x';
	}
	exit; // blank == ok
}
if (isset($_GET['aaron_zwas_hotelsDaemon_readComplete'])) {
	$f = './.deleteme/aaron_zwas_'.str_replace('/', '', $_GET['aaron_zwas_hotelsDaemon_readComplete']);
	if (file_exists($f)) {
		$f = file_get_contents($f);
		$f = json_decode($f);
		if (!$f[$_GET['aaron_zwas_hotelsDaemon_readComplete_num']]) {
			echo 'x';
		} else
			echo $f[$_GET['aaron_zwas_hotelsDaemon_readComplete_num']];
		//echo file_get_contents($f);
	}
	exit;
}
//
// Aaron Zwas
//
if (isset($_GET['aaron_zwas'])) {
	$file = './.deleteme/'.str_replace('/', '', $_GET['u']);
	if (file_exists($file)) {
		echo file_get_contents($file);
	}
	exit;
}
if (isset($_POST['aaron_zwas'])) {
	file_put_contents('./.deleteme/'.str_replace('/', '', $_POST['u']), $_POST['v']);
	exit;
}

if (isset($_POST['johnroberts'])) {
	file_put_contents('./.deleteme/'.str_replace('/', '', $_POST['u']), $_POST['v']);
	exit;
}

// Matt
// Aug. 16, 2013
if (isset($_GET['Matt_getqueue'])) {
	
	// Try to avoid pulling two items from the queue by setting
	// a unique id.
	// Does id exist?
	//$file = './.deleteme/Matt_getqueue_id'.$_GET['Matt_getqueue'];
	//if (file_exists($file))
	//	exit;
	//else
	//	file_put_contents($file, '');
		
	//
	$file = './.miscellaneous/Matt_queue';
	if (!file_exists($file)) {
		file_put_contents($file, '');
	}
	
	$f = file_get_contents($file);
	$f = trim($f);
	
	if ($f != '') {
		$f = explode("\n", $f);
		if (count($f) > 0) {
			$x = array_shift($f); // first element
			$f = implode("\n", $f) . "\n"; // add \n for elements still in queue file
			file_put_contents($file, $f);
			echo trim($x);
		}
	}
	exit;
}

if (isset($_GET['Matt_appendqueue'])) {
	//
	$file = './.miscellaneous/Matt_queue';
	$f = file_get_contents($file);
	if (strpos($f, $_GET['Matt_appendqueue']) === false) {
		file_put_contents($file,$_GET['Matt_appendqueue']."\n", FILE_APPEND);
	}
	exit;
}
if (isset($_GET['Matt_completed']) && isset($_GET['Matt_writecomplete_id'])) { // final read
	//
	$file = './.deleteme/'.$_GET['Matt_writecomplete_id'];
	if (file_exists($file)) {
		$f = file_get_contents($file);
		if (trim($f) != '') {
			echo $f;
		}
	}
	exit;
}
if (isset($_GET['Matt_writecomplete']) && isset($_GET['Matt_writecomplete_id'])) { //final write
	//
	$file = './.deleteme/'.$_GET['Matt_writecomplete_id'];
	file_put_contents($file,$_GET['Matt_writecomplete']);
	exit;
}

//Matt_Tarjeft
//echo 'tarjet ran ';
if (isset($_GET['Matt_Tarjeft_getqueue'])) {
	
	// Try to avoid pulling two items from the queue by setting
	// a unique id.
	// Does id exist?
	//$file = './.deleteme/Matt_getqueue_id'.$_GET['Matt_getqueue'];
	//if (file_exists($file))
	//	exit;
	//else
	//	file_put_contents($file, '');
		
	//
	$file = './.miscellaneous/Matt_Tarjeft_queue';
	if (!file_exists($file)) {
		file_put_contents($file, '');
	}
	
	$f = file_get_contents($file);
	$f = trim($f);
	
	if ($f != '') {
		$f = explode("\n", $f);
		if (count($f) > 0) {
			$x = array_shift($f); // first element
			$f = implode("\n", $f) . "\n"; // add \n for elements still in queue file
			file_put_contents($file, $f);
			echo trim($x);
		}
	}
	exit;
}
if (isset($_GET['Matt_Tarjeft_appendqueue'])) {
	//
	$file = './.miscellaneous/Matt_Tarjeft_queue';
	$f = file_get_contents($file);
	if (strpos($f, $_GET['Matt_Tarjeft_appendqueue']) === false) {
		file_put_contents($file,$_GET['Matt_Tarjeft_appendqueue']."\n", FILE_APPEND);
	}
	exit;
}
if (isset($_GET['Matt_Tarjeft_completed']) && isset($_GET['Matt_Tarjeft_writecomplete_id'])) { // final read
	//
	$file = './.deleteme/'.$_GET['Matt_Tarjeft_writecomplete_id'];
	if (file_exists($file)) {
		$f = file_get_contents($file);
		if (trim($f) != '') {
			echo $f;
		}
	}
	exit;
}
if (isset($_GET['Matt_Tarjeft_writecomplete']) && isset($_GET['Matt_Tarjeft_writecomplete_id'])) { //final write
	//
	$file = './.deleteme/'.$_GET['Matt_Tarjeft_writecomplete_id'];
	file_put_contents($file,$_GET['Matt_Tarjeft_writecomplete']);
	exit;
}

if (isset($_GET['x'])) {
	//
	print_r($_SERVER);
	exit;
}

if (isset($_GET['twitter_api_usersearch'])) {
	$file = './.deleteme/'.str_replace('\/','',$_GET['twitter_api_usersearch']);
	if (file_exists($file)) {
		$x = file_get_contents($file);
		if (trim($x) != '') {
			echo $x;
			exit;
		}
	}
	// e.g. yelp
	$consumer_key = '_redacted_';
	$consumer_secret = '_redacted_';
	$o = new OAuth($consumer_key, $consumer_secret);
	$o->disableSSLChecks();
	$o->setToken('_redacted_', '_redacted_');
	$url = 'https://api.twitter.com/1.1/users/search.json?'.$_GET['twitter_api_usersearch'].'&page=1&count=3';
	try {
		if ($o->fetch($url)) {
			file_put_contents($file, $o->getLastResponse());
			chmod($file, 0777);
			echo $o->getLastResponse();
		} else {
		}
	} catch( OAuthException $e ) {
		print_r($e);
	}
	exit;
}
if (isset($_GET['twitter_oauth_user'])) {
	$opts = json_decode($_GET['twitter_oauth_user']);
	$file = './.cached/'.md5($opts->user);
	if (file_exists($file)) {
		$x = file_get_contents($file);
		if (trim($x) != '' && strpos($x, 'Rate limit exceeded') === false) {
			echo $x;
			exit;
		}
	}
	// e.g. yelp
	$consumer_keys = [
		'_redacted_',
		'_redacted_',
		'_redacted_'
	];
	$consumer_secrets = [
		'_redacted_',
		'_redacted_',
		'_redacted_'
	];
	$tokens = [
		['16570399-_redacted_', '_redacted_'],   // #1
		['16570399-_redacted_', '_redacted_'], // #2
		['16570399-_redacted_', '_redacted_']  // #3
	];
	$n = rand(0, 2); // Between 0 and 2 inclusive.
	//~ $n = 1;
	$o = new OAuth($consumer_keys[$n], $consumer_secrets[$n]);
	//~ $o->disableSSLChecks();
	$o->setToken($tokens[$n][0], $tokens[$n][1]);
    $o->ssl_verifypeer = TRUE;
    //~ $connection->content_type = 'application/x-www-form-urlencoded';
    //~ $ucontent = $connection->get('users/show', array('screen_name' => $twit_screen_name));
	$url = 'https://api.twitter.com/1.1/users/show.json?screen_name='.urlencode($opts->user);
	//~ $url = 'https://api.twitter.com/1.1/users/show?screen_name='.urlencode($opts->user);
	try {
		if ($o->fetch($url)) {
			file_put_contents($file, $o->getLastResponse());
			chmod($file, 0777);
			echo $o->getLastResponse();
		} else {
		}
	} catch(OAuthException $e) {
		print_r($e);
	}
	exit;
}

if (isset($_GET['john_c_triggs'])) {
	//curl 'https://www.mgo.com/security/explore/content/1MV73f073b300dbda3adde969eeea64bd56/offers' -H 'Cookie: MGO_analytics_sessionId=6A02169D-1DEA-4FB1-9A01-748042668D5B; has_js=1; __utma=240625183.271282405.1380550821.1380550821.1380550821.1; __utmb=240625183.20.9.1380552000824; __utmc=240625183; __utmz=240625183.1380550821.1.1.utmcsr=my.hostmysite.com|utmccn=(referral)|utmcmd=referral|utmcct=/ssl/30//etrizzle.net/movies/tasks/MGoMoviePrices.cfm; utag_main=_st:1380554618087$ses_id:1380550844855%3Bexp-session' -H 'Accept-Encoding: gzip,deflate,sdch' -H 'Host: www.mgo.com' -H 'Accept-Language: en-US,en;q=0.8' -H 'Authorization: Bearer 0302a746607242f9be7904ce870f5421' -H 'Content-Type: application/json' -H 'Accept: application/json, text/javascript, */*; q=0.01' -H 'Referer: https://www.mgo.com/client/' -H 'X-Requested-With: XMLHttpRequest' -H 'Connection: keep-alive' -H 'User-Agent: Mozilla/5.0 (X11; Linux i686) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/30.0.1599.66 Safari/537.36' --compressed
	$v = "curl 'https://www.mgo.com/security/explore/content/1MV73f073b300dbda3adde969eeea64bd56/offers' -H 'Cookie: MGO_analytics_sessionId=6A02169D-1DEA-4FB1-9A01-748042668D5B; has_js=1; utag_main=_st:1380634794327$ses_id:1380633810776%3Bexp-session; __utma=240625183.271282405.1380550821.1380550821.1380632969.2; __utmb=240625183.6.9.1380632994837; __utmc=240625183; __utmz=240625183.1380550821.1.1.utmcsr=my.hostmysite.com|utmccn=(referral)|utmcmd=referral|utmcct=/ssl/30//etrizzle.net/movies/tasks/MGoMoviePrices.cfm' -H 'Accept-Encoding: gzip,deflate,sdch' -H 'Host: www.mgo.com' -H 'Accept-Language: en-US,en;q=0.8' -H 'Authorization: Bearer 7e01522f5b3742aca9e7a10fdec07ef9' -H 'Content-Type: application/json' -H 'Accept: application/json, text/javascript, */*; q=0.01' -H 'Referer: https://www.mgo.com/client/' -H 'X-Requested-With: XMLHttpRequest' -H 'Connection: keep-alive' -H 'User-Agent: Mozilla/5.0 (X11; Linux i686) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/30.0.1599.66 Safari/537.36' --compressed";
	$v = preg_replace('/\/content\/[^\/]+/', '/content/'.$_GET['john_c_triggs'], $v);
	echo shell_exec($v);
	exit;
	echo shell_exec("curl 'https://www.mgo.com/security/explore/content/".$_GET['john_c_triggs']."/offers' -H 'Cookie: MGO_analytics_sessionId=6A02169D-1DEA-4FB1-9A01-748042668D5B; has_js=1; __utma=240625183.271282405.1380550821.1380550821.1380550821.1; __utmb=240625183.45.9.1380555086555; __utmc=240625183; __utmz=240625183.1380550821.1.1.utmcsr=my.hostmysite.com|utmccn=(referral)|utmcmd=referral|utmcct=/ssl/30//etrizzle.net/movies/tasks/MGoMoviePrices.cfm; utag_main=_st:1380556886709$ses_id:1380550844855%3Bexp-session' -H 'Accept-Encoding: gzip,deflate,sdch' -H 'Host: www.mgo.com' -H 'Accept-Language: en-US,en;q=0.8' -H 'Authorization: Bearer 18de8d91759c4e7e961d4c493c82de9b' -H 'Content-Type: application/json' -H 'Accept: application/json, text/javascript, */*; q=0.01' -H 'Cache-Control: max-age=0' -H 'X-Requested-With: XMLHttpRequest' -H 'Connection: keep-alive' -H 'Referer: https://www.mgo.com/client/' -H 'User-Agent: Mozilla/5.0 (X11; Linux i686) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/30.0.1599.66 Safari/537.36' --compressed");
	exit;
	
	$header = array();
	//$header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,"; 
	//$header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5"; 
	
	$header[] = 'Cookie: MGO_analytics_sessionId=6A02169D-1DEA-4FB1-9A01-748042668D5B; has_js=1; __utma=240625183.271282405.1380550821.1380550821.1380550821.1; __utmb=240625183.20.9.1380552000824; __utmc=240625183; __utmz=240625183.1380550821.1.1.utmcsr=my.hostmysite.com|utmccn=(referral)|utmcmd=referral|utmcct=/ssl/30//etrizzle.net/movies/tasks/MGoMoviePrices.cfm; utag_main=_st:1380554618087$ses_id:1380550844855%3Bexp-session';
	$header[] = 'Accept-Encoding: gzip,deflate,sdch';
	$header[] = 'Host: www.mgo.com';
	$header[] = 'Accept-Language: en-US,en;q=0.8';
	$header[] = 'Authorization: Bearer 0302a746607242f9be7904ce870f5421';
	$header[] = 'Content-Type: application/json';
	$header[] = 'Accept: application/json, text/javascript, */*; q=0.01';
	$header[] = 'Referer: https://www.mgo.com/client/';
	$header[] = 'X-Requested-With: XMLHttpRequest';
	$header[] = 'Connection: keep-alive';
	$header[] = 'User-Agent: Mozilla/5.0 (X11; Linux i686) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/30.0.1599.66 Safari/537.36';
		
	$ch = curl_init('https://www.mgo.com/security/explore/content/'.$_GET['john_c_triggs'].'/offers');
	
	//curl_setopt($ch, CURLOPT_HEADER, $header);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_VERBOSE, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
	curl_setopt($ch, CURLOPT_POST, false);
	curl_setopt($ch, CURLOPT_COOKIEFILE, '/var/www/html/cookie.txt');
	curl_setopt($ch, CURLOPT_COOKIEJAR, '/var/www/html/cookie.txt');
	curl_setopt($ch, CURLOPT_TIMEOUT, 6);
	$r = curl_exec($ch);
	$x = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);ECHO $r.'a';
	if (strlen($r) > 10 || $x != 200)
		echo $r;
	exit;
}

if (isset($_GET['kim_harrison'])) {
	$ch = curl_init($_GET['kim_harrison']);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_VERBOSE, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
	curl_setopt($ch, CURLOPT_POST, false);
	curl_setopt($ch, CURLOPT_COOKIEFILE, '/var/www/html/cookie.txt');
	curl_setopt($ch, CURLOPT_COOKIEJAR, '/var/www/html/cookie.txt');
	curl_setopt($ch, CURLOPT_TIMEOUT, 6);
	$r = curl_exec($ch);
	$x = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	if (str_len($r) > 10 || $x != 200)
		echo str_len($r) . ' ' . $x;
	exit;
}

if (isset($_GET['huiming_gu_cache_google']) && !isset($_GET['huiming_gu_cache_google_write'])) { //read
	$f = './.cached/'.$_GET['huiming_gu_cache_google'];
	if (file_exists($f)) {
		$f = file_get_contents($f);
		if ($f != '')
			echo $f;
	}
	exit;
}
if (isset($_GET['huiming_gu_cache_google']) &&  isset($_GET['huiming_gu_cache_google_write'])) { //read
	$f = './.cached/'.$_GET['huiming_gu_cache_google'];
	file_put_contents($f, $_GET['huiming_gu_cache_google_write']);
	chmod($f, 0777);
	exit;
}
		
/**
 * 
 * Five Star
 * Linda Dowling (Aug. 22, 2013, oDesk)
 * 
 */
/* level 0,1,2 search page links
out = [];
var x = document.querySelectorAll('li.level2');
for (var i in x) {
	if (x[i] == 146) break;
	y = x[i].getElementsByTagName('a')[0];
	out.push(y.href);
}
console.log(out.join('","'));
 */
if (isset($argv[1])) {
	if ($argv[1] == 'five_star') {
		//name
		//lic. id
		//comp name
		//comp city,state,zip
		//active IAR
		//lic. disclosure
		function fivestar_write($x) {
			global $file;
			
			$out = '';
			$out.= '"'.str_replace('"','""',$x->name).'",';
			$out.= '"'.str_replace('"','""',$x->crd).'",';
			
			if ($x->compType == 2) {
				preg_match('/^<b>([^<]+)<\/b>/', $x->comp, $y);
				if (!empty($y[1]))
					$out.= '"'.str_replace('"','""',html_entity_decode($y[1])).'",';
				preg_match('/>([^,>]+), ([A-Z]{2})&nbsp;&nbsp;([\d\-]+)</', $x->comp, $y);
				if (!empty($y[1])) {
					$out.= '"'.str_replace('"','""',html_entity_decode($y[1])).'",';
					$out.= '"'.str_replace('"','""',html_entity_decode($y[2])).'",';
					$out.= '"'.str_replace('"','""',html_entity_decode($y[3])).'",';
				} else { // missing zip
					preg_match('/>([^,>]+), ([A-Z]{2})</', $x->comp, $y);
					if (!empty($y[1])) {
						$out.= '"'.str_replace('"','""',html_entity_decode($y[1])).'",';
						$out.= '"'.str_replace('"','""',html_entity_decode($y[2])).'",';
						$out.= '"N/A",';
					} else {
						echo $file.' 2';
						print_r($x);
						exit;
					}
				}
			} else if ($x->compType === 1) { //1
				preg_match('/^<font class="HighlightedText">([^<]+)<\/font>/', $x->comp, $y);
				if (!empty($y[1]))
					$out.= '"'.str_replace('"','""',html_entity_decode($y[1])).'",';
				preg_match('/>([^,>]+), ([A-Z]{2})&nbsp;&nbsp;([\d\-]+)</', $x->comp, $y);
				if (!empty($y[1])) {
					$out.= '"'.str_replace('"','""',html_entity_decode($y[1])).'",';
					$out.= '"'.str_replace('"','""',html_entity_decode($y[2])).'",';
					$out.= '"'.str_replace('"','""',html_entity_decode($y[3])).'",';
				} else {
					echo $file.' 1';
					print_r($x);
					exit;
				}
			} else { // no comp
				$out.= '"N/A",';
				$out.= '"N/A",';
				$out.= '"N/A",';
				$out.= '"N/A",';
			}
			
			$out .= 'Not Active,';
			$out .= $x->discl."\n";
			
			//print_r($out);
			//print_r($x);
			//exit;
			file_put_contents('/var/www/html/mturk/five_star.csv', $out, FILE_APPEND);
		}
		
		file_put_contents('./five_star.csv', ''); //empty
		$files = './FiveStarProfessional/s';
		chdir($files);
		$files = scandir('./');
		foreach ($files as $k => $file) {
			if ($file == '.' || $file == '..') continue;
			if (preg_match('/^\d+$/', $file) === false) continue;
			//echo $file;
			echo '.';
			$x = file_get_contents($file);
			$x = json_decode($x);
			if  ($x->runIsComplete && $x->hasResult) {
				//if  (
				//	$x->lic_type == 'FINRA' // !IAR
				//	)
				//{
				//	fivestar_write($x);
				//	echo '+';
				//}
				//else if
				if
					( // not active SEC, active FINRA
						// !INCORRECT> not active SEC, active FINRA
						// XX-CORRECT> not active SEC, not active FINRA
						// XX-CORRECT> active SEC, not active FINRA
						// XX-CORRECT> active SEC, active FINRA
					 $x->not_active_SEC && // !IAR
					!$x->not_active_FINRA // !IAR
					)
				{
					fivestar_write($x);
					echo '+';
				}
			}
			//print_r($x);
			//exit;
		}
		exit;
	}
	if ($argv[1] == 'linda_dowling') {
		
		// curl download
		
		// level 3 / 2 / 1, search pages
		$cats = file_get_contents('./Linda_Dowling/naturalpartners-level3.json');
		$cats = explode(',', $cats);
		
		$x = file_get_contents('./Linda_Dowling/naturalpartners-level2.json');
		$x = explode(',', $x);
		
		$x2= ["http://beta.naturalpartners.com/nutritional-supplements.html","http://beta.naturalpartners.com/vitamins-minerals.html","http://beta.naturalpartners.com/herbs.html","http://beta.naturalpartners.com/homeopathy.html","http://beta.naturalpartners.com/personal-care.html","http://beta.naturalpartners.com/animal-health.html"];
		
		$cats = array_merge($cats, $x, $x2);
		
		echo count($cats)."\n";
		
		$complete = 0;//0,1
		$start = 2;
		$start = 111;
		$start = 0;
		
		// into CSV, only unique
		$csv_unique = [];
		
		// test
		$test = [];
		$test2= [];
		$count_max_cat = 0;
		
		foreach ($cats as $k => $cat) {
			
			if ($complete < $start) {
				$complete++;
				continue;
			}// else $complete++;
			
			$cat = str_replace('"', '', $cat);
			
			echo "\n".'starting cat '.$cat.' '.$complete."\n";
			
			$complete++;
			
			$end_of_cat = false;
			$page = 1;
			
			while (!$end_of_cat) {
				echo "\n".'page '.$page."\n";
				
				$u = $cat.'?limit=36&p='.$page;
				$fn = str_replace('/', ',', $u);
				$fn = './Linda_Dowling/pages_indexes/'.$fn;
				
				if (!file_exists($fn)) {
					$ch = curl_init($u);
					curl_setopt($ch, CURLOPT_HEADER, 0);
					curl_setopt($ch, CURLOPT_VERBOSE, 0);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
					curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
					curl_setopt($ch, CURLOPT_POST, false);
					curl_setopt($ch, CURLOPT_COOKIEFILE, '/var/www/html/cookie.txt');
					curl_setopt($ch, CURLOPT_COOKIEJAR, '/var/www/html/cookie.txt');
					curl_setopt($ch, CURLOPT_TIMEOUT, 40);
					$r = curl_exec($ch);
					$x = curl_getinfo($ch, CURLINFO_HTTP_CODE);
					curl_close($ch);
					
					if ($x != 200) {
						echo 'status1-'.$x;
						exit;
					}
					
					// save search index page
					if (!file_put_contents($fn, $r)) {
						echo 'save1-';
						exit;
					}
				} else
					$r = file_get_contents($fn);
				
				/*
				 * Determine full catg. name
				 * this is rather than from URL, cat-1-url/cat-2-url/cat-3-url
				 */
				preg_match_all('/<li class="category\d+">[\r\n\t\s]+<[^>]+>([^<]+)<\/(a|strong)>/', $r, $j);
				if (empty($j[1])) {
					//echo $i;
					echo 'x';
					exit;
				}
				$pcat = [];
				foreach ($j[1] as $k3 => $p) {
					$p = html_entity_decode($p); // remove &amp;
					$p = trim($p);
					array_push($pcat, $p);
				}
				$pcat = implode('|', $pcat);//*/
				
				// download sub-pages
				preg_match_all('/<h2 class="product-name"><a href="([^"]+)"/', $r, $m);
				if (!empty($m[1])) {
					foreach ($m[1] as $k2 => $new) {
						// does file exist?
						$fn = './Linda_Dowling/pages/'.basename($new);
						
						if (!file_exists($fn)) { // item is new
							
							echo ' '.basename($new);
							
							$ch = curl_init($new);
							curl_setopt($ch, CURLOPT_HEADER, 0);
							curl_setopt($ch, CURLOPT_VERBOSE, 0);
							curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
							curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
							curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
							curl_setopt($ch, CURLOPT_POST, false);
							curl_setopt($ch, CURLOPT_COOKIEFILE, '/var/www/html/cookie.txt');
							curl_setopt($ch, CURLOPT_COOKIEJAR, '/var/www/html/cookie.txt');
							curl_setopt($ch, CURLOPT_TIMEOUT, 40);
							$r2 = curl_exec($ch);
							$x = curl_getinfo($ch, CURLINFO_HTTP_CODE);
							curl_close($ch);
							
							if ($x != 200) {
								echo 'status2-'.$x;
								exit;
							}
							
							/*// item # and item cats
							preg_match('/Item #: <strong>([^<]+)<\/strong>/i', $r2, $i);
							if (!empty($i)) {
								$i = trim($i[1]);//trim!
								//$i = $i[1];
							} else {
								echo 'item';
								exit;
							}
							
							// $i is item#
							// /cats/ \n
							// $cat
							$c = $cat;
							$c = str_replace('http://beta.naturalpartners.com/', '', $c);
							$c = str_replace('.html', '', $c);
							if (!file_put_contents('./Linda_Dowling/categories/'.$i, $c)) {
								echo 'save2';
								exit;
							}*/
							
							if (!file_put_contents($fn, $r2)) { // save!
								echo 'save3';
								exit;
							}
							
							echo '-'; // complete
							exit; // all downloaded!
						} else { // item exists
							
							//
							//
							// Item exists
							// Do the work for CSV here
							//
							// Duplicates are productX.html, rather than item#, productX.html->Item#
							// DUP: supplement/itemX.html, natural/itemX.html
							// Non-DUP: supplement/itemX.html#ItemZ, natural/itemY.html#ItemZ
							// 
							
							/*// WRITE CATEGORIES (limited)
							 * item 300-c.html #T020
							 * item 301-c.html #T020 (same)
							 * If 3rd level exists, then 3rd
							 * Else if 2nd level exists, then 2nd
							 * Else if 1st level exists, then 1st
							 * 
							$r2 = file_get_contents($fn);
							
							preg_match('/Item #: <strong>([^<]+)<\/strong>/i', $r2, $i);
							if (!empty($i)) {
								$i = trim($i[1]);
							} else {
								echo 'item2';
								exit;
							}
							preg_match_all('/<li class="category\d+">[\r\n\t\s]+<a href=[^>]+>([^<]+)<\/a>/', $r2, $j);
							
							if (empty($j[1])) {
								echo $i;
								exit;
							}
							$pcat = [];
							foreach ($j[1] as $k3 => $p) {
								$p = html_entity_decode($p); // remove &amp;
								$p = trim($p);
								array_push($pcat, $p);
							}
							$p = implode('|', $pcat);
							
							$cf = './Linda_Dowling/categories/'.$i;
							if (!file_exists($cf)) { // new
								file_put_contents($cf, $p."\n");
							} else { // append // already in file?
								$x = file_get_contents($cf);
								if (strpos($x, $p."\n") === false)
									file_put_contents($cf, $p."\n", FILE_APPEND);
							}
							continue;*/
							
							/*
							 * Build cats (full)
							 * By Item #
							 * //OLD//By product URL, X.html
							 */ 
							/*$r2 = file_get_contents($fn);
							preg_match('/Item #: <strong>([^<]+)<\/strong>/i', $r2, $i);
							if (!empty($i)) {
								$i = trim($i[1]);
							} else {
								echo 'item2';
								exit;
							}
							$cf = './Linda_Dowling/categories/'.$i;
							if (!file_exists($cf)) { // new
								if (!file_put_contents($cf, $pcat."\n")) {
									echo 'save4';
									exit;
								}
							} else { // add
								$x = file_get_contents($cf);
								if (strpos($x, $p."\n") === false) {
									if (!file_put_contents($cf, $pcat."\n", FILE_APPEND)) {
										echo 'save5';
										exit;
									}
								}
							}
							continue;//*/
							/*//OLD-Dup by URL//$i = basename($new, '.html'); // remove .html*/
							
							/*
							 * DUP TEST
							 * ITEM # + Product Name IS UNIQUE
							 * ITEM # + Brand Name IS UNIQUE
							 */
							/*// Product name
							preg_match('/<div class="product-name">[\r\n\t\s]+<h2>([^<]+)/i', $r2, $i);
							if (!empty($i)) {
								$i = $i[1];
								$i = html_entity_decode($i);
								$i = trim($i);
								$i1 = str_replace('"', '""', $i);
							} else {
							}
							// Item #
							preg_match('/Item #: <strong>([^<]+)<\/strong>/i', $r2, $i);
							if (!empty($i)) {
								//$item = trim($i[1]); // *** item #
								//$i = trim($item);
								$i = $i[1];
								$i = html_entity_decode($i);
								$i = trim($i);
								$i2 = str_replace('"', '""', $i);
							} else {
							}
							// Brand name
							preg_match('/<div class="brand_name">([^<]+)<\/div>/i', $r2, $i);
							if (!empty($i)) {
								$i = $i[1];
								$i = html_entity_decode($i);
								$i = trim($i);
								$i3 = str_replace('"', '""', $i3);
							}
							if (isset($test[$i2])) {
								if ($test[$i2] != $i1) {
									echo $test[$i2]."\n";
									echo $i1."\n";
									echo $i2."\n";
									exit;
								}
							} else {
								$test[$i2] = $i1;
							}
							if (isset($test2[$i2])) {
								if ($test2[$i2] != $i3) {
									echo $test2[$i2]."\n";
									echo $i3."\n";
									echo $i2."\n";
									exit;
								}
							} else {
								$test2[$i2]= $i3;
							}
							continue;*/
							/*//COUNT MAX. CATS
							 * count_max_cats = 13
							$r2 = file_get_contents($fn);
							// Item #
							preg_match('/Item #: <strong>([^<]+)<\/strong>/i', $r2, $i);
							if (!empty($i[1])) {
								$item = trim($i[1]); // *** item #
							} else {
								echo 'x';
								exit;
							}
							// $cats
							$cf = './Linda_Dowling/categories/'.$item;
							if (file_exists($cf)) { // new
								$i = file_get_contents($cf);
								$i = trim($i);
								$num = (substr_count($i, "\n")*1) + 1;
							} else { //empty
								echo $cf;
								echo 'ct';
								exit;
							}
							if ($num > $count_max_cat) {
								$count_max_cat = $num;
								echo $count_max_cat."\n";
							}
							continue;*/
							
							/*
							 * The rest
							 * 
							 * ** SKIP DUPS
							 */
							
							$r2 = file_get_contents($fn);
							$w = '';
							
							// Item #
							preg_match('/Item #: <strong>([^<]+)<\/strong>/i', $r2, $i);
							if (!empty($i[1])) {
								$item = trim($i[1]); // *** item #
							} else {
								echo 'x';
								exit;
							}
							//OLD//$item = basename($new, '.html'); // remove .html
							
							// Product name
							preg_match('/<div class="product-name">[\r\n\t\s]+<h2>([^<]+)/i', $r2, $i);
							if (!empty($i)) {
								$i = $i[1];
								$i = html_entity_decode($i);
								$i = trim($i);
								$i = str_replace('"', '""', $i);
								$w.= '"'.$i.'",';
							} else {
								echo 'nm';
								exit;
							}
							
							// Item #
							$i = html_entity_decode($item);
							$i = trim($i);
							$i = str_replace('"', '""', $i);
							$w.= '"'.$i.'",';
							/*preg_match('/Item #: <strong>([^<]+)<\/strong>/i', $r2, $i);
							if (!empty($i)) {
								//$item = trim($i[1]); // *** item #
								//$i = trim($item);
								$i = $i[1];
								$i = html_entity_decode($i);
								$i = trim($i);
								$i = str_replace('"', '""', $i);
								$w.= '"'.$i.'",';
							} else {
								echo 'it';
								exit;
							}*/
							
							// ORIGINAL URL ( *** FULL)
							$i = str_replace('"', '""', $new);
							$w.= '"'.$i.'",';
							
							// Brand name
							preg_match('/<div class="brand_name">([^<]+)<\/div>/i', $r2, $i);
							if (!empty($i)) {
								$i = $i[1];
								$i = html_entity_decode($i);
								$i = trim($i);
								$i = str_replace('"', '""', $i);
								$w.= '"'.$i.'",';
							} else { //blank
								$w.= ',';
								//echo 'it';
								//exit;
							}
							
							// $cats
							// count_max_cats = 13
							$cf = './Linda_Dowling/categories/'.$item;
							if (file_exists($cf)) { // new
								$i = file_get_contents($cf);
								$i = trim($i);
								$i = explode("\n", $i);
								foreach ($i as $key_i => $val_i) {
									$val_i = str_replace('"', '""', $val_i);
									$w.= '"'.$val_i.'",';
								}
								if (count($i) > 13) {
									echo '13';
									return;
								}
								$pad = str_repeat(',', 13 - count($i)); // 13 - 13 = 0
								$w .= $pad;
								//DEV//if ($i == '') {
								//	echo $item;
								//	echo 'blank';
								//	exit;
								//}
							} else { //empty
								echo $cf;
								echo 'ct';
								exit;
							}
							
							// Description
							preg_match('/<p>Description:<\/p>[\r\n\t\s]+<div class="std">((.|\s)*?(?=<\/div>[\r\n]+                    <\/div>[\r\n]+|$)|$)/i', $r2, $i);
							//preg_match('/<p>Description:<\/p>[\r\n\t\s]+<div class="std">(.+?)<\/div>[\r\n]+                    <\/div>[\r\n]+/i', $r2, $i);
							if (!empty($i)) {
								$i = trim($i[1]);
								
								//$i = preg_replace('/&gt;/i', '>', $i);
								//$i = preg_replace('/&lt;/i', '<', $i);
								$i = html_entity_decode($i); // remove &amp;
								
								$i = preg_replace('/(<p[^>]{0,}>|<\/p>|<br[^>]{0,}>|<li[^>]{0,}>|<div[^>]{0,}>|<\/?ol>)/i', "\n", $i);
								
								$i = preg_replace('/(<h\d+[^>]{0,}>)/i', "\n", $i);
								$i = preg_replace('/(<\/h\d+>)/i', '', $i);
								
								$i = preg_replace('/(<table[^>]{0,}>|<tr[^>]{0,}>)/i', "\n", $i);
								$i = preg_replace('/(<\/table>|<\/?tbody[^>]{0,}>|<\/?th[^>]{0,}>|<\/tr>|<\/?td[^>]{0,}>)/i', '', $i);
								
								//arctic-d-cod-liver-oil-lemon-16oz.html
								//<<iif(!empty(cookie.get("CUSTNO")) [... ...] >>
								$i = preg_replace('/<<((.|\s)*?(?=>>|$)|$)>>/i', '', $i);
								
								//citramesia-90c.html
								$i = preg_replace('/<\?xml[^>]{0,}>/i', '', $i);
								$i = preg_replace('/<\/?o:p>/i', '', $i);
								
								$i = preg_replace('/^<IIF[^<]+<embed \/>/i', '', $i);
								
								//$i = preg_replace('/(<\/?em>|<\/?strong>|<\/?span>|<span[^>]+>|<\/?b( [^>]{0,}|)>|<\/?i>|<\/?ul[^>]{0,}>|<\/li>|<\/?font[^>]{0,}>|<\/?u>|<\/div>|<\/?noedit>|<\/a>|<a [^>]+>|<\/?sub>|<\/?sup>)/i', '', $i);
								
								$i = preg_replace('/<ol( [^>]+|)>/i', "\n", $i);
								$i = preg_replace('/<blockquote( [^>]+|)>/i', "\n", $i);
								
								$i = preg_replace('/<\/?small( [^>]+|)>/i', '', $i);
								$i = preg_replace('/<\/?i( [^>]+|)>/i', '', $i);
								$i = preg_replace('/<\/?em( [^>]+|)>/i', '', $i);
								$i = preg_replace('/<\/?strong( [^>]+|)>/i', '', $i);
								$i = preg_replace('/<\/?span( [^>]+|)>/i', '', $i);
								$i = preg_replace('/<\/?b( [^>]+|)>/i', '', $i);
								$i = preg_replace('/<\/?i( [^>]+|)>/i', '', $i);
								$i = preg_replace('/<\/?ul( [^>]+|)>/i', '', $i);
								$i = preg_replace('/<\/?li( [^>]+|)>/i', '', $i);
								$i = preg_replace('/<\/?font( [^>]+|)>/i', '', $i);
								$i = preg_replace('/<\/?u( [^>]+|)>/i', '', $i);
								$i = preg_replace('/<\/?div( [^>]+|)>/i', '', $i);
								$i = preg_replace('/<\/?noedit( [^>]+|)>/i', '', $i);
								$i = preg_replace('/<\/?a( [^>]+|)>/i', '', $i);
								$i = preg_replace('/<\/?sub( [^>]+|)>/i', '', $i);
								$i = preg_replace('/<\/?sup( [^>]+|)>/i', '', $i);
								$i = preg_replace('/<\/?img( [^>]+|)>/i', '', $i);
								$i = preg_replace('/<\/?ol( [^>]+|)>/i', '', $i);
								$i = preg_replace('/<\/?blockquote( [^>]+|)>/i', '', $i);
								//$i = preg_replace('/<\/?( [^>]+|)>/i', '', $i);
								
								$i = preg_replace('/<\/\s{0,}$/i', '', $i);
								
								$i = preg_replace('/<!--((.|\s)*?(?=-->|$)|$)-->/i', '', $i);
								$i = preg_replace('/\n+/', "\n", $i);
								$i = trim($i);
								if  (
									preg_match('/</', $i) && // i.e. ... is >15%
									preg_match('/>/', $i)
									)
								{
									echo $fn."\n";
									echo $i."\n";
									echo '<>';
									exit;
								}
								
								$i = substr($i, 0, 2000);
								$i = str_replace('"', '""', $i);
								$w.= '"'.$i.'"'; // last
								
							} else {
								$w.= ''; // last
							}
							
							// is item in CSV already?
							// if yes then push to dup CSV
							if (in_array($item, $csv_unique)) {
								//file_put_contents('./Linda_Dowling/NaturalPartners-Duplicates.csv', $w."\n", FILE_APPEND);
							} else {
								file_put_contents('./Linda_Dowling/NaturalPartners.csv', $w."\n", FILE_APPEND);
								array_push($csv_unique, $item);
							}
							//exit;
							//echo '.'; // exists
						}
					}
				} else {
					echo 'empty';
					exit;
				}
				
				// next index page?
				if (strpos($r, '<a class="next i-next" href="') === false) {
					$end_of_cat = true;
					echo "\n";
					// debug //exit;
					continue;
				}
				
				// increment page & continue
				$page++;
			}
		}
		//echo $count_max_cat."\n";
		exit;
		
		//$previous_page = ''; // is previous page same as current?
		
		$cats = [
			'nutritional-supplements',
			'vitamins-minerals',
			'herbs',
			'homeopathy',
			'personal-care'
		];
		
		//$page = 1;
		
		foreach ($cats as $k => $cat) {
			echo 'starting cat '.$cat;
			$end_of_cat = false;
			$page = 1;
			while (!$end_of_cat) {
				echo 'starting page '.$page;
				$u = 'http://beta.naturalpartners.com/'.$cat.'.html?limit=36&p='.$page;
				$ch = curl_init($u);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_VERBOSE, 0);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
				curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
				curl_setopt($ch, CURLOPT_POST, false);
				curl_setopt($ch, CURLOPT_COOKIEFILE, '/var/www/html/cookie.txt');
				curl_setopt($ch, CURLOPT_COOKIEJAR, '/var/www/html/cookie.txt');
				curl_setopt($ch, CURLOPT_TIMEOUT, 6);
				$r = curl_exec($ch);
				$x = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				curl_close($ch);
				
				if ($x != 200) {
					echo 'status '.$x;
					exit;
				}
				
				// save search index page
				file_put_contents('./linda_dowling/'.$cat.'/'.$page, $r);
				//$previous_page = $r;
				
				//
				preg_match_all('/<h2 class="product-name"><a href="([^"]+)"/', $r, $m);
				
				// download sub-pages
				$u = 'http://beta.naturalpartners.com/'.$cat.'.html?limit=36&p='.$page;
				$ch = curl_init($u);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_VERBOSE, 0);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
				curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
				curl_setopt($ch, CURLOPT_POST, false);
				curl_setopt($ch, CURLOPT_COOKIEFILE, '/var/www/html/cookie.txt');
				curl_setopt($ch, CURLOPT_COOKIEJAR, '/var/www/html/cookie.txt');
				curl_setopt($ch, CURLOPT_TIMEOUT, 6);
				$r = curl_exec($ch);
				$x = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				curl_close($ch);
				
				// next index page?
				if (strpos($r, '<a class="next i-next" href="') === false) {
					$end_of_cat = true;
					continue;
				}
				
				// increment page & continue
				$page++;
			}
		}
	}
	if ($argv[1] == 'dowling__') {
		$f = './.miscellaneous/dowling';
		$data = array();
		$fp = fopen($f, 'rb');
		while(!feof($fp)) {
			$data[] = fgetcsv($fp);
		}
		fclose($fp);
		foreach ($data as $k => $v) {
			if ($v[0] == '') continue;
			$f2 = './.miscellaneous/Dowling/'.str_replace(' ', '_', $v[1]).'.jpg';
			$x = explode("\n",$v[7]); //either 0 or more
			file_put_contents($f2, file_get_contents($x[0]));
		}
		exit;
	}
}
if (isset($_POST['linda_dowling'])) {
	file_put_contents('./.miscellaneous/dowling', $_POST['linda_dowling'], FILE_APPEND);
	exit;
}


/**
 * This will generate an oauth token.
 * This is run from the command line with "php -f"
 * rather than through a get request.
**/
/**
 * oauth_token        = _redacted_
 * oauth_token_secret = _redacted_
 * 
 * oauth_token        = _redacted_
 * oauth_token_secret = _redacted_
 * 
 * oauth_token        = _redacted_
 * oauth_token_secret = _redacted_
 * 
 * oauth_token        = _redacted_
 * oauth_token_secret = _redacted_
 * 
 * Access Token:
    - oauth_token        = _redacted_
    - oauth_token_secret = _redacted_

oauth_token        = _redacted_
    - oauth_token_secret = _redacted_

 oauth_token        = _redacted_
    - oauth_token_secret = _redacted_


**/
if (isset($argv) && $argv[1] == 'LINKEDIN_API_OAUTH2') {
	// linkedin
	$consumer_key = '_redacted_';
	$consumer_secret = '_redacted_';

	$o = new OAuth($consumer_key, $consumer_secret);

	$o->setToken($access_token['oauth_token'], $access_token['oauth_token_secret']);
	$o->setToken('_redacted_', '_redacted_');

	//~ //$url = 'http://api.linkedin.com/v1/company-search?hq-only=true&facet=&facet=industry,4&start=2070&count=2&facet=location';//&facet=location,br:0
	//~ $url = 'https://api.linkedin.com/v1/companies/15305/';//&facet=location,br:0
	//~ $url = 'https://api.linkedin.com/v1/people/?company-name=Apple';//&facet=location,br:0
	$url = 'https://api.linkedin.com/v1/people/~:(id,first-name,skills,educations,languages,twitter-accounts)?format=json';//&facet=location,br:0

	try {
	  if( $o->fetch( $url ) ) {
	  
		print $o->getLastResponse();
		
		print "Successful fetch\n";
	  } else {
		print "Couldn't fetch\n";
	  }
	} catch( OAuthException $e ) {
	  print 'Error: ' . $e->getMessage() . "\n";
	  print 'Response: ' . $e->lastResponse . "\n";

	}
	exit;
}
if (isset($argv) && $argv[1] == 'LINKEDIN_API_OAUTH1') {
	
	
	
	define("CONSUMER_KEY", "_redacted_");
	define("CONSUMER_SECRET", "_redacted_");
	 
	$oauth = new OAuth(CONSUMER_KEY, CONSUMER_SECRET);

	//
	$request_token_response = $oauth->getRequestToken('https://api.linkedin.com/uas/oauth/requestToken?scope=r_basicprofile');
	//~ $request_token_response = $oauth->getRequestToken('https://api.linkedin.com/uas/oauth/requestToken?scope=r_contactinfo');
	//~ $request_token_response = $oauth->getRequestToken('https://api.linkedin.com/uas/oauth/requestToken?scope=rw_company_admin');
	//
	//~ $request_token_response = $oauth->getRequestToken('https://api.linkedin.com/uas/oauth/requestToken?scope=w_messages+r_network');
	//
	//~ $request_token_response = $oauth->getRequestToken('https://api.linkedin.com/uas/oauth/requestToken?scope=r_network');
	//
	//~ $request_token_response = $oauth->getRequestToken('https://api.linkedin.com/uas/oauth/requestToken');
	 
	if($request_token_response === FALSE) {
			throw new Exception("Failed fetching request token, response was: " . $oauth->getLastResponse());
	} else {
			$request_token = $request_token_response;
	}
	
	//request.addQuerystringParameter("scope", "r_network");
	
	//
	print "Request Token:\n";
	printf("    - oauth_token        = %s\n", $request_token['oauth_token']);
	printf("    - oauth_token_secret = %s\n", $request_token['oauth_token_secret']);
	print "\n";

	//
	print "Go to the following link in your browser:\n\n";
	printf("https://api.linkedin.com/uas/oauth/authorize?oauth_token=%s", $request_token['oauth_token']);
	print "\n\n";

	//
	$accepted = false;
	while(!$accepted) {
			print "Have you authorized me? (y/n) ";
			$accepted = !!stristr(fgetc(fopen("php://stdin", "r")), "y");
	}
	 
	$valid_pin = false;
	while(!$valid_pin) {
			print "What is the PIN? ";
			$oauth_verifier = trim(fgets(fopen("php://stdin", "r")));
			$valid_pin = is_numeric($oauth_verifier);
	}

	//
	$oauth->setToken($request_token['oauth_token'], $request_token['oauth_token_secret']);
	 
	$access_token_url = 'https://api.linkedin.com/uas/oauth/accessToken';
	$access_token_response = $oauth->getAccessToken($access_token_url, "", $oauth_verifier);
	 
	if($access_token_response === FALSE) {
			throw new Exception("Failed fetching request token, response was: " . $oauth->getLastResponse());
	} else {
			$access_token = $access_token_response;
	}
	 
	print "Access Token:\n";
	printf("    - oauth_token        = %s\n", $access_token['oauth_token']);
	printf("    - oauth_token_secret = %s\n", $access_token['oauth_token_secret']);
	print "\n";
	print "You may now access protected resources using the access tokens above.\n";
	print "\n";
	
	
	// linkedin
	$consumer_key = '_redacted_';
	$consumer_secret = '_redacted_';

	$o = new OAuth($consumer_key, $consumer_secret);

	$o->setToken($access_token['oauth_token'], $access_token['oauth_token_secret']);
	//~ $o->setToken('_redacted_', '_redacted_');

	//~ //$url = 'http://api.linkedin.com/v1/company-search?hq-only=true&facet=&facet=industry,4&start=2070&count=2&facet=location';//&facet=location,br:0
	$url = 'https://api.linkedin.com/v1/companies/15305/';//&facet=location,br:0
	$url = 'https://api.linkedin.com/v1/people/?company-name=Apple';//&facet=location,br:0

	try {
	  if( $o->fetch( $url ) ) {
	  
		print $o->getLastResponse();
		
		print "Successful fetch\n";
	  } else {
		print "Couldn't fetch\n";
	  }
	} catch( OAuthException $e ) {
	  print 'Error: ' . $e->getMessage() . "\n";
	  print 'Response: ' . $e->lastResponse . "\n";

	}
	exit;

}


/****************************************************************************************************************
 * Functions
 * 
 ****************************************************************************************************************/
// http://php.net/manual/en/function.xml-parse-into-struct.php#66487
class XmlElement {
  var $name;
  var $attributes;
  var $content;
  var $children;
};
function xml_to_object($xml) {
  $parser = xml_parser_create();
  xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
  xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
  xml_parse_into_struct($parser, $xml, $tags);
  xml_parser_free($parser);

  $elements = array();  // the currently filling [child] XmlElement array
  $stack = array();
  foreach ($tags as $tag) {
    $index = count($elements);
    if ($tag['type'] == "complete" || $tag['type'] == "open") {
      $elements[$index] = new XmlElement;
      $elements[$index]->name = $tag['tag'];
      $elements[$index]->attributes = $tag['attributes'];
      $elements[$index]->content = $tag['value'];
      if ($tag['type'] == "open") {  // push
        $elements[$index]->children = array();
        $stack[count($stack)] = &$elements;
        $elements = &$elements[$index]->children;
      }
    }
    if ($tag['type'] == "close") {  // pop
      $elements = &$stack[count($stack) - 1];
      unset($stack[count($stack) - 1]);
    }
  }
  return $elements[0];  // the single top-level element
}
function connect__() { // Return new mysqli()
	$m = new mysqli('127.0.0.1', 'root', '_redacted_') or die(mysqli_error());
	if (mysqli_connect_errno() || !$m) {
		printf("Connect failed: %s\n", mysqli_connect_error());
		exit;
	}
	return $m;
}
function close__() {
	mysqli_close();
}
function connect_() {
	//~ @mysql_connect('127.0.0.1', 'root', '_redacted_') or die('err2:'.mysql_error()); // Changed Jan5 2016
	@mysql_connect('localhost', 'root', '_redacted_') or die('err2:'.mysql_error());
}
function close_() {
	@mysql_close();
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
		if (property_exists($options, 'request_from_file') &&
			$options->request_from_file &&
			file_exists($file)
			) {
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
	curl_setopt($ch, CURLOPT_AUTOREFERER, true);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
	if (property_exists($options, 'tmp_cookie')) {
		curl_setopt($ch, CURLOPT_COOKIEFILE, '/var/www/html/cookie.txt');
		curl_setopt($ch, CURLOPT_COOKIEJAR, '/var/www/html/cookie.txt');
	}
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
/**
 * Calculate the distance between two lat / lon pairs.
 * 
 * Source: https://www.geodatasource.com/developers/php
 * Date retrieved: Dec. 19, 2015
 */
function distance($lat1, $lon1, $lat2, $lon2, $unit) { // $unit = M,K,N = Miles, Kilometers, Nautical miles

	$theta = $lon1 - $lon2;
	$dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
	$dist = acos($dist);
	$dist = rad2deg($dist);
	$miles = $dist * 60 * 1.1515;
	$unit = strtoupper($unit);
	if ($unit == "K") {
		return ($miles * 1.609344);
	} else if ($unit == "N") {
		return ($miles * 0.8684);
	} else {
		return $miles;
	}
}























?>
