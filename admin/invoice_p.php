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
	$totamt = $revInfo[last_total];// - $disamt[amt];
	if ($revInfo[base_rate] == "CAD") {
		$pricep = $totamt/1.13;
		$taxp = $totamt - $pricep;
	} 
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
	
	$qry6 = "select * from html_page where id = 'alert_g'";
	$rst6 = $dbConn->query($qry6);
	$row6 = $rst6->fetch_assoc();
	$alert3 = "$row6[content]";

	$lasttot = $revInfo[last_sale] + $revInfo[last_add];
	function tourplist()
	{
		 global $dbConn,$r_code;
		 $qry1="select * from reserve_traveler where reserveCode='$r_code' order by seqint asc";
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
			echo "<tr style='border: 1px solid #aaa;'>
						<td style='padding: 10px;text-align:center;border: 1px solid #aaa;'>$k</td>
						<td style='text-align:center;border: 1px solid #aaa;padding: 5px;'>$row1[traveler_nm]</td>
						<td style='text-align:center;border: 1px solid #aaa;padding: 5px;'>$sexcap</td>
						<td style='text-align:center;border: 1px solid #aaa;padding: 5px;'>$rcap</td>
						<td style='text-align:center;border: 1px solid #aaa;padding: 5px;'>$picknm[pick_name] $picknm[pick_time] - $picknm[pick_1desc]</td>
					</tr>";
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
	<link href="css/invoice-f2.css" rel="stylesheet" id="invoice-css">

	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
	
</head>
<style type="text/css" media="print">
  @media print {
  body {-webkit-print-color-adjust: exact;}
  }
	@page {
		size:auto;
		margin-left: 0px;
		margin-right: 10px;
		margin-top: 10px;
		margin-bottom: 0px;
		margin: 0;
		-webkit-print-color-adjust: exact;
	}
</style>

<body>
	<!-- book info-->

<br />
<div style="text-align: center;margin-bottom:-10px;margin-top:10px;"><h2>예약내역</h2></div>
<br />
<form name=print id=print action='<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&r_code=<?=$r_code?>' method=post enctype="multipart/form-data">
  <input type=hidden name=r_code value="<?= $r_code ?>">
  <input type=hidden name=mode value="send_email">
	<div id="invoice1">
		<div class="text-center confim_book">예약이 완료되었습니다.</div>
		<br/>
		
		<div class="invoice1 overflow-auto">
			<div style="min-width: 400px !important;">
			  <main>
			    
				<div class="text-left book_header">1. 예약자 정보</div>
				

				<?php if ($revInfo[pricet] == 3) { ?>
				<table style='width: 100%;line-height: 18px;text-align: left;border: 1px solid #aaa;font-size: 13px;'>
					<tbody>
						 <tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border: 1px solid #aaa;">
							<td colspan="2"  style="border: 1px solid #aaa;padding: 10px;">업체명</td>
							<td colspan="6" style="background: #fff;padding: 5px;text-align: left;"><?=$rname[kor_name]?></td>
							<td colspan="2"  style="border: 1px solid #aaa;padding: 10px;">담당자명</td>
							<td colspan="6" style="background: #fff;padding: 5px;text-align: left;"><?=$revInfo[book_pri]?></td>
						</tr>
						<tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border: 1px solid #aaa;">
							<td colspan="2"  style="text-align: center;border: 1px solid #aaa; padding: 10px;">이메일</td>
							<td colspan="6" style="background: #fff;padding: 5px;text-align: left;"><?=$revInfo[book_email]?></td>
							<td colspan="2"  style="text-align: center;border: 1px solid #aaa;">연락처</td>
							<td colspan="6" style="background: #fff;padding: 5px;text-align: left;"><?=$revInfo[book_phone]?></td>
						</tr>
					</tbody>
				</table>

				<?php } else { ?>
				<table style='width: 100%;line-height: 18px;text-align: left;border: 1px solid #aaa;font-size: 13px;'>
					<tbody>
						 <tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border: 1px solid #aaa;">
							<td colspan="2"  style="border: 1px solid #aaa;padding: 10px;">예약자명</td>
							<td colspan="14" style="background: #fff;padding: 5px;text-align: left;"><?=$revInfo[book_pri]?></td>
						</tr>
						<tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border: 1px solid #aaa;">
							<td colspan="2"  style="text-align: center;border: 1px solid #aaa; padding: 10px;">이메일</td>
							<td colspan="6" style="background: #fff;padding: 5px;text-align: left;"><?=$revInfo[book_email]?></td>
							<td colspan="2"  style="text-align: center;border: 1px solid #aaa;">연락처</td>
							<td colspan="6" style="background: #fff;padding: 5px;text-align: left;"><?=$revInfo[book_phone]?></td>
						</tr>
					</tbody>
				</table>
				<?php }  ?>
				
				<!-- 여행 예약정보 -->
				<div class="text-left book_header">2. 여행 예약 정보</div>
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
							<td colspan="2" style="border: 1px solid #aaa;padding: 10px;">여행비용</td>
							<td colspan="14" style="background: #fff;padding: 5px;text-align: left;"><?=$revInfo[base_rate]?> <?php echo number_format($revInfo[last_total]);?> (세금포함)  </td>
						</tr>
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
						<tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border: 1px solid #aaa;">
							<td colspan="2" style="border: 1px solid #aaa;padding: 10px;">포함사항</td>
							<td colspan="14" style="background: #fff;padding: 5px;text-align: left;"><?=nl2br($prodInfo[p_include])?></td>
						</tr >
						<tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border: 1px solid #aaa;">
							<td colspan="2" style="border: 1px solid #aaa;padding: 10px;">불포함사항</td>
							<td colspan="14" style="background: #fff;padding: 5px;text-align: left;"><?=nl2br($prodInfo[p_uninclude])?></td>
						</tr>
						<tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border: 1px solid #aaa;">
							<td colspan="2" style="border: 1px solid #aaa;padding: 10px;">일반주의사항</td>
							<td colspan="14" style="background: #fff;padding: 5px;text-align: left;"><?=$alert3?>
							</td>
						</tr>
						<tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border: 1px solid #aaa;">
							<td colspan="2" style="border: 1px solid #aaa;padding: 10px;">준비물</td>
							<td colspan="14" style="background: #fff;padding: 5px;text-align: left;"><?=nl2br($prodInfo[p_prepare])?>
							</td>
						</tr>
						<!--<tr>
							<td colspan="2" class="active text-center formHeader">국경 통과시 필요서류</td>
							<td colspan="14" class="line_height"><span class="font_bold">한국 국적 고객님 포함, 미국비자면제프로그램(VWP)에 해당되는 국적의 고객님들은 미국입국시 아래의 내용에 따라 적절한 서류를 준비해주셔야 합니다. </span> <br/>
								- 항공편을 이용하여 미국 입국시에는 ESTA(미국 무비자 입국허가증) 사전승인을 받으셔야 합니다.<br/>
								- 캐나다에서 출발하여 육로를 통해 미국 투어상품을 계획하시는 경우 ESTA 사전 승인 절차가 필수는 아니며, 미국입국세 U$6 이 발생합니다. <br/>
								<span class="font_bold"> 구비서류 </span> <br/>
								- 시민권자 : 여권 <br/>
								- 영주권자 : 전자여권(또는 구 여권+미국비자)+P.R Card / 미국 입국세 U$6 <br/>
								- 캐나다 체류비자 소지자 : 전자여권(또는 구 여권+미국비자)+캐나다 체류비자 / 미국 입국세 U$6 <br/>
								- 한국에서 오신 방문객 : 전자여권(또는 구 여권+미국비자)+한국행 리턴티켓 / 미국 입국세 U$6 <br/>
								- 전자여권은 여권 겉 페이지 앞면 하단에 칩 표시가 되어있으며 여권번호가 ‘M+숫자’로 구성되어 있습니다. 알파벳 두 개로 시작하는 것은 구여권입니다.<br/>
								- 부모를 동반하지 않는 미성년자의 미국 여행시, 사전에 ‘부모여행동의서’와 부모님의 여권 사본을 준비해주셔야 합니다. <br/>
								- 출발 전에 여권의 만기일을 꼭 확인하시기 바랍니다. 여권 만기일로부터 6개월 이상 남아있어야 합니다.
							</td>
						</tr>-->
					</tbody>
				</table>	
				<!-- 여행자 정보 -->
				<div class="text-left book_header">3. 여행자 정보</div>
				<div class="row">
						<table style='width: 100%;line-height: 18px;text-align: left;border: 1px solid #aaa;font-size: 13px;margin-right:10px;'>
							<tbody>
								<tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border: 1px solid #aaa;">
									<th style="padding: 10px;border: 1px solid #aaa;" width='5%'>NO.</th>
									<th style="border: 1px solid #aaa;" width='15%'>성명</th>
									<th style="border: 1px solid #aaa;" width='15%'>성별</th>
									<th style="border: 1px solid #aaa;" width='15%'>객실</th>
									<th style="border: 1px solid #aaa;">탑승지</th>
								</tr>
							
							
								<?=tourplist()?>
								
							</tbody>
						</table>
					</div>
				</div>
				
				<div class="text-left book_header">4. 추가 정보</div>
				<div class="row">
						
					<div class="col-sm-12" ><?=Trim($board_note)?></div>
				</div>
			  </main>
			</div>
		  </div>
	</div>
	<?php if ($revInfo[pricet] != 3) { ?>
	<!-- invoice page -->
	<div id="invoice" style="margin-top:30px; 0;page-break-before: always;">
		<div class="invoice overflow-auto">
			<div style="min-width: 600px">
				<header>
					<div class="row">
						<!--<div class="col-sm-3">
							<a target="_blank" href="#">
								<img src="/img/logo.jpg" data-holder-rendered="true" height="64px" width="100%" />
							</a>
						</div>-->
						<div class="col-sm-12 company-details" >
							<!--<div><B>캐나다본사: </B> 5633 Yonge Street, North York, ON M2M 3S9,TEL: 416-223-7767, 070-7752-1311,FAX: 416-223-7789 </div>
							<div><B>한국사무소: </B> 서울 종로구 종로 19, A동 714호 종로1가, 르메이에르 종로타운1, TEL: 02-720-7767, FAX: 02-720-7769 </div>
							<div>GST Registration No.  8574 12191RT0001 www.parantours.com </div>
							<div>TICO Registration No. 50015723  KATALK: 파란여행 admin@parantours.com </div>-->
							<img src="img/top_in.jpg" data-holder-rendered="true" height="100%" width="100%"/>

						</div>
					</div>
				</header>
				<main>
				    <div class="row contacts">
						<div class="col invoice-center">
							<h2 style='text-align:center;font-weight: 900;'>INVOICE
							</h2>
						</div>
					</div>
					<div class="row contacts">
						<div class="col invoice-to">
							<h2 class="invoice-to">고객정보 | Customer(s)</h2>
							<h2 class= "no-color"><b><?=$revInfo[book_pri]?></b> 님</h2>
							<div><?=$revInfo[book_phone]?></div>
							
						</div>
						
						<div class="col invoice-details">
							<h5 class="invoice-id">예약번호 : <?=$r_code?>
							</h5>
						</div>
					</div>
					<br />
					<div class="row tour-details">
						<div class="col-md-12 invoice-to">
							<h2 class="invoice-to">예약내역 ㅣTour Details</h2>
						</div>
					</div>
					<table style='width: 100%;line-height: inherit;text-align: left;border: 1px solid #aaa;font-size: 13px;'>
						<thead>
							 <tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border: 1px solid #aaa;">
							
								<th style="border: 1px solid #aaa;">여행상품<h6 style="margin-bottom: .3rem !important;padding-top:1px ;line-height: .5"><font size =1>Tour Package</h6></th>
								<th style="border: 1px solid #aaa;">출발일<h6 style="margin-bottom: .1rem !important;line-height: .5"><font size =1>Departure</h6></th>
								<th style="border: 1px solid #aaa;">도착일<h6 style="margin-bottom: .1rem !important;line-height: .5"><font size =1>Arrival</h6></th>
								<th style="border: 1px solid #aaa;">인원<h6 style="margin-bottom: .1rem !important;line-height: .5"><font size =1>Travelers</h6></th>
								<th style="border: 1px solid #aaa;">투어비<h6 style="margin-bottom: .1rem !important;line-height: .5"><font size =1>Total Price</h6></th>
							</tr>
						</thead>
						<tbody>
							<tr style="background: #fff;font-weight: 400;text-align: center;">
								<td style='text-align: left;border: 1px solid #aaa;padding: 5px;'><?=$revInfo[p_name]?></td>
								<td style='text-align: center;border: 1px solid #aaa;'><?=$revInfo[stDate]?>(<?=$seweekday?>)</td>
								<td style='text-align: center;border: 1px solid #aaa;'><?=$revInfo[edDate]?>(<?=$eeweekday?>)</td>
								<td style='text-align: center;border: 1px solid #aaa;'><?=$revInfo[p_cnt]?></td>
								<td style='text-align: right;border: 1px solid #aaa;' width="18%"><?=$sign?> <?php echo number_format($lasttot,2);?>&nbsp;</td>
							</tr>
							<tr style="background: #fff;font-weight: 400;text-align: center;">
								<td style='text-align: left;border: 1px solid #aaa;padding: 5px;;'>할인금액</td>
								<td style='text-align: center;border: 1px solid #aaa;'></td>
								<td style='text-align: center;border: 1px solid #aaa;'></td>
								<td style='text-align: center;border: 1px solid #aaa;'></td>
								<td style='text-align: right;border: 1px solid #aaa;' width="18%"><?=$sign?>  <?php echo number_format($disamt[amt],2);?>&nbsp;</td>
							</tr>
							
							

							
							<tr>
								<td style='text-align: left; padding: 5px;font-weight: 900;'><span ><b>최종 결제금액</b></span></td>
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
					<table style='width: 100%;line-height: inherit;text-align: left;border: 1px solid #aaa;font-size: 13px;'>
						<thead>
							 <tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border: 1px solid #aaa;">
							
								<th width="25%" style="border: 1px solid #aaa;">결제일<h6 style="margin-bottom: .3rem !important;padding-top:1px ;line-height: .5"><font size =1>Date</h6></th>
								<th width="15%" style="border: 1px solid #aaa;">결제방법<h6 style="margin-bottom: .3rem !important;padding-top:1px ;line-height: .5"><font size =1>Method</h6></th>
								<th width="30%"style="border: 1px solid #aaa;" >결제금액<h6 style="margin-bottom: .3rem !important;padding-top:1px ;line-height: .5"><font size =1>Paid Amount</h6></th>
								
								<th width="10%" style="border: 1px solid #aaa;">담당자<h6 style="margin-bottom: .3rem !important;padding-top:1px ;line-height: .5"><font size =1>Agent</h6></th>
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
								<td style='text-align: left;border: 1px solid #aaa;padding: 5px;'><?=$row[wdate]?></td>
								<td style='text-align: center;border: 1px solid #aaa;'><?php echo $cappay; ?></td>
								<td width="20%" style='text-align: center;border: 1px solid #aaa;'><?=$pamt?><?php if ($row[pay_method]=="creditcard") { echo "<br> (".$row[pay_info].")";  }?></td>
								
								<td style='text-align: center;border: 1px solid #aaa;' width="20%"><?=$pay_dbinfo[kor_name]?></td>
							</tr>
							
						<?php
										$i++;
						                endwhile;
									
									} else {
						?>

						<?php
						              
									}
						?>
							<tr style='border-bottom: 1px solid #aaa;'>
								<td style='text-align: left; padding: 5px;'><span >TOTAL PAID</span></td>
								<td  style='text-align: center; '></td>
								<td  style='text-align: center; '><?=$sign?> <?php echo number_format(
								$totpay,2);?></td>
								<td style='text-align: right;' width="18%"></td>
							</tr>
							<tr>
								<td style='text-align: left; padding: 5px;'><span ><b>BALANCE DUE</b></span></td>
								<td  style='text-align: center; '></td>
								<td  style='text-align: center; '><?=$sign?> <?php echo number_format($revInfo[last_bal],2);?></td>
								<td style='text-align: right;' width="18%"></td>
							</tr>
						</tbody>
					</table>
					
					<br/>
					<div class="row tour-details">
						<div class="col-md-12 invoice-to">
							<h2 class="invoice-to">변경 및 취소규정 | Changes & Cancellation</h2>
						</div>
					</div>
						
					<div style="margin-top: 15px;padding-left: 0px !important;">
						
						<div class="row">
						    <?php if ($prodInfo[p_type]=="5") { 
						     echo $alert2;

							} else {
								  echo $alert1;

							}
							?>
						</div>
						
					
					</div>
					
				</main>
			</div>
			<div></div>
		</div>
	</div>
	<?php }  ?>
</form>
    <script src="ckeditor/ckeditor.js"></script>
	<script>
	    $(document).ready(function () {
			window.print();	
		});
		
	</script>
</body>
</html>	

 