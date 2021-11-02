<?php
/**
 * Тип корзины
 */
namespace RAAS\CMS\Shop;

use SOME\SOME;
use SOME\Text;
use RAAS\CMS\Form;
use RAAS\CMS\ImportByURNTrait;
use RAAS\CMS\Material_Type;
use RAAS\CMS\Package;

/**
 * Класс типа корзины
 * @property-read Form $Form Форма заказа
 * @property-read Material_Type[] $material_types Типы материалов
 * @property-read int $unreadOrders Количество непрочитанных заявок
 */
class Cart_Type extends SOME
{
    use ImportByURNTrait;

    protected static $tablename = 'cms_shop_cart_types';

    protected static $defaultOrderBy = "name";

    protected static $cognizableVars = ['unreadOrders'];

    protected static $references = [
        'Form' => [
            'FK' => 'form_id',
            'classname' => Form::class,
            'cascade' => false,
        ],
    ];

    protected static $links = [
        'material_types' => [
            'tablename' => 'cms_shop_cart_types_material_types_assoc',
            'field_from' => 'ctype',
            'field_to' => 'mtype',
            'classname' => Material_Type::class,
        ]
    ];

    public function commit()
    {
        if (!trim($this->name) && trim($this->Form->name)) {
            $this->name = $this->Form->name;
        }
        if (!$this->urn && $this->name) {
            $this->urn = Text::beautify($this->name);
        }
        Package::i()->getUniqueURN($this);
        parent::commit();
        $sqlQuery = "DELETE FROM " . static::_dbprefix() . static::$links['material_types']['tablename']
                   . " WHERE " . static::$links['material_types']['field_from'] . " = " . (int)$this->id;
        static::$SQL->query($sqlQuery);
        $arr = [];
        foreach ((array)$this->mtypes as $row) {
            $arr[] = [
                static::$links['material_types']['field_from'] => (int)$this->id,
                static::$links['material_types']['field_to'] => (int)$row['id'],
                'price_id' => (int)$row['price_id'],
                'price_callback' => !(int)$row['price_id'] ? $row['price_callback'] : ''
            ];
        }
        if ($arr) {
            static::$SQL->add(static::$links['material_types']['tablename'], $arr);
        }
    }


    protected function _unreadOrders()
    {
        $sqlQuery = "SELECT COUNT(*) FROM " . Order::_tablename()
                  . " WHERE pid = " . (int)$this->id
                  . "   AND NOT vis";
        return static::$SQL->getvalue($sqlQuery);
    }


    /**
     * Рассчитывает вес товаров
     * @param Material[] $items Материалы с полем amount - количество
     * @return float Вес в кг
     */
    public function getWeight(array $items)
    {
        $result = 0;
        if ($f = $this->weight_callback) {
            $result = (float)eval($f);
        }
        return $result;
    }


    /**
     * Рассчитывает размер заказа
     * @param Material[] $items Материалы с полем amount - количество
     * @return array <pre><code>[
     *     int Длина, см,
     *     int Ширина, см,
     *     int Высота, см
     * ]</code></pre>
     */
    public function getSizes(array $items)
    {
        $result = [];
        if ($f = $this->sizes_callback) {
            $result = (array)eval($f);
        }
        $result = array_map('intval', $result);
        $result = array_values($result);
        while (count($result) < 3) {
            $result[] = 0;
        }
        $result = array_slice($result, 0, 3);
        return $result;
    }
}
