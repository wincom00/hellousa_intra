<?php

if (!function_exists('arapH')) {
    function arapH($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('arapRedirect')) {
    function arapRedirect($url, $message = '')
    {
        if ($message !== '') {
            Misc::jvAlert($message, '');
        }

        echo "<meta http-equiv='refresh' content='0; url={$url}'>";
        exit;
    }
}

if (!function_exists('arapBuildQuery')) {
    function arapBuildQuery($params)
    {
        $pairs = array();
        foreach ($params as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $pairs[] = rawurlencode((string)$key) . '=' . rawurlencode((string)$value);
        }

        return implode('&', $pairs);
    }
}

if (!function_exists('arapPageUrl')) {
    function arapPageUrl($scriptName, $params = array())
    {
        $query = arapBuildQuery($params);
        if ($query === '') {
            return $scriptName;
        }

        return $scriptName . '?' . $query;
    }
}

if (!function_exists('arapGetMonthValue')) {
    function arapGetMonthValue($monthValue = '')
    {
        $monthValue = trim((string)$monthValue);
        if ($monthValue === '' || !preg_match('/^\d{4}-\d{2}$/', $monthValue)) {
            return date('Y-m');
        }

        return $monthValue;
    }
}

if (!function_exists('arapGetMonthRange')) {
    function arapGetMonthRange($monthValue)
    {
        $monthValue = arapGetMonthValue($monthValue);
        $startDate = $monthValue . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        return array($startDate, $endDate);
    }
}

if (!function_exists('arapGetYearValue')) {
    function arapGetYearValue($yearValue = '')
    {
        $yearValue = trim((string)$yearValue);
        if ($yearValue === '' || !preg_match('/^\d{4}$/', $yearValue)) {
            return date('Y');
        }

        return $yearValue;
    }
}

if (!function_exists('arapFetchCategories')) {
    function arapFetchCategories($type = '')
    {
        global $dbConn;

        $sql = "SELECT cat_id, cat_type, cat_name, sort_order, use_yn
                FROM arap_category
                WHERE 1 = 1";
        if ($type !== '') {
            $safeType = $dbConn->real_escape_string($type);
            $sql .= " AND cat_type = '{$safeType}'";
        }
        $sql .= " ORDER BY cat_type ASC, sort_order ASC, cat_name ASC";

        $rows = array();
        $result = $dbConn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }

        return $rows;
    }
}

if (!function_exists('arapFetchSubcategories')) {
    function arapFetchSubcategories($catId = 0)
    {
        global $dbConn;

        $sql = "SELECT s.sub_id, s.cat_id, s.sub_name, s.sort_order, s.use_yn, c.cat_type, c.cat_name
                FROM arap_subcategory s
                INNER JOIN arap_category c ON c.cat_id = s.cat_id
                WHERE 1 = 1";
        if ((int)$catId > 0) {
            $sql .= " AND s.cat_id = " . (int)$catId;
        }
        $sql .= " ORDER BY c.cat_type ASC, c.sort_order ASC, c.cat_name ASC, s.sort_order ASC, s.sub_name ASC";

        $rows = array();
        $result = $dbConn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }

        return $rows;
    }
}

if (!function_exists('arapFetchMethods')) {
    function arapFetchMethods($type = '')
    {
        global $dbConn;

        $sql = "SELECT method_id, method_type, method_name, sort_order, use_yn
                FROM arap_method
                WHERE 1 = 1";
        if ($type !== '') {
            $safeType = $dbConn->real_escape_string($type);
            $sql .= " AND method_type = '{$safeType}'";
        }
        $sql .= " ORDER BY method_type ASC, sort_order ASC, method_name ASC";

        $rows = array();
        $result = $dbConn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }

        return $rows;
    }
}

if (!function_exists('arapFetchBanks')) {
    function arapFetchBanks($onlyUsed = false)
    {
        global $dbConn;

        $sql = "SELECT bank_id, bank_name, sort_order, use_yn
                FROM arap_bank
                WHERE 1 = 1";
        if ($onlyUsed) {
            $sql .= " AND use_yn = 'Y'";
        }
        $sql .= " ORDER BY sort_order ASC, bank_name ASC";

        $rows = array();
        $result = $dbConn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }

        return $rows;
    }
}

if (!function_exists('arapFetchPairs')) {
    function arapFetchPairs($sql, $keyField, $valueField)
    {
        global $dbConn;

        $pairs = array();
        $result = $dbConn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $pairs[$row[$keyField]] = $row[$valueField];
            }
        }

        return $pairs;
    }
}

if (!function_exists('arapFormatAmount')) {
    function arapFormatAmount($amount)
    {
        return number_format((float)$amount, 2);
    }
}

