<?php
    include "./include/header.php";
	
	if ($_COOKIE['MEMLOGIN_ADMIN_HELLO'] != "") {
		if ($user_dbinfo['division'] == "guide") {
			echo "<meta http-equiv='refresh' content='0; url=./memo_list.php'>";
			
			exit;
		}
	} else {
		
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		
		exit;
	}


	$mm = date("m");
	$ym = date("Y-m");
	$ymd = date("Y-m-d");
	$qry0 = "select sum(last_total) scnt from reserve_info where revDate = '$ymd' && parent='MAIN' && base_rate='USD' && rev_status !='CANCEL'";
    $rst0 = $dbConn->query($qry0);
	$row0 = $rst0->fetch_assoc();

	$qry1 = "select sum(last_total) scnt from reserve_info where substr(revDate,1,7) ='$ym' && parent='MAIN' && base_rate='USD' && rev_status !='CANCEL' ";
    $rst1 = $dbConn->query($qry1);
	$row1 = $rst1->fetch_assoc();
	
	$qry00 = "select sum(p_cnt) cnt from reserve_info where revDate = '$ymd' && parent='MAIN' && rev_status !='CANCEL' ";
    $rst00 = $dbConn->query($qry00);
	$row00 = $rst00->fetch_assoc();

	$qry11 = "select count(*) cnt from reserve_info where substr(revDate,1,7) ='$ym'&& parent='MAIN' && rev_status !='CANCEL'";;
    $rst11 = $dbConn->query($qry11);
	$row11 = $rst11->fetch_assoc();


	$qry2 = "select count(*) cnt from reserve_info where substr(revDate,1,7) ='$ym'&& parent='MAIN' && rev_status !='CANCEL'";
    $rst2 = $dbConn->query($qry2);
	$row2 = $rst2->fetch_assoc();

	

	$qry5 = "select count(*) cnt from reserve_info where YEARWEEK(revDate) = YEARWEEK(now()) && parent='MAIN' && rev_status !='CANCEL' ";
    $rst5 = $dbConn->query($qry5);
	$row5 = $rst5->fetch_assoc();

	$qry6 = "select sum(p_cnt) cnt from reserve_info where substr(revDate,1,7) ='$ym' && parent='MAIN' && rev_status !='CANCEL' ";
    $rst6 = $dbConn->query($qry6);
	$row6 = $rst6->fetch_assoc();

	$qry7 = "select sum(p_cnt) cnt from reserve_info where YEARWEEK(revDate) = YEARWEEK(now()) && parent='MAIN' && rev_status !='CANCEL' ";
    $rst7 = $dbConn->query($qry7);
	$row7 = $rst7->fetch_assoc();
	
	function printBoard($table_id){
		
		global $dbConn,$division,$pdx,$sub;

		$qry1 = "select * from dongbu_board where tablename = '$table_id' order by seq_no desc limit 10";
		$rst1 = $dbConn->query($qry1);


		$num1 = 0;

		while($row1 =$rst1->fetch_assoc()){
			if ($table_id == '25') {
				$url ='board_view.php?division=8&pdx=1&sub=25&table_id='.$table_id.'&';
			}
			if ($table_id == '15') {
				$url ='board_view.php?division=8&pdx=1&sub=15&table_id='.$table_id.'&';
			}
			if ($table_id == '10') {
				$url ='board_view.php?division=8&pdx=1&sub=10&table_id='.$table_id.'&';
			} 
			if ($table_id == '01') {
				$url ='board_view.php?division=8&pdx=1&sub=10&table_id='.$table_id.'&';
			}
			if ($table_id == '35') {
				$url ='board_view.php?division=8&pdx=1&sub=35&table_id='.$table_id.'&';;
			}
			$today = explode(" ",$row1[wdate]);
			$today2 = explode("-",$today[0]);

			$yesterday1 = date("Y-m-d H:i:s",time()-86400);
			if($row1[wdate] > $yesterday1)
			{
			$new_icon = "<img src='img/New2.gif'>";
			}
			else
			{
			$new_icon = "&nbsp;";
			}

			$title = Misc::cutLongString($row1[title], 16, $dot=true);

			$content .= "<table class='table table-borderless index_table_fixed'>
					<tr bgcolor=#FFFFFF>
						<td  height=22 width=80%><a href=".$url."no=$row1[seq_no]&start=0&board_mode=view>$title</a> $new_icon</td>
						<td align=right width=20%><span class=stxt>$today2[1].$today2[2]</span></td>
					</tr>
					</table>
					
			";

			$num1++;

		}

		echo $content;
	}

	/* 임박 행사 — 오늘 ~ +30일 출발분. 모객률(예약/정원) + 배차 상태.
	   정원은 tour_master.tour_pcnt 우선, 없으면 product_master.p_cnt (base_reservation_m.php와 동일 기준)
	   배차 판정 기준은 assign_list.php와 동일: car_cnt=tour_car 행수(배정인원), bus_cnt=버스대수, guide_cnt=가이드 배정행수 */
	function getSoonEvents($limit = 12) {
		global $dbConn;

		$from = date("Y-m-d");
		$to   = date("Y-m-d", strtotime("+30 day"));

		$qry = "
		select
			c.grand_eCode, c.p_code, c.stDate, c.ev_status, b.p_name,
			if(ifnull(c.tour_pcnt,0) > 0, c.tour_pcnt, b.p_cnt) as cap,
			(select sum(r.p_cnt) from reserve_info r
				where r.p_code=c.p_code and r.stDate=c.stDate and r.rev_status!='CANCEL') as pax,
			(select sum(r.p_cnt) from reserve_info r
				where r.p_code=c.p_code and r.stDate=c.stDate and r.rev_status='WAIT')    as wait_pax,
			(select count(*) from tour_car tc
				where tc.p_code=c.p_code and tc.stDate=c.stDate)                          as car_cnt,
			(select count(distinct tc.bus_num) from tour_car tc
				where tc.p_code=c.p_code and tc.stDate=c.stDate)                          as bus_cnt,
			(select count(*) from tour_guide tg
				where tg.p_code=c.p_code and tg.stDate=c.stDate)                          as guide_cnt
		from tour_master c
		join product_master b on b.p_code = c.p_code
		where b.p_type in ('1','2','4')
		  and b.m_type = 'S'
		  and c.ev_status != '4'
		  and c.stDate between '$from' and '$to'
		having pax > 0
		order by c.stDate asc
		limit $limit";

		$rst  = $dbConn->query($qry);
		$rows = array();
		while ($rst && $row = $rst->fetch_assoc()) $rows[] = $row;
		return $rows;
	}

	/* 종료 행사 정산현황 — 최근 N일 내 종료된 행사(서브코드 단위, hotel_settle.php/guide_settle.php와 동일 단위)
	   종료일 = stDate + p_day - 1 (getPeriodbyrev와 동일)
	   호텔·차량 : *_settlesum 행 존재 = 완료 (등록 즉시 완료. getHotelStStatus/getCarStStatus와 동일 기준)
	   가이드   : guide_setmaster 확정본 1건(guideMasterPickOrderExpr) 기준 — getGuideStatus와 동일 판정 */
	function getEndedEvents($days = 30) {
		global $dbConn;

		$from = date("Y-m-d", strtotime("-$days day"));
		$to   = date("Y-m-d", strtotime("-1 day"));
		$scan = date("Y-m-d", strtotime("-".($days + 60)." day"));  // stDate 선필터 (60일 이상 장기상품은 누락)

		$qry = "
		select
			tg.grand_eCode, tg.sub_eCode, min(tg.stDate) as stDate, min(tg.p_name) as p_name, min(tg.seq_no) as seq,
			date_add(min(tg.stDate), interval greatest(ifnull(max(pm.p_day),1),1)-1 day) as edDate,
			group_concat(distinct ml.kor_name) as guide_names,
			(select count(*) from hotel_settlesum h
				where h.grand_eCode=tg.grand_eCode and h.sub_eCode=tg.sub_eCode) as hotel_cnt,
			/* 호텔대상 판정용 — hotel_cal2.php와 같은 hotel_assign 기준 (옛 데이터는 sub_eCode 공란이라 함께 인정) */
			(select count(*) from hotel_assign ha
				where ha.grand_eCode=tg.grand_eCode and (ha.sub_eCode=tg.sub_eCode or ifnull(ha.sub_eCode,'')='')) as hotel_assign_cnt,
			(select count(*) from car_settlesum cs
				where cs.grand_eCode=tg.grand_eCode and cs.sub_eCode=tg.sub_eCode) as car_cnt,
			(select concat_ws('|', ifnull(gm.reg_status,''), ifnull(gm.finance_st,''), ifnull(gm.ceo_st,''), gm.settle_code)
				from guide_setmaster gm
				where gm.grand_eCode=tg.grand_eCode and gm.sub_eCode=tg.sub_eCode
				order by ".guideMasterPickOrderExpr()." limit 1) as guide_st
		from tour_guide tg
		join tour_master tm on tm.grand_eCode = tg.grand_eCode and tm.p_code = tg.p_code
		left join product_master pm on pm.p_code = tg.p_code
		left join member_list ml on ml.userid = tg.guide_id and ml.division = 'guide'
		where tg.stDate between '$scan' and '$to'
		  and tg.p_code not like 'ADD%'
		  and tm.ev_status != '4'
		group by tg.grand_eCode, tg.sub_eCode
		having edDate between '$from' and '$to'
		order by edDate desc, tg.sub_eCode asc";

		$rst  = $dbConn->query($qry);
		$rows = array();
		while ($rst && $row = $rst->fetch_assoc()) $rows[] = $row;
		return $rows;
	}
?>

    <style>
		.index_div1 {/*border-top:1px solid #999;*/border-bottom:1px solid #999;padding:7px 7px 7px 0px;}
		.index_padding {padding-top:10px;}
		.index_margin-top {margin-top:10px;}
		.index_margin-bottom {margin-bottom:0px !important;}
		.index-border-bottom {border-bottom:1px solid #999;}
		.autocut{text-overflow:ellipsis;overflow:hidden;}
		.index_table_fixed {table-layout:fixed;white-space:nowrap;}
		.index_scroll {height: 200px;overflow-y: scroll;}
		.index_table_color {color:#eee;}
		.index_table_color a {text-decoration:none ; color:#eee;}
		.autocut a {text-decoration:none ; color:#333;}
		.index-margint-top {margin-top:0px !important;}
		/* 임박 행사 카드 */
		.soon-card {display:block;padding:10px 12px;margin-bottom:12px;border:1px solid #ddd;border-radius:4px;background:#fff;color:#333;text-decoration:none;}
		.soon-card:hover {text-decoration:none;color:#333;box-shadow:0 1px 4px rgba(0,0,0,.15);}
		.soon-card.soon-danger {border-left:5px solid #d9534f;}
		.soon-card.soon-warn {border-left:5px solid #f0932b;}
		.soon-card.soon-ok {border-left:5px solid #5cb85c;}
		.soon-head {overflow:hidden;}
		.soon-dday {float:left;font-size:16px;font-weight:bold;color:#3993ba;}
		.soon-date {float:right;font-size:12px;color:#999;}
		.soon-name {margin-top:4px;font-size:13px;font-weight:bold;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
		.soon-code {font-size:11px;color:#999;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
		.soon-rate {margin-top:6px;font-size:12px;color:#666;}
		.soon-rate b {font-size:18px;color:#333;}
		.soon-pct {float:right;font-weight:bold;color:#3993ba;}
		.soon-wait {margin-left:6px;color:#f0932b;}
		.soon-progress {height:6px;margin:4px 0 6px;}
		.soon-badges {font-size:11px;}
		.soon-empty {color:#999;padding:10px 0;}
		.badge-success {background:#28a745;color:#fff;}
		.badge-danger {background:#d9534f;color:#fff;}
		.badge-orange {background:#f0932b;color:#fff;}
		.badge-muted {background:#adb5bd;color:#fff;}
		/* 종료 행사 정산현황 카드 (soon-card 재사용) */
		.end-scroll {max-height:560px;overflow-y:auto;overflow-x:hidden;}
		.end-dday {color:#777;}
		.end-summary {margin-left:6px;font-size:12px;}
		.end-guide {font-size:11px;color:#666;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
		.end-steps {display:flex;margin-top:6px;}
		.end-step {flex:1;margin-right:4px;padding:4px 0;border:1px solid #eee;border-radius:3px;text-align:center;font-size:11px;color:#666;text-decoration:none;}
		.end-step:last-child {margin-right:0;}
		.end-step:hover {background:#f5f5f5;color:#333;text-decoration:none;}
		.end-step .badge {display:block;margin:3px 4px 0;font-size:11px;font-weight:normal;}
	</style>

<div id="contentwrapper" class="js-mainPage">
	<div class="main_content">
	   <div id="jCrumbs" class="breadCrumb module">
			<ul>
				<li>
					<a href="#"><i class="glyphicon glyphicon-home"></i></a>
				</li>
			</ul>
	   </div>
	   <!-- 바로가기 -->	 
	   <div class="row">
	       <div class="col-sm-12">
		       <h4 class="heading"><strong>바로가기</strong></h4>
		   </div>
	    </div>
		<div class="row index-margint-top">
			<div class="col-sm-4">
				<img src='img/logo_p.png' width="80%">
			</div>
			<div class="col-sm-8">
				<ul class="dshb_icoNav clearfix">
					<li><a href="base_reservation.php?division=3&pdx=2&sub=10&ty=1" style="background-image: url(img/gCons/bookmark.png)">예약등록</a></li>
					<li><a href="base_reservation_mylist.php?division=3&pdx=4&sub=10" style="background-image: url(img/gCons/addressbook.png)">MY 예약현황</a></li>
					
					<li><a href="employee_cal_mylist.php?division=6&pdx=3&sub=15" style="background-image: url(img/gCons/dollar.png)">MY 수금현황</a></li>
				</ul>
			</div>
		</div>
        <!-- 예약현황 -->	
		<div class="row">
	       <div class="col-sm-12">
				<h4 class="heading"><strong>예약현황</strong></h4>
		    </div>
	    </div>
		<div class="row index-margint-top">
			<div class="col-sm-12">
				<div class="col-sm-12 tac">
					<div class="col-sm-4 index_div1">
						<table class="table table-borderless index_margin-bottom">
							<tr>
								<td class="text-left index-border-bottom index_table_color" width="100%" bgcolor="#3993ba" ><strong>Daily Sale<strong></td>
							</tr>
							<tr>
								<td width="100%" style="padding-top:10px;">
								    <table class="table table-borderless index_margin-bottom">
										<tr>
											<td><i class="fa fa-dollar fa-2x"></i></td>
											<td class="text-left" width="29%"><?php echo  number_format($row0[scnt],2) ?></</td>
											<td><i class="fas fa-male fa-2x"></i><i class="fas fa-male fa-2x"></i><i class="fas fa-male fa-2x"></i></td>
											<td><?php echo $row11[cnt];?></td>
											<td><i class="fas fa-male fa-2x"></i></td>
											<td><?php echo $row00[cnt];?></td>
										</tr>
									</table>
								</td>
							</tr>
							
						</table>
					</div>
					<div class="col-sm-4 index_div1">
						<table class="table table-borderless index_margin-bottom">
							<tr>
								<td class="text-left index-border-bottom index_table_color" width="100%" bgcolor="#3993ba"><strong>Monthly Sale<strong></td>
							</tr>
							<tr>
								<td width="100%" style="padding-top:10px;">
								    <table class="table table-borderless index_margin-bottom">
										<tr>
											<td><i class="fa fa-dollar fa-2x"></i></td>
											<td class="text-left" width="29%"><?php echo  number_format($row1[scnt],2) ?></</td>
											<td><i class="fas fa-male fa-2x"></i><i class="fas fa-male fa-2x"></i><i class="fas fa-male fa-2x"></i></td>
											<td><?php echo $row2[cnt];?></td>
											<td><i class="fas fa-male fa-2x"></i></td>
											<td><?php echo $row6[cnt];?></td>
										</tr>
									</table>
								</td>
							</tr>
							
						</table>
					</div>
					<div class="col-sm-4 index_div1">
						<table class="table table-borderless index_margin-bottom">
							<tr>
								<td class="text-left index-border-bottom index_table_color" width="100%" bgcolor="#3993ba"><strong>Weekly Sale<strong></td>
							</tr>
							<tr>
								<td width="100%" style="padding-top:10px;">
								    <table class="table table-borderless index_margin-bottom">
										<tr>
											<td><i class="fa fa-dollar fa-2x"></i></td>
											<td class="text-left" width="35%"><?php echo  number_format($row11[scnt],2) ?></td>
											<td><i class="fas fa-male fa-2x"></i><i class="fas fa-male fa-2x"></i><i class="fas fa-male fa-2x"></i></td>
											<td><?php echo $row5[cnt];?></td>
											<td><i class="fas fa-male fa-2x"></i></td>
											<td><?php echo $row7[cnt];?></td>
										</tr>
									</table>
								</td>
							</tr>
							
						</table>
					</div>
				</div>
				<br />
				<br />
				<br />
				<br />
				<div class="col-sm-12 tac index_margin-top">
					<table class="table table-borderless index_margin-bottom">
						<tr>
							<td width="100%" style="padding-top:50px;">
							  
							</td>
						</tr>
						
					</table>
				</div>
			</div>
				<!--<div class="col-sm-12 tac index_margin-top">
					<table class="table table-borderless index_margin-bottom">
						<tr>
							<td width="100%" style="padding-top:10px;">
							    <!-- 예약현황 - bar chart 
								<div id="bar-container" style="min-width: 310px; height: 270px; max-width: 600px; margin: 0 auto"></div>
							</td>
						</tr>
						
					</table>
				</div>
			</div>-->
			<!-- 예약현황 - pie chart 
			<div class="col-sm-4">
				<div id="pie-container" style="min-width: 310px; height: 250px; max-width: 600px; margin: 0 auto"></div>
			</div>-->
		</div>
		<!-- 임박 행사 -->
		<div class="row">
	       <div class="col-sm-12">
				<h4 class="heading"><strong>임박 행사</strong> <small>30일 이내 출발 &middot; 모객률 &middot; 배차</small>
					<a href="assign_list.php?division=5&pdx=2&sub=10&startDate1=<?=date("Y-m-d")?>&endDate1=<?=date("Y-m-d",strtotime("+30 day"))?>" class="pull-right" style="font-size:13px;">더보기</a>
				</h4>
		    </div>
	    </div>
		<div class="row index-margint-top">
<?php
	$soonEvents = getSoonEvents();
	if (!$soonEvents) {
		echo "<div class='col-sm-12'><p class='soon-empty'>30일 이내 출발하는 예약 행사가 없습니다.</p></div>";
	}
	foreach ($soonEvents as $ev) {
		$cap   = (int)$ev['cap'];
		$pax   = (int)$ev['pax'];
		$rate  = ($cap > 0) ? round($pax * 100 / $cap) : 0;
		$dday  = (int)floor((strtotime($ev['stDate']) - strtotime(date("Y-m-d"))) / 86400);
		$carPax   = (int)$ev['car_cnt'];
		$busCnt   = (int)$ev['bus_cnt'];
		$guideCnt = (int)$ev['guide_cnt'];

		/* 모객률 색 : 80%↑ 양호 / 50%↑ 보통 / 그 외 저조 */
		$rateClass = ($rate >= 80) ? 'progress-bar-success' : (($rate >= 50) ? 'progress-bar-info' : 'progress-bar-warning');
		/* 배차 : 없음(빨강) / 인원·가이드 불일치(주황) / 정상(초록) — assign_list.php와 동일 규칙 */
		if ($carPax == 0) {
			$carBadge  = "<span class='badge badge-danger'>배차없음</span>";
			$sideClass = ($dday <= 7) ? 'soon-danger' : 'soon-warn';
		} elseif ($carPax != $pax || $guideCnt != $busCnt) {
			$carBadge  = "<span class='badge badge-orange'>배차 $carPax/$pax &middot; 가이드 $guideCnt/{$busCnt}대</span>";
			$sideClass = 'soon-warn';
		} else {
			$carBadge  = "<span class='badge badge-success'>배차 $carPax명 &middot; {$busCnt}대</span>";
			$sideClass = 'soon-ok';
		}
		$guideBadge = ($guideCnt > 0)
			? "<span class='badge badge-success'>가이드 $guideCnt</span>"
			: "<span class='badge badge-muted'>가이드없음</span>";

		$link = "assign_m.php?division=5&pdx=2&sub=10&st={$ev['stDate']}&pcode={$ev['p_code']}";
?>
			<div class="col-sm-6 col-md-4 col-lg-3">
				<a href="<?=$link?>" target="_blank" class="soon-card <?=$sideClass?>">
					<div class="soon-head">
						<span class="soon-dday"><?=($dday == 0) ? 'D-DAY' : 'D-'.$dday?></span>
						<span class="soon-date"><?=$ev['stDate']?></span>
					</div>
					<div class="soon-name" title="<?=htmlspecialchars($ev['p_name'])?>"><?=htmlspecialchars($ev['p_name'])?></div>
					<div class="soon-code"><?=htmlspecialchars($ev['grand_eCode'])?> / <?=htmlspecialchars($ev['p_code'])?></div>
					<div class="soon-rate">
						<b><?=$pax?></b> / <?=$cap?>명 <span class="soon-pct"><?=$rate?>%</span>
						<?php if ((int)$ev['wait_pax'] > 0) { ?><span class="soon-wait">대기 <?=(int)$ev['wait_pax']?></span><?php } ?>
					</div>
					<div class="progress soon-progress">
						<div class="progress-bar <?=$rateClass?>" style="width:<?=min($rate,100)?>%"></div>
					</div>
					<div class="soon-badges"><?=$carBadge?> <?=$guideBadge?></div>
				</a>
			</div>
<?php } ?>
		</div>
		<!-- 종료 행사 정산현황 -->
<?php
	/* 카드 표시값 준비 (미완료 건수를 제목에 쓰기 위해 먼저 계산) */
	$endedEvents = getEndedEvents();
	$endPending  = 0;
	foreach ($endedEvents as $i => $ee) {
		/* guide_st = reg_status|finance_st|ceo_st|settle_code (마스터 없으면 null) */
		$gs = ($ee['guide_st'] === null) ? array('', '', '', '') : array_pad(explode('|', $ee['guide_st']), 4, '');
		/* [라벨, 배지색, 완료여부] — 판정 순서는 getGuideStatus()와 동일 */
		if ($gs[1] != '')             $g = array('회계확인', 'badge-success', 1);
		elseif ($gs[2] != '')         $g = array('대표확인', 'badge-success', 1);
		elseif ($gs[0] == 'COMPLETE') $g = array('보고완료', 'badge-success', 1);
		elseif ($gs[0] == 'DONE')     $g = array('보고전',   'badge-orange',  0);
		else                          $g = array('미등록',   'badge-danger',  0);

		$hotelReg    = ((int)$ee['hotel_cnt'] > 0) ? 1 : 0;
		$carReg      = ((int)$ee['car_cnt'] > 0) ? 1 : 0;
		/* 호텔대상 = 호텔배정 있음 또는 이미 호텔정산 등록 — 비대상은 호텔 배지·집계에서 제외 */
		$hotelTarget = ((int)$ee['hotel_assign_cnt'] > 0 || $hotelReg) ? 1 : 0;
		$endTotal    = 2 + $hotelTarget;
		$done        = $hotelReg + $carReg + $g[2];
		if ($done < $endTotal) $endPending++;

		$endedEvents[$i] += array('gs' => $gs, 'g' => $g, 'hotelReg' => $hotelReg, 'carReg' => $carReg, 'hotelTarget' => $hotelTarget, 'total' => $endTotal, 'done' => $done);
	}
?>
		<div class="row">
	       <div class="col-sm-12">
				<h4 class="heading"><strong>종료 행사 정산현황</strong> <small>최근 30일 종료 &middot; 호텔 &middot; 차량 &middot; 가이드</small>
					<span class="end-summary"><span class="label label-warning">미완료 <?=$endPending?></span> <span class="label label-default">전체 <?=count($endedEvents)?></span></span>
					<a href="guide_settle.php?division=6&pdx=2&sub=10" class="pull-right" style="font-size:13px;">더보기</a>
				</h4>
		    </div>
	    </div>
		<div class="end-scroll">
		<div class="row index-margint-top">
<?php
	if (!$endedEvents) {
		echo "<div class='col-sm-12'><p class='soon-empty'>최근 30일 내 종료된 행사가 없습니다.</p></div>";
	}
	foreach ($endedEvents as $ee) {
		$ago  = (int)floor((strtotime(date("Y-m-d")) - strtotime($ee['edDate'])) / 86400);
		$rate = round($ee['done'] * 100 / $ee['total']);
		/* 테두리 : 전부 완료(초록) / 일부(주황) / 전부 미완료(빨강) */
		$sideClass = ($ee['done'] == $ee['total']) ? 'soon-ok' : (($ee['done'] == 0) ? 'soon-danger' : 'soon-warn');
		$barClass  = ($ee['done'] == $ee['total']) ? 'progress-bar-success' : 'progress-bar-warning';

		$subQs = "number={$ee['seq']}&sub_eCode=".urlencode($ee['sub_eCode']);
		/* [이름, 링크, 라벨, 배지색] — 호텔은 호텔대상 행사에만 */
		$steps = array(
			array('차량',   "car_cal2.php?division=6&pdx=1&sub=15&$subQs",   $ee['carReg'] ? '완료' : '미등록',   $ee['carReg'] ? 'badge-success' : 'badge-danger'),
			array('가이드', "guide_cal_m.php?division=6&pdx=2&sub=10&number={$ee['seq']}&scode=".urlencode($ee['gs'][3]), $ee['g'][0], $ee['g'][1]),
		);
		if ($ee['hotelTarget']) {
			array_unshift($steps, array('호텔', "hotel_cal2.php?division=6&pdx=1&sub=10&$subQs", $ee['hotelReg'] ? '완료' : '미등록', $ee['hotelReg'] ? 'badge-success' : 'badge-danger'));
		}
?>
			<div class="col-sm-6 col-md-4 col-lg-3">
				<div class="soon-card <?=$sideClass?>">
					<div class="soon-head">
						<span class="soon-dday end-dday">D+<?=$ago?></span>
						<span class="soon-date">종료 <?=$ee['edDate']?></span>
					</div>
					<div class="soon-name" title="<?=htmlspecialchars($ee['p_name'])?>"><?=htmlspecialchars($ee['p_name'])?></div>
					<div class="soon-code"><?=htmlspecialchars($ee['grand_eCode'])?> / <?=htmlspecialchars($ee['sub_eCode'])?></div>
					<div class="end-guide">가이드 <?=($ee['guide_names'] != '') ? htmlspecialchars($ee['guide_names']) : '-'?></div>
					<div class="soon-rate">정산 <b><?=$ee['done']?></b> / <?=$ee['total']?> <span class="soon-pct"><?=$rate?>%</span></div>
					<div class="progress soon-progress">
						<div class="progress-bar <?=$barClass?>" style="width:<?=$rate?>%"></div>
					</div>
					<div class="end-steps">
<?php foreach ($steps as $step) { ?>
						<a href="<?=$step[1]?>" target="_blank" class="end-step"><?=$step[0]?><span class="badge <?=$step[3]?>"><?=$step[2]?></span></a>
<?php } ?>
					</div>
				</div>
			</div>
<?php } ?>
		</div>
		</div>
		<!-- 게시판 -->
		<div class="row">
	       <div class="col-sm-12">
				<h4 class="heading"><strong>게시판</strong></h4>
		    </div>
	    </div>
		<div class="row index-margint-top">
			<div class="col-sm-12">
				<div class="col-sm-3 index_scroll">
					<table class="table table-borderless">
						<tr>
							<td class="text-left index-border-bottom index_table_color"  bgcolor="#3993ba"><strong>단체문의<strong></td>
							<td class="text-right index-border-bottom index_table_color" bgcolor="#3993ba"><strong><a href="board_list.php?division=8&pdx=3&sub=10&table_id=35">더보기</a><strong></td>
						</tr>
						<?php printBoard('35'); ?>
					</table>
				</div>
				<div class="col-sm-3 index_scroll">
					<table class="table table-borderless">
						<tr>
							<td class="text-left index-border-bottom index_table_color"  bgcolor="#3993ba"><strong>상품공지<strong></td>
							<td class="text-right index-border-bottom index_table_color" bgcolor="#3993ba"><strong><a href="board_list.php?division=8&pdx=1&sub=25&table_id=25">더보기</a><strong></td>
						</tr>
						<?php printBoard('25'); ?>
					</table>
				</div>
				<div class="col-sm-3 index_scroll">
					<table class="table table-borderless">
						<tr>
							<td class="text-left index-border-bottom index_table_color"  bgcolor="#3993ba"><strong>사내공지<strong></td>
							<td class="text-right index-border-bottom index_table_color" bgcolor="#3993ba"><strong><a href="board_list.php?division=8&pdx=1&sub=15&table_id=15">더보기</a><strong></td>
						</tr>
						<?php printBoard('15'); ?>
						
					</table>
				</div>
				<div class="col-sm-3 index_scroll">
					<table class="table table-borderless">
						<tr>
							<td class="text-left index-border-bottom index_table_color"  bgcolor="#3993ba"><strong>문의게시판<strong></td>
							<td class="text-right index-border-bottom index_table_color" bgcolor="#3993ba"><strong><a href="board_list.php?division=8&pdx=1&sub=10&table_id=01">더보기</a><strong></td>
						</tr>
						<?php printBoard('01'); ?>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>

	    <?php
			include "include/side_m.php";
			//exit;
		?>
		

		<script>
			$(document).ready(function () {
				pt.initMainPage()
				$(".dataTables_length").css({ "display" :"none" });
			})

			/*예약현황 - bar chart*/
			Highcharts.chart('bar-container', {
				chart: {
					type: 'column'
				},
				title: {
					text: 'Daily Sale'
				},
				subtitle: {
					text: ''
				},
				xAxis: {
					categories: [
						'Sophia',
						'Joyce',
						'Sophia',
						'Joyce',
						'Sophia',
						'Joyce',
						'Sophia',
						'Joyce',
						'Sophia',
						'Joyce',
						'Sophia',
						'Joyce'
					],
					crosshair: true
				},
				yAxis: {
					min: 0,
					title: {
						text: 'amount'
					}
				},
                credits: {
					enabled: false
				}, 
				tooltip: {
					headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
					pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
						'<td style="padding:0"><b>{point.y:.1f}</b></td></tr>',
					footerFormat: '</table>',
					shared: true,
					useHTML: true
				},
				plotOptions: {
					column: {
						pointPadding: 0.2,
						borderWidth: 0
					},
					series: {
						pointWidth: 11
					}
				},
				series: [{
					name: 'USD',
					data: [83.6, 78.8, 98.5, 93.4, 106.0, 84.5, 105.0, 104.3, 91.2, 83.5, 106.6, 92.3],
					color: '#F45B5B'

				}]
			});
            /*예약현황 - pie chart*/
			Highcharts.chart('pie-container', {
			  chart: {
				plotBackgroundColor: null,
				plotBorderWidth: null,
				plotShadow: false,
				type: 'pie'
			  },
			  title: {
				text: ''
			  },
			  credits: {
				enabled: false
			  },
			  tooltip: {
				pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
			  },
			  plotOptions: {
				pie: {
				  allowPointSelect: true,
				  cursor: 'pointer',
				  dataLabels: {
					enabled: false
				  },
				  showInLegend: true
				}
			  },
			  series: [{
				name: '경로',
				colorByPoint: true,
				data: [{
				  name: '웹',
				  y: 61.41,
				  sliced: true,
				  selected: true
				}, {
				  name: '협력사',
				  y: 11.84
				}, {
				  name: '직접',
				  y: 10.85
				}, {
				  name: '기타',
				  y: 4.67
				}]
			  }]
			});
			
		</script>
		
		

    </body>
</html>
