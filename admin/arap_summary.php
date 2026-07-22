<?php
include "include/header.php";
require_once __DIR__ . "/include/arap_common.php";

if (empty($_COOKIE['MEMLOGIN_ADMIN_HELLO'])) {
    echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
    exit;
}

if ($division == "") {
    $division = '11';
}
if ($pdx == "") {
    $pdx = '1';
}
if ($sub == "") {
    $sub = '20';
}

if (!hasMenuAccess($division, $pdx, $sub)) {
    $goUrl_1 = "index.php";
    Misc::jvAlert("권한이 있는 메뉴가 아닙니다. 확인 후 사용해 주세요.","");
    echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
    exit;
}

$year = arapGetYearValue(isset($_GET['year']) ? $_GET['year'] : '');
$startDate = $year . '-01-01';
$endDate = $year . '-12-31';

$monthlySql = "SELECT
                    MONTH(tx_date) AS month_no,
                    SUM(CASE WHEN tx_type = 'IN' THEN amount ELSE 0 END) AS income_total,
                    SUM(CASE WHEN tx_type = 'OUT' THEN amount ELSE 0 END) AS expense_total
               FROM arap_transaction
               WHERE tx_date BETWEEN '" . $dbConn->real_escape_string($startDate) . "' AND '" . $dbConn->real_escape_string($endDate) . "'
                 AND (status IS NULL OR status != 'CANCELLED')
               GROUP BY MONTH(tx_date)
               ORDER BY MONTH(tx_date)";
$monthlyResult = $dbConn->query($monthlySql);
$monthlyMap = array();
if ($monthlyResult) {
    while ($row = $monthlyResult->fetch_assoc()) {
        $monthNo = (int)$row['month_no'];
        $monthlyMap[$monthNo] = array(
            'income_total' => abs((float)$row['income_total']),
            'expense_total' => abs((float)$row['expense_total'])
        );
    }
}

$incomeCategorySql = "SELECT c.cat_name, SUM(t.amount) AS amount_total
                      FROM arap_transaction t
                      INNER JOIN arap_category c ON c.cat_id = t.cat_id
                      WHERE t.tx_type = 'IN'
                        AND t.tx_date BETWEEN '" . $dbConn->real_escape_string($startDate) . "' AND '" . $dbConn->real_escape_string($endDate) . "'
                        AND (t.status IS NULL OR t.status != 'CANCELLED')
                      GROUP BY c.cat_id, c.cat_name
                      ORDER BY amount_total DESC, c.cat_name ASC";
$expenseCategorySql = "SELECT c.cat_name, SUM(t.amount) AS amount_total
                       FROM arap_transaction t
                       INNER JOIN arap_category c ON c.cat_id = t.cat_id
                       WHERE t.tx_type = 'OUT'
                         AND t.tx_date BETWEEN '" . $dbConn->real_escape_string($startDate) . "' AND '" . $dbConn->real_escape_string($endDate) . "'
                         AND (t.status IS NULL OR t.status != 'CANCELLED')
                       GROUP BY c.cat_id, c.cat_name
                       ORDER BY amount_total DESC, c.cat_name ASC";

$incomeCategories = array();
$incomeCategoryResult = $dbConn->query($incomeCategorySql);
if ($incomeCategoryResult) {
    while ($row = $incomeCategoryResult->fetch_assoc()) {
        $row['amount_total'] = abs((float)$row['amount_total']);
        $incomeCategories[] = $row;
    }
}

$expenseCategories = array();
$expenseCategoryResult = $dbConn->query($expenseCategorySql);
if ($expenseCategoryResult) {
    while ($row = $expenseCategoryResult->fetch_assoc()) {
        $row['amount_total'] = abs((float)$row['amount_total']);
        $expenseCategories[] = $row;
    }
}

$yearIncomeTotal = 0;
$yearExpenseTotal = 0;
for ($monthNo = 1; $monthNo <= 12; $monthNo++) {
    if (isset($monthlyMap[$monthNo])) {
        $yearIncomeTotal += $monthlyMap[$monthNo]['income_total'];
        $yearExpenseTotal += $monthlyMap[$monthNo]['expense_total'];
    }
}
$yearBalance = $yearIncomeTotal - $yearExpenseTotal;
$exportUrl = arapPageUrl('arap_export.php', array(
    'export_type' => 'summary',
    'division' => $division,
    'pdx' => $pdx,
    'sub' => $sub,
    'year' => $year
));
?>
<style>
.summary-card {
    border: 1px solid #dcdcdc;
    border-radius: 4px;
    background: #fff;
    padding: 15px;
    margin-bottom: 15px;
}
.summary-card .title {
    color: #777;
    font-size: 12px;
    display: block;
}
.summary-card .value {
    font-size: 24px;
    font-weight: bold;
}
.summary-ratio-bar {
    background: #e9ecef;
    border-radius: 3px;
    height: 8px;
    margin-top: 4px;
    overflow: hidden;
}
.summary-ratio-bar .bar-in  { background: #2c7a2c; height: 8px; }
.summary-ratio-bar .bar-out { background: #b94a48; height: 8px; }
.flot-chart-wrap {
    min-height: 260px;
    position: relative;
}
</style>

<div id="contentwrapper">
    <div class="main_content">
        <div id="jCrumbs" class="breadCrumb module">
            <ul>
                <li><a href="/"><i class="glyphicon glyphicon-home"></i></a></li>
                <li><a href="#">수입·지출</a></li>
                <li>월별통계</li>
            </ul>
        </div>

        <form method="get" class="form-inline" style="margin-bottom:15px;">
            <input type="hidden" name="division" value="<?= arapH($division) ?>">
            <input type="hidden" name="pdx" value="<?= arapH($pdx) ?>">
            <input type="hidden" name="sub" value="<?= arapH($sub) ?>">
            <label for="year">조회연도</label>
            <input type="number" name="year" id="year" class="form-control" style="width:120px;" value="<?= arapH($year) ?>" min="2000" max="2099">
            <button type="submit" class="btn btn-primary">조회</button>
            <a href="<?= arapH($exportUrl) ?>" class="btn btn-default">엑셀 다운로드</a>
        </form>

        <div class="row">
            <div class="col-sm-4">
                <div class="summary-card">
                    <span class="title">연간 수입합계</span>
                    <span class="value" style="color:#2c7a2c;">$<?= arapFormatAmount($yearIncomeTotal) ?></span>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="summary-card">
                    <span class="title">연간 지출합계</span>
                    <span class="value" style="color:#b94a48;">-$<?= arapFormatAmount($yearExpenseTotal) ?></span>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="summary-card">
                    <span class="title">연간 차액</span>
                    <span class="value" style="color:#245580;"><?= $yearBalance < 0 ? '-' : '' ?>$<?= arapFormatAmount(abs($yearBalance)) ?></span>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <div class="panel panel-default">
                    <div class="panel-heading"><strong>월별 추이</strong></div>
                    <div class="panel-body">
                        <table class="table table-bordered table-striped table-condensed">
                            <thead>
                                <tr>
                                    <th>월</th>
                                    <th class="text-right">수입합계</th>
                                    <th class="text-right">수입비중</th>
                                    <th class="text-right">지출합계</th>
                                    <th class="text-right">지출비중</th>
                                    <th class="text-right">차액</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php for ($monthNo = 1; $monthNo <= 12; $monthNo++) { ?>
                                    <?php
                                    $incomeAmount  = isset($monthlyMap[$monthNo]) ? $monthlyMap[$monthNo]['income_total']  : 0;
                                    $expenseAmount = isset($monthlyMap[$monthNo]) ? $monthlyMap[$monthNo]['expense_total'] : 0;
                                    $balanceAmount = $incomeAmount - $expenseAmount;
                                    $inRatio  = $yearIncomeTotal  > 0 ? ($incomeAmount  / $yearIncomeTotal)  * 100 : 0;
                                    $outRatio = $yearExpenseTotal > 0 ? ($expenseAmount / $yearExpenseTotal) * 100 : 0;
                                    ?>
                                    <tr>
                                        <td><?= $monthNo ?>월</td>
                                        <td class="text-right">$<?= arapFormatAmount($incomeAmount) ?></td>
                                        <td class="text-right">
                                            <?= number_format($inRatio, 1) ?>%
                                            <div class="summary-ratio-bar"><div class="bar-in" style="width:<?= min(100, round($inRatio)) ?>%"></div></div>
                                        </td>
                                        <td class="text-right"><?= $expenseAmount > 0 ? '-' : '' ?>$<?= arapFormatAmount($expenseAmount) ?></td>
                                        <td class="text-right">
                                            <?= number_format($outRatio, 1) ?>%
                                            <div class="summary-ratio-bar"><div class="bar-out" style="width:<?= min(100, round($outRatio)) ?>%"></div></div>
                                        </td>
                                        <td class="text-right"><?= $balanceAmount < 0 ? '-' : '' ?>$<?= arapFormatAmount(abs($balanceAmount)) ?></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <div class="panel panel-default">
                    <div class="panel-heading"><strong>월별 수입·지출 비중 차트</strong></div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-sm-8">
                                <div class="flot-chart-wrap">
                                    <div id="flot-monthly-bar" style="height:250px;"></div>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <p style="margin:0 0 6px;font-weight:bold;font-size:12px;color:#555;">지출 분류 비중</p>
                                <div id="flot-expense-pie" style="height:200px;"></div>
                                <div id="flot-expense-legend" style="margin-top:6px;font-size:11px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-6">
                <div class="panel panel-default">
                    <div class="panel-heading"><strong>수입 분류 비중</strong></div>
                    <div class="panel-body">
                        <table class="table table-bordered table-striped table-condensed">
                            <thead>
                                <tr>
                                    <th>대분류</th>
                                    <th class="text-right">합계</th>
                                    <th class="text-right">비율</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($incomeCategories as $row) { ?>
                                    <?php $ratio = $yearIncomeTotal > 0 ? ((float)$row['amount_total'] / $yearIncomeTotal) * 100 : 0; ?>
                                    <tr>
                                        <td><?= arapH($row['cat_name']) ?></td>
                                        <td class="text-right">$<?= arapFormatAmount($row['amount_total']) ?></td>
                                        <td class="text-right"><?= number_format($ratio, 2) ?>%</td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="panel panel-default">
                    <div class="panel-heading"><strong>지출 분류 비중</strong></div>
                    <div class="panel-body">
                        <table class="table table-bordered table-striped table-condensed">
                            <thead>
                                <tr>
                                    <th>대분류</th>
                                    <th class="text-right">합계</th>
                                    <th class="text-right">비율</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($expenseCategories as $row) { ?>
                                    <?php $ratio = $yearExpenseTotal > 0 ? ((float)$row['amount_total'] / $yearExpenseTotal) * 100 : 0; ?>
                                    <tr>
                                        <td><?= arapH($row['cat_name']) ?></td>
                                        <td class="text-right">-$<?= arapFormatAmount($row['amount_total']) ?></td>
                                        <td class="text-right"><?= number_format($ratio, 2) ?>%</td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "include/side_m.php"; ?>
<script src="lib/flot/jquery.flot.min.js"></script>
<script src="lib/flot/jquery.flot.categories.min.js"></script>
<script src="lib/flot/jquery.flot.stack.min.js"></script>
<script src="lib/flot/jquery.flot.pie.min.js"></script>
<script>
(function () {
    var months = ['1월','2월','3월','4월','5월','6월','7월','8월','9월','10월','11월','12월'];
    var inData  = <?php
        $arr = array();
        for ($i = 1; $i <= 12; $i++) {
            $arr[] = isset($monthlyMap[$i]) ? round($monthlyMap[$i]['income_total'], 2) : 0;
        }
        echo json_encode($arr);
    ?>;
    var outData = <?php
        $arr = array();
        for ($i = 1; $i <= 12; $i++) {
            $arr[] = isset($monthlyMap[$i]) ? round($monthlyMap[$i]['expense_total'], 2) : 0;
        }
        echo json_encode($arr);
    ?>;

    var inPoints  = [], outPoints = [];
    for (var i = 0; i < 12; i++) {
        inPoints.push([i, inData[i]]);
        outPoints.push([i, outData[i]]);
    }

    var $tip = $('<div id="flotTip" style="position:absolute;background:#fff;color:#333;padding:4px 10px;border:1px solid #999;border-radius:4px;font-size:12px;z-index:9999;white-space:nowrap;pointer-events:none;display:none;box-shadow:0 2px 4px rgba(0,0,0,.15);"></div>').appendTo('body');

    function fmtAmt(v) {
        var n = Math.round(v);
        return '$' + n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    $.plot('#flot-monthly-bar', [
        { label: '수입', data: inPoints,  bars: { show: true, barWidth: 0.35, align: 'left',  fillColor: { colors: ['#4caf7d','#2c7a2c'] } }, color: '#2c7a2c' },
        { label: '지출', data: outPoints, bars: { show: true, barWidth: 0.35, align: 'right', fillColor: { colors: ['#e57373','#b94a48'] } }, color: '#b94a48' }
    ], {
        xaxis: {
            ticks: $.map(months, function (m, i) { return [[i, m]]; }),
            tickLength: 0
        },
        yaxis: {
            tickFormatter: function (v) { return '$' + Math.round(v).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ','); },
            min: 0
        },
        grid: { hoverable: true, borderWidth: 1, borderColor: '#e0e0e0' },
        legend: { position: 'nw' }
    });

    $('#flot-monthly-bar').bind('plothover', function (event, pos, item) {
        if (item) {
            var xi = Math.round(item.datapoint[0]);
            var y  = item.datapoint[1];
            $tip.html(months[xi] + ' <strong>' + item.series.label + '</strong>: ' + fmtAmt(y))
                .css({ left: pos.pageX + 14, top: pos.pageY - 30 }).show();
        } else {
            $tip.hide();
        }
    });

    var expCats  = <?php
        $pc = array();
        foreach ($expenseCategories as $ec) {
            $pc[] = array('label' => $ec['cat_name'], 'data' => round($ec['amount_total'], 2));
        }
        echo json_encode($pc);
    ?>;

    if (expCats.length > 0) {
        $.plot('#flot-expense-pie', expCats, {
            series: {
                pie: {
                    show: true,
                    radius: 0.9,
                    label: { show: false },
                    innerRadius: 0.35,
                    highlight: { opacity: 0.5 }
                }
            },
            legend: {
                show: true,
                container: '#flot-expense-legend',
                noColumns: 2,
                labelBoxBorderColor: 'transparent'
            },
            grid: { hoverable: true }
        });

        $('#flot-expense-pie').bind('plothover', function (event, pos, item) {
            if (item) {
                var val  = item.series.data[0][1];
                var pct  = item.series.percent ? item.series.percent.toFixed(1) : '';
                $tip.html('<strong>' + item.series.label + '</strong>: ' + fmtAmt(val) + (pct ? ' (' + pct + '%)' : ''))
                    .css({ left: pos.pageX + 14, top: pos.pageY - 30 }).show();
            } else {
                $tip.hide();
            }
        });
    }
}());
</script>

