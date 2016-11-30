<?php
namespace RAAS\CMS\Shop;

function formatPrice($price)
{
    $remainder = (float)$price - (float)(int)$price;
    return str_replace(' ', '&nbsp;', number_format((float)$price, ($remainder > 0) ? 2 : 0, ',', ' ' ));
}

$getField = function($row) {
    $arr = array();
    $val = $row->doRich();
    switch ($row->datatype) {
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
            $arr[$key] .= '<a href="/' . $val->fileURL . '">' . htmlspecialchars($val->name) . '</a>';
            break;
        case 'image':
            $arr[$key] .= '<a href="/' . $val->fileURL . '"><img src="/' . $val->tnURL. '" alt="' . htmlspecialchars($val->name) . '" title="' . htmlspecialchars($val->name) . '" /></a>';
            break;
        case 'htmlarea':
            $arr[$key] = '<div>' . $val . '</div>';
            break;
        default:
            if (!$row->multiple && ($row->datatype == 'checkbox')) {
                $arr[$key] = $val ? _YES : _NO;
            } else {
                $arr[$key] = nl2br(htmlspecialchars($val));
            }
            break;
    }
    return implode(', ', $arr);
};

?>
<div class="my-orders">
  <?php if ($Item) { ?>
      <?php if (!$Item->status_id && !$Item->paid && !$Item->vis) { ?>
          <p class="text-right">
            <a href="#" data-id="<?php echo (int)$Item->id?>" data-toggle="modal" data-target="#confirmDeleteOrderModal"><span class="fa fa-close"></span> Удалить заказ</a>
          </p>
      <?php } ?>
      <div class="form-horizontal">
        <div data-role="feedback-form">
          <div class="form-group">
            <label class="control-label col-sm-3 col-md-2" style="padding-top: 0"><?php echo STATUS?>:</label>
            <div class="col-sm-9 col-md-4"><?php echo $Item->paid ? PAYMENT_PAID : PAYMENT_NOT_PAID?></div>
          </div>
          <?php
          foreach ($Item->fields as $row) {
              if ($val = $getField($row)) {
                  ?>
                  <div class="form-group">
                    <label class="control-label col-sm-3 col-md-2" style="padding-top: 0"><?php echo htmlspecialchars($row->name)?>:</label>
                    <div class="col-sm-9 col-md-4"><?php echo $val?></div>
                  </div>
                  <?php
              }
          }
          ?>
        </div>
      </div>
      <p>&nbsp;</p>
      <table class="table table-striped cart-table" data-role="cart-table">
        <tbody>
          <?php $sum = $am = 0; foreach ($Item->items as $row) {?>
            <tr data-role="cart-item">
              <td class="cart-table__image-col">
                <?php if ($row->visImages) { ?>
                    <a <?php echo $row->url ? 'href="' . htmlspecialchars($row->url) . '"' : ''?>>
                      <img src="/<?php echo htmlspecialchars(addslashes($row->visImages[0]->tnURL))?>" alt="<?php echo htmlspecialchars($row->visImages[0]->name ?: $row->name)?>" /></a>
                <?php } ?>
              </td>
              <td class="cart-table__name-col">
                <h3><a <?php echo $row->url ? 'href="' . htmlspecialchars($row->url) . '"' : ''?>><?php echo htmlspecialchars($row->name)?></a></h3>
                <?php if ($Cart->cartType->no_amount) { ?>
                    <input type="hidden" name="amount[<?php echo htmlspecialchars((int)$row->id . '_' . $row->meta)?>]" value="<?php echo (int)$row->amount?>" />
                <?php } else { ?>
                    <p><?php echo AMOUNT . ': ' . (int)$row->amount?></p>
                    <?php
                }
                foreach ($row->fields as $f) {
                    if ($val = array_filter(array_map(array($f, 'doRich'), $f->getValues(true)))) {
                        ?>
                        <p>
                          <?php echo htmlspecialchars($f->name)?>:
                          <?php
                          if (in_array($key, array('metal'))) {
                              echo htmlspecialchars(mb_strtolower(implode(', ', $val)));
                          } else {
                              echo htmlspecialchars(implode(', ', $val));
                          }
                          ?>
                        </p>
                        <?php
                    }
                }
                ?>
              </td>
              <td class="cart-table__sum-col"><span data-role="sum"><?php echo formatPrice($row->amount * $row->realprice)?></span> <span class="fa fa-rub"></span></td>
            </tr>
          <?php $sum += $row->amount * $row->realprice; $am += $row->amount; } ?>
          <tr>
            <th class="cart-table__image-col"></th>
            <th class="cart-table__name-col"><?php echo TOTAL_SUM?>:</th>
            <th class="cart-table__sum-col"><span data-role="total-sum"><?php echo formatPrice($sum)?></span>&nbsp;<span class="fa fa-rub"></span></th>
          </tr>
        </tbody>
      </table>

  <?php } elseif ($Set) { ?>
      <table class="table table-striped my-orders__table">
        <tbody>
          <?php foreach ($Set as $row) { ?>
              <tr>
                <td><a href="?id=<?php echo (int)$row->id?>"><?php echo Lang::i()->_('ORDER_NUMBER', $Page)?> <?php echo (int)$row->id?></td>
                <td><a href="?id=<?php echo (int)$row->id?>"><?php echo date(Lang::i()->_('DATETIME_FORMAT', $Page), strtotime($row->post_date))?></a></td>
                <td>
                  <a href="?id=<?php echo (int)$row->id?>">
                  <?php
                  $temp = array();
                  foreach ($row->items as $row2) {
                      $arr = $row2->name;
                      if ($row2->amount > 1) {
                          $arr .= ' - ' . (int)$row2->amount . 'x' . formatPrice($row2->realprice) . '<span class="fa fa-rub"></span> = ' . formatPrice($row2->amount * $row2->realprice) . ' <span class="fa fa-rub"></span>';
                      } else {
                          $arr .= ' = ' . formatPrice($row2->realprice) . ' <span class="fa fa-rub"></span>';
                      }
                      $temp[] = $arr;
                  }
                  echo implode('<br />', $temp);
                  ?>
                  </a>
                </td>
                <td>
                  <?php
                  $temp = array();
                  if ($row->status->id) {
                      $temp[] = $row->status->name;
                  } else {
                      // $temp[] = ORDER_STATUS_NEW;
                  }
                  if ($row->paid) {
                      $temp[] = PAYMENT_PAID;
                  } else {
                      $temp[] = PAYMENT_NOT_PAID;
                  }
                  echo implode('<br />', $temp);
                  ?>
                </td>
                <td class="my-orders__sum">
                  <a href="?id=<?php echo (int)$row->id?>"><?php echo formatPrice($row->sum)?> <span class="fa fa-rub"></span></a>
                </td>
                <td>
                  <?php if (!$row->status_id && !$row->paid && !$row->vis) { ?>
                      <a href="#" data-id="<?php echo (int)$row->id?>" data-back="true" data-toggle="modal" data-target="#confirmDeleteOrderModal"><span class="fa fa-close"></span></a>
                  <?php } ?>
                </td>
              </tr>
          <?php } ?>
        </tbody>
      </table>
  <?php } else { ?>
    <p><?php echo Lang::i()->_('YOU_HAVE_NO_ORDERS_YET', $Page)?></p>
  <?php } ?>
</div>

<div class="modal fade" id="confirmDeleteOrderModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header" style="border-bottom: none">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?php echo Lang::i()->_('ARE_YOU_SURE_TO_DELETE_THIS_ORDER', $Page)?></h4>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo CANCEL?></button>
        <a href="#" class="btn btn-primary"><?php echo DELETE?></a>
      </div>
    </div>
  </div>
</div>

<script>
jQuery(document).ready(function($) {
    var $confirmDeleteOrderModal = $('#confirmDeleteOrderModal');
    $('body').append('confirmDeleteOrderModal');
    $('a[data-target="#confirmDeleteOrderModal"][data-toggle="modal"]').on('click', function() {
        $('.modal-footer a').attr('href', '?action=delete&id=' + parseInt($(this).attr('data-id')) + ($(this).attr('data-back') ? '&back=1' : ''));
    });
});
</script>
