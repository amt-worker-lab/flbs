<?php



/**
 * Trevor Faden 
 ***/
set_time_limit(300); //5 min.

if (isset($_GET['n']) && isset($_GET['assignmentId'])) { //1
	$file = './TrevorFaden/' . $_GET['n']; // file -#
	if (file_put_contents($file, $_GET['assignmentId'])) {
		echo '1';
		chmod($file, 0777);
	}
	exit;
}
else if (isset($_GET['n']) && isset($_GET['cuname'])) { //2
	$e=1;
	$file = './TrevorFaden/' . $_GET['n']; // file - cu #
	if (file_exists($file)) {
		$x = file_get_contents($file);
		//$x = explode('|', $x);
		//if (count($x) == 1) {
		if (strpos($x, '|') === false) {
			if (!file_put_contents($file, '|'.$_GET['cuname'], FILE_APPEND)) {
				$e=0;
			}
		}
	}
	
	$_GET['cuname'] = str_replace("/", "_", $_GET['cuname']);
	$file = './TrevorFaden/' . $_GET['cuname']; // file - cu name
	if (!file_put_contents($file, $_GET['n'])) {
		$e=0;
	} else
		chmod($file, 0777);
	
	echo $e;
	exit;
}
else if (isset($_GET['remove']) && isset($_GET['n'])) { //3
	$file = './TrevorFaden/' . $_GET['n']; // file -#
	if (file_exists($file)) {
		$x = file_get_contents($file);
		if (strpos($x, '|') !== false) {
			$x=explode('|', $x);
			$f = $x[1];
			$f = str_replace("/", "_", $f);
			
			//$f = 'CallReport5300_March-2013_'.$x[1].'.pdf';
			//$f = 'CallReport5300_June-2013_'.$x[1].'.pdf';
			//$f = 'CallReport5300_September-2013_'.$x[1].'.pdf';
			//$f = 'CallReport5300_December-2013_'.$x[1].'.pdf';
			//~ $f = 'CallReport5300_June-2014_'.$x[1].'.pdf';
			//$f = 'CallReport5300_September-2014_'.$x[1].'.pdf';
			$f = 'CallReport5300_March-2015_'.$x[1].'.pdf';
			//echo $f;
			
			if (file_exists('./TrevorFaden/done/'.$f)) {
				rename('./TrevorFaden/done/'.$f, './TrevorFaden/remove/'.$f);
				chmod('./TrevorFaden/remove/'.$f, 0777);
				echo '1';
				//if (time() - filemtime('TrevorFaden/remove/'.$f) < 900) {
				//if (time() - filemtime('TrevorFaden/done/'.$f) < 900) {
				//	echo '1';
				//}
			}
		}
	}
	exit;
} else if (isset($_GET['n'])) { //4
	$file = './TrevorFaden/' . $_GET['n']; // assignmentId, name
	if (file_exists($file)) {
		$x=file_get_contents($file);
		$x = explode('|', $x);
		if (count($x) == 2) {
			echo $x[1];
		}
		/*if (count($x) == 3) {
			$y=file_get_contents('./TrevorFaden/'.$x[1]); // file, name, "|1" (done)
			if (preg_match('/\|1$/', $y) !== false) {
				$y=explode('|', $y);
				echo 
			}
		}*/
	}
	exit;
} else if (isset($argv[0])) { // cmd line
	$pid = pcntl_fork();
	if ($pid == -1) {
		 die('could not fork');
	} else if ($pid) {
		 // we are the parent
		 //pcntl_wait($status); //Protect against Zombie children
	} else {
		 // we are the child
		 echo 'we split!';
		/**
		 *  Trevor Faden handling websockets
		 * 
		 **/

		chdir('/var/www/html/mturk/.scripts/PHPWebSockets');
		require_once('websockets.php');
		chdir('/var/www/html/mturk/');
		class echoServer extends WebSocketServer {
		  //protected $maxBufferSize = 1048576; //1MB... overkill for an echo server, but potentially plausible for other applications.
		  
		  protected function process ($user, $message) {
			echo 'S';
			exec('./switchToNextChromeWindow.sh chrome');
			$this->send($user,$message);
		  }
		  
		  protected function connected ($user) {
			// Do nothing: This is just an echo server, there's no need to track the user.
			// However, if we did care about the users, we would probably have a cookie to
			// parse at this step, would be looking them up in permanent storage, etc.
		  }
		  
		  protected function closed ($user) {
			// Do nothing: This is where cleanup would go, in case the user had any sort of
			// open files or other objects associated with them.  This runs after the socket 
			// has been closed, so there is no need to clean up the socket itself here.
		  }
		}
			
		$echo = new echoServer("127.0.0.1","9000");

		try {
		  $echo->run();
		}
		catch (Exception $e) {
		  $echo->stdout($e->getMessage());
		}
	}
	
	$continue=true;
	while ($continue) {
		//old
		$directory='/var/www/html/mturk/TrevorFaden/done/';
		$iterator = new DirectoryIterator($directory);
		foreach ($iterator as $fileinfo) {
			if  (
					$fileinfo->isFile() &&
					preg_match('/\.pdf$/i', $fileinfo->getFilename()) &&
					!preg_match('/ \([\d]+\)\.pdf$/i', $fileinfo->getFilename())
				)
			{
				if (time() - $fileinfo->getMTime() > 900) { //7.5
					$f = $fileinfo->getFilename();
					rename('/var/www/html/mturk/TrevorFaden/done/'.$f, './TrevorFaden/remove/'.$f);
					chmod('./TrevorFaden/remove/'.$f, 0777);
				}
				//echo $fileinfo->getFilename()."\n";
				//$filename = $fileinfo->getFilename();
				//break;
			}
		}
	
		$directory='/var/www/html/mturk/TrevorFaden/pdf/';
		$filename = '';
		$iterator = new DirectoryIterator($directory);
		$files = array();
		foreach ($iterator as $fileinfo) {
			if  (
					$fileinfo->isFile() &&
					preg_match('/\.pdf;?$/i', $fileinfo->getFilename()) &&
					!preg_match('/ \([\d]+\)\.pdf;?$/i', $fileinfo->getFilename())
				)
			{
				$filename = $fileinfo->getFilename();
				$mtime = $fileinfo->getMTime();
				if (strpos($filename,'.pdf;') !== false) {
					$filename = str_replace('.pdf;', '.pdf',$filename);
					rename('./TrevorFaden/pdf/'.$filename.';','./TrevorFaden/pdf/'.$filename);
					chmod('./TrevorFaden/pdf/'.$filename, 0777);
				}
				$files[$mtime] = $filename;
			}
		}
		
		// If there are no files to upload just now,
		// then wait for one second.
		if (count($files) === 0) {
			echo '.';
			sleep(1);
			continue;
		}
		
		// Still here? Sort the files by mtime.
		ksort($files); // sorts ascending?
		
		// The first file name.
		foreach ($files as $key => $value) {
			$filename = $value;
			break;
		}
		echo $filename."\n";
		
		// Get the basename. This is CU name.
		// For example, "FIRESTONE" or "A & E".
		$basename = preg_replace('/\.pdf$/i', '', $filename); //
		
		//$basename = str_replace('CallReport5300_September-2012_', '', $basename);
		//$basename = str_replace('CallReport5300_December-2012_', '', $basename);
		//$basename = str_replace('CallReport5300_March-2013_', '', $basename);
		//$basename = str_replace('CallReport5300_June-2013_', '', $basename);
		//$basename = str_replace('CallReport5300_September-2013_', '', $basename);
		//$basename = str_replace('CallReport5300_December-2013_', '', $basename);
		//~ $basename = str_replace('CallReport5300_June-2014_', '', $basename);
		//$basename = str_replace('CallReport5300_September-2014_', '', $basename);
		$basename = str_replace('CallReport5300_March-2015_', '', $basename);
		
		$filename_ = $directory . $filename;
		
		// Does file exist? If not then stop here.
		if (!file_exists('./TrevorFaden/'."$basename")) {
			echo 'a>'.$basename;
			sleep(1);
			continue;
		}
		
		// Here?
		// The file './TrevorFaden/'."$basename" contains ID & program status.
		// For example, "7382|1" or "83723|1".
		$CU_ID = file_get_contents('./TrevorFaden/'."$basename");
		if (!file_exists('./TrevorFaden/'.$CU_ID)) {
			echo 'b';
			echo $CU_ID;
			sleep(1);
			continue;
		}
		
		// Here?
		// The file './TrevorFaden/'.$CU_ID contains Assignment ID & CU name.
		// For example, "3634BBTX0OUSRDTFQ5NPRB3NV6FFIQ|OUR COMMUNITY".
		$x = file_get_contents('./TrevorFaden/'.$CU_ID);
		$x = explode('|', $x);
		$assignmentId = $x[0];
		$cuname = $x[1];
		
		// Init
		$ch = curl_init();
		$url = 'http://www.creditunions.com/mturk/upload.aspx?cn=' . $CU_ID;
		
		// Get page 1 for the form vars __EVENTVALIDATION, __VIEWSTATE.
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_VERBOSE, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
		curl_setopt($ch, CURLOPT_URL, $url);
		$response = curl_exec($ch);
		
		// Scrape response.
		$response = str_replace("\n", "", $response);
		preg_match('/id="__VIEWSTATEGENERATOR" value="([^"]+)" \/>(?:.+?)"__EVENTVALIDATION" value="([^"]*)" \/>(?:.+?)"__VIEWSTATE" value="([^"]*)" \/>/i', $response, $m);
		
		if (empty($m[1])) {
			echo 'scrape' . "\n";
			sleep(1);
			continue;
		}
		
		// page 2
		// On success will be strpos "Success!" === 0.
		$post = array(
			"form1"				=>"",
			"__VIEWSTATEGENERATOR"	=> $m[1],
			"__EVENTVALIDATION"	=>$m[2],
			"__VIEWSTATE"		=>$m[3],
			"flUpload"			=>"@$filename_",
			"btnUpload"			=>"Upload"
		);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_VERBOSE, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		curl_setopt($ch, CURLOPT_TIMEOUT, 20); // 20s
		//curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie2.txt');
		//curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie2.txt');
		$r = curl_exec($ch);
		$v = curl_getinfo($ch);
		
		//$o='';ob_start();print_r($v);$o=ob_get_contents();ob_end_clean();
		//file_put_contents('./mturk-trevor_faden.log', $o."\r\n".$r);
		//file_put_contents('./mturk-trevor_faden.log', $r);
		//@chmod('./mturk-trevor_faden.log',0777);
		//$o='';ob_start();print_r($v);$o=ob_get_contents();ob_end_clean();
		//file_put_contents('./xx-succ.html', $o."\r\n".$r);
			
		if ($v['http_code'] != '200' && strpos($r, 'Success!') !== 0) {
			echo 'http '.$v['http_code']."\n";
			echo 'pos  '.strpos($r, 'Success!')."\n";
		} else if (strpos($r, 'Success!') !== false) {
			echo 'http '.$v['http_code']."\n";
			echo 'pos  '.strpos($r, 'Success!')."\n";
			rename($filename_, './TrevorFaden/done/'.$filename);
			chmod('./TrevorFaden/done/'.$filename, 0777);
			file_put_contents('./TrevorFaden/'."$basename", '|1', FILE_APPEND); // STATUS!
		}
		//sleep(1);
		usleep(100000);
		//~ continue;
		
		//~ /*
		 //~ * This is the old script. Old as of May 7 2014.
		 //~ * 
		 //~ * 
		 //~ * 
		 //~ * 
		 //~ * 
		 //~ */
		//~ 
		//~ //
		//~ $ch = curl_init();
		//~ // page 1
		//~ curl_setopt($ch, CURLOPT_HEADER, 0);
		//~ curl_setopt($ch, CURLOPT_VERBOSE, 0);
		//~ curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		//~ curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
		//~ // CHANGED FEB 10 2014
		//~ curl_setopt($ch, CURLOPT_URL, 'http://www.creditunions.com/mturk/tester.html');
		//~ //curl_setopt($ch, CURLOPT_URL, 'http://www.internify.me/tester/tester.html');
		//~ $response = curl_exec($ch);
		//~ 
		//~ // access key, policy, signature
		//~ $response = str_replace("\n","",$response);
		//~ preg_match('/AWSAccessKeyId" value="(.+?)"(?:.+?)policy" value="(.+?)"(?:.+?)signature" value="(.+?)"/',$response,$m);
		//~ 
		//~ // page 2
		//~ $post = array(
			//~ "key"=>"$filename",
			//~ "AWSAccessKeyId"=>$m[1],
			//~ "acl"=>"public-read-write",
			//~ "policy"=>$m[2],
			//~ "signature"=>$m[3],
			//~ "file"=>"@$filename_",
			//~ "submit"=>"Upload"
		//~ );
		//~ curl_setopt($ch, CURLOPT_HEADER, 0);
		//~ curl_setopt($ch, CURLOPT_VERBOSE, 0);
		//~ curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		//~ curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		//~ curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
		//~ curl_setopt($ch, CURLOPT_URL, 'http://s3.amazonaws.com/Q3-12-FL');
		//~ curl_setopt($ch, CURLOPT_POST, true);
		//~ curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		//~ curl_setopt($ch, CURLOPT_TIMEOUT, 20); // 20s
		//~ //curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie2.txt');
		//~ //curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie2.txt');
		//~ $r = curl_exec($ch);
		//~ $v = curl_getinfo($ch);
		//~ 
		//~ //$o='';ob_start();print_r($v);$o=ob_get_contents();ob_end_clean();
		//~ //file_put_contents('./mturk-trevor_faden.log', $o."\r\n".$r);
		//~ //file_put_contents('./mturk-trevor_faden.log', $r);
		//~ //@chmod('./mturk-trevor_faden.log',0777);
		//~ //$o='';ob_start();print_r($v);$o=ob_get_contents();ob_end_clean();
		//~ //file_put_contents('./xx-succ.html', $o."\r\n".$r);
			//~ 
		//~ if ( $v['http_code'] != '204') {
			//~ echo 'http '.$v['http_code']."\n";
		//~ } else {
			//~ rename($filename_, './TrevorFaden/done/'.$filename);
			//~ chmod('./TrevorFaden/done/'.$filename, 0777);
			//~ file_put_contents('./TrevorFaden/'."$basename", '|1', FILE_APPEND);
		//~ }
		//~ //sleep(1);
		//~ usleep(100000);
	}
}

?>
