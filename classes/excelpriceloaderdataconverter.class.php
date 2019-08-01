<?php
/**
 * Файл конвертера Excel для загрузчика прайсов
 */
namespace RAAS\CMS\Shop;

use PHPExcel;
use PHPExcel_Cell;
use PHPExcel_Cell_DataType;
use PHPExcel_Exception;
use PHPExcel_Reader_IReader;
use PHPExcel_Reader_IWriter;
use PHPExcel_Worksheet;
use RAAS\Exception;

/**
 * Класс конвертера Excel для загрузчика прайсов
 */
abstract class ExcelPriceloaderDataConverter extends PriceloaderDataConverter
{
    /**
     * Загружает данные из файла
     * @param string $file Файл для разбора
     * @return array<array<string>> Таблица данных
     * @throws Exception Выбрасывает исключение, если файл не удалось прочитать
     */
    public function load($file)
    {
        $xlsReader = $this->getReader();
        try {
            $workbook = $xlsReader->load($file);
            $data = $this->getDataFromExcelWorkbook($workbook);
            return $data;
        } catch (PHPExcel_Exception $e) {
            throw new Exception(Module::i()->view->_('ERR_CANNOT_READ_FILE'));
        }
    }


    public function import($text)
    {
        $tmpFile = tempnam(sys_get_temp_dir(), '');
        file_put_contents($tmpFile, $text);
        $data = $this->load($tmpFile);
        unlink($tmpFile);
        return $data;
    }


    public function export(array $data, Page $page, $rows = 0, $cols = 0, $encoding = 'UTF-8')
    {
        $tmpFile = tempnam(sys_get_temp_dir(), '');
        $this->save($tmpFile, $data, $page, $rows, $cols, $encoding);
        $text = file_get_contents($tmpFile);
        unlink($tmpFile);
        return $text;
    }


    public function save($file, array $data, Page $page, $rows = 0, $cols = 0, $encoding = 'UTF-8')
    {
        $workbook = new PHPExcel();
        $this->putDataToExcelWorkbook($workbook, $data, $page, $rows, $cols);
        $objWriter = $this->getWriter($workbook);
        $objWriter->save($file);
    }


    /**
     * Получает reader для заданного типа Excel
     * @return PHPExcel_Reader_IReader
     */
    abstract public function getReader();


    /**
     * Получает writer для заданного типа Excel
     * @param PHPExcel $workbook Книга Excel
     * @return PHPExcel_Reader_IWriter
     */
    abstract public function getWriter(PHPExcel $workbook);


    /**
     * Получает данные из книги Excel
     * @param PHPExcel $workbook Книга Excel
     * @return array<array<string>> Таблица данных
     */
    public function getDataFromExcelWorkbook(PHPExcel $workbook)
    {
        $data = array();
        foreach ($workbook->getAllSheets() as $sheet) {
            $data = array_merge($data, $this->getDataFromExcelSheet($sheet));
        }
        return $data;
    }


    /**
     * Сохраняет данные в книгу Excel
     * @param PHPExcel $workbook Книга Excel
     * @param array<array<mixed>> $data Данные для сохранения
     * @param Page $page Исходная страница для выгрузки (для заголовка)
     * @param int $rows Сколько строк пропускать
     * @param int $cols Сколько столбцов пропускать
     */
    public function putDataToExcelWorkbook(PHPExcel $workbook, array $data, Page $page, $rows = 0, $cols = 0)
    {
        $sheet = $workbook->setActiveSheetIndex(0);
        $this->putDataToExcelSheet($sheet, $data, $page, $rows, $cols);
    }


    /**
     * Получает данные из листа Excel
     * @param PHPExcel_Worksheet $sheet Лист Excel
     * @return array<array<string>> Таблица данных
     */
    public function getDataFromExcelSheet(PHPExcel_Worksheet $sheet)
    {
        $data = $sheet->toArray();
        $data = array_map(function ($x) {
            return array_map('strval', $x);
        }, $data);
        return $data;
    }


    /**
     * Сохраняет данные на лист Excel
     * @param PHPExcel_Worksheet $sheet Лист Excel
     * @param array<array<mixed>> $data Данные для сохранения
     * @param Page $page Исходная страница для выгрузки (для заголовка)
     * @param int $rows Сколько строк пропускать
     * @param int $cols Сколько столбцов пропускать
     */
    public function putDataToExcelSheet(PHPExcel_Worksheet $sheet, array $data, Page $page, $rows = 0, $cols = 0)
    {
        $sheet->setTitle(mb_substr($page->name, 0, 30));
        $maxcol = 0;
        for ($i = 0; $i < count($data); $i++) {
            $maxcol = max($maxcol, count($data[$i]) - 1);
            for ($j = 0; $j < count($data[$i]); $j++) {
                $cell = $sheet->getCellByColumnAndRow($j, $i + 1);
                $cell->setValueExplicit($data[$i][$j], PHPExcel_Cell_DataType::TYPE_STRING);
            }
        }
        if ($rows) {
            $range = PHPExcel_Cell::stringFromColumnIndex((int)$cols) . (int)$rows . ':'
                   . PHPExcel_Cell::stringFromColumnIndex($maxcol) . (int)$rows;
            $sheet->getStyle($range)->getFont()->setBold(true);
        }
    }
}