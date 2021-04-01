<?php
/**
 * Загрузчик прайс-листов
 */
namespace RAAS\CMS\Shop;

use SOME\SOME;
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
 */
class PriceLoader extends SOME
{
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

    public function commit()
    {
        if (!trim($this->name) && trim($this->Material_Type->name)) {
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
     * @param array|null $file Файл для загрузки <pre>[
     *     'name' => string Имя файла при загрузке,
     *     'tmp_name' => string Путь к загруженному временному файлу,
     *     'type' => string MIME-тип
     * ]</pre>
     * @param Page|null $Page Корневая страница для загрузки
     * @param bool $test Тестовый режим
     * @param int|bool $clear Режим очистки неиспользуемых товаров/страниц
     * @param int|null $rows Отступ строк
     * @param int|null $cols Отступ колонок
     */
    public function upload(
        array $file = null,
        Page $Page = null,
        $test = false,
        $clear = false,
        $rows = null,
        $cols = null
    ) {
        $Loader = $this;
        if ($Page === null) {
            $Page = $Loader->Page;
        }
        if ($rows === null) {
            $rows = $Loader->rows;
        }
        if ($cols === null) {
            $cols = $Loader->cols;
        }
        $OUT = eval('?' . '>' . $this->Interface->description);
        return $OUT;
    }


    /**
     * Выгрузить прайс
     * @param Page|null $Page Корневая страница для загрузки
     * @param int|null $rows Отступ строк
     * @param int|null $cols Отступ колонок
     * @param string $type Тип выгружаемого файла
     * @param string $encoding Кодировка
     */
    public function download(
        Page $Page,
        $rows = 0,
        $cols = 0,
        $type = null,
        $encoding = null
    ) {
        $Loader = $this;
        if ($Page === null) {
            $Page = $Loader->Page;
        }
        if ($rows === null) {
            $rows = $Loader->rows;
        }
        if ($cols === null) {
            $cols = $Loader->cols;
        }
        $OUT = eval('?' . '>' . $this->Interface->description);
        return $OUT;
    }


    /**
     * Импорт по URN
     * @param string $urn URN для импорта
     * @return self|null
     */
    public static function importByURN($urn = '')
    {
        $sqlQuery = "SELECT * FROM " . self::_tablename() . " WHERE urn = ?";
        if ($sqlResult = self::$SQL->getline([$sqlQuery, $urn])) {
            return new self($sqlResult);
        }
        return null;
    }

}
