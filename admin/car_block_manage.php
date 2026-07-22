<?php
// 1. 에러 설정 (PHP 7.0 이상)
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
ini_set('display_errors', 1);

include "include/inc_base.php";
// ※ 중요: inc_base.php에서 $dbConn = mysqli_connect(...) 객체가 생성되어야 합니다.

/*
[필수 DB 패치]
1) 컬럼 추가
ALTER TABLE tour_car_block ADD COLUMN guide_single VARCHAR(50) NULL AFTER bus_num;

2) (중요) 기존에 grand_eCode+stDate UNIQUE가 있으면 제거 후 아래로 교체
ALTER TABLE tour_car_block ADD UNIQUE KEY uniq_grand_st_guide (grand_eCode, stDate, guide_single);
*/

function car_block_is_date($value) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$value)) {
        return false;
    }
    list($year, $month, $day) = explode('-', $value);
    return checkdate((int)$month, (int)$day, (int)$year);
}

/* =================================================================================
 * 대기 목록(월별 가로 섹션) 렌더링
 *  - guide_block.php 와 동일한 UI 형식
 *  - 차량배정(tour_car) 존재 시 "등록됨"(회색·클릭불가)
 *  - 블록테이블(tour_car_block)에만 존재 시 "가등록"(파란 배지)
 * ================================================================================= */
function car_block_render_unassigned_months($groupedItems) {
    if (empty($groupedItems)) {
        echo '<div class="unassigned-heading-label">차량 배정 대기 상품 목록</div>';
        echo '<div class="unassigned-zone">';
        echo '<div style="color:#7f8c8d; padding:10px 0;">검색 결과가 없습니다.</div>';
        echo '</div>';
        return;
    }
?>
<div class="unassigned-heading-label">차량 배정 대기 상품 목록</div>
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

                    // 차량배정(tour_car) 존재 → 등록됨 / 블록만 존재 → 가등록
                    $isCarAssigned   = !empty($item['tour_car_cnt']);
                    $isPreRegistered = !$isCarAssigned && !empty($item['block_cnt']);
                    $cardClass = $isCarAssigned ? 'card is-disabled' : 'card js-click-modal';
                    if ($isPreRegistered) {
                        $cardClass .= ' is-pre-registered';
                    }
                ?>
                <div class="<?= $cardClass ?>"
                     <?php if(!$isCarAssigned): ?>
                     data-mode="new"
                     data-b64="<?=$base64Str?>"
                     <?php endif; ?>
                     title="<?= $isCarAssigned ? '이미 차량이 배정된 상품입니다. / 인원: ' . $paxCnt . '명' : '인원: ' . $paxCnt . '명' ?>">
                    <?php if($isCarAssigned): ?>
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

if (empty($_COOKIE['MEMLOGIN_ADMIN_HELLO'])) {
    echo "<script>alert('관리자 로그인이 필요합니다.'); window.close();</script>";
    exit;
}

/* =================================================================================
 * [Backend] 1. 파라미터 및 날짜 설정 (날짜 구간 필터 포함)
 * ================================================================================= */
$viewYear  = $_GET['year']  ?? date('Y');
$viewMonth = $_GET['month'] ?? date('m');
$filterStart = (isset($_GET['filter_start']) && car_block_is_date($_GET['filter_start'])) ? $_GET['filter_start'] : '';
$filterEnd   = (isset($_GET['filter_end'])   && car_block_is_date($_GET['filter_end']))   ? $_GET['filter_end']   : '';
$productKeyword = '';
$hasDateFilter = ($filterStart !== '' && $filterEnd !== '');

// 2. 날짜 계산 (기본: 이전달 ~ +10개월)
$monthStart   = date("{$viewYear}-{$viewMonth}-01");
$defaultStart = date("Y-m-01", strtotime($monthStart . " -1 month"));
$defaultEnd   = date("Y-m-t",  strtotime($monthStart . " +10 months"));

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
$dateInputEnd   = $filterEnd   !== '' ? $filterEnd   : $eDate;

// 3. 네비게이션
$today    = date('Y-m-d');
$prevDate = strtotime("-1 month", strtotime($monthStart));
$nextDate = strtotime("+1 month", strtotime($monthStart));

// 4. 날짜 배열 생성 (X축)
$dateHeaders = [];
$dStart = new DateTime($sDate);
$dEnd   = new DateTime($eDate);
$dEnd->modify('+1 day');
$interval = new DateInterval('P1D');
$period   = new DatePeriod($dStart, $interval, $dEnd);

foreach ($period as $dt) {
    $dateHeaders[] = $dt->format('Y-m-d');
}

// 4-1. 월 헤더(상단 그룹) 계산
$dateHeaderMonths = [];
foreach ($dateHeaders as $dt) {
    $monthKey = date('Y-m', strtotime($dt));
    if (!isset($dateHeaderMonths[$monthKey])) {
        $dateHeaderMonths[$monthKey] = [
            'key'     => $monthKey,
            'label'   => date('Y년 m월', strtotime($dt)),
            'colspan' => 0
        ];
    }
    $dateHeaderMonths[$monthKey]['colspan']++;
}

/* =================================================================================
 * [Backend] 2. 데이터 처리 (등록/수정/삭제) — 기존 차량 로직 유지
 * ================================================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mode'])) {

    $grand_eCode = isset($_POST['grand_eCode']) ? mysqli_real_escape_string($dbConn, $_POST['grand_eCode']) : '';
    $uid         = isset($_COOKIE['MEMLOGIN_ID']) ? mysqli_real_escape_string($dbConn, $_COOKIE['MEMLOGIN_ID']) : 'admin';

    // A. 배정 등록 및 수정
    if ($_POST['mode'] === 'assign') {
        $seq_no       = isset($_POST['seq_no']) ? mysqli_real_escape_string($dbConn, $_POST['seq_no']) : '';
        $bus_num      = mysqli_real_escape_string($dbConn, $_POST['bus_num']);
        $guide_single = isset($_POST['guide_single']) ? mysqli_real_escape_string($dbConn, $_POST['guide_single']) : '';
        $start_date   = mysqli_real_escape_string($dbConn, $_POST['stDate']);
        $end_date     = mysqli_real_escape_string($dbConn, $_POST['edDate']);
        $memo         = mysqli_real_escape_string($dbConn, $_POST['memo']);
        $color_code   = isset($_POST['color_code']) ? mysqli_real_escape_string($dbConn, $_POST['color_code']) : '#3498db';

        // 차량 지정 필수 체크
        if (empty($bus_num)) {
            echo "<script>alert('차량은 반드시 지정해야 합니다.'); history.back();</script>";
            exit;
        }

        // ✅ [핵심 보정] grand_eCode(상품 연결)인 경우 p_day 기준으로 edDate를 서버에서 강제 계산
        if (!empty($grand_eCode) && !empty($start_date)) {
            $qry_pd = "
                SELECT IFNULL(P.p_day, 1) AS p_day
                FROM tour_master M
                LEFT JOIN product_master P ON M.p_code = P.p_code
                WHERE M.grand_eCode = '$grand_eCode'
                LIMIT 1
            ";
            $rst_pd = mysqli_query($dbConn, $qry_pd);
            if ($rst_pd) {
                $pdrow = mysqli_fetch_assoc($rst_pd);
                $pday  = isset($pdrow['p_day']) ? (int)$pdrow['p_day'] : 1;
                if ($pday < 1) $pday = 1;

                // p_day=1 이면 종료일 = 시작일
                $end_date = date('Y-m-d', strtotime($start_date . ' +' . ($pday - 1) . ' day'));
            }
        }

        // ✅ 같은 날짜라도 가이드가 다르면 저장 가능하도록 guide_single을 함께 저장
        if ($seq_no) {
            // [수정]
            $qry = "UPDATE tour_car_block
                    SET grand_eCode   = '$grand_eCode',
                        bus_num      = '$bus_num',
                        guide_single = '$guide_single',
                        stDate       = '$start_date',
                        edDate       = '$end_date',
                        memo        = '$memo',
                        color_code  = '$color_code',
                        wdate       = NOW()
                    WHERE seq_no = '$seq_no'";
        } else {
            // [신규]
            $qry = "INSERT INTO tour_car_block (grand_eCode, bus_num, guide_single, stDate, edDate, memo, color_code, userid, wdate)
                    VALUES ('$grand_eCode', '$bus_num', '$guide_single', '$start_date', '$end_date', '$memo', '$color_code', '$uid', NOW())";
        }

        if (!mysqli_query($dbConn, $qry)) {
            echo "<script>alert('DB Error: " . mysqli_error($dbConn) . "'); history.back();</script>";
            exit;
        }
    }
    // B. 배정 삭제
    else if ($_POST['mode'] === 'delete') {
        $seq_no = mysqli_real_escape_string($dbConn, $_POST['seq_no']);
        $qry = "DELETE FROM tour_car_block WHERE seq_no = '$seq_no'";

        if (mysqli_query($dbConn, $qry)) {
            echo "<script>alert('정상적으로 삭제되었습니다.');</script>";
        } else {
            echo "<script>alert('삭제 실패: " . mysqli_error($dbConn) . "'); history.back();</script>";
            exit;
        }
    }

    $redirectParams = ['year' => $viewYear, 'month' => $viewMonth];
    if ($filterStart !== '' && $filterEnd !== '') {
        $redirectParams['filter_start'] = $filterStart;
        $redirectParams['filter_end']   = $filterEnd;
    }
    $qs = http_build_query($redirectParams);
    echo "<script>location.href='{$_SERVER['PHP_SELF']}?{$qs}';</script>";
    exit;
}

/* =================================================================================
 * [Backend] 3. 데이터 조회
 * ================================================================================= */

// 3-1. 차량 목록 조회
$busList = [];
$rst_bus = mysqli_query($dbConn, "SELECT * FROM bus_list ORDER BY bus_number, bus_id ");
if ($rst_bus) {
    while ($row = mysqli_fetch_assoc($rst_bus)) {
        $busList[] = $row;
    }
}

/* =================================================================================
 * 3-2. 배정된 스케줄 데이터 조회
 * ================================================================================= */

$scheduleMap = [];
$blockKeySet = []; // 수동 블록 중복 방지용 키셋 (grand_eCode|bus_id|stDate)

$qry_assign = "
    SELECT
        B.*,
        P.p_name, P.p_day,
        (SELECT count(*) FROM tour_car WHERE grand_eCode = B.grand_eCode) as locked_cnt,

        /* 이 차량(c_id=bus_id)에 배정된 인원수: 순번(bus_num)으로 매핑 후 tour_car 카운트 */
        IFNULL((
            SELECT COUNT(*)
            FROM tour_car TC
            WHERE TC.grand_eCode = B.grand_eCode
              AND TC.stDate      = B.stDate
              AND TC.bus_num     = (
                    SELECT TG3.bus_num
                    FROM tour_guide TG3
                    WHERE TG3.grand_eCode = B.grand_eCode
                      AND TG3.stDate     = B.stDate
                      AND TG3.c_id       = B.bus_num
                    LIMIT 1
              )
        ), 0) AS pax_cnt,

        /* 표시용 가이드(1명): guide_single 우선, 없으면(레거시) tour_guide에서 1명만 */
        COALESCE(
            NULLIF(B.guide_single,''),
            (
                SELECT T.guide_id
                FROM tour_guide T
                WHERE T.grand_eCode = B.grand_eCode
                  AND T.stDate = B.stDate
                  AND T.c_id   = B.bus_num
                  AND T.guide_id IS NOT NULL AND T.guide_id <> ''
                ORDER BY T.guide_id
                LIMIT 1
            )
        ) AS display_guide_id

    FROM tour_car_block B
    LEFT JOIN tour_master M ON B.grand_eCode = M.grand_eCode
    LEFT JOIN product_master P ON M.p_code = P.p_code
    WHERE (B.stDate <= '$eDate' AND B.edDate >= '$sDate')
    ORDER BY B.stDate ASC
";

$rst_assign = mysqli_query($dbConn, $qry_assign);
if ($rst_assign) {
    while ($row = mysqli_fetch_assoc($rst_assign)) {

        // 표시용 가이드명(1명만)
        $guideNameStr = "";
        if (!empty($row['display_guide_id'])) {
            $memInfo = getinfo_dbMember($row['display_guide_id']);
            $guideNameStr = $memInfo['kor_name'] ?? $row['display_guide_id'];
        }
        $row['guide_name_all'] = $guideNameStr;

        // 상품명 없으면 메모/미지정
        if (empty($row['p_name'])) {
            $row['p_name'] = $row['memo'] ? $row['memo'] : "(상품 미지정)";
            $row['is_unassigned_product'] = true;
        } else {
            $row['is_unassigned_product'] = false;
        }

        // ✅ [핵심] 표시용 종료일 보정: 상품 연결된 블록이면 p_day 기반으로 강제 종료일 계산
        $displayEd = $row['edDate'];
        $pday = isset($row['p_day']) ? (int)$row['p_day'] : 0;
        if (!$row['is_unassigned_product'] && $pday > 0 && !empty($row['stDate'])) {
            if ($pday < 1) $pday = 1;
            $expectedEnd = date('Y-m-d', strtotime($row['stDate'] . ' +' . ($pday - 1) . ' day'));
            $displayEd = $expectedEnd;
        }

        $bid = $row['bus_num'];

        // 수동 블록 키 등록 (행사 자동 표시 시 중복 방지)
        if (!empty($row['grand_eCode'])) {
            $blockKeySet[$row['grand_eCode'] . '|' . $bid . '|' . $row['stDate']] = true;
        }

        $displayStart = ($row['stDate'] < $sDate) ? $sDate : $row['stDate'];
        $displayEnd   = ($displayEd > $eDate) ? $eDate : $displayEd;

        $d1 = new DateTime($displayStart);
        $d2 = new DateTime($displayEnd);
        $diff = $d1->diff($d2);
        $colspan = $diff->days + 1;

        $scheduleMap[$bid][$displayStart][] = [
            'colspan' => $colspan,
            'info'    => $row
        ];
    }
}

/* =================================================================================
 * 3-2-b. 등록된 모든 행사를 블록으로 표시 (tour_guide 차량배정 → 행사 블록)
 *  - 원래의 수동 블록(tour_car_block)은 그대로 유지 (위에서 먼저 채움)
 *  - 차량(c_id = bus_list.bus_id)이 배정된 행사를 읽기 전용 "행사" 블록으로 추가
 *  - 같은 (행사·차량·시작일) 수동 블록이 이미 있으면 중복 표시하지 않음
 * ================================================================================= */
$qry_event = "
    SELECT
        TG.grand_eCode,
        TG.c_id,
        TG.stDate,
        TG.bus_num       AS ord_bus,
        MAX(TG.guide_id) AS guide_id,
        MAX(TG.p_name)   AS tg_pname,
        P.p_name         AS p_name,
        IFNULL(P.p_day, 1) AS p_day,
        (SELECT COUNT(*)
            FROM tour_car TC
            WHERE TC.grand_eCode = TG.grand_eCode
              AND TC.stDate      = TG.stDate
              AND TC.bus_num     = TG.bus_num) AS pax_cnt
    FROM tour_guide TG
    LEFT JOIN tour_master M ON TG.grand_eCode = M.grand_eCode
    LEFT JOIN product_master P ON M.p_code = P.p_code
    WHERE TG.c_id IS NOT NULL AND TG.c_id <> ''
      AND TG.stDate <= '$eDate'
      AND DATE_ADD(TG.stDate, INTERVAL (IFNULL(P.p_day, 1) - 1) DAY) >= '$sDate'
    GROUP BY TG.grand_eCode, TG.c_id, TG.stDate, TG.bus_num, P.p_name, P.p_day
    ORDER BY TG.stDate ASC
";

$rst_event = mysqli_query($dbConn, $qry_event);
if ($rst_event) {
    while ($erow = mysqli_fetch_assoc($rst_event)) {
        $bid  = $erow['c_id'];
        $ekey = $erow['grand_eCode'] . '|' . $bid . '|' . $erow['stDate'];

        // 같은 행사·차량·날짜의 수동 블록이 이미 있으면 건너뜀
        if (isset($blockKeySet[$ekey])) continue;

        $pname = !empty($erow['p_name']) ? $erow['p_name'] : (!empty($erow['tg_pname']) ? $erow['tg_pname'] : '(행사)');

        // 가이드명(1명)
        $guideNameStr = "";
        if (!empty($erow['guide_id'])) {
            $memInfo = getinfo_dbMember($erow['guide_id']);
            $guideNameStr = $memInfo['kor_name'] ?? $erow['guide_id'];
        }

        $pday = (int)$erow['p_day'];
        if ($pday < 1) $pday = 1;
        $edFull = date('Y-m-d', strtotime($erow['stDate'] . ' +' . ($pday - 1) . ' day'));

        $info = [
            'seq_no'                => '',
            'grand_eCode'           => $erow['grand_eCode'],
            'bus_num'               => $bid,
            'guide_single'          => $erow['guide_id'],
            '_guide_single'         => $erow['guide_id'],
            'stDate'                => $erow['stDate'],
            'edDate'                => $edFull,
            'memo'                  => '',
            'color_code'            => '#16a085',
            'p_name'                => $pname,
            'p_day'                 => $pday,
            'locked_cnt'            => 0,
            'pax_cnt'               => (int)$erow['pax_cnt'],
            'guide_name_all'        => $guideNameStr,
            'is_unassigned_product' => false,
            'is_auto_event'         => true
        ];

        $displayStart = ($erow['stDate'] < $sDate) ? $sDate : $erow['stDate'];
        $displayEnd   = ($edFull > $eDate) ? $eDate : $edFull;

        $d1 = new DateTime($displayStart);
        $d2 = new DateTime($displayEnd);
        $diff = $d1->diff($d2);
        $colspan = $diff->days + 1;

        $scheduleMap[$bid][$displayStart][] = [
            'colspan' => $colspan,
            'info'    => $info
        ];
    }
}

/* =================================================================================
 * 3-3. 대기 목록 조회 (월별 그룹)
 *  - tour_car_cnt : 차량배정(tour_car) 존재 여부 → "등록됨"
 *  - block_cnt    : 블록테이블(tour_car_block) 존재 여부 → "가등록"
 * ================================================================================= */

$unassignedListData = [];

$qry_list = "SELECT
                M.grand_eCode, M.stDate, P.p_name, IFNULL(P.p_day, 1) as p_day,
                DATE_ADD(M.stDate, INTERVAL (IFNULL(P.p_day, 1) - 1) DAY) as edDate,

                (SELECT IFNULL(SUM(RI.p_cnt), 0)
                   FROM reserve_info RI
                  WHERE RI.p_code = M.p_code
                    AND RI.stDate = M.stDate
                    AND RI.rev_status != 'CANCEL') as p_cnt_total,

                (SELECT count(*) FROM tour_car TC WHERE TC.grand_eCode = M.grand_eCode) as tour_car_cnt,
                (SELECT count(*) FROM tour_car_block B WHERE B.grand_eCode = M.grand_eCode) as block_cnt

             FROM tour_master M
             LEFT JOIN product_master P ON M.p_code = P.p_code
             WHERE
                 (M.stDate <= '$eDate' AND DATE_ADD(M.stDate, INTERVAL (IFNULL(P.p_day, 1) - 1) DAY) >= '$sDate')
             AND M.stDate >= '$today'
             AND (
                   NOT EXISTS (SELECT 1 FROM reserve_info R0 WHERE R0.reserveCode = M.grand_eCode)
                   OR EXISTS (SELECT 1 FROM reserve_info R1 WHERE R1.reserveCode = M.grand_eCode AND R1.rev_status <> 'CANCEL')
                 )
             GROUP BY M.grand_eCode
             ORDER BY M.stDate ASC";

$rst_list = mysqli_query($dbConn, $qry_list);
if ($rst_list) {
    while ($row = mysqli_fetch_assoc($rst_list)) {
        $unassignedListData[] = $row;
    }
}

// 3-4. 월별 그룹 (구간 내 모든 월을 미리 생성 → 빈 월도 (0) 으로 표시)
$unassignedGroupedByMonth = [];
foreach ($dateHeaderMonths as $mk => $mInfo) {
    $unassignedGroupedByMonth[$mk] = [];
}
foreach ($unassignedListData as $item) {
    $mk = date('Y-m', strtotime($item['stDate']));
    if (!isset($unassignedGroupedByMonth[$mk])) {
        $unassignedGroupedByMonth[$mk] = [];
    }
    $unassignedGroupedByMonth[$mk][] = $item;
}
ksort($unassignedGroupedByMonth);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>차량 배정 스케줄러</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Noto Sans KR', sans-serif; background: #f5f6fa; margin: 0; padding: 20px; font-size: 12px; color: #2f3640; }

        .control-bar { background: #fff; padding: 10px 15px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .month-control { display: flex; align-items: center; gap: 20px; }
        .month-title { font-size: 20px; font-weight: 700; color: #2c3e50; }
        .btn-nav { background: #ecf0f1; border: 1px solid #bdc3c7; padding: 6px 12px; border-radius: 4px; text-decoration: none; color: #333; font-weight: 600; }
        .btn-today { background: #3498db; color: #fff; border-color: #2980b9; }
        .btn-manual { background: #27ae60; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-weight: bold; cursor: pointer; border: 1px solid #2ecc71; }
        .filter-bar { background: #fff; padding: 10px 15px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 8px; margin-bottom: 15px; flex-wrap: wrap; }
        .filter-bar label { font-size: 11px; color: #636e72; font-weight: 600; }
        .filter-bar input[type="date"], .filter-bar input[type="text"] { border: 1px solid #bdc3c7; border-radius: 4px; padding: 5px 8px; font-size: 12px; }
        .filter-bar input[type="text"] { width: 190px; }
        .filter-btn { border: 1px solid #27ae60; background: #27ae60; color: #fff; border-radius: 4px; padding: 6px 12px; font-weight: 600; cursor: pointer; }

        /* ✅ 대기목록 존: 월별 가로 스크롤 */
        .unassigned-zone {
            background: #fff;
            padding: 0 12px 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid #1abc9c;
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
        /* 카드가 sticky 헤더 위로 올라오지 않도록 낮은 스택 고정 */
        .card { z-index: 0; }

        .card-container { display: flex; gap: 8px; flex-wrap: wrap; padding-bottom: 5px; align-items: flex-start; min-height: 150px; }
        .card {
            background: #e0f2f1; border: 1px solid #b2dfdb; padding: 6px; border-radius: 4px;
            min-width: 150px; width: 150px; flex: 0 0 auto;
            cursor: pointer; transition: 0.2s; position: relative;
            height: auto; display: flex; flex-direction: column; justify-content: space-between;
            margin-bottom: 6px;
        }
        .card:hover { border-color: #009688; box-shadow: 0 2px 5px rgba(0,0,0,0.1); transform: translateY(-2px); }
        .card.is-pre-registered { background: #e8f4fd; border-color: #85c1e9; }
        .card.is-disabled { background: #ecf0f1; border-color: #d5d8dc; color: #95a5a6; cursor: not-allowed; opacity: 0.85; pointer-events: none; }
        .card.is-disabled:hover { transform: none; box-shadow: none; border-color: #d5d8dc; }
        .badge-pre { position: absolute; top: 4px; right: 4px; background: #2980b9; color: #fff; font-size: 9px; line-height: 1; padding: 3px 5px; border-radius: 8px; font-weight: 700; }
        .badge-disabled { position: absolute; top: 4px; right: 4px; background: #95a5a6; color: #fff; font-size: 9px; line-height: 1; padding: 3px 5px; border-radius: 8px; font-weight: 700; }
        .card-title { font-weight: bold; margin-bottom: 5px; white-space: normal; word-break: keep-all; line-height: 1.3; font-size: 10px; }
        .card-date { font-size: 10px; color: #16a085; margin-top: 5px; text-align: right; }

        .grid-wrapper { background: #fff; border-radius: 8px; overflow: auto; box-shadow: 0 1px 3px rgba(0,0,0,0.1); height: calc(100vh - 430px); min-height: 300px; }
        table.scheduler-table { width: 100%; border-collapse: separate; border-spacing: 0; min-width: 1400px; table-layout: fixed; }
        th, td { border: 1px solid #dfe6e9; border-top: 0; border-left: 0; padding: 0; text-align: center; vertical-align: top; background: #fff; }

        thead th { background: #2d3436; color: #fff; position: sticky; top: 0; z-index: 10; padding: 8px 0; height: 30px; }
        thead tr.month-row th { top: 0; height: 24px; padding: 4px 0; background: #34495e; z-index: 40; }
        thead tr.day-row th { top: 32px; z-index: 45; background: #2d3436; }
        .month-group { font-size: 12px; font-weight: 700; border-bottom: 1px solid #243342; }

        .fixed-col { position: sticky; left: 0; z-index: 20; background: #dfe6e9; color: #2d3436; width: 80px; min-width: 80px; font-weight: 600; border-right: 2px solid #b2bec3; }
        thead tr.month-row th.fixed-col { top: 0; left: 0; z-index: 60; background: #636e72; color: #fff; }

        .merged-cell {
            color: white; cursor: pointer; border-radius: 4px;
            box-sizing: border-box; padding: 6px 8px; margin: 2px;
            white-space: normal; overflow: visible; text-overflow: clip; word-break: break-word;
            min-height: 26px; line-height: 1.25;
            box-shadow: 0 1px 2px rgba(0,0,0,0.15);
            position: relative; z-index: 5; font-size: 11px;
            display: block; text-align: left;
        }
        .merged-cell:hover { opacity: 0.9; transform: translateY(-1px); z-index: 10; box-shadow: 0 3px 6px rgba(0,0,0,0.2); }
        .merged-cell.no-product {
            background-image: linear-gradient(45deg, rgba(255,255,255,0.15) 25%, transparent 25%, transparent 50%, rgba(255,255,255,0.15) 50%, rgba(255,255,255,0.15) 75%, transparent 75%, transparent);
            background-size: 10px 10px;
            border: 1px dashed rgba(255,255,255,0.5);
        }
        /* 등록된 행사(읽기 표시용) 블록: 점선 테두리로 수동 블록과 구분 */
        .merged-cell.auto-event {
            border: 1px dashed rgba(255,255,255,0.85);
            box-shadow: 0 1px 2px rgba(0,0,0,0.15), inset 0 0 0 1px rgba(255,255,255,0.2);
        }

        .js-click-modal { cursor: pointer; }
        .empty-cell { background: #fff; }
        .weekend-cell { background: #f9f9f9; }

        .modal-overlay { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.5); z-index: 999; justify-content: center; align-items: center; }
        .modal-content { background: white; padding: 20px; border-radius: 5px; width: 400px; }
        .form-row { margin-bottom: 10px; }
        .form-row label { display: block; font-size: 11px; color: #7f8c8d; margin-bottom: 3px; }
        .form-row input, .form-row select { width: 100%; padding: 6px; border: 1px solid #ccc; border-radius: 3px; box-sizing: border-box; }

        .btn { padding: 6px 12px; border: none; border-radius: 3px; color: white; cursor: pointer; }

        .color-grid { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 5px; }
        .color-swatch { width: 28px; height: 28px; border-radius: 4px; cursor: pointer; border: 1px solid rgba(0,0,0,0.1); transition: all 0.2s; }
        .color-swatch:hover { transform: scale(1.1); box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .color-swatch.selected { border: 2px solid #2c3e50; transform: scale(1.1); box-shadow: 0 0 0 2px rgba(255,255,255,0.8) inset; }
    </style>
</head>
<body>

<div class="control-bar">
    <div class="month-control">
        <a href="?year=<?= date('Y', $prevDate) ?>&month=<?= date('m', $prevDate) ?>" class="btn-nav">&lt; 이전달</a>
        <div class="month-title"><?= htmlspecialchars($sDate) ?> ~ <?= htmlspecialchars($eDate) ?> 차량 스케줄</div>
        <a href="?year=<?= date('Y', $nextDate) ?>&month=<?= date('m', $nextDate) ?>" class="btn-nav">다음달 &gt;</a>
        <a href="?year=<?= date('Y') ?>&month=<?= date('m') ?>" class="btn-nav btn-today">오늘</a>
    </div>
    <button type="button" class="btn-manual" onclick="openModalManual()">[+] 차량 스케줄 수동 등록</button>
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
    <?php car_block_render_unassigned_months($unassignedGroupedByMonth); ?>
</div>

<div class="grid-wrapper">
    <table class="scheduler-table">
        <colgroup>
            <col style="width: 80px;">
            <?php foreach($dateHeaders as $dt): ?>
            <col style="width: 40px;">
            <?php endforeach; ?>
        </colgroup>
        <thead>
            <tr class="month-row">
                <th class="fixed-col" rowspan="2">차량</th>
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
            <?php foreach($busList as $bus): $skipCount = 0; ?>
            <tr>
                <td class="fixed-col" style="padding: 5px; vertical-align:middle;">
                    <div style="font-weight:bold;"><?= $bus['bus_id'] ?></div>
                    <div style="font-size:10px; color:#636e72;"><?= $bus['bus_number'] ?></div>
                </td>

                <?php foreach($dateHeaders as $dt):
                    if ($skipCount > 0) { $skipCount--; continue; }

                    $events = isset($scheduleMap[$bus['bus_id']][$dt]) ? $scheduleMap[$bus['bus_id']][$dt] : null;

                    if ($events) {
                        $maxColspan = 1;
                        foreach($events as $ev) {
                            if($ev['colspan'] > $maxColspan) $maxColspan = $ev['colspan'];
                        }
                        $skipCount = $maxColspan - 1;
                ?>
                    <td colspan="<?= $maxColspan ?>" style="padding: 1px;">
                        <?php foreach($events as $cell):
                            $info        = $cell['info'];
                            $isLocked    = ($info['locked_cnt'] > 0);
                            $isAutoEvent = !empty($info['is_auto_event']);
                            $cls         = $isLocked ? 'locked' : '';
                            if (!empty($info['is_unassigned_product'])) $cls .= ' no-product';
                            if ($isAutoEvent) $cls .= ' auto-event';
                            $cellMode = $isAutoEvent ? 'new' : 'edit';

                            $paxCnt = isset($info['pax_cnt']) ? (int)$info['pax_cnt'] : 0;

                            $txt = $info['p_name'];
                            if(!empty($info['guide_name_all'])) $txt .= " ({$info['guide_name_all']})";

                            $tooltip = $txt . " (" . ((int)($info['p_day'] ?? 0) ?: 1) . "일)";
                            if ($paxCnt > 0) $tooltip .= " / 배정 " . $paxCnt . "명";
                            if ($isAutoEvent) $tooltip .= "\n[등록된 행사] 클릭 시 블록으로 등록";
                            if (!empty($info['memo'])) $tooltip .= "\n[메모] " . $info['memo'];

                            $paxHtml = $paxCnt > 0 ? ' <span style="opacity:0.95;">/ ' . $paxCnt . '명</span>' : '';
                            $displayHtml = "<div>" . ($isAutoEvent ? '🚌 ' : '') . htmlspecialchars($txt) . $paxHtml . "</div>";
                            if (!empty($info['memo'])) {
                                $displayHtml .= '<div style="margin-top:4px; font-size:10px; opacity:0.95; border-top:1px solid rgba(255,255,255,0.35); padding-top:3px; white-space: normal; line-height: 1.25;">';
                                $displayHtml .= '📝 ' . htmlspecialchars($info['memo']);
                                $displayHtml .= '</div>';
                            }

                            $bg_color = !empty($info['color_code']) ? $info['color_code'] : '#3498db';

                            $jsonRaw = defined('JSON_UNESCAPED_UNICODE') ? json_encode($info, JSON_UNESCAPED_UNICODE) : json_encode($info);
                            if($jsonRaw === false) $jsonRaw = '{}';
                            $base64Str = base64_encode($jsonRaw);
                        ?>
                        <div class="merged-cell <?= $cls ?> js-click-modal"
                             style="background-color: <?= $bg_color ?>;"
                             title="<?= htmlspecialchars($tooltip) ?>"
                             data-mode="<?= $cellMode ?>"
                             data-b64="<?=$base64Str?>">
                            <?= $displayHtml ?>
                        </div>
                        <?php endforeach; ?>
                    </td>
                <?php } else {
                        $w = date('w', strtotime($dt));
                        $bg = ($w==0 || $w==6) ? 'weekend-cell' : 'empty-cell';
                ?>
                    <td class="<?= $bg ?>"></td>
                <?php } ?>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div id="assignModal" class="modal-overlay">
    <div class="modal-content">
        <h3 id="modal_title" style="margin-top:0;">배정 정보</h3>
        <form method="POST" id="modal_form">
            <input type="hidden" name="mode" id="modal_mode">
            <input type="hidden" name="seq_no" id="modal_seq">
            <input type="hidden" name="grand_eCode" id="modal_ecode_hidden">
            <input type="hidden" name="guide_single" id="modal_guide_single" value="">

            <div class="form-row">
                <label>상품명 (연결 대상)</label>
                <input type="text" id="modal_pname_readonly" readonly style="background:#eee; display:none;">

                <select id="modal_pname_select" style="display:none;" onchange="updateEcode(this)">
                    <option value="" data-sdate="" data-days="">[상품 미지정] - 메모로 관리</option>
                    <?php foreach($unassignedListData as $uItem): ?>
                        <option value="<?= $uItem['grand_eCode'] ?>"
                                data-sdate="<?= $uItem['stDate'] ?>"
                                data-days="<?= (int)$uItem['p_day'] ?>">
                            [<?= $uItem['stDate'] ?>] <?= $uItem['p_name'] ?> (<?= (int)$uItem['p_day'] ?>일)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-row">
                <label>스케줄 색상 선택</label>
                <input type="hidden" name="color_code" id="modal_color" value="#3498db">
                <div class="color-grid" id="color_palette"></div>
            </div>

            <div class="form-row">
                <label>차량 선택 (필수)</label>
                <select name="bus_num" id="modal_select_bus" required>
                    <option value="">-- 차량 선택 --</option>
                    <?php foreach($busList as $b): ?>
                    <option value="<?= $b['bus_id'] ?>"><?= $b['bus_id'] ?> (<?= $b['bus_number'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-row">
                <label>기간</label>
                <div style="display:flex; gap:5px;">
                    <input type="date" name="stDate" id="modal_sdate" required>
                    <input type="date" name="edDate" id="modal_edate" required>
                </div>
            </div>

            <div class="form-row">
                <label>메모</label>
                <input type="text" name="memo" id="modal_memo" placeholder="상품 미지정 시 표시될 내용 입력">
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
const colorList = [
    '#3498db', '#2980b9',
    '#e74c3c', '#c0392b',
    '#2ecc71', '#27ae60',
    '#f1c40f', '#f39c12',
    '#9b59b6', '#8e44ad',
    '#1abc9c', '#16a085',
    '#e67e22', '#d35400',
    '#34495e', '#7f8c8d'
];

document.addEventListener('DOMContentLoaded', function() {
    renderColorPalette();

    document.body.addEventListener('click', function(e) {
        var target = e.target.closest('.js-click-modal');
        if (target) {
            var mode = target.getAttribute('data-mode');
            var b64  = target.getAttribute('data-b64');
            openModalFromData(mode, b64);
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
    var fixedWidth = 80;
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

function selectColor(color) {
    document.getElementById('modal_color').value = color;
    const swatches = document.querySelectorAll('.color-swatch');
    swatches.forEach(swatch => swatch.classList.remove('selected'));
    for(let i=0; i<swatches.length; i++) {
        if (colorList[i] && colorList[i].toLowerCase() === color.toLowerCase()) {
            swatches[i].classList.add('selected');
        }
    }
}

function openModalManual() {
    document.getElementById('modal_title').innerText = "차량 스케줄 수동 등록 (상품 미지정)";
    document.getElementById('modal_mode').value = 'assign';
    document.getElementById('modal_seq').value = '';
    document.getElementById('modal_ecode_hidden').value = '';
    document.getElementById('modal_guide_single').value = '';

    document.getElementById('modal_pname_readonly').style.display = 'none';
    document.getElementById('modal_pname_select').style.display = 'block';
    document.getElementById('modal_pname_select').value = '';

    document.getElementById('modal_select_bus').value = '';
    document.getElementById('modal_select_bus').disabled = false;

    var today = '<?= date("Y-m-d") ?>';
    document.getElementById('modal_sdate').value = today;
    document.getElementById('modal_edate').value = today;
    document.getElementById('modal_sdate').readOnly = false;
    document.getElementById('modal_edate').readOnly = false;
    document.getElementById('modal_sdate').style.background = '#fff';
    document.getElementById('modal_edate').style.background = '#fff';

    document.getElementById('modal_memo').value = '';
    selectColor('#8e44ad');

    document.getElementById('btn_save').style.display = 'inline-block';
    document.getElementById('btn_del').style.display = 'none';

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
    document.getElementById('modal_select_bus').value = data.bus_num || '';
    document.getElementById('modal_sdate').value = data.stDate || '';
    document.getElementById('modal_edate').value = data.edDate || '';
    document.getElementById('modal_memo').value  = data.memo || '';

    document.getElementById('modal_guide_single').value = data._guide_single || data.guide_single || '';

    var savedColor = data.color_code || '#3498db';
    selectColor(savedColor);

    if (mode === 'new') {
        document.getElementById('modal_title').innerText = "신규 배정";
        document.getElementById('modal_mode').value = 'assign';

        document.getElementById('modal_pname_readonly').value = data.p_name || '';
        document.getElementById('modal_pname_readonly').style.display = 'block';
        document.getElementById('modal_pname_select').style.display = 'none';

        document.getElementById('modal_sdate').readOnly = true;
        document.getElementById('modal_edate').readOnly = true;
        document.getElementById('modal_sdate').style.background = '#eee';
        document.getElementById('modal_edate').style.background = '#eee';

        document.getElementById('btn_save').style.display = 'inline-block';
        document.getElementById('btn_del').style.display = 'none';
    } else {
        document.getElementById('modal_title').innerText = "배정 상세 / 수정";
        document.getElementById('modal_mode').value = 'assign';

        var isUnassignedProduct = data.is_unassigned_product;

        if (isUnassignedProduct) {
            document.getElementById('modal_pname_readonly').style.display = 'none';
            document.getElementById('modal_pname_select').style.display = 'block';
            document.getElementById('modal_pname_select').value = data.grand_eCode || '';
        } else {
            document.getElementById('modal_pname_readonly').value = data.p_name || '';
            document.getElementById('modal_pname_readonly').style.display = 'block';
            document.getElementById('modal_pname_select').style.display = 'none';
        }

        document.getElementById('modal_sdate').readOnly = false;
        document.getElementById('modal_edate').readOnly = false;
        document.getElementById('modal_sdate').style.background = '#fff';
        document.getElementById('modal_edate').style.background = '#fff';

        document.getElementById('btn_save').style.display = 'inline-block';
        document.getElementById('btn_del').style.display = 'inline-block';
    }

    document.getElementById('assignModal').style.display = 'flex';
}

/* ✅ 수동 드롭다운 선택시 종료일 계산: + (days-1) */
function updateEcode(sel) {
    document.getElementById('modal_ecode_hidden').value = sel.value;

    var selectedOption = sel.options[sel.selectedIndex];
    var sDateVal = selectedOption.getAttribute('data-sdate');
    var daysVal  = selectedOption.getAttribute('data-days');

    if (sDateVal && daysVal) {
        document.getElementById('modal_sdate').value = sDateVal;

        var dateObj = new Date(sDateVal);
        var days = parseInt(daysVal, 10);
        if (isNaN(days) || days < 1) days = 1;

        dateObj.setDate(dateObj.getDate() + (days - 1));

        var y = dateObj.getFullYear();
        var m = ('0' + (dateObj.getMonth() + 1)).slice(-2);
        var d = ('0' + dateObj.getDate()).slice(-2);
        document.getElementById('modal_edate').value = y + '-' + m + '-' + d;
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
window.onclick = function(e) { if(e.target == document.getElementById('assignModal')) closeModal(); }
</script>

</body>
</html>
