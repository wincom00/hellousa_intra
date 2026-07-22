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
	function printAmt(){
			
			global $dbConn,$cname,$division,$pdx,$sub,$seldate,$StartYMD,$user_dbinfo,$typer;
			

			if ($seldate == '1') {
				
						$qrysdate = " && year(b.revDate) = '$StartYMD'";
				 
			} else if ($seldate == '2') {
				
						$qrysdate = " && year(b.wdate) = '$StartYMD'";
						
				
			} 
			
			/*
			$qry1 = "select a.p_day,b.p_code,b.p_name,
								sum(case when month(b.revDate) = 1 $qrysdate
										   then b.last_total else 0 end) as '1month',
								sum(case when month(b.revDate) = 2 $qrysdate
										   then b.last_total else 0 end) as 2month,
								sum(case when month(b.revDate) = 3 $qrysdate
										   then b.last_total else 0 end) as 3month,
								sum(case when month(b.revDate) = 4 $qrysdate
										   then b.last_total else 0 end) as 4month,
								sum(case when month(b.revDate) = 5 $qrysdate
										   then b.last_total else 0 end) as 5month,
						        sum(case when month(b.revDate) = 6 $qrysdate
										   then b.last_total else 0 end) as 6month,
								sum(case when month(b.revDate) = 7 $qrysdate
										   then b.last_total else 0 end) as 7month,
								sum(case when month(b.revDate) = 8 $qrysdate
										   then b.last_total else 0 end) as 8month,
								sum(case when month(b.revDate) = 9 $qrysdate
										   then b.last_total else 0 end) as 9month,
								sum(case when month(b.revDate) = 10 $qrysdate
										   then b.last_total else 0 end) as 10month,
								sum(case when month(b.revDate) = 11 $qrysdate
										   then b.last_total else 0 end) as 11month,
								sum(case when month(b.revDate) = 12 $qrysdate
										   then b.last_total else 0 end) as 12month
								from product_master a ,reserve_info b 
								where a.p_code = b.p_code && b.rev_status = 'DONE' $qrysdate
								group by a.p_day,b.p_code order by  a.p_day asc
					  
					  ";
					  */
		    if (($typer ==1) || ($typer =='')) {
				$qry1 = "select a.p_day,b.p_code,b.p_name,
									sum(case when month(b.revDate) = 1 $qrysdate
											   then b.last_total else 0 end ) as tt1,
									sum(case when month(b.revDate) = 2 $qrysdate
											   then b.last_total else 0 end ) as tt2,
									sum(case when month(b.revDate) = 3 $qrysdate
											   then b.last_total else 0 end ) as tt3,
									sum(case when month(b.revDate) = 4 $qrysdate
											   then b.last_total else 0 end ) as tt4,
									sum(case when month(b.revDate) = 5 $qrysdate
											   then b.last_total else 0 end ) as tt5,
									sum(case when month(b.revDate) = 6 $qrysdate
											   then b.last_total else 0 end ) as tt6,
									sum(case when month(b.revDate) = 7 $qrysdate
											   then b.last_total else 0 end ) as tt7,
									sum(case when month(b.revDate) = 8 $qrysdate
											   then b.last_total else 0 end ) as tt8,
									sum(case when month(b.revDate) = 9 $qrysdate
											   then b.last_total else 0 end ) as tt9,
									sum(case when month(b.revDate) = 10 $qrysdate
											   then b.last_total else 0 end ) as tt10,
									sum(case when month(b.revDate) = 11 $qrysdate
											   then b.last_total else 0 end ) as tt11,
									sum(case when month(b.revDate) = 12 $qrysdate
											   then b.last_total else 0 end ) as tt12
									
							from product_master a ,reserve_info b 
									where a.p_code = b.p_code && b.rev_status = 'DONE' $qrysdate
									group by a.p_day,b.p_code order by  a.p_day asc";
			} else {
								$qry1 = "select a.p_day,b.p_code,b.p_name,
									sum(case when month(b.revDate) = 1 $qrysdate
											   then b.p_cnt else 0 end ) as tt1,
									sum(case when month(b.revDate) = 2 $qrysdate
											   then b.p_cnt else 0 end ) as tt2,
									sum(case when month(b.revDate) = 3 $qrysdate
											   then b.p_cnt else 0 end ) as tt3,
									sum(case when month(b.revDate) = 4 $qrysdate
											   then b.p_cnt else 0 end ) as tt4,
									sum(case when month(b.revDate) = 5 $qrysdate
											   then b.p_cnt else 0 end ) as tt5,
									sum(case when month(b.revDate) = 6 $qrysdate
											   then b.p_cnt else 0 end ) as tt6,
									sum(case when month(b.revDate) = 7 $qrysdate
											   then b.p_cnt else 0 end ) as tt7,
									sum(case when month(b.revDate) = 8 $qrysdate
											   then b.p_cnt else 0 end ) as tt8,
									sum(case when month(b.revDate) = 9 $qrysdate
											   then b.p_cnt else 0 end ) as tt9,
									sum(case when month(b.revDate) = 10 $qrysdate
											   then b.p_cnt else 0 end ) as tt10,
									sum(case when month(b.revDate) = 11 $qrysdate
											   then b.p_cnt else 0 end ) as tt11,
									sum(case when month(b.revDate) = 12 $qrysdate
											   then b.p_cnt else 0 end ) as tt12
									
							from product_master a ,reserve_info b 
									where a.p_code = b.p_code && b.rev_status = 'DONE' $qrysdate
									group by a.p_day,b.p_code order by  a.p_day asc";




			}
			$rst1 = $dbConn->query($qry1);
			//echo $qry1;
			///exit;
			$totamt = 0;
			while($row1 = $rst1->fetch_assoc()) {
				
				$k= 0;
				$r =0;
			     foreach( $row1 as $value ) {
					  if ($k != 15) {
						   if ($k ==0) {
							   $content .="<tr>";
								if ($value==1) {
									$value = "당일";
								}

						   }
						   if ($k !=1) {
							$content .= "<td align='center'>$value</td>";
						   } 
							if ($k >= 2) {
								$tot =$tot + $value;
							}

							$k++;	
					   } 
					  
					 // echo $value."<br/>";
					   
				  }
				  $content .= "<td align='center'>$tot</td>";
							$content .="</tr>";
							$tot = 0;
							$k=0;

				  echo $content;
				  $content ="";
				//print_r($row1);			//exit;
		
			
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
					<li>월별/상품별 매출</li>
				</ul>
			</div>
			<div class="row">
				<div class="col-sm-12 col-md-12">
					<form action=""  method="post" name="frmName">
						<table class="table table-bordered table-condensed gridSixteen reserveTable formDetail">
                            <tbody>
                                <tr>
                                    <td  width=15%   class="text-center formHeader">
                                        <select class="form-control" name="seldate">
                                            
                                            <option <?php if (($seldate == "1")) { ?> selected <?php } ?> value="1" >출발일</option>
                                            <option <?php if ($seldate == "2") { ?> selected <?php } ?> value="2">판매일</option>
                                        </select>
                                    </td>

									<td  width=15%   class="text-center formHeader">
                                        <select class="form-control" name="typer">
                                            
                                            <option <?php if (($typer == "1")) { ?> selected <?php } ?> value="1" >매출액</option>
                                            <option <?php if ($typer == "2") { ?> selected <?php } ?> value="2">인원수</option>
                                        </select>
                                    </td>
                                    <td  width=15%>
                                        <div class="row">
                                            <div class="col-sm-10">
                                                <div class="input-group input-group-sm">
                                                    <input type="year" name="StartYMD" data-date-format='yyyy' class="form-control tourdate1" aria-label="조회기간" placeholder="조회기간" autocomplete="off" value="<?=$StartYMD ?>">
                                                </div>
                                            </div>
                                            
                                        </div>
                                    </td>
									<td   class="text-left"><button type='submit' class="btn btn-primary btn-sm btn1">검색</button></td>
                                    
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
										<th>일차</th>
										<th width="10%">투어명</th>
										<th>1월</th>
										<th>2월</th>
										<th>3월</th>
										<th>4월</th>
										<th>5월</th>
										<th>6월</th>
										<th>7월</th>
										<th>8월</th>
										<th>9월</th>
										<th>10월</th>
										<th>11월</th>
										<th>12월</th>
										<th>총액</th>
										
									</tr>
								</thead>
								<tbody>
									<?php printAmt(); ?>
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
            //$.ajaxSetup({async:false});
			$('.tourdate1').datepicker({
				format: "yyyy",
				viewMode: "years", 
				minViewMode: "years",
				autoclose: true
			
			});
		});
</script>
	</body>
</html>