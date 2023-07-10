<?php 
session_start();
ob_start();

//include $_SERVER['DOCUMENT_ROOT'] . '/config.php';
include 'config.php';

$m_oid = $_SESSION['m_oid'];
//TODO: popup a user login if the "edit" button is clicked only...
//if (!isset($m_oid))
//{
//	echo "<p class=resultFont>You are not logged in.</p><BR/>";
//	echo "<p class=resultFont><a href='login.php'>Log back in!</a></p>";
//
//	//'loggedout' is a tag in .htaccess that should goto <root>/home.php
//	header("location:loggedout");//this crashes the page - use obstart?
//	exit;
//}

$showOid = $_GET['id'];
$sql1 = "SELECT FROM shows
		WHERE oid='$showOid'";
$sql_result1 = mysql_query($sql1) or die('Error, query failed: '.$sql1);
if ($row = mysql_fetch_array($sql_result1))
{
	$artistOid = $row['artist'];
	$date = $row['date'];
	$sequence = $row['sequence'];
	$title = $row['title'];
	$recordingType = $row['recordingtype'];
	$shnBook = $row['shnbook'];
	$shnPage = $row['shnpage'];
	$rating = $row['rating'];
	$phishComp = $row['phishcompanion'];

	/**
	 * retrieve everything first...
	 */

	$sql = "SELECT FROM artists
			WHERE oid='$artistOid'";
	$sql_result = mysql_query($sql) or die('Error, query failed: '.$sql);
	$i=0;
	if ($row = mysql_fetch_array($sql_result))
		$artist = $row['name'];

	//TODO: figure out if other versions exist at the end of the page.

	$sql = "SELECT FROM locations
			WHERE show='$showOid'
			ORDER BY sequence";
	$sql_result = mysql_query($sql) or die('Error, query failed: '.$sql);
	$i=0;
	while ($row = mysql_fetch_array($sql_result))
	{
		$location[$i++] = $row['data'];
	}

	$sql = "SELECT FROM myinfo
			WHERE show='$showOid'
			ORDER BY sequence";
	$sql_result = mysql_query($sql) or die('Error, query failed: '.$sql);
	$i=0;
	while ($row = mysql_fetch_array($sql_result))
	{
		$myInfo[$i++] = $row['data'];
	}

	$sql = "SELECT FROM tracklisting
			WHERE show='$showOid'
			ORDER BY sequence";
	$sql_result = mysql_query($sql) or die('Error, query failed: '.$sql);
	$i=0;
	while ($row = mysql_fetch_array($sql_result))
	{
		$trackListing[$i++] = $row['data'];
	}

	$sql = "SELECT FROM theband
			WHERE show='$showOid'
			ORDER BY sequence";
	$sql_result = mysql_query($sql) or die('Error, query failed: '.$sql);
	$i=0;
	while ($row = mysql_fetch_array($sql_result))
	{
		$theBand[$i++] = $row['data'];
	}

	$sql = "SELECT FROM footnotes
			WHERE show='$showOid'
			ORDER BY sequence";
	$sql_result = mysql_query($sql) or die('Error, query failed: '.$sql);
	$i=0;
	while ($row = mysql_fetch_array($sql_result))
	{
		$footnotes[$i++] = $row['data'];
	}

	$sql = "SELECT FROM sourceinfo
			WHERE show='$showOid'
			ORDER BY sequence";
	$sql_result = mysql_query($sql) or die('Error, query failed: '.$sql);
	$i=0;
	while ($row = mysql_fetch_array($sql_result))
	{
		$sourceInfo[$i++] = $row['data'];
	}

	$sql = "SELECT FROM notes
			WHERE show='$showOid'
			ORDER BY sequence";
	$sql_result = mysql_query($sql) or die('Error, query failed: '.$sql);
	$i=0;
	while ($row = mysql_fetch_array($sql_result))
	{
		$notes[$i++] = $row['data'];
	}

	$sql = "SELECT FROM otherinfo
			WHERE show='$showOid'
			ORDER BY sequence";
	$sql_result = mysql_query($sql) or die('Error, query failed: '.$sql);
	$i=0;
	while ($row = mysql_fetch_array($sql_result))
	{
		$otherinfo[$i++] = $row['data'];
	}

	/**
	 * now start the page html...
	 */

	echo"
<HTML>
	<FONT FACE=Arial>
	<BODY bgcolor=black text=yellow link=#FF8C00 alink=#00FFFF vlink=#00FFFF>
	<TITLE>
";

	//title bar and header 1 (artist)
	if (isNullOrEmptyString($title))
		echo"
		$artist - $date
";
	else
		echo"
		$artist - $title
";

	echo"
	</TITLE>
	<H1>$artist</H1>
";

	//header 2: title/date, venue, city, state
	if (isNullOrEmptyString($title))
	{
		echo"
	<H2>
		$date<BR/>
";
		foreach ($location as $line)
			echo"
		$line<BR/>
";
		echo"
	</H2>
";
	}
	else //bootleg with a title
	{
		echo"
	<H1>$title</H1>
	<H2>
";
		foreach ($location as $line)
			echo"
		$line<BR/>
";
		if (!isNullOrEmptyString($date))
			echo"
		$date<BR/>
";
		echo"
	</H2>
";
	}
	
	//Header3 - recording type, rating, my info lines
	echo"
	<H3>
		recording type: $recordingType<BR/>
";

	if (isNullOrEmptyString($shnBook))
		$shnInfo = "shn: not yet";
	else	//has been archived
		$shnInfo = "shn: yes; book/page: $shnBook/$shnPage";
	echo"
		$shnInfo<BR/>
		quality rating: $rating<BR/>
	</H3>
";

	foreach ($myInfo as $line)
		echo"
	<!--$line-->
";

	//song list - set details
	if (!empty($tracklisting))
	{
		echo"
	<P>
";
		foreach ($tracklisting as $track)
			echo"
		$track<BR/>
";
		echo"
	</P>
";
	}
	else
	{
		echo"
	<P>no tracks defined</P>
";
	}

	//other info, such as show billing, or guests that played the entire show
	if (!empty($otherInfo))
	{
		echo"
	<P>
";
		foreach ($otherInfo as $line)
			echo"
		$line<BR/>
";
		echo"
	</P>
";
	}

	//The Band members
	if (!empty($theBand))
	{
		echo"
	<P>
	";
		foreach ($theBand as $line)
			echo"
		$line<BR/>
";
		echo"
	</P>
";
	}

	//footnotes
	if (!empty($footnotes))
	{
		echo"
	<P>
";
		foreach ($footnotes as $footnote)
			echo"
		$footnote<BR/>
";
		echo"
	</P>
";
	}

	//source info, including transfer, master, taper, etc
	if (!empty($sourceInfo))
	{
		echo"
	<P>
";
		foreach ($sourceInfo as $line)
			echo"
		$line<BR/>
";
		echo"
	</P>
";
	}

	//links to other versions of this show
	$sql = "SELECT * FROM shows
			WHERE date='$date' AND artist='$artistOid'
			ORDER BY sequence";
	$sql_result = mysql_query($sql) or die('Error, query failed: '.$sql);
	if (mysql_num_rows($sql_result) > 1)
	{
		while ($row = mysql_fetch_array($sql_result))
		{
			if ($sequence !== $row['sequence'])
				echo"
		<p><a href=show?id=$showOid.php>source ".($row['sequence']+1)."</a></p>
";
			else
				echo"
		<p>this source</p>
";
		}
		echo"
		<BR/>
";
	}

}//end -- detail show if exists

//link back to the list
echo"
		<p><a href=listing.php>back to the cd list</a></p>
	</BODY>
	</FONT>
</HTML>
";

ob_end_flush();
?>