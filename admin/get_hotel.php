<?php

  include("include/inc_base.php");
    
  header("Content-Type: application/json");
  
  
 

  $qry1 = "select h_code,h_name,m_rate from product_hotel where p_typem = '$code1' && u_type in ('1','3')  order by h_name asc";
 
  $rst1 = $dbConn->query($qry1);    
  $result_array =  array();
 
  while($row = $rst1->fetch_assoc()) 
  {
  
         $result_array[] = $row;
  }
	
  
  
  $result_array = json_encode($result_array);
   
  echo $result_array;
