<?php
include "include/header.php";

$qry = "SELECT 
            p_code,
            stDate as st_date,
            COUNT(*) as total_count,
            SUM(CASE WHEN is_modified = 1 THEN 1 ELSE 0 END) as modified_count,
            MAX(CASE WHEN is_modified = 1 THEN last_modified ELSE NULL END) as latest_modification
        FROM reserve_info 
        WHERE rev_status != 'CANCEL'
        AND last_modified >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        GROUP BY p_code, stDate
        HAVING modified_count > 0";

$rst = $dbConn->query($qry);
$result = array();

while ($row = $rst->fetch_assoc()) {
    $status = getEventModificationStatus($row['p_code'], $row['st_date']);
    if ($status['priority'] > 0) {
        $result[] = array(
            'p_code' => $row['p_code'],
            'st_date' => $row['st_date'],
            'modified_count' => $row['modified_count'],
            'total_count' => $row['total_count'],
            'class' => $status['class'],
            'badge' => $status['badge']
        );
    }
}

header('Content-Type: application/json');
echo json_encode($result);
?>