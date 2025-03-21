<?php 
/**
 * Виджет отображения дополнительных параметров в YML-выгрузке
 */
namespace RAAS\CMS\Shop;

use RAAS\FieldSet;

/**
 * Отображает группу полей
 * @param FieldSet $fieldSet Группа полей
 */
$_RAASForm_FieldSet = function(FieldSet $fieldSet) {
    $DATA = $fieldSet->Form->DATA;
    $MType = $fieldSet->Form->meta['MType'];
    $fields = $fieldSet->Form->filterFieldsByType($MType);

    $repoData = [];
    foreach ((array)($DATA['add_param_name'] ?? []) as $i => $temp) {
        $repoRow = [
            'name' => $DATA['add_param_name'][$i] ?? '',
            'field' => $DATA['add_param_field'][$i] ?? '',
            'callback' => $DATA['add_param_callback'][$i] ?? '',
            'value' => $DATA['add_param_value'][$i] ?? '',
            'unit' => $DATA['add_param_unit'][$i] ?? '',
        ];
        $repoData[] = $repoRow;
    }
    ?>

    <raas-repo-table
      class="table table-striped table-condensed"
      :model-value="<?php echo htmlspecialchars(json_encode($repoData))?>"
      :defval="{ name: '', field: '', callback: '', value: '', unit: '' }"
      :sortable="true"
    >
      <template #header>
        <component is="tr">
          <component is="th"><?php echo Module::i()->view->_('PARAM_NAME')?></component>
          <component is="th"><?php echo Module::i()->view->_('FIELD')?></component>
          <component is="th">
            <?php echo Module::i()->view->_('CALLBACK')?>
            <span style="font-weight: normal">
              <raas-hint><?php echo Module::i()->view->_('FIELDS_CALLBACK_HINT')?></raas-hint>
            </span>
          </component>
          <component is="th"><?php echo Module::i()->view->_('STATIC_VALUE')?></component>
          <component is="th"><?php echo Module::i()->view->_('PARAM_UNIT')?></component>
          <component is="th"></component>
        </component>
      </template>
      <template #default="repo">
        <component is="td">
          <raas-field-text
            class="span2"
            name="add_param_name[]"
            :model-value="repo.modelValue.name"
            @update:model-value="repo.emit('update:modelValue', {...repo.modelValue, name: $event })"
          ></raas-field-text>
        </component>
        <component is="td">
          <raas-field-select
            class="span2"
            name="add_param_field[]"
            :source="<?php echo htmlspecialchars(json_encode(array_merge([['value' => '', 'caption' => '--']], $fields)))?>"
            :model-value="repo.modelValue.field"
            @update:model-value="repo.emit('update:modelValue', {...repo.modelValue, field: $event })"
          ></raas-field-select>
        </component>
        <component is="td">
          <raas-field-text
            name="add_param_callback[]"
            :model-value="repo.modelValue.callback"
            @update:model-value="repo.emit('update:modelValue', {...repo.modelValue, callback: $event })"
          ></raas-field-text>
        </component>
        <component is="td">
          <raas-field-text
            class="span2"
            name="add_param_value[]"
            :model-value="repo.modelValue.value"
            @update:model-value="repo.emit('update:modelValue', {...repo.modelValue, value: $event })"
          ></raas-field-text>
        </component>
        <component is="td">
          <raas-field-text
            class="span1"
            name="add_param_unit[]"
            :model-value="repo.modelValue.unit"
            @update:model-value="repo.emit('update:modelValue', {...repo.modelValue, unit: $event })"
          ></raas-field-text>
        </component>
      </template>
    </raas-repo-table>
<?php } ?>
