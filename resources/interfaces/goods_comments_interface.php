<?php
/**
 * Стандартный интерфейс комментариев к товарам
 * @param Block_Material $Block Текущий блок
 * @param Page $Page Текущая страница
 */
namespace RAAS\CMS\Shop;

use RAAS\CMS\Block_Material;
use RAAS\CMS\Page;

$interface = new GoodsCommentsInterface(
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
