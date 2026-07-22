<?php

  include("include/inc_base.php");
    
  header("Content-Type: application/json");
  
  $code2 = explode(',',$code1);
  for ($k=0;$k <=count($code2)-1 ;$k++ ) {

	  if ($k == (count($code2)-1)) {
			 $ccd .= "'".$code2[$k]."'";
			 
	  } else  {
			 $ccd .="'".$code2[$k]."',";
			
	  }
  }
			
 
  $qry1 = "select * from base_opt where  opt_code in ($ccd) ";
  $rst1 = $dbConn->query($qry1);
  $result_array =  array();
 //echo $qry1;
  if ($rst1) { // 쿼리 결과가 유효한지 확인
    // $rst1->fetch_object(): mysqli_result에서 한 행을 객체로 가져옵니다.
    while ($row = $rst1->fetch_object()) {
        $result_array[] = $row;
    }
    $rst1->free_result(); // 결과 메모리 해제
 }
	
  
  
  $result_array = json_encode($result_array);
   
  echo $result_array;
