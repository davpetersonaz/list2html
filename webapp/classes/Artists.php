<?php
/**
 * manages Artist objects
 */
class Artists{
	private $artists = array();//array of Class::Artist

	public function addArtist(Artist $artist){
//		logDebug('Artists::addArtist(): '.$artist->getName());
		$name = $artist->getName();
		if(isset($this->artists[$name])){
			
			//TODO:  not sure about this, maybe just overwrite the current Artist in the array
			logDebug('ERROR: trying to addArtist an existing artist: '.var_export($name, true));
			return false;
		}else{
			$this->artists[$name] = $artist;
			return true;
		}
	}
	
	public function addShowToArtist(Show $show){
		logDebug('addShowToArtist: '.var_export($show->getArtistName(), true));
		$this->getArtistByName($show->getArtistName())->addShow($show);
	}
	
	public function createArtist($name){
//		logDebug('createArtist: '.var_export($name, true));
		$artist = new Artist($name);
//		logDebug('new artist: '. var_export($artist, true));
		$this->addArtist($artist);
		return $artist;
	}
	
	public function getArtists(){
		return $this->artists;
	}

	public function getArtistByName($name){
//		logDebug('Artists::getArtistByName: '.var_export($name, true));
//		logDebug('this->artists: '.var_export($this->artists, true));
		if(isset($this->artists[$name])){
			$artist = $this->artists[$name];
		}else{
			$artist = $this->createArtist($name);
		}
		return $artist;
	}
	
	public function getArtistNames(){
		$artistNames = array();
//		logDebug('artists: '.var_export(array_slice($this->getArtists(), 0, 3), true));
		foreach($this->getArtists() as $artist){
			$artistNames[] = $artist->getName();
		}
//		logDebug('getArtistNames returning: '.var_export($artistNames, true));
		return $artistNames;
	}
	
	public function getArtistNamesSorted(){
		$artistNames = array();
//		logDebug('artists: '.var_export(array_slice($this->getArtists(), 0, 3), true));
		foreach($this->getArtists() as $artist){
			$artistNames[] = $artist->getSortingName();
		}
		sort($artistNames);
//		logDebug('getArtistNamesSorted returning: '.var_export($artistNames, true));
		return $artistNames;
	}
	
	public function getSongsForArtist($artistName){
//		logDebug("Artists->getSongsForArtist [{$artistName}]");
		$artist = $this->getArtistByName($artistName);
		$songsby = $artist->getSongsBy();
		ksort($songsby, SORT_NATURAL);
		return $songsby;
	}
	
	private function __construct(){
		//noop
	}

	private static $obj = null;//singleton
	public static function getInstance(){
		if(self::$obj === null){
			self::$obj = new Artists();
		}
		return self::$obj;
    }
	
}