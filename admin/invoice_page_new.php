<?php
    include "include/inc_base.php";
    if ($_COOKIE[MEMLOGIN_ADMIN_DONG] !="") {
	} else {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}

     if (!hasMenuAccess($division, $pdx, $sub)) {
		
		Misc::jvAlert("권한이 있는 메뉴가 아닙니다. 확인후 사용하세요.!!","");
		exit;
    }

	
	function check_email_address($email) {
	//Function copied from http://www.linuxjournal.com/article/9585
	  // First, we check that there's one @ symbol, 
	  // and that the lengths are right.
	  if (!ereg("^[^@]{1,64}@[^@]{1,255}$", $email)) {
		// Email invalid because wrong number of characters 
		// in one section or wrong number of @ symbols.
		return false;
	  }
	  // Split it into sections to make life easier
	  $email_array = explode("@", $email);
	  $local_array = explode(".", $email_array[0]);
	  for ($i = 0; $i < sizeof($local_array); $i++) {
		if (!ereg("^(([A-Za-z0-9!#$%&'*+/=?^_`{|}~-][A-Za-z0-9!#$%&'*+/=?^_`{|}~\.-]{0,63})|(\"[^(\\|\")]{0,62}\"))$", $local_array[$i])) {
		  return false;
		}
	  }
	  // Check if domain is IP. If not, 
	  // it should be valid domain name
	  if (!ereg("^\[?[0-9\.]+\]?$", $email_array[1])) {
		$domain_array = explode(".", $email_array[1]);
		if (sizeof($domain_array) < 2) {
			return false; // Not enough parts to domain
		}
		for ($i = 0; $i < sizeof($domain_array); $i++) {
		  if (!ereg("^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|([A-Za-z0-9]+))$", $domain_array[$i])) {
			return false;
		  }
		}
	  }
	  return true;
	}
	$reserve_info = getReserveInfo($r_code);
	if ($mode == "send_email") {

           // 메일 제목
				$sbj = "[동부투어] 정상적으로 예약번호 $r_code으로 예약이 접수되었습니다.";

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
			if(is_uploaded_file($tmpName3)){
				$pds_file3 = $_FILES['userfile3']['name'];
				$attc_name3 = Misc::uploadFileUnsafely($tmpName3 , $pds_file3 , $board_pds_pos);
				
				$fileloc3 = $board_pds_pos . "/" . $attc_name3[savedName];
				
				array_push($atc_arr,$fileloc3);
				$attachment3 = $attc_name3[savedName];
			}
			if(is_uploaded_file($tmpName4)){
				$pds_file4 = $_FILES['userfile4']['name'];
				$attc_name4 = Misc::uploadFileUnsafely($tmpName4 , $pds_file4 , $board_pds_pos);
				
				$fileloc4 = $board_pds_pos . "/" . $attc_name4[savedName];
				
				array_push($atc_arr,$fileloc4);
				$attachment4 = $attc_name4[savedName];
			}
			///$msg = "* 추가 사항 <br />".$board_note."<br /><br />".$content;
			
			//exit;
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
			/*
			$from= "PARANTOURS<sophia@parantours.com>";
		    require_once('ses.php');
			//get credentials at http://aws.amazon.com My Account / Console > Security Credentials
			$ses = new SimpleEmailService('AKIAIKST4H332TPDALNQ', 'AnexcAMYshorjm4nPrqjoqGuMrFCITeHlJi9Y0Pj');
			$m = new SimpleEmailServiceMessage();
			//note that from and to emails must be verified using AWS SES dashboard.  Can remove limitations here https://aws-portal.amazon.com/gp/aws/html-forms-controller/contactus/SESProductionAccess2011Q3.
			$m->addTo('sophia@parantours.com');
			$m->setFrom($from);
			$m->setSubject($sbj);
			$value = stripslashes((string)$msg);
			$m->setMessageFromString('PARAN',$value);
			print_r($ses->sendEmail($m));
			*/
	}
	if ($mode == "print") {
		echo "<meta http-equiv='refresh' content='0; url=./invoice_p.php?division=3&pdx=2&sub=15&r_code=".$r_code."'>";

	
	}
	$reserve_info = getReserveInfo($r_code);
	$productMaster = getProductMaster($reserve_info[p_code]);
		
	$trip_day = codebaseName($productMaster[p_day]);
	
	
    function printPaymentHistory($rCode){
		
		global $dbConn;

		$qry1 = "select * from payment_history where reserveCode = '$rCode' && payment_status = 'DONE' order by wdate asc";
		$rst1 = $dbConn->query($qry1);
	    //echo $qry1;
		//exit;
		while($row1 = $rst1->fetch_assoc()){
			
			if($row1[division] == "credit")
			{
				$row1[amt] = "<font color=blue>+$$row1[amt]</font>";
			}
			else
			{
				$row1[amt] = "<font color=red>-$$row1[amt]</font>";
			}

			$date = explode(" ",$row1[wdate]);
      
			$content .= "<tr class='item'>
			<td align=center height=28>$date[0] <input type=hidden name=seqnum value='$row1[seq_no]'></td>
			<td align=center>$row1[pay_method]</td>
			<td align=center>&nbsp;$row1[pay_info]</td>
			<td align=right>$row1[payment]</td>
			<td align=center>$row1[register]</td> " ;
			
			

		}

		return $content;
	}
	if ($prodInfo[p_type] == 1) {
	   $pcap = "로컬상품";
	 } else if ($prodInfo[p_type] == 2) {
		$pcap = "인바운드";
	 } else if ($prodInfo[p_type] == 4) {
		$pcap = "인센티브";
	 } else if ($prodInfo[p_type] == 5) {
		$pcap = "아웃바운드";
	 }
	
    
	$date = date('Y-m-d', time());

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

		

		$qry1 = "select seq_no,send_reg,subject,sent_on from mailing_history where reserveCode='$estimateCode' order by sent_on desc";
		$rst1 = $dbConn->query($qry1);

		while($row1 = $rst1->fetch_assoc()){
		
			
					echo "<tr bgcolor=#FFFFFF>
					<td height=25 class='malgun'>&nbsp;$row1[send_reg]</td>
					<td class='malgun'><a href=# OnClick=viewmail('$r_code','$row1[seq_no]')>$row1[subject]</a></td>
					<td class='malgun'>$row1[sent_on]</td>
					</tr>";
				
		}
		
	}
?>
<!doctype html>
<html >
<head>
    <meta charset="euc-kr">
    <title>고객영수증</title>
    <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
	<link rel="stylesheet" href="/resources/demos/style.css">
    <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
	<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
	<script language="javascript" src="//cdn.ckeditor.com/4.5.3/full/ckeditor.js"></script>
    <style>
	@page 
    {
        size: auto;   /* auto is the initial value */
        margin: 0mm;  /* this affects the margin in the printer settings */
    }
    .invoice-box {
        max-width: 800px;
        margin: auto;
        padding: 30px;
        border: 0px solid #eee;
        box-shadow: 0 0 0px rgba(0, 0, 0, 0);
        font-size: 12px;
        line-height: 20px;
        font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
        color: #555;
    }
    
    .invoice-box table {
        width: 100%;
        line-height: inherit;
        text-align: left;
    }
    
    .invoice-box table td {
        padding: 2px;
        vertical-align: top;
    }
    
    .invoice-box table tr td:nth-child(2) {
     /*   text-align: left; */
    }

	.tdleft {
        text-align: left;
    }
    
    .invoice-box table tr.top table td {
        padding-bottom: 20px;
    }
    
    .invoice-box table tr.top table td.title {
        font-size: 45px;
        line-height: 45px;
        color: #333;
    }
    
    .invoice-box table tr.information table td {
        padding-bottom: 40px;
    }
    
    .invoice-box table tr.heading td {
        background: #eee;
        border-bottom: 1px solid #ddd;
        font-weight: bold;
    }
    
    .invoice-box table tr.details td {
        padding-bottom: 20px;
    }
    
    .invoice-box table tr.item td{
        border-bottom: 1px solid #eee;
    }
    
    .invoice-box table tr.item.last td {
        border-bottom: none;
    }
    
    .invoice-box table tr.total td:nth-child(2) {
        border-top: 2px solid #eee;
        font-weight: bold;
    }
    
    @media only screen and (max-width: 600px) {
        .invoice-box table tr.top table td {
            width: 100%;
            display: block;
            text-align: center;
        }
        
        .invoice-box table tr.information table td {
            width: 100%;
            display: block;
            text-align: center;
        }
    }
    
    /** RTL **/
    .rtl {
        direction: rtl;
        font-family: Tahoma, 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
    }
    
    .rtl table {
        text-align: right;
    }
    
    .rtl table tr td:nth-child(2) {
        text-align: left;
    }
    </style>
</head>

<body>
<form name=print id=print action=<?= $PHP_SELF ?> method=post enctype="multipart/form-data">
  <input type=hidden name=r_code value="<?= $r_code ?>">
  <input type=hidden name=mode value="send_email">
    <div class="invoice-box">
        <table cellpadding="0" cellspacing="0">
            <tr class="top">
                <td colspan="2">
                    <table>
                        <tr>
                            <td class="title" width="70%">
                                <img src="img/logo.jpg" style="width:100%; max-width:300px;">
                            </td>
                            
                            <td>
                                예약번호 #: <b><?= $r_code ?></b><br>
                                발급일 : <?=$date?><br />
								발급인 : <b><?=$user_dbinfo[kor_name]?></b>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            
            <tr class="information">
                <td colspan="2">
                    <table>
					<?php if ($prodInfo[p_type]=="5") { ?>
                        <tr>
                            <td width="70%">
                                Dongbu USA, Inc<br>
								21 Grand Ave, Suite 603<br>
                                Palisades Park, NJ 07650<br><br>
                                150-24 Northern Blvd,Unit A2<br>
                                Flushing, NY 11354<br>
								1-718-939-1000
                            </td>
                    <?php } else { ?>
					    <tr>
                            <td width="70%">
                                Dongbu Tour & Travel, Inc<br>
                                21 Grand Ave, Suite 603<br>
                                Palisades Park, NJ 07650<br>
								1-718-939-1000
                            </td>
					
					<?php }  ?>

                            <td class='tdleft'>
                                고객명 : <?= $reserve_info[book_pri]?><br>
                                여행인원 : <?= $reserve_info[p_cnt] ?> 명<br>
								이메일 :  <?= $reserve_info[book_email] ?>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr >
                <td style="text-align :right" colspan="2">
                    <span id='cont'><button type=button value="<?=$r_code?>"  class="form_box btnmail" style="background-color:#FF9900;color:#FFFFFF;height:30px">고객이메일 전송</button>
					<button type=button value="<?=$r_code?>"  class="form_box btnprint" style="background-color:#99CC00;color:#FFFFFF;height:30px">주문인쇄</button></span>
                </td>
            </tr>
            
            <tr >
                <td colspan="2">
                    <table>
                        <tr class="heading">
                            <td width=15% align=center>결제일</td>
							<td width=20% align=center>결제방법</td>
							<td width=20% align=center>정보</td>
							<td width=15% align=center>결제액</td>
							<td width=10% align=center>등록자</td>
                        </tr>
						<?= printPaymentHistory($reserve_info[reserveCode]); ?>
                    </table>
                </td>
            </tr>
            
            
            
            <tr >
                <td colspan="2">
                    <table>
                        <tr class="heading">
                            <td width=30% align=center>상품명</td>
							<td width=10% style="text-align :center">출발일</td>
							<td width=10% align=center>복귀일</td>
							<td width=10% align=center>판매가</td>
							
                        </tr>
						<tr class="item">
							<td>
								<b><?= $productMaster[p_code] ?></b>&nbsp;<?= $productMaster[p_name] ?>
							</td>
							
							<td align=center><?= $reserve_info[stDate] ?></td>
							<td align=center><?= $reserve_info[edDate] ?></td>
							<td style="text-align :right">$ <?= number_format($reserve_info[last_total],2) ?></td>
                        </tr>
					<!--<?php if ($etcamt != 0) { ?>
						<tr class="item">
							<td>
								기타비용(항공료/픽업/숙박료/입장료등)
							</td>
							
							<td></td>
							<td></td>
							<td style="text-align :right">$ <?= number_format($etcamt,2) ?></td>
                        </tr>
					<?php } ?>	-->
						<tr class="item last">
							<td>
								할인금액
							</td>
							
							<td colspan="4" style="text-align :right">
								-$ <?=$reserve_info[last_dis]?>
							</td>
                        </tr>
						<tr class="total">
							<td></td>
							
							<td colspan="4" style="text-align :right">
							   총금액: $<?=number_format($reserve_info[last_total],2)?>
							</td>
						</tr>
						<tr class="total">
							<td></td>
							
							<td colspan="4" style="text-align :right">
							   잔액: <font color=red>$<?=number_format($reserve_info[last_bal],2)?></font>
							</td>
						</tr>
                    </table>
                </td>
            </tr>
            
            <?php if ($prodInfo[p_type]=="5") { ?>         
			<tr height='10px'>
                <td colspan="2">
                  &nbsp;
                </td>
                
            </tr>
			
			<tr class="information">
                <td colspan="2">
                    <table>
                        <tr>
                            <td>
                                <img src=img/accept.png align=absmiddle>&nbsp;<b>PAY TO THE ORDER</b><br /><br />
								PAY TO THE ORDER는 반드시 <b><font color=red>Dongbu USA, Inc</font></b>
								로 적어주세요.
                            </td>
                           
                        </tr>
						
                    </table>
                </td>
            </tr>
			<?php } ?>
			<tr height='10px'>
                <td colspan="2">
                  &nbsp;
                </td>
                
            </tr>

			<tr >
                <td  colspan="2">
                     <img src=img/accept.png align=absmiddle>&nbsp;<b>이메일 추가내용 보내기</b>
                </td>
            </tr>

			<tr >
                <td  colspan="2">
                     &nbsp;<textarea id="board_note_ta" name="msg"   ><?=$body?></textarea></b>
                </td>
            </tr>
			<tr>
					<td >&nbsp;첨부파일1:</td>
					<td>&nbsp;<input type=file name=userfile1 class="form_box" value="" style="width:600px"></td>
			</tr>
			 <tr height='10px'>
                <td colspan="2">
                  &nbsp;
                </td>
                
            </tr>

            
			<?php if ($prodInfo[p_type]=="5") { ?>
			<tr class="information">
                <td colspan="2">
                    <table>
                        <tr>
                            <td>
                                *&nbsp;<b>여행 취소와 환불 (CANCELLATIONS AND CANCELLATION FEES)</b>
								<?=$alert2?>
                            </td>
                           
                        </tr>
						
                    </table>
                </td>
            </tr>

			<tr height='10px'>
                <td colspan="2">
                  &nbsp;
                </td>
                
            </tr>
			<tr height='10px'>
                <td colspan="2">
                  &nbsp;
                </td>
                
            </tr>
			<tr height='10px'>
                <td >
                  &nbsp;
                </td>
                <td align ='right'>
                  수금인 _____________________ &nbsp;&nbsp;&nbsp;날짜 _________________&nbsp;<br /><br /><br />
				  회계담당자 _____________________&nbsp;&nbsp;&nbsp;날짜 _________________&nbsp;
                </td>
            </tr>
			<?php } else { ?>
			<tr class="information">
                <td colspan="2">
                    <table>
                        <tr>
                            <td>
                                <img src=img/accept.png align=absmiddle>&nbsp;<b>여행 취소와 환불 (CANCELLATIONS AND CANCELLATION FEES)</b>
								<?=$alert1?>
                            </td>
                           
                        </tr>
						
                    </table>
                </td>
            </tr>
		<?php } ?>
		    <tr >
                <td colspan="2">
                    <table>
                        <tr class="heading">
                            <td width=20% class="malgun">&nbsp;보낸사람</td>
						    <td width=50% class="malgun">&nbsp;제목</td>
						   <td width=30% class="malgun">&nbsp;보낸날짜</td>
							
                        </tr>
						<?php printCustomer(); ?>
                    </table>
                </td>
            </tr>
        </table>
    </div>
	<div id="dialog" width="800px" title="Basic dialog">
	    <div name="msg" id="msg" style="width: 800px; height: 600px"></div>
  
    </div>
  <script>

       $(document).ready(function() {
				//
				CKEDITOR.replace( 'board_note_ta', {
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
				
				$(".btnprint").click(function() {
					var  eCode;
					eCode = $(this).val();
					window.open("invoice_c.php?estimateCode=" + eCode,"invoice","width=800,height=500,left=200,top=250,scrollbars=1");

				});	 
				$(".btnmail").click(function() {
					$("#print").submit();
				});	 
	  
               

	  });
	  function viewmail(estimateCode,seqno) {
		 //  ,CKEDITOR.instances['editor1'].setData(msg);
		 $.getJSON("get_maillist.php?estimateCode="+estimateCode+"&seq="+seqno, function(result){
			$.each(result, function(j,data) {
				
				$( "#msg" ).html(data.message);
				$( "#dialog" ).dialog({ 
					  
					  width: 800
				});

				//
				
				//CKEDITOR.instances['board_note_ta'].setData(data.message);
			});
		 });

	  }
   </script>
 </form>
</body>

</html>