<?php
/**
 * Файл теста конвертера Excel 5 для загрузчика прайсов
 */
namespace RAAS\CMS\Shop;

use PhpOffice\PhpSpreadsheet\Reader\Xls as ReaderXls;
use PhpOffice\PhpSpreadsheet\Writer\Xls as WriterXls;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use SOME\BaseTest;

/**
 * Класс теста конвертера Excel 5 для загрузчика прайсов
 * @covers RAAS\CMS\Shop\Excel5PriceloaderDataConverter
 */
class Excel5PriceloaderDataConverterTest extends BaseTest
{
    /**
     * Тест получения reader'а для заданного типа Excel
     */
    public function testGetReader()
    {
        $converter = new Excel5PriceloaderDataConverter();

        $reader = $converter->getReader();

        $this->assertInstanceOf(ReaderXls::class, $reader);
    }


    /**
     * Тест получения writer'а для заданного типа Excel
     */
    public function testGetWriter()
    {
        $converter = new Excel5PriceloaderDataConverter();
        $workbook = new Spreadsheet();

        $writer = $converter->getWriter($workbook);

        $this->assertInstanceOf(WriterXls::class, $writer);
    }


    /**
     * Тест получения MIME-типа для заданного формата
     */
    public function testGetMime()
    {
        $converter = new Excel5PriceloaderDataConverter();

        $mime = $converter->getMime();

        $this->assertEquals('application/excel', $mime);
    }


    /**
     * Тест получения расширения файла для заданного формата
     */
    public function testGetExtension()
    {
        $converter = new Excel5PriceloaderDataConverter();

        $mime = $converter->getExtension();

        $this->assertEquals('xls', $mime);
    }
}
