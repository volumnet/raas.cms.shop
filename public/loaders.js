jQuery(document).ready(function($) {
    var checkLoader = function() {
        var col_names = '';
        try {
            col_names = JSON.parse($('#loader option:selected').attr('data-col-names'));
        } catch(e) {
            col_names = '';
        }
        $('table[data-role="headers-table"] tr').empty();
        if (col_names) {
            for (var i = 0; i < col_names.length; i++) {
                $('table[data-role="headers-table"] tr').append('<th' + (col_names[i].unique ? ' class="unique"' : '') + '>' + col_names[i].text + '</th>');
            }
            $('[data-role="headers-table__container"]').show();
        } else {
            $('[data-role="headers-table__container"]').hide();
        }

        var file_format = $('#loader option:selected').attr('data-file-format');
        if (file_format) {
            $('[data-role="file-format"]').text(file_format);
            $('[data-role="file-format__container"]').show();
        } else {
            $('[data-role="file-format"]').text('');
            $('[data-role="file-format__container"]').hide();
        }

        checkDownloadUrl();
    }

    var checkDownloadUrl = function() {
        var rows = parseInt($('#rows').val());
        rows = isNaN(rows) ? 0 : rows;
        var cols = parseInt($('#cols').val());
        cols = isNaN(cols) ? 0 : cols;
        
        var url = $('[data-role="loader-form"]').attr('action') + '&action=download';
        var loader_id = parseInt($('#loader').val());
        if ($.isNumeric(loader_id) && (loader_id > 0)) {
            url += '&loader=' + loader_id;
            if (cols) {
                url += '&cols=' + cols;
            }
            if (rows) {
                url += '&rows=' + rows;
            }
            if ($('#show_log').is(':checked')) {
                url += '&show_log=1';
            }
            $('[data-role="download-button"]').removeAttr('onclick').attr('href', url);
        } else {
            var onclick = 'return confirm(\'' + $('[data-role="download-button"]').attr('data-no-loader-hint').replace(/'/, '\\\'') + '\')';
            $('[data-role="download-button"]').attr('onclick', onclick).attr('href', '#');
        }
    }

    var checkColsRows = function() {
        var rows = parseInt($('#loader option:selected').attr('data-rows'));
        rows = isNaN(rows) ? 0 : rows;
        var cols = parseInt($('#loader option:selected').attr('data-cols'));
        cols = isNaN(cols) ? 0 : cols;
        
        $('#cols').val(cols);
        $('#rows').val(rows);
    }

    $('#loader').on('change', function() { checkColsRows(); checkLoader(); });
    $('#rows, #cols').on('change', checkDownloadUrl);
    $('#show_log').on('click', checkDownloadUrl);
    checkLoader();
});