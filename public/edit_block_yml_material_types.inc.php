<?php 
$_RAASForm_FieldSet = function(\RAAS\FieldSet $FieldSet) use (&$_RAASForm_Form_Tabbed, &$_RAASForm_Form_Plain, &$_RAASForm_Control) { 
    $Table = $FieldSet->meta['Table'];
    include \RAAS\CMS\ViewSub_Main::i()->tmp('/table.inc.php');
    ?>
    <fieldset>
      <legend><?php echo htmlspecialchars($FieldSet->caption)?></legend>
      <?php if ((array)$Table->Set || ($Table->emptyHeader && $Table->header)) { ?>
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
                  for ($i = 0; $i < count($Table->rows); $i++) { 
                      $row = $Table->rows[$i];
                      include \RAAS\Application::i()->view->context->tmp('/row.inc.php');
                      if ($row->template) {
                          include \RAAS\Application::i()->view->context->tmp($row->template);
                      }
                      $_RAASTable_Row($row, $i);
                      ?>
                  <?php } ?>
                </tbody>
            <?php } ?>
          </table>
      <?php } ?>
      <?php if (!(array)$Table->Set && $Table->emptyString) { ?>
        <p><?php echo htmlspecialchars($Table->emptyString)?></p>
      <?php } ?>
    </fieldset>
<?php } ?>