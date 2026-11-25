<?php

set_time_limit(300); //5 min.

if (!isset($_GET['done']) && isset($_GET['case'])) { //1
	$f = './s/'.preg_replace('/[^\w\d]/', '', $_GET['u']);
	file_put_contents($f, $_GET['case']);
	chmod($f, 0777);
}
else {
	$f = './s/'.preg_replace('/[^\w\d]/', '', $_GET['u']);
	if (file_exists($f)) {
		usleep(100000);//0.1
		echo file_get_contents($f);
	}
}
exit;
?>
