<?php
/**
 * Файл конвертера данных для загрузчика прайсов
 */
declare(strict_types=1);

namespace RAAS\CMS\Shop;

use RAAS\Exception;
use RAAS\CMS\Page;

/**
 * Класс конвертера данных для загрузчика прайсов
 */
abstract class PriceloaderDataConverter
{
    /**
     * Фабрика конвертеров по расширению файла
     * @param 'csv'|'xls'|'xlsx' $type Расширение файла
     * @return static
     */
    public static function spawn($type): self
    {
        switch ($type) {
            case 'xls':
                return new Excel5PriceloaderDataConverter();
                break;
            case 'xlsx':
                return new Excel2007PriceloaderDataConverter();
                break;
            default:
                return new CSVPriceloaderDataConverter();
                break;
        }
    }


    /**
     * Загружает данные из файла
     * @param string $file Файл для разбора
     * @return array Таблица данных <pre><code>array<array<string>></code></pre>
     */
    public function load($file): array
    {
        $text = file_get_contents($file);
        $data = $this->import($text);
        return $data;
    }


    /**
     * Загружает данные из строки
     * @param string $text Строка с данными
     * @return array Таблица данных <pre><code>array<array<string>></code></pre>
     */
    abstract public function import($text): array;


    /**
     * Сохраняет данные в строку
     * @param array $data Таблица данных <pre><code>array<array<string>></code></pre>
     * @param Page $page Страница, из которой выгружаем
     * @param int $rows Сколько строк пропускать
     * @param int $cols Сколько столбцов пропускать
     * @param string $encoding Кодировка в которой выгружаем (совместимо с iconv)
     *                         (в настоящее время затрагивает только CSV, в остальных - для совместимости)
     * @return string $text Строка с данными
     */
    abstract public function export(array $data, Page $page, $rows = 0, $cols = 0, $encoding = 'UTF-8'): string;


    /**
     * Сохраняет данные в файл
     * @param string $file Файл для сохранения
     * @param array<array<string>> $data Таблица данных
     * @param Page $page Страница, из которой выгружаем
     * @param int $rows Сколько строк пропускать
     * @param int $cols Сколько столбцов пропускать
     * @param string $encoding Кодировка в которой выгружаем (совместимо с iconv)
     *                         (в настоящее время затрагивает только CSV, в остальных - для совместимости)
     */
    public function save($file, array $data, Page $page, $rows = 0, $cols = 0, $encoding = 'UTF-8')
    {
        $text = $this->export($data, $page, $rows, $cols, $encoding);
        file_put_contents($file, $text);
    }


    /**
     * Получает MIME-тип для заданного формата
     * @return string
     */
    abstract public function getMime(): string;


    /**
     * Получает расширение файла для заданного формата
     * @return string
     */
    abstract public function getExtension(): string;
}
