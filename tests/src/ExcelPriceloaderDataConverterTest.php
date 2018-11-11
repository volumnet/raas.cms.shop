<?php
/**
 * Файл теста конвертера Excel для загрузчика прайсов
 */
namespace RAAS\CMS\Shop;

use RAAS\Exception;
use RAAS\CMS\Page;
use PHPExcel;
use PHPExcel_Worksheet;

/**
 * Класс теста конвертера Excel для загрузчика прайсов
 */
class ExcelPriceloaderDataConverterTest extends BaseTest
{
    /**
     * Тест загрузки данных из файла
     */
    public function testLoad()
    {
        $converter = ExcelPriceloaderDataConverter::spawn('xls');
        $filename = __DIR__ . '/../resources/test.xls';

        $data = $converter->load($filename);

        $this->assertEquals('Категория 1', $data[0][0]);
    }


    /**
     * Проверка выброса исключения при загрузке данных из неправильного файла
     * @expectedException Exception
     */
    public function testLoadThrowsException()
    {
        $converter = ExcelPriceloaderDataConverter::spawn('xls');

        $filename = __DIR__ . '/../resources/test.xlsx';

        $data = $converter->load($filename);
    }


    /**
     * Тест загрузки данных из строки
     */
    public function testImport()
    {
        $converter = ExcelPriceloaderDataConverter::spawn('xls');
        $filename = __DIR__ . '/../resources/test.xls';
        $text = file_get_contents($filename);

        $data = $converter->import($text);

        $this->assertEquals('Категория 1', $data[0][0]);
    }


    /**
     * Тест сохранения данных в строку
     */
    public function testExport()
    {
        $data = [
            ['Данные 1', '', ''],
            ['', 'Данные 2', ''],
            ['', '', 'Данные 3']
        ];
        $converter = ExcelPriceloaderDataConverter::spawn('xls');
        $filename = tempnam(sys_get_temp_dir(), '');
        $reader = $converter->getReader();

        $text = $converter->export($data, new Page(1), 2, 1);
        $data2 = $converter->import($text);

        $this->assertEquals($data, $data2);

        file_put_contents($filename, $text);
        $workbook = $reader->load($filename);
        $sheet = $workbook->setActiveSheetIndex(0);

        $this->assertEquals('Данные 1', $sheet->getCellByColumnAndRow(0, 1)->getValue());
        $this->assertFalse($sheet->getCellByColumnAndRow(0, 1)->getStyle()->getFont()->getBold());
        $this->assertEquals('Данные 2', $sheet->getCellByColumnAndRow(1, 2)->getValue());
        $this->assertTrue($sheet->getCellByColumnAndRow(1, 2)->getStyle()->getFont()->getBold());

        unlink($filename);
    }


    /**
     * Тест сохранения данных в файл
     */
    public function testSave()
    {
        $data = [
            ['Данные 1'],
            ['', 'Данные 2'],
            ['', '', 'Данные 3']
        ];
        $converter = ExcelPriceloaderDataConverter::spawn('xlsx');
        $filename = tempnam(sys_get_temp_dir(), '');
        $page = new Page(1);

        $page->name = 'Некоторое очень длинное название для страницы';

        $converter->save($filename, $data, $page, 1, 0);
        $reader = $converter->getReader();
        $workbook = $reader->load($filename);
        $sheet = $workbook->setActiveSheetIndex(0);

        $this->assertEquals('Некоторое очень длинное назван', $sheet->getTitle());
        $this->assertEquals('Данные 1', $sheet->getCellByColumnAndRow(0, 1)->getValue());
        $this->assertTrue($sheet->getCellByColumnAndRow(0, 1)->getStyle()->getFont()->getBold());
        $this->assertEquals('Данные 2', $sheet->getCellByColumnAndRow(1, 2)->getValue());
        $this->assertFalse($sheet->getCellByColumnAndRow(1, 2)->getStyle()->getFont()->getBold());

        unlink($filename);
    }


    /**
     * Тест получения данных из книги Excel
     */
    public function testGetDataFromExcelWorkbook()
    {
        $workbook = new PHPExcel();
        $sheet = $workbook->setActiveSheetIndex(0);
        $converter = ExcelPriceloaderDataConverter::spawn('xls');

        $sheet->getCellByColumnAndRow(0, 1)->setValue('Данные 1');
        $sheet->getCellByColumnAndRow(1, 2)->setValue('Данные 2');
        $sheet->getCellByColumnAndRow(2, 3)->setValue('Данные 3');
        $sheet = new PHPExcel_Worksheet($workbook, 'Некоторый лист');
        $workbook->addSheet($sheet);
        $sheet->getCellByColumnAndRow(0, 1)->setValue('Данные 1');
        $sheet->getCellByColumnAndRow(1, 2)->setValue('Данные 2');
        $sheet->getCellByColumnAndRow(2, 3)->setValue('Данные 3');
        $data = $converter->getDataFromExcelWorkbook($workbook);

        $this->assertEquals([
            ['Данные 1', '', ''],
            ['', 'Данные 2', ''],
            ['', '', 'Данные 3'],
            ['Данные 1', '', ''],
            ['', 'Данные 2', ''],
            ['', '', 'Данные 3'],
        ], $data);
    }


    /**
     * Тест сохранения данных в книгу Excel
     */
    public function testPutDataToExcelWorkbook()
    {
        $data = [
            ['Данные 1'],
            ['', 'Данные 2'],
            ['', '', 'Данные 3']
        ];
        $workbook = new PHPExcel();
        $page = new Page(1);
        $converter = ExcelPriceloaderDataConverter::spawn('xls');

        $page->name = 'Некоторое очень длинное название для страницы';
        $converter->putDataToExcelWorkbook($workbook, $data, $page, 1, 0);
        $sheet = $workbook->setActiveSheetIndex(0);

        $this->assertCount(1, $workbook->getAllSheets());
        $this->assertEquals('Некоторое очень длинное назван', $sheet->getTitle());
        $this->assertEquals('Данные 1', $sheet->getCellByColumnAndRow(0, 1)->getValue());
        $this->assertTrue($sheet->getCellByColumnAndRow(0, 1)->getStyle()->getFont()->getBold());
        $this->assertEquals('Данные 2', $sheet->getCellByColumnAndRow(1, 2)->getValue());
        $this->assertFalse($sheet->getCellByColumnAndRow(1, 2)->getStyle()->getFont()->getBold());
    }


    /**
     * Тест получения данных из листа Excel
     */
    public function testGetDataFromExcelSheet()
    {
        $workbook = new PHPExcel();
        $sheet = $workbook->setActiveSheetIndex(0);
        $sheet->getCellByColumnAndRow(0, 1)->setValue('Данные 1');
        $sheet->getCellByColumnAndRow(1, 2)->setValue('Данные 2');
        $sheet->getCellByColumnAndRow(2, 3)->setValue('Данные 3');
        $converter = ExcelPriceloaderDataConverter::spawn('xls');

        $data = $converter->getDataFromExcelSheet($sheet);

        $this->assertEquals([
            ['Данные 1', '', ''],
            ['', 'Данные 2', ''],
            ['', '', 'Данные 3'],
        ], $data);
    }


    /**
     * Тест сохранения данных на лист Excel
     */
    public function testPutDataToExcelSheet()
    {
        $data = [
            ['Данные 1'],
            ['', 'Данные 2'],
            ['', '', 'Данные 3']
        ];
        $workbook = new PHPExcel();
        $sheet = $workbook->setActiveSheetIndex(0);
        $page = new Page(1);
        $converter = ExcelPriceloaderDataConverter::spawn('xls');

        $page->name = 'Некоторое очень длинное название для страницы';
        $converter->putDataToExcelSheet($sheet, $data, $page, 1, 0);

        $this->assertEquals('Некоторое очень длинное назван', $sheet->getTitle());
        $this->assertEquals('Данные 1', $sheet->getCellByColumnAndRow(0, 1)->getValue());
        $this->assertTrue($sheet->getCellByColumnAndRow(0, 1)->getStyle()->getFont()->getBold());
        $this->assertEquals('Данные 2', $sheet->getCellByColumnAndRow(1, 2)->getValue());
        $this->assertFalse($sheet->getCellByColumnAndRow(1, 2)->getStyle()->getFont()->getBold());
    }
}
