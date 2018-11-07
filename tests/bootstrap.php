<?php
namespace RAAS\CMS;

use RAAS\Application;

require __DIR__ . '/../../../../cron/cron.php';
require __DIR__ . '/src/BaseTest.php';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['HTTPS'] = 'off';
\RAAS\Application::i()->run('cron', false);
