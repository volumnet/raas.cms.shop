<?php 
namespace RAAS\CMS\Shop;

use RAAS\Application;
use RAAS\FieldSet;

/**
 * Отображает список товаров заказа
 * @param FieldSet $fieldSet Группа полей
 */
$_RAASForm_FieldSet = function (FieldSet $FieldSet) {
    $Table = $FieldSet->meta['Table'];
    if ((array)$Table->Set) { 
        ?>
        <table<?php echo $Table->getAttrsString()?>>
          <?php if ($Table->header) { ?>
              <thead>
                <tr>
                  <?php 
                  foreach ($Table->columns as $key => $col) { 
                      echo $col->renderHeader($key);
                  } 
                  ?>
                </tr>
              </thead>
          <?php } ?>
          <?php if ((array)$Table->Set) { ?>
              <tbody>
                <?php 
                $sum = 0;
                for ($i = 0; $i < count($Table->rows); $i++) { 
                    $row = $Table->rows[$i];
                    echo $row->render($i);
                    $sum += $row->source->amount * $row->source->realprice;
                }
                ?>
                <tr>
                  <th colspan="4" style="text-align: right"><?php echo Module::i()->view->_('TOTAL_SUM')?>: </th>
                  <th style="white-space: nowrap"><?php echo number_format($sum, 2, '.', ' ')?></th>
                </tr>
              </tbody>
          <?php } ?>
        </table>
        <?php 
    }
};
