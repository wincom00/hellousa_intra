<?php
// PHP 7.4 / mysqli_* 절차형 / FullCalendar 무료판(dayGrid + list)
include 'include/header.php';

$grand_eCode = isset($_GET['grand_eCode']) ? trim($_GET['grand_eCode']) : '';
if ($grand_eCode === '') {
  http_response_code(400);
  exit('grand_eCode 파라미터가 필요합니다. 예) ?grand_eCode=GE2025-001');
}
?>
<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8">
  <title>행사 스케줄(무료판)</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.5/main.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.5/main.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid@5.11.5/main.global.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/list@5.11.5/main.global.min.js"></script>
  <style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Apple SD Gothic Neo,Malgun Gothic,sans-serif}
    #wrap{max-width:1200px;margin:18px auto;padding:0 12px}
    .toolbar{display:flex;gap:8px;align-items:center;margin-bottom:10px}
    .toolbar select{padding:6px 8px}
    .badge{display:inline-block;margin-left:6px;padding:0 6px;border:1px solid #999;border-radius:8px;font-size:11px}
  </style>
</head>
<body>
<div id="wrap">
  <div class="toolbar">
    <label>호차 필터:</label>
    <select id="resFilter">
      <option value="">전체</option>
    </select>
  </div>
  <div id="calendar"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async function(){
  const grand = "<?=htmlspecialchars($grand_eCode, ENT_QUOTES, 'UTF-8')?>";

  // 1) 리소스 목록 로드 → 필터 옵션 구성
  let res = [];
  try {
    const r = await fetch(`api_schedule.php?mode=resources&grand_eCode=${encodeURIComponent(grand)}`);
    if(r.ok) res = await r.json();
  } catch(e){ console.error(e); }

  const sel = document.getElementById('resFilter');
  res.forEach(r=>{
    const opt=document.createElement('option');
    opt.value=r.id;
    opt.text=`${r.group} — ${r.title}`;
    sel.appendChild(opt);
  });

  // 2) 달력 생성
  const cal = new FullCalendar.Calendar(document.getElementById('calendar'), {
    plugins: [ 'dayGrid','list' ],
    initialView: 'dayGridMonth',
    headerToolbar:{left:'prev,next today',center:'title',right:'dayGridMonth,dayGridWeek,listWeek'},
    height:'auto',
    firstDay:0,
    events:{
      url:'api_schedule.php',
      method:'GET',
      extraParams:()=>({mode:'events',grand_eCode:grand,resourceId:sel.value})
    },
    eventDidMount:function(info){
      const g=info.event.extendedProps && info.event.extendedProps.guideBadge;
      if(g){
        const t=info.el.querySelector('.fc-event-title');
        if(t){
          const b=document.createElement('span');
          b.className='badge';
          b.textContent=g;
          t.appendChild(b);
        }
      }
    }
  });
  cal.render();

  sel.addEventListener('change',()=>cal.refetchEvents());
});
</script>
</body>
</html>
