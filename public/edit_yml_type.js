jQuery(document).ready(function($) {
    var checkYMLType = function()
    {
        var ymlType = $('#type').val();
        var rx = new RegExp('(^|;)' + ymlType + '(;|$)');
        $('[data-role="yml-fields-table"] tr').each(function() {
            if (!$(this).attr('data-types') || rx.test($(this).attr('data-types'))) {
                $(this).show();
            } else {
                $(this).hide();
                $('select, input', this).val('');
            }
        })
    }

    var checkIgnored = function()
    {
        var isChecked = $('#param_exceptions').is(':checked');
        if (isChecked) {
            $('[name^="ignore_param"]').closest('.control-group').show();
            $('[name="params_callback"]').closest('.control-group').show();
        } else {
            $('[name^="ignore_param"]').closest('.control-group').hide();
            $('[name="params_callback"]').closest('.control-group').hide();
        }
    }

    $('#type').on('change', checkYMLType);
    $('#param_exceptions').on('change', checkIgnored);
    $('[name^="field_id"], [name^="add_param_name"]').on('change', function() {
        if ($(this).val() != '') {
            $(this).closest('td')
        }
    })
    checkYMLType();
    checkIgnored();

});