jQuery(document).ready(function($) {
    window.setTimeout(() => {
        $('#mtype').change(function() {
            $('#ufid').RAAS_getSelect(
                'ajax.php?p=cms&m=shop&action=material_fields&id=' + $(this).val(), 
                {
                    before: function(data) { return data.Set; },
                }
            );
            $('#ifid').RAAS_getSelect(
                'ajax.php?p=cms&m=shop&action=image_fields&id=' + $(this).val(), 
                {
                    before: function(data) { return data.Set; },
                }
            );
        })
    }, 0); // Чтобы успел отработать Vue
});