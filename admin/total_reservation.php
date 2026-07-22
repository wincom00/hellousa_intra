
<?php
    include "include/header.php";
	//include "include/inc_base.php";
	if ($_COOKIE[MEMLOGIN_ADMIN_HELLO] != "") {
	} else {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}
   
	if ($estimateCode) {
		$crev =$estimateCode;

	}
	if ($startDate1 == "") {
		$startDate1 =  date("Y-m-d",strtotime("now"));
		$endDate1 = date("Y-m-d",strtotime("+1 week"));

	}
	
?>
	<div id="contentwrapper" class="productDetailForm">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb 
			module">
				<ul>
					<li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li>
					<li><a href="#">통합예약검색</a></li>
					
				</ul>
			</div>
			<div class="row">
				<div class="col-sm-12 col-md-12">
					<form  enctype="multipart/form-data" name="base_code" id="base_code" method="post" >
						<input type="hidden" name="mode" value="search">
						<table class="table table-bordered table-condensed">
						    
							<tr>
								<td width="10%" class="titletd text-center">예약자/여행자 명</td>
								<td width="40%" class="">
									<input type="text" id="cname" name="cname" class="inpubase" value="<?=$cname?>"/>
								</td>
								<td width="10%" class="titletd text-center">전화번호</td>
								<td width="40%" class="">
									<input type="text" id="ctel" name="ctel" class="inpubase" value="<?=$ctel?>"/>
								</td>
							</tr>
							<tr>
								
								<td width="10%" class="titletd text-center">예약번호</td>
								<td width="40%" class="">
									<input type="text" id="crev" name="crev" class="inpubase" value="<?=$crev?>"/>
								</td>
								<td width="10%" class="titletd text-center">이메일</td>
								<td width="40%" class=""><input type="text" id="cemail" name="cemail" class="inpubase" value="<?=$cemail?>" autocomplete=off /></td>
							</tr>
							<tr>
								<td width="10%" class="titletd text-center">투어분류</td>
								<td width="40%" class="">
									<select class="form-control" name="tourCategory">
										<option value="" >- 투어분류 선택하세요 -</option>
										<option value="1" <?php if ($tourCategory==1) { ?> selected <?php } ?> >로컬상품</option>
										<option value="2" <?php if ($tourCategory==2) { ?> selected <?php } ?>>인바운드</option>
										<option value="4" <?php if ($tourCategory==4) { ?> selected <?php } ?>>인센티브</option>
										<option value="5" <?php if ($tourCategory==5) { ?> selected <?php } ?>>아웃바운드</option>
									</select>
								</td>
								<td width="10%" class="titletd text-center">접수상태</td>
								<td width="40%" class="">
									<select class="form-control" name="rstatus">
										<option value="" >- 접수상태를 선택하세요 -</option>
										<option value="READY" <?php if ($rstatus=='READY') { ?> selected <?php } ?> >예약접수</option>
										
										<option value="DONE" <?php if ($rstatus=='DONE') { ?> selected <?php } ?>>예약확정</option>
										
										<option value='CANCEL' <?php if ($rstatus=='CANCEL') { ?> selected <?php } ?>>예약취소</option>
									</select>
								</td>
								
							</tr>
							<tr>
								<td width="10%" class="titletd text-center">결제상태</td>
								<td width="40%" class="">
									<select class="form-control" name="tourpay">
										<option value="" >- 결제상태를 선택하세요 -</option>
										<option value="READY" <?php if ($tourpay=='READY') { ?> selected <?php } ?> >미납</option>
										<option value="PPAY" <?php if ($tourpay=='PPAY') { ?> selected <?php } ?>>부분완납</option>
										
										<option value="DONE" <?php if ($tourpay=='DONE') { ?> selected <?php } ?>>완납</option>
										<option value="OPAY" <?php if ($tourpay=='OPAY') { ?> selected <?php } ?>>환불</option>
									</select>
								</td>
								<td width="10%" class="titletd text-center">출발일</td>
								<td width="40%" class="">
									<div class="row">
									    <div class="col-sm-5">
											<label class="radio-inline">
												<input type="radio" name="kinddate" value="1" <?php if(strstr($kinddate,"1")) echo "checked"; ?>> 출발일
											</label>
											<label class="radio-inline">
												<input type="radio" name="kinddate" value="2" <?php if(strstr($kinddate,"2")) echo "checked"; ?>> 접수일
											</label>
											<label class="radio-inline">
												<input type="radio" name="kinddate" value="" <?php if($kinddate=="") echo "checked"; ?>> 미지정
											</label>
										</div>
										<div class="col-sm-3">
											<input type="search" id="startDate1" name="startDate1" class="inpubase tourDate1" placeholder="시작일" value="<?=$startDate1?>" autocomplete="off" />
										</div>
										<div class="col-sm-3">
											<input type="search" id="endDate1" name="endDate1" class="inpubase tourDate2" placeholder="마지막일" value="<?=$endDate1?>" autocomplete="off" />
										</div>
										
									</div>
								</td>
							</tr>
							
							<tr>
								<td colspan="4" class="text-center"><button type='submit' class="btn btn-primary btn-sm btn1">검색</button></td>
							</tr>
						</table>

						
					</form>

					<br />
					<div class="row">
						<div class="col-sm-12">
							<table class="table table-striped table-bordered table-hover table-condensed js-revTable">
								<thead>
								   <!--
									<tr>
										<th>투어분류</th>
										<th>대표예약번호</th>
										<th>예약번호</th>
										<th>상품명</th>
										<th>예약자</th>
										<th>최종결제금액</th>
										<th>잔액</th>
										<th>상품소유사</th>
										<th>출발일</th>
										<th>접수일</th>
										<th>최종수정일</th>
										<th>접수상태</th>
										<th>담당자</th>
									</tr>
									-->
									<tr>
									    <th>예약경로</th>
										<th>투어분류</th>
										<th>대표예약번호</th>
										<th>예약번호</th>
										<th>접수일</th>
										<th>소유사</th>
										<th>상품명</th>
										<th>출발일</th>
										<th>예약자명</th>
										<th>인원</th>
										<th>접수상태</th>
										<th>결제상태</th>
										<th>최종수정</th>
										<th>수정일</th>
										
									</tr>
								</thead>
								<tbody>
									
								</tbody>
							</table>
						</div>
					</div>
				 </form>	
				</div><!-- -->
			</div>                
		</div>

	</div>
    <?php
		include "include/side_m.php"
	?>
    <script>
		$(function () {
		  // datepicker는 유지
		  $('.tourDate1, .tourDate2').datepicker({ format: "yyyy-mm-dd", autoclose: true });

		  // 동기 Ajax 전역설정 제거
		  // $.ajaxSetup({async:false});

		  // 폼 submit 시 DataTable 다시 로드
		  const $form = $('#base_code');

		  const table = $('.js-revTable').DataTable({
			processing: true,
			serverSide: true,
			pageLength: 50,
			lengthMenu: [25, 50, 100],
			deferRender: true,
			stateSave: true,
			searchDelay: 400,
			order: [[4, 'desc']], // 접수일 기본 내림차순
			dom: 'Bfrtip',
			buttons: ['copy', 'csv', 'excel', 'print'],
			ajax: {
			  url: 'alltotprocess.php',   // ← 2)에서 제공하는 새 PHP
			  type: 'POST',
			  data: function (d) {
				// 폼의 현재 값들을 DataTables 파라미터(d)에 병합
				$form.serializeArray().forEach(({name, value}) => d[name] = value);
				d.division = '<?= $division ?>';
				d.pdx = '<?= $pdx ?>';
				d.sub = '<?= $sub ?>';
				d.ty = '<?= $ty ?>';
			  }
			},
			columns: [
			  { data: 'route', className: 'tcenter' },          // 예약경로(단일/복합)
			  { data: 'tour_type_label', className: 'tcenter' },// 투어분류 라벨
			  { data: 'grand_revNo', className: 'tcenter', render: dtLink },
			  { data: 'reserveCode', className: 'tcenter', render: dtLink },
			  { data: 'revDate', className: 'tcenter' },
			  { data: 'owner', className: 'tcenter' },          // 소유사
			  { data: 'p_name', className: 'tleft' },
			  { data: 'stDate_fmt', className: 'tleft' },       // 요일 포함 포맷
			  { data: 'book_pri', className: 'tcenter' },
			  { data: 'p_cnt', className: 'tcenter' },
			  { data: 'rev_status_label', className: 'tcenter' },
			  { data: 'payment_st_label', className: 'tcenter' },
			  { data: 'muser', className: 'tcenter' },          // 담당자명
			  { data: 'wdate', className: 'tcenter' }
			]
		  });

		  $form.on('submit', function (e) {
			e.preventDefault();
			table.ajax.reload();
		  });

		  function dtLink(data, type, row) {
			if (type !== 'display') return data;
			return `<a href="${row.detail_url}">${data}</a>`;
		  }
		});
		</script>
    </body>
</html>
