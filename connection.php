<?php
$host = "localhost";   
$dbuser = "D1229568";       
$dbpassword = '$mrjW7TzL';       
$dbname = "D1229568";      

$link= mysqli_connect($host, $dbuser, $dbpassword, $dbname);

if ($link) {
    mysqli_query($link, "SET NAMES utf8");
}
else {
    echo "Failed<br>";
    mysqli_connect_error();
}
?>



