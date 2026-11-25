<?php
/*/
 *
 * Copyright 2013-2018 David Shumway
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * Contact: _redacted_.com
 * 
/*/

//======================================================================
/**
 * The scripts that are using the AMT_Batch database,
 * Tables:
 * 	.jobs
 * 	.jobs_history_index
 *  .history
 * 	.history_weekly
 * 	.last_update
 * Scripts:
 * 	For Android, mt.apk
 * 		-last_update, jobs
 *  mturk.com script.js content script.
 * 		Recent	- last_update, jobs
 * 		Pool	-
 *  
 *  
 * New database structure.
 * Aug 6, 2014.
 * 
 * NOTE: BUG: In `ps aux` this program showed up as running after
 * 		around 2 weeks! The CPU time was 10:00!
 *  		April 2016
 * echo 0 > AMT_Batch/cron_status && php -f AMT_Batch.php
 * TODO: Is a run required? (continue)
 * CURL PROXY!
 * curl -m 80 --socks5-hostname localhost:_redacted_ http://localhost/mturk/.scripts/AMT_Batch.php >/dev/null
 */
//======================================================================
/**
 * Other sites where data was sent to in the past.
 */
/**
$mysql_host = "mysql11.000webhost.com"; // 000webhost
$mysql_database = "a3450205_turk";
$mysql_user = "a3450205_user";
$mysql_password = "_REDACTED_";

$mysql_host = "localhost"; // host1free vps
$mysql_database = "a3450205_turk";
$mysql_user = "a3450205_user00";
$mysql_password = "_REDACTED_";

$mysql_host = "localhost"; // host1free 
$mysql_database = "a20_turk";
$mysql_user = "a20_turk";
$mysql_password = "_REDACTED_";

if ($site == 'noads') {
	//set_time_limit(60);
	$mysql_host = "localhost"; // noads.biz
	$mysql_database = "383752";
	$mysql_user = "383752";
	$mysql_password = "_REDACTED_";
}

$mysql_host = "localhost"; // sjs
$mysql_database = "[redacted]_AMT";
$mysql_user = "[redacted]_user";
$mysql_password = "_REDACTED_";
 */ 
//======================================================================
 
// max execution time
set_time_limit(600); // 10 minutes...
// Set error all
error_reporting(E_ALL);
// Debug
echo 'Script starting at '.date('h:i:s')."\n";

// DB
$mysql_host = "localhost"; // localhost
$mysql_database = "AMT_Batch";
$mysql_user = "root";
$mysql_password = "[redacted]";
// Connect
$mysqli = new mysqli("localhost", "root", $mysql_password, "AMT_Batch");
// Test connection
if (mysqli_connect_errno()) {
    printf("Connect failed: %s\n", mysqli_connect_error());
    exit();
}
// Set UTF-8
$mysqli->query("Set names 'utf8'"); 
// Show file status.
$st = file_get_contents('/var/www/html/mturk/.scripts/AMT_Batch/cron_status');
$tmst = explode(' ', $st);
$start_time = time();
if ($tmst[0] == 'complete') {
	// Continue!
	echo 'Last run is complete. Starting new run.'."\n";
} else if ($tmst[0] == 'starting') {
	// Check if old script is too old... (10 minutes)
	if (time() - ($tmst[1]*1) >= 600) {
		// Continue!
		echo 'Last run is STALE! Starting new run.'."\n";
	} else {
		echo 'Last run is still running. EXITING!'."\n";
		exit; // End!
	}
}
// Write to log file.
file_put_contents('/var/www/html/mturk/.scripts/AMT_Batch/cron_status', 'starting '.time());
// Global variable played_first
$played_first = false;

/**
 * Headers temporary login bypass.
 * Note:
 * 		See ~/Desktop/notes/amt batch curl1 versus
 * 		~/Desktop/notes/amt batch curl2 for details of header difference
 * 		when doing max_auth_age=43200 versus max_auth_age=54320000000000.
 * 
 * Note:
 * 		Final URL is:
 * 		https://worker.mturk.com/?end_signin=1&openid.pape.max_auth_age=5432000000000000000000&openid.identity=https%3A%2F%2Fwww.amazon.com%2Fap%2Fid%2Famzn1.account.AHVQINOPOITANLGM6DT74WVR5TIQ&stashKey=da3a10e6-b4e2-4457-85d3-ef84b297b962&openid.claimed_id=https%3A%2F%2Fwww.amazon.com%2Fap%2Fid%2Famzn1.account.AHVQINOPOITANLGM6DT74WVR5TIQ
 * 
 * 		Loaded as a GET in browser with network tab open. (???)
 * 		Then copy as curl and paste headers below into $g_headers. (???)
 */
$gh = file_get_contents(
	'/var/www/html/mturk/.scripts/AMT_Batch/curl_session_bypass');
$gh = explode(' -H ', $gh);
array_shift($gh); // First line is "curl ..."
foreach ($gh as $key=>$value) {
	// Remove apostrophes.
	$value = preg_replace("/^'/", '', $value);
	//~ $value = preg_replace("/'$/", '', $value);
	$value = preg_replace("/' \\\[\r\n\t\s]*$/", '', $value); // tick, space, backslash, spaces, then $
	$gh[$key] = $value;
}
// Remove "' --compressed"
$gh[count($gh)-1] = preg_replace("/\s*--compressed$/", '', $gh[count($gh)-1]);
// Set global variable.
$g_headers = $gh;
echo 'gh';
print_r($g_headers);

// function mysql_try
function mysql_try($query) {
	global $mysqli;
	$result = false;
	$count = 0;
	$rslt = $mysqli->query($query);
	if ($rslt) {
		return $rslt;
	}
	else if (!$result) {
		die(mysqli_error($mysqli).$count."\nQuery:".$query);
	}
}

/**
 * class login
 * Login to AMT.
 * 
 * Latest solution (Jan2018): Login
 * 
 * (deprecated) Solution:
 * 		Login to mturk in FLBS chrome profile. Then
 * 		load the url:
 * 		https://www.mturk.com/mturk/viewhits?searchWords=&pageNumber=2&
 * 			searchSpec=HITGroupSearch%23T%232%23100%23-1%23T%23%21%23%21
 * 			NumHITs%211%21%23%21&sortType=NumHITs%3A1&selectedSearchType
 * 			=hitgroups&pageSize=100
 * 
 **/
class login {
	private $cookie_file = '/var/www/html/mturk/.scripts/AMT_Batch/cookie_file.txt';
	private $back_off;
	private $logged_out_str = '<a href="/mturk/beginsignin" id="lnkWorkerSignin" class="black_text_bold">Sign In</a>';
	private $logged_out_str2 = '<title>\n    Amazon.com Sign In \n</title>';
	private $logged_out_str3 = '" id="lnkWorkerSignin" class="black_text_bold">Sign In</a>';
	private $logged_out_str4 = ' <title dir="ltr">Amazon Sign In</title>';
	private $logged_out_str5 = '                Sign in';
	private $entry_captcha_str = 'auth-captcha-image';
	//							'<input type="text" id="ap_captcha_guess"';
	private $lf_previous_metadata = false;
	// Try pageNumber=2 (9 Sept 17)
	private $url_check_hits_page = 'https://worker.mturk.com/projects.json';
			//~ 'https://www.mturk.com/mturk/viewhits'.
		 	//~ '?searchWords=&pageNumber=2'.
		 	//~ '&searchSpec=HITGroupSearch%23T%233%23100%23-1%23T%23!%23!'.
		 	//~ 'NumHITs!1!%23!&sortType=NumHITs%3A1'.
		 	//~ '&selectedSearchType=hitgroups&pageSize=100';
	//'https://www.mturk.com/mturk/findhits?match=false';
	
	function __construct($script_start_time) {
		// Before retrying, check timer.
		$this->check_timer();
		$this->doLogin();
	}

	// Do sign out function.
				//function doSignout() {
					//$op = new stdClass();
					//$op->url = 'https://www.mturk.com/mturk/beginsignout';
					//$p = $this->curl_get($op);

	function doLogin() {
		$this->back_off = 0; // Reset!
		$this->login();
	}
	
	function check_timer() {
		// Quit before three minutes has elapsed.
		//
		// Desc: In crontab script runs every three minutes.
		// 		 And it only runs if "last complete" was more than ten
		//		 minutes ago. But if "last complete" was more than then
		// 		 minutes then any number of scripts may be running
		// 		 (at three minute intervals).
		//		 So exit after three minutes has elapsed.
		//		 Note: Assumption: This is STILL prior to login success.
		global $start_time;
		if (time() - $start_time > 600) {
			echo 'Hit ten-minute login "time limit". Exiting.';
			exit;
		}
	}
	
	/**
	 * function check_signed_in
	 * Returns false if not signed in, true otherwise.
	 * 
	 */
	function check_signed_in($html) {
		if (strpos($html, $this->logged_out_str) !== false ||
			strpos($html, $this->logged_out_str2)!== false ||
			strpos($html, $this->logged_out_str3)!== false ||
			strpos($html, $this->logged_out_str4)!== false ||
			strpos($html, $this->logged_out_str5)!== false) {
			return false; // Signed out.
		}
		return true; // Signed in.
	}
	
	function login() {
		global $g_headers;
		
		// Check timer.
		$this->check_timer();
		
		// Clear cookie.
				//~ file_put_contents($this->cookie_file, '');
		
		// Try sign-out?
				//~ $this->doSignout();
		
		// Get init. login page.
		// Change max_auth_age: 43200 to 4320000000000
		//
		//
		$lg = new stdClass();
		// Two vars in "url" are "marketplaceId=x" and "clientContext=x"
		// Used for a few weeks so far.
		//~ $lg->url = 'https://www.mturk.com/mturk/beginsignin';
		
		// Testing new, Jan2018
		$lg->url = 'https://www.amazon.com/ap/signin'; //-X POST 
		$lg->headers_array = $g_headers;
		$lg->post = true;
		$lg->post_str = '';
		// Get URL.
		$l = $this->curl_get($lg);
		
		
		/*
		// Do Login
		// Parse login vars
		// And generate url
		// And then post
		$v = $this->vars($l->html);
		if (!$v) {
			echo 'VARS FAILED #1 (Any of: No document / CAPTCHA / No sign-in form)!'."\n";
			file_put_contents('/var/www/html/mturk/.scripts/AMT_Batch/login-failed.log', $l->html);
			$this->retry();
			return;
		}
		$lg = new stdClass();
		$lg->url = $v['action'];
		$lg->post = true;
		$lg->post_str = $v['post_str'];
		//~ $login->debug = true;
		$l = $this->curl_get($lg);
		*/
		
		
		// Login failed and has "CAPTCHA"?
		if (strpos($l->html, $this->entry_captcha_str) !== false) {
			echo 'Login failed (at #1.0)! (Redirected to "CAPTCHA" form)'."\n";
			
			// LOAD VARS OF THIS RESPONSE.
			// IF CAPTCHA THEN WAIT 40s.
			//
			$v = $this->vars($l->html);
			if (!$v) {
				echo 'VARS FAILED #2!'."\n";
				file_put_contents('/var/www/html/mturk/.scripts/AMT_Batch/login-failed.log', $l->html);
				$this->retry();
				return;
			} else {
				$lg = new stdClass();
				$lg->url = $v['action'];
				$lg->post = true;
				$lg->post_str = $v['post_str'];
				// Untested:
						$lg->headers_array = $g_headers;
				$l = $this->curl_get($lg);

				// Login failed and has "CAPTCHA"?
				//
				//
				if (strpos($l->html, $this->entry_captcha_str) !== false) {
					echo 'Login failed (at #1.01)! (Redirected to "CAPTCHA" form)'."\n";
					$this->retry();
					return;
				}
			}
		}
		// Else if not signed in.
		else if ($this->check_signed_in($l->html) == false) {
			echo 'Login failed (at #1.1)! ("Sign-In" exists on page)'."\n";
			print_r($l->html); exit;
			$this->retry();
			return;
		}
		// Else log a successful login.
		else {
			file_put_contents('/var/www/html/mturk/.scripts/AMT_Batch/log_login_successful', $l->html);
		}
		
		// Try loading the HITs page.
		$lg = new stdClass();
		$lg->url = $this->url_check_hits_page;
		$lg->headers_array = $g_headers;
		$l = $this->curl_get($lg);
		// Check not 200
		if ($l->status != 200) {
			echo 'Load page failed ('.$lg->url.','.$lg->status.'), retry.'."\n";
			$this->retry();
			return;
		}
		// Check logged in
		else if ($this->check_signed_in($l->html) == false) {
			echo 'Login failed (at #1.2)! ("Sign-In" exists on page)'."\n";
			file_put_contents('/var/www/html/mturk/.scripts/AMT_Batch/login-failed.log', $l->html);
			$this->retry();
			return;
		}
		// Apparently logged in. Save response.
		else {
			file_put_contents(
				'/var/www/html/mturk/.scripts/AMT_Batch/log_login_successful',
				$l->html);
		}
		
		// Here... Then complete!
		echo 'Login completed at '.date('h:i:s')."\n";
	}
	/**
	 * function retry
	 * Sleep, increment back_off, check timer, then retry login.
	 */
	function retry() {
		echo 'Retrying in '.$this->back_off.'s'."\n";
		sleep($this->back_off);
		$this->back_off++; // increment
		if ($this->back_off > 10) $this->back_off = 10;
		$this->login();
	}
	
	/**
	 * function set_volume
	 */
	static function play_sound() {
		global $played_first;
		echo @shell_exec('/usr/bin/play /var/www/html/mturk/.scripts/AMT_Batch/audio/315694__acollier123__casio-1000p-preset-piano-c.wav');
		if (!$played_first) {
			//~ $this->set_volume();
			$played_first = true;
		}
	}
	/**
	 * function set_volume
	 */
	function set_volume() {
		// AUDIO UP/DOWN
		$vol = shell_exec('amixer -D pulse sset Master 100%+'); // Set up 100%
		if ($vol != '') {
			echo 'VOLUME NOT BLANK:'.$vol."\n";
		}
		$vol = shell_exec('amixer -D pulse sset Master 15%-'); // Bring down 15%
		if ($vol != '') {
			echo 'VOLUME NOT BLANK:'.$vol."\n";
		}
	}
	
	/**
	 * function wait_cp_response
	 * Wait for user to enter a captcha.
	 * 
	 * Desc: Remove 40 second time limit for entering a captcha.
	 * 	That is, just wait forever until script time limit is reached
	 * 	or captcha is entered. Previously counted to 40 then just
	 * 	tried again regardless.
	 */
	function wait_cp_response() {
		$x = '';
		$fp = '/var/www/html/mturk/.scripts/AMT_Batch/CAPTCHA-response';
		// Play sound
		$this->play_sound();
		echo 'Wait for CAPTCHA response.'."\n";
		while ($x == '' && file_exists($fp)) {
			$a = trim(file_get_contents($fp));
			if (trim($a) != '') {
				$x = $a;
				break; // Unnecessary to break?
			}
			sleep(1);
		}
		echo 'Wait complete! Captcha response is "'.$x.'".'."\n";
		return $x;
	}
	/**
	 * function vars
	 */
	function vars($html) {
		$r = [
			'action' => '',
			'post_str' => '',
		];
		try {
			$doc = new DOMDocument();
			@$doc->loadHTML($html);
			if (!$doc) {
				echo 'Error parsing DOC!'."\n";
				return false;
			}
			// Form CAPTCHA!
			//
			//   <img alt="Visual CAPTCHA image, continue down for an audio option." src="https://opfcaptcha-prod.s3.amazonaws.com/daa29fb5574c44baa908176a1aa32655.jpg?AWSAccessKeyId=AKIAIW42E3WHCTE72XHQ&amp;Expires=1508430833&amp;Signature=O%2BdoBrlE6EJJvr%2FHlwSl%2F%2FSQT%2BU%3D" data-refresh-url="/ap/captcha?appAction=SIGNIN&amp;captchaObfuscationLevel=ape:aGFyZA==&amp;captchaType=image" id="auth-captcha-image">

			// @Update:
			//		Captcha image changed to doc->getElementById('auth-captcha-image').
			//
			if ($doc->getElementById('auth-captcha-image')) {
									//auth-captcha-image
				echo 'Has CAPTCHA!'."\n";
				
				// Download CAPTCHA image!
				//
				// E.g.
  //~ <div id="ap_captcha_img">
    //~ <img src="https://opfcaptcha-prod.s3.amazonaws.com/5566c81db6d6413990dd1eace45fe8df.jpg?AWSAccessKeyId=_redacted_&Expires=1438800330&Signature=BE9e%2BkU6alkPSq%2Fq3V0a%2BU%2FsGGg%3D" />
  //~ </div>
				$captcha_response = '';
				//~ $im = $doc->getElementById('ap_captcha_img');
				$im = $doc->getElementById('auth-captcha-image');
				//auth-captcha-image
				if ($im) {
					$imsrc = $im->getAttribute('src');
					if ($imsrc) {
						$lg = new stdClass();
						$lg->url = $imsrc;
						$l = $this->curl_get($lg);
						file_put_contents('/var/www/html/mturk/.scripts/AMT_Batch/CAPTCHA.jpg', $l->html);
						file_put_contents('/var/www/html/mturk/.scripts/AMT_Batch/CAPTCHA-response', '');
						// Wait for 20 seconds.
						// While waiting if CAPTCHA-response becomes NOT empty
						// then continue. Else if empty then return false.
						$captcha_response = $this->wait_cp_response();
					}
				}
				if ($captcha_response == '') // No change...
					return false;
			} else {
				echo '...No captcha???...';
				echo strpos($html,'auth-captcha-image');
			}
			// Form action
			// @Updated: Form changed from Id="ap_signin_form" to Name="signIn".
			//			 I.e. $form = $doc->getElementById('ap_signin_form');
			//			 However, it is necessary to use "getElementsByTagName"
			//			 as getElementsByName is not implemented.
			// 
			$form = $doc->getElementsByTagName('form');
			if (!$form) {
				echo 'No sign-in form!'."\n";
				return false;
			}
			$form = $form[0];
			$r['action'] = $form->getAttribute('action');
			// Form vars  Duration: 00:00:02.09  

			//
			//
			$inputs = $form->getElementsByTagName('input');
			$inp = [];
			foreach ($inputs as $k=>$input) {
				if ($input->getAttribute('name') == 'create') { // `create` Not 1
					$input->setAttribute('value', '0');
				}
				if ($input->getAttribute('name') == 'email') { // `email` is ...
					//~ $input->setAttribute('value', '_redacted_@gmail.com');
					//~ $input->setAttribute('value', '_redacted_@gmail.com');
					//~ $input->setAttribute('value', '_redacted_@gmail.com');
					$input->setAttribute('value', '_redacted_@gmail.com');
				}
				if ($input->getAttribute('name') == 'password') { // `password` is ...
					//~ $input->setAttribute('value', '_redacted_');
					//~ $input->setAttribute('value', '_redacted_');
					//~ $input->setAttribute('value', '_redacted_');
					$input->setAttribute('value', '_redacted_');
				}
				if ($input->getAttribute('name') == 'guess' ||				// IF THERE IS A CAPTCHA!
					$input->getAttribute('name') == 'auth-captcha-guess') {	// Add "auth-captcha-guess", Jan2018
					$input->setAttribute('value', $captcha_response);
				}
				if ($input->getAttribute('name') == 'rememberMe') {
					// Present when signing into amazon.com
					$input->setAttribute('value', 'true');
				}
				if ($input->getAttribute('name') == '') {
					continue; // NOT BLANKS
				}
				if ($input->getAttribute('name') == 'metadata1' &&
					$input->getAttribute('value') != '') {
					
					//~ // Try: Updating metadata file if not blank.
					//~ file_put_contents(
						//~ '/var/www/html/mturk/.scripts/AMT_Batch/log_lf_metadata2',
						//~ $input->getAttribute('value')
						//~ );
					
					//~ // Try: Updating metadata file if not blank.
					//~ file_put_contents(
						//~ '/var/www/html/mturk/.scripts/AMT_Batch/log_lf_metadata1',
						//~ $input->getAttribute('value')
						//~ );
				}
				$inp[ $input->getAttribute('name') ] = $input->getAttribute('value');
			}
			// Create post_str
			$vars = [];
			foreach ($inp as $key => $value) {
				$vars[] = $key.'='.urlencode($value);
			}
			print_r($vars);
			$r['post_str'] = implode('&', $vars);
			$r['post_str'].= '&signIn='; // Form name
			if (strpos($r['post_str'], 'metadata1') === false) {
				// metadata.
				// RANDOM DOES NOT WORK.
				// 	e.g. $r['post_str'].= '&metadata1='.rand(0,100000);
				// 	800 random characters does not work.
				// 	e.g. $r['post_str'].= '&metadata1='.str_repeat(rand(10,99), 400); // 800 chars
				// From existing login attempt via chrome browser?
				// 	Does work.
				if ($this->lf_previous_metadata) {
					// Set from previous runs or from first time.
					$r['post_str'].= '&metadata1='.$this->lf_previous_metadata;
				} else {
					// Set from file.
					$this->lf_previous_metadata = file_get_contents('/var/www/html/mturk/.scripts/AMT_Batch/lf_metadata1');
					$r['post_str'].= '&metadata1='.urlencode($this->lf_previous_metadata);
				}
			}
			// Output to log file.
			file_put_contents('/var/www/html/mturk/.scripts/AMT_Batch/log_post_str', $r['post_str']);
			// Return complete
			return $r;
		} catch(Exception $e) {
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
		
		$ch = curl_init($options->url);
		if (property_exists($options, 'headers_array')) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $options->headers_array);
		}
		curl_setopt($ch, CURLOPT_VERBOSE, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0");
		curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_file);
		curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie_file);
		curl_setopt($ch, CURLOPT_TIMEOUT, 140);
		//~ curl_setopt($ch, CURLOPT_ENCODING, 'identity');//
		//~ curl_setopt($ch, CURLOPT_ENCODING, 'gzip');//
		curl_setopt($ch, CURLOPT_ENCODING, '');//
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);//
		//~ curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, true);//
		//~ curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//
		
		// Try: Turn off proxy. 18 Sept., 2017.
		//~ curl_setopt($ch, CURLOPT_PROXY, '127.0.0.1:8123');//
		if (property_exists($options, 'post')) {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $options->post_str);
		} else
			curl_setopt($ch, CURLOPT_POST, false);
		// Exec
		$r = curl_exec($ch);
		$x = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if (property_exists($options, 'debug')) {
			print_r(curl_getinfo($ch));
		}
		curl_close($ch); // Close?
		
		// Return
		$return->html = $r;
		$return->status = $x;
		return $return;
	}
}

// DO LOGIN
$scrape = new scraper();

/**
 * class scraper
 * Scrape the top tasks on the site.
 * 
 * @called_by		$scrape = new scraper();
 */
class scraper {
	private $urls_to_scrape = array();
	private $cookie_file = '/var/www/html/mturk/.scripts/AMT_Batch/cookie_file.txt';
	// <div data-react-class="require(&#39;reactComponents/hitSetTable/HitSetTable&#39;)[&#39;default&#39;]" data-react-props="
	//~ private $preg_match_string = '/<div data-react-class="require\(&#39;reactComponents\/hitSetTable\/HitSetTable&#39;\)\[&#39;default&#39;\]" data-react-props="\{(.+?)\}"><\/div>/';
	# New: Jan 2023
	private $preg_match_string = '/\{"results":\[/';
	
	# 
	//&quot;bodyData&quot;:[
	                           //'/<div data-react-class="require\(&#39;reactComponents\/hitSetTable\/HitSetTable&#39;\)\[&#39;default&#39;\]" data-react-props="\{&quot;bodyData&quot;:
	
	// Old, as of dec 2017.
	//~ private $preg_match_string = '/<a class="capsulelink" href="#" id="capsule(?:.+?)">(.+?)<\/a>(?:.+?)<a href="\/mturk\/searchbar\?selectedSearchType=hitgroups&amp;requesterId=([^&"]+)[^"]{0,}">(.+?)<\/a>(?:.+?)<a id="number_of_hits\.tooltip--\d+">[\r\n\t\s]+HITs Available:&nbsp;&nbsp;[\r\n\t\s]+<\/a>[\r\n\t\s]+<\/td>[\r\n\t\s]+<td align="left" valign="top" nowrap class="capsule_field_text">[\r\n\t\s]*(\d+)(?:.+?)Qualifications Required:(.+?)<\/table>/';
	
	// Search page 1 url to load
	//~ private $url_string = 'https://worker.mturk.com/?page_size=100&page_number=1&sort=num_hits_desc';
	private $url_string = 'https://worker.mturk.com/projects.json?page_size=100&page_number=1&sort=num_hits_desc';
	
	//~ private $url_string = 'https://www.mturk.com/mturk/viewhits'.
		//~ '?searchWords=&pageNumber=1'.
		//~ '&searchSpec=HITGroupSearch%23T%233%23100%23-1%23T%23!%23!NumHITs!1!%23!'.
		//~ '&sortType=NumHITs%3A1&selectedSearchType=hitgroups&pageSize=100';
	 	
	private $logged_out_str = '<a href="/mturk/beginsignin" id="lnkWorkerSignin" class="black_text_bold">Sign In</a>';
	private $logged_out_str2 = '<title>\n    Amazon.com Sign In \n</title>';
	private $logged_out_str3 = '" id="lnkWorkerSignin" class="black_text_bold">Sign In</a>';
	private $logged_out_str4 = ' <title dir="ltr">Amazon Sign In</title>';
	private $entry_captcha_str = 'auth-captcha-image';
	
	private $np = 2; // number pages to scrape (depth) -- past pg 2 (200) is always very low amount of hits/task
	
	// number completed pages
	private $count_pages_complete = 0;
	// MULTI
	private $requester;
	private $login;
	private $back_off;
	private $first_scrape;
	//
	private $run_start_time;
	private $jobs_are_modified;
	private $array_jobs_jhid;
	private $last_completed_run = 0; // Initial is 0
	private $memory_usage = 0;
	private $script_start_time;
	// New scrape method
	private $scrape_results = [];
	
	function __construct() {
		// Set time
		$this->script_start_time = time();
		
		// Multiple requests
				//~ require_once('/var/www/html/curl_mult_request.php');
				//~ $this->requester = new MultipleRequester();
		
		// Build URLs
		for ($i=1; $i<=$this->np; $i++) {
			$u = preg_replace('/page_number=\d+/', 'page_number='.$i, $this->url_string);
			// OLD, DEC 2017
					//~ $u = preg_replace('/pageNumber=\d+/', 'pageNumber='.$i, $this->url_string);
			array_push($this->urls_to_scrape, $u);
		}
		
		$this->init_page_scrape();
	}
	
	// function init_page_scrape
	function init_page_scrape() {
		echo 'Scraper run starting at '.date('h:i:s')."\n";
		$this->back_off = 0; // Reset this!
		$this->first_run = true; // Reset this!
		$this->run_start_time = time();
		$this->pages();
	}
	
	// function echo_memory_usage
	function echo_memory_usage() {
		//~ echo 'Change in memory is: '.(xdebug_memory_usage() - $this->memory_usage) . "\n";
		//~ $this->memory_usage = xdebug_memory_usage();
	}
	
	function check_signed_in($html) {
		if (strpos($html, $this->logged_out_str) !== false ||
			strpos($html, $this->logged_out_str2)!== false ||
			strpos($html, $this->logged_out_str3)!== false ||
			strpos($html, $this->logged_out_str4)!== false) {
			return false; // Signed out.
		}
		return true; // Signed in.
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
		
		$ch = curl_init($options->url);
		if (property_exists($options, 'headers_array')) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $options->headers_array);
		}
		curl_setopt($ch, CURLOPT_VERBOSE, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0");
		//~ curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_file); // Jan2018: Removed cookie file but added headers_array.
		//~ curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie_file);
		curl_setopt($ch, CURLOPT_TIMEOUT, 140);
		curl_setopt($ch, CURLOPT_ENCODING, '');//
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);//
		//~ curl_setopt($ch, CURLOPT_PROXY, '127.0.0.1:8123');//
		//~ if (property_exists($options, 'post')) {
			//~ curl_setopt($ch, CURLOPT_POST, true);
			//~ curl_setopt($ch, CURLOPT_POSTFIELDS, $options->post_str);
		//~ } else
			//~ curl_setopt($ch, CURLOPT_POST, false);
		// Exec
		$r = curl_exec($ch);
		$x = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		//print_r(curl_getinfo($ch));exit;
		if (property_exists($options, 'debug')) {
			print_r(curl_getinfo($ch));
		}
		print_r(curl_getinfo($ch));
		curl_close($ch); // Close?
		
		// Return
		$return->html = $r;
		$return->status = $x;
		return $return;
	}
	
	/**
	 * function pages
	 */
	function pages() {
		global $g_headers;
		
		// Loop URLs.
		foreach($this->urls_to_scrape as $key => $url) {
			// If URL is not in array yet or URL has not been downloaded.
			if (!array_key_exists($url, $this->scrape_results) ||
				$this->scrape_results[$url] == '') {
				// Initialize to blank.
				$this->scrape_results[$url] = '';
				// Get this page.
				$pg = new stdClass();
				$pg->url = $url;
				$pg->headers_array = $g_headers;
				$pg = $this->curl_get($pg);
				
				// If logged out.
				if ($this->check_signed_in($pg->html) == false) {
					echo 'Logged out!';
					// Make a sound and exit.
					// Jan2018
					login::play_sound();
					exit;
					// Skip login / Jan18
					//~ // Do login.
					//~ $login = new login( $this->script_start_time );
					//~ // Restart page scrape.
					//~ $this->init_page_scrape();
					//~ return;
				}
				// Check if valid.
				$x = str_replace("\n", "", $pg->html);
				$y = preg_match($this->preg_match_string, $x, $match);
				if  (
					$pg->status != 200 ||
					//~ preg_match('/<\/html>[\r\n\t\s]*$/i', $pg->html) === false ||
					$y === false
					)
				{
					echo 'Page invalid!'; 
					print_r($pg->status);
					//~ print_r(preg_match('/<\/html>[\r\n\t\s]*$/i', $pg->html));
					print_r($pg->html);
					if (strpos($pg->url, 'login') !== false) {
						login::play_sound();
						exit;
					}
				} else {
					// If this is a valid page then save it.
					print_r('Valid page');
					//~ print_r($pg->html);
					$this->scrape_results[$url] = $pg->html; #$match[1];
				}
				// Sleep briefly between successful downloads.
				sleep(3);
			}
		}
		
		// Show status.
		echo 'Download complete at '.date('h:i:s')."\n";
		
		// Run DB update.
		$this->update();
	}
	
	// Used once for testing static functions.
	// May be deleted.
	static function testing() {
		echo 'works';
	}
	
	function update() {
		/**
		 * DB update.
		 */
		global $mysqli;
		//~ $mysql_host = "localhost"; // localhost
		//~ $mysql_database = "AMT_Batch";
		//~ $mysql_user = "root";
		//~ $mysql_password = "[redacted]";
		//~ @mysql_connect($mysql_host,$mysql_user,$mysql_password) or die(mysql_error());
		//~ @mysql_select_db($mysql_database) or die(err(mysql_error()));
		
		/*****
		 * Deletion
		 * Update Sep. 29, 2013
		 * 
		 * Set all jobs to 0 where job_exists != -1. If job_exists still == -1
		 * after the run is complete, then job is deleted. (Delete from jobs
		 * where job_exists == -1.) Then set job_exists = -1 for all jobs where
		 * job_exists == 0.
		 * Jobs with -1 will be deleted from DB if not found on this run.
		 * 
		 * Update Jan 2023
		 * {"results":[
		 * 	{"hit_set_id":"37ZUGP9HAC8XSGS3UJ84SRGVFOS81G","requester_id":"A2UT8V5NGIWOE1",
		 *   "requester_name":"Research Rewards","title":"Quick Market Research Survey",
		 *   "description":"Participate in market research surveys of your choice. .. estimated time to complete.",
		 *   "assignment_duration_in_seconds":5400,"creation_time":"2023-01-28T21:02:03.000Z",
		 *   "assignable_hits_count":30687,"latest_expiration_time":"2023-03-29T21:02:03.000Z",
		 *   "caller_meets_requirements":true,"caller_meets_preview_requirements":true,
		 *   "last_updated_time":"2023-01-28T21:02:03.000Z","monetary_reward":{"currency_code":"USD","amount_in_dollars":0.02},
		 *   "accept_project_task_url":"/projects/37ZUGP9HAC8XSGS3UJ84SRGVFOS81G/tasks/accept_random.json?ref=w_pl_prvw",
		 *   "requester_url":"/requesters/A2UT8V5NGIWOE1/projects.json?ref=w_pl_prvw",
		 *   "project_tasks_url":"/projects/37ZUGP9HAC8XSGS3UJ84SRGVFOS81G/tasks.json?ref=w_pl_prvw",
		 *   "project_requirements":[],"requesterInfo":{"activityLevel":"High","taskApprovalRate":"≥ 99%",
		 *   "taskReviewTime":"less than a day"}} ...
		 *  ],
		 *  "page_number":1,"num_results":100,"total_num_results":547}
		 * 
		**/
		// Uncomment this when script is fixed: dec 2017
		mysql_try(
			'UPDATE jobs
			SET
				job_exists = 0
			WHERE
				job_exists != -1'
		);

		/**
		 * Iterate jobs
		 * 
		 */

		// Jobs are modified, default = false
		$this->jobs_are_modified = false;
		
		// @ $array_jobs_jhid
		// Jobs by jobs_history.id
		// This is a copy of the job.
		// However, it is indexed by the
		// jobs_history.id rather than by
		// jobs_history.hash_id.
		$this->array_jobs_jhid = array();
		
		// Number completed pages, start = 0
		$count_pages_complete = 0;

		// Duplicate jobs array
		// This will be filled with duplicates
		$duplicate_jobs_array = array();

		// Page count
		$page_number = 1;

		// Array current jobs [ {j1}, {j2} ... {jN} ]
		$array_current_jobs = array();

		// Array current jobs [ {j1}, {j2} ... {jN} ]
		$array_current_hash_ids = array();
		
		// Loop through pages.
		foreach($this->urls_to_scrape as $urls_key => $url_) {
			
			$html_content = $this->scrape_results[$url_];
			//~ print_r($html_content);
			
			// **** OLD **** //
			// HTML content.
			// Add surrounding brackets.
			//~ $html_content = '{'.$html_content.'}';
			//~ $html_content = html_entity_decode($html_content);
			//~ $html_content = str_replace("&#39;", "'", $html_content);
			
			// **** OLD **** //
			//$html_content = str_replace("\n", "", $html_content);
			
			// JSON decode
			$m = json_decode($html_content);
			
			// If no "results", try "bodyData".
			// (Update onsite Dec 22 2017.)
			if (!property_exists($m, 'results') && !property_exists($m, 'bodyData')) {
				echo 'No results or bodyData!';
				print($html_content);
				login::play_sound();
				exit;
			} else
			if (!property_exists($m, 'results')) {
				$m->results = $m->bodyData;
			}
			
			// Sanity check.
			// Assuming we don't go past page 2 or so, and there are
			// more than 300-400.
			if (count($m->results) != 100) {
				echo 'Num results != 100!';
				login::play_sound();
				exit;
			}
			
			// Old dec 2017
			// preg_match_all($this->preg_match_string, $html_content, $matches);
			
			$count_pages_complete++; // Unnecessary but still necessary.
			
			// count
			// "{"results":[{"hit_set_id":
			$c = 0;
			foreach ($m->results as $k => $job) { //=>$title
				
				$t		= trim($job->title);	// title
				$rid	= $job->requester_id;
				$rn		= $job->requester_name;
				$h		= $job->assignable_hits_count;
				
				// Build qual string.
				// Use the qual. "id" rather than a string (old).
				$qual	= '';
				if (count($job->project_requirements) > 0) {
					$a = array();
					foreach ($job->project_requirements as $k1 => $pr) {
						// val
						if (count($pr->qualification_values) == 0) {
							$val = ''; // E.g. MastersExists, so values=""
						} else if (count($pr->qualification_values) == 1) {
							$val = ' '.$pr->qualification_values[0];
						} else { // 2 or more
							$val = '('.implode(',', $pr->qualification_values).')';
						}
						// Generate the string
						$qstr = $pr->qualification_type->name . ' ' .
								$pr->comparator .
								$val;
						
						array_push($a, $qstr);
						//~ array_push($a, $pr->qualification_type_id);
					}
					$qual = implode('|', $a);
				}
				
				// REPLACE <SPAN> IN REQUESTER NAME
				// AUG19 2014
				// REMOVED: DEC 2017
				//
				//~ $rn = preg_replace('/<\/?span[^>]*>/i', '', $rn);
				//~ $rn = trim($rn);
				
				// REPLACE <SPAN> IN TITLE
				// OCT17 2014
				// REMOVED: DEC 2017
				// "TITLE ..... [spaces or other space chars] <span class="tags"></span>"
				//~ $t = preg_replace('/<span[^>]*>[^>]*<\/span>/i', '', $t);
				//~ $t = trim($t);
				
				// Duplicate Tasks Key
				// New Aug11 2014 => $key becomes $task_identifier_str
				// New version ought to keep all "duplicate ids" for
				// tasks mostly the same going forward; it may
				// consider some tasks to be duplicates that it would not
				// have before, because it adds additional constraints to
				// the ID such as groupId, reward, etc.
				//
				// This is for each task. Each task
				// is unique based on a string.
				// The string is:
				//
				// $t.
				// $rid.
				// $rn.
				// //~ $duplicate_jobs_array[$key]. // 0, 1, 2, ... etc.
				// $qual.
				// $groupId.
				// $reward.
				// $time_allotted.
				// $description;
				//
				// 
				
				// HTML ENTITY DECODE
				// SKIPPING THIS.
				//$t = html_entity_decode($t);
				//$rn	= html_entity_decode($rn);
				//$qual = html_entity_decode($qual);
				//$description = html_entity_decode($description);
				
				//
				$groupId = $job->hit_set_id;
				$reward = $job->monetary_reward->amount_in_dollars; #,&quot;monetary_reward&quot;:{&quot;currency_code&quot;:&quot;USD&quot;,&quot;amount_in_dollars&quot;:0.05},
				$time_allotted = $job->assignment_duration_in_seconds;
				$description = $job->description;
				
				// task_identifier_str
				// Replaces $key.
				//
				//
				$task_identifier_str = $t.
					$rid.
					$rn.
					$qual.
					$groupId.
					$reward.
					$time_allotted.
					$description;
				
				// Duplicate jobs ID
				// @param duplicate_jobs_array[]
				//
				if (isset($duplicate_jobs_array[$task_identifier_str])) { // #misses "NULL"
				
					//array_key_exists($key, $duplicate_jobs_array) // higher cpu load
					
					$duplicate_jobs_array[$task_identifier_str]++;
				} else {
					$duplicate_jobs_array[$task_identifier_str] = 0;
				}
				
				// MD5
				// This is the *unique* hash ID of each task.
				// This is exactly the same as $task_identifier_str;
				// however, it includes 'duplicate_jobs_id'.
				//
				$md5 = md5(
					$t.
					$rid.
					$rn.
					$duplicate_jobs_array[$task_identifier_str]. // 0, 1, 2, ... etc.
					$qual.
					$groupId.
					$reward.
					$time_allotted.
					$description
				);
				
				// $array_current_jobs
				// Push job to array.
				//
				$job = array();
				$job['title'] = $t;
				$job['HITs_available'] = $h;
				$job['requester_id'] = $rid;
				$job['requester_name'] = $rn;
				$job['id_duplicate_jobs'] = $duplicate_jobs_array[$task_identifier_str];
				$job['qualifications'] = $qual;
				$job['groupId'] = $groupId;
				$job['reward'] = $reward;
				$job['time_allotted'] = $time_allotted;
				$job['description'] = $description;
				$job['on_page_number'] = $page_number;
				// For later
				$job['in_jobs_history_index'] = false; // ...
				$job['jobs_history_index_ID'] = '';
				$job['hash_id'] = $md5;
				$job['in_jobs'] = false; // NEW AUG8 2014. (Boolean) Is in the .jobs table?
				$job['jobs_id'] = NULL;  // NEW AUG8 2014. (INT) jobs.id
				//~ $job['hash_id'] = ''; // OLD hash_id Aug5 2014
				
				// For EVEN later! This is for the Alerts.
				// Set the time_posted so it is as though
				// it is from the database.
				// This DOES NOT == $start
				$job['time_posted'] = NULL;
				
				// ...
				array_push($array_current_hash_ids, $md5);
				
				$array_current_jobs[$md5] = $job;
				
				// Increment preg count
				$c++;
			}
			
			// Increment page_number
			$page_number++;
		}
		
		// current_jobs_history_index_IDs
		$current_jobs_history_index_IDs = array();

		// Select all where in jobs_history_index.
		$rslt = mysql_try('
			SELECT
				*
			FROM jobs_history_index
			WHERE
				hash_id IN ("'.implode('","', $array_current_hash_ids).'")	
		');
		if (mysqli_num_rows($rslt) > 0) {
			while ($row = mysqli_fetch_array($rslt)) {
				$hash_id = $row['hash_id'];
				if (isset($array_current_jobs[$hash_id])) {
					$array_current_jobs[$hash_id]['jobs_history_index_ID'] = $row['id'];
					$array_current_jobs[$hash_id]['in_jobs_history_index'] = true;
					
					// NOT HERE
					//~ $array_current_jobs[$hash_id]['time_posted'] = $row['time_posted']; // !! SET time_posted
					
					$this->array_jobs_jhid[ $row['id'] ] = $array_current_jobs[$hash_id];
				}
			}
		}

		// Loop through the array $array_current_jobs
		// and add jobs that are not in jobs_history_index.
		// Hack: Jun 2022. unf8mb4 in qualifications not going into the 
		// db. So workaround: utf8_encode those values.
		foreach ($array_current_jobs as $hash_id => $v) {
			if (!$v['in_jobs_history_index']) {
				
				// TRY TO ADD TO REQUESTER_IDS
				// Jan 2023: This will fail if duplicate key exists,
				// so wrap in try. Apparently this previously failed
				// silently.
				try {
					$mysqli->query('
						INSERT INTO requester_ids SET requester_id="'.mysqli_real_escape_string($mysqli, $v['requester_id']).'"'
					);
				} catch (Exception $ex) {
					//print_r('');
				}
				
				// ADD TO INDEX
				mysql_try('
					INSERT INTO jobs_history_index
						(
							title,
							requester_id,
							requester_name,
							id_duplicate_jobs,
							qualifications,
							groupId,
							reward,
							time_allotted,
							description,
							hash_id
						)
					VALUES
						(
							"'.mysqli_real_escape_string($mysqli, $v['title']).'",
							"'.mysqli_real_escape_string($mysqli, $v['requester_id']).'",
							"'.mysqli_real_escape_string($mysqli, $v['requester_name']).'",
							 '.mysqli_real_escape_string($mysqli, $v['id_duplicate_jobs']).',
							"'.mysqli_real_escape_string($mysqli, utf8_encode($v['qualifications'])).'",
							"'.mysqli_real_escape_string($mysqli, $v['groupId']).'",
							"'.mysqli_real_escape_string($mysqli, $v['reward']).'",
							"'.mysqli_real_escape_string($mysqli, $v['time_allotted']).'",
							"'.mysqli_real_escape_string($mysqli, $v['description']).'",
							"'.mysqli_real_escape_string($mysqli, $v['hash_id']).'"
						)
				');
				
				$array_current_jobs[$hash_id]['jobs_history_index_ID'] = mysqli_insert_id($mysqli);
				$array_current_jobs[$hash_id]['in_jobs_history_index'] = true;
				
				// Add to array $array_jobs_jhid.
				$this->array_jobs_jhid[ mysqli_insert_id($mysqli) ] = $array_current_jobs[$hash_id];
			}
		}
		
		/**
		 * Add task to history and history_weekly tables.
		 */
		/* .history
		id	int(11) UN PK AI
		id_jobs_history_index	int(11) UN 
		date_run_time	int(11) UN 
		HITs_available	int(11) UN  
		 */
		/* .history_weekly
		id	int(11) UN PK AI
		id_jobs_history_index	int(11) UN 
		date_run_time	int(11) UN 
		HITs_available	int(11) UN  
		 */
		// m=01-12, d=01-31
		$fmt_date = date('Y-m-d', $this->run_start_time);
		foreach ($this->array_jobs_jhid as $jhid => $v) {
			// Insert to history
			mysql_try('
				INSERT INTO history
					(
						id_jobs_history_index,
						date_run_time,
						HITs_available,
						date_dmy
					)
				VALUES
					(
						'.$v['jobs_history_index_ID'].',
						'.$this->run_start_time.',
						'.$v['HITs_available'].',
						"'.mysqli_real_escape_string($mysqli, $fmt_date).'"
					)
			');
			
			// Insert to history_weekly
			mysql_try('
				INSERT INTO history_weekly
					(
						id_jobs_history_index,
						date_run_time,
						HITs_available
					)
				VALUES
					(
						'.$v['jobs_history_index_ID'].',
						'.$this->run_start_time.',
						'.$v['HITs_available'].'
					)
			');	
		}

		// IS THIS JOB IN .jobs?
		// If no, then add a new row to .jobs.
		// If yes, then update .HITs_available, .time_last_update, .job_exists, .on_page_number
		// Check by id_jobs_history_index...
		// Set the job's in_jobs value to true...

		// Build $current_jobs_history_index_IDs
		//
		//
		foreach ($this->array_jobs_jhid as $jhid => $job) {
			array_push($current_jobs_history_index_IDs, $job['jobs_history_index_ID']);
		}
		$curr_IDs_sql = '
			SELECT
				id,
				id_jobs_history_index,
				time_posted
			FROM jobs
			WHERE
				id_jobs_history_index IN ('.implode(',', $current_jobs_history_index_IDs).')
		';

		// $rslt
		// $hash_id = $row['hash_id']; // Does not exist!
		// 
		$rslt = mysql_try($curr_IDs_sql);
		if (mysqli_num_rows($rslt) > 0) {
			while ($row = mysqli_fetch_array($rslt)) {
				
				$jhid = $row['id_jobs_history_index'];
				//~ $hash_id = $row['hash_id']; // Does not exist!
				//~ echo $jhid;
				
				if (isset($this->array_jobs_jhid[$jhid])) {
					$this->array_jobs_jhid[$jhid]['jobs_history_index_ID'] = $row['id_jobs_history_index'];
					$this->array_jobs_jhid[$jhid]['in_jobs'] = true;
					$this->array_jobs_jhid[$jhid]['jobs_id'] = $row['id'];			
					$this->array_jobs_jhid[$jhid]['time_posted'] = $row['time_posted']; // time_posted !!!
				}
			}
		}

		// Loop
		foreach ($this->array_jobs_jhid as $jhid => $task) {

			// Insert new
			if (!$task['in_jobs']) {
				$this->jobs_are_modified = true;
				mysql_try('
					INSERT INTO jobs
						(
							HITs_available,
							time_posted,
							time_last_update,
							job_exists,
							on_page_number,
							id_jobs_history_index
						)
					VALUES
						(
							'.$task['HITs_available'].',
							'.$this->run_start_time.',
							'.$this->run_start_time.',
							1,
							'.$task['on_page_number'].',
							'.$task['jobs_history_index_ID'].'
						)
				');
				$this->array_jobs_jhid[$jhid]['jobs_id'] = mysqli_insert_id($mysqli);
				$this->array_jobs_jhid[$jhid]['in_jobs_history_index'] = true;		// unnecessary at this point?
				$this->array_jobs_jhid[$jhid]['in_jobs'] = true;						// unnecessary at this point?
				$this->array_jobs_jhid[$jhid]['time_posted'] = $this->run_start_time;	// time_posted
			}
			// Update old, HITs_available, time_last_update, job_exists, on_page_number
			else if ($task['in_jobs'])
			{
				mysql_try('
					UPDATE jobs
						SET
							HITs_available		= '.$task['HITs_available'].',
							time_last_update	= '.$this->run_start_time.',
							job_exists			= 1,
							on_page_number		= '.$task['on_page_number'].'
						WHERE
							id					= '.$task['jobs_id']
				);
			}
		}

		// Delete from history_weekly where time is > 1 week
		$one_week = 7 * 86400; // seconds in one week
		$time_minus_one_week = time() - $one_week;
		mysql_try('
			DELETE FROM
				history_weekly
			WHERE
				date_run_time < '.$time_minus_one_week.'
		');
		
		echo '$count_pages_complete:'.$count_pages_complete."\n";
		echo '$jobs_are_modified:'.$this->jobs_are_modified."\n";

		if ($count_pages_complete != $this->np) {
			echo 'ERROR: $count_pages_complete != $np but equals '.$this->np.'!!'."\n";
			// START OVER!
			$this->init_page_scrape();
			return;
			//~ exit;
		}
			
		$update_time = time();

		// Delete old jobs
		mysql_try(
			'DELETE FROM jobs
			WHERE
				job_exists = -1'
		);

		// Update old jobs to be removed on next run.
		// If the job is still not here on the next run
		// then it will be removed on the next run.
		mysql_try(
			'UPDATE jobs
			SET
				job_exists = -1
			WHERE
				job_exists = 0'
		);

		// Update jobs, HITs_available
		// Update last_update table (2 fields)
		if ($this->jobs_are_modified) {
			$result = mysql_try(
				'UPDATE last_update
				SET
					HITs_available	= '.$update_time.',
					jobs			= '.$update_time.'
				WHERE
					id = 1'
			);
			// Is the table empty?
			// This only ought to occur when first
			// creating the scraper!
			// Use @mysqli_affected_rows to hide warnings
			if (mysqli_affected_rows($mysqli) === 0) {
				mysql_try(
					'INSERT INTO last_update
					SET
						HITs_available	= '.$update_time.',
						jobs			= '.$update_time.',
						id				= 1'
				);
			}
		}
		// Else update HITs_available
		else {	
			// update last_update table (1 field)
			$result = mysql_try(
				'UPDATE last_update
				SET
					HITs_available = '.$update_time.'
				WHERE
					id = 1'
			);
			// Is the table empty?
			// This only ought to occur when first
			// creating the scraper!
			// Use @mysqli_affected_rows to hide warnings
			if (mysqli_affected_rows($mysqli) === 0) {
				mysql_try('
					INSERT INTO last_update
					SET
						HITs_available	= '.$update_time.',
						jobs			= '.$update_time.',
						id				= 1
				');
			}
			
		}

		$this->update_sj();
	}
	function update_sj() {
		/**
		 * Post to [redacted].com
		 */
		global $mysqli;
		$query = '
			SELECT
				jobs.id as i,
				jobs.time_posted as p,
				jobs.time_last_update as u,
				jobs.HITs_available as h,
				jobs.on_page_number as o,
				
				parent.title as t,
				parent.requester_id as r,
				parent.requester_name as n,
				parent.groupId as g,
				parent.reward as w,
				parent.time_allotted as a,
				parent.description as d,
				parent.qualifications as q
			FROM `jobs` jobs
			LEFT JOIN `jobs_history_index` parent ON jobs.id_jobs_history_index = parent.id
			ORDER BY
				jobs.time_posted
			ASC
		';
		$res = mysql_try($query);
		if (mysqli_num_rows($res) > 0) {
			$rows = array();
			while($row = mysqli_fetch_assoc($res)) {
				$rows[] = $row;
			}
			$rows = json_encode($rows);
		} else {
			$rows = '[]';
		}

		$rslt = $mysqli->query('SELECT * FROM last_update WHERE id = 1') or die(mysqli_error($mysqli));
		$t = mysqli_fetch_array($rslt);
		$t = urlencode($t['HITs_available'].','.$t['jobs']);

		$ch = curl_init(); 
		curl_setopt($ch, CURLOPT_URL, 'http://[redacted].com/AMT/new.php');
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20); //
		curl_setopt($ch, CURLOPT_TIMEOUT, 20); //
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, ["fFjbHoxpPdkEbnspVdoSgisN" => urlencode($rows), "t" => $t]);
		$c = curl_exec($ch);
		curl_close($ch);
		
		echo '[redacted].com response:'.$c."\n";
		echo 'Scrape complete at '.date('h:i:s')."\n";
		echo $this->echo_memory_usage();
		echo 'Begin alerts'."\n";
		$this->do_alerts();
	}
	function do_alerts() {
		// DO NOT CLOSE.
		//~ mysqli_close();
		
		$alerter = new alerts($this->run_start_time, $this->jobs_are_modified, $this->array_jobs_jhid);
		
		// When this is complete...
		echo 'Run completed at '.date('h:i:s')."\n";
		
		// Save time to log file.
		file_put_contents('/var/www/html/mturk/.scripts/AMT_Batch/cron_status', 'complete '.time());
		
	}
}

/**
 * Class alerts
 * ALERTS
 * 
 * 
 * 
 * 
 * 
 * 
**/
class alerts {

	public $email_alerts = array();
	public $wakeup_alerts = array();
	
	private $jobsDb_requesterIds; // full db rows
	private $jobsDb_hashedRidTitles; // full db rows
	
	private $jobsDb_requesterIdsArray = array();
	private $jobsDb_hashedRidTitleArray = array(); // old?
	
	private $alarmsInactive_requesterIds;
	private $alarmsInactive_tasks;
	
	private $alarmsActive_requesterIds = array();
	private $alarmsActive_tasks = array();
		
	public $alarmsEmailEnabled_requesterIds;
	public $alarmsEmailEnabled_tasks;
	
	private $script__starttime;
	private $script__new_jobs;
	private $script__array_current_jobs;
	
	private $debug = true;
	
	/**
	 * function __construct
	 * 
	 */
	function __construct($start, $jobs_are_modified, $array_jobs_jhid) {
		global $mysqli;
		
		$this->script__starttime = $start;				// these two are for email only!
		$this->script__new_jobs = $jobs_are_modified;	//
		$this->script__array_current_jobs = $array_jobs_jhid;	//
		
		// Debug
		echo 'script__starttime:' . $this->script__starttime . "\n";
		echo 'script__new_jobs:' .  $this->script__new_jobs . "\n";
		// End Debug
		
		//~ // Connect
		//~ $this->cn();
		
		// 
		$this->jobs__getExists();
		$this->alerts__selectActiveAlerts(); //
		
		$this->alerts__resetAlerts();
		
		$this->alerts__selectActiveAlerts(); //
		$this->alerts__selectInactiveAlerts();
		$this->alerts__selectEmailEnabled();
		
		$this->alerts__initWakeAlerts(); // 
		$this->alerts__initEmailAlerts(); //
		
		//
		$this->exec_alarms();// exec alarms
		
		//
		$this->check_misc_alarms();
		
		// Close mysql
		mysqli_close($mysqli);
	}
	
	function check_misc_alarms() {
		//~ // Grocery ROI
		//~ // Falls below threshold
		//~ $rslt = mysql_query('
			//~ SELECT
				//~ parent.requester_name as requester_name,
				//~ parent.title as title
			//~ FROM AMT_Batch.jobs
			//~ LEFT JOIN AMT_Batch.jobs_history_index parent ON jobs.id_jobs_history_index = parent.id
			//~ WHERE
				//~ requester_name="Grocery_ROI" &&
				//~ title="Collect Product Information" &&
				//~ HITs_available>15000
		//~ ') or die(mysql_error());
		//~ if (mysql_num_rows($rslt) == 0) {
			//~ echo shell_exec('/usr/bin/gnome-mplayer --display=:0.0 --volume 100 --random --playlist "/var/www/html/media/audio/AMT ALERT PLAYLIST.pls"');
		//~ }
		
		// WeFi
		// Goes above
		//~ $rslt = mysql_query('
			//~ SELECT
				//~ parent.requester_name as requester_name,
				//~ parent.title as title
			//~ FROM AMT_Batch.jobs
			//~ LEFT JOIN AMT_Batch.jobs_history_index parent ON jobs.id_jobs_history_index = parent.id
			//~ WHERE
				//~ requester_name="WeFi" &&
				//~ title="Create polygons in Google Earth" &&
				//~ HITs_available>=200
		//~ ') or die(mysql_error());
		//~ if (mysql_num_rows($rslt) > 0) {
			//~ $this->set_volume();
			//~ echo shell_exec('/usr/bin/gnome-mplayer --display=:0.0 --volume 100 --random --playlist "/var/www/html/media/audio/AMT ALERT PLAYLIST.pls"');
			//~ mail_alert('WeFi (Threshold Alert)', 'WeFi (Threshold Alert)', '-'); // req., title, HA
			//~ echo shell_exec('/usr/bin/notify-send --expire-time=4000 "WeFi"');
		//~ }
	}
	
	function cn() {
		//~ $mysql_host = "localhost"; // localhost
		//~ $mysql_database = "AMT_Batch";
		//~ $mysql_user = "root";
		//~ $mysql_password = "[redacted]";
		//~ @mysql_connect($mysql_host,$mysql_user,$mysql_password) or die(mysql_error());
		//~ //UTF-8
		//~ mysql_query("SET names 'utf8'"); 
	}
	
	/**
	 * function jobs__getExists
	 * 
	 * This eventually will filter to add wake alert /
	 * add email alert arrays.
	 * 
	 * @array wakeup_alerts
	 * 		- Has unique keys.
	 * 
	 * AUG8 2014 change keys from .jobs DB row "id" to 
	 * .jobs DB row "id_jobs_history_index". This could be any
	 * unique aspect of the row (including hash_id).
	 */
	function jobs__getExists() {
		
		$this->jobsDb_requesterIds = array();
		$this->jobsDb_hashedRidTitles = array();
		
		foreach ($this->script__array_current_jobs as $jhid => $task) {		
			$this->jobsDb_requesterIds		[$task['requester_id']]					= $task;
			$this->jobsDb_hashedRidTitles	[$task['requester_id'].$task['title']]	= $task;
		}
	}
	
	/**
	 * function alerts__selectActiveAlerts
	 * 
	 */
	function alerts__selectActiveAlerts() {
		global $mysqli;
		
		// Reset these arrays, if necessary.
		$this->alarmsActive_requesterIds = array();
		$this->alarmsActive_tasks = array();
		
		// Active requester alarms
		$rslt = $mysqli->query('
			SELECT * FROM
				AMT_Alerts.requesters
			WHERE
				alarm_active=1 &&
				wakeupAlertDefault=1
		') or die(mysqli_error($mysqli));//emailAlertDefault != 0
		while ($row = mysqli_fetch_array($rslt)) {
			$this->alarmsActive_requesterIds[$row['requesterUserId']]	= $row;
		}
		// Active task alarms
		$rslt = $mysqli->query('
			SELECT * FROM
				AMT_Alerts.tasks
			WHERE
				alarm_active=1 &&
				wakeupAlert=1
		') or die(mysqli_error($mysqli));
		while ($row = mysqli_fetch_array($rslt)) {
			$this->alarmsActive_tasks[$row['titleRequesterHash']]		= $row;
		}
	}
	
	/**
	 * function alerts__selectInactiveAlerts
	 *
	 * Wake Alerts that are >Inactive, >Enabled
	 * 
	 * @ alarmsInactive_requesterIds	array:
	 * @ alarmsInactive_tasks			array:
	 */
	function alerts__selectInactiveAlerts() { // threshold
		global $mysqli;
		
		$this->alarmsInactive_requesterIds = array();
		$this->alarmsInactive_tasks = array();
		
		// Inactive requester alarms
		$rslt = $mysqli->query('
			SELECT * FROM
				AMT_Alerts.requesters
			WHERE
				alarm_active=0 &&
				wakeupAlertDefault=1
		') or die(mysqli_error($mysqli));//emailAlertDefault != 0
		while ($row = mysqli_fetch_array($rslt)) {
			$this->alarmsInactive_requesterIds[$row['requesterUserId']]	= $row;
		}
		// Inactive task alarms
		$rslt = $mysqli->query('
			SELECT * FROM
				AMT_Alerts.tasks
			WHERE
				alarm_active=0 &&
				wakeupAlert=1
		') or die(mysqli_error($mysqli));
		while ($row = mysqli_fetch_array($rslt)) {
			$this->alarmsInactive_tasks[$row['titleRequesterHash']]		= $row;
		}
	}
	
	/**
	 * function alerts__selectEmailEnabled
	 * 
	 * Email Alerts that are >Enabled
	 * 
	 * @ alarmsEmailEnabled_requesterIds	array:
	 * @ alarmsEmailEnabled_tasks			array:
	 */
	function alerts__selectEmailEnabled() {
		global $mysqli;
		
		$this->alarmsEmailEnabled_requesterIds = array();
		$this->alarmsEmailEnabled_tasks = array();
		
		// Select
		$rslt = $mysqli->query('
			SELECT * FROM
				AMT_Alerts.requesters
			WHERE
				emailAlertDefault=1
		') or die(mysqli_error($mysqli));
		while ($row = mysqli_fetch_array($rslt)) {
			$this->alarmsEmailEnabled_requesterIds[ $row['requesterUserId'] ] = $row;
		}
		// Select
		$rslt = $mysqli->query('
			SELECT * FROM
				AMT_Alerts.tasks
			WHERE
				emailAlert=1
		') or die(mysqli_error($mysqli));
		while ($row = mysqli_fetch_array($rslt)) {
			$this->alarmsEmailEnabled_tasks[ $row['titleRequesterHash'] ] = $row;
		}
	}
	
	/**
	 * function alerts__resetAlerts
	 * Runs only once per run
	 * threshold only!
	 * Resets applicable alarm_active
	 * 
	 * Update 22 Aug 2014
	 * Change $tmp_db_taskarr. "UPDATE AMT_Alerts.tasks
	 * SET alarm_active=0" fixed.
	 * Error is that jobsDb_hashedRidTitles is the array
	 * NOT jobsDb_hashedRidTitleArray LOL.
	 */
	function alerts__resetAlerts() {
		global $mysqli;
		
		// Set requester/task alarms off for all non-existent jobs
		$tmp_db_reqarr = array();
		foreach ($this->jobsDb_requesterIds as $rid => $task) { 
			array_push($tmp_db_reqarr,  mysqli_real_escape_string($mysqli, $task['requester_id']));
		}
		$tmp_db_taskarr = array();
		foreach ($this->jobsDb_hashedRidTitles as $titleRequesterHash => $key) {
			array_push($tmp_db_taskarr, mysqli_real_escape_string($mysqli, $titleRequesterHash));
		}
		
		// (requesters)
		$rslt = $mysqli->query('
			UPDATE
				AMT_Alerts.requesters
			SET
				alarm_active=0
			WHERE
				requesterUserId NOT IN
					(
						"'.implode('","', $tmp_db_reqarr).'"
					)
		') or die(mysqli_error($mysqli));
		
		// (tasks)
		$query = '
			UPDATE
				AMT_Alerts.tasks
			SET
				alarm_active=0
			WHERE
				titleRequesterHash NOT IN
					(
						"'.implode('","', $tmp_db_taskarr).'"
					)
			';
		$rslt = $mysqli->query(
			$query
		) or die(mysqli_error($mysqli));
		
		// Set requester/task alarms off for minTasks thresholds
		
		// Requesters
		// $this->jobsDb_requesterIds[$row['requester_id']] = $row;
		// exists?
		foreach ($this->jobsDb_requesterIds as $rid => $task) {
			if (array_key_exists($rid, $this->alarmsActive_requesterIds)) {
				$m = $this->alarmsActive_requesterIds[$rid];
				if ($m['wakeupAlertDefault'] == '1') { // is it beneath threshold?
					if ($m['minTasksThreshold'] > $task['HITs_available']) {
						$rslt = $mysqli->query('
							UPDATE
								AMT_Alerts.requesters
							SET
								alarm_active=0
							WHERE
								id="'.mysqli_real_escape_string($mysqli, $m['id']).'"
						') or die(mysqli_error($mysqli));
					}
				}
			}
		}
		// Tasks
		// $this->jobsDb_hashedRidTitles[$row['requester_id'].$row['title']] = $row;
		// exists?
		foreach ($this->jobsDb_hashedRidTitles as $hash => $task) {
			if (array_key_exists($hash, $this->alarmsActive_tasks)) {
				$m = $this->alarmsActive_tasks[$hash];
				if ($m['wakeupAlert'] == '1') {
					if ($m['minTasksThreshold'] > $task['HITs_available']) { // is it beneath threshold?
						$rslt = $mysqli->query('
							UPDATE
								AMT_Alerts.tasks
							SET
								alarm_active=0
							WHERE
								id="'.mysqli_real_escape_string($mysqli, $m['id']).'"
						') or die(mysqli_error($mysqli));
					}
				}
			}
		}
	}
	
	/**
	 * function alerts__initEmailAlerts
	 * 
	 * Email alarms. These are tripped when job first appears in top 20 pages.
	 * 
	 * @ script__starttime					int
	 * @ script__new_jobs					bool
	 * @ alarmsEmailEnabled_tasks			array
	 * @ alarmsEmailEnabled_requesterIds	array
	 */
	function alerts__initEmailAlerts() {//
		// Debug
		if ($this->debug) {
			echo 'Count $this->alarmsEmailEnabled_tasks:'			. count($this->alarmsEmailEnabled_tasks) . "\n";
			echo 'Count $this->alarmsEmailEnabled_requesterIds:'	. count($this->alarmsEmailEnabled_requesterIds) . "\n";
		}
		// alarmsEmailEnabled_tasks			====key=is==== $task['requester_id'].$task['title'];
		// alarmsEmailEnabled_requesterIds	====key=is==== $task['requester_id'];
		// Run when new jobs
		if ($this->script__new_jobs) {
			// Debug
			echo 'Checking new task(s) for email alerts.' . "\n";
			// tasks
			foreach ($this->jobsDb_hashedRidTitles as $hash => $task) {
				if ($task['time_posted']*1 != $this->script__starttime*1) // jobs that have arrived on this run only!
					continue;
				// Debug
				if ($this->debug) {
					echo 'Checking task for email:' . $task['title'] . "\n";
				}
				// alarmsEmailEnabled_tasks
				if (array_key_exists($hash, $this->alarmsEmailEnabled_tasks)) {
					$row = $this->alarmsEmailEnabled_tasks[$hash];
					if ($task['HITs_available'] > $row['minTasksThreshold']) {
						echo 'Add email alert for task:' . $hash . "\n";
						$this->alerts__addEmailAlert($task);
					}
				}
				// alarmsEmailEnabled_requesterIds
				if (array_key_exists($task['requester_id'], $this->alarmsEmailEnabled_requesterIds)) {
					$row = $this->alarmsEmailEnabled_requesterIds[$task['requester_id']];
					if ($task['HITs_available'] > $row['minTasksThreshold']) {
						echo 'Add email alert for requester:' . $task['requester_id'] . "\n";
						$this->alerts__addEmailAlert($task);
					}
				}
			}
		}
	}
	
	/**
	 * function alerts__initWakeAlerts
	 * 
	 * ...Check jobs in db against alerts
	 * where the alert is set to alarm_active.
	 * 
	 * ...Threshold check only!
	 * 
	 * Do not reset the inactive/active status during the run
	 * through checks. This will allow Req.1-job1 to be wakeup
	 * and Req.1-job2 to also be wakeup?
	 * 
	 * NOTE:
	 * New Nov11 2014: Do not check for an alert if task is on page 20, i.e. the last page
	 * of results.
	 * 
	 */
	function alerts__initWakeAlerts() {//thresh
		global $mysqli;
		
		echo 'count($this->jobsDb_requesterIds):'		.count($this->jobsDb_requesterIds). "\n";
		echo 'count($this->jobsDb_hashedRidTitles):'	.count($this->jobsDb_hashedRidTitles). "\n";
		
		/**
		 * Requesters
		 * $this->jobsDb_requesterIds
		 * 
		 * TODO:
		 * DOES THIS TASK FALL OUTSIDE OF REQUESTER-WIDE ALERT? I.e.,
		 * is their an overriding task alert?
		 * 
		 * NOTE:
		 * Is it on PAGE 20?
		 */
		foreach ($this->jobsDb_requesterIds as $rid => $task) {
			if (array_key_exists($rid, $this->alarmsInactive_requesterIds)) {
				// Are there task alerts for this task that override requester alert?
				$hash = $task['requester_id'].$task['title'];
				echo 'Checking task in $this->alarmsInactive_tasks ('.$hash.')' . "\n";
				if (array_key_exists($hash, $this->alarmsInactive_tasks)) {
					$alrm_task = $this->alarmsInactive_tasks[$hash];
					
					if ($this->debug) {
						echo 'Task exists in $this->alarmsInactive_tasks!' . "\n";
						echo $alrm_task['wakeupAlert'];
						echo $alrm_task['minTasksThreshold'];
					}
					if ($alrm_task['wakeupAlert'] == '1') { // && $task['HITs_available'] > $alrm_task['minTasksThreshold']
						// This will be added below in the Tasks loop.
						echo 'Found a task that overrides requester alert. Going to try a task alert. (Title:'.$task['title'].')' . "\n";
						continue;
					}
				}
				
				$alrm = $this->alarmsInactive_requesterIds[$rid];
				if ($alrm['wakeupAlertDefault'] == '1') {
					// does it pass threshold? // This is (HITs_available > minTasksThreshold) rather than vice versa!
					if ($task['HITs_available'] > $alrm['minTasksThreshold']) {
						$this->alerts__addWakeAlert($task);
						$rslt = $mysqli->query('
							UPDATE
								AMT_Alerts.requesters
							SET
								alarm_active=1
							WHERE
								requesterUserId="'.mysqli_real_escape_string($mysqli, $alrm['requesterUserId']).'"
						') or die(mysqli_error($mysqli));
						echo 'updated requesters.alarm_active=1 ('.$task['title'].')'."\n";
					}
				}
			}
		}
		/**
		 * Tasks
		 * $this->jobsDb_hashedRidTitles
		 * 
		 * REMOVE A WAKE ALERT IF A JOB OVERRIDES A REQUESTER!
		 * ...
		 * For example:
		 * RequesterA has a wake alert regardless. However RequesterA has TaskB
		 * which is set to wake alert but MinTasksThreshold=1000. TaskB wake
		 * alert must override RequesterA wake alert.
		 */
		// Debug
		if ($this->debug) {
			echo 'Checking tasks against $this->alarmsInactive_tasks'."\n";
		}
		foreach ($this->jobsDb_hashedRidTitles as $title_req_hash => $task) {
			if (array_key_exists($title_req_hash, $this->alarmsInactive_tasks)) {
				// Debug
				if ($this->debug) {
					echo 'Task ' . $title_req_hash . ' exists in $this->alarmsInactive_tasks'. "\n";
				}
				$alrm = $this->alarmsInactive_tasks[$title_req_hash];
				if ($alrm['wakeupAlert'] == '1') {
					// does it pass threshold? // This is (HITs_available > minTasksThreshold) rather than vice versa!
					if ($task['HITs_available'] > $alrm['minTasksThreshold']) {
						$this->alerts__addWakeAlert($task); // UPDATE DB !
						$rslt = $mysqli->query('
							UPDATE
								AMT_Alerts.tasks
							SET
								alarm_active=1
							WHERE
								titleRequesterHash="'.mysqli_real_escape_string($mysqli, $alrm['titleRequesterHash']).'"
						') or die(mysqli_error($mysqli));
						echo 'updated alarm_active=1(tasks)'.$task['title']."\n";
					}
				} else {
					
				}
			}
		}
	}
	
	/**
	 * function alerts__addWakeAlert
	 * function alerts__addEmailAlert
	 * 
	 * ...helper functions.
	 * 
	 * Change from "id" to "jobs_history_index_ID". Aug8 2014.
	 * 
	 */
	function alerts__addWakeAlert($task) {//
		if (!array_key_exists($task['jobs_history_index_ID'], $this->wakeup_alerts)) {
			$this->wakeup_alerts[$task['jobs_history_index_ID']] = $task;
			
			// This adds an email alert in addition to wakeup.
			//
			//
			$this->alerts__addEmailAlert($task);
			echo 'Adding wake/email alert for ' . print_r($task);
		}
	}
	function alerts__addEmailAlert($task) {
		if (!array_key_exists($task['jobs_history_index_ID'], $this->email_alerts)) {
			$this->email_alerts[$task['jobs_history_index_ID']] = $task;
		}
	}
	/**
	 * function removeWakeAlert
	 * function removeEmailAlert
	 * 
	 * @@->	destroy a single element of an array
	 * @@->	unset($bar['quux']);
	 */
	function removeWakeAlert($task) {//
		if (!array_key_exists($task['jobs_history_index_ID'], $this->wakeup_alerts)) {
			unset(
				$this->wakeup_alerts[ $task['jobs_history_index_ID'] ]
			);
			// This removes an email alert.
			//
			//
			$this->removeEmailAlert($task);
			echo 'Removed wake/email alert for ' . print_r($task);
		}
	}
	function removeEmailAlert($task) {
		if (!array_key_exists($task['jobs_history_index_ID'], $this->email_alerts)) {
			unset(
				$this->email_alerts[ $task['jobs_history_index_ID'] ]
			);
		}
	}
	
	/**
	 * function set_volume
	 */
	function set_volume() {
		// AUDIO UP/DOWN
		$vol = shell_exec('amixer -D pulse sset Master 100%+'); // Set up 100%
		if ($vol != '') {
			echo 'VOLUME NOT BLANK:'.$vol."\n";
		}
		$vol = shell_exec('amixer -D pulse sset Master 15%-'); // Bring down 15%
		if ($vol != '') {
			echo 'VOLUME NOT BLANK:'.$vol."\n";
		}
	}
	
	/**
	 * function exec_alarms
	 */
	function exec_alarms() {
		
		//
		echo 'count $this->wakeup_alerts:' . count($this->wakeup_alerts)."\n";
		echo 'count $this->email_alerts:'  . count($this->email_alerts )."\n";
		// Email
		foreach ($this->email_alerts as $jhid => $task) {
			echo 'Sending email for:' . $jhid . ' ' . $task['title'] . "\n";
			
			$this->mail_alert(
				$task['requester_name'],
				$task['title'],
				$task['HITs_available']
			);
		}
		// Play
		if (count($this->wakeup_alerts) > 0) { //"play [filename]" also works?
			$this->set_volume();
			
			// Format a string for requester / title
			$wstr = [];
			foreach ($this->wakeup_alerts as $jhid => $task) {
				array_push($wstr, '[' . $task['requester_name'] . '|' . $task['title'] . '|' . $task['HITs_available'] . ']');
			}
			$wstr = escapeshellarg(implode("\n", $wstr)); // Note, if "\n" does not work, try $x="...[newline]..."
			
			// New method, Oct. 2015.
			// See below for note...
			// Shuffle, & Playlist.
			// Volume down: From 100. Oct 10 2016. Because neighbors...
			// Change to ... 90
			// Feb 8: Try > /dev/null & to background mplayer.
			shell_exec('/usr/bin/mplayer -channels 2 -af-add export=/tmp/mplayer-af_exportagetpy:512 -quiet -slave -noidle -noconsolecontrols -nostop-xscreensaver -identify -volume 90 -softvol -msglevel all=5 -osdlevel 0 -delay 0 -subdelay 0 -subpos 0 -sub-fuzziness 0 -wid 0x3a0001f -brightness 0 -contrast 0 -hue 0 -saturation 0 -alang English,eng,en -slang English,eng,en -nomsgcolor -nomsgmodule -nokeepaspect -ass -embeddedfonts -ass-font-scale 1 -ass-color ffffff00 -shuffle -playlist "/var/www/html/media/audio/AMT ALERT PLAYLIST.pls" > /dev/null &');
			
			// Python popup window.
			// 		((Going to turn off for now (Aug 20 2016) until...))
			// Use & to background this task...
			// Fix:
			//		Jan2018: Remove final semi-colon, because it is a
			//			syntax error!
			//			Also, background process needs redirect > /dev/null.
			//			https://stackoverflow.com/questions/14555971/php-exec-background-process-issues
			exec('export DISPLAY=:0; cd /home/_redacted_/; ./python-window-popup.py "'.$wstr.'" > /dev/null &');
			
			
			// TESTING...
			//~ $c = 'export DISPLAY=":0.0"';
			//~ echo shell_exec('export DISPLAY=":0"'); // does not work..... ~> https://mail.python.org/pipermail/python-list/2013-December/663528.html
			//~ echo shell_exec('export DISPLAY=":0.0"'); // ?
			// Deactivate screensaver. This is not so much to "show" users but rather to ensure python-window-popup.py executes.
			//echo shell_exec('/usr/bin/gnome-screensaver-command -d');
			// Display a Python pop-up window that does, essentially, 'killall mplayer' upon clicking the "Ok" button.
			//~ echo shell_exec($c . ' && /home/_redacted_/python-window-popup.py "'.$wstr.'"'); // Note "/" rather than "./".
			//~ echo shell_exec('./home/_redacted_/python-window-popup.py "'.$wstr.'"'); // Note "/" rather than "./".
			//~ exec('export DISPLAY=":0.0"; /usr/bin/gnome-screensaver-command -d; cd /home/_redacted_/; ./python-window-popup.py "'.$wstr.'"');
			//~ shell_exec('export DISPLAY=":0.0"; /usr/bin/gnome-screensaver-command -d; cd /home/_redacted_/; ./python-window-popup.py "'.$wstr.'"');
			//~ exec('export DISPLAY=:0; /usr/bin/gnome-screensaver-command -d;');
			//~ exec('cd /home/_redacted_/; ./python-window-popup.py "'.$wstr.'";');
			//~ exec('export DISPLAY=:0; /usr/bin/gnome-screensaver-command -d;cd /home/_redacted_/; ./python-window-popup.py "'.$wstr.'";');
			
			// This stopped working Oct. 2015 because of a softness issue.
			// Why? Mplayer binary works. Gnome-mplayer does not work.
			// So, moving to mplayer temporarily, for now.
			//echo shell_exec('/usr/bin/gnome-mplayer -q --display=:0.0 --volume 100 --random --playlist "/var/www/html/media/audio/AMT ALERT PLAYLIST.pls"');
		}
	}
	function mail_alert($r, $t, $h) {
		// TODO: Turn on / off phone alerts using a configuration, or
		// other, file. Another idea is configure for which hours to 
		// message, e.g., outside of working hours.
		
		$m = substr($t, 0, 148) . " \n$h";
		$m = wordwrap($m, 70, "\r\n");
		
		$full_subject = wordwrap("***$r-$t", 70, "\r\n");
		
		$a1 = mail("_redacted_.com",	$full_subject, $m);
		//$a2 = mail("[redacted]",		$full_subject, $m); // FORWARD?
		//$a3 = mail("[redacted]",		$full_subject, $m);
		
		// _redacted_'s phone
		// Turned off Oct. 13, 2019
		//$a4 = mail("[redacted]", $full_subject, substr($m, 0, 160));
		
		// _redacted_'s phone
		// Turned off Feb. 22, 2018
		//$a4 = mail("[redacted]", $full_subject, substr($m, 0, 160));
		
	}
}
echo 'EXITING at '.date('h:i:s')."\n";


//~ exit;

/**
 * OTHER ALERTS .......
 * 
 * 
 * 
 * 
 */

// HOWARD EIFERMAN
//~ $rslt = mysql_query('SELECT * FROM AMT_Batch.jobs WHERE requester_name="HOWARD EIFERMAN" && HITs_available<4600');
//~ if (mysql_num_rows($rslt) > 0) {
	//~ shell_exec('/usr/bin/gnome-mplayer --display=:0.0 --volume 100 --random --playlist "/var/www/html/media/audio/AMT ALERT PLAYLIST.pls"');
//~ }

// Bart Jansen
//~ $rslt = mysql_query('SELECT * FROM AMT_Batch.jobs WHERE requester_name="Bart Jansen" && HITs_available<3700');
//~ if (mysql_num_rows($rslt) > 0) {
	//~ shell_exec('/usr/bin/gnome-mplayer --display=:0.0 --volume 100 --random --playlist "/var/www/html/media/audio/AMT ALERT PLAYLIST.pls"');
//~ }

// kronopin
//~ $rslt = mysql_query('SELECT * FROM jobs WHERE requester_name="KronoPin" && HITs_available>6000');
//~ if (mysql_num_rows($rslt) > 0) {echo 'kr';
	//~ echo shell_exec('/usr/bin/gnome-mplayer --display=:0.0 --volume 100 --random --playlist "/var/www/html/media/audio/AMT ALERT PLAYLIST.pls"');
//~ }

//~ // grocery_roi
//~ if (date("H") * 1 >= 10) {
	//~ $rslt = mysql_query('SELECT * FROM jobs WHERE requester_name="Grocery_ROI" && HITs_available>=180');
	//~ if (mysql_num_rows($rslt) > 0) {//echo 'kr';
		//~ echo shell_exec('/usr/bin/gnome-mplayer --display=:0.0 --volume 100 --random --playlist "/var/www/html/media/audio/AMT ALERT PLAYLIST.pls"');
	//~ }
//~ }

// Newline
//~ if (date("H") * 1 >= 3) {
	//~ $rslt = mysql_query('SELECT * FROM jobs WHERE requester_name="Newline Financial" && HITs_available>=1400');
	//~ if (mysql_num_rows($rslt) > 0) {
		//~ echo shell_exec('/usr/bin/gnome-mplayer --display=:0.0 --volume 100 --random --playlist "/var/www/html/media/audio/AMT ALERT PLAYLIST.pls"');
	//~ }
//~ }
// Jeremy von Halle
//~ if (date("H") * 1 >= 11) {
	//~ $rslt = mysql_query('SELECT * FROM jobs WHERE requester_name="Jeremy von Halle" && HITs_available>=8000');
	//~ if (mysql_num_rows($rslt) == 0) {
		//~ echo shell_exec('/usr/bin/gnome-mplayer --display=:0.0 --volume 100 --random --playlist "/var/www/html/media/audio/AMT ALERT PLAYLIST.pls"');
	//~ }
//~ }
// Brittany Graham
//~ $rslt = mysql_query('SELECT * FROM jobs WHERE requester_name="ReachLocal" && HITs_available>=20000 && HITs_available<=54000');
//~ if (mysql_num_rows($rslt) > 0) {
	//~ echo shell_exec('/usr/bin/gnome-mplayer --display=:0.0 --volume 100 --random --playlist "/var/www/html/media/audio/AMT ALERT PLAYLIST.pls"');
//~ }

// Grocery_ROI > 20k
//~ $rslt = mysql_query('
	//~ SELECT
		//~ parent.requester_name as requester_name,
		//~ parent.title as title
	//~ FROM AMT_Batch.jobs
	//~ LEFT JOIN AMT_Batch.jobs_history_index parent ON jobs.id_jobs_history_index = parent.id
	//~ WHERE
		//~ requester_name="Grocery_ROI" &&
		//~ title="Collect Product Information" &&
		//~ HITs_available>20000
//~ ') or die(mysql_error());
//~ if (mysql_num_rows($rslt) == 0) {
	//~ echo shell_exec('/usr/bin/gnome-mplayer --display=:0.0 --volume 100 --random --playlist "/var/www/html/media/audio/AMT ALERT PLAYLIST.pls"');
//~ }

// Visual Search Requester > ...
//~ $rslt = mysql_query('
	//~ SELECT
		//~ parent.requester_name as requester_name,
		//~ parent.title as title
	//~ FROM AMT_Batch.jobs
	//~ LEFT JOIN AMT_Batch.jobs_history_index parent ON jobs.id_jobs_history_index = parent.id
	//~ WHERE
		//~ requester_name="Visual Search Requester" &&
		//~ title="Find associated product information for a given barcode value" &&
		//~ HITs_available>0
//~ ') or die(mysql_error());
//~ if (mysql_num_rows($rslt) > 0 && date("H") * 1 >= 3) {
	//~ echo shell_exec('/usr/bin/gnome-mplayer --display=:0.0 --volume 100 --random --playlist "/var/www/html/media/audio/AMT ALERT PLAYLIST.pls"');
//~ }

// Qian Wang > 
//~ $rslt = mysql_query('
	//~ SELECT
		//~ parent.requester_name as requester_name,
		//~ parent.title as title
	//~ FROM AMT_Batch.jobs
	//~ LEFT JOIN AMT_Batch.jobs_history_index parent ON jobs.id_jobs_history_index = parent.id
	//~ WHERE
		//~ requester_name="Qian Wang" &&
		//~ title="Find the Website URL for School Districts" &&
		//~ HITs_available>3500
//~ ') or die(mysql_error());
//~ if (mysql_num_rows($rslt) == 0) {
	//~ echo shell_exec('/usr/bin/gnome-mplayer --display=:0.0 --volume 100 --random --playlist "/var/www/html/media/audio/AMT ALERT PLAYLIST.pls"');
//~ }

// Crowd Task
// Falls below threshold
//~ $rslt = mysql_query('
	//~ SELECT
		//~ parent.requester_name as requester_name,
		//~ parent.title as title
	//~ FROM AMT_Batch.jobs
	//~ LEFT JOIN AMT_Batch.jobs_history_index parent ON jobs.id_jobs_history_index = parent.id
	//~ WHERE
		//~ requester_name="Crowd Task" &&
		//~ title="Find the company website" &&
		//~ HITs_available>180000
//~ ') or die(mysql_error());
//~ if (mysql_num_rows($rslt) == 0) {
	//~ echo shell_exec('/usr/bin/gnome-mplayer --display=:0.0 --volume 100 --random --playlist "/var/www/html/media/audio/AMT ALERT PLAYLIST.pls"');
//~ }

// Visual Search Requester
// Falls below threshold
//~ $rslt = mysql_query('
	//~ SELECT
		//~ parent.requester_name as requester_name,
		//~ parent.title as title
	//~ FROM AMT_Batch.jobs
	//~ LEFT JOIN AMT_Batch.jobs_history_index parent ON jobs.id_jobs_history_index = parent.id
	//~ WHERE
		//~ requester_name="Visual Search Requester" &&
		//~ title="Find associated product information for a given barcode value" &&
		//~ HITs_available>40000
//~ ') or die(mysql_error());
//~ if (mysql_num_rows($rslt) == 0) {
	//~ echo shell_exec('/usr/bin/gnome-mplayer --display=:0.0 --volume 100 --random --playlist "/var/www/html/media/audio/AMT ALERT PLAYLIST.pls"');
//~ }

//~ // Grocery ROI
//~ // Falls below threshold
//~ $rslt = mysql_query('
	//~ SELECT
		//~ parent.requester_name as requester_name,
		//~ parent.title as title
	//~ FROM AMT_Batch.jobs
	//~ LEFT JOIN AMT_Batch.jobs_history_index parent ON jobs.id_jobs_history_index = parent.id
	//~ WHERE
		//~ requester_name="Grocery_ROI" &&
		//~ title="Collect Product Information" &&
		//~ HITs_available>15000
//~ ') or die(mysql_error());
//~ if (mysql_num_rows($rslt) == 0) {
	//~ echo shell_exec('/usr/bin/gnome-mplayer --display=:0.0 --volume 100 --random --playlist "/var/www/html/media/audio/AMT ALERT PLAYLIST.pls"');
//~ }

//~ // Tom Overby
//~ // Falls below threshold
//~ $rslt = mysql_query('
	//~ SELECT
		//~ parent.requester_name as requester_name,
		//~ parent.title as title
	//~ FROM AMT_Batch.jobs
	//~ LEFT JOIN AMT_Batch.jobs_history_index parent ON jobs.id_jobs_history_index = parent.id
	//~ WHERE
		//~ requester_name="Tom Overby" &&
		//~ title="Find the Website Address for Companies" &&
		//~ HITs_available>14000
//~ ') or die(mysql_error());
//~ if (mysql_num_rows($rslt) == 0) {
	//~ echo shell_exec('/usr/bin/mplayer -channels 2 -af-add export=/tmp/mplayer-af_exportagetpy:512 -quiet -slave -noidle -noconsolecontrols -nostop-xscreensaver -identify -volume 100 -softvol -msglevel all=5 -osdlevel 0 -delay 0 -subdelay 0 -subpos 0 -sub-fuzziness 0 -wid 0x3a0001f -brightness 0 -contrast 0 -hue 0 -saturation 0 -alang English,eng,en -slang English,eng,en -nomsgcolor -nomsgmodule -nokeepaspect -ass -embeddedfonts -ass-font-scale 1 -ass-color ffffff00 -shuffle -playlist "/var/www/html/media/audio/AMT ALERT PLAYLIST.pls"');
//~ }



?>
