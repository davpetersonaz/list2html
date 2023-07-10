<?php

//date/time formats
//milliseconds v=001
//seconds s=01
//minutes i=02
//hours G=3 H=03 (24-hour)
//day l=Monday D=Mon d=07 j=7
//month m=08 n=8 M=Aug F=August
//year Y=2004 y=04

class Show{
	private $notTradeable = false;
	private $showLines = array();
	private $finished = 0;

	// in the order they will appear in the html file...
	private $artist = false;//the Artist name, shows will be added to Artist objects, so we want to avoid cyclical issues
	private $showDate = false;
	private $altDate = false;//this is unusual dates like "November 18-19", or "April 1976".
	private $altDateStr = false;//original string representation of altDate
	private $datePostfix = false;//the postfix is whatever follows the date, like "late", "dvd", "a", "sbd"
	private $title = false;
	private $venue = array();
	private $discs = false;
	private $recordingType = false;
	private $shnBook = false;
	private $shnPage = false;
	private $rating = false;
	private $myInfo = array();
	private $setDetails = array();
	private $extraInfo = array();
	private $showLinesIndex = -1;

	function __construct($showLines){
//		logDebug('new Show');
		$this->showLines = $showLines;
		
		//should automatically be on the first line of the show, a blank line, but just in case...
		$line = $this->getNextLine();
		while(LineTypes::identifyLine($line, false) === LineTypes::BLANK_LINE){
			$line = $this->getNextLine();
		}

		//artist line
//		logDebug('should be artist ['.var_export($line, true).']');
		$this->artist = Artists::getInstance()->getArtistByName($line)->getName();

		//either a date line or a title line
//		logDebug('date line, hopefully');
		$line = $this->getNextLine();
		if(LineTypes::identifyLine($line, false) === LineTypes::DATE_LINE){
//			logDebug('confirmed date-line: '.trim($line));
			$this->showDate = DateTime::createFromFormat('m-d-y', substr($line, 0, 8));
//			logDebug("date after parsing [".var_export($this->getDate(), true).']');
			if($this->getDate() === false){
				$this->altDateStr = $line;//not sure why i originally had this as "substr($line, 0, 8)" ??
				$this->showDate = date_create($this->altDateStr);//try to make it into a DateTime object
//				logDebug("altDate [".var_export($this->getDate(), true).']');
				if($this->showDate === false){
					logDebug('ERROR: unknown altDateStr format: '.var_export($this->altDateStr, true));
					//we'll have to return false for getDate(), and return altDateStr for getDateStr()
				}
				$this->datePostfix = "";
			}elseif(strlen($line) > 8){//this is the optional suffix on show dates
				$this->datePostfix = substr($line, 8);
//				logDebug("datePostfix [".var_export($this->getDatePostfix(), true).']');
			}
			$this->title = "";
		}else{//must be a title line then..
			$this->datePostfix = "";
			$this->title = $line;
		}
		$line = $this->getNextLine();

		//venue lines
		while(LineTypes::identifyLine($line, false) !== LineTypes::TOTAL_DISCS_LINE
				&& LineTypes::identifyLine($line) !== LineTypes::DATE_LINE
				&& LineTypes::identifyLine($line) !== LineTypes::MY_INFO_LINE
				&& LineTypes::identifyLine($line) !== LineTypes::RATING_LINE
				&& LineTypes::identifyLine($line) !== LineTypes::DAUD_SBD_LINE){
			$this->venue[] = $line;
			$line = $this->getNextLine();
		}

		//date line - for non-phish-type shows
		if(LineTypes::identifyLine($line, false) === LineTypes::DATE_LINE){
			$this->altDateStr = $line;
			$this->showDate = DateTime::createFromFormat('F j, Y', $line);
//			logDebug("date after parsing [".var_export($this->getDate(), true).']');
			if($this->getDate() == false){
				logDebug('ERROR: unknown date format: '.var_export($line, true));
			}
			$line = $this->getNextLine();
		}

		//total discs line
		if(LineTypes::identifyLine($line, false) === LineTypes::TOTAL_DISCS_LINE){
			$this->discs = trim(substr($line, 0, 2));
			$line = $this->getNextLine();
		}

		//daud-sbd line, including SHN-
		if(LineTypes::identifyLine($line, false) === LineTypes::DAUD_SBD_LINE){
			//separate recordingType & shnInfo
			$firstElement = $secondElement = "";
			$shnIndex = false;
//			logDebug('recordingType line: '.var_export($line, true));
			$split = explode(', ', $line);//force $line to be a string
//			logDebug('source/shn: '.var_export(implode(',', $split), true));
			if(isset($split[0])){ $firstElement = $split[0]; }
			if(isset($split[1])){ $secondElement = $split[1]; }
			if(strpos($firstElement, "SHN") === 0){
				$this->recordingType = "";
				$shnIndex = $firstElement;
			}else{
				$this->recordingType = $firstElement;
				if($secondElement){ $shnIndex = $secondElement; }
			}
//			logDebug('recordingType: '.var_export($this->recordingType, true));
			//create shn info
			if(!empty($shnIndex)){
				$split = explode('-', $shnIndex);//force $line to be a string
				if(isset($split[1])){
					$this->shnBook = $split[1];
				}
				if(isset($split[2])){
					$this->shnPage = $split[2];
				}
//				logDebug("shnbook [{$this->shnBook}] shnpage [{$this->shnPage}]");
			}
			$line = $this->getNextLine();
		}

		//should be a rating line, check to make sure
		if(str_contains('ABC', substr($line, 0, 1))){
			//just get the rating
			$this->rating = trim(substr($line, 0, 2));
			$line = $this->getNextLine();
		}

		//maybe a NOT TRADEABLE line
		if(LineTypes::identifyLine($line, false) === LineTypes::NOT_TRADEABLE_LINE){
			$this->notTradeable = true;
		}

		//my info lines
		while(LineTypes::identifyLine($line, false) === LineTypes::MY_INFO_LINE){
			$this->myInfo[] = "<!-- ".substr($line, 2)." -->";
			$line = $this->getNextLine();
		}

		//blank line between myInfo and setDetails
		if(LineTypes::identifyLine($line, false) === LineTypes::BLANK_LINE){
			$line = $this->getNextLine();
		}

		//song list lines:

		//bold the disc/set/encore/filler line
		while(LineTypes::identifyLine($line, false) === LineTypes::DISC_LINE
				|| LineTypes::identifyLine($line) === LineTypes::SET_LINE 
				|| LineTypes::identifyLine($line) === LineTypes::SONG_LINE 
				|| LineTypes::identifyLine($line) === LineTypes::BLANK_LINE){
			if(LineTypes::identifyLine($line) === LineTypes::SET_LINE){
				$line = "<B>".$line."</B>";
			}else
			if(LineTypes::identifyLine($line) === LineTypes::SONG_LINE){
				$artist = $this->getArtist();
				if(strpos($line, ">")){
					//separate songs grouped on a single track
					$split = explode('>', $line);
					foreach($split as $possibleSong){
						$artist->addSong($possibleSong);
						//TODO: do i need to log anything if it is "not a storable song" ??
					}
				}else{//line only contains one song on it.
					$artist->addSong($line);
					//TODO: do i need to log anything if it is not a storable song" ["+line+"]: "+e.getMessage());" ???
				}
			}
			$this->setDetails[] = $line;	//all songs in a track must be on the same line!!
			$line = $this->getNextLine();
			if($line === false){ break; }
		}

		//extra info lines
		while($line !== false){
//			logDebug('extra info line');
			
			//if its the "comment rest of show" characters (/**), comment everything else in this show
			if(LineTypes::identifyLine($line, false) === LineTypes::COMMENT_REST_LINE){
//				logDebug('comment rest line');
				$this->extraInfo[] = "<!-- ".$line." -->";
				while(($line = $this->getNextLine()) !== false){
					$this->extraInfo[] = "<!-- ".$line." -->";
				}
				continue;
			}

			//http link lines
			if(LineTypes::identifyLine($line) === LineTypes::HTTP_LINE){
//				logDebug('http line');
				$this->extraInfo[] = "<BR /><a href='{$line}' target=new>{$line}</a>";
				$line = $this->getNextLine();
				continue;
			}

			//set detail footnotes (*^#)
			if(LineTypes::identifyLine($line) === LineTypes::FOOTNOTE_LINE){
//				logDebug('footnote line');
				while(LineTypes::identifyLine($nextline = $this->getNextLine(), false) === LineTypes::NORMAL_LINE){
					$line .= " ".$nextline;
				}
				$this->extraInfo[] = '<BR />'.$line;
				$line = $nextline;
				continue;
			}

			//phish companion info
			if(LineTypes::identifyLine($line) === LineTypes::PH_COMP_LINE){//phish companion info added
//				logDebug('pc line?');
				$firstTime = true;
				while(LineTypes::identifyLine($nextline = $this->getNextLine(), false) === LineTypes::NORMAL_LINE){
					$line .= " ".$nextline;
				}
				if(substr($line, 0, 7) !== "pc: nil"){//some shows notes just aren't that great (TODO: not sure what this means ??  "nil" ???)
					$line = "<B>Phish Companion says:</b> ".trim(substr($line, 3));//replace "pc:"
					$this->extraInfo[] = ($firstTime ? '<BR />' : '').$line;
					$firstTime = false;
				}
				$line = $nextline;
				continue;
			}

			//band member info lines
			if(LineTypes::identifyLine($line) === LineTypes::THE_BAND_LINE){
//				logDebug('band line');
//				if(substr($line, 0, 2) === "//"){ //TODO: should probably investigate this, its probably not right.
//					//"The Band: text could be commented out, but band info still displayed (one for woody)  TODO: not sure what this means ??
//					$this->extraInfo[] = ($firstTime ? '<BR />' : '').$line;
//					$firstTime = false;
//				}
				$this->extraInfo[] = $line;
				while(LineTypes::identifyLine($line = $this->getNextLine(), false) === LineTypes::NORMAL_LINE){
					$this->extraInfo[] = $line;
				}
//				$this->extraInfo[] = '';//to produce a line break
				continue;
			}

			if(LineTypes::identifyLine($line) === LineTypes::NOTE_LINE 
					|| LineTypes::identifyLine($line) === LineTypes::SOURCE_LINE){
//				logDebug('note line(2)');
				while(LineTypes::identifyLine($nextline = $this->getNextLine(), false) === LineTypes::NORMAL_LINE){
					$line .= " ".$nextline;
				}
				$this->extraInfo[] = $line;
				$line = $nextline;
				continue;
			}

			if (LineTypes::identifyLine($line) === LineTypes::NORMAL_LINE){
//				logDebug('normal line');
				while(LineTypes::identifyLine($nextline = $this->getNextLine(), false) === LineTypes::NORMAL_LINE){
					$line .= " ".$nextline;
				}
				$this->extraInfo[] = '<BR />'.$line;
				$line = $nextline;
				continue;
			}

			$this->extraInfo[] = $line;
//			logDebug('loop bottom, get next line');
			$line = $this->getNextLine();
		}

		/*************************************
		 * add links to setlist websites
		 *************************************/
		
		$setlistLinks = $this->getOnlineSetlistLinks($this->getArtistName());
		if($setlistLinks){
			if(isset($setlistLinks['label'])){
				$this->extraInfo[] = "<a href='{$setlistLinks['url']}{$setlistLinks['page']}'>{$setlistLinks['label']}</a>";
			}else{
				foreach($setlistLinks as $setlistLink){
					$this->extraInfo[] = "<a href='{$setlistLink['url']}{$setlistLink['page']}'>{$setlistLink['label']}</a>";
				}
			}
		}

		logDebug('Show complete: '.$this->getArtistName().' '.$this->getDateString('Ymd'));
	}

	private function getNextLine(){
		if($this->showLinesIndex > 300){ exit; }
		$this->showLinesIndex++;
		$output = false;
		if(isset($this->showLines[$this->showLinesIndex])){
			$output = trim($this->showLines[$this->showLinesIndex]);
		}
//		logDebug("Show::getNextLine returning [".($this->showLinesIndex)."]: ".var_export($output, true));
		return $output;
	}
	
	private function getShowLines(){
		return $this->showLines;
	}

	public function isPhishType(){
		return (empty($this->getTitle()));
	}

	public function isTradeable(){
		return (!($this->notTradeable));
	}

	public function getArtist(){
		return Artists::getInstance()->getArtistByName($this->artist);
	}
	
	public function getArtistName(){
		return $this->artist;
	}
	
	/*
	$today = date("F j, Y, g:i a");                 // March 10, 2001, 5:16 pm
	$today = date("m.d.y");                         // 03.10.01
	$today = date("j, n, Y");                       // 10, 3, 2001
	$today = date("Ymd");                           // 20010310
	$today = date('h-i-s, j-m-y, it is w Day');     // 05-16-18, 10-03-01, 1631 1618 6 Satpm01
	$today = date('\i\t \i\s \t\h\e jS \d\a\y.');   // it is the 10th day.
	$today = date("D M j G:i:s T Y");               // Sat Mar 10 17:16:18 MST 2001
	$today = date('H:m:s \m \i\s\ \m\o\n\t\h');     // 17:03:18 m is month
	$today = date("H:i:s");                         // 17:16:18
	$today = date("Y-m-d H:i:s");                   // 2001-03-10 17:16:18 (the MySQL DATETIME format)
	*/

	public function getDate(){
		return $this->showDate;
	}

	public function getDateString($format='Ymd'){
		$rc = false;
		$showDate = $this->getDate();
		if($showDate !== false){
			$rc = $showDate->format($format);
		}else{
			$rc = $this->getAltDateStr();
		}
//		logDebug("getDateString({$format}) returning [".var_export($rc, true).']');
		return $rc;
	}

	private function getAltDateStr(){
		return $this->altDateStr;
	}

	public function getYear(){
		$rc = "";
		if($this->getDate()){
			$rc = $this->getDate()->format('Y');
		}else{
			$altDateStr = $this->getAltDateStr();
			if(preg_match('/.*(\d{4}).*/', $altDateStr, $matches) > 0){
				$rc = $matches[1];
			}
		}
		return $rc;
	}

	private function getDatePostfix(){
		return $this->datePostfix;
	}

	public function getTitle(){
		return $this->title;
	}

	public function getVenueLines(){
		return $this->venue;
	}

	private function getDiscs(){
		return $this->discs;
	}

	public function getRecordingType(){
		return $this->recordingType;
	}

	private function getShnBook(){
		return $this->shnBook;
	}

	private function getShnPage(){
		return $this->shnPage;
	}

	public function getRating(){
		return $this->rating;
	}

	private function getMyInfoLines(){
		return $this->myInfo;
	}

	private function getSetDetailLines(){
		return $this->setDetails;
	}

	private function getExtraInfoLines(){
		return $this->extraInfo;
	}

	private function getOnlineSetlistLinks($name){
		$year = ($this->getYear() ? $this->getYear() : '');
		$date = $this->getDate();
		$bandSetlists = array(
			"Allman Brothers Band" => array(
				"example" => "https://allmanbrothersband.com/event/abb19690619/",
				"url" => ($date ? "https://allmanbrothersband.com/event/abb".$date->format('Ymd')."/" : "https://allmanbrothersband.com/set-lists/"),
				"page" => "abbase.html",
				"label" => "view setlists on ABBase"
			),
			"Claypool Lennon Delirium" => array(
				"example" => "https://toasterland.com/setlists/browse.php?year=2004&band=11",
				"url" => "https://toasterland.com/setlists/browse.php".($year ? "?year={$year}&band=11" : ''),
				"page" => "",
				"label" => "this show on Toasterland's setlists"
			),
			"Disco Biscuits" => array(
				"example" => "https://discobiscuits.net/shows/year/2007",
				"url" => ($year ? "https://discobiscuits.net/shows/year/{$year}" : "https://discobiscuits.net/setlists.php"),
				"page" => "",
				"label" => "view the setlist on the Biscuits Internet Project 2.0"
			),
			"Duo de Twang" => array(
				"example" => "https://toasterland.com/setlists/browse.php?year=2004&band=10",
				"url" => "https://toasterland.com/setlists/browse.php".($year ? "?year={$year}&band=10" : ''),
				"page" => "",
				"label" => "this show on Toasterland's setlists"
			),
			"Electric Apricot" => array(
				"example" => "https://toasterland.com/setlists/browse.php?year=2004&band=7",
				"url" => "https://toasterland.com/setlists/browse.php".($year ? "?year={$year}&band=7" : ''),
				"page" => "",
				"label" => "this show on Toasterland's setlists"
			),
			"Govt Mule" => array(
				"example" => "http://themule.atspace.com/1999.html#August",
				"url" => "http://themule.atspace.com/".($year ? $year.".html" : '').($date ? "#".$date->format('F') : ''),
				"page" => "",
				"label" => "view the setlists on TheMule"
			),
			"Grateful Dead" => array(
				0 => array(	
					"example" => "https://gdsets.com/gd.htm#1969",
					"url" => "https://gdsets.com/gd.htm".($year ? '#'.$year : ''),
					"page" => "",
					"label" => "this show on GD Sets</a></p>"
				),
				1 => array(
					"example" => "",
					"url" => "http://deadlists.com/",
					"page" => "",
					"label" => "view the setlists on deadlists"
				),
				2 => array(	
					"example" => "https://www.cs.cmu.edu/~mleone/gdead/dead-sets/88/4-13-88.txt",
					"url" => ($date ? "https://www.cs.cmu.edu/~mleone/gdead/dead-sets/".$date->format('y').'/'.$date->format('n-j-y').".txt" : "https://www.cs.cmu.edu/~mleone/gdead/setlists.html"),
					"page" => '',
					"label" => "view the setlists on Grateful Dead Setlists</a></p>"
				)
			),
			"Holy Mackerel" => array(
				"example" => "https://toasterland.com/setlists/browse.php?year=2004&band=3",
				"url" => "https://toasterland.com/setlists/browse.php".($year ? "?year={$year}&band=3" : ''),
				"page" => "",
				"label" => "this show on Toasterland's setlists"
			),
			"Led Zeppelin" => array(
				0 => array(
					"example" => "https://www.ledzeppelin.com/timeline/1970",
					"url" => ($year ? "https://www.ledzeppelin.com/timeline/{$year}" : "https://www.ledzeppelin.com/timelinebrowse"),
					"page" => "",
					"label" => "this show on LedZeppelin.com"
				),
				1 => array(
					"example" => "https://www.argenteumastrum.com/1977.htm",
					"url" => ($year ? "https://www.argenteumastrum.com/{$year}.htm" : "http://www.argenteumastrum.com/tour_dates.htm"),
					"page" => "",
					"label" => "this show on the Led Zeppelin Database"
				)
			),
			"Les Claypool's Fancy Band" => array(
				"example" => "https://toasterland.com/setlists/browse.php?year=2004&band=8",
				"url" => "https://toasterland.com/setlists/browse.php".($year ? "?year={$year}&band=8" : ''),
				"page" => "",
				"label" => "this show on Toasterland's setlists"
			),
			"Les Claypool's Fearless Flying Frog Brigade" => array(
				"example" => "https://toasterland.com/setlists/browse.php?year=2004&band=1",
				"url" => "https://toasterland.com/setlists/browse.php".($year ? "?year={$year}&band=1" : ''),
				"page" => "",
				"label" => "this show on Toasterland's setlists"
			),
			"Medeski Martin and Wood" => array(
				"example" => "http://mmwhistory.com/2011.html#4/29/2011",
				"url" => "http://mmwhistory.com/".($year ? $year.".html" : '').($date ? "#".$date->format('n/j/Y') : ''),
				"page" => "",
				"label" => "this show on MMW History"
			),
			"moe." => array(
				"example" => "",
				"url" => "http://moelinks.com/",
				"page" => "",
				"label" => "view moe.'s That Setlist File"
			),
			"Oysterhead" => array(
				"example" => "https://toasterland.com/setlists/browse.php?year=2004&band=4",
				"url" => "https://toasterland.com/setlists/browse.php".($year ? "?year={$year}&band=4" : ''),
				"page" => "",
				"label" => "this show on Toasterland's setlists"
			),
			"Phish" => array(
				"example" => "",
				"url" => "https://phish.net/setlists",
				"page" => "",
				"label" => "view setlists at phish.net - the helping friendly book</a></p>"
			),
			"Primus" => array(
				"example" => "https://toasterland.com/setlists/browse.php?year=2004&band=0",
				"url" => "https://toasterland.com/setlists/browse.php".($year ? "?year={$year}&band=0" : ''),
				"page" => "",
				"label" => "this show on Toasterland's setlists"
			),
			"Sausage" => array(
				"example" => "https://toasterland.com/setlists/browse.php?year=2019&band=2",
				"url" => "https://toasterland.com/setlists/browse.php".($year ? "?year={$year}&band=2" : ''),
				"page" => "",
				"label" => "this show on Toasterland's setlists"
			),
			"String Cheese Incident" => array(
				"example" => "https://friendsofcheese.com/incidents.php?cat=year&year=2022",
				"url" => "https://friendsofcheese.com/incidents.php".($year ? "?cat=year&year={$year}" : ''),
				"page" => "",
				"label" => "view the setlist on FriendsOfCheese.com"
			),
			"Umphrey's McGee" => array(
				"example" => "",
				"url" => "https://allthings.umphreys.com/setlists/",
				"page" => "",
				"label" => "view the setlist on Umphreys.com</a></p>"
			)
		);
		
		return (isset($bandSetlists[$name]) ? $bandSetlists[$name] : '');
	}
	
	private function getLocalOutputFileName(){
		return OUTPUT_SHOWS_DIR.$this->getOutputFileName();
			//i think this was meant to split up the show files across 2 directories
//		if(toUppercase(substr($this->getArtist()->getName(), 0, 1)) === "M"){
//			return CDLIST_DIR1.$this->getOutputFileName();
//		}else{
//			return CDLIST_DIR2.$this->getOutputFileName();
//		}
	}

	public function getOutputFileName(){
		$rc = false;
		$artist = $this->getArtist();
		if($this->isPhishType()){
			$rc = $artist->getStrippedName()."-".$this->getDateString("Ymd").$this->getDatePostfix().".html";
		}else{//dateAlpha
			//floyd, zep, tool, stevie, nin, etc
			$rc = $artist->getStrippedName()."-".$artist->getStrippedName($this->getTitle()).".html";
		}
		return $rc;
	}

	public function outputShowFile($show){
		$output = array();

		//start the show's html doc
		$output[] = "<html>";
		$output[] = "<font face=Arial>";
		$output[] = LINK_COLORS;
		$artistName = $this->getArtistName();

		//put the title on the SHOW'S html doc
		$output[] = "<head><title>";
		if($this->isPhishType()){
			$output[] = $artistName." - ".$this->getDateString("m-d-Y");
		}else{
			$output[] = $artistName." - ".$this->getTitle();
		}
		$output[] = "</title></head>";
		$output[] = "<body>";

		//start the body with Header1 - artist
		$output[] = "<h1>".$artistName."</h1>";

		//Header2 - title, date, venue
		if($this->isPhishType()){
			$output[] = "<h2>";
			$output[] = $show->getDateString("m-d-Y").BR;
			foreach($this->getVenueLines() as $venueLine){
				$output[] = $venueLine.BR;
			}
			$output[] = "</h2>";
		}else{
			$output[] = "<h1>".$this->getTitle()."</h1>";
			$output[] = "<h2>";
			foreach($this->getVenueLines() as $venueLine){
				$output[] = $venueLine.BR;
			}
			if($this->getDateString("m-d-Y")){
				$output[] = $show->getDateString("F j, Y").BR;
			}
			$output[] = "</h2>";
		}

		//Header3 - discs, recording type, rating, my info lines
		$output[] = "<h3>";
		if (!empty($this->getDiscs())){
			$output[] = "discs: ".$this->getDiscs().BR;
		}
		$output[] = "recording type: ".$this->getRecordingType().BR;
		if(!$this->getShnBook()){
			$output[] = "shn: not yet".BR;
		}else{	//shn info
			$output[] = "shn: yes; book/page: ".$this->getShnBook()."/".$this->getShnPage().BR;
		}
		$output[] = "quality rating: ".$this->getRating().BR;
		foreach($this->getMyInfoLines() as $temp){
			$output[] = $temp;
		}
		$output[] = "</h3>";

		//song list - set details - write set detail line to the show's html file
		foreach($this->getSetDetailLines() as $templine){
			$output[] = $templine.BR;
		}

		//extra info
		foreach($this->getExtraInfoLines() as $extraInfo){
			if(substr($extraInfo, 0, 4) === "<!--"){
				$output[] = $extraInfo;
			}else{
				$output[] = $extraInfo.BR;
			}
		}

		//finish off the show's html file
		$output[] = "<p><a href=index.html>back to the cd list</a></p>";
		$output[] = "</body>";
		$output[] = "</font>";
		$output[] = "</html>";
		
		//save it
		file_put_contents($this->getLocalOutputFileName(), $output);
		
		$this->finished++;
		logDebug("done creating Show: ".$this->getOutputFileName());
	}

}