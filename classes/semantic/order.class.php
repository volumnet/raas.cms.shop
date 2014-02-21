<?php
namespace RAAS\CMS\Shop;
use \RAAS\CMS\Feedback;

class Order extends Feedback
{
    protected static $tablename = 'cms_shop_orders';
    protected static $links = array(
        'items' => array('tablename' => 'cms_shop_orders_goods', 'field_from' => 'order_id', 'field_to' => 'material_id', 'classname' => 'RAAS\\CMS\\Material')
    );

    public function __get($var)
    {
        switch ($var) {
            case 'count':
                $sum = 0;
                foreach ($this->items as $row) {
                    $sum += $row->amount;
                }
                return $sum;
                break;
            case 'sum':
                $sum = 0;
                foreach ($this->items as $row) {
                    $sum += $row->amount * $row->realprice;
                }
                return $sum;
                break;
            default:
                return parent::__get($var);
                break;
        }
    }
}