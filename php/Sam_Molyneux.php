<?php

set_time_limit(300); //5 min.

// last effective url, http code
if (isset($_POST['u']) && isset($_POST['v'])) {
	
	//print_r($_POST);exit;
	
	$_POST['v'] = str_replace('data:image/jpeg;base64,','',$_POST['v']);
	$_POST['v'] = str_replace('data:image/jpg;base64,', '',$_POST['v']);
	$_POST['v'] = str_replace('data:image/png;base64,', '',$_POST['v']);
	$_POST['v'] = str_replace(' ', '+',$_POST['v']);
	$_POST['v'] = base64_decode($_POST['v']);
	if (strpos($_POST['v'], 'data:image') !== false) {
		echo $_POST['v'];
		exit;
	}
	
	// save
	$f = '/var/www/html/mturk/Sam_Molyneux/s/' . $_POST['u'] . '.jpg';
	if (!file_put_contents($f, $_POST['v'])) {
		echo 'x';
		exit;
	}
	chmod($f, 0777);
	
	// save2 (for posterity)
	$g = '/var/www/html/mturk/Sam_Molyneux/s/' .str_replace('/','',$_POST['w']). ' (' .$_POST['u']. ').jpg';
	file_put_contents($g, $_POST['v']);
	chmod($g, 0777);
	
	// upload
	supload($f);
	
}
else if (isset($_GET['u']) && isset($_GET['v'])) {
	
	// 
	//$md5_fn = md5($_GET['u']);
	
	// download
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_VERBOSE, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
	curl_setopt($ch, CURLOPT_URL, $_GET['v']);
	curl_setopt($ch, CURLOPT_POST, false);
	//curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie2.txt');
	//curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie2.txt');
	$r = curl_exec($ch);
	$v = curl_getinfo($ch);
	
	if  (
			$v['http_code'] != 200
		)
	{
		echo 'xdownload ';
		echo $_GET['v'];
		exit;
	}
	
	$ext = pathinfo($_GET['v'], PATHINFO_EXTENSION);
	if (!pathinfo($_GET['v'], PATHINFO_EXTENSION) || @strlen($ext) != 3) {
		
		$x = '/var/www/html/mturk/Sam_Molyneux/tmp/' . str_replace('/', '', $_GET['v']);
		if (!file_put_contents($x, $r)) {
			echo 'x';
			exit;
		}
		if (exif_imagetype($x) == IMAGETYPE_JPEG) {
			$ext = 'jpg';
		} else if (exif_imagetype($x) == IMAGETYPE_PNG) {
			$ext = 'png';
		} else if (exif_imagetype($x) == IMAGETYPE_GIF) {
			$ext = 'gif';
		} else {
			echo exif_imagetype($x);
			exit;
		}
	}
	
	if (strlen($ext) != 3) {
		echo 'len ';
		echo $ext;
		exit;
	}
	
	// save
	$f = '/var/www/html/mturk/Sam_Molyneux/s/' . $_GET['u'] . '.' . $ext;
	//$f = '/var/www/html/mturk/Sam_Molyneux/s/' . $_GET['u'] . '.jpg';
	if (!file_put_contents($f, $r)) {
		echo 'x';
		exit;
	}
	chmod($f, 0777);
	
	// save2
	$g = '/var/www/html/mturk/Sam_Molyneux/s/' . str_replace('/','',$_GET['w']) . ' ('.$_GET['u'].').' . $ext;
	//$g = '/var/www/html/mturk/Sam_Molyneux/s/' . str_replace('/','',$_GET['w']) . ' ('.$_GET['u'].').jpg';
	file_put_contents($g, $r);
	chmod($g, 0777);
	
	// upload
	supload($f);
}


function supload($file) { // local file to remote
	
	$f = $file;
	
	// upload
	//XXXXX"file" => "@$f", // array 0..n
	$post = array(
					 
		"formID" => "32824516875967",
		"form_32824516875967" => "",
		"q5_uploadFile[0]" => "@$f", // array 0..n
		"website" => "",
		"simple_spc" => "32824516875967-32824516875967"//,
		
		//"temp_upload_folder" => "32824516875967_533af97c58b10" // //
		
		//"simple_spc" => "31345783612252-31345783612252"
		
		//~ "formID" => "32824516875967",
		//~ //"formID" => "31345783612252",
		//~ "form_32824516875967" => "",
		//~ //"form_31345783612252" => "",
		//~ "q5_uploadFile[0]" => "@$f", // array 0..n
		//~ //"q5_selectCover[0]" => "@$f", // array 0..n
		//~ "website" => "",
		//~ "simple_spc" => "32824516875967-32824516875967"
		//~ //"simple_spc" => "31345783612252-31345783612252"

	);
	
	//http://submit.jotformpro.com/server.php?action=multipleUpload&field=q5_uploadFile&folder=32824516875967_533af9fdf4110&qqfile=Jordan+N+Fink.jpg
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_VERBOSE, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
	curl_setopt($ch, CURLOPT_URL, 'http://submit.jotformpro.com/submit/32824516875967/');
	//curl_setopt($ch, CURLOPT_URL, 'http://submit.jotform.ca/submit/32824516875967/');
	//curl_setopt($ch, CURLOPT_URL, 'http://submit.jotform.ca/submit/31345783612252/');
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	//curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie2.txt');
	//curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie2.txt');
	$r = curl_exec($ch);
	$v = curl_getinfo($ch);

	//http://submit.jotformpro.com/server.php?action=multipleUpload&field=q5_uploadFile&folder=32824516875967_525721ea540e2&qqfile=0026-4776.jpg
	
	if  (
			$v['http_code'] == 200 &&
			strpos($r, 'Thank You! Your Submission has been received.') !== false
		)
	{
		echo '1';
		/*echo $v['http_code'].'|';
		print_r($post);
		print_r($v);
		echo $r;*/
	} else {
		echo $v['http_code'].'|';
		print_r($post);
		print_r($v);
		echo $r;
	}
	exit;
}






















?>
