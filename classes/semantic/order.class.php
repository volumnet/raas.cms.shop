<?php
namespace RAAS\CMS\Shop;

use Mustache_Engine;
use RAAS\Application;
use RAAS\CMS\Feedback;
use RAAS\CMS\Material;

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
        if (($this->updates['status_id'] !== null) &&
            ($this->properties['status_id'] != $this->updates['status_id']) &&
            $this->status->do_notify
        ) {
            $this->notifyStatus();
        }
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

    /**
     * Получает список товаров в виде массива текста (для комментариев)
     * @param array<
     *            Material материал с полями заказа (
     *                'meta' => string Мета-данные материала в заказе
     *                'realprice' => float Стоимость материала в заказе (за единицу)
     *                'amount' => int Количество
     *            )|array<[
     *                'material_id' => int ID# материала
     *                'name' => string Наименование материала
     *                'meta' => string Мета-данные материала в заказе
     *                'realprice' => float Стоимость материала в заказе (за единицу)
     *                'amount' => int Количество
     *            ]>
     *        > $items Список товаров
     * @return array<string>
     */
    public static function getItemsTextArr(array $items = [])
    {
        $arr = [];
        foreach ($items as $item) {
            if ($item instanceof Material) {
                $arr[] = '#' . $item->id . ' ' . $item->name
                       . ($item->meta['meta'] ? ' (' . $item->meta['meta'] . ')' : '')
                       . ': ' . (float)$item->realprice . ' x ' . (int)$item->amount
                       . ' = ' . (float)($item->realprice * $item->amount);
            } elseif (is_array($item)) {
                $arr[] = '#' . $item['material_id'] . ' ' . $item['name']
                       . ($item['meta'] ? ' (' . $item['meta'] . ')' : '')
                       . ': ' . (float)$item['realprice'] . ' x ' . (int)$item['amount']
                       . ' = ' . (float)($item['realprice'] * $item['amount']);
            }
        }
        return $arr;
    }


    /**
     * Уведомить пользователя об изменении статуса
     */
    public function notifyStatus()
    {
        $dataArr = ['ID' => (int)$this->id];
        $emails = [];
        foreach ($this->fields as $field) {
            if (!in_array($field->datatype, ['material', 'file', 'image']) && !$field->multiple) {
                $val = $field->doRich();
                $dataArr[mb_strtoupper($field->urn)] = $val;
                if (($field->datatype == 'email') || ($field->urn == 'email')) {
                    $emails[] = $val;
                }
            }
        }
        $mustache = new Mustache_Engine();
        if ($emails) {
            $subject = $mustache->render($this->status->notification_title, $dataArr);
            $message = $mustache->render($this->status->notification, $dataArr);
            Application::i()->sendmail(
                $emails,
                $subject,
                $message,
                ViewSub_Orders::i()->_('ADMINISTRATION_OF_SITE') . ' ' . $_SERVER['HTTP_HOST'],
                'info@' . $_SERVER['HTTP_HOST']
            );
        }
    }
}
