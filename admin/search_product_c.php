<?php
	include "include/inc_base.php";
    if ($_COOKIE[MEMLOGIN_ADMIN_HELLO] !="") {
	} else {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}



?>
<!DOCTYPE html>
<html>

<head>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
	<title>Invoice</title>
	<link rel="stylesheet" href="bootstrap/css/bootstrap.min.css" />
	<link rel="stylesheet" href="bootstrap/css/bootstrap-theme.min.css" />
	<link rel="stylesheet" href="css/normalize.css" />
	<link href="https://fonts.googleapis.com/css?family=Montserrat|Open+Sans|Roboto&display=swap" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css?family=Nanum+Gothic" rel="stylesheet">
	<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
	<link rel="stylesheet" href="/resources/demos/style.css">
	<link href="css/invoice-f.css" rel="stylesheet" id="invoice-css">

	<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
  <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>


</head>


<script language="javascript">
<!--
var prod_item = "";

function getchklimit(f)
{
	
   choice(f);
	
	
}
function choice(f)
{

	var opform = opener.document.all;

	opform.p_code.value = f.p_code.value;
	opform.p_name.value = f.p_name.value;
		
	window.close();
	
	
}

function choice_hotel(f)
{

	var opform = opener.document.product;
	/*
	opform.p_code.value = f.p_code.value;
	opform.p_name.value = f.p_name.value;

	opform.product_price_adult.value = f.normal_adult_price.value;
	opform.product_price_child.value = f.normal_child_price.value;
	opform.product_price_baby.value = f.normal_baby_price.value;
	*/

	opener.location.replace('hotel_batch_status.php?division=4&p_code=' + f.p_code.value);


	window.close();
}

function choice_carbatch(f)
{

	var opform = opener.document.product;

	

	opener.location.replace('car_batch.php?division=4&p_code=' + f.p_code.value + '&StartYMD=' + f.start_date.value);
	
	//opener.location.replace('car_batch.php?division=4&p_code=' + f.p_code.value);


	window.close();
}



function choice_guide(f)
{

	var opform = opener.document.product;
	
	opener.location.replace('guide_choose.php?division=4&p_code=' + f.p_code.value);


	window.close();
}




//-->
</script>

<body bgcolor="#FFFFFF" text="#464646" leftmargin="10" topmargin="10" marginwidth="10" marginheight="10">
			
		<table width="100%" border="0" cellspacing="0" cellpadding="0" class="txt_12">
			<tr>
				<td valign="top">
				<br>
					<table class="table table-striped table-bordered table-condensed">
					
					<form action=<?= $PHP_SELF ?> method=post name=product >
					<input type=hidden name=mode value="SEARCH">
					<input type=hidden name=page value="<?= $page ?>">
					<input type=hidden name=totalCode value="<?= $totalCode ?>">
						<tr>
							<td colspan="2" align="left">&nbsp;&nbsp;상품타입
							<select name=tour_type>
							<option value="1" <? if($p_type == "1") echo "selected"; ?>>Local
							<option value="2" <? if($p_type == "2") echo "selected"; ?>>In-Bound
							<option value="5" <? if($p_type == "5") echo "selected"; ?>>Out-Bound
							<option value="" >전체상품
							
							</select>
							&nbsp;&nbsp;
							검색어 : <input type=text name=customer_keyword size=32 class=form_box value="<?= $customer_keyword ?>">&nbsp;&nbsp;<input type=submit value="검색" class="form_box"></td>
						<tr></form>
							<td colspan="2" height="10"></td>
						</tr>
						<tr>
							<td colspan="2" align="center">
								
								<table class="table table-striped table-bordered table-condensed">

									<?php
									$i=1;
									

									if($customer_keyword)
									{
										$keyword_qry = "&& (p_code like '%$customer_keyword%' || p_name like '%$customer_keyword%')";
									}

									
									if($tour_type)
									{
										switch($tour_type)
										{
											case "1":
												$tour_type_qry = "&& p_type = '1'";
												break;
											case "2":
												$tour_type_qry = "&& p_type = '2'";
												break;
											case "5":
												$tour_type_qry = "&& p_type = '5'";
												break;
											
												
										}
									}


									

									$zip_qry1 = "select * from product_master where 1=1 $startWeek_qry $keyword_qry $tour_type_qry  order by p_type desc, p_name asc";
									//print_r($zip_qry1);

									$zip_rst1 = $dbConn->query($zip_qry1);
									?>
									<tr>
										<td width="10%" align="center" height="20" bgcolor="#E3E3E3">구분</td>
										<td width="15%" align="center" height="20" bgcolor="#E3E3E3">코드</td>
										<td width="35%" align="center" height="20" bgcolor="#E3E3E3">상품명</td>
										<td width="15%" align="center" height="20" bgcolor="#E3E3E3">출발일</td>
										
									</tr>
									

									<?
									while($row = $zip_rst1->fetch_assoc()){

									// 요일날짜

									$week1 = array("0", "1", "2","3","4","5","6","9");
									$week2   = array("일","월", "화", "수","목","금","토","매일");

									$row[p_week] = str_replace($week1, $week2, $row[p_week]);



									switch($row[p_type])
									{
										case "2":
											$tour_type = "<font color=#000000>인바운드</font>";
											break;
										case "5":
											$tour_type = "<font color=green>아웃바운드</font>";
											break;
										case "1":
											$tour_type = "<font color=red>로컬</font>";
											break;
										
									}

									// 시작일 StartYMD + 추가일 $row[p_day_cnt] 
									$start_date = explode("-",$StartYMD);
									$add_date = $row[p_day]-1;

									$stop_date  = date("Y-m-d",mktime (0,0,0,$start_date[1]  , $start_date[2]+$add_date, $start_date[0]));	

									// 
									?>


									<form name="form<?=$i?>" >
									<input type="hidden" name="p_code" value="<?= $row[p_code] ?>">
									<input type="hidden" name="p_name" value="<?= $row[p_name] ?>">
									
									
									<input type=hidden name=page value="<?= $page ?>">
									
									<tr bgcolor="#FFFFFF"  style="cursor:pointer;" onMouseOver="this.style.backgroundColor='#E3E3E3'" onMouseOut="this.style.backgroundColor=''" <? if($page == "guide"): ?>onclick="javascript:choice_guide(document.form<?=$i?>);"<? elseif($page == "hotel"): ?>onclick="javascript:choice_hotel(document.form<?=$i?>);"<? elseif($page == "carbatch"): ?>onclick="javascript:choice_carbatch(document.form<?=$i?>);"<? else: ?>onclick="javascript:getchklimit(document.form<?=$i?>);"<? endif; ?> >
										<td width="10%" align="left" height="28">&nbsp;<b><?= $tour_type?></b></td>
										<td width="15%" align="center" height="28"><?= $row[p_code]?></td>
										<td width="35%" height="20">&nbsp;&nbsp;<?=$row[p_name]?>  (<?= $row[p_day] ?>일)
										<td width="15%">&nbsp;<?= $row[p_week] ?></td>
										
										
									</tr>
									</form>
									<?
									$i++;
									}

									if($i == "1")
									{
										echo "<tr><td colspan=5 bgcolor=#F6F6F6 height=50 align=center>검색결과가 없습니다.</td></tr>";
									}

									?>
								</table>
								
							</td>
						</tr>
						<tr>
							<td colspan="2">&nbsp;</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>

</body>
</html>