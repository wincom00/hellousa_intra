  <?php
    include "include/header.php";
	//include "include/inc_base.php";
	if($_COOKIE['MEMLOGIN_ADMIN_HELLO'] !="")
	{
	} else {
		
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}
    
    if ($mode == "save") {
		 
		  if ($no == "") {
		        // 상품이미지
				$tmpName1 = $_FILES['userfile1']['tmp_name'];

				$ftmp = $_FILES['image']['tmp_name'];
				$oname = $_FILES['image']['name'];
				$fname = 'product_img/'.$_FILES['image']['name'];

				if(is_uploaded_file($tmpName1)){
						$pds_file1 = $_FILES['userfile1']['name'];
						$board_pds_pos = "product_img";
						$attc_name1 = Misc::uploadFileUnsafely($tmpName1 , $pds_file1 , $board_pds_pos);

						$src = 'product_img/'."$attc_name1[savedName]";        //-- 원본 
						$dst = 'product_img/thum_'."$attc_name1[savedName]";     //-- 저장 

						$quality = '80';    //-- jpg 퀄리티 
						$size = '70';    //-- 줄일 크기 pixel (너비, 또는 높이에 적용) 
						$ratio = '4:3';        //-- 이미지를 4:3 비율로 잘라냄 
						$ratio = 'false';        //-- 원본 이미지비율을 유지 

						$get_size = _getimagesize($src, $size, $ratio); 
						$result = resize_image($dst, $src, $get_size, $quality, $ratio); 

				}

				$qry1 = "insert into member_list (division,
											userid,
											passwd,
											level,
											kor_name,
											eng_name,
											email,
											birthday,
											sex,
											phone,
											cell_phone,
											wdate,
											access_level,
											deny,
											grant_s,
											ssn,
											address,
											reference,
											join_date,
											c_part,
											c_part1,
											userfile1,
											expire_date,
											area_comp,
											dept_prior) values ('admin',
																	'$userid',
																	'$passwd',
																	'10',
																	'$kor_name',
																	'$eng_name',
																	'$email',
																	'$birthday',
																	'$sex',
																	'$phone',
																	'$cell_phone',
																	now(),
																	'$new_access_level',
																	'$deny',
																	'$grantmode',
																	'$ssn',
																	'$address',
																	'$reference',
																	'$join_date',
																	'$c_part',
																	'$c_part1',
																	'$attc_name1[savedName]',
																	now(),
																	'$area_comp',
																	'$dept1')";
				$rst1 = $dbConn->query($qry1);

				if($rst1)
				{
					$goUrl_1 = "emp_list.php?division=$division&pdx=$pdx&sub=$sub";
					 echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
					 exit;
				}
				 
				 
		  } else {
				$qry3 = "delete from menu_info_user where userid = '$userid'";
				$rst3 = $dbConn->query($qry3);
				
				for($i=1; $i<=11; $i++)
				{
					$k = "access_level".$i;
									
					$u = $$k;
					
					
					//echo $u."<br/>";
						if($$k)
						{
							$new_access_level .= $$k."/";
							$s = $MCode[$u-1];
							//Save Menu Items
							$r = $urlA[$u-1];
							//echo $r."<br>";
						   $qry4 = "INSERT INTO `menu_info_user` 
								   (`division`, `parent_idx`, `sub_idx`, `menu_name`, 
								   `menu_link`, `access_level`, `pos`, `menu_code`, `userid`) 
									VALUES
								   ('m', 0, 0, '$s', '$r', 0, 0, '0', '$userid')";

							//print_r($qry4);
							//echo "<br>";
							$rst4 = $dbConn->query($qry4);
						}
					
				}
				//exit;
			  //print_r($_POST);
			  //exit;
			  if(isset($_POST["menu2"])) {
					//echo "values selected:<br>";
					$saved_menu_seq = array();
					foreach($_POST["menu2"] as $key => $val) {
						// 같은 메뉴가 중복 선택되어 넘어와도 한 번만 저장 (두번씩 저장 방지)
						if ($val == "" || in_array($val, $saved_menu_seq)) {
							continue;
						}
						$saved_menu_seq[] = $val;
						{
							$qry3 = "SELECT *
								FROM menu_info
								WHERE seq_no='$val'";
							$rst3 = $dbConn->query($qry3);
							
							$cnt = 0;
							while($row2 = $rst3->fetch_assoc())
							{
								  $qry5 = "INSERT INTO `menu_info_user` 
								   (`division`, `parent_idx`, `sub_idx`, `menu_name`, 
								   `menu_link`, `access_level`, `pos`, `menu_code`, `userid`) 
									VALUES
						   ('$row2[division]', '$row2[parent_idx]', '$row2[sub_idx]', '$row2[menu_name]', '$row2[menu_link]','$row2[access_level]', '$row2[pos]', '$row2[menu_code]', '$userid')";
								$rst5 = $dbConn->query($qry5);
								
							}
					   }
					}
				}

				for($k=0; $k<count($dCode); $k++)
				{
					$deny .= $dCode[$k]."@".$deny_code[$k]."NaN";
				}

			    if($photo_del1 != "1")
				{
					$ftmp = $_FILES['image']['tmp_name'];
					$oname = $_FILES['image']['name'];
					$fname = 'product_img/'.$_FILES['image']['name'];

					if(empty($_FILES['userfile1']['name']))
					{
					     $attc_name1['savedName'] = $old_pic;
					}
					else
					{
						$tmpName1 = $_FILES['userfile1']['tmp_name'];

						if(is_uploaded_file($tmpName1)){
								$pds_file1 = $_FILES['userfile1']['name'];
								$board_pds_pos = "uploads";
								$attc_name1 = Misc::uploadFileUnsafely($tmpName1 , $pds_file1 , $board_pds_pos);
						}

						$src = 'uploads/'."$attc_name1[savedName]";        //-- 원본 
						$dst = 'uploads/thum_'."$attc_name1[savedName]";     //-- 저장 

						$quality = '80';    //-- jpg 퀄리티 
						$size = '70';    //-- 줄일 크기 pixel (너비, 또는 높이에 적용) 
						$ratio = '4:3';        //-- 이미지를 4:3 비율로 잘라냄 
						$ratio = 'false';        //-- 원본 이미지비율을 유지 

						$get_size = _getimagesize($src, $size, $ratio); 
						$result = resize_image($dst, $src, $get_size, $quality, $ratio); 

					}
				}
				else
				{
					@unlink("uploads/$old_pic");
					 $attc_name1['savedName'] = "";
				}

				$qry1 = "update member_list set passwd = '$passwd',
											kor_name = '$kor_name',
											eng_name = '$eng_name',
											email = '$email',
											company_code = '$company_code',
											birthday = '$birthday',
											sex = '$sex',
											cell_phone = '$cell_phone',
											phone = '$phone',
											access_level = '$new_access_level',
											c_part1 = '$c_part1',
											deny = '$deny',
											ssn = '$ssn',
											grant_s = '$grantmode',
											address = '$address',
											reference = '$reference',
											join_date = '$join_date',
											c_part = '$c_part',
											userfile1 = '$attc1_name[savedName]' ,
											area_comp = '$area_comp',
											dept_prior = '$dept1'
																						
											where seq_no = '$no'";

				$rst1 = $dbConn->query($qry1);
				$goUrl_1 = "emp_list.php?division=$division&pdx=$pdx&sub=$sub";
			     echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
                 exit;


		  }

	} 
	$qry1 = "select * from member_list where seq_no = '$id'";
	$rst1 = $dbConn->query($qry1);
	$row1 = $rst1->fetch_assoc();
	$v_info = $row1 ?: array();


	// deny 
	$deny = explode("NaN",$row1['deny']);

	$qry2 = "select * from menu_info_user where division='m' && userid='$row1[userid]'";
	$rst2 = $dbConn->query($qry2);
	//$row2 = $rst2->fetch_assoc();

	// url
	$menu_num = array('기초관리' => '1',
					'상품관리' => '2',
					'예약관리' => '3',
					'행사관리' => '4',
					'MIS' => '5',
					'정산관리' => '6',
					'인사관리' => '7',
					'게시판관리' => '8',
					'컨텐츠관리' => '9',
					'고객관리' => '10',
					'수입·지출' => '11'
					);
	while($row2 = $rst2->fetch_assoc()) {
		$mName = $row2['menu_name'];
		$url["$menu_num[$mName]"] = $row2['menu_link'];
		//echo $row2[menu_link].$mName."<br />";
	
	}
	//print_r($url);
	$qry3 = "SELECT * 
					FROM menu_info
					WHERE division <> 'm' 
					ORDER BY division, parent_idx, sub_idx ASC 
					";
	$rst3 = $dbConn->query($qry3);
	$cnt = 0;
	while($row2 = $rst3->fetch_assoc())
	{
		    $cnt= $cnt+1;
			if ($row2['pos']== '0') {
			   
			   $stylec[] = "style='color:red'";
			} else {
			   
			   $stylec[] ="";
			}
			$mnm[] = $row2['menu_name'];
			$mdivision[] = $row2['division'];
			$mpidx1[] = $row2['parent_idx'];
			$mpidx[] = $row2['sub_idx'];
			$macc[] = $row2['access_level'];
			$mpos[] = $row2['pos'];
			$mcode[] = $row2['menu_code'];
			$mseq[] = $row2['seq_no'];
		
			
	}
	//print_r($mnm);
	$qry4 = "SELECT * 
					FROM menu_info_user
					WHERE division <> 'm' && userid='$row1[userid]'
					ORDER BY division, parent_idx, sub_idx ASC 
					";
	$rst4 = $dbConn->query($qry4);
	$cnt1 = 0;
	$seen_seq4 = array();
	//echo $qry4;
	while($row4 = $rst4->fetch_assoc())
	{
			$qry5 = "SELECT *
					FROM menu_info
					WHERE division='$row4[division]' && parent_idx='$row4[parent_idx]' && sub_idx='$row4[sub_idx]' && menu_name='$row4[menu_name]'
					ORDER BY division, parent_idx, sub_idx ASC
					";
			//echo $qry5."<br>";
			$rst5 = $dbConn->query($qry5);
			$row5 = $rst5->fetch_assoc();
			// 이미 중복 저장된 데이터는 화면에 한 번만 표시
			if ($row5 && in_array($row5['seq_no'], $seen_seq4)) {
				continue;
			}
			if ($row5) $seen_seq4[] = $row5['seq_no'];
		    $cnt1= $cnt1+1;
			$mnm4[] = $row4['menu_name'];
			$mdiv4[] = $row4['division'];
			$mpidx4[] = $row5['parent_idx'];
			$mseq4[] = $row5['seq_no'];
			$mpos4[] = $row4['pos'];
			if ($row4['pos']== '0') {
			   
			   $stylec4[] = "style='color:red'";
			} else {
			   
			   $stylec4[] ="";
			}
	}

	$qry6 = "select * from menu_info where division='m'";
	$rst6 = $dbConn->query($qry6);

	// url
	
	$menu_num = array('기초관리' => '1',
					'상품관리' => '2',
					'예약관리' => '3',
					'행사관리' => '4',
					'MIS' => '5',
					'정산관리' => '6',
					'인사관리' => '7',
					'게시판관리' => '8',
					'컨텐츠관리' => '9',
					'고객관리' => '10',
					'수입·지출' => '11');
	while($row6 = $rst6->fetch_assoc()) {
		$mName = $row6['menu_name'];
		$m_url["$menu_num[$mName]"] = $row6['menu_link'];
	}


?>
<style>
	.emp-access-table {
		border-collapse: collapse !important;
		border: 1px solid #333;
	}
	.emp-access-table > tbody > tr > td {
		border: 1px solid #333 !important;
		vertical-align: top;
	}
	.emp-access-row > td {
		padding: 0 !important;
	}
	.emp-access-row table {
		width: 100%;
		border-collapse: collapse;
		table-layout: fixed;
	}
	.emp-access-row table tr {
		height: 26px;
	}
	.emp-access-row table td {
		height: 26px;
		padding: 0 8px !important;
		border-bottom: 1px solid #ccc;
		line-height: 26px;
		vertical-align: middle;
	}
	.emp-access-row table tr:last-child td {
		border-bottom: 0;
	}
	.emp-access-row table br {
		display: none;
	}
	.emp-access-row input[type="checkbox"] {
		margin-top: 0;
		vertical-align: middle;
	}
	.emp-access-row input[type="text"].inpubase {
		width: 100%;
		max-width: 300px;
		height: 22px;
		box-sizing: border-box;
		vertical-align: middle;
	}
</style>
     
<div id="contentwrapper">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb module">
				<ul>
					<li>
						<a href="/"><i class="glyphicon glyphicon-home"></i></a>
					</li>
					<li>
						<a href="#">인사관리</a>
					</li>
					<li>
						<a href="emp_list.php?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>">직원관리</a>
					</li>
				</ul>
			</div>
			
		<div class="row">
				<div class="col-sm-12 col-md-12">
					  <form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>" enctype="multipart/form-data" name="frmemp" id="frmemp" method="post" onSubmit="return chk(this)">
			           	  <input type=hidden name=mode value="save">
						  <input type=hidden name=no value="<?= $id ?>">
						  <input type=hidden name=division value="<?= $division ?>">
						  <input type=hidden name=userfile1_tmp value="<?= $v_info['userfile1'] ?>">
						
						<table class="table table-striped table-bordered table-condensed">
						    <tbody>
							        <tr>
										<td colspan=4 bgcolor=#F9F9F9 height=25>&nbsp;기본정보</td>
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% align=center>아이디</td>
										<td width=35% align=left bgcolor=#FFFFFF>&nbsp;<input type=text name=userid size=30 class='inpubase md' value="<?= $v_info['userid'] ?>"></td>
										<td width=15% align=center>패스워드</td>
										<td width=35% align=left bgcolor=#FFFFFF>&nbsp;<input type=text name=passwd size=30 class='inpubase md' value="<?= $v_info['passwd'] ?>"></td>
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% align=center>이름(한글)</td>
										<td width=35% align=left bgcolor=#FFFFFF>&nbsp;<input type=text name=kor_name size=30 class='inpubase md' value="<?= $v_info['kor_name'] ?>"></td>
										<td width=15% align=center>이름(영문)</td>
										<td width=35% align=left bgcolor=#FFFFFF>&nbsp;<input type=text name=eng_name size=30 class='inpubase md' value="<?= $v_info['eng_name'] ?>"></td>
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% align=center>이메일</td>
										<td width=35% align=left bgcolor=#FFFFFF>&nbsp;<input type=text name=email size=40 class='inpubase md' value="<?= $v_info['email'] ?>"></td>
										<td width=15% align=center>생년월일</td>
										<td width=35% align=left bgcolor=#FFFFFF>&nbsp;<input type=text name=birthday id="mask_date1" class='inpubase md' value="<?= $v_info['birthday'] ?>">&nbsp;<input type="radio" name="sex" value = "M" <?php if($v_info['sex'] == "M") echo "checked"; ?>> 남 &nbsp;&nbsp;
										  <input type="radio" name="sex" value = "F" <?php if($v_info['sex'] == "F") echo "checked"; ?>> 여</td>
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% align=center>휴대폰</td>
										<td width=35% align=left bgcolor=#FFFFFF>&nbsp;<input type=text name=cell_phone id='mask_phone' size=30 class='inpubase md' value="<?= $v_info['cell_phone'] ?>"></td>
										<td width=15% align=center>일반전화</td>
										<td width=35% align=left bgcolor=#FFFFFF>&nbsp;<input type=text name=phone size=30 id='mask_phone1'  class='inpubase md' value="<?= $v_info['phone'] ?>"></td>
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% align=center>SSN#</td>
										<td width=35% align=left bgcolor=#FFFFFF>&nbsp;<input type=text name=ssn id='mask_ssn' size=30 class='inpubase md' value="<?= $v_info['ssn'] ?>"></td>
										<td width=15% align=center>집주소</td>
										<td width=35% align=left bgcolor=#FFFFFF>&nbsp;<input type=text name=address size=30 class='inpubase lg' value="<?= $v_info['address'] ?>"></td>
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% align=center>비상연락처</td>
										<td width=35% align=left bgcolor=#FFFFFF>&nbsp;<input type=text name=reference id='mask_phone2'  class='inpubase md' value="<?= $v_info['reference'] ?>"></td>
										<td width=15% align=center>입사일</td>
										<td width=35% align=left bgcolor=#FFFFFF>&nbsp;<input type=text name=join_date id="mask_date" class='inpubase md' value="<?= $v_info['join_date'] ?>"></td>
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% align=center>부서</td>
										<td width=35% align=left bgcolor=#FFFFFF>&nbsp;<select name='area_comp' class='inpubase md'><?=printBaseCode_first('D02',$v_info['area_comp'])?></select><br />&nbsp;<select name='c_part1' class='inpubase md'><?=printBaseCode_first('D01',$v_info['c_part1'])?></select><br />&nbsp;<input type=text name=c_part size=15 class='inpubase md' value="<?= $v_info['c_part'] ?>">&nbsp;</td>
										<td width=15% align=center>사진</td>
										<td width=35% align=left bgcolor=#FFFFFF style="vertical-align : middle;">&nbsp;<?php if($v_info['userfile1']): ?><IMG SRC="upload/<?= $v_info['userfile1'] ?>" width=120><?php endif; ?><input type=file name=userfile1 size=30 class='inpubase md' value="<?= $v_info['userfile1'] ?>"></td>
									</tr>
									
									
									
									
							</tbody>
						</table>
						<br />
						<?php
						   
						?>
						<table class="table table-striped table-bordered table-condensed emp-access-table">
						    <tbody>
							       <tr>
										<td bgcolor=#F9F9F9 height=25 align='center'><span class="label label-default">접근권한</span></td>
										<td bgcolor=#F9F9F9 height=25 align='center'><span class="label label-default">허용된 대메뉴</span></td>
										<td bgcolor=#F9F9F9 height=25>&nbsp;<span class="label label-default">기본메뉴접근 URL이 다르면 여기에서 수정해주세요</span></td>
									</tr>
									<tr class="emp-access-row" height=28>
										<td width=15% align=center>허용 메뉴</td>
										<td width=25% align=left bgcolor=#FFFFFF>
											<table cellspacing=0 cellpadding=0>
												<tr height=22><td>&nbsp;<input type=checkbox name=access_level1 value="1" <?php if (preg_match("/^1/", $row1['access_level'])) echo "checked"; ?>> 기초관리<br></td></tr>
												<tr height=22><td>&nbsp;<input type=checkbox name=access_level2 value="2" <?php if (preg_match("/2/", $row1['access_level'])) echo "checked"; ?>> 상품관리<br></td></tr>
												<tr height=22><td>&nbsp;<input type=checkbox name=access_level3 value="3" <?php if (preg_match("/3/", $row1['access_level'])) echo "checked"; ?>> 예약관리<br></td></tr>
												<tr height=22><td>&nbsp;<input type=checkbox name=access_level4 value="4" <?php if (preg_match("/4/", $row1['access_level'])) echo "checked"; ?>> 행사관리<br></td></tr>	
												<tr height=22><td>&nbsp;<input type=checkbox name=access_level5 value="5" <?php if (preg_match("/5/", $row1['access_level'])) echo "checked"; ?>> MIS<br></td></tr>
												<tr height=22><td>&nbsp;<input type=checkbox name=access_level6 value="6" <?php if (preg_match("/6/", $row1['access_level'])) echo "checked"; ?>> 정산관리<br></td></tr>
												<tr height=22><td>&nbsp;<input type=checkbox name=access_level7 value="7" <?php if (preg_match("/7/", $row1['access_level'])) echo "checked"; ?>> 인사관리<br></td></tr>
												<tr height=22><td>&nbsp;<input type=checkbox name=access_level8 value="8" <?php if (preg_match("/8/", $row1['access_level'])) echo "checked"; ?>> 게시판관리</td></tr>
												<tr height=22><td>&nbsp;<input type=checkbox name=access_level9 value="9" <?php if (preg_match("/9/", $row1['access_level'])) echo "checked"; ?>> 컨텐츠관리</td></tr>
												<tr height=22><td>&nbsp;<input type=checkbox name=access_level10 value="10" <?php if (preg_match("/10/", $row1['access_level'])) echo "checked"; ?>> 고객관리</td></tr>
												<tr height=22><td>&nbsp;<input type=checkbox name=access_level11 value="11" <?php if (preg_match("/11/", $row1['access_level'])) echo "checked"; ?>> 수입·지출</td></tr>
											</table>
										</td>
										<td width=60% align=left bgcolor=#FFFFFF colspan=2>
											<table cellspacing=0 cellpadding=0>
											   
												<tr height=20><td>&nbsp;<input type=hidden name=dCode[] value="1"><input type=hidden name=MCode[] value="기초관리"><input type=text name=urlA[]  value="<?= preg_match('/^1/', $row1['access_level']) ? $url[1] : $m_url[1] ?>" class="inpubase lg"><br></td></tr>
												<tr height=20><td>&nbsp;<input type=hidden name=dCode[] value="2"><input type=hidden name=MCode[] value="상품관리"><input type=text name=urlA[] size=50 value="<?= preg_match('/2/', $row1['access_level']) ? $url[2] : $m_url[2] ?>" class="inpubase lg"><br></td></tr>
												<tr height=20><td>&nbsp;<input type=hidden name=dCode[] value="3"><input type=hidden name=MCode[] value="예약관리"><input type=text name=urlA[] size=50 value="<?= preg_match('/3/', $row1['access_level']) ? $url[3] : $m_url[3] ?>" class="inpubase lg"><br></td></tr>
												<tr height=20><td>&nbsp;<input type=hidden name=dCode[] value="4"><input type=hidden name=MCode[] value="행사관리"><input type=text name=urlA[] size=50 value="<?= preg_match('/4/', $row1['access_level']) ? $url[4] : $m_url[4] ?>" class="inpubase lg"><br></td></tr>
												
												<tr height=22><td>&nbsp;<input type=hidden name=dCode[] value="5"><input type=hidden name=MCode[] value="MIS"><input type=text name=urlA[] size=50 value="<?= preg_match('/5/', $row1['access_level']) ? $url[5] : $m_url[5] ?>" class="inpubase lg"><br></td></tr>
												<tr height=22><td>&nbsp;<input type=hidden name=dCode[] value="6"><input type=hidden name=MCode[] value="정산관리"><input type=text name=urlA[] size=50 value="<?= preg_match('/6/', $row1['access_level']) ? $url[6] : $m_url[6] ?>" class="inpubase lg"><br></td></tr>
												<tr height=22><td>&nbsp;<input type=hidden name=dCode[] value="7"><input type=hidden name=MCode[] value="인사관리"><input type=text name=urlA[] size=50 value="<?= preg_match('/7/', $row1['access_level']) ? $url[7] : $m_url[7] ?>" class="inpubase lg"><br></td></tr>
												<tr height=22><td>&nbsp;<input type=hidden name=dCode[] value="8"><input type=hidden name=MCode[] value="게시판관리"><input type=text name=urlA[] size=50 value="<?= preg_match('/8/', $row1['access_level']) ? $url[8] : $m_url[8] ?>" class="inpubase lg"><br></td></tr>
												<tr height=22><td>&nbsp;<input type=hidden name=dCode[] value="9"><input type=hidden name=MCode[] value="컨텐츠관리"><input type=text name=urlA[] size=50 value="<?= preg_match('/9/', $row1['access_level']) ? $url[9] : $m_url[9] ?>" class="inpubase lg"><br></td></tr>
												<tr height=22><td>&nbsp;<input type=hidden name=dCode[] value="10"><input type=hidden name=MCode[] value="고객관리"><input type=text name=urlA[] size=50 value="<?= preg_match('/10/', $row1['access_level']) ? $url[10] : $m_url[10] ?>" class="inpubase lg"><br></td></tr>
												<tr height=22><td>&nbsp;<input type=hidden name=dCode[] value="11"><input type=hidden name=MCode[] value="수입·지출"><input type=text name=urlA[] size=50 value="<?= preg_match('/11/', $row1['access_level']) ? $url[11] : $m_url[11] ?>" class="inpubase lg"><br></td></tr>
												
											</table>
										</td>
									</tr>
									
									<tr bgcolor=#b2dcca height=28>
										<td width=15% align=center>상세 메뉴</td>
										<td width=25% align=left bgcolor=#FFFFFF>
										    <p><span class="label label-default">메뉴 고르기</span></p>
											<select name=menu1[] size='20' style='width:300px' multiple="multiple" ondblclick="move(this.form.elements['menu1[]'],this.form.elements['menu2[]'])">
											<?php for ($s=1 ; $s <= $cnt; $s++) { ?>	
											<option <?=$stylec[$s-1]?> value=<?=$mseq[$s-1]?>><font color=red>[<?=$mdivision[$s-1]?>-<?=$mpidx1[$s-1]?>-<?=$mpos[$s-1]?>] <?= $mnm[$s-1] ?></font> </option>
											<?php } ?>
											</select>
									 </td>
										<td width=65% align=left bgcolor=#FFFFFF>
											<table cellspacing=0 cellpadding=0>
												<tr>
													<td style="vertical-align:middle;">&nbsp;<input type=button value="&rArr;" onclick="move(this.form.elements['menu1[]'],this.form.elements['menu2[]'])"></td>
													<td rowspan=2>
													     <p><span class="label label-default">허용된 메뉴</span></p>
														<select name=menu2[]  size='20' style='width:300px' multiple="multiple" ondblclick="move(this.form.elements['menu2[]'],this.form.elements['menu1[]'])">
														<?php for ($s=1 ; $s <= $cnt1; $s++) { ?>
														<option <?=$stylec4[$s-1]?> value=<?=$mseq4[$s-1]?> >[<?=$mdiv4[$s-1]?>-<?=$mpidx4[$s-1]?>-<?=$mpos4[$s-1]?>] <?= $mnm4[$s-1] ?>  </option>
														<?php } ?>
														</select>
													</td>
												</tr>
												<tr>
													<td style="vertical-align:middle;">&nbsp;<input type=button value="&lArr;" onclick="move(this.form.elements['menu2[]'],this.form.elements['menu1[]'])">&nbsp;&nbsp;</td>
												</tr>
											</table>
										</td>
									</tr>
									<tr>
										<td colspan=3 height=35 bgcolor=#FFFFFF align=center><input type=submit value="정보저장" class="btn btn-primary btn-sm"></td>
									</tr>
									<tr>
										<td width=15% align=center>정산활성권한</td>
										<td width=25% align=left bgcolor=#FFFFFF>
											&nbsp;<input type="radio" name="dept1" value = "A" <?php if($v_info['dept_prior'] == "A") echo "checked"; ?>> 활성 &nbsp;&nbsp;
										  <input type="radio" name="dept1" value = "J" <?php if($v_info['dept_prior'] == "J") echo "checked"; ?>> 비활성
										</td>
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
   

    </body>
	<script>
	         $(document).ready(function() {
		   
					$('#mask_date').datepicker({
						format: "yyyy-mm-dd",
						autoclose: true
					});
					paran_mask_input.init();
	        });

		
			//* masked input
			paran_mask_input = {
				init: function() {
					$("#mask_date").inputmask("9999-99-99",{placeholder:"____-__-__"});
					$("#mask_phone").inputmask("(999) 999-9999");
					$("#mask_phone1").inputmask("(999) 999-9999");
					$("#mask_phone2").inputmask("(999) 999-9999");
					$("#mask_ssn").inputmask("999-99-9999");
					$("#mask_date1").inputmask("9999-99-99",{placeholder:"____-__-__"});
				}
			};
              function chk(tf){
					
					  if(!tf.kor_name.value)
					  {
							alert('직원성명(한글)을 입력하세요!');
							tf.kor_name.focus();
							return false;
					  }			 
					  if(!tf.eng_name.value)
					  {
							alert('직원성명(영문)을 입력하세요!');
							tf.eng_name.focus();
							return false;
					  }		
					  if(!tf.userid.value)
					  {
							alert('아이디를 입력하세요!');
							tf.userid.focus();
							return false;
					  }	
					  if(!tf.passwd.value)
					  {
							alert('패스워드를 입력하세요!');
							tf.passwd.focus();
							return false;
					  }	
					  selectAll(document.frmemp.elements['menu2[]']);
					  return true;
				}

			
 
			 function move(fbox, tbox) {
					for(i=0; i<fbox.options.length; i++)
				{
					if(fbox.options[i].selected)
					{
						// 대상 리스트에 이미 같은 메뉴가 있으면 추가하지 않음 (중복 방지)
						var dup = false;
						for(var j=0; j<tbox.options.length; j++) {
							if (tbox.options[j].value == fbox.options[i].value) {
								dup = true;
								break;
							}
						}
						if (!dup) {
							tbox.options[tbox.options.length] = new Option(fbox.options[i].text, fbox.options[i].value);
						}
						fbox.options[i] = null;
						i--;
					}
				}

			  }
			  function selectAll(box) {
				
						for(var i=0; i<box.length; i++) {
							box.options[i].selected = true;

						  }		
			
				
			   }
</script>

	</script>
</html>

      
      
