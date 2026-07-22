<?php
require_once 'include/header.php';
require_once 'include/side_m.php'; 
 
$q     = trim($_GET['q'] ?? '');
$from  = $_GET['from'] ?? '';
$to    = $_GET['to']   ?? '';

$sql  = "SELECT id, estimate_no, to_name, group_name, pax, foc, total_pax,
                start_date, end_date, grand_total, per_pax, updated_at, wdate
         FROM estimate_master WHERE 1";
$types=''; $params=[];

if ($q !== '') {
  $sql .= " AND (estimate_no LIKE CONCAT('%',?,'%')
              OR group_name LIKE CONCAT('%',?,'%')
              OR to_name LIKE CONCAT('%',?,'%'))";
  $types.='sss'; array_push($params,$q,$q,$q);
}
if ($from !== '') { $sql .= " AND wdate >= ?"; $types.='s'; $params[]=$from; }
if ($to   !== '') { $sql .= " AND wdate <= ?"; $types.='s'; $params[]=$to;   }

$sql .= " ORDER BY id DESC LIMIT 300";
$stmt = $dbConn->prepare($sql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
?>
<style>
  /* === 페이지 본문만 풀사이즈 === */
  #contentwrapper.reservationDetailForm .main_content{
    display:flex;
    flex-direction:column;
    min-height:100dvh;  /* viewport 높이 꽉 채움 (모바일도 안전) */
    padding:0;          /* 좌우/상하 여백은 list-wrap에서 처리 */
  }

  /* 본문 컨테이너를 가로 100%로 (기존 max-width 제거) */
  .list-wrap{width:100%;max-width:none;margin:16px 0 0;padding:0 16px}

  /* 필터/버튼/테이블 기본 스타일(기존 유지) */
  .flt{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-bottom:12px}
  .btn{border:1px solid #e5e7eb;padding:7px 10px;border-radius:8px;background:#fff}
  .btn.key{background:#0b5bd3;color:#fff;border-color:#0b5bd3}
  .tbl{width:100%;border-collapse:collapse}
  .tbl th,.tbl td{border:1px solid #e5e7eb;padding:8px 10px}
  .tbl th{background:#fafafa}
  .num{text-align:right}

  /* 테이블 영역이 남은 높이를 꽉 쓰고 내부 스크롤되도록 */
  .tbl-area{flex:1;min-height:0;overflow:auto}
</style>

<div id="contentwrapper" class="reservationDetailForm">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb module">
				<ul>
					<li><a href="/"><i class="glyphicon glyphicon-home"></i></a></li>
					<li><a href="#">예약관리</a></li>
					<li>업체/맞춤여행견적</li>
				</ul>
			</div>
			<div class="list-wrap">
			  <h2 style="margin:6px 0 14px">견적서 목록</h2>

			  <form class="flt" method="get">
				<input type="text"   class="btn" name="q"    value="<?=htmlspecialchars($q)?>" placeholder="견적번호/그룹/TO" style="min-width:260px">
				<input type="date"   class="btn" name="from" value="<?=$from?>">
				<input type="date"   class="btn" name="to"   value="<?=$to?>">
				<button class="btn key">검색</button>
				<a class="btn" href="estimate_form.php">+ 새 견적</a>
			  </form>

			  <div style="overflow:auto">
				<table class="tbl">
				  <thead>
					<tr>
					  <th style="width:120px">견적번호</th>
					  <th>GROUP</th>
					  <th>TO</th>
					  <th class="num" style="width:70px">PAX</th>
					  <th style="width:180px">여행기간</th>
					  <th class="num" style="width:120px">총액</th>
					  <th class="num" style="width:120px">1인요금</th>
					  <th style="width:140px">작성일/수정일</th>
					  <th style="width:200px">액션</th>
					</tr>
				  </thead>
				  <tbody>
					<?php while($r=$res->fetch_assoc()): ?>
					<tr>
					  <td><?=htmlspecialchars($r['estimate_no'])?></td>
					  <td><?=htmlspecialchars($r['group_name'])?></td>
					  <td><?=htmlspecialchars($r['to_name'])?></td>
					  <td class="num"><?=number_format($r['total_pax'])?></td>
					  <td><?=htmlspecialchars($r['start_date'].' ~ '.$r['end_date'])?></td>
					  <td class="num"><?=number_format($r['grand_total'])?></td>
					  <td class="num"><?=number_format($r['per_pax'])?></td>
					  <td><?=htmlspecialchars($r['wdate'])?><br><span style="color:#667085"><?=htmlspecialchars($r['updated_at'])?></span></td>
					  <td>
						<a class="btn key" href="estimate_form.php?id=<?=$r['id']?>">수정</a>
						<a class="btn" target="_blank" href="estimate_view.php?id=<?=$r['id']?>">내보내기</a>
						<!-- 상세 전용 페이지가 따로 필요하면 estimate_view.php로 분리 가능 -->
					  </td>
					</tr>
					<?php endwhile; ?>
					<?php if ($res->num_rows===0): ?>
					<tr><td colspan="9" style="text-align:center;color:#667085">검색 결과가 없습니다.</td></tr>
					<?php endif; ?>
				  </tbody>
				</table>
			  </div>
			</div>
      </div>
</div>
