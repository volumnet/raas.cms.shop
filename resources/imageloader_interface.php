<?php
namespace RAAS\CMS\Shop;
use \RAAS\CMS\Material;

ini_set('max_execution_time', 300);
$st = microtime(true);
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Загрузка картинок
    if (!$files) {
        return array('localError' => array(array('name' => 'MISSING', 'value' => 'files', 'description' => Module::i()->view->_('UPLOAD_FILES_REQUIRED'))));
    } else {
        $processFile = function($file) use (&$processFile) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $proceedFiles = false;
            switch ($ext) {
                case 'jpg': case 'jpeg': case 'png': case 'gif':
                    if ($type = getimagesize($file['tmp_name'])) {
                        if (($ext2 = image_type_to_extension($type[2])) != $ext) {
                            $file['name'] = str_replace('.' . $ext, '.' . $ext2, $file['name']);
                            $proceedFiles = true;
                        }
                    }
                    break;
                case 'zip':
                    $files = array();
                    $z = new \SOME\ZipArchive();
                    if ($z->open($file['tmp_name']) === true) {
                        for ($i = 0; $i < $z->numFiles; $i++) {
                            $tmpname = tempnam(sys_get_temp_dir(), '');
                            file_put_contents($tmpname, $z->getFromIndex($i));
                            $files[] = array('name' => $z->getNameIndex($i), 'tmp_name' => $tmpname);
                        }
                    }
                    $z->close();
                    foreach ($files as $f) {
                        $proceedFiles |= $processFile($f);
                    }
                    break;
            }
            return $proceedFiles;
        };

        $proceedFiles = false;
        foreach ($files as $file) {
            $proceedFiles |= $processFile($file);
        }
        if (!$proceedFiles) {
            return array('localError' => array(array('name' => 'INVALID', 'value' => 'files', 'description' => Module::i()->view->_('ALLOWED_FORMATS_JPG_JPEG_PNG_GIF_ZIP'))));
        }
    }
    return true;
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