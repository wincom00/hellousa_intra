<?php
include "include/header.php";

if ($_POST['p_code'] && $_POST['st_date']) {
    $pCode = $_POST['p_code'];
    $stDate = $_POST['st_date'];
    
    $result = markEventModificationAsRead($pCode, $stDate);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => '읽음 처리 완료']);
    } else {
        echo json_encode(['success' => false, 'message' => '읽음 처리 실패']);
    }
} else {
    echo json_encode(['success' => false, 'message' => '필수 파라미터 누락']);
}
?>