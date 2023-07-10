<?php

function stripChars($data)
{
    return preg_replace('/[^A-Za-z0-9]+/', '', $data);
}

// basic field validation (present and neither empty nor only white space)
function isNullOrEmptyString($question)
{
    return (!isset($question) || trim($question)==='');
}

function getVenue($showOid)
{
    $sql = "SELECT * FROM location
			WHERE show='$showOid' AND sequence='0'";
    $result = mysql_query($sql) or die ("error: $sql");
    return mysql_fetch_array($result)['data'];
}


?>
