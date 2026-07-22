
<?php
    if ($mode == "save") {
		//처음접수
		$tmpName1 = $_FILES['userfile1']['tmp_name'];

		
		if(is_uploaded_file($tmpName1)){
				$pds_file1 = $_FILES['userfile1']['name'];
				$board_pds_pos = "upload";
				$attc_name1 = Misc::uploadFileUnsafely($tmpName1 , $pds_file1 , $board_pds_pos);

				$src = 'upload/'."$attc_name1[savedName]";        //-- 원본 
				
				$ffname1 = $attc_name1[savedName];
		} else  {
			$ffname1 = "";
		}

        $tmpName2 = $_FILES['userfile2']['tmp_name'];

		
		if(is_uploaded_file($tmpName2)){
				$pds_file2 = $_FILES['userfile2']['name'];
				$board_pds_pos = "upload";
				$attc_name2 = Misc::uploadFileUnsafely($tmpName2 , $pds_file2 , $board_pds_pos);

				
				$ffname2 = $attc_name2[savedName];
		} else  {
			$ffname2 = "";
		}

        $tmpName3 = $_FILES['userfile3']['tmp_name'];

		if(is_uploaded_file($tmpName3)){
				$pds_file3 = $_FILES['userfile3']['name'];
				$board_pds_pos = "upload";
				$attc_name3 = Misc::uploadFileUnsafely($tmpName1 , $pds_file3 , $board_pds_pos);

				
				$ffname3 = $attc_name3[savedName];
		} else  {
			$ffname3 = "";
		}

		$tmpName4 = $_FILES['userfile4']['tmp_name'];


		if(is_uploaded_file($tmpName4)){
				$pds_file4 = $_FILES['userfile4']['name'];
				$board_pds_pos = "upload";
				$attc_name4 = Misc::uploadFileUnsafely($tmpName4 , $pds_file4, $board_pds_pos);

				
				
				$ffname4 = $attc_name4[savedName];
		} else  {
			$ffname4 = "";
		}
		if ($estimateCode == "") {
		       // 토탈예약용 예약코드
				 if ($grestimateCode=="") {
					$total_estimateNum = getNumReserve_total();
					$total_estimateCode = "DTT".date("ymd").$total_estimateNum;	
				} else {
					$total_estimateNum = getNumReserve_ctotal();
					$total_estimateCode = $grestimateCode;	
				}
				$estimateNum = getNumReserve();
				$estimateCode = "DONGBU".date("ymd").$estimateNum;
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
									re_issue,
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
									muser_id,
									pay_memo,
									userfile1,
									userfile2,
									userfile3,
									userfile4,
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
									'$reissue',
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
									'$user_dbinfo[userid]', 
									'$paymemo',
									'".addslashes($ffname1)."',
									'".addslashes($ffname2)."',
									'".addslashes($ffname3)."',
									'".addslashes($ffname4)."',
									now()
									)";
				//echo $qry1;
				//	exit;
			   
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
									muser_id,
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
									muser_id,
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
									'$user_dbinfo[userid]', 
									'$paymemo', 
									now()
									)";
			   
					$rst1 = $dbConn->query($qry1);




			   }

			   //항공정보
			   for($k=0; $k<count($pnrnum); $k++)
			   {
					//echo count($pnrnum);
					//echo $a_pnr_number[$k]."T".$rde_air[$k];
					//exit;
					if($pnrnum[$k])
					{
					 
						// 입력
						$a_qry1 = "insert into reserve_airline_pnr 
												(reserveCode, 
												rand_id, 
												a_pnr_number, 
												a_tk_number, 
												a_invoice1, 
												a_invoice2, 
												a_airline_start, 
												a_start_airport, 
												a_stop_airport, 
												a_airline_issue, 
												a_pnr_status, 
												a_airport_name, 
												a_airport_num, 
												a_airport_time, 
												a_airport_time1, 
												a_airline_print, 
												a_airline_return, 
												a_start_airport2, 
												a_stop_airport2, 
												a_airport_name2, 
												a_airport_num2, 
												a_airport_time2, 
												a_airport_time3, 
												a_pnr_number1, 
												a_tk_number2, 
												a_settle_type, 
												a_cls_type, 
												a_airline_amt, 
												a_airport_cnt, 
												a_amt_act, 
												a_rate, 
												a_tax, 
												a_fee, 
												a_fee1, 
												a_cms, 
												a_amt, 
												a_air_amt, 
												acc_bal_amt, 
												rand_fee, 
												a_tk_by, 
												a_acc_by, 
												a_re_by, 
												a_memo, 
												a_mco_num, 
												rand_fee_num, 
												seqm,
												memo_air
												)
												values
												('$estimateCode', 
												'$rand_id_air[$k]', 
												'$pnrnum[$k]', 
												'$ticket[$k]', 
												'', 
												'', 
												'$stdate_air[$k]', 
												'$st_air[$k]', 
												'$de_air[$k]', 
												'', 
												'', 
												'$sairnm[$k]', 
												'', 
												'$sairtime[$k]', 
												'$dairtime[$k]', 
												'$airdate[$k]', 
												'$redate_air[$k]', 
												'$rst_air[$k]', 
												'$rde_air[$k]', 
												'$rairnm[$k]', 
												'', 
												'$rairtime[$k]', 
												'$rdairtime[$k]', 
												'$rpnrnum[$k]', 
												'$rticket[$k]', 
												'$a_settle_type[$k]', 
												'$a_cls_type[$k]', 
												'$a_airline_amt[$k]', 
												'$air_p[$k]', 
												'', 
												'$air_rate[$k]', 
												'$airtax[$k]', 
												'$airmco[$k]', 
												'$mcofee[$k]', 
												'$a_cms[$k]', 
												'$a_amt[$k]', 
												'$a_air_amt[$k]', 
												'', 
												'$a_rand_fee[$k]', 
												'', 
												'', 
												'', 
												'', 
												'', 
												'', 
												'$k',
												'$airetc'
												)";
					   // print_r($a_qry1);
						//exit;
						$a_rst1 = $dbConn->query($a_qry1);
						
						
						$seqtmp2=$seqtmp+1;
						//$totamt=$a_amt[$k];
						if ($prodInfo[p_type] == 5) {
							if ($a_settle_type[$k]==1) {
								$totamt=$a_air_amt[$k];
							} else {
								$totamt=($a_amt[$k]);

							}
						} 
						if ($prodInfo[p_type] == 5) { 
									$totamt=$a_air_amt[$k];
								   //echo $totamt."bl".$tmpamt;
									$qry4="insert into rand_company_tmp 
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
												'', 
												'$rand_id_air[$k]', 
												'debit', 
												'USD', 
												'$totamt',
												'0',
												'$airdate[$k]', 
												'발권',
												'READY',
												'$user_dbinfo[userid]',
												'$stdate_air[$k]',
												now()
												);";
									$rst4 = $dbConn->query($qry4);
						} else {
									$history_qry1 = "insert into rand_pay 
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
																			'$rand_id_air[$k]', 
																			'$estimateCode', 
																			now(), 
																			'$stdate_air[$k]', 
																			now(),, 
																			'credit', 
																			'', 
																			'USD', 
																			'credit', 
																			'$airsys', 
																			'$totamt', 
																			'', 
																			'$a_pnr_number[$k]:$a_pnr_number2[$k]-발권합계:$a_airline_amt[$k]', 
																			'$seqtmp2', 
																			'$user_dbinfo[userid]', 
																			now()
																			);";
									//print_r($history_qry1);

									$history_rst1 = $dbConn->query($history_qry1);
									//}
									$balamt=$totamt;
									
									$totamt=-($a_air_amt[$k]);
								   //echo $totamt."bl".$tmpamt;
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
												'', 
												'$rand_id_air[$k]', 
												'debit', 
												'USD', 
												'$totamt',
												'0',
												'$airdate[$k]', 
												'$ramtmemo',
												'READY',
												'$user_dbinfo[userid]',
												'$stdate_air[$k]',
												now()
												);";
									$rst4 = $dbConn->query($qry4);
						
									if ($a_settle_type[$k]==1) {
										$qry1 = "update rand_company set cur_balamt = '$balamt' ,status='DONE'
												 where rand_id='$rand_id_air[$k]' && reserveCode = '$estimateCode' && settle_memo like '%$pnrnum[$k]%'";

										$rst1 = $dbConn->query($qry1);	
										
										//exit;
									}
						}

						$totamt = 0;
						$tmpamt = 0;
						$balamt = 0;
					
						
												
						
					}

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
				   $pos1 = $pos[j]+1;
				   
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
									muser_id, 
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
									'$user_dbinfo[userid]', 
									'$paymemo',
									'$pos1',
									now()
									)";
		            $rst3 = $dbConn->query($qry3);
			   }

			   if ($tourcomp) {
				   if ($prodInfo[p_type] == 5) {
					   $rand_company = "rand_company_tmp";
				   } else {
					   $rand_company = "rand_company";

				   }
				    $qry4="insert into $rand_company 
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
			   if ($tourcm) {
				   if ($prodInfo[p_type] == 5) {
					   $rand_company = "rand_company_tmp";
				   } else {
					   $rand_company = "rand_company";

				   }
				    $qry4="insert into $rand_company 
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
				   if ($prodInfo[p_type] == 5) {
					   $rand_company = "rand_company_tmp";
				   } else {
					   $rand_company = "rand_company";

				   }
				   //단일투어 정보
				   for($j=0; $j<count($tourcomp1); $j++)
				   {
						$qry4="insert into $rand_company 
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
											'$tourcomp1[$j]', 
											'debit', 
											'$brate', 
											'$pamt[$j]', 
											'0', 
											'',
											'READY',
											'$pamtmemo[$j]',
											'$user_dbinfo[userid]', 
											'$startDate',
											now()
											);";
						$rst4 = $dbConn->query($qry4);
				   }
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
				$file_name["userfile1"] = "";
				$file_name["userfile2"] = "";
				$file_name["userfile3"] = "";
				$file_name["userfile4"] = "";
				
				if ($_FILES["userfile1"]["tmp_name"] <> "")			$file_name["userfile1"] = file_save($_FILES["userfile1"], "../upload/");
				if ($_FILES["userfile2"]["tmp_name"] <> "")			$file_name["userfile2"] = file_save($_FILES["userfile2"], "./upload/");
				if ($_FILES["userfile3"]["tmp_name"] <> "")			$file_name["userfile3"] = file_save($_FILES["userfile3"], "./upload/");
				if ($_FILES["userfile4"]["tmp_name"] <> "")			$file_name["userfile4"] = file_save($_FILES["userfile4"], "./upload/");
				
				
				$u1_qry = "";
				$u2_qry = "";
				$u3_qry = "";
				$u4_qry = "";

				$userfile1 = $dbConn->real_escape_string($file_name["userfile1"]);
				$userfile2 = $dbConn->real_escape_string($file_name["userfile2"]);
				$userfile3 = $dbConn->real_escape_string($file_name["userfile3"]);
				$userfile4 = $dbConn->real_escape_string($file_name["userfile4"]);


				if (get_magic_quotes_gpc()) {
			

                if ($file_name["userfile1"] <> "" || $photo_del1 == "1") $u1_qry = " userfile1 = '" .stripslashes((string)$userfile1). "', ";
				if ($file_name["userfile2"] <> "" || $photo_del2 == "1") $u2_qry = " userfile2 = '" .stripslashes((string)$userfile2). "', ";
				if ($file_name["userfile3"] <> "" || $photo_del3 == "1") $u3_qry = " userfile3 = '" .stripslashes((string)$userfile3). "', ";
				if ($file_name["userfile4"] <> "" || $photo_del4 == "1") $u4_qry = " userfile4 = '" .stripslashes((string)$userfile4). "', ";
				
			} else {
			    if ($file_name["userfile1"] <> "" || $photo_del1 == "1") $u1_qry = " userfile1 = '".$userfile1."', ";
				if ($file_name["userfile2"] <> "" || $photo_del2 == "1") $u2_qry = " userfile2 = '".$userfile2."', ";
				if ($file_name["userfile3"] <> "" || $photo_del3 == "1") $u3_qry = " userfile3 = '".$userfile3."', ";
				if ($file_name["userfile4"] <> "" || $photo_del4 == "1") $u4_qry = " userfile4 = '".$userfile4."', ";
				
				
			}
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
								re_issue = '$reissue',
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
								$u1_qry
								$u2_qry
								$u3_qry
								$u4_qry
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
			   $qryd = "delete from reserve_info 
										where
										reserveCode = '$estimateCode'  && parent = 'SUB' && p_code not like  '%PICKUP%' && p_code not like  '%SENDING%'";
				//echo $qryd;
				//exit;
			   $rstd = $dbConn->query($qryd);
			   for($j=0; $j<count($singleDayTourStartDate); $j++)
               {

				   				
				   $pos1 = $pos[j];
				   if ($l_p_code[$j] != "") {
				   //$local_start  = date("Y-m-d",mktime (0,0,0,$s_date[1]  , $s_date[2]+$add_date, $s_date[0]));	
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
										'$revdate', 
										'$singleDayTourStartDate[$j]', 
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
										'".addslashes($pmemo)."', 
										'".addslashes($cmemo)."', 
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
										'$paystatus', 
										'$order_status', 
										'$user_dbinfo[userid]', 
										'$paymemo',
										'$pos[$j]',
										now()
										)";
						$rst3 = $dbConn->query($qry3);
				    }
					
			   
			   }
			   if ($tourcomp !="") {
						if ($prodInfo[p_type] == 5) {
							$rand_company = "rand_company_tmp";
							
					    } else {
						    $rand_company = "rand_company";
						

					    }
						$qryr1 = "select part_id from $rand_company where reserveCode = '$estimateCode' && money_type='credit' && p_memo !='발권' && base_rate!='cmo'";
				        $rstr1 = $dbConn->query($qryr1);
						$rowr1 = $rstr1->fetch_assoc();
						//echo $qryr1
						//echo $tourcomp."|".$rowr1[part_id];
						///exit;
						if ($rowr1[part_id] != $tourcomp) {
							$qryq = "select rand_id from rand_pay where reserveCode = '$estimateCode' && rand_id ='$tccomp' && trans_type='credit' && stDate='$startDate' ";
							$rstq = $dbConn->query($qryq);
							//$rowr1 = mysql_fetch_assoc($rstq);
							$rowrcnt = $rstq->num_rows;

							//$rowr1 = mysql_fetch_assoc($rstr);
							if (($rowrcnt > 0)) {
								 Misc::jvAlert("이 업체에 페이먼트 자료가 있습니다. <br />회계담당자에게 먼저 문의하신후 수정하세요!!!","history.back(-1)");
								 exit;
							}
							$qryc = "delete from $rand_company where part_id ='$rowr1[part_id]' && reserveCode = '$estimateCode' && money_type='credit' && p_memo !='발권'  && base_rate  !='cmo'";
							$rstc = $dbConn->query($qryc);
						
						
							$qry4="insert into $rand_company 
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
											update $rand_company 
												set
												
												money_type = 'credit' , 
												base_rate = '$brate' , 
												amt = '$ramt' , 
												tr_date = '$tr_date' , 
												p_memo = '$ramtmemo',
												rand_date ='$startDate'

												where
												reserveCode = '$estimateCode' && part_id='$tourcomp' && money_type = 'credit' && p_memo !='발권'";
							   $rst4 = $dbConn->query($qry4);
						 }
						 //echo $qry4;
						 //exit;
					
			   }
			   if ($tourcm !="") {
						if ($prodInfo[p_type] == 5) {
							$rand_company = "rand_company_tmp";
							$outqry = "&& base_rate='cmo' ";
							$cap = "cmo";
					    } else {
						    $rand_company = "rand_company";
						    $outqry = "";
							$cap = "";

					    }
						$qryr1 = "select part_id from $rand_company where reserveCode = '$estimateCode' && money_type='credit' && p_memo !='발권' $outqry";
				        $rstr1 = $dbConn->query($qryr1);
						$rowr1 = $rstr1->fetch_assoc();
						//echo $qryr1
						//echo $tourcomp."|".$rowr1[part_id];
						///exit;
						if ($rowr1[part_id] != $tourcm) {
							$qryq = "select rand_id from rand_pay where reserveCode = '$estimateCode' && rand_id ='$tourcm' && trans_type='credit' && stDate='$startDate'";
							$rstq = $dbConn->query($qryq);
							//$rowr1 = mysql_fetch_assoc($rstq);
							$rowrcnt = $rstq->num_rows;

							//$rowr1 = mysql_fetch_assoc($rstr);
							if (($rowrcnt > 0)) {
								 Misc::jvAlert("이 업체에 페이먼트 자료가 있습니다. <br />회계담당자에게 먼저 문의하신후 수정하세요!!!","history.back(-1)");
								 exit;
							}
							$qryc = "delete from $rand_company where part_id ='$rowr1[part_id]' && reserveCode = '$estimateCode' && money_type='credit' && p_memo !='발권' $outqry";
							$rstc = $dbConn->query($qryc);
						
						
							$qry4="insert into $rand_company 
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
													'$tourcm', 
													'credit', 
													'$cap', 
													'$cramt', 
													'$rDate', 
													'$cramtmemo',
													'READY',
													'$user_dbinfo[userid]', 
													now()
													);";
							    $rst4 = $dbConn->query($qry4);
						   } else {
								
								$qry4 ="
											update $rand_company 
												set
												
												money_type = 'credit' , 
												base_rate = '$cap' , 
												amt = '$cramt' , 
												tr_date = '$tr_date' , 
												p_memo = '$cramtmemo',
												rand_date ='$startDate'

												where
												reserveCode = '$estimateCode' && part_id='$tourcm' && money_type = 'credit' && p_memo !='발권'";
							   $rst4 = $dbConn->query($qry4);
						 }
						 //echo $qry4;
						 //exit;
					
			   }
               if ($tourcomp1!="") {
						if ($prodInfo[p_type] == 5) {
							$rand_company = "rand_company_tmp";
							
					    } else {
						    $rand_company = "rand_company";
							

					    }
						for($j=0; $j<count($tourcomp1); $j++)
						{
								$qryr1 = "select part_id from $rand_company where reserveCode = '$estimateCode' && money_type='debit' && p_memo !='발권' && part_id='$tourcomp1[$j]'";
								$rstr1 = $dbConn->query($qryr1);
								$rowr1 = $rstr1->fetch_assoc();
								//echo $tourcomp1."|".$rowr1[part_id];
								//exit;
								
								if ($rowr1[part_id] != $tourcomp1[$j]) {
									$qryq = "select rand_id from rand_pay where reserveCode = '$estimateCode' && rand_id ='$tourcomp1[$j]' && trans_type='debit' && stDate='$startDate'";
									$rstq = $dbConn->query($qryq);
									$rowrcnt = $rstq->num_rows;
									
									if (($rowrcnt > 0)) {
										 Misc::jvAlert("이 업체에 페이먼트 자료가 있습니다. <br />회계담당자에게 먼저 문의하신후 수정하세요!!!!!!","history.back(-1)");
										 exit;
									} 
									
									$qryc = "delete from $rand_company where part_id ='$tourcomp1[$j]' && reserveCode = '$estimateCode' && money_type='debit'";
									$rstc = $dbConn->query($qryc);
									
									$qry4="insert into $rand_company 
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
																'$tourcomp1[$j]', 
																'debit', 
																'$brate', 
																'$pamt[$j]', 
																'$pamtmemo[$j]',
																'READY',
																'$user_dbinfo[userid]', 
																now()
																);";
									$rst4 = $dbConn->query($qry4);
								
								
								} else {
								   
									  $qry4 ="
													update $rand_company 
														set
														
														money_type = 'debit' , 
														base_rate = '$brate' , 
														amt = '$pamt[$j]' , 
														p_memo = '$pamtmemo[$j]',
														rand_date ='$startDate'
														where
														reserveCode = '$estimateCode' && part_id='$tourcomp1[$j]' && money_type = 'debit' && p_memo !='발권'";

									   $rst4 = $dbConn->query($qry4);
									  
									//echo $qry4;
									//exit;
								}
								
						}
						
					//}
			   } 
			   //항공정보
			   $pre_airline_qry2 = "delete from reserve_airline_pnr where reserveCode = '$estimateCode'";
			   $pre_airline_rst2 = $dbConn->query($pre_airline_qry2);
			   for($k=0; $k<count($pnrnum); $k++)
			   {
					//echo count($pnrnum);
					//echo $ticket[$k]."<br />";
					
					if($pnrnum[$k])
					{
					 
		
						// 입력
						$a_qry1 = "insert into reserve_airline_pnr 
												(reserveCode, 
												rand_id, 
												a_pnr_number, 
												a_tk_number, 
												a_invoice1, 
												a_invoice2, 
												a_airline_start, 
												a_start_airport, 
												a_stop_airport, 
												a_airline_issue, 
												a_pnr_status, 
												a_airport_name, 
												a_airport_num, 
												a_airport_time, 
												a_airport_time1, 
												a_airline_print, 
												a_airline_return, 
												a_start_airport2, 
												a_stop_airport2, 
												a_airport_name2, 
												a_airport_num2, 
												a_airport_time2, 
												a_airport_time3, 
												a_pnr_number1, 
												a_tk_number2, 
												a_settle_type, 
												a_cls_type, 
												a_airline_amt, 
												a_airport_cnt, 
												a_amt_act, 
												a_rate, 
												a_tax, 
												a_fee, 
												a_fee1, 
												a_cms, 
												a_amt, 
												a_air_amt, 
												acc_bal_amt, 
												rand_fee, 
												a_tk_by, 
												a_acc_by, 
												a_re_by, 
												a_memo, 
												a_mco_num, 
												rand_fee_num, 
												seqm,
												memo_air
												)
												values
												('$estimateCode', 
												'$rand_id_air[$k]', 
												'$pnrnum[$k]', 
												'$ticket[$k]', 
												'', 
												'', 
												'$stdate_air[$k]', 
												'$st_air[$k]', 
												'$de_air[$k]', 
												'', 
												'', 
												'$sairnm[$k]', 
												'', 
												'$sairtime[$k]', 
												'$dairtime[$k]', 
												'$airdate[$k]', 
												'$redate_air[$k]', 
												'$rst_air[$k]', 
												'$rde_air[$k]', 
												'$rairnm[$k]', 
												'', 
												'$rairtime[$k]', 
												'$rdairtime[$k]', 
												'$rpnrnum[$k]', 
												'$rticket[$k]', 
												'$a_settle_type[$k]', 
												'$a_cls_type[$k]', 
												'$a_airline_amt[$k]', 
												'$air_p[$k]', 
												'', 
												'$air_rate[$k]', 
												'$airtax[$k]', 
												'$airmco[$k]', 
												'$mcofee[$k]', 
												'$a_cms[$k]', 
												'$a_amt[$k]', 
												'$a_air_amt[$k]', 
												'', 
												'$a_rand_fee[$k]', 
												'', 
												'', 
												'', 
												'', 
												'', 
												'', 
												'$k',
												'$airetc'
												)";
						$a_rst1 = $dbConn->query($a_qry1);
						if ($prodInfo[p_type] == 5) {
							$rand_company = "rand_company_tmp";
					    } else {
						    $rand_company = "rand_company";

					    }

						
						if ($a_settle_type[$k]==1) {
						    $totamt=$a_air_amt[$k];
							$divi = "credit";
						} else {
							$totamt=$a_amt[$k];
							$divi = "debit";
						}	
						
						// 넣기전에 이미 있는지 체크한다.
						$pre_qry1 = "select max(seq_no) as seq from $rand_company where money_type='$davi' &&  part_id='$rand_id_air[$k]' && reserveCode = '$estimateCode' && p_memo like '%발권%'
						&& amt='$totamt'";
						$pre_rst1 = $dbConn->query($pre_qry1);
						$rowrcnt1 = $pre_rst1->num_rows;
						//echo $pre_qry1;
						//exit;
						$rand_row1 = $pre_rst1->fetch_assoc();
						if ($rand_row1[seq] == 0) {
						   $seqtmp = 0;
						} else {
						   $seqtmp = $rand_row1[seq]+1;
						}
						
						
						if (($rand_row1[seq] == '')) {
						
							
							$qry4="insert into rand_company_tmp 
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
											values
											( 
											'$estimateCode', 
											'', 
											'$rand_id_air[$k]', 
											'$divi', 
											'USD', 
											'$totamt', 
											'0', 
											'$airdate[$k]', 
											'발권', 
											'$a_settle_type[$k]', 
											'READY', 
											'".$pnrnum[$k].":".$ticket[$k]."-발권합계:$a_airline_amt[$k]', 
											'$user_dbinfo[userid]', 
											'$stdate_air[$k]', 
											now()
											)";

							$rst4 = $dbConn->query($qry4);
						//	echo $qry4."<br />";
						 
						}
						$oldticket = $ticket[$k];
						
						if ($order_status == "DONE") {
							if ($a_settle_type[$k]==1) {
								$totamt=$a_air_amt[$k];
								$divi = "credit";
							} else {
								$totamt=($a_amt[$k]);
								$divi = "debit";
							}

							$rand_qry1 = "update $rand_company set amt='$totamt',
																		money_type ='$divi',
																		settle_memo='$pnrnum[$k]:$ticket[$k]-발권합계:$a_airline_amt[$k]',
																		air_ptype='$a_settle_type[$k]',
																		u_id ='$user_dbinfo[userid]'
																		 
																	  where part_id='$rand_id_air[$k]' && reserveCode = '$estimateCode' && p_memo = '발권' && settle_memo like '%$ticket[$k]%' && amt='$totamt'";

							$rand_rst1 = $dbConn->query($rand_qry1);
							//echo $rand_qry1."<br />";//.$rand_row1[seq]."N";
						
							if ($prodInfo[p_type] != 5) {
							
								if ($a_settle_type[$k]!=2) {
									$tmpamt1=$a_air_amt[$k];
									$history_qry1 = "update rand_pay SET trans_type ='credit',
																				payment='$totamt',
																				where rand_id='$rand_id_air[$k]' && reserveCode = '$estimateCode' && p_memo ='발권' && payment='$totamt'";
									$history_rst1 = $dbConn->query($history_qry1);


									$totamt=-($a_acc_amt[$k]);
									//$balamt=$totamt-$tmpamt1;
									$balamt=$totamt;
									/*
									$qry1 = "update rand_company set cur_balamt = '$balamt' ,status='DONE',settle_memo='발권정산'
											 where part_id='$rand_id_air[$k]' && reserveCode = '$estimateCode' && && p_memo ='발권'";

									$rst2 = $dbConn->query($qry1);
									*/
								}
							}

								$totamt = 0;
								$tmpamt1 = 0;
								$balamt = 0;

							
						}
												
						
					}

			   }
			   //exit;
			   Misc::jvAlert("저장 완료!!!");
			   if ($pricet == 1) {
				  // $sub = "15";
				   
			   } else if ($pricet == 3) {
				 //  $sub = "25";
				   
			   }
			   echo "<meta http-equiv='refresh' content='0; url=./base_reservation_list.php?estimateCode=$estimateCode&division=3&pdx=2&sub=$sub&ty=$ty&pricet=$pricet'>";

		}
    } else if ($mode == "paymentProcess") {

			  //payment history
			   
				if (($paymentmethod == "creditcard") || ($paymentmethod == "creditcardusa")) { //신용카드
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
					   if ($paymentmethod == "creditcard") {
						   $credit_result = credit_process($xType,$address_card,$zipcode,$cardnum,$ccv,$month,$year,$amt,$fname,$lname,$order);
					   } else if ($paymentmethod == "creditcardusa") {
					   
                           $credit_result = credit_processusa($xType,$address_card,$zipcode,$cardnum,$ccv,$month,$year,$amt,$fname,$lname,$order);
					   }
					   
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
									print_r($credit_result);
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


	} else if ($mode == "settle") {
		      $update_qry1 = "update reserve_info set settle_report = '1' where reserveCode = '$estimateCode'";
			  $rst5 = $dbConn->query($update_qry1);

			  Misc::jvAlert("정산보고 완료!!!");
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