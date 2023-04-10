<?php 
$_RAASForm_FieldSet = function(\RAAS\FieldSet $FieldSet) use (&$_RAASForm_Form_Tabbed, &$_RAASForm_Form_Plain) { 
    $DATA = $FieldSet->Form->DATA;
    $CONTENT = $FieldSet->Form->meta['CONTENT'];
    ?>
    <fieldset>
      <legend><?php echo htmlspecialchars($FieldSet->caption)?></legend>
      <table class="table table-striped table-condensed" data-role="raas-repo-block">
        <thead>
          <tr>
            <th class="span3"><?php echo CMS\MATERIAL_TYPE?></th>
            <th class="span3"><?php echo CMS\Shop\PRICE_COLUMN?></th>
            <th class="span4">
              <?php echo CMS\Shop\PRICE_CALLBACK?><br />
              <small><?php echo CMS\Shop\USE_X_AS_MATERIAL_OBJECT?></small>
            </th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ((array)$CONTENT['material_types'] as $mtype) { ?>
              <tr>
                <td style="padding-left: <?php echo ($mtype->level * 30)?>px"><?php echo htmlspecialchars($mtype->name)?></td>
                <td>
                  <select name="price_id[<?php echo (int)$mtype->id?>]" class="span2" data-role="price_id">
                    <?php foreach ($CONTENT['fields'][(int)$mtype->id] as $row) { ?>
                        <option value="<?php echo htmlspecialchars($row->id)?>" <?php echo $DATA['price_id'][$mtype->id] == $row->id ? 'selected="selected"' : ''?>>
                          <?php echo htmlspecialchars($row->name)?>
                        </option>
                    <?php } ?>
                  </select>
                </td>
                <td>
                  <input type="text" name="price_callback[<?php echo (int)$mtype->id?>]" value="<?php echo htmlspecialchars($DATA['price_callback'][$mtype->id] ?? '')?>" <?php echo ($DATA['price_id'][$mtype->id] ?? 0) ? 'disabled="disabled"' : ''?> data-role="callback" />
                </td>
              </tr>
          <?php } ?>
        </tbody>
      </table>
    </fieldset>
<?php } ?>
