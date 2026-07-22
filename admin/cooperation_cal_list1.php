<?php
include "include/header.php";
//include "inc_base.php";

if (!empty($_COOKIE['MEMLOGIN_ADMIN_HELLO'])) {
    // ok
} else {
    echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
    exit;
}

/* =========================================================================
 * 입력값 정리 (PHP 5.6 안전)
 * ========================================================================= */
$startyear    = isset($startyear) ? $startyear : '';
$StartYMD     = isset($_REQUEST['StartYMD']) ? trim($_REQUEST['StartYMD']) : (isset($StartYMD) ? trim($StartYMD) : '');
$EndYMD       = isset($_REQUEST['EndYMD']) ? trim($_REQUEST['EndYMD']) : (isset($EndYMD) ? trim($EndYMD) : '');
$basedate     = isset($_REQUEST['basedate']) ? trim($_REQUEST['basedate']) : (isset($basedate) ? trim($basedate) : '1');
$company_area = isset($_REQUEST['company_area']) ? trim($_REQUEST['company_area']) : (isset($company_area) ? trim($company_area) : '');
$companyName  = isset($_REQUEST['companyName']) ? trim($_REQUEST['companyName']) : '';

if ($startyear == "") {
    $startyear = date("Y");
}

/* =========================================================================
 * StartYMD 기본값: 없으면 현재 월
 * ========================================================================= */
if ($StartYMD == '' || $StartYMD == 'null') {
    $StartYMD = date("Y-m");
}

/* StartYMD 유효성(yyyy-mm) 최소 검증 */
if (!preg_match('/^\d{4}\-\d{2}$/', $StartYMD)) {
    $StartYMD = date("Y-m");
}

/* =========================================================================
 * 월 리스트(13개월) 생성: 기존 테이블 출력과 동일하게 "최근 13개월"을 만듦
 * - 원본은 mktime으로 복잡하게 만들었는데, 결과는 '월 문자열 13개' 생성이 핵심
 * ========================================================================= */
$months = array();
for ($i = 13; $i > 0; $i--) {
    // StartYMD(YYYY-MM) 기준으로 과거 12개월~현재월까지 13개
    $ym = date('Y-m', strtotime($StartYMD . '-01 -' . ($i - 1) . ' month'));
    $months[] = $ym;
}
$minDate = $months[0] . '-01';
$maxDate = date('Y-m-d', strtotime(end($months) . '-01 +1 month')); // < maxDate

/* =========================================================================
 * company_area 기본 처리(원본 유지)
 * - 원본은 company_area가 비어있으면 A010104로 강제했음
 * ========================================================================= */
if ($company_area != "") {
    $sqlSelCondition = " AND company_area = '" . $dbConn->real_escape_string($company_area) . "' ";
} else {
    $sqlSelCondition = " AND company_area = 'A010104' ";
}

/* =========================================================================
 * 업체 리스트 조회(1회)
 * ========================================================================= */
$companyNameEsc = $dbConn->real_escape_string($companyName);
$nameCond = "";
if ($companyName != "") {
    // userid / kor_name 둘 다 검색
    $nameCond = " AND (userid LIKE '%{$companyNameEsc}%' OR kor_name LIKE '%{$companyNameEsc}%') ";
}

$zip_qry1 = "
    SELECT userid, kor_name, a_color, company_area
    FROM member_list
    WHERE division='comp' AND del_yn='N'
    {$sqlSelCondition}
    {$nameCond}
    ORDER BY pos, kor_name ASC
";
$zip_rst1 = $dbConn->query($zip_qry1);

$companies = array(); // [userid] => row
if ($zip_rst1) {
    while ($r = $zip_rst1->fetch_assoc()) {
        $companies[$r['userid']] = $r;
    }
}

/* =========================================================================
 * basedate에 따른 기준 컬럼 선택
 * - basedate=1: 출발 기준월 -> reserve_info.stDate
 * - basedate=2: 판매 기준월 -> reserve_info.revDate
 * ========================================================================= */
$dateCol = ($basedate == '2') ? 'b.revDate' : 'b.stDate';

/* =========================================================================
 * rand_company 월별 집계(1회)
 * - reserve_info와 조인
 * - parent='MAIN', rev_status!='CANCEL' 유지
 * - 날짜조건: 범위(>=minDate, <maxDate)로 인덱스 사용
 * ========================================================================= */
$sql_rc = "
SELECT
    a.part_id,
    DATE_FORMAT($dateCol, '%Y-%m') AS ym,
    SUM(CASE WHEN a.money_type='credit' THEN a.amt ELSE 0 END) AS credit_sum,
    SUM(CASE WHEN a.money_type='debit'  THEN a.amt ELSE 0 END) AS debit_sum,
    COUNT(*) AS cnt
FROM rand_company a
JOIN reserve_info b
    ON b.reserveCode = a.reserveCode
WHERE
    a.reserveCode IS NOT NULL
    AND b.parent='MAIN'
    AND b.rev_status!='CANCEL'
    AND $dateCol >= '" . $dbConn->real_escape_string($minDate) . "'
    AND $dateCol <  '" . $dbConn->real_escape_string($maxDate) . "'
GROUP BY a.part_id, ym
";
$rst_rc = $dbConn->query($sql_rc);

$rcMap = array(); // rcMap[part_id][ym] = ['credit'=>, 'debit'=>, 'cnt'=>]
if ($rst_rc) {
    while ($row = $rst_rc->fetch_assoc()) {
        $pid = $row['part_id'];
        $ym  = $row['ym'];
        if (!isset($rcMap[$pid])) $rcMap[$pid] = array();
        $rcMap[$pid][$ym] = array(
            'credit' => (float)$row['credit_sum'],
            'debit'  => (float)$row['debit_sum'],
            'cnt'    => (int)$row['cnt']
        );
    }
}

/* =========================================================================
 * rand_pay 월별 집계(1회)
 * - 원본 로직: trans_type credit/debit 차감해서 pmt_tot 계산
 * - 기준월: 원본은 basedate별로 stDate 또는 rand_date를 쓰고 있었음
 *   -> 출발기준월(basedate=1): rand_pay.stDate
 *   -> 판매기준월(basedate=2): rand_pay.rand_date
 * ========================================================================= */
$payDateCol = ($basedate == '2') ? 'rand_date' : 'stDate';

$sql_rp = "
SELECT
    rand_id,
    DATE_FORMAT($payDateCol, '%Y-%m') AS ym,
    SUM(CASE WHEN trans_type='credit' THEN payment ELSE 0 END) AS pay_credit,
    SUM(CASE WHEN trans_type='debit'  THEN payment ELSE 0 END) AS pay_debit
FROM rand_pay
WHERE
    reserveCode IS NOT NULL
    AND $payDateCol >= '" . $dbConn->real_escape_string($minDate) . "'
    AND $payDateCol <  '" . $dbConn->real_escape_string($maxDate) . "'
GROUP BY rand_id, ym
";
$rst_rp = $dbConn->query($sql_rp);

$rpMap = array(); // rpMap[rand_id][ym] = (credit - debit)
if ($rst_rp) {
    while ($row = $rst_rp->fetch_assoc()) {
        $rid = $row['rand_id'];
        $ym  = $row['ym'];
        if (!isset($rpMap[$rid])) $rpMap[$rid] = array();
        $rpMap[$rid][$ym] = (float)$row['pay_credit'] - (float)$row['pay_debit'];
    }
}

/* =========================================================================
 * 화면 출력 시작
 * ========================================================================= */
?>
<link rel="stylesheet" type="text/css" href="lib/datatables.css"/>

<style>
    .tableFixHead          { overflow-y: auto; height: 600px; }
    .tableFixHead thead th { position: sticky; top: 0; background:#eee;border:0.05em solid #848484; }
    table.dataTable thead th, table.dataTable thead td { border-bottom: 1px solid #111; }
</style>

<div id="contentwrapper" class="reservationDetailForm">
    <div class="main_content">
        <div id="jCrumbs" class="breadCrumb module">
            <ul>
                <li><a href="/"><i class="glyphicon glyphicon-home"></i></a></li>
                <li><a href="#">업체정산</a></li>
                <li>업체별정산현황</li>
            </ul>
        </div>

        <div class="row">
            <div class="col-sm-12 col-md-12">
                <form action="<?= $PHP_SELF ?>" name="frmName" id="frmName" method="post">
                    <input type="hidden" name="mode" value="search">

                    <table class="table table-bordered table-condensed gridSixteen reserveTable formDetail">
                        <tbody>
                            <tr>
                                <td colspan="2" class="active text-center formHeader">업체명</td>
                                <td colspan="12">
                                    <input type="text" name="companyName" class="form-control" aria-label="업체명입력" placeholder="업체명입력"
                                           value="<?= htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8'); ?>" />
                                </td>
                            </tr>

                            <tr>
                                <td colspan="2" class="active text-center formHeader">
                                    <select name="basedate" class="form-control">
                                        <option <?php if ($basedate == "1" || $basedate == "") echo "selected"; ?> value="1">출발 기준월</option>
                                        <option <?php if ($basedate == "2") echo "selected"; ?> value="2">판매 기준월</option>
                                    </select>
                                </td>
                                <td colspan="12">
                                    <div class="row">
                                        <div class="col-sm-3">
                                            <input type="text" name="StartYMD" data-date-format="yyyy-mm" class="form-control tourdate1"
                                                   aria-label="조회기간" placeholder="조회기간" autocomplete="off"
                                                   value="<?= htmlspecialchars($StartYMD, ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td colspan="2" class="active text-center formHeader">지역별업체</td>
                                <td colspan="12">
                                    <div class="row">
                                        <div class="col-sm-3">
                                            <select name="company_area" class="inpubase md">
                                                <option value=""> 전체보기 </option>
                                                <?= printBaseCode4_without('A01', $company_area); ?>
                                            </select>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td colspan="16" class="text-center">
                                    <button type="submit" class="btn btn-primary btn-sm btn1">검색</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </form>

                <br />

                <div class="row">
                    <div class="col-sm-12 tableFixHead">
                        <table width="100%" id="guide_table" class="display nowrap table-bordered text-right">
                            <thead>
                                <tr>
                                    <th style="border:0.05em solid #848484;width:17%" height="28px" align="center">업 체 명</th>
                                    <?php
                                    $curYm = date('Y-m', strtotime($StartYMD . '-01'));
                                    foreach ($months as $m) {
                                        $bgCellStyle = ($m == $curYm) ? "background-color:#D4EFDF;" : "";
                                        $cmonth = "<font style='font-size:7pt'>{$m}</font>";
                                        echo "<th style='margin:0;border:0.05em solid #848484;{$bgCellStyle}' align='center'>{$cmonth}</th>";
                                    }
                                    ?>
                                </tr>
                            </thead>

                            <tbody>
                                <?php
                                if (empty($companies)) {
                                    echo "<tr><td colspan='".(1+count($months))."' style='text-align:center;padding:20px;'>데이터가 없습니다.</td></tr>";
                                } else {
                                    foreach ($companies as $userid => $cinfo) {
                                        $bg = (!empty($cinfo['a_color'])) ? $cinfo['a_color'] : "#ffffff";
                                        $month_td = "";

                                        foreach ($months as $ym) {
                                            $credit = 0; $debit = 0; $cnt = 0;
                                            if (isset($rcMap[$userid]) && isset($rcMap[$userid][$ym])) {
                                                $credit = $rcMap[$userid][$ym]['credit'];
                                                $debit  = $rcMap[$userid][$ym]['debit'];
                                                $cnt    = $rcMap[$userid][$ym]['cnt'];
                                            }

                                            $pmt = (isset($rpMap[$userid]) && isset($rpMap[$userid][$ym])) ? $rpMap[$userid][$ym] : 0;

                                            // 원본 로직 유지: tot_bal = (credit+debit) - (creditPay-debitPay)
                                            $tot_bal = ((double)$credit + (double)$debit) - (double)$pmt;

                                            $creditTot = ($credit != 0) ? number_format($credit, 2) : "";
                                            $debitTot  = ($debit  != 0) ? number_format($debit,  2) : "";

                                            if ($tot_bal != 0) {
                                                $totBal = number_format($tot_bal, 2);
                                            } else {
                                                if ($credit != 0 || $debit != 0) $totBal = "정산완료";
                                                else $totBal = "";
                                            }

                                            $bgCellColor = ($ym == $curYm) ? "#D4EFDF" : "#FFFFFF";

                                            $link = "cooperation_cal_list2.php?division=6&pdx=4&sub=10&sell={$ym}&rand_id={$userid}&stm={$ym}&flag={$basedate}";

                                            $month_td .= "
                                                <td bgcolor='{$bgCellColor}'>
                                                    <a href='{$link}' target='_blank'><font style='font-size:8pt;color:green'>{$creditTot}</font></a><br>
                                                    <a href='{$link}' target='_blank'><font style='font-size:8pt;color:orange'>{$debitTot}</font></a><br>
                                                    <a href='{$link}' target='_blank'>
                                                        <font style='font-size:8pt'>{$totBal}</font><br>
                                                        <font style='font-size:8pt;color:red;'>{$cnt}</font>&nbsp;
                                                    </a>
                                                </td>
                                            ";
                                        }

                                        echo "
                                            <tr>
                                                <td align='left' bgcolor='{$bg}' style='border:0.05em solid #848484;'>
                                                    <font style='font-size:8pt'>&nbsp;{$userid}<br/><b>&nbsp;{$cinfo['kor_name']}</b></font>
                                                </td>
                                                {$month_td}
                                            </tr>
                                        ";
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div><!-- col -->
        </div><!-- row -->
    </div>
</div>

<?php include "include/side_m.php"; ?>

<script>
$(document).ready(function () {
    pt.initReservationList();
    pt.initReservationDetail();

    $('.tourdate1').datepicker({
        format: "yyyy-mm",
        viewMode: "months",
        minViewMode: "months",
        autoclose: true
    });

    $('.tourdate2').datepicker({
        format: "yyyy-mm",
        viewMode: "months",
        minViewMode: "months",
        autoclose: true
    });

    var hh = window.innerHeight - 150;

    /*
    // DataTables 쓰면 더 느려질 수 있어서 원본처럼 주석 유지
    var table = $('#guide_table').DataTable({
        scrollY:        hh+"px",
        scrollX:        true,
        scrollCollapse: true,
        paging:         false,
        bSort : false,
        fixedColumns: { leftColumns: 1 }
    });
    */
});

var ctr = 0;
function openwin(stdate,s_code,rcd) {
    var winName = "all_" + (ctr++);
    window.open("guide_assign_customer.php?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&s_code="+s_code+"&stdate="+stdate+"&rcode="+rcd, winName, "width=1090px,height=700,scrollbars=1");
}

function numberOfDays(month,year) {
    var d = new Date(year, month, 0);
    return d.getDate();
}

function cal(mon) {
    if(mon<10) mon = "0" + mon;
    var st = $("#startyear").val()+"-"+mon+"-"+"01";
    $("#startDate1").val(st);
    var lastday = numberOfDays(mon,$("#startyear").val());
    var ed = $("#startyear").val()+"-"+mon+"-"+lastday;
    $("#endDate").val(ed);
    $("#frmName").submit();
}
</script>
</body>
</html>
