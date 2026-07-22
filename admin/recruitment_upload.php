<?php
include "include/header.php";
//include "include/inc_base.php";
if ($_COOKIE['MEMLOGIN_ADMIN_HELLO'] == "") {
    echo "<meta http-equiv='refresh' content='0; url=./login.php'>"; exit;
}

/* =========================
 * SimpleXLSX 로딩
 * ========================= */
$library_paths = array('SimpleXLSX.php', 'lib/SimpleXLSX.php', '../lib/SimpleXLSX.php');
$library_exists = false;
foreach ($library_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        // 네임스페이스 버전 호환
        if (!class_exists('SimpleXLSX') && class_exists('Shuchkin\\SimpleXLSX')) {
            class_alias('Shuchkin\\SimpleXLSX', 'SimpleXLSX');
        }
        if (class_exists('SimpleXLSX')) {
            $library_exists = true;
            break;
        }
    }
}

function normHeader($s) {
    $s = (string)$s;
    $s = trim($s);
    $s = preg_replace('/\s+/u', '', $s);
    $s = preg_replace('/[^\p{L}\p{N}]/u', '', $s);
    return mb_strtolower($s, 'UTF-8');
}
function toIntSafe($v) {
    $v = (string)$v;
    $v = preg_replace('/[^0-9]/', '', $v);
    return ($v === '') ? 0 : (int)$v;
}
function isSummaryCellValue($v) {
    $norm = normHeader($v);
    if ($norm === '') return false;

    // 합계/소계 계열은 부분 포함 허용, 영문은 정확 매칭만 허용
    if (mb_strpos($norm, normHeader('합계')) !== false) return true;
    if (mb_strpos($norm, normHeader('총합')) !== false) return true;
    if (mb_strpos($norm, normHeader('소계')) !== false) return true;

    return in_array($norm, array('total', 'subtotal', 'grandtotal'), true);
}
function isHeaderCellValue($v) {
    $norm = normHeader($v);
    if ($norm === '') return false;

    $headerWords = array('출발일', '출발날짜', '여행사', '상품명', '상품', '투어명', '인원', 'total');
    foreach ($headerWords as $word) {
        if ($norm === normHeader($word)) {
            return true;
        }
    }
    return false;
}
function isSummaryRecruitmentRow($stDate, $agency_name, $p_name) {
    if (isSummaryCellValue($stDate) || isSummaryCellValue($agency_name) || isSummaryCellValue($p_name)) {
        return true;
    }
    return false;
}
function isLikelyDateValue($v) {
    $text = trim((string)$v);
    if ($text === '') return false;

    // Excel serial date (e.g. 45500)
    if (preg_match('/^\d{5}$/', $text)) return true;

    // yyyy-mm-dd / yyyy.mm.dd / yyyy년 m월 d일
    if (preg_match('/(20\d{2})\D{0,3}(1[0-2]|0?[1-9])\D{0,3}(3[01]|[12]?\d)?/u', $text)) return true;

    // m월 d일
    if (preg_match('/(1[0-2]|0?[1-9])\s*월\s*(3[01]|[12]?\d)\s*일?/u', $text)) return true;

    // mm/dd/yyyy
    if (preg_match('/(1[0-2]|0?[1-9])\D{1,3}(3[01]|[12]?\d)\D{1,3}(20\d{2})/u', $text)) return true;

    return (strtotime($text) !== false);
}
function normalizeTextKey($v) {
    $s = trim((string)$v);
    $s = preg_replace('/\s+/u', ' ', $s);
    return mb_strtolower($s, 'UTF-8');
}
function normalizeDateKey($v) {
    $text = trim((string)$v);
    if ($text === '') return '';

    if (preg_match('/^\d{5}$/', $text)) {
        $serial = (int)$text;
        $timestamp = ($serial - 25569) * 86400;
        if ($timestamp > 0) return gmdate('Y-m-d', $timestamp);
    }

    if (preg_match('/(20\d{2})\D{0,3}(1[0-2]|0?[1-9])\D{0,3}(3[01]|[12]?\d)?/u', $text, $m)) {
        $day = isset($m[3]) && $m[3] !== '' ? (int)$m[3] : 1;
        return sprintf('%04d-%02d-%02d', (int)$m[1], (int)$m[2], $day);
    }
    if (preg_match('/(1[0-2]|0?[1-9])\s*월\s*(3[01]|[12]?\d)\s*일?/u', $text, $m)) {
        return sprintf('md-%02d-%02d', (int)$m[1], (int)$m[2]);
    }
    if (preg_match('/(1[0-2]|0?[1-9])\D{1,3}(3[01]|[12]?\d)\D{1,3}(20\d{2})/u', $text, $m)) {
        return sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[1], (int)$m[2]);
    }

    $ts = strtotime($text);
    if ($ts !== false) return date('Y-m-d', $ts);

    return normalizeTextKey($text);
}
function isEmptyRecruitmentRow($stDate, $agency_name, $p_name, $p_cnt, $total_rooms) {
    return ($stDate === '' && $agency_name === '' && $p_name === '' && $p_cnt === 0 && $total_rooms === 0);
}
function shouldSkipRecruitmentRow($stDate, $agency_name, $p_name) {
    // 시트 중간 반복 헤더행(출발일/여행사/상품명 등) 제외
    $headerHit = 0;

    if (isHeaderCellValue($stDate)) $headerHit++;
    if (isHeaderCellValue($agency_name)) $headerHit++;
    if (isHeaderCellValue($p_name)) $headerHit++;

    if ($headerHit >= 2) return true;

    // 병합셀 등으로 여행사 헤더만 단독 노출되는 경우도 헤더로 간주
    return isHeaderCellValue($agency_name);
}

function hasTableColumn($dbConn, $tableName, $columnName) {
    $tableName = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$tableName);
    $columnName = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$columnName);
    if ($tableName === '' || $columnName === '') {
        return false;
    }

    $sql = "SHOW COLUMNS FROM `{$tableName}` LIKE '{$columnName}'";
    $res = $dbConn->query($sql);
    return ($res && $res->num_rows > 0);
}
function findFirstExistingColumn($dbConn, $tableName, $candidates) {
    foreach ($candidates as $col) {
        if (hasTableColumn($dbConn, $tableName, $col)) {
            return $col;
        }
    }
    return null;
}

/**
 * 시트 자동 감지:
 * - 모든 시트를 순회하며 상단 maxScanRows행 안에서
 *   '인원' 헤더가 있는 행을 찾는다.
 * - 같은 헤더행에서 필요한 컬럼 인덱스를 자동 매핑한다.
 */
function detectAllSheetsAndColumns($xlsx, $maxScanRows = 15) {
    $sheetNames = $xlsx->sheetNames();
    $need = array(
        'stDate'      => array('출발일','출발날짜','출발'),
        'agency_name' => array('여행사'),
        'p_name'      => array('상품명','상품','투어명'),
        'p_cnt'       => array('인원',),
        'total'       => array('TOTAL'),
    );
    $detectedSheets = array();

    for ($si = 0; $si < count($sheetNames); $si++) {
        $rows = $xlsx->rows($si);
        if (!$rows || count($rows) == 0) continue;

        $scanLimit = min($maxScanRows, count($rows));
        for ($ri = 0; $ri < $scanLimit; $ri++) {
            $row = $rows[$ri];
            if (!is_array($row)) continue;

            $normalizedCells = array();
            foreach ($row as $ci => $cell) {
                $normalizedCells[$ci] = normHeader($cell);
            }

            // '인원'이 있는지 우선 체크
            $hasPeople = false;
            foreach ($normalizedCells as $cellNorm) {
                if ($cellNorm !== '' && mb_strpos($cellNorm, normHeader('인원')) !== false) {
                    $hasPeople = true;
                    break;
                }
            }
            if (!$hasPeople) continue;

            // 컬럼 매핑
            $colMap = array('stDate'=>null,'agency_name'=>null,'p_name'=>null,'p_cnt'=>null,'total'=>null);

            foreach ($colMap as $key => $dummy) {
                $aliases = $need[$key];
                foreach ($normalizedCells as $ci => $cellNorm) {
                    if ($cellNorm === '') continue;
                    foreach ($aliases as $alias) {
                        $aliasNorm = normHeader($alias);
                        if ($aliasNorm !== '' && mb_strpos($cellNorm, $aliasNorm) !== false) {
                            $colMap[$key] = $ci;
                            break 2;
                        }
                    }
                }
            }

            // 최소 조건: 출발일 + 여행사 + 인원
            // 여행사 컬럼이 없는 양식도 있어 stDate + p_cnt를 최소 조건으로 처리
            if ($colMap['stDate'] !== null && $colMap['p_cnt'] !== null) {
                $detectedSheets[] = array(
                    'sheetIndex' => $si,
                    'sheetName'  => $sheetNames[$si],
                    'headerRow'  => $ri,
                    'colMap'     => $colMap,
                );
                break;
            }
        }
    }
    return $detectedSheets;
}

/* =========================
 * 삭제 처리 (POST only)
 * ========================= */
if (isset($_POST['mode']) && $_POST['mode'] === 'delete') {
    $del_id = isset($_POST['del_id']) ? (int)$_POST['del_id'] : 0;
    if ($del_id <= 0) {
        echo "<script>alert('잘못된 요청입니다.');history.back();</script>";
        exit;
    }

    // 트랜잭션으로 묶어서 안전하게 삭제
    $dbConn->begin_transaction();
    try {
        // 1) 자식 데이터 삭제 (tour_recruitment)
        $stmtD1 = $dbConn->prepare("DELETE FROM tour_recruitment WHERE upload_id = ?");
        $stmtD1->bind_param("i", $del_id);
        $stmtD1->execute();
        $stmtD1->close();

        // 2) 히스토리 삭제 (upload_history)
        $stmtD2 = $dbConn->prepare("DELETE FROM upload_history WHERE idx = ?");
        $stmtD2->bind_param("i", $del_id);
        $stmtD2->execute();
        $affected = $stmtD2->affected_rows;
        $stmtD2->close();

        if ($affected <= 0) {
            throw new Exception("삭제할 히스토리가 없습니다.");
        }

        $dbConn->commit();
        echo "<script>alert('삭제 완료'); location.href='recruitment_upload.php?division=3&pdx=1&sub=25';</script>";
        exit;

    } catch (Exception $e) {
        $dbConn->rollback();
        $msg = addslashes($e->getMessage());
        echo "<script>alert('삭제 실패: {$msg}');history.back();</script>";
        exit;
    }
}

/* =========================
 * 업로드 처리
 * ========================= */
if (isset($_POST['mode']) && $_POST['mode'] == "upload" && $library_exists) {

    $filename = $_FILES['upfile']['name'] ?? '';
    $tmpName  = $_FILES['upfile']['tmp_name'] ?? '';

    if (!$tmpName) {
        echo "<script>alert('업로드 파일이 없습니다.');history.back();</script>";
        exit;
    }

    if (!class_exists('SimpleXLSX')) {
        echo "<script>alert('업로드 실패: SimpleXLSX 클래스를 로드하지 못했습니다. 라이브러리 파일을 확인하세요.');history.back();</script>";
        exit;
    }

    $xlsx = SimpleXLSX::parse($tmpName);
    if (!$xlsx) {
        $parseError = method_exists('SimpleXLSX', 'parseError') ? SimpleXLSX::parseError() : '엑셀 파일 파싱에 실패했습니다.';
        $parseError = addslashes((string)$parseError);
        echo "<script>alert('업로드 실패: {$parseError}');history.back();</script>";
        exit;
    }

    try {
        $dbConn->begin_transaction();

        // 1) 시트/컬럼 자동 감지 (다중 시트)
        $detectedSheets = detectAllSheetsAndColumns($xlsx, 20);
        if (empty($detectedSheets)) {
            throw new Exception('헤더(인원/출발일/여행사)를 가진 시트를 찾지 못했습니다. 엑셀 헤더를 확인하세요.');
        }

        // 2) 히스토리 기록
        $stmtH = $dbConn->prepare("INSERT INTO upload_history (file_name, upload_date) VALUES (?, NOW())");
        if (!$stmtH) {
            throw new Exception('upload_history INSERT prepare 실패: ' . $dbConn->error);
        }
        $stmtH->bind_param("s", $filename);
        if (!$stmtH->execute()) {
            throw new Exception('upload_history INSERT 실행 실패: ' . $stmtH->error);
        }
        $upload_id = $dbConn->insert_id;
        $stmtH->close();

        $has_sheet_name = hasTableColumn($dbConn, 'tour_recruitment', 'sheet_name');
        if ($has_sheet_name) {
            $stmtI = $dbConn->prepare("
                INSERT INTO tour_recruitment (upload_id, stDate, agency_name, p_name, p_cnt, total_rooms, sheet_name)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
        } else {
            $stmtI = $dbConn->prepare("
                INSERT INTO tour_recruitment (upload_id, stDate, agency_name, p_name, p_cnt, total_rooms)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
        }
        if (!$stmtI) {
            throw new Exception('tour_recruitment INSERT prepare 실패: ' . $dbConn->error);
        }

        $success_cnt = 0;
        $saved_sheet_names = array();
        $upload_row_keys = array();

        // 3) 감지된 모든 시트 데이터 저장
        foreach ($detectedSheets as $detected) {
            $sheetIndex = $detected['sheetIndex'];
            $sheetName  = (string)$detected['sheetName'];
            $headerRow  = $detected['headerRow'];
            $col        = $detected['colMap'];
            $rows       = $xlsx->rows($sheetIndex);
            $sheet_row_keys = array();
            $data_started = false;
            $blank_streak = 0;

            // 시트 처리 패턴(재정의):
            // 1) 헤더 다음 행부터 "첫 연속 데이터 블록"만 저장
            // 2) 종료 조건: 합계/총합 행, 반복 헤더행, 빈행 2연속
            // 3) 날짜 형식(stDate)인 행만 데이터로 인정
            // 4) 동일 키(stDate+agency+p_name+p_cnt+total)는 시트 내 1회만 저장
            for ($i = $headerRow + 1; $i < count($rows); $i++) {
                $r = $rows[$i];
                if (!is_array($r)) continue;

                $stDate      = trim((string)($r[$col['stDate']] ?? ''));
                $agency_name = trim((string)($col['agency_name'] !== null ? ($r[$col['agency_name']] ?? '') : ''));
                $p_name      = trim((string)($col['p_name'] !== null ? ($r[$col['p_name']] ?? '') : ''));

                // 합계/총합 행 이후는 같은 시트의 반복 블록으로 간주하고 종료
                if (isSummaryRecruitmentRow($stDate, $agency_name, $p_name)) {
                    if ($data_started) {
                        break;
                    }
                    continue;
                }

                $p_cnt_raw   = (string)($r[$col['p_cnt']] ?? '');
                $p_cnt       = toIntSafe($p_cnt_raw);

                $total_rooms = 0;
                if ($col['total'] !== null) {
                    $total_rooms = toIntSafe($r[$col['total']] ?? '');
                }

                // 완전 빈줄 2개 연속이면 현재 데이터 블록 종료
                if (isEmptyRecruitmentRow($stDate, $agency_name, $p_name, $p_cnt, $total_rooms)) {
                    if ($data_started) {
                        $blank_streak++;
                        if ($blank_streak >= 2) {
                            break;
                        }
                    }
                    continue;
                }
                $blank_streak = 0;

                // 데이터 블록 시작 이후에 반복 헤더를 만나면 종료
                if (shouldSkipRecruitmentRow($stDate, $agency_name, $p_name)) {
                    if ($data_started) {
                        break;
                    }
                    continue;
                }

                // 날짜 형식이 아닌 행은 데이터로 취급하지 않음
                if (!isLikelyDateValue($stDate)) {
                    continue;
                }

                if ($agency_name === '') {
                    $agency_name = trim($sheetName);
                }
                if ($agency_name === '') {
                    continue;
                }

                $row_key = mb_strtolower(trim($stDate), 'UTF-8') . '|' .
                           mb_strtolower(trim($agency_name), 'UTF-8') . '|' .
                           mb_strtolower(trim($p_name), 'UTF-8') . '|' .
                           (string)$p_cnt . '|' . (string)$total_rooms;
                if (isset($sheet_row_keys[$row_key])) {
                    continue;
                }
                $global_row_key = normalizeDateKey($stDate) . '|' .
                                  normalizeTextKey($agency_name) . '|' .
                                  normalizeTextKey($p_name) . '|' .
                                  (string)$p_cnt . '|' . (string)$total_rooms;
                if ($global_row_key !== '||||' && isset($upload_row_keys[$global_row_key])) {
                    continue;
                }

                if ($has_sheet_name) {
                    $sheet_name_to_save = $sheetName;
                    $stmtI->bind_param("isssiis", $upload_id, $stDate, $agency_name, $p_name, $p_cnt, $total_rooms, $sheet_name_to_save);
                } else {
                    $stmtI->bind_param("isssii", $upload_id, $stDate, $agency_name, $p_name, $p_cnt, $total_rooms);
                }
                if (!$stmtI->execute()) {
                    throw new Exception('tour_recruitment INSERT 실행 실패: ' . $stmtI->error);
                }
                $sheet_row_keys[$row_key] = true;
                if ($global_row_key !== '||||') {
                    $upload_row_keys[$global_row_key] = true;
                }
                $success_cnt++;
                $saved_sheet_names[$sheetName] = true;
                $data_started = true;
            }
        }
        $stmtI->close();

        // 4) 업로드 단위 중복 제거(최후 안전장치)
        $dedup_removed = 0;
        $row_id_col = findFirstExistingColumn($dbConn, 'tour_recruitment', array('idx', 'id', 'seq', 'no'));
        if ($row_id_col !== null) {
            $row_id_col_safe = preg_replace('/[^a-zA-Z0-9_]/', '', $row_id_col);
            $sqlDedup = "
                DELETE t1
                FROM tour_recruitment t1
                INNER JOIN tour_recruitment t2
                    ON t1.upload_id = t2.upload_id
                   AND IFNULL(TRIM(t1.stDate), '') = IFNULL(TRIM(t2.stDate), '')
                   AND IFNULL(TRIM(t1.agency_name), '') = IFNULL(TRIM(t2.agency_name), '')
                   AND IFNULL(TRIM(t1.p_name), '') = IFNULL(TRIM(t2.p_name), '')
                   AND IFNULL(t1.p_cnt, 0) = IFNULL(t2.p_cnt, 0)
                   AND IFNULL(t1.total_rooms, 0) = IFNULL(t2.total_rooms, 0)
                   AND t1.`{$row_id_col_safe}` > t2.`{$row_id_col_safe}`
                WHERE t1.upload_id = ?
            ";
            $stmtDedup = $dbConn->prepare($sqlDedup);
            if ($stmtDedup) {
                $stmtDedup->bind_param("i", $upload_id);
                if (!$stmtDedup->execute()) {
                    throw new Exception('tour_recruitment 중복 제거 실패: ' . $stmtDedup->error);
                }
                $dedup_removed = (int)$stmtDedup->affected_rows;
                $stmtDedup->close();
            }
        }

        // 5) row_count는 dedup 이후 실제 저장 건수로 계산
        $stmtC = $dbConn->prepare("SELECT COUNT(*) FROM tour_recruitment WHERE upload_id = ?");
        if (!$stmtC) {
            throw new Exception('tour_recruitment COUNT prepare 실패: ' . $dbConn->error);
        }
        $stmtC->bind_param("i", $upload_id);
        if (!$stmtC->execute()) {
            throw new Exception('tour_recruitment COUNT 실행 실패: ' . $stmtC->error);
        }
        $stmtC->bind_result($count_after_dedup);
        $stmtC->fetch();
        $stmtC->close();
        $success_cnt = (int)$count_after_dedup;

        // 6) row_count 업데이트
        $stmtU = $dbConn->prepare("UPDATE upload_history SET row_count = ? WHERE idx = ?");
        if (!$stmtU) {
            throw new Exception('upload_history UPDATE prepare 실패: ' . $dbConn->error);
        }
        $stmtU->bind_param("ii", $success_cnt, $upload_id);
        if (!$stmtU->execute()) {
            throw new Exception('upload_history UPDATE 실행 실패: ' . $stmtU->error);
        }
        $stmtU->close();

        $dbConn->commit();

        $sheet_count = count($saved_sheet_names);
        $msg = "{$success_cnt} 건 업로드 완료 (처리 시트: {$sheet_count}개)";
        if ($dedup_removed > 0) {
            $msg .= " / 중복 제거 {$dedup_removed}건";
        }
        $msg = addslashes($msg);
        echo "<script>alert('{$msg}'); location.href='recruitment_upload.php?division=3&pdx=1&sub=25';</script>";
        exit;
    } catch (Exception $e) {
        $dbConn->rollback();
        $msg = addslashes($e->getMessage());
        echo "<script>alert('업로드 실패: {$msg}');history.back();</script>";
        exit;
    }
}

if (isset($_POST['mode']) && $_POST['mode'] == "upload" && !$library_exists) {
    echo "<script>alert('업로드 실패: SimpleXLSX 라이브러리를 찾지 못했거나 클래스 로드에 실패했습니다.');history.back();</script>";
    exit;
}
?>

<div id="contentwrapper">
    <div class="main_content">
        <div class="panel panel-default">
            <div class="panel-heading"><h3 class="panel-title">엑셀 업로드</h3></div>
            <div class="panel-body">
                <form action="" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="mode" value="upload">
                    <input type="file" name="upfile" class="form-control" accept=".xlsx" required><br>
                    <button type="submit" class="btn btn-primary">데이터 업로드</button>
                </form>
                <?php if (!$library_exists) { ?>
                    <div class="alert alert-danger" style="margin-top:10px;">
                        SimpleXLSX.php 라이브러리를 찾을 수 없습니다. (SimpleXLSX.php / lib/SimpleXLSX.php / ../lib/SimpleXLSX.php)
                    </div>
                <?php } ?>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <h4>업로드 히스토리</h4>
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr class="info">
                            <th>번호</th>
                            <th>파일명</th>
                            <th>업로드 일시</th>
                            <th>데이터 수</th>
                            <th style="width:220px;">관리</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $h_res = $dbConn->query("SELECT * FROM upload_history ORDER BY idx DESC");
                        while($h_row = mysqli_fetch_assoc($h_res)){
                        ?>
                        <tr>
                            <td><?= (int)$h_row['idx'] ?></td>
                            <td><?= htmlspecialchars($h_row['file_name']) ?></td>
                            <td><?= htmlspecialchars($h_row['upload_date']) ?></td>
                            <td><?= number_format((int)$h_row['row_count']) ?>건</td>
                            <td>
                                <a href="recruitment_trend.php?upload_id=<?=(int)$h_row['idx']?>&division=3&pdx=1&sub=25"
                                   class="btn btn-xs btn-default">데이터 보기</a>

                                <form method="post" action="" style="display:inline-block;"
                                      onsubmit="return confirm('이 업로드 히스토리와 연결된 데이터까지 모두 삭제됩니다.\n삭제하시겠습니까?');">
                                    <input type="hidden" name="mode" value="delete">
                                    <input type="hidden" name="del_id" value="<?=(int)$h_row['idx']?>">
                                    <button type="submit" class="btn btn-xs btn-danger">삭제</button>
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

<?php include "include/side_m.php"; ?>
</body>
</html>
