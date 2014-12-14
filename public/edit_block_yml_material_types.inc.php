<?php 
$_RAASForm_FieldSet = function(\RAAS\FieldSet $FieldSet) use (&$_RAASForm_Form_Tabbed, &$_RAASForm_Form_Plain, &$_RAASForm_Control) { 
    $Table = $FieldSet->meta['Table'];
    $Item = $FieldSet->Form->Item;
    $Page = $FieldSet->meta['Page'];
    include \RAAS\CMS\ViewSub_Main::i()->tmp('/table.inc.php');
    ?>
    <fieldset>
      <legend><?php echo htmlspecialchars($FieldSet->caption)?></legend>
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
        <tbody>
          <?php if ((array)$Table->Set) { ?>
              <?php 
              for ($i = 0; $i < count($Table->rows); $i++) { 
                  $row = $Table->rows[$i];
                  include \RAAS\Application::i()->view->context->tmp('/row.inc.php');
                  if ($row->template) {
                      include \RAAS\Application::i()->view->context->tmp($row->template);
                  }
                  $_RAASTable_Row($row, $i);
                  ?>
              <?php } ?>
          <?php } ?>
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