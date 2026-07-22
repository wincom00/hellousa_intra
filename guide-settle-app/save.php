<?php
require_once __DIR__ . '/include/bootstrap.php';

$gsaUser = gsa_require_login();
$gsaRole = gsa_user_role($gsaUser);

function gsa_db_fetch_one($sql, $types = '', $params = array())
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

function gsa_save_escape($value)
{
    global $dbConn;

    return trim($dbConn->real_escape_string((string)$value));
}

function gsa_save_number($value)
{
    $value = str_replace(',', '', trim((string)$value));
    if ($value === '') {
        return '';
    }
    if (!is_numeric($value)) {
        return '0';
    }

    return (string)(0 + $value);
}

function gsa_save_post($key, $default = '')
{
    return isset($_POST[$key]) ? gsa_save_escape($_POST[$key]) : $default;
}

function gsa_save_post_number($key, $default = '')
{
    return isset($_POST[$key]) ? gsa_save_number($_POST[$key]) : $default;
}

function gsa_save_post_array($key, $numeric = false)
{
    if (!isset($_POST[$key])) {
        return array();
    }

    $value = $_POST[$key];
    if (!is_array($value)) {
        $value = array($value);
    }

    $rows = array();
    foreach ($value as $item) {
        $rows[] = $numeric ? gsa_save_number($item) : gsa_save_escape($item);
    }

    return $rows;
}

function gsa_save_is_blank_row($values)
{
    foreach ($values as $value) {
        if (trim((string)$value) !== '') {
            return false;
        }
    }

    return true;
}

function gsa_save_stmt(mysqli $dbConn, $sql, $types, $params)
{
    $stmt = $dbConn->prepare($sql);
    if (!$stmt) {
        throw new Exception('SQL prepare failed');
    }

    if ($types !== '' && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        $stmt->close();
        throw new Exception('SQL execute failed');
    }

    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['mode']) || $_POST['mode'] !== 'save') {
    echo "<script>alert('잘못된 접근입니다.'); history.back();</script>";
    exit;
}

$seqNo = isset($_POST['seq_no']) ? (int)$_POST['seq_no'] : 0;
if ($seqNo <= 0) {
    echo "<script>alert('행사 번호가 없습니다.'); history.back();</script>";
    exit;
}

$masterRow = gsa_db_fetch_one(
    "SELECT * FROM tour_guide WHERE seq_no = ? LIMIT 1",
    'i',
    array($seqNo)
);
if (!$masterRow) {
    echo "<script>alert('행사 정보를 찾을 수 없습니다.'); history.back();</script>";
    exit;
}

if (!gsa_can($gsaRole, 'edit', $masterRow)) {
    echo "<script>alert('수정 권한이 없습니다.'); history.back();</script>";
    exit;
}

if ($gsaRole === 'guide') {
    $currentUserId = isset($gsaUser['userid']) ? (string)$gsaUser['userid'] : '';
    $rowGuideId = isset($masterRow['guide_id']) ? (string)$masterRow['guide_id'] : '';
    if ($currentUserId === '' || $currentUserId !== $rowGuideId) {
        echo "<script>alert('본인 정산만 저장할 수 있습니다.'); history.back();</script>";
        exit;
    }
}

$currentGuideCode = getGuideCode($masterRow['grand_eCode'], $masterRow['sub_eCode']);
$currentSettleCode = is_array($currentGuideCode) && isset($currentGuideCode['settle_code']) ? trim((string)$currentGuideCode['settle_code']) : '';
$currentSetMaster = array();
if ($currentSettleCode !== '') {
    $currentSetMaster = gsa_db_fetch_one(
        "SELECT reg_status FROM guide_setmaster WHERE settle_code = ? LIMIT 1",
        's',
        array($currentSettleCode)
    );
}
$permissionRow = $masterRow;
if (is_array($currentSetMaster)) {
    $permissionRow = array_merge($permissionRow, $currentSetMaster);
}
if (!gsa_can($gsaRole, 'edit', $permissionRow)) {
    echo "<script>alert('제출 완료된 정산은 수정할 수 없습니다.'); history.back();</script>";
    exit;
}

$pCode = gsa_save_post('pcode');
$stDate = gsa_save_post('stDate');
$grandECode = gsa_save_post('grand_eCode');
$subECode = gsa_save_post('sub_eCode');
$guideEtcDepAmount = gsa_save_post_number('guideEtcDepAmount', '0');
$postedSettleCode = gsa_save_post('settle_code');
$period = getPeriodbyhotel($pCode, $stDate);
$periodParts = explode('~', str_replace(' ', '', (string)$period));
$startDate = isset($periodParts[0]) && $periodParts[0] !== '' ? $periodParts[0] : $stDate;
$endDate = isset($periodParts[1]) && $periodParts[1] !== '' ? $periodParts[1] : $startDate;
$guideCode = $postedSettleCode !== '' ? $postedSettleCode : 'GU-' . date('His') . sprintf('%02d', (int)(microtime(true) * 100) % 100);
$userId = isset($gsaUser['userid']) ? (string)$gsaUser['userid'] : '';

$bfDate = gsa_save_post_array('bfDate');
$bfStoreName = gsa_save_post_array('bfStoreName');
$bfPerson = gsa_save_post_array('bfPerson', true);
$bfCost = gsa_save_post_array('bfCost', true);
$bfTotalCost = gsa_save_post_array('bfTotalCost', true);
$bfSeq = gsa_save_post_array('bf_seq', true);

$lunchDate = gsa_save_post_array('lunchDate');
$lunchStoreName = gsa_save_post_array('lunchStoreName');
$lunchPerson = gsa_save_post_array('lunchPerson', true);
$lunchCost = gsa_save_post_array('lunchCost', true);
$lunchTotalCost = gsa_save_post_array('lunchTotalCost', true);
$lunchSeq = gsa_save_post_array('lunch_seq', true);

$dinnerDate = gsa_save_post_array('dinnerDate');
$dinnerStoreName = gsa_save_post_array('dinnerStoreName');
$dinnerPerson = gsa_save_post_array('dinnerPerson', true);
$dinnerCost = gsa_save_post_array('dinnerCost', true);
$dinnerTotalCost = gsa_save_post_array('dinnerTotalCost', true);
$dinnerSeq = gsa_save_post_array('dinner_seq', true);

$nameSelect = gsa_save_post_array('nameSelect');
$person = gsa_save_post_array('person', true);
$cost = gsa_save_post_array('cost', true);
$totalAmount = gsa_save_post_array('totalAmount', true);
$admissionSeq = gsa_save_post_array('admission_seq', true);

$optionName = gsa_save_post_array('optionName');
$assignGuideLine = gsa_save_post_array('assignGuideLine');
$optPerson = gsa_save_post_array('optPerson', true);
$optCost = gsa_save_post_array('optCost', true);
$optTotalAmount = gsa_save_post_array('optTotalAmount', true);
$optPrice = gsa_save_post_array('optPrice', true);
$optTotalPrice = gsa_save_post_array('optTotalPrice', true);
$optDiffAmount = gsa_save_post_array('optDiffAmount', true);
$optProfit = gsa_save_post_array('optProfit', true);
$optGuideProfit = gsa_save_post_array('optGuideProfit', true);
$optSeq = gsa_save_post_array('opt_seq', true);

$guideCarSelect = gsa_save_post_array('guideCarSelect');
$guideTotalAmount = gsa_save_post_array('guideTotalAmount', true);
$guideMemo = gsa_save_post_array('guideMemo');
$guideSeq = gsa_save_post_array('guide_seq', true);

$carSelect = gsa_save_post_array('carSelect');
$carTotalAmount = gsa_save_post_array('carTotalAmount', true);
$carMemo = gsa_save_post_array('carMemo');
$carSeq = gsa_save_post_array('car_seq', true);

$etcCarSelect = gsa_save_post_array('etcCarSelect');
$etcTotalAmount = gsa_save_post_array('etcTotalAmount', true);
$etcMemo = gsa_save_post_array('etcMemo');
$etcSeq = gsa_save_post_array('etc_seq', true);

$shoppingSelect = gsa_save_post_array('shoppingSelect');
$saleTotalAmount = gsa_save_post_array('saleTotalAmount', true);
$homeshoppingcom = gsa_save_post_array('homeshoppingcom', true);
$companyProfit = gsa_save_post_array('companyProfit', true);
$shoppingGuideProfit = gsa_save_post_array('shoppingGuideProfit', true);
$shoppSeq = gsa_save_post_array('shopp_seq', true);

$ipSelect = gsa_save_post_array('ipSelect');
$gInputAmt = gsa_save_post_array('g_inputamt', true);
$gInputCnt = gsa_save_post_array('g_inputcnt', true);
$gInputMemo = gsa_save_post_array('g_inputmemo');
$gSeqNo = gsa_save_post_array('g_seqno', true);

$adultInputAmt = gsa_save_post_number('adult_inputamt');
$adultInputCnt = gsa_save_post_number('adult_inputcnt');
$adultInputMemo = gsa_save_post('adult_inputmemo');
$childInputAmt = gsa_save_post_number('child_inputamt');
$childInputCnt = gsa_save_post_number('child_inputcnt');
$childInputMemo = gsa_save_post('child_inputmemo');
$etcInputAmt = gsa_save_post_number('etc_inputamt');
$etcInputCnt = gsa_save_post_number('etc_inputcnt');
$etcInputMemo = gsa_save_post('etc_inputmemo');

if (empty($ipSelect) && ($adultInputAmt !== '' || $adultInputCnt !== '' || $adultInputMemo !== '' || $childInputAmt !== '' || $childInputCnt !== '' || $childInputMemo !== '' || $etcInputAmt !== '' || $etcInputCnt !== '' || $etcInputMemo !== '')) {
    $legacyInputRows = array(
        array('adult', $adultInputAmt, $adultInputCnt, $adultInputMemo),
        array('child', $childInputAmt, $childInputCnt, $childInputMemo),
        array('etc', $etcInputAmt, $etcInputCnt, $etcInputMemo)
    );
    foreach ($legacyInputRows as $legacyInputRow) {
        if (gsa_save_is_blank_row(array($legacyInputRow[1], $legacyInputRow[2], $legacyInputRow[3]))) {
            continue;
        }
        $ipSelect[] = $legacyInputRow[0];
        $gInputAmt[] = $legacyInputRow[1];
        $gInputCnt[] = $legacyInputRow[2];
        $gInputMemo[] = $legacyInputRow[3];
        $gSeqNo[] = '0';
    }
}

$deleteTables = array(
    'guide_meal',
    'guide_admission',
    'guide_option',
    'guide_etcamt',
    'guide_shopping',
    'guide_inputamt',
    'guide_setmaster'
);

try {
    $dbConn->begin_transaction();

    foreach ($deleteTables as $table) {
        gsa_save_stmt($dbConn, "DELETE FROM {$table} WHERE settle_code = ?", 's', array($guideCode));
    }
    if ($postedSettleCode !== '' && $postedSettleCode !== $guideCode) {
        foreach ($deleteTables as $table) {
            gsa_save_stmt($dbConn, "DELETE FROM {$table} WHERE settle_code = ?", 's', array($postedSettleCode));
        }
    }

    gsa_save_stmt(
        $dbConn,
        "INSERT INTO guide_setmaster (
            settle_code, grand_eCode, sub_eCode, stDate, edDate, guide_etcamt, reg_status, reg_user, wdate
        ) VALUES (?, ?, ?, ?, ?, ?, 'DONE', ?, NOW())",
        'sssssss',
        array($guideCode, $grandECode, $subECode, $startDate, $endDate, $guideEtcDepAmount, $userId)
    );

    $mealInsertSql = "INSERT INTO guide_meal (
        settle_code, meal_type, meal_date, meal_rest, meal_cnt, meal_price, meal_pricetotal, wdate
    ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
    $mealSets = array(
        'bf' => array($bfDate, $bfStoreName, $bfPerson, $bfCost, $bfTotalCost),
        'lunch' => array($lunchDate, $lunchStoreName, $lunchPerson, $lunchCost, $lunchTotalCost),
        'dinner' => array($dinnerDate, $dinnerStoreName, $dinnerPerson, $dinnerCost, $dinnerTotalCost)
    );
    foreach ($mealSets as $mealType => $set) {
        $max = max(count($set[0]), count($set[1]), count($set[2]), count($set[3]), count($set[4]));
        for ($i = 0; $i < $max; $i++) {
            $rowDate = isset($set[0][$i]) ? $set[0][$i] : '';
            $rowStore = isset($set[1][$i]) ? $set[1][$i] : '';
            $rowPerson = isset($set[2][$i]) ? $set[2][$i] : '';
            $rowCost = isset($set[3][$i]) ? $set[3][$i] : '';
            $rowTotal = isset($set[4][$i]) ? $set[4][$i] : '';
            if (gsa_save_is_blank_row(array($rowDate, $rowStore, $rowPerson, $rowCost, $rowTotal))) {
                continue;
            }
            gsa_save_stmt($dbConn, $mealInsertSql, 'sssssss', array($guideCode, $mealType, $rowDate, $rowStore, $rowPerson, $rowCost, $rowTotal));
        }
    }

    $admissionInsertSql = "INSERT INTO guide_admission (
        settle_code, admission_code, e_cnt, e_price, e_pricetot, wdate
    ) VALUES (?, ?, ?, ?, ?, NOW())";
    $admissionMax = max(count($nameSelect), count($person), count($cost), count($totalAmount));
    for ($i = 0; $i < $admissionMax; $i++) {
        $rowCode = isset($nameSelect[$i]) ? $nameSelect[$i] : '';
        $rowPerson = isset($person[$i]) ? $person[$i] : '';
        $rowCost = isset($cost[$i]) ? $cost[$i] : '';
        $rowTotal = isset($totalAmount[$i]) ? $totalAmount[$i] : '';
        if (gsa_save_is_blank_row(array($rowCode, $rowPerson, $rowCost, $rowTotal))) {
            continue;
        }
        gsa_save_stmt($dbConn, $admissionInsertSql, 'sssss', array($guideCode, $rowCode, $rowPerson, $rowCost, $rowTotal));
    }

    $optionInsertSql = "INSERT INTO guide_option (
        settle_code, option_code, base_set, o_cnt, o_price, o_pricetot, o_cprice, o_cpricetot, o_diffamt, o_cprofit, o_gprofit, wdate
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $optionMax = max(count($optionName), count($assignGuideLine), count($optPerson), count($optCost), count($optTotalAmount), count($optPrice), count($optTotalPrice), count($optDiffAmount), count($optProfit), count($optGuideProfit));
    for ($i = 0; $i < $optionMax; $i++) {
        $rowOptionName = isset($optionName[$i]) ? $optionName[$i] : '';
        $rowRatio = isset($assignGuideLine[$i]) ? $assignGuideLine[$i] : '';
        $rowPerson = isset($optPerson[$i]) ? $optPerson[$i] : '';
        $rowCost = isset($optCost[$i]) ? $optCost[$i] : '';
        $rowCostTotal = isset($optTotalAmount[$i]) ? $optTotalAmount[$i] : '';
        $rowSale = isset($optPrice[$i]) ? $optPrice[$i] : '';
        $rowSaleTotal = isset($optTotalPrice[$i]) ? $optTotalPrice[$i] : '';
        $rowDiff = isset($optDiffAmount[$i]) ? $optDiffAmount[$i] : '';
        $rowCompany = isset($optProfit[$i]) ? $optProfit[$i] : '';
        $rowGuide = isset($optGuideProfit[$i]) ? $optGuideProfit[$i] : '';
        if (gsa_save_is_blank_row(array($rowOptionName, $rowRatio, $rowPerson, $rowCost, $rowCostTotal, $rowSale, $rowSaleTotal, $rowDiff, $rowCompany, $rowGuide))) {
            continue;
        }
        gsa_save_stmt($dbConn, $optionInsertSql, 'sssssssssss', array($guideCode, $rowOptionName, $rowRatio, $rowPerson, $rowCost, $rowCostTotal, $rowSale, $rowSaleTotal, $rowDiff, $rowCompany, $rowGuide));
    }

    $etcInsertSql = "INSERT INTO guide_etcamt (
        settle_code, etc_type, etc_pricety, etc_amt, etc_memo, wdate
    ) VALUES (?, ?, ?, ?, ?, NOW())";
    $etcGroups = array(
        'guide' => array($guideCarSelect, $guideTotalAmount, $guideMemo),
        'car' => array($carSelect, $carTotalAmount, $carMemo),
        'etc' => array($etcCarSelect, $etcTotalAmount, $etcMemo)
    );
    foreach ($etcGroups as $groupKey => $set) {
        $max = max(count($set[0]), count($set[1]), count($set[2]));
        for ($i = 0; $i < $max; $i++) {
            $rowType = isset($set[0][$i]) ? $set[0][$i] : '';
            $rowAmount = isset($set[1][$i]) ? $set[1][$i] : '';
            $rowMemo = isset($set[2][$i]) ? $set[2][$i] : '';
            if (gsa_save_is_blank_row(array($rowType, $rowAmount, $rowMemo))) {
                continue;
            }
            gsa_save_stmt($dbConn, $etcInsertSql, 'sssss', array($guideCode, $rowType, $groupKey, $rowAmount, $rowMemo));
        }
    }

    $shoppingInsertSql = "INSERT INTO guide_shopping (
        settle_code, shop_code, tot_amt, home_comamt, c_profit, g_profit, wdate
    ) VALUES (?, ?, ?, ?, ?, ?, NOW())";
    $shoppingMax = max(count($shoppingSelect), count($saleTotalAmount), count($homeshoppingcom), count($companyProfit), count($shoppingGuideProfit));
    for ($i = 0; $i < $shoppingMax; $i++) {
        $rowShopCode = isset($shoppingSelect[$i]) ? $shoppingSelect[$i] : '';
        $rowSale = isset($saleTotalAmount[$i]) ? $saleTotalAmount[$i] : '';
        $rowHome = isset($homeshoppingcom[$i]) ? $homeshoppingcom[$i] : '';
        $rowCompany = isset($companyProfit[$i]) ? $companyProfit[$i] : '';
        $rowGuide = isset($shoppingGuideProfit[$i]) ? $shoppingGuideProfit[$i] : '';
        if (gsa_save_is_blank_row(array($rowShopCode, $rowSale, $rowHome, $rowCompany, $rowGuide))) {
            continue;
        }
        gsa_save_stmt($dbConn, $shoppingInsertSql, 'ssssss', array($guideCode, $rowShopCode, $rowSale, $rowHome, $rowCompany, $rowGuide));
    }

    $inputInsertSql = "INSERT INTO guide_inputamt (
        settle_code, inputamt_type, input_amt, input_cnt, input_memo, wdate
    ) VALUES (?, ?, ?, ?, ?, NOW())";
    $inputMax = max(count($ipSelect), count($gInputAmt), count($gInputCnt), count($gInputMemo));
    for ($i = 0; $i < $inputMax; $i++) {
        $rowType = isset($ipSelect[$i]) ? $ipSelect[$i] : '';
        $rowAmount = isset($gInputAmt[$i]) ? $gInputAmt[$i] : '';
        $rowCount = isset($gInputCnt[$i]) ? $gInputCnt[$i] : '';
        $rowMemo = isset($gInputMemo[$i]) ? $gInputMemo[$i] : '';
        if (gsa_save_is_blank_row(array($rowType, $rowAmount, $rowCount, $rowMemo))) {
            continue;
        }
        gsa_save_stmt($dbConn, $inputInsertSql, 'sssss', array($guideCode, $rowType, $rowAmount, $rowCount, $rowMemo));
    }

    $dbConn->commit();
    header('Location: form.php?seq_no=' . $seqNo . '&saved=1');
    exit;
} catch (Exception $e) {
    $dbConn->rollback();
    echo "<script>alert('저장 중 오류가 발생했습니다. 다시 시도해 주세요.'); history.back();</script>";
    exit;
}
