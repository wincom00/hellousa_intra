<?php
// 1회용: MIS > 정산현황 > 가이드정산 MIS (division 4 / pdx 2 / sub 25) 메뉴 추가.
// 권한: 지금 'MIS > 정산현황' 메뉴를 가진 사용자에게만 준다. 나머지는 직원관리(emp_m) 메뉴권한에서 체크.
// 사용(웹): admin 로그인 상태로 열어 확인 → ?run=1 로 실행 → 서버에서 이 파일 삭제.
// 사용(CLI): php setup_guide_mis.php        (확인만)
//            php setup_guide_mis.php run    (실행)
// 여러 번 실행해도 이미 있는 행은 건너뛴다. 되돌리기: _bak_20261120/rollback.sql
$cli = (PHP_SAPI === 'cli');
if ($cli) {
	chdir(__DIR__.'/include');
	error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
	include_once 'dbconn.php';
	$run = (isset($argv[1]) && $argv[1] === 'run');
} else {
	include "include/inc_base.php";
	if (empty($user_dbinfo['userid']) || $user_dbinfo['division'] !== 'admin' || $user_info['user_pw'] !== $user_dbinfo['passwd']) {
		header('HTTP/1.1 403 Forbidden');
		exit('admin 로그인이 필요합니다.');
	}
	header('Content-Type: text/plain; charset=utf-8');
	$run = (isset($_GET['run']) && $_GET['run'] === '1');
}

$link = 'mis_guide_settle.php?division=4&pdx=2&sub=25';
$name = '가이드정산 MIS';

function gm_one($sql) {
	global $dbConn;
	$row = $dbConn->query($sql)->fetch_row();
	return $row ? $row[0] : null;
}

// 0) 충돌 확인: 4-2-25 를 다른 메뉴가 쓰고 있으면 중단
$menuName = gm_one("SELECT menu_name FROM menu_info WHERE division='4' AND parent_idx=2 AND sub_idx=25 LIMIT 1");
if ($menuName !== null && $menuName !== $name) {
	exit("중단: menu_info(4-2-25) 를 이미 '{$menuName}' 가 사용 중\n");
}

// 대상: MIS > 정산현황(4-2-0) 을 가진 사용자
$users = array();
$rst = $dbConn->query("SELECT DISTINCT u.userid FROM menu_info_user u JOIN member_list m ON m.userid = u.userid
                        WHERE u.division='4' AND u.parent_idx='2' AND u.sub_idx='0' ORDER BY u.userid");
while ($r = $rst->fetch_assoc()) $users[] = $r['userid'];

echo "메뉴 마스터(menu_info 4-2-25): ".($menuName === null ? '없음 → 추가' : '이미 있음')."\n";
echo "대상 사용자(MIS > 정산현황 보유): ".count($users)."명 — ".implode(', ', $users)."\n";
echo "현재 가이드정산 MIS 권한: ".gm_one("SELECT COUNT(*) FROM menu_info_user WHERE division='4' AND parent_idx='2' AND sub_idx='25'")."명\n";

if (!$run) {
	exit($cli ? "\n확인 후 'php setup_guide_mis.php run' 으로 실행하세요.\n" : "\n확인 후 주소 끝에 ?run=1 을 붙여 실행하세요.\n");
}

if ($menuName === null) {
	$dbConn->query("INSERT INTO menu_info (division, parent_idx, sub_idx, menu_name, menu_link, access_level, pos, menu_code)
		VALUES ('4', 2, 25, '$name', '$link', 2, 25, '2-25')");
}

$added = 0;
foreach ($users as $uid) {
	$u = $dbConn->real_escape_string($uid);
	if (gm_one("SELECT COUNT(*) FROM menu_info_user WHERE userid='$u' AND division='4' AND parent_idx='2' AND sub_idx='25'") > 0) continue;
	$dbConn->query("INSERT INTO menu_info_user (division, parent_idx, sub_idx, menu_name, menu_link, access_level, pos, menu_code, userid)
		VALUES ('4', '2', '25', '$name', '$link', 2, 25, '2-25', '$u')");
	$added++;
}

echo "\n추가된 메뉴 권한 행: {$added}\n";
echo "가이드정산 MIS 권한: ".gm_one("SELECT COUNT(*) FROM menu_info_user WHERE division='4' AND parent_idx='2' AND sub_idx='25'")."명\n";
echo "\n완료. 이 파일(setup_guide_mis.php)을 서버에서 삭제하세요.\n";
