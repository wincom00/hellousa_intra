<?php

require_once __DIR__ . '/include/arap_common.php';

$action = isset($_POST['action']) ? trim((string)$_POST['action']) : '';
$seqNo = isset($_POST['seq_no']) ? (int)$_POST['seq_no'] : 0;
$settleCode = isset($_POST['settle_code']) ? trim((string)$_POST['settle_code']) : '';
$financeDate = isset($_POST['finance_date']) ? trim((string)$_POST['finance_date']) : '';
$amount = isset($_POST['amount']) ? abs((float)$_POST['amount']) : 0;
$description = isset($_POST['description']) ? trim((string)$_POST['description']) : '';
$memo = isset($_POST['memo']) ? trim((string)$_POST['memo']) : '';
$methodId = isset($_POST['method_id']) ? (int)$_POST['method_id'] : 0;
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

if ($seqNo <= 0 || $settleCode === '') {
    arapRedirect($redirectUrl, '가이드 정산 정보를 확인해 주세요.');
}

if ($action !== 'add') {
    arapRedirect($redirectUrl, '알 수 없는 작업입니다.');
}

$regId = isset($user_dbinfo['userid']) ? $user_dbinfo['userid'] : '';
$safeSettleCode = $dbConn->real_escape_string($settleCode);

if ($action === 'add') {
    $catId = 0;
    $catRes = $dbConn->query("SELECT cat_id FROM arap_category WHERE cat_type='OUT' AND cat_name='가이드정산' AND use_yn='Y' ORDER BY cat_id ASC LIMIT 1");
    if ($catRes && ($catRow = $catRes->fetch_assoc())) {
        $catId = (int)$catRow['cat_id'];
    }
    if ($catId <= 0) {
        arapRedirect($redirectUrl, "'가이드정산' 분류를 찾을 수 없습니다. 분류 관리에서 등록해 주세요.");
    }

    if ($methodId > 0) {
        $methodStmt = $dbConn->prepare("SELECT method_id FROM arap_method WHERE method_id = ? AND method_type='OUT' AND use_yn='Y' LIMIT 1");
        $methodStmt->bind_param('i', $methodId);
        $methodStmt->execute();
        $methodRes = $methodStmt->get_result();
        $methodRow = $methodRes ? $methodRes->fetch_assoc() : null;
        $methodStmt->close();
        if (!$methodRow) {
            arapRedirect($redirectUrl, '거래수단을 다시 선택해 주세요.');
        }
    } else {
        arapRedirect($redirectUrl, '거래수단을 선택해 주세요.');
    }

    $txDate = date('Y-m-d');
    if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $financeDate, $matches) && $matches[1] >= '1000-01-01') {
        $txDate = $matches[1];
    }

    if ($amount <= 0) {
        arapRedirect($redirectUrl, '등록할 금액이 0 이하입니다.');
    }

    $existCheck = $dbConn->query("SELECT tx_id FROM arap_transaction WHERE src_ref = '{$safeSettleCode}' LIMIT 1");
    $alreadyExists = $existCheck && $existCheck->fetch_assoc();

    if ($alreadyExists) {
        arapRedirect($redirectUrl, '이미 지출 거래로 등록된 가이드 정산입니다.');
    }

    $signedAmount = -1 * abs($amount);
    $stmt = $dbConn->prepare("INSERT INTO arap_transaction
                              (tx_date, tx_type, cat_id, description, method_id, amount, memo, src_ref, reg_id, upd_id)
                              VALUES (?, 'OUT', ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param(
        'sisidssss',
        $txDate,
        $catId,
        $description,
        $methodId,
        $signedAmount,
        $memo,
        $settleCode,
        $regId,
        $regId
    );
    $insertOk = $stmt->execute();
    $stmt->close();
    if (!$insertOk) {
        arapRedirect($redirectUrl, '지출 거래 등록 중 오류가 발생했습니다.');
    }

    arapRedirect($redirectUrl, '가이드 정산이 지출 -$' . arapFormatAmount($amount) . ' 으로 등록되었습니다.');
}
