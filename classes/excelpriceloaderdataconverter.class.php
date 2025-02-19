<?php
/**
 * Файл конвертера Excel для загрузчика прайсов
 */
declare(strict_types=1);

namespace RAAS\CMS\Shop;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Exception as PhpSpreadsheetException;
use PhpOffice\PhpSpreadsheet\Reader\IReader;
use PhpOffice\PhpSpreadsheet\Writer\IWriter;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RAAS\Exception;
use RAAS\CMS\Page;

/**
 * Класс конвертера Excel для загрузчика прайсов
 */
abstract class ExcelPriceloaderDataConverter extends PriceloaderDataConverter
{
    /**
     * Загружает данные из файла
     * @param string $file Файл для разбора
     * @return array Таблица данных <pre><code>array<array<string>></code></pre>
     * @throws Exception Выбрасывает исключение, если файл не удалось прочитать
     */
    public function load($file): array
    {
        $xlsReader = @$this->getReader();
        $xlsReader->setReadDataOnly(true);
        try {
            $workbook = @$xlsReader->load($file);
            $data = $this->getDataFromExcelWorkbook($workbook);
            return $data;
        } catch (PhpSpreadsheetException $e) {
            throw new Exception(Module::i()->view->_('ERR_CANNOT_READ_FILE'));
        }
    }


    public function import($text): array
    {
        $tmpFile = tempnam(sys_get_temp_dir(), '');
        file_put_contents($tmpFile, $text);
        $data = $this->load($tmpFile);
        unlink($tmpFile);
        return $data;
    }


    public function export(array $data, Page $page, $rows = 0, $cols = 0, $encoding = 'UTF-8'): string
    {
        $tmpFile = tempnam(sys_get_temp_dir(), '');
        $this->save($tmpFile, $data, $page, $rows, $cols, $encoding);
        $text = file_get_contents($tmpFile);
        unlink($tmpFile);
        return $text;
    }


    public function save($file, array $data, Page $page, $rows = 0, $cols = 0, $encoding = 'UTF-8')
    {
        $workbook = new Spreadsheet();
        $this->putDataToExcelWorkbook($workbook, $data, $page, $rows, $cols);
        $objWriter = $this->getWriter($workbook);
        $objWriter->save($file);
    }


    /**
     * Получает reader для заданного типа Excel
     * @return IReader
     */
    abstract public function getReader(): IReader;


    /**
     * Получает writer для заданного типа Excel
     * @param Spreadsheet $workbook Книга Excel
     * @return IWriter
     */
    abstract public function getWriter(Spreadsheet $workbook): IWriter;


    /**
     * Получает данные из книги Excel
     * @param Spreadsheet $workbook Книга Excel
     * @return array Таблица данных <pre><code>array<array<string>></code></pre>
     */
    public function getDataFromExcelWorkbook(Spreadsheet $workbook): array
    {
        // 2020-03-05, AVS: исключили листы кроме первого
        // т.к. в них редко бывает продолжение прайса,
        // а часто вспомогательные данные
        $sheet = $workbook->getSheet(0);
        $data = $this->getDataFromExcelSheet($sheet);
        return $data;
    }


    /**
     * Сохраняет данные в книгу Excel
     * @param Spreadsheet $workbook Книга Excel
     * @param array<array<mixed>> $data Данные для сохранения
     * @param Page $page Исходная страница для выгрузки (для заголовка)
     * @param int $rows Сколько строк пропускать
     * @param int $cols Сколько столбцов пропускать
     */
    public function putDataToExcelWorkbook(Spreadsheet $workbook, array $data, Page $page, $rows = 0, $cols = 0)
    {
        $sheet = $workbook->setActiveSheetIndex(0);
        $this->putDataToExcelSheet($sheet, $data, $page, $rows, $cols);
    }


    /**
     * Получает данные из листа Excel
     * @param Worksheet $sheet Лист Excel
     * @return array Таблица данных <pre><code>array<array<string>></code></pre>
     */
    public function getDataFromExcelSheet(Worksheet $sheet): array
    {
        $data = $sheet->toArray();
        $data = array_map(function ($row) {
            return array_map(function ($col) {
                $result = strval($col);
                // 2024-05-02, AVS: Очищаем некорректные символы
                $result = mb_convert_encoding($result, 'UTF-8', mb_detect_encoding($result, 'UTF-8, ISO-8859-1', true));
                // 2024-05-02, AVS: Обрабатываем NULL-значение
                if ($result == '#NULL!') {
                    $result = '';
                }
                return $result;
            }, $row);
        }, $data);
        return $data;
    }


    /**
     * Сохраняет данные на лист Excel
     * @param Worksheet $sheet Лист Excel
     * @param array<array<mixed>> $data Данные для сохранения
     * @param Page $page Исходная страница для выгрузки (для заголовка)
     * @param int $rows Сколько строк пропускать
     * @param int $cols Сколько столбцов пропускать
     */
    public function putDataToExcelSheet(Worksheet $sheet, array $data, Page $page, $rows = 0, $cols = 0)
    {
        $sheet->setTitle(mb_substr($page->name, 0, 30));
        $maxcol = 0;
        $pageRows = [];
        for ($i = 0; $i < count($data); $i++) {
            $maxcol = max($maxcol, count($data[$i]) - 1);
            $isPage = (count(array_filter($data[$i], function ($x) {
                return trim((string)$x) !== '';
            })) == 1);
            if ($isPage) {
                $pageRows[] = $i;
            }
            for ($j = 0; $j < count($data[$i]); $j++) {
                $val = $data[$i][$j];
                if (is_float($val) || is_int($val)) {
                    $type = DataType::TYPE_NUMERIC;
                } else {
                    $type = DataType::TYPE_STRING;
                }
                $cell = $sheet->getCell([$j + 1, $i + 1]);
                $cell->setValueExplicit($val, $type);
            }
        }
        if ($rows) {
            $range = [(int)$cols + 1, $rows, $maxcol + 1, $rows];
            $sheet->getStyle($range)->getFont()->setBold(true);
        }
        if ($pageRows) {
            foreach ($pageRows as $i) {
                $range = [1, $i + 1, $maxcol + 1, $i + 1];
                $sheet->getStyle($range)->getFont()->setItalic(true);
            }
        }
    }
}
