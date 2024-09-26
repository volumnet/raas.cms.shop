<?php
/**
 * Стандартный интерфейс загрузчика прайсов
 */
declare(strict_types=1);

namespace RAAS\CMS\Shop;

use SOME\CSV;
use SOME\EventProcessor;
use SOME\SOME;
use SOME\Text;
use RAAS\Application;
use RAAS\Attachment;
use RAAS\Exception;
use RAAS\CMS\AbstractInterface;
use RAAS\CMS\Block;
use RAAS\CMS\Field;
use RAAS\CMS\Material;
use RAAS\CMS\Material_Field;
use RAAS\CMS\Material_Type;
use RAAS\CMS\MaterialTypeRecursiveCache;
use RAAS\CMS\Package;
use RAAS\CMS\Page;
use RAAS\CMS\Page_Field;
use RAAS\CMS\PageRecursiveCache;
use RAAS\CMS\Snippet;
use RAAS\CMS\Sub_Main as Package_Sub_Main;

/**
 * Стандартный интерфейс загрузчика прайсов
 * @property-read PriceLoader $loader Загрузчик прайсов
 */
class PriceloaderInterface extends AbstractInterface
{
    use BatchDeleteTrait;
    use InheritPageTrait;

    /**
     * Максимальное время загрузки прайса
     * @deprecated
     */
    const UPLOAD_MAX_TIME = 3600;

    /**
     * Максимальное время выгрузки прайса
     * @deprecated
     */
    const DOWNLOAD_MAX_TIME = 900;

    /**
     * Загрузчик прайсов
     * @var PriceLoader
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
     * @param PriceLoader $loader Загрузчик прайсов
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
        PriceLoader $loader,
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
     * @param string $file Путь к загружаемому файлу
     * @param string $type Тип (расширение) загружаемого файла
     * @param Page $page Страница, в которую загружаем
     * @param bool $test Тестовый режим
     * @param int $clear Очищать предыдущие материалы и/или страницы (варианты:
     *                       PriceLoader::DELETE_PREVIOUS_MATERIALS_NONE - не очищать
     *                       PriceLoader::DELETE_PREVIOUS_MATERIALS_MATERIALS_ONLY - очищать только материалы
     *                       PriceLoader::DELETE_PREVIOUS_MATERIALS_MATERIALS_AND_PAGES - очищать материалы и страницы
     *                   )
     * @param int $rows Сколько строк пропускать
     * @param int $cols Сколько столбцов пропускать
     * @return array <pre><code>[
     *     'localError' ?=> array<[
     *         'name' => 'MISSING'|'INVALID' тип ошибки,
     *         'value' => string URN поля, на которое ссылается ошибка,
     *         'description' => string Человеко-понятное описание ошибки
     *     ]> ошибки при загрузке
     *     'log' ?=> array<[
     *         'time' => float Время, прошедшее с начала загрузки
     *         'text' => string Текст записи,
     *         'row' ?=> int К какой строке относится запись
     *                       (относительно смещений, начиная с 0),
     *         'realrow' ?=> int К какой строке относится запись
     *                           (абсолютно, без учета смещений,
     *                           начиная с 0),
     *     ]> Лог выполнения,
     *     'raw_data' ?=> array<array<string>> Массив сырых данных,
     *     'ok' ?=> true Обработка завершена
     * ]</code></pre>
     */
    public function upload(
        string $file,
        string $type,
        Page $page,
        bool $test = true,
        int $clear = 0,
        int $rows = 0,
        int $cols = 0
    ): array {
        $st = microtime(true);
        // Загрузка прайса
        $affectedPagesIds = $affectedMaterialsIds = $log = $rawData = [];
        if (!$file || !is_file($file)) {
            return ['localError' => [[
                'name' => 'MISSING',
                'value' => 'file',
                'description' => Module::i()->view->_('UPLOAD_FILE_REQUIRED')
            ]]];
        }
        if (is_file($file)) {
            try {
                $data = $this->parse($file, $type);
                $data = $this->adjustData($data, $rows, $cols);
                if (!$data || ((count($data) == 1) && (count(array_filter($data[0])) == 1))) {
                    throw new Exception(Module::i()->view->_('ERR_EMPTY_FILE'));
                }
                $this->processData(
                    $this->loader,
                    $data,
                    $page,
                    $affectedMaterialsIds,
                    $affectedPagesIds,
                    $log,
                    $rawData,
                    $test,
                    $rows,
                    $cols,
                    $st
                );
            } catch (Exception $e) {
                return ['localError' => [[
                    'name' => 'INVALID',
                    'value' => 'file',
                    'description' => $e->getMessage(),
                ]]];
            }
        }

        if ($clear) {
            $this->clear(
                $this->loader,
                $page,
                $log,
                $affectedMaterialsIds,
                $affectedPagesIds,
                $clear,
                $test,
                $st
            );
        }
        return [
            'log' => $log,
            'raw_data' => $rawData,
            'affectedMaterialsIds' => $affectedMaterialsIds,
            'affectedPagesIds' => $affectedPagesIds,
            'ok' => true
        ];
    }



    /**
     * Выгрузка прайса с сервера
     * @param Page $page Страница, из которой выгружаем
     * @param int $rows Сколько строк пропускать
     * @param int $cols Сколько столбцов пропускать
     * @param 'csv'|'xls'|'xlsx' $type Формат, в котором выгружаем
     * @param string $encoding Кодировка для формата CSV, в которой выгружаем (совместимо с iconv)
     * @param bool $debug Дебаг-режим (не выходит по exit)
     * @return string|null В debug-режиме возвращает текст файла
     */
    public function download(
        Page $page = null,
        int $rows = 0,
        int $cols = 0,
        string $type = 'xls',
        string $encoding = 'UTF-8',
        bool $debug = false
    ) {
        // 2024-06-25, AVS: заменил на строку, т.к. PHP 7.4.33 выдает фатальную ошибку
        // В PHP8 ошибка не наблюдается, тестами не выявляется
        ini_set('max_execution_time', '900');
        if (!($page->id ?? null)) {
            $page = $this->loader->Page;
        }
        $converter = PriceloaderDataConverter::spawn($type);
        $data = $this->exportData($this->loader, $page, $rows);
        $data = $this->adjustData($data, ($rows > 1) ? (($rows - 1) * -1) : 0, $cols * -1);
        $filename = date('Y-m-d') . ' - ' . $page->name . '.' . $converter->getExtension();
        $mime = $converter->getMime();
        $text = $converter->export($data, $page, $rows, $cols, $encoding);
        if ($debug) {
            return $text;
        // @codeCoverageIgnoreStart
        } else {
            while (ob_get_level()) {
                ob_end_clean();
            }
            header('Content-Type: ' . $mime . '; name="' . $filename . '"');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            echo $text;
            exit;
        }
        // @codeCoverageIgnoreEnd
    }


    // Начало независимых функций

    /**
     * Приводит данные к нужному виду (применяет смещение, убирает пустые строки)
     * @param array<array<string>> Входная таблица данных
     * @param int $rows Сколько строк пропускать (отрицательные значения для добавления)
     * @param int $cols Сколько столбцов пропускать (отрицательные значения для добавления)
     * @return array<array<string>>
     */
    public function adjustData(array $data, $rows = 0, $cols = 0)
    {
        if ($cols > 0) {
            $data = array_map(function ($x) use ($cols) {
                return array_slice($x, $cols);
            }, $data);
        } else {
            $data = array_map(function ($row) use ($cols) {
                return array_merge(array_fill(0, $cols * -1, ''), (array)$row);
            }, $data);
        }
        if ($rows >= 0) {
            if ($rows > 0) {
                $data = array_slice($data, $rows);
            }
            $data = array_filter($data, function ($row) {
                return count(array_filter($row, 'trim'));
            }); // Фильтруем пустые строки
        } else {
            $colcounter = max(array_map(function ($x) {
                return count($x);
            }, $data));
            $dummy = array_fill(0, $colcounter, '');
            $data = array_merge(array_fill(0, $rows * -1, $dummy), $data);
        }
        $data = array_values($data);
        return $data;
    }


    /**
     * Получает номер уникальной колонки
     * @param PriceLoader $loader Загрузчик прайсов
     * @return int|null Номер уникальной колонки, начиная с 0, либо null, если не найдена
     */
    public function getUniqueColumnIndex(PriceLoader $loader)
    {
        if ($loader->ufid) {
            foreach ($loader->columns as $i => $col) {
                if ($col->fid == $loader->ufid) {
                    return $i;
                }
            }
        }
        return null;
    }


    /**
     * Проверяет, относится ли строка к товару
     * @param array<mixed> $dataRow Входная строка данных
     * @return bool
     */
    public function isItemDataRow(array $dataRow = [])
    {
        // 2022-07-05, AVS: заменил функцию проверки (было trim),
        // чтобы поля с нулями тоже учитывались
        $filledCellsCounter = count(array_filter($dataRow, function ($x) {
            return trim((string)$x) !== '';
        }));
        if ($filledCellsCounter > 1) {
            return true;
        } elseif (($this->loader->cats_usage == PriceLoader::CATS_USAGE_DONT_USE) &&
            ($filledCellsCounter == 1)
        ) {
            return true;
        }
        return false;
    }


    /**
     * Применяет к ячейке данных callback-преобразования
     * @param PriceLoader_Column $col Колонка загрузчика прайсов
     * @param mixed $data Исходные данные
     * @param int|null $uniqueIndex Индекс (начиная с 0) уникальной колонки,
     *                              null если нет {@deprecated Непонятно зачем}
     * @return mixed
     */
    public function convertCell(PriceLoader_Column $col, $data/*, $uniqueIndex = null*/)
    {
        if (is_string($data)) {
            $data = trim((string)$data);
        }
        if ($f = $col->Callback) {
            $data = $f($data);
        }
        if (/*(!$uniqueIndex || ($j != $uniqueIndex)) && */$col->isNative) {
            // 2018-10-12, AVS: Непонятно, зачем uniqueIndex,
            // если даже уникальное нативное поле, его тоже проходим
            // Если нативное неуникальное поле
            if (in_array($col->fid, ['vis'])) {
                $data = (int)(bool)$data;
            } elseif (in_array($col->fid, ['priority'])) {
                $data = (int)$data;
            }
        } elseif (is_array($data)) {
            // Если множественное поле (только в результате callback'а)
            foreach ($data as $k => $val) {
                if ($val instanceof SOME) {
                    $data[$k] = (int)$val->id;
                } elseif (!is_object($val) && !is_array($val)) {
                    // 2020-02-10, AVS: добавили fallback без fromRich,
                    // на случай если данные грузятся явно
                    if ($valFR = $col->Field->fromRich(trim((string)$val))) {
                        $data[$k] = $valFR;
                    } else {
                        $data[$k] = $val;
                    }
                }
            }
        } else {
            // 2020-02-10, AVS: добавили fallback без fromRich,
            // на случай если данные грузятся явно
            if ($valFR = $col->Field->fromRich(trim((string)$data))) {
                $data = $valFR;
            } else {
                $data = trim((string)$data);
            }
        }
        return $data;
    }


    /**
     * Поиск товара по уникальному полю
     * @param PriceLoader $loader Загрузчик
     * @param string|array $text Значение поля - array сделано для совместимости с другими загрузчиками
     * @return Material[] Массив найденных товаров
     */
    public function getItemsByUniqueField(PriceLoader $loader, $text): array
    {
        if ($ufid = $loader->ufid) {
            // Получим ассоциации
            if (!$this->assoc) {
                $sqlBind = [];
                $uniqueFieldId = $loader->Unique_Field->id;
                $sqlQuery = "SELECT tM.id,
                                    " . ($uniqueFieldId ? "tD.value" : "tM." . $ufid) . " AS priceloader_unique_field
                               FROM " . Material::_tablename() . " AS tM";
                if ($uniqueFieldId) {
                    $sqlQuery .= " JOIN " . Material::_dbprefix() . "cms_data AS tD ON tD.pid = tM.id AND tD.fid = ?";
                    $sqlBind[] = (int)$uniqueFieldId;
                }
                $mtypesIds = MaterialTypeRecursiveCache::i()->getSelfAndChildrenIds($loader->mtype);
                // @codeCoverageIgnoreStart
                // В текущем окружении не могу проверить отсутствие типов материалов
                if (!$mtypesIds) {
                    $mtypesIds = [0];
                }
                // @codeCoverageIgnoreEnd
                $sqlQuery .= " WHERE tM.pid IN (" . implode(", ", $mtypesIds) . ")
                            GROUP BY tM.id";
                $sqlResult = Material::_SQL()->get([$sqlQuery, $sqlBind]);
                foreach ($sqlResult as $sqlRow) {
                    $this->assoc[trim((string)$sqlRow['priceloader_unique_field'])][] = (int)$sqlRow['id'];
                }
            }

            if (trim((string)$text) && isset($this->assoc[trim((string)$text)])) {
                $result = [];
                foreach ((array)$this->assoc[trim((string)$text)] as $materialId) {
                    $result[] = new Material($materialId);
                }
                return $result;
            }
        }
        return [];
    }


    /**
     * Поиск товара по всем полям
     * @param PriceLoader $loader Загрузчик прайсов
     * @param array<mixed> $dataRow Строка данных
     * @return array<Material> Массив найденных товаров
     */
    public function getItemsByEntireRow(PriceLoader $loader, array $dataRow = [])
    {
        $sqlFrom = [Material::_tablename() . " AS tM"];
        $sqlWhere = [];
        $sqlFromBind = $sqlWhereBind = [];
        for ($i = 0; $i < max(count($dataRow), count($loader->columns)); $i++) {
            $col = $loader->columns[$i];
            if (!is_array($dataRow[$i]) && trim((string)$dataRow[$i])) {
                $tmpWhere = '';
                if ($fid = (int)$col->Field->id) {
                    $sqlFrom[] = Material::_dbprefix() . "cms_data AS tD" . $fid
                               . " ON tD" . $fid . ".pid = tM.id "
                               . " AND tD" . $fid . ".fid = ?";
                    $sqlFromBind[] = $fid;
                    $tmpWhere = " TRIM(tD" . $fid . ".value) ";
                } elseif ($fid = $col->fid) {
                    $tmpWhere = " TRIM(tM." . $fid . ") ";
                }
                if ($tmpWhere) {
                    $sqlWhere[] = $tmpWhere . " = ? ";
                    $sqlWhereBind[] = trim((string)$dataRow[$i]);
                }
            }
        }
        if ($sqlWhere) {
            $sqlQuery = "SELECT tM.*
                           FROM " . implode(" JOIN ", $sqlFrom)
                      . " WHERE " . implode(" AND ", $sqlWhere)
                      . " ORDER BY tM.id";
            $sqlBind = array_merge($sqlFromBind, $sqlWhereBind);
            $sqlResult = Material::getSQLSet([$sqlQuery, $sqlBind]);
            if ($sqlResult) {
                return $sqlResult;
            }
        }
        return [];
    }


    /**
     * Создает материал (без коммита) согласно настройкам загрузчика
     * @param PriceLoader $loader Загрузчик прайсов
     * @return Material Созданный материал
     */
    public function createItem(PriceLoader $loader)
    {
        $row = new Material();
        $row->pid = $loader->Material_Type->id;
        $row->vis = 1;
        return $row;
    }


    /**
     * Применяет нативное поле
     * @param PriceLoader_Column $col Колонка загрузчика прайсов
     * @param Material $item Материал, к которому нужно применить поля
     * @param mixed $data Исходные данные
     * @param bool $isUnique Поле уникальное
     * @return Material Возврат материала из поля $item
     */
    public function applyNativeField(PriceLoader_Column $col, Material $item, $data, $isUnique = false)
    {
        if ($col->isNative && !($isUnique && $item->id)) {
            $data = trim((string)$data);
            // У существующих товаров не обновляем уникальное поле
            $fid = $col->fid;
            if (in_array($fid, ['vis', 'priority'])) {
                $item->$fid = (int)$data;
            } elseif ($data || !in_array($fid, ['name', 'urn'])) {
                $item->$fid = $data;
            }
        }
        if (!$item->id && !$item->urn && $isUnique) { // 2015-11-20, AVS: добавили URN по артикулу
            $item->urn = Text::beautify($data, '-');
        }
        return $item;
    }


    /**
     * Проверяет и при необходимости размещает материал на странице
     * @param Material $item Материал для размещения
     * @param Page $root Корень загрузки прайса
     * @param Page $context Текущая категория загрузки прайса
     * @param bool $new Товар только что создан
     */
    public function checkAssoc(Material $item, Page $root, Page $context, $new)
    {
        if ($item->id &&
            !$item->material_type->global_type &&
            $context->id &&
            ($new || ($context->id != $root->id)) &&
            !in_array($context->id, $item->pages_ids)
        ) {
            Material::_SQL()->add(
                Material::_dbprefix() . "cms_materials_pages_assoc",
                ['id' => (int)$item->id, 'pid' => (int)$context->id]
            );
        }
    }


    /**
     * Применяет произвольное поле
     * @param PriceLoader_Column $col Колонка загрузчика прайсов
     * @param Material $item Материал, к которому нужно применить поля
     * @param mixed $data Исходные данные
     * @param bool $new Материал новый
     * @param bool $isUnique Поле уникальное
     * @return string|null URN затрагиваемого поля (если изменено),
     *                         либо null, если ничего не затронуто
     */
    public function applyCustomField(PriceLoader_Column $col, Material $item, $data, bool $new, bool $isUnique)
    {
        if (!$col->Field->id) {
            return null;
        }
        $preprocessor = $col->Field->preprocessor_classname ?: $col->Field->Preprocessor;
        $postprocessor = $col->Field->postprocessor_classname ?: $col->Field->Postprocessor;
        $field = $col->Field->deepClone();
        $field->Owner = $item;
        // 2024-04-17, AVS: убрал проверку if (!$field->id), т.к. это аналогично той, что проводится в начале метода
        $affectsField = false;
        $oldVal = [];
        if (!$new) {
            $oldVal = $field->getValues(true);
        }
        // 2015-06-01, AVS: добавляем поддержку множественных значений:
        $dataArr = (array)$data;
        // 2015-06-01, AVS: добавляем || $new , чтобы у новых товаров артикул
        // тоже заполнялся
        // 2016-02-01, AVS: закомментировали trim((string)$data), т.к. пустые значения
        // тоже должны вставляться
        if (!$isUnique || $new) {
            if ($isFileField = in_array($field->datatype, ['file', 'image'])) {
                $mediaAction = $col->Parent->media_action;
                $isImage = ($field->datatype == 'image');
                if (($mediaAction == PriceLoader::MEDIA_FIELDS_APPEND_TO_NEW_ONLY) &&
                    !$new
                ) {
                    return null;
                }
                if (($mediaAction == PriceLoader::MEDIA_FIELDS_APPEND_IF_EMPTY) &&
                    $oldVal
                ) {
                    return null;
                }
                if ($mediaAction == PriceLoader::MEDIA_FIELDS_REPLACE) {
                    foreach ($oldVal as $att) {
                        Attachment::delete($att);
                    }
                    $field->deleteValues();
                }

                $addedAttachments = [];
                $dataArr = $this->convertMediaData($dataArr, $field, $addedAttachments, $preprocessor, $postprocessor);
                foreach ($dataArr as $val) {
                    $field->addValue($val);
                    $affectsField = true;
                }
            } else {
                if ($dataArr != $oldVal) {
                    $field->deleteValues();
                    foreach ($dataArr as $val) {
                        if ($val !== null) {
                            $field->addValue($val);
                            $affectsField = true;
                        }
                    }
                }
            }
        }

        if ($affectsField) {
            return $col->Field->urn;
        }
        return null;
    }


    /**
     * Конвертирует данные для медиа-поля
     * @param array $data Данные для поля
     * @param Field $field Поле, к которому относятся данные
     * @param array $addedAttachments Добавленные вложения
     * @param Snippet|string|null $preprocessor Сниппет или класс препроцессора поля
     * @param Snippet|string|null $postprocessor Сниппет или класс постпроцессора поля
     */
    public function convertMediaData(
        array $data,
        Field $field,
        array &$addedAttachments,
        $preprocessor = null,
        $postprocessor = null
    ) {
        $newDataArr = [];
        $addedAttachments = [];
        foreach ($data as $val) {
            if (!is_string($val) || !$val) {
                continue;
            }
            $basename = basename($val);
            $tempname = sys_get_temp_dir() . '/' . $basename;
            if (stristr($val, '://')) {
                $text = @file_get_contents($val); // Для подавления ошибок несуществующих файлов
                if (!$text) {
                    continue;
                }
                file_put_contents($tempname, $text);
            } elseif (is_file(Application::i()->baseDir . $val)) {
                copy(Application::i()->baseDir . $val, $tempname);
            }
            if (is_file($tempname)) {
                if ($preprocessor) {
                    if (is_string($preprocessor) && class_exists($preprocessor)) {
                        $preprocessorInterface = new $preprocessor($_GET, $_POST, $_COOKIE, $_SESSION, $_SERVER, $_FILES);
                        $preprocessorInterface->process([$tempname]);
                    } elseif (($preprocessor instanceof Snippet) && $preprocessor->id) {
                        $preprocessor->process(['files' => [$tempname]]);
                    }
                }
                $mime = mime_content_type($tempname);
                $att = Attachment::createFromFile(
                    $tempname,
                    $field,
                    (int)Package::i()->registryGet('maxsize'),
                    (int)Package::i()->context->registryGet('tn'),
                    $mime
                );
                if ($postprocessor) {
                    if (is_string($postprocessor) && class_exists($postprocessor)) {
                        $postprocessorInterface = new $postprocessor($_GET, $_POST, $_COOKIE, $_SESSION, $_SERVER, $_FILES);
                        $postprocessorInterface->process([$att->file]);
                    } elseif (($postprocessor instanceof Snippet) && $postprocessor->id) {
                        $postprocessor->process(['files' => [$att->file]]);
                    }
                }
                unlink($tempname);
                $addedAttachments[] = $att;
                $val = json_encode([
                    'vis' => 1,
                    'name' => '',
                    'description' => '',
                    'attachment' => $att->id
                ]);
            }
            $newDataArr[] = $val;
        }
        return $newDataArr;
    }


    /**
     * Проверяет, относится ли строка к странице
     * @param array<mixed> $dataRow Входная строка данных
     * @return bool
     */
    public function isPageDataRow(array $dataRow = [])
    {
        if ($this->loader->cats_usage == PriceLoader::CATS_USAGE_DONT_USE) {
            return false;
        }
        // 2022-07-05, AVS: заменил функцию проверки (было trim),
        // чтобы поля с нулями тоже учитывались
        $filledCellsCounter = count(array_filter($dataRow, function ($x) {
            return trim((string)$x) !== '';
        }));
        return $filledCellsCounter == 1;
    }


    /**
     * Разбирает строку категории
     * @param PriceLoader $loader Загрузчик прайсов
     * @param array<mixed> $row Строка данных
     * @return [int Шаг смещения, string Наименование категории]
     */
    public function parseCategoryRow(PriceLoader $loader, array $row)
    {
        $filteredCells = array_filter($row, 'trim');
        $filteredCellsKeys = array_keys($filteredCells);
        $step = array_shift($filteredCellsKeys);
        $name = $filteredCells[$step];
        if ($loader->catalog_offset) {
            $step = 0;
            if (preg_match('/^\\s+/i', $name, $regs)) {
                $step = strlen($regs[0]);
            }
        }
        return [(int)$step, trim((string)$name)];
    }


    /**
     * Усечение backtrace
     * @param array<int[] Смещение => Page категория> $backtrace Текущая навигация
     * @param int $level Смещение, до которого (включительно) нужно усечь backtrace
     * @return array<int[] Смещение => Page категория> Новая навигация
     */
    public function cropBacktrace(array $backtrace = [], $level = 0)
    {
        $keys = array_keys($backtrace);
        foreach ($keys as $key) {
            if ($key >= $level) {
                unset($backtrace[$key]);
            }
        }
        return $backtrace;
    }


    /**
     * Возвращает последнюю категорию из backtrace
     * @param Page $root Корневая категория
     * @param array<int[] Смещение => Page категория> $backtrace Текущая навигация
     * @return Page
     */
    public function lastCat($root, array $backtrace = [])
    {
        if ($backtrace) {
            $temp = array_reverse($backtrace);
            $temp = array_values($temp);
            return $temp[0];
        }
        return $root;
    }


    /**
     * Ищет страницу с заданным именем в заданном контексте
     * @param Page $context Контекст, в котором ищется страница
     * @param string $name Наименование страницы
     * @return Page|null
     */
    public function getPage(Page $context, $name)
    {
        $sqlResult = Page::getSet(['where' => [
            "pid = " . (int)$context->id,
            "name = '" . Page::_SQL()->real_escape_string($name) . "'"
        ]]);
        if ($sqlResult) {
            return $sqlResult[0];
        }
        return null;
    }


    /**
     * Создает страницу
     * @param Page $context Текущий контекст (родительская страница)
     * @param string $name Наименование новой страницы
     * @param bool $test Тестовый режим
     * @return Page Новая страница
     */
    public function createPage(Page $context, $name, $test = true)
    {
        $arr = [
            'pid' => (int)$context->id,
            'vis' => 1,
            'name' => $name,
        ];
        $context = new Page($arr);
        $this->inheritPageNativeFields($context);
        if (!$test) {
            // 2020-02-10, AVS: для ускорения не обновляем связанные страницы
            $context->dontUpdateAffectedPages = true;
            $context->commit();
            $this->inheritPageCustomFields($context);
            $context->rollback();
        }
        return $context;
    }


    /**
     * Возвращает последний уровень смещения из backtrace
     * @param array<int[] Смещение => Page категория> $backtrace Текущая навигация
     * @return int|null null, если смещения нет
     */
    public function lastLevel(array $backtrace = [])
    {
        if ($backtrace) {
            $temp = array_reverse($backtrace, true);
            $temp = array_keys($temp);
            return $temp[0];
        }
        return null;
    }


    /**
     * Записывает в лог (в тестовом режиме) данные об удалении полей и вложений
     * @param array<[
     *            'time' => float Время, прошедшее с начала загрузки
     *            'text' => string Текст записи,
     *        ]> $log Лог выполнения
     * @param int[] $fieldsToClearIds Массив ID# полей для удаления
     * @param int[] $attachmentsToClearIds Массив ID# вложений для удаления
     * @param float $st UNIX-timestamp времени начала выполнения загрузки
     */
    public function logDeleteFieldsAndAttachments(
        array &$log,
        array $fieldsToClearIds = [],
        array $attachmentsToClearIds = [],
        $st = 0
    ) {
        foreach ($fieldsToClearIds as $fieldId) {
            $field = new Material_Field($fieldId);
            $logEntry = [
                'time' => (microtime(true) - $st),
                'text' => sprintf(
                    Module::i()->view->_('LOG_DELETE_FIELDS'),
                    $field->name
                )
            ];
            $log[] = $logEntry;
            EventProcessor::emit('priceLoaderLog', $this, $logEntry, false);
        }
        foreach ($attachmentsToClearIds as $attachmentId) {
            $attachment = new Attachment($attachmentId);
            $logEntry = [
                'time' => (microtime(true) - $st),
                'text' => sprintf(
                    Module::i()->view->_('LOG_DELETE_ATTACHMENTS'),
                    '/' . Package::i()->filesURL . '/' . $attachment->realname,
                    $attachment->realname
                )
            ];
            $log[] = $logEntry;
            EventProcessor::emit('priceLoaderLog', $this, $logEntry, false);
        }
    }

    // Конец независимых функций


    /**
     * Разбирает данные из файла
     * @param string $file Файл для разбора
     * @param string $type Тип (расширение) загружаемого файла
     * @return array<array<string>> Таблица данных
     */
    public function parse($file, $type)
    {
        $converter = PriceloaderDataConverter::spawn($type);
        $data = $converter->load($file);
        EventProcessor::emit('priceLoaderDataParsed', $this, []);
        return $data;
    }


    /**
     * Применяет к строке данных callback-преобразования
     * @param PriceLoader $loader Загрузчик прайсов
     * @param array<mixed> $dataRow Входная строка данных
     * @param int|null $uniqueIndex Индекс (начиная с 0) уникальной колонки,
     *                              null если нет {@deprecated Непонятно зачем}
     * @return array<mixed>
     */
    public function convertRow(PriceLoader $loader, array $dataRow/*, $uniqueIndex = null*/)
    {
        $columns = $loader->columns;
        $dataRow = array_slice($dataRow, 0, count($columns));
        for ($j = 0; $j < count($dataRow); $j++) {
            $dataRow[$j] = $this->convertCell($columns[$j], $dataRow[$j]/*, $uniqueIndex*/);
        }
        return $dataRow;
    }


    /**
     * Получает массив совпадающих материалов либо по уникальному полю,
     * либо по всей строке данных
     * @param PriceLoader $loader Загрузчик прайсов
     * @param array $dataRow Входная строка данных
     * @param int|null $uniqueIndex Индекс (начиная с 0) уникальной колонки, null если нет
     * @return Material[] Массив найденных товаров
     */
    public function getItems(PriceLoader $loader, array $dataRow, $uniqueIndex = null)
    {
        $itemSet = [];
        if ($uniqueIndex !== null) {
            // 2021-10-08, AVS: добавили возможность нескалярных значений
            if (!is_scalar($dataRow[$uniqueIndex]) ||
                trim((string)$dataRow[$uniqueIndex])
            ) {
                $itemSet = $this->getItemsByUniqueField(
                    $loader,
                    (
                        is_scalar($dataRow[$uniqueIndex]) ?
                        trim((string)$dataRow[$uniqueIndex]) :
                        $dataRow[$uniqueIndex]
                    )
                );
            }
        } else {
            $itemSet = $this->getItemsByEntireRow($loader, $dataRow);
        }
        return $itemSet;
    }


    /**
     * Применяет нативные поля (без сохранения)
     * @param PriceLoader $loader Загрузчик прайсов
     * @param Material $item Материал, к которому нужно применить поля
     * @param array<mixed> $dataRow Входная строка данных
     * @param int|null $uniqueIndex Индекс (начиная с 0) уникальной колонки, null если нет
     * @return Material Возврат материала из поля $item
     */
    public function applyNativeFields(PriceLoader $loader, Material $item, array $dataRow, $uniqueIndex = null)
    {
        for ($j = 0; $j < count($dataRow); $j++) {
            $this->applyNativeField(
                $loader->columns[$j],
                $item,
                $dataRow[$j],
                (($uniqueIndex !== null) && ($uniqueIndex == $j))
            );
        }
        return $item;
    }


    /**
     * Применяет дополнительные поля (с сохранением)
     * @param PriceLoader $loader Загрузчик прайсов
     * @param Material $item Материал, к которому нужно применить поля
     * @param array<mixed> $dataRow Входная строка данных
     * @param bool $new Материал новый
     * @param int|null $uniqueIndex Индекс (начиная с 0) уникальной колонки, null если нет
     * @return Material Возврат материала из поля $item
     */
    public function applyCustomFields(PriceLoader $loader, Material $item, array $dataRow, $new, $uniqueIndex = null)
    {
        $affectedFields = [];
        for ($j = 0; $j < count($dataRow); $j++) {
            $affectedFields[] = $this->applyCustomField(
                $loader->columns[$j],
                $item,
                $dataRow[$j],
                $new,
                (($uniqueIndex !== null) && ($uniqueIndex == $j))
            );
        }
        // Применим значения по умолчанию к тем полям, которые не присутствовали
        if ($new) {
            foreach ($item->fields as $field) {
                if ($field->defval && !in_array($field->urn, $affectedFields)) {
                    $field->addValue($field->defval);
                }
            }
        }
    }


    /**
     * Применяет строку данных к материалу
     * @param PriceLoader $loader Загрузчик прайсов
     * @param Material $item Материал, к которому нужно применить поля
     * @param Page $root Корень загрузки прайса
     * @param Page $context Текущая категория загрузки прайса
     * @param array<mixed> $dataRow Входная строка данных
     * @param int|null $uniqueIndex Индекс (начиная с 0) уникальной колонки, null если нет
     * @param bool $test Тестовый режим
     * @return Material Возврат материала из поля $item
     */
    public function processItem(
        PriceLoader $loader,
        Material $item,
        Page $root,
        Page $context,
        array $dataRow,
        $uniqueIndex = null,
        $test = true
    ) {
        $new = !$item->id;
        $this->applyNativeFields($loader, $item, $dataRow, $uniqueIndex);
        if (!$test) {
            // 2020-02-10, AVS: для ускорения не обновляем связанные страницы
            $item->dontUpdateAffectedPages = true;
            $item->dontCheckPages = true;
            $item->commit();
            $this->checkAssoc($item, $root, $context, $new);
            $this->applyCustomFields($loader, $item, $dataRow, $new, $uniqueIndex);
        }
        return $item;
    }


    /**
     * Обрабатывает строку данных товара (без callback-преобразований)
     * @param PriceLoader $loader Загрузчик прайсов
     * @param array<mixed> $dataRow Входная строка данных
     * @param Page $root Корень загрузки прайса
     * @param Page $context Текущая категория загрузки прайса
     * @param int[] $affectedMaterialsIds Массив ID# "затронутых" материалов
     * @param array<[
     *            'time' => float Время, прошедшее с начала загрузки
     *            'text' => string Текст записи,
     *            'row' ?=> int К какой строке относится запись (относительно смещений, начиная с 0),
     *            'realrow' ?=> int К какой строке относится запись (абсолютно, без учета смещений, начиная с 0),
     *        ]> $log Лог выполнения
     * @param int|null $uniqueIndex Индекс (начиная с 0) уникальной колонки, null если нет
     * @param bool $test Тестовый режим
     * @param int $rows Сколько строк пропускать
     * @param float $st UNIX-timestamp времени начала выполнения загрузки
     * @param int $i Номер строки прайса (только актуальные, с учетом смещения)
     */
    public function processItemRow(
        PriceLoader $loader,
        array $dataRow,
        Page $root,
        Page $context,
        array &$affectedMaterialsIds,
        array &$log,
        $uniqueIndex = null,
        $test = true,
        $rows = 0,
        $st = 0,
        $i = 0
    ) {
        $itemSet = $this->getItems($loader, $dataRow, $uniqueIndex);
        // 2015-06-01, AVS: добавили понятие $new (тж. 11 строками ниже)
        $new = false;
        if (!$itemSet && $loader->create_materials) {
            $itemSet = [$this->createItem($loader)];
            $new = true;
        } elseif ($itemSet && !$loader->update_materials) {
            $itemSet = [];
        }

        foreach ($itemSet as $item) {
            $this->processItem($loader, $item, $root, $context, $dataRow, $uniqueIndex, $test);
            $affectedMaterialsIds[] = (int)$item->id;
            $logEntry = [
                'time' => (microtime(true) - $st),
                'text' => sprintf(
                    Module::i()->view->_('LOG_MATERIAL_' . ($new ? 'CREATED' : 'UPDATED')),
                    Package_Sub_Main::i()->url . '&action=edit_material&id=' . (int)$item->id,
                    $item->name
                ),
                'row' => $i,
                'realrow' => $i + $rows,
            ];
            $log[] = $logEntry;
            EventProcessor::emit('priceLoaderLog', $this, $logEntry, false);
            $item->rollback();
            unset($item);
        }
        unset($itemSet);
    }


    /**
     * Обрабатывает строку данных категории
     * @param PriceLoader $loader Загрузчик прайсов
     * @param array<mixed> $dataRow Входная строка данных
     * @param Page $root Корень загрузки прайса
     * @param Page $context Текущая категория загрузки прайса
     * @param int $virtualLevel При запрете создавать новые категории, сюда
     *                          устанавливается уровень не найденной категории
     *                          (чтобы игнорировать дочерние)
     * @param array<int[] Смещение => Page категория> $backtrace Текущая навигация
     * @param int[] $affectedPagesIds Массив ID# "затронутых" страниц
     * @param array<[
     *            'time' => float Время, прошедшее с начала загрузки
     *            'text' => string Текст записи,
     *            'row' ?=> int К какой строке относится запись (относительно смещений, начиная с 0),
     *            'realrow' ?=> int К какой строке относится запись (абсолютно, без учета смещений, начиная с 0),
     *        ]> $log Лог выполнения
     * @param bool $test Тестовый режим
     * @param int $rows Сколько строк пропускать
     * @param float $st UNIX-timestamp времени начала выполнения загрузки
     * @param int $i Номер строки прайса (только актуальные, с учетом смещения)
     */
    public function processPageRow(
        PriceLoader $loader,
        array $dataRow,
        Page $root,
        Page &$context,
        &$virtualLevel,
        array &$backtrace,
        array &$affectedPagesIds,
        array &$log,
        $test = true,
        $rows = 0,
        $st = 0,
        $i = 0
    ) {
        list($step, $name) = $this->parseCategoryRow($loader, $dataRow);
        if (!$virtualLevel || ($step <= $virtualLevel)) {
            if ($step > 0) {
                $backtrace = $this->cropBacktrace($backtrace, $step);
            } else {
                $backtrace = [];
            }
            $context = $this->lastCat($root, $backtrace);
            $t = (microtime(true) - $st);
            $logEntry = ['time' => $t, 'row' => $i, 'realrow' => $i + $rows];

            $foundPage = $this->getPage($context, $name);
            if ($foundPage || $loader->create_pages) {
                $new = false;
                if ($foundPage) {
                    $context = $foundPage;
                } elseif ($loader->create_pages) {
                    $context = $this->createPage($context, $name, $test);
                    $new = true;
                }
                $affectedPagesIds[] = (int)$context->id;
                $backtrace[$step] = $context;
                $virtualLevel = null;
                $logEntry['text'] = sprintf(
                    Module::i()->view->_('LOG_PAGE_' . ($new ? 'CREATED' : 'SELECTED')),
                    Package_Sub_Main::i()->url . '&action=edit_page&id=' . (int)$context->id,
                    $context->name
                );
            } else {
                $virtualLevel = $step;
                $logEntry['text'] = sprintf(
                    Module::i()->view->_('LOG_PAGE_NOT_SELECTED'),
                    $name
                );
            }
            $log[] = $logEntry;
            EventProcessor::emit('priceLoaderLog', $this, $logEntry, false);
        }
    }


    /**
     * Обрабатывает массив данных
     * @param PriceLoader $loader Загрузчик прайсов
     * @param array<[mixed]> $data Таблица данных, приведенные к надлежащему формату
     *                                  (игнорирование заголовочных строк и столбцов, но без callback-преобразований)
     * @param Page $page Страница, в которую загружаем
     * @param bool $test Тестовый режим
     * @param int[] $affectedMaterialsIds Массив ID# "затронутых" материалов
     * @param int[] $affectedPagesIds Массив ID# "затронутых" страниц
     * @param array<[
     *            'time' => float Время, прошедшее с начала загрузки
     *            'text' => string Текст записи,
     *            'row' ?=> int К какой строке относится запись (относительно смещений, начиная с 0),
     *            'realrow' ?=> int К какой строке относится запись (абсолютно, без учета смещений, начиная с 0),
     *        ]> $log Лог выполнения
     * @param array<array<string>> $rawData Массив сырых данных
     * @param bool $test Тестовый режим
     * @param int $rows Сколько строк пропускать
     * @param int $cols Сколько столбцов пропускать
     * @param float $st UNIX-timestamp времени начала выполнения загрузки
     */
    public function processData(
        PriceLoader $loader,
        array $data,
        Page $page,
        array &$affectedMaterialsIds,
        array &$affectedPagesIds,
        array &$log,
        array &$rawData,
        $test = true,
        $rows = 0,
        $cols = 0,
        $st = 0
    ) {
        // Получим номер колонки с уникальным полем
        $uniqueIndex = $this->getUniqueColumnIndex($loader);
        $backtrace = [];
        $context = $page;
        $virtualLevel = null; // При запрете создавать новые категории, сюда
                              // устанавливается уровень не найденной категории
                              // (чтобы игнорировать дочерние)

        for ($i = 0; $i < count($data); $i++) {
            $dataRow = $data[$i];
            if ($this->isItemDataRow($dataRow)) {
                // Товар
                $dataRow = $this->convertRow($loader, $dataRow);
                $this->processItemRow(
                    $loader,
                    $dataRow,
                    $page,
                    $context,
                    $affectedMaterialsIds,
                    $log,
                    $uniqueIndex,
                    $test,
                    $rows,
                    $st,
                    $i
                );
            } elseif ($this->isPageDataRow($dataRow)) {
                // Категория
                $this->processPageRow(
                    $loader,
                    $dataRow,
                    $page,
                    $context,
                    $virtualLevel,
                    $backtrace,
                    $affectedPagesIds,
                    $log,
                    $test,
                    $rows,
                    $st,
                    $i
                );
            }
            $rawData[] = array_map(function ($x) {
                if (is_scalar($x)) {
                    return $x;
                } else {
                    return json_encode($x, JSON_UNESCAPED_UNICODE);
                }
            }, $dataRow);
        }
        // 2020-02-10, AVS: для ускорения обновим связанные страницы здесь
        Material_Type::updateAffectedPagesForSelf();
        Material_Type::updateAffectedPagesForMaterials();
    }


    /**
     * Очищает материалы
     * @param Material_Type $materialType Тип материалов
     * @param Page $deleteRoot Корень для удаления материалов
     * @param array<[
     *            'time' => float Время, прошедшее с начала загрузки
     *            'text' => string Текст записи,
     *            'row' ?=> int К какой строке относится запись (относительно смещений, начиная с 0),
     *            'realrow' ?=> int К какой строке относится запись (абсолютно, без учета смещений, начиная с 0),
     *        ]> $log Лог выполнения
     * @param int[] $affectedMaterialsIds Массив ID# "затронутых" материалов
     * @param bool $test Тестовый режим
     * @param float $st UNIX-timestamp времени начала выполнения загрузки
     */
    public function clearMaterials(
        Material_Type $materialType,
        Page $deleteRoot,
        array &$log,
        array $affectedMaterialsIds = [],
        $test = true,
        $st = 0
    ) {
        $assets = $this->findMaterialsFieldsAndAttachmentsToClear($materialType, $deleteRoot, $affectedMaterialsIds);
        $materialsToClearIds = $assets[Material::class];
        $fieldsToClearIds = $assets[Field::class];
        $attachmentsToClearIds = $assets[Attachment::class];

        if (!$test) {
            $this->deleteMaterialsByIds($materialsToClearIds);
            $this->deleteAttachmentsByIds($attachmentsToClearIds);
        } else {
            foreach ($materialsToClearIds as $materialId) {
                $material = new Material($materialId);
                $logEntry = [
                    'time' => (microtime(true) - $st),
                    'text' => sprintf(
                        Module::i()->view->_('LOG_DELETE_MATERIALS'),
                        Package_Sub_Main::i()->url . '&action=edit_material&id=' . $material->id,
                        $material->name
                    )
                ];
                $log[] = $logEntry;
                EventProcessor::emit('priceLoaderLog', $this, $logEntry, false);
            }
            $this->logDeleteFieldsAndAttachments($log, $fieldsToClearIds, $attachmentsToClearIds, $st);
        }
    }


    /**
     * Очищает страницы
     * @param Page $deleteRoot Корень для удаления материалов
     * @param array<[
     *            'time' => float Время, прошедшее с начала загрузки
     *            'text' => string Текст записи,
     *            'row' ?=> int К какой строке относится запись (относительно смещений, начиная с 0),
     *            'realrow' ?=> int К какой строке относится запись (абсолютно, без учета смещений, начиная с 0),
     *        ]> $log Лог выполнения
     * @param int[] $affectedPagesIds Массив ID# "затронутых" страниц
     * @param bool $test Тестовый режим
     * @param float $st UNIX-timestamp времени начала выполнения загрузки
     */
    public function clearPages(
        Page $deleteRoot,
        array &$log,
        array $affectedPagesIds = [],
        $test = true,
        $st = 0
    ) {
        $assets = $this->findPagesFieldsAndAttachmentsToClear($deleteRoot, $affectedPagesIds);
        $pagesToClearIds = $assets[Page::class];
        $fieldsToClearIds = $assets[Field::class];
        $attachmentsToClearIds = $assets[Attachment::class];

        if (!$test) {
            $this->deletePagesByIds($pagesToClearIds);
            $this->deleteAttachmentsByIds($attachmentsToClearIds);
        } else {
            foreach ($pagesToClearIds as $pageId) {
                $page = new Page($pageId);
                $logEntry = [
                    'time' => (microtime(true) - $st),
                    'text' => sprintf(
                        Module::i()->view->_('LOG_DELETE_PAGES'),
                        Package_Sub_Main::i()->url . '&action=edit_page&id=' . $page->id,
                        $page->name
                    )
                ];
                $log[] = $logEntry;
                EventProcessor::emit('priceLoaderLog', $this, $logEntry, false);
            }
            $this->logDeleteFieldsAndAttachments($log, $fieldsToClearIds, $attachmentsToClearIds, $st);
        }
    }


    /**
     * Очищает незатронутые материалы и категории
     * @param PriceLoader $loader Загрузчик прайсов
     * @param Page $page Страница, в которую загружали прайс
     * @param array $log <pre><code>array<[
     *     'time' => float Время, прошедшее с начала загрузки
     *     'text' => string Текст записи,
     *     'row' ?=> int К какой строке относится запись (относительно смещений, начиная с 0),
     *     'realrow' ?=> int К какой строке относится запись (абсолютно, без учета смещений, начиная с 0),
     * ]></code></pre> Лог выполнения
     * @param int[] $affectedMaterialsIds Массив ID# "затронутых" материалов
     * @param int[] $affectedPagesIds Массив ID# "затронутых" страниц
     * @param int $clear Очищать предыдущие материалы и/или страницы (варианты:
     *                       PriceLoader::DELETE_PREVIOUS_MATERIALS_NONE - не очищать
     *                       PriceLoader::DELETE_PREVIOUS_MATERIALS_MATERIALS_ONLY - очищать только материалы
     *                       PriceLoader::DELETE_PREVIOUS_MATERIALS_MATERIALS_AND_PAGES - очищать материалы и страницы
     *                   )
     * @param bool $test Тестовый режим
     * @param float $st UNIX-timestamp времени начала выполнения загрузки
     */
    public function clear(
        PriceLoader $loader,
        Page $page,
        array &$log,
        array $affectedMaterialsIds = [],
        array $affectedPagesIds = [],
        $clear = 0,
        $test = true,
        $st = 0
    ) {
        if (count($page->parents) > count($loader->Page->parents)) {
            $deleteRoot = $page;
        } else {
            $deleteRoot = $loader->Page;
        }
        if (in_array($clear, [
            PriceLoader::DELETE_PREVIOUS_MATERIALS_MATERIALS_ONLY,
            PriceLoader::DELETE_PREVIOUS_MATERIALS_MATERIALS_AND_PAGES
        ])) {
            // Очищаем материалы
            $this->clearMaterials($loader->Material_Type, $deleteRoot, $log, $affectedMaterialsIds, $test, $st);
        }
        if ($clear == PriceLoader::DELETE_PREVIOUS_MATERIALS_MATERIALS_AND_PAGES) {
            // Очищаем страницы
            $this->clearPages($deleteRoot, $log, $affectedPagesIds, $test, $st);
        }

        $logEntry = [
            'time' => (microtime(true) - $st),
            'text' => sprintf(
                Module::i()->view->_('LOG_OLD_MATERIALS_CLEARED'),
                $loader->Material_Type->name,
                Package_Sub_Main::i()->url . '&id=' . (int)$page->id,
                $page->name
            )
        ];
        $log[] = $logEntry;
        EventProcessor::emit('priceLoaderLog', $this, $logEntry, false);
    }


    /**
     * Выгружает заголовок
     * @param PriceLoader $loader Загрузчик прайсов
     * @return array<string>
     */
    public function exportHeader(PriceLoader $loader)
    {
        $header = [];
        foreach ($loader->columns as $col) {
            $x = '';
            if ($col->Field->id) {
                $x = $col->Field->name;
            } elseif ($col->fid == 'name') {
                $x = Module::i()->view->_('NAME');
            } elseif ($col->fid == 'urn') {
                $x = Module::i()->view->_('URN');
            } elseif ($col->fid == 'vis') {
                $x = Package::i()->view->_('VISIBILITY');
            } elseif ($col->fid == 'description') {
                $x = Module::i()->view->_('DESCRIPTION');
            }
            $header[] = trim((string)$x);
        }
        return $header;
    }


    /**
     * Выгружает строку категории
     * @param PriceLoader $loader Загрузчик прайсов
     * @param Page $page Страница, для которой выгружаем строку
     * @param int $level Уровень вложенности категории относительно корня (> 0, 0 - корень)
     * @return array<string>
     */
    public function exportPageRow(PriceLoader $loader, Page $page, $level)
    {
        if ($loader->catalog_offset) {
            $row = [
                str_repeat(' ', $loader->catalog_offset * ($level - 1)) .
                trim((string)$page->name)
            ];
        } else {
            $row = array_fill(0, $level, '');
            $row[$level - 1] = $page->name;
        }
        return $row;
    }


    /**
     * Выгружает ячейку материала
     * @param PriceLoader_Column $column Загрузчик прайсов
     * @param Material $material Материал, для которого выгружаем строку
     * @return string
     */
    public function exportMaterialColumn(PriceLoader_Column $column, Material $material)
    {
        $x = null;
        $field = $column->Field->deepClone();
        $materialType = $this->loader->Material_Type;
        if ($field->id) {
            $fieldURN = $field->urn;
            $field->Owner = $material;
            if ($field->multiple) {
                $x = $field->getValues(true);
                $x = array_map(function ($y) use ($field) {
                    $z = $field->doRich($y);
                    if (in_array($field->datatype, ['number', 'range'])) {
                        $z = (float)$z;
                    }
                    return $z;
                }, $x);
            } else {
                $x = $field->doRich();
                if (in_array($field->datatype, ['number', 'range'])) {
                    $x = (float)$x;
                }
            }
        } elseif ($column->fid) {
            $x = $material->{$column->fid};
        } else {
            return '';
        }
        if ($f = $column->CallbackDownload) {
            $x = $f($x, $material);
        }
        if (is_array($x)) {
            $x = implode(', ', $x);
        }
        if (is_string($x)) {
            $x = trim((string)$x);
        }
        return $x;
    }


    /**
     * Выгружает строку материала
     * @param PriceLoader $loader Загрузчик прайсов
     * @param Material $material Материал, для которого выгружаем строку
     * @return array<string>
     */
    public function exportMaterialRow(PriceLoader $loader, Material $material)
    {
        $row = [];
        foreach ($loader->columns as $col) {
            $x = $this->exportMaterialColumn($col, $material);
            $row[] = $x;
        }
        $material->rollback();
        return $row;
    }


    /**
     * Выгружает данные
     * @param PriceLoader $loader Загрузчик прайсов
     * @param Page $page Страница, в которую загружаем
     * @param int $rows Сколько строк пропускать
     * @param int $level Уровень вложенности
     * @param Material_Type[] $materialTypes Типы материалов, которые выгружаем
     * @return array
     */
    public function exportData(
        PriceLoader $loader,
        Page $page = null,
        $rows = 0,
        $level = 0,
        array $materialTypes = []
    ): array {
        if (!$materialTypes) {
            $materialTypes = $loader->Material_Type->selfAndChildrenIds;
        }

        $data = [];
        if (!$page) {
            $page = $loader->Page;
        }
        if ($level) {
            $data[] = $this->exportPageRow($loader, $page, $level);
        }

        $sqlQuery = "SELECT tM.* FROM " . Material::_tablename() . " AS tM ";
        if (!$loader->Material_Type->global_type &&
            ($loader->cats_usage != PriceLoader::CATS_USAGE_DONT_REPEAT)
        ) {
            $sqlQuery .= " JOIN " . Material::_dbprefix() . "cms_materials_pages_assoc AS tMPA ON tMPA.id = tM.id";
        }
        $sqlQuery .= " WHERE tM.pid IN (" . implode(", ", $materialTypes ?: [0]) . ") ";
        if (!$loader->Material_Type->global_type) {
            switch ($loader->cats_usage) {
                case PriceLoader::CATS_USAGE_DONT_REPEAT:
                    $sqlQuery .= " AND tM.cache_url_parent_id = " . (int)$page->id;
                    break;
                case PriceLoader::CATS_USAGE_DONT_USE:
                    $pagesIds = PageRecursiveCache::i()->getSelfAndChildrenIds($page->id);
                    $sqlQuery .= " AND tMPA.pid IN (" . implode(", ", $pagesIds) . ")";
                    break;
                default:
                    $sqlQuery .= " AND tMPA.pid = " . (int)$page->id;
                    break;
            }
        }
        $sqlQuery .= " GROUP BY tM.id
                       ORDER BY NOT tM.priority, tM.priority, tM.id";
        $sqlResult = Material::_SQL()->get($sqlQuery);
        $materialsIds = array_map(function ($sqlRow) {
            return (int)$sqlRow['id'];
        }, $sqlResult);
        Field::prefetch($materialsIds);
        if (($rows > 0) && !$level) {
            $data[] = $this->exportHeader($loader);
        }
        foreach ($sqlResult as $materialData) {
            $material = new Material($materialData);
            $data[] = $this->exportMaterialRow($loader, $material);
        }
        if ($loader->cats_usage != PriceLoader::CATS_USAGE_DONT_USE) {
            foreach ($page->children as $child) {
                $childrenData = $this->exportData($loader, $child, 0, $level + 1, $materialTypes);
                $data = array_merge($data, $childrenData);
            }
        }
        return $data;
    }
}
