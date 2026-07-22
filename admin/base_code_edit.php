
<?php
    include "include/header.php";
	
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
	if($mode == "save")
	{
		$file_name["image"] = "";
		if ($_FILES["image"]["tmp_name"] <> "") $file_name["image"] = file_save($_FILES["image"], "upload/");

		$image_qry = "";
		if ($file_name["image"] <> "" || $img_del == "1") $image_qry = " image = '" . $dbConn->real_escape_string($file_name["image"]) . "', ";

        $comment_e = $dbConn->real_escape_string($comment);

		$qry1 = "UPDATE code_base SET lvcode1 = '$lvcode1_value',
									lvcode2 = '$lvcode2_value',
									lvcode3 = '$lvcode3_value',
									lvcode4 = '$lvcode4_value',
									lvcode5 = '$lvcode5_value',
									comment = '$comment_e',
									$image_qry
									desc_comm = '$desc',
									active = '$active',
									modified = now()
				WHERE lvcode1 = '$lvcode1' && lvcode2 = '$lvcode2' && lvcode3 = '$lvcode3' && lvcode4 = '$lvcode4' ";
		$rst1 = $dbConn->query($qry1);
    	if($rst1)
		{
			Misc::jvAlert("저장완료!","location.replace('base_code.php?division=1&pdx=1&sub=1&lvcode1=$lvcode1&lvcode2=$lvcode2&lvcode3=$lvcode3&lvcode4=$lvcode4')");
			exit;
		}
		else
		{
			Misc::jvAlert("에러!","history.go(-1)");
			exit;
		}

		

	}
	$qry1 = "SELECT * FROM code_base WHERE lvcode1 = '$lvcode1' && lvcode2 = '$lvcode2' && lvcode3 = '$lvcode3' && lvcode4 = '$lvcode4'";
	$rst1 = $dbConn->query($qry1);
	$row1 = $rst1->fetch_assoc();
?>
     
<div id="contentwrapper">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb module">
				<ul>
					<li>
						<a href="/admin"><i class="glyphicon glyphicon-home"></i></a>
					</li>
					<li>
						<a href="#">기초관리</a>
					</li>
					<li>
						<a href="#">기초관리</a>
					</li>
					<li>
						기초코드등록
					</li>
				</ul>
			</div>	
			<div class="row">
				<div class="col-sm-12 col-md-12">
					<form action="base_code_edit.php?division=1&pdx=1&sub=1" enctype="multipart/form-data" name="base_code" id="base_code" method="post">
							  <input type="hidden" name="mode" value="save">
							  <input type="hidden" name="lvcode1" id="lvcode1" value="<?= $lvcode1 ?>">
							  <input type="hidden" name="lvcode2" value="<?= $lvcode2 ?>">
							  <input type="hidden" name="lvcode3" value="<?= $lvcode3 ?>">
							  <input type="hidden" name="lvcode4" value="<?= $lvcode4 ?>">
							  <input type="hidden" name="lvcode5" value="<?= $lvcode5 ?>">
							   <table class="table table-striped table-advance table-hover">
									   <tbody>
										  <tr>
											<th width=9% align="center">분류</th>
											<th width=9% align="center">대분류</th>
											<th width=9% align="center">중분류</th>
											<th width=9% align="center">세분류</th>
											<th width='*' align="center">코드정의</th>
											<th width=9% align="center">이미지</th>
											<th width=10% align="center">사용유무</th>
											<th width=8% align="center"><i class="glyphicon glyphicon-cog"></i>Action</th>
											
										  </tr>
										  <tr>
											<td><input type="text" id="lvcode1_value" name="lvcode1_value" class="form-control" value="<?= $lvcode1 ?>"/></td>
											<td><input type="text" id="lvcode2_value" name="lvcode2_value" class="form-control" value="<?= $lvcode2 ?>"/></td>
											<td><input type="text" id="lvcode3_value" name="lvcode3_value" class="form-control" value="<?= $lvcode3 ?>"/></td>
											<td><input type="text" id="lvcode4_value" name="lvcode4_value" class="form-control" value="<?= $lvcode4 ?>"/></td>
											<td><input type="text"  id="comment" name="comment" style='width : 100%;' class="form-control" placeholder="코드정의" value="<?= $row1[comment] ?>"/></td>
											<td>&nbsp;<input type="radio" name="active" value="yes" <? if ($row1[active] == "yes") echo "checked"; ?>>Active<br>&nbsp;<input type="radio" name="active" value="no" <? if ($row1[active] == "no") echo "checked"; ?>>Inactive</td>
											<td>&nbsp;<? if($row1[image]): ?><img src="upload/<?= $row1[image] ?>" >&nbsp;<input type="checkbox" id="img_del" name="img_del" value="1"> 삭제이미지<br><? else: ?>&nbsp이미지없음<? endif; ?>&nbsp;<input type="file" id="image" name="image" size="30" class="form_box"></td>
										    <td>&nbsp;<input type="submit" value="저장" class='btn btn-primary btn-xs btnatt'></td>
											
											
										</tr>          
									   </tbody>
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
    <script src="js/jquery.min.js"></script>
    <script src="js/jquery-migrate.min.js"></script>
    <script src="lib/jquery-ui/jquery-ui-1.10.0.custom.min.js"></script>
    <!-- touch events for jquery ui-->
	<script src="js/forms/jquery.ui.touch-punch.min.js"></script>
    <!-- easing plugin -->
	<script src="js/jquery.easing.1.3.min.js"></script>
    <!-- smart resize event -->
	<script src="js/jquery.debouncedresize.min.js"></script>
    <!-- js cookie plugin -->
	<script src="js/jquery_cookie_min.js"></script>
    <!-- main bootstrap js -->
	<script src="bootstrap/js/bootstrap.min.js"></script>
    <!-- bootstrap plugins -->
	<script src="js/bootstrap.plugins.min.js"></script>
	<!-- typeahead -->
	<script src="lib/typeahead/typeahead.min.js"></script>
    <!-- code prettifier -->
	<script src="lib/google-code-prettify/prettify.min.js"></script>
    <!-- sticky messages -->
	<script src="lib/sticky/sticky.min.js"></script>
    <!-- lightbox -->
	<script src="lib/colorbox/jquery.colorbox.min.js"></script>
    <!-- jBreadcrumbs -->
	<script src="lib/jBreadcrumbs/js/jquery.jBreadCrumb.1.1.min.js"></script>
	<!-- hidden elements width/height -->
	<script src="js/jquery.actual.min.js"></script>
	<!-- custom scrollbar -->
	<script src="lib/slimScroll/jquery.slimscroll.js"></script>
	<!-- fix for ios orientation change -->
	<script src="js/ios-orientationchange-fix.js"></script>
	<!-- to top -->
	<script src="lib/UItoTop/jquery.ui.totop.min.js"></script>
	<!-- mobile nav -->
	<script src="js/selectNav.js"></script>
    <!-- moment.js date library -->
    <script src="lib/moment/moment.min.js"></script>

	<!-- common functions -->
	<script src="js/pages/gebo_common.js"></script>



    </body>
</html>

      
      