<?php

set_time_limit(800);

if (
	isset($_GET['u']) &&
	isset($_GET['k'])
) {
	$file = '/var/www/html/mturk/JonBina/complete';
	
	if (file_exists($file)) {
		file_put_contents($file, "\n".$_GET['k'].' '.$_GET['u'], FILE_APPEND);
	}
}
else if (isset($_GET['get']))
{
	$file = '/var/www/html/mturk/JonBina/keywords';
	
	if (file_exists($file)) {
		$x = trim(file_get_contents($file));
		if ($x != '') {
			$x = explode("\n",$x);
			echo trim($x[0]);
			unset($x[0]);
			file_put_contents($file, implode("\n", $x));
		} else echo '____';
	}
}
else if (isset($_GET['new']))
{
	$file = '/var/www/html/mturk/JonBina/keywords';
	
	if (file_exists($file)) {
		file_put_contents($file, "\n".$_GET['new'], FILE_APPEND);
	}
}
else if (isset($_GET['load_complete']))
{
	$file = '/var/www/html/mturk/JonBina/complete';
	
	if (file_exists($file)) {
		$x = "\n".trim(file_get_contents($file));
		$y = preg_quote($_GET['load_complete']);
		preg_match("/\n$y ([<> \d,\-]+)/", $x, $m);
		if (!empty($m[1])) {
			echo $m[1];
		}
	}
}
exit;























?>
