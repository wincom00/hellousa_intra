<?php
require_once __DIR__ . '/include/bootstrap.php';
require_once __DIR__ . '/include/layout.php';

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

function gsa_db_fetch_all($sql, $types = '', $params = array())
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

function gsa_form_escape($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function gsa_form_money($value)
{
    return '$' . number_format((float)$value, 2);
}

function gsa_form_number($value)
{
    return number_format((float)$value, 0);
}

function gsa_form_number_input($value)
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

function gsa_form_label_or_dash($value)
{
    $value = trim((string)$value);
    if ($value === '') {
        return '-';
    }

    return $value;
}

function gsa_form_period_safe($pCode, $stDate)
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

function gsa_form_status_badge($statusHtml)
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

function gsa_form_fetch_code_lookup($lvCode)
{
    $lookup = array();
    $result = getGuideBaseCode($lvCode);
    if ($result instanceof mysqli_result) {
        while ($row = $result->fetch_assoc()) {
            $code = '';
            if (isset($row['lvcode1'], $row['lvcode2'])) {
                $code = $row['lvcode1'] . '|' . $row['lvcode2'];
            }
            if ($code !== '') {
                $lookup[$code] = isset($row['comment']) ? (string)$row['comment'] : $code;
            }
        }
        $result->free();
    }

    return $lookup;
}

function gsa_form_lookup_label($lookup, $code)
{
    $code = trim((string)$code);
    if ($code === '') {
        return '-';
    }

    return isset($lookup[$code]) ? $lookup[$code] : $code;
}

function gsa_form_calc_total($rowTotal, $countValue, $unitValue)
{
    $total = (float)$rowTotal;
    if ($total == 0.0) {
        $total = (float)$countValue * (float)$unitValue;
    }

    return $total;
}

function gsa_form_group_meals($mealRows)
{
    $grouped = array(
        'bf' => array(),
        'lunch' => array(),
        'dinner' => array()
    );

    foreach ($mealRows as $row) {
        $mealType = isset($row['meal_type']) ? (string)$row['meal_type'] : '';
        if (!isset($grouped[$mealType])) {
            continue;
        }
        $grouped[$mealType][] = $row;
    }

    return $grouped;
}

function gsa_form_group_etc_rows($etcRows)
{
    $grouped = array(
        'guide' => array(),
        'car' => array(),
        'etc' => array()
    );

    foreach ($etcRows as $row) {
        $groupKey = isset($row['etc_pricety']) ? (string)$row['etc_pricety'] : 'etc';
        if (!isset($grouped[$groupKey])) {
            $groupKey = 'etc';
        }
        $grouped[$groupKey][] = $row;
    }

    return $grouped;
}

function gsa_form_build_input_rows($inputRows)
{
    $mapped = array(
        'adult' => array(
            'inputamt_type' => 'adult',
            'label' => '성인',
            'input_amt' => '',
            'input_cnt' => '',
            'input_memo' => '',
            'seq_no' => 0
        ),
        'child' => array(
            'inputamt_type' => 'child',
            'label' => '아동',
            'input_amt' => '',
            'input_cnt' => '',
            'input_memo' => '',
            'seq_no' => 0
        ),
        'etc' => array(
            'inputamt_type' => 'etc',
            'label' => '기타',
            'input_amt' => '',
            'input_cnt' => '',
            'input_memo' => '',
            'seq_no' => 0
        )
    );

    foreach ($inputRows as $row) {
        $type = isset($row['inputamt_type']) ? (string)$row['inputamt_type'] : '';
        if (isset($mapped[$type])) {
            $mapped[$type] = array_merge($mapped[$type], $row);
        }
    }

    return array_values($mapped);
}

function gsa_form_prepare_rows_for_edit($rows, $blankRow)
{
    if (empty($rows)) {
        return array($blankRow);
    }

    return $rows;
}

function gsa_form_blank_meal_row($mealType, $stDate)
{
    return array(
        'seq_no' => 0,
        'meal_type' => $mealType,
        'meal_date' => $stDate,
        'meal_rest' => '',
        'meal_cnt' => '',
        'meal_price' => '',
        'meal_pricetotal' => ''
    );
}

function gsa_form_blank_admission_row()
{
    return array(
        'seq_no' => 0,
        'admission_code' => '',
        'e_cnt' => '',
        'e_price' => '',
        'e_pricetot' => ''
    );
}

function gsa_form_blank_option_row()
{
    return array(
        'seq_no' => 0,
        'option_code' => '',
        'base_set' => '',
        'o_cnt' => '',
        'o_price' => '',
        'o_pricetot' => '',
        'o_cprice' => '',
        'o_cpricetot' => '',
        'o_diffamt' => '',
        'o_cprofit' => '',
        'o_gprofit' => ''
    );
}

function gsa_form_blank_etc_row($groupKey)
{
    return array(
        'seq_no' => 0,
        'etc_pricety' => $groupKey,
        'etc_type' => '',
        'etc_amt' => '',
        'etc_memo' => ''
    );
}

function gsa_form_render_info_lines($lines)
{
    foreach ($lines as $label => $value) {
        echo '<div class="gsa-inline-metric"><span class="text-muted">' . gsa_form_escape($label) . '</span><strong>' . gsa_form_escape($value) . '</strong></div>';
    }
}

function gsa_form_render_empty($message)
{
    echo '<div class="gsa-empty-state">' . gsa_form_escape($message) . '</div>';
}

function gsa_form_render_empty_with_action($message, $actionUrl, $actionLabel)
{
    echo '<div class="gsa-empty-state">';
    echo '<div>' . gsa_form_escape($message) . '</div>';
    if (trim((string)$actionUrl) !== '' && trim((string)$actionLabel) !== '') {
        echo '<div class="mt-3">';
        echo '<a href="' . gsa_form_escape($actionUrl) . '" class="btn btn-primary btn-sm gsa-empty-action-btn">' . gsa_form_escape($actionLabel) . '</a>';
        echo '</div>';
    }
    echo '</div>';
}

function gsa_form_render_select_options($options, $selectedValue, $placeholder = '- 선택 -')
{
    echo '<option value="">' . gsa_form_escape($placeholder) . '</option>';
    foreach ($options as $value => $label) {
        $selected = ((string)$selectedValue === (string)$value) ? ' selected' : '';
        echo '<option value="' . gsa_form_escape($value) . '"' . $selected . '>' . gsa_form_escape($label) . '</option>';
    }
}

function gsa_form_render_read_card($title, $amount, $lines, $subtitle = '', $memo = '')
{
    echo '<div class="card border-0 shadow-sm gsa-detail-card">';
    echo '<div class="card-body">';
    echo '<div class="d-flex gap-3 justify-content-between align-items-start">';
    echo '<div class="min-w-0 flex-grow-1">';
    echo '<div class="fw-semibold text-break">' . gsa_form_escape($title !== '' ? $title : '항목 없음') . '</div>';
    if (trim((string)$subtitle) !== '') {
        echo '<div class="small text-muted mt-1">' . gsa_form_escape($subtitle) . '</div>';
    }
    echo '</div>';
    echo '<div class="text-end"><div class="gsa-amount-pill">' . gsa_form_escape(gsa_form_money($amount)) . '</div></div>';
    echo '</div>';
    echo '<div class="gsa-info-grid mt-3">';
    foreach ($lines as $label => $value) {
        echo '<div class="gsa-info-cell">';
        echo '<div class="small text-muted">' . gsa_form_escape($label) . '</div>';
        echo '<div class="fw-semibold text-break">' . gsa_form_escape($value) . '</div>';
        echo '</div>';
    }
    echo '</div>';
    echo '<div class="gsa-memo-box mt-3"><div class="small text-muted">메모</div><div class="text-break">' . gsa_form_escape(trim((string)$memo) !== '' ? $memo : '-') . '</div></div>';
    echo '</div>';
    echo '</div>';
}

function gsa_form_render_meal_edit_card($mealType, $row)
{
    $dateName = $mealType . 'Date[]';
    $storeName = $mealType === 'bf' ? 'bfStoreName[]' : ($mealType === 'lunch' ? 'lunchStoreName[]' : 'dinnerStoreName[]');
    $personName = $mealType === 'bf' ? 'bfPerson[]' : ($mealType === 'lunch' ? 'lunchPerson[]' : 'dinnerPerson[]');
    $costName = $mealType === 'bf' ? 'bfCost[]' : ($mealType === 'lunch' ? 'lunchCost[]' : 'dinnerCost[]');
    $totalName = $mealType === 'bf' ? 'bfTotalCost[]' : ($mealType === 'lunch' ? 'lunchTotalCost[]' : 'dinnerTotalCost[]');
    $seqName = $mealType . '_seq[]';

    echo '<div class="card border-0 shadow-sm gsa-edit-card js-meal-row" data-meal-type="' . gsa_form_escape($mealType) . '">';
    echo '<div class="card-body">';
    echo '<div class="d-flex justify-content-between align-items-start gap-3">';
    echo '<div class="fw-semibold">식대 행</div>';
    echo '<button type="button" class="btn btn-outline-danger btn-sm js-gsa-remove-row">삭제</button>';
    echo '</div>';
    echo '<div class="row g-2 mt-1">';
    echo '<div class="col-6"><label class="form-label small fw-semibold mb-1">일자</label><input type="date" class="form-control" name="' . gsa_form_escape($dateName) . '" value="' . gsa_form_escape(isset($row['meal_date']) ? $row['meal_date'] : '') . '"></div>';
    echo '<div class="col-6"><label class="form-label small fw-semibold mb-1">가게명</label><input type="text" class="form-control" name="' . gsa_form_escape($storeName) . '" value="' . gsa_form_escape(isset($row['meal_rest']) ? $row['meal_rest'] : '') . '" placeholder="가게명"></div>';
    echo '<div class="col-4"><label class="form-label small fw-semibold mb-1">인원</label><input type="number" step="1" min="0" class="form-control js-row-count" name="' . gsa_form_escape($personName) . '" value="' . gsa_form_escape(gsa_form_number_input(isset($row['meal_cnt']) ? $row['meal_cnt'] : '')) . '"></div>';
    echo '<div class="col-4"><label class="form-label small fw-semibold mb-1">단가</label><input type="number" step="0.01" min="0" class="form-control js-row-unit" name="' . gsa_form_escape($costName) . '" value="' . gsa_form_escape(gsa_form_number_input(isset($row['meal_price']) ? $row['meal_price'] : '')) . '"></div>';
    echo '<div class="col-4"><label class="form-label small fw-semibold mb-1">합계</label><input type="number" step="0.01" min="0" class="form-control js-row-total" name="' . gsa_form_escape($totalName) . '" value="' . gsa_form_escape(gsa_form_number_input(isset($row['meal_pricetotal']) ? $row['meal_pricetotal'] : '')) . '" readonly></div>';
    echo '</div>';
    echo '<input type="hidden" name="' . gsa_form_escape($seqName) . '" value="' . gsa_form_escape(isset($row['seq_no']) ? $row['seq_no'] : 0) . '">';
    echo '</div>';
    echo '</div>';
}

function gsa_form_render_admission_edit_card($row, $lookup)
{
    echo '<div class="card border-0 shadow-sm gsa-edit-card js-admission-row">';
    echo '<div class="card-body">';
    echo '<div class="d-flex justify-content-between align-items-start gap-3">';
    echo '<div class="fw-semibold">입장비 행</div>';
    echo '<button type="button" class="btn btn-outline-danger btn-sm js-gsa-remove-row">삭제</button>';
    echo '</div>';
    echo '<div class="row g-2 mt-1">';
    echo '<div class="col-12"><label class="form-label small fw-semibold mb-1">입장지명</label><select class="form-select" name="nameSelect[]">';
    gsa_form_render_select_options($lookup, isset($row['admission_code']) ? $row['admission_code'] : '', '- 선택 -');
    echo '</select></div>';
    echo '<div class="col-4"><label class="form-label small fw-semibold mb-1">인원</label><input type="number" step="1" min="0" class="form-control js-row-count" name="person[]" value="' . gsa_form_escape(gsa_form_number_input(isset($row['e_cnt']) ? $row['e_cnt'] : '')) . '"></div>';
    echo '<div class="col-4"><label class="form-label small fw-semibold mb-1">단가</label><input type="number" step="0.01" min="0" class="form-control js-row-unit" name="cost[]" value="' . gsa_form_escape(gsa_form_number_input(isset($row['e_price']) ? $row['e_price'] : '')) . '"></div>';
    echo '<div class="col-4"><label class="form-label small fw-semibold mb-1">합계</label><input type="number" step="0.01" min="0" class="form-control js-row-total" name="totalAmount[]" value="' . gsa_form_escape(gsa_form_number_input(isset($row['e_pricetot']) ? $row['e_pricetot'] : '')) . '" readonly></div>';
    echo '</div>';
    echo '<input type="hidden" name="admission_seq[]" value="' . gsa_form_escape(isset($row['seq_no']) ? $row['seq_no'] : 0) . '">';
    echo '</div>';
    echo '</div>';
}

function gsa_form_render_option_edit_card($row, $lookup, $ratioOptions)
{
    echo '<div class="card border-0 shadow-sm gsa-edit-card js-option-row">';
    echo '<div class="card-body">';
    echo '<div class="d-flex justify-content-between align-items-start gap-3">';
    echo '<div class="fw-semibold">옵션 행</div>';
    echo '<button type="button" class="btn btn-outline-danger btn-sm js-gsa-remove-row">삭제</button>';
    echo '</div>';
    echo '<div class="row g-2 mt-1">';
    echo '<div class="col-6"><label class="form-label small fw-semibold mb-1">옵션명</label><select class="form-select" name="optionName[]">';
    gsa_form_render_select_options($lookup, isset($row['option_code']) ? $row['option_code'] : '', '- 선택 -');
    echo '</select></div>';
    echo '<div class="col-6"><label class="form-label small fw-semibold mb-1">배분</label><select class="form-select js-opt-ratio" name="assignGuideLine[]">';
    gsa_form_render_select_options($ratioOptions, isset($row['base_set']) ? $row['base_set'] : '', '- 선택 -');
    echo '</select></div>';
    echo '<div class="col-4"><label class="form-label small fw-semibold mb-1">인원</label><input type="number" step="1" min="0" class="form-control js-opt-count" name="optPerson[]" value="' . gsa_form_escape(gsa_form_number_input(isset($row['o_cnt']) ? $row['o_cnt'] : '')) . '"></div>';
    echo '<div class="col-4"><label class="form-label small fw-semibold mb-1">원가/P</label><input type="number" step="0.01" min="0" class="form-control js-opt-cost" name="optCost[]" value="' . gsa_form_escape(gsa_form_number_input(isset($row['o_price']) ? $row['o_price'] : '')) . '"></div>';
    echo '<div class="col-4"><label class="form-label small fw-semibold mb-1">옵션가/P</label><input type="number" step="0.01" min="0" class="form-control js-opt-sale" name="optPrice[]" value="' . gsa_form_escape(gsa_form_number_input(isset($row['o_cprice']) ? $row['o_cprice'] : '')) . '"></div>';
    echo '<div class="col-6"><label class="form-label small fw-semibold mb-1">원가총액</label><input type="number" step="0.01" min="0" class="form-control js-opt-cost-total" name="optTotalAmount[]" value="' . gsa_form_escape(gsa_form_number_input(isset($row['o_pricetot']) ? $row['o_pricetot'] : '')) . '" readonly></div>';
    echo '<div class="col-6"><label class="form-label small fw-semibold mb-1">옵션가총액</label><input type="number" step="0.01" min="0" class="form-control js-opt-sale-total" name="optTotalPrice[]" value="' . gsa_form_escape(gsa_form_number_input(isset($row['o_cpricetot']) ? $row['o_cpricetot'] : '')) . '" readonly></div>';
    echo '<div class="col-4"><label class="form-label small fw-semibold mb-1">차액</label><input type="number" step="0.01" min="0" class="form-control js-opt-diff" name="optDiffAmount[]" value="' . gsa_form_escape(gsa_form_number_input(isset($row['o_diffamt']) ? $row['o_diffamt'] : '')) . '" readonly></div>';
    echo '<div class="col-4"><label class="form-label small fw-semibold mb-1">회사수익</label><input type="number" step="0.01" min="0" class="form-control js-opt-company-profit" name="optProfit[]" value="' . gsa_form_escape(gsa_form_number_input(isset($row['o_cprofit']) ? $row['o_cprofit'] : '')) . '" readonly></div>';
    echo '<div class="col-4"><label class="form-label small fw-semibold mb-1">가이드수익</label><input type="number" step="0.01" min="0" class="form-control js-opt-guide-profit" name="optGuideProfit[]" value="' . gsa_form_escape(gsa_form_number_input(isset($row['o_gprofit']) ? $row['o_gprofit'] : '')) . '" readonly></div>';
    echo '</div>';
    echo '<input type="hidden" name="opt_seq[]" value="' . gsa_form_escape(isset($row['seq_no']) ? $row['seq_no'] : 0) . '">';
    echo '</div>';
    echo '</div>';
}

function gsa_form_render_etc_edit_card($row, $lookup, $fieldPrefix)
{
    $selectName = $fieldPrefix === 'guide' ? 'guideCarSelect[]' : ($fieldPrefix === 'car' ? 'carSelect[]' : 'etcCarSelect[]');
    $amountName = $fieldPrefix === 'guide' ? 'guideTotalAmount[]' : ($fieldPrefix === 'car' ? 'carTotalAmount[]' : 'etcTotalAmount[]');
    $memoName = $fieldPrefix === 'guide' ? 'guideMemo[]' : ($fieldPrefix === 'car' ? 'carMemo[]' : 'etcMemo[]');
    $seqName = $fieldPrefix . '_seq[]';

    echo '<div class="card border-0 shadow-sm gsa-edit-card js-etc-row">';
    echo '<div class="card-body">';
    echo '<div class="d-flex justify-content-between align-items-start gap-3">';
    echo '<div class="fw-semibold">비용 행</div>';
    echo '<button type="button" class="btn btn-outline-danger btn-sm js-gsa-remove-row">삭제</button>';
    echo '</div>';
    echo '<div class="row g-2 mt-1">';
    echo '<div class="col-12"><label class="form-label small fw-semibold mb-1">항목</label><select class="form-select" name="' . gsa_form_escape($selectName) . '">';
    gsa_form_render_select_options($lookup, isset($row['etc_type']) ? $row['etc_type'] : '', '- 선택 -');
    echo '</select></div>';
    echo '<div class="col-5"><label class="form-label small fw-semibold mb-1">총액</label><input type="number" step="0.01" min="0" class="form-control js-etc-amount" name="' . gsa_form_escape($amountName) . '" value="' . gsa_form_escape(gsa_form_number_input(isset($row['etc_amt']) ? $row['etc_amt'] : '')) . '"></div>';
    echo '<div class="col-7"><label class="form-label small fw-semibold mb-1">메모</label><input type="text" class="form-control" name="' . gsa_form_escape($memoName) . '" value="' . gsa_form_escape(isset($row['etc_memo']) ? $row['etc_memo'] : '') . '" placeholder="메모"></div>';
    echo '</div>';
    echo '<input type="hidden" name="' . gsa_form_escape($seqName) . '" value="' . gsa_form_escape(isset($row['seq_no']) ? $row['seq_no'] : 0) . '">';
    echo '</div>';
    echo '</div>';
}

function gsa_form_render_input_edit_card($row, $amountName, $countName, $memoName, $seqName)
{
    $label = isset($row['label']) ? $row['label'] : '입금';
    echo '<div class="card border-0 shadow-sm gsa-edit-card js-input-row">';
    echo '<div class="card-body">';
    echo '<div class="d-flex justify-content-between align-items-start gap-3">';
    echo '<div class="fw-semibold">' . gsa_form_escape($label) . '</div>';
    echo '<div class="gsa-amount-pill js-input-total-display">$0.00</div>';
    echo '</div>';
    echo '<div class="row g-2 mt-1">';
    echo '<div class="col-4"><label class="form-label small fw-semibold mb-1">단가</label><input type="number" step="0.01" min="0" class="form-control js-input-amount" name="' . gsa_form_escape($amountName) . '" value="' . gsa_form_escape(gsa_form_number_input(isset($row['input_amt']) ? $row['input_amt'] : '')) . '"></div>';
    echo '<div class="col-4"><label class="form-label small fw-semibold mb-1">인원</label><input type="number" step="1" min="0" class="form-control js-input-count" name="' . gsa_form_escape($countName) . '" value="' . gsa_form_escape(gsa_form_number_input(isset($row['input_cnt']) ? $row['input_cnt'] : '')) . '"></div>';
    echo '<div class="col-4"><label class="form-label small fw-semibold mb-1">총액</label><input type="number" step="0.01" min="0" class="form-control js-input-total" value="" readonly></div>';
    echo '<div class="col-12"><label class="form-label small fw-semibold mb-1">메모</label><input type="text" class="form-control" name="' . gsa_form_escape($memoName) . '" value="' . gsa_form_escape(isset($row['input_memo']) ? $row['input_memo'] : '') . '" placeholder="메모"></div>';
    echo '</div>';
    echo '<input type="hidden" name="' . gsa_form_escape($seqName) . '" value="' . gsa_form_escape(isset($row['seq_no']) ? $row['seq_no'] : 0) . '">';
    echo '</div>';
    echo '</div>';
}

function gsa_form_blank_input_row()
{
    return array(
        'seq_no' => 0,
        'inputamt_type' => '',
        'input_amt' => '',
        'input_cnt' => '',
        'input_memo' => ''
    );
}

function gsa_form_render_input_row_desktop_style($row, $typeLookup)
{
    echo '<div class="card border-0 shadow-sm gsa-edit-card js-input-row gsa-input-row-card">';
    echo '<div class="card-body">';
    echo '<div class="d-flex justify-content-between align-items-start gap-3">';
    echo '<div class="fw-semibold">납입금 행</div>';
    echo '<div class="d-flex align-items-center gap-2">';
    echo '<div class="gsa-amount-pill js-input-total-display">$0.00</div>';
    echo '<button type="button" class="btn btn-outline-danger btn-sm js-gsa-remove-row">삭제</button>';
    echo '</div>';
    echo '</div>';
    echo '<div class="row g-2 mt-1">';
    echo '<div class="col-12"><label class="form-label small fw-semibold mb-1">구분</label><select class="form-select" name="ipSelect[]">';
    gsa_form_render_select_options($typeLookup, isset($row['inputamt_type']) ? $row['inputamt_type'] : '', '- 선택 -');
    echo '</select></div>';
    echo '<div class="col-4"><label class="form-label small fw-semibold mb-1">납입금</label><input type="number" step="0.01" min="0" class="form-control js-input-amount" name="g_inputamt[]" value="' . gsa_form_escape(gsa_form_number_input(isset($row['input_amt']) ? $row['input_amt'] : '')) . '"></div>';
    echo '<div class="col-4"><label class="form-label small fw-semibold mb-1">인원</label><input type="number" step="1" min="0" class="form-control js-input-count" name="g_inputcnt[]" value="' . gsa_form_escape(gsa_form_number_input(isset($row['input_cnt']) ? $row['input_cnt'] : '')) . '"></div>';
    echo '<div class="col-4"><label class="form-label small fw-semibold mb-1">총액</label><input type="number" step="0.01" min="0" class="form-control js-input-total" name="g_inputuamt[]" value="" readonly></div>';
    echo '<div class="col-12"><label class="form-label small fw-semibold mb-1">메모</label><input type="text" class="form-control" name="g_inputmemo[]" value="' . gsa_form_escape(isset($row['input_memo']) ? $row['input_memo'] : '') . '" placeholder="메모"></div>';
    echo '</div>';
    echo '<input type="hidden" name="g_seqno[]" value="' . gsa_form_escape(isset($row['seq_no']) ? $row['seq_no'] : 0) . '">';
    echo '</div>';
    echo '</div>';
}

$seqNo = isset($_GET['seq_no']) ? (int)$_GET['seq_no'] : 0;
if ($seqNo <= 0 && isset($_GET['number'])) {
    $seqNo = (int)$_GET['number'];
}

if ($seqNo <= 0) {
    echo "<script>alert('잘못된 접근입니다.'); location.href='list.php';</script>";
    exit;
}

$masterSql = "SELECT a.*, ml.kor_name AS kr_name, pm.base_rate
              FROM tour_guide a
              LEFT JOIN member_list ml
                ON ml.userid = a.guide_id
               AND ml.division = 'guide'
              LEFT JOIN product_master pm
                ON pm.p_code = a.p_code
              WHERE a.seq_no = ?
              LIMIT 1";
$masterRow = gsa_db_fetch_one($masterSql, 'i', array($seqNo));

if (!$masterRow) {
    echo "<script>alert('데이터를 찾을 수 없습니다.'); location.href='list.php';</script>";
    exit;
}

if ($gsaRole === 'guide') {
    $currentUserId = isset($gsaUser['userid']) ? (string)$gsaUser['userid'] : '';
    $rowGuideId = isset($masterRow['guide_id']) ? (string)$masterRow['guide_id'] : '';
    if ($currentUserId === '' || $rowGuideId !== $currentUserId) {
        echo "<script>alert('본인 정산만 확인할 수 있습니다.'); location.href='list.php';</script>";
        exit;
    }
}

$saved = isset($_GET['saved']) && $_GET['saved'] === '1';
$editUrl = 'form.php?seq_no=' . urlencode((string)$seqNo) . '&edit=1';

$guideInfo = getGuideCode($masterRow['grand_eCode'], $masterRow['sub_eCode']);
$settleCode = '';
if (is_array($guideInfo) && isset($guideInfo['settle_code'])) {
    $settleCode = trim((string)$guideInfo['settle_code']);
}

$statusInfo = gsa_form_status_badge(getGuideStatus($masterRow['grand_eCode'], $masterRow['sub_eCode'], $masterRow['stDate']));
$period = gsa_form_period_safe($masterRow['p_code'], $masterRow['stDate']);
$reserveInfo = getReserveInfoCnt($masterRow['p_code'], $masterRow['stDate']);
$reserveCount = is_array($reserveInfo) && isset($reserveInfo['cnt']) ? (int)$reserveInfo['cnt'] : 0;

$mainPcnt = getGuideMainPcnt($masterRow['p_code'], $masterRow['stDate']);
$subPcnt = getGuideSubPcnt($masterRow['p_code'], $masterRow['stDate']);
$mainCount = is_array($mainPcnt) && isset($mainPcnt['p_cnt']) ? (int)$mainPcnt['p_cnt'] : 0;
$subCount = is_array($subPcnt) && isset($subPcnt['p_cnt']) ? (int)$subPcnt['p_cnt'] : 0;
$totalCount = $mainCount + $subCount;
if ($totalCount <= 0) {
    $totalCount = $reserveCount;
}

$guideName = isset($masterRow['kr_name']) ? trim((string)$masterRow['kr_name']) : '';
if ($guideName === '' && isset($masterRow['guide_id'])) {
    $guideMember = getinfo_dbMember($masterRow['guide_id']);
    if (is_array($guideMember) && isset($guideMember['kor_name'])) {
        $guideName = (string)$guideMember['kor_name'];
    }
}

$setMasterRow = array();
$mealRows = array();
$admissionRows = array();
$optionRows = array();
$etcRows = array();
$shoppingRows = array();
$inputRows = array();

if ($settleCode !== '') {
    $setMasterRow = gsa_db_fetch_one("SELECT * FROM guide_setmaster WHERE settle_code = ? LIMIT 1", 's', array($settleCode));
    $mealRows = gsa_db_fetch_all("SELECT * FROM guide_meal WHERE settle_code = ? ORDER BY meal_type, seq_no", 's', array($settleCode));
    $admissionRows = gsa_db_fetch_all("SELECT * FROM guide_admission WHERE settle_code = ? ORDER BY seq_no", 's', array($settleCode));
    $optionRows = gsa_db_fetch_all("SELECT * FROM guide_option WHERE settle_code = ? ORDER BY seq_no", 's', array($settleCode));
    $etcRows = gsa_db_fetch_all("SELECT * FROM guide_etcamt WHERE settle_code = ? ORDER BY etc_pricety, seq_no", 's', array($settleCode));
    $shoppingRows = gsa_db_fetch_all("SELECT * FROM guide_shopping WHERE settle_code = ? ORDER BY seq_no", 's', array($settleCode));
    $inputRows = gsa_db_fetch_all("SELECT * FROM guide_inputamt WHERE settle_code = ? ORDER BY seq_no", 's', array($settleCode));
}

$mealGroups = gsa_form_group_meals($mealRows);
$etcGroups = gsa_form_group_etc_rows($etcRows);
$fixedInputRows = gsa_form_build_input_rows($inputRows);

$admissionLookup = gsa_form_fetch_code_lookup('G01');
$optionLookup = gsa_form_fetch_code_lookup('G02');
$etcLookup = gsa_form_fetch_code_lookup('G03');
$shoppingLookup = gsa_form_fetch_code_lookup('G04');
$inputTypeLookup = gsa_form_fetch_code_lookup('G05');
if (!isset($inputTypeLookup['adult'])) {
    $inputTypeLookup['adult'] = '성인';
}
if (!isset($inputTypeLookup['child'])) {
    $inputTypeLookup['child'] = '아동';
}
if (!isset($inputTypeLookup['etc'])) {
    $inputTypeLookup['etc'] = '기타';
}
foreach ($inputRows as $inputIndex => $inputRow) {
    $inputType = isset($inputRow['inputamt_type']) ? (string)$inputRow['inputamt_type'] : '';
    $inputRows[$inputIndex]['label'] = isset($inputTypeLookup[$inputType]) ? $inputTypeLookup[$inputType] : ($inputType !== '' ? $inputType : '납입금');
}
$ratioOptions = array(
    '55' => '5:5',
    '64' => '6:4',
    '73' => '7:3'
);

$mealSum = 0.0;
$mealGroupSums = array(
    'bf' => 0.0,
    'lunch' => 0.0,
    'dinner' => 0.0
);
foreach ($mealGroups as $mealType => $rows) {
    foreach ($rows as $row) {
        $lineTotal = gsa_form_calc_total(
            isset($row['meal_pricetotal']) ? $row['meal_pricetotal'] : 0,
            isset($row['meal_cnt']) ? $row['meal_cnt'] : 0,
            isset($row['meal_price']) ? $row['meal_price'] : 0
        );
        $mealGroupSums[$mealType] += $lineTotal;
        $mealSum += $lineTotal;
    }
}

$admissionSum = 0.0;
foreach ($admissionRows as $row) {
    $admissionSum += gsa_form_calc_total(
        isset($row['e_pricetot']) ? $row['e_pricetot'] : 0,
        isset($row['e_cnt']) ? $row['e_cnt'] : 0,
        isset($row['e_price']) ? $row['e_price'] : 0
    );
}

$optionCostSum = 0.0;
$optionSalesSum = 0.0;
$optionCompanyProfitSum = 0.0;
$optionGuideProfitSum = 0.0;
foreach ($optionRows as $row) {
    $optionCostSum += (float)(isset($row['o_pricetot']) ? $row['o_pricetot'] : 0);
    $optionSalesSum += (float)(isset($row['o_cpricetot']) ? $row['o_cpricetot'] : 0);
    $optionCompanyProfitSum += (float)(isset($row['o_cprofit']) ? $row['o_cprofit'] : 0);
    $optionGuideProfitSum += (float)(isset($row['o_gprofit']) ? $row['o_gprofit'] : 0);
}

$etcSums = array(
    'guide' => 0.0,
    'car' => 0.0,
    'etc' => 0.0
);
$etcSum = 0.0;
foreach ($etcGroups as $groupKey => $rows) {
    foreach ($rows as $row) {
        $amount = (float)(isset($row['etc_amt']) ? $row['etc_amt'] : 0);
        $etcSums[$groupKey] += $amount;
        $etcSum += $amount;
    }
}

$inputSum = 0.0;
foreach ($inputRows as $row) {
    $inputSum += (float)(isset($row['input_amt']) ? $row['input_amt'] : 0) * (int)(isset($row['input_cnt']) ? $row['input_cnt'] : 0);
}

$shoppingTotal = 0.0;
$shoppingHomeCom = 0.0;
$shoppingCompanyProfit = 0.0;
$shoppingGuideProfit = 0.0;
foreach ($shoppingRows as $row) {
    $shoppingTotal += (float)(isset($row['tot_amt']) ? $row['tot_amt'] : 0);
    $shoppingHomeCom += (float)(isset($row['home_comamt']) ? $row['home_comamt'] : 0);
    $shoppingCompanyProfit += (float)(isset($row['c_profit']) ? $row['c_profit'] : 0);
    $shoppingGuideProfit += (float)(isset($row['g_profit']) ? $row['g_profit'] : 0);
}

$preAmount = 0.0;
if (is_array($setMasterRow) && isset($setMasterRow['guide_etcamt']) && (float)$setMasterRow['guide_etcamt'] != 0) {
    $preAmount = (float)$setMasterRow['guide_etcamt'];
} elseif (isset($masterRow['pre_amt']) && (float)$masterRow['pre_amt'] != 0) {
    $preAmount = (float)$masterRow['pre_amt'];
}
$totalDeposit = $preAmount + $optionCompanyProfitSum + $inputSum;
$totalPay = $mealSum + $admissionSum + $etcSum + ($shoppingHomeCom + $shoppingGuideProfit);
$settleAmount = $totalDeposit - $totalPay;
$guideMemo = isset($setMasterRow['guide_memo']) ? trim((string)$setMasterRow['guide_memo']) : '';
$permissionRow = $masterRow;
if (is_array($setMasterRow)) {
    $permissionRow = array_merge($permissionRow, $setMasterRow);
}
$currentRegStatus = isset($permissionRow['reg_status']) ? trim((string)$permissionRow['reg_status']) : '';
$canEdit = gsa_can($gsaRole, 'edit', $permissionRow);
$isEdit = $canEdit && isset($_GET['edit']) && $_GET['edit'] === '1';

if ($isEdit) {
    $mealGroups['bf'] = gsa_form_prepare_rows_for_edit($mealGroups['bf'], gsa_form_blank_meal_row('bf', $masterRow['stDate']));
    $mealGroups['lunch'] = gsa_form_prepare_rows_for_edit($mealGroups['lunch'], gsa_form_blank_meal_row('lunch', $masterRow['stDate']));
    $mealGroups['dinner'] = gsa_form_prepare_rows_for_edit($mealGroups['dinner'], gsa_form_blank_meal_row('dinner', $masterRow['stDate']));
    $admissionRows = gsa_form_prepare_rows_for_edit($admissionRows, gsa_form_blank_admission_row());
    $optionRows = gsa_form_prepare_rows_for_edit($optionRows, gsa_form_blank_option_row());
    $etcGroups['guide'] = gsa_form_prepare_rows_for_edit($etcGroups['guide'], gsa_form_blank_etc_row('guide'));
    $etcGroups['car'] = gsa_form_prepare_rows_for_edit($etcGroups['car'], gsa_form_blank_etc_row('car'));
    $etcGroups['etc'] = gsa_form_prepare_rows_for_edit($etcGroups['etc'], gsa_form_blank_etc_row('etc'));
    $inputRows = gsa_form_prepare_rows_for_edit($inputRows, gsa_form_blank_input_row());
}

$canSubmit = !$isEdit && $settleCode !== '' && $currentRegStatus !== 'COMPLETE' && gsa_can($gsaRole, 'submit', $permissionRow);
$canRegisterCancel = !$isEdit && $settleCode !== '' && gsa_can($gsaRole, 'register_cancel', $permissionRow);
$canSubmitCancel = !$isEdit && $settleCode !== '' && $currentRegStatus === 'COMPLETE' && gsa_can($gsaRole, 'submit_cancel', $permissionRow);
$canFinanceOk = !$isEdit && $settleCode !== '' && gsa_can($gsaRole, 'finance_ok', $permissionRow) && (!isset($setMasterRow['finance_st']) || trim((string)$setMasterRow['finance_st']) !== 'V');
$canCeoOk = !$isEdit && $settleCode !== '' && gsa_can($gsaRole, 'ceo_ok', $permissionRow) && (!isset($setMasterRow['ceo_st']) || trim((string)$setMasterRow['ceo_st']) !== 'V');
$canCheckOut = !$isEdit && gsa_can($gsaRole, 'check_out', $permissionRow) && (!isset($masterRow['check_out']) || trim((string)$masterRow['check_out']) !== 'V');

gsa_layout_head($isEdit ? '가이드정산 수정' : '가이드정산 상세');
?>
<form id="gsaDetailForm" method="post" action="save.php" data-edit-mode="<?php echo $isEdit ? '1' : '0'; ?>" data-pre-amount="<?php echo gsa_form_escape(gsa_form_number_input($preAmount)); ?>">
    <input type="hidden" name="mode" value="save">
    <input type="hidden" name="seq_no" value="<?php echo gsa_form_escape($seqNo); ?>">
    <input type="hidden" name="pcode" value="<?php echo gsa_form_escape($masterRow['p_code']); ?>">
    <input type="hidden" name="stDate" value="<?php echo gsa_form_escape($masterRow['stDate']); ?>">
    <input type="hidden" name="grand_eCode" value="<?php echo gsa_form_escape($masterRow['grand_eCode']); ?>">
    <input type="hidden" name="sub_eCode" value="<?php echo gsa_form_escape($masterRow['sub_eCode']); ?>">
    <input type="hidden" name="settle_code" value="<?php echo gsa_form_escape($settleCode); ?>">
    <input type="hidden" name="guideEtcDepAmount" value="<?php echo gsa_form_escape(gsa_form_number_input($preAmount)); ?>">

    <div class="gsa-hidden-shopping d-none">
        <?php foreach ($shoppingRows as $row) { ?>
        <div class="js-shopping-row">
            <input type="hidden" name="shoppingSelect[]" value="<?php echo gsa_form_escape(isset($row['shop_code']) ? $row['shop_code'] : ''); ?>">
            <input type="hidden" name="saleTotalAmount[]" class="js-shopping-sale-total" value="<?php echo gsa_form_escape(gsa_form_number_input(isset($row['tot_amt']) ? $row['tot_amt'] : '')); ?>">
            <input type="hidden" name="homeshoppingcom[]" class="js-shopping-home-com" value="<?php echo gsa_form_escape(gsa_form_number_input(isset($row['home_comamt']) ? $row['home_comamt'] : '')); ?>">
            <input type="hidden" name="companyProfit[]" class="js-shopping-company-profit" value="<?php echo gsa_form_escape(gsa_form_number_input(isset($row['c_profit']) ? $row['c_profit'] : '')); ?>">
            <input type="hidden" name="shoppingGuideProfit[]" class="js-shopping-guide-profit" value="<?php echo gsa_form_escape(gsa_form_number_input(isset($row['g_profit']) ? $row['g_profit'] : '')); ?>">
            <input type="hidden" name="shopp_seq[]" value="<?php echo gsa_form_escape(isset($row['seq_no']) ? $row['seq_no'] : 0); ?>">
        </div>
        <?php } ?>
    </div>

    <div class="gsa-card-stack pb-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <div class="small text-muted">정산 상태</div>
                        <div class="mt-1">
                            <span class="badge <?php echo gsa_form_escape($statusInfo['class']); ?>"><?php echo gsa_form_escape($statusInfo['label']); ?></span>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <?php if ($isEdit) { ?>
                        <a href="form.php?seq_no=<?php echo gsa_form_escape($seqNo); ?>" class="btn btn-outline-secondary">취소</a>
                        <?php } elseif ($canEdit) { ?>
                        <a href="form.php?seq_no=<?php echo gsa_form_escape($seqNo); ?>&edit=1" class="btn btn-primary">수정</a>
                        <?php } elseif ($gsaRole === 'guide' && $currentRegStatus === 'COMPLETE') { ?>
                        <button type="button" class="btn btn-outline-secondary" disabled>제출 완료</button>
                        <?php } ?>
                    </div>
                </div>

                <?php if ($saved) { ?>
                <div class="alert alert-success mt-3 mb-0">저장되었습니다.</div>
                <?php } ?>

                <?php if ($settleCode === '') { ?>
                <div class="alert alert-warning mt-3 mb-0">아직 정산코드가 등록되지 않아 첫 저장 시 신규 정산코드가 생성됩니다.</div>
                <?php } ?>

                <div class="gsa-summary-grid mt-3">
                    <div class="gsa-summary-tile">
                        <div class="small text-muted">정산코드</div>
                        <div class="fw-semibold"><?php echo gsa_form_escape(gsa_form_label_or_dash($settleCode)); ?></div>
                    </div>
                    <div class="gsa-summary-tile">
                        <div class="small text-muted">행사코드</div>
                        <div class="fw-semibold"><?php echo gsa_form_escape($masterRow['sub_eCode']); ?></div>
                        <div class="small text-muted mt-1">Grand <?php echo gsa_form_escape($masterRow['grand_eCode']); ?></div>
                    </div>
                    <div class="gsa-summary-tile">
                        <div class="small text-muted">행사일</div>
                        <div class="fw-semibold"><?php echo gsa_form_escape($masterRow['stDate']); ?></div>
                    </div>
                    <div class="gsa-summary-tile">
                        <div class="small text-muted">행사기간</div>
                        <div class="fw-semibold"><?php echo gsa_form_escape($period); ?></div>
                    </div>
                    <div class="gsa-summary-tile">
                        <div class="small text-muted">행사명</div>
                        <div class="fw-semibold"><?php echo gsa_form_escape($masterRow['p_name']); ?></div>
                    </div>
                    <div class="gsa-summary-tile">
                        <div class="small text-muted">인원</div>
                        <div class="fw-semibold"><?php echo gsa_form_escape(gsa_form_number($totalCount)); ?>명</div>
                        <div class="small text-muted mt-1">본행사 <?php echo gsa_form_escape($mainCount); ?> / 복합 <?php echo gsa_form_escape($subCount); ?></div>
                    </div>
                    <div class="gsa-summary-tile">
                        <div class="small text-muted">가이드명</div>
                        <div class="fw-semibold"><?php echo gsa_form_escape(gsa_form_label_or_dash($guideName)); ?></div>
                    </div>
                    <div class="gsa-summary-tile">
                        <div class="small text-muted">기준요율</div>
                        <div class="fw-semibold"><?php echo gsa_form_escape(gsa_form_label_or_dash($masterRow['base_rate'])); ?></div>
                    </div>
                </div>

                <div class="card gsa-calc-card mt-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center gap-3">
                            <div>
                                <div class="small text-muted">정산 계산 요약</div>
                                <div class="fs-4 fw-bold" id="gsaCalcTotal"><?php echo gsa_form_escape(gsa_form_money($settleAmount)); ?></div>
                            </div>
                            <div class="small text-muted text-end">선지급 + 옵션회사수익 + 납입금 - 식대/입장비/기타/쇼핑</div>
                        </div>
                        <div class="gsa-inline-grid mt-3">
                            <div class="gsa-inline-metric"><span class="text-muted">선지급행사비</span><strong id="gsaMetricPreAmount"><?php echo gsa_form_escape(gsa_form_money($preAmount)); ?></strong></div>
                            <div class="gsa-inline-metric"><span class="text-muted">옵션회사수익</span><strong id="gsaMetricOptionCompany"><?php echo gsa_form_escape(gsa_form_money($optionCompanyProfitSum)); ?></strong></div>
                            <div class="gsa-inline-metric"><span class="text-muted">납입금합계</span><strong id="gsaMetricInputSum"><?php echo gsa_form_escape(gsa_form_money($inputSum)); ?></strong></div>
                            <div class="gsa-inline-metric"><span class="text-muted">지출합계</span><strong id="gsaMetricTotalPay"><?php echo gsa_form_escape(gsa_form_money($totalPay)); ?></strong></div>
                            <div class="gsa-inline-metric"><span class="text-muted">쇼핑반영</span><strong id="gsaMetricShopping"><?php echo gsa_form_escape(gsa_form_money($shoppingHomeCom + $shoppingGuideProfit)); ?></strong></div>
                        </div>
                        <?php if ($guideMemo !== '') { ?>
                        <div class="gsa-memo-box mt-3">
                            <div class="small text-muted">가이드 메모</div>
                            <div class="text-break"><?php echo gsa_form_escape($guideMemo); ?></div>
                        </div>
                        <?php } ?>
                    </div>
                </div>

                <?php if ($canSubmit || $canRegisterCancel || $canSubmitCancel || $canFinanceOk || $canCeoOk || $canCheckOut || ($settleCode !== '' && gsa_can($gsaRole, 'check_save', $masterRow))) { ?>
                <div class="card border-0 shadow-sm mt-3">
                    <div class="card-body">
                        <div class="small text-muted mb-2">워크플로 액션</div>
                        <div class="d-flex flex-wrap gap-2">
                            <?php if ($canSubmit) { ?>
                            <button type="button" class="btn btn-success js-gsa-action" data-action="submit" data-settle-code="<?php echo gsa_form_escape($settleCode); ?>">정산 제출</button>
                            <?php } ?>
                            <?php if ($canRegisterCancel) { ?>
                            <button type="button" class="btn btn-outline-danger js-gsa-action" data-action="register_cancel" data-settle-code="<?php echo gsa_form_escape($settleCode); ?>">등록 취소</button>
                            <?php } ?>
                            <?php if ($canSubmitCancel) { ?>
                            <button type="button" class="btn btn-outline-secondary js-gsa-action" data-action="submit_cancel" data-settle-code="<?php echo gsa_form_escape($settleCode); ?>">제출 취소</button>
                            <?php } ?>
                            <?php if ($canFinanceOk) { ?>
                            <button type="button" class="btn btn-primary js-gsa-action" data-action="finance_ok" data-settle-code="<?php echo gsa_form_escape($settleCode); ?>">회계확인</button>
                            <?php } ?>
                            <?php if ($canCeoOk) { ?>
                            <button type="button" class="btn btn-dark js-gsa-action" data-action="ceo_ok" data-settle-code="<?php echo gsa_form_escape($settleCode); ?>">대표확인</button>
                            <?php } ?>
                            <?php if ($canCheckOut) { ?>
                            <button type="button" class="btn btn-warning js-gsa-action" data-action="check_out" data-seq-no="<?php echo gsa_form_escape($seqNo); ?>">체크나감</button>
                            <?php } ?>
                            <?php if ($settleCode !== '' && gsa_can($gsaRole, 'check_save', $masterRow)) { ?>
                            <a href="check.php?settle_code=<?php echo urlencode($settleCode); ?>" class="btn btn-outline-primary">체크/메모</a>
                            <?php } ?>
                        </div>
                    </div>
                </div>
                <?php } ?>
            </div>
        </div>

        <div class="accordion" id="gsaDetailAccordion">
            <div class="accordion-item border-0 shadow-sm">
                <h2 class="accordion-header" id="headingMeal">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMeal" aria-expanded="false" aria-controls="collapseMeal">
                        <span>식대</span>
                        <span class="ms-auto me-3 fw-semibold" id="gsaSectionMealTotal"><?php echo gsa_form_escape(gsa_form_money($mealSum)); ?></span>
                    </button>
                </h2>
                <div id="collapseMeal" class="accordion-collapse collapse" aria-labelledby="headingMeal" data-bs-parent="#gsaDetailAccordion">
                    <div class="accordion-body">
                        <?php
                        $mealLabels = array(
                            'bf' => '조식',
                            'lunch' => '중식',
                            'dinner' => '석식'
                        );
                        foreach ($mealLabels as $mealKey => $mealLabel) {
                        ?>
                        <section class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0"><?php echo gsa_form_escape($mealLabel); ?></h6>
                                <span class="small text-muted" id="gsaMealTotal_<?php echo gsa_form_escape($mealKey); ?>"><?php echo gsa_form_escape(gsa_form_money($mealGroupSums[$mealKey])); ?></span>
                            </div>
                            <?php if ($isEdit) { ?>
                            <div class="gsa-card-stack" id="gsaMealList_<?php echo gsa_form_escape($mealKey); ?>">
                                <?php foreach ($mealGroups[$mealKey] as $row) {
                                    gsa_form_render_meal_edit_card($mealKey, $row);
                                } ?>
                            </div>
                            <div class="mt-3">
                                <button type="button" class="btn btn-outline-primary w-100 js-gsa-add-row" data-target="#gsaMealList_<?php echo gsa_form_escape($mealKey); ?>" data-template="#tpl-meal-<?php echo gsa_form_escape($mealKey); ?>">+ 행 추가</button>
                            </div>
                            <?php } else { ?>
                                <?php if (empty($mealGroups[$mealKey])) { ?>
                                    <?php
                                    if ($canEdit) {
                                        gsa_form_render_empty_with_action($mealLabel . ' 데이터가 없습니다.', $editUrl, $mealLabel . ' 입력하기');
                                    } else {
                                        gsa_form_render_empty($mealLabel . ' 데이터가 없습니다.');
                                    }
                                    ?>
                                <?php } else { ?>
                                    <div class="gsa-card-stack">
                                        <?php foreach ($mealGroups[$mealKey] as $row) {
                                            $lineTotal = gsa_form_calc_total(
                                                isset($row['meal_pricetotal']) ? $row['meal_pricetotal'] : 0,
                                                isset($row['meal_cnt']) ? $row['meal_cnt'] : 0,
                                                isset($row['meal_price']) ? $row['meal_price'] : 0
                                            );
                                            gsa_form_render_read_card(
                                                isset($row['meal_rest']) ? $row['meal_rest'] : '',
                                                $lineTotal,
                                                array(
                                                    '인원' => gsa_form_number(isset($row['meal_cnt']) ? $row['meal_cnt'] : 0) . '명',
                                                    '단가' => gsa_form_money(isset($row['meal_price']) ? $row['meal_price'] : 0)
                                                ),
                                                isset($row['meal_date']) ? $row['meal_date'] : '',
                                                ''
                                            );
                                        } ?>
                                    </div>
                                <?php } ?>
                            <?php } ?>
                        </section>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <div class="accordion-item border-0 shadow-sm">
                <h2 class="accordion-header" id="headingAdmission">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAdmission" aria-expanded="false" aria-controls="collapseAdmission">
                        <span>입장비</span>
                        <span class="ms-auto me-3 fw-semibold" id="gsaSectionAdmissionTotal"><?php echo gsa_form_escape(gsa_form_money($admissionSum)); ?></span>
                    </button>
                </h2>
                <div id="collapseAdmission" class="accordion-collapse collapse" aria-labelledby="headingAdmission" data-bs-parent="#gsaDetailAccordion">
                    <div class="accordion-body">
                        <?php if ($isEdit) { ?>
                        <div class="gsa-card-stack" id="gsaAdmissionList">
                            <?php foreach ($admissionRows as $row) {
                                gsa_form_render_admission_edit_card($row, $admissionLookup);
                            } ?>
                        </div>
                        <div class="mt-3">
                            <button type="button" class="btn btn-outline-primary w-100 js-gsa-add-row" data-target="#gsaAdmissionList" data-template="#tpl-admission">+ 행 추가</button>
                        </div>
                        <?php } else { ?>
                            <?php if (empty($admissionRows)) { ?>
                                <?php
                                if ($canEdit) {
                                    gsa_form_render_empty_with_action('입장비 데이터가 없습니다.', $editUrl, '입장비 입력하기');
                                } else {
                                    gsa_form_render_empty('입장비 데이터가 없습니다.');
                                }
                                ?>
                            <?php } else { ?>
                                <div class="gsa-card-stack">
                                    <?php foreach ($admissionRows as $row) {
                                        $lineTotal = gsa_form_calc_total(
                                            isset($row['e_pricetot']) ? $row['e_pricetot'] : 0,
                                            isset($row['e_cnt']) ? $row['e_cnt'] : 0,
                                            isset($row['e_price']) ? $row['e_price'] : 0
                                        );
                                        gsa_form_render_read_card(
                                            gsa_form_lookup_label($admissionLookup, isset($row['admission_code']) ? $row['admission_code'] : ''),
                                            $lineTotal,
                                            array(
                                                '인원' => gsa_form_number(isset($row['e_cnt']) ? $row['e_cnt'] : 0) . '명',
                                                '단가' => gsa_form_money(isset($row['e_price']) ? $row['e_price'] : 0)
                                            ),
                                            isset($row['admission_code']) ? $row['admission_code'] : '',
                                            ''
                                        );
                                    } ?>
                                </div>
                            <?php } ?>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <div class="accordion-item border-0 shadow-sm">
                <h2 class="accordion-header" id="headingOption">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOption" aria-expanded="false" aria-controls="collapseOption">
                        <span>옵션</span>
                        <span class="ms-auto me-3 fw-semibold" id="gsaSectionOptionTotal"><?php echo gsa_form_escape(gsa_form_money($optionSalesSum)); ?></span>
                    </button>
                </h2>
                <div id="collapseOption" class="accordion-collapse collapse" aria-labelledby="headingOption" data-bs-parent="#gsaDetailAccordion">
                    <div class="accordion-body">
                        <div class="gsa-inline-grid mb-3">
                            <?php
                            gsa_form_render_info_lines(array(
                                '원가합계' => gsa_form_money($optionCostSum),
                                '판매합계' => gsa_form_money($optionSalesSum),
                                '회사수익' => gsa_form_money($optionCompanyProfitSum),
                                '가이드수익' => gsa_form_money($optionGuideProfitSum)
                            ));
                            ?>
                        </div>
                        <?php if ($isEdit) { ?>
                        <div class="gsa-card-stack" id="gsaOptionList">
                            <?php foreach ($optionRows as $row) {
                                gsa_form_render_option_edit_card($row, $optionLookup, $ratioOptions);
                            } ?>
                        </div>
                        <div class="mt-3">
                            <button type="button" class="btn btn-outline-primary w-100 js-gsa-add-row" data-target="#gsaOptionList" data-template="#tpl-option">+ 행 추가</button>
                        </div>
                        <?php } else { ?>
                            <?php if (empty($optionRows)) { ?>
                                <?php
                                if ($canEdit) {
                                    gsa_form_render_empty_with_action('옵션 데이터가 없습니다.', $editUrl, '옵션 입력하기');
                                } else {
                                    gsa_form_render_empty('옵션 데이터가 없습니다.');
                                }
                                ?>
                            <?php } else { ?>
                                <div class="gsa-card-stack">
                                    <?php foreach ($optionRows as $row) {
                                        gsa_form_render_read_card(
                                            gsa_form_lookup_label($optionLookup, isset($row['option_code']) ? $row['option_code'] : ''),
                                            isset($row['o_cpricetot']) ? $row['o_cpricetot'] : 0,
                                            array(
                                                '인원' => gsa_form_number(isset($row['o_cnt']) ? $row['o_cnt'] : 0) . '명',
                                                '원가/P' => gsa_form_money(isset($row['o_price']) ? $row['o_price'] : 0),
                                                '옵션가/P' => gsa_form_money(isset($row['o_cprice']) ? $row['o_cprice'] : 0),
                                                '회사수익' => gsa_form_money(isset($row['o_cprofit']) ? $row['o_cprofit'] : 0)
                                            ),
                                            isset($row['base_set']) ? $row['base_set'] : '',
                                            '가이드수익 ' . gsa_form_money(isset($row['o_gprofit']) ? $row['o_gprofit'] : 0)
                                        );
                                    } ?>
                                </div>
                            <?php } ?>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <div class="accordion-item border-0 shadow-sm">
                <h2 class="accordion-header" id="headingEtc">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEtc" aria-expanded="false" aria-controls="collapseEtc">
                        <span>기타비용</span>
                        <span class="ms-auto me-3 fw-semibold" id="gsaSectionEtcTotal"><?php echo gsa_form_escape(gsa_form_money($etcSum)); ?></span>
                    </button>
                </h2>
                <div id="collapseEtc" class="accordion-collapse collapse" aria-labelledby="headingEtc" data-bs-parent="#gsaDetailAccordion">
                    <div class="accordion-body">
                        <?php
                        $etcLabels = array(
                            'guide' => '가이드',
                            'car' => '차량',
                            'etc' => '기타'
                        );
                        foreach ($etcLabels as $etcKey => $etcLabel) {
                        ?>
                        <section class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0"><?php echo gsa_form_escape($etcLabel); ?></h6>
                                <span class="small text-muted" id="gsaEtcTotal_<?php echo gsa_form_escape($etcKey); ?>"><?php echo gsa_form_escape(gsa_form_money($etcSums[$etcKey])); ?></span>
                            </div>
                            <?php if ($isEdit) { ?>
                            <div class="gsa-card-stack" id="gsaEtcList_<?php echo gsa_form_escape($etcKey); ?>">
                                <?php foreach ($etcGroups[$etcKey] as $row) {
                                    gsa_form_render_etc_edit_card($row, $etcLookup, $etcKey);
                                } ?>
                            </div>
                            <div class="mt-3">
                                <button type="button" class="btn btn-outline-primary w-100 js-gsa-add-row" data-target="#gsaEtcList_<?php echo gsa_form_escape($etcKey); ?>" data-template="#tpl-etc-<?php echo gsa_form_escape($etcKey); ?>">+ 행 추가</button>
                            </div>
                            <?php } else { ?>
                                <?php if (empty($etcGroups[$etcKey])) { ?>
                                    <?php
                                    if ($canEdit) {
                                        gsa_form_render_empty_with_action($etcLabel . ' 비용 데이터가 없습니다.', $editUrl, $etcLabel . ' 입력하기');
                                    } else {
                                        gsa_form_render_empty($etcLabel . ' 비용 데이터가 없습니다.');
                                    }
                                    ?>
                                <?php } else { ?>
                                    <div class="gsa-card-stack">
                                        <?php foreach ($etcGroups[$etcKey] as $row) {
                                            gsa_form_render_read_card(
                                                gsa_form_lookup_label($etcLookup, isset($row['etc_type']) ? $row['etc_type'] : ''),
                                                isset($row['etc_amt']) ? $row['etc_amt'] : 0,
                                                array(),
                                                '',
                                                isset($row['etc_memo']) ? $row['etc_memo'] : ''
                                            );
                                        } ?>
                                    </div>
                                <?php } ?>
                            <?php } ?>
                        </section>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <div class="accordion-item border-0 shadow-sm">
                <h2 class="accordion-header" id="headingInput">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseInput" aria-expanded="false" aria-controls="collapseInput">
                        <span>입금액</span>
                        <span class="ms-auto me-3 fw-semibold" id="gsaSectionInputTotal"><?php echo gsa_form_escape(gsa_form_money($inputSum)); ?></span>
                    </button>
                </h2>
                <div id="collapseInput" class="accordion-collapse collapse" aria-labelledby="headingInput" data-bs-parent="#gsaDetailAccordion">
                    <div class="accordion-body">
                        <?php if ($isEdit) { ?>
                        <div class="gsa-card-stack" id="gsaInputList">
                            <?php foreach ($inputRows as $row) {
                                gsa_form_render_input_row_desktop_style($row, $inputTypeLookup);
                            } ?>
                        </div>
                        <div class="mt-3">
                            <button type="button" class="btn btn-outline-primary w-100 js-gsa-add-row" data-target="#gsaInputList" data-template="#tpl-input">+ 행 추가</button>
                        </div>
                        <?php } else { ?>
                        <div class="gsa-card-stack">
                            <?php foreach ($inputRows as $row) {
                                $lineTotal = (float)(isset($row['input_amt']) ? $row['input_amt'] : 0) * (int)(isset($row['input_cnt']) ? $row['input_cnt'] : 0);
                                gsa_form_render_read_card(
                                    isset($row['label']) ? $row['label'] : '입금',
                                    $lineTotal,
                                    array(
                                        '인원' => gsa_form_number(isset($row['input_cnt']) ? $row['input_cnt'] : 0) . '명',
                                        '단가' => gsa_form_money(isset($row['input_amt']) ? $row['input_amt'] : 0)
                                    ),
                                    isset($row['inputamt_type']) ? $row['inputamt_type'] : '',
                                    isset($row['input_memo']) ? $row['input_memo'] : ''
                                );
                            } ?>
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="gsa-sticky-action shadow-lg">
        <div class="d-flex justify-content-between align-items-center gap-3">
            <div class="min-w-0">
                <div class="small text-muted">총합계</div>
                <div class="fs-4 fw-bold text-primary" id="gsaStickyTotal"><?php echo gsa_form_escape(gsa_form_money($settleAmount)); ?></div>
            </div>
            <?php if ($isEdit) { ?>
            <button type="submit" class="btn btn-primary btn-lg">저장</button>
            <?php } elseif ($canEdit) { ?>
            <a href="form.php?seq_no=<?php echo gsa_form_escape($seqNo); ?>&edit=1" class="btn btn-primary btn-lg">수정</a>
            <?php } ?>
        </div>
    </div>
</form>

<?php if ($isEdit) { ?>
<template id="tpl-meal-bf"><?php gsa_form_render_meal_edit_card('bf', gsa_form_blank_meal_row('bf', $masterRow['stDate'])); ?></template>
<template id="tpl-meal-lunch"><?php gsa_form_render_meal_edit_card('lunch', gsa_form_blank_meal_row('lunch', $masterRow['stDate'])); ?></template>
<template id="tpl-meal-dinner"><?php gsa_form_render_meal_edit_card('dinner', gsa_form_blank_meal_row('dinner', $masterRow['stDate'])); ?></template>
<template id="tpl-admission"><?php gsa_form_render_admission_edit_card(gsa_form_blank_admission_row(), $admissionLookup); ?></template>
<template id="tpl-option"><?php gsa_form_render_option_edit_card(gsa_form_blank_option_row(), $optionLookup, $ratioOptions); ?></template>
<template id="tpl-etc-guide"><?php gsa_form_render_etc_edit_card(gsa_form_blank_etc_row('guide'), $etcLookup, 'guide'); ?></template>
<template id="tpl-etc-car"><?php gsa_form_render_etc_edit_card(gsa_form_blank_etc_row('car'), $etcLookup, 'car'); ?></template>
<template id="tpl-etc-etc"><?php gsa_form_render_etc_edit_card(gsa_form_blank_etc_row('etc'), $etcLookup, 'etc'); ?></template>
<template id="tpl-input"><?php gsa_form_render_input_row_desktop_style(gsa_form_blank_input_row(), $inputTypeLookup); ?></template>
<?php } ?>
<?php
gsa_layout_foot();
