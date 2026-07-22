<?php

require_once __DIR__ . '/include/arap_common.php';

$txType = isset($_POST['tx_type']) ? trim((string)$_POST['tx_type']) : '';
$catId = isset($_POST['cat_id']) ? (int)$_POST['cat_id'] : 0;
$subId = isset($_POST['sub_id']) ? (int)$_POST['sub_id'] : 0;
$rawDescription = isset($_POST['raw_description']) ? trim((string)$_POST['raw_description']) : '';
$month = isset($_POST['month']) ? trim((string)$_POST['month']) : '';
$keyword = isset($_POST['keyword']) ? trim((string)$_POST['keyword']) : '';

$redirectParams = array(
    'division' => $division,
    'pdx' => $pdx,
    'sub' => $sub,
    'month' => $month,
    'keyword' => $keyword
);
$redirectUrl = arapPageUrl('arap_target_cards.php', $redirectParams);

if ($txType !== 'IN' && $txType !== 'OUT') {
    arapRedirect($redirectUrl, '거래구분을 확인해 주세요.');
}
if ($catId <= 0) {
    arapRedirect($redirectUrl, '분류 정보를 확인해 주세요.');
}

list($startDate, $endDate) = arapGetMonthRange($month);

$signCondition = $txType === 'IN' ? "t.amount > 0" : "t.amount < 0";

$sql = "DELETE t FROM arap_transaction t
        WHERE t.tx_date BETWEEN ? AND ?
          AND t.tx_type = ?
          AND t.cat_id = ?
          AND (
                (? = 0 AND t.sub_id IS NULL)
                OR t.sub_id = ?
              )
          AND TRIM(COALESCE(t.description, '')) = ?
          AND {$signCondition}";

$stmt = $dbConn->prepare($sql);
if (!$stmt) {
    arapRedirect($redirectUrl, '삭제 쿼리 준비 중 오류가 발생했습니다.');
}
$stmt->bind_param(
    'sssiiis',
    $startDate,
    $endDate,
    $txType,
    $catId,
    $subId,
    $subId,
    $rawDescription
);
$okDel = $stmt->execute();
$affected = $stmt->affected_rows;
$stmt->close();

if (!$okDel) {
    arapRedirect($redirectUrl, '거래 삭제 중 오류가 발생했습니다.');
}
if ($affected <= 0) {
    arapRedirect($redirectUrl, '삭제할 거래가 없습니다.');
}

arapRedirect($redirectUrl, '대상 거래 ' . (int)$affected . '건을 삭제했습니다.');
