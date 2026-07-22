<?php
    include "./include/header.php";
	
	if ($_COOKIE['MEMLOGIN_ADMIN_HELLO'] != "") {
		if ($user_dbinfo['division'] == "guide") {
			echo "<meta http-equiv='refresh' content='0; url=./memo_list.php'>";
			
			exit;
		}
	} else {
		
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		
		exit;
	}


	$mm = date("m");
	$ym = date("Y-m");
	$ymd = date("Y-m-d");
	$qry0 = "select sum(last_total) scnt from reserve_info where revDate = '$ymd' && parent='MAIN' && base_rate='USD' && rev_status !='CANCEL'";
    $rst0 = $dbConn->query($qry0);
	$row0 = $rst0->fetch_assoc();

	$qry1 = "select sum(last_total) scnt from reserve_info where substr(revDate,1,7) ='$ym' && parent='MAIN' && base_rate='USD' && rev_status !='CANCEL' ";
    $rst1 = $dbConn->query($qry1);
	$row1 = $rst1->fetch_assoc();
	
	$qry00 = "select sum(p_cnt) cnt from reserve_info where revDate = '$ymd' && parent='MAIN' && rev_status !='CANCEL' ";
    $rst00 = $dbConn->query($qry00);
	$row00 = $rst00->fetch_assoc();

	$qry11 = "select count(*) cnt from reserve_info where substr(revDate,1,7) ='$ym'&& parent='MAIN' && rev_status !='CANCEL'";;
    $rst11 = $dbConn->query($qry11);
	$row11 = $rst11->fetch_assoc();


	$qry2 = "select count(*) cnt from reserve_info where substr(revDate,1,7) ='$ym'&& parent='MAIN' && rev_status !='CANCEL'";
    $rst2 = $dbConn->query($qry2);
	$row2 = $rst2->fetch_assoc();

	

	$qry5 = "select count(*) cnt from reserve_info where YEARWEEK(revDate) = YEARWEEK(now()) && parent='MAIN' && rev_status !='CANCEL' ";
    $rst5 = $dbConn->query($qry5);
	$row5 = $rst5->fetch_assoc();

	$qry6 = "select sum(p_cnt) cnt from reserve_info where substr(revDate,1,7) ='$ym' && parent='MAIN' && rev_status !='CANCEL' ";
    $rst6 = $dbConn->query($qry6);
	$row6 = $rst6->fetch_assoc();

	$qry7 = "select sum(p_cnt) cnt from reserve_info where YEARWEEK(revDate) = YEARWEEK(now()) && parent='MAIN' && rev_status !='CANCEL' ";
    $rst7 = $dbConn->query($qry7);
	$row7 = $rst7->fetch_assoc();
	
	function printBoard($table_id){
		
		global $dbConn,$division,$pdx,$sub;

		$qry1 = "select * from dongbu_board where tablename = '$table_id' order by seq_no desc limit 10";
		$rst1 = $dbConn->query($qry1);


		$num1 = 0;

		while($row1 =$rst1->fetch_assoc()){
			if ($table_id == '25') {
				$url ='board_view.php?division=8&pdx=1&sub=25&table_id='.$table_id.'&';
			}
			if ($table_id == '15') {
				$url ='board_view.php?division=8&pdx=1&sub=15&table_id='.$table_id.'&';
			}
			if ($table_id == '10') {
				$url ='board_view.php?division=8&pdx=1&sub=10&table_id='.$table_id.'&';
			} 
			if ($table_id == '01') {
				$url ='board_view.php?division=8&pdx=1&sub=10&table_id='.$table_id.'&';
			}
			if ($table_id == '35') {
				$url ='board_view.php?division=8&pdx=1&sub=35&table_id='.$table_id.'&';;
			}
			$today = explode(" ",$row1[wdate]);
			$today2 = explode("-",$today[0]);

			$yesterday1 = date("Y-m-d H:i:s",time()-86400);
			if($row1[wdate] > $yesterday1)
			{
			$new_icon = "<img src='img/New2.gif'>";
			}
			else
			{
			$new_icon = "&nbsp;";
			}

			$title = Misc::cutLongString($row1[title], 16, $dot=true);

			$content .= "<table class='table table-borderless index_table_fixed'>
					<tr bgcolor=#FFFFFF>
						<td  height=22 width=80%><a href=".$url."no=$row1[seq_no]&start=0&board_mode=view>$title</a> $new_icon</td>
						<td align=right width=20%><span class=stxt>$today2[1].$today2[2]</span></td>
					</tr>
					</table>
					
			";

			$num1++;

		}

		echo $content;
	}
?>

    <style>
		.index_div1 {/*border-top:1px solid #999;*/border-bottom:1px solid #999;padding:7px 7px 7px 0px;}
		.index_padding {padding-top:10px;}
		.index_margin-top {margin-top:10px;}
		.index_margin-bottom {margin-bottom:0px !important;}
		.index-border-bottom {border-bottom:1px solid #999;}
		.autocut{text-overflow:ellipsis;overflow:hidden;}
		.index_table_fixed {table-layout:fixed;white-space:nowrap;}
		.index_scroll {height: 200px;overflow-y: scroll;}
		.index_table_color {color:#eee;}
		.index_table_color a {text-decoration:none ; color:#eee;}
		.autocut a {text-decoration:none ; color:#333;}
		.index-margint-top {margin-top:0px !important;}
	</style>

<div id="contentwrapper" class="js-mainPage">
	<div class="main_content">
	   <div id="jCrumbs" class="breadCrumb module">
			<ul>
				<li>
					<a href="#"><i class="glyphicon glyphicon-home"></i></a>
				</li>
			</ul>
	   </div>
	   <!-- 바로가기 -->	 
	   <div class="row">
	       <div class="col-sm-12">
		       <h4 class="heading"><strong>바로가기</strong></h4>
		   </div>
	    </div>
		<div class="row index-margint-top">
			<div class="col-sm-4">
				<img src='img/logo_p.png' width="80%">
			</div>
			<div class="col-sm-8">
				<ul class="dshb_icoNav clearfix">
					<li><a href="base_reservation.php?division=3&pdx=2&sub=10&ty=1" style="background-image: url(img/gCons/bookmark.png)">예약등록</a></li>
					<li><a href="base_reservation_mylist.php?division=3&pdx=4&sub=10" style="background-image: url(img/gCons/addressbook.png)">MY 예약현황</a></li>
					
					<li><a href="employee_cal_mylist.php?division=6&pdx=3&sub=15" style="background-image: url(img/gCons/dollar.png)">MY 수금현황</a></li>
				</ul>
			</div>
		</div>
        <!-- 예약현황 -->	
		<div class="row">
	       <div class="col-sm-12">
				<h4 class="heading"><strong>예약현황</strong></h4>
		    </div>
	    </div>
		<div class="row index-margint-top">
			<div class="col-sm-12">
				<div class="col-sm-12 tac">
					<div class="col-sm-4 index_div1">
						<table class="table table-borderless index_margin-bottom">
							<tr>
								<td class="text-left index-border-bottom index_table_color" width="100%" bgcolor="#3993ba" ><strong>Daily Sale<strong></td>
							</tr>
							<tr>
								<td width="100%" style="padding-top:10px;">
								    <table class="table table-borderless index_margin-bottom">
										<tr>
											<td><i class="fa fa-dollar fa-2x"></i></td>
											<td class="text-left" width="29%"><?php echo  number_format($row0[scnt],2) ?></</td>
											<td><i class="fas fa-male fa-2x"></i><i class="fas fa-male fa-2x"></i><i class="fas fa-male fa-2x"></i></td>
											<td><?php echo $row11[cnt];?></td>
											<td><i class="fas fa-male fa-2x"></i></td>
											<td><?php echo $row00[cnt];?></td>
										</tr>
									</table>
								</td>
							</tr>
							
						</table>
					</div>
					<div class="col-sm-4 index_div1">
						<table class="table table-borderless index_margin-bottom">
							<tr>
								<td class="text-left index-border-bottom index_table_color" width="100%" bgcolor="#3993ba"><strong>Monthly Sale<strong></td>
							</tr>
							<tr>
								<td width="100%" style="padding-top:10px;">
								    <table class="table table-borderless index_margin-bottom">
										<tr>
											<td><i class="fa fa-dollar fa-2x"></i></td>
											<td class="text-left" width="29%"><?php echo  number_format($row1[scnt],2) ?></</td>
											<td><i class="fas fa-male fa-2x"></i><i class="fas fa-male fa-2x"></i><i class="fas fa-male fa-2x"></i></td>
											<td><?php echo $row2[cnt];?></td>
											<td><i class="fas fa-male fa-2x"></i></td>
											<td><?php echo $row6[cnt];?></td>
										</tr>
									</table>
								</td>
							</tr>
							
						</table>
					</div>
					<div class="col-sm-4 index_div1">
						<table class="table table-borderless index_margin-bottom">
							<tr>
								<td class="text-left index-border-bottom index_table_color" width="100%" bgcolor="#3993ba"><strong>Weekly Sale<strong></td>
							</tr>
							<tr>
								<td width="100%" style="padding-top:10px;">
								    <table class="table table-borderless index_margin-bottom">
										<tr>
											<td><i class="fa fa-dollar fa-2x"></i></td>
											<td class="text-left" width="35%"><?php echo  number_format($row11[scnt],2) ?></td>
											<td><i class="fas fa-male fa-2x"></i><i class="fas fa-male fa-2x"></i><i class="fas fa-male fa-2x"></i></td>
											<td><?php echo $row5[cnt];?></td>
											<td><i class="fas fa-male fa-2x"></i></td>
											<td><?php echo $row7[cnt];?></td>
										</tr>
									</table>
								</td>
							</tr>
							
						</table>
					</div>
				</div>
				<br />
				<br />
				<br />
				<br />
				<div class="col-sm-12 tac index_margin-top">
					<table class="table table-borderless index_margin-bottom">
						<tr>
							<td width="100%" style="padding-top:50px;">
							  
							</td>
						</tr>
						
					</table>
				</div>
			</div>
				<!--<div class="col-sm-12 tac index_margin-top">
					<table class="table table-borderless index_margin-bottom">
						<tr>
							<td width="100%" style="padding-top:10px;">
							    <!-- 예약현황 - bar chart 
								<div id="bar-container" style="min-width: 310px; height: 270px; max-width: 600px; margin: 0 auto"></div>
							</td>
						</tr>
						
					</table>
				</div>
			</div>-->
			<!-- 예약현황 - pie chart 
			<div class="col-sm-4">
				<div id="pie-container" style="min-width: 310px; height: 250px; max-width: 600px; margin: 0 auto"></div>
			</div>-->
		</div>
		<!-- 게시판 -->
		<div class="row">
	       <div class="col-sm-12">
				<h4 class="heading"><strong>게시판</strong></h4>
		    </div>
	    </div>
		<div class="row index-margint-top">
			<div class="col-sm-12">
				<div class="col-sm-3 index_scroll">
					<table class="table table-borderless">
						<tr>
							<td class="text-left index-border-bottom index_table_color"  bgcolor="#3993ba"><strong>단체문의<strong></td>
							<td class="text-right index-border-bottom index_table_color" bgcolor="#3993ba"><strong><a href="board_list.php?division=8&pdx=3&sub=10&table_id=35">더보기</a><strong></td>
						</tr>
						<?php printBoard('35'); ?>
					</table>
				</div>
				<div class="col-sm-3 index_scroll">
					<table class="table table-borderless">
						<tr>
							<td class="text-left index-border-bottom index_table_color"  bgcolor="#3993ba"><strong>상품공지<strong></td>
							<td class="text-right index-border-bottom index_table_color" bgcolor="#3993ba"><strong><a href="board_list.php?division=8&pdx=1&sub=25&table_id=25">더보기</a><strong></td>
						</tr>
						<?php printBoard('25'); ?>
					</table>
				</div>
				<div class="col-sm-3 index_scroll">
					<table class="table table-borderless">
						<tr>
							<td class="text-left index-border-bottom index_table_color"  bgcolor="#3993ba"><strong>사내공지<strong></td>
							<td class="text-right index-border-bottom index_table_color" bgcolor="#3993ba"><strong><a href="board_list.php?division=8&pdx=1&sub=15&table_id=15">더보기</a><strong></td>
						</tr>
						<?php printBoard('15'); ?>
						
					</table>
				</div>
				<div class="col-sm-3 index_scroll">
					<table class="table table-borderless">
						<tr>
							<td class="text-left index-border-bottom index_table_color"  bgcolor="#3993ba"><strong>문의게시판<strong></td>
							<td class="text-right index-border-bottom index_table_color" bgcolor="#3993ba"><strong><a href="board_list.php?division=8&pdx=1&sub=10&table_id=01">더보기</a><strong></td>
						</tr>
						<?php printBoard('01'); ?>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>

	    <?php
			include "include/side_m.php";
			//exit;
		?>
		

		<script>
			$(document).ready(function () {
				pt.initMainPage()
				$(".dataTables_length").css({ "display" :"none" });
			})

			/*예약현황 - bar chart*/
			Highcharts.chart('bar-container', {
				chart: {
					type: 'column'
				},
				title: {
					text: 'Daily Sale'
				},
				subtitle: {
					text: ''
				},
				xAxis: {
					categories: [
						'Sophia',
						'Joyce',
						'Sophia',
						'Joyce',
						'Sophia',
						'Joyce',
						'Sophia',
						'Joyce',
						'Sophia',
						'Joyce',
						'Sophia',
						'Joyce'
					],
					crosshair: true
				},
				yAxis: {
					min: 0,
					title: {
						text: 'amount'
					}
				},
                credits: {
					enabled: false
				}, 
				tooltip: {
					headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
					pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
						'<td style="padding:0"><b>{point.y:.1f}</b></td></tr>',
					footerFormat: '</table>',
					shared: true,
					useHTML: true
				},
				plotOptions: {
					column: {
						pointPadding: 0.2,
						borderWidth: 0
					},
					series: {
						pointWidth: 11
					}
				},
				series: [{
					name: 'USD',
					data: [83.6, 78.8, 98.5, 93.4, 106.0, 84.5, 105.0, 104.3, 91.2, 83.5, 106.6, 92.3],
					color: '#F45B5B'

				}]
			});
            /*예약현황 - pie chart*/
			Highcharts.chart('pie-container', {
			  chart: {
				plotBackgroundColor: null,
				plotBorderWidth: null,
				plotShadow: false,
				type: 'pie'
			  },
			  title: {
				text: ''
			  },
			  credits: {
				enabled: false
			  },
			  tooltip: {
				pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
			  },
			  plotOptions: {
				pie: {
				  allowPointSelect: true,
				  cursor: 'pointer',
				  dataLabels: {
					enabled: false
				  },
				  showInLegend: true
				}
			  },
			  series: [{
				name: '경로',
				colorByPoint: true,
				data: [{
				  name: '웹',
				  y: 61.41,
				  sliced: true,
				  selected: true
				}, {
				  name: '협력사',
				  y: 11.84
				}, {
				  name: '직접',
				  y: 10.85
				}, {
				  name: '기타',
				  y: 4.67
				}]
			  }]
			});
			
		</script>
		
		

    </body>
</html>
