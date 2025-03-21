<?php 
$_RAASForm_FieldSet = function(\RAAS\FieldSet $fieldSet)  {
    $DATA = $fieldSet->Form->DATA;
    $CONTENT = $fieldSet->Form->meta['CONTENT'];
    ?>
    <fieldset>
      <legend><?php echo htmlspecialchars($fieldSet->caption)?></legend>
      <table class="table table-striped table-condensed" data-role="currencies">
        <thead>
          <tr>
            <th><?php echo NAME?></th>
            <th colspan="2" style="text-align: center"><?php echo CMS\Shop\CURRENCY_RATE?></th>
            <th><?php echo CMS\Shop\CURRENCY_PLUS?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($fieldSet->children as $key => $row) { ?>
              <tr data-role="currency-row" data-currency="<?php echo htmlspecialchars($key)?>">
                <td><?php echo htmlspecialchars($row->caption)?></td>
                <td><?php echo $row->children['rate']->render()?></td>
                <td><?php echo $row->children['rate_txt']->render()?></td>
                <td><?php echo $row->children['plus']->render()?></td>
              </tr>
          <?php } ?>
        </tbody>
      </table>
    </fieldset>
<?php } ?>
