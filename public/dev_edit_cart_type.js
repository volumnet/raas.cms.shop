jQuery(function($) {
    $('[data-role="price_id"]').change(function() {
        if (($(this).val() === '') || ($(this).val() === '0')) {
            $(this).closest('tr').find('[data-role="callback"]').removeAttr('disabled');
        } else {
            $(this).closest('tr').find('[data-role="callback"]').val('').attr('disabled', 'disabled');
        }
    })

    var checkInterface = function(first) {
        $obj = $('.control-group:has(#description)');
        $txt = $('#description');
        if ((parseInt($('select#std_template').val()) > 0) || $('input#std_template:checkbox').attr('checked')) {
            first === true ? $obj.hide() : $obj.fadeOut();
            $txt.attr('disabled', 'disabled');
        } else {
            $txt.removeAttr('disabled', 'disabled');
            first === true ? $obj.show() : $obj.fadeIn();
        }
    }
    
    checkInterface(true);
    $('#std_template').change(checkInterface);
});