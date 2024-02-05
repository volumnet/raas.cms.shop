<?php
namespace RAAS\CMS\Shop;

use RAAS\Application;
use RAAS\CMS\Package;

define('RAAS_BASEDIR', 'd:/web/home/test/www');
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['HTTPS'] = 'off';

require RAAS_BASEDIR . '/vendor/autoload.php';
Package::i()->registerDatatypes();
Application::i()->run('cron');
