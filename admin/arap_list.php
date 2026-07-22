<?php
if (isset($_POST['mode']) && $_POST['mode'] === 'inline_save') {
    ob_start();
    require_once __DIR__ . "/include/inc_base.php";
    require_once __DIR__ . "/include/arap_common.php";
    ob_end_clean();

    if (empty($_COOKIE['MEMLOGIN_ADMIN_HELLO'])) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('ok' => false, 'message' => '로그인이 필요합니다.'));
        exit;
    }

    include __DIR__ . "/inc_arap_inline_save.php";
    exit;
}

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
    $sub = '10';
}

if (!hasMenuAccess($division, $pdx, $sub)) {
    $goUrl_1 = "index.php";
    Misc::jvAlert("권한이 있는 메뉴가 아닙니다. 확인 후 사용해 주세요.","");
    echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
    exit;
}

if ($mode == "save") {
    include __DIR__ . "/inc_arap_save.php";
}

if ($mode == "delete") {
    include __DIR__ . "/inc_arap_delete.php";
}

if ($mode == "inline_save") {
    include __DIR__ . "/inc_arap_inline_save.php";
    exit;
}

$month = arapGetMonthValue(isset($_GET['month']) ? $_GET['month'] : '');
$txType = isset($_GET['tx_type']) ? trim((string)$_GET['tx_type']) : '';
$filterCatId = isset($_GET['cat_id']) ? (int)$_GET['cat_id'] : 0;
$filterSubId = isset($_GET['sub_id']) ? (int)$_GET['sub_id'] : 0;
$filterMethodId = isset($_GET['method_id']) ? (int)$_GET['method_id'] : 0;
$filterStatus = isset($_GET['status']) ? trim((string)$_GET['status']) : '';
$keyword = isset($_GET['keyword']) ? trim((string)$_GET['keyword']) : '';

$statusOptions = array(
    'PENDING' => '대기',
    'COMPLETED' => '완료',
    'CANCELLED' => '취소',
    'HOLD' => '보류'
);

list($startDate, $endDate) = arapGetMonthRange($month);

$categories = arapFetchCategories();
$subcategories = arapFetchSubcategories();
$methods = arapFetchMethods();
$banks = arapFetchBanks();

$allSubcategories = array();
foreach ($subcategories as $subcategory) {
    $catKey = (int)$subcategory['cat_id'];
    if (!isset($allSubcategories[$catKey])) {
        $allSubcategories[$catKey] = array();
    }
    $allSubcategories[$catKey][] = array(
        'sub_id' => (int)$subcategory['sub_id'],
        'sub_name' => $subcategory['sub_name'],
        'use_yn' => $subcategory['use_yn']
    );
}

$where = array();
$where[] = "t.tx_date BETWEEN '" . $dbConn->real_escape_string($startDate) . "' AND '" . $dbConn->real_escape_string($endDate) . "'";
if ($txType !== '') {
    $where[] = "t.tx_type = '" . $dbConn->real_escape_string($txType) . "'";
}
if ($filterCatId > 0) {
    $where[] = "t.cat_id = " . $filterCatId;
}
if ($filterSubId > 0) {
    $where[] = "t.sub_id = " . $filterSubId;
}
if ($filterMethodId > 0) {
    $where[] = "t.method_id = " . $filterMethodId;
}
if (isset($statusOptions[$filterStatus])) {
    $where[] = "t.status = '" . $dbConn->real_escape_string($filterStatus) . "'";
}
if ($keyword !== '') {
    $safeKeyword = $dbConn->real_escape_string($keyword);
    $where[] = "(t.description LIKE '%{$safeKeyword}%' OR t.memo LIKE '%{$safeKeyword}%')";
}
$whereSql = implode(" AND ", $where);

$summarySql = "SELECT
                    SUM(CASE WHEN t.tx_type = 'IN' THEN t.amount ELSE 0 END) AS income_total,
                    SUM(CASE WHEN t.tx_type = 'OUT' THEN t.amount ELSE 0 END) AS expense_total
               FROM arap_transaction t
               WHERE {$whereSql}";
$summaryResult = $dbConn->query($summarySql);
$summaryRow = $summaryResult ? $summaryResult->fetch_assoc() : array();
$incomeTotal = isset($summaryRow['income_total']) ? abs((float)$summaryRow['income_total']) : 0;
$expenseTotal = isset($summaryRow['expense_total']) ? abs((float)$summaryRow['expense_total']) : 0;
$balanceTotal = $incomeTotal - $expenseTotal;
$exportUrl = arapPageUrl('arap_export.php', array(
    'export_type' => 'list',
    'division' => $division,
    'pdx' => $pdx,
    'sub' => $sub,
    'month' => $month,
    'tx_type' => $txType,
    'cat_id' => $filterCatId > 0 ? $filterCatId : '',
    'sub_id' => $filterSubId > 0 ? $filterSubId : '',
    'method_id' => $filterMethodId > 0 ? $filterMethodId : '',
    'status' => $filterStatus,
    'keyword' => $keyword
));
$targetCardsUrl = arapPageUrl('arap_target_cards.php', array(
    'division' => $division,
    'pdx' => $pdx,
    'sub' => $sub,
    'month' => $month,
    'keyword' => $keyword
));
$chartUrl = arapPageUrl('arap_chart.php', array(
    'division' => $division,
    'pdx' => $pdx,
    'sub' => $sub,
    'month' => $month,
));

$listSql = "SELECT
                t.tx_id,
                t.tx_date,
                t.tx_type,
                t.cat_id,
                t.sub_id,
                t.description,
                t.method_id,
                t.bank_id,
                t.check_no,
                t.amount,
                t.memo,
                t.status,
                t.src_ref,
                c.cat_name,
                s.sub_name,
                m.method_name,
                b.bank_name
            FROM arap_transaction t
            INNER JOIN arap_category c ON c.cat_id = t.cat_id
            LEFT JOIN arap_subcategory s ON s.sub_id = t.sub_id
            LEFT JOIN arap_method m ON m.method_id = t.method_id
            LEFT JOIN arap_bank b ON b.bank_id = t.bank_id
            WHERE {$whereSql}
            ORDER BY t.tx_date DESC, t.tx_id DESC";
$listResult = $dbConn->query($listSql);
$rows = array();
if ($listResult) {
    while ($row = $listResult->fetch_assoc()) {
        $rows[] = $row;
    }
}
?>
<style>
.arap-card {
    border: 1px solid #dcdcdc;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 15px;
    background: #fff;
}
.arap-card .label-text {
    display: block;
    font-size: 12px;
    color: #777;
    margin-bottom: 5px;
}
.arap-card .value-text {
    font-size: 24px;
    font-weight: bold;
}
.arap-income {
    color: #2c7a2c;
}
.arap-expense {
    color: #b94a48;
}
.arap-balance {
    color: #245580;
}
.arap-table-actions .btn {
    margin-right: 4px;
}
.arap-cell-editable {
    cursor: pointer;
    position: relative;
}
.arap-status-pending {
    color: #8a6d3b;
    font-weight: 600;
}
.arap-status-completed {
    color: #2c7a2c;
    font-weight: 600;
}
.arap-status-cancelled {
    color: #b94a48;
    font-weight: 600;
    text-decoration: line-through;
}
.arap-status-hold {
    color: #31708f;
    font-weight: 600;
}
.arap-cell-editable:hover {
    background: #fffbe6;
}
.arap-row-locked td {
    background: #f7f9fb;
}
.arap-row-locked .arap-status-completed {
    cursor: default;
}
.arap-cell-editable.arap-cell-editing {
    background: #f0f7ff;
    padding: 2px 4px;
}
.arap-cell-editable input.arap-cell-input,
.arap-cell-editable select.arap-cell-input {
    box-sizing: border-box;
    font-size: inherit;
    height: 26px;
    margin: 0;
    padding: 2px 4px;
    width: 100%;
}
.arap-cell-saving {
    opacity: 0.6;
}
.arap-cell-error {
    background: #fde2e2 !important;
}
.arap-cell-success {
    background: #d4edda !important;
    transition: background 0.6s ease-out;
}
.arap-row-pending td {
    background: #fdf6e3 !important;
}
</style>

<div id="contentwrapper">
    <div class="main_content">
        <div id="jCrumbs" class="breadCrumb module">
            <ul>
                <li><a href="/"><i class="glyphicon glyphicon-home"></i></a></li>
                <li><a href="#">수입·지출</a></li>
                <li>거래내역</li>
            </ul>
        </div>

        <div class="row">
            <div class="col-sm-4">
                <div class="arap-card">
                    <span class="label-text">수입합계</span>
                    <span class="value-text arap-income">$<?= arapFormatAmount($incomeTotal) ?></span>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="arap-card">
                    <span class="label-text">지출합계</span>
                    <span class="value-text arap-expense">-$<?= arapFormatAmount($expenseTotal) ?></span>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="arap-card">
                    <span class="label-text">차액</span>
                    <span class="value-text arap-balance"><?= $balanceTotal < 0 ? '-' : '' ?>$<?= arapFormatAmount(abs($balanceTotal)) ?></span>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <form method="get" class="form-horizontal">
                    <input type="hidden" name="division" value="<?= arapH($division) ?>">
                    <input type="hidden" name="pdx" value="<?= arapH($pdx) ?>">
                    <input type="hidden" name="sub" value="<?= arapH($sub) ?>">
                    <div class="panel panel-default">
                        <div class="panel-heading"><strong>조회조건</strong></div>
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-sm-2">
                                    <label>월</label>
                                    <input type="month" name="month" class="form-control" value="<?= arapH($month) ?>">
                                </div>
                                <div class="col-sm-2">
                                    <label>구분</label>
                                    <select name="tx_type" class="form-control">
                                        <option value="">전체</option>
                                        <option value="IN"<?= $txType === 'IN' ? ' selected' : '' ?>>수입</option>
                                        <option value="OUT"<?= $txType === 'OUT' ? ' selected' : '' ?>>지출</option>
                                    </select>
                                </div>
                                <div class="col-sm-2">
                                    <label>대분류</label>
                                    <select name="cat_id" class="form-control">
                                        <option value="0">전체</option>
                                        <?php foreach ($categories as $category) { ?>
                                            <option value="<?= (int)$category['cat_id'] ?>"<?= $filterCatId === (int)$category['cat_id'] ? ' selected' : '' ?>>
                                                <?= arapH($category['cat_name']) ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="col-sm-2">
                                    <label>소분류</label>
                                    <select name="sub_id" class="form-control">
                                        <option value="0">전체</option>
                                        <?php foreach ($subcategories as $subcategory) { ?>
                                            <option value="<?= (int)$subcategory['sub_id'] ?>"<?= $filterSubId === (int)$subcategory['sub_id'] ? ' selected' : '' ?>>
                                                <?= arapH($subcategory['sub_name']) ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="col-sm-2">
                                    <label>거래수단</label>
                                    <select name="method_id" class="form-control">
                                        <option value="0">전체</option>
                                        <?php foreach ($methods as $method) { ?>
                                            <option value="<?= (int)$method['method_id'] ?>"<?= $filterMethodId === (int)$method['method_id'] ? ' selected' : '' ?>>
                                                <?= arapH($method['method_name']) ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="col-sm-2">
                                    <label>거래상태</label>
                                    <select name="status" class="form-control">
                                        <option value="">전체</option>
                                        <?php foreach ($statusOptions as $sk => $sv) { ?>
                                            <option value="<?= arapH($sk) ?>"<?= $filterStatus === $sk ? ' selected' : '' ?>><?= arapH($sv) ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="col-sm-2">
                                    <label>키워드</label>
                                    <input type="text" name="keyword" class="form-control" value="<?= arapH($keyword) ?>" placeholder="사용내역/메모">
                                </div>
                            </div>
                            <div class="row" style="margin-top:15px;">
                                <div class="col-sm-12 text-right">
                                    <button type="submit" class="btn btn-primary">조회</button>
                                    <a href="<?= arapH($exportUrl) ?>" class="btn btn-default">엑셀 다운로드</a>
                                    <a href="<?= arapH($targetCardsUrl) ?>" class="btn btn-info">대상/가이드정산</a>
                                    <a href="<?= arapH($chartUrl) ?>" class="btn btn-warning"><i class="glyphicon glyphicon-stats"></i> 통계차트</a>
                                    <button type="button" class="btn btn-success js-arap-create">+ 추가</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <div class="panel panel-default">
                    <div class="panel-heading"><strong>거래내역</strong></div>
                    <div class="panel-body">
                        <table id="arapTable" class="table table-bordered table-striped table-condensed">
                            <thead>
                                <tr>
                                    <th>일자</th>
                                    <th>구분</th>
                                    <th>대분류</th>
                                    <th>소분류</th>
                                    <th>사용내역</th>
                                    <th>거래수단</th>
                                    <th class="text-right">금액</th>
                                    <th>메모</th>
                                    <th>거래상태</th>
                                    <th>원본</th>
                                    <th>관리</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rows as $row) {
                                    $rowAmount = (float)$row['amount'];
                                    $rowTxType = isset($row['tx_type']) ? trim((string)$row['tx_type']) : '';
                                    $isPending = ($rowTxType !== 'IN' && $rowTxType !== 'OUT');
                                    if ($rowTxType === 'OUT') {
                                        $rowSign = '-';
                                        $rowTypeLabel = '지출';
                                        $rowClass = 'arap-row-expense';
                                    } elseif ($rowTxType === 'IN') {
                                        $rowSign = '+';
                                        $rowTypeLabel = '수입';
                                        $rowClass = 'arap-row-income';
                                    } else {
                                        $rowSign = '';
                                        $rowTypeLabel = '<span class="label label-warning">PENDING</span>';
                                        $rowClass = 'arap-row-pending';
                                    }
                                    $rowStatus = isset($row['status']) ? $row['status'] : 'PENDING';
                                    $rowStatusLabel = isset($statusOptions[$rowStatus]) ? $statusOptions[$rowStatus] : $rowStatus;
                                    $cellEditableClass = ' arap-cell-editable';
                                ?>
                                    <tr class="<?= $rowClass ?>">
                                        <td class="<?= trim($cellEditableClass) ?>" data-tx-id="<?= (int)$row['tx_id'] ?>" data-field="tx_date" data-value="<?= arapH($row['tx_date']) ?>"><?= arapH($row['tx_date']) ?></td>
                                        <td class="<?= trim($cellEditableClass) ?>" data-tx-id="<?= (int)$row['tx_id'] ?>" data-field="tx_type" data-value="<?= arapH($row['tx_type']) ?>"><?= $rowTypeLabel ?></td>
                                        <td class="<?= trim($cellEditableClass) ?>" data-tx-id="<?= (int)$row['tx_id'] ?>" data-field="cat_id" data-value="<?= (int)$row['cat_id'] ?>"><?= arapH($row['cat_name']) ?></td>
                                        <td class="<?= trim($cellEditableClass) ?>" data-tx-id="<?= (int)$row['tx_id'] ?>" data-field="sub_id" data-value="<?= (int)$row['sub_id'] ?>"><?= arapH($row['sub_name']) ?></td>
                                        <td class="<?= trim($cellEditableClass) ?>" data-tx-id="<?= (int)$row['tx_id'] ?>" data-field="description" data-value="<?= arapH($row['description']) ?>"><?= arapH($row['description']) ?></td>
                                        <td class="<?= trim($cellEditableClass) ?>" data-tx-id="<?= (int)$row['tx_id'] ?>" data-field="method_id" data-value="<?= (int)$row['method_id'] ?>"><?= arapH($row['method_name']) ?></td>
                                        <td class="text-right<?= $cellEditableClass ?>" data-tx-id="<?= (int)$row['tx_id'] ?>" data-field="amount" data-value="<?= arapH($row['amount']) ?>"><?= $rowSign ?>$<?= arapFormatAmount(abs($rowAmount)) ?></td>
                                        <td class="<?= trim($cellEditableClass) ?>" data-tx-id="<?= (int)$row['tx_id'] ?>" data-field="memo" data-value="<?= arapH($row['memo']) ?>"><?= arapH($row['memo']) ?></td>
                                        <td class="<?= trim($cellEditableClass) ?> arap-status-<?= arapH(strtolower($rowStatus)) ?>" data-tx-id="<?= (int)$row['tx_id'] ?>" data-field="status" data-value="<?= arapH($rowStatus) ?>"><?= arapH($rowStatusLabel) ?></td>
                                        <td><?= arapH($row['src_ref']) ?></td>
                                        <td class="arap-table-actions">
                                            <button
                                                type="button"
                                                class="btn btn-xs btn-primary js-arap-edit"
                                                data-tx-id="<?= (int)$row['tx_id'] ?>"
                                                data-tx-date="<?= arapH($row['tx_date']) ?>"
                                                data-tx-type="<?= arapH($row['tx_type']) ?>"
                                                data-cat-id="<?= (int)$row['cat_id'] ?>"
                                                data-sub-id="<?= (int)$row['sub_id'] ?>"
                                                data-description="<?= arapH($row['description']) ?>"
                                                data-method-id="<?= (int)$row['method_id'] ?>"
                                                data-bank-id="<?= (int)$row['bank_id'] ?>"
                                                data-check-no="<?= arapH($row['check_no']) ?>"
                                                data-amount="<?= arapH($row['amount']) ?>"
                                                data-memo="<?= arapH($row['memo']) ?>"
                                                data-status="<?= arapH($rowStatus) ?>">
                                                수정
                                            </button>
                                            <form method="post" style="display:inline;" onsubmit="return confirm('이 거래를 삭제하시겠습니까?');">
                                                <input type="hidden" name="mode" value="delete">
                                                <input type="hidden" name="tx_id" value="<?= (int)$row['tx_id'] ?>">
                                                <input type="hidden" name="division" value="<?= arapH($division) ?>">
                                                <input type="hidden" name="pdx" value="<?= arapH($pdx) ?>">
                                                <input type="hidden" name="sub" value="<?= arapH($sub) ?>">
                                                <input type="hidden" name="month" value="<?= arapH($month) ?>">
                                                <input type="hidden" name="filter_tx_type" value="<?= arapH($txType) ?>">
                                                <input type="hidden" name="filter_cat_id" value="<?= (int)$filterCatId ?>">
                                                <input type="hidden" name="filter_sub_id" value="<?= (int)$filterSubId ?>">
                                                <input type="hidden" name="filter_method_id" value="<?= (int)$filterMethodId ?>">
                                                <input type="hidden" name="filter_status" value="<?= arapH($filterStatus) ?>">
                                                <input type="hidden" name="keyword" value="<?= arapH($keyword) ?>">
                                                <button
                                                    type="submit"
                                                    class="btn btn-xs btn-danger">삭제</button>
                                            </form>
                                        </td>
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

<div class="modal fade" id="arapModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" id="arapForm">
                <input type="hidden" name="mode" value="save">
                <input type="hidden" name="division" value="<?= arapH($division) ?>">
                <input type="hidden" name="pdx" value="<?= arapH($pdx) ?>">
                <input type="hidden" name="sub" value="<?= arapH($sub) ?>">
                <input type="hidden" name="tx_id" id="tx_id" value="0">
                <input type="hidden" name="month" value="<?= arapH($month) ?>">
                <input type="hidden" name="filter_tx_type" value="<?= arapH($txType) ?>">
                <input type="hidden" name="filter_cat_id" value="<?= (int)$filterCatId ?>">
                <input type="hidden" name="filter_sub_id" value="<?= (int)$filterSubId ?>">
                <input type="hidden" name="filter_method_id" value="<?= (int)$filterMethodId ?>">
                <input type="hidden" name="filter_status" value="<?= arapH($filterStatus) ?>">
                <input type="hidden" name="keyword" value="<?= arapH($keyword) ?>">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    <h4 class="modal-title" id="arapModalTitle">거래내역 등록</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-sm-6">
                            <label>거래일</label>
                            <input type="date" name="tx_date" id="tx_date" class="form-control" value="<?= arapH($startDate) ?>" required>
                        </div>
                        <div class="col-sm-6">
                            <label>구분</label>
                            <select name="tx_type" id="tx_type" class="form-control" required>
                                <option value="OUT">지출</option>
                                <option value="IN">수입</option>
                            </select>
                        </div>
                    </div>
                    <div class="row" style="margin-top:15px;">
                        <div class="col-sm-6">
                            <label>대분류</label>
                            <select name="cat_id" id="cat_id" class="form-control" required>
                                <option value="">선택</option>
                                <?php foreach ($categories as $category) { ?>
                                    <option value="<?= (int)$category['cat_id'] ?>" data-type="<?= arapH($category['cat_type']) ?>" data-use-yn="<?= arapH($category['use_yn']) ?>">
                                        <?= arapH($category['cat_name']) ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label>소분류</label>
                            <select name="sub_id" id="sub_id" class="form-control">
                                <option value="">선택</option>
                            </select>
                        </div>
                    </div>
                    <div class="row" style="margin-top:15px;">
                        <div class="col-sm-12">
                            <label>사용내역</label>
                            <input type="text" name="description" id="description" class="form-control" maxlength="255">
                        </div>
                    </div>
                    <div class="row" style="margin-top:15px;">
                        <div class="col-sm-6">
                            <label>거래수단</label>
                            <select name="method_id" id="method_id" class="form-control">
                                <option value="">선택</option>
                                <?php foreach ($methods as $method) { ?>
                                    <option value="<?= (int)$method['method_id'] ?>" data-type="<?= arapH($method['method_type']) ?>" data-use-yn="<?= arapH($method['use_yn']) ?>" data-name="<?= arapH($method['method_name']) ?>">
                                        <?= arapH($method['method_name']) ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label>금액</label>
                            <input type="number" step="0.01" min="0" name="amount" id="amount" class="form-control" required>
                        </div>
                    </div>
                    <div class="row hidden" id="checkInfoRow" style="margin-top:15px;">
                        <div class="col-sm-6">
                            <label>은행</label>
                            <select name="bank_id" id="bank_id" class="form-control">
                                <option value="">선택</option>
                                <?php foreach ($banks as $bank) { ?>
                                    <?php if ($bank['use_yn'] !== 'Y') continue; ?>
                                    <option value="<?= (int)$bank['bank_id'] ?>"><?= arapH($bank['bank_name']) ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label>체크번호</label>
                            <input type="text" name="check_no" id="check_no" class="form-control" maxlength="30">
                        </div>
                    </div>
                    <div class="row" style="margin-top:15px;">
                        <div class="col-sm-12">
                            <label>거래상태</label>
                            <select name="status" id="status" class="form-control">
                                <?php foreach ($statusOptions as $sk => $sv) { ?>
                                    <option value="<?= arapH($sk) ?>"><?= arapH($sv) ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="row" style="margin-top:15px;">
                        <div class="col-sm-12">
                            <label>메모</label>
                            <textarea name="memo" id="memo" rows="3" class="form-control"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">닫기</button>
                    <button type="submit" class="btn btn-primary">저장</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include "include/side_m.php"; ?>

<script>
var arapSubcategories = <?= json_encode($allSubcategories, JSON_UNESCAPED_UNICODE) ?>;
var arapCategories = <?= json_encode(array_map(function($c){ return array('cat_id'=>(int)$c['cat_id'],'cat_type'=>$c['cat_type'],'cat_name'=>$c['cat_name'],'use_yn'=>$c['use_yn']); }, $categories), JSON_UNESCAPED_UNICODE) ?>;
var arapMethods = <?= json_encode(array_map(function($m){ return array('method_id'=>(int)$m['method_id'],'method_type'=>$m['method_type'],'method_name'=>$m['method_name'],'use_yn'=>$m['use_yn']); }, $methods), JSON_UNESCAPED_UNICODE) ?>;
var arapStatusOptions = <?= json_encode($statusOptions, JSON_UNESCAPED_UNICODE) ?>;
var arapInlineUrl = 'arap_list.php?<?= arapH(arapBuildQuery(array('division'=>$division,'pdx'=>$pdx,'sub'=>$sub))) ?>';

function arapResetModal() {
    $('#arapModalTitle').text('거래내역 등록');
    $('#tx_id').val('0');
    $('#tx_date').val('<?= arapH($startDate) ?>');
    $('#tx_type').val('OUT');
    $('#cat_id').val('');
    $('#description').val('');
    $('#method_id').val('');
    $('#amount').val('');
    $('#bank_id').val('');
    $('#check_no').val('');
    $('#memo').val('');
    $('#status').val('PENDING');
    arapSyncCategoryOptions();
    arapSyncMethodOptions();
    arapRenderSubcategories('', '');
    arapToggleCheckInfo();
}

function arapToggleCheckInfo() {
    var methodName = $('#method_id option:selected').data('name') || '';
    if (methodName === '체크') {
        $('#checkInfoRow').removeClass('hidden');
    } else {
        $('#checkInfoRow').addClass('hidden');
        $('#bank_id').val('');
        $('#check_no').val('');
    }
}

function arapSyncCategoryOptions() {
    var txType = $('#tx_type').val();
    $('#cat_id option').each(function() {
        var type = $(this).data('type');
        var useYn = $(this).data('use-yn');
        if (!type) {
            $(this).show();
            return;
        }
        if (type === txType && useYn === 'Y') {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
    if ($('#cat_id option:selected').is(':hidden')) {
        $('#cat_id').val('');
    }
}

function arapSyncMethodOptions() {
    var txType = $('#tx_type').val();
    $('#method_id option').each(function() {
        var type = $(this).data('type');
        var useYn = $(this).data('use-yn');
        if (!type) {
            $(this).show();
            return;
        }
        if (type === txType && useYn === 'Y') {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
    if ($('#method_id option:selected').is(':hidden')) {
        $('#method_id').val('');
    }
}

function arapRenderSubcategories(catId, selectedSubId) {
    var options = ["<option value=''>선택</option>"];
    var items = arapSubcategories[catId] || [];
    $.each(items, function(index, item) {
        if (item.use_yn !== 'Y') {
            return;
        }
        var selected = String(item.sub_id) === String(selectedSubId) ? " selected" : "";
        options.push("<option value='" + item.sub_id + "'" + selected + ">" + item.sub_name + "</option>");
    });
    $('#sub_id').html(options.join(''));
}

$(function() {
    $('#arapTable').DataTable({
        order: [[0, 'desc']],
        pageLength: 25
    });

    $('.js-arap-create').on('click', function() {
        arapResetModal();
        $('#arapModal').modal('show');
    });

    $('#tx_type').on('change', function() {
        arapSyncCategoryOptions();
        arapSyncMethodOptions();
        arapRenderSubcategories($('#cat_id').val(), '');
    });

    $('#cat_id').on('change', function() {
        arapRenderSubcategories($(this).val(), '');
    });

    $('#method_id').on('change', function() {
        arapToggleCheckInfo();
    });

    $('.js-arap-edit').on('click', function() {
        if ($(this).prop('disabled')) {
            return;
        }
        arapResetModal();
        $('#arapModalTitle').text('거래내역 수정');
        $('#tx_id').val($(this).data('tx-id'));
        $('#tx_date').val($(this).data('tx-date'));
        $('#tx_type').val($(this).data('tx-type'));
        arapSyncCategoryOptions();
        arapSyncMethodOptions();
        $('#cat_id').val(String($(this).data('cat-id')));
        arapRenderSubcategories(String($(this).data('cat-id')), String($(this).data('sub-id')));
        $('#description').val($(this).data('description'));
        $('#method_id').val(String($(this).data('method-id')));
        $('#amount').val($(this).data('amount'));
        $('#memo').val($(this).data('memo'));
        $('#status').val(String($(this).data('status') || 'PENDING'));
        arapToggleCheckInfo();
        $('#bank_id').val(String($(this).data('bank-id') || ''));
        $('#check_no').val(String($(this).data('check-no') || ''));
        $('#arapModal').modal('show');
    });

    function arapBuildEditor($td) {
        var field = $td.data('field');
        var value = $td.data('value');
        var txId = $td.data('tx-id');
        var $row = $td.closest('tr');
        var rowTxType = $row.find('td[data-field="tx_type"]').data('value') || 'OUT';
        var rowCatId = parseInt($row.find('td[data-field="cat_id"]').data('value') || '0', 10);
        var $editor;
        switch (field) {
            case 'tx_date':
                $editor = $('<input type="date" class="arap-cell-input">').val(String(value || ''));
                break;
            case 'tx_type':
                $editor = $('<select class="arap-cell-input"><option value="IN">수입</option><option value="OUT">지출</option></select>').val(String(value || 'OUT'));
                break;
            case 'cat_id':
                $editor = $('<select class="arap-cell-input"><option value="">선택</option></select>');
                $.each(arapCategories, function(_, cat) {
                    if (cat.use_yn === 'Y' && cat.cat_type === rowTxType) {
                        $editor.append('<option value="' + cat.cat_id + '">' + cat.cat_name + '</option>');
                    }
                });
                $editor.val(String(value || ''));
                break;
            case 'sub_id':
                $editor = $('<select class="arap-cell-input"><option value="">선택</option></select>');
                var subs = arapSubcategories[String(rowCatId)] || [];
                $.each(subs, function(_, s) {
                    if (s.use_yn === 'Y') {
                        $editor.append('<option value="' + s.sub_id + '">' + s.sub_name + '</option>');
                    }
                });
                $editor.val(String(value || ''));
                break;
            case 'method_id':
                $editor = $('<select class="arap-cell-input"><option value="">선택</option></select>');
                $.each(arapMethods, function(_, mm) {
                    if (mm.use_yn === 'Y' && mm.method_type === rowTxType) {
                        $editor.append('<option value="' + mm.method_id + '">' + mm.method_name + '</option>');
                    }
                });
                $editor.val(String(value || ''));
                break;
            case 'status':
                $editor = $('<select class="arap-cell-input"></select>');
                $.each(arapStatusOptions, function(k, label) {
                    $editor.append('<option value="' + k + '">' + label + '</option>');
                });
                $editor.val(String(value || 'PENDING'));
                break;
            case 'amount':
                $editor = $('<input type="number" step="0.01" class="arap-cell-input">').val(String(value || ''));
                break;
            case 'description':
            case 'memo':
            default:
                $editor = $('<input type="text" class="arap-cell-input">').val(String(value == null ? '' : value));
                break;
        }
        return $editor;
    }

    function arapFlash($td, klass) {
        $td.addClass(klass);
        setTimeout(function() { $td.removeClass(klass); }, 1500);
    }

    function arapSaveCell($td, newValue, originalDisplay) {
        var field = $td.data('field');
        var txId = $td.data('tx-id');
        var oldValue = String($td.data('value') == null ? '' : $td.data('value'));
        if (String(newValue) === oldValue) {
            $td.removeClass('arap-cell-editing').html(originalDisplay);
            return;
        }
        $td.addClass('arap-cell-saving');
        $.post('arap_list.php', {
            mode: 'inline_save',
            tx_id: txId,
            field: field,
            value: newValue
        }, null, 'json').done(function(resp) {
            $td.removeClass('arap-cell-saving arap-cell-editing');
            if (resp && resp.ok) {
                $td.data('value', resp.value);
                $td.attr('data-value', resp.value);
                $td.html(resp.display === '' ? '&nbsp;' : resp.display);
                arapFlash($td, 'arap-cell-success');
            } else {
                $td.html(originalDisplay);
                arapFlash($td, 'arap-cell-error');
                alert((resp && resp.message) ? resp.message : '저장 중 오류가 발생했습니다.');
            }
        }).fail(function() {
            $td.removeClass('arap-cell-saving arap-cell-editing');
            $td.html(originalDisplay);
            arapFlash($td, 'arap-cell-error');
            alert('서버 통신 오류가 발생했습니다.');
        });
    }

    $(document).on('click', '.arap-cell-editable', function(e) {
        var $td = $(this);
        if ($td.hasClass('arap-cell-editing')) return;
        if ($td.find('.arap-cell-input').length) return;
        var originalDisplay = $td.html();
        var $editor = arapBuildEditor($td);
        $td.addClass('arap-cell-editing').empty().append($editor);
        $editor.trigger('focus');
        if ($editor.is('select')) {
            $editor.on('change.arapCell', function() {
                arapSaveCell($td, $editor.val(), originalDisplay);
            });
            $editor.on('blur.arapCell', function() {
                if (!$td.hasClass('arap-cell-saving')) {
                    $td.removeClass('arap-cell-editing').html(originalDisplay);
                }
            });
        } else {
            $editor.on('keydown.arapCell', function(ev) {
                if (ev.key === 'Enter') {
                    ev.preventDefault();
                    $editor.trigger('blur');
                } else if (ev.key === 'Escape') {
                    $editor.off('blur.arapCell');
                    $td.removeClass('arap-cell-editing').html(originalDisplay);
                }
            });
            $editor.on('blur.arapCell', function() {
                arapSaveCell($td, $editor.val(), originalDisplay);
            });
        }
    });
});
</script>
