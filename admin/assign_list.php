<?php
include "include/header.php";

if ($_COOKIE['MEMLOGIN_ADMIN_HELLO'] == "") {
    echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
    exit;
}

if (!hasMenuAccess($division, $pdx, $sub)) {
    $goUrl_1 = "index.php";
    Misc::jvAlert("권한이 있는 메뉴가 아닙니다. 확인후 사용하세요.!!","");
    echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
    exit;
}

/* ======================================================
   배정이상 집계
   - 미배정   : 차량 배정이 아예 없음 → 출발 임박(오늘 ~ +7일)만 집계.
                먼 미래 행사는 아직 배정 전이 정상이므로 제외한다.
   - 불일치   : 배정을 시작했는데 배정인원(tour_car 행수) != 예약인원,
                또는 가이드 수 != 버스 대수 → 출발일과 무관한 오류이므로
                오늘 이후 전체를 집계한다.
====================================================== */
function getUnassignedPast() {
    global $dbConn;
    $from = date("Y-m-d");                       /* 오늘 */
    $to   = date("Y-m-d", strtotime("+7 day"));  /* 7일 뒤 (미배정 집계 한계) */

    $qry = "
    select
        /* 미배정 = 차량 배정이 아예 없음 (출발 임박분만) */
        sum(case when unas=1 and soon=1 then 1 else 0 end)       as unas_cnt,
        sum(case when unas=1 and soon=1 then rev_cnt else 0 end) as unas_rev,
        sum(case when unas=1 and soon=1 then pax else 0 end)     as unas_pax,
        sum(case when unas=1 and soon=1 and guide_cnt=0 then 1 else 0 end) as no_guide,

        /* 불일치 = 배정은 시작했는데 건수가 안 맞음 (오늘 이후 전체) */
        sum(case when unas=0 and (car_cnt<>pax or guide_cnt<>bus_cnt) then 1 else 0 end)   as diff_cnt,
        sum(case when unas=0 and car_cnt<>pax then 1 else 0 end)                           as diff_pax_cnt,
        sum(case when unas=0 and guide_cnt<>bus_cnt then 1 else 0 end)                     as diff_guide_cnt,
        max(stDate) as last_date
    from (
      select t0.*,
             (case when t0.car_cnt=0 then 1 else 0 end)      as unas,
             (case when t0.stDate <= '$to' then 1 else 0 end) as soon
      from (
        select
            a.p_code, c.stDate,
            count(*) as rev_cnt,
            sum(case when a.rev_status='DONE' then a.p_cnt else 0 end) as pax,
            (select count(*) from tour_car tc
                where tc.p_code=a.p_code and tc.stDate=c.stDate)   as car_cnt,
            (select count(distinct tc.bus_num) from tour_car tc
                where tc.p_code=a.p_code and tc.stDate=c.stDate)   as bus_cnt,
            (select count(*) from tour_guide tg
                where tg.p_code=a.p_code and tg.stDate=c.stDate)   as guide_cnt
        from reserve_info a
        join product_master b on a.p_code=b.p_code
        join tour_master    c on b.p_code=c.p_code and a.stDate=c.stDate
        where b.p_type in ('1','2','4')
          and b.m_type = 'S'
          and a.rev_status != 'CANCEL'
          and c.ev_status != '4'
          and c.stDate >= '$from'
        group by a.p_code, c.stDate
      ) t0
      where t0.pax > 0   /* 예약(확정)이 실제 있는 행사만 */
    ) t";

    $rst = $dbConn->query($qry);
    $row = ($rst) ? $rst->fetch_assoc() : array();
    $row['from'] = $from;
    $row['to']   = $to;
    return $row;
}

/* ======================================================
   출력 함수
====================================================== */
function printSingle() {
    global $dbConn,$division,$crev,$pdx,$sub,$productName,$evest,$startDate1,$endDate1,$unassigned;

    $qrynm = ($productName) ? " && a.p_name like '%$productName%'" : "";
    if ($startDate1 == "") {
        $startDate1 = date("Y-m-d");
        $endDate1   = date("Y-m-d",strtotime("+1 month"));
    }
    $qrysdate = ($startDate1) ? " && c.stDate between '$startDate1' and '$endDate1'" : "";
    $qryeve   = ($evest) ? " && c.ev_status='$evest'" : "";
    /* 배정이상 필터 — 아래 행 강조(unas-row / diff-row) 규칙과 반드시 동일하게 유지할 것
       미배정 : 차량 배정 없음 + 출발 임박(오늘 ~ +7일)
       불일치 : 배정 시작함 + (배정인원 != 예약인원 또는 가이드 수 != 버스 대수) */
    $soonFrom = date("Y-m-d");
    $soonTo   = date("Y-m-d", strtotime("+7 day"));
    $qryunas  = ($unassigned)
        ? " having pax_done > 0
              and ( (car_cnt = 0 and c.stDate between '$soonFrom' and '$soonTo')
                 or (car_cnt > 0 and (car_cnt <> pax_done or guide_cnt <> bus_cnt)) )"
        : "";

    $qry1 = "
    select 
        a.reserveCode,
        c.grand_eCode,
        a.p_code,
        a.p_name,
        c.stDate,
        b.c_code1,b.c_code2,b.p_own,c.tour_pcnt,b.p_day,b.p_cnt,
        c.r_status,c.ev_status,b.p_type,
        sum(case when a.rev_status='DONE' then a.p_cnt else 0 end) as pax_done,

        /* ▼ 배정 여부 카운트 (car_cnt = tour_car 행수 = 배정인원) */
        (select count(*) from tour_car tc
            where tc.p_code=a.p_code and tc.stDate=c.stDate)        as car_cnt,
        (select count(distinct tc.bus_num) from tour_car tc
            where tc.p_code=a.p_code and tc.stDate=c.stDate)        as bus_cnt,
        (select count(*) from hotel_assign ha
            where ha.p_code=a.p_code and ha.stDate=c.stDate)        as hotel_cnt,
        (select count(*) from tour_guide tg 
            where tg.p_code=a.p_code and tg.stDate=c.stDate)        as guide_cnt

    from reserve_info a
    join product_master b on a.p_code=b.p_code
    join tour_master    c on b.p_code=c.p_code and a.stDate=c.stDate
    where b.p_type in ('1','2','4')
      and b.m_type = 'S'
      and a.rev_status != 'CANCEL'
      $qrynm $qrysdate $qryeve
    group by a.p_code, c.stDate
    $qryunas
    order by c.stDate desc";

    $rst1 = $dbConn->query($qry1);
    while($row1 = $rst1->fetch_assoc()) {
        $rowMark  = '';
        $rowClass = '';

        $cinfo1 = codebaseName($row1['c_code1']);
        $cinfo2 = codebaseName($row1['c_code2']);

        if ($row1['r_status']== 'P') $row1['r_status'] = "<font color=red>예약접수중</font>";
        if ($row1['r_status']== 'C') $row1['r_status'] = "<font color=red>예약마감</font>";
        if ($row1['r_status']== '')  $row1['r_status'] = "<font color=red>미등록</font>";

        if ($row1['ev_status']== '1') $row1['ev_status'] = "<font color=red>미확정</font>";
        if ($row1['ev_status']== '2') $row1['ev_status'] = "<font color=red>확정</font>";
        if ($row1['ev_status']== '3') $row1['ev_status'] = "<font color=red>만차</font>";
        if ($row1['ev_status']== '4') $row1['ev_status'] = "<font color=red>취소</font>";
        if ($row1['ev_status']== '5') $row1['ev_status'] = "<font color=red>기타</font>";
        if ($row1['ev_status']== '')  $row1['ev_status'] = "<font color=red>미등록</font>";

        if ($row1['p_type']==1) $row1['p_type'] = "단일상품";
        if ($row1['p_type']==4) $row1['p_type'] = "인센티브";

        $revPax   = (int)$row1['pax_done'];    /* 예약인원(확정) */
        $carPax   = (int)$row1['car_cnt'];     /* 배정인원 = tour_car 행수 */
        $busCnt   = (int)$row1['bus_cnt'];     /* 배정된 버스 대수 */
        $guideCnt = (int)$row1['guide_cnt'];   /* 가이드 배정 행수(1행=1차량) */

        /* 배정 여부 배지 — 배정/예약 건수를 나란히 표기
           배정을 시작한 행사에서만 건수를 비교한다(배정 전에는 0이 정상) */
        $paxDiff   = ($carPax > 0 && $carPax != $revPax);
        $guideDiff = ($carPax > 0 && $guideCnt != $busCnt);

        $carBadge = ($carPax > 0)
            ? "<span class='badge " . ($paxDiff ? "badge-orange" : "badge-success") . "'>차량 $carPax/$revPax</span>"
            : "<span class='badge badge-muted'>차량없음</span>";
        $hotelBadge = ($row1['hotel_cnt'] > 0) ? "<span class='badge badge-success'>호텔</span>" : "<span class='badge badge-muted'>호텔없음</span>";
        $guideBadge = ($guideCnt > 0)
            ? "<span class='badge " . ($guideDiff ? "badge-orange" : "badge-success") . "'>가이드 $guideCnt/{$busCnt}대</span>"
            : "<span class='badge " . ($guideDiff ? "badge-orange" : "badge-muted") . "'>가이드없음</span>";

        /* 배정이상 강조
           미배정(빨강) : 배정이 아예 없음 — 출발 임박(오늘~+7일)일 때만.
                          먼 미래 행사는 아직 배정 전이 정상이므로 제외한다.
           불일치(주황) : 배정을 시작했는데 건수가 안 맞음 — 출발일과 무관하게 항상 표시.
                          배정인원 != 예약인원, 또는 가이드 수 != 버스 대수 */
        $isSoon = ($row1['stDate'] >= date("Y-m-d") && $row1['stDate'] <= date("Y-m-d", strtotime("+7 day")));
        if ($revPax > 0) {
            if ($carPax == 0 && $isSoon) {
                $rowClass .= " unas-row";
                $rowMark .= " <span class='badge badge-danger'>미배정</span>";
            } elseif ($paxDiff || $guideDiff) {
                $rowClass .= " diff-row";
                if ($paxDiff) {
                    $gap = $carPax - $revPax;
                    $rowMark .= " <span class='badge badge-orange'>인원 " . ($gap > 0 ? "+$gap" : $gap) . "</span>";
                }
                if ($guideDiff) {
                    $rowMark .= " <span class='badge badge-orange'>가이드 $guideCnt/{$busCnt}대</span>";
                }
            }
        }

        echo "<tr class='arhef $rowClass' data-href='assign_m.php?division=$division&pdx=$pdx&sub=$sub&st=$row1[stDate]&pcode=$row1[p_code]'>
                <td align='center'>
                    {$row1['grand_eCode']}{$rowMark}
                </td>
                <td>{$row1['p_type']}</td>
                <td>{$cinfo2['comment']}</td>
                <td>{$row1['p_code']}</td>
                <td>{$row1['p_name']}</td>
                <td align='center'>{$row1['stDate']}</td>
                <td align='center'>{$row1['p_cnt']}</td>
                <td align='center'>{$revPax}</td>
                <td align='center'>{$row1['r_status']}</td>
                <td align='center'>{$row1['ev_status']}</td>
                <td align='center'>$carBadge $hotelBadge $guideBadge</td>
              </tr>";
    }
}
?>

<style>
/* 미배정 카드 */
.unas-card { display:block; padding:12px 14px; border-radius:4px; border:1px solid #ddd;
             background:#fff; color:#333; text-decoration:none; margin-bottom:12px; }
.unas-card:hover { text-decoration:none; color:#333; box-shadow:0 1px 4px rgba(0,0,0,.15); }
.unas-card.unas-danger { border-left:5px solid #d9534f; }
.unas-card.unas-warn   { border-left:5px solid #f0932b; }
.unas-card.unas-ok     { border-left:5px solid #5cb85c; }
.unas-title { font-size:13px; font-weight:bold; }
.unas-title small { display:block; font-weight:normal; color:#999; }
.unas-num { font-size:30px; font-weight:bold; line-height:1.1; margin:4px 0; }
.unas-card.unas-danger .unas-num { color:#d9534f; }
.unas-card.unas-warn   .unas-num { color:#f0932b; }
.unas-card.unas-ok     .unas-num { color:#5cb85c; }
.unas-num span { font-size:14px; font-weight:normal; margin-left:3px; }
.unas-sub { font-size:12px; color:#666; }
.unas-foot { font-size:11px; color:#999; margin-top:3px; }
/* 출발일 도래 미배정 행 */
.unas-row > td { background-color:#fdecea !important; }
.unas-row:hover > td { background-color:#f9d6d1 !important; }
.unas-row > td:first-child { border-left:4px solid #d9534f; }
/* 출발일 도래 인원/가이드 불일치 행 */
.diff-row > td { background-color:#fff4e5 !important; }
.diff-row:hover > td { background-color:#ffe6c7 !important; }
.diff-row > td:first-child { border-left:4px solid #f0932b; }
/* 배정 상태 배지 */
.badge-success { background:#28a745; color:#fff; }
.badge-danger { background:#d9534f; color:#fff; }
.badge-orange { background:#f0932b; color:#fff; }
.badge-muted { background:#adb5bd; color:#fff; }
.badge + .badge { margin-left:4px; }
</style>

<div id="contentwrapper" class="reservationDetailForm">
    <div class="main_content">
        <div id="jCrumbs" class="breadCrumb module">
            <ul>
                <li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li>
                <li><a href="#">행사배정</a></li>
                <li>통합행사관리</li>
            </ul>
        </div>

<?php
$unas = getUnassignedPast();
$unasBase = "assign_list.php?division=$division&pdx=$pdx&sub=$sub&unassigned=1&startDate1={$unas['from']}";
$unasLink = $unasBase . "&endDate1={$unas['to']}";                                   /* 미배정 : 오늘~+7일 */
$diffLink = $unasBase . "&endDate1=" . ($unas['last_date'] ?: $unas['to']);          /* 불일치 : 오늘 이후 전체 */
?>
        <div class="row">
            <div class="col-sm-4 col-md-3">
                <a href="<?=$unasLink?>" class="unas-card <?=($unas['unas_cnt']>0)?'unas-danger':'unas-ok'?>">
                    <div class="unas-title">출발 임박(7일) &middot; 미배정 <small>Unassigned &middot; departing soon</small></div>
                    <div class="unas-num"><?=(int)$unas['unas_cnt']?><span>건</span></div>
                    <div class="unas-sub">
                        예약 <b><?=(int)$unas['unas_rev']?></b>건 / <b><?=(int)$unas['unas_pax']?></b>명
                    </div>
                    <div class="unas-sub">
                        가이드도 없음 <b><?=(int)$unas['no_guide']?></b>건
                    </div>
                    <div class="unas-foot"><?=$unas['from']?> ~ <?=$unas['to']?> 출발분</div>
                </a>
            </div>
            <div class="col-sm-4 col-md-3">
                <a href="<?=$diffLink?>" class="unas-card <?=($unas['diff_cnt']>0)?'unas-warn':'unas-ok'?>">
                    <div class="unas-title">배정건수 불일치 <small>Assigned &ne; reserved</small></div>
                    <div class="unas-num"><?=(int)$unas['diff_cnt']?><span>건</span></div>
                    <div class="unas-sub">
                        배정인원 &ne; 예약인원 <b><?=(int)$unas['diff_pax_cnt']?></b>건
                    </div>
                    <div class="unas-sub">
                        가이드 수 &ne; 버스 대수 <b><?=(int)$unas['diff_guide_cnt']?></b>건
                    </div>
                    <div class="unas-foot"><?=$unas['from']?> 이후 출발분 전체</div>
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12 col-md-12">
                <form action="" name="frmName" method="post">
                    <input type="hidden" name="mode" value="search">
                    <table class="table table-bordered table-condensed">
                        <tr>
                            <td width="10%" class="titletd text-center">상품명</td>
                            <td width="40%"><input type="text" name="productName" class="inpubase" value="<?=htmlspecialchars($productName)?>"/></td>
                            <td width="10%" class="titletd text-center">출발일</td>
                            <td width="40%">
                                <div class="row">
                                    <div class="col-sm-6">
                                        <input type="search" id="startDate1" name="startDate1" class="inpubase tourDate1" placeholder="시작일" value="<?=htmlspecialchars($startDate1)?>" autocomplete="off" />
                                    </div>
                                    <div class="col-sm-6">
                                        <input type="search" id="endDate1" name="endDate1" class="inpubase tourDate2" placeholder="마지막일" value="<?=htmlspecialchars($endDate1)?>" autocomplete="off" />
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="titletd text-center">행사상태</td>
                            <td colspan="3">
                                <select class="form-control" name="evest">
                                    <option value="">- 선택 -</option>
                                    <option value="1" <?php if($evest==1) echo "selected";?>>미확정</option>
                                    <option value="2" <?php if($evest==2) echo "selected";?>>확정</option>
                                    <option value="3" <?php if($evest==3) echo "selected";?>>만차</option>
                                    <option value="4" <?php if($evest==4) echo "selected";?>>취소</option>
                                    <option value="5" <?php if($evest==5) echo "selected";?>>기타</option>
                                </select>
                                <label style="font-weight:normal;margin-top:5px;">
                                    <input type="checkbox" name="unassigned" value="1" <?php if($unassigned) echo "checked";?>> 배정이상(미배정 &middot; 건수불일치)만 보기
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="4" class="text-center">
                                <button type='submit' class="btn btn-primary btn-sm btn1">검색</button>
                            </td>
                        </tr>
                    </table>
                </form>

                <div class="row">
                    <div class="col-sm-12">
                        <div class="alert alert-info" style="padding:8px;margin-bottom:10px;">
                            <small>
                                <span class="badge badge-danger">미배정</span> <span style="background:#fdecea;padding:1px 6px;">붉은 행</span> 출발이 7일 이내인데 차량 배정이 없음
                                &nbsp;&nbsp;
                                <span class="badge badge-orange">불일치</span> <span style="background:#fff4e5;padding:1px 6px;">주황 행</span> 배정을 시작했는데 배정인원 &ne; 예약인원 이거나 가이드 수 &ne; 버스 대수 (출발일 무관)
                                <br>배정 칼럼은 <b>차량 배정인원/예약인원</b>, <b>가이드 배정수/버스대수</b>
                            </small>
                        </div>

                        <table class="table table-striped table-bordered table-hover table-condensed" id='ctable'>
                            <thead>
                                <tr>
                                    <th>통합행사코드</th>
                                    <th>투어분류</th>
                                    <th>상품지역분류</th>
                                    <th>상품코드</th>
                                    <th>상품명</th>
                                    <th>출발일</th>
                                    <th>정원</th>
                                    <th>예약/대기</th>
                                    <th>예약상태</th>
                                    <th>행사상태</th>
                                    <th>배정</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?=printSingle()?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>                
    </div>
</div>

<?php include "include/side_m.php"; ?>

<script>
$(document).ready(function () {
    var dateToday = new Date();
    $('.tourDate1,.tourDate2').datepicker({
        format: "yyyy-mm-dd",
        autoclose: true
    });

    $('tr[data-href]').on("click", function() {
        window.open($(this).data('href'), '_blank');
    });

    $('#ctable').dataTable({
        pageLength: 100,
        "order": [[5,"desc"]]
    });
    $(".dataTables_length").hide();
});
</script>
</body>
</html>
