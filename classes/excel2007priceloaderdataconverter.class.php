<?php
/**
 * Файл конвертера Excel 2007 для загрузчика прайсов
 */
namespace RAAS\CMS\Shop;

use PHPExcel;
use PHPExcel_IOFactory;
use PHPExcel_Reader_IReader;
use PHPExcel_Reader_IWriter;

/**
 * Класс конвертера Excel 2007 для загрузчика прайсов
 */
class Excel2007PriceloaderDataConverter extends ExcelPriceloaderDataConverter
{
    /**
     * Получает reader для Excel 2007
     * @return PHPExcel_Reader_Excel2007
     */
    public function getReader()
    {
        $xlsReader = PHPExcel_IOFactory::createReader('Excel2007');
        return $xlsReader;
    }


    /**
     * Получает writer для Excel 2007
     * @param PHPExcel $workbook Книга Excel
     * @return PHPExcel_Writer_Excel2007
     */
    public function getWriter(PHPExcel $workbook)
    {
        $xlsWriter = PHPExcel_IOFactory::createWriter($workbook, 'Excel2007');
        return $xlsWriter;
    }


    public function getMime()
    {
        return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    }


    public function getExtension()
    {
        return 'xlsx';
    }
}
