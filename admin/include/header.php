<?php
    include "inc_base.php";
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");
    $new_date=date("U", mktime(0,0,0,(date("m")), (date("d")), date("Y")));
	$dates=date("Y-m-d", $new_date);

	$qry2 = "select max(id) as mxid from att_log where userid='".$user_dbinfo['userid']."' && date_format( login_date, '%Y-%m-%d' ) = '".$dates."'";


	$rst2 = $dbConn->query($qry2);
	$row0 = $rst2->fetch_assoc();

	$m_qry1 = "select * from att_log where userid='".$user_dbinfo['userid']."' && date_format( login_date, '%Y-%m-%d' ) = '$dates' && id='".$row0['mxid']."' ";
	$m_rst1 = $dbConn->query($m_qry1);
	$m_row1 = $m_rst1->fetch_assoc();// mysql_fetch_assoc($m_rst1);
	
	
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>헬로USA인트라넷</title>

        <!-- Bootstrap framework -->
            <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css" />
            <link rel="stylesheet" href="bootstrap/css/bootstrap-theme.min.css" />
            <link rel="stylesheet" href="css/normalize.css" />
			<link rel="stylesheet" href="css/calc.css" />
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
			<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs/jszip-2.5.0/dt-1.10.18/af-2.3.2/b-1.5.4/b-colvis-1.5.4/b-flash-1.5.4/b-html5-1.5.4/b-print-1.5.4/cr-1.5.0/fc-3.2.5/fh-3.1.4/kt-2.5.0/r-2.2.2/rg-1.1.0/rr-1.2.4/sc-1.5.0/sl-1.2.6/datatables.min.css"/>
            <link rel="stylesheet" href="https://cdn.datatables.net/select/1.3.0/css/select.dataTables.min.css" />
        <!-- datepicker -->
           
			<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.3.0/css/datepicker.css" rel="stylesheet" type="text/css" />
		<!-- timepicker -->
            <!-- <link rel="stylesheet" href="lib/timepicker/css/bootstrap-timepicker.css" /> -->
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-timepicker/0.5.2/css/bootstrap-timepicker.min.css" />
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
		<!--<link id="link_theme" rel="stylesheet" href="css/green.css">-->
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.0.11/css/all.css" integrity="sha384-p2jx59pefphTFIpeqCcISO9MdVfIm4pNnsL08A6v5vaQc4owkQqxMV8kg4Yvhaw/" crossorigin="anonymous">
		<!-- main styles -->
            <link rel="stylesheet" href="css/style.css" />
		<!-- purun css -->
			<link rel="stylesheet" href="css/hello.css?sid=5fe18a1a-0023-476e-afb3-66cdb279d9f7" />
		<!-- favicon -->
            <link rel="apple-touch-icon" sizes="180x180" href="img/favi/apple-touch-icon.png">
			<link rel="icon" type="image/png" sizes="32x32" href="img/favi/favicon-32x32.png">
			<link rel="icon" type="image/png" sizes="16x16" href="img/favi/favicon-16x16.png">
			<link rel="manifest" href="img/favi/site.webmanifest">
			
			<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/chosen/1.8.7/chosen.css" integrity="sha512-0nkKORjFgcyxv3HbE4rzFUlENUMNqic/EzDIeYCgsKa/nwqr2B91Vu/tNAu4Q0cBuG4Xe/D1f/freEci/7GDRA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
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
		<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
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
		<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.3.0/js/bootstrap-datepicker.js"></script>
		<!-- timepicker -->
			<!-- <script src="lib/timepicker/js/bootstrap-timepicker.min.js"></script> -->
			<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-timepicker/0.5.2/js/bootstrap-timepicker.min.js"></script>
		<!-- clockpicker -->
			<script src="lib/bootstrap-clockpicker/dist/bootstrap-clockpicker.min.js"></script>

		<!-- switch buttons -->
			<script src="lib/bootstrap-switch/dist/js/bootstrap-switch.min.js"></script>
        <!-- datatables -->
			<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
			<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
			<script type="text/javascript" src="https://cdn.datatables.net/v/bs/jszip-2.5.0/dt-1.10.18/af-2.3.2/b-1.5.4/b-colvis-1.5.4/b-flash-1.5.4/b-html5-1.5.4/b-print-1.5.4/cr-1.5.0/fc-3.2.5/fh-3.1.4/kt-2.5.0/r-2.2.2/rg-1.1.0/rr-1.2.4/sc-1.5.0/sl-1.2.6/datatables.min.js"></script>
			<script type="text/javascript" src="https://cdn.datatables.net/select/1.3.0/js/dataTables.select.min.js"></script>
			<link type="text/css" href="//gyrocode.github.io/jquery-datatables-checkboxes/1.2.11/css/dataTables.checkboxes.css" rel="stylesheet" />
            <script type="text/javascript" src="//gyrocode.github.io/jquery-datatables-checkboxes/1.2.11/js/dataTables.checkboxes.min.js"></script>
			<script src="js/tinymce/tinymce.min.js" referrerpolicy="origin"></script>
		<!-- dongbu js -->
			<script src="js/dongbu.js?sid=b778ad81-59cf-49a4-b7bf-b9bc7808d745"></script>

		<!-- purun_lee js -->
			<script src="js/dongbu_lee.js?sid=f10d80e0-c59c-4b4f-8927-17e44a330d8ekk"></script>
			<script src="https://cdnjs.cloudflare.com/ajax/libs/chosen/1.8.7/chosen.jquery.min.js" integrity="sha512-rMGGF4wg1R73ehtnxXBt5mbUfN9JUJwbk21KMlnLZDJh7BkPmeovBuddZCENJddHYYMkCh9hPFnPmS9sspki8g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
	</head>
	<script>
        setInterval(function(){
            var now = new Date();

		    var year= now.getFullYear();
		    var mon = (now.getMonth()+1)>9 ? ''+(now.getMonth()+1) : '0'+(now.getMonth()+1);
		    var day = now.getDate()>9 ? ''+now.getDate() : '0'+now.getDate();
				  
		    var chan_val = year + '-' + mon + '-' + day;

            var timer = new Date();
            var h = timer.getHours();
            var m = timer.getMinutes();
            var s = timer.getSeconds();
            document.getElementById('clock').innerHTML = chan_val+" "+h + ":" + m + ":" + s;
        },1000);
    </script>
    <body class="full_width menu_hover">
        <div class="style_switcher">
			<div class="sepH_c">
				<p>Colors:</p>
				<div class="clearfix">
					<a href="javascript:void(0)" class="style_item jQclr blue_theme " title="blue">blue</a>
					<a href="javascript:void(0)" class="style_item jQclr dark_theme style_active" title="dark">dark</a>
					<a href="javascript:void(0)" class="style_item jQclr green_theme" title="green">green</a>
					<a href="javascript:void(0)" class="style_item jQclr brown_theme" title="brown">brown</a>
					<a href="javascript:void(0)" class="style_item jQclr eastern_blue_theme" title="eastern_blue">eastern blue</a>
					<a href="javascript:void(0)" class="style_item jQclr tamarillo_theme" title="tamarillo">tamarillo</a>
				</div>
			</div>
			<div class="sepH_c">
				<p>Backgrounds:</p>
				<div class="clearfix">
					<span class="style_item jQptrn style_active ptrn_def" title=""></span>
					<span class="ssw_ptrn_a style_item jQptrn" title="ptrn_a"></span>
					<span class="ssw_ptrn_b style_item jQptrn" title="ptrn_b"></span>
					<span class="ssw_ptrn_c style_item jQptrn" title="ptrn_c"></span>
					<span class="ssw_ptrn_d style_item jQptrn" title="ptrn_d"></span>
					<span class="ssw_ptrn_e style_item jQptrn" title="ptrn_e"></span>
				</div>
			</div>
			<div class="sepH_c">
				<p>Layout:</p>
				<div class="clearfix">
					<label class="radio-inline"><input name="ssw_layout" id="ssw_layout_fluid" value="" checked="" type="radio"> Fluid</label>
					<label class="radio-inline"><input name="ssw_layout" id="ssw_layout_fixed" value="gebo-fixed" type="radio"> Fixed</label>
				</div>
			</div>
			<div class="sepH_c">
				<p>Sidebar position:</p>
				<div class="clearfix">
					<label class="radio-inline"><input name="ssw_sidebar" id="ssw_sidebar_left" value="" checked="" type="radio"> Left</label>
					<label class="radio-inline"><input name="ssw_sidebar" id="ssw_sidebar_right" value="sidebar_right" type="radio"> Right</label>
				</div>
			</div>
			<div class="sepH_c">
				<p>Show top menu on:</p>
				<div class="clearfix">
					<label class="radio-inline"><input name="ssw_menu" id="ssw_menu_click" value="" checked="" type="radio"> Click</label>
					<label class="radio-inline"><input name="ssw_menu" id="ssw_menu_hover" value="menu_hover" type="radio"> Hover</label>
				</div>
			</div>

			<div class="gh_button-group">
				<a href="#" id="showCss" class="btn btn-success btn-sm">Show CSS</a>
				<a href="#" id="resetDefault" class="btn btn-default btn-sm">Reset</a>
			</div>
			<div class="hide">
				<ul id="ssw_styles">
					<li class="small ssw_mbColor sepH_a" style="display:none">body {<span class="ssw_mColor sepH_a" style="display:none"> color: #<span></span>;</span> <span class="ssw_bColor" style="display:none">background-color: #<span></span> </span>}</li>
					<li class="small ssw_lColor sepH_a" style="display:none">a { color: #<span></span> }</li>
				</ul>
			</div>
		</div>		<div id="maincontainer" class="clearfix">

            <header>
				<div style="background-color:#0062dd;">
                  
				</div>
				<nav class="navbar navbar-default navbar-fixed-top" role="navigation">
					<div class="navbar-inner">
						<div class="container-fluid">
							<a class="brand pull-left" href="index.php" style="font-weight: 900;"><font color="#f8ca41"><b>Tour Hello</b></font> USA</a>
							
							<ul class="nav navbar-nav" id="mobile-nav">
							      <?php echo printMenu($user_dbinfo['userid']); ?>
								  
							
							</ul>
												
							<ul class="nav navbar-nav user_menu pull-right">
								<li class="hidden-phone hidden-tablet">
									<div class="nb_boxes clearfix">
									<?php if ($user_dbinfo['division'] != "guide") { ?>
									<a  href="input_batchV2.php" data-placement="bottom" target='_blank' data-container="body" class="label bs_ttip" title="스마트등록">스마트등록&nbsp;<i class="splashy-image_modernist"></i></a>
									<?php } ?>
									<?php if ($user_dbinfo['division'] != "guide") { ?>
									<a  href="sc_hotel.php" data-placement="bottom" target='_blank' data-container="body" class="label bs_ttip" title="호텔스캐쥴">호텔스케줄		
									&nbsp;<i class="splashy-group_blue_edit"></i></a>
									<?php } ?>
									<a  href="memo_list.php" data-placement="bottom" target='_blank' data-container="body" class="label bs_ttip" title="메모바로가기">메모바로가기&nbsp;<i class="splashy-group_blue_edit"></i></a>
									<?php if ($user_dbinfo['division'] == "admin") { ?>
										<a  href="car_block_manage.php" data-placement="bottom" target='_blank' data-container="body" class="label bs_ttip" title="예약검색">차량블록&nbsp;<i class="splashy-group_blue_edit"></i></a>
									<?php } ?>
									<?php if ($user_dbinfo['division'] != "guide") { ?>
										<a  href="total_reservation.php" data-placement="bottom" target='_blank' data-container="body" class="label bs_ttip" title="예약검색">통합예약검색&nbsp;<i class="splashy-group_blue_edit"></i></a>
									<?php } ?>
										<a  href="sc_local.php" data-placement="bottom" data-container="body" class="label bs_ttip" title="New tasks" target='_blank'>전체스케줄표 <i class="splashy-image_modernist"></i></a>
									<?php if ($user_dbinfo['division'] != "guide") { ?>
										<!-- <a  data-backdrop="static" href="sc_out.php" data-placement="bottom" data-container="body" class="label bs_ttip" title="New tasks" target='_blank'>아웃바운드스케줄표 <i class="splashy-image_modernist"></i></a> -->
										 <a  data-backdrop="static" href="guide_block.php" data-placement="bottom" data-container="body" class="label bs_ttip" title="New tasks" target='_blank'>가이드스케줄표 <i class="splashy-image_modernist"></i></a>
									<?php } ?>
									</div>
								</li>
								
								<!--
								<li class="divider-vertical hidden-sm hidden-xs"></li>
								<li class="dropdown">
									<a href="#" class="dropdown-toggle" data-toggle="dropdown"><img src="img/user_avatar.png" alt="" class="user_avatar">Johny Smith <b class="caret"></b></a>
									<ul class="dropdown-menu dropdown-menu-right">
										<li><a href="user_profile.html">My Profile</a></li>
										<li><a href="javascrip:void(0)">Another action</a></li>
										<li class="divider"></li>
										<li><a href="login.html">Log Out</a></li>
									</ul>
								</li>-->
							</ul>
						</div>
					</div>
				</nav>
<!--
				<div class="modal fade" id="myMail">
					<div class="modal-dialog">
						<div class="modal-content">
							<div class="modal-header">
								<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
								<h3 class="modal-title">New Messages</h3>
							</div>
							<div class="modal-body">
								<table class="table table-condensed table-striped" data-provides="rowlink">
									<thead>
										<tr>
											<th>Sender</th>
											<th>Subject</th>
											<th>Date</th>
											<th>Size</th>
										</tr>
									</thead>
									<tbody>
										<tr>
											<td>Declan Pamphlett</td>
											<td><a href="javascript:void(0)">Lorem ipsum dolor sit amet</a></td>
											<td>23/05/2015</td>
											<td>25KB</td>
										</tr>
										<tr>
											<td>Erin Church</td>
											<td><a href="javascript:void(0)">Lorem ipsum dolor sit amet</a></td>
											<td>24/05/2015</td>
											<td>15KB</td>
										</tr>
										<tr>
											<td>Koby Auld</td>
											<td><a href="javascript:void(0)">Lorem ipsum dolor sit amet</a></td>
											<td>25/05/2015</td>
											<td>28KB</td>
										</tr>
										<tr>
											<td>Anthony Pound</td>
											<td><a href="javascript:void(0)">Lorem ipsum dolor sit amet</a></td>
											<td>25/05/2015</td>
											<td>33KB</td>
										</tr>
									</tbody>
								</table>
							</div>
							<div class="modal-footer">
								<button type="button" class="btn btn-default">Go to mailbox</button>
							</div>
						</div>
					</div>
				</div>

				<div class="modal fade" id="myTasks">
					<div class="modal-dialog">
						<div class="modal-content">
							<div class="modal-header">
								<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
								<h3 class="modal-title">New Tasks</h3>
							</div>
							<div class="modal-body">
								<table class="table table-condensed table-striped" data-provides="rowlink">
									<thead>
										<tr>
											<th>id</th>
											<th>Summary</th>
											<th>Updated</th>
											<th>Priority</th>
											<th>Status</th>
										</tr>
									</thead>
									<tbody>
										<tr>
											<td>P-23</td>
											<td><a href="javascript:void(0)">Admin should not break if URL…</a></td>
											<td>23/05/2015</td>
											<td><span class="label label-danger">High</span></td>
											<td>Open</td>
										</tr>
										<tr>
											<td>P-18</td>
											<td><a href="javascript:void(0)">Displaying submenus in custom…</a></td>
											<td>22/05/2015</td>
											<td><span class="label label-warning">Medium</span></td>
											<td>Reopen</td>
										</tr>
										<tr>
											<td>P-25</td>
											<td><a href="javascript:void(0)">Featured image on post types…</a></td>
											<td>22/05/2015</td>
											<td><span class="label label-success">Low</span></td>
											<td>Updated</td>
										</tr>
										<tr>
											<td>P-10</td>
											<td><a href="javascript:void(0)">Multiple feed fixes and…</a></td>
											<td>17/05/2015</td>
											<td><span class="label label-warning">Medium</span></td>
											<td>Open</td>
										</tr>
									</tbody>
								</table>
							</div>
							<div class="modal-footer">
								<button type="button" class="btn btn-default">Go to task manager</button>
							</div>
						</div>
					</div>
				</div>
-->
			</header>
  <div class="calculator-toggle" onclick="toggleCalculator()">
    <span style="font-size: 24px; color: #fff;">+</span>
  </div>
  <div class="calculator-widget" id="calculator">
    <input type="text" id="display" value="0" readonly>
    <div class="buttons">
      <button class="utility" onclick="clearDisplay()">C</button>
      <button class="utility" onclick="toggleSign()">±</button>
      <button class="utility" onclick="appendToDisplay('%')">%</button>
      <button class="operator" onclick="appendToDisplay('/')">÷</button>
      <button class="number" onclick="appendToDisplay('7')">7</button>
      <button class="number" onclick="appendToDisplay('8')">8</button>
      <button class="number" onclick="appendToDisplay('9')">9</button>
      <button class="operator" onclick="appendToDisplay('*')">×</button>
      <button class="number" onclick="appendToDisplay('4')">4</button>
      <button class="number" onclick="appendToDisplay('5')">5</button>
      <button class="number" onclick="appendToDisplay('6')">6</button>
      <button class="operator" onclick="appendToDisplay('-')">-</button>
      <button class="number" onclick="appendToDisplay('1')">1</button>
      <button class="number" onclick="appendToDisplay('2')">2</button>
      <button class="number" onclick="appendToDisplay('3')">3</button>
      <button class="operator" onclick="appendToDisplay('+')">+</button>
      <button class="number zero" onclick="appendToDisplay('0')">0</button>
      <button class="number" onclick="appendToDisplay('.')">.</button>
      <button class="equals" onclick="calculate()">=</button>
    </div>
  </div>

  <script>
    let display = document.getElementById('display');
    let calculator = document.getElementById('calculator');

    function appendToDisplay(value) {
      if (display.value === '0' && value !== '.') {
        display.value = value;
      } else {
        display.value += value;
      }
    }

    function clearDisplay() {
      display.value = '0';
    }

    function toggleSign() {
      if (display.value.startsWith('-')) {
        display.value = display.value.substring(1);
      } else if (display.value !== '0') {
        display.value = '-' + display.value;
      }
    }

    function backspace() {
      display.value = display.value.slice(0, -1);
      if (display.value === '') {
        display.value = '0';
      }
    }

    function calculate() {
      try {
        let expression = display.value.replace(/%/g, '/100');
        display.value = eval(expression);
        if (display.value === 'Infinity' || display.value === '-Infinity') {
          display.value = 'Error';
          setTimeout(clearDisplay, 1000);
        }
      } catch (error) {
        display.value = 'Error';
        setTimeout(clearDisplay, 1000);
      }
    }

    function toggleCalculator() {
      if (calculator.style.display === 'none' || calculator.style.display === '') {
        calculator.style.display = 'block';
      } else {
        calculator.style.display = 'none';
      }
    }

    // 키보드 입력 처리
    document.addEventListener('keydown', function(event) {
      const key = event.key;
      if (/[0-9]/.test(key)) {
        appendToDisplay(key);
      } else if (key === '+' || key === '-' || key === '*' || key === '/') {
        appendToDisplay(key);
      } else if (key === '.') {
        appendToDisplay('.');
      } else if (key === '%') {
        appendToDisplay('%');
      } else if (key === 'Enter') {
        calculate();
      } else if (key === 'Escape') {
        clearDisplay();
        toggleCalculator(); // Escape 키로 계산기 접기
      } else if (key === 'Backspace') {
		 
        backspace();
      }
    });
  </script>
  
