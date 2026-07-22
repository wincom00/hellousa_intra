<?php
    // -------------------------------------------------------------------------
    // [중요] 세션 시작 확인 (header.php보다 먼저 실행하여 안전 확보)
    // -------------------------------------------------------------------------
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

	include "include/header.php";

    // -------------------------------------------------------------------------
    // [기능 수정] 검색 조건 유지 로직 (강력한 버전)
    // -------------------------------------------------------------------------
    // 1. 검색 버튼을 눌러서 들어온 경우 (POST 'search')
    if (isset($_POST['mode']) && $_POST['mode'] == 'search') {
        $_SESSION['ses_guide_settle_search'] = $_POST;
    }
    // 2. 상세 페이지에서 저장 후 돌아온 경우 (POST 값 없음, 세션 있음)
    elseif (empty($_POST) && isset($_SESSION['ses_guide_settle_search'])) {
        $_POST = $_SESSION['ses_guide_settle_search'];
        
        // [핵심] 복구된 $_POST 데이터를 실제 변수($startDate1, $guide_kw 등)로 강제 변환
        // 이렇게 해야 아래쪽의 if($startDate1 == "") 초기화 로직을 건너뛸 수 있습니다.
        extract($_POST); 
    }
	
    // -------------------------------------------------------------------------

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

    // 날짜 변수가 복구되지 않았을 때만 기본값(최근 7일) 설정
	if ($startDate1 == "") {
		$startDate1 = date("Y-m-d",strtotime("-7days"));
		$endDate1 = date("Y-m-d",strtotime("+1 month"));
	}

    function printSingle(){

		global $dbConn,$division,$crev,$pdx,$sub,$startDate1,$endDate1,$guideid;

        if ($startDate1) {
            $from_w = " AND a.stDate >= '$startDate1' ";
        }
        if ($endDate1) {
            $to_w = " AND a.stDate <= '$endDate1' ";
        }
		// 파라미터
		$view_type       = trim($_POST['view_type'] ?? '');      // 상태용 (선택했을 때만 적용)
		$guide_kw        = trim($_POST['guide_kw'] ?? '');       // 항상 적용 (있으면)
		$settle_code_kw  = trim($_POST['settle_code_kw'] ?? ''); // 항상 적용 (있으면)
		$pname_kw        = trim($_POST['pname_kw'] ?? '');       // 항상 적용 (있으면)
		$order_by        = trim($_POST['order_by'] ?? 'stDate_desc');

		// 선택적으로 쓰는 날짜들: 출발일자 범위 우선, 없으면 행사일 범위
		$dep_from        = trim($_POST['dep_from'] ?? '');
		$dep_to          = trim($_POST['dep_to'] ?? '');
		$conds = [];

		// 1) 날짜 범위
		if ($dep_from !== '' || $dep_to !== '') {
			if ($dep_from !== '') $conds[] = "a.stDate >= '".$dbConn->real_escape_string($dep_from)."'";
			if ($dep_to   !== '') $conds[] = "a.stDate <= '".$dbConn->real_escape_string($dep_to)."'";
		} else {
			if (!empty($startDate1)) $conds[] = "a.stDate >= '".$dbConn->real_escape_string($startDate1)."'";
			if (!empty($endDate1))   $conds[] = "a.stDate <= '".$dbConn->real_escape_string($endDate1)."'";
		}

		// 2) 상태 필터: view_type을 고른 경우에만 적용
		switch ($view_type) {
		  case 'unchecked':      // 체크 미완료
			$conds[] = "(gsm.check_out IS NULL OR gsm.check_out <> 'V')";
			break;
		  case 'finance_done':   // 회계확인 완료
			$conds[] = "(gsm.finance_st IS NOT NULL AND gsm.finance_st <> '' AND gsm.finance_date IS NOT NULL)";
			break;
		  case 'report_missing': // 가이드 보고 미제출
			$conds[] = "(gsm.report_st IS NULL OR gsm.report_st = '' OR gsm.report_date IS NULL)";
			break;
		  // 'my_guide'는 굳이 선택 안 해도 guide_kw가 있으면 아래 3)에서 필터됨
		}

		// 3) 키워드 필터: 값이 있으면 항상 적용
		if ($guide_kw !== '') {
		  $kw = $dbConn->real_escape_string($guide_kw);
		  $conds[] = "(a.guide_id LIKE '%{$kw}%' OR ml.kor_name LIKE '%{$kw}%')";
		}
		if ($settle_code_kw !== '') {
		  $kw = $dbConn->real_escape_string($settle_code_kw);
		  $conds[] = "EXISTS (
			  SELECT 1
				FROM guide_setmaster gsm_search
			   WHERE gsm_search.grand_eCode = a.grand_eCode
				 AND gsm_search.sub_eCode = a.sub_eCode
				 AND gsm_search.settle_code LIKE '%{$kw}%'
			)";
		}
		if ($pname_kw !== '') {
		  $kw = $dbConn->real_escape_string($pname_kw);
		  $conds[] = "a.p_name LIKE '%{$kw}%'";
		}

		// 4) 공통 제외 조건
		$conds[] = "a.p_code NOT LIKE 'ADD%'";

		// 5) 정렬
		$orderby = "a.stDate DESC";
		if ($order_by==='stDate_asc') $orderby = "a.stDate ASC";
		if ($order_by==='guide_asc')  $orderby = "ml.kor_name ASC, a.stDate DESC";
		if ($order_by==='pcode_asc')  $orderby = "gsm.settle_code ASC, a.stDate DESC"; // 정산코드 순

		// 6) WHERE + 최종 쿼리 (EXISTS 사용으로 타임아웃 방지)
		$where = !empty($conds) ? "WHERE ".implode(" AND ", $conds) : "";

		$query = "
		  SELECT
			a.seq_no, a.grand_eCode, a.sub_eCode, a.stDate, a.guide_id,
			a.p_code, a.p_name,
			gsm.settle_code, gsm.reg_status, gsm.finance_st, gsm.finance_date,
			gsm.report_st, gsm.report_date, gsm.check_out, gsm.check_date, gsm.ceo_st,
			ml.kor_name
		  FROM tour_guide a
		  LEFT JOIN guide_setmaster gsm
			ON gsm.seq_no = (
			  SELECT gsm_pick.seq_no
				FROM guide_setmaster gsm_pick
			   WHERE gsm_pick.grand_eCode = a.grand_eCode
				 AND gsm_pick.sub_eCode = a.sub_eCode
			   ORDER BY
				 CASE WHEN gsm_pick.finance_st <> '' THEN 1 ELSE 0 END DESC,
				 CASE WHEN gsm_pick.report_date IS NOT NULL AND gsm_pick.report_date <> '0000-00-00 00:00:00' THEN 1 ELSE 0 END DESC,
				 CASE WHEN gsm_pick.reg_status = 'COMPLETE' THEN 1 ELSE 0 END DESC,
				 CASE WHEN gsm_pick.check_out = 'V' THEN 1 ELSE 0 END DESC,
				 gsm_pick.seq_no DESC
			   LIMIT 1
			)
		  LEFT JOIN member_list ml
			ON ml.userid = a.guide_id
		  $where
			AND EXISTS (
			  SELECT 1
				FROM tour_master b
			   WHERE b.grand_eCode = a.grand_eCode
				 AND b.p_code      = a.p_code
			)
		  ORDER BY $orderby
		";
		$rst1 = $dbConn->query($query);


		while($row1 = $rst1->fetch_assoc()){
			 //가이드 정산코드
			 $guide_code = array(
				 'settle_code' => $row1['settle_code'],
				 'finance_date' => $row1['finance_date'],
				 'check_out' => $row1['check_out'],
				 'check_date' => $row1['check_date'],
				 'report_date' => $row1['report_date']
			 );
		/// echo $guide_code[settle_code]."TEST";
		//	 exit;
             //행사인원
			 $p_cnt = getReserveInfoCnt($row1['p_code'],$row1['stDate']);

			 //행사기간
			 $period = getPeriodbyrev($row1['p_code'],$row1['stDate']);

			 //행사코드
			 $grandCode = $row1['grand_eCode']." <br/><font color='red'>".$row1['sub_eCode'].'</font>';

			 //상태
			 if ($row1['settle_code'] != '') {
				 if($row1['reg_status'] == 'COMPLETE') $status = '&#51221;&#49328;&#48372;&#44256;&#50756;&#47308;';
				 else if($row1['reg_status'] == 'DONE') $status = '&#46321;&#47197;';
				 else $status = '&#48120;&#46321;&#47197;';

				 if ($row1['finance_st']!="") {
					 $status = "<font color=red>&#54924;&#44228;&#54869;&#51064;</font>";
				 }elseif ($row1['ceo_st']!="") {
					 $status = "<font color=blue>&#45824;&#54364;&#51060;&#49324;&#54869;&#51064;</font>";
				 }elseif ($row1['check_out']!="") {
					 $status = "<font color=blue>&#52404;&#53356;&#45208;&#44048;<br />".$row1['check_date']."</font>";
				 }
			 } else {
				 $status = '&#48120;&#46321;&#47197;';
			 }
			 //가이드정보
			 $korname = getinfo_dbMember($row1['guide_id']);

			 // check_out 및 check_date 값 가져오기
             $check_out = $guide_code['check_out'];
             $check_date = $guide_code['check_date'];

            echo "<tr>
                 <td align='center'><a href='guide_cal_m.php?division=6&pdx=2&sub=10&number=$row1[seq_no]&scode=$guide_code[settle_code]'>$guide_code[settle_code]</a></td>
				 <td align='center'><a href='guide_cal_m.php?division=6&pdx=2&sub=10&number=$row1[seq_no]&scode=$guide_code[settle_code]'>$grandCode</a></td>
				 <td align='center'><a href='guide_cal_m.php?division=6&pdx=2&sub=10&number=$row1[seq_no]&scode=$guide_code[settle_code]'>$row1[stDate]</a></td>
				 <td align='center'><a href='guide_cal_m.php?division=6&pdx=2&sub=10&number=$row1[seq_no]&scode=$guide_code[settle_code]'>$row1[p_name]</a></td>
				 <td align='center'><a href='guide_cal_m.php?division=6&pdx=2&sub=10&number=$row1[seq_no]&scode=$guide_code[settle_code]'>$period</a></td>
				 <td align='center'><a href='guide_cal_m.php?division=6&pdx=2&sub=10&number=$row1[seq_no]&scode=$guide_code[settle_code]'>$p_cnt[cnt]</a></td>
				 <td align='center'><a href='guide_cal_m.php?division=6&pdx=2&sub=10&number=$row1[seq_no]&scode=$guide_code[settle_code]'>$korname[kor_name]</a></td>
				 <td align='center'><a href='guide_cal_m.php?division=6&pdx=2&sub=10&number=$row1[seq_no]&scode=$guide_code[settle_code]'>$guide_code[report_date]</a></td>
				 <td align='center'><a href='guide_cal_m.php?division=6&pdx=2&sub=10&number=$row1[seq_no]&scode=$guide_code[settle_code]'>$guide_code[finance_date]</a></td>
				 <td align='center' class='check-date-cell'>";
                 if ($check_out != 'V') {
				 echo "<button type='button' class='btn btn-info btn-sm check-button' data-seq-no='$row1[seq_no]'>확인 완료</button>";
                 } else { 
                    echo $check_date;
                 } 
				 echo "
                 </td>
				 <td align='center'><a href='guide_cal_m.php?division=6&pdx=2&sub=10&number=$row1[seq_no]&scode=$guide_code[settle_code]'>$status</a></td>
                 
                 </tr>";
        }
	}

?>
	<div id="contentwrapper" class="reservationDetailForm">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb
			module">
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
						<table class="table table-bordered table-condensed">
							<tr>
							  <td width="10%" class="titletd text-center">조회조건</td>
							  <td>
								<div class="row" style="row-gap:8px;">
								  <div class="col-sm-3">
									<label class="control-label" style="font-weight:600;">행사일(From)</label>
									<div class="input-group input-group-sm">
									  <input type="date" class="form-control" id="startDate1" name="startDate1"
											 max="2999-12-31" value="<?=$startDate1?>" autocomplete="off" />
									</div>
								  </div>
								  <div class="col-sm-3">
									<label class="control-label" style="font-weight:600;">행사일(To)</label>
									<div class="input-group input-group-sm">
									  <input type="date" class="form-control" id="endDate1" name="endDate1"
											 max="2999-12-31" value="<?=$endDate1?>" autocomplete="off" />
									</div>
								  </div>
								  <div class="col-sm-6">
									<label class="control-label" style="opacity:.75;">빠른 선택</label>
									<div class="btn-group btn-group-sm" role="group" id="quickRange" style="display:block;">
									  <button type="button" class="btn btn-default" data-range="7d">최근 7일</button>
									  <button type="button" class="btn btn-default" data-range="30d">최근 30일</button>
									  <button type="button" class="btn btn-default" data-range="thism">이번 달</button>
									  <button type="button" class="btn btn-default" data-range="nextm">다음 달</button>
									  <button type="button" class="btn btn-default" data-range="clear">지우기</button>
									</div>
								  </div>
								</div>

								<div class="row" style="margin-top:10px; row-gap:8px;">
								  <div class="col-sm-3">
									<label class="control-label" style="font-weight:600;">조회유형</label>
									<select name="view_type" id="view_type" class="form-control input-sm">
									  <option value="" <?=($_POST['view_type']??'')===''?'selected':''?>>전체</option>
									  <option value="unchecked" <?=($_POST['view_type']??'')==='unchecked'?'selected':''?>>체크 미완료(미확인)</option>
									  <option value="finance_done" <?=($_POST['view_type']??'')==='finance_done'?'selected':''?>>회계확인 완료</option>
									  <option value="report_missing" <?=($_POST['view_type']??'')==='report_missing'?'selected':''?>>가이드 보고 미제출</option>
									  <option value="my_guide" <?=($_POST['view_type']??'')==='my_guide'?'selected':''?>>특정 가이드</option>
									</select>
								  </div>
								  <div class="col-sm-3">
									<label class="control-label" style="font-weight:600;">정렬</label>
									<?php $ob = $_POST['order_by'] ?? 'stDate_desc'; ?>
									<select name="order_by" class="form-control input-sm">
									  <option value="stDate_desc" <?=$ob==='stDate_desc'?'selected':''?>>행사일 ↓</option>
									  <option value="stDate_asc"  <?=$ob==='stDate_asc'?'selected':''?>>행사일 ↑</option>
									  <option value="pcode_asc"   <?=$ob==='pcode_asc'?'selected':''?>>정산코드</option>
									  <option value="guide_asc"   <?=$ob==='guide_asc'?'selected':''?>>가이드명</option>
									</select>
								  </div>
								  <div class="col-sm-3">
									<label class="control-label" style="font-weight:600;">가이드ID/이름</label>
									<input type="text" class="form-control input-sm" name="guide_kw"
										   value="<?=htmlspecialchars($_POST['guide_kw']??'')?>" placeholder="예: honggildong">
								  </div>
								  <div class="col-sm-3">
									<label class="control-label" style="font-weight:600;">정산코드</label>
									<input type="text" class="form-control input-sm" name="settle_code_kw"
										   value="<?=htmlspecialchars($_POST['settle_code_kw']??'')?>" placeholder="예: GU-101512">
								  </div>
								</div>

								<div class="row" style="margin-top:10px;">
								  <div class="col-sm-3">
									<label class="control-label" style="font-weight:600;">상품명</label>
									<input type="text" class="form-control input-sm" name="pname_kw"
										   value="<?=htmlspecialchars($_POST['pname_kw']??'')?>" placeholder="상품명 키워드">
								  </div>
								  <div class="col-sm-3" style="padding-top:22px;">
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
							<table id='ctable' class="table table-striped table-bordered table-hover table-condensed js-productTable">
								<thead>
									<tr>
									    <th>가이드정산코드</th>
										<th>행사코드</th>
										<th>행사일</th>
										<th>행사명</th>
										<th>행사기간</th>
										<th>행사인원</th>
										<th>가이드명</th>
										<th>기이드제출날짜</th>
										<th>회계확인날짜</th>
										<th>체크 확인일</th>
										<th>상태</th>
										
										</tr>
								</thead>
								<tbody>
									<?php  echo printSingle(); ?>

								</tbody>
							</table>
						</div>
					</div>
				</div></div>
		</div>

	</div>
    <?php
		include "include/side_m.php"
	?>
    <script>
		$(document).ready(function () {

            var dateToday = new Date()

			var oTable = $('#ctable').dataTable({
				stateSave: true,
				pageLength: 100,
				"order": [[ 2, "asc" ]]
			});

			$(".dataTables_length").css({ "display" :"none" });

            // '확인 완료' 버튼 클릭 이벤트 처리
            $('#ctable').on('click', '.check-button', function() {
                var $button = $(this);
                var seqNo = $button.data('seq-no'); // data-seq-no 속성에서 seq_no 가져오기
                var $cell = $button.closest('td'); // 버튼이 속한 셀

                // AJAX 요청을 보낼 서버 스크립트 파일 경로
                var updateUrl = 'update_check.php'; // 이 파일은 새로 생성해야 합니다.

                $.ajax({
                    url: updateUrl,
                    method: 'POST',
                    data: { seq_no: seqNo }, // 서버로 seq_no 전달
                    dataType: 'json', // 서버 응답을 JSON으로 예상
                    success: function(response) {
                        if (response.status === 'success') {
                            // 성공 시 버튼을 제거하고 받은 날짜를 표시
                            $button.remove();
                            $cell.text(response.check_date); // 서버에서 반환된 날짜 표시
                        } else {
                            alert('업데이트 실패: ' + response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('AJAX 오류 발생: ' + status + ' - ' + error);
                        console.error(xhr.responseText); // 상세 오류 로그
                    }
                });
            });
			
			// 빠른 기간 프리셋
			// 행사일 기간 빠른 선택
			$('#quickRange button').on('click', function(){
			  const v = $(this).data('range');
			  const now = new Date();
			  const toISO = d => d.toISOString().slice(0,10);

			  let s = '', e = '';
			  if (v==='7d')   { e = now; const sdt=new Date(now); sdt.setDate(now.getDate()-7); s = sdt; }
			  if (v==='30d')  { e = now; const sdt=new Date(now); sdt.setDate(now.getDate()-30); s = sdt; }
			  if (v==='thism'){ const sdt=new Date(now.getFullYear(),now.getMonth(),1);
								const edt=new Date(now.getFullYear(),now.getMonth()+1,0);
								s=sdt; e=edt; }
			  if (v==='nextm'){ const sdt=new Date(now.getFullYear(),now.getMonth()+1,1);
								const edt=new Date(now.getFullYear(),now.getMonth()+2,0);
								s=sdt; e=edt; }
			  if (v==='clear'){ $('#startDate1, #endDate1').val(''); return; }

			  $('#startDate1').val(toISO(s));
			  $('#endDate1').val(toISO(e));
			});



		})

	</script>
    </body>
</html>
