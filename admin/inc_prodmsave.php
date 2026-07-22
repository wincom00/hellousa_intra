<?php
     header('Content-Type: text/html; charset=utf-8');
	 if ($ty == 1 && $m_guidechk == "V") {
	 	$m_guidechk = "V"; 
	 } else { 
	 	$m_guidechk = "";
	 }
	 if ($pcode == "")  {	 
            $p_num = getPnumber();
			if ($ty == 1) {
				$pcode1 = "AEL";
			} else if ($ty == 2) {
				$pcode1 = "INB";
			} else if ($ty == 4) {
				$pcode1 = "ICT";
			} else if ($ty == 5) {
				$pcode1 = "OUB";
			} else if ($ty == 6) {
				$pcode1 = "HOM";
			}
			$p_code = $pcode1.$p_num[numChar];
		
			for($k=0; $k<count($pickLoc); $k++)
			{
				if ($pickLoc[$k] !="") {
					$qry1 = "insert into product_pick 
										( 
										p_code, 
										seq, 
										pick_area, 
										pick_time, 
										wdate
										)
										values
										(
										'$p_code', 
										'$k', 
										'$pickLoc[$k]', 
										'$pickTime[$k]', 
										now()
										)";
					$rst1 = $dbConn->query($qry1);
				}
			}
			for($k=0; $k<count($optLoc); $k++)
			{
				if ($optLoc[$k] !="") {
					$qry1 = "insert into product_opt 
															( 
															p_code, 
															seq, 
															opt_code, 
															wdate
															)
															values
															(
															'$p_code', 
															'$k', 
															'$optLoc[$k]', 
															now()
															)";
					$rst1 = $dbConn->query($qry1);
				}
			}
			
			for($k=0; $k<count($blockDate); $k++)
			{
				if ($blockDate[$k] !="") {
					$qry1 = "insert into product_limit 
										(
										p_code, 
										seq, 
										p_type, 
										p_limitdate, 
										wdate
										)
										values
										(
										'$p_code', 
										'$k', 
										'L', 
										'$blockDate[$k]', 
										now()
										)";
					$rst1 = $dbConn->query($qry1);
				}
			}

			for($k=0; $k<count($reservationDate); $k++)
			{
				if ($reservationDate[$k] !="") {
					$qry1 = "insert into product_limit 
										(
										p_code, 
										seq, 
										p_type, 
										p_limitdate, 
										wdate
										)
										values
										(
										'$p_code', 
										'$k', 
										'R', 
										'$reservationDate[$k]', 
										now()
										)";
					$rst1 = $dbConn->query($qry1);
				}
			}
			for($m=0; $m<count($tour_area0); $m++)
			{
				$tour_area_value0 .= $tour_area0[$m]."/";
			}
			for($m=0; $m<count($tour_area1); $m++)
			{
				$tour_area_value .= $tour_area1[$m]."/";
			}
			for($k=0; $k<count($dept2); $k++)
			{
				$dept2_value .= $dept2[$k]."/";
			}
			for($k=0; $k<count($dept1); $k++)
			{
				$dept1_value .= $dept1[$k]."/";
			}
			
		    for($i=0; $i<count($weekday); $i++)
			{
				$startWeekValue .= $weekday[$i]."/";
			}
		
		    for($m=0; $m<count($season); $m++)
			{
				$season_value .= $season[$m]."/";
			}
			
			$file_name["p_img1"] = "";
			$file_name["p_img2"] = "";
			$file_name["p_img3"] = "";
			$file_name["p_img4"] = "";
			$file_name["p_img5"] = "";
			$file_name["p_img6"] = "";
			$file_name["p_img7"] = "";
			$file_name["p_img8"] = "";
			$file_name["p_img9"] = "";
			$file_name["p_img10"] = "";
			$file_name["p_mimg"] = "";
		
		

			if ($_FILES["p_img1"]["tmp_name"] <> "")			$file_name["p_img1"] = file_save($_FILES["p_img1"], "./product_img/");
			if ($_FILES["p_img2"]["tmp_name"] <> "")			$file_name["p_img2"] = file_save($_FILES["p_img2"], "./product_img/");
			if ($_FILES["p_img3"]["tmp_name"] <> "")			$file_name["p_img3"] = file_save($_FILES["p_img3"], "./product_img/");
			if ($_FILES["p_img4"]["tmp_name"] <> "")			$file_name["p_img4"] = file_save($_FILES["p_img4"], "./product_img/");
			if ($_FILES["p_img5"]["tmp_name"] <> "")			$file_name["p_img5"] = file_save($_FILES["p_img5"], "./product_img/");
			if ($_FILES["p_img6"]["tmp_name"] <> "")			$file_name["p_img6"] = file_save($_FILES["p_img6"], "./product_img/");
			if ($_FILES["p_img7"]["tmp_name"] <> "")			$file_name["p_img7"] = file_save($_FILES["p_img7"], "./product_img/");
			if ($_FILES["p_img8"]["tmp_name"] <> "")			$file_name["p_img8"] = file_save($_FILES["p_img8"], "./product_img/");
			if ($_FILES["p_img9"]["tmp_name"] <> "")			$file_name["p_img9"] = file_save($_FILES["p_img9"], "./product_img/");
			if ($_FILES["p_img10"]["tmp_name"] <> "")			$file_name["p_img10"] = file_save($_FILES["p_img10"], "./product_img/");
			if ($_FILES["p_mimg"]["tmp_name"] <> "")			$file_name["p_mimg"] = file_save($_FILES["p_mimg"], "./product_img/");

            $p_img1 = $dbConn->real_escape_string($file_name["p_img1"]);
            $p_img2 = $dbConn->real_escape_string($file_name["p_img2"]);
            $p_img3 = $dbConn->real_escape_string($file_name["p_img3"]);
            $p_img4 = $dbConn->real_escape_string($file_name["p_img4"]);
            $p_img5 = $dbConn->real_escape_string($file_name["p_img5"]);
            $p_img6 = $dbConn->real_escape_string($file_name["p_img6"]);
            $p_img7 = $dbConn->real_escape_string($file_name["p_img7"]);
            $p_img8 = $dbConn->real_escape_string($file_name["p_img8"]);
            $p_img9 = $dbConn->real_escape_string($file_name["p_img9"]);
            $p_img10 = $dbConn->real_escape_string($file_name["p_img10"]);
            $p_mimg = $dbConn->real_escape_string($file_name["p_mimg"]);


			$qry1 = "insert into product_master 
								( 
								num,
								m_dept,
								p_dept,
								m_type,
								p_type, 
								base_rate,
								t_code1,
								t_code2,
								c_code1, 
								c_code2, 
								c_code3, 
								c_code4,
								s_cate, 
								tour_area_value,
								tour_area_value0,
								r_rate,
								p_code, 
								p_name, 
								p_own, 
								p_day, 
								p_cnt, 
								p_scnt, 
								price_0dadult, 
								price_0dchild, 
								price_0adult, 
								price_0child, 
								price_0cadult, 
								price_0cchild, 
								oprice_0cadult,
								price_1dadult, 
								price_1dchild, 
								price_1adult, 
								price_1child, 
								price_1cadult, 
								price_1cchild, 
								oprice_1cadult,
								price_2dadult, 
								price_2dchild, 
								price_2adult, 
								price_2child, 
								price_2cadult, 
								price_2cchild,
								oprice_2cadult,
								price_3dadult, 
								price_3dchild, 
								price_3adult, 
								price_3child, 
								price_3cadult, 
								price_3cchild,
								oprice_3cadult,
								price_4dadult, 
								price_4dchild, 
								price_4adult, 
								price_4child, 
								price_4cadult, 
								price_4cchild,
								oprice_4cadult,
								price_5dadult, 
								price_5dchild, 
								price_5adult, 
								price_5child, 
								price_5cadult, 
								price_5cchild,
								oprice_5cadult,
								price_busodadult, 
								price_busodchild, 
								price_busoadult, 
								price_busochild, 
								price_busocadult, 
								price_busocchild,
								oprice_busocadult,
								price_busrdadult, 
								price_busrdchild, 
								price_busradult, 
								price_busrchild, 
								price_busrcadult, 
								price_busrcchild, 
								oprice_busrcadult,
								p_vstart, 
								p_vend, 
								p_week, 
								t_addr,
								p_sdesc, 
								p_mimg, 
								p_img1, 
								p_img2, 
								p_img3, 
								p_img4, 
								p_img5, 
								p_img6, 
								p_img7, 
								p_img8, 
								p_img9, 
								p_img10,
								p_tmap, 
								p_4sdesc, 
								p_include, 
								p_uninclude, 
								p_otrip, 
								p_prepare, 
								p_ref, 
								p_spec, 
								p_alert,
								p_display, 
								p_pay,
								m_guidechk,
								grp,
								bgcolor,
								p_cat_name,
								p_register,
								wdate
								)
								values
								( 
								'$p_num[num]',
								'$dept2_value',
								'$dept1_value',
								'$mty', 
								'$ty', 
								'$currency',
								'$ty1', 
								'$ty2', 
								'$area1', 
								'$area2', 
								'$tripDuration', 
								'$sarea', 
								'$season_value',
								'$tour_area_value',
								'$tour_area_value0',
								'$rrate',
								'$p_code', 
								'".addslashes($prodName)."', 
								'$p_own', 
								'$tourLength', 
								'$maxPerCar', 
								'$minViableNum', 
								'$displayAdultPrice0', 
								'$displayChildPrice0', 
								'$regularAdultPrice0', 
								'$regularChildPrice0', 
								'$partnerAdultPrice0', 
								'$partnerChildPrice0',
								'$OutAdultPrice0',
								'$displayAdultPrice1', 
								'$displayChildPrice1', 
								'$regularAdultPrice1', 
								'$regularChildPrice1', 
								'$partnerAdultPrice1', 
								'$partnerChildPrice1',
								'$OutAdultPrice1',
								'$displayAdultPrice2', 
								'$displayChildPrice2', 
								'$regularAdultPrice2', 
								'$regularChildPrice2', 
								'$partnerAdultPrice2', 
								'$partnerChildPrice2',
								'$OutAdultPrice2',
								'$displayAdultPrice3', 
								'$displayChildPrice3', 
								'$regularAdultPrice3', 
								'$regularChildPrice3', 
								'$partnerAdultPrice3', 
								'$partnerChildPrice3',
								'$OutAdultPrice3',
								'$displayAdultPrice4', 
								'$displayChildPrice4', 
								'$regularAdultPrice4', 
								'$regularChildPrice4', 
								'$partnerAdultPrice4', 
								'$partnerChildPrice4',
								'$OutAdultPrice4',
								'$displayAdultPrice5', 
								'$displayChildPrice5', 
								'$regularAdultPrice5', 
								'$regularChildPrice5', 
								'$partnerAdultPrice5', 
								'$partnerChildPrice5',
								'$OutAdultPrice5',
								'$displayAdultPriceBusOneway', 
								'$displayChildPriceBusOneway', 
								'$regularAdultPriceBusOneway', 
								'$regularChildPriceBusOneway', 
								'$partnerAdultPriceBusOneway', 
								'$partnerChildPriceBusOneway',
								'$OutAdultPriceBusOneway',
								'$displayAdultPriceBusRoundTrip', 
								'$displayChildPriceBusRoundTrip', 
								'$regularAdultPriceBusRoundTrip', 
								'$regularChildPriceBusRoundTrip', 
								'$partnerAdultPriceBusRoundTrip', 
								'$partnerChildPriceBusRoundTrip', 
								'$OutAdultPriceBusRoundTrip', 
								'$startDate1', 
								'$startDate2', 
								'$startWeekValue', 
								'".addslashes($taddr)."',
								'".addslashes($prodDesc)."',
								'" . $p_mimg . "', 
								'" . $p_img1 . "',
								'" . $p_img2 . "',
								'" . $p_img3 . "',
								'" . $p_img4 . "',
								'" . $p_img5 . "',
								'" . $p_img6 . "',
								'" . $p_img7 . "',
								'" . $p_img8 . "',
								'" . $p_img9 . "',
								'" . $p_img10 . "',
								'".addslashes($ptmap)."', 
								'".addslashes($p4desc)."', 
								'".addslashes($pinclude)."', 
								'".addslashes($pninclude)."', 
								'".addslashes($poption)."', 
								'".addslashes($pprepare)."', 
								'".addslashes($pref)."', 
								'".addslashes($pspecial)."',
								'".addslashes($palert)."',
								'".addslashes($exposure)."', 
								'$purchasable',
								'$m_guidechk',
								'$grp',
								'$bgcolor',
								'".addslashes($prod_cat_name)."',
								'$user_dbinfo[userid]',
								now()
								)";
		  //echo $qry1;
		 // exit;
		  $rst1 = $dbConn->query($qry1);

		  if($rst1)
		  {
			   Misc::jvAlert("저장했습니다.","");
			   echo "<meta http-equiv='refresh' content='0;url=./base_product.php?division=2&pdx=$pdx&sub=$sub&ty=$ty'>";	
			   exit;
		  }
		  else
		  {
			   echo "저장실패! 다시시도";
			   exit;
		  }
	 } else if ($pcode != "") {
		 
		    
			for($m=0; $m<count($ty0); $m++)
			{
				$ty_area_value0 .= $ty0[$m]."/";
			}
			//echo $tyy;
			//exit;
			 $qry4 = "DELETE FROM product_pick WHERE p_code = '$pcode' " ;
		     $rst4 = $dbConn->query($qry4);
		     for($k=0; $k<count($pickLoc); $k++)
		     {
			    
				if ($pickLoc[$k] !="") {
					$qry1 = "insert into product_pick 
															( 
															p_code, 
															seq, 
															pick_area, 
															pick_time, 
															wdate
															)
															values
															(
															'$prodCode', 
															'$k', 
															'$pickLoc[$k]', 
															'$pickTime[$k]', 
															now()
															)";
					$rst1 = $dbConn->query($qry1);
				}
				
			}
			$qry4 = "DELETE FROM product_opt WHERE p_code = '$pcode' " ;
		    $rst4 = $dbConn->query($qry4);
			for($k=0; $k<count($optLoc); $k++)
			{
				if ($optLoc[$k] !="") {
					$qry1 = "insert into product_opt 
															( 
															p_code, 
															seq, 
															opt_code, 
															wdate
															)
															values
															(
															'$prodCode', 
															'$k', 
															'$optLoc[$k]', 
															now()
															)";
					$rst1 = $dbConn->query($qry1);
				}
			}
			
			$qry4 = "DELETE FROM product_limit WHERE p_code = '$pcode' && p_type='L' " ;
		    $rst4 = $dbConn->query($qry4);
			for($k=0; $k<count($blockDate); $k++)
			{
				
				if ($blockDate[$k] !="") {
					$qry1 = "insert into product_limit 
															(
															p_code, 
															seq, 
															p_type, 
															p_limitdate, 
															wdate
															)
															values
															(
															'$prodCode', 
															'$k', 
															'L', 
															'$blockDate[$k]', 
															now()
															)";
					$rst1 = $dbConn->query($qry1);
				}
			}
			$qry4 = "DELETE FROM product_limit WHERE p_code = '$pcode' && p_type='R' " ;
		    $rst4 = $dbConn->query($qry4);
			for($k=0; $k<count($reservationDate); $k++)
			{
				
				if ($reservationDate[$k] !="") {
					$qry1 = "insert into product_limit 
															(
															p_code, 
															seq, 
															p_type, 
															p_limitdate, 
															wdate
															)
															values
															(
															'$prodCode', 
															'$k', 
															'R', 
															'$reservationDate[$k]', 
															now()
															)";
					$rst1 = $dbConn->query($qry1);
				}
			}

			for($m=0; $m<count($tour_area0); $m++)
			{
				$tour_area_value0 .= $tour_area0[$m]."/";
			}
			for($m=0; $m<count($tour_area1); $m++)
			{
				$tour_area_value .= $tour_area1[$m]."/";
			}
			for($k=0; $k<count($dept2); $k++)
			{
				$dept2_value .= $dept2[$k]."/";
			}
			for($k=0; $k<count($dept1); $k++)
			{
				$dept1_value .= $dept1[$k]."/";
			}
		    for($i=0; $i<count($weekday); $i++)
			{
				$startWeekValue .= $weekday[$i]."/";
			}
		
		    for($m=0; $m<count($season); $m++)
			{
				$season_value .= $season[$m]."/";
			}

			$file_name["p_img1"] = "";
			$file_name["p_img2"] = "";
			$file_name["p_img3"] = "";
			$file_name["p_img4"] = "";
			$file_name["p_img5"] = "";
			$file_name["p_img6"] = "";
			$file_name["p_img7"] = "";
			$file_name["p_img8"] = "";
			$file_name["p_img9"] = "";
			$file_name["p_img10"] = "";
			$file_name["p_mimg"] = "";
		
		

			if ($_FILES["p_img1"]["tmp_name"] <> "")			$file_name["p_img1"] = file_save($_FILES["p_img1"], "./product_img/");
			if ($_FILES["p_img2"]["tmp_name"] <> "")			$file_name["p_img2"] = file_save($_FILES["p_img2"], "./product_img/");
			if ($_FILES["p_img3"]["tmp_name"] <> "")			$file_name["p_img3"] = file_save($_FILES["p_img3"], "./product_img/");
			if ($_FILES["p_img4"]["tmp_name"] <> "")			$file_name["p_img4"] = file_save($_FILES["p_img4"], "./product_img/");
			if ($_FILES["p_img5"]["tmp_name"] <> "")			$file_name["p_img5"] = file_save($_FILES["p_img5"], "./product_img/");
			if ($_FILES["p_img6"]["tmp_name"] <> "")			$file_name["p_img6"] = file_save($_FILES["p_img6"], "./product_img/");
			if ($_FILES["p_img7"]["tmp_name"] <> "")			$file_name["p_img7"] = file_save($_FILES["p_img7"], "./product_img/");
			if ($_FILES["p_img8"]["tmp_name"] <> "")			$file_name["p_img8"] = file_save($_FILES["p_img8"], "./product_img/");
			if ($_FILES["p_img9"]["tmp_name"] <> "")			$file_name["p_img9"] = file_save($_FILES["p_img9"], "./product_img/");
			if ($_FILES["p_img10"]["tmp_name"] <> "")			$file_name["p_img10"] = file_save($_FILES["p_img10"], "./product_img/");
			if ($_FILES["p_mimg"]["tmp_name"] <> "")			$file_name["p_mimg"] = file_save($_FILES["p_mimg"], "./product_img/");

			$img1_qry = "";
			$img2_qry = "";
			$img3_qry = "";
			$img4_qry = "";
			$img5_qry = "";
			$img6_qry = "";
			$img7_qry = "";
			$img8_qry = "";
			$img9_qry = "";
			$img10_qry = "";
			$imgm_qry = "";
			//print_r($_FILES);


            $p_img1 = $dbConn->real_escape_string($file_name["p_img1"]);
            $p_img2 = $dbConn->real_escape_string($file_name["p_img2"]);
            $p_img3 = $dbConn->real_escape_string($file_name["p_img3"]);
            $p_img4 = $dbConn->real_escape_string($file_name["p_img4"]);
            $p_img5 = $dbConn->real_escape_string($file_name["p_img5"]);
            $p_img6 = $dbConn->real_escape_string($file_name["p_img6"]);
            $p_img7 = $dbConn->real_escape_string($file_name["p_img7"]);
            $p_img8 = $dbConn->real_escape_string($file_name["p_img8"]);
            $p_img9 = $dbConn->real_escape_string($file_name["p_img9"]);
            $p_img10 = $dbConn->real_escape_string($file_name["p_img10"]);
            $p_mimg = $dbConn->real_escape_string($file_name["p_mimg"]);


			if (get_magic_quotes_gpc()) {
				//if ($file_name["p_img1"] <> "" || $photo_del1 == "1") $img1_qry = " p_img1 = '" . mysql_real_escape_string( stripslashes((string)$file_name["p_img1"]) ) . "', ";

                if ($file_name["p_img1"] <> "" || $photo_del1 == "1") $img1_qry = " p_img1 = '" .stripslashes((string)$p_img1). "', ";
				if ($file_name["p_img2"] <> "" || $photo_del2 == "1") $img2_qry = " p_img2 = '" .stripslashes((string)$p_img2). "', ";
				if ($file_name["p_img3"] <> "" || $photo_del3 == "1") $img3_qry = " p_img3 = '" .stripslashes((string)$p_img3). "', ";
				if ($file_name["p_img4"] <> "" || $photo_del4 == "1") $img4_qry = " p_img4 = '" .stripslashes((string)$p_img4). "', ";
				if ($file_name["p_img5"] <> "" || $photo_del5 == "1") $img5_qry = " p_img5 = '" .stripslashes((string)$p_img5). "', ";
				if ($file_name["p_img6"] <> "" || $photo_del6 == "1") $img6_qry = " p_img6 = '" .stripslashes((string)$p_img6). "', ";
				if ($file_name["p_img7"] <> "" || $photo_del7 == "1") $img7_qry = " p_img7 = '" .stripslashes((string)$p_img7). "', ";
				if ($file_name["p_img8"] <> "" || $photo_del8 == "1") $img8_qry = " p_img8 = '" .stripslashes((string)$p_img8). "', ";
				if ($file_name["p_img9"] <> "" || $photo_del9 == "1") $img9_qry = " p_img9 = '" .stripslashes((string)$p_img9). "', ";
				if ($file_name["p_img10"] <> "" || $photo_del10 == "1") $img10_qry = " p_img10 = '" .stripslashes((string)$p_img10). "', ";
				if ($file_name["p_mimg"] <> "" || $photo_delm == "1") $imgm_qry = " p_mimg = '" .stripslashes((string)$p_mimg). "', ";
				
			} else {
				//if ($file_name["p_img1"] <> "" || $photo_del1 == "1") $img1_qry = " p_img1 = '" . mysql_real_escape_string($file_name["p_img1"]) . "', ";

                if ($file_name["p_img1"] <> "" || $photo_del1 == "1") $img1_qry = " p_img1 = '".$p_img1."', ";
				if ($file_name["p_img2"] <> "" || $photo_del2 == "1") $img2_qry = " p_img2 = '".$p_img2."', ";
				if ($file_name["p_img3"] <> "" || $photo_del3 == "1") $img3_qry = " p_img3 = '".$p_img3."', ";
				if ($file_name["p_img4"] <> "" || $photo_del4 == "1") $img4_qry = " p_img4 = '".$p_img4."', ";
				if ($file_name["p_img5"] <> "" || $photo_del5 == "1") $img5_qry = " p_img5 = '".$p_img5."', ";
				if ($file_name["p_img6"] <> "" || $photo_del6 == "1") $img6_qry = " p_img6 = '".$p_img6."', ";
				if ($file_name["p_img7"] <> "" || $photo_del7 == "1") $img7_qry = " p_img7 = '".$p_img7."', ";
				if ($file_name["p_img8"] <> "" || $photo_del8 == "1") $img8_qry = " p_img8 = '".$p_img8."', ";
				if ($file_name["p_img9"] <> "" || $photo_del9 == "1") $img9_qry = " p_img9 = '".$p_img9."', ";
				if ($file_name["p_img10"] <> "" || $photo_del10 == "1") $img10_qry = " p_img10 = '".$p_img10."', ";
				if ($file_name["p_mimg"] <> "" || $photo_delm == "1") $imgm_qry = " p_mimg = '".$p_mimg."', ";
				
			}
			//echo $file_name["p_mimg"]."<br />";
			//echo $imgm_qry;
			//exit;

			$qry1 = "update product_master 
									set
									m_dept = '$dept2_value',
									p_dept = '$dept1_value',
									m_type = '$mty',
									base_rate = '$currency' , 
									t_code1 = '$ty_area_value0' , 
									t_code2 = '$ty2' , 
									c_code1 = '$area1' , 
									c_code2 = '$area2' , 
									c_code3 = '$tripDuration' , 
									c_code4 = '$sarea' , 
									s_cate = '$season_value' , 
									tour_area_value = '$tour_area_value' ,
									tour_area_value0 = '$tour_area_value0' ,
									r_rate = '$rrate',
									p_code = '$prodCode' , 
									p_name = '".addslashes($prodName)."',
									p_own = '$p_own' , 
									p_day = '$tourLength' , 
									p_cnt = '$maxPerCar' , 
									p_scnt = '$minViableNum' , 
									price_0dadult =		'$displayAdultPrice0',           
									price_0dchild =		'$displayChildPrice0',           
									price_0adult =		'$regularAdultPrice0',           
									price_0child =		'$regularChildPrice0',           
									price_0cadult =		'$partnerAdultPrice0',           
									price_0cchild =		'$partnerChildPrice0',
									oprice_0cadult =	'$OutAdultPrice0',
									price_1dadult =		'$displayAdultPrice1',           
									price_1dchild =		'$displayChildPrice1',           
									price_1adult =		'$regularAdultPrice1',           
									price_1child =		'$regularChildPrice1',           
									price_1cadult =		'$partnerAdultPrice1',           
									price_1cchild =		'$partnerChildPrice1',
									oprice_1cadult =	'$OutAdultPrice1',
									price_2dadult =		'$displayAdultPrice2',           
									price_2dchild =		'$displayChildPrice2',           
									price_2adult =		'$regularAdultPrice2',           
									price_2child =		'$regularChildPrice2',           
									price_2cadult =		'$partnerAdultPrice2',           
									price_2cchild =		'$partnerChildPrice2', 
									oprice_2cadult =	'$OutAdultPrice2',
									price_3dadult =		'$displayAdultPrice3',           
									price_3dchild =		'$displayChildPrice3',           
									price_3adult =		'$regularAdultPrice3',           
									price_3child =		'$regularChildPrice3',           
									price_3cadult =		'$partnerAdultPrice3',           
									price_3cchild =		'$partnerChildPrice3',
									oprice_3cadult =	'$OutAdultPrice3',
									price_4dadult =		'$displayAdultPrice4',           
									price_4dchild =		'$displayChildPrice4',           
									price_4adult =		'$regularAdultPrice4',           
									price_4child =		'$regularChildPrice4',           
									price_4cadult =		'$partnerAdultPrice4',           
									price_4cchild =		'$partnerChildPrice4',  
									oprice_4cadult =	'$OutAdultPrice5',
									price_5dadult =		'$displayAdultPrice5',           
									price_5dchild =		'$displayChildPrice5',           
									price_5adult =		'$regularAdultPrice5',           
									price_5child =		'$regularChildPrice5',           
									price_5cadult =		'$partnerAdultPrice5',           
									price_5cchild =		'$partnerChildPrice5',
									oprice_5cadult =	'$OutAdultPrice5',
									price_busodadult =  '$displayAdultPriceBusOneway',    
									price_busodchild =  '$displayChildPriceBusOneway',    
									price_busoadult =   '$regularAdultPriceBusOneway',   
									price_busochild =   '$regularChildPriceBusOneway',   
									price_busocadult =  '$partnerAdultPriceBusOneway',    
									price_busocchild =  '$partnerChildPriceBusOneway',
									oprice_busocadult = '$OutAdultPriceBusOneway', 
									price_busrdadult =  '$displayAdultPriceBusRoundTrip', 
									price_busrdchild =  '$displayChildPriceBusRoundTrip', 
									price_busradult =   '$regularAdultPriceBusRoundTrip',
									price_busrchild =   '$regularChildPriceBusRoundTrip',
									price_busrcadult =  '$partnerAdultPriceBusRoundTrip', 
									price_busrcchild =  '$partnerChildPriceBusRoundTrip', 
									oprice_busrcadult = '$OutAdultPriceBusRoundTrip',
									p_vstart = '$startDate1' , 
									p_vend = '$startDate2' , 
									p_week = '$startWeekValue' , 
									p_sdesc = '".addslashes($prodDesc)."', 
									t_addr = '".addslashes($taddr)."',
									$imgm_qry
									$img1_qry 
									$img2_qry 
									$img3_qry 
									$img4_qry 
									$img5_qry 
									$img6_qry 
									$img7_qry 
									$img8_qry
									$img9_qry
									$img10_qry
									p_tmap = '".addslashes($ptmap)."', 
    								p_4sdesc = '".addslashes($p4desc)."', 
									p_include = '".addslashes($pinclude)."',
									p_uninclude = '".addslashes($pninclude)."',
									p_otrip = '".addslashes($poption)."', 
									p_prepare = '".addslashes($pprepare)."', 
									p_ref = '".addslashes($pref)."',
									p_spec = '".addslashes($pspecial)."',
									p_alert = '".addslashes($palert)."',
									p_display = '$exposure' , 
									p_pay = '$purchasable',
									m_guidechk = '$m_guidechk',
									grp='$grp',
									bgcolor='$bgcolor',
									p_cat_name = '".addslashes($prod_cat_name)."'
									where
									p_code = '$pcode'" ;
 
        
		// echo $qry1;
		 // exit;
		  $rst1 = $dbConn->query($qry1);

		  if($rst1)
		  {
			   Misc::jvAlert("저장했습니다.","");
			   echo "<meta http-equiv='refresh' content='0;url=./base_product.php?division=2&pdx=$pdx&sub=$sub&ty=$ty'>";	
			   exit;
		  }
		  else
		  {
			   echo "저장실패! 다시시도";
			   exit;
		  }
		  
		  Misc::jvAlert("저장했습니다.","");

	 }
    
