<?php
	 if (session_status() === PHP_SESSION_NONE) session_start();
	 include("include/inc_base.php");
	 function update_cnt_fail($msg) {
	     header('Content-Type: application/json');
	     echo json_encode(['status' => 'error', 'message' => $msg]);
	     exit();
	 }
	 // 로그인 + 배정관리 권한(5,2,10 고정 — 요청의 division/pdx/sub 는 쓰지 않음) + 호텔배정 화면이 발급한 토큰
	 if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_COOKIE['MEMLOGIN_ADMIN_HELLO']) || empty($user_dbinfo['userid']) || !hasMenuAccess(5, 2, 10)) {
	     update_cnt_fail('호텔배정 권한이 없습니다. 다시 로그인하세요.');
	 }
	 if (empty($_SESSION['hotel_assign_csrf']) || !isset($_POST['csrf_token']) || !is_string($_POST['csrf_token'])
	     || !hash_equals($_SESSION['hotel_assign_csrf'], $_POST['csrf_token'])) {
	     update_cnt_fail('화면을 새로고침한 뒤 다시 시도하세요.');
	 }
	 session_write_close();
	 // --- Receive and Sanitize Data ---
	 // Check if required data is received via POST
	 if (isset($_POST['seq_no'], $_POST['grand_eCode'], $_POST['sub_eCode'], $_POST['pcnt'], $_POST['day'], $_POST['ssub'])) {

	     $seq_no = $_POST['seq_no'];
	     $grand_eCode = trim($_POST['grand_eCode']);
	     $sub_eCode = trim($_POST['sub_eCode']); // Trim whitespace
	     $pcnt = $_POST['pcnt'];
	     $day = trim($_POST['day']); // Trim whitespace
	     $ssub = trim($_POST['ssub']); // Trim whitespace - Assuming this maps to a column

	     // --- Server-Side Validation ---
	     $errors = [];
	     if (!filter_var($seq_no, FILTER_VALIDATE_INT)) {
	         $errors[] = '유효하지 않은 seq_no 입니다.'; // "Invalid seq_no."
	     }
	     if (!filter_var($pcnt, FILTER_VALIDATE_INT) || $pcnt < 1) {
	         $errors[] = '수량(pcnt)은 1 이상의 정수여야 합니다.'; // "Quantity (pcnt) must be an integer >= 1."
	     }
	     if (empty($grand_eCode) || empty($sub_eCode)) {
	          $errors[] = '행사코드(grand_eCode, sub_eCode)는 필수입니다.';
	     }
	     // Add more validation as needed for 'day', 'ssub', etc.


	     // If validation errors exist, send them back
	     if (!empty($errors)) {
	         header('Content-Type: application/json');
	         echo json_encode(['status' => 'error', 'message' => implode("\n", $errors)]);
	         exit();
	     }

	     // 배정이 화면의 행사에 속하는지 확인 — 다른 행사의 배정 번호로는 수량·정산을 바꿀 수 없다
	     $hr_row = $dbConn->query("SELECT grand_eCode, sub_eCode FROM hotel_assign WHERE seq_no=".(int)$seq_no
	         ." AND grand_eCode='".$dbConn->real_escape_string($grand_eCode)."' AND sub_eCode='".$dbConn->real_escape_string($sub_eCode)."'")->fetch_assoc();
	     if (!$hr_row) update_cnt_fail('이 행사의 호텔배정이 아닙니다. 새로고침 후 다시 시도하세요.');

	     // --- Prepare and Execute Update Statement ---
	     // IMPORTANT: Replace 'your_table_name' and column names with your actual table and column names.
	     // Make sure the column types match the data types being bound (i, s, d).
	     $sql = "UPDATE hotel_assign
	             SET  pcnt = ?
	             WHERE seq_no = ?";

	     // Using MySQLi
	     $stmt = $dbConn->prepare($sql);
	     if ($stmt === false) {
	          header('Content-Type: application/json');
	          echo json_encode(['status' => 'error', 'message' => 'SQL prepare failed: ' . $dbConn->error]);
	          exit();
	     }
	     // Bind parameters: s=string, i=integer, d=double/decimal
	     // Adjust the 'ssssi' types based on your actual column types
	     $stmt->bind_param("ii", $pcnt,$seq_no);

	     


	     // --- Execute and Check Result ---
	     $response = [];
	     $success = false;

	     // Using MySQLi
	     if ($stmt->execute()) {
	         if ($stmt->affected_rows > 0) {
	             $response = ['status' => 'success', 'message' => '성공적으로 업데이트되었습니다. (Seq: ' . $sub_eCode . ')']; // "Successfully updated."
	             $success = true;
	         } else {
	             // Query executed, but no rows were changed (maybe the data was the same)
	              $response = ['status' => 'success', 'message' => '데이터 변경사항이 없거나 해당 레코드를 찾을 수 없습니다. (Seq: ' . $sub_eCode . ')']; // "No data changes or record not found."
	              $success = true; // Still considered a success in terms of execution
	         }
	     } else {
	         $response = ['status' => 'error', 'message' => '업데이트 실행 오류: ' . $stmt->error]; // "Update execution error:"
	     }
	     $stmt->close(); // Close statement for MySQLi

	     // 방갯수 변경 → 호텔정산 자동 반영 (호텔등록 기간별 요금, 담당자 저장분은 제외)
	     if ($success) {
	         $hr_sync = syncHotelSettleFromAssign($hr_row['grand_eCode'], $hr_row['sub_eCode']);
	         if ($hr_sync != '') $response['message'] .= "\n".$hr_sync;
	     }

	   

	 } else {
	     // Required data not received
	     $response = ['status' => 'error', 'message' => '필수 데이터가 전송되지 않았습니다.']; // "Required data was not sent."
	 }

	 // --- Close Connection ---
	 // Using MySQLi
	 $dbConn->close();

	 /*
	 // Using PDO
	 $dbConn = null; // Closes the PDO connection
	 */


	 // --- Send JSON Response Back to JavaScript ---
	 header('Content-Type: application/json');
	 echo json_encode($response);
	 exit(); // Ensure no further output

	 ?>