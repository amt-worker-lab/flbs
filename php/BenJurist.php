<?php

if (
	isset($_GET['u'])
) {
	
	file_put_contents('./skips', $_GET['u']."\n", FILE_APPEND);
}
else if (
	isset($_GET['v'])
) {
	
	echo file_get_contents('./skips');
}
exit;

?>
