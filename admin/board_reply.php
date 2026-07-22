<?php
    include "include/header.php";
	//include "include/inc_base.php";
	if($_COOKIE[MEMLOGIN_ADMIN_HELLO] !="")
	{
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
	
	
	if ($table_id=='01') {
		$cap = "문의게시판";
	} else if ($table_id=='02') {
		$cap = "회계문의";
	} else if ($table_id=='15') {
		$cap = "사내공지사항";
	} else if ($table_id=='25') {
		$cap = "상품공지사항";
	} else if ($table_id=='30') {
		$cap = "자료실";
	} else {
		$cap = "";
	}

	include "inc_board.php";
?>
     
<div id="contentwrapper">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb module">
				<ul>
					<li>
						<a href="/"><i class="glyphicon glyphicon-home"></i></a>
					</li>
					<li>
						<a href="#">게시판관리</a>
					</li>
					
					<li>
						<?=$cap?>
					</li>
				</ul>
			</div>
			
		<div class="row">
				<div class="col-sm-12 col-md-12">
					   <form Enctype="multipart/form-data" name=board_write id=board_write action=<?= $PHP_SELF ?> method=post onSubmit="return chk(this)">
								  <input type=hidden name=board_mode value="reply">
								  <input type=hidden name=table_id value="<?= $table_id ?>">
								  <input type=hidden name=division value="<?= $division ?>">
								  <input type=hidden name=pdx value="<?= $pdx ?>">
								  <input type=hidden name=sub value="<?= $sub ?>">
								  <input type=hidden name=no value="<?= $no ?>">
								  <input type=hidden name=mail value="<?= $mail ?>">
								  <input type=hidden name=start value="<?= $start ?>">
								  <input type=hidden name=user_id value="<?= $user_dbinfo[userid] ?>">
								  <input type=hidden name=thread value="<?= $board_row2[thread] ?>">
	                              <input type=hidden name=fid value="<?= $board_row2[fid] ?>">
								  <input type=hidden name=passwd value="<?= $board_row2[passwd] ?>">
								 <table class="table table-striped table-bordered mediaTable" width="100%" >
										<tr bgcolor="#FFFFFF"> 
										  <td width="100" height="25" align="center" bgcolor="#FBFBFB">게시판이름</td>
										  <td  >&nbsp;&nbsp;<?= $board_config[board_name] ?></td>
										</tr>
										<tr bgcolor="#FFFFFF"> 
										  <td width="100" height="25" align="center" bgcolor="#FBFBFB">작성자</td>
										  <td><input name="user_name" type="text" class="form-control" style="width:200px;" value="<?= $user_dbinfo[kor_name] ?>"></td>
										</tr>
										<tr bgcolor="#FFFFFF"> 
										  <td height="25" align="center" bgcolor="#FBFBFB">제목 </td>
										  <td  align="left"><input name="title" type="text" class="form-control" value="RE : <?= $board_row2[title] ?>"></td>
											 
										</tr>
										<tr bgcolor="#FFFFFF"> 
										  <td height="25" align="center" bgcolor="#FBFBFB">내용 </td>
										  <td  align="center"><textarea name="FCKeditor1" id ="FCKeditor1"  class="form-control js-specialBenefit js-ckEditor" ><?=$board_row2[content]?><br />==============================<br /></textarea>
											</td>
										</tr>
										<tr bgcolor="#FFFFFF"> 
										  <td height="25" align="center" bgcolor="#FBFBFB">첨부파일 </td>
										  <td colspan="3" align="left"><table width="98%" border="0" cellspacing="0" cellpadding="0">
											  <tr> 
												<td>현재 파일 : <?= $board_row2[userfile1] ?>&nbsp;<input type=checkbox name=photo_del1 value="1">(※ 첨부파일 삭제)</td>
											  </tr>
											</table></td>
										</tr>
										<tr bgcolor="#FFFFFF"> 
										  <td height="25" align="center" bgcolor="#FBFBFB">첨부파일 </td>
										  <td colspan="3" align="left"><table width="98%" border="0" cellspacing="0" cellpadding="0">
											  <tr> 
												<td><input name="userfile1" type="file" class="form-control" style="width:250px;" ></td>
											  </tr>
											</table></td>
										</tr>
										
										<tr bgcolor="#FFFFFF"> 
										  <td height="25" align="center" bgcolor="#FBFBFB">첨부파일 </td>
										  <td colspan="3" align="left"><table width="98%" border="0" cellspacing="0" cellpadding="0">
											  <tr> 
												<td>현재 파일 : <?= $board_row2[userfile2] ?>&nbsp;<input type=checkbox name=photo_del2 value="1">(※ 첨부파일 삭제)</td>
											  </tr>
											</table></td>
										</tr>
										<tr bgcolor="#FFFFFF"> 
										  <td height="25" align="center" bgcolor="#FBFBFB">첨부파일 </td>
										  <td colspan="3" align="left"><table width="98%" border="0" cellspacing="0" cellpadding="0">
											  <tr> 
												<td><input name="userfile2" type="file" class="form-control" style="width:250px;"></td>
											  </tr>
											</table></td>
										</tr>
										<tr> 
										       <td height="25" align="center" colspan="32 bgcolor="#FBFBFB"><input type=submit class="btn btn-primary btn-sm" value='저장' > </td>
	
									    </tr>
								 </table>
					   </form>
				</div><!-- -->
		</div>                
		</div>
	  </div>

	</div>

    <?php
		include "include/side_m.php"
	?>
    <!--<script src="//cdn.ckeditor.com/4.11.0/full/ckeditor.js"></script>-->
    <script src="ckeditor/ckeditor.js"></script>
    <script>
		$(document).ready(function () {
				$.ajaxSetup({async:false});
				pt1.initBoardForm();
				
		});
		function chk(tf){
				
				if(!tf.user_name.value)
				{
					alert('작성자명이 빠졌습니다.');
					tf.user_name.focus();
					return false;
				}
				if(!tf.title.value)
				{
					alert('제목이 빠졌습니다.');
					tf.title.focus();
					return false;
				}	
			    return true;
	   }   
		
		
	</script>


    </body>
</html>

      
      