<?php
// ============================================================
// 외부 사이트(tourhellousa.com 등)에서 base64 파일을 수신해 ./upload/ 에 저장.
// [보안 강화] 실행 가능한 스크립트 확장자/MIME 을 차단해 웹쉘 업로드를 막는다.
//   - 크로스도메인 서버 연동이므로 로그인 쿠키 인증은 사용하지 않는다.
//   - 추가 방어선: admin/upload/.htaccess 가 이 폴더의 PHP 실행 자체를 차단한다.
//   - (권장·후속) 아래 UPLOAD_SHARED_SECRET 토큰 검증을 활성화하면 더 안전하다.
//                 이 경우 연동측(tourhellousa.com)도 token 을 함께 전송하도록 수정 필요.
// ============================================================
error_reporting(E_ALL);
ini_set('display_errors', 0); // 운영 환경 정보 노출 방지
header('Content-Type: text/plain; charset=utf-8');

// ---- (선택) 공유 비밀키 검증 ----------------------------------------------
// 연동측에서 POST token 을 함께 보낼 수 있게 되면 아래 define 주석을 해제한다.
// define('UPLOAD_SHARED_SECRET', 'CHANGE-ME-긴-랜덤-문자열');
if (defined('UPLOAD_SHARED_SECRET')) {
    $token = isset($_POST['token']) ? (string)$_POST['token'] : '';
    if (!hash_equals(UPLOAD_SHARED_SECRET, $token)) {
        echo "인증 실패\n";
        exit;
    }
}

// 설정값 보조 함수 (post_max_size 비교용)
function to_bytes($v) {
    $u = strtoupper(trim($v));
    if (is_numeric($u)) return (int)$u;
    $map = array('K'=>1024, 'M'=>1048576, 'G'=>1073741824);
    $n = (float)$u;
    $s = strtoupper(substr($u, -1));
    return isset($map[$s]) ? (int)($n * $map[$s]) : (int)$n;
}

$rawLen = isset($_SERVER['CONTENT_LENGTH']) ? (int)$_SERVER['CONTENT_LENGTH'] : 0;
$pmx    = ini_get('post_max_size');
$umx    = ini_get('upload_max_filesize');

// 필수 파라미터 확인
if (!isset($_POST['fileName']) || !isset($_POST['fileData'])) {
    echo "파일이 업로드 되지 않았습니다.\n";
    echo "- CONTENT_LENGTH: {$rawLen}\n";
    echo "- post_max_size: {$pmx} (".to_bytes($pmx)." bytes)\n";
    echo "- upload_max_filesize: {$umx} (".to_bytes($umx)." bytes)\n";
    echo "- 참고: base64 전송은 원본보다 약 33% 커집니다.\n";
    exit;
}

$uploadDir = './upload/';
$fileName  = basename($_POST['fileName']); // 경로 조작(../) 차단

// ---- [보안] 확장자 화이트리스트: 실행 스크립트 차단 -----------------------
$allowedExt = array('jpg','jpeg','png','gif','webp','bmp','pdf');
$ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
if ($ext === '' || !in_array($ext, $allowedExt, true)) {
    echo "허용되지 않는 파일 형식입니다.\n";
    exit;
}
// 이중 확장자/스크립트 확장자 포함(shell.php.jpg 등) 방지
if (preg_match('/\.(php|phtml|phps|php[0-9]|phar|pht|cgi|pl|py|sh|asp|aspx|jsp|htaccess)(\.|$)/i', $fileName)) {
    echo "허용되지 않는 파일명입니다.\n";
    exit;
}

$filePath  = $uploadDir . $fileName;

if (!is_writable($uploadDir)) {
    clearstatcache();
    echo "업로드 폴더 쓰기 권한 없음\n";
    exit;
}

// base64 디코드 (strict 모드)
$data = base64_decode($_POST['fileData'], true);
if ($data === false) {
    $len = strlen($_POST['fileData']);
    echo "base64 디코딩 실패 (데이터 훼손 또는 post_max_size 초과로 잘렸을 수 있음)\n";
    echo "수신한 data 길이: {$len}\n";
    exit;
}

// ---- [보안] 실제 내용(MIME) 검증: 확장자 위장 차단 -----------------------
if (function_exists('finfo_open')) {
    $fi = @finfo_open(FILEINFO_MIME_TYPE);
    if ($fi) {
        $mime = (string)@finfo_buffer($fi, $data);
        @finfo_close($fi);
        $allowedMime = array(
            'image/jpeg','image/pjpeg','image/png','image/gif',
            'image/webp','image/bmp','image/x-ms-bmp','application/pdf'
        );
        if ($mime !== '' && !in_array($mime, $allowedMime, true)) {
            echo "파일 내용이 허용된 형식이 아닙니다.\n";
            exit;
        }
    }
}

// 저장
$result = @file_put_contents($filePath, $data);
if ($result === false) {
    echo "파일 저장 실패\n";
    exit;
}

echo "success/({$result} bytes written)";
