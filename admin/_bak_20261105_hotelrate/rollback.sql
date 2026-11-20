-- 호텔 기간별 요금 + 호텔정산 자동화 롤백 (2026-11-05)
-- 코드만 되돌려도 운영은 정상 동작한다. DB까지 원상복구할 때만 실행.

-- (선택) 일괄 자동정산으로 생겨서 아직 담당자가 저장하지 않은 정산 걷어내기 — 필요할 때만 주석 해제
-- DELETE s FROM hotel_settle s JOIN hotel_settlesum m ON m.grand_eCode=s.grand_eCode AND m.sub_eCode=s.sub_eCode WHERE m.status='AUTO';
-- DELETE e FROM hotel_settleetc e JOIN hotel_settlesum m ON m.grand_eCode=e.grand_eCode AND m.sub_eCode=e.sub_eCode WHERE m.status='AUTO';
-- DELETE FROM hotel_settlesum WHERE status='AUTO';

-- 예전 기초관리 메뉴(1-1-4)가 남아 있을 때만 해당 (현재 버전은 메뉴를 만들지 않음)
DELETE FROM menu_info_user WHERE division='1' AND parent_idx='1' AND sub_idx='4' AND menu_link LIKE 'hotel_rate.php%';
DELETE FROM menu_info WHERE division='1' AND parent_idx='1' AND sub_idx='4' AND menu_link LIKE 'hotel_rate.php%';

DROP TABLE IF EXISTS hotel_rate;
