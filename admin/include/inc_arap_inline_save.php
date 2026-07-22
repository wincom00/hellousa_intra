<?php

require_once __DIR__ . '/include/arap_common.php';

header('Content-Type: application/json; charset=utf-8');

$resp = function ($ok, $message = '', $extra = array()) {
    $payload = array_merge(array('ok' => (bool)$ok, 'message' => (string)$message), (array)$extra);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
};

$txId = isset($_POST['tx_id']) ? (int)$_POST['tx_id'] : 0;
$field = isset($_POST['field']) ? trim((string)$_POST['field']) : '';
$value = isset($_POST['value']) ? (string)$_POST['value'] : '';

if ($txId <= 0) {
    $resp(false, '대상 거래를 찾지 못했습니다.');
}

$allowedFields = array('tx_date', 'tx_type', 'cat_id', 'sub_id', 'description', 'method_id', 'amount', 'memo', 'status');
$statusLabels = array('PENDING' => '대기', 'COMPLETED' => '완료', 'CANCELLED' => '취소', 'HOLD' => '보류');
if (!in_array($field, $allowedFields, true)) {
    $resp(false, '편집할 수 없는 필드입니다.');
}

$rowStmt = $dbConn->prepare("SELECT tx_id, tx_date, tx_type, cat_id, sub_id, description, method_id, amount, memo FROM arap_transaction WHERE tx_id = ? LIMIT 1");
$rowStmt->bind_param('i', $txId);
$rowStmt->execute();
$rowRes = $rowStmt->get_result();
$row = $rowRes ? $rowRes->fetch_assoc() : null;
$rowStmt->close();
if (!$row) {
    $resp(false, '대상 거래를 찾지 못했습니다.');
}

$regId = isset($user_dbinfo['userid']) ? $user_dbinfo['userid'] : '';

$display = '';
$paramType = '';
$paramValue = null;

switch ($field) {
    case 'tx_date':
        $value = trim($value);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            $resp(false, '거래일 형식이 올바르지 않습니다.');
        }
        $paramType = 's';
        $paramValue = $value;
        $display = $value;
        break;

    case 'tx_type':
        if ($value !== 'IN' && $value !== 'OUT') {
            $resp(false, '거래구분이 올바르지 않습니다.');
        }
        $paramType = 's';
        $paramValue = $value;
        $display = $value === 'IN' ? '수입' : '지출';
        break;

    case 'cat_id':
        $catIdVal = (int)$value;
        if ($catIdVal <= 0) {
            $resp(false, '대분류를 선택해 주세요.');
        }
        $catStmt = $dbConn->prepare("SELECT cat_type, cat_name FROM arap_category WHERE cat_id = ? AND use_yn = 'Y' LIMIT 1");
        $catStmt->bind_param('i', $catIdVal);
        $catStmt->execute();
        $catRes = $catStmt->get_result();
        $catRow = $catRes ? $catRes->fetch_assoc() : null;
        $catStmt->close();
        if (!$catRow) {
            $resp(false, '대분류를 다시 선택해 주세요.');
        }
        if ($catRow['cat_type'] !== $row['tx_type']) {
            $resp(false, '거래구분과 맞지 않는 대분류입니다.');
        }
        $paramType = 'i';
        $paramValue = $catIdVal;
        $display = $catRow['cat_name'];
        break;

    case 'sub_id':
        $subIdVal = (int)$value;
        if ($subIdVal <= 0) {
            $sql = "UPDATE arap_transaction SET sub_id = NULL, upd_id = ?, upd_dt = NOW() WHERE tx_id = ?";
            $stmt = $dbConn->prepare($sql);
            $stmt->bind_param('si', $regId, $txId);
            $ok = $stmt->execute();
            $stmt->close();
            if (!$ok) {
                $resp(false, '소분류 저장 중 오류가 발생했습니다.');
            }
            $resp(true, '저장되었습니다.', array('display' => '', 'value' => '0'));
        }
        $subStmt = $dbConn->prepare("SELECT sub_name FROM arap_subcategory WHERE sub_id = ? AND cat_id = ? AND use_yn = 'Y' LIMIT 1");
        $subStmt->bind_param('ii', $subIdVal, $row['cat_id']);
        $subStmt->execute();
        $subRes = $subStmt->get_result();
        $subRow = $subRes ? $subRes->fetch_assoc() : null;
        $subStmt->close();
        if (!$subRow) {
            $resp(false, '대분류와 맞는 소분류를 선택해 주세요.');
        }
        $paramType = 'i';
        $paramValue = $subIdVal;
        $display = $subRow['sub_name'];
        break;

    case 'description':
        $value = trim($value);
        if (mb_strlen($value, 'UTF-8') > 255) {
            $value = mb_substr($value, 0, 255, 'UTF-8');
        }
        $paramType = 's';
        $paramValue = $value;
        $display = $value;
        break;

    case 'method_id':
        $methodIdVal = (int)$value;
        if ($methodIdVal <= 0) {
            $sql = "UPDATE arap_transaction SET method_id = NULL, upd_id = ?, upd_dt = NOW() WHERE tx_id = ?";
            $stmt = $dbConn->prepare($sql);
            $stmt->bind_param('si', $regId, $txId);
            $ok = $stmt->execute();
            $stmt->close();
            if (!$ok) {
                $resp(false, '거래수단 저장 중 오류가 발생했습니다.');
            }
            $resp(true, '저장되었습니다.', array('display' => '', 'value' => '0'));
        }
        $methodStmt = $dbConn->prepare("SELECT method_name FROM arap_method WHERE method_id = ? AND method_type = ? AND use_yn = 'Y' LIMIT 1");
        $methodStmt->bind_param('is', $methodIdVal, $row['tx_type']);
        $methodStmt->execute();
        $methodRes = $methodStmt->get_result();
        $methodRow = $methodRes ? $methodRes->fetch_assoc() : null;
        $methodStmt->close();
        if (!$methodRow) {
            $resp(false, '거래구분과 맞지 않는 거래수단입니다.');
        }
        $paramType = 'i';
        $paramValue = $methodIdVal;
        $display = $methodRow['method_name'];
        break;

    case 'amount':
        if (!is_numeric($value)) {
            $resp(false, '금액 형식이 올바르지 않습니다.');
        }
        $amountVal = (float)$value;
        if (abs($amountVal) <= 0) {
            $resp(false, '금액은 0보다 커야 합니다.');
        }
        $paramType = 'd';
        $paramValue = $amountVal;
        $sign = $row['tx_type'] === 'OUT' ? '-' : '+';
        $display = $sign . '$' . number_format(abs($amountVal), 2);
        break;

    case 'memo':
        if (mb_strlen($value, 'UTF-8') > 255) {
            $value = mb_substr($value, 0, 255, 'UTF-8');
        }
        $paramType = 's';
        $paramValue = $value;
        $display = $value;
        break;

    case 'status':
        if (!isset($statusLabels[$value])) {
            $resp(false, '거래상태 값이 올바르지 않습니다.');
        }
        $paramType = 's';
        $paramValue = $value;
        $display = $statusLabels[$value];
        break;

    default:
        $resp(false, '편집할 수 없는 필드입니다.');
}

$updateSql = "UPDATE arap_transaction SET {$field} = ?, upd_id = ?, upd_dt = NOW() WHERE tx_id = ?";
$stmt = $dbConn->prepare($updateSql);
if (!$stmt) {
    $resp(false, '저장 쿼리 준비 중 오류가 발생했습니다.');
}
$stmt->bind_param($paramType . 'si', $paramValue, $regId, $txId);
$ok = $stmt->execute();
$stmt->close();
if (!$ok) {
    $resp(false, '저장 중 오류가 발생했습니다.');
}

$resp(true, '저장되었습니다.', array('display' => $display, 'value' => (string)$paramValue));
