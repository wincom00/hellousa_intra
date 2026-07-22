<?php
function gsa_auth_json_fail($message, $statusCode)
{
    http_response_code((int)$statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(
        array(
            'ok' => false,
            'error' => (string)$message
        ),
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

function gsa_require_login($forApi = false)
{
    if (empty($_COOKIE['MEMLOGIN_ADMIN_HELLO'])) {
        if ($forApi) {
            gsa_auth_json_fail('로그인이 필요합니다.', 401);
        }
        header('Location: ../admin/login.php');
        exit;
    }

    $info = getinfo_Member($_COOKIE['MEMLOGIN_ADMIN_HELLO']);
    if (!is_array($info) || empty($info['user_id'])) {
        if ($forApi) {
            gsa_auth_json_fail('로그인 정보가 올바르지 않습니다.', 401);
        }
        header('Location: ../admin/login.php');
        exit;
    }

    $dbinfo = getinfo_dbMember($info['user_id']);
    if (!is_array($dbinfo) || empty($dbinfo['userid'])) {
        if ($forApi) {
            gsa_auth_json_fail('사용자 정보를 찾을 수 없습니다.', 401);
        }
        header('Location: ../admin/login.php');
        exit;
    }

    $GLOBALS['user_info'] = $info;
    $GLOBALS['user_dbinfo'] = $dbinfo;
    $role = gsa_user_role($dbinfo);

    if ($role !== 'guide' && !hasMenuAccess(6, 2, 10)) {
        if ($forApi) {
            gsa_auth_json_fail('권한이 없습니다.', 403);
        }
        echo "<script>alert('권한이 없습니다.'); location.href='../admin/index.php';</script>";
        exit;
    }

    return $dbinfo;
}

function gsa_user_role($dbinfo)
{
    $division = isset($dbinfo['division']) ? trim((string)$dbinfo['division']) : '';
    if ($division === 'guide') {
        return 'guide';
    }

    return 'staff';
}

function gsa_row_value($row, $key)
{
    if (!is_array($row) || !array_key_exists($key, $row)) {
        return '';
    }

    return trim((string)$row[$key]);
}

function gsa_can($role, $action, $row = null)
{
    if ($role === 'guide') {
        $regStatus = gsa_row_value($row, 'reg_status');

        if ($action === 'list_self_only') {
            return true;
        }
        if ($action === 'view') {
            return true;
        }
        if ($action === 'edit') {
            return $regStatus !== 'COMPLETE';
        }
        if ($action === 'submit') {
            return $regStatus !== 'COMPLETE';
        }
        if ($action === 'register_cancel') {
            return $regStatus !== 'COMPLETE';
        }
        if ($action === 'submit_cancel') {
            return $regStatus === 'COMPLETE';
        }
        if ($action === 'check_save') {
            return true;
        }
        if ($action === 'finance_ok') {
            return false;
        }
        if ($action === 'ceo_ok') {
            return false;
        }
        if ($action === 'check_out') {
            return false;
        }

        return false;
    }

    return true;
}
