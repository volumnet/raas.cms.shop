<?php 
namespace RAAS\CMS\Shop;

use RAAS\Application;
use RAAS\FormTab;

/**
 * Отображает историю заказа
 * @param FormTab $formTab Вкладка
 */
$_RAASForm_FormTab = function(FormTab $formTab) {
    $Table = $formTab->meta['Table'];
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
            <tr>
              <?php 
              foreach ($Table->columns as $key => $col) { 
                  echo '<th>';
                  if (isset($formTab->children[$key])) {
                      if ($key == 'description') {
                          echo '<input type="text" name="description" id="description" style="margin: 0;" required="required" />';
                      } else {
                          echo $formTab->children[$key]->render();
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
                echo $Table->rows[$i]->render($i);
            }
            ?>
          </tbody>
      <?php } ?>
    </table>
    <?php 
};
