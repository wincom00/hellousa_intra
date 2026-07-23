<?php
/* ============================================================
 * 행사별 손익 리포트 (profit_list.php)
 * - 행사 단위: tour_car(grand_eCode, sub_eCode) 기준
 * - 매출    : reserve_info.last_total (rev_status='DONE', tour_car로 예약↔행사 연결)
 *             ※ 예약 전액을 그 예약의 '첫날 행사'(가장 이른 배정일)에 한 번만 계상.
 *               다일정 패키지는 첫날에 매출이 몰리고 이후 날짜 행사는 원가만 잡힘(총계는 중복/누락 없이 정확).
 * - 호텔원가: hotel_settlesum.real_amt(실제) 우선, 없으면 hotel_settle+hotel_settleetc(예상)
 * - 차량원가: car_settlesum.real_amt(실제) 우선, 없으면 car_settle+car_settleetc(예상)
 * - 가이드원가: guide_meal + guide_admission + guide_etcamt
 *               ※ 정산코드(settle_code)가 있어야 유효한 정산. 행사당 대표 1건(정산완료 우선)만 반영해 이중계산 방지.
 * - 행사수입: guide_shopping.c_profit + guide_option.o_cprofit (마이너스 원가)
 * - 손익 = 매출 + 행사수입 - (호텔 + 차량 + 가이드 원가)
 * ============================================================ */

if (isset($_GET['action']) && $_GET['action'] == 'export') ob_start();
include "include/header.php"; // $dbConn: mysqli
if (isset($_GET['action']) && $_GET['action'] == 'export') ob_end_clean();

if (empty($_COOKIE['MEMLOGIN_ADMIN_HELLO'])) {
    echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
    exit;
}

/* --------------------------- 필터 ---------------------------- */
$from_ym = isset($_GET['from_ym']) && $_GET['from_ym'] != '' ? $_GET['from_ym'] : date('Y').'-01';
$to_ym   = isset($_GET['to_ym'])   && $_GET['to_ym']   != '' ? $_GET['to_ym']   : date('Y-m');
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$settled_only = isset($_GET['settled_only']) && $_GET['settled_only'] == '1'; // 가이드정산 있는 행사만

$from_date = $from_ym.'-01';
$dtTo = DateTime::createFromFormat('Y-m-d', $to_ym.'-01');
if ($dtTo) { $dtTo->modify('last day of this month'); $to_date = $dtTo->format('Y-m-d'); }
else { $to_date = date('Y-m-d'); }

$esc_from = $dbConn->real_escape_string($from_date);
$esc_to   = $dbConn->real_escape_string($to_date);
$esc_kw   = $dbConn->real_escape_string($keyword);

$whereOuter = "WHERE e.stDate BETWEEN '$esc_from' AND '$esc_to'";
if ($keyword != '') {
    $whereOuter .= " AND (tg.p_name LIKE '%$esc_kw%'
                       OR tg.p_code LIKE '%$esc_kw%'
                       OR e.grand_eCode LIKE '%$esc_kw%'
                       OR ml.kor_name LIKE '%$esc_kw%'
                       OR tg.guide_id LIKE '%$esc_kw%')";
}
if ($settled_only) {
    $whereOuter .= " AND g.settle_code IS NOT NULL AND g.settle_code <> ''";
}

/* --------------------------- 행사별 손익 쿼리 ---------------------------- */
$sql = "SELECT
            e.grand_eCode,
            e.sub_eCode,
            e.stDate,
            COALESCE(tg.p_code, '')  AS p_code,
            COALESCE(tg.p_name, '')  AS p_name,
            COALESCE(ml.kor_name, tg.guide_id, '') AS guide_name,
            COALESCE(rc.rsv_cnt, 0)  AS rsv_cnt,
            COALESCE(rev.sales_total, 0) AS sales_total,
            COALESCE(h.room_amt, 0)  AS hotel_room_amt,
            COALESCE(he.etc_amt, 0)  AS hotel_etc_amt,
            hs.real_amt              AS hotel_real,
            COALESCE(c.bus_amt, 0)   AS car_bus_amt,
            COALESCE(ce.etc_amt, 0)  AS car_etc_amt,
            cs.real_amt              AS car_real,
            COALESCE(g.meal_total, 0)       AS meal_total,
            COALESCE(g.admission_total, 0)  AS admission_total,
            COALESCE(g.etcamt_total, 0)     AS etcamt_total,
            COALESCE(g.shopping_cprofit, 0) AS shopping_cprofit,
            COALESCE(g.option_cprofit, 0)   AS option_cprofit,
            COALESCE(g.input_total, 0)      AS input_total,
            g.settle_code                   AS guide_settle_code,
            COALESCE(g.reg_status, '')      AS guide_reg_status
        FROM (
            SELECT grand_eCode, sub_eCode, MIN(stDate) AS stDate
            FROM tour_car
            GROUP BY grand_eCode, sub_eCode
        ) e
        /* 대표 tour_guide (상품명/가이드) */
        LEFT JOIN (
            SELECT grand_eCode, sub_eCode, MAX(seq_no) AS guide_seq_no
            FROM tour_guide
            GROUP BY grand_eCode, sub_eCode
        ) tg_pick ON tg_pick.grand_eCode = e.grand_eCode AND tg_pick.sub_eCode = e.sub_eCode
        LEFT JOIN tour_guide tg ON tg.seq_no = tg_pick.guide_seq_no
        LEFT JOIN member_list ml ON ml.userid = tg.guide_id
        /* 예약 건수 */
        LEFT JOIN (
            SELECT grand_eCode, sub_eCode, COUNT(DISTINCT reserveCode) AS rsv_cnt
            FROM tour_car
            GROUP BY grand_eCode, sub_eCode
        ) rc ON rc.grand_eCode = e.grand_eCode AND rc.sub_eCode = e.sub_eCode
        /* 매출: 예약 전액(last_total)을 그 예약의 '첫날 행사'(가장 이른 배정 행사)에 한 번만 계상.
           last_total은 예약 총액이 상품 행마다 중복 저장되므로 MAX로 예약당 1개 값만 취함.
           다일정 패키지는 첫날 행사에 매출이 몰리고 이후 날짜 행사는 원가만 잡힘(총계는 중복/누락 없이 정확). */
        LEFT JOIN (
            SELECT grand_eCode, sub_eCode, SUM(last_total) AS sales_total
            FROM (
                SELECT re.reserveCode, re.grand_eCode, re.sub_eCode, re.last_total,
                       ROW_NUMBER() OVER (
                           PARTITION BY re.reserveCode
                           ORDER BY re.ev_date ASC, re.grand_eCode ASC
                       ) AS rn
                FROM (
                    SELECT tc.reserveCode, tc.grand_eCode, tc.sub_eCode,
                           MIN(tc.stDate) AS ev_date,
                           MAX(ri.last_total) AS last_total
                    FROM tour_car tc
                    INNER JOIN reserve_info ri
                        ON ri.reserveCode = tc.reserveCode AND ri.p_code = tc.p_code
                       AND ri.rev_status = 'DONE'
                    GROUP BY tc.reserveCode, tc.grand_eCode, tc.sub_eCode
                ) re
            ) z
            WHERE z.rn = 1
            GROUP BY grand_eCode, sub_eCode
        ) rev ON rev.grand_eCode = e.grand_eCode AND rev.sub_eCode = e.sub_eCode
        /* 호텔 원가 */
        LEFT JOIN (
            SELECT grand_eCode, sub_eCode, SUM(hotel_amt) AS room_amt
            FROM hotel_settle GROUP BY grand_eCode, sub_eCode
        ) h ON h.grand_eCode = e.grand_eCode AND h.sub_eCode = e.sub_eCode
        LEFT JOIN (
            SELECT grand_eCode, sub_eCode, SUM(etc_amt) AS etc_amt
            FROM hotel_settleetc GROUP BY grand_eCode, sub_eCode
        ) he ON he.grand_eCode = e.grand_eCode AND he.sub_eCode = e.sub_eCode
        LEFT JOIN (
            SELECT grand_eCode, sub_eCode, SUM(real_amt) AS real_amt
            FROM hotel_settlesum WHERE status = 'DONE'
            GROUP BY grand_eCode, sub_eCode
        ) hs ON hs.grand_eCode = e.grand_eCode AND hs.sub_eCode = e.sub_eCode
        /* 차량 원가 */
        LEFT JOIN (
            SELECT grand_eCode, sub_eCode, SUM(bus_amt) AS bus_amt
            FROM car_settle GROUP BY grand_eCode, sub_eCode
        ) c ON c.grand_eCode = e.grand_eCode AND c.sub_eCode = e.sub_eCode
        LEFT JOIN (
            SELECT grand_eCode, sub_eCode, SUM(etc_amt) AS etc_amt
            FROM car_settleetc GROUP BY grand_eCode, sub_eCode
        ) ce ON ce.grand_eCode = e.grand_eCode AND ce.sub_eCode = e.sub_eCode
        LEFT JOIN (
            SELECT grand_eCode, sub_eCode, SUM(real_amt) AS real_amt
            FROM car_settlesum WHERE status = 'DONE'
            GROUP BY grand_eCode, sub_eCode
        ) cs ON cs.grand_eCode = e.grand_eCode AND cs.sub_eCode = e.sub_eCode
        /* 가이드 정산: 정산코드(settle_code)가 있어야 유효한 정산.
           한 행사에 정산코드가 여러 개면(재저장 등으로 발생) 대표 1건만 사용해 이중계산 방지.
           우선순위: reg_status COMPLETE(정산보고완료) > DONE(등록) > 기타, 동순위는 최신 seq_no */
        LEFT JOIN (
            SELECT s.grand_eCode, s.sub_eCode, s.settle_code, s.reg_status,
                   COALESCE(gm.meal_total,0)      AS meal_total,
                   COALESCE(ga.admission_total,0) AS admission_total,
                   COALESCE(ge.etcamt_total,0)    AS etcamt_total,
                   COALESCE(gs.c_profit,0)        AS shopping_cprofit,
                   COALESCE(go.o_cprofit,0)       AS option_cprofit,
                   COALESCE(gi.input_total,0)     AS input_total
            FROM (
                SELECT gsm.grand_eCode, gsm.sub_eCode, gsm.settle_code, gsm.reg_status,
                       ROW_NUMBER() OVER (
                           PARTITION BY gsm.grand_eCode, gsm.sub_eCode
                           ORDER BY CASE gsm.reg_status
                                        WHEN 'COMPLETE' THEN 2
                                        WHEN 'DONE' THEN 1
                                        ELSE 0 END DESC,
                                    gsm.seq_no DESC
                       ) AS rn
                FROM guide_setmaster gsm
                WHERE gsm.settle_code IS NOT NULL AND gsm.settle_code <> ''
            ) s
            LEFT JOIN (SELECT settle_code, SUM(meal_pricetotal) AS meal_total
                       FROM guide_meal GROUP BY settle_code) gm ON gm.settle_code = s.settle_code
            LEFT JOIN (SELECT settle_code, SUM(e_pricetot) AS admission_total
                       FROM guide_admission GROUP BY settle_code) ga ON ga.settle_code = s.settle_code
            LEFT JOIN (SELECT settle_code, SUM(etc_amt) AS etcamt_total
                       FROM guide_etcamt GROUP BY settle_code) ge ON ge.settle_code = s.settle_code
            LEFT JOIN (SELECT settle_code, SUM(c_profit) AS c_profit
                       FROM guide_shopping GROUP BY settle_code) gs ON gs.settle_code = s.settle_code
            LEFT JOIN (SELECT settle_code, SUM(o_cprofit) AS o_cprofit
                       FROM guide_option GROUP BY settle_code) go ON go.settle_code = s.settle_code
            LEFT JOIN (SELECT settle_code, SUM(input_amt * input_cnt) AS input_total
                       FROM guide_inputamt GROUP BY settle_code) gi ON gi.settle_code = s.settle_code
            WHERE s.rn = 1
        ) g ON g.grand_eCode = e.grand_eCode AND g.sub_eCode = e.sub_eCode
        $whereOuter
        ORDER BY e.stDate DESC, e.grand_eCode DESC";

$rows = array();
$rst = $dbConn->query($sql);
if ($rst) {
    while ($row = $rst->fetch_assoc()) {

        $hotel_expected = (float)$row['hotel_room_amt'] + (float)$row['hotel_etc_amt'];
        $hotel_is_real  = ($row['hotel_real'] !== null);
        $hotel_cost     = $hotel_is_real ? (float)$row['hotel_real'] : $hotel_expected;

        $car_expected = (float)$row['car_bus_amt'] + (float)$row['car_etc_amt'];
        $car_is_real  = ($row['car_real'] !== null);
        $car_cost     = $car_is_real ? (float)$row['car_real'] : $car_expected;

        $guide_cost   = (float)$row['meal_total'] + (float)$row['admission_total'] + (float)$row['etcamt_total'];
        $event_income = (float)$row['shopping_cprofit'] + (float)$row['option_cprofit'];

        $sales  = (float)$row['sales_total'];
        $cost   = $hotel_cost + $car_cost + $guide_cost;
        $profit = $sales + $event_income - $cost;
        $margin = ($sales > 0) ? ($profit / $sales * 100) : null;

        // 가이드정산 유효성: 정산코드가 있어야 유효한 정산
        $guide_settled = (isset($row['guide_settle_code']) && $row['guide_settle_code'] != '');
        $row['guide_settled'] = $guide_settled;
        if (!$guide_settled)                              $row['guide_status'] = '미정산';
        else if ($row['guide_reg_status'] == 'COMPLETE')  $row['guide_status'] = '정산완료';
        else if ($row['guide_reg_status'] == 'DONE')      $row['guide_status'] = '등록';
        else                                              $row['guide_status'] = '작성중';

        $row['hotel_cost']    = $hotel_cost;
        $row['hotel_is_real'] = $hotel_is_real;
        $row['car_cost']      = $car_cost;
        $row['car_is_real']   = $car_is_real;
        $row['guide_cost']    = $guide_cost;
        $row['event_income']  = $event_income;
        $row['total_cost']    = $cost;
        $row['profit']        = $profit;
        $row['margin']        = $margin;

        $rows[] = $row;
    }
}

/* --------------------------- 합계 ---------------------------- */
$sumSales = 0; $sumHotel = 0; $sumCar = 0; $sumGuide = 0; $sumIncome = 0; $sumCost = 0; $sumProfit = 0;
$cntSettled = 0;
foreach ($rows as $r) {
    $sumSales  += $r['sales_total'];
    $sumHotel  += $r['hotel_cost'];
    $sumCar    += $r['car_cost'];
    $sumGuide  += $r['guide_cost'];
    $sumIncome += $r['event_income'];
    $sumCost   += $r['total_cost'];
    $sumProfit += $r['profit'];
    if ($r['guide_settled']) $cntSettled++;
}
$sumMargin = ($sumSales > 0) ? ($sumProfit / $sumSales * 100) : null;

/* --------------------------- 엑셀(CSV) 다운로드 ---------------------------- */
if (isset($_GET['action']) && $_GET['action'] == 'export') {
    while (ob_get_level()) ob_end_clean();
    $filename = "profit_list_{$from_ym}_{$to_ym}.csv";
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    $fp = fopen('php://output', 'w');
    fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
    fputcsv($fp, array('행사일','행사코드','서브코드','상품코드','상품명','가이드','정산상태','정산코드','예약건수',
                       '매출','호텔원가','호텔구분','차량원가','차량구분',
                       '가이드경비','행사수입(쇼핑/옵션)','가이드납입금','총원가','손익','수익률(%)'));
    foreach ($rows as $r) {
        fputcsv($fp, array(
            $r['stDate'],
            $r['grand_eCode'],
            $r['sub_eCode'],
            $r['p_code'],
            $r['p_name'],
            $r['guide_name'],
            $r['guide_status'],
            $r['guide_settle_code'],
            (int)$r['rsv_cnt'],
            round($r['sales_total'], 2),
            round($r['hotel_cost'], 2),
            $r['hotel_is_real'] ? '실제' : '예상',
            round($r['car_cost'], 2),
            $r['car_is_real'] ? '실제' : '예상',
            round($r['guide_cost'], 2),
            round($r['event_income'], 2),
            round($r['input_total'], 2),
            round($r['total_cost'], 2),
            round($r['profit'], 2),
            ($r['margin'] === null) ? '' : round($r['margin'], 1)
        ));
    }
    fputcsv($fp, array('합계','','','','','','','', '',
        round($sumSales,2), round($sumHotel,2), '', round($sumCar,2), '',
        round($sumGuide,2), round($sumIncome,2), '', round($sumCost,2), round($sumProfit,2),
        ($sumMargin === null) ? '' : round($sumMargin,1)));
    fclose($fp);
    exit;
}

function pf_money($v) { return number_format((float)$v, 2); }
?>
<style>
.pf-summary .panel-body{padding:10px 14px;}
.pf-summary .pf-label{font-size:12px;color:#888;}
.pf-summary .pf-val{font-size:19px;font-weight:700;}
.pf-summary .pf-val.plus{color:#31708f;}
.pf-summary .pf-val.minus{color:#a94442;}
.pf-table th, .pf-table td{vertical-align:middle!important;white-space:nowrap;}
.pf-table td.num{text-align:right;}
.pf-table .profit-minus{color:#a94442;font-weight:700;}
.pf-table .profit-plus{color:#31708f;font-weight:700;}
.pf-badge-real{background:#5cb85c;}
.pf-badge-est{background:#aaa;}
.pf-badge-none{background:#d9534f;}
.js-sortable th{cursor:pointer;position:relative;user-select:none;}
.js-sortable th .sort-caret{position:absolute;right:6px;top:50%;transform:translateY(-50%);font-size:10px;opacity:.35;}
.js-sortable th[aria-sort="ascending"] .sort-caret::after{content:"▲";opacity:.9;}
.js-sortable th[aria-sort="descending"] .sort-caret::after{content:"▼";opacity:.9;}
.pf-note{font-size:12px;color:#777;margin-top:6px;}
</style>

<div id="contentwrapper" class="reservationDetailForm">
  <div class="main_content">

    <div id="jCrumbs" class="breadCrumb module">
      <ul>
        <li><a href="/"><i class="glyphicon glyphicon-home"></i></a></li>
        <li><a href="#">정산관리</a></li>
        <li>행사별 손익 리포트 <h6>Tour Profit Report</h6></li>
      </ul>
    </div>

    <!-- 필터 -->
    <form class="form-inline" method="get" action="profit_list.php">
      <div class="panel panel-default">
        <div class="panel-body">
          <div class="form-group">
            <label>행사기간</label>
            <input type="month" class="form-control input-sm" name="from_ym" value="<?=htmlspecialchars($from_ym)?>"> ~
            <input type="month" class="form-control input-sm" name="to_ym" value="<?=htmlspecialchars($to_ym)?>">
          </div>
          <div class="form-group" style="margin-left:10px;">
            <label>검색</label>
            <input type="text" class="form-control input-sm" name="keyword" value="<?=htmlspecialchars($keyword)?>"
                   placeholder="상품명 / 상품코드 / 행사코드 / 가이드">
          </div>
          <div class="checkbox" style="margin-left:10px;display:inline-block;">
            <label><input type="checkbox" name="settled_only" value="1" <?=($settled_only ? 'checked' : '')?>> 가이드정산 완료 행사만</label>
          </div>
          <button type="submit" class="btn btn-primary btn-sm" style="margin-left:10px;">조회</button>
          <a class="btn btn-success btn-sm" style="margin-left:4px;"
             href="profit_list.php?<?=http_build_query(array('action'=>'export','from_ym'=>$from_ym,'to_ym'=>$to_ym,'keyword'=>$keyword,'settled_only'=>($settled_only?'1':'')))?>">엑셀 다운로드</a>
        </div>
      </div>
    </form>

    <!-- 요약 -->
    <div class="row pf-summary">
      <div class="col-sm-2">
        <div class="panel panel-default"><div class="panel-body">
          <div class="pf-label">행사수 (정산완료) <h6>Tours (Settled)</h6></div>
          <div class="pf-val"><?=number_format(count($rows))?>
            <small style="font-size:13px;color:#888;">(<?=number_format($cntSettled)?>)</small>
          </div>
        </div></div>
      </div>
      <div class="col-sm-2">
        <div class="panel panel-default"><div class="panel-body">
          <div class="pf-label">총매출 <h6>Sales</h6></div>
          <div class="pf-val"><?=pf_money($sumSales)?></div>
        </div></div>
      </div>
      <div class="col-sm-2">
        <div class="panel panel-default"><div class="panel-body">
          <div class="pf-label">총원가 <h6>Cost</h6></div>
          <div class="pf-val"><?=pf_money($sumCost)?></div>
        </div></div>
      </div>
      <div class="col-sm-2">
        <div class="panel panel-default"><div class="panel-body">
          <div class="pf-label">행사수입 <h6>Extra Income</h6></div>
          <div class="pf-val"><?=pf_money($sumIncome)?></div>
        </div></div>
      </div>
      <div class="col-sm-2">
        <div class="panel panel-default"><div class="panel-body">
          <div class="pf-label">총손익 <h6>Profit</h6></div>
          <div class="pf-val <?=($sumProfit < 0 ? 'minus' : 'plus')?>"><?=pf_money($sumProfit)?></div>
        </div></div>
      </div>
      <div class="col-sm-2">
        <div class="panel panel-default"><div class="panel-body">
          <div class="pf-label">수익률 <h6>Margin</h6></div>
          <div class="pf-val <?=($sumMargin !== null && $sumMargin < 0 ? 'minus' : 'plus')?>">
            <?=($sumMargin === null) ? '-' : number_format($sumMargin, 1).'%'?>
          </div>
        </div></div>
      </div>
    </div>

    <!-- 리스트 -->
    <div class="panel panel-default">
      <div class="panel-heading">
        행사별 손익 <h6>Profit by Tour</h6>
        <span class="pf-note">원가: <span class="label pf-badge-real">실제</span> = 정산완료(settlesum) 금액,
        <span class="label pf-badge-est">예상</span> = 정산입력(단가×수량) 합산 금액</span>
      </div>
      <div class="panel-body" style="padding:0;overflow-x:auto;">
        <table class="table table-bordered table-condensed table-hover pf-table js-sortable" style="margin:0;">
          <thead>
            <tr>
              <th data-sort-type="date">행사일<h6>Date</h6></th>
              <th data-sort-type="text">상품<h6>Product</h6></th>
              <th data-sort-type="text">행사코드<h6>Code</h6></th>
              <th data-sort-type="text">가이드<h6>Guide</h6></th>
              <th data-sort-type="text">정산<h6>Settle</h6></th>
              <th data-sort-type="number">예약<h6>Rsv</h6></th>
              <th data-sort-type="number">매출<h6>Sales</h6></th>
              <th data-sort-type="number">호텔원가<h6>Hotel</h6></th>
              <th data-sort-type="number">차량원가<h6>Car</h6></th>
              <th data-sort-type="number">가이드경비<h6>Guide</h6></th>
              <th data-sort-type="number">행사수입<h6>Income</h6></th>
              <th data-sort-type="number">손익<h6>Profit</h6></th>
              <th data-sort-type="number">수익률<h6>Margin</h6></th>
            </tr>
          </thead>
          <tbody>
          <?php if (count($rows) == 0): ?>
            <tr><td colspan="13" class="text-center" style="padding:20px;">조회된 행사가 없습니다.</td></tr>
          <?php endif; ?>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?=htmlspecialchars($r['stDate'])?></td>
              <td>
                <?=htmlspecialchars($r['p_name'] != '' ? $r['p_name'] : $r['p_code'])?>
                <?php if ($r['p_name'] != '' && $r['p_code'] != ''): ?>
                  <span style="color:#999;font-size:11px;">(<?=htmlspecialchars($r['p_code'])?>)</span>
                <?php endif; ?>
              </td>
              <td style="font-size:11px;color:#666;">
                <?=htmlspecialchars($r['grand_eCode'])?><?php if ($r['sub_eCode'] != ''): ?> / <?=htmlspecialchars($r['sub_eCode'])?><?php endif; ?>
              </td>
              <td><?=htmlspecialchars($r['guide_name'])?></td>
              <td>
                <?php
                  $st = $r['guide_status'];
                  $stCls = ($st == '정산완료') ? 'pf-badge-real'
                         : (($st == '미정산') ? 'pf-badge-none' : 'pf-badge-est');
                ?>
                <span class="label <?=$stCls?>"><?=htmlspecialchars($st)?></span>
              </td>
              <td class="num"><?=number_format($r['rsv_cnt'])?></td>
              <td class="num"><?=pf_money($r['sales_total'])?></td>
              <td class="num">
                <?=pf_money($r['hotel_cost'])?>
                <?php if ($r['hotel_cost'] > 0 || $r['hotel_is_real']): ?>
                  <span class="label <?=$r['hotel_is_real'] ? 'pf-badge-real' : 'pf-badge-est'?>"><?=$r['hotel_is_real'] ? '실제' : '예상'?></span>
                <?php endif; ?>
              </td>
              <td class="num">
                <?=pf_money($r['car_cost'])?>
                <?php if ($r['car_cost'] > 0 || $r['car_is_real']): ?>
                  <span class="label <?=$r['car_is_real'] ? 'pf-badge-real' : 'pf-badge-est'?>"><?=$r['car_is_real'] ? '실제' : '예상'?></span>
                <?php endif; ?>
              </td>
              <td class="num">
                <?=pf_money($r['guide_cost'])?>
                <?php if (!$r['guide_settled']): ?>
                  <span class="label pf-badge-none" title="가이드정산 미입력 - 원가 누락 가능">미정산</span>
                <?php endif; ?>
              </td>
              <td class="num"><?=pf_money($r['event_income'])?></td>
              <td class="num <?=($r['profit'] < 0 ? 'profit-minus' : 'profit-plus')?>"><?=pf_money($r['profit'])?></td>
              <td class="num <?=($r['margin'] !== null && $r['margin'] < 0 ? 'profit-minus' : '')?>">
                <?=($r['margin'] === null) ? '-' : number_format($r['margin'], 1).'%'?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <th colspan="6" class="text-right">합계</th>
              <th class="num text-right"><?=pf_money($sumSales)?></th>
              <th class="num text-right"><?=pf_money($sumHotel)?></th>
              <th class="num text-right"><?=pf_money($sumCar)?></th>
              <th class="num text-right"><?=pf_money($sumGuide)?></th>
              <th class="num text-right"><?=pf_money($sumIncome)?></th>
              <th class="num text-right <?=($sumProfit < 0 ? 'profit-minus' : 'profit-plus')?>"><?=pf_money($sumProfit)?></th>
              <th class="num text-right"><?=($sumMargin === null) ? '-' : number_format($sumMargin, 1).'%'?></th>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <div class="pf-note">
      · 매출: 예약 전액을 그 예약의 <b>첫날 행사</b>(가장 이른 배정일)에 한 번만 계상합니다. 다일정 패키지는 첫날에 매출이 몰리고 이후 날짜 행사는 원가만 잡히므로(개별 행사일은 적자로 보일 수 있음), 손익은 <b>기간 합계</b> 또는 패키지 <b>첫날 행사</b> 기준으로 해석하세요.
      · 행사수입: 쇼핑 회사수익 + 옵션 회사수익 (가이드납입금은 엑셀에만 별도 표시)
      · 가이드경비: 식대 + 입장료 + 기타비용(guide/car/etc)
      · <span class="label pf-badge-none">미정산</span> 행사는 가이드정산(정산코드)이 없어 가이드원가가 누락되어 손익이 실제보다 높게 보일 수 있습니다. "가이드정산 완료 행사만" 필터로 유효 손익만 볼 수 있습니다.
      · 한 행사에 정산코드가 여러 개면 대표 1건(정산완료 우선)만 반영해 이중계산을 방지합니다.
    </div>

  </div>
</div>

<?php include "include/side_m.php"; ?>

<script>
$(function(){

  // ===== 테이블 헤더 클릭 정렬 =====
  function parseByType(text, type) {
    if (text == null) return null;
    var t = String(text).trim();
    switch (type) {
      case 'number':
        t = t.replace(/[,%]/g, '').replace(/(실제|예상|정산)/g, '');
        var n = parseFloat(t);
        return isNaN(n) ? null : n;
      case 'date':
        var d = new Date(t);
        return isNaN(d.getTime()) ? null : d.getTime();
      default:
        return t.toLowerCase();
    }
  }

  $('table.js-sortable').each(function(){
    var table = this;
    if (!table.tHead || !table.tHead.rows.length) return;

    $(table.tHead.rows[0].cells).each(function(){
      if (!$(this).find('.sort-caret').length) {
        $(this).append('<span class="sort-caret"></span>');
      }
    });

    $(table.tHead).off('click.pfSort').on('click.pfSort', 'th', function(){
      var th = this;
      var ths = Array.prototype.slice.call(table.tHead.rows[0].cells);
      var colIndex = ths.indexOf(th);
      if (colIndex < 0) return;
      var type = th.getAttribute('data-sort-type') || 'text';

      var curr = th.getAttribute('aria-sort');
      var dir  = (curr === 'ascending') ? 'descending' : 'ascending';
      ths.forEach(function(h){ if (h !== th) h.removeAttribute('aria-sort'); });
      th.setAttribute('aria-sort', dir);

      var tbody = table.tBodies[0];
      if (!tbody) return;
      var rows = Array.prototype.slice.call(tbody.rows);
      rows.forEach(function(r, i){ r.__idx = i; });

      rows.sort(function(a, b){
        var A = a.cells[colIndex] ? a.cells[colIndex].innerText : '';
        var B = b.cells[colIndex] ? b.cells[colIndex].innerText : '';
        var aV = parseByType(A, type);
        var bV = parseByType(B, type);
        if (aV == null && bV == null) return a.__idx - b.__idx;
        if (aV == null) return 1;
        if (bV == null) return -1;
        var cmp = 0;
        if (type === 'number' || type === 'date') cmp = (aV < bV) ? -1 : (aV > bV ? 1 : 0);
        else cmp = aV.localeCompare(bV);
        return (dir === 'ascending') ? cmp : -cmp;
      });

      var frag = document.createDocumentFragment();
      rows.forEach(function(r){ frag.appendChild(r); });
      tbody.appendChild(frag);
      if (table.tFoot) table.appendChild(table.tFoot);
    });
  });

});
</script>
