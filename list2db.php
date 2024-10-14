<?php
session_start();
ob_start();

//include $_SERVER['DOCUMENT_ROOT'] . '/config.php';
include 'config.php';

$_SERVER['artistSequencer'] = 0;

//open shows file
$handle = @fopen($SHOWS_FILE, "r");
if ($handle)
{
	try
	{
		$haventFoundFirstShowYet = true;
		while (($buffer = getLine($handle)) !== false)
		{
			//find the first show
			if ($haventFoundFirstShowYet)
				if (identifyLine($buffer) !== DASHED_LINE)
					continue;
		
			$haventFoundFirstShowYet = false;
			processShow($handle);
		}
	}
	catch (Exception $e)
	{
		if ($e !== "EOF")
			echo "error while reading: ".$e;
	}
	fclose($handle);	
}

function processShow($handle)
{
	//$artist = "";//not necessary, it is overwritten below
	$date = "";
	$datePostfix = "";
	$title = "";
	$venue = "";
	$discs = "";
	$recordingType = "";
	$shnBook = "";
	$shnPage = "";
	$rating = "";
	$myInfo = "";
	$tracks = "";
	$footnotes = "";
	$phishComp = "";
	$theBand = "";
	$sourceInfo = "";
	$notes = "";
	$otherInfo = "";

	//1st line is a blank line
	$line = getLine($handle);
	if (identifyLine($line) === BLANK_LINE)
		$line = getLine($handle);

	//artist line
	$artist = $line;
	$line = getLine($handle);

	//date, or bootleg title
	if (identifyLine($line) === DATE_LINE)
	{   //phish-type of show (date, not title)
		$century = (substr($line, 7, 8) > date('y') ? '19' : '20');
		$datestr = substr_replace(substr($line, 0, 8), $century, 7, 0);
		if (($date = date_create_from_format("m-d-Y", $datestr)) === false){
			echo "invalid date format: $line ($artist)";
		}
		if (strlen($line) > 8){
			$datePostfix = substr($line, 8);
		}
	}
	else
	{   //non-phish type of show (bootleg title, not the date)
		$title = $line;
	}

	//venue/city/state lines
	$i = 0;
	$line = getLine($handle);
	$lineType = identifyLine($line);
	while ($lineType !== TOTAL_DISCS_LINE
		&& $lineType !== DATE_LINE
		&& $lineType !== MY_INFO_LINE
		&& $lineType !== DAUD_SBD_LINE)
	{
		$venue[$i++] = $line;
		$line = getLine($handle);
		$lineType = identifyLine($line);
	}

	//date line, if a bootleg-type
	if (identifyLine($line) === DATE_LINE)
	{
		if (($date = date_create_from_format("F n, Y", $line)) === false)
			echo "invalid date format: $line ($artist)";
		$line = getLine($handle);
	}

	//total discs line
	if (identifyLine($line) === TOTAL_DISCS_LINE)
	{
		$discs = substr($line, 0, strpos($line, " "));
		$line = getLine($handle);
	}

	//AUD-SBD line, including SHN location
	if (identifyLine($line) === DAUD_SBD_LINE)
	{
		$split = strpos($line, ", ");
		if (substr($line, 0, 4) !== "SHN-")
		{
			if ($split === false)
				$recordingType = $line;
			else
				$recordingType = substr($line, 0, $split);
		}
		if (strpos($line, "SHN-") >= 0)
		{
			$shnBook = substr($line, strpos($line, "SHN-")+4, strrpos($line, "-"));
			$shnPage = substr($line, strrpos($line, "-")+1);
		}
		$line = getLine($handle);
	}

	//rating
	if (strpos($line, "A") === 0 || strpos($line, "B") === 0 || strpos($line, "C") === 0 || strpos($line, "D") === 0)
	{
		$rating = $line;
		$line = getLine($handle);
	}

	//not-tradeable shows
	if (identifyLine($line) === NOT_TRADEABLE_LINE)
	{
		echo "Not tradeable show, aborting ($artist $date)";
		return;
	}

	//non-public info lines
	$i = 0;
	while (identifyLine($line) === MY_INFO_LINE)
	{
		$myInfo[$i++] = $line;
		$line = getLine($handle);
	}

	//blank line between header and songs
	if (identifyLine($line) === BLANK_LINE)
		$line = getLine($handle);

	//song lines, including set and disc lines
	$i = 0;
	$lineType = identifyLine($line);
	while ($lineType === DISC_LINE || $lineType === SET_LINE || $lineType === SONG_LINE || $lineType === BLANK_LINE)
	{
		$tracks[$i++] = $line;
		$line = getLine($handle);
		$lineType = identifyLine($line);
	}

	//post- track listings (notes, band info, footnotes, etc)	
	$footnote_idx = 0;
	$theBand_idx = 0;
	$source_idx = 0;
	$note_idx = 0;
	$other_idx = 0;
	while (identifyLine($line) !== DASHED_LINE)
	{
		if (identifyLine($line) === COMMENT_REST_LINE)
			break;

		if (identifyLine($line) === BLANK_LINE)
		{
			$line = getLine($handle);
			continue;
		}

		//there can be many footnotes per show
		if (identifyLine($line) === FOOTNOTE_LINE)
		{
			$temp = $line;
			$line = getLine($handle);
			while (identifyLine($line) === NORMAL_LINE)
			{
				$temp = $temp." ".$line;
				$line = getLine($handle);
			}
			$footnotes[$footnote_idx++] = $temp;
			continue;
		}

		//phish companion is only one paragraph, never multiples
		if (identifyLine($line) === PH_COMP_LINE)
		{
			$phishComp = $line;
			$line = getLine($handle);
			while (identifyLine($line) === NORMAL_LINE)
			{
				$phishComp = $phishComp." ".$line;
				$line = getLine($handle);
			}
			continue;
		}

		if (identifyLine($line) === THE_BAND_LINE)
		{
			$theBand[$theBand_idx++] = $line;
			$line = getLine($handle);
			while (identifyLine($line) === NORMAL_LINE)
			{
				$theBand[$theBand_idx++] = $line;
				$line = getLine($handle);
			}
			continue;
		}

		//there can be many sources per show
		if (identifyLine($line) === SOURCE_LINE)
		{
			$temp = $line;
			$line = getLine($handle);
			while (identifyLine($line) === NORMAL_LINE)
			{
				$temp = $temp." ".$line;
				$line = getLine($handle);
			}
			$sourceInfo[$source_idx++] = $temp;
			continue;
		}

		//there can be many notes per show
		if (identifyLine($line) === NOTE_LINE)
		{
			$temp = $line;
			$line = getLine($handle);
			while (identifyLine($line) === NORMAL_LINE)
			{
				$temp = $temp." ".$line;
				$line = getLine($handle);
			}
			$notes[$note_idx++] = $temp;
			continue;
		}

		if (identifyLine($line) === NORMAL_LINE)
		{
			$temp = $line;
			$line = getLine($handle);
			while (identifyLine($line) === NORMAL_LINE)
			{
				$temp = $temp." ".$line;
				$line = getLine($handle);
			}
			$otherInfo[$other_idx++] = $temp;
			continue;
		}
	}

	$theShow = 
	[
		$artist,
		$date,
		$datePostfix,
		$title,
		$venue,
		$discs,
		$recordingType,
		$shnBook,
		$shnPage,
		$rating,
		$myInfo,
		$tracks,
		$footnotes,
		$phishComp,
		$theBand,
		$sourceInfo,
		$notes,
		$otherInfo
	];
	finishShow($theShow);
}

function finishShow($theShow)
{
	list(	$artist,
			$date,
			$datePostfix,
			$title,
			$venue,
			$discs,
			$recordingType,
			$shnBook,
			$shnPage,
			$rating,
			$myInfo,
			$tracks,
			$footnotes,
			$phishComp,
			$theBand,
			$sourceInfo,
			$notes,
			$otherInfo
		) = $theShow;

	//does artist exist
	$artist_oid = "";
	$sql = "SELECT * FROM artists
			WHERE name='$artist'";
	$sql_result = mysql_query($sql) or die('Error, query failed: '.$sql);
	if ($row = mysql_fetch_array($sql_result))
		$artist_oid = $row['oid'];

	//create artist if it doesn't exist
	if (isNullOrEmptyString($artist_oid))
	{
		//TODO: find the logo

		//add the artist
		$sql = "INSERT INTO artists
					(name, sequence)
				VALUES
					('$artist', '".($_SERVER['artistSequencer']++)."')";
		mysql_query($sql) or die('Error, query failed: '.$sql);
		$artist_oid = mysql_insert_id();
	}

	//see if this show already exists
	$sequence = 0;
	$sql = "SELECT * FROM shows
			WHERE artist='$artist_oid' AND date='".$date."'
			ORDER BY sequence";
	$sql_result = mysql_query($sql) or die('Error, query failed: '.$sql);
	if (mysql_num_rows($sql_result) > 0)
	{
		//on initial load, this should add to the end of the sequence for this show, cuz that's how they are in the list.
		//moving ahead, we'll want to add the new show to the head of the list, cuz it'll most likely be an "upgrade".
		while ($row = mysql_fetch_array($sql_result))
		{
			$sequence = $row['sequence']+1;
		}
	}

	//add the show
	$sql = "INSERT INTO shows
				(
					artist,
					date,
					sequence,
					datepostfix,
					title,
					discs,
					recordingtype,
					shnbook,
					shnpage,
					rating,
					phishcompanion
				)
			VALUES
				(
					$artist_oid,
					$date,
					$sequence,
					$datePostfix,
					$title,
					$discs,
					$recordingType,
					$shnBook,
					$shnPage,
					$rating,
					$phishComp
				)";
	mysql_query($sql) or die('Error, query failed: '.$sql);
	$show_oid = mysql_insert_id();

	//add venue/city/state
	foreach ($venue as $i => $value)
	{
		$sql = "INSERT INTO location
					(show, sequence, data)
				VALUE
					('$show_oid', '$i', '$venue[$i]')";
		mysql_query($sql) or die('Error, query failed: '.$sql);
	}

	//add myInfo (non-public) lines
	foreach ($myInfo as $i => $value)
	{
		$sql = "INSERT INTO myinfo
					(show, sequence, data)
				VALUE
					('$show_oid', '$i', '$myInfo[$i]')";
		mysql_query($sql) or die('Error, query failed: '.$sql);
	}

	//add track listing lines
	foreach ($tracks as $i => $value)
	{
		$sql = "INSERT INTO tracklisting
					(show, sequence, data)
				VALUE
					('$show_oid', '$i', '$tracks[$i]')";
		mysql_query($sql) or die('Error, query failed: '.$sql);
	}

	//add footnote lines
	foreach ($footnotes as $i => $value)
	{
		$sql = "INSERT INTO footnotes
					(show, sequence, data)
				VALUE
					('$show_oid', '$i', '$footnotes[$i]')";
		mysql_query($sql) or die('Error, query failed: '.$sql);
	}

	//add The Band lines
	foreach ($theBand as $i => $value)
	{
		$sql = "INSERT INTO theband
					(show, sequence, data)
				VALUE
					('$show_oid', '$i', '$theBand[$i]')";
		mysql_query($sql) or die('Error, query failed: '.$sql);
	}

	//add source info lines
	foreach ($sourceInfo as $i => $value)
	{
		$sql = "INSERT INTO sourceinfo
					(show, sequence, data)
				VALUE
					('$show_oid', '$i', '$sourceInfo[$i]')";
		mysql_query($sql) or die('Error, query failed: '.$sql);
	}

	//add notes lines
	foreach ($notes as $i => $value)
	{
		$sql = "INSERT INTO notes
					(show, sequence, data)
				VALUE
					('$show_oid', '$i', '$notes[$i]')";
		mysql_query($sql) or die('Error, query failed: '.$sql);
	}

	//add other info lines
	foreach ($otherInfo as $i => $value)
	{
		$sql = "INSERT INTO otherinfo
					(show, sequence, data)
				VALUE
					('$show_oid', '$i', '$otherInfo[$i]')";
		mysql_query($sql) or die('Error, query failed: '.$sql);
	}
}

function identifyLine($line)
{
	$line = trim($line);

	//show separator line
	if (strpos($line, "---------------------------------") >= 0)
		return DASHED_LINE;

	if (strpos($line, "NOT TRADEABLE") === 0)
		return NOT_TRADEABLE_LINE;

	if (strpos($line, "[disc ") === 0)
		return DISC_LINE;

	if (strpos($line, "[show]") === 0)
		return DISC_LINE;

	//Phish Companion notes
	if (strpos($line, "pc:") === 0)
		return PH_COMP_LINE;

	if (strpos($line, "http://") >= 0)
		return HTTP_LINE;

	//"The xxx Band:" is allowed...
	if (strpos($line, "The") === 0 && strpos($line, " Band:") >= 3)
		return THE_BAND_LINE;

	if (strpos($line, "//") === 0)
		return MY_INFO_LINE;

	if (strpos($line, "/**") === 0)
		return COMMENT_REST_LINE;

	if ($line === "")
		return BLANK_LINE;

	if (
			preg_match('/^BOOKMARK:\ /', $line) === 1 ||
			preg_match('/^BROADCAST(\ DATE)?:\ /', $line) === 1 ||
			preg_match('/^CATALOG:\ /', $line) === 1 ||
			preg_match('/^CDR>SHN:\ /', $line) === 1 ||
			preg_match('/^COMPILATION(\ BY)?:\ /', $line) === 1 ||
			preg_match('/^CONFIG(URATION)?:\ /', $line) === 1 ||
			preg_match('/^CONVERSION(\ BY)?:\ /', $line) === 1 ||
			preg_match('/^DAT>SHN:\ /', $line) === 1 ||
			preg_match('/^DITHER(ING)?(\ BY)?:\ /', $line) === 1 ||
			preg_match('/^EDIT(ED|ING)?(\ BY)?:\ /', $line) === 1 ||
			preg_match('/^ENCOD(ED|ING)(\ BY)?:\ /', $line) === 1 ||
			preg_match('/^EQUIPMENT:\ /', $line) === 1 ||
			preg_match('/^EXTRACT(ED|ING|ION)(\ BY)?:\ /', $line) === 1 ||
			preg_match('/^FLAC:\ /', $line) === 1 ||
			preg_match('/^FORMAT:\ /', $line) === 1 ||
			preg_match('/^GENERATION:\ /', $line) === 1 ||
			preg_match('/^LINEAGE:\ /', $line) === 1 ||
			preg_match('/^LOCATION:\ /', $line) === 1 ||
			preg_match('/^MANUFACTURED BY:\ /', $line) === 1 ||
			preg_match('/^MASTER(ED|ING)(\ BY)?:\ /', $line) === 1 ||
			preg_match('/^MICS?:\ /', $line) === 1 ||
			preg_match('/^MIX(ED|ING)?(\ BY)?:\ /', $line) === 1 ||
			preg_match('/^ORIGINAL LABEL:\ /', $line) === 1 ||
			preg_match('/^PATCH(ED)?(\ BY)?:\ /', $line) === 1 ||
			preg_match('/^PROCESS(ED|ING)(\ BY)?:\ /', $line) === 1 ||
			preg_match('/^PRODUC(ED|TION)(\ AND\ MIX(ED|ING)?)?(\ BY)?:\ /', $line) === 1 ||
			preg_match('/^RECORD(ED|ING)(\ AND\ (MIX|MASTER|TRANSFER(R)?)(ED|ING)?)?(\ BY)?:\ /', $line) === 1 ||
			preg_match('/^REFERENCE:\ /', $line) === 1 ||
			preg_match('/^(RE)?TRACK(ED|ING)(\ BY)?:\ /', $line) === 1 ||
			preg_match('/^SEED(ED)?(\ BY)?:\ /', $line) === 1 ||
			preg_match('/^(SOUND\ )?QUALITY:\ /', $line) === 1 ||
			preg_match('/^SOURCES?(\ ?(\()?\d(\))?)?:\ /', $line) === 1 ||
			preg_match('/^TAPE(D|R|)?((\ &\ |\ AND\ |\/)(TRANSFERR?|MASTER)(ED|ER)?)?(\ BY)?:\ /', $line) === 1 ||
			preg_match('/^TAPERS NOTES:\ /', $line) === 1 ||
			preg_match('/^TRANSFER(RED)?(\ BY)?:\ /', $line) === 1 ||
			preg_match('/^UPLOADED BY:\ /', $line) === 1 ||
			preg_match('/^VERSION:\ /', $line) === 1 ||
			preg_match('/^XREF:\ /', $line) === 1
		)
		return SOURCE_LINE;

	//* with someone on trumpet.
	if (strpos("~!@#$%^&*-+=:?", substr($line, 0, 1)) >= 0)
		return FOOTNOTE_LINE;

	//less specific

	//3 discs, 1 disc, etc
	if (preg_match('/^\d\d?\ discs?$/', $line) === 1) 
		return TOTAL_DISCS_LINE;

	//MM-dd-yy date format
	if (preg_match('/^\d\d\-\d\d\-\d\d$/', $line) === 1)
		return DATE_LINE;

	//[January 1, 1980] or [January, 1980]
	if (preg_match('/^(January|February|March|April|May|June|July|August|September|October|November|December)(\ \d\d?)?,\ \d{4}$/', $line) === 1)
		return DATE_LINE;

	//[1. song1] or [100. song100]
	if (preg_match('/^[1-9]\d?\d?\.\ $/', $line) === 1)
		return SONG_LINE;

	if (preg_match('/^(set\ \d|(ph|f)iller|soundcheck|encores?(\ \d)?):$/', $line) === 1)
		return SET_LINE;

	if (preg_match('/^(D?AUD|D?SBD|MATRIX|FM|STUDIO|VCD|SHN)/', $line) === 1)
		return DAUD_SBD_LINE;

	if (preg_match('/^(N|n)(OTE|ote)(S|s)?: /', $line) === 1)
		return NOTE_LINE;

	//if all else fails...
	//echo "returning as NORMAL_LINE: ".$line;
	return NORMAL_LINE;
}

function getLine($handle)
{
	$buffer = fgets($handle);
	if ($buffer === false)
		throw new Exception("EOF");
	else
		return trim($buffer);
}

ob_end_flush();
?>