<?php
// 가이드정산 공용 SQL 검증 — CLI 전용, 읽기만 함. 실행: php admin/tests/gs_status_check.php [시작일] [종료일]
// 1) gsStatusExpr() (MIS 보고서) 가 getGuideStatus() (목록 화면) 과 행마다 같은 상태를 내는지
// 2) gsMasterJoinSql() 이 예전 형태의 정산마스터 선택과 같은 행을 고르는지
if (PHP_SAPI !== 'cli') { header('HTTP/1.1 404 Not Found'); exit; }
chdir(__DIR__.'/../include');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
include_once 'dbconn.php';
include_once 'func_list.php';
include_once 'dong_func.php';
include_once 'guide_settle_inc.php';

$from = isset($argv[1]) ? $argv[1] : date('Y-01-01');
$to   = isset($argv[2]) ? $argv[2] : date('Y-12-31');
$w = "a.stDate BETWEEN '".$dbConn->real_escape_string($from)."' AND '".$dbConn->real_escape_string($to)."' AND a.p_code NOT LIKE 'ADD%'";
$fail = 0;

// 1) 상태 판정
$rst = $dbConn->query("SELECT a.seq_no, a.grand_eCode, a.sub_eCode, ".gsStatusExpr()." AS st FROM tour_guide a ".gsMasterJoinSql()." WHERE $w");
$n = 0;
while ($r = $rst->fetch_assoc()) {
	$n++;
	$php = strip_tags(getGuideStatus($r['grand_eCode'], $r['sub_eCode']));
	if (strpos($php, '체크나감') === 0) $php = '체크나감';
	if ($php !== $r['st']) {
		$fail++;
		if ($fail <= 10) echo "  FAIL 상태 seq_no={$r['seq_no']} 목록={$php} SQL={$r['st']}\n";
	}
}
echo "상태 판정: {$n}행 비교\n";

// 2) 정산마스터 선택
$pick = array();
foreach (array('old' => gsMasterJoinSql('a', false), 'new' => gsMasterJoinSql('a', true)) as $k => $join) {
	$rst = $dbConn->query("SELECT a.seq_no, gsm.seq_no ms FROM tour_guide a $join WHERE $w ORDER BY a.seq_no");
	$pick[$k] = array();
	while ($r = $rst->fetch_row()) $pick[$k][] = $r[0].':'.$r[1];
}
if ($pick['old'] !== $pick['new']) { $fail++; echo "  FAIL 정산마스터 선택이 다름\n"; }
echo "정산마스터 선택: ".count($pick['new'])."행 비교\n";

echo $fail ? "실패 {$fail}건\n" : "모두 OK ({$from} ~ {$to})\n";
exit($fail ? 1 : 0);
