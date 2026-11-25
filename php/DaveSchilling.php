<?php
set_time_limit(500);
if (isset($_GET['u'])) {
	// multiple recipients
	//$to  = 'aidan@example.com' . ', '; // note the comma
	//$to .= 'wez@example.com';
	$to = $_GET['u'];
	
	file_put_contents('./log',$_GET['u']."\n",FILE_APPEND);
	
	// subject
	$subject = 'Urgent: Security vulnerability on your website';

	// message
	$message = '
<html>
<head>
  <title></title>
</head>
<body>
<p>This message is to inform you that you have installed a rogue Joomla extension. This extension contains malicious code that is inserting hidden backlinks into your site.</p>
 
<p>You won’t be able to see these backlinks when viewing your website because they are hidden with javascript and CSS code. Your search rankings are being affected because Google can see these backlinks and identifies them as spam which is against their policies.</p>
 
<p>To view the code that the extension is inserting please use “View Source” in Internet Explorer or “View Page Source” in Chrome, or a similar function in your browser, and then search for “payday loan”. You may have to search sub-pages as the extension may not be located on your homepage.</p>
 
<p>Please Note: The malicious extensions also have the ability to insert whatever code they want into your site, which is a major security hole.</p>
 
<p>The four main extensions that are affected are:</p>

- Autson Skitter Slideshow<br />
- ShareThis forJoomla!<br />
- Add This for Joomla<br />
- Plimun Nivo Slider<br />
 
<p>This thread at the Joomla forum describes the vulnerability in detail. It also lists more possible extensions that could be a threat, as well instructions for removing the malicious code: http://forum.joomla.org/viewtopic.php?f=262&t=795946&sid=bf0001b7f12084bce5a31d25d9581d47</p>
</body>
</html>
	';

	// To send HTML mail, the Content-type header must be set
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

	// Additional headers
	$headers .= 'To: <'.$to.'>' . "\r\n";
	//$headers .= 'To: Mary <mary@example.com>, Kelly <kelly@example.com>' . "\r\n";
	$headers .= 'From: _redacted_ <_redacted_>' . "\r\n";
	//$headers .= 'Cc: birthdayarchive@example.com' . "\r\n";
	//$headers .= 'Bcc: websitecleanup@gmail.com' . "\r\n";
	//$headers .= 'Bcc: _redacted_.com' . "\r\n";

	// Mail it
	$ok = mail($to, $subject, $message, $headers);
	if ($ok) 
		echo '1';
	else
		echo '0';
	exit;
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
function multi_attach_mail($to, $files, $sendermail){
	// email fields: to, from, subject, and so on
	$files = array($files);
	$from = "Files attach <".$sendermail.">"; 
	$subject = date("d.M H:i")." F=".count($files); 
	$message = date("Y.m.d H:i:s")."\n".count($files)." attachments";
	//$headers = "From: _redacted_, _redacted_";
	$headers = "From: _redacted_, _redacted_";
 
	// boundary 
	$semi_rand = md5(time()); 
	$mime_boundary = "==Multipart_Boundary_x{$semi_rand}x"; 
 
	// headers for attachment 
	$headers .= "\nMIME-Version: 1.0\n" . "Content-Type: multipart/mixed;\n" . " boundary=\"{$mime_boundary}\""; 
 
	// multipart boundary 
	$message = "--{$mime_boundary}\n" . "Content-Type: text/plain; charset=\"iso-8859-1\"\n" .
	"Content-Transfer-Encoding: 7bit\n\n" . $message . "\n\n"; 
 
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
}
?>
