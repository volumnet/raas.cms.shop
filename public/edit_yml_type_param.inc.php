<?php 
$_RAASForm_FieldSet = function(\RAAS\FieldSet $FieldSet) use (&$_RAASForm_Form_Tabbed, &$_RAASForm_Form_Plain, &$_RAASForm_Control) { 
    $DATA = $FieldSet->Form->DATA;
    $MType = $FieldSet->Form->meta['MType'];
    $fields = $FieldSet->Form->filterFieldsByType($MType);
    ?>
    <table class="table table-striped table-condensed" data-role="raas-repo-block">
      <thead>
        <tr>
          <th><?php echo CMS\Shop\PARAM_NAME?></th>
          <th><?php echo CMS\Shop\FIELD?></th>
          <th>
            <?php echo CMS\Shop\CALLBACK?> 
            <span style="font-weight: normal">
              <a class="btn" href="#" rel="popover" data-content="<?php echo CMS\Shop\FIELDS_CALLBACK_HINT?>"><i class="icon-question-sign"></i></a>
            </span>
          </th>
          <th><?php echo CMS\Shop\STATIC_VALUE?></th>
          <th></th>
        </tr>
      </thead>
      <tbody data-role="raas-repo-container">
        <?php foreach ((array)$DATA['add_param_name'] as $i => $temp) { ?>
            <tr data-role="raas-repo-element">
              <td><input type="text" name="add_param_name[]" value="<?php echo htmlspecialchars($DATA['add_param_name'][$i])?>" /></td>
              <td>
                <select class="span2" name="add_param_field[]">
                  <option value="" <?php echo !$DATA['add_param_field'][$i] ? 'selected="selected"' : ''?>>--</option>
                  <?php foreach ($fields as $row) { ?>
                      <option value="<?php echo htmlspecialchars($row['value'])?>" <?php echo $DATA['add_param_field'][$i] == $row['value'] ? 'selected="selected"' : ''?>>
                        <?php echo htmlspecialchars($row['caption'])?>
                      </option>
                  <?php } ?>
                </select>
              </td>
              <td><input type="text" name="add_param_callback[]" value="<?php echo htmlspecialchars($DATA['add_param_callback'][$i])?>" /></td>
              <td><input type="text" name="add_param_value[]" value="<?php echo htmlspecialchars($DATA['add_param_value'][$i])?>" /></td>
              <td><a href="#" class="close" data-role="raas-repo-del">&times;</a></td>
            </tr>
        <?php } ?>
      </tbody>
      <tbody>
        <tr data-role="raas-repo">
          <td><input type="text" name="add_param_name[]" value="<?php echo htmlspecialchars($DATA['add_param_name'][$i])?>" disabled="disabled" /></td>
          <td>
            <select class="span2" name="add_param_field[]" disabled="disabled">
              <option value="" <?php echo !$DATA['add_param_field'][$i] ? 'selected="selected"' : ''?>>--</option>
              <?php foreach ($fields as $row) { ?>
                  <option value="<?php echo htmlspecialchars($row['value'])?>" <?php echo $DATA['add_param_field'][$i] == $row['value'] ? 'selected="selected"' : ''?>>
                    <?php echo htmlspecialchars($row['caption'])?>
                  </option>
              <?php } ?>
            </select>
          </td>
          <td><input type="text" name="add_param_callback[]" value="<?php echo htmlspecialchars($DATA['add_param_callback'][$i])?>" disabled="disabled" /></td>
          <td><input type="text" name="add_param_value[]" value="<?php echo htmlspecialchars($DATA['add_param_value'][$i])?>" disabled="disabled" /></td>
          <td><a href="#" class="close" data-role="raas-repo-del">&times;</a></td>
        </tr>
      </tbody>
    </table>
<?php } ?>