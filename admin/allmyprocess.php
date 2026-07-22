     
      
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
   
	$bColumns = array(  'tour_type',
								'grand_revNo', 
								'reserveCode', 
								'p_name',
								'book_pri',
								'p_cnt',
								'last_total',
								'last_bal',
								'p_code',
								'stDate',
								'revDate', 
								'wdate',
								'rev_status', 
								'userid',
								'pricet'
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
    if ( isset( $_POST['iDisplayStart'] ) && $_POST['iDisplayLength'] != '-1' )
    {
        $sLimit = "LIMIT ".intval( $_POST['iDisplayStart'] ).", ".
            intval( $_POST['iDisplayLength'] );
    }
     
     
    /*
     * Ordering
     */
     
   // $sOrder = "ORDER BY grand_revNo desc ,wdate desc ";
    /*
 * Ordering
 */
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
    
	
  //  $sOrder = " ORDER BY b.wdate DESC";
     $sWhere = " && parent ='MAIN'";
	
	//&& rev_status!='CANCEL' ";
     if ($_GET[startDate1] !="") {
			$start=  date("Y-m-d",strtotime($_GET[startDate1]));
		
			$sWhere .= " && a.stDate = '$start' ";

	 }
	 if ($_GET[cname] !="") {
			
		
			$sWhere .= " && ((a.book_pri like '"."%".$_GET[cname]."%"."') || (c.traveler_nm like '"."%".$_GET[cname]."%"."'))";

	 }
	 if ($_GET[crev] !="") {
			
		
			$sWhere .= " && a.reserveCode like '"."%".$_GET[crev]."%"."'";

	 }
     if ($_GET[cemail] !="") {
			
		
			$sWhere .= " && a.book_email like '"."%".$_GET[cemail]."%"."'";

	 }
	
	 if ( $_GET[ty] !="") {
			
		    if ($_GET[ty] == 1) {
			    $sWhere .= " && a.tour_type ='".$_GET[ty]."' && a.pricet ='1'";
			} else if ($_GET[ty]== 2) {
				$sWhere .= " && (a.tour_type ='".$_GET[ty]."')";
			} else if ($_GET[ty]== 3) {
				$sWhere .= " && (a.tour_type ='".$_GET[ty]."' || a.pricet ='3')";
			}

	 }
	 $sWhere .= " && userid='$user_dbinfo[userid]' ";
    /*
     * SQL queries
     * Get data to display
     */
    
	$sQuery = "select SQL_CALC_FOUND_ROWS distinct
	                            a.tour_type,
								a.grand_revNo, 
								a.reserveCode, 
								a.p_name,
								a.book_pri,
								a.p_cnt,
								a.last_total,
								a.last_bal,
								a.p_code,
								a.stDate,
								a.revDate, 
								a.wdate,
								a.rev_status, 
								a.userid,
								a.pricet
     from reserve_info a,reserve_traveler b where 1=1 && a.rev_status !='CANCEL' && a.reserveCode = b.reserveCode
	 $sWhere
	 $sOrder
	 $sLimit";
	//echo $sQuery;
	//exit;
    $rResult = $dbConn->query($sQuery); 
     
	 
    /* Data set length after filtering */
    $sQuery = "
        SELECT FOUND_ROWS()
    ";
    $rResultFilterTotal = $dbConn->query($sQuery); 
    $aResultFilterTotal = $rResultFilterTotal->fetch_assoc();
	
    $iFilteredTotal = $aResultFilterTotal[0];
     
  
	$sQuery = "select SQL_CALC_FOUND_ROWS COUNT(".$sIndexColumn.")
     from  reserve_info a,reserve_traveler b where 1=1 && a.rev_status !='CANCEL' && a.reserveCode = b.reserveCode
	 $sWhere
	 ";
    $rResultTotal = $dbConn->query($sQuery);
    $aResultTotal = $rResultTotal->fetch_assoc(); 
    $iTotal = $aResultTotal[0];
    
    /*
     * Output
     */
    
	$output = array(
        "sEcho" => intval($_POST['sEcho']),
        "iTotalRecords" => $iTotal,
        "iTotalDisplayRecords" => $iFilteredTotal,
        "aaData" => array()
    );
	
     
    while ( $aRow = $rResult->fetch_assoc())
    {
		
        $row = array();
		
        for ( $i=0 ; $i<14; $i++ )
        {

				if ($i==0) {
					if ($aRow[tour_type] == '1') {
						$aRow[tour_type] = '직접예약';
					}
					if ($aRow[tour_type] == '2') {
						$aRow[tour_type] = '웹예약';
					}
					if ($aRow[tour_type] == '3') {
						$aRow[tour_type] = '업체예약';
					}
					if (($aRow[tour_type] == '3') && ($aRow[pricet] == '3'))  {
						$aRow[tour_type] = '업체예약';
					}

				}
			    if ($i==7) {
					$pInfo=getProductMaster($aRow[p_code]);	
					if ($pInfo[p_own] == "hello") {
						$aRow[p_code]= "투어헬로USA";
					} else {
					    $rname=randname($pInfo[p_own]);
						$aRow[p_code]= $rname[kor_name];
					}
					
				}
				if ($i==10) {
					
					if ($aRow[rev_status]== 'READY') {
						$aRow[rev_status] = "<font color=#0984a3>예약접수</font>";
					}
					
					if ($aRow[rev_status]== 'DONE') {
						$aRow[rev_status] = "<font color=#911f77>예약확정</font>";
					}
					
					if ($aRow[rev_status]== 'CANCEL') {
						$aRow[rev_status] = "<font color=#e02133>예약취소</font>";
					}
				}
                $row[] = "<a href='base_reservation_m.php?estimateCode=$aRow[reserveCode]&division=$division&pdx=$pdx&sub=$sub&ty=$ty&pricet=$aRow[pricet]#TOP' >".$aRow[ $bColumns[$i] ]."</a>";
            
            
        }
        $output['aaData'][] = $row;

    }
	//print_r($output['aaData']);

	

	
    echo json_encode( $output );
?>