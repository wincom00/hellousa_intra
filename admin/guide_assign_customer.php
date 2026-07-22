<?php
    include "include/inc_base.php";
    if ($_COOKIE['MEMLOGIN_ADMIN_HELLO'] !="") {
	} else {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}

     
    
	$prodInfo = getProductMaster($s_code);
	$g_dbinfo = getinfo_dbMember($gid);
	$totpcnt = getReserveInfoCntG($s_code,$stdate);
	$totroom = getReserveInfoRoom($s_code,$stdate);
	$totbal = getReserveInfoBalSS($s_code,$stdate);
	$totsal = getReserveInfoSal($s_code,$stdate);
	/*$carinfo = getbusInfo($s_code,$stdate);
	$g_dbinfo1 = getguideInfor($carinfo[sub_eCode]);
	$g_dbinfo = getinfo_dbMemberg($g_dbinfo1[guide_id]);
	$g_dbinfo2 = getinfo_dbMemberg($g_dbinfo1[sguide_id]);*/
	//$picStr  =bal getPicGr2($s_code,$stdate);
	//print_r($carinfo);
	$pickmain = getPicGr8($s_code,$stdate);

	function custlist() {
             global $dbConn,$p_code,$gid,$s_code,$stdate,$prodInfo,$mode;
			 if (($prodInfo[p_code] == 'LAPICKUP') || ($prodInfo[p_code] == 'LVPICKUP') || ($prodInfo[p_code] == 'PICKUP') || ($prodInfo[p_code] == 'EWRPICKUP') || ($prodInfo[p_code] == 'SFOPICKUP') || ($prodInfo[p_code] == 'SLCPICKUP') || ($prodInfo[p_code] == 'ANCPICKUP')  || ($prodInfo[p_code] == 'SANPICKUP') || ($prodInfo[p_code] == 'DFWPICKUP') || ($prodInfo[p_code] == 'DFWPICKUP') || ($prodInfo[p_code] == 'LGAPICKUP') || ($prodInfo[p_code] == 'YULPICKUP') ) {
				$order = "order by b.air_arrivetime,b.reserveCode,c.traveler_room asc"; 
			 } else if (($prodInfo[p_code] == 'LASENDING') || ($prodInfo[p_code] == 'LVSENDING') || ($prodInfo[p_code] == 'SENDING') || ($prodInfo[p_code] == 'EWRSENDING') || ($prodInfo[p_code] == 'SFOSENDING') || ($prodInfo[p_code] == 'SLCSENDING') || ($prodInfo[p_code] == 'ANCSENDING') || ($prodInfo[p_code] == 'YULSENDING') || ($prodInfo[p_code] == 'SANSENDING') || ($prodInfo[p_code] == 'LAXSENDING')   ) {
				$order = "order by pm.p_cnt asc, b.reserveCode,c.traveler_room,b.air_sttime asc"; 
			 } else {
				$order = "order by pm.p_cnt asc ,b.reserveCode,c.traveler_room,c.pick_area,c.seqint asc";
			 }
			 
		     $qry1="SELECT DISTINCT b.pricet, d.cnt, b.p_code, b.air_arcity, b.air_arrivetime, b.air_arriveNm, 
                b.air_stcity, b.air_sttime, b.air_stNm, b.p_name, b.reserveCode, b.book_pri, 
                b.book_phone, b.rand_id, b.meet_area, c.traveler_room, c.traveler_nm, 
                c.traveler_enm, c.e_memo, c.traveler_phone, b.room_cnt, b.p_cnt as pcnt, 
                b.last_bal, b.stDate, b.base_rate, b.parent, b.userid, c.pick_area
				FROM reserve_info b
				JOIN reserve_traveler c ON b.reserveCode = c.reserveCode
				JOIN product_master pm ON b.p_code = pm.p_code 
				JOIN (
					SELECT COUNT(a.reserveCode) as cnt, a.reserveCode 
					FROM reserve_info a, reserve_traveler b 
					WHERE a.reserveCode = b.reserveCode 
					  AND a.p_code = '$s_code' 
					  AND a.stDate = '$stdate' 
					  AND a.rev_status = 'DONE'  
					GROUP BY a.reserveCode
				) d ON b.reserveCode = d.reserveCode
				WHERE b.p_code = '$s_code' 
				  AND b.stDate = '$stdate' 
				  AND b.rev_status = 'DONE' $order ";
			
			 
			 $rst1 = $dbConn->query($qry1);
			 //echo $qry1;
			 $num1= $rst1->num_rows;
			 //echo $num1;
			 $k = 0;
			 $orev = "";
			 while ($row1 = $rst1->fetch_assoc()) {
			    $PICKUP = getProductPick($prodInfo[p_code]);
				$SENDING = getProductSend($prodInfo[p_code]);
				//echo $prodInfo[p_code]."1";
				if (($prodInfo[p_code] == $PICKUP[p_code]) && ($prodInfo[p_code]!="")) {
					$picnum = $row1[air_arcity]."-".$row1[air_arriveNm]."-".$row1[air_arrivetime];
				} else if (($prodInfo[p_code] == $SENDING[p_code]) && ($prodInfo[p_code]!="")) {
					$picnum = $row1[air_stcity]."-".$row1[air_stNm]."-".$row1[air_sttime];
					$picnum = $picnum.'<br/>'.getPicSub($row1[reserveCode],$s_code,$stdate);
				} else if ($row1[parent] == "MAIN") {
				    $picnum = getPicGr3($row1[reserveCode],$row1[traveler_nm]);
					//echo "2222";
				} else {
					
					$picnum = getPicSub2($row1[reserveCode],$s_code,$stdate);
					if ($picnum=="") {
						$picnum = pickBaseCodeC($row1[meet_area]);
					}
					//echo "3333";
				}
				/*
				$carinfo = getbusInfo2($prodInfo[p_code],$stdate,$row1['reserveCode']);
				$g_dbinfo1 = getguideInfor($carinfo['grand_eCode'],$carinfo['sub_eCode']);
				$g_dbinfo = getinfo_dbMemberg($g_dbinfo1['guide_id']);
				$g_dbinfo2 = getinfo_dbMemberg($g_dbinfo1['sguide_id']);
				
				*/
				$gmsg = getbusInfo5($row1['reserveCode']);
				
				$reInfo = getReserveInfo($row1[reserveCode]);
				$tinfo = getTourInfo2($s_code,$row1[stDate]);
				///echo $row1[rand_id]."<br/>";
				$nrev=$row1[reserveCode];
				$sign = "$";
				if ($reInfo[tour_type] == "2") {
					$mdbinfo[kor_name]= "<font color='blue'>웹예약</font>";
				}else {
					$mdbinfo = getinfo_dbMember($row1[userid]);
				}
				if ($reInfo[payment_st] == "DONE") {
					$rest = "<font color=red>완납</font>";
				} else if ($reInfo[payment_st] == "PPAY") {
					$rest = "<font color=blue>부분완납</font>";
				} else {
					$rest = "미납";
				}
				//echo $row1[rand_id]."<br/>";
				if($reInfo[progress] != strip_tags($reInfo[progress])) {
					// contains HTML
					$rein = $reInfo[progress];
				} else {
					$rein = nl2br($reInfo[progress]);
				}
				if ($tinfo[etc_memo] == "") {
					$tin = "";
				} else {
					$tin = nl2br($tinfo[etc_memo]);
				}
				if ($tinfo[ev_memo] == "") {
					$rein2 = "";
				} else {
					$rein2 = nl2br($tinfo[ev_memo]);
				}
				//echo $row1[rand_id]."<br/>";
				if ($row1[rand_id] != "") {
					$rnm = randname($row1[rand_id]);
					$row1[book_pri] = $rnm[kor_name];
					//echo $rnm[kor_name]."21111<br/>";
					
				}
				
				
				if ($row1[pricet] == '3') {
					$reInfo[last_bal] ="해당사항없음";

				}
				$carinfo = implode('@',array_unique(explode('@', $carinfo)));
				if ($mode=="down") {
					if ($nrev != $orev) {
						echo "<tr>
								  
								  <td rowspan='$row1[cnt]'><a href=javascript:openwin('$row1[reserveCode]','$row1[pricet]') >$row1[book_pri]/<br /> $reInfo[p_name]</a></td>
								  <td rowspan='$row1[cnt]'>$row1[book_phone]</td>
								  <td rowspan='$row1[cnt]'>$reInfo[p_cnt]</td>
								  <td rowspan='$row1[cnt]'>$reInfo[room_cnt]</td>
								  <td>$hopt $vopt <a href=javascript:openwin('$row1[reserveCode]','$row1[pricet]') >$row1[traveler_nm]</a><br />$row1[traveler_phone]</td>
								  <td>$row1[e_memo]</td>
								  <td>$row1[traveler_room]</td>
								  <td>$picnum</td>
								  <td rowspan='$row1[cnt]'>$rest</td>
								  <td rowspan='$row1[cnt]'>$sign $reInfo[last_bal]</td>
								  <td rowspan='$row1[cnt]'>$gmsg</td>
								  <td rowspan='$row1[cnt]'>$mdbinfo[kor_name]</td>
								  <td rowspan='$row1[cnt]'>$rein $tin $rein2</td>
								  
								</tr>";
					} else {
						echo "<tr>
								  
								  <td><a href=javascript:openwin('$row1[reserveCode]','$row1[pricet]') >$row1[traveler_nm]</a><br />$row1[traveler_phone]</td>
								  <td>$row1[traveler_enm]</td>
								  <td>$row1[e_memo]</td>
								  <td>$row1[traveler_room]</td>
								  <td>$picnum</td>
								  
								  
								</tr>";

					}
				} else {
				
					if ($nrev != $orev) {
						echo "<tr>
								  
								  <td rowspan='$row1[cnt]'><a href=javascript:openwin('$row1[reserveCode]','$row1[pricet]') >$row1[book_pri]/<br /> $reInfo[p_name]</a></td>
								  <td rowspan='$row1[cnt]'>$row1[book_phone]</td>
								  <td rowspan='$row1[cnt]'>$reInfo[p_cnt]</td>
								  <td rowspan='$row1[cnt]'>$reInfo[room_cnt]</td>
								  <td>$hopt $vopt <a href=javascript:openwin('$row1[reserveCode]','$row1[pricet]') >$row1[traveler_nm]</a><br />$row1[traveler_phone]</td>
								  <td>$row1[e_memo]</td>
								  <td>$row1[traveler_room]</td>
								  <td>$picnum</td>
								  <td rowspan='$row1[cnt]'>$rest</td>
								  <td rowspan='$row1[cnt]'>$sign $reInfo[last_bal]</td>
								  <td rowspan='$row1[cnt]'>$gmsg</td>
								  <td rowspan='$row1[cnt]'>$mdbinfo[kor_name]</td>
								  <td  rowspan='$row1[cnt]'>$rein $tin $rein2</td>
								  
								</tr>";
					} else {
						echo "<tr>
								  
								  <td><a href=javascript:openwin('$row1[reserveCode]','$row1[pricet]') >$row1[traveler_nm]</a><br />$row1[traveler_phone]</td>
								  <td>$row1[e_memo]</td>
								  <td>$row1[traveler_room]</td>
								  <td>$picnum</td>
								  
								  
								</tr>";

					}
                }
				$orev = $row1[reserveCode];
				$k++;
			}

	}

?>
<!DOCTYPE html>
<html>
    <head>
	<?php
	    if($mode=='down') {
		    header("Content-type: application/vnd.ms-excel; charset=UTF-8"); 
			header("Content-Disposition: attachment; filename=".$_GET[s_code]."".date('Ymd').".xls");
			header("Pragma: no-cache"); 
			header("Expires: 0");

			//header("Content-Description: PHP5 Generated Data");
		    //echo "<meta http-equiv='Content-Type' content='application/vnd.ms-excel; charset=utf-8'/>";
			echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>";


       }
	   ?>
	   <?php
	    if($mode!='down') {
             echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>";
		 } ?>
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title> TOUR HELLO USA 인트라넷</title>
        <?php if($mode!='down') { ?>
        <!-- Bootstrap framework -->
            <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css" />
            <link rel="stylesheet" href="bootstrap/css/bootstrap-theme.min.css" />
            <link rel="stylesheet" href="css/normalize.css" />
        <!-- jQuery UI theme -->
            <link rel="stylesheet" href="lib/jquery-ui/css/Aristo/Aristo.css" />
        <!-- breadcrumbs -->
            <link rel="stylesheet" href="lib/jBreadcrumbs/css/BreadCrumb.css" />
        <!-- tooltips-->
            <link rel="stylesheet" href="lib/qtip2/jquery.qtip.min.css" />
		<!-- colorbox -->
            <link rel="stylesheet" href="lib/colorbox/colorbox.css" />
        <!-- code prettify -->
            <link rel="stylesheet" href="lib/google-code-prettify/prettify.css" />
        <!-- sticky notifications -->
            <link rel="stylesheet" href="lib/sticky/sticky.css" />
        <!-- aditional icons -->
            <link rel="stylesheet" href="img/splashy/splashy.css" />
		<!-- flags -->
            <link rel="stylesheet" href="img/flags/flags.css" />
        <!-- datatables -->
            <!-- <link rel="stylesheet" href="lib/datatables/extras/TableTools/media/css/TableTools.css"> -->
			<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.25/css/jquery.dataTables.min.css"/>
            <link rel="stylesheet" href="https://cdn.datatables.net/buttons/1.7.1/css/buttons.dataTables.min.css" />
        <!-- datepicker -->
            <!-- <link rel="stylesheet" href="lib/datepicker/datepicker.css" /> -->
            <link rel="stylesheet" href="lib/bootstrap-datepicker-1.6.4-dist/css/bootstrap-datepicker.min.css" />
		<!-- timepicker -->
            <!-- <link rel="stylesheet" href="lib/timepicker/css/bootstrap-timepicker.css" /> -->
            <link rel="stylesheet" href="lib/bootstrap-timepicker/css/bootstrap-timepicker.min.css" />
		<!-- clockpicker -->
            <link rel="stylesheet" href="lib/bootstrap-clockpicker/dist/bootstrap-clockpicker.min.css" />

        <!-- switch buttons -->
            <link rel="stylesheet" href="lib/bootstrap-switch/dist/css/bootstrap3/bootstrap-switch.min.css" />

        <!-- font-awesome -->
            <link rel="stylesheet" href="img/font-awesome/css/font-awesome.min.css" />
        <!-- calendar -->
            <link rel="stylesheet" href="lib/fullcalendar/fullcalendar_gebo.css" />
			<link href="https://fonts.googleapis.com/css?family=Nanum+Gothic" rel="stylesheet">
        
		<!-- theme color-->
            <link rel="stylesheet" href="css/blue.css" id="link_theme" />

        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.0.11/css/all.css" integrity="sha384-p2jx59pefphTFIpeqCcISO9MdVfIm4pNnsL08A6v5vaQc4owkQqxMV8kg4Yvhaw/" crossorigin="anonymous">
		<!-- main styles -->
            <link rel="stylesheet" href="css/style.css" />
		<!-- paran css -->
			<link rel="stylesheet" href="css/purun.css?sid=5fe18a1a-0023-476e-afb3-66cdb279d9f7" />
		<!-- favicon -->
            <link rel="shortcut icon" href="favicon1.ico" />
			<link type="text/css" href="//gyrocode.github.io/jquery-datatables-checkboxes/1.2.11/css/dataTables.checkboxes.css" rel="stylesheet" />
        <?php } ?>
        <!--[if lte IE 8]>
            <link rel="stylesheet" href="css/ie.css" />
        <![endif]-->

        <!--[if lt IE 9]>
			<script src="js/ie/html5.js"></script>
			<script src="js/ie/respond.min.js"></script>
			<script src="lib/flot/excanvas.min.js"></script>
        <![endif]-->  
		<!-- <script src="js/jquery.min.js"></script> -->
		<!-- <script src="js/jquery-migrate.min.js"></script> -->
		<script src="https://code.jquery.com/jquery-3.5.1.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-migrate/3.0.1/jquery-migrate.min.js"></script>
		<script src="lib/jquery-ui/jquery-ui-1.10.0.custom.min.js"></script>

		<!-- touch events for jquery ui-->
			<script src="js/forms/jquery.ui.touch-punch.min.js"></script>
		<!-- easing plugin -->
			<script src="js/jquery.easing.1.3.min.js"></script>
		<!-- smart resize event -->
			<script src="js/jquery.debouncedresize.min.js"></script>
		<!-- js cookie plugin -->
			<script src="js/jquery_cookie_min.js"></script>
		<!-- main bootstrap js -->
			<script src="bootstrap/js/bootstrap.min.js"></script>
		<!-- bootstrap plugins -->
			<script src="js/bootstrap.plugins.min.js"></script>
		<!-- typeahead -->
			<script src="lib/typeahead/typeahead.min.js"></script>
		<!-- code prettifier -->
			<script src="lib/google-code-prettify/prettify.min.js"></script>
		<!-- sticky messages -->
			<script src="lib/sticky/sticky.min.js"></script>
		<!-- lightbox -->
			<script src="lib/colorbox/jquery.colorbox.min.js"></script>
		<!-- masked inputs -->
			<script src="js/forms/jquery.inputmask.min.js"></script>
		<!-- jBreadcrumbs -->
			<script src="lib/jBreadcrumbs/js/jquery.jBreadCrumb.1.1.min.js"></script>
		<!-- hidden elements width/height -->
			<script src="js/jquery.actual.min.js"></script>
		<!-- custom scrollbar -->
			<script src="lib/slimScroll/jquery.slimscroll.js"></script>
		<!-- fix for ios orientation change -->
			<script src="js/ios-orientationchange-fix.js"></script>
		<!-- to top -->
			<script src="lib/UItoTop/jquery.ui.totop.min.js"></script>
		<!-- mobile nav -->
			<script src="js/selectNav.js"></script>
		<!-- moment.js date library -->
			<script src="lib/moment/moment.min.js"></script>

		<!-- common functions -->
			<script src="js/pages/gebo_common.js"></script>

		<!-- multi-column layout -->
			<script src="js/jquery.imagesloaded.min.js"></script>
		<script src="js/jquery.wookmark.js"></script>
		<!-- responsive table -->
			<script src="js/jquery.mediaTable.min.js"></script>
		<!-- small charts -->
			<script src="js/jquery.peity.min.js"></script>
		<!-- charts -->
			<script src="lib/flot/jquery.flot.min.js"></script>
			<script src="lib/flot/jquery.flot.resize.min.js"></script>
			<script src="lib/flot/jquery.flot.pie.min.js"></script>
			<script src="lib/flot.tooltip/jquery.flot.tooltip.min.js"></script>
		<!-- calendar -->
			<script src="lib/fullcalendar/fullcalendar.min.js"></script>
		<!-- sortable/filterable list -->
			<script src="lib/list_js/list.min.js"></script>
			<script src="lib/list_js/plugins/paging/list.paging.min.js"></script>

		<!-- datepicker -->
			<!-- <script src="lib/datepicker/bootstrap-datepicker.min.js"></script> -->
			<script src="lib/bootstrap-datepicker-1.6.4-dist/js/bootstrap-datepicker.min.js"></script>
		
			
        <!-- datatables -->
			<script type="text/javascript" src="https://cdn.datatables.net/buttons/1.7.1/js/dataTables.buttons.min.js"></script>
			<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
			<script type="text/javascript" src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
			<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
			<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
			<script type="text/javascript" src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.html5.min.js"></script>
			
		<!-- purun js -->
			<script src="js/dongbu.js?sid=b778ad81-59cf-49a4-b7bf-b9bc7808d745"></script>

		<!-- purun_lee js -->
			<script src="js/dongbu_lee.js?sid=f10d80e0-c59c-4b4f-8927-17e44a330d8e"></script>
			<style type="text/css">
			  @media print {
				  @page 
					{ 
						size: A4;   /* auto is the initial value */ 

						/* this affects the margin in the printer settings */ 
						margin: 10mm 3mm 5mm 3mm;  
					} 

					body  
					{ 
						/* this affects the margin on the content before sending to printer */ 
						margin: 0px;  
					}		
			  .pr {
					padding-right: 5px;
					padding-left: 5px;
			  }
			</style>
	</head>


<style>
    /*div.dt-buttons {
        float: right; 
        padding-bottom: 10px;
    }*/
    table { page-break-inside:auto }
    tr    { page-break-inside:avoid; page-break-after:auto }
</style>
<body>
    <br />
	<br />
	<div id="contentwrapper" class="reservationDetailForm">
         <? if ($mode != 'down') { ?>
			<div id="jCrumbs" class="breadCrumb 
			module">
				<ul>
					<li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li>
					<li><a href="#">전체스케줄표</a></li>
					<li>행사현황</li>
					<li>행사명단</li>
				</ul>
			</div>
		<?} ?>
			<div class="row">
				<div class="col-sm-12 col-md-12">
					
					
						<div class="col-sm-12" style='page-break-after:always'>
						    <form action="" name="frmName" method="post" Onsubmit='exex()'>
						      <input type="hidden" name="mode" id="mode" value="">
                                <? if ($mode != 'down') { ?>
                                <div class="row no-nav">
                                    <div id="custom_button" class="col-sm-12 text-right">
                                        <button type="submit" class="btn btn-xs btn-default js-xxx" >엑셀보내기</button>
                                       <button type="button" class="btn btn-xs btn-default js-xxx" onclick="pageprint()">프린트</button>
									   <button type="button" onclick="pageprint2('<?=$s_code?>','<?=$stdate?>')" class="btn btn-xs btn-default js-xxx"  >간단명단출력</button>
									   <button type="button" onclick="agentemail('<?=$s_code?>','<?=$stdate?>')" class="btn btn-xs btn-default js-agent"  >에이전트엑셀보내기</button>
                                    </div>
                                </div>
								<?}?>
                                
									
										<legend class="guide-assign-border"><span class="pull-left small text-muted">행사고객현황</span></legend>
										
										<br/>
											<table class="table table-bordered table-condensed">
										
													<tr>
														<td width="10%" class="titletd text-center">행사명</td>
														<td width="40%" class=""><?=$prodInfo[p_name]?>
															
														</td>
														<td width="10%" class="titletd text-center">행사일자</td>
														<td width="40%" class=""><?php echo $_GET[stdate];?></td>
													</tr>
													<tr>
														<td width="10%" class="titletd text-center">여행인원</td>
														<td width="40%" class=""><?=$totpcnt[cnt]?> 명	</td>
														<td width="10%" class="titletd text-center">객실수</td>
														<td width="40%" class=""><?=$totroom[rcnt]?> 개									
														</td>
													</tr>
													<tr>
														<td width="10%" class="titletd text-center">총판매금액</td>
														<td width="40%" class="">$<?=number_format($totsal[tot],2)?>	</td>
														<td width="10%" class="titletd text-center">잔금</td>
														<td width="20%" class="">$<?=number_format($totbal[bal],2)?>									
														</td>
													</tr>
													<tr>
														<td width="10%" class="titletd text-center">픽업요약</td>
														<td width="40%" class="" colspan= 3><?=$pickmain?>	</td>
														

													</tr>
													<!--<tr>
														<td width="10%" class="titletd text-center">가이드</td>
														<td width="40%" class=""><?php echo $g_dbinfo[kor_name]; ?> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $g_dbinfo[company_phone];?></td>
														<td width="10%" class="titletd text-center">부가이드</td>
														<td width="40%" class="">									<?php echo $g_dbinfo2[kor_name]; ?> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $g_dbinfo2[company_phone];?>
														</td>
													</tr>
												  -->
											</table>
											<br/>
											<table id="custom_table" class="table table-bordered table-condensed custom_table">
												<thead>
													<tr>  
													  
													  <th class="tcenter" width='7%'>예약자</th>
													  <th class="tcenter" width='5%'>연락처</th>
													  <th class="tcenter" width='5%'>인원</th>
													  <th class="tcenter" width='7%'>방갯수</th>
													  <th class="tcenter" width='7%'>투어고객</th>
													  <?php if ($mode=="down") {?>
													  <th class="tcenter" width='7%'>영문이름</th>
													  <?php } ?>
													  <th class="tcenter" width='13%'>예약메모</th>
													  <th class="tcenter" width='5%'>루밍</th>
													  <th class="tcenter" width='8%'>픽업</th>
													  
													  <th class="tcenter" width='5%'>결제상태</th>
											  
													  <th class="tcenter" width='10%'>잔금</th>
													  <th class="tcenter" width='10%'>가이드</th>
													  <th class="tcenter" width='5%'>접수자</th>
													  <th class="tleft" width='40%'>진행사항</th>
													 
													</tr>
												</thead>
												<tbody>
													<?php custlist(); ?>
												</tbody>
											</table>
									
								<br />
								<br />
							</form>
						</div>
					
				</div><!-- -->
			</div>                
	

	</div>
  
    <script>
		$(document).ready(function () {
         //  pt.initReservationList()
            
          
           pt.initReservationDetail()

            $('.custom_table').DataTable( {
				"ordering": true,
				"order": [[ 0, "desc" ]]
			} );
			$(".dataTables_length").css({ "display" :"none" });
		
           //var args = {paging:false, ordering:false, info:false,scrollX:true,scrollY: 200};
           
            
           
		})
		
		function pageprint()
		{
			 
			
           window.print();
  
		}
		function exex()
		{
			 
		   
           $('#mode').val('down');
  
		}
		var ctr=0;
	    function openwin(r_code,pricet) { 
	       var winName = "all_"+(ctr++);
		   window.open("base_reservation_m.php?estimateCode="+r_code+"&pricet="+pricet+"&division=3&pdx=2&sub=15",winName,"width=900,height=1080,scrollbars=1");
	    }

		function pageprint2(s_code,stdate)
		{
			 
			
           var winName = "all_"+(ctr++);
		   window.open("print_customer.php?s_code="+s_code+"&stdate="+stdate,winName,"width=900,height=1080,scrollbars=1");
  
		}

		function agentemail(s_code,stdate)
		{
			 
			
           var winName = "all_"+(ctr++);
		   window.open("print_customer2.php?s_code="+s_code+"&stdate="+stdate,winName,"width=900,height=1080,scrollbars=1");
  
		}
      
	</script>
    </body>
</html>
