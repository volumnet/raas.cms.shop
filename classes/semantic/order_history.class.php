<?php
/**
 * Запись истории заказа
 */
namespace RAAS\CMS\Shop;

use SOME\SOME;
use RAAS\User as RAASUser;

/**
 * Класс записи истории заказа
 * @property-read RAASUser $user Автор записи
 * @property-read Order $parent Родительский заказ
 * @property-read Order_Status $status Статус, на который сменили заказ данной записью
 */
class Order_History extends SOME
{
    protected static $tablename = 'cms_shop_orders_history';

    protected static $defaultOrderBy = "post_date DESC";

    protected static $references = [
        'user' => [
            'FK' => 'uid',
            'classname' => RAASUser::class,
            'cascade' => false
        ],
        'parent' => [
            'FK' => 'order_id',
            'classname' => Order::class,
            'cascade' => true
        ],
        'status' => [
            'FK' => 'status_id',
            'classname' => Order_Status::class,
            'cascade' => true
        ],
    ];
}
