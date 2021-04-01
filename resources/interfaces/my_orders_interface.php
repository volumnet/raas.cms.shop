<?php
/**
 * Стандартный интерфейс сервиса "Мои заказы"
 * @param Block_PHP $Block Текущий блок
 * @param Page $Page Текущая страница
 */
namespace RAAS\CMS\Shop;

use RAAS\CMS\Block_PHP;
use RAAS\CMS\Page;

$interface = new MyOrdersInterface(
    $Block,
    $Page,
    $_GET,
    $_POST,
    $_COOKIE,
    $_SESSION,
    $_SERVER,
    $_FILES
);
return $interface->process();
