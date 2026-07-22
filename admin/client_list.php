<?php
    include "include/header.php";
	
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
	if($Mode == "del")
	{
		$qry1 = "update member_list set out_yn='1' where  seq_no ='$id'";
		$rst1 = $dbConn->query($qry1);
	}
	if($Mode == "reset")
	{
		$qry1 = "update member_list set out_yn=null where  seq_no ='$id'";
		$rst1 = $dbConn->query($qry1);
	}
	if($mode == "update")
	{
		update_infoinit($userid);
	}
	if ($mode == 'upst') {
		update_time($userid,$v);
	}
	function printVendor(){
			
			global $dbConn,$division,$g_nm,$pdx,$sub,$type1;
			if ($g_nm != "") {

				$gudnm = "&& kor_name like '%$g_nm%' ";
			} else {

				$gudnm = "";

			}
			//echo $type1;
			if ($type1 == "1") {
				$qry1 = "select *
							from 
								member_list
							where division in ('web-member','NORMAL') && out_yn is null  $gudnm order by wdate desc ";
			} elseif ($type1 == "2") { 
				$qry1 = "select *
							from 
								member_list
							where division in ('web-member','NORMAL') && out_yn ='1' $gudnm order by wdate desc";
				
			} else {
				$qry1 = "(select *, 1 as ord
							from member_list
							where division in('web-member','NORMAL')  && out_yn is null && grant_s != 2 $gudnm)
						union (select *, 2
							from member_list
							where division in('web-member','NORMAL') && out_yn is null && grant_s = 2 $gudnm)

						order by ord, kor_name";
			} 
			//echo $qry1;
			$rst1 = $dbConn->query($qry1);

			while($row1 = $rst1->fetch_assoc()){
			
				
				$log_cnt=getinfo_dbExMember($row1[userid]);
				$usid= $log_cnt[userid];
			//	echo $log_cnt[log_cnt]."11";
				if ($log_cnt[log_cnt] > 3 ) {
					 $st = '<td align=center bgcolor="ffcccc"><a href=client_list.php?mode=update&division=$division&pdx=$pdx&sub=$sub&userid='.$usid.'>잠김</a></td>';
				} else {
					$st = '<td align=center>정상</td>';
				}
				$log=getinfo_dbExMember($row1[userid]);
				$usid= $log[userid];
				
				
					
				echo "<tr bgcolor=#FFFFFF>
				 <td align=center height=28><input type=checkbox name=seqNo[]  value='$row1[seq_no]'></td>
				<td align=left>&nbsp;$row1[kor_name]</td>
				<td height=25>&nbsp;$row1[email]</td>
				<td align=center>$row1[email]</td>
				<td align=center>&nbsp;<b>P.</b> $row1[phone] &nbsp;&nbsp;</td>
				$st
				<td align=center><a href=cli_m.php?division=$division&pdx=$pdx&sub=$sub&id=$row1[seq_no]>수정</a> |  <a href=\"javascript:del($row1[seq_no])\">탈퇴</a>  | <a href=\"javascript:rest($row1[seq_no])\">재가입</a></td>
				</tr>";


			}

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
						<a href="#">고객관리</a>
					</li>
					<li>
						<a href="#">고객정보</a>
					</li>
					
					<li>
						<a href="client_list.php?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>">고객정보리스트</a>
					</li>
				</ul>
			</div>
			
		<div class="row">
				<div class="col-sm-12 col-md-12">
					  <form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>" enctype="multipart/form-data" name="base_code" id="base_code" method="post">
			          <input type="hidden" name="mode" value="search">
						<table class="table table-striped table-bordered table-condensed">
						    <tbody>
							   <tr>
							      <td width=10%  class="titletd" style="vertical-align: middle;">항목명 </td>
								  <td width=20% style='border:0;' class="conttd">
								  <select name="type1" class="inpubase lg" >
									<? $option0 = ($type1 == "0") ? ('<option value="0" selected>이름순</option>') : ('<option value="0">이름순</option>'); echo $option0 ?>
									<? $option1 = ($type1 == "1") ? ('<option value="1" selected>입력일순</option>') : ('<option value="1">입력일순</option>'); echo $option1 ?>
									<? $option2 = ($type1== "2") ? ('<option value="2" selected>탈퇴자</option>') : ('<option value="2">탈퇴자</option>'); echo $option2 ?>
								</select><input type=text name=g_nm size=15 class='inpubase md' placeholder="이름조회" value=''>
								  </td>
								  <td width=5%  class="conttd"><button type='submit' class="btn btn-primary btn-sm btn1">검색</button> </td>
								  <td class="conttd"><a href='cli_m.php?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>' class="btn btn-primary btn-sm btn1">추가</a> </td>
                               </tr> 
							</tbody>
						</table>
					 </form>
					  <table id='ctable' class="table table-striped table-bordered mediaTable">
						<thead>
							<tr>
							    <th width=5% class="essential"><input id='selectAll' type=checkbox ></th>
							    <th width=10% class="essential">이름</th>
								<th width=10% class="essential">아이디</th>
								<th width=10% class="essential">이메일</td>
								<th width=10% class="essential">연락처</th>
								<th width=10% class="essential">상태</td>
								<th width=15% class="essential">수정|삭제</td>

							    
							</tr>
						</thead> 
							
						<? printVendor(); ?>
					  </table>
                     
				</div><!-- -->
		</div>                
		</div>
	  </div>

	</div>

    <?php
		include "include/side_m.php"
	?>
    
    <script>
		$(document).ready(function () {
			//pt.initProductList()
			
			
			var oTable = $('#ctable').dataTable({
				
				stateSave: true,
				pageLength: 100,
				"order": [[ 1, "asc" ]]
			});

			var allPages = oTable.fnGetNodes();

			$('body').on('click', '#selectAll', function () {
				if ($(this).hasClass('allChecked')) {
					$('input[type="checkbox"]', allPages).prop('checked', false);
				} else {
					$('input[type="checkbox"]', allPages).prop('checked', true);
				}
				$(this).toggleClass('allChecked');
			})
			$(".dataTables_length").css({ "display" :"none" });
		});
			function del(id){
				
				if(confirm("탈퇴처리할까요?") == true)
				{
					location.replace('client_list.php?Mode=del&division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&id=' + id);
				}
				else return;
			}
			function rest(id){
				
				if(confirm("재가입처리할까요?") == true)
				{
					location.replace('client_list.php?Mode=reset&division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&id=' + id);
				}
				else return;
			}
		
		
	</script>


    </body>
</html>

      
      