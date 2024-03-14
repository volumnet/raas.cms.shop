<?php
/**
 * Команда проверки оплаты Сбербанка
 */
namespace RAAS\CMS\Shop;

use RAAS\Application;
use RAAS\Command;
use RAAS\CMS\Block;
use RAAS\CMS\Page;
use RAAS\CMS\PageRecursiveCache;

/**
 * Команда проверки оплаты Сбербанка
 */
class SberbankCheckPaymentCommand extends Command
{
    /**
     * Выполнение команды
     * @param string|int $expiration Проверять заказы не старше, секунд
     */
    public function process($expiration = 86400)
    {
        $sqlQuery = "SELECT *
                       FROM " . Order::_tablename()
                  . " WHERE payment_id
                        AND payment_interface_id
                        AND payment_url LIKE '%securecardpayment%'
                        AND NOT paid
                        AND post_date >= NOW() - INTERVAL ? SECOND";
        $sqlBind = [(int)$expiration];
        $sqlResult = Order::getSQLSet([$sqlQuery, $sqlBind]);

        foreach ($sqlResult as $order) {
            // Найдем блок корзины
            $sqlQuery = "SELECT id FROM " . Block_Cart::_tablename2() . " WHERE epay_interface_id = ? AND cart_type = ?";
            $blockId = Block_Cart::_SQL()->getvalue([$sqlQuery, (int)$order->payment_interface_id, (int)$order->pid]);
            $block = null;
            $sber = null;
            $page = null;
            if ($blockId) {
                $block = Block::spawn($blockId);
                if ($block instanceof Block_Cart) {
                    $sber = new SberbankInterface($block);
                }
            }
            if (!$sber) {
                $this->controller->doLog('Невозможно найти блок корзины для заказа #' . $order->id);
                continue;
            }
            if (!$order->page_id || !(PageRecursiveCache::i()->cache[(string)$order->page_id] ?? null)) {
                $this->controller->doLog('Невозможно найти страницу корзины для заказа #' . $order->id);
                continue;
            }
            $page = new Page(PageRecursiveCache::i()->cache[(string)$order->page_id]);

            $orderIsPaid = $sber->getOrderIsPaid($order, $block, $page);

            if ($orderIsPaid) {
                $history = new Order_History([
                    'uid' => 0,
                    'order_id' => (int)$order->id,
                    'status_id' => (int)$order->status_id,
                    'paid' => 1,
                    'post_date' => date('Y-m-d H:i:s'),
                    'description' => 'Оплачено через Сбербанк (проверка по времени)'
                        .  ' (ID# заказа в системе банка: ' .  $order->payment_id . ')'
                ]);
                $history->commit();

                $order->paid = 1;
                $order->commit();
            }

            $logMessage = 'Заказ #' . $order->id . ' — ' . ($orderIsPaid ? '' : 'НЕ ') . 'ОПЛАЧЕН';

            $this->controller->doLog($logMessage);
        }
    }
}
