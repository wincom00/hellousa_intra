<?php

  include("include/inc_base.php");
    
  header("Content-Type: application/json");
  
  
  $result_array =  array();
  $qry6= "update reserve_info 
							set
							last_bal = '$bal',
							reserve_info where reserveCode='$reserveCode' && parent='MAIN' ";
 //echo $qry6;
 $rst6 = $dbConn->query($qry6);		
 $result_array = json_encode("1");
 echo $result_array;
