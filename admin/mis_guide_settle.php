<?php
	include "include/header.php";
	include_once "include/guide_settle_inc.php";

	if ($_COOKIE['MEMLOGIN_ADMIN_HELLO'] == "") {
		echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}
	if (!hasMenuAccess($division, $pdx, $sub)) {
		Misc::jvAlert("권한이 있는 메뉴가 아닙니다. 확인후 사용하세요.!!","");
		echo "<meta http-equiv='refresh' content='0; url=index.php'>";
		exit;
	}

	// ---------------------------------------------------------------
	// 조회조건 (기본: 2달 전 1일 ~ 이번 달 말일). 날짜 형식만 허용 → 쿼리에 그대로 넣어도 안전
	// ---------------------------------------------------------------
	$ms_from  = isset($_GET['ms_from'])  ? trim($_GET['ms_from'])  : '';
	$ms_to    = isset($_GET['ms_to'])    ? trim($_GET['ms_to'])    : '';
	$ms_guide = isset($_GET['ms_guide']) ? trim($_GET['ms_guide']) : '';
	if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $ms_from)) $ms_from = date('Y-m-01', strtotime(date('Y-m-01').' -2 month'));
	if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $ms_to))   $ms_to   = date('Y-m-t');
	if ($ms_from > $ms_to) { $t = $ms_from; $ms_from = $ms_to; $ms_to = $t; }

	$ms_where = "a.stDate BETWEEN '$ms_from' AND '$ms_to' AND a.p_code NOT LIKE 'ADD%'";
	if ($ms_guide !== '') {
		$kw = $dbConn->real_escape_string($ms_guide);
		$ms_where .= " AND (a.guide_id LIKE '%{$kw}%' OR ml.kor_name LIKE '%{$kw}%')";
	}

	// 가이드정산 목록(guide_settle.php)과 같은 대상: tour_guide 1행 = 1건, ADD 상품 제외, tour_master 있는 행사만
	$ms_sql = "SELECT a.seq_no, a.grand_eCode, a.sub_eCode, a.stDate, a.p_name, a.guide_id,
	                  COALESCE(ml.kor_name, a.guide_id) AS guide_name,
	                  gsm.settle_code,
	                  ".gsStatusExpr()." AS st,
	                  ".gsAmountExpr()." AS amt
	             FROM tour_guide a
	             ".gsMasterJoinSql()."
	             LEFT JOIN member_list ml ON ml.userid = a.guide_id
	             ".gsAmountJoinSql()."
	            WHERE $ms_where
	              AND EXISTS (SELECT 1 FROM tour_master b WHERE b.grand_eCode = a.grand_eCode AND b.p_code = a.p_code)
	            ORDER BY a.stDate, a.seq_no";

	// 집계 버킷: 건수/금액/경과 + 상태별 건수(st)·금액(sta)
	function msAdd(&$bucket, $key, $st, $amt, $late) {
		if (!isset($bucket[$key])) $bucket[$key] = array('cnt' => 0, 'amt' => 0, 'late' => 0, 'st' => array(), 'sta' => array());
		$b = &$bucket[$key];
		$b['cnt']++;
		$b['amt'] += $amt;
		if ($late) $b['late']++;
		$b['st'][$st]  = (isset($b['st'][$st])  ? $b['st'][$st]  : 0) + 1;
		$b['sta'][$st] = (isset($b['sta'][$st]) ? $b['sta'][$st] : 0) + $amt;
	}

	$ms_sts   = gsStatuses();
	$ms_today = date('Y-m-d');
	$byMonth = array(); $byGuide = array(); $byProd = array(); $ms_tot = array();
	$seenCode = array();

	$rst = $dbConn->query($ms_sql);
	while ($rst && ($r = $rst->fetch_assoc())) {
		// 같은 정산코드가 여러 행에 붙어도 금액은 한 번만
		$amt = 0;
		if ($r['settle_code'] != '' && !isset($seenCode[$r['settle_code']])) {
			$seenCode[$r['settle_code']] = 1;
			$amt = (float)$r['amt'];
		}
		// 미처리 경과: 행사일이 지났는데 아직 미등록/등록(보고 전)
		$late = ($r['stDate'] < $ms_today && ($r['st'] === '미등록' || $r['st'] === '등록'));
		$gname = ($r['guide_name'] != '') ? $r['guide_name'] : '(미배정)';

		msAdd($byMonth, substr($r['stDate'], 0, 7), $r['st'], $amt, $late);
		msAdd($byGuide, $gname, $r['st'], $amt, $late);
		msAdd($byProd, $r['p_name'], $r['st'], $amt, $late);
		msAdd($ms_tot, 'T', $r['st'], $amt, $late);
	}
	ksort($byMonth);
	$byCnt = function ($x, $y) { return $y['cnt'] - $x['cnt']; };
	uasort($byGuide, $byCnt);
	uasort($byProd, $byCnt);
	$T = isset($ms_tot['T']) ? $ms_tot['T'] : array('cnt' => 0, 'amt' => 0, 'late' => 0, 'st' => array(), 'sta' => array());

	function msN($b, $st)  { return isset($b['st'][$st])  ? $b['st'][$st]  : 0; }
	function msA($b, $st)  { return isset($b['sta'][$st]) ? $b['sta'][$st] : 0; }
	function msPct($a, $b) { return $b > 0 ? number_format($a * 100 / $b, 1).'%' : '-'; }
	function msPeriod($ym, $from, $to) {
		$f = $ym.'-01';
		$e = date('Y-m-t', strtotime($f));
		if ($from > $f) $f = $from;
		if ($to < $e)   $e = $to;
		return "$f ~ $e";
	}

	// 표 하나 출력: $cols = [ [헤더HTML, function($key, $bucket) → 셀, 정렬class], ... ]
	function msTable($id, $title, $cols, $rows, $foot = null) {
		echo "<div class='ms-block'><h4 class='ms-title'>".htmlspecialchars($title)."</h4>";
		echo "<table id='$id' class='table table-bordered table-condensed table-hover ms-tbl' data-title='".htmlspecialchars($title, ENT_QUOTES)."'><thead><tr>";
		foreach ($cols as $c) echo "<th class='text-center'>{$c[0]}</th>";
		echo "</tr></thead><tbody>";
		foreach ($rows as $k => $b) {
			echo "<tr>";
			foreach ($cols as $c) echo "<td class='".(isset($c[2]) ? $c[2] : 'text-right')."'>".$c[1]($k, $b)."</td>";
			echo "</tr>";
		}
		echo "</tbody>";
		if ($foot !== null) {
			echo "<tfoot><tr class='active'>";
			foreach ($cols as $i => $c) echo "<th class='".(isset($c[2]) ? $c[2] : 'text-right')."'>".($i === 0 ? '합계' : $c[1]('합계', $foot))."</th>";
			echo "</tr></tfoot>";
		}
		echo "</table></div>";
	}

	// 공통 열 묶음
	$colStCnt = array();
	foreach ($ms_sts as $st) {
		$colStCnt[] = array(gsStBadge($st), function ($k, $b) use ($st) { $n = msN($b, $st); return $n ? number_format($n) : ''; });
	}
	$colStAmt = array();
	foreach ($ms_sts as $st) {
		if ($st === '미등록') continue; // 미등록은 정산서가 없어 금액이 항상 0
		$colStAmt[] = array(gsStBadge($st), function ($k, $b) use ($st) { return msN($b, $st) ? gsAmt(msA($b, $st)) : ''; });
	}
	$colPeriod = array('기간', function ($k, $b) use ($ms_from, $ms_to) { return msPeriod($k, $ms_from, $ms_to); }, 'text-center');
	$colCnt    = array('합계 건수', function ($k, $b) { return '<b>'.number_format($b['cnt']).'</b>'; });
	$colRegPct = array('정산 등록률', function ($k, $b) { return msPct($b['cnt'] - msN($b, '미등록'), $b['cnt']); });
	$colLate   = array('미처리 경과', function ($k, $b) { return $b['late'] ? "<span class='text-danger'><b>".number_format($b['late'])."</b></span>" : ''; });
	$colAmt    = array('정산금액', function ($k, $b) { return '<b>'.gsAmt($b['amt']).'</b>'; });
?>
<style>
	<?=gsStBadgeCss()?>
	.ms-kpi { border:1px solid #ddd; border-radius:6px; padding:12px 14px; margin-bottom:12px; background:#fff; }
	.ms-kpi .k { color:#666; font-size:12px; }
	.ms-kpi .v { font-size:22px; font-weight:700; color:#222; margin-top:4px; }
	.ms-kpi .s { color:#888; font-size:12px; margin-top:2px; }
	.ms-block { margin-bottom:28px; }
	.ms-title { font-weight:700; border-left:4px solid #337ab7; padding-left:8px; margin:0 0 8px; }
	.ms-block .dt-buttons { margin-bottom:6px; }
	@media print {
		header, .navbar, .sidebar, .sidebar_switch, #jCrumbs, .ms-noprint, .dt-buttons { display:none !important; }
		#contentwrapper, .main_content { margin:0 !important; padding:0 !important; }
		.gs-st { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
		.ms-block { page-break-inside:avoid; }
	}
</style>
<div id="contentwrapper" class="reservationDetailForm">
	<div class="main_content">
		<div id="jCrumbs" class="breadCrumb module">
			<ul>
				<li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li>
				<li><a href="#">MIS</a></li>
				<li><a href="#">정산현황</a></li>
				<li>가이드정산 MIS</li>
			</ul>
		</div>

		<form method="get" class="ms-noprint">
			<input type="hidden" name="division" value="<?=htmlspecialchars($division)?>">
			<input type="hidden" name="pdx" value="<?=htmlspecialchars($pdx)?>">
			<input type="hidden" name="sub" value="<?=htmlspecialchars($sub)?>">
			<table class="table table-bordered table-condensed">
				<tr>
					<td width="10%" class="titletd text-center">조회조건</td>
					<td>
						<div class="row" style="row-gap:8px;">
							<div class="col-sm-2">
								<label class="control-label" style="font-weight:600;">행사일(From)</label>
								<input type="date" class="form-control input-sm" id="ms_from" name="ms_from" max="2999-12-31" value="<?=$ms_from?>">
							</div>
							<div class="col-sm-2">
								<label class="control-label" style="font-weight:600;">행사일(To)</label>
								<input type="date" class="form-control input-sm" id="ms_to" name="ms_to" max="2999-12-31" value="<?=$ms_to?>">
							</div>
							<div class="col-sm-2">
								<label class="control-label" style="font-weight:600;">가이드ID/이름</label>
								<input type="text" class="form-control input-sm" name="ms_guide" value="<?=htmlspecialchars($ms_guide)?>" placeholder="전체">
							</div>
							<div class="col-sm-6">
								<label class="control-label" style="opacity:.75;">빠른 선택</label>
								<div class="btn-group btn-group-sm" id="msRange" style="display:block;">
									<button type="button" class="btn btn-default" data-range="thism">이번 달</button>
									<button type="button" class="btn btn-default" data-range="lastm">지난 달</button>
									<button type="button" class="btn btn-default" data-range="3m">최근 3개월</button>
									<button type="button" class="btn btn-default" data-range="year">올해</button>
								</div>
							</div>
						</div>
						<div style="margin-top:10px;">
							<button type="submit" class="btn btn-primary btn-sm">조회</button>
							<button type="button" class="btn btn-default btn-sm" onclick="window.print();"><i class="glyphicon glyphicon-print"></i> 보고서 인쇄</button>
						</div>
					</td>
				</tr>
			</table>
		</form>

		<h3 style="margin:6px 0 14px;">가이드정산 MIS 보고서 <small><?=$ms_from?> ~ <?=$ms_to?><?=($ms_guide !== '' ? ' · 가이드: '.htmlspecialchars($ms_guide) : '')?> · 작성 <?=date('Y-m-d H:i')?></small></h3>

		<!-- 핵심 지표 -->
		<div class="row">
			<div class="col-sm-2"><div class="ms-kpi"><div class="k">총 행사</div><div class="v"><?=number_format($T['cnt'])?>건</div><div class="s">가이드 배정 기준</div></div></div>
			<div class="col-sm-2"><div class="ms-kpi"><div class="k">정산 등록률</div><div class="v"><?=msPct($T['cnt'] - msN($T, '미등록'), $T['cnt'])?></div><div class="s">미등록 <?=number_format(msN($T, '미등록'))?>건</div></div></div>
			<div class="col-sm-2"><div class="ms-kpi"><div class="k">회계확인 완료율</div><div class="v"><?=msPct(msN($T, '회계확인'), $T['cnt'])?></div><div class="s">회계확인 <?=number_format(msN($T, '회계확인'))?>건</div></div></div>
			<div class="col-sm-2"><div class="ms-kpi"><div class="k">미처리 경과</div><div class="v" style="color:#c9302c;"><?=number_format($T['late'])?>건</div><div class="s">행사일 지남 · 미등록/등록</div></div></div>
			<div class="col-sm-2"><div class="ms-kpi"><div class="k">정산금액 합계</div><div class="v"><?=gsAmt($T['amt'])?></div><div class="s">정산서 <?=number_format(count($seenCode))?>건</div></div></div>
			<div class="col-sm-2"><div class="ms-kpi"><div class="k">회계확인 금액</div><div class="v"><?=gsAmt(msA($T, '회계확인'))?></div><div class="s">확정된 금액</div></div></div>
		</div>
		<p class="text-muted small">정산금액 = 입금 − (식비 + 입장비 + 기타 + 쇼핑), AR/AP 대상 화면과 같은 기준 (음수 = 지급). 상태 판정은 가이드정산 목록과 같다.</p>

		<?php
			msTable('msMonthCnt', '월별 · 상태별 건수',
				array_merge(array($colPeriod), $colStCnt, array($colCnt, $colRegPct, $colLate)), $byMonth, $T);

			msTable('msMonthAmt', '월별 · 상태별 정산금액',
				array_merge(array($colPeriod), $colStAmt, array($colAmt)), $byMonth, $T);

			msTable('msGuide', '가이드별 현황',
				array_merge(array(array('가이드', function ($k, $b) { return htmlspecialchars($k); }, 'text-left')),
					$colStCnt, array($colCnt, $colLate, $colAmt)), $byGuide, $T);

			msTable('msProd', '행사(상품)별 현황',
				array(
					array('행사명', function ($k, $b) { return htmlspecialchars($k); }, 'text-left'),
					$colCnt,
					array('정산 등록', function ($k, $b) { return number_format($b['cnt'] - msN($b, '미등록')); }),
					$colRegPct,
					$colAmt,
					array('건당 평균', function ($k, $b) { $n = $b['cnt'] - msN($b, '미등록'); return $n ? gsAmt($b['amt'] / $n) : ''; }),
				), $byProd, $T);
		?>
	</div>
</div>
<?php
	include "include/side_m.php"
?>
<script>
	$(document).ready(function () {
		// 표마다 엑셀/인쇄 (DataTables Buttons — header.php 번들에 이미 포함)
		$('.ms-tbl').each(function () {
			var title = $(this).data('title') + ' (<?=$ms_from?> ~ <?=$ms_to?>)';
			$(this).DataTable({
				paging: false, info: false, searching: false, order: [], autoWidth: false,
				dom: 'Bt',
				language: { emptyTable: '조회된 자료가 없습니다.' },
				buttons: [
					{ extend: 'excelHtml5', text: '엑셀', title: title, footer: true, className: 'btn-sm' },
					{ extend: 'print', text: '인쇄', title: title, footer: true, className: 'btn-sm' }
				]
			});
		});

		// 빠른 기간 (로컬 날짜로 계산 — toISOString 은 UTC라 밤에 하루 밀린다)
		function ymd(d) { return d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2); }
		$('#msRange').off('click.msRange').on('click.msRange', 'button', function () {
			var now = new Date(), y = now.getFullYear(), m = now.getMonth(), s, e;
			switch ($(this).data('range')) {
				case 'thism': s = new Date(y, m, 1);     e = new Date(y, m + 1, 0); break;
				case 'lastm': s = new Date(y, m - 1, 1); e = new Date(y, m, 0);     break;
				case '3m':    s = new Date(y, m - 2, 1); e = new Date(y, m + 1, 0); break;
				case 'year':  s = new Date(y, 0, 1);     e = new Date(y, 11, 31);   break;
			}
			$('#ms_from').val(ymd(s));
			$('#ms_to').val(ymd(e));
			$(this).closest('form').submit();
		});
	});
</script>
	</body>
</html>
