<?php
/**
 * 가이드 정산 - 체크번호(다건) + 전체정산 메모 입력/저장
 * - PHP 7.0 호환
 * - CSRF 토큰, 기본 밸리데이션 포함
 * - guide_setmaster.settle_code 기준으로 동작
 * - 체크내역: guide_set_check 테이블 사용
 * - 메모: guide_setmaster.guide_memo 컬럼 사용
 */

ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);

// ===== 공통 include (환경에 맞게 경로 조정) =====
require_once $_SERVER['DOCUMENT_ROOT'].'/include/db-con.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/include/functions.php';

// ===== 유틸 =====
function gv($k,$d=''){ return isset($_GET[$k])?trim($_GET[$k]):$d; }
function pv($k,$d=''){ return isset($_POST[$k])? (is_string($_POST[$k])?trim($_POST[$k]):$_POST[$k]) : $d; }
function arr($a){ return is_array($a)? $a : array(); }

// 세션 (CSRF용)
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(16));
}

// ===== 파라미터 =====
$settle_code = gv('settle_code'); // 필수 (정산 식별자)
if ($settle_code === '') {
  // 기존 페이지 흐름에 맞게 처리
  die('<script>alert("settle_code가 없습니다."); history.back();</script>');
}

// 로그인 사용자 (사이트 정책에 맞게 세팅)
$login_user = isset($_COOKIE['MEMLOGIN_ADMIN_HELLO']) ? $_COOKIE['MEMLOGIN_ADMIN_HELLO'] : 'system';

// ===== DB 핸들 얻기 (functions.php 내부 $db 혹은 mysqli 리소스 사용) =====
// 아래 예시는 PDO/$db 또는 mysqli를 모두 고려. 프로젝트 표준에 맞게 조정.
global $db; // PDO 라고 가정
$is_pdo = is_object($db);

// ===== 저장 처리 =====
$mode = pv('mode','');
if ($mode === 'save') {

  // CSRF 체크
  $csrf = pv('csrf_token','');
  if ($csrf === '' || $csrf !== $_SESSION['csrf_token']) {
    die('<script>alert("보안 토큰이 유효하지 않습니다. 새로고침 후 다시 시도하세요."); history.back();</script>');
  }

  // 입력 수집
  $memo_text  = pv('guide_memo','');

  $check_no   = arr($_POST['check_no']);
  $bank_name  = arr($_POST['bank_name']);
  $used_date  = arr($_POST['used_date']);
  $amount     = arr($_POST['amount']);
  $note       = arr($_POST['note']);

  // 트랜잭션 시작
  if ($is_pdo) {
    $db->beginTransaction();
  } else {
    mysqli_begin_transaction($db);
  }

  try {
    // 1) 메모 저장 (guide_setmaster.guide_memo 업데이트)
    if ($is_pdo) {
      $q1 = $db->prepare("UPDATE guide_setmaster SET guide_memo = :memo WHERE settle_code = :sc");
      $q1->execute(array(':memo'=>$memo_text, ':sc'=>$settle_code));
    } else {
      $q1 = mysqli_prepare($db, "UPDATE guide_setmaster SET guide_memo = ? WHERE settle_code = ?");
      mysqli_stmt_bind_param($q1, 'ss', $memo_text, $settle_code);
      mysqli_stmt_execute($q1);
      mysqli_stmt_close($q1);
    }

    // 2) 기존 체크내역 삭제 후 재입력(간단 전략)
    if ($is_pdo) {
      $db->prepare("DELETE FROM guide_set_check WHERE settle_code = :sc")
         ->execute(array(':sc'=>$settle_code));
      $ins = $db->prepare("
        INSERT INTO guide_set_check(settle_code, check_no, bank_name, used_date, amount, note, reg_user)
        VALUES(:sc, :no, :bank, :ud, :amt, :note, :user)
      ");
    } else {
      $del = mysqli_prepare($db, "DELETE FROM guide_set_check WHERE settle_code = ?");
      mysqli_stmt_bind_param($del, 's', $settle_code);
      mysqli_stmt_execute($del);
      mysqli_stmt_close($del);

      $ins = mysqli_prepare($db, "
        INSERT INTO guide_set_check (settle_code, check_no, bank_name, used_date, amount, note, reg_user)
        VALUES (?, ?, ?, ?, ?, ?, ?)
      ");
    }

    $row_count = max(count($check_no), count($bank_name), count($used_date), count($amount), count($note));
    for ($i=0; $i<$row_count; $i++) {
      $no   = trim($check_no[$i]);
      $bank = trim($bank_name[$i]);
      $ud   = trim($used_date[$i]);
      $amt  = trim($amount[$i]);
      $nt   = trim($note[$i]);

      // 완전 빈 행은 스킵
      if ($no==='' && $bank==='' && $ud==='' && $amt==='' && $nt==='') continue;

      // 일자 형식 보정(YYYY-MM-DD)
      if ($ud !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $ud)) {
        // 2025/09/05 → 2025-09-05 정도만 간단 보정
        $ud = str_replace('/', '-', $ud);
      }

      // 금액 숫자만 추출
      $amt_n = $amt === '' ? '0' : preg_replace('/[^\d.\-]/','', $amt);

      if ($is_pdo) {
        $ins->execute(array(
          ':sc'   => $settle_code,
          ':no'   => $no,
          ':bank' => $bank,
          ':ud'   => ($ud===''? null : $ud),
          ':amt'  => ($amt_n===''? 0 : $amt_n),
          ':note' => $nt,
          ':user' => $login_user
        ));
      } else {
        $ud_param  = ($ud===''? null : $ud);
        $amt_param = ($amt_n===''? 0 : (float)$amt_n);
        mysqli_stmt_bind_param($ins, 'sssssss',
          $settle_code, $no, $bank, $ud_param, $amt_param, $nt, $login_user
        );
        mysqli_stmt_execute($ins);
      }
    }

    if (!$is_pdo) {
      mysqli_stmt_close($ins);
    }

    // 커밋
    if ($is_pdo) $db->commit(); else mysqli_commit($db);

    echo '<script>alert("저장되었습니다."); location.href="?settle_code='.htmlspecialchars($settle_code,ENT_QUOTES).'";</script>';
    exit;

  } catch (Exception $e) {
    if ($is_pdo) $db->rollBack(); else mysqli_rollback($db);
    // 로그/디버그 필요 시 $e->getMessage()
    echo '<script>alert("저장 중 오류가 발생했습니다."); history.back();</script>';
    exit;
  }
}

// ===== 화면 로드: 기본 데이터 =====
// guide_setmaster
if ($is_pdo) {
  $stmt = $db->prepare("SELECT * FROM guide_setmaster WHERE settle_code = :sc");
  $stmt->execute(array(':sc'=>$settle_code));
  $setm = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
  $stmt = mysqli_prepare($db, "SELECT * FROM guide_setmaster WHERE settle_code = ?");
  mysqli_stmt_bind_param($stmt, 's', $settle_code);
  mysqli_stmt_execute($stmt);
  $res  = mysqli_stmt_get_result($stmt);
  $setm = mysqli_fetch_assoc($res);
  mysqli_stmt_close($stmt);
}
if (!$setm) {
  die('<script>alert("해당 정산을 찾을 수 없습니다."); history.back();</script>');
}

// 체크 내역
if ($is_pdo) {
  $stmt2 = $db->prepare("SELECT * FROM guide_set_check WHERE settle_code = :sc ORDER BY id ASC");
  $stmt2->execute(array(':sc'=>$settle_code));
  $checks = $stmt2->fetchAll(PDO::FETCH_ASSOC);
} else {
  $stmt2 = mysqli_prepare($db, "SELECT * FROM guide_set_check WHERE settle_code = ? ORDER BY id ASC");
  mysqli_stmt_bind_param($stmt2, 's', $settle_code);
  mysqli_stmt_execute($stmt2);
  $res2   = mysqli_stmt_get_result($stmt2);
  $checks = array();
  while($row = mysqli_fetch_assoc($res2)) $checks[] = $row;
  mysqli_stmt_close($stmt2);
}

// 화면 값
$guide_memo  = isset($setm['guide_memo']) ? $setm['guide_memo'] : '';
$stDate      = isset($setm['stDate']) ? $setm['stDate'] : '';
$edDate      = isset($setm['edDate']) ? $setm['edDate'] : '';
$grand_eCode = isset($setm['grand_eCode']) ? $setm['grand_eCode'] : '';
$sub_eCode   = isset($setm['sub_eCode']) ? $setm['sub_eCode'] : '';

?>
<!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>가이드 정산 - 체크/메모 입력</title>
<style>
  :root{
    --bg:#0f172a;--card:#111827;--ink:#e5edff;--muted:#9fb0d1;--line:#1f2937;--brand:#60a5fa;
  }
  body{margin:0;background:var(--bg);color:var(--ink);font-family:system-ui,-apple-system,Segoe UI,Roboto,Apple SD Gothic Neo,NanumGothic,Arial}
  .wrap{max-width:1100px;margin:24px auto;padding:16px;}
  .head{
    display:flex;align-items:center;justify-content:space-between;background:linear-gradient(180deg,#0b1327,#0b1327cc);
    border:1px solid var(--line);border-radius:16px;padding:18px 20px;margin-bottom:16px
  }
  .meta{display:flex;gap:16px;flex-wrap:wrap}
  .pill{background:#0b1b36;border:1px solid #132442;padding:6px 10px;border-radius:999px;color:#cfe0ff}
  h1{font-size:18px;margin:0}
  a.link{color:#9ed1ff;text-decoration:none;border-bottom:1px dashed #2b6ab3}
  /* 카드 */
  .card{background:var(--card);border:1px solid var(--line);border-radius:16px;margin-top:16px;overflow:hidden}
  .card h2{font-size:16px;margin:0;padding:14px 16px;border-bottom:1px solid var(--line);background:#0e1528}
  .card .body{padding:14px 16px}
  /* 테이블 */
  table{width:100%;border-collapse:collapse}
  th,td{border-bottom:1px solid var(--line);padding:10px 8px;text-align:left;font-size:14px}
  th{color:#c9daf7;background:#0e1528;position:sticky;top:0}
  tr:last-child td{border-bottom:none}
  input[type="text"], input[type="date"], input[type="number"], textarea{
    width:100%;box-sizing:border-box;background:#0b1327;border:1px solid #1a2744;color:#e5edff;border-radius:10px;padding:10px 12px;
    outline:none
  }
  input[type="text"]:focus, input[type="date"]:focus, input[type="number"]:focus, textarea:focus{border-color:#2c70d3}
  textarea{min-height:120px;resize:vertical}
  .row-actions{display:flex;gap:8px}
  .btn{
    appearance:none;border:1px solid #1f334e;background:#0e1c35;color:#cde2ff;border-radius:10px;padding:10px 14px;font-weight:600;cursor:pointer
  }
  .btn:hover{border-color:#2c70d3}
  .btn.brand{background:linear-gradient(180deg,#1c3f7c,#15325f);border-color:#2c5ea7}
  .btn.ghost{background:transparent}
  .btn.icon{padding:8px 10px;border-radius:8px}
  .flex{display:flex;align-items:center;gap:8px}
  .right{margin-left:auto}
  .footbar{display:flex;gap:10px;justify-content:flex-end;padding:12px 16px;border-top:1px solid var(--line);background:#0e1528}
  .sub{color:var(--muted);font-size:12px}
  .sumline{display:flex;gap:16px;align-items:center;margin-top:8px;color:#bcd3ff}
</style>
</head>
<body>
<div class="wrap">

  <!-- 상단 정보 헤더 -->
  <div class="head">
    <div>
      <h1>가이드 정산 입력 (체크번호 + 메모)</h1>
      <div class="meta">
        <div class="pill">정산코드: <b style="margin-left:6px;"><?=htmlspecialchars($settle_code,ENT_QUOTES)?></b></div>
        <?php if($stDate||$edDate){ ?>
        <div class="pill">기간: <b style="margin-left:6px;"><?=htmlspecialchars($stDate,ENT_QUOTES)?> ~ <?=htmlspecialchars($edDate,ENT_QUOTES)?></b></div>
        <?php } ?>
        <?php if($grand_eCode){ ?><div class="pill">대분류: <b style="margin-left:6px;"><?=htmlspecialchars($grand_eCode,ENT_QUOTES)?></b></div><?php } ?>
        <?php if($sub_eCode){ ?><div class="pill">소분류: <b style="margin-left:6px;"><?=htmlspecialchars($sub_eCode,ENT_QUOTES)?></b></div><?php } ?>
      </div>
    </div>
    <div class="sub">
      이 화면은 <b>해당 정산</b>에 사용된 <b>체크번호(여러 장)</b>와 <b>전체 메모</b>를 함께 기록합니다.
    </div>
  </div>

  <form method="post" action="?settle_code=<?=htmlspecialchars($settle_code,ENT_QUOTES)?>">
    <input type="hidden" name="mode" value="save">
    <input type="hidden" name="csrf_token" value="<?=$_SESSION['csrf_token']?>">

    <!-- 체크번호 입력 카드 -->
    <div class="card">
      <h2>체크 사용내역</h2>
      <div class="body">
        <div class="flex" style="margin-bottom:10px;">
          <button type="button" class="btn icon" id="btnAddRow">+ 행 추가</button>
          <div class="right sumline">
            <span>합계 금액:</span>
            <b id="sumAmount">0</b>
          </div>
        </div>

        <div style="overflow:auto; max-height:420px; border:1px solid var(--line); border-radius:12px;">
          <table id="checkTable">
            <thead>
              <tr>
                <th style="width:18%">체크번호</th>
                <th style="width:16%">은행/발행처</th>
                <th style="width:16%">사용일</th>
                <th style="width:16%">금액</th>
                <th>비고</th>
                <th style="width:80px">삭제</th>
              </tr>
            </thead>
            <tbody>
              <?php
              if (count($checks) === 0) {
                // 빈 행 1개
                ?>
                <tr>
                  <td><input type="text" name="check_no[]" placeholder="예) 123456" /></td>
                  <td><input type="text" name="bank_name[]" placeholder="예) BOA" /></td>
                  <td><input type="date" name="used_date[]" /></td>
                  <td><input type="text" name="amount[]" placeholder="0" oninput="onlyNumber(this); calcSum();" /></td>
                  <td><input type="text" name="note[]" placeholder="메모" /></td>
                  <td class="row-actions"><button type="button" class="btn ghost icon" onclick="removeRow(this)">삭제</button></td>
                </tr>
                <?php
              } else {
                foreach($checks as $r){
                  ?>
                  <tr>
                    <td><input type="text" name="check_no[]" value="<?=htmlspecialchars($r['check_no'],ENT_QUOTES)?>" /></td>
                    <td><input type="text" name="bank_name[]" value="<?=htmlspecialchars($r['bank_name'],ENT_QUOTES)?>" /></td>
                    <td><input type="date" name="used_date[]" value="<?=htmlspecialchars($r['used_date'],ENT_QUOTES)?>" /></td>
                    <td><input type="text" name="amount[]" value="<?=htmlspecialchars($r['amount'],ENT_QUOTES)?>" oninput="onlyNumber(this); calcSum();" /></td>
                    <td><input type="text" name="note[]" value="<?=htmlspecialchars($r['note'],ENT_QUOTES)?>" /></td>
                    <td class="row-actions"><button type="button" class="btn ghost icon" onclick="removeRow(this)">삭제</button></td>
                  </tr>
                  <?php
                }
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- 전체정산 메모 카드 -->
    <div class="card">
      <h2>전체정산 메모</h2>
      <div class="body">
        <textarea name="guide_memo" placeholder="해당 정산 전체에 대한 특이사항/요약/전달메모 등을 자유롭게 입력하세요."><?=htmlspecialchars($guide_memo,ENT_QUOTES)?></textarea>
      </div>
    </div>

    <!-- 저장 바 -->
    <div class="card" style="margin-bottom:24px;">
      <div class="footbar">
        <button type="submit" class="btn brand">저장</button>
        <button type="button" class="btn" onclick="location.href='?settle_code=<?=htmlspecialchars($settle_code,ENT_QUOTES)?>'">새로고침</button>
        <button type="button" class="btn ghost" onclick="history.back();">뒤로</button>
      </div>
    </div>
  </form>

</div>

<script>
  function removeRow(btn){
    var tr = btn.closest('tr');
    var tbody = tr.parentNode;
    tbody.removeChild(tr);
    calcSum();
    if(tbody.querySelectorAll('tr').length===0){
      addRow(); // 최소 1행 유지
    }
  }

  function addRow(){
    var tbody = document.querySelector('#checkTable tbody');
    var tr = document.createElement('tr');
    tr.innerHTML =
      '<td><input type="text" name="check_no[]" placeholder="예) 123456" /></td>'+
      '<td><input type="text" name="bank_name[]" placeholder="예) BOA" /></td>'+
      '<td><input type="date" name="used_date[]" /></td>'+
      '<td><input type="text" name="amount[]" placeholder="0" oninput="onlyNumber(this); calcSum();" /></td>'+
      '<td><input type="text" name="note[]" placeholder="메모" /></td>'+
      '<td class="row-actions"><button type="button" class="btn ghost icon" onclick="removeRow(this)">삭제</button></td>';
    tbody.appendChild(tr);
  }

  function onlyNumber(inp){
    // 숫자/소수점/마이너스만 허용
    var v = inp.value.replace(/[^0-9.\-]/g,'');
    // 소수점 여러 개 방지
    var parts = v.split('.');
    if(parts.length>2){
      v = parts.shift()+'.'+parts.join('');
    }
    inp.value = v;
  }

  function calcSum(){
    var sum = 0;
    var list = document.querySelectorAll('input[name="amount[]"]');
    for(var i=0;i<list.length;i++){
      var n = parseFloat(list[i].value);
      if(!isNaN(n)) sum += n;
    }
    document.getElementById('sumAmount').textContent = sum.toLocaleString();
  }

  document.getElementById('btnAddRow').addEventListener('click', addRow);
  // 초기 합계
  calcSum();
</script>
</body>
</html>
