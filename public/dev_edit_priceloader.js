jQuery(document).ready(function($) {
    window.setTimeout(() => {
        $('#mtype').change(function() {
            var mtype = $(this).val();
            $('[data-role="material-type-field"]').each(function () {
                var self = this;
                var $self = $(this);
                var val = $(this).val();
                $(this).RAAS_getSelect(
                    'ajax.php?p=cms&m=shop&action=material_fields&id=' + mtype, 
                    {
                        before(data) { 
                            data.Set.unshift({ text: '--', val: '' }); 
                            return data.Set; 
                        },
                        after() { 
                            $self.val(val); 
                            updateCheckbox.call(self);
                        }
                    }
                )
            });
        })
    }, 0); // Чтобы успел отработать Vue
});