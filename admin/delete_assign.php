<?php
// delete_assign.php
// tour_car가 있으면 삭제 불가, 없을 때만 tour_guide 삭제

include "include/inc_base.php";
header('Content-Type: application/json; charset=utf-8');

$gcode = trim($_POST['gcode'] ?? '');
$sub   = trim($_POST['sub'] ?? '');
$pcode   = trim($_POST['pcode'] ?? '');
$sdate = trim($_POST['sdate'] ?? ''); // 선택

if (!$gcode || !$sub) {
    echo json_encode(['ok'=>false, 'msg'=>'gcode/sub 누락']); exit;
}

// 1) tour_car 존재 여부
$st = $dbConn->prepare("SELECT 1 FROM tour_car WHERE grand_eCode=? AND sub_eCode=? AND p_code=? LIMIT 1");
$st->bind_param('sss', $gcode, $sub);
$st->execute();
$hasCar = $st->get_result()->num_rows>0;
$st->close();

if ($hasCar) {
    echo json_encode(['ok'=>false, 'msg'=>'차량 배정 데이터가 있어 삭제할 수 없습니다.']); exit;
}

// 2) tour_guide 삭제 허용
$dbConn->begin_transaction();
try {
    if ($sdate) {
        $st = $dbConn->prepare("DELETE FROM tour_guide WHERE grand_eCode=? AND sub_eCode=? AND stDate=?");
        $st->bind_param('sss', $gcode, $sub, $sdate);
    } else {
        $st = $dbConn->prepare("DELETE FROM tour_guide WHERE grand_eCode=? AND sub_eCode=?");
        $st->bind_param('ss', $gcode, $sub);
    }
    $st->execute(); $st->close();

    $dbConn->commit();
    echo json_encode(['ok'=>true]);
} catch (Exception $e){
    $dbConn->rollback();
    echo json_encode(['ok'=>false, 'msg'=>$e->getMessage()]);
}
