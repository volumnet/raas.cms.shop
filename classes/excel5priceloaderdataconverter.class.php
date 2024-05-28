<?php
/**
 * Файл конвертера Excel 5 для загрузчика прайсов
 */
declare(strict_types=1);

namespace RAAS\CMS\Shop;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReader;
use PhpOffice\PhpSpreadsheet\Writer\IWriter;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Класс конвертера Excel 5 для загрузчика прайсов
 */
class Excel5PriceloaderDataConverter extends ExcelPriceloaderDataConverter
{
    /**
     * Получает reader для Excel 5
     * @return Spreadsheet_Reader_Excel5
     */
    public function getReader(): IReader
    {
        $xlsReader = IOFactory::createReader('Xls');
        return $xlsReader;
    }


    /**
     * Получает writer для Excel 5
     * @param Spreadsheet $workbook Книга Excel
     * @return IWriter
     */
    public function getWriter(Spreadsheet $workbook): IWriter
    {
        $xlsWriter = IOFactory::createWriter($workbook, 'Xls');
        return $xlsWriter;
    }


    public function getMime(): string
    {
        return 'application/excel';
    }


    public function getExtension(): string
    {
        return 'xls';
    }
}
