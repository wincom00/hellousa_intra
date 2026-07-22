<?php

    session_start();
    
    date_default_timezone_set('america/new_york');

    include 'dbclass.php';

    $dbhost = '74.208.228.155';
    $dbuser = 'wincom00';
    $dbpass = 'lee10011!!';
    $dbname = 'parandb';
    $today = date("Y-m-d H:i:s");

    $db = new db($dbhost, $dbuser, $dbpass, $dbname);

?>
