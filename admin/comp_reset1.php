<?php
    include "include/inc_base.php";

    header("Content-Type: application/json");
    $randty =$_GET['randty'];
	
    $qry1 = "delete from $randty  where part_id = '$randid' && reserveCode = '$rev' && money_type='debit'";
	$rst1= $dbConn->query($qry1);
   //echo $qry1;
 // exit;
	$qry2 = "delete from rand_pay  where rand_id='$randid' && reserveCode = '$reserveCode' && trans_type='debit'";
    $rst2 = $dbConn->query($qry2);
   
    echo $rst2;
?>