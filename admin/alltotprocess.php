<?php
// api/reservations/search.php
declare(strict_types=1);
require_once 'include/inc_base.php'; // $dbConn (mysqli)

header('Content-Type: application/json; charset=UTF-8');

// DataTables 표준 파라미터
$draw   = (int)($_POST['draw']   ?? 0);
$start  = (int)($_POST['start']  ?? 0);
$length = (int)($_POST['length'] ?? 50);
if ($length <= 0 || $length > 500) $length = 50;

// 검색 파라미터
$cname        = trim($_POST['cname'] ?? '');
$ctel         = trim($_POST['ctel'] ?? '');
$cemail       = trim($_POST['cemail'] ?? '');
$crev         = trim($_POST['crev'] ?? '');
$tourCategory = trim($_POST['tourCategory'] ?? '');
$rstatus      = trim($_POST['rstatus'] ?? '');
$tourpay      = trim($_POST['tourpay'] ?? '');
$kinddate     = trim($_POST['kinddate'] ?? '');
$startDate1   = trim($_POST['startDate1'] ?? '');
$endDate1     = trim($_POST['endDate1'] ?? '');

// 정렬 화이트리스트 (프런트 columns 순서와 매칭)
$sortableCols = [
  'route'              => 'a.parent',       // 표시용, 내부는 parent
  'tour_type_label'    => 'a.tour_type',
  'grand_revNo'        => 'a.grand_revNo',
  'reserveCode'        => 'a.reserveCode',
  'revDate'            => 'a.revDate',
  'owner'              => 'b.p_own',        // 표시용, 내부는 p_own
  'p_name'             => 'a.p_name',
  'stDate_fmt'         => 'a.stDate',       // 표시용, 내부는 stDate
  'book_pri'           => 'a.book_pri',
  'p_cnt'              => 'a.p_cnt',
  'rev_status_label'   => 'a.rev_status',
  'payment_st_label'   => 'a.payment_st',
  'muser'              => 'a.muser_id',     // 표시용, 내부는 muser_id
  'wdate'              => 'a.wdate',
];
// DataTables는 index 기반으로 보냄
$orderColIndex = (int)($_POST['order'][0]['column'] ?? 4); // 기본 접수일
$orderDir      = strtolower($_POST['order'][0]['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
$colKeys = array_keys($sortableCols);
$orderKey = $colKeys[$orderColIndex] ?? 'revDate';
$orderBy = $sortableCols[$orderKey] . ' ' . $orderDir;

// 공통 WHERE
$where = ['a.parent = ?'];   // 단일/복합 구분 값 사용
$params = ['s'];             // mysqli bind types
$args = ['MAIN'];

// 날짜 필터
if ($kinddate === '1' && $startDate1 && $endDate1) {        // 출발일
  $where[] = 'a.stDate BETWEEN ? AND ?';
  $params[] = 's'; $args[] = $startDate1;
  $params[] = 's'; $args[] = $endDate1;
} elseif ($kinddate === '2' && $startDate1 && $endDate1) {   // 접수일
  $where[] = 'DATE(a.revDate) BETWEEN ? AND ?';
  $params[] = 's'; $args[] = $startDate1;
  $params[] = 's'; $args[] = $endDate1;
}

// 예약자/여행자명
if ($cname !== '') {
  $where[] = '(a.book_pri LIKE ? OR EXISTS (SELECT 1 FROM reserve_traveler c WHERE c.reserveCode = a.reserveCode AND c.traveler_nm LIKE ?))';
  $like = '%' . $cname . '%';
  $params[] = 's'; $args[] = $like;
  $params[] = 's'; $args[] = $like;
}

// 예약번호/대표예약번호
if ($crev !== '') {
  $where[] = '(a.reserveCode LIKE ? OR a.grand_revNo LIKE ?)';
  $like = '%' . $crev . '%';
  $params[] = 's'; $args[] = $like;
  $params[] = 's'; $args[] = $like;
}

// 이메일/전화
if ($cemail !== '') { $where[] = 'a.book_email LIKE ?';  $params[]='s'; $args[]='%'.$cemail.'%'; }
if ($ctel   !== '') { $where[] = 'a.book_phone LIKE ?';  $params[]='s'; $args[]='%'.$ctel.'%';   }

// 접수상태/결제상태
if ($rstatus !== '') { $where[] = 'a.rev_status = ?';   $params[]='s'; $args[]=$rstatus; }
if ($tourpay !== '') { $where[] = 'a.payment_st = ?';   $params[]='s'; $args[]=$tourpay; }

// 투어분류(product_master.p_type)
$joinProduct = false;
if ($tourCategory !== '') {
  $joinProduct = true;
  $where[] = 'b.p_type = ?';
  $params[] = 's'; $args[] = $tourCategory;
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// 총 레코드 수 (필터 미적용)
$totalSql = 'SELECT COUNT(*) AS cnt FROM reserve_info a WHERE a.parent = ?';
$totalStmt = $dbConn->prepare($totalSql);
$totalStmt->bind_param('s', $args0 = 'MAIN');
$totalStmt->execute();
$total = (int)$totalStmt->get_result()->fetch_assoc()['cnt'];
$totalStmt->close();

// 필터 적용 수
$countSql = 'SELECT COUNT(*) AS cnt
  FROM reserve_info a ' . ($joinProduct ? 'LEFT JOIN product_master b ON b.p_code = a.p_code ' : '') . $whereSql;
$countStmt = $dbConn->prepare($countSql);
$countStmt->bind_param(implode('', $params), ...$args);
$countStmt->execute();
$filtered = (int)$countStmt->get_result()->fetch_assoc()['cnt'];
$countStmt->close();

// 데이터 조회
$dataSql = 'SELECT
    a.parent, a.tour_type, a.grand_revNo, a.reserveCode, a.revDate, a.p_code, a.p_name, a.stDate,
    a.book_pri, a.p_cnt, a.rev_status, a.payment_st, a.muser_id, a.userid, a.wdate,
    b.p_type, b.p_own
  FROM reserve_info a
  ' . ($joinProduct ? 'LEFT JOIN product_master b ON b.p_code = a.p_code ' : 'LEFT JOIN product_master b ON b.p_code = a.p_code ') . // 항상 p_own 표시용
  $whereSql . "
  ORDER BY $orderBy
  LIMIT ?, ?";

$params2 = $params;
$args2   = $args;
$params2[] = 'i'; $args2[] = $start;
$params2[] = 'i'; $args2[] = $length;

$stmt = $dbConn->prepare($dataSql);
$stmt->bind_param(implode('', $params2), ...$args2);
$stmt->execute();
$rs = $stmt->get_result();

$week = ['일','월','화','수','목','금','토'];

$rows = [];
while ($r = $rs->fetch_assoc()) {
  // 라벨/표시 포맷
  $route = ($r['parent'] === 'SUB') ? '복합' : '단일';
  $tourTypeLabel = ($r['tour_type'] === '1') ? '직접예약' : (($r['tour_type'] === '2') ? '웹예약' : '업체예약');

  $st = $r['stDate'];
  $stFmt = $st ? ($st . ' (' . $week[ date('w', strtotime($st)) ] . ')') : '';

  $revLabel = ($r['rev_status'] === 'READY') ? '예약접수' : (($r['rev_status'] === 'DONE') ? '<font color=blue>예약확정</font>' : ($r['rev_status'] === 'CANCEL' ? '<font color=red>예약취소</font>' : $r['rev_status']));
  $payLabel = ($r['payment_st'] === 'READY') ? '미납' : (($r['payment_st'] === 'PPAY') ? '부분완납' : (($r['payment_st'] === 'DONE') ? '완납' : ($r['payment_st'] === 'OPAY' ? '환불' : $r['payment_st'])));

  // 담당자: muser_id 없으면 userid fallback
  $muser = $r['muser_id'] ?: $r['userid'];

  // 소유사: b.p_own이 'hello'면 '투어헬로USA', 아니면 p_own 그대로(필요시 별도 매핑 함수 적용 가능)
  $owner = ($r['p_own'] === 'hello') ? '투어헬로USA' : ($r['p_own'] ?: '');

  // 상세 링크 (기존 규칙 유지)
  // ty/pricet/sub 매핑
  $ty = $pricet = $sub = null;
  if ($r['tour_type'] === '1') { $ty = 1; $pricet = 1; $sub = 15; }
  elseif ($r['tour_type'] === '2') { $ty = 2; $pricet = 2; $sub = 20; }
  else { $ty = 3; $pricet = 3; $sub = 25; }

  $detailUrl = "base_reservation_m.php?estimateCode={$r['reserveCode']}&division=3&pdx=2&sub={$sub}&ty={$ty}&pricet={$pricet}#TOP";

  $rows[] = [
    'route'             => $route,
    'tour_type_label'   => $tourTypeLabel,
    'grand_revNo'       => $r['grand_revNo'],
    'reserveCode'       => $r['reserveCode'],
    'revDate'           => $r['revDate'],
    'owner'             => $owner,
    'p_name'            => $r['p_name'],
    'stDate_fmt'        => $stFmt,
    'book_pri'          => $r['book_pri'],
    'p_cnt'             => (int)$r['p_cnt'],
    'rev_status_label'  => $revLabel,
    'payment_st_label'  => $payLabel,
    'muser'             => $muser,
    'wdate'             => $r['wdate'],
    'detail_url'        => $detailUrl
  ];
}
$stmt->close();

echo json_encode([
  'draw'            => $draw,
  'recordsTotal'    => $total,
  'recordsFiltered' => $filtered,
  'data'            => $rows
], JSON_UNESCAPED_UNICODE);
?>