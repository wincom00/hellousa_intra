<?php

require_once __DIR__ . '/include/arap_common.php';

$txId = isset($_POST['tx_id']) ? (int)$_POST['tx_id'] : 0;
$month = isset($_POST['month']) ? trim((string)$_POST['month']) : '';
$filterType = isset($_POST['filter_tx_type']) ? trim((string)$_POST['filter_tx_type']) : '';
$filterCatId = isset($_POST['filter_cat_id']) ? (int)$_POST['filter_cat_id'] : 0;
$filterSubId = isset($_POST['filter_sub_id']) ? (int)$_POST['filter_sub_id'] : 0;
$filterMethodId = isset($_POST['filter_method_id']) ? (int)$_POST['filter_method_id'] : 0;
$filterStatus = isset($_POST['filter_status']) ? trim((string)$_POST['filter_status']) : '';
$keyword = isset($_POST['keyword']) ? trim((string)$_POST['keyword']) : '';

$redirectParams = array(
    'division' => $division,
    'pdx' => $pdx,
    'sub' => $sub,
    'month' => $month,
    'tx_type' => $filterType,
    'cat_id' => $filterCatId,
    'sub_id' => $filterSubId,
    'method_id' => $filterMethodId,
    'status' => $filterStatus,
    'keyword' => $keyword
);

if ($txId <= 0) {
    arapRedirect(arapPageUrl('arap_list.php', $redirectParams), '삭제할 거래를 찾지 못했습니다.');
}

$lockStmt = $dbConn->prepare("SELECT status FROM arap_transaction WHERE tx_id = ? LIMIT 1");
$lockStmt->bind_param('i', $txId);
$lockStmt->execute();
$lockResult = $lockStmt->get_result();
$lockRow = $lockResult ? $lockResult->fetch_assoc() : null;
$lockStmt->close();

if (!$lockRow) {
    arapRedirect(arapPageUrl('arap_list.php', $redirectParams), '삭제할 거래를 찾지 못했습니다.');
}

$stmt = $dbConn->prepare("DELETE FROM arap_transaction WHERE tx_id = ?");
$stmt->bind_param('i', $txId);
$isSuccess = $stmt->execute();
$stmt->close();

arapRedirect(
    arapPageUrl('arap_list.php', $redirectParams),
    $isSuccess ? '거래내역이 삭제되었습니다.' : '거래내역 삭제 중 오류가 발생했습니다.'
);

