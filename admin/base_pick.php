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
    $mode     = in_get('mode', in_post('mode',''));           // ← Mode 오타 방지(대/소문자)
    $pcode    = in_get('pcode','');
    $pick_nm  = in_post('pick_nm', in_get('pick_nm',''));     // 검색어

    // ===== 로그인/권한 체크 =====
    if (in_cookie('MEMLOGIN_ADMIN_HELLO') === '') {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>"; exit;
    }
    if (!hasMenuAccess($division, $pdx, $sub)) {
        $goUrl_1 = "index.php";
        Misc::jvAlert("권한이 있는 메뉴가 아닙니다. 확인후 사용하세요.!!","");
        echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>"; exit;
    }

    // ===== 삭제 처리 =====
    if ($mode === "del" && $pcode !== '') {
        $pc = $dbConn->real_escape_string($pcode);
        $qry1 = "DELETE FROM base_pick WHERE pick_code = '$pc'";
        $rst1 = $dbConn->query($qry1);
        // 필요 시 결과 체크/알림 추가 가능
    }

    // ===== 목록 렌더링 =====
    function printPick() {
        global $dbConn,$division,$pdx,$sub,$pick_nm;

        $where = "WHERE 1=1 AND pick_m='M'";
        if ($pick_nm !== '') {
            $kw = $dbConn->real_escape_string($pick_nm);
            $where .= " AND pick_name LIKE '%$kw%'";
        }

        $qry = "SELECT * FROM base_pick $where ORDER BY pick_code ASC";
        if ($rs = $dbConn->query($qry)) {
            while ($row = $rs->fetch_assoc()) {
                $pick_code  = htmlspecialchars($row['pick_code']);
                $pick_name  = htmlspecialchars($row['pick_name']);
                $pick_1desc = htmlspecialchars($row['pick_1desc']);
                $pick_addr  = htmlspecialchars($row['pick_addr']);

                $editUrl = "base_pick_m.php?division=$division&pdx=$pdx&sub=$sub&pcode=$pick_code";
                echo "<tr bgcolor=\"#FFFFFF\">
                        <td align=\"center\">$pick_code</td>
                        <td align=\"center\">$pick_name</td>
                        <td align=\"center\">$pick_1desc</td>
                        <td align=\"center\">$pick_addr</td>
                        <td align=\"center\">
                          <a href=\"$editUrl\">수정</a> |
                          <a href=\"javascript:del('$pick_code')\">삭제</a>
                        </td>
                      </tr>";
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
        <li><a href="#">기초관리</a></li>
        <li>탑승지등록</li>
      </ul>
    </div>

    <div class="row">
      <div class="col-sm-12 col-md-12">
        <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>?division=<?= htmlspecialchars($division) ?>&pdx=<?= htmlspecialchars($pdx) ?>&sub=<?= htmlspecialchars($sub) ?>" enctype="multipart/form-data" name="base_code" id="base_code" method="post">
          <input type="hidden" name="mode" value="search">
          <table class="table table-striped table-bordered table-condensed">
            <tbody>
              <tr>
                <td width="10%" class="titletd" style="vertical-align: middle;">탑승지명</td>
                <td width="20%" style="border:0;" class="conttd">
                  <input type="text" id="pick_nm" name="pick_nm" class="inpubase lg" value="<?= htmlspecialchars($pick_nm) ?>"/>
                </td>
                <td width="5%" class="conttd">
                  <button type="submit" class="btn btn-primary btn-sm btn1 btnatt">검색</button>
                </td>
                <td class="conttd">
                  <a href="base_pick_m.php?division=<?= htmlspecialchars($division) ?>&pdx=<?= htmlspecialchars($pdx) ?>&sub=<?= htmlspecialchars($sub) ?>" class="btn btn-primary btn-sm btn1 btnatt">추가</a>
                </td>
              </tr>
            </tbody>
          </table>
        </form>

        <table class="table table-striped table-bordered mediaTable">
          <thead>
            <tr>
              <th width="10%" class="essential" align="center">탑승지코드</th>
              <th width="20%" class="essential" align="center">탑승지명</th>
              <th width="30%" class="essential">한줄설명</th>
              <th width="*"   class="essential">주소</th>
              <th width="10%" class="essential">수정 | 삭제</th>
            </tr>
          </thead>
          <?php printPick(); ?>
        </table>

      </div><!-- /.col -->
    </div><!-- /.row -->
  </div>
</div>

<?php include "include/side_m.php"; ?>

<script>
  function del(id){
    if(confirm("삭제할까요?") === true){
      var url = '<?= htmlspecialchars($_SERVER["PHP_SELF"]) ?>'
        + '?mode=del'
        + '&division=<?= htmlspecialchars($division) ?>'
        + '&pdx=<?= htmlspecialchars($pdx) ?>'
        + '&sub=<?= htmlspecialchars($sub) ?>'
        + '&pcode=' + encodeURIComponent(id);
      location.replace(url);
    }
  }
</script>
</body>
</html>
