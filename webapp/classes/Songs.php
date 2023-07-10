<?php
class Songs{
	private $songs = array();//array of Song Objects: songName=>songObject
	
	public function getSong($title){
		return ($this->songs[$title] ? $this->songs[$title] : false);
	}
	
	public function addSong($title, $artistName){
//		logDebug("Songs->addSong [{$title}] [{$artistName}]");
		if($this->doesSongExist($title)){
			$this->updateSong($title, $artistName);//also increments occurrences
		}else{
			$this->songs[$title] = new Song($title, $artistName);
		}
		return $this->songs[$title];
	}
	
	public function getSongsForArtist($artistName){
//		logDebug("Songs->getSongsForArtist [{$artistName}]");
		$songsby = array();
		foreach($this->songs as $title=>$songObj){
			if($occurrences = $songObj->getArtistOccurrences($artistName)){
				$songsby[$title] = $occurrences;
			}
		}
		return $songsby;
	}
	
	private function updateSong($title, $artistName){
//		logDebug("Songs->updateSong [{$title}] [{$artistName}]");
		$this->songs[$title]->updateSong($artistName);
	}
	
	private function doesSongExist($title){
		return (isset($this->songs[$title]));
	}

	private static $obj = null;//singleton
	public static function getInstance(){
		if(self::$obj === null){
			self::$obj = new Songs();
		}
		return self::$obj;
    }

	private function __construct(){
		//noop
	}
	
}