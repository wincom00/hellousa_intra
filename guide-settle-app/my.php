<?php
require_once __DIR__ . '/include/bootstrap.php';
require_once __DIR__ . '/include/layout.php';

$gsaUser = gsa_require_login();
$gsaRole = gsa_user_role($gsaUser);

if ($gsaRole !== 'guide') {
    header('Location: list.php');
    exit;
}

function gsa_my_default_search()
{
    return array(
        'start_date' => date('Y-m-d', strtotime('-7 days')),
        'end_date' => date('Y-m-d', strtotime('+1 month')),
        'status_chip' => 'pending'
    );
}

function gsa_my_normalize_date($value)
{
    $value = trim((string)$value);
    if ($value === '') {
        return '';
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return '';
    }

    return $value;
}

function gsa_my_normalize_search($source)
{
    $defaults = gsa_my_default_search();
    $allowedChips = array('pending', 'complete', 'finance', 'all');

    $search = $defaults;
    $search['start_date'] = gsa_my_normalize_date(isset($source['start_date']) ? $source['start_date'] : $defaults['start_date']);
    $search['end_date'] = gsa_my_normalize_date(isset($source['end_date']) ? $source['end_date'] : $defaults['end_date']);

    $statusChip = isset($source['status_chip']) ? trim((string)$source['status_chip']) : $defaults['status_chip'];
    if (in_array($statusChip, $allowedChips, true)) {
        $search['status_chip'] = $statusChip;
    }

    return $search;
}

function gsa_my_period_safe($pCode, $stDate)
{
    global $dbConn;

    $safePCode = $dbConn->real_escape_string((string)$pCode);
    $safeStDate = $dbConn->real_escape_string((string)$stDate);
    $sql = "SELECT b.p_day
            FROM reserve_info a
            INNER JOIN product_master b ON a.p_code = b.p_code
            WHERE a.p_code = '{$safePCode}'
              AND a.stDate = '{$safeStDate}'
              AND a.rev_status != 'CANCEL'
              AND a.rev_status != 'WAIT'
            LIMIT 1";
    $result = $dbConn->query($sql);
    $row = $result ? $result->fetch_assoc() : null;

    if (!$row || !isset($row['p_day']) || (int)$row['p_day'] <= 0) {
        return $stDate;
    }

    $pDay = (int)$row['p_day'] - 1;
    if ($pDay <= 0) {
        return $stDate;
    }

    return $stDate . ' ~ ' . date('Y-m-d', strtotime($stDate . ' +' . $pDay . ' day'));
}

function gsa_my_status_badge($statusHtml)
{
    $label = trim(strip_tags(str_replace(array('<br>', '<br/>', '<br />'), ' ', (string)$statusHtml)));
    $label = preg_replace('/\s+/u', ' ', $label);
    $badgeClass = 'text-bg-light border text-dark';

    if ($label === '') {
        $label = '미등록';
    }

    if (strpos($label, '정산보고완료') !== false) {
        $badgeClass = 'text-bg-success';
    } elseif (strpos($label, '회계확인') !== false) {
        $badgeClass = 'text-bg-primary';
    } elseif (strpos($label, '대표이사확인') !== false) {
        $badgeClass = 'text-bg-info';
    } elseif (strpos($label, '체크나감') !== false) {
        $badgeClass = 'text-bg-secondary';
    } elseif (strpos($label, '등록') !== false) {
        $badgeClass = 'text-bg-warning text-dark';
    }

    return array(
        'label' => $label,
        'class' => $badgeClass
    );
}

function gsa_my_fetch_rows($search, $userId, $chipFilter = '')
{
    global $dbConn;

    $conds = array();
    if ($search['start_date'] !== '') {
        $conds[] = "a.stDate >= '" . $dbConn->real_escape_string($search['start_date']) . "'";
    }
    if ($search['end_date'] !== '') {
        $conds[] = "a.stDate <= '" . $dbConn->real_escape_string($search['end_date']) . "'";
    }

    $conds[] = "a.guide_id = '" . $dbConn->real_escape_string($userId) . "'";
    $conds[] = "a.p_code NOT LIKE 'ADD%'";
    $conds[] = "EXISTS (
                    SELECT 1
                    FROM tour_master b
                    WHERE b.grand_eCode = a.grand_eCode
                      AND b.p_code = a.p_code
                )";

    if ($chipFilter === 'pending') {
        $conds[] = "(gsm.settle_code IS NULL OR gsm.reg_status = '' OR gsm.reg_status = 'DONE')";
    } elseif ($chipFilter === 'complete') {
        $conds[] = "(gsm.reg_status = 'COMPLETE' AND (gsm.finance_st IS NULL OR gsm.finance_st <> 'V'))";
    } elseif ($chipFilter === 'finance') {
        $conds[] = "gsm.finance_st = 'V'";
    }

    $whereSql = 'WHERE ' . implode(' AND ', $conds);

    // 한 행사(grand_eCode+sub_eCode)에 정산마스터가 중복 존재해도 '가장 확정된' 1건만 조인해
    // 목록 행이 중복(뻥튀기)되지 않게 한다. (admin/guide_settle.php 와 동일 기준)
    $gsmJoin = "LEFT JOIN (
                    SELECT gm.*
                    FROM guide_setmaster gm
                    INNER JOIN (
                        SELECT grand_eCode, sub_eCode,
                            SUBSTRING_INDEX(GROUP_CONCAT(seq_no ORDER BY " . guideMasterPickOrderExpr() . "), ',', 1) AS pick_seq
                        FROM guide_setmaster
                        GROUP BY grand_eCode, sub_eCode
                    ) gmx
                        ON gmx.grand_eCode = gm.grand_eCode
                       AND gmx.sub_eCode = gm.sub_eCode
                       AND gmx.pick_seq = gm.seq_no
                ) gsm
                    ON gsm.grand_eCode = a.grand_eCode
                   AND gsm.sub_eCode = a.sub_eCode";

    $sql = "SELECT
                a.seq_no,
                a.grand_eCode,
                a.sub_eCode,
                a.stDate,
                a.guide_id,
                a.p_code,
                a.p_name,
                gsm.settle_code,
                gsm.finance_date,
                gsm.report_date,
                gsm.check_out,
                gsm.check_date,
                gsm.reg_status,
                gsm.finance_st,
                ml.kor_name
            FROM tour_guide a
            {$gsmJoin}
            LEFT JOIN member_list ml
              ON ml.userid = a.guide_id
            {$whereSql}
            ORDER BY a.stDate DESC";

    $rows = array();
    $result = $dbConn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $guideCode = getGuideCode($row['grand_eCode'], $row['sub_eCode']);
            $settleCode = isset($row['settle_code']) ? trim((string)$row['settle_code']) : '';
            if ($settleCode === '' && is_array($guideCode) && isset($guideCode['settle_code'])) {
                $settleCode = (string)$guideCode['settle_code'];
            }

            $statusHtml = getGuideStatus($row['grand_eCode'], $row['sub_eCode'], $row['stDate']);
            $status = gsa_my_status_badge($statusHtml);
            $reserveInfo = getReserveInfoCnt($row['p_code'], $row['stDate']);
            $personCount = is_array($reserveInfo) && isset($reserveInfo['cnt']) ? (int)$reserveInfo['cnt'] : 0;

            $rows[] = array(
                'seq_no' => (int)$row['seq_no'],
                'settle_code' => $settleCode,
                'st_date' => isset($row['stDate']) ? $row['stDate'] : '',
                'p_name' => isset($row['p_name']) ? $row['p_name'] : '',
                'period' => gsa_my_period_safe($row['p_code'], $row['stDate']),
                'person_count' => $personCount,
                'status_label' => $status['label'],
                'status_class' => $status['class'],
                'reg_status' => isset($row['reg_status']) ? trim((string)$row['reg_status']) : '',
                'finance_st' => isset($row['finance_st']) ? trim((string)$row['finance_st']) : '',
                'check_out' => isset($row['check_out']) ? trim((string)$row['check_out']) : ''
            );
        }
    }

    return $rows;
}

function gsa_my_count_by_chip($rows)
{
    $counts = array(
        'pending' => 0,
        'complete' => 0,
        'finance' => 0,
        'all' => count($rows)
    );

    foreach ($rows as $row) {
        $regStatus = isset($row['reg_status']) ? trim((string)$row['reg_status']) : '';
        $financeSt = isset($row['finance_st']) ? trim((string)$row['finance_st']) : '';
        $settleCode = isset($row['settle_code']) ? trim((string)$row['settle_code']) : '';

        if ($settleCode === '' || $regStatus === '' || $regStatus === 'DONE') {
            $counts['pending']++;
        }
        if ($regStatus === 'COMPLETE' && $financeSt !== 'V') {
            $counts['complete']++;
        }
        if ($financeSt === 'V') {
            $counts['finance']++;
        }
    }

    return $counts;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mode']) && $_POST['mode'] === 'search') {
    $_SESSION['gsa_my_search'] = gsa_my_normalize_search($_POST);
    header('Location: my.php');
    exit;
}

$search = gsa_my_default_search();
if (isset($_SESSION['gsa_my_search']) && is_array($_SESSION['gsa_my_search'])) {
    $search = gsa_my_normalize_search($_SESSION['gsa_my_search']);
}
if (isset($_GET['chip']) && $_GET['chip'] !== '') {
    $search['status_chip'] = gsa_my_normalize_search(array('status_chip' => $_GET['chip']))['status_chip'];
    $_SESSION['gsa_my_search'] = $search;
}

$baseRows = gsa_my_fetch_rows($search, $gsaUser['userid'], '');
$chipCounts = gsa_my_count_by_chip($baseRows);
$rows = gsa_my_fetch_rows($search, $gsaUser['userid'], $search['status_chip'] === 'all' ? '' : $search['status_chip']);

$todayText = date('Y-m-d');
$guideName = isset($gsaUser['kor_name']) ? trim((string)$gsaUser['kor_name']) : '';
$chips = array(
    'pending' => array('label' => '입력 대기', 'btn' => 'btn-warning'),
    'complete' => array('label' => '제출 완료', 'btn' => 'btn-primary'),
    'finance' => array('label' => '회계 확인됨', 'btn' => 'btn-success'),
    'all' => array('label' => '전체', 'btn' => 'btn-secondary')
);

gsa_layout_head('내 정산');
?>
<section class="mb-3">
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start gap-3">
                <div class="min-w-0">
                    <div class="small text-muted"><?php echo htmlspecialchars($todayText, ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="fw-semibold fs-5 text-truncate">안녕하세요, <?php echo htmlspecialchars($guideName !== '' ? $guideName : $gsaUser['userid'], ENT_QUOTES, 'UTF-8'); ?> 가이드님</div>
                </div>
                <a href="../admin/logout.php" class="btn btn-outline-primary btn-sm">로그아웃</a>
            </div>

            <div class="row g-1 mt-3">
                <div class="col-4">
                    <a href="memo.php" class="btn btn-primary btn-sm w-100">&#47700;&#47784;&#48148;&#47196;&#44032;&#44592;</a>
                </div>
                <div class="col-4">
                    <a href="assignments.php" class="btn btn-outline-primary btn-sm w-100">&#45236;&#48176;&#51221;&#54788;&#54889;</a>
                </div>
                <div class="col-4">
                    <a href="schedule.php" class="btn btn-outline-primary btn-sm w-100">전체스케줄표    </a>
                </div>
            </div>

            <div class="gsa-chip-row mt-3">
                <?php foreach ($chips as $chipKey => $chipInfo) {
                    $isActive = $search['status_chip'] === $chipKey;
                    $chipClass = $isActive ? $chipInfo['btn'] : 'btn-outline-secondary';
                ?>
                <a href="my.php?chip=<?php echo urlencode($chipKey); ?>" class="btn btn-sm <?php echo $chipClass; ?>">
                    <?php echo htmlspecialchars($chipInfo['label'], ENT_QUOTES, 'UTF-8'); ?>
                    <span class="ms-1"><?php echo number_format(isset($chipCounts[$chipKey]) ? $chipCounts[$chipKey] : 0); ?></span>
                </a>
                <?php } ?>
            </div>

            <form method="post" action="my.php" class="gsa-card-stack mt-3">
                <input type="hidden" name="mode" value="search">
                <input type="hidden" name="status_chip" value="<?php echo htmlspecialchars($search['status_chip'], ENT_QUOTES, 'UTF-8'); ?>">
                <div class="row g-2">
                    <div class="col-6">
                        <label for="start_date" class="form-label small fw-semibold mb-1">행사일 From</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($search['start_date'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="col-6">
                        <label for="end_date" class="form-label small fw-semibold mb-1">행사일 To</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($search['end_date'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </div>

                <div id="gsaQuickRange" class="gsa-chip-row">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-range="30d">최근 30일</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-range="thism">이번 달</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-range="nextm">다음 달</button>
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-100">기간 적용</button>
            </form>
        </div>
    </div>
</section>

<section class="mb-3">
    <?php if (count($rows) === 0) { ?>
        <div class="card shadow-sm border-0">
            <div class="card-body text-center text-muted py-4">
                이번 기간에 배정된 행사가 없습니다.
            </div>
        </div>
    <?php } else { ?>
        <div class="gsa-card-stack">
            <?php foreach ($rows as $row) {
                $isPending = $row['settle_code'] === '' || $row['reg_status'] === '' || $row['reg_status'] === 'DONE';
                $formUrl = 'form.php?seq_no=' . (int)$row['seq_no'];
                $checkUrl = $row['settle_code'] !== '' ? 'check.php?settle_code=' . urlencode($row['settle_code']) : '';
                $canRegisterCancel = false;
                $canSubmit = false;
                $canSubmitCancel = false;
            ?>
            <div class="card shadow-sm border-0 gsa-list-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                        <div class="min-w-0">
                            <div class="fw-bold fs-6 text-truncate"><?php echo htmlspecialchars($row['settle_code'] !== '' ? $row['settle_code'] : '미등록', ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="small text-muted"><?php echo htmlspecialchars($row['st_date'], ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <span class="badge rounded-pill <?php echo htmlspecialchars($row['status_class'], ENT_QUOTES, 'UTF-8'); ?> gsa-status-badge">
                            <?php echo htmlspecialchars($row['status_label'], ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </div>
                    <div class="fw-semibold mb-2 gsa-line-clamp-2"><?php echo htmlspecialchars($row['p_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="small text-muted gsa-card-meta">
                        <div><?php echo htmlspecialchars($row['period'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <div>인원 <?php echo number_format($row['person_count']); ?>명</div>
                    </div>
                    <div class="d-flex gap-2 mt-3">
                        <a href="<?php echo htmlspecialchars($formUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn <?php echo $isPending ? 'btn-primary' : 'btn-outline-primary'; ?> btn-sm flex-grow-1">
                            <?php echo $isPending ? '정산 입력' : '보기'; ?>
                        </a>
                        <?php if ($checkUrl !== '') { ?>
                        <a href="<?php echo htmlspecialchars($checkUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary btn-sm">체크/메모</a>
                        <?php } else { ?>
                        <button type="button" class="btn btn-outline-secondary btn-sm" disabled>체크/메모</button>
                        <?php } ?>
                    </div>
                    <?php if ($canSubmit) { ?>
                    <div class="d-flex gap-2 mt-2">
                        <?php if ($canRegisterCancel) { ?>
                        <button type="button" class="btn btn-outline-danger btn-sm flex-grow-1 js-gsa-action" data-action="register_cancel" data-settle-code="<?php echo htmlspecialchars($row['settle_code'], ENT_QUOTES, 'UTF-8'); ?>">등록 취소</button>
                        <?php } ?>
                        <?php if ($canSubmit) { ?>
                        <button type="button" class="btn btn-success btn-sm flex-grow-1 js-gsa-action" data-action="submit" data-settle-code="<?php echo htmlspecialchars($row['settle_code'], ENT_QUOTES, 'UTF-8'); ?>">정산 제출</button>
                        <?php } ?>
                        <?php if ($canSubmitCancel) { ?>
                        <button type="button" class="btn btn-outline-secondary btn-sm flex-grow-1 js-gsa-action" data-action="submit_cancel" data-settle-code="<?php echo htmlspecialchars($row['settle_code'], ENT_QUOTES, 'UTF-8'); ?>">제출 취소</button>
                        <?php } ?>
                    </div>
                    <?php } ?>
                </div>
            </div>
            <?php } ?>
        </div>
    <?php } ?>
</section>
<?php
gsa_layout_foot();
