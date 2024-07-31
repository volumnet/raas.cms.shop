<?php
/**
 * Команда проверки оплаты
 */
declare(strict_types=1);

namespace RAAS\CMS\Shop;

use Exception;
use RAAS\Application;
use RAAS\Command;
use RAAS\CMS\Block;
use RAAS\CMS\Page;
use RAAS\CMS\PageRecursiveCache;

/**
 * Команда проверки оплаты
 */
class EPayCheckPaymentCommand extends Command
{
    /**
     * Строка поиска в заказах по payment_url (операция LIKE)
     */
    const PAYMENT_URL_TO_FIND = '';

    /**
     * Выполнение команды
     * @param string|int $expiration Проверять заказы не старше, секунд
     */
    public function process($expiration = 86400)
    {
        $sqlQuery = "SELECT *
                       FROM " . Order::_tablename()
                  . " WHERE payment_id != ''
                        AND NOT paid
                        AND post_date >= NOW() - INTERVAL ? SECOND";
        $sqlBind = [(int)$expiration];
        if (static::PAYMENT_URL_TO_FIND) {
            $sqlQuery .= " AND (payment_interface_id OR (payment_interface_classname != ''))
                           AND payment_url LIKE ? ";
            $sqlBind[] = static::PAYMENT_URL_TO_FIND;
        } else {
            $sqlQuery .= "AND payment_interface_classname != ''";
        }
        $sqlResult = Order::getSQLSet([$sqlQuery, $sqlBind]);

        foreach ($sqlResult as $order) {
            // Найдем блок корзины
            $sqlQuery = $sqlQuery = "SELECT id FROM " . Block_Cart::_tablename2() . " WHERE cart_type = ?";
            $sqlBind = [(int)$order->pid];
            if ($order->payment_interface_classname) {
                $sqlQuery .= " AND epay_interface_classname = ?";
                $sqlBind[] = (string)$order->payment_interface_classname;
            } else {
                $sqlQuery .= " AND epay_interface_id = ?";
                $sqlBind[] = (int)$order->payment_interface_id;
            }
            $blockId = Block_Cart::_SQL()->getvalue([$sqlQuery, $sqlBind]);
            $block = null;
            $interface = null;
            $page = null;
            if ($blockId) {
                $block = Block::spawn($blockId);
                if ($block instanceof Block_Cart) {
                    $interface = $this->getInterface($block);
                }
            }
            if (!$interface) {
                $this->controller->doLog('Невозможно найти блок корзины для заказа #' . $order->id);
                continue;
            }
            if (!$order->page_id || !(PageRecursiveCache::i()->cache[(string)$order->page_id] ?? null)) {
                $this->controller->doLog('Невозможно найти страницу корзины для заказа #' . $order->id);
                continue;
            }
            $page = new Page(PageRecursiveCache::i()->cache[(string)$order->page_id]);

            try {
                $orderIsPaid = $interface->getOrderIsPaid($order, $block, $page);
            } catch (Exception $e) {
                $this->controller->doLog($e->getMessage() . ' для заказа #' . $order->id);
                continue;
            }

            if ($orderIsPaid) {
                $interfaceClassname = get_class($interface);
                $history = new Order_History([
                    'uid' => 0,
                    'order_id' => (int)$order->id,
                    'status_id' => (int)$order->status_id,
                    'paid' => 1,
                    'post_date' => date('Y-m-d H:i:s'),
                    'description' => 'Оплачено через ' . $interfaceClassname::BANK_NAME . ' (проверка по времени)'
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


    /**
     * Получает платежный интерфейс для блока
     * @return EPayInterface|null
     */
    public function getInterface(Block_Cart $block)
    {
        if ($epayInterfaceClassname = $block->epay_interface_classname) {
            return new $epayInterfaceClassname($block);
        }
        return null;
    }
}
