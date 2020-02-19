<?php
/**
 * Виджет просмотра заказа
 * @param Order $order Заказ
 * @param bool $orderDataFirst Отображать сначала данные полей
 * @param bool $showPaymentStatus Отображать статус оплаты
 *                                (для сервиса "Мои заказы")
 */
namespace RAAS\CMS\Shop;

use SOME\Text;

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
<div class="cart">
  <?php
  ob_start();
  if ($order->items) { ?>
      <div class="cart__list">
        <div class="cart-list">
          <div class="cart-list__header">
            <div class="cart-list__item cart-list__item_header">
              <div class="cart-item cart-item_header">
                <div class="cart-item__image">
                  <?php echo IMAGE?>
                </div>
                <div class="cart-item__title">
                  <?php echo NAME?>
                </div>
                <div class="cart-item__price">
                  <?php echo PRICE?>
                </div>
                <div class="cart-item__amount-block">
                  <?php echo AMOUNT?>
                </div>
                <div class="cart-item__sum">
                  <?php echo SUM?>
                </div>
              </div>
            </div>
          </div>
          <div class="cart-list__list">
            <?php foreach ($order->items as $item) { ?>
                <div class="cart-list__item">
                  <div class="cart-item">
                    <div class="cart-item__image">
                      <?php if ($item->visImages) { ?>
                          <a <?php echo $item->url ? 'href="' . htmlspecialchars($item->url) . '" target="_blank"' : ''?>>
                            <img src="/<?php echo htmlspecialchars(addslashes($item->visImages[0]->tnURL))?>" alt="<?php echo htmlspecialchars($item->visImages[0]->name ?: $item->name)?>" target="_blank" /></a>
                      <?php } ?>
                    </div>
                    <div class="cart-item__title">
                      <a <?php echo $item->url ? 'href="' . htmlspecialchars($item->url) . '" target="_blank"' : ''?>>
                        <?php echo htmlspecialchars($item->name)?>
                      </a>
                    </div>
                    <div class="cart-item__price">
                      <?php echo htmlspecialchars(Text::formatPrice($item->realprice))?> ₽
                    </div>
                    <div class="cart-item__amount-block">
                      <?php echo (float)$item->amount?>
                    </div>
                    <div class="cart-item__sum">
                      <?php echo htmlspecialchars(Text::formatPrice($item->realprice * $item->amount))?> ₽
                    </div>
                  </div>
                </div>
            <?php } ?>
          </div>
          <div class="cart-list__summary">
            <div class="cart-list__item cart-list__item_summary">
              <div class="cart-item cart-item_summary">
                <div class="cart-item__title">
                  <?php echo TOTAL_SUM?>:
                </div>
                <div class="cart-item__amount-block">
                  <?php echo htmlspecialchars($order->count)?>
                </div>
                <div class="cart-item__sum">
                   <?php echo htmlspecialchars(Text::formatPrice($order->sum))?> ₽
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
  <?php }
  $itemsData = ob_get_clean();
  ob_start();
  ?>
  <div class="cart__form">
    <div class="cart-form form-horizontal">
      <?php if ($showPaymentStatus) { ?>
          <div class="form-group">
            <label class="control-label col-sm-3 col-md-2">
              <?php echo STATUS?>:
            </label>
            <div class="col-sm-9 col-md-4">
              <?php echo $Item->paid ? PAYMENT_PAID : PAYMENT_NOT_PAID?>
            </div>
          </div>
      <?php } ?>
      <?php foreach ($order->fields as $fieldURN => $field) { ?>
          <div class="form-group">
            <label class="control-label col-sm-3 col-md-2">
              <?php echo htmlspecialchars($field->name)?>:
            </label>
            <div class="col-sm-9 col-md-4">
              <?php echo $getField($field)?>
            </div>
          </div>
      <?php } ?>
    </div>
  </div>
  <?php
  $fieldsData = ob_get_clean();
  if ($orderDataFirst) {
      echo $fieldsData . $itemsData;
  } else {
      echo $itemsData . $fieldsData;
  }
  ?>
</div>
