<?php
/* ============================================================
 * 복합상품별 손익 리포트 (profit_pkg_list.php) - profit_list.php(행사별)와 탭으로 전환
 * - 집계 단위: 복합상품(reserve_info parent='MAIN'의 p_code) × 출발일(MAIN stDate)
 *             ※ 단일상품 단독예약은 MAIN 행이 곧 그 단일상품이므로 그대로 한 행으로 잡힘.
 * - 매출    : MAIN 행의 last_total (rev_status='DONE', 예약당 1건)
 * - 원가    : 구성 단일상품 행사(tour_car grand_eCode, sub_eCode)의 원가를
 *             '예약 배정인원 / 행사 배정인원'(tour_car 1행 = 1명, DONE 예약만) 비율로 예약별 배분 후 합산.
 *             한 행사에 여러 복합상품 예약이 섞여 있어도 행사 원가가 중복/누락 없이 나뉨.
 *   · 호텔원가: hotel_settlesum.real_amt(실제) 우선, 없으면 hotel_settle+hotel_settleetc(예상)
 *   · 차량원가: car_settlesum.real_amt(실제) 우선, 없으면 car_settle+car_settleetc(예상)
 *   · 가이드원가: guide_meal + guide_admission + guide_etcamt
 *                 ※ 정산코드(settle_code)가 있어야 유효한 정산. 행사당 대표 1건(정산완료 우선)만 반영해 이중계산 방지.
 * - 행사수입: guide_shopping.c_profit + guide_option.o_cprofit (마이너스 원가, 원가와 같은 비율로 배분)
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
$settled_only = isset($_GET['settled_only']) && $_GET['settled_only'] == '1'; // 구성행사 전부 가이드정산된 상품만

$from_date = $from_ym.'-01';
$dtTo = DateTime::createFromFormat('Y-m-d', $to_ym.'-01');
if ($dtTo) { $dtTo->modify('last day of this month'); $to_date = $dtTo->format('Y-m-d'); }
else { $to_date = date('Y-m-d'); }

$esc_from = $dbConn->real_escape_string($from_date);
$esc_to   = $dbConn->real_escape_string($to_date);
$esc_kw   = $dbConn->real_escape_string($keyword);

/* 탭 이동/조회 시 좌측 메뉴 활성(division/pdx/sub)과 기간을 그대로 전달 */
$pf_keep = array();
foreach (array('division', 'pdx', 'sub') as $k) if (isset($_GET[$k])) $pf_keep[$k] = $_GET[$k];
$pf_tabq = http_build_query($pf_keep + array('from_ym' => $from_ym, 'to_ym' => $to_ym));

$whereOuter = "WHERE m.stDate BETWEEN '$esc_from' AND '$esc_to'";
if ($keyword != '') {
    $whereOuter .= " AND (m.p_name LIKE '%$esc_kw%'
                       OR m.p_code LIKE '%$esc_kw%'
                       OR m.reserveCode LIKE '%$esc_kw%')";
}
$having = $settled_only ? "HAVING ev_cnt > 0 AND settled_cnt = ev_cnt" : "";

/* --------------------------- 복합상품별 손익 쿼리 ---------------------------- */
$sql = "WITH
        /* 기준 예약: MAIN 행 = 복합상품(단일상품 단독예약이면 그 상품). 예약당 1행 */
        m AS (
            SELECT reserveCode, p_code, p_name, stDate, p_cnt, last_total
            FROM reserve_info
            WHERE parent = 'MAIN' AND rev_status = 'DONE'
        ),
        /* 예약별 행사 배정인원 (tour_car 1행 = 1명) */
        tcm AS (
            SELECT tc.reserveCode, tc.grand_eCode, tc.sub_eCode, COUNT(*) AS pax
            FROM tour_car tc
            INNER JOIN m ON m.reserveCode = tc.reserveCode
            GROUP BY tc.reserveCode, tc.grand_eCode, tc.sub_eCode
        ),
        /* 행사(단일상품) 단위 원가 */
        ev AS (
            SELECT x.grand_eCode, x.sub_eCode, x.ev_pax,
                   COALESCE(hs.real_amt, COALESCE(h.room_amt,0) + COALESCE(he.etc_amt,0)) AS hotel_cost,
                   CASE WHEN hs.real_amt IS NULL AND COALESCE(h.room_amt,0) + COALESCE(he.etc_amt,0) <> 0 THEN 1 ELSE 0 END AS hotel_est,
                   COALESCE(cs.real_amt, COALESCE(c.bus_amt,0) + COALESCE(ce.etc_amt,0)) AS car_cost,
                   CASE WHEN cs.real_amt IS NULL AND COALESCE(c.bus_amt,0) + COALESCE(ce.etc_amt,0) <> 0 THEN 1 ELSE 0 END AS car_est,
                   COALESCE(g.meal_total,0) + COALESCE(g.admission_total,0) + COALESCE(g.etcamt_total,0) AS guide_cost,
                   COALESCE(g.shopping_cprofit,0) + COALESCE(g.option_cprofit,0) AS event_income,
                   COALESCE(g.input_total,0) AS input_total,
                   CASE WHEN g.settle_code IS NULL THEN 0 ELSE 1 END AS settled,
                   CASE WHEN g.reg_status = 'COMPLETE' THEN 1 ELSE 0 END AS complete
            FROM (
                SELECT grand_eCode, sub_eCode, SUM(pax) AS ev_pax
                FROM tcm GROUP BY grand_eCode, sub_eCode
            ) x
            /* 호텔 원가 */
            LEFT JOIN (
                SELECT grand_eCode, sub_eCode, SUM(hotel_amt) AS room_amt
                FROM hotel_settle GROUP BY grand_eCode, sub_eCode
            ) h ON h.grand_eCode = x.grand_eCode AND h.sub_eCode = x.sub_eCode
            LEFT JOIN (
                SELECT grand_eCode, sub_eCode, SUM(etc_amt) AS etc_amt
                FROM hotel_settleetc GROUP BY grand_eCode, sub_eCode
            ) he ON he.grand_eCode = x.grand_eCode AND he.sub_eCode = x.sub_eCode
            LEFT JOIN (
                SELECT grand_eCode, sub_eCode, SUM(real_amt) AS real_amt
                FROM hotel_settlesum WHERE status = 'DONE'
                GROUP BY grand_eCode, sub_eCode
            ) hs ON hs.grand_eCode = x.grand_eCode AND hs.sub_eCode = x.sub_eCode
            /* 차량 원가 */
            LEFT JOIN (
                SELECT grand_eCode, sub_eCode, SUM(bus_amt) AS bus_amt
                FROM car_settle GROUP BY grand_eCode, sub_eCode
            ) c ON c.grand_eCode = x.grand_eCode AND c.sub_eCode = x.sub_eCode
            LEFT JOIN (
                SELECT grand_eCode, sub_eCode, SUM(etc_amt) AS etc_amt
                FROM car_settleetc GROUP BY grand_eCode, sub_eCode
            ) ce ON ce.grand_eCode = x.grand_eCode AND ce.sub_eCode = x.sub_eCode
            LEFT JOIN (
                SELECT grand_eCode, sub_eCode, SUM(real_amt) AS real_amt
                FROM car_settlesum WHERE status = 'DONE'
                GROUP BY grand_eCode, sub_eCode
            ) cs ON cs.grand_eCode = x.grand_eCode AND cs.sub_eCode = x.sub_eCode
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
            ) g ON g.grand_eCode = x.grand_eCode AND g.sub_eCode = x.sub_eCode
        ),
        /* 예약별 원가 배분: 행사 원가 × (예약 배정인원 / 행사 배정인원) */
        rc AS (
            SELECT t.reserveCode,
                   SUM(ev.hotel_cost   * t.pax / ev.ev_pax) AS hotel_cost,
                   SUM(ev.car_cost     * t.pax / ev.ev_pax) AS car_cost,
                   SUM(ev.guide_cost   * t.pax / ev.ev_pax) AS guide_cost,
                   SUM(ev.event_income * t.pax / ev.ev_pax) AS event_income,
                   SUM(ev.input_total  * t.pax / ev.ev_pax) AS input_total,
                   MAX(ev.hotel_est) AS hotel_est,
                   MAX(ev.car_est)   AS car_est
            FROM tcm t
            INNER JOIN ev ON ev.grand_eCode = t.grand_eCode AND ev.sub_eCode = t.sub_eCode
            GROUP BY t.reserveCode
        ),
        /* 복합상품·출발일별 구성 행사 수 / 가이드정산된 행사 수 */
        ec AS (
            SELECT p_code, stDate, COUNT(*) AS ev_cnt, SUM(settled) AS settled_cnt, SUM(complete) AS complete_cnt
            FROM (
                SELECT DISTINCT m.p_code, m.stDate, ev.grand_eCode, ev.sub_eCode, ev.settled, ev.complete
                FROM m
                INNER JOIN tcm t ON t.reserveCode = m.reserveCode
                INNER JOIN ev ON ev.grand_eCode = t.grand_eCode AND ev.sub_eCode = t.sub_eCode
                WHERE m.stDate BETWEEN '$esc_from' AND '$esc_to'
            ) d
            GROUP BY p_code, stDate
        ),
        /* 복합상품·출발일별 배정 현황
           차량 = 예약자가 탄 행사별 버스(grand_eCode, sub_eCode, bus_num) 수
           가이드 = 그 버스들에 배정된 메인가이드(tour_guide.guide_id) 인원 수
           호텔 = 그 행사들의 호텔배정(hotel_assign) 호텔 수 (hotel_assign은 예약번호 없이 행사 단위 저장) */
        asg AS (
            SELECT b.p_code, b.stDate,
                   COUNT(DISTINCT b.grand_eCode, b.sub_eCode, b.bus_num) AS car_cnt,
                   COUNT(DISTINCT NULLIF(tg.guide_id, ''))               AS guide_cnt,
                   COUNT(DISTINCT ha.hotel_code)                         AS hotel_cnt
            FROM (
                SELECT DISTINCT m.p_code, m.stDate, tc.grand_eCode, tc.sub_eCode, tc.bus_num
                FROM m
                INNER JOIN tour_car tc ON tc.reserveCode = m.reserveCode
                WHERE m.stDate BETWEEN '$esc_from' AND '$esc_to'
            ) b
            LEFT JOIN tour_guide tg
                   ON tg.grand_eCode = b.grand_eCode AND tg.sub_eCode = b.sub_eCode AND tg.bus_num = b.bus_num
            LEFT JOIN hotel_assign ha
                   ON ha.grand_eCode = b.grand_eCode AND ha.sub_eCode = b.sub_eCode
            GROUP BY b.p_code, b.stDate
        )
        SELECT m.p_code, m.stDate,
               MAX(m.p_name)                     AS p_name,
               COUNT(*)                          AS rsv_cnt,
               SUM(m.p_cnt)                      AS pax_cnt,
               SUM(m.last_total)                 AS sales_total,
               SUM(COALESCE(rc.hotel_cost,0))    AS hotel_cost,
               SUM(COALESCE(rc.car_cost,0))      AS car_cost,
               SUM(COALESCE(rc.guide_cost,0))    AS guide_cost,
               SUM(COALESCE(rc.event_income,0))  AS event_income,
               SUM(COALESCE(rc.input_total,0))   AS input_total,
               COALESCE(MAX(rc.hotel_est),0)     AS hotel_est,
               COALESCE(MAX(rc.car_est),0)       AS car_est,
               COALESCE(MAX(ec.ev_cnt),0)        AS ev_cnt,
               COALESCE(MAX(ec.settled_cnt),0)   AS settled_cnt,
               COALESCE(MAX(ec.complete_cnt),0)  AS complete_cnt,
               COALESCE(MAX(asg.car_cnt),0)      AS car_cnt,
               COALESCE(MAX(asg.hotel_cnt),0)    AS hotel_cnt,
               COALESCE(MAX(asg.guide_cnt),0)    AS guide_cnt
        FROM m
        LEFT JOIN rc ON rc.reserveCode = m.reserveCode
        LEFT JOIN ec ON ec.p_code = m.p_code AND ec.stDate = m.stDate
        LEFT JOIN asg ON asg.p_code = m.p_code AND asg.stDate = m.stDate
        $whereOuter
        GROUP BY m.p_code, m.stDate
        $having
        ORDER BY m.stDate DESC, m.p_code";

$rows = array();
$rst = $dbConn->query($sql);
if ($rst) {
    while ($row = $rst->fetch_assoc()) {

        $hotel_cost   = (float)$row['hotel_cost'];
        $car_cost     = (float)$row['car_cost'];
        $guide_cost   = (float)$row['guide_cost'];
        $event_income = (float)$row['event_income'];

        $sales  = (float)$row['sales_total'];
        $cost   = $hotel_cost + $car_cost + $guide_cost;
        $profit = $sales + $event_income - $cost;
        $margin = ($sales > 0) ? ($profit / $sales * 100) : null;

        // 구성 행사 중 예상(정산입력) 금액이 하나라도 섞이면 '예상'
        $row['hotel_is_real'] = ($row['hotel_est'] == 0);
        $row['car_is_real']   = ($row['car_est'] == 0);

        // 가이드정산: 구성 행사 전부 정산코드가 있어야 유효
        $ev_cnt  = (int)$row['ev_cnt'];
        $set_cnt = (int)$row['settled_cnt'];
        $guide_settled = ($ev_cnt > 0 && $set_cnt == $ev_cnt);
        $row['guide_settled'] = $guide_settled;
        if ($ev_cnt == 0)                                  $row['guide_status'] = '미배정';
        else if ($set_cnt == 0)                            $row['guide_status'] = '미정산';
        else if (!$guide_settled)                          $row['guide_status'] = '부분정산';
        else if ((int)$row['complete_cnt'] == $ev_cnt)     $row['guide_status'] = '정산완료';
        else                                               $row['guide_status'] = '등록';

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
    $filename = "profit_pkg_list_{$from_ym}_{$to_ym}.csv";
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    $fp = fopen('php://output', 'w');
    fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
    fputcsv($fp, array('출발일','상품코드','상품명','정산상태','정산행사수','구성행사수','예약건수','인원',
                       '차량수','호텔수','가이드수',
                       '매출','호텔원가','호텔구분','차량원가','차량구분',
                       '가이드경비','행사수입(쇼핑/옵션)','가이드납입금','총원가','손익','수익률(%)'));
    foreach ($rows as $r) {
        fputcsv($fp, array(
            $r['stDate'],
            $r['p_code'],
            $r['p_name'],
            $r['guide_status'],
            (int)$r['settled_cnt'],
            (int)$r['ev_cnt'],
            (int)$r['rsv_cnt'],
            (int)$r['pax_cnt'],
            (int)$r['car_cnt'],
            (int)$r['hotel_cnt'],
            (int)$r['guide_cnt'],
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
    fputcsv($fp, array('합계','','','','','','','','','','',
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
/* 헤더 고정: 스크롤 박스 안에서 thead sticky */
.pf-scroll{padding:0;overflow:auto;max-height:calc(100vh - 260px);}
.pf-table thead th{position:sticky;top:0;z-index:2;background:#f5f5f5;box-shadow:inset 0 -1px 0 #ddd;}
/* 금액 클릭 → 근거 팝업 */
.pf-detail{color:inherit;border-bottom:1px dotted #888;text-decoration:none!important;cursor:pointer;}
.pf-detail:hover{color:#337ab7;border-bottom-color:#337ab7;}
</style>

<div id="contentwrapper" class="reservationDetailForm">
  <div class="main_content">

    <div id="jCrumbs" class="breadCrumb module">
      <ul>
        <li><a href="/"><i class="glyphicon glyphicon-home"></i></a></li>
        <li><a href="#">정산관리</a></li>
        <li>복합상품별 손익 리포트 <h6>Package Profit Report</h6></li>
      </ul>
    </div>

    <!-- 탭 -->
    <ul class="nav nav-tabs" style="margin-bottom:10px;">
      <li><a href="profit_list.php?<?=htmlspecialchars($pf_tabq)?>">행사별 (단일상품) <h6>By Tour</h6></a></li>
      <li class="active"><a href="profit_pkg_list.php?<?=htmlspecialchars($pf_tabq)?>">복합상품별 <h6>By Package</h6></a></li>
    </ul>

    <!-- 필터 -->
    <form class="form-inline" method="get" action="profit_pkg_list.php">
      <?php foreach ($pf_keep as $k => $v): ?><input type="hidden" name="<?=htmlspecialchars($k)?>" value="<?=htmlspecialchars($v)?>"><?php endforeach; ?>
      <div class="panel panel-default">
        <div class="panel-body">
          <div class="form-group">
            <label>출발기간</label>
            <input type="month" class="form-control input-sm" name="from_ym" value="<?=htmlspecialchars($from_ym)?>"> ~
            <input type="month" class="form-control input-sm" name="to_ym" value="<?=htmlspecialchars($to_ym)?>">
          </div>
          <div class="form-group" style="margin-left:10px;">
            <label>검색</label>
            <input type="text" class="form-control input-sm" name="keyword" value="<?=htmlspecialchars($keyword)?>"
                   placeholder="상품명 / 상품코드 / 예약번호">
          </div>
          <div class="checkbox" style="margin-left:10px;display:inline-block;">
            <label><input type="checkbox" name="settled_only" value="1" <?=($settled_only ? 'checked' : '')?>> 가이드정산 완료 상품만</label>
          </div>
          <button type="submit" class="btn btn-primary btn-sm" style="margin-left:10px;">조회</button>
          <a class="btn btn-success btn-sm" style="margin-left:4px;"
             href="profit_pkg_list.php?<?=http_build_query(array('action'=>'export','from_ym'=>$from_ym,'to_ym'=>$to_ym,'keyword'=>$keyword,'settled_only'=>($settled_only?'1':'')))?>">엑셀 다운로드</a>
        </div>
      </div>
    </form>

    <!-- 요약 -->
    <div class="row pf-summary">
      <div class="col-sm-2">
        <div class="panel panel-default"><div class="panel-body">
          <div class="pf-label">상품·출발 (정산완료) <h6>Departures (Settled)</h6></div>
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
        복합상품별 손익 <h6>Profit by Package</h6>
        <span class="pf-note">원가: <span class="label pf-badge-real">실제</span> = 구성 행사 전부 정산완료(settlesum) 금액,
        <span class="label pf-badge-est">예상</span> = 정산입력(단가×수량) 금액 포함</span>
      </div>
      <div class="panel-body pf-scroll">
        <table class="table table-bordered table-condensed table-hover pf-table js-sortable" style="margin:0;">
          <thead>
            <tr>
              <th data-sort-type="date">출발일<h6>Date</h6></th>
              <th data-sort-type="text">상품<h6>Package</h6></th>
              <th data-sort-type="text">정산<h6>Settle</h6></th>
              <th data-sort-type="text">배정<h6>Assign</h6></th>
              <th data-sort-type="number">예약<h6>Rsv</h6></th>
              <th data-sort-type="number">인원<h6>Pax</h6></th>
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
            <tr><td colspan="13" class="text-center" style="padding:20px;">조회된 상품이 없습니다.</td></tr>
          <?php endif; ?>
          <?php foreach ($rows as $r): ?>
            <tr data-pk="<?=htmlspecialchars($r['p_code'])?>" data-dt="<?=htmlspecialchars($r['stDate'])?>"
                data-title="<?=htmlspecialchars($r['stDate'].' 출발 '.($r['p_name'] != '' ? $r['p_name'] : $r['p_code']).' ('.$r['p_code'].')')?>">
              <td><?=htmlspecialchars($r['stDate'])?></td>
              <td>
                <?=htmlspecialchars($r['p_name'] != '' ? $r['p_name'] : $r['p_code'])?>
                <?php if ($r['p_name'] != '' && $r['p_code'] != ''): ?>
                  <span style="color:#999;font-size:11px;">(<?=htmlspecialchars($r['p_code'])?>)</span>
                <?php endif; ?>
              </td>
              <td>
                <?php
                  $st = $r['guide_status'];
                  $stCls = ($st == '정산완료') ? 'pf-badge-real'
                         : (($st == '미정산' || $st == '미배정') ? 'pf-badge-none' : 'pf-badge-est');
                ?>
                <span class="label <?=$stCls?>"><?=htmlspecialchars($st)?></span>
                <span style="color:#999;font-size:11px;" title="가이드정산 행사 / 구성 행사"><?=(int)$r['settled_cnt']?>/<?=(int)$r['ev_cnt']?></span>
              </td>
              <td>
                <span class="label <?=$r['car_cnt'] > 0 ? 'label-primary' : 'label-default'?>" title="차량 수 (행사별 버스)">차량 <?=(int)$r['car_cnt']?></span>
                <span class="label <?=$r['hotel_cnt'] > 0 ? 'label-info' : 'label-default'?>" title="호텔 수 (구성 행사 호텔배정 기준 - 같은 행사 내 다른 객실 그룹 호텔 포함)">호텔 <?=(int)$r['hotel_cnt']?></span>
                <span class="label <?=$r['guide_cnt'] > 0 ? 'label-warning' : 'label-default'?>" title="배정 가이드 수 (메인가이드)">가이드 <?=(int)$r['guide_cnt']?></span>
              </td>
              <td class="num"><?=number_format($r['rsv_cnt'])?></td>
              <td class="num"><?=number_format($r['pax_cnt'])?></td>
              <td class="num"><a href="#" class="pf-detail" data-type="sales" data-label="매출"><?=pf_money($r['sales_total'])?></a></td>
              <td class="num">
                <a href="#" class="pf-detail" data-type="hotel" data-label="호텔원가"><?=pf_money($r['hotel_cost'])?></a>
                <?php if ($r['hotel_cost'] != 0): ?>
                  <span class="label <?=$r['hotel_is_real'] ? 'pf-badge-real' : 'pf-badge-est'?>"><?=$r['hotel_is_real'] ? '실제' : '예상'?></span>
                <?php endif; ?>
              </td>
              <td class="num">
                <a href="#" class="pf-detail" data-type="car" data-label="차량원가"><?=pf_money($r['car_cost'])?></a>
                <?php if ($r['car_cost'] != 0): ?>
                  <span class="label <?=$r['car_is_real'] ? 'pf-badge-real' : 'pf-badge-est'?>"><?=$r['car_is_real'] ? '실제' : '예상'?></span>
                <?php endif; ?>
              </td>
              <td class="num">
                <a href="#" class="pf-detail" data-type="guide" data-label="가이드경비"><?=pf_money($r['guide_cost'])?></a>
                <?php if (!$r['guide_settled']): ?>
                  <span class="label pf-badge-none" title="가이드정산 미입력 행사 포함 - 원가 누락 가능"><?=htmlspecialchars($r['guide_status'])?></span>
                <?php endif; ?>
              </td>
              <td class="num"><a href="#" class="pf-detail" data-type="income" data-label="행사수입"><?=pf_money($r['event_income'])?></a></td>
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
      · 집계 단위: <b>복합상품 × 출발일</b>. 매출은 예약 총액(MAIN 예약)을 패키지 출발일에 계상합니다. 단일상품 단독예약은 그 상품이 한 행으로 표시됩니다.
      · 원가: 구성 단일상품 행사의 호텔·차량·가이드 원가를 <b>예약 배정인원 / 행사 배정인원</b> 비율로 나눠 합산합니다(한 행사에 여러 상품 예약이 섞여도 중복/누락 없음).
      · 행사수입: 쇼핑 회사수익 + 옵션 회사수익 (원가와 같은 비율로 배분, 가이드납입금은 엑셀에만 별도 표시)
      · 가이드경비: 식대 + 입장료 + 기타비용(guide/car/etc)
      · 정산 n/m = 가이드정산된 행사 / 구성 행사. <span class="label pf-badge-none">미정산</span>·<span class="label pf-badge-est">부분정산</span> 상품은 가이드원가가 누락되어 손익이 실제보다 높게 보일 수 있습니다. "가이드정산 완료 상품만" 필터로 유효 손익만 볼 수 있습니다.
      · 한 행사에 정산코드가 여러 개면 대표 1건(정산완료 우선)만 반영해 이중계산을 방지합니다.
      · 매출·호텔원가·차량원가·가이드경비·행사수입 금액을 클릭하면 금액을 가져온 근거(예약 / 구성 행사별 배분 내역)를 볼 수 있습니다.
    </div>

  </div>
</div>

<!-- 금액 근거 팝업 -->
<div class="modal fade" id="pfDetailModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"></h4>
      </div>
      <div class="modal-body"></div>
    </div>
  </div>
</div>

<?php include "include/side_m.php"; ?>

<script>
$(function(){

  // ===== 금액 근거 팝업 =====
  var $pfModal = $('#pfDetailModal').appendTo('body');
  $(document).off('click.pfDetail').on('click.pfDetail', '.pf-detail', function(e){
    e.preventDefault();
    var $a = $(this), $tr = $a.closest('tr');
    $pfModal.find('.modal-title').text($a.data('label') + ' 근거 — ' + $tr.data('title'));
    $pfModal.find('.modal-body').html('<p class="text-center text-muted" style="padding:20px;">불러오는 중...</p>');
    $pfModal.modal('show');
    $.get('get_profit_detail.php', { type: $a.data('type'), pk: $tr.attr('data-pk'), dt: $tr.attr('data-dt') })
      .done(function(html){ $pfModal.find('.modal-body').html(html); })
      .fail(function(xhr){ $pfModal.find('.modal-body').html('<p class="text-danger">근거 내역을 불러오지 못했습니다. (' + xhr.status + ')</p>'); });
  });

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
