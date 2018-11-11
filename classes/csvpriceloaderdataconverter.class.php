<?php
/**
 * Файл конвертера CSV для загрузчика прайсов
 */
namespace RAAS\CMS\Shop;

use SOME\CSV;

/**
 * Класс конвертера CSV для загрузчика прайсов
 */
class CSVPriceloaderDataConverter extends PriceloaderDataConverter
{
    public function import($text)
    {
        $encoding = mb_detect_encoding($text, 'UTF-8, Windows-1251');
        if ($encoding != 'UTF-8') {
            $text = iconv($encoding, 'UTF-8', $text);
        }
        $csv = new CSV(trim($text));
        $data = $csv->data;
        return $data;
    }


    public function export(array $data, Page $page, $rows = 0, $cols = 0, $encoding = 'UTF-8')
    {
        $csv = new CSV($data);
        unset($data);
        $text = $csv->csv;
        unset($csv);
        if ($encoding && ($encoding != 'UTF-8')) {
            $text = iconv('UTF-8', $encoding . '//IGNORE', $text);
        }
        return $text;
    }


    public function getMime()
    {
        return 'text/csv';
    }


    public function getExtension()
    {
        return 'csv';
    }
}
