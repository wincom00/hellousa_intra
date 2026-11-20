<?php
    if (session_status() === PHP_SESSION_NONE) session_start();
    include "include/header.php";
    if (empty($_SESSION['hotel_settle_csrf'])) $_SESSION['hotel_settle_csrf'] = bin2hex(random_bytes(32));
    if (empty($user_dbinfo['userid']) || !hasMenuAccess(6, 1, 10)) {
        exit('호텔정산 권한이 없습니다.');
    }
    
    if ($_COOKIE['MEMLOGIN_ADMIN_HELLO'] !="") {
    } else {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
        exit;
    }

    $seqno = (int)$_GET['number'];

    //투어 기본 정보
    $qry1 = "SELECT a.*, (SELECT kor_name FROM member_list where userid = a.guide_id AND division ='guide') AS kr_name,
    (SELECT base_rate FROM product_master b where a.p_code = b.p_code) AS base_rate
    FROM tour_guide a WHERE a.seq_no = $seqno ";
    $rst1 = $dbConn->query($qry1);
    $data_row = mysqli_fetch_assoc($rst1);

    //행사기간
    $period = getPeriodbyhotel($data_row['p_code'],$data_row['stDate']);
    //행사인원
    $p_cnt = getReserveInfoCntguide($seqno);

    // [수정] 모든 정산 정보 조회 시 grand_eCode와 sub_eCode를 동시에 체크하도록 변경
    
    // HOTEL SETTLE 정보
    $qry2 = "SELECT * FROM hotel_settle WHERE grand_eCode = '$data_row[grand_eCode]' AND sub_eCode = '$data_row[sub_eCode]' "; 
    $rst2 = $dbConn->query($qry2);

    // HOTEL SETTLE ETC 정보
    $qry3 = "SELECT * FROM hotel_settleetc WHERE grand_eCode = '$data_row[grand_eCode]' AND sub_eCode = '$data_row[sub_eCode]' ";
    $rst3 = $dbConn->query($qry3);

    // HOTEL SETTLE SUM 정보
    $qry4 = "SELECT * FROM hotel_settlesum WHERE grand_eCode = '$data_row[grand_eCode]' AND sub_eCode = '$data_row[sub_eCode]' ORDER BY seq_no DESC "; // 중복 저장분이 있으면 최신 1건 (손익 리포트와 동일)
    $rst4 = $dbConn->query($qry4);
    $hotel_sum_data = mysqli_fetch_assoc($rst4);
    $hc_status = getHotelStStatus($data_row['grand_eCode'], $data_row['sub_eCode'], $data_row['stDate']);
    $hc_pending = $hotel_sum_data && in_array($hotel_sum_data['status'], array('AUTO', 'WAIT'), true);

    // 호텔등록 기간별 요금 기반 자동계산 — 정산 미등록 시 호텔배정 날짜별 행으로 자동채움 + 기타비용 단가표(JS)
    $hc_auto = buildHotelAutoSettle($data_row['grand_eCode'], $data_row['sub_eCode']);
    $hc_etcRates = array();
    if (hotelRateReady()) {
        $hc_stay = array();   // 호텔별 첫 숙박일 (배정에 없는 호텔은 행사 시작일)
        foreach ($hc_auto['days'] as $hc_d) {
            if (!isset($hc_stay[$hc_d['h_code']])) $hc_stay[$hc_d['h_code']] = $hc_d['stay_date'];
        }
        $hc_codes = array();
        $hc_rst = getEtcCostSelect();
        while ($hc_c = mysqli_fetch_assoc($hc_rst)) $hc_codes[] = $hc_c['lvcode2'].$hc_c['lvcode3'];
        $hc_rst = getHotelList();
        while ($hc_h = mysqli_fetch_assoc($hc_rst)) {
            $hc_sd = isset($hc_stay[$hc_h['h_code']]) ? $hc_stay[$hc_h['h_code']] : $data_row['stDate'];
            foreach ($hc_codes as $hc_code) {
                $hc_r = getHotelRate($hc_h['h_code'], $hc_code, $hc_sd);
                if ($hc_r) $hc_etcRates[$hc_h['h_code'].'|'.$hc_code] = (float)$hc_r['rate'];
            }
        }
    }

?>
    <div id="contentwrapper" class="reservationDetailForm">
        <div class="main_content">
            <div id="jCrumbs" class="breadCrumb module">
                <ul>
                    <li><a href="/"><i class="glyphicon glyphicon-home"></i></a></li>
                    <li><a href="#">정산관리</a></li>
                    <li>호텔별정산 (<?=$data_row['sub_eCode']?>)</li>
                </ul>
            </div>

            <form name="frnName" id="frnName" method="post" action="">
                <input type="hidden" name="mode" id="mode" value="save">
                <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['hotel_settle_csrf'], ENT_QUOTES)?>">
                <input type="hidden" name="grand_eCode" id="grand_eCode" value="<?=$data_row['grand_eCode']?>">
                <input type="hidden" name="sub_eCode" id="sub_eCode" value="<?=$data_row['sub_eCode']?>"> 
                <input type="hidden" name="m_rate_h" id="m_rate_h" value="<?=$data_row['base_rate']?>">
                <input type="hidden" name="hotel_sumseq" value="<?=$hotel_sum_data['seq_no']?>">

                <div class="row no-nav">
                    <div class="col-sm-12 text-center">
                        <span class="label label-<?=$hc_pending ? 'warning' : ($hc_status == '정산확인' ? 'success' : 'default')?>"><?=$hc_status?></span>
                        <button type="button" class="btn btn-xs btn-default js-save">저장</button>
                        <?php if ($hc_pending) { ?>
                        <button type="button" class="btn btn-xs btn-primary js-confirm">정산확인</button>
                        <?php } ?>
                        <p class="help-block">저장은 정산대기로 유지됩니다. 금액을 검토한 뒤 정산확인을 눌러 확정하세요. 확인 후 수정·삭제하면 다시 확인이 필요합니다.</p>
                    </div>
                </div>
                <br />
                <table id="custom_table" class="table table-bordered table-condensed gridSixteen reserveTable formDetail">
                    <tbody>
                        <tr>
                            <td colspan="2" class="active text-center formHeader">기준통화</td>
                            <td colspan="6" class="m_rate"><?=$data_row['base_rate']?></td>
                            <td colspan="2" class="active text-center formHeader">서브코드</td>
                            <td colspan="6"><b style="color:blue;"><?=$data_row['sub_eCode']?></b></td>
                        </tr>
                        <tr>
                            <td colspan="2" class="active text-center formHeader">행사코드</td>
                            <td colspan="6"><?=$data_row['grand_eCode']?></td> 
                            
                            <td colspan="2" class="active text-center formHeader">행사명</td>
                            <td colspan="6"><?=$data_row['p_name']?></td>
                        </tr>
                        <tr>                        
                            <td colspan="2" class="active text-center formHeader">행사기간</td>
                            <td colspan="6"><?=$period?></td>
                            <td colspan="2" class="active text-center formHeader">행사인원</td>
                            <td colspan="6"><?=$p_cnt['cnt']?></td>
                        </tr>
                        <tr>                        
                            <td colspan="2" class="active text-center formHeader">가이드</td>
                            <td colspan="14"><?=$data_row['kr_name']?></td>
                        </tr>

                        <?php $ii=0; $rst2_cnt = mysqli_num_rows($rst2);
                        while($row1 = mysqli_Fetch_assoc($rst2)){ 
                            if($ii ==0) $rowspan = "rowspan='$rst2_cnt'";  
                        ?>
                            
                        <tr class="basic-class" param ="tr-parent"> 
                            <?php if($ii ==0) { ?><td colspan="2" <?=$rowspan?> class="active text-center formHeader">호텔선택&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td> <?php }?>
                            
                            <input type="hidden" name="hotel_seq[]" class="hotel_seq" value="<?=$row1['seq_no']?>">
                            
                            <td colspan="14">
                                <div class="row hotel_div">
                                    <div class="col-sm-2">
                                        <select class="form-control hotellist" name="hotelSelect[]">
                                            <option value="">- 호텔을 선택하세요 -</option>
                                            <?php $hotelList = getHotelList();
                                            while($row11 = mysqli_Fetch_assoc($hotelList)){
                                            ?>
                                            <option value="<?=$row11['h_code']?>" <?php if($row1['h_code'] == $row11['h_code']) echo 'selected'; ?> ><?=$row11['h_name']?></option>
                                            <?php }?>
                                        </select>
                                    </div>    
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">RATE/RM</span>
                                            <input type="text" name="hotelRateRm[]" class="form-control rate" aria-label="RATE/RM" value="<?=$row1['rate_rm']?>"/>
                                        </div>
                                    </div>
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">RM수</span>
                                            <input type="text" name="hotelRm[]" class="form-control rm" aria-label="RM수" value="<?=$row1['room_cnt']?>"/>
                                        </div>
                                    </div>
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">호텔비</span>
                                            <input type="text" name="hotelCost[]" class="form-control hotelCost" aria-label="호텔비" value="<?=$row1['hotel_amt']?>"/>
                                        </div>
                                    </div>
                                    <?php if($row1['seq_no']>0) {?>
                                    <div class="col-sm-1 show button-minus">
                                        <button type="button" class="btn btn-default btn-xs js-deleteHotelButton" data-id="<?=$row1['seq_no']?>" ><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
                                    </div>

                                    <?php }else{ ?>

                                    <div class="col-sm-1 hide button-minus">
                                        <button type="button" class="btn btn-default btn-xs js-removeHotelButton"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
                                    </div>
                                    <?php }?>
                                
                                </div>
                            </td>

                        </tr>
                        <?php $ii++;}   
                            
                        ?>

                        <?php if($rst2_cnt <=0) { //호텔정보 리스트 데이터가 없는 경우 → 호텔배정 날짜별 행 + 호텔등록 기간별 요금 로 자동채움 ?>
                        <?php
                                  $jj=0;
                                  $rst00_cnt = count($hc_auto['days']);
                                  foreach($hc_auto['days'] as $row00){
                                  $rowspan_0 = "rowspan='$rst00_cnt'";
                            ?>
                        <tr class="basic-class" param ="tr-parent"> 
                            <?php if($jj ==0) { ?>
                            <td colspan="2" <?=$rowspan_0?> class="active text-center formHeader" >호텔선택&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>
                            <?php } ?>
                            
                            <input type="hidden" name="hotel_seq[]" class="hotel_seq" value="">
                            
                            <td colspan="14">
                                <div class="row hotel_div">
                                    <div class="col-sm-2">
                                        <select class="form-control hotellist" name="hotelSelect[]">
                                            <option value="">- 호텔을 선택하세요 -</option>
                                            <?php $hotelList = getHotelList();
                                            while($row11 = mysqli_Fetch_assoc($hotelList)){
                                            ?>
                                            <option value="<?=$row11['h_code']?>" <?php if($row00['h_code'] == $row11['h_code']) echo 'selected'; ?> ><?=$row11['h_name']?></option>
                                            <?php }?>
                                        </select>
                                        <div class="small text-muted js-stayInfo"><?=$row00['day']?>일차 <?=$row00['stay_date']?><?=$row00['season'] != '' ? ' · '.htmlspecialchars($row00['season']) : ''?><?php if ($row00['rate'] === '' && hotelRateReady()) { ?> <span class="text-danger">요금미등록</span><?php } ?></div>
                                    </div>
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">RATE/RM</span>
                                            <input type="text" name="hotelRateRm[]" class="form-control rate" aria-label="RATE/RM" value="<?=$row00['rate']?>"/>
                                        </div>
                                    </div>
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">RM수</span>
                                            <input type="text" name="hotelRm[]" class="form-control rm" aria-label="RM수" value="<?=$row00['room_cnt']?>"/>
                                        </div>
                                    </div>
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">호텔비</span>
                                            <input type="text" name="hotelCost[]" class="form-control hotelCost" aria-label="호텔비" value="<?=$row00['amt']?>"/>
                                        </div>
                                    </div>
                                    
                                    <div class="col-sm-1 hide button-minus">
                                        <button type="button" class="btn btn-default btn-xs js-removeHotelButton"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
                                    </div>

                                </div>
                            </td>
                            
                        </tr>
                            <?php $jj++; } ?>
                        <?php }?>


                        <?php $jj=0; $rst3_cnt = mysqli_num_rows($rst3);
                        while($row1 = mysqli_Fetch_assoc($rst3)){ 
                            if($jj ==0) $rowspan = "rowspan='$rst3_cnt'";  
                        ?>

                        <tr class="cost-class" param ="tr-parent">
                            <?php if($jj ==0) { ?><td colspan="2" <?=$rowspan?> class="active text-center formHeader">기타비용&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td> <?php }?>

                            <input type="hidden" name="etc_seq[]" class="etc_seq" value="<?=$row1['seq_no']?>">

                            <td colspan="14">
                                <div class="row">
                                    <div class="col-sm-2">
                                        <select class="form-control hotellist" name="hotelSelect_etc[]">
                                            <option value="">- 호텔을 선택하세요 -</option>
                                            <?php $hotelList = getHotelList();
                                            while($row11 = mysqli_Fetch_assoc($hotelList)){
                                            ?>
                                            <option value="<?=$row11['h_code']?>" <?php if($row1['h_code'] == $row11['h_code']) echo 'selected'; ?>><?=$row11['h_name']?></option>
                                            <?php }?>
                                        </select>
                                    </div>    

                                    <div class="col-sm-2">
                                        <select class="form-control" name="etcCostSelect[]">
                                            <option value="">- 기타비용을 선택하세요 -</option>
                                            <?php $etcexpense = getEtcCostSelect();
                                            while($row11 = mysqli_Fetch_assoc($etcexpense)){
                                                $code = $row11['lvcode2'].$row11['lvcode3'];
                                            ?>
                                            <option value="<?=$code?>" <?php if($row1['etc_code'] == $code) echo 'selected'; ?>><?=$row11['comment']?></option>
                                            <?php }?>
                                        </select>
                                    </div>    
                                    <div class="col-sm-2">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">RATE</span>
                                            <input type="text" name="etcRate[]" class="form-control" aria-label="RATE" value="<?=$row1['rate']?>"/>
                                        </div>
                                    </div>
                                    <div class="col-sm-2">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">수량</span>
                                            <input type="text" name="etcCount[]" class="form-control" aria-label="수량" value="<?=$row1['cnt']?>"/>
                                        </div>
                                    </div>
                                    <div class="col-sm-2">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">금액</span>
                                            <input type="text" name="etcAmount[]" class="form-control etcAmount" aria-label="금액" value="<?=$row1['etc_amt']?>"/>
                                        </div>
                                    </div>

                                    <?php if($row1['seq_no']>0) {?>
                                    <div class="col-sm-1 show button-minus">
                                        <button type="button" class="btn btn-default btn-xs js-delete1HotelButton" data-id="<?=$row1['seq_no']?>"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
                                    </div>

                                    <?php }else{ ?>
                                    <div class="col-sm-1 hide button-minus">
                                        <button type="button" class="btn btn-default btn-xs js-removeHotelButton"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
                                    </div>
                                    <?php }?>

                                </div>
                            </td>
                        </tr>
                        <?php $jj++;}?>

                        <?php if($rst3_cnt <=0) { //기본비용 데이터가 없는경우 ?>
                        <tr class="cost-class" param ="tr-parent">
                            <td colspan="2" class="active text-center formHeader">기타비용&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td> 
                            <input type="hidden" name="etc_seq[]" class="etc_seq" value="">

                            <td colspan="14">
                                <div class="row">
                                    <div class="col-sm-2">
                                        <select class="form-control hotellist" name="hotelSelect_etc[]">
                                            <option value="">- 호텔을 선택하세요 -</option>
                                            <?php $hotelList = getHotelList();
                                            while($row11 = mysqli_Fetch_assoc($hotelList)){
                                            ?>
                                            <option value="<?=$row11['h_code']?>"><?=$row11['h_name']?></option>
                                            <?php }?>
                                        </select>
                                    </div>    


                                    <div class="col-sm-2">
                                        <select class="form-control" name="etcCostSelect[]">
                                            <option value="">- 기타비용을 선택하세요 -</option>
                                            <?php $etcexpense = getEtcCostSelect();
                                            while($row11 = mysqli_Fetch_assoc($etcexpense)){
                                                $code = $row11['lvcode2'].$row11['lvcode3'];
                                            ?>
                                            <option value="<?=$code?>"><?=$row11['comment']?></option>
                                            <?php }?>
                                        </select>
                                    </div>    
                                    <div class="col-sm-2">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">RATE</span>
                                            <input type="text" name="etcRate[]" class="form-control" aria-label="RATE" value=""/>
                                        </div>
                                    </div>
                                    <div class="col-sm-2">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">수량</span>
                                            <input type="text" name="etcCount[]" class="form-control" aria-label="수량" value=""/>
                                        </div>
                                    </div>
                                    <div class="col-sm-2">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">금액</span>
                                            <input type="text" name="etcAmount[]" class="form-control etcAmount" aria-label="금액" value=""/>
                                        </div>
                                    </div>

                                    <div class="col-sm-1 hide button-minus">
                                        <button type="button" class="btn btn-default btn-xs js-removeHotelButton"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
                                    </div>

                                </div>
                            </td>
                        </tr>

                        <?php }?>

                        <tr>
                            <td colspan="2" class="active text-center formHeader">호텔총액</td>
                            <td colspan="6">호텔별 총액-기준통화기준</td>
                            <td colspan="2" class="active text-center formHeader">실제지불총액</td>
                            <td colspan="6"><input type="text" name="totalPayment" id="totalPayment" class="form-control" aria-label="실제지불총액" readonly value="<?=$hotel_sum_data['real_amt']?>"/></td>
                            
                        </tr>
                        <tr>
                            <td colspan="16">
                                <textarea class="form-control" rows="7" name="memo" placeholder="메모"><?=htmlspecialchars($hotel_sum_data['memo'] ?? '', ENT_QUOTES)?></textarea>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
            </form>
        </div>
    </div>
    <?php
        include "include/side_m.php"
    ?>
    <script>
        
        var number = <?=$seqno?>;
        // 호텔등록 기간별 요금 기타비용 단가 { "호텔코드|E01코드": RATE } — 이 행사 숙박일 기준
        var hotelEtcRates = <?=json_encode((object)$hc_etcRates)?>;

        $(document).ready(function () {
            pt.initReservationList() ;
            if ($('#totalPayment').val() === '') calcuAmount();   // 자동채움된 호텔비 합계 표시 (저장 전)

            
            $('.js-addPlusRow').on( 'click', function () {
                var clickedRow = $(this).parent().parent();
                var cls = clickedRow.attr("class");
                var newrow = clickedRow.clone();
                newrow.removeAttr('param');
                newrow.find("td:eq(0)").remove();
                newrow.insertAfter($("#custom_table ."+cls+":last"));
                //$('.hotel-minus').not( ':first' ).removeClass('hide');

                newrow.find('button:last').addClass('js-removeHotelButton');
                newrow.find('button:last').removeClass('js-deleteHotelButton');
                newrow.find('button:last').removeClass('js-delete1HotelButton');
                
                newrow.find('.hotel_seq').val('');
                newrow.find('.etc_seq').val('');
                newrow.find('.js-stayInfo').remove();
                newrow.find(".hotellist").chosen({width: '100%' });
                newrow.find($('.chosen-container-single:last')).css('display','none');
                newrow.find($('.hotel_div .chosen-container-single:last')).css('display','none');
                
                var attr = newrow.attr('param');
                if (typeof attr == typeof undefined) newrow.find('.button-minus').removeClass('hide');
                resizeRowspan(cls);

                calcuAmount(); //실제지불금액

            });
            
            $(document).on("click", ".js-removeHotelButton", function(){
                var clickedRow = $(this).closest('tr').remove();
                var cls = clickedRow.attr("class");
                resizeRowspan(cls);
            });

        })
        
        function resizeRowspan(cls){
            var rowspan = $("."+cls).length;
            $("."+cls+":first td:eq(0)").attr("rowspan", rowspan); 
            calcuAmount(); //실제지불금액
        }

        $(".hotellist").chosen({width: '100%' });

        // 저장은 대기, 정산확인은 현재 입력 내용을 저장하고 확정
        $(document).off('click.hotelSave').on('click.hotelSave', '.js-save, .js-confirm', function(e) {
            e.preventDefault();
            var confirming = $(this).hasClass('js-confirm');
            if (confirming && !confirm('현재 호텔정산 금액을 확인하고 확정하시겠습니까?')) return;
            $('#mode').val(confirming ? 'confirm' : 'save');
            calcuAmount();
            $('.js-save, .js-confirm').prop('disabled', true);
            
            var form = $("#frnName").closest("form");
            var formData = new FormData(form[0]);

            $.ajax({
                type: 'POST',
                url: 'hotel_save.php',
                data: formData,
                cache:false,
                processData: false,
                contentType: false,
                success: function (response) {
                    var msg = $.trim(response).split("/");

                    if(msg[0] !== '1') {
                        alert(msg[1] || '저장에 실패했습니다. 다시 시도하세요.');

                        return false;
                    }else{
                        location.href = "hotel_cal2.php?division=6&pdx=1&sub=10&number="+number;
                    }
                },
                error: function () { alert('저장에 실패했습니다. 다시 시도하세요.'); },
                complete: function () { $('.js-save, .js-confirm').prop('disabled', false); }
            });
        });
        
        //호텔비 포커스아웃시
        $(document).on('focusout',".rate",function () {
            var row =$(this).closest("tr");
            var rate = parseFloat(row.find('.rate').val() || 0);
            var rm = parseFloat(row.find('.rm').val() || 0);
            
            row.find('.hotelCost').val(rate*rm);
            calcuAmount();
            
        })
        $(document).on('focusout',".rm",function () {
            var row =$(this).closest("tr");
            var rate = parseFloat(row.find('.rate').val() || 0);
            var rm = parseFloat(row.find('.rm').val() || 0);
            
            row.find('.hotelCost').val(rate*rm);
            calcuAmount();
            
        })
        //호텔비 포커스아웃시
        $(document).on('focusout',".hotelCost",function () {
            calcuAmount();
            
        });

        //기타비용 금액 포커스아웃시
        $(document).on('focusout',".etcAmount",function () {
            calcuAmount();
        });

        //기타비용: 호텔·항목 선택 시 호텔등록 기간별 요금 단가 자동 (RATE가 비어 있을 때만)
        $(document).off('change.hrate').on('change.hrate', "select[name='hotelSelect_etc[]'], select[name='etcCostSelect[]']", function () {
            var row = $(this).closest('.row');
            var key = row.find("select[name='hotelSelect_etc[]']").val() + '|' + row.find("select[name='etcCostSelect[]']").val();
            var rateInput = row.find("input[name='etcRate[]']");
            if (rateInput.val() === '' && hotelEtcRates[key] !== undefined) {
                rateInput.val(hotelEtcRates[key]);
                etcRowAmount(row);
            }
        });
        //기타비용: RATE × 수량 → 금액 자동
        $(document).off('input.hrate').on('input.hrate', "input[name='etcRate[]'], input[name='etcCount[]']", function () {
            etcRowAmount($(this).closest('.row'));
        });
        function etcRowAmount(row) {
            var rate = row.find("input[name='etcRate[]']").val();
            var cnt = row.find("input[name='etcCount[]']").val();
            if (rate === '' || cnt === '' || isNaN(rate) || isNaN(cnt)) return;
            row.find('.etcAmount').val((parseFloat(rate) * parseFloat(cnt)).toFixed(2));
            calcuAmount();
        }

        //실제지불총액
        function calcuAmount(){

            var hotelcost = 0;
            var etcamount = 0;
            
            $("input[name='hotelCost[]']").each(function(){
                hotelcost = Number(hotelcost) + Number(this.value);
            });

            $("input[name='etcAmount[]']").each(function(){
                etcamount = Number(etcamount) + Number(this.value);
            });
            var totalamount = (hotelcost+etcamount).toFixed(2);

            $("#totalPayment").val(totalamount); //실제지불총액

        }

        //호텔관련 데이터 삭제
        $(document).on("click", ".js-deleteHotelButton", function(){
            if(confirm("삭제하시겠습니까?") == true) {
                var seqno = $(this).attr("data-id");
                var grandcode = $("#grand_eCode").val();
                var subcode = $("#sub_eCode").val();

                $.ajax({
                    type: 'POST',
                    url: 'hotel_save.php',
                    data:{seqno:seqno,grandcode:grandcode,subcode:subcode,mode:'delete_hotel', hotel_sumseq:$('[name=hotel_sumseq]').val(), csrf_token:$('[name=csrf_token]').val()},
                    success: function (response) {
                        var msg = $.trim(response).split('/');
                        if (msg[0] !== '1') { alert(msg[1] || '삭제에 실패했습니다.'); return; }
                        location.href = "hotel_cal2.php?division=6&pdx=1&sub=10&number="+number;
                    },
                    error: function () { alert('삭제에 실패했습니다. 다시 시도하세요.'); }
                }); 
            }

        });

        //기타비용 삭제
        $(document).on("click", ".js-delete1HotelButton", function(){
            if(confirm("삭제하시겠습니까?") == true) {
                var seqno = $(this).attr("data-id");
                var grandcode = $("#grand_eCode").val();
                var subcode = $("#sub_eCode").val();

                $.ajax({
                    type: 'POST',
                    url: 'hotel_save.php',
                    data:{seqno:seqno,grandcode:grandcode,subcode:subcode,mode:'delete_etc', hotel_sumseq:$('[name=hotel_sumseq]').val(), csrf_token:$('[name=csrf_token]').val()},
                    success: function (response) {
                        var msg = $.trim(response).split('/');
                        if (msg[0] !== '1') { alert(msg[1] || '삭제에 실패했습니다.'); return; }
                        location.href = "hotel_cal2.php?division=6&pdx=1&sub=10&number="+number;
                    },
                    error: function () { alert('삭제에 실패했습니다. 다시 시도하세요.'); }
                }); 
            }

        });


    </script>
    </body>
</html>