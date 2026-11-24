-- 가이드정산 MIS 메뉴 되돌리기 (setup_guide_mis.php 로 넣은 행만 지운다)
DELETE FROM menu_info_user WHERE division='4' AND parent_idx='2' AND sub_idx='25';
DELETE FROM menu_info      WHERE division='4' AND parent_idx=2  AND sub_idx=25 AND menu_link LIKE 'mis_guide_settle.php%';
