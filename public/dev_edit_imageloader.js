jQuery(document).ready(function($) {
    var checkInterface = function(first) {
        $obj = $('.control-group:has(#description)');
        $txt = $('#description');
        if ((parseInt($('select#std_interface').val()) > 0) || $('input#std_interface:checkbox').attr('checked')) {
            first === true ? $obj.hide() : $obj.fadeOut();
            $txt.attr('disabled', 'disabled');
        } else {
            $txt.removeAttr('disabled', 'disabled');
            first === true ? $obj.show() : $obj.fadeIn();
        }
    }
    
    checkInterface(true);

    $('#mtype').change(function() {
        $('#ufid').RAAS_getSelect(
            'ajax.php?p=cms&m=shop&action=material_fields&id=' + $(this).val(), 
            {
                before: function(data) { return data.Set; },
            }
        );
    })
    $('#std_interface').change(checkInterface);
});