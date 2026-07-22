<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('America/New_York');
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);

if (!defined('GSA_APP_ROOT')) {
    define('GSA_APP_ROOT', dirname(__DIR__));
}

if (!defined('GSA_ADMIN_ROOT')) {
    define('GSA_ADMIN_ROOT', realpath(__DIR__ . '/../../admin'));
}

require_once GSA_ADMIN_ROOT . '/include/dbconn.php';
require_once GSA_ADMIN_ROOT . '/include/func_list.php';
require_once GSA_ADMIN_ROOT . '/include/dong_func.php';
require_once __DIR__ . '/auth.php';
