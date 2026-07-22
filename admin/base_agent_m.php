<?php
    include "include/header.php";
    // include "include/inc_base.php"; // 필요시 복원

    // ───────── 입력 헬퍼 ─────────
    function in_get($k,$d=''){return isset($_GET[$k])?$_GET[$k]:$d;}
    function in_post($k,$d=''){return isset($_POST[$k])?$_POST[$k]:$d;}
    function in_cookie($k,$d=''){return isset($_COOKIE[$k])?$_COOKIE[$k]:$d;}

    // ───────── 공용 파라미터 ─────────
    $division = in_get('division', in_post('division',''));
    $pdx      = in_get('pdx',      in_post('pdx',''));
    $sub      = in_get('sub',      in_post('sub',''));
    $mode     = in_get('mode',     in_post('mode',''));   // ← Mode 오타 방지
    $id       = in_get('id',       in_post('id',''));     // seq_no (수정 시)
    $seq_no   = in_post('seq_no', '');                    // 폼 hidden에서 옴

    // 로그인/권한
    if (in_cookie('MEMLOGIN_ADMIN_HELLO') === '') {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>"; exit;
    }
    if (!hasMenuAccess($division, $pdx, $sub)) {
        $goUrl_1 = "index.php";
        Misc::jvAlert("권한이 있는 메뉴가 아닙니다. 확인후 사용하세요.!!", "");
        echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>"; exit;
    }

    // ───────── 저장 처리 ─────────
    if ($mode === "save") {

        // 폼 입력 수집 (체크박스 미체크 대비)
        $ruserid          = in_post('ruserid','');
        $userid           = in_post('userid','');
        $passwd           = in_post('passwd','');
        $kor_name         = in_post('kor_name','');
        $eng_name         = in_post('eng_name','');
        $company_type     = in_post('company_type','');
        $company_division = in_post('company_division','');
        $company_homepage = in_post('company_homepage','');
        $zipcode          = in_post('zipcode','');
        $address          = in_post('address','');
        $city             = in_post('city','');
        $state            = in_post('state','');
        $country          = in_post('country','');
        $company_boss     = in_post('company_boss','');
        $company_manager  = in_post('company_manager','');
        $company_phone    = in_post('company_phone','');
        $company_fax      = in_post('company_fax','');
        $company_email    = in_post('company_email','');
        $company_area     = in_post('company_area','');
        $issue_airline    = in_post('issue_airline','');          // 체크박스(YES / '')
        $balance_alert    = in_post('balance_alert','');
        $tax_id           = in_post('tax_id','');
        $bank_info        = in_post('bank_info','');
        $ata_arc          = in_post('ata_arc','');
        $build_date       = in_post('build_date','');
        $employee_ch      = in_post('employee_ch','');
        $chkcc            = in_post('chkcc','');                  // 결제여부(주석 처리되어 있을 수 있음)
        $a_color          = in_post('a_color','');
        $pos              = in_post('pos','');
        $set_a            = in_post('set_a','');                  // 체크박스(C / '')
        $set_pro          = in_post('set_pro','');                // 체크박스(C / '')
        $agent_rate       = in_post('agent_rate','');
        $feetype          = in_post('feetype','');

        // 체크박스 미체크 시 값 보정 (form에서 value="C"/"YES")
        $set_a         = ($set_a === 'C') ? 'C' : '';
        $set_pro       = ($set_pro === 'C') ? 'C' : '';
        $issue_airline = ($issue_airline === 'YES') ? 'YES' : '';

        // 이스케이프
        foreach ([
            'ruserid','userid','passwd','kor_name','eng_name','company_type','company_division','company_homepage',
            'zipcode','address','city','state','country','company_boss','company_manager','company_phone','company_fax',
            'company_email','company_area','issue_airline','balance_alert','tax_id','bank_info','ata_arc','build_date',
            'employee_ch','chkcc','a_color','pos','set_a','set_pro','agent_rate','feetype'
        ] as $k) {
            $$k = $dbConn->real_escape_string($$k);
        }

        if ($seq_no === "") {
            // 신규 — 랜덤키 길이 off-by-one 수정 (8자)
            $keychars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
            $length = 8;
            $randkey = "";
            $max = strlen($keychars) - 1;
            for ($i = 0; $i < $length; $i++) {
                $randkey .= substr($keychars, rand(0, $max), 1);
            }

            $qry1 = "
                INSERT INTO member_list (
                    division, ruserid, userid, passwd, kor_name, eng_name,
                    company_type, company_division, company_homepage, zipcode, address, city, state, country,
                    company_boss, company_manager, company_phone, company_fax, company_email, company_area,
                    issue_airline, balance_alert, tax_id, bank_info, ata_arc, build_date, employee_ch,
                    cc_type, a_color, pos, set_acc, set_pro, agent_rate, fee_type
                ) VALUES (
                    'comp', '$ruserid', '$userid', '$randkey', '$kor_name', '$eng_name',
                    '$company_type', '$company_division', '$company_homepage', '$zipcode', '$address', '$city', '$state', '$country',
                    '$company_boss', '$company_manager', '$company_phone', '$company_fax', '$company_email', '$company_area',
                    '$issue_airline', '$balance_alert', '$tax_id', '$bank_info', '$ata_arc', '$build_date', '$employee_ch',
                    '$chkcc', '$a_color', '$pos', '$set_a', '$set_pro', '$agent_rate', '$feetype'
                )
            ";
            $rst1 = $dbConn->query($qry1);
        } else {
            // 수정 — 패스워드가 빈값이면 기존 유지하고 싶다면 아래 라인 분기 처리 가능
            $qry1 = "
                UPDATE member_list SET
                    ruserid='$ruserid',
                    passwd='$passwd',
                    kor_name='$kor_name',
                    eng_name='$eng_name',
                    company_type='$company_type',
                    company_division='$company_division',
                    company_homepage='$company_homepage',
                    zipcode='$zipcode',
                    address='$address',
                    city='$city',
                    state='$state',
                    country='$country',
                    company_boss='$company_boss',
                    company_manager='$company_manager',
                    company_phone='$company_phone',
                    company_fax='$company_fax',
                    company_email='$company_email',
                    company_area='$company_area',
                    issue_airline='$issue_airline',
                    balance_alert='$balance_alert',
                    tax_id='$tax_id',
                    bank_info='$bank_info',
                    ata_arc='$ata_arc',
                    build_date='$build_date',
                    employee_ch='$employee_ch',
                    cc_type='$chkcc',
                    a_color='$a_color',
                    pos='$pos',
                    set_acc='$set_a',
                    set_pro='$set_pro',
                    agent_rate='$agent_rate',
                    fee_type='$feetype'
                WHERE seq_no = '". $dbConn->real_escape_string($seq_no) ."'
            ";
            $rst1 = $dbConn->query($qry1);
        }

        // 에러 확인(운영시 로그로)
        if (!$rst1) {
            echo "DB Error: " . htmlspecialchars($dbConn->error);
            exit;
        }

        $goUrl_1 = "base_agent.php?division=$division&pdx=$pdx&sub=$sub";
        echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>"; exit;
    }

    // 상세 조회 (수정 모드)
    $v_info = getinfo_dbMember_byid($id); // 내부 함수가 배열 반환한다고 가정
    $v = is_array($v_info) ? $v_info : [];

?>
     
<div id="contentwrapper">
    <div class="main_content">
        <div id="jCrumbs" class="breadCrumb module">
            <ul>
                <li><a href="/"><i class="glyphicon glyphicon-home"></i></a></li>
                <li><a href="#">기초관리</a></li>
                <li><a href="#">업체관리</a></li>
                <li>업체등록</li>
            </ul>
        </div>

        <div class="row">
            <div class="col-sm-12 col-md-12">
                <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>?division=<?= htmlspecialchars($division) ?>&pdx=<?= htmlspecialchars($pdx) ?>&sub=<?= htmlspecialchars($sub) ?>" enctype="multipart/form-data" name="base_code" id="base_code" method="post">
                    <input type="hidden" name="mode" value="save">
                    <input type="hidden" name="seq_no" value="<?= htmlspecialchars($id) ?>">

                    <table class="table table-striped table-bordered table-condensed">
                        <tbody>
                            <tr bgcolor="#f9f9f9" height="28">
                                <td width="15%" class="titletd">이용 ID</td>
                                <td width="35%" bgcolor="#FFFFFF">&nbsp;
                                    <input type="text" name="ruserid" class="inpubase sm1" value="<?= htmlspecialchars($v['ruserid'] ?? '') ?>">
                                </td>
                                <td width="15%" class="titletd" style="vertical-align: middle;">패스워드</td>
                                <td width="35%" bgcolor="#FFFFFF">&nbsp;
                                    <input type="password" name="passwd" class="inpubase md" placeholder="자동생성" value="<?= htmlspecialchars($v['passwd'] ?? '') ?>">
                                </td>
                            </tr>
                            <tr bgcolor="#f9f9f9" height="28">
                                <td class="titletd" style="vertical-align: middle;">회계 ID</td>
                                <td bgcolor="#FFFFFF">&nbsp;
                                    <input type="text" name="userid" class="inpubase sm1" value="<?= htmlspecialchars($v['userid'] ?? '') ?>">
                                </td>
                                <td class="titletd" style="vertical-align: middle;">지역선택</td>
                                <td bgcolor="#FFFFFF">&nbsp;
                                    <select name="company_area" class="inpubase md">
                                        <?= printBaseCode4_without('A01', $v['company_area'] ?? '') /* 함수가 에코한다고 가정 */ ?>
                                    </select>
                                </td>
                            </tr>
                            <tr bgcolor="#f9f9f9" height="28">
                                <td class="titletd" style="vertical-align: middle;">회사명(한글)</td>
                                <td bgcolor="#FFFFFF">&nbsp;
                                    <input type="text" name="kor_name" class="inpubase lg" value="<?= htmlspecialchars($v['kor_name'] ?? '') ?>">
                                </td>
                                <td class="titletd" style="vertical-align: middle;">회사명(영문)</td>
                                <td bgcolor="#FFFFFF">&nbsp;
                                    <input type="text" name="eng_name" class="inpubase lg" value="<?= htmlspecialchars($v['eng_name'] ?? '') ?>">
                                </td>
                            </tr>
                            <tr bgcolor="#f9f9f9" height="28">
                                <td class="titletd" style="vertical-align: middle;">홈페이지</td>
                                <td bgcolor="#FFFFFF">&nbsp;
                                    <input type="text" name="company_homepage" class="inpubase lg" value="<?= htmlspecialchars($v['company_homepage'] ?? '') ?>">
                                </td>
                                <td class="titletd" style="vertical-align: middle;">이메일</td>
                                <td bgcolor="#FFFFFF">&nbsp;
                                    <input type="text" name="company_email" class="inpubase md" value="<?= htmlspecialchars($v['company_email'] ?? '') ?>">
                                </td>
                            </tr>
                            <tr bgcolor="#f9f9f9" height="28">
                                <td class="titletd" style="vertical-align: middle;">전화번호</td>
                                <td bgcolor="#FFFFFF">&nbsp;
                                    <input type="text" name="company_phone" class="inpubase md" value="<?= htmlspecialchars($v['company_phone'] ?? '') ?>">
                                </td>
                                <td class="titletd" style="vertical-align: middle;">팩스</td>
                                <td bgcolor="#FFFFFF">&nbsp;
                                    <input type="text" name="company_fax" class="inpubase md" value="<?= htmlspecialchars($v['company_fax'] ?? '') ?>">
                                </td>
                            </tr>
                            <tr bgcolor="#f9f9f9" height="28">
                                <td class="titletd" style="vertical-align: middle;">대표자명</td>
                                <td bgcolor="#FFFFFF">&nbsp;
                                    <input type="text" name="company_boss" class="inpubase sm1" value="<?= htmlspecialchars($v['company_boss'] ?? '') ?>">
                                </td>
                                <td class="titletd" style="vertical-align: middle;">담당자명</td>
                                <td bgcolor="#FFFFFF">&nbsp;
                                    <input type="text" name="company_manager" class="inpubase sm1" value="<?= htmlspecialchars($v['company_manager'] ?? '') ?>">
                                </td>
                            </tr>
                            <tr bgcolor="#f9f9f9" height="28">
                                <td class="titletd" style="vertical-align: middle;">주소</td>
                                <td bgcolor="#FFFFFF">&nbsp;
                                    <input type="text" name="address" class="inpubase lg" value="<?= htmlspecialchars($v['address'] ?? '') ?>">
                                </td>
                                <td class="titletd" style="vertical-align: middle;">도시</td>
                                <td bgcolor="#FFFFFF">&nbsp;
                                    <input type="text" name="city" class="inpubase sm1" value="<?= htmlspecialchars($v['city'] ?? '') ?>">
                                </td>
                            </tr>
                            <tr bgcolor="#f9f9f9" height="28">
                                <td class="titletd" style="vertical-align: middle;">주</td>
                                <td bgcolor="#FFFFFF">&nbsp;
                                    <input type="text" name="state" class="inpubase sm1" value="<?= htmlspecialchars($v['state'] ?? '') ?>">
                                </td>
                                <td class="titletd" style="vertical-align: middle;">우편번호</td>
                                <td bgcolor="#FFFFFF">&nbsp;
                                    <input type="text" name="zipcode" size="7" class="inpubase sm1" value="<?= htmlspecialchars($v['zipcode'] ?? '') ?>">
                                    &nbsp;
                                    <label><input type="radio" name="country" value="CAN" <?= (($v['country'] ?? '') === 'CAN') ? 'checked' : '' ?>> CAN</label>&nbsp;
                                    <label><input type="radio" name="country" value="USA" <?= (($v['country'] ?? '') === 'USA') ? 'checked' : '' ?>> USA</label>&nbsp;
                                    <label><input type="radio" name="country" value="KOR" <?= (($v['country'] ?? '') === 'KOR') ? 'checked' : '' ?>> KOR</label>
                                </td>
                            </tr>

                            <tr bgcolor="#f9f9f9" height="28">
                                <td class="titletd" style="vertical-align: middle;">Tax ID</td>
                                <td bgcolor="#FFFFFF">&nbsp;
                                    <input type="text" name="tax_id" class="inpubase md" value="<?= htmlspecialchars($v['tax_id'] ?? '') ?>">
                                </td>
                                <td class="titletd" style="vertical-align: middle;">은행정보</td>
                                <td bgcolor="#FFFFFF">&nbsp;
                                    <input type="text" name="bank_info" size="40" class="inpubase md" value="<?= htmlspecialchars($v['bank_info'] ?? '') ?>">
                                </td>
                            </tr>

                            <!-- 거래처결제여부/담당직원정보 섹션이 필요하면 주석 해제 후 사용 -->
                            <!--
                            <tr bgcolor="#f9f9f9" height="28">
                                <td class="titletd" style="vertical-align: middle;">담당직원정보</td>
                                <td bgcolor="#FFFFFF" colspan="3">&nbsp;
                                    <input type="text" name="employee_ch" size="60" class="inpubase md" value="<?= htmlspecialchars($v['employee_ch'] ?? '') ?>">
                                    &nbsp;&nbsp;&nbsp;
                                    <input type="checkbox" name="chkcc" id="chkcc" <?= (($v['cc_type'] ?? '') === 'C') ? 'checked' : '' ?> value="C"> 거래처결제여부
                                </td>
                            </tr>
                            -->

                            <tr bgcolor="#f9f9f9" height="28">
                                <td class="titletd" style="vertical-align: middle;">지역컬러배경선택</td>
                                <td bgcolor="#FFFFFF" colspan="3">&nbsp;HEX# :
                                    <input type="text" name="a_color" size="10" class="inpubase sm1" value="<?= htmlspecialchars($v['a_color'] ?? '') ?>">
                                    (정산현황 배경색 -
                                    <a href="https://htmlcolorcodes.com/" target="_blank"><u>[칼라 차트 보기]</u></a>)
                                </td>
                            </tr>
                            <tr bgcolor="#f9f9f9" height="28">
                                <td class="titletd" style="vertical-align: middle;">셋틀 위치선정</td>
                                <td bgcolor="#FFFFFF">&nbsp;위치 :
                                    <input type="text" name="pos" size="10" class="inpubase sm1" value="<?= htmlspecialchars($v['pos'] ?? '') ?>">
                                </td>
                                <td class="titletd" style="vertical-align: middle;">회계 노출여부</td>
                                <td bgcolor="#FFFFFF">&nbsp;
                                    <input type="checkbox" class="bs-switch" data-size="mini" name="set_a" id="set_a" value="C" <?= (($v['set_acc'] ?? '') === 'C') ? 'checked' : '' ?>>
                                </td>
                            </tr>
                            <tr bgcolor="#f9f9f9" height="28">
                                <td class="titletd" style="vertical-align: middle;">상품소유사지정여부</td>
                                <td bgcolor="#FFFFFF">&nbsp;
                                    <input type="checkbox" class="bs-switch" data-size="mini" name="set_pro" id="set_pro" value="C" <?= (($v['set_pro'] ?? '') === 'C') ? 'checked' : '' ?>>
                                </td>
                                <td class="titletd" style="vertical-align: middle;">발권처여부</td>
                                <td bgcolor="#FFFFFF">&nbsp;
                                    <input type="checkbox" class="bs-switch" data-size="mini" name="issue_airline" id="issue_airline" value="YES" <?= (($v['issue_airline'] ?? '') === 'YES') ? 'checked' : '' ?>>
                                </td>
                            </tr>

                            <tr>
                                <td colspan="4" height="35" bgcolor="#FFFFFF" class="titletd" style="vertical-align: middle;">
                                    <input type="submit" value="저장" class="btn btn-primary btn-sm">
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </form>
            </div><!-- -->
        </div>
    </div>
</div>

<?php include "include/side_m.php"; ?>

<script>
$(document).ready(function(){
    if ($('.bs-switch').length && typeof $.fn.bootstrapSwitch === 'function') {
        $('.bs-switch').bootstrapSwitch();
    }
});
</script>

</body>
</html>
