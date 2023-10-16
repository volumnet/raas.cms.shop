<?php
/**
 * Загрузчик изображений
 */
namespace RAAS\CMS\Shop;

use SOME\SOME;
use RAAS\CMS\ImportByURNTrait;
use RAAS\CMS\Material_Field;
use RAAS\CMS\Material_Type;
use RAAS\CMS\Package;
use RAAS\CMS\Snippet;

/**
 * Класс загрузчика изображений
 * @property-read Material_Type $Material_Type Тип материалов
 * @property-read Material_Field $Unique_Field Уникальное поле
 * @property-read Material_Field $Image_Field Поле изображений
 * @property-read Snippet $Interface Сниппет интерфейса
 */
class ImageLoader extends SOME
{
    use ImportByURNTrait;

    protected static $tablename = 'cms_shop_imageloaders';

    protected static $defaultOrderBy = "name";

    protected static $references = [
        'Material_Type' => [
            'FK' => 'mtype',
            'classname' => Material_Type::class,
            'cascade' => true,
        ],
        'Unique_Field' => [
            'FK' => 'ufid',
            'classname' => Material_Field::class,
            'cascade' => false,
        ],
        'Image_Field' => [
            'FK' => 'ifid',
            'classname' => Material_Field::class,
            'cascade' => false,
        ],
        'Interface' => [
            'FK' => 'interface_id',
            'classname' => Snippet::class,
            'cascade' => true,
        ],
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
     * Загружает файлы
     * @param array|null $files <pre><code>array<[
     *     'name' => string Наименование файла,
     *     'tmp_name' => string Путь к файлу,
     *     'type' => string MIME-тип файла,
     *     'size' => int Размер файла в байтах
     * ]></code></pre> Массив файлов для загрузки
     * @param bool $test Тестовый режим
     * @param bool $clear Очистить предыдущие изображения
     * @return mixed
     */
    public function upload(array $files = null, bool $test = false, bool $clear = false)
    {
        $out = $this->Interface->process([
            'Loader' => $this,
            'files' => $files,
            'test' => $test,
            'clear' => $clear,
        ]);
        return $out;
    }


    /**
     * Выгружает файлы
     * @return mixed
     */
    public function download()
    {
        $out = $this->Interface->process(['Loader' => $this]);
        return $out;
    }
}
