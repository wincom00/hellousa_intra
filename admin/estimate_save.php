<?php
require_once 'include/dbconn.php'; // 여기서 $dbConn (mysqli 연결)이 준비되어 있어야 합니다.

// 개발 단계: 에러 표시 (운영에서는 0 권장)
ini_set('display_errors','1');
ini_set('display_startup_errors','0');
ini_set('log_errors','1');
error_reporting(E_ALL);

// CORS(필요 시)
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

/* ------ 문자셋 설정 (mysqli) ------ */
if (function_exists('mysqli_set_charset')) {
  @mysqli_set_charset($dbConn, 'utf8mb4');
} else {
  @mysqli_query($dbConn, "SET NAMES utf8mb4");
}

/* ===== 안전한 JSON 응답 헬퍼 ===== */
function send_json($arr, $status=200) {
  while (ob_get_level()) { ob_end_clean(); }
  $body = json_encode($arr, JSON_UNESCAPED_UNICODE);
  http_response_code($status);
  header('Content-Type: application/json; charset=utf-8');
  header('Cache-Control: no-store, no-cache, must-revalidate');
  header('Pragma: no-cache');
  header('Connection: close');
  header('Content-Length: '.strlen($body));
  echo $body;
  flush();
  exit;
}

/* ---- helper: '' -> NULL (DATE/NULL 허용 칼럼용) ---- */
$nullIfEmpty = function($v) {
  if (!isset($v)) return null;
  $v = trim((string)$v);
  return ($v === '') ? null : $v;
};

/* ---- mysqli 이스케이프/값 헬퍼 ---- */
function esc($s){
  global $dbConn;
  return mysqli_real_escape_string($dbConn, (string)$s);
}
function sqlv_str($s){ return "'" . esc($s) . "'"; }
function sqlv_num($n){
  if ($n === null) return "NULL";
  if (is_numeric($n)) return (string)$n;
  return "0";
}
function sqlv_json($v){
  global $dbConn;
  if (is_array($v)) {
    $json = json_encode($v, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  } else {
    $json = (string)$v;
    $tmp  = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
      $json = json_encode($json, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    }
  }
  return "'" . mysqli_real_escape_string($dbConn, $json) . "'";
}

/* ---- 합계 재계산 (서버 신뢰원) ----
 *  MEAL        : (unit_per_pax) × (pax) × Σ(dates)
 *  TRANSPORT   : (unit_per_car = 차량수) × Σ(dates)
 *  OVERTIME    : (unit_per_target = 건수) × Σ(dates)  ← ★ 추가
 *  기타        : 클라이언트 sum 그대로
 */
function recalc_sum_for_item($section, $item) {
  $etc = isset($item['etc']) ? $item['etc'] : array();
  if (is_string($etc)) {
    $dec = json_decode($etc, true);
    $etc = (json_last_error() === JSON_ERROR_NONE) ? $dec : array();
  }
  $dates = (isset($etc['dates']) && is_array($etc['dates'])) ? $etc['dates'] : array();

  $qty_total = 0.0;
  foreach ($dates as $d => $v) $qty_total += (float)$v;

  if ($section === 'MEAL') {
    $unit = (float)(isset($etc['unit_per_pax']) ? $etc['unit_per_pax'] : (isset($item['unit']) ? $item['unit'] : 0));
    $pax  = (float)(isset($etc['pax'])          ? $etc['pax']          : (isset($item['cnt'])  ? $item['cnt']  : 0));
    return $unit * $pax * $qty_total;
  }
  if ($section === 'TRANSPORT') {
    $unit = (float)(isset($etc['unit_per_car']) ? $etc['unit_per_car'] : (isset($item['cnt']) ? $item['cnt'] : 0));
    return $unit * $qty_total;
  }
  if ($section === 'OVERTIME') { // ★ 신규 분기
    $unit = (float)(isset($etc['unit_per_target']) ? $etc['unit_per_target'] : (isset($item['cnt']) ? $item['cnt'] : 0));
    return $unit * $qty_total;
  }
  return (float)(isset($item['sum']) ? $item['sum'] : 0);
}

/* ===== 파라미터/가드 ===== */
$mode = isset($_POST['mode']) ? $_POST['mode'] : '';
if ($mode !== 'save_estimate') {
  send_json(array('result'=>'NOOP'));
}

/* ===== 파라미터 ===== */
$estimate_no = isset($_POST['estimate_no']) ? (string)$_POST['estimate_no'] : '';
$to_name     = isset($_POST['to_name'])     ? (string)$_POST['to_name']     : '';
$pax         = isset($_POST['pax'])         ? (int)$_POST['pax']            : 0;
$foc         = isset($_POST['foc'])         ? (int)$_POST['foc']            : 0;
$total_pax   = isset($_POST['total_pax'])   ? (int)$_POST['total_pax']      : 0;
$group_name  = isset($_POST['group_name'])  ? (string)$_POST['group_name']  : '';

$start_date  = $nullIfEmpty(isset($_POST['start_date']) ? $_POST['start_date'] : null);
$end_date    = $nullIfEmpty(isset($_POST['end_date'])   ? $_POST['end_date']   : null);
$wdate       = $nullIfEmpty(isset($_POST['wdate'])      ? $_POST['wdate']      : null);

$profit      = isset($_POST['profit'])      ? (float)$_POST['profit']      : 0.0;
$profit_memo = isset($_POST['profit_memo']) ? (string)$_POST['profit_memo'] : '';
$grand_total = isset($_POST['grand_total']) ? (float)$_POST['grand_total'] : 0.0;
$per_pax     = isset($_POST['per_pax'])     ? (float)$_POST['per_pax']     : 0.0;

/* items: items[][key] 형태 */
$items = (isset($_POST['items']) && is_array($_POST['items'])) ? $_POST['items'] : array();

/* ===== 트랜잭션 시작 ===== */
if (!mysqli_begin_transaction($dbConn)) {
  send_json(array('ok'=>false, 'result'=>'ERROR', 'message'=>'BEGIN failed: '.mysqli_error($dbConn)), 200);
}

try {
  /* ===== 마스터 저장 ===== */
  if (empty($_POST['id'])) {
    // INSERT
    $sql = "
      INSERT INTO estimate_master
        (estimate_no,to_name,pax,foc,total_pax,group_name,start_date,end_date,wdate,profit,profit_memo,grand_total,per_pax)
      VALUES (
        ".sqlv_str($estimate_no).",
        ".sqlv_str($to_name).",
        ".sqlv_num($pax).",
        ".sqlv_num($foc).",
        ".sqlv_num($total_pax).",
        ".sqlv_str($group_name).",
        ".($start_date===null ? "NULL" : sqlv_str($start_date)).",
        ".($end_date===null   ? "NULL" : sqlv_str($end_date)).",
        ".($wdate===null      ? "NULL" : sqlv_str($wdate)).",
        ".sqlv_num($profit).",
        ".sqlv_str($profit_memo).",
        ".sqlv_num($grand_total).",
        ".sqlv_num($per_pax)."
      )
    ";
    if (!mysqli_query($dbConn, $sql)) {
      throw new Exception('INSERT master failed: '.mysqli_error($dbConn));
    }
    $estimate_id = (int)mysqli_insert_id($dbConn);

  } else {
    // UPDATE
    $estimate_id = (int)$_POST['id'];
    $sql = "
      UPDATE estimate_master SET
        estimate_no = ".sqlv_str($estimate_no).",
        to_name     = ".sqlv_str($to_name).",
        pax         = ".sqlv_num($pax).",
        foc         = ".sqlv_num($foc).",
        total_pax   = ".sqlv_num($total_pax).",
        group_name  = ".sqlv_str($group_name).",
        start_date  = ".($start_date===null ? "NULL" : sqlv_str($start_date)).",
        end_date    = ".($end_date===null   ? "NULL" : sqlv_str($end_date)).",
        wdate       = ".($wdate===null      ? "NULL" : sqlv_str($wdate)).",
        profit      = ".sqlv_num($profit).",
        profit_memo = ".sqlv_str($profit_memo).",
        grand_total = ".sqlv_num($grand_total).",
        per_pax     = ".sqlv_num($per_pax)."
      WHERE id = ".sqlv_num($estimate_id)."
    ";
	//echo $sql."<br />";
    if (!mysqli_query($dbConn, $sql)) {
      throw new Exception('UPDATE master failed: '.mysqli_error($dbConn));
    }

    // 기존 아이템 삭제
    if (!mysqli_query($dbConn, "DELETE FROM estimate_items WHERE estimate_id=".sqlv_num($estimate_id))) {
      throw new Exception('DELETE items failed: '.mysqli_error($dbConn));
    }
  }

  /* ===== 아이템 저장 (etc_json + 서버 재계산) ===== */

  // UTF-8 정리
  $utf8clean = function ($v) use (&$utf8clean) {
    if (is_array($v)) {
      foreach ($v as $k => $vv) $v[$k] = $utf8clean($vv);
      return $v;
    }
    if (is_string($v)) {
      if (function_exists('mb_convert_encoding')) {
        return mb_convert_encoding($v, 'UTF-8', 'UTF-8');
      }
      if (function_exists('iconv')) {
        $res = @iconv('UTF-8', 'UTF-8//IGNORE', $v);
        return ($res === false) ? '' : $res;
      }
      return $v;
    }
    return $v;
  };

  if (!empty($items)) {
    foreach ($items as $idx => $it) {
      $section = isset($it['section']) ? strtoupper(trim((string)$it['section'])) : '';
      $label   = trim((string)(isset($it['label']) ? $it['label'] : ''));
      $qty     = (float)(isset($it['qty'])  ? $it['qty']  : 0);
      $unit    = (float)(isset($it['unit']) ? $it['unit'] : 0);
      $cnt     = (float)(isset($it['cnt'])  ? $it['cnt']  : 1);

      // etc 정규화
      $etc = isset($it['etc']) ? $it['etc'] : array();
      if (is_string($etc)) {
        $dec = json_decode($etc, true);
        $etc = (json_last_error() === JSON_ERROR_NONE) ? $dec : array();
      }
      $etc = $utf8clean($etc);

      // dates=0 제거
      if (!empty($etc['dates']) && is_array($etc['dates'])) {
        $tmp = array();
        foreach ($etc['dates'] as $d => $v) {
          $vv = (string)$v;
          if ($vv !== '' && (float)$vv != 0.0) $tmp[$d] = $v;
        }
        $etc['dates'] = $tmp;
      }

      $etc_json_sql = sqlv_json($etc);

      // sum 재계산(서버 신뢰)
      $sum = recalc_sum_for_item($section, array(
        'etc'=>$etc, 'unit'=>$unit, 'cnt'=>$cnt, 'sum'=> (isset($it['sum']) ? $it['sum'] : 0)
      ));

      // INSERT item
      $sql = "
        INSERT INTO estimate_items
          (estimate_id, section, label, qty, unit, cnt, `sum`, etc_json)
        VALUES (
          ".sqlv_num($estimate_id).",
          ".sqlv_str($section).",
          ".sqlv_str($label).",
          ".sqlv_num($qty).",
          ".sqlv_num($unit).",
          ".sqlv_num($cnt).",
          ".sqlv_num($sum).",
          ".$etc_json_sql."
        )
      ";
	  //echo $sql."<br />";
      if (!mysqli_query($dbConn, $sql)) {
        throw new Exception('INSERT item failed: '.mysqli_error($dbConn));
      }
    }
  }

  /* ===== 커밋 ===== */
  if (!mysqli_commit($dbConn)) {
    throw new Exception('COMMIT failed: '.mysqli_error($dbConn));
  }

  send_json(array('ok'=>true, 'result'=>'OK', 'id'=>$estimate_id));

} catch (Exception $e) {
  @mysqli_rollback($dbConn);
  send_json(array('ok'=>false, 'result'=>'ERROR', 'message'=>$e->getMessage()), 200);
}
