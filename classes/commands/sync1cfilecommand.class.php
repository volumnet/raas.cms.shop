<?php
/**
 * Файл команды синхронизации товаров с 1С из пары файлов
 */
namespace RAAS\CMS\Shop;

use RAAS\Command;
use RAAS\CMS\Page;
use RAAS\CMS\Material_Type;
use RAAS\Exception;

/**
 * Класс команды синхронизации товаров с 1С из пары файлов
 */
class Sync1CFileCommand extends Command
{
    /**
     * Запускает команду
     * @param string $configFile Файл конфигурации
     * @param string $goodsFile Относительный путь (относительно папки обмена с 1С) файла номенклатуры
     * @param string $offersFile Относительный путь (относительно папки обмена с 1С) файла предложений
     * @param int $clear Очищать предыдущие материалы (константа из Sync1CInterface::DELETE_PREVIOUS_MATERIALS_...)
     */
    public function process(
        $configFile = '',
        $goodsFile = null,
        $offersFile = null,
        $clear = Sync1CInterface::DELETE_PREVIOUS_MATERIALS_NONE
    ) {
        try {
            $config = $this->loadConfig($configFile);
        } catch (Exception $e) {
            $this->controller->doLog($e->getMessage());
            return;
        }
        $interface = new Sync1CInterface();
        if ($goodsFile) {
            $goodsFile = $config['sync1CDir'] . '/' . $goodsFile;
            if (!is_file($goodsFile)) {
                $this->controller->doLog('Invalid goods file specified');
            }
        }
        if ($offersFile) {
            $offersFile = $config['sync1CDir'] . '/' . $offersFile;
            if (!is_file($offersFile)) {
                $this->controller->doLog('Invalid offers file specified');
            }
        }

        $controller = $this->controller;

        try {
            $interface->process(
                $config['page'],
                $config['materialType'],
                $goodsFile,
                $offersFile,
                $config['goodsXSLFile'],
                $config['offersXSLFile'],
                $config['mappingFile'],
                $config['articleFieldURN'],
                $config['assets1CDir'],
                $clear,
                function ($x) use ($controller) {
                    return $controller->doLog($x);
                },
                $config['saveMappingAfterIterations']
            );
        } catch (\Exception $e) {
            $this->controller->doLog($e->getMessage());
        }
    }


    /**
     * Загружает и проверяет файл конфигурации
     * @param string $configFile Путь к файлу конфигурации
     * @return [
     *         'page' => Page Страница для загрузки,
     *         'materialType' => Material_Type тип материала загружаемых товаров,
     *         'articleFieldURN' => string URN поля артикула (уникального поля товаров)),
     *         'sync1CDir' => Корневая папка обмена файлами 1С
     *         'assets1CDir' => string Относительный путь (относительно корневой папки обмена файлами 1С)
     *                                 для размещения сопутствующих файлов
     *         'goodsXSLFile' => string XLS-файл обработки номенклатуры 1С
     *         'offersXSLFile' => string XLS-файл обработки предложений 1С
     *         'mappingFile' => string Файл маппинга идентификаторов
     *         'saveMappingAfterIterations' => int Сохранять файл маппинга после определенного количества итераций
     *                                             (0 - если только после прохождения очередной сущности)
     * ]
     * @throws Exception Выбрасывает исключение, если файл конфигурации не существует, либо данные некорректны
     */
    public function loadConfig($configFile)
    {
        if (!$configFile || !is_file($configFile)) {
            throw new Exception('Config file doesn\'t exist');
        }
        $config = include $configFile;
        if (!isset(
            $config['pageId'],
            $config['materialTypeURN'],
            $config['articleFieldURN'],
            $config['sync1CDir']
        )) {
            throw new Exception('Invalid config file');
        }
        $page = new Page((int)$config['pageId']);
        if ($page->id != $config['pageId']) {
            throw new Exception('Invalid page specified');
        }
        $materialType = Material_Type::importByURN($config['materialTypeURN']);
        if ($materialType->urn != $config['materialTypeURN']) {
            throw new Exception('Invalid material type specified');
        }
        $articleFieldURN = $config['articleFieldURN'];
        $articleField = $materialType->fields[$articleFieldURN];
        if ($articleField->urn != $config['articleFieldURN']) {
            throw new Exception('Invalid article field specified');
        }
        $sync1CDir = $config['sync1CDir'];
        if (!is_dir($sync1CDir)) {
            throw new Exception('Invalid 1C sync directory specified');
        }
        $assets1CDir = $sync1CDir;
        if (isset($config['assets1CDir'])) {
            $assets1CDir .= '/' . $config['assets1CDir'];
        }
        if (!is_dir($assets1CDir)) {
            throw new Exception('Invalid 1C assets directory specified');
        }
        $goodsXSLFile = isset($config['goodsXSLFile']) ? $config['goodsXSLFile'] : null;
        if ($goodsXSLFile && !is_file($goodsXSLFile)) {
            throw new Exception('Invalid goods XSL file specified');
        }
        $offersXSLFile = isset($config['offersXSLFile']) ? $config['offersXSLFile'] : null;
        if ($offersXSLFile && !is_file($offersXSLFile)) {
            throw new Exception('Invalid goods XSL file specified');
        }
        $mappingFile = isset($config['mappingFile']) ? $config['mappingFile'] : null;
        $saveMappingAfterIterations = isset($config['saveMappingAfterIterations'])
                                    ? (int)$config['saveMappingAfterIterations']
                                    : 0;
        return [
            'page' => $page,
            'materialType' => $materialType,
            'articleFieldURN' => $articleFieldURN,
            'sync1CDir' => $sync1CDir,
            'assets1CDir' => $assets1CDir,
            'goodsXSLFile' => $goodsXSLFile,
            'offersXSLFile' => $offersXSLFile,
            'mappingFile' => $mappingFile,
            'saveMappingAfterIterations' => $saveMappingAfterIterations,
        ];
    }
}
