
<?php
    include "include/header.php";
	
	if ($_COOKIE[MEMLOGIN_ADMIN_HELLO] !="") {
	} else {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}

     if (!hasMenuAccess($division, $pdx, $sub)) {
		
		Misc::jvAlert("권한이 있는 메뉴가 아닙니다. 확인후 사용하세요.!!","");
		exit;
    }

    if ($Mode == "del") {
		$qry1 = "delete from html_page where seq_no= '$id'";
		$rst1 = $dbConn->query($qry1);
	}

	if ($mode == "save") {
		$FCKeditor1 = addslashes($body);

		$qry1 = "update html_page set content = '".$FCKeditor1."' where id = '$id'";
		$rst1 = $dbConn->query($qry1);

		if ($rst1) {
			echo "<meta http-equiv='refresh' content='0; url=./page_regi.php?division=9&pdx=1&sub=10&Mode=modify&id=$id'>";
			exit;
		}					
	}

	if ($Mode == "modify") {
		$qry1 = "select * from html_page where id = '$id'";
		$rst1 = $dbConn->query($qry1);
		$row1 = $rst1->fetch_assoc();
	}


?>
     
<div id="contentwrapper">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb module">
				<ul>
					<li>
						<a href="/"><i class="glyphicon glyphicon-home"></i></a>
					</li>
					<li>
						<a href="#">컨텐츠 페이지 편집</a>
					</li>
					<li>
						페이지 편집
					</li>
				</ul>
			</div>
			
		<div class="row">
				<div class="col-sm-12 col-md-12">
						  <table id="productDetailForm" class="table table-bordered table-condensed gridSixteen reserveTable formDetail js-base" width="98%" align=center border="0" cellspacing="1" bgcolor=#cccccc cellpadding="0">
							<tr bgcolor=#FFFFFF>
								<td width=33% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> <a href=<?= $PHP_SELF ?>?division=9&pdx=1&sub=10&Mode=modify&id=ab_1>회사소개</a></td>
								<td width=33% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> <a href=<?= $PHP_SELF ?>?division=9&pdx=1&sub=10&Mode=modify&id=pp_1>개인정보처리방침</a></td>
								
								<td width=33% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> <a href=<?= $PHP_SELF ?>?division=9&pdx=1&sub=10&Mode=modify&id=use_1>이용약관</a></td>
								
							</tr>
							<tr bgcolor=#FFFFFF>
							    
								<td width=33% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> <a href=<?= $PHP_SELF ?>?division=9&pdx=1&sub=10&Mode=modify&id=cp_1>여행약관</a></td>
								<td width=33% height=30 class="malgun" >&nbsp;<i class="glyphicon glyphicon-folder-close"></i> <a href=<?= $PHP_SELF ?>?division=9&pdx=1&sub=10&Mode=modify&id=qq_1>이메일수집거부</a></td>
								<td width=33% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> <a href=<?= $PHP_SELF ?>?division=9&pdx=1&sub=10&Mode=modify&id=in_1>오시는길</a></td>
								<!--
								<td width=33% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> <a href=<?= $PHP_SELF ?>?division=9&pdx=1&sub=10&Mode=modify&id=use_1>이용약관</a></td>
								<td width=33% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> <a href=<?= $PHP_SELF ?>?division=9&pdx=1&sub=10&Mode=modify&id=use_1>제휴안내</a></td>-->

							</tr>
							<tr bgcolor=#FFFFFF>
							    
								<td width=33% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> <a href=<?= $PHP_SELF ?>?division=9&pdx=1&sub=10&Mode=modify&id=t_1>비자정보</a></td>
								<td width=33% height=30 class="malgun" >&nbsp;<i class="glyphicon glyphicon-folder-close"></i> <a href=<?= $PHP_SELF ?>?division=9&pdx=1&sub=10&Mode=modify&id=t_2>안전정보</a></td>
								<td width=33% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> <a href=<?= $PHP_SELF ?>?division=9&pdx=1&sub=10&Mode=modify&id=t_3>여행자보험</a></td>
								<!--
								<td width=33% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> <a href=<?= $PHP_SELF ?>?division=9&pdx=1&sub=10&Mode=modify&id=use_1>이용약관</a></td>
								<td width=33% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> <a href=<?= $PHP_SELF ?>?division=9&pdx=1&sub=10&Mode=modify&id=use_1>제휴안내</a></td>-->

							</tr>
							<tr bgcolor=#FFFFFF>
							    
								<td width=33% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> <a href=<?= $PHP_SELF ?>?division=9&pdx=1&sub=10&Mode=modify&id=t_4>공항정보</a></td>
								<td width=33% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> <a href=<?= $PHP_SELF ?>?division=9&pdx=1&sub=10&Mode=modify&id=t_5>코로나19정보</a></td>
								<td width=33% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> <a href=<?= $PHP_SELF ?>?division=9&pdx=1&sub=10&Mode=modify&id=t_6>유트브스토리</a></td>
								<!--
								<td width=33% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> <a href=<?= $PHP_SELF ?>?division=9&pdx=1&sub=10&Mode=modify&id=use_1>이용약관</a></td>
								<td width=33% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> <a href=<?= $PHP_SELF ?>?division=9&pdx=1&sub=10&Mode=modify&id=use_1>제휴안내</a></td>-->

							</tr>
							<tr bgcolor=#FFFFFF>
							    
								<td width=33% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> <a href=<?= $PHP_SELF ?>?division=9&pdx=1&sub=10&Mode=modify&id=r_1>여행규정(홈페이지영수증)</a></td>
								<td width=33% height=30 class="malgun" >&nbsp;<i class="glyphicon glyphicon-folder-close"></i> <a href=<?= $PHP_SELF ?>?division=9&pdx=1&sub=10&Mode=modify&id=r_2>담당자(홈페이지영수증)</a></td>
								<td width=33% height=30 class="malgun" >&nbsp;<i class="glyphicon glyphicon-folder-close"></i> <a href=<?= $PHP_SELF ?>?division=9&pdx=1&sub=10&Mode=modify&id=dbt_lh>고객이메일(영수증)</a></td>
								
								<!--
								<td width=33% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> <a href=<?= $PHP_SELF ?>?division=9&pdx=1&sub=10&Mode=modify&id=use_1>이용약관</a></td>
								<td width=33% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> <a href=<?= $PHP_SELF ?>?division=9&pdx=1&sub=10&Mode=modify&id=use_1>제휴안내</a></td>-->

							</tr>

							<tr bgcolor=#FFFFFF>
							    
								<td width=33% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> <a href=<?= $PHP_SELF ?>?division=9&pdx=1&sub=10&Mode=modify&id=returnc>고객영수증 취소/환불(로컬)</a></td>
								<td width=33% height=30 class="malgun" >&nbsp;<i class="glyphicon glyphicon-folder-close"></i> <a href=<?= $PHP_SELF ?>?division=9&pdx=1&sub=10&Mode=modify&id=returno>고객영수증 취소/환불(아웃바운드)</a></td>
								<td width=33% height=30 class="malgun" >&nbsp;<i class="glyphicon glyphicon-folder-close"></i> <a href=<?= $PHP_SELF ?>?division=9&pdx=1&sub=10&Mode=modify&id=alert>확정서주의사항/결제정보안내</a></td>
								
								<!--
								<td width=33% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> <a href=<?= $PHP_SELF ?>?division=9&pdx=1&sub=10&Mode=modify&id=use_1>이용약관</a></td>
								<td width=33% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> <a href=<?= $PHP_SELF ?>?division=9&pdx=1&sub=10&Mode=modify&id=use_1>제휴안내</a></td>-->

							</tr>
							<tr bgcolor=#FFFFFF>
							    
								<td width=33% height=30 class="malgun" colspan='3'>&nbsp;<i class="glyphicon glyphicon-folder-close"></i> <a href=<?= $PHP_SELF ?>?division=9&pdx=1&sub=10&Mode=modify&id=alert_g>상품-일반주의사항</a></td>
								
								<!--
								<td width=33% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> <a href=<?= $PHP_SELF ?>?division=9&pdx=1&sub=10&Mode=modify&id=use_1>이용약관</a></td>
								<td width=33% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> <a href=<?= $PHP_SELF ?>?division=9&pdx=1&sub=10&Mode=modify&id=use_1>제휴안내</a></td>-->

							</tr>
							
							<!--<tr bgcolor=#FFFFFF>
								<td height=30 class="malgun">&nbsp;<img src='../images/page_white_text.png' align=absmiddle> <a href=<?= $PHP_SELF ?>?division=6&Mode=modify&id=privacy>개인정보 취급방침</a></td>
								<td class="malgun">&nbsp;<img src='../images/page_white_text.png' align=absmiddle> <a href=<?= $PHP_SELF ?>?division=6&Mode=modify&id=join>제휴문의</a></td>
								<td class="malgun">&nbsp;<img src='../images/page_white_text.png' align=absmiddle> <a href=<?= $PHP_SELF ?>?division=6&Mode=modify&id=email>이메일 주소수집거부</a></td>
							</tr> -->
							
							
						  </table>
						  <br><br>
						  <table id="level4" class="table table-bordered table-condensed ptTable formDetail" width="98%" align=center border="0" cellspacing="1" bgcolor=#cccccc cellpadding="0">
						  <tr>
							<td height=40 bgcolor=#FFFFFF class="malgun">&nbsp;&nbsp;상단 페이지중 편집하실 메뉴를 선택하세요. </td>
						  </tr>
						  
						  <form action=<?= $PHP_SELF ?>?division=9&pdx=1&sub=10 method=post onSubmit="return chk(this)">
						  <input type=hidden name=mode value="save">
						  <input type=hidden name=division value="<?= $division ?>">
						  <input type=hidden name=extra_mode value="<?= $extra_mode ?>">
						  <input type=hidden name=id value="<?= $id ?>">
							<tr bgcolor=#f9f9f9 height=28>
								<td colspan=4 bgcolor=#FFFFFF class="malgun">
									
										<textarea class="form-control js-specialBenefit js-ckEditor" name="body" id ="body" ><?= $row1[content] ?></textarea>
									
								</td>
							</tr>

							<tr>
								<td colspan=4 height=35 bgcolor=#FFFFFF align=center class="malgun"><input type=submit value="저장" class="btn btn-primary btn-sm"></td>
							</tr>
							</form>
						  </table>
						  <br>
					
					  
				</div><!-- -->
		</div>                
		</div>
	  </div>

	</div>

    <?php
		include "include/side_m.php"
	?>
	<script src="ckeditor/ckeditor.js"></script>
<!--	<script src="//cdn.ckeditor.com/4.11.0/full/ckeditor.js"></script>-->
     <script>
         $(document).ready(function() {
				$.ajaxSetup({async:false});
				//pt.initProductDetailForm()
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
									var height = element.attributes.height;
									if (height) {
										delete element.attributes.height;
									}
									
									var width = element.attributes.width;
									if (width) {
										delete element.attributes.width;
									}
									element.attributes.style="margin-left:auto;margin-right:auto;";

								}

								// return element without style attribute
								return element;
							}
						}
					});
				}); 
				
				
				CKEDITOR.replace( 'body', {
					extraPlugins : 'simpleuploads',
					filebrowserUploadUrl: 'upload.php',
					allowedContent : true,
					enterMode:'2',
					height : '495px',
					disallowedContent: "",
					
					
				} )
				
				
	     });

	 </script>

    </body>
</html>

      
      