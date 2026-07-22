<?php
// estimate_view.php - 레이아웃 문제 해결 + 모든 섹션 추가
include 'include/header.php';
include 'include/side_m.php';

$id = (int)($_GET['id'] ?? 0);

// 기본 조회
$sql  = "SELECT * FROM estimate_master WHERE id = $id";
$result = mysqli_query($dbConn, $sql);
$master = mysqli_fetch_assoc($result) ?: [];

$sql  = "SELECT * FROM estimate_items WHERE estimate_id = $id ORDER BY section, id";
$result = mysqli_query($dbConn, $sql);
$items = [];
while ($row = mysqli_fetch_assoc($result)) {
    $items[] = $row;
}

// 섹션 분류
$sections = [];
foreach ($items as $item) {
    $sec = $item['section'] ?? 'ETC';
    $sections[$sec][] = $item;
}

// 통화 포맷 헬퍼(빈값 방어)
function money($v) { $n = is_numeric($v) ? (float)$v : 0; return '$'.number_format($n, 2); }
?>
<style>
/* ====== 기존 스타일 유지 ====== */
.breakdown-wrapper { width:100%; padding:10px; box-sizing:border-box; }
.breakdown-header { background:linear-gradient(135deg,#2E86AB 0%,#A23B72 100%); color:#fff; padding:12px 15px; margin-bottom:15px; border-radius:6px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; }
.breakdown-title { font-size:18px; font-weight:bold; margin:0; flex-shrink:0; }
.action-buttons { display:flex; gap:6px; flex-wrap:wrap; }
.btn-export { padding:6px 10px; border:none; border-radius:4px; color:#fff; text-decoration:none; font-size:11px; font-weight:bold; transition:.2s; white-space:nowrap; }
.btn-excel{background:#28a745;} .btn-pdf{background:#dc3545;} .btn-edit{background:#ffc107;color:#212529;} .btn-list{background:#6c757d;}
.btn-export:hover{opacity:.8; text-decoration:none; color:#fff;}
.info-grid{ display:grid; grid-template-columns:auto 1fr auto 1fr auto 1fr auto 1fr; gap:8px 15px; background:#f8f9fa; padding:12px; border-radius:6px; margin-bottom:15px; font-size:12px; align-items:center; }
.info-label{ font-weight:bold; color:#495057; white-space:nowrap; }
.info-value{ color:#212529; }
.section-header{ background:linear-gradient(135deg,#A23B72 0%,#F18F01 100%); color:#fff; padding:8px 12px; margin:15px 0 8px; border-radius:4px; font-weight:bold; font-size:13px; }
.data-table{ width:100%; border-collapse:collapse; margin-bottom:12px; font-size:11px; }
.data-table th,.data-table td{ border:1px solid #dee2e6; padding:4px 6px; text-align:center; }
.data-table th{ background:#f8f9fa; font-weight:bold; font-size:10px; }
.data-table .text-left{text-align:left;} .data-table .text-right{text-align:right;}
.data-table .currency{ color:#2E86AB; font-weight:bold; }
.total-row{ background:#e3f2fd; font-weight:bold; }
.summary-section{ background:linear-gradient(135deg,#2E86AB 0%,#A23B72 100%); color:#fff; padding:12px; border-radius:6px; margin-top:15px; }
.summary-pills{ display:flex; flex-wrap:wrap; gap:6px; margin-bottom:12px;}
.pill{ background:rgba(255,255,255,.2); padding:3px 6px; border-radius:12px; font-size:10px; font-weight:bold; white-space:nowrap;}
.final-totals{ display:grid; grid-template-columns:1fr 1fr; gap:8px;}
.final-total-item{ background:rgba(255,255,255,.1); padding:8px; border-radius:4px; text-align:center;}
.final-total-label{ font-size:11px; margin-bottom:4px;}
.final-total-amount{ font-size:16px; font-weight:bold;}
@media (max-width:1024px){ .breakdown-header{flex-direction:column; text-align:center;} .info-grid{grid-template-columns:auto 1fr; gap:4px 10px;} .final-totals{grid-template-columns:1fr;} }
@media (max-width:768px){ .breakdown-wrapper{padding:5px;} .data-table{font-size:10px;} .data-table th,.data-table td{padding:2px 4px;} }
@media print{ .action-buttons{display:none!important;} .breakdown-wrapper{padding:0!important;} }
</style>

<div id="contentwrapper" class="reservationDetailForm">
  <div class="main_content">
    <div class="breakdown-wrapper">
      <!-- 헤더 -->
      <div class="breakdown-header">
        <h1 class="breakdown-title">BREAKDOWN QUOTATION</h1>
        <div class="action-buttons">
          <a href="estimate_export_breakdown.php?action=download_excel&id=<?= $id ?>" class="btn-export btn-excel">엑셀</a>
         <a href="estimate_export_breakdown2.php?id=<?= (int)$id ?>&auto=1" class="btn-export btn-pdf" target='_new'>PDF</a>
          <a href="javascript:sendBreakdownEmail(<?= $id ?>)" class="btn-export" style="background:#28a745;">이메일 발송</a>
          <a href="estimate_form.php?id=<?= $id ?>" class="btn-export btn-edit">수정</a>
          <a href="estimate_list.php" class="btn-export btn-list">목록</a>
        </div>
      </div>

      <!-- 기본 정보 -->
      <div class="info-grid">
        <span class="info-label">PAX</span><span class="info-value"><?= (int)($master['pax'] ?? 0) ?></span>
        <span class="info-label">FOC</span><span class="info-value"><?= (int)($master['foc'] ?? 0) ?></span>
        <span class="info-label">총인원</span><span class="info-value"><?= (int)($master['total_pax'] ?? 0) ?></span>
        <span class="info-label">TO</span><span class="info-value"><?= htmlspecialchars($master['to_name'] ?? '') ?></span>

        <span class="info-label">여행 시작일</span><span class="info-value"><?= $master['start_date'] ?? '' ?></span>
        <span class="info-label">여행 종료일</span><span class="info-value"><?= $master['end_date'] ?? '' ?></span>
        <span class="info-label">작성일</span><span class="info-value"><?= $master['wdate'] ?? '' ?></span>
        <span class="info-label">GROUP</span><span class="info-value"><?= htmlspecialchars($master['group_name'] ?? '') ?></span>
      </div>

      <?php
      $section_totals = [];

      /* ========== 1) HOTEL ========== */
      if (!empty($sections['HOTEL'])):
          $hotel_total = 0;
      ?>
      <div class="section-header">1) HOTEL</div>
      <table class="data-table">
        <thead>
          <tr>
            <th width="10%">지역</th>
            <th width="12%">날짜</th>
            <th width="6%">요일</th>
            <th width="30%">호텔명</th>
            <th width="8%">방수</th>
            <th width="12%">요금(USD)</th>
            <th width="6%">박수</th>
            <th width="16%">합계</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($sections['HOTEL'] as $it):
              $etc = json_decode($it['etc_json'] ?? '{}', true) ?: [];
              $hotel_total += (float)($it['sum'] ?? 0);
          ?>
          <tr>
            <td><?= htmlspecialchars($etc['region'] ?? '') ?></td>
            <td><?= $etc['date'] ?? '' ?></td>
            <td><?= $etc['weekday'] ?? '' ?></td>
            <td class="text-left"><?= htmlspecialchars($it['label']." 또는 동급호텔" ?? '') ?></td>
            <td><?= $it['cnt'] ?? 0 ?></td>
            <td class="text-right currency"><?= money($it['unit'] ?? 0) ?></td>
            <td><?= $it['qty'] ?? 0 ?></td>
            <td class="text-right currency"><?= money($it['sum'] ?? 0) ?></td>
          </tr>
          <?php endforeach; ?>
          <tr class="total-row">
            <td colspan="7" class="text-right">HOTEL 소계</td>
            <td class="text-right currency"><?= money($hotel_total) ?></td>
          </tr>
        </tbody>
      </table>
      <?php $section_totals['HOTEL'] = $hotel_total; endif; ?>

      <!-- ========== 2) MEAL ========== -->
      <!-- ========== 2) MEAL (일자별/식사별 매트릭스 | PHP 5.6 호환) ========== -->
<?php
// 연관배열 여부
function is_assoc_array($arr){
  if (!is_array($arr)) return false;
  if ($arr === []) return false;
  return array_keys($arr) !== range(0, count($arr)-1);
}

if (!empty($sections['MEAL'])):
	
  /* 1) 날짜 헤더 구성: master start~end가 있으면 범위, 없으면 items의 dates를 합집합 */
  $dates = array();
  $sd = isset($master['start_date']) ? trim($master['start_date']) : '';
  $ed = isset($master['end_date'])   ? trim($master['end_date'])   : '';
  if ($sd !== '' && $ed !== '' && strtotime($sd)!==false && strtotime($ed)!==false) {
    $cur = strtotime($sd); $end = strtotime($ed);
    while ($cur <= $end) { $dates[] = date('Y-m-d',$cur); $cur = strtotime('+1 day',$cur); }
  } else {
    // start/end 없으면 items의 dates 맵에서 수집
    $dateSet = array();
    foreach ($sections['MEAL'] as $it) {
      $etc = json_decode($it['etc_json'] ?? '{}', true) ?: [];
      if (!empty($etc['dates']) && is_array($etc['dates'])) {
        if (is_assoc_array($etc['dates'])) {
          foreach ($etc['dates'] as $d=>$v) $dateSet[$d] = true;
        } else {
          foreach ($etc['dates'] as $d) $dateSet[$d] = true;
        }
      }
    }
    $dates = array_keys($dateSet);
    sort($dates);
  }
  if (empty($dates)) { // 완전 비었으면 화면용 3칸
    $base=time(); for($i=0;$i<3;$i++) $dates[] = date('Y-m-d', strtotime('+'.$i.' day',$base));
  }

  /* 2) 매트릭스/단가/인원 */
  $mealTypes  = array('조식','중식','석식');
  $mealUnits  = array('조식'=>0,'중식'=>0,'석식'=>0);    // unit_per_pax
  $mealPax    = array('조식'=>0,'중식'=>0,'석식'=>0);    // pax
  $mealMatrix = array('조식'=>array(),'중식'=>array(),'석식'=>array()); // 날짜별 횟수

  foreach ($dates as $d) foreach ($mealTypes as $mt) $mealMatrix[$mt][$d]=0;

  foreach ($sections['MEAL'] as $it) {
    $label = (string)($it['label'] ?? '');
    $etc   = json_decode($it['etc_json'] ?? '{}', true) ?: [];

    // 타입 판별: etc.meal_type 우선, 없으면 라벨에서 추정
    $type = '';
    if (!empty($etc['meal_type'])) {
      $type = $etc['meal_type'];
    } else {
      $has_mb = function_exists('mb_strpos');
      if ($has_mb ? mb_strpos($label,'조식')!==false : strpos($label,'조식')!==false) $type='조식';
      elseif ($has_mb ? mb_strpos($label,'중식')!==false : strpos($label,'중식')!==false) $type='중식';
      elseif ($has_mb ? mb_strpos($label,'석식')!==false : strpos($label,'석식')!==false) $type='석식';
    }
    if (!in_array($type,$mealTypes,true)) continue;

    // 단가/인원 (등장하는 첫 유효값 사용)
    if (isset($etc['unit_per_pax']) && is_numeric($etc['unit_per_pax']) && $mealUnits[$type]<=0)
      $mealUnits[$type] = (float)$etc['unit_per_pax'];
    if (isset($etc['pax']) && is_numeric($etc['pax']) && $mealPax[$type]<=0)
      $mealPax[$type] = (int)$etc['pax'];

    // 날짜별 횟수: dates(맵/리스트 모두 지원)
    if (!empty($etc['dates']) && is_array($etc['dates'])) {
      if (is_assoc_array($etc['dates'])) {
        foreach ($etc['dates'] as $d=>$cnt) if (isset($mealMatrix[$type][$d])) $mealMatrix[$type][$d] += (int)$cnt;
      } else {
        foreach ($etc['dates'] as $d) if (isset($mealMatrix[$type][$d])) $mealMatrix[$type][$d] += 1;
      }
    }
  }

  /* 3) 합계 계산: (unit_per_pax × pax) × 날짜별 총합 */
  $meal_total = 0.0;
  $rowTotals  = array(); $perPersonTotal=0; $paxDisplay=0;
  foreach ($mealTypes as $mt) {
    $occ = 0; foreach ($dates as $d) $occ += (int)$mealMatrix[$mt][$d];
    $rowTotals[$mt] = ((float)$mealUnits[$mt]) * ((int)$mealPax[$mt]) * $occ;
    $meal_total += $rowTotals[$mt];
    $perPersonTotal += (float)$mealUnits[$mt];
    if ($paxDisplay===0 && $mealPax[$mt]>0) $paxDisplay = (int)$mealPax[$mt];
  }
  $section_totals['MEAL'] = $meal_total;
?>
<div class="section-header">2) MEAL</div>
<table class="data-table">
  <thead>
    <tr>
      <th width="10%">구분</th>
      <?php foreach ($dates as $d): ?><th><?= htmlspecialchars($d,ENT_QUOTES,'UTF-8') ?></th><?php endforeach; ?>
      <th>일인단가</th><th>인원</th><th>합계</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($mealTypes as $mt): ?>
      <tr>
        <td><?= $mt ?></td>
        <?php foreach ($dates as $d): ?>
          <td><input type="text" value="<?= (int)$mealMatrix[$mt][$d] ?>" style="width:50px;text-align:center" readonly></td>
        <?php endforeach; ?>
        <td class="text-right currency"><?= $mealUnits[$mt] ?></td>
        <td><?= (int)$mealPax[$mt] ?></td>
        <td class="text-right currency"><?= money($rowTotals[$mt]) ?></td>
      </tr>
    <?php endforeach; ?>
    <tr class="total-row">
      <td class="text-right" colspan="<?= 1+count($dates) ?>">MEAL 소계</td>
      <td class="text-right currency"><?= money($perPersonTotal) ?></td>
      <td><?= (int)$paxDisplay ?></td>
      <td class="text-right currency"><?= money($meal_total) ?></td>
    </tr>
  </tbody>
</table>
<?php endif; ?>


  
<?php
if (!empty($sections['TRANSPORT'])):

  /* 1) 날짜 헤더: master start~end, 없으면 items의 dates 합집합 */
  $dates = array();
  $sd = isset($master['start_date']) ? trim($master['start_date']) : '';
  $ed = isset($master['end_date'])   ? trim($master['end_date'])   : '';
  if ($sd !== '' && $ed !== '' && strtotime($sd)!==false && strtotime($ed)!==false) {
    $cur=strtotime($sd); $end=strtotime($ed);
    while ($cur <= $end) { $dates[] = date('Y-m-d',$cur); $cur = strtotime('+1 day',$cur); }
  } else {
    $dateSet = array();
    foreach ($sections['TRANSPORT'] as $it){
      $etc = json_decode($it['etc_json'] ?? '{}', true) ?: [];
      if (!empty($etc['dates']) && is_array($etc['dates'])) {
        // 맵/리스트 모두 지원
        if (array_keys($etc['dates']) !== range(0, count($etc['dates'])-1)) {
          foreach ($etc['dates'] as $d=>$v) $dateSet[$d]=true;
        } else {
          foreach ($etc['dates'] as $d) $dateSet[$d]=true;
        }
      }
    }
    $dates = array_keys($dateSet); sort($dates);
  }
  if (empty($dates)) { $base=time(); for($i=0;$i<3;$i++) $dates[] = date('Y-m-d', strtotime('+'.$i.' day',$base)); }

  /* 2) 차량(항목)별 매트릭스
       - 단가(unit): 테이블의 item.unit(USD/대/일) 사용
       - 차량수(vehicles): etc.unit_per_car 사용 (없으면 item.cnt fallback)
       - 날짜별 횟수: etc.dates 의 값(숫자)을 그대로 더함; 리스트면 +1
  */
  $rows = array(); // $rows[label] = ['unit'=>0.0,'vehicles'=>0.0,'matrix'=>[date=>0]]
  foreach ($sections['TRANSPORT'] as $it) {
    $label = trim((string)($it['label'] ?? '차량'));
    if ($label==='') $label = '차량';

    $etc = json_decode($it['etc_json'] ?? '{}', true) ?: [];

    $unitUSD  = (isset($it['unit']) && is_numeric($it['unit'])) ? (float)$it['unit'] : 0.0; // 단가(USD/대/일)
    $vehicles = 0.0;
    if (isset($etc['unit_per_car']) && is_numeric($etc['unit_per_car'])) $vehicles = (float)$etc['unit_per_car'];
    elseif (isset($it['cnt']) && is_numeric($it['cnt']))                 $vehicles = (float)$it['cnt'];

    if (!isset($rows[$label])) {
      $rows[$label] = array('unit'=>0.0,'vehicles'=>0.0,'matrix'=>array());
      foreach ($dates as $d) $rows[$label]['matrix'][$d]=0;
    }

    if ($unitUSD  > 0) $rows[$label]['unit']     = $unitUSD;
    if ($vehicles > 0) $rows[$label]['vehicles'] = $vehicles;

    // 날짜별 횟수 누적
    if (!empty($etc['dates']) && is_array($etc['dates'])) {
      // 연관 맵: {"2025-09-29":10, ...}
      if (array_keys($etc['dates']) !== range(0, count($etc['dates'])-1)) {
        foreach ($etc['dates'] as $d=>$cnt) {
          if (isset($rows[$label]['matrix'][$d])) $rows[$label]['matrix'][$d] += (int)$cnt;
        }
      } else {
        // 리스트: ["2025-10-01","2025-10-02"]
        foreach ($etc['dates'] as $d) {
          if (isset($rows[$label]['matrix'][$d])) $rows[$label]['matrix'][$d] += 1;
        }
      }
    }
  }

  /* 3) 합계: (unit × vehicles) × Σ(날짜별 횟수) */
  $transport_total = 0.0; $rowTotals=array();
  foreach ($rows as $name=>$r) {
    $occ = 0; foreach ($dates as $d) $occ += (int)$r['matrix'][$d];
    $rowTotals[$name] = ((float)$r['vehicles']) * $occ;
    $transport_total += $rowTotals[$name];
  }
  $section_totals['TRANSPORT'] = $transport_total;
?>
<div class="section-header">3) TRANSPORTATION</div>
<table class="data-table">
  <thead>
    <tr>
      <th width="12%">차량/항목</th>
      <?php foreach ($dates as $d): ?><th><?= htmlspecialchars($d,ENT_QUOTES,'UTF-8') ?></th><?php endforeach; ?>
      <th width="8%">차량수</th>
      <th width="12%">합계</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($rows as $name=>$r): ?>
      <tr>
        <td class="text-left"><?= htmlspecialchars($name,ENT_QUOTES,'UTF-8') ?></td>
        <?php foreach ($dates as $d): ?>
          <td><input type="text" value="<?= (int)$r['matrix'][$d] ?>" style="width:50px;text-align:center" readonly></td>
        <?php endforeach; ?>
        <td><?= number_format($r['vehicles'], 2) ?></td>
        <td class="text-right currency"><?= money($rowTotals[$name]) ?></td>
      </tr>
    <?php endforeach; ?>
    <tr class="total-row">
      <td class="text-right" colspan="<?= 1+count($dates) ?>">TRANSPORTATION 소계</td>
      <td><!-- 단가 자리 --></td>
      <td class="text-right currency"><?= money($transport_total) ?></td>
    </tr>
  </tbody>
</table>
<?php endif; ?>

<?php
if (!empty($sections['OVERTIME'])):

  // 1) 날짜 헤더
  $ot_dates = array();
  $sd = isset($master['start_date']) ? trim($master['start_date']) : '';
  $ed = isset($master['end_date'])   ? trim($master['end_date'])   : '';
  if ($sd !== '' && $ed !== '' && strtotime($sd)!==false && strtotime($ed)!==false) {
    $cur=strtotime($sd); $end=strtotime($ed);
    while ($cur <= $end) { $ot_dates[] = date('Y-m-d',$cur); $cur = strtotime('+1 day',$cur); }
  } else {
    $dateSet = array();
    foreach ($sections['OVERTIME'] as $it){
      $etc = json_decode($it['etc_json'] ?? '{}', true) ?: [];
      if (!empty($etc['dates']) && is_array($etc['dates'])) {
        $isAssoc = array_keys($etc['dates']) !== range(0, count($etc['dates'])-1);
        if ($isAssoc) { foreach ($etc['dates'] as $d=>$v) $dateSet[$d]=true; }
        else          { foreach ($etc['dates'] as $d)     $dateSet[$d]=true; }
      }
    }
    $ot_dates = array_keys($dateSet); sort($ot_dates);
  }
  if (empty($ot_dates)) { $base=time(); for($i=0;$i<3;$i++) $ot_dates[] = date('Y-m-d', strtotime('+'.$i.' day',$base)); }

  // 2) 항목 매트릭스: unit(시간/요율), targets(대상/수량), matrix(날짜별 횟수)
  $ot_rows = array(); // $ot_rows[label] = ['unit'=>0.0,'targets'=>0.0,'matrix'=>[date=>0]]
  foreach ($sections['OVERTIME'] as $it) {
    $label = trim((string)($it['label'] ?? '오버타임'));
    if ($label==='') $label = '오버타임';

    $etc = json_decode($it['etc_json'] ?? '{}', true) ?: [];

    // 단가(시간/요율): item.unit 우선
    $unitRate = (isset($it['unit']) && is_numeric($it['unit'])) ? (float)$it['unit'] : 0.0;

    // 대상 수(명/대/회 등): etc.unit_per_rate 또는 item.cnt
    $targets  = 0.0;
    if (isset($etc['unit_per_rate']) && is_numeric($etc['unit_per_rate'])) $targets = (float)$etc['unit_per_rate'];
    elseif (isset($it['cnt']) && is_numeric($it['cnt']))                    $targets = (float)$it['cnt'];

    if (!isset($ot_rows[$label])) {
      $ot_rows[$label] = array('unit'=>0.0,'targets'=>0.0,'matrix'=>array());
      foreach ($ot_dates as $d) $ot_rows[$label]['matrix'][$d]=0;
    }

    if ($unitRate > 0) $ot_rows[$label]['unit']    = $unitRate;
    if ($targets  > 0) $ot_rows[$label]['targets'] = $targets;

    // 날짜별 횟수 누적
    if (!empty($etc['dates']) && is_array($etc['dates'])) {
      $isAssoc = array_keys($etc['dates']) !== range(0, count($etc['dates'])-1);
      if ($isAssoc) {
        foreach ($etc['dates'] as $d=>$cnt) {
          if (isset($ot_rows[$label]['matrix'][$d])) $ot_rows[$label]['matrix'][$d] += (int)$cnt;
        }
      } else {
        foreach ($etc['dates'] as $d) {
          if (isset($ot_rows[$label]['matrix'][$d])) $ot_rows[$label]['matrix'][$d] += 1;
        }
      }
    }
  }

  // 3) 합계 계산: (unit × targets) × Σ(날짜별 횟수)
  $overtime_total = 0.0; $ot_rowTotals = array();
  foreach ($ot_rows as $name=>$r) {
    $occ = 0; foreach ($ot_dates as $d) { $occ += (int)$r['matrix'][$d]; }
    $ot_rowTotals[$name] = ((float)$r['targets']) * $occ;
    $overtime_total += $ot_rowTotals[$name];
  }
  $section_totals['OVERTIME'] = $overtime_total;
  
?>
<div class="section-header">3-1) OVERTIME</div>
<table class="data-table">
  <thead>
    <tr>
      <th width="12%">오버타임</th>
      <?php foreach ($ot_dates as $d): ?><th><?= htmlspecialchars($d,ENT_QUOTES,'UTF-8') ?></th><?php endforeach; ?>
      <th width="10%">타임</th>
      <th width="12%">합계</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($ot_rows as $name=>$r): ?>
      <tr>
        <td class="text-left"><?= htmlspecialchars($name,ENT_QUOTES,'UTF-8') ?></td>
        <?php foreach ($ot_dates as $d): ?>
          <td><input type="text" value="<?= (int)$r['matrix'][$d] ?>" style="width:50px;text-align:center" readonly></td>
        <?php endforeach; ?>
        <td><?= number_format($r['targets'], 2) ?></td>
        <td class="text-right currency"><?= money($ot_rowTotals[$name]) ?></td>
      </tr>
    <?php endforeach; ?>
    <tr class="total-row">
      <td class="text-right" colspan="<?= 1 + count($ot_dates) ?>">OVERTIME 소계</td>
      <td><!-- 타임 자리 --></td>
      <td class="text-right currency"><?= money($overtime_total) ?></td>
    </tr>
  </tbody>
</table>
<?php endif; ?>


      <!-- ========== 4) TICKET ========== -->
      <?php if (!empty($sections['TICKET'])):
          $tk_total = 0;
      ?>
      <div class="section-header">4) TICKET</div>
      <table class="data-table">
        <thead>
          <tr>
            <th width="40%">티켓명</th>
            <th width="20%">단가</th>
            <th width="20%">매수/인원</th>
            <th width="20%">합계</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($sections['TICKET'] as $it):
              $tk_total += (float)($it['sum'] ?? 0);
          ?>
          <tr>
            <td class="text-left"><?= htmlspecialchars($it['label'] ?? '') ?></td>
            <td class="text-right currency"><?= money($it['unit'] ?? 0) ?></td>
            <td><?= $it['cnt'] ?? 0 ?></td>
            <td class="text-right currency"><?= money($it['sum'] ?? 0) ?></td>
          </tr>
          <?php endforeach; ?>
          <tr class="total-row">
            <td colspan="3" class="text-right">TICKET 소계</td>
            <td class="text-right currency"><?= money($tk_total) ?></td>
          </tr>
        </tbody>
      </table>
      <?php $section_totals['TICKET'] = $tk_total; endif; ?>

      <!-- ========== 5) GUIDE ========== -->
      <?php if (!empty($sections['GUIDE'])):
          $gd_total = 0;
      ?>
      <div class="section-header">5) GUIDE</div>
      <table class="data-table">
        <thead>
          <tr>
            <th width="40%">가이드/설명</th>
            <th width="20%">일당/단가</th>
            <th width="20%">일수</th>
            <th width="20%">합계</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($sections['GUIDE'] as $it):
              $gd_total += (float)($it['sum'] ?? 0);
          ?>
          <tr>
            <td class="text-left"><?= htmlspecialchars($it['label'] ?? '') ?></td>
            <td class="text-right currency"><?= money($it['unit'] ?? 0) ?></td>
            <td><?= $it['qty'] ?? 0 ?></td>
            <td class="text-right currency"><?= money($it['sum'] ?? 0) ?></td>
          </tr>
          <?php endforeach; ?>
          <tr class="total-row">
            <td colspan="3" class="text-right">GUIDE 소계</td>
            <td class="text-right currency"><?= money($gd_total) ?></td>
          </tr>
        </tbody>
      </table>
      <?php $section_totals['GUIDE'] = $gd_total; endif; ?>

      <!-- ========== 6) ETC ========== -->
      <?php if (!empty($sections['ETC'])):
          $etc_total = 0;
      ?>
      <div class="section-header">6) ETC</div>
      <table class="data-table">
        <thead>
          <tr>
            <th width="50%">항목</th>
            <th width="15%">단가</th>
            <th width="15%">수량</th>
            <th width="20%">합계</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($sections['ETC'] as $it):
              $etc_total += (float)($it['sum'] ?? 0);
          ?>
          <tr>
            <td class="text-left"><?= htmlspecialchars($it['label'] ?? '') ?></td>
            <td class="text-right currency"><?= money($it['unit'] ?? 0) ?></td>
            <td><?= $it['qty'] ?? 0 ?></td>
            <td class="text-right currency"><?= money($it['sum'] ?? 0) ?></td>
          </tr>
          <?php endforeach; ?>
          <tr class="total-row">
            <td colspan="3" class="text-right">ETC 소계</td>
            <td class="text-right currency"><?= money($etc_total) ?></td>
          </tr>
        </tbody>
      </table>
      <?php $section_totals['ETC'] = $etc_total; endif; ?>

      <!-- ========== 7) TIP ========== -->
      <?php if (!empty($sections['TIP'])):
          $tip_total = 0;
      ?>
      <div class="section-header">7) TIP</div>
      <table class="data-table">
        <thead>
          <tr>
            <th width="50%">항목</th>
            <th width="15%">단가</th>
            <th width="15%">수량/인원</th>
            <th width="20%">합계</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($sections['TIP'] as $it):
              $tip_total += (float)($it['sum'] ?? 0);
          ?>
          <tr>
            <td class="text-left"><?= htmlspecialchars($it['label'] ?? '') ?></td>
            <td class="text-right currency"><?= money($it['unit'] ?? 0) ?></td>
            <td><?= $it['cnt'] ?? 0 ?></td>
            <td class="text-right currency"><?= money($it['sum'] ?? 0) ?></td>
          </tr>
          <?php endforeach; ?>
          <tr class="total-row">
            <td colspan="3" class="text-right">TIP 소계</td>
            <td class="text-right currency"><?= money($tip_total) ?></td>
          </tr>
        </tbody>
      </table>
      <?php $section_totals['TIP'] = $tip_total; endif; ?>

      <!-- ========== 8) PROFIT ========== -->
      <?php
        $profit_total_items = 0;
        if (!empty($sections['PROFIT'])):
          foreach ($sections['PROFIT'] as $it) $profit_total_items += (float)($it['sum'] ?? 0);
      ?>
      <div class="section-header">8) PROFIT</div>
      <table class="data-table">
        <thead>
          <tr>
            <th width="55%">항목</th>
            <th width="15%">단가</th>
            <th width="10%">수량</th>
            <th width="20%">합계</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($sections['PROFIT'] as $it): ?>
          <tr>
            <td class="text-left"><?= htmlspecialchars($it['label'] ?? '') ?></td>
            <td class="text-right currency"><?= money($it['unit'] ?? 0) ?></td>
            <td><?= $it['qty'] ?? 0 ?></td>
            <td class="text-right currency"><?= money($it['sum'] ?? 0) ?></td>
          </tr>
          <?php endforeach; ?>
          <tr class="total-row">
            <td colspan="3" class="text-right">PROFIT 소계(아이템)</td>
            <td class="text-right currency"><?= money($profit_total_items) ?></td>
          </tr>
        </tbody>
      </table>
      <?php endif;

      // master 테이블의 profit 필드가 있으면 별도 표시
      $master_profit = (float)($master['profit'] ?? 0);
      $master_profit_memo = trim((string)($master['profit_memo'] ?? ''));
      if ($master_profit > 0 || $master_profit_memo !== ''): ?>
        <table class="data-table">
          <tbody>
            <tr class="total-row">
              <td class="text-left" style="width:80%;">PROFIT(마스터): <?= htmlspecialchars($master_profit_memo) ?></td>
              <td class="text-right currency" style="width:20%;"><?= money($master_profit) ?></td>
            </tr>
          </tbody>
        </table>
      <?php
      endif;
      $section_totals['PROFIT'] = ($section_totals['PROFIT'] ?? 0) + $profit_total_items + $master_profit;
      ?>

      <!-- ========== 요약/최종 합계 ========== -->
      <?php
        // 아이템 합계 기준 자동 총합(마스터 grand_total이 0이면 자동 계산 표시용)
        $auto_grand = 0;
        foreach ($section_totals as $v) $auto_grand += (float)$v;
        $grand_total = is_numeric($master['grand_total'] ?? null) ? (float)$master['grand_total'] : 0.0;
        $per_pax    = is_numeric($master['per_pax'] ?? null) ? (float)$master['per_pax'] : 0.0;

        // per_pax 자동 보정(마스터에 없고 total_pax>0이면)
        if ($per_pax <= 0 && $grand_total > 0 && (int)($master['total_pax'] ?? 0) > 0) {
          $per_pax = $grand_total / (int)$master['total_pax'];
        } elseif ($per_pax <= 0 && $grand_total <= 0 && $auto_grand > 0 && (int)($master['total_pax'] ?? 0) > 0) {
          $per_pax = $auto_grand / (int)$master['total_pax'];
        }
      ?>

      <div class="summary-section">
        <div class="summary-pills">
          <span class="pill">HOTEL: <?= money($section_totals['HOTEL'] ?? 0) ?></span>
          <span class="pill">MEAL: <?= money($section_totals['MEAL'] ?? 0) ?></span>
          <span class="pill">TRANSPORT: <?= money($section_totals['TRANSPORT'] ?? 0) ?></span>
          <span class="pill">TICKET: <?= money($section_totals['TICKET'] ?? 0) ?></span>
          <span class="pill">GUIDE: <?= money($section_totals['GUIDE'] ?? 0) ?></span>
          <span class="pill">ETC: <?= money($section_totals['ETC'] ?? 0) ?></span>
          <span class="pill">TIP: <?= money($section_totals['TIP'] ?? 0) ?></span>
          <span class="pill">PROFIT: <?= money($section_totals['PROFIT'] ?? 0) ?></span>
        </div>

        <div class="final-totals">
          <div class="final-total-item">
            <div class="final-total-label">10) TOTAL TOUR FEE</div>
            <div class="final-total-amount">
              <?= money($grand_total > 0 ? $grand_total : $auto_grand) ?>
            </div>
          </div>
          <div class="final-total-item">
            <div class="final-total-label">11) 1인당 요금</div>
            <div class="final-total-amount">
              <?= money($per_pax) ?>
            </div>
          </div>
        </div>
      </div>

    </div><!-- /.breakdown-wrapper -->
  </div>
</div>

<script>
function sendBreakdownEmail(estimateId) {
  const userId = prompt("이메일을 받을 사용자 ID를 입력하세요:");
  if (userId) {
    if (confirm(`${userId}님에게 견적서 이메일을 발송하시겠습니까?`)) {
      window.open(
        `send_breakdown_email.php?action=send_email&user_id=${encodeURIComponent(userId)}&estimate_id=${encodeURIComponent(estimateId)}`,
        '_blank',
        'width=500,height=300'
      );
    }
  }
}
</script>
