<?php
class Showlist{
	
	private $header = array();
	private $body = array();

	private $missingLogos = array();//collects all the missing artist logo's
	private $phishStats = array();//an array of dates (of phish shows)
	private $handle = false;
	
	private $started = 0;
	private $finished = 0;
	private $notTradeable = 0;
	private $iteration = 0;
	private $stop_after_iteration = 9999999;
	
	public function processInputFile(){
		logDebug('processInputFile');
		$previousArtist = false;
		$currentShow = false;
		
		$this->clearOutputDirs();
		$this->startOutputHtml();
			
		//process the input file
		$this->handle = fopen(SHOWS_INPUT_FILE, "r");
		if($this->handle){
			while(($line = $this->getLineFromHandle()) !== false){
				logDebug('processing line: '.var_export($line, true));

				//find the first show ... indicated by the first dashed line
				if(LineTypes::identifyLine($line, false) !== LineTypes::DASHED_LINE){
					continue;
				}

				//the next line is blank, so continue on to the meat...

				/*
				 * loops for every show:
				 *	every tradeable show gets its own html file, 
				 *	a link is added to 2 index files, 
				 *	all songs are added to a listing of all songs performed by that artist, 
				 *	and also, phish shows are added to the phishstats file - for use with the phishstats website
				 */

				while($line !== false){ //breaks at 2 consecutive DASHED_LINEs
//					logDebug("loops for every show, reading the next show...");
					
					//read the next show from the inputfile, that way we aren't passing around the entire InputFile
					$firstTimeThru = true;
					$theNextShow = array();
					while(LineTypes::identifyLine($line = $this->getLineFromHandle(), false) !== LineTypes::DASHED_LINE){
						$firstTimeThru = false;
						if($line === false){ logDebug('EOF-false, break-2'); break 3; }//i don't think this ever triggers
						$theNextShow[] = $line;
					}
					if(LineTypes::identifyLine($line) === LineTypes::DASHED_LINE && $firstTimeThru){ logDebug('EOF-double-dashed, break'); break 2; }

//					logDebug('theNextShow: '.var_export($theNextShow, true));
					$currentShow = new Show($theNextShow);
					Artists::getInstance()->addShowToArtist($currentShow); 

					$this->started++;

					//skip past the untradeable shows, don't even make html pages for them.
					if(!$currentShow->isTradeable()){
						$this->notTradeable++;
						continue;
					}

					//log the show that is processing
					if($currentShow->isPhishType()){
						logDebug("processing ".$currentShow->getArtistName()." - ".$currentShow->getDateString("m-d-Y")." ... ");
					}else{
						logDebug("processing ".$currentShow->getArtistName()." - ".$currentShow->getTitle()." ... ");
					}

					//create the output html file for each show
					$currentShow->outputShowFile($currentShow);
					if($currentShow->getArtistName() === 'Phish'){
						$this->addToPhishStats[] = $currentShow->getDateString('m/d/Y');//these should be added in a chronological manner, since the resulting array would not be sortable given m/d/Y
					}

					//add the previousArtist's logo and internal anchor, and links to all that artist's shows.
					if(!$previousArtist || $currentShow->getArtistName() !== $previousArtist->getName()){
						if($previousArtist){//it'll be 'false' on the first loop
							$this->addPreviousArtistsShows($previousArtist);
						}
						$previousArtist = $currentShow->getArtist();
					}

					//pause so we don't overwhelm the machine
					usleep(50000); // 1/25th second, i think
					$this->finished++;
					if($this->stop_after_iteration <= ++$this->iteration){ logDebug('stop_after_iteration'); break 2; }
				}
			}
			$this->addPreviousArtistsShows($previousArtist);//process the last artist in the list
			fclose($this->handle);
		}
		
		$this->finishOffOutputFiles();
	}
	
	private function getLineFromHandle(){
		return fgets($this->handle);
	}
	
	private function clearOutputDirs(){
		exec('find '.OUTPUT_DIR.' -type f -name "*" -delete');
	}
	
	/**
	 * create the header
	 */
	private function startOutputHtml(){
		//start the header of the index file
		$this->addToHeader("<html><font face=Arial><title>Dave's List of Shows</title>".LINK_COLORS);
		$this->addToHeader("<head><center><h1>Dave's CD List</h1><p><B>NOTE</b>: <B>primuslive</B> and <b>livephish</B> shows are not for trade,".BR);
		$this->addToHeader("trading is limited to public domain / uncopyrighted material only.".BR);
		$this->addToHeader("<p><a href='http://userpages.umbc.edu/~hamilton/btclientconfig.html' target='_blank'>How BitTorrent works</a>".BR);
		$this->addToHeader("<a href='".STATIC_PAGES_URL."links.html'>Some good links (burning, etc)</a>".BR);
		$this->addToHeader("<a href='".STATIC_PAGES_URL."CDRMediaInfo.html' target='_blank'>information on CD-R Media / etree & trading standards</a></p>");
		$this->addToHeader("<p><a href='https://sites.lafayette.edu/fams240-sp14-graye/2014/02/24/what-is-a-jam-band/' target='_blank'>what's a jam band?</a>".BR);
		$this->addToHeader("<a href='".STATIC_PAGES_URL."ShowsIWant.html'>shows i'm looking for -- help me out if you can!</a>".BR);
		$this->addToHeader("<a href='".STATIC_PAGES_URL."LegendaryPhishShows.html' target='_blank'>legendary phish shows</a></p>");
		$this->addToHeader("<p>last updated: ".(date('F j, Y', time())).BR."</head>");
	}

	/**
	 * in the indexFile, add internalAnchors and the artist header line,
	 * and all the shows for this particular artist
	 *
	 * @param artist the artist all the shows are of.
	 */
	private function addPreviousArtistsShows($artist){
		
		//put internal anchor & artist header line in index doc
		$artistHeader = BR."<a name=".$artist->getInternalAnchor()."></a>".BR.BR.BR.$this->getArtistHeader($artist);
		$this->addToBody($artistHeader);

		//if new year in artist chronology, add blank line or year
		$yearSpacer = (count($artist->getShows()) > 25 || count($artist->getCareerYears()) > 7);
		$previousYr = "";
		foreach($artist->getShows() as $show){
			if($show->getYear() !== $previousYr){
				if($yearSpacer){
					$this->addToBody("<font size=-1>".BR."<b>".$show->getYear()."</b>".BR.BR."</font>");
				}else{
					$this->addToBody("<font size=-1>".BR."</font>");
				}
				$previousYr = $show->getYear();
			}

			//add link to the show's html page in the index html
			if($show->isPhishType()){
				$venueLines = $show->getVenueLines();
				$venue = reset($venueLines);
				$this->addToBody(
					"<a href='shows/".$show->getOutputFileName()."'>"
						.(empty($show->getDateString("m-d-Y")) ? "" : "<b>".$show->getDateString("m-d-Y")."</b> - ")
						.substr($venue, 0, 34)
						/* ." - ".$show->getDiscs()." cds" */
						.(empty($show->getRecordingType()) ? "" : " - ".$show->getRecordingType())
						.(empty($show->getRating()) ? "" : " - ".$show->getRating())
						."</a>".BR
				);
			}else{
				$this->addToBody(
					"<a href='shows/".$show->getOutputFileName()."'>"
						."<b>".$show->getTitle()."</b>"
						.(empty($show->getDateString("m-d-y")) ? "" : " - ".$show->getDateString("m-d-Y"))
						/* ." - ".$show->getDiscs()." cds" */
						.(empty($show->getRecordingType()) ? "" : " - ".$show->getRecordingType())
						.(empty($show->getRating()) ? "" : " - ".$show->getRating())
						."</a>".BR
				);
			}
		}
	}

	private function getArtistHeader($artist){
		$logoFileName = addslashes($artist->getStrippedName()."Logo");
        if(file_exists(LOGOS_DIR) && is_dir(LOGOS_DIR)){
            $picsDirList = scandir(LOGOS_DIR);
            if(count($picsDirList) > 0){
				$logoMatches = preg_grep("/^{$logoFileName}\..*$/", $picsDirList);
//				logDebug('logoMatches: '.var_export($logoMatches, true));
				if($logoMatches){
//					$image = new Imagick($logoMatches[0]);
                    return "<h2>"
                            ."<img src=".PICS_URL.reset($logoMatches)." "
                            ."border=0 "
//							."width=".$image->getImageWidth()." "
//							."ehight=".$image->getImageHeight()." "
                            ."alt='".$artist->getName()."'>"
                            ."</h2>";
                }
			}
        }
		logDebug("missing logo [{$logoFileName}]");
        $this->missingLogos[] = $logoFileName;
        return "<h2>".$artist->getName()."</h2>";
    }

	private function finishOffOutputFiles(){
		logDebug('finishOffOutputFiles');
		
		//finish off the INDEX file
		$this->addToBody(BR.BR."</body></font></html>");

		//create internal anchor LINKS (quick access links)
		$column1 = $column2 = array();
		$artists = Artists::getInstance()->getArtists();
		$sortedArtists = array();
		foreach($artists as $artist){
			$sortedArtists[$artist->getSortingName()] = $artist;
		}
		ksort($sortedArtists, SORT_STRING | SORT_FLAG_CASE);
		
		//first element is the html for the quicklink
		//second element is the ongoing "rank" count, so the middle can be found
		$anchorLinksRankings = array();
		$tiers = [ 1, 3, 7, 12, 19, 34, 49 ];
		$logRankings = array_fill(0, count($tiers)+1, 0);
		$rankingsTotal = 0;
		foreach($sortedArtists as $artist){

			//resize the quickLink font based on number of shows by artist
			$fontResizing = 0;
			$numShows = count($artist->getShows());
			if($numShows > $tiers[6]){
				$fontResizing = 230;
				$logRankings[7]++;
			}elseif($numShows > $tiers[5]){
				$fontResizing = 210;
				$logRankings[6]++;
			}elseif($numShows > $tiers[4]){
				$fontResizing = 190;
				$logRankings[5]++;
			}elseif($numShows > $tiers[3]){
				$fontResizing = 165;
				$logRankings[4]++;
			}elseif($numShows > $tiers[2]){
				$fontResizing = 140;
				$logRankings[3]++;
			}elseif($numShows > $tiers[1]){
				$fontResizing = 115;
				$logRankings[2]++;
			}elseif($numShows > $tiers[0]){
				$fontResizing = 90;
				$logRankings[1]++;
			}else{
				$fontResizing = 70;
				$logRankings[0]++;
			}
			$rankingsTotal += $fontResizing;

			$quickLink = "<center>"
							."<a href='#".$artist->getInternalAnchor()."' style='text-align:center;text-decoration:none;font-family:arial;font-size:{$fontResizing}%'>"
								.$artist->getName()
							."</a>"
						."</center>";

			logDebug("[{$numShows}] shows for [".$artist->getName()."]");
			$anchorLinksRankings[] = new ArtistRankings($rankingsTotal, $quickLink);
		}

		logDebug("num of artists with shows in the ".count($tiers)." tiers:");
		logDebug( $tiers[6]+1 .">"		    ." ... ".$logRankings[7]);
		logDebug(($tiers[5]+1)."-".$tiers[6]." ... ".$logRankings[6]);
		logDebug(($tiers[4]+1)."-".$tiers[5]." ... ".$logRankings[5]);
		logDebug(($tiers[3]+1)."-".$tiers[4]." ... ".$logRankings[4]);
		logDebug(($tiers[2]+1)."-".$tiers[3]." ... ".$logRankings[3]);
		logDebug(($tiers[1]+1)."-".$tiers[2]." ... ".$logRankings[2]);
		logDebug(($tiers[0]+1)."-".$tiers[1]." ... ".$logRankings[1]);
		logDebug(              "<".$tiers[0]." ... ".$logRankings[0]);

		foreach($anchorLinksRankings as $rankings){
			if ($rankings->getRankingsSoFar() < $rankingsTotal / 2){
				$column1[] = $rankings->getQuickLink();
			}else{
				$column2[] = $rankings->getQuickLink();
			}
		}

		$quickAccessLinkLines = "<br><h2>Quick Access Links</h2><table align=center><tr><td>";
		foreach($column1 as $link){
			$quickAccessLinkLines .= $link;
		}
		$quickAccessLinkLines .= "</td><td>";
		foreach($column2 as $link){
			$quickAccessLinkLines .= $link;
		}
		$quickAccessLinkLines .= "</td></tr></table>";

		//build the output file
//		logDebug('this->body slice: '.var_export(array_slice($this->body, 0, 3), true));
		$output = array_merge($this->header, array($quickAccessLinkLines), $this->body);

		//now populate the INDEX file
		file_put_contents(OUTPUT_DIR.SHOWS_INDEX_HTML, $output);

		//create and populate the phish STATS file
		logDebug("create phish-stats file");
		$this->createPhishStatsFile();

		//create and populate the song list files
		logDebug("create songsby files");
		$this->createSongListFiles();

		//create and populate the artist-names file
		logDebug("create artist-names files");
		$this->createArtistNamesFiles();

		//create and populate the missing-logos file
		logDebug("create missing-logos file");
		$this->createMissingLogosFile();

		logDebug("started [{$this->started}] finished [{$this->finished}] not tradeable [{$this->notTradeable}]");
		return;
	}

	private function addToBody($line){
//		logDebug('add line to index file: '.var_export($line, true));
		$this->body[] = $line;
	}
	
	private function addToHeader($line){
//		logDebug('add line to header file: '.var_export($line, true));
		$this->header[] = $line;
	}
	
	private function addToPhishStats($line){
//		logDebug('add line to phishstats file: '.var_export($line, true));
		$this->phishStats[] = $line;
	}
	
	private function createPhishStatsFile(){
		$output = array();
		//create first line of personal stats html page
		$output[] = ("<html><body>");
		//add all the dates
		foreach($this->phishStats as $statDate){
			$output[] = $statDate->format('m/d/Y').BR;
		}
		//finish off the STATS file
		$output[] = "</body></html>";
		file_put_contents(OUTPUT_DIR.PHISH_STATS_HTML, $output);
	}

	public function createSongListFiles(){
		foreach(Artists::getInstance()->getArtists() as $artist){
			$output = array();
			if($artist->getSongsBy()){
				logDebug("creating SongsBy file for: ".$artist->getName());
				foreach($artist->getSongsBy() as $title=>$ocurrences){
					$output[] = "{$title} : {$ocurrences}".PHP_EOL;
				}
				$filename = OUTPUT_SONGSBY_DIR."SongsBy".$artist->getStrippedName().".txt";
//				logDebug('writing: '.$filename);
				file_put_contents($filename, $output);
			}
		}
	}

	private function createArtistNamesFiles(){

		//create one file where names are in the order they are in the list
		$output = array();
		foreach(Artists::getInstance()->getArtistNames() as $name){
			$output[] = $name.PHP_EOL;
		}
		file_put_contents(OUTPUT_DIR."ArtistNames.txt", $output);

		//create another file where names are in alphabetical order
		$output = array();
		foreach(Artists::getInstance()->getArtistNamesSorted() as $name){
			$output[] = $name.PHP_EOL;
		}
		file_put_contents(OUTPUT_DIR."ArtistNamesSorted.txt", $output);
	}

	private function createMissingLogosFile(){
		$output = array();
		sort($this->missingLogos);
		foreach($this->missingLogos as $missingLogo){
			$output[] = $missingLogo.PHP_EOL;
		}
		file_put_contents(OUTPUT_DIR."MissingLogos.txt", $output);
	}

}