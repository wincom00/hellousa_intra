<?php
/**
 * send_breakdown_excel_xlsx_styled.php
 * - mysqli + PHPExcel 1.8
 * - XLSX + 수식
 * - MEAL: 동적 매트릭스(가로 날짜), master.start~end 스팬 옵션
 * - TRANSPORT: 날짜별 일수 합산 → 합계 = SUM(일수열)*단가*차량수
 * - 모든 섹션: 마지막 합계 셀을 시트 끝(P)까지 병합
 * - 최대 컬럼은 P(16)이며, 실제 데이터 중 가장 긴 열을 기준으로 하되 P를 상한으로 사용
 * - 로우수는 섹션별로 독립 (세로 패딩 제거)
 */

error_reporting(E_ALL);
ini_set('display_errors', '0'); // 필요 시 1

if (isset($_GET['action']) && $_GET['action'] === 'download_excel') {
    require_once __DIR__ . "/include/inc_base.php"; // $dbConn (mysqli)

    $estimateId = isset($_GET['estimate_id']) ? (int)$_GET['estimate_id'] : 0;
    if ($estimateId <= 0) {
        header("Content-Type: text/html; charset=UTF-8");
        echo "<script>alert('견적서 ID가 필요합니다.'); history.back();</script>";
        exit;
    }

    $ret = streamBreakdownExcelXlsxStyled($dbConn, $estimateId);
    if ($ret !== true) {
        header("Content-Type: text/html; charset=UTF-8");
        echo "<script>alert('엑셀 생성 실패: " . htmlspecialchars((string)$ret, ENT_QUOTES, 'UTF-8') . "'); history.back();</script>";
    }
    exit;
}
/*}else {
    header("Content-Type: text/html; charset=UTF-8");
    echo "<!DOCTYPE html>
    <html><head><meta charset='UTF-8'><title>BREAKDOWN XLSX (Styled)</title>
    <style>body{font-family:Arial,sans-serif;max-width:520px;margin:50px auto;padding:20px}
    .form-group{margin-bottom:15px}label{display:block;margin-bottom:5px;font-weight:bold}
    input{width:100%;padding:8px;border:1px solid #ddd;border-radius:4px}
    button{background:#2E86AB;color:#fff;padding:10px 20px;border:none;border-radius:4px;cursor:pointer}</style>
    </head><body>
    <h2>BREAKDOWN QUOTATION .xlsx (Styled)</h2>
    <form method='GET'>
      <input type='hidden' name='action' value='download_excel'>
      <div class='form-group'><label for='estimate_id'>견적서 ID:</label>
      <input type='number' id='estimate_id' name='estimate_id' required placeholder='estimate_master의 id'></div>
      <div class='form-group'><button type='submit'>엑셀 다운로드</button></div>
    </form>
    </body></html>";
}
*/
function streamBreakdownExcelXlsxStyled(mysqli $dbConn, int $estimateId) {
    // === 옵션 ===
    $MEAL_FILL_AUTO_SPAN = true; // true: master.start~end 날짜를 MEAL 헤더에 모두 포함
    $MAX_COL_IDX = 16;           // P 열
    $MAX_COL_LETTER = 'P';

    // --- 데이터 로드 ---
    $sql = "SELECT * FROM estimate_master WHERE id = ? LIMIT 1";
    if (!$stmt = $dbConn->prepare($sql)) return "master prepare 실패: ".$dbConn->error;
    $stmt->bind_param("i", $estimateId);
    if (!$stmt->execute()) return "master execute 실패: ".$stmt->error;
    $master = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$master) return "견적서를 찾을 수 없습니다. id=".$estimateId;

    $sql = "SELECT * FROM estimate_items WHERE estimate_id = ? ORDER BY section, id";
    if (!$stmt = $dbConn->prepare($sql)) return "items prepare 실패: ".$dbConn->error;
    $stmt->bind_param("i", $estimateId);
    if (!$stmt->execute()) return "items execute 실패: ".$stmt->error;
    $rs = $stmt->get_result();
    $items = [];
    while ($row = $rs->fetch_assoc()) $items[] = $row;
    $stmt->close();

    // ---------- 유틸 ----------
    $masterStart = isset($master['start_date']) ? trim((string)$master['start_date']) : '';

    $normalizeDate = function($s) use ($masterStart) {
        $s = trim((string)$s);
        if ($s === '') return '';
        $s = str_replace(['.','/'],'-',$s);
        if (preg_match('/^\d{8}$/',$s)) return substr($s,0,4).'-'.substr($s,4,2).'-'.substr($s,6,2);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/',$s)) return $s;
        if (preg_match('/^\d{1,2}-\d{1,2}$/',$s)) {
            $y = date('Y', $masterStart ? strtotime($masterStart) : time());
            list($m,$d) = explode('-', $s);
            return sprintf('%04d-%02d-%02d', (int)$y, (int)$m, (int)$d);
        }
        if (strpos($s,'~')!==false) { $left = explode('~',$s)[0] ?? ''; return trim($left); }
        return $s;
    };

    $addDays = function($baseYmd,$days){
        if(!$baseYmd||!preg_match('/^\d{4}-\d{2}-\d{2}$/',$baseYmd)) return '';
        return date('Y-m-d', strtotime($baseYmd.' +'.(int)$days.' day'));
    };

    $resolveDate = function(string $section, array $etc) use ($normalizeDate,$addDays,$masterStart) {
        $candBy = [
            'HOTEL'=>['date','hotel_date','checkin_date'],
            'MEAL'=>['date','meal_date'],
            'TRANSPORT'=>['date','transport_date','drive_date'],
            'TICKET'=>['date','ticket_date','visit_date'],
            'GUIDE'=>['date','guide_date','service_date'],
            'ETC'=>['date'],
        ];
        $cand = $candBy[$section] ?? ['date'];
        foreach ($cand as $k) if (!empty($etc[$k])) return $normalizeDate($etc[$k]);
        foreach (['day','day_index','d','dayno'] as $k) if (!empty($etc[$k]) && (int)$etc[$k]>0) return $addDays($masterStart,(int)$etc[$k]-1);
        if (isset($etc['day_offset'])) return $addDays($masterStart,(int)$etc['day_offset']);
        if (!empty($etc['date_range'])) {
            $left = explode('~', str_replace([' ','/','.'],['','-','-'], (string)$etc['date_range']))[0] ?? '';
            return $normalizeDate($left);
        }
        return '';
    };

    // 날짜 키 정규화(맵) → 'YYYY-MM-DD' 키로
    $normalizeKeyedDates = function(array $arr) use ($normalizeDate) {
        $out = [];
        foreach ($arr as $k => $v) {
            $nk = $normalizeDate(is_string($k) ? $k : (string)$k);
            if ($nk === '') continue;
            $out[$nk] = $v;
        }
        ksort($out);
        return $out;
    };

    // dates(맵/리스트) → "일수" 맵으로 변환 (리스트면 각 날짜 일수=1)
    $datesToDaysMap = function($dates) use ($normalizeKeyedDates, $normalizeDate) {
        $days = [];
        if (is_array($dates)) {
            // 맵
            if (array_keys($dates) !== range(0, count($dates)-1)) {
                $dates = $normalizeKeyedDates($dates);
                foreach ($dates as $d => $v) $days[$d] = (float)$v ?: 1.0;
            } else { // 리스트
                foreach ($dates as $d) {
                    $d = $normalizeDate($d);
                    if ($d==='') continue;
                    if (!isset($days[$d])) $days[$d] = 0.0;
                    $days[$d] += 1.0;
                }
            }
        }
        ksort($days);
        return $days;
    };

    // 번호 -> 엑셀 컬럼 레터 (A..Z, AA..)
    $colByIdx = function($n){
        $s = '';
        while ($n > 0) {
            $m = ($n - 1) % 26;
            $s = chr(65 + $m) . $s;
            $n = (int)(($n - 1) / 26);
        }
        return $s;
    };

    // 날짜 범위 전개
    $buildDateRange = function($startYmd, $endYmd){
        $s = strtotime($startYmd ?: ''); $e = strtotime($endYmd ?: '');
        if (!$s || !$e || $e < $s) return [];
        $out = [];
        for ($t=$s; $t <= $e; $t = strtotime('+1 day', $t)) $out[] = date('Y-m-d', $t);
        return $out;
    };

    // --- PHPExcel 로더 ---
    $phpexcelCandidates = [
        __DIR__ . '/lib/PHPExcel/Classes/PHPExcel.php',
        __DIR__ . '/admin/lib/PHPExcel/Classes/PHPExcel.php',
        __DIR__ . '/vendor/phpoffice/phpexcel/Classes/PHPExcel.php',
        dirname(__DIR__) . '/vendor/phpoffice/phpexcel/Classes/PHPExcel.php'
    ];
    $loaded=false; foreach ($phpexcelCandidates as $p) { if (is_file($p)) { require_once $p; $loaded=true; break; } }
    if (!$loaded) return 'PHPExcel.php 를 찾을 수 없습니다.';

    // --- 워크북/시트 ---
    $excel = new PHPExcel();
    $excel->getProperties()->setCreator("System")->setTitle("BREAKDOWN QUOTATION");
    $excel->setActiveSheetIndex(0);
    $sheet = $excel->getActiveSheet();
    $sheet->setTitle('BREAKDOWN');

    // 팔레트
    $CLR_BLUE  = '2E86AB';
    $CLR_LBLUE = 'E3F2FD';
    $CLR_HGRAY = 'F8F9FA';
    $CLR_BORDER= 'DDDDDD';
    $CLR_WHITE = 'FFFFFF';
    $CLR_BLACK = '000000';
    $CLR_YELLOW= 'FFF9C4';

    // 공통 스타일
    $bold=['font'=>['bold'=>true]];
    $center=['alignment'=>['horizontal'=>PHPExcel_Style_Alignment::HORIZONTAL_CENTER]];
    $wrap=['alignment'=>['wrap'=>true]];
    $vbCenter=['alignment'=>['vertical'=>PHPExcel_Style_Alignment::VERTICAL_CENTER]];
    $borderAll=['borders'=>['allborders'=>['style'=>PHPExcel_Style_Border::BORDER_THIN,'color'=>['rgb'=>$CLR_BORDER]]]];
    $setFill=function($range,$rgb)use($sheet){$sheet->getStyle($range)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB($rgb);};
    $setFontColor=function($range,$rgb)use($sheet){$sheet->getStyle($range)->getFont()->getColor()->setRGB($rgb);};

    // 기본 컬럼 폭 (A~P)
    foreach (range(1,$MAX_COL_IDX) as $i) {
        $c = $colByIdx($i);
        $sheet->getColumnDimension($c)->setWidth(in_array($c,['A','B','C','D','H'])?14:10);
    }

    $row = 1;

    // 섹션 분류
    $sections=[];
    foreach ($items as $it) {
        $sec = $it['section'] ?? 'ETC';
        if (!isset($sections[$sec])) $sections[$sec] = [];
        $sections[$sec][] = $it;
    }

    $order=['HOTEL','MEAL','TRANSPORT','TICKET','GUIDE','ETC'];
    $titles=[
        'HOTEL'=>'1) HOTEL',
        'MEAL'=>'2) MEAL (일자×식사 매트릭스)',
        'TRANSPORT'=>'3) TRANSPORTATION (일자별 차량료)',
        'TICKET'=>'4) 입장권',
        'GUIDE'=>'5) 가이드/기사',
        'ETC'=>'7) 기타경비'
    ];

    // ── 사전 계산: 섹션별 날짜열 준비 ──
    $mealAllDates = [];
    if (!empty($sections['MEAL'])) {
        $set = [];
        foreach ($sections['MEAL'] as $it) {
            $etc = json_decode((string)($it['etc_json']??'{}'), true) ?: [];
            $map = $datesToDaysMap($etc['dates'] ?? []);
            if (empty($map)) {
                $d0 = $resolveDate('MEAL', $etc) ?: date('Y-m-d');
                $map = [$d0 => (float)($etc['unit_per_pax'] ?? (float)($it['unit'] ?? 0))];
            }
            foreach ($map as $ymd => $_) $set[$ymd] = true;
        }
        if ($MEAL_FILL_AUTO_SPAN) {
            $ms = trim((string)($master['start_date'] ?? ''));
            $me = trim((string)($master['end_date'] ?? ''));
            if ($ms !== '' && $me !== '') {
                foreach ($buildDateRange($ms,$me) as $d) $set[$d] = true;
            }
        }
        $mealAllDates = array_keys($set); sort($mealAllDates);
        if (empty($mealAllDates)) $mealAllDates = [date('Y-m-d')];
    }

    // TRANSPORT 헤더용 날짜 (MEAL 없으면 자체 수집)
    $transAllDates = $mealAllDates;
    if (empty($transAllDates) && !empty($sections['TRANSPORT'])) {
        $set = [];
        foreach ($sections['TRANSPORT'] as $it) {
            $etc = json_decode((string)($it['etc_json']??'{}'), true) ?: [];
            $m = $datesToDaysMap($etc['dates'] ?? []);
            if (empty($m)) {
                $d0 = $resolveDate('TRANSPORT',$etc) ?: date('Y-m-d');
                $m = [$d0 => (float)($it['qty'] ?? 1)];
            }
            foreach ($m as $ymd => $_) $set[$ymd] = true;
        }
        $transAllDates = array_keys($set); sort($transAllDates);
        if (empty($transAllDates)) $transAllDates = [date('Y-m-d')];
    }

    // ── P 상한에 맞춰 날짜 열 수 절단 ──
    // MEAL: A(구분)=1 + 날짜N + [일인당합계단가, 인원수, 합계]=3 → N <= 16-1-3 = 12
    $MEAL_MAX_DATES = max(0, $MAX_COL_IDX - 1 - 3); // 12
    if (count($mealAllDates) > $MEAL_MAX_DATES) {
        $mealAllDates = array_slice($mealAllDates, 0, $MEAL_MAX_DATES);
    }
    // TRANSPORT: A(차량)=1 + 날짜N + [차량수, 합계]=2 → N <= 16-1-2 = 13
    $TRANS_MAX_DATES = max(0, $MAX_COL_IDX - 1 - 2); // 13
    if (count($transAllDates) > $TRANS_MAX_DATES) {
        $transAllDates = array_slice($transAllDates, 0, $TRANS_MAX_DATES);
    }

    // ── “가장 긴 열” 계산 후 P 상한 적용 ──
    $MEAL_LAST_COL_IDX  = !empty($mealAllDates)  ? (1 + count($mealAllDates)  + 3) : 0;
    $TRANS_LAST_COL_IDX = !empty($transAllDates) ? (1 + count($transAllDates) + 2) : 0;
    $LAST_COL_IDX = min($MAX_COL_IDX, max(8, $MEAL_LAST_COL_IDX, $TRANS_LAST_COL_IDX));
    $L = $colByIdx($LAST_COL_IDX); // 시트 운용상의 실제 끝 컬럼(최대 P)

    // 합계/우측 영역을 시트 끝까지 병합하는 헬퍼
    $mergeToSheetEnd = function($row, $fromColLetter) use ($sheet, $L) {
        if ($L !== $fromColLetter) {
            $sheet->mergeCells("{$fromColLetter}{$row}:{$L}{$row}");
        }
    };

    // ── 타이틀 ──
    $sheet->mergeCells("A{$row}:{$L}{$row}");
    $sheet->setCellValue("A{$row}","BREAKDOWN QUOTATION");
    $sheet->getStyle("A{$row}")->applyFromArray($bold + $center);
    $sheet->getStyle("A{$row}")->getFont()->setSize(18);
    $setFill("A{$row}:{$L}{$row}",$CLR_BLUE); $setFontColor("A{$row}:{$L}{$row}",$CLR_WHITE);
    $row += 2;

    // 기본정보
    $firstInfoRow = $row;
    $sheet->fromArray([['TO',(string)($master['to_name']??''),'GROUP',(string)($master['group_name']??''),'PAX',(int)($master['pax']??0),'FOC',(int)($master['foc']??0)]],null,"A{$row}");
    $sheet->fromArray([['시작일',(string)($master['start_date']??''),'종료일',(string)($master['end_date']??''),'총인원',(int)($master['total_pax']??0),'작성일',(string)($master['wdate']??'')]],null,"A".($row+1));
    foreach (['A','C','E','G'] as $c){ $setFill("{$c}{$row}",$CLR_HGRAY); $setFill("{$c}".($row+1),$CLR_HGRAY); }
    $sheet->getStyle("A{$row}:{$L}".($row+1))->applyFromArray($borderAll + $vbCenter);
    $paxCell="F{$firstInfoRow}";
    $row += 3;

    $subtotalCells=[];

    // ───────────────────── 렌더링 ─────────────────────
    foreach ($order as $sec) {
        if (empty($sections[$sec])) continue;

        // 섹션 타이틀 바
        $sheet->mergeCells("A{$row}:{$L}{$row}");
        $sheet->setCellValue("A{$row}", $titles[$sec]);
        $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($bold + $vbCenter);
        $setFill("A{$row}:{$L}{$row}", $CLR_YELLOW); $setFontColor("A{$row}:{$L}{$row}", $CLR_BLACK);
        $row++;

        $startDataRow = $row;

        // ── HOTEL ──
        if ($sec==='HOTEL') {
            $sheet->fromArray([['지역','날짜','요일/시간','호텔명','방수','요금(USD)','박수','합계']],null,"A{$row}");
            // 헤더 마지막 셀(H) ~ L 까지 병합 일관화
            $mergeToSheetEnd($row, 'H');
            $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($bold + $center + $borderAll + $vbCenter);
            $setFill("A{$row}:{$L}{$row}",$CLR_HGRAY);
            $row++;

            foreach ($sections[$sec] as $it) {
                $etc=json_decode((string)($it['etc_json']??'{}'),true)?:[];
                $a=(string)($etc['region']??'');
                $b=$resolveDate('HOTEL',$etc);
                $c=(string)($etc['weekday']??($etc['time']??''));
                $d=(string)($it['label']??''); if ($d!=='') $d.=" 또는 동급호텔";
                $e=(float)($it['cnt']??0); $f=(float)($it['unit']??0); $g=(float)($it['qty']??0);

                $sheet->fromArray([[ $a,$b,$c,$d,$e,$f,$g ]],null,"A{$row}");
                $sheet->setCellValue("H{$row}","=F{$row}*G{$row}");
                $sheet->getStyle("E{$row}:H{$row}")->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');
                $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($borderAll + $vbCenter + $wrap);
                $mergeToSheetEnd($row,'H');
                $row++;
            }

            // 소계
            $endDataRow=$row-1;
            $sheet->mergeCells("A{$row}:G{$row}");
            $sheet->setCellValue("A{$row}",$titles[$sec].' 소계');
            $sheet->setCellValue("H{$row}", ($endDataRow>=$startDataRow) ? "=SUM(H{$startDataRow}:H{$endDataRow})" : 0);
            $mergeToSheetEnd($row,'H');
            $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($bold + $borderAll + $vbCenter);
            $setFill("A{$row}:{$L}{$row}",$CLR_LBLUE);
            $sheet->getStyle("H{$row}")->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');
            $subtotalCells[]="H{$row}";
            $row+=2;
            continue;
        }

        // ── MEAL (동적 매트릭스) ──
        if ($sec==='MEAL') {
            // 헤더: A=구분 | dates... | 일인당 합계단가 | 인원수 | 합계
            $col=1; $sheet->setCellValue($colByIdx($col).$row,'구분'); $col++;
            foreach($mealAllDates as $ymd){ $sheet->setCellValue($colByIdx($col).$row,$ymd); $col++; }
            $colSumPerPax=$col; $sheet->setCellValue($colByIdx($col).$row,'일인당 합계단가'); $col++;
            $colPax=$col;       $sheet->setCellValue($colByIdx($col).$row,'인원수');       $col++;
            $colTotal=$col;     $sheet->setCellValue($colByIdx($col).$row,'합계');

            // 헤더 마지막 셀(합계) ~ L까지 병합
            $mergeToSheetEnd($row, $colByIdx($colTotal));

            $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($bold + $center + $borderAll + $vbCenter);
            $setFill("A{$row}:{$L}{$row}",$CLR_HGRAY);
            $row++;

            // 데이터 집계
            $classifyMeal=function($etc,$time){
                $v=strtolower(trim((string)($etc['meal_type']??'')));
                if (in_array($v,['b','bf','breakfast','조식','아침'],true)) return 'B';
                if (in_array($v,['l','lunch','중식','점심'],true)) return 'L';
                if (in_array($v,['d','din','dinner','석식','저녁'],true)) return 'D';
                if (preg_match('/^(\d{1,2}):(\d{2})$/',(string)$time,$m)){ $h=(int)$m[1]; return ($h<=10?'B':($h<=15?'L':'D')); }
                return 'L';
            };
            $labelBy=['B'=>'조식','L'=>'중식','D'=>'석식'];
            $grid=['B'=>[],'L'=>[],'D'=>[]];
            $paxByMeal=['B'=>0,'L'=>0,'D'=>0];
            $defaultPax=(int)($master['total_pax']??0);

            foreach ($sections[$sec] as $it){
                $etc=json_decode((string)($it['etc_json']??'{}'),true)?:[];
                $mk=$classifyMeal($etc,$etc['time']??'');
                $pax=(int)($etc['pax'] ?? $defaultPax);
                if ($pax>0) $paxByMeal[$mk]=max($paxByMeal[$mk],$pax);

                $map=$datesToDaysMap($etc['dates']??[]);
                if (empty($map)) {
                    $d0=$resolveDate('MEAL',$etc) ?: date('Y-m-d');
                    $map=[$d0 => (float)($etc['unit_per_pax'] ?? (float)($it['unit'] ?? 0))];
                }
                foreach ($map as $ymd=>$unitPerPax){
                    if (!isset($grid[$mk][$ymd])) $grid[$mk][$ymd]=0.0;
                    $grid[$mk][$ymd]+=(float)$unitPerPax;
                }
            }

            $mealRowTotals=[];
            foreach (['B','L','D'] as $mk) {
                $sheet->setCellValue("A{$row}", $labelBy[$mk]);
                $sumCells=[]; $col=2; // 날짜열 시작
                foreach ($mealAllDates as $ymd){
                    $val = isset($grid[$mk][$ymd]) ? (float)$grid[$mk][$ymd] : 0.0;
                    $addr=$colByIdx($col).$row;
                    $sheet->setCellValue($addr,$val);
                    $sheet->getStyle($addr)->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');
                    $sumCells[]=$addr; $col++;
                }
                $addrSum=$colByIdx($col).$row;
                $sheet->setCellValue($addrSum, !empty($sumCells)?'='.implode('+',$sumCells):0);
                $sheet->getStyle($addrSum)->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0'); $col++;

                $addrPax=$colByIdx($col).$row; $sheet->setCellValueExplicit($addrPax,(int)$paxByMeal[$mk],PHPExcel_Cell_DataType::TYPE_NUMERIC); $col++;

                $addrTot=$colByIdx($col).$row; $sheet->setCellValue($addrTot,"={$addrSum}*{$addrPax}");
                $sheet->getStyle($addrTot)->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');
                $mealRowTotals[]=$addrTot;

                $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($borderAll + $vbCenter + $wrap);
                // 합계 셀 ~ L 까지 병합
                $mergeToSheetEnd($row, $colByIdx($col));
                $row++;
            }

            // 소계
            $sumExpr = !empty($mealRowTotals)?'='.implode('+',$mealRowTotals):'0';
            $sheet->mergeCells("A{$row}:".$colByIdx($colTotal-1)."{$row}");
            $sheet->setCellValue("A{$row}", 'MEAL 소계');
            $sheet->setCellValue($colByIdx($colTotal).$row, $sumExpr);
            $mergeToSheetEnd($row, $colByIdx($colTotal));
            $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($bold + $borderAll + $vbCenter);
            $setFill("A{$row}:{$L}{$row}",$CLR_LBLUE);
            $sheet->getStyle($colByIdx($colTotal).$row)->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');
            $subtotalCells[] = $colByIdx($colTotal).$row;
            $row+=2;
            continue;
        }

        // ── TRANSPORT (일자별 차량료 매트릭스) ──
        if ($sec==='TRANSPORT') {
            // 헤더: A=차량 | dates... | 차량수 | 합계
            $col=1; $sheet->setCellValue($colByIdx($col).$row,'차량'); $col++;
            foreach($transAllDates as $ymd){ $sheet->setCellValue($colByIdx($col).$row,$ymd); $col++; }
            $colCar=$col;       $sheet->setCellValue($colByIdx($col).$row,'차량수'); $col++;
            $colTotal=$col;     $sheet->setCellValue($colByIdx($col).$row,'합계');

            // 헤더 마지막(합계) ~ L 병합
            $mergeToSheetEnd($row, $colByIdx($colTotal));

            $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($bold + $center + $borderAll + $vbCenter);
            $setFill("A{$row}:{$L}{$row}",$CLR_HGRAY);
            $row++;

            // 차량/노선별 집계
            $rows = []; // key => ['title'=>..., 'car'=>N, 'days'=>[ymd=>days], 'unit'=>price]
            foreach ($sections[$sec] as $it) {
                $etc  = json_decode((string)($it['etc_json']??'{}'),true)?:[];
                $veh  = (string)($etc['vehicle_type'] ?? ($it['label'] ?? ''));
                $route= (string)($etc['route'] ?? ($it['description'] ?? ''));
                $key  = trim($veh . ($route ? " / {$route}" : ''));

                if (!isset($rows[$key])) $rows[$key] = ['title'=>$key,'car'=>0.0,'days'=>[],'unit'=>(float)($it['unit'] ?? 0)];

                // 차량수 누적
                $rows[$key]['car'] += (float)($etc['unit_per_car'] ?? (float)($it['cnt'] ?? 0));

                // 날짜별 일수 맵
                $daysMap = $datesToDaysMap($etc['dates'] ?? []);
                if (empty($daysMap)) {
                    $d0 = $resolveDate('TRANSPORT',$etc) ?: date('Y-m-d');
                    $daysMap = [$d0 => (float)($it['qty'] ?? 1)];
                }
                foreach ($daysMap as $ymd=>$days) {
                    if (!isset($rows[$key]['days'][$ymd])) $rows[$key]['days'][$ymd]=0.0;
                    $rows[$key]['days'][$ymd] += (float)$days;
                }
            }

            $transRowTotals=[];
            foreach ($rows as $r) {
                $sheet->setCellValue("A{$row}", $r['title']);

                // 날짜칸: 일수(숫자) 입력
                $col=2; $sumCells=[];
                foreach ($transAllDates as $ymd){
                    $addr=$colByIdx($col).$row;
                    $days = isset($r['days'][$ymd]) ? (float)$r['days'][$ymd] : 0.0;
                    $sheet->setCellValueExplicit($addr,$days, PHPExcel_Cell_DataType::TYPE_NUMERIC);
                    $sumCells[]=$addr;
                    $col++;
                }

                // 차량수
                $addrCar=$colByIdx($col).$row;
                $sheet->setCellValueExplicit($addrCar,(float)$r['car'] ?: 1.0, PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $col++;

                // 합계 = SUM(일수열) * 단가 * 차량수
                $addrTot  = $colByIdx($col).$row;
                $sumExpr  = !empty($sumCells)?'('.implode('+',$sumCells).')': '0';
                $unitVal  = (float)$r['unit'];
                $sheet->setCellValue($addrTot, "={$sumExpr}*{$unitVal}*{$addrCar}");
                $sheet->getStyle($addrTot)->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');
                $transRowTotals[]=$addrTot;

                $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($borderAll + $vbCenter + $wrap);
                // 합계 셀 ~ L 병합
                $mergeToSheetEnd($row, $colByIdx($col));
                $row++;
            }

            // 소계
            $sumExpr = !empty($transRowTotals)?'='.implode('+',$transRowTotals):'0';
            $sheet->mergeCells("A{$row}:".$colByIdx($colTotal-1)."{$row}");
            $sheet->setCellValue("A{$row}", 'TRANSPORTATION 소계');
            $sheet->setCellValue($colByIdx($colTotal).$row, $sumExpr);
            $mergeToSheetEnd($row, $colByIdx($colTotal));
            $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($bold + $borderAll + $vbCenter);
            $setFill("A{$row}:{$L}{$row}",$CLR_LBLUE);
            $sheet->getStyle($colByIdx($colTotal).$row)->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');
            $subtotalCells[] = $colByIdx($colTotal).$row;
            $row+=2;
            continue;
        }

        // ── TICKET ──
        if ($sec==='TICKET') {
            $sheet->fromArray([['입장지','단가','인원','합계']],null,"A{$row}");
            $mergeToSheetEnd($row,'D');
            $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($bold + $center + $borderAll + $vbCenter);
            $setFill("A{$row}:{$L}{$row}",$CLR_HGRAY);
            $row++;

            foreach ($sections[$sec] as $it) {
                $etc=json_decode((string)($it['etc_json']??'{}'),true)?:[];
                $place=(string)($etc['place']??($it['label']??''));
                $unit=(float)($it['unit']??0);
                $cnt=(float)($it['cnt']??0); $qty=(float)($it['qty']??0);
                $people = $cnt>0?$cnt:$qty;

                $sheet->fromArray([[ $place, $unit, $people ]],null,"A{$row}");
                $sheet->setCellValue("D{$row}","=B{$row}*C{$row}");
                $sheet->getStyle("B{$row}:D{$row}")->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');
                $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($borderAll + $vbCenter + $wrap);
                $mergeToSheetEnd($row,'D');
                $row++;
            }

            $endDataRow=$row-1;
            $sheet->mergeCells("A{$row}:C{$row}");
            $sheet->setCellValue("A{$row}",$titles[$sec].' 소계');
            $sheet->setCellValue("D{$row}", ($endDataRow>=$startDataRow) ? "=SUM(D{$startDataRow}:D{$endDataRow})" : 0);
            $mergeToSheetEnd($row,'D');
            $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($bold + $borderAll + $vbCenter);
            $setFill("A{$row}:{$L}{$row}",$CLR_LBLUE);
            $sheet->getStyle("D{$row}")->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');
            $subtotalCells[]="D{$row}";
            $row+=2;
            continue;
        }

        // ── GUIDE ──
        if ($sec==='GUIDE') {
            $sheet->fromArray([['서비스종류','일수/시간','대수','단가','합계']],null,"A{$row}");
            $mergeToSheetEnd($row,'E');
            $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($bold + $center + $borderAll + $vbCenter);
            $setFill("A{$row}:{$L}{$row}",$CLR_HGRAY);
            $row++;

            foreach ($sections[$sec] as $it) {
                $etc=json_decode((string)($it['etc_json']??'{}'),true)?:[];
                $label=(string)($it['label']??($etc['service_type']??''));
                $days=(float)($it['qty']??0); $cars=(float)($it['cnt']??0); $unit=(float)($it['unit']??0);

                $sheet->fromArray([[ $label, $days, $cars, $unit ]],null,"A{$row}");
                $sheet->setCellValue("E{$row}","=B{$row}*C{$row}*D{$row}");
                $sheet->getStyle("B{$row}:E{$row}")->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');
                $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($borderAll + $vbCenter + $wrap);
                $mergeToSheetEnd($row,'E');
                $row++;
            }

            $endDataRow=$row-1;
            $sheet->mergeCells("A{$row}:D{$row}");
            $sheet->setCellValue("A{$row}",$titles[$sec].' 소계');
            $sheet->setCellValue("E{$row}", ($endDataRow>=$startDataRow) ? "=SUM(E{$startDataRow}:E{$endDataRow})" : 0);
            $mergeToSheetEnd($row,'E');
            $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($bold + $borderAll + $vbCenter);
            $setFill("A{$row}:{$L}{$row}",$CLR_LBLUE);
            $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');
            $subtotalCells[]="E{$row}";
            $row+=2;
            continue;
        }

        // ── ETC ──
        if ($sec==='ETC') {
            $sheet->fromArray([['항목명','내용','인원','요금(USD)','수량','합계']],null,"A{$row}");
            $mergeToSheetEnd($row,'F');
            $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($bold + $center + $borderAll + $vbCenter);
            $setFill("A{$row}:{$L}{$row}",$CLR_HGRAY);
            $row++;

            foreach ($sections[$sec] as $it) {
                $label=(string)($it['label']??''); $desc=(string)($it['description']??'');
                $cnt=(float)($it['cnt']??0); $unit=(float)($it['unit']??0); $qty=(float)($it['qty']??0);

                $sheet->fromArray([[ $label, $desc, $cnt, $unit, $qty ]], null, "A{$row}");
                $sheet->setCellValue("F{$row}","=D{$row}*E{$row}");
                $sheet->getStyle("C{$row}:F{$row}")->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');
                $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($borderAll + $vbCenter + $wrap);
                $mergeToSheetEnd($row,'F');
                $row++;
            }

            $endDataRow=$row-1;
            $sheet->mergeCells("A{$row}:E{$row}");
            $sheet->setCellValue("A{$row}",$titles[$sec].' 소계');
            $sheet->setCellValue("F{$row}", ($endDataRow>=$startDataRow) ? "=SUM(F{$startDataRow}:F{$endDataRow})" : 0);
            $mergeToSheetEnd($row,'F');
            $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($bold + $borderAll + $vbCenter);
            $setFill("A{$row}:{$L}{$row}",$CLR_LBLUE);
            $sheet->getStyle("F{$row}")->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');
            $subtotalCells[]="F{$row}";
            $row+=2;
            continue;
        }
    }

    // 총합 & 1인당 (오른쪽 끝 ~ L까지 병합)
    $totalRow=$row;
    $sheet->mergeCells("A{$row}:D{$row}");
    $sheet->setCellValue("A{$row}",'10) TOTAL TOUR FEE');
    $sheet->setCellValue("H{$row}", !empty($subtotalCells)?'='.implode('+',$subtotalCells):0);
    $mergeToSheetEnd($row,'H');
    $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($bold + $vbCenter + $borderAll);
    $setFill("A{$row}:{$L}{$row}",$CLR_BLUE); $setFontColor("A{$row}:{$L}{$row}",$CLR_WHITE);
    $sheet->getStyle("H{$row}")->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');
    $row++;

    $sheet->mergeCells("A{$row}:D{$row}");
    $sheet->setCellValue("A{$row}",'11) 1인당 요금');
    $sheet->setCellValue("H{$row}","=IFERROR(H{$totalRow}/{$paxCell},0)");
    $mergeToSheetEnd($row,'H');
    $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($bold + $vbCenter + $borderAll);
    $setFill("A{$row}:{$L}{$row}",$CLR_BLUE); $setFontColor("A{$row}:{$L}{$row}",$CLR_WHITE);
    $sheet->getStyle("H{$row}")->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');

    // 정렬
    $sheet->getStyle("A1:{$L}{$row}")->getAlignment()->setWrapText(true);
    $sheet->getStyle("A1:{$L}{$row}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    // 출력
    $groupName = trim((string)($master['group_name'] ?? '')) ?: ('GROUP_'.$estimateId);
    $safeGroup = preg_replace('/[^a-zA-Z0-9가-힣_-]+/', '_', $groupName);
    $filename  = "BREAKDOWN_QUOTATION_{$safeGroup}_".date('Ymd').".xlsx";

    @ini_set('zlib.output_compression','0'); while (ob_get_level()) @ob_end_clean();

    $writer = PHPExcel_IOFactory::createWriter($excel,'Excel2007');
    $writer->setPreCalculateFormulas(false);
    $tmp = tempnam(sys_get_temp_dir(),'xl_'); $writer->save($tmp);

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    header('Content-Transfer-Encoding: binary');
    header('Cache-Control: must-revalidate, max-age=0');
    header('Pragma: public'); header('Expires: 0');
    header('Content-Length: '.filesize($tmp));

    $fp=fopen($tmp,'rb'); fpassthru($fp); fclose($fp); @unlink($tmp);
    return true;
}
