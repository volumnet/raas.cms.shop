<?php
/**
 * Файл теста конвертера Excel для загрузчика прайсов
 */
namespace RAAS\CMS\Shop;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\Datatype;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use SOME\BaseTest;
use RAAS\Exception;
use RAAS\CMS\Page;

/**
 * Класс теста конвертера Excel для загрузчика прайсов
 */
#[CoversClass(ExcelPriceloaderDataConverter::class)]
class ExcelPriceloaderDataConverterTest extends BaseTest
{
    public static $tables = [
        'cms_pages',
    ];

    /**
     * Проверка ошибки от 2024-05-02 19:55
     * $sheet->toArray() иногда выдает некорректные символы UTF8
     * Нужно их почистить
     */
    public function testError202405021955()
    {
        $converter = ExcelPriceloaderDataConverter::spawn('xls');
        $sheet = $this->getMockBuilder(Worksheet::class)
            ->onlyMethods([
                'toArray',
            ])
            ->getMock();
        $sheet->expects($this->once())
            ->method('toArray')
            ->willReturn([
                ["4\xa0000"],
            ]);

        $result = $converter->getDataFromExcelSheet($sheet);

        $this->assertEquals([
            ['4 000'],
        ], $result);
    }

    /**
     * Проверка ошибки от 2024-05-02 19:31
     * $sheet->toArray() иногда выдает текстовое значение #NULL!
     * Нужно чтобы вместо него попадала пустая строка
     */
    public function testError202405021931()
    {
        $converter = ExcelPriceloaderDataConverter::spawn('xls');
        $sheet = $this->getMockBuilder(Worksheet::class)
            ->onlyMethods([
                'toArray',
            ])
            ->getMock();
        $sheet->expects($this->once())
            ->method('toArray')
            ->willReturn([
                ['aaa', 1, '#NULL!', 'ccc'],
                ['aaa', '#NULL!', 'bbb', 2],
            ]);

        $result = $converter->getDataFromExcelSheet($sheet);

        $this->assertEquals([
            ['aaa', '1', '', 'ccc'],
            ['aaa', '', 'bbb', '2'],
        ], $result);
    }


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
     */
    public function testLoadThrowsException()
    {
        $this->expectException(Exception::class);

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
        // file_put_contents(__DIR__ . '/aaa.xls', $text); exit;
        $data2 = $converter->import($text);


        $this->assertEquals($data, $data2);

        file_put_contents($filename, $text);
        $workbook = $reader->load($filename);
        $sheet = $workbook->setActiveSheetIndex(0);

        $this->assertEquals('Данные 1', $sheet->getCell('A1')->getValue());
        $this->assertFalse($sheet->getCell('A1')->getStyle()->getFont()->getBold());
        $this->assertEquals('Данные 2', $sheet->getCell('B2')->getValue());
        $this->assertTrue($sheet->getCell('B2')->getStyle()->getFont()->getBold());

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
        $this->assertEquals('Данные 1', $sheet->getCell('A1')->getValue());
        $this->assertTrue($sheet->getCell('A1')->getStyle()->getFont()->getBold());
        $this->assertEquals('Данные 2', $sheet->getCell('B2')->getValue());
        $this->assertFalse($sheet->getCell('B2')->getStyle()->getFont()->getBold());

        unlink($filename);
    }


    /**
     * Тест получения данных из книги Excel
     */
    public function testGetDataFromExcelWorkbook()
    {
        $workbook = new Spreadsheet();
        $sheet = $workbook->setActiveSheetIndex(0);
        $converter = ExcelPriceloaderDataConverter::spawn('xls');

        $sheet->getCell('A1')->setValue('Данные 1');
        $sheet->getCell('B2')->setValue('Данные 2');
        $sheet->getCell('C3')->setValue('Данные 3');
        $sheet = new Worksheet($workbook, 'Некоторый лист');
        $workbook->addSheet($sheet);
        $sheet->getCell('A1')->setValue('Данные 1');
        $sheet->getCell('B2')->setValue('Данные 2');
        $sheet->getCell('C3')->setValue('Данные 3');
        $data = $converter->getDataFromExcelWorkbook($workbook);

        // 2020-03-05, AVS: исключили листы кроме первого
        // т.к. в них редко бывает продолжение прайса,
        // а часто вспомогательные данные
        $this->assertEquals([
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
        $workbook = new Spreadsheet();
        $page = new Page(1);
        $converter = ExcelPriceloaderDataConverter::spawn('xls');

        $page->name = 'Некоторое очень длинное название для страницы';
        $converter->putDataToExcelWorkbook($workbook, $data, $page, 1, 0);
        $sheet = $workbook->setActiveSheetIndex(0);

        $this->assertCount(1, $workbook->getAllSheets());
        $this->assertEquals('Некоторое очень длинное назван', $sheet->getTitle());
        $this->assertEquals('Данные 1', $sheet->getCell('A1')->getValue());
        $this->assertTrue($sheet->getCell('A1')->getStyle()->getFont()->getBold());
        $this->assertEquals('Данные 2', $sheet->getCell('B2')->getValue());
        $this->assertFalse($sheet->getCell('B2')->getStyle()->getFont()->getBold());
    }


    /**
     * Тест получения данных из листа Excel
     */
    public function testGetDataFromExcelSheet()
    {
        $workbook = new Spreadsheet();
        $sheet = $workbook->setActiveSheetIndex(0);
        $sheet->getCell('A1')->setValue('Данные 1');
        $sheet->getCell('B2')->setValue('Данные 2');
        $sheet->getCell('C3')->setValue('Данные 3');
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
            ['', '', 'Данные 3', 3]
        ];
        $workbook = new Spreadsheet();
        $sheet = $workbook->setActiveSheetIndex(0);
        $page = new Page(1);
        $converter = ExcelPriceloaderDataConverter::spawn('xls');

        $page->name = 'Некоторое очень длинное название для страницы';
        $converter->putDataToExcelSheet($sheet, $data, $page, 1, 0);

        $this->assertEquals('Некоторое очень длинное назван', $sheet->getTitle());
        $this->assertEquals('Данные 1', $sheet->getCell('A1')->getValue());
        $this->assertTrue($sheet->getCell('A1')->getStyle()->getFont()->getBold());
        $this->assertEquals('Данные 2', $sheet->getCell('B2')->getValue());
        $this->assertFalse($sheet->getCell('B2')->getStyle()->getFont()->getBold());
        $this->assertEquals('Данные 3', $sheet->getCell('C3')->getValue());
        $this->assertEquals(DataType::TYPE_STRING, $sheet->getCell('C3')->getDataType());
        $this->assertEquals(3, $sheet->getCell('D3')->getValue());
        $this->assertEquals(DataType::TYPE_NUMERIC, $sheet->getCell('D3')->getDataType());
    }
}
