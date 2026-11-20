
<?php
    include "include/header.php";
	//include "include/inc_base.php";
	if($_COOKIE[MEMLOGIN_ADMIN_HELLO] !="")
	{
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
	$v_info = getinfo_dbHotel_bycode($pcode);
	
	$lvcode2 = substr($v_info[p_typem],3,2);

	// ── 기간별 요금(hotel_rate) — 저장된 호텔에서만, 호텔정산 자동계산에 사용 ──
	// inc_base 가 요청값을 extract 하므로 변수는 hr_ 접두사, 입력은 $_POST 로 명시적으로 읽는다 (호텔 저장 mode 와 분리)
	$hr_on = hotelRateReady() && $v_info['h_code'] != '';
	$hr_self = "hotel_regi_m.php?division=".urlencode($division)."&pdx=".urlencode($pdx)."&sub=".urlencode($sub)."&pcode=".urlencode($v_info['h_code']);
	$hr_items = array('ROOM' => '객실요금');
	if ($hr_on) {
		$hr_rst = getEtcCostSelect();
		while ($hr_e = $hr_rst->fetch_assoc()) $hr_items[$hr_e['lvcode2'].$hr_e['lvcode3']] = $hr_e['comment'];

		$hr_mode = isset($_POST['hr_mode']) ? $_POST['hr_mode'] : '';
		$hr_hcode = $dbConn->real_escape_string($v_info['h_code']);
		if ($hr_mode == 'save') {
			$p_seq   = (int)$_POST['hr_seq'];
			$p_item  = trim($_POST['hr_item']);
			$p_sdate = trim($_POST['hr_sdate']);
			$p_edate = trim($_POST['hr_edate']);
			$p_rate  = str_replace(',', '', trim($_POST['hr_rate']));
			$hr_err = '';
			if (!isset($hr_items[$p_item])) $hr_err = '항목을 선택하세요.';
			else if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $p_sdate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $p_edate)) $hr_err = '기간을 입력하세요.';
			else if ($p_sdate > $p_edate) $hr_err = '시작일이 종료일보다 늦습니다.';
			else if (!is_numeric($p_rate) || $p_rate < 0) $hr_err = 'RATE는 0 이상 숫자로 입력하세요.';

			if ($hr_err != '') {
				Misc::jvAlert($hr_err, "");
			} else {
				$q_item   = $dbConn->real_escape_string($p_item);
				$q_season = $dbConn->real_escape_string(trim($_POST['hr_season']));
				$q_memo   = $dbConn->real_escape_string(trim($_POST['hr_memo']));
				$q_rate   = (float)$p_rate;
				$q_user   = $dbConn->real_escape_string($user_dbinfo['userid']);
				if ($p_seq > 0) {
					$dbConn->query("UPDATE hotel_rate SET item_code='$q_item', season_name='$q_season', start_date='$p_sdate', end_date='$p_edate',
						rate='$q_rate', memo='$q_memo', userid='$q_user', wdate=now() WHERE seq_no=$p_seq AND h_code='$hr_hcode'");
				} else {
					$dbConn->query("INSERT INTO hotel_rate (h_code, item_code, season_name, start_date, end_date, rate, memo, userid, wdate)
						VALUES ('$hr_hcode', '$q_item', '$q_season', '$p_sdate', '$p_edate', '$q_rate', '$q_memo', '$q_user', now())");
				}
				echo "<meta http-equiv='refresh' content='0; url=$hr_self&hr_ok=1#hotelRate'>";   // 주소가 #만 다르면 브라우저가 새로고침하지 않으므로 hr_ok 추가
				exit;
			}
		} else if ($hr_mode == 'del') {
			$p_seq = (int)$_POST['hr_seq'];
			if ($p_seq > 0) $dbConn->query("DELETE FROM hotel_rate WHERE seq_no=$p_seq AND h_code='$hr_hcode'");
			echo "<meta http-equiv='refresh' content='0; url=$hr_self&hr_ok=1#hotelRate'>";
			exit;
		}
		$hr_list = $dbConn->query("SELECT * FROM hotel_rate WHERE h_code='$hr_hcode' ORDER BY item_code='ROOM' DESC, item_code, start_date");
	}

    if ($mode == "save") {
			 $qry1 = "delete  from product_hotel where h_code='$pcode'";
			 $rst1 = $dbConn->query($qry1);
			 $file_name["huserfile"] = "";
			 if ($_FILES["huserfile"]["tmp_name"] <> "")			$file_name["huserfile"] = file_save($_FILES["huserfile"], "./product_img/");
			 if ($pcode == "") {
				$h_num = getHnumber();
				$hcode = "PHOT".$h_num[numChar];
			 }  else {
				$h_num[num] = $v_info[num];
			 }

             $filename = $dbConn->real_escape_string($file_name["huserfile"]);

			 $qry1 = " insert into product_hotel 
														(
														num,
														m_rate, 
														u_type, 
														h_type, 
														p_typem, 
														p_types, 
														h_stype, 
														h_code, 
														h_name,
														h_sname,
														h_grade, 
														hd_price, 
														h_addr, 
														h_room, 
														h_userfile, 
														h_desc, 
														wdate
														)
														values
														(
														'$h_num[num]', 
														'$currency', 
														'$utype', 
														'$htype', 
														'$area1', 
														'$area2', 
														'', 
														'$hcode', 
														'$hname',
														'$hsname',
														'$hgrade', 
														'$hdprice', 
														'$haddr', 
														'$hroom', 
														'$filename', 
														'$hdesc', 
														now()
														)";
			 // echo $qry1;
			 // exit;
			  $rst1 = $dbConn->query($qry1);

			  if($rst1)
			  {
				     Misc::jvAlert("저장했습니다.","");
				     $goUrl_1 = "hotel_regi.php?division=$division&pdx=$pdx&sub=$sub";
					 echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
					 exit;
			  }
			

	} 
	
	

?>
<script src="ckeditor/ckeditor.js"></script>
<div id="contentwrapper" class="HotelDetailForm">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb module">
				<ul>
					<li>
						<a href="/admin"><i class="glyphicon glyphicon-home"></i></a>
					</li>
					<li>
						<a href="#">상품관리</a>
					</li>
					<li>
						<a href="#">상품등록</a>
					</li>
					<li>
						호텔등록
					</li>
				</ul>
			</div>
			
		<div class="row">
				<div class="col-sm-12 col-md-12">
					  <form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&ty=<?=$ty?>&pcode=<?=$pcode?>" name="frmproduct" id="frmproduct" method="post" Enctype="multipart/form-data" onSubmit="return chksave()">
			            <input type=hidden name=mode value="save">
						<input type=hidden name=pcode value="<?= $pcode ?>">
						<input type="hidden" name="currency" value="USD">
						
						<table class="table table-striped table-bordered table-condensed">
						    <tbody>
								    <tr>
										<td colspan=4 height=35 bgcolor=#FFFFFF class="titletd" style="vertical-align: middle;"><input type=submit value="저장" class="btn btn-primary btn-sm"></td>
									</tr> 
									<!--<tr  bgcolor=#f9f9f9 height=28>
									    <td width=15% class="titletd" style="vertical-align: middle;">기준통화</td>
									    <td  colspan="3" bgcolor=#FFFFFF>
												<label class="radio-inline">
													<input type="radio" name="currency" id="currencyCAD" value="CAD" <?php if ($v_info[m_rate] == "CAD") echo "checked"; ?>> CAD
												</label>
												<label class="radio-inline">
													<input type="radio" name="currency" id="currencyUSD" value="USD" <?php if ($v_info[m_rate] == "USD") echo "checked"; ?>> USD
												</label>
										</td>
										
									</tr>-->
									<tr  bgcolor=#f9f9f9 height=28>
									    <td width=15% class="titletd" style="vertical-align: middle;">사용구분</td>
									    <td  colspan="3" bgcolor=#FFFFFF>
												<label class="radio-inline">
													<input type="radio" name="utype" id="utype1" value="1" <?php if ($v_info[u_type] == "1") echo "checked"; ?>> 둘다사용
												</label>
												<label class="radio-inline">
													<input type="radio" name="utype" id="utype2" value="2" <?php if ($v_info[u_type] == "2") echo "checked"; ?>> 투어만사용
												</label>
												<label class="radio-inline">
													<input type="radio" name="utype" id="utype2" value="2" <?php if ($v_info[u_type] == "3") echo "checked"; ?>> FIT전용
												</label>
										</td>
										
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
									    <td width=15% class="titletd" style="vertical-align: middle;">호텔분류</td>
									    <td colspan="3" class="form-inline" bgcolor=#FFFFFF>
											<select class="form-control fst1" name="area1" id="area1">
												<option value="">분류선택1
												<?=printBaseCode_first('T01',$v_info[p_typem])?>
											</select>
											<select class="form-control fst2" name="area2" id="area2">
												<option value="">분류선택2</option>
												<?=printBaseCode_second('T01',$lvcode2,$v_info[p_types])?>
											</select>
											
										</td>
										
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
									    <td width=15% class="titletd" style="vertical-align: middle;">호텔등급<?=$v_info[h_grade]?></td>
									    <td colspan="3" class="form-inline" bgcolor=#FFFFFF>
											<select class="form-control " name="hgrade" id="hgrade">
												<option value="">등급선택</option>
												<option value="5" <?php if ($v_info[h_grade] == "5") echo "selected"; ?>>5성급 호텔</option>
												<option value="4" <?php if ($v_info[h_grade] == "4") echo "selected"; ?>>4성급 호텔</option>
												<option value="3" <?php if ($v_info[h_grade] == "3") echo "selected"; ?>>3성급 호텔</option>
												<option value="2" <?php if ($v_info[h_grade] == "2") echo "selected"; ?>>2성급 호텔</option>
												<option value="1" <?php if ($v_info[h_grade] == "1") echo "selected"; ?>>1성급 호텔</option>
												
												
											</select>
											
											
										</td>
										
									</tr>
									
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% class="titletd">호텔코드</td>
										<td colspan="3" bgcolor=#FFFFFF><input type=text name=hcode  class="inpubase md"  placeholder="자동생성 및 수정가능"  value="<?= $v_info[h_code] ?>"> </td>
										
									</tr>
									
									
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% class="titletd" style="vertical-align: middle;">호텔명</td>
										<td colspan="3" bgcolor=#FFFFFF><input type=text name=hname  class="inpubase lg" value="<?= $v_info[h_name] ?>"></td>
										
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% class="titletd" style="vertical-align: middle;">호텔닉네임</td>
										<td colspan="3" bgcolor=#FFFFFF><input type=text name=hsname  class="inpubase lg" value="<?= $v_info[h_sname] ?>"></td>
										
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% class="titletd" style="vertical-align: middle;">호텔주소</td>
										<td colspan="3" bgcolor=#FFFFFF><input type=text name=haddr  class="inpubase llg" value="<?= $v_info[h_addr] ?>"></td>
										
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% class="titletd" style="vertical-align: middle;">방갯수</td>
										<td  colspan="3" bgcolor=#FFFFFF><input type=text name=hroom  class="inpubase sm1" value="<?= $v_info[h_room] ?>"></td>
										
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% class="titletd" style="vertical-align: middle;">호텔표시용가격</td>
										<td  colspan="3" bgcolor=#FFFFFF><input type=text name=hdprice  class="inpubase md" value="<?= $v_info[hd_price] ?>"></td>
										
									</tr>
									
									<tr>
										<td class="active text-center formHeader">현재호텔이미지</td>
										<td >
											
												 <img width='140px' height='140px' alt="140x140" data-src="product_img/<?= $v_info[h_userfile] ?>" class="img-thumbnail js-placeholderImg" src="product_img/<?= $v_info[h_userfile] ?>" data-holder-rendered="true">
										
										</td>
										<td  class="active text-center formHeader">이미지업로드</td>
										<td >
											<div class="col-sm-6">
												<div class="form-group removeBottomMargin">
													<label class="sr-only" for="huserfile">현재호텔이미지 </label>
													<input type="file" class="form-control" id="huserfile" name="huserfile" placeholder="현재호텔이미지">
												</div>
											</div>
											
										</td>
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% class="titletd" style="vertical-align: middle;">호텔한줄설명</td>
										<td colspan="3" bgcolor=#FFFFFF><input type=text name=hdesc  class="inpubase" value="<?= $v_info[h_desc] ?>"></td>
										
									</tr>
							</tbody>
						</table>
					 </form>

					<?php if ($hr_on) { ?>
					<!-- 기간별 요금: 호텔정산(hotel_cal2 자동채움 / 정산목록 일괄 자동정산)이 숙박일 기준으로 조회 -->
					<a name="hotelRate"></a>
					<form name="frmRate" id="frmRate" method="post" action="<?=htmlspecialchars($hr_self)?>">
						<input type="hidden" name="hr_mode" value="save">
						<input type="hidden" name="hr_seq" id="hr_seq" value="">
						<table class="table table-striped table-bordered table-condensed">
							<tbody>
								<tr>
									<td colspan="2" class="titletd">
										<b>기간별 요금</b>
										<small class="text-muted">&nbsp;호텔정산 자동계산에 사용 · 기간이 겹치면 <b>짧은 기간</b> 우선 (연중 &lt; 성수기 &lt; 특정일) · RATE는 세금포함, 객실요금은 1박 1실</small>
									</td>
								</tr>
								<tr>
									<td width="15%" class="titletd" style="vertical-align: middle;"><span id="hr_title">요금등록</span></td>
									<td bgcolor="#FFFFFF">
										<div class="row">
											<div class="col-sm-2">
												<select class="form-control input-sm" name="hr_item" id="hr_item">
													<?php foreach ($hr_items as $hr_k => $hr_v) { ?>
													<option value="<?=htmlspecialchars($hr_k)?>"><?=htmlspecialchars($hr_v)?></option>
													<?php } ?>
												</select>
											</div>
											<div class="col-sm-3">
												<div class="input-group input-group-sm">
													<input type="date" class="form-control" name="hr_sdate" id="hr_sdate">
													<span class="input-group-addon">~</span>
													<input type="date" class="form-control" name="hr_edate" id="hr_edate">
												</div>
											</div>
											<div class="col-sm-2">
												<input type="text" class="form-control input-sm" name="hr_season" id="hr_season" placeholder="시즌명 (성수기 등)">
											</div>
											<div class="col-sm-2">
												<div class="input-group input-group-sm">
													<span class="input-group-addon">RATE</span>
													<input type="text" class="form-control" name="hr_rate" id="hr_rate">
												</div>
											</div>
											<div class="col-sm-2">
												<input type="text" class="form-control input-sm" name="hr_memo" id="hr_memo" placeholder="메모">
											</div>
											<div class="col-sm-1">
												<button type="submit" class="btn btn-primary btn-sm">저장</button>
												<button type="button" class="btn btn-default btn-sm js-hrReset hidden">취소</button>
											</div>
										</div>
									</td>
								</tr>
							</tbody>
						</table>
					</form>

					<table id="rateTable" class="table table-bordered table-condensed table-hover">
						<thead>
							<tr class="info">
								<th>항목<h6>Item</h6></th>
								<th>시즌<h6>Season</h6></th>
								<th>시작일<h6>From</h6></th>
								<th>종료일<h6>To</h6></th>
								<th class="text-right">RATE<h6>Rate</h6></th>
								<th>메모<h6>Memo</h6></th>
								<th width="90">관리<h6>Action</h6></th>
							</tr>
						</thead>
						<tbody>
						<?php $hr_cnt = 0; while ($hr_r = $hr_list->fetch_assoc()) { $hr_cnt++; ?>
							<tr data-seq="<?=$hr_r['seq_no']?>" data-item="<?=htmlspecialchars($hr_r['item_code'])?>"
								data-sdate="<?=$hr_r['start_date']?>" data-edate="<?=$hr_r['end_date']?>" data-season="<?=htmlspecialchars($hr_r['season_name'])?>"
								data-rate="<?=$hr_r['rate']?>" data-memo="<?=htmlspecialchars($hr_r['memo'])?>">
								<td><?=htmlspecialchars(isset($hr_items[$hr_r['item_code']]) ? $hr_items[$hr_r['item_code']] : $hr_r['item_code'])?></td>
								<td><?=htmlspecialchars($hr_r['season_name'])?></td>
								<td><?=$hr_r['start_date']?></td>
								<td><?=$hr_r['end_date']?></td>
								<td class="text-right"><?=number_format($hr_r['rate'], 2)?></td>
								<td><?=htmlspecialchars($hr_r['memo'])?></td>
								<td class="text-center">
									<button type="button" class="btn btn-default btn-xs js-hrEdit">수정</button>
									<button type="button" class="btn btn-danger btn-xs js-hrDel">삭제</button>
								</td>
							</tr>
						<?php } ?>
						<?php if ($hr_cnt == 0) { ?>
							<tr><td colspan="7" class="text-center text-muted">등록된 요금이 없습니다. 이 호텔은 호텔정산에서 '요금미등록'으로 표시됩니다.</td></tr>
						<?php } ?>
						</tbody>
					</table>

					<form name="frmRateDel" id="frmRateDel" method="post" action="<?=htmlspecialchars($hr_self)?>">
						<input type="hidden" name="hr_mode" value="del">
						<input type="hidden" name="hr_seq" id="hr_delseq" value="">
					</form>
					<?php } else if (hotelRateReady()) { ?>
					<p class="text-muted">호텔을 먼저 저장하면 기간별 요금을 등록할 수 있습니다.</p>
					<?php } ?>

				</div><!-- -->
		</div>                
		</div>
	  </div>

	</div>

    <?php
		include "include/side_m.php"
	?>
    <script>
	   
         $(document).ready(function() {
				//* bootstrap timepicker
		        pt1.inithotelDetailForm();
	     });

		// 기간별 요금 — 수정/취소/삭제/입력확인
		$(document).off('click.hrate').on('click.hrate', '.js-hrEdit', function () {
			var tr = $(this).closest('tr');
			$('#hr_seq').val(tr.data('seq'));
			$('#hr_item').val(String(tr.data('item')));
			$('#hr_sdate').val(tr.data('sdate'));
			$('#hr_edate').val(tr.data('edate'));
			$('#hr_season').val(tr.data('season'));
			$('#hr_rate').val(tr.data('rate'));
			$('#hr_memo').val(tr.data('memo'));
			$('#hr_title').text('요금수정');
			$('.js-hrReset').removeClass('hidden');
			$('#hr_rate').focus();
		});
		$(document).off('click.hrateReset').on('click.hrateReset', '.js-hrReset', function () {
			$('#frmRate')[0].reset();
			$('#hr_seq').val('');
			$('#hr_title').text('요금등록');
			$(this).addClass('hidden');
		});
		$(document).off('click.hrateDel').on('click.hrateDel', '.js-hrDel', function () {
			if (!confirm('삭제하시겠습니까?')) return;
			$('#hr_delseq').val($(this).closest('tr').data('seq'));
			$('#frmRateDel').submit();
		});
		$('#frmRate').on('submit', function () {
			if (!$('#hr_sdate').val() || !$('#hr_edate').val()) { alert('기간을 입력하세요.'); return false; }
			if ($('#hr_sdate').val() > $('#hr_edate').val()) { alert('시작일이 종료일보다 늦습니다.'); return false; }
			if ($('#hr_rate').val() === '' || isNaN($('#hr_rate').val().replace(/,/g, ''))) { alert('RATE를 숫자로 입력하세요.'); return false; }
			return true;
		});
		
		function chksave() {
				  
				  
                  if (($('input:radio[name=utype]').is(':checked'))== false)
                  {
						alert("사용구분을 입력하세요!");
						$('input:radio[name=utype]').focus();
						return false;
                  }
                  if ($("#area1").val() == "") {
						alert("호텔분류 1을 입력하세요!");
						$("#area1").focus();
						return false;
				  }
				  if ($("#area2").val() == "") {
						alert("호텔분류 2를 입력하세요!");
						$("#area2").focus();
						return false;
				  }
				  if ($("#hname").val() == "") {
						alert("호텔명을 입력하세요!");
						$("#hname").focus();
						return false;
				  }
				  
				  
				  return true;

			}
		
		
	</script>

    </body>
</html>

      
      