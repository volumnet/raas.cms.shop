<?php
/**
 * Поле легенды для уведомлений статуса
 */
namespace RAAS\CMS\Shop;

use RAAS\Field as RAASField;


$_RAASForm_Field = function(RAASField $field) {
    $DATA = $field->Form->DATA;
    ?>
    <p><?php echo View_Web::i()->_('YOU_CAN_USE_FOLLOWING_PLACEHOLDERS')?>:</p>
    <pre v-pre><?php
    $textArr = [];
    foreach ($DATA[$field->name] as $key => $val) {
        $textArr[] = '{{' . mb_strtoupper($key) . '}} — ' . $val;
    }
    echo implode("\n", $textArr);
    ?></pre>
    <?php
};
