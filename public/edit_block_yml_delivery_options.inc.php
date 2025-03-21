<?php
/**
 * Поле "Варианты доставки (или самовывоза)"
 */
namespace RAAS\CMS\Shop;

use RAAS\Application;
use RAAS\Field as RAASField;

/**
 * Отображает поле
 * @param RAASField $field Поле для отображения
 */
$_RAASForm_Control = function (RAASField $field) {
    $DATA = $field->Form->DATA;
    $repoData = [];
    foreach ((array)($DATA[$field->name] ?? []) as $i => $temp) {
        $repoRow = [
            'cost' => $DATA[$field->name][$i]['cost'] ?? '',
            'days' => $DATA[$field->name][$i]['days'] ?? '',
            'order_before' => $DATA[$field->name][$i]['order_before'] ?? '',
        ];
        $repoData[] = $repoRow;
    }
    ?>
    <raas-repo-table
      class="table table-striped table-condensed"
      :model-value="<?php echo htmlspecialchars(json_encode($repoData))?>"
      :defval="{ cost: '', days: '', order_before: '' }"
      :columns="<?php echo htmlspecialchars(json_encode([
          Module::i()->view->_('DELIVERY_OPTION_COST'),
          Module::i()->view->_('DELIVERY_OPTION_DAYS'),
          Module::i()->view->_('DELIVERY_OPTION_ORDER_BEFORE'),
        ]))?>"
      :sortable="true"
      v-slot="repo"
    >
      <component is="td">
        <raas-field-number
          name="<?php echo htmlspecialchars($field->name)?>@cost[]"
          :model-value="repo.modelValue.cost"
          @update:model-value="repo.emit('update:modelValue', {...repo.modelValue, cost: $event })"
        ></raas-field-number>
      </component>
      <component is="td">
        <raas-field-text
          name="<?php echo htmlspecialchars($field->name)?>@days[]"
          :model-value="repo.modelValue.days"
          @update:model-value="repo.emit('update:modelValue', {...repo.modelValue, days: $event })"
        ></raas-field-text>
      </component>
      <component is="td">
        <raas-field-text
          name="<?php echo htmlspecialchars($field->name)?>@order_before[]"
          :model-value="repo.modelValue.order_before"
          @update:model-value="repo.emit('update:modelValue', {...repo.modelValue, order_before: $event })"
        ></raas-field-text>
      </component>
    </raas-repo-table>
<?php } ?>
