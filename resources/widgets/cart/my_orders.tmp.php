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
            $arr[$key] = date('d.m.Y', strtotime($val));
            break;
        case 'datetime-local':
            $arr[$key] = date('d.m.Y H:i', strtotime($val));
            break;
        case 'color':
            $arr[$key] = '<span style="display: inline-block; height: 16px; width: 16px; background-color: ' . htmlspecialchars($val) . '"></span>';
            break;
        case 'email':
            $arr[$key] .= '<a href="mailto:' . htmlspecialchars($val) . '">' . htmlspecialchars($val) . '</a>';
            break;
        case 'url':
            $arr[$key] .= '<a href="' . (!preg_match('/^http(s)?:\\/\\//umi', trim($val)) ? 'http://' : '') . htmlspecialchars($val) . '">' . htmlspecialchars($val) . '</a>';
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
            <div class="col-sm-9 col-md-4"><?php echo $Item->paid ? 'Оплачен' : 'Не оплачен'?></div>
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
      <div class="cart">
        <div class="cart__inner">
          <table class="table table-striped cart-table" data-role="cart-table">
            <thead>
              <tr>
                <th class="cart-table__image-col"><?php echo IMAGE?></th>
                <th class="cart-table__article-col"><?php echo ARTICLE?></th>
                <th class="cart-table__name-col"><?php echo NAME?></th>
                <th class="cart-table__price-col"><?php echo PRICE?></th>
                <th class="cart-table__amount-col"><?php echo AMOUNT?></th>
                <th class="cart-table__sum-col"><?php echo SUM?></th>
              </tr>
            </thead>
            <tbody data-role="cart__body_main">
              <?php $sum = $am = 0; foreach ($Item->items as $row) {?>
                  <tr>
                    <td class="cart-table__image-col">
                      <?php if ($row->visImages) { ?>
                          <a <?php echo $row->url ? 'href="' . htmlspecialchars($row->url) . '"' : ''?>>
                            <img src="/<?php echo htmlspecialchars(addslashes($row->visImages[0]->smallURL))?>" style="max-width: 48px" alt="<?php echo htmlspecialchars($row->visImages[0]->name ?: $row->name)?>" /></a>
                      <?php } ?>
                    </td>
                    <td class="cart-table__article-col"><a <?php echo $row->url ? 'href="' . htmlspecialchars($row->url) . '"' : ''?>><?php echo htmlspecialchars($row->article)?></a></td>
                    <td class="cart-table__name-col"><a <?php echo $row->url ? 'href="' . htmlspecialchars($row->url) . '"' : ''?>><?php echo htmlspecialchars($row->name)?></a></td>
                    <td data-role="price" class="cart-table__price-col">
                      <?php echo formatPrice($row->realprice)?> <span class="fa fa-rub"></span>
                    </td>
                    <td class="cart-table__amount-col"><?php echo (int)$row->amount?></td>
                    <td class="cart-table__sum-col"><?php echo formatPrice($row->amount * $row->realprice)?> <span class="fa fa-rub"></span></td>
                  </tr>
              <?php $sum += $row->amount * $row->realprice; $am += $row->amount; } ?>
            </tbody>
            <tbody>
              <tr>
                <th colspan="<?php echo !$Cart->cartType->no_amount ? '4' : '3'?>"><?php echo TOTAL_SUM?>:</th>
                <th class="cart-table__amount-col"><span data-role="total-amount"><?php echo (int)$am; ?></span></td>
                <th class="cart-table__sum-col"><span data-role="total-sum"><?php echo formatPrice($sum)?></span>&nbsp;<span class="fa fa-rub"></span></th>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
  <?php } elseif ($Set) { ?>
      <div class="cart">
        <div class="cart__inner">
          <table class="table table-striped cart-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Дата</th>
                <th>Товары</th>
                <th>Статус</th>
                <th>Сумма</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($Set as $row) { ?>
                  <tr>
                    <td><a href="?id=<?php echo (int)$row->id?>"><?php echo (int)$row->id?></a></td>
                    <td><a href="?id=<?php echo (int)$row->id?>"><?php echo date('d.m.Y', strtotime($row->post_date))?></a></td>
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
                          $temp[] = 'Оплачен';
                      } else {
                          $temp[] = 'Не оплачен';
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
        </div>
      </div>
  <?php } else { ?>
    <p>У вас пока нет ни одного заказа</p>
  <?php } ?>
</div>

<div class="modal fade" id="confirmDeleteOrderModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header" style="border-bottom: none">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Вы действительно хотите удалить заказ?</h4>
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
