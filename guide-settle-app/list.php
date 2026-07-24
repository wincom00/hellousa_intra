<?php
require_once __DIR__ . '/include/bootstrap.php';
require_once __DIR__ . '/include/layout.php';

$gsaUser = gsa_require_login();
$gsaRole = gsa_user_role($gsaUser);

if ($gsaRole === 'guide') {
    header('Location: my.php');
    exit;
}

function gsa_list_default_search()
{
    return array(
        'start_date' => date('Y-m-d', strtotime('-7 days')),
        'end_date' => date('Y-m-d', strtotime('+1 month')),
        'view_type' => '',
        'keyword' => ''
    );
}

function gsa_list_normalize_date($value)
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

function gsa_list_normalize_search($source)
{
    $defaults = gsa_list_default_search();
    $allowedViewTypes = array('', 'unchecked', 'finance_done', 'report_missing');

    $search = $defaults;
    $search['start_date'] = gsa_list_normalize_date(isset($source['start_date']) ? $source['start_date'] : $defaults['start_date']);
    $search['end_date'] = gsa_list_normalize_date(isset($source['end_date']) ? $source['end_date'] : $defaults['end_date']);

    $viewType = isset($source['view_type']) ? trim((string)$source['view_type']) : '';
    if (in_array($viewType, $allowedViewTypes, true)) {
        $search['view_type'] = $viewType;
    }

    $keyword = isset($source['keyword']) ? trim((string)$source['keyword']) : '';
    if ($keyword === '') {
        $legacyParts = array();
        $legacyKeys = array('guide_kw', 'settle_code_kw', 'pname_kw');
        foreach ($legacyKeys as $legacyKey) {
            if (isset($source[$legacyKey])) {
                $legacyValue = trim((string)$source[$legacyKey]);
                if ($legacyValue !== '') {
                    $legacyParts[] = $legacyValue;
                }
            }
        }
        $keyword = implode(' ', $legacyParts);
    }
    $search['keyword'] = $keyword;

    return $search;
}

function gsa_fetch_period_safe($pCode, $stDate)
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

/**
 * Generate status badge info from HTML status string.
 *
 * @param string $statusHtml HTML content containing status text
 * @return array{label:string, class:string}
 */
function gsa_status_badge($statusHtml)
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
    } elseif (strpos($label, '미등록') !== false) {
        $badgeClass = 'text-bg-light border text-dark';
    } elseif (strpos($label, '등록') !== false) {
        $badgeClass = 'text-bg-warning text-dark';
    }

    return array(
        'label' => $label,
        'class' => $badgeClass
    );
}

function gsa_fetch_list_rows($search, $role, $userId, $page, $pageSize)
{
    global $dbConn;

    $conds = array();

    if ($search['start_date'] !== '') {
        $conds[] = "a.stDate >= '" . $dbConn->real_escape_string($search['start_date']) . "'";
    }
    if ($search['end_date'] !== '') {
        $conds[] = "a.stDate <= '" . $dbConn->real_escape_string($search['end_date']) . "'";
    }

    if ($search['view_type'] === 'unchecked') {
        $conds[] = "(gsm.check_out IS NULL OR gsm.check_out <> 'V')";
    } elseif ($search['view_type'] === 'finance_done') {
        $conds[] = "(gsm.finance_st IS NOT NULL AND gsm.finance_st <> '' AND gsm.finance_date IS NOT NULL)";
    } elseif ($search['view_type'] === 'report_missing') {
        $conds[] = "(gsm.report_date IS NULL OR gsm.report_date = '')";
    }

    if ($search['keyword'] !== '') {
        $safeKeyword = $dbConn->real_escape_string($search['keyword']);
        $conds[] = "(a.guide_id LIKE '%{$safeKeyword}%'
                    OR ml.kor_name LIKE '%{$safeKeyword}%'
                    OR gsm.settle_code LIKE '%{$safeKeyword}%'
                    OR a.p_name LIKE '%{$safeKeyword}%')";
    }

    if ($role === 'guide') {
        $conds[] = "a.guide_id = '" . $dbConn->real_escape_string($userId) . "'";
    }

    $conds[] = "a.p_code NOT LIKE 'ADD%'";
    $conds[] = "EXISTS (
                    SELECT 1
                    FROM tour_master b
                    WHERE b.grand_eCode = a.grand_eCode
                      AND b.p_code = a.p_code
                )";

    $whereSql = 'WHERE ' . implode(' AND ', $conds);

    $orderBy = "a.stDate DESC";

    // 한 행사(grand_eCode+sub_eCode)에 정산마스터가 중복 존재해도 '가장 확정된' 1건만 조인해
    // count/list 양쪽에서 행이 중복(뻥튀기)되지 않게 한다. (admin/guide_settle.php 와 동일 기준)
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

    $countSql = "SELECT COUNT(*) AS cnt
                 FROM tour_guide a
                 {$gsmJoin}
                 LEFT JOIN member_list ml
                   ON ml.userid = a.guide_id
                 {$whereSql}";
    $countResult = $dbConn->query($countSql);
    $countRow = $countResult ? $countResult->fetch_assoc() : array();
    $totalCount = isset($countRow['cnt']) ? (int)$countRow['cnt'] : 0;

    $offset = ($page - 1) * $pageSize;
    $listSql = "SELECT
                    a.seq_no,
                    a.grand_eCode,
                    a.sub_eCode,
                    a.stDate,
                    a.guide_id,
                    a.p_code,
                    a.p_name,
                    gsm.settle_code,
                    ml.kor_name
                FROM tour_guide a
                {$gsmJoin}
                LEFT JOIN member_list ml
                  ON ml.userid = a.guide_id
                {$whereSql}
                ORDER BY {$orderBy}
                LIMIT " . (int)$offset . ", " . (int)$pageSize;

    $rows = array();
    $result = $dbConn->query($listSql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $guideCode = getGuideCode($row['grand_eCode'], $row['sub_eCode']);
            $settleCode = isset($row['settle_code']) ? trim((string)$row['settle_code']) : '';
            if ($settleCode === '' && is_array($guideCode) && isset($guideCode['settle_code'])) {
                $settleCode = (string)$guideCode['settle_code'];
            }

            $statusHtml = getGuideStatus($row['grand_eCode'], $row['sub_eCode']);
            $status = gsa_status_badge($statusHtml);
            $reserveInfo = getReserveInfoCnt($row['p_code'], $row['stDate']);
            $personCount = is_array($reserveInfo) && isset($reserveInfo['cnt']) ? (int)$reserveInfo['cnt'] : 0;
            $guideName = isset($row['kor_name']) ? trim((string)$row['kor_name']) : '';
            if ($guideName === '') {
                $guideInfo = getinfo_dbMember($row['guide_id']);
                if (is_array($guideInfo) && isset($guideInfo['kor_name'])) {
                    $guideName = (string)$guideInfo['kor_name'];
                }
            }

            $rows[] = array(
                'seq_no' => (int)$row['seq_no'],
                'settle_code' => $settleCode,
                'st_date' => $row['stDate'],
                'p_name' => $row['p_name'],
                'guide_name' => $guideName,
                'person_count' => $personCount,
                'period' => gsa_fetch_period_safe($row['p_code'], $row['stDate']),
                'status_label' => $status['label'],
                'status_class' => $status['class']
            );
        }
    }

    return array(
        'rows' => $rows,
        'total_count' => $totalCount
    );
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mode']) && $_POST['mode'] === 'search') {
    $_SESSION['gsa_list_search'] = gsa_list_normalize_search($_POST);
    header('Location: list.php');
    exit;
}

$search = gsa_list_default_search();
if (isset($_SESSION['gsa_list_search']) && is_array($_SESSION['gsa_list_search'])) {
    $search = gsa_list_normalize_search($_SESSION['gsa_list_search']);
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}
$pageSize = 50;
$listData = gsa_fetch_list_rows($search, $gsaRole, $gsaUser['userid'], $page, $pageSize);
$rows = $listData['rows'];
$totalCount = $listData['total_count'];
$shownCount = ($page - 1) * $pageSize + count($rows);
$hasMore = $shownCount < $totalCount;

gsa_layout_head('가이드정산 리스트');
?>
<section class="gsa-search-sticky mb-3">
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="post" action="list.php" class="gsa-card-stack">
                <input type="hidden" name="mode" value="search">
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

                <div class="row g-2">
                    <div class="col-12">
                        <label for="view_type" class="form-label small fw-semibold mb-1">조회유형</label>
                        <select name="view_type" id="view_type" class="form-select">
                            <option value=""<?php echo $search['view_type'] === '' ? ' selected' : ''; ?>>전체</option>
                            <option value="unchecked"<?php echo $search['view_type'] === 'unchecked' ? ' selected' : ''; ?>>체크미완료</option>
                            <option value="finance_done"<?php echo $search['view_type'] === 'finance_done' ? ' selected' : ''; ?>>회계확인완료</option>
                            <option value="report_missing"<?php echo $search['view_type'] === 'report_missing' ? ' selected' : ''; ?>>가이드보고미제출</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label for="keyword" class="form-label small fw-semibold mb-1">통합 검색</label>
                        <input type="text" class="form-control" id="keyword" name="keyword" value="<?php echo htmlspecialchars($search['keyword'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="가이드명 / 정산코드 / 상품명">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-100">검색</button>
            </form>
        </div>
    </div>
</section>

<section class="mb-3">
    <div class="d-flex align-items-center justify-content-between mb-2 px-1">
        <div class="small text-muted">총 <?php echo number_format($totalCount); ?>건</div>
        <div class="small text-muted"><?php echo number_format($shownCount); ?>건 표시</div>
    </div>

    <?php if (count($rows) === 0) { ?>
        <div class="card shadow-sm border-0">
            <div class="card-body text-center text-muted py-4">
                검색 결과가 없습니다.
            </div>
        </div>
    <?php } else { ?>
        <div class="gsa-card-stack">
            <?php foreach ($rows as $row) { ?>
                <a class="card shadow-sm border-0 text-decoration-none text-reset gsa-list-card" href="form.php?seq_no=<?php echo (int)$row['seq_no']; ?>&scode=<?php echo urlencode($row['settle_code']); ?>">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <div class="min-w-0">
                                <div class="fw-bold fs-6 text-truncate"><?php echo htmlspecialchars($row['settle_code'] !== '' ? $row['settle_code'] : '정산코드 없음', ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="small text-muted"><?php echo htmlspecialchars($row['st_date'], ENT_QUOTES, 'UTF-8'); ?></div>
                            </div>
                            <span class="badge rounded-pill <?php echo htmlspecialchars($row['status_class'], ENT_QUOTES, 'UTF-8'); ?> gsa-status-badge">
                                <?php echo htmlspecialchars($row['status_label'], ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </div>
                        <div class="fw-semibold mb-2 gsa-line-clamp-2"><?php echo htmlspecialchars($row['p_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="small text-muted gsa-card-meta">
                            <div>가이드: <?php echo htmlspecialchars($row['guide_name'] !== '' ? $row['guide_name'] : '-', ENT_QUOTES, 'UTF-8'); ?></div>
                            <div>인원: <?php echo number_format($row['person_count']); ?>명</div>
                            <div>기간: <?php echo htmlspecialchars($row['period'], ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                    </div>
                </a>
            <?php } ?>
        </div>
    <?php } ?>
</section>

<?php if ($hasMore) { ?>
    <div class="d-grid pb-3">
        <a href="list.php?page=<?php echo $page + 1; ?>" class="btn btn-outline-primary btn-lg">더 보기</a>
    </div>
<?php } ?>
<?php
gsa_layout_foot();
