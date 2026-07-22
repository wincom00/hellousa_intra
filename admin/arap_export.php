<?php
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);

ob_start();
include "include/header.php";
ob_end_clean();

require_once __DIR__ . "/include/arap_common.php";
require_once __DIR__ . "/SimpleXLSXGen.php";

use Shuchkin\SimpleXLSXGen;

if (empty($_COOKIE['MEMLOGIN_ADMIN_HELLO'])) {
    echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
    exit;
}

$exportType = isset($_GET['export_type']) ? trim((string)$_GET['export_type']) : 'list';

if ($division == "") {
    $division = '11';
}
if ($pdx == "") {
    $pdx = '1';
}
if ($sub == "") {
    $sub = $exportType === 'summary' ? '20' : '10';
}

if (!hasMenuAccess($division, $pdx, $sub)) {
    arapRedirect("index.php", "권한이 있는 메뉴가 아닙니다. 확인 후 사용해 주세요.");
}

function arapTypeLabel($txType)
{
    return $txType === 'IN' ? '수입' : '지출';
}

function arapExportFilename($prefix, $suffix = '')
{
    $parts = array($prefix);
    if ($suffix !== '') {
        $parts[] = $suffix;
    }
    $parts[] = date('Ymd_His');

    return implode('_', $parts) . '.xlsx';
}

function arapCleanOutputBuffer()
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
}

function arapLoadPHPExcel()
{
    if (class_exists('PHPExcel') && class_exists('PHPExcel_IOFactory')) {
        return true;
    }

    $basePath = __DIR__ . "/lib/PHPExcel/Classes/";
    $mainPath = $basePath . "PHPExcel.php";
    $ioPath = $basePath . "PHPExcel/IOFactory.php";

    if (!file_exists($mainPath) || !file_exists($ioPath)) {
        return false;
    }

    require_once $mainPath;
    require_once $ioPath;

    return class_exists('PHPExcel') && class_exists('PHPExcel_IOFactory');
}

function arapFetchSummaryData($dbConn, $year)
{
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
            $monthlyMap[(int)$row['month_no']] = array(
                'income_total' => abs((float)$row['income_total']),
                'expense_total' => abs((float)$row['expense_total'])
            );
        }
    }

    $incomeSql = "SELECT c.cat_name, SUM(t.amount) AS amount_total
                  FROM arap_transaction t
                  INNER JOIN arap_category c ON c.cat_id = t.cat_id
                  WHERE t.tx_type = 'IN'
                    AND t.tx_date BETWEEN '" . $dbConn->real_escape_string($startDate) . "' AND '" . $dbConn->real_escape_string($endDate) . "'
                  GROUP BY c.cat_id, c.cat_name
                  ORDER BY amount_total DESC, c.cat_name ASC";
    $incomeResult = $dbConn->query($incomeSql);
    $incomeCategories = array();
    if ($incomeResult) {
        while ($row = $incomeResult->fetch_assoc()) {
            $row['amount_total'] = abs((float)$row['amount_total']);
            $incomeCategories[] = $row;
        }
    }

    $expenseSql = "SELECT c.cat_name, SUM(t.amount) AS amount_total
                   FROM arap_transaction t
                   INNER JOIN arap_category c ON c.cat_id = t.cat_id
                   WHERE t.tx_type = 'OUT'
                     AND t.tx_date BETWEEN '" . $dbConn->real_escape_string($startDate) . "' AND '" . $dbConn->real_escape_string($endDate) . "'
                   GROUP BY c.cat_id, c.cat_name
                   ORDER BY amount_total DESC, c.cat_name ASC";
    $expenseResult = $dbConn->query($expenseSql);
    $expenseCategories = array();
    if ($expenseResult) {
        while ($row = $expenseResult->fetch_assoc()) {
            $row['amount_total'] = -1 * abs((float)$row['amount_total']);
            $expenseCategories[] = $row;
        }
    }

    $monthRows = array();
    $yearIncomeTotal = 0;
    $yearExpenseTotal = 0;
    for ($monthNo = 1; $monthNo <= 12; $monthNo++) {
        $incomeAmount = isset($monthlyMap[$monthNo]) ? (float)$monthlyMap[$monthNo]['income_total'] : 0;
        $expenseAmount = isset($monthlyMap[$monthNo]) ? (float)$monthlyMap[$monthNo]['expense_total'] : 0;
        $balanceAmount = $incomeAmount - $expenseAmount;
        $yearIncomeTotal += $incomeAmount;
        $yearExpenseTotal += $expenseAmount;

        $monthRows[] = array(
            'label' => $monthNo . '월',
            'income_total' => $incomeAmount,
            'expense_total' => -1 * $expenseAmount,
            'balance_total' => $balanceAmount
        );
    }

    return array(
        'year' => $year,
        'year_income_total' => $yearIncomeTotal,
        'year_expense_total' => -1 * $yearExpenseTotal,
        'year_balance_total' => $yearIncomeTotal - $yearExpenseTotal,
        'months' => $monthRows,
        'income_categories' => $incomeCategories,
        'expense_categories' => $expenseCategories
    );
}

function arapApplyBoxBorder($sheet, $range, $borderStyle)
{
    $sheet->getStyle($range)->applyFromArray(array(
        'borders' => array(
            'allborders' => array(
                'style' => $borderStyle,
                'color' => array('rgb' => '2C2C2C')
            )
        )
    ));
}

function arapWriteMergedValue($sheet, $range, $value, $style = array())
{
    $parts = explode(':', $range);
    $sheet->mergeCells($range);
    $sheet->setCellValue($parts[0], $value);
    if (!empty($style)) {
        $sheet->getStyle($range)->applyFromArray($style);
    }
}

function arapStreamSummaryDashboard($summary)
{
    if (!arapLoadPHPExcel()) {
        return false;
    }

    $excel = new PHPExcel();
    $sheet = $excel->setActiveSheetIndex(0);
    $sheet->setTitle('ARAP Summary');
    $sheet->setShowGridLines(false);

    $sheet->getSheetView()->setZoomScale(85);

    $columnWidths = array(
        'A' => 3, 'B' => 14, 'C' => 14, 'D' => 14, 'E' => 14, 'F' => 14,
        'G' => 4, 'H' => 14, 'I' => 14, 'J' => 14, 'K' => 14, 'L' => 14,
        'M' => 4, 'N' => 14, 'O' => 14, 'P' => 14, 'Q' => 14, 'R' => 14
    );
    foreach ($columnWidths as $column => $width) {
        $sheet->getColumnDimension($column)->setWidth($width);
    }

    $sheet->getDefaultRowDimension()->setRowHeight(24);
    $sheet->getRowDimension(2)->setRowHeight(8);
    $sheet->getRowDimension(7)->setRowHeight(10);
    $sheet->getRowDimension(23)->setRowHeight(10);

    $baseFont = array(
        'font' => array(
            'name' => 'Malgun Gothic',
            'size' => 10
        )
    );
    $titleStyle = array(
        'font' => array(
            'name' => 'Malgun Gothic',
            'size' => 11,
            'bold' => true,
            'color' => array('rgb' => '555555')
        ),
        'alignment' => array(
            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
            'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
        )
    );
    $headerStyle = array(
        'font' => array(
            'name' => 'Malgun Gothic',
            'size' => 10,
            'bold' => true
        ),
        'fill' => array(
            'type' => PHPExcel_Style_Fill::FILL_SOLID,
            'color' => array('rgb' => 'E6EEF5')
        ),
        'alignment' => array(
            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
        )
    );
    $sectionStyle = array(
        'font' => array(
            'name' => 'Malgun Gothic',
            'size' => 11,
            'bold' => true
        ),
        'fill' => array(
            'type' => PHPExcel_Style_Fill::FILL_SOLID,
            'color' => array('rgb' => 'F3F3F3')
        ),
        'alignment' => array(
            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
            'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
        )
    );
    $leftBodyStyle = array(
        'font' => array(
            'name' => 'Malgun Gothic',
            'size' => 10,
            'bold' => true
        ),
        'alignment' => array(
            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
            'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
        )
    );
    $rightBodyStyle = array(
        'font' => array(
            'name' => 'Malgun Gothic',
            'size' => 10,
            'bold' => true
        ),
        'alignment' => array(
            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
            'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
        )
    );

    $sheet->getStyle('A1:R40')->applyFromArray($baseFont);

    arapWriteMergedValue($sheet, 'B3:F3', '연간 수입합계', $titleStyle);
    arapWriteMergedValue($sheet, 'B4:F5', $summary['year_income_total'], array(
        'font' => array(
            'name' => 'Malgun Gothic',
            'size' => 24,
            'bold' => true,
            'color' => array('rgb' => '2B8F43')
        ),
        'alignment' => array(
            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
            'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
        )
    ));
    arapApplyBoxBorder($sheet, 'B3:F5', PHPExcel_Style_Border::BORDER_THIN);
    $sheet->getStyle('B4')->getNumberFormat()->setFormatCode('$#,##0.00;-$#,##0.00');

    arapWriteMergedValue($sheet, 'H3:L3', '연간 지출합계', $titleStyle);
    arapWriteMergedValue($sheet, 'H4:L5', $summary['year_expense_total'], array(
        'font' => array(
            'name' => 'Malgun Gothic',
            'size' => 24,
            'bold' => true,
            'color' => array('rgb' => 'D35A4A')
        ),
        'alignment' => array(
            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
            'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
        )
    ));
    arapApplyBoxBorder($sheet, 'H3:L5', PHPExcel_Style_Border::BORDER_THIN);
    $sheet->getStyle('H4')->getNumberFormat()->setFormatCode('$#,##0.00;-$#,##0.00');

    arapWriteMergedValue($sheet, 'N3:R3', '연간 차액', $titleStyle);
    arapWriteMergedValue($sheet, 'N4:R5', $summary['year_balance_total'], array(
        'font' => array(
            'name' => 'Malgun Gothic',
            'size' => 24,
            'bold' => true,
            'color' => array('rgb' => '2E6EA5')
        ),
        'alignment' => array(
            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
            'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
        )
    ));
    arapApplyBoxBorder($sheet, 'N3:R5', PHPExcel_Style_Border::BORDER_THIN);
    $sheet->getStyle('N4')->getNumberFormat()->setFormatCode('$#,##0.00;-$#,##0.00');

    arapWriteMergedValue($sheet, 'B8:R9', '월별 추이', $sectionStyle);
    arapApplyBoxBorder($sheet, 'B8:R22', PHPExcel_Style_Border::BORDER_THIN);

    arapWriteMergedValue($sheet, 'B11:C11', '월', $headerStyle);
    arapWriteMergedValue($sheet, 'D11:G11', '수입합계', $headerStyle);
    arapWriteMergedValue($sheet, 'H11:L11', '지출합계', $headerStyle);
    arapWriteMergedValue($sheet, 'M11:Q11', '차액', $headerStyle);

    $rowNo = 12;
    foreach ($summary['months'] as $monthRow) {
        arapWriteMergedValue($sheet, 'B' . $rowNo . ':C' . $rowNo, $monthRow['label'], $leftBodyStyle);
        arapWriteMergedValue($sheet, 'D' . $rowNo . ':G' . $rowNo, $monthRow['income_total'], $rightBodyStyle);
        arapWriteMergedValue($sheet, 'H' . $rowNo . ':L' . $rowNo, $monthRow['expense_total'], $rightBodyStyle);
        arapWriteMergedValue($sheet, 'M' . $rowNo . ':Q' . $rowNo, $monthRow['balance_total'], $rightBodyStyle);

        $sheet->getStyle('D' . $rowNo)->getNumberFormat()->setFormatCode('$#,##0.00;-$#,##0.00');
        $sheet->getStyle('H' . $rowNo)->getNumberFormat()->setFormatCode('$#,##0.00;-$#,##0.00');
        $sheet->getStyle('M' . $rowNo)->getNumberFormat()->setFormatCode('$#,##0.00;-$#,##0.00');
        $rowNo++;
    }

    arapWriteMergedValue($sheet, 'B25:I26', '수입 분류 비중', $sectionStyle);
    arapApplyBoxBorder($sheet, 'B25:I31', PHPExcel_Style_Border::BORDER_THIN);
    arapWriteMergedValue($sheet, 'B28:C28', '대분류', $headerStyle);
    arapWriteMergedValue($sheet, 'D28:F28', '합계', $headerStyle);
    arapWriteMergedValue($sheet, 'G28:I28', '비율', $headerStyle);

    $incomeRowNo = 29;
    if (count($summary['income_categories']) === 0) {
        arapWriteMergedValue($sheet, 'B29:C29', '데이터 없음', $leftBodyStyle);
        arapWriteMergedValue($sheet, 'D29:F29', 0, $rightBodyStyle);
        arapWriteMergedValue($sheet, 'G29:I29', 0, $rightBodyStyle);
        $sheet->getStyle('D29')->getNumberFormat()->setFormatCode('$#,##0.00;-$#,##0.00');
        $sheet->getStyle('G29')->getNumberFormat()->setFormatCode('0.00%');
    } else {
        foreach ($summary['income_categories'] as $row) {
            $amountTotal = (float)$row['amount_total'];
            $ratio = $summary['year_income_total'] > 0 ? $amountTotal / $summary['year_income_total'] : 0;

            arapWriteMergedValue($sheet, 'B' . $incomeRowNo . ':C' . $incomeRowNo, $row['cat_name'], $leftBodyStyle);
            arapWriteMergedValue($sheet, 'D' . $incomeRowNo . ':F' . $incomeRowNo, $amountTotal, $rightBodyStyle);
            arapWriteMergedValue($sheet, 'G' . $incomeRowNo . ':I' . $incomeRowNo, $ratio, $rightBodyStyle);

            $sheet->getStyle('D' . $incomeRowNo)->getNumberFormat()->setFormatCode('$#,##0.00;-$#,##0.00');
            $sheet->getStyle('G' . $incomeRowNo)->getNumberFormat()->setFormatCode('0.00%');
            $incomeRowNo++;
        }
    }

    arapWriteMergedValue($sheet, 'K25:R26', '지출 분류 비중', $sectionStyle);
    arapApplyBoxBorder($sheet, 'K25:R37', PHPExcel_Style_Border::BORDER_THIN);
    arapWriteMergedValue($sheet, 'K28:M28', '대분류', $headerStyle);
    arapWriteMergedValue($sheet, 'N28:P28', '합계', $headerStyle);
    arapWriteMergedValue($sheet, 'Q28:R28', '비율', $headerStyle);

    $expenseRowNo = 29;
    if (count($summary['expense_categories']) === 0) {
        arapWriteMergedValue($sheet, 'K29:M29', '데이터 없음', $leftBodyStyle);
        arapWriteMergedValue($sheet, 'N29:P29', 0, $rightBodyStyle);
        arapWriteMergedValue($sheet, 'Q29:R29', 0, $rightBodyStyle);
        $sheet->getStyle('N29')->getNumberFormat()->setFormatCode('$#,##0.00;-$#,##0.00');
        $sheet->getStyle('Q29')->getNumberFormat()->setFormatCode('0.00%');
    } else {
        foreach ($summary['expense_categories'] as $row) {
            $amountTotal = (float)$row['amount_total'];
            $ratio = $summary['year_expense_total'] > 0 ? $amountTotal / $summary['year_expense_total'] : 0;

            arapWriteMergedValue($sheet, 'K' . $expenseRowNo . ':M' . $expenseRowNo, $row['cat_name'], $leftBodyStyle);
            arapWriteMergedValue($sheet, 'N' . $expenseRowNo . ':P' . $expenseRowNo, $amountTotal, $rightBodyStyle);
            arapWriteMergedValue($sheet, 'Q' . $expenseRowNo . ':R' . $expenseRowNo, $ratio, $rightBodyStyle);

            $sheet->getStyle('N' . $expenseRowNo)->getNumberFormat()->setFormatCode('$#,##0.00;-$#,##0.00');
            $sheet->getStyle('Q' . $expenseRowNo)->getNumberFormat()->setFormatCode('0.00%');
            $expenseRowNo++;
        }
    }

    arapCleanOutputBuffer();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . arapExportFilename('arap_summary_dashboard', $summary['year']) . '"');
    header('Cache-Control: max-age=0');

    $writer = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
    $writer->setPreCalculateFormulas(false);
    $writer->save('php://output');
    exit;
}

function arapStreamListExport($dbConn)
{
    $month = arapGetMonthValue(isset($_GET['month']) ? $_GET['month'] : '');
    $txType = isset($_GET['tx_type']) ? trim((string)$_GET['tx_type']) : '';
    $filterCatId = isset($_GET['cat_id']) ? (int)$_GET['cat_id'] : 0;
    $filterSubId = isset($_GET['sub_id']) ? (int)$_GET['sub_id'] : 0;
    $filterMethodId = isset($_GET['method_id']) ? (int)$_GET['method_id'] : 0;
    $keyword = isset($_GET['keyword']) ? trim((string)$_GET['keyword']) : '';

    list($startDate, $endDate) = arapGetMonthRange($month);

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

    $categoryLabel = '';
    $subcategoryLabel = '';
    $methodLabel = '';

    if ($filterCatId > 0) {
        $categoryResult = $dbConn->query("SELECT cat_name FROM arap_category WHERE cat_id = " . $filterCatId . " LIMIT 1");
        if ($categoryResult && $categoryRow = $categoryResult->fetch_assoc()) {
            $categoryLabel = $categoryRow['cat_name'];
        }
    }
    if ($filterSubId > 0) {
        $subcategoryResult = $dbConn->query("SELECT sub_name FROM arap_subcategory WHERE sub_id = " . $filterSubId . " LIMIT 1");
        if ($subcategoryResult && $subcategoryRow = $subcategoryResult->fetch_assoc()) {
            $subcategoryLabel = $subcategoryRow['sub_name'];
        }
    }
    if ($filterMethodId > 0) {
        $methodResult = $dbConn->query("SELECT method_name FROM arap_method WHERE method_id = " . $filterMethodId . " LIMIT 1");
        if ($methodResult && $methodRow = $methodResult->fetch_assoc()) {
            $methodLabel = $methodRow['method_name'];
        }
    }

    $listSql = "SELECT
                    t.tx_date,
                    t.tx_type,
                    c.cat_name,
                    s.sub_name,
                    t.description,
                    m.method_name,
                    t.amount,
                    t.memo,
                    t.src_ref
                FROM arap_transaction t
                INNER JOIN arap_category c ON c.cat_id = t.cat_id
                LEFT JOIN arap_subcategory s ON s.sub_id = t.sub_id
                LEFT JOIN arap_method m ON m.method_id = t.method_id
                WHERE {$whereSql}
                ORDER BY t.tx_date DESC, t.tx_id DESC";
    $listResult = $dbConn->query($listSql);

    $filterRows = array(
        array('항목', '값'),
        array('조회월', $month),
        array('구분', $txType === '' ? '전체' : arapTypeLabel($txType)),
        array('대분류', $categoryLabel),
        array('소분류', $subcategoryLabel),
        array('결제구분', $methodLabel),
        array('키워드', $keyword),
        array('수입합계', $incomeTotal),
        array('지출합계', -1 * $expenseTotal),
        array('차액', $balanceTotal)
    );

    $transactionRows = array(
        array('거래일자', '구분', '대분류', '소분류', '사용내역', '결제구분', '금액', '메모', '원천')
    );
    if ($listResult) {
        while ($row = $listResult->fetch_assoc()) {
            $signedAmount = $row['tx_type'] === 'OUT' ? -1 * abs((float)$row['amount']) : abs((float)$row['amount']);
            $transactionRows[] = array(
                $row['tx_date'],
                arapTypeLabel($row['tx_type']),
                $row['cat_name'],
                $row['sub_name'],
                $row['description'],
                $row['method_name'],
                $signedAmount,
                $row['memo'],
                $row['src_ref']
            );
        }
    }
    if (count($transactionRows) === 1) {
        $transactionRows[] = array('', '', '', '', '데이터 없음', '', 0, '', '');
    }

    if (arapLoadPHPExcel()) {
        $excel = new PHPExcel();
        $sheet = $excel->setActiveSheetIndex(0);
        $sheet->setTitle('거래내역');
        $sheet->setShowGridLines(false);
        $sheet->freezePane('A2');

        $headers = array('일자', '구분', '대분류', '소분류', '사용내역', '거래수단', '금액', '메모', '원본');
        $columns = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I');
        $widths = array(16, 10, 14, 14, 40, 15, 16, 24, 16);

        foreach ($columns as $index => $column) {
            $sheet->setCellValue($column . '1', $headers[$index]);
            $sheet->getColumnDimension($column)->setWidth($widths[$index]);
        }

        $headerRange = 'A1:I1';
        $sheet->getStyle($headerRange)->applyFromArray(array(
            'font' => array(
                'name' => 'Malgun Gothic',
                'size' => 10,
                'bold' => true
            ),
            'fill' => array(
                'type' => PHPExcel_Style_Fill::FILL_SOLID,
                'color' => array('rgb' => 'E6EEF5')
            ),
            'alignment' => array(
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
            ),
            'borders' => array(
                'allborders' => array(
                    'style' => PHPExcel_Style_Border::BORDER_THIN,
                    'color' => array('rgb' => '2C2C2C')
                )
            )
        ));
        $sheet->getRowDimension(1)->setRowHeight(24);

        $excelRow = 2;
        for ($i = 1; $i < count($transactionRows); $i++) {
            $row = $transactionRows[$i];
            $sheet->setCellValueExplicit('A' . $excelRow, (string)$row[0], PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('B' . $excelRow, $row[1]);
            $sheet->setCellValue('C' . $excelRow, $row[2]);
            $sheet->setCellValue('D' . $excelRow, $row[3]);
            $sheet->setCellValue('E' . $excelRow, $row[4]);
            $sheet->setCellValue('F' . $excelRow, $row[5]);
            $sheet->setCellValue('G' . $excelRow, (float)$row[6]);
            $sheet->setCellValue('H' . $excelRow, $row[7]);
            $sheet->setCellValue('I' . $excelRow, $row[8]);
            $excelRow++;
        }

        $lastRow = max(2, $excelRow - 1);
        $dataRange = 'A1:I' . $lastRow;
        $sheet->getStyle($dataRange)->applyFromArray(array(
            'font' => array(
                'name' => 'Malgun Gothic',
                'size' => 10
            ),
            'alignment' => array(
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
            ),
            'borders' => array(
                'allborders' => array(
                    'style' => PHPExcel_Style_Border::BORDER_THIN,
                    'color' => array('rgb' => '2C2C2C')
                )
            )
        ));

        $sheet->getStyle('A2:A' . $lastRow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('B2:D' . $lastRow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('E2:E' . $lastRow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('F2:F' . $lastRow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('G2:G' . $lastRow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('H2:I' . $lastRow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('G2:G' . $lastRow)->getNumberFormat()->setFormatCode('$#,##0.00;-$#,##0.00');

        for ($rowNo = 2; $rowNo <= $lastRow; $rowNo++) {
            $sheet->getRowDimension($rowNo)->setRowHeight(22);
        }

        $sheet->setAutoFilter($dataRange);

        arapCleanOutputBuffer();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . arapExportFilename('arap_list_table', $month) . '"');
        header('Cache-Control: max-age=0');

        $writer = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
        $writer->setPreCalculateFormulas(false);
        $writer->save('php://output');
        exit;
    }

    $xlsx = SimpleXLSXGen::fromArray($filterRows, '조회조건');
    $xlsx->addSheet($transactionRows, '거래내역');

    arapCleanOutputBuffer();
    $xlsx->downloadAs(arapExportFilename('arap_list', $month));
    exit;
}

if ($exportType === 'summary') {
    $year = arapGetYearValue(isset($_GET['year']) ? $_GET['year'] : '');
    $summary = arapFetchSummaryData($dbConn, $year);
    if (arapStreamSummaryDashboard($summary) === false) {
        $fallbackRows = array(
            array('항목', '값'),
            array('조회년도', $summary['year']),
            array('연간 수입합계', $summary['year_income_total']),
            array('연간 지출합계', $summary['year_expense_total']),
            array('연간 차액', $summary['year_balance_total'])
        );
        $fallbackMonthRows = array(
            array('월', '수입합계', '지출합계', '차액')
        );
        foreach ($summary['months'] as $monthRow) {
            $fallbackMonthRows[] = array(
                $monthRow['label'],
                $monthRow['income_total'],
                $monthRow['expense_total'],
                $monthRow['balance_total']
            );
        }
        $xlsx = SimpleXLSXGen::fromArray($fallbackRows, '개요');
        $xlsx->addSheet($fallbackMonthRows, '월별추이');
        arapCleanOutputBuffer();
        $xlsx->downloadAs(arapExportFilename('arap_summary', $summary['year']));
        exit;
    }
}

arapStreamListExport($dbConn);
