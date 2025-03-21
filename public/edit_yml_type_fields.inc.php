<?php
$_RAASForm_FieldSet = function(\RAAS\FieldSet $fieldSet) {
    $Form = $fieldSet->Form;
    ?>
    <table class="table table-striped table-condensed" data-role="yml-fields-table">
      <thead>
        <tr>
          <th><?php echo CMS\Shop\FIELD_NAME?></th>
          <th><?php echo CMS\Shop\FIELD?></th>
          <th>
            <?php echo CMS\Shop\CALLBACK?>
            <span style="font-weight: normal">
              <raas-hint><?php echo CMS\Shop\FIELDS_CALLBACK_HINT?></raas-hint>
            </span>
          </th>
          <th><?php echo CMS\Shop\STATIC_VALUE?></th>
        </tr>
      </thead>
      <tbody>
        <?php
        foreach ($fieldSet->children as $key => $row) {
            $err = (bool)array_filter(
                (array)$fieldSet->Form->localError,
                function ($x) use ($row) {
                    return $x['value'] == $row->name;
                }
            );
            ?>
            <tr <?php echo ($row->{'data-types'} ? 'data-types="' . htmlspecialchars($row->{'data-types'}) . '"' : '') . ($err ? ' class="error"' : '')?>>
              <td>
                <?php echo htmlspecialchars($row->caption) . ($row->{'data-required'} ? '*' : '')?><br />
                <small style="font-size: 10px; color: gray"><?php echo htmlspecialchars($row->name)?></small>
              </td>
              <td><?php echo $row->children['field_id']->render()?></td>
              <td><?php echo $row->children['field_callback']->render()?></td>
              <td>
                <?php
                if ($row->children['field_value']->type != 'hidden') {
                    echo $row->children['field_value']->render();
                }
                ?>
              </td>
            </tr>
        <?php } ?>
      </tbody>
    </table>
<?php } ?>
