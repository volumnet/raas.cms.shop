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
    ?>
    <table class="table table-striped table-condensed" data-role="raas-repo-block">
      <thead>
        <tr>
          <th class="span2"><?php echo Module::i()->view->_('DELIVERY_OPTION_COST')?></th>
          <th class="span2"><?php echo Module::i()->view->_('DELIVERY_OPTION_DAYS')?></th>
          <th class="span2"><?php echo Module::i()->view->_('DELIVERY_OPTION_ORDER_BEFORE')?></th>
          <th class="span1"></th>
        </tr>
      </thead>
      <tbody data-role="raas-repo-container">
        <?php foreach ((array)($DATA[$field->name] ?? []) as $i => $temp) { ?>
            <tr data-role="raas-repo-element">
              <td>
                <input type="number" class="span2" name="<?php echo htmlspecialchars($field->name)?>@cost[]" value="<?php echo htmlspecialchars($DATA[$field->name][$i]['cost'])?>" />
              </td>
              <td>
                <input type="text" class="span2" name="<?php echo htmlspecialchars($field->name)?>@days[]" value="<?php echo htmlspecialchars($DATA[$field->name][$i]['days'])?>" />
              </td>
              <td>
                <input type="text" class="span2" name="<?php echo htmlspecialchars($field->name)?>@order_before[]" value="<?php echo htmlspecialchars($DATA[$field->name][$i]['order_before'])?>" />
              </td>
              <td>
                <a href="#" class="close" data-role="raas-repo-del">&times;</a>
              </td>
            </tr>
        <?php } ?>
      </tbody>
      <tbody>
        <tr data-role="raas-repo">
          <td>
            <input type="number" class="span2" name="<?php echo htmlspecialchars($field->name)?>@cost[]" disabled="disabled" />
          </td>
          <td>
            <input type="text" class="span2" name="<?php echo htmlspecialchars($field->name)?>@days[]" disabled="disabled" />
          </td>
          <td>
            <input type="text" class="span2" name="<?php echo htmlspecialchars($field->name)?>@order_before[]" disabled="disabled" />
          </td>
          <td>
            <a href="#" class="close" data-role="raas-repo-del">&times;</a>
          </td>
        </tr>
        <tr>
          <td></td>
          <td></td>
          <td></td>
          <td>
            <input type="button" class="btn" value="<?php echo ADD?>" data-role="raas-repo-add" />
          </td>
        </tr>
      </tbody>
    </table>
<?php } ?>
