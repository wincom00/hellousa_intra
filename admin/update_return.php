<?php

  include("include/inc_base.php");
    
  header("Content-Type: application/json");
  $qry0 = "select reserveCode from payment_history where seq_no='$seq'" ;
  $rst0 = $dbConn->query($qry0); 
  $row = $rst0->fetch_assoc();
  $reserveCode = $row[reserveCode];
  $qry1 = "select last_total,last_bal from reserve_info where reserveCode='$reserveCode' && parent='MAIN'";
 

  $rst1 = $dbConn->query($qry1);    
  $result_array =  array();
// echo $qry1."<br>";
  while($row = $rst1->fetch_assoc()) 
  {
	    if( $row->last_bal == 0) {
			 $bal = $row->last_bal + $payamt;
			 $row->last_bal = $bal;
			 
			
			 $qry6= "update reserve_info 
										set
										last_bal = '$bal'  
										where
										reserveCode = '$reserveCode'  && parent = 'MAIN' ";

			//echo $qry6."<br/>";		
			 $rst6 = $dbConn->query($qry6);
		} else {
			 $bal = $row->last_bal + $payamt;
			 $row->last_bal = $bal;
			 
			
			 $qry6= "update reserve_info 
										set
										last_bal = '$bal'  
										where
										reserveCode = '$reserveCode'  && parent = 'MAIN' ";

			//echo $qry6."<br/>";	
			//exit;
			$rst6 = $dbConn->query($qry6);

		}
		 $qry7= "update payment_history 
									set
									payment_status = 'RETURN',
									rconf_user = '$user_dbinfo[userid]',
									conf_date = now()
									where
									reserveCode = '$reserveCode'  && payment_status = 'RRQUEST' ";

		 //echo $qry7."<br/>";			
		 $rst7 = $dbConn->query($qry7);
         $result_array[] = $row;

  }
	//exit;
    $result_array = json_encode($result_array);
   
  echo "1";
