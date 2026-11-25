<?php
//Example of $contents
/*
{"ComputerId":2,"User":"_redacted_"}
*/

$DEBUG = TRUE;
if($DEBUG) {
	error_reporting(E_ALL);
	ini_set('display_errors', '1');
}


$filename = "ComputerIdAndPerson.txt";
$handle = fopen($filename, "rw");
$contents = fread($handle, filesize($filename));
$contents = json_decode($contents);

if(isset($_GET['signIn']))
{
	$user = $_GET['User'];
	$contents->User = $user;
	echo var_dump($contents);
	
	fclose($handle);
	$handle = fopen($filename, "w");
	fwrite($handle, json_encode($contents));// json_encode($contents));
	fclose($handle);
}
else if(isset($_GET['getSignIn']))
{
	echo $contents->User;
	fclose($handle);
}
else if(isset($_GET['getSignInAndCompId'])) {
	echo json_encode($contents);
	fclose($handle);
}

?>
