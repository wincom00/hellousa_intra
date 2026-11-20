<?php
// 1회용: 호텔 기간별 요금 테이블(hotel_rate) 생성. 요금 입력은 상품관리 > 호텔등록 > 수정 화면(hotel_regi_m.php) 아래쪽.
// 사용: admin 로그인 상태로 열어 확인 → ?run=1 로 실행 → 서버에서 이 파일 삭제.
// 여러 번 실행해도 이미 있으면 건너뛴다.
include "include/inc_base.php";

if (empty($user_dbinfo['userid']) || $user_dbinfo['division'] !== 'admin' || $user_info['user_pw'] !== $user_dbinfo['passwd']) {
	header('HTTP/1.1 403 Forbidden');
	exit('admin 로그인이 필요합니다.');
}
header('Content-Type: text/plain; charset=utf-8');

$rst = $dbConn->query("SHOW TABLES LIKE 'hotel_rate'");
$hasTable = $rst && $rst->num_rows > 0;
echo "hotel_rate 테이블: " . ($hasTable ? "있음" : "없음 → 생성") . "\n";

if (!isset($_GET['run']) || $_GET['run'] !== '1') {
	exit("\n확인 후 주소 끝에 ?run=1 을 붙여 실행하세요.");
}

if (!$hasTable) {
	$ok = $dbConn->query("CREATE TABLE IF NOT EXISTS hotel_rate (
		seq_no int NOT NULL AUTO_INCREMENT PRIMARY KEY,
		h_code varchar(50) NOT NULL,
		item_code varchar(10) NOT NULL DEFAULT 'ROOM',
		season_name varchar(50) DEFAULT NULL,
		start_date date NOT NULL,
		end_date date NOT NULL,
		rate decimal(12,2) NOT NULL,
		memo varchar(200) DEFAULT NULL,
		userid varchar(20) DEFAULT NULL,
		wdate datetime DEFAULT NULL,
		KEY idx_hotel_rate (h_code, item_code, start_date, end_date)
	) DEFAULT CHARSET=utf8mb3");
	if (!$ok) exit("테이블 생성 실패: " . $dbConn->error);
	echo "생성 완료\n";
}

echo "\n완료. 이 파일(setup_hotel_rate.php)을 서버에서 삭제하세요.";
