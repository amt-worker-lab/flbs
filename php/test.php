<?php
error_reporting(E_ALL);

if (function_exists('imagecreatefromjpeg'))
	echo 'a';
else
	echo 'b';
foreach (new DirectoryIterator('/var/www/html/media/images/') as $fileInfo) {
    if($fileInfo->isDot()) continue;
    echo $fileInfo->getFilename() . "\n";
    
    print_r(exif_read_data('/var/www/html/media/images/'.$fileInfo->getFilename()));
    /*if ($fileInfo->getExtension() == 'gif') {
		$im = imagecreatefromgif('/var/www/html/media/images/'.$fileInfo->getFilename());
	}
	else {
		// Create image instance
		$im = imagecreatefromjpeg('/var/www/html/media/images/'.$fileInfo->getFilename());

	}
	echo 'Total colors in image: ' . imagecolorstotal($im)."\n";
	// Free image
	imagedestroy($im);*/
}

exit;







echo html_entity_decode('BONNIE O&#039;HARA')."\n";
$text= preg_replace('/&#(\d+);/me',"chr(\\1)",'BONNIE O&#039;HARA');
echo $text."\n";

function utf8_rawurldecode($raw_url_encoded){ 
    $enc = rawurldecode($raw_url_encoded); 
    if(utf8_encode(utf8_decode($enc))==$enc){; 
        return rawurldecode($raw_url_encoded); 
    }else{ 
        return utf8_encode(rawurldecode($raw_url_encoded)); 
    } 
} 
echo utf8_rawurldecode('BONNIE O&#039;HARA')."\n";


function cleanString($in,$offset=null) 
{ 
    $out = trim($in); 
    if (!empty($out)) 
    { 
        $entity_start = strpos($out,'&',$offset); 
        if ($entity_start === false) 
        { 
            // ideal 
            return $out;    
        } 
        else 
        { 
            $entity_end = strpos($out,';',$entity_start); 
            if ($entity_end === false) 
            { 
                 return $out; 
            } 
            // zu lang um eine entity zu sein 
            else if ($entity_end > $entity_start+7) 
            { 
                 // und weiter gehts 
                 $out = cleanString($out,$entity_start+1); 
            } 
            // gottcha! 
            else 
            { 
                 $clean = substr($out,0,$entity_start); 
                 $subst = substr($out,$entity_start+1,1); 
                 // &scaron; => "s" / &#353; => "_" 
                 $clean .= ($subst != "#") ? $subst : "_"; 
                 $clean .= substr($out,$entity_end+1); 
                 // und weiter gehts 
                 $out = cleanString($clean,$entity_start+1); 
            } 
        } 
    } 
    return $out; 
} 

cleanString('BONNIE O&#039;HARA')."\n";


function replace_num_entity($ord)
{
	$ord = $ord[1];
	if (preg_match('/^x([0-9a-f]+)$/i', $ord, $match))
	{
		$ord = hexdec($match[1]);
	}
	else
	{
		$ord = intval($ord);
	}
	
	$no_bytes = 0;
	$byte = array();

	if ($ord < 128)
	{
		return chr($ord);
	}
	elseif ($ord < 2048)
	{
		$no_bytes = 2;
	}
	elseif ($ord < 65536)
	{
		$no_bytes = 3;
	}
	elseif ($ord < 1114112)
	{
		$no_bytes = 4;
	}
	else
	{
		return;
	}

	switch($no_bytes)
	{
		case 2:
		{
			$prefix = array(31, 192);
			break;
		}
		case 3:
		{
			$prefix = array(15, 224);
			break;
		}
		case 4:
		{
			$prefix = array(7, 240);
		}
	}

	for ($i = 0; $i < $no_bytes; $i++)
	{
		$byte[$no_bytes - $i - 1] = (($ord & (63 * pow(2, 6 * $i))) / pow(2, 6 * $i)) & 63 | 128;
	}

	$byte[0] = ($byte[0] & $prefix[0]) | $prefix[1];

	$ret = '';
	for ($i = 0; $i < $no_bytes; $i++)
	{
		$ret .= chr($byte[$i]);
	}

	return $ret;
}

echo preg_replace_callback('/&#([0-9a-fx]+);/mi', 'replace_num_entity', 'BONNIE O&#039;HARA')."\n";
echo replace_num_entity('BONNIE O&#039;HARA');
?>
