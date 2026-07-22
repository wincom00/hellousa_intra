<?php
include "include/header.php";
require_once __DIR__ . "/include/arap_common.php";

if (empty($_COOKIE['MEMLOGIN_ADMIN_HELLO'])) {
    echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
    exit;
}

if ($division == "") $division = '11';
if ($pdx == "")      $pdx = '1';
if ($sub == "")      $sub = '10';

if (!hasMenuAccess($division, $pdx, $sub)) {
    Misc::jvAlert("권한이 있는 메뉴가 아닙니다. 확인 후 사용해 주세요.", "");
    echo "<meta http-equiv='refresh' content='0; url=index.php'>";
    exit;
}

$month = arapGetMonthValue(isset($_GET['month']) ? $_GET['month'] : '');
list($startDate, $endDate) = arapGetMonthRange($month);
$ss = $dbConn->real_escape_string($startDate);
$se = $dbConn->real_escape_string($endDate);
$startOf12 = date('Y-m-01', strtotime($startDate . ' -11 months'));
$s12 = $dbConn->real_escape_string($startOf12);

// 요약
$incomeTotal  = 0.0;
$expenseTotal = 0.0;
$sRes = $dbConn->query("SELECT SUM(CASE WHEN tx_type='IN' THEN amount ELSE 0 END) AS inc, SUM(CASE WHEN tx_type='OUT' THEN amount ELSE 0 END) AS exp FROM arap_transaction WHERE tx_date BETWEEN '$ss' AND '$se'");
if ($sRes) {
    $sRow = $sRes->fetch_assoc();
    if ($sRow) {
        $incomeTotal  = abs((float)$sRow['inc']);
        $expenseTotal = abs((float)$sRow['exp']);
    }
}
$balanceTotal = $incomeTotal - $expenseTotal;

// 1. 최근 12개월 수입/지출
$monthlyData = array();
$res = $dbConn->query("SELECT DATE_FORMAT(tx_date,'%Y-%m') as ym,
    SUM(CASE WHEN tx_type='IN' THEN amount ELSE 0 END) as income,
    SUM(CASE WHEN tx_type='OUT' THEN amount ELSE 0 END) as expense
    FROM arap_transaction
    WHERE tx_date >= '$s12' AND tx_date <= '$se'
    GROUP BY DATE_FORMAT(tx_date,'%Y-%m')
    ORDER BY ym ASC");
if ($res) while ($r = $res->fetch_assoc()) $monthlyData[] = $r;

// 2. 일별 수입/지출 (선택 월)
$dailyData = array();
$res = $dbConn->query("SELECT DAY(tx_date) as d,
    SUM(CASE WHEN tx_type='IN' THEN amount ELSE 0 END) as income,
    SUM(CASE WHEN tx_type='OUT' THEN amount ELSE 0 END) as expense
    FROM arap_transaction
    WHERE tx_date BETWEEN '$ss' AND '$se'
    GROUP BY DAY(tx_date)
    ORDER BY d ASC");
if ($res) while ($r = $res->fetch_assoc()) $dailyData[] = $r;

// 3. 대분류별 지출
$catExpData = array();
$res = $dbConn->query("SELECT c.cat_name, SUM(t.amount) as total
    FROM arap_transaction t
    INNER JOIN arap_category c ON c.cat_id = t.cat_id
    WHERE t.tx_type='OUT' AND t.tx_date BETWEEN '$ss' AND '$se'
    GROUP BY t.cat_id, c.cat_name
    ORDER BY total DESC");
if ($res) while ($r = $res->fetch_assoc()) $catExpData[] = $r;

// 4. 대분류별 수입
$catIncData = array();
$res = $dbConn->query("SELECT c.cat_name, SUM(t.amount) as total
    FROM arap_transaction t
    INNER JOIN arap_category c ON c.cat_id = t.cat_id
    WHERE t.tx_type='IN' AND t.tx_date BETWEEN '$ss' AND '$se'
    GROUP BY t.cat_id, c.cat_name
    ORDER BY total DESC");
if ($res) while ($r = $res->fetch_assoc()) $catIncData[] = $r;

// 5. 소분류별 지출 TOP 10
$subExpData = array();
$res = $dbConn->query("SELECT COALESCE(s.sub_name,'미분류') as sub_name, SUM(t.amount) as total
    FROM arap_transaction t
    LEFT JOIN arap_subcategory s ON s.sub_id = t.sub_id
    WHERE t.tx_type='OUT' AND t.tx_date BETWEEN '$ss' AND '$se'
    GROUP BY t.sub_id, s.sub_name
    ORDER BY total DESC
    LIMIT 10");
if ($res) while ($r = $res->fetch_assoc()) $subExpData[] = $r;

// 6. 거래수단별 금액
$methodData = array();
$res = $dbConn->query("SELECT COALESCE(m.method_name,'미지정') as method_name, SUM(t.amount) as total
    FROM arap_transaction t
    LEFT JOIN arap_method m ON m.method_id = t.method_id
    WHERE t.tx_date BETWEEN '$ss' AND '$se'
    GROUP BY t.method_id, m.method_name
    ORDER BY total DESC");
if ($res) while ($r = $res->fetch_assoc()) $methodData[] = $r;

// 7. 거래상태별 건수
$statusData = array();
$statusLabelMap = array('PENDING'=>'대기','COMPLETED'=>'완료','CANCELLED'=>'취소','HOLD'=>'보류');
$res = $dbConn->query("SELECT status, COUNT(*) as cnt, SUM(amount) as total
    FROM arap_transaction
    WHERE tx_date BETWEEN '$ss' AND '$se'
    GROUP BY status
    ORDER BY cnt DESC");
if ($res) while ($r = $res->fetch_assoc()) $statusData[] = $r;

// 8. 월별 누적 (당해연도)
$yearData = array();
$yearStart = date('Y') . '-01-01';
$res = $dbConn->query("SELECT DATE_FORMAT(tx_date,'%m') as m,
    SUM(CASE WHEN tx_type='IN' THEN amount ELSE 0 END) as income,
    SUM(CASE WHEN tx_type='OUT' THEN amount ELSE 0 END) as expense
    FROM arap_transaction
    WHERE tx_date >= '" . $dbConn->real_escape_string($yearStart) . "' AND tx_date <= '$se'
    GROUP BY DATE_FORMAT(tx_date,'%m')
    ORDER BY m ASC");
if ($res) while ($r = $res->fetch_assoc()) $yearData[] = $r;

$listUrl   = arapPageUrl('arap_list.php',  array('division'=>$division,'pdx'=>$pdx,'sub'=>$sub,'month'=>$month));
$prevMonth = date('Y-m', strtotime($month . '-01 -1 month'));
$nextMonth = date('Y-m', strtotime($month . '-01 +1 month'));
$prevUrl   = arapPageUrl('arap_chart.php', array('division'=>$division,'pdx'=>$pdx,'sub'=>$sub,'month'=>$prevMonth));
$nextUrl   = arapPageUrl('arap_chart.php', array('division'=>$division,'pdx'=>$pdx,'sub'=>$sub,'month'=>$nextMonth));
?>
<style>
.chart-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 6px;
    padding: 18px;
    margin-bottom: 22px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
}
.chart-card h5 {
    margin: 0 0 14px 0;
    font-size: 14px;
    font-weight: 700;
    color: #333;
    border-bottom: 2px solid #f0f0f0;
    padding-bottom: 10px;
}
.summary-box {
    border-radius: 6px;
    padding: 18px 16px;
    margin-bottom: 22px;
    color: #fff;
    text-align: center;
    box-shadow: 0 2px 6px rgba(0,0,0,0.12);
}
.summary-box .sb-label { font-size: 12px; opacity: 0.88; letter-spacing: 0.3px; }
.summary-box .sb-value { font-size: 28px; font-weight: 700; margin-top: 6px; }
.bg-inc  { background: linear-gradient(135deg,#27ae60,#2ecc71); }
.bg-exp  { background: linear-gradient(135deg,#c0392b,#e74c3c); }
.bg-bal-pos { background: linear-gradient(135deg,#2980b9,#3498db); }
.bg-bal-neg { background: linear-gradient(135deg,#7f8c8d,#95a5a6); }
.month-nav { display:flex; align-items:center; gap:8px; margin-bottom:16px; flex-wrap:wrap; }
.chart-empty { text-align:center; color:#aaa; font-size:13px; padding:40px 0; }
</style>

<div id="contentwrapper">
    <div class="main_content">
        <div id="jCrumbs" class="breadCrumb module">
            <ul>
                <li><a href="/"><i class="glyphicon glyphicon-home"></i></a></li>
                <li><a href="#">수입·지출</a></li>
                <li>통계 차트</li>
            </ul>
        </div>

        <!-- 월 네비게이션 -->
        <div class="month-nav">
            <a href="<?= arapH($prevUrl) ?>" class="btn btn-default btn-sm">
                <i class="glyphicon glyphicon-chevron-left"></i>
            </a>
            <form method="get" style="display:inline-flex;align-items:center;gap:6px;margin:0;">
                <input type="hidden" name="division" value="<?= arapH($division) ?>">
                <input type="hidden" name="pdx"      value="<?= arapH($pdx) ?>">
                <input type="hidden" name="sub"      value="<?= arapH($sub) ?>">
                <input type="month" name="month" class="form-control input-sm" value="<?= arapH($month) ?>" style="width:140px;">
                <button type="submit" class="btn btn-primary btn-sm">조회</button>
            </form>
            <a href="<?= arapH($nextUrl) ?>" class="btn btn-default btn-sm">
                <i class="glyphicon glyphicon-chevron-right"></i>
            </a>
            <a href="<?= arapH($listUrl) ?>" class="btn btn-default btn-sm">
                <i class="glyphicon glyphicon-list"></i> 거래목록
            </a>
        </div>

        <!-- 요약 카드 -->
        <div class="row">
            <div class="col-sm-4">
                <div class="summary-box bg-inc">
                    <div class="sb-label">수입합계 (<?= arapH($month) ?>)</div>
                    <div class="sb-value">$<?= arapFormatAmount($incomeTotal) ?></div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="summary-box bg-exp">
                    <div class="sb-label">지출합계 (<?= arapH($month) ?>)</div>
                    <div class="sb-value">-$<?= arapFormatAmount($expenseTotal) ?></div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="summary-box <?= $balanceTotal >= 0 ? 'bg-bal-pos' : 'bg-bal-neg' ?>">
                    <div class="sb-label">차액 (<?= arapH($month) ?>)</div>
                    <div class="sb-value"><?= $balanceTotal < 0 ? '-' : '' ?>$<?= arapFormatAmount(abs($balanceTotal)) ?></div>
                </div>
            </div>
        </div>

        <!-- Row 1: 최근 12개월 추이 -->
        <div class="row">
            <div class="col-sm-12">
                <div class="chart-card">
                    <h5><i class="glyphicon glyphicon-stats"></i> 최근 12개월 수입/지출 추이</h5>
                    <?php if (empty($monthlyData)): ?>
                        <div class="chart-empty">데이터가 없습니다.</div>
                    <?php else: ?>
                        <div style="position:relative;height:280px;">
                            <canvas id="chartMonthly"></canvas>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Row 2: 당해연도 월별 + 일별 -->
        <div class="row">
            <div class="col-sm-6">
                <div class="chart-card">
                    <h5><i class="glyphicon glyphicon-calendar"></i> <?= date('Y') ?>년 월별 수입/지출</h5>
                    <?php if (empty($yearData)): ?>
                        <div class="chart-empty">데이터가 없습니다.</div>
                    <?php else: ?>
                        <div style="position:relative;height:240px;">
                            <canvas id="chartYear"></canvas>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="chart-card">
                    <h5><i class="glyphicon glyphicon-calendar"></i> <?= arapH($month) ?> 일별 수입/지출</h5>
                    <?php if (empty($dailyData)): ?>
                        <div class="chart-empty">데이터가 없습니다.</div>
                    <?php else: ?>
                        <div style="position:relative;height:240px;">
                            <canvas id="chartDaily"></canvas>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Row 3: 대분류별 지출/수입 도넛 -->
        <div class="row">
            <div class="col-sm-6">
                <div class="chart-card">
                    <h5><i class="glyphicon glyphicon-minus-sign" style="color:#c0392b;"></i> 대분류별 지출 비율</h5>
                    <?php if (empty($catExpData)): ?>
                        <div class="chart-empty">지출 내역이 없습니다.</div>
                    <?php else: ?>
                        <div style="position:relative;height:280px;">
                            <canvas id="chartCatExp"></canvas>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="chart-card">
                    <h5><i class="glyphicon glyphicon-plus-sign" style="color:#27ae60;"></i> 대분류별 수입 비율</h5>
                    <?php if (empty($catIncData)): ?>
                        <div class="chart-empty">수입 내역이 없습니다.</div>
                    <?php else: ?>
                        <div style="position:relative;height:280px;">
                            <canvas id="chartCatInc"></canvas>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Row 4: 소분류 TOP10 + 거래수단 -->
        <div class="row">
            <div class="col-sm-8">
                <div class="chart-card">
                    <h5><i class="glyphicon glyphicon-list-alt"></i> 소분류별 지출 TOP 10</h5>
                    <?php if (empty($subExpData)): ?>
                        <div class="chart-empty">지출 내역이 없습니다.</div>
                    <?php else: ?>
                        <div style="position:relative;height:320px;">
                            <canvas id="chartSubExp"></canvas>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="chart-card">
                    <h5><i class="glyphicon glyphicon-credit-card"></i> 거래수단별 금액</h5>
                    <?php if (empty($methodData)): ?>
                        <div class="chart-empty">데이터가 없습니다.</div>
                    <?php else: ?>
                        <div style="position:relative;height:320px;">
                            <canvas id="chartMethod"></canvas>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Row 5: 거래상태별 -->
        <div class="row">
            <div class="col-sm-5">
                <div class="chart-card">
                    <h5><i class="glyphicon glyphicon-tasks"></i> 거래상태별 건수</h5>
                    <?php if (empty($statusData)): ?>
                        <div class="chart-empty">데이터가 없습니다.</div>
                    <?php else: ?>
                        <div style="position:relative;height:240px;">
                            <canvas id="chartStatus"></canvas>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-sm-7">
                <div class="chart-card">
                    <h5><i class="glyphicon glyphicon-usd"></i> 거래상태별 금액</h5>
                    <?php if (empty($statusData)): ?>
                        <div class="chart-empty">데이터가 없습니다.</div>
                    <?php else: ?>
                        <div style="position:relative;height:240px;">
                            <canvas id="chartStatusAmt"></canvas>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include "include/side_m.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
var PALETTE = [
    '#3498db','#e74c3c','#2ecc71','#f39c12',
    '#9b59b6','#1abc9c','#e67e22','#e91e63',
    '#607d8b','#795548','#009688','#ff5722',
    '#8e44ad','#2980b9','#16a085','#d35400'
];

Chart.defaults.font.family = "'Helvetica Neue', Arial, '맑은 고딕', sans-serif";
Chart.defaults.font.size   = 12;

var dollarTick = { callback: function(v) { return '$' + v.toLocaleString('en', {minimumFractionDigits:0, maximumFractionDigits:0}); } };
var dollarTooltip = { callbacks: { label: function(ctx) {
    return ' ' + ctx.dataset.label + ': $' + parseFloat(ctx.raw).toLocaleString('en', {minimumFractionDigits:2, maximumFractionDigits:2});
}}};

// 1. 최근 12개월 추이 (grouped bar)
var monthlyData = <?= json_encode($monthlyData, JSON_UNESCAPED_UNICODE) ?>;
if (monthlyData.length && document.getElementById('chartMonthly')) {
    new Chart(document.getElementById('chartMonthly'), {
        type: 'bar',
        data: {
            labels: monthlyData.map(function(r){ return r.ym; }),
            datasets: [
                { label:'수입', data: monthlyData.map(function(r){ return parseFloat(r.income); }),
                  backgroundColor:'rgba(39,174,96,0.75)', borderColor:'#27ae60', borderWidth:1 },
                { label:'지출', data: monthlyData.map(function(r){ return parseFloat(r.expense); }),
                  backgroundColor:'rgba(192,57,43,0.75)', borderColor:'#c0392b', borderWidth:1 }
            ]
        },
        options: {
            responsive:true, maintainAspectRatio:false,
            plugins: { legend:{ position:'top' }, tooltip: dollarTooltip },
            scales: { y:{ beginAtZero:true, ticks: dollarTick } }
        }
    });
}

// 2. 당해연도 월별 (line)
var yearData = <?= json_encode($yearData, JSON_UNESCAPED_UNICODE) ?>;
if (yearData.length && document.getElementById('chartYear')) {
    var monthNames = ['1월','2월','3월','4월','5월','6월','7월','8월','9월','10월','11월','12월'];
    new Chart(document.getElementById('chartYear'), {
        type: 'line',
        data: {
            labels: yearData.map(function(r){ return monthNames[parseInt(r.m,10)-1]; }),
            datasets: [
                { label:'수입', data: yearData.map(function(r){ return parseFloat(r.income); }),
                  borderColor:'#27ae60', backgroundColor:'rgba(39,174,96,0.08)', fill:true, tension:0.4, pointRadius:4 },
                { label:'지출', data: yearData.map(function(r){ return parseFloat(r.expense); }),
                  borderColor:'#c0392b', backgroundColor:'rgba(192,57,43,0.08)', fill:true, tension:0.4, pointRadius:4 }
            ]
        },
        options: {
            responsive:true, maintainAspectRatio:false,
            plugins: { legend:{ position:'top' }, tooltip: dollarTooltip },
            scales: { y:{ beginAtZero:true, ticks: dollarTick } }
        }
    });
}

// 3. 일별 (line)
var dailyData = <?= json_encode($dailyData, JSON_UNESCAPED_UNICODE) ?>;
if (dailyData.length && document.getElementById('chartDaily')) {
    new Chart(document.getElementById('chartDaily'), {
        type: 'line',
        data: {
            labels: dailyData.map(function(r){ return r.d + '일'; }),
            datasets: [
                { label:'수입', data: dailyData.map(function(r){ return parseFloat(r.income); }),
                  borderColor:'#27ae60', backgroundColor:'rgba(39,174,96,0.1)', fill:true, tension:0.3, pointRadius:4 },
                { label:'지출', data: dailyData.map(function(r){ return parseFloat(r.expense); }),
                  borderColor:'#c0392b', backgroundColor:'rgba(192,57,43,0.1)', fill:true, tension:0.3, pointRadius:4 }
            ]
        },
        options: {
            responsive:true, maintainAspectRatio:false,
            plugins: { legend:{ position:'top' }, tooltip: dollarTooltip },
            scales: { y:{ beginAtZero:true, ticks: dollarTick } }
        }
    });
}

// donut 공통 옵션
function donutOpts(position) {
    return {
        responsive:true, maintainAspectRatio:false,
        plugins: {
            legend: { position: position || 'right' },
            tooltip: { callbacks: { label: function(ctx) {
                var pct = ctx.chart.data.datasets[0].data.reduce(function(a,b){ return a+b; }, 0);
                var ratio = pct > 0 ? (parseFloat(ctx.raw)/pct*100).toFixed(1) : 0;
                return ' ' + ctx.label + ': $' + parseFloat(ctx.raw).toLocaleString('en',{minimumFractionDigits:2,maximumFractionDigits:2}) + ' (' + ratio + '%)';
            }}}
        }
    };
}

// 4. 대분류별 지출 도넛
var catExpData = <?= json_encode($catExpData, JSON_UNESCAPED_UNICODE) ?>;
if (catExpData.length && document.getElementById('chartCatExp')) {
    new Chart(document.getElementById('chartCatExp'), {
        type: 'doughnut',
        data: {
            labels: catExpData.map(function(r){ return r.cat_name; }),
            datasets: [{ data: catExpData.map(function(r){ return parseFloat(r.total); }), backgroundColor: PALETTE }]
        },
        options: donutOpts('right')
    });
}

// 5. 대분류별 수입 도넛
var catIncData = <?= json_encode($catIncData, JSON_UNESCAPED_UNICODE) ?>;
if (catIncData.length && document.getElementById('chartCatInc')) {
    new Chart(document.getElementById('chartCatInc'), {
        type: 'doughnut',
        data: {
            labels: catIncData.map(function(r){ return r.cat_name; }),
            datasets: [{ data: catIncData.map(function(r){ return parseFloat(r.total); }), backgroundColor: PALETTE }]
        },
        options: donutOpts('right')
    });
}

// 6. 소분류별 지출 TOP10 (horizontal bar)
var subExpData = <?= json_encode($subExpData, JSON_UNESCAPED_UNICODE) ?>;
if (subExpData.length && document.getElementById('chartSubExp')) {
    new Chart(document.getElementById('chartSubExp'), {
        type: 'bar',
        data: {
            labels: subExpData.map(function(r){ return r.sub_name; }),
            datasets: [{ label:'지출', data: subExpData.map(function(r){ return parseFloat(r.total); }),
                backgroundColor: subExpData.map(function(r,i){ return PALETTE[i % PALETTE.length]; }),
                borderWidth:0 }]
        },
        options: {
            indexAxis: 'y',
            responsive:true, maintainAspectRatio:false,
            plugins: { legend:{ display:false }, tooltip: { callbacks: { label: function(ctx) {
                return ' $' + parseFloat(ctx.raw).toLocaleString('en',{minimumFractionDigits:2,maximumFractionDigits:2});
            }}}},
            scales: { x:{ beginAtZero:true, ticks: dollarTick } }
        }
    });
}

// 7. 거래수단별 도넛
var methodData = <?= json_encode($methodData, JSON_UNESCAPED_UNICODE) ?>;
if (methodData.length && document.getElementById('chartMethod')) {
    new Chart(document.getElementById('chartMethod'), {
        type: 'doughnut',
        data: {
            labels: methodData.map(function(r){ return r.method_name; }),
            datasets: [{ data: methodData.map(function(r){ return parseFloat(r.total); }), backgroundColor: PALETTE }]
        },
        options: donutOpts('bottom')
    });
}

// 8. 거래상태별 건수 도넛
var statusData    = <?= json_encode($statusData, JSON_UNESCAPED_UNICODE) ?>;
var statusLabels  = <?= json_encode($statusLabelMap, JSON_UNESCAPED_UNICODE) ?>;
var statusColors  = { PENDING:'#f39c12', COMPLETED:'#27ae60', CANCELLED:'#c0392b', HOLD:'#3498db' };
if (statusData.length && document.getElementById('chartStatus')) {
    new Chart(document.getElementById('chartStatus'), {
        type: 'doughnut',
        data: {
            labels: statusData.map(function(r){ return statusLabels[r.status] || r.status; }),
            datasets: [{ data: statusData.map(function(r){ return parseInt(r.cnt, 10); }),
                backgroundColor: statusData.map(function(r){ return statusColors[r.status] || '#95a5a6'; }) }]
        },
        options: {
            responsive:true, maintainAspectRatio:false,
            plugins: {
                legend: { position:'right' },
                tooltip: { callbacks: { label: function(ctx) { return ' ' + ctx.label + ': ' + ctx.raw + '건'; } } }
            }
        }
    });
}

// 9. 거래상태별 금액 bar
if (statusData.length && document.getElementById('chartStatusAmt')) {
    new Chart(document.getElementById('chartStatusAmt'), {
        type: 'bar',
        data: {
            labels: statusData.map(function(r){ return statusLabels[r.status] || r.status; }),
            datasets: [{ label:'금액', data: statusData.map(function(r){ return parseFloat(r.total); }),
                backgroundColor: statusData.map(function(r){ return statusColors[r.status] || '#95a5a6'; }),
                borderWidth:0 }]
        },
        options: {
            responsive:true, maintainAspectRatio:false,
            plugins: { legend:{ display:false }, tooltip: { callbacks: { label: function(ctx) {
                return ' $' + parseFloat(ctx.raw).toLocaleString('en',{minimumFractionDigits:2,maximumFractionDigits:2});
            }}}},
            scales: { y:{ beginAtZero:true, ticks: dollarTick } }
        }
    });
}
</script>
