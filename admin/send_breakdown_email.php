<?php
// send_breakdown_email_classic.php - 클래식 PHPMailer 사용 버전

error_reporting(E_ALL);
ini_set('display_errors', 1);

include "./PHPMailer/class.phpmailer.php";

function sendBreakdownEmail($dbConn, $userId, $estimateId) {
    try {
        // 1. member_list에서 사용자 정보 가져오기 (company_email 우선, 없으면 email)
        $userQuery = "SELECT company_email, email, kor_name FROM member_list WHERE userid = ?";
        $userStmt = $dbConn->prepare($userQuery);
        $userStmt->bind_param("s", $userId);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        $userInfo = $userResult->fetch_assoc();
        
		/*
        if (!$userInfo) {
            return "사용자를 찾을 수 없습니다: " . $userId;
        }
		*/
        
        // company_email이 있으면 우선 사용, 없으면 email 사용
        $recipientEmail = !empty($userInfo['company_email']) ? $userInfo['company_email'] : $userId;
        $recipientName = $userInfo['kor_name'] ?: $userId;
        
        if (empty($recipientEmail)) {
            return "이메일 주소가 없습니다: " . $userId;
        }
        
        // 2. 견적서 정보 가져오기
        $estimateQuery = "SELECT * FROM estimate_master WHERE id = ?";
        $estimateStmt = $dbConn->prepare($estimateQuery);
        $estimateStmt->bind_param("i", $estimateId);
        $estimateStmt->execute();
        $estimateResult = $estimateStmt->get_result();
        $estimateInfo = $estimateResult->fetch_assoc();
        
        if (!$estimateInfo) {
            return "견적서를 찾을 수 없습니다: " . $estimateId;
        }
        
        $groupName = $estimateInfo['group_name'] ?: 'GROUP_' . $estimateId;
        
        // 3. 제목 생성 (그룹명 포함)
        $subject = "[견적서] {$groupName} - BREAKDOWN QUOTATION";
        $subj = iconv("UTF-8", "UTF-8//IGNORE", $subject);
        
        // 4. 이메일 내용 생성
        $content = generateEmailContent($recipientName, $estimateInfo, $groupName);
        $value = stripslashes((string)$content);
        
        // 5. 첨부파일 생성 (그룹명 포함 파일명)
        $attachmentPath = createBreakdownAttachment($dbConn, $estimateId, $groupName);
        $attachments = [$attachmentPath];
        
        // 6. PHPMailer 설정 및 발송 (클래식 방식)
        $mail = new PHPMailer();
        $mail->IsSMTP();
        
        $mail->CharSet = "UTF-8";
        $mail->SMTPDebug = 0;
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = 'tls';
        $mail->Host = 'in-v3.mailjet.com';
        $mail->Port = 587;
        $mail->Username = "201aa76dd3ef231dc54a7db95432252f"; // 실제 Mailjet API Key
        $mail->Password = "8333a812fce7f25ae3d61401b41c8f67"; // 실제 Mailjet Secret Key
        $mail->SetFrom("admin@dongbutour.com", "Tour Hello USA");
        
        $mail->Subject = $subj;
        $mail->MsgHTML($value);
        $mail->AddAddress($recipientEmail, $recipientName);
        
        // 첨부파일 추가
        foreach($attachments as $attachment) {
            $mail->AddAttachment($attachment);
        }
        
        // 이메일 발송
        if(!$mail->Send()) {
            // 임시 파일 삭제
            if (file_exists($attachmentPath)) {
                unlink($attachmentPath);
            }
            return $mail->ErrorInfo;
        } else {
            // 임시 파일 삭제
            if (file_exists($attachmentPath)) {
                unlink($attachmentPath);
            }
            return true;
        }
        
    } catch (Exception $e) {
        return "이메일 발송 실패: " . $e->getMessage();
    }
}

/**
 * 이메일 내용 생성
 */
function generateEmailContent($recipientName, $estimateInfo, $groupName) {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { 
                background: linear-gradient(135deg, #2E86AB 0%, #A23B72 100%); 
                color: white; 
                padding: 20px; 
                text-align: center; 
                border-radius: 10px 10px 0 0; 
            }
            .content { 
                background: #f9f9f9; 
                padding: 30px; 
                border: 1px solid #ddd; 
            }
            .footer { 
                background: #333; 
                color: white; 
                padding: 15px; 
                text-align: center; 
                border-radius: 0 0 10px 10px; 
                font-size: 12px; 
            }
            .info-box { 
                background: white; 
                padding: 20px; 
                margin: 20px 0; 
                border-left: 4px solid #2E86AB; 
                border-radius: 5px;
            }
            .highlight { color: #2E86AB; font-weight: bold; }
            table { width: 100%; border-collapse: collapse; }
            td { padding: 8px 0; border-bottom: 1px solid #eee; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Tour Hello USA</h1>
                <h2>BREAKDOWN QUOTATION</h2>
            </div>
            <div class='content'>
                <p>안녕하세요, <strong>" . htmlspecialchars($recipientName) . "</strong>님.</p>
                
                <p>Tour Hello USA에서 요청하신 <span class='highlight'>" . htmlspecialchars($groupName) . "</span> 그룹의 견적서를 보내드립니다.</p>
                
                <div class='info-box'>
                    <h3 style='color: #2E86AB; margin-top: 0;'>견적서 정보</h3>
                    <table>
                        <tr><td><strong>그룹명:</strong></td><td>" . htmlspecialchars($groupName) . "</td></tr>
                        <tr><td><strong>인원:</strong></td><td>" . ($estimateInfo['pax'] ?? 0) . "명 (FOC: " . ($estimateInfo['foc'] ?? 0) . "명)</td></tr>
                        <tr><td><strong>여행기간:</strong></td><td>" . ($estimateInfo['start_date'] ?? '') . " ~ " . ($estimateInfo['end_date'] ?? '') . "</td></tr>
                        <tr><td><strong>총 금액:</strong></td><td class='highlight'>$" . number_format($estimateInfo['grand_total'] ?? 0, 2) . "</td></tr>
                        <tr><td><strong>1인당 요금:</strong></td><td class='highlight'>$" . number_format($estimateInfo['per_pax'] ?? 0, 2) . "</td></tr>
                    </table>
                </div>
                
                <p>상세한 내역은 첨부된 BREAKDOWN QUOTATION 파일을 확인해 주세요.</p>
                
                <p>문의사항이 있으시면 언제든지 연락 부탁드립니다.</p>
                
                <p>감사합니다.</p>
            </div>
            <div class='footer'>
                <p><strong>Tour Hello USA</strong></p>
                <p>이 이메일은 자동으로 발송된 메일입니다.</p>
            </div>
        </div>
    </body>
    </html>";
}

/**
 * BREAKDOWN QUOTATION 첨부파일 생성 (그룹명 포함 파일명)
 */
function createBreakdownAttachment($dbConn, $estimateId, $groupName) {
    // 안전한 파일명 생성 (그룹명 포함)
    $safeGroupName = preg_replace('/[^a-zA-Z0-9가-힣_-]/', '_', $groupName);
    $fileName = "BREAKDOWN_QUOTATION_{$safeGroupName}_" . date('Ymd') . ".xls";
    $tempPath = sys_get_temp_dir() . '/' . $fileName;
    
    // 견적서 마스터 데이터 가져오기
    $masterQuery = "SELECT * FROM estimate_master WHERE id = ?";
    $stmt = $dbConn->prepare($masterQuery);
    $stmt->bind_param("i", $estimateId);
    $stmt->execute();
    $master = $stmt->get_result()->fetch_assoc();
    
    // 견적서 아이템 데이터 가져오기
    $itemsQuery = "SELECT * FROM estimate_items WHERE estimate_id = ? ORDER BY section, id";
    $stmt = $dbConn->prepare($itemsQuery);
    $stmt->bind_param("i", $estimateId);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = [];
    while($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    
    // 엑셀 내용 생성
    $content = "\xEF\xBB\xBF"; // UTF-8 BOM
    $content .= '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;font-family:Arial,sans-serif;">';
    
    // 제목
    $content .= '<tr><td colspan="8" style="background:#2E86AB;color:white;font-size:20px;text-align:center;font-weight:bold;height:50px;">BREAKDOWN QUOTATION</td></tr>';
    
    // 기본 정보
    $content .= '<tr>';
    $content .= '<td style="background:#f5f5f5;font-weight:bold;text-align:center;">TO</td><td>' . htmlspecialchars($master['to_name'] ?? '') . '</td>';
    $content .= '<td style="background:#f5f5f5;font-weight:bold;text-align:center;">GROUP</td><td>' . htmlspecialchars($master['group_name'] ?? '') . '</td>';
    $content .= '<td style="background:#f5f5f5;font-weight:bold;text-align:center;">PAX</td><td style="text-align:center;">' . ($master['pax'] ?? 0) . '</td>';
    $content .= '<td style="background:#f5f5f5;font-weight:bold;text-align:center;">FOC</td><td style="text-align:center;">' . ($master['foc'] ?? 0) . '</td>';
    $content .= '</tr>';
    
    $content .= '<tr>';
    $content .= '<td style="background:#f5f5f5;font-weight:bold;text-align:center;">시작일</td><td>' . ($master['start_date'] ?? '') . '</td>';
    $content .= '<td style="background:#f5f5f5;font-weight:bold;text-align:center;">종료일</td><td>' . ($master['end_date'] ?? '') . '</td>';
    $content .= '<td style="background:#f5f5f5;font-weight:bold;text-align:center;">총인원</td><td style="text-align:center;">' . ($master['total_pax'] ?? 0) . '</td>';
    $content .= '<td style="background:#f5f5f5;font-weight:bold;text-align:center;">작성일</td><td>' . ($master['wdate'] ?? '') . '</td>';
    $content .= '</tr>';
    
    // 빈 행
    $content .= '<tr><td colspan="8" style="height:15px;"></td></tr>';
    
    // 섹션별 데이터 처리
    $sections = [];
    foreach ($items as $item) {
        $section = $item['section'] ?? 'ETC';
        if (!isset($sections[$section])) {
            $sections[$section] = [];
        }
        $sections[$section][] = $item;
    }
    
    $section_totals = [];
    
    // HOTEL 섹션
    if (isset($sections['HOTEL'])) {
        $content .= '<tr><td colspan="8" style="background:#A23B72;color:white;font-weight:bold;padding:10px;">1) HOTEL</td></tr>';
        $content .= '<tr style="background:#f8f9fa;font-weight:bold;">';
        $content .= '<td style="text-align:center;">지역</td><td style="text-align:center;">날짜</td><td style="text-align:center;">요일</td><td style="text-align:center;">호텔명</td>';
        $content .= '<td style="text-align:center;">방수</td><td style="text-align:center;">요금(USD)</td><td style="text-align:center;">박수</td><td style="text-align:center;">합계</td>';
        $content .= '</tr>';
        
        $hotel_total = 0;
        foreach ($sections['HOTEL'] as $item) {
            $etc = json_decode($item['etc_json'] ?? '{}', true) ?: [];
            $content .= '<tr>';
            $content .= '<td style="text-align:center;">' . htmlspecialchars($etc['region'] ?? '') . '</td>';
            $content .= '<td style="text-align:center;">' . htmlspecialchars($etc['date'] ?? '') . '</td>';
            $content .= '<td style="text-align:center;">' . htmlspecialchars($etc['weekday'] ?? '') . '</td>';
            $content .= '<td>' . htmlspecialchars($item['label'] ?? '') . '</td>';
            $content .= '<td style="text-align:center;">' . ($item['cnt'] ?? 0) . '</td>';
            $content .= '<td style="text-align:right;color:#2E86AB;font-weight:bold;">$' . number_format($item['unit'] ?? 0, 2) . '</td>';
            $content .= '<td style="text-align:center;">' . ($item['qty'] ?? 0) . '</td>';
            $content .= '<td style="text-align:right;color:#2E86AB;font-weight:bold;">$' . number_format($item['sum'] ?? 0, 2) . '</td>';
            $content .= '</tr>';
            $hotel_total += $item['sum'] ?? 0;
        }
        
        $content .= '<tr style="background:#e3f2fd;font-weight:bold;">';
        $content .= '<td colspan="7" style="text-align:right;">HOTEL 소계</td>';
        $content .= '<td style="text-align:right;color:#1976D2;">$' . number_format($hotel_total, 2) . '</td>';
        $content .= '</tr>';
        $section_totals['HOTEL'] = $hotel_total;
        
        $content .= '<tr><td colspan="8" style="height:10px;"></td></tr>';
    }
    
    // 다른 섹션들 간단 처리
    $other_sections = [
        'MEAL' => '2) MEAL', 
        'TRANSPORT' => '3) TRANSPORTATION', 
        'TICKET' => '4) 입장권', 
        'GUIDE' => '5) 가이드/기사', 
        'ETC' => '7) 기타경비'
    ];
    
    foreach ($other_sections as $key => $title) {
        if (isset($sections[$key])) {
            $total = 0;
            foreach ($sections[$key] as $item) {
                $total += $item['sum'] ?? 0;
            }
            
            $content .= '<tr><td colspan="8" style="background:#A23B72;color:white;font-weight:bold;padding:10px;">' . $title . '</td></tr>';
            $content .= '<tr style="background:#e3f2fd;font-weight:bold;">';
            $content .= '<td colspan="7" style="text-align:right;">' . $title . ' 소계</td>';
            $content .= '<td style="text-align:right;color:#1976D2;">$' . number_format($total, 2) . '</td>';
            $content .= '</tr>';
            $section_totals[$key] = $total;
            
            $content .= '<tr><td colspan="8" style="height:5px;"></td></tr>';
        }
    }
    
    // 최종 합계
    $content .= '<tr><td colspan="8" style="height:15px;"></td></tr>';
    $content .= '<tr style="background:#2E86AB;color:white;font-weight:bold;font-size:18px;">';
    $content .= '<td colspan="4" style="text-align:center;padding:15px;">10) TOTAL TOUR FEE</td>';
    $content .= '<td colspan="4" style="text-align:center;padding:15px;">$' . number_format($master['grand_total'] ?? 0, 2) . '</td>';
    $content .= '</tr>';
    
    $content .= '<tr style="background:#2E86AB;color:white;font-weight:bold;font-size:18px;">';
    $content .= '<td colspan="4" style="text-align:center;padding:15px;">11) 1인당 요금</td>';
    $content .= '<td colspan="4" style="text-align:center;padding:15px;">$' . number_format($master['per_pax'] ?? 0, 2) . '</td>';
    $content .= '</tr>';
    
    $content .= '</table>';
    
    // 파일에 저장
    file_put_contents($tempPath, $content);
    
    return $tempPath;
}

// 사용 예시
if ($_GET['action'] == 'send_email') {
    include "include/inc_base.php"; // DB 연결
    
    $userId = $_GET['user_id'] ?? '';
    $estimateId = $_GET['estimate_id'] ?? 0;
    
    if (empty($userId) || empty($estimateId)) {
        echo "<script>alert('사용자 ID와 견적서 ID가 필요합니다.'); history.back();</script>";
        exit;
    }
    
    $result = sendBreakdownEmail($dbConn, $userId, $estimateId);
    
    if ($result === true) {
        echo "<script>alert('이메일이 성공적으로 발송되었습니다.\\n수신자: " . addslashes($userId) . "'); window.close();</script>";
    } else {
        echo "<script>alert('이메일 발송 실패: " . addslashes($result) . "'); history.back();</script>";
    }
} else {
    // 간단한 테스트 폼
    echo "<!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>BREAKDOWN QUOTATION 이메일 발송</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 500px; margin: 50px auto; padding: 20px; }
            .form-group { margin-bottom: 15px; }
            label { display: block; margin-bottom: 5px; font-weight: bold; }
            input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
            button { background: #2E86AB; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        </style>
    </head>
    <body>
        <h2>BREAKDOWN QUOTATION 이메일 발송</h2>
        <form method='GET'>
            <input type='hidden' name='action' value='send_email'>
            <div class='form-group'>
                <label for='user_id'>사용자 ID:</label>
                <input type='text' id='user_id' name='user_id' required placeholder='member_list의 userid'>
            </div>
            <div class='form-group'>
                <label for='estimate_id'>견적서 ID:</label>
                <input type='number' id='estimate_id' name='estimate_id' required placeholder='estimate_master의 id'>
            </div>
            <div class='form-group'>
                <button type='submit'>이메일 발송</button>
            </div>
        </form>
    </body>
    </html>";
}
?>