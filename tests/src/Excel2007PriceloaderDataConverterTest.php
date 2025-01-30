<?php
/**
 * Файл теста конвертера Excel 2007 для загрузчика прайсов
 */
namespace RAAS\CMS\Shop;

use PhpOffice\PhpSpreadsheet\Reader\Xlsx as ReaderXlsx;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as WriterXlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use SOME\BaseTest;

/**
 * Класс теста конвертера Excel 2007 для загрузчика прайсов
 */
#[CoversClass(Excel2007PriceloaderDataConverter::class)]
class Excel2007PriceloaderDataConverterTest extends BaseTest
{
    /**
     * Тест получения reader'а для заданного типа Excel
     */
    public function testGetReader()
    {
        $converter = new Excel2007PriceloaderDataConverter();

        $reader = $converter->getReader();

        $this->assertInstanceOf(ReaderXlsx::class, $reader);
    }


    /**
     * Тест получения writer'а для заданного типа Excel
     */
    public function testGetWriter()
    {
        $converter = new Excel2007PriceloaderDataConverter();
        $workbook = new Spreadsheet();

        $writer = $converter->getWriter($workbook);

        $this->assertInstanceOf(WriterXlsx::class, $writer);
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
