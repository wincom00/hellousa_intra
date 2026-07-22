<?php
// get_po2.php - Fetches product options based on product code

// --- Configuration & Setup ---

// Enable error reporting for development (disable or log to file in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set the content type and character set for the response
header('Content-Type: text/html; charset=utf-8');

// Include base file (presumably sets up $dbConn using mysqli)
// IMPORTANT: Ensure inc_base.php establishes a mysqli connection, e.g.:
// $dbHost = 'localhost';
// $dbUser = 'your_db_user';
// $dbPass = 'your_db_password';
// $dbName = 'your_db_name';
// $dbConn = mysqli_connect($dbHost, $dbUser, $dbPass, $dbName);
// if (!$dbConn) {
//     // Log error, don't echo details in production
//     error_log("Database connection failed: " . mysqli_connect_error());
//     http_response_code(500); // Internal Server Error
//     die("데이터베이스 연결 실패"); // Generic message for user
// }
// mysqli_set_charset($dbConn, 'utf8mb4'); // Use utf8mb4 for broader character support
include("include/inc_base.php");

// --- Input Validation ---

// Check if the required product code is provided
if (!isset($_GET['pcode']) || empty(trim($_GET['pcode']))) {
    // Optionally log this attempt
    // error_log("get_po2.php called without pcode parameter.");
    http_response_code(400); // Bad Request
    echo "<option value=''>제품 코드를 지정해주세요.</option>"; // Informative message
    exit;
}
$pcode = trim($_GET['pcode']); // Trim whitespace

// Get the codes to pre-select (if any). Ensure it's an array.
$selectedCodes = []; // Default to an empty array
if (isset($_GET['code'])) {
    // Ensure 'code' is treated as an array, even if only one value is passed
    $selectedCodes = (array)$_GET['code'];
}

// --- Database Interaction ---

// Check if the database connection object exists and is valid
if (!$dbConn || !($dbConn instanceof mysqli)) {
     error_log("get_op2.php: Invalid or missing mysqli database connection.");
     http_response_code(500);
     echo "<option value=''>서버 오류가 발생했습니다. (DB Connection)</option>";
     exit;
}

// Set character set for the connection (redundant if set in inc_base.php, but safe)
if (!mysqli_set_charset($dbConn, 'utf8mb4')) {
     error_log("Error loading character set utf8mb4: " . mysqli_error($dbConn));
     // Continue execution, but be aware of potential encoding issues
}


// Prepare the SQL query using placeholders (?) to prevent SQL injection
// Corrected SQL syntax (using AND, removed extra '.')
$sql = "SELECT a.opt_code, a.opt_name, a.opt_price
        FROM base_opt a
        JOIN product_opt b ON a.opt_code = b.opt_code
        WHERE b.p_code = ? AND a.opt_m = 'M'
        ORDER BY a.opt_name ASC";

$stmt = mysqli_prepare($dbConn, $sql);

if (!$stmt) {
    // Log the detailed error
    error_log("SQL prepare failed: " . mysqli_error($dbConn));
    http_response_code(500);
    echo "<option value=''>데이터 조회 준비 중 오류가 발생했습니다.</option>";
    mysqli_close($dbConn); // Close connection if it was opened
    exit;
}

// Bind the parameter ($pcode) to the placeholder
// 's' indicates the parameter is a string
mysqli_stmt_bind_param($stmt, 's', $pcode);

// Execute the prepared statement
if (!mysqli_stmt_execute($stmt)) {
    // Log the detailed error
    error_log("SQL execute failed: " . mysqli_stmt_error($stmt));
    http_response_code(500);
    echo "<option value=''>데이터 조회 실행 중 오류가 발생했습니다.</option>";
    mysqli_stmt_close($stmt);
    mysqli_close($dbConn);
    exit;
}

// Get the result set from the prepared statement
$result = mysqli_stmt_get_result($stmt);

if ($result === false) {
    // Log the detailed error
    error_log("Failed to get result set: " . mysqli_stmt_error($stmt));
    http_response_code(500);
    echo "<option value=''>데이터 결과 가져오기 중 오류가 발생했습니다.</option>";
    mysqli_stmt_close($stmt);
    mysqli_close($dbConn);
    exit;
}

// --- Generate Output ---

$optionsHtml = "";

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $optionValue = htmlspecialchars($row['opt_code'], ENT_QUOTES, 'UTF-8');
        $optionText = htmlspecialchars($row['opt_name'], ENT_QUOTES, 'UTF-8');
        // Optionally include price: $optionText .= " (+".number_format($row['opt_price'])."원)";

        // Check if this option's code is in the array of codes to be selected
        $isSelected = in_array($row['opt_code'], $selectedCodes);
        $selectedAttribute = $isSelected ? " selected" : "";

        $optionsHtml .= "<option value='" . $optionValue . "'" . $selectedAttribute . ">" . $optionText . "</option>";
    }
} else {
    // No options found for this product code
    $optionsHtml = "<option value=''>선택 가능한 옵션이 없습니다.</option>";
}

// --- Cleanup and Output ---

// Free the result set
mysqli_free_result($result);

// Close the statement
mysqli_stmt_close($stmt);

// Close the database connection (important!)
mysqli_close($dbConn);

// Echo the generated HTML options
echo $optionsHtml;

// No exit needed here as script naturally ends

?>
