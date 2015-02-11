<?php
namespace RAAS\CMS\Shop;
use \Exception;
use \RAAS\CMS\Page;
use \RAAS\CMS\Material;
use \RAAS\CMS\Package;
use \RAAS\CMS\Sub_Main as Package_Sub_Main;
use \RAAS\CMS\Material_Field;
use \RAAS\CMS\Page_Field;
use \RAAS\Application;
use \RAAS\Attachment;
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
    $affectedPages = array();
    $affectedMaterials = array();
    if (!$file && !$clear) {
        return array('localError' => array(array('name' => 'MISSING', 'value' => 'file', 'description' => Module::i()->view->_('UPLOAD_FILE_REQUIRED'))));
    }
    if ($file) {
        if (!in_array(strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)), array('xls', 'xlsx', 'csv'))) {
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
        $DATA = array_map(function($x) use ($cols) { return array_slice($x, $cols); }, $DATA);
        $DATA = array_slice($DATA, $rows);
        $DATA = array_filter($DATA, function($x) { return count(array_filter($x, 'trim')); }); // Фильтруем пустые строки
        $DATA = array_values($DATA);
        if (!$DATA || ((count($DATA) == 1) && (count(array_filter($DATA[0])) == 1))) {
            return array('localError' => array(array('name' => 'INVALID', 'value' => 'file', 'description' => Module::i()->view->_('ERR_EMPTY_FILE'))));
        }
        $log = $raw_data = array();
        
        // Получим номер колонки с уникальным полем
        $uniqueColumn = null;
        if ($Loader->ufid) {
            foreach ($Loader->columns as $i => $col) {
                if ($col->fid == $Loader->ufid) {
                    $uniqueColumn = $i;
                    break;
                }
            }
        }
        $backtrace = array();
        $context = $Page;
        $virtualLevel = null; // При запрете создавать новые категории, сюда устанавливается уровень не найденной категории (чтобы игнорировать дочерние)
        

        // Поиск товара по уникальному полю
        $getItemByUniqueField = function($text) use ($Loader) {
            if (trim($text) && $Loader->ufid) {
                $SQL_query = " SELECT tM.* FROM " . Material::_tablename() . " AS tM ";
                if ($Loader->Unique_Field->id) {
                    $SQL_query .= " JOIN " . Material::_dbprefix() . "cms_data AS tD ON tD.pid = tM.id AND tD.fid = " . (int)$Loader->Unique_Field->id 
                               .  " WHERE TRIM(tD.value)";
                } elseif ($Loader->ufid) {
                    $SQL_query .= " WHERE TRIM(tM." . $Loader->ufid . ")";
                }
                $SQL_query .= " = '" . Material::_SQL()->real_escape_string(trim($text)) . "' ORDER BY tM.id";
                $SQL_result = Material::getSQLSet($SQL_query);
                return $SQL_result;
            }
            return array();
        };


        // Поиск товара по всем полям
        $getItemByEntireRow = function(array $row = array()) use ($Loader) {
            $SQL_from = array(Material::_tablename() . " AS tM");
            $SQL_where = array();
            for ($i = 0; $i < max(count($row), count($Loader->columns)); $i++) {
                if (trim($row[$i])) {
                    $tmp_where = '';
                    if ($Loader->columns[$i]->Field->id) {
                        $SQL_from[] = Material::_dbprefix() . "cms_data AS tD" . (int)$Loader->columns[$i]->Field->id 
                                    . " ON tD" . (int)$Loader->columns[$i]->Field->id . ".pid = tM.id "
                                    . " AND tD" . (int)$Loader->columns[$i]->Field->id . ".fid = " . (int)$Loader->columns[$i]->Field->id;
                        $tmp_where = " TRIM(tD" . (int)$Loader->columns[$i]->Field->id . ".value) ";
                    } elseif ($Loader->columns[$i]->fid) {
                        $tmp_where = " TRIM(tM." . $Loader->columns[$i]->fid . ") ";
                    }
                    if ($tmp_where) {
                        $tmp_where .= " = '" . Material::_SQL()->real_escape_string(trim($row[$i])) . "'";
                    }
                    $SQL_where[] = $tmp_where;
                }
            }
            $SQL_query = "SELECT tM.* FROM " . implode(" JOIN ", $SQL_from) . " WHERE " . ($SQL_where ? implode(" AND ", $SQL_where) : " 1 ") . " ORDER BY tM.id";
            $SQL_result = Material::getSQLSet($SQL_query);
            if ($SQL_result) {
                return $SQL_result;
            }
            return array();
        };


        // Возвращает последнюю категорию из backtrace
        $lastCat = function() use (&$backtrace, &$Page) {
            if ($backtrace) {
                $temp = array_reverse($backtrace);
                $temp = array_values($temp);
                return $temp[0];
            }
            return $Page;
        };


        // Возвращает последний уровень из backtrace
        $lastLevel = function() use (&$backtrace) {
            if ($backtrace) {
                $temp = array_reverse($backtrace, true);
                $temp = array_keys($temp);
                return $temp[0];
            }
            return null;
        };


        // Усечение backtrace
        $cropBacktrace = function($level) use (&$backtrace) {
            $keys = array_keys($backtrace);
            foreach ($keys as $key) {
                if ($key >= $level) {
                    unset($backtrace[$key]);
                }
            }
        };

        for ($i = 0; $i < count($DATA); $i++) {
            $dataRow = $DATA[$i];
            if (count(array_filter($DATA[$i], 'trim')) > 1) {
                // Товар
                $dataRow = array_slice($dataRow, 0, count($Loader->columns));
                for ($j = 0; $j < count($dataRow); $j++) {
                    $dataRow[$j] = trim($dataRow[$j]);
                    if ($f = $Loader->columns[$j]->Callback) {
                        $dataRow[$j] = $f($dataRow[$j]);
                    }
                }
                $itemSet = null;
                if (($uniqueColumn !== null) && trim($dataRow[$uniqueColumn])) {
                    $itemSet = $getItemByUniqueField(trim($dataRow[$uniqueColumn]));
                } else {
                    $itemSet = $getItemByEntireRow($dataRow);
                }
                if (!$itemSet && $Loader->create_materials) {
                    $row = new Material();
                    $row->pid = $Loader->Material_Type->id;
                    $row->vis = 1;
                    $itemSet = array($row);
                }
                foreach ($itemSet as $Item) {
                    // Сначала проходим нативные поля
                    for ($j = 0; $j < count($dataRow); $j++) {
                        if (trim($dataRow[$j]) && (!$uniqueColumn || ($j != $uniqueColumn)) && !$Loader->columns[$j]->Field->id && $Loader->columns[$j]->fid) {
                            $Item->{$Loader->columns[$j]->fid} = trim($dataRow[$j]);
                        }
                    }
                    $id = $Item->id;
                    if (!$test) {
                        $Item->commit();
                        if ($Item->id && !$Loader->Material_Type->global_type && $context->id && !in_array($context->id, $Item->pages_ids)) {
                            Material::_SQL()->add(Material::_dbprefix() . "cms_materials_pages_assoc", array('id' => (int)$Item->id, 'pid' => (int)$context->id));
                        }
                        // Проходим доп. поля
                        for ($j = 0; $j < count($dataRow); $j++) {
                            if (trim($dataRow[$j]) && (!$uniqueColumn || ($j != $uniqueColumn)) && $Loader->columns[$j]->Field->id) {
                                $val = $Item->fields[$Loader->columns[$j]->Field->urn]->fromRich(trim($dataRow[$j]));
                                if ($val != $Item->fields[$Loader->columns[$j]->Field->urn]->getValues()) {
                                    $Item->fields[$Loader->columns[$j]->Field->urn]->deleteValues();
                                    $Item->fields[$Loader->columns[$j]->Field->urn]->addValue($val);
                                }
                            }
                        }
                    }
                    $affectedMaterials[] = (int)$Item->id;
                    $log[] = array(
                        'time' => (microtime(true) - $st), 
                        'text' => sprintf(
                            Module::i()->view->_('LOG_MATERIAL_' . ($id ? 'UPDATED' : 'CREATED')), 
                            Package_Sub_Main::i()->url . '&action=edit_material&id=' . (int)$Item->id, 
                            $Item->name
                        ),
                        'row' => $i,
                        'realrow' => $i + $rows,
                    );
                    $Item->rollback();
                    unset($Item);
                }
                unset($itemSet);
            } elseif (count(array_filter($DATA[$i], 'trim')) == 1) {
                // Категория
                list($step, $name) = each(array_filter($DATA[$i], 'trim'));
                if ($Loader->catalog_offset) {
                    $step = 0;
                    if (preg_match('/^\\s+/i', $name, $regs)) {
                        $step = strlen($regs[0]);
                    }
                }
                $name = trim($name);
                if (!$virtualLevel || ($step <= $virtualLevel)) {
                    if ($step > 0) {
                        $cropBacktrace($step);
                    } else {
                        $backtrace = array();
                    }
                    $context = $lastCat();

                    $SQL_result = Page::getSet(array('where' => array("pid = " . (int)$context->id, "name = '" . Page::_SQL()->real_escape_string($name) . "'")));
                    if ($SQL_result || $Loader->create_pages) {
                        if ($SQL_result) {
                            $context = $SQL_result[0];
                            $id = $context->id;
                        } elseif ($Loader->create_pages) {
                            $arr = array(
                                'pid' => (int)$context->id, 
                                'vis' => 1, 
                                'name' => $name,
                                'sitemaps_priority' => $context->sitemaps_priority ?: '0.5',
                                'inherit_sitemaps_priority' => $context->inherit_sitemaps_priority,
                                'nat' => $context->nat,
                                'lang' => $context->inherit_lang ? $context->lang : Package::i()->view->language
                            );
                            foreach (array('title', 'keywords', 'description') as $key) {
                                $arr['meta_' . $key] = $context->{'inherit_meta_' . $key} ? $context->{'meta_' . $key} : '';
                                $arr['inherit_meta_' . $key] = $context->{'inherit_meta_' . $key};
                            }
                            foreach (array('changefreq', 'cache', 'template') as $key) {
                                $arr[$key] = $context->$key;
                                $arr['inherit_' . $key] = $context->{'inherit_' . $key};
                            }
                            $context = new Page($arr);
                            $id = 0;
                            if (!$test) {
                                $context->commit();
                                $context->rollback();
                            }
                        }
                        $affectedPages[] = (int)$context->id;
                        $backtrace[$step] = $context;
                        $virtualLevel = null;
                        $log[] = array(
                            'time' => (microtime(true) - $st), 
                            'text' => sprintf(
                                Module::i()->view->_('LOG_PAGE_' . ($id ? 'SELECTED' : 'CREATED')), 
                                Package_Sub_Main::i()->url . '&action=edit_page&id=' . (int)$context->id, 
                                $context->name
                            ),
                            'row' => $i,
                            'realrow' => $i + $rows,
                        );
                    } else {
                        $virtualLevel = $step;
                        $log[] = array('time' => (microtime(true) - $st), 'text' => sprintf(Module::i()->view->_('LOG_PAGE_NOT_SELECTED'), $name), 'row' => $i, 'realrow' => $i + $rows);
                    }
                }
            }
            $raw_data[] = $dataRow;
        }
    }

    if ($clear) {
        if (count($Page->parents) > count($Loader->Page->parents)) {
            $deleteRoot = $Page;
        } else {
            $deleteRoot = $Loader->Page;
        }
        if (($clear == PriceLoader::DELETE_PREVIOUS_MATERIALS_MATERIALS_ONLY) || ($clear == PriceLoader::DELETE_PREVIOUS_MATERIALS_MATERIALS_AND_PAGES)) {
            // Очищаем материалы
            
            // Ищем задействованные типы
            $mtypes = array_merge(array((int)$Loader->Material_Type->id), (array)$Loader->Material_Type->all_children_ids);
            $mtypes = array_map('intval', $mtypes);
            
            // Ищем материалы для удаления
            $affectedMaterials = array_map('intval', $affectedMaterials);
            $SQL_query = "SELECT tM.id FROM " . Material::_tablename() . " AS tM ";
            if (!$Loader->Material_Type->global_type) {
                $SQL_query .= " LEFT JOIN " . Material::_dbprefix() . "cms_materials_pages_assoc AS tMPA ON (tMPA.id = tM.id)";
            }
            $SQL_query .= " WHERE tM.pid IN (" . implode(", ", $mtypes ?: array(0)) . ") AND tM.id NOT IN (" . implode(", ", $affectedMaterials ?: array(0)) . ")";
            if (!$Loader->Material_Type->global_type) {
                $SQL_query .= " AND (tMPA.pid IN (" . implode(", ", array_merge(array($deleteRoot->id), (array)$deleteRoot->all_children_ids)) . ") OR tMPA.pid IS NULL)";
            }
            $materialsToClear = Material::_SQL()->getcol($SQL_query);
            
            // Ищем поля картинок и файлов (с attachment'ами)
            $SQL_query = "SELECT tF.id FROM " . Material_Field::_tablename() . " AS tF 
                           WHERE tF.classname = 'RAAS\\\\CMS\\\\Material_Type' AND tF.pid IN (" . implode(", ", $mtypes ?: array(0)) . ") AND datatype IN ('image', 'file')";
            $fieldsToClear = Material::_SQL()->getcol($SQL_query);

            // Ищем attachment'ы для удаления
            $attachmentsToClear = array();
            $SQL_query = "SELECT value FROM " . Material::_dbprefix() . "cms_data 
                           WHERE pid IN (" . implode(", ", $materialsToClear ?: array(0)) . ") AND fid IN (" . implode(", ", $fieldsToClear ?: array(0)) . ")";
            $SQL_result = Material::_SQL()->getcol($SQL_query);
            foreach ($SQL_result as $val) {
                if (preg_match('/"attachment":(\\d+)/i', $val, $regs)) {
                    $attachmentsToClear[] = (int)$regs[1];
                }
            }
            $SQL_query = "SELECT realname FROM " . Attachment::_tablename() . " WHERE id IN (" . implode(", ", $attachmentsToClear ?: array(0)) . ")";
            $filesToClear = Material::_SQL()->getcol($SQL_query);

            if (!$test) {
                // Очищаем материалы
                $SQL_query = "DELETE FROM " . Material::_tablename() . " WHERE id IN (" . implode(", ", $materialsToClear ?: array(0)) . ")";
                Material::_SQL()->query($SQL_query);

                // Очищаем привязку к страницам
                $SQL_query = "DELETE FROM " . Material::_dbprefix() . "cms_materials_pages_assoc WHERE id IN (" . implode(", ", $materialsToClear ?: array(0)) . ")";
                Material::_SQL()->query($SQL_query);

                // Очищаем данные
                $SQL_query = "DELETE tD 
                                FROM " . Material::_dbprefix() . "cms_data AS tD JOIN " . Material_Field::_tablename() . " AS tF ON tF.id = tD.fid 
                               WHERE (tF.classname = 'RAAS\\\\CMS\\\\Material_Type') AND (tF.pid > 0) AND (tD.pid IN (" . implode(", ", $materialsToClear ?: array(0)) . "))";
                Material::_SQL()->query($SQL_query);

                // Чистим файлы
                foreach ($filesToClear as $val) {
                    $val = realpath(Package::i()->filesDir) . '/' . str_replace('.', '*.', $val);
                    $arr = glob($val);
                    foreach ($arr as $row) {
                        unlink($row);
                    }
                }

                // Чистим сами attachment'ы
                $SQL_query = "DELETE FROM " . Attachment::_tablename() . " WHERE id IN (" . implode(", ", $attachmentsToClear ?: array(0)) . ")";
                Material::_SQL()->query($SQL_query);
            } else {
                foreach ($materialsToClear as $val) {
                    $row = new Material($val);
                    $log[] = array(
                        'time' => (microtime(true) - $st), 
                        'text' => sprintf(Module::i()->view->_('LOG_DELETE_MATERIALS'), Package_Sub_Main::i()->url . '&action=edit_material&id=' . $row->id, $row->name)
                    );
                }
                foreach ($fieldsToClear as $val) {
                    $row = new Material_Field($val);
                    $log[] = array('time' => (microtime(true) - $st), 'text' => sprintf(Module::i()->view->_('LOG_DELETE_FIELDS'), $row->name));
                }
                foreach ($attachmentsToClear as $val) {
                    $row = new Attachment($val);
                    $log[] = array(
                        'time' => (microtime(true) - $st), 
                        'text' => sprintf(Module::i()->view->_('LOG_DELETE_ATTACHMENTS'), '/' . Package::i()->filesURL . '/' . $row->realname, $row->realname)
                    );
                }
            }
        }
        if ($clear == PriceLoader::DELETE_PREVIOUS_MATERIALS_MATERIALS_AND_PAGES) {
            // Очищаем страницы
            
            // Ищем страницы для удаления
            $affectedPages = array_map('intval', $affectedPages);
            $pagesToClear = array_diff($deleteRoot->all_children_ids, $affectedPages);
            
            // Ищем поля картинок и файлов (с attachment'ами)
            $SQL_query = "SELECT tF.id FROM " . Page_Field::_tablename() . " AS tF 
                           WHERE tF.classname = 'RAAS\\\\CMS\\\\Material_Type' AND tF.pid = 0 AND datatype IN ('image', 'file')";
            $fieldsToClear = Page::_SQL()->getcol($SQL_query);
            
            // Ищем attachment'ы для удаления
            $attachmentsToClear = array();
            $SQL_query = "SELECT value FROM " . Page::_dbprefix() . "cms_data 
                           WHERE pid IN (" . implode(", ", $pagesToClear ?: array(0)) . ") AND fid IN (" . implode(", ", $fieldsToClear ?: array(0)) . ")";
            $SQL_result = Page::_SQL()->getcol($SQL_query);
            foreach ($SQL_result as $val) {
                if (preg_match('/"attachment":(\\d+)/i', $val, $regs)) {
                    $attachmentsToClear[] = (int)$regs[1];
                }
            }
            $SQL_query = "SELECT realname FROM " . Attachment::_tablename() . " WHERE id IN (" . implode(", ", $attachmentsToClear ?: array(0)) . ")";
            $filesToClear = Material::_SQL()->getcol($SQL_query);

            if (!$test) {
                // Очищаем страницы
                $SQL_query = "DELETE FROM " . Page::_tablename() . " WHERE id IN (" . implode(", ", $pagesToClear ?: array(0)) . ")";
                Page::_SQL()->query($SQL_query);

                // Очищаем привязку к страницам
                $SQL_query = "DELETE FROM " . Material::_dbprefix() . "cms_materials_pages_assoc WHERE pid IN (" . implode(", ", $pagesToClear ?: array(0)) . ")";
                Material::_SQL()->query($SQL_query);

                // Очищаем данные
                $SQL_query = "DELETE tD 
                                FROM " . Page::_dbprefix() . "cms_data AS tD JOIN " . Page_Field::_tablename() . " AS tF ON tF.id = tD.fid 
                               WHERE (tF.classname = 'RAAS\\\\CMS\\\\Material_Type') AND (tF.pid = 0) AND (tD.pid IN (" . implode(", ", $pagesToClear ?: array(0)) . "))";
                Page::_SQL()->query($SQL_query);

                // Чистим файлы
                foreach ($filesToClear as $val) {
                    $val = realpath(Package::i()->filesDir) . '/' . str_replace('.', '*.', $val);
                    $arr = glob($val);
                    foreach ($arr as $row) {
                        unlink($row);
                    }
                }

                // Чистим сами attachment'ы
                $SQL_query = "DELETE FROM " . Attachment::_tablename() . " WHERE id IN (" . implode(", ", $attachmentsToClear ?: array(0)) . ")";
                Page::_SQL()->query($SQL_query);
            } else {
                foreach ($pagesToClear as $val) {
                    $row = new Page($val);
                    $log[] = array(
                        'time' => (microtime(true) - $st), 
                        'text' => sprintf(Module::i()->view->_('LOG_DELETE_PAGES'), Package_Sub_Main::i()->url . '&action=edit_page&id=' . $row->id, $row->name)
                    );
                }
                foreach ($fieldsToClear as $val) {
                    $row = new Page_Field($val);
                    $log[] = array('time' => (microtime(true) - $st), 'text' => sprintf(Module::i()->view->_('LOG_DELETE_FIELDS'), $row->name));
                }
                foreach ($attachmentsToClear as $val) {
                    $row = new Attachment($val);
                    $log[] = array(
                        'time' => (microtime(true) - $st), 
                        'text' => sprintf(Module::i()->view->_('LOG_DELETE_ATTACHMENTS'), '/' . Package::i()->filesURL . '/' . $row->realname, $row->realname)
                    );
                }
            }
        }
        $log[] = array(
            'time' => (microtime(true) - $st), 
            'text' => sprintf(
                Module::i()->view->_('LOG_OLD_MATERIALS_CLEARED'), 
                $Loader->Material_Type->name, 
                Package_Sub_Main::i()->url . '&id=' . (int)$Page->id, 
                $Page->name
            )
        );
    }
    return array('log' => $log, 'raw_data' => $raw_data, 'ok' => true);
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
            if ($Loader->catalog_offset) {
                $temp = array(str_repeat(' ', $Loader->catalog_offset * $level) . trim($Page->name));
            } else {
                $temp = array_fill(0, $level, '');
                $temp[$level - 1] = $Page->name;
            }
            $DATA[] = $temp;
        }


        $SQL_query = "SELECT tM.* FROM " . Material::_tablename() . " AS tM ";
        if (!$Loader->Material_Type->global_type) {
            $SQL_query .= " JOIN " . Material::_dbprefix() . "cms_materials_pages_assoc AS tMPA ON tMPA.id = tM.id";
        }
        $SQL_query .= " WHERE tM.pid IN (" . implode(", ", $mtypes ?: array(0)) . ") ";
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
                $temp[] = trim($x);
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
                $temp[] = trim($x);
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
                    $cell->setValueExplicit($DATA[$i][$j], PHPExcel_Cell_DataType::TYPE_STRING);
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