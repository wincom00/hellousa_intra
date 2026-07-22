<?php
// send_breakdown_excel_mysqli.php - 이메일 발송 제거 + 엑셀 다운로드로 변경 (PHP 7.0, mysqli)
// 기존 구조를 최대한 유지하되, mysql_* → mysqli_* 로 변환

error_reporting(E_ALL);
ini_set('display_errors', 0);

/**
 * 엔드포인트 동작:
 *   GET ?action=download_excel&estimate_id=123
 *   - 브라우저로 "BREAKDOWN_QUOTATION_그룹명_YYYYMMDD.xls" 파일을 바로 다운로드
 *
 * 필요 include:
 *   include "include/inc_base.php"; // 여기서 $dbConn (mysqli 연결) 준비
 */

// ===== 메인 엔트리 =====
if (isset($_GET['action']) && $_GET['action'] === 'download_excel') {
    require_once __DIR__ . "/include/inc_base.php"; // $dbConn (mysqli)

    $estimateId = isset($_GET['estimate_id']) ? (int)$_GET['estimate_id'] : 0;
    if ($estimateId <= 0) {
        header("Content-Type: text/html; charset=UTF-8");
        echo "<script>alert('견적서 ID가 필요합니다.'); history.back();</script>";
        exit;
    }

    $result = streamBreakdownExcel($dbConn, $estimateId);
    if ($result !== true) {
        header("Content-Type: text/html; charset=UTF-8");
        echo "<script>alert('엑셀 내보내기 실패: " . htmlspecialchars((string)$result, ENT_QUOTES, 'UTF-8') . "'); history.back();</script>";
    }
    exit;
} else {
    // 간단 테스트 폼 유지
    echo "<!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>BREAKDOWN QUOTATION 엑셀 다운로드</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 520px; margin: 50px auto; padding: 20px; }
            .form-group { margin-bottom: 15px; }
            label { display: block; margin-bottom: 5px; font-weight: bold; }
            input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
            button { background: #2E86AB; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        </style>
    </head>
    <body>
        <h2>BREAKDOWN QUOTATION 엑셀 다운로드</h2>
        <form method='GET'>
            <input type='hidden' name='action' value='download_excel'>
            <div class='form-group'>
                <label for='estimate_id'>견적서 ID:</label>
                <input type='number' id='estimate_id' name='estimate_id' required placeholder='estimate_master의 id'>
            </div>
            <div class='form-group'>
                <button type='submit'>엑셀 다운로드</button>
            </div>
        </form>
    </body>
    </html>";
}

/**
 * 브라우저로 바로 XLS(HTML-Table) 스트리밍
 * @return true|string 성공시 true, 실패시 오류메시지
 */
function streamBreakdownExcel(mysqli $dbConn, int $estimateId) {
    // 1) 마스터
    $sql = "SELECT * FROM estimate_master WHERE id = ? LIMIT 1";
    if (!$stmt = $dbConn->prepare($sql)) return "master prepare 실패: ".$dbConn->error;
    $stmt->bind_param("i", $estimateId);
    if (!$stmt->execute()) return "master execute 실패: ".$stmt->error;
    $master = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$master) return "견적서를 찾을 수 없습니다. id=".$estimateId;

    // 2) 아이템
    $sql = "SELECT * FROM estimate_items WHERE estimate_id = ? ORDER BY section, id";
    if (!$stmt = $dbConn->prepare($sql)) return "items prepare 실패: ".$dbConn->error;
    $stmt->bind_param("i", $estimateId);
    if (!$stmt->execute()) return "items execute 실패: ".$stmt->error;
    $rs = $stmt->get_result();
    $items = [];
    while ($row = $rs->fetch_assoc()) $items[] = $row;
    $stmt->close();

    $groupName = trim((string)($master['group_name'] ?? '')) ?: ('GROUP_'.$estimateId);
    $safeGroup = preg_replace('/[^a-zA-Z0-9가-힣_-]+/', '_', $groupName);
    $filename  = "BREAKDOWN_QUOTATION_{$safeGroup}_".date('Ymd').".xls";

    // 3) 섹션화
    $sections = [];
    foreach ($items as $it) {
        $sec = $it['section'] ?? 'ETC';
        if (!isset($sections[$sec])) $sections[$sec] = [];
        $sections[$sec][] = $it;
    }

    // 4) HTML-Table 기반 XLS 콘텐츠
    $content = "\xEF\xBB\xBF"; // UTF-8 BOM
    $content .= '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;font-family:Arial,sans-serif;">';

    // 제목
    $content .= '<tr><td colspan="8" style="background:#2E86AB;color:white;font-size:20px;text-align:center;font-weight:bold;height:50px;">BREAKDOWN QUOTATION</td></tr>';

    // 기본정보
    $content .= '<tr>';
    $content .= '<td style="background:#f5f5f5;font-weight:bold;text-align:center;">TO</td><td>'.htmlspecialchars((string)($master['to_name']??''), ENT_QUOTES, 'UTF-8').'</td>';
    $content .= '<td style="background:#f5f5f5;font-weight:bold;text-align:center;">GROUP</td><td>'.htmlspecialchars((string)($master['group_name']??''), ENT_QUOTES, 'UTF-8').'</td>';
    $content .= '<td style="background:#f5f5f5;font-weight:bold;text-align:center;">PAX</td><td style="text-align:center;">'.(int)($master['pax']??0).'</td>';
    $content .= '<td style="background:#f5f5f5;font-weight:bold;text-align:center;">FOC</td><td style="text-align:center;">'.(int)($master['foc']??0).'</td>';
    $content .= '</tr>';

    $content .= '<tr>';
    $content .= '<td style="background:#f5f5f5;font-weight:bold;text-align:center;">시작일</td><td>'.htmlspecialchars((string)($master['start_date']??''), ENT_QUOTES, 'UTF-8').'</td>';
    $content .= '<td style="background:#f5f5f5;font-weight:bold;text-align:center;">종료일</td><td>'.htmlspecialchars((string)($master['end_date']??''), ENT_QUOTES, 'UTF-8').'</td>';
    $content .= '<td style="background:#f5f5f5;font-weight:bold;text-align:center;">총인원</td><td style="text-align:center;">'.(int)($master['total_pax']??0).'</td>';
    $content .= '<td style="background:#f5f5f5;font-weight:bold;text-align:center;">작성일</td><td>'.htmlspecialchars((string)($master['wdate']??''), ENT_QUOTES, 'UTF-8').'</td>';
    $content .= '</tr>';

    // 빈 행
    $content .= '<tr><td colspan="8" style="height:15px;"></td></tr>';

    $sectionTitles = [
        'HOTEL'     => '1) HOTEL',
        'MEAL'      => '2) MEAL',
        'TRANSPORT' => '3) TRANSPORTATION',
        'TICKET'    => '4) 입장권',
        'GUIDE'     => '5) 가이드/기사',
        'ETC'       => '7) 기타경비',
    ];

    $sectionTotals = [];

    // HOTEL
    if (!empty($sections['HOTEL'])) {
        $content .= '<tr><td colspan="8" style="background:#A23B72;color:white;font-weight:bold;padding:10px;">'.$sectionTitles['HOTEL'].'</td></tr>';
        $content .= '<tr style="background:#f8f9fa;font-weight:bold;">'
                  . '<td style="text-align:center;">지역</td><td style="text-align:center;">날짜</td><td style="text-align:center;">요일</td><td style="text-align:center;">호텔명</td>'
                  . '<td style="text-align:center;">방수</td><td style="text-align:center;">요금(USD)</td><td style="text-align:center;">박수</td><td style="text-align:center;">합계</td>'
                  . '</tr>';
        $hotel_total = 0.0;
        foreach ($sections['HOTEL'] as $it) {
            $etc = json_decode((string)($it['etc_json']??'{}'), true) ?: [];
            $content .= '<tr>';
            $content .= '<td style="text-align:center;">'.htmlspecialchars((string)($etc['region']??''), ENT_QUOTES, 'UTF-8').'</td>';
            $content .= '<td style="text-align:center;">'.htmlspecialchars((string)($etc['date']??''), ENT_QUOTES, 'UTF-8').'</td>';
            $content .= '<td style="text-align:center;">'.htmlspecialchars((string)($etc['weekday']??''), ENT_QUOTES, 'UTF-8').'</td>';
            $content .= '<td>'.htmlspecialchars((string)($it['label']??''), ENT_QUOTES, 'UTF-8').'</td>';
            $content .= '<td style="text-align:center;">'.(float)($it['cnt']??0).'</td>';
            $content .= '<td style="text-align:right;color:#2E86AB;font-weight:bold;">$'.number_format((float)($it['unit']??0), 2).'</td>';
            $content .= '<td style="text-align:center;">'.(float)($it['qty']??0).'</td>';
            $content .= '<td style="text-align:right;color:#2E86AB;font-weight:bold;">$'.number_format((float)($it['sum']??0), 2).'</td>';
            $content .= '</tr>';
            $hotel_total += (float)($it['sum']??0);
        }
        $content .= '<tr style="background:#e3f2fd;font-weight:bold;">'
                  . '<td colspan="7" style="text-align:right;">HOTEL 소계</td>'
                  . '<td style="text-align:right;color:#1976D2;">$'.number_format($hotel_total, 2).'</td>'
                  . '</tr>';
        $sectionTotals['HOTEL'] = $hotel_total;
        $content .= '<tr><td colspan="8" style="height:10px;"></td></tr>';
    }

    // 기타 섹션 공통 처리
    $other = ['MEAL','TRANSPORT','TICKET','GUIDE','ETC'];
    foreach ($other as $key) {
        if (empty($sections[$key])) continue;
        $content .= '<tr><td colspan="8" style="background:#A23B72;color:white;font-weight:bold;padding:10px;">'.$sectionTitles[$key].'</td></tr>';

        if ($key === 'MEAL') {
            $content .= '<tr style="background:#f8f9fa;font-weight:bold;">'
                      . '<td style="text-align:center;">날짜</td><td style="text-align:center;">시간</td><td style="text-align:center;">식당명</td><td style="text-align:center;">메뉴</td>'
                      . '<td style="text-align:center;">인원</td><td style="text-align:center;">단가(USD)</td><td style="text-align:center;">수량</td><td style="text-align:center;">합계</td>'
                      . '</tr>';
        } elseif ($key === 'TRANSPORT') {
            $content .= '<tr style="background:#f8f9fa;font-weight:bold;">'
                      . '<td style="text-align:center;">날짜</td><td style="text-align:center;">시간</td><td style="text-align:center;">차량종류</td><td style="text-align:center;">구간</td>'
                      . '<td style="text-align:center;">대수</td><td style="text-align:center;">요금(USD)</td><td style="text-align:center;">일수</td><td style="text-align:center;">합계</td>'
                      . '</tr>';
        } elseif ($key === 'TICKET') {
            $content .= '<tr style="background:#f8f9fa;font-weight:bold;">'
                      . '<td style="text-align:center;">날짜</td><td style="text-align:center;">시간</td><td style="text-align:center;">장소명</td><td style="text-align:center;">입장권종류</td>'
                      . '<td style="text-align:center;">인원</td><td style="text-align:center;">요금(USD)</td><td style="text-align:center;">수량</td><td style="text-align:center;">합계</td>'
                      . '</tr>';
        } elseif ($key === 'GUIDE') {
            $content .= '<tr style="background:#f8f9fa;font-weight:bold;">'
                      . '<td style="text-align:center;">날짜</td><td style="text-align:center;">시간</td><td style="text-align:center;">가이드명</td><td style="text-align:center;">서비스종류</td>'
                      . '<td style="text-align:center;">명수</td><td style="text-align:center;">요금(USD)</td><td style="text-align:center;">일수</td><td style="text-align:center;">합계</td>'
                      . '</tr>';
        } else { // ETC
            $content .= '<tr style="background:#f8f9fa;font-weight:bold;">'
                      . '<td style="text-align:center;">날짜</td><td style="text-align:center;">시간</td><td style="text-align:center;">항목명</td><td style="text-align:center;">내용</td>'
                      . '<td style="text-align:center;">인원</td><td style="text-align:center;">요금(USD)</td><td style="text-align:center;">수량</td><td style="text-align:center;">합계</td>'
                      . '</tr>';
        }

        $sec_total = 0.0;
        foreach ($sections[$key] as $it) {
            $etc = json_decode((string)($it['etc_json']??'{}'), true) ?: [];
            $content .= '<tr>';
            if ($key === 'MEAL') {
                $content .= '<td style="text-align:center;">'.htmlspecialchars((string)($etc['date']??''), ENT_QUOTES, 'UTF-8').'</td>';
                $content .= '<td style="text-align:center;">'.htmlspecialchars((string)($etc['time']??''), ENT_QUOTES, 'UTF-8').'</td>';
                $content .= '<td>'.htmlspecialchars((string)($etc['restaurant']??($it['label']??'')), ENT_QUOTES, 'UTF-8').'</td>';
                $content .= '<td>'.htmlspecialchars((string)($etc['menu']??($it['description']??'')), ENT_QUOTES, 'UTF-8').'</td>';
            } elseif ($key === 'TRANSPORT') {
                $content .= '<td style="text-align:center;">'.htmlspecialchars((string)($etc['date']??''), ENT_QUOTES, 'UTF-8').'</td>';
                $content .= '<td style="text-align:center;">'.htmlspecialchars((string)($etc['time']??''), ENT_QUOTES, 'UTF-8').'</td>';
                $content .= '<td>'.htmlspecialchars((string)($etc['vehicle_type']??($it['label']??'')), ENT_QUOTES, 'UTF-8').'</td>';
                $content .= '<td>'.htmlspecialchars((string)($etc['route']??($it['description']??'')), ENT_QUOTES, 'UTF-8').'</td>';
            } elseif ($key === 'TICKET') {
                $content .= '<td style="text-align:center;">'.htmlspecialchars((string)($etc['date']??''), ENT_QUOTES, 'UTF-8').'</td>';
                $content .= '<td style="text-align:center;">'.htmlspecialchars((string)($etc['time']??''), ENT_QUOTES, 'UTF-8').'</td>';
                $content .= '<td>'.htmlspecialchars((string)($etc['place']??($it['label']??'')), ENT_QUOTES, 'UTF-8').'</td>';
                $content .= '<td>'.htmlspecialchars((string)($etc['ticket_type']??($it['description']??'')), ENT_QUOTES, 'UTF-8').'</td>';
            } elseif ($key === 'GUIDE') {
                $content .= '<td style="text-align:center;">'.htmlspecialchars((string)($etc['date']??''), ENT_QUOTES, 'UTF-8').'</td>';
                $content .= '<td style="text-align:center;">'.htmlspecialchars((string)($etc['time']??''), ENT_QUOTES, 'UTF-8').'</td>';
                $content .= '<td>'.htmlspecialchars((string)($etc['guide_name']??($it['label']??'')), ENT_QUOTES, 'UTF-8').'</td>';
                $content .= '<td>'.htmlspecialchars((string)($etc['service_type']??($it['description']??'')), ENT_QUOTES, 'UTF-8').'</td>';
            } else { // ETC
                $content .= '<td style="text-align:center;">'.htmlspecialchars((string)($etc['date']??''), ENT_QUOTES, 'UTF-8').'</td>';
                $content .= '<td style="text-align:center;">'.htmlspecialchars((string)($etc['time']??''), ENT_QUOTES, 'UTF-8').'</td>';
                $content .= '<td>'.htmlspecialchars((string)($it['label']??''), ENT_QUOTES, 'UTF-8').'</td>';
                $content .= '<td>'.htmlspecialchars((string)($it['description']??''), ENT_QUOTES, 'UTF-8').'</td>';
            }
            $content .= '<td style="text-align:center;">'.(float)($it['cnt']??0).'</td>';
            $content .= '<td style="text-align:right;color:#2E86AB;font-weight:bold;">$'.number_format((float)($it['unit']??0), 2).'</td>';
            $content .= '<td style="text-align:center;">'.(float)($it['qty']??0).'</td>';
            $content .= '<td style="text-align:right;color:#2E86AB;font-weight:bold;">$'.number_format((float)($it['sum']??0), 2).'</td>';
            $content .= '</tr>';
            $sec_total += (float)($it['sum']??0);
        }

        $content .= '<tr style="background:#e3f2fd;font-weight:bold;">'
                  . '<td colspan="7" style="text-align:right;">'.$sectionTitles[$key].' 소계</td>'
                  . '<td style="text-align:right;color:#1976D2;">$'.number_format($sec_total, 2).'</td>'
                  . '</tr>';
        $content .= '<tr><td colspan="8" style="height:10px;"></td></tr>';

        $sectionTotals[$key] = $sec_total;
    }

    // 합계
    $content .= '<tr><td colspan="8" style="height:15px;"></td></tr>';
    $content .= '<tr style="background:#2E86AB;color:white;font-weight:bold;font-size:18px;">'
              . '<td colspan="4" style="text-align:center;padding:15px;">10) TOTAL TOUR FEE</td>'
              . '<td colspan="4" style="text-align:center;padding:15px;">$'.number_format((float)($master['grand_total']??0), 2).'</td>'
              . '</tr>';
    $content .= '<tr style="background:#2E86AB;color:white;font-weight:bold;font-size:18px;">'
              . '<td colspan="4" style="text-align:center;padding:15px;">11) 1인당 요금</td>'
              . '<td colspan="4" style="text-align:center;padding:15px;">$'.number_format((float)($master['per_pax']??0), 2).'</td>'
              . '</tr>';

    $content .= '</table>';

    // 5) 헤더 후 출력 (다운로드)
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    header('Cache-Control: max-age=0');
    echo $content;
    return true;
}
