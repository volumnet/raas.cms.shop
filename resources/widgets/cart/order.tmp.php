<?php
/**
 * Виджет просмотра заказа
 * @param Order $order Заказ
 */
namespace RAAS\CMS\Shop;

use SOME\Text;

?>
<div class="cart">
  <?php if ($order->items) { ?>
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
  <?php } ?>
  <div class="cart__form">
    <div class="cart-form form-horizontal">
      <?php foreach ($order->fields as $fieldURN => $field) { ?>
          <div class="form-group">
            <label class="control-label col-sm-3 col-md-2">
              <?php echo htmlspecialchars($field->name)?>
            </label>
            <div class="col-sm-9 col-md-4">
              <?php
              if ($field->datatype == 'checkbox' && !$field->multiple) {
                  echo ($field->getValue() ? 'да' : 'нет');
              } else {
                  echo htmlspecialchars($order->fields[$fieldURN]->doRich());
              }
              ?>
            </div>
          </div>
      <?php } ?>
    </div>
  </div>
</div>
