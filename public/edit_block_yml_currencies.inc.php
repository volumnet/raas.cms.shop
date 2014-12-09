<?php 
$_RAASForm_FieldSet = function(\RAAS\FieldSet $FieldSet) use (&$_RAASForm_Form_Tabbed, &$_RAASForm_Form_Plain, &$_RAASForm_Control) { 
    $DATA = $FieldSet->Form->DATA;
    $CONTENT = $FieldSet->Form->meta['CONTENT'];
    ?>
    <fieldset>
      <legend><?php echo htmlspecialchars($FieldSet->caption)?></legend>
      <table class="table table-striped table-condensed" data-role="currencies">
        <thead>
          <tr>
            <th><?php echo NAME?></th><th colspan="2" style="text-align: center"><?php echo CMS\Shop\CURRENCY_RATE?></th><th><?php echo CMS\Shop\CURRENCY_PLUS?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($FieldSet->children as $key => $row) { ?>
              <tr data-role="currency-row" data-currency="<?php echo htmlspecialchars($key)?>">
                <td><?php echo htmlspecialchars($row->caption)?></td>
                <td><?php echo $_RAASForm_Control($row->children['rate'])?></td>
                <td><?php echo $_RAASForm_Control($row->children['rate_txt'])?></td>
                <td><?php echo $_RAASForm_Control($row->children['plus'])?></td>
              </tr>
          <?php } ?>
        </tbody>
      </table>
    </fieldset>
<?php } ?>