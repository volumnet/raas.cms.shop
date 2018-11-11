<?php
/**
 * Файл теста конвертера Excel 5 для загрузчика прайсов
 */
namespace RAAS\CMS\Shop;

use PHPExcel_Reader_Excel5;
use PHPExcel_Writer_Excel5;

/**
 * Класс теста конвертера Excel 5 для загрузчика прайсов
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

        $this->assertInstanceOf(PHPExcel_Reader_Excel5::class, $reader);
    }


    /**
     * Тест получения writer'а для заданного типа Excel
     */
    public function testGetWriter()
    {
        $converter = new Excel5PriceloaderDataConverter();

        $writer = $converter->getWriter();

        $this->assertInstanceOf(PHPExcel_Writer_Excel5::class, $writer);
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
