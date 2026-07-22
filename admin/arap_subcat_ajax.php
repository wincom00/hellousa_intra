<?php

include "include/inc_base.php";
require_once __DIR__ . "/include/arap_common.php";

header('Content-Type: application/json; charset=utf-8');

if (empty($_COOKIE['MEMLOGIN_ADMIN_HELLO'])) {
    echo json_encode(array('success' => false, 'message' => '로그인이 필요합니다.'));
    exit;
}

$catId = isset($_GET['cat_id']) ? (int)$_GET['cat_id'] : 0;
$subcategories = arapFetchSubcategories($catId);
$items = array();

foreach ($subcategories as $subcategory) {
    if ($subcategory['use_yn'] !== 'Y') {
        continue;
    }

    $items[] = array(
        'sub_id' => (int)$subcategory['sub_id'],
        'sub_name' => $subcategory['sub_name']
    );
}

echo json_encode(array('success' => true, 'items' => $items), JSON_UNESCAPED_UNICODE);
exit;

