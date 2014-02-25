<?php
namespace RAAS\CMS\Shop;
use \RAAS\Application;

class Cookie_Cart extends Cart
{
    protected function load()
    {
        $var = 'cart_' . (int)$this->cartType->id;
        $this->items = @(array)json_decode($_COOKIE[$var], true);
    }


    protected function save()
    {
        $var = 'cart_' . (int)$this->cartType->id;
        $_COOKIE[$var] = json_encode($this->items);
        setcookie($var, $_COOKIE[$var], time() + Application::i()->registryGet('cookieLifetime') * 86400, '/');
    }
}