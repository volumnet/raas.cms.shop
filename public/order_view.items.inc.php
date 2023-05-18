<?php 
$_RAASForm_FieldSet = function(\RAAS\FieldSet $FieldSet) use (&$_RAASForm_Form_Tabbed, &$_RAASForm_Form_Plain) { 
    $Table = $FieldSet->meta['Table'];
    include \RAAS\CMS\Shop\Module::i()->view->tmp('/table.inc.php');
    if ((array)$Table->Set) { 
        ?>
        <table<?php echo $_RAASTable_Attrs($Table)?>>
          <?php if ($Table->header) { ?>
              <thead>
                <tr>
                  <?php 
                  foreach ($Table->columns as $key => $col) { 
                      include \RAAS\Application::i()->view->context->tmp('/column.inc.php');
                      if ($col->template) {
                          include \RAAS\Application::i()->view->context->tmp($col->template);
                      }
                      $_RAASTable_Header($col, $key);
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
                    include \RAAS\Application::i()->view->context->tmp('/row.inc.php');
                    if ($row->template) {
                        include \RAAS\Application::i()->view->context->tmp($row->template);
                    }
                    $_RAASTable_Row($row, $i);
                    $sum += $row->source->amount * $row->source->realprice;
                }
                ?>
                <tr>
                  <th colspan="4" style="text-align: right"><?php echo CMS\Shop\TOTAL_SUM?>: </th>
                  <th style="white-space: nowrap"><?php echo number_format($sum, 2, '.', ' ')?></th>
                </tr>
              </tbody>
          <?php } ?>
        </table>
        <?php 
    }
};
