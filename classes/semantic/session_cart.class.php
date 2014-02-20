<?php
namespace \RAAS\CMS\Shop;

class Session_Cart extends Cart
{
    protected function load()
    {
        $this->items = @(array)json_decode($_SESSION['cart_' . (int)$this->id], true);
    }


    protected function save()
    {
        $_SESSION['cart_' . (int)$this->id] = json_encode($this->items);
    }
}