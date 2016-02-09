<?php
$smsField = function($field)
{
    $values = $field->getValues(true);
    $arr = array();
    foreach ($values as $key => $val) {
        $val = $field->doRich($val);
        switch ($field->datatype) {
            case 'date':
                $arr[$key] = date(DATEFORMAT, strtotime($val));
                break;
            case 'datetime-local':
                $arr[$key] = date(DATETIMEFORMAT, strtotime($val));
                break;
            case 'file': case 'image':
                $arr[$key] .= $val->name;
                break;
            case 'htmlarea':
                $arr[$key] = strip_tags($val);
                break;
            default:
                if (!$field->multiple && ($field->datatype == 'checkbox')) {
                    $arr[$key] = $val ? _YES : _NO;
                } else {
                    $arr[$key] = $val;
                }
                break;
        }
    }
    return $field->name . ': ' . implode(', ', $arr) . "\n";
};
$emailField = function($field)
{
    $values = $field->getValues(true);
    $arr = array();
    foreach ($values as $key => $val) {
        $val = $field->doRich($val);
        switch ($field->datatype) {
            case 'date':
                $arr[$key] = date(DATEFORMAT, strtotime($val));
                break;
            case 'datetime-local':
                $arr[$key] = date(DATETIMEFORMAT, strtotime($val));
                break;
            case 'color':
                $arr[$key] = '<span style="display: inline-block; height: 16px; width: 16px; background-color: ' . htmlspecialchars($val) . '"></span>';
                break;
            case 'email':
                $arr[$key] .= '<a href="mailto:' . htmlspecialchars($val) . '">' . htmlspecialchars($val) . '</a>';
                break;
            case 'url':
                $arr[$key] .= '<a href="http://' . htmlspecialchars(str_replace('http://', '', $val)) . '">' . htmlspecialchars($val) . '</a>';
                break;
            case 'file':
                $arr[$key] .= '<a href="http://' . $_SERVER['HTTP_HOST'] . '/' . $val->fileURL . '">' . htmlspecialchars($val->name) . '</a>';
                break;
            case 'image':
                $arr[$key] .= '<a href="http://' . $_SERVER['HTTP_HOST'] . '/' . $val->fileURL . '"><img src="http://' . $_SERVER['HTTP_HOST'] . '/' . $val->tnURL. '" alt="' . htmlspecialchars($val->name) . '" title="' . htmlspecialchars($val->name) . '" /></a>';
                break;
            case 'htmlarea':
                $arr[$key] = '<div>' . $val . '</div>';
                break;
            default:
                if (!$field->multiple && ($field->datatype == 'checkbox')) {
                    $arr[$key] = $val ? _YES : _NO;
                } else {
                    $arr[$key] = nl2br(htmlspecialchars($val));
                }
                break;
        }
    }
    return '<div>' . htmlspecialchars($field->name) . ': ' . implode(', ', $arr) . '</div>';
};
?>
<?php if ($SMS) {
    foreach ($Item->fields as $field) {
        echo $smsField($field);
    }
} else { ?>
    <div>
      <?php
      foreach ($Item->fields as $field) {
          echo $emailField($field);
      }
      ?>
    </div>
    <?php if ($Item->items) { ?>
      <br />
      <table style="width: 100%" border="1">
        <thead>
          <tr>
            <th><?php echo NAME?></th>
            <th><?php echo ADDITIONAL_INFO?></th>
            <th><?php echo PRICE?></th>
            <th><?php echo AMOUNT?></th>
            <th><?php echo SUM?></th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $sum = 0; 
          foreach ($Item->items as $row) { 
            $url = ($forUser ? $row->url : '/admin/?p=cms&sub=main&action=edit_material&id=' . $row->id . '&pid=' . ($row->material_type->affectedPages[0]->id)); ?>
            <tr>
              <td>
                <?php if ($url) { ?>
                    <a href="http://<?php echo htmlspecialchars($_SERVER['HTTP_HOST'] . $url)?>">
                      <?php echo htmlspecialchars($row->name)?>
                    </a>
                <?php } else { ?>
                    <?php echo htmlspecialchars($row->name)?>
                <?php } ?>
              </td>
              <td><?php echo htmlspecialchars($row->meta)?>&nbsp;</td>
              <td style="text-align: right"><?php echo number_format($row->realprice, 2, '.', ' ')?></td>
              <td><?php echo (int)$row->amount?></td>
              <td style="text-align: right"><?php echo number_format($row->amount * $row->realprice, 2, '.', ' ')?></td>
            </tr>
          <?php $sum += $row->amount * $row->realprice; } ?>
          <tr>
            <th colspan="4" style="text-align: right"><?php echo TOTAL_SUM?>:</th>
            <th><?php echo number_format($sum, 2, '.', ' ')?></th>
          </tr>
        </tbody>
      </table>
    <?php } ?>
    <?php if (!$forUser) { ?>
        <p><a href="http://<?php echo htmlspecialchars($_SERVER['HTTP_HOST'] . '/admin/?p=cms&m=shop&sub=orders&action=view&id=' . $Item->id)?>"><?php echo VIEW?></a></p>
        <p>
          <small>
            <?php echo IP_ADDRESS?>: <?php echo htmlspecialchars($Item->ip)?><br />
            <?php echo USER_AGENT?>: <?php echo htmlspecialchars($Item->user_agent)?><br />
            <?php echo PAGE?>: 
            <?php if ($Item->page->parents) { ?>
                <?php foreach ($Item->page->parents as $row) { ?>
                    <a href="<?php echo htmlspecialchars($Item->domain . $row->url)?>"><?php echo htmlspecialchars($row->name)?></a> / 
                <?php } ?>
            <?php } ?>
            <a href="<?php echo htmlspecialchars($Item->domain . $Item->page->url)?>"><?php echo htmlspecialchars($Item->page->name)?></a>
            <br />
            <?php echo CART_TYPE?>: 
            <a href="<?php echo htmlspecialchars($Item->domain . '/admin/?p=cms&m=shop&sub=orders&id=' . $Item->parent->id)?>"><?php echo htmlspecialchars($Item->parent->name)?></a>
          </small>
        </p>
    <?php } ?>
<?php } ?>