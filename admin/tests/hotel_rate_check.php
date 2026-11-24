<?php
// 호텔 기간별 요금 계산 검증 — CLI 전용. 실행: php admin/tests/hotel_rate_check.php
// 가짜 호텔(ZZT*)·가짜 배정(ZZTEST*)을 트랜잭션 안에서 넣고 계산을 확인한 뒤 ROLLBACK → DB에 흔적 없음.
// 검증 대상은 hotel_cal2 자동채움 / 정산목록 일괄 자동정산 / 호텔배정 연동이 모두 쓰는 getHotelRate · buildHotelAutoSettle · saveHotelAutoSettle.
if (PHP_SAPI !== 'cli') { header('HTTP/1.1 404 Not Found'); exit; }
chdir(__DIR__.'/../include');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
include_once 'dbconn.php';
include_once 'func_list.php';
include_once 'dong_func.php';
if (!hotelRateReady()) exit("hotel_rate 테이블이 없거나 HOTEL_RATE_AUTO=false\n");

// begin_transaction() 이 아니라 autocommit(false) — saveHotelAutoSettle 이 호출측 트랜잭션으로 보고 SAVEPOINT 만 쓰게 (커밋 방지)
$dbConn->autocommit(false);
$fail = 0;
function ck($label, $got, $want) {
    global $fail;
    $ok = ((string)$got === (string)$want);
    if (!$ok) $fail++;
    printf("  %-4s %-58s 기대 %-10s 결과 %s\n", $ok ? 'OK' : 'FAIL', $label, var_export($want, true), var_export($got, true));
}
function rate($h, $item, $s, $e, $r, $season) {
    global $dbConn;
    $dbConn->query("INSERT INTO hotel_rate (h_code, item_code, season_name, start_date, end_date, rate, memo, userid, wdate)
                    VALUES ('$h', '$item', '$season', '$s', '$e', $r, 'check', 'cli', now())");
}
function assign($g, $h, $stDate, $day, $pcnt, $stDateSub = '0000-00-00') {
    global $dbConn;
    $dbConn->query("INSERT INTO hotel_assign (grand_eCode, sub_eCode, hotel_code, stDate, day, stDate_sub, pcnt, wdate)
                    VALUES ('$g', 'ZZTEST-S', '$h', '$stDate', $day, '$stDateSub', $pcnt, now())");
}
function dayRates($b) {
    $o = array();
    foreach ($b['days'] as $d) $o[] = $d['stay_date'].'='.($d['rate'] === '' ? '없음' : $d['rate']);
    return implode(' ', $o);
}

// ── 요금표 ──
rate('ZZT001', 'ROOM', '2026-01-01', '2026-12-31', 100, '연중2026');
rate('ZZT001', 'ROOM', '2026-07-01', '2026-08-31', 150, '성수기');
rate('ZZT001', 'ROOM', '2026-08-15', '2026-08-15', 200, '특정일');
rate('ZZT001', 'ROOM', '2027-01-01', '2027-12-31', 110, '연중2027');
rate('ZZT001', 'ROOM', '2026-10-01', '2026-10-10', 120, '행사A(먼저 등록)');
rate('ZZT001', 'ROOM', '2026-10-01', '2026-10-10', 125, '행사B(나중 등록)');
rate('ZZT001', '1500', '2026-01-01', '2026-12-31', 20, '조식 연중');
rate('ZZT001', '1500', '2026-07-01', '2026-08-31', 25, '조식 성수기');
rate('ZZT002', 'ROOM', '2026-06-01', '2026-06-30', 90, '6월만');

echo "1) 날짜별 요금 조회 (getHotelRate)\n";
$cases = array(
    array('2026-06-30', 100, '성수기 시작 전날 → 연중'),
    array('2026-07-01', 150, '성수기 시작일 당일 포함'),
    array('2026-08-14', 150, '성수기 중간'),
    array('2026-08-15', 200, '특정일이 성수기보다 우선'),
    array('2026-08-31', 150, '성수기 종료일 당일 포함'),
    array('2026-09-01', 100, '성수기 끝난 다음날 → 연중'),
    array('2026-10-05', 125, '같은 길이 기간 겹침 → 나중 등록 우선'),
    array('2026-12-31', 100, '연말'),
    array('2027-01-01', 110, '해 바뀜 → 2027 요금'),
);
foreach ($cases as $c) {
    $r = getHotelRate('ZZT001', 'ROOM', $c[0]);
    ck("ZZT001 {$c[0]} {$c[2]}", $r ? (float)$r['rate'] : null, (float)$c[1]);
}
$r = getHotelRate('ZZT001', 'ROOM', '2028-01-01');
ck('ZZT001 2028-01-01 등록 기간 밖 → 없음', $r, null);
$r = getHotelRate('ZZT001', '1500', '2026-08-31');
ck('ZZT001 조식 2026-08-31 → 조식 성수기', $r ? (float)$r['rate'] : null, 25.0);
$r = getHotelRate('ZZT001', '1500', '2026-09-01');
ck('ZZT001 조식 2026-09-01 → 조식 연중', $r ? (float)$r['rate'] : null, 20.0);

echo "\n2) 여러 박 행사 — 숙박일마다 다른 기간 요금 (buildHotelAutoSettle)\n";
// 8/30 출발 3박, 2실: 8/30·8/31 성수기 150, 9/1 연중 100 / 조식은 같은 날짜 기준 25·25·20
assign('ZZTEST-E1', 'ZZT001', '2026-08-30', 1, 2);
assign('ZZTEST-E1', 'ZZT001', '2026-08-30', 2, 2);
assign('ZZTEST-E1', 'ZZT001', '2026-08-30', 3, 2);
$specs = array(array('h_code' => '', 'etc_code' => '1500', 'rate' => '', 'cnt' => ''));
$b = buildHotelAutoSettle('ZZTEST-E1', 'ZZTEST-S', $specs);
ck('E1 숙박일별 요금', dayRates($b), '2026-08-30=150 2026-08-31=150 2026-09-01=100');
ck('E1 객실금액 (150×2+150×2+100×2)', $b['room_amt'], 800);
ck('E1 조식금액 (25×4+20×2)', $b['etc_amt'], 140);
ck('E1 합계', $b['total'], 940);
ck('E1 요금 미등록 수', count($b['missing']), 0);

// 12/30 출발 3박: 연말 100·100, 1/1 은 2027 요금 110
assign('ZZTEST-E2', 'ZZT001', '2026-12-30', 1, 1);
assign('ZZTEST-E2', 'ZZT001', '2026-12-30', 2, 1);
assign('ZZTEST-E2', 'ZZT001', '2026-12-30', 3, 1);
$b = buildHotelAutoSettle('ZZTEST-E2', 'ZZTEST-S');
ck('E2 해 바뀜 숙박일별 요금', dayRates($b), '2026-12-30=100 2026-12-31=100 2027-01-01=110');
ck('E2 객실금액', $b['room_amt'], 310);

// 8/14 출발 3박: 특정일(8/15)만 200
assign('ZZTEST-E3', 'ZZT001', '2026-08-14', 1, 1);
assign('ZZTEST-E3', 'ZZT001', '2026-08-14', 2, 1);
assign('ZZTEST-E3', 'ZZT001', '2026-08-14', 3, 1);
$b = buildHotelAutoSettle('ZZTEST-E3', 'ZZTEST-S');
ck('E3 특정일 낀 숙박일별 요금', dayRates($b), '2026-08-14=150 2026-08-15=200 2026-08-16=150');
ck('E3 객실금액', $b['room_amt'], 500);

// 배정의 stDate_sub 는 저장 시 순서가 어긋난 값이 많으므로 무시하고 출발일+일차로 계산 (실데이터: 7/25 출발 3일차가 7/26 으로 저장됨)
assign('ZZTEST-E4', 'ZZT001', '2026-07-25', 3, 1, '2026-07-26');
$b = buildHotelAutoSettle('ZZTEST-E4', 'ZZTEST-S');
ck('E4 잘못된 stDate_sub(7/26) 무시 → 7/27', dayRates($b), '2026-07-27=150');
// 일차가 없는 행만 stDate_sub 사용
assign('ZZTEST-E6', 'ZZT001', '2026-03-01', 0, 1, '2026-08-15');
$b = buildHotelAutoSettle('ZZTEST-E6', 'ZZTEST-S');
ck('E6 일차 없음 → stDate_sub(8/15) 사용', dayRates($b), '2026-08-15=200');

// 요금 기간이 끊긴 날 → 요금 미등록으로 잡혀야 함 (일괄 자동정산은 건너뜀)
assign('ZZTEST-E5', 'ZZT002', '2026-06-30', 1, 1);
assign('ZZTEST-E5', 'ZZT002', '2026-06-30', 2, 1);
$b = buildHotelAutoSettle('ZZTEST-E5', 'ZZTEST-S');
ck('E5 6/30 있음, 7/1 기간 밖', dayRates($b), '2026-06-30=90 2026-07-01=없음');
ck('E5 요금 미등록 목록', implode(',', $b['missing']), 'ZZT002 2026-07-01');

echo "\n3) 저장 금액 (saveHotelAutoSettle → hotel_settle / hotel_settleetc / hotel_settlesum)\n";
$b = buildHotelAutoSettle('ZZTEST-E1', 'ZZTEST-S', $specs);
$total = saveHotelAutoSettle('ZZTEST-E1', 'ZZTEST-S', $b, 'check', true);
$s1 = $dbConn->query("SELECT GROUP_CONCAT(CONCAT(rate_rm,'x',room_cnt,'=',hotel_amt) ORDER BY seq_no) v, SUM(hotel_amt) t FROM hotel_settle WHERE grand_eCode='ZZTEST-E1'")->fetch_assoc();
$s2 = $dbConn->query("SELECT GROUP_CONCAT(CONCAT(rate,'x',cnt,'=',etc_amt) ORDER BY seq_no) v FROM hotel_settleetc WHERE grand_eCode='ZZTEST-E1'")->fetch_assoc();
$s3 = $dbConn->query("SELECT real_amt, status FROM hotel_settlesum WHERE grand_eCode='ZZTEST-E1'")->fetch_assoc();
ck('E1 저장된 객실행', $s1['v'], '150x2=300.00,150x2=300.00,100x2=200.00');
ck('E1 저장된 조식행', $s2['v'], '25x4=100.00,20x2=40.00');
ck('E1 저장된 합계 / 상태', $s3['real_amt'].' '.$s3['status'], '940.00 AUTO');
ck('E1 반환 합계', $total, 940);

echo "\n4) 저장 실패·담당자 저장분 — 기존 정산 보존\n";
$snap = function () use ($dbConn) {
    return implode('|', $dbConn->query("SELECT
        (SELECT GROUP_CONCAT(CONCAT(seq_no,':',hotel_amt) ORDER BY seq_no) FROM hotel_settle WHERE grand_eCode='ZZTEST-E1') a,
        (SELECT GROUP_CONCAT(CONCAT(seq_no,':',etc_amt) ORDER BY seq_no) FROM hotel_settleetc WHERE grand_eCode='ZZTEST-E1') b,
        (SELECT GROUP_CONCAT(CONCAT(seq_no,':',real_amt,':',status) ORDER BY seq_no) FROM hotel_settlesum WHERE grand_eCode='ZZTEST-E1') c")->fetch_assoc());
};
$before = $snap();
$bad = $b;
$bad['days'][] = array('h_code' => 'ZZT001', 'rate' => "1', 'x", 'room_cnt' => 1, 'amt' => 1);   // 마지막 객실 INSERT 만 실패
ck('E1 중간 INSERT 실패 → false', var_export(saveHotelAutoSettle('ZZTEST-E1', 'ZZTEST-S', $bad, 'fail', true), true), 'false');
ck('E1 실패 후 객실·기타비용·합계 그대로', $snap(), $before);
$dbConn->query("UPDATE hotel_settlesum SET status='DONE' WHERE grand_eCode='ZZTEST-E1'");
$before = $snap();
ck('E1 정산확인(DONE) 후 자동정산 → false', var_export(saveHotelAutoSettle('ZZTEST-E1', 'ZZTEST-S', $b, 'done', true), true), 'false');
ck('E1 확정 자료 그대로', $snap(), $before);

$dbConn->rollback();
$left = $dbConn->query("SELECT (SELECT COUNT(*) FROM hotel_rate WHERE memo='check' AND userid='cli') + (SELECT COUNT(*) FROM hotel_assign WHERE grand_eCode LIKE 'ZZTEST%') + (SELECT COUNT(*) FROM hotel_settlesum WHERE grand_eCode LIKE 'ZZTEST%') c")->fetch_assoc();
echo "\n테스트 데이터 롤백 후 남은 행: {$left['c']}\n";
echo $fail ? "\n실패 {$fail}건\n" : "\n전부 통과\n";
exit($fail ? 1 : 0);
