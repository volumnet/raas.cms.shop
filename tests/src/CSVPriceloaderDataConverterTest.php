<?php
/**
 * Файл теста конвертера CSV для загрузчика прайсов
 */
namespace RAAS\CMS\Shop;

use RAAS\CMS\Page;
use SOME\BaseTest;
use SOME\CSV;

/**
 * Класс теста конвертера CSV для загрузчика прайсов
 */
class CSVPriceloaderDataConverterTest extends BaseTest
{
    public static $tables = [
        'cms_pages',
    ];

    /**
     * Тест загрузки данных из строки
     */
    public function testImport()
    {
        $converter = new CSVPriceloaderDataConverter();
        $data = [
            ['Данные 1'],
            ['', 'Данные 2'],
            ['', '', 'Данные 3']
        ];
        $text = "Данные 1\n;Данные 2\n;;Данные 3";

        $data = $converter->import($text);

        $this->assertEquals($data, $data);
    }


    /**
     * Тест загрузки данных из строки (вариант с кодировкой Windows-1251)
     */
    public function testImportWithWindows1251()
    {
        $converter = new CSVPriceloaderDataConverter();
        $data = [
            ['Данные 1'],
            ['', 'Данные 2'],
            ['', '', 'Данные 3']
        ];
        $text = "Данные 1\n;Данные 2\n;;Данные 3";
        $text = iconv('UTF-8', 'Windows-1251', $text);

        $data = $converter->import($text);

        $this->assertEquals($data, $data);
    }


    /**
     * Тест сохранения данных в строку
     */
    public function testExport()
    {
        $data = [
            ['Данные 1'],
            ['', 'Данные 2'],
            ['', '', 'Данные 3']
        ];
        $converter = new CSVPriceloaderDataConverter();

        $text = $converter->export($data, new Page(1));
        $csv = new CSV($text);

        $this->assertEquals($data, $csv->data);
    }


    /**
     * Тест сохранения данных в строку (вариант с кодировкой Windows-1251)
     */
    public function testExportWithWindows1251()
    {
        $data = [
            ['Данные 1'],
            ['', 'Данные 2'],
            ['', '', 'Данные 3']
        ];
        $converter = new CSVPriceloaderDataConverter();

        $text = $converter->export($data, new Page(1), 0, 0, 'Windows-1251');
        $text = iconv('Windows-1251', 'UTF-8', $text);
        $csv = new CSV($text);

        $this->assertEquals($data, $csv->data);
    }


    /**
     * Тест получения MIME-типа для заданного формата
     */
    public function testGetMime()
    {
        $converter = new CSVPriceloaderDataConverter();

        $mime = $converter->getMime();

        $this->assertEquals('text/csv', $mime);
    }


    /**
     * Тест получения расширения файла для заданного формата
     */
    public function testGetExtension()
    {
        $converter = new CSVPriceloaderDataConverter();

        $mime = $converter->getExtension();

        $this->assertEquals('csv', $mime);
    }
}
