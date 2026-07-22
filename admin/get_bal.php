<?php

  include("include/inc_base.php");
    
  header("Content-Type: application/json");
  
  
  $qry1 = "select last_sale,last_total,last_bal from reserve_info where reserveCode='$code1' && parent='MAIN'";
 

  $rst1 = $dbConn->query($qry1);    
  $result_array =  array();
// echo $qry1."<br>";
  while($row = $rst1->fetch_assoc()) 
  {
	     $totamt = ($unitamt+$lastval2+$airamt+$optamt)-$lastval;
         /*if ($totamt == $row[last_total]) {
				$totamt = ($unitamt+$lastval2)-$lastval;
		 } else if ($totamt < $row[last_total]) {
				$totamt = ($row[last_total]+$lastval2)-$lastval;
		 } else {
				$totamt = ($unitamt+$lastval2)-$lastval;
		 }
		 */
		 $qryp = "select * from payment_history where reserveCode = '$code1' && (payment_status='DONE' || payment_status='RETURN')";
		 $rstp = $dbConn->query($qryp);
		 while($rowp = $rstp->fetch_assoc()){

			  if ( $rowp[payment_status] == "RETURN") {

					$rtnamt = $rtnamt + $rowp[payment];
			  } else {
					$ttotamt1 = $ttotamt1 + $rowp[payment];
			  }
			  
			 
			  

		 }
		 $totpay = $ttotamt1 - $rtnamt;
		// $lstbal = $unitamt - $totpay+($lastval2 - $lastval);
		 $lstbal = (round($totamt,2) - round($totpay,2));
		//echo $totamt."test".$lstbal."TEST".$totpay;
		 //exit;
		 $bal = $lstbal;
		 $row["last_bal"] = round($bal,2);
		 //echo $totamt."test".$row["last_bal"]."TEST".$totpay;
		// print_r($row);
	
         $result_array[] = $row;

		 

  }
	
  
  
  $result_array = json_encode($result_array);
   
  echo $result_array;
