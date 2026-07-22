# Guide Settle App

## 개요
- 위치: `hellousa/guide-settle-app/`
- 목적: 기존 `admin` 가이드정산 기능을 모바일 웹앱 형태로 분리
- 인증: `MEMLOGIN_ADMIN_HELLO` 쿠키 재사용
- 권한: `hasMenuAccess(6, 2, 10)` 기준

## 공통 구조
- `include/bootstrap.php`: 세션, 타임존, admin 공통 include
- `include/auth.php`: 로그인/권한/역할 헬퍼
- `include/layout.php`: 모바일 공통 레이아웃
- `assets/app.css`, `assets/app.js`: 앱 스타일/동작

## 현재 상태
- Phase 0 완료
- `list.php`는 접속 검증용 자리표시자이며, 실제 목록 구현은 Phase 1에서 진행
