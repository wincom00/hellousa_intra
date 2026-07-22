<?php
    include "include/header.php";
	
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

	function printPay(){
			
			global $dbConn,$cname,$division,$crev,$pdx,$sub,$seldate,$startDate,$endDate,$employeeName,$searchpay,$user_dbinfo;
			

			
			if ($seldate == '1') {
				if ($startDate) {
						$qrysdate = " && ((  a.revDate >= '$startDate' && a.revDate <= '$endDate' )) ";
						
				} 
			} else if ($seldate == '2') {
				if ($startDate) {
					    $startDate = "$startDate 00:00:00";
			            $endDate = "$endDate 23:23:59";
						$qrysdate = " && ((  a.stDate >= '$startDate' && a.stDate <= '$endDate' )) ";
						
				}
			} else if ($seldate == '3') {
				if ($startDate) {
					    $startDate = "$startDate 00:00:00";
			            $endDate = "$endDate 23:23:59";
						$qrysdate = " && ((  b.paydate >= '$startDate' && b.paydate <= '$endDate' )) ";
						
				} 
			
			} else {
				
				$sdate = date("Y-m-d");
				$sdate = "$sdate 23:23:59";
				$edate = date("Y-m-d",strtotime("-30 day"));
				$edate = "$edate 00:00:00";
				$qrysdate =" && (( a.revDate >= '$edate' && a.revDate <= '$sdate' )) ";
			}

			if ($searchpay) {
					$qrypay = " && b.conf_p='$searchpay'";

			} else {
				    $qrypay ="";
			}

			if ($employeeName) {
					$qryemp = " && a.userid='$employeeName'";

			} else {
				    $qryemp ="";
			}

			
			//&& a.rev_status not in ('CANCEL')
			/*$qry1 = "select distinct a.rev_status,a.grand_revNo,a.reserveCode,a.p_code,a.p_name,a.book_pri,a.revDate,a.stDate,a.edDate,a.last_total,a.last_bal,a.p_cnt,a.base_rate,a.userid,b.payment_status,a.pricet,b.pay_method as pmethod,rate_m as rm,
			            b.* ,DATE_FORMAT(b.wdate, '%Y-%m-%d')  as wwdate,b.register as pregister from reserve_info a,payment_history b,reserve_traveler c,product_master d
					  where a.reserveCode=b.reserveCode  && b.pay_method != 'init' && b.payment_status not in ('RRQUEST') && a.reserveCode = c.reserveCode && a.p_code=d.p_code
					  && a.parent ='MAIN' $qrycname $qrysdate $qrypay $qryemp $deptqry order by a.revDate desc
					  
					  ";
			*/
			$qry1 = "select  a.*,sum(a.p_cnt) as total_mem, b.paydate,b.register from reserve_info a left outer join (select reserveCode,register,max(wdate) as paydate from payment_history where payment_status = 'DONE' group by reserveCode ) b on a.reserveCode=b.reserveCode,
					product_master c where a.p_code = c.p_code && c.p_type='5'
					&& a.parent = 'MAIN' && a.rev_status in ('DONE')  && a.settle_report != '0' $qryemp $qrysdate GROUP BY a.reserveCode
					";
			
			$k=0;
			///echo $qry1;
			///exit;
			$rst1 = $dbConn->query($qry1);
			$total_member = 0;
			$total_sum = 0;
			$total_bal_sum = 0;
			$rtn_amt = 0;
			while($row1 = $rst1->fetch_assoc()){
				$renm = getReserveTrRepre($row1[reserveCode]);
				if ($row1[base_rate] == "CAD") {
					$sign = "$";
					$row1[rate_m] = 0;
			    } else {
					$sign = "$";
			    }
				
				
				$uinfo=getinfo_dbMember($row1[register]);
				$uinfo1=getinfo_dbMember($row1[userid]);
				
				$agent_sum_qry = "select sum(amt) as agent_sum from rand_company_tmp where reserveCode = '$row1[reserveCode]' && money_type='credit' && p_memo != '발권' group by reserveCode";
				$cagent_sum_rst = $dbConn->query($agent_sum_qry);
				$agent_sum = @dbMysql_result($cagent_sum_rst, 0, 0);
				
				$rand_sum_qry = "select sum(amt) as rand_sum from rand_company_tmp where reserveCode = '$row1[reserveCode]' && money_type='debit' && p_memo != '발권' group by reserveCode";
				$rand_sum_rst = $dbConn->query($rand_sum_qry);
				$rand_sum  = @dbMysql_result($rand_sum_rst, 0, 0);
				

				$air_sum_qry = "select sum(a_airline_amt + case when rand_fee is null then 0 else rand_fee end) as air_sales_sum, sum(a_amt) as air_net_sum, min(a_airline_print) as air_date from reserve_airline_pnr where reserveCode = '$row1[reserveCode]'";
				$air_sum_rst = $dbConn->query($air_sum_qry);
				$air_sales_sum = @dbMysql_result($air_sum_rst, 0, 0);
				$air_net_sum = @dbMysql_result($air_sum_rst, 0, 1);
				$air_date = @dbMysql_result($air_sum_rst, 0, 2);

				$cc_pmt_fee_qry = "select sum(payment * 0.04) as cc_proc_fee from payment_history where reserveCode = '$row1[reserveCode]' && payment_status = 'DONE' && pay_method = 'bcreditcard'";
				$cc_pmt_fee_rst = $dbConn->query($cc_pmt_fee_qry);
				$cc_pmt_fee = @dbMysql_result($cc_pmt_fee_rst, 0, 0);
				


				$rtnqry1 = "select sum(payment) as rtnamt from payment_history where reserveCode = '$row1[reserveCode]'  && payment_status = 'RETURN' order by wdate asc";
				$rtn_rst = $dbConn->query($rtnqry1);
				$rtn_amt = @dbMysql_result($rtn_rst, 0, 0);
                

				$pqry1 = "select sum(payment) as pamt from payment_history where reserveCode = '$row1[reserveCode]'  && payment_status = 'READY' order by wdate asc";
				$p_rst = $dbConn->query($pqry1);
				$ob_agent_sales = @dbMysql_result($p_rst, 0, 0);

				//$ob_agent_sales1 = ($ob_agent_sales+$agent_sum+$air_sales_sum);
				$ob_agent_profit = $ob_agent_sales - ($rand_sum + $air_net_sum +$cc_pmt_fee+$rtn_amt);
              // echo $ob_agent_sales."|".$agent_sum."|".$air_sales_sum."<br />";
				echo "<tr>
						<td align='center'>$row1[revDate]</td>
						<td align='center'><a href=javascript:openwin1('$row1[reserveCode]','$row1[pricet]')  >$row1[stDate]<br/>$row1[reserveCode]</a></td>
						<td align='center'>$row1[paydate]</td>
						<td><a href=javascript:openwin('$row1[reserveCode]','$row1[p_code]')>$row1[p_name]</td>
						<td align='center'>$renm[traveler_nm]</td>
						<td align='center'>$row1[p_cnt]</td>
						<td align='right'>$" . number_format($ob_agent_sales, 2) . "&nbsp;</td>
									
						<td align='right'>$" . number_format($ob_agent_profit, 2) . "&nbsp;</td>
						
						<td align='center'>$uinfo[kor_name]</td>
						<td align='center'>$uinfo1[kor_name]</td>
						";
				if (!isset($rtn_amt)) {

					echo "
						<td align='center'>$" . number_format($row1['last_bal'], 2) . "&nbsp;</td> 
					</tr>";
				} else {
					echo "
						<td align='center'>$" . number_format($row1['last_bal'], 2) . "&nbsp;(<font color=red>$".$rtn_amt."</font>)</td> 
					</tr>";

				}
				$k++;

				$total_member +=  $row1[p_cnt];
				$total_sum += $ob_agent_sales;
				$total_bal_sum += $row1[last_bal]; 
				$total_profit += $ob_agent_profit;
			}
			$total_sum_print = number_format($total_sum,2);
			$total_profit_print = number_format($total_profit,2);
			$total_bal_sum_print = number_format($total_bal_sum,2);

			echo "<tr>
						<td align='center'></td>
						<td align='center'></td>
						<td align='center'></td>
						<td></td>
						<td align='center'></td>
						<td align='center'>$total_member</td>
						<td align='center'>$$total_sum_print</td>
									
						<td align='center'>$$total_profit_print</td>
						
						<td align='center'></td>
						<td align='center'></td>
						<td align='center'>$$total_bal_sum_print</td> 
					</tr>";

	}
	
?>
	<div id="contentwrapper" class="reservationDetailForm">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb 
			module">
				<ul>
					<li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li>
					<li><a href="#">정산현황</a></li>
					<li>아웃바운드정산현황</li>
				</ul>
			</div>
			<div class="row">
				<div class="col-sm-12 col-md-12">
					<form action=""  method="post" name="frmName">
						<table class="table table-bordered table-condensed gridSixteen reserveTable formDetail">
                            <tbody>
                                <tr>
                                    <td colspan="2" class="text-center formHeader">
                                        <select class="form-control" name="seldate">
                                            <option value="">- 정산일선택 -</option>
                                            <option <?php if (($seldate == "1")) { ?> selected <?php } ?> value="1" >판매일</option>
                                            <option <?php if ($seldate == "2") { ?> selected <?php } ?> value="2">출발일</option>
											 <option <?php if ($seldate == "3") { ?> selected <?php } ?> value="3">결제일</option>
                                        </select>
                                    </td>
                                    <td colspan="5">
                                        <div class="row">
                                            <div class="col-sm-5">
                                                <div class="input-group input-group-sm">
                                                    <input type="text" name="startDate" data-date-format='yyyy-mm-dd' class="form-control js-dateInputWithBlocks js-tourDates tourDate1" aria-label="조회기간" placeholder="조회기간" autocomplete='off' value='<?=$startDate?>'>
                                                    <span class="input-group-btn">
                                                        <button class="btn btn-default js-dateInputBtn" type="button"><span class="glyphicon glyphicon-calendar" aria-hidden="true"></span></button>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="col-sm-5">
                                                <div class="input-group input-group-sm">
                                                    <input type="text" name="endDate" data-date-format='yyyy-mm-dd' class="form-control js-dateInputWithBlocks js-tourDates tourDate2" aria-label="조회기간" placeholder="조회기간" autocomplete='off' value='<?=$endDate?>'>
                                                    <span class="input-group-btn">
                                                        <button class="btn btn-default js-dateInputBtn" type="button"><span class="glyphicon glyphicon-calendar" aria-hidden="true"></span></button>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
									
                                    <td colspan="2" class="text-center formHeader">
                                        <select class="form-control" name="employeeName">
                                            <option value="">- 직원선택 -</option>
                                            <?=employeeoutlist($employeeName)?>
                                        </select>
                                    </td>
                                    
                                    <td colspan="3" class="text-center"><button type='submit' class="btn btn-primary btn-sm btn1">검색</button></td>
                                </tr>
                            </tbody>
                        </table>
					</form>
					<br />
					<div class="row">
						<div class="col-sm-12">
							<table class="table table-striped table-bordered table-hover table-condensed js-productTable2">
								<thead>
									<tr>
									    <th>판매일자</th>
										<th>출발일자</th>
										<th>결제일</th>
										<th>상품명</th>
										<th>고객이름</th>
										<th>모객수</th>
										<th>최종결제금액</th>
										<th>수익</th>
										<th width='5%'>결제자</th>
										<th width='5%'>담당자</th>
										<th>발란스</th>
									</tr>
								</thead>
								<tbody>
									<?=printPay()?>
								</tbody>
							</table>
						</div>
					</div>
					<br/>
					
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
			$('.tourDate1').datepicker({
				format: "yyyy-mm-dd",
				autoclose: true
				
			});
			$('.tourDate2').datepicker({
				format: "yyyy-mm-dd",
				autoclose: true
			});

			$('.js-productTable2').DataTable( {
				 dom: 'Bfrtip',
				buttons: [
						'copy', 'csv', 'excel', 'print'
					 ],
				"order": [[ 1, "desc" ]]
			} );


			$(".dataTables_length").css({ "display" :"none" });
		})
		function appbtn(obj){

				var tmp = $(obj).val();
				var tmpstr = tmp.split("/");

				var num =tmpstr[0];
				var seq =tmpstr[1];
				
				$.ajax({
							type: "POST",
							url: "update_acc.php?seq="+seq,
							data: "",
							dataType: "json",
							success: function(data) {
								if (data==1)
								{
									alert("확인되었습니다.!!");
									$("#accspan"+num).html(""); 
									$("#accspan"+num).html("확인완료"); 
								}
							},
							error: function(){
								  alert('저장 에러 !!');
							}
				  }); 
				  
				  

		}
		var ctr=0;
	    function openwin(r_code,pcode) { 
			
	       var winName = "all_"+(ctr++);
		   window.open("pay_hist.php?r_code="+r_code+"&pcode="+pcode+"",winName,"width=1000,height=600,scrollbars=1");
	    }
		function openwin1(r_code,pricet) { 
	       var winName = "all_"+(ctr++);
		   window.open("base_reservation_m.php?estimateCode="+r_code+"&pricet="+pricet+"&division=3&pdx=2&sub=15",winName,"width=900,height=1080,scrollbars=1");
	    }
	</script>
    </body>
</html>
