<?php
include "include/header.php";

if ($_GET['p_code'] && $_GET['st_date']) {
    $pCode = $_GET['p_code'];
    $stDate = $_GET['st_date'];
    
    $details = getEventModificationDetails($pCode, $stDate);
    
    header('Content-Type: application/json');
    echo json_encode($details);
} else {
    header('Content-Type: application/json');
    echo json_encode([]);
}
?>