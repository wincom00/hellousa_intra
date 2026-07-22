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
    
	if ($mode == "send_email") {
			$sbj = "[동부투어] $sday ,$prodInfo[p_name] 예약을 확정합니다.";
 
			$content= file_get_contents("http://dongbutour.online/invoice_m2.php?division=3&pdx=2&sub=15&r_code=".$r_code."");

			//echo $content;
		//exit;
            // 메세지
			$board_pds_pos = "/var/www/html/upload";
			$tmpName1 = $_FILES['userfile1']['tmp_name'];
			if(is_uploaded_file($tmpName1)){
				$pds_file1 = $_FILES['userfile1']['name'];
				$attc_name1 = Misc::uploadFileUnsafely($tmpName1 , $pds_file1 , $board_pds_pos);
				
				$fileloc1 = $board_pds_pos . "/" . $attc_name1[savedName];
				
				array_push($atc_arr,$fileloc1);
				$attachment1 = $attc_name1[savedName];
			}
			$tmpName2 = $_FILES['userfile2']['tmp_name'];
			if(is_uploaded_file($tmpName2)){
				$pds_file2 = $_FILES['userfile2']['name'];
				$attc_name2 = Misc::uploadFileUnsafely($tmpName2 , $pds_file2 , $board_pds_pos);
				
				$fileloc2 = $board_pds_pos . "/" . $attc_name2[savedName];
				
				array_push($atc_arr,$fileloc2);
				$attachment2 = $attc_name2[savedName];
			}
			$smail = randname($randD[part_id]);
			//print_r($smail);
			//exit;
			
			$smail = randname($rand_id);
			if ($revInfo[book_email] == "") {
				$cmail = $revInfo[book_email];
			} else {
				$cmail = $smail[company_email];
			}
			///$msg = "* 추가 사항 <br />".$board_note."<br /><br />".$content;
			$msg = str_replace('{ADDINFO}',$board_note,$content);
			$ret= mailsend_h($revInfo[book_email],$sbj,$msg,$attachment1,$attachment2,$attachment3,$attachment4);
			
			
			if (($prodInfo[p_type] == 1)) {
			$ret= mailsend_h('admin@dongbutour.com',$sbj,$msg,$attachment1,$attachment2,$attachment3,$attachment4);
			} else {
			$ret= mailsend_h('admin@dongbutour.com',$sbj,$msg,$attachment1,$attachment2,$attachment3,$attachment4);
			}
			echo "<br><font size=2 color=red><p align=center>이메일 전송완료!</p></font>";
			$qry2 = "insert into mailing_history (division,
												send_reg,
												subject,
												message,
												attach1,
												attach2,
												attach3,
												reserveCode) values ('mailinglist',
																'$user_dbinfo[userid]',
																'$sbj',
																'".addslashes($msg)."',
																'$attc_name1[savedName]',
																'$attc_name2[savedName]',
																'',
																'$r_code')";
																					
			$rst2 = $dbConn->query($qry2);
			
	}
	if ($mode == "print") {
		echo "<meta http-equiv='refresh' content='0; url=./invoice_p.php?division=3&pdx=2&sub=15&r_code=".$r_code."'>";

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
<form name=print id=print action='invoice_page2.php?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&r_code=<?=$r_code?>' method=post enctype="multipart/form-data">
  <input type=hidden name=r_code id='r_code' value="<?= $r_code ?>">
  <input type=hidden name=mode id="mode" value="send_email">
	<div id="invoice1">
		<div class="text-center confim_book">예약이 확정되었습니다.</div>
		<br/>
		
		<? if ($mode != 'print') { ?>
		<div class="row no-nav">
			<div id="custom_button" class="col-sm-12 text-right">
				<button type="button" class="btn btn-xs btn-default js-mail" >이메일 보내기</button>
				<button type="button" class="btn btn-xs btn-default js-print" onclick="pageprint()">프린트</button><br /><br />

			</div>
		</div>
		
	
		<?}?>
		<? if ($mode != 'print') { ?>

		<div class="text-left book_header">*추가내용 정보(항공정보 포함)</div>
		 <table class="table table-bordered table-condensed gridSixteen reserveTable formDetail">
			<tbody>
				<tr>
					<td ><textarea class="form-control js-tripNote js-ckEditor" name="board_note"><?=$body?></textarea></td>
					
				</tr>
				<tr>
					<td >첨부파일 : <input type=file name=userfile1 class="form_box" value="" style="width:600px"></textarea></td>
					
				</tr>
				<tr>
					<td >첨부파일 : <input type=file name=userfile2 class="form_box" value="" style="width:600px"></textarea></td>
					
				</tr>
				
			</tbody>
		</table>
		<?}?>
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
				
				<!-- 여행참고사항 -->
				<br />
				
		  </div>
	    </div>

		
		
		<!-- invoice page -->
	
	<div id="invoice" style="page-break-before: always;">
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
							<img src="/img/top_in.jpg" data-holder-rendered="true" height="80px" width="100%"/>

						</div>
					</div>
				</header>
				<main>
				   <div class="row contacts">
						<div class="col invoice-center">
							<h2 style='text-align:center;font-weight: 700;'>INVOICE
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
								<td style='text-align: left;border: 1px solid #aaa;padding: 5px;'>할인금액</td>
								<td style='text-align: center;border: 1px solid #aaa;'></td>
								<td style='text-align: center;border: 1px solid #aaa;'></td>
								<td style='text-align: center;border: 1px solid #aaa;'></td>
								<td style='text-align: right;border: 1px solid #aaa;padding: 5px;' width="18%"><?=$sign?>  <?php echo number_format($disamt[amt],2);?></td>
							</tr>
							<?php if ($revInfo[base_rate] == "CAD") { ?>
							<tr>
								<td style='text-align: left; padding: 5px;'><span ><b>최종 결제금액</b></span></td>
								<td colspan="2" style='text-align: center; '><b><font size=1.3em>Price </font> <?=$sign?> <?=number_format($pricep,2) ?> &nbsp; + &nbsp;<font size=1.3em>Taxes</font> <?=$sign?> <?=number_format($taxp,2) ?></b></td>
								<td style='text-align: center; '></td>
								<td style='text-align: right;font-weight: bold;font-size: 15px;' width="18%"><?=$sign?> <?php echo number_format($totamt,2);?>&nbsp;</td>
							</tr>
							<?php } else { ?>
							<tr>
								<td style='text-align: left; padding: 5px;'><span ><b>최종 결제금액</b></span></td>
								<td colspan="3" style='text-align: center; '></td>
								<td style='text-align: right;font-weight: bold;font-size: 15px;' width="18%"><?=$sign?> <?php echo number_format($totamt,2);?>&nbsp;</td>
							</tr>
							<?php } ?>

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
					<div>
					      <table style='width: 100%;line-height: 18px;text-align: left;border: 1px solid #aaa;font-size: 13px;'>
							<tbody>
								<tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border: 1px solid #aaa;">
									<td width=20% style="border: 1px solid #aaa;">&nbsp;보낸사람</td>
									<td width=50% style="border: 1px solid #aaa;">&nbsp;제목</td>
									<td width=30% style="border: 1px solid #aaa;">&nbsp;보낸날짜</td>
								
								</tr>
							</tbody>
							<? printCustomer(); ?>
						</table>
					</div>
				</main>
			</div>
			<div></div>
		</div>
	</div>
	
</form>
<div id="dialog" width="800px" title="Basic dialog">
	    <div name="msg" id="msg" style="width: 800px; height: 600px"></div>
  
</div>
    <script src="ckeditor/ckeditor.js"></script>
	<script>
	    $(document).ready(function () {
				$.ajaxSetup({async:false});
				CKEDITOR.replace( 'board_note', {
					height: 200,
                    allowedContent: true,
					removeButtons: '',
                   	toolbar: [
							  [ 'Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo' ],
							  { name: 'insert', items: [ 'Image', 'Table', 'HorizontalRule', 'SpecialChar' ] },
							  { name: 'font', items: ['Font','FontSize'] },
							  '/',
							  { name: 'basicstyles', items: [ 'Bold', 'Italic', 'Underline','-','JustifyLeft','JustifyCenter','JustifyRight','-','NumberedList','BulletedList','-','Link','Unlink','-','Source' ] },
							  { name: 'colors', items: [ 'TextColor', 'BGColor' ] },
							  { name: 'tools', items: [ 'Maximize' ] }
							 ]
			    } );
				$(".js-mail").click(function() {
					$("#mode").val("send_email");
					var r_code =$("#r_code").val();
					$("#print").attr("action","invoice_page2.php?division=3&pdx=2&sub=15&r_code="+r_code+"");
					$("#print").submit();
				});	
			});
		
		function pageprint()
		{ 
		   //$("#mode").val("print");
		   var r_code =$("#r_code").val();
		   $("#print").attr("action","invoice_p2.php?division=3&pdx=2&sub=15&r_code="+r_code+"");
		   $("#print").submit();
		} 

		function viewmail(estimateCode,seqno) {
		 
		 $.getJSON("get_mailc.php?estimateCode="+estimateCode+"&seq="+seqno, function(result){
			$.each(result, function(j,data) {
				
				$( "#msg" ).html(data.message);
				$( "#dialog" ).dialog({ 
					  
					  width: 1000
				});

			});
		 });

	  }
		
	</script>
</body>
</html>	

 