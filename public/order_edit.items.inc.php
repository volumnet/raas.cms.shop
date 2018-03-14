<?php
namespace RAAS\CMS\Shop;

use RAAS\FieldSet;
use RAAS\Application;
use RAAS\CMS\Material;

include Module::i()->view->tmp('cms/field.inc.php');

$_RAASForm_FieldSet = function (FieldSet $FieldSet) use (&$_RAASForm_Form_Tabbed, &$_RAASForm_Form_Plain) {
    $Item = $FieldSet->Form->Item;
    $DATA = $FieldSet->Form->DATA;
    ?>
    <table class="table table-striped" data-role="raas-repo-block">
      <thead>
        <tr>
          <th><?php echo Module::i()->view->_('NAME')?></th>
          <th><?php echo Module::i()->view->_('ADDITIONAL_INFO')?></th>
          <th><?php echo Module::i()->view->_('PRICE')?></th>
          <th><?php echo Module::i()->view->_('AMOUNT')?></th>
          <th><?php echo Module::i()->view->_('SUM')?></th>
          <th></th>
        </tr>
      </thead>
      <tbody data-role="raas-repo-container">
        <?php foreach ((array)$DATA['material'] as $key => $materialId) {
            $material = new Material($materialId); ?>
            <tr data-role="raas-repo-element">
              <td>
                <input datatype="material" type="hidden" data-cart-type-id="<?php echo $FieldSet->meta['Cart_Type']->id?>" data-material-id="<?php echo (int)$DATA['material'][$key]?>" data-material-name="<?php echo htmlspecialchars($material->name)?>" name="material[]" value="<?php echo (int)$DATA['material'][$key]?>" />
              </td>
              <td>
                <input type="text" name="meta[]" value="<?php echo htmlspecialchars($DATA['meta'][$key])?>" />
              </td>
              <td>
                <input type="number" name="realprice[]" step="0.01" class="span2" value="<?php echo (float)$DATA['realprice'][$key]?>" />
              </td>
              <td>
                <input type="number" name="amount[]" class="span1" value="<?php echo (float)$DATA['amount'][$key]?>" />
              </td>
              <td data-role="sum"></td>
              <td>
                <a href="#" class="close" data-role="raas-repo-del">&times;</a>
              </td>
            </tr>
        <?php } ?>
      </tbody>
      <tbody>
        <tr data-role="raas-repo">
          <td>
            <input datatype="material" type="hidden" data-cart-type-id="<?php echo $FieldSet->meta['Cart_Type']->id?>" name="material[]" disabled="disabled" />
          </td>
          <td>
            <input type="text" name="meta[]" disabled="disabled" />
          </td>
          <td>
            <input type="number" name="realprice[]" step="0.01" class="span2" disabled="disabled" />
          </td>
          <td>
            <input type="number" name="amount[]" class="span1" disabled="disabled" />
          </td>
          <td data-role="sum"></td>
          <td>
            <a href="#" class="close" data-role="raas-repo-del">&times;</a>
          </td>
        </tr>
      </tbody>
      <tbody>
        <tr>
          <th><input type="button" class="btn" value="<?php echo ADD?>" data-role="raas-repo-add" /></th>
          <th colspan="3" style="text-align: right"><?php echo Module::i()->view->_('TOTAL_SUM')?>: </th>
          <th style="white-space: nowrap" data-role="total-sum"></th>
        </tr>
      </tbody>
    </table>
    <?php
};
