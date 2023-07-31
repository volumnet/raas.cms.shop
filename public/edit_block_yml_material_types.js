jQuery(document).ready(function($) {
    window.setTimeout(() => {
        var checkMaterialTypeAddress = function() {
            var id = parseInt($('#create_yml_type').attr('data-block-id')) || 0;
            var pid = parseInt($('#create_yml_type').attr('data-block-pid')) || 0;
            var mtype = parseInt($('#types_select').val()) || 0;
            url = '?p=cms&m=shop&action=edit_yml_type&id=' + id + '&pid=' + pid + '&mtype=' + mtype;
            $('#create_yml_type').attr('href', url);
            // alert(url);
        }

        $('#types_select, #yml_types_select').on('change', checkMaterialTypeAddress);
        checkMaterialTypeAddress();
    }, 0); // Чтобы успел отработать Vue
});