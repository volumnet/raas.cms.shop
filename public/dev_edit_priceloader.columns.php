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
            <th class="span4"><?php echo CMS\MATERIAL_FIELD?></th>
            <th class="span4"><?php echo CMS\Shop\COLUMN_CALLBACK?></th>
            <th class="span1"><?php echo CMS\Shop\UNIQUE?></th>
            <th></th>
          </tr>
        </thead>
        <tbody data-role="raas-repo-container">
          <?php foreach ((array)$DATA['column_id'] as $i => $temp) { ?>
              <tr data-role="raas-repo-element">
                <td>
                  <input type="hidden" name="column_id[]" value="<?php echo (int)$DATA['column_id'][$i]?>" />
                  <select name="column_fid[]" data-role="field-id-column">
                    <option value="" <?php echo !$DATA['column_fid'][$i] ? 'selected="selected"' : ''?>>--</option>
                    <?php foreach ($CONTENT['fields'] as $row) { ?>
                        <option value="<?php echo htmlspecialchars($row['value'])?>" <?php echo $DATA['column_fid'][$i] == $row['value'] ? 'selected="selected"' : ''?>>
                          <?php echo htmlspecialchars($row['caption'])?>
                        </option>
                    <?php } ?>
                  </select>
                </td>
                <td><input type="text" name="column_callback[]" value="<?php echo htmlspecialchars($DATA['column_callback'][$i])?>" /></td>
                <td><input type="radio" name="ufid" value="<?php echo htmlspecialchars($DATA['column_fid'][$i])?>" <?php echo $DATA['ufid'] == $DATA['column_fid'][$i] ? 'checked="checked"' : ''?> <?php echo !$DATA['column_fid'][$i] ? 'disabled="disabled"' : ''?> /></td>
                <td><a href="#" class="close" data-role="raas-repo-del">&times;</a></td>
              </tr>
          <?php } ?>
        </tbody>
        <tbody>
          <tr data-role="raas-repo">
            <td>
              <input type="hidden" name="column_id[]" value="" disabled="disabled" />
              <select name="column_fid[]" data-role="field-id-column" disabled="disabled">
                <option value="" selected="selected">--</option>
                <?php foreach ($CONTENT['fields'] as $row) { ?>
                    <option value="<?php echo htmlspecialchars($row['value'])?>">
                      <?php echo htmlspecialchars($row['caption'])?>
                    </option>
                <?php } ?>
              </select>
            </td>
            <td><input type="text" name="column_callback[]" value="" disabled="disabled" /></td>
            <td><input type="radio" name="ufid" value="" disabled="disabled" /></td>
            <td><a href="#" class="close" data-role="raas-repo-del">&times;</a></td>
          </tr>
          <tr><td></td><td></td><td><input type="button" class="btn" value="<?php echo ADD?>" data-role="raas-repo-add" /></td></tr>
        </tbody>
      </table>
    </fieldset>
<?php } ?>