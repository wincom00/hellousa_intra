<?php
    include "include/header.php";
	//include "include/inc_base.php";
	if ($_COOKIE[MEMLOGIN_ADMIN_HELLO] != "") {
	} else {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}
    if (!hasMenuAccess($division, $pdx, $sub)) {
		$goUrl_1 = "index.php";
		Misc::jvAlert("권한이 있는 메뉴가 아닙니다. 확인후 사용하세요.!!","");
		echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
		exit;
    }

	if($StartYMD)
	{
		$start_date = "$StartYMD 01:01:01";
		$stop_date = "$EndYMD 23:23:59";	

		$orderdate_qry = "&& a.revDate between '$start_date' and '$stop_date'";
		$orderdate_qry1 = "&& a.wdate between '$start_date' and '$stop_date'";
	}
	else
	{
		$StartYMD = date("Y-m-d",mktime (0,0,0,date("m")  , date("d")-7, date("Y")));
		$EndYMD = date("Y-m-d");

		$start_date = date("Y-m-d",mktime (0,0,0,date("m")  , date("d")-7, date("Y")));
		$stop_date = date('Y-m-d');

		//$stop_date = date('Y-m-d 23:59:59');
		
		//$orderdate_qry = "&& start_date between '$start_date' and '$stop_date'";
	}

	if($Mode == "SEARCH")
	{

		
		$qry1 = "select a.from_sns as typp ,DAYOFWEEK(a.revDate) as yoil ,a.revDate as wdate ,a.p_name,a.p_code,sum(a.p_cnt) as pcnt, sum(a.last_total)as last_total_amt,sum(a.last_bal) as balance,a.reserveCode from reserve_info a, product_master b where a.tour_type='2' && a.p_code = b.p_code 
		&& b.p_type != 5 && a.parent='MAIN' &&  a.rev_status not in ('READY','CANCEL') $orderdate_qry
		group by DAYOFWEEK(a.revDate),a.revDate,a.p_name
		
		order by DAYOFWEEK(a.wdate),date_format(a.wdate, '%Y-%m-%d') desc"; 
		$rst1 = $dbConn->query($qry1);
		//$cnt = mysql_num_rows($rst1);
		$cnt = $rst1->num_rows;
		//echo $qry1;
		//exit;
		$num1 = 0;
		$tour_amt = 0;
		$tour_pcnt = 0;
		$tour_samt = 0;
		$tour_spcnt = 0;
		
		$tour_amt1 = 0;
		$tour_pcnt1 = 0;
		$tour_samt1 = 0;
		$tour_spcnt1 = 0;
		$tour_balance = 0;
		$num = 1;
		while($row1 = $rst1->fetch_assoc()) {
			


			
			$tour_balance = $tour_balance + $row1[balance];
			$tour_sbalance = $tour_sbalance + $row1[balance];
			$sDate2 = explode("-",$row1[wdate]);
			$yoil = Date("D", mktime (0,0,0,$sDate2[1]  , $sDate2[2]+$i, $sDate2[0]));
			
			
			if (($oldyo != $yoil) && ($num != 1)) {
				$tot_scnt = $tour_spcnt + $tour_spcnt1;
				$tot_samt = $tour_samt + $tour_samt1;
				$content .= "<tr bgcolor=#FFFFFF>
				<td height=22></td>
				<td height=22></td>
				<td height=22></td>
				<td align=center></td>
				<td align=left></td>
				<td align=right>예약인원 : ".$tour_spcnt1."&nbsp;</td>
				<td align=right>예약금액 : $".number_format($tour_samt1,2)."&nbsp;</td>
				<td align=right>$".number_format($tour_sbalance,2)."&nbsp;</td>
			
				</tr>";

				$tour_sbalance =0;
				
				//$tour_amt = 0;
				//$tour_pcnt = 0;
				$tour_samt = 0;
				$tour_spcnt = 0;
				
				//$tour_amt1 = 0;
				//$tour_pcnt1 = 0;
				$tour_samt1 = 0;
				$tour_spcnt1 = 0;
				

			}
			
			$ptype = $row1[typp];

			$tour_amt1 = $tour_amt1 + $row1[last_total_amt];
			$tour_pcnt1 = $tour_pcnt1 + $row1[pcnt];

			$tour_samt1 = $tour_samt1 + $row1[last_total_amt];
			$tour_spcnt1 = $tour_spcnt1 + $row1[pcnt];
		
		

			//echo $tour_spcnt."|".$tour_spcnt1."|".$row1[pcnt]."<br />";
	
			$content .= "<tr bgcolor=#FFFFFF>
			  <td height=22 align=center>&nbsp;$num </td>
			  <td height=22 align=center>&nbsp;$ptype </td>
				<td height=22 align=center>&nbsp;$row1[wdate]</td>
				<td align=center>$yoil</td>
				<td align=left>$row1[p_name]</td>
				<td align=right>$row1[pcnt]&nbsp;</td>
				<td align=right>$$row1[last_total_amt]&nbsp;</td>
				<td align=right>$$row1[balance]&nbsp;</td>
				</tr>";
			
			if ($cnt == $num) {
				$tot_scnt = $tour_spcnt + $tour_spcnt1;
				$tot_samt = $tour_samt + $tour_samt1;
				
				$content .= "<tr bgcolor=#FFFFFF>
				<td height=22></td>
				<td height=22></td>
				<td height=22></td>
				<td align=center></td>
				<td align=left></td>
				<td align=right>예약인원 : ".$tour_spcnt1."&nbsp;</td>
				<td align=right>예약금액 : $".number_format($tour_samt1,2)."&nbsp;</td>
				<td align=right>$".number_format($tour_balance,2)."&nbsp;</td>
			
				</tr>";

				
				
				
			}
			$oldyo = $yoil; 
			
			$num1++;
			$num++;

		}

		if($num1 == "0")
		{
			$content .= "<tr bgcolor=#FFFFFF>
			<td colspan=9 height=30 align=center>등록된 예약이 없습니다.</td>
			</tr>";
		} else {
			$tot_amt = $tour_amt + $tour_amt1; 
			$tot_cnt = $tour_pcnt + $tour_pcnt1; 
			$content .= "<tr bgcolor=#FFFFFF>
			<td height=22></td>
				<td height=22></td>
				<td height=22></td>
				<td align=center></td>
				<td align=left></td>
				<td align=right>총예약인원 : ".$tour_pcnt1."&nbsp;</td>
				<td align=right>총예약금액 : $".number_format($tour_amt1,2)."&nbsp;</td>
				<td align=right>$".number_format($tour_balance,2)."&nbsp;</td>
			
			</tr>";
			
		}

	}


	
?>
 

<div id="contentwrapper" class="reservationDetailForm">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb 
			module">
				<ul>
					<li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li>
					<li><a href="#">MIS</a></li>
					<li>요일별 인터넷 매출</li>
				</ul>
			</div>
			<div class="row">
				<div class="col-sm-12 col-md-12">
				    <FORM action="<?= $PHP_SELF ?>?division=4&pdx=1&sub=15" method=post>
				    <input type=hidden name=Mode value="SEARCH">
					  <table class="table table-bordered table-condensed gridSixteen reserveTable formDetail">
                            <tbody>
                                <tr>
                                    <td width=15% class="text-center formHeader">&nbsp;조회 기간</td>
									<td width=85% bgcolor=#FFFFFF>&nbsp;<input name=StartYMD type="text" class="form_box"  readOnly size="12" id="date1" value="<?= $StartYMD ?>"> ~ <input name=EndYMD type="text" class="form_box" readOnly size="12" id="date2" value="<?= $EndYMD ?>"></td>
								</tr>
								
								<td  colspan='2' class="text-center"><button type='submit' class="btn btn-primary btn-sm btn1">검색</button></td>
							</tbody>
						</form>
					  </table>
					  <br>
					  <?php if($Mode == "SEARCH"): ?>
						  <table class="table table-striped table-bordered mediaTable">
                            <tbody>
								<tr  height=28>
									<td width=5% align=center>순번</td>
									<td width=10% align=center>예약경로</td>
									<td width=10% align=center>예약일</td>
									<td width=10% align=center>요일</td>
									<td width=23% align=center>예약상품</td>
									<td width=10% align=center>예약인원</td>
									<td width=12% align=center>예약금액</td>
									<td width=10% align=center>발란스</td>
								
									
								</tr>
								<?= $content; ?>
						  </tbody>
						</table>
					  <?php endif; ?>
			   
				<br><br>

		    </div>
       </div>
	</div>

<?php
		include "include/side_m.php"
?>
<script>
		$(document).ready(function () {
            //$.ajaxSetup({async:false});
			$('#date1').datepicker({
				format: "yyyy-mm-dd",
				
				autoclose: true
			
			});
			$('#date2').datepicker({
				format: "yyyy-mm-dd",
				
				autoclose: true
			
			});
		});
</script>
</body>
</html>