<?php
/**
 * Добавление типа материалов в блок Яндекс-Маркета
 */
namespace RAAS\CMS\Shop;

use RAAS\Application;
use RAAS\FieldSet;
use RAAS\CMS\ViewSub_Main as CMSViewSubMain;

include CMSViewSubMain::i()->tmp('/field.inc.php');

$_RAASForm_FieldSet = function (FieldSet $FieldSet) use (&$_RAASForm_Control) {
    $Table = $FieldSet->meta['Table'];
    $Item = $FieldSet->Form->Item;
    $Page = $FieldSet->meta['Page'];
    include CMSViewSubMain::i()->tmp('/table.inc.php');
    ?>
    <fieldset>
      <legend><?php echo htmlspecialchars($FieldSet->caption)?></legend>
      <table<?php echo $_RAASTable_Attrs($Table)?>>
        <?php if ($Table->header) { ?>
            <thead>
              <tr>
                <?php
                foreach ($Table->columns as $key => $col) {
                    include Application::i()->view->context->tmp('/column.inc.php');
                    if ($col->template) {
                        include Application::i()->view->context->tmp($col->template);
                    }
                    $_RAASTable_Header($col, $key);
                }
                ?>
              </tr>
            </thead>
        <?php } ?>
        <tbody>
          <?php if ((array)$Table->Set) {
              for ($i = 0; $i < count($Table->rows); $i++) {
                  $row = $Table->rows[$i];
                  include Application::i()->view->context->tmp('/row.inc.php');
                  if ($row->template) {
                      include Application::i()->view->context->tmp($row->template);
                  }
                  $_RAASTable_Row($row, $i);
              }
          } ?>
          <tr>
            <td><?php echo $_RAASForm_Control($FieldSet->children['types_select'])?></td>
            <td></td>
            <td>
              <a href="#" class="btn btn-default" id="create_yml_type" data-block-id="<?php echo (int)$Item->id?>" data-block-pid="<?php echo (int)$Page->id?>">
                <?php echo CREATE?>
              </a>
            </td>
          </tr>
        </tbody>
      </table>
    </fieldset>
<?php } ?>
