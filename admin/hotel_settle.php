<?php
    include "include/header.php";
    
    if ($_COOKIE['MEMLOGIN_ADMIN_HELLO'] != "") {
    } else {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
        exit;
    }
    if (!hasMenuAccess($division, $pdx, $sub)) {
        $goUrl_1 = "index.php";
        Misc::jvAlert("권한이 있는 메뉴가 아닙니다. 확인후 사용하세요.!!","");
        echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
        exit;
    }

    // [함수] 특정 행사의 가이드 명단 (단일 서브행사용으로 최적화 가능하지만 기존 구조 유지)
    function getGuideListBySubEvent($grand_eCode, $stDate, $sub_eCode) {
        global $dbConn;
        $sql = "SELECT sub_eCode, guide_id, 
                (SELECT kor_name FROM member_list WHERE userid = tour_guide.guide_id AND division = 'guide') as k_name
                FROM tour_guide 
                WHERE grand_eCode = '$grand_eCode' 
                  AND stDate = '$stDate' 
                  AND sub_eCode = '$sub_eCode'
                ORDER BY sub_eCode ASC";
        
        $result = $dbConn->query($sql);
        $display_str = "";
        while($row = mysqli_fetch_assoc($result)) {
            $guideName = $row['k_name'] ? $row['k_name'] : "<span style='color:#ccc;'>미지정</span>";
            $display_str .= "<b>$guideName</b>";
        }
        return $display_str;
    }

    function printSingle(){
        global $dbConn,$division,$crev,$pdx,$sub,$startDate1,$endDate1,$guideid;

        if ($startDate1) $from_w = " AND a.stDate >= '$startDate1' ";
        if ($endDate1) $to_w = " AND a.stDate <= '$endDate1' ";
        if($guideid) $guide_w = " AND a.guide_id = '$guideid' ";

        // [수정] 메인 코드 + 서브 코드 기준으로 그룹화하여 각각의 서브행사가 리스트에 나오도록 함
        $query = "SELECT a.seq_no as seq, a.* FROM (
         SELECT a.seq_no, a.grand_eCode, a.sub_eCode, a.stDate, a.guide_id, a.p_code, a.p_name
        FROM tour_guide a, tour_master b
        WHERE a.grand_eCode = b.grand_eCode AND a.p_code = b.p_code
        $from_w $to_w $guide_w ) a LEFT JOIN product_master b ON a.p_code = b.p_code
        WHERE 1=1 
        GROUP BY a.grand_eCode, a.sub_eCode 
        ORDER BY a.stDate DESC, a.sub_eCode ASC";

        $rst1 = $dbConn->query($query);
        while($row1 = mysqli_Fetch_assoc($rst1)){

            $p_cnt = getReserveInfoCnt($row1['p_code'],$row1['stDate']);
            $period = getPeriodbyhotel($row1['p_code'],$row1['stDate']);
            
            // [수정] 상태 체크 함수에 서브코드(sub_eCode) 파라미터 전달
            $status1 = getHotelStStatus($row1['grand_eCode'], $row1['sub_eCode'], $row1['stDate']);
            $status2 = getCarStStatus($row1['grand_eCode'], $row1['sub_eCode'], $row1['stDate']);
            
            $guide_name = getGuideListBySubEvent($row1['grand_eCode'], $row1['stDate'], $row1['sub_eCode']);

            // [수정] 링크 설정 시 sub_eCode 추가 전달
            $link_hotel = "hotel_cal2.php?division=6&pdx=1&sub=10&number=".$row1['seq']."&sub_eCode=".$row1['sub_eCode'];
            $link_car   = "car_cal2.php?division=6&pdx=1&sub=15&number=".$row1['seq']."&sub_eCode=".$row1['sub_eCode'];

            echo "<tr>
                <td align='center'>$row1[grand_eCode]</td>
                <td align='center' style='color:blue; font-weight:bold;'>$row1[sub_eCode]</td>
                <td align='center'>$row1[stDate]</td>
                <td>$row1[p_name]</td>
                <td align='center'>$period</td>
                <td align='center'>$p_cnt[cnt]</td>
                <td align='center'>$guide_name</td>
                <td align='center'>$status1</td>
                <td align='center'>$status2</td>
                <td align='center' style='vertical-align:middle;'>
                    <div class='btn-group btn-group-xs'>
                        <button type='button' class='btn btn-primary' onclick=\"location.href='$link_hotel'\" title='호텔 정산'>호텔</button>
                        <button type='button' class='btn btn-success' onclick=\"location.href='$link_car'\" title='차량 정산'>차량</button>
                    </div>
                </td>
            </tr>";
        }
    } 
?>

    <div id="contentwrapper" class="reservationDetailForm">
        <div class="main_content">
            <div id="jCrumbs" class="breadCrumb module">
                <ul>
                    <li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li>
                    <li><a href="#">정산관리</a></li>
                    <li>호텔별정산(서브코드별)</li>
                </ul>
            </div>

            <div class="row">
                <div class="col-sm-12 col-md-12">
                    <form action="" name="frmName" method="post">
                        <input type="hidden" name="mode" value="search">
                        <table class="table table-bordered table-condensed">
                            <tr>
                                <td width="10%" class="titletd text-center">행사일기준</td>
                                <td width="40%">
                                    <div class="row">
                                        <div class="col-sm-3">
                                            <input type="date" class="form-control input-sm" name="startDate1" value="<?=$startDate1?>" />
                                        </div>
                                        <div class="col-sm-3">
                                            <input type="date" class="form-control input-sm" name="endDate1" value="<?=$endDate1?>" />
                                        </div>
                                        <div class="col-sm-3">
                                            <select class="form-control input-sm" name="guideid">
                                                <option value=''>- 가이드 전체 -</option>
                                                <?php 
                                                  $query ="SELECT userid,kor_name FROM member_list WHERE division ='guide' ";
                                                  $rst1 = mysqli_query($dbConn, $query);
                                                  while($row1 = mysqli_Fetch_assoc($rst1)){
                                                ?>
                                                <option value="<?=$row1['userid']?>" <?php if($guideid == $row1['userid']) echo 'selected'; ?>><?=$row1['kor_name']?></option>
                                                <?php }?>
                                            </select>
                                        </div>    
                                        <div class="col-sm-3">
                                            <button type='submit' class="btn btn-primary btn-sm btn1">검색</button>    
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </form>
                    <br />
                    
                    <div class="row">
                        <div class="col-sm-12">
                            <table name="ctable" id="ctable" class="table table-striped table-bordered table-hover table-condensed js-productTable">
                                <thead>
                                    <tr class="info">
                                        <th>메인코드</th>
                                        <th>서브코드</th>
                                        <th>행사일</th>
                                        <th>행사명</th>
                                        <th>기간</th>
                                        <th>인원</th>
                                        <th>가이드</th>
                                        <th>호텔상태</th>
                                        <th>차량상태</th>
                                        <th width="90">관리</th> 
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php echo printSingle(); ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>                
        </div>
    </div>
    
    <?php include "include/side_m.php" ?>
    
    <script>
        $(document).ready(function () {
            $('#ctable').dataTable({
                stateSave: true,
                pageLength: 100,
                "order": [[ 2, "desc" ], [ 1, "asc" ]] // 행사일 내림차순 후 서브코드 오름차순
            });
			$(".dataTables_length").css({ "display" :"none" });
        })
    </script>
    </body>
</html>