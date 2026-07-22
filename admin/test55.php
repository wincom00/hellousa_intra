<?php
	include "include/header.php";
	
	if ($_COOKIE[MEMLOGIN_ADMIN_HELLO] != "") {
	} else {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}

	if($StartYMD)
	{
		$start_date = new DateTime($StartYMD);
	    $end_date = new DateTime($EndYMD); 
	}
	else
	{
		$year = date("Y");
		$month = date("m");
		$day = date("d");

		$StartYMD = date("Y-m-d",mktime(0,0,0,$month,$day -4,$year));
		$EndYMD = date("Y-m-d",mktime(0,0,0,$month,$day +30,$year));
		$start_date = new DateTime($StartYMD);
	    $end_date = new DateTime($EndYMD); 

	}
	
	$interval = new DateInterval('P1D');
	$date_range = new DatePeriod($start_date, $interval, $end_date);
	$StartYMD = $start_date->format('Y-m-d');
	$EndYMD = $end_date->format('Y-m-d');

	$dates = [];
	$datesf = [];
	foreach ($date_range as $date) {
		$dates[] = $date->format('m/d');
		$datesf[] = $date->format('Y-m-d');
	}

	// 요일 생성
	//$days = ['(금)', '(토)', '(일)', '(월)', '(화)', '(수)', '(목)'];
	$days = array( "(월)", "(화)", "(수)", "(목)", "(금)", "(토)","(일)", );

?>


    <style>
        /* DataTables 자체 스타일과 충돌 방지, 명확성 확보 */
    #guide_table {
        width: 100%;
        border-collapse: collapse;
    }

    #guide_table th, #guide_table td {
        border: 0.05em solid #848484;
        padding: 5px;
        text-align: center;
    }

    .highlight {
        background-color: #FFFF99;
    }

    /* 요일별 색상 (DataTables 스타일과 충돌 방지) */
    table.dataTable thead th[style*="color:red"] {
        color: red !important;
    }
    table.dataTable thead th[style*="color:blue"] {
        color: blue !important;
    }

     /* DataTables 스크롤 영역 스타일 */
    div.dataTables_scrollBody {
      overflow-y: scroll !important; /* 세로 스크롤 항상 표시 (선택 사항) */
      overflow-x: auto !important;
       /* maxHeight를 설정해서 스크롤 영역의 최대 높이를 제한합니다. */
        max-height: 600px; /* 원하는 높이로 조절하세요. */
    }
    /*첫번째 열 고정*/
    td:first-child, th:first-child{
        position: sticky;
        left: 0;
        background-color: white;
        z-index: 1;
    }
     /*thead, tbody tr 스타일 지정 */
    table.dataTable thead tr, table.dataTable tbody tr{
        position: relative;

    }
      /* thead와 tbody가 겹치는 문제 해결 */
    table.dataTable thead th, table.dataTable thead td {
        position: relative; /* 추가 */
    }
    </style>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.16/js/jquery.dataTables.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.16/js/dataTables.fixedHeader.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.16/js/dataTables.fixedColumns.min.js"></script>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.16/css/dataTables.bootstrap4.min.css">
   <div id="contentwrapper" class="reservationDetailForm">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb 
			module">
				<ul>
					<li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li>
					<li><a href="#">행사배정관리</a></li>
					<li>가이드배정현황</li>
				</ul>
			</div>
         <div class="row">
				<div class="col-sm-12 col-md-12">
					<form action="" name="frmName" method="post">
						<input type="hidden" name="mode" value="search">
						<table class="table table-bordered table-condensed">
							<tr>
							    <td width="10%" class="titletd text-center">출발일</td>
								<td width="40%" class="">
									<div class="row">
                                        <div class="col-sm-12">
                                            <div class="input-group input-group-sm">
                                                <div class="row">
										<div class="col-sm-6">
											<input type="text" id="startDate1" name="StartYMD" class="inpubase tourDate1" placeholder="시작일" value="<?= $StartYMD ?>" autocomplete=off />
										</div>
										<div class="col-sm-6">
											<input type="text" id="endDate" name="EndYMD" class="inpubase tourDate1" placeholder="마지막일" autocomplete=off value="<?= $EndYMD ?>"/>
										</div>
									</div>
                                            </div>
                                        </div>
                                    </div>
								</td>
								<td width="10%" class="titletd text-center">가이드</td>
								<td width="40%" class="no-right-border">
									<select class="form-control" name="guideSelect">
										<option value="" selected>- 가이드선택 -</option>
										<?=printGuideSelect($guideSelect)?>
									</select>
								</td>
							</tr>
									
							<tr>
								<td colspan="4" class="text-center"><button type='submit' class="btn btn-primary btn-sm btn1">검색(반드시 2개월 이상 조회하세요!!!)</button></td>
							</tr>
						</table>
					</form>
					<br />
                    <h3 style="text-align: center; color: blue;">검색(반드시 2개월 이상 조회하세요!!!)</h3>
					<div class="row" >
						<div class="col-sm-12" style="overflow-x: auto;">
						    <table id="guide_table" class="table table-striped table-bordered text-center ">
                                <thead>
									<tr>
										<th rowspan="2" style="width: 5%;margin:0;border:0.05em solid #848484;">가이드</th>
										
										<?php foreach ($dates as $index => $date): ?>
										    <?php if ($days[$index % 7] == '(일)') { ?>
											    <th style='width: 2.5%;font-size:7pt;color:red'><?= $date ?><br><?= $days[$index % 7] ?></th>
											<?php } else if ($days[$index % 7] == '(토)') { ?>
											    <th style='width: 2.5%;font-size:7pt;color:blue'><?= $date ?><br><?= $days[$index % 7] ?></th>
										    <?php } else  { ?>
												<th style='width: 2.5%;font-size:7pt;'><?= $date ?><br><?= $days[$index % 7] ?></th>
											<?php } ?>
											
										<?php endforeach; ?>
									</tr>
								</thead>
								<?php
								  if ($guideSelect != "") {
										$guideqry = " && a.guide_id = '$guideSelect'";
									}
									$guides =  array();
									$zip_qry1 = "select a.*,b.kor_name from tour_guide a, member_list b ,tour_car c 
																		where a.guide_id=b.userid && division='guide'
																		&& a.p_code=c.p_code && a.stDate=c.stDate && a.grand_eCode =c.grand_eCode && a.sub_eCode = c.sub_eCode	&& a.stDate between '$StartYMD' and '$EndYMD'
																		$guideqry
																		group by a.stDate,a.guide_id order by a.guide_id,a.stDate asc";
									
									/*$zip_qry1 = "select a.*,b.kor_name from tour_guide a, member_list b 
																		where a.guide_id=b.userid && division='guide' 
																		&& a.stDate between '2019-09-01' and '2019-09-20' 
																		group by a.guide_id";*/
									//echo $totalDay ;
									//echo $zip_qry1;
									
									
									$zip_rst1 = mysqli_query($dbConn,$zip_qry1);
								
								?>
								<tbody>
									<?php while ($zip_row1 = mysqli_fetch_assoc($zip_rst1)) { ?>
										<tr>
											<td>
												<?= $zip_row1['kor_name'] ?>
												<?php if (!empty($zip_row1['guide_id'])): ?>
													<br>(<?= $zip_row1['guide_id'] ?>)
												<?php endif; ?>
											</td>
											<?php
											
											$pCode = $zip_row1['p_code'];
											$productInfo = getProductMaster($pCode); 
											
											$guide_start_index = array_search($zip_row1['stDate'], $datesf);
											$startDate = new DateTime($zip_row1['stDate']);  // DateTime 객체 생성
											$interval = new DateInterval("P{$productInfo['p_day']}D"); // 기간 객체 (P[숫자]D 형식)
											$endDate = $startDate->add($interval); // 날짜에 기간을 더함

											$guide_end_index = array_search($endDate->format('Y-m-d'), $datesf);
											$i = 0;
											$is_cell_merged = false;
											foreach ($dates as $index => $date) {
											  if ($index >= $guide_start_index && $index <= $guide_end_index) {
												if (!$is_cell_merged) {
												  // 데이터가 있는 연속된 셀의 범위를 찾습니다.
												  $merge_start_index = $index;
												  $merge_end_index = $index;
												  while ($merge_end_index + 1 <= $guide_end_index && $merge_end_index + 1 < count($dates)) {
													$merge_end_index++;
												  }

												  // colspan을 계산합니다.
												  $colspan = $merge_end_index - $merge_start_index + 1;

												  // 셀을 출력하고 병합합니다.
												  echo "<td class=\"highlight\" colspan=\"{$colspan}\" style='border:1px dotted black;' align=center bgcolor=#FFFF99'><a href=javascript:openwin('{$zip_row1[grand_eCode]}','{$zip_row1[p_code]}','{$zip_row1[sub_eCode]}','{$zip_row1[guide_id]}') >{$zip_row1[p_name]}</a></td>";
												  $is_cell_merged = true;
												  $i += $colspan;
												}
											  } else {
												if (!$is_cell_merged) {
													echo "<td></td>";
													$i++;
												}
											  }
											  // 병합된 셀의 끝에 도달하면 병합 상태를 초기화합니다.
											  if ($index == $guide_end_index) {
												$is_cell_merged = false;
											  }
											}
											?>
										</tr>
									<?php } ?>

									
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div>
   <?php
		include "include/side_m.php"
	?>
	
	<script>
		$(document).ready(function () {
            pt.initReservationList()
            
            pt.initReservationDetail()
			var dateToday = new Date()
			$('.tourDate1').datepicker({
				format: "yyyy-mm-dd",
				autoclose: true
			
			});
			$('.tourDate2').datepicker({
				format: "yyyy-mm-dd",
				autoclose: true
				
			});
            var hh  =window.innerHeight-150;
			
		   var table = $('#guide_table').DataTable({
						scrollY:        "600px",  //  수직 스크롤 높이
						scrollX:        true,    //  수평 스크롤 활성화
						scrollCollapse: true,   //  컨텐츠가 짧을 때 테이블 높이 축소
						paging:         false,   //  페이징 비활성화
						fixedHeader: true,     //  헤더 고정 활성화
						fixedColumns: {        //  열 고정 설정
							left: 1           //  왼쪽에서 1개의 열 고정
						},
						info:           false,  //  정보 표시줄 (하단의 "Showing 1 to..." 메시지) 숨김
						searching:      false,  //  검색창 숨김
						ordering:       false,   // 컬럼 sort기능 숨김
					});
			
		});
		var ctr=0;
        function openwin(grand_eCode,p_code,s_code,gid) { 
	       var winName = "all_"+(ctr++);
		   window.open("guide_assign_customer1.php?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&g_code="+grand_eCode+"&s_code="+s_code+"&p_code="+p_code+"&gid="+gid,winName,"width=1050,height=700,scrollbars=1");
	    }
	</script>
	</body>
</html>