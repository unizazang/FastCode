<?php
    require_once __DIR__ . '/config.php';

    // Use the db_connect function from config.php
    $mysqli = db_connect();
    if($mysqli -> connect_errno){
        die('Connect Error:'.$mysqli->connect_error);
    } 
    // echo 'connect successfully';
?>