<?php
/**
 * estimate_excel.php  (PHP 7.0 + PHPExcel 1.8)
 * - 템플릿의 스타일/수식/병합/유효성을 그대로 유지
 * - 템플릿 내 "모델행"을 복제하여 항목 수만큼 확장 (A열에 {{ITEMS_MODEL}})
 * - 마스터 값은 {{master:필드명}} 플레이스홀더로 치환
 *
 * 준비물:
 * 1) PHPExcel 1.8 경로(예): /var/www/html/admin/lib/PHPExcel/Classes/PHPExcel.php
 * 2) 템플릿(예): /var/www/html/templates/견적서 form.xlsx
 * 3) DB: estimate_master / estimate_items
 */

ini_set('display_errors', '1');
error_reporting(E_ALL);

// 메모리/시간 (필요 시 상향)
ini_set('memory_limit', '512M'); // 부족하면 1024M까지
set_time_limit(300);

// DB 연결 (환경에 맞게)
require_once './include/dbconn2.php'; // $dbConn (mysqli)

// PHPExcel 1.8 로더 (경로 확인)
require_once './lib/PHPExcel/Classes/PHPExcel.php';

use PHPExcel;
use PHPExcel_IOFactory;
use PHPExcel_Cell;
use PHPExcel_Worksheet;
use PHPExcel_Settings;
use PHPExcel_CachedObjectStorageFactory;

// ===== 입력 파라미터 =====
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { die('invalid id'); }

// ===== 데이터 로드 =====
function loadEstimate(mysqli $db, int $id) {
    // master
    $stmt = $db->prepare("SELECT * FROM estimate_master WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $master = $stmt->get_result()->fetch_assoc();
    if (!$master) { die("견적서를 찾을 수 없습니다. id=".$id); }

    // items (detail)
    $stmt = $db->prepare("SELECT * FROM estimate_items WHERE estimate_id = ? ORDER BY section, id");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $rs = $stmt->get_result();
    $items = [];
    while ($row = $rs->fetch_assoc()) {
        $items[] = $row;
    }
    return [$master, $items];
}
list($master, $items) = loadEstimate($dbConn, $id);

// ===== 템플릿 경로 =====
$templatePath = './templates/estimate_form.xlsx';
if (!is_file($templatePath)) {
    die('템플릿을 찾을 수 없습니다: '.$templatePath);
}

// ===== PHPExcel 메모리 최적화 =====
PHPExcel_Settings::setCacheStorageMethod(
    PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp,
    array('memoryCacheSize' => '512MB') 
);

// ===== 템플릿 로드 (리더 최적화) =====
$reader = PHPExcel_IOFactory::createReader('Excel2007');
// 값만 읽기 -> 템플릿 스타일/수식은 유지되며, 데이터 주입엔 충분
$reader->setReadDataOnly(true);
// 특정 시트만 로드할 경우 (시트명이 확실할 때만)
// $reader->setLoadSheetsOnly(['견적서']);

$excel = $reader->load($templatePath);
$sheet = $excel->getActiveSheet();

// ===== 유틸: 마스터 플레이스홀더 치환 =====
function replaceMasterPlaceholders(PHPExcel_Worksheet $sheet, array $master) {
    $highestRow = $sheet->getHighestRow();
    $highestCol = PHPExcel_Cell::columnIndexFromString($sheet->getHighestColumn());
    for ($r=1; $r<=$highestRow; $r++) {
        for ($c=0; $c<$highestCol; $c++) {
            $cell = $sheet->getCellByColumnAndRow($c, $r);
            $v = $cell->getValue();
            if (!is_string($v) || $v==='') continue;

            if (preg_match_all('/\{\{\s*master:([a-zA-Z0-9_]+)\s*\}\}/', $v, $m, PREG_SET_ORDER)) {
                foreach ($m as $one) {
                    $key = $one[1];
                    $rep = isset($master[$key]) ? (string)$master[$key] : '';
                    $v = str_replace($one[0], $rep, $v);
                }
                $cell->setValueExplicit($v);
            }
        }
    }
}
replaceMasterPlaceholders($sheet, $master);

// ===== 유틸: 모델행 찾기 (A열에 {{ITEMS_MODEL}}) =====
function findModelRow(PHPExcel_Worksheet $sheet) {
    $highestRow = $sheet->getHighestRow();
    for ($r=1; $r<=$highestRow; $r++) {
        $val = $sheet->getCell('A'.$r)->getValue();
        if (is_string($val) && strpos($val, '{{ITEMS_MODEL}}') !== false) {
            return $r;
        }
    }
    return null;
}
//$modelRow = findModelRow($sheet);
//Sif (!$modelRow) { die('템플릿에 모델행 {{ITEMS_MODEL}} 가 필요합니다.'); }

// 모델행 마커 지우기
$sheet->setCellValue('A'.$modelRow, '');

// ===== 유틸: 병합/스타일/유효성 복제 =====
function cloneRowMerges(PHPExcel_Worksheet $sheet, $srcRow, $dstRow) {
    foreach ($sheet->getMergeCells() as $merge) {
        list($start, $end) = explode(':', $merge);
        list($sc, $sr) = PHPExcel_Cell::coordinateFromString($start);
        list($ec, $er) = PHPExcel_Cell::coordinateFromString($end);
        if ($sr == $er && $sr == $srcRow) { // 같은 행에만 적용
            $sheet->mergeCells($sc.$dstRow.':'.$ec.$dstRow);
        }
    }
}
function cloneRowStyleAndValidation(PHPExcel_Worksheet $sheet, $srcRow, $dstRow) {
    $highestColIndex = PHPExcel_Cell::columnIndexFromString($sheet->getHighestColumn());
    // 행높이
    $sheet->getRowDimension($dstRow)->setRowHeight(
        $sheet->getRowDimension($srcRow)->getRowHeight()
    );
    for ($c=0; $c<$highestColIndex; $c++) {
        $col = PHPExcel_Cell::stringFromColumnIndex($c);
        // 스타일(폰트/테두리/채우기/정렬/번호형식 등)
        $sheet->duplicateStyle($sheet->getStyle($col.$srcRow), $col.$dstRow);
        // 데이터 유효성 복제
        $srcDV = $sheet->getDataValidation($col.$srcRow);
        if ($srcDV && $srcDV->getType()) {
            $dstDV = clone $srcDV;
            $sheet->setDataValidation($col.$dstRow, $dstDV);
        }
    }
}

// ===== 유틸: 수식 행번호 보정 (간단 케이스) =====
function adjustFormulaRow($formula, $srcRow, $dstRow) {
    // "=B10*C10" -> 새 행으로 변경
    return preg_replace_callback('/(\$?[A-Z]{1,3})(\$?)(\d+)/', function($m) use($srcRow,$dstRow){
        $col = $m[1];
        $absRow = $m[2]; // '$' 또는 ''
        $num = (int)$m[3];
        if ($num === (int)$srcRow) {
            return $col . $absRow . $dstRow;
        }
        return $m[0];
    }, $formula);
}

// ===== 항목 쓰기 =====
// 템플릿 열 매핑(필요 시 조정)
//  - A: label, B: unit, C: cnt, D: 금액(=B*C 수식은 템플릿 모델행에 존재 가정)
$colMap = [
    'label' => 'A',
    'unit'  => 'B',
    'cnt'   => 'C',
];

$srcRow = $modelRow;
$curRow = $modelRow;

// 모델행에 바로 1개 채울지 여부
$writeOnModelRow = true;

// 1) 첫 아이템: 모델행에 작성
if ($writeOnModelRow && count($items) > 0) {
    $it = $items[0];
    foreach ($colMap as $key => $col) {
        $val = isset($it[$key]) ? $it[$key] : '';
        $sheet->setCellValue($col.$curRow, $val);
    }
    // 모델행 내 수식 보정
    $highestColIndex = PHPExcel_Cell::columnIndexFromString($sheet->getHighestColumn());
    for ($c=0; $c<$highestColIndex; $c++) {
        $addr = PHPExcel_Cell::stringFromColumnIndex($c).$curRow;
        $cell = $sheet->getCell($addr);
        if ($cell->isFormula()) {
            $cell->setValue(adjustFormulaRow($cell->getValue(), $srcRow, $curRow));
        }
    }
    $curRow++;
}

// 2) 나머지 아이템: 행 삽입 + 복제 + 값 주입
for ($i = ($writeOnModelRow?1:0); $i < count($items); $i++) {
    // 새 줄 삽입
    $sheet->insertNewRowBefore($curRow, 1);
    // 스타일/병합/유효성 복제
    cloneRowStyleAndValidation($sheet, $srcRow, $curRow);
    cloneRowMerges($sheet, $srcRow, $curRow);

    // 수식 복사 및 행번호 보정
    $highestColIndex = PHPExcel_Cell::columnIndexFromString($sheet->getHighestColumn());
    for ($c=0; $c<$highestColIndex; $c++) {
        $col = PHPExcel_Cell::stringFromColumnIndex($c);
        $srcAddr = $col.$srcRow;
        $dstAddr = $col.$curRow;
        $srcCell = $sheet->getCell($srcAddr);
        $dstCell = $sheet->getCell($dstAddr);
        if ($srcCell->isFormula()) {
            $dstCell->setValue(adjustFormulaRow($srcCell->getValue(), $srcRow, $curRow));
        } else {
            // 값은 아래에서 채움
            if (is_string($srcCell->getValue()) && $srcCell->getValue() !== '') {
                $dstCell->setValueExplicit('');
            }
        }
    }

    // 값 주입
    $it = $items[$i];
    foreach ($colMap as $key => $col) {
        $val = isset($it[$key]) ? $it[$key] : '';
        $sheet->setCellValue($col.$curRow, $val);
    }

    $curRow++;
}

// ===== 저장/다운로드 =====
$filename = 'estimate_'.$id.'_'.date('Ymd_His').'.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Cache-Control: max-age=0');

$writer = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
// 서버에서 수식 미리 계산하지 않음(메모리 절약). 필요하면 true로 변경
$writer->setPreCalculateFormulas(false);
$writer->save('php://output');
exit;
