<?php
include "include/header.php";
//include "include/inc_base.php";

// 로그인 쿠키 확인 (문자열 키 필수)
if (empty($_COOKIE['MEMLOGIN_ADMIN_HELLO'])) {
    echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
    exit;
}


/* ======================================================================== */

// 입력값 안전 수집
$mode = isset($_POST['mode']) ? $_POST['mode'] : (isset($_GET['mode']) ? $_GET['mode'] : '');

// include에서 세팅될 수도 있는 쿼리 조각(Notice 방지)
$diviqry = isset($diviqry) ? $diviqry : '';

// ====== 저장 처리 ======
if ($mode === 'save') {

    // POST 헬퍼
    if (!function_exists('postv')) {
        function postv($key, $default = '')
        {
            return isset($_POST[$key]) ? $_POST[$key] : $default;
        }
    }
    // 업로드 헬퍼: 빈 업로드면 빈 문자열 반환
    if (!function_exists('save_upload')) {
        function save_upload($field)
        {
            if (!isset($_FILES[$field]['tmp_name']) || $_FILES[$field]['tmp_name'] === '') {
                return '';
            }
            return file_save($_FILES[$field], 'upload/');
        }
    }

    // 슬롯 매핑 (PC=img1, Mobile=img2)
    $map = [
        'b1'  => ['alink' => 'alink1',   'apos' => 'apos1',  'pc' => 'userfile1',   'mo' => 'userfile11',  'delPc' => 'photo_del1',  'delMo' => 'photo_del11'],
        'b2'  => ['alink' => 'alink2',   'apos' => 'apos2',  'pc' => 'userfile2',   'mo' => 'userfile22',  'delPc' => 'photo_del2',  'delMo' => 'photo_del22'],
        'b3'  => ['alink' => 'alink3',   'apos' => 'apos3',  'pc' => 'userfile3',   'mo' => 'userfile33',  'delPc' => 'photo_del3',  'delMo' => 'photo_del33'],
        'b4'  => ['alink' => 'alink4',   'apos' => 'apos4',  'pc' => 'userfile4',   'mo' => 'userfile44',  'delPc' => 'photo_del4',  'delMo' => 'photo_del44'],
        'b5'  => ['alink' => 'alink5',   'apos' => 'apos5',  'pc' => 'userfile5',   'mo' => 'userfile55',  'delPc' => 'photo_del5',  'delMo' => 'photo_del55'],
        'b6'  => ['alink' => 'alink6',   'apos' => 'apos6',  'pc' => 'userfile6',   'mo' => 'userfile66',  'delPc' => 'photo_del6',  'delMo' => 'photo_del66'],
        'b7'  => ['alink' => 'alink7',   'apos' => 'apos7',  'pc' => 'userfile7',   'mo' => 'userfile77',  'delPc' => 'photo_del7',  'delMo' => 'photo_del77'],
        'b8'  => ['alink' => 'alink8',   'apos' => 'apos8',  'pc' => 'userfile8',   'mo' => 'userfile88',  'delPc' => 'photo_del8',  'delMo' => 'photo_del88'],
        'b9'  => ['alink' => 'alink9',   'apos' => 'apos9',  'pc' => 'userfile9',   'mo' => 'userfile99',  'delPc' => 'photo_del9',  'delMo' => 'photo_del99'],
        'b10' => ['alink' => 'alink10',  'apos' => 'apos10', 'pc' => 'userfile10',  'mo' => 'userfile100', 'delPc' => 'photo_del10', 'delMo' => 'photo_del100'],
        'b11' => ['alink' => 'alink111', 'apos' => 'apos11', 'pc' => 'userfile110', 'mo' => 'userfile1110','delPc' => 'photo_del101','delMo' => 'photo_del1101'],
    ];

    foreach ($map as $area => $m) {
        $alink = $dbConn->real_escape_string(postv($m['alink'], ''));
        $pos   = $dbConn->real_escape_string(postv($m['apos'], ''));

        $sets  = [
            "alink='{$alink}'",
            "pos='{$pos}'",
        ];

        // PC 이미지
        $newPc = save_upload($m['pc']);
        if ($newPc !== '' || postv($m['delPc']) === '1') {
            $sets[] = "img1='" . $dbConn->real_escape_string($newPc) . "'";
        }

        // Mobile 이미지
        $newMo = save_upload($m['mo']);
        if ($newMo !== '' || postv($m['delMo']) === '1') {
            $sets[] = "img2='" . $dbConn->real_escape_string($newMo) . "'";
        }

        $sql = "UPDATE banner_page 
                   SET " . implode(', ', $sets) . " 
                 WHERE 1=1 {$diviqry} AND area='" . $dbConn->real_escape_string($area) . "'";
        $dbConn->query($sql);
    }

    echo "<meta http-equiv='refresh' content='0; url=./m_banner.php?division=9&pdx=2&sub=20'>";
    exit;
}

// ====== 표시용 데이터 로드 ======
$areas = ['b1','b2','b3','b4','b5','b6','b7','b8','b9','b10','b11'];
$escapedAreas = array();
foreach ($areas as $a) { $escapedAreas[] = "'" . $dbConn->real_escape_string($a) . "'"; }
$in  = implode(",", $escapedAreas);

$sql = "SELECT area, pos, alink, img1, img2 FROM banner_page WHERE area IN ($in)";
$rs  = $dbConn->query($sql);

$rowsByArea = [];
if ($rs) {
    while ($r = $rs->fetch_assoc()) {
        $rowsByArea[$r['area']] = $r;
    }
}

// 화면 렌더링용 슬롯 메타
$viewMap = [
    'b1'  => ['label' => '메인1',  'apos' => 'apos1',  'alink' => 'alink1',  'pc' => 'userfile1',   'mo' => 'userfile11',  'delPc' => 'photo_del1',  'delMo' => 'photo_del11'],
    'b2'  => ['label' => '메인2',  'apos' => 'apos2',  'alink' => 'alink2',  'pc' => 'userfile2',   'mo' => 'userfile22',  'delPc' => 'photo_del2',  'delMo' => 'photo_del22'],
    'b3'  => ['label' => '메인3',  'apos' => 'apos3',  'alink' => 'alink3',  'pc' => 'userfile3',   'mo' => 'userfile33',  'delPc' => 'photo_del3',  'delMo' => 'photo_del33'],
    'b4'  => ['label' => '메인4',  'apos' => 'apos4',  'alink' => 'alink4',  'pc' => 'userfile4',   'mo' => 'userfile44',  'delPc' => 'photo_del4',  'delMo' => 'photo_del44'],
    'b5'  => ['label' => '메인5',  'apos' => 'apos5',  'alink' => 'alink5',  'pc' => 'userfile5',   'mo' => 'userfile55',  'delPc' => 'photo_del5',  'delMo' => 'photo_del55'],
    'b6'  => ['label' => '메인6',  'apos' => 'apos6',  'alink' => 'alink6',  'pc' => 'userfile6',   'mo' => 'userfile66',  'delPc' => 'photo_del6',  'delMo' => 'photo_del66'],
    'b7'  => ['label' => '메인7',  'apos' => 'apos7',  'alink' => 'alink7',  'pc' => 'userfile7',   'mo' => 'userfile77',  'delPc' => 'photo_del7',  'delMo' => 'photo_del77'],
    'b8'  => ['label' => '메인8',  'apos' => 'apos8',  'alink' => 'alink8',  'pc' => 'userfile8',   'mo' => 'userfile88',  'delPc' => 'photo_del8',  'delMo' => 'photo_del88'],
    'b9'  => ['label' => '메인9',  'apos' => 'apos9',  'alink' => 'alink9',  'pc' => 'userfile9',   'mo' => 'userfile99',  'delPc' => 'photo_del9',  'delMo' => 'photo_del99'],
    'b10' => ['label' => '메인10', 'apos' => 'apos10', 'alink' => 'alink10', 'pc' => 'userfile10',  'mo' => 'userfile100', 'delPc' => 'photo_del10', 'delMo' => 'photo_del100'],
    'b11' => ['label' => '메인11', 'apos' => 'apos11', 'alink' => 'alink111','pc' => 'userfile110', 'mo' => 'userfile1110','delPc' => 'photo_del101','delMo' => 'photo_del1101'],
];

// HTML 이스케이프
if (!function_exists('h')) {
    function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <title>메인배너 관리</title>
</head>
<body>
<div id="contentwrapper">
    <div class="main_content">
        <div id="jCrumbs" class="breadCrumb module">
            <ul>
                <li><a href="/"><i class="glyphicon glyphicon-home"></i></a></li>
                <li><a href="#">홈페이지관련설정</a></li>
                <li>메인배너</li>
            </ul>
        </div>

        <div class="row">
            <div class="col-sm-12 col-md-12 tab-content">
                <div id="home" class="tab-pane fade in active">
                    <form action="m_banner.php?division=9&pdx=2&sub=20" enctype="multipart/form-data" method="post">
                        <input type="hidden" name="mode" value="save">
                        <input type="hidden" name="divi" value="head">

                        <table id="productDetailForm" class="table table-bordered table-condensed gridSixteen reserveTable formDetail js-base" width="98%" align="center" border="0" cellspacing="1" bgcolor="#cccccc" cellpadding="0">
<?php
foreach ($areas as $area) {
    $meta = $viewMap[$area];
    $row  = isset($rowsByArea[$area]) ? $rowsByArea[$area] : ['pos'=>'','alink'=>'','img1'=>'','img2'=>''];

    $label = $meta['label'];
    $apos  = $meta['apos'];
    $alink = $meta['alink'];
    $pc    = $meta['pc'];
    $mo    = $meta['mo'];
    $delPc = $meta['delPc'];
    $delMo = $meta['delMo'];

    $posVal   = h(isset($row['pos'])   ? $row['pos']   : '');
    $alinkVal = h(isset($row['alink']) ? $row['alink'] : '');
    $img1Val  = h(isset($row['img1'])  ? $row['img1']  : '');
    $img2Val  = h(isset($row['img2'])  ? $row['img2']  : '');

    echo <<<HTML
                            <tr bgcolor="#FFFFFF">
                                <td width="10%" height="30" class="malgun">
                                    &nbsp;<i class="glyphicon glyphicon-folder-close"></i> {$label}<br>
                                    &nbsp;<input type="text" class="form-control" id="{$apos}" name="{$apos}" placeholder="위치" value="{$posVal}">
                                </td>
                                <td width="33%" height="30" class="malgun">
                                    &nbsp;<input type="text" class="form-control" id="{$alink}" name="{$alink}" placeholder="링크" value="{$alinkVal}">
                                    &nbsp;PC <input type="file" name="{$pc}" size="30">
HTML;

    if ($img1Val !== '') {
        echo '<div style="margin-top:6px;">현재: ' . $img1Val . ' &nbsp; <label><input type="checkbox" id="'.$delPc.'" name="'.$delPc.'" value="1"> 삭제</label></div>';
    }

    // *** 여기 문법 오류 수정 (따옴표/세미콜론) ***
    echo ' <br>&nbsp;Mobile <input type="file" name="'.$mo.'" size="30">';

    if ($img2Val !== '') {
        echo '<div style="margin-top:6px;">현재: ' . $img2Val . ' &nbsp; <label><input type="checkbox" id="'.$delMo.'" name="'.$delMo.'" value="1"> 삭제</label></div>' . "\n";
    }

    echo "                                </td>\n";
    echo "                            </tr>\n";
}
?>
                            <tr>
                                <td colspan="4" height="35" bgcolor="#FFFFFF" align="center" class="malgun">
                                    <input type="submit" value="저장" class="btn btn-primary btn-sm">
                                </td>
                            </tr>
                        </table>
                        <br><br>
                    </form>
                </div>
            </div><!-- /.col -->
        </div><!-- /.row -->
    </div><!-- /.main_content -->
</div><!-- /#contentwrapper -->

<?php include "include/side_m.php"; ?>
</body>
</html>
