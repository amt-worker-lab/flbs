<?php


// Real Estate find website
if (
	isset($_GET['u'])
) {
	
	file_put_contents('./skips', $_GET['u']."\n", FILE_APPEND);
	exit;
}
else if (
	isset($_GET['v'])
) {
	
	echo file_get_contents('./skips');
	exit;
}

// Real Estate find website logo
if  ( // post
		isset($_POST['w']) &&
		isset($_POST['w2'])
	)
{
	$f = './realty_logo/' . str_replace('/', '', $_POST['w']);
	file_put_contents($f, $_POST['w2']); //json.stringify
	chmod($f, 0777);
	exit;
}
else if
	( // load
		isset($_GET['w3'])
	)
{
	$f = './realty_logo/' . str_replace('/', '', $_GET['w3']);
	if (file_exists($f)) {
		echo file_get_contents($f);
	}
	exit;
}
/*if  (
		isset($_GET['w']) &&
		isset($_GET['w2'])
	)
{
	$f = './realty_logo/' . str_replace('/', '', $_GET['w']);
	file_put_contents($f, $_GET['w2']);
	chmod($f, 0777);
	exit;
}
// first load (do not continue)
if  (
		isset($_GET['w3'])
	)
{
	$f = './realty_logo/' . str_replace('/', '', $_GET['w3']);
	if (file_exists($f)) {
		echo '1';
	} else {
		echo '0';
		file_put_contents($f, '');
		chmod($f, 0777);
	}
	exit;
}*/
// exit

?>
