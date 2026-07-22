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
	$totamt = $revInfo[last_total] ;//- $disamt[amt];
	//$lasttot = $revInfo[last_sale] + $revInfo[last_add];
	$lasttot =$revInfo[last_sale];
	$airamt = getAirlineSum($r_code);
	$lastadd = $revInfo[last_add];
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
	
	$qry6 = "select * from html_page where id = 'alert'";
	$rst6 = $dbConn->query($qry6);
	$row6 = $rst6->fetch_assoc();
	$alert = "$row6[content]";

	function printCustomer() {
		global $dbConn, $division, $randSelection,$r_code;

		

		$qry1 = "select seq_no,send_reg,subject,sent_on from mailing_history where reserveCode='$r_code' order by sent_on desc";
		$rst1 = $dbConn->query($qry1);
	

		while($row1 = $rst1->fetch_assoc()){
		
			
					echo "<tr bgcolor=#FFFFFF>
					<td height=25 style='text-align: center;border: 1px solid #aaa;font-weight: bold;'>&nbsp;$row1[send_reg]</td>
					<td style='text-align: center;border: 1px solid #aaa;'><a href=javascript:viewmail('$r_code','$row1[seq_no]') >$row1[subject]</a></td>
					<td style='text-align: center;border: 1px solid #aaa;font-weight: bold;'>$row1[sent_on]</td>
					</tr>";
				
		}
		
	}
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
			echo "<tr style='font-weight: bold;border: 1px solid #aaa;'>
						<td style='padding: 10px;text-align:center;border: 1px solid #aaa;'>$k</td>
						<td style='text-align:center;border: 1px solid #aaa;padding: 5px;'>$row1[traveler_nm]<br />$row1[traveler_enm]</td>
						<td style='text-align:center;border: 1px solid #aaa;padding: 5px;'>$sexcap</td>
						<td style='text-align:center;border: 1px solid #aaa;padding: 5px;'>$row1[traveler_birth]</td>
						<td style='text-align:center;border: 1px solid #aaa;padding: 5px;'>$row1[pass_date]</td>
						<td style='text-align:center;border: 1px solid #aaa;padding: 5px;'>$row1[pass_num]</td>
						<td style='text-align:center;border: 1px solid #aaa;padding: 5px;'>$row1[traveler_room]</td>
						<td style='text-align:center;border: 1px solid #aaa;padding: 5px;'>$row1[traveler_phone]</td>
						<td style='text-align:center;border: 1px solid #aaa;padding: 5px;'>$row1[e_memo]</td>
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
<div style="text-align: center;margin-bottom:-10px;margin-top:10px;"><h2>확정예약내역</h2></div>

<br />
<form name=print id=print action='<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&r_code=<?=$r_code?>' method=post enctype="multipart/form-data">
  <input type=hidden name=r_code id='r_code' value="<?= $r_code ?>">
  <input type=hidden name=mode id="mode" value="send_email">
	<div id="invoice1">
		<div class="text-center confim_book">예약이 확정되었습니다.</div>
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
				<div class="text-left book_header">2. 여행자 정보</div>
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
				<div class="text-left book_header">- 추가 정보(항공정보 포함)</div>
				<div class="row">
					
					<div class="col-sm-12" ><?=Trim($board_note)?></div>
				</div>
				
				
		  </div>
		  <!-- invoice page -->
	<div id="invoice" style="max-width: 100%; margin: auto;padding: 10px; border: 0px solid #eee;  box-shadow: 0 0 0px rgba(0, 0, 0, 0); font-size: 12px;line-height: 20px;font-family: 'Nanum Gothic', sans-serif;height:100%; color: #555;">
		
			<div style="min-width: 600px">
				<header style=" margin-bottom: 10px;  border-bottom: 1px solid #3989c6">
					<div class="row">
						<div width='100%' >
							<!--<a target="_blank" href="#">
								<img src="http://parantours.biz/img/logo.jpg" data-holder-rendered="true" width="30%" height="64px"/>
								
							</a>-->
							<span style="text-align: right;font-size:12px;">
								<img src="http://dongbutourchina.com/img/top_in.jpg" data-holder-rendered="true"  height="120px" width="100%"/>
							</span>
						</div>
						
					</div>
				</header>
				<main style="padding-bottom: 50px;">
				    <div style="text-align: center;
    margin-right: -15px;
    margin-left: -15px;margin-bottom: 20px;">
						<div style="text-align: center;
	margin-left: 0;
	font-family:'Open San',sans-serif;
	font-size:14px;">
							<h2 style='font-size:20px !important;text-align:center;font-weight: 900 !important;'>INVOICE
							</h2>
						</div>
	                </div>
					<div style="display: flex;flex-wrap: wrap;
    margin-right: -15px;
    margin-left: -15px;margin-bottom: 20px;">
						<div style="flex-basis: 0;flex-grow: 1;max-width: 100%;text-align: left;
    font-family: 'Open San',sans-serif;font-size: 13px;position: relative;
    width: 100%;min-height: 1px; padding-right: 15px; padding-left: 15px;">
							<h2 style="margin-top: 0;color: #3989c6;font-size: 15px !important; font-weight: 900;">고객정보 | Customer(s)</h2>
							<h2 style= "font-size: 15px !important;color: #212529 !important;
    font-weight: normal !important;margin-bottom: .5rem;font-weight: 500;line-height: 1.2;margin-top: 0;"><b><?=$revInfo[book_pri]?></b> 님</h2>
							<div><?=$revInfo[book_phone]?></div>
						
						</div>
						
						<div style="flex-basis: 0;
    -ms-flex-positive: 1;
    flex-grow: 1;
    max-width: 100%;position: relative;width: 100%;min-height: 1px;
    padding-right: 15px;padding-left: 15px; text-align: right;
    font-family: 'Open San',sans-serif;
    font-size: 14px;">
							<h5 style="margin-top: 0;color: #3989c6; font-size: 16px;">예약번호 : <?=$r_code?>
							</h5>
						</div>
					</div>
					<br />
					<div style="display: flex;flex-wrap: wrap;margin-right: -15px; margin-left: -15px;margin-bottom: 5px;padding-right: 15px; padding-left: 15px;">
						<div style="text-align: left;
    font-family: 'Open San',sans-serif;
    font-size: 13px;">
							<h2 style="color: #3989c6;margin-bottom: .5rem;    margin-top: 0;
    font-size: 15px !important; font-weight: 900;">예약내역 ㅣTour Details</h2>
						</div>
					</div>
					<table style='width: 100%;line-height: inherit;text-align: left;border: 1px solid #aaa;font-size: 13px;border-collapse: collapse;
    border-spacing: 0;
    margin-bottom: 20px;'>
						<thead>
							 <tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border: 1px solid #aaa;">
							
								<th style="border: 1px solid #aaa;">여행상품<h6 style="margin-bottom: .3rem !important;padding-top:1px ;line-height: .5;margin-top: 0;"><font size =1>Tour Package</h6></th>
								<th style="border: 1px solid #aaa;">출발일<h6 style="margin-bottom: .1rem !important;line-height: .5;margin-top: 0;"><font size =1>Departure</h6></th>
								<th style="border: 1px solid #aaa;">도착일<h6 style="margin-bottom: .1rem !important;line-height: .5;margin-top: 0;"><font size =1>Arrival</h6></th>
								<th style="border: 1px solid #aaa;">인원<h6 style="margin-bottom: .1rem !important;line-height: .5;margin-top: 0;"><font size =1>Travelers</h6></th>
								<th style="border: 1px solid #aaa;">투어비<h6 style="margin-bottom: .1rem !important;line-height: .5;margin-top: 0;"><font size =1>Total Price</h6></th>
							</tr>
						</thead>
						<tbody>
							<tr style="background: #fff;font-weight: 400;text-align: center;border: 1px solid #fff;">
								<td style='text-align: left;border: 1px solid #aaa;padding: 5px;'><?=$revInfo[p_name]?></td>
								<td style='text-align: center;border: 1px solid #aaa;'><?=$revInfo[stDate]?>(<?=$seweekday?>)</td>
								<td style='text-align: center;border: 1px solid #aaa;'><?=$revInfo[edDate]?>(<?=$eeweekday?>)</td>
								<td style='text-align: center;border: 1px solid #aaa;'><?=$revInfo[p_cnt]?></td>
								<td style='text-align: right;border: 1px solid #aaa;' width="18%"><?=$sign?> <?php echo number_format($lasttot,2);?></td>
							</tr>
							<tr style="background: #fff;font-weight: 400;text-align: center;">
								<td style='text-align: left;border: 1px solid #aaa;padding: 5px;'>항공금액</td>
								<td style='text-align: center;border: 1px solid #aaa;'></td>
								<td style='text-align: center;border: 1px solid #aaa;'></td>
								<td style='text-align: center;border: 1px solid #aaa;'></td>
								<td style='text-align: right;border: 1px solid #aaa;padding: 5px;' width="18%"><?=$sign?>  <?php echo number_format($airamt[samt],2);?></td>
							</tr>
							<tr style="background: #fff;font-weight: 400;text-align: center;">
								<td style='text-align: left;border: 1px solid #aaa;padding: 5px;'>추가금액</td>
								<td style='text-align: center;border: 1px solid #aaa;'></td>
								<td style='text-align: center;border: 1px solid #aaa;'></td>
								<td style='text-align: center;border: 1px solid #aaa;'></td>
								<td style='text-align: right;border: 1px solid #aaa;padding: 5px;' width="18%"><?=$sign?>  <?php echo number_format($lastadd,2);?></td>
							</tr>
							<tr style="background: #fff;font-weight: 400;text-align: center;">
								<td style='text-align: left;border: 1px solid #aaa;padding: 5px;;'>할인금액</td>
								<td style='text-align: center;border: 1px solid #aaa;'></td>
								<td style='text-align: center;border: 1px solid #aaa;'></td>
								<td style='text-align: center;border: 1px solid #aaa;'></td>
								<td style='text-align: right;border: 1px solid #aaa;' width="18%"><?=$sign?>  <?php echo number_format($disamt[amt],2);?></td>
							</tr>
							
					
							<tr>
								<td style='text-align: left; padding: 5px;color: #3989c6;font-weight: 900;
    font-style: italic;'><span ><b>최종 결제금액</b></span></td>
								<td colspan="3" style='text-align: center; '></td>
								<td style='text-align: right;color: #3989c6;font-size: 15px;
    font-style: italic;' width="18%"><?=$sign?> <?php echo number_format($totamt,2);?>&nbsp;</td>
							</tr>
							
						</tbody>
					</table>
					<br />
					
					
					<br/>
					<div style="display: flex;flex-wrap: wrap;margin-right: -15px; margin-left: -15px;margin-bottom: 5px;padding-right: 15px; padding-left: 15px;">
						<div style="text-align: left;
    font-family: 'Open San',sans-serif;
    font-size: 13px;">
							<h2 style="color: #3989c6;margin-bottom: .5rem;    margin-top: 0;
    font-size: 15px !important; font-weight: 900;">변경 및 취소규정 | Changes & Cancellation</h2>
						</div>
					</div>
					<div style="font-size: 12px;line-height: 1.9;font-family: 'Roboto',sans-serif;" id=terms>
						
        				<div style="margin-top: 10px;padding-left: 0px !important;">
							
							<div class="row">
							  <?php if ($prodInfo[p_type]=="5") { 
								      echo $alert2;

								} else {
									  echo $alert1;

								}
								?>
							</div>
							
							
						</div>
						
					</div>
					<div class="row tour-details">
						<div class="col-md-12 invoice-to">
							<h2 class="invoice-to">주의사항 및 결제안내</h2>
						</div>
					</div>
						
					<div style="margin-top: 15px;padding-left: 0px !important;">
						
						<div class="row">
						<?php 
							 echo $alert;

						
						?>
							
						</div>
						
						
					</div>
					<!-- 여행참고사항 -->
					
				</main>
			</div>
			<div>
				
		</div>

	</div>
	</table>
 </div>
		
		
	
</form>
<div id="dialog" width="800px" title="Basic dialog">
	    <div name="msg" id="msg" style="width: 800px; height: 600px"></div>
  
</div>
    <script src="ckeditor/ckeditor.js"></script>
	<script>
	    $(document).ready(function () {
			window.print();	
		});
		
	</script>
</body>
</html>	

 