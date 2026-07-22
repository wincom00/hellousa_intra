<?php
    include "include/header.php";
    //include "include/inc_base.php";
    if ($_COOKIE[MEMLOGIN_ADMIN_HELLO] !="") {
    } else {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
        exit;
    }

    // 디버깅 함수 추가
    function debug_log($message, $data = null, $type = 'INFO') {
        $timestamp = date('Y-m-d H:i:s');
        $debug_msg = "[$timestamp] [$type] $message";
        if ($data !== null) {
            $debug_msg .= " | Data: " . print_r($data, true);
        }
        echo "<div style='background: #f0f0f0; border: 1px solid #ccc; padding: 5px; margin: 2px; font-family: monospace; font-size: 11px; color: #333;'>$debug_msg</div>";
    }

    function debug_error($message, $data = null) {
        debug_log($message, $data, 'ERROR');
    }

    function debug_info($message, $data = null) {
        debug_log($message, $data, 'INFO');
    }

    function debug_warning($message, $data = null) {
        debug_log($message, $data, 'WARNING');
    }

    // 디버깅 시작
    debug_info("페이지 로드 시작", $_REQUEST);

    $sctour = getTourInfo2($pcode,$st);
    debug_info("Tour 정보 조회", ['pcode' => $pcode, 'st' => $st, 'result' => $sctour]);
    
    $pcnt = getReserveInfoCnt($pcode,$st);
    debug_info("예약 정보 카운트", $pcnt);
    
    if ($pcnt['cnt'] =="") {
        $pcnt['cnt'] = 0;
        debug_warning("예약 카운트가 비어있어 0으로 설정");
    }
    
    $pInfo = getProductMaster($pcode);
    debug_info("상품 정보 조회", $pInfo);
    
    if ($mode == "save") {
        debug_info("저장 모드 진입");
        
        $eventcnt = count($rnum);
        debug_info("이벤트 카운트", ['eventcnt' => $eventcnt, 'rnum' => $rnum]);
        
        $qry1 = "";
        $rst1 = $dbConn->query($qry1);
        
        $nbnum = ""; // 초기화 추가
        debug_info("nbnum 초기화");
        
        for($r=0;$r<$eventcnt;$r++) {
            debug_info("루프 진행", ['r' => $r, 'bnum[$r]' => $bnum[$r]]);
            
            if ($bnum[$r] != "") {
                if (($nbnum == "") || ($bnum[$r] != $nbnum)){
                    $sub_eventNum = getNumSevent($gcode,$sdate);
                    $sub_eventCode = "GSE".$sdate."-".$sub_eventNum;
                    debug_info("새 이벤트 코드 생성", ['sub_eventNum' => $sub_eventNum, 'sub_eventCode' => $sub_eventCode]);
                }

                $qry2 ="insert into tour_car 
                                    ( 
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
                                    )
                                    values
                                    ( 
                                    '$gcode', 
                                    '$sub_eventNum', 
                                    '$sub_eventCode', 
                                    '".$rev[$r]."', 
                                    '$pcode', 
                                    '$pname', 
                                    '$sdate', 
                                    '".$bnum[$r]."', 
                                    '".$rnum[$r]."', 
                                    '".$rand[$r]."', 
                                    '".$revnm[$r]."',
                                    '".$roomt[$r]."',
                                    '".$rsex[$r]."', 
                                    '".addslashes($pick[$r])."', 
                                    '".$user_dbinfo['userid']."', 
                                    '".$hseq[$r]."',
                                    now()
                                    );
                                ";

                debug_info("SQL 쿼리 생성", ['query' => $qry2]);
                
                $rst = $dbConn->query($qry2);
                if ($rst) {
                    debug_info("SQL 실행 성공", ['affected_rows' => $dbConn->affected_rows]);
                } else {
                    debug_error("SQL 실행 실패", ['error' => $dbConn->error]);
                }
                
                $nbnum = $bnum[$r];
                debug_info("nbnum 업데이트", ['nbnum' => $nbnum]);
            } else {
                debug_warning("bnum[$r]이 비어있음", ['r' => $r]);
            }
        }
        
        debug_info("저장 완료");
        Misc::jvAlert("업데이트 되었습니다!!","");
    }
    
    function reservelist2() {
        global $dbConn,$pcode,$st,$num1;
        
        debug_info("reservelist2 함수 시작", ['pcode' => $pcode, 'st' => $st]);

        $qry1 = "select    a.grand_eCode, 
                                a.p_code, 
                                a.p_name, 
                                a.bus_cnt, 
                                a.tour_pcnt, 
                                a.stDate, 
                                b.reserveCode,
                                c.traveler_nm,
                                c.pick_area,
                                c.sextype,
                                c.seqint
                                from 
                                tour_master a, reserve_info b ,(select reserveCode,traveler_nm,pick_area,seqint,sextype from reserve_traveler) c
                             where a.stDate=b.stDate &&  a.p_code =  b.p_code && b.reserveCode=c.reserveCode  && a.stDate =  '$st' && a.p_code = '$pcode' 
                             && c.seqint not in (select h_seq from tour_car where stDate = '$st' && p_code = '$pcode') && (b.rev_status!='WAIT' && b.rev_status!='CANCEL')  order by c.seqint asc";
        
        debug_info("reservelist2 쿼리", ['query' => $qry1]);
        
        $rst1 = $dbConn->query($qry1);
        
        if (!$rst1) {
            debug_error("reservelist2 쿼리 실행 실패", ['error' => $dbConn->error]);
            return;
        }
        
        $row_count = 0;
        while($row1 = $rst1->fetch_assoc()){
            $row_count++;
            debug_info("예약 데이터 처리", ['row_count' => $row_count, 'reserveCode' => $row1['reserveCode']]);
            
            $reserve_info2 = getReserveTrInfo($row1['reserveCode'],$row1['traveler_nm']);
            debug_info("여행자 정보", $reserve_info2);
            
            if ($reserve_info2['room_type'] == "1r1p") {
                $fimg = "1인1실";
                $fmn = "1r1p";
            } else if ($reserve_info2['room_type'] === "1r2p") {
                $fimg = "2인1실";
                $fmn = "1r2p";
            } else if ($reserve_info2['room_type'] == "1r3p") {
                $fimg = "3인1실";
                $fmn = "1r3p";
            } else if ($reserve_info2['room_type'] == "1r4p") {
                $fimg = "4인1실";
                $fmn = "1r4p";
            } else if ($reserve_info2['room_type'] == "1r5p") {
                $fimg = "5인1실";
                $fmn = "1r5p";
            }
            
            debug_info("방 타입 설정", ['room_type' => $reserve_info2['room_type'], 'fimg' => $fimg]);
            
            $reserve_info = getReserveInfo($row1['reserveCode']);
            debug_info("예약 정보", $reserve_info);
            
            $prodInfo = getProductMaster($reserve_info['p_code']);
            debug_info("상품 정보", $prodInfo);
            
            if ($row1['rand_id'] != "") {
                $rnm = randname($row1['rand_id']);
                $row1['book_pri'] = $rnm['kor_name'];
                debug_info("랜덤 이름 처리", ['rand_id' => $row1['rand_id'], 'kor_name' => $rnm['kor_name']]);
            }
            
            // 성별 처리
            if ($prodInfo['p_day'] > 1) {
                if ($row1['sextype'] == "man") {
                    $sex= $fimg."<br />/남자";
                } else if ($row1['sextype'] == "female") {
                    $sex= $fimg."<br />/여자";
                } else if ($row1['sextype'] == "mfemale") {
                     $sex= $fimg."<br />/혼성";
                }
            } else {
                if ($row1['sextype'] == "man") {
                    $sex= "남자";
                } else if ($row1['sextype'] == "female") {
                    $sex = "여자";
                } else if ($row1['sextype'] == "mfemale") {
                     $sex = "혼성";
                }
            }
            
            debug_info("성별 처리 완료", ['sextype' => $row1['sextype'], 'sex_display' => $sex]);
            
            // 상품 타입 처리
            if ($prodInfo['p_type'] == 1) {
               $pcap = "로컬";
            } else if ($prodInfo['p_type'] == 2) {
                $pcap = "인바운드";
            } else if ($prodInfo['p_type'] == 4) {
                $pcap = "인센티브";
            } else if ($prodInfo['p_type'] == 5) {
                $pcap = "아웃바운드";
            }
            
            debug_info("상품 타입 설정", ['p_type' => $prodInfo['p_type'], 'pcap' => $pcap]);
            
            if ($reserve_info['tour_type'] == 3) {
                $rcap = "협력사";
            } else {
                $rcap = "자사";
            }
            
            $rname=randname($reserve_info['rand_id']);
            debug_info("예약자 이름", $rname);
             
            if ($prodInfo['p_type'] == 2) {
                  $reserve_info2 = getReserveInfo2($reserve_info['reserveCode'],$st);
                  $picknm = pickBaseCode3($reserve_info2['meet_area']);
                  debug_info("인바운드 픽업 정보", ['meet_area' => $reserve_info2['meet_area'], 'picknm' => $picknm]);
            } else {
                $pickarr = explode("/",$row1['pick_area']);
                $picknm['pick_code'] = $row1['picCode'];
                debug_info("일반 픽업 정보", ['pick_area' => $row1['pick_area'], 'picCode' => $row1['picCode']]);
            }

            echo " <tr>
                        <td align='center'><input type='checkbox' class='form-control' value='".$row1['seq_no']."'><input type='hidden' name='hseq[]' id='hseq' value='".$row1['seqint']."'><input type='hidden' name='bnum[]' id='bnum1' value=''></td>
                        <td>".$row1['traveler_room']."<input type='hidden' name='rnum[]' value='".$row1['traveler_room']."'></td>
                        <td title='".$reserve_info['p_name']."'>".$row1['book_pri']."<input type='hidden' name='rev[]' value='".$row1['reserveCode']."'><input type='hidden' name='rand[]' value='".$row1['rand_id']."'></td>
                        <td align='center' title='".$reserve_info['p_name']."'>$pcap</td>
                        <td align='center' title='".$rname['kor_name']."'>$rcap</td>
                        <td align='center'>".$row1['traveler_nm']."<input type='hidden' name='revnm[]' value='".$row1['traveler_nm']."'></td>
                        <td align='center'>$sex<input type='hidden' name='roomt[]' value='$fmn'><input type='hidden' name='rsex[]' value='".$row1['sextype']."'></td>
                        <td>".$picknm['pick_name']."-".$picknm['pick_time']." <input type='hidden' name='pick[]' value='".addslashes($row1['pick_area'])."'></td>
                    </tr>";
        }
        
        debug_info("reservelist2 함수 완료", ['총 행 수' => $row_count]);
    }
    
    $troomcnt = getReserveRoomCnt($pcode,$st);
    debug_info("방 카운트 조회", $troomcnt);
    
    $troomcnt3 = $troomcnt; // 중복 호출 제거
    $Buscnt = getBusCnt($sctour['grand_eCode']);
    debug_info("버스 카운트", ['Buscnt' => $Buscnt, 'grand_eCode' => $sctour['grand_eCode']]);
    
    if ($num1 != 0) {
        $troomcnt3 = $troomcnt['rcnt'];
        debug_info("num1이 0이 아님", ['num1' => $num1, 'troomcnt3' => $troomcnt3]);
    }
    
    $troomcnt1 = $troomcnt['rcnt'];
    if (($Buscnt == "") || ($Buscnt == 0)){
        $troomcnt2 = 0;
        debug_warning("버스 카운트가 0이거나 비어있음");
    } else {
        $troomcnt2 = $troomcnt1-1;
        debug_info("troomcnt2 계산", ['troomcnt1' => $troomcnt1, 'troomcnt2' => $troomcnt2]);
    }
    
    function totbuslist() {
        global $dbConn,$pcode,$st,$Buscnt,$sctour,$gcode;
        
        debug_info("totbuslist 함수 시작", ['Buscnt' => $Buscnt]);
        
        $content = "";
        
        for($r=1;$r<=$Buscnt;$r++) {
            debug_info("버스 루프", ['r' => $r, 'total_buses' => $Buscnt]);
            
            $content .= "<div class='row'>
                                <div class='col-sm-1'>
                                    <div class='row'></div>
                                    <div class='row text-center moveR' id='topRight_$r'><i class='splashy-arrow_medium_right'></i></div>
                                    <div class='row text-center moveL' id='topLeft_$r'><i class='splashy-arrow_medium_left'></i></div>
                                </div>
                                <div class='col-sm-11'>
                                    <table id='rightTableTop$r' class='table table-striped table-side-no-bordered table-hover table-condensed text-center rtab'>
                                        <thead>
                                            <tr>
                                                <th scope='col' colspan ='8'>차량$r</th>
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
            
            $qry2= "select 	 
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
                                     
                                    from 
                                    tour_car 
                                    where grand_eCode='".$sctour['grand_eCode']."' && bus_num ='$r' && p_code not like '%ADD%'";
            
            debug_info("차량별 쿼리", ['bus_number' => $r, 'query' => $qry2]);
            
            $rst1 = $dbConn->query($qry2);
            
            if (!$rst1) {
                debug_error("차량별 쿼리 실행 실패", ['error' => $dbConn->error, 'bus_number' => $r]);
                continue;
            }
            
            $car_row_count = 0;
            while($row1 = $rst1->fetch_assoc()){
                $car_row_count++;
                debug_info("차량 데이터 처리", ['bus_number' => $r, 'row_count' => $car_row_count, 'reserveCode' => $row1['reserveCode']]);
                
                if ($row1['rand_id'] != "") {
                    $rnm = randname($row1['rand_id']);
                    $row1['book_pri'] = $rnm['kor_name'];
                }
        
                if ($row1['room_type'] == "1r1p") {
                    $fimg = "독방";
                    $fmn = "1r1p";
                } else if ($row1['room_type'] === "1r2p") {
                    $fimg = "2인1실";
                    $fmn = "1r2p";
                } else if ($row1['room_type'] == "1r3p") {
                    $fimg = "3인1실";
                    $fmn = "1r3p";
                } else if ($row1['room_type'] == "1r4p") {
                    $fimg = "4인1실";
                    $fmn = "1r4p";
                } else if ($row1['room_type'] == "1r5p") {
                    $fimg = "5인1실";
                    $fmn = "1r5p";
                }
                
                debug_info("차량 방 타입 설정", ['room_type' => $row1['room_type'], 'fimg' => $fimg]);
                
                $reserve_info = getReserveInfo($row1['reserveCode']);
                $prodInfo = getProductMaster($reserve_info['p_code']);
                
                debug_info("차량 상품 정보", ['p_code' => $reserve_info['p_code'], 'p_day' => $prodInfo['p_day']]);
                
                if ($prodInfo['p_day'] > 1) {
                    if ($row1['sex'] == "man") {
                        $sex= $fimg."<br />/남자";
                    } else if ($row1['sex'] == "female") {
                        $sex = $fimg."<br />/여자";
                    } else if ($row1['sex'] == "mfemale") {
                         $sex = $fimg."<br />/혼성";
                    }
                } else {
                    if ($row1['sex'] == "man") {
                        $sex= "남자";
                    } else if ($row1['sex'] == "female") {
                        $sex = "여자";
                    } else if ($row1['sex'] == "mfemale") {
                         $sex = "혼성";
                    }
                }

                if ($prodInfo['p_type'] == 1) {
                   $pcap = "로컬";
                } else if ($prodInfo['p_type'] == 2) {
                    $pcap = "인바운드";
                } else if ($prodInfo['p_type'] == 4) {
                    $pcap = "인센티브";
                } else if ($prodInfo['p_type'] == 5) {
                    $pcap = "아웃바운드";
                }
                
                if (($row1['romm_num'] == 1) || ($row1['romm_num'] == 0)){
                    $rrnum = "1";
                } else {
                    $rrnum = $row1['romm_num'];
                }
                
                if ($reserve_info['tour_type'] == 3) {
                    $rcap = "업체";
                } else {
                    $rcap = "자사";
                }
                
                if ($prodInfo['p_type'] == 2) {
                    $reserve_info2 = getReserveInfo2($reserve_info['reserveCode'],$st);
                    $picknm = pickBaseCode3($reserve_info2['meet_area']);
                    debug_info("차량 인바운드 픽업", ['meet_area' => $reserve_info2['meet_area']]);
                } else {
                    $pickarr = explode("/",$row1['picCode']);
                    $picknm = pickBaseInfo($pickarr[0],$pickarr[1]);
                    $picknm['pick_code'] = $row1['picCode'];
                    debug_info("차량 일반 픽업", ['picCode' => $row1['picCode']]);
                }

                $content .= " <tr>
                                <td align='center'><input type='checkbox' class='form-control' value='".$row1['seq_no']."'><input type='hidden' name='hseq[]' id='hseq' value='".$row1['h_seq']."'><input type='hidden' name='bnum[]' id='bnum1' value='$r'></td>
                                <td>$rrnum<input type='hidden' name='rnum[]' value='".$row1['romm_num']."'></td>
                                <td>".$row1['reserveCode']."<input type='hidden' name='rev[]' value='".$row1['reserveCode']."'></td>
                                <td align='center'>$pcap</td>
                                <td align='center'>$rcap</td>
                                <td align='center'>".$row1['rev_nm']."<input type='hidden' name='revnm[]' value='".$row1['rev_nm']."'></td>
                                <td align='center'>$sex<input type='hidden' name='roomt[]' value='".$row1['room_type']."'><input type='hidden' name='rsex[]' value='".$row1['sex']."'></td>
                                <td>".$picknm['pick_name']."-".$picknm['pick_time']."<input type='hidden' name='pick[]' value='".$picknm['pick_code']."'></td>
                            </tr>";
            }
            
            debug_info("차량별 데이터 처리 완료", ['bus_number' => $r, 'total_rows' => $car_row_count]);
            
            $totp = getbusperson($r,$sctour['grand_eCode']);
            $totroom = getbusRoom($r,$sctour['grand_eCode']);
            
            debug_info("차량 통계", ['bus_number' => $r, 'person_count' => $totp['cnt'], 'room_count' => $totroom]);
            
            global $psInfo; // 전역 변수 선언 추가
            if ($psInfo['p_day'] == 1) {
                $trnum = 0;
            } else {
                $trnum = $totroom;
            }
            
            $piccnt = getPicGr3($sctour['grand_eCode'],$r);
            
            $content .= "</tbody>
                                </table>
                            </div>
                            <div class='row'>
                                <div class='col-sm-1'></div>
                                <div class='col-sm-10'>
                                    <div class='panel-group'>
                                        <div class='panel panel-default'>
                                            <div class='panel-body custom_padding bg-info'>총인원 : ".$totp['cnt']."인 &nbsp;&nbsp;&nbsp;&nbsp;총 객실수 : $trnum 개<br /> 
                                             $piccnt
                                            </div>
                                            
                                        </div>
                                    </div>
                                </div>    
                            </div>
                        </div>";
        }
        
        debug_info("totbuslist 함수 완료", ['총 버스 수' => $Buscnt]);
        return $content;
    }
    
    debug_info("페이지 로드 완료");
?>

<!-- 나머지 HTML 코드는 동일 -->
<div id="contentwrapper" class="reservationDetailForm">
    <!-- 디버깅 정보 표시 영역 -->
    <div style="background: #ffffcc; border: 2px solid #ffcc00; padding: 10px; margin: 10px 0;">
        <h4 style="color: #cc6600; margin: 0 0 10px 0;">🐛 디버깅 정보</h4>
        <div id="debug-info" style="max-height: 200px; overflow-y: auto; font-size: 12px;">
            <!-- 디버깅 메시지들이 여기에 표시됩니다 -->
        </div>
    </div>
    
    <div class="main_content">
        <!-- 기존 HTML 코드 계속... -->
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
									    <button type="button" class="btn btn-primary btn-sm js-car" id="add_room">차량추가</button>
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
		//include "include/side_m.php"
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
				
            <!--reset -->
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

        
        function drawData(name1,name2,_rightTableTop,_leftTable,bnum){
           
            var _selTable_1 = _leftTable;  
           
            var  _selTable_2 = _rightTableTop;  
               
            var tr,row,rowData;
            $('#'+name1+' td input[type=checkbox]').each(function () {
                if ($(this).is(':checked')) {
                    $(this).attr('checked', 'checked');
                    tr = $(this).closest('tr');
                    row = _selTable_1.row(tr);
                    rowData = [];
                    tr.find('td').each(function(i, td) {
						$(this).closest('tr').find("#bnum1").val(bnum);
						//alert($(this).closest('tr').find("#bnum1").val());
                        rowData.push($(td).html());
						
						
                    });    
                    row.remove().draw();
					_selTable_2.row;

					///alert(bnum);
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
