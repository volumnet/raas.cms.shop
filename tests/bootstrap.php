<?php
namespace RAAS\CMS\Shop;

use RAAS\Application;
use RAAS\CMS\Package;

require __DIR__ . '/../../../cron/cron.php';
require __DIR__ . '/../../common/classes/controller_frontend.class.php';
require __DIR__ . '/src/BaseTest.php';
require __DIR__ . '/src/BaseDBTest.php';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['HTTPS'] = 'off';
Application::i()->run('cron', false);
Package::i();
