<?php
set_time_limit(500);
if (isset($_GET['u']) && !isset($_GET['get'])) {
	
	//
	//
	//
	header("Content-Length: 0");
	header('Connection: close');
	
	$file = './s/'.basename($_GET['u']);
	$basename = basename($_GET['u']);
	$f = file_get_contents($_GET['u']);
	file_put_contents($file, $f);
	exec("convert -colorspace gray -level 10%,90%,0.5 $file $file.tiff");
	exec("tesseract $file.tiff $file hocr");
	chmod($file, 0777);
	chmod($file.'.tiff', 0777);
	chmod($file.'.html', 0777);
	
}
if (isset($_GET['u']) && isset($_GET['get'])) {
	$file = './s/'.basename($_GET['u']).'.html';
	if (file_exists($file) && filesize($file) > 100) {
		echo file_get_contents($file);
	}
}
exit;
?>
