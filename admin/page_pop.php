
<?php
    include "include/header.php";
	//include "include/inc_base.php";
	if ($_COOKIE['MEMLOGIN_ADMIN_HELLO'] !="") {
	} else {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}

     if (!hasMenuAccess($division, $pdx, $sub)) {
		
		Misc::jvAlert("권한이 있는 메뉴가 아닙니다. 확인후 사용하세요.!!","");
		exit;
    }

   if($mode == "save")
	{
		$qry1 = "update banner_page set content = '$pop', visible = '$visibility', test = '$test' where area = 'popup'";
			$rst1 = $dbConn->query($qry1);

		
	}
   
	$qry1 = "select * from banner_page where area = 'popup' ";
	$rst1 = $dbConn->query($qry1);
	$row1 = $rst1->fetch_assoc();

	$popupvisible1 = $row1['visible'];
	$testmode = $row1['test'];

?>
     
<div id="contentwrapper">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb module">
				<ul>
					<li>
						<a href="/"><i class="glyphicon glyphicon-home"></i></a>
					</li>
					<li>
						<a href="#">홈페이지관련설정</a>
					</li>
					<li>
						팝업관리
					</li>
				</ul>
			</div>
			
		<div class="row">
				<div class="col-sm-12 col-md-12">
						  
						  <table id="level4" class="table table-bordered table-condensed ptTable formDetail " width="100%" align=center border="0" cellspacing="1" bgcolor=#cccccc cellpadding="0">
						  
						  <form action=<?= $PHP_SELF ?>?division=9&pdx=2&sub=15 method=post onSubmit="return chk(this)">
						  <input type=hidden name=mode value="save">
						  
						    <tr bgcolor=#f9f9f9 >
								
								<td align=left width=45% >&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=checkbox name="visibility" value="1" <?= $popupvisible1 ? "checked" : "" ?>>팝업창 보기</td>
							</tr>
							<tr bgcolor=#f9f9f9 height=28>
								<td bgcolor=#FFFFFF ><textarea name="pop" id ="pop" class="form_box"><?= stripslashes((string)$row1[content]) ?></textarea>
								
								</td>
							</tr>
							<tr>
								<td  height=35 bgcolor=#FFFFFF align=center class="malgun"><input type=submit value="저장" class="btn btn-primary btn-sm"></td>
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
	<!--<script src="ckeditor_full/ckeditor.js"></script>-->
	<script src="ckeditor/ckeditor.js"></script>
     <script>
         $(document).ready(function() {
				$.ajaxSetup({async:false});
				///pt.initProductDetailForm()
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
				CKEDITOR.replace( 'pop', {
					extraPlugins : 'simpleuploads',
					filebrowserUploadUrl: 'upload.php',
					allowedContent : true,
					enterMode:'2',
					height : '595px',
					disallowedContent: "",
					
				} );
				
				
	     });
		
	 </script>
	 
	 

    </body>
</html>

      
      