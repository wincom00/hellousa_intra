<?php
	include "include/header.php";
	//include "include/inc_base.php";
	if ($_COOKIE[MEMLOGIN_ADMIN_HELLO] != "") {
	} else {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}
	/*if (!hasMenuAccess($division, $pdx, $sub)) {
		$goUrl_1 = "index.php";
		Misc::jvAlert("권한이 있는 메뉴가 아닙니다. 확인후 사용하세요.!!","");
		echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
		exit;
    }*/

		if($StartYMD)
		{
			$start_date = "$StartYMD 00:00:00";
			$stop_date = "$EndYMD 23:23:59";	

			if ($type == "1") {
				$orderdate_qry = "&& a.stDate between '$start_date' and '$stop_date' group by a.reserveCode order by start_date, register, p_code";
			} else {
				$orderdate_qry = "&& a.revDate between '$start_date' and '$stop_date' group by a.reserveCode order by a.revDate, a.userid, a.p_code";
			}
		}
		else
		{
			$StartYMD = date("Y-m-d",mktime (0,0,0,date("m")  , date("d")-7, date("Y")));
			$EndYMD = date("Y-m-d");

			$start_date = "$StartYMD 00:00:00";
			$stop_date = "$EndYMD 23:23:59";	
		}

		if($Mode == "SEARCH")
		{

			if($employee_id)
			{
				$employee_id_qry = "&& a.userid = '$employee_id'";
			} else {
				$employee_id_qry = "&& a.userid in (select userid from member_list where division = 'admin' && c_part1='D013000')";
			}

			$qry1 = "select *, sum(a.p_cnt) as total_mem from 
			reserve_info a,product_master b where a.p_code=b.p_code && a.rev_status != 'CANCEL' && a.parent='MAIN' && b.p_type!='5' 
			$employee_id_qry $orderdate_qry";

			$rst1 = $dbConn->query($qry1);
			$numRows1 = $rst1->num_rows;
			
			$num1 = 0;
//echo $qry1			;
			$total_member = 0;
			$total_sum = 0;
			$total_bal_sum = 0;

			while($row1 = $rst1->fetch_assoc()){
				


				$date = explode(" ",$row1[revDate]);



				$content .= "<tr bgcolor=#FFFFFF>
					<td align=center height=25><b>$date[0]</b></td>
					<td align=center>$row1[userid]</td>
					<td align=center>$row1[stDate]</td>
					<td><a href='base_reservation_m.php?estimateCode=$row1[reserveCode]&division=3&pdx=2&sub=15&ty=1&pricet=1' target='_blank'>&nbsp;$row1[p_name]</a></td>
					<td align=center>$row1[total_mem]</td>
					<td align=right>$$row1[last_total]&nbsp;</td>
					<td align=right>$$row1[last_bal]&nbsp;</td>
				</tr>";

				$total_member = $total_member + $row1['total_mem'];
				$total_sum = $total_sum + $row1['last_total'];
				$total_bal_sum += $row1['last_bal'];

				$num1++;

			}

			$total_sum_print = number_format($total_sum,2);
			$total_bal_sum_print = number_format($total_bal_sum,2);

			$content .= "<table id='level4'  class='table table-striped table-bordered table-hover table-condensed'>
			<tr bgcolor=#b2dcca height=28>
					<td width=13% align=center>총예약건수</td>
					<td width=13% align=center></td>
					<td width=13% align=center></td>
					<td width=28% align=center></td>
					<td width=7% align=center>모객수</td>
					<td width=13% align=center>총금액</td>
					<td width=13% align=center>발란스</td>
				</tr>
			<tr bgcolor=#FFFFFF>
			<td align=center height=50 colspan=2><b>총 건수: $numRows1</b></td>
			<td align=center colspan=2>총 합</td>
			<td align=center><b>$total_member 명</b></td>
			<td align=right><b>$$total_sum_print</b>&nbsp;</td>
			<td align=right>$$total_bal_sum_print&nbsp;</td>

			</tr></table>";

			if($num1 == "0")
			{
				$content .= "<tr bgcolor=#FFFFFF>
				<td colspan=6 height=30 align=center>등록된 정산이 없습니다.</td>
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
					<li>정산현황</li>
					<li>아웃바운드로컬정산</li>
				</ul>
			</div>
			<div class="row">
				<div class="col-sm-12 col-md-12"> 
					  <FORM action=<?= $PHP_SELF ?> method=post>
					  <input type=hidden name=division value="<?= $division ?>">
					  <input type=hidden name=Mode value="SEARCH">
					  <table id="level4" class="txt_12" width="100%" align=center border="1" cellspacing="1" bgcolor=#dcdcdc cellpadding="0">
						<tr>
							<td width=20% bgcolor=#f4f4f4 height=30 align=right><select name=type>
							<? $option0 = ($type == "0") ? ('<option value="0" selected>판매일</option>') : ('<option value="0">판매일</option>'); echo $option0 ?>
							<? $option1 = ($type == "1") ? ('<option value="1" selected>출발일</option>') : ('<option value="1">출발일</option>'); echo $option1 ?>
							</select>&nbsp;정산 기간&nbsp;</td>
							<td width=80% bgcolor=#FFFFFF>&nbsp;<input name=StartYMD id=StartYMD  type="text" class="form_box"  readOnly size="12"  value="<?= $StartYMD ?>"> ~ <input name=EndYMD id=EndYMD type="text" class="form_box"  readOnly size="12" value="<?= $EndYMD ?>"></td>
						</tr>
						<tr>
							<td width=20% bgcolor=#f4f4f4 height=30 align=right>판매직원&nbsp;</td>
							<td width=80% bgcolor=#FFFFFF>&nbsp;<select name=employee_id>
							<option value="">전체직원
							<?= printOBEmployeeSelect($employee_id); ?>
							</select></td>
						</tr>
						<tr>
							<td colspan=2 height=35 align=center bgcolor=#FFFFFF><input type=submit style="background-color:#99CC00;color:#FFFFFF;height:22px;vertical-align: top;" value="  조회하기  " ></td>
						</tr>
						</form>
					  </table>
					  <br>
					  <table id="local" class="table table-striped table-bordered table-hover table-condensed js-productTable2">
						  <thead>
							<tr>
								<td width=13% align=center>판매일</td>
								<td width=13% align=center>판매직원</td>
								<td width=13% align=center>출발일</td>
								<td width=28% align=center>상품명</td>
								<td width=7% align=center>모객수</td>
								<td width=13% align=center>총금액</td>
								<td width=13% align=center>발란스</td>
							</tr>
						  </thead>
						  <tbody>
							<?= $content; ?>
						  </tbody>
					  </table>
					  <br><br>
				</div><!-- -->
			</div>                
		</div>

	</div>
    <?php
		include "include/side_m.php"
	?>
			  
     <script>
		$(document).ready(function () {
            pt.initReservationDetail()

			pt.initReservationList()
			var dateToday = new Date()
			$('#StartYMD').datepicker({
				format: "yyyy-mm-dd",
				autoclose: true
				
			});
			$('#EndYMD').datepicker({
				format: "yyyy-mm-dd",
				autoclose: true
			});

			$('.js-productTable2').DataTable( {
				dom: 'Bfrtip',
				buttons: [
						'copy',  'excel', 'print'
					 ],
				"order": [[ 1, "desc" ]]
			} );


			$(".dataTables_length").css({ "display" :"none" });
		})
		
		
	</script>
    </body>
</html>