<?php
    include "include/header.php";
    
    if ($_COOKIE['MEMLOGIN_ADMIN_HELLO'] != "") {
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

    // [함수] 특정 행사의 가이드 명단 (단일 서브행사용으로 최적화 가능하지만 기존 구조 유지)
    function getGuideListBySubEvent($grand_eCode, $stDate, $sub_eCode) {
        global $dbConn;
        $sql = "SELECT sub_eCode, guide_id, 
                (SELECT kor_name FROM member_list WHERE userid = tour_guide.guide_id AND division = 'guide') as k_name
                FROM tour_guide 
                WHERE grand_eCode = '$grand_eCode' 
                  AND stDate = '$stDate' 
                  AND sub_eCode = '$sub_eCode'
                ORDER BY sub_eCode ASC";
        
        $result = $dbConn->query($sql);
        $display_str = "";
        while($row = mysqli_fetch_assoc($result)) {
            $guideName = $row['k_name'] ? $row['k_name'] : "<span style='color:#ccc;'>미지정</span>";
            $display_str .= "<b>$guideName</b>";
        }
        return $display_str;
    }

    function printSingle(){
        global $dbConn,$division,$crev,$pdx,$sub,$startDate1,$endDate1,$guideid;

        if ($startDate1) $from_w = " AND a.stDate >= '$startDate1' ";
        if ($endDate1) $to_w = " AND a.stDate <= '$endDate1' ";
        if($guideid) $guide_w = " AND a.guide_id = '$guideid' ";

        // [수정] 메인 코드 + 서브 코드 기준으로 그룹화하여 각각의 서브행사가 리스트에 나오도록 함
        $query = "SELECT a.seq_no as seq, a.* FROM (
         SELECT a.seq_no, a.grand_eCode, a.sub_eCode, a.stDate, a.guide_id, a.p_code, a.p_name
        FROM tour_guide a, tour_master b
        WHERE a.grand_eCode = b.grand_eCode AND a.p_code = b.p_code
        $from_w $to_w $guide_w ) a LEFT JOIN product_master b ON a.p_code = b.p_code
        WHERE 1=1 
        GROUP BY a.grand_eCode, a.sub_eCode 
        ORDER BY a.stDate DESC, a.sub_eCode ASC";

        $rst1 = $dbConn->query($query);
        while($row1 = mysqli_Fetch_assoc($rst1)){

            $p_cnt = getReserveInfoCnt($row1['p_code'],$row1['stDate']);
            $period = getPeriodbyhotel($row1['p_code'],$row1['stDate']);
            
            // [수정] 상태 체크 함수에 서브코드(sub_eCode) 파라미터 전달
            $status1 = getHotelStStatus($row1['grand_eCode'], $row1['sub_eCode'], $row1['stDate']);
            $status2 = getCarStStatus($row1['grand_eCode'], $row1['sub_eCode'], $row1['stDate']);
            
            $guide_name = getGuideListBySubEvent($row1['grand_eCode'], $row1['stDate'], $row1['sub_eCode']);

            // 일괄 체크 대상: NONE·AUTO = 자동정산(재계산), AUTO·WAIT = 일괄 정산확인. 확정(DONE)·예전 수동입력분 제외
            $auto_state = hotelSettleState($row1['grand_eCode'], $row1['sub_eCode']);
            $chk_st = ($auto_state == 'DONE') ? ($status1 == '정산대기' ? 'WAIT' : '') : $auto_state;
            $auto_chk = ($chk_st != '' && hotelRateReady())
                ? "<input type='checkbox' class='js-auto' data-st='$chk_st' value='".htmlspecialchars($row1['grand_eCode'].'|'.$row1['sub_eCode'], ENT_QUOTES)."'>"
                : "";

            $hotel_action = $status1 == '정산대기' ? '정산확인' : '호텔';

            // [수정] 링크 설정 시 sub_eCode 추가 전달
            $link_hotel = "hotel_cal2.php?division=6&pdx=1&sub=10&number=".$row1['seq']."&sub_eCode=".$row1['sub_eCode'];
            $link_car   = "car_cal2.php?division=6&pdx=1&sub=15&number=".$row1['seq']."&sub_eCode=".$row1['sub_eCode'];

            echo "<tr>
                <td align='center'>$auto_chk</td>
                <td align='center'>$row1[grand_eCode]</td>
                <td align='center' style='color:blue; font-weight:bold;'>$row1[sub_eCode]</td>
                <td align='center'>$row1[stDate]</td>
                <td>$row1[p_name]</td>
                <td align='center'>$period</td>
                <td align='center'>$p_cnt[cnt]</td>
                <td align='center'>$guide_name</td>
                <td align='center'>$status1</td>
                <td align='center'>$status2</td>
                <td align='center' style='vertical-align:middle;'>
                    <div class='btn-group btn-group-xs'>
                        <button type='button' class='btn btn-primary' onclick=\"location.href='$link_hotel'\" title='호텔 정산 검토 및 확인'>$hotel_action</button>
                        <button type='button' class='btn btn-success' onclick=\"location.href='$link_car'\" title='차량 정산'>차량</button>
                    </div>
                </td>
            </tr>";
        }
    } 
?>

    <div id="contentwrapper" class="reservationDetailForm">
        <div class="main_content">
            <div id="jCrumbs" class="breadCrumb module">
                <ul>
                    <li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li>
                    <li><a href="#">정산관리</a></li>
                    <li>호텔별정산(서브코드별)</li>
                </ul>
            </div>

            <div class="row">
                <div class="col-sm-12 col-md-12">
                    <form action="" name="frmName" method="post">
                        <input type="hidden" name="mode" value="search">
                        <table class="table table-bordered table-condensed">
                            <tr>
                                <td width="10%" class="titletd text-center">행사일기준</td>
                                <td width="40%">
                                    <div class="row">
                                        <div class="col-sm-3">
                                            <input type="date" class="form-control input-sm" name="startDate1" value="<?=$startDate1?>" />
                                        </div>
                                        <div class="col-sm-3">
                                            <input type="date" class="form-control input-sm" name="endDate1" value="<?=$endDate1?>" />
                                        </div>
                                        <div class="col-sm-3">
                                            <select class="form-control input-sm" name="guideid">
                                                <option value=''>- 가이드 전체 -</option>
                                                <?php 
                                                  $query ="SELECT userid,kor_name FROM member_list WHERE division ='guide' ";
                                                  $rst1 = mysqli_query($dbConn, $query);
                                                  while($row1 = mysqli_Fetch_assoc($rst1)){
                                                ?>
                                                <option value="<?=$row1['userid']?>" <?php if($guideid == $row1['userid']) echo 'selected'; ?>><?=$row1['kor_name']?></option>
                                                <?php }?>
                                            </select>
                                        </div>    
                                        <div class="col-sm-3">
                                            <button type='submit' class="btn btn-primary btn-sm btn1">검색</button>    
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </form>
                    <br />

                    <?php if (hotelRateReady()) { ?>
                    <!-- 일괄 자동정산: 객실 = 호텔배정 날짜별 방갯수 × 호텔등록 기간별 요금, 기타비용은 아래에서 지정한 것만 -->
                    <div class="panel panel-default" id="autoPanel">
                        <div class="panel-heading">
                            <b>일괄 자동정산</b>
                            <small class="text-muted">&nbsp;아래 목록에서 행사를 체크하세요 · 자동정산 = 미등록·자동정산분 (수동 저장분 제외) · 정산확인 = 정산대기분을 저장된 금액으로 확정 · 객실 = 호텔배정 방갯수 × 호텔등록 기간별 요금</small>
                        </div>
                        <div class="panel-body">
                            <div id="autoEtc">
                                <div class="row auto-etc-row" style="margin-bottom:4px;">
                                    <div class="col-sm-1 text-right" style="padding-top:5px;">
                                        <b class="auto-etc-label">기타비용</b>
                                        <button type="button" class="btn btn-default btn-xs js-autoEtcAdd"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button>
                                    </div>
                                    <div class="col-sm-3">
                                        <select class="form-control input-sm" name="etc_hotel[]">
                                            <option value="">- 전체 호텔 -</option>
                                            <?php $hl = getHotelList(); while ($h = mysqli_fetch_assoc($hl)) { ?>
                                            <option value="<?=htmlspecialchars($h['h_code'])?>"><?=htmlspecialchars($h['h_name'])?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-sm-2">
                                        <select class="form-control input-sm" name="etc_code[]">
                                            <option value="">- 기타비용을 선택하세요 -</option>
                                            <?php $el = getEtcCostSelect(); while ($e = mysqli_fetch_assoc($el)) { ?>
                                            <option value="<?=$e['lvcode2'].$e['lvcode3']?>"><?=htmlspecialchars($e['comment'])?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-sm-2">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">RATE</span>
                                            <input type="text" name="etc_rate[]" class="form-control" placeholder="비우면 요금표">
                                        </div>
                                    </div>
                                    <div class="col-sm-2">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">수량</span>
                                            <input type="text" name="etc_cnt[]" class="form-control" placeholder="비우면 방갯수">
                                        </div>
                                    </div>
                                    <div class="col-sm-1">
                                        <button type="button" class="btn btn-default btn-xs js-autoEtcDel"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
                                    </div>
                                </div>
                            </div>

                            <div id="autoSummary" class="hidden" style="margin-top:10px;">
                                <div class="clearfix" style="margin-bottom:4px;">
                                    <b id="autoHead"></b>
                                    <span class="pull-right">합계 <b id="autoTotal" style="font-size:15px;"></b></span>
                                </div>
                                <table class="table table-bordered table-condensed" id="autoEvTable" style="margin-bottom:6px;">
                                    <thead>
                                        <tr class="active">
                                            <th>메인 / 서브코드<h6>Event / Sub Code</h6></th>
                                            <th class="text-right">객실(실·박)<h6>Room Nights</h6></th>
                                            <th class="text-right">객실금액<h6>Room</h6></th>
                                            <th class="text-right">기타금액<h6>Extra</h6></th>
                                            <th class="text-right">합계<h6>Total</h6></th>
                                            <th>상태<h6>Status</h6></th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                                <table class="table table-bordered table-condensed" id="autoHotelTable" style="margin-bottom:6px;">
                                    <thead>
                                        <tr class="active">
                                            <th>호텔별 합계<h6>By Hotel</h6></th>
                                            <th class="text-right">객실(실·박)<h6>Room Nights</h6></th>
                                            <th class="text-right">객실금액<h6>Room</h6></th>
                                            <th class="text-right">기타금액<h6>Extra</h6></th>
                                            <th class="text-right">합계<h6>Total</h6></th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                                <div id="autoSkipped" class="small text-warning"></div>
                            </div>

                            <div style="margin-top:8px;">
                                <button type="button" class="btn btn-primary btn-sm js-autoSave" disabled>선택 행사 자동정산</button>
                                <button type="button" class="btn btn-success btn-sm js-autoConfirm" disabled>선택 정산확인</button>
                                <span id="autoMsg" class="small text-muted"></span>
                            </div>
                        </div>
                    </div>
                    <?php } ?>

                    <div class="row">
                        <div class="col-sm-12">
                            <table name="ctable" id="ctable" class="table table-striped table-bordered table-hover table-condensed js-productTable">
                                <thead>
                                    <tr class="info">
                                        <th width="30" class="text-center"><?php if (hotelRateReady()) { ?><input type="checkbox" class="js-autoAll" title="이 페이지 체크 가능한 행사 전체"><?php } ?></th>
                                        <th>메인코드</th>
                                        <th>서브코드</th>
                                        <th>행사일</th>
                                        <th>행사명</th>
                                        <th>기간</th>
                                        <th>인원</th>
                                        <th>가이드</th>
                                        <th>호텔상태</th>
                                        <th>차량상태</th>
                                        <th width="90">관리</th> 
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php echo printSingle(); ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>                
        </div>
    </div>
    
    <?php include "include/side_m.php" ?>
    
    <script>
        var oTable;
        $(document).ready(function () {
            oTable = $('#ctable').dataTable({
                stateSave: true,
                pageLength: 100,
                "columnDefs": [{ "orderable": false, "targets": 0 }],
                "order": [[ 3, "desc" ], [ 2, "asc" ]] // 행사일 내림차순 후 서브코드 오름차순 (0열 = 자동정산 체크)
            });
			$(".dataTables_length").css({ "display" :"none" });
        })

        /* ── 일괄 자동정산: 실시간 요약 + 저장 (hotel_autosettle.php) ── */
        if ($('#autoPanel').length) {
            var autoTimer = null, autoReq = 0, autoOkCnt = 0;

            function autoNf(n) {
                return Number(n || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            }
            function autoEvents() {   // 자동정산 대상 (NONE·AUTO)
                var ev = [];
                oTable.$('input.js-auto:checked').not('[data-st=WAIT]').each(function () { ev.push(this.value); });  // 다른 페이지 체크 포함
                return ev;
            }
            function autoWaitEvents() {   // 정산확인 대상 (정산대기 AUTO·WAIT)
                var ev = [];
                oTable.$('input.js-auto:checked').filter('[data-st=AUTO],[data-st=WAIT]').each(function () { ev.push(this.value); });
                return ev;
            }
            function autoData(act) {
                var data = $('#autoEtc :input').serializeArray();
                $.each(autoEvents(), function (i, v) { data.push({ name: 'events[]', value: v }); });
                data.push({ name: 'act', value: act });
                return data;
            }
            function autoSchedule() {
                var wn = autoWaitEvents().length;
                $('.js-autoConfirm').prop('disabled', wn === 0).text(wn ? '선택 정산확인 (대기 ' + wn + '건)' : '선택 정산확인');
                clearTimeout(autoTimer);
                autoTimer = setTimeout(autoPreview, 300);
            }
            function autoTd(text, cls) {
                return $('<td>').addClass(cls || '').text(text);
            }
            function autoRender(res) {
                var evBody = $('#autoEvTable tbody').empty();
                $.each(res.events, function (i, ev) {
                    var tr = $('<tr class="js-autoEvRow" style="cursor:pointer;">')
                        .append(autoTd(ev.grand_eCode + ' / ' + ev.sub_eCode + ' ▸'))
                        .append(autoTd(ev.rooms || '-', 'text-right'))
                        .append(autoTd(ev.ok ? autoNf(ev.room_amt) : '-', 'text-right'))
                        .append(autoTd(ev.ok ? autoNf(ev.etc_amt) : '-', 'text-right'))
                        .append(autoTd(ev.ok ? autoNf(ev.total) : '-', 'text-right'))
                        .append(autoTd(ev.ok ? (ev.recalc ? '가능(재계산)' : '가능') : ev.reason, ev.ok ? 'text-success' : 'text-danger'));
                    evBody.append(tr);
                    // 일차별 상세 (행 클릭 시 펼침)
                    var lines = $('<div class="small">');
                    $.each(ev.days, function (j, d) {
                        lines.append($('<div>').text(
                            d.day + '일차 ' + d.stay_date + ' · ' + d.h_name + (d.season ? ' · ' + d.season : '') + ' · ' +
                            (d.rate === '' ? '요금미등록' : autoNf(d.rate) + ' × ' + d.room_cnt + '실 = ' + autoNf(d.amt))));
                    });
                    $.each(ev.etc, function (j, e) {
                        lines.append($('<div class="text-info">').text(
                            '기타 · ' + e.h_name + ' · ' + e.etc_name + ' · ' + autoNf(e.rate) + ' × ' + e.cnt + ' = ' + autoNf(e.amt)));
                    });
                    evBody.append($('<tr class="auto-detail hidden">').append($('<td colspan="6">').append(lines)));
                });

                var hBody = $('#autoHotelTable tbody').empty();
                $.each(res.hotels, function (i, h) {
                    hBody.append($('<tr>')
                        .append(autoTd(h.h_name))
                        .append(autoTd(h.rooms, 'text-right'))
                        .append(autoTd(autoNf(h.room_amt), 'text-right'))
                        .append(autoTd(autoNf(h.etc_amt), 'text-right'))
                        .append(autoTd(autoNf(h.total), 'text-right')));
                });
                $('#autoHotelTable').toggleClass('hidden', res.hotels.length === 0);

                $('#autoSkipped').text(res.etc_skipped.length ? '기타비용 단가없음 제외: ' + res.etc_skipped.join(', ') : '');
                $('#autoHead').text('선택 ' + res.events.length + '건 · 저장가능 ' + res.ok_cnt + ' · 건너뜀 ' + res.skip_cnt);
                $('#autoTotal').text(autoNf(res.total));
                autoOkCnt = res.ok_cnt;
                $('.js-autoSave').prop('disabled', autoOkCnt === 0).text('선택 행사 자동정산 (가능 ' + autoOkCnt + '건 저장)');
            }
            function autoPreview() {
                var ev = autoEvents();
                if (ev.length === 0) {
                    $('#autoSummary').addClass('hidden');
                    $('.js-autoSave').prop('disabled', true).text('선택 행사 자동정산');
                    $('#autoMsg').text('');
                    return;
                }
                var my = ++autoReq;
                $('#autoMsg').text('계산 중...');
                $.ajax({ type: 'POST', url: 'hotel_autosettle.php', data: autoData('preview'), dataType: 'json' })
                    .done(function (res) {
                        if (my !== autoReq) return;   // 늦게 온 이전 응답 무시
                        if (res.error) { $('#autoMsg').text(res.error); $('.js-autoSave').prop('disabled', true); return; }
                        $('#autoMsg').text('');
                        $('#autoSummary').removeClass('hidden');
                        autoRender(res);
                    })
                    .fail(function () { if (my === autoReq) $('#autoMsg').text('요약 계산 실패 — 다시 시도하세요.'); });
            }

            $('#ctable').off('change.hauto').on('change.hauto', 'input.js-auto', autoSchedule);
            $('#ctable').off('change.hautoAll').on('change.hautoAll', 'input.js-autoAll', function () {
                $('#ctable tbody input.js-auto').prop('checked', this.checked);   // 현재 페이지만
                autoSchedule();
            });
            $('#autoEtc').off('change.hauto input.hauto').on('change.hauto input.hauto', ':input', autoSchedule);

            $(document).off('click.hautoAdd').on('click.hautoAdd', '.js-autoEtcAdd', function () {
                var row = $('#autoEtc .auto-etc-row:first').clone();
                row.find('select, input').val('');
                row.find('.auto-etc-label, .js-autoEtcAdd').remove();
                $('#autoEtc').append(row);
            });
            $(document).off('click.hautoDel').on('click.hautoDel', '.js-autoEtcDel', function () {
                var row = $(this).closest('.auto-etc-row');
                if ($('#autoEtc .auto-etc-row').length > 1 && !row.is(':first-child')) row.remove();
                else row.find('select, input').val('');
                autoSchedule();
            });
            $(document).off('click.hautoEv').on('click.hautoEv', '.js-autoEvRow', function () {
                $(this).next('.auto-detail').toggleClass('hidden');
            });

            $(document).off('click.hautoSave').on('click.hautoSave', '.js-autoSave', function () {
                if (autoOkCnt === 0) return;
                if (!confirm('가능 ' + autoOkCnt + '건을 자동정산으로 저장합니다.\n(자동정산 행사는 다시 계산하고, 담당자가 저장한 정산·요금 미등록 행사는 건너뜁니다)')) return;
                var btn = $(this).prop('disabled', true);
                $.ajax({ type: 'POST', url: 'hotel_autosettle.php', data: autoData('save'), dataType: 'json' })
                    .done(function (res) {
                        if (res.error) { alert(res.error); btn.prop('disabled', false); return; }
                        var msg = '자동정산 ' + res.saved + '건 저장 / 건너뜀 ' + res.skip_cnt + '건';
                        $.each(res.events, function (i, ev) { if (!ev.ok) msg += '\n- ' + ev.grand_eCode + ' / ' + ev.sub_eCode + ': ' + ev.reason; });
                        if (res.etc_skipped.length) msg += '\n\n기타비용 단가없음 제외:\n- ' + res.etc_skipped.join('\n- ');
                        alert(msg);
                        document.frmName.submit();   // 검색조건 유지한 채 목록 새로고침
                    })
                    .fail(function () { alert('저장 중 오류가 발생했습니다.'); btn.prop('disabled', false); });
            });

            $(document).off('click.hautoConfirm').on('click.hautoConfirm', '.js-autoConfirm', function () {
                var ev = autoWaitEvents();
                if (ev.length === 0) return;
                if (!confirm('정산대기 ' + ev.length + '건을 현재 저장된 금액으로 정산확인합니다.\n(위 요약은 아직 저장 전 금액이므로, 자동정산을 먼저 저장했는지 확인하세요)')) return;
                var btn = $(this).prop('disabled', true);
                var data = $.map(ev, function (v) { return { name: 'events[]', value: v }; });
                data.push({ name: 'act', value: 'confirm' });
                $.ajax({ type: 'POST', url: 'hotel_autosettle.php', data: data, dataType: 'json' })
                    .done(function (res) {
                        if (res.error) { alert(res.error); btn.prop('disabled', false); return; }
                        var msg = '정산확인 ' + res.confirmed + '건';
                        if (res.skipped.length) msg += '\n\n정산대기가 아니어서 건너뜀:\n- ' + res.skipped.join('\n- ');
                        alert(msg);
                        document.frmName.submit();
                    })
                    .fail(function () { alert('정산확인 중 오류가 발생했습니다.'); btn.prop('disabled', false); });
            });
        }
    </script>
    </body>
</html>