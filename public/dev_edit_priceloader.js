jQuery(document).ready(function($) {
    window.setTimeout(() => {
        var checkCheckboxChecked = function () {
            if (!$('[data-role="raas-repo-container"] input:radio:not(:disabled):checked').length) {
                $('[data-role="raas-repo-container"] input:radio:not(:disabled):eq(0)').attr('checked', 'checked');
            }
        };

        var updateCheckbox = function() {
            var val = $(this).val();
            if (val) {
                $(this).closest('tr').find('input:radio[name="ufid"]').attr('value', val).removeAttr('disabled');
            } else {
                $(this).closest('tr').find('input:radio[name="ufid"]').attr('value', val).attr('disabled', 'disabled').removeAttr('checked');
                checkCheckboxChecked();
            }
        }

        
        $('#mtype').change(function() {
            var mtype = $(this).val();
            $('[data-role="field-id-column"]').each(function () {
                var self = this;
                var $self = $(this);
                var val = $(this).val();
                $(this).RAAS_getSelect(
                    'ajax.php?p=cms&m=shop&action=material_fields&id=' + mtype, 
                    {
                        before: function(data) { 
                            data.Set.unshift({ text: '--', val: '' }); 
                            return data.Set; 
                        },
                        after: function() { 
                            $self.val(val); 
                            updateCheckbox.call(self);
                        }
                    }
                )
            });
        })

        $('[data-role="raas-repo-block"]').on('click', '[data-role="raas-repo-del"], [data-role="raas-repo-add"]', function() {
            checkCheckboxChecked();
            $('[data-role="field-id-column"]').each(updateCheckbox);
        });
        $('[data-role="field-id-column"]').change(updateCheckbox);
    }, 0); // Чтобы успел отработать Vue
});