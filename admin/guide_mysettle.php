<?php
	include "include/header.php";
	
	if ($_COOKIE['MEMLOGIN_ADMIN_HELLO'] != "") {
	} else {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}
    
    // 날짜 초기화
	if ($startDate1 == "") {
		$startDate1 =  date("Y-m-d",strtotime("-7days"));
		$endDate1 = date("Y-m-d",strtotime("+1 month"));
	}

    function printSingle(){
    
        global $dbConn,$division,$crev,$pdx,$sub,$startDate1,$endDate1,$guideid,$user_dbinfo;

        // [중요] 변수 초기화 (Undefined variable 오류 방지)
        $from_w = "";
        $to_w = "";

        if ($startDate1) {
            $from_w = " AND a.stDate >= '$startDate1' ";
        }
        if ($endDate1) {
            $to_w = " AND a.stDate <= '$endDate1' ";
        }
       
        $query = "
          SELECT
            a.seq_no, a.grand_eCode, a.sub_eCode, a.stDate, a.guide_id,
            a.p_code, a.p_name,
            gsm.settle_code, gsm.finance_date, gsm.report_date, gsm.check_out, gsm.check_date,
            ml.kor_name
          FROM tour_guide a
          LEFT JOIN guide_setmaster gsm
            ON gsm.grand_eCode = a.grand_eCode
           AND gsm.sub_eCode   = a.sub_eCode
          LEFT JOIN member_list ml
            ON ml.userid = a.guide_id 
          WHERE 1=1 $from_w $to_w AND a.guide_id = '".$user_dbinfo['userid']."'
            AND EXISTS (
              SELECT 1
                FROM tour_master b
               WHERE b.grand_eCode = a.grand_eCode
                 AND b.p_code      = a.p_code
            )
        ";
        
        // [중요] 쿼리 실행 및 에러 체크
        $rst1 = $dbConn->query($query);
        
        if(!$rst1) {
            // 쿼리 에러 발생 시 디버깅 메시지 (실 운영 시에는 주석 처리 권장)
            echo "<tr><td colspan='8' align='center'>Query Error: " . $dbConn->error . "</td></tr>";
            return;
        }
        
        while($row1 = $rst1->fetch_assoc()){
             //가이드 정산코드
             $guide_code = getGuideCode($row1['grand_eCode'],$row1['sub_eCode']);
             // 배열이 아닐 경우 대비 (PHP 8.0+ 호환성)
             $settle_code = isset($guide_code['settle_code']) ? $guide_code['settle_code'] : '';

             //행사인원
             $p_cnt_arr = getReserveInfoCnt($row1['p_code'],$row1['stDate']);
             $p_cnt = isset($p_cnt_arr['cnt']) ? $p_cnt_arr['cnt'] : 0;

             //행사기간
             $period = getPeriodbyrev($row1['p_code'],$row1['stDate']);
 
             //행사코드
             $grandCode = $row1['grand_eCode']." <br/><font color='red'>".$row1['sub_eCode'].'</font>';

             //상태
             $status = getGuideStatus($row1['grand_eCode'],$row1['sub_eCode']);
             //가이드정보
             $korname_arr = getinfo_dbMember($row1['guide_id']);
             $kor_name = isset($korname_arr['kor_name']) ? $korname_arr['kor_name'] : '';

             // [중요] 문자열 내 배열 접근 시 {} 사용 또는 변수 분리
             $seq_no = $row1['seq_no'];
             $stDate = $row1['stDate'];
             $p_name = $row1['p_name'];

             echo "<tr>
                 <td align='center'><a href='guide_cal_my.php?division=6&pdx=2&sub=15&number={$seq_no}&scode={$settle_code}'>{$settle_code}</a></td>
                 <td align='center'><a href='guide_cal_my.php?division=6&pdx=2&sub=15&number={$seq_no}&scode={$settle_code}'>{$grandCode}</a></td>
                 <td align='center'><a href='guide_cal_my.php?division=6&pdx=2&sub=15&number={$seq_no}&scode={$settle_code}'>{$stDate}</a></td>
                 <td align='center'><a href='guide_cal_my.php?division=6&pdx=2&sub=15&number={$seq_no}&scode={$settle_code}'>{$p_name}</a></td>
                 <td align='center'><a href='guide_cal_my.php?division=6&pdx=2&sub=15&number={$seq_no}&scode={$settle_code}'>{$period}</a></td>
                 <td align='center'><a href='guide_cal_my.php?division=6&pdx=2&sub=15&number={$seq_no}&scode={$settle_code}'>{$p_cnt}</a></td>
                 <td align='center'><a href='guide_cal_my.php?division=6&pdx=2&sub=15&number={$seq_no}&scode={$settle_code}'>{$kor_name}</a></td>
                 <td align='center'><a href='guide_cal_my.php?division=6&pdx=2&sub=15&number={$seq_no}&scode={$settle_code}'>{$status}</a></td>
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
                <li>가이드정산등록</li>
            </ul>
        </div>
        <div class="row">
            <div class="col-sm-12 col-md-12">
                <form action="" name="frmName"  method="post">
                    <input type="hidden" name="mode" value="search">
                    <!-- 검색 폼 UI 개선 -->
                    <div class="well well-sm">
                        <table class="table-condensed" style="width:100%">
                            <tr>
                                <td width="80" style="font-weight:bold;"><i class="glyphicon glyphicon-calendar"></i> 행사일</td>
                                <td>
                                    <div class="form-inline">
                                        <div class="input-group">
                                            <input type="date" class="form-control input-sm" id="startDate1" name="startDate1" value="<?=$startDate1?>" style="width:130px;">
                                            <span class="input-group-addon">~</span>
                                            <input type="date" class="form-control input-sm" id="endDate1" name="endDate1" value="<?=$endDate1?>" style="width:130px;">
                                        </div>
                                        <button type='submit' class="btn btn-primary btn-sm"><i class="glyphicon glyphicon-search"></i> 검색</button>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>
                </form>
                
                <div class="row">
                    <div class="col-sm-12">
                        <!-- Table ID 추가 및 DataTables 연결 -->
                        <table id="guide_settlement_table" class="table table-striped table-bordered table-hover table-condensed js-productTable">
                            <thead>
                                <tr class="active">
                                    <th class="text-center">가이드정산코드</th>
                                    <th class="text-center">행사코드</th>
                                    <th class="text-center">행사일</th>
                                    <th class="text-center">행사명</th>
                                    <th class="text-center">행사기간</th>
                                    <th class="text-center">행사인원</th>
                                    <th class="text-center">가이드명</th>
                                    <th class="text-center">상태</th>
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
<?php
    include "include/side_m.php"
?>
<script>
    $(document).ready(function () {
        
        // DataTables 설정
        // 테이블 ID(#guide_settlement_table)를 정확히 타겟팅하여 정렬 기능을 활성화합니다.
        var oTable = $('#guide_settlement_table').dataTable({
            stateSave: true,
            pageLength: 100,
            "order": [[ 2, "desc" ]], // 기본 정렬: 행사일(3번째 컬럼) 내림차순
            "columnDefs": [
                { "targets": [0, 1, 2, 3, 4, 5, 6, 7], "orderable": true } // 모든 컬럼 정렬 가능
            ],
            "language": {
                "emptyTable": "데이터가 없습니다.",
                "lengthMenu": "페이지당 _MENU_ 개씩 보기",
                "info": "현재 _START_ - _END_ / _TOTAL_건",
                "infoEmpty": "데이터 없음",
                "infoFiltered": "( _MAX_건의 데이터에서 필터링됨 )",
                "search": "필터링:",
                "zeroRecords": "일치하는 데이터가 없습니다.",
                "loadingRecords": "로딩중...",
                "processing":     "잠시만 기다려 주세요...",
                "paginate": {
                    "next": "다음",
                    "previous": "이전"
                }
            }
        });

        // 기본 Length Select 숨김 (필요시 주석 해제)
        $(".dataTables_length").css({ "display" :"none" });
    })
</script>
</body>
</html>