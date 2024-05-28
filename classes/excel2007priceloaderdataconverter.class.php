<?php
/**
 * Файл конвертера Excel 2007 для загрузчика прайсов
 */
declare(strict_types=1);

namespace RAAS\CMS\Shop;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReader;
use PhpOffice\PhpSpreadsheet\Writer\IWriter;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Класс конвертера Excel 2007 для загрузчика прайсов
 */
class Excel2007PriceloaderDataConverter extends ExcelPriceloaderDataConverter
{
    public function getReader(): IReader
    {
        $xlsReader = IOFactory::createReader('Xlsx');
        return $xlsReader;
    }


    public function getWriter(Spreadsheet $workbook): IWriter
    {
        $xlsWriter = IOFactory::createWriter($workbook, 'Xlsx');
        return $xlsWriter;
    }


    public function getMime(): string
    {
        return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    }


    public function getExtension(): string
    {
        return 'xlsx';
    }
}
