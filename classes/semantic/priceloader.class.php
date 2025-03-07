<?php
/**
 * Загрузчик прайс-листов
 */
declare(strict_types=1);

namespace RAAS\CMS\Shop;

use SOME\SOME;
use RAAS\CMS\ImportByURNTrait;
use RAAS\CMS\Material_Field;
use RAAS\CMS\Material_Type;
use RAAS\CMS\Page;
use RAAS\CMS\Package;
use RAAS\CMS\Snippet;

/**
 * Класс загрузчика прайс-листов
 * @property-read Material_Type $Material_Type Тип материалов
 * @property-read Material_Field $Unique_Field Уникальное поле
 * @property-read Snippet $Interface Интерфейс загрузчика
 * @property-read Page $Page Корневая страница
 * @property-read PriceLoader_Column[] $columns Набор колонок
 * @property-read array $columnsByIds <pre><code>array<
 *     string[] ID# колонки загрузчика => PriceLoader_Column
 * ></code></pre> Колонки по их ID#
 * @property-read PriceLoader_Column|null $uniqueColumn Уникальная колонка
 * @property-read PriceLoader_Column|null $nameColumn Колонка наименования
 */
class PriceLoader extends SOME
{
    use ImportByURNTrait;

    /**
     * Не удалять неиспользуемые материалы и страницы
     */
    const DELETE_PREVIOUS_MATERIALS_NONE = 0;

    /**
     * Удалять неиспользуемые материалы, оставлять неиспользуемые страницы
     */
    const DELETE_PREVIOUS_MATERIALS_MATERIALS_ONLY = 1;

    /**
     * Удалять неиспользуемые материалы и страницы
     */
    const DELETE_PREVIOUS_MATERIALS_MATERIALS_AND_PAGES = 2;

    /**
     * Добавлять значения в медиа-поля, только если пустые
     */
    const MEDIA_FIELDS_APPEND_IF_EMPTY = 0;

    /**
     * Добавлять значения в медиа-поля только к новым товарам
     */
    const MEDIA_FIELDS_APPEND_TO_NEW_ONLY = 1;

    /**
     * Добавлять значения в медиа-поля
     */
    const MEDIA_FIELDS_APPEND = 2;

    /**
     * Заменять значения в медиа-полях
     */
    const MEDIA_FIELDS_REPLACE = 3;

    /**
     * Использовать категории
     */
    const CATS_USAGE_NORMAL = 0;

    /**
     * Использовать категории, но не дублировать товары при выгрузке
     */
    const CATS_USAGE_DONT_REPEAT = 1;

    /**
     * Не использовать категории
     */
    const CATS_USAGE_DONT_USE = 2;

    protected static $tablename = 'cms_shop_priceloaders';

    protected static $defaultOrderBy = "name";

    protected static $references = [
        'Material_Type' => [
            'FK' => 'mtype',
            'classname' => Material_Type::class,
            'cascade' => true
        ],
        'Unique_Field' => [
            'FK' => 'ufid',
            'classname' => Material_Field::class,
            'cascade' => false
        ],
        'Interface' => [
            'FK' => 'interface_id',
            'classname' => Snippet::class,
            'cascade' => true
        ],
        'Page' => [
            'FK' => 'cat_id',
            'classname' => Page::class,
            'cascade' => false
        ],
    ];

    protected static $children = [
        'columns' => [
            'classname' => PriceLoader_Column::class,
            'FK' => 'pid'
        ]
    ];


    protected static $cognizableVars = ['uniqueColumn', 'columnsByIds', 'nameColumn'];

    public function commit()
    {
        if (!trim((string)$this->name) && trim((string)$this->Material_Type->name)) {
            $this->name = $this->Material_Type->name;
        }
        if (!$this->urn && $this->name) {
            $this->urn = $this->name;
        }
        Package::i()->getUniqueURN($this);
        parent::commit();
    }


    /**
     * Загрузить прайс
     * @param ?array $file Файл для загрузки <pre>[
     *     'name' => string Имя файла при загрузке,
     *     'tmp_name' => string Путь к загруженному временному файлу,
     *     'type' => string MIME-тип
     * ]</pre>
     * @param ?Page $page Корневая страница для загрузки
     * @param bool $test Тестовый режим
     * @param int $clear Режим очистки неиспользуемых товаров/страниц
     * @param ?int $rows Отступ строк
     * @param ?int $cols Отступ колонок
     * @return mixed
     */
    public function upload(
        ?array $file = null,
        ?Page $page = null,
        ?bool $test = null,
        int $clear = self::DELETE_PREVIOUS_MATERIALS_NONE,
        ?int $rows = null,
        ?int $cols = null
    ) {
        if ($page === null) {
            $page = $this->Page;
        }
        if ($rows === null) {
            $rows = (int)$this->rows;
        }
        if ($cols === null) {
            $cols = (int)$this->cols;
        }
        $test = (bool)$test;
        $out = null;
        if (($interfaceClassname = trim((string)$this->interface_classname)) &&
            class_exists($interfaceClassname) &&
            (
                ($interfaceClassname == PriceloaderInterface::class) ||
                is_subclass_of($interfaceClassname, PriceloaderInterface::class)
            )
        ) {
            $type = '';
            if ($file['name'] ?? '') {
                $type = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
            }
            $interface = new $interfaceClassname($this);
            $out = $interface->upload($file['tmp_name'] ?? '', $type, $page, $test, $clear, $rows, $cols);
        } elseif ($this->Interface) {
            $out = $this->Interface->process([
                'Loader' => $this,
                'file' => $file,
                'Page' => $page,
                'test' => $test,
                'clear' => $clear,
                'rows' => $rows,
                'cols' => $cols,
            ]);
        }
        return $out;
    }


    /**
     * Выгрузить прайс
     * @param ?Page $page Корневая страница для загрузки
     * @param ?int $rows Отступ строк
     * @param ?int $cols Отступ колонок
     * @param ?string $type Тип выгружаемого файла
     * @param ?string $encoding Кодировка
     */
    public function download(
        ?Page $page = null,
        ?int $rows = null,
        ?int $cols = null,
        ?string $type = null,
        ?string $encoding = null
    ) {
        if ($page === null) {
            $page = $this->Page;
        }
        if ($rows === null) {
            $rows = (int)$this->rows;
        }
        if ($cols === null) {
            $cols = (int)$this->cols;
        }
        if (($interfaceClassname = trim((string)$this->interface_classname)) &&
            class_exists($interfaceClassname) &&
            (
                ($interfaceClassname == PriceloaderInterface::class) ||
                is_subclass_of($interfaceClassname, PriceloaderInterface::class)
            )
        ) {
            $interface = new $interfaceClassname($this);
            $out = $interface->download($page, $rows, $cols, $type, $encoding);
        } elseif ($this->Interface) {
            $out = $this->Interface->process([
                'Loader' => $this,
                'Page' => $page,
                'rows' => $rows,
                'cols' => $cols,
                'type' => $type,
                'encoding' => $encoding,
            ]);
        }
        return $out;
    }


    /**
     * Получает уникальную колонку
     * @return PriceLoader_Column|null
     */
    protected function _uniqueColumn()
    {
        foreach ($this->columns as $column) {
            if ($column->fid == $this->ufid) {
                return $column;
            }
        }
        return null;
    }


    /**
     * Получает колонки загрузчика по их ID#
     * @return array <pre><code>array<
     *     string[] ID# колонки загрузчика => PriceLoader_Column
     * ></code></pre>
     */
    protected function _columnsByIds()
    {
        $result = [];
        foreach ($this->columns as $column) {
            $result[(string)$column->id] = $column;
        }
        return $result;
    }


    /**
     * Получает колонку наименования
     * @return PriceLoader_Column|null
     */
    protected function _nameColumn()
    {
        foreach ($this->columns as $column) {
            if ($column->fid == 'name') {
                return $column;
            }
        }
        return null;
    }
}
