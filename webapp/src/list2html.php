<?php
include_once('../config_new.php');

if(!function_exists('ourautoload')){
	function ourautoload($classname){
	if(file_exists("../classes/{$classname}.php")){
			require_once("classes/{$classname}.php");
		}
	}
}
spl_autoload_register('ourautoload');

$showlist = new Showlist();
$showlist->processInputFile();

exit;