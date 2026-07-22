<?php
include "include/header.php";

$qry = "SELECT reserveCode, is_modified, last_modified 
        FROM reserve_info 
        WHERE is_modified = 1 
        AND last_modified >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";

$rst = $dbConn->query($qry);
$result = array();

while ($row = $rst->fetch_assoc()) {
    $status = getModificationStatus($row['last_modified'], $row['is_modified']);
    $result[] = array(
        'reserveCode' => $row['reserveCode'],
        'isModified' => $row['is_modified'],
        'class' => $status['class'],
        'badge' => $status['badge']
    );
}

header('Content-Type: application/json');
echo json_encode($result);
?>