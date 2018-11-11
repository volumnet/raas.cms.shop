<?php
/**
 * Файл теста конвертера Excel 2007 для загрузчика прайсов
 */
namespace RAAS\CMS\Shop;

use PHPExcel_Reader_Excel2007;
use PHPExcel_Writer_Excel2007;

/**
 * Класс теста конвертера Excel 2007 для загрузчика прайсов
 */
class Excel2007PriceloaderDataConverterTest extends BaseTest
{
   /**
     * Тест получения reader'а для заданного типа Excel
     */
    public function testGetReader()
    {
        $converter = new Excel2007PriceloaderDataConverter();

        $reader = $converter->getReader();

        $this->assertInstanceOf(PHPExcel_Reader_Excel2007::class, $reader);
    }


    /**
     * Тест получения writer'а для заданного типа Excel
     */
    public function testGetWriter()
    {
        $converter = new Excel2007PriceloaderDataConverter();

        $writer = $converter->getWriter();

        $this->assertInstanceOf(PHPExcel_Writer_Excel2007::class, $writer);
    }


    /**
     * Тест получения MIME-типа для заданного формата
     */
    public function testGetMime()
    {
        $converter = new Excel2007PriceloaderDataConverter();

        $mime = $converter->getMime();

        $this->assertEquals('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $mime);
    }


    /**
     * Тест получения расширения файла для заданного формата
     */
    public function testGetExtension()
    {
        $converter = new Excel2007PriceloaderDataConverter();

        $mime = $converter->getExtension();

        $this->assertEquals('xlsx', $mime);
    }
}
