<?php
$_RAASForm_FieldSet = function(\RAAS\FieldSet $FieldSet) use (&$_RAASForm_Form_Tabbed, &$_RAASForm_Form_Plain, &$_RAASForm_Control) {
    $Form = $FieldSet->Form;
    $DATA = $Form->DATA;
    $MType = $Form->meta['MType'];
    $fields = $Form->filterFieldsByType($MType);
    ?>
    <table class="table table-striped table-condensed" data-role="yml-fields-table">
      <thead>
        <tr>
          <th><?php echo CMS\Shop\FIELD_NAME?></th>
          <th><?php echo CMS\Shop\FIELD?></th>
          <th>
            <?php echo CMS\Shop\CALLBACK?>
            <span style="font-weight: normal">
              <a class="btn" href="#" rel="popover" data-content="<?php echo CMS\Shop\FIELDS_CALLBACK_HINT?>"><i class="icon-question-sign"></i></a>
            </span>
          </th>
          <th><?php echo CMS\Shop\STATIC_VALUE?></th>
        </tr>
      </thead>
      <tbody>
        <?php
        foreach ($FieldSet->children as $key => $row) {
            $err = (bool)array_filter(
                (array)$FieldSet->Form->localError,
                function ($x) use ($row) {
                    return $x['value'] == $row->name;
                }
            );
            ?>
            <tr <?php echo ($row->{'data-types'} ? 'data-types="' . htmlspecialchars($row->{'data-types'}) . '"' : '') . ($err ? ' class="error"' : '')?>>
              <td><?php echo htmlspecialchars($row->caption) . ($row->{'data-required'} ? '*' : '')?><br /><small style="font-size: 10px; color: gray"><?php echo htmlspecialchars($row->name)?></small></td>
              <td><?php echo $_RAASForm_Control($row->children['field_id'])?></td>
              <td><?php echo $_RAASForm_Control($row->children['field_callback'])?></td>
              <td>
                <?php
                if ($row->children['field_value']->type != 'hidden') {
                    echo $_RAASForm_Control($row->children['field_value']);
                }
                ?>
              </td>
            </tr>
        <?php } ?>
      </tbody>
    </table>
<?php } ?>
