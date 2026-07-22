<?php
// guide_assign.php
// 가이드 배정 화면 + 저장 업서트 로직(guide-first) + 동적 서브코드 생성 UI

include "include/header.php";
// include "include/inc_base.php"; // header.php 안에서 이미 $dbConn, 유틸/세션 로드된다고 가정

// ============ 공통 가드 ============
if (empty($_COOKIE['MEMLOGIN_ADMIN_HELLO'])) {
    echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
    exit;
}
if (!hasMenuAccess($division, $pdx, $sub)) {
    $goUrl_1 = "index.php";
    Misc::jvAlert("권한이 있는 메뉴가 아닙니다. 확인후 사용하세요.!!","");
    echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
    exit;
}

// 안전한 변수
$division = $_GET['division'] ?? '';
$pdx      = $_GET['pdx'] ?? '';
$sub      = $_GET['sub'] ?? '';
$st       = $_GET['st'] ?? '';          // 출발일 YYYY-MM-DD
$pcode    = $_GET['pcode'] ?? '';

$mode     = $_POST['mode'] ?? '';
$gcode    = $_POST['gcode'] ?? '';      // grand_eCode (빈 경우 자동 생성 규칙 적용)
$pname    = $_POST['pname'] ?? '';
$sdate    = $_POST['sdate'] ?? '';

// 저장용 배열
$bbnum         = $_POST['bbnum'] ?? [];         // bus_num[]
$ssnum         = $_POST['ssnum'] ?? [];         // sub_eCode[]
$guideName     = $_POST['guideName'] ?? [];     // guide_id[]
$sguideName    = $_POST['sguideName'] ?? [];    // sguide_id[]
$guideEmail    = $_POST['guideEmail'] ?? [];    // g_email[]
$guideTelephone= $_POST['guideTelephone'] ?? [];// g_tel[]
$guidePreCost  = $_POST['guidePreCost'] ?? [];  // pre_amt[]
$cName         = $_POST['cName'] ?? [];         // c_id[]
$carTelephone  = $_POST['carTelephone'] ?? [];  // c_tel[]
$cartype       = $_POST['cartype'] ?? [];       // c_type[]
$carmemo       = $_POST['carmemo'] ?? [];       // c_memo[]
$driver        = $_POST['driver'] ?? [];        // d_nm[]
$dTelephone    = $_POST['dTelephone'] ?? [];    // d_tel[]
$dmemo         = $_POST['dmemo'] ?? [];         // d_memo[]

$userid = $user_dbinfo['userid'] ?? 'system';

// ====== 보조 유틸: 날짜 숫자화 ======
function ymd_digits($s) { return preg_replace('/[^0-9]/','', $s ?: date('Y-m-d')); }

// ====== 보조 유틸: 충돌 여부 체크(여러 테이블) ======
function sub_exists_in_any(mysqli $db, $gcode, $sub){
    $tables = ['tour_guide','tour_car','tour_bus'];
    foreach($tables as $t){
        $sql = "SELECT 1 FROM {$t} WHERE grand_eCode=? AND sub_eCode=? LIMIT 1";
        $st = $db->prepare($sql);
        $st->bind_param('ss',$gcode,$sub);
        $st->execute();
        $has = $st->get_result()->num_rows>0;
        $st->close();
        if ($has) return true;
    }
    return false;
}

// ====== 그랜드코드 자동 생성 (비었을 때만) ======
function get_or_create_grand_code(mysqli $db, $gcode, $sdate){
    if ($gcode) return $gcode; // 이미 있으면 그대로 사용
    $sd = ymd_digits($sdate);
    $n  = 1;
    do {
        $try = "GE{$sd}-{$n}";
        // tour_master or 3개 테이블에 같은 grand_eCode가 있는지 최소 충돌 회피
        $dup = false;
        foreach (['tour_master','tour_guide','tour_car','tour_bus'] as $t) {
            $sql="SELECT 1 FROM {$t} WHERE grand_eCode=? LIMIT 1";
            if (!($st=$db->prepare($sql))) { $dup=false; break; }
            $st->bind_param('s',$try);
            $st->execute();
            $has = $st->get_result()->num_rows>0;
            $st->close();
            if($has){ $dup=true; break; }
        }
        if(!$dup) { $gcode = $try; break; }
        $n++;
    } while(true);
    // 필요하면 tour_master에 최소행을 생성하도록 확장 가능(여기서는 생성하지 않고 gcode만 사용)
    return $gcode;
}

// ====== 저장(업서트) ======
if ($mode === 'save') {
    // 그랜드코드 비었으면 자동 생성
    $gcode = get_or_create_grand_code($dbConn, $gcode, $sdate);

    $eventcnt = is_array($ssnum) ? count($ssnum) : 0;
    $dbConn->begin_transaction();
    try {
        for ($r=0; $r<$eventcnt; $r++) {
            $sub  = trim($ssnum[$r] ?? '');
            if ($sub==='') continue;

            $bus  = trim($bbnum[$r] ?? '');
            $gid  = trim($guideName[$r] ?? '');
            $sgid = trim($sguideName[$r] ?? '');
            $gEm  = trim($guideEmail[$r] ?? '');
            $gTel = trim($guideTelephone[$r] ?? '');
            $pre  = trim($guidePreCost[$r] ?? '');
            $cid  = trim($cName[$r] ?? '');
            $cTel = trim($carTelephone[$r] ?? '');
            $cTyp = trim($cartype[$r] ?? '');
            $cMem = trim($carmemo[$r] ?? '');
            $dNm  = trim($driver[$r] ?? '');
            $dTel = trim($dTelephone[$r] ?? '');
            $dMem = trim($dmemo[$r] ?? '');

            // tour_guide: 존재하면 UPDATE, 없으면 INSERT (절대 DELETE하지 않음)
            $sql = "SELECT 1 FROM tour_guide WHERE grand_eCode=? AND sub_eCode=? AND stDate=? LIMIT 1";
            $stp = $dbConn->prepare($sql);
            $stp->bind_param('sss',$gcode,$sub,$sdate);
            $stp->execute();
            $existsG = $stp->get_result()->num_rows>0;
            $stp->close();

            if ($existsG) {
                $sql = "UPDATE tour_guide
                        SET p_code=?, p_name=?, bus_num=?,
                            guide_id=?, sguide_id=?, g_email=?, g_tel=?, pre_amt=?,
                            c_id=?, c_tel=?, c_type=?, c_memo=?,
                            d_nm=?, d_tel=?, d_memo=?, userid=?, wdate=NOW()
                        WHERE grand_eCode=? AND sub_eCode=? AND stDate=?";
                $stp = $dbConn->prepare($sql);
                $stp->bind_param(
                    'sssssssdsssssssssss',
                    $pcode, $pname, $bus,
                    $gid, $sgid, $gEm, $gTel, $pre,
                    $cid, $cTel, $cTyp, $cMem,
                    $dNm, $dTel, $dMem, $userid,
                    $gcode, $sub, $sdate
                );
                $stp->execute();
                $stp->close();
            } else {
                $sql = "INSERT INTO tour_guide
                        (grand_eCode, sub_eCode, p_code, p_name, stDate, bus_num,
                         guide_id, sguide_id, g_email, g_tel, pre_amt,
                         c_id, c_tel, c_type, c_memo,
                         d_nm, d_tel, d_memo, userid, wdate)
                        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())";
                $stp = $dbConn->prepare($sql);
                $stp->bind_param(
                    'sssssssssdssssssss',
                    $gcode, $sub, $pcode, $pname, $sdate, $bus,
                    $gid, $sgid, $gEm, $gTel, $pre,
                    $cid, $cTel, $cTyp, $cMem,
                    $dNm, $dTel, $dMem, $userid
                );
                $stp->execute();
                $stp->close();
            }

            
        }
        $dbConn->commit();
        Misc::jvAlert("가이드 배정(수정) 저장 완료!","");
    } catch (Exception $e) {
        $dbConn->rollback();
        Misc::jvAlert("저장 오류: ".$e->getMessage(),"");
    }
}

// 조회용 데이터
$sctour = getTourInfo2($pcode, $st);
$pcnt   = getReserveInfoCnt($pcode, $st); if (empty($pcnt['cnt'])) $pcnt['cnt']=0;
$pInfo  = getProductMaster($pcode);

// 화면 출력 시작
?>
<div id="contentwrapper" class="reservationDetailForm">
  <div class="main_content">
    <div id="jCrumbs" class="breadCrumb module">
      <ul>
        <li><a href="/"><i class="glyphicon glyphicon-home"></i></a></li>
        <li><a href="#">행사배정</a></li>
        <li>가이드배정관리</li>
      </ul>
    </div>

    <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&st=<?=$st?>&pcode=<?=$pcode?>" name="frmcar" method="post" onSubmit="return chksave()">
      <input type="hidden" name="mode"  id="mode"  value="save">
      <input type="hidden" name="gcode" id="gcode" value="<?= htmlspecialchars($sctour['grand_eCode'] ?: $gcode) ?>">
      <input type="hidden" name="pcode" id="pcode" value="<?= htmlspecialchars($sctour['p_code'] ?: $pcode) ?>">
      <input type="hidden" name="pname" id="pname" value="<?= htmlspecialchars($sctour['p_name'] ?: $pname) ?>">
      <input type="hidden" name="sdate" id="sdate" value="<?= htmlspecialchars($sctour['stDate'] ?: $st) ?>">

      <table id="custom_table" class="table table-bordered table-condensed gridSixteen reserveTable formDetail">
        <tbody>
          <tr>
            <td colspan="2" class="active text-center formHeader">통합행사코드</td>
            <td colspan="12"><?= htmlspecialchars($sctour['grand_eCode'] ?: ($gcode ?: '미생성')) ?></td>
          </tr>
          <tr>
            <td colspan="2" class="active text-center formHeader">상품명</td>
            <td colspan="12">[<?=htmlspecialchars($sctour['p_code'] ?: $pcode)?>] <?=htmlspecialchars($sctour['p_name'] ?: $pname)?></td>
          </tr>
          <tr>
            <td colspan="2" class="active text-center formHeader">출발일</td>
            <td colspan="2"><?=htmlspecialchars($sctour['stDate'] ?: $st)?></td>
            <td colspan="2" class="active text-center formHeader">투어정원</td>
            <td colspan="2"><?= (int)($sctour['tour_pcnt'] ?? 0) ?> 명</td>
            <td colspan="2" class="active text-center formHeader">예약인원</td>
            <td colspan="2"><?= (int)$pcnt['cnt'] ?> 명</td>
          </tr>
          <tr>
            <td colspan="2" class="active text-center formHeader">예약상태</td>
            <td colspan="12">
              <label class="radio-inline"><input type="radio" disabled <?= strstr(($sctour['r_status']??''),'P')?'checked':'' ?>> 예약접수중</label>
              <label class="radio-inline"><input type="radio" disabled <?= strstr(($sctour['r_status']??''),'C')?'checked':'' ?>> 예약마감</label>
            </td>
          </tr>
          <tr>
            <td colspan="2" class="active text-center formHeader">행사상태</td>
            <td colspan="12">
              <div class="row">
                <div class="col-sm-4">
                  <div class="input-group input-group-sm">
                    <label class="radio-inline"><input type="radio" disabled <?= strstr(($sctour['ev_status']??''),'1')?'checked':'' ?>> 미확정</label>
                    <label class="radio-inline"><input type="radio" disabled <?= strstr(($sctour['ev_status']??''),'2')?'checked':'' ?>> 확정</label>
                    <label class="radio-inline"><input type="radio" disabled <?= strstr(($sctour['ev_status']??''),'3')?'checked':'' ?>> 만차</label>
                    <label class="radio-inline"><input type="radio" disabled <?= strstr(($sctour['ev_status']??''),'4')?'checked':'' ?>> 취소</label>
                    <label class="radio-inline"><input type="radio" disabled <?= strstr(($sctour['ev_status']??''),'5')?'checked':'' ?>> 기타</label>
                  </div>
                </div>
                <div class="col-sm-8">
                  <input type="text" name="etcMemo" class="form-control" placeholder="기타메모" value="<?=htmlspecialchars($sctour['etc_memo'] ?? '')?>" readonly>
                </div>
              </div>
            </td>
          </tr>
          <tr>
            <td colspan="16" class="text-center">
              <button type="button" class="btn-maroon btn-sm" id="btn-add-guide">가이드 추가</button>
              <button type="submit" class="btn-maroon btn-sm" id="car-assign">가이드배정 저장</button>
            </td>
          </tr>
        </tbody>
      </table>

      <div class="row">
        <div class="col-sm-12">
<?php
// 기존: tour_car 기준 버스별 그룹 → guide-first를 놓치므로 guide LEFT JOIN car로 전환 권장
$q = $dbConn->prepare("
  SELECT g.grand_eCode, g.sub_eCode, g.p_code, g.bus_num, g.guide_id, g.sguide_id, g.g_tel, g.g_email, g.pre_amt,
         g.c_id, g.c_tel, g.c_type, g.c_memo, g.d_nm, g.d_tel, g.d_memo
    FROM tour_guide g
    WHERE g.stDate=? AND g.p_code=?
    ORDER BY g.bus_num IS NULL, g.bus_num ASC, g.sub_eCode ASC
");
$q->bind_param('ss', $st, $pcode);
$q->execute();
$res = $q->get_result();
while($row1 = $res->fetch_assoc()):
    $gss = $row1; // 그대로 사용
?>
          <fieldset class="guide-assign-border">
            <legend class="guide-assign-border">
              <span class="pull-left small text-muted">행사코드: <?=htmlspecialchars($gss['sub_eCode'])?><?php if(!empty($gss['bus_num'])) echo " / 차량".$gss['bus_num']; ?></span>
            </legend>
            <input type="hidden" name="bbnum[]" value="<?=htmlspecialchars($gss['bus_num'] ?? '')?>">
            <input type="hidden" name="ssnum[]" value="<?=htmlspecialchars($gss['sub_eCode'])?>">
            <table class="table table-borderless table-condensed gridSixteen reserveTable formDetail">
              <tbody>
                <tr>
                  <td colspan="2" class="text-center">가이드</td>
                  <td colspan="2" class="text-center">차량</td>
                  <td colspan="2" class="text-center">기사</td>
                </tr>
                <tr>
                  <td width="3%" class="text-center">가이드 이름</td>
                  <td width="10%">
                    <select class="form-control guidecs" name="guideName[]">
                      <option value="" selected>선택</option>
                      <?= printGuideSelect($gss['guide_id'] ?? '') ?>
                    </select>
                  </td>
                  <td width="2%" class="text-center">차량</td>
                  <td>
                    <select class="form-control cName" name="cName[]">
                      <option value="" selected>선택</option>
                      <?= printCarSelect($gss['c_id'] ?? '') ?>
                    </select>
                  </td>
                  <td width="2%" class="text-center">기사명</td>
                  <td><input type="text" name="driver[]" class="form-control driver" value="<?=htmlspecialchars($gss['d_nm'] ?? '')?>"></td>
                </tr>
                <tr>
                  <td class="text-center">연락처</td>
                  <td><input type="text" name="guideTelephone[]" class="form-control tel" value="<?=htmlspecialchars($gss['g_tel'] ?? '')?>"></td>
                  <td class="text-center">연락처</td>
                  <td><input type="text" name="carTelephone[]" class="form-control ctel" value="<?=htmlspecialchars($gss['c_tel'] ?? '')?>"></td>
                  <td class="text-center">연락처</td>
                  <td><input type="text" name="dTelephone[]" class="form-control dtel" value="<?=htmlspecialchars($gss['d_tel'] ?? '')?>"></td>
                </tr>
                <tr>
                  <td class="text-center">선지급 행사비</td>
                  <td><input type="text" name="guidePreCost[]" class="form-control preamt" value="<?=htmlspecialchars($gss['pre_amt'] ?? '')?>"></td>
                  <td class="text-center">차량종류</td>
                  <td><input type="text" name="cartype[]" class="form-control ctype" value="<?=htmlspecialchars($gss['c_type'] ?? '')?>"></td>
                  <td class="text-center">메모</td>
                  <td><input type="text" name="dmemo[]" class="form-control dmeom" value="<?=htmlspecialchars($gss['d_memo'] ?? '')?>"></td>
                </tr>
                <tr>
                  <td class="text-center">부가이드</td>
                  <td>
                    <select class="form-control sguidecs" name="sguideName[]">
                      <option value="" selected>선택</option>
                      <?= printGuideSelect($gss['sguide_id'] ?? '') ?>
                    </select>
                  </td>
                  <td class="text-center">메모</td>
                  <td><input type="text" name="carmemo[]" class="form-control cmemo" value="<?=htmlspecialchars($gss['c_memo'] ?? '')?>"></td>
                  <td class="text-left"></td>
                  <td></td>
                </tr>
              </tbody>
            </table>
          </fieldset>
<?php endwhile; $q->close(); ?>

          <!-- 동적 추가 영역 -->
          <div id="guideListDynamic"></div>
        </div>
      </div>
    </form>
  </div>
</div>

<?php include "include/side_m.php"; ?>

<script>
$(document).ready(function(){
  pt.initReservationList?.();

  // 공통 핸들러 바인딩 함수
  function wireGuideCarHandlers(scope){
    scope.find('.guidecs').off('change').on('change', function(){
      var ruid = $(this).val(), sel = $(this);
      $.getJSON("get_guide.php?ruid="+encodeURIComponent(ruid), function(jsonData){
        sel.closest('table').find(".tel").val("");
        sel.closest('table').find(".email").val("");
        $.each(jsonData || [], function(i,data){
          sel.closest('table').find(".tel").val(data.company_phone || "");
          sel.closest('table').find(".email").val(data.company_email || "");
        });
      });
    });

    scope.find('.cName').off('change').on('change', function(){
      var ruid = $(this).val(), sel = $(this);
      $.getJSON("get_ccar.php?ruid="+encodeURIComponent(ruid), function(jsonData){
        sel.closest('table').find(".ctel").val("");
        $.each(jsonData || [], function(i,data){
          sel.closest('table').find(".ctel").val(data.bus_tel || "");
          $.getJSON("get_codec.php?code="+encodeURIComponent(data.bus_type || ''), function(jsonData1){
            $.each(jsonData1 || [], function(i,data1){
              sel.closest('table').find(".ctype").val(data1.comment || "");
            });
          });
        });
      });
    });
  }
  wireGuideCarHandlers($(document));

  // 가이드 추가: 그랜드코드 자동 생성 포함, 서브코드 생성 후 빈 블록 추가
  $('#btn-add-guide').on('click', function(){
    var gcode = $('#gcode').val();
    var sdate = $('#sdate').val();
    var pcode = $('#pcode').val();
    var pname = $('#pname').val();

    $.post('create_sub_event.php', {
      gcode: gcode,
      sdate: sdate,
      pcode: pcode,
      pname: pname
    }, function(res){
      if(!res || !res.ok){ alert(res && res.msg ? res.msg : '서브코드 생성 실패'); return; }

      // 서버가 그랜드코드도 자동 생성/반환하므로 갱신
      if(res.grand_eCode){
        $('#gcode').val(res.grand_eCode);
      }

      var sub = res.sub_eCode;
      var html = `
      <fieldset class="guide-assign-border">
        <legend class="guide-assign-border">
          <span class="pull-left small text-muted">신규 (행사코드: ${sub})</span>
        </legend>
        <input type="hidden" name="bbnum[]" value="">
        <input type="hidden" name="ssnum[]" value="${sub}">
        <table class="table table-borderless table-condensed gridSixteen reserveTable formDetail">
          <tbody>
            <tr>
              <td colspan="2" class="text-center">가이드</td>
              <td colspan="2" class="text-center">차량</td>
              <td colspan="2" class="text-center">기사</td>
            </tr>
            <tr>
              <td width="3%" class="text-center">가이드 이름</td>
              <td width="10%">
                <select class="form-control guidecs" name="guideName[]">
                  <option value="">선택</option>
                  <?= printGuideSelect('') ?>
                </select>
              </td>
              <td class="text-center">차량</td>
              <td>
                <select class="form-control cName" name="cName[]">
                  <option value="">선택</option>
                  <?= printCarSelect('') ?>
                </select>
              </td>
              <td class="text-center">기사명</td>
              <td><input type="text" name="driver[]" class="form-control driver"></td>
            </tr>
            <tr>
              <td class="text-center">연락처</td>
              <td><input type="text" name="guideTelephone[]" class="form-control tel"></td>
              <td class="text-center">연락처</td>
              <td><input type="text" name="carTelephone[]" class="form-control ctel"></td>
              <td class="text-center">연락처</td>
              <td><input type="text" name="dTelephone[]" class="form-control dtel"></td>
            </tr>
            <tr>
              <td class="text-center">선지급 행사비</td>
              <td><input type="text" name="guidePreCost[]" class="form-control preamt"></td>
              <td class="text-center">차량종류</td>
              <td><input type="text" name="cartype[]" class="form-control ctype"></td>
              <td class="text-center">메모</td>
              <td><input type="text" name="dmemo[]" class="form-control dmeom"></td>
            </tr>
            <tr>
              <td class="text-center">부가이드</td>
              <td>
                <select class="form-control sguidecs" name="sguideName[]">
                  <option value="">선택</option>
                  <?= printGuideSelect('') ?>
                </select>
              </td>
              <td class="text-center">메모</td>
              <td><input type="text" name="carmemo[]" class="form-control cmemo"></td>
              <td class="text-left"></td>
              <td></td>
            </tr>
          </tbody>
        </table>
      </fieldset>`;
      $('#guideListDynamic').prepend(html);
      wireGuideCarHandlers($('#guideListDynamic fieldset').first());
    }, 'json');
  });
});

function chksave(){
  return confirm("배정(수정) 내용을 저장할까요?");
}
</script>
</body>
</html>
