     
      
	<?php
	 include 'include/inc_base.php';

    /*
     * Script:    DataTables server-side script for PHP and MySQL
     * Copyright: 2010 - Allan Jardine, 2012 - Chris Wright
     * License:   GPL v2 or BSD (3-point)
     */
     
    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * Easy set variables
     */
     
    /* Array of database columns which should be read and sent back to DataTables. Use a space where
     * you want to insert a non-database field (for example a counter or static image)
     */
   
	$bColumns = array(/*'p_type1',*/'reserveCode', 
									 	'book_pri',
										'p_cnt',
										'last_total',
										'last_bal',
										'stDate',
										'revDate', 
										/*'wdate',*/
										'rev_status', 
										'userid', 
										'progress'
										);
	    
    /* Indexed column (used for fast and accurate table cardinality) */
    $sIndexColumn = "a.wdate";
     
   
    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * If you just want to use the basic configuration for DataTables with PHP server-side, there is
     * no need to edit below this line
     */
     
    /*
     * Local functions
     */
    function fatal_error ( $sErrorMessage = '' )
    {
        header( $_SERVER['SERVER_PROTOCOL'] .' 500 Internal Server Error' );
        die( $sErrorMessage );
    }
 
     

    /*
     * Paging
     */
    $sLimit = "";
    if ( isset( $_GET['iDisplayStart'] ) && $_GET['iDisplayLength'] != '-1' )
    {
        $sLimit = "LIMIT ".intval( $_GET['iDisplayStart'] ).", ".
            intval( $_GET['iDisplayLength'] );
    }
     
     
    /*
     * Ordering
     */
     
    //$sOrder = "ORDER BY a.revDate desc ";
    if ( isset( $_POST['iSortCol_0'] ) )
    {
        $sOrder = "ORDER BY  ";
        for ( $i=0 ; $i<intval( $_POST['iSortingCols'] ) ; $i++ )
        {
            if ( $_POST[ 'bSortable_'.intval($_POST['iSortCol_'.$i]) ] == "true" )
            {
                $sOrder .= $bColumns[ intval( $_POST['iSortCol_'.$i] ) ]."
                    ".($_POST['sSortDir_'.$i]==='asc' ? 'asc' : 'desc') .", ";
            }
        }
         
        $sOrder = substr_replace( $sOrder, "", -2 );
        if ( $sOrder == "ORDER BY" )
        {
            $sOrder = "";
        }
    }      
    
	
  
	//&& rev_status!='CANCEL' ";
     if ($_GET[startDate1] !="") {
			$start=  date("Y-m-d",strtotime($_GET[startDate1]));
		
			$sWhere .= " && a.stDate = '$start' ";

	 }
	 if ($_GET[pcode] !="") {
			
		
			$sWhere .= " && a.p_code = '".$_GET[pcode]."'";

	 }
	 if ($_GET[kindEvent] !="") {
			if ($_GET[kindEvent] == 1) {
		
			    $sWhere .= " && a.rev_status in ('READY','ORDER','DONE')";
			} else if ($_GET[kindEvent] == 2) {
				$sWhere .= " && a.rev_status in ('WAIT')";

			} else if ($_GET[kindEvent] == 3) {
				$sWhere .= " && a.rev_status in ('CANCEL')";

			}
	 } else {
		$sWhere .= " && a.rev_status not in ('CANCEL')";

	 }
	 
	
	 
    /*
     * SQL queries
     * Get data to display
     */
    
	$sQuery = "select SQL_CALC_FOUND_ROWS
								a.tour_type,
								'$tourCategory' as p_type1,
								a.reserveCode, 
								a.book_pri,
								a.last_total,
								a.last_bal,
								a.stDate,
								a.revDate, 
								a.wdate,
								a.rev_status, 
								a.userid,
								a.progress,
								a.p_cnt
     from reserve_info a,product_master b
	 where a.p_code=b.p_code 
	 $sWhere
	 $sOrder
	 $sLimit";
	//echo $sQuery;
	//exit;
     //$rResult = mysql_query( $sQuery, $dbConn ) or fatal_error( 'MySQL Error: ' . mysql_errno() );
    $rResult = $dbConn->query($sQuery);
	 
    /* Data set length after filtering */
    $sQuery = "
        SELECT FOUND_ROWS()
    ";
    //$rResultFilterTotal = mysql_query( $sQuery, $dbConn) or fatal_error( 'MySQL Error: ' . mysql_errno() );
    $rResultFilterTotal = $dbConn->query($sQuery); 
   // $aResultFilterTotal = mysql_fetch_array($rResultFilterTotal);
	$aResultFilterTotal = $rResultFilterTotal->fetch_assoc(); 
    $iFilteredTotal = $aResultFilterTotal[0];
     
  
	$sQuery = "select SQL_CALC_FOUND_ROWS COUNT(".$sIndexColumn.")
     from reserve_info a,product_master b
	 where a.p_code=b.p_code 
	 $sWhere
	 ";
    //$rResultTotal = mysql_query( $sQuery, $dbConn ) or fatal_error( 'MySQL Error: ' . mysql_errno() );
	$rResultTotal = $dbConn->query($sQuery) or fatal_error( 'MySQL Error: ' . mysql_errno() ); 
    //$aResultTotal = mysql_fetch_array($rResultTotal);
	$aResultTotal = $rResultTotal->fetch_assoc(); 
    $iTotal = $aResultTotal[0];
    
    /*
     * Output
     */
    
	$output = array(
        "sEcho" => intval($_GET['sEcho']),
        "iTotalRecords" => $iTotal,
        "iTotalDisplayRecords" => $iFilteredTotal,
        "aaData" => array()
    );
	
     
    while($aRow = $rResult->fetch_assoc())
    {
		
        $row = array();
		$trnm = getReserveTrRepre($aRow[reserveCode]);
	    
        for ( $i=0 ; $i<13; $i++ )
        {
			   $pInfo=getProductMaster($pcode);	
			   
				
				if ($i==1) {
					$aRow[book_pri] = $trnm[traveler_nm];
					
				}
				/*
				if ($i==1) {
				    if ($aRow[parent]== 'SUB') {
						$aRow[parent] = "<font color=BLUE>복합상품</font>";
					}else{
						$aRow[parent] = "<font color=BLUE>단일상품</font>";
					}
				}
				*/
			    
				if ($i==7) {
					
					if ($aRow[rev_status]== 'READY') {
						$aRow[rev_status] = "<font color=red>예약접수</font>";
					}
					if ($aRow[rev_status]== 'DONE') {
						$aRow[rev_status] = "<font color=red>예약확정</font>";
					}
					
					if ($aRow[rev_status]== 'CANCEL') {
						$aRow[rev_status] = "<font color=red>예약취소</font>";
					}
				}
				if ($i==8) {
					
				    $user_rinfo = getinfo_dbMember($aRow[userid]);
					$aRow[userid] = $user_rinfo[kor_name];
				}
				if ($i==9) {
					
				    //$sStr = mb_substr($aRow[progress], 0, 45, 'utf-8');
					
				}
				if ($aRow[tour_type]== '1') {
						
					$ty=1;
					$pricet=1;
					$sub=15;
				} else if ($aRow[tour_type]== '2') {
					
					$ty=2;
					$pricet=2;
					$sub=20;
				}else if ($aRow[tour_type]== '3') {
					
					$ty=3;
					$pricet=3;
					$sub=25;
				}
				if ($i==10) {
					$row[] = "<a href='base_reservation_m.php?estimateCode=$aRow[reserveCode]&division=3&pdx=2&sub=$sub&ty=$ty&pricet=$pricet#TOP' target='_blank'>".$aRow[progress]."</a>";
				    
				} else {
						$row[] = "<a href='base_reservation_m.php?estimateCode=$aRow[reserveCode]&division=3&pdx=2&sub=$sub&ty=$ty&pricet=$pricet#TOP' target='_blank'>".$aRow[ $bColumns[$i] ]."</a>";
				}
            
            
        }
        $output['aaData'][] = $row;

    }
	//print_r($output['aaData']);

	

	
    echo json_encode( $output );
?>