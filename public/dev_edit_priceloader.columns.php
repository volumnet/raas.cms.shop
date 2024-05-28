<?php
/**
 * Виджет набора полей колонок при редактировании загрузчика прайсов
 */
namespace RAAS\CMS\Shop;

use RAAS\FieldSet;

$_RAASForm_FieldSet = function (FieldSet $fieldSet) use (&$_RAASForm_Form_Tabbed, &$_RAASForm_Form_Plain) {
    $DATA = $fieldSet->Form->DATA;
    $CONTENT = $fieldSet->Form->meta['CONTENT'];
    ?>
    <fieldset>
      <legend><?php echo htmlspecialchars($fieldSet->caption)?></legend>
      <table class="table table-striped table-condensed cms-shop-edit-priceloader-columns-table" data-role="raas-repo-block">
        <thead>
          <tr>
            <th class="span1"></th>
            <th class="span4"><?php echo Module::i()->view->_('MATERIAL_FIELD')?></th>
            <th class="span4"><?php echo Module::i()->view->_('COLUMN_CALLBACK')?></th>
            <th class="span1"><?php echo Module::i()->view->_('UNIQUE')?></th>
            <th></th>
          </tr>
        </thead>
        <tbody data-role="raas-repo-container">
          <?php foreach ((array)($DATA['column_id'] ?? []) as $i => $temp) { ?>
              <tr data-role="raas-repo-element">
                <td></td>
                <td>
                  <input type="hidden" name="column_id[]" value="<?php echo (int)$DATA['column_id'][$i]?>" />
                  <select name="column_fid[]" data-role="field-id-column">
                    <option value="" <?php echo !$DATA['column_fid'][$i] ? 'selected="selected"' : ''?>>--</option>
                    <?php foreach ($CONTENT['fields'] as $row) { ?>
                        <option value="<?php echo htmlspecialchars($row['value'])?>" <?php echo $DATA['column_fid'][$i] == $row['value'] ? 'selected="selected"' : ''?>>
                          <?php echo htmlspecialchars($row['caption'])?>
                        </option>
                    <?php } ?>
                  </select>
                </td>
                <td>
                  <div>
                    <i class="icon icon-upload" title="<?php echo Module::i()->view->_('FOR_UPLOAD')?>"></i>
                    <textarea name="column_callback[]" placeholder="<?php echo Module::i()->view->_('FOR_UPLOAD')?>"><?php echo htmlspecialchars($DATA['column_callback'][$i])?></textarea><br />
                  </div>
                  <div>
                    <i class="icon icon-download-alt" title="<?php echo Module::i()->view->_('FOR_DOWNLOAD')?>"></i>
                    <textarea name="column_download_callback[]" placeholder="<?php echo Module::i()->view->_('FOR_DOWNLOAD')?>"><?php echo htmlspecialchars($DATA['column_download_callback'][$i])?></textarea>
                  </div>
                </td>
                <td><input type="radio" name="ufid" value="<?php echo htmlspecialchars($DATA['column_fid'][$i])?>" <?php echo $DATA['ufid'] == $DATA['column_fid'][$i] ? 'checked="checked"' : ''?> <?php echo !$DATA['column_fid'][$i] ? 'disabled="disabled"' : ''?> /></td>
                <td><a href="#" class="close" data-role="raas-repo-del">&times;</a></td>
              </tr>
          <?php } ?>
        </tbody>
        <tbody>
          <tr data-role="raas-repo">
            <td></td>
            <td>
              <input type="hidden" name="column_id[]" value="" disabled="disabled" />
              <select name="column_fid[]" data-role="field-id-column" disabled="disabled">
                <option value="" selected="selected">--</option>
                <?php foreach ($CONTENT['fields'] as $row) { ?>
                    <option value="<?php echo htmlspecialchars($row['value'])?>">
                      <?php echo htmlspecialchars($row['caption'])?>
                    </option>
                <?php } ?>
              </select>
            </td>
            <td>
              <i class="icon icon-upload" title="<?php echo Module::i()->view->_('FOR_UPLOAD')?>"></i>
              <textarea name="column_callback[]" placeholder="<?php echo Module::i()->view->_('FOR_UPLOAD')?>"></textarea><br />
              <i class="icon icon-download-alt" title="<?php echo Module::i()->view->_('FOR_DOWNLOAD')?>"></i>
              <textarea name="column_download_callback[]" placeholder="<?php echo Module::i()->view->_('FOR_DOWNLOAD')?>"></textarea>
            </td>
            <td><input type="radio" name="ufid" value="" disabled="disabled" /></td>
            <td><a href="#" class="close" data-role="raas-repo-del">&times;</a></td>
          </tr>
          <tr><td></td><td></td><td><input type="button" class="btn" value="<?php echo Module::i()->view->_('ADD')?>" data-role="raas-repo-add" /></td></tr>
        </tbody>
      </table>
    </fieldset>
<?php } ?>
