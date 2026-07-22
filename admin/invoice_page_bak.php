
<?php
   include "include/inc_base.php";
    if ($_COOKIE[MEMLOGIN_ADMIN_PURUN] !="") {
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
	$sign = "U$";
	
	$disinfo = codebaseName($revInfo[dis_code]);
	$disamt = getReserveSum($r_code);
	$totamt = $revInfo[last_total] ;//- $disamt[amt];
	$lasttot = $revInfo[last_sale] + $revInfo[last_add];
	$file_server_path = realpath(__FILE__);
	//echo $file_server_path;
	$rev_dbinfo = getinfo_dbMember($revInfo[userid]);
	$today = date("Y-m-d");
	if ($mode == "send_email") {
			$sbj = "[푸른투어] $r_code - 예약접수가 되었습니다.";

			$content= file_get_contents( "http://www.myprt.online/invoice_m.php?division=3&pdx=2&sub=15&r_code=".$r_code."");

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
			$msg = $board_note."<br />".$content;
			//echo $content;
			//exit;
			$ret= mailsend_k($revInfo[book_email],$sbj,$msg,$attachment1,$attachment2);
			
			if (($prodInfo[p_type] == 1)) {
			$ret= mailsend_k('local@prttour.com',$sbj,$msg,$attachment1,$attachment2);
			} else {
			$ret= mailsend_k('local@prttour.com',$sbj,$msg,$attachment1,$attachment2);
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
																					
			$rst2 = mysql_query($qry2, $dbConn);
			
	}	
	if ($mode == "print") {
		echo "<meta http-equiv='refresh' content='0; url=./invoice_p.php?division=3&pdx=2&sub=15&r_code=".$r_code."'>";

	}
?>

<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>푸른투어</title>
    <link href="//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
	<link href="https://fonts.googleapis.com/css?family=Montserrat|Open+Sans|Roboto&display=swap" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css?family=Nanum+Gothic" rel="stylesheet">
	<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
	<link rel="stylesheet" href="/resources/demos/style.css">
	<link href="css/invoice-f.css" rel="stylesheet" id="invoice-css">

	<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>

    <style type="text/css">
	  @media print {
		  @page { margin: 0;margin-top: 2.6 em; }
		  body { margin: 1 cm; }
	  }
	</style>
</head>

<body>
<form name=print id=print action='<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&r_code=<?=$r_code?>' method=post enctype="multipart/form-data">
  <input type=hidden name=r_code value="<?= $r_code ?>">
  <input type=hidden name=mode id="mode" value="send_email">
    <? if ($mode != 'print') { ?>
	    <br /><br />
		<div class="row no-nav">
			<div id="custom_button" class="col-sm-12 text-right">
				<button type="button" class="btn btn-xs btn-default js-mail" >이메일 보내기</button>
				<button type="button" class="btn btn-xs btn-default js-print" onclick="pageprint()">프린트</button><br /><br />

			</div>
		</div>
		
		<div class="text-left book_header">*추가내용 정보</div>
		 <table class="table table-bordered table-condensed gridSixteen reserveTable formDetail">
			<tbody>
				<tr>
					<td ><textarea class="form-control js-tripNote js-ckEditor" name="board_note"></textarea></td>
					
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
	<table cellpadding="0" cellspacing="0" style="width: 100%;line-height: inherit;text-align: left;">
           <tr>
				<td style="padding-bottom: 5px;align-text:left;">
                     <img src="http://www.myprt.online/img/top_in3.png">
                </td>
		   </tr>
   </table>
    <div style="max-width: 900px;
        margin: auto;
        padding: 15px 10px 20px 10px;
        /*border: 1px solid #eee;
        box-shadow: 0 0 10px rgba(0, 0, 0, .15);  */
        font-size: 16px;
        line-height: 21px;
        font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
        color: #555;">
		
        <table cellpadding="0" cellspacing="0" style="width: 100%;line-height: inherit;text-align: left;">
            <tr>
                <td colspan="2" style="vertical-align: top;">
                    <table style="width: 100%;line-height: inherit;text-align: left;">
                        <tr>
                            
							<!--<td style="font-size: 12px;color: #111;padding:5px;">
								<table style="width: 100%;line-height: inherit;text-align: left;">
									<tr>
										<td colspan="5" style="font-size:11px;vertical-align: top;text-align: left;color:#111;font-weight:700;">
										미국 본사:324 Broad Ave.Ridgefield, NJ 07657 T.1.201.778.4000 F.1.201.313.0890 
										</td>
									</tr>
									<tr>
										<td colspan="5" style="font-size:11px;vertical-align: top;text-align: left;color:#111;font-weight:700;">
										*인센티브/패키지 문의 usa@prttour.com / prlseoul@prttour.com *모든투어 문의 local@prttour.com *웹사이트 www.prttour.com
										</td>
									</tr>
									<tr>
										<td style="font-size:11px;vertical-align: top;text-align: left;color:#111;font-weight:700;">
										뉴욕(플러싱)지사:154-08 Northern Blvd #2B Flushing, NY 11354 | T: 1.718.928.3333 | F : 1.718.460.7889
										</td>
									 </tr>
									<tr>
										<td colspan="5" style="font-size:11px;vertical-align: top;text-align: left;color:#111;font-weight:700;">
										서부 본부:3435 Wilshire Blvd, #152 Los Angeles, CA 90010 | T : 1.213.739.2222 | F : 1.213.279.2220
										</td>
									</tr>
									<tr>
										<td colspan="5" style="font-size:11px;vertical-align: top;text-align: left;color:#111;font-weight:700;">
										서울 지사: Officia #1922, 92 Saemunan-ro Jongno-gu, Seoul┃T : 82.2.739.0890┃F : 82.2.739.0892
										</td>
									</tr>
									<tr>
										<td colspan="5" style="font-size:11px;vertical-align: top;text-align: left;color:#111;font-weight:700;">
										토론토 지사: Unit203,77Finch Ave W.North York,ON,M2N 2H5┃T : 1.416.222.65520
										</td>
									</tr>
									<tr>
										<td colspan="5" style="font-size:11px;vertical-align: top;text-align: left;color:#111;font-weight:700;">
										라스베가스 지사: 6850 Spring Mountain Rd., #127 Las Vegas, NV 89146 ┃T : 1.702.861.2377 ┃F : 702.410.5883
										</td>
									</tr>	
								</table>
                            </td>-->
                        </tr>
                    </table>
                </td>
            </tr>
			<tr><td style="padding-bottom:12px;"></td></tr>
			<tr>
                <td colspan="2" style="line-height:35px;padding: 5px;vertical-align: top;background-color: #f3f3f3;font-weight: 700;color:#111;text-align:center;font-size:24px;">
                    INVOICE
                </td>
            </tr>

			<tr>
                <td colspan="2">
                    <table style="width: 100%;line-height: inherit;text-align: left;">
                        <tr>
                            <td style="padding-left: 3px;padding-top:15px;font-weight: 700;text-align:left;font-size: 14px; color:#111;">
                                Sales Person: <?=$rev_dbinfo[kor_name]?>
                            </td>
                            
                            <td style="padding-left: 3px;padding-top:15px;font-weight: 700;text-align:right;font-size: 14px; color:#111;">
								Reserve Date: <?=$revInfo[revDate]?>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr><td style="padding-bottom:12px;"></td></tr>
			<tr>
                <td colspan="2" style="padding-top:15px;">
                    <table style="width: 100%;line-height: inherit;text-align: left;border: 1px solid #ddd;border-collapse:collapse;">
                        <tr>
                            <td style="padding:5px;background: #f3f3f3;font-weight: 700;text-align:center;font-size: 14px; color:#111;border: 1px solid #ddd;">
                               Reserve Number
                            </td>
                            
                            <td colspan=3 style="padding:5px;font-size: 13px;font-weight: 5	00;border: 1px solid #ddd;">
								<?=$revInfo[reserveCode]?>
                            </td>
							
                        </tr>
						<tr>
                            <td style="padding:5px;background: #f3f3f3;font-weight: 700;text-align:center;font-size: 14px; color:#111;border: 1px solid #ddd;">
                                Customer
                            </td>
                            
                            <td style="padding:5px;font-size: 13px;border: 1px solid #ddd;">
								<?=$revInfo[book_pri]?>
                            </td>
							<td style="padding:5px;background: #f3f3f3;font-weight: 700;text-align:center;font-size: 14px;color:#111;border: 1px solid #ddd;">
                                Phone
                            </td>
                            
                            <td style="padding:5px;font-size: 15px;border: 1px solid #ddd;">
								<?=$revInfo[book_phone]?>
                            </td>
                        </tr>
						<tr>
                            <td style="padding:5px;background: #f3f3f3;text-align:center;font-weight: 700;font-size: 14px;color:#111;border: 1px solid #ddd;">
                                Tour Name
                            </td>
                            
                            <td style="padding:5px;font-size: 13px;border: 1px solid #ddd;">
								<?=$prodInfo[p_name]?>
                            </td>
							<td style="padding:5px;background: #f3f3f3;text-align:center;font-weight: 700;font-size: 14px;color:#111;border: 1px solid #ddd;">
                                Email
                            </td>
                            
                            <td style="padding:5px;font-size: 13px;border: 1px solid #ddd;">
								<?=$revInfo[book_email]?>
                            </td>
                        </tr>
						<tr>
                            <td style="padding:5px;background: #f3f3f3;text-align:center;font-weight: 700;font-size: 14px;color:#111;border: 1px solid #ddd;">
                                Tour Date
                            </td>
                            
                            <td style="padding:5px;font-size: 13px;border: 1px solid #ddd;">
								<?=$revInfo[stDate]?>(<?=$sweekday?>)~<?=$revInfo[edDate]?>(<?=$eweekday?>)
                            </td>
							<td style="padding:5px;background: #f3f3f3;text-align:center;font-weight: 700;font-size: 14px;color:#111;border: 1px solid #ddd;">
                                PAX
                            </td>
                            
                            <td style="padding:5px;text-align:right;font-size: 13px;border: 1px solid #ddd;">
								<table style="width: 100%;line-height: inherit;text-align: left;">
									<tr>
										
										<td> Total: <?=$revInfo[p_cnt]?></td>
									</tr>
								</table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
			<tr><td style="padding-bottom:20px;"></td></tr>

			<tr>
                <td colspan="2" style="padding-top:15px;">
                    <table style="width: 100%;line-height: 21px;text-align: left;border: 1px solid #ddd;border-collapse:collapse;">
                        <tr>
							<td colspan="2" style="width:70%;padding: 5px;background: #f3f3f3;border: 1px solid #ddd;font-weight: 700;font-size: 14px;color:#111;text-align:center;">
								DESCRIPTION
							</td>
							<td style="width:10%;padding: 5px;background: #f3f3f3;border: 1px solid #ddd;font-weight: 700;font-size: 14px;color:#111;text-align:center;">
								AMT
							</td>
							<td style="width:10%;padding: 5px;background: #f3f3f3;border: 1px solid #ddd;font-weight: 700;font-size: 14px;color:#111;text-align:center;">
								PERSON
							</td>
							<td style="width:10%;padding: 5px;background: #f3f3f3;border: 1px solid #ddd;font-weight: 700;font-size: 14px;color:#111;text-align:center;">
								SUB AMT
							</td>
						</tr>
						<tr>
							<td colspan="2" style="width:70%;font-size:12px;color:#111;padding: 5px;border: 1px solid #ddd;font-size:13px;color:#111;text-align:right"><?=$prodInfo[p_name]?></td>
							<td style="width:10%;font-size:12px;color:#111;padding: 5px;border: 1px solid #ddd;font-size:13px;color:#111;text-align:right">$<?=$revInfo[last_sale]?></td>
							<td style="width:10%;font-size:12px;color:#111;padding: 5px;border: 1px solid #ddd;font-size:13px;color:#111;text-align:center"><?=$revInfo[p_cnt]?></td>
							<td style="width:10%;font-size:12px;color:#111;padding: 5px;border: 1px solid #ddd;font-size:13px;color:#111;text-align:right">$<?=$revInfo[last_total]?></td>
						</tr>
						<tr>
							<td colspan="2" rowspan="10" style="width:70%;font-size:12px;color:#111;padding: 7px;border: 1px solid #ddd;font-size:13px;color:#111;vertical-align:top;">
							

							<span style="color:#111;font-weight:700;"><?=nl2br($revInfo[progress])?> </span> <br>
							 </td>	
							<td style="width:10%;font-size:12px;color:#111;padding: 5px;border: 1px solid #ddd;font-size:13px;color:#111;text-align:right"></td>
							<td style="width:10%;font-size:12px;color:#111;padding: 5px;border: 1px solid #ddd;font-size:13px;color:#111;text-align:center"></td>
							<td style="width:10%;font-size:12px;color:#111;padding: 5px;border: 1px solid #ddd;font-size:13px;color:#111;text-align:right">$0.00</td>
						</tr>
						<tr>
							<td style="width:10%;font-size:12px;color:#111;padding: 5px;border: 1px solid #ddd;font-size:13px;color:#111;text-align:right"></td>
							<td style="width:10%;font-size:12px;color:#111;padding: 5px;border: 1px solid #ddd;font-size:13px;color:#111;text-align:center"></td>
							<td style="width:10%;font-size:12px;color:#111;padding: 5px;border: 1px solid #ddd;font-size:13px;color:#111;text-align:right">$0.00</td>
						</tr>
						<tr>
							<td style="width:10%;font-size:12px;color:#111;padding: 5px;border: 1px solid #ddd;font-size:13px;color:#111;text-align:right"></td>
							<td style="width:10%;font-size:12px;color:#111;padding: 5px;border: 1px solid #ddd;font-size:13px;color:#111;text-align:center"></td>
							<td style="width:10%;font-size:12px;color:#111;padding: 5px;border: 1px solid #ddd;font-size:13px;color:#111;text-align:right">$0.00</td>
						</tr>
						<tr>
							<td style="width:10%;font-size:12px;color:#111;padding: 5px;border: 1px solid #ddd;font-size:13px;color:#111;text-align:right"></td>
							<td style="width:10%;font-size:12px;color:#111;padding: 5px;border: 1px solid #ddd;font-size:13px;color:#111;text-align:center"></td>
							<td style="width:10%;font-size:12px;color:#111;padding: 5px;border: 1px solid #ddd;font-size:13px;color:#111;text-align:right">$0.00</td>
						</tr>
						<tr>
							<td style="width:10%;font-size:12px;color:#111;padding: 5px;border: 1px solid #ddd;font-size:13px;color:#111;text-align:right"></td>
							<td style="width:10%;font-size:12px;color:#111;padding: 5px;border: 1px solid #ddd;font-size:13px;color:#111;text-align:center"></td>
							<td style="width:10%;font-size:12px;color:#111;padding: 5px;border: 1px solid #ddd;font-size:13px;color:#111;text-align:right">$0.00</td>
						</tr>
						<tr>
							<td style="width:10%;font-size:12px;color:#111;padding: 5px;border: 1px solid #ddd;font-size:13px;color:#111;text-align:right"></td>
							<td style="width:10%;font-size:12px;color:#111;padding: 5px;border: 1px solid #ddd;font-size:13px;color:#111;text-align:center"></td>
							<td style="width:10%;font-size:12px;color:#111;padding: 5px;border: 1px solid #ddd;font-size:13px;color:#111;text-align:right">$0.00</td>
						</tr>
						<tr>
							<td style="width:10%;font-size:12px;color:#111;padding: 5px;border: 1px solid #ddd;font-size:13px;color:#111;text-align:right"></td>
							<td style="width:10%;font-size:12px;color:#111;padding: 5px;border: 1px solid #ddd;font-size:13px;color:#111;text-align:center"></td>
							<td style="width:10%;font-size:12px;color:#111;padding: 5px;border: 1px solid #ddd;font-size:13px;color:#111;text-align:right">$0.00</td>
						</tr>
						<tr>
							<td style="width:10%;font-size:12px;color:#111;padding: 5px;border: 1px solid #ddd;font-size:13px;color:#111;text-align:right"></td>
							<td style="width:10%;font-size:12px;color:#111;padding: 5px;border: 1px solid #ddd;font-size:13px;color:#111;text-align:center"></td>
							<td style="width:10%;font-size:12px;color:#111;padding: 5px;border: 1px solid #ddd;font-size:13px;color:#111;text-align:right">$0.00</td>
						</tr>
						<tr>
							<td style="width:10%;font-size:12px;color:#111;padding: 5px;border: 1px solid #ddd;font-size:13px;color:#111;text-align:right"></td>
							<td style="width:10%;font-size:12px;color:#111;padding: 5px;border: 1px solid #ddd;font-size:13px;color:#111;text-align:center"></td>
							<td style="width:10%;font-size:12px;color:#111;padding: 5px;border: 1px solid #ddd;font-size:13px;color:#111;text-align:right">$0.00</td>
						</tr>
						<tr>
							<td style="width:10%;font-size:12px;color:#111;padding: 5px;border: 1px solid #ddd;font-size:13px;color:#111;text-align:right"></td>
							<td style="width:10%;font-size:12px;color:#111;padding: 5px;border: 1px solid #ddd;font-size:13px;color:#111;text-align:center"></td>
							<td style="width:10%;font-size:12px;color:#111;padding: 5px;border: 1px solid #ddd;font-size:13px;color:#111;text-align:right">$0.00</td>
						</tr>
						<tr>
							<td style="background: #f3f3f3;border: 1px solid #ddd;width:20%;padding: 5px;color:#111;vertical-align:top;font-weight: 700;font-size: 14px;color:#111;text-align:center;">TOTAL
							</td>
							<td style="width:50%;border: 1px solid #ddd;padding: 5px;color:#111;text-align:right;vertical-align:top;font-weight: 400;font-size: 13px;color:#111;text-align:center;"></td>
							<td style="background: #f3f3f3;border: 1px solid #ddd;padding: 5px;color:#111;text-align:right;vertical-align:top;font-weight: 700;font-size: 14px;color:#111;text-align:center;">USD</td>
							<td colspan="2" style="background: #f3f3f3;border: 1px solid #ddd;padding: 5px;color:#111;text-align:right;vertical-align:top;font-weight: 700;font-size: 14px;color:#111;text-align:right;">$<?=$revInfo[last_total]?></td>
						</tr>
					</table>
					<table style="width: 100%;line-height: 21px;text-align: left;border: 1px solid #ddd;border-collapse:collapse;">
					<?php
									$qryr = "select *,DATE_FORMAT(wdate, '%Y-%m-%d') as wwdate from payment_history where reserveCode = '$r_code' && pay_method !='init'  order by seq_no asc";
									//echo $qryr;
									$rstr = mysql_query($qryr);
									$cntr= mysql_num_rows($rstr);
									$i = 0;
									if ($cntr > 0) {
										while($row = mysql_fetch_assoc($rstr)):
										  
										   $rate = "";
										   switch ($row[pay_method])
										   {
												case "cash" : 
													$cappay = "현금";
													break;   
												case "creditcard" : 
													$cappay = "신용카드";
													break;
												case "debitcard" : 
													$cappay = "데빗";
													break;
												
												case "bcreditcard" : 
													$cappay = "자사단말기";
													break; 
												case "check" : 
													$cappay = "체크";
													break; 
												case "banktransfer" : 
													$cappay = "은행송금";
													break; 
												case "giftcertificate" : 
													$cappay = "상품권";
													break; 
												case "fundtransfer" : 
													$cappay = "금액이동";
													break; 
												default : 
													$cappay = "";
													break; 
												
											}
											
											if ($row[payment_status] == "RETURN") {
												$pamt = "<font color=red>-".$sign." ".$row[payment]."</font>";
											} else {

												$pamt = $sign." ".$row[payment];
											}
											

						?>
						<tr>
							<td style="width:5%;background: #f3f3f3;border: 1px solid #ddd;padding: 5px;color:#111;vertical-align:top;font-weight: 700;font-size: 14px;color:#111;text-align:center;">PAY METHOD
							</td>
							<td style="width:10%;border: 1px solid #ddd;padding: 5px;color:#111;vertical-align:top;font-weight: 400;font-size: 14px;color:#111;text-align:left;"><?=$cappay?>
							</td>
							<td style="width:10%;background: #f3f3f3;border: 1px solid #ddd;padding: 5px;color:#111;vertical-align:top;font-weight: 700;font-size: 14px;color:#111;text-align:center;">Date
							</td>
							<td style="width:13%;border: 1px solid #ddd;padding: 5px;color:#111;vertical-align:top;font-weight: 400;font-size: 14px;color:#111;text-align:left;"><?=$row[wwdate]?>
							</td>
							<td style="width:10%;background: #f3f3f3;border: 1px solid #ddd;padding: 5px;color:#111;vertical-align:top;font-weight: 700;font-size: 14px;color:#111;text-align:center;">Memo
							</td>
							<td style="width:20%;border: 1px solid #ddd;padding: 5px;color:#111;text-align:right;vertical-align:top;font-weight: 400;font-size: 13px;color:#111;text-align:left;"><?=$row[pay_memo]?></td>
							<td style="background: #f3f3f3;border: 1px solid #ddd;padding: 5px;color:#111;text-align:right;vertical-align:top;font-weight: 700;font-size: 14px;color:#111;text-align:center;">USD</td>
							<td colspan="2" style="background: #f3f3f3;border: 1px solid #ddd;padding: 5px;color:#111;text-align:right;vertical-align:top;font-weight: 700;font-size: 14px;color:#111;text-align:right;"><?=$pamt?></td>
						</tr>
						<?php
										$i++;
						                endwhile;
									
									} else {
						?>
						<tr>
							<td style="width:5%;background: #f3f3f3;border: 1px solid #ddd;padding: 5px;color:#111;vertical-align:top;font-weight: 700;font-size: 14px;color:#111;text-align:center;">PAY METHOD
							</td>
							<td style="width:10%;border: 1px solid #ddd;padding: 5px;color:#111;vertical-align:top;font-weight: 400;font-size: 14px;color:#111;text-align:left;">
							</td>
							<td style="width:10%;background: #f3f3f3;border: 1px solid #ddd;padding: 5px;color:#111;vertical-align:top;font-weight: 700;font-size: 14px;color:#111;text-align:center;">Date
							</td>
							<td style="width:10%;border: 1px solid #ddd;padding: 5px;color:#111;vertical-align:top;font-weight: 400;font-size: 14px;color:#111;text-align:left;">
							</td>
							<td style="width:10%;background: #f3f3f3;border: 1px solid #ddd;padding: 5px;color:#111;vertical-align:top;font-weight: 700;font-size: 14px;color:#111;text-align:center;">Memo
							</td>
							<td style="width:25%;border: 1px solid #ddd;padding: 5px;color:#111;text-align:right;vertical-align:top;font-weight: 400;font-size: 13px;color:#111;text-align:left;"></td>
							<td style="background: #f3f3f3;border: 1px solid #ddd;padding: 5px;color:#111;text-align:right;vertical-align:top;font-weight: 700;font-size: 14px;color:#111;text-align:center;"></td>
							<td colspan="2" style="background: #f3f3f3;border: 1px solid #ddd;padding: 5px;color:#111;text-align:right;vertical-align:top;font-weight: 700;font-size: 14px;color:#111;text-align:right;"></td>
						</tr>

						<?php
						              
									}
						?>
						<tr>
							<td  style="width:5%;background: #f3f3f3;border: 1px solid #ddd;padding: 5px;color:#111;vertical-align:top;font-weight: 700;font-size: 14px;color:#111;text-align:center;">BALANCE
							</td>
							
							<td colspan="5" style="width:30%;border: 1px solid #ddd;padding: 5px;color:#111;text-align:right;vertical-align:top;font-weight: 400;font-size: 13px;color:#111;text-align:left;"></td>
							<td style="background: #f3f3f3;border: 1px solid #ddd;padding: 5px;color:#111;text-align:right;vertical-align:top;font-weight: 700;font-size: 14px;color:#111;text-align:center;">USD</td>
							<td colspan="2" style="background: #f3f3f3;border: 1px solid #ddd;padding: 5px;color:#111;text-align:right;vertical-align:top;font-weight: 700;font-size: 14px;color:#111;text-align:right;">$<?=$revInfo[last_bal]?></td>
						</tr>
						<tr>
							<td style="width:20%;background: #f3f3f3;border: 1px solid #ddd;padding: 5px;color:#111;vertical-align:top;font-weight: 700;font-size: 14px;color:#111;text-align:center;">FORM OF PAYMENT
							</td>
							<td colspan="7" style="padding: 5px;color:#111;vertical-align:top;font-weight: 400;font-size: 13px;color:#111;text-align:left;border: 1px solid #ddd;">디파짓 결제 각자 해주심,나머지 잔금 한달전에 완불 예정 </td>
						</tr>
                    </table>
                </td>
            </tr>

			<tr>
                <td colspan="8" style="padding-top:15px;">
                    <table style="width: 100%;line-height: inherit;text-align: left;">
                        <tr>
							<td style="padding: 5px;font-weight: 400;font-size: 13px;color:blue;text-align:left;">
								※미동부/미서부/그외 투어 : 출발 2~3주전까지 완불 입니다
						</tr>
						<tr>
							<td style="padding: 5px;font-weight: 400;font-size: 13px;color:blue;text-align:left;">
								※전날예약자는 전액 완불 입니다!! 캔슬시 환불 안됩니다.
						</tr>
						<tr>
							<td style="padding: 5px;font-weight: 400;font-size: 13px;color:blue;text-align:left;">
								※투어 규정은 꼭 확인요청드립니다.(성수기시즌은 약간씩 규정이 변경될수 있습니다. 꼭 담당자께 확인요청드립니다.)
						</tr>
						<tr>
							<td style="padding: 5px;font-weight: 400;font-size: 13px;color:red;text-align:left;">
								※국제선&국내선 항공권은 발권후에 요금이 변동이 되어도 환불 또는 취소하실경우엔 패널티가 발생하므로 이점 꼭 유념해서 확인부탁드립니다.
						</tr>
						<tr>
							<td style="padding: 5px;font-weight: 400;font-size: 13px;color:red;text-align:left;">
								※고객님의 개인사정으로 국경을 통과하지 못하였거나, 부득이 한 사정으로 인하여 투어를 중단 하실경우에는 모든 책임과 금점적인 부담은 여행자 본인에게 있습니다.
						</tr>
						<tr>
							<td style="padding: 5px;font-weight: 400;font-size: 13px;color:red;text-align:left;">
								★캐나다 관광(국경 통과)시 학생은 학교 관계자의 싸인(6개월 이상 남아 있어야 함) 이 되어있는 I-20 , 교환 교수는 학교에서 발부되는 체류자격 서류를 필히 지참하시길 바랍니다.
						</tr>
						<tr>
							<td style="padding: 5px;font-weight: 400;font-size: 13px;color:red;text-align:left;">
								★미국 영주권자의 경우 영주권과 여권, 시민권자의 경우 여권, 한국에서 오신경우에는 여권과 리턴티켓이 있어야 합니다.
						</tr>
						<tr>
							<td style="padding: 5px;font-weight: 400;font-size: 13px;color:red;text-align:left;">
								★여권 유효기간은 출발일로부터 6개월 이상 남아있어야 합니다.
						</tr>
						<tr>
							<td style="padding: 5px;font-weight: 400;font-size: 13px;color:red;text-align:left;">
								★한국 및 미국 여권 외에 다른 여권을 소유하고 계신 분들은 비자가 필요한지 꼭 확인 부탁드립니다.<br>
								투어 외 발생하는 모든 비용은 투어 당사자에게 있으며 본사(푸른)은 책임지지 않습니다.
						</tr>
						<tr>
							<td style="padding: 5px;font-weight: 400;font-size: 13px;color:red;text-align:left;">
								※기상악화로 인해 투어 및 항공이 진행(운행)이 되지 않을경우에는 당사(푸른)에 책임이 없음을 알려드립니다.
						</tr>
						<tr>
							<td style="padding: 5px;font-weight: 400;font-size: 13px;color:blue;text-align:left;">
								※해외 여행자 보험은 불포함입니다. 출국 전 개별적으로 가입하시는 것을 권장합니다.
						</tr>
						<tr>
							<td style="padding: 5px;font-weight: 400;font-size: 13px;color:blue;text-align:left;">
								※항공사측에서 항공편을 변경 및 캔슬 할 경우에 당사(푸른)의 책임은 없음을 알려드립니다.
						</tr>
						<tr><td style="padding-bottom:15px;"></td></tr>
						<tr>
							<td style="padding:5px;font-weight: 400;font-size: 13px;color:#111;text-align:center;border-top:1px solid #ddd;">
								저희 푸른투어를 이용해 주셔서 대단히 감사합니다.<br>
								즐거운 여행 되십시요.
						</tr>
					</table>
				</td>
			</tr>
        </table>
    </div>
</form>
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
					$("#print").submit();
				});
				
			});
		
		function pageprint()
		{ 
		   $("#mode").val("print");
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