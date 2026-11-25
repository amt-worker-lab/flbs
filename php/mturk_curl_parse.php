<?php

set_time_limit(300); // five min.

require_once('curl_mult_request.php');

error_reporting(E_ALL);

//
$requester = new MultipleRequester();
//
$ids	= array();
//
$urls	= array();
//
$tcount = 0;
//
$requesters = requesters_array();
//

//
connect_();

//
// Default page is sort by most HITs available
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://www.mturk.com/mturk/findhits?match=false');
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20); // 16 second max connect 
curl_setopt($ch, CURLOPT_TIMEOUT, 20); // 20 second max operation time
curl_setopt($ch, CURLOPT_POST, false);
$r=curl_exec($ch);

//
addBody($r);

//
//"/mturk/viewhits?searchWords=&amp;selectedSearchType=hitgroups&amp;sortType=NumHITs%3A1&amp;pageNumber=2&amp;searchSpec=HITGroupSearch%23T%231%2310%23-1%23T%23%21%23%21NumHITs%211%21%23%21"
//preg_match('/<a href="\/mturk\/viewhits\?searchWords=(.+?)"/', $r, $m); 
preg_match('/<a href="\/mturk\/viewhits\?searchWords=(?:.+?)sortType=(.+?)&(?:.+?)searchSpec=(.+?)"/', $r, $m); 

//
$m[1] = 'http://www.mturk.com/mturk/viewhits?searchWords=&selectedSearchType=hitgroups&sortType=' . $m[1];

//
for ($i=2; $i<=12; $i++)
{
	//
	array_push($urls, $m[1] . '&pageNumber=' . $i . '&searchSpec=' . $m[2]);
}

//print_r($urls);



//
//
//
foreach( $urls as $key => $url )
{
	//
	$requester->addGetRequest( $key, $url );
}
 
// fetch the N urls at a time until there are no more
while( $requester->moreRemaining() )
{
	//
	$requester->execute(4); # Num to execute at a time
	
	sleep(6);
}

//
foreach( $urls as $key => $url )
{	//$lastURL = trim($requester->getEffectiveURL($key)); // (skip if root not in url?)
	// add urls if page status code less than 400
	//if ( $requester->getStatusCode($key) < 400 )
	
	addBody( $requester->getContent($key) );
	
}


// TEST MPLAYER
// WORKS HERE!
//shell_exec('/usr/bin/gnome-mplayer --display=:0.0 --volume 100 --random --playlist "/var/www/html/media/audio/AMT ALERT PLAYLIST.pls"');


#
# set lastSeen to 0
if ( count($ids) > 0 && $tcount == 12 ) // ##### tcount //
{
	mysql_query('UPDATE mturk_HITs.HITs
				SET
					lastSeen = 0
				WHERE
					id NOT IN ('. implode(',', $ids) .')');	
}
	
// db close
close_();





/******
 * 
 * 
 * 
 * 
 * 
**/
 
function connect_() {
	@mysql_connect('localhost', 'root', '_redacted_') or die('err'.mysql_error());
}
function close_() {
	mysql_close();
}
function mail_alert($r, $t, $h) {
	
	$m = "$t\r\n$h";
	$m = wordwrap($m, 70, "\r\n");
	//mail("_redacted_@messaging.sprintpcs.com", "$r-$t", $m);
	
	$a = mail("_redacted_.com", wordwrap("$r-$t", 70, "\r\n"), $m);
	$b = mail("_redacted_@messaging.sprintpcs.com", substr("$r-$t", 0, 40), substr($m, 0, 120));
	
	file_put_contents('./var/www/html/mturk_curl_parse.log', $r.'||'.$t.'||'.$h.'||'."\r\n", FILE_APPEND);
	
	// della alert
	if (
		$r == 'Della Nelson' ||
		$r == 'David Sugrue' ||
		$r == 'Scott McCaskill' ||
		$r == 'Andy Sparks'
	   )
	{
		// "play [filename]" also works?
		shell_exec('/usr/bin/gnome-mplayer --display=:0.0 --volume 100 --random --playlist "/var/www/html/media/audio/AMT ALERT PLAYLIST.pls"');
	}
}
function addBody($body)
{
	
	//
	global $ids, $tcount, $requesters;
	
	//
	$body=str_replace("\n","",$body);
	//
	preg_match_all('/<a class="capsulelink" href="#" id="capsule(?:.+?)">(.+?)<\/a>(?:.+?)<a href="\/mturk\/searchbar\?selectedSearchType=hitgroups&amp;requesterId=([^&"]+)[^"]{0,}">(.+?)<\/a>(?:.+?)number_of_hits(?:.+?)(\d{3,})/', $body, $m);
		
	
	// HIT title,requester name,# available
	if ( isset ($m) && count($m[1]) == 10 )
	{	//
		// increment tcount
		//
		$tcount++;
		
		$c = 0;
		foreach ($m[1] as $k=>$t)
		{
			$t		= trim($t); // title
			$rid	= $m[2][$c]; // requester id
			$r		= $m[3][$c]; // requester name
			$h		= $m[4][$c]; // number of HITs
			
			//if (in_array($rid, $requesters)) { //debug
			//	mail_alert($r,$t,$h);
			//}
			
			$res = mysql_query('SELECT * FROM mturk_HITs.HITs
							WHERE
								title		= "'.mysql_real_escape_string($t).'" &&
								requester	= "'.mysql_real_escape_string($r).'"
							LIMIT 1');
							
			if (mysql_num_rows($res) > 0)
			{
				$row = mysql_fetch_array($res);
				
				#
				array_push($ids, $row['id']);
				
				#if ( time() - $row['lastSeen'] > 28800 ) // last seen + hits num 8hrs
				if ( $row['lastSeen'] == 0 ) // reappeared HIT
				{
					mysql_query('UPDATE mturk_HITs.HITs
								SET
									lastSeen	= "'.time().'",
									HITs_num	= '.$h.'
								WHERE
									id='.$row['id']);
									
					# mail out alert if job requester in alerts
					if (in_array($rid, $requesters)) {
						mail_alert($r,$t,$h);
					}
				}
				else // hits num update
				{
					mysql_query('UPDATE mturk_HITs.HITs
								SET
									HITs_num = '.$h.'
								WHERE
									id='.$row['id']);
				}
			}
			else
			{
				mysql_query('INSERT INTO mturk_HITs.HITs
							 SET
								title		= "'.mysql_real_escape_string($t).'",
								requester	= "'.mysql_real_escape_string($r).'",
								HITs_num	= '.$h.',
								lastSeen	= '.time());
				
				#				
				array_push($ids, mysql_insert_id());
				
				# mail out alert if job requester in alerts
				if (in_array($rid, $requesters)) {
					mail_alert($r,$t,$h);
				}
			}
			
			$c++;
		}
	}
}
function requesters_array() {
	return array(
				'A34BT2M69CYE0J',
				'A3FM95L8KYUGQ6',
				'A3KOVNWZH0M1PT',
				'A2PWX477JB1CVF',
				'A2YORC5MI5FYTF',
				'A3G4L9RC365LU',
				'A1B1HKBOWQ4RNE',
				'A2DVDVH9CYUR1K',
				'A2Y357C4VTD3OZ',
				'A25WGUNTLZ1RW1',
				'A5F2FL1U15JM',
				'A00147046S7WXS8QXI4I',
				'AFAOUHS65HNDS',
				'A22RZRN3Y7AQW1',
				'ASWXJ5RSIB60M',
				'A34Q9BWURAVPI4',
				'A2T2YI6AR65K4A',
				'A07488853E1BGFBN0V1PL',
				'A2XXUIAS25CP00',
				'A29GW9399Z0UXX',
				'A21QRVCT3HFY4M',
				'AX4SPY375574B',
				'A2VA72TOUC9ARR',
				'A3H5R5EDERAAH7',
				'A266EDFZKL9UXR',
				'A2X84UPYIFRFH2',
				'AEXDHB2F1AMN2',
				'A163U5BOKXOWX3',
				'AQRLC5QMT9X9R',
				'A1PCWPZRTOKMOT',
				'A1QW3TOUFFPYE2',
				'A3H0CWB5LNV3K',
				'A2J8CY87BNWCWS',
				'AN89VSP0TG0NW',
				'A2O3OTL30IRUV2',
				'A1X3YGKV0J0C2',
				'A2NAP9IJ473NHI',
				'A3RYMN96RLLCXU',
				'A335H3CPIAZ6CT',
				'ALSV21XJ4VKWE',
				'AUTU9YQ14VGI8',
				'A38NCT9WOGLAM1',
				'A2BHM0JTJJYU2D',
				'A2F6SFCZIA1ZL9',
				'A29P1VFMKWSA',
				'A3QN1AD5CE3JCN',
				'A3JN0CZ97VKEJO',
				'A06104152HPHL3XTQOOLG',
				'AHVDF3HVRWNF7',
				'A2K1UFVN8QGI2J',
				'A3ND5SSFVTXOFR',
				'A1Q95H2J1PDPCW',
				'A1QIDJ2OJWX8E0',
				'A2X3CRKNRHIH4Z',
				'AHAKWB8C7YS0W',
				'A1ZI6TTOV1K2YX',
				'A3K0GLLLVVOVZO',
				'A7PAD3PWRQQV5',
				'AJ1ZLZBZXBUCO',
				'A3BB55YQLFWKX2',
				'AVROWC2U7F0PL',
				'A23BXZMR2O31NP',
				'A3I9C8OA9GYJHD',
				'A1VP5D3XI43QX8', // SS
				'A3EHOJWG9ZUZLJ', // christopher bail
				'A1PZCAZLCQLH6U', // garry gorman
				'A14UKINVM3AI5U', // maya sen
				'A307U62YWUNUKP', // DW
				'A3QXU878XEJP4G', // Clarence Wardell
				'A1TVZM44D3577E', // Amicus Labs / judicata / automan
				'A1NSJPDXY0ZLHB', // Jim
				'AW6OKFW9O4LL0', // x8 data
				'A4CYMSIU2D4N2', // Cine Phil
				'A7SMOSB00EL4Y', // Birkam Uni Research
				'A1G5S03EGW1ED6', // Andrew Brown
				'A23GA8QO6Y5LW', // Sara Adams
				'A3BBH4FSUVG60F', // Lakewood Design
				'A2N562UK89D82M', // Bob
				'A3IJ63M7NSKSN6', // David Vigil
				'A3VLZQ1M9K4A9X', // HOWARD EIFERMAN
				'A2ZGBI3CIS8HM', // John Roberts
				'A25A3BO8955RKM', // Anand Kishore
				'A2SHOMRGSBKEFF', // exl022
				'A281ZPO7J9PWUN', // Kristiana Laugen 
				'AI6IJM3MMPVIG', // Jordan A Gross 
				'A2RP245WWSUELO', // HelloWallet
				'A2VD7XET6DRF8M', // R H
				'A36LWNR0K8QTWZ', // Timothy Simcoe
				'AM7C96DWPL8UF', // hidden data (aka travis mccollum)
				'A1Y7M77YN71H0T', // Five Star Professional
				'AO1GS3CMM2IGY', // Five Star Professional
				'AMY1QXVXT7JX', // Brandon Ramirez, Input Data From Yelp, Sep. 27, 2013, Masters, 0.10
				'ASCFKGHZNZ3B7', // 4578458934, i.e. numbers
				'A3RU55LPCCZNQR', // 4578458934 -- second account? (not in), i.e. numbers
				'AOZDAKDJ4NZF0', // Matt (not in), i.e. places.google.com business verified Aug16,13
				'A2FMGVP37X3FIY', // Drew (not in), i.e. mykellerwilliams.com search
				'A34Q9BWURAVPI4', // Darrell DeVeaux (not in)
				'A18FS5IWKCNH83', // Kevin S Agnew (not in)
				'A2O08YO5LWXIN5', // Frederick W. Taylor (not in)
				'AUN8AE8UCO3MD', // Triangulate (not in)
				'AE2MGHJOWILQ4', // David Camacho (not in) "Completion time per HIT is 10 seconds or less, which is abnormally below the average completion time."
				'A30Z62VL4RVP7O', // John C Triggs
				'A23BFOVOOKCADW', // Max Powers
				'A3AZIIU9Q53B2M', // alec / Sam Molyneux
				// (Not in)
				// J Turk
				
				
				// new
				'AWT2CVNDVVAJC', // Della Nelson
				'A3RMXZ573H498B', // David Sugrue
				'A3FFMLLNY1QNDO', // Aaron Zwas
				'AURP01N7NLM27', // Scott McCaskill
				'A1RG4I0LXUWICB' // Matthew Purdon
				);
}
?>
