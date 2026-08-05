# 헬로USA 인트라넷 (HelloUSA Intranet ERP) - Codex 가이드

## 프로젝트 개요
헬로USA 여행사의 인트라넷 ERP 시스템. 투어 예약, 견적, 결제, 가이드·호텔·버스·차량 배정, 가이드/호텔 정산, 인보이스 발송, AR/AP 회계, 스케줄 관리 등을 통합 관리한다.

- **운영 도메인**: `https://www.myhello.info` (실서버), 로컬 개발은 Laragon
- **작업 디렉토리**: `d:\www\hellousa_intra\`
  - `admin/` — 메인 ERP (데스크톱, PHP 256개 파일)
  - `guide-settle-app/` — 가이드 정산 모바일 웹앱 (admin에서 분리된 서브앱)
- **실서버 경로**: `_BASE_DIR = '/var/www/html/'` (production)
- **타임존**: `America/New_York` (전역 고정)

> 참고: 상위 폴더 `d:\www\AGENTS.md`는 본 문서(`hellousa_intra`)와 혼동하지 말 것. 두 시스템은 코드 계보를 공유하지만 DB·도메인·인증 쿠키가 다르다.

---

## 기술 스택

| 영역 | 기술 |
|------|------|
| Backend | PHP (mysqli **객체지향** 방식 — `$dbConn->query()`, `->fetch_assoc()`) |
| Database | MySQL 8.x (AWS RDS) |
| Frontend | Bootstrap 3, jQuery 3.3.1 (+ jquery-migrate), jQuery UI 1.10, DataTables 1.10.18 |
| 날짜/시간 | bootstrap-datepicker 1.3.0, bootstrap-timepicker, bootstrap-clockpicker (모두 CDN) |
| 에디터 | TinyMCE (`js/tinymce/`) — ckeditor 디렉토리는 존재하나 현재 미사용 |
| 차트/캘린더 | Flot, FullCalendar, peity |
| 셀렉트 UI | Chosen 1.8.7 |
| PDF/엑셀 | pdfmake 0.1.36, SimpleXLSX / SimpleXLSXGen / SimpleExcelReader |
| 이메일 | PHPMailer (`admin/libs/PHPMailer/class.phpmailer.php`), `ses.php` (AWS SES) |
| 환율 | FRED API (`include/get_rate.php`) |

> paran_erp와 달리 이 프로젝트는 레거시 `mysql_*` 함수가 아니라 **mysqli 객체(`$dbConn`)** 를 직접 사용한다. 새 쿼리 작성 시 이 점을 반드시 지킬 것.

---

## 데이터베이스 연결

### 메인 DB (`include/dbconn.php` → `$dbConn`)
- **호스트**: `database-1.c6dioccwsg78.us-east-1.rds.amazonaws.com:3306` (AWS RDS)
- **DB명**: `dbs13437728` / 사용자: `admin` / charset: `utf8`
- **연결 특성**: 영구 연결(`p:` 접두사) + `MYSQLI_CLIENT_COMPRESS` + 연결 타임아웃 10초
- 전역 객체 `$dbConn`(mysqli)로 사용: `$rst = $dbConn->query($qry); $row = $rst->fetch_assoc();`

### 보조 DB (`include/db-con2.php` → `$db`)
- **호스트**: `74.208.228.155` / DB명: `parandb` / 사용자: `wincom00`
- `dbclass.php`의 `db` 클래스(prepared statement) 사용 — 재시도·타임아웃·백오프 내장
- 사용 예: `$db->query("... ?", $val)->fetchArray()` / `->fetchAll()`
- 이 파일은 필요한 화면에서만 명시적으로 include (전역 로드 아님)

### `dbclass.php`의 `db` 클래스 주요 메서드
- `query($sql, ...$params)` — prepared statement 실행 (타입 자동 판별)
- `fetchArray()` — 단일 행 / `fetchAll($callback)` — 전체 행
- `numRows()`, `affectedRows()`, `lastInsertID()`

---

## 인증 / 권한

### 로그인 세션
- 쿠키 **`MEMLOGIN_ADMIN_HELLO`** 기반 (base64 + serialize로 인코딩된 user_info)
- `getinfo_Member($cookie)` — 쿠키를 디코딩해 로그인 사용자 정보 반환
- `getinfo_dbMember($userid)` — `member_list` 테이블에서 DB 사용자 정보 조회
- `inc_base.php`에서 자동으로 쿠키를 읽어 `$user_info`, `$user_dbinfo` 전역 설정
- 로그인 처리: `login.php` (`Member_login()`), 모바일+가이드는 `guide-settle-app/my.php`로 리다이렉트

### 메뉴 권한 (`menu_info_user` 테이블)
- `hasMenuAccess($division, $par_idx, $sub_idx)` — division/부모/서브 메뉴 인덱스로 접근 권한 확인
- `hasMenuAccess2($filename)` — 파일명(menu_link)으로 권한 확인
- 사이드바 메뉴: `include/side_m.php`의 `printLeftMenu()` / `printLeftMenu_b()`가 division·권한에 따라 렌더

---

## 핵심 파일 구조

```
admin/
├── include/
│   ├── inc_base.php          # 부트스트랩: 타임존·에러설정·전역 extract·인증·include 로딩
│   ├── dbconn.php            # 메인 DB ($dbConn, AWS RDS / dbs13437728)
│   ├── dbclass.php           # db 클래스 (prepared statement)
│   ├── db-con2.php           # 보조 DB ($db, parandb)
│   ├── func_list.php  (79KB) # 공통 함수 라이브러리 (인증·예약·메일 등)
│   ├── dong_func.php  (86KB) # 도메인 함수 라이브러리 (상품·가이드·호텔·결제 등)
│   ├── c_misc_inc.php        # Misc 클래스 (jvAlert 등 UI 헬퍼)
│   ├── header.php / header_n.php / header_sc.php  # 페이지 레이아웃 + 에셋 로드
│   ├── side_m.php            # 좌측 네비게이션 메뉴
│   ├── arap_common.php       # AR/AP 공통 헬퍼
│   ├── get_rate.php          # 환율 조회 (FRED API)
│   ├── functions_estimate.php# 견적 관련 함수
│   ├── inc_arap_save.php / inc_arap_inline_save.php  # AR/AP 저장 핸들러
│   └── inc_base.php          # (상동)
├── js/
│   ├── dongbu.js             # 메인 스크립트 (pt 객체 정의)
│   ├── paran.js              # pt 객체 정의 (datepicker/datatable 기본값)
│   └── tinymce/              # TinyMCE 에디터
├── libs/PHPMailer/           # 이메일 발송
├── base_reservation_m.php  (228KB)  # 예약 수정 — 가장 큰 파일
├── base_product_m.php       (92KB)  # 상품(투어) 마스터 수정
├── invoice_page.php / invoice_p.php # 인보이스 (일반 / 패키지)
└── ...

guide-settle-app/            # 가이드 정산 모바일 웹앱
├── include/
│   ├── bootstrap.php         # 세션·타임존, admin의 dbconn/func_list/dong_func 재사용
│   ├── auth.php              # gsa_require_login / gsa_user_role / gsa_can
│   └── layout.php            # 모바일 공통 레이아웃 (gsa_layout_head/foot)
├── assets/app.css, app.js
├── index.php                # 역할별 진입 (guide→my.php, staff→list.php)
├── list.php / my.php / form.php / save.php / check.php / check_save.php
├── assignments.php / assignment_customers.php / guide_settle.php / memo.php
└── schedule.php             # ../admin/sc_local.php 를 iframe으로 임베드
```

---

## 주요 모듈 (파일 접두사 기준)

| 모듈 | 대표 파일 |
|------|-----------|
| 예약 | `base_reservation_m.php`(수정, 최대), `base_reservation.php`, `event_reservation_*`, `total_reservation_*` |
| 상품/기준정보 | `base_product_m.php`, `base_agent*`, `base_bus*`, `base_code*`, `base_guide*`, `base_opt*`, `base_pick*`, `base_conslut*` |
| 견적 | `estimate_form.php`, `estimate_list.php`, `estimate_save.php`, `estimate_view.php`, `estimate_export_breakdown*.php`, `estimate_excel.php` |
| 배정 | `assign_m.php`, `car_assign_m.php`, `guide_assign_m.php`, `hotel_assign_m.php` |
| 가이드 정산 | `guide_cal_m.php`, `guide_settle.php`, `guide_mysettle.php`, `guide_save.php`, `guide_block.php`, `guide_setcheck.php` + `guide-settle-app/` |
| 호텔 | `hotel_regi*.php`, `hotel_assign_m.php`, `hotel_settle.php`, `hotel_stat*.php`, `hotel_customer.php`, `hotel_cal2.php` |
| 인보이스 | `invoice_page.php`(일반), `invoice_p.php`(패키지), `invoice_m.php`, `invoice_cc.php`, `invoice_out.php` |
| AR/AP 회계 | `arap_list.php`, `arap_master.php`, `arap_summary.php`, `arap_chart.php`, `arap_target_cards.php`, `arap_export.php` |
| 결제 | `pay_hist.php`, `getPayment*()` 함수 계열 |
| 스케줄 | `sc_local.php`(전체스케줄표) |
| 게시판 | `board_list/write/view/reply/modify.php` |

---

## 주요 함수 참조

### 인증/회원 (`func_list.php`)
- `getinfo_Member($cookie)` — 로그인 쿠키 디코딩
- `getinfo_dbMember($userid)` / `getinfo_dbMember_byid($seq)` / `getinfo_dbMember_byuser($id)` — `member_list` 조회
- `getinfo_dbExMember($userid)` — 만료일 포함 회원 조회
- `hasMenuAccess($division, $par, $sub)` / `hasMenuAccess2($filename)` — 메뉴 권한
- `mailsend_f($to, $subj, $contents, $attachments=false)` — PHPMailer 이메일 발송
- `RemoveXSS($val)` — XSS 필터

### 예약/상품/정산 (`dong_func.php`)
- `getProductMaster($p_code)` / `getProductHMaster($p_code)` — 상품·호텔상품 마스터
- `getReserveInfo($rCode)` / `getReserveHInfo($rCode)` / `getReservePSInfo($rCode,$pcode)` — 예약 정보
- `getReserveInfoCnt / getReserveInfoGCnt / getReserveWaitCnt(...)` — 예약 인원 집계
- `getGuideInfo / getGuideCode / getGuideStatus / getGuideMainPcnt(...)` — 가이드 관련
- `getHotelList / getHotelCnt21 / getHotelass2 / getHotelStStatus(...)` — 호텔 관련
- `getPayment / getPayment2 / getPaymethod / getRandBalance(...)` — 결제/잔액
- `getCarInfo / getBusCnt / getCarStStatus(...)` — 차량/버스

### JavaScript (`js/dongbu.js`, `js/paran.js`)
- 전역 `pt` 객체: `pt.defaults.datepicker`, `pt.defaults.clockpicker`, DataTable 기본값
- `pt.init()` → `pt.initDataTable()` 자동 초기화

---

## guide-settle-app (가이드 정산 모바일 웹앱)

- **인증**: admin의 `MEMLOGIN_ADMIN_HELLO` 쿠키 재사용 (`gsa_require_login()`)
- **접근 권한**: staff는 `hasMenuAccess(6, 2, 10)` 통과 필요, guide는 본인 데이터만
- **역할 판정**: `member_list.division === 'guide'` → 가이드, 그 외 → staff (`gsa_user_role()`)
- **액션 권한**: `gsa_can($role, $action, $row)` — `reg_status === 'COMPLETE'`면 가이드의 수정/제출/취소 차단
- **부트스트랩**: `include/bootstrap.php`가 admin의 `dbconn.php`·`func_list.php`·`dong_func.php`를 재사용
- **레이아웃**: `gsa_layout_head($title)` / `gsa_layout_foot()`

---

## 코드 작성 규칙

### PHP
- **DB 접근은 mysqli 객체 방식**: `$rst = $dbConn->query($qry); $row = $rst->fetch_assoc();` (레거시 `mysql_query()` 사용 금지)
- 보조 DB는 `dbclass.php`의 `db` 클래스(`$db`) prepared statement 사용
- `inc_base.php`가 `$_GET/$_POST/$_SERVER/$_COOKIE`를 `extract()`하므로 요청 파라미터는 변수로 바로 접근 가능 (변수명 충돌 주의)
- 세션/인증: `$_COOKIE['MEMLOGIN_ADMIN_HELLO']` 및 전역 `$user_dbinfo` 사용
- 새 쿼리는 기존 파일의 패턴을 그대로 따를 것
- 작업 후 **수정된 파일 목록 표시**

### JavaScript
- jQuery 사용 (vanilla JS 지양)
- datepicker 초기화: `$.extend({}, pt.defaults.datepicker, { ... })` 패턴으로 기본값 상속
- 위치 문제 발생 시 `container: 'body'` 추가
- 동적 요소 이벤트는 위임 방식: `$(document).on('이벤트', '선택자', handler)`
- 이벤트 네임스페이스 사용: `.off('change.myNS').on('change.myNS', ...)`
- 작업 후 **수정된 파일 목록 표시**

### HTML/레이아웃
- Bootstrap 3 그리드 사용
- 숨기기/보이기는 Bootstrap `hidden` 클래스 사용 (`display:none` 직접 사용 지양)
- 테이블 헤더: 한글 + 영문 부제목 패턴
- 작업 후 **수정된 파일 목록 표시**

---

## 알려진 패턴 및 주의사항

1. **백업 파일 다수 존재**: `base_reservation_m_012623.php`, `assign_m.php_042026`, `base_product_m_1222.php` 등 날짜 접미사(`_MMDDYY`, `_YYYYMMDD`) 파일은 **과거 백업본**이다. 절대 편집하지 말고, 항상 접미사 없는 최신 파일(`base_reservation_m.php` 등)을 대상으로 작업할 것.
2. **CRLF 줄바꿈**: PHP 파일은 Windows CRLF(`\r\n`). Edit 도구로 매칭 실패 시 Python 스크립트 사용.
3. **한글 인코딩**: UTF-8. Python 처리 시 `encode('utf-8')` 필수.
4. **타임존**: 전 시스템 `America/New_York` 고정 — 날짜 계산 시 유의.
5. **전역 extract 주의**: `inc_base.php`가 슈퍼글로벌을 extract하므로, 함수/변수명이 요청 파라미터와 충돌하지 않도록 주의.
6. **인보이스 파일 계열**: `invoice_page.php`(일반), `invoice_p.php`(패키지) — 구조 동기화 필요 시 `invoice_page.php`를 기준으로 삼는다.
7. **두 개의 DB**: 메인은 AWS RDS(`dbs13437728`), 보조는 `parandb`. 어느 연결(`$dbConn` vs `$db`)을 쓰는지 파일별로 확인 후 작업.

---

## 이메일 발송 주의사항
- `mailsend_f()` (PHPMailer) 및 AWS SES(`ses.php`) 사용
- `MsgHTML()` 사용 시 HTML 이메일로 발송
- 스팸 분류 방지를 위해 발신 도메인 SPF/DKIM 인증 유지
- 단축 URL 사용 금지 (스팸 점수 상승)
