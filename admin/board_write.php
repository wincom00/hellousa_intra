<?php
    include "include/header.php";
	//include "include/inc_base.php";
	if($_COOKIE[MEMLOGIN_ADMIN_HELLO] !="")
	{
	} else {
		
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}
	/*
    if (!hasMenuAccess($division, $pdx, $sub)) {
    	 $goUrl_1 = "index.php";
		   Misc::jvAlert("권한이 있는 메뉴가 아닙니다. 확인후 사용하세요.!!","");
		 	 echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
			 exit;
    }
	*/
	
	if ($table_id=='01') {
		$cap = "문의게시판";
	} else if ($table_id=='02') {
		$cap = "회계문의";
	} else if ($table_id=='15') {
		$cap = "사내공지사항";
	} else if ($table_id=='70') {
		$cap = "갤러리";
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
								  <input type=hidden name=board_mode value="write">
								  <input type=hidden name=table_id value="<?= $table_id ?>">
								  <input type=hidden name=division value="<?= $division ?>">
								  <input type=hidden name=pdx value="<?= $pdx ?>">
								  <input type=hidden name=sub value="<?= $sub ?>">
								  <input type=hidden name=user_id value="<?= $user_dbinfo[userid] ?>">
								 <table class="table table-striped table-bordered mediaTable" width="100%" >
										<tr bgcolor="#FFFFFF"> 
										  <td width="100" height="25" align="center" bgcolor="#FBFBFB">게시판이름</td>
										  <td  >&nbsp;&nbsp;<?= $board_config[board_name] ?></td>
										</tr>
										<?php if (($table_id == "70")){ ?>
										<tr bgcolor="#FFFFFF"> 
										  <td width="100" height="25" align="center" bgcolor="#FBFBFB">카테고리</td>
										  <td> <select class="inpubase md " name="cc" id="cc"><option value="">선택</option>
										  <?php
										
											$cr_qry1 = "select * from code_base where lvcode1 = 'G01' && lvcode2 <> '00'  order by lvcode2 asc";
											$cr_rst1 = $dbConn->query($cr_qry1);

											$cr_num1 = 1;

											while($cr_row1 = $cr_rst1->fetch_assoc()):

												//$area_name = codebasename($cr_row1[lvcode1]);
												$tour_value2 = $cr_row1[lvcode2];
											
										?>
												
												<option value="<?=$tour_value2?>"><?=$cr_row1[comment]?></option>
										<?php
											
											endwhile;
										?>
										    </select>
										  

										  </td>
										</tr>
										<?php } ?>
										<tr bgcolor="#FFFFFF"> 
										  <td width="100" height="25" align="center" bgcolor="#FBFBFB">작성자</td>
										  <td><input name="user_name" type="text" class="form-control" style="width:200px;" value="<?= $user_dbinfo[kor_name] ?>[동부투어]"></td>
										</tr>
										<tr bgcolor="#FFFFFF"> 
										  <td height="25" align="center" bgcolor="#FBFBFB">제목 </td>
										  <td  align="left"><input name="title" type="text" class="form-control" value=""></td>
											 
										</tr>
										<tr bgcolor="#FFFFFF"> 
										  <td height="25" align="center" bgcolor="#FBFBFB">내용 </td>
										  <td  align="center"><textarea name="FCKeditor1" id ="FCKeditor1"  class="form-control js-specialBenefit js-ckEditor" > </textarea>
											</td>
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
												<td><input name="userfile2" type="file" class="form-control" style="width:250px;"></td>
											  </tr>
											</table></td>
										</tr>
										<?php if (($table_id == "70")){ ?>
										
										<tr bgcolor="#FFFFFF"> 
										  <td height="25" align="center" bgcolor="#FBFBFB">첨부파일 </td>
										  <td colspan="3" align="left"><table width="98%" border="0" cellspacing="0" cellpadding="0">
											  <tr> 
												<td><input name="userfile3" type="file" class="form-control" style="width:250px;"></td>
											  </tr>
											</table></td>
										</tr>

										
										<tr bgcolor="#FFFFFF"> 
										  <td height="25" align="center" bgcolor="#FBFBFB">첨부파일 </td>
										  <td colspan="3" align="left"><table width="98%" border="0" cellspacing="0" cellpadding="0">
											  <tr> 
												<td><input name="userfile4" type="file" class="form-control" style="width:250px;"></td>
											  </tr>
											</table></td>
										</tr>

										
										<tr bgcolor="#FFFFFF"> 
										  <td height="25" align="center" bgcolor="#FBFBFB">첨부파일 </td>
										  <td colspan="3" align="left"><table width="98%" border="0" cellspacing="0" cellpadding="0">
											  <tr> 
												<td><input name="userfile5" type="file" class="form-control" style="width:250px;"></td>
											  </tr>
											</table></td>
										</tr>

										
										<tr bgcolor="#FFFFFF"> 
										  <td height="25" align="center" bgcolor="#FBFBFB">첨부파일 </td>
										  <td colspan="3" align="left"><table width="98%" border="0" cellspacing="0" cellpadding="0">
											  <tr> 
												<td><input name="userfile6" type="file" class="form-control" style="width:250px;"></td>
											  </tr>
											</table></td>
										</tr>

										
										<tr bgcolor="#FFFFFF"> 
										  <td height="25" align="center" bgcolor="#FBFBFB">첨부파일 </td>
										  <td colspan="3" align="left"><table width="98%" border="0" cellspacing="0" cellpadding="0">
											  <tr> 
												<td><input name="userfile7" type="file" class="form-control" style="width:250px;"></td>
											  </tr>
											</table></td>
										</tr>

										
										<tr bgcolor="#FFFFFF"> 
										  <td height="25" align="center" bgcolor="#FBFBFB">첨부파일 </td>
										  <td colspan="3" align="left"><table width="98%" border="0" cellspacing="0" cellpadding="0">
											  <tr> 
												<td><input name="userfile8" type="file" class="form-control" style="width:250px;"></td>
											  </tr>
											</table></td>
										</tr>

										
										<tr bgcolor="#FFFFFF"> 
										  <td height="25" align="center" bgcolor="#FBFBFB">첨부파일 </td>
										  <td colspan="3" align="left"><table width="98%" border="0" cellspacing="0" cellpadding="0">
											  <tr> 
												<td><input name="userfile9" type="file" class="form-control" style="width:250px;"></td>
											  </tr>
											</table></td>
										</tr>

										
										<tr bgcolor="#FFFFFF"> 
										  <td height="25" align="center" bgcolor="#FBFBFB">첨부파일 </td>
										  <td colspan="3" align="left"><table width="98%" border="0" cellspacing="0" cellpadding="0">
											  <tr> 
												<td><input name="userfile10" type="file" class="form-control" style="width:250px;"></td>
											  </tr>
											</table></td>
										</tr>
										<?php } ?>
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
    <!--<script src="ckeditor/ckeditor.js"></script>--->
    <script src="ckeditor/ckeditor.js"></script>

    <script>
		$(document).ready(function () {
				$.ajaxSetup({async:false});
				pt1.initBoardForm();
				CKEDITOR.on('instanceReady', function (ev) {
					ev.editor.dataProcessor.htmlFilter.addRules(
					{
						elements:
						{
							$: function (element) {
								// check for the tag name
								if (element.name == 'img') {
									var style = element.attributes.style;
									element.addClass("img-responsive");
								   // remove style tag if it exists
									if (style) {
										delete element.attributes.style;
									}
								}

								// return element without style attribute
								return element;
							}
						}
					});
				}); 
				
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

      
      