<?php
/**
 * Колонка загрузчика прайсов
 */
namespace RAAS\CMS\Shop;

use SOME\SOME;
use RAAS\CMS\Material_Field;

/**
 * Класс колонки загрузчика прайсов
 * @property-read callable $Callback Функция обработки данных при загрузке
 * @property-read callable $CallbackDownload Функция обработки данных при выгрузке
 * @property-read bool $isNative Нативное поле
 * @property-read PriceLoader $Parent Родительский загрузчик прайсов
 * @property-read Material_Field $[name] [<description>]
 */
class PriceLoader_Column extends SOME
{
    protected static $tablename = 'cms_shop_priceloaders_columns';
    protected static $defaultOrderBy = "priority";
    protected static $cognizableVars = ['Callback', 'CallbackDownload', 'isNative'];
    protected static $references = [
        'Parent' => [
            'FK' => 'pid',
            'classname' => PriceLoader::class,
            'cascade' => true
        ],
        'Field' => [
            'FK' => 'fid',
            'classname' => Material_Field::class,
            'cascade' => true
        ],
    ];


    /**
     * Метод получения функции обработки данных при загрузке
     * @return callable
     */
    public function _Callback()
    {
        $t = $column = $this;
        if (trim((string)$this->callback)) {
            $f = $this->callback;
            return function ($x) use ($column, $f) {
                $data = $val = $x;
                return eval($f);
            };
        }
    }


    /**
     * Метод получения функции обработки данных при выгрузке
     * @return callable
     */
    public function _CallbackDownload()
    {
        $t = $column = $this;
        if (trim($this->callback_download ?? '')) {
            $f = $this->callback_download;
            return function ($x, $row) use ($f, $column) {
                $data = $val = $x;
                $material = $item = $row;
                return eval($f);
            };
        }
    }


    /**
     * Нативное ли поле
     * @return bool
     */
    public function _isNative()
    {
        return (!$this->Field->id && $this->fid);
    }
}
