
<?php
    include "include/header.php";
	
	if ($_COOKIE[MEMLOGIN_ADMIN_PURUN] != "") {
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
	if($Mode == "del")
	{
		$qry1 = "delete from product_master where p_code= '$pcode'";
		$rst1 = mysql_query($qry1,$dbConn);

		$qry1 = "delete from product_details where p_code= '$pcode'";
		$rst1 = mysql_query($qry1,$dbConn);
		
		$qry1 = "delete from product_limit where p_code= '$pcode'";
		$rst1 = mysql_query($qry1,$dbConn); 

		$qry1 = "delete from product_details_local where p_code= '$pcode'";
		$rst1 = mysql_query($qry1,$dbConn);

		$qry1 = "delete from product_pick where p_code= '$pcode'";
		$rst1 = mysql_query($qry1,$dbConn);

	}
	function printProdut($ty) {
		global $dbConn, $division, $pdx, $sub, $search;

		
		if ($search) {
		    $qrycom = "&& (p_code like '%$search%' || p_name like '%$search%')";
	    }else {
			$qrycom = "";
		}

		$qry1 = "select * from product_master where 1=1 && p_type='$ty' $qrycom order by p_code , p_name asc ";
		$rst1 = mysql_query($qry1,$dbConn);
		//echo $qry1;	
		while($row1 = mysql_Fetch_assoc($rst1)){
			$cinfo1=codebaseName($row1[c_code1]);
			$cinfo2=codebaseName($row1[c_code2]);
			if ($row1[p_day]==1) {
				$day = "당일";
				$dprice = $row1[price_0dadult];
			} else {
				$dprice = $row1[price_4dadult];
				$day = $row1[p_day];
			}
			echo "<tr bgcolor=#FFFFFF>
				<td align=center>$cinfo1[comment]:$cinfo2[comment]</td>
				<td align=center>$row1[p_code]</td>
				<td align=left>&nbsp;$row1[p_name]</td>
				<td align=center>$day</td>
				<td align=center>$row1[p_cnt]</td>
				<td align=center>$row1[base_rate]</td>
				<td align=left>&nbsp;$dprice</td>
				<td align=center><a href=base_product_m.php?division=$division&pdx=$pdx&sub=$sub&pcode=$row1[p_code]&ty=$ty>수정</a> | <a href=\"javascript:del('$row1[p_code]','$ty')\">삭제</a></td>
			</tr>"; 
		}

	}
	if ($ty == 1) {
        $pcap = "단일상품등록";
	} else if ($ty == 2) {
        $pcap = "복합상품등록";
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
						<table class="table table-striped table-bordered table-condensed">
							<tbody>
							<tr>
								<td width=10%  class="titletd" style="vertical-align: middle;">검색어 </td>
								<td width=20% style='border:0;' class="conttd"><input width=30%  type="text" id="prod_code" name="search" class="inpubase lg" value="<?=$prod_code?>"/></td>
								<td width=5%  class="conttd"><button type='submit' class="btn btn-primary btn-sm btn1 btnatt">검색</button> </td>
								<td class="conttd"><a href='base_product_m.php?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&ty=<?=$ty?>' class="btn btn-primary btn-sm btn1 btnatt">추가</a> </td>
							</tr> 
							</tbody>
						</table>
					</form>
					<table class="table table-striped table-bordered mediaTable js-productListTable">
						<thead>
							<tr>
							    <th width='10%' class="essential" align="center">상품분류</th>
								<th width='10%' class="essential" align="center">상품코드</th>
								<th width='30%' class="essential" align="center">상품명</th>
								<th width='10%' class="essential" align="center">여행기간</th>
								<th width='10%' class="essential" align="center">투어정원</th>
								<th width='10%' class="essential" align="center">기준통화</th>
								<th width='10%' class="essential" align="center">표시용성인가격<br />(4인1실/당일)</th>
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
			pt.initProductList()
			$(".dataTables_length").css({ "display" :"none" });
		})
		function del(id,ty) {
			if (confirm("삭제할까요?") == true) {
				location.replace('base_product.php?Mode=del&division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&pcode=' + id+'&ty='+ty);
			}
			else return;
		}
	</script>
    </body>
</html>
