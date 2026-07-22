(function ($) {
    $(function () {
        $('input[type="number"], input[type="tel"]').attr('inputmode', 'decimal');

        $('#gsaQuickRange button').on('click', function () {
            var rangeType = $(this).data('range');
            var now = new Date();
            var startDate = '';
            var endDate = '';

            function toIso(value) {
                var year = value.getFullYear();
                var month = String(value.getMonth() + 1).padStart(2, '0');
                var day = String(value.getDate()).padStart(2, '0');
                return year + '-' + month + '-' + day;
            }

            if (rangeType === '7d') {
                endDate = new Date(now.getFullYear(), now.getMonth(), now.getDate());
                startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate() - 7);
            } else if (rangeType === '30d') {
                endDate = new Date(now.getFullYear(), now.getMonth(), now.getDate());
                startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate() - 30);
            } else if (rangeType === 'thism') {
                startDate = new Date(now.getFullYear(), now.getMonth(), 1);
                endDate = new Date(now.getFullYear(), now.getMonth() + 1, 0);
            } else if (rangeType === 'nextm') {
                startDate = new Date(now.getFullYear(), now.getMonth() + 1, 1);
                endDate = new Date(now.getFullYear(), now.getMonth() + 2, 0);
            } else if (rangeType === 'clear') {
                $('#start_date, #end_date').val('');
                return;
            }

            $('#start_date').val(toIso(startDate));
            $('#end_date').val(toIso(endDate));
        });

        function toNumber(value) {
            if (value === undefined || value === null) {
                return 0;
            }

            value = String(value).replace(/,/g, '').trim();
            if (value === '') {
                return 0;
            }

            var parsed = parseFloat(value);
            return isNaN(parsed) ? 0 : parsed;
        }

        function formatMoney(value) {
            return '$' + toNumber(value).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function optionRatioToProfit(diff, ratio) {
            if (ratio === '55') {
                return {
                    company: diff * 0.5,
                    guide: diff * 0.5
                };
            }
            if (ratio === '64') {
                return {
                    company: diff * 0.6,
                    guide: diff * 0.4
                };
            }
            if (ratio === '73') {
                return {
                    company: diff * 0.7,
                    guide: diff * 0.3
                };
            }

            return {
                company: 0,
                guide: 0
            };
        }

        function recalcMealCard($row) {
            var count = toNumber($row.find('.js-row-count').val());
            var unit = toNumber($row.find('.js-row-unit').val());
            $row.find('.js-row-total').val((count * unit).toFixed(2));
        }

        function recalcAdmissionCard($row) {
            var count = toNumber($row.find('.js-row-count').val());
            var unit = toNumber($row.find('.js-row-unit').val());
            $row.find('.js-row-total').val((count * unit).toFixed(2));
        }

        function recalcOptionCard($row) {
            var count = toNumber($row.find('.js-opt-count').val());
            var cost = toNumber($row.find('.js-opt-cost').val());
            var sale = toNumber($row.find('.js-opt-sale').val());
            var ratio = $row.find('.js-opt-ratio').val();
            var costTotal = count * cost;
            var saleTotal = count * sale;
            var diff = saleTotal - costTotal;
            var profits = optionRatioToProfit(diff, ratio);

            $row.find('.js-opt-cost-total').val(costTotal.toFixed(2));
            $row.find('.js-opt-sale-total').val(saleTotal.toFixed(2));
            $row.find('.js-opt-diff').val(diff.toFixed(2));
            $row.find('.js-opt-company-profit').val(profits.company.toFixed(2));
            $row.find('.js-opt-guide-profit').val(profits.guide.toFixed(2));
        }

        function recalcInputCard($row) {
            var amount = toNumber($row.find('.js-input-amount').val());
            var count = toNumber($row.find('.js-input-count').val());
            var total = amount * count;
            $row.find('.js-input-total').val(total.toFixed(2));
            $row.find('.js-input-total-display').text(formatMoney(total));
        }

        function recalcGuideSettleForm() {
            var $form = $('#gsaDetailForm');
            if (!$form.length || $form.data('edit-mode') !== 1 && $form.data('edit-mode') !== '1') {
                return;
            }

            var mealTotals = {
                bf: 0,
                lunch: 0,
                dinner: 0
            };
            $('#gsaMealList_bf .js-meal-row').each(function () {
                recalcMealCard($(this));
                mealTotals.bf += toNumber($(this).find('.js-row-total').val());
            });
            $('#gsaMealList_lunch .js-meal-row').each(function () {
                recalcMealCard($(this));
                mealTotals.lunch += toNumber($(this).find('.js-row-total').val());
            });
            $('#gsaMealList_dinner .js-meal-row').each(function () {
                recalcMealCard($(this));
                mealTotals.dinner += toNumber($(this).find('.js-row-total').val());
            });

            var mealSum = mealTotals.bf + mealTotals.lunch + mealTotals.dinner;
            $('#gsaMealTotal_bf').text(formatMoney(mealTotals.bf));
            $('#gsaMealTotal_lunch').text(formatMoney(mealTotals.lunch));
            $('#gsaMealTotal_dinner').text(formatMoney(mealTotals.dinner));
            $('#gsaSectionMealTotal').text(formatMoney(mealSum));

            var admissionSum = 0;
            $('#gsaAdmissionList .js-admission-row').each(function () {
                recalcAdmissionCard($(this));
                admissionSum += toNumber($(this).find('.js-row-total').val());
            });
            $('#gsaSectionAdmissionTotal').text(formatMoney(admissionSum));

            var optionCostSum = 0;
            var optionSaleSum = 0;
            var optionCompanySum = 0;
            var optionGuideSum = 0;
            $('#gsaOptionList .js-option-row').each(function () {
                recalcOptionCard($(this));
                optionCostSum += toNumber($(this).find('.js-opt-cost-total').val());
                optionSaleSum += toNumber($(this).find('.js-opt-sale-total').val());
                optionCompanySum += toNumber($(this).find('.js-opt-company-profit').val());
                optionGuideSum += toNumber($(this).find('.js-opt-guide-profit').val());
            });
            $('#gsaSectionOptionTotal').text(formatMoney(optionSaleSum));
            $('#gsaMetricOptionCompany').text(formatMoney(optionCompanySum));

            var etcTotals = {
                guide: 0,
                car: 0,
                etc: 0
            };
            $('#gsaEtcList_guide .js-etc-row').each(function () {
                etcTotals.guide += toNumber($(this).find('.js-etc-amount').val());
            });
            $('#gsaEtcList_car .js-etc-row').each(function () {
                etcTotals.car += toNumber($(this).find('.js-etc-amount').val());
            });
            $('#gsaEtcList_etc .js-etc-row').each(function () {
                etcTotals.etc += toNumber($(this).find('.js-etc-amount').val());
            });
            var etcSum = etcTotals.guide + etcTotals.car + etcTotals.etc;
            $('#gsaEtcTotal_guide').text(formatMoney(etcTotals.guide));
            $('#gsaEtcTotal_car').text(formatMoney(etcTotals.car));
            $('#gsaEtcTotal_etc').text(formatMoney(etcTotals.etc));
            $('#gsaSectionEtcTotal').text(formatMoney(etcSum));

            var inputSum = 0;
            $('#collapseInput .js-input-row').each(function () {
                recalcInputCard($(this));
                inputSum += toNumber($(this).find('.js-input-total').val());
            });
            $('#gsaSectionInputTotal').text(formatMoney(inputSum));
            $('#gsaMetricInputSum').text(formatMoney(inputSum));

            var shoppingHomeCom = 0;
            var shoppingGuideProfit = 0;
            $('.js-shopping-home-com').each(function () {
                shoppingHomeCom += toNumber($(this).val());
            });
            $('.js-shopping-guide-profit').each(function () {
                shoppingGuideProfit += toNumber($(this).val());
            });
            var shoppingSum = shoppingHomeCom + shoppingGuideProfit;
            $('#gsaMetricShopping').text(formatMoney(shoppingSum));

            var preAmount = toNumber($form.data('pre-amount'));
            var totalDeposit = preAmount + optionCompanySum + inputSum;
            var totalPay = mealSum + admissionSum + etcSum + shoppingSum;
            var settleTotal = totalDeposit - totalPay;

            $('#gsaMetricTotalPay').text(formatMoney(totalPay));
            $('#gsaCalcTotal').text(formatMoney(settleTotal));
            $('#gsaStickyTotal').text(formatMoney(settleTotal));
        }

        $(document).on('click', '.js-gsa-add-row', function () {
            var targetSelector = $(this).data('target');
            var templateSelector = $(this).data('template');
            var template = $(templateSelector).html();
            if (!template) {
                return;
            }

            $(targetSelector).append(template);
            recalcGuideSettleForm();
        });

        $(document).on('click', '.js-gsa-remove-row', function () {
            $(this).closest('.gsa-edit-card').remove();
            recalcGuideSettleForm();
        });

        $(document).on('input change', '.js-row-count, .js-row-unit, .js-opt-count, .js-opt-cost, .js-opt-sale, .js-opt-ratio, .js-etc-amount, .js-input-amount, .js-input-count', function () {
            recalcGuideSettleForm();
        });

        $(document).on('click', '.js-gsa-action', function () {
            var $button = $(this);
            var actionName = $button.data('action');
            var settleCode = $button.data('settle-code');
            var seqNo = $button.data('seq-no');
            var payload = {
                action: actionName
            };

            if (settleCode) {
                payload.settle_code = settleCode;
            }
            if (seqNo) {
                payload.seq_no = seqNo;
            }

            $button.prop('disabled', true);
            $.post('action.php', payload)
                .done(function (response) {
                    if (response && response.ok) {
                        window.location.reload();
                        return;
                    }

                    alert(response && response.error ? response.error : '처리 중 오류가 발생했습니다.');
                })
                .fail(function () {
                    alert('처리 중 오류가 발생했습니다.');
                })
                .always(function () {
                    $button.prop('disabled', false);
                });
        });

        function recalcCheckSum() {
            var $form = $('#gsaCheckForm');
            if (!$form.length) {
                return;
            }

            var sum = 0;
            $('#gsaCheckList .js-check-row').each(function () {
                sum += toNumber($(this).find('.js-check-amount').val());
            });
            $('#gsaCheckStickyTotal').text(formatMoney(sum));
        }

        $(document).on('click', '.js-check-add-row', function () {
            var template = $('#tpl-check-row').html();
            if (!template) {
                return;
            }

            $('#gsaCheckList').append(template);
            recalcCheckSum();
        });

        $(document).on('click', '.js-check-remove-row', function () {
            var $rows = $('#gsaCheckList .js-check-row');
            if ($rows.length <= 1) {
                $(this).closest('.js-check-row').find('input').val('');
                recalcCheckSum();
                return;
            }

            $(this).closest('.js-check-row').remove();
            recalcCheckSum();
        });

        $(document).on('input change', '.js-check-amount', function () {
            recalcCheckSum();
        });

        recalcGuideSettleForm();
        recalcCheckSum();
    });
})(jQuery);
