<?php
require_once __DIR__ . '/include/bootstrap.php';

$gsaUser = gsa_require_login();
$gsaRole = gsa_user_role($gsaUser);

function gsa_check_save_fetch_one($sql, $types = '', $params = array())
{
    global $dbConn;

    $stmt = $dbConn->prepare($sql);
    if (!$stmt) {
        return null;
    }

    if ($types !== '' && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        $stmt->close();
        return null;
    }

    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $row;
}

function gsa_check_save_escape($value)
{
    global $dbConn;

    return trim($dbConn->real_escape_string((string)$value));
}

function gsa_check_save_amount($value)
{
    $value = preg_replace('/[^\d.\-]/', '', (string)$value);
    if ($value === '' || !is_numeric($value)) {
        return '0';
    }

    return (string)(0 + $value);
}

function gsa_check_save_date($value)
{
    $value = trim((string)$value);
    if ($value === '') {
        return '';
    }
    $value = str_replace('/', '-', $value);
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return $value;
    }
    $time = strtotime($value);
    if ($time === false) {
        return '';
    }

    return date('Y-m-d', $time);
}

function gsa_check_save_post_array($key)
{
    if (!isset($_POST[$key])) {
        return array();
    }

    $value = $_POST[$key];
    if (!is_array($value)) {
        $value = array($value);
    }

    return $value;
}

function gsa_check_save_stmt(mysqli $dbConn, $sql, $types, $params)
{
    $stmt = $dbConn->prepare($sql);
    if (!$stmt) {
        throw new Exception('SQL prepare failed');
    }

    if ($types !== '' && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        $stmt->close();
        throw new Exception('SQL execute failed');
    }

    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['mode']) || $_POST['mode'] !== 'save') {
    echo "<script>alert('잘못된 접근입니다.'); history.back();</script>";
    exit;
}

$settleCode = isset($_POST['settle_code']) ? trim((string)$_POST['settle_code']) : '';
if ($settleCode === '') {
    echo "<script>alert('정산코드가 없습니다.'); history.back();</script>";
    exit;
}

$csrfToken = isset($_POST['csrf_token']) ? trim((string)$_POST['csrf_token']) : '';
if ($csrfToken === '' || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    echo "<script>alert('보안 토큰이 유효하지 않습니다.'); history.back();</script>";
    exit;
}

$masterRow = gsa_check_save_fetch_one(
    "SELECT gsm.settle_code, tg.guide_id
     FROM guide_setmaster gsm
     LEFT JOIN tour_guide tg
       ON tg.grand_eCode = gsm.grand_eCode
      AND tg.sub_eCode = gsm.sub_eCode
     WHERE gsm.settle_code = ?
     LIMIT 1",
    's',
    array($settleCode)
);

if (!$masterRow) {
    echo "<script>alert('정산 정보를 찾을 수 없습니다.'); history.back();</script>";
    exit;
}

if (!gsa_can($gsaRole, 'check_save', $masterRow)) {
    echo "<script>alert('저장 권한이 없습니다.'); history.back();</script>";
    exit;
}

if ($gsaRole === 'guide') {
    $currentUserId = isset($gsaUser['userid']) ? (string)$gsaUser['userid'] : '';
    $ownerId = isset($masterRow['guide_id']) ? (string)$masterRow['guide_id'] : '';
    if ($currentUserId === '' || $ownerId === '' || $currentUserId !== $ownerId) {
        echo "<script>alert('본인 정산만 저장할 수 있습니다.'); history.back();</script>";
        exit;
    }
}

$guideMemo = isset($_POST['guide_memo']) ? gsa_check_save_escape($_POST['guide_memo']) : '';
$checkNo = gsa_check_save_post_array('check_no');
$bankName = gsa_check_save_post_array('bank_name');
$usedDate = gsa_check_save_post_array('used_date');
$amount = gsa_check_save_post_array('amount');
$note = gsa_check_save_post_array('note');
$regUser = isset($gsaUser['userid']) ? (string)$gsaUser['userid'] : '';

try {
    $dbConn->begin_transaction();

    gsa_check_save_stmt(
        $dbConn,
        "UPDATE guide_setmaster SET guide_memo = ? WHERE settle_code = ?",
        'ss',
        array($guideMemo, $settleCode)
    );

    gsa_check_save_stmt(
        $dbConn,
        "DELETE FROM guide_set_check WHERE settle_code = ?",
        's',
        array($settleCode)
    );

    $insertSql = "INSERT INTO guide_set_check (
        settle_code, check_no, bank_name, used_date, amount, note, reg_user
    ) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $max = max(count($checkNo), count($bankName), count($usedDate), count($amount), count($note));
    for ($i = 0; $i < $max; $i++) {
        $rowCheckNo = isset($checkNo[$i]) ? gsa_check_save_escape($checkNo[$i]) : '';
        $rowBankName = isset($bankName[$i]) ? gsa_check_save_escape($bankName[$i]) : '';
        $rowUsedDate = isset($usedDate[$i]) ? gsa_check_save_date($usedDate[$i]) : '';
        $rowAmount = isset($amount[$i]) ? gsa_check_save_amount($amount[$i]) : '0';
        $rowNote = isset($note[$i]) ? gsa_check_save_escape($note[$i]) : '';

        if ($rowCheckNo === '' && $rowBankName === '' && $rowUsedDate === '' && $rowAmount === '0' && $rowNote === '') {
            continue;
        }

        gsa_check_save_stmt(
            $dbConn,
            $insertSql,
            'sssssss',
            array($settleCode, $rowCheckNo, $rowBankName, $rowUsedDate, $rowAmount, $rowNote, $regUser)
        );
    }

    $dbConn->commit();
    header('Location: check.php?settle_code=' . urlencode($settleCode) . '&saved=1');
    exit;
} catch (Exception $e) {
    $dbConn->rollback();
    echo "<script>alert('저장 중 오류가 발생했습니다.'); history.back();</script>";
    exit;
}
