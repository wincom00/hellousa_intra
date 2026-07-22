<?php
    include "include/inc_base.php";

    header("Content-Type: application/json");
    
	
    $qry1 = "delete from rand_company  where part_id = '$randid' && reserveCode = '$rev' && money_type='credit'  && base_rate != 'cmo'";
	$rst1= $dbConn->query($qry1);
	$qry1 = "delete from rand_company_tmp  where part_id = '$randid' && reserveCode = '$rev' && money_type='credit' && base_rate != 'cmo'";
	$rst1= $dbConn->query($qry1);
  //echo $qry1;
  //exit;
	$qry2 = "delete from rand_pay  where rand_id='$randid' && reserveCode = '$reserveCode' && trans_type='credit'";
    $rst2 = $dbConn->query($qry2);
   
    echo $rst2;
?>