<?php
// CLI regression check: real save handler + in-memory DB, no connection to the application DB.
if (PHP_SAPI !== 'cli') exit;
$cases = array(
    'auto_save' => array('save', 'AUTO', 'WAIT'),
    'auto_confirm' => array('confirm', 'AUTO', 'DONE'),
    'manual_confirm' => array('confirm', 'WAIT', 'DONE'),
    'confirmed_edit' => array('save', 'DONE', 'WAIT'),
    'delete_room' => array('delete_hotel', 'DONE', 'WAIT'),
    'delete_etc' => array('delete_etc', 'DONE', 'WAIT'),
    'new_save' => array('save', null, 'WAIT'),
    'legacy_blank_etc' => array('confirm', 'AUTO', 'DONE'),
    'missing_confirm' => array('confirm', null, null, false),
    'duplicate_confirm' => array('confirm', 'DONE', 'DONE', false),
    'no_permission' => array('confirm', 'AUTO', 'AUTO', false),
    'no_cookie' => array('confirm', 'AUTO', 'AUTO', false),
    'bad_csrf' => array('confirm', 'AUTO', 'AUTO', false),
    'get_request' => array('confirm', 'AUTO', 'AUTO', false),
    'stale_summary' => array('confirm', 'AUTO', 'AUTO', false),
    'stale_version' => array('confirm', 'AUTO', 'AUTO', false),
    'foreign_room' => array('confirm', 'AUTO', 'AUTO', false),
    'invalid_amount' => array('confirm', 'AUTO', 'AUTO', false),
    'write_failure' => array('confirm', 'AUTO', 'AUTO', false),
    'sum_failure' => array('confirm', 'AUTO', 'AUTO', false)
);
if (!isset($argv[1])) {
    foreach ($cases as $name => $case) {
        $cmd = escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' '.escapeshellarg($name);
        exec($cmd, $lines, $code);
        $result = json_decode(array_pop($lines), true);
        $success = isset($case[3]) ? $case[3] : true;
        if ($code !== 0 || !$result || $result['success'] !== $success || $result['status'] !== $case[2]
            || ($success && (!$result['committed'] || $result['amount'] !== '215.50'))
            || (!$success && $result['committed'])) {
            fwrite(STDERR, "FAIL $name: ".json_encode($result, JSON_UNESCAPED_UNICODE)."\n");
            exit(1);
        }
        echo "PASS $name\n";
        $lines = array();
    }
    echo 'PASS '.count($cases)." cases (no application database used)\n";
    exit;
}

$caseName = $argv[1];
$case = $cases[$caseName];
class HotelReviewResult {
    private $row;
    function __construct($row) { $this->row = $row; }
    function fetch_assoc() { return $this->row; }
}
class HotelReviewDb {
    public $status, $amount, $committed = false, $affected_rows = 1;
    private $initial;
    function __construct($status) { $this->status = $this->initial = $status; }
    function real_escape_string($value) { return addslashes($value); }
    function begin_transaction() {}
    function commit() { $this->committed = true; }
    function rollback() { $this->status = $this->initial; $this->amount = null; }
    function query($sql) {
        global $caseName;
        if (strpos($sql, "grand_eCode='TEST' AND sub_eCode='SUB'") === false && strpos($sql, "VALUES ('TEST', 'SUB',") === false) throw new Exception('Unscoped query');
        if (strpos($sql, 'SELECT seq_no FROM tour_guide') === 0) return new HotelReviewResult(array('seq_no' => 1));
        if (strpos($sql, 'SELECT * FROM hotel_settlesum') === 0) return new HotelReviewResult($this->status === null ? null : array(
            'seq_no' => 10, 'status' => $this->status, 'base_rate' => 'USD', 'memo' => 'Existing memo'));
        if (strpos($sql, 'SELECT seq_no FROM hotel_settle') === 0) return new HotelReviewResult($caseName === 'foreign_room' ? null : array('seq_no' => 20));
        if (strpos($sql, 'AS amt') !== false) return new HotelReviewResult(array('amt' => '215.50'));
        if ($caseName === 'write_failure' && strpos($sql, 'UPDATE hotel_settle SET') === 0) throw new mysqli_sql_exception('Simulated detail failure');
        if (strpos($sql, 'UPDATE hotel_settlesum') === 0 || strpos($sql, 'INSERT INTO hotel_settlesum') === 0) {
            if ($caseName === 'sum_failure') throw new mysqli_sql_exception('Simulated summary failure');
            $this->status = strpos($sql, "'DONE'") !== false ? 'DONE' : 'WAIT';
            if (strpos($sql, "'215.50'") === false || strpos($sql, "'99999'") !== false) throw new Exception('Trusted client total');
            $this->amount = '215.50';
        }
        return true;
    }
}
function hasMenuAccess($division, $parent, $sub) {
    global $caseName;
    return $caseName !== 'no_permission' && $division === 6 && $parent === 1 && $sub === 10;
}
// 화면을 연 뒤 다른 직원이 금액을 바꾼 상황 = 현재 버전이 화면 버전과 다름 (합계 번호는 같음)
function hotelSettleVersion($g, $s) {
    if ($g !== 'TEST' || $s !== 'SUB') throw new Exception('Unscoped version');
    return 'ver-current';
}
$dbConn = new HotelReviewDb($case[1]);
$user_dbinfo = array('userid' => 'test');
$_COOKIE = $caseName === 'no_cookie' ? array() : array('MEMLOGIN_ADMIN_HELLO' => 'test');
$_SESSION = array('hotel_settle_csrf' => 'test-token');
$_SERVER['REQUEST_METHOD'] = $caseName === 'get_request' ? 'GET' : 'POST';
$_POST = array('mode' => $case[0], 'grand_eCode' => 'TEST', 'sub_eCode' => 'SUB',
    'grandcode' => 'TEST', 'subcode' => 'SUB', 'seqno' => '20',
    'hotel_sumseq' => $case[1] === null ? '' : '10', 'csrf_token' => 'test-token', 'settle_ver' => 'ver-current',
    'hotelSelect' => array('HOTEL'), 'hotel_seq' => array('20'), 'hotelRateRm' => array('100'),
    'hotelRm' => array('2'), 'hotelCost' => array('200'), 'm_rate_h' => 'USD', 'totalPayment' => '99999',
    'memo' => "Reviewer's memo", 'etcCostSelect' => array(''), 'hotelSelect_etc' => array(''),
    'etcRate' => array(''), 'etcCount' => array(''), 'etcAmount' => array(''), 'etc_seq' => array(''));
if ($caseName === 'new_save') $_POST['hotel_seq'] = array('');
if ($caseName === 'legacy_blank_etc') { $_POST['etc_seq'] = array('30'); $_POST['etcRate'] = array('0'); $_POST['etcCount'] = array('0'); $_POST['etcAmount'] = array('0.00'); }
if ($caseName === 'bad_csrf') $_POST['csrf_token'] = 'wrong';
if ($caseName === 'stale_summary') $_POST['hotel_sumseq'] = '9';
if ($caseName === 'stale_version') $_POST['settle_ver'] = 'ver-old';
if ($caseName === 'invalid_amount') $_POST['hotelCost'] = array('invalid');
ob_start();
register_shutdown_function(function () use ($dbConn) {
    $response = trim(ob_get_clean());
    echo json_encode(array('success' => $response === '1/', 'status' => $dbConn->status,
        'committed' => $dbConn->committed, 'amount' => $dbConn->amount, 'response' => $response), JSON_UNESCAPED_UNICODE)."\n";
});
// Replace only environment bootstrap; run the actual authorization, validation and mutation code.
$source = file_get_contents(__DIR__.'/../hotel_save.php');
$source = str_replace(array("if (session_status() === PHP_SESSION_NONE) session_start();", "include 'include/inc_base.php';"), '', $source);
eval(substr($source, 5));
