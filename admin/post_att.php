<?php

    include("include/inc_base.php");
    

	header("Content-Type: application/json");
    if($kind == "1")
	{
			$qry1 = "insert into att_log (userid,login_date,login_ip,status) values ('$user_dbinfo[userid]',now(),'".$_SERVER['REMOTE_ADDR']."','1')";
			$rst1 = $dbConn->query($qry1);
			//print_r($qry1);
			$new_date=date("U", mktime(0,0,0,(date("m")), (date("d")), date("Y")));
			$dates=date("Y-m-d", $new_date);
		  
		    $qry2 = "select max(id) as mxid from att_log where userid='$user_dbinfo[userid]' && date_format( login_date, '%Y-%m-%d' ) = '$dates'";
			$rst2 = $dbConn->query($qry2);
			$row0 = $rst2->fetch_assoc();
			 
		    $m_qry1 = "select status,login_date from att_log where userid='$user_dbinfo[userid]' && date_format( login_date, '%Y-%m-%d' ) = '$dates' && id='$row0[mxid]' ";
			$m_rst1 = $dbConn->query($m_qry1);

	} else {
		    $new_date=date("U", mktime(0,0,0,(date("m")), (date("d")), date("Y")));
			$dates=date("Y-m-d", $new_date);
			
			$qry2 = "select max(id) as mxid from att_log where userid='$user_dbinfo[userid]' && date_format( login_date, '%Y-%m-%d' ) = '$dates'";
			$rst2 = $dbConn->query($qry2);
			$row1 = $rst2->fetch_assoc();
		
			//퇴근
			
			$qry1 = "update att_log set logout_date=now() ,logout_ip='".$_SERVER['REMOTE_ADDR']."' , status='2' 
			where userid='$user_dbinfo[userid]' && date_format( login_date, '%Y-%m-%d' ) = '$dates' && id='$row1[mxid]'";
      
			$rst1 = $dbConn->query($qry1);

			$m_qry1 = "select status,logout_date from att_log where userid='$user_dbinfo[userid]' && date_format( login_date, '%Y-%m-%d' ) = '$dates' && id='$row1[mxid]' ";
			$m_rst1 = $dbConn->query($m_qry1);

	}
    $result_array =  array();
	
	while($row = $m_rst1->fetch_object()) 
	{
  
         $result_array[] = $row;
        
	}
	$result_array = json_encode($result_array);
 
    echo $result_array;

?>
