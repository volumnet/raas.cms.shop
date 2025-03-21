<?php
/**
 * Виджет набора полей колонок при редактировании загрузчика прайсов
 */
namespace RAAS\CMS\Shop;

use RAAS\FieldSet;

/**
 * Отображает группу полей
 * @param FieldSet $fieldSet Группа полей для отображения
 */
$_RAASForm_FieldSet = function (FieldSet $fieldSet) {
    $DATA = $fieldSet->Form->DATA;
    $CONTENT = $fieldSet->Form->meta['CONTENT'];
    $repoData = [];
    foreach ((array)($DATA['column_id'] ?? []) as $i => $temp) {
        $repoRow = [
            'id' => $DATA['column_id'][$i] ?? null,
            'fid' => $DATA['column_fid'][$i] ?? null,
            'callback' => $DATA['column_callback'][$i] ?? '',
            'download_callback' => $DATA['column_download_callback'][$i] ?? '',
        ];
        $repoData[] = $repoRow;
    }
    $ufid = $DATA['ufid'] ?? null;
    ?>
    <fieldset>
      <legend><?php echo htmlspecialchars($fieldSet->caption)?></legend>
      <raas-repo-table
        class="table table-striped table-condensed cms-shop-edit-priceloader-columns-table"
        :model-value="<?php echo htmlspecialchars(json_encode($repoData))?>"
        :defval="{ id: null, fid: null, callback: '', download_callback: '' }"
        :columns="<?php echo htmlspecialchars(json_encode([
            '',
            Module::i()->view->_('MATERIAL_FIELD'),
            Module::i()->view->_('COLUMN_CALLBACK'),
            Module::i()->view->_('UNIQUE'),
          ]))?>"
        :sortable="true"
        v-slot="repo"
      >
        <component is="td"></component>
        <component is="td">
          <input type="hidden" name="column_id[]" :value="repo.modelValue.id" />
          <raas-field-select
            name="column_fid[]"
            data-role="material-type-field"
            :source="<?php echo htmlspecialchars(json_encode(array_merge(
                [['value' => '', 'caption' => '--']],
                $CONTENT['fields']
            )))?>"
            :model-value="repo.modelValue.fid"
            @update:model-value="repo.emit('update:modelValue', {...repo.modelValue, fid: $event })"
          ></raas-field-select>
        </component>
        <component is="td">
          <div>
            <raas-icon icon="upload" title="<?php echo Module::i()->view->_('FOR_UPLOAD')?>"></raas-icon>
            <raas-field-textarea
              name="column_callback[]"
              placeholder="<?php echo Module::i()->view->_('FOR_UPLOAD')?>"
              :model-value="repo.modelValue.callback"
              @update:model-value="repo.emit('update:modelValue', {...repo.modelValue, callback: $event })"
            ></raas-field-textarea>
          </div>
          <div>
            <raas-icon icon="download" title="<?php echo Module::i()->view->_('FOR_DOWNLOAD')?>"></raas-icon>
            <raas-field-textarea
              name="column_download_callback[]"
              placeholder="<?php echo Module::i()->view->_('FOR_DOWNLOAD')?>"
              :model-value="repo.modelValue.download_callback"
              @update:model-value="repo.emit('update:modelValue', {...repo.modelValue, download_callback: $event })"
            ></raas-field-textarea>
          </div>
        </component>
        <component is="td">
          <raas-field-radio
            name="ufid"
            :defval="repo.modelValue.fid"
            :disabled="!repo.modelValue.fid"
            :model-value="<?php echo htmlspecialchars(json_encode($ufid))?>"
          ></raas-field-radio>
        </component>
      </raas-repo-table>
    </fieldset>
<?php } ?>
