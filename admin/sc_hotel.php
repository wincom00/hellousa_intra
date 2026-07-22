<?php
include "include/header.php";

// ── 디버그(필요 시 URL에 ?debug=1)
if (isset($_GET['debug'])) {
  ini_set('display_errors', 1);
  ini_set('display_startup_errors', 1);
  error_reporting(E_ALL);
}

// ── 로그인 체크 (키는 반드시 문자열 인덱스)
if (empty($_COOKIE['MEMLOGIN_ADMIN_HELLO'])) {
  echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
  exit;
}

// ── DB 커넥션 보장
if (!isset($dbConn) || !($dbConn instanceof mysqli)) {
  @include_once __DIR__ . '/include/inc_base.php';
}
if (!isset($dbConn) || !($dbConn instanceof mysqli)) {
  http_response_code(500);
  exit('DB connection not available');
}

/* =========================
   입력값 정리 (PHP 7.4)
   ========================= */
$startyear = isset($_POST['startyear']) && $_POST['startyear'] !== '' ? (int)$_POST['startyear'] : (int)date('Y');

// POST 우선, 없으면 기존 변수 사용
$StartYMD = isset($_POST['StartYMD']) ? $_POST['StartYMD'] : (isset($StartYMD) ? $StartYMD : null);
$EndYMD   = isset($_POST['EndYMD'])   ? $_POST['EndYMD']   : (isset($EndYMD)   ? $EndYMD   : null);

// yyyy-mm-dd 검사 + 기본값(이번 달 1일~말일)
$reDate = '/^\d{4}-\d{2}-\d{2}$/';
if (!$StartYMD || !preg_match($reDate, $StartYMD) || !$EndYMD || !preg_match($reDate, $EndYMD)) {
  $first = new DateTime('first day of this month');
  $last  = new DateTime('last day of this month');
  $StartYMD = $first->format('Y-m-d');
  $EndYMD   = $last->format('Y-m-d');
}

// 역전 방지
try {
  $dStart = new DateTime($StartYMD);
  $dEnd   = new DateTime($EndYMD);
  if ($dEnd < $dStart) { $tmp = $dStart; $dStart = $dEnd; $dEnd = $tmp; }
} catch (Exception $e) {
  $dStart = new DateTime('first day of this month');
  $dEnd   = new DateTime('last day of this month');
  $StartYMD = $dStart->format('Y-m-d');
  $EndYMD   = $dEnd->format('Y-m-d');
}

/* =========================
   날짜 배열 생성 (포함 범위)
   ========================= */
$dates = [];
$endPlus = clone $dEnd; $endPlus->modify('+1 day');
$iter = new DatePeriod($dStart, new DateInterval('P1D'), $endPlus);
foreach ($iter as $d) { $dates[] = $d->format('Y-m-d'); }
$totalDay = count($dates);
$today = date('Y-m-d');

/* =========================
   호텔 목록 + 기간 총합
   ========================= */
$hotels = []; // [hotel_code => ['code'=>..., 'name'=>..., 'total_cnt'=>int]]
if ($totalDay > 0) {
  $sqlHotels = "
    SELECT ha.hotel_code, ph.h_name, SUM(ha.pcnt) AS total_cnt
    FROM hotel_assign ha
    JOIN product_hotel ph ON ph.h_code = ha.hotel_code
    WHERE ha.stDate_sub BETWEEN ? AND ?
    GROUP BY ha.hotel_code, ph.h_name
    ORDER BY ph.h_name ASC
  ";
  $stmt = $dbConn->prepare($sqlHotels);
  if (!$stmt) { http_response_code(500); exit('SQL prepare failed (hotels): '.$dbConn->error); }
  $stmt->bind_param('ss', $StartYMD, $EndYMD);
  $stmt->execute();
  $rs = $stmt->get_result();
  while ($r = $rs->fetch_assoc()) {
    $hotels[$r['hotel_code']] = [
      'code' => $r['hotel_code'],
      'name' => $r['h_name'],
      'total_cnt' => (int)$r['total_cnt']
    ];
  }
  $stmt->close();
}

/* =========================
   날짜별 카운트 맵(호텔당 1쿼리)
   ========================= */
$cellsByHotel = []; // $cellsByHotel[hotel_code][date] = ['cnt'=>int, 'rcd'=>string]
if (!empty($hotels)) {
  // 한 번에 모두 긁어오고 PHP에서 나누는 방법 (범위가 크지 않다면 이것도 좋습니다)
  $sqlAll = "
    SELECT ha.hotel_code, ha.stDate_sub, SUM(ha.pcnt) AS cnt, MIN(ha.sub_eCode) AS rcd
    FROM hotel_assign ha
    WHERE ha.stDate_sub BETWEEN ? AND ?
    GROUP BY ha.hotel_code, ha.stDate_sub
  ";
  $stmt = $dbConn->prepare($sqlAll);
  if (!$stmt) { http_response_code(500); exit('SQL prepare failed (cells): '.$dbConn->error); }
  $stmt->bind_param('ss', $StartYMD, $EndYMD);
  $stmt->execute();
  $rs = $stmt->get_result();
  while ($r = $rs->fetch_assoc()) {
    $h = $r['hotel_code'];
    $d = $r['stDate_sub'];
    if (!isset($cellsByHotel[$h])) $cellsByHotel[$h] = [];
    $cellsByHotel[$h][$d] = [
      'cnt' => (int)$r['cnt'],
      'rcd' => (string)$r['rcd']
    ];
  }
  $stmt->close();
}

/* =========================
   엑셀 다운로드 모드
   ========================= */
$mode = isset($_POST['mode']) ? $_POST['mode'] : (isset($_GET['mode']) ? $_GET['mode'] : '');
if ($mode === 'down') {
  header("Content-type: application/vnd.ms-excel; charset=UTF-8");
  header("Content-Disposition: attachment; filename=sc_".date('Ymd').".xls");
  header("Content-Description: PHP5 Generated Data");
  echo "<meta http-equiv='Content-Type' content='application/vnd.ms-excel; charset=utf-8'/>";
}
?>
<link rel="stylesheet" type="text/css" href="lib/datatables.css"/>
<style>
  .tableFixHead          { height: 600px; }
  .tableFixHead thead th { top: 0; background:#eee; border:0.05em solid #848484; }
  table.dataTable thead tr th,
  table.dataTable thead td,
  table.dataTable tbody tr td {
    border-bottom: 1px solid #111;
    padding: 1px 1px;
  }
  div.dataTables_wrapper { margin: 0 auto; }
  .sticky-col { position: sticky; left: 0; background: #fff; z-index: 3; }
  .first-col { min-width: 150px; max-width: 250px; }
</style>

<div id="contentwrapper" class="reservationDetailForm">
  <div class="main_content">
    <div id="jCrumbs" class="breadCrumb module">
      <ul>
        <li><a href="/"><i class="glyphicon glyphicon-home"></i></a></li>
        <li><a href="#">호텔스케줄표</a></li>
      </ul>
    </div>

    <div class="row">
      <div class="col-sm-12 col-md-12">
        <form action="<?= htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') ?>" name="frmName" id="frmName" method="post">
          <input type="hidden" name="mode" value="search">
          <table class="table table-bordered table-condensed">
            <tr>
              <td width="10%" class="titletd text-center">출발일</td>
              <td width="40%">
                <div class="row">
                  <div class="col-sm-12">
                    <div class="input-group input-group-sm">
                      <div class="row">
                        <div class="col-sm-6">
                          <input type="text" id="startDate1" name="StartYMD" class="inpubase tourDate1" placeholder="시작일" value="<?= htmlspecialchars($StartYMD, ENT_QUOTES, 'UTF-8') ?>" autocomplete="off" />
                        </div>
                        <div class="col-sm-6">
                          <input type="text" id="endDate" name="EndYMD" class="inpubase tourDate1" placeholder="마지막일" value="<?= htmlspecialchars($EndYMD, ENT_QUOTES, 'UTF-8') ?>" autocomplete="off" />
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </td>
            </tr>
          </table>

          <table class="table table-bordered table-condensed">
            <tr>
              <td width="5%" class="text-center">검색년도</td>
              <td>
                <div class="row no-nav">
                  <div class="col-sm-2">
                    <input type="text" id="startyear" name="startyear" class="inpubase tourDate3" placeholder="년도" value="<?= htmlspecialchars((string)$startyear, ENT_QUOTES, 'UTF-8') ?>" autocomplete="off" />
                  </div>
                  <div class="col-sm-6">
                    <ul class="pagination non-nav" style="margin:0;">
                      <?php for ($m=1; $m<=12; $m++): ?>
                        <li class="disabled" style="display:inline-block;margin-right:4px;">
                          <span><a href="javascript:cal('<?= $m ?>')"><?= $m ?>월</a></span>
                        </li>
                      <?php endfor; ?>
                    </ul>
                  </div>
                  <div class="col-sm-2">
                    <button type="submit" class="btn btn-primary btn-sm text-left btn1">검색</button>
                  </div>
                </div>
              </td>
            </tr>
          </table>
        </form>

        <br />
        <div class="row">
          <div class="col-sm-12 tableFixHead">
            <table width="100%" id="guide_table" class="stripe row-border order-column text-center">
              <thead>
                <tr>
                  <th class="sticky-col first-col" style="border:0.05em solid #848484;">상 품 명</th>
                  <?php
                  $weekNames = ['일','월','화','수','목','금','토'];
                  foreach ($dates as $d) {
                    $w = (int)date('w', strtotime($d));
                    $yoil = $weekNames[$w];
                    $m = date('m', strtotime($d));
                    $day = date('d', strtotime($d));
                    $label = "{$m}/{$day}<br>({$yoil})";
                    $color = ($yoil === '일') ? 'red' : (($yoil === '토') ? 'blue' : 'inherit');
                    $cell = "<a href='memo_list.php?stdate={$d}'><span style=\"font-size:7pt;color:{$color};\">{$label}</span></a>";
                    $bg = ($d === $today) ? "background-color:#DDA0DD;" : "";
                    echo "<th class='sticky-col' style='border:0.05em solid #848484; {$bg}'>{$cell}</th>";
                  }
                  ?>
                </tr>
              </thead>

              <tbody>
              <?php if (!empty($hotels)): ?>
                <?php foreach ($hotels as $code => $h): ?>
                  <tr>
                    <td class="sticky-col first-col" style="border:0.05em solid #848484; text-align:left;">
                      <span style="font-size:8pt">
                        &nbsp;<?= htmlspecialchars($h['code'], ENT_QUOTES, 'UTF-8') ?>
                        &nbsp;<span style="color:red">(<?= (int)$h['total_cnt'] ?>)</span><br/>
                        <b>&nbsp;<?= htmlspecialchars($h['name'], ENT_QUOTES, 'UTF-8') ?></b>
                      </span>
                    </td>
                    <?php
                      $rowMap = isset($cellsByHotel[$code]) ? $cellsByHotel[$code] : [];
                      foreach ($dates as $d):
                        $cell = isset($rowMap[$d]) ? $rowMap[$d] : null;
                        $cnt  = $cell ? (int)$cell['cnt'] : 0;
                        $rcd  = $cell ? (string)$cell['rcd'] : '';
                        $bg   = ($d === $today) ? '#DDA0DD' : (($cnt > 0) ? '#FFFF99' : '#FFFFFF');
                        $content = ($cnt > 0)
                          ? "<a href=\"javascript:openwin('{$d}','".htmlspecialchars($rcd, ENT_QUOTES, 'UTF-8')."','".htmlspecialchars($code, ENT_QUOTES, 'UTF-8')."')\"><span style='font-size:8pt;color:#000;'>{$cnt}</span></a>"
                          : "&nbsp;";
                    ?>
                      <td style="width:10px !important; border:0.05em solid #848484; background: <?= $bg ?>; text-align:center;">
                        <?= $content ?>
                      </td>
                    <?php endforeach; ?>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="<?= 1 + $totalDay ?>">표시할 데이터가 없습니다.</td></tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div><!-- col -->
    </div><!-- row -->
  </div><!-- main_content -->
</div>

<?php include "include/side_m.php"; ?>

<script>
$(function () {
  $('.tourDate1').datepicker({ format: "yyyy-mm-dd", autoclose: true });
  $('.tourDate3').datepicker({ minViewMode: 2, format: "yyyy", autoclose: true });

  var table = $('#guide_table').DataTable({
    scrollY: 600,
    scrollX: true,
    scrollCollapse: true,
    paging: false,
    fixedHeader: true,
    ordering: false,
    autoWidth: false,
    columnDefs: [{ width: 150, targets: 0 }],
    fixedColumns: true
  });
  table.columns.adjust().draw();
});

function openwin(stdate, rcd, h_code) {
  var winName = "all_1";
  window.open(
    "hotel_customer.php?division=<?= isset($division) ? $division : '' ?>&pdx=<?= isset($pdx) ? $pdx : '' ?>&sub=<?= isset($sub) ? $sub : '' ?>&stdate=" + encodeURIComponent(stdate) + "&rcode=" + encodeURIComponent(rcd) + "&h_code=" + encodeURIComponent(h_code),
    winName,
    "width=1090px,height=700,scrollbars=1"
  );
}
function numberOfDays(month, year) {
  month = parseInt(month,10);
  year = parseInt(year,10);
  var d = new Date(year, month, 0);
  return d.getDate();
}
function cal(mon) {
  mon = parseInt(mon,10);
  if (mon < 10) mon = "0" + mon;
  var yr = $("#startyear").val();
  var st = yr + "-" + mon + "-01";
  $("#startDate1").val(st);
  var lastday = numberOfDays(mon, yr);
  var ed = yr + "-" + mon + "-" + lastday;
  $("#endDate").val(ed);
  $("#frmName").submit();
}
</script>
</body>
</html>
