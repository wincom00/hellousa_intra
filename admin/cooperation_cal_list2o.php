<?php
    include "include/header.php";
	
	if ($_COOKIE[MEMLOGIN_ADMIN_HELLO] != "") {
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
?>
	<div id="contentwrapper" class="reservationDetailForm">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb 
			module">
				<ul>
					<li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li>
					<li><a href="#">업체정산</a></li>
					<li>업체별정산현황-2</li>
				</ul>
			</div>
			<div class="row">
				<div class="col-sm-12 col-md-12">
					<form action=""  method="post" name="frmName">
						<table class="table table-bordered table-condensed gridSixteen reserveTable formDetail">
                            <tbody>
                                <tr>
                                    <td colspan="2" class="active text-center formHeader">지역별조회</td>
                                    <td colspan="2">   
                                        <select class="form-control" name="areaSelect">
                                            <option value="" selected>전체</option>
                                            <option value="">KR:한국</option>
                                        </select>
                                    </td>
                                    <td colspan="2" class="active text-center formHeader">협력사명</td>
                                    <td colspan="2"><input type="text" name="companyName" class="form-control" aria-label="협력사명입력" placeholder="협력사명" value="" /></td>
                                    <td colspan="2" class="active text-center formHeader">출발일기준</td>
                                    <td colspan="2">
                                        <div class="row">
                                            <div class="col-sm-12">
                                                <div class="input-group input-group-sm">
                                                    <input type="text" name="singleDayTourStartDate" data-date-format='yyyy-mm-dd' class="form-control js-singleDayTourDate js-singleDayTourDate1" aria-label="출발일" placeholder="출발일">
                                                    <span class="input-group-btn">
                                                        <button class="btn btn-default js-dateInputBtn" type="button"><span class="glyphicon glyphicon-calendar" aria-hidden="true"></span></button>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td colspan="1" class="text-center"><button type='submit' class="btn btn-primary btn-sm btn1">검색</button></td>
                                    <td colspan="3" class="text-center"><button type='button' class="btn btn-xs btn-default js-xxx">인보이스출력</button></td>
                                </tr>
                            </tbody>
                        </table>
					</form>
					<br />
					<div class="row">
						<div class="col-sm-12">
							<table class="table table-striped table-bordered table-hover table-condensed js-productTable">
								<thead>
									<tr>
									    <th width="1%" align="center"><input type="checkbox" clas="form-control"></th>
										<th>날짜</th>
										<th>인원</th>
										<th>상품명</th>
										<th>대표고객명</th>
										<th>발런스</th>
										<th>지출예정</th>
										<th>입금예정</th>
										<th>페이먼트</th>
										<th>예약상태</th>
										<th>접수자</th>
										<th>정산상태</th>
                                        <th>메모</th>
										<th>결제</th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td align="center"><input type="checkbox" clas="form-control"></td>
										<td align="center">2018-12-27<br/>TD2018051800002</td>
										<td align="center">3</td>
										<td>퀘벡2박3일<br/>2018-12-27~2018-12-30</td>
										<td align="center">아무개</td>
										<td align="right">C$100</td>
										<td align="right"></td>
										<td align="right">C$1000</td>
										<td align="right">C$0</td>
										<td align="center">예약확정</td>
										<td align="center">테스트</td>
										<td align="center">대기</td>
										<td>테스트입니다.</td>
										<td align="center"><button type='button' class="btn btn-xs btn-default js-xxx">PAY</button></td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>
					<br/>
                   <div class="row">
						<div class="col-sm-12">
                            <table class="table table-bordered table-condensed gridSixteen reserveTable formDetail">
                                <tbody>
                                    <tr>
                                        <td colspan="2" class="text-center">총인원</td>
                                        <td colspan="2">9명</td>
                                        <td colspan="2" class="text-center">총합계</td>
                                        <td colspan="10">
                                            <div class="row">
                                                <div class="col-sm-12"></div> 
                                            </div>
                                            <div class="row">
                                                <div class="col-sm-12">
                                                   <div class="col-sm-4 text-right">지출</div>
                                                   <div class="col-sm-4 text-right">입금</div>
                                                   <div class="col-sm-4 text-right">최종발란스</div>
                                                </div> 
                                            </div>
                                            <div class="row">
                                                <div class="col-sm-12">
                                                   <div class="col-sm-4 text-right">
                                                       <div class="col-sm-4 text-right">예정</div>
                                                       <div class="col-sm-8 text-right">C$0.00</div> 
                                                   </div>
                                                   <div class="col-sm-4 text-right" >C$3000</div>
                                                   <div class="col-sm-4 text-right">
                                                       <div class="col-sm-4 text-left">지출</div>
                                                       <div class="col-sm-8 text-right">C$0.00</div> 
                                                   </div>
                                                </div> 
                                            </div>   
                                            <div class="row">
                                                <div class="col-sm-12">
                                                   <div class="col-sm-4 text-right">
                                                       <div class="col-sm-4 text-right">페이먼트</div>
                                                       <div class="col-sm-8 text-right">C$0.00</div> 
                                                   </div>
                                                   <div class="col-sm-4 text-right" >C$2000</div>
                                                   <div class="col-sm-4 text-right">
                                                       <div class="col-sm-4 text-left">입금</div>
                                                       <div class="col-sm-8 text-right">C$1000</div> 
                                                   </div>
                                                </div> 
                                            </div>  
                                            <div class="row">
                                                <div class="col-sm-12">
                                                    <div class="col-sm-4 text-right"><hr class='dotted' />C$0.00</div>
                                                    <div class="col-sm-4 text-right"><hr class='dotted' />C$1000</div>
                                                    <div class="col-sm-4 text-right"><hr class='dotted' />C$1000</div>   
                                                </div> 
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>   
                            <br/>
                            <div class="row no-nav">
                                <div class="col-sm-12 text-left">
                                    <button type="submit" class="btn btn-xs btn-default js-xxx">일괄정산처리</button>
                                </div>
                            </div>
                            <br />
                            <table class="table table-bordered table-condensed gridSixteen reserveTable formDetail">
                                <tbody>
                                    <tr>
                                        <td colspan="2" class="titletd text-center">거래일자</td>
                                        <td colspan="14">
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <input type="text" name="calStartDate" class="inpubase tourDate" value=""/>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>                    			
                                        <td colspan="2" class="active text-center formHeader">변환통화</td>
                                        <td colspan="6">
                                            <select class="form-control" name="currencyChange">
                                                <option selected>- 선택 -</option>
                                                <option value="">캐나다->캐나다</option>
                                                <option value="">2</option>
                                            </select>
                                        </td>
                                        <td colspan="2" class="active text-center formHeader">환율</td>
                                        <td colspan="6">
                                            <input type="text" name="rate" class="form-control" aria-label="환율" value=""/>    
                                        </td>
                                    </tr>
                                    <tr>                    			
                                        <td colspan="2" class="active text-center formHeader">선택입금예정금액</td>
                                        <td colspan="6">
                                            <div class="row">
                                                <div class="col-sm-12">
                                                    <div class="input-group input-group-sm">
                                                        <input type="text" name="depositAmount1" class="form-control" aria-label="선택입금예정금액" placeholder="선택입금예정금액">
                                                        <span class="input-group-addon no-border">-></span>
                                                        <input type="text" name="depositAmount2" class="form-control" aria-label="선택입금예정금액" placeholder="선택입금예정금액">
                                                    </div>
                                                </div>
                                            </div>    
                                        </td>
                                        <td colspan="2" class="active text-center formHeader">선택지출예정금액</td>
                                        <td colspan="6">
                                            <div class="row">
                                                <div class="col-sm-12">
                                                    <div class="input-group input-group-sm">
                                                        <input type="text" name="spendAmount1" class="form-control" aria-label="선택지출예정금액" placeholder="선택지출예정금액">
                                                        <span class="input-group-addon no-border">-></span>
                                                        <input type="text" name="spendAmount2" class="form-control" color="green" aria-label="선택지출예정금액" placeholder="선택지출예정금액">
                                                    </div>
                                                </div>
                                            </div>    
                                        </td>
                                    </tr>
                                    <tr>                    			
                                        <td colspan="2" class="active text-center formHeader">결제방법</td>
                                        <td colspan="6">
                                            <select class="form-control" name="paymentMethod">
                                                <option selected>- 선택 -</option>
                                                <option value="">은행송금</option>
                                                <option value="">2</option>
                                            </select>
                                        </td>
                                        <td colspan="2" class="active text-center formHeader">거래은행</td>
                                        <td colspan="6">
                                            <select class="form-control" name="bankSelect">
                                                <option selected>- 은행명(기초코드에서 추가) -</option>
                                                <option value="">1</option>
                                                <option value="">2</option>
                                            </select>    
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" class="active text-center formHeader">정산메모</td>
                                        <td colspan="14">
                                            <input type="text" name="calMemo" class="form-control" aria-label="정산메모" value=""/> 
                                        </td>
                                    </tr>
                                   <tr>
                                       <td colspan="16" class="text-center"><button type="submit" class="btn btn-xs btn-default js-xxx">결제하기</button></td>
                                    </tr>
                                </tbody>
                            </table> 
                       </div>
                    </div>          
				</div><!-- -->
			</div>                
		</div>
	</div>
   <!--modal -->
   <div class="modal fade js-openCooperationCal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
        <div class="modal-dialog modal-lg modal-full-width" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="gridSystemModalLabel">정산처리</h4>
                </div>
                <div class="modal-body">
                    <form action="" method="post">
                        <div class="row">
                            <div class="col-sm-12">
                                <table class="table table-bordered table-condensed gridSixteen reserveTable formDetail">
                                    <tbody>
                                        <tr>
                                            <td colspan="2" class="active text-center formHeader">업체명</td>
                                            <td colspan="6">홍길동 투어_캐나다</td>
                                            <td colspan="2" class="active text-center formHeader">행사명</td>
                                            <td colspan="6">퀘벡2박3일</td>
                                        </tr>
                                        <tr>       
                                            <td colspan="2" class="active text-center formHeader">예약코드/고객명</td>
                                            <td colspan="6">TD2018051800003 아무개</td>
                                            <td colspan="2" class="active text-center formHeader">거래예정금액</td>
                                            <td colspan="6">C$3000</td>
                                        </tr>
                                        <tr>       
                                            <td colspan="2" class="active text-center formHeader">거래타입</td>
                                            <td colspan="14">
                                                <label class="radio-inline">
                                                    <input type="radio" name="dealType" value=""> 크레딧(협력사로부터 수금)
                                                </label>
                                                <label class="radio-inline">
                                                    <input type="radio" name="dealType" value=""> 데빗(협력사에게 지급)
                                                </label>
                                            </td>
                                        </tr>
                                        <tr>       
                                            <td colspan="2" class="active text-center formHeader">거래일자</td>
                                            <td colspan="14">
                                                <input type="text" id="" name="popStartDate" class="inpubase tourDate" value="" placeholder="출발일"/>
                                            </td>
                                        </tr>
                                        <tr>       
                                            <td colspan="2" class="active text-center formHeader">변동통화/환율</td>
                                            <td colspan="6">
                                                <div class="row">
                                                    <div class="col-sm-6">
                                                        <select class="form-control" name="popCurrencyChange">
                                                            <option value="" selected>캐나다->캐나다</option>
                                                            <option value="">2</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <input type="text" name="popRate" class="form-control" aria-label="환율" value="" placeholder="환율" />
                                                    </div>
                                                </div>
                                            </td>
                                            <td colspan="2" class="active text-center formHeader">거래금액</td>
                                            <td colspan="6">
                                                <div class="row">
                                                    <div class="col-sm-12">
                                                        <div class="input-group input-group-sm">
                                                            <span class="input-group-addon">변환금액</span>
                                                            <input type="text" name="popChgAmount" class="form-control" aria-label="변환금액">
                                                            <span class="input-group-addon no-border">-></span>
                                                            <input type="text" class="form-control" aria-label="변환금액">
                                                        </div>
                                                    </div>
                                                </div>    
                                            </td>
                                        </tr>
                                        <tr>       
                                            <td colspan="2" class="active text-center formHeader">결제방법</td>
                                            <td colspan="6">
                                                <select class="form-control" name="popPayment">
                                                    <option selected>은행송금</option>
                                                    <option value="">현금</option>
                                                    <option value="">데빗</option>
                                                </select>
                                            </td>
                                            <td colspan="2" class="active text-center formHeader">거래은행</td>
                                            <td colspan="6">
                                                <select class="form-control" name="popBank">
                                                    <option selected>은행명(기초코드에서추가)</option>
                                                    <option value="">1</option>
                                                    <option value="">2</option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" class="active text-center formHeader">정산메모</td>
                                            <td colspan="14">
                                                <input type="text" name="popMemo" class="form-control" aria-label="정산메모" value=""/> 
                                            </td>
                                        </tr>
                                        <tr>
                                           <td colspan="16" class="text-center"><button type="submit" class="btn btn-xs btn-default js-xxx">결제하기</button></td>
                                        </tr>
                                    </tbody>
                                </table>
                                <br />
                                <div class="row">
                                    <div class="col-sm-12">
                                        <table class="table table-striped table-bordered table-hover table-condensed js-productTable">
                                            <thead>
                                                <tr>
                                                    <th>거래일자</th>
                                                    <th>거래타입</th>
                                                    <th>결제방법</th>
                                                    <th>거래금액</th>
                                                    <th>거래은행</th>
                                                    <th>정산메모</th>
                                                    <th>등록자</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td align="center">2018-09-12</td>
                                                    <td align="center">크래딧</td>
                                                    <td align="center">은행송금</td>
                                                    <td align="right">C$1000</td>
                                                    <td align="center">파란은행</td>
                                                    <td >은행결제완료</td>
                                                    <td align="center">김일동</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
   
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
                    
					singleDateTourDate1.datepicker($.extend({}, pt.defaults.datepicker, {
						
						datesDisabled: [
							'01/11/2019',
							'01/25/2019',
						],
						datesEnabled: [    // to override datesDisabled
							'01/20/2019',
							'01/25/2019',
						],
						datesOnly: [    // will override anything
							// '01/10/2019',
						],
						beforeShowDay: function (date) {
							var enabled = pt.beforeShowDayFunc(date, this)
							return enabled
						},
					}))
                }
            }
			pt.initReservationList()
            
            $('.js-productTable tbody').on( 'click', 'tr', function () {
                $(".js-openCooperationCal").modal("show");
            } );
            
		})
	</script>
    </body>
</html>
