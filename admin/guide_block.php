<?php
// 1. 에러 설정
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', 1);

include "include/inc_base.php";             
// ※ inc_base.php에서 $dbConn(mysqli) DB 연결이 되어 있어야 합니다.

function guide_block_escape($value) {
    global $dbConn;
    return mysqli_real_escape_string($dbConn, (string)$value);
}

function guide_block_is_date($value) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$value)) {
        return false;
    }

    list($year, $month, $day) = explode('-', $value);
    return checkdate((int)$month, (int)$day, (int)$year);
}

function guide_block_render_unassigned_months($groupedItems) {
    if (empty($groupedItems)) {
        echo '<div class="unassigned-heading-label">가이드 배정 대기 상품 목록</div>';
        echo '<div class="unassigned-zone">';
        echo '<div style="color:#7f8c8d; padding:10px 0;">검색 결과가 없습니다.</div>';
        echo '</div>';
        return;
    }
?>
<div class="unassigned-heading-label">가이드 배정 대기 상품 목록</div>
<div class="unassigned-zone">
    <div class="month-sections">
        <?php foreach($groupedItems as $monthKey => $monthItems): ?>
        <div class="month-section" data-month="<?= htmlspecialchars($monthKey) ?>">
            <div class="month-heading"><?= date('Y년 m월', strtotime($monthKey . '-01')) ?> (<?= count($monthItems) ?>)</div>
            <div class="card-container">
                <?php foreach($monthItems as $item):
                    $stTxt = date('m/d', strtotime($item['stDate']));
                    $title = "[{$stTxt}] " . $item['p_name'];
                    $paxCnt = isset($item['p_cnt_total']) ? (int)$item['p_cnt_total'] : 0;

                    if (defined('JSON_UNESCAPED_UNICODE')) {
                        $jsonRaw = json_encode($item, JSON_UNESCAPED_UNICODE);
                    } else {
                        $jsonRaw = json_encode($item);
                    }
                    if($jsonRaw === false) $jsonRaw = '{}';
                    $base64Str = base64_encode($jsonRaw);
                ?>
                <?php
                    $isRegisteredGuide = !empty($item['tour_guide_cnt']);
                    $isPreRegistered = !$isRegisteredGuide && !empty($item['assign_cnt']);
                    $cardClass = $isRegisteredGuide ? 'card is-disabled' : 'card js-click-modal';
                    if ($isPreRegistered) {
                        $cardClass .= ' is-pre-registered';
                    }
                ?>
                <div class="<?= $cardClass ?>"
                     <?php if(!$isRegisteredGuide): ?>
                     data-mode="new"
                     data-b64="<?=$base64Str?>"
                     <?php endif; ?>
                     title="<?= $isRegisteredGuide ? 'tour_guide에 이미 등록된 상품입니다. / 인원: ' . $paxCnt . '명' : '인원: ' . $paxCnt . '명' ?>">
                    <?php if($isRegisteredGuide): ?>
                        <span class="badge-disabled">등록됨</span>
                    <?php elseif($isPreRegistered): ?>
                        <span class="badge-pre">가등록</span>
                    <?php endif; ?>
                    <div class="card-title" title="<?= htmlspecialchars($title) ?>"><?= htmlspecialchars($title) ?></div>
                    <div class="card-date"><?= htmlspecialchars($item['stDate']) ?> (<?= (int)$item['p_day'] ?>일 / <?= $paxCnt ?>명)</div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php
}

/* =================================================================================
 * [Backend] 1. 파라미터 및 날짜 설정
 * ================================================================================= */

if (empty($_COOKIE['MEMLOGIN_ADMIN_HELLO'])) {
    echo "<script>alert('관리자 로그인이 필요합니다.'); window.close();</script>";
    exit;
}

// 1. 파라미터 수신
$viewYear  = isset($_GET['year']) ? $_GET['year'] : date('Y');
$viewMonth = isset($_GET['month']) ? $_GET['month'] : date('m');
$filterStart = (isset($_GET['filter_start']) && guide_block_is_date($_GET['filter_start'])) ? $_GET['filter_start'] : '';
$filterEnd   = (isset($_GET['filter_end']) && guide_block_is_date($_GET['filter_end'])) ? $_GET['filter_end'] : '';
$productKeyword = '';
$hasDateFilter = ($filterStart !== '' && $filterEnd !== '');

// 2. 날짜 계산
$monthStart = date("{$viewYear}-{$viewMonth}-01"); 
$defaultStart = date("Y-m-01", strtotime($monthStart . " -1 month"));
$defaultEnd = date("Y-m-t", strtotime($monthStart . " +10 months"));

if ($hasDateFilter) {
    if (strtotime($filterStart) > strtotime($filterEnd)) {
        $tmp = $filterStart;
        $filterStart = $filterEnd;
        $filterEnd = $tmp;
    }

    $sDate = $filterStart;
    $eDate = $filterEnd;
} else {
    $sDate = $defaultStart;
    $eDate = $defaultEnd;
}

$dateInputStart = $filterStart !== '' ? $filterStart : $sDate;
$dateInputEnd = $filterEnd !== '' ? $filterEnd : $eDate;

// 3. 네비게이션
$today    = date('Y-m-d');
$prevDate = strtotime("-1 month", strtotime($monthStart));
$nextDate = strtotime("+1 month", strtotime($monthStart));

// 4. 날짜 배열 생성 (X축)
$dateHeaders = array();
$dStart = new DateTime($sDate);
$dEnd   = new DateTime($eDate);
$dEnd->modify('+1 day');
$interval = new DateInterval('P1D');
$period   = new DatePeriod($dStart, $interval, $dEnd);

foreach($period as $dt) {
    $dateHeaders[] = $dt->format('Y-m-d');
}

$dateHeaderMonths = array();
foreach($dateHeaders as $dt) {
    $monthKey = date('Y-m', strtotime($dt));
    if (!isset($dateHeaderMonths[$monthKey])) {
        $dateHeaderMonths[$monthKey] = array(
            'key' => $monthKey,
            'label' => date('Y년 m월', strtotime($dt)),
            'colspan' => 0
        );
    }
    $dateHeaderMonths[$monthKey]['colspan']++;
}

$tableName = "tour_guide_block";
$chk_tbl = mysqli_query($dbConn, "SHOW TABLES LIKE '$tableName'");
$tableExists = ($chk_tbl && mysqli_num_rows($chk_tbl) > 0);

if (!$tableExists) {
    $createTableSql = "CREATE TABLE IF NOT EXISTS $tableName (
        seq_no INT(11) NOT NULL AUTO_INCREMENT,
        grand_eCode VARCHAR(50) DEFAULT '',
        guide_id VARCHAR(50) NOT NULL DEFAULT '',
        stDate DATE NOT NULL,
        edDate DATE NOT NULL,
        memo TEXT,
        color_code VARCHAR(20) DEFAULT '#3498db',
        userid VARCHAR(50) DEFAULT '',
        wdate DATETIME DEFAULT NULL,
        PRIMARY KEY (seq_no),
        KEY idx_guide_date (guide_id, stDate, edDate),
        KEY idx_grand_ecode (grand_eCode)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if (mysqli_query($dbConn, $createTableSql)) {
        $tableExists = true;
    } else {
        echo "<script>alert('tour_guide_block 테이블 생성 실패: " . addslashes(mysqli_error($dbConn)) . "'); history.back();</script>";
        exit;
    }
}

/* =================================================================================
 * [Backend] 2. 데이터 처리 (등록/수정/삭제)
 * ================================================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mode'])) {
    
    $grand_eCode = isset($_POST['grand_eCode']) ? guide_block_escape($_POST['grand_eCode']) : '';
    $uid         = isset($_COOKIE['MEMLOGIN_ID']) ? guide_block_escape($_COOKIE['MEMLOGIN_ID']) : 'admin';

    // A. 가이드 배정 등록 및 수정
    if ($_POST['mode'] === 'assign') {
        $seq_no     = isset($_POST['seq_no']) ? guide_block_escape($_POST['seq_no']) : '';
        $guide_id   = isset($_POST['guide_id']) ? guide_block_escape($_POST['guide_id']) : '';
        $start_date = isset($_POST['stDate']) ? guide_block_escape($_POST['stDate']) : '';
        $end_date   = isset($_POST['edDate']) ? guide_block_escape($_POST['edDate']) : '';
        $memo       = isset($_POST['memo']) ? guide_block_escape($_POST['memo']) : '';
        $color_code = isset($_POST['color_code']) ? guide_block_escape($_POST['color_code']) : '#3498db';
        $p_cnt      = isset($_POST['p_cnt']) ? (int)$_POST['p_cnt'] : 0;

        // auto 블록 여부를 먼저 확인 (guide_id 검사 전)
        $isAuto = false;
        if ($seq_no) {
            $chkAuto = mysqli_query($dbConn, "SELECT userid FROM $tableName WHERE seq_no = '$seq_no'");
            $chkRow  = $chkAuto ? mysqli_fetch_assoc($chkAuto) : null;
            $isAuto  = ($chkRow && $chkRow['userid'] === 'auto');
        }

        if (empty($guide_id) && !$isAuto) {
            echo "<script>alert('가이드는 반드시 지정해야 합니다.'); history.back();</script>";
            exit;
        }
        if ($seq_no) {
            if ($isAuto) {
                // auto 블록: 색상·메모만 수정 (연동 필드 보호)
                $qry = "UPDATE $tableName
                        SET color_code = '$color_code',
                            memo = '$memo',
                            wdate = NOW()
                        WHERE seq_no = '$seq_no'";
            } else {
                $qry = "UPDATE $tableName
                        SET grand_eCode = '$grand_eCode',
                            guide_id = '$guide_id',
                            stDate = '$start_date',
                            edDate = '$end_date',
                            memo = '$memo',
                            p_cnt = '$p_cnt',
                            color_code = '$color_code',
                            wdate = NOW()
                        WHERE seq_no = '$seq_no'";
            }
        } else {
            // [신규] - 중복 날짜 허용
            $qry = "INSERT INTO $tableName (grand_eCode, guide_id, stDate, edDate, memo, p_cnt, color_code, userid, wdate)
                    VALUES ('$grand_eCode', '$guide_id', '$start_date', '$end_date', '$memo', '$p_cnt', '$color_code', '$uid', NOW())";
        }
        
        if (!mysqli_query($dbConn, $qry)) {
            echo "<script>alert('DB Error: " . addslashes(mysqli_error($dbConn)) . "'); history.back();</script>";
            exit;
        }
    }
    // B. 배정 삭제
    else if ($_POST['mode'] === 'delete') {
        $seq_no = isset($_POST['seq_no']) ? guide_block_escape($_POST['seq_no']) : '';
        $chkDel = mysqli_query($dbConn, "SELECT userid FROM $tableName WHERE seq_no = '$seq_no'");
        $delRow = $chkDel ? mysqli_fetch_assoc($chkDel) : null;
        if ($delRow && $delRow['userid'] === 'auto') {
            echo "<script>alert('가이드 연동 블록은 삭제할 수 없습니다.\\n가이드 배정 화면에서 배정을 해제하면 자동 삭제됩니다.'); history.back();</script>";
            exit;
        }
        $qry = "DELETE FROM $tableName WHERE seq_no = '$seq_no'";
        if(mysqli_query($dbConn, $qry)) {
            echo "<script>alert('정상적으로 삭제되었습니다.');</script>";
        } else {
            echo "<script>alert('삭제 실패: " . addslashes(mysqli_error($dbConn)) . "'); history.back();</script>";
            exit;
        }
    }

    $redirectParams = array('year' => $viewYear, 'month' => $viewMonth);
    if ($filterStart !== '' && $filterEnd !== '') {
        $redirectParams['filter_start'] = $filterStart;
        $redirectParams['filter_end'] = $filterEnd;
    }
    $qs = http_build_query($redirectParams);
    echo "<script>location.href='{$_SERVER['PHP_SELF']}?{$qs}';</script>";
    exit;
}

/* =================================================================================
 * [Backend] 2.5 자동 블록 동기화 (tour_guide ↔ tour_guide_block)
 *
 *   매칭 키: (grand_eCode + stDate + bus_num)
 *   - 가이드 변경       → 같은 차량의 자동 블록 guide_id UPDATE (색상·메모는 보존)
 *   - p_day 변경        → edDate UPDATE
 *   - 가이드 배정 해제  → 자동 블록 DELETE
 *   - 신규 가이드 배정  → 자동 블록 INSERT
 *   인원수(p_cnt_total)는 표시 시점에 reserve_info로 동적 계산 → 별도 갱신 불필요.
 *   수동 등록 블록(userid != 'auto')은 영향 받지 않음.
 * ================================================================================= */
if ($tableExists) {
    // 0) bus_num 컬럼 보장 (idempotent)
    $colChk = mysqli_query($dbConn, "SHOW COLUMNS FROM $tableName LIKE 'bus_num'");
    if ($colChk && mysqli_num_rows($colChk) === 0) {
        @mysqli_query($dbConn, "ALTER TABLE $tableName ADD COLUMN bus_num VARCHAR(20) DEFAULT '' AFTER guide_id");
        @mysqli_query($dbConn, "ALTER TABLE $tableName ADD KEY idx_bus_lookup (grand_eCode, stDate, bus_num)");
    }

    // 0-1) p_cnt 컬럼 보장 (idempotent)
    $colChk2 = mysqli_query($dbConn, "SHOW COLUMNS FROM $tableName LIKE 'p_cnt'");
    if ($colChk2 && mysqli_num_rows($colChk2) === 0) {
        @mysqli_query($dbConn, "ALTER TABLE $tableName ADD COLUMN p_cnt INT DEFAULT 0 AFTER memo");
    }

    // 1) 기존 자동 블록의 bus_num 백필 (1회성 마이그레이션)
    @mysqli_query(
        $dbConn,
        "UPDATE $tableName B
           INNER JOIN tour_guide TG
              ON TG.grand_eCode = B.grand_eCode
             AND TG.guide_id   = B.guide_id
             AND TG.stDate     = B.stDate
            SET B.bus_num = TG.bus_num
          WHERE B.userid  = 'auto'
            AND B.bus_num = ''"
    );

    // 2) 가이드 변경 동기화: 같은 차량의 가이드가 바뀌면 guide_id UPDATE
    @mysqli_query(
        $dbConn,
        "UPDATE $tableName B
           INNER JOIN tour_guide TG
              ON TG.grand_eCode = B.grand_eCode
             AND TG.bus_num    = B.bus_num
             AND TG.stDate     = B.stDate
            SET B.guide_id = TG.guide_id,
                B.wdate    = NOW()
          WHERE B.userid    = 'auto'
            AND B.stDate    >= '$today'
            AND B.bus_num   <> ''
            AND TG.guide_id <> ''
            AND TG.guide_id <> B.guide_id"
    );

    // 3) edDate 동기화 (p_day 변경 반영)
    @mysqli_query(
        $dbConn,
        "UPDATE $tableName B
           INNER JOIN tour_guide TG
              ON TG.grand_eCode = B.grand_eCode
             AND TG.bus_num    = B.bus_num
             AND TG.stDate     = B.stDate
           INNER JOIN product_master PM ON PM.p_code = TG.p_code
            SET B.edDate = DATE_ADD(B.stDate, INTERVAL (IFNULL(PM.p_day, 1) - 1) DAY),
                B.wdate  = NOW()
          WHERE B.userid  = 'auto'
            AND B.stDate  >= '$today'
            AND B.edDate <> DATE_ADD(B.stDate, INTERVAL (IFNULL(PM.p_day, 1) - 1) DAY)"
    );

    // 4) tour_guide에서 사라진 자동 블록 DELETE
    @mysqli_query(
        $dbConn,
        "DELETE B FROM $tableName B
          WHERE B.userid = 'auto'
            AND B.stDate >= '$today'
            AND NOT EXISTS (
                SELECT 1
                  FROM tour_guide TG
                 WHERE TG.grand_eCode = B.grand_eCode
                   AND TG.bus_num    = B.bus_num
                   AND TG.stDate     = B.stDate
                   AND TG.guide_id  <> ''
            )"
    );

    // 5) 신규 tour_guide 행에 대해 자동 블록 INSERT
    $autoBlockSql = "INSERT INTO $tableName
                        (grand_eCode, guide_id, bus_num, stDate, edDate, memo, color_code, userid, wdate)
                     SELECT TG.grand_eCode,
                            TG.guide_id,
                            TG.bus_num,
                            TG.stDate,
                            DATE_ADD(TG.stDate, INTERVAL (IFNULL(PM.p_day, 1) - 1) DAY) AS edDate,
                            ''           AS memo,
                            '#16a085'   AS color_code,
                            'auto'      AS userid,
                            NOW()       AS wdate
                       FROM tour_guide TG
                       LEFT JOIN product_master PM ON TG.p_code = PM.p_code
                      WHERE TG.guide_id IS NOT NULL
                        AND TG.guide_id <> ''
                        AND TG.stDate   >= '$today'
                        AND NOT EXISTS (
                            SELECT 1
                              FROM $tableName B
                             WHERE B.grand_eCode = TG.grand_eCode
                               AND B.bus_num    = TG.bus_num
                               AND B.stDate     = TG.stDate
                        )";
    @mysqli_query($dbConn, $autoBlockSql);

    // 기존 '[자동등록]' 메모 일괄 제거
    @mysqli_query($dbConn, "UPDATE $tableName SET memo = '' WHERE userid = 'auto' AND memo = '[자동등록]'");
}

/* =================================================================================
 * [Backend] 3. 데이터 조회
 * ================================================================================= */

// 3-1. 가이드 목록 (Y축)
$guideList = array();
$qry_guide = "SELECT userid, kor_name, cell_phone, division 
              FROM member_list 
              WHERE division = 'guide' 
                AND (out_yn IS NULL OR out_yn = '' OR out_yn = 'n')
              ORDER BY kor_name ASC";

$rst_guide = mysqli_query($dbConn, $qry_guide);
if ($rst_guide) {
    while($row = mysqli_fetch_assoc($rst_guide)) {
        $guideList[] = $row;
    }
}

// 3-2. 배정 데이터 조회
$scheduleMap = array(); 

if($tableExists) {
    $assignDateWhere = "(B.stDate <= '$eDate' AND B.edDate >= '$sDate')";
    $assignWhere = $assignDateWhere;

    $qry_assign = "SELECT B.*, M.p_code AS m_p_code, P.p_name, P.p_day,
                          (SELECT IFNULL(SUM(RI.p_cnt), 0)
                             FROM reserve_info RI
                            WHERE RI.p_code = M.p_code
                              AND RI.stDate = B.stDate
                              AND RI.rev_status != 'CANCEL') AS p_cnt_total
                   FROM $tableName B
                   LEFT JOIN tour_master M ON B.grand_eCode = M.grand_eCode
                   LEFT JOIN product_master P ON M.p_code = P.p_code
                   WHERE $assignWhere
                   ORDER BY B.stDate ASC, B.seq_no ASC";

    $rst_assign = mysqli_query($dbConn, $qry_assign);
    if ($rst_assign) {
        while($row = mysqli_fetch_assoc($rst_assign)) {
            if ($row['stDate'] > $eDate || $row['edDate'] < $sDate) {
                continue;
            }

            if (empty($row['p_name'])) {
                $row['p_name'] = $row['memo'] ? $row['memo'] : "(일정명 미입력)";
                $row['is_unassigned_product'] = true;
            } else {
                $row['is_unassigned_product'] = false;
            }

            $gid = $row['guide_id'];
            
            $displayStart = ($row['stDate'] < $sDate) ? $sDate : $row['stDate'];
            $displayEnd   = ($row['edDate'] > $eDate) ? $eDate : $row['edDate'];
            
            $d1 = new DateTime($displayStart);
            $d2 = new DateTime($displayEnd);
            $diff = $d1->diff($d2);
            $colspan = $diff->days + 1; 
            
            $scheduleMap[$gid][$displayStart][] = array(
                'colspan' => $colspan,
                'info'    => $row
            );
        }
    }
}

// 3-3. 대기 목록
$unassignedListData = array();
if($tableExists) {
    $listDateWhere = "(M.stDate <= '$eDate' AND DATE_ADD(M.stDate, INTERVAL (IFNULL(P.p_day, 1) - 1) DAY) >= '$sDate')";
    $listFilterWhere = $listDateWhere;

    $qry_list = "SELECT M.grand_eCode, M.stDate, P.p_name, IFNULL(P.p_day, 1) as p_day, 
                    DATE_ADD(M.stDate, INTERVAL (IFNULL(P.p_day, 1) - 1) DAY) as edDate,
                    (SELECT IFNULL(SUM(RI.p_cnt), 0)
                       FROM reserve_info RI
                      WHERE RI.p_code = M.p_code
                        AND RI.stDate = M.stDate
                        AND RI.rev_status != 'CANCEL') as p_cnt_total,
                    (SELECT count(*) FROM $tableName WHERE grand_eCode = M.grand_eCode) as assign_cnt,
                    (SELECT count(*) FROM tour_guide TG WHERE TG.grand_eCode = M.grand_eCode AND TG.stDate = M.stDate) as tour_guide_cnt
                 FROM tour_master M
                 LEFT JOIN product_master P ON M.p_code = P.p_code
                 LEFT JOIN reserve_info R ON M.grand_eCode = R.reserveCode
                  WHERE 
                      $listFilterWhere
                  AND M.stDate >= '$today'
                  AND (R.rev_status IS NULL OR R.rev_status != 'CANCEL') 
                  GROUP BY M.grand_eCode
                 ORDER BY M.stDate ASC";

    $rst_list = mysqli_query($dbConn, $qry_list);
    if ($rst_list) {
        while($row = mysqli_fetch_assoc($rst_list)) {
            $unassignedListData[] = $row;
        }
    }
}

$unassignedGroupedByMonth = array();
for($m = 1; $m <= 12; $m++) {
    $monthKey = sprintf('%04d-%02d', (int)$viewYear, $m);
    $unassignedGroupedByMonth[$monthKey] = array();
}
foreach($unassignedListData as $item) {
    $monthKey = date('Y-m', strtotime($item['stDate']));
    if (date('Y', strtotime($item['stDate'])) != (string)((int)$viewYear)) {
        continue;
    }
    $unassignedGroupedByMonth[$monthKey][] = $item;
}
ksort($unassignedGroupedByMonth);

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>가이드 배정 스케줄러</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Noto Sans KR', sans-serif; background: #f5f6fa; margin: 0; padding: 20px; font-size: 12px; color: #2f3640; }
        
        .control-bar { background: #fff; padding: 10px 15px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .month-control { display: flex; align-items: center; gap: 20px; }
        .month-title { font-size: 20px; font-weight: 700; color: #2c3e50; }
        .btn-nav { background: #ecf0f1; border: 1px solid #bdc3c7; padding: 6px 12px; border-radius: 4px; text-decoration: none; color: #333; font-weight: 600; }
        .btn-today { background: #3498db; color: #fff; border-color: #2980b9; }
        .btn-manual { background: #8e44ad; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-weight: bold; cursor: pointer; border: 1px solid #9b59b6; }
        .filter-bar { background: #fff; padding: 10px 15px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 8px; margin-bottom: 15px; flex-wrap: wrap; }
        .filter-bar label { font-size: 11px; color: #636e72; font-weight: 600; }
        .filter-bar input[type="date"], .filter-bar input[type="text"] { border: 1px solid #bdc3c7; border-radius: 4px; padding: 5px 8px; font-size: 12px; }
        .filter-bar input[type="text"] { width: 190px; }
        .filter-btn { border: 1px solid #27ae60; background: #27ae60; color: #fff; border-radius: 4px; padding: 6px 12px; font-weight: 600; cursor: pointer; }

        .unassigned-zone {
            background: #fff;
            padding: 0 12px 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid #f1c40f;
            height: 240px;
            overflow: scroll;
            box-sizing: border-box;
        }
        /* 제목은 스크롤 영역 밖(정적)으로 빼서 상단 비침 원인 제거 */
        .unassigned-heading-label { font-weight: bold; margin-bottom: 8px; }
        .month-sections { display: flex; align-items: stretch; gap: 12px; min-width: max-content; }
        .month-section { flex: 0 0 660px; min-height: 205px; }
        .month-heading {
            position: sticky;
            top: 0; /* 스크롤 영역 최상단에 붙여 섹션 폭 전체를 불투명하게 덮음 */
            z-index: 30;
            background: #fff;
            font-weight: 800;
            color: #2c3e50;
            margin-bottom: 6px;
            padding: 8px 0 4px;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .card-container { display: flex; gap: 8px; flex-wrap: wrap; padding-bottom: 5px; align-items: flex-start; min-height: 150px; }
        .card {
            background: #fff3e0; border: 1px solid #ffe0b2; padding: 6px; border-radius: 4px;
            min-width: 150px; width: 150px; flex: 0 0 auto;
            cursor: pointer; transition: 0.2s; position: relative; z-index: 0;
            height: auto; display: flex; flex-direction: column; justify-content: space-between;
            margin-bottom: 6px;
        }
        .card:hover { border-color: #ffb74d; box-shadow: 0 2px 5px rgba(0,0,0,0.1); transform: translateY(-2px); }
        .card.is-pre-registered { background: #e8f4fd; border-color: #85c1e9; }
        .card.is-disabled { background: #ecf0f1; border-color: #d5d8dc; color: #95a5a6; cursor: not-allowed; opacity: 0.85; pointer-events: none; }
        .card.is-disabled:hover { transform: none; box-shadow: none; border-color: #d5d8dc; }
        .badge-pre { position: absolute; top: 4px; right: 4px; background: #2980b9; color: #fff; font-size: 9px; line-height: 1; padding: 3px 5px; border-radius: 8px; font-weight: 700; }
        .badge-disabled { position: absolute; top: 4px; right: 4px; background: #95a5a6; color: #fff; font-size: 9px; line-height: 1; padding: 3px 5px; border-radius: 8px; font-weight: 700; }
        .card-title { font-weight: bold; margin-bottom: 5px; white-space: normal; word-break: keep-all; line-height: 1.3; font-size: 10px; }
        .card-date { font-size: 10px; color: #e67e22; margin-top: 5px; text-align: right; }

        .grid-wrapper { background: #fff; border-radius: 8px; overflow: auto; box-shadow: 0 1px 3px rgba(0,0,0,0.1); height: calc(100vh - 430px); min-height: 300px; }
        table.scheduler-table { width: 100%; border-collapse: separate; border-spacing: 0; min-width: 1400px; table-layout: fixed; }
        th, td { border: 1px solid #dfe6e9; border-top: 0; border-left: 0; padding: 0; text-align: center; vertical-align: top; position: relative; }
        
        thead th { background: #2d3436; color: #fff; position: sticky; top: 0; z-index: 10; padding: 8px 0; height: 30px; }
        thead tr.month-row th { top: 0; height: 24px; padding: 4px 0; background: #34495e; z-index: 40; }
        thead tr.day-row th { top: 32px; z-index: 45; }
        .month-group { font-size: 12px; font-weight: 700; border-bottom: 1px solid #243342; }
        .fixed-col { position: sticky; left: 0; z-index: 20; background: #dfe6e9; color: #2d3436; width: 100px; min-width: 100px; font-weight: 600; border-right: 2px solid #b2bec3; }
        thead tr.month-row th.fixed-col { top: 0; left: 0; z-index: 60; background: #636e72; color: #fff; }
        thead tr.day-row th { background: #2d3436; }

        .merged-cell {
            color: white; cursor: pointer; border-radius: 4px;
            box-sizing: border-box; padding: 4px 6px; margin: 2px;
            min-height: 26px; line-height: 1.3;
            white-space: normal; word-break: keep-all; overflow-wrap: anywhere;
            box-shadow: 0 1px 2px rgba(0,0,0,0.15);
            position: relative; z-index: 5; font-size: 11px;
            display: block; text-align: left;
        }
        .merged-cell:hover { opacity: 0.9; transform: translateY(-1px); z-index: 15; box-shadow: 0 3px 6px rgba(0,0,0,0.2); }
        .merged-cell.no-product {
            background-image: linear-gradient(45deg, rgba(255,255,255,0.15) 25%, transparent 25%, transparent 50%, rgba(255,255,255,0.15) 50%, rgba(255,255,255,0.15) 75%, transparent 75%, transparent);
            background-size: 10px 10px;
            border: 1px dashed rgba(255,255,255,0.5);
        }

        .js-click-modal { cursor: pointer; }
        td.cell-empty:hover { background-color: #f0f9ff; }
        td.cell-empty:hover .add-btn { display: block; }
        td.cell-hoverable:not(.cell-empty):hover .add-btn { display: block; opacity: 1; }

        .empty-cell { background: #fff; }
        .weekend-cell { background: #f9f9f9; }

        .add-btn {
            display: none;
            position: absolute;
            top: 2px;
            right: 2px;
            width: 16px;
            height: 16px;
            line-height: 16px;
            background: rgba(255,255,255,0.85);
            color: #2c3e50;
            text-align: center;
            border-radius: 50%;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
            z-index: 8;
            opacity: 0.85;
        }

        .modal-overlay { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.5); z-index: 999; justify-content: center; align-items: center; }
        .modal-content { background: white; padding: 20px; border-radius: 5px; width: 400px; }
        .form-row { margin-bottom: 10px; }
        .form-row label { display: block; font-size: 11px; color: #7f8c8d; margin-bottom: 3px; }
        .form-row input, .form-row select { width: 100%; padding: 6px; border: 1px solid #ccc; border-radius: 3px; box-sizing: border-box; }
        
        .btn { padding: 6px 12px; border: none; border-radius: 3px; color: white; cursor: pointer; }
        
        .guide-info { display: flex; flex-direction: column; justify-content: center; height: 100%; padding: 5px; text-align: left; }
        .guide-name { font-weight: bold; font-size: 12px; }
        .guide-phone { font-size: 10px; color: #636e72; margin-top: 2px; }

        /* [변경] 색상표 바둑판 스타일 */
        .color-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 5px;
        }
        .color-swatch {
            width: 28px;
            height: 28px;
            border-radius: 4px;
            cursor: pointer;
            border: 1px solid rgba(0,0,0,0.1);
            transition: all 0.2s;
        }
        .color-swatch:hover {
            transform: scale(1.1);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .color-swatch.selected {
            border: 2px solid #2c3e50;
            transform: scale(1.1);
            box-shadow: 0 0 0 2px rgba(255,255,255,0.8) inset; /* 내부 흰 테두리 효과 */
        }

        .merged-cell .edit-badge {
            display: inline-block;
            width: 14px;
            height: 14px;
            line-height: 13px;
            text-align: center;
            background: rgba(255,255,255,0.85);
            color: #2c3e50;
            border-radius: 50%;
            font-size: 11px;
            font-weight: 700;
            margin-right: 4px;
            cursor: pointer;
            box-shadow: 0 1px 1px rgba(0,0,0,0.15);
        }
        .merged-cell .edit-badge:hover {
            background: #fff;
            transform: scale(1.15);
        }
    </style>
</head>
<body>

<div class="control-bar">
    <div class="month-control">
        <a href="?year=<?= date('Y', $prevDate) ?>&month=<?= date('m', $prevDate) ?>" class="btn-nav">&lt; 이전달</a>
        <div class="month-title">
            <?php if($hasDateFilter): ?>
                <?= htmlspecialchars($sDate) ?> ~ <?= htmlspecialchars($eDate) ?> 가이드 스케줄
            <?php else: ?>
                <?= htmlspecialchars($sDate) ?> ~ <?= htmlspecialchars($eDate) ?> 가이드 스케줄
            <?php endif; ?>
        </div>
        <a href="?year=<?= date('Y', $nextDate) ?>&month=<?= date('m', $nextDate) ?>" class="btn-nav">다음달 &gt;</a>
        <a href="?year=<?= date('Y') ?>&month=<?= date('m') ?>" class="btn-nav btn-today">오늘</a>
    </div>
    <button type="button" class="btn-manual" onclick="openModalManual()">[+] 가이드 스케줄 수동 등록</button>
</div>

<form method="GET" class="filter-bar">
    <input type="hidden" name="year" value="<?= htmlspecialchars($viewYear) ?>">
    <input type="hidden" name="month" value="<?= htmlspecialchars($viewMonth) ?>">
    <label for="filter_start">날짜 구간</label>
    <input type="date" name="filter_start" id="filter_start" value="<?= htmlspecialchars($dateInputStart) ?>" required>
    <span>~</span>
    <input type="date" name="filter_end" id="filter_end" value="<?= htmlspecialchars($dateInputEnd) ?>" required>
    <label for="product_keyword">상품명</label>
    <input type="text" id="product_keyword" value="<?= htmlspecialchars($productKeyword) ?>" placeholder="상품명/코드 검색">
    <button type="submit" class="filter-btn">조회</button>
    <a href="?year=<?= htmlspecialchars($viewYear) ?>&month=<?= htmlspecialchars($viewMonth) ?>" class="btn-nav">초기화</a>
</form>

<div id="unassigned_area">
    <?php guide_block_render_unassigned_months($unassignedGroupedByMonth); ?>
</div>

<div class="grid-wrapper">
    <table class="scheduler-table">
        <colgroup>
            <col style="width: 100px;"> <!-- 가이드명 -->
            <?php foreach($dateHeaders as $dt): ?>
            <col style="width: 40px;">
            <?php endforeach; ?>
        </colgroup>
        <thead>
            <tr class="month-row">
                <th class="fixed-col" rowspan="2">가이드</th>
                <?php foreach($dateHeaderMonths as $monthInfo): ?>
                    <th class="month-group" data-month="<?= htmlspecialchars($monthInfo['key']) ?>" colspan="<?= $monthInfo['colspan'] ?>"><?= htmlspecialchars($monthInfo['label']) ?></th>
                <?php endforeach; ?>
            </tr>
            <tr class="day-row">
                <?php foreach($dateHeaders as $dt): 
                    $w = date('w', strtotime($dt));
                    $color = ($w==0) ? '#ff7675' : (($w==6) ? '#74b9ff' : '#fff');
                ?>
                <th style="color:<?= $color ?>;">
                    <?= date('d', strtotime($dt)) ?><br>
                    <span style="font-size:10px; font-weight:normal;"><?= date('D', strtotime($dt)) ?></span>
                </th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach($guideList as $guide): 
                $skipCount = 0; 
                $gId = $guide['userid'];
            ?>
            <tr>
                <td class="fixed-col">
                    <div class="guide-info">
                        <div class="guide-name"><?= $guide['kor_name'] ?></div>
                        <div class="guide-phone"><?= $guide['cell_phone'] ?></div>
                    </div>
                </td>
                <?php foreach($dateHeaders as $dt): 
                    if ($skipCount > 0) {
                        $skipCount--;
                        continue; 
                    }

                    $events = isset($scheduleMap[$gId][$dt]) ? $scheduleMap[$gId][$dt] : null;

                    // 1. 이벤트가 있는 경우
                    if ($events) {
                        $maxColspan = 1;
                        foreach($events as $ev) {
                            if($ev['colspan'] > $maxColspan) $maxColspan = $ev['colspan'];
                        }
                        $skipCount = $maxColspan - 1; 
                ?>
                    <td colspan="<?= $maxColspan ?>" class="cell-hoverable" style="padding: 1px; vertical-align: top;"
                        data-dt="<?=$dt?>" data-gid="<?=$gId?>">
                        <!-- 추가 버튼 (호버 시 표시) -->
                        <div class="add-btn" title="이 날짜에 새 스케줄 추가">+</div>

                        <?php 
                        foreach($events as $cell):
                            $info     = $cell['info'];
                            $cls      = '';
                            if ($info['is_unassigned_product']) {
                                $cls .= ' no-product';
                            }

                            $originStart = date('m/d', strtotime($info['stDate']));
                            $txt      = $info['p_name'];
                            $paxCnt   = $is_auto
                                ? (isset($info['p_cnt_total']) ? (int)$info['p_cnt_total'] : 0)
                                : (isset($info['p_cnt']) ? (int)$info['p_cnt'] : 0);
                            
                            $is_auto  = (isset($info['userid']) && $info['userid'] === 'auto');
                            $fallback = $is_auto ? '#16a085' : '#e91e8c';
                            $bg_color = !empty($info['color_code']) ? $info['color_code'] : $fallback;
                            
                            if (defined('JSON_UNESCAPED_UNICODE')) {
                                $jsonRaw = json_encode($info, JSON_UNESCAPED_UNICODE);
                            } else {
                                $jsonRaw = json_encode($info);
                            }
                            if($jsonRaw === false) $jsonRaw = '{}';
                            $base64Str = base64_encode($jsonRaw);
                        ?>
                        <!-- 본체 클릭: 수정 모달 / [+] 배지: 명단 팝업(guide_assign_customer.php) -->
                        <div class="merged-cell js-edit-cell <?= $cls ?>"
                             style="background-color: <?= $bg_color ?>;"
                             title="<?= htmlspecialchars($txt) ?> (<?= $info['stDate'] ?> ~ <?= $info['edDate'] ?> / 인원: <?= $paxCnt ?>명) / 클릭: 명단 / +: 수정"
                             data-b64="<?=$base64Str?>">
                            <span class="edit-badge js-list-badge"
                                  data-pcode="<?= htmlspecialchars($info['m_p_code']) ?>"
                                  data-stdate="<?= htmlspecialchars($info['stDate']) ?>"
                                  title="수정/삭제">+</span>
                            <?= htmlspecialchars($txt . (!empty($info['memo']) ? ' ' . $info['memo'] : '')) ?> / <?= $paxCnt ?>명
                        </div>
                        <?php endforeach; ?>
                    </td>
                <?php 
                    } else { 
                        // 2. 이벤트가 없는 빈 셀
                        $w = date('w', strtotime($dt));
                        $bg = ($w==0 || $w==6) ? 'weekend-cell' : 'empty-cell';
                ?>
                    <td class="<?= $bg ?> cell-hoverable cell-empty" data-dt="<?=$dt?>" data-gid="<?=$gId?>">
                        <div class="add-btn" title="새 스케줄 추가">+</div>
                    </td>
                <?php } ?>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- 모달: 가이드 배정용 -->
<div id="assignModal" class="modal-overlay">
    <div class="modal-content">
        <h3 id="modal_title" style="margin-top:0;">가이드 배정</h3>
        <form method="POST" id="modal_form">
            <input type="hidden" name="mode" id="modal_mode">
            <input type="hidden" name="seq_no" id="modal_seq">
            <input type="hidden" name="grand_eCode" id="modal_ecode_hidden"> 
            <input type="hidden" name="p_day" id="modal_p_day" value="1">

            <div class="form-row">
                <label>상품명 (연결 대상)</label>
                <input type="text" id="modal_pname_readonly" readonly style="background:#eee; display:none;">
                
                <select id="modal_pname_select" style="display:none;" onchange="updateEcode(this)">
                    <option value="" data-sdate="" data-days="">[상품 미지정] - 메모로 관리</option>
                    <?php foreach($unassignedListData as $uItem): ?>
                        <option value="<?= $uItem['grand_eCode'] ?>" 
                                data-sdate="<?= $uItem['stDate'] ?>" 
                                data-days="<?= $uItem['p_day'] ?>">
                            [<?= $uItem['stDate'] ?>] <?= $uItem['p_name'] ?> (<?= $uItem['p_day'] ?>일)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-row">
                <label>스케줄 색상 선택</label>
                <!-- [변경] 컬러 피커 대신 hidden input과 div 컨테이너 -->
                <input type="hidden" name="color_code" id="modal_color" value="#3498db">
                <div class="color-grid" id="color_palette">
                    <!-- 자바스크립트로 색상 타일 생성 -->
                </div>
            </div>

            <div class="form-row">
                <label>가이드 선택 (필수)</label>
                <select name="guide_id" id="modal_select_guide" required>
                    <option value="">-- 가이드 선택 --</option>
                    <?php foreach($guideList as $g): ?>
                    <option value="<?= $g['userid'] ?>"><?= $g['kor_name'] ?> (<?= $g['userid'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-row">
                <label>기간 (자동 계산됨)</label>
                <div style="display:flex; gap:5px;">
                    <input type="date" name="stDate" id="modal_sdate" required onchange="calcEndDate()">
                    <input type="date" name="edDate" id="modal_edate" required>
                </div>
            </div>
            <div class="form-row">
                <label id="modal_memo_label">메모</label>
                <small id="modal_memo_current" style="display:none; color:#7f8c8d; margin-bottom:4px; display:block; font-size:11px;"></small>
                <input type="text" name="memo" id="modal_memo" placeholder="일정명 또는 특이사항">
            </div>
            <div class="form-row">
                <label>인원수</label>
                <input type="number" name="p_cnt" id="modal_p_cnt" min="0" placeholder="0" style="width:120px;">
            </div>

            <div style="text-align:right; margin-top:15px;">
                <button type="button" class="btn" style="background:#95a5a6;" onclick="closeModal()">닫기</button>
                <button type="submit" class="btn" style="background:#3498db;" id="btn_save">저장</button>
                <button type="button" class="btn" style="background:#e74c3c;" id="btn_del" onclick="delAssign()">삭제</button>
            </div>
        </form>
    </div>
</div>

<script>
// [변경] 사용할 색상 리스트 정의
const colorList = [
    '#3498db', '#2980b9', // Blue
    '#e74c3c', '#c0392b', // Red
    '#2ecc71', '#27ae60', // Green
    '#f1c40f', '#f39c12', // Yellow
    '#9b59b6', '#8e44ad', // Purple
    '#1abc9c', '#16a085', // Teal
    '#e67e22', '#d35400', // Orange
    '#e91e8c', '#ad1457', // Magenta
    '#34495e', '#7f8c8d'  // Grey
];

document.addEventListener('DOMContentLoaded', function() {
    renderColorPalette(); // 색상표 생성

    document.body.addEventListener('click', function(e) {
        // [+] 배지 → 수정/삭제 모달
        var listBadge = e.target.closest('.js-list-badge');
        if (listBadge) {
            var parentCell = listBadge.closest('.js-edit-cell');
            if (parentCell) {
                openModalFromData('edit', parentCell.getAttribute('data-b64'));
            }
            return;
        }

        // 블록 본체 → 예약자 명단 팝업 (guide_assign_customer.php)
        var editCell = e.target.closest('.js-edit-cell');
        if (editCell) {
            var badge = editCell.querySelector('.js-list-badge');
            if (badge) {
                openCustomerWindow(
                    badge.getAttribute('data-pcode'),
                    badge.getAttribute('data-stdate')
                );
            }
            return;
        }

        // 미배정 카드 → 신규 등록
        var target = e.target.closest('.js-click-modal');
        if (target) {
            var mode = target.getAttribute('data-mode');
            var b64  = target.getAttribute('data-b64');
            openModalFromData(mode, b64);
            return;
        }

    });

    var productInput = document.getElementById('product_keyword');
    if (productInput) {
        productInput.addEventListener('input', filterUnassignedCardsOnScreen);
    }

    scrollUnassignedToCurrentMonth();
    scrollScheduleToCurrentMonth();
});

function scrollUnassignedToCurrentMonth() {
    var zone = document.querySelector('#unassigned_area .unassigned-zone');
    var currentMonth = '<?= date('Y-m') ?>';
    var currentSection = document.querySelector('#unassigned_area .month-section[data-month="' + currentMonth + '"]');

    if (!zone || !currentSection) return;

    zone.scrollLeft = Math.max(0, currentSection.offsetLeft - zone.offsetLeft - 12);
}

function scrollScheduleToCurrentMonth() {
    var wrapper = document.querySelector('.grid-wrapper');
    var currentMonth = '<?= date('Y-m') ?>';
    var currentHeader = document.querySelector('.scheduler-table .month-group[data-month="' + currentMonth + '"]');

    if (!wrapper || !currentHeader) return;

    var fixedWidth = 100;
    wrapper.scrollLeft = Math.max(0, currentHeader.offsetLeft - fixedWidth - 12);
}

function filterUnassignedCardsOnScreen() {
    var input = document.getElementById('product_keyword');
    var keyword = input ? input.value.trim().toLowerCase() : '';
    var monthSections = document.querySelectorAll('#unassigned_area .month-section');

    monthSections.forEach(function(section) {
        var visibleCount = 0;
        var cards = section.querySelectorAll('.card');

        cards.forEach(function(card) {
            var text = card.innerText.toLowerCase();
            var matched = keyword === '' || text.indexOf(keyword) !== -1;
            card.style.display = matched ? '' : 'none';
            if (matched) visibleCount++;
        });

        var heading = section.querySelector('.month-heading');
        if (heading) {
            heading.style.opacity = visibleCount > 0 || keyword === '' ? '1' : '0.45';
        }
    });
}

// [변경] 색상표 렌더링 함수
function renderColorPalette() {
    const palette = document.getElementById('color_palette');
    palette.innerHTML = '';
    
    colorList.forEach(color => {
        const div = document.createElement('div');
        div.className = 'color-swatch';
        div.style.backgroundColor = color;
        div.onclick = function() { selectColor(color); };
        palette.appendChild(div);
    });
}

// [변경] 색상 선택 함수
function selectColor(color) {
    document.getElementById('modal_color').value = color;
    
    // UI 업데이트 (선택된 타일 강조)
    const swatches = document.querySelectorAll('.color-swatch');
    swatches.forEach(swatch => {
        // rgb로 변환되는 경우도 고려하여 비교하거나 단순히 style값 비교
        // 여기서는 간단히 모든 클래스 제거 후, 클릭된 요소만 추가하는 방식이 아니라
        // 전체 루프 돌며 배경색 비교
        swatch.classList.remove('selected');
        
        // 브라우저가 hex를 rgb로 변환할 수 있으므로, 단순 비교를 위해 
        // 현재 선택한 color 값을 input에 넣고, 다시 가져오는 방식보단
        // 렌더링 시점에 할당된 값과 비교하는 것이 안전하나,
        // 여기서는 간단하게 처리 (backgroundColor 값을 직접 비교 시 hex/rgb 차이 발생 가능)
    });

    // 선택된 컬러와 일치하는 swatch 찾기 (hex to rgb 변환 문제 회피를 위해 다시 루프)
    // 좀 더 정확한 매칭을 위해 render 시 dataset 이용 권장하지만, 간단 구현
    for(let i=0; i<swatches.length; i++) {
        // inline style을 hex로 지정했으므로 style.backgroundColor가 브라우저에 따라 rgb(...)로 나옴
        // 따라서, colorList의 인덱스로 매칭하거나, dataset을 활용하는 것이 좋음.
        // 이번에는 colorList 순서대로 생성했으므로, colorList에서 인덱스를 찾아 매칭.
        if (colorList[i] && colorList[i].toLowerCase() === color.toLowerCase()) {
            swatches[i].classList.add('selected');
        }
    }
}

function filterProductOptions(targetDate) {
    var select = document.getElementById('modal_pname_select');
    var options = select.options;
    
    if (!targetDate) return;

    for (var i = 0; i < options.length; i++) {
        var opt = options[i];
        if (opt.value === "") {
            opt.style.display = 'block';
            continue;
        }
        var optDate = opt.getAttribute('data-sdate');
        if (optDate === targetDate) {
            opt.style.display = 'block';
            opt.disabled = false;
        } else {
            opt.style.display = 'none';
            opt.disabled = true;
        }
    }
    
    var currentSelected = select.options[select.selectedIndex];
    if (currentSelected.style.display === 'none' || currentSelected.disabled) {
        select.value = "";
        document.getElementById('modal_ecode_hidden').value = "";
    }
}

function calcEndDate() {
    var sDateVal = document.getElementById('modal_sdate').value;
    var daysVal = document.getElementById('modal_p_day').value; 

    if (sDateVal && daysVal && parseInt(daysVal) > 0) {
        var dateObj = new Date(sDateVal);
        var days = parseInt(daysVal);
        
        dateObj.setDate(dateObj.getDate() + (days - 1));
        
        var y = dateObj.getFullYear();
        var m = ('0' + (dateObj.getMonth() + 1)).slice(-2);
        var d = ('0' + dateObj.getDate()).slice(-2);
        
        document.getElementById('modal_edate').value = y + '-' + m + '-' + d;
    }
}

function openModalManual(preDate, preGuideId) {
    document.getElementById('modal_title').innerText = "가이드 스케줄 수동 등록 (중복 가능)";
    document.getElementById('modal_mode').value = 'assign';
    document.getElementById('modal_seq').value = '';
    document.getElementById('modal_ecode_hidden').value = ''; 
    document.getElementById('modal_p_day').value = '1'; 
    
    document.getElementById('modal_pname_readonly').style.display = 'none';
    document.getElementById('modal_pname_select').style.display = 'block';
    document.getElementById('modal_pname_select').value = '';

    var guideSel = document.getElementById('modal_select_guide');
    if(preGuideId) {
        guideSel.value = preGuideId;
    } else {
        guideSel.value = '';
    }
    guideSel.disabled = false;
    guideSel.style.pointerEvents = '';
    guideSel.style.background = '';
    guideSel.style.color = '';
    
    var targetDate = preDate ? preDate : '<?= date("Y-m-d") ?>';
    document.getElementById('modal_sdate').value = targetDate;
    document.getElementById('modal_edate').value = targetDate;
    
    document.getElementById('modal_sdate').readOnly = false;
    document.getElementById('modal_edate').readOnly = false;
    document.getElementById('modal_sdate').style.background = '#fff';
    document.getElementById('modal_edate').style.background = '#fff';

    document.getElementById('modal_memo').value = '';
    document.getElementById('modal_memo').placeholder = '일정명 또는 특이사항';
    document.getElementById('modal_memo_label').textContent = '메모';
    document.getElementById('modal_memo_current').style.display = 'none';

    var pCntEl = document.getElementById('modal_p_cnt');
    pCntEl.value = '';
    pCntEl.readOnly = false;
    pCntEl.style.background = '#fff';

    // 수동 등록 기본 색상: 마젠타
    selectColor('#e91e8c');
    
    document.getElementById('btn_save').style.display = 'inline-block';
    document.getElementById('btn_del').style.display = 'none';

    filterProductOptions(targetDate);
    document.getElementById('assignModal').style.display = 'flex';
}

function openModalFromData(mode, base64Str) {
    if (!base64Str) { alert("데이터 오류"); return; }

    var data = {};
    try {
        var jsonStr = decodeURIComponent(escape(window.atob(base64Str)));
        data = JSON.parse(jsonStr);
    } catch (e) { console.error(e); return; }

    document.getElementById('modal_seq').value    = data.seq_no || '';
    document.getElementById('modal_ecode_hidden').value = data.grand_eCode || '';
    document.getElementById('modal_select_guide').value = data.guide_id || '';

    document.getElementById('modal_sdate').value = data.stDate || '';
    document.getElementById('modal_edate').value = data.edDate || '';
    document.getElementById('modal_memo').value  = data.memo || '';

    var savedColor = data.color_code || '#3498db';
    selectColor(savedColor);

    document.getElementById('modal_p_day').value = data.p_day || '1';

    var isAuto  = (data.userid === 'auto');
    var pCntEl  = document.getElementById('modal_p_cnt');

    filterProductOptions(data.stDate);

    if (mode === 'new') {
        document.getElementById('modal_title').innerText = "신규 가이드 배정";
        document.getElementById('modal_mode').value = 'assign';
        
        document.getElementById('modal_pname_readonly').value = data.p_name;
        document.getElementById('modal_pname_readonly').style.display = 'block';
        document.getElementById('modal_pname_select').style.display = 'none';

        document.getElementById('modal_sdate').readOnly = true;
        document.getElementById('modal_edate').readOnly = true;
        document.getElementById('modal_sdate').style.background = '#eee';
        document.getElementById('modal_edate').style.background = '#eee';

        document.getElementById('modal_select_guide').disabled = false;
        document.getElementById('btn_save').style.display = 'inline-block';
        document.getElementById('btn_del').style.display = 'none';

        // new: 인원수 편집 가능
        pCntEl.value = data.p_cnt_total || 0;
        pCntEl.readOnly = false;
        pCntEl.style.background = '#fff';

    } else {
        document.getElementById('modal_mode').value = 'assign';

        var isUnassignedProduct = data.is_unassigned_product;

        if (isUnassignedProduct) {
            document.getElementById('modal_pname_readonly').style.display = 'none';
            document.getElementById('modal_pname_select').style.display = 'block';
            document.getElementById('modal_pname_select').value = data.grand_eCode || '';
        } else {
            document.getElementById('modal_pname_readonly').value = data.p_name;
            document.getElementById('modal_pname_readonly').style.display = 'block';
            document.getElementById('modal_pname_select').style.display = 'none';
        }

        if (isAuto) {
            // 연동 블록: 색상·메모(append)만 수정 가능, 삭제 불가
            document.getElementById('modal_title').innerText = "배정 수정 (가이드 연동)";
            document.getElementById('modal_sdate').readOnly = true;
            document.getElementById('modal_edate').readOnly = true;
            document.getElementById('modal_sdate').style.background = '#eee';
            document.getElementById('modal_edate').style.background = '#eee';
            document.getElementById('modal_select_guide').disabled = false;
            document.getElementById('modal_select_guide').style.pointerEvents = 'none';
            document.getElementById('modal_select_guide').style.background = '#eee';
            document.getElementById('modal_select_guide').style.color = '#777';
            pCntEl.value = data.p_cnt_total || 0;
            pCntEl.readOnly = true;
            pCntEl.style.background = '#eee';
            document.getElementById('modal_memo_current').style.display = 'none';
            document.getElementById('modal_memo').value = data.memo || '';
            document.getElementById('modal_memo').placeholder = '일정명 또는 특이사항';
            document.getElementById('modal_memo_label').textContent = '메모';
            document.getElementById('btn_save').style.display = 'inline-block';
            document.getElementById('btn_del').style.display = 'none';
        } else {
            // 수동 블록: 전체 수정·삭제 가능
            document.getElementById('modal_title').innerText = "배정 수정 / 삭제";
            document.getElementById('modal_memo_label').textContent = '메모';
            document.getElementById('modal_memo').placeholder = '일정명 또는 특이사항';
            document.getElementById('modal_memo_current').style.display = 'none';
            document.getElementById('modal_sdate').readOnly = false;
            document.getElementById('modal_edate').readOnly = false;
            document.getElementById('modal_sdate').style.background = '#fff';
            document.getElementById('modal_edate').style.background = '#fff';
            document.getElementById('modal_select_guide').disabled = false;
            document.getElementById('modal_select_guide').style.pointerEvents = '';
            document.getElementById('modal_select_guide').style.background = '';
            document.getElementById('modal_select_guide').style.color = '';
            pCntEl.value = data.p_cnt || 0;
            pCntEl.readOnly = false;
            pCntEl.style.background = '#fff';
            document.getElementById('btn_save').style.display = 'inline-block';
            document.getElementById('btn_del').style.display = 'inline-block';
        }
    }
    
    document.getElementById('assignModal').style.display = 'flex';
}

function updateEcode(sel) {
    document.getElementById('modal_ecode_hidden').value = sel.value;

    var selectedOption = sel.options[sel.selectedIndex];
    var sDateVal = selectedOption.getAttribute('data-sdate');
    var daysVal  = selectedOption.getAttribute('data-days');

    if (sDateVal && daysVal) {
        document.getElementById('modal_sdate').value = sDateVal;
        document.getElementById('modal_p_day').value = daysVal; 
        
        calcEndDate(); 
    }
}

function delAssign() {
    if(confirm('정말 삭제하시겠습니까?')) {
        document.getElementById('modal_mode').value = 'delete';
        document.getElementById('modal_form').submit();
    }
}

function closeModal() {
    document.getElementById('assignModal').style.display = 'none';
}

// ===== guide_assign_customer.php 팝업 =====
function openCustomerWindow(pcode, stdate) {
    if (!pcode || !stdate) { alert('상품/시작일 정보가 없습니다.'); return; }
    var url = 'guide_assign_customer.php?division=&pdx=&sub=&s_code='
            + encodeURIComponent(pcode)
            + '&stdate=' + encodeURIComponent(stdate)
            + '&rcode=';
    window.open(url, 'guide_assign_view', 'width=1090,height=700,scrollbars=1,resizable=1');
}

window.onclick = function(e) {
    if (e.target == document.getElementById('assignModal')) closeModal();
};
</script>

</body>
</html>
