<?php
/**
 * Интерфейс Сбербанка
 * @param Block_Cart $Block Текущий блок
 * @param Page $Page Текушая страница
 * @param Order $Item текущий заказ
 */
namespace RAAS\CMS\Shop;

use RAAS\CMS\Page;

$interface = new SberbankInterface(
    $Block,
    $Page,
    $_GET,
    $_POST,
    $_COOKIE,
    $_SESSION,
    $_SERVER,
    $_FILES
);
$sberbankOut = $interface->process($Item);
if (isset($OUT)) {
    $OUT = array_merge((array)$OUT, $sberbankOut);
}
return $sberbankOut;
