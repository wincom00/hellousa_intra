<?php
  include("include/inc_base.php");

  header("Content-Type: application/json");
  
  
  
  $qry1 = "select message from mailing_history where reserveCode = '$estimateCode' && seq_no='$seq'";

  //echo $qry1;
		
  $rst1 = $dbConn->query($qry1);
  $result_array =  array();

  while($row = $rst1->fetch_assoc()) 
  {

		 $result_array[] = $row;
  }
	
  
  
  $result_array = json_encode($result_array);
   
  echo $result_array;

?>
