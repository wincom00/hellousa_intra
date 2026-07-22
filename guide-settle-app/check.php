<?php
require_once __DIR__ . '/include/bootstrap.php';
require_once __DIR__ . '/include/layout.php';

$gsaUser = gsa_require_login();
$gsaRole = gsa_user_role($gsaUser);

function gsa_check_fetch_one($sql, $types = '', $params = array())
{
    global $dbConn;

    $stmt = $dbConn->prepare($sql);
    if (!$stmt) {
        return null;
    }

    if ($types !== '' && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        $stmt->close();
        return null;
    }

    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $row;
}

function gsa_check_fetch_all($sql, $types = '', $params = array())
{
    global $dbConn;

    $stmt = $dbConn->prepare($sql);
    if (!$stmt) {
        return array();
    }

    if ($types !== '' && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        $stmt->close();
        return array();
    }

    $result = $stmt->get_result();
    $rows = array();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    $stmt->close();

    return $rows;
}

function gsa_check_escape($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function gsa_check_money($value)
{
    return '$' . number_format((float)$value, 2);
}

function gsa_check_number_input($value)
{
    if ($value === null || $value === '') {
        return '';
    }

    $normalized = str_replace(',', '', (string)$value);
    if (!is_numeric($normalized)) {
        return (string)$value;
    }

    return (string)(0 + $normalized);
}

function gsa_check_label_or_dash($value)
{
    $value = trim((string)$value);
    if ($value === '') {
        return '-';
    }

    return $value;
}

function gsa_check_period_safe($startDate, $endDate)
{
    $startDate = trim((string)$startDate);
    $endDate = trim((string)$endDate);

    if ($startDate === '' && $endDate === '') {
        return '-';
    }
    if ($endDate === '' || $endDate === $startDate) {
        return $startDate;
    }

    return $startDate . ' ~ ' . $endDate;
}

function gsa_check_render_row($row)
{
    echo '<div class="card border-0 shadow-sm gsa-edit-card js-check-row">';
    echo '<div class="card-body">';
    echo '<div class="d-flex justify-content-between align-items-start gap-3">';
    echo '<div class="fw-semibold">체크 사용내역</div>';
    echo '<button type="button" class="btn btn-outline-danger btn-sm js-check-remove-row">삭제</button>';
    echo '</div>';
    echo '<div class="row g-2 mt-1">';
    echo '<div class="col-6"><label class="form-label small fw-semibold mb-1">체크번호</label><input type="text" class="form-control" name="check_no[]" value="' . gsa_check_escape(isset($row['check_no']) ? $row['check_no'] : '') . '" placeholder="예: 123456"></div>';
    echo '<div class="col-6"><label class="form-label small fw-semibold mb-1">은행</label><input type="text" class="form-control" name="bank_name[]" value="' . gsa_check_escape(isset($row['bank_name']) ? $row['bank_name'] : '') . '" placeholder="예: BOA"></div>';
    echo '<div class="col-6"><label class="form-label small fw-semibold mb-1">사용일</label><input type="date" class="form-control" name="used_date[]" value="' . gsa_check_escape(isset($row['used_date']) ? $row['used_date'] : '') . '"></div>';
    echo '<div class="col-6"><label class="form-label small fw-semibold mb-1">금액</label><input type="number" step="0.01" min="0" class="form-control js-check-amount" name="amount[]" value="' . gsa_check_escape(gsa_check_number_input(isset($row['amount']) ? $row['amount'] : '')) . '" placeholder="0.00"></div>';
    echo '<div class="col-12"><label class="form-label small fw-semibold mb-1">비고</label><input type="text" class="form-control" name="note[]" value="' . gsa_check_escape(isset($row['note']) ? $row['note'] : '') . '" placeholder="메모"></div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
}

$settleCode = isset($_GET['settle_code']) ? trim((string)$_GET['settle_code']) : '';
if ($settleCode === '') {
    echo "<script>alert('정산코드가 없습니다.'); history.back();</script>";
    exit;
}

$saved = isset($_GET['saved']) && $_GET['saved'] === '1';

$masterRow = gsa_check_fetch_one(
    "SELECT
        gsm.*,
        tg.seq_no,
        tg.guide_id,
        tg.p_name,
        tg.p_code,
        tg.stDate AS tg_stDate,
        tg.sub_eCode,
        tg.grand_eCode,
        ml.kor_name
     FROM guide_setmaster gsm
     LEFT JOIN tour_guide tg
       ON tg.grand_eCode = gsm.grand_eCode
      AND tg.sub_eCode = gsm.sub_eCode
     LEFT JOIN member_list ml
       ON ml.userid = tg.guide_id
      AND ml.division = 'guide'
     WHERE gsm.settle_code = ?
     LIMIT 1",
    's',
    array($settleCode)
);

if (!$masterRow) {
    echo "<script>alert('해당 정산을 찾을 수 없습니다.'); history.back();</script>";
    exit;
}

if ($gsaRole === 'guide') {
    $currentUserId = isset($gsaUser['userid']) ? (string)$gsaUser['userid'] : '';
    $ownerId = isset($masterRow['guide_id']) ? (string)$masterRow['guide_id'] : '';
    if ($currentUserId === '' || $ownerId === '' || $currentUserId !== $ownerId) {
        echo "<script>alert('본인 정산만 확인할 수 있습니다.'); history.back();</script>";
        exit;
    }
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

$checkRows = gsa_check_fetch_all(
    "SELECT * FROM guide_set_check WHERE settle_code = ? ORDER BY id ASC",
    's',
    array($settleCode)
);
if (empty($checkRows)) {
    $checkRows = array(
        array(
            'check_no' => '',
            'bank_name' => '',
            'used_date' => '',
            'amount' => '',
            'note' => ''
        )
    );
}

$guideName = isset($masterRow['kor_name']) ? (string)$masterRow['kor_name'] : '';
$summaryLink = 'form.php?seq_no=' . urlencode((string)$masterRow['seq_no']);
$periodText = gsa_check_period_safe(
    isset($masterRow['stDate']) ? $masterRow['stDate'] : '',
    isset($masterRow['edDate']) ? $masterRow['edDate'] : ''
);

$sumAmount = 0.0;
foreach ($checkRows as $row) {
    $sumAmount += (float)(isset($row['amount']) ? $row['amount'] : 0);
}

gsa_layout_head('체크/메모');
?>
<form id="gsaCheckForm" method="post" action="check_save.php">
    <input type="hidden" name="mode" value="save">
    <input type="hidden" name="settle_code" value="<?php echo gsa_check_escape($settleCode); ?>">
    <input type="hidden" name="csrf_token" value="<?php echo gsa_check_escape($_SESSION['csrf_token']); ?>">

    <div class="gsa-card-stack pb-5">
        <a href="<?php echo gsa_check_escape($summaryLink); ?>" class="card border-0 shadow-sm text-decoration-none text-reset">
            <div class="card-body">
                <div class="small text-muted">정산 요약</div>
                <div class="fw-semibold fs-5 mt-1"><?php echo gsa_check_escape(gsa_check_label_or_dash(isset($masterRow['p_name']) ? $masterRow['p_name'] : '')); ?></div>
                <div class="gsa-summary-grid mt-3">
                    <div class="gsa-summary-tile">
                        <div class="small text-muted">정산코드</div>
                        <div class="fw-semibold"><?php echo gsa_check_escape($settleCode); ?></div>
                    </div>
                    <div class="gsa-summary-tile">
                        <div class="small text-muted">행사기간</div>
                        <div class="fw-semibold"><?php echo gsa_check_escape($periodText); ?></div>
                    </div>
                    <div class="gsa-summary-tile">
                        <div class="small text-muted">행사코드</div>
                        <div class="fw-semibold"><?php echo gsa_check_escape(gsa_check_label_or_dash(isset($masterRow['sub_eCode']) ? $masterRow['sub_eCode'] : '')); ?></div>
                    </div>
                    <div class="gsa-summary-tile">
                        <div class="small text-muted">가이드</div>
                        <div class="fw-semibold"><?php echo gsa_check_escape(gsa_check_label_or_dash($guideName)); ?></div>
                    </div>
                </div>
            </div>
        </a>

        <?php if ($saved) { ?>
        <div class="alert alert-success mb-0">저장되었습니다.</div>
        <?php } ?>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center gap-3">
                    <div>
                        <div class="small text-muted">체크 사용내역</div>
                        <div class="fw-semibold">사용한 체크를 카드로 기록합니다.</div>
                    </div>
                    <button type="button" class="btn btn-outline-primary js-check-add-row">+ 행 추가</button>
                </div>

                <div class="gsa-card-stack mt-3" id="gsaCheckList">
                    <?php foreach ($checkRows as $row) {
                        gsa_check_render_row($row);
                    } ?>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="small text-muted mb-2">전체정산 메모</div>
                <textarea class="form-control gsa-textarea-lg" name="guide_memo" placeholder="전체 정산 메모를 입력하세요."><?php echo gsa_check_escape(isset($masterRow['guide_memo']) ? $masterRow['guide_memo'] : ''); ?></textarea>
            </div>
        </div>
    </div>

    <div class="gsa-sticky-action shadow-lg">
        <div class="d-flex justify-content-between align-items-center gap-3">
            <div class="min-w-0">
                <div class="small text-muted">체크 합계</div>
                <div class="fs-4 fw-bold text-primary" id="gsaCheckStickyTotal"><?php echo gsa_check_escape(gsa_check_money($sumAmount)); ?></div>
            </div>
            <button type="submit" class="btn btn-primary btn-lg">저장</button>
        </div>
    </div>
</form>

<template id="tpl-check-row"><?php gsa_check_render_row(array('check_no' => '', 'bank_name' => '', 'used_date' => '', 'amount' => '', 'note' => '')); ?></template>
<?php
gsa_layout_foot();
