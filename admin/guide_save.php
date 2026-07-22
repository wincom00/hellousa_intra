<?php

      include("include/dbconn.php");
      include("include/func_list.php");
	  include("include/dong_func.php");
      include ("inc_base.php");
      //조식
      $bfDate = $_POST['bfDate'];
      $bfStoreName = $_POST['bfStoreName'];
      $bfPerson = $_POST['bfPerson'];
      $bfCost = $_POST['bfCost'];
      $bfTotalCost = $_POST['bfTotalCost'];
      $bfseq = $_POST['bf_seq'];
      //중식
      $lunchDate = $_POST['lunchDate'];
      $lunchStoreName = $_POST['lunchStoreName'];
      $lunchPerson = $_POST['lunchPerson'];
      $lunchCost = $_POST['lunchCost'];
      $lunchTotalCost = $_POST['lunchTotalCost'];
      $lunchseq = $_POST['lunch_seq'];
      //석식
      $dinnerDate = $_POST['dinnerDate'];
      $dinnerStoreName = $_POST['dinnerStoreName'];
      $dinnerPerson = $_POST['dinnerPerson'];
      $dinnerCost = $_POST['dinnerCost'];
      $dinnerTotalCost = $_POST['dinnerTotalCost'];
      $dinnerseq = $_POST['dinner_seq'];
      
      //행사기간
      $period = getPeriodbyhotel($_POST['pcode'],$_POST['stDate']);
      $returndata = explode("~",$period);
      $grand_eCode = $_POST['grand_eCode'];
      $sub_eCode = $_POST['sub_eCode'];
      $guide_etcamt = $_POST['guideEtcDepAmount'];
      $userid = $user_dbinfo['userid'];
      
      $settle_code_p = $_POST['settle_code'];

       if($_POST['mode'] =='save') {


            $guide_code = $settle_code_p !== '' ? $settle_code_p : 'GU-' . date('His') . sprintf('%02d', (int)(microtime(true) * 100) % 100);

            $query = "DELETE FROM guide_meal WHERE settle_code = '$settle_code_p' "; 
            $rst0 = mysqli_query($dbConn,$query);  
            $query = "DELETE FROM guide_admission WHERE settle_code = '$settle_code_p' "; 
            $rst0 = mysqli_query($dbConn,$query);  
            $query = "DELETE FROM guide_option WHERE settle_code = '$settle_code_p' "; 
            $rst0 = mysqli_query($dbConn,$query);  
            $query = "DELETE FROM guide_etcamt WHERE settle_code = '$settle_code_p' "; 
            $rst0 = mysqli_query($dbConn,$query);  
            $query = "DELETE FROM guide_shopping WHERE settle_code = '$settle_code_p' "; 
            $rst0 = mysqli_query($dbConn,$query);  
            $query = "DELETE FROM guide_inputamt WHERE settle_code = '$settle_code_p' "; 
            $rst0 = mysqli_query($dbConn,$query);   
            $query = "DELETE FROM guide_setmaster WHERE settle_code = '$settle_code_p' "; 
            $rst0 = mysqli_query($dbConn,$query);  


            //0. guide setmaster
            $query = "INSERT INTO guide_setmaster (
            settle_code,
            grand_eCode,
            sub_eCode,
            stDate,
            edDate,
            guide_etcamt,
            reg_status,
            reg_user,
            wdate) VALUES (
            '$guide_code', '$grand_eCode','$sub_eCode','$returndata[0]','$returndata[1]','$guide_etcamt','DONE','$userid',now())";
            $rst0 = mysqli_query($dbConn,$query);

            //1. guide meal
            foreach($bfDate as $key =>$value){

              $bf_StoreName = $bfStoreName[$key];
              $bf_Person = $bfPerson[$key];
              $bf_Cost = $bfCost[$key];
              $bf_TotalCost = $bfTotalCost[$key];
              $bf_seq = $bfseq[$key];

              //$query = "DELETE FROM guide_meal WHERE seq_no = $bf_seq";
              //$rst0 = mysqli_query($dbConn,$query);

              $query = "INSERT INTO guide_meal(
              settle_code,
              meal_type,
              meal_date,
              meal_rest,
              meal_cnt,
              meal_price,
              meal_pricetotal,
              wdate) VALUES (
              '$guide_code','bf','$value','$bf_StoreName','$bf_Person','$bf_Cost','$bf_TotalCost',now() )";

              $rst0 = mysqli_query($dbConn,$query);
            }

            foreach($lunchDate as $key =>$value){

            $lunch_StoreName = $lunchStoreName[$key];
            $lunch_Person = $lunchPerson[$key];
            $lunch_Cost = $lunchCost[$key];
            $lunch_TotalCost = $lunchTotalCost[$key];
            $lunch_seq = $lunchseq[$key];

            //$query = "DELETE FROM guide_meal WHERE seq_no = $lunch_seq";
            //$rst0 = mysqli_query($dbConn,$query);

            $query = "INSERT INTO guide_meal(
            settle_code,
            meal_type,
            meal_date,
            meal_rest,
            meal_cnt,
            meal_price,
            meal_pricetotal,
            wdate) VALUES (
            '$guide_code','lunch','$value','$lunch_StoreName','$lunch_Person','$lunch_Cost','$lunch_TotalCost',now() )";

            $rst0 = mysqli_query($dbConn,$query);
            }

            foreach($dinnerDate as $key =>$value){

                $dinner_StoreName = $dinnerStoreName[$key];
                $dinner_Person = $dinnerPerson[$key];
                $dinner_Cost = $dinnerCost[$key];
                $dinner_TotalCost = $dinnerTotalCost[$key];
                $dinner_seq = $dinnerseq[$key];

                //$query = "DELETE FROM guide_meal WHERE seq_no = $dinner_seq";
                //$rst0 = mysqli_query($dbConn,$query);

                $query = "INSERT INTO guide_meal(
                settle_code,
                meal_type,
                meal_date,
                meal_rest,
                meal_cnt,
                meal_price,
                meal_pricetotal,
                wdate) VALUES (
                '$guide_code','dinner','$value','$dinner_StoreName','$dinner_Person','$dinner_Cost','$dinner_TotalCost',now() )";

                $rst0 = mysqli_query($dbConn,$query);
            }

            //2. guide admission
            $nameSelect = $_POST['nameSelect'];
            $person = $_POST['person'];
            $cost = $_POST['cost'];
            $totalAmount = $_POST['totalAmount'];
            $admissionseq = $_POST['admission_seq'];
            
            foreach($nameSelect as $key =>$value){
                $namecode = $value;
                $admission_p = $person[$key];
                $admission_cost = $cost[$key];
                $admission_totamt = $totalAmount[$key];
                $admission_seq = $admissionseq[$key];
                
                //$query = "DELETE FROM guide_admission WHERE seq_no = $admission_seq";
                //$rst0 = mysqli_query($dbConn,$query);
                
                $query = "INSERT INTO guide_admission (
                settle_code,
                admission_code,
                e_cnt,
                e_price,
                e_pricetot,
                wdate) VALUES (
                '$guide_code','$namecode','$admission_p','$admission_cost','$admission_totamt',now() )";

                $rst0 = mysqli_query($dbConn,$query);
            }

            //3. guide option
            $optionName = $_POST['optionName'];
            $assignGuideLine = $_POST['assignGuideLine'];
            $optPerson = $_POST['optPerson'];
            $optCost = $_POST['optCost'];
            $optTotalAmount = $_POST['optTotalAmount'];
            $optPrice = $_POST['optPrice'];
            $optTotalPrice = $_POST['optTotalPrice'];
            $optDiffAmount = $_POST['optDiffAmount'];
            $optProfit = $_POST['optProfit'];
            $optGuideProfit = $_POST['optGuideProfit'];
            $opt_seq = $_POST['opt_seq'];

            foreach($optionName as $key =>$value){
                $optcode = $value;
                $opt_assignGuideLine = $assignGuideLine[$key];
                $opt_optPerson = $optPerson[$key];
                $opt_optCost = $optCost[$key];
                $opt_optTotalAmount = $optTotalAmount[$key];
                $opt_optPrice = $optPrice[$key];
                $opt_optTotalPrice = $optTotalPrice[$key];
                $opt_optDiffAmount = $optDiffAmount[$key];
                $opt_optProfit = $optProfit[$key];
                $opt_optGuideProfit = $optGuideProfit[$key];
                $optseq = $opt_seq[$key];

                //$query = "DELETE FROM guide_option WHERE seq_no = $optseq";
                //$rst0 = mysqli_query($dbConn,$query);
                
                $query = "INSERT INTO guide_option (
                settle_code,
                option_code,
                base_set,
                o_cnt,
                o_price,
                o_pricetot,
                o_cprice,
                o_cpricetot,
                o_diffamt,
                o_cprofit,
                o_gprofit,
                wdate) VALUES (
                '$guide_code','$optcode','$opt_assignGuideLine','$opt_optPerson','$opt_optCost','$opt_optTotalAmount','$opt_optPrice','$opt_optTotalPrice','$opt_optDiffAmount',
                '$opt_optProfit','$opt_optGuideProfit',now() )";

                $rst0 = mysqli_query($dbConn,$query);

            }    

            //4. guide etcamt
            //guide
            $guideCarSelect = $_POST['guideCarSelect'];
            $guideTotalAmount = $_POST['guideTotalAmount'];
            $guideMemo = $_POST['guideMemo'];
            $guide_seq = $_POST['guide_seq'];
            
            foreach($guideCarSelect as $key =>$value){
                $etctype = $value;
                $g_guideTotalAmount = $guideTotalAmount[$key];
                $g_guideMemo = $guideMemo[$key];
                $g_guide_seq = $guide_seq[$key];
                
                //$query = "DELETE FROM guide_etcamt WHERE seq_no = $g_guide_seq";
                //$rst0 = mysqli_query($dbConn,$query);
                
                $query = "INSERT INTO guide_etcamt (
                settle_code,
                etc_type,
                etc_pricety,
                etc_amt,
                etc_memo,
                wdate) VALUES (
                '$guide_code','$etctype','guide','$g_guideTotalAmount','$g_guideMemo',now() )";

                $rst0 = mysqli_query($dbConn,$query);
            }

            //car
            $carSelect = $_POST['carSelect'];
            $carTotalAmount = $_POST['carTotalAmount'];
            $carMemo = $_POST['carMemo'];
            $car_seq = $_POST['car_seq'];
            
            foreach($carSelect as $key =>$value){
                $c_etctype = $value;
                $c_carTotalAmount = $carTotalAmount[$key];
                $c_carMemo = $carMemo[$key];
                $c_car_seq= $car_seq[$key];
                
                //$query = "DELETE FROM guide_etcamt WHERE seq_no = $c_car_seq";
                //$rst0 = mysqli_query($dbConn,$query);
                
                $query = "INSERT INTO guide_etcamt (
                settle_code,
                etc_type,
                etc_pricety,
                etc_amt,
                etc_memo,
                wdate) VALUES (
                '$guide_code','$c_etctype','car','$c_carTotalAmount','$c_carMemo',now() )";

                $rst0 = mysqli_query($dbConn,$query);
            }

            //etc
            $etcCarSelect = $_POST['etcCarSelect'];
            $etcTotalAmount = $_POST['etcTotalAmount'];
            $etcMemo = $_POST['etcMemo'];
            $etc_seq = $_POST['etc_seq'];
            
            foreach($etcCarSelect as $key =>$value){
                $e_etctype = $value;
                $e_etcTotalAmount = $etcTotalAmount[$key];
                $e_etcMemo = $etcMemo[$key];
                $e_etc_seq = $etc_seq[$key];
                
                //$query = "DELETE FROM guide_etcamt WHERE seq_no = $e_etc_seq";
                //$rst0 = mysqli_query($dbConn,$query);
                
                $query = "INSERT INTO guide_etcamt (
                settle_code,
                etc_type,
                etc_pricety,
                etc_amt,
                etc_memo,
                wdate) VALUES (
                '$guide_code','$e_etctype','etc','$e_etcTotalAmount','$e_etcMemo',now() )";

                $rst0 = mysqli_query($dbConn,$query);
            }


            //5. guide shopping
            $shoppingSelect = $_POST['shoppingSelect'];
            $saleTotalAmount = $_POST['saleTotalAmount'];
            $homeshoppingcom = $_POST['homeshoppingcom'];
            $companyProfit = $_POST['companyProfit'];
            $shoppingGuideProfit = $_POST['shoppingGuideProfit'];
            $shopp_seq = $_POST['shopp_seq'];
            
            foreach($shoppingSelect as $key =>$value){
                $shopcode = $value;
                $s_saleTotalAmount = $saleTotalAmount[$key];
                $s_homeshoppingcom = $homeshoppingcom[$key];
                $s_companyProfit = $companyProfit[$key];
                $s_shoppingGuideProfit = $shoppingGuideProfit[$key];
                $s_shopp_seq = $shopp_seq[$key];
                
                //$query = "DELETE FROM guide_shopping WHERE seq_no = $s_shopp_seq";
                //$rst0 = mysqli_query($dbConn,$query);
                
                $query = "INSERT INTO guide_shopping (
                settle_code,
                shop_code,
                tot_amt,
                home_comamt,
                c_profit,
                g_profit,
                wdate) VALUES (
                '$guide_code','$shopcode','$s_saleTotalAmount','$s_homeshoppingcom','$s_companyProfit','$s_shoppingGuideProfit',now() )";

                $rst0 = mysqli_query($dbConn,$query);
            }

             //6. guide inputamt
             $adult_inputamt = $_POST['adult_inputamt'];
             $adult_inputcnt = $_POST['adult_inputcnt'];
             $adult_inputmemo = $_POST['adult_inputmemo'];
             $adult_seqno = $_POST['adult_seqno'];

             $child_inputamt = $_POST['child_inputamt'];
             $child_inputcnt = $_POST['child_inputcnt'];
             $child_inputmemo = $_POST['child_inputmemo'];
             $child_seqno = $_POST['child_seqno'];

             $etc_inputamt = $_POST['etc_inputamt'];
             $etc_inputcnt = $_POST['etc_inputcnt'];
             $etc_inputmemo = $_POST['etc_inputmemo'];
             $etc_seqno = $_POST['etc_seqno'];
             
             /*$query = "DELETE FROM guide_inputamt WHERE seq_no = $adult_seqno";
             $rst0 = mysqli_query($dbConn,$query);

             $query = "DELETE FROM guide_inputamt WHERE seq_no = $child_seqno";
             $rst0 = mysqli_query($dbConn,$query);

             $query = "DELETE FROM guide_inputamt WHERE seq_no = $etc_seqno";
             $rst0 = mysqli_query($dbConn,$query);
             */

             $query = "INSERT INTO guide_inputamt (
             settle_code,
             inputamt_type,
             input_amt,
             input_cnt,
             input_memo,
             wdate) VALUES (
             '$guide_code','adult','$adult_inputamt','$adult_inputcnt','$adult_inputmemo',now() )";

            $rst0 = mysqli_query($dbConn,$query);

            $query = "INSERT INTO guide_inputamt (
            settle_code,
            inputamt_type,
            input_amt,
            input_cnt,
            input_memo,
            wdate) VALUES (
            '$guide_code','child','$child_inputamt','$child_inputcnt','$child_inputmemo',now() )";
   
            $rst0 = mysqli_query($dbConn,$query);

            $query = "INSERT INTO guide_inputamt (
            settle_code,
            inputamt_type,
            input_amt,
            input_cnt,
            input_memo,
            wdate) VALUES (
            '$guide_code','etc','$etc_inputamt','$etc_inputcnt','$etc_inputmemo',now() )";
    
            $rst0 = mysqli_query($dbConn,$query);    

	   } else if($_POST['mode']  =='delete') {

				$query = "DELETE FROM guide_meal WHERE settle_code = '$settle_code_p' "; 
				$rst0 = mysqli_query($dbConn,$query);  
				$query = "DELETE FROM guide_admission WHERE settle_code = '$settle_code_p' "; 
				$rst0 = mysqli_query($dbConn,$query);  
				$query = "DELETE FROM guide_option WHERE settle_code = '$settle_code_p' "; 
				$rst0 = mysqli_query($dbConn,$query);  
				$query = "DELETE FROM guide_etcamt WHERE settle_code = '$settle_code_p' "; 
				$rst0 = mysqli_query($dbConn,$query);  
				$query = "DELETE FROM guide_shopping WHERE settle_code = '$settle_code_p' "; 
				$rst0 = mysqli_query($dbConn,$query);  
				$query = "DELETE FROM guide_inputamt WHERE settle_code = '$settle_code_p' "; 
				$rst0 = mysqli_query($dbConn,$query);   
				$query = "DELETE FROM guide_setmaster WHERE settle_code = '$settle_code_p' "; 
				$rst0 = mysqli_query($dbConn,$query);  


	  } else if($_POST['mode']  =='report') {

				$query = "UPDATE guide_setmaster SET report_date = now(),reg_status = 'COMPLETE' WHERE settle_code = '$settle_code_p' ";
				$rst0 = mysqli_query($dbConn,$query);  

	  } else if($_POST['mode']  =='repcan') {
		  		$query = "UPDATE guide_setmaster SET report_date = '',reg_status = '' WHERE settle_code = '$settle_code_p' ";
				$rst0 = mysqli_query($dbConn,$query);  
		  
	  } else if($_POST['mode']  =='finance') {
		  		$query = "UPDATE guide_setmaster SET finance_st = 'V' WHERE settle_code = '$settle_code_p' ";
				$rst0 = mysqli_query($dbConn,$query);  
		  
	  } else if($_POST['mode']  =='ceo') {
		  		$query = "UPDATE guide_setmaster SET ceo_st = 'V' WHERE settle_code = '$settle_code_p' ";
				$rst0 = mysqli_query($dbConn,$query);  
		  
	  }
	  //echo $_POST['mode'];
      //echo $query;
	  echo '1/'

?>