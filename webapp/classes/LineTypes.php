<?php
//line types
enum LineTypes{
	case ARTIST_LINE;
	case BLANK_LINE;
	case COMMENT_REST_LINE;
	case DASHED_LINE;
	case DATE_LINE;
	case DAUD_SBD_LINE;
	case DISC_LINE;
	case FOOTNOTE_LINE;
	case HTTP_LINE;
	case MY_INFO_LINE;
	case NORMAL_LINE;
	case NOTE_LINE;
	case PH_COMP_LINE;
	case RATING_LINE;
	case SET_LINE;
	case SONG_LINE;
	case SOURCE_LINE;
	case THE_BAND_LINE;
	case TITLE_LINE;
	case TOTAL_DISCS_LINE;
	case VENUE_LINE;
	case NOT_TRADEABLE_LINE;

	public function getType(){
		return $this->name;
	}
	
	public static function identifyLine($line, $debug=false){
		$line = trim($line);
		$uppercaseLine = strtoupper($line);
		$commaPos = strpos($line, ', ');
		$rc = 0;

		//very specific first:
		if(strpos($line, "---------------------------------") === 0){
			$rc = LineTypes::DASHED_LINE;
		}elseif($line === "NOT TRADEABLE"){
			$rc = LineTypes::NOT_TRADEABLE_LINE;
		}elseif(strpos($line, "[disc ") === 0){
			$rc = LineTypes::DISC_LINE;
		}elseif(strpos($line, "[show]") === 0){
			$rc = LineTypes::DISC_LINE;
		}elseif(strpos($line, "pc: ") === 0){
			$rc = LineTypes::PH_COMP_LINE;
		}elseif(strpos($line, "http://") === 0){
			$rc = LineTypes::HTTP_LINE;
		}elseif(strpos($line, "https://") === 0){
			$rc = LineTypes::HTTP_LINE;
		}elseif(strpos($line, "The ") === 0 && strpos($line, " Band:") === strlen($line)-6){
			$rc = LineTypes::THE_BAND_LINE;
		}elseif(strpos($line, "//") === 0){//this check now follows "The Band:" cuz of 'one for woody' (see above)
			$rc = LineTypes::MY_INFO_LINE;
		}elseif(strpos($line, "/**") === 0){
			$rc = LineTypes::COMMENT_REST_LINE;
		}elseif(trim($line) === ''){
			$rc = LineTypes::BLANK_LINE;
		}elseif(
			strpos($uppercaseLine, "BOOKMARK: ") === 0 ||
			strpos($uppercaseLine, "BROADCAST: ") === 0 ||
			strpos($uppercaseLine, "BROADCAST DATE: ") === 0 ||
			strpos($uppercaseLine, "CATALOG: ") === 0 ||
			strpos($uppercaseLine, "CDR>SHN: ") === 0 ||
			strpos($uppercaseLine, "COMPILATION: ") === 0 ||
			strpos($uppercaseLine, "CONFIG: ") === 0 ||
			strpos($uppercaseLine, "CONFIGURATION: ") === 0 ||
			strpos($uppercaseLine, "CONVERSION: ") === 0 ||
			strpos($uppercaseLine, "DAT>SHN: ") === 0 ||
			strpos($uppercaseLine, "DITHER: ") === 0 ||
			strpos($uppercaseLine, "EDIT: ") === 0 ||
			strpos($uppercaseLine, "EDITING: ") === 0 ||
			strpos($uppercaseLine, "ENCODED BY: ") === 0 ||
			strpos($uppercaseLine, "ENCODING: ") === 0 ||
			strpos($uppercaseLine, "EQUIPMENT: ") === 0 ||
			strpos($uppercaseLine, "EXTRACTED BY: ") === 0 ||
			strpos($uppercaseLine, "FLAC: ") === 0 ||
			strpos($uppercaseLine, "FORMAT: ") === 0 ||
			strpos($uppercaseLine, "GENERATION: ") === 0 ||
			strpos($uppercaseLine, "LINEAGE: ") === 0 ||
			strpos($uppercaseLine, "LOCATION: ") === 0 ||
			strpos($uppercaseLine, "MANUFACTURED BY: ") === 0 ||
			strpos($uppercaseLine, "MASTERED BY: ") === 0 ||
			strpos($uppercaseLine, "MASTERING: ") === 0 ||
			strpos($uppercaseLine, "MIC: ") === 0 ||
			strpos($uppercaseLine, "MICS: ") === 0 ||
			strpos($uppercaseLine, "MIXED BY: ") === 0 ||
			strpos($uppercaseLine, "MIXING: ") === 0 ||
			strpos($uppercaseLine, "ORIGINAL LABEL: ") === 0 ||
			strpos($uppercaseLine, "PATCH: ") === 0 ||
			strpos($uppercaseLine, "PATCHED BY: ") === 0 ||
			strpos($uppercaseLine, "PROCESSING: ") === 0 ||
			strpos($uppercaseLine, "PRODUCED BY: ") === 0 ||
			strpos($uppercaseLine, "PRODUCED AND MIXED BY: ") === 0 ||
			strpos($uppercaseLine, "RECORDED BY: ") === 0 ||
			strpos($uppercaseLine, "RECORDED AND MASTERED BY: ") === 0 ||
			strpos($uppercaseLine, "RECORDED AND MIXED BY: ") === 0 ||
			strpos($uppercaseLine, "RECORDED AND TRANSFERRED BY: ") === 0 ||
			strpos($uppercaseLine, "RECORDING: ") === 0 ||
			strpos($uppercaseLine, "RECORDING AND TRANSFER BY: ") === 0 ||
			strpos($uppercaseLine, "REFERENCE: ") === 0 ||
			strpos($uppercaseLine, "RETRACKED BY: ") === 0 ||
			strpos($uppercaseLine, "SEED: ") === 0 ||
			strpos($uppercaseLine, "SEEDED BY: ") === 0 ||
			strpos($uppercaseLine, "SOUND QUALITY: ") === 0 ||
			strpos($uppercaseLine, "SOURCE: ") === 0 ||
			strpos($uppercaseLine, "SOURCE (") === 0 ||
			strpos($uppercaseLine, "SOURCE 1: ") === 0 ||
			strpos($uppercaseLine, "SOURCE 2: ") === 0 ||
			strpos($uppercaseLine, "SOURCE 3: ") === 0 ||
			strpos($uppercaseLine, "SOURCES: ") === 0 ||
			strpos($uppercaseLine, "TAPE & TRANSFER: ") === 0 ||
			strpos($uppercaseLine, "TAPE & TRANSFER BY: ") === 0 ||
			strpos($uppercaseLine, "TAPE AND TRANSFER: ") === 0 ||
			strpos($uppercaseLine, "TAPE AND TRANSFER BY: ") === 0 ||
			strpos($uppercaseLine, "TAPED AND MASTERED BY: ") === 0 ||
			strpos($uppercaseLine, "TAPED & TRANSFERRED BY: ") === 0 ||
			strpos($uppercaseLine, "TAPED AND TRANSFERRED BY: ") === 0 ||
			strpos($uppercaseLine, "TAPE/TRANSFER: ") === 0 ||
			strpos($uppercaseLine, "TAPED/TRANSFERRED BY: ") === 0 ||
			strpos($uppercaseLine, "TAPED BY: ") === 0 ||
			strpos($uppercaseLine, "TAPER: ") === 0 ||
			strpos($uppercaseLine, "TAPERS NOTES: ") === 0 ||
			strpos($uppercaseLine, "TRACKED BY: ") === 0 ||
			strpos($uppercaseLine, "TRACKING: ") === 0 ||
			strpos($uppercaseLine, "TRANSFER: ") === 0 ||
			strpos($uppercaseLine, "TRANSFER BY: ") === 0 ||
			strpos($uppercaseLine, "TRANSFERRED BY: ") === 0 ||
			strpos($uppercaseLine, "UPLOADED BY: ") === 0 ||
			strpos($uppercaseLine, "VERSION: ") === 0 ||
			strpos($uppercaseLine, "XREF: ") === 0
		){
			$rc = LineTypes::SOURCE_LINE;
		}elseif(preg_match("[~!@#$%^&*=|\?]", $line[0]) === 1){
			$rc = LineTypes::FOOTNOTE_LINE;
		}

		//less specific
		elseif(strpos($line, " disc") === 1 
				|| strpos($line, " disc") === 2){ //allows double digits numbers of discs
			$rc = LineTypes::TOTAL_DISCS_LINE;
		}elseif(strlen($line) >= 6 && strpos($line, "-") === 2 && strpos($line, "-", 3) === 5){
			$rc = LineTypes::DATE_LINE;
		}elseif($commaPos >= 5 
				&& strlen($line) > $commaPos + 5
				&& is_numeric(substr($line, $commaPos+2, 3))
				&& is_numeric(substr($line, $commaPos+5))){
			$rc = LineTypes::DATE_LINE;
		}elseif(str_contains('123456789', substr($line, 0, 1)) 
				&& (strpos($line, '.') === 1 || strpos($line, '.') === 2)){
			$rc = LineTypes::SONG_LINE;
		}elseif(strpos($line, ":") > 0 
				&& (
					strpos($line, "set ") === 0
					|| strpos($line, "philler") === 0
					|| strpos($line, "soundcheck:") > 0 
					|| strpos($line, "filler") > 0	//kinda vague
					|| strpos($line, "encores:") === 0 
					|| strpos($line, "encore ") === 0
					|| strpos($line, "encore:") === 0 )
				){
			$rc = LineTypes::SET_LINE;
		}elseif(strpos($line, "DAUD") === 0 
				|| strpos($line, "AUD") === 0
				|| strpos($line, "DSBD") === 0
				|| strpos($line, "SBD") === 0
//				|| strpos($line, "SHN") === 0
				|| strpos($line, "VCD") === 0
				|| strpos($line, "STUDIO") === 0
				|| strpos($line, "MATRIX") === 0
				|| strpos($line, "FM") === 0){
			$rc = LineTypes::DAUD_SBD_LINE;
		}
		//put this toward the end cuz its 'sweeping implications' (> and =)
		elseif(strpos($uppercaseLine, "NOTE: ") !== false 
				&& strpos($uppercaseLine, "NOTES: ") !== false ){
			$rc = LineTypes::NOTE_LINE;
		}else{
			//if all else fails...
			$rc = LineTypes::NORMAL_LINE;
		}
		if($debug){ logDebug("identifyLine [".$rc->getType()."]: ".trim($line)); }
		return $rc;
	}
	
}
