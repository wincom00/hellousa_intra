<?php
// 1회용: 상단 메인메뉴 '게시판관리' + 게시판관리 > 일반게시판 > 가이드게시판(table_id 75)을 메뉴권한 있는 전 사용자에게 추가.
// 사용: admin 로그인 상태로 열어 확인 → ?run=1 로 실행 → 서버에서 이 파일 삭제.
// 여러 번 실행해도 이미 있는 행은 건너뛴다.
include "include/inc_base.php";

if (empty($user_dbinfo['userid']) || $user_dbinfo['division'] !== 'admin' || $user_info['user_pw'] !== $user_dbinfo['passwd']) {
	header('HTTP/1.1 403 Forbidden');
	exit('admin 로그인이 필요합니다.');
}
header('Content-Type: text/plain; charset=utf-8');

$link = 'board_list.php?division=8&pdx=1&sub=40&table_id=75';

function gb_one($sql) {
	global $dbConn;
	$row = $dbConn->query($sql)->fetch_row();
	return $row ? $row[0] : null;
}

// 0) 충돌 확인: table_id 75 / 일반게시판 sub 40 을 다른 게시판이 쓰고 있으면 중단
$setupName = gb_one("SELECT board_name FROM hello_board_setup WHERE table_id='75' LIMIT 1");
$menuName  = gb_one("SELECT menu_name FROM menu_info WHERE division='8' AND parent_idx=1 AND sub_idx=40 LIMIT 1");
if (($setupName !== null && $setupName !== '가이드게시판') || ($menuName !== null && $menuName !== '가이드게시판')) {
	exit("중단: 이미 사용 중 — hello_board_setup(75)={$setupName}, menu_info(8-1-40)={$menuName}");
}

$users = array();
$rst = $dbConn->query("SELECT DISTINCT u.userid FROM menu_info_user u JOIN member_list m ON m.userid = u.userid ORDER BY u.userid");
while ($r = $rst->fetch_assoc()) $users[] = $r['userid'];

echo "대상 사용자: " . count($users) . "명\n";
echo "현재 가이드게시판 권한: " . gb_one("SELECT COUNT(*) FROM menu_info_user WHERE division='8' AND parent_idx='1' AND sub_idx='40'") . "명\n";

if (!isset($_GET['run']) || $_GET['run'] !== '1') {
	exit("\n확인 후 주소 끝에 ?run=1 을 붙여 실행하세요.");
}

if ($setupName === null) {
	$dbConn->query("INSERT INTO hello_board_setup (table_id, board_name) VALUES ('75', '가이드게시판')");
}
if ($menuName === null) {
	$dbConn->query("INSERT INTO menu_info (division, parent_idx, sub_idx, menu_name, menu_link, access_level, pos, menu_code)
		VALUES ('8', 1, 40, '가이드게시판', '$link', 1, 40, '1-40')");
}

// [존재확인 조건, division, parent_idx, sub_idx, menu_name, menu_link, access_level, pos, menu_code]
$rows = array(
	array("division='m' AND menu_name='게시판관리'",              'm', '0', '0',  '게시판관리',   $link, 0, 0,  '0'),
	array("division='8' AND parent_idx='1' AND sub_idx='0'",  '8', '1', '0',  '일반게시판',   '',    1, 0,  '1-0'),
	array("division='8' AND parent_idx='1' AND sub_idx='40'", '8', '1', '40', '가이드게시판', $link, 1, 40, '1-40'),
);

$added = 0;
$lvUpd = 0;
foreach ($users as $uid) {
	$u = $dbConn->real_escape_string($uid);
	// 상단 메뉴 체크(access_level의 8=게시판관리)도 켜야 직원/가이드 수정 화면에서 저장해도 유지된다
	$dbConn->query("UPDATE member_list SET access_level = CONCAT(IFNULL(access_level, ''), '8/')
		WHERE userid='$u' AND division IN ('admin', 'guide') AND (access_level IS NULL OR access_level NOT LIKE '%8%')");
	$lvUpd += $dbConn->affected_rows;
	foreach ($rows as $r) {
		if (gb_one("SELECT COUNT(*) FROM menu_info_user WHERE userid='$u' AND {$r[0]}") > 0) continue;
		$dbConn->query("INSERT INTO menu_info_user (division, parent_idx, sub_idx, menu_name, menu_link, access_level, pos, menu_code, userid)
			VALUES ('{$r[1]}', '{$r[2]}', '{$r[3]}', '{$r[4]}', '{$r[5]}', {$r[6]}, {$r[7]}, '{$r[8]}', '$u')");
		$added++;
	}
}

echo "\n추가된 메뉴 행: {$added}\n";
echo "가이드게시판 권한: " . gb_one("SELECT COUNT(*) FROM menu_info_user WHERE division='8' AND parent_idx='1' AND sub_idx='40'") . "명\n";
echo "\n완료. 이 파일(setup_guide_board.php)을 서버에서 삭제하세요.";
