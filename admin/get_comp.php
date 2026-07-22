<?php

  include("include/inc_base.php");
    
  header("Content-Type: application/json");
  
  
 
  $lvcode1 = substr($code1,0,3);
  $lvcode2 = substr($code1,3,2);

  $qry1 = "select * from member_list where company_area = '$code1' order by company_area asc";
 
  $rst1 = $dbConn->query($qry1);    
  $result_array =  array();
 
  while($row = $rst1->fetch_assoc()) 
  {
  
         $result_array[] = $row;
  }
	
  
  
  $result_array = json_encode($result_array);
   
  echo $result_array;
