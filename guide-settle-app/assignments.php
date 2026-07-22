<?php
require_once __DIR__ . '/include/bootstrap.php';
require_once __DIR__ . '/include/layout.php';

$gsaUser = gsa_require_login();
$gsaRole = gsa_user_role($gsaUser);

if ($gsaRole !== 'guide') {
    header('Location: list.php');
    exit;
}

function gsa_assign_default_search()
{
    return array(
        'start_date' => date('Y-m-d', strtotime('-4 days')),
        'end_date' => date('Y-m-d', strtotime('+60 days')),
        'view' => 'upcoming'
    );
}

function gsa_assign_normalize_date($value)
{
    $value = trim((string)$value);
    if ($value === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return '';
    }

    return $value;
}

function gsa_assign_normalize_search($source)
{
    $defaults = gsa_assign_default_search();
    $allowedViews = array('upcoming', 'today', 'all');

    $search = $defaults;
    $search['start_date'] = gsa_assign_normalize_date(isset($source['start_date']) ? $source['start_date'] : $defaults['start_date']);
    $search['end_date'] = gsa_assign_normalize_date(isset($source['end_date']) ? $source['end_date'] : $defaults['end_date']);

    $view = isset($source['view']) ? trim((string)$source['view']) : $defaults['view'];
    if (in_array($view, $allowedViews, true)) {
        $search['view'] = $view;
    }

    return $search;
}

function gsa_assign_product_days($pCode)
{
    $productInfo = getProductMaster($pCode);
    $days = is_array($productInfo) && isset($productInfo['p_day']) ? (int)$productInfo['p_day'] : 1;
    return $days > 0 ? $days : 1;
}

function gsa_assign_period($pCode, $stDate)
{
    $days = gsa_assign_product_days($pCode);
    if ($days <= 1) {
        return $stDate;
    }

    return $stDate . ' ~ ' . date('Y-m-d', strtotime($stDate . ' +' . ($days - 1) . ' day'));
}

function gsa_assign_fetch_rows($search, $guideId)
{
    global $dbConn;

    $conds = array();
    $conds[] = "a.guide_id = '" . $dbConn->real_escape_string($guideId) . "'";
    $conds[] = "a.p_code NOT LIKE 'ADD%'";

    if ($search['start_date'] !== '') {
        $conds[] = "a.stDate >= '" . $dbConn->real_escape_string($search['start_date']) . "'";
    }
    if ($search['end_date'] !== '') {
        $conds[] = "a.stDate <= '" . $dbConn->real_escape_string($search['end_date']) . "'";
    }
    if ($search['view'] === 'upcoming') {
        $conds[] = "a.stDate >= '" . date('Y-m-d') . "'";
    } elseif ($search['view'] === 'today') {
        $conds[] = "a.stDate = '" . date('Y-m-d') . "'";
    }

    $whereSql = 'WHERE ' . implode(' AND ', $conds);
    $sql = "SELECT
                MIN(a.seq_no) AS seq_no,
                a.grand_eCode,
                a.sub_eCode,
                a.p_code,
                a.p_name,
                a.stDate,
                MIN(a.bus_num) AS bus_num,
                COUNT(DISTINCT tc.reserveCode) AS reserve_count
            FROM tour_guide a
            LEFT JOIN tour_car tc
              ON tc.p_code = a.p_code
             AND tc.stDate = a.stDate
             AND tc.grand_eCode = a.grand_eCode
             AND tc.sub_eCode = a.sub_eCode
            {$whereSql}
            GROUP BY a.grand_eCode, a.sub_eCode, a.p_code, a.p_name, a.stDate
            ORDER BY a.stDate ASC, a.p_name ASC";

    $rows = array();
    $result = $dbConn->query($sql);
    if (!$result) {
        return $rows;
    }

    while ($row = $result->fetch_assoc()) {
        $pCode = isset($row['p_code']) ? $row['p_code'] : '';
        $stDate = isset($row['stDate']) ? $row['stDate'] : '';
        $reserveInfo = getReserveInfoCnt($pCode, $stDate);
        $personCount = is_array($reserveInfo) && isset($reserveInfo['cnt']) ? (int)$reserveInfo['cnt'] : 0;

        $rows[] = array(
            'seq_no' => (int)$row['seq_no'],
            'grand_eCode' => isset($row['grand_eCode']) ? $row['grand_eCode'] : '',
            'sub_eCode' => isset($row['sub_eCode']) ? $row['sub_eCode'] : '',
            'p_code' => $pCode,
            'p_name' => isset($row['p_name']) ? $row['p_name'] : '',
            'st_date' => $stDate,
            'period' => gsa_assign_period($pCode, $stDate),
            'days' => gsa_assign_product_days($pCode),
            'bus_num' => isset($row['bus_num']) ? trim((string)$row['bus_num']) : '',
            'reserve_count' => isset($row['reserve_count']) ? (int)$row['reserve_count'] : 0,
            'person_count' => $personCount
        );
    }

    return $rows;
}

function gsa_assign_day_label($date)
{
    $today = date('Y-m-d');
    if ($date === $today) {
        return '&#50724;&#45720;';
    }
    if ($date === date('Y-m-d', strtotime('+1 day'))) {
        return '&#45236;&#51068;';
    }

    $diff = (strtotime($date) - strtotime($today)) / 86400;
    if ($diff > 0 && $diff < 7) {
        return '+' . (int)$diff . '&#51068;';
    }
    if ($diff < 0) {
        return (int)abs($diff) . '&#51068; &#51204;';
    }

    return '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mode']) && $_POST['mode'] === 'search') {
    $_SESSION['gsa_assign_search'] = gsa_assign_normalize_search($_POST);
    header('Location: assignments.php');
    exit;
}

$search = gsa_assign_default_search();
if (isset($_SESSION['gsa_assign_search']) && is_array($_SESSION['gsa_assign_search'])) {
    $search = gsa_assign_normalize_search($_SESSION['gsa_assign_search']);
}
if (isset($_GET['view']) && $_GET['view'] !== '') {
    $search['view'] = gsa_assign_normalize_search(array('view' => $_GET['view']))['view'];
    $_SESSION['gsa_assign_search'] = $search;
}

$baseSearch = $search;
$baseSearch['view'] = 'all';
$baseRows = gsa_assign_fetch_rows($baseSearch, $gsaUser['userid']);
$rows = gsa_assign_fetch_rows($search, $gsaUser['userid']);
$todayRows = 0;
$upcomingRows = 0;
foreach ($baseRows as $row) {
    if ($row['st_date'] === date('Y-m-d')) {
        $todayRows++;
    }
    if ($row['st_date'] >= date('Y-m-d')) {
        $upcomingRows++;
    }
}

$views = array(
    'upcoming' => array('label' => '&#50696;&#51221;', 'count' => $upcomingRows),
    'today' => array('label' => '&#50724;&#45720;', 'count' => $todayRows),
    'all' => array('label' => '&#51204;&#52404;', 'count' => count($baseRows))
);

gsa_layout_head('내배정현황');
?>
<section class="gsa-assign-hero mb-3">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start gap-3">
                <div class="min-w-0">
                    <div class="small text-muted"><?php echo htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="fw-bold fs-5 text-truncate">&#45236;&#48176;&#51221;&#54788;&#54889;</div>
                    <div class="small text-muted mt-1"><?php echo htmlspecialchars($search['start_date'], ENT_QUOTES, 'UTF-8'); ?> ~ <?php echo htmlspecialchars($search['end_date'], ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
                <a href="my.php" class="btn btn-outline-secondary btn-sm">&#51221;&#49328;&#54868;&#47732;</a>
            </div>

            <div class="gsa-assign-summary mt-3">
                <div>
                    <span class="gsa-assign-number"><?php echo number_format(count($rows)); ?></span>
                    <span class="small text-muted">&#44148;</span>
                </div>
                <div class="text-end small text-muted">
                    <div>&#50724;&#45720; <?php echo number_format($todayRows); ?>&#44148;</div>
                    <div>&#50696;&#51221; <?php echo number_format($upcomingRows); ?>&#44148;</div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="gsa-search-sticky mb-3">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="gsa-chip-row mb-3">
                <?php foreach ($views as $viewKey => $viewInfo) {
                    $active = $search['view'] === $viewKey;
                ?>
                <a href="assignments.php?view=<?php echo urlencode($viewKey); ?>" class="btn btn-sm <?php echo $active ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                    <?php echo $viewInfo['label']; ?>
                    <span class="ms-1"><?php echo number_format($viewInfo['count']); ?></span>
                </a>
                <?php } ?>
            </div>

            <form method="post" action="assignments.php" class="gsa-card-stack">
                <input type="hidden" name="mode" value="search">
                <input type="hidden" name="view" value="<?php echo htmlspecialchars($search['view'], ENT_QUOTES, 'UTF-8'); ?>">
                <div class="row g-2">
                    <div class="col-6">
                        <label for="start_date" class="form-label small fw-semibold mb-1">From</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($search['start_date'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="col-6">
                        <label for="end_date" class="form-label small fw-semibold mb-1">To</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($search['end_date'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100">&#51312;&#54924;</button>
            </form>
        </div>
    </div>
</section>

<section class="pb-3">
    <?php if (count($rows) === 0) { ?>
        <div class="gsa-empty-state">&#51312;&#54924;&#46108; &#48176;&#51221;&#51060; &#50630;&#49845;&#45768;&#45796;.</div>
    <?php } else { ?>
        <div class="gsa-card-stack">
            <?php
            $currentDate = '';
            foreach ($rows as $row) {
                if ($currentDate !== $row['st_date']) {
                    $currentDate = $row['st_date'];
                    $dayLabel = gsa_assign_day_label($currentDate);
            ?>
            <div class="gsa-assign-datebar">
                <span><?php echo htmlspecialchars($currentDate, ENT_QUOTES, 'UTF-8'); ?></span>
                <?php if ($dayLabel !== '') { ?><span class="badge text-bg-light border"><?php echo $dayLabel; ?></span><?php } ?>
            </div>
            <?php } ?>
            <?php
                $customerUrl = 'assignment_customers.php?'
                    . '&g_code=' . urlencode($row['grand_eCode'])
                    . '&s_code=' . urlencode($row['sub_eCode'])
                    . '&p_code=' . urlencode($row['p_code'])
                    . '&st=' . urlencode($row['st_date']);
            ?>
            <div class="card border-0 shadow-sm gsa-assign-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div class="min-w-0">
                            <div class="fw-bold gsa-line-clamp-2"><?php echo htmlspecialchars($row['p_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="small text-muted mt-1"><?php echo htmlspecialchars($row['period'], ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <span class="badge rounded-pill text-bg-primary"><?php echo number_format($row['days']); ?>D</span>
                    </div>

                    <div class="gsa-assign-meta mt-3">
                        <div>
                            <span class="text-muted">&#51064;&#50896;</span>
                            <strong><?php echo number_format($row['person_count']); ?></strong>
                        </div>
                        <div>
                            <span class="text-muted">&#50696;&#50557;</span>
                            <strong><?php echo number_format($row['reserve_count']); ?></strong>
                        </div>
                        <div>
                            <span class="text-muted">&#52264;&#47049;</span>
                            <strong><?php echo htmlspecialchars($row['bus_num'] !== '' ? $row['bus_num'] : '-', ENT_QUOTES, 'UTF-8'); ?></strong>
                        </div>
                    </div>

                    <div class="small text-muted mt-3 text-break">
                        <?php echo htmlspecialchars($row['grand_eCode'], ENT_QUOTES, 'UTF-8'); ?> / <?php echo htmlspecialchars($row['sub_eCode'], ENT_QUOTES, 'UTF-8'); ?>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <a href="<?php echo htmlspecialchars($customerUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary flex-grow-1">&#47749;&#45800;&#48372;&#44592;</a>
                        <a href="my.php" class="btn btn-outline-secondary">&#51221;&#49328;</a>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>
    <?php } ?>
</section>
<?php
gsa_layout_foot();
