<?php
/**
 * Команда импорта пунктов выдачи заказа СДЭК
 */
namespace RAAS\CMS\Shop;

use RAAS\Application;
use RAAS\Command;

/**
 * Команда импорта пунктов выдачи заказа СДЭК
 */
class SDEKPVZImportCommand extends Command
{
    /**
     * Выполнение команды
     * @param string $filename Файл, в который будет импортироваться список
     *                         (относительно корня приложения)
     */
    public function process($filename = 'sdek.pvz.json')
    {
        $filepath = Application::i()->baseDir . '/' . $filename;
        $sdek = new SDEKInterface();
        $url = $sdek->getDomain() . '/pvzlist/v1/json';
        $text = $sdek->rawMethod($url);
        $tmpname = tempnam(sys_get_temp_dir(), '');
        file_put_contents($tmpname, $text);
        rename($tmpname, $filepath);
    }
}
