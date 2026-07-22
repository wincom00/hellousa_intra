<?php	
	include "include/inc_base.php";
    if ($_COOKIE[MEMLOGIN_ADMIN_HELLO] !="") {
	} else {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}

     if (!hasMenuAccess($division, $pdx, $sub)) {
		
		Misc::jvAlert("권한이 있는 메뉴가 아닙니다. 확인후 사용하세요.!!","");
		exit;
    }

	$revInfo= getReserveInfo($r_code);
	$prodInfo = getProductMaster($revInfo[p_code]);
    $randC = getCRandInfo($r_code);
	$randD = getDRandInfo($r_code);
	$sday = $revInfo[stDate] ;
	$eday = $revInfo[edDate] ;
    $week = array("일" , "월"  , "화" , "수" , "목" , "금" ,"토") ;
	$eweek = array("SUN" , "MON" , "TUE" , "WED" , "THU" , "FRI" ,"SAT") ;
    $sweekday = $week[ date('w'  , strtotime($sday)  ) ] ;
	$eweekday = $week[ date('w'  , strtotime($eday)  ) ] ;
	$seweekday = $eweek[ date('w'  , strtotime($sday)  ) ] ;
	$eeweekday = $eweek[ date('w'  , strtotime($eday)  ) ] ;
	if ($revInfo[base_rate] == "CAD") {
		$sign = "C$";
	} else {
		$sign = "U$";
	}
	$disinfo = codebaseName($revInfo[dis_code]);
	$disamt = getReserveSum($r_code);
	$airamt = getAirlineSum($r_code);
	$airprofit = getAirProfit($r_code);
	$randamt = getRandSum($r_code);
	$arandamt = getARandSum($r_code);
	$ageamt = getAgeSum($r_code);
	$totamt = $revInfo[last_total] ;//- $disamt[amt];
	$lastadd = $revInfo[last_add];
	$lasttot = $revInfo[last_sale];
	 
	$rev_dbinfo = getinfo_dbMember($revInfo[userid]);
	$rname=randname($revInfo[rand_id]);
	$qry3 = "select * from html_page where id = 'dbt_lh'";
	$rst3 = $dbConn->query($qry3);
	$row3 = $rst3->fetch_assoc();
	$body = "$row3[content]";

	$qry4 = "select * from html_page where id = 'returnc'";
	$rst4 = $dbConn->query($qry4);
	$row4 = $rst4->fetch_assoc();
	$alert1 = "$row4[content]";

	$qry5 = "select * from html_page where id = 'returno'";
	$rst5 = $dbConn->query($qry5);
	$row5 = $rst5->fetch_assoc();
	$alert2 = "$row5[content]";
    

	function printCustomer() {
		global $dbConn, $division, $randSelection,$r_code;

		

		$qry1 = "select seq_no,send_reg,subject,sent_on from mailing_history where reserveCode='$r_code' order by sent_on desc limit 1";
		$rst1 = $dbConn->query($qry1);
	

		while($row1 = $rst1->fetch_assoc()){
		
			
					echo "<tr bgcolor=#FFFFFF>
					<td height=25 style='text-align: center;border: 1px solid #222;font-weight: bold;'>&nbsp;$row1[send_reg]</td>
					<td style='text-align: center;border: 1px solid #222;'><a href=javascript:viewmail('$r_code','$row1[seq_no]') >$row1[subject]</a></td>
					<td style='text-align: center;border: 1px solid #222;font-weight: bold;'>$row1[sent_on]</td>
					</tr>";
				
		}
		
	}
	function tourplist()
	{
		 global $dbConn,$r_code;
		 $qry1="select * from reserve_traveler where reserveCode='$r_code' order by seqint asc limit 1";
		 $rst1 = $dbConn->query($qry1);
		 $k=1;
		 while($row1 = $rst1->fetch_assoc()){
			if ($row1[sextype] == "man") {
				$sexcap= "남자";
			} else if ($row1[sextype] == "female") {
				$sexcap= "여자";
			} else if ($row1[sextype] == "mfemale") {
				$sexcap= "혼성";
			}
			if ($row1[room_type] == "1r1p") {
				$rcap= "1인1실";
			} else if ($row1[room_type] == "1r2p") {
				$rcap= "2인1실";
			} else if ($row1[room_type] == "1r3p") {
				$rcap= "3인1실";
			} else if ($row1[room_type] == "1r4p") {
				$rcap= "4인1실";
			} else if ($row1[room_type] == "1r5p") {
				$rcap= "5인1실";
			} 
			$pickarr = explode("/",$row1[pick_area]);
			$picknm=pickBaseInfo($pickarr[0],$pickarr[1]);
			//print_r($picknm);
			echo "<tr style='font-weight: bold;border: 1px solid #222;'>
						<td style='padding: 10px;text-align:center;border: 1px solid #222;'>$k</td>
						<td style='text-align:center;border: 1px solid #222;padding: 5px;'>$row1[traveler_nm]<br />$row1[traveler_enm]</td>
						<td style='text-align:center;border: 1px solid #222;padding: 5px;'>$sexcap</td>
						<td style='text-align:center;border: 1px solid #222;padding: 5px;'>$row1[traveler_birth]</td>
						<td style='text-align:center;border: 1px solid #222;padding: 5px;'>$row1[pass_date]</td>
						<td style='text-align:center;border: 1px solid #222;padding: 5px;'>$row1[pass_num]</td>
						<td style='text-align:center;border: 1px solid #222;padding: 5px;'>$row1[traveler_room]</td>
						<td style='text-align:center;border: 1px solid #222;padding: 5px;'>$row1[traveler_phone]</td>
						<td style='text-align:center;border: 1px solid #222;padding: 5px;'>$row1[e_memo]</td>
					</tr>";;
			$k++;
		 }

	}
    
	
	
?>
<!DOCTYPE html>
<html>

<head>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
	<title>Invoice</title>
	<link href="//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
	<link href="https://fonts.googleapis.com/css?family=Montserrat|Open+Sans|Roboto&display=swap" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css?family=Nanum+Gothic" rel="stylesheet">
	<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
	<link rel="stylesheet" href="/resources/demos/style.css">
	<link href="css/invoice-f.css" rel="stylesheet" id="invoice-css">

	<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
  <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>


</head>
<style type="text/css">
  @media print {
	  @page { margin: 0; }
	  body { margin: 1.6cm; }
  }
</style>

<body>
	<!-- book info-->
<div style="text-align: center;margin-bottom:-10px;margin-top:10px;"><h2>아웃바운드 투어리포트</h2></div>

<br />
<form name=print id=print action='<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&r_code=<?=$r_code?>' method=post enctype="multipart/form-data">
  <input type=hidden name=r_code id='r_code' value="<?= $r_code ?>">
  <input type=hidden name=mode id="mode" value="send_email">
	<div id="invoice1">
		<div class="text-center confim_book">투어리포트</div>
		<br/>
		
		
		<div class="invoice1 overflow-auto">
			<div style="min-width: 600px">
				
				<!-- 여행 예약정보 -->
				<div class="text-left book_header">1. 여행 예약 정보</div>
				<table style='width: 100%;line-height: 18px;text-align: left;border: 1px solid #aaa;font-size: 13px;'>
					<tbody>
						<tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border: 1px solid #aaa;">
							<td colspan="2" width = '10%' style="border: 1px solid #aaa;padding: 10px;">여행명</td>
							<td colspan="14" style="background: #fff;padding: 5px;text-align: left;"><?=$prodInfo[p_name]?></td>
						</tr>
						<tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border: 1px solid #aaa;">
							<td colspan="2" style="border: 1px solid #aaa;padding: 10px;">여행기간</td>
							<td colspan="6" style="background: #fff;padding: 5px;text-align: left;"><?=$revInfo[stDate]?>(<?=$sweekday?>)~<?=$revInfo[edDate]?>(<?=$eweekday?>)</td>
							<td colspan="2" style="border: 1px solid #aaa;padding: 10px;">통합예약번호</td>
							<td colspan="6" style="background: #fff;padding: 5px;text-align: left;"><?=$revInfo[grand_revNo]?></td>
						</tr >
						<tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border: 1px solid #aaa;">
							<td colspan="2" style="border: 1px solid #aaa;padding: 10px;">여행인원</td>
							<td colspan="6" style="background: #fff;padding: 5px;text-align: left;"><?=$revInfo[p_cnt]?>인</td>
							<td colspan="2" style="border: 1px solid #aaa;padding: 10px;">예약번호</td>
							<td colspan="6" style="background: #fff;padding: 5px;text-align: left;"><?=$revInfo[reserveCode]?></td>
						</tr >
						<tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border: 1px solid #aaa;">
							<td colspan="2" style="border: 1px solid #aaa;padding: 10px;">예약일</td>
							<td colspan="6" style="background: #fff;padding: 5px;text-align: left;"><?=$revInfo[revDate]?></td>
							<td colspan="2" style="border: 1px solid #aaa;padding: 10px;">예약상담원</td>
							<td colspan="6" style="background: #fff;padding: 5px;text-align: left;"><?=$rev_dbinfo[kor_name]?></td>
						</tr>
						<?php if ($revInfo[pricet] != 3) { ?>
						
						<tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border: 1px solid #aaa;">
							<td colspan="2" style="border: 1px solid #aaa;padding: 10px;">방갯수</td>
							<td colspan="14" style="background: #fff;padding: 5px;text-align: left;"><?=$revInfo[room_cnt]?> </td>
						</tr>
						<?php } else { ?>
						<tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border: 1px solid #aaa;">
							<td colspan="2" style="border: 1px solid #aaa;padding: 10px;">방갯수</td>
							<td colspan="14" style="background: #fff;padding: 5px;text-align: left;"><?=$revInfo[room_cnt]?> </td>
						</tr>

						<?php }  ?>
						
					</tbody>
				</table>
				<br/>
				<!-- 여행자 정보 -->
				<div class="text-left book_header">2. 대표여행자 정보</div>
				<div class="row">
					<div class="col-sm-12">
						<table style='width: 100%;line-height: 18px;text-align: left;border: 1px solid #aaa;font-size: 13px;'>
							<tbody>
								<tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border: 1px solid #aaa;">
									<th style="padding: 10px;border: 1px solid #aaa;" width='5%'>NO.</th>
									<th style="border: 1px solid #aaa;" width='10%'>성명</th>
									<th style="border: 1px solid #aaa;" width='5%'>성별</th>
									<th style="border: 1px solid #aaa;" width='10%'>DOB</th>
									<th style="border: 1px solid #aaa;" width='10%'>여권만료일</th>
									<th style="border: 1px solid #aaa;" width='10%'>여권번호</th>
									<th style="border: 1px solid #aaa;" >RM</th>
									<th style="border: 1px solid #aaa;" >연락처</th>
									<th style="border: 1px solid #aaa;"width='*'>특이사항</th>
								</tr>
							
							
								<?=tourplist()?>
								
							</tbody>
						</table>
						<br/>
					</div>
				</div>
				<br />
				<div class="text-left book_header">3.항공정보</div>
				<div class="row">
					
					<div class="col-sm-12" >
						<?php
									$qryr = "select * from reserve_airline_pnr where reserveCode = '$r_code' order by a_airline_print asc ";
									//echo $qryr;
									$rstr = $dbConn->query($qryr);
									$cntr= $rstr->num_rows;
									$i =1; 
									$totamtair =0.00;
									if ($cntr > 0) {
										while($rrow = $rstr->fetch_assoc()):
										   $productInfo = getProductMaster($rrow[p_code]);
										   $totamtair = $totamtair + $rrow[a_airline_amt];
						?>
							               <table style='width: 100%;line-height: 18px;text-align: left;border: 1px solid #222;font-size: 13px;'>
												<tbody>
													<tr style="background: #eee;font-weight: bold;text-align: center;padding: 5px;border: 1px solid #222;">
														<td colspan="15" width = '10%' style="border: 1px solid #222;padding: 5px;">항공 인보이스#<?=$i?></td>
														
													</tr>
													<tr style="background: #eee;font-weight: bold;text-align: center;padding: 5px;border: 1px solid #222;">
														<td style="border: 1px solid #222;padding: 5px;">출발일</td>
														<td style="background: #fff;padding: 5px;text-align: left;">&nbsp;<?=$rrow[a_airline_start]?></td>
														<td  style="border: 1px solid #222;padding: 5px;">출발공항</td>
														<td style="background: #fff;padding: 5px;text-align: left;">&nbsp;<?=$rrow[a_start_airport]?></td>
														<td style="border: 1px solid #222;padding: 5px;">도착공항</td>
														<td style="background: #fff;padding: 5px;text-align: left;">&nbsp;<?=$rrow[a_stop_airport]?></td>
														<td style="border: 1px solid #222;padding: 5px;">편명</td>
														<td style="background: #fff;padding: 5px;text-align: left;">&nbsp;<?=$rrow[a_airport_name]?></td>
														<td style="border: 1px solid #222;padding: 5px;">출.도착시간</td>
														<td style="background: #fff;padding: 5px;text-align: left;">&nbsp;<?=$rrow[a_airport_time]?> / <?=$rrow[a_airport_time1]?></td>
														<td style="border: 1px solid #222;padding: 5px;">PNR/TICKET#</td>
														<td colspan=4 style="background: #fff;padding: 5px;text-align: left;">&nbsp;<?=$rrow[a_pnr_number]?>/<?=$rrow[a_tk_number]?></td>
													</tr >
													<tr style="background: #eee;font-weight: bold;text-align: center;padding: 5px;border: 1px solid #222;">
														<td style="border: 1px solid #222;padding: 5px;">발권일</td>
														<td style="background: #fff;padding: 5px;text-align: left;">&nbsp;<?=$rrow[a_airline_print]?></td>
														<td  style="border: 1px solid #222;padding: 5px;">복귀일</td>
														<td style="background: #fff;padding: 5px;text-align: left;">&nbsp;<?=$rrow[a_airline_return]?></td>
														<td style="border: 1px solid #222;padding: 5px;">출발공항</td>
														<td style="background: #fff;padding: 5px;text-align: left;">&nbsp;<?=$rrow[a_start_airport2]?></td>
														<td style="border: 1px solid #222;padding: 5px;">도착공항</td>
														<td style="background: #fff;padding: 5px;text-align: left;">&nbsp;<?=$rrow[a_stop_airport2]?></td>
														<td style="border: 1px solid #222;padding: 5px;">편명</td>
														<td style="background: #fff;padding: 5px;text-align: left;">&nbsp;<?=$rrow[a_airport_name2]?></td>
														<td style="border: 1px solid #222;padding: 5px;">출.도착시간</td>
														<td style="background: #fff;padding: 5px;text-align: left;">&nbsp;  <?=$rrow[a_airport_time2]?>/
															 /<?=$rrow[a_airport_time3]?></td>
														<td  style="border: 1px solid #222;padding: 5px;">PNR/TICKET#</td>
														<td  colspan=2 style="background: #fff;padding: 5px;text -align: left;">&nbsp;<?=$rrow[a_pnr_number1]?>/<?=$rrow[a_tk_number2]?></td>
													</tr >
													<tr style="background: #eee;font-weight: bold;text-align: center;padding: 5px;border: 1px solid #222;">
														<td style="border: 1px solid #222;padding: 5px;">인원</td>
														<td style="background: #fff;padding: 5px;text-align: left;">&nbsp;<?=$rrow[a_airport_cnt]?></td>
														<td  style="border: 1px solid #222;padding: 5px;">단가항공료</td>
														<td style="background: #fff;padding: 5px;text-align: left;">&nbsp;<?=$rrow[a_rate]?></td>
														<td style="border: 1px solid #222;padding: 5px;">TAX</td>
														<td style="background: #fff;padding: 5px;text-align: left;">&nbsp;<?=$rrow[a_tax]?></td>
														<td style="border: 1px solid #222;padding: 5px;">MCO/MCO Fee</td>
														<td style="background: #fff;padding: 5px;text-align: left;">&nbsp;<?=$rrow[a_fee]?>/<?=$rrow[a_fee1]?></td>
														<td style="border: 1px solid #222;padding: 5px;">VCOM</td>
														<td style="background: #fff;padding: 5px;text-align: left;">&nbsp;<?=$rrow[a_cms]?></td>
														<td style="border: 1px solid #222;padding: 5px;">티켓 NET총합</td>
														<td style="background: #fff;padding: 5px;text-align: left;">&nbsp;  <?=$rrow[a_amt]?></td>
														<td  style="border: 1px solid #222;padding: 5px;">Class</td>
														<td colspan=2 style="background: #fff;padding: 5px;text-align: left;"><select name=a_cls_type[] class=form-control>
																		<option value="1" <? if($rrow[a_cls_type] == 1) echo "selected"; ?>>ADT
																		<option value="2" <? if($rrow[a_cls_type] == 2) echo "selected"; ?>>CHD
																		<option value="3" <? if($rrow[a_cls_type] == 3) echo "selected"; ?>>SRC
																		<option value="4" <? if($rrow[a_cls_type] == 4) echo "selected"; ?>>INF
															</select>
														</td>
													</tr >
													<tr style="background: #eee;font-weight: bold;text-align: center;padding: 5px;border: 1px solid #222;">
														<td style="border: 1px solid #222;padding: 5px;">결제방법</td>
														<td colspan=4 style="background: #fff;padding: 5px;text-align: left;"><select name=a_settle_type[] class='form-control'>
																<option value="1" <? if($rrow[a_settle_type] == 1) echo "selected"; ?>>항공시스템
																<option value="2" <? if($rrow[a_settle_type] == 2) echo "selected"; ?>>Cash&Check
																<option value="3" <? if($rrow[a_settle_type] == 3) echo "selected"; ?>>지사단말기
																<option value="4" <? if($rrow[a_settle_type] == 4) echo "selected"; ?>>웹결제
															</select>
														</td>
														<td colspan="2" style="border: 1px solid #222;padding: 5px;">판매금액</td>
														<td style="background: #fff;padding: 5px;text-align: left;">&nbsp;<?=$rrow[a_airline_amt]?></td>
														<td colspan="2" style="border: 1px solid #222;padding: 5px;">발권처</td>
														<td colspan="2" style="background: #fff;padding: 5px;text-align: left;"><select name=rand_id_air[] class=form-control >
																  <option value='0' >선 택
																	<?= printRandSelectAirlie($rrow[rand_id]); ?>
															  </select></td>
														<td colspan=2 style="border: 1px solid #222;padding: 5px;">항공수익 </td>
														<td  style="background: #fff;padding: 5px;text-align: left;">&nbsp;<?=$rrow[a_air_amt]?></td>
														
													</tr >
													
												</tbody>
											</table>
												
						<?php
										$i++;
						                endwhile;
									} 
						?>

					</div>
				</div>
				
				
		  </div>
	
	</table>
	<div id="invoice" style="page-break-before: always;">

		<div class="invoice overflow-auto">
			<div style="min-width: 600px">
				
				<main>
				   <div class="row contacts">
						<div class="col invoice-center">
							<h2 style='text-align:center;font-weight: 700;'>정산내역
							</h2>
						</div>
					</div>
					
					<br />
					
					<div class="row tour-details">
						<div class="col-md-12 invoice-to">
							<h2 class="invoice-to">예약내역 ㅣTour Details</h2>
						</div>
					</div>
					<table style='width: 100%;line-height: inherit;text-align: left;border: 1px solid #222;font-size: 13px;'>
						<thead>
							 <tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border: 1px solid #222;">
							
								<th style="border: 1px solid #222;">여행상품<h6 style="margin-bottom: .3rem !important;padding-top:1px ;line-height: .5"><font size =1>Tour Package</h6></th>
								<th style="border: 1px solid #222;">출발일<h6 style="margin-bottom: .1rem !important;line-height: .5"><font size =1>Departure</h6></th>
								<th style="border: 1px solid #222;">도착일<h6 style="margin-bottom: .1rem !important;line-height: .5"><font size =1>Arrival</h6></th>
								<th style="border: 1px solid #222;">인원<h6 style="margin-bottom: .1rem !important;line-height: .5"><font size =1>Travelers</h6></th>
								<th style="border: 1px solid #222;">투어비<h6 style="margin-bottom: .1rem !important;line-height: .5"><font size =1>Total Price</h6></th>
							</tr>
						</thead>
						<tbody>
							<tr style="background: #fff;font-weight: 400;text-align: center;">
								<td style='text-align: left;border: 1px solid #222;padding: 5px;'><?=$revInfo[p_name]?></td>
								<td style='text-align: center;border: 1px solid #222;'><?=$revInfo[stDate]?>(<?=$seweekday?>)</td>
								<td style='text-align: center;border: 1px solid #222;'><?=$revInfo[edDate]?>(<?=$eeweekday?>)</td>
								<td style='text-align: center;border: 1px solid #222;'><?=$revInfo[p_cnt]?></td>
								<td style='text-align: right;border: 1px solid #222;' width="18%"><?=$sign?> <?php echo number_format($lasttot,2);?></td>
							</tr>
							<tr style="background: #fff;font-weight: 400;text-align: center;">
								<td style='text-align: left;border: 1px solid #222;padding: 5px;'>항공금액</td>
								<td style='text-align: center;border: 1px solid #222;'></td>
								<td style='text-align: center;border: 1px solid #222;'></td>
								<td style='text-align: center;border: 1px solid #222;'></td>
								<td style='text-align: right;border: 1px solid #222;padding: 5px;' width="18%"><?=$sign?>  <?php echo number_format($airamt[samt],2);?></td>
							</tr>
							<tr style="background: #fff;font-weight: 400;text-align: center;">
								<td style='text-align: left;border: 1px solid #222;padding: 5px;'>추가금액</td>
								<td style='text-align: center;border: 1px solid #222;'></td>
								<td style='text-align: center;border: 1px solid #222;'></td>
								<td style='text-align: center;border: 1px solid #222;'></td>
								<td style='text-align: right;border: 1px solid #222;padding: 5px;' width="18%"><?=$sign?>  <?php echo number_format($lastadd,2);?></td>
							</tr>
							<tr style="background: #fff;font-weight: 400;text-align: center;">
								<td style='text-align: left;border: 1px solid #222;padding: 5px;'>할인금액</td>
								<td style='text-align: center;border: 1px solid #222;'></td>
								<td style='text-align: center;border: 1px solid #222;'></td>
								<td style='text-align: center;border: 1px solid #222;'></td>
								<td style='text-align: right;border: 1px solid #222;padding: 5px;' width="18%"><?=$sign?>  <?php echo number_format($disamt[amt],2);?></td>
							</tr>
							
							<tr>
								<td style='text-align: left; padding: 5px;'><span ><b>최종 결제금액</b></span></td>
								<td colspan="3" style='text-align: center; '></td>
								<td style='text-align: right;font-weight: bold;font-size: 15px;' width="18%"><?=$sign?> <?php echo number_format($totamt,2);?>&nbsp;</td>
							</tr>
							
						</tbody>
					</table>
					<br />
					<div class="row tour-details">
						<div class="col-md-12 invoice-to">
							<h2 class="invoice-to">결제내역 ㅣPayments</h2>
						</div>
					</div>
					<table style='width: 100%;line-height: inherit;text-align: left;border: 1px solid #222;font-size: 13px;'>
						<thead>
							 <tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border: 1px solid #222;">
							
								<th width="25%" style="border: 1px solid #222;">결제일<h6 style="margin-bottom: .3rem !important;padding-top:1px ;line-height: .5"><font size =1>Date</h6></th>
								<th width="15%" style="border: 1px solid #222;">결제방법<h6 style="margin-bottom: .3rem !important;padding-top:1px ;line-height: .5"><font size =1>Method</h6></th>
								<th width="25%"style="border: 1px solid #222;" >결제금액<h6 style="margin-bottom: .3rem !important;padding-top:1px ;line-height: .5"><font size =1>Paid Amount</h6></th>
								<th width="5%"style="border: 1px solid #222;" >결제상태<h6 style="margin-bottom: .3rem !important;padding-top:1px ;line-height: .5"><font size =1>Paid Status</h6></th>
								<th width="10%" style="border: 1px solid #222;">담당자<h6 style="margin-bottom: .3rem !important;padding-top:1px ;line-height: .5"><font size =1>Agent</h6></th>
							</tr>
						</thead>
						<tbody>
						<?php
									$qryr = "select * from payment_history where reserveCode = '$r_code' && pay_method !='init' && payment_status != 'RRQUEST'  order by seq_no asc";
									//echo $qryr;
									$rstr = $dbConn->query($qryr);
									$cntr= $rstr->num_rows;
									$i = 0;
									if ($cntr > 0) {
										while($row = $rstr->fetch_assoc()):
										  
										   $rate = "";
										   switch ($row[pay_method])
										   {
												case "cash" : 
													$cappay = "현금";
													break;   
												case "creditcard" : 
													$cappay = "신용카드웹";
													break;
												case "debitcard" : 
													$cappay = "데빗";
													break;
												
												case "bcreditcard" : 
													$cappay = "신용카드 자사단말기";
													break; 
												case "check" : 
													$cappay = "체크";
													break; 
												case "banktransfer" : 
													$cappay = "은행송금";
													break; 
												case "airsys" : 
													$cappay = "항공시스템";
													break;
												case "gift" : 
													$cappay = "기프트(상품권)";
													break;
												case "fundtransfer" : 
													$cappay = "금액이동";
													break; 
												default : 
													$cappay = "";
													break; 
												
											}
											if ($row[b_rate] == "CAD") {
												 
												$amtt=$row[payment] / 1.13 ;
												$tax = $row[payment] - $amtt;
												if ($row[rate_m] != '0.0000') {
												  $rate ="<br />(Rate : $row[rate_m])";
												}
												$sign1 = "C$";

											} else { 
												$tax = 0;
												
												if ($row[rate_m] != '0.0000') {
												  $rate ="<br />(Rate : $row[rate_m])";
												}
												$sign1 = "U$";
											}
											if ($row[payment_status] == "RETURN") {
												$pamt = "<font color=red>-".$sign." ".$row[payment]."</font>";
												$cappay = "환불";
												$row[pay_info] = "환불완료";
											} else {

												$pamt = $sign." ".$row[payment];
											}
											$pay_dbinfo = getinfo_dbMember($row[register]);

											if ($row[payment_status] == "RETURN") {
												$pamt1 = "<font color=red>-".$sign1." ".$row[rate_payment]."</font>";
												$totpay=$totpay - $row[payment] ;
											} else {
												$totpay=$totpay + $row[payment] ;
												$pamt1 = $sign1." ".$row[rate_payment];
											}
											$pay_dbinfo = getinfo_dbMember($row[register]);

						?>
							<tr style="background: #fff;font-weight: 400;text-align: center;">
								<td style='text-align: left;border: 1px solid #222;padding: 5px;'><?=$row[wdate]?></td>
								<td style='text-align: center;border: 1px solid #222;'><?php echo $cappay; ?></td>
								<td width="20%" style='text-align: center;border: 1px solid #222;'><?=$pamt?><?php if ($row[pay_method]=="creditcard") { echo "<br> (".$row[pay_info].")";  }?></td>
								<td style='text-align: center;border: 1px solid #222;' width="20%"><?=$row[payment_status]?></td>
								<td style='text-align: center;border: 1px solid #222;' width="20%"><?=$pay_dbinfo[kor_name]?></td>
							</tr>
							
						<?php
										$i++;
						                endwhile;
									
									} else {
						?>

						<?php
						              
									}
						?>
							<tr style='border-bottom: 1px solid #222;'>
								<td style='text-align: left; padding: 5px;'><span >TOTAL PAID</span></td>
								<td  style='text-align: center; '></td>
								<td  style='text-align: center; '><?=$sign?> <?php echo number_format(
								$totpay,2);?></td>
								<td style='text-align: right;' width="18%" colspan='2'></td>
							</tr>
							<tr>
								<td style='text-align: left; padding: 5px;'><span ><b>BALANCE DUE</b></span></td>
								<td  style='text-align: center; '></td>
								<td  style='text-align: center; '><b><?=$sign?> <?php echo number_format(
								$revInfo[last_bal],2);?></b></td>
								<td style='text-align: right;' width="18%" colspan='2'></td>
							</tr>
						</tbody>
					</table>
					
					<br/>
					<?php

						 $cc_pmt_fee_qry = "select sum(payment * 0.03) as cc_proc_fee from payment_history where reserveCode = '$r_code' && payment_status = 'DONE' && pay_method = 'bcreditcard'";
						 $cc_pmt_fee_rst = $dbConn->query($cc_pmt_fee_qry);
						 $row2 = $cc_pmt_fee_rst->fetch_assoc();
						 $cc_pmt_fee1 = $row2[cc_proc_fee];
						 $cc_pmt_fee1 = round($cc_pmt_fee1, 2);
						 
						 

						 $cc_pmt_fee_qry = "select sum(payment * 0.03) as cc_proc_fee from payment_history where reserveCode = '$r_code' && payment_status = 'DONE' && pay_method = 'creditcard'";
						 $cc_pmt_fee_rst = $dbConn->query($cc_pmt_fee_qry);
						 $row2 = $cc_pmt_fee_rst->fetch_assoc();
						 $cc_pmt_fee2 = $row2[cc_proc_fee];
						 
						 $cc_pmt_fee2 = round($cc_pmt_fee2, 2);
						 
						 $cc_pmt_fee = $cc_pmt_fee1 + $cc_pmt_fee2;


						 $rt_pmtqry = "select sum(payment) as rtamt from payment_history where reserveCode = '$r_code' && payment_status = 'RETURN' ";
						 $rt_pmtrst = $dbConn->query($rt_pmtqry);
						 $row2 = $rt_pmtrst->fetch_assoc();
						 $rtamt2 = $row2[rtamt];
						 
						 $rtamt = round($rtamt2, 2);

                         $tot_expense= $airamt[samt]+ $randamt[amt]+$rtamt+$cc_pmt_fee;
						 $totprofit = $totamt - $tot_expense;
						 //echo $tot_expense."|".$totprofit;

					?>
					<div class="row tour-details">
						<div class="col-md-12 invoice-to">
							<h2 class="invoice-to">지출내역 | Expenses</h2>
						</div>
					</div>
						
					<div style="margin-top: 15px;padding-left: 0px !important;">
						
						<div class="row">
						   <table style='width: 100%;line-height: inherit;text-align: left;border: 1px solid #222;font-size: 13px;'>
								<thead>
									 <tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border: 1px solid #222;">
									
										<th width="15%" style="border: 1px solid #222;">항공사지출</th>
										<th width="15%" style="border: 1px solid #222;">단말기지출</th>
										<th width="15%"style="border: 1px solid #222;" >기타지출</th>
										<th width="15%"style="border: 1px solid #222;" >랜드사지출</th>
										<th width="15%" style="border: 1px solid #222;">에이젼트수익</th>
										<th width="15%" style="border: 1px solid #222;">환불합계</th>
									</tr>
									</tr>
								</thead>
								<tbody>
									<tr style="background: #fff;font-weight: 400;text-align: center;">
										<td style='text-align: left;border: 1px solid #222;padding: 5px;color: #222 !important;'><?=$sign?>  <?php echo number_format($airamt[samt],2);?></td>
										<td style='text-align: center;border: 1px solid #222;color: #222 !important;'><?=$sign?><?=$cc_pmt_fee?></td>
										<td width="20%" style='text-align: center;border: 1px solid #222;color: #222 !important;'><?=$sign?>  <?php echo number_format($disamt[amt],2);?></td>
										<td style='text-align: center;border: 1px solid #222;color: #222 !important;' width="20%"><?=$sign?>  <?php echo number_format($randamt[amt],2);?></td>
										<td style='text-align: center;border: 1px solid #222;color: #222 !important;' width="20%"><?=$sign?>  <?php echo number_format($ageamt[amt],2);?></td>
										<td style='text-align: center;border: 1px solid #222;color: #222 !important;' width="20%"><?=$sign?>  <?php echo number_format($rtamt,2);?></td>
									</tr>
							   </tbody>
							</table>
							
						</div>
						
						
					</div>
					
					<div class="row tour-details">
						<div class="col-md-12 invoice-to">
							<h2 class="invoice-to">총수익 | Profit</h2>
						</div>
					</div>
						
					<div style="margin-top: 15px;padding-left: 0px !important;">
						
						<div class="row">
						   <table style='width: 100%;line-height: inherit;text-align: left;border: 1px solid #222;font-size: 13px;'>
								<thead>
									 <tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border: 1px solid #222;">
									
										<th width="15%" style="border: 1px solid #222;">판매가</th>
										
										<th width="15%"style="border: 1px solid #222; color:red" >판매대비 총수익</th>
										
									</tr>
									</tr>
								</thead>
								<tbody>
									<tr style="background: #fff;font-weight: 400;text-align: center;">
										<td style='text-align: left;border: 1px solid #222;padding: 5px;'><b><?=$sign?>  <?php echo number_format($totamt,2);?></b></td>
										
										<td width="20%" style='text-align: center;border: 1px solid #222;color: red !important;'><b><?=$sign?>  <?php echo number_format($totprofit,2);?></b></td>
										
									</tr>
							   </tbody>
							</table>
							
						</div>
						
						
					</div>
				</main>
			</div>
			<div></div>
		</div>
	</div>
	
 </div>
		
		
	
</form>

    <script src="ckeditor/ckeditor.js"></script>
	<script>
	    $(document).ready(function () {
			window.print();	
		});
		
	</script>
</body>
</html>	

 