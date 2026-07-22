
<?php
    include "include/header.php";
	//include "include/inc_base.php";
	if($_COOKIE[MEMLOGIN_ADMIN_HELLO] !="")
	{
	} else {
		
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}
    /*if (!hasMenuAccess($division, $pdx, $sub)) {
    	 $goUrl_1 = "index.php";
		   Misc::jvAlert("권한이 있는 메뉴가 아닙니다. 확인후 사용하세요.!!","");
		 	 echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
			 exit;
    }
	*/
    if ($mode == "save") {
			 $qry1 = "delete  from base_opt where opt_code='$pcode'";
			 $rst1 = $dbConn->query($qry1);
				
			  for ($i=0;$i<count($scode);$i++) {
						if ($i == 0) {
							$parent = "M";
						} else {
							$parent = "S";
						}
						$qry1 = "insert into base_opt 
													(
													opt_m,
													opt_code, 
													opt_name, 
													opt_time,
													opt_1desc,
													opt_price,
													wdate
													)
													values
													( 
													'$parent', 
													'$scode', 
													'".mysqli_real_escape_string($dbConn,$stname)."',  
													'0', 
													'$desc1', 
													'$price', 
													now()
													);
												";
						
						
						 $rst1 = $dbConn->query($qry1);
			 }
			 
			 $goUrl_1 = "base_opt.php?division=$division&pdx=$pdx&sub=$sub";
			 echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
		  

	} 
	$v_info = getinfo_dbopt_bycode($pcode);


?>
<script src="ckeditor/ckeditor.js"></script>
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
						<a href="#">기초코드관리</a>
					</li>
					<li>
						옵션등록
					</li>
				</ul>
			</div>
			
		<div class="row">
				<div class="col-sm-12 col-md-12">
					  <form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>"  name="base_opt" id="base_opt" method="post">
			            <input type=hidden name=mode value="save">
						<input type=hidden name=pcode value="<?= $pcode ?>">
						
						
						<table class="table table-striped table-bordered table-condensed">
						    <tbody>
								<tr>
										<td colspan=4 height=35 bgcolor=#FFFFFF class="titletd" style="vertical-align: middle;"><input type=submit value="저장" class="btn btn-primary btn-sm"></td>
									</tr> 
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% class="titletd">옵션명</td>
										<td  bgcolor=#FFFFFF>&nbsp;<input type=text name=stname  class="inpubase lg" value="<?= $v_info[opt_name] ?>"> </td>
										
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% class="titletd" style="vertical-align: middle;">옵션코드</td>
										<td  bgcolor=#FFFFFF>&nbsp;<input type=text name=scode  class="inpubase md" value="<?= $v_info[opt_code] ?>"> </td>
										
									</tr>
									
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% class="titletd" style="vertical-align: middle;">한줄설명</td>
										<td  bgcolor=#FFFFFF>&nbsp;<input type=text name=desc1  class="inpubase lg" value="<?= $v_info[opt_1desc] ?>"></td>
										
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% class="titletd" style="vertical-align: middle;">가격</td>
										<td  bgcolor=#FFFFFF>&nbsp;$<input type=text name=price  class="inpubase md" value="<?= $v_info[opt_price] ?>"></td>
										
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
    <script>
	   
         $(document).ready(function() {
				//* bootstrap timeopter
		        paran_timeopter.init();
				$('.addtime').click(function(e) {
					
					 var tbl = $("#sTime");
					 var sHtml = "";
					 sHtml = "<tr > "+
									   "  <td class='bootstrap-timeopter'>"+
										"	 &nbsp; <input type=text name='ptp_2[]'  id='ptp_2' class='inpubase sm1' >&nbsp;"+
										"	  <button type='button' class='btn btn-danger btn-xs delBtn'>삭제</button>"+
										"  </td>"+
										"</tr><br /> ";
					 tbl.append(sHtml);
					
					$("input[name='ptp_2[]']").timeopter({
						defaultTime: 'current',
						minuteStep: 1,
						disableFocus: true,
						template: 'dropdown'
				     });
					
				});
				$('#sTime').on('click', '.delBtn', function() {
					
					var par = $(this).parent().parent(); //tr
					
					par.remove();
					

				});
	     });

		
		paran_timeopter = {
			init: function() {
				
				$('#ptp_2').timeopter({
					defaultTime: 'current',
					minuteStep: 1,
					disableFocus: true,
					template: 'dropdown'
				});
				
				
			}
		};
		CKEDITOR.replace( 's_map' );
	</script>

    </body>
</html>

      
      