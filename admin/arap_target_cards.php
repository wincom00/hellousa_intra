<?php
if (isset($_POST['mode']) && $_POST['mode'] === 'target_status_update') {
    ob_start();
    require_once __DIR__ . "/include/inc_base.php";
    require_once __DIR__ . "/include/arap_common.php";
    ob_end_clean();

    if (empty($_COOKIE['MEMLOGIN_ADMIN_HELLO'])) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('ok' => false, 'message' => '로그인이 필요합니다.'));
        exit;
    }

    include __DIR__ . "/inc_arap_target_status_update.php";
    exit;
}

include "include/header.php";
//include "include/inc_base.php";
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
    Misc::jvAlert("권한이 없는 메뉴입니다. 확인 후 사용해 주세요.","");
    echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
    exit;
}

if ($mode == "guide_action") {
    include __DIR__ . "/inc_arap_guide_action.php";
}
if ($mode == "target_delete") {
    include __DIR__ . "/inc_arap_target_delete.php";
}

$month = arapGetMonthValue(isset($_GET['month']) ? $_GET['month'] : '');
$keyword = isset($_GET['keyword']) ? trim((string)$_GET['keyword']) : '';
list($startDate, $endDate) = arapGetMonthRange($month);

function arapTargetWhereSql($txType, $keyword)
{
    global $dbConn, $startDate, $endDate;

    $where = array();
    $where[] = "t.tx_date BETWEEN '" . $dbConn->real_escape_string($startDate) . "' AND '" . $dbConn->real_escape_string($endDate) . "'";

    if ($txType === 'IN') {
        $where[] = "t.tx_type = 'IN'";
    } elseif ($txType === 'OUT') {
        $where[] = "t.tx_type = 'OUT'";
    }

    if ($keyword !== '') {
        $safeKeyword = $dbConn->real_escape_string($keyword);
        $where[] = "(t.description LIKE '%{$safeKeyword}%'
                    OR t.memo LIKE '%{$safeKeyword}%'
                    OR c.cat_name LIKE '%{$safeKeyword}%'
                    OR s.sub_name LIKE '%{$safeKeyword}%')";
    }

    return implode(" AND ", $where);
}

function arapFetchTargetCards($txType, $keyword, $limit = 24)
{
    global $dbConn;

    $whereSql = arapTargetWhereSql($txType, $keyword);
    $limit = (int)$limit;
    $sql = "SELECT
                COALESCE(NULLIF(TRIM(t.description), ''), NULLIF(TRIM(s.sub_name), ''), c.cat_name) AS target_name,
                TRIM(COALESCE(t.description, '')) AS raw_description,
                c.cat_id,
                c.cat_name,
                COALESCE(s.sub_id, 0) AS sub_id,
                COALESCE(s.sub_name, '') AS sub_name,
                COUNT(*) AS tx_count,
                SUM(t.amount) AS amount_total,
                MIN(t.tx_date) AS first_date,
                MAX(t.tx_date) AS last_date,
                GROUP_CONCAT(DISTINCT m.method_name ORDER BY m.method_name SEPARATOR ', ') AS method_names,
                GROUP_CONCAT(DISTINCT COALESCE(t.status, 'PENDING') ORDER BY t.status) AS status_list
            FROM arap_transaction t
            INNER JOIN arap_category c ON c.cat_id = t.cat_id
            LEFT JOIN arap_subcategory s ON s.sub_id = t.sub_id
            LEFT JOIN arap_method m ON m.method_id = t.method_id
            WHERE {$whereSql}
            GROUP BY raw_description, c.cat_id, c.cat_name, s.sub_id, s.sub_name
            ORDER BY amount_total DESC, tx_count DESC, target_name ASC
            LIMIT {$limit}";

    $rows = array();
    $result = $dbConn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }

    return $rows;
}

function arapFetchGuideSettlementRows($keyword, $limit = 60)
{
    global $dbConn, $startDate, $endDate;

    $where = array();
    $where[] = "gsm.reg_status = 'COMPLETE'";
    $where[] = "gsm.finance_st = 'V'";
    $where[] = "gsm.ceo_st = 'V'";
    $where[] = "gsm.finance_date IS NOT NULL";
    $where[] = "gsm.finance_date >= '1000-01-01 00:00:00'";
    $where[] = "(gsm.check_date IS NULL OR gsm.check_date < '1000-01-01')";
    $where[] = "gsm.stDate BETWEEN '" . $dbConn->real_escape_string($startDate) . "' AND '" . $dbConn->real_escape_string($endDate) . "'";
    $where[] = "NOT EXISTS (SELECT 1 FROM arap_transaction at WHERE at.src_ref = gsm.settle_code)";
    $where[] = "((COALESCE(tg.pre_amt, 0) + COALESCE(gs.shopping_cprofit_total, 0) + COALESCE(go.option_cprofit_total, 0) + COALESCE(gi.deposit_total, 0))
                - (COALESCE(gm.meal_total, 0) + COALESCE(ga.admission_total, 0) + COALESCE(ge.etc_total, 0) + COALESCE(gs.shopping_homecom_total, 0) + COALESCE(gs.shopping_gprofit_total, 0))) <> 0";
    if ($keyword !== '') {
        $safeKeyword = $dbConn->real_escape_string($keyword);
        $where[] = "(gsm.settle_code LIKE '%{$safeKeyword}%'
                    OR gsm.grand_eCode LIKE '%{$safeKeyword}%'
                    OR gsm.sub_eCode LIKE '%{$safeKeyword}%'
                    OR tg.p_name LIKE '%{$safeKeyword}%'
                    OR tg.guide_id LIKE '%{$safeKeyword}%'
                    OR ml.kor_name LIKE '%{$safeKeyword}%'
                    OR gsm.g_memo LIKE '%{$safeKeyword}%')";
    }
    $whereSql = implode(" AND ", $where);
    $limit = (int)$limit;

    $sql = "SELECT
                gsm.seq_no,
                gsm.settle_code,
                gsm.grand_eCode,
                gsm.sub_eCode,
                gsm.stDate,
                gsm.edDate,
                gsm.finance_date,
                gsm.report_date,
                gsm.check_date,
                COALESCE(gsm.guide_etcamt, 0) AS guide_etcamt,
                COALESCE(gsm.g_memo, '') AS g_memo,
                COALESCE(tg.seq_no, 0) AS guide_seq_no,
                COALESCE(tg.p_name, '') AS p_name,
                COALESCE(tg.guide_id, '') AS guide_id,
                COALESCE(ml.kor_name, tg.guide_id, '') AS guide_name,
                COALESCE(tg.pre_amt, 0) AS pre_amt,
                COALESCE(gi.deposit_total, 0) AS deposit_total,
                COALESCE(gm.meal_total, 0) AS meal_total,
                COALESCE(ga.admission_total, 0) AS admission_total,
                COALESCE(ge.etc_total, 0) AS etc_total,
                COALESCE(gs.shopping_homecom_total, 0) AS shopping_total,
                COALESCE(gs.shopping_cprofit_total, 0) AS shopping_cprofit_total,
                COALESCE(gs.shopping_gprofit_total, 0) AS shopping_gprofit_total,
                COALESCE(go.option_cprofit_total, 0) AS option_cprofit_total
            FROM guide_setmaster gsm
            LEFT JOIN (
                SELECT grand_eCode, sub_eCode, MAX(seq_no) AS guide_seq_no
                FROM tour_guide
                GROUP BY grand_eCode, sub_eCode
            ) tg_pick
                ON tg_pick.grand_eCode = gsm.grand_eCode
               AND tg_pick.sub_eCode = gsm.sub_eCode
            LEFT JOIN tour_guide tg
                ON tg.seq_no = tg_pick.guide_seq_no
            LEFT JOIN member_list ml ON ml.userid = tg.guide_id
            LEFT JOIN (
                SELECT settle_code, SUM(input_amt * input_cnt) AS deposit_total
                FROM guide_inputamt
                GROUP BY settle_code
            ) gi ON gi.settle_code = gsm.settle_code
            LEFT JOIN (
                SELECT settle_code, SUM(meal_pricetotal) AS meal_total
                FROM guide_meal
                GROUP BY settle_code
            ) gm ON gm.settle_code = gsm.settle_code
            LEFT JOIN (
                SELECT settle_code, SUM(e_pricetot) AS admission_total
                FROM guide_admission
                GROUP BY settle_code
            ) ga ON ga.settle_code = gsm.settle_code
            LEFT JOIN (
                SELECT settle_code, SUM(etc_amt) AS etc_total
                FROM guide_etcamt
                GROUP BY settle_code
            ) ge ON ge.settle_code = gsm.settle_code
            LEFT JOIN (
                SELECT settle_code,
                       SUM(home_comamt) AS shopping_homecom_total,
                       SUM(c_profit) AS shopping_cprofit_total,
                       SUM(g_profit) AS shopping_gprofit_total
                FROM guide_shopping
                GROUP BY settle_code
            ) gs ON gs.settle_code = gsm.settle_code
            LEFT JOIN (
                SELECT settle_code, SUM(o_cprofit) AS option_cprofit_total
                FROM guide_option
                GROUP BY settle_code
            ) go ON go.settle_code = gsm.settle_code
            WHERE {$whereSql}
            ORDER BY gsm.finance_date DESC, gsm.seq_no DESC
            LIMIT {$limit}";
   // echo "SQL: {$sql}"; // Debug: Show the generated SQL query
    $rows = array();
    $result = $dbConn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }

    return $rows;
}

function arapFetchTargetSummary($keyword)
{
    global $dbConn, $startDate, $endDate;

    $incomeWhere = arapTargetWhereSql('IN', $keyword);
    $expenseWhere = arapTargetWhereSql('OUT', $keyword);
    $arapSql = "SELECT
                (SELECT COALESCE(SUM(t.amount), 0)
                 FROM arap_transaction t
                 INNER JOIN arap_category c ON c.cat_id = t.cat_id
                 LEFT JOIN arap_subcategory s ON s.sub_id = t.sub_id
                 WHERE {$incomeWhere}) AS income_total,
                (SELECT COALESCE(SUM(t.amount), 0)
                 FROM arap_transaction t
                 INNER JOIN arap_category c ON c.cat_id = t.cat_id
                 LEFT JOIN arap_subcategory s ON s.sub_id = t.sub_id
                 WHERE {$expenseWhere}) AS expense_total,
                (SELECT COUNT(*)
                 FROM arap_transaction t
                 INNER JOIN arap_category c ON c.cat_id = t.cat_id
                 LEFT JOIN arap_subcategory s ON s.sub_id = t.sub_id
                 WHERE {$incomeWhere}) AS income_count,
                (SELECT COUNT(*)
                 FROM arap_transaction t
                 INNER JOIN arap_category c ON c.cat_id = t.cat_id
                 LEFT JOIN arap_subcategory s ON s.sub_id = t.sub_id
                 WHERE {$expenseWhere}) AS expense_count";

    $result = $dbConn->query($arapSql);
    if (!$result) {
        $summary = array(
            'income_total' => 0,
            'expense_total' => 0,
            'income_count' => 0,
            'expense_count' => 0
        );
    } else {
        $summary = $result->fetch_assoc();
    }

    $guideWhere = array();
    $guideWhere[] = "gsm.reg_status = 'COMPLETE'";
    $guideWhere[] = "gsm.finance_st = 'V'";
    $guideWhere[] = "gsm.ceo_st = 'V'";
    $guideWhere[] = "gsm.finance_date IS NOT NULL";
    $guideWhere[] = "gsm.finance_date >= '1000-01-01 00:00:00'";
    $guideWhere[] = "(gsm.check_date IS NULL OR gsm.check_date < '1000-01-01')";
    $guideWhere[] = "gsm.stDate BETWEEN '" . $dbConn->real_escape_string($startDate) . "' AND '" . $dbConn->real_escape_string($endDate) . "'";
    $guideWhere[] = "NOT EXISTS (SELECT 1 FROM arap_transaction at WHERE at.src_ref = gsm.settle_code)";
    $guideWhere[] = "((COALESCE(tg.pre_amt, 0) + COALESCE(gs.shopping_cprofit_total, 0) + COALESCE(go.option_cprofit_total, 0) + COALESCE(gi.deposit_total, 0))
                - (COALESCE(gm.meal_total, 0) + COALESCE(ga.admission_total, 0) + COALESCE(ge.etc_total, 0) + COALESCE(gs.shopping_homecom_total, 0) + COALESCE(gs.shopping_gprofit_total, 0))) <> 0";
    if ($keyword !== '') {
        $safeKeyword = $dbConn->real_escape_string($keyword);
        $guideWhere[] = "(gsm.settle_code LIKE '%{$safeKeyword}%'
                    OR gsm.grand_eCode LIKE '%{$safeKeyword}%'
                    OR gsm.sub_eCode LIKE '%{$safeKeyword}%'
                    OR tg.p_name LIKE '%{$safeKeyword}%'
                    OR tg.guide_id LIKE '%{$safeKeyword}%'
                    OR ml.kor_name LIKE '%{$safeKeyword}%'
                    OR gsm.g_memo LIKE '%{$safeKeyword}%')";
    }
    $guideWhereSql = implode(" AND ", $guideWhere);
    $guideSql = "SELECT
                    COUNT(*) AS guide_count,
                    COALESCE(SUM(
                        (
                            COALESCE(tg.pre_amt, 0)
                            + COALESCE(gs.shopping_cprofit_total, 0)
                            + COALESCE(go.option_cprofit_total, 0)
                            + COALESCE(gi.deposit_total, 0)
                        )
                        - (
                            COALESCE(gm.meal_total, 0)
                            + COALESCE(ga.admission_total, 0)
                            + COALESCE(ge.etc_total, 0)
                            + COALESCE(gs.shopping_homecom_total, 0)
                            + COALESCE(gs.shopping_gprofit_total, 0)
                        )
                    ), 0) AS guide_total
                 FROM guide_setmaster gsm
                 LEFT JOIN (
                    SELECT grand_eCode, sub_eCode, MAX(seq_no) AS guide_seq_no
                    FROM tour_guide
                    GROUP BY grand_eCode, sub_eCode
                 ) tg_pick
                    ON tg_pick.grand_eCode = gsm.grand_eCode
                   AND tg_pick.sub_eCode = gsm.sub_eCode
                 LEFT JOIN tour_guide tg
                    ON tg.seq_no = tg_pick.guide_seq_no
                 LEFT JOIN member_list ml ON ml.userid = tg.guide_id
                 LEFT JOIN (
                    SELECT settle_code, SUM(input_amt * input_cnt) AS deposit_total
                    FROM guide_inputamt
                    GROUP BY settle_code
                 ) gi ON gi.settle_code = gsm.settle_code
                 LEFT JOIN (
                    SELECT settle_code, SUM(meal_pricetotal) AS meal_total
                    FROM guide_meal
                    GROUP BY settle_code
                 ) gm ON gm.settle_code = gsm.settle_code
                 LEFT JOIN (
                    SELECT settle_code, SUM(e_pricetot) AS admission_total
                    FROM guide_admission
                    GROUP BY settle_code
                 ) ga ON ga.settle_code = gsm.settle_code
                 LEFT JOIN (
                    SELECT settle_code, SUM(etc_amt) AS etc_total
                    FROM guide_etcamt
                    GROUP BY settle_code
                 ) ge ON ge.settle_code = gsm.settle_code
                 LEFT JOIN (
                    SELECT settle_code,
                           SUM(home_comamt) AS shopping_homecom_total,
                           SUM(c_profit) AS shopping_cprofit_total,
                           SUM(g_profit) AS shopping_gprofit_total
                    FROM guide_shopping
                    GROUP BY settle_code
                 ) gs ON gs.settle_code = gsm.settle_code
                 LEFT JOIN (
                    SELECT settle_code, SUM(o_cprofit) AS option_cprofit_total
                    FROM guide_option
                    GROUP BY settle_code
                 ) go ON go.settle_code = gsm.settle_code
                 WHERE {$guideWhereSql}";
    $guideResult = $dbConn->query($guideSql);
    $guideSummary = $guideResult ? $guideResult->fetch_assoc() : array('guide_total' => 0, 'guide_count' => 0);

    $summary['guide_total'] = isset($guideSummary['guide_total']) ? $guideSummary['guide_total'] : 0;
    $summary['guide_count'] = isset($guideSummary['guide_count']) ? $guideSummary['guide_count'] : 0;

    return $summary;
}

$summary = arapFetchTargetSummary($keyword);
$incomeCards = arapFetchTargetCards('IN', $keyword, 24);
$expenseCards = arapFetchTargetCards('OUT', $keyword, 24);
$guideRows = arapFetchGuideSettlementRows($keyword, 60);
$outMethods = arapFetchMethods('OUT');
$guideDefaultMethodId = 0;
foreach ($outMethods as $om) {
    if ($om['use_yn'] !== 'Y') {
        continue;
    }
    if ($guideDefaultMethodId <= 0) {
        $guideDefaultMethodId = (int)$om['method_id'];
    }
    if ($om['method_name'] === '체크') {
        $guideDefaultMethodId = (int)$om['method_id'];
        break;
    }
}

$targetStatusOptions = array(
    'PENDING' => '대기',
    'COMPLETED' => '완료',
    'CANCELLED' => '취소',
    'HOLD' => '보류'
);

$listUrl = arapPageUrl('arap_list.php', array(
    'division' => $division,
    'pdx' => $pdx,
    'sub' => $sub,
    'month' => $month,
    'keyword' => $keyword
));
$targetUpdateUrl = arapPageUrl('arap_target_cards.php', array(
    'division' => $division,
    'pdx' => $pdx,
    'sub' => $sub,
    'month' => $month,
    'keyword' => $keyword
));
?>
<style>
.arap-target-page {
    color: #243447;
}
.arap-target-toolbar {
    background: #ffffff;
    border: 1px solid #d9e2ec;
    border-radius: 6px;
    padding: 14px;
    margin-bottom: 18px;
}
.arap-target-summary {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 20px;
}
.arap-total-card,
.arap-target-card,
.arap-guide-card {
    background: #ffffff;
    border: 1px solid #d9e2ec;
    border-radius: 6px;
    box-shadow: 0 2px 6px rgba(31, 45, 61, 0.06);
}
.arap-total-card {
    min-height: 116px;
    padding: 16px;
    position: relative;
    overflow: hidden;
}
.arap-total-card:before {
    content: "";
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 5px;
}
.arap-total-card.income:before {
    background: #258a56;
}
.arap-total-card.expense:before {
    background: #c65353;
}
.arap-total-card.guide:before {
    background: #2f6f9f;
}
.arap-total-label {
    display: block;
    color: #6b7c93;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0;
    margin-bottom: 8px;
}
.arap-total-value {
    display: block;
    font-size: 26px;
    font-weight: 800;
    line-height: 1.15;
    word-break: break-word;
}
.arap-total-sub {
    display: block;
    color: #829ab1;
    font-size: 12px;
    margin-top: 8px;
}
.arap-section-head {
    align-items: center;
    display: flex;
    justify-content: space-between;
    margin: 8px 0 12px;
}
.arap-section-head h3 {
    font-size: 17px;
    font-weight: 800;
    margin: 0;
}
.arap-section-head .badge {
    background: #eef2f7;
    color: #52606d;
}
.arap-card-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}
.arap-target-card {
    min-height: 142px;
    padding: 14px;
}
.arap-card-status-row {
    align-items: center;
    border-top: 1px dashed #e3e8ee;
    display: flex;
    gap: 8px;
    margin-top: 10px;
    padding-top: 10px;
}
.arap-card-status-label {
    color: #6b7c93;
    font-size: 12px;
    font-weight: 700;
}
.arap-card-status-current {
    color: #243447;
    font-size: 12px;
    font-weight: 700;
    min-width: 36px;
}
.arap-card-status-current.mixed {
    color: #b8860b;
}
.js-target-status {
    flex: 1;
    max-width: 110px;
    height: 28px;
    padding: 2px 6px;
}
.arap-target-card .card-top {
    align-items: flex-start;
    display: flex;
    justify-content: space-between;
    gap: 10px;
}
.arap-target-card h4,
.arap-guide-card h4 {
    font-size: 15px;
    font-weight: 800;
    line-height: 1.35;
    margin: 0;
    word-break: break-word;
}
.arap-type-pill {
    border-radius: 999px;
    display: inline-block;
    font-size: 11px;
    font-weight: 700;
    line-height: 1;
    padding: 6px 8px;
    white-space: nowrap;
}
.arap-type-pill.income {
    background: #e4f4ec;
    color: #1f7a4c;
}
.arap-type-pill.expense {
    background: #fae8e8;
    color: #ad3f3f;
}
.arap-type-pill.guide {
    background: #e5f0f8;
    color: #2f6f9f;
}
.arap-card-amount {
    font-size: 22px;
    font-weight: 800;
    margin-top: 12px;
}
.arap-card-meta {
    color: #6b7c93;
    font-size: 12px;
    line-height: 1.6;
    margin-top: 8px;
}
.arap-card-meta span {
    display: inline-block;
    margin-right: 10px;
}
.arap-guide-list {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
}
.arap-guide-card {
    min-height: 176px;
    padding: 14px;
}
.arap-guide-card .guide-date {
    color: #6b7c93;
    font-size: 12px;
    font-weight: 700;
    margin-bottom: 8px;
}
.arap-guide-card .guide-amount {
    color: #2f6f9f;
    font-size: 21px;
    font-weight: 800;
    margin: 10px 0 8px;
}
.arap-guide-card .guide-note {
    color: #52606d;
    font-size: 12px;
    line-height: 1.55;
}
.arap-guide-actions {
    align-items: center;
    border-top: 1px dashed #e3e8ee;
    display: flex;
    gap: 6px;
    justify-content: flex-end;
    margin-top: 10px;
    padding-top: 10px;
}
.arap-guide-actions .btn {
    margin-left: 4px;
}
.arap-target-actions {
    border-top: 1px dashed #e3e8ee;
    margin-top: 10px;
    padding-top: 10px;
    text-align: right;
}
.arap-empty {
    background: #f8fafc;
    border: 1px dashed #cbd5e1;
    border-radius: 6px;
    color: #6b7c93;
    padding: 18px;
    text-align: center;
}
@media (max-width: 991px) {
    .arap-target-summary,
    .arap-guide-list {
        grid-template-columns: 1fr;
    }
    .arap-card-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div id="contentwrapper">
    <div class="main_content arap-target-page">
        <div id="jCrumbs" class="breadCrumb module">
            <ul>
                <li><a href="/"><i class="glyphicon glyphicon-home"></i></a></li>
                <li><a href="#">AR/AP</a></li>
                <li>수입·지출 대상 / 가이드 정산</li>
            </ul>
        </div>

        <form method="get" class="arap-target-toolbar">
            <input type="hidden" name="division" value="<?= arapH($division) ?>">
            <input type="hidden" name="pdx" value="<?= arapH($pdx) ?>">
            <input type="hidden" name="sub" value="<?= arapH($sub) ?>">
            <div class="row">
                <div class="col-sm-3">
                    <label for="month">조회월</label>
                    <input type="month" name="month" id="month" class="form-control" value="<?= arapH($month) ?>">
                </div>
                <div class="col-sm-5">
                    <label for="keyword">검색어</label>
                    <input type="text" name="keyword" id="keyword" class="form-control" value="<?= arapH($keyword) ?>" placeholder="대상명, 분류, 메모">
                </div>
                <div class="col-sm-4" style="padding-top:24px;">
                    <button type="submit" class="btn btn-primary">조회</button>
                    <a href="<?= arapH($listUrl) ?>" class="btn btn-default">거래내역 보기</a>
                </div>
            </div>
        </form>

        <div class="arap-target-summary">
            <div class="arap-total-card income">
                <span class="arap-total-label">수입 대상 합계</span>
                <span class="arap-total-value">+$<?= arapFormatAmount(abs((float)$summary['income_total'])) ?></span>
                <span class="arap-total-sub"><?= (int)$summary['income_count'] ?>건 · <?= arapH($startDate) ?> ~ <?= arapH($endDate) ?></span>
            </div>
            <div class="arap-total-card expense">
                <span class="arap-total-label">지출 대상 합계</span>
                <span class="arap-total-value">-$<?= arapFormatAmount(abs((float)$summary['expense_total'])) ?></span>
                <span class="arap-total-sub"><?= (int)$summary['expense_count'] ?>건 · 수입 제외</span>
            </div>
            <div class="arap-total-card guide">
                <span class="arap-total-label">가이드 정산 합계</span>
                <span class="arap-total-value">-$<?= arapFormatAmount(abs((float)$summary['guide_total'])) ?></span>
                <span class="arap-total-sub"><?= (int)$summary['guide_count'] ?>건 · 회계일자 등록 완료</span>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="arap-section-head">
                    <h3>수입 대상 목록</h3>
                    <span class="badge"><?= count($incomeCards) ?>개</span>
                </div>
                <?php if (count($incomeCards) === 0) { ?>
                    <div class="arap-empty">조회된 수입 대상이 없습니다.</div>
                <?php } else { ?>
                    <div class="arap-card-grid">
                        <?php foreach ($incomeCards as $row) {
                            $statusList = isset($row['status_list']) ? $row['status_list'] : '';
                            $statusArr = $statusList !== '' ? explode(',', $statusList) : array();
                            $isMixedStatus = count($statusArr) > 1;
                            $cardStatus = !$isMixedStatus && count($statusArr) === 1 ? $statusArr[0] : '';
                            $cardStatusLabel = $isMixedStatus ? '혼합' : (isset($targetStatusOptions[$cardStatus]) ? $targetStatusOptions[$cardStatus] : '');
                        ?>
                            <div class="arap-target-card">
                                <div class="card-top">
                                    <h4><?= arapH($row['target_name']) ?></h4>
                                    <span class="arap-type-pill income">수입</span>
                                </div>
                                <div class="arap-card-amount">+$<?= arapFormatAmount(abs((float)$row['amount_total'])) ?></div>
                                <div class="arap-card-meta">
                                    <span><?= arapH($row['cat_name']) ?><?= $row['sub_name'] !== '' ? ' / ' . arapH($row['sub_name']) : '' ?></span>
                                    <span><?= (int)$row['tx_count'] ?>건</span>
                                    <span><?= arapH($row['first_date']) ?> ~ <?= arapH($row['last_date']) ?></span>
                                    <?php if ($row['method_names'] !== '') { ?><span><?= arapH($row['method_names']) ?></span><?php } ?>
                                </div>
                                <div class="arap-card-status-row">
                                    <span class="arap-card-status-label">거래상태:</span>
                                    <span class="arap-card-status-current<?= $isMixedStatus ? ' mixed' : '' ?>"><?= arapH($cardStatusLabel) ?></span>
                                    <select class="form-control input-sm js-target-status"
                                            data-tx-type="IN"
                                            data-cat-id="<?= (int)$row['cat_id'] ?>"
                                            data-sub-id="<?= (int)$row['sub_id'] ?>"
                                            data-raw-description="<?= arapH($row['raw_description']) ?>"
                                            data-tx-count="<?= (int)$row['tx_count'] ?>">
                                        <option value="">변경</option>
                                        <?php foreach ($targetStatusOptions as $sk => $sv) { ?>
                                            <option value="<?= arapH($sk) ?>"<?= ($cardStatus === $sk ? ' selected' : '') ?>><?= arapH($sv) ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <form method="post" class="arap-target-actions">
                                    <input type="hidden" name="mode" value="target_delete">
                                    <input type="hidden" name="tx_type" value="IN">
                                    <input type="hidden" name="cat_id" value="<?= (int)$row['cat_id'] ?>">
                                    <input type="hidden" name="sub_id" value="<?= (int)$row['sub_id'] ?>">
                                    <input type="hidden" name="raw_description" value="<?= arapH($row['raw_description']) ?>">
                                    <input type="hidden" name="tx_count" value="<?= (int)$row['tx_count'] ?>">
                                    <input type="hidden" name="division" value="<?= arapH($division) ?>">
                                    <input type="hidden" name="pdx" value="<?= arapH($pdx) ?>">
                                    <input type="hidden" name="sub" value="<?= arapH($sub) ?>">
                                    <input type="hidden" name="month" value="<?= arapH($month) ?>">
                                    <input type="hidden" name="keyword" value="<?= arapH($keyword) ?>">
                                    <button type="submit" class="btn btn-xs btn-danger js-target-delete">삭제</button>
                                </form>
                            </div>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>

            <div class="col-md-6">
                <div class="arap-section-head">
                    <h3>지출 대상 목록</h3>
                    <span class="badge"><?= count($expenseCards) ?>개</span>
                </div>
                <?php if (count($expenseCards) === 0) { ?>
                    <div class="arap-empty">조회된 지출 대상이 없습니다.</div>
                <?php } else { ?>
                    <div class="arap-card-grid">
                        <?php foreach ($expenseCards as $row) {
                            $statusList = isset($row['status_list']) ? $row['status_list'] : '';
                            $statusArr = $statusList !== '' ? explode(',', $statusList) : array();
                            $isMixedStatus = count($statusArr) > 1;
                            $cardStatus = !$isMixedStatus && count($statusArr) === 1 ? $statusArr[0] : '';
                            $cardStatusLabel = $isMixedStatus ? '혼합' : (isset($targetStatusOptions[$cardStatus]) ? $targetStatusOptions[$cardStatus] : '');
                        ?>
                            <div class="arap-target-card">
                                <div class="card-top">
                                    <h4><?= arapH($row['target_name']) ?></h4>
                                    <span class="arap-type-pill expense">지출</span>
                                </div>
                                <div class="arap-card-amount">-$<?= arapFormatAmount(abs((float)$row['amount_total'])) ?></div>
                                <div class="arap-card-meta">
                                    <span><?= arapH($row['cat_name']) ?><?= $row['sub_name'] !== '' ? ' / ' . arapH($row['sub_name']) : '' ?></span>
                                    <span><?= (int)$row['tx_count'] ?>건</span>
                                    <span><?= arapH($row['first_date']) ?> ~ <?= arapH($row['last_date']) ?></span>
                                    <?php if ($row['method_names'] !== '') { ?><span><?= arapH($row['method_names']) ?></span><?php } ?>
                                </div>
                                <div class="arap-card-status-row">
                                    <span class="arap-card-status-label">거래상태:</span>
                                    <span class="arap-card-status-current<?= $isMixedStatus ? ' mixed' : '' ?>"><?= arapH($cardStatusLabel) ?></span>
                                    <select class="form-control input-sm js-target-status"
                                            data-tx-type="OUT"
                                            data-cat-id="<?= (int)$row['cat_id'] ?>"
                                            data-sub-id="<?= (int)$row['sub_id'] ?>"
                                            data-raw-description="<?= arapH($row['raw_description']) ?>"
                                            data-tx-count="<?= (int)$row['tx_count'] ?>">
                                        <option value="">변경</option>
                                        <?php foreach ($targetStatusOptions as $sk => $sv) { ?>
                                            <option value="<?= arapH($sk) ?>"<?= ($cardStatus === $sk ? ' selected' : '') ?>><?= arapH($sv) ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <form method="post" class="arap-target-actions">
                                    <input type="hidden" name="mode" value="target_delete">
                                    <input type="hidden" name="tx_type" value="OUT">
                                    <input type="hidden" name="cat_id" value="<?= (int)$row['cat_id'] ?>">
                                    <input type="hidden" name="sub_id" value="<?= (int)$row['sub_id'] ?>">
                                    <input type="hidden" name="raw_description" value="<?= arapH($row['raw_description']) ?>">
                                    <input type="hidden" name="tx_count" value="<?= (int)$row['tx_count'] ?>">
                                    <input type="hidden" name="division" value="<?= arapH($division) ?>">
                                    <input type="hidden" name="pdx" value="<?= arapH($pdx) ?>">
                                    <input type="hidden" name="sub" value="<?= arapH($sub) ?>">
                                    <input type="hidden" name="month" value="<?= arapH($month) ?>">
                                    <input type="hidden" name="keyword" value="<?= arapH($keyword) ?>">
                                    <button type="submit" class="btn btn-xs btn-danger js-target-delete">삭제</button>
                                </form>
                            </div>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>
        </div>

        <div class="arap-section-head" style="margin-top:24px;">
            <h3>가이드 정산 목록</h3>
            <span class="badge"><?= count($guideRows) ?>건</span>
        </div>
        <?php if (count($guideRows) === 0) { ?>
            <div class="arap-empty">회계일자가 등록된 가이드 정산 완료 내역이 없습니다.</div>
        <?php } else { ?>
            <div class="arap-guide-list">
                <?php foreach ($guideRows as $row) { ?>
                    <?php
                    $guideDepositSide = (float)$row['pre_amt']
                        + (float)$row['shopping_cprofit_total']
                        + (float)$row['option_cprofit_total']
                        + (float)$row['deposit_total'];
                    $guideCostSide = (float)$row['meal_total']
                        + (float)$row['admission_total']
                        + (float)$row['etc_total']
                        + (float)$row['shopping_total']
                        + (float)$row['shopping_gprofit_total'];
                    $guideAmount = $guideDepositSide - $guideCostSide;
                    $detailUrl = arapPageUrl('guide_cal_m.php', array(
                        'division' => 6,
                        'pdx' => 2,
                        'sub' => 10,
                        'number' => $row['guide_seq_no'] > 0 ? $row['guide_seq_no'] : $row['seq_no'],
                        'scode' => $row['settle_code']
                    ));
                    $guideLabel = $row['p_name'] !== '' ? $row['p_name'] : ($row['grand_eCode'] . ' / ' . $row['sub_eCode']);
                    $descParts = array($row['settle_code']);
                    if ($guideLabel !== '') { $descParts[] = $guideLabel; }
                    if ($row['guide_name'] !== '') { $descParts[] = '가이드:' . $row['guide_name']; }
                    $guideDescription = mb_substr(implode(' | ', $descParts), 0, 255, 'UTF-8');
                    ?>
                    <div class="arap-guide-card">
                        <div class="guide-date">회계일자 <?= arapH($row['finance_date']) ?></div>
                        <div class="card-top">
                            <h4><a href="<?= arapH($detailUrl) ?>"><?= arapH($row['settle_code']) ?></a></h4>
                            <span class="arap-type-pill guide">정산</span>
                        </div>
                        <div class="guide-amount">-$<?= arapFormatAmount(abs($guideAmount)) ?></div>
                        <div class="guide-note">
                            <?= arapH($row['stDate']) ?><?= $row['edDate'] !== '' ? ' ~ ' . arapH($row['edDate']) : '' ?><br>
                            <?= arapH($guideLabel) ?><br>
                            가이드: <?= arapH($row['guide_name']) ?><br>
                            입금 +$<?= arapFormatAmount(abs($guideDepositSide)) ?> · 비용 -$<?= arapFormatAmount(abs($guideCostSide)) ?>
                            <?php if ($row['g_memo'] !== '') { ?><br><?= arapH($row['g_memo']) ?><?php } ?>
                        </div>
                        <form method="post" class="arap-guide-actions">
                            <input type="hidden" name="mode" value="guide_action">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="seq_no" value="<?= (int)$row['seq_no'] ?>">
                            <input type="hidden" name="settle_code" value="<?= arapH($row['settle_code']) ?>">
                            <input type="hidden" name="finance_date" value="<?= arapH($row['finance_date']) ?>">
                            <input type="hidden" name="amount" value="<?= arapH(number_format(abs($guideAmount), 2, '.', '')) ?>">
                            <input type="hidden" name="amount_display" value="-$<?= arapH(arapFormatAmount(abs($guideAmount))) ?>">
                            <input type="hidden" name="description" value="<?= arapH($guideDescription) ?>">
                            <input type="hidden" name="memo" value="<?= arapH($row['g_memo']) ?>">
                            <input type="hidden" name="division" value="<?= arapH($division) ?>">
                            <input type="hidden" name="pdx" value="<?= arapH($pdx) ?>">
                            <input type="hidden" name="sub" value="<?= arapH($sub) ?>">
                            <input type="hidden" name="month" value="<?= arapH($month) ?>">
                            <input type="hidden" name="keyword" value="<?= arapH($keyword) ?>">
                            <input type="hidden" name="method_id" value="<?= (int)$guideDefaultMethodId ?>">
                            <button type="submit" class="btn btn-xs btn-primary js-guide-add">지출대상 추가</button>
                        </form>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
</div>

<?php include "include/side_m.php"; ?>

<script>
var arapTargetStatusLabels = <?= json_encode($targetStatusOptions, JSON_UNESCAPED_UNICODE) ?>;
var arapTargetUpdateUrl = <?= json_encode($targetUpdateUrl, JSON_UNESCAPED_UNICODE) ?>;

$(function() {
    $(document).on('click', '.js-guide-add', function() {
        var $form = $(this).closest('form');
        var amountDisp = $form.find('input[name="amount_display"]').val() || '';
        if (!confirm('이 가이드 정산을 지출 ' + amountDisp + ' 으로 등록하시겠습니까?')) {
            return false;
        }
    });
    $(document).on('click', '.js-target-delete', function() {
        var $form = $(this).closest('form');
        var cnt = parseInt($form.find('input[name="tx_count"]').val() || '1', 10);
        var msg = '이 카드에 묶인 거래(' + cnt + '건)를 모두 삭제하시겠습니까?';
        if (!confirm(msg)) {
            return false;
        }
    });

    $(document).on('change', '.js-target-status', function() {
        var $select = $(this);
        var newStatus = $select.val();
        if (!newStatus) { return; }

        var txCount = parseInt($select.data('tx-count') || '0', 10);
        var label = arapTargetStatusLabels[newStatus] || newStatus;
        if (!confirm('이 카드에 묶인 거래(' + txCount + '건)의 상태를 "' + label + '"(으)로 변경하시겠습니까?')) {
            $select.val($select.data('prev-value') || '');
            return;
        }

        var data = {
            mode: 'target_status_update',
            tx_type: $select.data('tx-type'),
            cat_id: $select.data('cat-id'),
            sub_id: $select.data('sub-id'),
            raw_description: $select.data('raw-description'),
            new_status: newStatus,
            month: <?= json_encode($month, JSON_UNESCAPED_UNICODE) ?>
        };

        $select.prop('disabled', true);
        $.post(arapTargetUpdateUrl, data, null, 'json').done(function(resp) {
            $select.prop('disabled', false);
            if (resp && resp.ok) {
                var $row = $select.closest('.arap-card-status-row');
                $row.find('.arap-card-status-current').removeClass('mixed').text(label);
                $select.data('prev-value', newStatus);
            } else {
                $select.val($select.data('prev-value') || '');
                alert((resp && resp.message) ? resp.message : '저장 중 오류가 발생했습니다.');
            }
        }).fail(function() {
            $select.prop('disabled', false);
            $select.val($select.data('prev-value') || '');
            alert('서버 통신 오류가 발생했습니다.');
        });
    });

    $('.js-target-status').each(function() {
        $(this).data('prev-value', $(this).val());
    });
});
</script>
