<?php

require_once __DIR__ . '/include/arap_common.php';

// === 임시 디버그 (확인 후 삭제) ===
////if (isset($_POST['return_to']) && $_POST['return_to'] === 'target_cards') {
    echo "<pre style='background:#fff;color:#000;padding:20px;font-size:14px;'>";
    echo "=== POST DATA ===\n";
    print_r($_POST);
    echo "\n=== 검증 흐름 ===\n";
    echo "tx_date = " . var_export(isset($_POST['tx_date']) ? $_POST['tx_date'] : null, true) . "\n";
    echo "tx_type = " . var_export(isset($_POST['tx_type']) ? $_POST['tx_type'] : null, true) . "\n";
    echo "cat_id  = " . var_export(isset($_POST['cat_id']) ? $_POST['cat_id'] : null, true) . " (int=" . (int)(isset($_POST['cat_id']) ? $_POST['cat_id'] : 0) . ")\n";
    echo "sub_id  = " . var_export(isset($_POST['sub_id']) ? $_POST['sub_id'] : null, true) . " (int=" . (int)(isset($_POST['sub_id']) ? $_POST['sub_id'] : 0) . ")\n";
    echo "method_id = " . var_export(isset($_POST['method_id']) ? $_POST['method_id'] : null, true) . " (int=" . (int)(isset($_POST['method_id']) ? $_POST['method_id'] : 0) . ")\n";
    echo "amount  = " . var_export(isset($_POST['amount']) ? $_POST['amount'] : null, true) . "\n";
    echo "description = " . var_export(isset($_POST['description']) ? $_POST['description'] : null, true) . "\n";

    $catIdDbg = (int)(isset($_POST['cat_id']) ? $_POST['cat_id'] : 0);
    if ($catIdDbg > 0) {
        $r = $dbConn->query("SELECT cat_id, cat_type, cat_name, use_yn FROM arap_category WHERE cat_id = {$catIdDbg}");
        echo "\n=== 대분류 DB 조회 ===\n";
        if ($r && ($row = $r->fetch_assoc())) {
            print_r($row);
        } else {
            echo "[X] cat_id={$catIdDbg} 없음\n";
        }
    }

    $methodIdDbg = (int)(isset($_POST['method_id']) ? $_POST['method_id'] : 0);
    if ($methodIdDbg > 0) {
        $r = $dbConn->query("SELECT method_id, method_type, method_name, use_yn FROM arap_method WHERE method_id = {$methodIdDbg}");
        echo "\n=== 거래수단 DB 조회 ===\n";
        if ($r && ($row = $r->fetch_assoc())) {
            print_r($row);
        } else {
            echo "[X] method_id={$methodIdDbg} 없음\n";
        }
//    }
    echo "</pre>";
 exit;

// === 디버그 끝 ===

$txId = isset($_POST['tx_id']) ? (int)$_POST['tx_id'] : 0;
$txDate = isset($_POST['tx_date']) ? trim((string)$_POST['tx_date']) : '';
$txType = isset($_POST['tx_type']) ? trim((string)$_POST['tx_type']) : 'OUT';
$catId = isset($_POST['cat_id']) ? (int)$_POST['cat_id'] : 0;
$subId = isset($_POST['sub_id']) ? (int)$_POST['sub_id'] : 0;
$description = isset($_POST['description']) ? trim((string)$_POST['description']) : '';
$methodId = isset($_POST['method_id']) ? (int)$_POST['method_id'] : 0;
$amount = isset($_POST['amount']) ? trim((string)$_POST['amount']) : '';
$memo = isset($_POST['memo']) ? trim((string)$_POST['memo']) : '';
$status = isset($_POST['status']) ? trim((string)$_POST['status']) : 'PENDING';
$month = isset($_POST['month']) ? trim((string)$_POST['month']) : '';
$filterType = isset($_POST['filter_tx_type']) ? trim((string)$_POST['filter_tx_type']) : '';
$filterCatId = isset($_POST['filter_cat_id']) ? (int)$_POST['filter_cat_id'] : 0;
$filterSubId = isset($_POST['filter_sub_id']) ? (int)$_POST['filter_sub_id'] : 0;
$filterMethodId = isset($_POST['filter_method_id']) ? (int)$_POST['filter_method_id'] : 0;
$filterStatus = isset($_POST['filter_status']) ? trim((string)$_POST['filter_status']) : '';
$keyword = isset($_POST['keyword']) ? trim((string)$_POST['keyword']) : '';
$returnTo = isset($_POST['return_to']) ? trim((string)$_POST['return_to']) : '';

$allowedStatus = array('PENDING', 'COMPLETED', 'CANCELLED', 'HOLD');
if (!in_array($status, $allowedStatus, true)) {
    $status = 'PENDING';
}
var_dump($txId, $txDate, $txType, $catId, $subId, $description, $methodId, $amount, $memo, $status);


if ($returnTo === 'target_cards') {
    $redirectPage = 'arap_target_cards.php';
    $redirectParams = array(
        'division' => $division,
        'pdx' => $pdx,
        'sub' => $sub,
        'month' => $month,
        'keyword' => $keyword
    );
} else {
    $redirectPage = 'arap_list.php';
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
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $txDate)) {
    arapRedirect(arapPageUrl($redirectPage, $redirectParams),'거래일을 확인해 주세요.');
}

if ($txType !== 'IN' && $txType !== 'OUT') {
    arapRedirect(arapPageUrl($redirectPage, $redirectParams),'거래구분을 확인해 주세요.');
}

if ($catId <= 0) {
    arapRedirect(arapPageUrl($redirectPage, $redirectParams),'대분류를 선택해 주세요.');
}

if (!is_numeric($amount) || (float)$amount <= 0) {
    arapRedirect(arapPageUrl($redirectPage, $redirectParams),'금액을 확인해 주세요.');
}

$amountValue = (float)$amount;
$regId = isset($user_dbinfo['userid']) ? $user_dbinfo['userid'] : '';

$catCheckStmt = $dbConn->prepare("SELECT cat_type FROM arap_category WHERE cat_id = ? AND use_yn = 'Y' LIMIT 1");
$catCheckStmt->bind_param('i', $catId);
$catCheckStmt->execute();
$catResult = $catCheckStmt->get_result();
$catRow = $catResult ? $catResult->fetch_assoc() : null;
$catCheckStmt->close();

if (!$catRow || $catRow['cat_type'] !== $txType) {
    arapRedirect(arapPageUrl($redirectPage, $redirectParams),'대분류와 거래구분이 맞지 않습니다.');
}

if ($subId > 0) {
    $subCheckStmt = $dbConn->prepare("SELECT sub_id FROM arap_subcategory WHERE sub_id = ? AND cat_id = ? AND use_yn = 'Y' LIMIT 1");
    $subCheckStmt->bind_param('ii', $subId, $catId);
    $subCheckStmt->execute();
    $subResult = $subCheckStmt->get_result();
    $subRow = $subResult ? $subResult->fetch_assoc() : null;
    $subCheckStmt->close();

    if (!$subRow) {
        arapRedirect(arapPageUrl($redirectPage, $redirectParams),'소분류를 다시 선택해 주세요.');
    }
} else {
    $subId = null;
}

if ($methodId > 0) {
    $methodCheckStmt = $dbConn->prepare("SELECT method_id FROM arap_method WHERE method_id = ? AND method_type = ? AND use_yn = 'Y' LIMIT 1");
    $methodCheckStmt->bind_param('is', $methodId, $txType);
 //   echo $methodCheckStmt->queryString; // 디버그용: 실제 쿼리문 출력
    $methodCheckStmt->execute();
    $methodResult = $methodCheckStmt->get_result();
    $methodRow = $methodResult ? $methodResult->fetch_assoc() : null;
    $methodCheckStmt->close();

    if (!$methodRow) {
        arapRedirect(arapPageUrl($redirectPage, $redirectParams),'거래수단을 다시 선택해 주세요.');
    }
} else {
    $methodId = null;
}

if ($txId > 0) {
    $stmt = $dbConn->prepare("UPDATE arap_transaction
                              SET tx_date = ?, tx_type = ?, cat_id = ?, sub_id = ?, description = ?, method_id = ?, amount = ?, memo = ?, status = ?, upd_id = ?, upd_dt = NOW()
                              WHERE tx_id = ?");
    $stmt->bind_param(
        'ssissidsssi',
        $txDate,
        $txType,
        $catId,
        $subId,
        $description,
        $methodId,
        $amountValue,
        $memo,
        $status,
        $regId,
        $txId
    );
    $isSuccess = $stmt->execute();
    $stmt->close();
    $message = $isSuccess ? '거래내역이 수정되었습니다.' : '거래내역 수정 중 오류가 발생했습니다.';
} else {
    $stmt = $dbConn->prepare("INSERT INTO arap_transaction
                              (tx_date, tx_type, cat_id, sub_id, description, method_id, amount, memo, status, src_ref, reg_id, upd_id)
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, '', ?, ?)");
    $stmt->bind_param(
        'ssissidssss',
        $txDate,
        $txType,
        $catId,
        $subId,
        $description,
        $methodId,
        $amountValue,
        $memo,
        $status,
        $regId,
        $regId
    );
    $isSuccess = $stmt->execute();
    $stmt->close();
    $message = $isSuccess ? '거래내역이 등록되었습니다.' : '거래내역 등록 중 오류가 발생했습니다.';
}

arapRedirect(arapPageUrl($redirectPage, $redirectParams),$message);

