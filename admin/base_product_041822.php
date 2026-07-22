
<?php
    include "include/header.php";
	
	if ($_COOKIE[MEMLOGIN_ADMIN_DONG] != "") {
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
		//print_r($pos):
		 $s = $seqNo[$i];	
		 for($i=0; $i<count($seqNo); $i++)
		 {
			$s = $seqNo[$i];
			$qry2 = " update product_master 
								set
								
								pos = '$pos[$s]'  
							where
								p_code = '$pcode[$s]' ";
			
			 $rst2 = $dbConn->query($qry2);
			// echo $qry2;
		 }
		//exit;
		 Misc::jvAlert("저장 완료!!!");
		 
	}
	if($Mode == "del")
	{
		$qry1 = "delete from product_master where p_code= '$pcode'";
		$rst1 = $dbConn->query($qry1);

		$qry1 = "delete from product_details where p_code= '$pcode'";
		$rst1 = $dbConn->query($qry1);
		
		$qry1 = "delete from product_limit where p_code= '$pcode'";
		$rst1 = $dbConn->query($qry1); 

		$qry1 = "delete from product_details_local where p_code= '$pcode'";
		$rst1 = $dbConn->query($qry1);

		$qry1 = "delete from product_pick where p_code= '$pcode'";
		$rst1 = $dbConn->query($qry1);

	}
	function printProdut($ty) {
		global $dbConn, $division, $pdx, $sub, $search;

		
		if ($search) {
		    $qrycom = "&& (p_code like '%$search%' || p_name like '%$search%')";
	    }else {
			$qrycom = "";
		}

		$qry1 = "select * from product_master where 1=1 && p_type='$ty' $qrycom order by p_code , p_name asc ";
		$rst1 = $dbConn->query($qry1);
		//echo $qry1;	
		$k=0;
		while($row1 = $rst1->fetch_assoc()){
			$cinfo1=codebaseName($row1[c_code1]);
			$cinfo2=codebaseName($row1[c_code2]);
			$dept=codebaseName($row1[m_dept]);
			if ($row1[base_rate] == "USD") {
				$sign = "U$";
			} else if ($row1[base_rate] == "CAD") {
				$sign = "C$";
			} else {
				$sign = "";
			} 
			if ($row1[p_day]==1) {
				$day = "당일";
				 if ($row1[price_0dadult] == "") {
					$sign = "";
				 }
				 if ($row1[price_0dadult] == "문의") {
					$sign = "";
				 }
				$dprice = $row1[price_0dadult];
			} else {
				 if ($row1[price_2dadult] == "") {
					$sign = "";
				 }
				 if ($row1[price_2dadult] == "문의") {
					$sign = "";
				 }
				$dprice = $row1[price_2dadult];
				$day = $row1[p_day];
			}
			echo "<tr bgcolor=#FFFFFF>
			    <td> <input type='checkbox' name='seqNo[]' value='$k' /></td>
				<td align=center><input type='hidden' name='pcode[$k]' value='$row1[p_code]'><input type=text name='pos[$k]' class='form-control text-right' value='$row1[pos]'></td>
				<td align=center>$cinfo1[comment]/$cinfo2[comment]</td>
				<td align=center>$row1[p_code]</td>
				<td align=left>&nbsp;$row1[p_name]</td>
				<td align=center>$day</td>
				<td align=center>$row1[p_cnt]</td>
				<td align=center>$dept[comment]</td>
				<td align=center>&nbsp;$sign $dprice</td>
				<td align=center><a href=base_product_m.php?division=$division&pdx=$pdx&sub=$sub&pcode=$row1[p_code]&ty=$ty>수정</a> | <a href=\"javascript:del('$row1[p_code]','$ty')\">삭제</a>| <a href=\"javascript:copy('$row1[p_code]','$ty')\">복사</a></td>
			</tr>";
			$k++;
		}

	}
	if ($ty == 1) {
        $pcap = "로컬상품등록";
	} else if ($ty == 2) {
        $pcap = "인바운드";
	} else if ($ty == 3) {
        $pcap = "인바운드";
	} else if ($ty == 4) {
        $pcap = "인센티브";
	} else if ($ty == 5) {
        $pcap = "아웃바운드";
	}
	
?>
     
	<div id="contentwrapper">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb 
			module">
				<ul>
					<li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li>
					<li><a href="#">상품관리</a></li>
					<li><a href="#">상품등록</a></li>
					<li><?=$pcap?></li>
				</ul>
			</div>
			<div class="row">
				<div class="col-sm-12 col-md-12">
					<form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&ty=<?=$ty?>" enctype="multipart/form-data" name="base_code" id="base_code" method="post">
						<input type="hidden" name="mode" value="search">
						<table class="table table-striped table-bordered table-condensed js-prod1">
							<tbody>
							<tr>
								<td width=10%  class="titletd" style="vertical-align: middle;">검색어 </td>
								<td width=20% style='border:0;' class="conttd"><input width=30%  type="text" id="prod_code" name="search" class="inpubase lg" value="<?=$prod_code?>"/></td>
								<td width=5%  class="conttd"><button type='submit' class="btn btn-primary btn-sm btn1">검색</button> </td>
								<td class="conttd"><a href='base_product_m.php?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&ty=<?=$ty?>' class="btn btn-primary btn-sm btn1">추가</a> </td>
							</tr> 
							</tbody>
						</table>
					</form>
					<form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&ty=<?=$ty?>" enctype="multipart/form-data" id="frmprod"  method="post">
					<input type="hidden" name="mode1" id="mode1" value="save">
					<input type=hidden name=view_position value="">
						<table class="table table-striped table-bordered table-condensed js-prod1">
								<tbody>
								<tr>
									<td width="10%" class="titletd text-center ">
									   <select class="form-control" name="displaysel">
											<option value="" selected>- 진열선택 -</option>
											<option value="MAIN_BEST"  >[메인]가장 인기있는 여행</option>
											
											
										</select> </td>
									
									<td width=5%  class="conttd"><button type='button' class="btn btn-primary btn-sm btn1 js-psave">일괄지정</button> </td>
									<td class="conttd"><button type='button' class="btn btn-primary btn-sm btn1 js-usave">업데이트</button> </td>
								</tr> 

								</tbody>
							</table>
					<table id='ctable' class="table table-striped table-bordered mediaTable js-productListTable">
						<thead>
							<tr>
							    <th><input type="checkbox" id="selectAll" /></th>
								<th width='2%' class="essential" align="center">상품위치</th>
							    <th width='10%' class="essential" align="center">지역분류</th>
								<th width='10%' class="essential" align="center">상품코드</th>
								<th width='20%' class="essential" align="center">상품명</th>
								<th width='10%' class="essential" align="center">여행기간</th>
								<th width='10%' class="essential" align="center">투어정원</th>
								<th width='10%' class="essential" align="center">상품관리지사</th>
								<th width='10%' class="essential" align="center">표시용성인가격<br />(2인1실/당일)</th>
								<th width='10%' class="essential" data-orderable="false">수정 | 삭제</th>
							</tr>
						</thead> 
						<?php printProdut($ty); ?>
					</table>
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
			});
			$(".dataTables_length").css({ "display" :"none" });
			$('.js-usave').click(function(e){
				if (confirm("업데이트 하시겠습니까?"))
				{
					$("#mode1").val("save");
				    $("#frmprod").submit();
				}
				

			});
			$('.js-psave').click(function(e){
				if (confirm("지정 하시겠습니까?"))
				{
					$("#mode1").val("save");
					$("#frmprod").attr('action', 'p_display.php?division=9&pdx=2&sub=10'); 
				    $("#frmprod").submit();
				}
				

			});
		})
		function del(id,ty) {
			if (confirm("삭제할까요?") == true) {
				location.replace('base_product.php?Mode=del&division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&pcode=' + id+'&ty='+ty);
			}
			else return;
		}
		function copy(id,ty) {
			if (confirm("복사할까요?") == true) {
				location.replace('base_product_m.php?Mode=copy&division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&pcode=' + id+'&ty='+ty);
			}
			else return;
		}
	</script>
    </body>
</html>
