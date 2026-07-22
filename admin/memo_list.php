<?php
	include "include/header.php";
	
	// 변수 초기화
	$mode = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '';
	$no = isset($_REQUEST['no']) ? $_REQUEST['no'] : '';
	$content1 = isset($_REQUEST['content1']) ? $_REQUEST['content1'] : '';
	$content2 = isset($_REQUEST['content2']) ? $_REQUEST['content2'] : '';
	$board_mode = isset($_REQUEST['board_mode']) ? $_REQUEST['board_mode'] : '';
	$start = isset($_REQUEST['start']) ? $_REQUEST['start'] : 0;
	$sdate = isset($_REQUEST['sdate']) ? $_REQUEST['sdate'] : ''; // 검색용 날짜
	$stdate = isset($_REQUEST['stdate']) ? $_REQUEST['stdate'] : ''; // 수정/보기용 날짜
	
	// 검색어 파라미터 받기
	$search_mode = isset($_REQUEST['search_mode']) ? $_REQUEST['search_mode'] : 'all'; // 기본값을 'all'로 설정
	$search_text = isset($_REQUEST['search_text']) ? $_REQUEST['search_text'] : '';

	if($_COOKIE['MEMLOGIN_ADMIN_HELLO'] !="")
	{
	} else {
		
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}

	// [삭제 로직]
	if ($mode == "del")
	{
		$qry1 = "delete from memo_board where seq_no = '$no'";
		$rst1 = $dbConn->query($qry1);

		if($rst1)
		{
			Misc::jvAlert("Completed!","location.replace('memo_list.php')");
			exit;
		}
	}

    // [수정 로직]
    if ($mode == "modify")
	{
		$qry1 = "update memo_board  set  content1='$content1' ,content2='$content2' where seq_no = '$no'";
		$rst1 = $dbConn->query($qry1);
		if($rst1)
		{
			Misc::jvAlert("Completed!","location.replace('memo_list.php')");
			exit;
		}
	}

	// [편집 모드 로딩]
	if ($mode == "edit")
	{
		$qry1 = "select * from memo_board where seq_no = '$no'";
		$rst1 = $dbConn->query($qry1);
		$row1 = $rst1->fetch_assoc();
		$content1=$row1['content1'];
		$content2=$row1['content2'];
		$s_date=$row1['date']; // 작성폼에 뿌려질 날짜
	} else if ($stdate!="") {
		$qry1 = "select * from memo_board where date = '$stdate'";
		$rst1 = $dbConn->query($qry1);
		$row1 = $rst1->fetch_assoc();
		$content1=$row1['content1'];
		$content2=$row1['content2'];
		$s_date=$row1['date'];
	}
	
	// [쓰기/저장 로직]
	if($board_mode == "write")
	{
		$write_date = isset($_REQUEST['s_date']) ? $_REQUEST['s_date'] : '';

		$qry2 = "select * from memo_board  where  date='$write_date'";
		$rst2 = $dbConn->query($qry2);
		$result_rows=$rst2->num_rows;
		
		if ($result_rows > 0) {
			if ($no == "") {
				$row2= $rst2->fetch_assoc(); 
				$row2['content1'] = $row2['content1']."<br>".$content1;
				$row2['content2'] = $row2['content2']."<br>".$content2;

				$qry1 = "update memo_board  set  content1='$row2[content1]' ,content2='$row2[content2]' where  date='$write_date'";
				$rst1 = $dbConn->query($qry1);
            } else {
				$qry1 = "update memo_board  set  content1='$content1' ,content2='$content2' where seq_no = '$no'";
				$rst1 = $dbConn->query($qry1);
				if($rst1)
				{
					Misc::jvAlert("Completed!","location.replace('memo_list.php')");
					exit;
				}
			}
		} else {
			$qry1 = "insert into memo_board values ('','$user_dbinfo[userid]','$user_name','$write_date','$content1','$content2',now())";
			$rst1 = $dbConn->query($qry1);
		}

		if($rst1)
		{
			Misc::jvAlert("Completed!","location.replace('memo_list.php')");
			exit;
		}
	}

	$board_scale = 20;
	$board_page = 10;
	$scale=$board_scale;
	$page_scale=$board_page;

	// [검색 쿼리 생성]
	$where_clause = " where 1=1 ";

	// 날짜 검색 ($sdate 파라미터가 있으면 적용)
	if ($sdate!="") {
		$where_clause .= " and date='$sdate' ";
    }

	// [텍스트 검색 개선: OR 조건 추가]
	if ($search_text != "") {
		if ($search_mode == "name") {
			$where_clause .= " and name like '%$search_text%' ";
		} else if ($search_mode == "content") {
			$where_clause .= " and (content1 like '%$search_text%' or content2 like '%$search_text%') ";
		} else {
            // search_mode가 'all'이거나 없을 때는 전체 검색 (Name OR Content)
            $where_clause .= " and (name like '%$search_text%' or content1 like '%$search_text%' or content2 like '%$search_text%') ";
        }
	}

	$que = "select * from memo_board $where_clause order by date desc  LIMIT $start, $scale";
	$page=floor($start/($scale*$page_scale));

	$result=$dbConn->query($que);
	$result_rows=$result->num_rows;
	$total=$dbConn->affected_rows;
	$last=floor($total/$scale);

	// 페이지 카운트
	$page_total_qry = $dbConn->query("SELECT count(*) FROM memo_board $where_clause");
    $page_total = removeMysql_result($page_total_qry,0,0);

	$page_last = floor($page_total/$scale);
	$total_page_num = ceil($page_total/$scale);
	$now_page_num = floor($start/$scale) + 1;
	
    // [하이라이트 헬퍼 함수 추가]
    function highlight_view($content, $keyword) {
        if(!$keyword) return $content;
        // HTML 태그 내부(속성 등)를 제외하고 텍스트만 찾아서 하이라이트 처리
        // (?![^<]*>) : 뒤에 '>'가 나오기 전에 '<'가 나오지 않음 (즉, 태그 안이 아님을 추측)
        $pattern = '/(' . preg_quote($keyword, '/') . ')(?![^<]*>)/iu';
        return preg_replace($pattern, '<span style="background-color: #fff176; font-weight: bold; border-radius: 2px;">$1</span>', $content);
    }

	function printMemo1(){
		global $dbConn, $start, $page_total, $scale, $page, $page_scale,$page_last,$result;
        global $search_text; // 검색어 전역 변수 가져오기
		
		if($start) $n=$page_total-$start;
		else $n=$page_total;
		
        if($page_total != "0") { 
	        for($i=$start; $i<$start+$scale; $i++) {
		        if($i<$page_total) {
					 $row1=$result->fetch_assoc();
                     
                     // [하이라이트 적용]
                     $name_view = $row1['name'];
                     $content1_view = $row1['content1'];
                     $content2_view = $row1['content2'];

                     if($search_text != "") {
                         $name_view = highlight_view($name_view, $search_text);
                         $content1_view = highlight_view($content1_view, $search_text);
                         $content2_view = highlight_view($content2_view, $search_text);
                     }

					 echo "<tr>
								<td width=15% align=center height=22>Date</td>
								<td width=35% align=left>&nbsp;$row1[date]</td>
								<td width=15% align=center>Name</td>
								<td width=35% align=left>&nbsp;$name_view ($row1[register])</td>
							</tr>
							<tr><td colspan=4 height=1 bgcolor=#dcdcdc></td></tr><tr>
								<td width=15% align=center height=70>Memo 1</td>
								<td width=35% align=left valign=top><br />&nbsp;$content1_view</td>
								<td width=15% align=center>Memo 2</td>
								<td width=35% align=left valign=top>&nbsp;$content2_view</td>
							</tr>
							<tr><td colspan=4 height=25 align=right><a href=\"javascript:memo_edit('$row1[seq_no]')\">수정</a>&nbsp;||&nbsp;<a href=\"javascript:memo_del('$row1[seq_no]')\">삭제</a></td></tr>
							<tr><td colspan=4 height=2 bgcolor=#cccccc></td></tr>";
				}
			}
		} else {
			echo "<tr><td colspan=4 height=50 align=center style='padding: 20px;'>데이터가 없습니다.</td></tr>";
	    }
	}

	function pageNavigation(){
        global $page_total,$page,$start,$scale,$page_scale,$division,$page_last,$PHP_SELF,$search_mode,$search_text,$sdate;
        $Parameter_value = "search_mode=$search_mode&search_text=$search_text&sdate=$sdate";

        if($page_total>$scale) {
			if($start+1>$scale*$page_scale) {
				$pre_start=$page*$scale*$page_scale-$scale;
				echo "<a href='$PHP_SELF?start=$pre_start&$Parameter_value'>Prev </a>";
			}
			for($vj=0; $vj<$page_scale; $vj++) {
				$ln=($page * $page_scale+$vj)*$scale;
				$vk=$page*$page_scale+$vj+1;
				if($ln<$page_total) {
					if($ln!=$start) echo "<a href='$PHP_SELF?start=$ln&$Parameter_value'>$vk. </a>";
					else echo "<a href='$PHP_SELF?start=$ln&$Parameter_value'>[$vk]. </a>";
				}
			}
			if($page_total>(($page+1)*$scale*$page_scale)) {
				$n_start=($page+1)*$scale*$page_scale;
				$last_start=$page_last*$scale;
				echo "<a href='$PHP_SELF?start=$n_start&$Parameter_value'>Next </a>";
			}
        }
      }
?>
<script>
	function memo_del(no){
		if(confirm("삭제할까요?") == true) {
			location.replace('memo_list.php?mode=del&no=' + no);
		}
		else return;
	}
	
	function memo_edit(no){
		location.replace('memo_list.php?mode=edit&board_mode=edit&no=' + no);
	}

	function chk(tf){
		if(tf.s_date.value == '') {
			alert('날짜 넣으세요!');
			tf.s_date.focus();
			return false;
		}
	    return true;
	}
	
	function search_chk() {
        // 검색 조건이 하나라도 있으면 true
		var form = document.search_form;
		if(form.search_text.value == '' && form.sdate.value == '') {
			alert('검색어 또는 날짜를 입력하세요.');
			return false;
		}
		return true;
	}
  </script>

<div id="contentwrapper">
    <div class="main_content">
        <div id="jCrumbs" class="breadCrumb module">
            <ul>
                <li><a href="/"><i class="glyphicon glyphicon-home"></i></a></li>
                <li><a href="#">스케줄메모등록</a></li>
            </ul>
        </div>
        
        <!-- [검색 기능 위치 변경 및 디자인 개선] -->
        <div class="row">
            <div class="col-md-12">
                <div class="well well-sm" style="background-color: #f9f9f9; border: 1px solid #e3e3e3; border-radius: 4px; padding: 15px;">
                    <form name="search_form" method="get" action="<?=$PHP_SELF?>" class="form-inline" onsubmit="return search_chk()">
                        <div class="form-group">
                            <label for="search_date_picker" style="margin-right:5px;"><i class="glyphicon glyphicon-calendar"></i> 날짜(When)</label>
                            <input type="text" name="sdate" id="search_date_picker" value="<?=$sdate?>" class="form-control input-sm" style="width: 120px;" placeholder="YYYY-MM-DD">
                        </div>
                        
                        <div class="form-group" style="margin-left: 15px;">
                            <!-- [옵션 추가: 전체(All)] -->
                            <select name="search_mode" class="form-control input-sm">
                                <option value="all" <?=(!$search_mode || $search_mode=="all")?"selected":""?>>전체 (All)</option>
                                <option value="name" <?=($search_mode=="name")?"selected":""?>>작성자 (Name)</option>
                                <option value="content" <?=($search_mode=="content")?"selected":""?>>내용 (Content)</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <div class="input-group">
                                <input type="text" name="search_text" value="<?=$search_text?>" class="form-control input-sm" placeholder="검색어 입력">
                                <span class="input-group-btn">
                                    <button type="submit" class="btn btn-primary btn-sm"><i class="glyphicon glyphicon-search"></i> 검색</button>
                                </span>
                            </div>
                        </div>
                        
                        <?php if($search_text || $sdate) { ?>
                        <div class="form-group" style="margin-left: 5px;">
                             <a href="<?=$PHP_SELF?>" class="btn btn-default btn-sm"><i class="glyphicon glyphicon-refresh"></i> Reset</a>
                        </div>
                        <?php } ?>
                    </form>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12 col-md-12">
                <!-- 작성 폼 -->
                <form Enctype="multipart/form-data" name=board_write action=<?= $PHP_SELF ?> method=post onSubmit="return chk(this)">
                    <table id="level4" class="txt_12" width="98%" align="center" border="0" cellspacing="0" cellpadding="0">
                        <tr>
                            <td colspan="4" height="50" align="center" bgcolor="#FFFFFF"><input type="submit" value="&nbsp;메모 저장&nbsp;" class="btn btn-success"></td>
                        </tr>
                    </table>
                    <br>
                    
                    <table class="table table-striped table-bordered mediaTable">
                        <input type=hidden name=board_mode id=board_mode value="write">
                        <input type=hidden name=no value="<?= $no ?>">
                        
                        <tr bgcolor="#B0FDF9" height="28">
                            <td align="left" colspan=4 style="background-color: #d9edf7; color: #31708f; font-weight:bold;">
                                <i class="glyphicon glyphicon-pencil"></i> 스케줄 메모 정보
                            </td>
                        </tr>		
                        <tr bgcolor="#FFFFFF"> 
                            <td width="15%" height="25" align="center" bgcolor="#FBFBFB">Date</td>
                            <td width=35%>&nbsp;&nbsp;<input type=text name=s_date id='write_date_picker' size=16 class="inpubase lg form-control" style="width:150px; display:inline-block;" value="<?=$s_date?>" ></td>
                            <td width="15%" height="25" align="center" bgcolor="#FBFBFB" >Name</td>
                            <td width=35%>&nbsp;<input name="user_name" type="text" class="inpubase lg form-control" style="width:150px; display:inline-block;" size="16" value="<?= $user_dbinfo['kor_name'] ?>"></td>
                        </tr>
                        <tr bgcolor="#FFFFFF"> 
                            <td width="15%" height="25" align="center" bgcolor="#FBFBFB">Memo 1</td>
                            <td width=85% colspan=3>&nbsp;<textarea name=content1 cols=80 rows=10 class="form-control" ><?=$content1?></textarea></td>
                        </tr>
                        <tr bgcolor=#FFFFFF>
                            <td width="15%" height="25" align="center" bgcolor="#FBFBFB">Memo 2</td>
                            <td width=85% colspan=3>&nbsp;<textarea name=content2 cols=80 rows=10 class="form-control" ><?=$content2?></textarea></td>
                        </tr>
                    </table>
                </form>

                <!-- ===== DEBUG ===== -->
                <!--<div style="background:#ffffcc;border:1px dashed #cc9900;padding:8px 12px;font-family:monospace;font-size:12px;margin-bottom:8px;">
                    <b>[DEBUG]</b>
                    page_total=<?= var_export($page_total, true) ?> |
                    result_rows=<?= $result_rows ?> |
                    que=<?= htmlspecialchars($que, ENT_QUOTES, 'UTF-8') ?>
                </div>-->
                <!-- ===== /DEBUG ===== -->

                <!-- 리스트 테이블 -->
                <table width="98%" align=center border="0" cellspacing="0" cellpadding="0" class="table table-hover">
                    <thead>
                        <tr> 
                            <th height="34" bgcolor="#eeeeee" colspan=4 style="text-align:center; font-size:14px;"><strong>Memo 리스트</strong></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php echo printMemo1(); ?>
                    </tbody>
                </table>
                
                <!-- 페이징 -->
                <div style="text-align:center; margin-top:20px; margin-bottom:40px;">
                     <?php pageNavigation(); ?>
                </div>
                
            </div>
        </div>
 </div>
 <?php
		include "include/side_m.php"
?>
<script>
		$(document).ready(function () {
			// 작성용 날짜 피커
			$('#write_date_picker').datepicker({
				format: "yyyy-mm-dd",
				autoclose: true,
                todayHighlight: true
			});
            
            // [검색용 날짜 피커 추가]
            $('#search_date_picker').datepicker({
				format: "yyyy-mm-dd",
				autoclose: true,
                todayHighlight: true,
                orientation: "bottom auto"
			});
		});
</script>
<script src="ckeditor/ckeditor.js"></script>

<script>
    $(document).ready(function() {
        
        CKEDITOR.replace( 'content1', {
            extraPlugins : 'simpleuploads',
            filebrowserUploadUrl: 'upload.php',
            allowedContent : true,
            enterMode:'2',
            height : '295px',
            disallowedContent: "",
        } );
        CKEDITOR.replace( 'content2', {
            extraPlugins : 'simpleuploads',
            filebrowserUploadUrl: 'upload.php',
            allowedContent : true,
            enterMode:'2',
            height : '295px',
            disallowedContent: "",
        } )
    });
</script>