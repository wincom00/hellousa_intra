
<?php
    include "include/header.php";
	//include "include/inc_base.php";
	if ($_COOKIE[MEMLOGIN_ADMIN_HELLO] != "") {
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

	if ($mode1 =="save") {
		
		 for($i=0; $i<count($seqNo); $i++)
		 {  
			$s = $seqNo[$i];
			//echo $s;
		
			$pre_qry1 = "select * from main_display where view_position = '$displaysel' && p_code = '$pcode[$s]'";
			$pre_rst1 = $dbConn->query($pre_qry1);
			$pre_num1 = $pre_rst1->num_rows;
			//echo $pre_num1;
			//exit;
			switch($displaysel){

				case "MAIN_BEST":
					$title = "[메인]가장인기인기있는여행";
					break;
				case "MAIN_DISC":
					$title = "[메인]할인상품";
					break;
				case "MAIN_MON":
					$title = "[메인]스페셜[월]";
					break;
				case "MAIN_TUE":
					$title = "[메인]스페셜[화]";
					break;
				case "MAIN_WED":
					$title = "[메인]스페셜[수]";
					break;
				case "MAIN_THU":
					$title = "[메인]스페셜[목]";
					break;
				case "MAIN_FRI":
					$title = "[메인]스페셜[금]";
					break;
				case "MAIN_SAT":
					$title = "[메인]스페셜[토]";
					break;
				case "MAIN_SUN":
					$title = "[메인]스페셜[일]";
					break;
				
				
			}

			if($pre_num1<=0)
		    {

				$qry1 = "insert into main_display (item_type,view_position,
																p_code,
																pos,
																m_link,
																w_title) values (   'tour',
																				'$displaysel',
																				'$pcode[$s]',
																				'100',
																				'$mlink[$s]',
																				'$w_tit[$s]')";
				//echo $qry1;
				//exit;
				$rst1 = $dbConn->query($qry1);

		   }
			// echo $qry2;
		 }
		//exit;
		 Misc::jvAlert("저장 완료!!!");
		 
	}

	if ($mode1 =="usave") {
		//print_r($pos);
		
		
		 for($i=0; $i<count($seqNo); $i++)
		 {  
			 $s = $num[$i];
			 $qry1 = "update main_display set pos = '$pos[$s]',m_link = '$mlink[$s]' ,p_name = '$p_name[$s]',w_title='$w_tit[$s]' where seq_no = '$seqNo[$i]'";
				$rst1 = $dbConn->query($qry1);
			// echo $qry1;
		 }
		//exit;
		 Misc::jvAlert("저장 완료!!!");
		 
	}
    if($mode1 == "del")
	{
		  for($i=0; $i<count($p_code); $i++)
		  {
			
			$qry1 = "delete from main_display where p_code = '$p_code[$i]' && view_position='".$flag."'";
			//echo $qry1;
			//exit;
			$rst1 = $dbConn->query($qry1);

		  }
	}

	if ($flag == "") {
		 $flag=$displaysel;

	}
	function printProduct(){
		
		global $dbConn,$flag;

		if(empty($flag))
		{
			$flag = "MAIN_BEST";
		}

		$qry1 = "select * from main_display where view_position = '$flag' order by pos asc";
		$rst1 = $dbConn->query($qry1);
//echo $qry1;
		$num1 = 0;

		while($row1 = $rst1->fetch_assoc()){

			$p_info = getProductMaster($row1[p_code]);

    

			switch($row1[view_position]){

				
                case "MAIN_BEST":
					$title = "[메인]가장인기인기있는여행";
					break;
				case "MAIN_DISC":
					$title = "[메인]할인상품";
					break;
				case "MAIN_MON":
					$title = "[메인]스페셜[월]";
					break;
				case "MAIN_TUE":
					$title = "[메인]스페셜[화]";
					break;
				case "MAIN_WED":
					$title = "[메인]스페셜[수]";
					break;
				case "MAIN_THU":
					$title = "[메인]스페셜[목]";
					break;
				case "MAIN_FRI":
					$title = "[메인]스페셜[금]";
					break;
				case "MAIN_SAT":
					$title = "[메인]스페셜[토]";
					break;
				case "MAIN_SUN":
					$title = "[메인]스페셜[일]";
					break;
                
			} 
            if ($row1[p_name] == "") {
				$pnm=$p_info[p_name];
			} else {
				$pnm=$row1[p_name];
			}
			if ($row1[w_title] == "") {
				$wtit=$p_info[w_title];
			} else {
				$wtit=$row1[w_title];
			}
			echo "<tr bgcolor=#FFFFFF>
					<td  align=center height=28><input type=checkbox name=p_code[] value=$row1[p_code]></td>
					<td align=center><input type=hidden name=seqNo[] value=$row1[seq_no]><input type=hidden name=num[] value=$num1><input type=text name=pos[] value=$row1[pos] class='form-control'></td>
					<td  align=center>$title</td>
					<td  align=center>$row1[p_code]</td>
					<td  align=left>&nbsp;<input type=text name=p_name[] value='$pnm' size=50 class='form-control'><br/>&nbsp;<input type=text name=w_tit[] value='$wtit' size=50 class='form-control'> </td>
					<td  align=center><input type=text name=mlink[] value='$row1[m_link]' size=50 class='form-control'></td>
					</tr>";
			
			$num1++;
		}

		if($num1 == "0")
		{
			echo "<tr><td colspan=5 height=35 align=center bgcolor=#FFFFFF>진열된 상품없습니다.</td></tr>";
		}

	}
    
    
	
	
?>
     
	<div id="contentwrapper">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb 
			module">
				<ul>
					<li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li>
					<li><a href="#">홈페이지관련설정</a></li>
					<li><a href="#">홈페이지상품설정</a></li>
					<li><?=$pcap?></li>
				</ul>
			</div>
			<div class="row">
				<div class="col-sm-12 col-md-12">
					<form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=<?=$flag?>" enctype="multipart/form-data" name="base_code" id="base_code" method="post">
						<input type="hidden" name="mode" value="search">
						
						<table id="level4" class="txt_12" width="98%" align=center border="0" cellspacing="1" cellpadding="0" bgcolor=#cccccc>
							<tr>
								<td bgcolor=#FFFFFF height=30>&nbsp;
								
								   
								<a href=<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=MAIN_BEST>[메인]가장인기인기있는여행</a>	|			
									
							    <a href=<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=MAIN_DISC>[메인]할인상품</a>	|
								<a href=<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=MAIN_MON>[메인]스페셜[월]</a>     |
								<a href=<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=MAIN_TUE>[메인]스페셜[화]</a>     |
								<a href=<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=MAIN_WED>[메인]스페셜[수]</a>     |
								<a href=<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=MAIN_THU>[메인]스페셜[목]</a>     |
								<a href=<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=MAIN_FRI>[메인]스페셜[금]</a>     |
							    <a href=<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=MAIN_SAT>[메인]스페셜[토]</a>     |
								<a href=<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=MAIN_SUN>[메인]스페셜[일]</a>     |
								</td>
							</tr>
						</table>
					</form>
					<form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=<?=$flag?>" enctype="multipart/form-data" id="frmprod"  method="post">
					<input type="hidden" name="mode1" id="mode1" value="">
					
						<table class="table table-striped table-bordered table-condensed js-prod1">
								<tbody>
								<tr>
									
									<td width=5%  class="conttd"><button type='button' class="btn btn-primary btn-sm btn1 js-dsave">지정삭제</button> </td>
									<td class="conttd"><button type='button' class="btn btn-primary btn-sm btn1 js-usave">업데이트</button> </td>
								</tr> 
								</tbody>
							</table>
					
							<table id='ctable' class="table table-striped table-bordered mediaTable js-productListTable">
								<thead>
									<tr>
										<th width=10% align=center><input type=checkbox id="selectAll" ></th>
										<th width=10% align=center>순서</th>
										<th width=20% align=center>위치</th>
										<th width=20% align=center>상품코드</th>
										<th width=30% align=center>상품명</th>
										<th width=30% align=center>사용자링크</th>
									</tr>
								</thead> 
								<tbody>
									<?php echo printProduct(); ?>
								</tbody>
							</table>
					</form>
				</div><!-- -->
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
				"order": [[ 2, "asc" ]]
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
			$('.js-usave').click(function(e){
				if (confirm("업데이트 하시겠습니까?"))
				{
					$("#mode1").val("usave");
				    $("#frmprod").submit();
				}
				

			});

			$('.js-dsave').click(function(e){
				if (confirm("삭제 하시겠습니까?"))
				{
					$("#mode1").val("del");
				    $("#frmprod").submit();
				}
				

			});
			
		});
		
			
		
	</script>
    </body>
</html>
