<?php
// base_reservation_m.php — 튜닝 버전 (N+1 제거, 날짜 PHP생성, 집계쿼리 2회)

// 공용 include
include "include/header.php";

// 로그인체크
if (empty($_COOKIE['MEMLOGIN_ADMIN_HELLO'])) {
    echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
    exit;
}

// ===== 파라미터/기본값 =====
$mode       = $_POST['mode']       ?? $_GET['mode']       ?? '';
$StartYMD   = trim($_POST['StartYMD'] ?? $_GET['StartYMD'] ?? '');
$EndYMD     = trim($_POST['EndYMD']   ?? $_GET['EndYMD']   ?? '');
$startyear  = trim($_POST['startyear']?? $_GET['startyear']?? '');

$today = date('Y-m-d');

if ($startyear === '') $startyear = date('Y');

// 기본: 이번달 1일~말일
if ($StartYMD === '' || $EndYMD === '') {
    $year  = date('Y');
    $month = date('m');
    $StartYMD = date('Y-m-01');
    $EndYMD   = date('Y-m-t');
} else {
    // 간단 검증
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $StartYMD)) $StartYMD = date('Y-m-01');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $EndYMD))   $EndYMD   = date('Y-m-t');
}

// ===== 날짜 배열 생성 (SQL 카르테시안 제거) =====
$start = new DateTime($StartYMD);
$end   = (new DateTime($EndYMD))->modify('+1 day'); // DatePeriod는 end 미포함
$period = new DatePeriod($start, new DateInterval('P1D'), $end);

$dates = [];
foreach ($period as $d) $dates[] = $d->format('Y-m-d');
$totalDay = count($dates);

// ===== 모드: 엑셀다운 =====
if ($mode === 'down') {
    header("Content-type: application/vnd.ms-excel; charset=UTF-8");
    header("Content-Disposition: attachment; filename=sc_".date('Ymd').".xls");
    header("Content-Description: PHP5 Generated Data");
    echo "<meta http-equiv='Content-Type' content='application/vnd.ms-excel; charset=utf-8'/>";
}

// ===== 데이터 로드 =====
// 1) 화면에 그릴 상품 목록(기간 내 예약 존재, 집계 포함)
$products = [];  // [ [p_code, p_name, p_day, bgcolor, person], ... ]
$pstmt = $dbConn->prepare("
    SELECT pm.p_day, pm.p_name, pm.p_code, pm.bgcolor, SUM(ri.p_cnt) AS person
    FROM product_master pm
    JOIN reserve_info ri ON ri.p_code = pm.p_code
    WHERE ri.rev_status = 'DONE'
      AND pm.p_type IN ('1','3','4')
      AND pm.m_type  = 'S'
      AND ri.stDate BETWEEN ? AND ?
    GROUP BY pm.p_code
    ORDER BY pm.grp, pm.p_day, pm.p_name ASC
");
$pstmt->bind_param('ss', $StartYMD, $EndYMD);
$pstmt->execute();
$res = $pstmt->get_result();
while ($row = $res->fetch_assoc()) $products[] = $row;
$pstmt->close();

// 상품이 없으면 빈 테이블 처리
// 2) 날짜×상품별 집계(사람수/객실수) — 한번에 전부
$stats = []; // $stats[p_code][Y-m-d] = ['cnt'=>int, 'room'=>int]
if (!empty($products)) {
    $sstmt = $dbConn->prepare("
        SELECT ri.p_code, ri.stDate, SUM(ri.p_cnt) AS cnt, SUM(ri.room_cnt) AS room_cnt
        FROM reserve_info ri
        JOIN product_master pm ON pm.p_code = ri.p_code
        WHERE ri.rev_status = 'DONE'
          AND pm.p_type IN ('1','3','4')
          AND pm.m_type  = 'S'
          AND ri.stDate BETWEEN ? AND ?
        GROUP BY ri.p_code, ri.stDate
    ");
    $sstmt->bind_param('ss', $StartYMD, $EndYMD);
    $sstmt->execute();
    $r2 = $sstmt->get_result();
    while ($r = $r2->fetch_assoc()) {
        $pcode = $r['p_code'];
        $dt    = $r['stDate'];
        if (!isset($stats[$pcode])) $stats[$pcode] = [];
        $stats[$pcode][$dt] = ['cnt' => (int)$r['cnt'], 'room' => (int)$r['room_cnt']];
    }
    $sstmt->close();
}

// ===== 유틸 =====
function headerDayCell(string $date, string $today): string {
    static $week = ['일','월','화','수','목','금','토'];
    $w = (int)date('w', strtotime($date));
    $yoil  = $week[$w];
    $month = date('m', strtotime($date));
    $day   = date('d', strtotime($date));
    $color = ($yoil==='일') ? 'red' : (($yoil==='토') ? 'blue' : '#111');
    $bg    = ($date === $today) ? " style='margin:0;border:0.05em solid #848484;background-color:#DDA0DD;'" 
                                : " style='margin:0;border:0.05em solid #848484;'";
    $txt = "<a href='memo_list.php?stdate={$date}'><font style='font-size:7pt;color:{$color}'>{$month}/{$day}<br>({$yoil})</font></a>";
    return "<th class='sticky-col'{$bg} align='center'>{$txt}</th>";
}

?>
<link rel="stylesheet" type="text/css" href="lib/datatables.css"/>
<style>
.tableFixHead{height:600px}
.tableFixHead thead th{top:0;background:#eee;border:0.05em solid #848484}
table.dataTable thead tr th,table.dataTable thead td,table.dataTable tbody tr td{
  border-bottom:1px solid #111;padding:1px 1px
}
div.dataTables_wrapper{margin:0 auto}
</style>

<div id="contentwrapper" class="reservationDetailForm">
  <div class="main_content">
    <div id="jCrumbs" class="breadCrumb module">
      <ul>
        <li><a href="/"><i class="glyphicon glyphicon-home"></i></a></li>
        <li><a href="#">전체스케줄표</a></li>
      </ul>
    </div>

    <div class="row">
      <div class="col-sm-12 col-md-12">
        <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" name="frmName" id="frmName" method="post">
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
                          <input type="text" id="startDate1" name="StartYMD" class="inpubase tourDate1" placeholder="시작일" value="<?= htmlspecialchars($StartYMD) ?>" autocomplete="off"/>
                        </div>
                        <div class="col-sm-6">
                          <input type="text" id="endDate" name="EndYMD" class="inpubase tourDate1" placeholder="마지막일" value="<?= htmlspecialchars($EndYMD) ?>" autocomplete="off"/>
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
                    <input type="text" id="startyear" name="startyear" class="inpubase tourDate3" placeholder="년도" value="<?= htmlspecialchars($startyear) ?>" autocomplete="off"/>
                  </div>
                  <div class="col-sm-6">
                    <ul class="pagination non-nav">
                      <?php for($m=1;$m<=12;$m++): ?>
                        <li class="disabled"><span><a href="javascript:cal('<?= $m ?>')"><?= $m ?>월</a></span></li>
                      <?php endfor; ?>
                    </ul>
                  </div>
                  <div class="col-sm-2">
                    <button type='submit' class="btn btn-primary btn-sm text-left btn1">검색</button>
                  </div>
                </div>
              </td>
            </tr>
          </table>
        </form>

        <br/>

        <div class="row">
          <div class="col-sm-12 tableFixHead">
            <table width="100%" id="guide_table" class="stripe row-border order-column text-center">
              <thead>
              <tr>
                <th style="position:sticky;margin:0;border:0.05em solid #848484;" align="center">상 품 명</th>
                <?php
                  foreach ($dates as $d) echo headerDayCell($d, $today);
                ?>
              </tr>
              </thead>
              <tbody>
              <?php
              // 한 번에 큰 문자열로 만들어 출력(출력횟수 줄여 성능↑)
              $out = '';
              foreach ($products as $p) {
                  $pcode   = $p['p_code'];
                  $pname   = $p['p_name'];
                  $pday    = (int)$p['p_day'];
                  $bg      = $p['bgcolor'];
                  $person  = (int)$p['person'];

                  $firstTd = "<td align='left' style='border:0.05em solid #848484;' class='sticky-col first-col' bgcolor='{$bg}'>"
                           . "<font style='font-size:8pt'>&nbsp;{$pcode}&nbsp;<font color='red'>({$person})</font><br/><b>&nbsp;{$pname}</b></font></td>";

                  $rowHtml = $firstTd;

                  foreach ($dates as $d) {
                      $cnt  = $stats[$pcode][$d]['cnt']  ?? 0;
                      $room = $stats[$pcode][$d]['room'] ?? 0;

                      // 배경색: 오늘 보라색, 그 외 예약있으면 노랑
                      if ($d === $today)       $bgcol = "#DDA0DD";
                      elseif ($cnt > 0)        $bgcol = "#FFFF99";
                      else                     $bgcol = "#FFFFFF";

                      $mem = ($cnt > 0) ? (string)$cnt : "";
                      $roomTxt = ($room > 0) ? "/{$room}" : "";

                      $block = "<a href=\"javascript:openwin('{$d}','{$pcode}')\"><font color='black'><font style='font-size:8pt'>{$mem}</font></font></a>"
                             . "<font color='black'><font style='font-size:8pt'><br>{$roomTxt}</font></font>";

                      $rowHtml .= "<td height='35' style='width:10px !important;border:0.05em solid #848484;' align='center' bgcolor='{$bgcol}' title='호텔 : {$room}'>{$block}</td>";
                  }

                  $out .= "<tr>{$rowHtml}</tr>\n";
              }
              echo $out;
              ?>
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
$(document).ready(function(){
  pt.initReservationList();
  pt.initReservationDetail();

  $('.tourDate1').datepicker({format:"yyyy-mm-dd",autoclose:true});
  $('.tourDate2').datepicker({format:"yyyy-mm-dd",autoclose:true});
  $('.tourDate3').datepicker({minViewMode:2,format:"yyyy",autoclose:true});

  $('#guide_table').DataTable({
    scrollY:600, scrollX:true, scrollCollapse:true,
    paging:false, fixedHeader:true, ordering:false, autoWidth:false,
    columnDefs:[{width:150, targets:0}], fixedColumns:true
  }).columns.adjust().draw();
});

function openwin(stdate,s_code,rcd){
  var winName="all_1";
  window.open(
    "guide_assign_customer.php?division=<?= $division ?? '' ?>&pdx=<?= $pdx ?? '' ?>&sub=<?= $sub ?? '' ?>&s_code="+s_code+"&stdate="+stdate+"&rcode="+(rcd||''),
    winName,"width=1090px,height=700,scrollbars=1"
  );
}
function numberOfDays(month,year){ var d=new Date(year, month, 0); return d.getDate(); }
function cal(mon){
  if(mon<10) mon="0"+mon;
  var st = $("#startyear").val()+"-"+mon+"-01";
  $("#startDate1").val(st);
  var lastday = numberOfDays(mon,$("#startyear").val());
  var ed = $("#startyear").val()+"-"+mon+"-"+lastday;
  $("#endDate").val(ed);
  $("#frmName").submit();
}
</script>
</body>
</html>
