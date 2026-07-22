<?php

  include("include/inc_base.php");
    
  header("Content-Type: application/json");
  
  
 
    if ($grestimateCode=="") {
		$total_estimateNum = getNumReserve_total();
		$total_estimateCode = "TP".date("ymd").$total_estimateNum;	
	} else {
		$total_estimateNum = getNumReserve_ctotal();
		$total_estimateCode = $grestimateCode;	
	}
	$estimateNum = getNumReserve();
	$estimateCode = "PAR".date("ymd").$estimateNum;
    $qry1 = "select count(reserveCode) cnt  from reserve_info where reserveCode='$estimateCode'";
 
    //echo $qry1;
    $rst1 = $dbConn->query($qry1);    
    $result_array =  array();
 
    while($row = $rst1->fetch_assoc()) 
    {
  
         $result_array[] = $row;
    }
	
  
  
     $result_array = json_encode($result_array);
   
    echo $result_array;
