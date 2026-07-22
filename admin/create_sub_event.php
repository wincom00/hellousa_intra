<?php
// create_sub_event.php
// 그랜드코드/서브코드 자동 생성 + tour_guide 최소행 생성(DELETE 금지)
// userid → guide_id 저장으로 변경

include "include/inc_base.php";
header('Content-Type: application/json; charset=utf-8');

// ====== 전달된 값 ======
$gcode     = trim($_POST['gcode'] ?? '');   // 비어 있으면 자동 생성
$sdate     = trim($_POST['sdate'] ?? '');   // YYYY-MM-DD
$pcode     = trim($_POST['pcode'] ?? '');
$pname     = trim($_POST['pname'] ?? '');
$guide_id  = trim($_POST['guide_id'] ?? ''); // 추가: 배정하려는 가이드 아이디 (필수)

// 유효성 검사
if (!$sdate) { echo json_encode(['ok'=>false,'msg'=>'sdate 누락']); exit; }
//if (!$guide_id) { echo json_encode(['ok'=>false,'msg'=>'guide_id 누락']); exit; }

function ymd_digits($s){ return preg_replace('/[^0-9]/','',$s ?: date('Y-m-d')); }
function code_exists(mysqli $db, $table, $gc, $sc){
    $sql="SELECT 1 FROM {$table} WHERE grand_eCode=? AND sub_eCode=? LIMIT 1";
    $st=$db->prepare($sql); $st->bind_param('ss',$gc,$sc); $st->execute();
    $has = $st->get_result()->num_rows>0; $st->close(); return $has;
}



// 2️⃣ 서브코드 자동 생성(중복 회피)
$sd = ymd_digits($sdate); $k=1;
do {
    $sub = "GSE-{$sd}-{$k}";
    $dup = false;
    foreach(['tour_guide','tour_car'] as $t){
        if (code_exists($dbConn, $t, $gcode, $sub)) { $dup=true; break; }
    }
    if (!$dup) break;
    $k++;
} while(true);

// 3️⃣ tour_guide 최소행 생성/업데이트 (DELETE 금지, userid → guide_id로 대체)
$dbConn->begin_transaction();
try {
    $st = $dbConn->prepare("SELECT 1 FROM tour_guide WHERE grand_eCode=? AND sub_eCode=? AND stDate=? LIMIT 1");
    $st->bind_param('sss', $gcode, $sub, $sdate);
    $st->execute();
    $exists = $st->get_result()->num_rows>0;
    $st->close();

    if ($exists) {
        $sql = "UPDATE tour_guide
                SET p_code=?, p_name=?, guide_id=?, wdate=NOW()
                WHERE grand_eCode=? AND sub_eCode=? AND stDate=?";
        $st = $dbConn->prepare($sql);
        $st->bind_param('ssssss', $pcode, $pname, $guide_id, $gcode, $sub, $sdate);
        $st->execute(); $st->close();
    }
    $dbConn->commit();
    echo json_encode([
        'ok'=>true,
        'grand_eCode'=>$gcode,
        'sub_eCode'=>$sub,
		'p_code'=>$pcode,
        'guide_id'=>$guide_id
    ]);
} catch (Exception $e){
    $dbConn->rollback();
    echo json_encode(['ok'=>false, 'msg'=>$e->getMessage()]);
}
