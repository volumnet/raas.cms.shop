<?php
/**
 * Команда привязки заказов к пользователям
 */
namespace RAAS\CMS\Shop;

use RAAS\Application;
use RAAS\Command;
use RAAS\Controller_Frontend;
use RAAS\CMS\Block;
use RAAS\CMS\Package;
use RAAS\CMS\Page;
use RAAS\CMS\User;
use RAAS\CMS\Users\Module as UsersModule;

/**
 * Команда привязки заказов к пользователям
 */
class BindOrdersToUsersCommand extends Command
{
    /**
     * Обработка команды
     * @param bool $real Реальное выполнение команды (тестовый режим, если false)
     * @param string $bindUserBy Список URN полей для привязки,
     *     разделенные запятыми
     * @param int $createNewBlockId ID# блока регистрации для создания
     *     новых пользователей (новые пользователи не создаются,
     *     если не установлен)
     */
    public function process($real = false, $bindUserBy = 'phone,email', $createNewBlockId = null)
    {
        $bindUserBy = explode(',', $bindUserBy);
        $bindUserBy = array_map('trim', $bindUserBy);
        $registerBlock = Block::spawn($createNewBlockId);
        if ($registerBlock->id && $registerBlock->pages[0]->lang) {
            $lang = $registerBlock->pages[0]->lang;
            Controller_Frontend::i()->exportLang(Application::i(), $lang);
            Controller_Frontend::i()->exportLang(Package::i(), $lang);
            Controller_Frontend::i()->exportLang(Module::i(), $lang);
            Controller_Frontend::i()->exportLang(UsersModule::i(), $lang);
        }
        $sqlQuery = "SELECT *
                       FROM " . Order::_tablename()
                  . " WHERE NOT uid
                   ORDER BY id";
        $sqlResult = Order::getSQLSet($sqlQuery);
        $cartInterface = new CartInterface();
        foreach ($sqlResult as $order) {
            $postData = [];
            foreach (['login', 'email'] as $fieldURN) {
                if ($val = trim($order->fieldURN)) {
                    $postData[$fieldURN] = $val;
                }
            }
            foreach ($order->fields as $fieldURN => $field) {
                $val = $field->getValues();
                if (is_array($val)) {
                    foreach ($val as $k => $v) {
                        if (is_scalar($v)) {
                            $postData[$fieldURN][$k] = trim($v);
                        } elseif ($v->id) {
                            $postData[$fieldURN][$k] = trim($v->id);
                        }
                    }
                } else {
                    if (is_scalar($val)) {
                        $postData[$fieldURN] = trim($val);
                    } elseif ($val->id) {
                        $postData[$fieldURN] = trim($val->id);
                    }
                }
            }
            $user = $cartInterface->findUser($postData, $bindUserBy);
            $logMessage = '';
            if ($user->id) {
                $logMessage = 'Заказ #' . (int)$order->id
                    . ' присвоен пользователю #' . (int)$user->id
                    . ' (' . $user->email . ')';
            } elseif ($registerBlock->id && trim($postData['email'])) {
                if ($real) {
                    $user = $cartInterface->createUser($registerBlock, new Page(), $postData);
                    $logMessage = 'Для заказа #' . (int)$order->id
                        . ' создан пользователь #' . (int)$user->id
                        . ' (' . $user->email . ')';
                } else {
                    $logMessage = 'Для заказа #' . (int)$order->id
                        . ' создан пользователь (' . $order->email . ')';
                }
            }
            if ($logMessage) {
                $this->controller->doLog($logMessage);
            }
            if ($real && $user->id) {
                Order::_SQL()->update(
                    Order::_tablename(),
                    "id = " . (int)$order->id,
                    ['uid' => (int)$user->id]
                );
            }
        }
    }
}
