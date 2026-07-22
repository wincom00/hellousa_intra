<?php
require_once __DIR__ . '/include/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

function gsa_action_resp($ok, $data = array(), $error = '')
{
    $response = array(
        'ok' => (bool)$ok
    );

    if ($ok) {
        $response['data'] = $data;
    } else {
        $response['error'] = (string)$error;
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

function gsa_action_fetch_one($sql, $types = '', $params = array())
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

function gsa_action_run($sql, $types = '', $params = array())
{
    global $dbConn;

    $stmt = $dbConn->prepare($sql);
    if (!$stmt) {
        return false;
    }

    if ($types !== '' && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $ok = $stmt->execute();
    $stmt->close();

    return $ok;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    gsa_action_resp(false, array(), '잘못된 요청입니다.');
}

$gsaUser = gsa_require_login(true);
$gsaRole = gsa_user_role($gsaUser);
$action = isset($_POST['action']) ? trim((string)$_POST['action']) : '';

$allowedActions = array('submit', 'register_cancel', 'submit_cancel', 'finance_ok', 'ceo_ok', 'check_out');
if (!in_array($action, $allowedActions, true)) {
    gsa_action_resp(false, array(), '지원하지 않는 액션입니다.');
}

if (!gsa_can($gsaRole, $action)) {
    gsa_action_resp(false, array(), '권한 없음');
}

if ($action === 'check_out') {
    $seqNo = isset($_POST['seq_no']) ? (int)$_POST['seq_no'] : 0;
    if ($seqNo <= 0) {
        gsa_action_resp(false, array(), '행사 번호가 없습니다.');
    }

    $tourRow = gsa_action_fetch_one(
        "SELECT seq_no, guide_id, check_out, check_date FROM tour_guide WHERE seq_no = ? LIMIT 1",
        'i',
        array($seqNo)
    );
    if (!$tourRow) {
        gsa_action_resp(false, array(), '대상을 찾을 수 없습니다.');
    }

    $checkDate = date('Y-m-d');
    $updated = gsa_action_run(
        "UPDATE tour_guide SET check_out = 'V', check_date = ? WHERE seq_no = ?",
        'si',
        array($checkDate, $seqNo)
    );
    if (!$updated) {
        gsa_action_resp(false, array(), '체크나감 처리에 실패했습니다.');
    }

    gsa_action_resp(true, array('check_date' => $checkDate));
}

$settleCode = isset($_POST['settle_code']) ? trim((string)$_POST['settle_code']) : '';
if ($settleCode === '') {
    gsa_action_resp(false, array(), '정산코드가 없습니다.');
}

$masterRow = gsa_action_fetch_one(
    "SELECT
        gsm.settle_code,
        gsm.reg_status,
        gsm.report_date,
        gsm.finance_st,
        gsm.finance_date,
        gsm.ceo_st,
        tg.seq_no,
        tg.guide_id,
        tg.check_out,
        tg.check_date
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
    gsa_action_resp(false, array(), '정산 대상을 찾을 수 없습니다.');
}

if ($gsaRole === 'guide') {
    $currentUserId = isset($gsaUser['userid']) ? (string)$gsaUser['userid'] : '';
    $ownerId = isset($masterRow['guide_id']) ? (string)$masterRow['guide_id'] : '';
    if ($currentUserId === '' || $ownerId === '' || $currentUserId !== $ownerId) {
        gsa_action_resp(false, array(), '권한 없음');
    }
}

$regStatus = isset($masterRow['reg_status']) ? trim((string)$masterRow['reg_status']) : '';

if ($action === 'submit') {
    if ($regStatus === 'COMPLETE') {
        gsa_action_resp(false, array(), '이미 제출된 정산입니다.');
    }

    $reportDate = date('Y-m-d H:i:s');
    $updated = gsa_action_run(
        "UPDATE guide_setmaster SET report_date = NOW(), reg_status = 'COMPLETE' WHERE settle_code = ?",
        's',
        array($settleCode)
    );
    if (!$updated) {
        gsa_action_resp(false, array(), '정산 제출에 실패했습니다.');
    }

    gsa_action_resp(true, array('report_date' => $reportDate));
}

if ($action === 'register_cancel') {
    if ($regStatus === 'COMPLETE') {
        gsa_action_resp(false, array(), '제출 완료 건은 등록 취소할 수 없습니다.');
    }

    $deleteTables = array(
        'guide_meal',
        'guide_admission',
        'guide_option',
        'guide_etcamt',
        'guide_shopping',
        'guide_inputamt',
        'guide_setmaster'
    );

    $dbConn->begin_transaction();
    try {
        foreach ($deleteTables as $table) {
            if (!gsa_action_run("DELETE FROM {$table} WHERE settle_code = ?", 's', array($settleCode))) {
                throw new Exception('delete failed');
            }
        }
        $dbConn->commit();
    } catch (Exception $e) {
        $dbConn->rollback();
        gsa_action_resp(false, array(), '등록 취소 처리에 실패했습니다.');
    }

    gsa_action_resp(true, array());
}

if ($action === 'submit_cancel') {
    if ($regStatus !== 'COMPLETE') {
        gsa_action_resp(false, array(), '제출 완료 상태에서만 취소할 수 있습니다.');
    }

    $updated = gsa_action_run(
        "UPDATE guide_setmaster SET report_date = '', reg_status = '' WHERE settle_code = ?",
        's',
        array($settleCode)
    );
    if (!$updated) {
        gsa_action_resp(false, array(), '제출 취소에 실패했습니다.');
    }

    gsa_action_resp(true, array());
}

if ($action === 'finance_ok') {
    $financeDate = date('Y-m-d H:i:s');
    $updated = gsa_action_run(
        "UPDATE guide_setmaster SET finance_st = 'V', finance_date = NOW() WHERE settle_code = ?",
        's',
        array($settleCode)
    );
    if (!$updated) {
        gsa_action_resp(false, array(), '회계확인 처리에 실패했습니다.');
    }

    gsa_action_resp(true, array('finance_date' => $financeDate));
}

if ($action === 'ceo_ok') {
    $updated = gsa_action_run(
        "UPDATE guide_setmaster SET ceo_st = 'V' WHERE settle_code = ?",
        's',
        array($settleCode)
    );
    if (!$updated) {
        gsa_action_resp(false, array(), '대표확인 처리에 실패했습니다.');
    }

    gsa_action_resp(true, array());
}

gsa_action_resp(false, array(), '처리할 수 없는 요청입니다.');
