<?php
include "include/header.php";

if ($_COOKIE['MEMLOGIN_ADMIN_HELLO'] == "") {
    echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
    exit;
}

function parseYearMonthFromDateText($value) {
    $text = trim((string)$value);
    if ($text === '') {
        return null;
    }

    // yyyy-mm-dd / yyyy.mm.dd / yyyy년 m월
    if (preg_match('/(20\d{2})\D{0,3}(1[0-2]|0?[1-9])/u', $text, $m)) {
        return array('year' => (int)$m[1], 'month' => (int)$m[2]);
    }

    // mm/dd/yyyy
    if (preg_match('/(1[0-2]|0?[1-9])\D{1,3}(3[01]|[12]?\d)\D{1,3}(20\d{2})/u', $text, $m)) {
        return array('year' => (int)$m[3], 'month' => (int)$m[1]);
    }

    // yyyymmdd
    if (preg_match('/^(20\d{2})(1[0-2]|0[1-9])([0-2]\d|3[01])$/', $text, $m)) {
        return array('year' => (int)$m[1], 'month' => (int)$m[2]);
    }

    // Excel serial date (e.g. 45500)
    if (preg_match('/^\d{5}$/', $text)) {
        $serial = (int)$text;
        $timestamp = ($serial - 25569) * 86400;
        if ($timestamp > 0) {
            return array(
                'year' => (int)gmdate('Y', $timestamp),
                'month' => (int)gmdate('n', $timestamp),
            );
        }
    }

    $ts = strtotime($text);
    if ($ts !== false) {
        return array('year' => (int)date('Y', $ts), 'month' => (int)date('n', $ts));
    }

    return null;
}

function normalizeAgency($value) {
    $name = trim((string)$value);
    $name = preg_replace('/\s+/u', ' ', $name);
    return $name === '' ? '-' : $name;
}

$division = isset($_REQUEST['division']) ? (int)$_REQUEST['division'] : 3;
$pdx = isset($_REQUEST['pdx']) ? (int)$_REQUEST['pdx'] : 1;
$sub = isset($_REQUEST['sub']) ? (int)$_REQUEST['sub'] : 25;

$history_rows = array();
$history_res = $dbConn->query("SELECT idx, file_name, upload_date, row_count FROM upload_history ORDER BY idx DESC");
if ($history_res) {
    while ($row = mysqli_fetch_assoc($history_res)) {
        $history_rows[] = $row;
    }
}

$history_ids = array();
foreach ($history_rows as $h) {
    $history_ids[] = (int)$h['idx'];
}

$selected_upload_id = isset($_REQUEST['upload_id']) ? (int)$_REQUEST['upload_id'] : 0;
if ($selected_upload_id <= 0 || !in_array($selected_upload_id, $history_ids, true)) {
    $selected_upload_id = !empty($history_rows) ? (int)$history_rows[0]['idx'] : 0;
}

$selected_history = null;
foreach ($history_rows as $h) {
    if ((int)$h['idx'] === $selected_upload_id) {
        $selected_history = $h;
        break;
    }
}

$raw_rows = array();
if ($selected_upload_id > 0) {
    $stmt = $dbConn->prepare("SELECT stDate, agency_name, p_cnt FROM tour_recruitment WHERE upload_id = ?");
    $stmt->bind_param("i", $selected_upload_id);
    $stmt->execute();
    $stmt->bind_result($stDate, $agency_name, $p_cnt);
    while ($stmt->fetch()) {
        $raw_rows[] = array(
            'stDate' => $stDate,
            'agency_name' => $agency_name,
            'p_cnt' => $p_cnt,
        );
    }
    $stmt->close();
}

$parsed_rows = array();
$year_set = array();
$agency_set_all = array();
$agency_columns_all = array();

foreach ($raw_rows as $r) {
    $parsed = parseYearMonthFromDateText($r['stDate'] ?? '');
    if (!$parsed) {
        continue;
    }

    $year = (int)$parsed['year'];
    $month = (int)$parsed['month'];
    if ($month < 1 || $month > 12) {
        continue;
    }

    $agency = normalizeAgency($r['agency_name'] ?? '');
    if (!isset($agency_set_all[$agency])) {
        $agency_set_all[$agency] = true;
        $agency_columns_all[] = $agency;
    }

    $year_set[$year] = true;
    $parsed_rows[] = array(
        'year' => $year,
        'month' => $month,
        'agency' => $agency,
        'p_cnt' => max(0, (int)$r['p_cnt']),
    );
}

$year_options = array_keys($year_set);
if (empty($year_options)) {
    $year_options[] = (int)date('Y');
}
rsort($year_options, SORT_NUMERIC);

$selected_year = isset($_REQUEST['year']) ? (int)$_REQUEST['year'] : 0;
if ($selected_year <= 0 && isset($_REQUEST['searchYear'])) {
    $selected_year = (int)$_REQUEST['searchYear'];
}
if (!in_array($selected_year, $year_options, true)) {
    $selected_year = (int)$year_options[0];
}

$agency_columns = array();
$agency_set_year = array();
foreach ($parsed_rows as $r) {
    if ((int)$r['year'] !== $selected_year) {
        continue;
    }
    $agency = $r['agency'];
    if (!isset($agency_set_year[$agency])) {
        $agency_set_year[$agency] = true;
        $agency_columns[] = $agency;
    }
}
if (empty($agency_columns)) {
    $agency_columns = $agency_columns_all;
}
if (empty($agency_columns)) {
    $agency_columns = array('-');
}

$month_agency_counts = array();
$month_totals = array();
$agency_totals = array();

for ($m = 1; $m <= 12; $m++) {
    $month_totals[$m] = 0;
    $month_agency_counts[$m] = array();
    foreach ($agency_columns as $agency) {
        $month_agency_counts[$m][$agency] = 0;
    }
}
foreach ($agency_columns as $agency) {
    $agency_totals[$agency] = 0;
}

foreach ($parsed_rows as $r) {
    if ((int)$r['year'] !== $selected_year) {
        continue;
    }

    $month = (int)$r['month'];
    $agency = $r['agency'];
    $cnt = (int)$r['p_cnt'];

    if (!isset($month_agency_counts[$month][$agency])) {
        $month_agency_counts[$month][$agency] = 0;
    }
    if (!isset($agency_totals[$agency])) {
        $agency_totals[$agency] = 0;
    }

    $month_agency_counts[$month][$agency] += $cnt;
    $month_totals[$month] += $cnt;
    $agency_totals[$agency] += $cnt;
}

$grand_total = array_sum($month_totals);
$selected_file_name = $selected_history ? (string)$selected_history['file_name'] : '선택된 업로드 없음';
?>

<div id="contentwrapper">
    <div class="main_content">
        <ul class="nav nav-tabs">
            <li><a href="recruitment_upload.php?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>">현황관리</a></li>
            <li class="active"><a href="#">모객현황추이 히스토리</a></li>
        </ul>

        <div class="well well-sm" style="margin-top:15px;">
            <form method="get" class="form-inline" style="margin-bottom:0;">
                <input type="hidden" name="division" value="<?=$division?>">
                <input type="hidden" name="pdx" value="<?=$pdx?>">
                <input type="hidden" name="sub" value="<?=$sub?>">

                <label for="year">연도:</label>
                <select name="year" id="year" class="form-control input-sm" style="width:110px;">
                    <?php foreach ($year_options as $yy) { ?>
                        <option value="<?=$yy?>" <?=$selected_year === (int)$yy ? 'selected' : ''?>><?=$yy?>년</option>
                    <?php } ?>
                </select>

                &nbsp; <label for="upload_id">업로드 히스토리:</label>
                <select name="upload_id" id="upload_id" class="form-control input-sm" style="min-width:250px;">
                    <?php foreach ($history_rows as $h) { ?>
                        <option value="<?=(int)$h['idx']?>" <?=$selected_upload_id === (int)$h['idx'] ? 'selected' : ''?>>
                            <?=htmlspecialchars($h['file_name'])?>
                        </option>
                    <?php } ?>
                </select>

                <button type="submit" class="btn btn-primary btn-sm">필터 적용</button>
                <button type="button" class="btn btn-default btn-sm" onclick="window.print();">프린트</button>
            </form>
        </div>

        <h4 class="text-primary" style="margin:0 0 10px 0;">
            <i class="glyphicon glyphicon-list-alt"></i>
            [<?=htmlspecialchars($selected_file_name)?>] 데이터 (<?=$selected_year?>년)
        </h4>

        <div class="table-responsive">
            <table class="table table-bordered table-condensed" id="trendTable">
                <thead>
                    <tr class="info">
                        <th class="text-center" style="width:12%;">월</th>
                        <?php foreach ($agency_columns as $agency) { ?>
                            <th class="text-center"><?=htmlspecialchars($agency)?></th>
                        <?php } ?>
                        <th class="text-center active" style="width:12%;">월합계</th>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($m = 1; $m <= 12; $m++) { ?>
                        <tr>
                            <th class="text-center active"><?=$m?>월</th>
                            <?php foreach ($agency_columns as $agency) {
                                $value = (int)($month_agency_counts[$m][$agency] ?? 0);
                            ?>
                                <td class="text-right"><?=$value > 0 ? number_format($value) : '-'?></td>
                            <?php } ?>
                            <td class="text-right active"><strong><?=number_format((int)$month_totals[$m])?></strong></td>
                        </tr>
                    <?php } ?>
                </tbody>
                <tfoot>
                    <tr class="warning">
                        <th class="text-center">여행사 합계</th>
                        <?php foreach ($agency_columns as $agency) { ?>
                            <th class="text-right"><?=number_format((int)($agency_totals[$agency] ?? 0))?></th>
                        <?php } ?>
                        <th class="text-right" style="color:red;"><?=number_format((int)$grand_total)?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php include "include/side_m.php"; ?>
</body>
</html>