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

    if($StartYMD)
	{
		$stDate = "$StartYMD 00:00:00";
		$stop_date = "$EndYMD 23:23:59";	

		
		$orderdate_qry = "&& revDate >=  '$stDate' and revDate <= '$stop_date' 
		group by a.userid,a.p_code   "; 
		
		$orderdate_qrys = "&& a.revDate >=  '$stDate' and revDate <= '$stop_date' 
		group by p_code   ";
		
		$qrydesc1 ="&& a.revDate >=  '$stDate' and revDate <= '$stop_date' 
		 group by a.p_code,b.c_code1 order by b.c_code1,b.c_code2 asc";
		$qrydescr ="&& a.revDate >=  '$stDate' and revDate <= '$stop_date' ";
	}
	if($StartYMD1)
	{
		$stDate1 = "$StartYMD1 00:00:00";
		$stop_date1 = "$EndYMD1 23:23:59";	
		$stDate = "$StartYMD1 00:00:00";
		$stop_date = "$EndYMD1 23:23:59";	
		
		
	    $orderdate_qry1 = "&& a.stDate  >= '$stDate1' and a.stDate <= '$stop_date1' 
		group by a.userid,a.p_code   ";
		$orderdate_qrys1 = "&& stDate  >= '$stDate1' and stDate <= '$stop_date1' 
		group by p_code   ";
		$qrydesc2 = "&& a.stDate  >= '$stDate1' and a.stDate <= '$stop_date1' 
		 group by p_code,b.c_code1,b.c_code2 order by b.c_code1,b.c_code2 asc";
		$qrydescs = "&& a.stDate  >= '$stDate1' and a.stDate <= '$stop_date1' ";
	}

      
	
 
  if($Mode == "SEARCH")
  {
      
			
      
		$num1 = 0;

		$total_member = 0;
		$total_sum = 0;
		$total_profit = 0;
		$total_bal = 0;
      
        $qry ="select  distinct b.p_code,b.p_name,b.c_code1,b.c_code2
         from reserve_info a left outer join 
					product_master b on a.p_code=b.p_code 
					where b.p_type = '5' && a.parent = 'MAIN' && a.rev_status in ('DONE') && a.settle_report != '0'  $qrydesc1
					$qrydesc2";
      
		$rst = $dbConn->query($qry);
		$datanum = $rst->num_rows;
		$numk = 0;	
		$gtot_mem = 0;
		$m1 = 0;
		$m2 = 0;
		$m3 = 0;
		$m4 = 0;
		$m5 = 0;
		$m6 = 0;
		$m7 = 0;
		$m8 = 0;
		$m9 = 0;
		
		//////////
		$s1 = 0;
		$s2 = 0;
		$s3 = 0;
		$s4 = 0;
		$s5 = 0;
		$s6 = 0;
		$s7 = 0;
		$s8 = 0;
		$s9 = 0;
		
		//////////
		$p1 = 0;
		$p2 = 0;
		$p3 = 0;
		$p4 = 0;
		$p5 = 0;
		$p6 = 0;
		$p7 = 0;
		$p8 = 0;
		$p9 = 0;
		
		
		while($row = $rst->fetch_assoc()){
			 $rcategory = codebaseName($row[c_code1]);
			 $gtot_mem = 0; 		
			 $pgtot_amt = 0;
			 $pgtot_amt1 = 0;
			 if (($oldc3 != $row[c_code1]) && ($numk != 0)) {
				$gtot_mem = $m1+$m2+$m3+$m4+$m5+$m6+$m7+$m8+$m9; 
				$pgtot_amt = $s1+$s2+$s3+$s4+$s5+$s6+$s7+$s8+$s9;
				$pgtot_amt1 = $p1+$p2+$p3+$p4+$p5+$p6+$p7+$p8+$p9;
				if (($pgtot_amt1 == 0) && ($gtot_mem==0)){
					$perprofit= 0;
				} else {
					$perprofit= $pgtot_amt1/$gtot_mem;

				}
				if (($p1 == 0) && ($m1==0)){
					$perprofit1= 0;
				} else {
					$perprofit1= $p1/$m1;

				}
				if (($p2 == 0) && ($m2==0)){
					$perprofit2= 0;
				} else {
					$perprofit2= $p2/$m2;

				}
				if (($p3 == 0) && ($m3==0)){
					$perprofit3= 0;
				} else {
					$perprofit3= $p3/$m3;

				}
				if (($p4 == 0) && ($m4==0)){
					$perprofit4= 0;
				} else {
					$perprofit4= $p4/$m4;

				}
				if (($p5 == 0) && ($m5==0)){
					$perprofit5= 0;
				} else {
					$perprofit5= $p5/$m5;

				}
				
				$perprofit5 = $p5/$m5;
				$perprofit6 = $p6/$m6;
				$perprofit7 = $p7/$m7;
				$perprofit8 = $p8/$m8;
				$perprofit9 = $p9/$m9;
				$content .= "<tr bgcolor=#F4FBC6>";
				$content .= "<td  align=center width='15px' colspan=2 height=25><b>소계2 </b></td>";     
				$content .= "<td align=center><b>$gtot_mem</b></td>";
				$content .=	"<td align=right><b>$" . number_format($pgtot_amt, 2) . "&nbsp;</b></td>
									   <td align=right><b>$" . number_format($pgtot_amt1, 2) . "&nbsp;</b></td>";
				$content .= "<td align=center><b>$" . number_format($perprofit, 2) . "&nbsp;</b></td>";
				$content .= "<td align=center><b>$m1</b></td>";
				$content .=	"<td align=right><b>$" . number_format($s1, 2) . "&nbsp;</b></td>
									   <td align=right><b>$" . number_format($p1, 2) . "&nbsp;</b></td>";
				$content .= "<td align=center><b>$" . number_format($perprofit1, 2) . "&nbsp;</b></td>";
				$content .= "<td align=center><b>$m2</b></td>";
				$content .=	"<td align=right><b>$" . number_format($s2, 2) . "&nbsp;</b></td>
									   <td align=right><b>$" . number_format($p2, 2) . "&nbsp;</b></td>";
				
				$content .= "<td align=center><b>$" . number_format($perprofit2, 2) . "&nbsp;</b></td>";
				$content .= "<td align=center><b>$m3</b></td>";
				$content .=	"<td align=right><b>$" . number_format($s3, 2) . "&nbsp;</b></td>
									   <td align=right><b>$" . number_format($p3, 2) . "&nbsp;</b></td>";
				$content .= "<td align=center><b>$" . number_format($perprofit3, 2) . "&nbsp;</b></td>";
				$content .= "<td align=center><b>$m4</b></td>";
				$content .=	"<td align=right><b>$" . number_format($s4, 2) . "&nbsp;</b></td>
									   <td align=right><b>$" . number_format($p4, 2) . "&nbsp;</b></td>";
				$content .= "<td align=center><b>$" . number_format($perprofit4, 2) . "&nbsp;</b></td>";
				$content .= "<td align=center><b>$m5</b></td>";
				$content .=	"<td align=right><b>$" . number_format($s5, 2) . "&nbsp;</b></td>
									   <td align=right><b>$" . number_format($p5, 2) . "&nbsp;</b></td>";
				$content .= "<td align=center><b>$" . number_format($perprofit5, 2) . "&nbsp;</b></td>";
				$content .= "<td align=center><b>$m6</b></td>";
				$content .=	"<td align=right><b>$" . number_format($s6, 2) . "&nbsp;</b></td>
									   <td align=right><b>$" . number_format($p6, 2) . "&nbsp;</b></td>";
				$content .= "<td align=center><b>$" . number_format($perprofit6, 2) . "&nbsp;</b></td>";
				$content .= "<td align=center><b>$m7</b></td>";
				$content .=	"<td align=right><b>$" . number_format($s7, 2) . "&nbsp;</b></td>
									   <td align=right><b>$" . number_format($p7, 2) . "&nbsp;</b></td>";
				$content .= "<td align=center><b>$" . number_format($perprofit7, 2) . "&nbsp;</b></td>";
				$content .= "<td align=center><b>$m8</b></td>";
				$content .=	"<td align=right><b>$" . number_format($s8, 2) . "&nbsp;</b></td>
									   <td align=right><b>$" . number_format($p8, 2) . "&nbsp;</b></td>";
				$content .= "<td align=center><b>$" . number_format($perprofit8, 2) . "&nbsp;</b></td>";
				$content .= "<td align=center><b>$m9</b></td>";
				$content .=	"<td align=right><b>$" . number_format($s9, 2) . "&nbsp;</b></td>
									   <td align=right><b>$" . number_format($p9, 2) . "&nbsp;</b></td>";
				$content .= "<td align=center><b>$" . number_format($perprofit9, 2) . "&nbsp;</b></td>";

				
							
				$content .= "</tr>";
				

                $gm_total= $gm_total + $gtot_mem; 
				$gm_stot = $gm_stot +  $pgtot_amt;
				$gm_ptot = $gm_ptot +  $pgtot_amt1;
				
				$mem_tot1 =$mem_tot1 + $m1;
				$mem_tot2 =$mem_tot2 + $m2;
				$mem_tot3 =$mem_tot3 + $m3;
				$mem_tot4 =$mem_tot4 + $m4;
				$mem_tot5 =$mem_tot5 + $m5;
				$mem_tot6 =$mem_tot6 + $m6;
				$mem_tot7 =$mem_tot7 + $m7;
				$mem_tot8 =$mem_tot8 + $m8;
				$mem_tot9 =$mem_tot9 + $m9;


				$gm_s1 =  $gm_s1 + $s1;
				$gm_s2 =  $gm_s2 + $s2;
				$gm_s3 =  $gm_s3 + $s3;
				$gm_s4 =  $gm_s4 + $s4;
				$gm_s5 =  $gm_s5 + $s5;
				$gm_s6 =  $gm_s6 + $s6;
				$gm_s7 =  $gm_s7 + $s7;
				$gm_s8 =  $gm_s8 + $s8;
				$gm_s9 =  $gm_s9 + $s9;

				$gm_p1 = $gm_p1 + $p1;
				$gm_p2 = $gm_p2 + $p2;
				$gm_p3 = $gm_p3 + $p3;
				$gm_p4 = $gm_p4 + $p4;
				$gm_p5 = $gm_p5 + $p5;
				$gm_p6 = $gm_p6 + $p6;
				$gm_p7 = $gm_p7 + $p7;
				$gm_p8 = $gm_p8 + $p8;
				$gm_p9 = $gm_p9 + $p9;

				//////////////////////////////

				$m1 = 0;
				$m2 = 0;
				$m3 = 0;
				$m4 = 0;
				$m5 = 0;
				$m6 = 0;
				$m7 = 0;
				$m8 = 0;
				$m9 = 0;
				
				//////////
				$s1 = 0;
				$s2 = 0;
				$s3 = 0;
				$s4 = 0;
				$s5 = 0;
				$s6 = 0;
				$s7 = 0;
				$s8 = 0;
				$s9 = 0;
				
				//////////
				$p1 = 0;
				$p2 = 0;
				$p3 = 0;
				$p4 = 0;
				$p5 = 0;
				$p6 = 0;
				$p7 = 0;
				$p8 = 0;
				$p9 = 0;
				
							
				
				
				
			 } 
				   
				
			$content .= "<tr bgcolor=#FFFFFF>";
						
			if (($oldc3 != $row[c_code1]) || ($numk == 0)) { 
					$content .= "<td  align=left style='width:20px;' height=25><b>&nbsp;".$rcategory[comment]."</b></td>";
			} else {
				
				   $content .= "<td  align=left style='width:200px;' height=25>&nbsp;</td>";
			}
			 
			  $content .=" <td  align=left style='width:150px;' height=25><b>&nbsp;".$row[p_name]."</b></td>";

			 
			  $qry1 ="
			 select  b.p_code,a.kor_name,b.total_mem1,b.last_sum_amt,b_1.sum_airline,b_1.air_sales_sum,b_1.air_net_sum,
				   d.rand_sum,f.agent_sum,h.cagent_sum,g.etc_sum,i.cc_proc_fee,j.rtnamt,k.cc_proc_fee2 from  
			  member_list a 
			  left outer join
			  (SELECT a.p_code,a.userid,sum(a.p_cnt) AS total_mem1,sum(a.last_total) AS last_sum_amt FROM reserve_info a ,product_master c WHERE  a.p_code=c.p_code && c.p_type = '5' && parent = 'MAIN'  && a.rev_status in ('PLAY','DONE') && settle_report != '0'  && a.p_code='$row[p_code]'
			   $qrydescr $qrydescs group by a.p_code,a.userid) b
				on a.userid=b.userid
			  left outer join
			  (SELECT a.userid,SUM(b.a_airline_amt) AS sum_airline
			 ,SUM(b.a_airline_amt + CASE WHEN b.rand_fee IS NULL THEN 0 ELSE b.rand_fee END) AS air_sales_sum,   
			  SUM(b.a_amt) AS air_net_sum FROM reserve_info a LEFT OUTER JOIN  
			  reserve_airline_pnr b ON a.reserveCode=b.reserveCode ,product_master c WHERE  a.p_code=c.p_code && c.p_type = '5' && parent = 'MAIN'  && a.rev_status in ('PLAY','DONE') && settle_report != '0'  && a.p_code='$row[p_code]'
			   $qrydescr $qrydescs group by a.p_code,a.userid) b_1
				on a.userid=b_1.userid
			   left outer join 
			   (select a.p_code,a.userid,sum(amt) as rand_sum
			   from reserve_info a,rand_company_tmp b,product_master c
				where a.reserveCode=b.reserveCode && a.p_code=c.p_code && c.p_type ='5' && a.parent = 'MAIN' && a.rev_status in ('PLAY','DONE') && settle_report != '0' && a.p_code='$row[p_code]'
				&& b.money_type = 'debit' && b.p_memo != '발권'
			   $qrydescr $qrydescs group by a.p_code,a.userid) d 
				on a.userid=d.userid
               left outer join 
			   (select a.p_code,a.userid,sum(b.amt) as agent_sum
			   from reserve_info a, rand_company_tmp b ,product_master c
			   where a.reserveCode=b.reserveCode && a.p_code=c.p_code && c.p_type ='5' && a.parent = 'MAIN' && a.rev_status in ('PLAY','DONE') && a.settle_report != '0'  && a.p_code='$row[p_code]'
			   && b.money_type = 'credit' && b.p_memo != '발권' 
			   $qrydescr $qrydescs group by a.p_code,a.userid) f 
			   on a.userid=f.userid
			   left outer join 
			   (select a.p_code,a.userid,sum(b.amt) as cagent_sum
			   from reserve_info a, rand_company_tmp b ,product_master c
			   where a.reserveCode=b.reserveCode && a.p_code=c.p_code && c.p_type ='5' && a.parent = 'MAIN' && a.rev_status in ('PLAY','DONE') && a.settle_report != '0'  && a.p_code='$row[p_code]'
			   && b.money_type = 'credit' && b.p_memo != '발권' && b.base_rate ='cmo'
			   $qrydescr $qrydescs group by a.p_code,a.userid) h 
			   on a.userid=h.userid
			   left outer join 
			   (select a.p_code,a.userid,sum(b.dis_pay) as etc_sum
				from reserve_info a, reserve_traveler b ,product_master c
				where a.reserveCode=b.reserveCode && a.p_code=c.p_code && c.p_type ='5' && parent = 'MAIN' && a.rev_status in ('PLAY','DONE') && settle_report <> '0'  && a.p_code='$row[p_code]'
			   $qrydescr $qrydescs group by a.p_code,a.userid) g
			   on a.userid=g.userid
			   left outer join 
			   (select a.p_code,a.userid,sum(b.payment * 0.04) as cc_proc_fee
				from reserve_info a, payment_history b ,product_master c
			   where a.reserveCode=b.reserveCode && a.p_code=c.p_code && c.p_type ='5' && parent = 'MAIN' && a.rev_status in ('PLAY','DONE') && a.settle_report != '0'  && a.p_code='$row[p_code]' && b.payment_status = 'DONE' && b.pay_method = 'bcreditcard'
			   $qrydescr $qrydescs group by a.p_code,a.userid) i
			   on a.userid=i.userid 
			   left outer join 
			   (select a.p_code,a.userid,sum(b.payment * 0.04) as cc_proc_fee2
				from reserve_info a, payment_history b ,product_master c
			   where a.reserveCode=b.reserveCode && a.p_code=c.p_code && c.p_type ='5' && parent = 'MAIN' && a.rev_status in ('PLAY','DONE') && a.settle_report != '0'  && a.p_code='$row[p_code]' && b.payment_status = 'DONE' && b.pay_method = 'creditcard'
			   $qrydescr $qrydescs group by a.p_code,a.userid) k
			   on a.userid=k.userid
			   left outer join 
			   (select a.p_code,a.userid,sum(b.payment) as rtnamt
				from reserve_info a, payment_history b ,product_master c
			   where a.reserveCode=b.reserveCode && a.p_code=c.p_code && c.p_type ='5' && parent = 'MAIN' && a.rev_status in ('PLAY','DONE') && a.settle_report != '0'  && a.p_code='$row[p_code]' && b.payment_status = 'RETURN' 
			   $qrydescr $qrydescs group by a.p_code,a.userid) j
			   on a.userid=j.userid 
			   where a.c_part1='D013000' && ((a.out_yn is null ) || (a.userid in ('alexkang','bonniekim'))) order by a.kor_name DESC";

			 //  echo $qry1."<br /><br />";
		     //exit;
			  $tot_mem = 0;
			  $k=0;
              $rst1 = $dbConn->query($qry1);
			  $content1 = "";
			  $content2 = "";
			  $m_tot = 0;
              $s_tot = 0;
			  $p_tot = 0;
			  while($row1 = $rst1->fetch_assoc()){
				    $perpro= 0;
				    
					//echo $row1[last_sum_amt]."TEST";	  
					if ($row1[total_mem1] == "")
				    {

                       $total_mem1 = 0;
				    } else {
                       $total_mem1 = $row1[total_mem1] ;

					}
					if ($row1[rtnamt]=="") {
						$row1[rtnamt]= 0.00;
					}
					/*$ob_agent_sales = $row1[last_sum_amt] ;
					$expense = $row1[rand_sum] + $row1[air_net_sum] +      $row1[etc_sum]+$row1[cc_proc_fee]+$row1[rtnamt];
					$ob_agent_profit = ($ob_agent_sales - $expense); */
                    
					$ob_agent_sales = $row1[last_sum_amt]+ $row1[rand_sales_sum] + ($row1[air_sales_sum] - $row1[sum_airline]) ;
					
					$ob_agent_profit = ($ob_agent_sales+$row1[cagent_sum]) - ($row1[rand_sum] + $row1[air_net_sum] + $row1[rtnamt]+$row1[cc_proc_fee] +$row1[cc_proc_fee2]);
					//echo $row1[p_code]."|".$ob_agent_sales."|".$row1[agent_sum]."|P".$row1[rand_sum]."|".$row1[rand_sum]."|n". $row1[air_net_sum]."|e".$row1[etc_sum]."|c". $row1[cc_proc_fee]."|r".$row1[rtnamt]."<br/>";
					//exit;
					switch ($k)
				    {
                        case 0 :
							$m1 =$m1 +$total_mem1;
						    $s1 =$s1 +$ob_agent_sales;
							$p1 =$p1 +$ob_agent_profit;
						    break;
                        case 1 :
							$m2 =$m2 +$total_mem1;
						    $s2 =$s2 +$ob_agent_sales;
							$p2 =$p2 +$ob_agent_profit;
						    break;
						case 2 :
							$m3 =$m3 +$total_mem1;
						    $s3 =$s3 +$ob_agent_sales;
							$p3 =$p3 +$ob_agent_profit;
						    break;
						case 3 :
							$m4 =$m4 +$total_mem1;
						    $s4 =$s4 +$ob_agent_sales;
							$p4 =$p4 +$ob_agent_profit;
						    break;
						case 4 :
							$m5 =$m5 +$total_mem1;
						    $s5 =$s5 +$ob_agent_sales;
							$p5 =$p5 +$ob_agent_profit;
						    break;
						case 5 :
							$m6 =$m6 +$total_mem1;
						    $s6 =$s6 +$ob_agent_sales;
							$p6 =$p6 +$ob_agent_profit;
						    break;
						case 6 :
							$m7 =$m7 +$total_mem1;
						    $s7 =$s7 +$ob_agent_sales;
							$p7 =$p7 +$ob_agent_profit;
						    break;
						case 7 :
							$m8 =$m8 +$total_mem1;
						    $s8 =$s8 +$ob_agent_sales;
							$p8 =$p8 +$ob_agent_profit;
						    break;
						case 8 :
							$m9 =$m9 +$total_mem1;
						    $s9 =$s9 +$ob_agent_sales;
							$p9 =$p9 +$ob_agent_profit;
						    break;



				    }
				   
                   $m_tot = $m_tot + $total_mem1; 
				   $s_tot = $s_tot + $ob_agent_sales;
				   $p_tot = $p_tot + $ob_agent_profit;
				   
				   if (($ob_agent_profit == 0) && ($total_mem1==0)){
						$perpro= 0;
				   } else {
					    $perpro = $ob_agent_profit/$total_mem1;

				   }
				   if ($k % 2 == 1) { 
						$content1 .= "<td align=center>$total_mem1 </td>";
						$content1 .= "<td align=right>$" . number_format($ob_agent_sales, 2) . "&nbsp;</td>
									 <td align=right>$" . number_format($ob_agent_profit, 2) . "&nbsp;</td>";
						$content1 .= "<td align=right>$" . number_format($perpro, 2) . "&nbsp;</td>";
						
				   } else {
					    $content1 .= " <td bgcolor=#E7FED6  align=center>$total_mem1</td>";
					    $content1 .= " <td bgcolor=#E7FED6 align=right>$" . number_format($ob_agent_sales, 2) . "&nbsp;</td>
									  <td bgcolor=#E7FED6 align=right>$" . number_format($ob_agent_profit, 2) . "&nbsp;</td>";
						$content1 .= "<td bgcolor=#E7FED6 align=center>$" . number_format($perpro, 2) . "&nbsp;</td>";
				   }												           
				
								
								
							
							
				$k++;				
			}
			if (($p_tot == 0) && ($m_tot==0)){
				$perpro= 0;
		    } else {
				$perpro = $p_tot/$m_tot;
		    }

			
            $content2 .= "<td align=center>" . number_format($m_tot) . "&nbsp;</td>";
			$content2 .= "<td align=right>$" . number_format($s_tot, 2) . "&nbsp;</td>
						 <td align=right>$" . number_format($p_tot, 2) . "&nbsp;</td>";
			$content2 .= "<td align=center>$" . number_format($perpro,2) . "&nbsp;</td>";

            $content .= $content2.$content1;
			$content .= "</tr>";

			$oldc3 = $row[c_code1]; 
            

			$cnt=$numk+1;
			if ($datanum == $cnt) {
				
						$gtot_mem = $m1+$m2+$m3+$m4+$m5+$m6+$m7+$m8+$m9; 
						$pgtot_amt = $s1+$s2+$s3+$s4+$s5+$s6+$s7+$s8+$s9;
						$pgtot_amt1 = $p1+$p2+$p3+$p4+$p5+$p6+$p7+$p8+$p9;
	
						
						if (($pgtot_amt1 == 0) && ($gtot_mem==0)){
							$perprofitot= 0;
					    } else {
							$perprofitot= $pgtot_amt1/$gtot_mem;

					    }
						if (($p1 == 0) && ($m1==0)){
							$perprofitot1= 0;
					    } else {
							$perprofitot1= $p1/$m1;

					    }
						if (($p2 == 0) && ($m2==0)){
							$perprofitot2= 0;
					    } else {
							$perprofitot2= $p2/$m2;

					    }
						if (($p3 == 0) && ($m3==0)){
							$perprofitot3= 0;
					    } else {
							$perprofitot3= $p3/$m3;

					    }
						if (($p4 == 0) && ($m4==0)){
							$perprofitot4= 0;
					    } else {
							$perprofitot4= $p4/$m4;

					    }
						if (($p5 == 0) && ($m5==0)){
							$perprofitot5= 0;
					    } else {
							$perprofitot5= $p5/$m5;

					    }
						$perprofitot5= $p5/$m5;
						$perprofitot6= $p6/$m6;
						$perprofitot7= $p7/$m7;
						$perprofitot8= $p8/$m8;
						$perprofitot9= $p9/$m9;
						$content .= "<tr bgcolor=#F4FBC6>";
						$content .= "<td  align=center width='15px' colspan=2 height=25><b>소계 </b></td>";     
						
						$content .= "<td align=center><b>$gtot_mem </b></td>";
						$content .=	"<td align=right><b>$" . number_format($pgtot_amt, 2) . "&nbsp;</b></td>
											   <td align=right><b>$" . number_format($pgtot_amt1, 2) . "&nbsp;</b></td>";
						$content .= "<td align=center><b>$" . number_format($perprofitot, 2) . "&nbsp;</b></td>";
						$content .= "<td align=center><b>$m1</b></td>";
						$content .=	"<td align=right><b>$" . number_format($s1, 2) . "&nbsp;</b></td>
											   <td align=right><b>$" . number_format($p1, 2) . "&nbsp;</b></td>";
						$content .= "<td align=center><b>$" . number_format($perprofitot1, 2) . "&nbsp;</b></td>";
						$content .= "<td align=center><b>$m2</b></td>";
						$content .=	"<td align=right><b>$" . number_format($s2, 2) . "&nbsp;</b></td>
											   <td align=right><b>$" . number_format($p2, 2) . "&nbsp;</b></td>";
						$content .= "<td align=center><b>$" . number_format($perprofitot2, 2) . "&nbsp;</b></td>";
						$content .= "<td align=center><b>$m3</b></td>";
						$content .=	"<td align=right><b>$" . number_format($s3, 2) . "&nbsp;</b></td>
											   <td align=right><b>$" . number_format($p3, 2) . "&nbsp;</b></td>";
						$content .= "<td align=center><b>$1" . number_format($perprofitot3, 2) . "&nbsp;</b></td>";
						$content .= "<td align=center><b>$m4</b></td>";
						$content .=	"<td align=right><b>$" . number_format($s4, 2) . "&nbsp;</b></td>
											   <td align=right><b>$" . number_format($p4, 2) . "&nbsp;</b></td>";
						$content .= "<td align=center><b>$" . number_format($perprofitot4, 2) . "&nbsp;</b></td>";
						$content .= "<td align=center><b>$m5</b></td>";
						$content .=	"<td align=right><b>$" . number_format($s5, 2) . "&nbsp;</b></td>
											   <td align=right><b>$" . number_format($p5, 2) . "&nbsp;</b></td>";
						$content .= "<td align=center><b>$" . number_format($perprofitot5, 2) . "&nbsp;</b></td>";
						$content .= "<td align=center><b>$m6</b></td>";
						$content .=	"<td align=right><b>$" . number_format($s6, 2) . "&nbsp;</b></td>
											   <td align=right><b>$" . number_format($p6, 2) . "&nbsp;</b></td>";
						$content .= "<td align=center><b>$" . number_format($perprofitot6, 2) . "&nbsp;</b></td>";
						$content .= "<td align=center><b>$m7</b></td>";
					    $content .=	"<td align=right><b>$" . number_format($s7, 2) . "&nbsp;</b></td>
											   <td align=right><b>$" . number_format($p7, 2) . "&nbsp;</b></td>";
						$content .= "<td align=center><b>$" . number_format($perprofitot7, 2) . "&nbsp;</b></td>";
						$content .= "<td align=center><b>$m8</b></td>";
						$content .=	"<td align=right><b>$" . number_format($s8, 2) . "&nbsp;</b></td>
											   <td align=right><b>$" . number_format($p8, 2) . "&nbsp;</b></td>";
						$content .= "<td align=center><b>$" . number_format($perprofitot8, 2) . "&nbsp;</b></td>";
						$content .= "<td align=center><b>$m9</b></td>";
						$content .=	"<td align=right><b>$" . number_format($s9, 2) . "&nbsp;</b></td>
											   <td align=right><b>$" . number_format($p9, 2) . "&nbsp;</b></td>";
						$content .= "<td align=center><b>$" . number_format($perprofitot9, 2) . "&nbsp;</b></td>";
											  
						
									
						$content .= "</tr>";

						$gm_total= $gm_total + $gtot_mem; 
					    $gm_stot = $gm_stot +  $pgtot_amt;
						$gm_ptot = $gm_ptot +  $pgtot_amt1;
						
						$mem_tot1 =$mem_tot1 + $m1;
						$mem_tot2 =$mem_tot2 + $m2;
						$mem_tot3 =$mem_tot3 + $m3;
						$mem_tot4 =$mem_tot4 + $m4;
						$mem_tot5 =$mem_tot5 + $m5;
						$mem_tot6 =$mem_tot6 + $m6;
						$mem_tot7 =$mem_tot7 + $m7;
						$mem_tot8 =$mem_tot8 + $m8;
						$mem_tot9 =$mem_tot9 + $m9;
						
						$gm_s1 =  $gm_s1 + $s1;
						$gm_s2 =  $gm_s2 + $s2;
						$gm_s3 =  $gm_s3 + $s3;
						$gm_s4 =  $gm_s4 + $s4;
						$gm_s5 =  $gm_s5 + $s5;
                        $gm_s6 =  $gm_s6 + $s6;
						$gm_s7 =  $gm_s7 + $s7;
						$gm_s8 =  $gm_s8 + $s8;
						$gm_s9 =  $gm_s9 + $s9;

						$gm_p1 = $gm_p1 + $p1;
						$gm_p2 = $gm_p2 + $p2;
						$gm_p3 = $gm_p3 + $p3;
						$gm_p4 = $gm_p4 + $p4;
						$gm_p5 = $gm_p5 + $p5;
						$gm_p6 = $gm_p6 + $p6;
						$gm_p7 = $gm_p7 + $p7;
						$gm_p8 = $gm_p8 + $p8;
						$gm_p9 = $gm_p9 + $p9;


						//////////////////////////////
						
						$m1 = 0;
						$m2 = 0;
						$m3 = 0;
						$m4 = 0;
						$m5 = 0;
						$m6 = 0;
						$m7 = 0;
						$m8 = 0;
						$m9 = 0;
						
						//////////
						$s1 = 0;
						$s2 = 0;
						$s3 = 0;
						$s4 = 0;
						$s5 = 0;
						$s6 = 0;
						$s7 = 0;
						$s8 = 0;
						$s9 = 0;
						
						//////////
						$p1 = 0;
						$p2 = 0;
						$p3 = 0;
						$p4 = 0;
						$p5 = 0;
						$p6 = 0;
						$p7 = 0;
						$p8 = 0;
						$p9 = 0;
					    $gtot_mem = 0;
						
				        /*&**(***************************************************************************/
				
					    
						
						
						if (($gm_ptot == 0) && ($gm_total==0)){
							$gmprofit= 0;
					    } else {
							$gmprofit = $gm_ptot / $gm_total;

					    }
						if (($gm_p1 == 0) && ($mem_tot1==0)){
							$gmprofit1= 0;
					    } else {
							$gmprofit1 = $gm_p1 / $mem_tot1;

					    }if (($gm_p2 == 0) && ($mem_tot2==0)){
							$gmprofit2= 0;
					    } else {
							$gmprofit2 = $gm_p2 / $mem_tot2;

					    }
						if (($gm_p3 == 0) && ($mem_tot3==0)){
							$gmprofit3= 0;
					    } else {
							$gmprofit3 = $gm_p3 / $mem_tot3;

					    }
						if (($gm_p4 == 0) && ($mem_tot4==0)){
							$gmprofit4= 0;
					    } else {
							$gmprofit4 = $gm_p4 / $mem_tot4;

					    }

						if (($gm_p5 == 0) && ($mem_tot5==0)){
							$gmprofit5= 0;
					    } else {
							$gmprofit5 = $gm_p5 / $mem_tot5;

					    }

						$gmprofit5 = $gm_p5 / $mem_tot5;
						$gmprofit6 = $gm_p6 / $mem_tot6;
						$gmprofit7 = $gm_p7 / $mem_tot7;
						$gmprofit8 = $gm_p8 / $mem_tot8;
						$gmprofit9 = $gm_p9 / $mem_tot9;

						$content .= "<tr bgcolor=#FFCA95>";
						$content .= "<td  align=center width='15px' colspan=2 height=35><b>총계 </b></td>";     
						
						$content .= "<td align=center><b>$gm_total</b></td>";
						$content .=	"<td align=right><b>$" . number_format($gm_stot, 2) . "&nbsp;</b></td>
											   <td align=right><b>$" . number_format($gm_ptot, 2) . "&nbsp;</b></td>";
						$content .= "<td align=center><b>$" . number_format($gmprofit, 2) . "&nbsp;</b></td>";
						$content .= "<td align=center><b>$mem_tot1</b></td>";
						$content .=	"<td align=right><b>$" . number_format($gm_s1, 2) . "&nbsp;</b></td>
											   <td align=right><b>$" . number_format($gm_p1, 2) . "&nbsp;</b></td>";
						$content .= "<td align=center><b>$" . number_format($gmprofit1, 2) . "&nbsp;</b></td>";
						$content .= "<td align=center><b>$mem_tot2</b></td>";
						$content .=	"<td align=right><b>$" . number_format($gm_s2, 2) . "&nbsp;</b></td>
											   <td align=right><b>$" . number_format($gm_p2, 2) . "&nbsp;</b></td>";
						$content .= "<td align=center><b>$" . number_format($gmprofit2, 2) . "&nbsp;</b></td>";
						$content .= "<td align=center><b>$mem_tot3</b></td>";
						$content .=	"<td align=right><b>$" . number_format($gm_s3, 2) . "&nbsp;</b></td>
											   <td align=right><b>$" . number_format($gm_p3, 2) . "&nbsp;</b></td>";
						$content .= "<td align=center><b>$" . number_format($gmprofit3, 2) . "&nbsp;</b></td>";
						$content .= "<td align=center><b>$mem_tot4</b></td>";
						$content .=	"<td align=right><b>$" . number_format($gm_s4, 2) . "&nbsp;</b></td>
											   <td align=right><b>$" . number_format($gm_p4, 2) . "&nbsp;</b></td>";
						$content .= "<td align=center><b>$" . number_format($gmprofit4, 2) . "&nbsp;</b></td>";
						$content .= "<td align=center><b>$mem_tot5</b></td>";
						$content .=	"<td align=right><b>$" . number_format($gm_s5, 2) . "&nbsp;</b></td>
											   <td align=right><b>$" . number_format($gm_p5, 2) . "&nbsp;</b></td>";
			            
						$content .= "<td align=center><b>$" . number_format($gmprofit5, 2) . "&nbsp;</b></td>";
						$content .= "<td align=center><b>$mem_tot6</b></td>";
						$content .=	"<td align=right><b>$" . number_format($gm_s6, 2) . "&nbsp;</b></td>
											   <td align=right><b>$" . number_format($gm_p6, 2) . "&nbsp;</b></td>";
						$content .= "<td align=center><b>$" . number_format($gmprofit6, 2) . "&nbsp;</b></td>";
						$content .= "<td align=center><b>$mem_tot7</b></td>";
					    $content .=	"<td align=right><b>$" . number_format($gm_s7, 2) . "&nbsp;</b></td>
											   <td align=right><b>$" . number_format($gm_p7, 2) . "&nbsp;</b></td>";
						$content .= "<td align=center><b>$" . number_format($gmprofit7, 2) . "&nbsp;</b></td>";
						$content .= "<td align=center><b>$mem_tot8</b></td>";
						$content .=	"<td align=right><b>$" . number_format($gm_s8, 2) . "&nbsp;</b></td>
											   <td align=right><b>$" . number_format($gm_p8, 2) . "&nbsp;</b></td>";
						$content .= "<td align=center><b>$" . number_format($gmprofit8, 2) . "&nbsp;</b></td>";
						$content .= "<td align=center><b>$mem_tot9</b></td>";
						$content .=	"<td align=right><b>$" . number_format($gm_s9, 2) . "&nbsp;</b></td>
											   <td align=right><b>$" . number_format($gm_p9, 2) . "&nbsp;</b></td>";
						$content .= "<td align=center><b>$" . number_format($gmprofit9, 2) . "&nbsp;</b></td>";
						
									
						$content .= "</tr>";	
					
				 
				  
					//print_r($result_array2);
				
			}

			$numk++; 
				
	    
	   }
	   $content.= "</tbody>";
    }
    function HeaderPrint(){
    	global $dbConn,$orderdate_qry1,$orderdate_qry;
    	$qry1 ="select kor_name,userid from member_list where
    	        c_part1='D013000' && ((out_yn is null ) || (userid in ('alexkang','bonniekim'))) order by kor_name desc"   ;
							
		//echo $qry1;				
		$rst1 = $dbConn->query($qry1);
		//$header = "<thead>";
		$header = "<tr bgcolor=#E7FED6 height=28>";	
		$header.= "	<th style='width:250px;' bgcolor=#FFFFFF colspan=2 align=center >구분</th>";	
		$header.= "	<th bgcolor=#FFFFFF align=center colspan=4>소 계</th>"; 
		$k=0;
		while($row1 = $rst1->fetch_assoc()){
			 
			if ($k % 2==1) { 
			  $header.= "	<th bgcolor=#FFFFFF  colspan=4 align=center>$row1[kor_name]</th>"; 
			} else {
			  $header.= "	<th bgcolor=#E7FED6  colspan=4 align=center>$row1[kor_name]</th>"; 
			}
			$k++;
		
		}
    	 
    	
    	$header.=  "</tr>";
		$header.=  "</thead>";
    	$rst2 = $dbConn->query($qry1);
		$header .= "<tbody>";
    	$header .= "<tr bgcolor=#E7FED6 height=28>";	
		$header.= "	<td style='width:200px;' bgcolor=#FFFFFF colspan=2 align=center width=250px>지역/상품</td>";		
		$k =0;
		$header.= "	<td bgcolor=#FFFFFF align=center width=100px>모객합계</td>"; 
		$header.= "	<td bgcolor=#FFFFFF align=center width=200px>매출합계</td>"; 
		$header.= "	<td bgcolor=#FFFFFF align=center width=200px>수익합계</td>";
		$header.= "	<td bgcolor=#FFFFFF align=center width=200px>인당수익</td>";
    	while($row2 = $rst2->fetch_assoc()){
    		if ($k % 2==1) { 
		    		$header.= "	<td bgcolor=#FFFFFF align=center width=100px>인원</td>"; 
		    		$header.= "	<td bgcolor=#FFFFFF align=center width=180px>판매금액</td>"; 
		    		$header.= "	<td bgcolor=#FFFFFF align=center width=180px>수익</td>"; 
					$header.= "	<td bgcolor=#FFFFFF align=center width=180px>인당수익</td>"; 
	    	} else {
	    		  $header.= "	<td bgcolor=#E7FED6 align=center width=100px>인원</td>"; 
		    		$header.= "	<td bgcolor=#E7FED6 align=center width=180px>판매금액</td>"; 
		    		$header.= "	<td bgcolor=#E7FED6 align=center width=180px>수익</td>"; 
					$header.= "	<td bgcolor=#E7FED6 align=center width=180px>인당수익</td>"; 
	    	}
			$k++;
    		
    	}
    	  
		  
		  $header.=  "</tr>";
    	return $header;
    }
   

 
?>
 
    
 
      
	 

<div id="contentwrapper" class="reservationDetailForm">
	<div class="main_content">
		<div id="jCrumbs" class="breadCrumb 
		module">
			<ul>
				<li><a href="/"><i class="glyphicon glyphicon-home"></i></a></li>
				<li><a href="#">MIS</a></li>
				<li><a href="#">정산현황</a></li>
				<li>아웃바운드개인별정산</li>
			</ul>
		</div>
		<div class="row"  style="overflow:scroll;" >
			<div class="col-sm-12 col-md-12">
			  
			  <br>
			  <FORM id=frmmis  method=post action="mis_outbound.php?division=4&pdx=2&sub=15">
			  <input type=hidden name=division value="<?= $division ?>">
			  <input type=hidden name=Mode  value="SEARCH"> 
			   
		
			  <table id="level4" class="txt_12" width="100%" align=center border="1" cellspacing="1" bgcolor=#dcdcdc cellpadding="0">
				<tr>
					<td width=20% bgcolor=#f4f4f4 height=30 align=right>판매일&nbsp;</td>
					<td width=80% bgcolor=#FFFFFF>&nbsp;<input name=StartYMD  autocomplete='off' type="text" class="form_box"   size="12" id="date1" value="<?= $StartYMD ?>"> ~ <input name=EndYMD type="text" autocomplete='off' class="form_box"  size="12" id="date2" value="<?= $EndYMD ?>">
						</td>
				</tr>
				
				<tr>
					<td width=20% bgcolor=#f4f4f4 height=30 align=right>&nbsp;출발일&nbsp;</td>
					<td width=80% bgcolor=#FFFFFF>&nbsp;<input name=StartYMD1 type="text" autocomplete='off' class="form_box"  size="12" id="date3" value="<?= $StartYMD1 ?>"> ~ <input name=EndYMD1 type="text" class="form_box" autocomplete='off'  size="12" id="date4" value="<?= $EndYMD1 ?>"></td>
				</tr>
				
				<tr>
					<td colspan=2 height=35 align=center bgcolor=#FFFFFF><input type=button onclick='formsub();' style="background-color:#99CC00;color:#FFFFFF;height:22px;vertical-align: top;" value="  조회하기  " >
						<!--&nbsp;<input type=button valign='top' value="  엑 셀  " style="background-color:#99CC00;color:#FFFFFF;height:22px;vertical-align: top;" onClick='Excelex();'>-->
						</td>
				</tr>
				</form>
			  </table>
			  <br>
			  <!--
			  <div style="width: 80%; min-width: 210px;margin-left : 12px;">
         <div id="chart"></div>
        </div>
       -->
        
			  <table id="grid"  class="display nowrap table-bordered text-center">
			    <thead>
				  <?= HeaderPrint()?>
				
				 <tbody>
				  
				   <?= $content; ?>
				 </tbody>
			  </table>  
			</div>
		</div>
	</div>
</div>
<?php
		include "include/side_m.php"
?>
<script type="text/javascript"> 

           $(document).ready(function () {
           
			var dateToday = new Date()
			    $('#date1').datepicker({
					format: "yyyy-mm-dd",
					autoclose: true
				
			    });
				$('#date2').datepicker({
					format: "yyyy-mm-dd",
					autoclose: true
					
			    });
				$('#date3').datepicker({
					
					format: "yyyy-mm-dd",
					autoclose: true
					
			    });
				$('#date4').datepicker({
					
					format: "yyyy-mm-dd",
					autoclose: true
					
			    });
            var hh  =window.innerHeight-150;
			var table = $('#grid').DataTable({
                    scrollY:        600,
					scrollX:        true,
					scrollCollapse: true,
					paging:         false,
					fixedHeader:    true,
					sort : false,
					autoWidth: false,
					columnDefs: [
						{ width: 150, targets: 0 }
					],
					fixedColumns: true
			   
			 });
			 new $.fn.dataTable.FixedHeader( table, {
				// options
			} );
          
			
		});
</script>
<script> 
	  function Excelex() { 
	//	alert("1");
		$("#second").val('excel');
		$("#frmmis").attr("action","mis_outbound_excel.php"); 
		$("#frmmis").submit();
		
	}   
	function formsub() { 
		//alert("1");
		$("#second").val(''); 
		$("#frmmis").attr("action","mis_outbound.php?division=4&pdx=2&sub=15"); 
		
		$("#frmmis").submit();
		
	}
</script>	
    	
			 
			  

