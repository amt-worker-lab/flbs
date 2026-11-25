<?php

set_time_limit(600); //10 min.
$start = time();

if (isset($_GET['u']) && isset($_GET['v']) ) {
	
	//
	//
	//
	//header("Content-Length: 0");
	//header('Connection: close');
	
	$fn = './s/'.$_GET['v'].'.pdf';
	$fn2= './s/'.$_GET['v'].'-html.html';
	
	if (!file_exists($fn)) {
		
		file_put_contents($fn, '');
		
		// 775: 33277
		// 777: 33279
		
		chmod($fn, 0775);
		
		//$f = file_get_contents('http://www.adviserinfo.sec.gov/Iapd/Content/Common/crd_iapd_Brochure.aspx?BRCHR_VRSN_ID='.$_GET['u']);
		
		$ch = curl_init('http://www.adviserinfo.sec.gov/Iapd/Content/Common/crd_iapd_Brochure.aspx?BRCHR_VRSN_ID='.$_GET['u']);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)"); 

		$f = curl_exec($ch);
		curl_close($ch);

		if (file_put_contents($fn, $f)) {
			$o = exec('pdftohtml -i -s '.$fn); // no img, zoom 1xxx, && chmod
			
			sleep(4);
			
			$f = file_get_contents($fn2);
			$f = str_replace('&#160;', ' ', $f);
			
			$out = '<div style="width: 80%;font-size: 0.8em;"><div>';
			preg_match_all('/[\d\D]{1600}%[\d\D]{1600}/',$f,$m);
			$c = 0;
			if (!empty($m[0])) {
				foreach ($m[0] as $k => $v) {
					if (preg_match('/^[^<>]+>/', $v) !== false) {
						$v = preg_replace('/^[^<>]+>/', '', $v);
					}
					$v = preg_replace('/<[^>]+>/', '', $v);
					$v = preg_replace('/<[^>]+$/', '', $v);
					$v = preg_replace('/&#160;/', ' ', $v);
					$v = str_replace('%', '<span style="color:yellow;font-weight:bold;">{||}</span>',$v);
					//if (preg_match('/^(?:.+?)+>/', $v) !== false) {
					//	$v = preg_replace('/^[^<>]+>/', '', $v);
					//}
					$out .= $v.'<br><br>';
					//$out .= $v.'<br><input type="text" onmouseover="javascript:function() { this.select(); this.focus(); }"><br><br>';
					//$out .= htmlspecialchars($v).'<br><input type="text" onmouseover="javascript:function() { this.select(); this.focus(); }"><br><br>';
				}
				$c++;
			}
			$out .= '<hr>';
			//$out .= '<hr style="width:100%;">';
			preg_match_all('/[\d\D]{600}minimum[\d\D]{600}/i',$f,$m);
			$c = 0;
			if (!empty($m[0])) {
				foreach ($m[0] as $k => $v) {
					if (preg_match('/^[^<>]+>/', $v) !== false) {
						$v = preg_replace('/^[^<>]+>/', '', $v);
					}
					$v = preg_replace('/<[^>]+>/', '', $v);
					$v = preg_replace('/<[^>]+$/', '', $v);
					$v = preg_replace('/&#160;/', ' ', $v);
					$v = str_replace('%', '<span style="color:yellow;font-weight:bold;">{||}</span>',$v);
					$v = str_ireplace('minimum', '<span style="color:green;font-weight:bold;">[||||]</span>', $v);
					$out .= $v.'<br><br>';
					//$out .= $v.'<br><input type="text" onmouseover="javascript:function() { this.select(); this.focus(); }"><br><br>';
					//$out .= htmlspecialchars($v).'<br><input type="text" onmouseover="javascript:function() { this.select(); this.focus(); }"><br><br>';
				}
				$c++;
			}
			$out .= '<hr>';
			//$out .= '<hr style="width:100%;">';
			$f__ = str_replace("\n", '', $f);
			preg_match_all('/item 7(?:.+?)item 8/i', $f__, $m);
			$c = 0;
			if (!empty($m[0])) {
				foreach ($m[0] as $k => $v) {
					if (count($v) > 1000)
						$v = substr($v, 0, 1000);
					if (preg_match('/^[^<>]+>/', $v) !== false) {
						$v = preg_replace('/^[^<>]+>/', '', $v);
					}
					$v = preg_replace('/<[^>]+>/', '', $v);
					$v = preg_replace('/<[^>]+$/', '', $v);
					$v = preg_replace('/&#160;/', ' ', $v);
					$v = str_replace('%', '<span style="color:yellow;font-weight:bold;">{||}</span>',$v);
					$v = str_ireplace('minimum', '<span style="color:green;font-weight:bold;">[||||]</span>', $v);
					$out .= $v.'<br><br>';
					//$out .= $v.'<br><input type="text" onmouseover="javascript:function() { this.select(); this.focus(); }"><br><br>';
					//$out .= htmlspecialchars($v).'<br><input type="text" onmouseover="javascript:function() { this.select(); this.focus(); }"><br><br>';
				}
				$c++;
			}
			$f = $out. '</div></div>' . $f;
			file_put_contents($fn2, $f);
			
			chmod($fn2, 0777);
		}
	}
	
    exit;
}
else if (isset($_GET['v']))
{
	$fn = './s/'.$_GET['v'].'-html.html';
	
	//echo fileperms($fn);exit;
	
	if (file_exists($fn)) {
		$x=fileperms($fn);
		//$x=filesize($fn);
		if ($x == 33279) {
			echo 'xx';
		}
	}
	exit;
}








/*
 * 
 * 
 * 
 */
if (isset($_GET['u2'])) { // && !isset($_GET['v2'])
	
	//
	// kills
	//
	/*header("Connection: close");
	ob_start();
	phpinfo();
	$size=ob_get_length();
	header("Content-Length: $size");
	ob_end_flush();
	flush();*/
	
	$file = './s/'.basename($_GET['u2']);
	
	//echo $file;
	
	//echo $_GET['u2'];
	
	//exit;
	
	// download
	$ch = curl_init($_GET['u2']);

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)"); 

	$f = curl_exec($ch);
	
	print_r(curl_getinfo($ch));
	
	curl_close($ch);
	
	file_put_contents($file, $f);
	chmod($file, 0777);
	
	
	
	// post
	$ch = curl_init();
	//http://ec2-50-17-42-228.compute-1.amazonaws.com/
	$post = array(
		"file" => "@/var/www/html/mturk/Lakewood_Design/s/".basename($_GET['u2']) // full location
	);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_VERBOSE, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
	curl_setopt($ch, CURLOPT_URL, 'http://ec2-50-17-42-228.compute-1.amazonaws.com/upload');
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	//curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie2.txt');
	//curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie2.txt');
	$r = curl_exec($ch);
	$v = curl_getinfo($ch);
	curl_close($ch);
	
	//file_put_contents($file.'.log', $r);
	
	preg_match('/ url: \'\/queue\/([^\/]+)/', $r, $m);
	
	if (!empty($m[1])) {
		file_put_contents($file.'.log', $m[1]);
		chmod($file.'.log', 0777);
	}
	
	exit;
}
else if (isset($_GET['v2'])) {
	
	$file = './s/'.basename($_GET['v2']).'.log';
	
	//echo $file;
	
	if (file_exists($file)) {
		usleep(400000);
		echo file_get_contents($file);
	}
	exit;
}












?>
