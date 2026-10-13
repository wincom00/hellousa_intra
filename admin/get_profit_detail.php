<?php
/* ============================================================
 * 손익 리포트 - 금액 근거 팝업 (get_profit_detail.php)
 * 금액 칸 클릭 → 모달 본문에 HTML 조각으로 표시
 * - 행사별 탭(profit_list.php)      : gc = grand_eCode(행사코드)
 * - 복합상품 탭(profit_pkg_list.php): pk = 복합상품 코드(MAIN p_code), dt = 출발일(MAIN stDate)
 * - type: sales(매출) | hotel(호텔원가) | car(차량원가) | guide(가이드경비) | income(행사수입)
 * ※ 계산 규칙은 각 리스트 쿼리와 같게 유지할 것 (합계 = 리스트 금액)
 * ============================================================ */
include("include/inc_base.php");

if (empty($_COOKIE['MEMLOGIN_ADMIN_HELLO'])) { echo '<p class="text-danger">로그인이 필요합니다.</p>'; exit; }

$pfd_type = isset($_GET['type']) ? $_GET['type'] : '';
$pfd_gc   = isset($_GET['gc']) ? $dbConn->real_escape_string($_GET['gc']) : '';
$pfd_pk   = isset($_GET['pk']) ? $dbConn->real_escape_string($_GET['pk']) : '';
$pfd_dt   = isset($_GET['dt']) ? $dbConn->real_escape_string($_GET['dt']) : '';
if ($pfd_gc == '' && ($pfd_pk == '' || $pfd_dt == '')) { echo '<p class="text-danger">행사코드가 없습니다.</p>'; exit; }

function pfd_h($v) { return htmlspecialchars((string)$v); }
function pfd_m($v) { return number_format((float)$v, 2); }
function pfd_rows($qry) {
    global $dbConn;
    $out = array();
    $rst = $dbConn->query($qry);
    if ($rst) while ($row = $rst->fetch_assoc()) $out[] = $row;
    return $out;
}
/* 근거 표 출력
 * $blocks = [ ['title' => 블록 제목(HTML), 'items' => [[구분, 내용, 단가, 수량, 금액, 적용(true/라벨)], ...]], ... ] */
function pfd_render($head, $blocks, $total, $note) {
    echo '<table class="table table-bordered table-condensed" style="margin-bottom:8px;"><thead><tr>';
    foreach ($head as $h) echo '<th>'.$h.'</th>';
    echo '</tr></thead><tbody>';
    foreach ($blocks as $b) {
        if ($b['title'] != '') echo '<tr class="active"><td colspan="6"><b>'.$b['title'].'</b></td></tr>';
        if (count($b['items']) == 0) echo '<tr><td colspan="6" class="text-muted">입력된 내역 없음</td></tr>';
        foreach ($b['items'] as $it) {
            $used = ($it[5] === true);
            echo '<tr'.($used ? '' : ' style="color:#aaa;"').'>'
               . '<td>'.pfd_h($it[0]).'</td><td>'.pfd_h($it[1]).'</td>'
               . '<td class="text-right">'.($it[2] === '' ? '' : pfd_m($it[2])).'</td>'
               . '<td class="text-right">'.pfd_h($it[3]).'</td>'
               . '<td class="text-right">'.pfd_m($it[4]).'</td>'
               . '<td>'.($used ? '<span class="label label-success">반영</span>' : pfd_h($it[5])).'</td></tr>';
        }
    }
    echo '</tbody><tfoot><tr><th colspan="4" class="text-right">합계 (반영분)</th>'
       . '<th class="text-right">'.pfd_m($total).'</th><th></th></tr></tfoot></table>';
    echo '<div style="font-size:12px;color:#777;">'.$note.'</div>';
}

/* ================= 복합상품 탭: 복합상품(pk) × 출발일(dt) =================
 * 매출 = 확정(DONE) MAIN 예약 총액 합
 * 원가/행사수입 = 구성 행사 차량(grand_eCode, sub_eCode) 금액 × 이 상품 인원 / 차량 전체 인원 (profit_pkg_list.php 와 동일) */
if ($pfd_pk != '') {
    if ($pfd_type == 'sales') {
        $items = array(); $total = 0;
        foreach (pfd_rows("SELECT r.reserveCode, r.book_pri, r.p_cnt, r.last_total,
                                  (SELECT COUNT(*) FROM tour_car tc WHERE tc.reserveCode = r.reserveCode) AS car_rows
                           FROM reserve_info r
                           WHERE r.parent = 'MAIN' AND r.rev_status = 'DONE' AND r.p_code = '$pfd_pk' AND r.stDate = '$pfd_dt'
                           ORDER BY r.reserveCode") as $r) {
            $items[] = array('확정 예약', $r['reserveCode'].($r['book_pri'] != '' ? ' · '.$r['book_pri'] : '')
                             .((int)$r['car_rows'] == 0 ? ' · 차량 미배정(원가 없음)' : ''),
                             '', $r['p_cnt'], $r['last_total'], true);
            $total += (float)$r['last_total'];
        }
        pfd_render(array('구분', '예약번호 · 예약자', '', '인원', '예약총액', '적용'),
            array(array('title' => '', 'items' => $items)), $total,
            '· 이 복합상품·출발일의 확정(DONE) 예약 총액(MAIN last_total)을 합산합니다.');
        exit;
    }

    if ($pfd_type == 'hotel' || $pfd_type == 'car') {
        // 차량별 실제(settlesum DONE) 우선, 없으면 예상(정산입력 + 기타비용)
        $t   = $pfd_type;
        $col = ($t == 'hotel') ? 'hotel_amt' : 'bus_amt';
        $costCols = "COALESCE(s.r, COALESCE(b.a, 0) + COALESCE(e.a, 0)) AS amt,
                     CASE WHEN s.r IS NOT NULL THEN '실제'
                          WHEN COALESCE(b.a, 0) + COALESCE(e.a, 0) <> 0 THEN '예상' ELSE '입력 없음' END AS kind";
        $costJoin = "LEFT JOIN (SELECT grand_eCode, sub_eCode, SUM($col) AS a FROM {$t}_settle GROUP BY grand_eCode, sub_eCode) b
                            ON b.grand_eCode = k.grand_eCode AND b.sub_eCode = k.sub_eCode
                     LEFT JOIN (SELECT grand_eCode, sub_eCode, SUM(etc_amt) AS a FROM {$t}_settleetc GROUP BY grand_eCode, sub_eCode) e
                            ON e.grand_eCode = k.grand_eCode AND e.sub_eCode = k.sub_eCode
                     LEFT JOIN (SELECT grand_eCode, sub_eCode, SUM(real_amt) AS r FROM {$t}_settlesum WHERE status = 'DONE' GROUP BY grand_eCode, sub_eCode) s
                            ON s.grand_eCode = k.grand_eCode AND s.sub_eCode = k.sub_eCode";
        $note = '· 차량별로 정산완료 금액(실제)이 있으면 그 금액, 없으면 정산입력 금액(예상)을 행사 금액으로 씁니다.<br>';
    } else if ($pfd_type == 'guide' || $pfd_type == 'income') {
        // 차량별 대표 가이드정산 1건: COMPLETE > DONE > 기타, 동순위는 최신 seq_no
        $costCols = ($pfd_type == 'guide'
                        ? "COALESCE(gm.t, 0) + COALESCE(ga.t, 0) + COALESCE(ge.t, 0) AS amt,"
                        : "COALESCE(gs.t, 0) + COALESCE(go.t, 0) AS amt,")
                  . " COALESCE(CONCAT('정산 ', s.settle_code), '가이드정산 없음') AS kind";
        $costJoin = "LEFT JOIN (SELECT grand_eCode, sub_eCode, settle_code,
                                       ROW_NUMBER() OVER (PARTITION BY grand_eCode, sub_eCode
                                                          ORDER BY CASE reg_status WHEN 'COMPLETE' THEN 2 WHEN 'DONE' THEN 1 ELSE 0 END DESC,
                                                                   seq_no DESC) AS rn
                                FROM guide_setmaster WHERE settle_code IS NOT NULL AND settle_code <> '') s
                            ON s.grand_eCode = k.grand_eCode AND s.sub_eCode = k.sub_eCode AND s.rn = 1
                     LEFT JOIN (SELECT settle_code, SUM(meal_pricetotal) AS t FROM guide_meal GROUP BY settle_code) gm ON gm.settle_code = s.settle_code
                     LEFT JOIN (SELECT settle_code, SUM(e_pricetot) AS t FROM guide_admission GROUP BY settle_code) ga ON ga.settle_code = s.settle_code
                     LEFT JOIN (SELECT settle_code, SUM(etc_amt) AS t FROM guide_etcamt GROUP BY settle_code) ge ON ge.settle_code = s.settle_code
                     LEFT JOIN (SELECT settle_code, SUM(c_profit) AS t FROM guide_shopping GROUP BY settle_code) gs ON gs.settle_code = s.settle_code
                     LEFT JOIN (SELECT settle_code, SUM(o_cprofit) AS t FROM guide_option GROUP BY settle_code) go ON go.settle_code = s.settle_code";
        $note = ($pfd_type == 'guide' ? '· 행사 금액 = 식대 + 입장료 + 기타비용' : '· 행사 금액 = 쇼핑 회사수익 + 옵션 회사수익')
              . ' (차량별 대표 가이드정산 1건 기준).<br>';
    } else {
        echo '<p class="text-danger">알 수 없는 항목입니다.</p>';
        exit;
    }

    $rows = pfd_rows("
        WITH m AS (SELECT reserveCode, p_code, stDate FROM reserve_info WHERE parent = 'MAIN' AND rev_status = 'DONE'),
             tcm AS (SELECT tc.reserveCode, tc.grand_eCode, tc.sub_eCode, COUNT(*) AS pax
                     FROM tour_car tc INNER JOIN m ON m.reserveCode = tc.reserveCode
                     GROUP BY tc.reserveCode, tc.grand_eCode, tc.sub_eCode),
             /* 이 복합상품·출발일 예약이 탄 차량별 인원 */
             k AS (SELECT t.grand_eCode, t.sub_eCode, SUM(t.pax) AS pax
                   FROM tcm t INNER JOIN m ON m.reserveCode = t.reserveCode
                   WHERE m.p_code = '$pfd_pk' AND m.stDate = '$pfd_dt'
                   GROUP BY t.grand_eCode, t.sub_eCode),
             ep AS (SELECT grand_eCode, sub_eCode, SUM(pax) AS ev_pax FROM tcm GROUP BY grand_eCode, sub_eCode)
        SELECT k.grand_eCode, k.sub_eCode, k.pax, ep.ev_pax, ev.ev_date, ev.p_name, $costCols
        FROM k
        INNER JOIN ep ON ep.grand_eCode = k.grand_eCode AND ep.sub_eCode = k.sub_eCode
        LEFT JOIN (SELECT tc.grand_eCode, MIN(tc.stDate) AS ev_date, MAX(COALESCE(pm.p_name, tc.p_code)) AS p_name
                   FROM tour_car tc LEFT JOIN product_master pm ON pm.p_code = tc.p_code
                   WHERE tc.grand_eCode IN (SELECT grand_eCode FROM k)
                   GROUP BY tc.grand_eCode) ev ON ev.grand_eCode = k.grand_eCode
        $costJoin
        ORDER BY ev.ev_date, k.grand_eCode, k.sub_eCode");

    $items = array(); $total = 0;
    foreach ($rows as $r) {
        $alloc = (float)$r['amt'] * (int)$r['pax'] / (int)$r['ev_pax'];
        $items[] = array($r['ev_date'], $r['p_name'].' · '.$r['grand_eCode'].' / '.$r['sub_eCode'].' · '.$r['kind'],
                         $r['amt'], (int)$r['pax'].' / '.(int)$r['ev_pax'].'명', $alloc, true);
        $total += $alloc;
    }
    pfd_render(array('행사일', '상품 · 행사코드 / 차량 · 구분', '행사 금액', '인원(이 상품/전체)', '배분 금액', '적용'),
        array(array('title' => '', 'items' => $items)), $total,
        $note
      . '· 배분 금액 = 행사 금액 × 이 상품 인원 / 차량 전체 인원 (확정 예약, tour_car 1행 = 1명).<br>'
      . '· 행사 금액의 세부 내역은 <b>행사별 탭</b>에서 해당 행사 금액을 클릭하면 볼 수 있습니다.');
    exit;
}

/* 행사의 차량 목록 (리스트 쿼리의 k 와 동일) */
$pfd_subs = array();
foreach (pfd_rows("SELECT DISTINCT sub_eCode FROM tour_car WHERE grand_eCode = '$pfd_gc' ORDER BY sub_eCode") as $r) $pfd_subs[] = $r['sub_eCode'];

/* code_base 이름: 'G01|14' 형식 (lvcode1|lvcode2) */
$pfd_codeJoin = "(SELECT CONCAT(lvcode1, '|', lvcode2) AS code, MAX(comment) AS name
                  FROM code_base WHERE lvcode3 = '00' GROUP BY lvcode1, lvcode2)";

/* ============================ 매출 ============================ */
if ($pfd_type == 'sales') {
    // 예약 전액(last_total)은 그 예약의 '첫날 행사'(가장 이른 배정일, 같으면 행사코드 순)에만 계상
    $rows = pfd_rows("
        SELECT b.reserveCode, m.pk_name, m.book_pri, m.p_cnt, m.rev_status,
               z.last_total, z.rn, z.first_gc, z.first_date
        FROM (SELECT DISTINCT reserveCode FROM tour_car WHERE grand_eCode = '$pfd_gc') b
        LEFT JOIN (
            SELECT re.reserveCode, re.grand_eCode, re.last_total,
                   ROW_NUMBER()  OVER w AS rn,
                   FIRST_VALUE(re.grand_eCode) OVER w AS first_gc,
                   FIRST_VALUE(re.ev_date)     OVER w AS first_date
            FROM (
                SELECT tc.reserveCode, tc.grand_eCode,
                       MIN(tc.stDate) AS ev_date,
                       MAX(ri.last_total) AS last_total
                FROM tour_car tc
                INNER JOIN reserve_info ri
                    ON ri.reserveCode = tc.reserveCode AND ri.p_code = tc.p_code
                   AND ri.rev_status = 'DONE'
                WHERE tc.reserveCode IN (SELECT reserveCode FROM tour_car WHERE grand_eCode = '$pfd_gc')
                GROUP BY tc.reserveCode, tc.grand_eCode
            ) re
            WINDOW w AS (PARTITION BY re.reserveCode ORDER BY re.ev_date ASC, re.grand_eCode ASC)
        ) z ON z.reserveCode = b.reserveCode AND z.grand_eCode = '$pfd_gc'
        LEFT JOIN (
            SELECT reserveCode, MAX(p_name) AS pk_name, MAX(book_pri) AS book_pri,
                   MAX(p_cnt) AS p_cnt, MAX(rev_status) AS rev_status
            FROM reserve_info WHERE parent = 'MAIN' GROUP BY reserveCode
        ) m ON m.reserveCode = b.reserveCode
        ORDER BY (z.rn = 1) DESC, b.reserveCode");

    $items = array(); $total = 0;
    foreach ($rows as $r) {
        $desc = $r['reserveCode'].' · '.$r['pk_name'].($r['book_pri'] != '' ? ' · '.$r['book_pri'] : '');
        if ($r['rn'] === null) {
            $items[] = array('제외', $desc, '', $r['p_cnt'], 0, '확정(DONE) 예약 아님 ('.$r['rev_status'].')');
        } else if ((int)$r['rn'] == 1) {
            $items[] = array('이 행사에 계상', $desc, '', $r['p_cnt'], $r['last_total'], true);
            $total += (float)$r['last_total'];
        } else {
            $items[] = array('다른 행사에 계상', $desc, '', $r['p_cnt'], $r['last_total'],
                             '첫날 행사 '.$r['first_date'].' '.$r['first_gc'].'에 계상');
        }
    }
    pfd_render(array('구분', '예약번호 · 패키지 · 예약자', '', '인원', '예약총액', '적용'),
        array(array('title' => '', 'items' => $items)), $total,
        '· 예약 총액(reserve_info.last_total, 확정 DONE)은 그 예약이 배정된 행사 중 <b>첫날 행사</b>에 한 번만 매출로 잡습니다.<br>'
      . '· 다일정 패키지는 첫날 행사에 매출이 몰리고, 이후 날짜 행사는 원가만 잡힙니다.');
    exit;
}

/* ======================== 호텔 / 차량 원가 ======================== */
if ($pfd_type == 'hotel' || $pfd_type == 'car') {
    $isHotel = ($pfd_type == 'hotel');
    if ($isHotel) {
        $base = pfd_rows("SELECT s.sub_eCode, COALESCE(p.h_name, s.h_code) AS name, s.rate_rm AS rate, s.room_cnt AS cnt, s.hotel_amt AS amt
                          FROM hotel_settle s
                          LEFT JOIN (SELECT h_code, MAX(h_name) AS h_name FROM product_hotel GROUP BY h_code) p ON p.h_code = s.h_code
                          WHERE s.grand_eCode = '$pfd_gc' ORDER BY s.seq_no");
        $etcGroup = 'E01';
    } else {
        $base = pfd_rows("SELECT s.sub_eCode, COALESCE(b.name, s.c_code) AS name, s.rate_rm AS rate, s.bus_cnt AS cnt, s.bus_amt AS amt
                          FROM car_settle s
                          LEFT JOIN (SELECT bus_id, MAX(CONCAT(bus_number, ' (', bus_id, ')')) AS name FROM bus_list GROUP BY bus_id) b ON b.bus_id = s.c_code
                          WHERE s.grand_eCode = '$pfd_gc' ORDER BY s.seq_no");
        $etcGroup = 'E03';
    }
    $etc = pfd_rows("SELECT s.sub_eCode, COALESCE(c.name, s.etc_code) AS name, s.rate, s.cnt, s.etc_amt AS amt
                     FROM {$pfd_type}_settleetc s
                     LEFT JOIN (SELECT CONCAT(lvcode2, lvcode3) AS code, MAX(comment) AS name
                                FROM code_base WHERE lvcode1 = '$etcGroup' AND lvcode2 <> '00'
                                GROUP BY lvcode2, lvcode3) c ON c.code = s.etc_code
                     WHERE s.grand_eCode = '$pfd_gc' ORDER BY s.seq_no");
    $sums = pfd_rows("SELECT sub_eCode, real_amt, memo, status, wdate
                      FROM {$pfd_type}_settlesum WHERE grand_eCode = '$pfd_gc' ORDER BY seq_no");

    $blocks = array(); $total = 0;
    foreach ($pfd_subs as $sub) {
        $real = 0; $hasReal = false; $exp = 0;
        foreach ($sums as $s) if ($s['sub_eCode'] == $sub && $s['status'] == 'DONE') { $hasReal = true; $real += (float)$s['real_amt']; }
        $items = array();
        foreach ($sums as $s) {
            if ($s['sub_eCode'] != $sub) continue;
            $done = ($s['status'] == 'DONE');
            $items[] = array('실제(정산완료)', trim($s['memo'].' '.substr($s['wdate'], 0, 10)), '', '', $s['real_amt'],
                             $done ? true : '상태 '.$s['status'].' - 제외');
        }
        foreach (array(array($isHotel ? '예상-객실' : '예상-버스', $base), array('예상-기타비용', $etc)) as $grp) {
            foreach ($grp[1] as $s) {
                if ($s['sub_eCode'] != $sub || (float)$s['amt'] == 0) continue;
                $exp += (float)$s['amt'];
                $items[] = array($grp[0], $s['name'], $s['rate'] === null ? '' : $s['rate'], $s['cnt'], $s['amt'],
                                 $hasReal ? '실제 금액 우선 - 미반영' : true);
            }
        }
        $applied = $hasReal ? $real : $exp;
        $total += $applied;
        $blocks[] = array('title' => '차량 '.pfd_h($sub).' → '.($hasReal ? '실제' : '예상').' '.pfd_m($applied), 'items' => $items);
    }
    pfd_render(array('구분', '내용', '단가', ($isHotel ? '객실수' : '대수'), '금액', '적용'), $blocks, $total,
        '· 차량(서브코드)별로 정산완료 금액(' . $pfd_type . '_settlesum, 상태 DONE)이 있으면 그 금액을, 없으면 정산입력 금액(단가×수량 + 기타비용)을 반영합니다.<br>'
      . '· 금액 0인 입력줄은 생략했습니다.');
    exit;
}

/* ===================== 가이드경비 / 행사수입 ===================== */
if ($pfd_type == 'guide' || $pfd_type == 'income') {
    // 차량별 대표 정산 1건: COMPLETE > DONE > 기타, 동순위는 최신 seq_no (리스트 쿼리와 동일)
    $sets = pfd_rows("SELECT gsm.sub_eCode, gsm.settle_code, gsm.reg_status,
                             ROW_NUMBER() OVER (
                                 PARTITION BY gsm.sub_eCode
                                 ORDER BY CASE gsm.reg_status WHEN 'COMPLETE' THEN 2 WHEN 'DONE' THEN 1 ELSE 0 END DESC,
                                          gsm.seq_no DESC
                             ) AS rn
                      FROM guide_setmaster gsm
                      WHERE gsm.grand_eCode = '$pfd_gc' AND gsm.settle_code IS NOT NULL AND gsm.settle_code <> ''");
    $mealName = array('bf' => '조식', 'lunch' => '중식', 'dinner' => '석식');

    $blocks = array(); $total = 0;
    foreach ($pfd_subs as $sub) {
        $items = array(); $applied = 0; $used = '';
        foreach ($sets as $st) {
            if ($st['sub_eCode'] != $sub) continue;
            $sc = $dbConn->real_escape_string($st['settle_code']);
            if ((int)$st['rn'] != 1) {
                $items[] = array('중복 정산', $st['settle_code'].' ('.$st['reg_status'].')', '', '', 0, '대표 1건만 반영 - 제외');
                continue;
            }
            $used = $st['settle_code'].' ('.$st['reg_status'].')';
            if ($pfd_type == 'guide') {
                foreach (pfd_rows("SELECT meal_type, meal_date, meal_rest, meal_cnt, meal_price, meal_pricetotal
                                   FROM guide_meal WHERE settle_code = '$sc' AND meal_pricetotal <> 0 ORDER BY seq_no") as $x) {
                    $items[] = array('식대 '.(isset($mealName[$x['meal_type']]) ? $mealName[$x['meal_type']] : $x['meal_type']),
                                     trim($x['meal_date'].' '.$x['meal_rest']), $x['meal_price'], $x['meal_cnt'], $x['meal_pricetotal'], true);
                    $applied += (float)$x['meal_pricetotal'];
                }
                foreach (pfd_rows("SELECT COALESCE(c.name, a.admission_code) AS name, a.e_price, a.e_cnt, a.e_pricetot
                                   FROM guide_admission a LEFT JOIN $pfd_codeJoin c ON c.code = a.admission_code
                                   WHERE a.settle_code = '$sc' AND a.e_pricetot <> 0 ORDER BY a.seq_no") as $x) {
                    $items[] = array('입장료', $x['name'], $x['e_price'], $x['e_cnt'], $x['e_pricetot'], true);
                    $applied += (float)$x['e_pricetot'];
                }
                foreach (pfd_rows("SELECT COALESCE(c.name, e.etc_type) AS name, e.etc_pricety, e.etc_memo, e.etc_amt
                                   FROM guide_etcamt e LEFT JOIN $pfd_codeJoin c ON c.code = e.etc_type
                                   WHERE e.settle_code = '$sc' AND e.etc_amt <> 0 ORDER BY e.seq_no") as $x) {
                    $items[] = array('기타비용('.$x['etc_pricety'].')', trim($x['name'].' '.$x['etc_memo']), '', '', $x['etc_amt'], true);
                    $applied += (float)$x['etc_amt'];
                }
            } else {
                foreach (pfd_rows("SELECT COALESCE(c.name, s.shop_code) AS name, s.tot_amt, s.c_profit
                                   FROM guide_shopping s LEFT JOIN $pfd_codeJoin c ON c.code = s.shop_code
                                   WHERE s.settle_code = '$sc' AND s.c_profit <> 0 ORDER BY s.seq_no") as $x) {
                    $items[] = array('쇼핑 회사수익', $x['name'].' (쇼핑매출 '.pfd_m($x['tot_amt']).')', '', '', $x['c_profit'], true);
                    $applied += (float)$x['c_profit'];
                }
                foreach (pfd_rows("SELECT COALESCE(c.name, o.option_code) AS name, o.o_price, o.o_cnt, o.o_cprofit
                                   FROM guide_option o LEFT JOIN $pfd_codeJoin c ON c.code = o.option_code
                                   WHERE o.settle_code = '$sc' AND o.o_cprofit <> 0 ORDER BY o.seq_no") as $x) {
                    $items[] = array('옵션 회사수익', $x['name'], $x['o_price'], $x['o_cnt'], $x['o_cprofit'], true);
                    $applied += (float)$x['o_cprofit'];
                }
            }
        }
        $total += $applied;
        $blocks[] = array('title' => '차량 '.pfd_h($sub).' → '.($used == '' ? '<span class="text-danger">가이드정산 없음(미정산)</span>' : '정산 '.pfd_h($used)).' '.pfd_m($applied),
                          'items' => $items);
    }
    pfd_render(array('구분', '내용', '단가', '수량', '금액', '적용'), $blocks, $total,
        ($pfd_type == 'guide'
            ? '· 가이드경비 = 식대 + 입장료 + 기타비용(guide/car/etc).<br>'
            : '· 행사수입 = 쇼핑 회사수익 + 옵션 회사수익.<br>')
      . '· 차량별로 정산코드가 있는 가이드정산 중 대표 1건(정산완료 > 등록 > 기타, 같으면 최신)만 반영합니다. 금액 0인 입력줄은 생략했습니다.');
    exit;
}

echo '<p class="text-danger">알 수 없는 항목입니다.</p>';
