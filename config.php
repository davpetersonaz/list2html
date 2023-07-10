<?php

//database
$hostname="rockenoutcom.ipagemysql.com";
$username="shows";
$password="keepThatGr00veGoing";
$dbname="shows";
mysql_connect($hostname, $username, $password) OR DIE ('Unable to connect to database!');
mysql_select_db($dbname);

$SHOWS_FILE = "/home/dave/SpiderOak Hive/mine/shows";

//file constants
define('BASE_URL', 'http://shows.rockenout.com/');
define('STATIC_PAGES', 'http://shows.rockenout.com/staticpages/');
define('PICS_DIR', 'http://shows.rockenout.com/pics/');

//line type constants
define('ARTIST_LINE', 1);
define('BLANK_LINE', 2);
define('COMMENT_REST_LINE', 3);
define('DASHED_LINE', 4);
define('DATE_LINE', 5);
define('DAUD_SBD_LINE', 6);
define('DISC_LINE', 7);
define('FOOTNOTE_LINE', 8);
define('HTTP_LINE', 9);
define('MY_INFO_LINE', 10);
define('NORMAL_LINE', 11);
define('NOTE_LINE', 12);
define('PH_COMP_LINE', 13);
define('RATING_LINE', 14);
define('SET_LINE', 15);
define('SONG_LINE', 16);
define('SOURCE_LINE', 17);
define('THE_BAND_LINE', 18);
define('TITLE_LINE', 19);
define('TOTAL_DISCS_LINE', 20);
define('VENUE_LINE', 21);
define('NOT_TRADEABLE_LINE', 22);

define('STRIP_CHARS', "/[^0-9a-zA-Z]/");

//include functions
//include $_SERVER['DOCUMENT_ROOT'] . '/functions.php';
include 'functions.php';
require_once 'KLogger.php';

?>