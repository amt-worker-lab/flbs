<?php

// Note: Memory usage hit 1GB PHP max after about 1/2 of a chapter converted!
//       The solution to this is that in each loop() function call
//       the temporary variable $t required to be unset. That is,
//       "unset($t)".
// Note: Command: espeak -v en -s 120 -f test\ audio\ ch.\ 12 -w "ch.12,-s=120"
// Note: Inputs (parameters) are: "Chapter Name", "Page[init] Page[end]": Remove page numbers inclusive
// Todo?: © EDS/AP/Wide World

// $_POST
// ['text'] - Required
// ['audio_filename'] - Default = ?
// ['page_replace_start'] - Default = ''
// ['page_replace_end'] - Default = ''
// ['try_word_concat'] - Default = true
// ['speed'] - Default = 80

// $argv
if (isset($argv)) {
	if (count($argv) != 8) {
		echo count($argv)." arguments but 7 are required!\n";
		echo form2();
	}
	$_POST['text'] = file_get_contents($argv[1]);
	$_POST['audio_filename'] = $argv[2];
	$_POST['voice'] = $argv[3];
	$_POST['page_replace_start'] = $argv[4];
	$_POST['page_replace_end'] = $argv[5];
	$_POST['speed'] = $argv[6];
	$_POST['try_word_concat'] = $argv[7];
} else {
	echo form2();
}
function form2() {
	echo 'Usage:'."\n";
	echo 'php -f tts.php "[text file]" "[audio name]" "[voice]" "[page start]" "[page end]" "[speed]" "[try_word_concat=on|]"'."\n";
	echo ' text filename: Required'."\n";
	echo ' audio filename: Default/Blank = Timestamp'."\n";
	echo ' voice: Default/Blank = mb-en1 | [mb-en1 or mb-us1 or mb-us2 or mb-us3]'."\n";
	echo ' replace page start: # or Blank'."\n";
	echo ' replace page end: # or Blank'."\n";
	echo ' speed: # | Default/Blank=100'."\n";
	echo ' try word concat: # | Default/Blank=off'."\n";
	exit;
}
// Validate post
//
//
// Text
//
//
if (!isset($_POST['text'])) {
	echo form();
	exit;
}
if (isset($_POST['text']) && trim($_POST['text']) == '') {
	echo "Audio text is empty! Exiting.<hr>";
	echo form();
	exit;
}
$text = $_POST['text'];
// Voice
//
//
$voice = 'mb-en1';
if (!isset($_POST['voice'])) {
	echo "Voice is empty! Exiting.<hr>";
	echo form();
	exit;
}
if ($_POST['voice'] != 'mb-en1' &&
	$_POST['voice'] != 'mb-us1' &&
	$_POST['voice'] != 'mb-us2' &&
	$_POST['voice'] != 'mb-us3'
	// add more here.....
	)
{
	echo "Invalid voice! Exiting.<hr>";
	echo form();
	exit;
}
// File
//
//
if (isset($_POST['audio_filename'])) {
	$x = trim(preg_replace('/[^a-z0-9 ]/i', '', $_POST['audio_filename']));
	$x = substr($x, 0, 100); // 100 char limit...
	if ($x == '') {
		$file = time();
	} else {
		$file = $x;
	}
	$basename = $file;
	$file = '/var/www/html/dev/'.$file;
	if (file_exists($file)) {
		echo "$file exists! Exiting.<hr>";
		echo form();
		exit;
	}
}
// Pages
//
//
$page_numbers = [];
if (isset($_POST['page_replace_start']) &&
	isset($_POST['page_replace_end']) &&
	trim($_POST['page_replace_start']) != '' &&
	trim($_POST['page_replace_end']) != ''
	)
{
	$x = intval(preg_replace('/[^0-9]/i', '', $_POST['page_replace_start']));
	$y = intval(preg_replace('/[^0-9]/i', '', $_POST['page_replace_end']));
	if ($x >= $y) { // Must be greater. Therefore if either == '' then they would be 0?
		echo "$x >= $y! Exiting.<hr>";
		echo form();
		exit;
	}
	$page_numbers = [$x, $y];
}
// Speed
//
//
$speed = 100;
if (isset($_POST['speed']) &&
	trim($_POST['speed']) != ''
	)
{
	$x = intval(preg_replace('/[^0-9]/i', '', $_POST['speed']));
	if ($x == 0) { //
		echo "$x == 0! Exiting.<hr>";
		echo form();
		exit;
	}
	$speed = $x;
}
// ...
//
//
$d = explode("\n", strtolower( trim(file_get_contents('/usr/share/dict/words')) ));
$dict = array(); // Associative array
$skip_words = []; // Todo: Ignore these common words that are usually broken....?
foreach ($d as $key=>$word) {
	$dict[ $word ] = true;
}
	//~ $text = strtolower( clean_text(file_get_contents($file)) ); // OLD
$text = strtolower( clean_text($text) );
$done_text = '';  // Tally of incorrect
$final_text = ''; // Tally of all+corrected
function convert($size) // http://php.net/manual/en/function.memory-get-usage.php#96280
{
    $unit=array('b','kb','mb','gb','tb','pb');
    return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
}
// Run...
// Concat
//
//
if (isset($_POST['try_word_concat']) && $_POST['try_word_concat'] == 'on') {
	loop();
} else {
	$final_text = $text;
	write(); // Skips loop()......
}
exit;

//
//~ $file = '/var/www/html/dev/geg.121.ch.12';
//~ $file = '/var/www/html/dev/geg.121.ch.7';
//~ $page_numbers = [203, 248];
//~ $file = '/var/www/html/dev/geg.121.ch.11';
//~ $page_numbers = [365, 402];
//~ $file = '/var/www/html/dev/csc.336.ch.14';
//~ $page_numbers = [377, 388];
//~ $file = '/var/www/html/dev/csc.336.ch.10';
//~ $page_numbers = [264, 288];
//~ $file = '/var/www/html/dev/GEG 121.final.txt';
//~ $page_numbers = [];
//~ $file = '/var/www/html/dev/csc.336.ch.25';
//~ $page_numbers = [682, 705];
//~ $file = '/var/www/html/dev/csc217.ch.6';
//~ $page_numbers = [359, 445];
//~ $file = '/var/www/html/dev/CSC 336 Notes - Part 2.txt';
//~ $file = '/var/www/html/dev/CSC 336 Notes - Final Study Guide.txt';
//~ $file = '/var/www/html/dev/  The Elements of Style (Updated) (mobi).txt';
//~ $file = '/var/www/html/dev/Computer Systems.Preface.txt';
//~ $file = '/var/www/html/dev/A Practical Handbook for the Actor.txt';
//~ $page_numbers = [];

// Debugging
// Show memory usage....
// echo 'Memory usage:'.convert(memory_get_usage(true))."\n";

// This skips loop....
//
//
// $final_text = $text; write(); exit; // Skips loop()......

// Init
// This will connect words together. Such as "ver ify" becomes "verify".
//loop();

/**
 * Function loop
 */
function loop($iter) {
	global $dict;
	global $file;
	global $text;
	global $done_text;
	global $final_text;
	$t = str_replace($done_text, '', $text);
	$t = trim($t);
	//~ if ($iter && $iter != 'abas') {
		//~ echo 'iter:'.$iter."\n";
		//~ echo 'done_text:'.$done_text."\n";
		//~ echo 'text:'.$text."\n";
		//~ echo 'final_text:'.$final_text."\n";
		//~ echo 't:'.$t."\n";
		//~ return;
	//~ }
	$t = explode(' ', $t);
	echo convert(memory_get_usage(true))."(b)";
	$count = count($t);
	echo convert(memory_get_usage(true));
	for ($i=0; $i<$count; $i++) { // E.g. W0+W1+W2+W3;
		$word = $t[ $i ];
		if (strlen($word) == 1 && !preg_match('/[a-z]/', $word)) { // Is puncuation?
			// Add this word to "done" and go to next word...
			$final_text .= ' ' . $word;
			$done_text .= ' ' . $word;
			continue;
		}
		//~ echo 'word:'.$word."\n";
		$next_words = array();
		for ($j=3; $j>=1; $j--) { // 3,2,1 = Three
			$next_idx = $i+$j;
			//~ echo 'next_idx:'.$next_idx."\n";
			if ($next_idx >= $count) continue; // 0,1,2 > 3(e.g.last)
			$next_word = '';
			$words_regular = '';
			for ($k=$j; $k>=1; $k--) {
				$next_idx = $i+$k; // E.g. 0+3, 0+2, 0+1
				$tmp_word = $t[ $next_idx ];
				if (strlen($tmp_word) == 1 && !preg_match('/[a-z]/', $tmp_word)) { // Is puncuation?
					$next_word = '';     // Reset these...
					$words_regular = ''; // Reset these...
					continue;
				}
				$next_word = $tmp_word . $next_word; // Concatenate 
				$words_regular = $tmp_word . ' ' . $words_regular; // Same but with spaces
			}
			if ($next_word == '') { // If no subsequent words are here...
				continue;		    // Then continue...
			}
			$next_word = $word . $next_word;
			$words_regular = trim($word . ' ' . $words_regular); // With space
			//~ echo 'next_word:'.$next_word."\n";
			//~ echo 'words_regular:'.$words_regular."\n";
			if (isset($dict[ $next_word ])) {
				echo 'Found:'.$words_regular."<br />";
				$final_text .= ' ' . $next_word;
				$final_text = trim($final_text);
				$done_text .= ' ' . $words_regular;
				$done_text = trim($done_text);
				unset($t); // Unset t! t Uses ~4.50 mb. After unset($t) memory is reduced by 3.75mb!
				loop($next_word); // Goto next loop!
				return; // End!
			}
		}
		// Here is the case where the word+(+w1/+w2/+w3) 
		// did not result in a dictionary match...Damn!
		// Oh well. Add this word to "done" and go to next word...
		$done_text .= ' ' . $word;
		$final_text .= ' ' . $word;
	}
	write();
}
function write() {
	global $dict;
	global $file;
	global $basename;
	global $text;
	global $done_text;
	global $final_text;
	global $speed;
	global $voice;
	// If here then all words have been made.....
	echo 'Success!<br />';
	echo 'Speed: '.$speed.'<br />';
	echo 'Memory usage:'.convert(memory_get_usage(true))."<br />";
	//~ echo 'done_text:'.$done_text."\n";
	//~ echo 'text:'.$text."\n";
	//~ echo 'final_text:'.$final_text."\n";
	// Replace periods concerning numbers.
	// E.g. $1.50 and $2.99.
	// 
	//
	$final_text = preg_replace('/(\d) \. (\d)/', '$1.$2', $final_text);
	// Replace periods in other cases.
	// ...tts fails here (it says " dot ")
	// 
	$final_text = preg_replace_callback( 
		'/ [\.\?\!] (.)/', // [space][puncuation][space][any character]
		function ($matches) {// Capitalize it
            return '.' . "\n\n" . strtoupper($matches[1]); // Add Newline...
        },
		$final_text
	);
	file_put_contents($file . '.complete', $final_text);
	// espeak
	$cmd = 'espeak -v '. $voice .' -s '.$speed.' -f "'. $file .'.complete" -w "'. $file .'.wav"';
	echo 'Command: '.$cmd.'<br />';
	echo 'espeak command returns:' . shell_exec($cmd); // old = en-us
	echo 'espeak2 command returns:' . shell_exec('python /var/www/html/dev/tts.py'); // old = en-us
	echo "<br />";
	$f = 'http://192.168.1.2/dev/'. $basename .'.wav';
	echo 'File: <a href="'. $f .'" target="_blank">'. $f .' (opens in new window)</a>';
	//
	//~ echo 'espeak:' . shell_exec('festival --tts "' .$file . '.complete" -o "'.$file.'.wav"');
	//~ echo 'espeak:' . shell_exec('text2wave "' .escapeshellarg($file) . '.complete" -o "'.escapeshellcmd($file).'.wav"');
	//~ echo 'espeak:' . shell_exec('text2wave "' .escapeshellcmd($file) . '.complete" -o "test.wav"');
}
/**
 * function clean_text
 */
function clean_text($t) {
	global $page_numbers;
	// Remove page numbers.
	if (count($page_numbers) > 0) {
		for ($p = $page_numbers[0]; $p<=$page_numbers[1]; $p++) {
			$t = preg_replace('/'.$p.'/', '', $t, 1); // Replace first occurrence.
			//~ $pos = strpos($haystack,$needle);
			//~ if ($pos !== false) {
				//~ $newstring = substr_replace($haystack,$replace,$pos,strlen($needle));
			//~ }
			//~ if ($p % 10 == 0)
			//~ $t = str_replace($p, '', $t); // All occurrences
		}
	}
	// ...
	$t = preg_replace('/-\s*[\r\n]\s*/i', '', $t); // Remove hyphens, no space, includes hyphenated words across "newline"
	$t = preg_replace('/—/i', ' ', $t); // Remove "n-dash"
	$t = preg_replace('/([,\.!\{\}\[\]\(\)])/i', ' $1 ', $t); // Add space around commas and other punctuation.
	$t = preg_replace('/\n\d+\n/i', '\n', $t); // Remove lines with only numbers (assume page number)
	$t = preg_replace('/[\r\n]/i', ' ', $t); // Remove newline, add space
	$t = preg_replace('/\s+/i', ' ', $t); // Remove multi spaces
	
	//~ $t = preg_replace('/Chapter 7 Religion/i', '', $t); // Remove chapter name
	//~ $t = preg_replace('/Chapter 11 Agriculture/i', '', $t); // Remove chapter name
	//~ $t = preg_replace('/Chapter 12 Industry and Services/i', '', $t); // Remove chapter name
	//~ $t = preg_replace('/H *\. J *\. de Blij/i', '', $t); // Unnecessary term...
	
	$t = preg_replace('/\(* *Fig *\. \d+ *\.\d+ *\)*/i', '', $t); // Replace (Fig. 12.3) and Fig. 12.3
	$t = preg_replace('/\(* *Figure *\d+ *\.\d+ *\)*/i', '', $t); // Replace (Fig. 12.3) and Fig. 12.3
	$t = preg_replace('/\(* *fig \. \d+ \. \d+ *\)*/i', '', $t); // Replace (Fig. 12.3) and Fig. 12.3
	$t = preg_replace('/\(* *figure \. \d+ \. \d+ *\)*/i', '', $t); // Replace (Fig. 12.3) and Fig. 12.3
	$t = preg_replace('/\s+/i', ' ', $t); // Remove multi spaces
	$t = preg_replace('/ ([^\d]*)(\d) *,* *000 /i', ' $1 $2 thousand ', $t); // Remove multi spaces
	$t = preg_replace('/ ([^\d]*)(\d) *,* *000 *,* *000 /i', ' $1 $2 million ', $t); // Remove multi spaces
	return trim($t);
}

// Function form()
// Description: Displays a form for entering text and audio details.
// ['text'] - Required
// ['audio_filename'] - Default = ?
// ['page_replace_start'] - Default = ''
// ['page_replace_end'] - Default = ''
// ['try_word_concat'] - Default = true
function form() {
	$ftx = (isset($_POST['text'])) ? htmlspecialchars($_POST['text'], ENT_QUOTES) : '';
	$faf = (isset($_POST['audio_filename'])) ? htmlspecialchars($_POST['audio_filename'], ENT_QUOTES) : '';
	$frs = (isset($_POST['page_replace_start'])) ? htmlspecialchars($_POST['page_replace_start'], ENT_QUOTES) : '';
	$fre = (isset($_POST['page_replace_end'])) ? htmlspecialchars($_POST['page_replace_end'], ENT_QUOTES) : '';
	$fsp = (isset($_POST['speed'])) ? htmlspecialchars($_POST['speed'], ENT_QUOTES) : '100';
	$ftw = (isset($_POST['try_word_concat']) && $_POST['try_word_concat'] == 'on') ? 'checked ' : '';
	if (!isset($_POST['try_word_concat'])) $ftw = 'checked '; // Default=on
	?>
<html>
<body>
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
	Text: <textarea name="text" value="<?php echo $ftx; ?>"></textarea><br />
	Finished audio filename (default=current timestamp | [a-zA-Z0-9 ]): <input type="text" name="audio_filename" value="<?php echo $faf; ?>" /><br />
	Page replace start (default = blank = No page replace | [0-9]): <input type="text" name="page_replace_start" value="<?php echo $frs; ?>" /><br />
	Page replace end (default = blank = No page replace | [0-9]): <input type="text" name="page_replace_end" value="<?php echo $fre; ?>" /><br />
	Audio speed (default = 100 | 80=slow | 140=zoomie): <input type="text" name="speed" value="<?php echo $fsp; ?>" /><br />
	Try Word Concatenation (default = Yes, try): <input type="checkbox" name="try_word_concat" <?php echo $ftw; ?>/><br />
	Voice:
	<select name="voice">
		<option value="mb-en1">mb-en1</option>
		<option value="mb-us1">mb-us1</option>
		<option value="mb-us2">mb-us2</option>
		<option value="mb-us3">mb-us3</option>
	</select><br />
	<input type="submit" name="page_replace_end" value="Submit!" />
	</form>
</body>
</html>
	<?php
}
?>
