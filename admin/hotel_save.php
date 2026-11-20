<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'include/inc_base.php';
header('Content-Type: text/plain; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit('0/잘못된 요청입니다.');
if (empty($_COOKIE['MEMLOGIN_ADMIN_HELLO']) || empty($user_dbinfo['userid']) || !hasMenuAccess(6, 1, 10)) {
    exit('0/호텔정산 권한이 없습니다. 다시 로그인하세요.');
}
if (empty($_SESSION['hotel_settle_csrf']) || !isset($_POST['csrf_token']) || !is_string($_POST['csrf_token'])
    || !hash_equals($_SESSION['hotel_settle_csrf'], $_POST['csrf_token'])) {
    exit('0/화면을 새로고침한 뒤 다시 시도하세요.');
}

function hotelSaveText($value) {
    global $dbConn;
    if (!is_scalar($value) && $value !== null) throw new RuntimeException('입력값을 확인하세요.');
    return $dbConn->real_escape_string((string)$value);
}
function hotelSaveNumber($value) {
    if (!is_scalar($value) || !is_numeric($value) || !is_finite((float)$value)) {
        throw new RuntimeException('단가, 수량, 금액을 숫자로 입력하세요.');
    }
    return hotelSaveText($value);
}
function hotelSaveId($value) {
    if ($value === '') return 0;
    if (!is_scalar($value) || !ctype_digit((string)$value)) throw new RuntimeException('잘못된 정산 번호입니다.');
    return (int)$value;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$hotelSaveStarted = false;
try {
    $mode = isset($_POST['mode']) ? $_POST['mode'] : '';
    if (!in_array($mode, array('save', 'confirm', 'delete_hotel', 'delete_etc'), true)) {
        throw new RuntimeException('잘못된 요청입니다.');
    }
    $deleting = ($mode === 'delete_hotel' || $mode === 'delete_etc');
    $g = hotelSaveText(isset($_POST[$deleting ? 'grandcode' : 'grand_eCode']) ? $_POST[$deleting ? 'grandcode' : 'grand_eCode'] : '');
    $s = hotelSaveText(isset($_POST[$deleting ? 'subcode' : 'sub_eCode']) ? $_POST[$deleting ? 'subcode' : 'sub_eCode'] : '');
    if ($g === '' || $s === '') throw new RuntimeException('행사코드가 없습니다.');
    $scope = "grand_eCode='$g' AND sub_eCode='$s'";
    $sumId = hotelSaveId(isset($_POST['hotel_sumseq']) ? $_POST['hotel_sumseq'] : '');

    $dbConn->begin_transaction();
    $hotelSaveStarted = true;
    $event = $dbConn->query("SELECT seq_no FROM tour_guide WHERE $scope LIMIT 1 FOR UPDATE")->fetch_assoc();
    if (!$event) throw new RuntimeException('행사를 찾을 수 없습니다.');
    $sum = $dbConn->query("SELECT * FROM hotel_settlesum WHERE $scope ORDER BY seq_no DESC LIMIT 1 FOR UPDATE")->fetch_assoc();
    if ($sumId !== ($sum ? (int)$sum['seq_no'] : 0)) {
        throw new RuntimeException('정산 자료가 변경되었습니다. 새로고침 후 다시 확인하세요.');
    }
    if ($mode === 'confirm' && (!$sum || !in_array($sum['status'], array('AUTO', 'WAIT'), true))) {
        throw new RuntimeException('정산대기 건만 확인할 수 있습니다.');
    }

    if ($deleting) {
        $id = hotelSaveId(isset($_POST['seqno']) ? $_POST['seqno'] : '');
        $table = $mode === 'delete_hotel' ? 'hotel_settle' : 'hotel_settleetc';
        $dbConn->query("DELETE FROM $table WHERE seq_no=$id AND $scope");
        if ($dbConn->affected_rows !== 1) throw new RuntimeException('삭제할 정산 자료가 없습니다.');
        $baseRate = hotelSaveText($sum ? $sum['base_rate'] : '');
        $memo = hotelSaveText($sum ? $sum['memo'] : '');
    } else {
        $fields = array('hotelSelect', 'hotelRateRm', 'hotelRm', 'hotelCost', 'hotel_seq',
            'hotelSelect_etc', 'etcCostSelect', 'etcRate', 'etcCount', 'etcAmount', 'etc_seq');
        foreach ($fields as $field) {
            if (!isset($_POST[$field])) $_POST[$field] = array();
            if (!is_array($_POST[$field])) throw new RuntimeException('정산 항목을 확인하세요.');
        }
        foreach ($_POST['hotelSelect'] as $key => $value) {
            $h = explode(':::', hotelSaveText($value))[0];
            $id = hotelSaveId(isset($_POST['hotel_seq'][$key]) ? $_POST['hotel_seq'][$key] : '');
            if ($h === '') throw new RuntimeException('호텔을 선택하세요.');
            $rate = hotelSaveNumber(isset($_POST['hotelRateRm'][$key]) ? $_POST['hotelRateRm'][$key] : '');
            $cnt = hotelSaveNumber(isset($_POST['hotelRm'][$key]) ? $_POST['hotelRm'][$key] : '');
            $amt = hotelSaveNumber(isset($_POST['hotelCost'][$key]) ? $_POST['hotelCost'][$key] : '');
            if ($id > 0) {
                if (!$dbConn->query("SELECT seq_no FROM hotel_settle WHERE seq_no=$id AND $scope FOR UPDATE")->fetch_assoc()) {
                    throw new RuntimeException('호텔 항목이 변경되었습니다. 새로고침 후 다시 시도하세요.');
                }
                $dbConn->query("UPDATE hotel_settle SET h_code='$h', rate_rm='$rate', room_cnt='$cnt', hotel_amt='$amt' WHERE seq_no=$id AND $scope");
            } else {
                $dbConn->query("INSERT INTO hotel_settle (grand_eCode, sub_eCode, h_code, rate_rm, room_cnt, hotel_amt, wdate)
                    VALUES ('$g', '$s', '$h', '$rate', '$cnt', '$amt', now())");
            }
        }
        foreach ($_POST['etcCostSelect'] as $key => $value) {
            $ec = hotelSaveText($value);
            $h = hotelSaveText(isset($_POST['hotelSelect_etc'][$key]) ? $_POST['hotelSelect_etc'][$key] : '');
            $id = hotelSaveId(isset($_POST['etc_seq'][$key]) ? $_POST['etc_seq'][$key] : '');
            $rate = isset($_POST['etcRate'][$key]) ? $_POST['etcRate'][$key] : '';
            $cnt = isset($_POST['etcCount'][$key]) ? $_POST['etcCount'][$key] : '';
            $amt = isset($_POST['etcAmount'][$key]) ? $_POST['etcAmount'][$key] : '';
            // 기존 저장기가 만들던 금액 없는 빈 기타비용 행도 건너뛴다.
            if ($ec === '' && $h === ''
                && ($rate === '' || (is_numeric($rate) && (float)$rate == 0))
                && ($cnt === '' || (is_numeric($cnt) && (float)$cnt == 0))
                && ($amt === '' || (is_numeric($amt) && (float)$amt == 0))) continue;
            if ($ec === '') throw new RuntimeException('기타비용 항목을 선택하세요.');
            $rate = hotelSaveNumber($rate);
            $cnt = hotelSaveNumber($cnt);
            $amt = hotelSaveNumber($amt);
            if ($id > 0) {
                if (!$dbConn->query("SELECT seq_no FROM hotel_settleetc WHERE seq_no=$id AND $scope FOR UPDATE")->fetch_assoc()) {
                    throw new RuntimeException('기타비용 항목이 변경되었습니다. 새로고침 후 다시 시도하세요.');
                }
                $dbConn->query("UPDATE hotel_settleetc SET etc_code='$ec', rate='$rate', cnt='$cnt', etc_amt='$amt', h_code='$h' WHERE seq_no=$id AND $scope");
            } else {
                $dbConn->query("INSERT INTO hotel_settleetc (grand_eCode, sub_eCode, etc_code, rate, cnt, etc_amt, h_code, wdate)
                    VALUES ('$g', '$s', '$ec', '$rate', '$cnt', '$amt', '$h', now())");
            }
        }
        $baseRate = hotelSaveText(isset($_POST['m_rate_h']) ? $_POST['m_rate_h'] : '');
        $memo = hotelSaveText(isset($_POST['memo']) ? $_POST['memo'] : '');
    }

    $total = $dbConn->query("SELECT
        (SELECT IFNULL(SUM(hotel_amt),0) FROM hotel_settle WHERE $scope)
        + (SELECT IFNULL(SUM(etc_amt),0) FROM hotel_settleetc WHERE $scope) AS amt")->fetch_assoc();
    $amount = hotelSaveNumber($total['amt']);
    // 수동 저장(WAIT)은 자동 재계산에서 보호하고, 정산확인을 눌렀을 때만 확정한다.
    $status = $mode === 'confirm' ? 'DONE' : 'WAIT';
    if ($sum) {
        $dbConn->query("UPDATE hotel_settlesum SET base_rate='$baseRate', real_amt='$amount', memo='$memo', status='$status'
            WHERE seq_no=$sumId AND $scope");
    } else {
        $dbConn->query("INSERT INTO hotel_settlesum (grand_eCode, sub_eCode, base_rate, real_amt, memo, status, wdate)
            VALUES ('$g', '$s', '$baseRate', '$amount', '$memo', '$status', now())");
    }
    $dbConn->commit();
    echo '1/';
} catch (Throwable $e) {
    if ($hotelSaveStarted) $dbConn->rollback();
    echo '0/'.($e instanceof mysqli_sql_exception ? '저장에 실패했습니다. 다시 시도하세요.' : $e->getMessage());
}
