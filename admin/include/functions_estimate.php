<?php
// functions_corporate_estimate.php - 업체/단체 견적서 전용 함수

function save_corporate_estimate_data() {
    global $dbConn;
    
    $estimate_code = $_POST['estimate_code'];
    $data = $_POST;
    
    if($estimate_code) {
        // 기존 견적서 수정
        $sql = "UPDATE corporate_estimates SET 
                estimate_no = ?, estimate_date = ?, valid_until = ?, estimate_type = ?, estimate_status = ?,
                company_name = ?, business_license = ?, company_ceo = ?, contact_person = ?, contact_position = ?,
                company_address = ?, contact_phone = ?, contact_fax = ?, contact_email = ?,
                product_code = ?, product_name = ?, tour_days = ?, departure_date = ?, arrival_date = ?,
                total_pax = ?, adult_pax = ?, child_pax = ?, departure_city = ?, arrival_city = ?, tour_type = ?,
                product_description = ?, currency = ?, payment_method = ?, payment_schedule = ?,
                deposit_rate = ?, deposit_due_date = ?, balance_due_date = ?,
                total_cost = ?, total_margin = ?, grand_total = ?, group_discount_rate = ?,
                additional_service = ?, other_discount = ?, tax_fee = ?, final_total = ?,
                terms_conditions = ?, detailed_itinerary = ?, included_items = ?, excluded_items = ?,
                sales_person = ?, sales_phone = ?, sales_email = ?, update_date = NOW()
                WHERE estimate_code = ?";
        
        $stmt = $dbConn->prepare($sql);
        $stmt->bind_param("sssssssssssssssssssiiiissssssdddddddddssssssss",
            $data['estimate_no'], $data['estimate_date'], $data['valid_until'], $data['estimate_type'], $data['estimate_status'],
            $data['company_name'], $data['business_license'], $data['company_ceo'], $data['contact_person'], $data['contact_position'],
            $data['company_address'], $data['contact_phone'], $data['contact_fax'], $data['contact_email'],
            $data['product_code'], $data['product_name'], $data['tour_days'], $data['departure_date'], $data['arrival_date'],
            $data['total_pax'], $data['adult_pax'], $data['child_pax'], $data['departure_city'], $data['arrival_city'], $data['tour_type'],
            $data['product_description'], $data['currency'], $data['payment_method'], $data['payment_schedule'],
            $data['deposit_rate'], $data['deposit_due_date'], $data['balance_due_date'],
            $data['total_cost'], $data['total_margin'], $data['grand_total'], $data['group_discount_rate'],
            $data['additional_service'], $data['other_discount'], $data['tax_fee'], $data['final_total'],
            $data['terms_conditions'], $data['detailed_itinerary'], $data['included_items'], $data['excluded_items'],
            $data['sales_person'], $data['sales_phone'], $data['sales_email'], $estimate_code
        );
    } else {
        // 새 견적서 생성
        $estimate_code = 'CORP-' . date('Ymd') . '-' . str_pad(rand(1,9999), 4, '0', STR_PAD_LEFT);
        
        $sql = "INSERT INTO corporate_estimates (
                estimate_code, estimate_no, estimate_date, valid_until, estimate_type, estimate_status,
                company_name, business_license, company_ceo, contact_person, contact_position,
                company_address, contact_phone, contact_fax, contact_email,
                product_code, product_name, tour_days, departure_date, arrival_date,
                total_pax, adult_pax, child_pax, departure_city, arrival_city, tour_type,
                product_description, currency, payment_method, payment_schedule,
                deposit_rate, deposit_due_date, balance_due_date,
                total_cost, total_margin, grand_total, group_discount_rate,
                additional_service, other_discount, tax_fee, final_total,
                terms_conditions, detailed_itinerary, included_items, excluded_items,
                sales_person, sales_phone, sales_email, create_date
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $dbConn->prepare($sql);
        $stmt->bind_param("sssssssssssssssssssiiisssssssdddddddddsssssss",
            $estimate_code, $data['estimate_no'], $data['estimate_date'], $data['valid_until'], $data['estimate_type'], $data['estimate_status'],
            $data['company_name'], $data['business_license'], $data['company_ceo'], $data['contact_person'], $data['contact_position'],
            $data['company_address'], $data['contact_phone'], $data['contact_fax'], $data['contact_email'],
            $data['product_code'], $data['product_name'], $data['tour_days'], $data['departure_date'], $data['arrival_date'],
            $data['total_pax'], $data['adult_pax'], $data['child_pax'], $data['departure_city'], $data['arrival_city'], $data['tour_type'],
            $data['product_description'], $data['currency'], $data['payment_method'], $data['payment_schedule'],
            $data['deposit_rate'], $data['deposit_due_date'], $data['balance_due_date'],
            $data['total_cost'], $data['total_margin'], $data['grand_total'], $data['group_discount_rate'],
            $data['additional_service'], $data['other_discount'], $data['tax_fee'], $data['final_total'],
            $data['terms_conditions'], $data['detailed_itinerary'], $data['included_items'], $data['excluded_items'],
            $data['sales_person'], $data['sales_phone'], $data['sales_email']
        );
    }
    
    if($stmt->execute()) {
        // 견적 항목들 저장
        save_corporate_estimate_items($estimate_code);
        
        echo "<script>alert('업체/단체 견적서가 저장되었습니다.'); location.href='corporate_estimate_form.php?estimate_code=$estimate_code';</script>";
    } else {
        echo "<script>alert('저장 중 오류가 발생했습니다.');</script>";
    }
}

function save_corporate_estimate_items($estimate_code) {
    global $dbConn;
    
    // 기존 항목들 삭제
    $sql = "DELETE FROM corporate_estimate_items WHERE estimate_code = ?";
    $stmt = $dbConn->prepare($sql);
    $stmt->bind_param("s", $estimate_code);
    $stmt->execute();
    
    // 새 항목들 저장
    $categories = $_POST['item_category'];
    $codes = $_POST['item_code'];
    $names = $_POST['item_name'];
    $units = $_POST['item_unit'];
    $qtys = $_POST['item_qty'];
    $costs = $_POST['item_cost'];
    $margins = $_POST['item_margin'];
    $memos = $_POST['item_memo'];
    
    for($i = 0; $i < count($names); $i++) {
        if(!empty($names[$i])) {
            $qty = floatval($qtys[$i]);
            $cost = floatval(str_replace(',', '', $costs[$i]));
            $margin_rate = floatval($margins[$i]);
            
            $cost_amount = $qty * $cost;
            $margin_amount = $cost_amount * ($margin_rate / 100);
            $sale_price = $cost_amount + $margin_amount;
            
            $sql = "INSERT INTO corporate_estimate_items (
                    estimate_code, item_order, item_category, item_code, item_name, item_unit,
                    item_qty, item_cost, cost_amount, margin_rate, margin_amount, sale_price, item_memo
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $dbConn->prepare($sql);
            $stmt->bind_param("sissssdddddds",
                $estimate_code, ($i+1), $categories[$i], $codes[$i], $names[$i], $units[$i],
                $qty, $cost, $cost_amount, $margin_rate, $margin_amount, $sale_price, $memos[$i]
            );
            $stmt->execute();
        }
    }
}

function get_corporate_estimate_data($estimate_code) {
    global $dbConn;
    
    $sql = "SELECT * FROM corporate_estimates WHERE estimate_code = ?";
    $stmt = $dbConn->prepare($sql);
    $stmt->bind_param("s", $estimate_code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

function get_corporate_estimate_items($estimate_code) {
    global $dbConn;
    
    $sql = "SELECT * FROM corporate_estimate_items WHERE estimate_code = ? ORDER BY item_order";
    $stmt = $dbConn->prepare($sql);
    $stmt->bind_param("s", $estimate_code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = array();
    while($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    
    return $items;
}
?>