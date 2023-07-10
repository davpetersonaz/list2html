<?php
class ArtistRankings{
    private $rankingsSoFar = 0;
    private $quickLink = '';

    function __construct($rankingsSoFar, $quickLink){
        $this->rankingsSoFar = $rankingsSoFar;
        $this->quickLink = $quickLink;
    }

    public function getQuickLink(){
        return $this->quickLink;
    }

    public function getRankingsSoFar(){
        return $this->rankingsSoFar;
    }

}