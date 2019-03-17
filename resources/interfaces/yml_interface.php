<?php
/**
 * Сниппет интерфейса Яндекс.Маркета
 */
namespace RAAS\CMS\Shop;

$interface = new YMLInterface($Block, $Page, $_GET, $_POST, $_COOKIE, $_SESSION, $_SERVER);
$interface->process(true, 300);
