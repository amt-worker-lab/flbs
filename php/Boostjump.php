<?php

set_time_limit(500);


if (isset($_GET['u'])) {
	
	if  (
			file_exists('/var/www/html/mturk/.scripts/Dropbox/'.$_GET['u']) ||
			file_exists('/var/www/html/mturk/.scripts/Dropbox/'.$_GET['u'].'.csv')
		)
	{
		echo '1';
	}
	
	exit;
	
}
//else (isset($_GET['v'])) {	
//}

if (isset($_POST['u']) && isset($_POST['v'])) {
	
	//$x = './s/export.csv';
	
	//$x = './s/'.basename($_POST['u']).'.csv';
	
	$x = '/var/www/html/mturk/.scripts/Dropbox/'.basename($_POST['u']).'.csv';
	
	file_put_contents($x, $_POST['v']);
	
	chmod($x, 0777);
	
	echo '1';
	
}

/*EMAIL
if (isset($_POST['u']) && isset($_POST['v'])) {
	
	//$a = curl($_GET['a']);
	
	$x = './s/export.csv';
	//$x = './s/'.basename($_POST['u']);
	
	file_put_contents($x, $_POST['v']);
	
	//file_put_contents($x, utf8_encode($_POST['v']));
	
	//file_put_contents($x, iconv("CP1257","UTF-8", $_POST['v']));
	
	chmod($x, 0777);
	
	//exit;
	
	$done = trim(
		multi_attach_mail('boostjumpacct@gmail.com', $x, '_redacted_', $_POST['u'] . ' _redacted_')
	);
	
	//$done = trim(multi_attach_mail('_redacted_.com', $x, '_redacted_', $_POST['u']));
	
	//$done = trim(multi_attach_mail('boostjumpacct@gmail.com', $x, '_redacted_@gmail.com'));
	
	rename($x, './done/'.$_POST['u'].'.csv');
	
	echo $done;
	
	//echo trim(multi_attach_mail('upload.UCPNY_M.kr5f0s6lx9@u.box.com', $x, '_redacted_.com'));
	
	//echo trim(multi_attach_mail('upload.EmblemH.ze2axik003@u.box.com', $x, '_redacted_@gmail.com'));
	
}
function curl($u) {
	$ch=curl_init();		
	curl_setopt($ch,CURLOPT_URL, $u);
	curl_setopt($ch,CURLOPT_TIMEOUT, 50);
	curl_setopt($ch,CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)');
	curl_setopt($ch,CURLOPT_REFERER, '');
	curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch,CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch,CURLOPT_COOKIEJAR, 'cookie.txt');
	curl_setopt($ch,CURLOPT_COOKIEFILE, 'cookie.txt');

	$return = curl_exec($ch);

	return $return;
}
function multi_attach_mail($to, $files, $sendermail, $subject){
	// email fields: to, from, subject, and so on
	$files = array($files);
	$from = "Files attach <".$sendermail.">"; 
	
	//$subject = date("d.M H:i")." F=".count($files); 
	
	//$subject = 
	
	$message = date("Y.m.d H:i:s")."\n".count($files)." attachments";
	
	$headers = "From: _redacted_";
	
	//$headers = "From: _redacted_, ";
	
	//$headers = "From: _redacted_, _redacted_";
	
	// boundary 
	$semi_rand = md5(time()); 
	$mime_boundary = "==Multipart_Boundary_x{$semi_rand}x"; 
 
	// headers for attachment 
	$headers .= "\nMIME-Version: 1.0\n" . "Content-Type: multipart/mixed;\n" . " boundary=\"{$mime_boundary}\""; 
 
	// multipart boundary 
	$message = "--{$mime_boundary}\n" . "Content-Type: text/plain; charset=\"iso-8859-1\"\n" .
	"Content-Transfer-Encoding: 7bit\n\n" . $message . "\n\n";  // xhtml, csv, plain
 
	// preparing attachments
	for($i=0;$i<count($files);$i++) {
		if(is_file($files[$i])) {
			$message .= "--{$mime_boundary}\n";
			$fp = @fopen($files[$i],"rb");
			$data = @fread($fp,filesize($files[$i]));
			@fclose($fp);
			$data = chunk_split(base64_encode($data));
			$message .= "Content-Type: application/octet-stream; name=\"".basename($files[$i])."\"\n" . 
			"Content-Description: ".basename($files[$i])."\n" .
			"Content-Disposition: attachment;\n" . " filename=\"".basename($files[$i])."\"; size=".filesize($files[$i]).";\n" . 
			"Content-Transfer-Encoding: base64\n\n" . $data . "\n\n";
		}
	}
	$message .= "--{$mime_boundary}--";
	$returnpath = "-f" . $sendermail;
	$ok = mail($to, $subject, $message, $headers, $returnpath); 
	if($ok){ return $i; } else { return 0; }
}*/
?>
