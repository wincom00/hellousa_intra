<?php

  include("include/inc_base.php");
    
  header("Content-Type: application/json");
  

  $qry1 = "select * from member_list where userid = '$randid' order by company_area asc";
 
  $rst1 = $dbConn->query($qry1);    
  $result_array =  array();
  
  while($row = $rst1->fetch_assoc()) 
  {
  
         $result_array[] = $row;
  }
	
  
  
  $result_array = json_encode($result_array);
   
  echo $result_array;
