<?php
// JSON만 출력. 공통 레이아웃/인증 리다이렉트 include 금지!
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

// --------- 공통 함수들 ----------
function curl_simple_get($url, $timeout=12) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL=>$url, CURLOPT_RETURNTRANSFER=>true, CURLOPT_FOLLOWLOCATION=>true,
        CURLOPT_CONNECTTIMEOUT=>5, CURLOPT_TIMEOUT=>$timeout,
        CURLOPT_SSL_VERIFYPEER=>true, CURLOPT_SSL_VERIFYHOST=>2,
        CURLOPT_USERAGENT=>'Mozilla/5.0 (PHP FX Fallback)',
        CURLOPT_ENCODING=>''
    ]);
    $r = curl_exec($ch);
    if ($r === false) { error_log('curl_error: '.curl_error($ch)); }
    curl_close($ch);
    return $r === false ? '' : (string)$r;
}
function fred_latest($seriesId) {
    // FRED JSON API endpoint
    $apiKey = 'a2e10c9d4fc3548fd40a5b5b0b1b92ee';
    $url = "https://api.stlouisfed.org/fred/series/observations"
         . "?series_id=" . urlencode($seriesId)
         . "&api_key=" . urlencode($apiKey)
         . "&file_type=json"
         . "&sort_order=desc"
         . "&limit=1";

    // JSON 가져오기
    $json = curl_simple_get($url);
    if ($json === '') return null;

    // 파싱
    $data = json_decode($json, true);
    if (!is_array($data) || !isset($data['observations'][0])) {
        return null;
    }

    $obs = $data['observations'][0];

    // 유효성 검사
    if (!isset($obs['value']) || $obs['value'] === '.' || $obs['value'] === '') {
        return null;
    }

    // ✅ realtime_start, date, value 모두 반환
    return [
        'date' => isset($obs['realtime_start']) ? $obs['realtime_start'] : '',
        'date'           => isset($obs['date']) ? $obs['date'] : '',
        'value'          => (float)$obs['value']
    ];
}


/*
function get_rate_via_fred($code) {
    $krw = fred_latest('DEXKOUS'); // KRW per USD
    if (!$krw) return null;
    $krw_per_usd = (float)$krw['value'];
    $map = [
        'USD'=>['_','USD_BASE'],'JPY'=>['DEXJPUS','PER_USD'],'CNY'=>['DEXCHUS','PER_USD'],
        'CAD'=>['DEXCAUS','PER_USD'],'CHF'=>['DEXSZUS','PER_USD'],'HKD'=>['DEXHKUS','PER_USD'],
        'SGD'=>['DEXSIUS','PER_USD'],'THB'=>['DEXTHUS','PER_USD'],'MYR'=>['DEXMAUS','PER_USD'],
        'PHP'=>['DEXPHUS','PER_USD'],'INR'=>['DEXINUS','PER_USD'],
        'AUD'=>['DEXUSAL','USD_PER'],'GBP'=>['DEXUSUK','USD_PER'],'EUR'=>['DEXUSEU','USD_PER']
    ];
    if (!isset($map[$code])) return null;
    list($sid,$type) = $map[$code];
    if ($type==='USD_BASE') return ['rate'=>$krw_per_usd,'source'=>'FRED','updated_at'=>$krw['date']];
    $x = fred_latest($sid); if (!$x) return null;
    if ($type==='PER_USD') { $x_per_usd = (float)$x['value']; }
    else { $usd_per_x = (float)$x['value']; if ($usd_per_x<=0) return null; $x_per_usd = 1.0/$usd_per_x; }
    if ($x_per_usd<=0) return null;
    return ['rate'=>$krw_per_usd/$x_per_usd,'source'=>'FRED','updated_at'=>($krw['date']>$x['date']?$krw['date']:$x['date'])];
}
*/
function get_rate_via_fred($code) {
    // 1) KRW per USD
    $krw = fred_latest('DEXKOUS');   // ← 여기!
    if (!$krw) return null;
    $krw_per_usd = (float)$krw['value'];

    // 2) 통화 시리즈 매핑 (그대로)
    $map = [
        'USD' => ['_CONST_', 'USD_BASE'],
        'JPY' => ['DEXJPUS', 'PER_USD'],
        'CNY' => ['DEXCHUS', 'PER_USD'],
        'CAD' => ['DEXCAUS', 'PER_USD'],
        'CHF' => ['DEXSZUS', 'PER_USD'],
        'HKD' => ['DEXHKUS', 'PER_USD'],
        'SGD' => ['DEXSIUS', 'PER_USD'],
        'THB' => ['DEXTHUS', 'PER_USD'],
        'MYR' => ['DEXMAUS', 'PER_USD'],
        'PHP' => ['DEXPHUS', 'PER_USD'],
        'INR' => ['DEXINUS', 'PER_USD'],
        'AUD' => ['DEXUSAL', 'USD_PER'],
        'GBP' => ['DEXUSUK', 'USD_PER'],
        'EUR' => ['DEXUSEU', 'USD_PER'],
    ];
    if (!isset($map[$code])) return null;
    list($sid, $type) = $map[$code];

    if ($type === 'USD_BASE') {
        return ['rate'=>$krw_per_usd, 'source'=>'FRED(API)', 'updated_at'=>$krw['date']];
    }

    // 3) X/USD
    $x = fred_latest($sid);          // ← 여기!
    if (!$x) return null;

    if ($type === 'PER_USD') {
        $x_per_usd = (float)$x['value'];
    } else { // USD_PER
        $usd_per_x = (float)$x['value'];
        if ($usd_per_x <= 0) return null;
        $x_per_usd = 1.0 / $usd_per_x;
    }
    if ($x_per_usd <= 0) return null;

    $krw_per_x = $krw_per_usd / $x_per_usd;
    $updated   = ($krw['date'] > $x['date']) ? $krw['date'] : $x['date'];

    // JSON API를 썼는지 표시
    return ['rate'=>$krw_per_x, 'source'=>'FRED(API/CSV)', 'updated_at'=>$updated];
}

function get_rate_via_ecb($code) {
    $xml = curl_simple_get('https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml');
    if ($xml === '') return null;
    $sx = @simplexml_load_string($xml); if (!$sx) return null;
    $sx->registerXPathNamespace('def','http://www.ecb.int/vocabulary/2002-08-01/eurofxref');
    $time = (string)$sx->Cube->Cube['time'];
    $find = function($sym) use($sx){
        $n = $sx->xpath("//def:Cube[@time]/def:Cube[@currency='{$sym}']");
        return $n ? (float)$n[0]['rate'] : null;
    };
    $eur_to_krw = $find('KRW'); if (!$eur_to_krw) return null;
    if ($code==='KRW') return ['rate'=>1.0,'source'=>'ECB','updated_at'=>$time];
    if ($code==='EUR') return ['rate'=>$eur_to_krw,'source'=>'ECB','updated_at'=>$time];
    $eur_to_x = $find($code); if (!$eur_to_x || $eur_to_x<=0) return null;
    return ['rate'=>$eur_to_krw/$eur_to_x,'source'=>'ECB','updated_at'=>$time];
}

// --------- 엔드포인트 ---------
$code = isset($_GET['currency']) ? strtoupper(trim($_GET['currency'])) : 'USD';
try {
    //$krw_rate = get_rate_via_fred($code);
    if (!$krw_rate) $krw_rate = get_rate_via_ecb($code);
    if (!$krw_rate) throw new Exception('지원하지 않는 통화이거나 소스 오류입니다.');
    echo json_encode(array_merge(['ok'=>true], $krw_rate), JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
