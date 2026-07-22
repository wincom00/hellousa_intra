<?php
    // ===== 여기부터는 기존 네 PHP(출근/퇴근 상태 조회 등) =====
    $new_date=date("U", mktime(0,0,0,(date("m")), (date("d")), date("Y")));
	$dates=date("Y-m-d", $new_date);

    $qry2 = "select max(id) as mxid from att_log where userid='$user_dbinfo[userid]' && date_format( login_date, '%Y-%m-%d' ) = '$dates'";
	$rst2 = $dbConn->query($qry2);
	$row0 = $rst2->fetch_assoc();

    $m_qry1 = "select * from att_log where userid='$user_dbinfo[userid]' && date_format( login_date, '%Y-%m-%d' ) = '$dates' && id='$row0[mxid]' ";
	$m_rst1 = $dbConn->query($m_qry1);
	$m_row1 = $m_rst1->fetch_assoc();
?>
<a href="javascript:void(0)" class="sidebar_switch on_switch bs_ttip" data-placement="auto right" data-viewport="body" title="Hide Sidebar">사이드바 안보이기</a>
<div class="sidebar">
  <div class="sidebar_inner_scroll">
    <div class="sidebar_inner">
      <form class="input-group input-group-sm" method="post">
        <div style="text-align:center;">
          <?php  if ($m_row1['status']=="2") { ?>
              <button name=kkind id=kkind type="button" class="btn btn-primary btnatt text-center" value="1">
                <span id="instat" >&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;IN&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
              </button>
          <?php } else  if ($m_row1['status']=="1") { ?>
              <button name=kkind id=kkind type="button" class="btn btn-primary btnatt" value="2">
                <span id="instat">OUT <?=$m_row1['login_date']?></span>
              </button>
          <?php } else  if ($m_row1['status']=="") { ?>
              <button name=kkind id=kkind type="button" class="btn btn-primary btnatt" value="1">
                <span id="instat" >&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;IN&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
              </button>
          <?php } ?>
        </div>
      </form>
      <br />

      <div class="sidebar_info">
        <ul class="list-unstyled">
          <li>
            <span class="act act-warning"><?=$user_dbinfo['kor_name']?></span>
            <strong>로그인 사용자</strong>
          </li>
          <li>
            <span class="act act-success" id="clock"></span>
            <strong>시간</strong>
          </li>
          <li align="center" style='padding-top :5px;'>
            <a href="logout.php" class="btn btn-primary btn-sm btng"><strong>로그아웃</strong></a>
          </li>
        </ul>
      </div>

      <div id="side_accordion" class="panel-group">
        <?php
          if ($division != "") {
            if ($table_id != "") {
              printLeftMenu_b($division,$user_dbinfo['userid'],$pdx,$sub,$table_id);
            } else {
              printLeftMenu($division,$user_dbinfo['userid'],$pdx,$sub);
            }
          }
        ?>
      </div>

      <!-- 환율계산기 섹션 -->
      <div class="exchange-calculator" style="padding: 15px; background: #fff; border: 1px solid #ddd; margin: 10px 0;">
        <h4 style="margin-top: 0; color: #0099cc; border-bottom: 2px solid #0099cc; padding-bottom: 10px;">
          💱 실시간 환율계산기
        </h4>

        <div class="calculator-form">
          <div class="input-group" style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">통화 선택</label>
            <select id="currencySelect" onchange="loadExchangeRate()"
              style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
              <option value="USD">🇺🇸 USD (미국 달러)</option>
              <option value="JPY">🇯🇵 JPY (일본 엔)</option>
              <option value="EUR">🇪🇺 EUR (유로)</option>
              <option value="CNY">🇨🇳 CNY (중국 위안)</option>
              <option value="GBP">🇬🇧 GBP (영국 파운드)</option>
              <option value="AUD">🇦🇺 AUD (호주 달러)</option>
              <option value="CAD">🇨🇦 CAD (캐나다 달러)</option>
              <option value="CHF">🇨🇭 CHF (스위스 프랑)</option>
              <option value="HKD">🇭🇰 HKD (홍콩 달러)</option>
              <option value="SGD">🇸🇬 SGD (싱가포르 달러)</option>
              <option value="THB">🇹🇭 THB (태국 바트)</option>
              <option value="MYR">🇲🇾 MYR (말레이시아 링깃)</option>
              <option value="VND">🇻🇳 VND (베트남 동)</option>
              <option value="PHP">🇵🇭 PHP (필리핀 페소)</option>
              <option value="INR">🇮🇳 INR (인도 루피)</option>
            </select>
          </div>

          <div class="input-group" style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">금액</label>
            <input type="number" id="amount" class="form-control" placeholder="금액 입력"
              style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
          </div>

          <div class="input-group" style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">
              현재 환율 (1 외화 = ? KRW)
              <button onclick="forceReloadExchangeRate()"
                style="margin-left: 10px; padding: 3px 10px; background: #28a745; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 12px;">
                🔄 강제 새로고침
              </button>
            </label>
            <input type="text" id="exchangeRate" class="form-control" readonly
              style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; background: #f8f9fa; font-weight: bold; font-size: 16px;">
            <div id="rateInfo" style="font-size: 11px; color: #666; margin-top: 5px;">
              🔄 환율 정보 로딩 중...
            </div>
            <!-- (선택) 캐시 상태 표시 -->
            <div id="cacheInfo" style="font-size: 11px; color: #888; margin-top: 4px;"></div>
          </div>

          <div class="conversion-buttons" style="display: flex; gap: 10px; margin-bottom: 15px;">
            <button onclick="convertToKRW()"
              style="flex: 1; padding: 12px; background: #0099cc; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
              외화 → 원화
            </button>
            <button onclick="convertToForeign()"
              style="flex: 1; padding: 12px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
              원화 → 외화
            </button>
          </div>

          <div class="result-box" style="padding: 13px; background: linear-gradient(135deg, #e7f3ff 0%,#e7f3ff 100%); border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div id="result" style="font-size: 13x; font-weight: bold; color: black; text-align: center;">
              💱 계산 결과가 여기에 표시됩니다
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
  $(document).ready(function() {
    $(".btnatt").click(function() {
      $.getJSON("post_att.php?kind="+$(this).val(), function(data1) {
        $("#instat").html("");
        $(".btnatt").val("");
        $.each(data1, function(i,data2) {
          if (data2.status==1) {
            alert('IN 보고가 되었습니다.!!');
            $("#instat").html("OUT "+data2.login_date+"");
            $(".btnatt").val("2");
          } else {
            alert('OUT 보고가 되었습니다.!!');
            $("#instat").html("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;IN&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;");
            $(".btnatt").val("1");
          }
        });
      });
    });
  });

  /* ===== 환율: FreeCurrencyAPI + FRED + ECB 폴백 (하루 1회 실행) ===== */
  const FREECURRENCY_API_KEY = 'fca_live_rVLTPtrHzsRxUkJd9ZyZpWwaz7myhCxBkUJ9kZDZ'; // 필요 시 .env/서버에서 주입 권장
  const FX_API_KEY = 'fxr_live_e9ff58e46c6f109822e8ce1e607deb5c0fa2';
  let currentRate = 0;
  let lastUpdate = '';

  const CACHE_KEY_PREFIX = 'exchange_rate_';
  const CACHE_DATE_KEY   = 'exchange_rate_last_update';
  const DAILY_EXEC_FLAG  = 'exchange_rate_daily_done';
  
  document.addEventListener('DOMContentLoaded', function() {
    const today = getTodayDate();
    const lastDone = localStorage.getItem(DAILY_EXEC_FLAG);

    if (lastDone !== today) {
      loadExchangeRate(true).then(() => {
        localStorage.setItem(DAILY_EXEC_FLAG, today);
      });
    } else {
      loadExchangeRate(false);
    }
    updateCacheInfo();
  });
  function nowISO() {
	  return new Date().toISOString(); // 예: 2025-10-15T13:22:33.123Z
  }
  function getTodayDate() {
    const today = new Date();
    return today.toISOString().split('T')[0];
  }

  function getCachedRate(currency) {
    const lastUpdateDate = localStorage.getItem(CACHE_DATE_KEY);
    if (lastUpdateDate !== getTodayDate()) return null;
    const cacheKey = CACHE_KEY_PREFIX + currency;
    const cachedData = localStorage.getItem(cacheKey);
    return cachedData ? JSON.parse(cachedData) : null;
  }

  function setCachedRate(currency, rate, sourceDate, source) {
	  const cacheKey = CACHE_KEY_PREFIX + currency;
	  const fetchedAt = nowISO(); // ← 지금 가져온 시각
	  localStorage.setItem(cacheKey, JSON.stringify({
		rate: rate,
		updateTime: sourceDate,    // 소스가 준 날짜 (예: FRED observation date)
		fetchedAt: fetchedAt,      // 우리가 가져온 정확한 시각
		currency: currency,
		source: source || 'FreeCurrencyAPI'
	  }));
	  localStorage.setItem(CACHE_DATE_KEY, getTodayDate());
  }
   
  function updateCacheInfo() {
    const el = document.getElementById('cacheInfo');
    if (!el) return;
    const last = localStorage.getItem(CACHE_DATE_KEY);
    if (last) {
      const isToday = last === getTodayDate();
      el.innerHTML = `캐시 상태: ${isToday ? '✅ 오늘 캐시됨' : '⏰ 업데이트 필요'} (${last})`;
    } else {
      el.innerHTML = '캐시 상태: ❌ 캐시 없음';
    }
  }

  async function loadExchangeRate(forceReload = false) {
    const currency = document.getElementById('currencySelect').value;
    const rateInput = document.getElementById('exchangeRate');
    const rateInfo  = document.getElementById('rateInfo');
   
    // 캐시 우선
    if (!forceReload) {
	  const cached = getCachedRate(currency);
	  if (cached) {
		currentRate = cached.rate;
		rateInput.value = `${currentRate.toFixed(2)} KRW`;

		// 소스 날짜(일자) + 우리가 가져온 시각(로컬) 모두 표시
		const srcDate = cached.updateTime; // 'YYYY-MM-DD'
		const fetchedAtLocal = cached.fetchedAt
		  ? new Date(cached.fetchedAt).toLocaleString('ko-KR')
		  : '';

		rateInfo.innerHTML =
		  `📦 캐시 사용 — 소스 날짜: ${srcDate}` +
		  (fetchedAtLocal ? ` | 가져온 시각: ${fetchedAtLocal}` : '') +
		  ` | 출처: ${cached.source}`;
		updateCacheInfo();
		return;
	  }
	}

    rateInput.value = '조회 중...';
    rateInfo.innerHTML = '🔄 실시간 환율 조회 중...';
	
    // 1) FreeCurrencyAPI 시도
	
    try {
      const url = `https://api.freecurrencyapi.com/v1/latest?apikey=${FREECURRENCY_API_KEY}&base_currency=${currency}&currencies=KRW`;
      const response = await fetch(url);
      const data = await response.json();

      if (!response.ok || data.error) {
        throw new Error(data?.message || data?.error || '환율 조회 실패');
      }

      const rate = data?.data?.KRW;
	  //alert(rate);
      if (!rate) throw new Error('환율 데이터가 없습니다');

      currentRate = Number(rate);
      rateInput.value = `${currentRate.toFixed(2)} KRW`;

      const updated = data?.meta?.last_updated_at
        ? new Date(data.meta.last_updated_at)
        : new Date();

      lastUpdate = updated.toLocaleString('ko-KR');
      setCachedRate(currency, currentRate, updated, 'FreeCurrencyAPI');

      rateInfo.innerHTML = `✅ 최종 업데이트: ${lastUpdate} | 출처: FreeCurrencyAPI${forceReload ? ' (강제 새로고침)' : ''}`;
      updateCacheInfo();
      return;

    } catch (e1) {
      console.warn('FreeCurrencyAPI 실패 → FRED 폴백 시도:', e1?.message);
    }
    
    // 2) FRED 폴백
    try {
      // 별도 파일 호출
	  const fredResp = await fetch(`get_rate.php?currency=${encodeURIComponent(currency)}`, {
		  cache: 'no-store',
		  credentials: 'same-origin'
	  });
	  const ct = fredResp.headers.get('content-type') || '';
	  if (!ct.includes('application/json')) {
		  const text = await fredResp.text();
		  throw new Error('JSON 아님: ' + text.slice(0,120));
	  }


      const fredJson = await fredResp.json();
      if (!fredJson.ok || !fredJson.rate) throw new Error(fredJson.error || 'FRED 폴백 실패');

      currentRate = Number(fredJson.rate);
      rateInput.value = `${currentRate.toFixed(2)} KRW`;
      lastUpdate = new Date(fredJson.updated_at + 'T00:00:00').toLocaleDateString('ko-KR');
      setCachedRate(currency, currentRate, fredJson.updated_at, fredJson.source || 'FRED');
	  const fetchedAtLocal = new Date().toLocaleString('ko-KR');
	  rateInfo.innerHTML = `✅ 최종 업데이트: ${lastUpdate} (가져온 시각: ${fetchedAtLocal}) | 출처: ${fredJson.source}`;

      updateCacheInfo();
      return;

    } catch (e2) {
      console.warn('FRED 폴백 실패 → ECB 폴백 시도:', e2?.message);
    }

    // 3) ECB 최후 폴백
    try {
      const ecbResp = await fetch(`get_rate.php?fx=rate&currency=${encodeURIComponent(currency)}`, { cache: 'no-store' });
      const ecbJson = await ecbResp.json();
      if (!ecbJson.ok || !ecbJson.rate) throw new Error(ecbJson.error || 'ECB 폴백 실패');

      currentRate = Number(ecbJson.rate);
      rateInput.value = `${currentRate.toFixed(2)} KRW`;
      lastUpdate = new Date(ecbJson.updated_at + 'T00:00:00').toLocaleDateString('ko-KR');

      setCachedRate(currency, currentRate, ecbJson.updated_at, ecbJson.source || 'ECB');
      rateInfo.innerHTML = `✅ 최종 업데이트: ${lastUpdate} | 출처: ${ecbJson.source}`;
      updateCacheInfo();
      return;

    } catch (e3) {
      console.error('모든 폴백 실패:', e3?.message);
      rateInput.value = '조회 실패';
      rateInfo.innerHTML = `❌ 환율 조회 실패: ${e3.message || '알 수 없는 오류'}`;
    }
  }

  // ✅ 강제 새로고침 버튼 클릭 시 실행
	async function forceReloadExchangeRate() {
	  if (!confirm('⚠️ 캐시를 무시하고 최신 환율을 다시 불러오시겠습니까?')) return;

	  const currency = document.getElementById('currencySelect').value;
	  const rateInput = document.getElementById('exchangeRate');
	  const rateInfo  = document.getElementById('rateInfo');

	  rateInput.value = '⏳ 새로고침 중...';
	  rateInfo.innerHTML = '🔄 서버에서 최신 환율을 불러오는 중입니다...';


	  try {
		  // 1) 권장: 헤더에 Bearer 토큰
		  //    (대체안: URL 쿼리에 apikey= 또는 api_key= 로도 제공하는 경우가 있음. 계정 환경에 맞춰 택1)
		  const url = `https://api.fxratesapi.com/latest?base=${currency}&currencies=KRW`;
		  const headers = {
			// 옵션 A: 권장 방식
			Authorization: `Bearer ${FX_API_KEY}`,
			// 옵션 B: 쿼리스트링 사용하려면 위 Authorization 주석처리 후
			// url += `&apikey=${FXR_API_KEY}`;  또는 `&api_key=${FXR_API_KEY}`;
		  };

		  const response = await fetch(url);
		  const data = await response.json();

		  if (!response.ok || data.error) {
			throw new Error(data?.message || data?.error || '환율 조회 실패');
		  }

		  // FXRatesAPI 응답: { base: "USD", rates: { KRW: 1380.12 }, date: "2025-02-15T19:14:00.000Z", timestamp: 1739646840, ... }
		  const rate = data?.rates?.KRW;
		  
		  if (!rate) throw new Error('환율 데이터가 없습니다');

		  currentRate = Number(rate);
		  rateInput.value = `${currentRate.toFixed(2)} KRW`;

		  const updated = data?.date
			? new Date(data.date)
			: (data?.timestamp ? new Date(Number(data.timestamp) * 1000) : new Date());
		  
		  lastUpdate = updated.toLocaleString('ko-KR');
	      
		  setCachedRate(currency, currentRate, updated, 'FXRatesAPI');
		  
		  rateInfo.innerHTML = `✅ 최종 업데이트: ${lastUpdate} | 출처: FXRatesAPI(강제 새로고침)`;
		  updateCacheInfo();
		  return;

		} catch (e1) {
		  console.warn('FXRatesAPI 실패 → FRED 폴백 시도:', e1?.message);
		}

	}


  function convertToKRW() {
    const amount = parseFloat(document.getElementById('amount').value);
    const currency = document.getElementById('currencySelect').value;
    if (isNaN(amount) || amount <= 0) return showMsg('⚠️ 올바른 금액을 입력해주세요');
    if (!currentRate) return showMsg('⚠️ 환율을 먼저 조회해주세요');
    const result = amount * currentRate;
    showMsg(`💵 ${amount.toLocaleString()} ${currency} → <b>₩${Math.round(result).toLocaleString('ko-KR')}</b><br>환율: 1 ${currency} = ${currentRate.toFixed(2)} KRW`);
  }

  function convertToForeign() {
    const amount = parseFloat(document.getElementById('amount').value);
    const currency = document.getElementById('currencySelect').value;
    if (isNaN(amount) || amount <= 0) return showMsg('⚠️ 올바른 금액을 입력해주세요');
    if (!currentRate) return showMsg('⚠️ 환율을 먼저 조회해주세요');
    const result = amount / currentRate;
    showMsg(`₩${amount.toLocaleString('ko-KR')} → <b>${result.toFixed(2)} ${currency}</b><br>환율:  ${currency} = ${currentRate.toFixed(2)} KRW`);
  }

  function showMsg(msg) {
    document.getElementById('result').innerHTML = `<div style="text-align:center;">${msg}</div>`;
  }

  document.getElementById('amount').addEventListener('keypress', e => {
    if (e.key === 'Enter') convertToKRW();
  });
</script>
