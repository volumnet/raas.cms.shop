<?php
namespace RAAS\CMS\Shop;

use RAAS\CMS\Material;
use RAAS\CMS\Material_Field;
use RAAS\CMS\Material_Type;
use RAAS\CMS\User;
use RAAS\Application;
use stdClass;

class Cart
{
    protected $cartType;
    protected $items = array();
    protected $_user;

    public function __get($var)
    {
        switch ($var) {
            case 'cartType':
                return $this->$var;
                break;
            case 'rawItems':
                return $this->items;
                break;
            case 'items':
                $temp = array();
                foreach ($this->items as $item_id => $metas) {
                    foreach ($metas as $meta => $c) {
                        $m = new Material((int)$item_id);
                        $row = new stdClass();
                        $row->id = $m->id;
                        $row->name = $m->name;
                        $row->meta = $meta;
                        $row->realprice = (float)$m->{$this->getPriceURN($m->material_type)};
                        $row->amount = $c;
                        $temp[] = $row;
                    }
                }
                return $temp;
                break;
            case 'count':
                $sum = 0;
                foreach ($this->items as $item_id => $metas) {
                    foreach ($metas as $meta => $c) {
                        $sum += $c;
                    }
                }
                return $sum;
                break;
            case 'sum':
                $sum = 0;
                foreach ($this->items as $item_id => $metas) {
                    $Item = new Material((int)$item_id);
                    if ($priceURN = $this->getPriceURN($Item->material_type)) {
                        $price = (float)$Item->{$priceURN};
                        foreach ($metas as $meta => $c) {
                            $sum += $c * $price;
                        }
                    }
                }
                return $sum;
                break;
        }
    }


    public function __construct(Cart_Type $CartType = null, User $user = null)
    {
        $this->_user = $user;
        if ($CartType) {
            $this->cartType = $CartType;
        } else {
            $Set = Cart_Type::getSet();
            $this->cartType = $Set[0];
        }
        $this->load();
    }


    public function set(Material $Item, $amount, $meta = '')
    {
        $amount = max(0, (int)$amount);
        if ($this->cartType->no_amount) {
            $amount = min(1, $amount);
        }
        if ($amount > 0) {
            $ids = (array)$this->cartType->material_types_ids;
            foreach ((array)$this->cartType->material_types_ids as $id) {
                $row = new Material_Type($id);
                $ids = array_merge($ids, $row->all_children_ids);
            }
            $ids = array_values(array_unique($ids));
            if ($Item->id && in_array($Item->material_type->id, $ids)) {
                $this->items[(int)$Item->id][(string)$meta] = $amount;
            }
        } else {
            unset($this->items[(int)$Item->id][(string)$meta]);
        }
        $this->save();
    }


    public function count(Material $Item, $meta = '')
    {
        if (isset($this->items[(int)$Item->id][(string)$meta])) {
            return (int)$this->items[(int)$Item->id][(string)$meta];
        }
        return 0;
    }


    public function add(Material $Item, $amount = 1, $meta = '')
    {
        $this->set($Item, $this->count($Item, $meta) + $amount, $meta);
    }


    public function reduce(Material $Item, $amount = 1, $meta = '')
    {
        $this->set($Item, $this->count($Item, $meta) - $amount, $meta);
    }


    public function clear()
    {
        $this->items = array();
        $this->save();
    }


    protected function load()
    {
        $var = 'cart_' . (int)$this->cartType->id;
        $items1 = @(array)json_decode($_COOKIE[$var], true);
        if ($this->_user && (int)$this->_user->id) {
            $items2 = array();
            $SQL_query = "SELECT * FROM cms_shop_carts WHERE cart_type_id = " . (int)$this->cartType->id . " AND uid = " . (int)$this->_user->id;
            $SQL_result = Cart_Type::_SQL()->get($SQL_query);
            foreach ($SQL_result as $row) {
                $items2[(int)$row['material_id']][$row['meta']] = (int)$row['amount'];
            }
            $items = $items2;
            foreach ($items1 as $materialId => $metaItems) {
                foreach ($metaItems as $meta => $amount) {
                    $items[(int)$materialId][$meta] = $amount;
                }
            }
            $this->items = $items;
            if ($items1 != $items2) {
                $this->save();
            }
        } else {
            $this->items = $items1;
        }
    }


    protected function save()
    {
        $var = 'cart_' . (int)$this->cartType->id;
        $_COOKIE[$var] = json_encode($this->items);
        setcookie($var, $_COOKIE[$var], time() + Application::i()->registryGet('cookieLifetime') * 86400, '/');
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


    public function getPriceURN(Material_Type $Material_Type)
    {
        $mt = $Material_Type;
        while ($mt->id) {
            foreach ($this->cartType->material_types as $row) {
                if ($row->id == $mt->id) {
                    $field = new Material_Field((int)$row->price_id);
                    return $field->urn;
                }
            }
            $mt = $mt->pid ? $mt->parent : new Material_Type();
        }
        return 'price';
    }


    public function getPrice(Material $material)
    {
        $mt = $material->material_type;
        while ($mt->id) {
            foreach ($this->cartType->material_types as $row) {
                if ($row->id == $mt->id) {
                    $field = new Material_Field((int)$row->price_id);
                    $priceURN = $field->urn;
                    $fieldCallback = null;
                    if ($row->price_callback) {
                        $fieldCallback = create_function('$x', $row->price_callback);
                    }
                    break(2);
                }
            }
            $mt = $mt->pid ? $mt->parent : new Material_Type();
        }
        if (!$priceURN) {
            $priceURN = 'price';
            $fieldCallback = null;
        }
        if ($fieldCallback) {
            $price = $fieldCallback($material);
        } elseif ($priceURN) {
            $price = number_format($material->{$priceURN}, 2, '.', '');
        } else {
            $price = null;
        }
        return $price;
    }
}
