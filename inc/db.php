<?php
    $hostname = 'localhost';
    $dbuserid = 'unizazang123';
    $dbpasswd = 'ehflxhtm1!';
    $dbname = 'unizazang123';

    $mysqli = new mysqli($hostname,$dbuserid, $dbpasswd,$dbname);
    if($mysqli -> connect_errno){
        die('Connect Error:'.$mysqli->connect_error);
    } 
    // echo 'connect successfully';
?>