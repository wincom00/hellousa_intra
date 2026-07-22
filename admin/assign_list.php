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

/* ========== 수정확인 버튼 처리 ========== */
if ($mode == "markAsRead") {
    $pcode = $_POST['pcode'];
    $stDate = $_POST['stDate'];
    $qry = "
        UPDATE reserve_info 
        SET is_modified = 0 
        WHERE p_code = '$pcode' 
        AND stDate = '$stDate' 
        AND is_modified = 1
    ";
    $rst = $dbConn->query($qry);
    if ($rst) {
        echo json_encode(['success'=>true,'message'=>'확인 처리되었습니다.']);
    } else {
        echo json_encode(['success'=>false,'message'=>'처리 중 오류가 발생했습니다.']);
    }
    exit;
}

/* ======================================================
   출력 함수
====================================================== */
function printSingle() {
    global $dbConn,$division,$crev,$pdx,$sub,$productName,$evest,$startDate1,$endDate1;

    $qrynm = ($productName) ? " && a.p_name like '%$productName%'" : "";
    if ($startDate1 == "") {
        $startDate1 = date("Y-m-d");
        $endDate1   = date("Y-m-d",strtotime("+1 month"));
    }
    $qrysdate = ($startDate1) ? " && c.stDate between '$startDate1' and '$endDate1'" : "";
    $qryeve   = ($evest) ? " && c.ev_status='$evest'" : "";

    $qry1 = "
    select 
        a.reserveCode,
        c.grand_eCode,
        a.p_code,
        a.p_name,
        c.stDate,
        b.c_code1,b.c_code2,b.p_own,c.tour_pcnt,b.p_day,b.p_cnt,
        c.r_status,c.ev_status,b.p_type,
        MAX(a.is_modified) as has_modification,

        /* ▼ 배정 여부 카운트 */
        (select count(*) from tour_car tc 
            where tc.p_code=a.p_code and tc.stDate=c.stDate)        as car_cnt,
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
    order by c.stDate desc";

    $rst1 = $dbConn->query($qry1);
    while($row1 = $rst1->fetch_assoc()) {
        $modifiedMark = ($row1['has_modification']==1) ? ' <span class="badge badge-warning">수정됨</span>' : '';
        $rowClass = ($row1['has_modification']==1) ? 'table-warning' : '';

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

        $pcnt = getReserveInfoCntS($row1['p_code'],$row1['stDate']);

        /* 배정 여부 배지 */
        $carBadge   = ($row1['car_cnt']   > 0) ? "<span class='badge badge-success'>차량</span>"     : "<span class='badge badge-muted'>차량없음</span>";
        $hotelBadge = ($row1['hotel_cnt'] > 0) ? "<span class='badge badge-success'>호텔</span>"     : "<span class='badge badge-muted'>호텔없음</span>";
        $guideBadge = ($row1['guide_cnt'] > 0) ? "<span class='badge badge-success'>가이드</span>"   : "<span class='badge badge-muted'>가이드없음</span>";

        echo "<tr class='arhef $rowClass' data-href='assign_m.php?division=$division&pdx=$pdx&sub=$sub&st=$row1[stDate]&pcode=$row1[p_code]'>
                <td align='center'>
                    {$row1['grand_eCode']}{$modifiedMark}
                </td>
                <td>{$row1['p_type']}</td>
                <td>{$cinfo2['comment']}</td>
                <td>{$row1['p_code']}</td>
                <td>{$row1['p_name']}</td>
                <td align='center'>{$row1['stDate']}</td>
                <td align='center'>{$row1['p_cnt']}</td>
                <td align='center'>{$pcnt['cnt']}</td>
                <td align='center'>{$row1['r_status']}</td>
                <td align='center'>{$row1['ev_status']}</td>
                <td align='center'>$carBadge $hotelBadge $guideBadge</td>
              </tr>";
    }
}
?>

<style>
/* 기존 수정행 스타일 */
.modified-row { background-color:#fff3cd !important; border-left:4px solid #ffc107; }
.modified-icon { color:#ffc107; font-weight:bold; margin-right:5px; }
.modified-row:hover { background-color:#ffeaa7 !important; }
/* 배정 상태 배지 */
.badge-success { background:#28a745; color:#fff; }
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

        <div class="row">
            <div class="col-sm-12 col-md-12">
                <form action="" name="frmName" method="post">
                    <input type="hidden" name="mode" value="search">
                    <table class="table table-bordered table-condensed">
                        <tr>
                            <td width="10%" class="titletd text-center">상품명</td>
                            <td width="40%"><input type="text" name="productName" class="inpubase" value=""/></td>
                            <td width="10%" class="titletd text-center">출발일</td>
                            <td width="40%">
                                <div class="row">
                                    <div class="col-sm-6">
                                        <input type="search" id="startDate1" name="startDate1" class="inpubase tourDate1" placeholder="시작일" autocomplete="off" />
                                    </div>
                                    <div class="col-sm-6">
                                        <input type="search" id="endDate1" name="endDate1" class="inpubase tourDate2" placeholder="마지막일" autocomplete="off" />
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
                                <span class="badge badge-warning">수정됨</span> 예약이 변경된 행사
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

    $('tr[data-href]').on("click", function(e) {
        if (!$(e.target).closest('.mark-read-btn').length) {
            window.open($(this).data('href'), '_blank');
        }
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
