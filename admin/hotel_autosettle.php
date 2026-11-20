<?php
// 호텔정산 일괄 자동정산 — hotel_settle.php 에서 AJAX 호출, JSON 응답
//   act=preview : 선택 행사 금액 계산만 (실시간 요약)
//   act=save    : 같은 계산 결과를 저장 (saveHotelAutoSettle — 자동정산분은 교체, 담당자 저장분은 건너뜀, status='AUTO')
// 미리보기와 저장이 같은 함수(buildHotelAutoSettle)를 쓰므로 화면 금액 = 저장 금액.
include "include/inc_base.php";
header('Content-Type: application/json; charset=utf-8');

function ha_out($arr){ echo json_encode($arr); exit; }

if (empty($user_dbinfo['userid']) || !hasMenuAccess(6, 1, 10)) {
    header('HTTP/1.1 403 Forbidden');
    ha_out(array('error' => '권한이 없습니다. 다시 로그인하세요.'));
}
if (!hotelRateReady()) ha_out(array('error' => '호텔요금표가 준비되지 않았습니다.'));

$ha_act = (isset($_POST['act']) && $_POST['act'] == 'save') ? 'save' : 'preview';
$ha_events = (isset($_POST['events']) && is_array($_POST['events'])) ? array_values(array_unique($_POST['events'])) : array();
if (count($ha_events) > 200) ha_out(array('error' => '한 번에 200건까지 처리할 수 있습니다.'));

// act=confirm : 정산대기(AUTO·WAIT) 행사를 저장된 금액 그대로 정산확인(DONE) — hotel_save.php mode=confirm 과 같은 조건
if (isset($_POST['act']) && $_POST['act'] == 'confirm') {
    $ha_done = 0; $ha_skip = array();
    foreach ($ha_events as $ev) {
        $pp = explode('|', $ev, 2);
        if (count($pp) != 2 || $pp[0] == '' || $pp[1] == '') continue;
        $g = $dbConn->real_escape_string($pp[0]);
        $s = $dbConn->real_escape_string($pp[1]);
        $sum = $dbConn->query("SELECT seq_no, status FROM hotel_settlesum WHERE grand_eCode='$g' AND sub_eCode='$s' ORDER BY seq_no DESC LIMIT 1")->fetch_assoc();
        if ($sum && in_array($sum['status'], array('AUTO', 'WAIT'), true)
            && $dbConn->query("UPDATE hotel_settlesum SET status='DONE' WHERE seq_no=".(int)$sum['seq_no']." AND status IN ('AUTO','WAIT')")
            && $dbConn->affected_rows == 1) {
            $ha_done++;
        } else {
            $ha_skip[] = $pp[0].' / '.$pp[1];
        }
    }
    ha_out(array('confirmed' => $ha_done, 'skipped' => $ha_skip));
}

// 일괄등록 패널의 기타비용 지정 행
$ha_specs = array();
$p_eh = isset($_POST['etc_hotel']) ? (array)$_POST['etc_hotel'] : array();
$p_ec = isset($_POST['etc_code']) ? (array)$_POST['etc_code'] : array();
$p_er = isset($_POST['etc_rate']) ? (array)$_POST['etc_rate'] : array();
$p_en = isset($_POST['etc_cnt']) ? (array)$_POST['etc_cnt'] : array();
foreach ($p_ec as $i => $code) {
    $code = trim($code);
    if ($code == '') continue;
    $rate = isset($p_er[$i]) ? str_replace(',', '', trim($p_er[$i])) : '';
    $cnt  = isset($p_en[$i]) ? trim($p_en[$i]) : '';
    if (($rate !== '' && (!is_numeric($rate) || $rate < 0)) || ($cnt !== '' && (!is_numeric($cnt) || $cnt < 0))) {
        ha_out(array('error' => '기타비용 RATE/수량은 0 이상 숫자로 입력하세요.'));
    }
    $ha_specs[] = array('h_code' => isset($p_eh[$i]) ? trim($p_eh[$i]) : '', 'etc_code' => $code, 'rate' => $rate, 'cnt' => $cnt);
}

// 이름표
$ha_hname = array();
$rst = $dbConn->query("SELECT h_code, h_name FROM product_hotel");
while ($r = $rst->fetch_assoc()) $ha_hname[$r['h_code']] = $r['h_name'];
$ha_ename = array();
$rst = getEtcCostSelect();
while ($r = $rst->fetch_assoc()) $ha_ename[$r['lvcode2'].$r['lvcode3']] = $r['comment'];
function ha_h($c){ global $ha_hname; return isset($ha_hname[$c]) ? $ha_hname[$c] : $c; }
function ha_e($c){ global $ha_ename; return isset($ha_ename[$c]) ? $ha_ename[$c] : $c; }

$out = array('events' => array(), 'hotels' => array(), 'etc_skipped' => array(),
             'room_amt' => 0, 'etc_amt' => 0, 'total' => 0, 'ok_cnt' => 0, 'skip_cnt' => 0, 'saved' => 0);
$ha_now = date('Y-m-d H:i');

foreach ($ha_events as $ev) {
    $pp = explode('|', $ev, 2);
    if (count($pp) != 2 || $pp[0] == '' || $pp[1] == '') continue;
    $g = $pp[0]; $s = $pp[1];

    $row = array('grand_eCode' => $g, 'sub_eCode' => $s, 'ok' => false, 'reason' => '', 'recalc' => false,
                 'rooms' => 0, 'room_amt' => 0, 'etc_amt' => 0, 'total' => 0, 'days' => array(), 'etc' => array());

    // 담당자가 저장한 정산(DONE)은 절대 덮어쓰지 않는다. 자동정산(AUTO)은 다시 계산 (저장 함수 안에서도 같은 확인)
    $state = hotelSettleState($g, $s);
    if ($state == 'DONE') {
        $row['reason'] = '담당자 저장 정산 있음';
    } else {
        $row['recalc'] = ($state == 'AUTO');
        $b = buildHotelAutoSettle($g, $s, $ha_specs);
        foreach ($b['days'] as $d) {
            $d['h_name'] = ha_h($d['h_code']);
            $row['days'][] = $d;
        }
        foreach ($b['etc'] as $e) {
            $e['h_name'] = ha_h($e['h_code']);
            $e['etc_name'] = ha_e($e['etc_code']);
            $row['etc'][] = $e;
        }
        $row['rooms'] = $b['rooms'];
        $row['room_amt'] = $b['room_amt'];
        $row['etc_amt'] = $b['etc_amt'];
        $row['total'] = $b['total'];

        if (count($b['days']) == 0) {
            $row['reason'] = '호텔배정 없음';
        } else if (count($b['missing']) > 0) {
            $miss = array();
            foreach ($b['missing'] as $m) {
                $mp = explode(' ', $m, 2);
                $miss[] = ha_h($mp[0]).' '.$mp[1];
            }
            $row['reason'] = '객실요금 미등록: '.implode(', ', array_unique($miss));
        } else {
            $row['ok'] = true;
        }
        foreach ($b['etc_skipped'] as $k) {
            $out['etc_skipped'][] = $g.'/'.$s.' '.ha_h($k['h_code']).' '.ha_e($k['etc_code']);
        }

        if ($row['ok'] && $ha_act == 'save') {
            if (saveHotelAutoSettle($g, $s, $b, '자동정산 '.$ha_now.' ('.$user_dbinfo['userid'].')', true) === false) {
                $row['ok'] = false;
                $row['reason'] = '저장 실패: '.$dbConn->error;
            } else {
                $out['saved']++;
            }
        }
    }

    if ($row['ok']) {
        $out['ok_cnt']++;
        $out['room_amt'] += $row['room_amt'];
        $out['etc_amt'] += $row['etc_amt'];
        // 호텔별 합계 (저장 대상 행사만)
        foreach ($row['days'] as $d) {
            $k = $d['h_code'];
            if (!isset($out['hotels'][$k])) $out['hotels'][$k] = array('h_code' => $k, 'h_name' => $d['h_name'], 'rooms' => 0, 'room_amt' => 0, 'etc_amt' => 0, 'total' => 0);
            $out['hotels'][$k]['rooms'] += $d['room_cnt'];
            $out['hotels'][$k]['room_amt'] += $d['amt'];
        }
        foreach ($row['etc'] as $e) {
            $k = $e['h_code'];
            if (!isset($out['hotels'][$k])) $out['hotels'][$k] = array('h_code' => $k, 'h_name' => $e['h_name'], 'rooms' => 0, 'room_amt' => 0, 'etc_amt' => 0, 'total' => 0);
            $out['hotels'][$k]['etc_amt'] += $e['amt'];
        }
    } else {
        $out['skip_cnt']++;
    }
    $out['events'][] = $row;
}

foreach ($out['hotels'] as $k => $h) {
    $out['hotels'][$k]['room_amt'] = round($h['room_amt'], 2);
    $out['hotels'][$k]['etc_amt'] = round($h['etc_amt'], 2);
    $out['hotels'][$k]['total'] = round($h['room_amt'] + $h['etc_amt'], 2);
}
$out['hotels'] = array_values($out['hotels']);
$out['room_amt'] = round($out['room_amt'], 2);
$out['etc_amt'] = round($out['etc_amt'], 2);
$out['total'] = round($out['room_amt'] + $out['etc_amt'], 2);
ha_out($out);
