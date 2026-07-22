<?php
// ============================================================
// 첨부파일 다운로드 (보안 강화판)
//   - 로그인 인증 필수
//   - 경로 조작(../) 차단: basename + realpath 경계 검증
//   - admin/upload/ 밖의 파일 접근 불가 (임의 파일 열람 방지)
// ============================================================

// 1) 인증
if (empty($_COOKIE['MEMLOGIN_ADMIN_HELLO'])) {
    header('HTTP/1.1 403 Forbidden');
    echo '로그인이 필요합니다.';
    exit;
}

// 2) 파라미터 (GET/POST 만 명시적으로 수집 — extract 제거)
$filename = isset($_GET['filename'])  ? $_GET['filename']
          : (isset($_POST['filename']) ? $_POST['filename'] : '');
$filename = (string)$filename;

// 3) 경로 조작 차단: 순수 파일명만 허용
$filename = basename($filename);
if ($filename === '' || $filename[0] === '.') {
    header('HTTP/1.1 400 Bad Request');
    echo '잘못된 요청입니다.';
    exit;
}

// 4) upload 디렉토리 경계 검증
$uploadDir = realpath(__DIR__ . '/upload');
$target    = ($uploadDir !== false) ? realpath($uploadDir . '/' . $filename) : false;
if ($uploadDir === false || $target === false ||
    strpos($target, $uploadDir . DIRECTORY_SEPARATOR) !== 0 ||
    !is_file($target)) {
    header('HTTP/1.1 404 Not Found');
    echo '파일을 찾을 수 없습니다.';
    exit;
}

// 5) 헤더 인젝션 방지용 파일명 정리 후 전송
$safeDownloadName = str_replace(array("\r", "\n", '"'), '', basename($target));

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $safeDownloadName . '"');
header('Content-Transfer-Encoding: binary');
header('Content-Length: ' . filesize($target));
header('Pragma: no-cache');
header('Expires: 0');
readfile($target);
exit;
