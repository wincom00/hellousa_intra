<?php
   include "include/header.php";
   // include "include/inc_base.php";
	if ($_COOKIE['MEMLOGIN_ADMIN_HELLO'] !="") {
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
	if ($mode == "save") {
		$eventcnt = count($bbnum);
		
		//echo $eventcnt;
		//exit;
		
		for($r=0;$r<$eventcnt;$r++)
		{
			$qry1 = "delete from tour_guide where grand_eCode= '$gcode' && sub_eCode='$ssnum[$r]'  && stDate= '$sdate' ";
			$rst1 = $dbConn->query($qry1);

			if ($guideName[$r] !="") {
				$qry2 ="insert into tour_guide 
												( 
												grand_eCode, 
												sub_eCode, 
												p_code, 
												p_name, 
												stDate, 
												bus_num, 
												guide_id, 
												sguide_id, 
												g_email, 
												g_tel, 
												pre_amt,
												c_id, 
												c_tel,
												c_type,
												c_memo,
												d_nm,
												d_tel,
												d_memo,
												userid, 
												wdate
												)
												values
												(
												'$gcode', 
												'$ssnum[$r]', 
												'$pcode', 
												'$pname', 
												'$sdate', 
												'$bbnum[$r]', 
												'$guideName[$r]', 
												'$sguideName[$r]', 
												'$guideEmail[$r]', 
												'$guideTelephone[$r]', 
												'$guidePreCost[$r]', 
												'$cName[$r]', 
												'$carTelephone[$r]',
												'$cartype[$r]',
												'$carmemo[$r]',
												'$driver[$r]',
												'$dTelephone[$r]',
												'$dmemo[$r]',
												'$user_dbinfo[userid]', 
												now()
												)";
				//echo $qry2;
				//exit;
				$rst2 = $dbConn->query($qry2);
			}
		}
		Misc::jvAlert("업데이트 되었습니다!!","");
	}
	$sctour = getTourInfo2($pcode,$st);
	$pcnt = getReserveInfoCnt($pcode,$st);
	if ($pcnt['cnt'] =="") {
		$pcnt['cnt'] = 0;
	}
    $pInfo = getProductMaster($pcode);

	//가이드 이름 추가 (콤마/+ 구분 다중 처리, 중복 제거)
	if (!function_exists('gamAppendGuideNameById')) {
		function gamAppendGuideNameById(&$names, $memberId)
		{
			$memberId = trim((string)$memberId);
			if ($memberId == "") {
				return;
			}
			$info = getinfo_dbMemberg($memberId);
			$nm = "";
			if ($info) {
				$nm = ($info['kor_name'] != "") ? $info['kor_name'] : $info['eng_name'];
			}
			if ($nm == "") {
				$nm = $memberId;
			}
			foreach (explode("+", $nm) as $onePart) {
				$onePart = trim($onePart);
				if ($onePart != "" && !in_array($onePart, $names, true)) {
					$names[] = $onePart;
				}
			}
		}
	}

	//메인가이드상품(m_guidechk='V')의 배정 가이드 이름 수집 (assign_m.php 히스토리와 동일 기준)
	if (!function_exists('gamAppendMainGuideNamesForProduct')) {
		function gamAppendMainGuideNamesForProduct(&$names, $grandECode, $stDate, $pCode, $subECode = "", $busNum = "")
		{
			global $dbConn;

			$safeGrand = $dbConn->real_escape_string($grandECode);
			$safeDate = $dbConn->real_escape_string($stDate);
			$safePCode = $dbConn->real_escape_string($pCode);
			$safeSub = $dbConn->real_escape_string($subECode);
			$safeBus = $dbConn->real_escape_string($busNum);

			$qry = "select a.guide_id, a.sguide_id
					from tour_guide a
					inner join product_master b on a.p_code = b.p_code
					where a.grand_eCode = '$safeGrand'
					  and a.stDate = '$safeDate'
					  and a.p_code = '$safePCode'
					  and b.m_guidechk = 'V'";
			if ($safeSub != "") {
				$qry .= " and a.sub_eCode = '$safeSub'";
			}
			if ($safeBus != "") {
				$qry .= " and a.bus_num = '$safeBus'";
			}
			$qry .= " order by a.bus_num asc, a.seq_no asc";

			$rst = $dbConn->query($qry);
			if ((!$rst || $rst->num_rows == 0) && ($safeSub != "" || $safeBus != "")) {
				$fallbackQry = "select a.guide_id, a.sguide_id
								from tour_guide a
								inner join product_master b on a.p_code = b.p_code
								where a.grand_eCode = '$safeGrand'
								  and a.stDate = '$safeDate'
								  and a.p_code = '$safePCode'
								  and b.m_guidechk = 'V'
								order by a.bus_num asc, a.seq_no asc";
				$rst = $dbConn->query($fallbackQry);
			}
			if ($rst) {
				while ($row = $rst->fetch_assoc()) {
					gamAppendGuideNameById($names, $row['guide_id']);
					gamAppendGuideNameById($names, $row['sguide_id']);
				}
			}
		}
	}

	//차량(bus_num)별 메인상품·메인가이드 조회 (assign_m.php 히스토리등록과 동일 기준)
	if (!function_exists('gamGetMainGuideAndProduct')) {
		function gamGetMainGuideAndProduct($grandECode, $subECode, $pCode, $stDate, $busNum = "")
		{
			global $dbConn;

			$safeGrand = $dbConn->real_escape_string($grandECode);
			$safeSub = $dbConn->real_escape_string($subECode);
			$safePCode = $dbConn->real_escape_string($pCode);
			$safeDate = $dbConn->real_escape_string($stDate);
			$safeBus = $dbConn->real_escape_string($busNum);

			$busFilter = ($safeBus != "") ? " and bus_num = '$safeBus'" : "";
			$subFilter = ($safeSub != "") ? " and sub_eCode = '$safeSub'" : "";

			$guideNames = array();
			$productNames = array();

			//1차: 이 차량의 예약 1건 → 예약의 메인가이드상품(m_guidechk='V') → 그 상품의 배정 가이드
			$reserveCode = "";
			$reserveQry = "select reserveCode
						   from tour_car
						   where grand_eCode = '$safeGrand'
						     and p_code = '$safePCode'
						     and stDate = '$safeDate'
						     and reserveCode != ''".$subFilter.$busFilter."
						   order by sub_eNum+0 asc, reserveCode asc
						   limit 1";
			$reserveRst = $dbConn->query($reserveQry);
			if ((!$reserveRst || $reserveRst->num_rows == 0) && $safeSub != "") {
				$reserveQry = "select reserveCode
							   from tour_car
							   where grand_eCode = '$safeGrand'
							     and p_code = '$safePCode'
							     and stDate = '$safeDate'
							     and reserveCode != ''".$busFilter."
							   order by sub_eNum+0 asc, reserveCode asc
							   limit 1";
				$reserveRst = $dbConn->query($reserveQry);
			}
			if ($reserveRst && $reserveRst->num_rows > 0) {
				$reserveRow = $reserveRst->fetch_assoc();
				$reserveCode = trim((string)$reserveRow['reserveCode']);
			}

			if ($reserveCode != "") {
				$safeRes = $dbConn->real_escape_string($reserveCode);
				$targetQry = "select distinct a.p_code, a.p_name, a.stDate, tc.grand_eCode, tc.sub_eCode, tc.bus_num
							  from reserve_info a
							  inner join product_master b on a.p_code = b.p_code
							  inner join tour_car tc on a.reserveCode = tc.reserveCode
							      and a.p_code = tc.p_code
							      and a.stDate = tc.stDate
							  where a.reserveCode = '$safeRes'
							    and a.parent = 'SUB'
							    and a.rev_status != 'CANCEL'
							    and b.m_guidechk = 'V'
							  order by a.stDate asc, a.seq_no asc, a.p_code asc, tc.bus_num+0 asc, tc.bus_num asc";
				$targetRst = $dbConn->query($targetQry);
				if ($targetRst) {
					while ($target = $targetRst->fetch_assoc()) {
						$targetName = trim((string)$target['p_name']);
						if ($targetName != "" && !in_array($targetName, $productNames, true)) {
							$productNames[] = $targetName;
						}
						$targetDate = trim((string)$target['stDate']);
						if ($targetDate == "") {
							$targetDate = $stDate;
						}
						gamAppendMainGuideNamesForProduct($guideNames, $target['grand_eCode'], $targetDate, $target['p_code'], $target['sub_eCode'], $target['bus_num']);
					}
				}
			}

			//2차(fallback): 같은 행사의 m_guidechk='V' 상품 (자기 자신 제외)
			if (empty($guideNames) && empty($productNames)) {
				$fallbackQry = "select distinct a.p_code, a.p_name, a.stDate
								from tour_guide a
								inner join product_master b on a.p_code = b.p_code
								where a.grand_eCode = '$safeGrand'
								  and a.stDate = '$safeDate'
								  and a.p_code != '$safePCode'
								  and b.m_guidechk = 'V'".$busFilter."
								order by a.stDate asc, a.sub_eCode asc, a.p_code asc";
				$fallbackRst = $dbConn->query($fallbackQry);
				if ($fallbackRst) {
					while ($target = $fallbackRst->fetch_assoc()) {
						$targetName = trim((string)$target['p_name']);
						if ($targetName != "" && !in_array($targetName, $productNames, true)) {
							$productNames[] = $targetName;
						}
						$targetDate = trim((string)$target['stDate']);
						if ($targetDate == "") {
							$targetDate = $stDate;
						}
						gamAppendMainGuideNamesForProduct($guideNames, $grandECode, $targetDate, $target['p_code'], $subECode, $busNum);
					}
				}
			}

			return array(
				"guide" => implode("+", $guideNames),
				"product" => implode(" / ", $productNames)
			);
		}
	}

?>
	<div id="contentwrapper" class="reservationDetailForm">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb module">
				<ul>
					<li><a href="/"><i class="glyphicon glyphicon-home"></i></a></li>
					<li><a href="#">행사배정</a></li>
					<li>가이드배정관리</li>
				</ul>
			</div>

			<form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&st=<?=$st?>&pcode=<?=$pcode?>" name="frmcar" method="post" onSubmit="return chksave()">
				<input type="hidden" name="mode" id="mode" value="save">
				<input type="hidden" name="gcode" id="gcode" value="<?=$sctour['grand_eCode']?>">
				<input type="hidden" name="pcode" id="pcode" value="<?=$sctour['p_code']?>">
				<input type="hidden" name="pname" id="pname" value='<?=$sctour['p_name']?>'>
				<input type="hidden" name="sdate" id="sdate" value="<?=$sctour['stDate']?>">
				<table id="custom_table" class="table table-bordered table-condensed gridSixteen reserveTable formDetail">
					<tbody>
						<tr>
                        <td colspan="2" class="active text-center formHeader">통합행사코드</td>
                        <td colspan="12"><?=$sctour['grand_eCode']?></td>
                    </tr>
					        			
                        <td colspan="2" class="active text-center formHeader">상품명</td>
                        <td colspan="12">[<?=$sctour['p_code']?>] <?=$sctour['p_name']?></td>
                    </tr>
                    <tr>
                        <td colspan="2" class="active text-center formHeader">출발일</td>
                        <td colspan="2"><?=$sctour['stDate']?></td>
                        
                        <td colspan="2" class="active text-center formHeader">투어정원</td>
                        <td colspan="2"><?=$sctour['tour_pcnt']?> 명 </td>
                        <td colspan="2" class="active text-center formHeader">예약인원</td>
                        <td colspan="2"><?=$pcnt['cnt']?> 명 </td>
                    </tr>
					
                        <td colspan="2" class="active text-center formHeader">예약인원</td>
                        <td colspan="12">
                            <label class="radio-inline">
                                <input type="radio" name="bookNumber" value="P" <?php if(strstr($sctour['r_status'],"P")) echo "checked"; ?> disabled> 예약접수중
                            </label>
                            <label class="radio-inline">
                                <input type="radio" name="bookNumber" value="C" <?php if(strstr($sctour['r_status'],"C")) echo "checked"; ?> disabled> 예약마감
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" class="active text-center formHeader">행사상태</td>
                        <td colspan="12">
                            <div class="row">
                                <div class="col-sm-4">
                                    <div class="input-group input-group-sm">
                                        <label class="radio-inline">
                                            <input type="radio" name="eventStatus" value="1" <?php if(strstr($sctour['ev_status'],"1")) echo "checked"; ?> disabled> 미확정
                                        </label>
                                        <label class="radio-inline">
                                            <input type="radio" name="eventStatus" value="2" <?php if(strstr($sctour['ev_status'],"2")) echo "checked"; ?> disabled> 확정
                                        </label>
                                        <label class="radio-inline">
                                            <input type="radio" name="eventStatus" value="3" <?php if(strstr($sctour['ev_status'],"3")) echo "checked"; ?> disabled> 만차
                                        </label>
                                        <label class="radio-inline">
                                            <input type="radio" name="eventStatus" value="4" <?php if(strstr($sctour['ev_status'],"4")) echo "checked"; ?> disabled> 취소
                                        </label>
                                        <label class="radio-inline">
                                            <input type="radio" name="eventStatus" <?php if(strstr($sctour['ev_status'],"5")) echo "checked"; ?> disabled> 기타
                                        </label>
                                    </div>
                                </div>    
                                <div class="col-sm-8">
                                    <div>   
                                        <input type="text" name="etcMemo" class="form-control" aria-label="기타메모"  placeholder="기타메모" value="<?=$sctour['etc_memo']?>" readOnly/>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                        <tr>
                            <td colspan="16" class="text-center"><button type='submit' class="btn-maroon btn-sm" name="car-assign" id="car-assign" >가이드배정</button>
                                <!--<button type='button' class="btn-orange btn-sm" name="hotel-assign" id="hotel-assign">호텔배정</button>-->
                            </td>
                        </tr>
                    </tbody>
				</table>
				<div class="row">
                    <!--<div class="col-sm-4">
                        <textarea class="form-control" rows="12" name="eventMemo" placeholder="행사메모" readONly><?=$sctour['ev_memo']?></textarea>
                    </div>-->    
                    <div class="col-sm-12">
                        
                        <?php
								$qry1 = "select 	
												grand_eCode, 
												sub_eCode, 
												reserveCode, 
												bus_num,
												p_code
												from tour_car 
												where stDate = '$st' && p_code = '$pcode'  group by bus_num order by bus_num asc";
								//echo $qry1;
								
								$rst1 = $dbConn->query($qry1);
								$k = 0;
								while($row1 = $rst1->fetch_assoc()){
								     $gss = getGuideInfo($sctour['grand_eCode'],$row1['sub_eCode'],$row1['bus_num'],$row1['p_code']);
									 //메인상품·메인가이드 (assign_m.php 히스토리등록과 동일 기준)
									 $mainInfo = gamGetMainGuideAndProduct($sctour['grand_eCode'],$row1['sub_eCode'],$row1['p_code'],$st,$row1['bus_num']);
									 $g_dbinfo = getinfo_dbMember($gss['guide_id']);
									 if ($g_dbinfo['userfile1'] == "") {
										$gimg = "http://www.myhello.info/img/sample.jpg";
									 } else {
										$gimg = "http://www.myhello.info/upload/$g_dbinfo[userfile1]";
									 }
						?>
                        <fieldset class="guide-assign-border">
                            <legend class="guide-assign-border"><span class="pull-left small text-muted">차량<?=$row1['bus_num']?>  (행사코드: <?=$row1['sub_eCode']?>)</span></legend>
							<input type="hidden" name="bbnum[]" id="bbnum" value="<?=$row1['bus_num']?>">
							<input type="hidden" name="ssnum[]" id="ssnum" value="<?=$row1['sub_eCode']?>">
                            <table class="table table-borderless table-condensed gridSixteen reserveTable formDetail">
                                <tbody>
								<tr>
                                       <!-- <td rowspan="5" width="5%" class="text-center formHeader">
                                           <span id="gimgid"> <img src="<?=$gimg?>" width="150" height="150"></span>
                                            <!--<input type="file" class="form-control" id="guideImg" name="guideImg<?=$i?>" placeholder="이미지">
                                        </td>-->
                                        <td colspan=2 class="text-center">가이드</td>
                                        <td colspan=2 class="text-center">차량</td>
										<td colspan=2 class="text-center">기사</td>
                                    </tr>
                                    <tr>
                                        <td colspan="6" style="background:#fff7cc;border:1px solid #c9a227;padding:8px 12px;">
                                            <span style="display:inline-block;background:#c9302c;color:#fff;font-size:11px;font-weight:bold;padding:2px 8px;margin-right:8px;">메인상품</span><span class="text-primary" style="font-weight:bold;"><?=($mainInfo['product'] != "" ? $mainInfo['product'] : "-")?></span>
                                            &nbsp;&nbsp;|&nbsp;&nbsp;
                                            <span style="display:inline-block;background:#c9302c;color:#fff;font-size:11px;font-weight:bold;padding:2px 8px;margin-right:8px;">메인가이드</span><span class="text-danger" style="font-weight:bold;"><?=($mainInfo['guide'] != "" ? $mainInfo['guide'] : "-")?></span>
                                        </td>
                                    </tr>
                                    <tr>
                                       <!-- <td rowspan="5" width="5%" class="text-center formHeader">
                                           <span id="gimgid"> <img src="<?=$gimg?>" width="150" height="150"></span>
                                            <!--<input type="file" class="form-control" id="guideImg" name="guideImg<?=$i?>" placeholder="이미지">
                                        </td>-->
                                        <td width="3%" class="text-center">가이드 이름</td>
                                        <td width="10%">
											<select class="form-control guidecs" name="guideName[]">
												<option value="" selected>선택</option>
												 <?=printGuideSelect($gss['guide_id'])?>
											</select>
                                        </td>
										<td width="2%" class="text-center">차량</td>
                                        <td>
											<select class="form-control cName" name="cName[]">
												<option value="" selected>선택</option>
												 <?=printCarSelect($gss['c_id'])?>
											</select>
                                        </td>
										<td width="2%" class="text-center">기사명</td>
                                        <td>
											<input type="text" name="driver[]" id="driver" class="form-control driver" aria-label="기사명" value="<?=$gss['d_nm']?>"/>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td width="2%" class="text-center">연락처</td>
                                        <td><input type="text" name="guideTelephone[]" id="guideTelephone" class="form-control tel" aria-label="전화번호" value="<?=$gss['g_tel']?>"/></td>
										<td width="2%" class="text-center">연락처</td>
                                        <td><input type="text" name="carTelephone[]" id="carTelephone" class="form-control ctel" aria-label="전화번호" value="<?=$gss['c_tel']?>"/></td>
										<td width="2%" class="text-center">연락처</td>
                                        <td><input type="text" name="dTelephone[]" id="dTelephone" class="form-control dtel" aria-label="전화번호" value="<?=$gss['d_tel']?>"/></td>
                                    </tr>
                                    <tr>
										<td width="2%" class="text-center">선지급 행사비</td>
                                        <td><input type="text" name="guidePreCost[]" class="form-control preamt" aria-label="선지급 행사비" value="<?=$gss['pre_amt']?>"/></td>
                                        <td width="2%" class="text-center">차량종류</td>
                                        <td><input type="text" name="cartype[]"  name="cartype" class="form-control ctype" aria-label="차량종류" value="<?=$gss['c_type']?>"/></td>
										<td width="2%" class="text-center">메모</td>
                                        <td><input type="text" name="dmemo[]"  name="dmemo" class="form-control dmeom" aria-label="메모" value="<?=$gss['d_memo']?>"/></td>
                                    </tr>
                                    <tr>
                                        <td width="2%" class="text-center">부가이드</td>
                                        <td width="10%">
											<?php
												//부가이드 여러명: sguide_id 콤마(,) 구분 저장
												$sguideIds = array();
												foreach (explode(",", (string)$gss['sguide_id']) as $sgid_tmp) {
													$sgid_tmp = trim($sgid_tmp);
													if ($sgid_tmp != "") $sguideIds[] = $sgid_tmp;
												}
												if (count($sguideIds) == 0) $sguideIds[] = "";
											?>
											<div class="js-sguideBox">
												<?php foreach ($sguideIds as $sgKey => $sgid_tmp) { ?>
												<div class="input-group input-group-sm sguideRow" <?php if($sgKey>0){ ?>style="margin-top:4px;"<?php } ?>>
													<select class="form-control sguidecs">
														<option value="" <?php if($sgid_tmp==""){ ?>selected<?php } ?>>선택</option>
														 <?=printGuideSelect($sgid_tmp)?>
													</select>
													<span class="input-group-btn">
														<button type="button" class="btn btn-default js-addSguide" title="부가이드 추가"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button>
														<button type="button" class="btn btn-default js-delSguide" title="부가이드 삭제"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
													</span>
												</div>
												<?php } ?>
												<input type="hidden" name="sguideName[]" class="sguideCsv" value="<?=implode(",", array_filter($sguideIds))?>">
											</div>
                                        </td>
                                        <td width="2%" class="text-center">메모</td>
                                        <td><input type="text" name="carmemo[]"  name="carmemo" class="form-control cmemo" aria-label="메모" value="<?=$gss['c_memo']?>"/></td>
										<td width="2%" class="text-left"></td>
                                        <td></td>
                                    </tr>
                                   

                                </tbody>
                            </table> 
                        </fieldset>
                        <?php }?>
                        <!--
                        <fieldset class="guide-assign-border">
                            <legend class="guide-assign-border"><span class="pull-left small text-muted">차량2 가이드배정</span></legend>
                            <table class="table table-borderless table-condensed gridSixteen reserveTable formDetail">
                                <tbody>
                                    <tr>
                                        <td rowspan="5" width="5%" class="text-center formHeader">
                                            <img src="http://www.parantours.biz/admin/img/sample.jpg" width="150" height="="150"">
                                        </td>
                                        <td width="2%" class="text-left">가이드 이름</td>
                                        <td><input type="text" class="form-control" aria-label="가이드 이름" value=""/></td>
                                    </tr>
                                    <tr>
                                        <td width="2%" class="text-left">전화번호</td>
                                        <td><input type="text" class="form-control" aria-label="전화번호" value=""/></td>
                                    </tr>
                                    <tr>
                                        <td width="2%" class="text-left">이메일</td>
                                        <td><input type="text" class="form-control" aria-label="이메일" value=""/></td>
                                    </tr>
                                    <tr>
                                        <td width="2%" class="text-left">선지급 행사비</td>
                                        <td><input type="text" class="form-control" aria-label="선지급 행사비" value=""/></td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" class="text-center"><button type="submit" class="btn btn-xs btn-default js-xxx">가이드배정</button></td>
                                    </tr>

                                </tbody>
                            </table> 
                        </fieldset>-->
                    </div>
                </div>
			</form>
		</div>
	</div>
	
    <?php
		include "include/side_m.php"
	?>
   
   <script>
		$(document).ready(function () {
			pt.initReservationList()
			$('.guidecs').bind("change",function() {
				var ruid = $(this).val();
			    var sel = $(this); 
				$.getJSON("get_guide.php?ruid="+ruid, function(jsonData){
					 sel.closest('table').find("#guideTelephone").val("");
					 sel.closest('table').find(".email").val("");
					 //sel.closest('table').find("#gimgid").html("<img src='http://www.parantours.biz/admin/img/sample.jpg' width='150' height='150'>");
					 
					 $.each(jsonData, function(i,data){
						  var tel = data.company_phone;
						  var email = data.company_email;
						  var gimg = "";
						  if (data.userfile1== "") {
								gimg = "http://www.parantours.biz/admin/img/sample.jpg";
						  } else {
								gimg = "http://www.parantours.biz/admin/upload/"+data.userfile1+"";
						  }
						  sel.closest('table').find("#guideTelephone").val(tel);
						  sel.closest('table').find(".email").val(email);
						  sel.closest('table').find("#gimgid").html("<img src='"+gimg+"' width='150' height='150'>");
											
					 });
					  
				});
			});
			$('.cName').bind("change",function() {
				var ruid = $(this).val();
			    var sel = $(this); 
				$.getJSON("get_ccar.php?ruid="+ruid, function(jsonData){
					 sel.closest('table').find(".ctel").val("");
					
					 $.each(jsonData, function(i,data){
						  var tel = data.bus_tel;
						 // var cmemo = data.cmemo;
						 // var gimg = "";
						 
						  $.getJSON("get_codec.php?code="+data.bus_type, function(jsonData1){
								$.each(jsonData1, function(i,data1){
									var code = data1.comment;
								    sel.closest('table').find(".ctype").val(code);
								});
						  });
						  
						 
						  sel.closest('table').find(".ctel").val(tel);
						
											
					 });
					  
				});
			});
		})

		//부가이드 select 값들을 콤마(,)로 합쳐 hidden(sguideName[])에 반영
		function syncSguideCsv(box) {
			var ids = [];
			box.find('select.sguidecs').each(function(){
				var v = $(this).val();
				if (v != '' && $.inArray(v, ids) < 0) ids.push(v);
			});
			box.find('.sguideCsv').val(ids.join(','));
		}

		$(document).off('click.sguideAdd').on('click.sguideAdd', '.js-addSguide', function(){
			var box = $(this).closest('.js-sguideBox');
			var row = $(this).closest('.sguideRow');
			var newrow = row.clone();
			newrow.find('select.sguidecs').val('');
			newrow.css('margin-top','4px');
			box.find('.sguideRow:last').after(newrow);
			syncSguideCsv(box);
		});
		$(document).off('click.sguideDel').on('click.sguideDel', '.js-delSguide', function(){
			var box = $(this).closest('.js-sguideBox');
			if (box.find('.sguideRow').length <= 1) {
				box.find('.sguideRow select.sguidecs').val('');
			} else {
				$(this).closest('.sguideRow').remove();
			}
			syncSguideCsv(box);
		});
		$(document).off('change.sguideSel').on('change.sguideSel', '.js-sguideBox select.sguidecs', function(){
			syncSguideCsv($(this).closest('.js-sguideBox'));
		});

		function chksave() {
			/*
                  if ($("#area1").val() == "") {
						alert("상품분류 1을 입력하세요!");
						$("#area1").focus();
						return false;
				  }
			*/
				$('.js-sguideBox').each(function(){
					syncSguideCsv($(this));
				});
				if (confirm("배정할까요?") == true) {
				    return true;
			    } else {
					return false;
				}
			}
	</script>
   
    </body>
</html>
