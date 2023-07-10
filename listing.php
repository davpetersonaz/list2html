<?php 
session_start();
ob_start();

//include $_SERVER['DOCUMENT_ROOT'] . '/config.php';
include 'config.php';
//$log = new KLogger ( "log.txt" , KLogger::DEBUG );

$m_oid = $_SESSION['m_oid'];
//TODO: you don't have to be logged in to see the listing page
//if (!isset($m_oid))
//{
//	echo "<p class=resultFont>You are not logged in.</p><BR/>";
////	echo "<p class=resultFont><a href='login.php'>Log back in!</a></p>";
//
//	//'loggedout' is a tag in .htaccess that should goto <root>/home.php
//	header("location:loggedout");//this crashes the page - use obstart?
//	exit;
//}

echo"
<HTML>
	<FONT FACE=Arial>
	<TITLE>Dave's List of Shows</TITLE>
	<BODY bgcolor=black text=yellow link=#FF8C00 alink=#00FFFF vlink=#00FFFF>
		<HEAD>
			<center>
				<H1>Dave's CD List</H1>
				<p>
					<a href=".STATIC_PAGES."TradingGuidelines.html>trading guidelines</a><BR/>
					<a href=".STATIC_PAGES."BlanksPostageRules.html>B+P rules - READ BEFORE YOU B+P!!</a><BR/>
				</p>
				<p>
					<B>NOTE</b>: <B>primuslive</B> and <b>livephish</B> shows are not for trade,<BR/>
					trading is limited to public domain / uncopywrited material only.<BR/>
				</p>
				<p>
					<a href=http://userpages.umbc.edu/~hamilton/btclientconfig.html>How BitTorrent works</a><BR/>
					<a href=../links.html>Some good links (burning, etc)</a><BR/>
					<a href=".STATIC_PAGES."CDRMediaInfo.html>information on CD-R Media / etree & trading standards</a><BR/>
				</p>
				<p>
					<a href=http://www.sci.edu/sleepyweasel/weasel05/jambands.html>what's a jam band?</a><BR/>
					<a href=".STATIC_PAGES."ShowsIWant.html>shows i'm looking for -- help me out if you can!</a><BR/>
				</p>
			</center>
		</HEAD>
";

$column1 = "";
$column2 = "";
$sql = "SELECT * FROM artists
		ORDER BY name";
$sql_result = mysql_query($sql) or die('Error, query failed: '.$sql);
while ($row = mysql_fetch_array($sql_result))
	$artists[] = $row;

//first element is the html for the quicklink
//second element is the ongoing "rank" count, so the middle can be found
$anchorLinksRankings = [];
$tiers = [1, 3, 7, 12, 19, 34, 49];
$logRankings = [0, 0, 0, 0, 0, 0, 0, 0];
$rankingsTotal = 0;
foreach ($artists as $artist)
{
	//resize the quickLink font based on number of shows by artist
	$sql = "SELECT * FROM shows
			WHERE artist='".$artists['oid']."'";
	$sql_result = mysql_query($sql) or die('Error, query failed: '.$sql);
	$numShows = mysql_num_rows($sql_result);

	if ($numShows > $tiers[6])
	{
		$fontResizing = 240;
		$logRankings[7]++;
	}
	else if ($numShows > $tiers[5])
	{
		$fontResizing = 210;
		$logRankings[6]++;
	}
	else if ($numShows > $tiers[4])
	{
		$fontResizing = 180;
		$logRankings[5]++;
	}
	else if ($numShows > $tiers[3])
	{
		$fontResizing = 155;
		$logRankings[4]++;
	}
	else if ($numShows > $tiers[2])
	{
		$fontResizing = 130;
		$logRankings[3]++;
	}
	else if ($numShows > $tiers[1])
	{
		$fontResizing = 105;
		$logRankings[2]++;
	}
	else if ($numShows > $tiers[0])
	{
		$fontResizing = 80;
		$logRankings[1]++;
	}
	else
	{
		$fontResizing = 60;
		$logRankings[0]++;
	}
	$rankingsTotal += $fontResizing;

	//TODO: ????
	$quickLink = "<center><a href=#".preg_replace(STRIP_CHARS, "", $artist).
				" style=text-align:center;text-decoration:none;font-family:arial;font-size:$fontResizing%>"
				.$artist['name']."<BR/></a></center>";

	//System.out.println("["+numShows+"] shows for ["+anchorArtist.getFullName()+"]");
	$anchorLinksRankings[$rankingsTotal] = $quickLink;
}

$log = new KLogger ("log.txt", KLogger::DEBUG);
$log->LogDebug("num of artists with shows in the ".count($tiers)." tiers:");
$log->LogDebug(    ($tiers[6]+1).">"           ." ... ".$logRankings[7]);
$log->LogDebug(    ($tiers[5]+1)."-" .$tiers[6]." ... ".$logRankings[6]);
$log->LogDebug(    ($tiers[4]+1)."-" .$tiers[5]." ... ".$logRankings[5]);
$log->LogDebug(    ($tiers[3]+1)."-" .$tiers[4]." ... ".$logRankings[4]);
$log->LogDebug(" ".($tiers[2]+1)."-" .$tiers[3]." ... ".$logRankings[3]);
$log->LogDebug(" ".($tiers[1]+1)."- ".$tiers[2]." ... ".$logRankings[2]);
$log->LogDebug(" ".($tiers[0]+1)."- ".$tiers[1]." ... ".$logRankings[1]);
$log->LogDebug(                "  < ".$tiers[0]." ... ".$logRankings[0]);

foreach ($anchorLinksRankings as $key => $value)
{
	if ($key < $rankingsTotal / 2)
		$column1[] = $value;
	else
		$column2[] = $value;
}

echo"
		<br><H2>Quick Access Links</H2>
";

$i = 0;
for ($i=0; $i<count($column1); $i++)
	echo"
		<div align=center>
			<div>".$column1[$i]."</div>
			<div>".$column2[$i]."</div>
		</div>
";

//on to the shows...

$sql = "SELECT * FROM artists
		ORDER BY sequence";
$sql_result = mysql_query($sql) or die('Error, query failed: '.$sql);
while ($artist = mysql_fetch_array($sql_result))
{
	$logoFileName = stripChars($artist['name']) . "Logo";
	if (file_exists($logoFileName))
	{
		echo "
		<H2>
			<IMG SRC=" . PICS_DIR . $artist['logo'] . " BORDER=0 " . getimagesize($logoFileName)[3] . " ALT=\"" . $artist['name'] . "\">
		</H2>
";
	}
	else
	{
		$log = new KLogger ("log.txt", KLogger::DEBUG);
		$log->LogError("missing logo: $logoFileName");
		echo"
		<H2>
			".$artist['logo']."
		</H2>
";
	}

	/**
	 * if new year in artist chronology, add blank line or year
	 */

	$sql2= "SELECT COUNT(DISTINCT year) AS distinctYears
			FROM shows
			WHERE artist='$artist'";
	$result2 = mysql_query($sql2) or die ("error: $sql2");
	$data = mysql_fetch_assoc($result2);
	$distinctYears = $data['distinctYears'];

	$sql2= "SELECT * FROM shows
			WHERE artist='$artist'
			ORDER BY date";
	$result2 = mysql_query($sql2) or die ("error: $sql2");
	$numShows = mysql_num_rows($result2);
	$yearSpacer = ($numShows > 25 || $distinctYears > 7);

	$previousYr = "";
	while ($show = mysql_fetch_array($result2))
	{
		if ($show['year'] !== $previousYr)
		{
			if ($yearSpacer)
				echo "
		<FONT SIZE=-1><BR/><B>" . $show['year'] . "</B><BR/><BR/></FONT>
";
			else
				echo "
		<FONT SIZE=-1><BR/></FONT>
";
			$previousYr = $show['year'];
		}

		/**
		 * add link to the show's html page in the index html
		 */

		$recordingTypeStr = isNullOrEmptyString($show['recordingtype']) ? "" : " - " . $show['recordingtype'];
		$ratingStr = isNullOrEmptyString($show['rating']) ? "" : " - " . $show['rating'];
		if (isNullOrEmptyString($show['title']))//phish-type
		{
			echo "
		<A HREF=show?id=".$show['oid'].">
				<B>" . $show["DATE_FORMAT(date, '%m/%e/%Y')"] . "</B> -
				" . substr(getVenue($show), 30)
				. $recordingTypeStr . $ratingStr . "
		</A><BR/>
";
		}
		else//bootleg-type
		{
			echo "
		<A HREF=show?id=".$show['oid'].">
				<B>" . $show['title'] . "</B> -
				" . $show["DATE_FORMAT(date, '%m/%e/%Y')"] . " -
				" . $recordingTypeStr . $ratingStr . "
		</A><BR/>
";
		}

	}//each show

}//each artist

ob_end_flush();
?>
