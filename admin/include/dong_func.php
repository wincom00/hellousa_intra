<?php
   

   function codebaseName($code){
			
			global $dbConn;
			if (strlen($code) == "8") {
				$lvcode1 = substr($code,0,3);
				$lvcode2 = substr($code,3,2);
				$lvcode3 = substr($code,5,2);
				$lvcode4 = '00';
				
			} elseif (strlen($code) == "9") {
				$lvcode1 = substr($code,0,3);
				$lvcode2 = substr($code,3,2);
				$lvcode3 = substr($code,5,2);
				$lvcode4 = substr($code,7,2);
				
			} else {
				$lvcode1 = substr($code,0,3);
				$lvcode2 = substr($code,3,2);
				$lvcode3 = substr($code,5,2);
				$lvcode4 = '00';
			}

			$qry1 = "select * from code_base where lvcode1 = '$lvcode1' && lvcode2 = '$lvcode2' && lvcode3 = '$lvcode3' && lvcode4='$lvcode4'";
			//print_r($qry1);
            $rst1 = $dbConn->query($qry1);

			$row1 = $rst1->fetch_assoc();
			
			return $row1;
	}
	function pickBaseCode2($code = false) {
			
		global $dbConn;
		$qry1 = "select pick_code,pick_name from base_pick where pick_m = 'M' 
		         union all
				 select h_code as pick_code,h_name as pick_name from product_hotel  ";
		
		$rst1 = $dbConn->query($qry1);
		//echo $qry1;
		while($row1 = $rst1->fetch_assoc()){
			
			
			$selectValueInput = $row1[pick_code];
				
			if($selectValueInput == $code)
			{
				$option.= "<option value=$selectValueInput selected>$row1[pick_name] ";
			} else 
			{
				$option.= "<option value=$selectValueInput >$row1[pick_name]";
			}
			

		}

		return $option;

	}
	//호텔별 정산 행사기간
      function getPeriodbyhotel($p_code,$stDate){

          global $dbConn;

          $query = "SELECT b.p_day FROM reserve_info  a , product_master b 
          WHERE a.p_code = b.p_code  
          AND a.p_code = '$p_code' 
          AND a.stDate='$stDate' 
          AND ( rev_status!='CANCEL' AND rev_status!='WAIT') LIMIT 1";
          $rst1 = $dbConn->query($query);
          $data_row = mysqli_fetch_assoc($rst1);
          $data_row[p_day] = $data_row[p_day]-1;
          $c_day = '+'.$data_row[p_day].' day';
          $period = $stDate."~".date( "Y-m-d", strtotime( "$stDate $c_day" ));

          return $period;

      }

      //호텔별정산 상태가져오기
      function getHotelStStatus($grand_eCode,$sub_eCode,$stDate){

          global $dbConn;
		  $qry1 = "SELECT COUNT(*) cnt FROM hotel_settlesum WHERE grand_eCode = '$grand_eCode' 
              AND sub_eCode = '$sub_eCode' ";
              $rst1 = $dbConn->query($qry1);
              $data_row = mysqli_fetch_assoc($rst1);
           //echo $qry1;
              if($data_row['cnt'] >0) $status = '정산등록';
              else $status = '미등록'; 
          return $status; 

      }
    //호텔별정산 상태가져오기
      function getCarStStatus($grand_eCode,$sub_eCode,$stDate){

          global $dbConn;
		  $qry1 = "SELECT COUNT(*) cnt FROM car_settlesum WHERE grand_eCode = '$grand_eCode' 
              AND sub_eCode = '$sub_eCode' ";
              $rst1 = $dbConn->query($qry1);
              $data_row = mysqli_fetch_assoc($rst1);

              if($data_row['cnt'] >0) $status = '정산등록';
              else $status = '미등록'; 
          return $status; 

      }
	function get_html($id) {
			$qry3 = "select * from html_page where id = '$id'";
			$rst3 = $dbConn->query($qry3);

			$row3 = $rst3->fetch_assoc();
			return $row3;

	}
	//호텔리스트
      function getHotelList(){
          global $dbConn;

          $query = "SELECT * FROM product_hotel WHERE u_type in ('1','2')";
          $rst1 = $dbConn->query($query);
          
          return $rst1;
      }
	//호텔별 정산 기타비용 호텔관련리스트
      function getEtcCostSelect(){
          global $dbConn;

          $query = "SELECT * FROM code_base WHERE lvcode1 = 'E01' and lvcode2 !='00' ";
          $rst1 = $dbConn->query($query);
          
          return $rst1;

      }
	  function getEtcCostSelect3(){
          global $dbConn;

          $query = "SELECT * FROM code_base WHERE lvcode1 = 'E03' and lvcode2 !='00' ";
          $rst1 = $dbConn->query($query);
          
          return $rst1;

      }
    function getConsultInfo($cCode){
		
				global $dbConn;

				$qry1 = "select * from consult_info where consultCode = '$cCode'";
				$rst1 = $dbConn->query($qry1);
				$row1 = $rst1->fetch_assoc();

				return $row1;

	}
	function printBaseCode_hsecond($lvcode1,$lvcode2,$code = false){
			
		global $dbConn;
		$lvcode1 = substr($code,0,3);
		$lvcode2 = substr($code,3,2);
		$lvcode3 = substr($code,3,2);
		$qry1 = "select * from code_base where active='yes' && lvcode1 = '$lvcode1' && lvcode2 = '$lvcode2' && lvcode3 <> '00' &&  lvcode4 = '00' order by lvcode3 asc";
		$rst1 = $dbConn->query($qry1);
		//echo $qry1;
		while($row1 = $rst1->fetch_assoc()){
			
			
			$selectValue = $row1[lvcode1].$row1[lvcode2];
			$selectValueInput = $row1[lvcode1].$row1[lvcode2].$row1[lvcode3];
				
			if($selectValueInput == $code)
			{
				$option.= "<option value=$selectValueInput selected>$row1[comment] ";
			} else 
			{
				$option.= "<option value=$selectValueInput >$row1[comment]";
			}
			

		}

		return $option;

	}
	// 새로운상담 번호 가져오기
	function getConsultNum($code){
		
		global $dbConn;

		$start_date = date('Y-m-d 00:00:01');
		$stop_date = date('Y-m-d 23:59:59');

		$qry1 = "select max(consultNum) from consult_info where consultCode='$consultCode'";
		$rst1 = $dbConn->query($qry1);
		$num1 = $rst1->num_rows;

		if($num1>0)
		{
			$rNum = @mysql_result($rst1,0,0) + 1;

			if(strlen($rNum) == "1")
			{
				$num1 = "00".$rNum;
			}
			elseif(strlen($rNum) == "2")
			{
				$num1 = "0".$rNum;
			}
			else
			{
				$num1 = $rNum;
			}
			
		}
		else
		{
			$num1 = "001";
		}

		return $num1;
	}
	function getRConsultNum($code){
			
			global $dbConn;

			$start_date = date('Y-m-d 00:00:01');
			$stop_date = date('Y-m-d 23:59:59');

			$qry1 = "select max(substr(consultCode,19,3)) as cmax,substr(consultCode,1,17) from consult_info where substr(consultCode,1,17)='$code'";
			$rst1 = $dbConn->query($qry1);
			$row1 = @$rst1->fetch_assoc();

			return $row1;
			
	}
	function printBaseCode_first1($lvcode1,$code = false){
			
		global $dbConn;

		$qry1 = "select * from code_base where lvcode1 = '$lvcode1' && lvcode2 <> '00' && lvcode2 <> '10' && lvcode3 = '00' order by lvcode2 asc";
				
		$rst1 = $dbConn->query($qry1);
		//echo $code;
		while($row1 = $rst1->fetch_assoc()){
			
			$selectValue = $row1[lvcode1].$row1[lvcode2];
			$selectValueInput = $row1[lvcode1].$row1[lvcode2].$row1[lvcode3];
				
			if($selectValueInput == $code)
			{
				$option.= "<option value=$selectValueInput selected>$row1[comment] ";
			} else 
			{
				$option.= "<option value=$selectValueInput >$row1[comment]";
			}
			

		}

		return $option;

	}
	function printBaseCode_first($lvcode1,$code = false){
			
		global $dbConn;

		$qry1 = "select * from code_base where lvcode1 = '$lvcode1' && lvcode2 <> '00' && lvcode3 = '00' order by lvcode2 asc";
				
		$rst1 = $dbConn->query($qry1);
		//echo $code;
		while($row1 = $rst1->fetch_assoc()){
			
			$selectValue = $row1[lvcode1].$row1[lvcode2];
			$selectValueInput = $row1[lvcode1].$row1[lvcode2].$row1[lvcode3];
				
			if($selectValueInput == $code)
			{
				$option.= "<option value=$selectValueInput selected>$row1[comment] ";
			} else 
			{
				$option.= "<option value=$selectValueInput >$row1[comment]";
			}
			

		}

		return $option;

	}
	
	function printBaseCode_second($lvcode1,$lvcode2,$code = false){
			
		global $dbConn;
		$lvcode1 = substr($code,0,3);
		$lvcode2 = substr($code,3,2);
		$qry1 = "select * from code_base where lvcode1 = '$lvcode1' && lvcode2 = '$lvcode2' && lvcode3 <> '00' && lvcode4 = '00' order by lvcode3 asc";
		$rst1 = $dbConn->query($qry1);
		//echo $qry1."TEST";
		///exit;
		while($row1 = $rst1->fetch_assoc()){
			
			$selectValue = $row1[lvcode1].$row1[lvcode2];
			$selectValueInput = $row1[lvcode1].$row1[lvcode2].$row1[lvcode3].$row1[lvcode4];
			//echo 	$selectValueInput."||".$code."<br>";
			if($selectValueInput == $code)
			{
				$option.= "<option value=$selectValueInput selected>$row1[comment] ";
			} else 
			{
				$option.= "<option value=$selectValueInput >$row1[comment]";
			}
			

		}

		return $option;

	}
	function printBaseCode_hotel(){
			
		global $dbConn;
		$lvcode1 = substr($code,0,3);
		$lvcode2 = substr($code,3,2);
		$qry1 = "select * from code_base where lvcode1 = 'T01' && lvcode2 <> '00' && lvcode3 = '00' order by lvcode3 asc";
		$rst1 = $dbConn->query($qry1);
		
		while($row1 = $rst1->fetch_assoc()){
			
			$selectValueInput = $row1[lvcode1].$row1[lvcode2].$row1[lvcode3];
		
			$option.= "<option value=$selectValueInput >$row1[comment]";
	
		}

		return $option;

	}	
	function getHotelfInfo21($h_code){
		
		global $dbConn;

		$qry1 = "select * from product_hotel where h_code='$h_code'";
		$rst1 = $dbConn->query($qry1);
		$row1 = $rst1->fetch_assoc();
		
		return $row1;

	 }
	 function getHotelCnt21($gscode,$pcode,$st,$day){
		
			global $dbConn;

			$qry1 = "select count(*) as cnt ,hotel_code from hotel_assign a where p_code='$pcode' && stDate='$st' && day = '$day' && sub_eCode = '$gscode' group by hotel_code";
			$rst1 = $dbConn->query($qry1);
		
		   while($row1 = $rst1->fetch_assoc()){
				
				$hinfo=getHotelfInfo21($row1[hotel_code]);
				
				$content .= "$hinfo[h_name] : $row1[cnt]개<br>";
						
						
			}
			return $content;

	}
	function getReserveInfoRoom2($pcode,$sdate,$ecode){
		
				global $dbConn;

				$qry1 = "SELECT  SUM(room_cnt) as rcnt1  FROM reserve_info  WHERE p_code = '$pcode' && stDate='$sdate' && (rev_status ='ORDER' || rev_status ='DONE') && reserveCode IN (SELECT DISTINCT reserveCode FROM tour_car WHERE sub_eCode='$ecode' && stDate='$sdate' && p_code='$pcode' )";
				///echo $qry1;
				//exit;
				$rst1 = $dbConn->query($qry1);
				$row1 = $rst1->fetch_assoc();
				
				return $row1;
    }
	 //가이드정산 기초코드
	function getGuideBaseCode($lvcode){
			global $dbConn;

			$query = "SELECT * FROM code_base WHERE lvcode1 ='$lvcode' AND lvcode2 !='00' AND lvcode3 ='00' ";

			$rst1 = $dbConn->query($query);
			
			return $rst1;

	}
	function printBaseCodeCategory($pjCategory = false){
			
			global $dbConn;

			$qry1 = "select * from code_base where lvcode1 = 'C01' && lvcode2 <> '00' && lvcode3 = '00' && lvcode4 = '00' && lvcode5 = '00' order by lvcode1,lvcode2,lvcode3 asc";
			$rst1 = $dbConn->query($qry1);

			while($row1 = $rst1->fetch_assoc()){
				
				$selectValueInput = $row1[lvcode1].$row1[lvcode2].$row1[lvcode3];

				if($pjCategory == $selectValueInput)
				{
					echo "<option value=$selectValueInput selected>$row1[comment]";		
				}
				else
				{
					echo "<option value=$selectValueInput>$row1[comment]";		
				}
					

			}
	}

	function pickBaseCode($code = false){
			
		global $dbConn;

		$qry1 = "select * from base_pick where pick_m = 'M' order by pick_code asc";
		$rst1 = $dbConn->query($qry1);
		
		while($row1 = $rst1->fetch_assoc()){
			
			
			$selectValueInput = $row1[pick_code];
				
			if($selectValueInput == $code)
			{
				$option.= "<option value=$selectValueInput selected>$row1[pick_name] ";
			} else 
			{
				$option.= "<option value=$selectValueInput >$row1[pick_name]";
			}
			

		}

		return $option;

	}
	function pickBaseCodeSencond($pickcode,$picktt){
			
		global $dbConn;

		$qry1 = "select * from base_pick where pick_code='$pickcode' order by pick_time asc";
		$rst1 = $dbConn->query($qry1);
		
		while($row1 = $rst1->fetch_assoc()){
			
			
			$selectValueInput = $row1[pick_time];
				
			if($selectValueInput == $picktt)
			{
				$option.= "<option value='$selectValueInput' selected>$row1[pick_time] ";
			} else 
			{
				$option.= "<option value='$selectValueInput' >$row1[pick_time]";
			}
			

		}

		return $option;

	}

	function getProductMaster($p_code){
		
		global $dbConn;

		$qry1 = "select * from product_master where p_code = '".$p_code."'";
		//print_r($qry1);
		//exit;
		$rst1 = $dbConn->query($qry1);
		$row1 = @$rst1->fetch_assoc();

		return $row1;

	}
	function getProductHMaster($p_code){
		
		global $dbConn;

		$qry1 = "select * from product_hotel where h_code = '".$p_code."' && u_type in ('1','3') ";
		//print_r($qry1);
		//exit;
		$rst1 = $dbConn->query($qry1);
		$row1 = @$rst1->fetch_assoc();

		return $row1;

	}
	function printRandSelect($rand_id = false){
	
	    global $dbConn;

		$qry1 = "select * from member_list where division = 'comp' && del_yn  ='N' && set_pro ='C' order by company_area,kor_name asc";
		$rst1 = $dbConn->query($qry1);

		while($row1 = $rst1->fetch_assoc()){
			
			$company_area = codebaseName($row1[company_area]);

			if($rand_id == $row1[userid])
			{
				$content .= "<option value='$row1[userid]' selected>[$company_area[comment]] $row1[kor_name] ($row1[userid])";
			}
			else
			{
				$content .= "<option value='$row1[userid]'>[$company_area[comment]] $row1[kor_name] ($row1[userid])";
			}
			

		}

		return $content;
	}
	
	function randname($rand_id = false){
	
		global $dbConn;

		$qry1 = "select * from member_list where division = 'comp' && del_yn  ='N'  && userid = '".$rand_id."'  ";
		$rst1 = $dbConn->query($qry1);

		$row1 = $rst1->fetch_assoc();

		return $row1;
	}
	
	/**
	* @ 아이디로 개인정보 뽑아오기
	*/
	function getinfo_dbMember($user_info){
		
		global $dbConn;

		$qry1 = "select * from member_list where userid = '".$user_info."'  && del_yn='n' ";

		//echo $qry1;exit;
		$rst1 = $dbConn->query($qry1);
		$row1 = $rst1->fetch_assoc();
		
		return $row1;

	}
	function boardConfig($table_id){
	
		global $dbConn;

		$qry1 = "select * from hello_board_setup where table_id = '".$table_id."'";
		$rst1 = $dbConn->query($qry1);

		$row1 = $rst1->fetch_assoc();

		return $row1;
	}

	function getinfo_dbHotel_bycode($hcode){
	
		global $dbConn;

		$qry1 = "select * from product_hotel where h_code = '".$hcode."'";
		$rst1 = $dbConn->query($qry1);

		$row1 = $rst1->fetch_assoc();

		return $row1;
	}

	// 상품 번호 가져오기
	function getHnumber(){
		
		global $dbConn;

		$qry1 = "select max(num) from product_hotel";
		//print_r($qry1);
		$rst1 = $dbConn->query($qry1);
		$num1 = $rst1->num_rows;

		if($num1>0)
		{
			$rNum = @dbMysql_result($rst1, 0, 0) + 1;

			if(strlen($rNum) == "1")
			{
				$num1 = "00".$rNum;
			}
			elseif(strlen($rNum) == "2")
			{
				$num1 = "0".$rNum;
			}
			else
			{
				$num1 = $rNum;
			}

			$numInt = $rNum;
		}
		else
		{
			$num1 = "001";

			$numInt = "1";
		}

		$num[num] = $numInt;
		$num[numChar] = $num1;

		return $num;
	}

    function printPickSelect($prodcode ,$prodseq= false,$ty){
	
	    global $dbConn;
		if ($ty !="5") {
			$qry1 = "select a.pick_code,b.seq,a.pick_name,b.pick_time from base_pick a, product_pick b where a.pick_code=b.pick_area && a.pick_time = b.pick_time &&  b.p_code='".$prodcode."'";
		} else {
			$qry1 = "select distinct a.pick_code,b.seq,a.pick_name,b.pick_time from base_pick a, product_pick b where a.pick_code=b.pick_area  &&  b.p_code='".$prodcode."'";
		}
		$rst1 = $dbConn->query($qry1);
		//echo $qry1."<br />";
		//exit;
		while($row1 = $rst1->fetch_assoc()){
			if ($ty !="5") {
				$curcode =$row1[pick_code]."/".$row1[pick_time];
				
				if($prodseq == $curcode)
				{
					$content .= "<option value='$curcode' selected>".$row1[pick_name]." (.".$row1[pick_time].")";
				}
				else
				{
					$content .= "<option value='$curcode' >".$row1[pick_name]." (".$row1[pick_time].")";
				}
			} else {
				$curcode =$row1[pick_code];
				
				if($prodseq == $curcode)
				{
					$content .= "<option value='$curcode' selected>".$row1[pick_name]."";
				}
				else
				{
					$content .= "<option value='$curcode' >".$row1[pick_name]."";
				}


			}
			

		}

		return $content;
	}

	function printBaseCode2_without($lvcode1,$code = false){
			
			global $dbConn;
			
			$qry1 = "select * from code_base where lvcode1 = '$lvcode1' && lvcode2 <> '00' order by lvcode2 asc";
			$rst1 = $dbConn->query($qry1);
			
			while($row1 = $rst1->fetch_assoc()){
				
				$selectValue = $row1[lvcode1].$row1[lvcode2];
				$selectValueInput = $row1[lvcode1].$row1[lvcode2].$row1[lvcode3];
				
				if($selectValueInput == $code)
				{
					$option.= "<option value=$selectValueInput selected>$row1[comment] ";
				} else 
				{
					$option.= "<option value=$selectValueInput >$row1[comment]";
				}
				

			}

			return $option;

		}
		function printBaseCode3_without($lvcode1,$code = false){
			
			global $dbConn;
			$lvcode1 = substr($code,0,3);
			$lvcode2 = substr($code,3,2);
			$qry1 = "select * from code_base where lvcode1 = '$lvcode1' && lvcode2 = '$lvcode2' && lvcode3 <> '00' order by lvcode3 asc";
			$rst1 = $dbConn->query($qry1);
			
			while($row1 = $rst1->fetch_assoc()){
				
				$selectValue = $row1[lvcode1].$row1[lvcode2];
				$selectValueInput = $row1[lvcode1].$row1[lvcode2].$row1[lvcode3];
					
				if($selectValueInput == $code)
				{
					$option.= "<option value=$selectValueInput selected>$row1[comment] ";
				} else 
				{
					$option.= "<option value=$selectValueInput >$row1[comment]";
				}
				

			}

			return $option;

		}

		function printBaseCode4_without($lvcode1,$code = false){
			
			global $dbConn;
			
			$qry1 = "select * from code_base where lvcode1 = '$lvcode1' && lvcode2 <> '00' && lvcode3 <> '00' order by lvcode2 asc";
			$rst1 = $dbConn->query($qry1);
			///echo $qry1;
			//exit;
			while($row1 = $rst1->fetch_assoc()){
				
				$selectValue = $row1[lvcode1].$row1[lvcode2];
				$selectValueInput = $row1[lvcode1].$row1[lvcode2].$row1[lvcode3];
				//echo $code."<br />";	
				if($selectValueInput == $code)
				{
					$option.= "<option value=$selectValueInput selected>$row1[comment] ";
				} else 
				{
					$option.= "<option value=$selectValueInput >$row1[comment]";
				}
				

			}

			return $option;

		}

		function getReserveInfo($rCode){
		
				global $dbConn;

				$qry1 = "select * from reserve_info where reserveCode = '".$rCode."' && parent = 'MAIN' && rev_status!='CANCEL'";
				$rst1 = $dbConn->query($qry1);
				$row1 = @$rst1->fetch_assoc();
				
				return $row1;

	    }
		function getReservePSInfo($rCode,$pcode){
		
				global $dbConn;

				$qry1 = "select * from reserve_info where reserveCode = '".$rCode."' && dis_code = '".$pcode."'";
				$rst1 = $dbConn->query($qry1);
				$row1 = @$rst1->fetch_assoc();
			//echo $qry1;
				return $row1;

	    }
		
		function getReserveHInfo($rCode){
		
				global $dbConn;

				$qry1 = "select * from reserve_hotel where reserveCode = '".$rCode."' ";
				$rst1 = $dbConn->query($qry1);
				$row1 = @$rst1->fetch_assoc();
				
				return $row1;

	    }
		function getReserveInfoGCnt($pcode,$sdate){
		
				global $dbConn;

				$qry1 = "select tour_pcnt as pcnt from reserve_info where p_code = '".$pcode."' && stDate='".$sdate."'";
				$rst1 = $dbConn->query($qry1);
				$row1 = @$rst1->fetch_assoc();
				
				return $row1;

	    }
		function getReserveInfoCnt($pcode,$sdate){
		
				global $dbConn;

				$qry1 = "select sum(p_cnt) as cnt from reserve_info where p_code = '$pcode' && stDate='$sdate' && rev_status!='CANCEL' ";
				//echo $qry1;
				//exit;
				$rst1 = $dbConn->query($qry1);
				$row1 = @$rst1->fetch_assoc();
				
				return $row1;

	    }
		function optBaseCode4($rev,$pcode,$tnm)
		{
				
			global $dbConn;

			$cr_qry1 = "SELECT distinct a.*,b.* ,c.opt_code as copt_code FROM product_opt a, base_opt b LEFT OUTER JOIN reserve_opt c ON b.opt_code=c.opt_code && c.reserveCode='$rev' && c.tnm ='$tnm'  
					 WHERE a.opt_code=b.opt_code &&  a.p_code='$pcode' ORDER BY b.opt_name ASC";
			//echo $cr_qry1."test";
			//exit;									`
			$rst1 = $dbConn->query($cr_qry1);
			
			while($row1 = $rst1->fetch_assoc()){
			
				
				$selectValueInput = $row1['copt_code'];
					
				if($selectValueInput == $row1['opt_code'])
				{
					$option.= "<option value=$row1[opt_code] selected>$row1[opt_name] ";
				} else 
				{
					$option.= "<option value=$row1[opt_code] >$row1[opt_name]";
				}
				

			}

			return $option;

		}

		function getReserveTrIn2($rev,$tnm){
		
				global $dbConn;

				$qry1 = "select   *  from reserve_traveler 
									 where reserveCode = '$rev' order by seqint asc 
									
								";
				
				//exit;
				$rst1 = $dbConn->query($qry1);
				//echo $qry1;
				while($row2 = $rst1->fetch_assoc()){
					//echo $tnm."|".$row1[traveler_nm]."<br/>";
			          if ($row2[traveler_nm] == $tnm) {
						  $content .= "<option value='$row2[traveler_nm]' selected />$row2[traveler_nm]</option>";
			          } else  {
						  $content .= "<option value='$row2[traveler_nm]'>$row2[traveler_nm]</option>";
			          }
					
						
					
					
				}
				
				return $content;

		}
		function getReserveTrIn($rev){
		
				global $dbConn;

				$qry1 = "select   *  from reserve_traveler 
									 where reserveCode = '$rev' order by seqint asc 
									
								";
				
				$rst1 = $dbConn->query($qry1);
				//echo $qry1;
				while($row1 = $rst1->fetch_assoc()){
			
					
						$content .= "<option value='$row1[traveler_nm]'>$row1[traveler_nm]</option>";
					
					
				}
				
				return $content;

		}
		function optBaseCode($code = false){
			
			global $dbConn;

			$qry1 = "select * from base_opt where opt_m = 'M' order by opt_name asc";
			$rst1 = $dbConn->query($qry1);
			//echo $qry1;
			while($row1 = $rst1->fetch_assoc()){
				$selectValueInput = $row1[opt_code];
					
				if($selectValueInput == $code)
				{
					$option.= "<option value=$selectValueInput selected>$row1[opt_name] ";
				} else 
				{
					$option.= "<option value=$selectValueInput >$row1[opt_name]";
				}
				

			}

			return $option;

		}
		function getinfo_dbopt_bycode($id){

			global $dbConn;

			$qry1 = "select * from base_opt where opt_m = 'M' && opt_code='$id'";
			$rst1 = $dbConn->query($qry1);
			$row1 = @$rst1->fetch_assoc();
			
		    return $row1;
	    }
		function getReserveInfoCntG($pcode,$sdate){
		
				global $dbConn;

				$qry1 = "select sum(p_cnt) as cnt from reserve_info where p_code = '".$pcode."' && stDate='".$sdate."' && rev_status ='DONE' ";
				//echo $qry1;
				//exit;
				$rst1 = $dbConn->query($qry1);
				$row1 = @$rst1->fetch_assoc();
				
				return $row1;

	    }
		function getReserveInfoBal($pcode,$sdate){
		
				global $dbConn;

				$qry1 = "select sum(last_bal) as bal from reserve_info where p_code = '$pcode' && stDate='$sdate' && rev_status='DONE' ";
				//echo $qry1;
				//exit;
				$rst1 = $dbConn->query($qry1);
				$row1 = @$rst1->fetch_assoc();
				
				return $row1;

	    }
		function getReserveInfoBalSS($pcode,$sdate){
		
				global $dbConn;

				$qry1 = "select sum(last_bal) as bal from reserve_info where p_code = '".$pcode."' && stDate='".$sdate."' && rev_status='DONE' ";
				//echo $qry1;
				//exit;
				$rst1 = $dbConn->query($qry1);
				$row1 = @$rst1->fetch_assoc();
				
				return $row1;

	    }
		function getReserveInfoSal($pcode,$sdate){
		
				global $dbConn;

				$qry1 = "select sum(last_total) as tot from reserve_info where p_code = '".$pcode."' && stDate='".$sdate."' && rev_status='DONE' ";
				//echo $qry1;
				//exit;
				$rst1 = $dbConn->query($qry1);
				$row1 = @$rst1->fetch_assoc();
				
				return $row1;

	    }
		function getReserveInfoRoom($pcode,$sdate){
		
				global $dbConn;

				$qry1 = "select sum(room_cnt) as rcnt from reserve_info where p_code = '".$pcode."' && stDate='".$sdate."' && rev_status ='DONE' ";
				//echo $qry1;
				//exit;
				$rst1 = $dbConn->query($qry1);
				$row1 = @$rst1->fetch_assoc();
				
				return $row1;

	    }
		function getReserveWaitCnt($pcode,$sdate){
		
				global $dbConn;

				$qry1 = "select sum(p_cnt) as cnt from reserve_info where p_code = '$pcode' && stDate='$sdate' && parent = 'MAIN' && rev_status='WAIT' ";
				$rst1 = $dbConn->query($qry1);
				$row1 = @$rst1->fetch_assoc();
				
				return $row1;

	    }

		function getReserveInfoSCnt($pcode,$sdate){
		
				global $dbConn;

				$qry1 = "select sum(p_cnt) as cnt from reserve_info where p_code = '$pcode' && stDate='$sdate' && p_code not in ('SPICKUP003','SSEND007') && rev_status ='ORDER'";
				$rst1 = $dbConn->query($qry1);
				$row1 = @$rst1->fetch_assoc();
				
				return $row1;

	    }

		function getReserveWaitSCnt($pcode,$sdate){
		
				global $dbConn;

				$qry1 = "select sum(p_cnt) as cnt from reserve_info where p_code = '$pcode' && stDate='$sdate' && p_code not in ('SPICKUP003','SSEND007') && rev_status='WAIT' ";
				//echo $qry1."<br>";
				$rst1 = $dbConn->query($qry1);
				$row1 = @$rst1->fetch_assoc();
				
				return $row1;

	    }
		//가이드정산보고 본행사인원
		function getGuideMainPcnt($p_code,$stDate){
			global $dbConn;

			$query = "SELECT SUM(p_cnt) p_cnt FROM reserve_info WHERE p_code ='$p_code' AND stDate ='$stDate' AND parent ='MAIN'
			AND ( rev_status!='CANCEL' && rev_status!='WAIT') ";

			$rst1 = $dbConn->query($query);
			$data_row = mysqli_fetch_assoc($rst1);
			  
			return $data_row;
		}

		//가이드정산보고 복합행사인원
		function getGuideSubPcnt($p_code,$stDate){
			global $dbConn;

			$query = "SELECT SUM(p_cnt) p_cnt FROM reserve_info WHERE p_code ='$p_code' AND stDate ='$stDate' AND parent ='SUB'
			AND ( rev_status!='CANCEL')";

			$rst1 = $dbConn->query($query);
			$data_row = mysqli_fetch_assoc($rst1);
			  
			return $data_row;
		}
		function getProductPick($p_code){
		
			global $dbConn;

			$qry1 = "select * from product_master where p_code = '".$p_code."' && p_code like '%PICK%'";
			//print_r($qry1);
			//exit;
			$rst1 = $dbConn->query($qry1);
			$row1 = @$rst1->fetch_assoc();

			return $row1;

		}
		function getProductSend($p_code){
			
			global $dbConn;

			$qry1 = "select * from product_master where p_code = '$p_code' && p_code like '%SEND%'";
			//print_r($qry1);
			//exit;
			$rst1 = $dbConn->query($qry1);
			$row1 = @$rst1->fetch_assoc();

			return $row1;

		}
		function getProductPickup($code){
		
			global $dbConn;

			$qry1 = "select * from product_master where p_code like '%PICKUP%'";
			//print_r($qry1);
			//exit;
			$rst1 = $dbConn->query($qry1);
			while($row1 = $rst1->fetch_assoc()){
				
				

				if($code == $row1[p_code])
				{
					$content .= "<option value='$row1[p_code]' selected>$row1[p_name]";
				}
				else
				{
					$content .= "<option value='$row1[p_code]'>$row1[p_name]";
				}
				

			}
			return $content;

		}
		function getProductSending($code){
		
				global $dbConn;

				$qry1 = "select * from product_master where p_code like '%SEND%'";
				//print_r($qry1);
				//exit;
				$rst1 = $dbConn->query($qry1);
				while($row1 = $rst1->fetch_assoc()){
					
					

					if($code == $row1[p_code])
					{
						$content .= "<option value='$row1[p_code]' selected>$row1[p_name]";
					}
					else
					{
						$content .= "<option value='$row1[p_code]'>$row1[p_name]";
					}
					

				}
				return $content;

		}
		function getPicGr6($scode) {

				global $dbConn;

				$qry1 = "select pick_area from reserve_traveler 
				where reserveCode = '$scode' && seqint=0 limit 1";
				//echo $qry1;
				$rst1 = $dbConn->query($qry1);
				while($row1 = $rst1->fetch_assoc()){
			
					    $pickarr = explode("/",$row1[pick_area]);
						$picknm=pickBaseInfo($pickarr[0],$pickarr[1]);
						if ($picknm[pick_time] =="") {
							$picknm=pickBaseInfo2($pickarr[0]);
							$content = "$picknm[pick_code]";
						} else {
							$content = "$picknm[pick_code]-$picknm[pick_time]";
						}
					
					
				}


				return $content;

	   }
		function getTourInfo2($pcode,$st){
		
			global $dbConn;

			$qry1 = "select b.* from tour_master b where  b.p_code='$pcode'
			&& b.stDate ='$st'";
			//echo $qry1;
			//exit;
			$rst1 = $dbConn->query($qry1);
			$row1 = @$rst1->fetch_assoc();
			
			return $row1;

		}
        function getPicGr5($pcode,$st) {

				global $dbConn;

				$qry1 = "select count(b.pick_area) cnt,b.pick_area from reserve_info a,reserve_traveler b 
				where a.reserveCode=b.reserveCode && a.p_code = '$pcode' && a.stDate ='$st' && a.rev_status not in ('CANCEL') group by b.pick_area";		//echo $qry1;
				$rst1 = $dbConn->query($qry1);

				while($row1 = $rst1->fetch_assoc()){
			
					    $pickarr = explode("/",$row1[pick_area]);
						$picknm=pickBaseInfo($pickarr[0],$pickarr[1]);
						if ($picknm[pick_name] != "") {
						$content .= "$picknm[pick_name]-$picknm[pick_time] : $row1[cnt]인&nbsp;&nbsp;";
						}
					    
					
					
				}


				return $content;

	    }
		function getPicGr8($pcode,$st) {

				global $dbConn;

				$qry1 = "select count(b.pick_area) cnt,a.meet_area,b.pick_area from reserve_info a,reserve_traveler b 
				where a.reserveCode=b.reserveCode && a.p_code = '$pcode' && a.stDate ='$st' && a.rev_status not in ('CANCEL') group by b.pick_area";	//	echo $qry1;
				$rst1 = $dbConn->query($qry1);

				while($row1 = $rst1->fetch_assoc()){
			
					   
						
						$content .= "$row1[meet_area] : $row1[cnt]인&nbsp;&nbsp;&nbsp;";
						
					    
					
					
				}


				return $content;

	    }
		function getbusInfo($pcode,$stdate,$rcode) {

				global $dbConn;

				$qry1 = "select * from tour_car 
									 where  sub_eCode='$pcode' && reserveCode='$rcode' && stDate='$stdate'";
			    ///echo $qry1."<br />";
				$rst1 = $dbConn->query($qry1);
				$row1 = $rst1->fetch_assoc();
               /*
				while($row1 = $rst1->fetch_assoc()){
                     $g_dbinfo = getinfo_dbMemberg($row1['guide_id']);
					 $rstmsg .= $g_dbinfo[kor_name]."/";

				}
				return $rstmsg;
				*/
				return $row1;


	    }

		function getbusInfo2($pcode,$stdate,$rcode) {

				global $dbConn;

				$qry1 = "select DISTINCT grand_eCode,sub_eCode,bus_num from tour_car 
									 where  p_code='$pcode' && reserveCode='$rcode' && stDate='$stdate'";
			    //echo $qry1."<br />";
				$rst1 = $dbConn->query($qry1);
				$row1 = $rst1->fetch_assoc();
               /*
				while($row1 = $rst1->fetch_assoc()){
                     $g_dbinfo1 = getguideInfor($row1[grand_eCode],$row1[sub_eCode],$row1[bus_num]);
					 $g_dbinfo = getinfo_dbMemberg($g_dbinfo1[guide_id]);
					 $g_dbinfo2 = getinfo_dbMemberg($g_dbinfo1[sguide_id]);
					 $rstmsg .= $g_dbinfo[kor_name]."@".$g_dbinfo2[kor_name];
					 ;

				}
				return $rstmsg;
				*/
				return $row1;


	    }
		function getbusInfo5($rcode,$busnum=1) {

				global $dbConn;

				$qry1 = "SELECT DISTINCT
									b.grand_eCode,
									b.sub_eCode,
									a.p_code,
									b.stDate,
									a.p_name,c.guide_id 
								FROM
									reserve_info AS a  
								INNER JOIN
									tour_car AS b ON a.p_code = b.p_code AND a.stDate = b.stDate 
								INNER JOIN
									tour_guide AS c ON b.grand_eCode = c.grand_eCode AND b.sub_eCode = c.sub_eCode 
								WHERE
									a.reserveCode = '".$rcode."' && b.bus_num = c.bus_num && b.bus_num='".$busnum."' order by b.bus_num,b.stDate asc";
			    
				
				$rst1 = mysqli_query($dbConn,$qry1);
				$php_total_count = mysqli_num_rows($rst1);
				//echo $php_total_count."<br />";
				//$row1 = $rst1->fetch_assoc();
                $rstmsg ="";
				$i=0;
				while($row1 = mysqli_fetch_assoc($rst1)){
					 $i++;
					 //var_dump($row1);
					 $g_dbinfo = getinfo_dbMemberg(trim($row1['guide_id']));
					 $rstmsg .= $g_dbinfo[kor_name]."/";
					 

				}
			    //echo $rstmsg."<br />";
				//exit;
				return $rstmsg;
				


	    }
		function printCompanySelect($rand_id){
	
			    global $dbConn;

				$qry1 = "select * from member_list where division = 'comp' && del_yn  ='N' order by company_area,kor_name asc";
				$rst1 = $dbConn->query($qry1);

				while($row1 = $rst1->fetch_assoc()){
					
					$company_area = codebaseName($row1[company_area]);

					if($rand_id == $row1[userid])
					{
						$content .= "<option value='$row1[userid]' selected>[$company_area[comment]] $row1[kor_name] ($row1[userid])";
					}
					else
					{
						$content .= "<option value='$row1[userid]'>[$company_area[comment]] $row1[kor_name] ($row1[userid])";
					}
					

				}

			    return $content;
		}

		function printCompanySelect1($rand_id){
	
			    global $dbConn;

				$qry1 = "select * from member_list where division = 'comp' && del_yn  ='N'  order by company_area,kor_name asc";
				$rst1 = $dbConn->query($qry1);

				while($row1 = $rst1->fetch_assoc()){
					
					$company_area = codebaseName($row1[company_area]);

					if($rand_id == $row1[userid])
					{
						$content .= "<option value='$row1[userid]' selected>[$company_area[comment]] $row1[kor_name] ($row1[userid])";
					}
					else
					{
						$content .= "<option value='$row1[userid]'>[$company_area[comment]] $row1[kor_name] ($row1[userid])";
					}
					

				}

			    return $content;
		}
	// 예약 최근 번호 가져오기
	function getNumReserve_total(){
		
		global $dbConn;

		$start_date = date('Y-m-d 00:00:01');
		$stop_date = date('Y-m-d 23:59:59');

		$qry1 = "select max(grandNum) as cnt from grand_reserve where wdate between '$start_date' and '$stop_date'";
		//print_r($qry1);
		$rst1 = $dbConn->query($qry1);
		$num1 = $rst1->num_rows;

		if($num1>0)
		{
			$row1 = $rst1->fetch_assoc();
			$rNum = $row1[cnt]+1;
			if(strlen($rNum) == "1")
			{
				$num1 = "00".$rNum;
			}
			elseif(strlen($rNum) == "2")
			{
				$num1 = "0".$rNum;
			}
			else
			{
				$num1 = $rNum;
			}
		}
		else
		{
			$num1 = 1;
		}
		
		return $num1;
	}
   // 예약 최근 번호 가져오기
   function getNumReserve(){
		
		global $dbConn;

		$start_date = date('Y-m-d 00:00:01');
		$stop_date = date('Y-m-d 23:59:59');

		$qry1 = "select max(reserveNum) from reserve_info where wdate between '$start_date' and '$stop_date'";
		//print_r($qry1);
		$rst1 = $dbConn->query($qry1);
		$num1 = $rst1->num_rows;

		if($num1>0)
		{
			$rNum = @dbMysql_result($rst1, 0, 0) + 1;

			if(strlen($rNum) == "1")
			{
				$num1 = "00".$rNum;
			}
			elseif(strlen($rNum) == "2")
			{
				$num1 = "0".$rNum;
			}
			else
			{
				$num1 = $rNum;
			}
		}
		else
		{
			$num1 = 1;
		}

		return $num1;
	}

	// 예약 최근 번호 가져오기
	function getNumHReserve(){
		
		global $dbConn;

		$start_date = date('Y-m-d 00:00:01');
		$stop_date = date('Y-m-d 23:59:59');

		$qry1 = "select max(reserveNum) from reserve_hotel where wdate between '$start_date' and '$stop_date'";
		//print_r($qry1);
		$rst1 = $dbConn->query($qry1);
		$num1 = $rst1->num_rows;

		if($num1>0)
		{
			$rNum = @dbMysql_result($rst1, 0, 0) + 1;

			if(strlen($rNum) == "1")
			{
				$num1 = "00".$rNum;
			}
			elseif(strlen($rNum) == "2")
			{
				$num1 = "0".$rNum;
			}
			else
			{
				$num1 = $rNum;
			}
		}
		else
		{
			$num1 = "001";
		}

		return $num1;
	}
	
	// 예약 최근 번호 가져오기
	function getNumReserve_ctotal(){
		
		global $dbConn;

		$start_date = date('Y-m-d 00:00:01');
		$stop_date = date('Y-m-d 23:59:59');

		$qry1 = "select max(grandNum) from grand_reserve where 1=1";
		
		$rst1 = $dbConn->query($qry1);
		$num1 = $rst1->num_rows;

		if($num1>0)
		{
			$rNum = @dbMysql_result($rst1, 0, 0);

		}

		return $rNum;
	}

	// 예약 최근 번호 가져오기//토탈행사번호
    function getNumTevent(){
		
		global $dbConn;

		$start_date = date('Y-m-d 00:00:01');
		$stop_date = date('Y-m-d 23:59:59');

		$qry1 = "select max(grand_eNum) from tour_master where wdate between '$start_date' and '$stop_date'";
		//print_r($qry1);
		$rst1 = $dbConn->query($qry1);
		$num1 = $rst1->num_rows;

		if($num1>0)
		{
			$rNum = @dbMysql_result($rst1, 0, 0) + 1;

			if(strlen($rNum) == "1")
			{
				$num1 = "00".$rNum;
			}
			elseif(strlen($rNum) == "2")
			{
				$num1 = "0".$rNum;
			}
			else
			{
				$num1 = $rNum;
			}
		}
		else
		{
			$num1 = 1;
		}

		return $num1;
	}

	// 예약 최근 번호 가져오기//서브행사번호
    function getNumSevent($gcode,$st){
		
		global $dbConn;

		$start_date = $st;
		$stop_date =  $st	;

		$qry1 = "select max(sub_eNum) from tour_car where grand_eCode='$gcode' && stDate between '$start_date' and '$stop_date'";
		//print_r($qry1);
		///exit;
		$rst1 = $dbConn->query($qry1);
		$num1 = $rst1->num_rows;

		if($num1>0)
		{
			$rNum = @dbMysql_result($rst1, 0, 0) + 1;

			if(strlen($rNum) == "1")
			{
				$num1 = "00".$rNum;
			}
			elseif(strlen($rNum) == "2")
			{
				$num1 = "0".$rNum;
			}
			else
			{
				$num1 = $rNum;
			}
		}
		else
		{
			$num1 = 1;
		}

		return $num1;
	}
	function getCRandInfo($rCode){
		
				global $dbConn;

				$qry2 = "SELECT * FROM  rand_company WHERE reserveCode='$rCode' && money_type='credit' && p_memo !='발권'";
				//echo $qry2;
		        $rst2 = $dbConn->query($qry2);
				$row1 = @$rst2->fetch_assoc();
				
				return $row1;

	}
	function getCRMandInfo($rCode){
		
				global $dbConn;

				$qry2 = "SELECT * FROM  rand_company WHERE reserveCode='$rCode' && money_type='credit' && base_rate='cmo' && p_memo !='발권'";
				//echo $qry2;
		        $rst2 = $dbConn->query($qry2);
				$row1 = @$rst2->fetch_assoc();
				
				return $row1;

	}
	function getDRandInfo($rCode){
		
				global $dbConn;

				$qry2 = "SELECT * FROM  rand_company WHERE reserveCode='$rCode' && money_type='debit' && p_memo !='발권'";
				//echo $qry2;
		        $rst2 = $dbConn->query($qry2);
				$row1 = @$rst2->fetch_assoc();
				
				return $row1;

	}
	function getDtmRandInfo($rCode){
		
				global $dbConn;

				$qry2 = "SELECT * FROM  rand_company_tmp WHERE part_id !='' && reserveCode='$rCode' && money_type='debit' && p_memo !='발권'";
				//echo $qry2;
		        $rst2 = $dbConn->query($qry2);
				$row1 = @$rst2->fetch_assoc();
				
				return $row1;

	}
	function getAirtmRandInfo($rCode){
		
				global $dbConn;

				$qry2 = "SELECT * FROM  rand_company_tmp WHERE reserveCode='$rCode' && p_memo ='발권'";
				
		        $rst2 = $dbConn->query($qry2);
				$row1 = @$rst2->fetch_assoc();
				
				return $row1;

	}
	function getCTRandInfo($rCode){
		
				global $dbConn;

				$qry2 = "SELECT * FROM  rand_company_tmp WHERE reserveCode='$rCode' && money_type='credit' && p_memo !='발권' && base_rate != 'cmo'";
		        $rst2 = $dbConn->query($qry2);
				$row1 = @$rst2->fetch_assoc();
				
				return $row1;

	}
	function getCMRandInfo($rCode){
		
				global $dbConn;

				$qry2 = "SELECT * FROM  rand_company_tmp WHERE reserveCode='$rCode' && money_type='credit' && base_rate='cmo' && p_memo !='발권'";
		        $rst2 = $dbConn->query($qry2);
				$row1 = @$rst2->fetch_assoc();
				
				return $row1;

	}
	function getDTRandInfo($rCode){
		
				global $dbConn;

				$qry2 = "SELECT * FROM  rand_company_tmp WHERE reserveCode='$rCode' && money_type='debit' && p_memo !='발권'";
		        $rst2 = $dbConn->query($qry2);
				$row1 = @$rst2->fetch_assoc();
				
				return $row1;

	}
	function getRandInfo($seq){
		
				global $dbConn;

				$qry2 = "SELECT * FROM  rand_company WHERE seq_no='$seq'";
		        $rst2 = $dbConn->query($qry2);
				$row1 = @$rst2->fetch_assoc();
				//echo $qry1;
				return $row1;

	}

	function getRandInfoAIr($rev,$part){
		
				global $dbConn;

				$qry2 = "SELECT * FROM  rand_company WHERE reserveCode='$rev' && part_id='$part'";
		        $rst2 = $dbConn->query($qry2);
				//$row1 = @$rst2->fetch_assoc();
				//echo $qry1;
				return $rst2;

	}


	function getPaymethod($reserveCode){
		
		global $dbConn;

		
		$qry1 = "select* from payment_history where reserveCode = '$reserveCode' && payment_status='DONE' limit 1";
		$rst1 = $dbConn->query($qry1);
		$row1 = $rst1->fetch_assoc();
		
		
		return $row1;
	}
	function getPayment($reserveCode){
		
		global $dbConn;

		
		$qry1 = "select sum(payment) as pay1 from payment_history where reserveCode = '$reserveCode' && payment_status='DONE' && pay_method not in ('init')";
		$rst1 = $dbConn->query($qry1);
		$row1 = $rst1->fetch_assoc();

		
		
		return $row1;
	}
	function getPayment2($reserveCode){
		
		global $dbConn;

		
		$qry1 = "select sum(payment) as pay1 from payment_history where reserveCode = '$reserveCode' && payment_status='DONE' && pay_method not in ('init')";
		$rst1 = $dbConn->query($qry1);
		$row1 = $rst1->fetch_assoc();

		$qry2 = "select sum(payment) as pay2 from payment_history where reserveCode = '$reserveCode' && payment_status='RETURN' && pay_method not in ('init')";
		$rst2 = $dbConn->query($qry2);
		$row2 = $rst2->fetch_assoc();
		
		$pay = $row1[pay1]-$row2[pay2];
		
		return $pay;
	}
	function getRPaymethod($reserveCode){
		
		global $dbConn;

		
		$qry1 = "select* from payment_history where reserveCode = '$reserveCode' && (payment_status='RRQUEST' || payment_status='RETURN') limit 1";
		$rst1 = $dbConn->query($qry1);
		
		$row1 = $rst1->fetch_assoc();
		
		
		return $row1;
	}
    
	function printHotelList($h_code = false){
		
		global $dbConn;

		$qry1 = "select * from product_hotel order by h_code asc";
		$rst1 = $dbConn->query($qry1);

		while($row1 = $rst1->fetch_assoc()){
			
			if($h_code == $row1[h_code])
			{
				$content .= "<option value=$row1[h_code] selected>$row1[h_name]";
			}
			else
			{
				$content .= "<option value=$row1[p_code]>$row1[h_name]";
			}
			
		}

		return $content;
	}
	function getReserveTrPic($rev){
		
				global $dbConn;

				$qry1 = "select   count(*) cnt from reserve_traveler 
									 where reserveCode = '$rev' 
									
								";
				$rst1 = $dbConn->query($qry1);
				
				$row1 = @$rst1->fetch_assoc();
				
				return $row1;

	}
	function getReserveCnt($pcode,$sdate){
		
				global $dbConn;

				$qry1 = "select   c.reserveCode from reserve_info a,product_master b,reserve_traveler c
									 where a.p_code=b.p_code && a.reserveCode = c.reserveCode 
									 && a.stDate = '$sdate' && a.p_code = '$pcode' group by c.reserveCode
								";
				$rst1 = $dbConn->query($qry1);
				$num1 = $rst1->num_rows;
				$row1 = @$rst1->fetch_assoc();
				
				return $num1;

	}

	function getReserveTr($rev){
		
				global $dbConn;

				$qry1 = "select   count(*) cnt from reserve_traveler 
									 where reserveCode = '$rev' 
									
								";
				$rst1 = $dbConn->query($qry1);
				
				$row1 = @$rst1->fetch_assoc();
				
				return $row1;

	}
	function getReserveTrRepre($rev){
		
				global $dbConn;

				$qry1 = "select   *  from reserve_traveler 
									 where reserveCode = '$rev' && seqint = '0'
									
								";
				$rst1 = $dbConn->query($qry1);
				
				$row1 = $rst1->fetch_assoc();
				
				return $row1;

	}
	function getReserveHRepre($rev){
		
				global $dbConn;

				$qry1 = "select r_kname  from reserve_hotel 
									 where reserveCode = '$rev' 
									
								";
				//echo $qry1;
				$rst1 = $dbConn->query($qry1);
				
				$row1 = @$rst1->fetch_assoc();
				
				return $row1;

	}
	function getReserveTrRepre2($rev,$cname=""){
		
				global $dbConn;
				
					$qrycname= " && (traveler_nm like '"."%".$cname."%"."')";

				$qry1 = "select   *  from reserve_traveler 
									 where reserveCode = '$rev' $qrycname
									
								";
				$rst1 = $dbConn->query($qry1);
				
				$row1 = $rst1->fetch_assoc();
				
				return $row1;

	}
	function getReserveSum($rev){
		
				global $dbConn;

				$qry1 = "select   sum(dis_pay) amt from reserve_traveler 
									 where reserveCode = '$rev' 
									
								";
				$rst1 = $dbConn->query($qry1);
				
				$row1 = @$rst1->fetch_assoc();
				
				return $row1;

	}
	function getAirlineData($estimateCode){
		
			global $dbConn;

			$qry1 = "select * from reserve_airline_pnr where reserveCode = '$estimateCode'  order by seqm asc";
			$rst1 = $dbConn->query($qry1);
			$row1 = $rst1->fetch_assoc();
		//echo $qry1;
			return $row1;

	   }//getRandSum
	function getAirlineSum($estimateCode){
		
			global $dbConn;

			$qry1 = "select sum(a_amt) as samt from reserve_airline_pnr where reserveCode = '$estimateCode' group by reserveCode ";
			$rst1 = $dbConn->query($qry1);
			$row1 = $rst1->fetch_assoc();
		//echo $qry1;
			return $row1;

	   }//getRandSum
	function getAirProfit($estimateCode){
		
			global $dbConn;

			$qry1 = "select sum(a_airline_amt) as pamt from reserve_airline_pnr where reserveCode = '$estimateCode' group by reserveCode ";
			$rst1 = $dbConn->query($qry1);
			$row1 = $rst1->fetch_assoc();
		//echo $qry1;
			return $row1;

	   }//getRandSum
	function getAirInfo($estimateCode,$pnrnum='',$startDate='',$endDate='',$seldate='',$tknum=''){
		
			global $dbConn;
			
			if ($seldate == '1') {
				if ($startDate) {
				$qrysdate = " && ((  a_airline_print >= '$startDate' && a_airline_print <= '$endDate' )) ";
						
				} 
			} else {

				$qrysdate ="";
			}
			
			if ($pnrnum) {
					$qrypnr = " && a_pnr_number='$pnrnum'";

			} else {
				    $qrypnr ="";
			}

			if ($tknum) {
					$qrytk = " && c.a_tk_number='$tknum'";

			} else {
				    $qrytk ="";
			}
			$qry1 = "select * from reserve_airline_pnr where reserveCode = '$estimateCode' $qrysdate $qrypnr $qrytk limit 1 ";
			//echo $qry1;
			//exit;
			$rst1 = $dbConn->query($qry1);
			$row1 = $rst1->fetch_assoc(); 
	
			return $row1;

	   }//getAirInfo

	function getRandSum($estimateCode){
		
			global $dbConn;

			$qry1 = "select sum(amt) as amt from rand_company_tmp where reserveCode = '$estimateCode' && money_type='debit' && p_memo != '발권' group by reserveCode ";
			$rst1 = $dbConn->query($qry1);
			$row1 = $rst1->fetch_assoc();
		//echo $qry1;
			return $row1;

	 }

	 function getARandSum($estimateCode){
		
			global $dbConn;

			$qry1 = "select sum(amt) as amt from rand_company_tmp where reserveCode = '$estimateCode' && money_type='debit' && p_memo = '발권'  group by reserveCode ";
			$rst1 = $dbConn->query($qry1);
			$row1 = $rst1->fetch_assoc();
		//echo $qry1;
			return $row1;

	 }

	 function getAgeSum($estimateCode){
		
			global $dbConn;

			$qry1 = "select sum(amt) as amt from rand_company_tmp where reserveCode = '$estimateCode' && money_type='credit'  group by reserveCode ";
			$rst1 = $dbConn->query($qry1);
			$row1 = $rst1->fetch_assoc();
		//echo $qry1;
			return $row1;

	 }
	function getCSum($estimateCode){
		
			global $dbConn;

			$qry1 = "select sum(amt) as amt from rand_company_tmp where reserveCode = '$estimateCode' && money_type='credit' && base_rate='cmo' group by reserveCode ";
			$rst1 = $dbConn->query($qry1);
			$row1 = $rst1->fetch_assoc();
		//echo $qry1;
			return $row1;

	 }
	function pickBaseInfo($pickcode,$picktt){
			
		global $dbConn;

		$qry1 = "select * from base_pick where pick_code='$pickcode' && pick_time='$picktt'";
		$rst1 = $dbConn->query($qry1);
				
		$row1 = $rst1->fetch_assoc();
		
		
		return $row1;

	}
    function pickBaseInfo2($pickcode){
			
		global $dbConn;

		$qry1 = "select * from base_pick where pick_code='$pickcode' ";
		$rst1 = $dbConn->query($qry1);
				
		$row1 = $rst1->fetch_assoc();
		
		
		return $row1;

	}
	function getRoomTr($rev,$nm){
		
				global $dbConn;

				$qry1 = "select  * from hotelroom_assign 
									 where reserveCode = '$rev' && tnm ='$nm'
									
								";
				//echo $qry1 ."<br />";
				$rst1 = $dbConn->query($qry1);
				
				$row1 = @$rst1->fetch_assoc();
				
				return $row1;

	}

    function getReserveInfoCntS($pcode,$sdate){
	
			global $dbConn;

			$qry1 = "select sum(p_cnt) as cnt from reserve_info where p_code = '$pcode' && stDate='$sdate' && ( rev_status!='CANCEL') && rev_status='DONE'";
			//echo $qry1."<br>";
			//exit;
			$rst1 = $dbConn->query($qry1);
				
			$row1 = @$rst1->fetch_assoc();
			
			return $row1;

	}

	function getReserveRoomCnt($pcode,$sdate){
		
				global $dbConn;

				/*$qry1 = "select   room_num from hotelroom_assign
							where stDate = '$sdate' && p_code = '$pcode' && room_num <> '99' 
							&& tnm not in (select rev_nm from tour_car where stDate = '$sdate' && p_code = '$pcode')
							group by room_num
								";
								*/
				$qry1 = "select sum(room_cnt) as rcnt from reserve_info where stDate = '$sdate' && p_code = '$pcode' && rev_status!='CANCEL'";
				$rst1 = $dbConn->query($qry1);
				$num1 = $rst1->num_rows;
				$row1 = $rst1->fetch_assoc();
				
				return $row1;

	}
	function getReserveRoomCnt1($pcode,$sdate){
		
				global $dbConn;

				$qry1 = "select   room_num from hotelroom_assign
							where stDate = '$sdate' && p_code = '$pcode' && room_num <> '99' 
							&& tnm not in (select rev_nm from tour_car where stDate = '$sdate' && p_code = '$pcode')
							group by room_num
								";
								
				
				//echo $qry1."<br >";
				$rst1 = $dbConn->query($qry1);
				$num1 = $rst1->num_rows;
				$row1 = @$rst1->fetch_assoc();
				
				return $num1;

	}
	function getbusass($gcode) {

				global $dbConn;

				$qry1 = "select   count(*) cnt from tour_car 
									 where  grand_eCode='$gcode' && p_code not like '%ADD%'";
				$rst1 = $dbConn->query($qry1);
				
				$row1 = @$rst1->fetch_assoc();
				
				return $row1;


	}
	function getbusperson($r,$gcode,$st=false ,$pcode='') {

				global $dbConn;
				if ($st!=false) {
					$stqry = "&& stDate='$st'";
				} else  {
					$stqry = "";
				}
				$qry1 = "select   count(*) cnt from tour_car 
									 where bus_num ='$r' $stqry && grand_eCode='$gcode' && p_code = '$pcode'";
				$rst1 = $dbConn->query($qry1);
				//echo $qry1."<br />";
				$row1 = @$rst1->fetch_assoc();
				
				return $row1;


	}

	function getbusRoom($r,$gcode,$st=false,$pcode) {

				global $dbConn;
				if ($st!=false) {
					$stqry = "&& stDate='$st'";
				} else  {
					$stqry = "";
				}
				$qry1 = "select  romm_num from tour_car 
                            where bus_num ='$r' $stqry && grand_eCode='$gcode' && p_code ='$pcode' group by romm_num";
				//echo $qry1."<br />";
				$rst1 = $dbConn->query($qry1);
				$num1 = $rst1->num_rows;
				$row1 = @$rst1->fetch_assoc();
				
				return $num1;

	}
	function getBusRoomCount($busNum, $grandCode, $stDate, $pCode) {
		global $dbConn;
		$sql = "
			SELECT 
		    SUM(DISTINCT b.room_cnt) AS cnt     
			FROM tour_car a
			JOIN reserve_info b ON a.reserveCode = b.reserveCode AND a.p_code = b.p_code
			WHERE a.bus_num = '$busNum'
			  AND a.stDate = '$stDate'
			  AND a.grand_eCode = '$grandCode'
			  AND a.p_code = '$pCode'
			  ;
		";
		//echo $sql;
		$rst1 = $dbConn->query($sql);
		$num1 = $rst1->num_rows;
		$row1 = @$rst1->fetch_assoc();
		
		return $row1;
	}

	function getbusMemo($gcode,$gscode) {

				global $dbConn;

				$qry1 = "select  distinct h_memo from tour_car 
                            where grand_eCode='$gcode' && sub_eCode ='$gscode'";
				//echo $qry1."<br />";
				$rst1 = $dbConn->query($qry1);
				$num1 = $rst1->num_rows;
				$row1 = @$rst1->fetch_assoc();
				
				return $row1;

	}

	function getBusCnt($gcode,$pcode,$st) {

				global $dbConn;

				$qry1 = "select bus_num from tour_car 
                            where grand_eCode='$gcode' && p_code ='$pcode' AND stDate='$st' group by bus_num";
				
				$rst1 = $dbConn->query($qry1);
				$num1 = $rst1->num_rows;
				$row1 = @$rst1->fetch_assoc();
				
				return $num1;

	}
	function getguideass($gcode) {

				global $dbConn;

				$qry1 = "select   count(*) cnt from tour_guide 
									 where  grand_eCode='$gcode' && p_code not like '%ADD%'";
				$rst1 = $dbConn->query($qry1);
				
				$row1 = @$rst1->fetch_assoc();
				
				return $row1;


	}
	function gethotelass($gcode,$scode) {

				global $dbConn;

				$qry1 = "select   count(*) cnt from hotel_assign 
									 where  grand_eCode='$gcode' && sub_eCode='$scode' ";
				$rst1 = $dbConn->query($qry1);
				
				$row1 = @$rst1->fetch_assoc();
				
				return $row1;


	}
	function getPicGr($gcode,$bnum) {

				global $dbConn;

				$qry1 = "select count(picCode) cnt,picCode from tour_car 
				where grand_eCode = '$gcode' && bus_num ='$bnum' && p_code not like '%ADD%' group by picCode";
				
				$rst1 = $dbConn->query($qry1);
				while($row1 = $rst1->fetch_assoc()){
			
					    $pickarr = explode("/",$row1[picCode]);
						$picknm=pickBaseInfo($pickarr[0],$pickarr[1]);
						$content .= "$picknm[pick_name]-$picknm[pick_time] : $row1[cnt]개&nbsp;&nbsp;&nbsp;&nbsp;";
					
					
				}

				return $content;

	}
	function getPicGr2($scode, $stdate) {
    /** @var mysqli $dbConn */
    global $dbConn;
    $contentParts = [];
    
    $sql = "
        SELECT COUNT(*) AS cnt, a.pick_area
        FROM reserve_traveler AS a
        INNER JOIN reserve_info AS b ON a.reserveCode = b.reserveCode
        INNER JOIN tour_car AS c ON b.reserveCode = c.reserveCode
        WHERE c.sub_eCode = ?
          AND b.stDate = ?
          AND a.pick_area IS NOT NULL
          AND a.pick_area <> ''
          AND b.rev_status != 'CANCEL'
        GROUP BY a.pick_area
        ORDER BY a.pick_area
    ";
    
    if (!$stmt = $dbConn->prepare($sql)) {
        error_log('getPicGr2 prepare failed: ' . $dbConn->error);
        return '';
    }
    
    $stmt->bind_param('ss', $scode, $stdate);
    
    if (!$stmt->execute()) {
        error_log('getPicGr2 execute failed: ' . $stmt->error);
        $stmt->close();
        return '';
    }
    
    $res = $stmt->get_result();
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            // pick_area 형식: "장소코드/상세위치" 또는 "장소코드"
            $pickarr = explode('/', $row['pick_area'], 2);
            $p0 = isset($pickarr[0]) ? trim($pickarr[0]) : '';
            $p1 = isset($pickarr[1]) ? trim($pickarr[1]) : '';
            
            // pickBaseInfo는 픽업 시간 정보를 반환
            $picknm = pickBaseInfo($p0, $p1);
            $ptime = isset($picknm['pick_time']) && $picknm['pick_time'] !== '' ? $picknm['pick_time'] : '-';
            
            $cnt = (int)$row['cnt'];
            
            // 픽업 장소명 가져오기
            $pickName = pickBaseCodeC($p0);
            if ($p1 !== '') {
                $pickName .= "/{$p1}";
            }
            
            $contentParts[] = "{$pickName}-{$ptime} : {$cnt}명";
        }
		
        $res->free();
    }
    $stmt->close();
    
    if (empty($contentParts)) {
        return '';
    }
    
    return implode(' // ', $contentParts);
}

	function getPicGr3($scode,$tnm) {

				global $dbConn;

				$qry1 = "select count(pick_area) cnt,pick_area from reserve_traveler 
				where reserveCode = '$scode' && traveler_nm = '$tnm' group by pick_area";
				//echo $qry1."<br />";
				//exit;
				$rst1 = $dbConn->query($qry1);
				while($row1 = $rst1->fetch_assoc()){
			
					    $pickarr = explode("/",$row1[pick_area]);
						$picknm=pickBaseInfo($pickarr[0],$pickarr[1]);
						if ($picknm[pick_time] =="") {
							$picknm=pickBaseInfo2($pickarr[0]);
							$content = "$pickarr[0]";
						} else {
							$content = "$pickarr[0]-$picknm[pick_time]";
						}
					
					
				}


				return $content;

	}
	function getPicSub($scode,$p_code,$stdate) {

				global $dbConn;

				$qry1 = "select meet_area from reserve_info 
				where reserveCode = '$scode' && stDate='$stdate' && p_code = '$p_code'";
				//echo $qry1;
				$rst1 = $dbConn->query($qry1);
				while($row1 = $rst1->fetch_assoc()){
			
					    
						$picknm=codebaseName($row1[meet_area]);
						$content .= "$picknm[comment]";
					
					
				}


				return $content;

	}
	function getPicSub2($scode,$p_code,$stdate) {

				global $dbConn;

				$qry1 = "select meet_area from reserve_info 
				where reserveCode = '$scode' && stDate='$stdate' && p_code = '$p_code'";
				//echo $qry1;
				$rst1 = $dbConn->query($qry1);
				$content="";
				while($row1 = $rst1->fetch_assoc()){
			
					    
						$picknm=pickBaseCode4($row1[meet_area]);
						$content = "$picknm[pick_name]";
					
					
				}


				return $content;

	}
	function pickBaseCode4($code){
			
		global $dbConn;

		$qry1 = "select * from base_pick where pick_m = 'M' && pick_code='$code' order by pick_code asc";
		$rst1 = $dbConn->query($qry1);
		
		$row1 = $rst1->fetch_assoc();
		return $row1;

	}
	function printSubHotelList($p_code){
		
		global $dbConn;

		$qry1 = "select * from product_master where p_code like '%ADD%' order by p_code asc";
		$rst1 = $dbConn->query($qry1);

		while($row1 = $rst1->fetch_assoc()){
			
			if($p_code == $row1[p_code])
			{
				$content .= "<option value=$row1[p_code] selected>$row1[p_name]";
			}
			else
			{
				$content .= "<option value=$row1[p_code]>$row1[p_name]";
			}
			
		}

		return $content;
	}
    function getPicGrM($gcode,$scode) {

			global $dbConn;

			$qry1 = "select count(picCode) cnt,picCode from tour_car 
			where grand_eCode= '$gcode' AND sub_eCode = '$scode'  group by picCode";
			
			$rst1 = $dbConn->query($qry1);

		    while($row1 = $rst1->fetch_assoc()) {
					$pickarr = explode("/",$row1[picCode]);
					$picknm=pickBaseInfo($pickarr[0],$pickarr[1]);
					$content .= "$picknm[pick_name]-$picknm[pick_time] : $row1[cnt]인&nbsp;&nbsp;&nbsp;&nbsp;";
				
				
			}


			return $content;

	}
	/**
	* @ 아이디로 개인정보 뽑아오기
	*/
	function getinfo_dbMemberg($user_info){
		
		global $dbConn;

		$qry1 = "select * from member_list where userid = '$user_info' ";
		$rst1 = $dbConn->query($qry1);
			
		$row1 = $rst1->fetch_assoc();
		//echo $qry1."<br/>";
		//exit;
		return $row1;

	}
	function getguideInfor($gcode,$scode) {

			global $dbConn;

			$qry1 = "select   * from tour_guide 
								 where  grand_eCode ='$gcode' && sub_eCode='$scode'  && p_code not like '%ADD%'";
	        
			$rst1 = $dbConn->query($qry1);
			//echo $qry1.'<br />1';
		    $row1 = $rst1->fetch_assoc();
				
			return $row1;


	}
	function getCCInfor($scode) {

			global $dbConn;

			$qry1 = "select   * from tour_car 
								 where  sub_eCode='$scode' ";
			$rst1 = $dbConn->query($qry1);
			
		    $row1 = @$rst1->fetch_assoc();
			
			return $row1;


	}

	function getGuideInfo2($gscode,$pcode) {

			global $dbConn;

			$qry1 = "select   * from tour_guide 
						where sub_eCode ='$gscode' && p_code='$pcode' ";
		  // echo $qry1."<br />";
			$rst1 = $dbConn->query($qry1);
			
		    $row1 = @$rst1->fetch_assoc();
			
			return $row1;

	}
    function pickBaseCodeC($code = false){
		
		global $dbConn;

		$qry1 = "select pick_code,pick_name from base_pick where pick_m = 'M' && pick_code='$code'
				 union
				 select h_code as pick_code,h_name as pick_name from product_hotel where h_code='$code' order by pick_name asc
				 ";
		$rst1 = $dbConn->query($qry1);

	    while($row1 = $rst1->fetch_assoc()) {
			
			$content= $row1[pick_name];
			
		}

		return $content;

	}

	function getPicGr4($scode,$tnm) {

			global $dbConn;

			$qry1 = "select count(pick_area) cnt,pick_area from reserve_traveler 
			where reserveCode = '$scode' group by pick_area"; //&& traveler_nm = '$tnm' 
			//echo $qry1;
			$rst1 = $dbConn->query($qry1);

		    while($row1 = $rst1->fetch_assoc()) {
		
					$pickarr = explode("/",$row1[pick_area]);
					$picknm=pickBaseInfo($pickarr[0],$pickarr[1]);
					$content .= "$picknm[pick_name]-$picknm[pick_time]<br /><br />";
				
				
			}


			return $content;

	}
	function getHotelass2($r_code) {
			global $dbConn;

			$qry1 = "select * from hotel_assign where reserveCode='$r_code' order by day asc";		
			$rst1 = $dbConn->query($qry1);
            $i =1;
		    while($row1 = $rst1->fetch_assoc()) {
					
					
					$content .= "$i 일차호텔 : $row1[hotel_code] <br />";
					
					
					$i++;
				
			}


			return $content;



	}
	function getHotelass21($r_code) {
			global $dbConn;

			$qry1 = "select * from hotel_assign where reserveCode='$r_code' order by day asc";		
			$rst1 = $dbConn->query($qry1);
            $i =1;
		    while($row1 = $rst1->fetch_assoc()) {
					$hinfo=getHotelfInfo21($row1[hotel_code]);
					
					$content .= "$i 일차호텔 : $hinfo[h_name] <br />";
					
					
					$i++;
				
			}


			return $content;



	}
	function getCarInfo($gscode) {

			global $dbConn;

			$qry1 = "select   * from bus_list 
						where  bus_id ='$gscode' ";
			//echo $qry1."<br />";
			$rst1 = $dbConn->query($qry1);
			
		    $row1 = @$rst1->fetch_assoc();
			
			return $row1;

	}
	function getCarInfo2($gscode) {

			global $dbConn;

			$qry1 = "select  * from bus_list 
						where  bus_id ='$gscode' ";
			//echo $qry1."<br />";
			$rst1 = $dbConn->query($qry1);
			
		    $row1 = @$rst1->fetch_assoc();
			
			return $row1;

	}
	function getTrSex($rev,$scode) {

			global $dbConn;

			$qry1 = "select   *  from reserve_traveler a,tour_car b 
					 where a.reserveCode=b.reserveCode && a.reserveCode = '$rev' && b.sub_eCode='$scode' && a.traveler_nm = b.rev_nm
					 group by a.sextype";
			
			$rst1 = $dbConn->query($qry1);

		    while($row1 = $rst1->fetch_assoc()) {
					if ($row1[sextype] == "man") {
						$sex= "남자";
					} else if ($row1[sextype] == "female") {
						$sex = "여자";

					} else if ($row1[sextype] == "mfemale") {
						 $sex = "혼성";
					}
					$sexcc .= $sex."/";
				
			}


			return $sexcc;

	}

	function getReserveTrInfo($rev,$nm){
		
				global $dbConn;

				$qry1 = "select   *  from reserve_traveler 
									 where reserveCode = '$rev' && traveler_nm = '$nm'
									
								";
								//echo $qry1."<br/>";
				$rst1 = $dbConn->query($qry1);
			
		        $row1 = @$rst1->fetch_assoc();
				
				return $row1;

	 }
	function getReserveInfo2($rCode,$st){
		
				global $dbConn;

				$qry1 = "select * from reserve_info where reserveCode = '$rCode' && stDate='$st' && parent = 'SUB'";
				//echo $qry1."<br />";	
				$rst1 = $dbConn->query($qry1);
			
		        $row1 = @$rst1->fetch_assoc();
				
				return $row1;

	}
	function pickBaseCodetxt($code = false){
			
		global $dbConn;

		$qry1 = "select pick_code,pick_name from base_pick where pick_m = 'M' && pick_code ='$code'
		         union
				 select h_code as pick_code,h_name as pick_name from product_hotel where h_code='$code' order by pick_name asc
				 ";
		$rst1 = $dbConn->query($qry1);
			
		$row1 = @$rst1->fetch_assoc();

		return $row1;

	}
	function pickBaseCode3($code = false){
			
		global $dbConn;

		$qry1 = "select pick_code,pick_name from base_pick where pick_m = 'M' && pick_code ='$code'
		         union
				 select h_code as pick_code,h_name as pick_name from product_hotel where h_code='$code' order by pick_name asc
				 ";
		$rst1 = $dbConn->query($qry1);
			
		$row1 = @$rst1->fetch_assoc();

		return $row1;

	}
	function getHRoomCnt($gcode,$gscode) {

				global $dbConn;

				$qry1 = "select romm_num from tour_car 
                            where grand_eCode='$gcode' && sub_eCode = '$gscode' group by romm_num";
				
				$rst1 = $dbConn->query($qry1);
				$num1 = $rst1->num_rows;
				$row1 = @$rst1->fetch_assoc();
				//echo $qry1;
				return $num1;

	}
	function printCarSelect($gid = false){
	
			global $dbConn;
			
			$qry1 = "select * from bus_list where 1=1   order by bus_team asc";
			$rst1 = $dbConn->query($qry1);

			while($row1 = $rst1->fetch_assoc()) {
				$comp=codebaseName($row1['bus_team']);
				if($gid == $row1['bus_id'])
				{
					$content .= "<option value='$row1[bus_id]' selected>$comp[comment] ($row1[bus_id])";
				}
				else
				{
					$content .= "<option value='$row1[bus_id]' >$comp[comment] ($row1[bus_id])";
				}
			

			}

			return $content;
		}
	function printCarSelect2($gid = false){
	
			global $dbConn;
			
			$qry1 = "select * from bus_list where 1=1   order by bus_team asc";
			$rst1 = $dbConn->query($qry1);
			
			while($row1 = $rst1->fetch_assoc()) {
				
				if($gid == $row1['bus_id'])
				{
					$content .= "<option value='$row1[bus_id]' selected>$row1[bus_number] ($row1[bus_id])";
				}
				else
				{
					$content .= "<option value='$row1[bus_id]' >$row1[bus_number] ($row1[bus_id])";
				}
			

			}

			return $content;
		}

	function printGuideSelect($gid = false){
	
	    global $dbConn;

		$qry1 = "select userid,kor_name from member_list where division = 'guide' && guide_status  ='GOOD' order by kor_name asc";
		$rst1 = $dbConn->query($qry1);

		while($row1 = $rst1->fetch_assoc()) {
			
			if($gid == $row1[userid])
			{
				$content .= "<option value='$row1[userid]' selected>$row1[kor_name] ($row1[userid])";
			}
			else
			{
				$content .= "<option value='$row1[userid]' >$row1[kor_name] ($row1[userid])";
			}
		

		}

		return $content;
	}
	function getReserveInfoCntguide($seqno){
		
				global $dbConn;

				$seqno = (int)$seqno;

				$qry1 = "
				  SELECT SUM(ri.p_cnt) AS cnt
				  FROM tour_guide tg
				  JOIN reserve_info ri
					   ON ri.p_code  = tg.p_code
					  AND ri.stDate  = tg.stDate
					  AND ri.rev_status = 'DONE'
				  WHERE tg.seq_no = {$seqno}
				";
				$rst1 = $dbConn->query($qry1);
				
				$row1 = $rst1->fetch_assoc();
				
				return $row1;

	 }
	 function getReserveInfoCntSUB($p_code,$stDate,$sub){
		
				global $dbConn;

				$seqno = (int)$seqno;

				$sql_assign = "SELECT SUM(pcnt) as cnt 
                   FROM hotel_assign 
                   WHERE p_code = '$p_code' 
                     AND stDate = '$stDate' 
                   AND  sub_eCode='$sub'";
    
				$res_assign = $dbConn->query($sql_assign);
				
				$row1 = $res_assign->fetch_assoc();
				
				return $row1;

	 }
    function getGuideInfo($gcode,$gscode,$bnum,$pcode) {

				global $dbConn;

				$qry1 = "select   * from tour_guide 
                            where grand_eCode='$gcode' && sub_eCode ='$gscode' && bus_num='$bnum' && p_code='$pcode'";
				//echo $qry1."<br />";
				$rst1 = $dbConn->query($qry1);
				$num1 = $rst1->num_rows;
				$row1 = @$rst1->fetch_assoc();
				
				return $row1;

	}
	//가이드정산코드
	function getGuideCode($grand_eCode,$sub_eCode){
        global $dbConn;

		$query = "SELECT settle_code,finance_date,check_out,check_date,report_date FROM guide_setmaster WHERE grand_eCode = '$grand_eCode' AND sub_eCode = '$sub_eCode' ";
	    ///echo $query;
		$rst1 = $dbConn->query($query);
		
		$row1 = $rst1->fetch_assoc();
          
        return $row1;
	}
	//행사별 정산 행사기간
    function getPeriodbyrev($p_code,$stDate){

          global $dbConn;

          $query = "SELECT b.p_day FROM reserve_info  a , product_master b 
          WHERE a.p_code = b.p_code  
          AND a.p_code = '$p_code' 
          AND a.stDate='$stDate' 
          AND ( rev_status!='CANCEL' AND rev_status!='WAIT') LIMIT 1";
          $rst1 = $dbConn->query($query);
		  $data_row = $rst1->fetch_assoc();
          $data_row[p_day] = $data_row[p_day]-1;
          $c_day = '+'.$data_row[p_day].' day';
          $period = $stDate." ~ ".date( "Y-m-d", strtotime( "$stDate $c_day" ));

          return $period;

    }
	 //가이드정산 상태가져오기
	 /*
     * tour_guide(o), guide_setmaster(x)  : 미등록
	 * 저장시 등록
	 * 정산보고완료시 정산완료

	 */
	 function getGuideStatus($grand_eCode,$sub_eCode,$stDate){

		global $dbConn;

		$query = "SELECT COUNT(*) cnt FROM tour_guide WHERE grand_eCode = '$grand_eCode' AND sub_eCode = '$sub_eCode' ";

		$rst1 = $dbConn->query($query);
		$data_row = $rst1->fetch_assoc();
		
		if($data_row[cnt] >0) {
			
			$query = "SELECT * FROM guide_setmaster WHERE grand_eCode = '$grand_eCode' AND sub_eCode = '$sub_eCode' ";
			$rst1 = $dbConn->query($query);
			$data_row = $rst1->fetch_assoc();

			if($data_row['reg_status'] == 'COMPLETE') $status = '정산보고완료';
			else if($data_row['reg_status'] == 'DONE') $status = '등록';
			else $status = '미등록'; 

			if ($data_row['finance_st']!="") {
				$status = "<font color=red>회계확인</font>";
			}elseif ($data_row['ceo_st']!="") {
				$status = "<font color=blue>대표이사확인</font>";
			}elseif ($data_row['check_out']!="") {
				$status = "<font color=blue>체크나감<br />".$data_row['check_date']."</font>";
			}

		}else{
			$status = '미등록';
		}

		return $status; 

	}
    
    function mailsend_h($to,$subj,$contents,$attachment1,$attachment2,$attachment3,$attachment4) {
    
            $mail = new PHPMailer(true);
            
            $mail->IsSMTP();
            //echo $contents;
        ///exit;
            $mail->CharSet = "utf-8"; 
            $mail->SMTPDebug = 1; // debugging: 1 = errors and messages, 2 = messages only
            $mail->SMTPAuth = true; // authentication enabled
            //$mail->SMTPSecure = 'tls'; // secure transfer enabled REQUIRED for GMail
            $mail->Host = 'in-v3.mailjet.com';
			$mail->Port = 587; 
			$mail->Username = "201aa76dd3ef231dc54a7db95432252f";
			$mail->Password = "3fdeb49dd53d226c5e11f6fb585721cb";
			$mail->SetFrom("info@tourhellousa.com","투어헬로USA");
            $mail->IsHTML(true);
            
            $mail->AltBody = '';
            $mail->Subject = $subj;
            
            $mail->MsgHTML($contents);
            
            $mail->AddAddress($to);
            /*
            foreach($attachments as $attachment) {
                    //$mail->AddAttachment("images/phpmailer.gif");      // attachment example
                    $mail->AddAttachment($attachment);
            }
            */
            
            if ($attachment1 !="") {
				
                $mail->AddAttachment("upload/".$attachment1."");
				//echo $attachment1."TEST";
               // exit;
            }
            if ($attachment2 !="") {
                $mail->AddAttachment("upload/".$attachment2."");

            }
            if ($attachment3 !="") {
                $mail->AddAttachment("upload/".$attachment3."");

            }
            if ($attachment4 !="") {
                $mail->AddAttachment("upload/".$attachment4."");

            }
            
            
            if(!$mail->Send()){
              echo $mail->ErrorInfo();
			  exit;
              return $mail->ErrorInfo();
            } else {
            
               return true;
            }
    }

	function mailsend_m($to,$subj,$contents,$attachment1,$attachment2) {
    
            $mail = new PHPMailer(true);
            
            $mail->IsSMTP();
            //echo $contents;
        ///exit;
            $mail->CharSet = "utf-8"; 
            $mail->SMTPDebug = 1; // debugging: 1 = errors and messages, 2 = messages only
            $mail->SMTPAuth = true; // authentication enabled
            //$mail->SMTPSecure = 'tls'; // secure transfer enabled REQUIRED for GMail
            //$mail->Host = 'in-v3.mailjet.com';
			$mail->Port = 587; 
			$mail->Username = "201aa76dd3ef231dc54a7db95432252f";
			$mail->Password = "3fdeb49dd53d226c5e11f6fb585721cb";
			$mail->SetFrom("info@tourhellousa.com","투어헬로USA");
            $mail->IsHTML(true);
            
            $mail->AltBody = '';
            $mail->Subject = $subj;
            
            $mail->MsgHTML($contents);
            
            $mail->AddAddress($to);
            /*
            foreach($attachments as $attachment) {
                    //$mail->AddAttachment("images/phpmailer.gif");      // attachment example
                    $mail->AddAttachment($attachment);
            }
            */
            //echo $attachment1;
            //exit;
            if ($attachment1 !="") {
				
                $mail->AddAttachment("upload/".$attachment1."");
				//echo $attachment1."TEST";
               // exit;
            }
            if ($attachment2 !="") {
                $mail->AddAttachment("upload/".$attachment2."");

            }
           
            
            
            if(!$mail->Send()){
              echo "111";
              exit; 
              return $mail->ErrorInfo();
            } else {
            
               return true;
            }
    }

	
    function getRandBalance($rand_id){

    global $dbConn;

    
            $qry1 = "select sum(amt) from rand_pay where reserveCode is not null && reserveCode <> ''  && rand_id = '$rand_id' 
            && date_format(tr_date ,'%Y-%m-%d') >= '2015-01-01'";
            $rst1 = $dbConn->query($qry1);
    //echo $qry1;
            $balance = @dbMysql_result($rst1, 0, 0);

            if(empty($balance))
            {
                $balance = "0";
            }

            return $balance;
    }

    function getRandBalance2($rand_id,$reserveCode){

            global $dbConn;

    
            $qry1 = "select sum(payment) from rand_pay where reserveCode= '$reserveCode'  && rand_id = '$rand_id'";
            $rst1 = $dbConn->query($qry1);
    //echo $qry1;
			
            $balance = @dbMysql_result($rst1, 0, 0);

            if(empty($balance))
            {
                $balance = "0";
            }

            return $balance;
    }

    function getCRandBalance2($rand_id,$reserveCode){

            global $dbConn;

    
            $qry1 = "select sum(amt) from rand_pay where reserveCode= '$reserveCode'  && rand_id = '$rand_id' && tr_type='credit'";
            $rst1 = $dbConn->query($qry1);
    //echo $qry1;
            $balance = @dbMysql_result($rst1, 0, 0);

            if(empty($balance))
            {
                $balance = "0";
            }

            return $balance;
    }
    function getDRandBalance2($rand_id,$reserveCode){

            global $dbConn;

    
            $qry1 = "select sum(amt) from rand_pay where reserveCode= '$reserveCode'  && rand_id = '$rand_id' && tr_type='debit'";
            $rst1 = $dbConn->query($qry1);
    //echo $qry1;
            $balance = @dbMysql_result($rst1, 0, 0);

            if(empty($balance))
            {
                $balance = "0";
            }

            return $balance;
    }

    function getPaymemo($rand_id,$reserveCode,$seq){

            global $dbConn;

    
            $qry1 = "select set_memo from rand_pay where reserveCode= '$reserveCode'  && rand_id = '$rand_id' && seq_rand='$seq'";
            $rst1 = $dbConn->query($qry1);
    //echo $qry1;
            $mm = @dbMysql_result($rst1, 0, 0);

        

            return $mm;
    }
    
    function printRandSelectAirlie($rand_id = false){

      global $dbConn;

        $qry1 = "select * from member_list where division = 'comp' && del_yn  ='N' && issue_airline = 'YES' order by company_area,kor_name asc";
        $rst1 = $dbConn->query($qry1);

        while($row1 = $rst1->fetch_assoc()){
            
            $company_area = codebaseName($row1[company_area]);

            if($rand_id == $row1[userid])
            {
                $content .= "<option value='$row1[userid]' selected>[$company_area[comment]] $row1[kor_name] ($row1[userid])";
            }
            else
            {
                $content .= "<option value='$row1[userid]'>[$company_area[comment]] $row1[kor_name] ($row1[userid])";
            }
            

        }

        return $content;
    }
    function getMembercnt($r_code){
    
        global $dbConn;

        $qry1 = "select reserveCode from reserve_traveler where reserveCode = '$r_code' ";
        $rst1 = $dbConn->query($qry1);
        $num1 = $rst1->num_rows;

        return $num1;
    }

    function employeelist($userid){
			
			global $dbConn;

			$qry1 = "select * from member_list where division = 'admin' && out_yn is null order by userid asc";
			$rst1 = $dbConn->query($qry1);
			
			while($row1 = $rst1->fetch_assoc()){
				
				$selectValue = $row1[userid];
				
				if($selectValue == $userid)
				{
					$option.= "<option value=$selectValue selected>$row1[kor_name]($row1[userid])";
				}
				else
				{
					$option.= "<option value=$selectValue>$row1[kor_name]($row1[userid])";
				}
				

			}

			return $option;

	}
    function employeeoutlist($userid){
			
			global $dbConn;

			$qry1 = "select * from member_list where division = 'admin' && out_yn is null && c_part1='D013000' order by userid asc";
			$rst1 = $dbConn->query($qry1);
			
			while($row1 = $rst1->fetch_assoc()){
				
				$selectValue = $row1[userid];
				
				if($selectValue == $userid)
				{
					$option.= "<option value=$selectValue selected>$row1[kor_name]($row1[userid])";
				}
				else
				{
					$option.= "<option value=$selectValue>$row1[kor_name]($row1[userid])";
				}
				

			}

			return $option;

	}
	//메일보내기
     function mailsend_k($to,$subj,$contents,$attachment1,$attachment2) {
         
         $mail = new PHPMailer(true);
		 $mail->IsSMTP();
				
         $mail->CharSet = "utf-8"; 
         $mail->Encoding = "base64"; 
         $mail->SMTPDebug = false; // debugging: 1 = errors and messages, 2 = messages only
         $mail->SMTPAuth = true; // authentication enabled
        // $mail->SMTPSecure = 'tls'; // secure transfer enabled REQUIRED for GMail
         //$mail->Host = 'email-smtp.us-east-1.amazonaws.com';
         $mail->Host = 'in-v3.mailjet.com';
         $mail->Port = 587; 
         $mail->Username = "201aa76dd3ef231dc54a7db95432252f";
         $mail->Password = "3fdeb49dd53d226c5e11f6fb585721cb";
         $mail->IsHTML(true);
         $mail->SetFrom("info@tourhellousa.com","TOUR HELLO USA");
         $mail->AltBody = '';
         $mail->Subject = $subj;
        
         $mail->MsgHTML($contents);
         $mail->AddAddress($to);
    
         if ($attachment1 !="") {
                $mail->AddAttachment("upload/".$attachment1."");

            }
            if ($attachment2 !="") {
                $mail->AddAttachment("upload/".$attachment2."");

            }

         if(!$mail->Send()){
             return $mail->ErrorInfo();
         } else {
             return true;
         }
     }

	 function hr_display($the_time) {
		$total_hrs = (int) ($the_time / 3600);
		$total_min = (int) ($the_time / 60) - ($total_hrs * 60);
		if ($total_min < 10) {
			$total_min = "0".$total_min;
		}

		return "$total_hrs:$total_min";
	 }
	 function removeMysql_result($result, $number, $field=0) {

        $result->data_seek($number);
        $row = $result->fetch_row();
        
        return $row[$field];

    }

	function printOBEmployeeSelect($employee_id = false){
	
	    global $dbConn;

		$qry1 = "select * from member_list where division = 'admin' && del_yn  ='N' && c_part1='D013000' order by kor_name asc";
		$rst1 = $dbConn->query($qry1);
			
		while($row1 = $rst1->fetch_assoc()){
			
			if($employee_id == $row1[userid])
			{
				$content .= "<option value='$row1[userid]' selected> $row1[kor_name] ($row1[userid])";
			}
			else
			{
				$content .= "<option value='$row1[userid]'> $row1[kor_name] ($row1[userid])";
			}
			

		}

		return $content;
	}
	


	/**
	 * PHP 7.0 호환 업로드 저장 헬퍼
	 * - 이미지 확장자/용량/MIME 검사
	 * - fileinfo 미설치 시 exif_imagetype → getimagesize 순서로 폴백
	 * - 유니크 파일명 생성 (random_bytes → openssl_random_pseudo_bytes → uniqid)
	 * - 저장 폴더가 없으면 생성, 업로드 폴더 내 PHP 실행 차단(.htaccess)
	 *
	 * 성공 시 'upload/20250901_120000_ab12cd34ef.png' 형태의 상대경로 반환
	 * 실패 시 ''(빈 문자열) 반환
	 */
	function file_save($file, $dir = '', $opt = [])
	{
		// 1) 업로드 유효성
		if (!isset($file['tmp_name'], $file['error'])) return '';
		if ($file['error'] !== UPLOAD_ERR_OK) return '';
		if (!is_uploaded_file($file['tmp_name'])) return '';

		// 2) 옵션 기본값
		$maxSize = isset($opt['max_size']) ? (int)$opt['max_size'] : (10 * 1024 * 1024); // 10MB
		$allowed = isset($opt['allowed']) && is_array($opt['allowed'])
			? array_map('strtolower', $opt['allowed'])
			: ['jpg','jpeg','png','gif','webp'];

		// 3) 용량 제한
		$size = isset($file['size']) ? (int)$file['size'] : 0;
		if ($size <= 0 || $size > $maxSize) return '';

		// 4) 확장자
		$origName = isset($file['name']) ? $file['name'] : 'upload.bin';
		$ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
		if (!in_array($ext, $allowed, true)) return '';

		// 5) MIME 검증 (fileinfo → exif_imagetype → getimagesize)
		$tmp = $file['tmp_name'];
		$mime = '';

		if (function_exists('finfo_open')) {
			$fi = @finfo_open(FILEINFO_MIME_TYPE);
			if ($fi) {
				$mime = (string)@finfo_file($fi, $tmp);
				@finfo_close($fi);
			}
		}
		if ($mime === '' && function_exists('exif_imagetype')) {
			$type = @exif_imagetype($tmp);
			if ($type) {
				// 간단 매핑
				$map = [
					IMAGETYPE_JPEG => 'image/jpeg',
					IMAGETYPE_PNG  => 'image/png',
					IMAGETYPE_GIF  => 'image/gif',
					IMAGETYPE_WEBP => 'image/webp',
				];
				$mime = isset($map[$type]) ? $map[$type] : '';
			}
		}
		if ($mime === '' && function_exists('getimagesize')) {
			$info = @getimagesize($tmp);
			if (is_array($info) && isset($info['mime'])) $mime = (string)$info['mime'];
		}

		$okMime = [
			'jpg'  => ['image/jpeg'],
			'jpeg' => ['image/jpeg'],
			'png'  => ['image/png'],
			'gif'  => ['image/gif'],
			'webp' => ['image/webp', 'image/x-webp'],
		];
		if (!isset($okMime[$ext])) return '';
		if ($mime !== '' && !in_array($mime, $okMime[$ext], true)) return '';

		// 6) 저장 경로 구성
		if (!isset($dir)) {
			$absDir = $root . "upload/";
		} else  {
			$absDir = $dir;
		}
		
        
		if (!is_dir($absDir)) {
			if (!@mkdir($absDir, 0755, true) && !is_dir($absDir)) return '';
		}

		

		// 7) 유니크 파일명 생성
		$rand = '';
		if (function_exists('random_bytes')) {
			$rand = bin2hex(@random_bytes(8));
		} elseif (function_exists('openssl_random_pseudo_bytes')) {
			$rand = bin2hex(@openssl_random_pseudo_bytes(8));
		}
		if ($rand === '') $rand = uniqid('', true);

		$base = date('Ymd_His') . '_' . $rand;
		$name = $base . '.' . $ext;
		$abs  = $absDir . $name;

		// 8) 파일 이동
		if (!@move_uploaded_file($tmp, $abs)) return '';
		@chmod($abs, 0644);

		// 9) 상대경로 반환
		return $relDir . $name;
	}


    /*
    function mailsend_g($to,$subj,$contents,$attachment1,$attachment2) {
    
            $mail = new PHPMailer(true);
            
            $mail->IsSMTP();
            //echo "111";
        
            $mail->CharSet = "utf-8"; 
            $mail->SMTPDebug = 0; // debugging: 1 = errors and messages, 2 = messages only
            $mail->SMTPAuth = true; // authentication enabled
            $mail->SMTPSecure = 'tls'; // secure transfer enabled REQUIRED for GMail
            $mail->Host = 'smtp.gmail.com';
            $mail->Port = 587; 
            $mail->Username = "prunetour1@gmail.com";
            $mail->Password = "prtprt0899"
            $mail->IsHTML(true);
            $mail->SetFrom("local@prttour.com","PRUNTOUR");
            $mail->AddReplyTo("local@prttour.com","PRUNTOUR");
            $mail->AltBody = '';
            $mail->Subject = $subj;
            
            $mail->MsgHTML($contents);
            
            $mail->AddAddress($to);
            
            ///echo $attachment2;
            
            if ($attachment1 !="") {
                $mail->AddAttachment("upload/".$attachment1."");

            }
            if ($attachment2 !="") {
                $mail->AddAttachment("upload/".$attachment2."");

            }
            
            
            if(!$mail->Send()){
                
              return $mail->ErrorInfo();
            } else {
                
               return true;
            }
    }
    */



	