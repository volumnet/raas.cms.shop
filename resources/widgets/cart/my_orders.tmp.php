<?php
namespace RAAS\CMS\Shop;

use SOME\Text;
use RAAS\CMS\Snippet;

?>
<div class="my-orders">
  <?php if ($Item) { ?>
      <div class="my-orders__article">
        <div class="my-orders-article">
          <?php if (!$Item->status_id && !$Item->paid && !$Item->vis) { ?>
              <div class="my-orders-article__actions">
                <a href="#" class="my-orders-article__delete" data-role="delete-item" data-id="<?php echo (int)$Item->id?>">
                  <?php echo DELETE_ORDER?>
                </a>
              </div>
          <?php }
          Snippet::importByURN('order')->process([
              'order' => $Item,
              'orderDataFirst' => true,
              'showPaymentStatus' => true
          ]);
          ?>
        </div>
      </div>
  <?php } elseif ($Set) { ?>
      <div class="my-orders__list">
        <div class="my-orders-list">
          <div class="my-orders-list__header">
            <div class="my-orders-list__item my-orders-list__item_header">
              <div class="my-orders-item my-orders-item_header">
                <div class="my-orders-item__num">
                  #
                </div>
                <div class="my-orders-item__date">
                  <?php echo DATE?>
                </div>
                <div class="my-orders-item__title">
                  <?php echo GOODS?>
                </div>
                <div class="my-orders-item__status">
                  <?php echo STATUS?>
                </div>
                <div class="my-orders-item__sum">
                  <?php echo SUM?>
                </div>
                <div class="my-orders-item__actions"></div>
              </div>
            </div>
          </div>
          <div class="my-orders-list__list">
            <?php foreach ($Set as $order) { ?>
                <div class="my-orders-list__item">
                  <div class="my-orders-item">
                    <a href="?id=<?php echo (int)$order->id?>" class="my-orders-item__num">
                      <?php echo (int)$order->id?>
                    </a>
                    <a href="?id=<?php echo (int)$order->id?>" class="my-orders-item__date">
                      <?php echo date(DATEFORMAT, strtotime($order->post_date))?>
                    </a>
                    <div class="my-orders-item__title">
                      <div class="my-orders-item__title-inner">
                        <?php
                        $temp = [];
                        foreach ($order->items as $item) {
                            $arr = $item->name;
                            $itemPriceText = Text::formatPrice($item->realprice);
                            if ($item->amount > 1) {
                                $arr .= ' – ' . (int)$item->amount . ' x '
                                     .  $itemPriceText .  ' ₽ = '
                                     .  Text::formatPrice($item->amount * $item->realprice)
                                     .  ' ₽';
                            } else {
                                $arr .= ' = ' . $itemPriceText . ' ₽';
                            }
                            $temp[] = $arr;
                        }
                        $text = implode("\n", $temp);
                        $text = Text::cuttext($text, 64, '...');
                        echo nl2br(htmlspecialchars($text));
                        ?>
                      </div>
                      <div class="my-orders-item__more">
                        <a href="?id=<?php echo (int)$order->id?>">
                          <?php echo SHOW_MORE?>
                        </a>
                      </div>
                    </div>
                    <a href="?id=<?php echo (int)$order->id?>" class="my-orders-item__status">
                      <?php if ($order->status->id) { ?>
                          <div class="my-orders-item__self-status">
                            <?php echo htmlspecialchars($order->status->name)?>
                          </div>
                      <?php } else {  ?>
                          <div class="my-orders-item__self-status">
                            <?php echo ORDER_STATUS_NEW?>
                          </div>
                      <?php  }
                      if ($order->paid) { ?>
                          <div class="my-orders-item__payment-status my-orders-item__payment-status_paid">
                            <?php echo PAYMENT_PAID?>
                          </div>
                      <?php } else { ?>
                          <div class="my-orders-item__payment-status my-orders-item__payment-status_not-paid">
                            <?php echo PAYMENT_NOT_PAID?>
                          </div>
                      <?php } ?>
                    </a>
                    <a href="?id=<?php echo (int)$order->id?>" class="my-orders-item__sum">
                      <?php echo Text::formatPrice($order->sum)?> ₽
                    </a>
                    <div class="my-orders-item__actions">
                      <?php if (!$order->status_id && !$order->paid && !$order->vis) { ?>
                          <a href="#" class="my-orders-item__delete" data-role="delete-item" data-id="<?php echo (int)$order->id?>" data-back="true"></a>
                      <?php } ?>
                    </div>
                  </div>
                </div>
            <?php } ?>
          </div>
        </div>
      </div>
  <?php } else { ?>
    <div class="my-orders__empty"><?php echo YOU_HAVE_NO_ORDERS?></div>
  <?php } ?>
</div>

<script>
jQuery(document).ready(function($) {
    $('a[data-role="delete-item"]')
        .on('click', function() {
            var url = '?action=delete&id=' + parseInt($(this).attr('data-id'))
                    + ($(this).attr('data-back') ? '&back=1' : '');
            $.RAASConfirm('<?php echo ARE_YOU_SURE_TO_DELETE_ORDER?>')
                .then(function () {
                    window.location.href = url;
                });
            return false;
        });
});
</script>
