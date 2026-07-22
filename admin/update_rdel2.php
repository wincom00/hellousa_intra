<?php

  include("include/inc_base.php");
    
  header("Content-Type: application/json");
  
  
  $qry1 = "delete from payment_history where reserveCode='$reserveCode' && seq_no = '$seq'";
  $rst1 = $dbConn->query($qry1); 

  $qry1 = "select last_total,last_bal from reserve_info where reserveCode='$reserveCode' && parent='MAIN'";
  $rst1 = $dbConn->query($qry1);    
  $result_array =  array();
// echo $qry1."<br>";
  while($row = $rst1->fetch_assoc()) 
  {
		$result_array[] = $row;
  }
 
  $result_array = json_encode($result_array);

  echo $result_array;
 