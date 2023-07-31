jQuery(function($) {
    window.setTimeout(() => {
        $('[data-role="price_id"]').change(function() {
            if (($(this).val() === '') || ($(this).val() === '0')) {
                $(this).closest('tr').find('[data-role="callback"]').removeAttr('disabled');
            } else {
                $(this).closest('tr').find('[data-role="callback"]').val('').attr('disabled', 'disabled');
            }
        })
    }, 0); // Чтобы успел отработать Vue
});