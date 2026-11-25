<?php
set_time_limit(360);//1 hr

if (isset($_GET['u']) && isset($_GET['v'])) {
	file_put_contents('./j', $_GET['u'].'|'.$_GET['v']."\n", FILE_APPEND);
	echo $_GET['v'];
} else if (isset($_GET['u'])) {
	
	if (strlen(trim($_GET['u'])) < 2) exit;
	
	$f = file_get_contents('./j');
	$f = explode("\n", $f);
	foreach ($f as $k => $v) {
		$v2 = explode('|', $v);
		if (strtolower(trim($v2[0])) == strtolower(trim($_GET['u']))) {
			echo $v2[1];
			break;
		}
	}
}
exit;
?>
