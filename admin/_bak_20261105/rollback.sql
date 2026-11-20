-- 호텔 기간별 요금 + 배정시 정산 자동화 롤백 (2026-11-05)
-- 코드만 되돌려도 운영은 정상 동작한다. 이 스크립트는 DB까지 원상복구할 때만 실행.

DROP TABLE IF EXISTS hotel_rate;

ALTER TABLE hotel_settle DROP COLUMN auto_yn;

-- 자동 생성된 정산행만 걷어내고 싶을 때 (auto_yn 컬럼을 지우기 전에 실행해야 한다)
-- DELETE FROM hotel_settle WHERE auto_yn = 'Y';
