<?php
    include "include/header.php";

    // ===== 입력 헬퍼 =====
    function in_get($k,$d=''){return isset($_GET[$k])?$_GET[$k]:$d;}
    function in_post($k,$d=''){return isset($_POST[$k])?$_POST[$k]:$d;}
    function in_cookie($k,$d=''){return isset($_COOKIE[$k])?$_COOKIE[$k]:$d;}

    // ===== 공용 파라미터 =====
    $division = in_get('division', in_post('division',''));
    $pdx      = in_get('pdx',      in_post('pdx',''));
    $sub      = in_get('sub',      in_post('sub',''));
    $mode     = in_get('mode', in_post('mode',''));         // ← Mode 오타 방지
    $id       = in_get('id','');                            // 삭제/복구 대상
    $com_nm   = in_post('com_nm', in_get('com_nm',''));     // 검색어

    // ===== 로그인/권한 체크 =====
    if (in_cookie('MEMLOGIN_ADMIN_HELLO') === '') {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>"; exit;
    }
    if (!hasMenuAccess($division, $pdx, $sub)) {
        $goUrl_1 = "index.php";
        Misc::jvAlert("권한이 있는 메뉴가 아닙니다. 확인후 사용하세요.!!","");
        echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>"; exit;
    }

    // ===== 삭제/복구 처리 =====
    if ($mode === "del" && $id !== '') {
        $sid = $dbConn->real_escape_string($id);
        $qry1 = "UPDATE member_list SET del_yn='Y' WHERE division='comp' AND seq_no='$sid'";
        $dbConn->query($qry1);
    }
    if ($mode === "rec" && $id !== '') {
        $sid = $dbConn->real_escape_string($id);
        $qry1 = "UPDATE member_list SET del_yn='N' WHERE division='comp' AND seq_no='$sid'";
        $dbConn->query($qry1);
    }

    // ===== 목록 렌더링 =====
    function printVendor(){
        global $dbConn,$division,$pdx,$sub,$com_nm;

        $where = "WHERE division='comp'";
        if ($com_nm !== "") {
            $kw = $dbConn->real_escape_string($com_nm);
            $where .= " AND kor_name LIKE '%$kw%'";
        }

        $qry1 = "SELECT * FROM member_list $where ORDER BY company_area ASC, userid ASC";
        if ($rst1 = $dbConn->query($qry1)) {
            while($row1 = $rst1->fetch_assoc()){
                // 지역명 조회
                $company_area_arr = codebaseName($row1['company_area']);
                $company_area_txt = '';
                if (is_array($company_area_arr)) {
                    $company_area_txt = isset($company_area_arr['comment']) ? $company_area_arr['comment'] : '';
                } else {
                    // 함수가 문자열을 리턴하는 경우 대비
                    $company_area_txt = (string)$company_area_arr;
                }

                $delyn = ($row1['del_yn'] === 'Y') ? '(<font color="red">삭제됨</font>)' : '';
                $checkyn = ($row1['set_acc'] === 'C') ? 'checked' : '';

                $ruserid   = htmlspecialchars($row1['ruserid'] . $delyn);
                $userid    = htmlspecialchars($row1['userid']  . $delyn);
                $area      = htmlspecialchars($company_area_txt);
                $kor_name  = htmlspecialchars($row1['kor_name']);
                $phone     = htmlspecialchars($row1['company_phone']);
                $manager   = htmlspecialchars($row1['company_manager']);
                $pos       = htmlspecialchars($row1['pos']);
                $seq_no    = (int)$row1['seq_no']; // id는 정수 출력

                $editUrl = "base_agent_m.php?division=$division&pdx=$pdx&sub=$sub&id=$seq_no";

                echo "<tr bgcolor=\"#FFFFFF\">
                        <td align=\"center\">$ruserid</td>
                        <td align=\"center\">$userid</td>
                        <td align=\"center\">$area</td>
                        <td height=\"25\">&nbsp;$kor_name</td>
                        <td>&nbsp;$phone</td>
                        <td>&nbsp;$manager</td>
                        <td align=\"center\">&nbsp;<input type=\"checkbox\" class=\"bs-switch\" data-size=\"mini\" name=\"set_a[]\" $checkyn disabled></td>
                        <td align=\"center\">&nbsp;$pos</td>";

                if ($row1['del_yn'] !== 'Y') {
                    echo   "<td align=\"center\"><a href=\"$editUrl\">수정</a> | <a href=\"javascript:del($seq_no)\">삭제</a></td>
                         </tr>";
                } else {
                    echo   "<td align=\"center\"><a href=\"$editUrl\">수정</a> | <a href=\"javascript:recover($seq_no)\">복구</a></td>
                         </tr>";
                }
            }
        }
    }
?>
     
<div id="contentwrapper">
    <div class="main_content">
        <div id="jCrumbs" class="breadCrumb module">
            <ul>
                <li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li>
                <li><a href="#">기초관리</a></li>
                <li><a href="#">협력사관리</a></li>
                <li>협력사등록</li>
            </ul>
        </div>
        
        <div class="row">
            <div class="col-sm-12 col-md-12">
                <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>?division=<?= htmlspecialchars($division) ?>&pdx=<?= htmlspecialchars($pdx) ?>&sub=<?= htmlspecialchars($sub) ?>" enctype="multipart/form-data" name="base_code" id="base_code" method="post">
                    <input type="hidden" name="mode" value="search">
                    <table class="table table-striped table-bordered table-condensed">
                        <tbody>
                            <tr>
                                <td width="10%" class="titletd" style="vertical-align: middle;">협력사명</td>
                                <td width="20%" style="border:0;" class="conttd">
                                    <input type="text" id="com_nm" name="com_nm" class="inpubase lg" value="<?= htmlspecialchars($com_nm) ?>"/>
                                </td>
                                <td width="5%" class="conttd">
                                    <button type="submit" class="btn btn-primary btn-sm btn1">검색</button>
                                </td>
                                <td class="conttd">
                                    <a href="base_agent_m.php?division=<?= htmlspecialchars($division) ?>&pdx=<?= htmlspecialchars($pdx) ?>&sub=<?= htmlspecialchars($sub) ?>" class="btn btn-primary btn-sm btn1">추가</a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </form>

                <table class="table table-striped table-bordered mediaTable">
                    <thead>
                        <tr>
                            <th width="10%" class="essential" align="center">접속 ID</th>
                            <th width="10%" class="essential" align="center">협력사 ID</th>
                            <th width="10%" class="essential">지역</th>
                            <th width="20%" class="essential">협력사</th>
                            <th width="10%" class="essential">전화</th>
                            <th width="10%" class="essential">담당자</th>
                            <th width="10%" class="essential">회계노출</th>
                            <th width="10%" class="essential">정산위치</th>
                            <th width="10%" class="essential">수정 | 삭제</th>
                        </tr>
                    </thead>
                    <?php printVendor(); ?>
                </table>
            </div><!-- -->
        </div>
    </div>                
</div>

<?php include "include/side_m.php"; ?>

<script>
$(document).ready(function() {
    if (typeof paran_bs_switch !== 'undefined') {
        paran_bs_switch.init && paran_bs_switch.init();
    } else if ($('.bs-switch').length && typeof $.fn.bootstrapSwitch === 'function') {
        $('.bs-switch').bootstrapSwitch();
    }
});

// bootstrap switch (원 코드 유지)
var paran_bs_switch = {
    init: function() {
        if($('.bs-switch').length && typeof $.fn.bootstrapSwitch === 'function') {
            $('.bs-switch').bootstrapSwitch();
        }
    }
};

function del(id){
    if(confirm("삭제할까요?") === true){
        var url = '<?= htmlspecialchars($_SERVER["PHP_SELF"]) ?>'
            + '?mode=del'
            + '&division=<?= htmlspecialchars($division) ?>'
            + '&pdx=<?= htmlspecialchars($pdx) ?>'
            + '&sub=<?= htmlspecialchars($sub) ?>'
            + '&id=' + encodeURIComponent(id);
        location.replace(url);
    }
}
function recover(id){
    if(confirm("복구할까요?") === true){
        var url = '<?= htmlspecialchars($_SERVER["PHP_SELF"]) ?>'
            + '?mode=rec'
            + '&division=<?= htmlspecialchars($division) ?>'
            + '&pdx=<?= htmlspecialchars($pdx) ?>'
            + '&sub=<?= htmlspecialchars($sub) ?>'
            + '&id=' + encodeURIComponent(id);
        location.replace(url);
    }
}
</script>

</body>
</html>
