<?php
session_start();
//include $_SERVER['DOCUMENT_ROOT'] . '/config.php';
include 'config.php';

$m_oid = $_SESSION['m_oid'];
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns='http://www.w3.org/1999/xhtml' lang='en' xml:lang='en'>
    <head>
        <title>Daves Shows</title>
<?php
echo"
        <link rel='stylesheet' href='".BASE_URL."style.css' type='text/css' media='screen'/>
	</head>
    <body onload='setFocus()'>
";
?>

<?php
if (!isset($m_oid))
{
	echo "<p class=resultFont>You are not logged in.</p><BR/>";
	echo "<p class=resultFont><a href='../login.php'>Log back into GLOW!</a></p>";

	//'loggedout' is a tag in .htaccess that should goto <root>/home.php
	header("location:loggedout");//this crashes the page - use obstart?
	exit;
}
?>