<?php 
/**
 * Отображение типов материалов для редактирования корзины
 */
namespace RAAS\CMS\Shop;

use RAAS\FieldSet;

/**
 * Отображает группу полей
 * @param FieldSet $fieldSet Группа полей для отображения
 */
$_RAASForm_FieldSet = function(FieldSet $FieldSet) {
    $DATA = $FieldSet->Form->DATA;
    $CONTENT = $FieldSet->Form->meta['CONTENT'];
    ?>
    <fieldset>
      <legend><?php echo htmlspecialchars($FieldSet->caption)?></legend>
      <table class="table table-striped table-condensed">
        <thead>
          <tr>
            <th><?php echo Module::i()->view->_('MATERIAL_TYPE')?></th>
            <th><?php echo Module::i()->view->_('PRICE_COLUMN')?></th>
            <th>
              <?php echo Module::i()->view->_('PRICE_CALLBACK')?><br />
              <small><?php echo Module::i()->view->_('USE_X_AS_MATERIAL_OBJECT')?></small>
            </th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ((array)$CONTENT['material_types'] as $mtype) { ?>
              <tr>
                <td style="padding-left: <?php echo ($mtype->level * 30)?>px">
                  <?php echo htmlspecialchars($mtype->name)?>
                </td>
                <td>
                  <raas-field-select
                    name="price_id[<?php echo (int)$mtype->id?>]"
                    data-role="price_id"
                    :source="<?php
                      echo htmlspecialchars(json_encode(array_map(
                          fn($x) => ['value' => (int)$x->id, 'caption' => $x->name],
                          $CONTENT['fields'][(int)$mtype->id]
                      )))?>"
                    :model-value="<?php echo htmlspecialchars(json_encode($DATA['price_id'][$mtype->id] ?? null))?>"
                  ></raas-field-select>
                </td>
                <td>
                  <raas-field-text
                    name="price_callback[<?php echo (int)$mtype->id?>]"
                    :model-value="<?php echo htmlspecialchars(json_encode($DATA['price_callback'][$mtype->id] ?? ''))?>"
                    <?php echo ($DATA['price_id'][$mtype->id] ?? 0) ? 'disabled="disabled"' : ''?> data-role="callback"
                  ></raas-field-text>
                </td>
              </tr>
          <?php } ?>
        </tbody>
      </table>
    </fieldset>
<?php } ?>
