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
    //페이징 Limit
	 $start=$_REQUEST['start'];
	 
     if (empty($start))
	 {
		$start = 0;
	 }
	 $k = $start+1;	
	 $board_scale = 25;
	 $board_page = 10;
	 $type=$_GET['type'];
	 /**
	 * 한페이지당 글수
	 */
	 $scale=$board_scale;

	 /*
	 * 한페이지당 페이지수
	 */
	 $page_scale=$board_page;

	

	function printsettle(){
			
		global $dbConn,$cname,$division,$crev,$pdx,$sub,$seldate,$startDate,$endDate,$employeeName,$user_dbinfo,$productName,$tknum,$pnrnum,$page,$start,$total,$scale,$_REQUEST,$page_scale,$page_last,$page_total;

			

			if ($productName) {
					$qrynm = " && a.p_name like '%$productName%'";

			} else {
				    $qrynm ="";
			}
			if ($cname !="") {
			
		
			      $qrycname= " && (a.book_pri like '"."%".$cname."%"."')";

	        }
			
			if ($seldate == '2') {
				if ($startDate) {
					    $startDate = "$startDate 00:00:00";
			            $endDate = "$endDate 23:23:59";
						$qrysdate = " && ((  a.stDate >= '$startDate' && a.stDate <= '$endDate' )) ";
						
				} 
		    } else if ($seldate == '3') {
				if ($startDate) {
					    $startDate = "$startDate 00:00:00";
			            $endDate = "$endDate 23:23:59";
						$qryrdate = " && ((  a.revDate >= '$startDate' && a.revDate <= '$endDate' )) ";
						 
				} 
		    } else if ($seldate == '1'){
				
				$sdate = date("Y-m-d");
				$sdate = "$sdate 23:23:59";
				$edate = date("Y-m-d",strtotime("-31 day"));
				$edate = "$edate 00:00:00";
				$qrysdate =" && (( a.revDate >= '$edate' && a.revDate <= '$sdate' )) ";
			}

			
			
			
			if ($employeeName) {
					$qryemp = " && a.userid='$employeeName'";

			} else {
				    $qryemp ="";
			}
            ////////////////////////////////////////////////////
			//$query = "SELECT *, 0 as tot_pages, 0 as total_records, category FROM royal_board WHERE ".$front_yn." AND  tablename = '".$table_id. "' $_where1  $_where2  $_where3   $order_by LIMIT $start , $scale ";
		   // echo $query;
			$query = "select  a.reserveCode,a.p_code,a.p_name,a.book_pri,a.revDate,a.stDate,a.edDate,a.last_total,a.p_name,
			a.last_bal,a.p_cnt,a.base_rate ,a.rev_status,a.userid,a.payment_st ,a.settle_report
			from reserve_info a,product_master b
			where a.p_code=b.p_code && a.rev_status != 'CANCEL'  && b.p_type='5' $qryrdate $qrycname $qrysdate $qryemp order by a.revDate desc LIMIT $start , $scale";
			$page=floor($start/($scale*$page_scale));
		//echo $query;
			$result=$dbConn->query($query);
			$result_rows=$result->num_rows;

			

			$total=$dbConn->affected_rows;
			$last=floor($total/$scale);



            //echo $last;
		    
			/**
			* 페이징을 위한 토탈을 구한다.
			*/
			$query_b = "select  count(a.reserveCode) cnt
			from reserve_info a,product_master b
			where a.p_code=b.p_code && a.rev_status != 'CANCEL'  && b.p_type='5' $qryrdate $qrycname $qrysdate $qryem";
	//	echo $query_b;
			$result2=$dbConn->query($query_b);
			$row2=$result2->fetch_array();
			$page_total = $row2[cnt];
	     	$page_last = floor($page_total/$scale);

			/**
			* 총 페이지수
			*/
			$total_page_num = ceil($page_total/$scale);

			$now_page_num = floor($start/$scale) + 1;

			if($start)
			{
				$n=$page_total-$start;
			}
			else
			{
				$n=$page_total;
			}

		
		    ////////////////////////////////////////////////////
						
			if($page_total != "0")
			{
					
					while($row1 = $result->fetch_assoc())
					{
						if ($cname =="") {
							$renm = getReserveTrRepre($row1[reserveCode]);
						} else {
							$renm = getReserveTrRepre2($row1[reserveCode]);
						}
						$AirIn = getAirInfo($row1[reserveCode],$pnrnum,$startDate,$endDate,$seldate,$tknum);
						
						$sign = "$";
						
						$totamt = $sign.$row1[last_total];
						$balamt = $sign.$row1[last_bal];
						
						
						if ($row1[settle_report] == "1") {

							$statcap = "정산대기";
						} else if ($row1[settle_report] == "2") {
							$statcap = "정산보고완료";

						} else {
							$statcap = "정산보고대기<br />가예약";

						}
						
						$uinfo=getinfo_dbMember_byuser($row1[userid]);
						$rinfo1=getAirtmRandInfo($row1[reserveCode]);
						$rinfo=getinfo_dbMember_byuser($rinfo1[part_id]);
						$airInfo=getDtmRandInfo($row1[reserveCode]);
						
						$ainfo = getinfo_dbMember_byuser($airInfo[part_id]);
						//echo $ainfo[kor_name]."<br/>";
						//print_r($airInfo);
						if ($row1[conf_p] == '2') {

							$btnv = "확인완료";
						} else {
							$btnv ="<button type='button' class='btn btn-xs btn-default js-money' value='$k/$row1[seq_no]' OnClick='appbtn(this)'>회계확인</button>";
						}

						if ($row1[rev_status]== 'READY') {
							$row1[rev_status] = "<font color=#0984a3>예약접수</font>";
						}
						
						if ($row1[rev_status]== 'DONE') {
							$row1[rev_status] = "<font color=#911f77>예약확정</font>";
						}
						
						if ($row1[rev_status]== 'CANCEL') {
							$row1[rev_status] = "<font color=#e02133>예약취소</font>";
						}
						if ($row1[payment_status]== 'RETURN') {
							$row1[rev_status] = "<font color=RED>환불완료</font>";
						}	
							
						$k++;
						// table 뿌려주기
						echo "<tr>
						<td align='center'><a href=javascript:openwin1('$row1[reserveCode]','$row1[pricet]')  >$row1[revDate]<br/>$row1[reserveCode]</a></td>
						<td align='center'>$uinfo[kor_name]<br />$AirIn[a_airline_print]</td>
						<td><a href=javascript:openwin('$row1[reserveCode]','$row1[p_code]')>$row1[p_name]<br /> $row1[stDate] ~ $row1[edDate]</td>
						<td align='center'><a href=javascript:openwin('$row1[reserveCode]','$row1[p_code]')   >$renm[traveler_nm]</a></td>
						<td align='center'>$row1[p_cnt]</td>
						<td align='center'>$row1[rev_status]</td>
						<td align='right'>$totamt<br /><font color=red>$balamt</font></td>
						
						<td align='center'>$ainfo[kor_name]</td>
						<td align='right'><a href=javascript:openwin('$row1[reserveCode]','$row1[p_code]')>#pnr $AirIn[a_pnr_number]<br />#Tk $AirIn[a_tk_number]</a></td>
						
						<td align='center'>$rinfo[kor_name]</td>
						<td align='center'>$statcap</td> 
					    </tr>";
						
					}
					


			}
			else
			{
					echo "
								<tr> 
								  <td align=center colspan=6 height=50>목록이 없습니다!</td>
								</tr>
					";
			}


			//////////////////////////////////////////////////////
			

	}

    function board_pageNavigation(){

		global $dbConn,$page,$start,$total,$scale,$page_scale,$page_last,$page_total,$cname,$division,$crev,$pdx,$sub,$seldate,$startDate,$endDate,$employeeName,$user_dbinfo,$productName,$tknum,$pnrnum;
		//echo $start;
		$Parameter_value = "startDate=$startDate&endDate=$endDate&seldate=$seldate&employeeName=$employeeName";
		
		if($page_total>$scale) //검색 결과가 페이지당 출력수보다 크면
		{
			
			if($start+1>$scale*$page_scale)
			{
				$pre_start=$page*$scale*$page_scale-$scale;
				echo "<li class='page-item'><a class='page-link' href='$PHP_SELF?division=6&pdx=3&sub=15&start=0&$Parameter_value&type=$type'>Previous</a></li>";
			
			}
			for($vj=0; $vj<$page_scale; $vj++)
			{
				$ln=($page * $page_scale+$vj)*$scale;
				$vk=$page*$page_scale+$vj+1;
				
				if($ln<$page_total)
				{
						if($ln!=$start)
						{
						echo "<li class='page-item'><a class='page-link' href='$PHP_SELF?division=6&pdx=3&sub=15&start=$ln&$Parameter_value&type=$type'>$vk</a></li>";
						} else {
						echo "<li class='page-item'><a class='page-link' href='' style=\"background-color:#eee\">$vk</a></li>";
						}		
				}
			}
			if($page_total>(($page+1)*$scale*$page_scale))
			{
				$n_start=($page+1)*$scale*$page_scale;
				$last_start=$page_last*$scale;
				echo "<li class='page-item'><a class='page-link' href='$PHP_SELF?division=6&pdx=3&sub=15&start=$n_start&$Parameter_value&type=$type'>Next</a></li>";
				
			}
		}
	}// pageNavigation function end

	
	
?>
	<div id="contentwrapper" class="reservationDetailForm">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb 
			module">
				<ul>
					<li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li>
					<li><a href="#">직원정산</a></li>
					<li>아웃바운드정산현황</li>
				</ul>
			</div>
			<div class="row">
				<div class="col-sm-12 col-md-12">
					<form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>"  method="post" name="frmName">
					  
						<table class="table table-bordered table-condensed gridSixteen reserveTable formDetail">
                            <tbody>
                                <tr>
                                    <td colspan="2" class="text-center formHeader">
                                        <select class="form-control" name="seldate">
                                            <option value="">- 선택 -</option>
                                            <option <?php if (($seldate == "1")) { ?> selected <?php } ?> value="1" >발권일</option>
                                            <option <?php if ($seldate == "2") { ?> selected <?php } ?> value="2">출발일</option>
											<option <?php if ($seldate == "3") { ?> selected <?php } ?> value="3">등록일</option>
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
                                        <input type="text" id="cname" name="cname" placeholder="고객명" class="inpubase sm" value="<?=$cname?>"/>
                                    </td>
                                    <td colspan="2" class="text-center formHeader">
                                        <select class="form-control" name="employeeName">
                                            <option value="">- 선택 -</option>
                                            <?=employeeoutlist($employeeName)?>
                                        </select>
                                    </td>
                                    <td  class="text-center formHeader">
                                        <input type="text" id="pnrnum" name="pnrnum" placeholder="PNR #" class="inpubase sm" value="<?=$pnrnum?>"/>

                                    </td>
									<td  class="text-center formHeader">
                                        <input type="text" id="tknum" name="tknum" placeholder="TK #" class="inpubase sm" value="<?=$tknum?>"/>

                                    </td>
                                    <td  class="text-center"><button type='submit' class="btn btn-primary btn-sm btn1">검색</button></td>
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
										<th>예약날짜</th>
										<th>등록자<br />발권일</th>
										<th>상품명<br />여행기간</th>
										<th>대표여행자</th>
										<th>인원</th>
										<th>예약상태</th>
										<th>최종결제금액<br />잔금</th>
										
										<th>랜드사</th>
										<th>#PNR/TK</th>
										
										<th>발권여행사</th>
										<th>정산상태</th>
									</tr>
								</thead>
								<tbody>
									<?=printsettle()?>
								</tbody>
							</table>
							 <ul class="pagination justify-content-center">
								<?php board_pageNavigation(); ?>
							</ul>
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
/*
			$('.js-productTable2').DataTable( {
				 dom: 'Bfrtip',
				buttons: [
						'copy', 'csv', 'excel', 'print'
					 ],
				"order": [[ 1, "desc" ]]
			} );


			$(".dataTables_length").css({ "display" :"none" });
			*/
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
		   window.open("invoice_out.php?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&r_code="+r_code,winName,"width=1000,height=700,scrollbars=1");
	    }
		function openwin1(r_code,pricet) { 
	       var winName = "all_"+(ctr++);
		   window.open("base_reservation_m.php?estimateCode="+r_code+"&pricet="+pricet+"&division=3&pdx=2&sub=15",winName,"width=900,height=1080,scrollbars=1");
	    }
	</script>
    </body>
</html>
