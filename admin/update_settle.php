<?php

  include("include/inc_base.php");
    
  header("Content-Type: application/json");
  
  
  $result_array =  array();
  $qry6= "update reserve_info 
							set
							settle_report = '1' 
							
							where
							reserveCode = '$revCode'  ";
 //echo $qry6;
 $rst6 = $dbConn->query($qry6);		
 $result_array = json_encode("1");
 echo $result_array;
