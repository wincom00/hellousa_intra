<?php

    session_start();
    
    date_default_timezone_set('america/new_york');

    include 'dbclass.php';

    $dbhost = '127.0.0.1';
    $dbuser = 'root';
    $dbpass = '';
    $dbname = 'parandb';
    $today = date("Y-m-d H:i:s");

    $db = new db($dbhost, $dbuser, $dbpass, $dbname);

?>
