
<?php
    if ($mode == "save") {
		//처음접수
		if ($estimateCode == "") {
		       // 토탈예약용 예약코드
				 if ($grestimateCode=="") {
					$total_estimateNum = getNumReserve_total();
					$total_estimateCode = "TU".date("ymd").$total_estimateNum;	
				} else {
					$total_estimateNum = getNumReserve_ctotal();
					$total_estimateCode = $grestimateCode;	
				}
				$estimateNum = getNumReserve();
				$estimateCode = "PUR".date("ymd").$estimateNum;
				if ($pricet == "3") {
					$ttype = "3";
				}
				$qry0 ="insert into grand_reserve 
													( 
													grandNum,
													grand_revNo, 
													revNo, 
													tour_type, 
													p_code, 
													p_name, 
													revDate, 
													stDate, 
													wdate
													)
													values
													( 
													'$total_estimateNum',
									                '$total_estimateCode', 
													'$estimateCode', 
													'$ttype', 
													'$pcode', 
													'$pname', 
													now(), 
													'$startDate', 
													now()
													)";
				$rst0 = $dbConn->query($qry0);

				
				//메인 저장
				if ($pricet == 1) {
					$ttype = 1;
				} else if ($pricet == 3) {
					$ttype = 3;
				}
				$qry1 ="insert into reserve_info 
									(
									grandNum,
									grand_revNo, 
									reserveNum, 
									reserveCode, 
									parent, 
									tour_type, 
									p_code, 
									p_name, 
									meet_area, 
									revDate, 
									stDate, 
									edDate, 
									p_cnt,
									tour_pcnt,
									room_cnt,
									c_day,
									rand_id,
									book_pri, 
									book_phone, 
									book_email, 
									dis_code, 
									c_code,
									progress, 
									c_progress, 
									air_arcity, 
									air_arriveDate, 
									air_arrivetime, 
									air_arriveNm, 
									air_arriveMemo, 
									air_stdate, 
									air_sttime, 
									air_stNm, 
									air_stMemo, 
									air_stcity,
									air_astcity,
									base_rate,
									pricet, 
									last_sale, 
									last_dis, 
									last_add, 
									last_total, 
									last_bal, 
									payment_st, 
									rev_status, 
									userid, 
									pay_memo, 
									wdate
									)
									values
									( 
									'$total_estimateNum',
									'$total_estimateCode', 
									'$estimateNum', 
									'$estimateCode', 
									'MAIN', 
									'$ttype', 
									'$pcode', 
									'".addslashes($pname)."', 
									'', 
									now(), 
									'$startDate', 
									'$endDate', 
									'$pcnt1',
									'$tcnt',
									'$rcnt1',
									'$cday',
									'$rand',
									'$r_name', 
									'$r_phone', 
									'$r_email', 
									'$pickloc', 
									'$dismemo',
									'".addslashes($pmemo)."' , 
									'".addslashes($cmemo)."' , 
									'$arrivecity', 
									'$arrivalDate', 
									'$arrivalTime', 
									'$airname', 
									'$arrivememo', 
									'$departureDate', 
									'$departureTime', 
									'$departureairname', 
									'$departurememo', 
									'$stcity',
									'$astcity',
									'$brate',
									'$pricet',
									'$ttamt', 
									'$ttotdis', 
									'$ttotaddamt', 
									'$tgtotamt', 
									'$tbalamt', 
									'READY', 
									'READY', 
									'$user_dbinfo[userid]', 
									'$paymemo', 
									now()
									)";
			   
		       $rst1 = $dbConn->query($qry1);
			   if ($tourpick != "") {
				    $propic = getProductMaster($tourpick);
					$qry1 ="insert into reserve_info 
									(
									grandNum,
									grand_revNo, 
									reserveNum, 
									reserveCode, 
									parent, 
									tour_type, 
									p_code, 
									p_name, 
									meet_area, 
									revDate, 
									stDate, 
									edDate, 
									p_cnt,
									tour_pcnt,
									room_cnt,
									c_day,
									rand_id,
									book_pri, 
									book_phone, 
									book_email, 
									dis_code, 
									c_code,
									progress, 
									c_progress, 
									air_arcity, 
									air_arriveDate, 
									air_arrivetime, 
									air_arriveNm, 
									air_arriveMemo, 
									air_stdate, 
									air_sttime, 
									air_stNm, 
									air_stMemo, 
									air_stcity,
									air_astcity,
									base_rate,
									pricet, 
									last_sale, 
									last_dis, 
									last_add, 
									last_total, 
									last_bal, 
									payment_st, 
									rev_status, 
									userid, 
									pay_memo, 
									wdate
									)
									values
									( 
									'$total_estimateNum',
									'$total_estimateCode', 
									'$estimateNum', 
									'$estimateCode', 
									'SUB', 
									'$ttype', 
									'$tourpick', 
									'".addslashes($propic[p_name])."', 
									'', 
									now(), 
									'$startDate', 
									'$endDate', 
									'$pcnt1',
									'$tcnt',
									'$rcnt1',
									'$cday',
									'$rand',
									'$r_name', 
									'$r_phone', 
									'$r_email', 
									'pick', 
									'$dismemo',
									'".addslashes($pmemo)."' , 
									'".addslashes($cmemo)."' , 
									'$arrivecity', 
									'$arrivalDate', 
									'$arrivalTime', 
									'$airname', 
									'$arrivememo', 
									'$departureDate', 
									'$departureTime', 
									'$departureairname', 
									'$departurememo', 
									'$stcity',
									'$astcity',
									'$brate',
									'$pricet',
									'$ttamt', 
									'$ttotdis', 
									'$ttotaddamt', 
									'$tgtotamt', 
									'$tbalamt', 
									'READY', 
									'READY', 
									'$user_dbinfo[userid]', 
									'$paymemo', 
									now()
									)";
			   
					$rst1 = $dbConn->query($qry1);




			   }
			   if ($toursend != "") {
				    $prosend = getProductMaster($toursend);
					$qry1 ="insert into reserve_info 
									(
									grandNum,
									grand_revNo, 
									reserveNum, 
									reserveCode, 
									parent, 
									tour_type, 
									p_code, 
									p_name, 
									meet_area, 
									revDate, 
									stDate, 
									edDate, 
									p_cnt,
									tour_pcnt,
									room_cnt,
									c_day,
									rand_id,
									book_pri, 
									book_phone, 
									book_email, 
									dis_code, 
									c_code,
									progress, 
									c_progress, 
									air_arcity, 
									air_arriveDate, 
									air_arrivetime, 
									air_arriveNm, 
									air_arriveMemo, 
									air_stdate, 
									air_sttime, 
									air_stNm, 
									air_stMemo, 
									air_stcity,
									air_astcity,
									base_rate,
									pricet, 
									last_sale, 
									last_dis, 
									last_add, 
									last_total, 
									last_bal, 
									payment_st, 
									rev_status, 
									userid, 
									pay_memo, 
									wdate
									)
									values
									( 
									'$total_estimateNum',
									'$total_estimateCode', 
									'$estimateNum', 
									'$estimateCode', 
									'SUB', 
									'$ttype', 
									'$toursend', 
									'".addslashes($prosend[p_name])."', 
									'', 
									now(), 
									'$startDate', 
									'$endDate', 
									'$pcnt1',
									'$tcnt',
									'$rcnt1',
									'$cday',
									'$rand',
									'$r_name', 
									'$r_phone', 
									'$r_email', 
									'send', 
									'$dismemo',
									'".addslashes($pmemo)."' , 
									'".addslashes($cmemo)."' , 
									'$arrivecity', 
									'$arrivalDate', 
									'$arrivalTime', 
									'$airname', 
									'$arrivememo', 
									'$departureDate', 
									'$departureTime', 
									'$departureairname', 
									'$departurememo', 
									'$stcity',
									'$astcity',
									'$brate',
									'$pricet',
									'$ttamt', 
									'$ttotdis', 
									'$ttotaddamt', 
									'$tgtotamt', 
									'$tbalamt', 
									'READY', 
									'READY', 
									'$user_dbinfo[userid]', 
									'$paymemo', 
									now()
									)";
			   
					$rst1 = $dbConn->query($qry1);




			   }
			   //예약멤버 저장
			   for($i=0; $i<count($t_name); $i++)
               {
				   $qry2 =" insert into reserve_traveler 
									( 
									grand_revNo, 
									reserveCode,
									pass_num,
									pass_date,
									e_memo,
									traveler_nm,
									traveler_enm,
									traveler_phone, 
									traveler_email,
									traveler_birth,
									traveler_room,
									seqint, 
									sextype, 
									room_type,
									pick_type,
									sale_price, 
									pick_area, 
									add_pay, 
									dis_pay, 
									last_pay, 
									wdate
									)
									values
									(
									'$total_estimateCode', 
									'$estimateCode',
									'$t_passnum[$i]',
									'$t_pass[$i]',
									'".addslashes($tmemo[$i])."',
									'$t_name[$i]', 
									'$t_ename[$i]',
									'$t_phone[$i]', 
									'$t_email[$i]',
									'$t_birth[$i]',
									'$room_num[$i]',
									'$i', 
									'$sexType[$i]', 
									'$pickRoomType1[$i]',
									'$pickPriceType1[$i]',
									'$unitPrice[$i]', 
									'$pickuploc[$i]', 
									'$addamt[$i]', 
									'$disamt[$i]', 
									'$lasttamt[$i]', 
									now()
									)";
				   $rst2 = $dbConn->query($qry2);
			   }
			   //단일투어 정보
			   for($j=0; $j<count($singleDayTourStartDate); $j++)
               {

				   				
					// start day
				   if ($arrivalDate !="") {
					    $s_date = explode("-",$arrivalDate);
				   } else {

						$s_date = explode("-",$startDate);
				   }
					
				   $add_date = $tday[$j]-1;
				   $pos1 = $pos[j];
				   
				   $local_start  = date("Y-m-d",mktime (0,0,0,$s_date[1]  , $s_date[2]+$add_date, $s_date[0]));	
				   $qry3 ="insert into reserve_info 
									(
									grandNum,
									grand_revNo, 
									reserveNum, 
									reserveCode, 
									parent, 
									tour_type, 
									p_code, 
									p_name, 
									meet_area, 
									revDate, 
									stDate, 
									edDate, 
									p_cnt,
									tour_pcnt,
									room_cnt,
									c_day,
									rand_id,
									book_pri, 
									book_phone, 
									book_email, 
									dis_code, 
									dis_desc, 
									progress, 
									c_progress, 
									air_astcity,
									air_stcity, 
									air_arriveDate, 
									air_arrivetime, 
									air_arriveNm, 
									air_arriveMemo, 
									air_arcity,
									air_stdate, 
									air_sttime, 
									air_stNm, 
									air_stMemo, 
									base_rate, 
									pricet,
									last_sale, 
									last_dis, 
									last_add, 
									last_total, 
									last_bal, 
									payment_st, 
									rev_status, 
									userid, 
									pay_memo,
									pos,
									wdate
									)
									values
									(
									'$total_estimateNum',
									'$total_estimateCode', 
									'$estimateNum', 
									'$estimateCode', 
									'SUB', 
									'$ttype', 
									'$l_p_code[$j]', 
									'".addslashes($singleTour[$j])."', 
									'$mtarea[$j]', 
									now(), 
									'$local_start', 
									'',
									'$pcnt1',
									'$tcnt',
									'$rcnt1',
									'$tday[$j]', 
									'$rand',
									'$r_name', 
									'$r_phone', 
									'$r_email', 
									'$pickloc', 
									'$dismemo', 
									'$pmemo', 
									'$cmemo', 
									'$astcity',
									'$stcity', 
									'$arrivalDate', 
									'$arrivalTime', 
									'$airname', 
									'$arrivememo', 
									'$arrivecity', 
									'$departureDate', 
									'$departureTime', 
									'$departureairname', 
									'$departurememo', 
									'$brate', 
									'$pricet',
									'$ttamt', 
									'$ttotdis', 
									'$ttotaddamt', 
									'$tgtotamt', 
									'$tbalamt', 
									'READY', 
									'READY', 
									'$user_dbinfo[userid]', 
									'$paymemo',
									'$pos[$j]',
									now()
									)";
		            $rst3 = $dbConn->query($qry3);
			   }

			   if ($tourcomp) {
				    $qry4="insert into rand_company 
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
										status,
										u_id, 
										rand_date,
										wdate
										)
										values
										(
										'$estimateCode', 
										'$tourRegion', 
										'$tourcomp', 
										'credit', 
										'$brate', 
										'$ramt',
										'0',
										'$rDate', 
										'$ramtmemo',
										'READY',
										'$user_dbinfo[userid]',
										'$startDate',
										now()
										);";
					$rst4 = $dbConn->query($qry4);
			   }
               if ($tourcomp1) {
				    $qry4="insert into rand_company 
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
										status,
										u_id,
										rand_date,
										wdate
										)
										values
										(
										'$estimateCode', 
										'$tourRegion1', 
										'$tourcomp1', 
										'debit', 
										'$brate', 
										'$pamt', 
										'0', 
										'',
										'READY',
										'$pamtmemo',
										'$user_dbinfo[userid]', 
										'$startDate',
										now()
										);";
					$rst4 = $dbConn->query($qry4);
			   }  
			   

			   //payment history

			   $qry5 = "insert into payment_history 
										( 
										reserveCode, 
										pay_method, 
										pay_info, 
										payment, 
										b_rate, 
										rate_payment, 
										rate_m, 
										payment_status, 
										pay_memo, 
										register, 
										wdate
										)
										values
										( 
										'$estimateCode', 
										'init', 
										'결제대상', 
										'$tgtotamt', 
										'$brate', 
										'$tgtotamt', 
										'', 
										'READY', 
										'', 
										'$user_dbinfo[userid]', 
										now()
										);";

			  $rst5 = $dbConn->query($qry5);


			   Misc::jvAlert("저장 완료!!!");
			   if ($pricet == 1) {
				   $sub = "15";
				   echo "<meta http-equiv='refresh' content='0; url=./base_reservation_list.php?estimateCode=$estimateCode&division=3&pdx=$pdx&sub=$sub&ty=$ty'>";
			   } else if ($pricet == 3) {
				   $sub = "25";
				   $ty = 3;
				   echo "<meta http-equiv='refresh' content='0; url=./base_reservation_list.php?estimateCode=$estimateCode&division=3&pdx=$pdx&sub=$sub&ty=$ty'>";
			   } else {
			       echo "<meta http-equiv='refresh' content='0; url=./base_reservation_list.php?estimateCode=$estimateCode&division=3&pdx=$pdx&sub=$sub&ty=$ty'>";
			   }
				
		} else if ($estimateCode != "") {
			  
			    //메인 저장
				//발란스계산
				//echo $order_status;
				//exit;

				$qry6= "update payment_history 
									set
									payment = '$tgtotamt' , 
									rate_payment= '$tgtotamt'
									where
									reserveCode = '$estimateCode' && payment_status='READY' && pay_method = 'init'";

							
			    $rst6 = $dbConn->query($qry6);

				$qryp = "select * from payment_history where reserveCode = '$estimateCode' && (payment_status='DONE' || payment_status='RETURN')";
				$rstp = $dbConn->query($qryp);
				while($rowp = $rstp->fetch_assoc()){

            	      if ( $rowp[payment_status] == "RETURN") {

							$rtnamt = $rtnamt + $rowp[payment];
					  } else {
					 		$ttotamt1 = $ttotamt1 + $rowp[payment];
					  }
					  
					  $totpay = $ttotamt1 - $rtnamt;
					  

	            }
				$totbal2 =$tbalamt;//$tgtotamt - $totpay;
				
				if ($paystatus != "CGPAY") {
				  if ($paystatus != "GPAY") {

				  
					if ($totbal2 > 0) {
						$paystatus = "PPAY";
					}
					if ($totbal2 == 0) {
						$paystatus = "DONE";
					}
					
					if ($totbal2 == $tgtotamt) {
						$paystatus = "READY";
					}
					if ($totbal2 < 0) {
						$paystatus = "OPAY";
					}
				  }
				}
				if (($order_status == "CANCEL") && ($payc >0)) {
					
						$paystatus = "OPAY";
				} else if (($order_status == "CANCEL") && ($payc == 0)) {
					    $paystatus = "";
				}
				//echo $tgtotamt."<br >".$totpay ;
				//exit;
				$qry1 ="update reserve_info 
								set
								
	  						   	stDate = '$startDate' , 
								edDate = '$endDate' , 
								p_cnt = '$pcnt1' ,
								rand_id = '$rand',
								book_pri = '$r_name' , 
								book_phone = '$r_phone' , 
								book_email = '$r_email' , 
								p_name = '".addslashes($pname)."', 
								dis_code = '$pickloc' , 
								c_code = '$dismemo' , 
								progress = '".addslashes($pmemo)."' , 
								c_progress = '".addslashes($cmemo)."' ,  
								tour_pcnt ='$tcnt',
								room_cnt = '$rcnt1',
								air_astcity = '$astcity' , 
								air_arcity = '$arrivecity' , 
								air_arriveDate = '$arrivalDate' , 
								air_arrivetime = '$arrivalTime' , 
								air_arriveNm = '$airname' , 
								air_arriveMemo = '$arrivememo' ,
								air_stcity = '$stcity' , 
								air_stdate = '$departureDate' , 
								air_sttime = '$departureTime' , 
								air_stNm = '$departureairname' , 
								air_stMemo = '$departurememo' , 
								pricet ='$pricet',
								last_sale = '$ttamt' , 
								last_dis = '$ttotdis' , 
								last_add = '$ttotaddamt' , 
								last_total = '$tgtotamt' , 
								last_bal = '$totbal2' ,
								payment_st= '$paystatus',
							    rev_status = '$order_status' , 
								muser_id ='$user_dbinfo[userid]', 
								pay_memo = '$paymemo' , 
								wdate = now()
								
								where
								reserveCode = '$estimateCode' && 
								parent = 'MAIN'";
					
				$rst1 = $dbConn->query($qry1);
				//echo $qry1;
					//exit;
				$qryd = "delete from reserve_info 
										where
										reserveCode = '$estimateCode' &&  p_code='$pickcode' && parent = 'SUB'";
				//echo $qryd;
				//exit;
				$rstd = $dbConn->query($qryd);
				if ($tourpick != "") {
					
				    $propic = getProductMaster($tourpick);
					$qry1 ="insert into reserve_info 
									(
									grandNum,
									grand_revNo, 
									reserveNum, 
									reserveCode, 
									parent, 
									tour_type, 
									p_code, 
									p_name, 
									meet_area, 
									revDate, 
									stDate, 
									edDate, 
									p_cnt,
									tour_pcnt,
									room_cnt,
									c_day,
									rand_id,
									book_pri, 
									book_phone, 
									book_email, 
									dis_code, 
									c_code,
									progress, 
									c_progress, 
									air_arcity, 
									air_arriveDate, 
									air_arrivetime, 
									air_arriveNm, 
									air_arriveMemo, 
									air_stdate, 
									air_sttime, 
									air_stNm, 
									air_stMemo, 
									air_stcity,
									air_astcity,
									base_rate,
									pricet, 
									last_sale, 
									last_dis, 
									last_add, 
									last_total, 
									last_bal, 
									payment_st, 
									rev_status, 
									userid, 
									pay_memo, 
									wdate
									)
									values
									( 
									'$total_estimateNum',
									'$total_estimateCode', 
									'$estimateNum', 
									'$estimateCode', 
									'SUB', 
									'$ttype', 
									'$tourpick', 
									'".addslashes($propic[p_name])."', 
									'', 
									now(), 
									'$arrivalDate', 
									'$arrivalDate', 
									'$pcnt1',
									'$tcnt',
									'$rcnt1',
									'$cday',
									'$rand',
									'$r_name', 
									'$r_phone', 
									'$r_email', 
									'pick', 
									'$dismemo',
									'".addslashes($pmemo)."' , 
									'".addslashes($cmemo)."' , 
									'$arrivecity', 
									'$arrivalDate', 
									'$arrivalTime', 
									'$airname', 
									'$arrivememo', 
									'$departureDate', 
									'$departureTime', 
									'$departureairname', 
									'$departurememo', 
									'$stcity',
									'$astcity',
									'$brate',
									'$pricet',
									'$ttamt', 
									'$ttotdis', 
									'$ttotaddamt', 
									'$tgtotamt', 
									'$tbalamt', 
									'$paystatus', 
									'$order_status', 
									'$user_dbinfo[userid]', 
									'$paymemo', 
									now()
									)";
			   
					$rst1 = $dbConn->query($qry1);


					

			   }
			   $qryd = "delete from reserve_info 
										where
										reserveCode = '$estimateCode' &&  p_code='$sendcode' && parent = 'SUB'";
			   $rstd = $dbConn->query($qryd);
			   if ($toursend != "") {
				    
				    $prosend = getProductMaster($toursend);
					$qry1 ="insert into reserve_info 
									(
									grandNum,
									grand_revNo, 
									reserveNum, 
									reserveCode, 
									parent, 
									tour_type, 
									p_code, 
									p_name, 
									meet_area, 
									revDate, 
									stDate, 
									edDate, 
									p_cnt,
									tour_pcnt,
									room_cnt,
									c_day,
									rand_id,
									book_pri, 
									book_phone, 
									book_email, 
									dis_code, 
									c_code,
									progress, 
									c_progress, 
									air_arcity, 
									air_arriveDate, 
									air_arrivetime, 
									air_arriveNm, 
									air_arriveMemo, 
									air_stdate, 
									air_sttime, 
									air_stNm, 
									air_stMemo, 
									air_stcity,
									air_astcity,
									base_rate,
									pricet, 
									last_sale, 
									last_dis, 
									last_add, 
									last_total, 
									last_bal, 
									payment_st, 
									rev_status, 
									userid, 
									pay_memo, 
									wdate
									)
									values
									( 
									'$total_estimateNum',
									'$total_estimateCode', 
									'$estimateNum', 
									'$estimateCode', 
									'SUB', 
									'$ttype', 
									'$toursend', 
									'".addslashes($prosend[p_name])."', 
									'', 
									now(), 
									'$departureDate', 
									'$departureDate', 
									'$pcnt1',
									'$tcnt',
									'$rcnt1',
									'$cday',
									'$rand',
									'$r_name', 
									'$r_phone', 
									'$r_email', 
									'send', 
									'$dismemo',
									'".addslashes($pmemo)."' , 
									'".addslashes($cmemo)."' , 
									'$arrivecity', 
									'$arrivalDate', 
									'$arrivalTime', 
									'$airname', 
									'$arrivememo', 
									'$departureDate', 
									'$departureTime', 
									'$departureairname', 
									'$departurememo', 
									'$stcity',
									'$astcity',
									'$brate',
									'$pricet',
									'$ttamt', 
									'$ttotdis', 
									'$ttotaddamt', 
									'$tgtotamt', 
									'$tbalamt', 
									'$paystatus', 
									'$order_status', 
									'$user_dbinfo[userid]', 
									'$paymemo', 
									now()
									)";
			   
					$rst1 = $dbConn->query($qry1);





			   }
				//예약멤버 저장
				$qryd = "delete from reserve_traveler 
										where
										reserveCode = '$estimateCode'";
				$rstd = $dbConn->query($qryd);
			    for($i=0; $i<count($t_name); $i++)
                { 
				   $qry2 =" insert into reserve_traveler 
									( 
									grand_revNo, 
									reserveCode,
									pass_num,
									pass_date,
									e_memo,
									traveler_nm,
									traveler_enm,
									traveler_phone, 
									traveler_email,
									traveler_birth,
									traveler_room,
									seqint, 
									sextype, 
									room_type,
									pick_type,
									sale_price, 
									pick_area, 
									add_pay, 
									dis_pay, 
									last_pay, 
									wdate
									)
									values
									(
									'$total_estimateCode', 
									'$estimateCode',
									'$t_passnum[$i]',
									'$t_pass[$i]',
									'".addslashes($tmemo[$i])."',
									'$t_name[$i]', 
									'$t_ename[$i]',
									'$t_phone[$i]', 
									'$t_email[$i]',
									'$t_birth[$i]',
									'$room_num[$i]',
									'$i', 
									'$sexType[$i]', 
									'$pickRoomType1[$i]',
									'$pickPriceType1[$i]',
									'$unitPrice[$i]', 
									'$pickuploc[$i]', 
									'$addamt[$i]', 
									'$disamt[$i]', 
									'$lasttamt[$i]', 
									now()
									)";
				   $rst2 = $dbConn->query($qry2);
			    }

			    //단일투어 정보
				/*
			    for($j=0; $j<count($singleDayTourStartDate); $j++)
                {
				  // start day
				  // start day
				   if ($j == 0) {
				     $s_date = explode("-",$startDate);
					 $add_date = $tday[$j]-1;
				   } else {
					 $s_date = explode("-",$local_start);
					 
				   }
					$s_date = explode("-",$startDate);
				    $add_date = $tday[$j];
				    
				   
				   $local_start  = date("Y-m-d",mktime (0,0,0,$s_date[1]  , $s_date[2]+$add_date, $s_date[0]));
				   //echo $local_start."<br>";
				   $qry3 ="update reserve_info 
									set
									stDate = '$local_start',
									meet_area = '$mtarea[$j]' , 
									p_cnt = '$pcnt1' ,
									c_day = '$tday[$j]',
									rev_status = '$order_status' , 
									payment_st= '$paystatus',
									progress = '$pmemo' , 
									pay_memo = '$paymemo',
									muser_id ='$user_dbinfo[userid]',
									pos = '$pos[$j]',
									wdate = now()
									where
									reserveCode = '$estimateCode' && 
								    parent = 'SUB' && p_code = '$l_p_code[$j]' && seq_no='$seqnum[$j]'";
		           //$rst3 = $dbConn->query($qry3);
				   echo $qry3."<br>";
			    }
			    */
				$qry1 = "select * from product_details_local where p_code = '$pcode'  
									order by day,position,seq_no asc";
									
				$rst1 = $dbConn->query($qry1);
				$cntd = $rst1->num_rows;
				$j = 0;
				while($r_row = $rst1->fetch_assoc()):
				   // start day
				   
				   $s_date = explode("-",$startDate);
					
				   $add_date = $r_row[day]-1;

				   $local_start  = date("Y-m-d",mktime (0,0,0,$s_date[1]  , $s_date[2]+$add_date, $s_date[0]));
				   if (($l_p_code[$j] == 'LAPICKUP') || ($l_p_code[$j] == 'LVPICKUP') || ($l_p_code[$j] == 'PICKUP')) {
						$pickar = $local_start;
						$qry3 ="update reserve_info 
									set
									air_arriveDate = '$pickar' 
									where
									reserveCode = '$estimateCode' && 
								    parent = 'MAIN'";
		                $rst3 = $dbConn->query($qry3);
				   }
				   if (($l_p_code[$j] == 'LASENDING') || ($l_p_code[$j] == 'LVSENDING') || ($l_p_code[$j] == 'SENDING')) {
						$pickst = $local_start;
						$qry3 ="update reserve_info 
									set
									air_stdate = '$pickst'  
									where
									reserveCode = '$estimateCode' && 
								    parent = 'MAIN'";
		                $rst3 = $dbConn->query($qry3);

				   }
				   //echo $local_start."<br>";
				   $qry3 ="update reserve_info 
									set
									
									stDate = '$local_start',
									meet_area = '$mtarea[$j]' , 
									p_cnt = '$pcnt1' ,
									rand_id = '$rand',
								    book_pri = '$r_name' , 
							 	    book_phone = '$r_phone' , 
								    book_email = '$r_email' , 
									room_cnt = '$rcnt1',
									dis_code = '$pickloc' , 
									c_code = '$dismemo' , 
									progress = '".addslashes($pmemo)."' , 
									c_progress = '".addslashes($cmemo)."' ,  
									tour_pcnt ='$tcnt',
									c_day = '$tday[$j]',
									pricet ='$pricet',
									air_astcity = '$astcity',
									air_arcity = '$arrivecity' , 
									air_arriveDate = '$pickar' , 
									air_arrivetime = '$arrivalTime' , 
									air_arriveNm = '$airname' , 
									air_arriveMemo = '$arrivememo' ,
									air_stcity = '$stcity' , 
									air_stdate = '$pickst' , 
									air_sttime = '$departureTime' , 
									air_stNm = '$departureairname' , 
									air_stMemo = '$departurememo' , 
									rev_status = '$order_status' , 
									payment_st= '$paystatus',
									progress = '$pmemo' , 
									pay_memo = '$paymemo',
									muser_id ='$user_dbinfo[userid]',
									pos = '$pos[$j]',
									wdate = now()
									where
									reserveCode = '$estimateCode' && 
								    parent = 'SUB' && p_code = '$l_p_code[$j]' && seq_no='$seqnum[$j]'";
		           $rst3 = $dbConn->query($qry3);
				   //echo $qry3."<br>";
				   $j++;
			    endwhile;
				//echo $tourcomp;
			  ///exit;
			   if ($tourcomp !="") {
				    
						$qryr1 = "select part_id from rand_company where reserveCode = '$estimateCode' && money_type='credit' ";
				        $rstr1 = $dbConn->query($qryr1);
						$rowr1 = $rstr1->fetch_assoc();
						//echo $tourcomp."|".$rowr1[part_id];
						///exit;
						if ($rowr1[part_id] != $tourcomp) {
							$qryq = "select rand_id from rand_pay where reserveCode = '$estimateCode' && rand_id ='$tccomp' && trans_type='credit' && stDate='$startDate'";
							$rstq = $dbConn->query($qryq);
							$rowrcnt = $rstq->num_rows;

							//$rowr1 = mysql_fetch_assoc($rstr);
							if (($rowrcnt > 0)) {
								 Misc::jvAlert("이 업체에 페이먼트 자료가 있습니다. <br />회계담당자에게 먼저 문의하신후 수정하세요!!!","history.back(-1)");
								 exit;
							}
							$qryc = "delete from rand_company where part_id ='$tourcomp' && reserveCode = '$estimateCode' && money_type='credit'";
							$rstc = $dbConn->query($qryc);
						
						
							$qry4="insert into rand_company 
													( 
													reserveCode, 
													part_area, 
													part_id, 
													money_type, 
													base_rate, 
													amt, 
													tr_date,
													p_memo,
													status,
													u_id, 
													wdate
													)
													values
													(
													'$estimateCode', 
													'$tourRegion', 
													'$tourcomp', 
													'credit', 
													'$brate', 
													'$ramt', 
													'$rDate', 
													'$ramtmemo',
													'READY',
													'$user_dbinfo[userid]', 
													now()
													);";
							    $rst4 = $dbConn->query($qry4);
						   } else {
								
								$qry4 ="
											update rand_company 
												set
												
												money_type = 'credit' , 
												base_rate = '$brate' , 
												amt = '$ramt' , 
												tr_date = '$tr_date' , 
												p_memo = '$ramtmemo',
												rand_date ='$startDate'
'
												where
												reserveCode = '$estimateCode' && part_id='$tourcomp' && money_type = 'credit' ";
							   $rst4 = $dbConn->query($qry4);
						 }
					
			   }
               if ($tourcomp1) {
				   
						$qryr1 = "select part_id from rand_company where reserveCode = '$estimateCode' && money_type='debit'";
				        $rstr1 = $dbConn->query($qryr1);
						$rowr1 = $rstr1->fetch_assoc();
						//echo $tourcomp1."|".$rowr1[part_id];
						//exit;
						
						if ($rowr1[part_id] != $tourcomp1) {
							$qryq = "select rand_id from rand_pay where reserveCode = '$estimateCode' && rand_id ='$tdcomp' && trans_type='debit' && stDate='$startDate'";
							$rstq = $dbConn->query($qryq);
							$rowrcnt = $rstq->num_rows;
							
							if (($rowrcnt > 0)) {
								 Misc::jvAlert("이 업체에 페이먼트 자료가 있습니다. <br />회계담당자에게 먼저 문의하신후 수정하세요!!!!!!","history.back(-1)");
								 exit;
							} 
							$qryc = "delete from rand_company where part_id ='$rowr1[part_id]' && reserveCode = '$estimateCode' && money_type='debit'";
							$rstc = $dbConn->query($qryc);
							$qry4="insert into rand_company 
														( 
														reserveCode, 
														part_area, 
														part_id, 
														money_type, 
														base_rate, 
														amt, 
														p_memo,
														status,
														u_id, 
														wdate
														)
														values
														(
														'$estimateCode', 
														'$tourRegion1', 
														'$tourcomp1', 
														'debit', 
														'$brate', 
														'$pamt', 
														'$pamtmemo',
														'READY',
														'$user_dbinfo[userid]', 
														now()
														);";
							$rst4 = $dbConn->query($qry4);
						
						} else {
						
							$qry4 ="
											update rand_company 
												set
												
												money_type = 'debit' , 
												base_rate = '$brate' , 
												amt = '$pamt' , 
												p_memo = '$pamtmemo',
												rand_date ='$startDate'
												where
												reserveCode = '$estimateCode' && part_id='$tourcomp1' && money_type = 'debit' ";

							   $rst4 = $dbConn->query($qry4);
						    //echo $qry4;
							//exit;
						}
						
					//}
			   }
			   
			   Misc::jvAlert("저장 완료!!!");
			   if ($pricet == 1) {
				   $sub = "15";
				   $ty = 1;
			   } else if ($pricet == 3) {
				   $sub = "25";
				   $ty = 3;
			   }
			   echo "<meta http-equiv='refresh' content='0; url=./base_reservation_list.php?estimateCode=$estimateCode&division=3&pdx=2&sub=$sub&ty=$ty&pricet=$pricet'>";

		}
    } else if ($mode == "paymentProcess") {

			  //payment history
			   
				if ($paymentmethod == "creditcard") { //신용카드
					   $order = $estimateCode;
					   $amt = $clastpayamt;
					   $fname = $fcardname;
					   $lname = $lcardname;
					   $cardnum = $cardnum;
					   $month = $ccexpmo;
					   $mm=substr($ccexpyr,-2);
					   $year = $mm;
					   $cvv = $cvvnum;
					   $address_card = "USANaN$addressNaN$cityNaN$state";

						// 인증ONLY
					   $xType = "AUTH_CAPTURE";
						
					   $credit_result = credit_process($xType,$address_card,$zipcode,$cardnum,$ccv,$month,$year,$amt,$fname,$lname,$order);
					   /*echo "<br/><br/><br/><br/><br/><br/><pre>";
					   print_r($rst);
					   echo "</pre>";
					   //exit; */
					   if($credit_result[0] == "2")
						{
							
							$tour_credit_return_msg = "$credit_result[0] $credit_result[1] $credit_result[2] $credit_result[3] $credit_result[4] $credit_result[5] $credit_result[6] $credit_result[7]";

							
							echo "<script> window.alert('Declined! $credit_result[1] / $credit_result[2] / $credit_result[3] / $credit_result[4]'); history.go(-1); </script>";
							exit;

						}
						else if($credit_result[0] == "3")
						{
							
							$tour_credit_return_msg = "$credit_result[0] $credit_result[1] $credit_result[2] $credit_result[3] $credit_result[4] $credit_result[5] $credit_result[6] $credit_result[7]";

							echo "<script> window.alert('Declined! $credit_result[1] / $credit_result[2] / $credit_result[3] / $credit_result[4]'); history.go(-1); </script>";
							exit;

							
						}
						else
						{
			
							//$trans_id = $credit_result[7];
							  if ($credit_result[6] != "") {
								 $pinfo = "Approved / $credit_result[6] $credit_result[7]";
								 $currencytype ="USD";
								 $payst1 = "DONE";
								 
								 $qry5 = "insert into payment_history 
													( 
													reserveCode, 
													pay_method, 
													pay_info, 
													payment, 
													b_rate, 
													rate_payment, 
													rate_c, 
													rate_m, 
													payment_status, 
													pay_memo, 
													register, 
													wdate
													)
													values
													( 
													'$estimateCode', 
													'$paymentmethod', 
													'$pinfo', 
													'$amt', 
													'USD', 
													'$clastpayamt', 
													'', 
													'$clastpayamt', 
													'$payst1', 
													'$ccmemo', 
													'$user_dbinfo[userid]', 
													now()
													);";
							
								   $rst5 = $dbConn->query($qry5);
								   $tlastpay=$lastbalance - $amt;
								   if ($tlastpay == 0) {
									  $paycap = "DONE";
								   } else if ($tlastpay > 0) {
									  $paycap = "PPAY";
								   } else if ($tlastpay < 0) {
									  $paycap = "OPAY";
								   } 
								   $qry6= "update reserve_info 
														set
														last_bal = '$tlastpay' , 
														payment_st = '$paycap'  
														where
														reserveCode = '$estimateCode'  && parent = 'MAIN' ";

												
								  $rst6 = $dbConn->query($qry6);
										
							  } else {
									
								  Misc::jvAlert("결제 실패 다시 확인하시고 결제하세요!!!");
								   if ($pricet == 1) {
									   $sub = "15";
								   } else if ($pricet == 3) {
									   $sub = "25";
								   }
								   echo "<meta http-equiv='refresh' content='0; url=./base_reservation_m.php?estimateCode=$estimateCode&division=$division&pdx=$pdx&sub=$sub&ty=$ty&pricet=$pricet'>";
								   exit;
								
							  }
						}
					  
						
				} else { 
					   if ($currencytype == "CAD") {
								$ratepay = "BUY";
								$ratevalue = $buyrate;
						} else if ($currencytype == "USD") {
								$ratepay = "SELL";
								$ratevalue = $sellrate;
								
						}
						//echo $currencytype.'<br />S'.$sellrate.'<br />B'.$buyrate.'<br />R'.$ratevalue;

						$payst1 ="DONE";
				

					    $qry5 = "insert into payment_history 
													( 
													reserveCode, 
													pay_method, 
													pay_info, 
													payment, 
													b_rate, 
													rate_payment, 
													rate_c, 
													rate_m, 
													payment_status, 
													pay_memo, 
													register, 
													wdate
													)
													values
													( 
													'$estimateCode', 
													'$paymentmethod', 
													'', 
													'$lastpayamt', 
													'USD', 
													'$lastpayamt', 
													'$ratepay', 
													'', 
													'$payst1', 
													'$dmemo', 
													'$puser', 
													now()
													);";
						//echo $currencytype.'<br />S'.$sellrate.'<br />B'.$buyrate.'<br />R'.$ratevalue;
						//exit;
					   $rst5 = $dbConn->query($qry5);
					   $tlastpay=$lastbalance - $lastpayamt;
					   if ($tlastpay == 0) {
						  $paycap = "DONE";
					   } else if ($tlastpay > 0) {
						  $paycap = "PPAY";
					   } else if ($tlastpay < 0) {
						  $paycap = "OPAY";
					   } else if ($tlastpay == $lasttotal) {
						  $paycap = "READY";
					   }
					   $qry6= "update reserve_info 
											set
											last_bal = '$tlastpay' , 
											payment_st = '$paycap'  
											where
											reserveCode = '$estimateCode'  && parent = 'MAIN' ";

									
					  $rst6 = $dbConn->query($qry6);

				}
			   Misc::jvAlert("결제 완료!!!");
			   if ($pricet == 1) {
				   $sub = "15";
				   $ty = 1;
			   } else if ($pricet == 3) {
				   $sub = "25";
				   $ty = 3;
			   }
			   echo "<meta http-equiv='refresh' content='0; url=./base_reservation_m.php?estimateCode=$estimateCode&division=$division&pdx=$pdx&sub=$sub&ty=$ty&pricet=$pricet'>";
			   exit;

	} else if ($mode == "paymentReturn") {
		      if ($paymentmethod != "creditcard") {
					$currencytype2 == "USD";
					$payst1 ="RRQUEST";
			  }

			  $qry5 = "insert into payment_history 
											( 
											reserveCode, 
											pay_method, 
											pay_info, 
											payment, 
											b_rate, 
											rate_payment, 
											rate_c, 
											rate_m, 
											payment_status, 
											pay_memo, 
											register, 
											wdate
											)
											values
											( 
											'$estimateCode', 
											'$paymentmethod2', 
											'', 
											'$rpay2', 
											'USD', 
											'$rpay2', 
											'$ratepay', 
											'0', 
											'$payst1', 
											'$dmemo2', 
											'$puser2', 
											now()
											);";

			  $rst5 = $dbConn->query($qry5);
			  //$tlastpay=$lastbalance - $rpay2;
			  

			  Misc::jvAlert("환불신청 완료!!!");
			  if ($pricet == 1) {
				   $sub = "15";
				   $ty = 1;
			   } else if ($pricet == 3) {
				   $sub = "25";
				   $ty = 3;
			   }
			  echo "<meta http-equiv='refresh' content='0; url=./base_reservation_m.php?estimateCode=$estimateCode&division=$division&pdx=$pdx&sub=$sub&ty=$ty&pricet=$pricet'>";


	}

?>