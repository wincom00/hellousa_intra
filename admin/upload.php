<?php
// ============================================================
// CKEditor 이미지 업로드 처리 (보안 강화판)
//   - 로그인 인증 필수 (관리자 세션 쿠키)
//   - 이미지 확장자/MIME 화이트리스트로 스크립트 업로드 차단
//   - 원본 파일명 대신 안전한 랜덤 파일명으로 저장 (webshell 방지)
//   - 응답 형식은 기존 CKEditor 연동과 호환 유지
// ============================================================
header('Content-Type: application/json; charset=utf-8');

function upload_fail($msg) {
    echo json_encode(array('uploaded' => 0, 'error' => array('message' => $msg)));
    exit;
}

// 1) 인증 확인 (프로젝트 공통 관리자 세션 쿠키)
if (empty($_COOKIE['MEMLOGIN_ADMIN_HELLO'])) {
    upload_fail('로그인이 필요합니다.');
}

// 2) 업로드 유효성
if (!isset($_FILES['upload']) ||
    $_FILES['upload']['error'] !== UPLOAD_ERR_OK ||
    !is_uploaded_file($_FILES['upload']['tmp_name'])) {
    upload_fail('업로드된 파일이 없습니다.');
}
$file = $_FILES['upload'];

// 3) 용량 제한 (10MB)
if ($file['size'] <= 0 || $file['size'] > 10 * 1024 * 1024) {
    upload_fail('파일 용량이 허용 범위를 벗어났습니다.');
}

// 4) 확장자 화이트리스트 (이미지 전용)
$allowedExt  = array('jpg','jpeg','png','gif','webp');
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExt, true)) {
    upload_fail('이미지 파일(jpg, png, gif, webp)만 업로드할 수 있습니다.');
}

// 5) 실제 내용(MIME) 검증 — 확장자 위장 방지
$mime = '';
if (function_exists('finfo_open')) {
    $fi = @finfo_open(FILEINFO_MIME_TYPE);
    if ($fi) { $mime = (string)@finfo_file($fi, $file['tmp_name']); @finfo_close($fi); }
}
$allowedMime = array('image/jpeg','image/pjpeg','image/png','image/gif','image/webp');
if ($mime !== '' && !in_array($mime, $allowedMime, true)) {
    upload_fail('유효한 이미지 파일이 아닙니다.');
}
if (@getimagesize($file['tmp_name']) === false) {
    upload_fail('유효한 이미지 파일이 아닙니다.');
}

// 6) 안전한 랜덤 파일명으로 저장
$uploadDir = __DIR__ . '/upload/';
if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0755, true); }
$safeName = date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
$target   = $uploadDir . $safeName;

if (!move_uploaded_file($file['tmp_name'], $target)) {
    upload_fail('파일 저장에 실패했습니다.');
}
@chmod($target, 0644);

// 7) CKEditor 호환 응답
$url = 'https://www.myhello.info/admin/upload/' . $safeName;
echo json_encode(array(
    'fileName' => $safeName,
    'uploaded' => 1,
    'url'      => $url,
    'width'    => 'auto',
));
exit;
