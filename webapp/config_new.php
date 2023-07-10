<?php
define('EOL', "\r\n");
define('DEBUG_LOG', '../logs/debug.log');
define('DEBUG_TIMESTAMP', 'D M d H:i:s');
if(!function_exists('logDebug')){
	function logDebug($text){
		error_log('['.date(DEBUG_TIMESTAMP).'] '.$text.EOL, 3, DEBUG_LOG);
	}
}

define('debug', true);

//html helpers
define('HTTPS', "https://");
define('BR', "<BR />");

//directories
define('MY_HOME_DIR', "/home/dave/");
define('PROJECT_HOME', MY_HOME_DIR.'mysite/siteground/list2html/webapp/');
define('LOGOS_DIR', PROJECT_HOME.'public_html/pics/');
define('OUTPUT_DIR', PROJECT_HOME.'public_html/output/');
define('OUTPUT_SHOWS_DIR', OUTPUT_DIR."shows/");
define('OUTPUT_SONGSBY_DIR', OUTPUT_DIR."songsby/");

//http paths
define('BASE_URL', HTTPS."davesmusiclist.com/");
define('PICS_URL', BASE_URL."pics/");
define('STATIC_PAGES_URL', HTTPS."davpeterson.com/staticpages/");

//file names
define('SHOWS_INPUT_FILE', MY_HOME_DIR.'sync/mine/shows');
define('SHOWS_INDEX_HTML', "index.html");
define('HEADER_HTML', "header.html");
define('SHN_INDEX_HTML', "shnidx.html");
define('GIF_SIZES', "picSizes.txt");
define('HPB_RTF', "hpb.rtf");
define('PHISH_STATS_HTML', "phishstats.html");
define('PHISHSONGS_TXT', "phish songs.txt");

//misc
define('LINK_COLORS', "<body bgcolor=black text=yellow link=#FF8C00 alink=#00FFFF vlink=#00FFFF>");
define('MY_ADDRESS', "davpeterson@zoho.com");

//run autoloader to instantiate all the classes
//logDebug('run autoload');
function ourautoload($classname){
	if(file_exists(PROJECT_HOME."classes/".$classname.".php")){
		require_once(PROJECT_HOME."classes/".$classname.".php");
	}
}
spl_autoload_register('ourautoload');
logDebug('autoload complete');