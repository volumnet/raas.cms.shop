jQuery(document).ready(function($) {
    var formatPrice = function (price)
    {
        var pR = Math.round((parseFloat(price) - parseInt(price)) * 100);
        var pS = parseInt(price).toString();
        var pT = '';
        var i;

        for (i = 0; i < pS.length; i++) {
            var j = pS.length - i - 1;
            pT = ((i % 3 == 2) && (j > 0) ? ' ' : '') + pS.substr(j, 1) + pT;
        }
        if (pR > 0) {
            pR = pR.toString();
            if (pR.length < 2) {
                pR = '0' + pR;
            }
            pT += ',' + pR;
        }
        return pT;
    };

    var calculate = function () {
        var totalSum = 0;
        $('[data-role="raas-repo-container"] tr').each(function () {
            var realprice = parseFloat($('[name="realprice\[\]"]', this).val()) || 0;
            var amount = parseInt($('[name="amount\[\]"]', this).val()) || 0;
            var sum = realprice * amount;
            totalSum += sum;
            $('[data-role="sum"]', this).text(formatPrice(sum));
        });
        $('[data-role="total-sum"]').text(formatPrice(totalSum));
    };

    $('[datatype="material"]').on('RAAS.Shop.material-field.selected', function () {
        var realprice = parseFloat($(this).attr('data-material-price')) || 0;
        var $tr = $(this).closest('tr');
        var $amount = $('[name="amount\[\]"]', $tr);
        var $realprice = $('[name="realprice\[\]"]', $tr);
        $realprice.val(realprice);
        if (!$amount.val()) {
            $amount.val(1);
        }
        calculate();
    });

    $('[name="amount\[\]"], [name="realprice\[\]"]').on('change', function () {
        calculate();
    });

    $('[data-role="raas-repo-container"]').on('RAAS_repo.delete', function () {
        window.setTimeout(function () {
            calculate();
        }, 0);
    });

    calculate();
});