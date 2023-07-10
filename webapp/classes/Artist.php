<?php
class Artist{
    private $fullname = false;
	private $abridgedName = false;
	private $shows = array();//an array of Shows by this artist

	function __construct($name){
        $this->fullname = $name;
		$this->abridgedName = $this->createSortingName($name);
    }
	
	/* might be useful to outside classes */
	public function createSortingName($name){
		if($name === "Stevie Ray Vaughan & Double Trouble"){
			$name = "Stevie Ray Vaughan";
		}elseif($name === "Bela Fleck & the Flecktones"){
			$name = "Bela Fleck";
		}elseif($name === "Animal Liberation Orchestra"){
			$name = $name." (ALO)";
		}
		
		if(strlen($name) > 4 && "the " === strtolower(substr($name, 0, 4))){
			$name = substr($name, 4).", The";
		}elseif(strlen($name) > 3 && "an " === substr($name, 0, 3)){
            $name = substr($name, 3).", An";
		}elseif(strlen($name) > 2 && "a " === strtolower(substr($name, 0, 2))){
            $name = substr($name, 2).", A";
		}
		return $name;
	}

	public function getName(){
		return $this->fullname;
	}

    public function getSortingName(){
        return $this->abridgedName;
    }

	public function getStrippedName($name=false){
		if(!$name){ $name = $this->getSortingName(); }
		$rc = preg_replace("/[^A-Za-z0-9]/", '', $name);
//		logDebug("getStrippedName({$name}): ".var_export($rc, true));
		return $rc;
	}

	public function getInternalAnchor(){
		return $this->getStrippedName();
	}
	
    public function addSong($songTitle){
//		logDebug("Artist->addSong [{$songTitle}]");
		$songTitle = preg_replace("/^\d{1,3}\.\ /", '', $songTitle);//remove track number: "1. "
		$songTitle = preg_replace("/\ \(\d{1,2}:\d\d\)/", '', $songTitle);//strip the tracktime: " (10:22)"
		$songTitle = str_replace(array(" ->", " >"), '', $songTitle);//strip any "into" sign: ">" or "->"
		$songTitle = trim($songTitle);//mainly to remove trailing space before looking for extraInfoChars

		$extraInfoChars = array("~", "!", "@", "#", "$", "%", "^", "&", "*", "+", "=", "?", "|");
		$lastFewChars = str_split(substr($songTitle, -6));
		$intersect = array_intersect($extraInfoChars, $lastFewChars);
		if(array_filter($intersect)){
			//this is a good way to avoid stripping '&', '#', '$' (etc) from song titles, unless the song uses an extra char ...
			//not sure how else to be more specific,
			//could remove the chars from the $lastFewChars then append the result to the rest of the songTitle, but then you get into problems with string length.
			$songTitle = str_replace($extraInfoChars, '', $songTitle);//strip the extrainfo chars: ~!@#$^&*+=?|
		}
		$songTitle = trim($songTitle);//trim, because the rest of the lines rely on absolute position
		
		if(substr($songTitle, 0, 2) === '//'){ $songTitle = ltrim($songTitle, '/'); }
		if(substr($songTitle, -2) === '//'){ $songTitle = rtrim($songTitle, '/'); }
		if(strpos($songTitle, '...') === 0  || strpos($songTitle, '...') === -3){ return; }
		//case-sensitive...
		if(substr($songTitle, -6) === " con't"){ return; }
		if(substr($songTitle, -7) === " ending"){ return; }
		if(substr($songTitle, -6) === " intro"){ return; }
		if(substr($songTitle, -4) === " jam"){ return; }
		if(substr($songTitle, -5) === " jams"){ return; }
		if(substr($songTitle, -4) === " rap"){ return; }
		if(substr($songTitle, -6) === " tease"){ return; }
		if(in_array(strtolower($songTitle), //not case-sensitive
				array('band intros', 'bass', 'bass improv', 'bass jam', 'bass solo', 'blues jam', "con't", 'drums', 'drum solo', 'drumz', 'guitar solo', 
					'improv', 'instrumental', 'intro', 'intro jam', 'intros jam', 'jam', 'jams', 'jazz jam', 'new jam', 'new song', 
					'outro', 'percussion', 'percussion jam', 'space', 'space jam', 'transition jam', 'unknown', 'unknown jam')))
						{ return; }
		$songTitle = trim($songTitle);//one more time for good measure
		
		if(!empty($songTitle)){
			$songs = Songs::getInstance();
	        $songs->addSong($songTitle, $this->getName());
		}
    }

    public function getSongsBy(){
//		logDebug('Artist->getSongsBy [{$this->getName()}]');
		$songs = Songs::getInstance();
		$songsby = $songs->getSongsForArtist($this->getName());
		ksort($songsby, SORT_NATURAL);
        return $songsby;
    }
	
	public function getCareerYears(){
		$result = array();
		$previousYr = false;
		foreach($this->getShows() as $show){
			if($show->getYear() !== $previousYr){
				$result[] = $previousYr = $show->getYear();
			}
		}
//		logDebug('getCareerYears returning: '.var_export($result, true));
		return $result;
	}

	public function toString(){
		return $this->getName();
	}
	
	/* private functions */

	public function addShow($show){
		$this->shows[$show->getDateString()] = $show;
	}

	public function getShows(){
		return $this->shows;
	}

}
