
<?php
    include "include/header.php";
    //include "include/inc_base.php";
	if ($_COOKIE[MEMLOGIN_ADMIN_PURUN] !="") {
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

	if ($mode == "scheduleSave") {
		include "inc_prodscsave.php";
	}
 
	if ($mode == "save") {
		include "inc_prodmsave.php";
	}
    
	if($mode == "del")
	{
		$qry1 = "delete from product_limit where p_code= '$pcode'";
		$rst1 = mysql_query($qry1,$dbConn); 

		$qry1 = "delete from product_details_local where p_code= '$pcode'";
		$rst1 = mysql_query($qry1,$dbConn);

		$qry1 = "delete from product_pick where p_code= '$pcode'";
		$rst1 = mysql_query($qry1,$dbConn);
		
		$qry1 = "delete from product_details where p_code= '$pcode'";
		$rst1 = mysql_query($qry1,$dbConn);

		$qry1 = "delete from product_master where p_code= '$pcode'";
		$rst1 = mysql_query($qry1,$dbConn);

		Misc::jvAlert("삭제했습니다.","");
		echo "<meta http-equiv='refresh' content='0;url=./base_product.php?division=2&pdx=$pdx&sub=$sub&ty=$ty'>";	
		exit;	
		
	}
	$prodInfo = getProductMaster($pcode);
	
	$lvcode2 = substr($prodInfo[c_code1],3,2);
	if ($ty == 1) {
        $pcap = "단일상품등록";
	} else if ($ty == 2) {
        $pcap = "복합상품등록";
	} else if ($ty == 3) {
        $pcap = "인바운드";
	} else if ($ty == 4) {
        $pcap = "인센티브";
	} else if ($ty == 5) {
        $pcap = "아웃바운드";
	}
	

?>
	<div id="contentwrapper" class="productDetailForm">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb module">
				<ul>
					<li><a href="/"><i class="glyphicon glyphicon-home"></i></a></li>
					<li><a href="#">상품관리</a></li>
					<li><a href="#">상품등록</a></li>
					<li><?= $pcap ?></li>
				</ul>
			</div>

			<form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&ty=<?=$ty?>&pcode=<?=$pcode?>" name="frmproduct" id="frmproduct" method="post" Enctype="multipart/form-data" onSubmit="return chksave()">
				<input type="hidden" name="mode" id="mode" value="save">
				<input type="hidden" name="pcode" value="<?= $pcode ?>">
				<input type="hidden" name="currency" value="USD">
				<div class="row">
					<div class="col-sm-6 col-sm-offset-6 text-right">
						<button type="submit" class="btn btn-xs btn-default js-formSave">상품저장</button>
						<?php if ($pcode!="") { ?>
							<button type="button" class="btn btn-xs btn-default js-formDelete" OnClick="javascript:pdel()">상품삭제</button>
							<button type="button" class="btn btn-xs btn-default js-openSchedule" data-toggle="modal" data-target=".js-openScheduleModal">일정표작성</button>
						<?php } ?>
					</div>
				</div>
				<br />
				<table class="table table-bordered table-condensed ptTable formDetail">
					<tbody>
						<!--<tr>
							<td colspan="2" class="active text-center formHeader">기준통화</td>
							<td colspan="10">
								<label class="radio-inline">
									<input type="radio" name="currency" id="currencyCAD" value="CAD" <?php if ($prodInfo[base_rate] == "CAD") echo "checked"; ?>> CAD
								</label>
								<label class="radio-inline">
									<input type="radio" name="currency" id="currencyUSD" value="USD" <?php if ($prodInfo[base_rate] == "USD") echo "checked"; ?>> USD
								</label>
							</td>
						</tr>-->
						<tr>
							<td colspan="2" class="active text-center formHeader">상품분류</td>
							<td colspan="10" class="form-inline">
								<select class="form-control fst1" name="area1" id="area1">
									<option value="">분류선택1
									<?=printBaseCode_first('T01',$prodInfo[c_code1])?>
								</select>
								<select class="form-control fst2" name="area2" id="area2">
									<option value="">분류선택2</option>
									<?=printBaseCode_second('T01',$lvcode2,$prodInfo[c_code2])?>
								</select>
								<select class="form-control" name="tripDuration">
									<option value="">박수설정</option>
									<?=printBaseCode_first('C01',$prodInfo[c_code3])?>
								</select>
							</td>
						</tr>
						<?php if ($ty !=1) { ?>
						<tr>
							<td colspan="2" class="active text-center formHeader">상품구성</td>
							<td colspan="10">
						<?php } ?>
						<?php
							$d_qry1 = "select * from product_details_local where p_code = '$pcode' order by day,position asc";
							$d_rst1 = mysql_query($d_qry1);
							$d = 1;
							while($d_row1 = mysql_fetch_assoc($d_rst1)):
								$sproductInfo = getProductMaster($d_row1[local_code]);
								if ($d_row1[local_code] !="") {
							
						?>
									<div class="well well-sm thinMargin" role="alert"><strong><?=$d_row1[day]?>일차: </strong>[<?=$d_row1[local_code]?>] <?=$sproductInfo[p_name]?></div>
								
						<?php
							    }
							endwhile;
						?>
						<?php if ($ty !=1) { ?>
							</td>
						</tr>
						<?php } ?>
						
						<tr>
							<td colspan="2" class="active text-center formHeader">상품코드</td>
							<td colspan="4">
								<div class="form-group removeBottomMargin">
									<label class="sr-only" for="prodCode">상품코드</label>
									<input type="text" class="form-control" id="prodCode" name="prodCode" placeholder="자동생성 및 수정가능" value='<?=$prodInfo[p_code]?>'>
								</div>
							</td>
							<td colspan="2" class="active text-center formHeader">상품명</td>
							<td colspan="4">
								<div class="form-group removeBottomMargin">
									<label class="sr-only" for="prodName">상품명</label>
									<input type="text" class="form-control" id="prodName" name="prodName" placeholder="상품명" value='<?=$prodInfo[p_name]?>'>
								</div>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">상품출발지역/상품소유사</td>
							<td colspan="1">
								<select class="form-control" name="sarea" id="sarea">
									<option value="">출발지선택</option>
									<?=printBaseCode_first('C02',$prodInfo[c_code4])?>
								</select>
							</td>
							<td colspan="3">
								<select class="randcomp" name="p_own" id="p_own">
									<option value="">소유사(지사) 선택</option>
									<option value="purun" selected>푸른투어-본사</option>
									<?=printRandSelect($prodInfo[p_own])?>
								</select>
							</td>
							<td colspan="2" class="active text-center formHeader">여행기간</td>
							<td colspan="4">
								<div class="form-group removeBottomMargin">
									<label class="sr-only" for="tourLength">여행기간</label>
									<input type="text" class="form-control js-tourLength" id="tourLength" name="tourLength" placeholder="여행기간" value="<?=$prodInfo[p_day]?>">
								</div>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">탑승지설정 &nbsp;<button type="button" class="btn btn-default btn-xs js-addPickup"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>
							<td colspan="10">
							   <?php
									$qry1 = "select * from product_pick where p_code = '$prodInfo[p_code]'";
									
									$rst1 = mysql_query($qry1);
									$cnt = mysql_num_rows($rst1);
									while($pick_row = mysql_fetch_assoc($rst1)):
						
							   ?>
									<div class="form-inline js-pickupSet">
										<select class="form-control pickarea" name="pickLoc[]">
											<option value="">픽업지역선택</option>
											<?=pickBaseCode($pick_row[pick_area])?>
										</select>
										<select class="form-control picktt" name="pickTime[]">
											<option value="">픽업시간선택</option>
											<?=pickBaseCodeSencond($pick_row[pick_area],$pick_row[pick_time])?>
										</select>
										<button type="button" class="btn btn-default btn-xs js-removePickup"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
									</div>
							   <?php
									
							      endwhile;
							  
							      if ($cnt == 0) {  	   
						       ?>
									<div class="form-inline js-pickupSet">
										<select class="form-control pickarea" name="pickLoc[]">
											<option value="">픽업지역선택</option>
											<?=pickBaseCode('')?>
										</select>
										<select class="form-control picktt" name="pickTime[]">
											<option value="">픽업시간선택</option>
											
										</select>
										<button type="button" class="btn btn-default btn-xs js-removePickup hidden"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
									</div>
							   <?php

								  }
						       ?>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">투어정원</td>
							<td colspan="4">
								<div class="form-group removeBottomMargin">
									<label class="sr-only" for="maxPerCar">투어정원</label>
									<input type="text" class="form-control" id="maxPerCar" name="maxPerCar" placeholder="여행기간" value="<?=$prodInfo[p_cnt]?>">
								</div>
							</td>
							<td colspan="2" class="active text-center formHeader">최소출발인원</td>
							<td colspan="4">
								<div class="form-group removeBottomMargin">
									<label class="sr-only" for="minViableNum">최소출발인원</label>
									<input type="text" class="form-control" id="minViableNum" name="minViableNum" placeholder="최소출발인원" value="<?=$prodInfo[p_scnt]?>">
								</div>
							</td>
						</tr>
						<tr>
							<td colspan="12" class="active text-center formHeader fullWidth">구분별 상품가격</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">당일</td>
							<td colspan="10">
								<div class="col-lg-4">
									<div class="row">
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label for="displayAdultPrice0">표시용 성인가격</label>
												<input type="text" class="form-control" id="displayAdultPrice0" name="displayAdultPrice0" placeholder="표시용 성인가격" value="<?=$prodInfo[price_0dadult]?>">
											</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label for="displayChildPrice0">표시용 어린이가격</label>
												<input type="text" class="form-control" id="displayChildPrice0" name="displayChildPrice0" placeholder="표시용 어린이가격" value="<?=$prodInfo[price_0dchild]?>">
											</div>
										</div>
									</div>
								</div>
								<div class="col-lg-4">
									<div class="row">
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label for="regularAdultPrice0">일반 성인가격</label>
												<input type="text" class="form-control" id="regularAdultPrice0" name="regularAdultPrice0" placeholder="일반 성인가격" value="<?=$prodInfo[price_0adult]?>">
											</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label for="regularChildPrice0">일반 어린이가격</label>
												<input type="text" class="form-control" id="regularChildPrice0" name="regularChildPrice0" placeholder="일반 어린이가격" value="<?=$prodInfo[price_0child]?>">
											</div>
										</div>
									</div>
								</div>
								<div class="col-lg-4">
									<div class="row">
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label for="partnerAdultPrice0">협력사 성인가격</label>
												<input type="text" class="form-control" id="partnerAdultPrice0" name="partnerAdultPrice0" placeholder="협력사 성인가격" value="<?=$prodInfo[price_0cadult]?>">
											</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label for="partnerChildPrice0">협력사 어린이가격</label>
												<input type="text" class="form-control" id="partnerChildPrice0" name="partnerChildPrice0" placeholder="협력사 어린이가격" value="<?=$prodInfo[price_0cchild]?>">
											</div>
										</div>
									</div>
								</div>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">1인1실</td>
							<td colspan="10">
								<div class="col-lg-4">
									<div class="row">
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												
												<input type="text" class="form-control" id="displayAdultPrice1" name="displayAdultPrice1" placeholder="표시용 성인가격" value="<?=$prodInfo[price_1dadult]?>">
											</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												
												<input type="text" class="form-control" id="displayChildPrice1" name="displayChildPrice1" placeholder="표시용 어린이가격" value="<?=$prodInfo[price_1dchild]?>">
											</div>
										</div>
									</div>
								</div>
								<div class="col-lg-4">
									<div class="row">
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												
												<input type="text" class="form-control" id="regularAdultPrice1" name="regularAdultPrice1" placeholder="일반 성인가격" value="<?=$prodInfo[price_1adult]?>">
											</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												
												<input type="text" class="form-control" id="regularChildPrice1" name="regularChildPrice1" placeholder="일반 어린이가격" value="<?=$prodInfo[price_1child]?>">
											</div>
										</div>
									</div>
								</div>
								<div class="col-lg-4">
									<div class="row">
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												
												<input type="text" class="form-control" id="partnerAdultPrice1" name="partnerAdultPrice1" placeholder="협력사 성인가격" value="<?=$prodInfo[price_1cadult]?>">
											</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												
												<input type="text" class="form-control" id="partnerChildPrice1" name="partnerChildPrice1" placeholder="협력사 어린이가격" value="<?=$prodInfo[price_1cchild]?>">
											</div>
										</div>
									</div>
								</div>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">2인1실</td>
							<td colspan="10">
								<div class="col-lg-4">
									<div class="row">
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="displayAdultPrice2">표시용 성인가격</label>
												<input type="text" class="form-control" id="displayAdultPrice2" name="displayAdultPrice2" placeholder="표시용 성인가격" value="<?=$prodInfo[price_2dadult]?>">
											</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="displayChildPrice2">표시용 어린이가격</label>
												<input type="text" class="form-control" id="displayChildPrice2" name="displayChildPrice2" placeholder="표시용 어린이가격" value="<?=$prodInfo[price_2dchild]?>">
											</div>
										</div>
									</div>
								</div>
								<div class="col-lg-4">
									<div class="row">
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="regularAdultPrice2">일반 성인가격</label>
												<input type="text" class="form-control" id="regularAdultPrice2" name="regularAdultPrice2" placeholder="일반 성인가격" value="<?=$prodInfo[price_2adult]?>">
											</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="regularChildPrice2">일반 어린이가격</label>
												<input type="text" class="form-control" id="regularChildPrice2" name="regularChildPrice2" placeholder="일반 어린이가격" value="<?=$prodInfo[price_2child]?>">
											</div>
										</div>
									</div>
								</div>
								<div class="col-lg-4">
									<div class="row">
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="partnerAdultPrice2">협력사 성인가격</label>
												<input type="text" class="form-control" id="partnerAdultPrice2" name="partnerAdultPrice2" placeholder="협력사 성인가격" value="<?=$prodInfo[price_2cadult]?>">
											</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="partnerChildPrice2">협력사 어린이가격</label>
												<input type="text" class="form-control" id="partnerChildPrice2" name="partnerChildPrice2" placeholder="협력사 어린이가격" value="<?=$prodInfo[price_2cchild]?>">
											</div>
										</div>
									</div>
								</div>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">3인1실</td>
							<td colspan="10">
								<div class="col-lg-4">
									<div class="row">
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="displayAdultPrice3">표시용 성인가격</label>
												<input type="text" class="form-control" id="displayAdultPrice3" name="displayAdultPrice3" placeholder="표시용 성인가격"  value="<?=$prodInfo[price_3dadult]?>">
											</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="displayChildPrice3">표시용 어린이가격</label>
												<input type="text" class="form-control" id="displayChildPrice3" name="displayChildPrice3" placeholder="표시용 어린이가격" value="<?=$prodInfo[price_3dchild]?>">
											</div>
										</div>
									</div>
								</div>
								<div class="col-lg-4">
									<div class="row">
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="regularAdultPrice3">일반 성인가격</label>
												<input type="text" class="form-control" id="regularAdultPrice3" name="regularAdultPrice3" placeholder="일반 성인가격" value="<?=$prodInfo[price_3adult]?>">
											</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="regularChildPrice3">일반 어린이가격</label>
												<input type="text" class="form-control" id="regularChildPrice3" name="regularChildPrice3" placeholder="일반 어린이가격" value="<?=$prodInfo[price_3child]?>">
											</div>
										</div>
									</div>
								</div>
								<div class="col-lg-4">
									<div class="row">
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="partnerAdultPrice3">협력사 성인가격</label>
												<input type="text" class="form-control" id="partnerAdultPrice3" name="partnerAdultPrice3" placeholder="협력사 성인가격" value="<?=$prodInfo[price_3cadult]?>">
											</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="partnerChildPrice3">협력사 어린이가격</label>
												<input type="text" class="form-control" id="partnerChildPrice3" name="partnerChildPrice3" placeholder="협력사 어린이가격" value="<?=$prodInfo[price_3cchild]?>">
											</div>
										</div>
									</div>
								</div>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">4인1실</td>
							<td colspan="10">
								<div class="col-lg-4">
									<div class="row">
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="displayAdultPrice4">표시용 성인가격</label>
												<input type="text" class="form-control" id="displayAdultPrice4" name="displayAdultPrice4" placeholder="표시용 성인가격" value="<?=$prodInfo[price_4dadult]?>">
											</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="displayChildPrice4">표시용 어린이가격</label>
												<input type="text" class="form-control" id="displayChildPrice4" name="displayChildPrice4" placeholder="표시용 어린이가격"  value="<?=$prodInfo[price_4dchild]?>">
											</div>
										</div>
									</div>
								</div>
								<div class="col-lg-4">
									<div class="row">
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="regularAdultPrice4">일반 성인가격</label>
												<input type="text" class="form-control" id="regularAdultPrice4" name="regularAdultPrice4" placeholder="일반 성인가격" value="<?=$prodInfo[price_4adult]?>">
											</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="regularChildPrice4">일반 어린이가격</label>
												<input type="text" class="form-control" id="regularChildPrice4" name="regularChildPrice4" placeholder="일반 어린이가격" value="<?=$prodInfo[price_4child]?>">
											</div>
										</div>
									</div>
								</div>
								<div class="col-lg-4">
									<div class="row">
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="partnerAdultPrice4">협력사 성인가격</label>
												<input type="text" class="form-control" id="partnerAdultPrice4" name="partnerAdultPrice4" placeholder="협력사 성인가격" value="<?=$prodInfo[price_4cadult]?>">
											</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="partnerChildPrice4">협력사 어린이가격</label>
												<input type="text" class="form-control" id="partnerChildPrice4" name="partnerChildPrice4" placeholder="협력사 어린이가격" value="<?=$prodInfo[price_4child]?>">
											</div>
										</div>
									</div>
								</div>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">5인1실</td>
							<td colspan="10">
								<div class="col-lg-4">
									<div class="row">
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="displayAdultPrice5">표시용 성인가격</label>
												<input type="text" class="form-control" id="displayAdultPrice5" name="displayAdultPrice5" placeholder="표시용 성인가격" value="<?=$prodInfo[price_5dadult]?>">
											</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="displayChildPrice5">표시용 어린이가격</label>
												<input type="text" class="form-control" id="displayChildPrice5" name="displayChildPrice5" placeholder="표시용 어린이가격" value="<?=$prodInfo[price_5dchild]?>">
											</div>
										</div>
									</div>
								</div>
								<div class="col-lg-4">
									<div class="row">
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="regularAdultPrice5">일반 성인가격</label>
												<input type="text" class="form-control" id="regularAdultPrice5" name="regularAdultPrice5" placeholder="일반 성인가격" value="<?=$prodInfo[price_5adult]?>">
											</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="regularChildPrice5">일반 어린이가격</label>
												<input type="text" class="form-control" id="regularChildPrice5" name="regularChildPrice5" placeholder="일반 어린이가격" value="<?=$prodInfo[price_5child]?>">
											</div>
										</div>
									</div>
								</div>
								<div class="col-lg-4">
									<div class="row">
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="partnerAdultPrice5">협력사 성인가격</label>
												<input type="text" class="form-control" id="partnerAdultPrice5" name="partnerAdultPrice5" placeholder="협력사 성인가격" value="<?=$prodInfo[price_5cadult]?>">
											</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="partnerChildPrice5">협력사 어린이가격</label>
												<input type="text" class="form-control" id="partnerChildPrice5" name="partnerChildPrice5" placeholder="협력사 어린이가격" value="<?=$prodInfo[price_5cchild]?>">
											</div>
										</div>
									</div>
								</div>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">편도버스이용</td>
							<td colspan="10">
								<div class="col-lg-4">
									<div class="row">
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="displayAdultPriceBusOneway">표시용 성인가격</label>
												<input type="text" class="form-control" id="displayAdultPriceBusOneway" name="displayAdultPriceBusOneway" placeholder="표시용 성인가격" value="<?=$prodInfo[price_busodadult]?>">
											</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="displayChildPriceBusOneway">표시용 어린이가격</label>
												<input type="text" class="form-control" id="displayChildPriceBusOneway" name="displayChildPriceBusOneway" placeholder="표시용 어린이가격" value="<?=$prodInfo[price_busodchild]?>">
											</div>
										</div>
									</div>
								</div>
								<div class="col-lg-4">
									<div class="row">
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="regularAdultPriceBusOneway">일반 성인가격</label>
												<input type="text" class="form-control" id="regularAdultPriceBusOneway" name="regularAdultPriceBusOneway" placeholder="일반 성인가격" value="<?=$prodInfo[price_busoadult]?>">
											</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="regularChildPriceBusOneway">일반 어린이가격</label>
												<input type="text" class="form-control" id="regularChildPriceBusOneway" name="regularChildPriceBusOneway" placeholder="일반 어린이가격" value="<?=$prodInfo[price_busochild]?>">
											</div>
										</div>
									</div>
								</div>
								<div class="col-lg-4">
									<div class="row">
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="partnerAdultPriceBusOneway">협력사 성인가격</label>
												<input type="text" class="form-control" id="partnerAdultPriceBusOneway" name="partnerAdultPriceBusOneway" placeholder="협력사 성인가격" value="<?=$prodInfo[price_busocadult]?>">
											</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="partnerChildPriceBusOneway">협력사 어린이가격</label>
												<input type="text" class="form-control" id="partnerChildPriceBusOneway" name="partnerChildPriceBusOneway" placeholder="협력사 어린이가격" value="<?=$prodInfo[price_busocchild]?>">
											</div>
										</div>
									</div>
								</div>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">왕복버스이용</td>
							<td colspan="10">
								<div class="col-lg-4">
									<div class="row">
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="displayAdultPriceBusRoundTrip">표시용 성인가격</label>
												<input type="text" class="form-control" id="displayAdultPriceBusRoundTrip" name="displayAdultPriceBusRoundTrip" placeholder="표시용 성인가격" value="<?=$prodInfo[price_busrdadult]?>">
											</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="displayChildPriceBusRoundTrip">표시용 어린이가격</label>
												<input type="text" class="form-control" id="displayChildPriceBusRoundTrip" name="displayChildPriceBusRoundTrip" placeholder="표시용 어린이가격" value="<?=$prodInfo[price_busrdchild]?>">
											</div>
										</div>
									</div>
								</div>
								<div class="col-lg-4">
									<div class="row">
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="regularAdultPriceBusRoundTrip">일반 성인가격</label>
												<input type="text" class="form-control" id="regularAdultPriceBusRoundTrip" name="regularAdultPriceBusRoundTrip" placeholder="일반 성인가격" value="<?=$prodInfo[price_busradult]?>">
											</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="regularChildPriceBusRoundTrip">일반 어린이가격</label>
												<input type="text" class="form-control" id="regularChildPriceBusRoundTrip" name="regularChildPriceBusRoundTrip" placeholder="일반 어린이가격" value="<?=$prodInfo[price_busrchild]?>">
											</div>
										</div>
									</div>
								</div>
								<div class="col-lg-4">
									<div class="row">
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="partnerAdultPriceBusRoundTrip">협력사 성인가격</label>
												<input type="text" class="form-control" id="partnerAdultPriceBusRoundTrip" name="partnerAdultPriceBusRoundTrip" placeholder="협력사 성인가격" value="<?=$prodInfo[price_busrcadult]?>">
											</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="partnerChildPriceBusRoundTrip">협력사 어린이가격</label>
												<input type="text" class="form-control" id="partnerChildPriceBusRoundTrip" name="partnerChildPriceBusRoundTrip" placeholder="협력사 어린이가격" value="<?=$prodInfo[price_busrcchild]?>">
											</div>
										</div>
									</div>
								</div>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">출발요일별선택</td>
							<td colspan="10" class="form-inline">
								<div class="col-sm-2">
									<div class="form-group removeBottomMargin">
										<label class="sr-only" for="startDate1">출발요일별선택</label>
										<input type="date" class="form-control" id="startDate1" name="startDate1" placeholder="출발요일별선택" value="<?=$prodInfo[p_vstart]?>">
									</div>
								</div>
								<div class="col-sm-2">
									<div class="form-group removeBottomMargin">
										<label class="sr-only" for="startDate2">출발요일별선택</label>
										<input type="date" class="form-control" id="startDate2" name="startDate2" placeholder="출발요일별선택" value="<?=$prodInfo[p_vend]?>">
									</div>
								</div>
								<div class="col-sm-8">
									<label class="form-inline">
										<input type="checkbox" name="weekday[]" id="monday" value="0" <?php if(strstr($prodInfo[p_week],"0/")) echo "checked"; ?> > 일
									</label>
									<label class="form-inline">
										<input type="checkbox" name="weekday[]" id="tuesday" value="1" <?php if(strstr($prodInfo[p_week],"1/")) echo "checked"; ?>> 월
									</label>
									<label class="form-inline">
										<input type="checkbox" name="weekday[]" id="wednesday" value="2" <?php if(strstr($prodInfo[p_week],"2/")) echo "checked"; ?>> 화
									</label>
									<label class="form-inline">
										<input type="checkbox" name="weekday[]" id="thursday" value="3" <?php if(strstr($prodInfo[p_week],"3/")) echo "checked"; ?>> 수
									</label>
									<label class="form-inline">
										<input type="checkbox" name="weekday[]" id="friday" value="4" <?php if(strstr($prodInfo[p_week],"4/")) echo "checked"; ?>> 목
									</label>
									<label class="form-inline">
										<input type="checkbox" name="weekday[]" id="saturday" value="5" <?php if(strstr($prodInfo[p_week],"5/")) echo "checked"; ?>> 금
									</label>
									<label class="form-inline">
										<input type="checkbox" name="weekday[]" id="sunday" value="6" <?php if(strstr($prodInfo[p_week],"6/")) echo "checked"; ?>> 토
									</label>
									<label class="form-inline">
										<input type="checkbox" name="weekday[]" id="everyday" value="9" <?php if(strstr($prodInfo[p_week],"9/")) echo "checked"; ?>> 매일
									</label>
								</div>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">예약제한일자 &nbsp;<button type="button" class="btn btn-default btn-xs js-addBlockDate"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>
							<td colspan="10" class="form-inline">
							    <?php
									$qry1 = "select * from product_limit where p_code = '$prodInfo[p_code]' && p_type ='L' order by p_limitdate asc";
									
									$rst1 = mysql_query($qry1);
									$cntL = mysql_num_rows($rst1);
									while($limit_row = mysql_fetch_assoc($rst1)):
						
							   ?>
										<div class="col-sm-12 js-blockDateSet">
											<div class="form-group removeBottomMargin">
												<label class="sr-only" for="blockDate">예약제한일자</label>
												<input type="date" class="form-control" id="blockDate" name="blockDate[]" placeholder="예약제한일자" value="<?= $limit_row[p_limitdate] ?>">
											</div>
											<button type="button" class="btn btn-default btn-xs js-removeBlockDate"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
										</div>
							  <?php
							      endwhile;
							      if ($cntL == 0) {  	   
						      ?>
									  <div class="col-sm-12 js-blockDateSet">
											<div class="form-group removeBottomMargin">
												<label class="sr-only" for="blockDate">예약제한일자</label>
												<input type="date" class="form-control" id="blockDate" name="blockDate[]" placeholder="예약제한일자" value="">
											</div>
											<button type="button" class="btn btn-default btn-xs js-removeBlockDate hidden"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
										</div>


							  <?php

								  }
						       ?>
								 
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">예약특정일자 &nbsp;<button type="button" class="btn btn-default btn-xs js-addReservationDate"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>
							<td colspan="10" class="form-inline">
							   <?php
									$qry1 = "select * from product_limit where p_code = '$prodInfo[p_code]' && p_type ='R' order by p_limitdate asc";
									
									$rst1 = mysql_query($qry1);
									$cntR = mysql_num_rows($rst1);
									while($r_row = mysql_fetch_assoc($rst1)):
						
							   ?>
										<div class="col-sm-12 js-reservationDateSet">
											<div class="form-group removeBottomMargin">
												<label class="sr-only" for="reservationDate">예약특정일자</label>
												<input type="date" class="form-control" id="reservationDate" name="reservationDate[]" placeholder="예약특정일자" value="<?= $r_row[p_limitdate] ?>">
											</div>
											<button type="button" class="btn btn-default btn-xs js-removeReservationDate"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
										</div>
							  <?php
							      endwhile;
							      if ($cntR == 0) {  	   
						      ?>
                                       <div class="col-sm-12 js-reservationDateSet">
											<div class="form-group removeBottomMargin">
												<label class="sr-only" for="reservationDate">예약특정일자</label>
												<input type="date" class="form-control" id="reservationDate" name="reservationDate[]" placeholder="예약특정일자" value="">
											</div>
											<button type="button" class="btn btn-default btn-xs js-removeReservationDate hidden"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
										</div>
							  <?php

								  }
						       ?>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">여행간단제목</td>
							<td colspan="10">
								<div class="form-group removeBottomMargin">
									<label class="sr-only" for="prodDesc">여행간단제목</label>
									<input type="text" class="form-control" id="prodDesc" name="prodDesc" placeholder="여행간단제목" value="<?=$prodInfo[p_sdesc]?>">
								</div>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">현재상품배너이미지</td>
							<td colspan="4">
                               <?php if($prodInfo[p_mimg]): ?>
									 <img width='140px' height='140px' alt="140x140" data-src="product_img/<?= $prodInfo[p_mimg] ?>" class="img-thumbnail js-placeholderImg" src="product_img/<?= $prodInfo[p_mimg] ?>" data-holder-rendered="true">
							   <?php endif; ?>
							</td>
							<td colspan="2" class="active text-center formHeader">이미지업로드</td>
							<td colspan="4">
								<div class="col-sm-6">
									<div class="form-group removeBottomMargin">
										<label class="sr-only" for="p_mimg">현재상품배너이미지</label>
										<input type="file" class="form-control" id="p_mimg" name="p_mimg" placeholder="현재상품배너이미지">
									</div>
								</div>
								<?php if($prodInfo[p_mimg]): ?>
									<div class="col-sm-6 form-inline text-right">
										<input type="checkbox" id="photo_delm" name="photo_delm" value="1">삭제 
										<span class=""><?= $prodInfo[p_mimg] ?></span>
									</div>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">현재서브이미지 - 1</td>
							<td colspan="4">
								<?php if($prodInfo[p_img1]): ?>
									 <img width='140px' height='140px' alt="140x140" data-src="product_img/<?= $prodInfo[p_img1] ?>" class="img-thumbnail js-placeholderImg" src="product_img/<?= $prodInfo[p_img1] ?>" data-holder-rendered="true">
							    <?php endif; ?>
							</td>
							<td colspan="2" class="active text-center formHeader">이미지업로드 - 1</td>
							<td colspan="4">
								<div class="col-sm-6">
									<div class="form-group removeBottomMargin">
										<label class="sr-only" for="p_img1">현재서브이미지 - 1</label>
										<input type="file" class="form-control" id="p_img1" name="p_img1" placeholder="현재서브이미지 - 1">
									</div>
								</div>
								<?php if($prodInfo[p_img1]): ?>
									<div class="col-sm-6 form-inline text-right">
										<input type="checkbox" id="photo_del1" name="photo_del1" value="1">삭제 
										<span class=""><?= $prodInfo[p_img1] ?></span>
									</div>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">현재서브이미지 - 2</td>
							<td colspan="4">
								<?php if($prodInfo[p_img2]): ?>
									 <img width='140px' height='140px' alt="140x140" data-src="product_img/<?= $prodInfo[p_img2] ?>" class="img-thumbnail js-placeholderImg" src="product_img/<?= $prodInfo[p_img2] ?>" data-holder-rendered="true">
							 	<?php endif; ?>
							</td>
							<td colspan="2" class="active text-center formHeader">이미지업로드 - 2</td>
							<td colspan="4">
								<div class="col-sm-6">
									<div class="form-group removeBottomMargin">
										<label class="sr-only" for="p_img2">현재서브이미지 - 2</label>
										<input type="file" class="form-control" id="p_img2" name="p_img2" placeholder="현재서브이미지 - 2">
									</div>
									
								</div>
								<?php if($prodInfo[p_img2]): ?>
									<div class="col-sm-6 form-inline text-right">
											<input type="checkbox" id="photo_del2" name="photo_del2" value="1">삭제 
											<span class=""><?= $prodInfo[p_img2] ?></span>
									 </div>
								 <?php endif; ?>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">현재서브이미지 - 3</td>
							<td colspan="4">
								<?php if($prodInfo[p_img3]): ?>
									 <img width='140px' height='140px' alt="140x140" data-src="product_img/<?= $prodInfo[p_img3] ?>" class="img-thumbnail js-placeholderImg" src="product_img/<?= $prodInfo[p_img3] ?>" data-holder-rendered="true">
							 	<?php endif; ?>
							</td>
							<td colspan="2" class="active text-center formHeader">이미지업로드 - 3</td>
							<td colspan="4">
								<div class="col-sm-6">
									<div class="form-group removeBottomMargin">
										<label class="sr-only" for="p_img3">현재서브이미지 - 3</label>
										<input type="file" class="form-control" id="p_img3" name="p_img3" placeholder="현재서브이미지 - 3">
									</div>
								</div>
								<?php if($prodInfo[p_img3]): ?>
									<div class="col-sm-6 form-inline text-right">
											<input type="checkbox" id="photo_del3" name="photo_del3" value="1">삭제 
											<span class=""><?= $prodInfo[p_img3] ?></span>
									 </div>
								 <?php endif; ?>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">현재서브이미지 - 4</td>
							<td colspan="4">
								<?php if($prodInfo[p_img4]): ?>
									 <img width='140px' height='140px' alt="140x140" data-src="product_img/<?= $prodInfo[p_img4] ?>" class="img-thumbnail js-placeholderImg" src="product_img/<?= $prodInfo[p_img4] ?>" data-holder-rendered="true">
							 	<?php endif; ?>
							</td>
							<td colspan="2" class="active text-center formHeader">이미지업로드 - 4</td>
							<td colspan="4">
								<div class="col-sm-6">
									<div class="form-group removeBottomMargin">
										<label class="sr-only" for="p_img4">현재서브이미지 - 4</label>
										<input type="file" class="form-control" id="p_img4" name="p_img4" placeholder="현재서브이미지 - 4">
									</div>
								</div>
								<?php if($prodInfo[p_img4]): ?>
									<div class="col-sm-6 form-inline text-right">
											<input type="checkbox" id="photo_del4" name="photo_del4" value="1">삭제 
											<span class=""><?= $prodInfo[p_img4] ?></span>
									 </div>
								 <?php endif; ?>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">현재서브이미지 - 5</td>
							<td colspan="4">
								<?php if($prodInfo[p_img5]): ?>
									<img width='140px' height='140px' alt="140x140" data-src="product_img/<?= $prodInfo[p_img5] ?>" class="img-thumbnail js-placeholderImg" src="product_img/<?= $prodInfo[p_img5] ?>" data-holder-rendered="true">
							 	<?php endif; ?>
							</td>
							<td colspan="2" class="active text-center formHeader">이미지업로드 - 5</td>
							<td colspan="4">
								<div class="col-sm-6">
									<div class="form-group removeBottomMargin">
										<label class="sr-only" for="p_img5">현재서브이미지 - 5</label>
										<input type="file" class="form-control" id="p_img5" name="p_img5" placeholder="현재서브이미지 - 5">
									</div>
								</div>
								<?php if($prodInfo[p_img5]): ?>
									<div class="col-sm-6 form-inline text-right">
											<input type="checkbox" id="photo_del5" name="photo_del5" value="1">삭제 
											<span class=""><?= $prodInfo[p_img5] ?></span>
									 </div>
								 <?php endif; ?>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">현재서브이미지 - 6</td>
							<td colspan="4">
								<?php if($prodInfo[p_img6]): ?>
									 <img width='140px' height='140px' alt="140x140" data-src="product_img/<?= $prodInfo[p_img6] ?>" class="img-thumbnail js-placeholderImg" src="product_img/<?= $prodInfo[p_img6] ?>" data-holder-rendered="true">
							 	<?php endif; ?>
							</td>
							<td colspan="2" class="active text-center formHeader">이미지업로드 - 6</td>
							<td colspan="4">
								<div class="col-sm-6">
									<div class="form-group removeBottomMargin">
										<label class="sr-only" for="p_img6">현재서브이미지 - 6</label>
										<input type="file" class="form-control" id="p_img6" name="p_img6" placeholder="현재서브이미지 - 6">
									</div>
								</div>
								<?php if($prodInfo[p_img6]): ?>
									<div class="col-sm-6 form-inline text-right">
											<input type="checkbox" id="photo_del6" name="photo_del6" value="1">삭제 
											<span class=""><?= $prodInfo[p_img6] ?></span>
									 </div>
								 <?php endif; ?>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">현재서브이미지 - 7</td>
							<td colspan="4">
								<?php if($prodInfo[p_img7]): ?>
									 <img width='140px' height='140px' alt="140x140" data-src="product_img/<?= $prodInfo[p_img7] ?>" class="img-thumbnail js-placeholderImg" src="product_img/<?= $prodInfo[p_img7] ?>" data-holder-rendered="true">
							 	<?php endif; ?>
							</td>
							<td colspan="2" class="active text-center formHeader">이미지업로드 - 7</td>
							<td colspan="4">
								<div class="col-sm-6">
									<div class="form-group removeBottomMargin">
										<label class="sr-only" for="p_img7">현재서브이미지 - 7</label>
										<input type="file" class="form-control" id="p_img7" name="p_img7" placeholder="현재서브이미지 - 7">
									</div>
								</div>
								<?php if($prodInfo[p_img7]): ?>
									<div class="col-sm-6 form-inline text-right">
											<input type="checkbox" id="photo_del7" name="photo_del7" value="1">삭제 
											<span class=""><?= $prodInfo[p_img7] ?></span>
									 </div>
								 <?php endif; ?>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">현재서브이미지 - 8</td>
							<td colspan="4">
								<?php if($prodInfo[p_img8]): ?>
									 <img width='140px' height='140px' alt="140x140" data-src="product_img/<?= $prodInfo[p_img8] ?>" class="img-thumbnail js-placeholderImg" src="product_img/<?= $prodInfo[p_img8] ?>" data-holder-rendered="true">
							 	<?php endif; ?>
							</td>
							<td colspan="2" class="active text-center formHeader">이미지업로드 - 8</td>
							<td colspan="4">
								<div class="col-sm-6">
									<div class="form-group removeBottomMargin">
										<label class="sr-only" for="p_img8">현재서브이미지 - 8</label>
										<input type="file" class="form-control" id="p_img8" name="p_img8" placeholder="현재서브이미지 - 8">
									</div>
								</div>
								<?php if($prodInfo[p_img8]): ?>
									<div class="col-sm-6 form-inline text-right">
											<input type="checkbox" id="photo_del8" name="photo_del8" value="1">삭제 
											<span class=""><?= $prodInfo[p_img8] ?></span>
									 </div>
								 <?php endif; ?>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">현재서브이미지 - 9</td>
							<td colspan="4">
								<?php if($prodInfo[p_img9]): ?>
									 <img width='140px' height='140px' alt="140x140" data-src="product_img/<?= $prodInfo[p_img9] ?>" class="img-thumbnail js-placeholderImg" src="product_img/<?= $prodInfo[p_img9] ?>" data-holder-rendered="true">
							 	<?php endif; ?>
							</td>
							<td colspan="2" class="active text-center formHeader">이미지업로드 - 9</td>
							<td colspan="4">
								<div class="col-sm-6">
									<div class="form-group removeBottomMargin">
										<label class="sr-only" for="p_img9">현재서브이미지 - 9</label>
										<input type="file" class="form-control" id="p_img9" name="p_img9" placeholder="현재서브이미지 - 9">
									</div>
								</div>
								<?php if($prodInfo[p_img9]): ?>
									<div class="col-sm-6 form-inline text-right">
											<input type="checkbox" id="photo_del9" name="photo_del9" value="1">삭제 
											<span class=""><?= $prodInfo[p_img9] ?></span>
									 </div>
								 <?php endif; ?>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">현재서브이미지 - 10</td>
							<td colspan="4">
								<?php if($prodInfo[p_img10]): ?>
									 <img width='140px' height='140px' alt="140x140" data-src="product_img/<?= $prodInfo[p_img10] ?>" class="img-thumbnail js-placeholderImg" src="product_img/<?= $prodInfo[p_img10] ?>" data-holder-rendered="true">>
							 	<?php endif; ?>
							</td>
							<td colspan="2" class="active text-center formHeader">이미지업로드 - 10</td>
							<td colspan="4">
								<div class="col-sm-6">
									<div class="form-group removeBottomMargin">
										<label class="sr-only" for="p_img10">현재서브이미지 - 10</label>
										<input type="file" class="form-control" id="p_img10" name="p_img10" placeholder="현재서브이미지 - 10">
									</div>
								</div>
								<?php if($prodInfo[p_img10]): ?>
									<div class="col-sm-6 form-inline text-right">
											<input type="checkbox" id="photo_del10" name="photo_del10" value="1">삭제 
											<span class=""><?= $prodInfo[p_img10] ?></span>
									 </div>
								 <?php endif; ?>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">4줄간단설명</td>
							<td colspan="10">
								<textarea class="form-control" rows="4" name="p4desc" ><?= $prodInfo[p_4sdesc] ?></textarea>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">포함사항</td>
							<td colspan="10">
								<textarea class="form-control" rows="4" name="pinclude"><?= $prodInfo[p_include] ?></textarea>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">불포함사항</td>
							<td colspan="10">
								<textarea class="form-control" rows="4" name="pninclude"><?= $prodInfo[p_uninclude] ?></textarea>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">선택관광</td>
							<td colspan="10">
								<textarea class="form-control" rows="4" name="poption"><?= $prodInfo[p_otrip] ?></textarea>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader" >준비물</td>
							<td colspan="10">
									<textarea class="form-control" rows="4" name="pprepare"><?= $prodInfo[p_prepare] ?></textarea>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">여행참고사항</td>
							<td colspan="10">
								<textarea class="form-control js-tripNote js-ckEditor" name="pref"><?= $prodInfo[p_ref] ?></textarea>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">이용특전</td>
							<td colspan="10">
								<textarea class="form-control js-specialBenefit js-ckEditor" name="pspecial"><?= $prodInfo[p_spec] ?></textarea>
							</td>
						</tr>
						
						<tr>
							<td colspan="2" class="active text-center formHeader">노출여부</td>
							<td colspan="10">
								<label class="radio-inline">
									<input type="radio" name="exposure" id="immediately" value="y" <?php if ($prodInfo[p_display] == "y" ) echo "checked"; ?>> 바로노출
								</label>
								<label class="radio-inline">
									<input type="radio" name="exposure" id="draft" value="n" <?php if ($prodInfo[p_display] == "n" ) echo "checked"; ?>> 임시저장
								</label>
								<div class="radio-inline">
									<a href="#">미리보기 링크</a>
								</div>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">즉시결제여부</td>
							<td colspan="10">
								<label class="radio-inline">
									<input type="radio" name="purchasable" id="purchasable" value="y" <?php if ($prodInfo[p_pay] == "y" ) echo "checked"; ?>> 즉시결제가능
								</label>
								<label class="radio-inline">
									<input type="radio" name="purchasable" id="consultingRequired" value="n" <?php if ($prodInfo[p_pay] == "n" ) echo "checked"; ?>> 문의후 결제
								</label>
							</td>
						</tr>
					</tbody>
				</table>
				<div class="row">
					<div class="col-sm-6 col-sm-offset-6 text-right">
						<button type="submit" class="btn btn-xs btn-default js-formSave">상품저장</button>
						<?php if ($pcode!="") { ?>
						<button type="button" class="btn btn-xs btn-default js-formDelete" OnClick="javascript:pdel()">상품삭제</button>
						<button type="button" class="btn btn-xs btn-default js-openSchedule" data-toggle="modal" data-target=".js-openScheduleModal">일정표작성</button>
						<?php } ?>
					</div>
				</div>
			</form>

			<div class="modal fade js-openScheduleModal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
				<div class="modal-dialog modal-lg modal-full-width" role="document">
					<div class="modal-content">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
							<h4 class="modal-title" id="gridSystemModalLabel">일정표</h4>
						</div>
						<div class="modal-body">
							<form action="<?= $PHP_SELF ?>?division=<?= $division ?>&pdx=<?= $pdx ?>&sub=<?= $sub ?>&ty=<?= $ty ?>&pcode=<?= $pcode ?>" name="frmproductschedule" id="frmproductschedule" method="post">
								<input type="hidden" name="mode" value="scheduleSave">
								<input type="hidden" name="pcode" value="<?= $pcode ?>">
								<div class="row">
									<div class="col-sm-6">
										<table class="table table-bordered table-condensed ptTable formSchedule scheduleHeader">
											<tbody>
												<tr>
													<td colspan="2" class="active text-center formHeader">상품명/코드</td>
													<td colspan="10"><?=$prodInfo[p_name]?>– <?=$pcode?> </td>
												</tr>
												<tr>
													<td colspan="2" class="active text-center formHeader">여행기간</td>
													<td colspan="10" class="js-formScheduleTourLength"><?=$prodInfo[p_day]?> 일</td>
												</tr>
											</tbody>
										</table>
									</div>
									<div class="col-sm-6 text-right">
										<br />
										<br />
										<button type="submit" class="btn btn-xs btn-default js-scheduleSave">일정표등록</button>
									</div>
								</div>
								<div class="row">
									<div class="col-sm-12">
										<table class="table table-bordered table-condensed ptTable formSchedule scheduleBody js-scheduleBody">
											<tbody>
												<tr>
													<td colspan="2" class="active text-center formHeader">상품명/코드</td>
													<td colspan="4" class="active text-center formHeader">경유지</td>
													<td colspan="6" class="active text-center formHeader">일정설명</td>
												</tr>
												<!------ --------->
												<?php
													$d_qry2 = "select * from product_details where p_code = '$pcode' order by day asc";
													$d_rst2 = mysql_query($d_qry2);
													if (mysql_affected_rows() > "0") {
													  $n =1;
													  while($d_row2 = mysql_fetch_assoc($d_rst2)):
												?>
														<tr class="day-<?=$n?>">
															<td colspan="2" class="formHeader text-center">
																<font color='#131176'?><?=$n?>일차</font>
																<br />
																<br />
																<br />
																<br />
																<br />
																<br />
																<br />
																<br />
																<button type="button" class="btn btn-xs btn-default js-addSingleDayTour">단일투어추가</button>
															</td>
															<td colspan="4" class="formHeader">
																<textarea name="tourRoute[]" class="textarea-halfSize js-tourRoute" rows="10"><?=$d_row2[area]?></textarea>
																<?php 
																	$qry1 = "select * from product_details_local where p_code='$pcode' && day='$n' order by position asc";
																	$rst1 = mysql_query($qry1,$dbConn);
																	$l = 0;
																	 if (mysql_affected_rows() > "0") { 
																		while($row1 = mysql_Fetch_assoc($rst1)) {
																			$lcode=getProductMaster($row1[local_code]);
														  		?>
																			<div class="form-inline js-tourSet">
																				<button type="button" class="btn btn-xs btn-default js-openSingleDayTourSelection" data-toggle="modal" data-target=".js-openSingleDayTourModal">선택</button>
																				<div class="form-group removeBottomMargin">
																					<input type="text" class="form-control js-tourName" name="singleDayTourName[<?=$n?>][]" placeholder="단일투어" value="<?=$lcode[p_name]?>">
																					<input type="hidden" name="singleDayTour[<?=$n?>][]" class="js-tourCode" value="<?=$row1[local_code]?>">
																				</div>
																				<div class="input-group removeBottomMargin">
																					<input type="text" class="form-control text-right" name="percentage[<?=$n?>][]" placeholder="배분율" value="<?=$row1[r_rate]?>">
																					<div class="input-group-addon">%</div>
																				</div>
																				<button type="button" class="btn btn-default btn-xs js-removeSingleDayTour "><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
																			</div>
																<?php
																	      $l++;
																		}
																	 } else {
																?>
																	    <div class="form-inline js-tourSet">
																				<button type="button" class="btn btn-xs btn-default js-openSingleDayTourSelection" data-toggle="modal" data-target=".js-openSingleDayTourModal">선택</button>
																				<div class="form-group removeBottomMargin">
																					<input type="text" class="form-control js-tourName" name="singleDayTourName[<?=$n?>][]" placeholder="단독투어" value="">
																					<input type="hidden" name="singleDayTour[<?=$n?>][]" class="js-tourCode" value="">
																				</div>
																				<div class="input-group removeBottomMargin">
																					<input type="text" class="form-control text-right" name="percentage[<?=$n?>][]" placeholder="배분율" value="">
																					<div class="input-group-addon">%</div>
																				</div>
																				<button type="button" class="btn btn-default btn-xs js-removeSingleDayTour hidden"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
																	    </div>
																<?php
																		
																	 } 
																?>
															</td>
															<td colspan="6">
																<textarea name="tourDesc[]" class="js-tourDesc js-ckEditor"><?=$d_row2[content]?></textarea>
															</td>
														</tr>
														<tr class="day-<?=$n?>">
															<td colspan="2" class="formHeader text-center">숙박호텔</td>
															<td colspan="10">
																<div class="form-group removeBottomMargin">
																	<label class="sr-only" for="hotelName1">호텔명</label>
																	<input type="text" class="form-control" id="hotelName1" name="hotelName[]" placeholder="호텔명" value="<?=$d_row2[hotel]?>">
																</div>
															</td>
														</tr>
														<tr class="day-<?=$n?>">
															<td colspan="2" class="formHeader text-center">식사선택</td>
															<td colspan="10">
																<label class="form-inline js-tourSet">
																	<input type=text name="meal1[]" class="form-control text-center" size=3 value="<?= $d_row2[meal_black] ?>">&nbsp;조식&nbsp;
																
																	<input type=text name="meal2[]" class="form-control text-center" size=3 value="<?= $d_row2[meal_lunch] ?>">&nbsp;중식&nbsp;&nbsp;
																
																	<input type=text name="meal3[]" class="form-control text-center" size=3 value="<?= $d_row2[meal_dinner] ?>">&nbsp;석식&nbsp;&nbsp;
															
																	<input type=text name="meal4[]" class="form-control text-center" size=3 value="<?= $d_row2[meal_black1] ?>">&nbsp;조식(자유식)&nbsp;&nbsp;
																
																	<input type=text name="meal5[]" class="form-control text-center" size=3 value="<?= $d_row2[meal_lunch1] ?>">&nbsp;중식(자유식)&nbsp;&nbsp;
																
																	<input type=text name="meal6[]" class="form-control text-center" size=3 value="<?= $d_row2[meal_dinner1] ?>">&nbsp;석식(자유식)
																</label>
															</td>
														</tr>
												
													<?php
														$n++;
														endwhile;
													?>
												<?php
												  } else {
													 for($k=1; $k<=$prodInfo[p_day]; $k++):
												?>
														<tr class="day-<?=$k?>">
															<td colspan="2" class="formHeader text-center">
																<font color='#131176'?><?=$k?>일차</font>
																<br />
																<br />
																<br />
																<br />
																<br />
																<br />
																<br />
																<br />
																<button type="button" class="btn btn-xs btn-default js-addSingleDayTour">단일투어추가</button>
															</td>
															<td colspan="4" class="formHeader">
																<textarea name="tourRoute[]" class="textarea-halfSize js-tourRoute" rows="10"></textarea>
																<div class="form-inline js-tourSet">
																	<button type="button" class="btn btn-xs btn-default js-openSingleDayTourSelection" data-toggle="modal" data-target=".js-openSingleDayTourModal">선택</button>
																	<div class="form-group removeBottomMargin">
																		<input type="text" class="form-control js-tourName" name="singleDayTourName[<?=$k?>][]" placeholder="단일투어" value="">
																		<input type="hidden" name="singleDayTour[<?=$k?>][]" class="js-tourCode" value="">
																	</div>
																	<div class="input-group removeBottomMargin">
																		<input type="text" class="form-control text-right" name="percentage[<?=$k?>][]" placeholder="배분율(숫자)" value="">
																		<div class="input-group-addon">%</div>
																	</div>
																	<button type="button" class="btn btn-default btn-xs js-removeSingleDayTour hidden"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
																</div>
															</td>
															<td colspan="6">
																<textarea name="tourDesc[]" class="js-tourDesc js-ckEditor"></textarea>
															</td>
														</tr>
														<tr class="day-<?=$k?>">
															<td colspan="2" class="formHeader text-center">숙박호텔</td>
															<td colspan="10">
																<div class="form-group removeBottomMargin">
																	<label class="sr-only" for="hotelName1">호텔명</label>
																	<input type="text" class="form-control" id="hotelName1" name="hotelName[]" placeholder="호텔명">
																</div>
															</td>
														</tr>
														<tr class="day-<?=$k?>">
															<td colspan="2" class="formHeader text-center">식사선택</td>
															<td colspan="10">
															   <label class="form-inline js-tourSet">
																   <input type=text name="meal1[]" class="form-control text-center" size=3 value="">&nbsp;조식&nbsp;
																
																	<input type=text name="meal2[]" class="form-control text-center" size=3 value="">&nbsp;중식&nbsp;&nbsp;
																
																	<input type=text name="meal3[]" class="form-control text-center" size=3 value="">&nbsp;석식&nbsp;&nbsp;
															
																	<input type=text name="meal4[]" class="form-control text-center" size=3 value="">&nbsp;조식(자유식)&nbsp;&nbsp;
																
																	<input type=text name="meal5[]" class="form-control text-center" size=3 value="">&nbsp;중식(자유식)&nbsp;&nbsp;
																
																	<input type=text name="meal6[]" class="form-control text-center" size=3 value="">&nbsp;석식(자유식)
																</label>
															</td>
														</tr>
												<?php
													endfor;
												  } 
												?>
												<!------- -------->
											</tbody>
										</table>
									</div>
								</div>
							</form>
						</div>
						<div class="modal-footer">
							<!-- <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
							<button type="button" class="btn btn-primary">Save changes</button> -->
						</div>
					</div><!-- /.modal-content -->
				</div><!-- /.modal-dialog -->
			</div><!-- /.modal -->

			<div class="modal fade js-openSingleDayTourModal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
				<div class="modal-dialog modal-lg modal-in-modal" role="document">
					<div class="modal-content">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
							<h4 class="modal-title" id="gridSystemModalLabel">단일투어</h4>
						</div>
						<div class="modal-body">
							<div class="row">
								<div class="col-sm-12">
									<input type="text" class="form-control removeBottomMargin js-searchSingleDayTour" name="sskeyword" placeholder="검색">
								</div>
							</div>
							<div class="row overflowBody">
								<div class="col-sm-12">
								<?php
								    
								   $qry1 = "select * from product_master where 1=1 && p_type='1' order by p_name asc ";
								   $rst1 = mysql_query($qry1,$dbConn);
									//echo $qry1;	
								   while($row1 = mysql_Fetch_assoc($rst1)){
								?>
										 <div class="radio">
											<label><!-- data-search-str needs to be in all lower case -->
												<input type="radio" name="singleDayTour[]" value="<?=$row1[p_code]?>" data-tour-name="<?=$row1[p_name]?>" data-tour-code="<?=$row1[p_code]?>" data-search-str="<?=$row1[p_code]?> <?=$row1[p_name]?>">
												[<?=$row1[p_code]?>] <?=$row1[p_name]?>
											</label>
										</div>
								<?php
								   }
								?>
									
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-default" data-dismiss="modal">취소</button>
							<button type="button" class="btn btn-primary js-saveSelection">선택사항 저장</button>
						</div>
					</div><!-- /.modal-content -->
				</div><!-- /.modal-dialog -->
			</div><!-- /.modal -->

		</div>
	</div>
    <?php
		include "include/side_m.php"
	?>
	<script src="ckeditor/ckeditor.js"></script>
    <script>
			$(document).ready(function () {
				$.ajaxSetup({async:false});
				pt.initProductDetailForm()
				pt1.initProductDetailForm2()
				$(".randcomp").chosen({
					width: '100%'
				});
			})
			function chksave() {

                  if ($("#area1").val() == "") {
						alert("상품분류 1을 입력하세요!");
						$("#area1").focus();
						return false;
				  }
				  if ($("#area2").val() == "") {
						alert("상품분류 2를 입력하세요!");
						$("#area2").focus();
						return false;
				  }
				  if ($("#prodName").val() == "") {
						alert("상품명을 입력하세요!");
						$("#prodName").focus();
						return false;
				  }
				  
				  if ($("#tourLength").val() == "") {
						alert("상품기간을 입력하세요!");
						$("#tourLength").focus();
						return false;
				  }

				  if ($("#p_own").val() == "") {
						alert("상품소유사를 입력하세요!");
						$("#p_own").focus();
						return false;
				  }
				  if ($("#maxPerCar").val() == "") {
						alert("투어정원을 입력하세요!");
						$("#maxPerCar").focus();
						return false;
				  }
				  
				  return true;

			}
			function pdel() {
					if (confirm("삭제할까요?") == true) {

						$("#mode").val("del");
						$("#frmproduct").submit();
					}
			}
	</script>
    </body>
</html>
