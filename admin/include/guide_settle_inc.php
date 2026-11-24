<?php
	// 가이드정산 목록(guide_settle.php)과 가이드정산 MIS(mis_guide_settle.php)가 같이 쓰는 것들.
	// guideMasterPickOrderExpr() 는 dong_func.php 에 있다 (inc_base.php 가 이미 로드).

	// 화면에 쓰는 상태 순서 — getGuideStatus() 가 내는 문구와 같다
	function gsStatuses() {
		return array('미등록', '등록', '정산보고완료', '체크나감', '대표이사확인', '회계확인');
	}

	function gsAmt($a) { return ($a < 0 ? '-$' : '$').number_format(abs($a), 2); }

	// 상태 배지 색 (흰 글자 대비 4.5:1 이상)
	function gsStColor($st) {
		$c = array('미등록' => '#6c757d', '등록' => '#337ab7', '정산보고완료' => '#6f42c1',
		           '체크나감' => '#b35400', '대표이사확인' => '#0f766e', '회계확인' => '#15803d');
		return isset($c[$st]) ? $c[$st] : '#6c757d';
	}
	function gsStBadge($st) {
		return "<span class='gs-st' style='background:".gsStColor($st)."'>".htmlspecialchars($st)."</span>";
	}
	function gsStBadgeCss() {
		return ".gs-st { display:inline-block; min-width:84px; padding:4px 10px; border-radius:12px; color:#fff; font-size:12px; font-weight:600; line-height:1.4; white-space:nowrap; text-align:center; }";
	}

	// 롤백 스위치: false 로 바꾸면 정산마스터 선택 SQL 을 예전 형태로 되돌린다 (결과는 같고 기간이 길면 느림)
	if (!defined('GS_FAST_MASTER_JOIN')) define('GS_FAST_MASTER_JOIN', true);

	// 행사(grand_eCode+sub_eCode)당 '가장 확정된' 정산마스터 1건을 gsm 으로 붙인다.
	// 빠른 형태: 고른 seq_no 목록(gmx)을 먼저 만들고 PK 로 붙임. 1년치 9.7초 → 0.05초, 결과 동일 (tests/gs_status_check.php)
	function gsMasterJoinSql($tg = 'a', $fast = GS_FAST_MASTER_JOIN) {
		$pick = "SUBSTRING_INDEX(GROUP_CONCAT(seq_no ORDER BY ".guideMasterPickOrderExpr()."), ',', 1)";
		if (!$fast) {
			return "LEFT JOIN (
				SELECT gm.* FROM guide_setmaster gm
				 INNER JOIN (SELECT grand_eCode, sub_eCode, $pick AS pick_seq FROM guide_setmaster GROUP BY grand_eCode, sub_eCode) gmx
				    ON gmx.grand_eCode = gm.grand_eCode AND gmx.sub_eCode = gm.sub_eCode AND gmx.pick_seq = gm.seq_no
			) gsm ON gsm.grand_eCode = {$tg}.grand_eCode AND gsm.sub_eCode = {$tg}.sub_eCode";
		}
		return "LEFT JOIN (
				SELECT grand_eCode, sub_eCode, CAST($pick AS UNSIGNED) AS pick_seq
				  FROM guide_setmaster
				 GROUP BY grand_eCode, sub_eCode
			) gmx ON gmx.grand_eCode = {$tg}.grand_eCode AND gmx.sub_eCode = {$tg}.sub_eCode
			LEFT JOIN guide_setmaster gsm ON gsm.seq_no = gmx.pick_seq";
	}

	// 정산금액 = 입금 - (식비 + 입장비 + 기타 + 쇼핑) — arap_target_cards.php 와 같은 기준
	function gsAmountJoinSql() {
		return "LEFT JOIN (SELECT settle_code, SUM(input_amt * input_cnt) amt FROM guide_inputamt GROUP BY settle_code) gi ON gi.settle_code = gsm.settle_code
			LEFT JOIN (SELECT settle_code, SUM(meal_pricetotal) amt FROM guide_meal GROUP BY settle_code) gml ON gml.settle_code = gsm.settle_code
			LEFT JOIN (SELECT settle_code, SUM(e_pricetot) amt FROM guide_admission GROUP BY settle_code) gad ON gad.settle_code = gsm.settle_code
			LEFT JOIN (SELECT settle_code, SUM(etc_amt) amt FROM guide_etcamt GROUP BY settle_code) gec ON gec.settle_code = gsm.settle_code
			LEFT JOIN (SELECT settle_code, SUM(home_comamt) amt FROM guide_shopping GROUP BY settle_code) gsh ON gsh.settle_code = gsm.settle_code";
	}
	function gsAmountExpr() {
		return "COALESCE(gi.amt, 0) - (COALESCE(gml.amt, 0) + COALESCE(gad.amt, 0) + COALESCE(gec.amt, 0) + COALESCE(gsh.amt, 0))";
	}

	// getGuideStatus() 와 같은 판정을 SQL 로 (gsm 기준). 목록 화면 상태와 같은지 tests/gs_status_check.php 로 확인.
	function gsStatusExpr() {
		return "CASE WHEN COALESCE(gsm.finance_st, '') <> '' THEN '회계확인'
		             WHEN COALESCE(gsm.ceo_st, '') <> '' THEN '대표이사확인'
		             WHEN COALESCE(gsm.check_out, '') <> '' THEN '체크나감'
		             WHEN gsm.reg_status = 'COMPLETE' THEN '정산보고완료'
		             WHEN gsm.reg_status = 'DONE' THEN '등록'
		             ELSE '미등록' END";
	}
?>
