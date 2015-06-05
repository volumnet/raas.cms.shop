<?php
namespace RAAS\CMS\Shop;
use \RAAS\CMS\Material;
use \RAAS\CMS\Package;
use \RAAS\CMS\Sub_Main as Package_Sub_Main;
use \RAAS\Application;
use \RAAS\Attachment;

ini_set('max_execution_time', 300);
$st = microtime(true);
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Загрузка картинок
    if (!$files) {
        return array('localError' => array(array('name' => 'MISSING', 'value' => 'files', 'description' => Module::i()->view->_('UPLOAD_FILES_REQUIRED'))));
    } else {
        // Ищем задействованные типы
        $mtypes = array_merge(array((int)$Loader->Material_Type->id), (array)$Loader->Material_Type->all_children_ids);
        $mtypes = array_map('intval', $mtypes);
        $articles = array();
        if ($Loader->Unique_Field->id) {
            $SQL_query = "SELECT tM.id, tD.value ";
        } elseif ($Loader->ufid) {
            $SQL_query = "SELECT id, " . $Loader->ufid . " AS value ";
        }
        $SQL_query .= " FROM " . Material::_tablename() . " AS tM ";
        if ($Loader->Unique_Field->id) {
            $SQL_query .= " JOIN " . Material::_dbprefix() . "cms_data AS tD ON tD.pid = tM.id ";
        }
        $SQL_query .= " WHERE tM.pid IN (" . implode(", ", $mtypes ?: array(0)) . ") ";
        if ($Loader->Unique_Field->id) {
            $SQL_query .= " AND TRIM(tD.value) != '' AND tD.fid = " . (int)$Loader->Unique_Field->id;
        } else {
            $SQL_query .= " AND " . $Loader->ufid . " != '' ";
        }
        $SQL_result = Material::_SQL()->get($SQL_query);
        foreach ($SQL_result as $row) {
            if (trim($row['value'])) {
                $articles[$row['id']] = \SOME\Text::beautify(trim($row['value']));
            }
        }

        // Подготовить реальные файлы к загрузке
        $processFile = function($file) use (&$processFile, $mtypes, $Loader, $articles) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $proceedFiles = array();
            switch ($ext) {
                case 'jpg': case 'jpeg': case 'png': case 'gif':
                    if ($type = getimagesize($file['tmp_name'])) {
                        $file['type'] = image_type_to_mime_type($type[2]);
                        $temp = array();
                        $filename = pathinfo($file['name'], PATHINFO_FILENAME);
                        foreach ($articles as $id => $article) {
                            if (preg_match('/^' . preg_quote($article) . '($|' . preg_quote($Loader->sep_string) . ')/i', $filename)) {
                                $temp[] = $id;
                                break;
                            }
                        }
                        if ($temp) {
                            $file['materials'] = $temp;
                            $ext2 = image_type_to_extension($type[2]);
                            $file['original_name'] = $file['name'];
                            $file['name'] = $filename . $ext2;
                        }
                        $proceedFiles[] = $file;
                    }
                    break;
                case 'zip':
                    $files = array();
                    $z = new \SOME\ZipArchive();
                    if ($z->open($file['tmp_name']) === true) {
                        for ($i = 0; $i < $z->numFiles; $i++) {
                            $tmpname = tempnam(sys_get_temp_dir(), '');
                            file_put_contents($tmpname, $z->getFromIndex($i));
                            $files[] = array('name' => basename($z->getNameIndex($i)), 'tmp_name' => $tmpname);
                        }
                    }
                    $z->close();
                    foreach ($files as $f) {
                        $proceedFiles = array_merge($proceedFiles, $processFile($f));
                    }
                    break;
            }
            return $proceedFiles;
        };

        $proceedFiles = array();
        $affectedMaterials = array();
        foreach ($files as $file) {
            $proceedFiles = array_merge($proceedFiles, $processFile($file));
        }
        if (!$proceedFiles) {
            return array('localError' => array(array('name' => 'INVALID', 'value' => 'files', 'description' => Module::i()->view->_('ALLOWED_FORMATS_JPG_JPEG_PNG_GIF_ZIP'))));
        }
        if ($clear) {
            // Ищем материалы для очистки изображений
            foreach ($proceedFiles as $file) {
                if ($file['materials']) {
                    $affectedMaterials = array_merge($affectedMaterials, (array)$file['materials']);
                }
            }
            $affectedMaterials = array_unique($affectedMaterials);
            $affectedMaterials = array_values($affectedMaterials);

            // Ищем attachment'ы для удаления
            $attachmentsToClear = array();
            $SQL_query = "SELECT value FROM " . Material::_dbprefix() . "cms_data 
                           WHERE pid IN (" . implode(", ", $affectedMaterials ?: array(0)) . ") AND fid = " . (int)$Loader->Image_Field->id;
            $SQL_result = Material::_SQL()->getcol($SQL_query);
            foreach ($SQL_result as $val) {
                if (preg_match('/"attachment":(\\d+)/i', $val, $regs)) {
                    $attachmentsToClear[] = (int)$regs[1];
                }
            }
            $SQL_query = "SELECT realname FROM " . Attachment::_tablename() . " WHERE id IN (" . implode(", ", $attachmentsToClear ?: array(0)) . ")";
            $filesToClear = Material::_SQL()->getcol($SQL_query);

            if (!$test) {
                // Очищаем данные
                $SQL_query = "DELETE tD 
                                FROM " . Material::_dbprefix() . "cms_data AS tD 
                               WHERE tD.fid = " . (int)$Loader->Image_Field->id . " AND tD.pid IN (" . implode(", ", $affectedMaterials ?: array(0)) . ")";
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
                foreach ($attachmentsToClear as $val) {
                    $row = new Attachment($val);
                    $log[] = array(
                        'time' => (microtime(true) - $st), 
                        'text' => sprintf(Module::i()->view->_('LOG_DELETE_ATTACHMENTS'), '/' . Package::i()->filesURL . '/' . $row->realname, $row->realname)
                    );
                }
                foreach ($affectedMaterials as $val) {
                    $row = new Material($val);
                    $log[] = array(
                        'time' => (microtime(true) - $st), 
                        'text' => sprintf(Module::i()->view->_('LOG_DELETE_MATERIAL_IMAGES'), Package_Sub_Main::i()->url . '&action=edit_material&id=' . $row->id, $row->name)
                    );
                }
            }
            $log[] = array('time' => (microtime(true) - $st), 'text' => Module::i()->view->_('LOG_OLD_MATERIAL_IMAGES_CLEARED'));
        }
        foreach ($proceedFiles as $file) {
            if ($file['materials']) {
                $att = new Attachment();
                $att->upload = $file['tmp_name'];
                $att->filename = $file['name'];
                $att->mime = $file['type'];
                $att->parent = $Loader->Image_Field;
                $att->image = 1;
                if ($temp = (int)Application::i()->context->registryGet('maxsize')) {
                    $att->maxWidth = $att->maxHeight = $temp;
                }
                if ($temp = (int)Application::i()->context->registryGet('tnsize')) {
                    $att->tnsize = $temp;
                }
                if (!$test) {
                    $att->commit();
                }
                $row = array('vis' => 1, 'name' => '', 'description' => '', 'attachment' => (int)$att->id);
                foreach ($file['materials'] as $id) {
                    $Item = new Material($id);
                    if (!$test) {
                        $Item->fields[$Loader->Image_Field->urn]->addValue(json_encode($row));
                    }
                    $log[] = array(
                        'time' => (microtime(true) - $st), 
                        'text' => sprintf(
                            Module::i()->view->_('LOG_ADD_MATERIAL_IMAGE'), 
                            '/' . Package::i()->filesURL . '/' . $att->realname, 
                            $att->filename,
                            $file['original_name'],
                            Package_Sub_Main::i()->url . '&action=edit_material&id=' . $Item->id, 
                            $Item->name
                        )
                    );
                }
            }
        }
    }
    return array('log' => $log, 'ok' => true);
} else {
    // Выгрузка картинок
    $st = microtime(true);
    $mtypes = array_merge(array((int)$Loader->Material_Type->id), (array)$Loader->Material_Type->all_children_ids);
    if ($Loader->Image_Field->id) {

        $SQL_query = "SELECT tM.*, ";
        if ($Loader->Unique_Field->id) {
            $SQL_query .= " tD.value ";
        } else {
            $SQL_query .= $Loader->ufid;
        }
        $SQL_query .= " AS ufield
                      FROM " . Material::_tablename() . " AS tM ";
        if ($Loader->Unique_Field->id) {
            $SQL_query .= " JOIN " . Material::_dbprefix() . "cms_data AS tD ON tD.pid = tM.id AND tD.fid = " . (int)$Loader->Unique_Field->id;
        }
        $SQL_query .= " WHERE tM.pid IN (" . implode(", ", $mtypes) . ") ";
        if ($Loader->Unique_Field->id) {
            $SQL_query .= " AND tD.value != '' ";
        }
        $SQL_query .= " GROUP BY ufield";
        $SQL_result = Material::_SQL()->get($SQL_query);
        $DATA = array();
        foreach ($SQL_result as $row2) {
            $row = new Material($row2);
            if ($attachments = $row->fields[$Loader->Image_Field->urn]->doRich()) {
                if (!is_array($attachments)) {
                    $attachments = array($attachments);
                }
                foreach ($attachments as $attachment) {
                    if ($attachment->id) {
                        $filename = array();
                        $filename[] = \SOME\Text::beautify(trim($row->{$Loader->Unique_Field->id ? $Loader->Unique_Field->urn : $Loader->ufid}));
                        $filename[] = trim($Loader->sep_string);
                        $filename[] = trim($attachment->filename);
                        $filename = array_filter($filename);
                        $filename = implode('', $filename);
                        $DATA[$attachment->file] = trim($filename);
                    }
                }
            }
            $row->rollback();
            unset($row);
        }
        if ($DATA) {
            $tmpname = tempnam(sys_get_temp_dir(), '');
            $z = new \SOME\ZipArchive();
            $z->open($tmpname, \SOME\ZipArchive::CREATE);
            foreach ($DATA as $key => $val) {
                $z->addFile($key, $val);
            }
            $z->close();
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $Loader->Material_Type->name . ' - ' . $Loader->Image_Field->name . '.zip"');
            echo file_get_contents($tmpname);
            exit;
        } else {
            return array('localError' => array(array('name' => 'INVALID', 'value' => 'loader', 'description' => Module::i()->view->_('IMAGES_NOT_FOUND'))));
        }

    } else {
        return array('localError' => array(array('name' => 'INVALID', 'value' => 'loader', 'description' => Module::i()->view->_('LOADER_HAS_NO_IMAGE_FIELD'))));
    }
}