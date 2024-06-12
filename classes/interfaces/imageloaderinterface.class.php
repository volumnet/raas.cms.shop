<?php
/**
 * Стандартный интерфейс загрузчика изображений
 */
declare(strict_types=1);

namespace RAAS\CMS\Shop;

use SOME\EventProcessor;
use SOME\Text;
use SOME\ZipArchive;
use RAAS\Application;
use RAAS\Attachment;
use RAAS\CMS\AbstractInterface;
use RAAS\CMS\Field;
use RAAS\CMS\Material;
use RAAS\CMS\MaterialTypeRecursiveCache;
use RAAS\CMS\Package;
use RAAS\CMS\Sub_Main as Package_Sub_Main;

/**
 * Стандартный интерфейс загрузчика изображений
 */
class ImageloaderInterface extends AbstractInterface
{
    use BatchFindTrait;

    /**
     * Режим отладки
     * @var bool
     */
    public $debug = false;

    /**
     * Загрузчик изображений
     * @var ImageLoader
     */
    protected $loader;

    /**
     * Ассоциации уникального поля с товарами
     * @var array<string[] Значение уникального поля => int[] ID# товаров>
     */
    public $assoc = [];

    public function __get($var)
    {
        switch ($var) {
            case 'loader':
                return $this->$var;
                break;
        }
    }


    /**
     * Конструктор класса
     * @param ImageLoader $loader Загрузчик изображений
     * @param Block|null $block Блок, для которого применяется интерфейс
     * @param Page|null $page Страница, для которой применяется интерфейс
     * @param array $get Поля $_GET параметров
     * @param array $post Поля $_POST параметров
     * @param array $cookie Поля $_COOKIE параметров
     * @param array $session Поля $_SESSION параметров
     * @param array $server Поля $_SERVER параметров
     * @param array $files Поля $_FILES параметров
     */
    public function __construct(
        ImageLoader $loader,
        Block $block = null,
        Page $page = null,
        array $get = [],
        array $post = [],
        array $cookie = [],
        array $session = [],
        array $server = [],
        array $files = []
    ) {
        $this->loader = $loader;
        $this->loader->loaderInterface = $this;
        parent::__construct($block, $page, $get, $post, $cookie, $session, $server, $files);
    }


    /**
     * Заглушка абстрактного метода обработки интерфейса
     * (не используется по причине наличия двух несвязанных функций upload и download)
     */
    public function process()
    {
    }


    /**
     * Загрузка прайса на сервер
     * @param array|null $files <pre><code>array<[
     *     'name' => string Наименование файла,
     *     'tmp_name' => string Путь к файлу,
     *     'type' => string MIME-тип файла,
     *     'size' => int Размер файла в байтах
     * ]></code></pre> Массив файлов для загрузки
     * @param bool $test Тестовый режим
     * @param bool $clear Очищать предыдущие изображения
     * @return array <pre><code>[
     *     'localError' ?=> array<[
     *         'name' => 'MISSING'|'INVALID' тип ошибки,
     *         'value' => string URN поля, на которое ссылается ошибка,
     *         'description' => string Человеко-понятное описание ошибки
     *     ]> ошибки при загрузке
     *     'log' ?=> array<[
     *         'time' => float Время, прошедшее с начала загрузки
     *         'text' => string Текст записи,
     *     ]> Лог выполнения,
     *     'ok' ?=> true Обработка завершена
     * ]</code></pre>
     */
    public function upload(array $files, bool $test = true, bool $clear = false): array
    {
        $st = microtime(true);
        $affectedMaterialsIds = $proceedFiles = $log = [];
        $files = array_values(array_filter($files, function ($file) {
            return is_file($file['tmp_name']);
        }));
        if (!$files) {
            return ['localError' => [[
                'name' => 'MISSING',
                'value' => 'files',
                'description' => Module::i()->view->_('UPLOAD_FILES_REQUIRED'),
            ]]];
        }

        foreach ($files as $file) {
            $proceedFiles = array_merge($proceedFiles, $this->processFile($file, $this->loader, $affectedMaterialsIds));
        }
        if (!$proceedFiles) {
            return ['localError' => [[
                'name' => 'INVALID',
                'value' => 'files',
                'description' => Module::i()->view->_('ALLOWED_FORMATS_JPG_JPEG_PNG_GIF_ZIP')
            ]]];
        }

        if ($clear) {
            $this->clear($this->loader, $log, $affectedMaterialsIds, $test, $st);
        }
        $this->applyFiles($proceedFiles, $this->loader, $log, $test, $st);
        if (!$test) {
            // 2022-12-27, AVS: вернул чистку в виде Field::clearLostAttachments()
            $this->loader->Image_Field->clearLostAttachments();
        }
        return ['log' => $log, 'ok' => true];
    }


    /**
     * Скачивает архив изображений в STDOUT
     * @return array|null <pre><code>[
     *     'localError' =>? array<[
     *         'name' => 'INVALID' Тип ошибки,
     *         'value' => 'loader' Поле ошибки,
     *         'description' => string Человеко-понятное описание ошибки
     *     ]>,
     *     'file' => string Путь к созданному файлу (только в режиме отладки)
     * ]</code></pre>
     */
    public function download()
    {
        $st = microtime(true);
        if ($this->loader->Image_Field->id) {
            $data = $this->exportData($this->loader);
            if ($data) {
                return $this->export($data, $this->loader);
            } else {
                return ['localError' => [[
                    'name' => 'INVALID',
                    'value' => 'loader',
                    'description' => Module::i()->view->_('IMAGES_NOT_FOUND'),
                ]]];
            }
        } else {
            return ['localError' => [[
                'name' => 'INVALID',
                'value' => 'loader',
                'description' => Module::i()->view->_('LOADER_HAS_NO_IMAGE_FIELD'),
            ]]];
        }
    }


    /**
     * Возвращает данные экспорта изображений
     * @param ImageLoader $loader Загрузчик изображений
     * @return array <pre><code>array<string[] Путь к файлу => экспортируемое имя файла></code></pre>
     */
    public function exportData(ImageLoader $loader): array
    {
        $imageField = $loader->Image_Field;
        $uniqueField = $loader->Unique_Field;
        $uniqueFieldId = $uniqueField->id;
        $mtypesIds = MaterialTypeRecursiveCache::i()->getSelfAndChildrenIds($loader->mtype);
        $sqlQuery = "SELECT tM.id, "
                  .         ($uniqueFieldId ? " tUniqueField.value " : $loader->ufid) . " AS ufield,
                            GROUP_CONCAT(tImageField.value ORDER BY tImageField.fii SEPARATOR '@@@') AS attachments_data
                      FROM " . Material::_tablename() . " AS tM
                      JOIN cms_data AS tImageField ON tImageField.fid = " . (int)$imageField->id . " AND tImageField.pid = tM.id";
        if ($uniqueFieldId) {
            $sqlQuery .= " JOIN " . Material::_dbprefix() . "cms_data AS tUniqueField ON tUniqueField.pid = tM.id AND tUniqueField.fid = " . (int)$uniqueFieldId;
        }
        $sqlQuery .= " WHERE tM.pid IN (" . implode(", ", $mtypesIds) . ") ";
        if ($uniqueFieldId) {
            $sqlQuery .= " AND tUniqueField.value != '' ";
        }
        $sqlQuery .= " GROUP BY ufield";
        $sqlResult = Material::_SQL()->get($sqlQuery);
        $sqlResult = array_map(function ($sqlRow) {
            $attachmentsData = explode('@@@', $sqlRow['attachments_data']);
            $attachmentsData = array_map(function ($attachmentDataVal) {
                return (array)json_decode($attachmentDataVal, true);
            }, $attachmentsData);
            $attachmentsIds = array_map(function ($attachmentData) {
                return (int)$attachmentData['attachment'];
            }, $attachmentsData);
            $sqlRow['attachments_data'] = $attachmentsData;
            $sqlRow['attachments_ids'] = $attachmentsIds;
            return $sqlRow;
        }, $sqlResult);
        // 2024-06-12, AVS: не используется
        // $affectedMaterialsIds = array_values(array_unique(array_map(function ($sqlRow) {
        //     return (int)$sqlRow['id'];
        // }, $sqlResult)));
        $affectedAttachmentsIds = array_values(array_unique(array_reduce(array_map(function ($sqlRow) {
            return (array)$sqlRow['attachments_ids'];
        }, $sqlResult), 'array_merge', [])));
        $affectedAttachmentsIds[] = 0; // Чтобы не было ошибки при SQL-запросе

        $tmpAttachments = Attachment::getSet(['where' => "id IN (" . implode(", ", $affectedAttachmentsIds) . ")"]);
        $attachments = [];
        foreach ($tmpAttachments as $attachment) {
            $attachments[trim((string)$attachment->id)] = $attachment;
        }

        $result = [];
        foreach ($sqlResult as $sqlRow) {
            $article = Text::beautify(trim($sqlRow['ufield']));
            foreach ((array)$sqlRow['attachments_ids'] as $i => $attachmentId) {
                $attachment = $attachments[$attachmentId];
                $ext = pathinfo($attachment->realname, PATHINFO_EXTENSION);
                $filename = trim($article . trim($loader->sep_string) . ($i + 1) . '.' . $ext);
                // @codeCoverageIgnoreStart
                // Не могу воспроизвести ситуацию, когда бы имена в строгом формате [артикул]_[число] пересекались
                while (in_array($filename, $result)) {
                    $filename .= $loader->sep_string . $attachment->id;
                }
                // @codeCoverageIgnoreEnd
                $result[$attachment->file] = trim($filename);
            }
        }
        return $result;
    }


    /**
     * Создает архив и выводит его в STDOUT
     * @param array $files <pre><code>array<string[] Путь к файлу => string Имя файла></code></pre>
     * @param ImageLoader $loader Загрузчик изображений
     * @return string Имя созданного файла (только в режиме отладки)
     */
    public function export($files, ImageLoader $loader)
    {
        $tmpname = tempnam(sys_get_temp_dir(), '');
        if (is_file($tmpname)) {
            unlink($tmpname); // Deprecated: ZipArchive::open(): Using empty file as ZipArchive is deprecated
        }
        $zip = new ZipArchive();
        $zip->open($tmpname, ZipArchive::CREATE);
        foreach ($files as $filepath => $filename) {
            $zip->addFile($filepath, $filename);
        }
        $zip->close();
        if ($this->debug) {
            return $tmpname;
        // @codeCoverageIgnoreStart
        } else {
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $loader->Material_Type->name . ' - ' . $loader->Image_Field->name . '.zip"');
            readfile($tmpname);
            unlink($tmpname);
            exit;
        }
        // @codeCoverageIgnoreEnd
    }


    /**
     * Обрабатывает один файл
     * @param array $file <pre><code>[
     *     'name' => string Наименование файла,
     *     'tmp_name' => string Путь к файлу
     * ]</code></pre> Данные файла
     * @param ImageLoader $loader Загрузчик
     * @param array $affectedMaterialsIds <pre><code>array<
     *     string[] ID# материала => int ID# материала
     * ></code></pre> Затронутые ID# материалов
     * @return array <pre><code>array<[
     *     'name' => string Наименование файла с реальным расширением,
     *     'originalName' => string Оригинальное имя файла
     *     'tmp_name' => string Путь к файлу,
     *     'type' => string Реальный MIME-тип файла,
     *     'materials' => Material[] Материалы, привязанные к файлу,
     * ]></code></pre> Данные обработанных файлов (распаковка архивов, привязка к материалам, нужные расширения)
     */
    public function processFile(array $file, ImageLoader $loader, array &$affectedMaterialsIds): array
    {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $result = [];
        switch ($ext) {
            case 'jpg':
            case 'jpeg':
            case 'png':
            case 'gif':
                if ($type = getimagesize($file['tmp_name'])) {
                    $file['originalName'] = $file['name'];
                    $file['type'] = image_type_to_mime_type($type[2]);
                    $filename = pathinfo($file['name'], PATHINFO_FILENAME);
                    $ext2 = image_type_to_extension($type[2]);
                    $file['name'] = $filename . $ext2;
                    $materials = $this->getItemsByUniqueField($loader, $filename);
                    if ($materials) {
                        $file['materials'] = $materials;
                        $result[] = $file;
                        foreach ($materials as $material) {
                            if ($materialId = (int)$material->id) {
                                if (!isset($affectedMaterialsIds[$materialId])) {
                                    $affectedMaterialsIds[trim((string)$materialId)] = (int)$materialId;
                                }
                            }
                        }
                    }
                }
                break;
            case 'zip':
                $files = [];
                $zip = new ZipArchive();
                if ($zip->open($file['tmp_name']) === true) {
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $tmpname = tempnam(sys_get_temp_dir(), '');
                        file_put_contents($tmpname, $zip->getFromIndex($i));
                        $files[] = ['name' => basename($zip->getNameIndex($i)), 'tmp_name' => $tmpname];
                    }
                    $zip->close();
                }
                foreach ($files as $file) {
                    $result = array_merge($result, $this->processFile($file, $loader, $affectedMaterialsIds));
                }
                break;
        }
        usort($result, function ($a, $b) {
            return strnatcasecmp($a['name'], $b['name']);
        });
        return $result;
    }


    /**
     * Очищает данные предыдущих изображений
     * @param ImageLoader $loader Загрузчик изображений
     * @param array $log <pre><code>array<[
     *     'time' => float Время, прошедшее с начала загрузки
     *     'text' => string Текст записи,
     * ]></code></pre> Лог выполнения
     * @param int[] $affectedMaterialsIds Массив ID# "затронутых" материалов
     * @param bool $test Тестовый режим
     * @param float $st UNIX-timestamp времени начала выполнения загрузки
     */
    public function clear(
        ImageLoader $loader,
        array &$log,
        array $affectedMaterialsIds = [],
        bool $test = true,
        float $st = 0
    ) {
        if (!$test) {
            if ($affectedMaterialsIds) {
                // Очищаем данные
                $sqlQuery = "DELETE tD
                               FROM " . Material::_dbprefix() . "cms_data AS tD
                              WHERE tD.fid = ?
                                AND tD.pid IN (" . implode(", ", $affectedMaterialsIds) . ")";
                $sqlBind = [(int)$loader->Image_Field->id];
                Material::_SQL()->query([$sqlQuery, $sqlBind]);
                Field::clearCache();
            }
            // 2022-12-15, AVS: САМИ ФАЙЛЫ НЕ ТРОГАЕМ!!! Т.К. ЕСЛИ НЕСКОЛЬКО ТОВАРОВ ССЫЛАЮТСЯ НА ОДИН ATTACHMENT,
            // ОН ПРОПАДЕТ У ВСЕХ!!!
            // 2022-12-27, AVS: вернул в виде Field::clearLostAttachments() в upload();
        } else {
            foreach ($affectedMaterialsIds as $materialId) {
                $material = new Material($materialId);
                $logEntry = [
                    'time' => (microtime(true) - $st),
                    'text' => sprintf(
                        Module::i()->view->_('LOG_DELETE_MATERIAL_IMAGES'),
                        Package_Sub_Main::i()->url . '&action=edit_material&id=' . $material->id,
                        $material->name
                    ),
                ];
                $log[] = $logEntry;
                EventProcessor::emit('imageLoaderLog', $this, $logEntry, false);
            }
        }
        $logEntry = [
            'time' => (microtime(true) - $st),
            'text' => Module::i()->view->_('LOG_OLD_MATERIAL_IMAGES_CLEARED')
        ];
        $log[] = $logEntry;
        EventProcessor::emit('imageLoaderLog', $this, $logEntry, false);
    }


    /**
     * Применяет файлы
     * @param array $files <pre><code>array<[
     *     'name' => string Наименование файла с реальным расширением,
     *     'originalName' => string Оригинальное имя файла
     *     'tmp_name' => string Путь к файлу,
     *     'type' => string Реальный MIME-тип файла,
     *     'materials' => Material[] Материалы, привязанные к файлу,
     * ]></code></pre> Данные файлов,
     * @param ImageLoader $loader Загрузчик изображений
     * @param array $log <pre><code>array<[
     *     'time' => float Время, прошедшее с начала загрузки
     *     'text' => string Текст записи,
     * ]></code></pre> Лог выполнения
     * @param bool $test Тестовый режим
     * @param float $st UNIX-timestamp времени начала выполнения загрузки
     */
    public function applyFiles(array $files, ImageLoader $loader, array &$log, bool $test = true, float $st = 0)
    {
        if (!$test) {
            if ($loader->Image_Field->preprocessor_classname || $loader->Image_Field->Preprocessor->id) {
                $filesToProcess = array_map(function ($fileData) {
                    return $fileData['tmp_name'];
                }, $files);
                if ($preprocessorClassname = $loader->Image_Field->preprocessor_classname) {
                    $preprocessor = new $preprocessorClassname($_GET, $_POST, $_COOKIE, $_SESSION, $_SERVER, $_FILES);
                    $preprocessor->process($filesToProcess);
                } elseif ($loader->Image_Field->Preprocessor->id) {
                    $loader->Image_Field->Preprocessor->process(['files' => $filesToProcess]);
                }
            }
        }
        $proceedFiles = [];
        foreach ($files as $file) {
            if ($file['materials']) {
                $proceedFiles[] = $this->applyFile($file, $loader, $log, $test, $st);
            }
        }
        if (!$test) {
            if ($loader->Image_Field->postprocessor_classname || $loader->Image_Field->Postprocessor->id) {
                $filesToProcess = array_map(function ($attachment) {
                    return $attachment->file;
                }, $proceedFiles);
                if ($postprocessorClassname = $loader->Image_Field->postprocessor_classname) {
                    $postprocessor = new $postprocessorClassname($_GET, $_POST, $_COOKIE, $_SESSION, $_SERVER, $_FILES);
                    $postprocessor->process($filesToProcess);
                } elseif ($loader->Image_Field->Postprocessor->id) {
                    $loader->Image_Field->Postprocessor->process(['files' => $filesToProcess]);
                }
            }
        }
    }


    /**
     * Применяет файлы
     * @param array $file <pre><code>[
     *     'name' => string Наименование файла с реальным расширением,
     *     'originalName' => string Оригинальное имя файла
     *     'tmp_name' => string Путь к файлу,
     *     'type' => string Реальный MIME-тип файла,
     *     'materials' => Material[] Материалы, привязанные к файлу,
     * ]</code></pre> Данные файла,
     * @param ImageLoader $loader Загрузчик изображений
     * @param array $log <pre><code>array<[
     *     'time' => float Время, прошедшее с начала загрузки
     *     'text' => string Текст записи,
     * ]></code></pre> Лог выполнения
     * @param bool $test Тестовый режим
     * @param float $st UNIX-timestamp времени начала выполнения загрузки
     * @return Attachment Созданное вложение
     */
    public function applyFile(array $file, ImageLoader $loader, array &$log, bool $test = true, float $st = 0)
    {
        $att = new Attachment();
        // 2023-10-16, AVS: сделал присвоения отдельными вызовами, чтобы не глючило с предыдущей версией SOME
        // (там свойства в конструкторе устанавливаются в обход __set)
        $att->upload = $file['tmp_name'];
        $att->filename = $file['name'];
        $att->mime = $file['type'];
        $att->parent = $loader->Image_Field;
        $att->image = true;
        $att->copy = true;
        if ($maxSize = (int)Package::i()->registryGet('maxsize')) {
            $att->maxWidth = $att->maxHeight = $maxSize;
        }
        if ($tnSize = (int)Package::i()->registryGet('tnsize')) {
            $att->tnsize = $tnSize;
        }
        if (!$test) {
            $att->commit();
        }
        $json = json_encode(['vis' => 1, 'name' => '', 'description' => '', 'attachment' => (int)$att->id]);
        foreach ($file['materials'] as $materialId) {
            $field = $loader->Image_Field->deepClone();
            $material = new Material($materialId);
            $field->Owner = $material;
            if (!$test) {
                $field->addValue($json);
            }
            $logEntry = [
                'time' => (microtime(true) - $st),
                'text' => sprintf(
                    Module::i()->view->_('LOG_ADD_MATERIAL_IMAGE'),
                    '/' . Package::i()->filesURL . '/' . $att->realname,
                    $att->filename,
                    $file['originalName'],
                    Package_Sub_Main::i()->url . '&action=edit_material&id=' . $material->id,
                    $material->name
                )
            ];
            $log[] = $logEntry;
            EventProcessor::emit('imageLoaderLog', $this, $logEntry, false);
        }
        return $att;
    }


    /**
     * Поиск товара по уникальному полю
     * @param ImageLoader $loader Загрузчик
     * @param string $text Значение поля
     * @return Material[] Массив найденных товаров
     */
    public function getItemsByUniqueField(ImageLoader $loader, string $text): array
    {
        if ($ufid = $loader->ufid) {
            // Получим ассоциации
            if (!$this->assoc) {
                $sqlBind = [];
                $uniqueFieldId = $loader->Unique_Field->id;
                $sqlQuery = "SELECT tM.id,
                                    " . ($uniqueFieldId ? "tD.value" : "tM." . $ufid) . " AS imageloader_unique_field
                               FROM " . Material::_tablename() . " AS tM";
                if ($uniqueFieldId) {
                    $sqlQuery .= " JOIN " . Material::_dbprefix() . "cms_data AS tD ON tD.pid = tM.id AND tD.fid = ?";
                    $sqlBind[] = (int)$uniqueFieldId;
                }
                $mtypesIds = MaterialTypeRecursiveCache::i()->getSelfAndChildrenIds($loader->mtype);
                if (!$mtypesIds) {
                    $mtypesIds = [0];
                }
                $sqlQuery .= " WHERE tM.pid IN (" . implode(", ", $mtypesIds) . ")
                            GROUP BY tM.id";
                $sqlResult = Material::_SQL()->get([$sqlQuery, $sqlBind]);
                foreach ($sqlResult as $sqlRow) {
                    $this->assoc[Text::beautify($sqlRow['imageloader_unique_field'])][] = (int)$sqlRow['id'];
                }
            }

            if ($text = Text::beautify($text)) {
                $result = [];
                foreach ($this->assoc as $article => $materialsIds) {
                    $rx = '/^' . preg_quote($article) . '($|' . preg_quote($loader->sep_string) . ')/umis';
                    if (preg_match($rx, $text)) {
                        foreach ($materialsIds as $materialId) {
                            $result[] = new Material($materialId);
                        }
                        break;
                    }
                }
                return $result;
            }
        }
        return [];
    }
}
