<?php
   ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL & ~E_NOTICE);
   
    include "include/header.php";
    //include "include/inc_base.php";
	if ($_COOKIE[MEMLOGIN_ADMIN_HELLO] !="") {
	} else {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}

	
	// 안전한 입력 초기화 (GET/POST 혼합)
	$division = $_GET['division'] ?? '';
	$pdx      = $_GET['pdx'] ?? '';
	$sub      = $_GET['sub'] ?? '';

	$mode     = $_POST['mode'] ?? ''; // save일 때만 POST
	$st       = $_GET['st']    ?? ($_POST['sdate'] ?? '');   // 출발일
	$pcode    = $_GET['pcode'] ?? ($_POST['pcode'] ?? '');
	$gcode    = $_POST['gcode'] ?? ($_GET['gcode'] ?? '');

	// sctour는 pcode+st 기준으로 조회
	$sctour = getTourInfo2($pcode, $st);

	// 조회용 grand_eCode는 sctour가 우선, 없으면 폼에서 넘어온 gcode 사용
	$curGcode = $sctour['grand_eCode'] ?: $gcode;

	$pcnt = getReserveInfoCnt($pcode,$st);				
	if ($pcnt[cnt] =="") {
		$pcnt[cnt] = 0;
	}
    $pInfo = getProductMaster($pcode);
	//echo '<pre>';
	//print_r($rnum);
	//echo '</pre>';
	//exit;
	if ($mode == "save") {
		// 필수값 점검
		if (empty($gcode) || empty($sdate) || empty($pcode)) {
			Misc::jvAlert("필수값 누락(gcode/sdate/pcode).",""); 
			exit;
		}

		$eventcnt = is_array($rnum) ? count($rnum) : 0;

		// 디버그: 우측으로 이동한 행이 하나도 없으면 안내
		$movedCnt = 0;
		if (is_array($bnum)) {
			foreach ($bnum as $bv) { if (!empty($bv)) { $movedCnt++; } }
		}
		if ($eventcnt === 0 || $movedCnt === 0) {
			Misc::jvAlert("오른쪽 박스로 이동된 항목이 없습니다. 먼저 좌측에서 우측으로 이동하세요.","");
			exit;
		}

		// 트랜잭션
		$dbConn->begin_transaction();

		try {
			// 1) 기존 전체 삭제
			$qry1 = "DELETE FROM tour_car WHERE grand_eCode = ? AND stDate = ? AND p_code = ?";
			$stmt = $dbConn->prepare($qry1);
			if (!$stmt) { throw new Exception("DELETE prepare 실패: ".$dbConn->error); }
			if (!$stmt->bind_param('sss', $gcode, $sdate, $pcode)) { throw new Exception("DELETE bind 실패: ".$stmt->error); }
			if (!$stmt->execute()) { throw new Exception("DELETE execute 실패: ".$stmt->error); }
			$stmt->close();

			// 2) 재삽입
			$qry2 = "INSERT INTO tour_car (
						grand_eCode, 
						sub_eNum, 
						sub_eCode, 
						reserveCode, 
						p_code, 
						p_name, 
						stDate, 
						bus_num, 
						romm_num,
						rand_id,
						rev_nm,
						room_type,
						sex, 
						picCode, 
						userid,
						h_seq,
						wdate
					) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())";

			$stmt = $dbConn->prepare($qry2);
			if (!$stmt) { throw new Exception("INSERT prepare 실패: ".$dbConn->error); }

			// ★ 타입 문자열을 실제 파라미터 타입과 맞춤
			//  1  $gcode          s
			//  2  $sub_eventNum   i
			//  3  $sub_eventCode  s
			//  4  $rv             s
			//  5  $pcode          s
			//  6  $pname          s
			//  7  $sdate          s
			//  8  $bus            i (정수 가정)
			//  9  $roomnum        i (정수 가정)
			// 10  $rnd            s
			// 11  $rn             s
			// 12  $rmty           s
			// 13  $rsx            s
			// 14  $pk             s
			// 15  $uid            s
			// 16  $hseqv          i (정수 가정)
			$types = "sissssiisssssssi";

			for ($r = 0; $r < $eventcnt; $r++) {
				// 오른쪽 박스로 이동된 항목만(bnum이 채워진 것만) 저장
				$bus    = isset($bnum[$r]) ? trim($bnum[$r]) : '';
				if ($bus === '') { continue; }

				// 박스에서 넘어온 서브코드(가이드 배정에서 만들어진 것)
				$sub_eventCode = isset($ssnum[$r]) ? trim($ssnum[$r]) : '';
				if ($sub_eventCode === '') { continue; }

				// 숫자 필드 정리
				$sub_eventNum = 0; // 필요 시 getNumSevent($gcode,$sdate)로 대체 가능
				$roomnum      = isset($rnum[$r]) ? (int)$rnum[$r] : 0;
				$bus          = (int)$bus;
				$hseqv        = isset($hseq[$r]) ? (int)$hseq[$r] : 0;

				// 문자열 필드 정리
				$rv   = $rev[$r]   ?? '';
				$rn   = $revnm[$r] ?? '';
				$rmty = $roomt[$r] ?? '';
				$rsx  = $rsex[$r]  ?? '';
				$pk   = $pick[$r]  ?? '';
				$rnd  = $rand[$r]  ?? '';
				$uid  = $user_dbinfo['userid'] ?? '';

				if (!$stmt->bind_param(
					$types,
					$gcode,
					$sub_eventNum,
					$sub_eventCode,
					$rv,
					$pcode,
					$pname,
					$sdate,
					$bus,
					$roomnum,
					$rnd,
					$rn,
					$rmty,
					$rsx,
					$pk,
					$uid,
					$hseqv
				)) { throw new Exception("INSERT bind 실패: ".$stmt->error); }

				if (!$stmt->execute()) { throw new Exception("INSERT execute 실패: ".$stmt->error); }
			}
			$stmt->close();

			$dbConn->commit();
			Misc::jvAlert("업데이트 되었습니다!!","");

		} catch (Exception $e) {
			$dbConn->rollback();
			// 화면에도 보여주고, 서버 로그도 남김
			error_log("[car_assign save] ".$e->getMessage());
			Misc::jvAlert("저장 오류: ".$e->getMessage(),"");
		}
	}


	/*function reservelist() {
		global $dbConn,$pcode,$st,$num1;

		$qry1 = "select     seq_no, 
								grand_eCode, 
								reserveCode, 
								room_num, 
								sub_eCode, 
								p_code, 
								p_name, 
								stDate, 
								picCode, 
								tnm, 
								sex, 
								seq_t 
								from 
								hotelroom_assign
							 where stDate = '$st' && p_code = '$pcode' 
							 && tnm not in (select rev_nm from tour_car where stDate = '$st' && p_code = '$pcode')  order by room_num, seq_t asc";
		//echo $qry1;
		$rst1 = mysql_query($qry1,$dbConn);
		$num1 = mysql_num_rows($rst1);
		while($row1 = mysql_Fetch_assoc($rst1)){
			    $reserve_info2 = getReserveTrInfo($row1[reserveCode],$row1[tnm]);
			    if ($reserve_info2[room_type] == "1r1p") {
					$fimg = "1_1.jpg";
					$fmn = "1r1p";
				} else if ($reserve_info2[room_type] === "1r2p") {
					$fimg = "2_1.jpg";
					$fmn = "1r2p";
				} else if ($reserve_info2[room_type] == "1r3p") {
					$fimg = "3_1.jpg";
					$fmn = "1r3p";
				} else if ($reserve_info2[room_type] == "1r4p") {
					$fimg = "4_1.jpg";
					$fmn = "1r4p";
				} else if ($reserve_info2[room_type] == "1r5p") {
					$fimg = "5_1.jpg";
					$fmn = "1r5p";
				}
				//echo $reserve_info2[room_type];
				if ($row1[sex] == "man") {
					$sex= "<img src='img/ico/".$fimg."'><img src='img/ico/M_1.jpg'>";
				} else if ($row1[sex] == "female") {
					$sex = "<img src='img/ico/$fimg'><img src='img/ico/W_1.jpg'> ";

				} else if ($row1[sex] == "mfemale") {
					 $sex = "<img src='img/ico/$fimg'><img src='img/ico/WM_1.jpg'>";
				}
				
				$pickarr = explode("/",$row1[picCode]);
				$picknm=pickBaseInfo($pickarr[0],$pickarr[1]);
				$reserve_info = getReserveInfo($row1[reserveCode]);
				$prodInfo = getProductMaster($reserve_info[p_code]);
				if ($prodInfo[p_type] == 1) {
				   $pcap = "단일";
				} else if ($prodInfo[p_type] == 2) {
					$pcap = "복합";
				} else if ($prodInfo[p_type] == 3) {
					$pcap = "인바운드로컬합류";
				} else if ($prodInfo[p_type] == 4) {
					$pcap = "인바운드단독";
				} else if ($prodInfo[p_type] == 5) {
					$pcap = "대행투어";
				}
				if ($reserve_info[tour_type] == 3) {
				    $rcap = "협력사";
				} else {
					$rcap = "자사";
				}
				$rname=randname($reserve_info[rand_id]);
				if ($prodInfo[p_type] == 2) {
					  $reserve_info2 = getReserveInfo2($reserve_info[reserveCode],$st);
					  $picknm = pickBaseCode3($reserve_info2[meet_area]);
					 // echo $prodinfo[p_type]."111";
				} else {
					if (strstr($pcode, "ADD") != "") { 
						
						$picknmtmp = codebaseName($row1[picCode]);
						$picknm[pick_name] = $picknmtmp[comment];
						$picknm[pick_time] = "";
						$picknm[pick_code] = $row1[picCode];
					} else {
						
						$pickarr = explode("/",$row1[picCode]);
						$picknm=pickBaseInfo($pickarr[0],$pickarr[1]);
						$picknm[pick_code] = $row1[picCode];
					}
				}
				echo " <tr>
							<td align='center'><input type='checkbox' class='form-control' value='$row1[seq_no]'><input type='hidden' name='hseq[]' id='hseq' value='$row1[seq_t]'><input type='hidden' name='bnum[]' id='bnum1' value=''></td>
							<td>$row1[room_num]<input type='hidden' name='rnum[]' value='$row1[room_num]'></td>
							<td title='$reserve_info[p_name]'>$row1[reserveCode]<input type='hidden' name='rev[]' value='$row1[reserveCode]'></td>
							<td align='center' title='$reserve_info[p_name]'>$pcap</td>
							<td align='center' title='$rname[kor_name]'>$rcap</td>
							<td align='center'>$row1[tnm]<input type='hidden' name='revnm[]' value='$row1[tnm]'></td>
							
							<td align='center'>$sex<input type='hidden' name='roomt[]' value='$fmn'><input type='hidden' name='rsex[]' value='$row1[sex]'></td>
							<td>$picknm[pick_name]-$picknm[pick_time] <input type='hidden' name='pick[]' value='$picknm[pick_code]'></td>
						</tr>";
		}
	
	}
	*/
	function reservelist2() {
		global $dbConn,$pcode,$st,$num1,$troomcnt3;

		$qry1 = "select distinct   a.grand_eCode, 
								a.p_code, 
								a.p_name, 
								a.bus_cnt, 
								a.tour_pcnt,
								b.rand_id,
								a.stDate, 
								b.reserveCode,
								c.traveler_nm,
								c.pick_area,
								c.sextype,
								c.seqint,
								c.traveler_room,
								c.traveler_nm
								from 
								tour_master a, reserve_info b ,(select reserveCode,traveler_nm,pick_area,seqint,sextype,traveler_room from reserve_traveler) c
							 where a.stDate=b.stDate &&  a.p_code =  b.p_code && b.reserveCode=c.reserveCode  && a.stDate =  '$st' && a.p_code = '$pcode' 
							 && c.traveler_nm not in (select rev_nm from tour_car where stDate = '$st' && p_code = '$pcode') && (b.rev_status='DONE' && b.rev_status!='CANCEL')  order by b.reserveCode,c.seqint asc";
		//echo $qry1;
		$rst1 = $dbConn->query($qry1);
		$num1 = mysqli_num_rows($rst1);
		$k=1;
		while($row1 = $rst1->fetch_assoc()){
			    $reserve_info2 = getReserveTrInfo($row1[reserveCode],$row1[traveler_nm]);
			    if ($reserve_info2[room_type] == "1r1p") {
					$fimg = "1인1실";
					$fmn = "1r1p";
				} else if ($reserve_info2[room_type] === "1r2p") {
					$fimg = "2인1실";
					$fmn = "1r2p";
				} else if ($reserve_info2[room_type] == "1r3p") {
					$fimg = "3인1실";
					$fmn = "1r3p";
				} else if ($reserve_info2[room_type] == "1r4p") {
					$fimg = "4인1실";
					$fmn = "1r4p";
				} else if ($reserve_info2[room_type] == "1r5p") {
					$fimg = "5인1실";
					$fmn = "1r5p";
				}
				//echo $reserve_info2[room_type];
				/*if ($row1[sex] == "man") {
					$sex= "<img src='img/ico/".$fimg."'><img src='img/ico/M_1.jpg'>";
				} else if ($row1[sex] == "female") {
					$sex = "<img src='img/ico/$fimg'><img src='img/ico/W_1.jpg'> ";

				} else if ($row1[sex] == "mfemale") {
					 $sex = "<img src='img/ico/$fimg'><img src='img/ico/WM_1.jpg'>";
				}
				if ($row1[sextype] == "man") {
					$sex= "<i class='fa fa-male' style='font-size:18px;' aria-hidden='true'></i>";
				} else if ($row1[sextype] == "female") {
					$sex = "<i class='fa fa-female' style='font-size:18px;color:red;' aria-hidden='true'></i> ";

				} else if ($row1[sextype] == "mfemale") {
					 $sex = "<i class='fa fa-female' style='font-size:18px;color:red;' aria-hidden='true'></i> <i class='fa fa-male' style='font-size:18px;' aria-hidden='true'></i>";
				}*/
				
				//$pickarr = explode("/",$row1[picCode]);
				//$picknm=pickBaseInfo($pickarr[0],$pickarr[1]);
				$reserve_info = getReserveInfo($row1[reserveCode]);
				$prodInfo = getProductMaster($reserve_info[p_code]);
				if ($row1[rand_id] != "") {
					$rnm = randname($row1[rand_id]);
					$row1[book_pri] = $rnm[kor_name];
					//echo $rnm[kor_name]."<br/>";
					
				}
				if ($prodInfo[p_day] > 1) {
					if ($row1[sextype] == "man") {
						$sex= $fimg."<br />/남자";
					} else if ($row1[sextype] == "female") {
						$sex= $fimg."<br />/여자";
					} else if ($row1[sextype] == "mfemale") {
						 $sex= $fimg."<br />/혼성";
					}
				} else {
					if ($row1[sextype] == "man") {
						$sex= "남자";
					} else if ($row1[sextype] == "female") {
						$sex = "여자";

					} else if ($row1[sextype] == "mfemale") {
						 $sex = "혼성";
					}

				}
				if ($prodInfo[p_type] == 1) {
				   $pcap = "로컬";
				} else if ($prodInfo[p_type] == 2) {
					$pcap = "인바운드";
				} else if ($prodInfo[p_type] == 4) {
					$pcap = "인센티브";
				} else if ($prodInfo[p_type] == 5) {
					$pcap = "아웃바운드";
				}
				if ($reserve_info[tour_type] == 3) {
				    $rcap = "협력사";
				} else {
					$rcap = "자사";
				}
				
				$rname=randname($reserve_info[rand_id]);
				 
				if ($prodInfo[p_type] == 2) {
					  $reserve_info2 = getReserveInfo2($reserve_info[reserveCode],$st);
					  $picknm = pickBaseCode3($reserve_info2[meet_area]);
					 
					 
				} else {
					
						
						$pickarr = explode("/",$row1[pick_area]);
						//$picknm=pickBaseInfo($pickarr[0],$pickarr[1]);
						$picknm[pick_code] = $row1[picCode];
				
					    //echo $row1[pick_area]."<br />";
				}
				//echo $st."111";

				echo " <tr>
							<td align='center'><input type='checkbox' name='selected' class='form-control' value='$row1[seq_no]'><input type='hidden' name='hseq[]' id='hseq' value='$row1[seqint]'><input type='hidden' name='bnum[]' id='bnum1' value=''></td>
							<td>$row1[traveler_room]_$k<input type='hidden' name='rnum[]' value='$row1[traveler_room]'></td>
							<td title='$reserve_info[p_name]'>$row1[book_pri]<input type='hidden' name='rev[]' value='$row1[reserveCode]'><input type='hidden' name='rand[]' value='$row1[rand_id]'></td>
							<td align='center' title='$reserve_info[p_name]'>$pcap</td>
							<td align='center' title='$rname[kor_name]'>$rcap</td>
							<td align='center'>$row1[traveler_nm]<input type='hidden' name='revnm[]' value='$row1[traveler_nm]'></td>
							<td align='center'>$sex<input type='hidden' name='roomt[]' value='$fmn'><input type='hidden' name='rsex[]' value='$row1[sextype]'></td>
							<td>$picknm[pick_name]-$picknm[pick_time] <input type='hidden' name='pick[]' value='".addslashes($row1[pick_area])."'></td>
						</tr>";

			// echo $st."111";
			$k++;
		}
			
	}
	$troomcnt = 0;
	$troomcnt=getReserveRoomCnt($pcode,$st);
	$troomcnt3=getReserveRoomCnt($pcode,$st);
	$Buscnt=getBusCnt($sctour['grand_eCode'],$pcode,$st);
	if ($num1 != 0) {
		$troomcnt3= $troomcnt[rcnt];
	}
    $troomcnt1= $troomcnt[rcnt];
	if (($Buscnt == "") || ($Buscnt == 0)){
		$troomcnt2 = 0;
	} else {
		$troomcnt2 = $troomcnt1-1;
	}
	//echo $troomcnt2."TEST";
	function totbuslist() {
		global $dbConn, $pcode, $st, $Buscnt, $sctour, $troomcntr, $gcode;

		$content = '';

		// grand_eCode 안전 확보(guide-first에서 $sctour['grand_eCode']가 비어있을 수 있음)
		$grand = isset($sctour['grand_eCode']) && $sctour['grand_eCode'] !== '' ? $sctour['grand_eCode'] : $gcode;

		// 상품 기본정보(여정일수 등) 1회 조회
		$prodMaster = getProductMaster($pcode);
		$p_day_main = isset($prodMaster['p_day']) ? (int)$prodMaster['p_day'] : 1;

		for ($r = 1; $r <= (int)$Buscnt; $r++) {

			// 상단 좌측 작은 카운터: 전역명이 $troomcntr 이므로 사용 통일
			$content .= "<div class='row'>
							<div class='col-sm-1'>
								<div class='row'>".$troomcntr."</div>
								<div class='row text-center moveR' id='topRight_{$r}'><i class='splashy-arrow_medium_right'></i></div>
								<div class='row text-center moveL' id='topLeft_{$r}'><i class='splashy-arrow_medium_left'></i></div>
							</div>
							<div class='col-sm-11'>
								<table id='rightTableTop{$r}' class='table table-striped table-side-no-bordered table-hover table-condensed text-center rtab'>
									<thead>
										<tr>
											<th scope='col' colspan ='8'>차량{$r}</th>
										</tr>
										<tr>
											<th align='center'><input type='checkbox' class='form-control checkAll'></th>
											<th width='10%'>룸넘버</th>
											<th width='13%'>예약자</th>
											<th>구분</th>
											<th>예약</th>
											<th>고객명</th>
											<th width='10%'>성별</th>
											<th>탑승지</th>
										</tr>
									</thead>
									<tbody>";

			// 기존 쿼리 그대로 유지 (컬럼/조건 동일)
			$qry2 = "
				SELECT
					grand_eCode,
					sub_eNum,
					sub_eCode,
					reserveCode,
					p_code,
					p_name,
					stDate,
					bus_num,
					romm_num,
					rev_nm,
					room_type,
					sex,
					picCode,
					userid,
					h_seq,
					wdate
				FROM tour_car
				WHERE grand_eCode = '{$grand}'
				  AND stDate      = '{$dbConn->real_escape_string($st)}'
				  AND bus_num     = '{$r}'
				  AND p_code      = '{$dbConn->real_escape_string($pcode)}'
			";
			// echo $qry2."<br />";

			$rst1 = $dbConn->query($qry2);
			while ($row1 = $rst1->fetch_assoc()) {

				// 룸타입 라벨
				$fimg = ''; $fmn = '';
				if ($row1['room_type'] == "1r1p") { $fimg="독방";   $fmn="1r1p"; }
				else if ($row1['room_type'] == "1r2p") { $fimg="2인1실"; $fmn="1r2p"; }
				else if ($row1['room_type'] == "1r3p") { $fimg="3인1실"; $fmn="1r3p"; }
				else if ($row1['room_type'] == "1r4p") { $fimg="4인1실"; $fmn="1r4p"; }
				else if ($row1['room_type'] == "1r5p") { $fimg="5인1실"; $fmn="1r5p"; }

				// 예약/상품 정보
				$reserve_info = getReserveInfo($row1['reserveCode']);
				$prodInfo     = getProductMaster($reserve_info['p_code']);

				// 성별 표기 (여정일수 > 1이면 룸타입 라벨 포함)
				if ((int)($prodInfo['p_day'] ?? 1) > 1) {
					if ($row1['sex'] == "man")        $sex = $fimg."<br />/남자";
					else if ($row1['sex'] == "female")$sex = $fimg."<br />/여자";
					else if ($row1['sex'] == "mfemale") $sex = $fimg."<br />/혼성";
					else $sex = $fimg;
				} else {
					if     ($row1['sex'] == "man")     $sex = "남자";
					else if($row1['sex'] == "female")  $sex = "여자";
					else if($row1['sex'] == "mfemale") $sex = "혼성";
					else $sex = "";
				}

				// 상품구분 라벨
				$pcap = '';
				if     (($prodInfo['p_type'] ?? null) == 1) $pcap = "로컬";
				else if(($prodInfo['p_type'] ?? null) == 2) $pcap = "인바운드";
				else if(($prodInfo['p_type'] ?? null) == 4) $pcap = "인센티브";
				else if(($prodInfo['p_type'] ?? null) == 5) $pcap = "아웃바운드";

				// 룸 넘버 표기
				$rrnum = ($row1['romm_num'] == 1) ? "1" : $row1['romm_num'];

				// 예약 구분 라벨(자사/업체)
				$rcap = (isset($reserve_info['tour_type']) && (int)$reserve_info['tour_type'] == 3) ? "업체" : "자사";

				// 랜덤명 처리
				if (!empty($reserve_info['rand_id'])) {
					$rnm = randname($reserve_info['rand_id']);
					$row1['book_pri'] = $rnm['kor_name'] ?? ($row1['book_pri'] ?? '');
				}

				// 픽업명/시간
				if (($prodInfo['p_type'] ?? null) == 2) {
					$reserve_info2 = getReserveInfo2($reserve_info['reserveCode'], $st);
					$picknm = pickBaseCode3($reserve_info2['meet_area'] ?? '');
					$pick_name = $picknm['pick_name'] ?? '';
					$pick_time = $picknm['pick_time'] ?? '';
					$pick_code = $picknm['pick_code'] ?? '';
				} else {
					$pickarr  = explode("/", $row1['picCode'] ?? '');
					$picknm   = pickBaseInfo($pickarr[0] ?? '', $pickarr[1] ?? '');
					$picknm['pick_code'] = $row1['picCode'] ?? '';
					$pick_name = $picknm['pick_name'] ?? '';
					$pick_time = $picknm['pick_time'] ?? '';
					$pick_code = $picknm['pick_code'] ?? '';
				}

				$h_seq_safe = htmlspecialchars($row1['h_seq'] ?? '', ENT_QUOTES);
				$reserve_code_safe = htmlspecialchars($row1['reserveCode'] ?? '', ENT_QUOTES);
				$book_pri_safe = htmlspecialchars($row1['book_pri'] ?? '', ENT_QUOTES);
				$rev_nm_safe = htmlspecialchars($row1['rev_nm'] ?? '', ENT_QUOTES);
				$room_type_safe = htmlspecialchars($row1['room_type'] ?? '', ENT_QUOTES);
				$sex_safe = htmlspecialchars($row1['sex'] ?? '', ENT_QUOTES);

				$content .= " <tr>
					<td align='center'>
					  <input type='checkbox' class='form-control' value='{$h_seq_safe}'>
					  <input type='hidden' name='hseq[]' id='hseq' value='{$h_seq_safe}'>
					  <input type='hidden' name='bnum[]' id='bnum1' value='{$r}'>
					</td>
					<td>{$rrnum}
					  <input type='hidden' name='rnum[]' value='".htmlspecialchars($row1['romm_num'] ?? '', ENT_QUOTES)."'>
					  {$reserve_code_safe}
					</td>
					<td>{$book_pri_safe}
					  <input type='hidden' name='rev[]' value='{$reserve_code_safe}'><br />
					  <input type='hidden' name='rand[]' value='".htmlspecialchars($reserve_info['rand_id'] ?? '', ENT_QUOTES)."'>
					</td>
					<td align='center'>{$pcap}</td>
					<td align='center'>".$rcap."</td>
					<td align='center'>{$rev_nm_safe}
					  <input type='hidden' name='revnm[]' value='{$rev_nm_safe}'>
					</td>
					<td align='center'>".$sex."
					  <input type='hidden' name='roomt[]' value='{$room_type_safe}'>
					  <input type='hidden' name='rsex[]' value='{$sex_safe}'>
					</td>
					<td>".htmlspecialchars($pick_name, ENT_QUOTES)."-".htmlspecialchars($pick_time, ENT_QUOTES)."
					  <input type='hidden' name='pick[]' value='".htmlspecialchars($pick_code, ENT_QUOTES)."'>
					</td>
				</tr>";
			}

			// 집계(함수 시그니처 유지)
			$totp    = getbusperson($r, $grand, $st, $pcode);
			$totroom = getbusRoom($r, $grand, $st, $pcode);
			// 기존 코드에서 $psInfo 사용 → 미정의. 여정 1일이면 0, 그 외엔 getbusRoom 결과 사용.
			$trnum = ($p_day_main === 1) ? 0 : (int)($totroom ?? 0);

			$piccnt = getPicGr3($grand, $r);

			$content .= "</tbody>
							</table>
						</div>
						<div class='row'>
							<div class='col-sm-1'></div>
							<div class='col-sm-10'>
								<div class='panel-group'>
									<div class='panel panel-default'>
										<div class='panel-body custom_padding bg-info'>
											총인원 : ".(int)($totp['cnt'] ?? 0)."인 &nbsp;&nbsp;&nbsp;&nbsp;총 객실수 : {$trnum} 개<br />
											{$piccnt}
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>";
		}

		return $content;
	}


?>
	<div id="contentwrapper" class="reservationDetailForm">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb module">
				<ul>
					<li><a href="/"><i class="glyphicon glyphicon-home"></i></a></li>
					<li><a href="#">행사배정관리</a></li>
					<li>차량배정관리</li>
				</ul>
			</div>

			<form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&st=<?=$st?>&pcode=<?=$pcode?>" name="frmcar" method="post" onSubmit="return chksave()">
				<input type="hidden" name="mode" id="mode" value="save">
				<input type="hidden" name="gcode" id="gcode" value="<?=$sctour[grand_eCode]?>">
				<input type="hidden" name="pcode" id="pcode" value="<?=$sctour[p_code]?>">
				<input type="hidden" name="pname" id="pname" value='<?=$sctour[p_name]?>'>
				<input type="hidden" name="sdate" id="sdate" value="<?=$sctour[stDate]?>">
				<table id="custom_table" class="table table-bordered table-condensed gridSixteen reserveTable formDetail">
					<tbody>
						<tr>
                        <td colspan="2" class="active text-center formHeader">통합행사코드</td>
                        <td colspan="12"><?=$sctour[grand_eCode]?></td>
                    </tr>
					        			
                        <td colspan="2" class="active text-center formHeader">상품명</td>
                        <td colspan="12">[<?=$sctour[p_code]?>] <?=$sctour[p_name]?></td>
                    </tr>
                    <tr>
                        <td colspan="2" class="active text-center formHeader">출발일</td>
                        <td colspan="2"><?=$sctour[stDate]?></td>
                        
                        <td colspan="2" class="active text-center formHeader">투어정원</td>
                        <td colspan="2"><?=$sctour[tour_pcnt]?> 명 </td>
                        <td colspan="2" class="active text-center formHeader">예약인원</td>
                        <td colspan="2"><?=$pcnt[cnt]?> 명 </td>
                    </tr>
					
                        <td colspan="2" class="active text-center formHeader">예약인원</td>
                        <td colspan="12">
                            <label class="radio-inline">
                                <input type="radio" name="bookNumber" value="P" <?php if(strstr($sctour[r_status],"P")) echo "checked"; ?> disabled> 예약접수중
                            </label>
                            <label class="radio-inline">
                                <input type="radio" name="bookNumber" value="C" <?php if(strstr($sctour[r_status],"C")) echo "checked"; ?> disabled> 예약마감
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
                                            <input type="radio" name="eventStatus" value="1" <?php if(strstr($sctour[ev_status],"1")) echo "checked"; ?> disabled> 미확정
                                        </label>
                                        <label class="radio-inline">
                                            <input type="radio" name="eventStatus" value="2" <?php if(strstr($sctour[ev_status],"2")) echo "checked"; ?> disabled> 확정
                                        </label>
                                        <label class="radio-inline">
                                            <input type="radio" name="eventStatus" value="3" <?php if(strstr($sctour[ev_status],"3")) echo "checked"; ?> disabled> 만차
                                        </label>
                                        <label class="radio-inline">
                                            <input type="radio" name="eventStatus" value="4" <?php if(strstr($sctour[ev_status],"4")) echo "checked"; ?> disabled> 취소
                                        </label>
                                        <label class="radio-inline">
                                            <input type="radio" name="eventStatus" <?php if(strstr($sctour[ev_status],"5")) echo "checked"; ?> disabled> 기타
                                        </label>
                                    </div>
                                </div>    
                                <div class="col-sm-8">
                                    <div>   
                                        <input type="text" name="etcMemo" class="form-control" aria-label="기타메모"  placeholder="기타메모" value="<?=$sctour[etc_memo]?>" readOnly/>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
						<tr>   
                           <td colspan="16" class="text-center">
                                <div class="row no-nav">
                                    <div class="col-sm-12 text-center">
									  <!--  <button type="button" class="btn btn-primary btn-sm js-car" id="add_room">차량추가</button>-->
									    <button type="submit" class="btn btn-primary btn-sm js-esave" >차량배정저장</button>
                                    <!--    <button type="button" class="btn btn-primary btn-sm js-rest" id="resetcar">전체초기화</button>-->
                                        
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
				</table>
				<div class="row">
                    <div class="col-sm-6" style='overflow:auto; height:500px;'>
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="col-sm-12 text-success"><h5><strong>&nbsp;&nbsp;예약고객현황</strong></h5></div>   
                                <div class="col-sm-12" >
                                    <table id="leftTable" class="table table-striped table-side-no-bordered table-hover text-center">
                                        <thead>
                                            <tr>
                                                <th align="center"><input type="checkbox" class="form-control" id="selectAll"></th>
                                                <th >룸넘버</th>
                                                <th >예약자</th>
												<th >구분</th>
												<th>예약</th>
                                                <th>고객명</th>
                                                <th >성별</th>
                                                <th>탑승지</th>
                                            </tr>
                                        </thead>
                                        <tbody>

                                            <?php
											   ///  if ($pInfo[p_day] >1) {
											          reservelist2() ;
											    // } else {
												//	  reservelist2() ;
											//	 }
											//
											
											?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="panel-group">
                                    <div class="panel panel-default">
                                        <div class="panel-body custom_padding bg-info">총인원 : <?php echo $num1;?>인 &nbsp;&nbsp;&nbsp;&nbsp;총객실수 : <?php echo $troomcnt3[rcnt];?>개</div>
                                    </div>
                                </div>
                            </div>
                        </div>    
                    </div>
                    <div class="col-sm-6" style='overflow:auto; height:500px;'>
                        <fieldset class="guide-assign-border" id="busass">
                            <legend class="guide-assign-border"><span class="pull-left small text-muted">행사차량배정</span></legend>
                            <?php echo totbuslist(); ?>
                        </fieldset>  
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
            
            //$.fn.dataTable.ext.errMode = 'none';
            var args = {paging:false, ordering:true, info:false,dom: 'Bfrtip',
					 buttons: [
						'excel'
					 ]};
            
            
           var  _leftTable = $('#leftTable').DataTable(args);
		   var  _rightTableTop = $('.rtab').DataTable(args);
            $(document).on("click",".moveR",function(){
				 var id1 = $(this).attr('id');
				 var result=id1.split('_');
				 var targettab = "rightTableTop"+result[1]+"";
				 if ( ! $.fn.DataTable.isDataTable( '#'+targettab+'' ) ) {
					 _rightTableTop = $('#'+targettab+'').DataTable(args);
				 } else {
					_rightTableTop = $('#'+targettab+'').DataTable(); 

				 }
				 
				 drawData('leftTable',targettab,_rightTableTop,_leftTable,result[1]);
			}); 
			$(document).on("click",".moveL",function(){
				var id1 = $(this).attr('id');
				//alert(id1);
				var result=id1.split('_');
				var targettab = "rightTableTop"+result[1]+"";
				if ( ! $.fn.DataTable.isDataTable( '#'+targettab+'' ) ) {
					 _rightTableTop = $('#'+targettab+'').DataTable(args);
				} else {
					
					 _rightTableTop = $('#'+targettab+'').DataTable(); 

				}
				
				drawData2(targettab,'leftTable',_rightTableTop,_leftTable);
			}); 
				
            
            $(".js-rest").click(function(){
                $("#formId")[0].reset();
            });
            $('.checkAll').on('click', function () {
                $(this).closest('table').find('tbody :checkbox')
                  .prop('checked', this.checked)
                  .closest('tr').toggleClass('selected', this.checked);
            });
            $('#selectAll').click(function(e){
                var table= $(e.target).closest('table');
                $('td input:checkbox',table).prop('checked',this.checked);
            });

			var flex_cnt =0;
			
			var counter = parseInt('<?=$Buscnt?>');
            $('#add_room').click(function() {
                tableDraw();
            });
			 var tableDraw = function(){
                counter++; 
               
				var sHtml = "<div class='row'>"+
                      "          <div class='col-sm-1'>	 "+
                      "              <div class='row'></div>  "+
                      "              <div class='row text-center moveR' id='topRight_"+counter+"'><i class='splashy-arrow_medium_right'></i></div>				    "+
                      "              <div class='row text-center moveL' id='topLeft_"+counter+"'><i class='splashy-arrow_medium_left'></i></div>					    "+
                      "          </div>				    "+
                      "          <div class='col-sm-11 rightDiv'>  "+
                      "              <table id='rightTableTop"+counter+"' name='bustab[]' class='table table-striped table-side-no-bordered table-hover table-condensed text-center'>   "+
                      "                  <thead>			    "+
                      "                      <tr><input type='hidden' name='bus[]' id='cbus'  value='"+counter+"'>					    "+
                      "                          <th scope='col' colspan ='6'>차량"+counter+"-미배정</th>   "+
                      "                      </tr>																		    "+
                      "                      <tr>																		    "+
                      "                          <th align='center'></th>														    "+
                      "                          <th>룸넘버</th>															    "+
                      "                          <th>예약자</th>															    "+
					  "                        <th >구분</th> "+	
					  "                         <th>예약</th> "+  
				      "                         <th>고객명</th> "+
                      "                          <th>성별</th>   "+
                      "                          <th>탑승지</th>															    "+
                      "                      </tr>																		    "+
                      "                  </thead>																		    "+
                      "                  <tbody>																		    "+
                      "                      																			    "+
                      "                  </tbody>																		   "+
                      "              </table>																		    "+
                      "          </div>																			    "+
                      "          <div class='row'>																	    "+
                      "              <div class='col-sm-1'></div>														   "+
                      "              <div class='col-sm-10'>																    "+
                      "                  <div class='panel-group'>															    "+
                      "                      <div class='panel panel-default'>													   "+
                      "                          <div class='panel-body custom_padding bg-info' id='sumtxt"+counter+"'>총인원 : 0명         총객실수 : 0개 <br />					   "+
                      "                         탑승지																	    "+
                      "                          </div>																	    "+
                      "                          																		    "+
                      "                      </div>																		    "+
                      "                  </div>																		    "+
                      "              </div>    																		    "+
                      "          </div>																			    "+
                      "      </div>																				    ";
								 
				$("#busass").append(sHtml);
				
				
          
				$(document).on("click",".moveR",function(){
					 var id1 = $(this).attr('id');
					 var result=id1.split('_');
					 var targettab = "rightTableTop"+result[1]+"";
					 if ( ! $.fn.DataTable.isDataTable( '#'+targettab+'' ) ) {
					     _rightTableTop = $('#'+targettab+'').DataTable(args);
					 } else {
						_rightTableTop = $('#'+targettab+'').DataTable(); 

					 }
					 
			         drawData('leftTable',targettab,_rightTableTop,_leftTable,result[1]);
			    }); 
				$(document).on("click",".moveL",function(){
					var id1 = $(this).attr('id');
					var result=id1.split('_');
					var targettab = "rightTableTop"+result[1]+"";
					if ( ! $.fn.DataTable.isDataTable( '#'+targettab+'' ) ) {
					     _rightTableTop = $('#'+targettab+'').DataTable(args);
					} else {
						
						 _rightTableTop = $('#'+targettab+'').DataTable(); 

					}
					
			        drawData2(targettab,'leftTable',_rightTableTop,_leftTable);
			    }); 
				
				
            }
            
		})

        
        function drawData(name1,name2,_rightTableTop,_leftTable,boxIndex){
			  var _selTable_1 = _leftTable;
			  var _selTable_2 = _rightTableTop;

			  var $target = $('#'+name2);
			  var subECode = $target.data('sub-ecode') || ''; // ← 박스의 서브코드
			  var tr,row,rowData;

			  $('#'+name1+' td input[type=checkbox]').each(function () {
				if ($(this).is(':checked')) {
				  $(this).attr('checked', 'checked');
				  tr = $(this).closest('tr');
				  row = _selTable_1.row(tr);

				  // 원본 TD HTML들을 복제
				  rowData = [];
				  tr.find('td').each(function(i, td) {
					// 선택된 행에 bnum/ssnum을 심는다(숨김 input 변경)
					$(this).closest('tr').find("#bnum1").val(boxIndex);

					// ssnum hidden 추가(한 번만)
					if ($(this).closest('tr').find('input[name="ssnum[]"]').length === 0) {
					  $('<input>').attr({type:'hidden', name:'ssnum[]', value: subECode}).appendTo($(this).closest('tr'));
					}
					rowData.push($(td).html());
				  });

				  row.remove().draw();
				  _selTable_2.row.add(rowData).draw();
				}
			  });
		 }


		 function drawData2(name1,name2,_rightTableTop,_leftTable,counter){
           
            var _selTable_1 = _rightTableTop;  
          
            var  _selTable_2 = _leftTable;  
              
            var tr,row,rowData;
            $('#'+name1+' td input[type=checkbox]').each(function () {
                if ($(this).is(':checked')) {
                    $(this).attr('checked', 'checked');
                    tr = $(this).closest('tr');
                    row = _selTable_1.row(tr);
                    rowData = [];
                    tr.find('td').each(function(i, td) {
						$(this).closest('tr').find("#bnum1").val("");
                        rowData.push($(td).html());
                    });    
                    row.remove().draw();
                    _selTable_2.row.add(rowData).draw();
                }   
            });    
            
        }
		function chksave() {
			
			  if(confirm("차량배정을 저장하시겠습니까?") == true)
			  {
				return true;
			  }else {
				return;
			  }

		  
		}
        

	</script>
    </body>
</html>
