<?php
    include "include/header.php";

    // ───────────── 공통 헬퍼 ─────────────
    function in_get($key, $default = '')  { return isset($_GET[$key])  ? $_GET[$key]  : $default; }
    function in_post($key, $default = '') { return isset($_POST[$key]) ? $_POST[$key] : $default; }
    function in_cookie($key, $default = '') { return isset($_COOKIE[$key]) ? $_COOKIE[$key] : $default; }

    // ───────────── 입력 파라미터 ─────────────
    $mode    = in_post('mode', in_get('mode'));
    $division = in_get('division');
    $pdx      = in_get('pdx');
    $sub      = in_get('sub');

    $lvcode1 = in_get('lvcode1', in_post('lvcode1'));
    $lvcode2 = in_get('lvcode2', in_post('lvcode2'));
    $lvcode3 = in_get('lvcode3', in_post('lvcode3'));
    $lvcode4 = in_get('lvcode4', in_post('lvcode4'));
    $lvcode5 = in_get('lvcode5', in_post('lvcode5')); // 일부 화면에서 쓰일 수 있어 가드

    $lvcode1_value = in_post('lvcode1_value', $lvcode1);
    $lvcode2_value = in_post('lvcode2_value', $lvcode2);
    $lvcode3_value = in_post('lvcode3_value', $lvcode3);
    $lvcode4_value = in_post('lvcode4_value', $lvcode4);
    $comment       = in_post('comment', '');
    $active        = in_post('active', 'yes'); // 라디오 기본값

    // ───────────── 로그인/권한 체크 ─────────────
    if (in_cookie('MEMLOGIN_ADMIN_HELLO') === '') {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
        exit;
    }

    if (!hasMenuAccess($division, $pdx, $sub)) {
        $goUrl_1 = "index.php";
        Misc::jvAlert("권한이 있는 메뉴가 아닙니다. 확인후 사용하세요.!!", "");
        echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
        exit;
    }

    // ───────────── 저장/삭제 처리 ─────────────
    if ($mode === "save") {
        // 파일 업로드 (이미지)
        $savedImage = "";
        if (!empty($_FILES['image']['tmp_name'])) {
            // 기존 함수 사용(프로젝트 공용 업로더)
            $savedImage = file_save1($_FILES['image'], "upload/");
        }

        // mysqli escaping (PHP 7.0 OK)
        $lv1 = $dbConn->real_escape_string($lvcode1_value);
        $lv2 = $dbConn->real_escape_string($lvcode2_value);
        $lv3 = $dbConn->real_escape_string($lvcode3_value);
        $lv4 = $dbConn->real_escape_string($lvcode4_value);
        $cm  = $dbConn->real_escape_string($comment);
        $ac  = ($active === 'no') ? 'no' : 'yes';
        $img = $dbConn->real_escape_string($savedImage);

        // NOTE: image/active 컬럼에 실제 값 반영
        $qry1 = "
            INSERT INTO code_base
                (lvcode1, lvcode2, lvcode3, lvcode4, comment, active, image, modified, created)
            VALUES
                ('$lv1', '$lv2', '$lv3', '$lv4', '$cm', '$ac', '$img', '', NOW())
        ";
        $rst1 = $dbConn->query($qry1);

        if (!$rst1) {
            // 개발 편의용 에러 표시(운영시 로그로 교체 권장)
            echo "DB Error: " . $dbConn->error;
            exit;
        }
    } else if ($mode === "del") {
        // 삭제 파라미터 가드
        $lv1 = $dbConn->real_escape_string($lvcode1);
        $lv2 = $dbConn->real_escape_string($lvcode2);
        $lv3 = $dbConn->real_escape_string($lvcode3);
        $lv4 = $dbConn->real_escape_string($lvcode4);

        $qry2 = "
            DELETE FROM code_base
            WHERE lvcode1 = '$lv1' AND lvcode2 = '$lv2' AND lvcode3 = '$lv3' AND lvcode4 = '$lv4'
        ";
        $rst2 = $dbConn->query($qry2);

        if ($rst2) {
            Misc::jvAlert("Completed!", "location.replace('base_code.php?division=$division&pdx=$pdx&sub=$sub&lvcode1=$lvcode1')");
            exit;
        } else {
            Misc::jvAlert("Error!", "history.go(-1)");
            exit;
        }
    }

    // ───────────── 코드 드롭다운 렌더 ─────────────
    function printCode1($code1) {
        global $dbConn;
        $qry = "SELECT * FROM code_base WHERE lvcode2='00' AND lvcode3='00' AND lvcode4='00' ORDER BY lvcode1 ASC";
        if ($rs = $dbConn->query($qry)) {
            while ($row = $rs->fetch_assoc()) {
                $val = htmlspecialchars($row['lvcode1']);
                $txt = htmlspecialchars($row['comment']) . " ($val)";
                $sel = ($row['lvcode1'] == $code1) ? ' selected' : '';
                echo "<option value=\"$val\"$sel>$txt</option>";
            }
        }
    }

    function printCode2($code1, $code2) {
        global $dbConn;
        $c1  = $dbConn->real_escape_string($code1);
        $qry = "SELECT * FROM code_base WHERE lvcode1='$c1' AND lvcode2<>'00' AND lvcode3='00' AND lvcode4='00' ORDER BY lvcode2 ASC";
        if ($rs = $dbConn->query($qry)) {
            while ($row = $rs->fetch_assoc()) {
                $val = htmlspecialchars($row['lvcode2']);
                $txt = htmlspecialchars($row['comment']);
                $sel = ($row['lvcode2'] == $code2) ? ' selected' : '';
                echo "<option value=\"$val\"$sel>$txt</option>";
            }
        }
    }

    function printCode3($code1, $code2, $code3) {
        global $dbConn;
        $c1 = $dbConn->real_escape_string($code1);
        $c2 = $dbConn->real_escape_string($code2);
        $qry = "SELECT * FROM code_base WHERE lvcode1='$c1' AND lvcode2='$c2' AND lvcode3<>'00' AND lvcode4='00' ORDER BY lvcode3 ASC";
        if ($rs = $dbConn->query($qry)) {
            while ($row = $rs->fetch_assoc()) {
                $val = htmlspecialchars($row['lvcode3']);
                $txt = htmlspecialchars($row['comment']);
                $sel = ($row['lvcode3'] == $code3) ? ' selected' : '';
                echo "<option value=\"$val\"$sel>$txt</option>";
            }
        }
    }

    function printCode4($code1, $code2, $code3, $code4) {
        global $dbConn;
        $c1 = $dbConn->real_escape_string($code1);
        $c2 = $dbConn->real_escape_string($code2);
        $c3 = $dbConn->real_escape_string($code3);
        $qry = "SELECT * FROM code_base WHERE lvcode1='$c1' AND lvcode2='$c2' AND lvcode3='$c3' AND lvcode4<>'00' ORDER BY lvcode4 ASC";
        if ($rs = $dbConn->query($qry)) {
            while ($row = $rs->fetch_assoc()) {
                $val = htmlspecialchars($row['lvcode4']);
                $txt = htmlspecialchars($row['comment']);
                $sel = ($row['lvcode4'] == $code4) ? ' selected' : '';
                echo "<option value=\"$val\"$sel>$txt</option>";
            }
        }
    }

    // (참고) 원래 코드에 printCode5가 있었지만, 테이블 정의에 lvcode5 컬럼이 없거나 사용처가 불명확하여
    // 안전상 제외했습니다. 필요하면 lvcode5 컬럼 존재 여부 확인 후 동일 패턴으로 추가하세요.

    function printContentlist($code1, $code2, $code3, $code4) {
        global $dbConn, $division, $pdx, $sub;

        $cond = [];
        if ($code1 !== '') { $cond[] = "lvcode1 = '" . $dbConn->real_escape_string($code1) . "'"; }
        if ($code2 !== '') { $cond[] = "lvcode2 = '" . $dbConn->real_escape_string($code2) . "'"; }
        if ($code3 !== '') { $cond[] = "lvcode3 = '" . $dbConn->real_escape_string($code3) . "'"; }
        if ($code4 !== '') { $cond[] = "lvcode4 = '" . $dbConn->real_escape_string($code4) . "'"; }

        $where = $cond ? ('WHERE ' . implode(' AND ', $cond)) : '';
        $qry = "SELECT * FROM code_base $where ORDER BY lvcode1, lvcode2, lvcode3, lvcode4 ASC";
        $rs  = $dbConn->query($qry);

        if ($rs) {
            while ($row = $rs->fetch_assoc()) {
                $isActive = ($row['active'] === 'yes') ? 'Active' : 'Inactive';
                $icon = '';
                if (!empty($row['image'])) {
                    $src  = 'upload/' . rawurlencode($row['image']);
                    $icon = "<img width='30%' src='{$src}' alt='icon'>";
                }

                $lv1 = htmlspecialchars($row['lvcode1']);
                $lv2 = htmlspecialchars($row['lvcode2']);
                $lv3 = htmlspecialchars($row['lvcode3']);
                $lv4 = htmlspecialchars($row['lvcode4']);
                $cmt = htmlspecialchars($row['comment']);
                $desc = htmlspecialchars(isset($row['desc_comm']) ? $row['desc_comm'] : '');
                $act = htmlspecialchars($isActive);

                $editUrl = "base_code_edit.php?division=$division&pdx=$pdx&sub=$sub&mode=modify&lvcode1=$lv1&lvcode2=$lv2&lvcode3=$lv3&lvcode4=$lv4";
                echo "<tr>
                        <td>&nbsp;$lv1</td>
                        <td>&nbsp;$lv2</td>
                        <td>&nbsp;$lv3</td>
                        <td>&nbsp;$lv4</td>
                        <td>&nbsp;$cmt<br><span style='color:blue'>$desc</span></td>
                        <td>$icon</td>
                        <td>&nbsp;$act</td>
                        <td align='left'>
                            <a class='btn btn-primary btn-xs btnatt' href='$editUrl'>수정</a> |
                            <a class='btn btn-danger btn-xs' href=\"javascript:del('$lv1','$lv2','$lv3','$lv4','')\">삭제</a>
                        </td>
                      </tr>";
            }
        }
    }

    // 메뉴 정보 (원 코드 유지)
    $rowm = getinfo_menufst($user_dbinfo['userid'], $division);
?>
     
<div id="contentwrapper">
    <div class="main_content">
        <div id="jCrumbs" class="breadCrumb module">
            <ul>
                <li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li>
                <li><a href="#">기초관리</a></li>
                <li><a href="#">기초관리</a></li>
                <li>기초코드등록</li>
            </ul>
        </div>
        <div class="row">
            <div class="col-sm-12 col-md-12">
                <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>?division=<?= htmlspecialchars($division) ?>&pdx=<?= htmlspecialchars($pdx) ?>&sub=<?= htmlspecialchars($sub) ?>" enctype="multipart/form-data" name="base_code" id="base_code" method="post">
                    <input type="hidden" name="mode" value="save">
                    <input type="hidden" name="lvcode1" id="lvcode1" value="<?= htmlspecialchars($lvcode1) ?>">
                    <input type="hidden" name="lvcode2" value="<?= htmlspecialchars($lvcode2) ?>">
                    <input type="hidden" name="lvcode3" value="<?= htmlspecialchars($lvcode3) ?>">
                    <input type="hidden" name="lvcode4" value="<?= htmlspecialchars($lvcode4) ?>">

                    <table class="table table-striped table-advance table-hover">
                        <tbody>
                            <tr>
                                <th width="9%" align="center">분류</th>
                                <th width="9%" align="center">대분류</th>
                                <th width="9%" align="center">중분류</th>
                                <th width="9%" align="center">세분류</th>
                                <th width="*" align="center">코드정의</th>
                                <th width="9%" align="center">이미지</th>
                                <th width="10%" align="center">사용유무</th>
                                <th width="8%" align="center"><i class="glyphicon glyphicon-cog"></i>Action</th>
                            </tr>
                            <tr>
                                <td>
                                    <select id="lvcode1_sel" class="form-control" onChange="go_change(this.value)">
                                        <option value="" selected>코드선택</option>
                                        <?php printCode1($lvcode1); ?>
                                    </select>
                                </td>
                                <td>
                                    <?php if ($lvcode1): ?>
                                        <select id="lvcode2_sel" class="form-control" onChange="go_change2(this.value,'<?= htmlspecialchars($lvcode1) ?>')">
                                            <option value="">코드선택</option>
                                            <?php printCode2($lvcode1, $lvcode2); ?>
                                        </select>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($lvcode1 && $lvcode2): ?>
                                        <select id="lvcode3_sel" class="form-control" onChange="go_change3(this.value,'<?= htmlspecialchars($lvcode1) ?>','<?= htmlspecialchars($lvcode2) ?>')">
                                            <option value="">코드선택</option>
                                            <?php printCode3($lvcode1, $lvcode2, $lvcode3); ?>
                                        </select>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($lvcode1 && $lvcode2 && $lvcode3): ?>
                                        <select id="lvcode4_sel" class="form-control" onChange="go_change4(this.value,'<?= htmlspecialchars($lvcode1) ?>','<?= htmlspecialchars($lvcode2) ?>','<?= htmlspecialchars($lvcode3) ?>')">
                                            <option value="">코드선택</option>
                                            <?php printCode4($lvcode1, $lvcode2, $lvcode3, $lvcode4); ?>
                                        </select>
                                    <?php endif; ?>
                                </td>
                                <td><!-- (기존 code_etc/lvcode5 부분은 주석 처리) --></td>
                                <td> </td>
                                <td>&nbsp;</td>
                                <td> </td>
                                <?php printContentlist($lvcode1, $lvcode2, $lvcode3, $lvcode4); ?>
                            </tr>

                            <tr>
                                <td><input type="text" id="lvcode1_value" name="lvcode1_value" class="form-control" value="<?= htmlspecialchars($lvcode1) ?>" /></td>
                                <td><input type="text" id="lvcode2_value" name="lvcode2_value" class="form-control" value="<?= htmlspecialchars($lvcode2) ?>" /></td>
                                <td><input type="text" id="lvcode3_value" name="lvcode3_value" class="form-control" value="<?= htmlspecialchars($lvcode3) ?>" /></td>
                                <td><input type="text" id="lvcode4_value" name="lvcode4_value" class="form-control" value="<?= htmlspecialchars($lvcode4) ?>" /></td>
                                <td><input type="text" id="comment" name="comment" style="width:100%;" class="form-control" placeholder="코드정의" /></td>
                                <td>&nbsp;<input type="file" name="image"></td>
                                <td>&nbsp;
                                    <label><input type="radio" name="active" value="yes" <?= ($active !== 'no' ? 'checked' : '') ?>> Active</label><br>
                                    <label><input type="radio" name="active" value="no"  <?= ($active === 'no' ? 'checked' : '') ?>> Inactive</label>
                                </td>
                                <td>&nbsp;&nbsp;<button type="submit" class="btn btn-primary btn-sm btnatt" onclick="return chk();">저장</button></td>
                            </tr>
                        </tbody>
                    </table>
                </form>
            </div><!-- -->
        </div>
    </div>
</div>

<?php include "include/side_m.php"; ?>

<script src="js/jquery.min.js"></script>
<script src="js/jquery-migrate.min.js"></script>
<script src="lib/jquery-ui/jquery-ui-1.10.0.custom.min.js"></script>
<script src="js/forms/jquery.ui.touch-punch.min.js"></script>
<script src="js/jquery.easing.1.3.min.js"></script>
<script src="js/jquery.debouncedresize.min.js"></script>
<script src="js/jquery_cookie_min.js"></script>
<script src="bootstrap/js/bootstrap.min.js"></script>
<script src="js/bootstrap.plugins.min.js"></script>
<script src="lib/typeahead/typeahead.min.js"></script>
<script src="lib/google-code-prettify/prettify.min.js"></script>
<script src="lib/sticky/sticky.min.js"></script>
<script src="lib/colorbox/jquery.colorbox.min.js"></script>
<script src="lib/jBreadcrumbs/js/jquery.jBreadCrumb.1.1.min.js"></script>
<script src="js/jquery.actual.min.js"></script>
<script src="lib/slimScroll/jquery.slimscroll.js"></script>
<script src="js/ios-orientationchange-fix.js"></script>
<script src="lib/UItoTop/jquery.ui.totop.min.js"></script>
<script src="js/selectNav.js"></script>
<script src="lib/moment/moment.min.js"></script>
<script src="js/pages/gebo_common.js"></script>

<script>
function chk(){
    if(!$("#lvcode1_value").val()){
        alert('No Value !!');
        $("#lvcode1_value").focus();
        return false;
    }
    return true;
}

function go_change(str){
    location.replace('base_code.php?division=1&pdx=<?= htmlspecialchars($pdx) ?>&sub=<?= htmlspecialchars($sub) ?>&lvcode1=' + encodeURIComponent(str));
}
function go_change2(str,code1){
    location.replace('base_code.php?division=1&pdx=<?= htmlspecialchars($pdx) ?>&sub=<?= htmlspecialchars($sub) ?>&lvcode1=' + encodeURIComponent(code1) + '&lvcode2=' + encodeURIComponent(str));
}
function go_change3(str,code1,code2){
    location.replace('base_code.php?division=1&pdx=<?= htmlspecialchars($pdx) ?>&sub=<?= htmlspecialchars($sub) ?>&lvcode1=' + encodeURIComponent(code1) + '&lvcode2=' + encodeURIComponent(code2) + '&lvcode3=' + encodeURIComponent(str));
}
function go_change4(str,code1,code2,code3){
    location.replace('base_code.php?division=1&pdx=<?= htmlspecialchars($pdx) ?>&sub=<?= htmlspecialchars($sub) ?>&lvcode1=' + encodeURIComponent(code1) + '&lvcode2=' + encodeURIComponent(code2) + '&lvcode3=' + encodeURIComponent(code3) + '&lvcode4=' + encodeURIComponent(str));
}
function del(lvcode1,lvcode2,lvcode3,lvcode4,lvcode5){
    if(confirm("Delete?") === true){
        location.replace('<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>?division=1&mode=del&pdx=<?= htmlspecialchars($pdx) ?>&sub=<?= htmlspecialchars($sub) ?>&lvcode1=' + encodeURIComponent(lvcode1) + '&lvcode2=' + encodeURIComponent(lvcode2) + '&lvcode3=' + encodeURIComponent(lvcode3) + '&lvcode4=' + encodeURIComponent(lvcode4) + '&lvcode5=' + encodeURIComponent(lvcode5 || ''));
    }
}
</script>
</body>
</html>
