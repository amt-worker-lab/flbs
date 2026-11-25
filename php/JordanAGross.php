<?php

set_time_limit(600); //10 min.

if (isset($_GET['u']) && isset($_GET['v'])) {
	
	$u = preg_replace('/[^\-\d]/', '', $_GET['u']);
	
	file_put_contents('./s/'.$u, $_GET['v']);
	
	chmod('./s/'.$u, 0777);
	exit;
}
else if (isset($_GET['u']) && !isset($_GET['v'])) {
	
	$u = preg_replace('/[^\-\d]/', '', $_GET['u']);

	if (file_exists('./s/'.$u)) {
		echo file_get_contents('./s/'.$u);
	}
	exit;
}
else if (isset($_GET['u2']) && !isset($_GET['v2'])) { // read
	
	$u = preg_replace('/[^\-\d]/', '', $_GET['u2']);

	if (file_exists('./s/'.$u)) {
		echo file_get_contents('./s/'.$u);
	}
	exit;
}
else if (isset($_GET['u2']) && isset($_GET['v2'])) { // write
	
	$u = preg_replace('/[^\-\d]/', '', $_GET['u2']);
	
	file_put_contents('./s/'.$u, $_GET['v2']);
	
	chmod('./s/'.$u, 0777);
	exit;
}
else if (isset($_GET['u_captcha_id']) && isset($_GET['u_captcha_src']) && isset($_GET['init'])) {
	//  MT___host + '/mturk/JordanAGross/JordanAGross.php?u_captcha_id='+encodeURIComponent(id)+'&u_captcha_src='+encodeURIComponent(IMAGE_SRC)+'&init',
	
	// TIME LIMIT 30 !
	set_time_limit(30);
	
	//
	// kills
	//
	//~ header("Connection: close");
	//~ ob_start();
	//~ phpinfo();
	//~ $size=ob_get_length();
	//~ header("Content-Length: $size");
	//~ ob_end_flush();
	//~ flush();
	
	//~ $fo = file_get_contents($_GET['u_captcha_src']);
	
	$ch = curl_init($_GET['u_captcha_src']);
	curl_setopt($ch, CURLOPT_VERBOSE, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
	curl_setopt($ch, CURLOPT_POST, false);
	//~ curl_setopt($ch, CURLOPT_COOKIEFILE, '/var/www/html/cookie.txt');
	//~ curl_setopt($ch, CURLOPT_COOKIEJAR, '/var/www/html/cookie.txt');
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	$fo = curl_exec($ch);
	$x = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	if ($x != 200) {
		exit;
	}
	
	$fn = './s/ocr-'.$_GET['u_captcha_id'];
	$fn_jpg = './s/ocr-'.$_GET['u_captcha_id'].'.jpg';
	$fn_tif = './s/ocr-'.$_GET['u_captcha_id'].'.tif';
	$fn_txt = './s/ocr-'.$_GET['u_captcha_id'].'.txt';
	
	file_put_contents($fn_jpg, $fo);
	
	chmod($fn_jpg, 0777);
	
	/**
	 * New (Jul28)
	 * This is for captchas for cookcountytreasurer.com. These are 6 A-Z chars.
	 * Try OCR with two types:
	 * 1) OCR regular
	 * 2) OCR with skewed text to the right. (cookcountytreasurer.) This is
	 *    helpful with NEW captchas such as -> http://cookcountytreasurer.com/securityimage.aspx?securitycode=F%2bULRM8A2GQ%3d
	 *    which seem to very often skew a lot left. Affine is at: http://www.imagemagick.org/Usage/distorts/#affine
	 */
	if (isset($_GET['cookcountytreasurer'])) {
		
		$fn_tif_tmp1 = './s/ocr-'.$_GET['u_captcha_id'].'TMP1.tif';
		$fn_tif_tmp2 = './s/ocr-'.$_GET['u_captcha_id'].'TMP2.tif';
		
		$fn_tmp1 = './s/ocr-'.$_GET['u_captcha_id'].'TMP1';
		$fn_tmp2 = './s/ocr-'.$_GET['u_captcha_id'].'TMP2';
		$fn_txt_tmp1 = './s/ocr-'.$_GET['u_captcha_id'].'TMP1.txt';
		$fn_txt_tmp2 = './s/ocr-'.$_GET['u_captcha_id'].'TMP2.txt';
		
		$word_dist1 = 0;
		$word_dist2 = 0;
		
		$txt1 = '';
		$txt2 = '';
		
		// regular (plus 300% plus level)
		shell_exec('convert -blur 2 -resize 300% -level 0%,50%,0.2 "'.$fn_jpg.'" "'.$fn_tif_tmp1.'"');
		chmod($fn_tif_tmp1, 0777);
		shell_exec('tesseract "'.$fn_tif_tmp1.'" "'.$fn_tmp1.'" nobatch goodcharsAZ');
		chmod($fn_txt_tmp1, 0777);
		// Calculate distance
		$txt1 = str_replace(' ', '', file_get_contents($fn_txt_tmp1));
		$word_dist1 = strlen($txt1) - 6;
		$word_dist1 = ($word_dist1 < 0) ? $word_dist1 *-1 : $word_dist1;
		// Exit if == 6
		if (strlen($txt1) == 6) {
			file_put_contents($fn_txt, $txt1);
			exit;
		}
		
		// slant (plus 300% plus level plus slant)
		$distort = "-distort Affine '3.5,60.5 3.5,60.5 32.5,60.5 32.5,60.5 3.5,30.5 33.5,20.5'";
		shell_exec('convert -blur 2 -resize 300% -level 0%,50%,0.2 '.$distort.' "'.$fn_jpg.'" "'.$fn_tif_tmp2.'"');
		chmod($fn_tif_tmp2, 0777);
		shell_exec('tesseract "'.$fn_tif_tmp2.'" "'.$fn_tmp2.'" nobatch goodcharsAZ');
		chmod($fn_txt_tmp2, 0777);
		// Calculate distance
		$txt2 = str_replace(' ', '', file_get_contents($fn_txt_tmp2));
		$word_dist2 = strlen($txt2) - 6;
		$word_dist2 = ($word_dist2 < 0) ? $word_dist2 *-1 : $word_dist2;
		// Exit if == 6
		if (strlen($txt2) == 6) {
			file_put_contents($fn_txt, $txt2);
			exit;
		}
		
		// Here?
		// Place the incorrect length string into file? This is probably
		// only useful for debug.
		if ($word_dist1 < $word_dist2)
			file_put_contents($fn_txt, $txt1);
		else
			file_put_contents($fn_txt, $txt2);
		
		exit;
	}
	
	/**
	 * Regular
	 * cookcountyclerk.com
	 */
	
	shell_exec('convert -blur 2 -resize 120% "'.$fn_jpg.'" "'.$fn_tif.'"');
	
	chmod($fn_tif, 0777);
	
	shell_exec('tesseract "'.$fn_tif.'" "'.$fn.'" nobatch goodchars09AZ');
	
	chmod($fn_txt, 0777);
	
	/**
	 * Update the text file to include the name of the image.
	 * This is useful for debug & OCR-learning. It is
	 * to line up the CAPTCHA image and human-entered CAPTCHA text.
	 * Add the "source" location of the image.
	 * 
	 * https://code.google.com/p/tesseract-ocr/wiki/TrainingTesseract3
	 * For example:
	 * 	for ($i in $txt_files) {
	 * 		$guid=u_captcha_src=...;
	 * 		$tif=fn_tif=...;
	 * 		
	 * 		$user_entered_captcha_text=NEWLINE_FINANCIAL_$guid;
	 * 		
	 * 		TESSERACT_LEARN:IMAGE{$tif}=TEXT{$user_entered_captcha_text}
	 * 	}
	 */
	$contents = file_get_contents($fn_txt);
	$contents .= '||||' . $_GET['u_captcha_src'] . '||||' . $fn_tif;
	file_put_contents($fn_txt, $contents);
	
	exit;
}
else if (isset($_GET['u_captcha_id']) && isset($_GET['complete'])) { 
	//  MT___host + '/mturk/JordanAGross/JordanAGross.php?u_captcha_id='+encodeURIComponent(id)+'&u_captcha_src='+encodeURIComponent(IMAGE_SRC)+'&complete',
	
	$fn_txt = './s/ocr-'.$_GET['u_captcha_id'].'.txt';
	
	if (file_exists($fn_txt)) {
		echo ' '.file_get_contents($fn_txt);
	}
	exit;
}




















?>
