<?php
namespace RAAS\CMS\Shop;

use \RAAS\CMS\Feedback;
use \RAAS\CMS\Material;

class Order extends Feedback
{
    protected static $tablename = 'cms_shop_orders';
    protected static $references = array(
        'user' => array('FK' => 'uid', 'classname' => 'RAAS\\CMS\\User', 'cascade' => true),
        'parent' => array('FK' => 'pid', 'classname' => 'RAAS\\CMS\\Shop\\Cart_Type', 'cascade' => true),
        'page' => array('FK' => 'page_id', 'classname' => 'RAAS\\CMS\\Page', 'cascade' => false),
        'viewer' => array('FK' => 'vis', 'classname' => 'RAAS\\User', 'cascade' => false),
        'status' => array('FK' => 'status_id', 'classname' => 'RAAS\\CMS\\Shop\\Order_Status', 'cascade' => false),
    );
    protected static $cognizableVars = array('fields', 'items');
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
            $SQL_query = "DELETE FROM " . static::_dbprefix() . "cms_shop_orders_goods WHERE order_id = " . (int)$this->id;
            static::$SQL->query($SQL_query);
            $arr = array();
            foreach ($this->meta_items as $i => $row) {
                $arr[] = array_merge(
                    array('order_id' => (int)$this->id, 'priority' => $i + 1),
                    (array)$row
                );
            }
            static::$SQL->add(static::_dbprefix() . "cms_shop_orders_goods", $arr);
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


    public static function delete(self $Item)
    {
        $SQL_query = "DELETE FROM " . static::_dbprefix() . "cms_shop_orders_goods WHERE order_id = " . (int)$Item->id;
        static::$SQL->query($SQL_query);
        parent::delete($Item);
    }


    protected function _items()
    {
        $SQL_query = "SELECT tM.*, tOG.meta, tOG.realprice, tOG.amount
                        FROM " . Material::_tablename() . " AS tM 
                        JOIN " . self::_dbprefix() . "cms_shop_orders_goods AS tOG ON tOG.material_id = tM.id 
                       WHERE tOG.order_id = " . (int)$this->id . "
                    ORDER BY tOG.priority";
        $Set = Material::getSQLSet($SQL_query);
        return $Set;
    }
}