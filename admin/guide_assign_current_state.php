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

    .tableFixHead {
        overflow: auto; /*  x, y축 모두 auto로 설정 */
        height: 600px;
    }

    .tableFixHead thead th {
        position: sticky;
        top: 0;
        background: #eee;
        border: 0.05em solid #848484;
        z-index: 1; /* 헤더 z-index */
    }
     /* 가이드 컬럼 고정 */
    .tableFixHead tbody td:first-child,
    .tableFixHead thead th:first-child
     {
        position: sticky;
        left: 0;
        background-color: #f0f0f0;
        z-index: 2; /* 가이드 컬럼 z-index (헤더보다 높게) */
    }
     /* 헤더의 첫 번째 셀 (가이드) */
     .tableFixHead thead th:first-child{
        z-index:3; /* 최상위 z-index */
     }

    table.dataTable thead tr th,
    table.dataTable thead td,
    table.dataTable tbody tr td {
        border-bottom: 1px solid #111;
        padding: 1px 1px;
        box-sizing: border-box; /* padding, border가 width/height에 포함되도록 */
    }
    /* DataTables 1.13+ 에서는 아래 설정이 필요할 수 있습니다. */
    table.dataTable.fixedHeader-floating,
    table.dataTable.fixedHeader-locked {
      z-index: 99999 !important;
    }


    div.dataTables_wrapper {
        margin: 0 auto;
    }
    /* 차트 툴팁 - 가이드의 날짜별 행사 상세 */
    #guideChartTip {
        position: absolute;
        display: none;
        max-width: 560px;
        padding: 8px 11px;
        background: #111111;
        color: #ffffff;
        border-radius: 4px;
        font-size: 11px;
        line-height: 1.55;
        z-index: 100000;
        box-shadow: 0 2px 12px rgba(0,0,0,0.45);
        pointer-events: none;
    }
    #guideChartTip .gc-tip-h {
        font-weight: 700;
        margin-bottom: 5px;
        padding-bottom: 4px;
        border-bottom: 1px solid #444444;
    }
    #guideChartTip .gc-tip-d {
        color: #6db3f2;
        font-weight: 700;
        margin-right: 3px;
    }
    #guideChartTip .gc-hi {
        color: #ffb02e;
        font-weight: 700;
    }

    /* 차트 범례 - 상품명은 진하게 */
    #chartLegend table { width: auto; }
    #chartLegend .legendLabel {
        color: #0b0b0b;
        font-weight: 700;
        padding: 0 12px 0 4px;
        white-space: nowrap;
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
    /* 필요하다면 아래처럼 최소/최대 너비를 설정해 줄 수 있습니다.
    td, th {
      min-width: 50px;  가장 좁은 셀의 너비
      max-width: 200px;  넓은 셀의 최대 너비
    }
    */
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
								<?php if ($user_dbinfo['division'] != "guide") { ?>
								<td width="10%" class="titletd text-center">가이드</td>
								<td width="40%" class="no-right-border">
									<select class="form-control" name="guideSelect">
										<option value="" selected>- 가이드선택 -</option>
										<?=printGuideSelect($guideSelect)?>
									</select>
								</td>
								<?php } else  {?>
								<td width="10%" class="titletd text-center">가이드</td>
								<td width="40%" class="no-right-border">
									<?=$user_dbinfo['kor_name']?>
									<input type="hidden" id="guideSelect" name="guideSelect"  value="<?=$user_dbinfo['userid']?>"/>
								</td>
								<?php } ?>
							</tr>
									
							<tr>
								<td colspan="4" class="text-center"><button type='submit' class="btn btn-primary btn-sm btn1">검색(반드시 2개월 이상 조회하세요!!!)</button></td>
							</tr>
						</table>
					</form>
					<br />
					<div class="row">
						<div class="col-sm-12">
							<div class="panel panel-default">
								<div class="panel-heading" style="padding:6px 10px;">
									<b>가이드별 · 상품별 배정 건수</b>
									<small class="text-muted">&nbsp;Assignments by guide &amp; product (<?=$StartYMD?> ~ <?=$EndYMD?>)</small>
									<a href="javascript:void(0)" id="chartToggle" class="pull-right">접기/펴기</a>
								</div>
								<div class="panel-body" id="chartBody" style="padding:8px;">
									<div id="chartLegend" style="font-size:11px;margin-bottom:6px;"></div>
									<div id="guideChart" style="width:100%;"></div>
								</div>
							</div>
						</div>
					</div>
					<div class="row" style="overflow:scroll;">
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
		if ($guideSelect != "") {
		    $guideqry = " && a.guide_id = '$guideSelect'";
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

		// --- Helper Function (Highly Recommended) ---
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

	$chartData = array();   // guide_id => ['name'=>, 'tours'=> tourId => pcnt]

	// 컬럼 인덱스 <-> 날짜 매핑 (헤더와 동일한 순서)
	$vDate2  = explode("-", $vDate);
	$dayList = array();
	for ($k = 0; $k < $totalDay; $k++) {
		$dayList[$k] = date("Y-m-d", mktime(0, 0, 0, $vDate2[1], $vDate2[2] + $k, $vDate2[0]));
	}
	$dayIdx = array_flip($dayList);

	$tourQry = "SELECT a.*, p.p_name, IFNULL(p.p_day,1) AS p_day
				FROM tour_guide a
				LEFT JOIN product_master p ON a.p_code = p.p_code
				WHERE a.guide_id = ? AND a.stDate BETWEEN ? AND ?
				AND EXISTS (
					SELECT 1 FROM tour_car c
					JOIN reserve_info r ON r.reserveCode = c.reserveCode AND r.p_code = c.p_code AND r.stDate = c.stDate
					WHERE c.p_code = a.p_code AND c.stDate = a.stDate
					AND c.grand_eCode = a.grand_eCode AND c.sub_eCode = a.sub_eCode
					AND IFNULL(r.rev_status,'') NOT IN ('CANCEL','CANCE','CANEL')
				)
				ORDER BY a.stDate, a.bus_num ASC";

	while ($zip_row1 = mysqli_fetch_assoc($zip_rst1)) {
    $guideId = $zip_row1['guide_id'];
    $guideName = htmlspecialchars($zip_row1['kor_name']);
    $content = "";

    // 기간 내 이 가이드의 배정을 한 번에 조회한다.
    // (예전에는 날짜마다 조회하면서 다중일 행사의 colspan 만큼 날짜를 건너뛰어,
    //  그 구간 안에서 시작하는 행사가 조회조차 되지 않고 사라졌다.)
    $tours    = array();   // tourId => 행 정보
    $dayTours = array();   // 컬럼 인덱스 => tourId 목록

    $stmt = mysqli_prepare($dbConn, $tourQry);
    if ($stmt === false) {
        error_log("Prepare failed: " . mysqli_error($dbConn));
    } else {
        mysqli_stmt_bind_param($stmt, "sss", $guideId, $StartYMD, $EndYMD);
        mysqli_stmt_execute($stmt);
        $rst1 = mysqli_stmt_get_result($stmt);
        while ($row1 = mysqli_fetch_assoc($rst1)) {
            $stDate = substr($row1['stDate'], 0, 10);
            $tourId = $row1['grand_eCode'] . "_" . $row1['p_code'] . "_" . $row1['sub_eCode'] . "_" . $stDate;
            if (isset($tours[$tourId])) { continue; }   // 같은 행사에 버스가 여러 대면 1건으로
            if (!isset($dayIdx[$stDate])) { continue; } // 조회 범위 밖 방어

            $tourPcnt = getGuideTourPassengerCount($dbConn, $row1['grand_eCode'], $row1['p_code'], $row1['sub_eCode'], $stDate);
            $row1['stDate']   = $stDate;
            $row1['tourPcnt'] = $tourPcnt;
            $tours[$tourId]   = $row1;

            $chartData[$guideId]['name'] = $zip_row1['kor_name'];
            $chartData[$guideId]['tours'][$tourId] = array(
                'd'   => $stDate,
                'p'   => ($row1['p_name'] === null || $row1['p_name'] === '') ? '(상품없음)' : $row1['p_name'],
                'pax' => $tourPcnt
            );

            // 행사 기간(p_day)만큼 해당 컬럼들에 배치
            $start = $dayIdx[$stDate];
            $last  = min($start + max(1, (int)$row1['p_day']) - 1, $totalDay - 1);
            for ($d = $start; $d <= $last; $d++) {
                $dayTours[$d][] = $tourId;
            }
        }
        mysqli_stmt_close($stmt);
    }

    for ($k = 0; $k < $totalDay; $k++) {
        if (empty($dayTours[$k])) {
            $content .= "<td style='border:1px dotted black;' align=center'>&nbsp;<br /></td>"; // Empty cell
            continue;
        }

        // 행사 조합이 동일한 날짜가 이어지는 동안만 셀을 합친다
        $colspan = 1;
        while ($k + $colspan < $totalDay
            && isset($dayTours[$k + $colspan])
            && $dayTours[$k + $colspan] === $dayTours[$k]) {
            $colspan++;
        }

        $tourLinks = "";
        $tourNo = 1;
        foreach ($dayTours[$k] as $tourId) {
            $tour = $tours[$tourId];
            $tourLinks .= sprintf(
                "<a class=\"guide-tour-item\" href=\"javascript:openwin('%s','%s','%s','%s','%s')\"><span class=\"guide-tour-no\">%s.</span>%s <span class=\"guide-tour-count\">(%s명)</span></a>",
                htmlspecialchars($tour['grand_eCode']),
                htmlspecialchars($tour['p_code']),
                htmlspecialchars($tour['sub_eCode']),
                htmlspecialchars($guideId),
                htmlspecialchars($tour['stDate']),
                $tourNo,
                htmlspecialchars((string)$tour['p_name']),
                $tour['tourPcnt']
            );
            $tourNo++;
        }

        $content .= "<td colspan='{$colspan}' style='border:1px dotted black; background-color: #ffcccc; vertical-align:top;' align=center'>";
        $content .= "<div class='guide-tour-list'>{$tourLinks}</div>";
        $content .= "</td>";

        $k += ($colspan - 1);
    }

    echo "<tr>
            <td align=left style='border:0.05em solid #848484;'><font style='font-size:8pt'>&nbsp;{$guideName}({$guideId})</font></td>
            {$content}
          </tr>";
    unset($content);
}

// --- 차트용 집계 (아래 <script> 에서 사용) ---
// 상품은 전부 개별 색으로 표시한다 (건수 많은 순으로 색 슬롯 배정).
$prodTotal = array();
foreach ($chartData as $g) {
	foreach ($g['tours'] as $t) {
		$prodTotal[$t['p']] = (isset($prodTotal[$t['p']]) ? $prodTotal[$t['p']] : 0) + 1;
	}
}
arsort($prodTotal);
$chartProds = array_keys($prodTotal);

$chartRows = array();
foreach ($chartData as $gid => $g) {
	$byProd = array();
	$items  = array();
	$pax    = 0;
	foreach ($g['tours'] as $t) {
		$byProd[$t['p']] = (isset($byProd[$t['p']]) ? $byProd[$t['p']] : 0) + 1;
		$pax += $t['pax'];
		$items[] = array('d' => $t['d'], 'p' => $t['p'], 'x' => $t['pax']);   // 툴팁 상세용
	}
	usort($items, function($a, $b) { return strcmp($a['d'], $b['d']); });
	$chartRows[] = array(
		'label' => $g['name']."(".$gid.")",
		'cnt'   => count($g['tours']),
		'pax'   => $pax,
		'prod'  => $byProd,
		'items' => $items
	);
}
usort($chartRows, function($a, $b) { return $b['cnt'] - $a['cnt']; });

?>
		


								</tbody>
                
                            </table>
                            
						</div>
					</div>
				</div><!-- -->
			</div>                
		</div>

	</div>
    <?php
		include "include/side_m.php"
	?>
    <script src="lib/flot/jquery.flot.stack.min.js"></script>
    <script>
		// --- 가이드별 x 상품별 배정 건수 차트 (Flot, header.php 에서 이미 로드됨) ---
		// DataTable 초기화에서 예외가 나도 차트는 그려지도록 별도 ready 블록에서 먼저 실행한다.
		$(document).ready(function () {
			var chartRows  = <?=json_encode($chartRows, JSON_UNESCAPED_UNICODE)?>;
			var chartProds = <?=json_encode($chartProds, JSON_UNESCAPED_UNICODE)?>;
			var $box = $('#guideChart');
			if (!chartRows || !chartRows.length) {
				$box.html('<div class="text-center text-muted" style="padding:20px;">조회된 배정 내역이 없습니다.</div>');
				return;
			}

			// 상품 8종 이하면 검증된 고정 팔레트, 그 이상이면 색상환을 균등 분할해 진한 색으로 생성한다.
			// Flot 의 $.color.parse() 는 hsl() 문자열을 못 읽으므로 반드시 hex 로 넘긴다.
			var palette8 = ['#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4', '#008300', '#4a3aa7', '#e34948'];
			var hslHex = function (h, s, l) {
				var a = s * Math.min(l, 1 - l);
				var f = function (k0) {
					var k = (k0 + h / 30) % 12;
					var c = l - a * Math.max(-1, Math.min(k - 3, 9 - k, 1));
					return ('0' + Math.round(255 * c).toString(16)).slice(-2);
				};
				return '#' + f(0) + f(8) + f(4);
			};
			var n = chartProds.length;
			var colorOf = function (i) {
				if (n <= palette8.length) { return palette8[i]; }
				// 인접 상품끼리 색상각이 좁아지므로 명도를 번갈아 줘서 경계를 살린다
				return hslHex((i * 360 / n + 205) % 360, 0.62, (i % 2 ? 0.34 : 0.47));
			};

			var ticks = $.map(chartRows, function (r, i) {
				return [[chartRows.length - 1 - i, r.label]];   // 건수 많은 가이드가 위로
			});
			var series = $.map(chartProds, function (pn, si) {
				return {
					label: pn,
					color: colorOf(si),
					data: $.map(chartRows, function (r, i) {
						return [[r.prod[pn] || 0, chartRows.length - 1 - i]];
					})
				};
			});

			$box.height(Math.max(140, chartRows.length * 30 + 40));
			$.plot($box, series, {
				series: {
					stack: true,
					bars: { show: true, horizontal: true, barWidth: 0.6, align: 'center', lineWidth: 0, fill: 1 }
				},
				xaxis: { min: 0, tickDecimals: 0, font: { size: 11, color: '#898781' } },
				yaxis: { ticks: ticks, font: { size: 11, color: '#52514e' } },
				grid:  { hoverable: true, borderWidth: 0, color: '#e1e0d9' },
				legend: { container: $('#chartLegend'), noColumns: 5 }
			});

			var $tip = $('<div id="guideChartTip"></div>').appendTo('body');
			var esc = function (s) { return $('<div></div>').text(s == null ? '' : s).html(); };

			// 가이드의 날짜별 행사 목록. hover 한 상품은 강조한다.
			var buildTip = function (r, pn) {
				var byDate = {}, order = [];
				$.each(r.items, function (i, it) {
					if (!byDate[it.d]) { byDate[it.d] = []; order.push(it.d); }
					byDate[it.d].push(it);
				});
				var html = '<div class="gc-tip-h">' + esc(r.label) + ' ' + (r.prod[pn] || 0) + '건'
					+ ' &middot; 전체 ' + r.cnt + '건 / ' + r.pax + '명</div>';
				$.each(order, function (i, d) {
					var parts = $.map(byDate[d], function (it) {
						var txt = esc(it.p) + ' (' + it.x + '명)';
						return it.p === pn ? '<span class="gc-hi">' + txt + '</span>' : txt;
					});
					html += '<div><span class="gc-tip-d">' + esc(d.substr(5).replace('-', '/')) + '</span>'
						+ parts.join(' / ') + '</div>';
				});
				return html;
			};

			$box.on('plothover', function (e, pos, item) {
				if (!item) { $tip.hide(); return; }
				var r = chartRows[item.dataIndex];
				$tip.html(buildTip(r, chartProds[item.seriesIndex])).show();

				// 화면 밖으로 나가지 않도록 위치 보정
				var $w = $(window);
				var left = item.pageX + 14;
				var top  = item.pageY - 20;
				if (left + $tip.outerWidth() > $w.scrollLeft() + $w.width() - 8) {
					left = Math.max($w.scrollLeft() + 8, item.pageX - $tip.outerWidth() - 14);
				}
				top = Math.min(top, $w.scrollTop() + $w.height() - $tip.outerHeight() - 8);
				top = Math.max(top, $w.scrollTop() + 4);
				$tip.css({ top: top, left: left });
			});
			$box.on('mouseleave', function () { $tip.hide(); });

			$('#chartToggle').on('click', function () { $('#chartBody').slideToggle(); });
		});

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
					fixedColumns: {
					  leftColumns: 1
					},
					sort : false,
					autoWidth: false,
					columnDefs: [
						{ width: 150, targets: 0 }
					],
					fixedHeader: {
						header: true
					},
					
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
