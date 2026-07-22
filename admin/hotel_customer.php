<?php
    include "include/inc_base.php";
    if ($_COOKIE[MEMLOGIN_ADMIN_HELLO] !="") {
	} else {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}

    if ($gid != "") {
		

	}
    $h_code = $_GET['h_code'];
	$prodInfo = getProductMaster($_GET['p_code']);
	$g_dbinfo1 = getguideInfor($_GET['g_code'],$_GET['s_code']);
	$g_dbinfo = getinfo_dbMemberg($g_dbinfo1[guide_id]);
	$g_dbinfo2 = getinfo_dbMemberg($g_dbinfo1[sguide_id]);
	//print_r($g_dbinfo1);
	//exit;
	$picM = getPicGrM($s_code);
	$picStr  = getPicGr2($s_code,$stdate);
	//$picctot = $picM."&nbsp;&nbsp;".$picStr;
	//$guideinfo = getguideInfor($_GET['grand_eCode'],$s_code);
	$carinfo = getCCInfor($s_code);

	$gss = getGuideInfo2($s_code);
	$ccinfo = getCarInfo($gss[c_id]);
	$bus_team = codebaseName($ccinfo[bus_team]);
	function custlist() {
             global $dbConn,$p_code,$gid,$s_code,$stdate,$rcode,$h_code;
		     $qry1="SELECT a.grand_eCode,a.hotel_code,a.stDate_sub,b.h_name,b.m_rate,a.day ,a.pcnt,a.sub_eCode,a.p_name,count(c.rev_nm) cnt FROM hotel_assign a ,product_hotel b,tour_car c WHERE a.hotel_code=b.h_code && a.p_code=c.p_code && a.stDate=c.stDate && a.stDate_sub ='".$stdate."'  && a.hotel_code='".$h_code."' group by  a.p_code,a.day order by a.day asc";
			
			
			 $rst1 = $dbConn->query($qry1);
			 //echo $qry1;
			 $num1= $rst1->num_rows;
			 //echo $num1;
			 $rst1 = $dbConn->query($qry1);

			 while($row1 = $rst1->fetch_assoc()){
				
				$g_dbinfo1 = getguideInfor($row1['grand_eCode'],$row1['sub_eCode']);
				$g_dbinfo = getinfo_dbMemberg($g_dbinfo1['guide_id']);
				$g_dbinfo2 = getinfo_dbMemberg($g_dbinfo1['sguide_id']);
				

				//$local_start  = date("Y-m-d",mktime (0,0,0,$s_date[1]  , $s_date[2]+$add_date, $s_date[0]));
				echo "<tr>
				      <td>$row1[day]</td>
					  <td>$row1[h_name]</td>
					  <td>$row1[p_name]</td>
					  <td>$row1[cnt]</td>
					  <td>{$row1['stDate_sub']}</td>
					  <td>$row1[pcnt]</td>
					 
					  <td >$g_dbinfo[kor_name]/$g_dbinfo2[kor_name]</td>
					  					  
					</tr>";
			  
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
			header("Content-Description: PHP5 Generated Data");
		    echo "<meta http-equiv='Content-Type' content='application/vnd.ms-excel; charset=utf-8'/>";
       }
	   ?>
	   <?php
	    if($mode!='down') {
             echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>";
		 } ?>
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>투어헬로USA 인트라넷</title>
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
			<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs/jszip-2.5.0/dt-1.10.18/af-2.3.2/b-1.5.4/b-colvis-1.5.4/b-flash-1.5.4/b-html5-1.5.4/b-print-1.5.4/cr-1.5.0/fc-3.2.5/fh-3.1.4/kt-2.5.0/r-2.2.2/rg-1.1.0/rr-1.2.4/sc-1.5.0/sl-1.2.6/datatables.min.css"/>
            <link rel="stylesheet" href="https://cdn.datatables.net/select/1.3.0/css/select.dataTables.min.css" />
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
			<link rel="stylesheet" href="css/paran.css?sid=5fe18a1a-0023-476e-afb3-66cdb279d9f7" />
		<!-- favicon -->
           

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
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
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
		<!-- timepicker -->
			<!-- <script src="lib/timepicker/js/bootstrap-timepicker.min.js"></script> -->
			<script src="lib/bootstrap-timepicker/js/bootstrap-timepicker.min.js"></script>
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

		<!-- paran js -->
			<script src="js/dongbu.js?sid=b778ad81-59cf-49a4-b7bf-b9bc7808d745"></script>

		<!-- paran_lee js -->
			<script src="js/dongbu_lee.js?sid=f10d80e0-c59c-4b4f-8927-17e44a330d8e"></script>
			<?php }?>
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
    
</style>
<body>
	<div id="contentwrapper" class="reservationDetailForm">
         <? if ($mode != 'down') { ?>
			<div id="jCrumbs" class="breadCrumb 
			module">
				<ul>
					<li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li>
					<li><a href="#">행사배정관리</a></li>
					<li>행사명단</li>
					<li></li>
				</ul>
			</div>
		<?} ?>
			<div class="row">
				<div class="col-sm-12 col-md-12">
					
					<div class="row">
						<div class="col-sm-12">
						    <form action="" name="frmName" method="post">
						      <input type="hidden" name="mode" value="down">
                              <fieldset class="guide-assign-border">
                                <legend class="guide-assign-border"><span class="pull-left small text-muted">행사고객현황</span></legend>
								<?php if ($mode != 'down') { ?>
                                <div class="row no-nav">
                                    <div id="custom_button" class="col-sm-12 text-right">
                                        <button type="submit" class="btn btn-xs btn-default js-xxx" >엑셀보내기</button>
                                        <button type="button" class="btn btn-xs btn-default js-xxx" onclick="pageprint()">프린트</button>
										
                                    </div>
                                </div>
								<?}?>
								<br/>
								<div class="col-sm-12" id='printarea'>
								
                                <table id="custom_table" class="table table-striped table-bordered table-hover table-condensed text-center">
                                    <thead>
                                        <tr>  
										  <th class="tcenter" width='10%'>일차</th>
                                          <th class="tcenter" width='10%'>호텔명</th>
                                          <th class="tcenter" width='10%'>행사명</th>
										  <th class="tcenter" width='5%'>인원</th>
										  <th class="tcenter" width='5%'>숙박일</th>
										  
                                          <th class="tcenter" width='7%'>방갯수</th>
										
										  <th class="tcenter" width='7%'>가이드</th>
										 
										  
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
		function beforePrint()
		{ 
		   initBody = document.body.innerHTML; 
		   document.body.innerHTML = printarea.innerHTML;
		} 

		function afterPrint()
		{ 
		  document.body.innerHTML = initBody; 
		} 

		function pageprint()
		{
			 window.onbeforeprint = beforePrint; 
			 window.onafterprint = afterPrint; 
			 window.print(); 
		}
		var ctr=0;
	    function openwin(r_code,ty) { 
	       var winName = "all_"+(ctr++);
		   
		   window.open("base_reservation_m.php?estimateCode="+r_code+"&division=3&pdx=2&sub="+ty+"",winName,"width=1300,height=700,scrollbars=1");
	    }
		function pageprint2(s_code,stdate)
		{
			 
		   var simplename = "simplelist";	
           var winName = "all_"+(ctr++);
		   window.open("print_customer.php?s_code="+s_code+"&stdate="+stdate,simplename,"width=900,height=1080,scrollbars=1");
  
		}
      
	</script>
    </body>
</html>
