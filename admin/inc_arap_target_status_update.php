<?php

require_once __DIR__ . '/include/arap_common.php';

header('Content-Type: application/json; charset=utf-8');

$resp = function ($ok, $message = '', $extra = array()) {
    $payload = array_merge(array('ok' => (bool)$ok, 'message' => (string)$message), (array)$extra);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
};

$txType = isset($_POST['tx_type']) ? trim((string)$_POST['tx_type']) : '';
$catId = isset($_POST['cat_id']) ? (int)$_POST['cat_id'] : 0;
$subId = isset($_POST['sub_id']) ? (int)$_POST['sub_id'] : 0;
$rawDescription = isset($_POST['raw_description']) ? trim((string)$_POST['raw_description']) : '';
$newStatus = isset($_POST['new_status']) ? trim((string)$_POST['new_status']) : '';
$month = isset($_POST['month']) ? trim((string)$_POST['month']) : (isset($_GET['month']) ? trim((string)$_GET['month']) : '');

$statusLabels = array(
    'PENDING' => '대기',
    'COMPLETED' => '완료',
    'CANCELLED' => '취소',
    'HOLD' => '보류'
);

if ($txType !== 'IN' && $txType !== 'OUT') {
    $resp(false, '거래구분을 확인해 주세요.');
}
if ($catId <= 0) {
    $resp(false, '분류 정보를 확인해 주세요.');
}
if (!isset($statusLabels[$newStatus])) {
    $resp(false, '거래상태 값이 올바르지 않습니다.');
}

list($startDate, $endDate) = arapGetMonthRange($month);
$regId = isset($user_dbinfo['userid']) ? $user_dbinfo['userid'] : '';

$sql = "UPDATE arap_transaction t
        SET t.status = ?, t.upd_id = ?, t.upd_dt = NOW()
        WHERE t.tx_date BETWEEN ? AND ?
          AND t.tx_type = ?
          AND t.cat_id = ?
          AND (
                (? = 0 AND t.sub_id IS NULL)
                OR t.sub_id = ?
          )
          AND TRIM(COALESCE(t.description, '')) = ?";

$stmt = $dbConn->prepare($sql);
if (!$stmt) {
    $resp(false, '상태 변경 쿼리 준비 중 오류가 발생했습니다.');
}

$stmt->bind_param(
    'sssssiiis',
    $newStatus,
    $regId,
    $startDate,
    $endDate,
    $txType,
    $catId,
    $subId,
    $subId,
    $rawDescription
);
$ok = $stmt->execute();
$affected = $stmt->affected_rows;
$stmt->close();

if (!$ok) {
    $resp(false, '거래상태 변경 중 오류가 발생했습니다.');
}
if ($affected <= 0) {
    $resp(false, '변경할 거래가 없습니다.');
}

$resp(true, '거래상태를 변경했습니다.', array(
    'affected_count' => (int)$affected,
    'status' => $newStatus,
    'display' => $statusLabels[$newStatus]
));
