<?php
/**
 * Файл теста конвертера данных для загрузчика прайсов
 */
namespace RAAS\CMS\Shop;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use SOME\BaseTest;
use SOME\CSV;
use RAAS\CMS\Page;

/**
 * Класс теста конвертера данных для загрузчика прайсов
 */
#[CoversClass(PriceloaderDataConverter::class)]
class PriceloaderDataConverterTest extends BaseTest
{
    public static $tables = [
        'cms_pages',
    ];

    /**
     * Тест фабрики конвертеров по расширению файла
     */
    public function testSpawn()
    {
        $converter = PriceloaderDataConverter::spawn('xls');
        $this->assertInstanceOf(Excel5PriceloaderDataConverter::class, $converter);

        $converter = PriceloaderDataConverter::spawn('xlsx');
        $this->assertInstanceOf(Excel2007PriceloaderDataConverter::class, $converter);

        $converter = PriceloaderDataConverter::spawn('foo');
        $this->assertInstanceOf(CSVPriceloaderDataConverter::class, $converter);
    }


    /**
     * Тест загрузки данных из файла
     */
    public function testLoad()
    {
        $converter = PriceloaderDataConverter::spawn('csv');
        $data = $converter->load(__DIR__ . '/../resources/testutf8.csv');
        $this->assertIsArray($data);
        $this->assertEquals(array_values(array_keys($data)), array_keys($data));
        $this->assertEquals('Категория 1', $data[0][0]);
    }


    /**
     * Тест сохранения данных в файл
     */
    public function testSave()
    {
        $converter = PriceloaderDataConverter::spawn('csv');
        $data = [
            ['Данные 1'],
            ['', 'Данные 2'],
            ['', '', 'Данные 3']
        ];
        $filename = tempnam(sys_get_temp_dir(), '');
        $converter->save($filename, $data, new Page(1));
        $this->assertFileExists($filename);
        $text = file_get_contents($filename);
        $csv = new CSV(trim($text));
        $this->assertEquals($data, $csv->data);
        unlink($filename);
    }
}
