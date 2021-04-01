<?php
/**
 * Сниппет интерфейса Яндекс.Маркета
 * @param Block_YML $Block Текущий блок
 * @param Page $Page Текущая страница
 */
namespace RAAS\CMS\Shop;

use RAAS\CMS\Page;

$interface = new YMLInterface(
    $Block,
    $Page,
    $_GET,
    $_POST,
    $_COOKIE,
    $_SESSION,
    $_SERVER
);
$interface->process(true, 300);
