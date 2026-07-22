<?php
// get_product_options_ajax.php

// 개발 중 오류 확인을 위해 에러 리포팅 활성화 (실제 서비스 시에는 비활성화 또는 로그 파일 기록)
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

header('Content-Type: text/html; charset=utf-8'); // 응답 타입 설정

include("include/inc_base.php");

// 2. AJAX 요청으로부터 데이터 받기 (POST 방식 가정)where a.opt_code=b.opt_code 
$code= $_GET['code'];
$pcode = $_GET['pcode'];
$qry1 = "SELECT a.opt_name,a.opt_price,b.* FROM base_opt a, product_opt b WHERE  a.opt_code=b.opt_code &&.b.p_code='$pcode' && a.opt_m = 'M' ORDER BY opt_name ASC
";

$rst1 = mysql_query($qry1,$dbConn);
if ($rst1 === false) {
echo "데이터베이스 쿼리 실행 중 오류가 발생했습니다.<br>";

    // 실제 오류 원인 확인 (개발 중에만 화면에 출력, 실제 운영 환경에서는 로그 파일에 기록)
    echo "MySQL 오류: " . mysql_error(); // 어떤 오류인지 출력
}
$option="";		
while($row1 = mysql_fetch_assoc($rst1)){

	
	$selectValueInput = $row1['opt_code'];
	
	// 이제 아래 코드가 실행됩니다.
	if (is_array($code) && in_array($selectValueInput, $code)) { // is_array 체크 추가 권장
		$option.= "<option value='" . htmlspecialchars($row1['opt_code'], ENT_QUOTES, 'UTF-8') . "' selected>" . htmlspecialchars($row1['opt_name'], ENT_QUOTES, 'UTF-8') . "</option>" ; // 보안 및 태그 수정
	} else {
		$option.= "<option value='" . htmlspecialchars($row1['opt_code'], ENT_QUOTES, 'UTF-8') . "' >" . htmlspecialchars($row1['opt_name'], ENT_QUOTES, 'UTF-8') . "</option>" ; // 보안 및 태그 수정
	}
		

}


// 5. 결과 출력 (이것이 AJAX 응답 본문이 됨)
echo $option;



exit; // 스크립트 실행 완료
?>