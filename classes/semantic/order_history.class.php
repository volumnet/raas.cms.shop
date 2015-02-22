<?php
namespace RAAS\CMS\Shop;

class Order_History extends \SOME\SOME
{
    protected static $tablename = 'cms_shop_orders_history';
    protected static $defaultOrderBy = "post_date DESC";
    protected static $references = array(
        'user' => array('FK' => 'uid', 'classname' => 'RAAS\\User', 'cascade' => false),
        'parent' => array('FK' => 'order_id', 'classname' => 'RAAS\\CMS\\Shop\\Order', 'cascade' => true),
        'status' => array('FK' => 'status_id', 'classname' => 'RAAS\\CMS\\Shop\\Order_Status', 'cascade' => true),
    );
}