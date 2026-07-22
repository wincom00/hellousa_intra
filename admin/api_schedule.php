<?php
// PHP 7.4 / mysqli_* 절차형 버전 – FullCalendar 무료판 API
header('Content-Type: application/json; charset=utf-8');
ob_start();
include 'include/header.php';  // 여기서 $dbConn 이 mysqli 연결리소스로 존재한다고 가정
ob_end_clean();

if (!isset($dbConn) || !is_object($dbConn)) {
  http_response_code(500);
  echo json_encode(['error'=>'DB connection not available']);
  exit;
}
mysqli_set_charset($dbConn,'utf8mb4');

$mode = $_GET['mode'] ?? '';
$grand = trim($_GET['grand_eCode'] ?? '');
$resourceId = trim($_GET['resourceId'] ?? '');
if ($grand === '') { echo json_encode([]); exit; }

// 헬퍼
function fetch_all_assoc($result){
  $out=[];
  while($r=mysqli_fetch_assoc($result)) $out[]=$r;
  return $out;
}

if ($mode === 'resources') {
  $sql = "SELECT sub_eCode,bus_num 
          FROM tour_car 
          WHERE grand_eCode=? AND bus_num IS NOT NULL 
          GROUP BY sub_eCode,bus_num
          ORDER BY sub_eCode,bus_num";
  $stmt = mysqli_prepare($dbConn,$sql);
  mysqli_stmt_bind_param($stmt,'s',$grand);
  mysqli_stmt_execute($stmt);
  $rs = mysqli_stmt_get_result($stmt);
  $rows = fetch_all_assoc($rs);
  mysqli_stmt_close($stmt);

  $out=[];
  foreach($rows as $r){
    $sub=$r['sub_eCode'];
    $bus=(int)$r['bus_num'];
    $out[]=[
      'id'=>$sub.'|'.$bus,
      'title'=>$sub.' — '.$bus.'호차',
      'group'=>$sub
    ];
  }
  echo json_encode($out,JSON_UNESCAPED_UNICODE);
  exit;
}

if ($mode === 'events') {
  $where="WHERE tc.grand_eCode=? AND tc.bus_num IS NOT NULL";
  $types='s'; $bind=[$grand];
  if($resourceId!==''){
    [$sub,$bus]=explode('|',$resourceId,2);
    $bus=(int)$bus;
    $where.=" AND tc.sub_eCode=? AND tc.bus_num=?";
    $types.='si';
    $bind[]=$sub; $bind[]=$bus;
  }

  $sql = "
  SELECT tc.sub_eCode,tc.bus_num,tc.p_code,
         tm.stDate,pm.p_day,pm.p_name,
         tg.guide_id,tg.sguide_id,
         ml.kor_name AS guide_name_kor,
         ml2.kor_name AS sguide_name_kor
    FROM tour_car tc
    LEFT JOIN tour_master tm
      ON tm.grand_eCode=tc.grand_eCode AND tm.p_code=tc.p_code
    LEFT JOIN product_master pm
      ON pm.p_code=tc.p_code
    LEFT JOIN tour_guide tg
      ON tg.grand_eCode=tc.grand_eCode
     AND tg.sub_eCode=tc.sub_eCode
     AND (tg.bus_num=tc.bus_num OR tg.bus_num IS NULL)
     AND tg.stDate=tm.stDate
    LEFT JOIN member_list ml ON ml.userid=tg.guide_id
    LEFT JOIN member_list ml2 ON ml2.userid=tg.sguide_id
    $where
    GROUP BY tc.sub_eCode,tc.bus_num,tc.p_code
    ORDER BY tc.sub_eCode,tc.bus_num";
  $stmt = mysqli_prepare($dbConn,$sql);
  mysqli_stmt_bind_param($stmt,$types,...$bind);
  mysqli_stmt_execute($stmt);
  $rs = mysqli_stmt_get_result($stmt);
  $rows = fetch_all_assoc($rs);
  mysqli_stmt_close($stmt);

  $out=[];
  foreach($rows as $r){
    $start=$r['stDate'];
    $pday=(int)($r['p_day']??1);
    if($pday<=0)$pday=1;
    $end=(new DateTime($start))->modify("+{$pday} day")->format('Y-m-d');

    $title=($r['p_name']??'행사')." ({$pday}일)";
    $names=[];
    if(!empty($r['guide_id'])) $names[]=($r['guide_name_kor']?:$r['guide_id']);
    if(!empty($r['sguide_id']))$names[]=($r['sguide_name_kor']?:$r['sguide_id']);
    $badge='';
    if($names){
      $title.=' — 가이드: '.implode('/',$names);
      $badge='가이드 '.implode('/',$names);
    }

    $out[]=[
      'id'=>$r['sub_eCode'].'|'.$r['bus_num'].'|'.$r['p_code'],
      'start'=>$start,
      'end'=>$end,
      'allDay'=>true,
      'title'=>$title,
      'extendedProps'=>[
        'sub_eCode'=>$r['sub_eCode'],
        'bus_num'=>(int)$r['bus_num'],
        'guideBadge'=>$badge
      ]
    ];
  }
  echo json_encode($out,JSON_UNESCAPED_UNICODE);
  exit;
}

echo json_encode([]);
