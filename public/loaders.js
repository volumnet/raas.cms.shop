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

        var image_field = $('#loader option:selected').attr('data-image-field');
        if (image_field) {
            $('[data-role="image-field"]').text(image_field);
            $('[data-role="image-field__container"]').show();
        } else {
            $('[data-role="image-field"]').text('');
            $('[data-role="image-field__container"]').hide();
        }

        $('[data-role="material-type-container"]').text($('#loader option:selected').attr('data-material-type'))

        checkDownloadUrl();
    }

    var checkDownloadUrl = function() {
        var rows = parseInt($('#rows').val());
        rows = isNaN(rows) ? 0 : rows;
        var cols = parseInt($('#cols').val());
        cols = isNaN(cols) ? 0 : cols;
        var cat_id = parseInt($('#cat_id').val());
        cat_id = isNaN(cat_id) ? 0 : cat_id;
        
        var loader_id = parseInt($('#loader').val());
        if ($.isNumeric(loader_id) && (loader_id > 0)) {
            $('[data-role="download-button"]' + ($('[data-role="download-button"] ul li a').length > 0 ? ' ul li a' : '')).each(function() {
                var url = $(this).attr('data-href');
                url += '&loader=' + loader_id;
                if (cols) {
                    url += '&cols=' + cols;
                }
                if (rows) {
                    url += '&rows=' + rows;
                }
                if (cat_id) {
                    url += '&cat_id=' + cat_id;
                }
                $(this).attr('href', url);
            });
            $('[data-role="download-button"]').removeAttr('onclick');
        } else {
            var onclick = 'alert(\'' + $('[data-role="download-button"]').attr('data-no-loader-hint').replace(/'/, '\\\'') + '\'); return false;';
            $('[data-role="download-button"]').attr('onclick', onclick);
            $('[data-role="download-button"]' + ($('[data-role="download-button"] ul li a').length > 0 ? ' ul li a' : '')).attr('href', '#');
        }
    }

    var checkColsRows = function() {
        var rows = parseInt($('#loader option:selected').attr('data-rows'));
        rows = isNaN(rows) ? 0 : rows;
        var cols = parseInt($('#loader option:selected').attr('data-cols'));
        cols = isNaN(cols) ? 0 : cols;
        var cat_id = parseInt($('#loader option:selected').attr('data-cat_id'));
        cat_id = isNaN(cat_id) ? 0 : cat_id;
        
        $('#cols').val(cols);
        $('#rows').val(rows);
        $('#cat_id').val(cat_id);
    }


    var createDataTable = function(data, log)
    {
        try {
            var col_names = JSON.parse($('#loader option:selected').attr('data-col-names'));
            var text = '<table class="table table-striped"><thead><tr>';
            if (log && log.length) {
                text += '<th>' + timeName + '</th>';
            }
            for (var j = 0; j < col_names.length; j++) {
                if (col_names[j].text) {
                    text += '<th>' + col_names[j].text + '</th>';
                }
            }
            text += '</thead><tbody>';
            var hint;
            for (var i = 0; i < data.length; i++) {
                hint = '';
                if (log && (log instanceof Array)) {
                    hint = $.grep(log, function(x) { return x.row == i; });
                    if (hint && hint.length) {
                        hint = hint[0];
                    }
                }
                text += '<tr>';
                if (log && log.length) {
                    text += '<th>';
                    if (hint != '') {
                        text += '<a class="btn small-btn" data-toggle="tooltip" title="' + hint.text.replace(/<\/?[^>]+>/gi, '') + '">' + hint.time + '</a>';
                    }
                    text += '</th>';
                }
                for (j = 0; j < col_names.length; j++) {
                    if (col_names[j].text) {
                        text += '<td>' + data[i][j] + '</td>';
                    }
                }
                text += '</tr>';
            }
            text += '</tbody></table>';
            $('#tab_data').append(text);
            $('#tab_data [data-toggle="tooltip"]').tooltip();
        } catch(e) {
        }
    }


    var createLog = function(log)
    {
        var text = '<div>';
        var hint;
        for (var i = 0; i < log.length; i++) {
            text += '<p><span class="muted">' + log[i].time + ':</span> ' + log[i].text + '</p>';
        }
        text += '</div>';
        $('#tab_log').append(text);
    }


    $('#loader').on('change', function() { checkColsRows(); checkLoader(); });
    $('#rows, #cols, #cat_id').on('change', checkDownloadUrl);
    $('#show_log').on('click', checkDownloadUrl);
    checkLoader();

    if ((typeof raw_data !== 'undefined') && raw_data) {
        createDataTable(raw_data, ((typeof log !== 'undefined') && log) ? log : []);
    }
    if ((typeof log !== 'undefined') && log) {
        createLog(log);
    }
});