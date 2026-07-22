<?php

	 include("include/inc_base.php");
	 // --- Receive and Sanitize Data ---
	 // Check if required data is received via POST
	 if (isset($_POST['seq_no'], $_POST['sub_eCode'], $_POST['pcnt'], $_POST['day'], $_POST['ssub'])) {

	     $seq_no = $_POST['seq_no'];
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
	     if (empty($sub_eCode)) {
	          $errors[] = 'sub_eCode는 필수입니다.'; // "sub_eCode is required."
	     }
	     // Add more validation as needed for 'day', 'ssub', etc.


	     // If validation errors exist, send them back
	     if (!empty($errors)) {
	         header('Content-Type: application/json');
	         echo json_encode(['status' => 'error', 'message' => implode("\n", $errors)]);
	         exit();
	     }

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