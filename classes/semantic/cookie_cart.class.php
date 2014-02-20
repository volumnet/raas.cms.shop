<?php
namespace \RAAS\CMS\Shop;

class Cookie_Cart extends Cart
{
    protected function load()
    {
        $this->items = @(array)json_decode($_COOKIE['cart_' . (int)$this->id], true);
    }


    protected function save()
    {
        $_COOKIE['cart_' . (int)$this->id] = json_encode($this->items);
        setcookie($var, $_COOKIE['cart_' . (int)$this->id], time() + Application::i()->registryGet('cookieLifetime') * 86400, '/');
    }
}