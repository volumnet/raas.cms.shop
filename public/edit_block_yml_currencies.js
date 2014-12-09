jQuery(document).ready(function($) {
    var checkRow = function($row) {
        if ($('[data-role="currency-selector"]', $row).val() == '-1') {
            $('[data-role="currency-rate"]', $row).removeAttr('disabled');
        } else {
            $('[data-role="currency-rate"]', $row).attr('disabled', 'disabled').val('');
        }
        if ($('[data-role="currency-selector"]', $row).val() == '') {
            $('[data-role="currency-plus"]', $row).val('');
        }
    }

    var checkDefaultCurrency = function()
    {
        var cur = $('#default_currency').val().toLowerCase();
        $('[data-role="currency-row"]').each(function() {
            if ($(this).attr('data-currency') == cur) {
                $(this).hide()
                $('[data-role="currency-selector"]', this).val('')
                checkRow($(this));
                $('select, input', this).attr('disabled', 'disabled');
            } else {
                $(this).show();
                $('select, input', this).removeAttr('disabled');
                checkRow($(this));
            }
        })
    }

    $('[data-role="currency-row"]').on('change', '[data-role="currency-selector"]', function() {
        checkRow($(this).closest('tr'));
    })
    $('#default_currency').on('change', checkDefaultCurrency);
    checkDefaultCurrency();
});