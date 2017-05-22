<?php
namespace RAAS\CMS\Shop;

class User_Cart extends Cart
{
    protected function load()
    {
        $this->items = array();
        if ($this->_user && (int)$this->_user->id) {
            $SQL_query = "SELECT * FROM cms_shop_carts WHERE cart_type_id = " . (int)$this->cartType->id . " AND uid = " . (int)$this->_user->id;
            $SQL_result = Cart_Type::_SQL()->get($SQL_query);
            foreach ($SQL_result as $row) {
                $items[(int)$row['material_id']][$row['meta']] = (int)$row['amount'];
            }
            $this->items = $items;
        }
    }


    protected function save()
    {
        if ($this->_user && (int)$this->_user->id) {
            $arr = array();
            foreach ($this->items as $item_id => $metas) {
                foreach ($metas as $meta => $c) {
                    $row = array(
                        'cart_type_id' => (int)$this->cartType->id,
                        'uid' => (int)$this->_user->id,
                        'material_id' => (int)$item_id,
                        'meta' => $meta,
                        'amount' => (int)$c
                    );
                    $arr[] = $row;
                }
            }
            $SQL_query = "DELETE FROM cms_shop_carts WHERE cart_type_id = " . (int)$this->cartType->id . " AND uid = " . (int)$this->_user->id;
            $SQL_result = Cart_Type::_SQL()->query($SQL_query);
            if ($arr) {
                Cart_Type::_SQL()->add('cms_shop_carts', $arr);
            }
        }
    }
}
