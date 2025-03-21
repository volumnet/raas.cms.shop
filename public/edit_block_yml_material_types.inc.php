<?php
/**
 * Добавление типа материалов в блок Яндекс-Маркета
 */
namespace RAAS\CMS\Shop;

use RAAS\Application;
use RAAS\FieldSet;
use RAAS\CMS\ViewSub_Main as CMSViewSubMain;


$_RAASForm_FieldSet = function (FieldSet $fieldSet) {
    $table = $fieldSet->meta['Table'];
    $Item = $fieldSet->Form->Item;
    $Page = $fieldSet->meta['Page'];
    ?>
    <fieldset>
      <legend><?php echo htmlspecialchars($fieldSet->caption)?></legend>
      <table<?php echo $table->getAttrsString()?>>
        <?php echo $table->renderHeader() . $table->renderBody() ?>
        <tfoot>
          <tr>
            <td><?php echo $fieldSet->children['types_select']->render()?></td>
            <td></td>
            <td style="text-align: right">
              <a href="#" class="btn btn-default" id="create_yml_type" data-block-id="<?php echo (int)$Item->id?>" data-block-pid="<?php echo (int)$Page->id?>">
                <?php echo CREATE?>
              </a>
            </td>
          </tr>
        </tfoot>
      </table>
    </fieldset>
<?php } ?>
