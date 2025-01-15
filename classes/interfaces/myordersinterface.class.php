<?php
/**
 * Интерфейс сервиса "Мои заказы"
 */
declare(strict_types=1);

namespace RAAS\CMS\Shop;

use SOME\HTTP;
use RAAS\Redirector;
use RAAS\Controller_Frontend as RAASControllerFrontend;
use RAAS\View_Web as RAASViewWeb;
use RAAS\CMS\BlockInterface;
use RAAS\CMS\Block_PHP;
use RAAS\CMS\Page;
use RAAS\CMS\User;

/**
 * Класс интерфейса сервиса "Мои заказы"
 */
class MyOrdersInterface extends BlockInterface
{
    /**
     * Конструктор класса
     * @param ?Block_PHP $block Блок, для которого применяется
     *                               интерфейс
     * @param ?Page $page Страница, для которой применяется интерфейс
     * @param array $get Поля $_GET параметров
     * @param array $post Поля $_POST параметров
     * @param array $cookie Поля $_COOKIE параметров
     * @param array $session Поля $_SESSION параметров
     * @param array $server Поля $_SERVER параметров
     * @param array $files Поля $_FILES параметров
     */
    public function __construct(
        ?Block_PHP $block = null,
        ?Page $page = null,
        array $get = [],
        array $post = [],
        array $cookie = [],
        array $session = [],
        array $server = [],
        array $files = []
    ) {
        parent::__construct(
            $block,
            $page,
            $get,
            $post,
            $cookie,
            $session,
            $server,
            $files
        );
    }


    public function process(): array
    {
        $user = $this->getUser();
        $order = $this->getOrder(
            isset($this->get['id']) ?
            (int)$this->get['id'] :
            0,
            $user
        );
        $result = [];

        $isAJAX = false;
        $ajaxValue = ($this->post['AJAX'] ?? $this->get['AJAX'] ?? null);
        if ($ajaxValue && $this->block->id && ($ajaxValue == $this->block->id)) {
            $isAJAX = true;
        }
        if ($order) {
            switch ($this->get['action'] ?? null) {
                case 'delete':
                    $redirectURL = '';
                    if (!$isAJAX) {
                        if ($this->get['back'] ?? null) {
                            $redirectURL = 'history:back';
                        } else {
                            $redirectURL = HTTP::queryString(
                                'id=&action=',
                                true,
                                ($this->server['REQUEST_URI'] ?? '')
                            );
                        }
                    }
                    $result['success'] = $this->deleteOrder($order, $redirectURL);
                    if ($isAJAX) {
                        $result['Set'] = $this->getOrdersList($user);
                    }
                    break;
                default:
                    $this->processOrder($order, $this->page);
                    $result['Item'] = $order;
                    break;
            }
        } else {
            $result['Set'] = $this->getOrdersList($user);
        }
        return $result;
    }


    /**
     * Получает текущего пользователя
     * @param bool $debug Режим отладки
     * @return User|null Возвращает пользователя, либо null в режиме отладки,
     *                   если пользователь не авторизован
     */
    public function getUser(bool $debug = false)
    {
        $user = RAASControllerFrontend::i()->user;
        if (!$user->id) {
            if ($debug) {
                return null;
            // @codeCoverageIgnoreStart
            } else {
                new Redirector('/');
            }
            // @codeCoverageIgnoreEnd
        }
        return $user;
    }


    /**
     * Получает заказ, если он соответствует заданному пользователю
     * @param int $id ID# заказа
     * @param User $user Пользователь
     * @return Order|null Возвращает заказ, либо null, если заказ
     *                    либо не существует, либо не принадлежит заданному
     *                    пользователю
     */
    public function getOrder($id, User $user)
    {
        $order = new Order((int)$id);
        if ($order->id && $user->id && ($order->uid == $user->id)) {
            return $order;
        }
        return null;
    }


    /**
     * Удаляет заказ и переходит на заданную страницу
     * @param Order $order Заказ для удаления
     * @param string $redirectURL URL для перехода
     *                            (пустая строка, если переход не нужен)
     * @return bool true, если заказ удален, false если нет -
     *              только когда нет перехода
     */
    public function deleteOrder(Order $order, $redirectURL = '')
    {
        $ok = false;
        if (!$order->status_id && !$order->paid && !$order->vis) {
            Order::delete($order);
            $ok = true;
        }
        // @codeCoverageIgnoreStart
        if ($redirectURL) {
            new Redirector($redirectURL);
        }
        // @codeCoverageIgnoreEnd
        return $ok;
    }


    /**
     * Обрабатывает страницу для отображения одного заказа
     * @param Order $order Заказ
     * @param Page $page Страница
     */
    public function processOrder(Order $order, Page $page)
    {
        $page->oldName = $page->name;
        $page->breadcrumbs_name = $page->name;
        $page->Item = $order;
        $page->name = sprintf(
            View_Web::i()->_('ORDER_NUM_FROM'),
            $order->id,
            date(
                RAASViewWeb::i()->_('DATETIMEFORMAT'),
                strtotime($order->post_date)
            )
        );
    }


    /**
     * Получает список заказов пользователя
     * @param User $user Пользователь, для которого получаем заказы
     * @return Order[]
     */
    public function getOrdersList(User $user)
    {
        if (!$user->id) {
            return [];
        }
        $set = Order::getSet([
            'where' => "uid = " . (int)$user->id,
            'orderBy' => 'id DESC'
        ]);
        return $set;
    }
}
