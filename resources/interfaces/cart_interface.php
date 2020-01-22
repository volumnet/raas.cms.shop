<?php
/**
 * Стандартный интерфейс корзины
 * @param Block_Cart $Block Текущий блок
 * @param Page $Page Текущая страница
 */
namespace RAAS\CMS\Shop;

$interface = new CartInterface($Block, $Page, $_GET, $_POST, $_COOKIE, $_SESSION, $_SERVER, $_FILES);
return $interface->process();
