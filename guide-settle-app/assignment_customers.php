<?php
require_once __DIR__ . '/include/bootstrap.php';
require_once __DIR__ . '/include/layout.php';

$gsaUser = gsa_require_login();
$gsaRole = gsa_user_role($gsaUser);

if ($gsaRole !== 'guide') {
    header('Location: list.php');
    exit;
}

function gsa_customer_escape($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function gsa_customer_html($value)
{
    $value = trim((string)$value);
    if ($value === '') {
        return '-';
    }

    $allowed = '<br><br/><br /><p><div><span><b><strong><i><em><u><ul><ol><li><table><thead><tbody><tr><th><td><small>';
    $html = strip_tags($value, $allowed);
    $html = preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
    $html = preg_replace('/\s+(href|src)\s*=\s*("|\')?\s*javascript:[^"\'>\s]*(\2)?/i', '', $html);

    if ($html === strip_tags($html)) {
        $html = nl2br(gsa_customer_escape($html));
    }

    return $html;
}

function gsa_customer_param($key)
{
    return isset($_GET[$key]) ? trim((string)$_GET[$key]) : '';
}

function gsa_customer_detail_type($tourType)
{
    if ((string)$tourType === '2') {
        return '20';
    }
    if ((string)$tourType === '5') {
        return '25';
    }

    return '15';
}

function gsa_customer_authorized($guideId, $grandCode, $subCode, $pCode, $stDate)
{
    global $dbConn;

    $sql = "SELECT seq_no
            FROM tour_guide
            WHERE guide_id = ?
              AND grand_eCode = ?
              AND sub_eCode = ?
              AND p_code = ?
              AND stDate = ?
            LIMIT 1";
    $stmt = $dbConn->prepare($sql);
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('sssss', $guideId, $grandCode, $subCode, $pCode, $stDate);
    $stmt->execute();
    $result = $stmt->get_result();
    $ok = $result && $result->num_rows > 0;
    $stmt->close();

    return $ok;
}

function gsa_customer_fetch_rows($grandCode, $subCode, $pCode, $stDate)
{
    global $dbConn;

    $sql = "SELECT
                b.rand_id,
                b.p_cnt,
                b.reserveCode,
                b.room_cnt,
                b.tour_type,
                b.h_cnt,
                b.last_bal,
                b.stDate,
                b.userid,
                b.base_rate,
                b.parent,
                b.meet_area,
                b.book_pri,
                b.book_phone,
                b.p_name AS reserve_p_name,
                a.bus_num,
                a.rev_nm,
                c.traveler_nm,
                c.traveler_phone,
                c.pick_area,
                c.e_memo,
                pm.p_name AS master_p_name,
                MAX(bl.bus_number) AS bus_name
            FROM tour_car a
            INNER JOIN reserve_info b
              ON a.p_code = b.p_code
             AND a.reserveCode = b.reserveCode
            INNER JOIN reserve_traveler c
              ON b.reserveCode = c.reserveCode
             AND a.rev_nm = c.traveler_nm
            LEFT JOIN product_master pm
              ON (
                CASE
                  WHEN b.parent = 'MAIN' THEN b.p_code
                  WHEN b.parent = 'SUB' THEN (
                    SELECT ri.p_code
                    FROM reserve_info ri
                    WHERE ri.reserveCode = b.reserveCode
                      AND ri.parent = 'MAIN'
                    LIMIT 1
                  )
                END
              ) = pm.p_code
            LEFT JOIN tour_guide tg
              ON tg.grand_eCode = a.grand_eCode
             AND tg.sub_eCode = a.sub_eCode
             AND tg.p_code = a.p_code
             AND tg.stDate = a.stDate
             AND tg.bus_num = a.bus_num
            LEFT JOIN bus_list bl
              ON bl.bus_id = tg.c_id
            WHERE a.grand_eCode = ?
              AND a.sub_eCode = ?
              AND a.p_code = ?
              AND a.stDate = ?
              AND c.seqint = '0'
              AND b.rev_status NOT IN ('CANCEL', 'CANCE', 'WAIT')
            GROUP BY a.reserveCode, a.sub_eCode, a.bus_num
            ORDER BY a.bus_num ASC, pm.p_day ASC, b.reserveCode ASC";

    $stmt = $dbConn->prepare($sql);
    if (!$stmt) {
        return array();
    }
    $stmt->bind_param('ssss', $grandCode, $subCode, $pCode, $stDate);
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = array();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $reserve = getReserveInfo($row['reserveCode']);
            $tourInfo = getTourInfo2($pCode, $row['stDate']);
            $agent = getinfo_dbMember($row['userid']);

            if ($row['parent'] === 'MAIN') {
                $pickup = getPicGr4($row['reserveCode'], $row['traveler_nm']);
            } else {
                $pickup = getPicSub2($row['reserveCode'], $subCode, $row['stDate']);
                if ($pickup === '') {
                    $pickup = pickBaseCodeC($row['meet_area']);
                }
            }

            $bookName = $row['book_pri'];
            if (trim((string)$row['rand_id']) !== '') {
                $randInfo = randname($row['rand_id']);
                if (is_array($randInfo) && isset($randInfo['kor_name']) && $randInfo['kor_name'] !== '') {
                    $bookName = $randInfo['kor_name'];
                }
            }

            $progressParts = array();
            if (is_array($reserve) && trim((string)$reserve['progress']) !== '') {
                $progressParts[] = $reserve['progress'];
            }
            if (is_array($tourInfo) && trim((string)$tourInfo['etc_memo']) !== '') {
                $progressParts[] = $tourInfo['etc_memo'];
            }
            if (is_array($tourInfo) && trim((string)$tourInfo['ev_memo']) !== '') {
                $progressParts[] = $tourInfo['ev_memo'];
            }
            if (trim((string)$row['e_memo']) !== '') {
                $progressParts[] = $row['e_memo'];
            }

            $personCount = is_array($reserve) && isset($reserve['p_cnt']) ? (int)$reserve['p_cnt'] : (int)$row['p_cnt'];
            $roomCount = is_array($reserve) && isset($reserve['room_cnt']) ? (int)$reserve['room_cnt'] : (int)$row['room_cnt'];
            $balance = is_array($reserve) && isset($reserve['last_bal']) ? $reserve['last_bal'] : $row['last_bal'];

            $rows[] = array(
                'reserve_code' => $row['reserveCode'],
                'book_name' => $bookName,
                'book_phone' => $row['book_phone'],
                'traveler_name' => $row['traveler_nm'],
                'traveler_phone' => $row['traveler_phone'],
                'product_name' => is_array($reserve) && isset($reserve['p_name']) ? $reserve['p_name'] : $row['master_p_name'],
                'person_count' => $personCount,
                'room_count' => $roomCount,
                'pickup' => $pickup,
                'payment_label' => ((float)$balance == 0.0) ? '완납' : '미납',
                'balance' => $balance,
                'bus_num' => trim((string)$row['bus_num']),
                'bus_name' => trim((string)$row['bus_name']),
                'agent_name' => is_array($agent) && isset($agent['kor_name']) ? $agent['kor_name'] : '',
                'memo' => implode("\n", array_filter($progressParts)),
                'detail_type' => gsa_customer_detail_type($row['tour_type'])
            );
        }
    }
    $stmt->close();

    return $rows;
}

function gsa_customer_fetch_hotels($grandCode, $subCode, $pCode, $stDate)
{
    global $dbConn;

    $sql = "SELECT a.day, a.hotel_code, b.h_sname, b.h_name
            FROM hotel_assign a
            LEFT JOIN product_hotel b ON a.hotel_code = b.h_code
            WHERE a.grand_eCode = ?
              AND a.sub_eCode = ?
              AND a.p_code = ?
              AND a.stDate = ?
            ORDER BY a.day+0 ASC, a.seq_no ASC";

    $stmt = $dbConn->prepare($sql);
    if (!$stmt) {
        return array();
    }
    $stmt->bind_param('ssss', $grandCode, $subCode, $pCode, $stDate);
    $stmt->execute();
    $result = $stmt->get_result();

    $items = array();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $label = '';
            if (trim((string)$row['h_name']) !== '') {
                $label = $row['h_name'];
            } elseif (trim((string)$row['h_sname']) !== '') {
                $label = $row['h_sname'];
            } elseif (trim((string)$row['hotel_code']) !== '') {
                $label = $row['hotel_code'];
            }
            if ($label === '') {
                continue;
            }
            $day = trim((string)$row['day']);
            $key = $day . '|' . $label;
            if (isset($items[$key])) {
                continue;
            }
            $items[$key] = array('day' => $day, 'name' => $label);
        }
    }
    $stmt->close();

    return array_values($items);
}

function gsa_customer_money($value)
{
    if ($value === '' || $value === null) {
        return '-';
    }

    return '$' . number_format((float)$value, 2);
}

$grandCode = gsa_customer_param('g_code');
$subCode = gsa_customer_param('s_code');
$pCode = gsa_customer_param('p_code');
$stDate = gsa_customer_param('st');

if ($grandCode === '' || $subCode === '' || $pCode === '' || $stDate === '') {
    echo "<script>alert('필수 정보가 없습니다.'); history.back();</script>";
    exit;
}

if (!gsa_customer_authorized($gsaUser['userid'], $grandCode, $subCode, $pCode, $stDate)) {
    echo "<script>alert('조회 권한이 없습니다.'); location.href='assignments.php';</script>";
    exit;
}

$productInfo = getProductMaster($pCode);
$productName = is_array($productInfo) && isset($productInfo['p_name']) ? $productInfo['p_name'] : $pCode;
$rows = gsa_customer_fetch_rows($grandCode, $subCode, $pCode, $stDate);
$hotels = gsa_customer_fetch_hotels($grandCode, $subCode, $pCode, $stDate);
$totalPeople = 0;
$totalRooms = 0;
foreach ($rows as $row) {
    $totalPeople += (int)$row['person_count'];
    $totalRooms += (int)$row['room_count'];
}

gsa_layout_head('모바일 명단보기');
?>
<section class="gsa-customer-hero mb-3">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start gap-3">
                <div class="min-w-0">
                    <div class="small text-muted"><?php echo gsa_customer_escape($stDate); ?></div>
                    <div class="fw-bold fs-5 gsa-line-clamp-2"><?php echo gsa_customer_escape($productName); ?></div>
                    <div class="small text-muted mt-1"><?php echo gsa_customer_escape($grandCode); ?> / <?php echo gsa_customer_escape($subCode); ?></div>
                </div>
                <a href="assignments.php" class="btn btn-outline-secondary btn-sm">배정</a>
            </div>

            <div class="gsa-customer-summary mt-3">
                <div>
                    <span class="gsa-assign-number"><?php echo number_format(count($rows)); ?></span>
                    <span class="small text-muted">팀</span>
                </div>
                <div class="text-end small text-muted">
                    <div>인원 <?php echo number_format($totalPeople); ?>명</div>
                    <div>객실 <?php echo number_format($totalRooms); ?>개</div>
                </div>
            </div>

            <?php if (count($hotels) > 0) { ?>
            <div class="gsa-customer-block mt-3">
                <div class="gsa-customer-block-title">호텔</div>
                <div class="small">
                    <?php foreach ($hotels as $hotel) { ?>
                    <div>
                        <?php if ($hotel['day'] !== '') { ?>
                        <span class="badge text-bg-light border me-1">Day <?php echo gsa_customer_escape($hotel['day']); ?></span>
                        <?php } ?>
                        <?php echo gsa_customer_escape($hotel['name']); ?>
                    </div>
                    <?php } ?>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
</section>

<section class="pb-3">
    <?php if (count($rows) === 0) { ?>
        <div class="gsa-empty-state">조회된 명단이 없습니다.</div>
    <?php } else { ?>
        <div class="gsa-card-stack">
            <?php foreach ($rows as $index => $row) {
                $phoneDigits = preg_replace('/[^0-9+]/', '', (string)$row['traveler_phone']);
            ?>
            <article class="card border-0 shadow-sm gsa-customer-card">
                <div class="card-body">
                    <div class="gsa-customer-top">
                        <div class="gsa-customer-rank"><?php echo number_format($index + 1); ?></div>
                        <div class="min-w-0 flex-grow-1">
                            <div class="d-flex align-items-start justify-content-between gap-2">
                                <div class="min-w-0">
                                    <div class="fw-bold fs-5 text-truncate"><?php echo gsa_customer_escape($row['traveler_name']); ?></div>
                                    <div class="small text-muted text-break"><?php echo gsa_customer_escape($row['traveler_phone'] !== '' ? $row['traveler_phone'] : $row['reserve_code']); ?></div>
                                </div>
                                <span class="badge rounded-pill <?php echo $row['payment_label'] === '완납' ? 'text-bg-success' : 'text-bg-warning text-dark'; ?>">
                                    <?php echo gsa_customer_escape($row['payment_label']); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <?php
                        $busLabel = '차량 -';
                        if ($row['bus_num'] !== '') {
                            $busLabel = '차량 ' . $row['bus_num'];
                            if (!preg_match('/호$/u', $busLabel)) {
                                $busLabel .= '호';
                            }
                        }
                        if ($row['bus_name'] !== '') {
                            $busLabel .= ' (' . $row['bus_name'] . ')';
                        }
                    ?>
                    <div class="gsa-customer-tags mt-3">
                        <span><?php echo gsa_customer_escape($row['reserve_code']); ?></span>
                        <span><?php echo gsa_customer_escape($busLabel); ?></span>
                        <span><?php echo number_format($row['person_count']); ?>명</span>
                        <span><?php echo number_format($row['room_count']); ?>객실</span>
                    </div>

                    <div class="gsa-customer-owner mt-3">
                        <div class="gsa-customer-block-title">예약자</div>
                        <div class="gsa-customer-owner-value">
                            <strong><?php echo gsa_customer_escape($row['book_name']); ?></strong>
                            <?php if (trim((string)$row['book_phone']) !== '') { ?>
                            <small><?php echo gsa_customer_escape($row['book_phone']); ?></small>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="gsa-customer-block mt-3">
                        <div class="gsa-customer-block-title">픽업</div>
                        <div class="gsa-customer-html"><?php echo gsa_customer_html($row['pickup']); ?></div>
                    </div>

                    <div class="gsa-customer-block mt-2">
                        <div class="gsa-customer-block-title">상품</div>
                        <div class="text-break"><?php echo gsa_customer_escape($row['product_name']); ?></div>
                    </div>

                    <div class="gsa-customer-block mt-2">
                        <div class="gsa-customer-block-title">메모</div>
                        <div class="gsa-customer-html"><?php echo gsa_customer_html($row['memo']); ?></div>
                    </div>

                    <?php if ($phoneDigits !== '') { ?>
                    <a href="tel:<?php echo gsa_customer_escape($phoneDigits); ?>" class="btn btn-primary w-100 mt-3">전화걸기</a>
                    <?php } ?>
                </div>
            </article>
            <?php } ?>
        </div>
    <?php } ?>
</section>
<?php
gsa_layout_foot();
