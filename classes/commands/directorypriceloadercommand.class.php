<?php
/**
 * Файл класса команды загрузки прайсов из папки
 */
namespace RAAS\CMS\Shop;

use RAAS\Application;
use RAAS\LockCommand;
use SOME\EventProcessor;
use RAAS\CMS\Page;
use RAAS\CMS\Material;

/**
 * Класс команды загрузки прайсов из папки
 */
class DirectoryPriceLoaderCommand extends LockCommand
{
    /**
     * Выполнение команды
     * @param string $dir Директория, из которой берем файлы
     *                    Файлы должны быть названы по URN загрузчика с любым
     *                    из доступных расширений
     *                    (относительно корня приложения)
     * @param bool $forceLockUpdate Принудительно выполнить обновление,
     *                              даже если есть параллельный процесс
     */
    public function process($dir = '', $forceLockUpdate = false)
    {
        $t = $this;
        if (!$forceLockUpdate && $this->checkLock()) {
            return;
        }
        if (!$dir) {
            $this->controller->doLog('Directory is not set');
            return;
        }
        $dirpath = Application::i()->baseDir . '/' . trim($dir, '/');
        if (!is_dir($dirpath)) {
            $this->controller->doLog('Directory "' . $dir . '" does not exist');
            return;
        }
        $files = glob($dirpath . '/*.*');

        $sqlQuery = "SELECT urn
                      FROM " . PriceLoader::_tablename()
                  . " ORDER BY urn";
        $availablePriceLoadersURNs = Application::i()->SQL->getcol($sqlQuery);
        $availablePriceLoadersURNs = array_map(
            'mb_strtolower',
            $availablePriceLoadersURNs
        );

        $files = array_values(array_filter(
            $files,
            function ($x) use ($availablePriceLoadersURNs) {
                return in_array(
                    mb_strtolower(pathinfo($x, PATHINFO_EXTENSION)),
                    ['xls', 'xlsx', 'csv']
                ) && in_array(
                    mb_strtolower(pathinfo($x, PATHINFO_FILENAME)),
                    $availablePriceLoadersURNs
                );
            }
        ));
        if (!$files) {
            $this->controller->doLog('There are no proper files in "' . $dir . '" directory');
            return;
        }
        EventProcessor::on(
            'priceLoaderLog',
            PriceloaderInterface::class,
            function ($object, $logEntry) {
                $message = $logEntry['text'];
                if ($logEntry['realrow']) {
                    $message .= ' (:' . $logEntry['realrow'] . ')';
                }
                $this->controller->doLog($message);
            }
        );
        EventProcessor::on(
            PriceloaderInterface::class . ':parse:priceLoaderDataParsed',
            PriceloaderInterface::class,
            function ($object, $logEntry) {
                $this->controller->doLog('Data parsed');
            }
        );
        $this->lock();
        $_SERVER['REQUEST_METHOD'] = 'POST'; // Для интерфейса загрузчиков
        foreach ($files as $file) {
            $priceloaderURN = pathinfo($file, PATHINFO_FILENAME);
            $priceloader = PriceLoader::importByURN($priceloaderURN);
            $this->controller->doLog('Priceloader "' . $priceloaderURN . '" started');
            $data = $priceloader->upload(['name' => basename($file), 'tmp_name' => $file]);
            if ($data['localError']) {
                foreach ($data['localError'] as $row) {
                    $this->controller->doLog($row['description']);
                }
            }
            $this->controller->doLog('Priceloader "' . $priceloaderURN . '" finished');
            unlink($file);
        }
        $this->unlock();
    }
}
