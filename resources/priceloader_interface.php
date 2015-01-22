<?php
namespace RAAS\CMS\Shop;
use \Exception;
use \RAAS\CMS\Page;
use \RAAS\CMS\Material;
use \RAAS\Application;
use \PHPExcel;
use \PHPExcel_Cell;
use \PHPExcel_IOFactory;
use \PHPExcel_Style_NumberFormat;
use \PHPExcel_Cell_DataType;

ini_set('max_execution_time', 300);
$st = microtime(true);
require_once Application::i()->includeDir . '/phpexcel/Classes/PHPExcel.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Загрузка прайса
    if (!$file) {
        return array('localError' => array(array('name' => 'MISSING', 'value' => 'file', 'description' => Module::i()->view->_('UPLOAD_FILE_REQUIRED'))));
    } elseif (!in_array(strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)), array('xls', 'xlsx', 'csv'))) {
        return array('localError' => array(array('name' => 'INVALID', 'value' => 'file', 'description' => Module::i()->view->_('ALLOWED_FORMATS_CSV_XLS_XLSX'))));
    }
    $type = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    switch ($type) {
        case 'xls': case 'xlsx':
            switch ($type) {
                case 'xls':
                    $readerName = 'Excel5';
                    break;
                case 'xlsx':
                    $readerName = 'Excel2007';
                    break;
            }
            $objReader = PHPExcel_IOFactory::createReader($readerName);
            try {
                $x = $objReader->load($file['tmp_name']);
                $DATA = array();
                foreach ($x->getAllSheets() as $s) {
                    $DATA = array_merge($DATA, $s->toArray());
                }
            } catch (Exception $e) {
                return array('localError' => array(array('name' => 'INVALID', 'value' => 'file', 'description' => Module::i()->view->_('ERR_CANNOT_READ_FILE'))));
            }
            break;
        case 'csv':
            $text = file_get_contents($file['tmp_name']);
            $encoding = mb_detect_encoding($text, 'UTF-8, Windows-1251');
            if ($encoding != 'UTF-8') {
                $text = iconv($encoding, 'UTF-8', $text);
            }
            $csv = new \SOME\CSV(trim($text));
            $DATA = $csv->data;
            unset($csv);
            break;
    }
    if (!$DATA || ((count($DATA) == 1) && (count(array_filter($DATA[0])) == 1))) {
        return array('localError' => array(array('name' => 'INVALID', 'value' => 'file', 'description' => Module::i()->view->_('ERR_EMPTY_FILE'))));
    }
    print_r ($DATA); exit;
    return true;
} else {
    // Выгрузка прайса
    $downloadPrice = function(Page $Page = null, $level = 0) use ($Loader, &$downloadPrice, $cols, $rows) {
        static $mtypes;
        if (!$mtypes) {
            $mtypes = array_merge(array((int)$Loader->Material_Type->id), (array)$Loader->Material_Type->all_children_ids);
        }

        $DATA = array();
        if (!$Page) {
            $Page = $Loader->Page;
        }
        if ($level) {
            $temp = array_fill(0, $level, '');
            $temp[$level - 1] = $Page->name;
            $DATA[] = $temp;
        }


        $SQL_query = "SELECT tM.* FROM " . Material::_tablename() . " AS tM ";
        if (!$Loader->Material_Type->global_type) {
            $SQL_query .= " JOIN " . Material::_dbprefix() . "cms_materials_pages_assoc AS tMPA ON tMPA.id = tM.id";
        }
        $SQL_query .= " WHERE tM.pid IN (" . implode(", ", $mtypes) . ") ";
        if (!$Loader->Material_Type->global_type) {
            $SQL_query .= " AND tMPA.pid = " . (int)$Page->id;
        }
        $SQL_query .= " GROUP BY tM.id";
        $SQL_result = Material::_SQL()->get($SQL_query);
        if (($rows > 0) && !$level) {
            $temp = array();
            foreach ($Loader->columns as $col) {
                $x = '';
                if ($col->Field->id) {
                    $x = $col->Field->name;
                } elseif ($col->fid == 'name') {
                    $x = Module::i()->view->_('NAME');
                } elseif ($col->fid == 'urn') {
                    $x = Module::i()->view->_('URN');
                } elseif ($col->fid == 'description') {
                    $x = Module::i()->view->_('DESCRIPTION');
                }
                $temp[] = $x;
            }
            $DATA[] = $temp;
        }
        foreach ($SQL_result as $row2) {
            $row = new Material($row2);
            $temp = array();
            foreach ($Loader->columns as $col) {
                $x = null;
                if ($col->Field->id) {
                    $x = $row->fields[$col->Field->urn]->doRich();
                } elseif ($col->fid) {
                    $x = $row->{$col->fid};
                } else {
                    $temp[] = '';
                    continue;
                }
                if ($f = $col->CallbackDownload) {
                    $x = $f($x);
                }
                $temp[] = $x;
            }
            $DATA[] = $temp;
            $row->rollback();
            unset($row);
        }
        foreach ($Page->children as $row) {
            $DATA = array_merge($DATA, $downloadPrice($row, $level + 1));
        }
        return $DATA;
    };

    if (!$Page->id) {
        $Page = $Loader->Page;
    }
    if (!$type) {
        $type = 'csv';
    }
    $DATA = $downloadPrice($Page);
    if ($cols > 0) {
        $DATA = array_map(function($row) use ($cols) { return array_merge(array_fill(0, (int)$cols, ''), (array)$row); }, $DATA);
    }
    if ($rows > 1) {
        $DATA = array_merge(array_fill(0, (int)($rows - 1), array('')), $DATA);
    }
    while (ob_get_level()) {
        ob_end_clean();
    }
    $filename = date('Y-m-d') . ' - ' . $Page->name;
    switch ($type) {
        case 'xls': case 'xlsx':
            $filename .= '.' . $type;
            $x = new PHPExcel();
            $x->setActiveSheetIndex(0)->setTitle($Page->name);
            $maxcol = 0;
            for ($i = 0; $i < count($DATA); $i++) {
                $maxcol = max($maxcol, count($DATA[$i]));
                for ($j = 0; $j < count($DATA[$i]); $j++) {
                    $cell = $x->getActiveSheet()->getCellByColumnAndRow($j, $i + 1);
                    $cell->setValueExplicit(trim($DATA[$i][$j]), PHPExcel_Cell_DataType::TYPE_STRING);
                }
            }
            if ($rows) {
                $range = PHPExcel_Cell::stringFromColumnIndex((int)$cols) . (int)$rows . ':' . PHPExcel_Cell::stringFromColumnIndex($maxcol + (int)$cols) . (int)$rows;
                $x->getActiveSheet()->getStyle($range)->getFont()->setBold(true); 
            }
            switch ($type) {
                case 'xlsx':
                    $writerName = 'Excel2007';
                    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; name="' . $filename . '"');
                    break;
                default:
                    $writerName = 'Excel5';
                    header('Content-Type: application/excel; name="' . $filename . '"');
                    break;
            }
            $objWriter = PHPExcel_IOFactory::createWriter($x, $writerName);
            $temp_file = tempnam(sys_get_temp_dir(), '');
            $objWriter->save($temp_file);
            $text = file_get_contents($temp_file);
            break;
        default:
            $filename .= '.csv';
            $csv = new \SOME\CSV($DATA);
            unset($DATA);
            $text = $csv->csv;
            unset($csv);
            if ($encoding) {
                $text = iconv('UTF-8', $encoding . '//IGNORE', $text);
            }
            header('Content-Type: text/csv; name="' . $filename . '"');
            break;
    }
    
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo $text;
    exit;
}