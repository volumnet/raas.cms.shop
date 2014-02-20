jQuery(function($) {
    $('[data-role="price_id"]').change(function() {
        if (($(this).val() === '') || ($(this).val() === '0')) {
            $(this).closest('tr').find('[data-role="callback"]').removeAttr('disabled');
        } else {
            $(this).closest('tr').find('[data-role="callback"]').val('').attr('disabled', 'disabled');
        }
    })

    if ($('.code#description').length) {
        if (parseInt($('select#interface_id').val()) > 0) {
            $('.control-group:has(.code#description)').hide();
        }
        $('#interface_id').change(function() {
            if (parseInt($(this).val()) > 0) {
                $('.control-group:has(.code#description)').fadeOut();
            } else {
                $('.control-group:has(.code#description)').fadeIn();
            }
        })
    }
});