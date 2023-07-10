<?php
class Song{
	private $title = false;
	private $artists = array();//array of artistName=>ocurrences
	
	function __construct($songTitle, $artistName){
		$this->title = $songTitle;
		$this->addArtist($artistName);
	}
	
	public function addArtist($artistName){
//		logDebug("Song->addArtist [{$this->getSongTitle()}] [{$artistName}]");
		if(!isset($this->artists[$artistName])){
			$this->artists[$artistName] = 0;
		}
		$this->artists[$artistName]++;
//		logDebug("incremented [{$this->getSongTitle()}] [{$artistName}] [{$this->artists[$artistName]}]");
	}
	
	public function getArtistOccurrences($artistName){
		return (isset($this->artists[$artistName]) ? $this->artists[$artistName] : false);
	}
	
	public function getSongTitle(){
		return $this->title;
	}
	
	public function updateSong($artistName){
//		logDebug("Song->updateSong [{$this->getSongTitle()}] [{$artistName}]");
		$this->addArtist($artistName);
	}
	
}