<?php
/**
 * Стандартное уведомление о заказе
 * @param bool $SMS Уведомление отправляется по SMS
 * @param Order $Item Уведомление формы обратной связи
 * @param bool $forUser Отправка сообщения для пользователя
 *     (если false то для администратора)
 */
namespace RAAS\CMS\Shop;

use SOME\Text;
use RAAS\Controller_Frontend as ControllerFrontend;
use RAAS\CMS\DiagTimer;
use RAAS\CMS\Form_Field;
use RAAS\CMS\NotificationFieldRenderer;

$cf = ControllerFrontend::i();
$adminUrl = $cf->schemeHost . '/admin/?p=cms';

$page = $Item->page;
$cartType = $Item->parent;

if ($SMS) {
    if ($forUser) {
        echo sprintf(ORDER_SUCCESSFULLY_SENT, $Item->id);
    } else {
        echo date(DATETIMEFORMAT) . ' ' .
            sprintf(ORDER_STANDARD_HEADER_USER, $Item->id, $cf->idnHost) . "\n";
        foreach ($Item->fields as $field) {
            $renderer = NotificationFieldRenderer::spawn($field);
            echo $renderer->render(['admin' => !$forUser, 'sms' => true]);
        }
    }
} else { ?>
    <div>
      <?php echo ORDER_ID . ': ' . (int)$Item->id?>
    </div>
    <div>
      <?php
      if ($forUser) {
          $fields = $Item->visFields;
      } else {
          $fields = $Item->fields;
      }
      foreach ($fields as $field) {
          $renderer = NotificationFieldRenderer::spawn($field);
          echo $renderer->render(['admin' => !$forUser, 'sms' => false]);
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
            foreach ($Item->items as $item) {
                $url = '';
                if (($item->id && !$forUser) || $item->url) {
                    if ($forUser) {
                        $url = $cf->schemeHost . $item->url;
                    } elseif ($item->id) {
                        $url = $cf->schemeHost
                            . '/admin/?p=cms&sub=main&action=edit_material&id='
                            . $item->id;
                        if ($item->cache_url_parent_id) {
                            // 2023-04-12, AVS: переписал на cache_url_parent_id (было через affectedPages) -
                            // при большом количестве товаров дико тормозило (до 3 секунд на позицию)
                            $url .= '&pid=' . (int)$item->cache_url_parent_id;
                        }
                    }
                }
                $itemSum = $item->amount * $item->realprice;
                $sum += $itemSum;
                ?>
                <tr>
                  <td>
                    <?php if ($url) { ?>
                        <a href="<?php echo htmlspecialchars($url)?>">
                          <?php echo htmlspecialchars($item->name)?>
                        </a>
                    <?php } else {
                        echo htmlspecialchars($item->name);
                    } ?>
                  </td>
                  <td>
                    <?php echo htmlspecialchars($item->__get('meta'))?>&nbsp;
                  </td>
                  <td style="text-align: right; white-space: nowrap;">
                    <?php echo Text::formatPrice($item->realprice)?>
                  </td>
                  <td>
                    <?php echo (int)$item->amount?>
                  </td>
                  <td style="text-align: right; white-space: nowrap;">
                    <?php echo Text::formatPrice($itemSum)?>
                  </td>
                </tr>
            <?php } ?>
            <tr>
              <th colspan="4" style="text-align: right">
                <?php echo TOTAL_SUM?>:
              </th>
              <th style="text-align: right; white-space: nowrap;">
                <?php echo Text::formatPrice($sum)?>
              </th>
            </tr>
          </tbody>
        </table>
    <?php }
    if (!$forUser) { ?>
        <p>
          <a href="<?php echo htmlspecialchars($adminUrl . '&m=shop&sub=orders&action=view&id=' . (int)$Item->id)?>">
            <?php echo VIEW?>
          </a>
        </p>
        <p>
          <small>
            <?php
            echo IP_ADDRESS . ': ' .
                htmlspecialchars((string)$Item->ip) . '<br />' .
                USER_AGENT . ': ' .
                htmlspecialchars((string)$Item->user_agent) . '<br />' .
                PAGE . ': ';
            if ($page->parents) {
                foreach ($page->parents as $row) { ?>
                    <a href="<?php echo htmlspecialchars($adminUrl . '&id=' . (int)$row->id)?>">
                      <?php echo htmlspecialchars($row->name)?>
                    </a> /
                <?php }
            } ?>
            <a href="<?php echo htmlspecialchars($adminUrl . '&id=' . (int)$page->id)?>">
              <?php echo htmlspecialchars($page->name)?>
            </a>
            <br />
            <?php echo CART_TYPE?>:
            <a href="<?php echo htmlspecialchars($adminUrl . '&m=shop&sub=orders&id=' . (int)$cartType->id)?>">
              <?php echo htmlspecialchars($cartType->name)?>
            </a>
          </small>
        </p>
    <?php }
}
