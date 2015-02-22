<?php
namespace RAAS\CMS\Shop;
use \RAAS\CMS\Feedback;

class Order extends Feedback
{
    const PAYMENT_NOT_PAID = 0;
    const PAYMENT_PAID_NOT_CONFIRMED = 1;
    const PAYMENT_PAID_CONFIRMED = 2;

    protected static $tablename = 'cms_shop_orders';
    protected static $references = array(
        'user' => array('FK' => 'uid', 'classname' => 'RAAS\\CMS\\User', 'cascade' => true),
        'parent' => array('FK' => 'pid', 'classname' => 'RAAS\\CMS\\Shop\\Cart_Type', 'cascade' => true),
        'page' => array('FK' => 'page_id', 'classname' => 'RAAS\\CMS\\Page', 'cascade' => false),
        'viewer' => array('FK' => 'vis', 'classname' => 'RAAS\\User', 'cascade' => false),
        'status' => array('FK' => 'status_id', 'classname' => 'RAAS\\CMS\\Shop\\Order_Status', 'cascade' => false),
    );
    protected static $links = array(
        'items' => array('tablename' => 'cms_shop_orders_goods', 'field_from' => 'order_id', 'field_to' => 'material_id', 'classname' => 'RAAS\\CMS\\Material'),
    );
    protected static $children = array(
        'history' => array('classname' => 'RAAS\\CMS\\Shop\\Order_History', 'FK' => 'order_id')
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


    public function commit()
    {
        parent::commit();
        if ($this->meta_items) {
            $t = $this;
            $SQL_query = "DELETE FROM " . static::_dbprefix() . self::$links['items']['tablename'] . " WHERE order_id = " . (int)$this->id;
            static::$SQL->query($SQL_query);
            $arr = array_map(function($x) use ($t) { return array_merge(array('order_id' => (int)$t->id), (array)$x); }, $this->meta_items);
            static::$SQL->add(static::_dbprefix() . self::$links['items']['tablename'], $arr);
            unset($this->meta_items);
        }
    }


    protected function _fields()
    {
        $temp = $this->parent->Form->fields;
        $arr = array();
        foreach ($temp as $row) {
            $row->Owner = $this;
            $arr[$row->urn] = $row;
        }
        return $arr;
    }

    public static function Order(self $Item)
    {
        $SQL_query = "DELETE FROM " . static::_dbprefix() . self::$links['items']['tablename'] . " WHERE order_id = " . (int)$Item->id;
        static::$SQL->query($SQL_query);
        parent::delete($Item);
    }
}