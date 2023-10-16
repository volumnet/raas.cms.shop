<?php
/**
 * Интерфейс загрузчика изображений
 * @param ImageLoader $Loader Загрук
 */
namespace RAAS\CMS\Shop;

use RAAS\Application;
use RAAS\Attachment;
use RAAS\CMS\Material;
use RAAS\CMS\Package;
use RAAS\CMS\Sub_Main as Package_Sub_Main;

$interface = new ImageloaderInterface($Loader);
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    return $interface->upload($files, $test, $clear);
} else {
    return $interface->download();
}
