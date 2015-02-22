<?php 
$_RAASForm_FormTab = function(\RAAS\FormTab $FormTab) use (&$_RAASForm_Form_Tabbed, &$_RAASForm_Form_Plain, &$_RAASForm_Attrs) {
    $Table = $FormTab->meta['Table'];
    include \RAAS\CMS\Shop\Module::i()->view->tmp('/table.inc.php');
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
            <tr>
              <?php 
              foreach ($Table->columns as $key => $col) { 
                  echo '<th>';
                  include \RAAS\Application::i()->view->context->tmp('/field.inc.php');
                  if (isset($FormTab->children[$key])) {
                      if ($key == 'description') {
                          echo '<input type="text" name="description" id="description" style="margin: 0;" required="required" />';
                      } else {
                          echo $_RAASForm_Control($FormTab->children[$key]);
                      }
                  } elseif ($key == 'uid') {
                      echo '<button type="submit" class="btn btn-primary">' . SAVE . '</button>';
                  }
                  echo '</th>';
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
            }
            ?>
          </tbody>
      <?php } ?>
    </table>
    <?php 
};