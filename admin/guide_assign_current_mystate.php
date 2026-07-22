<?php
    include "include/header.php";
	
	if ($_COOKIE['MEMLOGIN_ADMIN_HELLO'] != "") {
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
		$start_date = $StartYMD." "."00:00:01";
		$stop_date = $EndYMD." "."23:59:59";	
	}
	else
	{
		$year = date("Y");
		$month = date("m");
		$day = date("d");

		$StartYMD = date("Y-m-d",mktime(0,0,0,$month,$day -4,$year));
		$EndYMD = date("Y-m-d",mktime(0,0,0,$month,$day +60,$year));

	}
	//$StartYMD = "2019-09-01";
	//$EndYMD = "2019-09-21";
	// 1주일간 뽑아내기
    $cur_day = date("w",mktime(0,0,0,$month,$day,$year));
    $now = date("Y-m-d",mktime(0,0,0,$month,$day,$year));
    $minus_day = 14 - $cur_day;
    $week_first = date("m / d / Y",mktime(0,0,0,$month,$day - $cur_day,$year));
    $week_last = date("m / d / Y",mktime(0,0,0,$month,$day + $minus_day,$year));

	
	$qry22 = "(select selected_date from 
						(select adddate('1970-01-01',t4*10000 + t3*1000 + t2*100 + t1*10 + t0) selected_date from
						 (select 0 t0 union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t0,
						 (select 0 t1 union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t1,
						 (select 0 t2 union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t2,
						 (select 0 t3 union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t3,
						 (select 0 t4 union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t4) v
					   where selected_date between '$StartYMD' and '$EndYMD')";
	$rst22 = mysqli_query($dbConn,$qry22);
    $totalDay =mysqli_num_rows($rst22);

	$total_td = 150 + (($totalDay+1) * 60);

   
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.16/js/jquery.dataTables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.16/js/dataTables.fixedHeader.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.16/js/dataTables.fixedColumns.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.16/css/dataTables.bootstrap4.min.css">
<style>
    /*div.dt-buttons {
        float: right; 
        padding-bottom: 10px;
    }*/
    
	.tableFixHead          { height: 600px;}
    .tableFixHead thead th { top: 0; background:#eee;border:0.05em solid #848484; } 
	 table.dataTable thead tr th, table.dataTable thead td,table.dataTable tbody tr td {
       
       border-bottom: 1px solid #111;
	   padding: 1px 1px;

    }
	
     div.dataTables_wrapper {
        margin: 0 auto;
    }
	.guide-tour-list {
		display: flex;
		flex-direction: column;
		gap: 3px;
		padding: 2px;
	}
	.guide-tour-item {
		display: block;
		padding: 3px 4px;
		border: 1px solid #e39a9a;
		border-radius: 4px;
		background: #fff8f8;
		color: #23527c;
		font-size: 8pt;
		line-height: 1.25;
		text-align: left;
		text-decoration: none;
		word-break: keep-all;
	}
	.guide-tour-item + .guide-tour-item {
		margin-top: 2px;
	}
	.guide-tour-no {
		display: inline-block;
		min-width: 14px;
		color: #a94442;
		font-weight: bold;
	}
	.guide-tour-count {
		display: inline-block;
		margin-left: 3px;
		color: #a94442;
		font-weight: bold;
		white-space: nowrap;
	}
	.guide-mobile-schedule {
		display: none;
	}
	.guide-mobile-card {
		margin: 0 0 12px;
		padding: 12px;
		border: 1px solid #d7dde5;
		border-radius: 8px;
		background: #fff;
		box-shadow: 0 1px 4px rgba(0,0,0,0.06);
	}
	.guide-mobile-title {
		margin: 0 0 8px;
		font-size: 15px;
		font-weight: 700;
		color: #222;
	}
	.guide-mobile-empty {
		padding: 10px;
		border-radius: 6px;
		background: #f5f7fa;
		color: #777;
		text-align: center;
	}
	.guide-mobile-tour {
		display: block;
		margin-top: 8px;
		padding: 10px;
		border: 1px solid #f0c0c0;
		border-left: 4px solid #d9534f;
		border-radius: 6px;
		background: #fff5f5;
	}
	.guide-mobile-date {
		display: block;
		margin-bottom: 4px;
		font-size: 12px;
		font-weight: 700;
		color: #a94442;
	}
	.guide-mobile-link {
		display: block;
		font-size: 14px;
		font-weight: 700;
		line-height: 1.35;
		color: #23527c;
		word-break: keep-all;
	}
	.guide-search-table input {
		width: 100%;
	}
	@media (max-width: 767px) {
		#contentwrapper.reservationDetailForm {
			padding: 0 8px;
		}
		#jCrumbs {
			display: none;
		}
		.guide-search-table,
		.guide-search-table tbody,
		.guide-search-table tr,
		.guide-search-table td {
			display: block;
			width: 100% !important;
		}
		.guide-search-table td {
			padding: 6px !important;
			border: 0 !important;
		}
		.guide-search-table .titletd {
			text-align: left !important;
			font-weight: 700;
			background: transparent;
		}
		.guide-search-table .row {
			margin-left: -4px;
			margin-right: -4px;
		}
		.guide-search-table [class*="col-"] {
			padding-left: 4px;
			padding-right: 4px;
		}
		.guide-search-table .btn {
			width: 100%;
			padding: 10px;
			font-size: 14px;
		}
		.guide-desktop-table {
			display: none;
		}
		.guide-mobile-schedule {
			display: block;
		}
	}
</style>
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
						<table class="table table-bordered table-condensed guide-search-table">
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
								
							</tr>
									
							<tr>
								<td colspan="4" class="text-center"><button type='submit' class="btn btn-primary btn-sm btn1">검색(반드시 2개월 이상 조회하세요!!!)</button></td>
							</tr>
						</table>
					</form>
					<br />
					<div class="row guide-desktop-table" style="overflow:scroll;">
						<div class="col-sm-12 tableFixHead" >
						    <table id="guide_table" class="table table-striped table-bordered text-center ">
                                <thead>
                                    <tr>
                                        
                                        <?php
												
												$sDate = $StartYMD;
												echo "<th style='position: sticky;margin:0;border:0.05em solid #848484;'  align=center>가 이 드</th>";
												for($i=0; $i<$totalDay; $i++)
												{

													$sDate2 = explode("-",$sDate);

													$pdate  = date("Y-m-d",mktime (0,0,0,$sDate2[1]  , $sDate2[2]+$i, $sDate2[0]));

													$month  = date("m",mktime (0,0,0,$sDate2[1]  , $sDate2[2]+$i, $sDate2[0]));	
													$day  = date("d",mktime (0,0,0,$sDate2[1]  , $sDate2[2]+$i, $sDate2[0]));	

													$yoil = date("w", mktime (0,0,0,$sDate2[1]  , $sDate2[2]+$i, $sDate2[0]));
													
													$week = array("일", "월", "화", "수", "목", "금", "토");

													$yoil = $week[date("w", mktime (0,0,0,$sDate2[1]  , $sDate2[2]+$i, $sDate2[0]))];


													if($yoil == "일")
													{
														$day = "<font style='font-size:7pt;color:red'>$month/$day<br>($yoil)</font>";
													} else if($yoil == "토")
													{
														$day = "<font style='font-size:7pt;color:blue'>$month/$day<br>($yoil)</font>";
													}
													else
													{
														$day = "<font style='font-size:7pt'>$month/$day<br>($yoil)</font>";
													}
													if ($pdate == $now) {
														echo "<th style='border:0.05em solid #848484;background-color:#DDA0DD;' align=center>$day</th>";

													} else {
													    echo "<th style='border:0.05em solid #848484;' align=center>$day</th>";
													}
												}


											?>
											
                                    </tr>
                                </thead>
                                <tbody>
 <?php

		$vDate = $StartYMD;
		if ($user_dbinfo['userid'] != "") {
		    $guideqry = " && a.guide_id = '{$user_dbinfo['userid']}'";
		}

      $zip_qry1 = "select a.*,b.kor_name from tour_guide a, member_list b ,tour_car c 
											where a.guide_id=b.userid && division='guide'
											&& a.p_code=c.p_code && a.stDate=c.stDate && a.grand_eCode =c.grand_eCode && a.sub_eCode = c.sub_eCode	&& a.stDate between '$StartYMD' and '$EndYMD'
											&& exists (
												select 1 from reserve_info r
												where r.reserveCode = c.reserveCode
												&& r.p_code = c.p_code
												&& r.stDate = c.stDate
												&& ifnull(r.rev_status,'') not in ('CANCEL','CANCE','CANEL')
											)
											$guideqry
											group by a.guide_id";
		
		/*$zip_qry1 = "select a.*,b.kor_name from tour_guide a, member_list b 
											where a.guide_id=b.userid && division='guide' 
											&& a.stDate between '2019-09-01' and '2019-09-20' 
											group by a.guide_id";*/
		//echo $totalDay ;
	    //echo $zip_qry1;
		$zip_rst1 = mysqli_query($dbConn,$zip_qry1);
		$mobileCards = "";

	// --- Helper Function (Highly Recommended) ---
	function getTourCellContent($row, $guideId) {
		if (!$row) {
			return "<td style='border:1px dotted black;' align=center'>&nbsp;<br /></td>";
		}

		$pCode = $row['p_code'];
		$productInfo = getProductMaster($pCode);
		$addDate = $productInfo['p_day'] ?? 1;  // Use null coalescing operator, default to 1

		$link = sprintf(
			"<a class=\"guide-tour-item\" href=\"javascript:openwin('%s','%s','%s','%s','%s')\">%s</a>",
			htmlspecialchars($row['grand_eCode']),
			htmlspecialchars($row['p_code']),
			htmlspecialchars($row['sub_eCode']),
			htmlspecialchars($guideId),
			htmlspecialchars($row['stDate']),
			htmlspecialchars($row['p_name']) //use p_name from the query result
		);

		return "<td colspan='{$addDate}' style='border:1px dotted black;' align=center bgcolor=#FFFF99'>{$link}</td>";
	}

	function getGuideTourPassengerCount($dbConn, $grand_eCode, $p_code, $sub_eCode, $stDate) {
		$stmt = mysqli_prepare($dbConn, "SELECT IFNULL(SUM(x.p_cnt),0) AS total_pcnt FROM (SELECT r.reserveCode, MAX(r.p_cnt + 0) AS p_cnt FROM tour_car c JOIN reserve_info r ON r.reserveCode = c.reserveCode AND r.p_code = c.p_code AND r.stDate = c.stDate WHERE c.grand_eCode = ? AND c.p_code = ? AND c.sub_eCode = ? AND c.stDate = ? AND IFNULL(r.rev_status,'') NOT IN ('CANCEL','CANCE','CANEL') GROUP BY r.reserveCode) x");
		if ($stmt === false) {
			error_log("Passenger count prepare failed: " . mysqli_error($dbConn));
			return 0;
		}
		mysqli_stmt_bind_param($stmt, "ssss", $grand_eCode, $p_code, $sub_eCode, $stDate);
		mysqli_stmt_execute($stmt);
		$result = mysqli_stmt_get_result($stmt);
		$row = $result ? mysqli_fetch_assoc($result) : null;
		mysqli_stmt_close($stmt);
		return $row ? (int)$row['total_pcnt'] : 0;
	}


	// --- Main Logic ---

while ($zip_row1 = mysqli_fetch_assoc($zip_rst1)) {
    $guideId = $zip_row1['guide_id'];
    $guideName = htmlspecialchars($zip_row1['kor_name']);
    $content = "";
    $mobileItems = "";
    $activeTours = []; // KEY: Keeps track of ALL active tours

    for ($k = 0; $k < $totalDay; $k++) { // Loop through EACH day
        $vDate2 = explode("-", $vDate);
        $choose_date = date("Y-m-d", mktime(0, 0, 0, $vDate2[1], $vDate2[2] + $k, $vDate2[0]));

        // 1. REMOVE COMPLETED TOURS (Essential for different start dates)
        foreach ($activeTours as $tourId => $endDate) {
            if ($choose_date > $endDate) {
                unset($activeTours[$tourId]); // Remove if the tour has ended
            }
        }

        // 2. FETCH NEW TOURS *STARTING* ON THE CURRENT DATE
        $stmt = mysqli_prepare($dbConn, "SELECT a.*, p.p_name FROM tour_guide a LEFT JOIN product_master p ON a.p_code = p.p_code WHERE a.stDate = ? AND a.guide_id = ? AND EXISTS (SELECT 1 FROM tour_car c JOIN reserve_info r ON r.reserveCode = c.reserveCode AND r.p_code = c.p_code AND r.stDate = c.stDate WHERE c.p_code = a.p_code AND c.stDate = a.stDate AND c.grand_eCode = a.grand_eCode AND c.sub_eCode = a.sub_eCode AND IFNULL(r.rev_status,'') NOT IN ('CANCEL','CANCE','CANEL')) ORDER BY a.stDate, a.bus_num ASC");
        if ($stmt === false) {
            $content .= "<td style='border:1px dotted black;' align=center'>Error: Could not prepare query.</td>";
            error_log("Prepare failed: " . mysqli_error($dbConn));
            continue;
        }
        mysqli_stmt_bind_param($stmt, "ss", $choose_date, $guideId);
        mysqli_stmt_execute($stmt);
        $rst1 = mysqli_stmt_get_result($stmt);
        $newTours = [];
        while ($row1 = mysqli_fetch_assoc($rst1)) {
            $newTours[] = $row1;
        }
        mysqli_stmt_close($stmt);

        // 3. ADD NEW TOURS TO $activeTours (With their END DATES)
        foreach ($newTours as $tour) {
            $productInfo = getProductMaster($tour['p_code']);
            $tourDuration = $productInfo['p_day'] ?? 1;
            // Calculate the END DATE of the tour
            $endDate = date('Y-m-d', strtotime($choose_date . " + " . ($tourDuration - 1) . " days"));
            $tourId = $tour['grand_eCode'] . "_" . $tour['p_code'] . "_" . $tour['sub_eCode'] . "_" . $tour['stDate'];
            $activeTours[$tourId] = $endDate; // Store: [TourID => EndDate]

            $dateText = $choose_date;
            if ($endDate != $choose_date) {
                $dateText .= " ~ " . $endDate;
            }
			$tourPcnt = getGuideTourPassengerCount($dbConn, $tour['grand_eCode'], $tour['p_code'], $tour['sub_eCode'], $tour['stDate']);
            $mobileItems .= sprintf(
                "<div class=\"guide-mobile-tour\"><span class=\"guide-mobile-date\">%s</span><a class=\"guide-mobile-link\" href=\"javascript:openwin('%s','%s','%s','%s','%s')\">%s <span class=\"guide-tour-count\">(%s명)</span></a></div>",
                htmlspecialchars($dateText),
                htmlspecialchars($tour['grand_eCode']),
                htmlspecialchars($tour['p_code']),
                htmlspecialchars($tour['sub_eCode']),
                htmlspecialchars($guideId),
                htmlspecialchars($tour['stDate']),
                htmlspecialchars($tour['p_name']),
				$tourPcnt
            );
        }

        // 4. DISPLAY *ALL* ACTIVE TOURS (Handles different start dates)
        if (!empty($activeTours)) {
            $maxEndDate = max($activeTours); // Find the *latest* end date
            $colspan = (strtotime($maxEndDate) - strtotime($choose_date)) / (60 * 60 * 24) + 1;

            $tourLinks = "";
			$tourNo = 1;
            // Iterate through ALL active tours, regardless of start date
            foreach ($activeTours as $tourId => $endDate) {
                // Re-fetch tour details (optimization possible, but this is clearer)
                list($grand_eCode, $p_code, $sub_eCode, $stDate) = explode("_", $tourId);
                $stmt = mysqli_prepare($dbConn, "SELECT a.*, p.p_name FROM tour_guide a LEFT JOIN product_master p ON a.p_code = p.p_code WHERE a.grand_eCode = ? AND a.p_code = ? AND a.sub_eCode = ? AND a.stDate=? AND EXISTS (SELECT 1 FROM tour_car c JOIN reserve_info r ON r.reserveCode = c.reserveCode AND r.p_code = c.p_code AND r.stDate = c.stDate WHERE c.p_code = a.p_code AND c.stDate = a.stDate AND c.grand_eCode = a.grand_eCode AND c.sub_eCode = a.sub_eCode AND IFNULL(r.rev_status,'') NOT IN ('CANCEL','CANCE','CANEL'))");
                // ... (Error handling for inner $stmt) ...
                mysqli_stmt_bind_param($stmt, "ssss", $grand_eCode, $p_code, $sub_eCode, $stDate);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                if ($tour = mysqli_fetch_assoc($result)) {
					$tourPcnt = getGuideTourPassengerCount($dbConn, $tour['grand_eCode'], $tour['p_code'], $tour['sub_eCode'], $stDate);
					$tourLinks .= sprintf(
                        "<a class=\"guide-tour-item\" href=\"javascript:openwin('%s','%s','%s','%s','%s')\"><span class=\"guide-tour-no\">%s.</span>%s <span class=\"guide-tour-count\">(%s명)</span></a>",
                        htmlspecialchars($tour['grand_eCode']),
                        htmlspecialchars($tour['p_code']),
                        htmlspecialchars($tour['sub_eCode']),
                        htmlspecialchars($guideId),
                        htmlspecialchars($stDate),
                        $tourNo,
                        htmlspecialchars($tour['p_name']),
						$tourPcnt
                    );
					$tourNo++;
                }
                mysqli_stmt_close($stmt); // Close the inner prepared statement
            }


            $content .= "<td colspan='{$colspan}' style='border:1px dotted black; background-color: #ffcccc; vertical-align:top;' align=center'>";
            $content .= "";
            $content .= "<div class='guide-tour-list'>{$tourLinks}</div>"; // This contains links to ALL active tours
            $content .= "</td>";

            $k += ($colspan - 1); // Advance $k to avoid reprocessing

        } else {
           $content .= "<td style='border:1px dotted black;' align=center'>&nbsp;<br /></td>";
        }
    }

    echo "<tr>
            <td align=left style='border:0.05em solid #848484;'><font style='font-size:8pt'>&nbsp;{$guideName}({$guideId})</font></td>
            {$content}
          </tr>";
    if ($mobileItems == "") {
        $mobileItems = "<div class=\"guide-mobile-empty\">조회 기간 내 배정 상품이 없습니다.</div>";
    }
    $mobileCards .= "<div class=\"guide-mobile-card\"><div class=\"guide-mobile-title\">{$guideName}({$guideId})</div>{$mobileItems}</div>";
    unset($content);
}

?>
		


								</tbody>
                
                            </table>
                            
						</div>
					</div>
					<div class="guide-mobile-schedule">
						<?= $mobileCards ?>
					</div>
				</div><!-- -->
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
			
            //var args = {paging:false, ordering:false, info:false,scrollX:true,scrollY: 200};
            /* var table = $('#guide_table').DataTable({
                    scrollY:        hh+"px",
					scrollX:        true,
					scrollCollapse: true,
					paging:         false,
                    bSort : false,
					fixedColumns:   {
						leftColumns: 1
					
					}
			  
			   
			 }); */
			 var table = $('#guide_table').DataTable({
                    scrollY:        600,
					scrollX:        true,
					scrollCollapse: true,
					paging:         false,
					fixedHeader: true,
					fixedColumns: {
					  leftColumns: 1
					},
					sort : false,
					autoWidth: false,
					columnDefs: [
						{ width: 150, targets: 0 }
					],
					
					fixedColumns: true
			   
			 });
			
		});
		var ctr=0;
        function openwin(grand_eCode,p_code,s_code,gid,st) { 
	       var winName = "all_"+(ctr++);
		   window.open("guide_assign_customer1.php?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&g_code="+grand_eCode+"&s_code="+s_code+"&p_code="+p_code+"&gid="+gid+"&st="+st,winName,"width=1050,height=700,scrollbars=1");
	    }
	</script>
    </body>
</html>
