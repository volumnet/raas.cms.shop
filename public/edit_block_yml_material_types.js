jQuery(document).ready(function($) {
    var checkMaterialTypeAddress = function() {
        var id = parseInt($('#create_yml_type').attr('data-block-id'));
        id = isNaN(id) ? 0 : id;
        var pid = parseInt($('#create_yml_type').attr('data-block-pid'));
        pid = isNaN(pid) ? 0 : pid;
        var mtype = parseInt($('#types_select').val());
        mtype = isNaN(mtype) ? 0 : mtype;
        url = '?p=cms&m=shop&action=edit_yml_type&id=' + id + '&pid=' + pid + '&mtype=' + mtype;
        $('#create_yml_type').attr('href', url);
        // alert(url);
    }

    $('#types_select, #yml_types_select').on('change', checkMaterialTypeAddress);
    checkMaterialTypeAddress();
});