<?php
/**
 * Файл конвертера Excel 5 для загрузчика прайсов
 */
namespace RAAS\CMS\Shop;

use PHPExcel;
use PHPExcel_IOFactory;
use PHPExcel_Reader_IReader;
use PHPExcel_Reader_IWriter;

/**
 * Класс конвертера Excel 5 для загрузчика прайсов
 */
class Excel5PriceloaderDataConverter extends ExcelPriceloaderDataConverter
{
    /**
     * Получает reader для Excel 5
     * @return PHPExcel_Reader_Excel5
     */
    public function getReader()
    {
        $xlsReader = PHPExcel_IOFactory::createReader('Excel5');
        return $xlsReader;
    }


    /**
     * Получает writer для Excel 5
     * @param PHPExcel $workbook Книга Excel
     * @return PHPExcel_Writer_Excel5
     */
    public function getWriter(PHPExcel $workbook)
    {
        $xlsWriter = PHPExcel_IOFactory::createWriter($workbook, 'Excel5');
        return $xlsWriter;
    }


    public function getMime()
    {
        return 'application/excel';
    }


    public function getExtension()
    {
        return 'xls';
    }
}
