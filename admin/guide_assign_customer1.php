<?php
    include_once "include/inc_base.php";

    // 로그인 체크
    if (empty($_COOKIE['MEMLOGIN_ADMIN_HELLO'])) {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
        exit;
    }

    // 입력 파라미터 정리
    $mode   = $_POST['mode'] ?? $_GET['mode'] ?? '';
    $gid    = $_GET['gid'] ?? '';
    $p_code = $_GET['p_code'] ?? '';
    $s_code = $_GET['s_code'] ?? '';
    $stdate = $_GET['stdate'] ?? ($_GET['st'] ?? '');
    $st     = $stdate;

    // 상단에서 쓰는 데이터들
    $prodInfo  = getProductMaster($p_code);
    $g_dbinfo1 = getguideInfor($_GET['g_code'] ?? '', $_GET['s_code'] ?? '');
    $g_dbinfo  = getinfo_dbMemberg($g_dbinfo1['guide_id'] ?? '');
    $g_dbinfo2 = getinfo_dbMemberg($g_dbinfo1['sguide_id'] ?? '');

    $picM    = getPicGrM($_GET['g_code'],$s_code);
    $picStr  = getPicGr2($s_code, $stdate);
    $picctot = $picM . "&nbsp;&nbsp;" . $picStr;

    $carinfo  = getCCInfor($s_code);
    $gss      = getGuideInfo2($s_code, $p_code);
    $ccinfo   = getCarInfo($gss['c_id'] ?? '');
    $bus_team = codebaseName($ccinfo['bus_team'] ?? '');

    // ─────────────────────────────────────────────────────────────
    // 고객 리스트 출력 (mysqli 일원화, 배열키 인용, 순서 오류 수정)
    // ─────────────────────────────────────────────────────────────
    function custlist() {
             global $dbConn,$p_code,$gid,$s_code,$stdate;
		     $qry1="SELECT 
					b.rand_id,
					b.p_cnt,
					b.reserveCode, 
					pm.p_name, 
					b.room_cnt, 
					a.rev_nm,
					c.traveler_nm,
					c.traveler_phone,
					b.p_name,
					b.tour_type,
					b.h_cnt,
					b.last_bal,
					b.stDate,
					b.userid,
					b.base_rate,
					b.parent,
					b.meet_area,
					a.bus_num
				FROM 
					tour_car a
					JOIN reserve_info b ON a.p_code = b.p_code AND a.reserveCode = b.reserveCode
					JOIN reserve_traveler c ON b.reserveCode = c.reserveCode AND a.rev_nm = c.traveler_nm
					LEFT JOIN product_master pm ON 
					(CASE 
						WHEN b.parent = 'MAIN' THEN b.p_code 
						WHEN b.parent = 'SUB' THEN (
							SELECT ri.p_code 
							FROM reserve_info ri 
							WHERE ri.reserveCode = b.reserveCode 
							AND ri.parent = 'MAIN' 
							LIMIT 1
						)
					END) = pm.p_code 
				WHERE 
					a.sub_eCode = '$s_code' 
					AND a.p_code = '$p_code' 
					AND c.seqint = '0'
					AND b.rev_status != 'CANCE'
				GROUP BY 
					a.reserveCode, a.sub_eCode, pm.p_day
				ORDER BY 
					pm.p_day ASC";
								
			
			 $rst1 = $dbConn->query($qry1);
		    // echo $qry1."<br>";
			 $num1= $rst1->num_rows;
			 //echo $num1;
			// $rst1 = $dbConn->query($qry1);

			 while($row1 = $rst1->fetch_assoc()){
				//$picnum = getPicGr3($s_code,$row1[romm_num]);
				if ($row1['parent'] == "MAIN") {
				    $picnum = getPicGr4($row1[reserveCode],$row1[traveler_nm]);
					//$picnum2 = "";
					$tycap = "단일";

				} else {
					$picnum = getPicSub2($row1['reserveCode'],$s_code,$row1['stDate']);
					if ($picnum=="") {
						$picnum = pickBaseCodeC($row1[meet_area]);
					}
					$tycap = "복합";
				}
                if ($reInfo[tour_type] == "2") {
					$mdbinfo[kor_name]= "<font color='blue'>웹예약</font>";
				}else {
					$mdbinfo = getinfo_dbMember($row1[userid]);
				}
				$reInfo = getReserveInfo($row1[reserveCode]);
				$randInfo = randname($row1[rand_id]);
				$sexli = getTrSex($row1[reserveCode],$s_code);
				$tinfo = getTourInfo2($p_code,$row1[stDate]);
				/*
				$carinfo = getbusInfo($s_code,$row1['stDate'],$row1['reserveCode']);
				$g_dbinfo1 = getguideInfor($carinfo['grand_eCode'],$carinfo['sub_eCode']);
				$g_dbinfo = getinfo_dbMemberg($g_dbinfo1['guide_id']);
				$g_dbinfo2 = getinfo_dbMemberg($g_dbinfo1['sguide_id']);
			    */
				$gmsg = getbusInfo5($row1['reserveCode'],$row1['bus_num']);
				$sign = "$";
				
				if ($row1[last_bal] == "0") {
					$rest = "<font color=red>완납</font>";
				} else {
					$rest = "미납";
				}
				if ($reInfo[progress] == "") {
					$rein = "";
				} else {
					$rein = $reInfo[progress]."<br />";
				}
				if ($tinfo[etc_memo] == "") {
					$tin = "";
				} else {
					$tin = $tinfo[etc_memo]."<br />";
				}
				if ($tinfo[ev_memo] == "") {
					$rein2 = "";
				} else {
					$rein2 = $tinfo[ev_memo]."<br />";
				}
				if ($row1[rand_id] !="") {
					$rname=randname($row1[rand_id]);
					$rcap = $rname[kor_name];
				} else {
					$rcap = "투어헬로USA";
				}
				if ($row1[tour_type] == "1") {
					$ty = "15";
				} else if ($row1[tour_type] == "2") {
					$ty = "20";
				} else if ($row1[tour_type] == "5") {
					$ty = "25";
				}
				if ($reInfo[p_cnt] == 1) {
					$reInfo1 = 0;
				} else {
					$reInfo1= $reInfo[p_cnt]-1;
				}
				$hinfo= getHotelass21($row1[reserveCode]);
				$tpcnt = $tpcnt + $row1[p_cnt];
				echo "<tr>
						  <td><a href=javascript:openwin('$row1[reserveCode]','25') >$randInfo[kor_name]/$reInfo[p_name]</a></td>
						  <td><a href=javascript:openwin('$row1[reserveCode]','25') >$row1[traveler_nm]
						 + $reInfo1 / $row1[traveler_phone]</a></td>
						  <td>$row1[p_cnt]</td>
						  <td>$row1[room_cnt]</td>
					       <td>$picnum</td>
						 
						  <td>$rest</td>
						 
						  <td>X</td>
						   <td >$gmsg</td>
						  <td >$mdbinfo[kor_name]</td>
						  <td align='left'>$rein $tin $rein2</td>
						  
						</tr>";
			  $totbal = $totbal + $row1[last_bal];
			}

			echo "<tr>
						  <td></td>
						  <td>총인원</td>
						  <td>$tpcnt</td>
						  <td></td>
						  <td></td>						  
						  <td>총금액</td>
						  <td>XX.XX</td>
						  <td ></td>
						  <td ></td>
						  <td></td>
						  
						</tr>";

	}
?>
<!DOCTYPE html>
<html>
    <head>
       <?php
        if ($mode === 'down') {
            header("Content-type: application/vnd.ms-excel; charset=UTF-8"); 
            header("Content-Disposition: attachment; filename=" . ($s_code ?: 'list') . date('Ymd') . ".xls");
            header("Content-Description: PHP5 Generated Data");
            echo "<meta http-equiv='Content-Type' content='application/vnd.ms-excel; charset=utf-8'/>";
        } else {
            echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>";
        }
       ?>
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>투어헬로USA 인트라넷</title>
        <?php if ($mode !== 'down') { ?>
        <!-- CSS -->
        <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css" />
        <link rel="stylesheet" href="bootstrap/css/bootstrap-theme.min.css" />
        <link rel="stylesheet" href="css/normalize.css" />
        <link rel="stylesheet" href="lib/jquery-ui/css/Aristo/Aristo.css" />
        <link rel="stylesheet" href="lib/jBreadcrumbs/css/BreadCrumb.css" />
        <link rel="stylesheet" href="lib/qtip2/jquery.qtip.min.css" />
        <link rel="stylesheet" href="lib/colorbox/colorbox.css" />
        <link rel="stylesheet" href="lib/google-code-prettify/prettify.css" />
        <link rel="stylesheet" href="lib/sticky/sticky.css" />
        <link rel="stylesheet" href="img/splashy/splashy.css" />
        <link rel="stylesheet" href="img/flags/flags.css" />
        <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs/jszip-2.5.0/dt-1.10.18/af-2.3.2/b-1.5.4/b-colvis-1.5.4/b-flash-1.5.4/b-html5-1.5.4/b-print-1.5.4/cr-1.5.0/fc-3.2.5/fh-3.1.4/kt-2.5.0/r-2.2.2/rg-1.1.0/rr-1.2.4/sc-1.5.0/sl-1.2.6/datatables.min.css"/>
        <link rel="stylesheet" href="https://cdn.datatables.net/select/1.3.0/css/select.dataTables.min.css" />
        <link rel="stylesheet" href="lib/bootstrap-datepicker-1.6.4-dist/css/bootstrap-datepicker.min.css" />
        <link rel="stylesheet" href="lib/bootstrap-timepicker/css/bootstrap-timepicker.min.css" />
        <link rel="stylesheet" href="lib/bootstrap-clockpicker/dist/bootstrap-clockpicker.min.css" />
        <link rel="stylesheet" href="lib/bootstrap-switch/dist/css/bootstrap3/bootstrap-switch.min.css" />
        <link rel="stylesheet" href="img/font-awesome/css/font-awesome.min.css" />
        <link rel="stylesheet" href="lib/fullcalendar/fullcalendar_gebo.css" />
        <link href="https://fonts.googleapis.com/css?family=Nanum+Gothic" rel="stylesheet">
        <link rel="stylesheet" href="css/blue.css" id="link_theme" />
        <link rel="stylesheet" href="css/style.css" />
        <link rel="stylesheet" href="css/paran.css?sid=5fe18a1a-0023-476e-afb3-66cdb279d9f7" />

        <!-- JS -->
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-migrate/3.0.1/jquery-migrate.min.js"></script>
        <script src="lib/jquery-ui/jquery-ui-1.10.0.custom.min.js"></script>
        <script src="js/forms/jquery.ui.touch-punch.min.js"></script>
        <script src="js/jquery.easing.1.3.min.js"></script>
        <script src="js/jquery.debouncedresize.min.js"></script>
        <script src="js/jquery_cookie_min.js"></script>
        <script src="bootstrap/js/bootstrap.min.js"></script>
        <script src="js/bootstrap.plugins.min.js"></script>
        <script src="lib/typeahead/typeahead.min.js"></script>
        <script src="lib/google-code-prettify/prettify.min.js"></script>
        <script src="lib/sticky/sticky.min.js"></script>
        <script src="lib/colorbox/jquery.colorbox.min.js"></script>
        <script src="js/forms/jquery.inputmask.min.js"></script>
        <script src="lib/jBreadcrumbs/js/jquery.jBreadCrumb.1.1.min.js"></script>
        <script src="js/jquery.actual.min.js"></script>
        <script src="lib/slimScroll/jquery.slimscroll.js"></script>
        <script src="js/ios-orientationchange-fix.js"></script>
        <script src="lib/UItoTop/jquery.ui.totop.min.js"></script>
        <script src="js/selectNav.js"></script>
        <script src="lib/moment/moment.min.js"></script>
        <script src="js/pages/gebo_common.js"></script>
        <script src="js/jquery.imagesloaded.min.js"></script>
        <script src="js/jquery.wookmark.js"></script>
        <script src="js/jquery.mediaTable.min.js"></script>
        <script src="js/jquery.peity.min.js"></script>
        <script src="lib/flot/jquery.flot.min.js"></script>
        <script src="lib/flot/jquery.flot.resize.min.js"></script>
        <script src="lib/flot/jquery.flot.pie.min.js"></script>
        <script src="lib/flot.tooltip/jquery.flot.tooltip.min.js"></script>
        <script src="lib/fullcalendar/fullcalendar.min.js"></script>
        <script src="lib/list_js/list.min.js"></script>
        <script src="lib/list_js/plugins/paging/list.paging.min.js"></script>
        <script src="lib/bootstrap-datepicker-1.6.4-dist/js/bootstrap-datepicker.min.js"></script>
        <script src="lib/bootstrap-timepicker/js/bootstrap-timepicker.min.js"></script>
        <script src="lib/bootstrap-clockpicker/dist/bootstrap-clockpicker.min.js"></script>
        <script src="lib/bootstrap-switch/dist/js/bootstrap-switch.min.js"></script>
        <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
        <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
        <script type="text/javascript" src="https://cdn.datatables.net/v/bs/jszip-2.5.0/dt-1.10.18/af-2.3.2/b-1.5.4/b-colvis-1.5.4/b-flash-1.5.4/b-html5-1.5.4/b-print-1.5.4/cr-1.5.0/fc-3.2.5/fh-3.1.4/kt-2.5.0/r-2.2.2/rg-1.1.0/rr-1.2.4/sc-1.5.0/sl-1.2.6/datatables.min.js"></script>
        <script type="text/javascript" src="https://cdn.datatables.net/select/1.3.0/js/dataTables.select.min.js"></script>
        <link type="text/css" href="/gyrocode.github.io/jquery-datatables-checkboxes/1.2.11/css/dataTables.checkboxes.css" rel="stylesheet" />
        <script type="text/javascript" src="/gyrocode.github.io/jquery-datatables-checkboxes/1.2.11/js/dataTables.checkboxes.min.js"></script>

        <script src="js/dongbu.js?sid=b778ad81-59cf-49a4-b7bf-b9bc7808d745"></script>
        <script src="js/dongbu_lee.js?sid=f10d80e0-c59c-4b4f-8927-17e44a330d8e"></script>
        <?php } ?>
        <style type="text/css">
        @media print {
            @page { size: A4; margin: 10mm 3mm 5mm 3mm; }
            body  { margin: 0px; }
            .pr { padding-right: 5px; padding-left: 5px; }
        }
        </style>
    </head>

<body>
    <div id="contentwrapper" class="reservationDetailForm">
        <?php if ($mode !== 'down') { ?>
        <div id="jCrumbs" class="breadCrumb module">
            <ul>
                <li><a href="admin"><i class="glyphicon glyphicon-home"></i></a></li>
                <li><a href="#">행사배정관리</a></li>
                <li>행사명단</li>
                <li></li>
            </ul>
        </div>
        <?php } ?>

        <div class="row">
            <div class="col-sm-12 col-md-12">
                <div class="row">
                    <div class="col-sm-12">
                        <form action="" name="frmName" method="post">
                            <input type="hidden" name="mode" value="down">
                            <fieldset class="guide-assign-border">
                                <legend class="guide-assign-border">
                                    <span class="pull-left small text-muted">행사고객현황</span>
                                </legend>

                                <?php if ($mode !== 'down') { ?>
                                <div class="row no-nav">
                                    <div id="custom_button" class="col-sm-12 text-right">
                                        <button type="submit" class="btn btn-xs btn-default js-xxx">엑셀보내기</button>
                                        <button type="button" class="btn btn-xs btn-default js-xxx" onclick="pageprint()">프린트</button>
                                    </div>
                                </div>
                                <?php } ?>

                                <br/>
                                <div class="col-sm-12" id="printarea">
                                    <table class="table table-bordered table-condensed">
                                        <tr>
                                            <td width="10%" class="titletd text-center">행사명</td>
                                            <td width="40%" class=""><?= htmlspecialchars($prodInfo['p_name'] ?? '') ?></td>
                                            <td width="10%" class="titletd text-center">행사번호</td>
                                            <td width="40%" class=""><?= htmlspecialchars($_GET['s_code'] ?? '') ?></td>
                                        </tr>
                                        <tr>
                                            <td width="10%" class="titletd text-center">픽업장소</td>
                                            <td width="40%" class=""><?= $picctot ?></td>
                                            <td width="10%" class="titletd text-center">가이드</td>
                                            <td width="40%" class="">
                                                <?= htmlspecialchars($g_dbinfo['kor_name'] ?? '') ?>
                                                &nbsp;&nbsp;&nbsp;<?= htmlspecialchars($g_dbinfo['company_phone'] ?? '') ?>
                                                &nbsp;&nbsp;부 :
                                                <?= htmlspecialchars($g_dbinfo2['kor_name'] ?? '') ?>
                                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?= htmlspecialchars($g_dbinfo2['company_phone'] ?? '') ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td width="10%" class="titletd text-center">차량</td>
                                            <td width="40%" class="">
                                                <?= htmlspecialchars($bus_team['comment'] ?? '') ?>
                                                <?= htmlspecialchars($ccinfo['bus_id'] ?? '') ?>
                                                <?= htmlspecialchars($ccinfo['bus_number'] ?? '') ?>
                                            </td>
                                            <td width="10%" class="titletd text-center">기사</td>
                                            <td width="40%" class="">
                                                <?= htmlspecialchars($ccinfo['bus_driver'] ?? '') ?>
                                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?= htmlspecialchars($gss['d_tel'] ?? '') ?>
                                            </td>
                                        </tr>
                                    </table>

                                    <br/> ▶ 호텔정보
                                    <table id="custom_table" class="table table-condensed custom_table">
                                        <thead>
                                            <tr>
                                                <th class="tcenter" width="30%">날짜</th>
                                                <th class="tcenter" width="*">호텔명</th>
                                                <th class="tcenter" width="10%">QQ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                            $qry2 = "
                                                SELECT a.pcnt, a.hotel_code, a.stDate, b.h_name, b.m_rate, a.day
                                                  FROM hotel_assign a, product_hotel b
                                                 WHERE a.hotel_code=b.h_code
                                                   AND a.sub_eCode='" . $dbConn->real_escape_string($s_code) . "'
                                                   AND a.p_code='" . $dbConn->real_escape_string($p_code) . "'
                                                 ORDER BY a.day ASC
                                            ";
                                            $rst2 = $dbConn->query($qry2);
                                            if ($rst2) {
                                                while ($row2 = $rst2->fetch_assoc()) {
                                                    $s_date = explode("-", $row2['stDate']);
                                                    $add_date = ((int)$row2['day']) - 1;
                                                    $local_start = date("Y-m-d", mktime(0,0,0, (int)$s_date[1], (int)$s_date[2] + $add_date, (int)$s_date[0]));
                                                    echo "<tr>
                                                            <td>{$local_start}</td>
                                                            <td>" . htmlspecialchars($row2['h_name']) . "</td>
                                                            <td class='tcenter'>" . htmlspecialchars($row2['pcnt']) . "</td>
                                                          </tr>";
                                                }
                                            }
                                        ?>
                                        </tbody>
                                    </table>

                                    ▶ 옵션정보
                                    <table id="custom_table" class="table table-condensed custom_table">
                                        <thead>
                                            <tr>
                                                <th class="tcenter" width="30%">옵션명</th>
                                                <th class="tcenter" width="10%">인원수</th>
                                                <th class="tcenter" width="*">옵션예약자</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                            // 옵션 코드별 집계
                                            $qryOpt = "
                                                SELECT b.opt_code, c.opt_name
                                                  FROM reserve_info a
                                                  JOIN reserve_opt b ON a.reserveCode=b.reserveCode
                                                  JOIN base_opt    c ON b.opt_code=c.opt_code
                                                 WHERE a.reserveCode IN (SELECT reserveCode FROM tour_car WHERE sub_eCode='" . $dbConn->real_escape_string($s_code) . "')
                                                   AND b.opt_code <> ''
                                                   AND a.stDate='" . $dbConn->real_escape_string($st) . "'
                                                   AND a.p_code='" . $dbConn->real_escape_string($p_code) . "'
                                                   AND a.rev_status!='CANCEL'
                                              GROUP BY b.opt_code
                                              ORDER BY b.opt_code ASC
                                            ";
                                            $rstOpt = $dbConn->query($qryOpt);
                                            if ($rstOpt) {
                                                while ($row2 = $rstOpt->fetch_assoc()) {
                                                    // 해당 옵션의 예약자 목록/인원 수
                                                    $qryNames = "
                                                        SELECT a.tnm, a.reserveCode
                                                          FROM reserve_opt a
                                                          JOIN reserve_info b ON a.reserveCode=b.reserveCode
                                                         WHERE a.opt_code='" . $dbConn->real_escape_string($row2['opt_code']) . "'
                                                           AND b.p_code='" . $dbConn->real_escape_string($p_code) . "'
                                                           AND b.stDate='" . $dbConn->real_escape_string($st) . "'
                                                           AND a.reserveCode IN (SELECT reserveCode FROM tour_car WHERE sub_eCode='" . $dbConn->real_escape_string($s_code) . "')
                                                           AND b.rev_status!='CANCEL'
                                                      GROUP BY a.reserveCode, a.tnm
                                                    ";
                                                    $rstNames = $dbConn->query($qryNames);

                                                    $k = 0;
                                                    $tnm = "";
                                                    if ($rstNames) {
                                                        while ($row3 = $rstNames->fetch_assoc()) {
                                                            $tnm .= ($row3['tnm'] . ",");
                                                            $k++;
                                                        }
                                                        $tnm = rtrim($tnm, ',');
                                                    }

                                                    echo "<tr>
                                                            <td>" . htmlspecialchars($row2['opt_name']) . "</td>
                                                            <td align='center'>{$k}</td>
                                                            <td>" . htmlspecialchars($tnm) . "</td>
                                                          </tr>";
                                                }
                                            }
                                        ?>
                                        </tbody>
                                    </table>

                                    <table id="custom_table" class="table table-striped table-bordered table-hover table-condensed text-center">
                                        <thead>
                                            <tr>
                                                <th class="tcenter" width="10%">예약자</th>
                                                <th class="tcenter" width="10%">고객명/전번</th>
                                                <th class="tcenter" width="5%">인원</th>
                                                <th class="tcenter" width="5%">방갯수</th>
                                                <th class="tcenter" width="7%">픽업</th>
                                                <th class="tcenter" width="5%">결재</th>
                                                <th class="tcenter" width="5%">잔금</th>
                                                <th class="tcenter" width="7%">가이드</th>
                                                <th class="tcenter" width="5%">접수</th>
                                                <th class="tleft"   width="*">진행사항</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php echo custlist(); ?>
                                        </tbody>
                                    </table>
                                </div>
                            </fieldset>
                        </form>
                    </div>
                </div>
            </div><!-- -->
        </div>
    </div>

    <script>
        var initBody;
        function beforePrint(){ initBody = document.body.innerHTML; document.body.innerHTML = printarea.innerHTML; }
        function afterPrint(){ document.body.innerHTML = initBody; }
        function pageprint(){ window.onbeforeprint = beforePrint; window.onafterprint = afterPrint; window.print(); }

        var ctr=0;
        function openwin(r_code,ty){
            var winName = "all_"+(ctr++);
            window.open("base_reservation_m.php?estimateCode="+r_code+"&division=3&pdx=2&sub="+ty, winName, "width=1300,height=700,scrollbars=1");
        }
        function pageprint2(s_code,stdate){
            var simplename = "simplelist";
            var winName = "all_"+(ctr++);
            window.open("print_customer.php?s_code="+s_code+"&stdate="+stdate, simplename, "width=900,height=1080,scrollbars=1");
        }
    </script>
</body>
</html>
