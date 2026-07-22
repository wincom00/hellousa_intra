<?php

  include("include/inc_base.php");

  
  if ($order_status == "confirm") {
		
		if ($row1[part_id] == "TK1005") {
			
			$smemo = $row1[settle_memo];
			$smat = $row1[amt];
			

		} else { ///if (($row1[p_memo] == "발권") && ($row1[air_ptype] != "")) {
			$smemo = $row1[settle_memo];
			$smat = $row1[amt];

		} 
		
		$qry4 ="insert into rand_company 
							(
							reserveCode, 
							part_area, 
							part_id, 
							money_type, 
							base_rate, 
							amt, 
							cur_amt, 
							tr_date, 
							p_memo,
							air_ptype,
							status, 
							settle_memo, 
							u_id, 
							rand_date, 
							wdate
							)
						select 	reserveCode, 
							part_area, 
							part_id, 
							money_type, 
							base_rate, 
							amt, 
							'$smat', 
							tr_date, 
							p_memo,
							air_ptype,
							status, 
							'$smemo', 
							u_id, 
							rand_date, 
							wdate
							 
							from 
							rand_company_tmp 
							where reserveCode='".$r_code."'";
		//echo $qry4."<br />";
		// && settle_memo='".addslashes($smemo)."'
		$rst4 = $dbConn->query($qry4);


		//exit;
  
		$qry3 = "update reserve_info set settle_report='2' ,out_profit='$pay_profit_amt' where reserveCode='".$r_code."'"; 
		
		$rst3= $dbConn->query($qry3);
		//echo count($a_settle_type);
		//exit;
		for($i = 0; $i < count($a_settle_type) ;$i++) 
		{
			if ($a_settle_type[$i]!='2')  {
			   if ($a_settle_type[$i]=='1') {
					$airsys ="airsys";
					
			   } else if ($a_settle_type[$i]=='3') {
					$airsys ="bcreditcard";
			   }
			   $qryr = "select * from reserve_airline_pnr where reserveCode = '".$r_code."' && a_tk_number='".$a_tk_number[$i]."' order by a_airline_print asc ";
			   //echo $qryr;

			   $rstr = $dbConn->query($qryr);
			   while($rrow = $rstr->fetch_assoc()) {
				   $rst2 = getRandInfoAIr($r_code,$rrow[rand_id]);
				   while($arow = $rst2->fetch_assoc()) {

						if ($arow[amt] == $rrow[a_air_amt]) {
						    $seqno = $arow[seq_no];
							break;
						} else {
							$seqno = 0;

						}
				   }
				   //echo $seqno;
				   ///exit;
				  /* $randqry="insert into rand_pay 
									(
									rand_id, 
									reserveCode, 
									rand_date, 
									stDate, 
									tr_date, 
									tr_type, 
									tr_bank, 
									trans_rate, 
									trans_type, 
									pay_method, 
									payment, 
									r_payment, 
									set_memo, 
									seq_rand, 
									u_id, 
									wdate
									)
									values
									( 
									'$rrow[rand_id]', 
									'$r_code', 
									now(), 
									'$rrow[a_airline_start]', 
									now(), 
									'credit', 
									'', 
									'USD', 
									'credit', 
									'$airsys', 
									'$rrow[a_air_amt]', 
									'$rrow[a_air_amt]', 
									'$rrow[a_pnr_number]:$rrow[a_tk_number]-발권합계:$rrow[a_airline_amt]', 
									'$seqno', 
									'$user_dbinfo[userid]', 
									now()
									);";
		  //echo $randqry;
		  
				$randrst=$dbConn->query($randqry);
				/*$qry3 = "update rand_company set status='SETTLEDONE' where part_id='".$rrow[rand_id]."' && reserveCode='".$r_code."'";
				$rst3= $dbConn->query($qry3);
				*/

			   }
			
			}
		}
		//exit;
		
		Misc::jvAlert("정산보고완료!!!");
		//echo "<meta http-equiv='refresh' content='0; url=./invoice_out.php?division=6&pdx=3&sub=17&r_code=".$r_code.">";
		echo("<script>self.close()</script>");
		exit;
  } else if ($order_status == "cancel") {
		$qry1 = "DELETE FROM rand_company WHERE  reserveCode='".$r_code."'";
		$dbConn->query($qry1);
        $qry2 = "DELETE FROM rand_pay WHERE reserveCode='".$r_code."'";
        $dbConn->query($qry2);

		$qry3 = "update reserve_info set settle_report='0' where reserveCode='".$r_code."'";
		$dbConn->query($qry3);
		Misc::jvAlert("정산보고취소완료!!!");
		echo("<script>self.close()</script>");
		//echo "<meta http-equiv='refresh' content='0; url=./out_settle_list.php?division=6&pdx=3&sub=17'>";
		exit;
  }

