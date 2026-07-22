<?php
	
   /// @mkdir(__DIR__.'/../../logs', 0777, true);
	ini_set('display_errors', 1);
	ini_set('log_errors', 0);
	//ini_set('error_log', __DIR__.'/../../logs/php_error.log');
	error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
	   
	
	#define('_BASE_DIR', mb_ereg_replace("/include$", "", dirname(__FILE__)));          // 위치한 절대경로
	#define('_WEB_BASE_DIR', mb_ereg_replace(mb_ereg_replace("/$", "", $HTTP_SERVER_VARS[DOCUMENT_ROOT]), '', _BASE_DIR));
	date_default_timezone_set('America/NEW_YORK');
    $WEB_BASE_PATH = "https://www.myhello.info";
	define('_BASE_DIR', '/var/www/html/'); 
	
	if (is_array($_GET)) extract($_GET);
	if (is_array($_POST)) extract($_POST);
	if (is_array($_SERVER)) extract($_SERVER);
	if (is_array($_COOKIE)) extract($_COOKIE);
	if (is_array($_FILES)) extract($_FILES); 

    
	include_once  "dbconn.php";
	include_once  "c_misc_inc.php";
	include_once  "func_list.php";
	include_once  "dong_func.php";
	include_once  _BASE_DIR."admin/libs/PHPMailer/class.phpmailer.php";
	include_once  _BASE_DIR."ses.php";

	$pre_doamin =  $HTTP_HOST; 
	$pre_doamin = str_replace("www.","",$pre_doamin); 
	$pre_doamin = preg_replace('/:\d+$/', '', $pre_doamin); // 포트 제거
	// 로컬(localhost / IP 주소)에서는 '.도메인' 형태 쿠키를 브라우저가 저장하지 않아
	// 로그인 쿠키가 유실되고 change_pass.php -> login.php 루프가 발생한다. 이 경우 호스트 전용 쿠키로 처리한다.
	if ($pre_doamin === '' || $pre_doamin === 'localhost'
		|| preg_match('/^\d{1,3}(\.\d{1,3}){3}$/', $pre_doamin)   // IPv4
		|| strpos($pre_doamin, ':') !== false) {                  // IPv6
		$c_domain = '';
	} else {
		$c_domain = '.'.$pre_doamin;
	}

	$G_Current_Url = $_SERVER["SCRIPT_URI"];
	$G_Current_Page = $_SERVER["REQUEST_URI"];	

	$pageNameValue = explode("?",$G_Current_Page);
    

	@extract($_GET);
	@extract($_POST);
	@extract($_SERVER);
	@extract($_COOKIE);
	//print_r($_COOKIE);
	//exit;
	//아이디가 있다면 회원정보 분석해라
	if( $_COOKIE['MEMLOGIN_ADMIN_HELLO'])
	{
		$user_info = getinfo_Member($_COOKIE['MEMLOGIN_ADMIN_HELLO']);
		$user_dbinfo = getinfo_dbMember($user_info['user_id']);
        
		
	} 
	
  
    
	
?>

