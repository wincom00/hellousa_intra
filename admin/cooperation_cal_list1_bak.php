<?php
    include "include/header.php";
	
	if ($_COOKIE[MEMLOGIN_ADMIN_PURUN] != "") {
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
	$addyear ="+ 1 year";
	if (($StartYMD == '') || ($StartYMD =='null')) {
    	// 오늘 날짜 기준 최근 5달치를 가져온다.
	    $start_date = explode("-",date("Y-m-d"));
		$future_date = date("Y-m-d", strtotime(date("Y-m-d", $start_date) . $addyear));
		$StartYMD = date("Y-m-d");
	} else {
		
		$start_date = explode("-",$StartYMD);
		$future_date = date("Y-m-d", strtotime(date("Y-m-d", $start_date) . $addyear));
		
	}
?>
	<div id="contentwrapper" class="reservationDetailForm">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb module">
				<ul>
					<li><a href="/"><i class="glyphicon glyphicon-home"></i></a></li>
					<li><a href="#">업체정산</a></li>
					<li>업체별정산현황</li>
				</ul>
			</div>
			<div class="row">
				<div class="col-sm-12 col-md-12">
					<form action=""  method="post" name="frmName">
						<table class="table table-bordered table-condensed gridSixteen reserveTable formDetail">
                            <tbody>
                                
                                <tr>   
                                    <td colspan="2" class="active text-center formHeader">업체명</td>  
                                    <td colspan="6"><input type="text" name="companyName" class="form-control" aria-label="업체명입력" placeholder="업체명입력" value="" /></td>
                                    <td colspan="2" class="active text-center formHeader">지역별업체</td>
                                    <td colspan="6">   
                                        <select name=company_area  class="inpubase md"><?= printBaseCode4_without('A01',$v_info[company_area]); ?></select>
                                    </td>
                                </tr>
                                <tr>   
                                    <td colspan="2" class="active text-center formHeader">출발일조회</td>  
                                    <td colspan="14">
                                        <div class="row">
                                            <div class="col-sm-3">
                                                <div class="input-group input-group-sm">
                                                    <input type="text" name="StartYMD" data-date-format='yyyy-mm-dd' class="form-control js-dateInputWithBlocks js-tourDates js-tourStartDate" aria-label="조회기간" placeholder="조회기간">
                                                    <span class="input-group-btn">
                                                        <button class="btn btn-default js-dateInputBtn" type="button"><span class="glyphicon glyphicon-calendar" aria-hidden="true"></span></button>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="col-sm-3">
                                                <div class="input-group input-group-sm">
                                                    <input type="text" name="EndYMD" data-date-format='yyyy-mm-dd' class="form-control js-dateInputWithBlocks js-tourDates js-tourEndDate" aria-label="조회기간" placeholder="조회기간">
                                                    <span class="input-group-btn">
                                                        <button class="btn btn-default js-dateInputBtn" type="button"><span class="glyphicon glyphicon-calendar" aria-hidden="true"></span></button>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>   
                                    <td colspan="16" class="text-center"><button type='submit' class="btn btn-primary btn-sm btn1">검색</button></td>
                                </tr>
                            </tbody>
                        </table>
					</form>
					<br />
					<div class="row">
						<div class="col-sm-12">
							<table class="table table-striped table-bordered table-hover table-condensed" id="custom_table">
								<thead>
									<tr>
									    <th>업체명(ID)</th>
										<th>2017 11월</th>
										<th>2017 12월</th>
										<th>2018 1월</th>
										<th>2018 2월</th>
										<th>2018 3월</th>
										<th>2018 4월</th>
										<th>2018 5월</th>
										<th>2018 6월</th>
										<th>2018 7월</th>
										<th>2018 8월</th>
										<th>2018 9월</th>
										<th>2018 10월</th>
										<th>2018 11월</th>
										<th>2018 12월</th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td align="center">A투어-KR1101C$ 1,500</td>
										<td align="right"><font color="blue">8,474.95</font><br/>-5909.05</td>
										<td align="right"></td>
										<td align="right"></td>
										<td align="right"></td>
										<td align="right"></td>
										<td align="right"></td>
										<td align="right"></td>
										<td align="right"></td>
										<td align="right"></td>
										<td align="right"></td>
										<td align="right"></td>
										<td align="right"></td>
										<td align="right"></td> 
										<td align="right"></td>
									</tr>
									<tr>
										<td align="center">B투어-KR1101C$ 1,500</td>
										<td align="right"></td>
										<td align="right"></td>
										<td align="right"></td>
										<td align="right"></td>
										<td align="right" id="7"><font color="blue">8,474.95</font><br/>-5909.05<br/><font color="red">14384.00</font></td>
										<td align="right"></td>
										<td align="right"></td>
										<td align="right"></td>
										<td align="right"></td>
										<td align="right"></td>
										<td align="right"></td>
										<td align="right"></td>
										<td align="right"></td> 
										<td align="right"></td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>
				</div><!-- -->
			</div>                
		</div>

	</div>
    <?php
		include "include/side_m.php"
	?>
    <script>
		$(document).ready(function () {
            pt.initReservationDetail()

			{
				var scope = $('.reservationDetailForm')
				for (var i = 0; i < scope.length; i++) {
					var self = $(scope[i])
					var tourStartDate = self.find('.js-tourStartDate')
					var tourEndDate = self.find('.js-tourEndDate')
					var singleDateTourDate1 = self.find('.js-singleDayTourDate1')

					tourStartDate.datepicker($.extend({}, pt.defaults.datepicker, {
						daysOfWeekEnabled: [
							/*2, 3, 4*/
						],
						datesDisabled: [
							'01/10/2019',
							'01/25/2019',
						],
						datesEnabled: [    // to override datesDisabled
							'01/10/2019',
							'01/25/2019',
						],
						datesOnly: [    // will override anything
							// '01/10/2019',
						],
						beforeShowDay: function (date) {
							var enabled = pt.beforeShowDayFunc(date, this)
							return enabled
						},
					})).off('changeDate').on('changeDate', { self: self }, pt.changeTourStartDate)
					tourEndDate.prop({ disabled: true })
					.closest('.input-group').find('button').prop({ disabled: true })
				}
			}
			pt.initReservationList()
            
            var args = {paging:false, ordering:false, info:false};
           
             /*click row event*/
            var table = $('#custom_table').DataTable(args);
 
            $('#custom_table tbody').on( 'click', 'td', function () {
                
                var id = table.row( this ).id();
                var url = "cooperation_cal_list2.php?division=6&pdx=4&sub=10";
                $(location).attr('href',url);
                
                //alert($(this).attr("id"));
                
            } );
            
		})
	</script>
    </body>
</html>
