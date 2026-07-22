<?php   
  include "include/header.php";
	//include "include/inc_base.php";
	if ($_COOKIE[MEMLOGIN_ADMIN_HELLO] != "") {
	} else {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}
	/*
    if (!hasMenuAccess($division, $pdx, $sub)) {
		$goUrl_1 = "index.php";
		Misc::jvAlert("권한이 있는 메뉴가 아닙니다. 확인후 사용하세요.!!","");
		echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
		exit;
    }
    */
	if($StartYMD)
	{
		$start_date = "$StartYMD";
		$stop_date = "$EndYMD";	

		if ($type == "1") {
			$orderdate_qry = "&& a.revDate between '$start_date' and '$stop_date'";
			$orderdate_qry1 = "&& a.revDate between '$start_date' and '$stop_date'";
		} else {
			$orderdate_qry = "&& a.stDate between '$start_date' and '$stop_date'";
			$orderdate_qry1 = "&& a.stDate between '$start_date' and '$stop_date'";
		}
	}
	else
	{
		$StartYMD = date("Y-m-d",mktime (0,0,0,date("m")  , date("d")-7, date("Y")));
		$EndYMD = date("Y-m-d");

		$start_date = date("Y-m-d",mktime (0,0,0,date("m")  , date("d")-7, date("Y")));
		$stop_date = date('Y-m-d');

		
	}

	
	$s_qry1 = "select  COALESCE(sum(a.last_total),0) as total_sum,COALESCE(sum(a.p_cnt),0) as total_mem from reserve_info a,product_master b  where a.p_code=b.p_code && a.parent = 'MAIN' && a.rev_status in ('DONE','PLAY') && b.p_type in ('1','5') $orderdate_qry";
	
	$s_rst1 = $dbConn->query($s_qry1);
	$row1 = $s_rst1->fetch_assoc();
	$s_amt = $row1[total_sum];

//echo $s_qry1;
	
	$s_qry2 = "select  COALESCE(sum(a.last_total),0) as total_sum,COALESCE(sum(a.p_cnt),0) as total_mem from reserve_info a,product_master b  where a.p_code=b.p_code && a.parent = 'MAIN' && a.rev_status in ('DONE','PLAY') && b.p_type in ('5') $orderdate_qry";
	
	
	$s_rst2 = $dbConn->query($s_qry2);
	$row2 = $s_rst2->fetch_assoc();
	$s_amt2 = $row2[total_sum];

	//print_r($s_qry2);
	$s_qry3 ="select COALESCE(sum(a.last_total),0) as total_sum,COALESCE(sum(a.p_cnt),0) as total_mem from reserve_info a,product_master b  where a.p_code=b.p_code && a.parent = 'MAIN' && a.rev_status in ('DONE','PLAY') && b.p_type in ('1') $orderdate_qry";
	
	$s_rst3 = $dbConn->query($s_qry3);
	$row3 = $s_rst3->fetch_assoc();
	$s_amt3 = $row3[total_sum];


	$qry1 = "select *,a.last_total as total_amt from reserve_info a,member_list b where b.division='admin' && a.userid=b.userid && a.parent = 'MAIN' && a.rev_status in ('DONE','PLAY')   $orderdate_qry1 group by a.userid order by a.last_total desc";
	$rst1 = $dbConn->query($qry1);
   
	//print_r($qry1);
	while($row1 = $rst1->fetch_assoc()){

			
			$p_qry1 = "select COALESCE(sum(a.last_total),0) as total_sum, COALESCE(count(*),0) as cnt,COALESCE(sum(a.p_cnt),0) as pcnt from reserve_info a,product_master b where a.p_code=b.p_code &&  a.userid = '$row1[userid]' && a.parent = 'MAIN' && a.rev_status in ('DONE','PLAY') $orderdate_qry && b.p_type in ('2')";
			//echo $p_qry1.'<br />';
			$p_rst1 = $dbConn->query($p_qry1);
			$row2 = $p_rst1->fetch_assoc();
	        $local_amt = $row2[total_sum];
			$local_cnt = $row2[cnt];
			$local_mem = $row2[pcnt];
            
      
			$p_qry3 = "select COALESCE(sum(a.last_total),0) as total_sum, COALESCE(count(*),0) as cnt,COALESCE(sum(a.p_cnt),0) as pcnt from reserve_info a,product_master b where a.p_code=b.p_code &&  a.userid = '$row1[userid]' && a.parent = 'MAIN' && a.rev_status in ('DONE','PLAY') $orderdate_qry && b.p_type in ('1')";
			//print_r($p_qry3);
			$p_rst3 = $dbConn->query($p_qry3);
			$local_amt3 = dbMysql_result($p_rst3,0,0);
			$local_cnt3 = dbMysql_result($p_rst3,0,1);
			$local_mem3 = dbMysql_result($p_rst3,0,2);
			//echo $p_qry3.'<br />';
			/*
			if($local_amt)
			{
				$local_amt = "$local_cnt �� ($local_mem ��) / $$local_amt";
			}
			else
			{
				$local_amt = "&nbsp;";
			}
			*/

			$p_qry2 = "select COALESCE(sum(a.last_total),0) as total_sum, COALESCE(count(*),0) as cnt,COALESCE(sum(a.p_cnt),0) as pcnt from reserve_info a,product_master b where a.p_code=b.p_code &&  a.userid = '$row1[userid]' && a.parent = 'MAIN' && a.rev_status in ('DONE','PLAY') $orderdate_qry && b.p_type in ('5')";
			$p_rst2 = $dbConn->query($p_qry2);
			$outbound_amt = dbMysql_result($p_rst2,0,0);
			$outbound_cnt = dbMysql_result($p_rst2,0,1);
			$outbound_mem = dbMysql_result($p_rst2,0,2);
			
			/*
			if($outbound_amt)
			{
				$outbound_amt = "$outbound_cnt �� ($outbound_mem ��) / $$outbound_amt";
			}
			else
			{
				$outbound_amt = "&nbsp;";
			}
			*/

            $userinfo = getinfo_dbMember($row1[userid]);
			// �ιٿ�� %���
			$MAX_barsize = 400;
			$allocate_rate = @round(($local_amt/$s_amt1)*100); 		//��ǥ��
			$bar_width = @round(($local_amt/$s_amt1)*$MAX_barsize); 	// �̹��� ������
      
      
            $MAX_barsize = 400;
			$allocate_rate3 = @round(($local_amt3/$s_amt3)*100); 		//��ǥ��
			$bar_width3 = @round(($local_amt3/$s_amt3)*$MAX_barsize); 	// �̹��� ������

			// �ƿ�ٿ�� %���
			$allocate_rate2 = @round(($outbound_amt/$s_amt2)*100); 		//��ǥ��
			$bar_width2 = @round(($outbound_amt/$s_amt2)*$MAX_barsize); 	// �̹��� ������


		$content .= "<tr onMouseOver=\"this.style.backgroundColor='#E3E3E3'\" onMouseOut=\"this.style.backgroundColor=''\">
						<td align=left bgcolor=#ffffff>&nbsp;$userinfo[kor_name]($row1[userid])</td>
						<td align=right bgcolor=#ffffff><font style='font-size:8pt'>Local&nbsp;<br>OutBound</font>&nbsp;</td>
						<td bgcolor=#ffffff>&nbsp;<img src=img/graph04.gif width=$bar_width3 height=10> $".number_format($local_amt3,2)." (".$local_cnt3.")
						<br>
				&nbsp;<img src=img/graph02.gif width=$bar_width2 height=10> $".number_format($outbound_amt,2)." (".$outbound_cnt.")</td>
				</tr>";

	}


				
?>
  <div id="contentwrapper" class="reservationDetailForm">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb 
			module">
				<ul>
					<li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li>
					<li><a href="#">MIS</a></li>
					<li>매출현황</li>
					<li>직원별 매출현황</li>
				</ul>
			</div>
			 
			  <FORM action=graph_sales_employee.php method=post>
			  <input type=hidden name=division value="<?= $division ?>">
			  <input type=hidden name=Mode value="SEARCH">
			  <table class="table table-striped table-bordered table-hover table-condensed js-productTable2">
				<tr>
					<td width=15% bgcolor=#f4f4f4 height=30><select name='type' class='form-control'>
					<? $option0 = ($type == "0") ? ('<option value="0" selected>출발일</option>') : ('<option value="0">출발일</option>'); echo $option0 ?>
					<? $option1 = ($type == "1") ? ('<option value="1" selected>판매일</option>') : ('<option value="1">판매일</option>'); echo $option1 ?>
					</select></td>
					<td width=85% bgcolor=#FFFFFF>
						<div class="row">
							<div class="col-sm-3">
								<div class="input-group input-group-sm">
									<input type="text" name="StartYMD" data-date-format='yyyy-mm-dd' class="form-control md js-dateInputWithBlocks js-tourDates tourDate1" aria-label="조회기간" placeholder="조회기간" autocomplete='off' value='<?=$start_date?>'>
									<span class="input-group-btn">
										<button class="btn btn-default js-dateInputBtn" type="button"><span class="glyphicon glyphicon-calendar" aria-hidden="true"></span></button>
									</span>
								</div>
							</div>
							<div class="col-sm-3">
								<div class="input-group input-group-sm">
									<input type="text" name="EndYMD" data-date-format='yyyy-mm-dd' class="form-control md js-dateInputWithBlocks js-tourDates tourDate2" aria-label="조회기간" placeholder="조회기간" autocomplete='off' value='<?=$stop_date?>'>
									<span class="input-group-btn">
										<button  class="btn btn-default js-dateInputBtn" type="button"><span class="glyphicon glyphicon-calendar" aria-hidden="true"></span></button>
									</span>
								</div>
							</div>
						</div>
                   </td>
				</tr>
				<tr>
					<td colspan=2 height=35 align=center bgcolor=#FFFFFF><button  class="btn btn-success" type='submit'>검색하기</button></td>
				</tr>
				</form>
			  </table>
			  
			  <table class="table table-striped table-bordered table-hover table-condensed js-productTable2">
				<tr bgcolor=#b2dcca height=28>
					<td width=20% align=center>직원명</td>
					<td width=10% align=center>구분</td>
					<td width=70% align=center>매출액</td>
				</tr>
			  <?= $content ?>
			  </table>
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
			$('.tourDate1').datepicker({
				format: "yyyy-mm-dd",
				autoclose: true
				
			});
			$('.tourDate2').datepicker({
				format: "yyyy-mm-dd",
				autoclose: true
			});

			

			//$(".dataTables_length").css({ "display" :"none" });
		})

</script>