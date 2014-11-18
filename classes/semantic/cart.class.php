<?php
namespace RAAS\CMS\Shop;
use \RAAS\CMS\Material;
use \RAAS\CMS\Material_Field;
use \RAAS\CMS\Material_Type;
use \stdClass;

abstract class Cart
{
    protected $cartType;
    protected $items = array();

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
                        $row->realprice = (int)$m->{$this->getPriceURN($m->material_type)};
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


    public function __construct(Cart_Type $CartType = null)
    {
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
        $this->set($Item, $this->count($Item) + $amount, $meta);
    }


    public function reduce(Material $Item, $amount = 1, $meta = '')
    {
        $this->set($Item, $this->count($Item) - $amount, $meta);
    }


    public function clear()
    {
        $this->items = array();
        $this->save();
    }


    abstract protected function load();


    abstract protected function save();


    public function getPriceURN(Material_Type $Material_Type)
    {
        $mt = $Material_Type;
        while ($mt->id) {
            foreach ($this->cartType->material_types as $row) {
                if ($row->id == $Material_Type->id) {
                    $field_id = $row->price_id;
                    $Field = new Material_Field((int)$field_id);
                    return $Field->urn;
                }
            }
            $mt = $mt->pid ? $mt->parent : new Material_Type();
        }
        return 'price';
    }
}