<?php
    include("include/inc_base.php");
    
	header("Content-Type: application/json");
  
    // POST 요청으로 seq_no 받기
    if (isset($_POST['seq_no']) && !empty($_POST['seq_no'])) {
        $seqNo = $dbConn->real_escape_string($_POST['seq_no']); // SQL Injection 방지

        // 현재 날짜 구하기 (YYYY-MM-DD 형식)
        $currentDate = date("Y-m-d");

        // 데이터베이스 업데이트 쿼리
        $updateQuery = "UPDATE tour_guide SET check_out = 'V', check_date = '$currentDate' WHERE seq_no = '$seqNo'";

        $response = array();

        if ($dbConn->query($updateQuery)) {
            // 업데이트 성공
            $response['status'] = 'success';
            $response['check_date'] = $currentDate; // 업데이트된 날짜 반환
        } else {
            // 업데이트 실패
            $response['status'] = 'error';
            $response['message'] = 'Database update failed: ' . $dbConn->error;
        }

        // JSON 형식으로 응답 출력
        header('Content-Type: application/json');
        echo json_encode($response);

    } else {
        // seq_no가 전달되지 않음
        $response['status'] = 'error';
        $response['message'] = 'Invalid request: seq_no not provided.';
        header('Content-Type: application/json');
        echo json_encode($response);
    }

    // 데이터베이스 연결 종료 (필요하다면)
    $dbConn->close();
?>