<?php
namespace RAAS\CMS\Shop;
use \RAAS\CMS\Material;
use \RAAS\CMS\Material_Field;

abstract class Cart
{
    protected $cartType;
    protected $items = array();

    public function __get($var)
    {
        switch (variable) {
            case 'cartType':
                return $this->cartType;
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
                    $material_type_id = $Item->material_type->id;
                    $field_id = array_shift(array_filter($this->cartType->material_types, function($x) use ($material_type_id) { return $x->id == $material_type_id; }));
                    $Field = Material_Field((int)$field_id);
                    $price = (float)$Item->{$Field->urn};
                    foreach ($metas as $meta => $c) {
                        $sum += $c * $price;
                    }
                }
                return $sum;
                break;
        }
    }


    public function __construct(Cart_Type $CartType)
    {
        $this->cartType = $CartType;
        $this->load();
    }


    public function set(Material $Item, $amount, $meta = '')
    {
        $amount = max(0, (int)$amount);
        if ($this->cartType->no_amount) {
            $amount = min(1, $amount);
        }
        if ($amount > 0) {
            if (in_array($Item->material_type->id, $this->cartType->material_types_ids)) {
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
            return count($this->items[(int)$Item->id][(string)$meta]);
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


    abstract protected function load();


    abstract protected function save();
}