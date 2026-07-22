<?php


    include_once  'dbclass.php';
	/*$db_host = "db5016562642.hosting-data.io";
	$db_user = "dbu4064937";

	$db_passwd = 'Lee10011!';
	$db_name = "dbs13437728";
    $charset = 'utf8';
   */
   /*
    $db_host = "database-1.c6dioccwsg78.us-east-1.rds.amazonaws.com";
	$db_user = "admin";

	$db_passwd = 'Lee10011!!';
	$db_name = "dbs13437728";
    
   */
	$charset = 'utf8';
    //$dbConn = new mysqli($db_host,$db_user,$db_passwd,$db_name);
	$dbConn = mysqli_init();
	$dbConn->options(MYSQLI_OPT_CONNECT_TIMEOUT,10);
	$dbConn->real_connect('p:database-1.c6dioccwsg78.us-east-1.rds.amazonaws.com', 'admin', 'Lee10011!!', 'dbs13437728', 3306, null, MYSQLI_CLIENT_COMPRESS);
    if ($dbConn->connect_errno) {
        printf("Connect failed: %s\n", $dbConn->connect_error);
        exit();
    }
	

    $dbConn->set_charset($charset);


?>
