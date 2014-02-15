<?php
namespace RAAS\CMS\Shop;

class Order_Status extends \SOME\SOME
{
    protected static $tablename = 'cms_shop_orders_statuses';
    protected static $defaultOrderBy = "priority";
    protected static $aiPriority = true;
}